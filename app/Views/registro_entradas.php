<header class="page-heading">
    <div>
        <p class="eyebrow">Administración</p>
        <h1><?= !empty($partido) ? 'Entradas compradas' : 'Entradas registradas' ?></h1>
        <?php if (!empty($partido)): ?>
            <p><?= h($partido['equipo_local_nombre']) ?> vs. <?= h($partido['equipo_visitante_nombre']) ?> · <?= h($partido['torneo_nombre'] ?? '') ?></p>
        <?php else: ?>
            <p>Historial de las entradas compradas, incluidas las compras como invitado.</p>
        <?php endif; ?>
    </div>
    <?php if (!empty($partido)): ?>
        <a class="button button-secondary" href="<?= h(url_for('partidos', ['torneo_id' => $partido['torneo_id']])) ?>">Volver al torneo</a>
    <?php else: ?>
        <a class="button button-secondary" href="<?= h(url_for('entradas')) ?>">Volver a entradas</a>
    <?php endif; ?>
</header>

<?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>

<?php if (empty($compras)): ?>
    <p class="empty-state">Todavía no hay entradas compradas.</p>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Partido</th>
                    <th>Torneo</th>
                    <th>Comprador</th>
                    <th>Correo</th>
                    <th>Estado de pago</th>
                    <th>Código</th>
                    <th>Precio</th>
                    <th>Fecha de compra</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compras as $compra): ?>
                    <tr>
                        <td><?= h($compra['partido_nombre'] ?? '') ?></td>
                        <td><?= h($compra['torneo_nombre'] ?? '') ?></td>
                        <td><?= h($compra['comprador_nombre'] ?? '') ?></td>
                        <td><?= h($compra['comprador_email'] ?? '') ?></td>
                        <td><?= h($compra['estado_pago'] ?? '') ?></td>
                        <td><?= h($compra['codigo_qr'] ?? '') ?></td>
                        <td><?= h($compra['precio_pagado'] ?? '') ?></td>
                        <td><?= h($compra['fecha_compra'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
