<header class="page-heading"><div><p class="eyebrow">Competencias</p><h1>Torneos</h1><p>Consulta cada torneo para administrar sus equipos y partidos.</p></div><a class="button" href="<?= h(url_for('torneos/crear')) ?>">Crear torneo</a></header>
<?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<section><h2>Torneos creados</h2>
    <?php if (empty($torneos)): ?><p class="empty-state">Todavía no hay torneos. Usa “Crear torneo” para comenzar.</p>
    <?php else: ?><div class="card-list tournament-list">
        <?php foreach ($torneos as $torneo): ?><article class="card tournament-card"><div><p class="eyebrow"><?= h($torneo['categoria']) ?></p><h3><?= h($torneo['nombre']) ?></h3><p><?= h($torneo['formato'] ?: 'Formato sin especificar') ?> · <?= h($torneo['estado'] ?? '') ?> · Inscripciones <?= (int)($torneo['inscripciones_abiertas'] ?? 1) === 1 ? 'abiertas' : 'cerradas' ?></p><p class="muted">Equipos inscritos: <?= count($torneo['equipos_inscritos'] ?? []) ?><?php if (!empty($torneo['fecha_inicio'])): ?> · Inicio <?= h($torneo['fecha_inicio']) ?><?php endif; ?></p></div><a class="button button-secondary" href="<?= h(url_for('torneos/ver',['id'=>$torneo['id']])) ?>">Ingresar al torneo</a></article><?php endforeach; ?>
    </div><?php endif; ?>
</section>
