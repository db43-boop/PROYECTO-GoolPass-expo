<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_login();

// --- Datos para la portada (con degradación segura si la BD falla) ---
$totalEquipos = 0;
$totalLigas = 0;
$ultimos = [];
$jugadoresPorEquipo = [];

try {
    $totales = Equipo::totales();
    $totalEquipos = $totales['equipos'];
    $totalLigas = $totales['ligas'];
    $ultimos = Equipo::recientes();
    foreach (Jugador::porEquipos(array_column($ultimos, 'id')) as $j) {
        $jugadoresPorEquipo[(int)$j['equipo_id']][] = $j;
    }
} catch (PDOException $e) {
    error_log('GoolPass inicio: '.$e->getMessage());
    $ultimos = [];
    $jugadoresPorEquipo = [];
}

/** Devuelve la inicial para el avatar de respaldo */
function inicial_avatar($nombre)
{
    $nombre = trim((string) $nombre);
    if ($nombre === '') return '?';
    return mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'));
}

/** Abreviatura y clase de color según la posición del jugador */
function pos_meta($pos)
{
    switch ($pos) {
        case 'Portero':         return ['POR', 'por'];
        case 'Defensas':        return ['DEF', 'def'];
        case 'Centrocampistas': return ['MED', 'med'];
        case 'Delanteros':      return ['DEL', 'del'];
        default:                return ['—',   'otro'];
    }
}
?>
