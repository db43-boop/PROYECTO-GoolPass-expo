<?php
/** Cabecera compartida de las vistas reorganizadas. */
function goolpass_cabecera(string $paginaActiva = ''): void { ?>

<header class="barra goolpass-cabecera">
    <a class="marca" href="index.php?r=index" aria-label="GoolPass — Ir a Portada">
        <span class="marca-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                <path d="M5 3h14v3a7 7 0 0 1-5.2 6.8V16h3.2a1.8 1.8 0 0 1 0 3.6H7a1.8 1.8 0 0 1 0-3.6h3.2v-3.2A7 7 0 0 1 5 6V3Z"
                      fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="marca-txt">Gool<em>Pass</em></span>
    </a>
    <nav class="nav" aria-label="Navegación principal">
        <a href="index.php?r=index"<?= $paginaActiva === 'index' ? ' class="activo" aria-current="page"' : '' ?>>Portada</a>
        <a href="index.php?r=ver"<?= $paginaActiva === 'ver' ? ' class="activo" aria-current="page"' : '' ?>>Equipos</a>
        <a href="index.php?r=torneo"<?= $paginaActiva === 'torneo' ? ' class="activo" aria-current="page"' : '' ?>>Torneo</a>
        <a href="index.php?r=partidos"<?= $paginaActiva === 'partidos' ? ' class="activo" aria-current="page"' : '' ?>>Partidos</a>
        <button type="button" class="btn-anim" data-anim-toggle aria-pressed="false" title="Desactivar animaciones (modo ligero)">
            <span class="btn-anim-ico" aria-hidden="true">✨</span>
            <span class="btn-anim-txt">Animaciones</span>
            <span class="btn-anim-estado" data-anim-estado>ON</span>
        </button>
        <?php if (es_admin()): ?>
            <a href="index.php?r=crear" class="nav-cta"<?= $paginaActiva === 'crear' ? ' aria-current="page"' : '' ?>>+ Nuevo</a>
        <?php endif; ?>
        <?= nav_usuario_html($paginaActiva) ?>
    </nav>
</header>

<?php }
if (($__page ?? '') === 'inicio'): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoolPass - Portada</title>
    <meta name="description" content="Registra clubes, su liga y su plantilla de jugadores, genera sus fichas y organiza los partidos del torneo.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    

    <?php View::script('animaciones'); ?>
    <?php View::script('antireload'); ?>
    
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>

<?php $paginaActiva = 'index'; View::cabecera($paginaActiva); ?>

