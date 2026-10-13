<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Partido - GoolPass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    
    
    <?php View::script('animaciones'); ?>
    <?php View::script('antireload'); ?>
    <style>
        .partido-marcador{display:flex;align-items:center;justify-content:space-between;gap:9px;padding:15px 8px;margin:12px 0;border-radius:12px;background:linear-gradient(120deg,#25252d,#131317);border:1px solid #45414a;color:#f2f2f2}
        .partido-marcador .marcador-eq{flex:1;min-width:0;text-align:center;font-size:13px;font-weight:800;overflow-wrap:anywhere}
        .partido-marcador .marcador-cifra{font:900 clamp(22px,3vw,32px)/1 Outfit,Arial,sans-serif;color:#ffd36b;letter-spacing:1px;white-space:nowrap}
        .partido-marcador .marcador-estado{font-size:11px;color:#c7c7cc;text-align:center;margin:6px 0 14px}
        .marcador-admin{margin:12px 0;border-top:1px solid #494949;padding-top:11px}
        .marcador-admin summary{cursor:pointer;color:#ffba68;font-weight:700;font-size:13px;margin-bottom:12px}
        .marcador-admin .campos-marcador{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
        .marcador-admin label{display:flex;flex-direction:column;gap:4px;font-size:11px;color:#c7c7cc}
        .marcador-admin input,.marcador-admin select{width:100%;background:#23232c;color:white;border:1px solid #5d5d66;border-radius:7px;padding:8px}
        .marcador-admin button{margin-top:12px}
        @media(max-width:550px){.marcador-admin .campos-marcador{grid-template-columns:1fr 1fr}.marcador-admin .campos-marcador label:first-child{grid-column:1/-1}}
        .grid-partidos { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin: 20px 0 30px; }
        .card-partido { background: #1e1e1e; border: 2px solid #333; border-radius: 12px; padding: 20px; transition: border-color .3s; }
        .card-partido:hover { border-color: #FF6B6B; }
        .card-partido h3 { margin: 0 0 8px; color: #fff; font-size: 18px; }
        .estado-compra-cerrada {border:1px solid #625239;background:#2d261d;color:#ffd36b;padding:11px 13px;border-radius:9px;text-align:center;font-size:13px;font-weight:700;margin-top:13px;}
        .estado-entrada-comprada {border:1px solid rgba(53,208,127,.45);background:rgba(34,113,70,.16);color:#a6f4c8;padding:12px 13px;border-radius:10px;text-align:center;font-size:13px;font-weight:700;margin-top:13px;}
        .estado-entrada-comprada a {color:#fff3ac;display:inline-block;margin-left:5px;text-decoration:underline;text-underline-offset:3px;}
        .estado-entrada-comprada a:focus-visible {outline:2px solid #ffd24a;outline-offset:3px;}
        .card-partido .meta { color: #aaa; font-size: 13px; margin: 4px 0; }
        .card-partido .precio { color: #FFD700; font-weight: 700; font-size: 20px; margin: 10px 0; }
        .form-panel { background: #1e1e1e; border: 2px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .form-panel h2 { color: #fff; margin-top: 0; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .form-grid label { color: #aaa; font-size: 13px; display: flex; flex-direction: column; gap: 6px; }
        .form-grid input, .form-grid select { padding: 10px; border-radius: 8px; border: 1px solid #444; background: #252538; color: #fff; }
        .alerta-ok { background: #1c3a24; border: 1px solid #4CAF50; color: #c8f0cf; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        .alerta-error { background: #3a1c1c; border: 1px solid #f44336; color: #f3c1c1; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        .modal-pago { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); align-items: center; justify-content: center; z-index: 50; padding: 16px; }
        .modal-pago.abierto { display: flex; }
        .modal-caja { background: #1e1e1e; border: 2px solid #FFD700; border-radius: 12px; padding: 24px; max-width: 420px; width: 100%; }
        .modal-caja h3 { color: #FFD700; margin-top: 0; }
        .modal-caja label { color: #aaa; font-size: 13px; display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
        .modal-caja input { padding: 10px; border-radius: 8px; border: 1px solid #444; background: #252538; color: #fff; }
        .modal-acciones { display: flex; gap: 10px; margin-top: 12px; }
        .ayuda-origen { color:#c8c8d0; font-size:13px; line-height:1.5; margin:0 0 13px; }
        .form-grid select:disabled { opacity:.5; cursor:not-allowed; }
    </style>
    
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>

<?php $paginaActiva = 'partidos'; View::cabecera($paginaActiva); ?>

<main class="envoltura envoltura-partidos">
    <h1 class="seccion-titulo">🎟️ <?= $esAdmin ? 'Gestionar partidos' : 'Ver partido' ?></h1>
    <p class="seccion-sub"><?= $esAdmin
        ? 'Crea nuevos partidos y revisa los existentes.'
        : 'Elige el partido que quieras ver y paga tu entrada para obtener el acceso.' ?></p>

    <?php if ($mensaje !== ''): ?>
        <div class="alerta-ok">✅ <?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alerta-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($esAdmin): ?>
    <div class="form-panel" style="margin-bottom:24px;">
        <h2 style="color:#fff;margin-top:0;">➕ Crear partido nuevo</h2>
        <?php if (count($equipos) < 2): ?>
            <p style="color:#aaa;margin:0;">Necesitas al menos dos equipos registrados para crear un partido.
               <a href="index.php?r=crear" style="color:var(--dorado);">Crea equipos aquí</a>.</p>
        <?php else: ?>
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="accion" value="crear_partido">
                <p class="ayuda-origen">Selecciona el origen: solo se pueden enfrentar equipos nacionales entre sí o internacionales entre sí.</p>
                <div class="form-grid">
                    <label>Origen del partido
                        <select id="origenPartido" name="origen_partido" required>
                            <option value="">Seleccionar origen…</option>
                            <option value="nacional" <?= ($_POST['origen_partido'] ?? '') === 'nacional' ? 'selected' : '' ?> <?= $equiposPorOrigen['nacional'] < 2 ? 'disabled' : '' ?>>Nacional (Perú) · <?= $equiposPorOrigen['nacional'] ?> equipos</option>
                            <option value="internacional" <?= ($_POST['origen_partido'] ?? '') === 'internacional' ? 'selected' : '' ?> <?= $equiposPorOrigen['internacional'] < 2 ? 'disabled' : '' ?>>Internacional · <?= $equiposPorOrigen['internacional'] ?> equipos</option>
                        </select>
                    </label>
                    <label>Equipo local
                        <select id="equipoLocalPartido" name="equipo_local_id" required disabled>
                            <option value="">Primero selecciona un origen…</option>
                            <?php foreach ($equipos as $eq): ?>
                                <option data-origen="<?= e($eq['origen']) ?>" value="<?= (int)$eq['id'] ?>" disabled <?= (int)($_POST['equipo_local_id'] ?? 0) === (int)$eq['id'] ? 'selected' : '' ?>><?= e($eq['nombre']) ?> (<?= e($eq['liga']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Equipo visitante
                        <select id="equipoVisitantePartido" name="equipo_visitante_id" required disabled>
                            <option value="">Primero selecciona un origen…</option>
                            <?php foreach ($equipos as $eq): ?>
                                <option data-origen="<?= e($eq['origen']) ?>" value="<?= (int)$eq['id'] ?>" disabled <?= (int)($_POST['equipo_visitante_id'] ?? 0) === (int)$eq['id'] ? 'selected' : '' ?>><?= e($eq['nombre']) ?> (<?= e($eq['liga']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Fecha y hora
                        <input type="datetime-local" name="fecha_hora" required>
                    </label>
                    <label>Estadio
                        <input type="text" name="estadio" placeholder="Ej. Estadio Central" required>
                    </label>
                    <label>Precio de entrada
                        <input type="number" name="precio_entrada" min="0" step="0.01" placeholder="Ej. 25.00" required>
                    </label>
                </div>
                <div style="margin-top:14px;">
                    <button type="submit" class="btn btn-primario"><span class="btn-ico">💾</span><span>Guardar partido</span></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!$esAdmin): ?>
    <?php $partidosParaCompra = array_filter(
        $partidos,
        static fn($partido) => partido_permite_compra($partido) && !isset($entradasCompradas[(int)$partido['id']])
    ); ?>
    <div class="form-panel">
        <?php if (!$partidosParaCompra): ?>
            <p style="color:#ffd36b;">No tienes partidos pendientes de compra. Puede que ya tengas una entrada o que las ventas estén cerradas. <a href="index.php?r=mis_entradas" style="color:#fff3ac;text-decoration:underline;">Ver mis entradas</a>.</p>
        <?php endif; ?>
        <form onsubmit="return false;">
            <div class="form-grid">
                <label>Partido a ver
                    <select id="selPartido" onchange="cargarPartido()">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($partidos as $p): ?>
                            <?php if (!partido_permite_compra($p)) continue; ?>
                            <?php if (isset($entradasCompradas[(int)$p['id']])): ?>
                                <option disabled>Entrada comprada · <?= e($p['local_nombre'] . ' vs ' . $p['visitante_nombre']) ?></option>
                                <?php continue; ?>
                            <?php endif; ?>
                            <option value="<?= (int)$p['id'] ?>"
                                    data-titulo="<?= htmlspecialchars($p['local_nombre'] . ' vs ' . $p['visitante_nombre'], ENT_QUOTES) ?>"
                                    data-fecha="<?= htmlspecialchars($p['fecha_hora'], ENT_QUOTES) ?>"
                                    data-estadio="<?= htmlspecialchars($p['estadio'], ENT_QUOTES) ?>"
                                    data-precio="<?= htmlspecialchars((string)$p['precio_entrada'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($p['local_nombre'] . ' vs ' . $p['visitante_nombre']) ?> · <?= htmlspecialchars($p['fecha_hora']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Fecha y hora
                    <input type="datetime-local" id="vFecha" readonly>
                </label>
                <label>Estadio
                    <input type="text" id="vEstadio" readonly>
                </label>
                <label>Precio de entrada
                    <input type="number" id="vPrecio" min="0" step="0.01" readonly>
                </label>
            </div>
            <div style="margin-top:14px;">
                <button type="button" class="btn btn-primario" onclick="pagarSeleccionado()" <?= !$partidosParaCompra ? 'disabled style="opacity:.5;cursor:not-allowed;"' : '' ?>>
                    <span class="btn-ico">💳</span><span>Pagar entrada del partido elegido</span>
                </button>
            </div>
        </form>
    </div>

    <?php endif; ?>

    <h2 class="seccion-titulo">📋 Partidos y resultados</h2>
    <?php if (count($partidos) === 0): ?>
        <div class="form-panel"><p style="color:#aaa;margin:0;"><?= $esAdmin ? 'Aún no hay partidos registrados. Puedes crear el primero arriba.' : 'Aún no hay partidos disponibles.' ?></p></div>
    <?php else: ?>
        <div class="grid-partidos">
            <?php foreach ($partidos as $p): ?>
                <div class="card-partido">
                    <div class="partido-marcador">
                        <span class="marcador-eq"><?= e($p['local_nombre']) ?></span>
                        <span class="marcador-cifra"><?= ($p['estado'] ?? 'Programado') === 'Programado' ? '— : —' : ((int)$p['goles_local'] . ' : ' . (int)$p['goles_visitante']) ?></span>
                        <span class="marcador-eq"><?= e($p['visitante_nombre']) ?></span>
                    </div>
                    <p class="marcador-estado"><?= e($p['estado'] ?? 'Programado') ?><?= ($p['estado'] ?? 'Programado') !== 'Programado' ? (((int)$p['goles_local'] > (int)$p['goles_visitante']) ? ' · Va ganando: '. e($p['local_nombre']) : (((int)$p['goles_visitante'] > (int)$p['goles_local']) ? ' · Va ganando: '.e($p['visitante_nombre']) : ' · Empate')) : ' · Sin marcador todavía' ?></p>
                    <p class="meta">📅 <?= htmlspecialchars($p['fecha_hora']) ?></p>
                    <p class="meta">🏟️ <?= htmlspecialchars($p['estadio']) ?></p>
                    <p class="meta">🎫 Entradas vendidas: <?= (int)$p['entradas_vendidas'] ?></p>
                    <p class="precio">S/ <?= number_format((float)$p['precio_entrada'], 2) ?></p>
                    <?php if ($esAdmin): ?>
                        <details class="marcador-admin">
                            <summary>Editar resultado</summary>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="accion" value="actualizar_resultado">
                                <input type="hidden" name="partido_id" value="<?= (int)$p['id'] ?>">
                                <div class="campos-marcador">
                                    <label>Estado
                                        <select name="estado" required>
                                            <?php foreach (['Programado','En juego','Finalizado'] as $estadoOpcion): ?>
                                            <option value="<?= e($estadoOpcion) ?>" <?= ($p['estado'] ?? 'Programado') === $estadoOpcion ? 'selected' : '' ?>><?= e($estadoOpcion) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>Goles local <input name="goles_local" type="number" min="0" max="99" value="<?= (int)$p['goles_local'] ?>" required></label>
                                    <label>Goles visitante <input name="goles_visitante" type="number" min="0" max="99" value="<?= (int)$p['goles_visitante'] ?>" required></label>
                                </div>
                                <button type="submit" class="btn btn-primario">Guardar resultado</button>
                            </form>
                        </details>
                    <?php else: ?>
                        <?php if (isset($entradasCompradas[(int)$p['id']])): ?>
                            <p class="estado-entrada-comprada" role="status">✓ Entrada comprada · Código generado
                                <a href="index.php?r=mis_entradas">Ver mi entrada</a>
                            </p>
                        <?php elseif (partido_permite_compra($p)): ?>
                            <button type="button" class="btn btn-primario btn-ancho"
                                onclick="abrirPago(<?= (int)$p['id'] ?>, '<?= htmlspecialchars($p['local_nombre'] . ' vs ' . $p['visitante_nombre'], ENT_QUOTES) ?>', <?= (float)$p['precio_entrada'] ?>)">
                                <span class="btn-ico">💳</span><span>Pagar para ver</span>
                            </button>
                        <?php else: ?>
                            <p class="estado-compra-cerrada" role="status"><?= e(texto_compra_no_disponible($p)) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php View::fragment('registro_entradas', get_defined_vars()); ?>
<?php if ($esAdmin): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const origen = document.getElementById('origenPartido');
    const local = document.getElementById('equipoLocalPartido');
    const visitante = document.getElementById('equipoVisitantePartido');
    if (!origen || !local || !visitante) return;

    function actualizarEquipos(mantenerSeleccion = false) {
        const categoria = origen.value;
        for (const select of [local, visitante]) {
            const anterior = mantenerSeleccion ? select.value : '';
            select.disabled = !categoria;
            select.options[0].textContent = categoria ? 'Seleccionar equipo…' : 'Primero selecciona un origen…';
            for (const opcion of Array.from(select.options).slice(1)) {
                const corresponde = opcion.dataset.origen === categoria;
                opcion.disabled = !corresponde;
                opcion.hidden = !corresponde;
            }
            const candidato = Array.from(select.options).find(op => op.value === anterior && !op.disabled);
            select.value = candidato && anterior ? anterior : '';
        }
        // Un equipo no puede enfrentarse consigo mismo.
        for (const opcion of Array.from(visitante.options).slice(1)) {
            if (opcion.dataset.origen === categoria && opcion.value === local.value && local.value !== '') {
                opcion.disabled = true;
                opcion.hidden = true;
            }
        }
        if (visitante.value === local.value && local.value !== '') visitante.value = '';
    }

    origen.addEventListener('change', () => actualizarEquipos());
    local.addEventListener('change', () => {
        const visitanteAnterior = visitante.value;
        for (const opcion of Array.from(visitante.options).slice(1)) {
            const corresponde = opcion.dataset.origen === origen.value && opcion.value !== local.value;
            opcion.disabled = !corresponde;
            opcion.hidden = !corresponde;
        }
        if (visitanteAnterior === local.value) visitante.value = '';
    });
    actualizarEquipos(true);
});
</script>
<?php endif; ?>

</body>
</html>
