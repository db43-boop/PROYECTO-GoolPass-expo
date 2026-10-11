<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo - GoolPass</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="public/css/app_common.css">
    <link rel="stylesheet" href="public/css/crear.css">
    <link rel="stylesheet" href="public/css/ligas.css">
    <script src="public/js/ligas.js" defer></script>
    <link rel="stylesheet" href="public/css/estilos_fichas.css">
    <link rel="stylesheet" href="public/css/animaciones.css">

    <script src="public/js/animaciones.js"></script>
    <script src="public/js/antireload.js"></script>
    <style>
        .seccion-jugadores { margin-top: 8px; border-top: 1px solid var(--panel-borde, rgba(255,255,255,.1)); padding-top: 18px; }
        .seccion-jugadores > h3 { font-family: var(--fuente, 'Outfit'); font-size: 17px; font-weight: 800; margin: 0 0 4px; color: #fff; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .seccion-jugadores .hint { color: var(--tenue, #9a9aae); font-size: 12.5px; font-weight: 500; margin: 0 0 14px; }
        .jugador-fila { display: grid; grid-template-columns: 1.6fr 1.3fr auto; gap: 10px; align-items: end; padding: 12px; margin-bottom: 10px; background: rgba(0,0,0,.28); border: 1px solid var(--panel-borde, rgba(255,255,255,.1)); border-radius: 12px; }
        .jugador-fila .campo { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
        .jugador-fila .campo label { color: var(--tenue, #9a9aae); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
        .jugador-fila input, .jugador-fila select { width: 100%; padding: 9px 10px; border-radius: 9px; border: 1px solid rgba(255,255,255,.14); background: rgba(0,0,0,.35); color: #fff; font-family: inherit; font-size: 13.5px; }
        .jugador-fila input:focus, .jugador-fila select:focus { outline: none; border-color: rgba(255,122,51,.65); }
        .btn-quitar-jug { border: none; background: rgba(211,47,47,.25); color: #ff8a80; width: 38px; height: 38px; border-radius: 9px; cursor: pointer; font-size: 16px; line-height: 1; transition: .2s; }
        .btn-quitar-jug:hover { background: rgba(211,47,47,.45); color: #fff; }
        .btn-agregar-jug { margin-top: 4px; border: 1px dashed rgba(255,122,51,.55); background: rgba(255,122,51,.08); color: var(--naranja-2, #ff7a33); font-weight: 700; font-size: 14px; padding: 11px 16px; border-radius: 10px; cursor: pointer; font-family: var(--fuente, 'Outfit'); transition: .2s; }
        .btn-agregar-jug:hover { background: rgba(255,122,51,.16); }
        .sin-jugadores { color: var(--tenue, #9a9aae); font-size: 13.5px; background: rgba(0,0,0,.2); border: 1px dashed var(--panel-borde, rgba(255,255,255,.1)); border-radius: 12px; padding: 16px; text-align: center; margin: 0; }
        .aviso-editar { margin-bottom: 18px; padding: 13px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; }
        .aviso-editar.error { background: rgba(211,47,47,.14); border: 1px solid rgba(211,47,47,.35); color: #ffb4b4; }
        .aviso-editar.exito { background: rgba(53,208,127,.12); border: 1px solid rgba(53,208,127,.32); color: #9df3c6; }
        .acciones-form { display: flex; gap: 12px; margin-top: 22px; flex-wrap: wrap; }
        .btn-secundario { flex: 1; text-align: center; padding: 15px; border-radius: 999px; border: 1px solid var(--panel-borde); background: rgba(255,255,255,.05); color: var(--texto); font-family: var(--fuente); font-weight: 600; font-size: 15px; transition: .2s; }
        .btn-secundario:hover { background: rgba(255,255,255,.1); }
        .btn-submit { flex: 2; }
        .avatar-placeholder-preview { width: 100%; height: 190px; display: flex; align-items: center; justify-content: center; font-family: var(--fuente); font-size: 64px; font-weight: 900; color: rgba(255,255,255,.9); text-shadow: 0 4px 18px rgba(0,0,0,.5); }
        @media (max-width: 700px) { .jugador-fila { grid-template-columns: 1fr 1fr; } .jugador-fila .campo-ancho { grid-column: 1 / -1; } }
    </style>
    <link rel="stylesheet" href="public/css/cabecera.css">
</head>
<body>

<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>

<?php $paginaActiva = 'editar'; require GOOLPASS_ROOT . '/app/Views/partials/cabecera.php'; ?>

<div class="cabecera">
    <div>
        <h1>Editar <span class="gradiente">Equipo</span></h1>
        <p>Modifica los datos del club y gestiona su plantilla de jugadores.</p>
    </div>
    <a href="ver.php" class="volver">
        <span aria-hidden="true">&#8592;</span> Volver a la lista
    </a>
</div>


<main class="envoltura">
    <div class="container-crear">
        <div class="form-panel">
            <h2><span class="ico" aria-hidden="true">&#9998;&#65039;</span> Datos del Equipo</h2>

            <?php if ($mensaje !== ""): ?>
                <div class="aviso-editar <?= $tipo_mensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="accion_editar" value="<?= (int)$id ?>">

                <div class="form-group">
                    <label for="in_nombre">Nombre del Club <span class="req">*</span></label>
                    <input type="text" name="nombre" id="in_nombre" required
                           value="<?= htmlspecialchars($equipo['nombre']) ?>">
                </div>

                <div class="form-group">
                    <label>Origen del equipo <span class="req">*</span></label>
                    <div class="goolpass-origen" role="radiogroup" aria-label="Origen del equipo">
                        <label><input type="radio" name="origen" value="nacional" <?= $origenActual === 'nacional' ? 'checked' : '' ?>> Nacional (Perú)</label>
                        <label><input type="radio" name="origen" value="internacional" <?= $origenActual === 'internacional' ? 'checked' : '' ?>> Internacional</label>
                    </div>
                    <small class="opcional">Para clubes peruanos, puedes elegir una liga existente o escribir otra.</small>
                </div>
                <div class="form-group" id="bloqueLigaInternacional">
                    <label for="in_liga">Liga internacional <span class="req">*</span></label>
                    <select name="liga" id="in_liga" required>
                        <option value="">-- Selecciona una liga --</option>
                        <?php foreach (ligas_internacionales() as $lg): ?>
                        <option value="<?= e($lg) ?>" <?= $ligaActual === $lg ? 'selected' : '' ?>><?= e($lg) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="bloqueLigaNacional" hidden>
                    <label for="ligaNacional">Liga nacional (Perú) <span class="req">*</span></label>
                    <select name="liga_nacional" id="ligaNacional" disabled>
                        <option value="">-- Selecciona una liga peruana --</option>
                        <?php foreach (ligas_nacionales() as $lg): ?>
                        <option value="<?= e($lg) ?>" <?= $ligaActual === $lg ? 'selected' : '' ?>><?= e($lg) ?></option>
                        <?php endforeach; ?>
                        <option value="__otra__" <?= $origenActual === 'nacional' && !in_array($ligaActual, ligas_nacionales(), true) && $ligaActual !== '' ? 'selected' : '' ?>>Otra liga nacional (escribir)</option>
                    </select>
                    <div id="bloqueLigaOtra" class="liga-otra" hidden>
                        <label for="ligaOtra">Escribe el nombre de la liga</label>
                        <input type="text" name="liga_nacional_otra" id="ligaOtra" maxlength="90" placeholder="Ej. Liga Distrital de Lima" value="<?= e($origenActual === 'nacional' && !in_array($ligaActual, ligas_nacionales(), true) ? $ligaActual : '') ?>" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label for="in_anio">Año del Club</label>
                    <input type="number" name="anio" id="in_anio" min="1800" max="2100" placeholder="Ej. 1902"
                           value="<?= htmlspecialchars($equipo['anio'] ?? '') ?>">
                    <small class="opcional">Año de fundación del club (entre 1800 y 2100). Puedes dejarlo vacío.</small>
                </div>

                <div class="seccion-jugadores">
                    <h3>&#128101; Plantilla de jugadores <span style="color:var(--tenue,#9a9aae);font-weight:500;font-size:12px;">(<?= count($jugadores) ?>)</span></h3>
                    <p class="hint">Edita, agrega o quita jugadores. Sus estadísticas se conservan; puedes ampliarlas desde la ficha de cada uno.</p>

                    <div id="listaJugadores">
                        <?php foreach ($jugadores as $jIndex => $j): ?>
                        <div class="jugador-fila">
                            <div class="campo campo-ancho">
                                <label>Nombre</label>
                                <input type="text" name="jugadores[<?= (int)$jIndex ?>][nombre]" value="<?= htmlspecialchars($j['nombre']) ?>" placeholder="Nombre del jugador">
                            </div>
                            <div class="campo">
                                <label>Posición</label>
                                <select name="jugadores[<?= (int)$jIndex ?>][posicion]">
                                    <?= opciones_posicion($j['posicion']) ?>
                                </select>
                            </div>
                            <button type="button" class="btn-quitar-jug" onclick="quitarJugador(this)" title="Quitar jugador">&#10005;</button>
                            <input type="hidden" name="jugadores[<?= (int)$jIndex ?>][goles]" value="<?= (int)$j['goles'] ?>">
                            <input type="hidden" name="jugadores[<?= (int)$jIndex ?>][asistencias]" value="<?= (int)$j['asistencias'] ?>">
                            <input type="hidden" name="jugadores[<?= (int)$jIndex ?>][tarjetas_amarillas]" value="<?= (int)$j['tarjetas_amarillas'] ?>">
                            <input type="hidden" name="jugadores[<?= (int)$jIndex ?>][tarjetas_rojas]" value="<?= (int)$j['tarjetas_rojas'] ?>">
                            <input type="hidden" name="jugadores[<?= (int)$jIndex ?>][mvp_count]" value="<?= (int)$j['mvp_count'] ?>">
                            <input type="hidden" name="jugadores[<?= (int)$jIndex ?>][trofeos_ganados]" value="<?= (int)$j['trofeos_ganados'] ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <p id="sinJugadores" class="sin-jugadores" <?= count($jugadores) > 0 ? 'style="display:none;"' : '' ?>>Aún no hay jugadores en la plantilla.</p>

                    <button type="button" class="btn-agregar-jug" onclick="agregarJugador()">&#10133; Agregar jugador</button>
                </div>

                <div class="acciones-form">
                    <button type="submit" class="btn-submit">&#10132; Guardar Cambios</button>
                    <a href="ver.php" class="btn-secundario">Cancelar</a>
                </div>
            </form>
        </div>

        <aside class="preview-panel">
            <h2>
                <span class="ico" aria-hidden="true">&#128065;</span> Vista previa
                <span class="vivo"><span class="punto"></span> En vivo</span>
            </h2>

            <div class="card" id="previewCard">
                <div class="img-wrapper">
                    <?php if (!empty($equipo['escudo']) && is_file(GOOLPASS_UPLOADS_DIR . '/' . $equipo['escudo'])): ?>
                        <img src="public/uploads/<?= htmlspecialchars($equipo['escudo']) ?>" alt="Escudo del equipo">
                    <?php else: ?>
                        <div class="avatar-placeholder-preview"><?= htmlspecialchars(mb_strtoupper(mb_substr($equipo['nombre'], 0, 1, 'UTF-8'))) ?></div>
                    <?php endif; ?>
                </div>

                <h3 id="txtNombre"><?= htmlspecialchars($equipo['nombre']) ?></h3>

                <div class="detalles-box">
                    <div class="linea-dato"><span class="label">Liga:</span> <span id="txtLiga"><?= htmlspecialchars($equipo['liga']) ?></span></div>
                    <div class="linea-dato"><span class="label">Año:</span> <span id="txtAnio"><?= htmlspecialchars($equipo['anio'] ?? '') ?: '---' ?></span></div>
                    <div class="linea-dato"><span class="label">Plantilla:</span> <span id="txtPlantilla"><?= count($jugadores) ?> jugadores</span></div>
                </div>

                <div class="acciones">
                    <a href="ficha.php?id=<?= (int)$id ?>" class="btn-accion btn-pdf">&#128203; Ver ficha</a>
                </div>
            </div>

            <p class="pie-form">Así se ve tu equipo. Guarda para aplicar los cambios.</p>
        </aside>
    </div>
</main>

<template id="tplJugador">
    <div class="jugador-fila">
        <div class="campo campo-ancho">
            <label>Nombre</label>
            <input type="text" name="jugadores[][nombre]" placeholder="Nombre del jugador">
        </div>
        <div class="campo">
            <label>Posición</label>
            <select name="jugadores[][posicion]">
                <option value="">Selecciona…</option>
                <option value="Portero">Portero</option>
                <option value="Defensas">Defensas</option>
                <option value="Centrocampistas">Centrocampistas</option>
                <option value="Delanteros">Delanteros</option>
            </select>
        </div>
        <button type="button" class="btn-quitar-jug" onclick="quitarJugador(this)" title="Quitar jugador">&#10005;</button>
        <input type="hidden" name="jugadores[][goles]" value="0">
        <input type="hidden" name="jugadores[][asistencias]" value="0">
        <input type="hidden" name="jugadores[][tarjetas_amarillas]" value="0">
        <input type="hidden" name="jugadores[][tarjetas_rojas]" value="0">
        <input type="hidden" name="jugadores[][mvp_count]" value="0">
        <input type="hidden" name="jugadores[][trofeos_ganados]" value="0">
    </div>
</template>

<script>
    function actualizarContador() {
        const n = document.querySelectorAll('#listaJugadores .jugador-fila').length;
        document.getElementById('sinJugadores').style.display = n > 0 ? 'none' : 'block';
        const lbl = document.getElementById('txtPlantilla');
        if (lbl) lbl.textContent = n + (n === 1 ? ' jugador' : ' jugadores');
    }

    let siguienteIndice = <?= count($jugadores) ?>;
    function agregarJugador() {
        const plantilla = document.getElementById('tplJugador');
        const nodo = plantilla.content.firstElementChild.cloneNode(true);
        const indice = siguienteIndice++;
        nodo.querySelectorAll('[name^="jugadores[]"]').forEach(el => {
            el.name = el.name.replace('jugadores[]', `jugadores[${indice}]`);
        });
        document.getElementById('listaJugadores').appendChild(nodo);
        actualizarContador();
        nodo.querySelector('input[name$="[nombre]"]').focus();
    }

    function quitarJugador(boton) {
        boton.closest('.jugador-fila').remove();
        actualizarContador();
    }

    // Vista previa en vivo
    document.getElementById('in_nombre').addEventListener('input', function () {
        document.getElementById('txtNombre').textContent = this.value || 'SIN NOMBRE';
    });
    function actualizarLigaPreview() {
        document.getElementById('txtLiga').textContent = goolpassLigaSeleccionada() || '---';
    }
    document.addEventListener('goolpass-liga-cambiada', actualizarLigaPreview);
    actualizarLigaPreview();
    document.getElementById('in_anio').addEventListener('input', function () {
        document.getElementById('txtAnio').textContent = this.value || '---';
    });
</script>
</body>
</html>

