<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Equipo - GoolPass</title>

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

<?php $paginaActiva = 'crear'; require GOOLPASS_ROOT . '/app/Views/partials/cabecera.php'; ?>

<div class="cabecera">
    <div>
        <h1>Registrar <span class="gradiente">Equipo</span></h1>
        <p>Completa los datos del club. Subirás el escudo oficial y podrás añadir su plantilla de jugadores en la ficha.</p>
    </div>
    <a href="ver.php" class="volver">
        <span aria-hidden="true">&#8592;</span> Volver a la lista
    </a>
</div>

<main class="envoltura">
    <div class="container-crear">
        <div class="form-panel">
            <h2><span class="ico" aria-hidden="true">&#9917;</span> Datos del Equipo</h2>

            <form action="guardar_ficha.php" method="POST" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <input type="hidden" name="accion" value="crear_equipo">

                <div class="form-group">
                    <label for="in_nombre">Nombre del Club <span class="req">*</span></label>
                    <input type="text" name="nombre" id="in_nombre" required placeholder="Ej. Real Madrid, FC Barcelona, Bayern Múnich" oninput="actualizarPreview()">
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
                    <label for="in_anio">A&ntilde;o del Club</label>
                    <input type="number" name="anio" id="in_anio" min="1800" max="2100"
                           placeholder="Ej. 1902" oninput="actualizarPreview()">
                    <small class="opcional">A&ntilde;o de fundaci&oacute;n del club (entre 1800 y 2100). Puedes dejarlo vac&iacute;o.</small>
                </div>

                <div class="form-group">
                    <label for="in_escudo">Escudo Oficial <span class="req">*</span></label>
                    <input type="file" name="escudo" id="in_escudo" accept=".jpg,.jpeg,.png,.webp" required onchange="previewImagen(event)">
                    <small class="opcional">Solo imágenes JPG, PNG o WEBP. Máximo 5MB.</small>
                </div>

                <div class="preview-box" id="previewBox" style="display:none;">
                    <img id="imgPreview" src="" alt="Vista previa del escudo" style="max-width:200px;max-height:120px;display:block;margin:0 auto;">
                    <button type="button" class="btn-borrar" onclick="quitarEscudo()">&#10005; Quitar</button>
                </div>

                <div class="seccion-jugadores">
                    <h3>&#128101; Plantilla de jugadores <span style="color:var(--tenue,#9a9aae);font-weight:500;font-size:12px;">(opcional)</span></h3>
                    <p class="hint">Agrega a los jugadores de este equipo. Más tarde podrás completar sus estadísticas desde su ficha.</p>

                    <div id="listaJugadores"></div>
                    <p id="sinJugadores" class="sin-jugadores">Aún no has agregado jugadores.</p>

                    <button type="button" class="btn-agregar-jug" onclick="agregarJugador()">&#10133; Agregar jugador</button>
                </div>

                <template id="tplJugador">
                    <div class="jugador-fila">
                        <div class="campo campo-ancho">
                            <label>Nombre</label>
                            <input type="text" name="jugadores[][nombre]" placeholder="Ej. Ramírez" required maxlength="120">
                        </div>
                        <div class="campo">
                            <label>Posición</label>
                            <select name="jugadores[][posicion]" required>
                                <option value="">Selecciona…</option>
                                <option value="Portero">Portero</option>
                                <option value="Defensas">Defensas</option>
                                <option value="Centrocampistas">Centrocampistas</option>
                                <option value="Delanteros">Delanteros</option>
                            </select>
                        </div>
                        <button type="button" class="btn-quitar-jug" onclick="quitarJugador(this)" title="Quitar jugador">&#10005;</button>
                    </div>
                </template>

                <div class="acciones acciones-formulario">
                    <button type="submit" class="btn btn-primario btn-guardar-equipo">
                        <span aria-hidden="true">&#10003;</span><span>Guardar equipo</span>
                    </button>
                    <a href="ver.php" class="btn btn-fantasma btn-cancelar-equipo">
                        <span aria-hidden="true">&#8592;</span><span>Cancelar</span>
                    </a>
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
                    <span class="badge-tipo" id="prevBadge">&#9917; F&uacute;tbol</span>
                    <img id="prevImg" src="" alt="Escudo del equipo">
                    <div id="noImgText">Sin imagen</div>
                </div>

                <h3 id="txtNombre">NUEVO EQUIPO</h3>

                <div class="detalles-box">
                    <div class="linea-dato"><span class="label">Liga:</span> <span id="txtLiga">---</span></div>
                    <div class="linea-dato"><span class="label">A&ntilde;o:</span> <span id="txtAnio">---</span></div>
                    <div class="linea-dato"><span class="label">Escudo:</span> <span id="txtEscudo">Pendiente</span></div>
                    <div class="linea-dato"><span class="label">Estado:</span> En registro</div>
                </div>

                <div class="acciones">
                    <span class="btn-accion btn-editar">&#9999;&#65039; Editar</span>
                    <span class="btn-accion btn-pdf">&#128203; Ver ficha</span>
                    <span class="btn-accion btn-eliminar">&#128465;&#65039; Eliminar</span>
                </div>
            </div>

            <p class="pie-form">As&iacute; se ver&aacute; tu equipo en la lista de &laquo;Equipos&raquo; una vez guardado.</p>
        </aside>
    </div>
