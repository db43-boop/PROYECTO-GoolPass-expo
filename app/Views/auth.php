<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - GoolPass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    
    <style>
        .auth-wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .auth-card { width: 100%; max-width: 440px; padding: 34px 30px; }
        .auth-logo { display: flex; align-items: center; gap: 11px; font-family: var(--fuente); font-weight: 700; font-size: 20px; margin-bottom: 22px; }
        .auth-titulo { font-family: var(--fuente); font-size: 26px; font-weight: 900; margin: 0 0 6px; }
        .auth-sub { color: var(--tenue); font-size: 14px; margin: 0 0 22px; }
        .auth-tabs { display: flex; gap: 8px; background: rgba(0,0,0,.25); padding: 6px; border-radius: 999px; margin-bottom: 22px; }
        .auth-tab { flex: 1; text-align: center; padding: 10px; border-radius: 999px; font-weight: 700; font-size: 14px; color: var(--tenue); cursor: pointer; border: none; background: transparent; font-family: var(--fuente); }
        .auth-tab.activo { background: linear-gradient(135deg, var(--naranja), var(--naranja-2)); color: #fff; }
        .auth-campo { margin-bottom: 14px; }
        .auth-campo label { display: block; color: var(--tenue); font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .auth-alerta { background: #3a1c1c; border: 1px solid #f44336; color: #f3c1c1; padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
        .auth-roles { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
        .rol-opcion { position: relative; }
        .rol-opcion input { position: absolute; opacity: 0; }
        .rol-opcion span { display: block; text-align: center; padding: 12px 10px; border-radius: 12px; border: 1px solid var(--panel-borde); background: rgba(0,0,0,.28); cursor: pointer; font-weight: 700; font-size: 14px; font-family: var(--fuente); transition: .2s; }
        .rol-opcion span small { display: block; font-weight: 500; font-size: 11px; color: var(--tenue); margin-top: 3px; }
        .rol-opcion input:checked + span { border-color: var(--naranja-2); background: rgba(255,122,51,.15); box-shadow: 0 0 0 3px rgba(255,122,51,.18); }
        .auth-pie { text-align: center; margin-top: 18px; color: var(--tenue); font-size: 13px; }
        .auth-pie a { color: var(--dorado); font-weight: 700; }
    </style>
    
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

<div class="auth-wrap">
    <div class="auth-card panel">
        <a class="auth-logo" href="index.php?r=login">
            <span class="marca-icono">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                    <path d="M5 3h14v3a7 7 0 0 1-5.2 6.8V16h3.2a1.8 1.8 0 0 1 0 3.6H7a1.8 1.8 0 0 1 0-3.6h3.2v-3.2A7 7 0 0 1 5 6V3Z"
                          fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="marca-txt">Gool<em>Pass</em></span>
        </a>

        <button type="button" class="auth-anim-toggle btn-anim" data-anim-toggle aria-pressed="false" aria-label="Activar o desactivar animaciones">
            <span class="btn-anim-ico" aria-hidden="true">&#10024;</span>
            <span class="btn-anim-txt">Animaciones</span>
            <span class="btn-anim-estado" data-anim-estado>ON</span>
        </button>

        <h1 class="auth-titulo"><?= $modo === 'registro' ? 'Crear cuenta de usuario' : 'Bienvenido de nuevo' ?></h1>
        <p class="auth-sub"><?= $modo === 'registro' ? 'Regístrate para ver y pagar partidos.' : 'Inicia sesión para continuar.' ?></p>

        <div class="auth-tabs">
            <button type="button" class="auth-tab <?= $modo === 'login' ? 'activo' : '' ?>" data-tab="login">Iniciar sesión</button>
            <button type="button" class="auth-tab <?= $modo === 'registro' ? 'activo' : '' ?>" data-tab="registro">Registrarme</button>
        </div>

        <?php if ($error !== ''): ?>
            <div class="auth-alerta">❌ <?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" id="form-login" class="<?= $modo === 'registro' ? 'oculto' : '' ?>">
            <input type="hidden" name="accion" value="login">
            <div class="auth-campo">
                <label for="login-email">Correo electrónico</label>
                <input type="email" id="login-email" name="email" placeholder="tu@correo.com" required
                       value="<?= isset($_POST['email']) && $modo === 'login' ? e($_POST['email']) : '' ?>">
            </div>
            <div class="auth-campo">
                <label for="login-pass">Contraseña</label>
                <input type="password" id="login-pass" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primario btn-ancho"><span class="btn-ico">🔓</span><span>Entrar</span></button>
        </form>

        <form method="post" id="form-registro" class="<?= $modo === 'login' ? 'oculto' : '' ?>">
            <input type="hidden" name="accion" value="registro">
            <div class="auth-campo">
                <label for="reg-nombre">Nombre</label>
                <input type="text" id="reg-nombre" name="nombre" placeholder="Tu nombre" required
                       value="<?= isset($_POST['nombre']) && $modo === 'registro' ? e($_POST['nombre']) : '' ?>">
            </div>
            <div class="auth-campo">
                <label for="reg-email">Correo electrónico</label>
                <input type="email" id="reg-email" name="email" placeholder="tu@correo.com" required
                       value="<?= isset($_POST['email']) && $modo === 'registro' ? e($_POST['email']) : '' ?>">
            </div>
            <div class="auth-campo">
                <label for="reg-pass">Contraseña</label>
                <input type="password" id="reg-pass" name="password" placeholder="Mínimo 6 caracteres" required>
            </div>
            <button type="submit" class="btn btn-primario btn-ancho"><span class="btn-ico">✨</span><span>Crear cuenta</span></button>
        </form>

        <p class="auth-pie">
            <?php if ($modo === 'registro'): ?>
                ¿Ya tienes cuenta? <a href="#" data-tab-link="login">Inicia sesión</a>
            <?php else: ?>
                ¿No tienes cuenta? <a href="#" data-tab-link="registro">Regístrate aquí</a>
            <?php endif; ?>
        </p>
    </div>
</div>

<script>
    function activarTab(tab) {
        document.querySelectorAll('.auth-tab').forEach(t =>
            t.classList.toggle('activo', t.dataset.tab === tab));
        document.getElementById('form-login').classList.toggle('oculto', tab !== 'login');
        document.getElementById('form-registro').classList.toggle('oculto', tab !== 'registro');
    }
    document.querySelectorAll('[data-tab]').forEach(b =>
        b.addEventListener('click', () => activarTab(b.dataset.tab)));
    document.querySelectorAll('[data-tab-link]').forEach(a =>
        a.addEventListener('click', ev => { ev.preventDefault(); activarTab(a.dataset.tabLink); }));
</script>

</body>
</html>
