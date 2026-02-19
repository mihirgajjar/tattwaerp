<?php

class AppSetting
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->ensureTable();
    }

    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :k LIMIT 1');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch();
        return $row['setting_value'] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $sql = 'INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['k' => $key, 'v' => $value]);
    }

    private function ensureTable(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS app_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(80) NOT NULL UNIQUE,
                setting_value TEXT NOT NULL
            ) ENGINE=InnoDB'
        );
    }
}
