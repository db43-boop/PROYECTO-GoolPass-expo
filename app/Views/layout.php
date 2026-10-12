<?php
$title = isset($title) ? $title : 'GoolPass';
$currentRoute = isset($currentRoute) ? $currentRoute : '';
$loggedIn = !empty($_SESSION['user']['id']);
$assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'), '/\\');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · GoolPass</title>
    <link rel="stylesheet" href="<?= h($assetBase . '/css/app.css') ?>">
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?= h(url_for($loggedIn ? 'equipos' : 'login')) ?>">GoolPass</a>
    <nav aria-label="Navegación principal">
        <?php if ($loggedIn): ?>
        <a class="<?= $currentRoute === 'equipos' ? 'is-active' : '' ?>" href="<?= h(url_for('equipos')) ?>">Equipos</a>
        <a class="<?= $currentRoute === 'torneos' ? 'is-active' : '' ?>" href="<?= h(url_for('torneos')) ?>">Torneos</a>
        <a class="<?= $currentRoute === 'partidos' ? 'is-active' : '' ?>" href="<?= h(url_for('partidos')) ?>">Partidos</a>
        <?php endif; ?>
        <a class="<?= $currentRoute === 'entradas' ? 'is-active' : '' ?>" href="<?= h(url_for('entradas')) ?>">Entradas</a>
    </nav>
    <?php if ($loggedIn): ?>
    <form method="post" action="<?= h(url_for('logout')) ?>" class="logout-form">
        <?= csrf_field() ?>
        <button class="button button-quiet" type="submit">Cerrar sesión</button>
    </form>
    <?php else: ?>
    <a class="button button-quiet" href="<?= h(url_for('login')) ?>">Iniciar sesión</a>
    <?php endif; ?>
</header>
<main class="container">
    <?php if (!empty($notice)): ?><p class="notice" role="status"><?= h($notice) ?></p><?php endif; ?>
    <?= $content ?? '' ?>
</main>
<footer class="site-footer">GoolPass · Gestión de fútbol y entradas</footer>
</body>
</html>
