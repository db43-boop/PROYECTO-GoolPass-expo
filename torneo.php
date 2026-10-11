<?php
require_once 'auth.php';
requerir_login();

try {
    // Obtener equipos en lugar de participantes
    $stmt = $conexion->query("SELECT id, nombre, liga, escudo FROM equipos ORDER BY nombre ASC");
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al consultar equipos: " . $e->getMessage());
}

$json_equipos = json_encode($equipos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torneo - GoolPass</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/app_common.css">
    <link rel="stylesheet" href="css/torneo.css">
    <link rel="stylesheet" href="css/animaciones.css">

    <script src="js/animaciones.js"></script>
    <script src="js/antireload.js"></script>
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
        <a href="torneo.php" class="activo">Torneo</a>
        <button type="button" class="btn-anim" data-anim-toggle aria-pressed="false"
                title="Desactivar animaciones (modo ligero)">
            <span class="btn-anim-ico" aria-hidden="true">✨</span>
            <span class="btn-anim-txt">Animaciones</span>
            <span class="btn-anim-estado" data-anim-estado>ON</span>
        </button>
        <a href="crear.php" class="nav-cta">+ Nuevo</a>
    </nav>
</header>

<main class="envoltura">

    <div class="stepper">
        <button type="button" id="tab-paso1" class="step activo" onclick="mostrarPaso(1)">
            <span class="num">1</span> Sorteo de Grupos
        </button>
        <span class="step-flecha" aria-hidden="true"></span>
        <button type="button" id="tab-paso2" class="step" onclick="mostrarPaso(2)">
            <span class="num">2</span> Fase de Grupos
        </button>
        <span class="step-flecha" aria-hidden="true"></span>
        <button type="button" id="tab-paso3" class="step" onclick="mostrarPaso(3)">
            <span class="num">3</span> Eliminatorias
        </button>
    </div>

    <!-- PASO 1 · CONFIGURACIÓN Y SORTEO -->
    <section id="paso1" class="paso-seccion activo">
        <div class="config-box">
            <label for="numGrupos">¿Cuántos grupos habrá? (Grupo A · Z)</label>
            <select id="numGrupos" onchange="cambiarCantidadGrupos()"></select>
        </div>

        <div class="centrado">
            <button type="button" class="btn btn-primario" onclick="realizarSorteo()">
                <span class="btn-ico" aria-hidden="true">🎲</span>
                <span>Sortear y Ver Grupos →</span>
            </button>
        </div>
    </section>

    <!-- PASO 2 · FASE DE GRUPOS -->
    <section id="paso2" class="paso-seccion">
        <h2 class="seccion-titulo">🏆 Clasificación por Grupos</h2>
        <p class="seccion-sub">
            Pulsa <strong>Clasificar</strong> en los ganadores de cada grupo para enviarlos a las eliminatorias.
        </p>

        <div id="contenedorGrupos" class="grid-grupos"></div>

        <div class="centrado">
            <button type="button" class="btn btn-primario" onclick="cargarClasificadosEnBracket()">
                <span class="btn-ico" aria-hidden="true">✨</span>
                <span>Cargar Clasificados a las Eliminatorias →</span>
            </button>
        </div>
    </section>

    <!-- PASO 3 · ELIMINATORIAS -->
    <section id="paso3" class="paso-seccion">
        <div class="bracket-wrapper">
            <h2 class="seccion-titulo">⚔️ Eliminatorias directas</h2>
            <p class="seccion-sub">Haz clic sobre el equipo vencedor para hacerlo avanzar de ronda.</p>

            <div class="bracket-tree" id="bracketTree"></div>

            <div id="campeonBox" class="campeon-box">
                <h2>🏆 ¡Campeón del torneo! 🏆</h2>
                <div id="nombreCampeon"></div>
            </div>
        </div>

        <div class="centrado">
            <a href="ver.php" class="btn btn-fantasma">
                <span class="btn-ico" aria-hidden="true">←</span>
                <span>Volver a la lista</span>
            </a>
            <button type="button" class="btn btn-fantasma" onclick="reiniciarTorneo()">
                <span class="btn-ico" aria-hidden="true">🌄</span>
                <span>Reiniciar</span>
            </button>
        </div>
    </section>

</main>

<script>
const participantesOriginales = <?= $json_equipos ?>;
let participantesSorteo = [...participantesOriginales];
let totalGrupos = 4;
let grupos = [];
let clasificadosFinales = [];

const letrasAbecedario = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];

// Color estable derivado del nombre del equipo
function getColorClave(nombre) {
    let hash = 0;
    for (let i = 0; i < nombre.length; i++) {
        const char = nombre.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }
    const colores = [
        '#e53935','#fb8c00','#43a047','#1e88e5','#8e24aa','#00acc1',
        '#ff6f00','#7b1fa2','#009688','#6d4c41','#3949ab','#26a69a',
        '#d84315','#f57c00','#283593','#1a237e','#fafafa','#cfd8dc',
        '#546e7a','#ff5722','#795548','#3f51b5','#03a9f4','#4caf50',
        '#ff9800','#9c27b0','#00bcd4','#795548','#607d8b','#2196f3'
    ];
    return colores[Math.abs(hash) % colores.length];
}

