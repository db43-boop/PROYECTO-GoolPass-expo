<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil - GoolPass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/app_common.css">
    <link rel="stylesheet" href="public/css/animaciones.css">
    <script src="public/js/antireload.js"></script>
    <link rel="stylesheet" href="public/css/cabecera.css">
    <script src="public/js/animaciones.js"></script>
</head>
<body>

<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>

<?php $paginaActiva = 'perfil'; require GOOLPASS_ROOT . '/app/Views/partials/cabecera.php'; ?>

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
        <p style="margin:0 0 18px;"><a href="mis_entradas.php" class="btn btn-primario">🎟️ Ver mis entradas y códigos</a></p>
        <?php endif; ?>
        <?php if (es_admin()): ?>
        <p><a href="usuarios.php" class="btn btn-primario">Administrar usuarios</a></p>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-rojo"><span class="btn-ico">⏻</span><span>Cerrar sesión</span></a>
    </div>
</main>

</body>
</html>
