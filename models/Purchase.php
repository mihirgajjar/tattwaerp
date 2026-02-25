<?php

class Purchase
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $sql = 'SELECT p.*, s.name AS supplier_name FROM purchases p JOIN suppliers s ON s.id = p.supplier_id ORDER BY p.id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM purchases WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function items(int $purchaseId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM purchase_items WHERE purchase_id = :id');
        $stmt->execute(['id' => $purchaseId]);
        return $stmt->fetchAll();
    }

    public function nextInvoiceNo(): string
    {
        $row = $this->db->query('SELECT purchase_invoice_no FROM purchases ORDER BY id DESC LIMIT 1')->fetch();
        if (!$row) {
            return 'PUR-0001';
        }
        $num = (int)substr($row['purchase_invoice_no'], 4) + 1;
        return 'PUR-' . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $header, array $items): int
    {
        $this->db->beginTransaction();

        try {
            $sql = 'INSERT INTO purchases (purchase_invoice_no, supplier_id, date, subtotal, cgst, sgst, igst, total_amount, status, transport_cost, other_charges)
                    VALUES (:purchase_invoice_no, :supplier_id, :date, :subtotal, :cgst, :sgst, :igst, :total_amount, :status, :transport_cost, :other_charges)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($header);

            $purchaseId = (int)$this->db->lastInsertId();

            $itemSql = 'INSERT INTO purchase_items (purchase_id, product_id, quantity, rate, gst_percent, tax_amount, total)
                        VALUES (:purchase_id, :product_id, :quantity, :rate, :gst_percent, :tax_amount, :total)';
            $itemStmt = $this->db->prepare($itemSql);

            foreach ($items as $item) {
                $item['purchase_id'] = $purchaseId;
                $itemStmt->execute($item);
            }

            if (strtoupper((string)$header['status']) === 'FINAL') {
                $this->applyStockIn($items);
            }

            $this->db->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateDraft(int $id, array $header, array $items): void
    {
        $purchase = $this->find($id);
        if (!$purchase) {
            throw new RuntimeException('Purchase not found.');
        }

        $oldStatus = strtoupper((string)$purchase['status']);
        if ($oldStatus !== 'DRAFT') {
            throw new RuntimeException('Only draft purchases can be edited.');
        }

        $newStatus = strtoupper((string)$header['status']);
        if (!in_array($newStatus, ['DRAFT', 'FINAL'], true)) {
            throw new RuntimeException('Invalid purchase status.');
        }

        $this->db->beginTransaction();
        try {
            $sql = 'UPDATE purchases
                    SET purchase_invoice_no = :purchase_invoice_no,
                        supplier_id = :supplier_id,
                        date = :date,
                        subtotal = :subtotal,
                        cgst = :cgst,
                        sgst = :sgst,
                        igst = :igst,
                        total_amount = :total_amount,
                        status = :status,
                        transport_cost = :transport_cost,
                        other_charges = :other_charges
                    WHERE id = :id';
            $params = $header;
            $params['id'] = $id;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $delItems = $this->db->prepare('DELETE FROM purchase_items WHERE purchase_id = :id');
            $delItems->execute(['id' => $id]);

            $itemSql = 'INSERT INTO purchase_items (purchase_id, product_id, quantity, rate, gst_percent, tax_amount, total)
                        VALUES (:purchase_id, :product_id, :quantity, :rate, :gst_percent, :tax_amount, :total)';
            $itemStmt = $this->db->prepare($itemSql);
            foreach ($items as $item) {
                $item['purchase_id'] = $id;
                $itemStmt->execute($item);
            }

            if ($newStatus === 'FINAL') {
                $this->applyStockIn($items);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function setStatus(int $id, string $newStatus): void
    {
        $purchase = $this->find($id);
        if (!$purchase) {
            throw new RuntimeException('Purchase not found.');
        }

        $oldStatus = strtoupper((string)$purchase['status']);
        $newStatus = strtoupper($newStatus);

        if ($oldStatus === $newStatus) {
            return;
        }

        if (!in_array($newStatus, ['DRAFT', 'FINAL', 'CANCELLED'], true)) {
            throw new RuntimeException('Invalid purchase status.');
        }

        $items = $this->items($id);

        $this->db->beginTransaction();
        try {
            if ($oldStatus !== 'FINAL' && $newStatus === 'FINAL') {
                $this->applyStockIn($items);
            }

            if ($oldStatus === 'FINAL' && in_array($newStatus, ['DRAFT', 'CANCELLED'], true)) {
                $this->reverseStockIn($items);
            }

            $stmt = $this->db->prepare('UPDATE purchases SET status = :s WHERE id = :id');
            $stmt->execute(['s' => $newStatus, 'id' => $id]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteSafe(int $id): bool
    {
        $purchase = $this->find($id);
        if (!$purchase) {
            return false;
        }

        $status = strtoupper((string)$purchase['status']);
        if (!in_array($status, ['DRAFT', 'CANCELLED'], true)) {
            return false;
        }

        $payStmt = $this->db->prepare('SELECT COALESCE(SUM(amount),0) AS paid FROM customer_payables WHERE purchase_id = :id');
        $payStmt->execute(['id' => $id]);
        if ((float)$payStmt->fetch()['paid'] > 0) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare('DELETE FROM purchases WHERE id = :id');
            $del->execute(['id' => $id]);
            $this->db->commit();
            return $del->rowCount() > 0;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function applyStockIn(array $items): void
    {
        $stockStmt = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id');
        foreach ($items as $item) {
            $stockStmt->execute(['qty' => (int)$item['quantity'], 'id' => (int)$item['product_id']]);
        }
    }

    private function reverseStockIn(array $items): void
    {
        $stockStmt = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id AND stock_quantity >= :qty');
        foreach ($items as $item) {
            $stockStmt->execute(['qty' => (int)$item['quantity'], 'id' => (int)$item['product_id']]);
            if ($stockStmt->rowCount() === 0) {
                throw new RuntimeException('Cannot cancel/revert purchase because current stock is lower than purchased quantity for product ID ' . (int)$item['product_id']);
            }
        }
    }
}
