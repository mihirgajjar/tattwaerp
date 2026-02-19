<?php

function config(string $file): array
{
    static $configs = [];

    if (!isset($configs[$file])) {
        $path = __DIR__ . '/../config/' . $file . '.php';
        $configs[$file] = file_exists($path) ? require $path : [];
    }

    return $configs[$file];
}

function redirect(string $route): void
{
    header('Location: index.php?route=' . $route);
    exit;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function old(string $key, string $default = ''): string
{
    return $_SESSION['_old'][$key] ?? $default;
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function app_setting(string $key, string $default = ''): string
{
    try {
        return (new AppSetting())->get($key, $default);
    } catch (Throwable $e) {
        return $default;
    }
}

function audit_log(string $action, string $entity, int $entityId = 0, array $meta = []): void
{
    try {
        (new Audit())->log($action, $entity, $entityId, $meta);
    } catch (Throwable $e) {
        // Keep business flow running if audit storage is unavailable.
    }
}

function validate_password_policy(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must include at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must include at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must include at least one digit.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return 'Password must include at least one special character.';
    }

    return null;
}

function current_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function number_to_words_indian(float $amount): string
{
    if (!class_exists('NumberFormatter')) {
        return 'Rupees ' . number_format($amount, 2) . ' only';
    }

    $formatter = new NumberFormatter('en_IN', NumberFormatter::SPELLOUT);
    $parts = explode('.', number_format($amount, 2, '.', ''));
    $rupees = ucfirst($formatter->format((int)$parts[0]));
    $paise = (int)$parts[1];

    if ($paise > 0) {
        return $rupees . ' rupees and ' . $formatter->format($paise) . ' paise only';
    }

    return $rupees . ' rupees only';
}
