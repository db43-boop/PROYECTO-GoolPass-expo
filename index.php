<?php
require_once 'conexion.php';

// --- Datos para la portada (con degradación segura si la BD falla) ---
$totalEquipos = 0;
$totalLigas = 0;
$ultimos = [];

try {
    $totalEquipos = (int) $conexion->query("SELECT COUNT(*) FROM equipos")->fetchColumn();
    $totalLigas = (int) $conexion->query(
        "SELECT COUNT(DISTINCT liga) FROM equipos WHERE liga IS NOT NULL AND TRIM(liga) <> ''"
    )->fetchColumn();

    $stmt = $conexion->query("SELECT id, nombre, liga, escudo, anio FROM equipos ORDER BY id DESC LIMIT 6");
    $ultimos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ultimos = [];
}

/** Devuelve la inicial para el avatar de respaldo */
function inicial_avatar($nombre)
{
    $nombre = trim((string) $nombre);
    if ($nombre === '') return '?';
    return mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoolPass - Portada</title>
    <meta name="description" content="Registra clubes, género, liga y plantillas, genera sus fichas y organiza eliminatorias con la ruleta.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/portada.css">
    <link rel="stylesheet" href="css/animaciones.css">

    <script src="js/animaciones.js"></script>
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
        <a href="partidos.php">Partidos</a>
        <button type="button" class="btn-anim" data-anim-toggle aria-pressed="false"
                title="Desactivar animaciones (modo ligero)">
            <span class="btn-anim-ico" aria-hidden="true">🎰</span>
            <span class="btn-anim-txt">Animaciones</span>
            <span class="btn-anim-estado" data-anim-estado>ON</span>
        </button>
        <a class="nav-cta" href="crear.php">+ Nuevo</a>
    </nav>
</header>

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
            Registra <b>clubes</b>, <b>géneros</b>, <b>ligas</b> y <b>plantillas</b>,
            genera sus fichas con diseño único y organiza las eliminatorias
            con la ruleta. Todo en un solo lugar.
        </p>

        <div class="acciones">
            <a href="ver.php" class="btn btn-primario">
                <span class="btn-ico">🏟️</span>
                <span>Ver Equipos</span>
            </a>
            <a href="crear.php" class="btn btn-vidrio">
                <span class="btn-ico">➕</span>
                <span>Agregar Equipo</span>
            </a>
        </div>
    </section>

    <aside class="hero-stat">
        <div class="stat-box">
            <span class="stat-ico" aria-hidden="true">⚽</span>
            <span class="stat-num"><?= (int)$totalEquipos ?></span>
            <span class="stat-label">Equipos registrados</span>
        </div>
        <div class="stat-box">
            <span class="stat-num"><?= (int)$totalLigas ?></span>
            <span class="stat-label">Ligas disponibles</span>
        </div>

    <?php if (count($ultimos) === 0): ?>
        <div class="vaciado">
            <p>Aún no hay equipos registrados.</p>
            <p>Administrador, <a href="partidos.php">mira tus partidos aqui</a>.</p>
        </div>
    <?php else: ?>
        <div class="fila">
            <?php foreach ($ultimos as $eq): ?>
            <div class="card-equipo">
                <div class="img-wrapper">
                    <?php if (!empty($eq['escudo']) && file_exists('uploads/' . $eq['escudo'])): ?>
                        <img src="uploads/<?= htmlspecialchars($eq['escudo']) ?>" alt="<?= htmlspecialchars($eq['nombre']) ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?= inicial_avatar($eq['nombre']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="info">
                    <h3><?= htmlspecialchars($eq['nombre']) ?></h3>
                    <span class="liga"><?= htmlspecialchars($eq['liga']) ?><?= trim($eq['anio'] ?? '') !== '' ? ' &middot; ' . htmlspecialchars($eq['anio']) : '' ?></span>
                </div>
                <a href="ficha.php?id=<?= (int)$eq['id'] ?>" class="icon-btn" title="Ver ficha">◉</a>
                <a href="editar.php?id=<?= (int)$eq['id'] ?>" class="icon-btn" title="Editar">✎</a>
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
    <a href="torneo.php">Ruleta</a>
    <span class="sep">·</span>
    <a href="partidos.php">Partidos</a>
    <span class="sep">·</span>
    <a href="crear.php">Nuevo equipo</a>
</footer>

</body>
</html>
