<?php
/** Render de las vistas y recursos JS originales, sin archivos JS separados. */
final class View
{
    public static function render(string $nombre, array $variables = []): void
    {
        $ruta = GOOLPASS_ROOT . '/app/Views/' . $nombre . '.php';
        if (!is_file($ruta)) throw new RuntimeException('Vista no encontrada: ' . $nombre);
        extract($variables, EXTR_SKIP);
        require $ruta;
    }
    public static function fragment(string $nombre, array $variables = []): void
    {
        self::render($nombre, $variables);
    }
    public static function cabecera(string $paginaActiva = ''): void
    {
        // layout.php contiene el encabezado HTML compartido y la portada.
        require_once GOOLPASS_ROOT . '/app/Views/layout.php';
        goolpass_cabecera($paginaActiva);
    }
    public static function script(string $nombre): void
    {
        $scripts = [
            'animaciones' => <<<'GOOLPASS_JS_ANIMACIONES'
/* ==========================================================================
   ANIMACIONES GLOBALES · INTERRUPTOR DE MODO LIGERO
   --------------------------------------------------------------------------
   Un solo interruptor para todas las paginas: apaga animaciones,
   transiciones y desenfoques (backdrop-filter) en equipos modestos.

     - Recuerda la preferencia en localStorage ('torneo-animaciones')
     - Aplica la clase `fx-sin-animacion` en <html> antes del primer
       pintado (sin parpadeo) y la replica en <body> al cargar el DOM
     - Respeta `prefers-reduced-motion` como valor por defecto
     - Expone window.Animaciones y emite el evento 'torneo:animaciones'

   Uso:
     <link rel="stylesheet" href="css/animaciones.css">
     <script src="js/animaciones.js"></script>      <!-- dentro de <head> -->
     <button type="button" data-anim-toggle>
         <span data-anim-estado>ON</span>
     </button>
   ========================================================================== */
(function (global) {
    'use strict';

    var doc  = global.document;
    var raiz = doc.documentElement;
    var CLAVE = 'torneo-animaciones';   // 'on' | 'off'
    var CLASE = 'fx-sin-animacion';     // misma clase que usa js/ficha_animaciones.js

    var activas = true;

    /* ------------------------------------------------------------------
       PREFERENCIA GUARDADA
       ------------------------------------------------------------------ */
    function leerGuardada() {
        try {
            var v = global.localStorage.getItem(CLAVE);
            if (v === 'off') return false;
            if (v === 'on')  return true;
        } catch (e) { /* almacenamiento no disponible */ }
        return null;
    }

    function guardar(valor) {
        try { global.localStorage.setItem(CLAVE, valor ? 'on' : 'off'); } catch (e) {}
    }

    function prefiereReducido() {
        return !!(global.matchMedia &&
                  global.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    /* ------------------------------------------------------------------
       PINTADO DEL ESTADO
       ------------------------------------------------------------------ */
    function pintar() {
        raiz.classList.toggle(CLASE, !activas);
        if (doc.body) doc.body.classList.toggle(CLASE, !activas);

        Array.prototype.forEach.call(doc.querySelectorAll('[data-anim-toggle]'), function (btn) {
            btn.setAttribute('aria-pressed', activas ? 'false' : 'true');
            btn.title = activas
                ? 'Desactivar animaciones (modo ligero)'
                : 'Activar animaciones';

            var estado = btn.querySelector('[data-anim-estado]');
            if (estado) estado.textContent = activas ? 'ON' : 'OFF';
        });
    }

    function avisar() {
        var datos = { activas: activas };
        var ev;
        try {
            ev = new global.CustomEvent('torneo:animaciones', { detail: datos });
        } catch (e) {
            ev = doc.createEvent('CustomEvent');
            ev.initCustomEvent('torneo:animaciones', false, false, datos);
        }
        doc.dispatchEvent(ev);
    }

    function fijar(valor) {
        activas = !!valor;
        guardar(activas);
        pintar();
        avisar();
    }

    function alternar() { fijar(!activas); }

    /* ------------------------------------------------------------------
       BOTONES CON data-anim-toggle
       ------------------------------------------------------------------ */
    function enlazar() {
        pintar();
        Array.prototype.forEach.call(doc.querySelectorAll('[data-anim-toggle]'), function (btn) {
            if (btn.getAttribute('data-anim-listo')) return;
            btn.setAttribute('data-anim-listo', '1');
            btn.addEventListener('click', alternar);
        });
    }

    /* ------------------------------------------------------------------
       ARRANQUE
       ------------------------------------------------------------------ */
    var guardada = leerGuardada();
    activas = (guardada === null) ? !prefiereReducido() : guardada;

    raiz.classList.toggle(CLASE, !activas);   // antes del primer pintado

    global.Animaciones = {
        estaActivo:  function () { return activas; },
        estaApagado: function () { return !activas; },
        activar:     function () { fijar(true); },
        desactivar:  function () { fijar(false); },
        alternar:    alternar
    };

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', enlazar);
    } else {
        enlazar();
    }
})(window);
GOOLPASS_JS_ANIMACIONES,
            'antireload' => <<<'GOOLPASS_JS_ANTIRELOAD'
/**
 * antireload.js · Cierra la sesión solo cuando la página se recarga (F5).
 *
 * - Un clic en un enlace o un envío de formulario es tipo "navigate"  → NO cierra sesión.
 * - Presionar F5 / Ctrl+R / botón «Recargar» es tipo "reload"        → cierra sesión (va al login).
 *
 * La navegación normal entre páginas del sitio NO se ve afectada.
 */
(function () {
    'use strict';

    var esRecarga = false;

    var nav = window.performance && performance.getEntriesByType &&
              performance.getEntriesByType('navigation')[0];

    if (nav && typeof nav.type === 'string') {
        // Navigation Timing Level 2 (navegadores modernos)
        esRecarga = (nav.type === 'reload');
    } else if (window.performance && performance.navigation) {
        // Compatibilidad con navegadores antiguos
        esRecarga = (performance.navigation.type === 1);
    }

    if (esRecarga) {
        // logout.php destruye la sesión y redirige al login.
        // replace() evita que esta página quede en el historial.
        window.location.replace('index.php?r=logout');
    }
})();
GOOLPASS_JS_ANTIRELOAD,
            'ligas' => <<<'GOOLPASS_JS_LIGAS'
document.addEventListener('DOMContentLoaded',function(){
  const radios=[...document.querySelectorAll('input[name="origen"]')];
  const internacional=document.getElementById('bloqueLigaInternacional');
  const nacional=document.getElementById('bloqueLigaNacional');
  const selectInt=document.getElementById('in_liga');
  const selectNac=document.getElementById('ligaNacional');
  const otra=document.getElementById('bloqueLigaOtra');
  const inputOtra=document.getElementById('ligaOtra');
  if(!radios.length||!selectInt||!selectNac||!inputOtra)return;
  function actualizar(){
    const esNacional=radios.find(r=>r.checked)?.value==='nacional';
    internacional.hidden=esNacional;
    nacional.hidden=!esNacional;
    selectInt.disabled=esNacional;
    selectInt.required=!esNacional;
    selectNac.disabled=!esNacional;
    selectNac.required=esNacional;
    const esOtra=esNacional&&selectNac.value==='__otra__';
    otra.hidden=!esOtra;
    inputOtra.disabled=!esOtra;
    inputOtra.required=esOtra;
    document.dispatchEvent(new Event('goolpass-liga-cambiada'));
  }
  radios.forEach(r=>r.addEventListener('change',actualizar));
  [selectInt,selectNac,inputOtra].forEach(c=>c.addEventListener('input',actualizar));
  actualizar();
});
function goolpassLigaSeleccionada(){
 const n=document.querySelector('input[name="origen"]:checked')?.value==='nacional';
 if(!n)return document.getElementById('in_liga')?.value||'';
 const v=document.getElementById('ligaNacional')?.value||'';
 return v==='__otra__'?(document.getElementById('ligaOtra')?.value||''):v;
}
GOOLPASS_JS_LIGAS,
            'ficha_diseno' => <<<'GOOLPASS_JS_FICHA_DISENO'
/* ==========================================================================
   FICHA · MOTOR DE DISEÑO GENERATIVO
   --------------------------------------------------------------------------
   Cada participante recibe un diseño irrepetible calculado de forma
   determinista a partir de su identidad (id + nombre + color + tema + semilla).

   Reglas:
     - Misma identidad  =>  mismo diseño  (estable entre recargas)
     - "Regenerar"     =>  nueva semilla  =>  diseño totalmente nuevo

   Uso:
     <script src="js/ficha_diseno.js"></script>
     <div class="card" data-nombre="Tibet" data-id="7"
          data-color="#ff4500" data-tema="tema-tibet" data-semilla=""></div>

   API publica: window.FichaDiseno.generar(cfg) / .aplicar(el) / .regenerar(el)
   ========================================================================== */
(function (global) {
    'use strict';

    /* ------------------------------------------------------------------
       1. RNG DETERMINISTA (mulberry32 + hash FNV-1a)
       ------------------------------------------------------------------ */
    function hashFnv(str) {
        var h = 0x811c9dc5;
        for (var i = 0; i < str.length; i++) {
            h ^= str.charCodeAt(i);
            h = (h + ((h << 1) + (h << 4) + (h << 7) + (h << 8) + (h << 24))) >>> 0;
        }
        return h >>> 0;
    }

    function mulberry32(seed) {
        var a = seed >>> 0;
        return function () {
            a = (a + 0x6D2B79F5) >>> 0;
            var t = Math.imul(a ^ (a >>> 15), 1 | a);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }

    /** Extrae n valores del flujo aleatorio */
    function tomar(rnd, n) {
        var o = [], i;
        for (i = 0; i < n; i++) o.push(rnd());
        return o;
    }

    /* ------------------------------------------------------------------
       2. PALETAS Y CATALOGOS
       ------------------------------------------------------------------ */
    // El acento principal (p1) siempre es el color elegido por el usuario.
    var PALETAS = [
        ['#ffffff', '#ffe8a3', '#0b0b0b'], // Oro imperial
        ['#8ef7ff', '#c9fbff', '#05202b'], // Neon glacial
        ['#ff9de2', '#ffe3f4', '#2a0b1e'], // Rosa neon
        ['#b8ff5c', '#e8ffcb', '#12200a'], // Verde acido
        ['#ffd23f', '#fff4c2', '#2b1d00'], // Oro puro
        ['#7cf9ff', '#e0ffff', '#082430'], // Cibernetico
        ['#ff6b6b', '#ffd4d4', '#2b0a0a'], // Carmesi
        ['#c9a3ff', '#ecdcff', '#1b0d2e'], // Amatista
        ['#9bff6b', '#dcffcb', '#0f2a06'], // Veneno
        ['#ffb3c9', '#ffe0ea', '#2b0f18'], // Sakura
        ['#6bffd4', '#ccffee', '#042b22'], // Turquesa
        ['#ffc94b', '#fff0c4', '#241a00']  // Ambar
    ];

    var NOMBRES_RAREZA = ['Común', 'Poco Común', 'Rara', 'Épica', 'Mítica'];
    var ROTACIONES_TONO = [30, 60, -40, 150, 180, 210];
    var FUENTES_TITULO = [
        '"Impact", "Arial Black", sans-serif',
        '"Georgia", "Times New Roman", serif',
        '"Trebuchet MS", Arial, sans-serif',
        '"Courier New", monospace',
        '"Palatino Linotype", "Book Antiqua", serif'
    ];

    /* ------------------------------------------------------------------
       3. UTILIDADES DE COLOR
       ------------------------------------------------------------------ */
    function hexToRgb(hex) {
        hex = (hex || '').replace('#', '').trim();
        if (/^[0-9a-f]{3}$/i.test(hex)) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        if (!/^[0-9a-f]{6}$/i.test(hex)) return { r: 255, g: 69, b: 0 };
        return {
            r: parseInt(hex.substring(0, 2), 16),
            g: parseInt(hex.substring(2, 4), 16),
            b: parseInt(hex.substring(4, 6), 16)
        };
    }

    function rgbToHex(r, g, b) {
        return '#' + [r, g, b].map(function (v) {
            var s = Math.max(0, Math.min(255, Math.round(v))).toString(16);
            return s.length === 1 ? '0' + s : s;
        }).join('');
    }

    /** Rota el tono (grados) manteniendo saturacion y luminosidad */
    function rotarTono(hex, grados) {
        var c = hexToRgb(hex);
        var r = c.r / 255, g = c.g / 255, b = c.b / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b);
        var l = (max + min) / 2, s = 0, h = 0, d = max - min;
        if (d !== 0) {
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
            else if (max === g) h = (b - r) / d + 2;
            else h = (r - g) / d + 4;
            h /= 6;
        }
        h = (h + grados / 360) % 1;
        if (h < 0) h += 1;

        function hue2rgb(p, q, t) {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1 / 6) return p + (q - p) * 6 * t;
            if (t < 1 / 2) return q;
            if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
            return p;
        }
        var r2, g2, b2;
        if (s === 0) {
            r2 = g2 = b2 = l;
        } else {
            var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            var p = 2 * l - q;
            r2 = hue2rgb(p, q, h + 1 / 3);
            g2 = hue2rgb(p, q, h);
            b2 = hue2rgb(p, q, h - 1 / 3);
        }
        return rgbToHex(r2 * 255, g2 * 255, b2 * 255);
    }

    function iniciales(nombre) {
        var partes = (nombre || '?').trim().split(/\s+/).filter(Boolean);
        if (!partes.length) return '?';
        if (partes.length === 1) return partes[0].substring(0, 2).toUpperCase();
        return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase();
    }

    function nuevaSemilla() {
        return Math.floor(Math.random() * 0xFFFFFFFF).toString(36).toUpperCase();
    }
    /* ------------------------------------------------------------------
       4. GENERADOR PRINCIPAL
       ------------------------------------------------------------------ */
    function generar(cfg) {
        cfg = cfg || {};
        var id      = String(cfg.id != null ? cfg.id : 0);
        var nombre  = cfg.nombre || '';
        var color   = cfg.color || '#ff4500';
        var tema    = cfg.tema || 'default';
        var semilla = (cfg.semilla || '').trim();

        // Sin semilla guardada se deriva de la identidad => diseño estable.
        if (!semilla) {
            semilla = hashFnv(id + '|' + nombre + '|' + color + '|' + tema)
                      .toString(36).toUpperCase();
        }

        var r = mulberry32(hashFnv(semilla + '#' + nombre));
        var v = tomar(r, 24);

        var paleta = PALETAS[Math.floor(v[0] * PALETAS.length)];
        var rareza = Math.min(4, Math.floor(v[1] * 5));

        return {
            semilla: semilla,
            p1: color,   // color elegido por el usuario
            p2: rotarTono(color, ROTACIONES_TONO[Math.floor(v[2] * ROTACIONES_TONO.length)]),
            p3: paleta[0], // tinta del motivo
            angulo:      Math.round(v[3] * 360),
            patron:      Math.floor(v[4] * 8),
            patAng:      Math.round(v[5] * 360),
            patSize:     Math.round(12 + v[6] * 26),
            patX:        Math.round(v[7] * 40),
            patY:        Math.round(v[8] * 40),
            patOp:       +(0.05 + v[9] * 0.10).toFixed(3),
            esq:         Math.floor(v[10] * 7),
            acento:      Math.floor(v[11] * 7),
            mono:        Math.floor(v[12] * 4),
            rareza:      rareza,
            rarezaNombre: NOMBRES_RAREZA[rareza],
            fuente:      Math.floor(v[13] * FUENTES_TITULO.length),
            largoTitulo: Math.floor(v[14] * 4),
            inclinacion: Math.floor(v[15] * 5) - 2,
            animacion:   Math.floor(v[16] * 4),
            monoTexto:   iniciales(nombre),
            codigo:      semilla.substring(0, 6)
        };
    }

    /* ------------------------------------------------------------------
       5. APLICACION AL DOM
       ------------------------------------------------------------------ */
    function aplicar(el, diseno) {
        if (!el) return null;
        diseno = diseno || generar({
            id:      el.dataset.id,
            nombre:  el.dataset.nombre,
            color:   el.dataset.color,
            tema:    el.dataset.tema,
            semilla: el.dataset.semilla
        });

        var st = el.style;
        st.setProperty('--d-p1', diseno.p1);
        st.setProperty('--d-p2', diseno.p2);
        st.setProperty('--d-p3', diseno.p3);
        st.setProperty('--d-ang', diseno.angulo + 'deg');
        st.setProperty('--d-pat-size', diseno.patSize + 'px');
        st.setProperty('--d-pat-ang', diseno.patAng + 'deg');
        st.setProperty('--d-pat-x', diseno.patX + 'px');
        st.setProperty('--d-pat-y', diseno.patY + 'px');
        st.setProperty('--d-pat-op', diseno.patOp);

        el.dataset.fx = '1';
        el.dataset.pat = diseno.patron;
        el.dataset.esq = diseno.esq;
        el.dataset.acento = diseno.acento;
        el.dataset.mono = diseno.mono;
        el.dataset.rareza = diseno.rareza;
        el.dataset.semilla = diseno.semilla;

        inyectarDecoracion(el, diseno);
        return diseno;
    }
    /** Crea (una sola vez) los elementos decorativos dentro de la ficha */
    function inyectarDecoracion(el, d) {
        var i;

        // Halo de rareza (epica y mitica)
        if (d.rareza >= 3 && !el.querySelector(':scope > .f-halo')) {
            var halo = document.createElement('div');
            halo.className = 'f-halo';
            halo.setAttribute('aria-hidden', 'true');
            el.insertBefore(halo, el.firstChild);
        }

        // Particulas para rareza mitica
        if (d.rareza >= 4 && !el.querySelector(':scope > .f-particulas')) {
            var cont = document.createElement('div');
            cont.className = 'f-particulas';
            cont.setAttribute('aria-hidden', 'true');
            for (i = 0; i < 14; i++) {
                var pt = document.createElement('i');
                pt.style.left = (6 + Math.random() * 88) + '%';
                pt.style.animationDuration = (2.6 + Math.random() * 3.2) + 's';
                pt.style.animationDelay = (Math.random() * 3) + 's';
                cont.appendChild(pt);
            }
            el.appendChild(cont);
        }

        // Acento / franja
        if (!el.querySelector(':scope > .f-accento')) {
            var acento = document.createElement('div');
            acento.className = 'f-accento';
            acento.setAttribute('aria-hidden', 'true');
            el.appendChild(acento);
        }

        // Marco geometrico
        if (!el.querySelector(':scope > .f-marco')) {
            var marco = document.createElement('div');
            marco.className = 'f-marco';
            marco.setAttribute('aria-hidden', 'true');
            el.appendChild(marco);
        }

        // Spotlight (hijo real: ::before esta ocupado por los temas culturales)
        if (!el.querySelector(':scope > .f-spot')) {
            var spot = document.createElement('div');
            spot.className = 'f-spot';
            spot.setAttribute('aria-hidden', 'true');
            el.appendChild(spot);
        }

        // Barrido de brillo
        if (!el.querySelector(':scope > .f-sheen')) {
            var sheen = document.createElement('div');
            sheen.className = 'f-sheen';
            sheen.setAttribute('aria-hidden', 'true');
            el.appendChild(sheen);
        }

        // Reflejo inferior
        if (!el.querySelector(':scope > .f-reflejo')) {
            var ref = document.createElement('div');
            ref.className = 'f-reflejo';
            ref.setAttribute('aria-hidden', 'true');
            el.appendChild(ref);
        }

        // Monograma
        var mono = el.querySelector(':scope > .f-mono');
        if (!mono) {
            mono = document.createElement('span');
            mono.className = 'f-mono';
            mono.setAttribute('aria-hidden', 'true');
            mono.textContent = d.monoTexto;
            el.appendChild(mono);
        }

        // Sello de rareza + codigo
        var sello = el.querySelector(':scope > .f-sello');
        if (!sello) {
            sello = document.createElement('div');
            sello.className = 'f-sello';
            sello.setAttribute('aria-hidden', 'true');
            sello.innerHTML = '<span class="f-raridad"></span><span class="f-codigo"></span>';
            el.appendChild(sello);
        }
        sello.querySelector('.f-raridad').textContent = d.rarezaNombre;
        sello.querySelector('.f-codigo').textContent = '#' + d.codigo;

        // Estilo de titulo propio de la ficha
        var titulo = el.querySelector('h3, h1');
        if (titulo) {
            titulo.style.fontFamily = FUENTES_TITULO[d.fuente];
            titulo.style.letterSpacing = ['0px', '1.5px', '3px', '5px'][d.largoTitulo];
            titulo.style.transform = 'skewX(' + d.inclinacion + 'deg)';
        }

        el.classList.add('fx-sheen');
    }

    /** Nueva semilla + reaplicacion + persistencia opcional */
    function regenerar(el) {
        var semilla = nuevaSemilla();
        el.dataset.semilla = semilla;
        var d = aplicar(el, generar({
            id:      el.dataset.id,
            nombre:  el.dataset.nombre,
            color:   el.dataset.color,
            tema:    el.dataset.tema,
            semilla: semilla
        }));

        var id = el.dataset.id;
        if (id && global.fetch && global.FichaDiseno && global.FichaDiseno.endpoint) {
            var cuerpo = new URLSearchParams();
            cuerpo.append('id', id);
            cuerpo.append('semilla', semilla);
            global.fetch(global.FichaDiseno.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: cuerpo.toString()
            })['catch'](function () {
                /* Silencioso: el diseño ya cambio en pantalla */
            });
        }
        return d;
    }
    /* ------------------------------------------------------------------
       6. INICIALIZACION AUTOMATICA
       ------------------------------------------------------------------ */
    function iniciar() {
        var fichas = document.querySelectorAll('.card[data-nombre], .ficha-card[data-nombre]');
        Array.prototype.forEach.call(fichas, function (el, i) {
            if (!el.style.getPropertyValue('--fx-delay')) {
                el.style.setProperty('--fx-delay', (i * 70) + 'ms');
            }
            aplicar(el);

            // Indice de linea para el revelado escalonado
            var lineas = el.querySelectorAll('.detalles-box .linea-dato, .seccion-datos .dato-linea');
            Array.prototype.forEach.call(lineas, function (l, j) {
                l.style.setProperty('--i', j);
            });
        });

        // Boton "Regenerar diseno" en el listado
        var btn = document.getElementById('btn-fx-regen');
        if (btn) {
            btn.addEventListener('click', function () {
                var fichas2 = document.querySelectorAll('.grid-participantes .card[data-nombre]');
                Array.prototype.forEach.call(fichas2, function (el, i) {
                    setTimeout(function () {
                        regenerar(el);
                        // Reinicia la animacion de entrada
                        el.style.setProperty('--fx-delay', (i * 55) + 'ms');
                    }, i * 45);
                });
            });
        }
    }

    global.FichaDiseno = {
        generar: generar,
        aplicar: aplicar,
        regenerar: regenerar,
        nuevaSemilla: nuevaSemilla,
        rotarTono: rotarTono,
        iniciales: iniciales,
        endpoint: 'index.php?r=api_diseno',
        iniciar: iniciar,
        NOMBRES_RAREZA: NOMBRES_RAREZA
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})(window);
GOOLPASS_JS_FICHA_DISENO,
            'ficha_animaciones' => <<<'GOOLPASS_JS_FICHA_ANIMACIONES'
/* ==========================================================================
   FICHA · MOTOR DE ANIMACIONES
   --------------------------------------------------------------------------
   Efectos sobre las fichas generadas por js/ficha_diseno.js:
     - Entrada escalonada al hacer scroll (IntersectionObserver)
     - Tilt 3D + spotlight siguiendo al puntero
     - Barrido de brillo al pasar el cursor
     - Contador animado del Año / Era
     - Overlay de presentación a pantalla completa con confeti por rareza
     - Boton global de animaciones (sincronizado con js/animaciones.js)

   Uso:  <script src="js/ficha_animaciones.js"></script>
   ========================================================================== */
(function (global) {
    'use strict';

    var doc = global.document;
    var ANIM_ACTIVAS = true;

    /** El interruptor global (js/animaciones.js) tiene la ultima palabra */
    function sinAnimacion() {
        if (global.Animaciones) return global.Animaciones.estaApagado();
        return !ANIM_ACTIVAS || doc.documentElement.classList.contains('fx-sin-animacion');
    }

    function fichas() {
        return doc.querySelectorAll('.card[data-fx], .ficha-card[data-fx]');
    }

    /* ------------------------------------------------------------------
       1. ENTRADA ESCALONADA AL HACER SCROLL
       ------------------------------------------------------------------ */
    function entradaAlScroll() {
        var nodos = fichas();
        if (sinAnimacion() || !('IntersectionObserver' in global)) return;

        var obs = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.style.setProperty('--fx-delay',
                        (Math.min(9, Number(e.target.dataset.fxOrden || 0)) * 65) + 'ms');
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        Array.prototype.forEach.call(nodos, function (el, i) {
            el.dataset.fxOrden = i;
            obs.observe(el);
        });
    }

    /* ------------------------------------------------------------------
       2. TILT 3D + SPOTLIGHT
       ------------------------------------------------------------------ */
    function inclinacion() {
        if (sinAnimacion()) return;
        if (global.matchMedia &&
            global.matchMedia('(pointer: coarse)').matches) return;

        Array.prototype.forEach.call(fichas(), function (el) {
            el.addEventListener('mouseenter', function () {
                el.classList.add('fx-hover');
            });
            el.addEventListener('mouseleave', function () {
                el.classList.remove('fx-hover');
                el.style.setProperty('--fx-rx', '0deg');
                el.style.setProperty('--fx-ry', '0deg');
            });
            el.addEventListener('mousemove', function (ev) {
                var r = el.getBoundingClientRect();
                var x = (ev.clientX - r.left) / r.width;   // 0..1
                var y = (ev.clientY - r.top) / r.height;   // 0..1
                el.style.setProperty('--fx-mx', (x * 100) + '%');
                el.style.setProperty('--fx-my', (y * 100) + '%');
                el.style.setProperty('--fx-rx', ((0.5 - y) * 14).toFixed(2) + 'deg');
                el.style.setProperty('--fx-ry', ((x - 0.5) * 16).toFixed(2) + 'deg');
            });
        });
    }

    /* ------------------------------------------------------------------
       3. CONTADOR ANIMADO (Año / Era numerico)
       ------------------------------------------------------------------ */
    function animarContadores() {
        var apagado = sinAnimacion();
        Array.prototype.forEach.call(fichas(), function (el) {
            var celdas = el.querySelectorAll('.linea-dato, .dato-linea');
            Array.prototype.forEach.call(celdas, function (celda) {
                var texto = celda.textContent;
                var m = texto.match(/(\d{2,5})/);
                if (!m) return;
                var destino = parseInt(m[1], 10);
                // Solo valores con sentido de año (1000 - 3000)
                if (destino < 1000 || destino > 3000) return;

                var nodoTexto = Array.prototype.filter.call(celda.childNodes, function (n) {
                    return n.nodeType === 3 && n.nodeValue.indexOf(m[1]) !== -1;
                })[0];
                if (!nodoTexto) return;

                // Modo ligero: se muestra el valor final sin animarlo
                if (apagado) {
                    nodoTexto.nodeValue = texto.replace(m[1], destino);
                    return;
                }

                var inicio = Date.now();
                var dur = 1100;
                celda.classList.add('f-contador');

                (function paso() {
                    var t = Math.min(1, (Date.now() - inicio) / dur);
                    // easeOutExpo
                    var eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
                    nodoTexto.nodeValue = texto
                        .replace(m[1], Math.round(destino * eased));
                    if (t < 1) global.requestAnimationFrame(paso);
                })();
            });
        });
    }
    /* ------------------------------------------------------------------
       4. OVERLAY DE PRESENTACION
       ------------------------------------------------------------------ */
    var overlay = null;

    function crearOverlay() {
        if (overlay) return overlay;
        overlay = doc.createElement('div');
        overlay.className = 'fx-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.innerHTML =
            '<button class="fx-cerrar" aria-label="Cerrar">✕</button>' +
            '<div class="fx-stage"></div>' +
            '<p class="fx-pista">Clic o ESC para cerrar</p>';

        overlay.addEventListener('click', function (ev) {
            if (ev.target === overlay || ev.target.classList.contains('fx-cerrar')) {
                cerrarOverlay();
            }
        });
        doc.body.appendChild(overlay);
        return overlay;
    }

    function abrirOverlay(ficha) {
        var ov = crearOverlay();
        var stage = ov.querySelector('.fx-stage');

        // Clon: la ficha original no se mueve
        var clon = ficha.cloneNode(true);
        clon.removeAttribute('data-fx-delay');
        clon.style.setProperty('--fx-delay', '0ms');
        clon.classList.remove('fx-hover');

        stage.innerHTML = '';
        stage.appendChild(clon);

        // Reaplica el diseno al clon (las capas decorativas ya existen)
        if (global.FichaDiseno) {
            global.FichaDiseno.aplicar(clon);
            var lineas = clon.querySelectorAll('.linea-dato, .dato-linea');
            Array.prototype.forEach.call(lineas, function (l, j) {
                l.style.setProperty('--i', j);
            });
        }

        ov.classList.add('fx-abierto');
        doc.body.style.overflow = 'hidden';

        // Confeti proporcional a la rareza
        soltarConfeti(Number(ficha.dataset.rareza || 0));
    }

    function cerrarOverlay() {
        if (!overlay) return;
        overlay.classList.remove('fx-abierto');
        doc.body.style.overflow = '';
        global.setTimeout(function () {
            if (overlay) overlay.querySelector('.fx-stage').innerHTML = '';
        }, 320);
    }

    /** Lluvia de confeti: mas piezas y mas color a mayor rareza */
    function soltarConfeti(rareza) {
        if (sinAnimacion()) return;   // el confeti es decorativo y costoso
        var escala = [
            ['#9aa4b2', '#6b7280'],
            ['#4ade80', '#22c55e'],
            ['#3b82f6', '#60a5fa'],
            ['#a855f7', '#d946ef'],
            ['#ff9500', '#ffd23f', '#ff4d00', '#fff']
        ][Math.max(0, Math.min(4, rareza))];

        var n = 14 + rareza * 12;
        for (var i = 0; i < n; i++) {
            (function (idx) {
                global.setTimeout(function () {
                    var c = doc.createElement('i');
                    c.className = 'fx-confeti';
                    c.style.left = (Math.random() * 100) + 'vw';
                    c.style.background = escala[idx % escala.length];
                    c.style.borderRadius = Math.random() > 0.6 ? '50%' : '1px';
                    c.style.animationDuration = (2.2 + Math.random() * 2.4) + 's';
                    doc.body.appendChild(c);
                    global.setTimeout(function () { c.remove(); }, 4800);
                }, idx * 45);
            })(i);
        }
    }

    /* ------------------------------------------------------------------
       5. DOBLE CLIC / CLIC SOBRE LA IMAGEN => overlay
       ------------------------------------------------------------------ */
    function activarOverlay() {
        Array.prototype.forEach.call(fichas(), function (el) {
            el.addEventListener('dblclick', function (ev) {
                if (ev.target.closest('a, button')) return;
                abrirOverlay(el);
            });
        });
    }

    /* ------------------------------------------------------------------
       6. TECLADO: ESC cierra, 1-5 filtra rareza
       ------------------------------------------------------------------ */
    function teclado() {
        doc.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && overlay) {
                cerrarOverlay();
                return;
            }
            // Atajo para presentar la primera ficha
            if (ev.key === 'Enter' && ev.target === doc.body) {
                var primera = fichas()[0];
                if (primera) abrirOverlay(primera);
            }
        });
    }
    /* ------------------------------------------------------------------
       7. BOTONES FLOTANTES: animaciones on/off + regenerar
       ------------------------------------------------------------------ */
    function barraControles() {
        if (doc.getElementById('btn-fx-anim')) return; // ya existe en el HTML

        var btnAnim = doc.createElement('button');
        btnAnim.className = 'btn-fx';
        btnAnim.id = 'btn-fx-anim';
        btnAnim.type = 'button';
        btnAnim.innerHTML = '<span>🎬</span><span>Animaciones: ON</span>';

        var etiqueta = btnAnim.querySelector('span:last-child');

        function pintarBoton() {
            etiqueta.textContent = 'Animaciones: ' + (ANIM_ACTIVAS ? 'ON' : 'OFF');
        }

        btnAnim.addEventListener('click', function () {
            // El interruptor global (js/animaciones.js) guarda y sincroniza
            if (global.Animaciones) {
                global.Animaciones.alternar();
                return;
            }
            ANIM_ACTIVAS = !ANIM_ACTIVAS;
            doc.body.classList.toggle('fx-sin-animacion', !ANIM_ACTIVAS);
            btnAnim.querySelector('span:last-child').textContent =
                'Animaciones: ' + (ANIM_ACTIVAS ? 'ON' : 'OFF');
        });

        if (global.Animaciones) {
            ANIM_ACTIVAS = global.Animaciones.estaActivo();
            doc.addEventListener('torneo:animaciones', function (ev) {
                ANIM_ACTIVAS = !!(ev.detail && ev.detail.activas);
                pintarBoton();
            });
        }

        pintarBoton();
        doc.body.appendChild(btnAnim);
    }

    /* ------------------------------------------------------------------
       8. ARRANQUE
       ------------------------------------------------------------------ */
    function iniciar() {
        if (global.FichaDiseno) global.FichaDiseno.iniciar();

        var reduce = global.matchMedia &&
                     global.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) {
            ANIM_ACTIVAS = false;
            doc.body.classList.add('fx-sin-animacion');
        }

        entradaAlScroll();
        inclinacion();
        animarContadores();
        activarOverlay();
        teclado();
        barraControles();
    }

    global.FichaAnimaciones = {
        iniciar: iniciar,
        abrirOverlay: abrirOverlay,
        cerrarOverlay: cerrarOverlay,
        soltarConfeti: soltarConfeti,
        estaActiva: function () { return ANIM_ACTIVAS; }
    };

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})(window);
GOOLPASS_JS_FICHA_ANIMACIONES,
        ];
        if (!array_key_exists($nombre,$scripts)) return;
        // Evita el cierre accidental del elemento SCRIPT en comentarios de JS.
        $js = str_ireplace('</script', '<\/script', $scripts[$nombre]);
        echo '<script>' . "\n" . $js . "\n" . '</script>';
    }
}
