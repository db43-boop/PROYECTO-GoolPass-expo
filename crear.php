<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Equipo - GoolPass</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/app_common.css">
    <link rel="stylesheet" href="css/crear.css">
    <link rel="stylesheet" href="css/estilos_fichas.css">
    <link rel="stylesheet" href="css/animaciones.css">

    <script src="js/animaciones.js"></script>
</head>
<body>

<div class="fondo" aria-hidden="true">
    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>
    <div class="aurora aurora-3"></div>
    <div class="rejilla"></div>
    <div class="vineta"></div>
</div>

<header class="barra">
    <div class="marca">
        <span class="marca-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                <path d="M5 3h14v3a7 7 0 0 1-5.2 6.8V16h3.2a1.8 1.8 0 0 1 0 3.6H7a1.8 1.8 0 0 1 0-3.6h3.2v-3.2A7 7 0 0 1 5 6V3Z"
                      fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="marca-txt">Gool<em>Pass</em></span>
    </div>

    <nav class="nav" aria-label="Navegación principal">
        <a href="index.php">Portada</a>
        <a href="ver.php">Equipos</a>
        <a href="torneo.php">Ruleta &amp; Torneo</a>
        <a href="crear.php" class="nav-cta">+ Nuevo Equipo</a>
    </nav>
</header>

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
                <input type="hidden" name="accion" value="crear_equipo">

                <div class="form-group">
                    <label for="in_nombre">Nombre del Club <span class="req">*</span></label>
                    <input type="text" name="nombre" id="in_nombre" required placeholder="Ej. Real Madrid, FC Barcelona, Bayern Múnich" oninput="actualizarPreview()">
                </div>

                <div class="form-group">
                    <label for="in_liga">Liga de Origen <span class="req">*</span></label>
                    <select name="liga" id="in_liga" required oninput="actualizarPreview()">
                        <option value="">-- Selecciona una liga --</option>
                        <option value="LaLiga">LaLiga (España)</option>
                        <option value="Bundesliga">Bundesliga (Alemania)</option>
                        <option value="Serie A">Serie A (Italia)</option>
                        <option value="Premier League">Premier League (Inglaterra)</option>
                        <option value="Ligue 1">Ligue 1 (Francia)</option>
                        <option value="J-League">J-League (Japón)</option>
                        <option value="Liga MX">Liga MX (México)</option>
                        <option value="Champions League">Champions League (Europa)</option>
                    </select>
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

                <div class="acciones">
                    <button type="submit" class="btn-principal">&#10132; Guardar Equipo</button>
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
        document.getElementById('txtLiga').innerText = document.getElementById('in_liga').value || '---';
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

    // Sincroniza la vista previa con los valores iniciales del formulario
    actualizarPreview();
</script>
</body>
</html>
