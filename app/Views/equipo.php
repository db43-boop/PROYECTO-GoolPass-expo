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
<section class="section-gap panel">
    <h2>Inscripción a torneo</h2>
    <?php if (!empty($inscripcion)): ?>
        <p>Este equipo ya está inscrito en <strong><?= h($inscripcion['torneo_nombre']) ?></strong>.</p>
        <a class="button button-secondary" href="<?= h(url_for('torneos/ver', ['id' => $inscripcion['torneo_id']])) ?>">Ver torneo</a>
    <?php elseif (empty($torneosDisponibles)): ?>
        <p class="empty-state">No hay torneos abiertos compatibles. Puedes volver más tarde o consultar los torneos creados.</p>
        <a class="button button-secondary" href="<?= h(url_for('torneos')) ?>">Ver torneos</a>
    <?php else: ?>
        <p>Elige un torneo abierto para inscribir este equipo.</p>
        <form method="post" action="<?= h(url_for('equipos/inscribir')) ?>">
            <?= csrf_field() ?><input type="hidden" name="equipo_id" value="<?= h($equipo['id']) ?>">
            <label>Torneo<select name="torneo_id" required>
                <option value="">Selecciona un torneo</option>
                <?php foreach ($torneosDisponibles as $torneo): ?>
                    <option value="<?= h($torneo['id']) ?>" <?= (int)($selectedTournamentId ?? 0) === (int)$torneo['id'] ? 'selected' : '' ?>><?= h($torneo['nombre']) ?> · <?= h($torneo['categoria']) ?></option>
                <?php endforeach; ?>
            </select></label>
            <button class="button" type="submit">Inscribir equipo</button>
        </form>
    <?php endif; ?>
</section>
