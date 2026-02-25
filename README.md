# Invoice + GST + Inventory System (India - Essential Oils)

Lightweight web-based Invoice, Sales, Purchase and Inventory Management System using PHP (MVC), MySQL, HTML/CSS/JS.

## Tech Stack
- Frontend: HTML5, CSS3, Vanilla JavaScript
- Backend: PHP 8+ (XAMPP compatible)
- Database: MySQL
- Architecture: Simple MVC (`config`, `controllers`, `models`, `views`, `assets`)

## Features
- Admin authentication with hashed password and session login/logout
- Dashboard with monthly totals, profit, low stock, and monthly sales chart
- Product management with GST rates (5/12/18), HSN, stock, reorder level
- Supplier and customer master data
- Purchase module with GST auto-calc and stock increment
- Sales module with GST auto-calc, stock decrement, auto invoice number (`INV-0001`), print/PDF invoice
- Inventory module with live stock, low stock alerts, valuation, product sales summary
- Reports: sales, purchase, GST monthly summary, profit report
- Supplier/customer edit and delete
- Invoice logo upload from Settings (stored in DB-backed app settings)
- CSV export for sales, purchases, GST, and profit reports
- Smart reorder suggestions based on sales velocity, lead time, MOQ
- Demand forecast (30/60/90 days)
- Margin intelligence report per sale line
- Batch/expiry tracking with FEFO visibility
- Credit control with receivable ageing and payment posting
- Supplier rate snapshots + purchase optimization view
- Multi-price lists (retail/wholesale/distributor)
- Multi-warehouse stock and warehouse transfer workflow
- Returns and credit note flow with stock reversal
- Approval workflow (pending/approved/rejected)
- Audit trail for critical actions
- Daily summary notification queue generator
- GST compliance HSN export and e-invoice JSON draft output
- Barcode/QR payload generator per SKU
- User management with roles, permissions, activation, reset password, login history
- Authentication improvements: login by email/username, forgot/reset password, force first-login password change, complexity rules
- Centralized master data module (categories/sub-categories/brands/units/payment modes/expense categories/tax/warehouses)
- Enhanced customer master (type, region, terms, credit limit, PAN, billing/shipping, active/inactive)
- Enhanced supplier master (type, terms, bank details, active/inactive)
- Finance module: payment received/made, partial payment support, bank account + UPI/QR setup
- Sales enhancements: draft/final/cancel status, lock after final, discounts, round-off, notes/terms, payment status
- Purchase enhancements: draft/final, transport/other charges
- Auto-generate purchase from uploaded CSV/XLSX and best-effort PDF/Image text extraction
- Customer/Supplier dashboard and ledger views
- Dashboard improvements: today sales, monthly revenue, receivables, payables, low stock, top products
- Delete vs deactivate rule support (draft invoice deletion restricted)

## Installation (XAMPP)
1. Place project inside web root:
   - `/Applications/XAMPP/xamppfiles/htdocs/billing`
2. Start Apache + MySQL in XAMPP control panel.
3. Create DB and tables:
   - Open phpMyAdmin, import `/Applications/XAMPP/xamppfiles/htdocs/billing/sql/schema.sql`
4. Update DB credentials if needed:
   - `/Applications/XAMPP/xamppfiles/htdocs/billing/config/database.php`
5. Open in browser:
   - `http://localhost/billing/index.php`

On first run, the app auto-creates/updates additional tables and columns using:
- `/Applications/XAMPP/xamppfiles/htdocs/billing/models/SystemSetup.php`

## Default Login
- Username: `admin`
- Password: `admin123`

## Business Configuration
Update business details used in invoice and GST state logic:
- `/Applications/XAMPP/xamppfiles/htdocs/billing/config/app.php`

Invoice logo management:
- Open `Settings` from sidebar and upload logo image.

Smart operations:
- Open `Smart Ops` from sidebar.
- Use the unified page to manage forecasting, reorder, returns, approvals, warehouse transfers, compliance exports, and e-invoice JSON.

Important GST rule in system:
- If business state equals party state: split GST into CGST and SGST.
- If different states: apply IGST.

## Folder Structure
- `/Applications/XAMPP/xamppfiles/htdocs/billing/config`
- `/Applications/XAMPP/xamppfiles/htdocs/billing/controllers`
- `/Applications/XAMPP/xamppfiles/htdocs/billing/models`
- `/Applications/XAMPP/xamppfiles/htdocs/billing/views`
- `/Applications/XAMPP/xamppfiles/htdocs/billing/assets`
- `/Applications/XAMPP/xamppfiles/htdocs/billing/sql`

## Notes
- Uses prepared statements through PDO.
- Uses transactions for purchase/sale with stock updates.
- PDF export is browser print-to-PDF from invoice screen.
- CSV exports are available in Reports screen.
- Additional compliance CSV is available in Smart Ops.

## Safe Live Upgrade (Non-Technical)
Use this process whenever new code is uploaded to live hosting.

1. Take backup first:
   - Export database from phpMyAdmin.
2. Upload new code files to server.
3. Login as admin.
4. Open Migration Center:
   - `index.php?route=migration/index`
5. Click `Run Pending Migrations`.
6. Check success message and verify key pages.

If your live DB already has the same structure (manual past changes):
1. Open Migration Center.
2. Click `Mark Baseline (No SQL Run)` once.
3. Future releases: use only `Run Pending Migrations`.

Developer/CLI options:
- `php migrate.php status` (see pending)
- `php migrate.php run` (apply pending)
- `php migrate.php baseline` (mark all as applied)
