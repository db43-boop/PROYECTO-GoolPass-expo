<?php
/** Inicialización única de la aplicación. */
if (!defined('GOOLPASS_ROOT')) {
    define('GOOLPASS_ROOT', dirname(__DIR__));
    define('GOOLPASS_PUBLIC', GOOLPASS_ROOT . '/public');
    define('GOOLPASS_UPLOADS_DIR', GOOLPASS_PUBLIC . '/uploads');
    define('GOOLPASS_STORAGE', GOOLPASS_ROOT . '/storage');
}
$appConfig = require GOOLPASS_ROOT . '/app/Config/app.php';
date_default_timezone_set($appConfig['timezone']);
spl_autoload_register(static function (string $clase): void {
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/D', $clase)) return;
    $ruta=GOOLPASS_ROOT . '/app/Models/' . $clase . '.php';
    if (is_file($ruta)) require_once $ruta;
});
require_once GOOLPASS_ROOT . '/app/Core/Database.php';
require_once GOOLPASS_ROOT . '/app/Database/Migrations/Schema.php';
try {
    $conexion = Database::connection();
    Schema::migrate($conexion);
} catch (PDOException $e) {
    error_log('GoolPass: error de conexión/migración: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo inicializar la base de datos. Comprueba la configuración y PDO SQLite.');
}
