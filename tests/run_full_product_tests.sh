#!/bin/zsh
set -euo pipefail
set +x
setopt typeset_silent

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PHP_BIN="${PHP_BIN:-/Applications/XAMPP/xamppfiles/bin/php}"
APP_URL="${APP_URL:-http://127.0.0.1:8099}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin123}"
TEMP_PASSWORD="${TEMP_PASSWORD:-Admin@1234!}"

TMP_DIR="$(mktemp -d)"
COOKIE="$TMP_DIR/cookie.txt"
DEFECTS="$TMP_DIR/defects.log"
ROUTE_LOG="$TMP_DIR/routes.log"

cleanup() {
  if [[ -n "${SERVER_PID:-}" ]]; then
    kill "$SERVER_PID" >/dev/null 2>&1 || true
  fi
  echo "Artifacts: $TMP_DIR"
}
trap cleanup EXIT

log() {
  printf '%s\n' "$*"
}

record_defect() {
  printf '%s\n' "$*" | tee -a "$DEFECTS" >/dev/null
}

extract_csrf() {
  local file="$1"
  rg -o 'name="_csrf" value="[^"]+"' "$file" | head -n1 | sed -E 's/.*value="([^"]+)"/\1/'
}

status_code() {
  sed -n '1s/.* \([0-9][0-9][0-9]\) .*/\1/p' "$1"
}

start_server() {
  log "Starting local PHP server at $APP_URL"
  (
    cd "$ROOT_DIR"
    "$PHP_BIN" -S 127.0.0.1:8099 index.php
  ) >"$TMP_DIR/server.log" 2>&1 &
  SERVER_PID=$!

  local i=0
  until curl -sS "$APP_URL/index.php?route=auth/login" >/dev/null 2>&1; do
    i=$((i+1))
    if [[ $i -gt 40 ]]; then
      record_defect "Could not start local test server"
      return 1
    fi
    sleep 0.25
  done
}

lint_php() {
  log "Running PHP syntax check"
  rg --files -g '*.php' "$ROOT_DIR" | xargs -n1 "$PHP_BIN" -l >/dev/null
}

db_check() {
  log "Checking DB connectivity"
  (
    cd "$ROOT_DIR"
    "$PHP_BIN" -r '$cfg=require "config/database.php"; $pdo=new PDO("mysql:host={$cfg["host"]};port={$cfg["port"]};dbname={$cfg["dbname"]};charset={$cfg["charset"]}",$cfg["username"],$cfg["password"]); echo "DB_OK\n";'
  ) >/dev/null
}

login() {
  curl -sS -c "$COOKIE" "$APP_URL/index.php?route=auth/login" -o "$TMP_DIR/login_get.html"
  local csrf
  csrf="$(extract_csrf "$TMP_DIR/login_get.html")"

  curl -sS -b "$COOKIE" -c "$COOKIE" -D "$TMP_DIR/login_headers.txt" -o /dev/null \
    -X POST "$APP_URL/index.php?route=auth/login" \
    --data-urlencode "identifier=$ADMIN_USER" \
    --data-urlencode "password=$ADMIN_PASSWORD" \
    --data-urlencode "_csrf=$csrf"

  if rg -q 'Location: index.php\?route=dashboard/index' "$TMP_DIR/login_headers.txt"; then
    return 0
  fi
  return 1
}

