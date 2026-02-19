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
            $sql = 'INSERT INTO purchases (purchase_invoice_no, supplier_id, date, subtotal, cgst, sgst, igst, total_amount)
                    VALUES (:purchase_invoice_no, :supplier_id, :date, :subtotal, :cgst, :sgst, :igst, :total_amount)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($header);

            $purchaseId = (int)$this->db->lastInsertId();

            $itemSql = 'INSERT INTO purchase_items (purchase_id, product_id, quantity, rate, gst_percent, tax_amount, total)
                        VALUES (:purchase_id, :product_id, :quantity, :rate, :gst_percent, :tax_amount, :total)';
            $itemStmt = $this->db->prepare($itemSql);

            $stockStmt = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id');

            foreach ($items as $item) {
                $item['purchase_id'] = $purchaseId;
                $itemStmt->execute($item);
                $stockStmt->execute(['qty' => $item['quantity'], 'id' => $item['product_id']]);
            }

            $this->db->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
