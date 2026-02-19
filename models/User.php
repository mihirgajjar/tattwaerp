<?php

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByLogin(string $identifier): ?array
    {
        $stmt = $this->db->prepare('SELECT u.*, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE (u.username = :id OR u.email = :id) LIMIT 1');
        $stmt->execute(['id' => $identifier]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        return $this->findByLogin($username);
    }

    public function all(string $search = '', string $status = 'all'): array
    {
        $sql = 'SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (u.username LIKE :q OR u.email LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        if ($status === 'active') {
            $sql .= ' AND u.is_active = 1';
        } elseif ($status === 'inactive') {
            $sql .= ' AND u.is_active = 0';
        }

        $sql .= ' ORDER BY u.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function roles(): array
    {
        return $this->db->query('SELECT * FROM roles ORDER BY name')->fetchAll();
    }

    public function rolePermissions(int $roleId): array
    {
        $stmt = $this->db->prepare('SELECT p.code, rp.can_read, rp.can_write, rp.can_delete
            FROM role_permissions rp
            JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id = :rid');
        $stmt->execute(['rid' => $roleId]);
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['code']] = [
                'read' => (int)$r['can_read'] === 1,
                'write' => (int)$r['can_write'] === 1,
                'delete' => (int)$r['can_delete'] === 1,
            ];
        }
        return $result;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO users
            (username, email, password, role, role_id, is_active, must_change_password, created_at)
            VALUES (:username, :email, :password, :role, :role_id, :is_active, :must_change_password, NOW())');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->db->prepare('UPDATE users SET
            username = :username,
            email = :email,
            role = :role,
            role_id = :role_id,
            is_active = :is_active,
            must_change_password = :must_change_password
            WHERE id = :id');
        $stmt->execute($data);
    }

    public function setPassword(int $id, string $hash, bool $mustChange = false): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :p, must_change_password = :m WHERE id = :id');
        $stmt->execute(['p' => $hash, 'm' => $mustChange ? 1 : 0, 'id' => $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = :a WHERE id = :id');
        $stmt->execute(['a' => $active ? 1 : 0, 'id' => $id]);
    }

    public function createResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(24));
        $stmt = $this->db->prepare('INSERT INTO password_resets (user_id, token, expires_at, created_at)
            VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())');
        $stmt->execute(['uid' => $userId, 'token' => $token]);
        return $token;
    }

    public function findResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM password_resets WHERE token = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markResetUsed(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function logLogin(int $userId, string $username, bool $success): void
    {
        $stmt = $this->db->prepare('INSERT INTO login_history (user_id, username, ip_address, success, created_at)
            VALUES (:uid, :username, :ip, :success, NOW())');
        $stmt->execute([
            'uid' => $userId,
            'username' => $username,
            'ip' => current_ip(),
            'success' => $success ? 1 : 0,
        ]);
    }

    public function loginHistory(int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM login_history ORDER BY id DESC LIMIT :l');
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
