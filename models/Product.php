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

    public function findBySku(string $sku): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE product_name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
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

    public function skuExists(string $sku, int $excludeId = 0): bool
    {
        $sql = 'SELECT id FROM products WHERE sku = :sku';
        $params = ['sku' => $sku];
        if ($excludeId > 0) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function generateSku(array $data, int $excludeId = 0): string
    {
        $category = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)($data['category'] ?? '')));
        $variant = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)($data['variant'] ?? '')));
        $size = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($data['size'] ?? '')));

        $catPart = substr($category !== '' ? $category : 'PRD', 0, 2);
        $varPart = substr($variant !== '' ? $variant : 'ITEM', 0, 3);
        $sizePart = $size !== '' ? $size : 'GEN';

        $base = $catPart . '-' . $varPart . '-' . $sizePart;
        $sku = $base;
        $n = 1;

        while ($this->skuExists($sku, $excludeId)) {
            $sku = $base . '-' . str_pad((string)$n, 2, '0', STR_PAD_LEFT);
            $n++;
        }

        return $sku;
    }
}
