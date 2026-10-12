<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$route = $_GET['r'] ?? '';
if (!is_string($route) || $route === '') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $scriptPath = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
    $basePath = rtrim(dirname(dirname($scriptPath)), '/');
    if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
        $requestPath = substr($requestPath, strlen($basePath));
    }
    $route = trim($requestPath, '/');
    if ($route === '' || $route === 'public' || $route === 'public/index.php' || $route === 'index.php') {
        $route = 'home';
    } else {
        $route = preg_replace('/\.php$/i', '', $route) ?? $route;
    }
}
$route = strtolower($route);

if ($route === 'home') {
    go_to(empty($_SESSION['user']['id']) ? 'login' : 'equipos');
}

if ($route === 'assets/app.css') {
    $asset = GOOLPASS_PUBLIC . '/css/app.css';
    if (!is_file($asset)) { http_response_code(404); exit('Hoja de estilos no encontrada.'); }
    header('Content-Type: text/css; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    readfile($asset);
    exit;
}

$controllerRoutes = [
    'login' => [AuthController::class, 'login', ['GET', 'POST']],
    'register' => [AuthController::class, 'register', ['GET', 'POST']],
    'logout' => [AuthController::class, 'logout', ['POST']],
    'equipos' => [EquipoController::class, 'index', ['GET']],
    'equipos/crear' => [EquipoController::class, 'create', ['GET', 'POST']],
    'equipos/ver' => [EquipoController::class, 'show', ['GET']],
    'equipos/jugadores/crear' => [EquipoController::class, 'createPlayer', ['POST']],
    'torneos' => [TorneoController::class, 'index', ['GET']],
    'torneos/ver' => [TorneoController::class, 'show', ['GET']],
    'torneos/crear' => [TorneoController::class, 'create', ['GET', 'POST']],
    'torneos/inscribir' => [TorneoController::class, 'enroll', ['POST']],
    'partidos' => [PartidoController::class, 'index', ['GET']],
    'partidos/crear' => [PartidoController::class, 'create', ['POST']],
    'partidos/estadisticas' => [PartidoController::class, 'statistics', ['GET', 'POST']],
    'entradas' => [EntradaController::class, 'index', ['GET']],
    'entradas/registro' => [EntradaController::class, 'sales', ['GET']],
    'entradas/comprar' => [EntradaController::class, 'buy', ['POST']],
];

if (isset($controllerRoutes[$route])) {
    [$controllerClass, $method, $allowedMethods] = $controllerRoutes[$route];
    $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($requestMethod, $allowedMethods, true)) {
        header('Allow: ' . implode(', ', $allowedMethods));
        http_response_code(405);
        exit('Método no permitido.');
    }
    try {
        $controller = new $controllerClass(Database::connection());
        $controller->$method();
    } catch (Throwable $e) {
        error_log('GoolPass route ' . $route . ': ' . $e->getMessage());
        http_response_code(500);
        exit('Ocurrió un error al procesar la solicitud.');
    }
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>No encontrado</title>';
echo '<body><h1>Página no encontrada</h1><a href="' . h(url_for('equipos')) . '">Ir a GoolPass</a></body></html>';
