# Full Product Testing Plan

## Objective
Validate the full billing product for runtime stability, access control, and route-level integrity, then surface actionable defects.

## Scope
- All PHP files syntax validation.
- Authentication flow required for protected modules.
- All public controller actions discovered from `controllers/*.php`.
- Security behavior checks for CSRF and HTTP method enforcement.

## Test Strategy
1. Environment readiness
- Verify PHP CLI availability.
- Verify DB connectivity using `config/database.php`.

2. Static baseline
- Run `php -l` on all PHP files to catch parse errors early.

3. Auth bootstrap
- Open login page and extract CSRF token.
- Attempt login.
- If default credentials fail, execute forgot/reset flow to set a temporary test password and login.

4. Product-wide route smoke
- Discover all public controller methods.
- Hit each route as authenticated user.
- Flag defects for:
  - HTTP 5xx
  - Fatal runtime text markers (`Fatal error`, `Uncaught`, `Parse error`)

5. Security assertions
- POST without CSRF token should return HTTP 403.
- GET against POST-only endpoint should return HTTP 405.

6. Reporting
- Save machine-readable logs and a concise defect summary.

## Exit Criteria
- All routes execute without 5xx/fatal markers.
- Security assertions pass.
- Any failures are recorded with route, actual status, and expected behavior.
