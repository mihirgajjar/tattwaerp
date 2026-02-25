<?php

class Smart
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->ensureSmartSchema();
    }

    public function data(string $from, string $to, string $month): array
    {
        return [
            'reorder' => $this->reorderSuggestions(),
            'forecast' => $this->demandForecast(),
            'margin' => $this->marginIntelligence($from, $to),
            'expiring_batches' => $this->expiringBatches(),
            'aging' => $this->receivableAging(),
            'supplier_rates' => $this->supplierRateSnapshots(),
            'warehouses' => $this->warehouses(),
            'stock_by_wh' => $this->stockByWarehouse(),
            'price_lists' => $this->priceLists(),
            'approvals' => $this->approvals(),
            'audit' => (new Audit())->recent(),
            'notifications' => $this->notifications(),
            'gst_compliance' => $this->gstCompliance($month),
            'products' => (new Product())->all(),
            'sales' => (new Sale())->all(),
            'customers' => (new Partner())->all('customers'),
            'suppliers' => (new Partner())->all('suppliers'),
        ];
    }

    public function reorderSuggestions(): array
    {
        $sql = "SELECT p.id, p.product_name, p.sku, p.stock_quantity, p.reorder_level, p.lead_time_days, p.moq,
                    COALESCE(SUM(CASE WHEN s.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN si.quantity ELSE 0 END), 0) AS sold_30d
                FROM products p
                LEFT JOIN sale_items si ON si.product_id = p.id
                LEFT JOIN sales s ON s.id = si.sale_id AND s.status = 'FINAL'
                GROUP BY p.id
                ORDER BY p.product_name";
        $rows = $this->db->query($sql)->fetchAll();

        foreach ($rows as &$row) {
            $avgDaily = (float)$row['sold_30d'] / 30.0;
            $target = ($avgDaily * max(1, (int)$row['lead_time_days'])) + (int)$row['reorder_level'];
            $need = max(0, (int)ceil($target - (int)$row['stock_quantity']));
            if ($need > 0 && $need < (int)$row['moq']) {
                $need = (int)$row['moq'];
            }
            $row['avg_daily'] = round($avgDaily, 2);
            $row['suggested_order_qty'] = $need;
        }
        unset($row);

        return array_values(array_filter($rows, fn($r) => (int)$r['suggested_order_qty'] > 0));
    }

    public function demandForecast(): array
    {
        $sql = "SELECT p.product_name, p.sku,
                    COALESCE(SUM(CASE WHEN s.date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) THEN si.quantity ELSE 0 END), 0) AS sold_60d
                FROM products p
                LEFT JOIN sale_items si ON si.product_id = p.id
                LEFT JOIN sales s ON s.id = si.sale_id AND s.status = 'FINAL'
                GROUP BY p.id
                ORDER BY p.product_name";
        $rows = $this->db->query($sql)->fetchAll();

        foreach ($rows as &$row) {
            $daily = (float)$row['sold_60d'] / 60.0;
            $row['forecast_30d'] = (int)round($daily * 30);
            $row['forecast_60d'] = (int)round($daily * 60);
            $row['forecast_90d'] = (int)round($daily * 90);
        }
        unset($row);

        return $rows;
    }

    public function marginIntelligence(string $from, string $to): array
    {
        $sql = "SELECT s.invoice_no, s.date, p.product_name, si.quantity, si.rate,
                    (si.quantity * p.purchase_price) AS estimated_cost,
                    (si.quantity * si.rate) AS gross_revenue,
                    (si.quantity * si.rate) - (si.quantity * p.purchase_price) AS margin
                FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                JOIN products p ON p.id = si.product_id
                WHERE s.date BETWEEN :from AND :to AND s.status = 'FINAL'
                ORDER BY s.date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }

    public function receivableAging(): array
    {
        $sql = "SELECT s.id, s.invoice_no, s.date, s.due_date, c.name AS customer_name, s.total_amount,
                    COALESCE((SELECT SUM(cp.amount) FROM customer_payments cp WHERE cp.sale_id = s.id), 0) AS paid,
                    DATEDIFF(CURDATE(), s.due_date) AS days_overdue
                FROM sales s
                JOIN customers c ON c.id = s.customer_id
                WHERE s.status = 'FINAL'
                ORDER BY s.date DESC";
        $rows = $this->db->query($sql)->fetchAll();

        foreach ($rows as &$row) {
            $outstanding = (float)$row['total_amount'] - (float)$row['paid'];
            $row['outstanding'] = round(max(0, $outstanding), 2);
            $bucket = 'Current';
            if ($row['outstanding'] <= 0.0) {
                $bucket = 'Paid';
            } elseif ((int)$row['days_overdue'] > 90) {
                $bucket = '>90';
            } elseif ((int)$row['days_overdue'] > 60) {
                $bucket = '61-90';
            } elseif ((int)$row['days_overdue'] > 30) {
                $bucket = '31-60';
            } elseif ((int)$row['days_overdue'] > 0) {
                $bucket = '1-30';
            }
            $row['bucket'] = $bucket;
        }
        unset($row);

        return $rows;
    }

    public function recordPayment(int $saleId, float $amount, string $mode): void
    {
        $stmt = $this->db->prepare('INSERT INTO customer_payments (sale_id, amount, payment_mode, payment_date, created_at) VALUES (:sale_id, :amount, :payment_mode, CURDATE(), NOW())');
        $stmt->execute([
            'sale_id' => $saleId,
            'amount' => $amount,
            'payment_mode' => $mode,
        ]);
    }

    public function expiringBatches(): array
    {
        $sql = "SELECT pb.*, p.product_name FROM product_batches pb JOIN products p ON p.id = pb.product_id
                WHERE pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 120 DAY)
                ORDER BY pb.expiry_date ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function addBatch(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO product_batches (product_id, warehouse_id, batch_no, mfg_date, expiry_date, quantity, created_at)
            VALUES (:product_id, :warehouse_id, :batch_no, :mfg_date, :expiry_date, :quantity, NOW())');
        $stmt->execute($data);

        $stockStmt = $this->db->prepare('INSERT INTO product_warehouse_stock (product_id, warehouse_id, quantity)
            VALUES (:product_id, :warehouse_id, :quantity)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
        $stockStmt->execute([
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'],
            'quantity' => $data['quantity'],
        ]);
    }

    public function supplierRateSnapshots(): array
    {
        $sql = "SELECT p.product_name, s.name AS supplier_name, spr.rate, spr.recorded_on
                FROM supplier_price_history spr
                JOIN products p ON p.id = spr.product_id
                JOIN suppliers s ON s.id = spr.supplier_id
                ORDER BY spr.recorded_on DESC, spr.id DESC LIMIT 50";
        return $this->db->query($sql)->fetchAll();
    }

    public function saveSupplierRate(int $productId, int $supplierId, float $rate): void
    {
        $stmt = $this->db->prepare('INSERT INTO supplier_price_history (product_id, supplier_id, rate, recorded_on, created_at)
            VALUES (:product_id, :supplier_id, :rate, CURDATE(), NOW())');
        $stmt->execute([
            'product_id' => $productId,
            'supplier_id' => $supplierId,
            'rate' => $rate,
        ]);
    }

    public function priceLists(): array
    {
        $lists = $this->db->query('SELECT * FROM price_lists ORDER BY id DESC')->fetchAll();
        foreach ($lists as &$list) {
            $stmt = $this->db->prepare('SELECT pli.*, p.product_name FROM price_list_items pli JOIN products p ON p.id = pli.product_id WHERE pli.price_list_id = :id');
            $stmt->execute(['id' => $list['id']]);
            $list['items'] = $stmt->fetchAll();
        }
        unset($list);
        return $lists;
    }

    public function createPriceList(string $name, string $channel): int
    {
        $stmt = $this->db->prepare('INSERT INTO price_lists (name, channel, created_at) VALUES (:name, :channel, NOW())');
        $stmt->execute(['name' => $name, 'channel' => $channel]);
        return (int)$this->db->lastInsertId();
    }

    public function addPriceListItem(int $listId, int $productId, float $price): void
    {
        $stmt = $this->db->prepare('INSERT INTO price_list_items (price_list_id, product_id, price) VALUES (:price_list_id, :product_id, :price)
            ON DUPLICATE KEY UPDATE price = VALUES(price)');
        $stmt->execute(['price_list_id' => $listId, 'product_id' => $productId, 'price' => $price]);
    }

    public function warehouses(): array
    {
        return $this->db->query('SELECT * FROM warehouses ORDER BY id ASC')->fetchAll();
    }

    public function addWarehouse(string $name, string $state): void
    {
        $stmt = $this->db->prepare('INSERT INTO warehouses (name, state, created_at) VALUES (:name, :state, NOW())');
        $stmt->execute(['name' => $name, 'state' => $state]);
    }

    public function stockByWarehouse(): array
    {
        $sql = 'SELECT w.name AS warehouse, p.product_name, pws.quantity
                FROM product_warehouse_stock pws
                JOIN warehouses w ON w.id = pws.warehouse_id
                JOIN products p ON p.id = pws.product_id
                ORDER BY w.name, p.product_name';
        return $this->db->query($sql)->fetchAll();
    }

    public function transferStock(array $data): void
    {
        $this->db->beginTransaction();
        try {
            $hdr = $this->db->prepare('INSERT INTO stock_transfers (product_id, from_warehouse_id, to_warehouse_id, quantity, transfer_date, created_at)
                VALUES (:product_id, :from_warehouse_id, :to_warehouse_id, :quantity, CURDATE(), NOW())');
            $hdr->execute($data);

            $dec = $this->db->prepare('UPDATE product_warehouse_stock SET quantity = quantity - :qty WHERE product_id = :pid AND warehouse_id = :wid AND quantity >= :qty');
            $dec->execute(['qty' => $data['quantity'], 'pid' => $data['product_id'], 'wid' => $data['from_warehouse_id']]);
            if ($dec->rowCount() === 0) {
                throw new RuntimeException('Insufficient source warehouse stock.');
            }

            $inc = $this->db->prepare('INSERT INTO product_warehouse_stock (product_id, warehouse_id, quantity)
                VALUES (:pid, :wid, :qty)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
            $inc->execute(['pid' => $data['product_id'], 'wid' => $data['to_warehouse_id'], 'qty' => $data['quantity']]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createReturn(int $saleId, int $productId, int $qty, string $reason): void
    {
        $this->db->beginTransaction();
        try {
            $si = $this->db->prepare('SELECT rate, gst_percent FROM sale_items WHERE sale_id = :sale_id AND product_id = :product_id LIMIT 1');
            $si->execute(['sale_id' => $saleId, 'product_id' => $productId]);
            $item = $si->fetch();
            if (!$item) {
                throw new RuntimeException('Sale item not found for return.');
            }

            $taxable = $qty * (float)$item['rate'];
            $tax = $taxable * ((float)$item['gst_percent'] / 100);
            $total = $taxable + $tax;

            $creditNo = 'CRN-' . str_pad((string)time(), 10, '0', STR_PAD_LEFT);
            $hdr = $this->db->prepare('INSERT INTO sales_returns (credit_note_no, sale_id, return_date, reason, total_amount, created_at)
                VALUES (:credit_note_no, :sale_id, CURDATE(), :reason, :total_amount, NOW())');
            $hdr->execute(['credit_note_no' => $creditNo, 'sale_id' => $saleId, 'reason' => $reason, 'total_amount' => $total]);
            $rid = (int)$this->db->lastInsertId();

            $itm = $this->db->prepare('INSERT INTO sales_return_items (sales_return_id, product_id, quantity, rate, gst_percent, tax_amount, total)
                VALUES (:sales_return_id, :product_id, :quantity, :rate, :gst_percent, :tax_amount, :total)');
            $itm->execute([
                'sales_return_id' => $rid,
                'product_id' => $productId,
                'quantity' => $qty,
                'rate' => $item['rate'],
                'gst_percent' => $item['gst_percent'],
                'tax_amount' => $tax,
                'total' => $total,
            ]);

            $stock = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id');
            $stock->execute(['qty' => $qty, 'id' => $productId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function approvals(): array
    {
        return $this->db->query('SELECT * FROM approvals ORDER BY id DESC LIMIT 50')->fetchAll();
    }

    public function createApproval(string $type, string $reference, string $notes): void
    {
        $stmt = $this->db->prepare('INSERT INTO approvals (approval_type, reference_no, notes, status, created_at) VALUES (:approval_type, :reference_no, :notes, :status, NOW())');
        $stmt->execute([
            'approval_type' => $type,
            'reference_no' => $reference,
            'notes' => $notes,
            'status' => 'PENDING',
        ]);
    }

    public function reviewApproval(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE approvals SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'reviewed_by' => Auth::user()['username'] ?? 'admin',
            'id' => $id,
        ]);
    }

    public function notifications(): array
    {
        return $this->db->query('SELECT * FROM notifications ORDER BY id DESC LIMIT 20')->fetchAll();
    }

    public function runNotifications(): void
    {
        $sales = $this->db->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM sales WHERE date = CURDATE() AND status = 'FINAL'")->fetch()['v'];
        $due = $this->db->query("SELECT COALESCE(SUM(s.total_amount - IFNULL(p.paid,0)),0) AS v
            FROM sales s
            LEFT JOIN (SELECT sale_id, SUM(amount) AS paid FROM customer_payments GROUP BY sale_id) p ON p.sale_id = s.id
            WHERE s.status = 'FINAL' AND (s.total_amount - IFNULL(p.paid,0)) > 0")->fetch()['v'];
        $low = $this->db->query('SELECT COUNT(*) AS c FROM products WHERE stock_quantity < reorder_level')->fetch()['c'];

        $message = 'Daily Summary: Sales Rs ' . number_format((float)$sales, 2) . ', Outstanding Rs ' . number_format((float)$due, 2) . ', Low Stock Items ' . (int)$low;

        $stmt = $this->db->prepare('INSERT INTO notifications (channel, message, status, created_at) VALUES (:channel, :message, :status, NOW())');
        $stmt->execute(['channel' => 'WHATSAPP', 'message' => $message, 'status' => 'QUEUED']);
    }

    public function gstCompliance(string $month): array
    {
        $stmt = $this->db->prepare("SELECT p.hsn_code,
                SUM(si.quantity * si.rate) AS taxable,
                SUM(si.tax_amount) AS gst
            FROM sale_items si
            JOIN sales s ON s.id = si.sale_id
            JOIN products p ON p.id = si.product_id
            WHERE DATE_FORMAT(s.date, '%Y-%m') = :month AND s.status = 'FINAL'
            GROUP BY p.hsn_code
            ORDER BY p.hsn_code");
        $stmt->execute(['month' => $month]);
        return $stmt->fetchAll();
    }

    private function ensureSmartSchema(): void
    {
        if (!$this->columnExists('products', 'lead_time_days')) {
            $this->db->exec('ALTER TABLE products ADD COLUMN lead_time_days INT NOT NULL DEFAULT 7');
        }
        if (!$this->columnExists('products', 'moq')) {
            $this->db->exec('ALTER TABLE products ADD COLUMN moq INT NOT NULL DEFAULT 10');
        }
        if (!$this->columnExists('sales', 'due_date')) {
            $this->db->exec('ALTER TABLE sales ADD COLUMN due_date DATE NULL');
            $this->db->exec('UPDATE sales SET due_date = DATE_ADD(date, INTERVAL 15 DAY) WHERE due_date IS NULL');
        }

        $queries = [
            'CREATE TABLE IF NOT EXISTS warehouses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                state VARCHAR(80) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS product_warehouse_stock (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                warehouse_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 0,
                UNIQUE KEY uq_pws (product_id, warehouse_id)
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS product_batches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                warehouse_id INT NOT NULL,
                batch_no VARCHAR(80) NOT NULL,
                mfg_date DATE,
                expiry_date DATE,
                quantity INT NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS customer_payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sale_id INT NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                payment_mode VARCHAR(40) NOT NULL,
                payment_date DATE NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS sales_returns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                credit_note_no VARCHAR(40) NOT NULL UNIQUE,
                sale_id INT NOT NULL,
                return_date DATE NOT NULL,
                reason VARCHAR(255) NOT NULL,
                total_amount DECIMAL(12,2) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS sales_return_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sales_return_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                rate DECIMAL(12,2) NOT NULL,
                gst_percent DECIMAL(5,2) NOT NULL,
                tax_amount DECIMAL(12,2) NOT NULL,
                total DECIMAL(12,2) NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS supplier_price_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                supplier_id INT NOT NULL,
                rate DECIMAL(12,2) NOT NULL,
                recorded_on DATE NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS price_lists (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                channel VARCHAR(40) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS price_list_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                price_list_id INT NOT NULL,
                product_id INT NOT NULL,
                price DECIMAL(12,2) NOT NULL,
                UNIQUE KEY uq_pli (price_list_id, product_id)
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS stock_transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                from_warehouse_id INT NOT NULL,
                to_warehouse_id INT NOT NULL,
                quantity INT NOT NULL,
                transfer_date DATE NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS approvals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                approval_type VARCHAR(60) NOT NULL,
                reference_no VARCHAR(60) NOT NULL,
                notes TEXT,
                status VARCHAR(20) NOT NULL,
                reviewed_by VARCHAR(50),
                reviewed_at DATETIME,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                channel VARCHAR(30) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
        ];

        foreach ($queries as $q) {
            $this->db->exec($q);
        }

        $cnt = (int)$this->db->query('SELECT COUNT(*) AS c FROM warehouses')->fetch()['c'];
        if ($cnt === 0) {
            $this->db->exec("INSERT INTO warehouses (name, state, created_at) VALUES ('Main Warehouse', 'Maharashtra', NOW()), ('Delhi Hub', 'Delhi', NOW())");
        }

        $stockCnt = (int)$this->db->query('SELECT COUNT(*) AS c FROM product_warehouse_stock')->fetch()['c'];
        if ($stockCnt === 0) {
            $this->db->exec('INSERT INTO product_warehouse_stock (product_id, warehouse_id, quantity)
                SELECT p.id, 1, p.stock_quantity FROM products p');
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare("SHOW COLUMNS FROM {$table} LIKE :col");
        $stmt->execute(['col' => $column]);
        return (bool)$stmt->fetch();
    }
}
