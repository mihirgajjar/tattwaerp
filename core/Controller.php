<?php

class Controller
{
    protected function view(string $view, array $data = [], bool $useLayout = true): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new RuntimeException('View not found: ' . $viewPath);
        }

        if ($useLayout) {
            require __DIR__ . '/../views/layouts/header.php';
            require $viewPath;
            require __DIR__ . '/../views/layouts/footer.php';
        } else {
            require $viewPath;
        }
    }

    protected function request(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            redirect('auth/login');
        }
    }

    protected function requirePermission(string $permission, string $action = 'read'): void
    {
        $this->requireAuth();
        if (!Auth::can($permission, $action)) {
            flash('error', 'Access denied for this action.');
            redirect('dashboard/index');
        }
    }
}
