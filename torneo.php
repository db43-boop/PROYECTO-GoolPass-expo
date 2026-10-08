<?php
require_once 'conexion.php';

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
    <title>Ruleta &amp; Torneo - GoolPass</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/app_common.css">
    <link rel="stylesheet" href="css/torneo.css">
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
        <a href="torneo.php" class="activo">Ruleta &amp; Torneo</a>
        <button type="button" class="btn-anim" data-anim-toggle aria-pressed="false"
                title="Desactivar animaciones (modo ligero)">
            <span class="btn-anim-ico" aria-hidden="true">🎰</span>
            <span class="btn-anim-txt">Animaciones</span>
            <span class="btn-anim-estado" data-anim-estado>ON</span>
        </button>
        <a href="crear.php" class="nav-cta">+ Nuevo</a>
    </nav>
</header>
<main class="envoltura">
    <section id="paso1" class="paso-seccion activo">
            <label for="numGrupos">¿Cuántos grupos habrá? (Grupo A · Z)</label>
            <select id="numGrupos" onchange="cambiarCantidadGrupos()"></select>
        </div>

        <div class="ruleta-wrapper">
            <h2 class="seccion-titulo">🎰 Ruleta de Sorteo</h2>
            <p class="seccion-sub">Gira la ruleta para repartir a los equipos entre los grupos</p>

            <div class="marco-ruleta">
                <canvas id="canvas-ruleta" width="550" height="550"></canvas>
            </div>

            <div class="acciones-ruleta">
                <button type="button" id="btnGirar" class="btn btn-primario" onclick="girarRuleta()">
                    <span class="btn-ico" aria-hidden="true">💨</span><span>Girar Ruleta</span>
                </button>
                <button type="button" id="btnAuto" class="btn btn-azul" onclick="sorteoAutomatico()">
                    <span class="btn-ico" aria-hidden="true">⚒</span><span>Sorteo Rápido</span>
                </button>
            </div>

            <button type="button" id="btnIrAGrupos" class="btn btn-primario btn-ancho oculto"
                    onclick="mostrarPaso(2)">
                <span class="btn-ico" aria-hidden="true">😁</span><span>Ver Tablas de Grupos →</span>
            </button>
        </div>
    </section>

    <section id="paso2" class="paso-seccion">
        <h2 class="seccion-titulo">🏆 Clasificación por Grupos</h2>
        <p class="seccion-sub">
            Pulsa <strong>Clasificar</strong> en los ganadores de cada grupo para enviarlos a las eliminatorias.
        </p>

        <div id="contenedorGrupos" class="grid-grupos"></div>

        <div class="centrado">
            <button type="button" class="btn btn-primario" onclick="cargarClasificadosEnBracket()">
                <span class="btn-ico" aria-hidden="true">✨</span><span>Cargar Clasificados a las Eliminatorias →</span>
            </button>
        </div>
    </section>

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
    </section>

</main>

<div class="cabecera">
    <div>
        <h1>Ruleta &amp; <span class="gradiente">Torneo</span></h1>
        <p>Sortea los grupos con la ruleta, clasifica a los mejores de cada grupo
           y define al campeón en las eliminatorias directas.</p>
    </div>
    <div class="cabecera-acciones">
        <a href="ver.php" class="volver">← Volver a la lista</a>
        <button type="button" class="btn btn-fantasma" onclick="reiniciarTorneo()">🌄 Reiniciar</button>
    </div>
</div>

<script>
const participantesOriginales = <?= $json_equipos ?>;
let participantesSorteo = [...participantesOriginales];
let totalGrupos = 4;
let grupos = [];
let clasificadosFinales = [];
let turnoGrupoIndex = 0;
let girando = false;

const letrasAbecedario = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];

