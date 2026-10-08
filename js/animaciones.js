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
