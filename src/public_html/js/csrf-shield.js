/**
 * Escudo CSRF global — Medisoft Hoteles
 *
 * Garantiza que TODA petición mutante (POST/PUT/PATCH/DELETE) del mismo
 * origen lleve el token CSRF, sin depender de que cada vista lo recuerde:
 *
 *  1. Parche a XMLHttpRequest → header X-CSRF-Token (cubre jQuery $.ajax).
 *  2. Parche a fetch()        → header X-CSRF-Token.
 *  3. Listener de submit      → inyecta <input name="csrf_token"> en forms POST.
 *  4. Parche a form.submit()  → mismo caso para envíos programáticos
 *     (submit() nativo NO dispara el evento submit).
 *
 * El token sale del <meta name="csrf-token"> que emite layout/header.php.
 * Este script debe cargarse SIN defer y ANTES que cualquier otro JS que
 * haga peticiones. La validación del lado servidor vive en Router::dispatch.
 */
(function () {
    'use strict';

    var METODOS_SEGUROS = { GET: 1, HEAD: 1, OPTIONS: 1 };

    function token() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function esMismoOrigen(url) {
        if (!url) return true; // URL vacía o relativa → mismo origen
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return true; // URL relativa inválida para el parser: tratar como local
        }
    }

    function esMutante(metodo) {
        return !METODOS_SEGUROS[String(metodo || 'GET').toUpperCase()];
    }

    // ── 1. XMLHttpRequest (jQuery incluido) ────────────────────────────────
    var _open = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (metodo, url) {
        this._msCsrfInfo = { metodo: metodo, url: url };
        return _open.apply(this, arguments);
    };

    var _send = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function () {
        try {
            var info = this._msCsrfInfo;
            if (info && esMutante(info.metodo) && esMismoOrigen(info.url)) {
                this.setRequestHeader('X-CSRF-Token', token());
            }
        } catch (e) { /* nunca romper la petición por el escudo */ }
        return _send.apply(this, arguments);
    };

    // ── 2. fetch ───────────────────────────────────────────────────────────
    if (window.fetch) {
        var _fetch = window.fetch;
        window.fetch = function (entrada, opciones) {
            try {
                var metodo = (opciones && opciones.metodo) || (opciones && opciones.method)
                    || (entrada && entrada.method) || 'GET';
                var url = (typeof entrada === 'string') ? entrada
                    : (entrada && entrada.url) || '';

                if (esMutante(metodo) && esMismoOrigen(url)) {
                    opciones = opciones || {};
                    var headers = new Headers(
                        opciones.headers
                        || (entrada instanceof Request ? entrada.headers : undefined)
                    );
                    if (!headers.has('X-CSRF-Token')) {
                        headers.set('X-CSRF-Token', token());
                    }
                    opciones.headers = headers;
                }
            } catch (e) { /* nunca romper la petición por el escudo */ }
            return _fetch.call(this, entrada, opciones);
        };
    }

    // ── 3. Forms POST clásicos (evento submit, fase de captura) ───────────
    function inyectarCampo(form) {
        if (!form || String(form.method).toLowerCase() !== 'post') return;
        if (!esMismoOrigen(form.getAttribute('action') || '')) return;
        if (form.querySelector('input[name="csrf_token"]')) return;

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = token();
        form.appendChild(input);
    }

    document.addEventListener('submit', function (ev) {
        inyectarCampo(ev.target);
    }, true);

    // ── 4. form.submit() programático ──────────────────────────────────────
    var _submit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        try { inyectarCampo(this); } catch (e) { /* no romper el envío */ }
        return _submit.apply(this, arguments);
    };
})();
