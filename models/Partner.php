<?php

class Partner
{
    private PDO $db;
    private array $allowed = ['suppliers', 'customers'];

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(string $table): array
    {
        if (!in_array($table, $this->allowed, true)) {
            throw new InvalidArgumentException('Invalid table.');
        }
        return $this->db->query("SELECT * FROM {$table} ORDER BY id DESC")->fetchAll();
    }

    public function create(string $table, array $data): void
    {
        if (!in_array($table, $this->allowed, true)) {
            throw new InvalidArgumentException('Invalid table.');
        }
        $sql = "INSERT INTO {$table} (name, gstin, state, phone, address) VALUES (:name, :gstin, :state, :phone, :address)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function find(string $table, int $id): ?array
    {
        if (!in_array($table, $this->allowed, true)) {
            throw new InvalidArgumentException('Invalid table.');
        }

        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(string $table, int $id, array $data): void
    {
        if (!in_array($table, $this->allowed, true)) {
            throw new InvalidArgumentException('Invalid table.');
        }

        $data['id'] = $id;
        $sql = "UPDATE {$table} SET name = :name, gstin = :gstin, state = :state, phone = :phone, address = :address WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function delete(string $table, int $id): void
    {
        if (!in_array($table, $this->allowed, true)) {
            throw new InvalidArgumentException('Invalid table.');
        }

        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