// Utils de color
function getColorClave(nombre) {
    const hash = 0;
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

function dibujarRuleta() {
    const total = participantesSorteo.length;
    if (!total) { ctx.clearRect(0, 0, canvas.width, canvas.height); return; }

    const cx = canvas.width / 2;
    const cy = canvas.height / 2;
    const r = canvas.width / 2 - 18;
    const sliceAngle = (2 * Math.PI) / total;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    let fontSize = 13;
    if (total > 15) fontSize = 11;
    if (total > 25) fontSize = 10;
    if (total > 40) fontSize = 9;

    for (let i = 0; i < total; i++) {
        const startAngle = i * sliceAngle;
        const endAngle = startAngle + sliceAngle;

        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, startAngle, endAngle);
        ctx.closePath();

        ctx.fillStyle = participantesSorteo[i].color || '#ff4500';
        ctx.fill();
        ctx.lineWidth = 1.5;

function girarRuleta() {
    if (girando || participantesSorteo.length === 0) return;
    girando = true;
    btnGirar.classList.add('rota');

    const saltos = participantesSorteo.length <= 6 ? 8 + Math.floor(Math.random() * 12) :
                   participantesSorteo.length <= 15 ? 10 + Math.floor(Math.random() * 16) :
                   participantesSorteo.length <= 25 ? 14 + Math.floor(Math.random() * 20) : 20 + Math.floor(Math.random() * 25);

    let rotacionTotal = 0;
    for (let i = 0; i < saltos; i++) {
        const step = 0.06 + Math.random() * 0.10;
        rotacionTotal += step;
        canvas.rotation = (canvas.rotation || 0) + step;
        dibujarRuleta();
        await sleep(35);
    }

    girando = false;
    btnGirar.classList.remove('rota');

    if (participantesSorteo.length > 0) {
        const indiceFinal = Math.round(canvas.rotation / (2 * Math.PI) * participantesSorteo.length) % participantesSorteo.length;
        const resultado = participantesSorteo[indiceFinal];
        mostrarPopupSorteo(resultado);
    }
}

function mostrarPopupSorteo(resultado) {
    const fondo = document.createElement('div');
    fondo.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.8);z-index:9999;';
    const div = document.createElement('div');
    div.style.cssText = 'background:#1e1e1e;padding:30px;border-radius:12px;text-align:center;max-width:500px;border:3px solid #FFD700;box-shadow:0 0 30px #FFD700;';

    const span = document.createElement('span');
    span.style.cssText = 'font-size:60px;margin-bottom:15px;';
    span.textContent = '🎰';

    const h2 = document.createElement('h2');

function renderizarTablasGrupos() {
    const cont = document.getElementById('contenedorGrupos');
    cont.innerHTML = '';

    if (participantesSorteo.length === 0) {
        cont.innerHTML = '<p style="color:#888;">No hay equipos para sortear.</p>';
        return;
    }

    const total = participantesSorteo.length;
    const porGrupo = Math.ceil(total / totalGrupos);
    let idx = 0;

    grupos = [];
    for (let g = 0; g < totalGrupos; g++) {
        grupos.push({ nombre: "Grupo " + letrasAbecedario[g], participantes: [] });
    }

    const aux = [...participantesSorteo];
    for (let i = aux.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [aux[i], aux[j]] = [aux[j], aux[i]];
    }

    for (let g = 0; g < totalGrupos; g++) {
        const grupoDiv = document.createElement('div');
        grupoDiv.className = 'grupo-cartulina';
        grupoDiv.style.borderColor = getColorClave(grupos[g].nombre) + '66';

        const h3 = document.createElement('h3');
        h3.style.color = getColorClave(grupos[g].nombre);
        h3.textContent = grupos[g].nombre;
        grupoDiv.appendChild(h3);

        const ul = document.createElement('ul');
        ul.style.listStyle = 'none';
        ul.style.padding = 0;

        const inicio = idx;
        const fin = Math.min(idx + porGrupo, aux.length);

        for (let i = inicio; i < fin; i++) {
            const lugar = document.createElement('li');
            lugar.style.cssText = 'background:#252538;padding:10px 15px;border-radius:6px;margin-bottom:8px;';

            const spanNum = document.createElement('span');
            spanNum.style.cssText = 'display:inline-block;width:25px;height:25px;border-radius:50%;background:#FFD700;color:#1e1e1e;font-weight:bold;text-align:center;line-height:25px;float:left;margin-right:10px;';
            spanNum.textContent = (i + 1);


function cargarClasificadosEnBracket() {
    if (clasificadosFinales.length < 4) {
        alert('🚧 ¿No hay clasificados suficientes (mínimo 4).');
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


// Inicializar al cargar
cargarOpcionesGrupos();
dibujarRuleta();

// Expor el contexto para que las funciones en el scope global puedan acceder
window._canvas = canvas;
window._ctx = ctx;

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
                const idx = clasificadosFinales[m * 2];
                if (idx !== undefined) setSlotData(slot1, idx);
            } else {
                slot1.className = 'slot vacio';
                slot1.innerText = 'Pendiente';
            }
            slot1.onclick = () => avanzarDinamico(id1, nextId);

            const slot2 = document.createElement('div');
            slot2.id = id2;
            if (rIdx === 0) {
                const idx = clasificadosFinales[m * 2 + 1];
                if (idx !== undefined) setSlotData(slot2, idx);
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

function setSlotData(slotElem, idx) {
    if (idx !== undefined && participantesSorteo[idx]) {
        slotElem.className = 'slot';
        slotElem.dataset.nombre = participantesSorteo[idx].nombre;
        slotElem.dataset.color = getColorClave(participantesSorteo[idx].nombre);
        slotElem.innerHTML = `<span><span class="badge-color" style="background:${slotElem.dataset.color}"></span>${participantesSorteo[idx].nombre} (${participantesSorteo[idx].liga})</span>`;
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
    if (confirm('🚫 ¿Deseas reiniciar el torneo?')) {
        if (confirm('🚩 ¿Estás seguro de eliminar todas las clasificaciones?')) {
            location.reload();
        }
    }
}

            const spN = document.createElement('span');
            spN.style.cssText = 'display:inline-block;';
            spN.textContent = aux[i].nombre + ' (' + aux[i].liga + ')';

            lugar.appendChild(spanNum);
            lugar.appendChild(spN);

            const bot = document.createElement('button');
            bot.style.cssText = 'margin-top:6px;padding:4px 12px;background:#4CAF50;color:#fff;border:none;border-radius:4px;cursor:pointer;';
            bot.textContent = 'Clasificar';
            bot.onclick = (i2 = i) => clasificarEquipo(i2);

            lugar.appendChild(bot);
            ul.appendChild(lugar);
            idx++;
        }

        grupoDiv.appendChild(ul);
        cont.appendChild(grupoDiv);
    }
}

function clasificarEquipo(idx) {
    const equipo = participantesSorteo[idx];
    if (!equipo) return;

    const pos = clasificadosFinales.indexOf(idx);
    if (pos !== -1) { alert('🚫 ¿El equipo ya está clasificado!'); return; }

    clasificadosFinales.push(idx);
    renderizarTablasGrupos();
    cargarClasificadosEnBracket();
}

    h2.style.cssText = 'color:#FFD700;margin:0 0 10px 0;font-size:28px;';
    h2.textContent = 'Equipos Sorteados!';

    const sp = document.createElement('p');
    sp.style.cssText = 'color:#fff;font-size:22px;margin:10px 0;';
    sp.textContent = resultado.nombre + ' - ' + resultado.liga;

    const btn = document.createElement('button');
    btn.style.cssText = 'margin-top:20px;padding:10px 25px;background:#FFD700;color:#1e1e1e;border:none;border-radius:6px;font-weight:bold;cursor:pointer;';
    btn.textContent = 'Aceptar';
    btn.onclick = () => { fondo.remove(); };

    div.appendChild(span); div.appendChild(h2); div.appendChild(sp); div.appendChild(btn);
    fondo.appendChild(div);
    document.body.appendChild(fondo);
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

        ctx.strokeStyle = '#1e1e1e';
        ctx.stroke();

        ctx.save();
        ctx.translate(cx, cy);
        ctx.rotate(startAngle + sliceAngle / 2);
        ctx.textAlign = "right";
        ctx.fillStyle = "#ffffff";
        ctx.font = `bold ${fontSize}px Arial`;
        ctx.shadowColor = "black";
        ctx.shadowBlur = 4;

        const maxChar = total > 30 ? 15 : 20;
        ctx.fillText(participantesSorteo[i].nombre.substring(0, maxChar), r - 15, 4);
        ctx.restore();
    }

    ctx.beginPath();
    ctx.moveTo(cx - 15, 6);
    ctx.lineTo(cx + 15, 6);
    ctx.lineTo(cx, 32);
    ctx.closePath();
    ctx.fillStyle = '#ffffff';
    ctx.fill();
}

    turnoGrupoIndex = 0;
}

function cambiarCantidadGrupos() {
    participantesSorteo = [...participantesOriginales];
    inicializarGrupos();
    dibujarRuleta();
}

function obtenerIndiceClave(nombre, total) {
    let hash = 0;
    for (let i = 0; i < nombre.length; i++) {
        hash = ((hash << 5) - hash) + nombre.charCodeAt(i);
        hash = hash & hash;
    }
    return Math.abs(hash) % total;
}

            <span aria-hidden="true">←</span> Volver a la lista
        </a>
        <button type="button" class="btn btn-fantasma" onclick="reiniciarTorneo()">
            <span class="btn-ico" aria-hidden="true">🌄</span><span>Reiniciar</span>
        </button>
    </div>
</div>

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
