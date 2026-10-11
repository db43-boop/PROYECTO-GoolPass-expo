<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
require_once GOOLPASS_ROOT . '/app/Services/escudos.php';
require_once GOOLPASS_ROOT . '/app/Catalogs/catalogo_ligas.php';
requerir_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
verificar_csrf();

$mensaje = "";
$tipo_mensaje = "";

// ============================================================
// 1. CREAR EQUIPO  (+ jugadores)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_equipo') {
    $nombre = trim($_POST['nombre'] ?? '');
    $anio   = trim($_POST['anio'] ?? '');
    try {
        [$origen, $liga] = validar_liga_equipo($_POST);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        exit('Error al registrar equipo: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    if ($nombre === '' || $liga === '') {
        $mensaje = "Por favor, completa nombre y liga.";
        $tipo_mensaje = "error";
    } elseif ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || (int)$anio < 1800 || (int)$anio > 2100)) {
        $mensaje = "El año del club debe estar entre 1800 y 2100.";
        $tipo_mensaje = "error";
    } else {
        try {
            $jugadores = $_POST['jugadores'] ?? [];
            if (!is_array($jugadores)) {
                throw new InvalidArgumentException('La plantilla de jugadores no es válida.');
            }
            foreach ($jugadores as $jugador) {
                if (!is_array($jugador) || trim((string)($jugador['nombre'] ?? '')) === '' || trim((string)($jugador['posicion'] ?? '')) === '') {
                    throw new InvalidArgumentException('Completa el nombre y la posición de cada jugador agregado.');
                }
            }

            // Primero almacenar la imagen real. Si falla la BD, se retira el archivo subido.
            $nombreEscudo = guardar_escudo_subido($_FILES['escudo'] ?? []);
            $conexion->beginTransaction();
            $equipo_id = Equipo::crear($nombre, $liga, $origen, $anio, $nombreEscudo);
            foreach ($jugadores as $jugador) {
                Jugador::insertar($equipo_id, $jugador);
            }

            $conexion->commit();
            header('Location: ficha.php?id=' . $equipo_id);
            exit;
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            if (!empty($nombreEscudo) && is_file(GOOLPASS_UPLOADS_DIR . '/' . $nombreEscudo)) {
                unlink(GOOLPASS_UPLOADS_DIR . '/' . $nombreEscudo);
            }
            $mensaje = $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'Error al guardar el equipo y sus jugadores: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
        if ($mensaje !== '') {
            http_response_code(400);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>No se pudo guardar el equipo</title>';
            echo '<body style="font-family:Arial,sans-serif;background:#121212;color:#fff;max-width:650px;margin:70px auto;padding:24px">';
            echo '<h1>Error al guardar el equipo</h1><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
            echo '<a style="color:#ffad45" href="crear.php">Volver al formulario</a></body></html>';
            exit;
        }
    }
}

// ============================================================
// 2. ACTUALIZAR EL ESCUDO DE UN EQUIPO EXISTENTE
// ============================================================
if (($_POST['accion'] ?? '') === 'actualizar_escudo') {
    $idEscudo = (int)($_POST['equipo_id'] ?? 0);
    $nombreNuevo = null;
    try {
        if ($idEscudo <= 0) {
            throw new InvalidArgumentException('Equipo no válido.');
        }
        if (!Equipo::existe($idEscudo)) throw new InvalidArgumentException('No se encontró el equipo.');
        $nombreNuevo = guardar_escudo_subido($_FILES['escudo'] ?? []);
        Equipo::actualizarEscudo($idEscudo, $nombreNuevo);
        header('Location: ficha.php?id=' . $idEscudo . '&mensaje=escudo_actualizado');
        exit;
    } catch (Throwable $e) {
        if ($nombreNuevo !== null && is_file(GOOLPASS_UPLOADS_DIR . '/' . $nombreNuevo)) {
            unlink(GOOLPASS_UPLOADS_DIR . '/' . $nombreNuevo);
        }
        http_response_code(400);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error al guardar escudo</title>';
        echo '<body style="font-family:Arial;background:#121212;color:white;margin:65px auto;max-width:680px;padding:24px">';
        echo '<h1>No se pudo guardar el escudo</h1><p>' . e($e->getMessage()) . '</p>';
        echo '<a style="color:#ffae55" href="ficha.php?id=' . $idEscudo . '">Volver a la ficha</a></body></html>';
        exit;
    }
}

// ============================================================
// 3. ACTUALIZAR EQUIPO EXISTENTE (+ jugadores)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_editar'])) {
    $id     = (int)$_POST['accion_editar'];
    $nombre = trim($_POST['nombre'] ?? '');
    $liga   = trim($_POST['liga'] ?? '');
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
            Equipo::actualizar($id, $nombre, $liga, $anio);
            if (!empty($_POST['jugadores']) && is_array($_POST['jugadores'])) {
                Jugador::reemplazarPlantilla($id, $_POST['jugadores']);
            }
            $conexion->commit();

            header("Location: ver.php?mensaje=Equipo+actualizado+correctamente");
            exit;
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            $mensaje = "Error al actualizar el equipo: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}


// ============================================================
// 3. ELIMINAR JUGADOR
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_jugador') {
    $id_jugador = (int)($_POST['id_jugador'] ?? 0);
    if ($id_jugador > 0) {
        try {
            Jugador::eliminar($id_jugador);
        } catch (PDOException $e) {
            die("Error al eliminar jugador: " . $e->getMessage());
        }
    }
    header("Location: ver.php");
    exit;
}

// ============================================================
// 4. AGREGAR / EDITAR JUGADOR (desde modal en ficha.php)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['accion'] ?? ''), ['agregar_jugador', 'editar_jugador'], true)) {
    $accion_player    = $_POST['accion'];
    $id_jugador       = (int)($_POST['id_jugador'] ?? 0);
    $equipo_id        = (int)($_POST['equipo_id'] ?? 0);
    $nombre           = trim($_POST['nombre'] ?? '');
    $posicion         = trim($_POST['posicion'] ?? '');
    $goles            = (int)($_POST['goles'] ?? 0);
    $asistencias      = (int)($_POST['asistencias'] ?? 0);
    $tarjetas_amarillas = (int)($_POST['tarjetas_amarillas'] ?? 0);
    $tarjetas_rojas   = (int)($_POST['tarjetas_rojas'] ?? 0);
    $mvp_count        = (int)($_POST['mvp_count'] ?? 0);
    $trofeos_ganados = max(0, (int)($_POST['trofeos_ganados'] ?? 0));

    // Volver a la ficha del equipo tras procesar
    $volver = $equipo_id > 0 ? "ficha.php?id={$equipo_id}" : "ver.php";

    try {
        if ($accion_player === 'agregar_jugador') {
            if ($equipo_id > 0 && $nombre !== '' && $posicion !== '') {
                Jugador::insertar($equipo_id, compact('nombre','posicion','goles','asistencias','tarjetas_amarillas','tarjetas_rojas','mvp_count','trofeos_ganados'));
            }
        } else { // editar_jugador
            if ($id_jugador > 0 && $equipo_id > 0 && $nombre !== '' && $posicion !== '') {
                Jugador::actualizarParaEquipo($id_jugador, $equipo_id, compact('nombre','posicion','goles','asistencias','tarjetas_amarillas','tarjetas_rojas','mvp_count','trofeos_ganados'));
            }
        }
        header("Location: {$volver}");
        exit;
    } catch (PDOException $e) {
        die("Error al guardar jugador: " . $e->getMessage());
    }
}

// Si llegamos aquí sin procesar nada, redirigir
header("Location: ver.php");
exit;

