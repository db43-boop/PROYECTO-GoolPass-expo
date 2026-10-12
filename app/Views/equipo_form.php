<header class="page-heading">
    <div><p class="eyebrow">Clubes</p><h1>Crear equipo</h1><p>Registra el equipo y selecciona su origen.</p></div>
    <a class="button button-secondary" href="<?= h(url_for('equipos')) ?>">Volver a equipos</a>
</header>
<?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<?php $datos = $datos ?? []; ?>
<?php $ligasPorOrigen = $ligasPorOrigen ?? []; ?>
<section class="panel">
    <form method="post" action="<?= h(url_for('equipos/crear')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Nombre<input name="nombre" required maxlength="120" value="<?= h($datos['nombre'] ?? '') ?>"></label>
        <label>Origen<select name="origen" required><option value="">Selecciona</option><option value="nacional" <?= ($datos['origen'] ?? '') === 'nacional' ? 'selected' : '' ?>>Nacional</option><option value="internacional" <?= ($datos['origen'] ?? '') === 'internacional' ? 'selected' : '' ?>>Internacional</option></select></label>
        <label>Liga<select name="liga" required><option value="">Selecciona una liga</option><?php foreach ($ligasPorOrigen as $origen => $ligas): foreach ($ligas as $liga): ?><option value="<?= h($liga) ?>" data-origen="<?= h($origen) ?>" <?= ($datos['ligaSeleccionada'] ?? $datos['liga'] ?? '') === $liga ? 'selected' : '' ?>><?= h($liga) ?></option><?php endforeach; ?><option value="otra" data-origen="<?= h($origen) ?>" <?= ($datos['ligaSeleccionada'] ?? '') === 'otra' ? 'selected' : '' ?>>Otra liga</option><?php endforeach; ?></select></label>
        <label data-other-league hidden style="display:none">Nombre de otra liga<input name="liga_otro" maxlength="120" value="<?= h($datos['ligaOtro'] ?? '') ?>" disabled></label>
        <label>Escudo<input type="file" name="escudo" accept="image/png,image/jpeg,image/webp"></label>
        <button class="button" type="submit">Guardar equipo</button>
    </form>
</section>
<script>
(() => {
    const origin = document.querySelector('[name="origen"]');
    const league = document.querySelector('[name="liga"]');
    const otherField = document.querySelector('[data-other-league]');
    const otherInput = otherField.querySelector('input');
    const updateLeagues = () => {
        [...league.options].forEach(option => {
            if (option.dataset.origen) option.hidden = Boolean(origin.value) && option.dataset.origen !== origin.value;
        });
        const isOther = league.value === 'otra';
        otherField.hidden = !isOther;
        otherField.style.display = isOther ? 'grid' : 'none';
        otherInput.required = isOther;
        otherInput.disabled = !isOther;
    };
    origin.addEventListener('change', () => {
        const selected = league.selectedOptions[0];
        if (selected?.dataset.origen && selected.dataset.origen !== origin.value) league.value = '';
        updateLeagues();
    });
    league.addEventListener('change', updateLeagues);
    updateLeagues();
})();
</script>
