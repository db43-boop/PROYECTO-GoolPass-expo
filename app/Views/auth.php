<section class="auth-grid">
    <div class="auth-intro">
        <p class="eyebrow">Bienvenido a GoolPass</p>
        <h1>Fútbol, torneos y entradas en un solo lugar.</h1>
        <p>Inicia sesión o crea una cuenta para empezar a gestionar el sistema.</p>
    </div>
    <div class="auth-panels">
        <section class="panel" aria-labelledby="login-title">
            <h2 id="login-title">Iniciar sesión</h2>
            <form method="post" action="<?= h(url_for('login')) ?>">
                <?= csrf_field() ?>
                <label>Correo electrónico<input type="email" name="email" autocomplete="email" required value="<?= h($old['email'] ?? '') ?>"></label>
                <label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label>
                <button class="button" type="submit">Entrar</button>
            </form>
        </section>
        <section class="panel" aria-labelledby="register-title">
            <h2 id="register-title">Crear cuenta</h2>
            <form method="post" action="<?= h(url_for('register')) ?>">
                <?= csrf_field() ?>
                <label>Nombre<input type="text" name="nombre" autocomplete="name" required value="<?= h($old['nombre'] ?? '') ?>"></label>
                <label>Correo electrónico<input type="email" name="email" autocomplete="email" required value="<?= h($old['register_email'] ?? '') ?>"></label>
                <label>Contraseña<input type="password" name="password" autocomplete="new-password" required minlength="8"></label>
                <button class="button button-secondary" type="submit">Registrarme</button>
            </form>
        </section>
    </div>
</section>
