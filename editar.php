<?php
require_once 'conexion.php';

$mensaje = "";
$tipo_mensaje = "info";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ver.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos del equipo
try {
    $stmt = $conexion->prepare("SELECT * FROM equipos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) {
        die("Equipo no encontrado.");
    }

    // Obtener jugadores
    $stmt = $conexion->prepare("SELECT * FROM jugadores WHERE equipo_id = :id ORDER BY nombre");
    $stmt->execute([':id' => $id]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al consultar equipo: " . $e->getMessage());
}

// Procesar actualización de equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_editar'])) {
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

            // Reconstruir jugadores
            if (!empty($_POST['jugadores'])) {
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
            }

            $mensaje = "Equipo actualizado correctamente.";
            $tipo_mensaje = "exito";
            header("Location: ver.php?mensaje=" . urlencode($mensaje));
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}
?>
<div class="header">
    <a href="ver.php" style="color:#888;text-decoration:none;">&#8592; Volver a la lista</a>
    <h1>&#9888; Editar Equipo</h1>
</div>

<div class="container">
    <h2>Datos del Equipo</h2>
    <form method="POST">
        <input type="hidden" name="accion_editar" value="<?= $id ?>">
        <div class="form-grid">
            <div class="form-grupo">
                <label for="nombre">Nombre del Equipo *</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($equipo['nombre']) ?>" required>
            </div>
            <div class="form-grupo">
                <label for="anio">Año del Club</label>
                <input type="number" id="anio" name="anio" min="1800" max="2100" placeholder="Ej. 1902"
                       value="<?= htmlspecialchars($equipo['anio'] ?? '') ?>">
            </div>
            <div class="form-grupo">
                <label for="liga">Liga *</label>
                <select id="liga" name="liga" required>
                    <option value="Futsal" <?= ($equipo['liga'] == 'Futsal') ? 'selected' : '' ?>>Futsal</option>
                    <option value="7-as" <?= ($equipo['liga'] == '7-as') ? 'selected' : '' ?>>7-as</option>

    <?php if (count($jugadores) === 0): ?>
        <p style="color:#888;">No hay jugadores registrados.</p>
    <?php else: ?>
        <table class="tabla-jugadores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Posición</th>
                    <th>Goles</th>
                    <th>Asistencias</th>
                    <th>&#24163;/Roja</th>
                    <th>MVP</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jugadores as $idx => $j): ?>
                <tr>
                    <td style="font-weight:600;"><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($j['nombre']) ?></td>
                    <td><?= htmlspecialchars($j['posicion']) ?></td>
                    <td><?= (int)$j['goles'] ?></td>
                    <td><?= (int)$j['asistencias'] ?></td>

<script>
    function eliminarJugador(id) {
        if (confirm('&#10060; ¿Eliminar este jugador?')) {
            fetch('guardar_ficha.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'accion=eliminar_jugador&id_jugador=' + id
            }).then(() => location.reload());
        }
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>
</body>
</html>

                    <td><?= (int)$j['tarjetas_amarillas'] ?>/<?= (int)$j['tarjetas_rojas'] ?></td>
                    <td><?= (int)$j['mvp_count'] ?></td>
                    <td>
                        <button type="button" class="btn-eliminar-jugador" onclick="eliminarJugador(<?= $j['id'] ?>)">&#128465; Eliminar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <button type="button" class="btn-guardar" onclick="abrirModalAgregar()" style="margin-top:10px;">
        &#128194; Agregar Jugador
    </button>
</div>

<!-- Modal: Agregar Jugador -->
<div id="modalAgregar" class="modal">
    <div class="modal-contenido">
        <span class="modal-cerrar" onclick="cerrarModal('modalAgregar')">&#10005;</span>
        <h2>&#128194; Nuevo Jugador</h2>
        <form id="formAgregarJugador" method="POST">
            <input type="hidden" name="equipo_id" value="<?= $id ?>">
            <div class="form-grupo">
                <label for="nombre_j">Nombre *</label>
                <input type="text" id="nombre_j" name="nombre" required>
            </div>
            <div class="form-grupo">
                <label for="posicion_j">Posición *</label>
                <select id="posicion_j" name="posicion" required>
                    <option value="">Selecciona posición</option>
                    <option value="Portero">Portero</option>
                    <option value="Defensas">Defensas</option>
                    <option value="Centrocampistas">Centrocampistas</option>
                    <option value="Delanteros">Delanteros</option>
                </select>
            </div>
            <div class="form-grupo">
                <label for="goles_j">Goles</label>
                <input type="number" id="goles_j" name="goles" value="0" min="0">
            </div>
            <div class="form-grupo">
                <label for="asistencias_j">Asistencias</label>
                <input type="number" id="asistencias_j" name="asistencias" value="0" min="0">
            </div>
            <div class="form-grupo">
                <label for="amarillas_j">Tarjetas Amarillas</label>
                <input type="number" id="amarillas_j" name="tarjetas_amarillas" value="0" min="0">
            </div>
            <div class="form-grupo">
                <label for="rojas_j">Tarjetas Rojas</label>
                <input type="number" id="rojas_j" name="tarjetas_rojas" value="0" min="0">
            </div>
            <div class="form-grupo">
                <label for="mvp_j">MVP</label>
                <input type="number" id="mvp_j" name="mvp_count" value="0" min="0">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn-guardar" onclick="cerrarModal('modalAgregar')" style="background:#888;">Cancelar</button>
                <button type="submit" class="btn-guardar" style="background:#4CAF50;">&#10132; Guardar</button>
            </div>
        </form>
    </div>
</div>

<div id="popup" class="popup"></div>

<script>
    function abrirModalAgregar() {
        document.getElementById('modalAgregar').style.display = 'block';
        document.getElementById('formAgregarJugador').reset();
    }

    function cerrarModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>
</body>
</html>

                    <option value="11-as" <?= ($equipo['liga'] == '11-as') ? 'selected' : '' ?>>11-as</option>
                    <option value="Beach" <?= ($equipo['liga'] == 'Beach') ? 'selected' : '' ?>>Beach Soccer</option>
                    <option value="Premitad" <?= ($equipo['liga'] == 'Premitad') ? 'selected' : '' ?>>Premitida</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn-guardar">&#10132; Guardar Equipo</button>
    </form>

    <h2>Plantilla de Jugadores</h2>


            $mensaje = "Equipo actualizado correctamente.";
            $tipo_mensaje = "exito";
            header("Location: ver.php?mensaje=" . urlencode($mensaje));
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}
?>
