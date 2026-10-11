<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_login();

// Se muestran exclusivamente las tarjetas de la categoría seleccionada.
$origenParam = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['origen'] ?? '') : ($_GET['origen'] ?? '');
$origenSeleccionado = $origenParam === 'internacional' ? 'internacional' : 'nacional';

try {
    $equipos = Equipo::listarPorOrigen($origenSeleccionado);
    $conteos = Equipo::conteosPorOrigen();
} catch (PDOException $e) {
    error_log('GoolPass equipos: ' . $e->getMessage());
    die('Error al consultar equipos.');
}

// Solo el administrador puede eliminar equipos. No se realizan eliminaciones por URL (GET).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requerir_admin();
    verificar_csrf();
    if (($_POST['accion'] ?? '') !== 'eliminar_equipo') {
        http_response_code(400);
        exit('Acción no válida.');
    }
    $idEliminar = (int)($_POST['equipo_id'] ?? 0);
    if ($idEliminar <= 0) {
        http_response_code(400);
        exit('Equipo no válido.');
    }
    try {
        $conexion->beginTransaction();
        // SQLite aplica ON DELETE CASCADE para la plantilla.
        Equipo::eliminar($idEliminar);
        $conexion->commit();
        header('Location: ver.php?origen=' . urlencode($origenSeleccionado) . '&mensaje=Equipo+eliminado+correctamente');
        exit;
    } catch (PDOException $e) {
        if ($conexion->inTransaction()) { $conexion->rollBack(); }
        http_response_code(500);
        exit('No se pudo eliminar el equipo. Comprueba si tiene partidos registrados.');
    }
}

// (La creación de partidos se gestiona ahora en partidos.php, solo para admin)
$error = '';
?>
