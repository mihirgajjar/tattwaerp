<?php

class Sale
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $sql = 'SELECT s.*, c.name AS customer_name FROM sales s JOIN customers c ON c.id = s.customer_id ORDER BY s.id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function findWithItems(int $id): ?array
    {
        $saleStmt = $this->db->prepare('SELECT s.*, c.name AS customer_name, c.gstin, c.state, c.phone, c.address FROM sales s JOIN customers c ON c.id = s.customer_id WHERE s.id=:id');
        $saleStmt->execute(['id' => $id]);
        $sale = $saleStmt->fetch();

        if (!$sale) {
            return null;
        }

        $itemStmt = $this->db->prepare('SELECT si.*, p.product_name, p.hsn_code FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id=:id');
        $itemStmt->execute(['id' => $id]);
        $sale['items'] = $itemStmt->fetchAll();

        return $sale;
    }

    public function nextInvoiceNo(): string
    {
        $row = $this->db->query('SELECT invoice_no FROM sales ORDER BY id DESC LIMIT 1')->fetch();
        if (!$row) {
            return 'INV-0001';
        }
        $num = (int)substr($row['invoice_no'], 4) + 1;
        return 'INV-' . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $header, array $items): int
    {
        // Keep invoice and stock mutations atomic.
        $this->db->beginTransaction();

        try {
            $sql = 'INSERT INTO sales (invoice_no, customer_id, date, due_date, subtotal, cgst, sgst, igst, total_amount, status, is_locked, item_discount, overall_discount, round_off, notes, terms, payment_status)
                    VALUES (:invoice_no, :customer_id, :date, :due_date, :subtotal, :cgst, :sgst, :igst, :total_amount, :status, :is_locked, :item_discount, :overall_discount, :round_off, :notes, :terms, :payment_status)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($header);

            $saleId = (int)$this->db->lastInsertId();

            $itemSql = 'INSERT INTO sale_items (sale_id, product_id, quantity, rate, gst_percent, tax_amount, total, discount_amount)
                        VALUES (:sale_id, :product_id, :quantity, :rate, :gst_percent, :tax_amount, :total, :discount_amount)';
            $itemStmt = $this->db->prepare($itemSql);

            foreach ($items as $item) {
                $item['sale_id'] = $saleId;
                $itemStmt->execute($item);
            }
            if (strtoupper((string)$header['status']) === 'FINAL') {
                $this->applyStockOut($items);
            }

            $this->db->commit();
            return $saleId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('SELECT status FROM sales WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();
        if (!$sale) {
            throw new RuntimeException('Sale not found.');
        }

        $oldStatus = strtoupper((string)$sale['status']);
        $newStatus = strtoupper($status);
        if ($oldStatus === $newStatus) {
            return;
        }
        if (!in_array($newStatus, ['DRAFT', 'FINAL', 'CANCELLED', 'VOID'], true)) {
            throw new RuntimeException('Invalid sale status.');
        }

        $itemStmt = $this->db->prepare('SELECT product_id, quantity FROM sale_items WHERE sale_id = :id');
        $itemStmt->execute(['id' => $id]);
        $items = $itemStmt->fetchAll();

        $this->db->beginTransaction();
        try {
            if ($oldStatus !== 'FINAL' && $newStatus === 'FINAL') {
                $this->applyStockOut($items);
            }

            if ($oldStatus === 'FINAL' && in_array($newStatus, ['DRAFT', 'CANCELLED', 'VOID'], true)) {
                $this->reverseStockOut($items);
            }

            $isLocked = $newStatus === 'FINAL' ? 1 : 0;
            $upd = $this->db->prepare('UPDATE sales SET status = :s, is_locked = :l WHERE id = :id');
            $upd->execute(['s' => $newStatus, 'l' => $isLocked, 'id' => $id]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteIfUnpaidAndAllowedStatus(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT status FROM sales WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        $status = strtoupper(trim((string)($row['status'] ?? '')));
        if (!$row || !in_array($status, ['DRAFT', 'CANCELLED', 'VOID'], true)) {
            return false;
        }

        $paid = $this->db->prepare('SELECT COALESCE(SUM(amount),0) AS paid FROM customer_payments WHERE sale_id = :id');
        $paid->execute(['id' => $id]);
        if ((float)$paid->fetch()['paid'] > 0) {
            return false;
        }

        $del = $this->db->prepare('DELETE FROM sales WHERE id = :id');
        $del->execute(['id' => $id]);
        return $del->rowCount() > 0;
    }

    private function applyStockOut(array $items): void
    {
        $stockStmt = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id AND stock_quantity >= :qty');
        foreach ($items as $item) {
            $stockStmt->execute([
                'qty' => (int)$item['quantity'],
                'id' => (int)$item['product_id'],
            ]);
            if ($stockStmt->rowCount() === 0) {
                throw new RuntimeException('Insufficient stock for product ID ' . (int)$item['product_id']);
            }
        }
    }

    private function reverseStockOut(array $items): void
    {
        $stockStmt = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id');
        foreach ($items as $item) {
            $stockStmt->execute([
                'qty' => (int)$item['quantity'],
                'id' => (int)$item['product_id'],
            ]);
        }
    }
}
