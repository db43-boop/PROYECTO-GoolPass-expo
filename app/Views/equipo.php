<header class="page-heading"><div><p class="eyebrow"><?= h($equipo['origen'] ?? 'Equipo') ?></p><h1><?= h($equipo['nombre'] ?? '') ?></h1><p><?= h($equipo['liga'] ?? '') ?></p></div><a class="button button-secondary" href="<?= h(url_for('equipos')) ?>">Volver a equipos</a></header>
<?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-grid">
    <section class="panel"><h2>Agregar jugador</h2><form method="post" action="<?= h(url_for('equipos/jugadores/crear', ['id' => $equipo['id'] ?? ''])) ?>">
        <?= csrf_field() ?><input type="hidden" name="equipo_id" value="<?= h($equipo['id'] ?? '') ?>">
        <label>Nombre<input name="nombre" required maxlength="120"></label><label>Posición<input name="posicion" required maxlength="60" placeholder="Ej. Delantero"></label>
        <button class="button" type="submit">Agregar jugador</button>
    </form></section>
    <section><h2>Plantilla</h2><?php if (empty($jugadores)): ?><p class="empty-state">Este equipo todavía no tiene jugadores.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Jugador</th><th>Posición</th></tr></thead><tbody><?php foreach ($jugadores as $jugador): ?><tr><td><?= h($jugador['nombre']) ?></td><td><?= h($jugador['posicion']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
</div>