</main>

<script>
    function actualizarPreview() {
        const nombre = document.getElementById('in_nombre').value || 'NUEVO EQUIPO';
        document.getElementById('txtNombre').innerText = nombre;
        document.getElementById('txtLiga').innerText = goolpassLigaSeleccionada() || '---';
        document.getElementById('txtAnio').innerText = document.getElementById('in_anio').value || '---';
    }

    function previewImagen(event) {
        const imagen = event.target.files[0];
        if (!imagen) return;

        const reader = new FileReader();
        reader.onload = function() {
            // Miniatura bajo el campo del formulario
            document.getElementById('imgPreview').src = reader.result;
            document.getElementById('previewBox').style.display = 'block';

            // Escudo dentro de la tarjeta de vista previa
            const prev = document.getElementById('prevImg');
            prev.src = reader.result;
            prev.style.display = 'block';
            document.getElementById('noImgText').style.display = 'none';
            document.getElementById('txtEscudo').innerText = 'Adjunto ✓';
        };
        reader.readAsDataURL(imagen);
    }

    function quitarEscudo() {
        document.getElementById('previewBox').style.display = 'none';
        document.getElementById('in_escudo').value = '';
        document.getElementById('imgPreview').src = '';

        const prev = document.getElementById('prevImg');
        prev.src = '';
        prev.style.display = 'none';
        document.getElementById('noImgText').style.display = 'flex';
        document.getElementById('txtEscudo').innerText = 'Pendiente';
    }

    // Índice único por fila para que PHP reciba nombre y posición juntos.
    let siguienteIndiceJugador = 0;
    function agregarJugador() {
        const plantilla = document.getElementById('tplJugador');
        const nodo = plantilla.content.firstElementChild.cloneNode(true);
        const indice = siguienteIndiceJugador++;
        nodo.querySelector('input[name="jugadores[][nombre]"]').name = `jugadores[${indice}][nombre]`;
        nodo.querySelector('select[name="jugadores[][posicion]"]').name = `jugadores[${indice}][posicion]`;
        document.getElementById('listaJugadores').appendChild(nodo);
        actualizarSinJugadores();
        nodo.querySelector('input[name="jugadores[][nombre]"]').focus();
    }

    function quitarJugador(boton) {
        boton.closest('.jugador-fila').remove();
        actualizarSinJugadores();
    }

    function actualizarSinJugadores() {
        const hay = document.querySelectorAll('#listaJugadores .jugador-fila').length > 0;
        document.getElementById('sinJugadores').style.display = hay ? 'none' : 'block';
    }

    document.addEventListener("goolpass-liga-cambiada", actualizarPreview);
    // Sincroniza la vista previa con los valores iniciales del formulario
    actualizarPreview();
</script>
</body>
</html>
