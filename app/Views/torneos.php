<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torneo - GoolPass</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    
    
    

    <?php View::script('animaciones'); ?>
    <?php View::script('antireload'); ?>
    
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

<?php $paginaActiva = 'torneo'; View::cabecera($paginaActiva); ?>

<main class="envoltura envoltura-torneos">

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

<?php View::fragment('torneo_form', get_defined_vars()); ?>
<?php View::fragment('torneo_detalle', get_defined_vars()); ?>
</main>

<script>
const participantesOriginales = <?= $json_equipos ?>;
const esAdministrador = <?= es_admin() ? 'true' : 'false' ?>;
const tokenTorneo = <?= json_encode(csrf_token()) ?>;
const letrasAbecedario = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

let origenTorneo = 'nacional';
let participantesSorteo = [];
let totalGrupos = 2;
let grupos = [];
let clasificadosFinales = [];
let resultadosRondas = {};
let campeonGuardado = null;
let campeonPendiente = null;
let faseTorneo = null;
let sorteoRealizado = false;
let guardadoEnCurso = Promise.resolve();
let cargandoEstado = false;
const porId = new Map(participantesOriginales.map(e => [Number(e.id), e]));

function nombreOrigenTorneo() {
    return origenTorneo === 'nacional' ? 'Nacional (Perú)' : 'Internacional';
}
function equiposDelOrigen() {
    return participantesOriginales.filter(e => e.origen === origenTorneo);
}
function colorEquipo(nombre) {
    let hash = 0;
    for (const letra of nombre) hash = ((hash << 5) - hash + letra.charCodeAt(0)) | 0;
    const colores = ['#e53935','#fb8c00','#43a047','#1e88e5','#8e24aa','#00acc1','#ff6f00','#7b1fa2','#009688','#3949ab','#26a69a','#d84315','#f57c00','#283593','#ff5722','#795548','#607d8b'];
    return colores[Math.abs(hash) % colores.length];
}
function mezclar(lista) {
    const a = [...lista];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}