<main class="hero">

    <section class="hero-txt">
        <span class="chip chip-live">
            <i class="punto"></i> Temporada activa
        </span>

        <h1 class="titulo">
            <span class="linea">Sistema de</span>
            <span class="linea gradiente">Torneos</span>
        </h1>

        <p class="bajada">
            Registra <b>clubes</b>, su <b>liga</b> y su <b>plantilla</b> de jugadores,
            genera sus fichas y organiza los <b>partidos</b> del torneo.
            Todo en un solo lugar.
        </p>

        <div class="acciones">
            <a href="index.php?r=ver" class="btn btn-primario">
                <span class="btn-ico">🏟️</span>
                <span>Ver Equipos</span>
            </a>
            <?php if (es_admin()): ?>
            <a href="index.php?r=crear" class="btn btn-vidrio">
                <span class="btn-ico">➕</span>
                <span>Agregar Equipo</span>
            </a>
            <?php else: ?>
            <a href="index.php?r=partidos" class="btn btn-vidrio"><span class="btn-ico">🎟</span><span>Ver partidos</span></a>
            <?php endif; ?>
        </div>
    </section>

    <aside class="hero-stat">
        <div class="stats-par">
            <div class="stat-box">
                <span class="stat-ico" aria-hidden="true">⚽</span>
                <span class="stat-num"><?= (int)$totalEquipos ?></span>
                <span class="stat-label">Equipos registrados</span>
            </div>
            <div class="stat-box">
                <span class="stat-ico" aria-hidden="true">🏆</span>
                <span class="stat-num"><?= (int)$totalLigas ?></span>
                <span class="stat-label">Ligas disponibles</span>
            </div>
        </div>

    <?php if (count($ultimos) === 0): ?>
        <div class="vaciado">
            <p>Aún no hay equipos registrados.</p>
            <p><a href="index.php?r=partidos">Consulta los partidos disponibles aquí</a>.</p>
        </div>
    <?php else: ?>
        <div class="fila">
            <?php foreach ($ultimos as $eq): ?>
            <?php $plan = $jugadoresPorEquipo[(int)$eq['id']] ?? []; ?>
            <div class="card-equipo">
                <div class="img-wrapper">
                    <?php if (!empty($eq['escudo']) && is_file(GOOLPASS_UPLOADS_DIR . '/' . $eq['escudo'])): ?>
                        <img src="uploads/<?= htmlspecialchars($eq['escudo']) ?>" alt="<?= htmlspecialchars($eq['nombre']) ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?= inicial_avatar($eq['nombre']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="info">
                    <h3><?= htmlspecialchars($eq['nombre']) ?></h3>
                    <span class="liga"><?= htmlspecialchars($eq['liga']) ?><?= trim($eq['anio'] ?? '') !== '' ? ' · ' . htmlspecialchars($eq['anio']) : '' ?></span>
                    <?php if ($plan): ?>
                    <div class="plantilla">
                        <?php foreach ($plan as $pl): $meta = pos_meta($pl['posicion']); ?>
                        <span class="jug-chip" title="<?= htmlspecialchars($pl['posicion']) ?>">
                            <i class="dot pos-<?= $meta[1] ?>"></i><?= htmlspecialchars($pl['nombre']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="plantilla plantilla-vacia">Sin plantilla</div>
                    <?php endif; ?>
                </div>
                <div class="card-acciones">
                    <a href="index.php?r=ficha&id=<?= (int)$eq['id'] ?>" class="icon-btn" title="Ver ficha">◉</a>
                    <?php if (es_admin()): ?>
                    <a href="index.php?r=editar&id=<?= (int)$eq['id'] ?>" class="icon-btn" title="Editar">✎</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    </aside>
</main>

<footer class="pie">
    <span>GoolPass &copy; 2026</span>
    <span class="sep">·</span>
    <a href="index.php?r=ver">Equipos</a>
    <span class="sep">·</span>
    <a href="index.php?r=torneo">Torneo</a>
    <span class="sep">·</span>
    <a href="index.php?r=partidos">Partidos</a>
    <span class="sep">·</span>
    <?php if (es_admin()): ?><a href="index.php?r=crear">Nuevo equipo</a><?php endif; ?>
</footer>

</body>
</html>

<?php elseif (($__page ?? '') === 'perfil'): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil - GoolPass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    
    <?php View::script('antireload'); ?>
    
    <?php View::script('animaciones'); ?>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>

<?php $paginaActiva = 'perfil'; View::cabecera($paginaActiva); ?>

<main class="envoltura" style="max-width:560px;margin:0 auto;padding:0 20px 40px;">
    <h1 class="seccion-titulo">👤 Mi perfil</h1>

    <?php if ($denegado): ?>
        <div class="alerta-error" style="background:#3a1c1c;border:1px solid #f44336;color:#f3c1c1;padding:12px;border-radius:10px;margin-bottom:16px;">
            ⛔ No tienes permiso de administrador para esa acción.
        </div>
    <?php endif; ?>

    <div class="panel" style="padding:24px;">
        <p style="margin:0 0 8px;color:var(--tenue);font-size:13px;">Nombre</p>
        <h2 style="margin:0 0 18px;"><?= e($u['nombre']) ?></h2>

        <p style="margin:0 0 8px;color:var(--tenue);font-size:13px;">Correo</p>
        <p style="margin:0 0 18px;"><?= e($u['email']) ?></p>

        <p style="margin:0 0 8px;color:var(--tenue);font-size:13px;">Rol</p>
        <p style="margin:0 0 24px;">
            <?php if ($u['rol'] === 'admin'): ?>
                <span class="rol-badge rol-admin">🛡️ Administrador · gestiona equipos, jugadores, torneos, partidos y usuarios</span>
            <?php else: ?>
                <span class="rol-badge rol-usuario">👤 Usuario · puedes consultar equipos, ver partidos y comprar entradas</span>
            <?php endif; ?>
        </p>

        <?php if (!es_admin()): ?>
        <p style="margin:0 0 18px;"><a href="index.php?r=mis_entradas" class="btn btn-primario">🎟️ Ver mis entradas y códigos</a></p>
        <?php endif; ?>
        <?php if (es_admin()): ?>
        <p><a href="index.php?r=usuarios" class="btn btn-primario">Administrar usuarios</a></p>
        <?php endif; ?>
        <a href="index.php?r=logout" class="btn btn-rojo"><span class="btn-ico">⏻</span><span>Cerrar sesión</span></a>
    </div>
</main>

</body>
</html>

<?php endif; ?>
