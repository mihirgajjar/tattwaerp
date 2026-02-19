CREATE DATABASE IF NOT EXISTS billing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE billing_system;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS stock_transfers;
DROP TABLE IF EXISTS price_list_items;
DROP TABLE IF EXISTS price_lists;
DROP TABLE IF EXISTS supplier_price_history;
DROP TABLE IF EXISTS sales_return_items;
DROP TABLE IF EXISTS sales_returns;
DROP TABLE IF EXISTS customer_payments;
DROP TABLE IF EXISTS product_batches;
DROP TABLE IF EXISTS product_warehouse_stock;
DROP TABLE IF EXISTS warehouses;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS app_settings;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB;

CREATE TABLE app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(80) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(120) NOT NULL,
    sku VARCHAR(60) NOT NULL UNIQUE,
    category ENUM('Single Oil', 'Blend', 'Diffuser Oil') NOT NULL,
    variant VARCHAR(120) NOT NULL,
    size VARCHAR(20) NOT NULL,
    hsn_code VARCHAR(20) NOT NULL,
    gst_percent DECIMAL(5,2) NOT NULL,
    purchase_price DECIMAL(12,2) NOT NULL,
    selling_price DECIMAL(12,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 5,
    lead_time_days INT NOT NULL DEFAULT 7,
    moq INT NOT NULL DEFAULT 10,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    gstin VARCHAR(20),
    state VARCHAR(80) NOT NULL,
    phone VARCHAR(20),
    address TEXT
) ENGINE=InnoDB;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    gstin VARCHAR(20),
    state VARCHAR(80) NOT NULL,
    phone VARCHAR(20),
    address TEXT
) ENGINE=InnoDB;

