<?php

class Finance
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function banks(): array
    {
        return $this->db->query('SELECT * FROM bank_accounts ORDER BY is_default DESC, id DESC')->fetchAll();
    }

    public function addBank(array $data): void
    {
        if (!empty($data['is_default'])) {
            $this->db->exec('UPDATE bank_accounts SET is_default = 0');
        }

        $stmt = $this->db->prepare('INSERT INTO bank_accounts
            (bank_name, account_name, account_no, ifsc, upi_id, qr_image_path, is_default, is_active, created_at)
            VALUES (:bank_name, :account_name, :account_no, :ifsc, :upi_id, :qr_image_path, :is_default, 1, NOW())');
        $stmt->execute($data);
    }

    public function receivedPayments(string $from, string $to): array
    {
        $stmt = $this->db->prepare('SELECT cp.*, s.invoice_no, c.name AS customer_name
            FROM customer_payments cp
            LEFT JOIN sales s ON s.id = cp.sale_id
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE cp.payment_date BETWEEN :from AND :to
            ORDER BY cp.id DESC');
        $stmt->execute(['from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }

    public function paidPayments(string $from, string $to): array
    {
        $stmt = $this->db->prepare('SELECT pp.*, p.purchase_invoice_no, s.name AS supplier_name
            FROM customer_payables pp
            LEFT JOIN purchases p ON p.id = pp.purchase_id
            LEFT JOIN suppliers s ON s.id = pp.supplier_id
            WHERE pp.payment_date BETWEEN :from AND :to
            ORDER BY pp.id DESC');
        $stmt->execute(['from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }

    public function recordReceived(int $saleId, float $amount, string $mode): void
    {
        if ($saleId <= 0) {
            throw new RuntimeException('Invalid sale selected.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than 0.');
        }

        $saleStmt = $this->db->prepare('SELECT id FROM sales WHERE id = :id');
        $saleStmt->execute(['id' => $saleId]);
        if (!$saleStmt->fetch()) {
            throw new RuntimeException('Sale not found.');
        }

        $stmt = $this->db->prepare('INSERT INTO customer_payments (sale_id, amount, payment_mode, payment_date, created_at)
            VALUES (:sale_id, :amount, :payment_mode, CURDATE(), NOW())');
        $stmt->execute(['sale_id' => $saleId, 'amount' => $amount, 'payment_mode' => $mode]);

        $this->refreshSalePaymentStatus($saleId);
    }

    public function recordPaid(int $supplierId, int $purchaseId, float $amount, string $mode): void
    {
        if ($supplierId <= 0 || $purchaseId <= 0) {
            throw new RuntimeException('Supplier and purchase are required.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than 0.');
        }

        $purchaseStmt = $this->db->prepare('SELECT supplier_id FROM purchases WHERE id = :id');
        $purchaseStmt->execute(['id' => $purchaseId]);
        $purchase = $purchaseStmt->fetch();
        if (!$purchase) {
            throw new RuntimeException('Purchase not found.');
        }
        if ((int)$purchase['supplier_id'] !== $supplierId) {
            throw new RuntimeException('Supplier does not match selected purchase.');
        }

        $stmt = $this->db->prepare('INSERT INTO customer_payables (supplier_id, purchase_id, amount, payment_mode, payment_date, created_at)
            VALUES (:supplier_id, :purchase_id, :amount, :payment_mode, CURDATE(), NOW())');
        $stmt->execute([
            'supplier_id' => $supplierId,
            'purchase_id' => $purchaseId,
            'amount' => $amount,
            'payment_mode' => $mode,
        ]);
    }

    private function refreshSalePaymentStatus(int $saleId): void
    {
        $saleStmt = $this->db->prepare('SELECT total_amount FROM sales WHERE id = :id');
        $saleStmt->execute(['id' => $saleId]);
        $sale = $saleStmt->fetch();
        if (!$sale) {
            return;
        }

        $paidStmt = $this->db->prepare('SELECT COALESCE(SUM(amount),0) AS p FROM customer_payments WHERE sale_id = :id');
        $paidStmt->execute(['id' => $saleId]);
        $paid = (float)$paidStmt->fetch()['p'];
        $total = (float)$sale['total_amount'];

        $status = 'UNPAID';
        if ($paid >= $total && $total > 0) {
            $status = 'PAID';
        } elseif ($paid > 0 && $paid < $total) {
            $status = 'PARTIALLY_PAID';
        }

        $upd = $this->db->prepare('UPDATE sales SET payment_status = :s WHERE id = :id');
        $upd->execute(['s' => $status, 'id' => $saleId]);
    }
}