reset_password_and_login() {
  log "Default login failed; using forgot/reset flow for test access"

  curl -sS -b "$COOKIE" -c "$COOKIE" "$APP_URL/index.php?route=auth/forgotPassword" -o "$TMP_DIR/forgot_get.html"
  local csrf token csrf2
  csrf="$(extract_csrf "$TMP_DIR/forgot_get.html")"

  curl -sS -b "$COOKIE" -c "$COOKIE" -o /dev/null \
    -X POST "$APP_URL/index.php?route=auth/forgotPassword" \
    --data-urlencode "identifier=$ADMIN_USER" \
    --data-urlencode "_csrf=$csrf"

  curl -sS -b "$COOKIE" -c "$COOKIE" "$APP_URL/index.php?route=auth/forgotPassword" -o "$TMP_DIR/forgot_after.html"
  token="$(rg -o 'Reset token generated: [^.]+' "$TMP_DIR/forgot_after.html" | head -n1 | sed -E 's/Reset token generated: //')"

  if [[ -z "$token" ]]; then
    record_defect "Unable to obtain reset token for test login"
    return 1
  fi

  curl -sS -b "$COOKIE" -c "$COOKIE" "$APP_URL/index.php?route=auth/resetPassword&token=$token" -o "$TMP_DIR/reset_get.html"
  csrf2="$(extract_csrf "$TMP_DIR/reset_get.html")"

  curl -sS -b "$COOKIE" -c "$COOKIE" -o /dev/null \
    -X POST "$APP_URL/index.php?route=auth/resetPassword" \
    --data-urlencode "token=$token" \
    --data-urlencode "new_password=$TEMP_PASSWORD" \
    --data-urlencode "confirm_password=$TEMP_PASSWORD" \
    --data-urlencode "_csrf=$csrf2"

  ADMIN_PASSWORD="$TEMP_PASSWORD"
  login
}

smoke_routes() {
  log "Probing all public controller actions"
  : > "$ROUTE_LOG"

  while IFS= read -r file; do
    local cname
    cname="$(basename "$file" .php | sed 's/Controller$//' | tr '[:upper:]' '[:lower:]')"

    while IFS= read -r action; do
      local route="$cname/$action"
      local body="$TMP_DIR/body_${cname}_${action}.html"
      local code

      code="$(curl -sS -b "$COOKIE" -o "$body" -w '%{http_code}' "$APP_URL/index.php?route=$route")"
      printf '%s|%s\n' "$route" "$code" >> "$ROUTE_LOG"

      if [[ "$code" -ge 500 ]]; then
        record_defect "Route $route returned HTTP $code"
      elif rg -q 'Fatal error|Uncaught|Parse error' "$body"; then
        record_defect "Route $route rendered runtime fatal text (HTTP $code)"
      fi
    done < <(rg -n 'public function [a-zA-Z0-9_]+\(' "$ROOT_DIR/$file" | sed -E 's/.*public function ([a-zA-Z0-9_]+)\(.*/\1/')

  done < <(cd "$ROOT_DIR" && rg --files controllers)
}

security_assertions() {
  log "Running security assertions"

  curl -sS -c "$TMP_DIR/csrf_cookie.txt" -D "$TMP_DIR/no_csrf_headers.txt" -o /dev/null \
    -X POST "$APP_URL/index.php?route=auth/login" \
    --data-urlencode "identifier=$ADMIN_USER" \
    --data-urlencode "password=wrong"

  local code_no_csrf
  code_no_csrf="$(status_code "$TMP_DIR/no_csrf_headers.txt")"
  if [[ "$code_no_csrf" != "403" ]]; then
    record_defect "CSRF failure expected HTTP 403 but got HTTP $code_no_csrf"
  fi

  curl -sS -b "$COOKIE" -D "$TMP_DIR/logout_get_headers.txt" -o /dev/null \
    "$APP_URL/index.php?route=auth/logout"

  local code_logout_get
  code_logout_get="$(status_code "$TMP_DIR/logout_get_headers.txt")"
  if [[ "$code_logout_get" != "405" ]]; then
    record_defect "GET auth/logout expected HTTP 405 but got HTTP $code_logout_get"
  fi
}

main() {
  start_server
  lint_php
  db_check

  if ! login; then
    reset_password_and_login || true
  fi

  if ! login; then
    record_defect "Unable to authenticate test user"
  fi

  smoke_routes
  security_assertions

  log ""
  log "Route status snapshot:"
  sed -n '1,120p' "$ROUTE_LOG"

  log ""
  if [[ -s "$DEFECTS" ]]; then
    log "Defects found:"
    cat "$DEFECTS"
    exit 1
  else
    log "No defects found in current smoke scope."
  fi
}

main "$@"
