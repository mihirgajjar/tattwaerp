<?php

class Partner
{
    private PDO $db;
    private array $allowed = ['suppliers', 'customers'];

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(string $table, string $search = '', string $status = 'all'): array
    {
        $this->guard($table);
        $sql = "SELECT * FROM {$table} WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (name LIKE :q OR gstin LIKE :q OR phone LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        if ($status === 'active') {
            $sql .= ' AND is_active = 1';
        } elseif ($status === 'inactive') {
            $sql .= ' AND is_active = 0';
        }

        $sql .= ' ORDER BY id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(string $table, array $data): void
    {
        $this->guard($table);
        if ($table === 'customers') {
            $sql = 'INSERT INTO customers (name, gstin, state, phone, address, customer_type, area_region, payment_terms, credit_limit, pan_no, shipping_address, is_active)
                VALUES (:name, :gstin, :state, :phone, :address, :customer_type, :area_region, :payment_terms, :credit_limit, :pan_no, :shipping_address, :is_active)';
        } else {
            $sql = 'INSERT INTO suppliers (name, gstin, state, phone, address, supplier_type, bank_details, payment_terms, is_active)
                VALUES (:name, :gstin, :state, :phone, :address, :supplier_type, :bank_details, :payment_terms, :is_active)';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function find(string $table, int $id): ?array
    {
        $this->guard($table);
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(string $table, int $id, array $data): void
    {
        $this->guard($table);
        $data['id'] = $id;

        if ($table === 'customers') {
            $sql = 'UPDATE customers SET name=:name, gstin=:gstin, state=:state, phone=:phone, address=:address,
                customer_type=:customer_type, area_region=:area_region, payment_terms=:payment_terms, credit_limit=:credit_limit,
                pan_no=:pan_no, shipping_address=:shipping_address, is_active=:is_active WHERE id=:id';
        } else {
            $sql = 'UPDATE suppliers SET name=:name, gstin=:gstin, state=:state, phone=:phone, address=:address,
                supplier_type=:supplier_type, bank_details=:bank_details, payment_terms=:payment_terms, is_active=:is_active WHERE id=:id';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function delete(string $table, int $id): void
    {
        $this->guard($table);
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function customerDashboard(int $customerId): array
    {
        $summaryStmt = $this->db->prepare("SELECT
            COALESCE(SUM(total_amount),0) AS total_sales,
            MAX(date) AS last_purchase_date
            FROM sales WHERE customer_id = :id");
        $summaryStmt->execute(['id' => $customerId]);
        $summary = $summaryStmt->fetch() ?: ['total_sales' => 0, 'last_purchase_date' => null];

        $outStmt = $this->db->prepare("SELECT COALESCE(SUM(s.total_amount - IFNULL(p.paid,0)),0) AS outstanding
            FROM sales s
            LEFT JOIN (SELECT sale_id, SUM(amount) AS paid FROM customer_payments GROUP BY sale_id) p ON p.sale_id = s.id
            WHERE s.customer_id = :id");
        $outStmt->execute(['id' => $customerId]);
        $outstanding = (float)($outStmt->fetch()['outstanding'] ?? 0);

        $txStmt = $this->db->prepare('SELECT * FROM sales WHERE customer_id = :id ORDER BY date DESC');
        $txStmt->execute(['id' => $customerId]);

        return [
            'summary' => $summary,
            'outstanding' => $outstanding,
            'transactions' => $txStmt->fetchAll(),
        ];
    }

    public function supplierDashboard(int $supplierId): array
    {
        $summaryStmt = $this->db->prepare("SELECT
            COALESCE(SUM(total_amount),0) AS total_purchase,
            MAX(date) AS last_purchase_date
            FROM purchases WHERE supplier_id = :id AND status = 'FINAL'");
        $summaryStmt->execute(['id' => $supplierId]);
        $summary = $summaryStmt->fetch() ?: ['total_purchase' => 0, 'last_purchase_date' => null];

        $outStmt = $this->db->prepare("SELECT COALESCE(SUM(p.total_amount - IFNULL(pp.paid,0)),0) AS outstanding
            FROM purchases p
            LEFT JOIN (SELECT purchase_id, SUM(amount) AS paid FROM customer_payables GROUP BY purchase_id) pp ON pp.purchase_id = p.id
            WHERE p.supplier_id = :id AND p.status = 'FINAL'");
        $outStmt->execute(['id' => $supplierId]);
        $outstanding = (float)($outStmt->fetch()['outstanding'] ?? 0);

        $txStmt = $this->db->prepare('SELECT * FROM purchases WHERE supplier_id = :id AND status = \'FINAL\' ORDER BY date DESC');
        $txStmt->execute(['id' => $supplierId]);

        return [
            'summary' => $summary,
            'outstanding' => $outstanding,
            'transactions' => $txStmt->fetchAll(),
        ];
    }

    private function guard(string $table): void
    {
        if (!in_array($table, $this->allowed, true)) {
            throw new InvalidArgumentException('Invalid table.');
        }
    }
}
