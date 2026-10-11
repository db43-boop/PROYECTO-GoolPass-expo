<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_admin();
require_once GOOLPASS_ROOT . '/app/Catalogs/catalogo_ligas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { verificar_csrf(); }

$mensaje = "";
$tipo_mensaje = "info";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ver.php");
    exit;
}

$id = (int)$_GET['id'];

// --- Lista de ligas (coincide con crear.php) ---
$ligas = ligas_internacionales();

/** Opciones del <select> de posición marcando la actual. */
function opciones_posicion($actual)
{
    $vals = ['Portero', 'Defensas', 'Centrocampistas', 'Delanteros'];
    $out = '<option value="">Selecciona…</option>';
    foreach ($vals as $v) {
        $sel = ($v === $actual) ? ' selected' : '';
        $out .= '<option value="' . $v . '"' . $sel . '>' . $v . '</option>';
    }
    return $out;
}

// --- Procesar actualización ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_editar'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    try {
        [$origen, $liga] = validar_liga_equipo($_POST);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        exit('Error al actualizar equipo: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    $anio   = trim($_POST['anio'] ?? '');

    if ($nombre === '') {
        $mensaje = "Por favor, completa el nombre.";
        $tipo_mensaje = "error";
    } elseif ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || (int)$anio < 1800 || (int)$anio > 2100)) {
        $mensaje = "El año del club debe estar entre 1800 y 2100.";
        $tipo_mensaje = "error";
    } else {
        try {
            $conexion->beginTransaction();
            Equipo::actualizar($id, $nombre, $liga, $anio, $origen);
            Jugador::reemplazarPlantilla($id, is_array($_POST['jugadores'] ?? null) ? $_POST['jugadores'] : []);

            $conexion->commit();
            header("Location: ver.php?mensaje=" . urlencode("Equipo actualizado correctamente."));
            exit;
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            $mensaje = "Error al actualizar: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// --- Cargar datos del equipo y sus jugadores ---
try {
    $equipo = Equipo::buscar($id);
    if (!$equipo) die('Equipo no encontrado.');
    $jugadores = Jugador::porEquipo($id);
} catch (PDOException $e) {
    die("Error al consultar equipo: " . $e->getMessage());
}
?>
<?php
$origenActual = origen_equipo($equipo);
$ligaActual = (string)($equipo['liga'] ?? '');
?>
