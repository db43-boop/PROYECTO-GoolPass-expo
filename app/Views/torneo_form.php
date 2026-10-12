<header class="page-heading">
    <div><p class="eyebrow">Competencias</p><h1>Crear torneo</h1><p>Completa los datos básicos para abrir un torneo.</p></div>
    <a class="button button-secondary" href="<?= h(url_for('torneos')) ?>">Volver a torneos</a>
</header>
<?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<?php $datos = $datos ?? []; ?>
<section class="panel">
    <form method="post" action="<?= h(url_for('torneos/crear')) ?>">
        <?= csrf_field() ?>
        <label>Nombre<input name="nombre" required maxlength="140" value="<?= h($datos['nombre'] ?? '') ?>"></label>
        <label>Categoría<select name="categoria" required><option value="">Selecciona</option><option value="nacional" <?= ($datos['categoria'] ?? '') === 'nacional' ? 'selected' : '' ?>>Nacional</option><option value="internacional" <?= ($datos['categoria'] ?? '') === 'internacional' ? 'selected' : '' ?>>Internacional</option></select></label>
        <label>Formato<select name="formato"><option value="">Sin especificar</option><option value="liga" <?= ($datos['formato'] ?? '') === 'liga' ? 'selected' : '' ?>>Liga</option><option value="eliminacion" <?= ($datos['formato'] ?? '') === 'eliminacion' ? 'selected' : '' ?>>Eliminación directa</option><option value="grupos" <?= ($datos['formato'] ?? '') === 'grupos' ? 'selected' : '' ?>>Grupos</option><option value="otro" <?= ($datos['formato'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option></select></label>
        <label>Fecha de inicio<input type="date" name="fecha_inicio" value="<?= h($datos['fecha_inicio'] ?? '') ?>"></label>
        <label>Fecha de fin<input type="date" name="fecha_fin" value="<?= h($datos['fecha_fin'] ?? '') ?>"></label>
        <button class="button" type="submit">Guardar torneo</button>
    </form>
</section>
