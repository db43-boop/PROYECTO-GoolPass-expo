<?php
// Cabecera única para todas las páginas de GoolPass.
// Las páginas deben ejecutar requerir_login() antes de incluir este archivo.
$paginaActiva = $paginaActiva ?? '';
?>
<header class="barra goolpass-cabecera">
    <a class="marca" href="index.php" aria-label="GoolPass — Ir a Portada">
        <span class="marca-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                <path d="M5 3h14v3a7 7 0 0 1-5.2 6.8V16h3.2a1.8 1.8 0 0 1 0 3.6H7a1.8 1.8 0 0 1 0-3.6h3.2v-3.2A7 7 0 0 1 5 6V3Z"
                      fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="marca-txt">Gool<em>Pass</em></span>
    </a>
    <nav class="nav" aria-label="Navegación principal">
        <a href="index.php"<?= $paginaActiva === 'index' ? ' class="activo" aria-current="page"' : '' ?>>Portada</a>
        <a href="ver.php"<?= $paginaActiva === 'ver' ? ' class="activo" aria-current="page"' : '' ?>>Equipos</a>
        <a href="torneo.php"<?= $paginaActiva === 'torneo' ? ' class="activo" aria-current="page"' : '' ?>>Torneo</a>
        <a href="partidos.php"<?= $paginaActiva === 'partidos' ? ' class="activo" aria-current="page"' : '' ?>>Partidos</a>
        <button type="button" class="btn-anim" data-anim-toggle aria-pressed="false" title="Desactivar animaciones (modo ligero)">
            <span class="btn-anim-ico" aria-hidden="true">✨</span>
            <span class="btn-anim-txt">Animaciones</span>
            <span class="btn-anim-estado" data-anim-estado>ON</span>
        </button>
        <?php if (es_admin()): ?>
            <a href="crear.php" class="nav-cta"<?= $paginaActiva === 'crear' ? ' aria-current="page"' : '' ?>>+ Nuevo</a>
        <?php endif; ?>
        <?= nav_usuario_html($paginaActiva) ?>
    </nav>
</header>
