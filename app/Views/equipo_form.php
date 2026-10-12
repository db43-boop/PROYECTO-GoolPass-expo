<header class="page-heading">
    <div><p class="eyebrow">Clubes</p><h1>Crear equipo</h1><p>Registra el equipo y selecciona su origen.</p></div>
    <a class="button button-secondary" href="<?= h(url_for('equipos')) ?>">Volver a equipos</a>
</header>
<?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<?php $datos = $datos ?? []; ?>
<section class="panel">
    <form method="post" action="<?= h(url_for('equipos/crear')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Nombre<input name="nombre" required maxlength="120" value="<?= h($datos['nombre'] ?? '') ?>"></label>
        <label>Liga<input name="liga" required maxlength="120" value="<?= h($datos['liga'] ?? '') ?>"></label>
        <label>Origen<select name="origen" required><option value="">Selecciona</option><option value="nacional" <?= ($datos['origen'] ?? '') === 'nacional' ? 'selected' : '' ?>>Nacional</option><option value="internacional" <?= ($datos['origen'] ?? '') === 'internacional' ? 'selected' : '' ?>>Internacional</option></select></label>
        <label>Escudo<input type="file" name="escudo" accept="image/png,image/jpeg,image/webp"></label>
        <button class="button" type="submit">Guardar equipo</button>
    </form>
</section>
