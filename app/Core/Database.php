<?php
/** Crea y comparte la conexión a SQLite. No contiene consultas del negocio. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $config = require GOOLPASS_ROOT . '/app/Config/database.php';
            $ruta = $config['path'];
            if (!is_dir(dirname($ruta))) {
                throw new RuntimeException('No existe la carpeta storage de GoolPass.');
            }
            self::$pdo = new PDO('sqlite:' . $ruta, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
        }
        return self::$pdo;
    }
}
