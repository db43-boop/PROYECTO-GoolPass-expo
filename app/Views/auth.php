<section class="auth-grid">
    <div class="auth-intro">
        <p class="eyebrow">Bienvenido a GoolPass</p>
        <h1>Fútbol, torneos y entradas en un solo lugar.</h1>
        <p>Inicia sesión para gestionar torneos, equipos y entradas.</p>
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
            <p class="form-link">¿No tienes cuenta? <a href="<?= h(url_for('register')) ?>">Crear cuenta</a></p>
        </section>
    </div>
</section>