function mezclar(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

function cargarOpcionesGrupos() {
    const select = document.getElementById('numGrupos');
    select.innerHTML = '';

    const maxGrupos = 26;

    for (let i = 2; i <= maxGrupos; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = `${i} Grupos (Grupo A hasta Grupo ${letrasAbecedario[i - 1]})`;
        if (i === 4) {
            option.selected = true;
        }
        select.appendChild(option);
    }
    inicializarGrupos();
}

function inicializarGrupos() {
    const select = document.getElementById('numGrupos');
    totalGrupos = parseInt(select.value) || 4;
    grupos = [];

    for (let i = 0; i < totalGrupos; i++) {
        grupos.push({
            nombre: "Grupo " + letrasAbecedario[i],
            participantes: []
        });
    }
}

function cambiarCantidadGrupos() {
    participantesSorteo = [...participantesOriginales];
    inicializarGrupos();
    clasificadosFinales = [];
    if (document.getElementById('paso2').classList.contains('activo')) {
        renderizarTablasGrupos();
    }
}

// Sortea (mezcla) los equipos y avanza a la fase de grupos
function realizarSorteo() {
    participantesSorteo = mezclar([...participantesOriginales]);
    inicializarGrupos();
    clasificadosFinales = [];
    renderizarTablasGrupos();
    mostrarPaso(2);
}

function mostrarPaso(num) {
    document.getElementById('paso1').classList.remove('activo');
    document.getElementById('paso2').classList.remove('activo');
    document.getElementById('paso3').classList.remove('activo');
    document.getElementById('tab-paso1').classList.remove('activo');
    document.getElementById('tab-paso2').classList.remove('activo');
    document.getElementById('tab-paso3').classList.remove('activo');

    document.getElementById('paso' + num).classList.add('activo');
    document.getElementById('tab-paso' + num).classList.add('activo');

    if (num === 2) renderizarTablasGrupos();
}


function renderizarTablasGrupos() {
    const cont = document.getElementById('contenedorGrupos');
    cont.innerHTML = '';

    if (participantesSorteo.length === 0) {
        cont.innerHTML = '<p class="vacio-grupo">No hay equipos para sortear.</p>';
        return;
    }

    const total = participantesSorteo.length;
    const porGrupo = Math.ceil(total / totalGrupos);
    let idx = 0;

    grupos = [];
    for (let g = 0; g < totalGrupos; g++) {
        grupos.push({ nombre: "Grupo " + letrasAbecedario[g], participantes: [] });
    }

    // Repartir los equipos (ya mezclados) entre los grupos
    for (let g = 0; g < totalGrupos; g++) {
        const grupoDiv = document.createElement('div');
        grupoDiv.className = 'card-grupo';
        grupoDiv.style.borderTopColor = getColorClave(grupos[g].nombre);

        const h3 = document.createElement('h3');
        h3.style.color = getColorClave(grupos[g].nombre);
        h3.innerHTML = `${grupos[g].nombre} <span class="cuenta"></span>`;
        grupoDiv.appendChild(h3);

        const inicio = idx;
        const fin = Math.min(idx + porGrupo, total);

        if (inicio >= fin) {
            const p = document.createElement('p');
            p.className = 'vacio-grupo';
            p.textContent = 'Sin equipos';
            grupoDiv.appendChild(p);
            cont.appendChild(grupoDiv);
            continue;
        }

        const ul = document.createElement('ul');
        ul.style.listStyle = 'none';
        ul.style.padding = '0';
        ul.style.margin = '0';

        for (let i = inicio; i < fin; i++) {
            const equipo = participantesSorteo[i];
            grupos[g].participantes.push(equipo);

            const lugar = document.createElement('li');
            lugar.className = 'item-grupo';

            const spanNom = document.createElement('span');
            spanNom.className = 'nombre';

            const badge = document.createElement('span');
            badge.className = 'badge-color';
            badge.style.background = getColorClave(equipo.nombre);

            const txt = document.createElement('span');
            txt.className = 'txt';
            txt.textContent = `${equipo.nombre} (${equipo.liga})`;

            spanNom.appendChild(badge);
            spanNom.appendChild(txt);

            const clasificado = clasificadosFinales.some(e => e.id === equipo.id);
            const bot = document.createElement('button');
            bot.type = 'button';
            bot.className = 'btn-clasificar' + (clasificado ? ' clasificado' : '');
            bot.textContent = clasificado ? 'Clasificado ✓' : 'Clasificar';
            bot.onclick = () => clasificarEquipo(equipo);

            lugar.appendChild(spanNom);
            lugar.appendChild(bot);
            ul.appendChild(lugar);
            idx++;
        }

        grupoDiv.appendChild(ul);
        cont.appendChild(grupoDiv);
    }
}

function clasificarEquipo(equipo) {
    const pos = clasificadosFinales.findIndex(e => e.id === equipo.id);
    if (pos !== -1) {
        clasificadosFinales.splice(pos, 1);
    } else {
        clasificadosFinales.push(equipo);
    }
    renderizarTablasGrupos();
}


function cargarClasificadosEnBracket() {
    if (clasificadosFinales.length < 4) {
        alert('🚧 No hay clasificados suficientes (mínimo 4).');
        return;
    }

    const tree = document.getElementById('bracketTree');
    tree.innerHTML = '';

    let n = clasificadosFinales.length;
    let bracketSize = 2;
    while (bracketSize < n) {
        bracketSize *= 2;
    }

    const rondasNombres = {
        32: "16vos de Final",
        16: "Octavos de Final",
        8: "Cuartos de Final",
        4: "Semifinales",
        2: "Gran Final"
    };

    let rondas = [];
    let curSize = bracketSize;
    while (curSize >= 2) {
        rondas.push(curSize);
        curSize /= 2;
    }

    rondas.forEach((size, rIdx) => {
        const col = document.createElement('div');
        col.className = 'bracket-col';

        const h3 = document.createElement('h3');
        h3.innerText = rondasNombres[size] || `Ronda de ${size}`;
        col.appendChild(h3);

        const numMatchups = size / 2;

        for (let m = 0; m < numMatchups; m++) {
            const slotWrap = document.createElement('div');
            slotWrap.className = 'bracket-slot';

            const matchup = document.createElement('div');
            matchup.className = 'matchup';

            const id1 = `r${rIdx}_m${m}_s0`;
            const id2 = `r${rIdx}_m${m}_s1`;

            const nextMatchupIdx = Math.floor(m / 2);
            const nextSlotIdx = m % 2;
            const nextId = rIdx < rondas.length - 1 ? `r${rIdx+1}_m${nextMatchupIdx}_s${nextSlotIdx}` : 'campeon';

            const slot1 = document.createElement('div');
            slot1.id = id1;
            if (rIdx === 0) {
                const eq = clasificadosFinales[m * 2];
                if (eq !== undefined) setSlotData(slot1, eq);
                else { slot1.className = 'slot vacio'; slot1.innerText = 'Pase Libre'; }
            } else {
                slot1.className = 'slot vacio';
                slot1.innerText = 'Pendiente';
            }
            slot1.onclick = () => avanzarDinamico(id1, nextId);

            const slot2 = document.createElement('div');
            slot2.id = id2;
            if (rIdx === 0) {
                const eq = clasificadosFinales[m * 2 + 1];
                if (eq !== undefined) setSlotData(slot2, eq);
                else { slot2.className = 'slot vacio'; slot2.innerText = 'Pase Libre'; }
            } else {
                slot2.className = 'slot vacio';
                slot2.innerText = 'Pendiente';
            }
            slot2.onclick = () => avanzarDinamico(id2, nextId);

            matchup.appendChild(slot1);
            matchup.appendChild(slot2);
            slotWrap.appendChild(matchup);
            col.appendChild(slotWrap);
        }

        tree.appendChild(col);
    });

    mostrarPaso(3);
}

function setSlotData(slotElem, equipo) {
    if (equipo) {
        const color = getColorClave(equipo.nombre);
        slotElem.className = 'slot';
        slotElem.dataset.nombre = equipo.nombre;
        slotElem.dataset.color = color;
        slotElem.innerHTML = `<span><span class="badge-color" style="background:${color}"></span>${equipo.nombre} (${equipo.liga})</span>`;
    } else {
        slotElem.className = 'slot vacio';
        slotElem.innerText = 'Pase Libre';
    }
}

function avanzarDinamico(origenId, destinoId) {
    const orig = document.getElementById(origenId);
    if (!orig || orig.classList.contains('vacio')) return;

    if (destinoId === 'campeon') {
        const box = document.getElementById('campeonBox');
        const nomElem = document.getElementById('nombreCampeon');
        box.style.display = 'block';
        box.style.borderColor = orig.dataset.color || '#FFD700';
        nomElem.style.color = orig.dataset.color || '#FFD700';
        nomElem.innerText = orig.dataset.nombre;
        return;
    }

    const dest = document.getElementById(destinoId);
    if (dest) {
        dest.className = 'slot';
        dest.dataset.nombre = orig.dataset.nombre;
        dest.dataset.color = orig.dataset.color;
        dest.innerHTML = orig.innerHTML;
    }
}

function reiniciarTorneo() {
    if (confirm('¿Deseas reiniciar el torneo?')) {
        if (confirm('¿Estás seguro de eliminar todas las clasificaciones?')) {
            location.reload();
        }
    }
}

// Inicializar al cargar
cargarOpcionesGrupos();
</script>

</body>
</html>

