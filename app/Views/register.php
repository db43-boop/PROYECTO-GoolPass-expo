<section class="auth-grid">
    <div class="auth-intro">
        <p class="eyebrow">GoolPass</p>
        <h1>Crear cuenta</h1>
        <p>Registra tus datos para empezar a gestionar equipos, torneos y partidos.</p>
    </div>
    <div class="auth-panels">
        <section class="panel" aria-labelledby="register-title">
            <h2 id="register-title">Crear cuenta</h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= h(url_for('register')) ?>">
                <?= csrf_field() ?>
                <label>Nombre<input type="text" name="nombre" autocomplete="name" required value="<?= h($old['nombre'] ?? '') ?>"></label>
                <label>Correo electrónico<input type="email" name="email" autocomplete="email" required value="<?= h($old['email'] ?? '') ?>"></label>
                <label>Contraseña<input type="password" name="password" autocomplete="new-password" required minlength="8"></label>
                <button class="button" type="submit">Registrarme</button>
            </form>
            <p class="form-link">¿Ya tienes cuenta? <a href="<?= h(url_for('login')) ?>">Iniciar sesión</a></p>
        </section>
    </div>
</section>
