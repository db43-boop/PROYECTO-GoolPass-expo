<?php
require_once 'conexion.php';

$mensaje = '';
$error = '';

// Simular pago de entrada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion']) && $_POST['accion'] === 'pagar_entrada') {
    $partidoId = (int)($_POST['partido_id'] ?? 0);
    $nombre = trim($_POST['nombre_comprador'] ?? '');
    $tarjeta = preg_replace('/\D/', '', $_POST['tarjeta'] ?? '');

    if ($partidoId <= 0) {
        $error = 'Partido no válido.';
    } elseif ($nombre === '') {
        $error = 'Escribe el nombre del comprador.';
    } elseif (strlen($tarjeta) < 12) {
        $error = 'La tarjeta debe tener al menos 12 dígitos (pago simulado).';
    } else {
        try {
            $codigo = 'QR-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $conexion->prepare("INSERT INTO entradas (partido_id, estado_pago, codigo_qr) VALUES (:partido, 'Pagado', :codigo)");
            $stmt->execute([':partido' => $partidoId, ':codigo' => $codigo]);
            $mensaje = 'Pago aceptado. Tu código de entrada es ' . $codigo . '.';
        } catch (PDOException $e) {
            $error = 'Error al procesar el pago: ' . $e->getMessage();
        }
    }
}

