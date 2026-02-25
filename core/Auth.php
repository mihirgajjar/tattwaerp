<?php

class Auth
{
    public static function attempt(string $identifier, string $password): bool
    {
        $userModel = new User();
        $user = $userModel->findByLogin($identifier);

        if (!$user) {
            $userModel->logLogin(0, $identifier, false);
            return false;
        }

        if ((int)$user['is_active'] !== 1 || !password_verify($password, $user['password'])) {
            $userModel->logLogin((int)$user['id'], $user['username'], false);
            return false;
        }

        session_regenerate_id(true);
        $roleId = (int)($user['role_id'] ?? 0);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'] ?? '',
            'role' => $user['role_name'] ?? $user['role'],
            'role_id' => $roleId,
            'must_change_password' => (int)$user['must_change_password'] === 1,
            'permissions' => $roleId > 0 ? $userModel->rolePermissions($roleId) : [],
        ];

        $userModel->logLogin((int)$user['id'], $user['username'], true);

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function can(string $permission, string $action = 'read'): bool
    {
        if (!self::check()) {
            return false;
        }

        $user = self::user();
        if (strcasecmp((string)($user['role'] ?? ''), 'Admin') === 0 || strcasecmp((string)($user['role'] ?? ''), 'admin') === 0) {
            return true;
        }

        $perms = $user['permissions'][$permission] ?? null;
        if (!$perms) {
            return false;
        }

        return !empty($perms[$action]);
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
}
