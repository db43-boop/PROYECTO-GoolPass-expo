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
    <link rel="stylesheet" href="public/css/portada.css">
    <link rel="stylesheet" href="public/css/animaciones.css">

    <script src="public/js/animaciones.js"></script>
    <script src="public/js/antireload.js"></script>
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

<?php $paginaActiva = 'index'; require GOOLPASS_ROOT . '/app/Views/partials/cabecera.php'; ?>

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
            <a href="ver.php" class="btn btn-primario">
                <span class="btn-ico">🏟️</span>
                <span>Ver Equipos</span>
            </a>
            <?php if (es_admin()): ?>
            <a href="crear.php" class="btn btn-vidrio">
                <span class="btn-ico">➕</span>
                <span>Agregar Equipo</span>
            </a>
            <?php else: ?>
            <a href="partidos.php" class="btn btn-vidrio"><span class="btn-ico">🎟</span><span>Ver partidos</span></a>
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
            <p><a href="partidos.php">Consulta los partidos disponibles aquí</a>.</p>
        </div>
    <?php else: ?>
        <div class="fila">
            <?php foreach ($ultimos as $eq): ?>
            <?php $plan = $jugadoresPorEquipo[(int)$eq['id']] ?? []; ?>
            <div class="card-equipo">
                <div class="img-wrapper">
                    <?php if (!empty($eq['escudo']) && is_file(GOOLPASS_UPLOADS_DIR . '/' . $eq['escudo'])): ?>
                        <img src="public/uploads/<?= htmlspecialchars($eq['escudo']) ?>" alt="<?= htmlspecialchars($eq['nombre']) ?>">
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
                    <a href="ficha.php?id=<?= (int)$eq['id'] ?>" class="icon-btn" title="Ver ficha">◉</a>
                    <?php if (es_admin()): ?>
                    <a href="editar.php?id=<?= (int)$eq['id'] ?>" class="icon-btn" title="Editar">✎</a>
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
    <a href="ver.php">Equipos</a>
    <span class="sep">·</span>
    <a href="torneo.php">Torneo</a>
    <span class="sep">·</span>
    <a href="partidos.php">Partidos</a>
    <span class="sep">·</span>
    <?php if (es_admin()): ?><a href="crear.php">Nuevo equipo</a><?php endif; ?>
</footer>

</body>
</html>
