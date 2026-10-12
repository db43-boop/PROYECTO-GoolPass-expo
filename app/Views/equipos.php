<header class="page-heading"><div><p class="eyebrow">Clubes</p><h1>Equipos</h1><p>Consulta los equipos por origen y administra sus plantillas.</p></div><a class="button" href="<?= h(url_for('equipos/crear')) ?>">Crear equipo</a></header>
<?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-grid team-groups">
    <?php foreach ([['titulo'=>'Nacionales','equipos'=>$equiposNacionales ?? []],['titulo'=>'Internacionales','equipos'=>$equiposInternacionales ?? []]] as $grupo): ?>
        <section class="team-category"><h2><?= h($grupo['titulo']) ?> <span class="muted">(<?= count($grupo['equipos']) ?>)</span></h2>
            <?php if (empty($grupo['equipos'])): ?><p class="empty-state">Todavía no hay equipos <?= strtolower($grupo['titulo']) ?> registrados.</p>
            <?php else: ?><div class="card-list">
                <?php foreach ($grupo['equipos'] as $equipo): ?><article class="card team-card">
                    <?php if (!empty($equipo['escudo'])): ?><img class="crest" src="<?= h($equipo['escudo']) ?>" alt="Escudo de <?= h($equipo['nombre']) ?>"><?php endif; ?>
                    <div><h3><a href="<?= h(url_for('equipos/ver', ['id' => $equipo['id']])) ?>"><?= h($equipo['nombre']) ?></a></h3><p><?= h($equipo['liga']) ?></p></div>
                </article><?php endforeach; ?>
            </div><?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
