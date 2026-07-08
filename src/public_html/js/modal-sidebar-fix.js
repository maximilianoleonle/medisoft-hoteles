/**
 * modal-sidebar-fix.js
 *
 * Oculta la barra lateral (#sidebar) mientras haya CUALQUIER modal abierto y la
 * restaura al cerrarse el ultimo. Es global y agnostico al patron de modal que
 * use cada vista: detecta la apertura observando el DOM en lugar de depender de
 * que cada boton llame a una funcion especifica.
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
        style.textContent =
            'body.' + BODY_CLASS + ' #sidebar,' +
            'body.' + BODY_CLASS + ' .sidebar-main {' +
            ' display: none !important;' +
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
