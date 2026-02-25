#!/bin/zsh
set -euo pipefail
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

cleanup() {
  if [[ -n "${SERVER_PID:-}" ]]; then
    kill "$SERVER_PID" >/dev/null 2>&1 || true
  fi
  echo "Artifacts: $TMP_DIR"
}
trap cleanup EXIT

log() { printf '%s\n' "$*"; }
fail() { printf '%s\n' "$*" | tee -a "$DEFECTS" >/dev/null; }

db_value() {
  local sql="$1"
  (
    cd "$ROOT_DIR"
    "$PHP_BIN" -r '$cfg=require "config/database.php"; $pdo=new PDO("mysql:host={$cfg["host"]};port={$cfg["port"]};dbname={$cfg["dbname"]};charset={$cfg["charset"]}",$cfg["username"],$cfg["password"]); $v=$pdo->query($argv[1])->fetchColumn(); echo ($v===false?"":$v);' "$sql"
  )
}

extract_csrf() {
  local file="$1"
  rg -o 'name="_csrf" value="[^"]+"' "$file" | head -n1 | sed -E 's/.*value="([^"]+)"/\1/' || true
}

fetch_csrf_for_route() {
  local route="$1"
  local out="$TMP_DIR/csrf_$(echo "$route" | tr '/&=?' '_').html"
  curl -sS -b "$COOKIE" -c "$COOKIE" "$APP_URL/index.php?route=$route" -o "$out"
  extract_csrf "$out"
}

status_code() {
  sed -n '1s/.* \([0-9][0-9][0-9]\) .*/\1/p' "$1"
}

start_server() {
  (
    cd "$ROOT_DIR"
    "$PHP_BIN" -S 127.0.0.1:8099 index.php
  ) >"$TMP_DIR/server.log" 2>&1 &
  SERVER_PID=$!

  local i=0
  until curl -sS "$APP_URL/index.php?route=auth/login" >/dev/null 2>&1; do
    i=$((i+1))
    [[ $i -gt 40 ]] && fail "Could not start local PHP server" && return 1
    sleep 0.25
  done
}

login() {
  curl -sS -c "$COOKIE" "$APP_URL/index.php?route=auth/login" -o "$TMP_DIR/login.html"
  local csrf
  csrf="$(extract_csrf "$TMP_DIR/login.html")"

  curl -sS -b "$COOKIE" -c "$COOKIE" -D "$TMP_DIR/login_headers.txt" -o /dev/null \
    -X POST "$APP_URL/index.php?route=auth/login" \
    --data-urlencode "identifier=$ADMIN_USER" \
    --data-urlencode "password=$ADMIN_PASSWORD" \
    --data-urlencode "_csrf=$csrf"

  rg -q 'Location: index.php\?route=dashboard/index' "$TMP_DIR/login_headers.txt"
}

reset_password_and_login() {
  curl -sS -b "$COOKIE" -c "$COOKIE" "$APP_URL/index.php?route=auth/forgotPassword" -o "$TMP_DIR/forgot.html"
  local csrf token csrf2
  csrf="$(extract_csrf "$TMP_DIR/forgot.html")"

  curl -sS -b "$COOKIE" -c "$COOKIE" -o /dev/null \
    -X POST "$APP_URL/index.php?route=auth/forgotPassword" \
    --data-urlencode "identifier=$ADMIN_USER" \
    --data-urlencode "_csrf=$csrf"

  curl -sS -b "$COOKIE" -c "$COOKIE" "$APP_URL/index.php?route=auth/forgotPassword" -o "$TMP_DIR/forgot_after.html"
  token="$(rg -o 'Reset token generated: [^.]+' "$TMP_DIR/forgot_after.html" | head -n1 | sed -E 's/Reset token generated: //')"
  [[ -z "$token" ]] && fail "Cannot extract reset token" && return 1

  curl -sS -b "$COOKIE" -c "$COOKIE" "$APP_URL/index.php?route=auth/resetPassword&token=$token" -o "$TMP_DIR/reset.html"
  csrf2="$(extract_csrf "$TMP_DIR/reset.html")"

  curl -sS -b "$COOKIE" -c "$COOKIE" -o /dev/null \
    -X POST "$APP_URL/index.php?route=auth/resetPassword" \
    --data-urlencode "token=$token" \
    --data-urlencode "new_password=$TEMP_PASSWORD" \
    --data-urlencode "confirm_password=$TEMP_PASSWORD" \
    --data-urlencode "_csrf=$csrf2"

  ADMIN_PASSWORD="$TEMP_PASSWORD"
  login
}

