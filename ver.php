<?php
require_once 'conexion.php';

// Habilitar reporte de errores
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtener todos los equipos con sus estadÃ­sticas
try {
    $sql = "SELECT e.*,
            (SELECT COUNT(*) FROM jugadores j WHERE j.equipo_id = e.id) as total_jugadores,
            (SELECT COALESCE(SUM(j.goles),0) FROM jugadores j WHERE j.equipo_id = e.id) as total_goles,
            (SELECT COALESCE(SUM(j.asistencias),0) FROM jugadores j WHERE j.equipo_id = e.id) as total_asistencias,
            (SELECT COALESCE(SUM(j.tarjetas_amarillas),0) FROM jugadores j WHERE j.equipo_id = e.id) as total_amarillas,
            (SELECT COALESCE(SUM(j.tarjetas_rojas),0) FROM jugadores j WHERE j.equipo_id = e.id) as total_rojas,
            (SELECT COALESCE(SUM(j.mvp_count),0) FROM jugadores j WHERE j.equipo_id = e.id) as total_mvp
            FROM equipos e
            ORDER BY e.nombre";

    $stmt = $conexion->query($sql);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al consultar equipos: " . $e->getMessage());
}

// Procesar eliminaciÃ³n
if (isset($_GET['eliminar'])) {
    $idEliminar = (int)$_GET['eliminar'];
    try {
        $stmtDelJ = $conexion->prepare("DELETE FROM jugadores WHERE equipo_id = :id");
        $stmtDelJ->execute([':id' => $idEliminar]);
        $stmtDel = $conexion->prepare("DELETE FROM equipos WHERE id = :id");
        $stmtDel->execute([':id' => $idEliminar]);
        header("Location: ver.php");
        exit;
    } catch (PDOException $e) {
        die("Error al eliminar equipo: " . $e->getMessage());
    }
}

// Procesar guardado desde crear.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion']) && $_POST['accion'] === 'crear_equipo') {
    $nombre = trim($_POST['nombre'] ?? '');
    $liga   = trim($_POST['liga'] ?? '');
    $anio   = trim($_POST['anio'] ?? '');

    if ($nombre === '' || $liga === '') {
        die("Por favor, completa nombre y liga.");
    }

    if ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || (int)$anio < 1800 || (int)$anio > 2100)) {
        die("El año del club debe estar entre 1800 y 2100.");
    }

    try {
        $sql = "INSERT INTO equipos (nombre, liga, anio, escudo) VALUES (:nombre, :liga, :anio, :escudo)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':liga'   => $liga,
            ':anio'   => $anio,
            ':escudo' => 'default.png'
        ]);

        header("Location: ver.php");
        exit;
    } catch (PDOException $e) {
        die("Error al guardar equipo: " . $e->getMessage());
    }
}

// Procesar actualizaciÃ³n
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_editar'])) {
    $id = (int)$_POST['accion_editar'];
    $nombre = trim($_POST['nombre'] ?? '');
    $liga   = trim($_POST['liga'] ?? '');
    $anio   = trim($_POST['anio'] ?? '');

    if ($nombre === '') {
        die("Por favor, completa el nombre.");
    }

    if ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || (int)$anio < 1800 || (int)$anio > 2100)) {
        die("El año del club debe estar entre 1800 y 2100.");
    }

    try {
        $sql = "UPDATE equipos SET nombre = :nombre, liga = :liga, anio = :anio WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':liga'   => $liga,
            ':anio'   => $anio,
            ':id'     => $id
        ]);

        // Actualizar jugadores
        if (!empty($_POST['jugadores'])) {
            $stmtDelJ = $conexion->prepare("DELETE FROM jugadores WHERE equipo_id = :id");
            $stmtDelJ->execute([':id' => $id]);
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

        header("Location: ver.php");
        exit;
    } catch (PDOException $e) {
        die("Error al actualizar equipo: " . $e->getMessage());
    }
}