try {
    $partidos = $conexion->query(
        "SELECT p.*, l.nombre AS local_nombre, v.nombre AS visitante_nombre,
            (SELECT COUNT(*) FROM entradas e WHERE e.partido_id = p.id AND e.estado_pago = 'Pagado') AS entradas_vendidas
         FROM partidos p
         JOIN equipos l ON l.id = p.equipo_local_id
         JOIN equipos v ON v.id = p.equipo_visitante_id
         ORDER BY p.fecha_hora ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $partidos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Partido - GoolPass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app_common.css">
    <link rel="stylesheet" href="css/torneo.css">
    <link rel="stylesheet" href="css/animaciones.css">
    <script src="js/animaciones.js"></script>
    <style>
        .grid-partidos { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin: 20px 0 30px; }
        .card-partido { background: #1e1e1e; border: 2px solid #333; border-radius: 12px; padding: 20px; transition: border-color .3s; }
        .card-partido:hover { border-color: #FF6B6B; }
        .card-partido h3 { margin: 0 0 8px; color: #fff; font-size: 18px; }
        .card-partido .meta { color: #aaa; font-size: 13px; margin: 4px 0; }
        .card-partido .precio { color: #FFD700; font-weight: 700; font-size: 20px; margin: 10px 0; }
        .form-panel { background: #1e1e1e; border: 2px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .form-panel h2 { color: #fff; margin-top: 0; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .form-grid label { color: #aaa; font-size: 13px; display: flex; flex-direction: column; gap: 6px; }
        .form-grid input, .form-grid select { padding: 10px; border-radius: 8px; border: 1px solid #444; background: #252538; color: #fff; }
        .alerta-ok { background: #1c3a24; border: 1px solid #4CAF50; color: #c8f0cf; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        .alerta-error { background: #3a1c1c; border: 1px solid #f44336; color: #f3c1c1; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        .modal-pago { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); align-items: center; justify-content: center; z-index: 50; padding: 16px; }
        .modal-pago.abierto { display: flex; }
        .modal-caja { background: #1e1e1e; border: 2px solid #FFD700; border-radius: 12px; padding: 24px; max-width: 420px; width: 100%; }
        .modal-caja h3 { color: #FFD700; margin-top: 0; }
        .modal-caja label { color: #aaa; font-size: 13px; display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
        .modal-caja input { padding: 10px; border-radius: 8px; border: 1px solid #444; background: #252538; color: #fff; }
        .modal-acciones { display: flex; gap: 10px; margin-top: 12px; }
    </style>
</head>
<body>

<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>

<header class="barra">
    <a class="marca" href="index.php">
        <span class="marca-icono">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                <path d="M5 3h14v3a7 7 0 0 1-5.2 6.8V16h3.2a1.8 1.8 0 0 1 0 3.6H7a1.8 1.8 0 0 1 0-3.6h3.2v-3.2A7 7 0 0 1 5 6V3Z"
                      fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="marca-txt">Gool<em>Pass</em></span>
    </a>
    <nav class="nav">
        <a href="ver.php">Equipos</a>
        <a href="torneo.php">Ruleta</a>
        <a href="partidos.php" class="activo">Partidos</a>
        <a class="nav-cta" href="crear.php">+ Nuevo</a>
    </nav>
</header>

<main class="envoltura">
    <h1 class="seccion-titulo">🎟️ Ver partido</h1>
    <p class="seccion-sub">Elige el partido que quieras ver y paga tu entrada para obtener el acceso.</p>

    <?php if ($mensaje !== ''): ?>
        <div class="alerta-ok">✅ <?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alerta-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="form-panel">
        <form onsubmit="return false;">
            <div class="form-grid">
                <label>Partido a ver
                    <select id="selPartido" onchange="cargarPartido()">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($partidos as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                    data-titulo="<?= htmlspecialchars($p['local_nombre'] . ' vs ' . $p['visitante_nombre'], ENT_QUOTES) ?>"
                                    data-fecha="<?= htmlspecialchars($p['fecha_hora'], ENT_QUOTES) ?>"
                                    data-estadio="<?= htmlspecialchars($p['estadio'], ENT_QUOTES) ?>"
                                    data-precio="<?= htmlspecialchars((string)$p['precio_entrada'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($p['local_nombre'] . ' vs ' . $p['visitante_nombre']) ?> · <?= htmlspecialchars($p['fecha_hora']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Fecha y hora
                    <input type="datetime-local" id="vFecha" readonly>
                </label>
                <label>Estadio
                    <input type="text" id="vEstadio" readonly>
                </label>
                <label>Precio de entrada
                    <input type="number" id="vPrecio" min="0" step="0.01" readonly>
                </label>
            </div>
            <div style="margin-top:14px;">
                <button type="button" class="btn btn-primario" onclick="pagarSeleccionado()">
                    <span class="btn-ico">💳</span><span>Pagar entrada del partido elegido</span>
                </button>
            </div>
        </form>
    </div>

    <h2 class="seccion-titulo">📋 Partidos disponibles</h2>
    <?php if (count($partidos) === 0): ?>
        <div class="form-panel"><p style="color:#aaa;margin:0;">Aún no hay partidos. Créalos desde la página de Equipos.</p></div>
    <?php else: ?>
        <div class="grid-partidos">
            <?php foreach ($partidos as $p): ?>
                <div class="card-partido">
                    <h3><?= htmlspecialchars($p['local_nombre']) ?> vs <?= htmlspecialchars($p['visitante_nombre']) ?></h3>
                    <p class="meta">📅 <?= htmlspecialchars($p['fecha_hora']) ?></p>
                    <p class="meta">🏟️ <?= htmlspecialchars($p['estadio']) ?></p>
                    <p class="meta">🎫 Entradas vendidas: <?= (int)$p['entradas_vendidas'] ?></p>
                    <p class="precio">$<?= number_format((float)$p['precio_entrada'], 2) ?></p>
                    <button type="button" class="btn btn-primario btn-ancho"
                        onclick="abrirPago(<?= (int)$p['id'] ?>, '<?= htmlspecialchars($p['local_nombre'] . ' vs ' . $p['visitante_nombre'], ENT_QUOTES) ?>', <?= (float)$p['precio_entrada'] ?>)">
                        <span class="btn-ico">💳</span><span>Pagar para ver</span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<div id="modalPago" class="modal-pago" role="dialog" aria-modal="true">
    <div class="modal-caja">
        <h3>💳 Pagar entrada</h3>
        <p id="pagoDetalle" style="color:#fff;"></p>
        <form method="post">
            <input type="hidden" name="accion" value="pagar_entrada">
            <input type="hidden" name="partido_id" id="pagoPartidoId">
            <label>Nombre del comprador
                <input type="text" name="nombre_comprador" placeholder="Tu nombre" required>
            </label>
            <label>Número de tarjeta (simulado)
                <input type="text" name="tarjeta" inputmode="numeric" placeholder="4111 1111 1111 1111" required>
            </label>
            <div class="modal-acciones">
                <button type="submit" class="btn btn-primario"><span class="btn-ico">✅</span><span>Confirmar pago</span></button>
                <button type="button" class="btn btn-fantasma" onclick="cerrarPago()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirPago(id, titulo, precio) {
    document.getElementById('pagoPartidoId').value = id;
    document.getElementById('pagoDetalle').textContent = titulo + ' — Total: $' + Number(precio).toFixed(2);
    document.getElementById('modalPago').classList.add('abierto');
}
function cerrarPago() {
    document.getElementById('modalPago').classList.remove('abierto');
}
function cargarPartido() {
    const sel = document.getElementById('selPartido');
    const f = document.getElementById('vFecha');
    const e = document.getElementById('vEstadio');
    const p = document.getElementById('vPrecio');
    const opt = (sel && sel.selectedIndex > 0) ? sel.options[sel.selectedIndex] : null;
    if (!opt) { f.value = ''; e.value = ''; p.value = ''; return; }
    f.value = (opt.dataset.fecha || '').replace(' ', 'T').slice(0, 16);
    e.value = opt.dataset.estadio || '';
    p.value = opt.dataset.precio || '';
}
function pagarSeleccionado() {
    const sel = document.getElementById('selPartido');
    if (!sel || !sel.value) {
        alert('Primero elige un partido en el selector.');
        return;
    }
    const opt = sel.options[sel.selectedIndex];
    abrirPago(parseInt(sel.value, 10), opt.dataset.titulo || '', parseFloat(opt.dataset.precio) || 0);
}
</script>

</body>
</html>
