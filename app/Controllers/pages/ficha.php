<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_login();
require_once GOOLPASS_ROOT . '/app/Services/formacion.php';
require_once GOOLPASS_ROOT . '/app/Catalogs/catalogo_ligas.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ver.php");
    exit;
}

$id = (int)$_GET['id'];

try {
    $equipo = Equipo::buscar($id);
    if (!$equipo) die('Equipo no encontrado.');
    $jugadores = Jugador::porEquipo($id);

    // Totales del equipo
    $total_goles = array_sum(array_column($jugadores, 'goles'));
    $total_asistencias = array_sum(array_column($jugadores, 'asistencias'));
    $total_amarillas = array_sum(array_column($jugadores, 'tarjetas_amarillas'));
    $total_rojas = array_sum(array_column($jugadores, 'tarjetas_rojas'));
    $total_mvp = array_sum(array_column($jugadores, 'mvp_count'));
    $total_trofeos = array_sum(array_column($jugadores, 'trofeos_ganados'));
} catch (PDOException $e) {
    die("Error al consultar equipo: " . $e->getMessage());
}

// Mostrar solo archivos locales válidos que realmente existan en uploads.
$archivoEscudo = basename((string)($equipo['escudo'] ?? ''));
$tieneEscudo = $archivoEscudo !== '' && $archivoEscudo !== 'default.png'
    && is_file(GOOLPASS_UPLOADS_DIR . '/' . $archivoEscudo);
$srcEscudo = $tieneEscudo ? 'public/uploads/' . rawurlencode($archivoEscudo) : '';

$color = '#2196F3';
?>
