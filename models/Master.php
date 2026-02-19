<?php

class Master
{
    private PDO $db;

    private array $allowedTables = [
        'product_categories' => 'Product Categories',
        'product_subcategories' => 'Product Sub-categories',
        'brands' => 'Brands',
        'units' => 'Units',
        'payment_modes' => 'Payment Modes',
        'expense_categories' => 'Expense Categories',
        'tax_settings' => 'Tax Settings',
        'warehouses' => 'Warehouses',
    ];

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function tables(): array
    {
        return $this->allowedTables;
    }

    public function list(string $table, string $search = '', string $status = 'all'): array
    {
        $this->guard($table);

        $sql = "SELECT * FROM {$table} WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= ' AND name LIKE :q';
            $params['q'] = '%' . $search . '%';
        }

        if ($status === 'active') {
            $sql .= ' AND (is_active = 1 OR is_active IS NULL)';
        } elseif ($status === 'inactive') {
            $sql .= ' AND is_active = 0';
        }

        $sql .= ' ORDER BY id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function add(string $table, array $data): void
    {
        $this->guard($table);
        if ($table === 'tax_settings') {
            $stmt = $this->db->prepare('INSERT INTO tax_settings (name, gst_rate, is_active, created_at) VALUES (:name, :gst_rate, :is_active, NOW())');
            $stmt->execute([
                'name' => $data['name'],
                'gst_rate' => $data['gst_rate'],
                'is_active' => $data['is_active'],
            ]);
            return;
        }
        if ($table === 'product_subcategories') {
            $stmt = $this->db->prepare('INSERT INTO product_subcategories (category_id, name, is_active, created_at) VALUES (:category_id, :name, :is_active, NOW())');
            $stmt->execute([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'is_active' => $data['is_active'],
            ]);
            return;
        }
        if ($table === 'warehouses') {
            $stmt = $this->db->prepare('INSERT INTO warehouses (name, state, created_at) VALUES (:name, :state, NOW())');
            $stmt->execute([
                'name' => $data['name'],
                'state' => $data['state'],
            ]);
            return;
        }
        $stmt = $this->db->prepare("INSERT INTO {$table} (name, is_active, created_at) VALUES (:name, :is_active, NOW())");
        $stmt->execute([
            'name' => $data['name'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function update(string $table, int $id, array $data): void
    {
        $this->guard($table);
        $data['id'] = $id;

        if ($table === 'tax_settings') {
            $stmt = $this->db->prepare('UPDATE tax_settings SET name=:name, gst_rate=:gst_rate, is_active=:is_active WHERE id=:id');
            $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'gst_rate' => $data['gst_rate'],
                'is_active' => $data['is_active'],
            ]);
            return;
        }
        if ($table === 'product_subcategories') {
            $stmt = $this->db->prepare('UPDATE product_subcategories SET category_id=:category_id, name=:name, is_active=:is_active WHERE id=:id');
            $stmt->execute([
                'id' => $id,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'is_active' => $data['is_active'],
            ]);
            return;
        }
        if ($table === 'warehouses') {
            $stmt = $this->db->prepare('UPDATE warehouses SET name=:name, state=:state WHERE id=:id');
            $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'state' => $data['state'],
            ]);
            return;
        }

        $stmt = $this->db->prepare("UPDATE {$table} SET name=:name, is_active=:is_active WHERE id=:id");
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function find(string $table, int $id): ?array
    {
        $this->guard($table);
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deactivate(string $table, int $id, bool $active): void
    {
        $this->guard($table);
        if ($table === 'warehouses') {
            return;
        }
        $stmt = $this->db->prepare("UPDATE {$table} SET is_active = :a WHERE id = :id");
        $stmt->execute(['a' => $active ? 1 : 0, 'id' => $id]);
    }

    public function delete(string $table, int $id): void
    {
        $this->guard($table);
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE id=:id");
        $stmt->execute(['id' => $id]);
    }

    private function guard(string $table): void
    {
        if (!array_key_exists($table, $this->allowedTables)) {
            throw new InvalidArgumentException('Invalid master table.');
        }
    }
}
