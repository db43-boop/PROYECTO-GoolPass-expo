<header class="page-heading">
    <div>
        <p class="eyebrow">Administración</p>
        <h1>Entradas registradas</h1>
        <p>Historial de las entradas compradas, incluidas las compras como invitado.</p>
    </div>
    <a class="button button-secondary" href="<?= h(url_for('entradas')) ?>">Volver a entradas</a>
</header>

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
