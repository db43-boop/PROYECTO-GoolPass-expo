<?php
require_once 'conexion.php';

$mensaje = "";
$tipo_mensaje = "";

// ============================================================
// 1. CREAR / ACTUALIZAR EQUIPO (tabla 'equipos')
// ============================================================
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'crear_equipo') {
        $nombre = trim($_POST['nombre'] ?? '');
        $liga   = trim($_POST['liga'] ?? '');
        $anio   = trim($_POST['anio'] ?? '');

        if ($nombre === '' || $liga === '') {
            $mensaje = "Por favor, completa nombre y liga.";
            $tipo_mensaje = "error";
        } elseif ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || (int)$anio < 1800 || (int)$anio > 2100)) {
            $mensaje = "El año del club debe estar entre 1800 y 2100.";
            $tipo_mensaje = "error";
        } else {
            try {
                $sql = "INSERT INTO equipos (nombre, liga, anio, escudo) VALUES (:nombre, :liga, :anio, :escudo)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':liga'   => $liga,
                    ':anio'   => $anio,
                    ':escudo' => 'default.png'
                ]);

                $equipo_id = (int)$conexion->lastInsertId();

                // Guardar jugadores si se enviaron
                if (!empty($_POST['jugadores'])) {
                    foreach ($_POST['jugadores'] as $idx => $j) {
                        $equipo_id_temp = (int)($j['equipo_id'] ?? $equipo_id);
                        $nombre_jugador = trim($j['nombre'] ?? '');
                        $posicion       = trim($j['posicion'] ?? '');

                        if ($nombre_jugador !== '' && $posicion !== '') {
                            $sql_player = "INSERT INTO jugadores
                                (equipo_id, nombre, posicion, goles, asistencias, tarjetas_amarillas, tarjetas_rojas, mvp_count)
                                VALUES (:equipo_id, :nombre, :posicion, :goles, :asistencias, :tarjetas_amarillas, :tarjetas_rojas, :mvp_count)";
                            $stmt_p = $conexion->prepare($sql_player);
                            $stmt_p->execute([
                                ':equipo_id'            => $equipo_id_temp,
                                ':nombre'               => $nombre_jugador,
                                ':posicion'             => $posicion,
                                ':goles'                => (int)($j['goles'] ?? 0),
                                ':asistencias'          => (int)($j['asistencias'] ?? 0),
                                ':tarjetas_amarillas'   => (int)($j['tarjetas_amarillas'] ?? 0),
                                ':tarjetas_rojas'       => (int)($j['tarjetas_rojas'] ?? 0),
                                ':mvp_count'            => (int)($j['mvp_count'] ?? 0)
                            ]);
                        }
                    }
                }

                header("Location: ver.php?mensaje=Equipo+guardado+correctamente");
                exit;
            } catch (PDOException $e) {
                $mensaje = "Error al guardar el equipo: " . $e->getMessage();
                $tipo_mensaje = "error";
            }
        }
    }
}