post_form() {
  local route="$1"
  shift
  local source_route
  source_route="$(csrf_source_route "$route")"
  local csrf
  csrf="$(fetch_csrf_for_route "$source_route")"
  if [[ -z "$csrf" ]]; then
    fail "CSRF token not found for route '$route' via source '$source_route'"
    return 1
  fi
  local hdr="$TMP_DIR/h_$(echo "$route" | tr '/&=?' '_').txt"
  curl -sS -b "$COOKIE" -c "$COOKIE" -D "$hdr" -o /dev/null -X POST "$APP_URL/index.php?route=$route" \
    --data-urlencode "_csrf=$csrf" "$@"
  echo "$hdr"
}

csrf_source_route() {
  local route="$1"
  case "$route" in
    master/*) echo "master/index" ;;
    supplier/*) echo "supplier/index" ;;
    customer/*) echo "customer/index" ;;
    product/edit*) echo "$route" ;;
    product/*) echo "product/create" ;;
    purchase/create*|purchase/edit*|purchase/import*|purchase/finalizeImport*) echo "purchase/create" ;;
    purchase/*) echo "purchase/index" ;;
    sale/create*) echo "sale/create" ;;
    sale/*) echo "sale/index" ;;
    finance/*) echo "finance/index" ;;
    user/*) echo "user/index" ;;
    setting/*) echo "setting/index" ;;
    smart/*) echo "smart/index" ;;
    auth/*) echo "auth/login" ;;
    *) echo "dashboard/index" ;;
  esac
}

assert_location_contains() {
  local header_file="$1"
  local expected="$2"
  if ! rg -q "Location: .*${expected}" "$header_file"; then
    fail "Expected redirect containing '$expected' but got: $(sed -n '1,20p' "$header_file" | tr '\n' ' ')"
  fi
}

main() {
  log "Starting functional feature tests"
  start_server

  if ! login; then
    log "Default login failed; attempting reset flow"
    reset_password_and_login || true
  fi
  if ! login; then
    fail "Unable to authenticate"
  fi

  local suffix="$(date +%s)"
  local category="AUTO_CAT_${suffix}"
  local supplier="AUTO_SUP_${suffix}"
  local customer="AUTO_CUS_${suffix}"
  local product="AUTO_PROD_${suffix}"
  local sku="AUTO-SKU-${suffix}"
  local invoice="AUTOINV-${suffix}"
  local purinv="AUTOPUR-${suffix}"
  local bank="AUTO_BANK_${suffix}"
  local username="autouser${suffix}"
  local email="autouser${suffix}@local.test"

  log "Functional: master create"
  local h
  h="$(post_form 'master/save' --data-urlencode 'table=product_categories' --data-urlencode "name=$category" --data-urlencode 'is_active=1')"
  assert_location_contains "$h" 'master/index'
  local cat_id
  cat_id="$(db_value "SELECT id FROM product_categories WHERE name='${category}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$cat_id" ]] && fail "Master category create failed"

  log "Functional: supplier create + dashboard"
  h="$(post_form 'supplier/create' --data-urlencode "name=$supplier" --data-urlencode 'gstin=27ABCDE1234F1Z5' --data-urlencode 'state=Maharashtra' --data-urlencode 'phone=9000000001' --data-urlencode 'address=Test Supplier Address')"
  assert_location_contains "$h" 'supplier/index'
  local supplier_id
  supplier_id="$(db_value "SELECT id FROM suppliers WHERE name='${supplier}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$supplier_id" ]] && fail "Supplier create failed"
  curl -sS -b "$COOKIE" "$APP_URL/index.php?route=supplier/dashboard&id=$supplier_id" -o "$TMP_DIR/supplier_dash.html"
  rg -q "$supplier" "$TMP_DIR/supplier_dash.html" || fail "Supplier dashboard did not load expected supplier"

  log "Functional: customer create + dashboard"
  h="$(post_form 'customer/create' --data-urlencode "name=$customer" --data-urlencode 'gstin=27ABCDE1234F1Z6' --data-urlencode 'state=Maharashtra' --data-urlencode 'phone=9000000002' --data-urlencode 'address=Test Customer Address')"
  assert_location_contains "$h" 'customer/index'
  local customer_id
  customer_id="$(db_value "SELECT id FROM customers WHERE name='${customer}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$customer_id" ]] && fail "Customer create failed"
  curl -sS -b "$COOKIE" "$APP_URL/index.php?route=customer/dashboard&id=$customer_id" -o "$TMP_DIR/customer_dash.html"
  rg -q "$customer" "$TMP_DIR/customer_dash.html" || fail "Customer dashboard did not load expected customer"

  log "Functional: product create + edit"
  h="$(post_form 'product/create' --data-urlencode "product_name=$product" --data-urlencode "sku=$sku" --data-urlencode "category=$category" --data-urlencode 'variant=Standard' --data-urlencode 'size=100ml' --data-urlencode 'hsn_code=3301' --data-urlencode 'gst_percent=18' --data-urlencode 'purchase_price=100' --data-urlencode 'selling_price=150' --data-urlencode 'stock_quantity=20' --data-urlencode 'reorder_level=5' --data-urlencode 'is_active=1')"
  assert_location_contains "$h" 'product/index'
  local product_id
  product_id="$(db_value "SELECT id FROM products WHERE sku='${sku}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$product_id" ]] && fail "Product create failed"
  h="$(post_form "product/edit&id=$product_id" --data-urlencode "product_name=${product}_UPD" --data-urlencode "sku=$sku" --data-urlencode "category=$category" --data-urlencode 'variant=Premium' --data-urlencode 'size=100ml' --data-urlencode 'hsn_code=3301' --data-urlencode 'gst_percent=18' --data-urlencode 'purchase_price=110' --data-urlencode 'selling_price=170' --data-urlencode 'stock_quantity=25' --data-urlencode 'reorder_level=6' --data-urlencode 'is_active=1')"
  assert_location_contains "$h" 'product/index'

  log "Functional: purchase create + finalize status"
  h="$(post_form 'purchase/create' --data-urlencode "supplier_id=$supplier_id" --data-urlencode 'party_state=Maharashtra' --data-urlencode 'date=2026-02-25' --data-urlencode "purchase_invoice_no=$purinv" --data-urlencode 'status=DRAFT' --data-urlencode 'transport_cost=10' --data-urlencode 'other_charges=5' --data-urlencode "product_id[]=$product_id" --data-urlencode 'quantity[]=10' --data-urlencode 'rate[]=120' --data-urlencode 'gst_percent[]=18')"
  assert_location_contains "$h" 'purchase/index'
  local purchase_id
  purchase_id="$(db_value "SELECT id FROM purchases WHERE purchase_invoice_no='${purinv}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$purchase_id" ]] && fail "Purchase create failed"
  h="$(post_form 'purchase/status' --data-urlencode "id=$purchase_id" --data-urlencode 'status=FINAL')"
  assert_location_contains "$h" 'purchase/index'

  log "Functional: sale create + invoice + status"
  h="$(post_form 'sale/create' --data-urlencode "customer_id=$customer_id" --data-urlencode 'customer_state=Maharashtra' --data-urlencode 'date=2026-02-25' --data-urlencode 'due_date=2026-03-10' --data-urlencode "invoice_no=$invoice" --data-urlencode 'status=FINAL' --data-urlencode 'item_discount=0' --data-urlencode 'overall_discount=0' --data-urlencode 'round_off=0' --data-urlencode "product_id[]=$product_id" --data-urlencode 'quantity[]=2' --data-urlencode 'rate[]=200' --data-urlencode 'gst_percent[]=18')"
  assert_location_contains "$h" 'sale/invoice&id='
  local sale_id
  sale_id="$(db_value "SELECT id FROM sales WHERE invoice_no='${invoice}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$sale_id" ]] && fail "Sale create failed"
  curl -sS -b "$COOKIE" "$APP_URL/index.php?route=sale/invoice&id=$sale_id" -o "$TMP_DIR/invoice.html"
  rg -q "$invoice" "$TMP_DIR/invoice.html" || fail "Invoice page missing invoice number"
  h="$(post_form 'sale/status' --data-urlencode "id=$sale_id" --data-urlencode 'status=CANCELLED')"
  assert_location_contains "$h" 'sale/index'

  log "Functional: finance (bank/receive/pay)"
  h="$(post_form 'finance/addBank' --data-urlencode "bank_name=$bank" --data-urlencode 'account_name=Auto Test' --data-urlencode "account_no=123456${suffix}" --data-urlencode 'ifsc=SBIN0001234' --data-urlencode 'upi_id=autotest@upi' --data-urlencode 'is_default=1')"
  assert_location_contains "$h" 'finance/index'
  h="$(post_form 'finance/receive' --data-urlencode "sale_id=$sale_id" --data-urlencode 'amount=50' --data-urlencode 'payment_mode=UPI')"
  assert_location_contains "$h" 'finance/index'
  h="$(post_form 'finance/pay' --data-urlencode "supplier_id=$supplier_id" --data-urlencode "purchase_id=$purchase_id" --data-urlencode 'amount=100' --data-urlencode 'payment_mode=Bank Transfer')"
  assert_location_contains "$h" 'finance/index'

  log "Functional: reports exports"
  curl -sS -b "$COOKIE" "$APP_URL/index.php?route=report/export&type=sales&from=2026-02-01&to=2026-02-29" -o "$TMP_DIR/report_sales.csv"
  rg -q 'Invoice No' "$TMP_DIR/report_sales.csv" || fail "Sales export missing header"
  curl -sS -b "$COOKIE" "$APP_URL/index.php?route=report/export&type=profit&from=2026-02-01&to=2026-02-29" -o "$TMP_DIR/report_profit.csv"
  rg -q 'Profit' "$TMP_DIR/report_profit.csv" || fail "Profit export missing header"

  log "Functional: smart ops actions"
  h="$(post_form 'smart/index' --data-urlencode 'action=add_warehouse' --data-urlencode "name=AUTO_WH_A_${suffix}" --data-urlencode 'state=Maharashtra')"
  assert_location_contains "$h" 'smart/index'
  h="$(post_form 'smart/index' --data-urlencode 'action=add_warehouse' --data-urlencode "name=AUTO_WH_B_${suffix}" --data-urlencode 'state=Maharashtra')"
  assert_location_contains "$h" 'smart/index'

  local wh1 wh2
  wh1="$(db_value "SELECT id FROM warehouses WHERE name='AUTO_WH_A_${suffix}' ORDER BY id DESC LIMIT 1")"
  wh2="$(db_value "SELECT id FROM warehouses WHERE name='AUTO_WH_B_${suffix}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$wh1" || -z "$wh2" ]] && fail "Warehouse creation failed"

  h="$(post_form 'smart/index' --data-urlencode 'action=add_batch' --data-urlencode "product_id=$product_id" --data-urlencode "warehouse_id=$wh1" --data-urlencode "batch_no=AUTO-BATCH-${suffix}" --data-urlencode 'mfg_date=2026-01-01' --data-urlencode 'expiry_date=2026-12-31' --data-urlencode 'quantity=8')"
  assert_location_contains "$h" 'smart/index'

  h="$(post_form 'smart/index' --data-urlencode 'action=transfer_stock' --data-urlencode "product_id=$product_id" --data-urlencode "from_warehouse_id=$wh1" --data-urlencode "to_warehouse_id=$wh2" --data-urlencode 'quantity=3')"
  assert_location_contains "$h" 'smart/index'

  h="$(post_form 'smart/index' --data-urlencode 'action=save_supplier_rate' --data-urlencode "product_id=$product_id" --data-urlencode "supplier_id=$supplier_id" --data-urlencode 'rate=118')"
  assert_location_contains "$h" 'smart/index'

  h="$(post_form 'smart/index' --data-urlencode 'action=create_price_list' --data-urlencode "name=AUTO_PL_${suffix}" --data-urlencode 'channel=Retail')"
  assert_location_contains "$h" 'smart/index'
  local plist
  plist="$(db_value "SELECT id FROM price_lists WHERE name='AUTO_PL_${suffix}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$plist" ]] && fail "Price list not created"

  h="$(post_form 'smart/index' --data-urlencode 'action=add_price_item' --data-urlencode "price_list_id=$plist" --data-urlencode "product_id=$product_id" --data-urlencode 'price=199')"
  assert_location_contains "$h" 'smart/index'

  h="$(post_form 'smart/index' --data-urlencode 'action=create_approval' --data-urlencode 'approval_type=DISCOUNT' --data-urlencode "reference_no=AUTO-APP-${suffix}" --data-urlencode 'notes=auto test approval')"
  assert_location_contains "$h" 'smart/index'
  local appr
  appr="$(db_value "SELECT id FROM approvals WHERE reference_no='AUTO-APP-${suffix}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$appr" ]] && fail "Approval not created"

  h="$(post_form 'smart/index' --data-urlencode 'action=review_approval' --data-urlencode "approval_id=$appr" --data-urlencode 'status=APPROVED')"
  assert_location_contains "$h" 'smart/index'

  h="$(post_form 'smart/index' --data-urlencode 'action=run_notifications')"
  assert_location_contains "$h" 'smart/index'

  h="$(post_form 'smart/index' --data-urlencode 'action=create_return' --data-urlencode "sale_id=$sale_id" --data-urlencode "product_id=$product_id" --data-urlencode 'quantity=1' --data-urlencode 'reason=Functional test return')"
  assert_location_contains "$h" 'smart/index'

  curl -sS -b "$COOKIE" "$APP_URL/index.php?route=smart/complianceCsv&month=2026-02" -o "$TMP_DIR/smart_compliance.csv"
  rg -q 'HSN' "$TMP_DIR/smart_compliance.csv" || fail "Smart compliance CSV header missing"
  curl -sS -b "$COOKIE" "$APP_URL/index.php?route=smart/einvoice&sale_id=$sale_id" -o "$TMP_DIR/smart_einvoice.json"
  rg -q '"DocDtls"' "$TMP_DIR/smart_einvoice.json" || fail "Smart e-invoice JSON missing DocDtls"
  curl -sS -b "$COOKIE" "$APP_URL/index.php?route=smart/barcode&product_id=$product_id" -o "$TMP_DIR/smart_barcode.txt"
  rg -q 'BARCODE LABEL' "$TMP_DIR/smart_barcode.txt" || fail "Smart barcode output missing label"

  log "Functional: user management"
  h="$(post_form 'user/create' --data-urlencode "username=$username" --data-urlencode "email=$email" --data-urlencode 'password=Pass@1234' --data-urlencode 'role_id=1' --data-urlencode 'must_change_password=1')"
  assert_location_contains "$h" 'user/index'
  local user_id
  user_id="$(db_value "SELECT id FROM users WHERE username='${username}' ORDER BY id DESC LIMIT 1")"
  [[ -z "$user_id" ]] && fail "User create failed"

  h="$(post_form 'user/update' --data-urlencode "id=$user_id" --data-urlencode "username=${username}_u" --data-urlencode "email=$email" --data-urlencode 'role_id=1' --data-urlencode 'is_active=1' --data-urlencode 'must_change_password=0')"
  assert_location_contains "$h" 'user/index'
  h="$(post_form 'user/toggleActive' --data-urlencode "id=$user_id" --data-urlencode 'active=0')"
  assert_location_contains "$h" 'user/index'
  h="$(post_form 'user/resetPassword' --data-urlencode "id=$user_id" --data-urlencode 'new_password=Reset@1234')"
  assert_location_contains "$h" 'user/index'

  log "Functional: settings theme"
  h="$(post_form 'setting/index' --data-urlencode 'action=save_invoice_theme' --data-urlencode 'invoice_theme=minimal' --data-urlencode 'invoice_accent_color=#0A7B5F')"
  assert_location_contains "$h" 'setting/index'
  local theme
  theme="$(db_value "SELECT setting_value FROM app_settings WHERE setting_key='invoice_theme' LIMIT 1")"
  [[ "$theme" != "minimal" ]] && fail "Invoice theme setting not saved"

  log "Functional verification checks"
  local pp ps payment_status
  pp="$(db_value "SELECT COUNT(*) FROM customer_payables WHERE purchase_id=${purchase_id}")"
  [[ -z "$pp" || "$pp" = "0" ]] && fail "Finance pay did not persist customer payable"
  ps="$(db_value "SELECT status FROM purchases WHERE id=${purchase_id}")"
  [[ "$ps" != "FINAL" ]] && fail "Purchase status not FINAL after status action"
  payment_status="$(db_value "SELECT payment_status FROM sales WHERE id=${sale_id}")"
  [[ -z "$payment_status" ]] && fail "Sale payment status missing"

  if [[ -s "$DEFECTS" ]]; then
    log ""
    log "Functional defects found:"
    cat "$DEFECTS"
    exit 1
  fi

  log ""
  log "All functional feature tests passed in current automated scope."
}

main "$@"
