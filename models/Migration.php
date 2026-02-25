<?php

class Migration
{
    private PDO $db;
    private string $dir;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->dir = __DIR__ . '/../migrations';
        $this->ensureMigrationsTable();
    }

    public function pending(): array
    {
        $files = $this->migrationFiles();
        $applied = $this->appliedMap();

        return array_values(array_filter($files, static fn($f) => !isset($applied[$f])));
    }

    public function applied(): array
    {
        return $this->db->query('SELECT migration, applied_at FROM schema_migrations ORDER BY id ASC')->fetchAll();
    }

    public function applyAll(): array
    {
        $pending = $this->pending();
        $results = [];

        foreach ($pending as $migration) {
            $this->applyOne($migration);
            $results[] = $migration;
        }

        return $results;
    }

    public function baselineMarkAll(): array
    {
        $files = $this->migrationFiles();
        $applied = $this->appliedMap();
        $marked = [];

        foreach ($files as $migration) {
            if (!isset($applied[$migration])) {
                $this->recordApplied($migration);
                $marked[] = $migration;
            }
        }

        return $marked;
    }

    private function applyOne(string $migration): void
    {
        $path = $this->dir . '/' . $migration;
        if (!is_file($path)) {
            throw new RuntimeException('Migration file not found: ' . $migration);
        }

        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Unable to read migration file: ' . $migration);
        }

        $statements = $this->splitSqlStatements($sql);

        $this->db->beginTransaction();
        try {
            foreach ($statements as $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '') {
                    continue;
                }
                $this->db->exec($trimmed);
            }

            $this->recordApplied($migration);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('Migration failed: ' . $migration . ' - ' . $e->getMessage());
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $lines = preg_split('/\R/', $sql) ?: [];
        $clean = [];
        foreach ($lines as $line) {
            $trim = ltrim($line);
            if (str_starts_with($trim, '--') || str_starts_with($trim, '#')) {
                continue;
            }
            $clean[] = $line;
        }

        $content = implode("\n", $clean);

        $statements = [];
        $buffer = '';
        $inString = false;
        $quote = '';

        $len = strlen($content);
        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];

            if (($ch === '"' || $ch === "'") && ($i === 0 || $content[$i - 1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $quote = $ch;
                } elseif ($quote === $ch) {
                    $inString = false;
                    $quote = '';
                }
            }

            if ($ch === ';' && !$inString) {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function migrationFiles(): array
    {
        if (!is_dir($this->dir)) {
            return [];
        }

        $files = array_values(array_filter(scandir($this->dir) ?: [], static function ($f) {
            return preg_match('/\.sql$/i', $f) === 1;
        }));

        sort($files, SORT_STRING);
        return $files;
    }

    private function appliedMap(): array
    {
        $rows = $this->db->query('SELECT migration FROM schema_migrations')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['migration']] = true;
        }
        return $map;
    }

    private function recordApplied(string $migration): void
    {
        $stmt = $this->db->prepare('INSERT INTO schema_migrations (migration, applied_at) VALUES (:m, NOW())');
        $stmt->execute(['m' => $migration]);
    }

    private function ensureMigrationsTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB');
    }
}
