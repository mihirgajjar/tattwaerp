<?php

class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): void
    {
        $sql = 'INSERT INTO products (product_name, sku, category, variant, size, hsn_code, gst_percent, purchase_price, selling_price, stock_quantity, reorder_level, reserved_stock, min_stock_level, barcode, image_path, is_active, created_at)
                VALUES (:product_name, :sku, :category, :variant, :size, :hsn_code, :gst_percent, :purchase_price, :selling_price, :stock_quantity, :reorder_level, :reserved_stock, :min_stock_level, :barcode, :image_path, :is_active, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $sql = 'UPDATE products SET product_name=:product_name, sku=:sku, category=:category, variant=:variant, size=:size, hsn_code=:hsn_code,
                gst_percent=:gst_percent, purchase_price=:purchase_price, selling_price=:selling_price, stock_quantity=:stock_quantity,
                reorder_level=:reorder_level, reserved_stock=:reserved_stock, min_stock_level=:min_stock_level, barcode=:barcode,
                image_path=:image_path, is_active=:is_active WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }

    public function adjustStock(int $id, int $qtyChange): void
    {
        $stmt = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id');
        $stmt->execute(['qty' => $qtyChange, 'id' => $id]);
    }

    public function lowStock(): array
    {
        return $this->db->query('SELECT * FROM products WHERE stock_quantity < reorder_level ORDER BY stock_quantity ASC')->fetchAll();
    }

    public function stockValuation(): float
    {
        $row = $this->db->query('SELECT COALESCE(SUM(purchase_price * stock_quantity), 0) AS valuation FROM products')->fetch();
        return (float)$row['valuation'];
    }

    public function salesSummary(): array
    {
        $sql = 'SELECT p.product_name, p.sku, COALESCE(SUM(si.quantity),0) AS total_qty, COALESCE(SUM(si.total),0) AS total_sales
                FROM products p
                LEFT JOIN sale_items si ON si.product_id = p.id
                LEFT JOIN sales s ON s.id = si.sale_id
                GROUP BY p.id, p.product_name, p.sku
                ORDER BY total_qty DESC';
        return $this->db->query($sql)->fetchAll();
    }
}
