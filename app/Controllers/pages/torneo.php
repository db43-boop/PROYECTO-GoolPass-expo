<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_login();

try {
    $equipos = Equipo::opcionesTorneo();

} catch (PDOException $e) {
    die("Error al consultar equipos: " . $e->getMessage());
}

$json_equipos = json_encode($equipos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
?>

