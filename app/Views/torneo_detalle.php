<header class="page-heading">
    <div><p class="eyebrow"><?= h($torneo['categoria']) ?></p><h1><?= h($torneo['nombre']) ?></h1><p><?= h($torneo['formato'] ?: 'Formato sin especificar') ?> · <?= h($torneo['estado'] ?? '') ?></p></div>
    <div class="card-actions"><a class="button button-secondary" href="<?= h(url_for('torneos')) ?>">Volver a torneos</a><a class="button" href="<?= h(url_for('partidos',['torneo_id'=>$torneo['id']])) ?>">Generar partido</a></div>
</header>
<?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-grid">
    <section class="panel">
        <h2>Inscribir equipo</h2>
        <?php if (empty($equiposDisponibles)): ?><p class="empty-state">No hay equipos disponibles y compatibles para inscribir.</p>
        <?php else: ?><form method="post" action="<?= h(url_for('torneos/inscribir')) ?>">
            <?= csrf_field() ?><input type="hidden" name="torneo_id" value="<?= h($torneo['id']) ?>">
            <label>Equipo<select name="equipo_id" required><option value="">Selecciona un equipo</option><?php foreach ($equiposDisponibles as $equipo): ?><option value="<?= h($equipo['id']) ?>"><?= h($equipo['nombre']) ?> · <?= h($equipo['origen']) ?></option><?php endforeach; ?></select></label>
            <button class="button" type="submit">Inscribir equipo</button>
        </form><?php endif; ?>
        <p class="hint">Un equipo solo puede inscribirse en un torneo. Los equipos internacionales solo pueden participar en torneos internacionales.</p>
    </section>
    <section>
        <h2>Equipos inscritos (<?= count($equiposInscritos) ?>)</h2>
        <?php if (empty($equiposInscritos)): ?><p class="empty-state">Todavía no hay equipos inscritos.</p>
        <?php else: ?><div class="card-list"><?php foreach ($equiposInscritos as $equipo): ?><article class="card"><div><h3><?= h($equipo['equipo_nombre']) ?></h3><p class="muted"><?= h($equipo['origen']) ?></p></div></article><?php endforeach; ?></div><?php endif; ?>
    </section>
</div>
