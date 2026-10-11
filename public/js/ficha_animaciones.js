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