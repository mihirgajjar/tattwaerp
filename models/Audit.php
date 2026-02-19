<?php

class Audit
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->ensureTable();
    }

    public function log(string $action, string $entity, int $entityId = 0, array $meta = []): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, meta, created_at) VALUES (:user_id, :action, :entity, :entity_id, :meta, NOW())');
        $stmt->execute([
            'user_id' => (int)(Auth::user()['id'] ?? 0),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => json_encode($meta),
        ]);
    }

    public function recent(int $limit = 30): array
    {
        $stmt = $this->db->prepare('SELECT * FROM audit_logs ORDER BY id DESC LIMIT :l');
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function ensureTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL DEFAULT 0,
            action VARCHAR(120) NOT NULL,
            entity VARCHAR(80) NOT NULL,
            entity_id INT NOT NULL DEFAULT 0,
            meta TEXT,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB');
    }
}
