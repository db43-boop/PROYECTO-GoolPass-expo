<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis entradas - GoolPass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/app_common.css">
    <link rel="stylesheet" href="public/css/animaciones.css">
    <link rel="stylesheet" href="public/css/mis_entradas.css">
    <link rel="stylesheet" href="public/css/cabecera.css">
    <script src="public/js/animaciones.js"></script>
</head>
<body>
<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>
<?php $paginaActiva = 'mis_entradas'; require GOOLPASS_ROOT . '/app/Views/partials/cabecera.php'; ?>
<main class="envoltura mis-entradas-wrapper">
    <div class="titulo-entradas">
        <div>
            <h1 class="seccion-titulo">🎟️ Mis entradas</h1>
            <p class="seccion-sub">Consulta tus compras simuladas y conserva el código de acceso de cada partido.</p>
        </div>
        <a class="btn btn-primario" href="partidos.php">Comprar otra entrada</a>
    </div>
    <?php if ($mostrarCompraCorrecta): ?>
        <div class="compra-ok" role="status">Pago simulado aceptado. Tu entrada quedó registrada y puedes consultar su código aquí.</div>
    <?php endif; ?>
    <?php if (count($entradas) === 0): ?>
        <section class="sin-entradas panel">
            <h2>Aún no tienes entradas</h2>
            <p>Cuando compres una entrada simulada, aparecerá aquí el partido, el nombre del comprador y su código.</p>
            <a href="partidos.php" class="btn btn-primario">Ver partidos disponibles</a>
        </section>
    <?php else: ?>
        <p class="resumen-entradas">Tienes <?= count($entradas) ?> entrada(s) registradas.</p>
        <div class="grid-entradas">
            <?php foreach ($entradas as $entrada): ?>
            <article class="ticket panel">
                <div class="ticket-encabezado">
                    <span class="ticket-tipo">Entrada #<?= (int)$entrada['id'] ?></span>
                    <span class="ticket-estado">Pagado · Simulado</span>
                </div>
                <h2><?= e($entrada['local_nombre']) ?> <span>vs</span> <?= e($entrada['visitante_nombre']) ?></h2>
                <dl class="ticket-detalles">
                    <div><dt>Fecha del partido</dt><dd><?= e($entrada['fecha_hora']) ?></dd></div>
                    <div><dt>Estadio</dt><dd><?= e($entrada['estadio']) ?></dd></div>
                    <div><dt>Comprador</dt><dd><?= e($entrada['nombre_comprador'] !== '' ? $entrada['nombre_comprador'] : 'No disponible') ?></dd></div>
                    <div><dt>Fecha de compra</dt><dd><?= e($entrada['fecha_compra']) ?></dd></div>
                    <div><dt>Importe pagado</dt><dd>$<?= number_format((float)$entrada['precio_pagado'], 2) ?></dd></div>
                </dl>
                <div class="codigo-box">
                    <span>Código de entrada</span>
                    <div class="codigo-contenido">
                        <code><?= e($entrada['codigo_qr']) ?></code>
                        <button type="button" class="btn-copiar" data-codigo="<?= e($entrada['codigo_qr']) ?>">Copiar</button>
                    </div>
                </div>
                <p class="ticket-aviso">Este código es de demostración: no representa una entrada válida para un evento real.</p>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<script>
document.querySelectorAll('.btn-copiar').forEach((boton) => {
    boton.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(boton.dataset.codigo || '');
            boton.textContent = 'Copiado';
        } catch (_) {
            boton.textContent = 'Selecciona el código para copiarlo';
        }
    });
});
</script>
</body>
</html>
