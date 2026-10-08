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
        endpoint: 'api_diseno.php',
        iniciar: iniciar,
        NOMBRES_RAREZA: NOMBRES_RAREZA
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})(window);