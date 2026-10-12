<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url_for(string $route, array $params = []): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
    $entryPoint = rtrim($script, '/');
    if (basename($entryPoint) !== 'index.php') {
        $entryPoint = rtrim(dirname($entryPoint), '/') . '/index.php';
    }
    return $entryPoint . '?' . http_build_query(['r' => $route] + $params);
}

function go_to(string $route, array $params = []): never
{
    header('Location: ' . url_for($route, $params));
    exit;
}

function show_view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require GOOLPASS_ROOT . '/app/Views/' . $view . '.php';
    $content = ob_get_clean();
    require GOOLPASS_ROOT . '/app/Views/layout.php';
}

function app_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(app_csrf_token()) . '">';
}

function check_csrf(): void
{
    $posted = $_POST['csrf_token'] ?? '';
    if (!is_string($posted) || !hash_equals(app_csrf_token(), $posted)) {
        http_response_code(403);
        exit('La solicitud venció. Recarga la página e inténtalo nuevamente.');
    }
}

function need_login(): void
{
    if (empty($_SESSION['user']['id'])) {
        go_to('login');
    }
}