function informar(texto, esError = false) {
    const elem = document.getElementById('estadoGuardadoTorneo');
    elem.textContent = texto;
    elem.classList.toggle('advertencia', esError);
}
function cargarOpcionesGrupos() {
    const select = document.getElementById('numGrupos');
    const previa = parseInt(select.value, 10);
    select.replaceChildren();
    for (let n = 2; n <= 26; n++) {
        const opt = document.createElement('option');
        opt.value = String(n);
        opt.textContent = `${n} grupos (A hasta ${letrasAbecedario[n - 1]})`;
        select.appendChild(opt);
    }
    select.value = previa >= 2 && previa <= 26 ? String(previa) : '2';
    select.disabled = !esAdministrador;
}
function actualizarDisponibilidadSorteo() {
    const equipos = equiposDelOrigen();
    const boton = document.getElementById('btnSortearGrupos');
    const cantidad = parseInt(document.getElementById('numGrupos').value, 10) || 2;
    boton.disabled = !esAdministrador || equipos.length < 4 || cantidad > equipos.length;
    let mensaje = `Torneo ${nombreOrigenTorneo()}: ${equipos.length} equipos disponibles.`;
    if (equipos.length < 4) mensaje += ' Se necesitan al menos cuatro equipos para sortear.';
    else if (cantidad > equipos.length) mensaje += ` Debes contar con al menos ${cantidad} equipos para esos grupos.`;
    else if (cantidad * 2 > equipos.length) mensaje += ' Algunos grupos tendrán un único equipo (pase libre).';
    document.getElementById('resumenOrigenTorneo').textContent = mensaje;
    document.getElementById('tituloOrigenGrupos').textContent = `Torneo ${nombreOrigenTorneo()}`;
    document.getElementById('tituloOrigenEliminatorias').textContent = `Torneo ${nombreOrigenTorneo()}`;
}
function obtenerEstado() {
    return {
        fase: faseTorneo,
        cantidad_grupos: totalGrupos,
        orden: participantesSorteo.map(e => Number(e.id)),
        clasificados: clasificadosFinales.map(e => Number(e.id)),
        rondas: {...resultadosRondas},
        campeon: campeonGuardado
    };
}
async function peticionGuardar(accion, torneo = null) {
    const respuesta = await fetch('index.php?r=api_torneo', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({accion, origen: origenTorneo, torneo, csrf_token: tokenTorneo})
    });
    const data = await respuesta.json();
    if (!respuesta.ok || !data.ok) throw Error(data.error || 'No se pudo guardar el torneo.');
    return data;
}
function guardarAutomatico() {
    if (!esAdministrador || !sorteoRealizado || !faseTorneo) return Promise.resolve();
    const origenGuardado = origenTorneo;
    const estadoGuardado = obtenerEstado();
    informar('Guardando cambios en SQLite…');
    guardadoEnCurso = guardadoEnCurso.catch(() => {}).then(async () => {
        const resp = await fetch('index.php?r=api_torneo', {
            method:'POST', credentials:'same-origin',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({accion:'guardar', origen:origenGuardado, torneo:estadoGuardado, csrf_token:tokenTorneo})
        });
        const data = await resp.json();
        if (!resp.ok || !data.ok) throw Error(data.error || 'Error al guardar');
        if (origenTorneo === origenGuardado) informar('Cambios guardados. Los usuarios pueden ver este torneo.');
    }).catch(e => {
        informar(`No se pudo guardar: ${e.message}. Vuelve a intentarlo antes de salir.`, true);
        throw e;
    });
    // Evita avisos de promesas rechazadas por los eventos de clic.
    guardadoEnCurso.catch(() => {});
    return guardadoEnCurso;
}
function limpiarLocal() {
    participantesSorteo = [];
    clasificadosFinales = [];
    resultadosRondas = {};
    campeonGuardado = null;
    campeonPendiente = null;
    faseTorneo = null;
    sorteoRealizado = false;
    grupos = [];
    document.getElementById('contenedorGrupos').replaceChildren();
    document.getElementById('bracketTree').replaceChildren();
    document.getElementById('campeonBox').style.display = 'none';
    document.getElementById('btnGuardarCampeon').hidden = true;
}
async function leerEstadoTorneo() {
    cargandoEstado = true;
    try {
        await guardadoEnCurso.catch(() => {});
        const resp = await fetch(`index.php?r=api_torneo&origen=${encodeURIComponent(origenTorneo)}&_=${Date.now()}`, {credentials: 'same-origin', cache:'no-store'});
        const datos = await resp.json();
        if (!resp.ok || !datos.ok) throw Error(datos.error || 'No se pudo consultar el torneo.');
        limpiarLocal();
        if (!datos.torneo) {
            document.getElementById('numGrupos').value = '2';
            totalGrupos = 2;
            informar(esAdministrador ? 'Todavía no hay torneo guardado para este origen.' : 'El administrador todavía no ha iniciado un torneo de esta categoría.');
            mostrarPaso(1);
        } else {
            const t = datos.torneo;
            totalGrupos = t.cantidad_grupos;
            document.getElementById('numGrupos').value = String(totalGrupos);
            participantesSorteo = t.orden.map(id => porId.get(Number(id))).filter(Boolean);
            clasificadosFinales = t.clasificados.map(id => porId.get(Number(id))).filter(Boolean);
            resultadosRondas = t.rondas || {};
            campeonGuardado = t.campeon == null ? null : Number(t.campeon);
            faseTorneo = t.fase;
            sorteoRealizado = true;
            renderizarTablasGrupos();
            if (faseTorneo === 'eliminatorias' || faseTorneo === 'finalizado') {
                dibujarBracket();
                mostrarPaso(3);
            } else {
                mostrarPaso(2);
            }
            informar(`${faseTorneo === 'finalizado' ? 'Torneo finalizado' : 'Torneo guardado'} · Última actualización: ${datos.actualizado_en || 'reciente'}`);
        }
        actualizarDisponibilidadSorteo();
    } catch (e) {
        informar(`Error al consultar el torneo: ${e.message}`, true);
    } finally {
        cargandoEstado = false;
    }
}
async function cambiarOrigenTorneo() {
    origenTorneo = document.getElementById('origenTorneo').value;
    await leerEstadoTorneo();
}
function cambiarCantidadGrupos() {
    if (!esAdministrador) return;
    // El cambio es una propuesta para el siguiente sorteo: no altera el torneo guardado.
    actualizarDisponibilidadSorteo();
    informar(sorteoRealizado ? 'La nueva cantidad se aplicará al realizar otro sorteo. El anterior sigue guardado hasta entonces.' : 'Selecciona «Sortear y ver grupos» para comenzar.');
}
async function realizarSorteo() {
    if (!esAdministrador) return;
    totalGrupos = parseInt(document.getElementById('numGrupos').value,10) || 2;
    const disponibles = equiposDelOrigen();
    if (disponibles.length < 4 || disponibles.length < totalGrupos) {
        alert(`Se necesitan al menos ${Math.max(4,totalGrupos)} equipos del origen elegido.`);
        return;
    }
    if (sorteoRealizado && !confirm('Se reemplazará el sorteo guardado de este origen, incluidos sus clasificados y campeón. ¿Continuar?')) return;
    limpiarLocal();
    participantesSorteo = mezclar(disponibles);
    sorteoRealizado = true;
    faseTorneo = 'grupos';
    renderizarTablasGrupos();
    mostrarPaso(2);
    await guardarAutomatico();
}
function mostrarPaso(numero) {
    if (numero > 1 && !sorteoRealizado) {
        if (!cargandoEstado) informar('Todavía no existe un sorteo para mostrar.', true);
        return;
    }
    if (numero === 3 && faseTorneo === 'grupos') {
        informar('Primero deben publicarse los clasificados a las eliminatorias.', true);
        return;
    }
    for (let n = 1; n <= 3; n++) {
        document.getElementById('paso'+n).classList.toggle('activo', n === numero);
        document.getElementById('tab-paso'+n).classList.toggle('activo', n === numero);
    }
    if (numero === 2) renderizarTablasGrupos();
    if (numero === 3) dibujarBracket();
}
function gruposActuales() {
    const cantidad = participantesSorteo.length;
    const base = Math.floor(cantidad / totalGrupos);
    const extras = cantidad % totalGrupos;
    const resultado = [];
    let indice = 0;
    for (let i=0; i<totalGrupos; i++) {
        const tam = base + (i < extras ? 1 : 0);
        resultado.push({nombre: `Grupo ${letrasAbecedario[i]}`, participantes: participantesSorteo.slice(indice,indice+tam)});
        indice += tam;
    }
    return resultado;
}
function renderizarTablasGrupos() {
    const cont = document.getElementById('contenedorGrupos');
    cont.replaceChildren();
    if (!sorteoRealizado) return;
    grupos = gruposActuales();
    for (const grupo of grupos) {
        const tarjeta = document.createElement('div');
        tarjeta.className = 'card-grupo';
        tarjeta.style.borderTopColor = colorEquipo(grupo.nombre);
        const titulo = document.createElement('h3');
        titulo.style.color = colorEquipo(grupo.nombre);
        titulo.textContent = grupo.nombre;
        tarjeta.appendChild(titulo);
        const lista = document.createElement('ul');
        lista.style.cssText = 'list-style:none;padding:0;margin:0';
        for (const equipo of grupo.participantes) {
            const fila = document.createElement('li');
            fila.className = 'item-grupo';
            const nombre = document.createElement('span');
            nombre.className = 'nombre';
            const color = document.createElement('span');
            color.className = 'badge-color';
            color.style.background = colorEquipo(equipo.nombre);
            const texto = document.createElement('span');
            texto.className = 'txt';
            texto.textContent = `${equipo.nombre} (${equipo.liga})`;
            nombre.append(color,texto);
            fila.append(nombre);
            const clasificado = clasificadosFinales.some(e => Number(e.id) === Number(equipo.id));
            if (esAdministrador && faseTorneo === 'grupos') {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'btn-clasificar' + (clasificado ? ' clasificado' : '');
                boton.textContent = clasificado ? 'Clasificado ✓' : 'Clasificar';
                boton.onclick = () => clasificarEquipo(equipo, grupo);
                fila.appendChild(boton);
            } else if (clasificado) {
                const est = document.createElement('span');
                est.className = 'btn-clasificar clasificado etiqueta-clasificado';
                est.textContent = 'Clasificado ✓';
                fila.appendChild(est);
            }
            lista.appendChild(fila);
        }
        tarjeta.appendChild(lista);
        cont.appendChild(tarjeta);
    }
}
async function clasificarEquipo(equipo, grupo) {
    if (!esAdministrador || faseTorneo !== 'grupos') return;
    const id = Number(equipo.id);
    if (clasificadosFinales.some(e=>Number(e.id)===id)) {
        clasificadosFinales = clasificadosFinales.filter(e=>Number(e.id)!==id);
    } else {
        const idsGrupo = new Set(grupo.participantes.map(e=>Number(e.id)));
        clasificadosFinales = clasificadosFinales.filter(e=>!idsGrupo.has(Number(e.id)));
        clasificadosFinales.push(equipo);
    }
    renderizarTablasGrupos();
    await guardarAutomatico();
}
async function cargarClasificadosEnBracket() {
    if (!esAdministrador || !sorteoRealizado) return;
    if (clasificadosFinales.length < 2) {
        alert('Clasifica al menos dos equipos, como mínimo uno de cada grupo.');
        return;
    }
    // Orden estable siguiendo los grupos A, B, C... (independiente del orden de clics).
    clasificadosFinales = gruposActuales().flatMap(g=>g.participantes.filter(p=>clasificadosFinales.some(c=>Number(c.id)===Number(p.id))));
    resultadosRondas = {};
    campeonGuardado = null;
    campeonPendiente = null;
    faseTorneo = 'eliminatorias';
    dibujarBracket();
    mostrarPaso(3);
    await guardarAutomatico();
}
function calcularRondas() {
    let dimension=2;
    while (dimension < clasificadosFinales.length) dimension*=2;
    let entradas=[...clasificadosFinales, ...Array(dimension-clasificadosFinales.length).fill(null)];
    const estructura=[];
    let numero=0;
    let final=null;
    while (dimension >= 2) {
        const enfrentamientos=[];
        const siguientes=[];
        for (let m=0; m<dimension/2; m++) {
            const a=entradas[2*m], b=entradas[2*m+1];
            const clave=`r${numero}_m${m}`;
            let ganador;
            const elegido=Number(resultadosRondas[clave]);
            if (a === undefined || b === undefined) ganador=undefined;
            else if (a === null && b === null) ganador=null;
            else if (a === null) ganador=b;
            else if (b === null) ganador=a;
            else if (Number(a.id) === elegido) ganador=a;
            else if (Number(b.id) === elegido) ganador=b;
            else ganador=undefined;
            enfrentamientos.push({a,b,clave,ganador});
            siguientes.push(ganador);
        }
        estructura.push({dimension,enfrentamientos});
        entradas=siguientes;
        dimension=Math.floor(dimension/2);
        numero++;
    }
    final=entradas[0];
    return {estructura,final};
}
function elementoSlot(equipo, ganador, clave) {
    const boton=document.createElement('button');
    boton.type='button';
    boton.className='slot';
    if (equipo == null || equipo === undefined) {
        boton.classList.add('vacio');
        boton.textContent=equipo === null ? 'Pase libre' : 'Pendiente';
        boton.disabled=true;
        return boton;
    }
    const insig=document.createElement('span');
    insig.className='badge-color';
    insig.style.background=colorEquipo(equipo.nombre);
    boton.append(insig,document.createTextNode(`${equipo.nombre} (${equipo.liga})`));
    const elegido=Number(resultadosRondas[clave]) === Number(equipo.id);
    if (elegido) boton.classList.add('ganador-seleccionado');
    boton.disabled=!esAdministrador || faseTorneo === 'finalizado';
    if (!boton.disabled) boton.onclick=()=>seleccionarGanador(clave, equipo.id);
    return boton;
}
function dibujarBracket() {
    const arbol=document.getElementById('bracketTree');
    arbol.replaceChildren();
    const caja=document.getElementById('campeonBox');
    caja.style.display='none';
    const btn=document.getElementById('btnGuardarCampeon');
    btn.hidden=true;
    if (faseTorneo !== 'eliminatorias' && faseTorneo !== 'finalizado') return;
    const {estructura,final}=calcularRondas();
    const nombres={32:'16vos de Final',16:'Octavos de Final',8:'Cuartos de Final',4:'Semifinales',2:'Gran Final'};
    for (const ronda of estructura) {
        const col=document.createElement('div');
        col.className='bracket-col';
        const titulo=document.createElement('h3');
        titulo.textContent=nombres[ronda.dimension]||`Ronda de ${ronda.dimension}`;
        col.appendChild(titulo);
        for (const enfrentamiento of ronda.enfrentamientos) {
            const marco=document.createElement('div');
            marco.className='bracket-slot';
            const duelo=document.createElement('div');
            duelo.className='matchup';
            duelo.appendChild(elementoSlot(enfrentamiento.a,enfrentamiento.ganador,enfrentamiento.clave));
            duelo.appendChild(elementoSlot(enfrentamiento.b,enfrentamiento.ganador,enfrentamiento.clave));
            marco.appendChild(duelo);
            col.appendChild(marco);
        }
        arbol.appendChild(col);
    }
    const campeon = campeonGuardado === null ? null : porId.get(Number(campeonGuardado));
    if (faseTorneo === 'finalizado' && campeon) {
        caja.style.display='block';
        document.getElementById('nombreCampeon').textContent=campeon.nombre;
    } else if (esAdministrador && final && final !== null && final !== undefined) {
        campeonPendiente = Number(final.id);
        btn.hidden=false;
        btn.textContent=`Guardar y publicar campeón: ${final.nombre}`;
    } else {
        campeonPendiente=null;
    }
}
async function seleccionarGanador(clave, id) {
    if (!esAdministrador || faseTorneo !== 'eliminatorias') return;
    const [ronda] = clave.match(/^r(\d+)_m\d+$/).slice(1).map(Number);
    // Cambiar un ganador invalida las rondas posteriores.
    for (const k of Object.keys(resultadosRondas)) {
        const r=parseInt(k.match(/^r(\d+)_/)[1],10);
        if (r > ronda) delete resultadosRondas[k];
    }
    resultadosRondas[clave]=Number(id);
    campeonPendiente=null;
    dibujarBracket();
    await guardarAutomatico();
}
async function guardarCampeon() {
    if (!esAdministrador || faseTorneo !== 'eliminatorias' || campeonPendiente == null) return;
    const {final}=calcularRondas();
    if (!final || Number(final.id)!==campeonPendiente) return;
    campeonGuardado=campeonPendiente;
    faseTorneo='finalizado';
    dibujarBracket();
    await guardarAutomatico();
}
async function reiniciarTorneo() {
    if (!esAdministrador) return;
    if (!confirm(`¿Reiniciar el torneo ${nombreOrigenTorneo()} guardado? El sorteo, clasificaciones y campeón se perderán.`)) return;
    try {
        await guardadoEnCurso.catch(()=>{});
        await peticionGuardar('reiniciar');
        limpiarLocal();
        mostrarPaso(1);
        informar('El torneo se reinició correctamente. Puedes crear un nuevo sorteo.');
    } catch (e) {
        informar('No se pudo reiniciar el torneo: '+e.message,true);
    }
}

cargarOpcionesGrupos();
if (!esAdministrador) {
    document.getElementById('btnSortearGrupos').hidden=true;
    document.getElementById('bloqueAccionClasificar').hidden=true;
    document.getElementById('btnReiniciarTorneo').hidden=true;
    document.getElementById('numGrupos').disabled=true;
    document.getElementById('textoInstruccionesGrupos').textContent='Consulta los equipos de cada grupo y los que ya clasificaron a eliminatorias.';
    document.getElementById('textoInstruccionesFinal').textContent='Consulta los resultados guardados por el administrador.';
}
leerEstadoTorneo();
// Los espectadores ven las novedades al recargar o automáticamente cada 30 s.
if (!esAdministrador) {
    setInterval(() => {
        if (document.visibilityState === 'visible' && !cargandoEstado) {
            const pasoActivo=[1,2,3].find(n=>document.getElementById('paso'+n).classList.contains('activo')) || 1;
            leerEstadoTorneo().then(()=>{
                if (pasoActivo===1 || (pasoActivo===2 && sorteoRealizado) || (pasoActivo===3 && ['eliminatorias','finalizado'].includes(faseTorneo))) mostrarPaso(pasoActivo);
            });
        }
    }, 30000);
}
</script>

</body>
</html>

