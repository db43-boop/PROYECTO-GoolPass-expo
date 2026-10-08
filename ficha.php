<?php
require_once 'conexion.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ver.php");
    exit;
}

$id = (int)$_GET['id'];

try {
    // Obtener datos del equipo
    $stmt = $conexion->prepare("SELECT * FROM equipos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) {
        die("Equipo no encontrado.");
    }

    // Obtener jugadores del equipo
    $stmt = $conexion->prepare("SELECT * FROM jugadores WHERE equipo_id = :id ORDER BY nombre");
    $stmt->execute([':id' => $id]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Totales del equipo
    $total_goles = array_sum(array_column($jugadores, 'goles'));
    $total_asistencias = array_sum(array_column($jugadores, 'asistencias'));
    $total_amarillas = array_sum(array_column($jugadores, 'tarjetas_amarillas'));
    $total_rojas = array_sum(array_column($jugadores, 'tarjetas_rojas'));
    $total_mvp = array_sum(array_column($jugadores, 'mvp_count'));
} catch (PDOException $e) {
    die("Error al consultar equipo: " . $e->getMessage());
}

$color = '#2196F3';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha del Equipo - <?= htmlspecialchars($equipo['nombre']) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/app_common.css">
    <link rel="stylesheet" href="css/estilos_fichas.css">
    <link rel="stylesheet" href="css/animaciones.css">

    <script src="js/animaciones.js"></script>

    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            margin: 0;
            padding: 20px;
        }

        .header-equipo {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #1e1e2e, #2d2d44);
            border-radius: 12px;
            border: 3px solid <?= $color ?>;
        }

        .header-equipo .info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .header-equipo h1 {
            margin: 0;
            font-size: 28px;
            color: <?= $color ?>;
            font-weight: 900;
        }

        .header-equipo .subtitulo {
            margin: 0;
            color: #aaa;
            font-size: 14px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: #1e1e1e;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #333;
            text-align: center;
        }

        .stats-card .numero {
            font-size: 32px;
            font-weight: 900;
            color: <?= $color ?>;
        }

        .stats-card .etiqueta {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
            color: <?= $color ?>;
        }

        .tabla-jugadores {
            width: 100%;
            border-collapse: collapse;
            background: #1e1e1e;
            border-radius: 8px;
            overflow: hidden;
        }

        .tabla-jugadores th,
        .tabla-jugadores td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .tabla-jugadores th {
            background: #2d2d44;
            color: #aaa;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .tabla-jugadores tr:hover td {
            background: #252538;
        }

        .posicion-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .posicion-Portero { background: #bbdefb; color: #0d47a1; }
        .posicion-Defensas { background: #c8e6c9; color: #1b5e20; }
        .posicion-Centrocampistas { background: #ffe082; color: #e65100; }
        .posicion-Delanteros { background: #ffcdd2; color: #b71c1c; }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            overflow: auto;
        }

        .modal-contenido {
            background: #1e1e1e;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            border: 3px solid <?= $color ?>;
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .modal-contenido h2 {
            margin-top: 0;
            color: <?= $color ?>;
            font-size: 22px;
        }

        .modal-cerrar {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #888;
        }

        .modal-cerrar:hover {
            color: #f44336;
        }

        .formulario-jugador {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .formulario-jugador input,
        .formulario-jugador select {
            width: 100%;
            padding: 10px;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 6px;
            color: #e0e0e0;
            font-size: 14px;
        }

        .btn-guardar-jugador {
            padding: 12px 20px;
            background: <?= $color ?>;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-guardar-jugador:hover {
            background: #1976D2;
        }

        .btn-volver {
            display: inline-block;
            margin-bottom: 20px;
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-volver:hover {
            color: #fff;
        }

        .popup {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            color: #fff;
            padding: 15px 20px;
            border-radius: 6px;
            z-index: 2000;
            font-size: 14px;
        }
    </style>
</head>
<body>

<a href="ver.php" class="btn-volver">&#8592; Volver a la lista de equipos</a>

<div class="header-equipo">
    <div class="info">
        <div style="display:flex;align-items:center;gap:15px;">
            <?php if (!empty($equipo['escudo'])): ?>
                <img src="uploads/<?= htmlspecialchars($equipo['escudo']) ?>" alt="Escudo de <?= htmlspecialchars($equipo['nombre']) ?>"
                     style="width:80px;height:80px;border-radius:8px;border:2px solid <?= $color ?>;object-fit:cover;">
            <?php endif; ?>
            <div>
                <h1><?= htmlspecialchars($equipo['nombre']) ?></h1>
                <p class="subtitulo"><?= htmlspecialchars($equipo['liga']) ?><?= trim($equipo['anio'] ?? '') !== '' ? ' &middot; ' . htmlspecialchars($equipo['anio']) : '' ?></p>
            </div>
        </div>
    </div>
</div>

<div class="cards-grid">
    <div class="stats-card">
        <div class="numero"><?= count($jugadores) ?></div>
        <div class="etiqueta">Jugadores</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_goles ?></div>
        <div class="etiqueta">Goles Totales</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_asistencias ?></div>
        <div class="etiqueta">Asistencias</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_amarillas ?></div>
        <div class="etiqueta">Tarjetas Amarillas</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_rojas ?></div>
        <div class="etiqueta">Tarjetas Rojas</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_mvp ?></div>
        <div class="etiqueta">MVP del Partido</div>
    </div>
</div>

<div class="section">
    <h2>Plantilla del Equipo</h2>

    <?php if (count($jugadores) === 0): ?>
        <div style="background:#1e1e1e;padding:30px;border-radius:8px;border:2px dashed #555;text-align:center;color:#888;">
            No hay jugadores registrados todavía.<br>
            <button type="button" class="btn-guardar-jugador" onclick="abrirModalAgregar()">&#128194; Agregar primer jugador</button>
        </div>
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
                    <td>
                        <?php
                        $posicion = $j['posicion'];
                        $clase = match($posicion) {
                            'Portero' => 'posicion-Portero',
                            'Defensas' => 'posicion-Defensas',
                            'Centrocampistas' => 'posicion-Centrocampistas',
                            'Delanteros' => 'posicion-Delanteros',
                            default => ''
                        };
                        ?>
                        <span class="posicion-badge <?= $clase ?>"><?= htmlspecialchars($posicion) ?></span>
                    </td>
                    <td><?= (int)$j['goles'] ?></td>
                    <td><?= (int)$j['asistencias'] ?></td>
                    <td><?= (int)$j['tarjetas_amarillas'] ?>/<?= (int)$j['tarjetas_rojas'] ?></td>
                    <td><?= (int)$j['mvp_count'] ?></td>
                    <td>
                        <button type="button" class="btn-editar" onclick="abrirModalEditar(<?= $j['id'] ?>, '<?= addslashes(htmlspecialchars($j['nombre'])) ?>', '<?= addslashes(htmlspecialchars($j['posicion'])) ?>')">
                            &#9998; Editar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div style="margin-top:15px;text-align:center;">
        <button type="button" class="btn-guardar-jugador" onclick="abrirModalAgregar()">
            &#128194; Agregar Jugador
        </button>
    </div>
</div>

<!-- MODAL: Agregar Jugador -->
<div id="modalAgregar" class="modal">
    <div class="modal-contenido">
        <span class="modal-cerrar" onclick="cerrarModal('modalAgregar')">&#10005;</span>
        <h2>&#128194; Nuevo Jugador</h2>

<form id="formAgregarJugador" method="POST" action="guardar_ficha.php">
            <input type="hidden" name="accion" value="agregar_jugador">
            <input type="hidden" name="equipo_id" value="<?= $id ?>">

            <div class="formulario-jugador">
                <input type="text" name="nombre" placeholder="Nombre del jugador *" required>
                <select name="posicion" required>
                    <option value="">Selecciona posición</option>
                    <option value="Portero">Portero</option>
                    <option value="Defensas">Defensas</option>
                    <option value="Centrocampistas">Centrocampistas</option>
                    <option value="Delanteros">Delanteros</option>
                </select>
                <input type="number" name="goles" placeholder="Goles (0)" value="0" min="0">
                <input type="number" name="asistencias" placeholder="Asistencias (0)" value="0" min="0">
                <input type="number" name="tarjetas_amarillas" placeholder="Tarjetas amarillas (0)" value="0" min="0">
                <input type="number" name="tarjetas_rojas" placeholder="Tarjetas rojas (0)" value="0" min="0">
                <input type="number" name="mvp_count" placeholder="Veces MVP (0)" value="0" min="0">
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalAgregar')">Cancelar</button>
                <button type="submit" class="btn-guardar-jugador">&#10132; Guardar Jugador</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Editar Jugador -->
<div id="modalEditar" class="modal">
    <div class="modal-contenido">
        <span class="modal-cerrar" onclick="cerrarModal('modalEditar')">&#10005;</span>
        <h2>&#128222; Editar Jugador</h2>
<form id="formEditarJugador" method="POST" action="guardar_ficha.php">
            <input type="hidden" name="accion" value="editar_jugador">
            <input type="hidden" name="id_jugador" id="input_id_jugador">
            <input type="hidden" name="equipo_id" value="<?= $id ?>">

            <div class="formulario-jugador">
                <input type="text" name="nombre" id="input_nombre_editar" required>
                <select name="posicion" id="input_posicion_editar" required>
                    <option value="Portero">Portero</option>
                    <option value="Defensas">Defensas</option>
                    <option value="Centrocampistas">Centrocampistas</option>
                    <option value="Delanteros">Delanteros</option>
                </select>
                <input type="number" name="goles" id="input_goles_editar" min="0">
                <input type="number" name="asistencias" id="input_asistencias_editar" min="0">
                <input type="number" name="tarjetas_amarillas" id="input_amarillas_editar" min="0">
                <input type="number" name="tarjetas_rojas" id="input_rojas_editar" min="0">
                <input type="number" name="mvp_count" id="input_mvp_editar" min="0">
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn-guardar-jugador">&#10132; Actualizar</button>
            </div>
        </form>
    </div>
</div>

<!--Popup de mensaje-->
<div id="popup" class="popup">&#10003; &#10003; ¡Operación exitosa!</div>

<script>
    // Abre el modal de agregar
    function abrirModalAgregar() {
        document.getElementById('modalAgregar').style.display = 'block';
        document.getElementById('formAgregarJugador').reset();
    }

    // Abre el modal de editar
    function abrirModalEditar(id, nombre, posicion) {
        document.getElementById('modalEditar').style.display = 'block';
        document.getElementById('input_id_jugador').value = id;
        document.getElementById('input_nombre_editar').value = nombre;
        document.getElementById('input_posicion_editar').value = posicion;
        document.getElementById('input_goles_editar').value = 0;
        document.getElementById('input_asistencias_editar').value = 0;
        document.getElementById('input_amarillas_editar').value = 0;
        document.getElementById('input_rojas_editar').value = 0;
        document.getElementById('input_mvp_editar').value = 0;
    }

    function cerrarModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera del contenido
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Mostrar popup
    const params = new URLSearchParams(window.location.search);
    const msg = params.get('mensaje');
    if (msg) {
        const popup = document.getElementById('popup');
        popup.style.display = 'block';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 3000);
    }
</script>
</body>
</html>