// ============================================================
// 2. ACTUALIZAR EQUIPO EXISTENTE (con datos del formulario)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_editar'])) {
    $id = (int)$_POST['accion_editar'];
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
            $sql = "UPDATE equipos SET nombre = :nombre, liga = :liga, anio = :anio WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':liga'   => $liga,
                ':anio'   => $anio,
                ':id'     => $id
            ]);

            // Guardar/actualizar jugadores
            if (!empty($_POST['jugadores'])) {
                // Eliminar jugadores anteriores
                $conexion->exec("DELETE FROM jugadores WHERE equipo_id = :id");
                foreach ($_POST['jugadores'] as $idx => $j) {
                    $nombre_jugador = trim($j['nombre'] ?? '');
                    $posicion       = trim($j['posicion'] ?? '');
                    if ($nombre_jugador !== '' && $posicion !== '') {
                        $sql_player = "INSERT INTO jugadores
                            (equipo_id, nombre, posicion, goles, asistencias, tarjetas_amarillas, tarjetas_rojas, mvp_count)
                            VALUES (:equipo_id, :nombre, :posicion, :goles, :asistencias, :tarjetas_amarillas, :tarjetas_rojas, :mvp_count)";
                        $stmt_p = $conexion->prepare($sql_player);
                        $stmt_p->execute([
                            ':equipo_id'            => $id,
                            ':nombre'               => $nombre_jugador,
                            ':posicion'             => $posicion,
                            ':goles'                => (int)($j['goles'] ?? 0),
                            ':asistencias'          => (int)($j['asistencias'] ?? 0),
                            ':tarjetas_amarillas'   => (int)($j['tarjetas_amarillas'] ?? 0),
                            ':tarjetas_rojas'       => (int)($j['tarjetas_rojas'] ?? 0),
                            ':mvp_count'            => (int)($j['mvp_count'] ?? 0)
                        ]);
                    }
                }

// ============================================================
// 3. ELIMINAR JUGADOR
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion']) && $_POST['accion'] === 'eliminar_jugador') {
    $id_jugador = (int)($_POST['id_jugador'] ?? 0);
    if ($id_jugador > 0) {
        try {


// ============================================================
// 4. AGREGAR / ACTUALIZAR JUGADOR (desde modal en ficha.php)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion'])) {
    $accion_player = $_POST['accion'];

    // AGREGAR JUGADOR
    if ($accion_player === 'agregar_jugador') {
        $equipo_id = (int)($_POST['equipo_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $posicion = trim($_POST['posicion'] ?? '');
        $goles = (int)($_POST['goles'] ?? 0);
        $asistencias = (int)($_POST['asistencias'] ?? 0);
        $tarjetas_amarillas = (int)($_POST['tarjetas_amarillas'] ?? 0);
        $tarjetas_rojas = (int)($_POST['tarjetas_rojas'] ?? 0);
        $mvp_count = (int)($_POST['mvp_count'] ?? 0);

        if ($equipo_id > 0 && $nombre !== '' && $posicion !== '') {
            try {
                $sql = "INSERT INTO jugadores
                    (equipo_id, nombre, posicion, goles, asistencias, tarjetas_amarillas, tarjetas_rojas, mvp_count)
                    VALUES (:equipo_id, :nombre, :posicion, :goles, :asistencias, :tarjetas_amarillas, :tarjetas_rojas, :mvp_count)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':equipo_id' => $equipo_id,
                    ':nombre' => $nombre,
                    ':posicion' => $posicion,
                    ':goles' => $goles,
                    ':asistencias' => $asistencias,
                    ':tarjetas_amarillas' => $tarjetas_amarillas,
                    ':tarjetas_rojas' => $tarjetas_rojas,
                    ':mvp_count' => $mvp_count
                ]);
            } catch (PDOException $e) {
                die("Error al guardar jugador: " . $e->getMessage());
            }
        }
    }
    // ACTUALIZAR JUGADOR
    elseif ($accion_player === 'editar_jugador') {
        $id_jugador = (int)($_POST['id_jugador'] ?? 0);
        $equipo_id = (int)($_POST['equipo_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $posicion = trim($_POST['posicion'] ?? '');
        $goles = (int)($_POST['goles'] ?? 0);
        $asistencias = (int)($_POST['asistencias'] ?? 0);
        $tarjetas_amarillas = (int)($_POST['tarjetas_amarillas'] ?? 0);
        $tarjetas_rojas = (int)($_POST['tarjetas_rojas'] ?? 0);
        $mvp_count = (int)($_POST['mvp_count'] ?? 0);

        if ($id_jugador > 0 && $equipo_id > 0 && $nombre !== '' && $posicion !== '') {
            try {
                $sql = "UPDATE jugadores SET
                    nombre = :nombre, posicion = :posicion, goles = :goles,
                    asistencias = :asistencias, tarjetas_amarillas = :tarjetas_amarillas,
                    tarjetas_rojas = :tarjetas_rojas, mvp_count = :mvp_count
                    WHERE id = :id";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':id' => $id_jugador,
                    ':nombre' => $nombre,
                    ':posicion' => $posicion,
                    ':goles' => $goles,
                    ':asistencias' => $asistencias,
                    ':tarjetas_amarillas' => $tarjetas_amarillas,
                    ':tarjetas_rojas' => $tarjetas_rojas,
                    ':mvp_count' => $mvp_count
                ]);
            } catch (PDOException $e) {
                die("Error al actualizar jugador: " . $e->getMessage());
            }
        }
    }
}

            $conexion->exec("DELETE FROM jugadores WHERE id = :id");
            header("Location: ver.php");
            exit;
        } catch (PDOException $e) {
            die("Error al eliminar jugador: " . $e->getMessage());
        }
    }
}

// Si llegamos aquí sin procesar nada, redirigir
header("Location: ver.php");
exit;

            }

            header("Location: ver.php?mensaje=Equipo+actualizado+correctamente");
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar el equipo: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// Si llegamos aquí sin procesar nada, redirigir
header("Location: ver.php");
exit;