CREATE TABLE warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    state VARCHAR(80) NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE product_warehouse_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_pws (product_id, warehouse_id),
    CONSTRAINT fk_pws_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pws_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    batch_no VARCHAR(80) NOT NULL,
    mfg_date DATE,
    expiry_date DATE,
    quantity INT NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_batch_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_batch_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_invoice_no VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    date DATE NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    cgst DECIMAL(12,2) NOT NULL DEFAULT 0,
    sgst DECIMAL(12,2) NOT NULL DEFAULT 0,
    igst DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    gst_percent DECIMAL(5,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_purchase_items_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    CONSTRAINT fk_purchase_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(12,2) NOT NULL,
    cgst DECIMAL(12,2) NOT NULL DEFAULT 0,
    sgst DECIMAL(12,2) NOT NULL DEFAULT 0,
    igst DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    gst_percent DECIMAL(5,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE customer_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_mode VARCHAR(40) NOT NULL,
    payment_date DATE NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_payment_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sales_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    credit_note_no VARCHAR(40) NOT NULL UNIQUE,
    sale_id INT NOT NULL,
    return_date DATE NOT NULL,
    reason VARCHAR(255) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_return_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE sales_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sales_return_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    gst_percent DECIMAL(5,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_return_items_return FOREIGN KEY (sales_return_id) REFERENCES sales_returns(id) ON DELETE CASCADE,
    CONSTRAINT fk_return_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE supplier_price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    supplier_id INT NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    recorded_on DATE NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_sph_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_sph_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE price_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    channel VARCHAR(40) NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE price_list_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    price_list_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    UNIQUE KEY uq_pli (price_list_id, product_id),
    CONSTRAINT fk_pli_list FOREIGN KEY (price_list_id) REFERENCES price_lists(id) ON DELETE CASCADE,
    CONSTRAINT fk_pli_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE stock_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    from_warehouse_id INT NOT NULL,
    to_warehouse_id INT NOT NULL,
    quantity INT NOT NULL,
    transfer_date DATE NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_transfer_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transfer_from FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transfer_to FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    approval_type VARCHAR(60) NOT NULL,
    reference_no VARCHAR(60) NOT NULL,
    notes TEXT,
    status VARCHAR(20) NOT NULL,
    reviewed_by VARCHAR(50),
    reviewed_at DATETIME,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 0,
    action VARCHAR(120) NOT NULL,
    entity VARCHAR(80) NOT NULL,
    entity_id INT NOT NULL DEFAULT 0,
    meta TEXT,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel VARCHAR(30) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

INSERT INTO users (username, password, role)
VALUES ('admin', '$2y$10$d2MpJn4ZdzQB25ei7WoUieYSrVmJ6dRM48OD2PzcXp47cWfC5M9JC', 'admin');

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('invoice_logo', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO products (product_name, sku, category, variant, size, hsn_code, gst_percent, purchase_price, selling_price, stock_quantity, reorder_level, lead_time_days, moq) VALUES
('Lavender Essential Oil', 'EO-LAV-10', 'Single Oil', 'Lavender', '10ml', '330129', 18, 180.00, 320.00, 80, 20, 7, 20),
('Peppermint Essential Oil', 'EO-PEP-10', 'Single Oil', 'Peppermint', '10ml', '330129', 18, 160.00, 290.00, 65, 15, 7, 20),
('Tea Tree Essential Oil', 'EO-TTR-30', 'Single Oil', 'Tea Tree', '30ml', '330129', 18, 320.00, 560.00, 40, 12, 10, 15),
('Sleep Calm Blend', 'BL-SLP-10', 'Blend', 'Lavender + Chamomile', '10ml', '330129', 12, 210.00, 390.00, 50, 15, 8, 15),
('Citrus Diffuser Oil', 'DF-CIT-100', 'Diffuser Oil', 'Sweet Orange', '100ml', '330129', 5, 350.00, 640.00, 30, 10, 12, 10);

INSERT INTO suppliers (name, gstin, state, phone, address) VALUES
('Aroma Farms Pvt Ltd', '27AAECA9090B1ZQ', 'Maharashtra', '9822221111', 'Nashik, Maharashtra'),
('Herbal Extracts India', '29AABCH2288D1Z2', 'Karnataka', '9845012345', 'Bengaluru, Karnataka');

INSERT INTO customers (name, gstin, state, phone, address) VALUES
('Nature Wellness Store', '27AAGFN1234L1Z7', 'Maharashtra', '9988776655', 'Pune, Maharashtra'),
('Zen Aroma Retail', '07AABCZ5678P1ZS', 'Delhi', '9990001122', 'Dwarka, New Delhi');

INSERT INTO warehouses (name, state, created_at) VALUES
('Main Warehouse', 'Maharashtra', NOW()),
('Delhi Hub', 'Delhi', NOW());

INSERT INTO product_warehouse_stock (product_id, warehouse_id, quantity)
SELECT id, 1, stock_quantity FROM products;

INSERT INTO purchases (purchase_invoice_no, supplier_id, date, subtotal, cgst, sgst, igst, total_amount) VALUES
('PUR-0001', 1, CURDATE(), 12000.00, 1080.00, 1080.00, 0.00, 14160.00),
('PUR-0002', 2, CURDATE(), 8000.00, 0.00, 0.00, 960.00, 8960.00);

INSERT INTO purchase_items (purchase_id, product_id, quantity, rate, gst_percent, tax_amount, total) VALUES
(1, 1, 20, 180.00, 18, 648.00, 4248.00),
(1, 2, 20, 160.00, 18, 576.00, 3776.00),
(1, 4, 20, 210.00, 12, 504.00, 4704.00),
(2, 3, 10, 320.00, 18, 576.00, 3776.00),
(2, 5, 10, 350.00, 5, 175.00, 3675.00);

INSERT INTO sales (invoice_no, customer_id, date, due_date, subtotal, cgst, sgst, igst, total_amount) VALUES
('INV-0001', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 9500.00, 675.00, 675.00, 0.00, 10850.00),
('INV-0002', 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 6200.00, 0.00, 0.00, 756.00, 6956.00);

INSERT INTO sale_items (sale_id, product_id, quantity, rate, gst_percent, tax_amount, total) VALUES
(1, 1, 15, 320.00, 18, 864.00, 5664.00),
(1, 2, 10, 290.00, 18, 522.00, 3422.00),
(1, 4, 5, 390.00, 12, 234.00, 2184.00),
(2, 3, 6, 560.00, 18, 604.80, 3964.80),
(2, 5, 5, 640.00, 5, 160.00, 3360.00);

INSERT INTO supplier_price_history (product_id, supplier_id, rate, recorded_on, created_at) VALUES
(1, 1, 178.00, CURDATE(), NOW()),
(1, 2, 185.00, CURDATE(), NOW()),
(2, 1, 158.00, CURDATE(), NOW());

INSERT INTO price_lists (name, channel, created_at) VALUES
('Retail List', 'Retail', NOW()),
('Wholesale List', 'Wholesale', NOW());

INSERT INTO price_list_items (price_list_id, product_id, price) VALUES
(1, 1, 320.00),
(1, 2, 290.00),
(2, 1, 290.00),
(2, 2, 260.00);
