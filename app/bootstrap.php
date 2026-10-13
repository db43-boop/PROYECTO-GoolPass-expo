<?php
/** Inicialización de GoolPass con la nueva estructura MVC y router único. */
if (!defined('GOOLPASS_ROOT')) {
    define('GOOLPASS_ROOT', dirname(__DIR__));
    define('GOOLPASS_PUBLIC', GOOLPASS_ROOT . '/public');
    define('GOOLPASS_UPLOADS_DIR', GOOLPASS_PUBLIC . '/uploads');
    define('GOOLPASS_STORAGE', GOOLPASS_ROOT . '/storage');
}
$appConfig = require GOOLPASS_ROOT . '/app/Config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'America/Lima');
require_once GOOLPASS_ROOT . '/app/Core/Database.php';
require_once GOOLPASS_ROOT . '/app/Core/View.php';
require_once GOOLPASS_ROOT . '/app/Schema.php';
foreach (['Equipo','Jugador','Partido','Entrada','Torneo','Usuario','EstadisticaPartido','Inscripcion'] as $modelo) {
    require_once GOOLPASS_ROOT . '/app/Models/' . $modelo . '.php';
}
require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
require_once GOOLPASS_ROOT . '/app/Controllers/EntradaController.php';
require_once GOOLPASS_ROOT . '/app/Controllers/EquipoController.php';
require_once GOOLPASS_ROOT . '/app/Controllers/PartidoController.php';
require_once GOOLPASS_ROOT . '/app/Controllers/TorneoController.php';
try {
    $conexion = Database::connection();
    Schema::migrate($conexion);
} catch (Throwable $e) {
    error_log('GoolPass: fallo al iniciar SQLite: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo iniciar la base de datos. Verifica PHP PDO_SQLITE, storage y permisos.');
}
