<?php
session_start();

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Auth.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/controllers/' . $class . '.php',
        __DIR__ . '/models/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

try {
    (new SystemSetup())->ensure();
} catch (Throwable $e) {
    // Keep app available even if migration step partially fails.
}

$route = $_GET['route'] ?? 'dashboard/index';
[$controllerName, $action] = array_pad(explode('/', $route), 2, 'index');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = (string)($_POST['_csrf'] ?? '');
    if (!csrf_validate($csrf)) {
        http_response_code(403);
        flash('error', 'Your session expired or CSRF token is invalid. Please retry.');
        $fallback = Auth::check() ? 'dashboard/index' : 'auth/login';
        redirect($fallback);
    }
}

try {
    $requestKeys = array_values(array_filter(array_keys($_REQUEST), static fn($k) => !in_array($k, ['password', 'current_password', 'new_password', 'confirm_password'], true)));
    audit_log('request', 'route', 0, [
        'route' => $route,
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'keys' => $requestKeys,
    ]);
} catch (Throwable $e) {
    // Ignore logging failures.
}

$controllerClass = ucfirst($controllerName) . 'Controller';

if (!class_exists($controllerClass)) {
    http_response_code(404);
    echo 'Controller not found.';
    exit;
}

$controller = new $controllerClass();

if (!method_exists($controller, $action)) {
    http_response_code(404);
    echo 'Action not found.';
    exit;
}

$controller->$action();
