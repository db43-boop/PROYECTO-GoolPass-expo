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
        window.location.replace('logout.php');
    }
})();
