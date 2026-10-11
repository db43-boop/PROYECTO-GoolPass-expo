<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos - GoolPass</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="public/css/app_common.css">
    <link rel="stylesheet" href="public/css/torneo.css">
    <link rel="stylesheet" href="public/css/animaciones.css">

    <script src="public/js/animaciones.js"></script>
    <script src="public/js/antireload.js"></script>
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
        .filtros-origen { display:flex; gap:10px; flex-wrap:wrap; margin:28px 0 10px; }
        .filtro-origen { flex:1; min-width:190px; display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px 20px; border:1px solid var(--panel-borde); border-radius:16px; background:rgba(255,255,255,.04); color:#e5e5e5; text-decoration:none; font-weight:800; transition:.2s; }
        .filtro-origen:hover { color:#fff; background:rgba(255,255,255,.08); border-color:rgba(255,122,51,.35); }
        .filtro-origen.activo { color:#fff; border-color:rgba(255,122,51,.65); background:linear-gradient(120deg, rgba(255,122,51,.22), rgba(255,210,74,.06)); }
        .filtro-origen .contador { padding:5px 11px; background:rgba(0,0,0,.32); border-radius:999px; font-size:13px; color:var(--dorado); }
        .filtro-indicacion { color:var(--tenue); font-size:13px; margin:4px 0 15px; }
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
    <link rel="stylesheet" href="public/css/cabecera.css">
</head>
<body>
<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>
<?php $paginaActiva = 'ver'; require GOOLPASS_ROOT . '/app/Views/partials/cabecera.php'; ?>

<div class="ver-wrap">
<div class="ver-hero">
    <span class="ver-chip"><i></i> Temporada activa</span>
    <h1>Equipos <span class="grad">registrados</span></h1>
    <p>Explora todos los clubes, sus ligas y el resumen de su plantilla. Busca equipos y abre sus fichas para consultar sus jugadores y estadísticas.</p>
</div>

<?php
$totalJug = 0; $totalGoles = 0;
foreach ($equipos as $x) { $totalJug += (int)$x['total_jugadores']; $totalGoles += (int)$x['total_goles']; }
?>
<nav class="filtros-origen" aria-label="Clasificación de equipos">
    <a class="filtro-origen <?= $origenSeleccionado === 'nacional' ? 'activo' : '' ?>" href="ver.php?origen=nacional" <?= $origenSeleccionado === 'nacional' ? 'aria-current="page"' : '' ?>>
        <span>🇵🇪 Equipos nacionales</span><span class="contador"><?= $conteos['nacional'] ?></span>
    </a>
    <a class="filtro-origen <?= $origenSeleccionado === 'internacional' ? 'activo' : '' ?>" href="ver.php?origen=internacional" <?= $origenSeleccionado === 'internacional' ? 'aria-current="page"' : '' ?>>
        <span>🌍 Equipos internacionales</span><span class="contador"><?= $conteos['internacional'] ?></span>
    </a>
</nav>
<p class="filtro-indicacion">Mostrando únicamente equipos <?= $origenSeleccionado === 'nacional' ? 'nacionales' : 'internacionales' ?>. Selecciona otra pestaña para cambiar de categoría.</p>
<div class="mini-stats">
    <div class="mini-stat"><b><?= count($equipos) ?></b><span>Equipos</span></div>
    <div class="mini-stat"><b><?= $totalJug ?></b><span>Jugadores</span></div>
    <div class="mini-stat"><b><?= $totalGoles ?></b><span>Goles</span></div>
    <div class="mini-stat"><b><?= count(array_unique(array_column($equipos,'liga'))) ?></b><span>Ligas</span></div>
</div>

<div class="toolbar">
    <div class="searchbox"><span>🔎</span><input id="buscador" type="text" placeholder="Buscar equipo o liga..."></div>
    <?php if (es_admin()): ?>
    <a href="crear.php" class="btn btn-primario"><span class="btn-ico">➕</span><span>Nuevo equipo</span></a>
    <?php endif; ?>
</div>

<div class="grid-equipos">
    <?php if (count($equipos) === 0): ?>
        <div class="tarjeta-vacia">
            <div style="font-size:44px;">&#127963;&#65039;</div>
            <p style="margin:10px 0 14px;">No hay equipos <?= $origenSeleccionado === 'nacional' ? 'nacionales' : 'internacionales' ?> registrados.<br>
            <?php if (es_admin()): ?><a href="crear.php">Crear tu primer equipo</a><?php endif; ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($equipos as $eq):
            $inicial = mb_strtoupper(mb_substr(trim($eq['nombre'] ?: '?'), 0, 1, 'UTF-8'));
        ?>
        <div class="tarjeta-equipo" data-nombre="<?= strtolower(htmlspecialchars($eq['nombre'] . ' ' . $eq['liga'] . ' ' . ($eq['anio'] ?? ''))) ?>">
            <div class="img-wrapper">
                <?php if (!empty($eq['escudo']) && is_file(GOOLPASS_UPLOADS_DIR . '/' . $eq['escudo'])): ?>
                    <img src="public/uploads/<?= htmlspecialchars($eq['escudo']) ?>" alt="Escudo" loading="lazy">
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
                <?php if (es_admin()): ?>
                <a href="editar.php?id=<?= (int)$eq['id'] ?>" class="btn-accion btn-editar2">&#9999;&#65039; Editar</a>
                <form method="post" action="ver.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este equipo?');">
                    <?= csrf_input() ?>
                    <input type="hidden" name="accion" value="eliminar_equipo">
                    <input type="hidden" name="origen" value="<?= e($origenSeleccionado) ?>">
                    <input type="hidden" name="equipo_id" value="<?= (int)$eq['id'] ?>">
                    <button type="submit" class="btn-accion btn-eliminar" style="cursor:pointer;">&#128465;&#65039; Eliminar</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

    <?php if ($error !== ''): ?>
        <div class="alerta-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (es_admin()): ?>
    <div class="panel-crear" style="margin-top:6px;">
        <p style="margin:0;color:var(--tenue);">🛡️ Como administrador puedes
            <a href="partidos.php" style="color:var(--dorado);font-weight:700;">crear partidos aquí</a>.</p>
    </div>
    <?php endif; ?>
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
