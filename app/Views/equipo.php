<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha del Equipo - <?= htmlspecialchars($equipo['nombre']) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    
    
    
    

    <?php View::script('animaciones'); ?>

    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            margin: 0;
            padding: 20px;
        }

        .header-equipo {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #1e1e2e, #2d2d44);
            border-radius: 12px;
            border: 3px solid <?= $color ?>;
        }

        .header-equipo .info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .header-equipo h1 {
            margin: 0;
            font-size: 28px;
            color: <?= $color ?>;
            font-weight: 900;
        }

        .header-equipo .subtitulo {
            margin: 0;
            color: #aaa;
            font-size: 14px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: #1e1e1e;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #333;
            text-align: center;
        }

        .stats-card .numero {
            font-size: 32px;
            font-weight: 900;
            color: <?= $color ?>;
        }

        .stats-card .etiqueta {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
            color: <?= $color ?>;
        }

        .posicion-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .posicion-Portero { background: #bbdefb; color: #0d47a1; }
        .posicion-Defensas { background: #c8e6c9; color: #1b5e20; }
        .posicion-Centrocampistas { background: #ffe082; color: #e65100; }
        .posicion-Delanteros { background: #ffcdd2; color: #b71c1c; }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            overflow: auto;
        }

        .modal-contenido {
            background: #1e1e1e;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            border: 3px solid <?= $color ?>;
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .modal-contenido h2 {
            margin-top: 0;
            color: <?= $color ?>;
            font-size: 22px;
        }

        .modal-cerrar {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #888;
        }

        .modal-cerrar:hover {
            color: #f44336;
        }

        .formulario-jugador {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .formulario-jugador .campo-jugador { display:flex; flex-direction:column; gap:5px; }
        .formulario-jugador .campo-jugador label { color:#f0f0f0; font-weight:700; font-size:13px; }
        .formulario-jugador .campo-jugador small { color:#a9a9b5; font-size:12px; line-height:1.4; }
        .formulario-jugador .estadisticas-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        @media (max-width:520px) { .formulario-jugador .estadisticas-grid { grid-template-columns:1fr; } }
        .escudo-acciones { display:flex; flex-direction:column; gap:8px; margin-top:12px; max-width:360px; }
        .escudo-acciones label { font-size:13px; color:#ddd; font-weight:700; }
        .escudo-acciones input[type=file] { color:#ddd; max-width:100%; font-size:12px; }
        .escudo-acciones button { width:max-content; padding:8px 12px; border-radius:8px; border:0; background:#ff9800; color:#151515; font-weight:800; cursor:pointer; }
        .escudo-acciones small { color:#aaa; }
        .escudo-respaldo { width:80px; height:80px; display:grid; place-items:center; flex-shrink:0; border:2px dashed #777; border-radius:8px; color:#aaa; font-size:11px; text-align:center; padding:5px; }
        .formulario-jugador input,
        .formulario-jugador select {
            width: 100%;
            padding: 10px;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 6px;
            color: #e0e0e0;
            font-size: 14px;
        }

        .btn-guardar-jugador {
            padding: 12px 20px;
            background: <?= $color ?>;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-guardar-jugador:hover {
            background: #1976D2;
        }

        .btn-volver {
            display: inline-block;
            margin-bottom: 20px;
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-volver:hover {
            color: #fff;
        }

        .popup {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            color: #fff;
            padding: 15px 20px;
            border-radius: 6px;
            z-index: 2000;
            font-size: 14px;
        }

        /* ===== Plantilla en tarjetas con Logros ===== */
        .jugadores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
        }
        .jugador-card {
            background: #1e1e1e;
            border: 2px solid #2a2a3e;
            border-radius: 14px;
            padding: 16px;
            position: relative;
            overflow: hidden;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }
        .jugador-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--pos-color, #555);
        }
        .jugador-card:hover {
            transform: translateY(-4px);
            border-color: <?= $color ?>;
            box-shadow: 0 10px 26px rgba(0,0,0,.45);
        }
        .jugador-card.pos-portero { --pos-color: #2196F3; }
        .jugador-card.pos-defensas { --pos-color: #43a047; }
        .jugador-card.pos-centrocampistas { --pos-color: #fb8c00; }
        .jugador-card.pos-delanteros { --pos-color: #e53935; }
        .jugador-top {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .jugador-avatar {
            width: 48px; height: 48px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 20px; color: #fff;
            background: var(--pos-color, #555);
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,.35);
        }
        .jugador-datos { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .jugador-nombre {
            font-weight: 700; font-size: 16px; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .logros-titulo {
            font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
            color: #888; margin-bottom: 9px; font-weight: 700;
        }
        .logros-lista { display: flex; flex-wrap: wrap; gap: 7px; }
        .logro {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 10px; border-radius: 999px; font-size: 12.5px;
            background: #262638; color: #9a9aae; border: 1px solid #333;
            opacity: .55; transition: .18s;
        }
        .logro b { font-weight: 800; font-size: 13px; }
        .logro.on { opacity: 1; }
        .logro-goles.on    { background: rgba(67,160,71,.16);  border-color: #43a047; color: #a5d6a7; }
        .logro-asist.on    { background: rgba(33,150,243,.16); border-color: #2196F3; color: #90caf9; }
        .logro-mvp.on      { background: rgba(255,193,7,.16);  border-color: #ffc107; color: #ffe082; }
        .logro-amarilla.on { background: rgba(253,216,53,.14); border-color: #fdd835; color: #fff59d; }
        .logro-roja.on     { background: rgba(229,57,53,.16);  border-color: #e53935; color: #ef9a9a; }

        /* Leyenda de estadísticas visible para administradores y usuarios */
        .leyenda-estadisticas {
            margin: 0 0 20px;
            padding: 17px 19px;
            background: #1e1e1e;
            border: 1px solid #38383f;
            border-left: 3px solid #ff9800;
            border-radius: 12px;
        }
        .leyenda-estadisticas-titulo {
            margin: 0 0 5px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }
        .leyenda-estadisticas-intro {
            margin: 0 0 14px;
            color: #bdbdc5;
            font-size: 13px;
            line-height: 1.5;
        }
        .leyenda-estadisticas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
            gap: 10px 16px;
        }
        .leyenda-estadisticas-item {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #e4e4e9;
            font-size: 13px;
            line-height: 1.35;
        }
        .leyenda-estadisticas-icono {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            border: 1px solid #3a3a46;
            border-radius: 9px;
            background: #282831;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }
        .leyenda-estadisticas-nota {
            margin: 13px 0 0;
            color: #a9a9b5;
            font-size: 12px;
            line-height: 1.5;
        }
        .btn-editar {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%; margin-top: 16px;
            padding: 10px 14px; border: none; border-radius: 9px;
            background: #ff9800; color: #fff; font-weight: 700; font-size: 13.5px;
            cursor: pointer; transition: background .18s;
        }
        .btn-editar:hover { background: #f57c00; }
    </style>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>

<a href="index.php?r=ver" class="btn-volver">&#8592; Volver a la lista de equipos</a>

<div class="header-equipo">
    <div class="info">
        <div style="display:flex;align-items:center;gap:15px;">
            <?php if ($tieneEscudo): ?>
                <img src="<?= e($srcEscudo) ?>" alt="Escudo de <?= e($equipo['nombre']) ?>"
                     style="width:80px;height:80px;border-radius:8px;border:2px solid <?= $color ?>;object-fit:contain;">
            <?php else: ?>
                <div class="escudo-respaldo">Sin escudo</div>
            <?php endif; ?>
            <div>
                <h1><?= htmlspecialchars($equipo['nombre']) ?></h1>
                <p class="subtitulo"><?= origen_equipo($equipo) === 'nacional' ? 'Nacional · Perú · ' : 'Internacional · ' ?><?= htmlspecialchars($equipo['liga']) ?><?= trim($equipo['anio'] ?? '') !== '' ? ' &middot; ' . htmlspecialchars($equipo['anio']) : '' ?></p>
                <?php if (es_admin()): ?>
                    <form class="escudo-acciones" action="index.php?r=guardar_ficha" method="POST" enctype="multipart/form-data">
                        <?= csrf_input() ?>
                        <input type="hidden" name="accion" value="actualizar_escudo">
                        <input type="hidden" name="equipo_id" value="<?= (int)$id ?>">
                        <label for="escudo-equipo"><?= $tieneEscudo ? 'Cambiar escudo' : 'Añadir escudo del equipo' ?></label>
                        <input type="file" id="escudo-equipo" name="escudo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <small>JPG, PNG o WEBP. Máximo 5 MB.</small>
                        <button type="submit">Guardar escudo</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="cards-grid">
    <div class="stats-card">
        <div class="numero"><?= count($jugadores) ?></div>
        <div class="etiqueta">Jugadores</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_goles ?></div>
        <div class="etiqueta">Goles Totales</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_asistencias ?></div>
        <div class="etiqueta">Asistencias</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_amarillas ?></div>
        <div class="etiqueta">Tarjetas Amarillas</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_rojas ?></div>
        <div class="etiqueta">Tarjetas Rojas</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= $total_mvp ?></div>
        <div class="etiqueta">MVP del Partido</div>
    </div>
    <div class="stats-card">
        <div class="numero"><?= (int)$total_trofeos ?></div>
        <div class="etiqueta">Trofeos ganados (jugadores)</div>
    </div>
</div>

<?= mostrar_formacion_equipo($jugadores) ?>

<div class="section">
    <h2>Plantilla del Equipo</h2>

    <?php if (count($jugadores) > 0): ?>
    <aside class="leyenda-estadisticas" aria-label="Leyenda de los iconos de estadísticas de jugadores">
        <h3 class="leyenda-estadisticas-titulo">¿Qué significan los íconos?</h3>
        <p class="leyenda-estadisticas-intro">Cada número corresponde a una estadística del jugador:</p>
        <div class="leyenda-estadisticas-grid">
            <div class="leyenda-estadisticas-item"><span class="leyenda-estadisticas-icono" aria-hidden="true">⚽</span><span><strong>Goles:</strong> goles anotados.</span></div>
            <div class="leyenda-estadisticas-item"><span class="leyenda-estadisticas-icono" aria-hidden="true">🎯</span><span><strong>Asistencias:</strong> pases que terminaron en gol.</span></div>
            <div class="leyenda-estadisticas-item"><span class="leyenda-estadisticas-icono" aria-hidden="true">⭐</span><span><strong>MVP:</strong> veces elegido mejor jugador del partido.</span></div>
            <div class="leyenda-estadisticas-item"><span class="leyenda-estadisticas-icono" aria-hidden="true">🏆</span><span><strong>Trofeos:</strong> títulos o campeonatos ganados.</span></div>
            <div class="leyenda-estadisticas-item"><span class="leyenda-estadisticas-icono" aria-hidden="true">🟨</span><span><strong>Tarjetas amarillas:</strong> amonestaciones recibidas.</span></div>
            <div class="leyenda-estadisticas-item"><span class="leyenda-estadisticas-icono" aria-hidden="true">🟥</span><span><strong>Tarjetas rojas:</strong> expulsiones recibidas.</span></div>
        </div>
        <p class="leyenda-estadisticas-nota">Si un ícono muestra <strong>0</strong> o está atenuado, significa que no tiene registros de esa estadística.</p>
    </aside>
    <?php endif; ?>

    <?php if (count($jugadores) === 0): ?>
        <div style="background:#1e1e1e;padding:30px;border-radius:8px;border:2px dashed #555;text-align:center;color:#888;">
            No hay jugadores registrados todavía.<br>
            <?php if (es_admin()): ?>
            <button type="button" class="btn-guardar-jugador" onclick="abrirModalAgregar()">&#128194; Agregar primer jugador</button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="jugadores-grid">
            <?php foreach ($jugadores as $j):
                $posicion = $j['posicion'];
                $clase = match($posicion) {
                    'Portero' => 'posicion-Portero',
                    'Defensas' => 'posicion-Defensas',
                    'Centrocampistas' => 'posicion-Centrocampistas',
                    'Delanteros' => 'posicion-Delanteros',
                    default => ''
                };
                $posTema = match($posicion) {
                    'Portero' => 'pos-portero',
                    'Defensas' => 'pos-defensas',
                    'Centrocampistas' => 'pos-centrocampistas',
                    'Delanteros' => 'pos-delanteros',
                    default => 'pos-defensas'
                };
                $inicial = strtoupper(substr($j['nombre'], 0, 1));
                $goles  = (int)$j['goles'];
                $asis   = (int)$j['asistencias'];
                $amar   = (int)$j['tarjetas_amarillas'];
                $rojas  = (int)$j['tarjetas_rojas'];
                $mvp    = (int)$j['mvp_count'];
                $trofeos = (int)$j['trofeos_ganados'];
            ?>
            <div class="jugador-card <?= $posTema ?>">
                <div class="jugador-top">
                    <div class="jugador-avatar"><?= htmlspecialchars($inicial) ?></div>
                    <div class="jugador-datos">
                        <span class="jugador-nombre"><?= htmlspecialchars($j['nombre']) ?></span>
                        <span class="posicion-badge <?= $clase ?>"><?= htmlspecialchars($posicion) ?></span>
                    </div>
                </div>

                <div class="logros">
                    <div class="logros-titulo">🏅 Logros</div>
                    <div class="logros-lista">
                        <span class="logro logro-goles <?= $goles > 0 ? 'on' : '' ?>" title="Goles anotados">⚽ <b><?= $goles ?></b></span>
                        <span class="logro logro-asist <?= $asis > 0 ? 'on' : '' ?>" title="Asistencias">🎯 <b><?= $asis ?></b></span>
                        <span class="logro logro-mvp <?= $mvp > 0 ? 'on' : '' ?>" title="Veces elegido mejor jugador (MVP)">⭐ <b><?= $mvp ?></b></span>
                        <span class="logro logro-mvp <?= $trofeos > 0 ? 'on' : '' ?>" title="Trofeos ganados">🏆 <b><?= $trofeos ?></b></span>
                        <span class="logro logro-amarilla <?= $amar > 0 ? 'on' : '' ?>" title="Tarjetas amarillas">🟨 <b><?= $amar ?></b></span>
                        <span class="logro logro-roja <?= $rojas > 0 ? 'on' : '' ?>" title="Tarjetas rojas">🟥 <b><?= $rojas ?></b></span>
                    </div>
                </div>

                <?php if (es_admin()): ?>
                <button type="button" class="btn-editar" onclick="abrirModalEditar(<?= (int)$j['id'] ?>, '<?= addslashes(htmlspecialchars($j['nombre'])) ?>', '<?= addslashes(htmlspecialchars($posicion)) ?>', <?= $goles ?>, <?= $asis ?>, <?= $amar ?>, <?= $rojas ?>, <?= $mvp ?>, <?= $trofeos ?>)">
                    ✎ Editar
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (es_admin()): ?>
    <div style="margin-top:15px;text-align:center;">
        <button type="button" class="btn-guardar-jugador" onclick="abrirModalAgregar()">
            &#128194; Agregar Jugador
        </button>
    </div>
</div>

<?php endif; ?>

<?php if (es_admin()): ?>
<!-- MODAL: Agregar Jugador -->
<div id="modalAgregar" class="modal">
    <div class="modal-contenido">
        <span class="modal-cerrar" onclick="cerrarModal('modalAgregar')">&#10005;</span>
        <h2>&#128194; Nuevo Jugador</h2>

<form id="formAgregarJugador" method="POST" action="index.php?r=guardar_ficha">
            <?= csrf_input() ?>
            <input type="hidden" name="accion" value="agregar_jugador">
            <input type="hidden" name="equipo_id" value="<?= $id ?>">

            <div class="formulario-jugador">
                <div class="campo-jugador"><label for="nuevo_nombre">Nombre del jugador</label><input type="text" id="nuevo_nombre" name="nombre" placeholder="Ej. Ramírez" required></div>
                <div class="campo-jugador"><label for="nueva_posicion">Posición</label><select id="nueva_posicion" name="posicion" required>
                    <option value="">Selecciona posición</option>
                    <option value="Portero">Portero</option><option value="Defensas">Defensas</option>
                    <option value="Centrocampistas">Centrocampistas</option><option value="Delanteros">Delanteros</option>
                </select></div>
                <div class="estadisticas-grid">
                    <div class="campo-jugador"><label for="nuevo_goles">Goles</label><input type="number" id="nuevo_goles" name="goles" placeholder="Ej. 3" value="0" min="0" required><small>Goles que ha anotado.</small></div>
                    <div class="campo-jugador"><label for="nuevo_asistencias">Asistencias</label><input type="number" id="nuevo_asistencias" name="asistencias" placeholder="Ej. 2" value="0" min="0" required><small>Pases que terminaron en gol.</small></div>
                    <div class="campo-jugador"><label for="nuevo_amarillas">Tarjetas amarillas</label><input type="number" id="nuevo_amarillas" name="tarjetas_amarillas" placeholder="Ej. 1" value="0" min="0" required><small>Amonestaciones recibidas.</small></div>
                    <div class="campo-jugador"><label for="nuevo_rojas">Tarjetas rojas</label><input type="number" id="nuevo_rojas" name="tarjetas_rojas" placeholder="Ej. 0" value="0" min="0" required><small>Expulsiones recibidas.</small></div>
                    <div class="campo-jugador"><label for="nuevo_mvp">Veces MVP</label><input type="number" id="nuevo_mvp" name="mvp_count" placeholder="Ej. 1" value="0" min="0" required><small>Veces elegido mejor jugador del partido.</small></div>
                    <div class="campo-jugador"><label for="nuevo_trofeos">Trofeos ganados</label><input type="number" id="nuevo_trofeos" name="trofeos_ganados" placeholder="Ej. 2" value="0" min="0" required><small>Campeonatos y títulos obtenidos.</small></div>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalAgregar')">Cancelar</button>
                <button type="submit" class="btn-guardar-jugador">&#10132; Guardar Jugador</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Editar Jugador -->
<div id="modalEditar" class="modal">
    <div class="modal-contenido">
        <span class="modal-cerrar" onclick="cerrarModal('modalEditar')">&#10005;</span>
        <h2>&#128222; Editar Jugador</h2>
<form id="formEditarJugador" method="POST" action="index.php?r=guardar_ficha">
            <?= csrf_input() ?>
            <input type="hidden" name="accion" value="editar_jugador">
            <input type="hidden" name="id_jugador" id="input_id_jugador">
            <input type="hidden" name="equipo_id" value="<?= $id ?>">

            <div class="formulario-jugador">
                <div class="campo-jugador"><label for="input_nombre_editar">Nombre del jugador</label><input type="text" id="input_nombre_editar" name="nombre" placeholder="Ej. Ramírez" required></div>
                <div class="campo-jugador"><label for="input_posicion_editar">Posición</label><select name="posicion" id="input_posicion_editar" required>
                    <option value="Portero">Portero</option><option value="Defensas">Defensas</option>
                    <option value="Centrocampistas">Centrocampistas</option><option value="Delanteros">Delanteros</option>
                </select></div>
                <div class="estadisticas-grid">
                    <div class="campo-jugador"><label for="input_goles_editar">Goles</label><input type="number" name="goles" id="input_goles_editar" placeholder="Ej. 3" min="0" required><small>Goles que ha anotado.</small></div>
                    <div class="campo-jugador"><label for="input_asistencias_editar">Asistencias</label><input type="number" name="asistencias" id="input_asistencias_editar" placeholder="Ej. 2" min="0" required><small>Pases que terminaron en gol.</small></div>
                    <div class="campo-jugador"><label for="input_amarillas_editar">Tarjetas amarillas</label><input type="number" name="tarjetas_amarillas" id="input_amarillas_editar" placeholder="Ej. 1" min="0" required><small>Amonestaciones recibidas.</small></div>
                    <div class="campo-jugador"><label for="input_rojas_editar">Tarjetas rojas</label><input type="number" name="tarjetas_rojas" id="input_rojas_editar" placeholder="Ej. 0" min="0" required><small>Expulsiones recibidas.</small></div>
                    <div class="campo-jugador"><label for="input_mvp_editar">Veces MVP</label><input type="number" name="mvp_count" id="input_mvp_editar" placeholder="Ej. 1" min="0" required><small>Veces elegido mejor jugador del partido.</small></div>
                    <div class="campo-jugador"><label for="input_trofeos_editar">Trofeos ganados</label><input type="number" name="trofeos_ganados" id="input_trofeos_editar" placeholder="Ej. 2" min="0" required><small>Campeonatos y títulos obtenidos.</small></div>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn-secundario" onclick="cerrarModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn-guardar-jugador">&#10132; Actualizar</button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<!--Popup de mensaje-->
<div id="popup" class="popup">&#10003; &#10003; ¡Operación exitosa!</div>

<script>
    // Abre el modal de agregar
    function abrirModalAgregar() {
        document.getElementById('modalAgregar').style.display = 'block';
        document.getElementById('formAgregarJugador').reset();
    }

    // Abre el modal de editar
    function abrirModalEditar(id, nombre, posicion, goles, asistencias, amarillas, rojas, mvp, trofeos) {
        document.getElementById('modalEditar').style.display = 'block';
        document.getElementById('input_id_jugador').value = id;
        document.getElementById('input_nombre_editar').value = nombre;
        document.getElementById('input_posicion_editar').value = posicion;
        document.getElementById('input_goles_editar').value = goles || 0;
        document.getElementById('input_asistencias_editar').value = asistencias || 0;
        document.getElementById('input_amarillas_editar').value = amarillas || 0;
        document.getElementById('input_rojas_editar').value = rojas || 0;
        document.getElementById('input_mvp_editar').value = mvp || 0;
        document.getElementById('input_trofeos_editar').value = trofeos || 0;
    }

    function cerrarModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera del contenido
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Mostrar popup
    const params = new URLSearchParams(window.location.search);
    const msg = params.get('mensaje');
    if (msg) {
        const popup = document.getElementById('popup');
        popup.style.display = 'block';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 3000);
    }
</script>
</body>
</html>
