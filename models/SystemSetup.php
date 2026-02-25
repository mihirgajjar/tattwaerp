<?php

class SystemSetup
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function ensure(): void
    {
        $this->ensureUserAccessTables();
        $this->ensureMasterTables();
        $this->ensureBusinessColumns();
        $this->seedDefaults();
    }

    private function ensureUserAccessTables(): void
    {
        $this->run('CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(60) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(80) NOT NULL UNIQUE,
            label VARCHAR(120) NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            can_read TINYINT(1) NOT NULL DEFAULT 1,
            can_write TINYINT(1) NOT NULL DEFAULT 0,
            can_delete TINYINT(1) NOT NULL DEFAULT 0,
            UNIQUE KEY uq_role_perm (role_id, permission_id)
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS login_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            username VARCHAR(100) NOT NULL,
            ip_address VARCHAR(80) NOT NULL,
            success TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->ensureColumn('users', 'email', 'VARCHAR(120) NULL');
        $this->ensureColumn('users', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');
        $this->ensureColumn('users', 'role_id', 'INT NULL');
        $this->ensureColumn('users', 'must_change_password', 'TINYINT(1) NOT NULL DEFAULT 0');
        $this->ensureColumn('users', 'first_login_at', 'DATETIME NULL');
        $this->ensureColumn('users', 'created_at', 'DATETIME NULL');
    }

    private function ensureMasterTables(): void
    {
        $this->run('CREATE TABLE IF NOT EXISTS product_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS product_subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(30) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS payment_modes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(40) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS expense_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS tax_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            gst_rate DECIMAL(5,2) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS bank_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bank_name VARCHAR(120) NOT NULL,
            account_name VARCHAR(120) NOT NULL,
            account_no VARCHAR(80) NOT NULL,
            ifsc VARCHAR(40) NOT NULL,
            upi_id VARCHAR(120) NULL,
            qr_image_path VARCHAR(255) NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');

        $this->run('CREATE TABLE IF NOT EXISTS customer_payables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_id INT NOT NULL,
            purchase_id INT NULL,
            amount DECIMAL(12,2) NOT NULL,
            payment_mode VARCHAR(40) NOT NULL,
            payment_date DATE NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');
    }

    private function ensureBusinessColumns(): void
    {
        $this->ensureProductsCategoryIsFlexible();
        $this->ensureColumn('products', 'image_path', 'VARCHAR(255) NULL');
        $this->ensureColumn('products', 'barcode', 'VARCHAR(100) NULL');
        $this->ensureColumn('products', 'reserved_stock', 'INT NOT NULL DEFAULT 0');
        $this->ensureColumn('products', 'min_stock_level', 'INT NOT NULL DEFAULT 0');
        $this->ensureColumn('products', 'category_id', 'INT NULL');
        $this->ensureColumn('products', 'subcategory_id', 'INT NULL');
        $this->ensureColumn('products', 'brand_id', 'INT NULL');
        $this->ensureColumn('products', 'unit_id', 'INT NULL');
        $this->ensureColumn('products', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');

        $this->ensureColumn('customers', 'customer_type', "VARCHAR(40) NULL");
        $this->ensureColumn('customers', 'area_region', "VARCHAR(100) NULL");
        $this->ensureColumn('customers', 'payment_terms', "VARCHAR(80) NULL");
        $this->ensureColumn('customers', 'credit_limit', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('customers', 'pan_no', 'VARCHAR(20) NULL');
        $this->ensureColumn('customers', 'shipping_address', 'TEXT NULL');
        $this->ensureColumn('customers', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');

        $this->ensureColumn('suppliers', 'supplier_type', "VARCHAR(40) NULL");
        $this->ensureColumn('suppliers', 'bank_details', 'TEXT NULL');
        $this->ensureColumn('suppliers', 'payment_terms', "VARCHAR(80) NULL");
        $this->ensureColumn('suppliers', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');

        $this->ensureColumn('sales', 'status', "VARCHAR(20) NOT NULL DEFAULT 'FINAL'");
        $this->ensureColumn('sales', 'is_locked', 'TINYINT(1) NOT NULL DEFAULT 1');
        $this->ensureColumn('sales', 'item_discount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('sales', 'overall_discount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('sales', 'round_off', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('sales', 'notes', 'TEXT NULL');
        $this->ensureColumn('sales', 'terms', 'TEXT NULL');
        $this->ensureColumn('sales', 'payment_status', "VARCHAR(20) NOT NULL DEFAULT 'UNPAID'");
        $this->ensureColumn('sales', 'approved_by', 'VARCHAR(80) NULL');

        $this->ensureColumn('purchases', 'status', "VARCHAR(20) NOT NULL DEFAULT 'FINAL'");
        $this->ensureColumn('purchases', 'transport_cost', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('purchases', 'other_charges', 'DECIMAL(12,2) NOT NULL DEFAULT 0');

        $this->ensureColumn('sale_items', 'discount_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');

        $this->run('CREATE TABLE IF NOT EXISTS deleted_records_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(80) NOT NULL,
            record_id INT NOT NULL,
            deleted_by INT NOT NULL,
            payload TEXT,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');
    }

    private function ensureProductsCategoryIsFlexible(): void
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM products LIKE 'category'");
        $col = $stmt ? $stmt->fetch() : null;
        if (!$col) {
            return;
        }

        $type = strtolower((string)($col['Type'] ?? ''));
        if (str_starts_with($type, 'enum(')) {
            $this->run('ALTER TABLE products MODIFY category VARCHAR(100) NOT NULL');
        }
    }

    private function seedDefaults(): void
    {
        $roleCount = (int)$this->db->query('SELECT COUNT(*) c FROM roles')->fetch()['c'];
        if ($roleCount === 0) {
            $this->run("INSERT INTO roles (name, created_at) VALUES
                ('Admin', NOW()), ('Manager', NOW()), ('Sales', NOW()), ('Accountant', NOW()), ('Store', NOW()), ('Viewer', NOW())");
        }

        $permCount = (int)$this->db->query('SELECT COUNT(*) c FROM permissions')->fetch()['c'];
        if ($permCount === 0) {
            $this->run("INSERT INTO permissions (code, label) VALUES
                ('billing', 'Billing Access'),
                ('inventory', 'Inventory Access'),
                ('reports', 'Reports Access'),
                ('master_edit', 'Master Data Edit'),
                ('user_manage', 'User Management'),
                ('invoice_delete', 'Delete Invoice'),
                ('invoice_edit_after_approval', 'Edit Approved Invoice'),
                ('financial_report_view', 'View Financial Reports')");
        }

        $adminRole = $this->db->query("SELECT id FROM roles WHERE name='Admin' LIMIT 1")->fetch();
        if ($adminRole) {
            $rid = (int)$adminRole['id'];
            $this->run("INSERT INTO role_permissions (role_id, permission_id, can_read, can_write, can_delete)
                SELECT {$rid}, id, 1, 1, 1 FROM permissions
                ON DUPLICATE KEY UPDATE can_read=1, can_write=1, can_delete=1");

            $this->run("UPDATE users SET role_id = {$rid}, email = COALESCE(email, CONCAT(username, '@local.test')), is_active = 1 WHERE role_id IS NULL");
        }

        $this->run("INSERT INTO payment_modes (name, is_active, created_at)
            SELECT 'Cash', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM payment_modes WHERE name='Cash')");
        $this->run("INSERT INTO payment_modes (name, is_active, created_at)
            SELECT 'UPI', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM payment_modes WHERE name='UPI')");
        $this->run("INSERT INTO payment_modes (name, is_active, created_at)
            SELECT 'Bank Transfer', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM payment_modes WHERE name='Bank Transfer')");

        $this->run("INSERT INTO units (name, is_active, created_at)
            SELECT 'ml', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM units WHERE name='ml')");
        $this->run("INSERT INTO units (name, is_active, created_at)
            SELECT 'liter', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM units WHERE name='liter')");
        $this->run("INSERT INTO units (name, is_active, created_at)
            SELECT 'piece', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM units WHERE name='piece')");

        $this->run("INSERT INTO product_categories (name, is_active, created_at)
            SELECT 'Single Oil', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE name='Single Oil')");
        $this->run("INSERT INTO product_categories (name, is_active, created_at)
            SELECT 'Blend', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE name='Blend')");
        $this->run("INSERT INTO product_categories (name, is_active, created_at)
            SELECT 'Diffuser Oil', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE name='Diffuser Oil')");

        $this->run("INSERT INTO tax_settings (name, gst_rate, is_active, created_at)
            SELECT 'GST 5%', 5, 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tax_settings WHERE name='GST 5%')");
        $this->run("INSERT INTO tax_settings (name, gst_rate, is_active, created_at)
            SELECT 'GST 12%', 12, 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tax_settings WHERE name='GST 12%')");
        $this->run("INSERT INTO tax_settings (name, gst_rate, is_active, created_at)
            SELECT 'GST 18%', 18, 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tax_settings WHERE name='GST 18%')");
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $stmt = $this->db->prepare("SHOW COLUMNS FROM {$table} LIKE :col");
        $stmt->execute(['col' => $column]);
        if (!$stmt->fetch()) {
            $this->run("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function run(string $sql): void
    {
        $this->db->exec($sql);
    }
}