// Crear nuevo partido (panel «Crear partido nuevo» de esta página)
$error = '';
$abrir_crear = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion']) && $_POST['accion'] === 'crear_partido') {
    $abrir_crear = true;
    $local = (int)($_POST['equipo_local_id'] ?? 0);
    $visitante = (int)($_POST['equipo_visitante_id'] ?? 0);
    $fecha = trim($_POST['fecha_hora'] ?? '');
    $estadio = trim($_POST['estadio'] ?? '');
    $precio = (float)($_POST['precio_entrada'] ?? 0);

    if ($local <= 0 || $visitante <= 0) {
        $error = 'Elige los dos equipos del partido.';
    } elseif ($local === $visitante) {
        $error = 'El equipo local y el visitante no pueden ser el mismo.';
    } elseif ($fecha === '' || $estadio === '') {
        $error = 'Completa la fecha y el estadio del partido.';
    } elseif ($precio < 0) {
        $error = 'El precio no puede ser negativo.';
    } else {
        try {
            $stmt = $conexion->prepare("INSERT INTO partidos (equipo_local_id, equipo_visitante_id, fecha_hora, estadio, precio_entrada) VALUES (:local, :visitante, :fecha, :estadio, :precio)");
            $stmt->execute([':local' => $local, ':visitante' => $visitante, ':fecha' => $fecha, ':estadio' => $estadio, ':precio' => $precio]);
            header("Location: ver.php?mensaje=" . urlencode("Partido creado correctamente."));
            exit;
        } catch (PDOException $e) {
            $error = 'Error al crear el partido: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos - GoolPass</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/app_common.css">
    <link rel="stylesheet" href="css/torneo.css">
    <link rel="stylesheet" href="css/animaciones.css">

    <script src="js/animaciones.js"></script>
    <style>
        .ver-wrap { max-width: 1280px; margin: 0 auto; padding: 0 28px 70px; }
        .ver-hero { text-align:center; padding: 34px 0 6px; }
        .ver-hero h1 { font-family: var(--fuente); font-weight:900; font-size: clamp(30px,4.4vw,46px); margin:12px 0 8px; letter-spacing:-1px; color:#fff; }
        .ver-hero h1 .grad { background: linear-gradient(135deg, var(--naranja), var(--dorado)); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .ver-hero p { color: var(--tenue); margin:0 auto; max-width:640px; font-size:15px; line-height:1.6; }
        .ver-chip { display:inline-flex; align-items:center; gap:9px; padding:8px 16px; border-radius:999px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); font-size:11.5px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--tenue); }
        .ver-chip i { width:8px; height:8px; border-radius:50%; background:#35d07f; box-shadow:0 0 10px 2px rgba(53,208,127,.7); }
        .mini-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin:24px 0 6px; }
        .mini-stat { background:linear-gradient(160deg, rgba(255,255,255,.07), rgba(255,255,255,.02)); border:1px solid var(--panel-borde); border-radius:16px; padding:16px 12px; text-align:center; }
        .mini-stat b { display:block; font-family:var(--fuente); font-size:27px; font-weight:900; color:#fff; }
        .mini-stat span { color:var(--tenue); font-size:11px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; }
        .toolbar { display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between; margin:20px 0 22px; }
        .searchbox { flex:1; min-width:230px; display:flex; align-items:center; gap:10px; background:rgba(0,0,0,.35); border:1px solid var(--panel-borde); border-radius:12px; padding:0 14px; }
        .searchbox input { flex:1; background:none; border:none; outline:none; color:#fff; font-size:14px; padding:13px 0; font-family:inherit; }
        .ver-barra { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 28px; background: rgba(11,11,15,.82); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,.08); }
        .ver-marca { display:flex; align-items:center; gap:10px; font-family: var(--fuente); font-weight: 900; font-size: 17px; color:#fff; text-decoration:none; }
        .ver-marca em { font-style:normal; background: linear-gradient(135deg, var(--naranja-2), var(--dorado)); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .ver-marca-ico { width:34px; height:34px; display:grid; place-items:center; border-radius:10px; background: linear-gradient(135deg, var(--naranja), var(--naranja-2)); box-shadow: 0 8px 22px rgba(255,69,0,.4); }
        .ver-nav { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .ver-nav a { color: var(--tenue); font-size:13.5px; font-weight:600; padding:9px 14px; border-radius:999px; border:1px solid transparent; text-decoration:none; transition:.2s; }
        .ver-nav a:hover { color:#fff; background: rgba(255,255,255,.07); }
        .ver-nav a.activo { color:#fff; background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.12); }
        .ver-nav a.cta { color:#fff; background: linear-gradient(135deg, var(--naranja), var(--naranja-2)); font-weight:700; box-shadow: 0 8px 22px rgba(255,69,0,.35); }
        .ver-fondo { position: fixed; inset: 0; z-index: -1; pointer-events: none;
            background: radial-gradient(1100px 600px at 12% -10%, #2a1200 0%, transparent 60%), radial-gradient(900px 600px at 100% 0%, #0d1b33 0%, transparent 55%), linear-gradient(180deg, #0d0d12 0%, #0b0b0f 100%); }
        .ver-fondo::after { content:""; position:absolute; inset:0;
            background-image: linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse 90% 70% at 50% 30%, #000 25%, transparent 75%); }
        .grid-equipos { display:grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap:20px; margin-bottom:30px; }

        .tarjeta-equipo { background: linear-gradient(165deg, rgba(255,255,255,.07), rgba(255,255,255,.02)); border:1px solid var(--panel-borde); border-radius:18px; overflow:hidden; box-shadow: var(--sombra-s); transition: transform .25s, border-color .25s, box-shadow .25s; display:flex; flex-direction:column; }
        .tarjeta-equipo:hover { transform: translateY(-6px); border-color: rgba(255,122,51,.45); box-shadow: 0 18px 44px rgba(0,0,0,.5); }

        .tarjeta-equipo:hover {
            border-color: #FF6B6B;
        }

        .tarjeta-equipo .img-wrapper { width:100%; height:190px; overflow:hidden; position:relative; background: radial-gradient(circle at 50% 20%, rgba(255,122,51,.22), rgba(0,0,0,.45)); display:flex; align-items:center; justify-content:center; }
        .tarjeta-equipo .img-wrapper img { width:100%; height:100%; object-fit:cover; }
        .avatar-fallback { font-family:var(--fuente); font-size:64px; font-weight:900; color:rgba(255,255,255,.9); text-shadow:0 4px 18px rgba(0,0,0,.5); }
        .liga-flotante { position:absolute; left:12px; top:12px; background:rgba(0,0,0,.55); border:1px solid rgba(255,255,255,.16); padding:5px 11px; border-radius:999px; font-size:11.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:#ffd24a; backdrop-filter:blur(6px); }
        .tarjeta-equipo .cuerpo { padding:18px 18px 16px; display:flex; flex-direction:column; gap:12px; flex:1; }
        .tarjeta-equipo .info h3 { margin:0; font-family:var(--fuente); font-size:20px; font-weight:800; letter-spacing:-.3px; color:#fff; }
        .tarjeta-vacia { grid-column:1/-1; text-align:center; padding:44px 20px; border:1px dashed rgba(255,255,255,.18); border-radius:18px; background:rgba(255,255,255,.03); color:var(--tenue); }
        .tarjeta-vacia a { color:#ff7a33; font-weight:700; }

        .tarjeta-equipo .info .liga {
            display: inline-block;
            background: #2d2d44;
            color: #aaa;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .tarjeta-equipo .stats { display:flex; flex-wrap:wrap; gap:7px; }

        .tarjeta-equipo .stats span { background:rgba(0,0,0,.32); border:1px solid rgba(255,255,255,.09); padding:6px 10px; border-radius:999px; font-size:12px; color:#d7d7e3; font-weight:600; }

        .tarjeta-equipo .stats .num { color:#ffb37a; border-color:rgba(255,122,51,.3); }

        .tarjeta-equipo .acciones { display:flex; gap:8px; flex-wrap:wrap; margin-top:2px; }

        .btn-accion { padding:9px 14px; border:1px solid rgba(255,255,255,.12); border-radius:10px; font-size:12.5px; font-weight:700; text-decoration:none; cursor:pointer; transition:.2s; color:#fff; background:rgba(255,255,255,.06); }
        .btn-accion:hover { transform:translateY(-1px); background:rgba(255,255,255,.1); }
        .btn-ficha { background:linear-gradient(135deg, var(--azul), var(--azul-2)); border-color:transparent; }
        .btn-editar2 { background:linear-gradient(135deg, var(--verde), var(--verde-2)); border-color:transparent; }
        .btn-eliminar { background:linear-gradient(135deg, #b71c1c, #e53935); border-color:transparent; }

        /* Aviso flotante de éxito (solo se muestra con ?mensaje= en la URL) */
        .popup {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(20, 34, 27, .96);
            border: 1px solid rgba(53,208,127,.45);
            color: #9df3c6;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,.5);
            z-index: 2000;
            font-size: 14px;
            font-weight: 600;
        }

        @media (max-width:700px){ .mini-stats{grid-template-columns:repeat(2,1fr);} .ver-barra{padding:12px 16px;} .ver-wrap{padding:0 16px 50px;} }

        /* Panel «Crear partido nuevo» (debajo de los equipos) */
        .alerta-error { background:#3a1c1c; border:1px solid #f44336; color:#f3c1c1; padding:12px 14px; border-radius:12px; margin-bottom:14px; font-size:14px; }
        .panel-crear { background: linear-gradient(160deg, rgba(255,255,255,.07), rgba(255,255,255,.02)); border:1px solid var(--panel-borde); border-radius:18px; padding:16px 20px; margin:0 0 30px; }
        .panel-crear summary { cursor:pointer; color:#fff; font-family:var(--fuente); font-weight:800; font-size:17px; letter-spacing:-.2px; }
        .panel-crear .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-top:16px; }
        .panel-crear .form-grid label { color:#aaa; font-size:13px; display:flex; flex-direction:column; gap:6px; }
        .panel-crear .form-grid input, .panel-crear .form-grid select { padding:10px; border-radius:10px; border:1px solid rgba(255,255,255,.14); background:rgba(0,0,0,.35); color:#fff; font-family:inherit; }
        .panel-crear .form-grid input:focus, .panel-crear .form-grid select:focus { outline:none; border-color:rgba(255,122,51,.65); }
    </style>
</head>
<body>
<div class="ver-fondo" aria-hidden="true"></div>
<header class="ver-barra">
    <a class="ver-marca" href="index.php"><span class="ver-marca-ico">🏆</span><span>Gool<em>Pass</em></span></a>
    <nav class="ver-nav">
        <a href="ver.php" class="activo">Equipos</a>
        <a href="torneo.php">Ruleta</a>
        <a href="partidos.php">Partidos</a>
        <a href="crear.php" class="cta">+ Nuevo</a>
    </nav>
</header>

<div class="ver-wrap">
<div class="ver-hero">
    <span class="ver-chip"><i></i> Temporada activa</span>
    <h1>Equipos <span class="grad">registrados</span></h1>
    <p>Explora todos los clubes, sus ligas y el resumen de su plantilla. Busca, abre la ficha o edita en un clic.</p>
</div>

<?php
$totalJug = 0; $totalGoles = 0;
foreach ($equipos as $x) { $totalJug += (int)$x['total_jugadores']; $totalGoles += (int)$x['total_goles']; }
?>
<div class="mini-stats">
    <div class="mini-stat"><b><?= count($equipos) ?></b><span>Equipos</span></div>
    <div class="mini-stat"><b><?= $totalJug ?></b><span>Jugadores</span></div>
    <div class="mini-stat"><b><?= $totalGoles ?></b><span>Goles</span></div>
    <div class="mini-stat"><b><?= count(array_unique(array_column($equipos,'liga'))) ?></b><span>Ligas</span></div>
</div>

<div class="toolbar">
    <div class="searchbox"><span>🔎</span><input id="buscador" type="text" placeholder="Buscar equipo o liga..."></div>
    <a href="crear.php" class="btn btn-primario"><span class="btn-ico">➕</span><span>Nuevo equipo</span></a>
</div>

<div class="grid-equipos">
    <?php if (count($equipos) === 0): ?>
        <div class="tarjeta-vacia">
            <div style="font-size:44px;">&#127963;&#65039;</div>
            <p style="margin:10px 0 14px;">No hay equipos registrados.<br>
            <a href="crear.php">Crear tu primer equipo</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($equipos as $eq):
            $inicial = mb_strtoupper(mb_substr(trim($eq['nombre'] ?: '?'), 0, 1, 'UTF-8'));
        ?>
        <div class="tarjeta-equipo" data-nombre="<?= strtolower(htmlspecialchars($eq['nombre'] . ' ' . $eq['liga'] . ' ' . ($eq['anio'] ?? ''))) ?>">
            <div class="img-wrapper">
                <?php if (!empty($eq['escudo']) && file_exists('uploads/' . $eq['escudo'])): ?>
                    <img src="uploads/<?= htmlspecialchars($eq['escudo']) ?>" alt="Escudo" loading="lazy">
                <?php else: ?>
                    <div class="avatar-fallback"><?= htmlspecialchars($inicial) ?></div>
                <?php endif; ?>
                <span class="liga-flotante"><?= htmlspecialchars($eq['liga']) ?><?= trim($eq['anio'] ?? '') !== '' ? ' &middot; ' . htmlspecialchars($eq['anio']) : '' ?></span>
            </div>
            <div class="cuerpo">
            <div class="info">
                <h3><?= htmlspecialchars($eq['nombre']) ?></h3>
            </div>
            <div class="stats">
                <span class="num">&#128101; <?= (int)$eq['total_jugadores'] ?> jugadores</span>
                <span>&#9917; <?= (int)$eq['total_goles'] ?> goles</span>
                <span>&#127344; <?= (int)$eq['total_asistencias'] ?> asistencias</span>
                <span>&#129001; <?= (int)$eq['total_amarillas'] ?> amarillas</span>
                <span>&#129000; <?= (int)$eq['total_rojas'] ?> rojas</span>
                <span>&#127941;… <?= (int)$eq['total_mvp'] ?> MVP</span>
            </div>
            </div>
            <div class="acciones">
                <a href="ficha.php?id=<?= $eq['id'] ?>" class="btn-accion btn-ficha">&#128203; Ver ficha</a>
                <a href="editar.php?id=<?= $eq['id'] ?>" class="btn-accion btn-editar2">&#9999;&#65039; Editar</a>
                <a href="ver.php?eliminar=<?= $eq['id'] ?>" class="btn-accion btn-eliminar" onclick="return confirm('&#191;Est&#225;s seguro de eliminar este equipo?')">&#128465;&#65039; Eliminar</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

    <?php if ($error !== ''): ?>
        <div class="alerta-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <details class="panel-crear" <?= $abrir_crear ? 'open' : '' ?>>
        <summary>➕ Crear partido nuevo</summary>
        <form method="post" style="margin-top:6px;">
            <input type="hidden" name="accion" value="crear_partido">
            <div class="form-grid">
                <label>Equipo local
                    <select name="equipo_local_id" required>
                        <option value="">Seleccionar…</option>
                        <?php foreach ($equipos as $eq): ?>
                            <option value="<?= (int)$eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?> (<?= htmlspecialchars($eq['liga']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Equipo visitante
                    <select name="equipo_visitante_id" required>
                        <option value="">Seleccionar…</option>
                        <?php foreach ($equipos as $eq): ?>
                            <option value="<?= (int)$eq['id'] ?>"><?= htmlspecialchars($eq['nombre']) ?> (<?= htmlspecialchars($eq['liga']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Fecha y hora
                    <input type="datetime-local" name="fecha_hora" required>
                </label>
                <label>Estadio
                    <input type="text" name="estadio" placeholder="Ej. Estadio Central" required>
                </label>
                <label>Precio de entrada
                    <input type="number" name="precio_entrada" min="0" step="0.01" placeholder="Ej. 25.00" required>
                </label>
            </div>
            <div style="margin-top:14px;">
                <button type="submit" class="btn btn-primario"><span class="btn-ico">💾</span><span>Guardar partido</span></button>
            </div>
        </form>
    </details>
</div>

<div id="sinResultados" style="display:none;" class="tarjeta-vacia">Sin resultados para esa b&uacute;squeda.</div>
<div id="popup" class="popup">&#10003; Equipo guardado correctamente</div>

<script>
    const caja = document.getElementById('buscador');
    if (caja) {
        caja.addEventListener('input', () => {
            const q = caja.value.trim().toLowerCase();
            let visibles = 0;
            document.querySelectorAll('.tarjeta-equipo').forEach(c => {
                const ok = !q || (c.dataset.nombre || '').includes(q);
                c.style.display = ok ? '' : 'none';
                if (ok) visibles++;
            });
            document.getElementById('sinResultados').style.display = visibles ? 'none' : 'block';
        });
    }
    const params = new URLSearchParams(window.location.search);
    const msg = params.get('mensaje');
    if (msg) {
        const popup = document.getElementById('popup');
        popup.textContent = '✓ ' + msg;
        popup.style.display = 'block';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 3000);
    }
</script>

</body>
</html>
