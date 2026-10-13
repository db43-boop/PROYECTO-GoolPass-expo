<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios - GoolPass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    
    <?php View::script('animaciones'); ?>
    <style>
        /* Coherente con el diseño de index.php?r=ver: negro, naranja y dorado */
        body { background:#0b0b0f; color:var(--texto); }
        .usuarios-fondo { position:fixed; inset:0; z-index:-1; pointer-events:none;
            background:radial-gradient(1100px 600px at 12% -10%, #2a1200 0%, transparent 60%),
                       radial-gradient(900px 600px at 100% 0%, #0d1b33 0%, transparent 55%),
                       linear-gradient(180deg, #0d0d12 0%, #0b0b0f 100%); }
        .usuarios-fondo::after { content:""; position:absolute; inset:0;
            background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),
                             linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);
            background-size:56px 56px;
            -webkit-mask-image:radial-gradient(ellipse 90% 70% at 50% 30%, #000 25%, transparent 75%);
            mask-image:radial-gradient(ellipse 90% 70% at 50% 30%, #000 25%, transparent 75%); }
        .usuarios-barra {position:sticky; top:0; z-index:20; display:flex; align-items:center;
            justify-content:space-between; flex-wrap:wrap; gap:16px; padding:14px 28px;
            background:rgba(11,11,15,.82); backdrop-filter:blur(12px);
            border-bottom:1px solid rgba(255,255,255,.08);}
        .usuarios-marca {display:flex; align-items:center; gap:10px; font-family:var(--fuente);
            font-weight:900; font-size:17px; color:#fff; text-decoration:none;}
        .usuarios-marca em {font-style:normal; background:linear-gradient(135deg,var(--naranja-2),var(--dorado));
            -webkit-background-clip:text; background-clip:text; color:transparent;}
        .usuarios-marca-ico {width:34px; height:34px; display:grid; place-items:center; border-radius:10px;
            background:linear-gradient(135deg,var(--naranja),var(--naranja-2));
            box-shadow:0 8px 22px rgba(255,69,0,.4);}
        .usuarios-nav {display:flex; align-items:center; gap:8px; flex-wrap:wrap;}
        .usuarios-nav a {color:var(--tenue); font-size:13.5px; font-weight:600; padding:9px 14px;
            border-radius:999px; border:1px solid transparent; transition:.2s; text-decoration:none;}
        .usuarios-nav a:hover {color:#fff; background:rgba(255,255,255,.07);}
        .usuarios-nav a[href="index.php?r=usuarios"] {color:#fff; background:rgba(255,255,255,.10);
            border-color:rgba(255,255,255,.12);}
        .usuarios-wrap {max-width:1180px; margin:0 auto; padding:0 28px 70px;}
        .usuarios-hero {text-align:center; padding:36px 0 16px;}
        .usuarios-chip {display:inline-flex; align-items:center; gap:9px; padding:8px 16px;
            border-radius:999px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1);
            color:var(--tenue); font-size:11.5px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase;}
        .usuarios-chip i {width:8px; height:8px; border-radius:50%; background:#35d07f;
            box-shadow:0 0 10px 2px rgba(53,208,127,.55);}
        .usuarios-hero h1 {font-family:var(--fuente); font-size:clamp(30px,4.4vw,46px);
            font-weight:900; letter-spacing:-1px; margin:14px 0 10px; color:#fff;}
        .usuarios-hero h1 span {background:linear-gradient(135deg,var(--naranja),var(--dorado));
            -webkit-background-clip:text; background-clip:text; color:transparent;}
        .usuarios-hero p {color:var(--tenue); line-height:1.7; font-size:14px;
            max-width:690px; margin:0 auto;}
        .usuarios-resumen {display:grid; grid-template-columns:repeat(3,minmax(0,1fr));
            gap:14px; margin:28px 0;}
        .usuarios-stat {background:linear-gradient(160deg,rgba(255,255,255,.07),rgba(255,255,255,.02));
            border:1px solid var(--panel-borde); border-radius:16px; padding:18px; text-align:center;}
        .usuarios-stat strong {display:block; font-family:var(--fuente); font-size:28px; color:#fff;}
        .usuarios-stat span {color:var(--tenue); font-size:11px; text-transform:uppercase;
            font-weight:700; letter-spacing:1px;}
        .usuarios-panel {background:linear-gradient(160deg,rgba(255,255,255,.065),rgba(255,255,255,.025));
            border:1px solid var(--panel-borde); border-radius:18px; padding:26px;
            margin-bottom:24px; box-shadow:var(--sombra-s);}
        .usuarios-panel h2 {font-family:var(--fuente); font-weight:800; font-size:21px;
            margin:0 0 6px; color:#fff;}
        .usuarios-panel .subtitulo {margin:0 0 22px; font-size:13px; color:var(--tenue); line-height:1.6;}
        .usuarios-campos {display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px;}
        .usuarios-campos label {display:block; color:#d2d2de; font-size:13px; font-weight:600; margin-bottom:8px;}
        .usuarios-campos input {display:block; width:100%; border:1px solid rgba(255,255,255,.14);
            border-radius:11px; padding:13px 14px; background:rgba(0,0,0,.35); color:#fff;
            font-family:var(--fuente-txt); font-size:14px; outline:0; transition:border-color .2s,box-shadow .2s;}
        .usuarios-campos input::placeholder {color:#77788a;}
        .usuarios-campos input:focus {border-color:rgba(255,122,51,.7);
            box-shadow:0 0 0 3px rgba(255,122,51,.12);}
        .usuarios-submit {margin-top:22px; border:0; border-radius:999px;
            background:linear-gradient(135deg,var(--naranja),var(--naranja-2));
            color:#fff; padding:12px 24px; font-family:var(--fuente); font-weight:800;
            font-size:14px; cursor:pointer; box-shadow:0 10px 26px rgba(255,69,0,.28);}
        .usuarios-submit:hover {filter:brightness(1.1); transform:translateY(-1px);}
        .usuarios-tabla-wrap {overflow-x:auto; border-radius:12px; border:1px solid rgba(255,255,255,.08);}
        .usuarios-tabla {border-collapse:collapse; width:100%; min-width:650px; font-size:13.5px;}
        .usuarios-tabla th {background:rgba(255,255,255,.055); text-transform:uppercase;
            letter-spacing:.8px; font-size:11px; color:var(--tenue); font-weight:800;}
        .usuarios-tabla th,.usuarios-tabla td {padding:15px 16px; text-align:left;
            border-bottom:1px solid rgba(255,255,255,.075);}
        .usuarios-tabla tbody tr:hover {background:rgba(255,255,255,.035);}
        .usuarios-tabla tbody tr:last-child td {border-bottom:0;}
        .rol-etiqueta {display:inline-flex; border-radius:999px; font-weight:700; font-size:11px;
            padding:6px 11px; border:1px solid rgba(255,255,255,.12);}
        .rol-etiqueta.admin {color:#ffcf82; background:rgba(255,152,0,.1); border-color:rgba(255,152,0,.27);}
        .rol-etiqueta.usuario {color:#a4d1ff; background:rgba(66,165,255,.09); border-color:rgba(66,165,255,.24);}
        .usuarios-aviso {padding:13px 16px; border-radius:11px; margin:18px 0;
            background:rgba(53,208,127,.09); color:#a9f0ce; border:1px solid rgba(53,208,127,.3);}
        .usuarios-aviso.error {background:rgba(229,57,53,.1); color:#ffcdcd; border-color:rgba(229,57,53,.35);}
        .usuarios-vacio {text-align:center; color:var(--tenue); padding:25px;}
        @media(max-width:900px) {.usuarios-campos {grid-template-columns:1fr 1fr;}}
        @media(max-width:700px) {
            .usuarios-barra {padding:12px 16px;}
            .usuarios-wrap {padding:0 16px 48px;}
            .usuarios-panel {padding:18px;}
            .usuarios-campos {grid-template-columns:1fr;}
            .usuarios-resumen {grid-template-columns:1fr 1fr;}
            .usuarios-resumen .usuarios-stat:first-child {grid-column:1/-1;}
        }
    </style>
    
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
<?php $paginaActiva = 'usuarios'; View::cabecera($paginaActiva); ?>
<main class="usuarios-wrap">
    <div class="usuarios-hero">
        <span class="usuarios-chip"><i></i> Panel de administración</span>
        <h1>Gestión de <span>usuarios</span></h1>
        <p>Administra las cuentas registradas en GoolPass. Esta sección es exclusiva de administradores y los nuevos registros tendrán el rol Usuario.</p>
    </div>
    <?php
        $totalUsuarios = count($usuarios);
        $totalAdmins = count(array_filter($usuarios, static fn($u) => $u['rol'] === 'admin'));
    ?>
    <section class="usuarios-resumen" aria-label="Resumen de usuarios">
        <div class="usuarios-stat"><strong><?= $totalUsuarios ?></strong><span>Usuarios registrados</span></div>
        <div class="usuarios-stat"><strong><?= $totalAdmins ?></strong><span>Administradores</span></div>
        <div class="usuarios-stat"><strong><?= $totalUsuarios - $totalAdmins ?></strong><span>Usuarios normales</span></div>
    </section>
    <?php if ($error !== ''): ?><div class="usuarios-aviso error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($mensaje !== ''): ?><div class="usuarios-aviso" role="status"><?= e($mensaje) ?></div><?php endif; ?>
    <section class="usuarios-panel">
        <h2>Registrar nuevo usuario</h2>
        <p class="subtitulo">Completa los datos para crear una cuenta con permisos de usuario.</p>
        <form method="post" action="index.php?r=usuarios">
            <?= csrf_input() ?>
            <input type="hidden" name="accion" value="crear_usuario">
            <div class="usuarios-campos">
                <div>
                    <label for="nombre">Nombre completo</label>
                    <input id="nombre" name="nombre" type="text" maxlength="100" placeholder="Ej. Juan Pérez" value="<?= e($nombre ?? '') ?>" autocomplete="name" required>
                </div>
                <div>
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" placeholder="usuario@correo.com" value="<?= e($email ?? '') ?>" autocomplete="email" required>
                </div>
                <div>
                    <label for="password">Contraseña inicial (mínimo 8 caracteres)</label>
                    <input id="password" name="password" type="password" minlength="8" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required>
                </div>
            </div>
            <button class="usuarios-submit" type="submit">+ Crear usuario</button>
        </form>
    </section>
    <section class="usuarios-panel">
        <h2>Usuarios registrados (<?= $totalUsuarios ?>)</h2>
        <p class="subtitulo">Consulta las cuentas existentes y sus permisos asignados.</p>
        <div class="usuarios-tabla-wrap">
            <table class="usuarios-tabla">
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Fecha de registro</th></tr>
                </thead>
                <tbody>
                    <?php if (!$usuarios): ?>
                        <tr><td colspan="5" class="usuarios-vacio">Todavía no hay usuarios registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= e($u['nombre']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><span class="rol-etiqueta <?= $u['rol'] === 'admin' ? 'admin' : 'usuario' ?>"><?= $u['rol'] === 'admin' ? 'Administrador' : 'Usuario' ?></span></td>
                                <td><?= e($u['creado_en']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
