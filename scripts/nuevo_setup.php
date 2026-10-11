<?php
/** Elimina toda la base SOLO bajo confirmación explícita en CLI. */
if (PHP_SAPI !== 'cli' || !in_array('--confirmar-borrado', $argv ?? [], true)) {
    exit("Atención: esto elimina todos los equipos, usuarios y compras. Solo CLI con --confirmar-borrado.\n");
}
require_once dirname(__DIR__) . '/app/bootstrap.php';
$archivo = GOOLPASS_STORAGE . '/torneo.db';
$bak = GOOLPASS_STORAGE . '/respaldo_antes_de_reiniciar_' . date('Ymd_His') . '.db';
if (is_file($archivo) && !copy($archivo, $bak)) {
    fwrite(STDERR, "No se pudo crear respaldo. No se borraron los datos.\n");exit(1);
}
$tablas = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
$conexion->exec('PRAGMA foreign_keys = OFF');
foreach ($tablas as $t) {
    $conexion->exec('DROP TABLE IF EXISTS "' . str_replace('"','""',$t) . '"');
}
$conexion->exec('PRAGMA foreign_keys = ON');
$conexion->exec('PRAGMA user_version = 0');
Schema::migrate($conexion);
echo "Base de datos reiniciada. Respaldo: $bak\n";
