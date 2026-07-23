/**
 * ms-combo — convierte cualquier <select> en un buscador que filtra al escribir.
 *
 * Uso:  <select name="x" data-ms-combo="Buscar estado...">...</select>
 *   - data-ms-combo="texto"   -> placeholder del buscador
 *   - data-ms-combo-free      -> permite guardar un valor que no está en la lista
 *   - <option data-iso="US" data-hint="Estadounidense" data-search="usa eeuu">Estados Unidos</option>
 *       data-iso    -> bandera (emoji; si el sistema no las trae, chip "US")
 *       data-hint   -> texto secundario, también entra a la búsqueda
 *       data-search -> alias extra de búsqueda
 *
 * El <select> nativo se queda en el DOM (transparente, encima del trigger) para no
 * romper required/validación/borradores; al elegir se disparan input + change.
 */
(function () {
    'use strict';

    var ID = 0;
    var abierto = null;            // instancia con el panel abierto (solo una a la vez)
    var soportaEmojiFlags = null;

    var FLAG_MX  = '\uD83C\uDDF2\uD83C\uDDFD'; // bandera de México (par de indicadores)
    var FLAG_UNO = '\uD83C\uDDF2';             // indicador regional suelto

    function esMovil() {
        return window.matchMedia('(max-width: 640px)').matches;
    }

    function normalizar(txt) {
        var s = String(txt == null ? '' : txt).toLowerCase();
        if (s.normalize) {
            s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');  // quita acentos: "queretaro" encuentra "Querétaro"
        }
        return s.replace(/\s+/g, ' ').trim();
    }

    function esc(txt) {
        return String(txt == null ? '' : txt)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Windows no trae banderas en su fuente de emoji (sí Mac/iOS/Android): si el par
    // de indicadores no se funde en un solo glifo, caemos al chip con el código ISO.
    function banderasDisponibles() {
        if (soportaEmojiFlags !== null) return soportaEmojiFlags;
        soportaEmojiFlags = false;
        try {
            var ctx = document.createElement('canvas').getContext('2d');
            if (ctx) {
                ctx.font = '16px sans-serif';
                var par = ctx.measureText(FLAG_MX).width;
                var uno = ctx.measureText(FLAG_UNO).width;
                soportaEmojiFlags = par > 0 && uno > 0 && par < uno * 1.7;
            }
        } catch (e) { /* sin canvas: chip ISO */ }
        return soportaEmojiFlags;
    }

    function emojiBandera(iso) {
        var codigos = iso.toUpperCase().split('').map(function (l) {
            return 0x1F1E6 + (l.charCodeAt(0) - 65);
        });
        return String.fromCodePoint.apply(String, codigos);
    }

    function marcaPais(iso) {
        if (!iso || !/^[A-Za-z]{2}$/.test(iso)) return '';
        if (banderasDisponibles()) {
            return '<span class="ms-combo__flag" aria-hidden="true">' + emojiBandera(iso) + '</span>';
        }
        return '<span class="ms-combo__iso" aria-hidden="true">' + esc(iso.toUpperCase()) + '</span>';
    }

    function Combo(select) {
        this.select = select;
        this.id = 'mscombo-' + (++ID);
        this.libre = select.hasAttribute('data-ms-combo-free');
        this.textoBuscador = select.getAttribute('data-ms-combo') || 'Escribe para buscar...';
        this.textoVacio = select.getAttribute('data-ms-combo-empty') || 'Selecciona una opción';
        this.montar();
    }

    Combo.prototype.montar = function () {
        var self = this;
        var select = this.select;

        var wrap = document.createElement('div');
        wrap.className = 'ms-combo';

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = this.id + '-trigger';
        trigger.className = 'ms-combo__trigger ' + (select.className || '');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.disabled = select.disabled;

        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(trigger);
        wrap.appendChild(select);
        select.classList.add('ms-combo__native');
        select.setAttribute('tabindex', '-1');
        select.setAttribute('aria-hidden', 'true');

        this.wrap = wrap;
        this.trigger = trigger;

        // El <label for="..."> apunta al select oculto: lo redirigimos al trigger.
        if (select.id) {
            var lbl = document.querySelector('label[for="' + select.id + '"]');
            if (lbl) {
                if (!lbl.id) lbl.id = this.id + '-label';
                trigger.setAttribute('aria-labelledby', lbl.id + ' ' + trigger.id);
                lbl.addEventListener('click', function (e) { e.preventDefault(); self.abrir(); });
            }
        }
        // Sin <label for> (lo normal en estos formularios): el boton toma su nombre
        // de la etiqueta del campo, si no quedaria como un boton sin nombre.
        if (!trigger.hasAttribute('aria-labelledby')) {
            var campo = select.closest('.gc-field, .ge-field, .lote-field, .form-group, .field');
            var etiqueta = campo ? campo.querySelector('label') : null;
            var texto = etiqueta ? etiqueta.textContent.replace(/\*/g, '').replace(/\s+/g, ' ').trim() : '';
            trigger.setAttribute('aria-label', texto || select.getAttribute('name') || 'Selector');
        }

        this.pintarTrigger();

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            if (abierto === self) self.cerrar(); else self.abrir();
        });
        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                self.abrir();
            } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                self.abrir(e.key);   // empezar a escribir abre el buscador con esa letra
            }
        });

        // Cambios que no vienen del panel (borrador de app.js, JS de la vista).
        select.addEventListener('change', function () {
            self.wrap.classList.remove('is-invalid');
            self.pintarTrigger();
        });
        select.addEventListener('ms-combo:refresh', function () { self.pintarTrigger(); });
        // La validación nativa no puede enfocar un control invisible: vamos al trigger.
        select.addEventListener('invalid', function () {
            self.wrap.classList.add('is-invalid');
            setTimeout(function () { self.trigger.focus(); }, 0);
        });
    };

    Combo.prototype.tienePlaceholder = function () {
        return !!(this.select.options.length && this.select.options[0].value === '');
    };

    Combo.prototype.pintarTrigger = function () {
        var self = this;
        var opt = this.select.options[this.select.selectedIndex] || null;
        var vacio = !opt || opt.value === '';
        var iso = opt ? (opt.getAttribute('data-iso') || '') : '';
        var texto = vacio ? this.textoVacio : (opt.getAttribute('data-label') || opt.textContent.trim());
        var puedeLimpiar = !vacio && this.tienePlaceholder() && !this.select.required;

        this.trigger.innerHTML =
            '<span class="ms-combo__valor' + (vacio ? ' is-placeholder' : '') + '">' +
                (vacio ? '' : marcaPais(iso)) +
                '<span class="ms-combo__texto">' + esc(texto) + '</span>' +
            '</span>' +
            (puedeLimpiar ? '<span class="ms-combo__limpiar" title="Quitar selección"><i class="fas fa-xmark"></i></span>' : '') +
            '<span class="ms-combo__caret" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>';

        var limpiar = this.trigger.querySelector('.ms-combo__limpiar');
        if (limpiar) {
            limpiar.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.elegir('');
            });
        }
    };

    Combo.prototype.filas = function () {
        var filas = [];
        var hijos = this.select.children;
        var self = this;
        var agregar = function (option, grupo) {
            var etiqueta = option.getAttribute('data-label') || option.textContent.trim();
            var hint = option.getAttribute('data-hint') || '';
            var iso = option.getAttribute('data-iso') || '';
            filas.push({
                valor: option.value,
                etiqueta: etiqueta,
                hint: hint,
                iso: iso,
                grupo: grupo,
                busqueda: normalizar([etiqueta, hint, iso, option.getAttribute('data-search') || ''].join(' '))
            });
        };
        for (var i = 0; i < hijos.length; i++) {
            var nodo = hijos[i];
            if (nodo.tagName === 'OPTGROUP') {
                for (var j = 0; j < nodo.children.length; j++) agregar(nodo.children[j], nodo.label || '');
            } else if (nodo.tagName === 'OPTION') {
                agregar(nodo, '');
            }
        }
        return filas;
    };

    Combo.prototype.abrir = function (semilla) {
        if (abierto && abierto !== this) abierto.cerrar();
        if (abierto === this) return;
        if (this.select.disabled) return;
        abierto = this;

        var self = this;
        var sheet = esMovil();

        var panel = document.createElement('div');
        panel.className = 'ms-combo-panel' + (sheet ? ' is-sheet' : '');
        panel.innerHTML =
            (sheet ? '<div class="ms-combo-panel__handle" aria-hidden="true"></div>' : '') +
            '<div class="ms-combo-panel__search">' +
                '<i class="fas fa-magnifying-glass" aria-hidden="true"></i>' +
                '<input type="text" class="ms-combo-panel__input" autocomplete="off" autocapitalize="off" ' +
                    'autocorrect="off" spellcheck="false" placeholder="' + esc(this.textoBuscador) + '" ' +
                    'aria-label="' + esc(this.textoBuscador) + '">' +
                (sheet ? '<button type="button" class="ms-combo-panel__cerrar">Listo</button>' : '') +
            '</div>' +
            '<div class="ms-combo-panel__list" role="listbox" tabindex="-1"></div>';
        document.body.appendChild(panel);

        if (sheet) {
            var backdrop = document.createElement('div');
            backdrop.className = 'ms-combo-backdrop';
            document.body.appendChild(backdrop);
            backdrop.addEventListener('click', function () { self.cerrar(); });
            this.backdrop = backdrop;
        }

        this.panel = panel;
        this.lista = panel.querySelector('.ms-combo-panel__list');
        this.input = panel.querySelector('.ms-combo-panel__input');
        this.wrap.classList.add('is-open');
        this.trigger.setAttribute('aria-expanded', 'true');

        if (semilla) this.input.value = semilla;
        this.filtrar(this.input.value);
        this.posicionar();

        this.input.addEventListener('input', function () { self.filtrar(self.input.value); });
        this.input.addEventListener('keydown', function (e) { self.teclado(e); });
        panel.addEventListener('mousedown', function (e) {
            // clic en la lista no debe robarle el foco al buscador (el input sí lo necesita)
            if (!e.target.closest('.ms-combo-panel__input')) e.preventDefault();
        });
        panel.addEventListener('click', function (e) {
            var fila = e.target.closest('[data-valor]');
            if (fila) {
                self.elegir(fila.getAttribute('data-valor'), fila.getAttribute('data-nuevo') === '1');
            } else if (e.target.closest('.ms-combo-panel__cerrar')) {
                self.cerrar();
            }
        });

        this._fuera = function (e) {
            if (!panel.contains(e.target) && !self.wrap.contains(e.target)) self.cerrar();
        };
        document.addEventListener('mousedown', this._fuera, true);
        document.addEventListener('touchstart', this._fuera, true);
        if (!sheet) {
            this._reubicar = function () { self.posicionar(); };
            window.addEventListener('scroll', this._reubicar, true);
            window.addEventListener('resize', this._reubicar);
        }

        setTimeout(function () { if (self.input) self.input.focus(); }, sheet ? 60 : 0);
    };

    Combo.prototype.posicionar = function () {
        if (!this.panel || this.panel.classList.contains('is-sheet')) return;
        var r = this.trigger.getBoundingClientRect();
        var ancho = Math.max(r.width, 260);
        var espacioAbajo = window.innerHeight - r.bottom;
        var arriba = espacioAbajo < 260 && r.top > espacioAbajo;

        this.panel.style.width = ancho + 'px';
        this.panel.style.left = Math.max(8, Math.min(r.left, window.innerWidth - ancho - 8)) + 'px';
        if (arriba) {
            this.panel.style.top = 'auto';
            this.panel.style.bottom = (window.innerHeight - r.top + 6) + 'px';
            this.panel.style.maxHeight = Math.max(180, Math.min(340, r.top - 16)) + 'px';
        } else {
            this.panel.style.bottom = 'auto';
            this.panel.style.top = (r.bottom + 6) + 'px';
            this.panel.style.maxHeight = Math.max(180, Math.min(340, espacioAbajo - 16)) + 'px';
        }
    };

    Combo.prototype.filtrar = function (texto) {
        var q = normalizar(texto);
        var actual = this.select.value;
        var encontradas = [];
        var vistos = {};   // al buscar se aplanan los grupos: un valor repetido (ej. "frecuentes") va una vez

        var hayPrefijo = false;
        this.filas().forEach(function (f) {
            if (f.valor === '') { if (q === '') encontradas.push({ f: f, peso: -1 }); return; }
            if (q === '') { encontradas.push({ f: f, peso: 0 }); return; }
            var pos = f.busqueda.indexOf(q);
            if (pos === -1) return;
            if (vistos[f.valor]) return;
            vistos[f.valor] = true;
            // 0 = el nombre empieza con lo escrito · 1 = alguna palabra empieza · 2 = va a media palabra
            var peso = 2;
            if (normalizar(f.etiqueta).indexOf(q) === 0) { peso = 0; hayPrefijo = true; }
            else if (pos === 0 || f.busqueda.charAt(pos - 1) === ' ') { peso = 1; }
            encontradas.push({ f: f, peso: peso });
        });
        encontradas.sort(function (a, b) { return a.peso - b.peso; });

        var html = '';
        var grupo = null;
        encontradas.forEach(function (r) {
            var f = r.f;
            if (q === '' && f.grupo !== grupo) {
                grupo = f.grupo;
                if (grupo) html += '<div class="ms-combo-panel__grupo">' + esc(grupo) + '</div>';
            }
            var sel = f.valor !== '' && f.valor === actual;
            html += '<div class="ms-combo-panel__opt' + (sel ? ' is-selected' : '') + (f.valor === '' ? ' is-vacia' : '') + '"' +
                    ' role="option" aria-selected="' + (sel ? 'true' : 'false') + '" data-valor="' + esc(f.valor) + '">' +
                    marcaPais(f.iso) +
                    '<span class="ms-combo-panel__txt">' +
                        '<span class="ms-combo-panel__nombre">' + esc(f.etiqueta) + '</span>' +
                        (f.hint ? '<span class="ms-combo-panel__hint">' + esc(f.hint) + '</span>' : '') +
                    '</span>' +
                    (sel ? '<i class="fas fa-check ms-combo-panel__check" aria-hidden="true"></i>' : '') +
                '</div>';
        });

        // "Usar «lo que escribi»" solo cuando la lista no trae ya lo que empezo a teclear.
        var escrito = texto.trim();
        if (this.libre && escrito !== '' && !hayPrefijo) {
            html += '<div class="ms-combo-panel__opt ms-combo-panel__opt--nueva" role="option" aria-selected="false"' +
                    ' data-valor="' + esc(escrito) + '" data-nuevo="1">' +
                    '<span class="ms-combo__iso" aria-hidden="true"><i class="fas fa-pen"></i></span>' +
                    '<span class="ms-combo-panel__txt">' +
                        '<span class="ms-combo-panel__nombre">Usar &laquo;' + esc(escrito) + '&raquo;</span>' +
                        '<span class="ms-combo-panel__hint">No est&aacute; en la lista</span>' +
                    '</span></div>';
        }

        if (!html) {
            html = '<div class="ms-combo-panel__vacio">Sin resultados para &laquo;' + esc(escrito) + '&raquo;</div>';
        }

        this.lista.innerHTML = html;
        this.activar(this.lista.querySelector('.is-selected') || this.lista.querySelector('[data-valor]'));
    };

    Combo.prototype.activar = function (el) {
        var previa = this.lista.querySelector('.is-active');
        if (previa) previa.classList.remove('is-active');
        if (!el) return;
        el.classList.add('is-active');
        var r = el.getBoundingClientRect();
        var rl = this.lista.getBoundingClientRect();
        if (r.top < rl.top) this.lista.scrollTop -= (rl.top - r.top) + 4;
        else if (r.bottom > rl.bottom) this.lista.scrollTop += (r.bottom - rl.bottom) + 4;
    };

    Combo.prototype.teclado = function (e) {
        var opciones = Array.prototype.slice.call(this.lista.querySelectorAll('[data-valor]'));
        var activa = this.lista.querySelector('.is-active');
        var idx = opciones.indexOf(activa);

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!opciones.length) return;
            idx = e.key === 'ArrowDown' ? Math.min(opciones.length - 1, idx + 1) : Math.max(0, idx - 1);
            this.activar(opciones[idx]);
        } else if (e.key === 'Enter') {
            e.preventDefault();   // nunca enviar el formulario desde el buscador
            if (activa) this.elegir(activa.getAttribute('data-valor'), activa.getAttribute('data-nuevo') === '1');
        } else if (e.key === 'Escape') {
            e.preventDefault();
            this.cerrar(true);
        } else if (e.key === 'Tab') {
            this.cerrar();
        }
    };

    Combo.prototype.elegir = function (valor, esNuevo) {
        var select = this.select;
        if (esNuevo) {
            var previa = select.querySelector('option[data-libre="1"]');
            if (previa) previa.remove();
            var opt = document.createElement('option');
            opt.value = valor;
            opt.textContent = valor;
            opt.setAttribute('data-libre', '1');
            select.appendChild(opt);
        }
        select.value = valor;
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        this.pintarTrigger();
        this.cerrar(true);
    };

    Combo.prototype.cerrar = function (devolverFoco) {
        if (abierto === this) abierto = null;
        if (this._fuera) {
            document.removeEventListener('mousedown', this._fuera, true);
            document.removeEventListener('touchstart', this._fuera, true);
            this._fuera = null;
        }
        if (this._reubicar) {
            window.removeEventListener('scroll', this._reubicar, true);
            window.removeEventListener('resize', this._reubicar);
            this._reubicar = null;
        }
        if (this.panel) { this.panel.remove(); this.panel = null; }
        if (this.backdrop) { this.backdrop.remove(); this.backdrop = null; }
        this.lista = null;
        this.input = null;
        this.wrap.classList.remove('is-open');
        this.trigger.setAttribute('aria-expanded', 'false');
        if (devolverFoco) this.trigger.focus();
    };

    function init(raiz) {
        var selects = (raiz || document).querySelectorAll('select[data-ms-combo]:not(.ms-combo__native)');
        for (var i = 0; i < selects.length; i++) {
            try {
                selects[i]._msCombo = new Combo(selects[i]);
            } catch (e) {
                if (window.console) console.warn('ms-combo: no se pudo montar', e);
            }
        }
    }

    function cerrarTodo() { if (abierto) abierto.cerrar(); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }

    // bfcache: la foto del historial se toma DESPUES de pagehide -> se guarda sin panel abierto.
    window.addEventListener('pagehide', cerrarTodo);
    window.addEventListener('pageshow', function (e) { if (e.persisted) cerrarTodo(); });

    window.MsCombo = { init: init, cerrarTodo: cerrarTodo };
})();
