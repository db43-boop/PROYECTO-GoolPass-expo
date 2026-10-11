<?php
require_once __DIR__ . '/auth.php';
requerir_login();

$u = usuario_actual();
$denegado = isset($_GET['denegado']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil - GoolPass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app_common.css">
    <link rel="stylesheet" href="css/animaciones.css">
    <script src="js/antireload.js"></script>
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
        <a href="torneo.php">Torneo</a>
        <a href="partidos.php">Partidos</a>
        <?= nav_usuario_html() ?>
    </nav>
</header>

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
                <span class="rol-badge rol-admin">🛡️ Administrador · puedes crear partidos</span>
            <?php else: ?>
                <span class="rol-badge rol-usuario">👤 Usuario · puedes ver y pagar partidos</span>
            <?php endif; ?>
        </p>

        <a href="logout.php" class="btn btn-rojo"><span class="btn-ico">⏻</span><span>Cerrar sesión</span></a>
    </div>
</main>

</body>
</html>
