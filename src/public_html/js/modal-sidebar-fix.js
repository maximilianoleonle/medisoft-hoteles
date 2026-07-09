/**
 * modal-sidebar-fix.js
 *
 * Mientras haya CUALQUIER modal abierto, baja la barra lateral (#sidebar) a
 * z-index:1 (y pointer-events:none) para que el BACKDROP del propio modal la cubra
 * y difumine igual que al resto del contenido, y la restaura al cerrarse el ultimo.
 *
 * Historia: (1) antes la ocultaba con display:none -> salto de layout (desaparecia
 * al abrir / reaparecia al cerrar). (2) luego le aplicaba un filter:blur propio ->
 * como la clase se quita tarde (cuando el modal se marca `hidden` al terminar su
 * animacion de cierre) la sidebar "entraba tarde", desfasada del backdrop. (3) ahora
 * no le aplica ningun filtro: solo la manda debajo del modal y deja que el backdrop
 * (que aparece/desvanece CON la animacion del modal) la difumine en sincronia. Sin
 * reflow y sin desfase. Requiere que el modal sea full-bleed (cubra la franja de la
 * sidebar); los modales del sistema lo son (p.ej. #modalLimpieza tiene override).
 *
 * Es global y agnostico al patron de modal que use cada vista: detecta la apertura
 * observando el DOM en lugar de depender de que cada boton llame a una funcion.
 *
 * Patrones de modal soportados en el proyecto:
 *   - Estandar ARIA: <div role="dialog" aria-modal="true"> (p.ej. Caja: "fixed inset-0 hidden")
 *   - <div class="modal-overlay" style="display:none|flex|block"> ... </div>
 *   - <div class="modal-overlay hidden"> ... </div>
 *   - Bootstrap: <div class="modal show|active|open">
 *   - <dialog open>
 *   - SweetAlert2: .swal2-container
 *
 * No requiere tocar cada modal: basta con incluir este archivo una vez.
 */
(function () {
    'use strict';

    if (window.__msModalSidebarFixReady) {
        return;
    }
    window.__msModalSidebarFixReady = true;

    var BODY_CLASS = 'ms-modal-hide-sidebar';

    // Selectores de "contenedor" de modal. Se evalua su visibilidad real; que un
    // elemento exista en el DOM no implica que este abierto.
    var MODAL_SELECTORS = [
        '[aria-modal="true"]',   // estandar ARIA: cubre la mayoria de modales del proyecto
        '[role="dialog"]',
        '.modal-overlay',
        '.modal.show',
        '.modal.active',
        '.modal.open',
        'dialog[open]',
        '.swal2-container',
        '[data-modal-open="true"]'
    ].join(',');

    function esVisible(el) {
        if (!el || el.classList.contains('hidden')) {
            return false;
        }
        var cs = window.getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity) === 0) {
            return false;
        }
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function hayModalAbierto() {
        var nodos = document.querySelectorAll(MODAL_SELECTORS);
        for (var i = 0; i < nodos.length; i++) {
            // Opt-out: los nodos marcados con data-ms-keep-sidebar (p.ej. el panel del
            // Copiloto, que es un widget flotante no bloqueante) NO ocultan la sidebar.
            if (nodos[i].closest('[data-ms-keep-sidebar]')) {
                continue;
            }
            if (esVisible(nodos[i])) {
                return true;
            }
        }
        return false;
    }

    var estadoAplicado = null;

    function aplicar() {
        var abierto = hayModalAbierto();
        if (abierto === estadoAplicado) {
            return;
        }
        estadoAplicado = abierto;
        document.body.classList.toggle(BODY_CLASS, abierto);
    }

    // Coalescer multiples mutaciones en un solo recalculo por frame.
    var programado = false;
    function programar() {
        if (programado) {
            return;
        }
        programado = true;
        var raf = window.requestAnimationFrame || function (cb) { return window.setTimeout(cb, 16); };
        raf(function () {
            programado = false;
            aplicar();
        });
    }

    // La regla vive en JS para que el fix sea autocontenido (un solo archivo).
    function inyectarEstilo() {
        if (document.getElementById('ms-modal-sidebar-fix-style')) {
            return;
        }
        var style = document.createElement('style');
        style.id = 'ms-modal-sidebar-fix-style';
        // Selectores con clases suficientes para ganar (por especificidad) al
        // z-index:900 !important base de la sidebar y dejarla DEBAJO del modal.
        //
        // NO le aplicamos un filter propio a la sidebar: eso creaba un desfase de
        // timing (el filter se quitaba tarde, al marcarse el modal `hidden` tras su
        // animación de cierre, así que la sidebar "entraba tarde"). En su lugar la
        // bajamos a z-index:1 y dejamos que el BACKDROP del propio modal (oscuro +
        // blur) la cubra igual que al resto del contenido: como ese backdrop aparece
        // y se desvanece CON la animación del modal, la sidebar entra/sale en perfecta
        // sincronía con el resto del sistema. (Requiere que el modal sea full-bleed;
        // p.ej. #modalLimpieza tiene su override desktop para cubrir la franja.)
        style.textContent =
            'body.' + BODY_CLASS + ' #sidebar.sidebar-main.hotel-sidebar,' +
            'body.' + BODY_CLASS + ' #sidebar.sidebar-main.sidebar-saas,' +
            'body.' + BODY_CLASS + ' #sidebar,' +
            'body.' + BODY_CLASS + ' .sidebar-main {' +
            ' z-index: 1 !important;' +
            ' pointer-events: none !important;' +
            '}';
        (document.head || document.documentElement).appendChild(style);
    }

    function iniciar() {
        inyectarEstilo();
        aplicar();

        var observer = new MutationObserver(programar);
        observer.observe(document.body, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'open']
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
