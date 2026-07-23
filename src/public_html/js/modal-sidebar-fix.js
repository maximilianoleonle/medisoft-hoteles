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
 * Ademas de la clase para la sidebar, publica en <body> la clase generica
 * `ms-modal-abierto` mientras haya un modal visible, para que otros flotantes
 * (p.ej. el widget del Copiloto en movil) puedan retirarse con solo CSS.
 *
 * Y en TACTIL congela el fondo mientras hay un modal abierto: la pagina de
 * atras deja de deslizarse y solo se desplaza el contenido del modal. Expone
 * window.msBloquearFondo(clave, activo) para que otros flotantes que no son
 * modales (el panel del Copiloto) compartan el mismo candado; ver el bloque
 * "Candado del fondo" mas abajo.
 *
 * Opt-outs por atributo (en el nodo del modal o un ancestro):
 *   - data-ms-no-modal: NO es un modal (widgets flotantes como el panel del
 *     Copiloto); no activa ninguna de las dos clases.
 *   - data-ms-keep-sidebar: es un modal pero gestiona su sidebar por su cuenta
 *     (p.ej. #modalLimpieza); activa ms-modal-abierto pero no la de sidebar.
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
    // Marcador generico "hay un modal visible": lo consumen otros flotantes via CSS.
    var BODY_CLASS_GLOBAL = 'ms-modal-abierto';
    // Fondo congelado: el body pasa a position:fixed (ver "Candado del fondo").
    var BODY_CLASS_LOCK = 'ms-fondo-bloqueado';

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
        '[data-modal-open="true"]',
        '.hdv-lightbox'          // foto ampliada de habitaciones: es modal, pero no traia ni role ni aria-modal
    ].join(',');

    // ── Candado del fondo (solo tactil) ──────────────────────────────────
    // Con un modal abierto la pagina de atras no se desliza; solo se desplaza
    // el contenido del modal.
    //
    // position:fixed y NO overflow:hidden a secas: iOS Safari ignora el
    // overflow del body y la pagina se seguia moviendo detras del modal. El
    // precio es guardar y devolver el scroll a mano (body.style.top), porque
    // al fijar el body la pagina salta al inicio.
    //
    // Se lleva por CLAVES en vez de un booleano porque hay varios candidatos a
    // ponerlo (los modales de aqui, el panel del Copiloto): el fondo se libera
    // cuando lo suelta el ULTIMO, y el scroll que se devuelve es el que guardo
    // el PRIMERO. Si cada uno guardara el suyo, el segundo leeria 0 -- el body
    // ya esta fijo -- y al cerrar la pagina saltaria al inicio.
    //
    // Solo en tactil: en escritorio congelar el fondo hace desaparecer la barra
    // de desplazamiento y la pagina da un brinco lateral al abrir cada modal.
    var mqTactil = window.matchMedia('(pointer: coarse)');
    var clavesFondo = {};
    var scrollGuardado = 0;

    function alguienPideFondo() {
        for (var clave in clavesFondo) {
            if (Object.prototype.hasOwnProperty.call(clavesFondo, clave) && clavesFondo[clave]) {
                return true;
            }
        }
        return false;
    }

    function sincronizarFondo() {
        var debe = mqTactil.matches && alguienPideFondo();
        if (debe === document.body.classList.contains(BODY_CLASS_LOCK)) {
            return;
        }
        if (debe) {
            scrollGuardado = window.pageYOffset || document.documentElement.scrollTop || 0;
            document.body.style.top = (-scrollGuardado) + 'px';
            document.body.classList.add(BODY_CLASS_LOCK);
        } else {
            document.body.classList.remove(BODY_CLASS_LOCK);
            document.body.style.top = '';
            window.scrollTo(0, scrollGuardado);
        }
    }

    // API compartida: window.msBloquearFondo('copiloto', true).
    window.msBloquearFondo = function (clave, activo) {
        clavesFondo[String(clave)] = !!activo;
        sincronizarFondo();
    };

    // Un modal que vive en el flujo del documento (no es fixed) NECESITA el
    // scroll de la pagina para verse completo: congelar el fondo lo dejaria
    // inalcanzable. Por eso el candado solo se pone si el modal -- o algun
    // ancestro suyo, que es donde suele estar el overlay -- es fixed.
    function esFlotante(el) {
        for (var n = el; n && n !== document.body; n = n.parentElement) {
            if (window.getComputedStyle(n).position === 'fixed') {
                return true;
            }
        }
        return false;
    }

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

    function estadoModales() {
        var nodos = document.querySelectorAll(MODAL_SELECTORS);
        var abierto = false;
        var bajarSidebar = false;
        var bloquearFondo = false;
        for (var i = 0; i < nodos.length; i++) {
            // data-ms-no-modal: flotantes no bloqueantes (p.ej. el panel del
            // Copiloto) que casan con los selectores pero NO son un modal; no
            // cuentan para ningun efecto.
            if (nodos[i].closest('[data-ms-no-modal]')) {
                continue;
            }
            if (!esVisible(nodos[i])) {
                continue;
            }
            abierto = true;
            if (!bloquearFondo && esFlotante(nodos[i])) {
                bloquearFondo = true;
            }
            // data-ms-keep-sidebar: el modal gestiona la sidebar por su cuenta
            // (p.ej. #modalLimpieza agrega su clase propia al abrirse), asi que
            // no activa el ocultador global de sidebar; aun asi cuenta como
            // "modal abierto" para el resto de flotantes (ms-modal-abierto).
            if (!nodos[i].closest('[data-ms-keep-sidebar]')) {
                bajarSidebar = true;
                if (bloquearFondo) {
                    break; // los tres estados ya son true: no hay mas que buscar
                }
            }
        }
        return { abierto: abierto, bajarSidebar: bajarSidebar, bloquearFondo: bloquearFondo };
    }

    var estadoAplicado = null;

    function aplicar() {
        var estado = estadoModales();
        if (estadoAplicado
            && estado.abierto === estadoAplicado.abierto
            && estado.bajarSidebar === estadoAplicado.bajarSidebar
            && estado.bloquearFondo === estadoAplicado.bloquearFondo) {
            return;
        }
        estadoAplicado = estado;
        document.body.classList.toggle(BODY_CLASS, estado.bajarSidebar);
        document.body.classList.toggle(BODY_CLASS_GLOBAL, estado.abierto);
        // Poner el candado muta el <body>, o sea que vuelve a disparar al
        // observer; la comparacion de arriba corta ese rebote en el acto.
        window.msBloquearFondo('modal', estado.bloquearFondo);
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
            '}' +
            // Fondo congelado. El scroll de la pagina lo lleva el JS en
            // body.style.top; aqui solo se define QUE hace el candado.
            // !important porque varias vistas le fijan position al body.
            'body.' + BODY_CLASS_LOCK + ' {' +
            ' position: fixed !important;' +
            ' left: 0 !important;' +
            ' right: 0 !important;' +
            ' width: 100% !important;' +
            ' overflow: hidden;' +
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

        // Los modales del sistema abren/cierran con fade (opacity 0 <-> 1). En el
        // frame de la mutacion de clase la opacity computada aun es 0, asi que
        // esVisible() los descarta, y la transicion en si no genera mutaciones que
        // re-disparen el observer: el estado quedaba congelado en "sin modal".
        // Los eventos de transicion/animacion burbujean hasta document y cubren
        // ese hueco: al progresar o terminar el fade se recalcula y converge.
        ['transitionrun', 'transitionend', 'transitioncancel', 'animationstart', 'animationend']
            .forEach(function (tipo) {
                document.addEventListener(tipo, programar, true);
            });

        // Si el aparato deja de ser tactil (raro, pero pasa al conectar un
        // mouse o al proyectar), el candado se suelta solo en vez de dejar la
        // pagina congelada.
        if (mqTactil.addEventListener) {
            mqTactil.addEventListener('change', sincronizarFondo);
        } else if (mqTactil.addListener) {
            mqTactil.addListener(sincronizarFondo);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
