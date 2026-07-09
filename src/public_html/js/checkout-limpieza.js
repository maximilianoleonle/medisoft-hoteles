/**
 * Selector de responsable de limpieza al hacer check-out.
 *
 * Se muestra despues de confirmar un check-out (desde cualquier seccion del
 * sistema) para elegir que camarista(s) se encargaran de la limpieza de las
 * habitaciones liberadas. La asignacion es opcional: siempre se puede
 * continuar sin asignar, y si el modulo de tareas/personal no esta disponible
 * el selector se omite en silencio (fail-open, nunca bloquea el check-out).
 *
 * Uso desde una vista:
 *
 *   const asignaciones = await CheckoutLimpieza.seleccionar({
 *       infoUrl: '<?= url("api/reservaciones/{id}/limpieza-personal") ?>',
 *       habitacionesIds: [12, 14]   // opcional: limitar a ciertos cuartos
 *   });
 *   if (asignaciones === null) return;                        // cancelado
 *   CheckoutLimpieza.aplicarAFormData(formData, asignaciones); // o aplicarAForm(form, ...)
 */
(function() {
    'use strict';

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    async function obtenerInfo(infoUrl) {
        try {
            const resp = await fetch(infoUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!resp.ok) return null;
            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) return null;
            return await resp.json();
        } catch (e) {
            return null;
        }
    }

    function construirHtml(personal, habitaciones) {
        const opciones = ['<option value="">Sin asignar</option>'].concat(
            personal.map(function(p) {
                const rol = p.rol ? ' (' + escapeHtml(p.rol) + ')' : '';
                return '<option value="' + p.id + '">' + escapeHtml(p.nombre) + rol + '</option>';
            })
        ).join('');

        const selectStyle = 'width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:0.9rem;background:#fff;color:#111827;';

        if (habitaciones.length <= 1) {
            const hab = habitaciones[0];
            const habId = hab ? hab.habitacion_id : 'todas';
            const etiqueta = hab ? ('Habitación ' + escapeHtml(hab.numero)) : 'Todas las habitaciones';
            return '' +
                '<div class="cl-selector" style="text-align:left;max-width:420px;margin:0 auto;">' +
                    '<p style="margin:0 0 12px;color:#4B5563;font-size:0.92rem;">¿Quién se encargará de la limpieza? Puedes continuar sin asignar y asignarlo después desde Tareas.</p>' +
                    '<label style="display:block;font-weight:600;font-size:0.85rem;color:#374151;margin-bottom:4px;">' + etiqueta + '</label>' +
                    '<select data-cl-habitacion="' + habId + '" style="' + selectStyle + '">' + opciones + '</select>' +
                '</div>';
        }

        const filas = habitaciones.map(function(hab) {
            return '' +
                '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">' +
                    '<span style="flex:0 0 110px;font-weight:600;font-size:0.85rem;color:#374151;">Hab. ' + escapeHtml(hab.numero) + '</span>' +
                    '<select data-cl-habitacion="' + hab.habitacion_id + '" style="flex:1;' + selectStyle + '">' + opciones + '</select>' +
                '</div>';
        }).join('');

        return '' +
            '<div class="cl-selector" style="text-align:left;max-width:460px;margin:0 auto;">' +
                '<p style="margin:0 0 10px;color:#4B5563;font-size:0.92rem;">¿Quién se encargará de la limpieza de cada habitación? Puedes continuar sin asignar.</p>' +
                '<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px dashed #E5E7EB;">' +
                    '<span style="flex:0 0 110px;font-weight:600;font-size:0.85rem;color:#374151;">Todas</span>' +
                    '<select data-cl-todas="1" style="flex:1;' + selectStyle + '">' + opciones + '</select>' +
                '</div>' +
                '<div style="max-height:240px;overflow-y:auto;">' + filas + '</div>' +
            '</div>';
    }

    /**
     * Muestra el selector. Devuelve:
     *  - objeto {habitacion_id: trabajador_id, ...} (puede ser {} si no asigno nada
     *    o si el selector no aplica) -> continuar con el check-out
     *  - null -> el usuario cancelo el check-out
     */
    async function seleccionar(opts) {
        opts = opts || {};
        const infoUrl = opts.infoUrl;
        if (!infoUrl || typeof Swal === 'undefined') {
            return {};
        }

        const info = await obtenerInfo(infoUrl);
        if (!info || !info.success || !info.disponible || !Array.isArray(info.personal) || info.personal.length === 0) {
            return {}; // sin personal o modulo no disponible: continuar sin friccion
        }

        let habitaciones = Array.isArray(info.habitaciones) ? info.habitaciones : [];
        if (Array.isArray(opts.habitacionesIds) && opts.habitacionesIds.length > 0) {
            const ids = opts.habitacionesIds.map(Number);
            const filtradas = habitaciones.filter(function(h) { return ids.indexOf(Number(h.habitacion_id)) !== -1; });
            if (filtradas.length > 0) habitaciones = filtradas;
        }

        const result = await Swal.fire({
            title: 'Asignar limpieza',
            html: construirHtml(info.personal, habitaciones),
            icon: 'question',
            iconHtml: '<i class="fas fa-broom" style="color:#0EA5E9;font-size:0.8em;"></i>',
            iconColor: '#0EA5E9',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check"></i> Continuar con el check-out',
            cancelButtonText: 'Cancelar check-out',
            confirmButtonColor: '#0EA5E9',
            cancelButtonColor: '#6B7280',
            reverseButtons: true,
            allowOutsideClick: false,
            customClass: { popup: 'cl-swal-limpieza' },
            preConfirm: function() {
                const popup = Swal.getPopup();
                const asignaciones = {};
                let global = 0;
                const selTodas = popup.querySelector('[data-cl-todas]');
                if (selTodas && selTodas.value) global = parseInt(selTodas.value, 10) || 0;
                popup.querySelectorAll('[data-cl-habitacion]').forEach(function(sel) {
                    const habId = sel.getAttribute('data-cl-habitacion');
                    const val = parseInt(sel.value, 10) || 0;
                    if (val > 0) asignaciones[habId] = val;
                });
                return { asignaciones: asignaciones, global: global };
            },
            didOpen: function() {
                const popup = Swal.getPopup();
                const selTodas = popup.querySelector('[data-cl-todas]');
                if (selTodas) {
                    selTodas.addEventListener('change', function() {
                        popup.querySelectorAll('[data-cl-habitacion]').forEach(function(sel) {
                            sel.value = selTodas.value;
                        });
                    });
                }
            }
        });

        if (!result.isConfirmed) {
            return null; // cancelar todo el check-out
        }

        const valor = result.value || { asignaciones: {}, global: 0 };
        const asignaciones = valor.asignaciones || {};
        // Caso "una sola habitacion sin id conocido": usar asignacion global.
        if (asignaciones['todas']) {
            valor.global = asignaciones['todas'];
            delete asignaciones['todas'];
        }
        if (valor.global > 0) {
            asignaciones.__todas = valor.global;
        }
        return asignaciones;
    }

    function aplicarAFormData(formData, asignaciones) {
        if (!asignaciones) return;
        Object.keys(asignaciones).forEach(function(habId) {
            if (habId === '__todas') {
                formData.append('limpieza_responsable_todas', asignaciones[habId]);
            } else {
                formData.append('limpieza_responsable[' + habId + ']', asignaciones[habId]);
            }
        });
    }

    function aplicarAForm(form, asignaciones) {
        if (!asignaciones) return;
        Object.keys(asignaciones).forEach(function(habId) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = habId === '__todas'
                ? 'limpieza_responsable_todas'
                : 'limpieza_responsable[' + habId + ']';
            input.value = asignaciones[habId];
            form.appendChild(input);
        });
    }

    window.CheckoutLimpieza = {
        seleccionar: seleccionar,
        aplicarAFormData: aplicarAFormData,
        aplicarAForm: aplicarAForm
    };
})();

/**
 * Selector de personal al MARCAR una habitacion como limpia (quien la limpio).
 *
 * A diferencia de CheckoutLimpieza (opcional, al hacer check-out), este selector
 * es OBLIGATORIO: no deja continuar sin elegir una o mas personas o marcar
 * explicitamente "Sin registrar personal". Si el modulo de tareas/personal no
 * esta disponible se omite en silencio (fail-open) y devuelve omitido:true.
 *
 * Uso:
 *   const sel = await LimpiezaPersonal.elegir({
 *       personal: [{id, nombre, rol}, ...],   // o infoUrl para pedirlos al servidor
 *       preseleccion: [3, 7],                 // opcional: asignados a la tarea activa
 *       habitacionId: 12                      // opcional, con infoUrl: preselecciona asignados
 *   });
 *   if (sel === null) return;                        // usuario cancelo
 *   LimpiezaPersonal.aplicarAFormData(formData, sel); // o aplicarAForm(form, sel)
 */
(function() {
    'use strict';

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    async function obtenerInfo(infoUrl) {
        try {
            const resp = await fetch(infoUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!resp.ok) return null;
            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) return null;
            return await resp.json();
        } catch (e) {
            return null;
        }
    }

    function construirHtml(personal, preseleccion, textoIntro) {
        const pre = (preseleccion || []).map(Number);
        const filas = personal.map(function(p) {
            const checked = pre.indexOf(Number(p.id)) !== -1 ? ' checked' : '';
            const rol = p.rol ? '<span class="lp-room__badge">' + escapeHtml(p.rol) + '</span>' : '';
            const inicial = escapeHtml(String(p.nombre || '?').trim().charAt(0).toUpperCase() || '?');
            return '<label class="lp-room">' +
                '<input type="checkbox" class="lp-check-input lp-check" value="' + p.id + '"' + checked + '>' +
                '<span class="lp-check-box" aria-hidden="true"><i class="fas fa-check"></i></span>' +
                '<span class="lp-room__emblem" aria-hidden="true">' + inicial + '</span>' +
                '<span class="lp-room__name">' + escapeHtml(p.nombre) + '</span>' +
                rol +
            '</label>';
        }).join('');

        const intro = textoIntro || 'Puedes seleccionar a una o varias personas.';

        return '' +
            '<div class="lp-selector">' +
                '<p class="lp-selector__intro">' + escapeHtml(intro) + '</p>' +
                '<div class="lp-selector__list">' + filas + '</div>' +
                '<label class="lp-room lp-room--none">' +
                    '<input type="checkbox" id="lpSinPersonal" class="lp-check-input">' +
                    '<span class="lp-check-box" aria-hidden="true"><i class="fas fa-check"></i></span>' +
                    '<span class="lp-room__emblem lp-room__emblem--none" aria-hidden="true"><i class="fas fa-user-slash"></i></span>' +
                    '<span class="lp-room__name lp-room__name--none">Sin registrar personal</span>' +
                '</label>' +
            '</div>';
    }

    // Inyecta una sola vez el "look" del modal de limpieza (#modalLimpieza) para el
    // selector de personal: serif Cormorant, emblema/acento azul, filas tipo .lm-room
    // con check que se rellena, y botón primario azul. Se aplica en todas las vistas
    // que usan LimpiezaPersonal (habitaciones/index, ver, motor).
    function inyectarEstiloLP() {
        if (document.getElementById('lp-personal-style')) return;
        const st = document.createElement('style');
        st.id = 'lp-personal-style';
        st.textContent = [
            "@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&display=swap');",
            ".lp-swal-personal.swal2-popup{",
            "  --lp-clean:#2F77E0; --lp-clean-deep:#1E5FBF; --lp-clean-soft:#E6EFFC;",
            "  --lp-surface:#FFFEFB; --lp-surface-warm:#FCFAF5; --lp-line:#E7E1D4;",
            "  --lp-ink:#20293A; --lp-ink-soft:#5C6675; --lp-ink-faint:#8B94A3;",
            "  --lp-serif:'Cormorant Garamond', Georgia, 'Times New Roman', serif;",
            "  border-radius:22px !important; background:var(--lp-surface) !important;",
            "  padding:26px 24px 22px !important; box-shadow:0 34px 80px -30px rgba(16,32,64,.5) !important; }",
            ".lp-swal-personal .swal2-icon{ border:0 !important; width:74px !important; height:74px !important;",
            "  margin:4px auto 12px !important; border-radius:20px; display:grid; place-items:center;",
            "  background:linear-gradient(135deg,#2E6FD2 0%,#3D82EA 55%,#60A1FA 100%) !important;",
            "  box-shadow:0 14px 30px -12px rgba(47,119,224,.6); }",
            ".lp-swal-personal .swal2-icon .swal2-icon-content, .lp-swal-personal .swal2-icon i{ color:#fff !important; font-size:1.9rem !important; }",
            ".lp-swal-personal .swal2-title{ font-family:var(--lp-serif) !important; font-weight:700 !important;",
            "  font-size:1.7rem !important; color:var(--lp-ink) !important; padding:0 !important; margin:2px 0 6px !important; }",
            ".lp-swal-personal .lp-selector{ text-align:left; max-width:430px; margin:0 auto; }",
            ".lp-swal-personal .lp-selector__intro{ margin:0 0 12px; color:var(--lp-ink-soft); font-size:.9rem; line-height:1.45; }",
            ".lp-swal-personal .lp-selector__list{ display:flex; flex-direction:column; gap:8px; max-height:250px; overflow-y:auto; padding:2px; }",
            ".lp-swal-personal .lp-room{ position:relative; display:flex; align-items:center; gap:12px; padding:11px 13px;",
            "  border:1px solid var(--lp-line); border-radius:13px; background:var(--lp-surface); cursor:pointer;",
            "  transition:border-color .16s ease, background .16s ease, box-shadow .16s ease; }",
            ".lp-swal-personal .lp-room:hover{ border-color:color-mix(in srgb,var(--lp-clean) 40%,var(--lp-line)); background:var(--lp-surface-warm); }",
            ".lp-swal-personal .lp-room:has(.lp-check-input:checked){ border-color:var(--lp-clean); background:var(--lp-clean-soft); box-shadow:0 10px 22px -14px rgba(47,119,224,.5); }",
            ".lp-swal-personal .lp-check-input{ position:absolute; opacity:0; width:1px; height:1px; margin:0; pointer-events:none; }",
            ".lp-swal-personal .lp-check-box{ flex:0 0 auto; width:24px; height:24px; border-radius:8px;",
            "  border:2px solid color-mix(in srgb,var(--lp-ink-faint) 55%,transparent); display:grid; place-items:center;",
            "  background:#fff; transition:all .16s ease; }",
            ".lp-swal-personal .lp-check-box i{ font-size:.7rem; color:#fff; opacity:0; transform:scale(.4); transition:all .18s ease; }",
            ".lp-swal-personal .lp-check-input:checked + .lp-check-box{ background:var(--lp-clean); border-color:var(--lp-clean); }",
            ".lp-swal-personal .lp-check-input:checked + .lp-check-box i{ opacity:1; transform:scale(1); }",
            ".lp-swal-personal .lp-room__emblem{ flex:0 0 auto; width:36px; height:36px; border-radius:11px; display:grid; place-items:center;",
            "  background:var(--lp-clean-soft); color:var(--lp-clean-deep); font-weight:800; font-size:.95rem; }",
            ".lp-swal-personal .lp-room__emblem--none{ background:#F1F0EC; color:var(--lp-ink-faint); }",
            ".lp-swal-personal .lp-room__name{ font-family:var(--lp-serif); font-weight:700; font-size:1.08rem; color:var(--lp-ink); line-height:1.1; }",
            ".lp-swal-personal .lp-room__name--none{ font-family:inherit; font-weight:600; font-size:.9rem; color:var(--lp-ink-soft); }",
            ".lp-swal-personal .lp-room__badge{ margin-left:auto; font-size:.7rem; color:var(--lp-clean-deep); background:var(--lp-clean-soft); padding:3px 9px; border-radius:999px; font-weight:700; white-space:nowrap; }",
            ".lp-swal-personal .lp-room--none{ margin-top:9px; border-style:dashed; background:var(--lp-surface-warm); }",
            ".lp-swal-personal .swal2-actions{ gap:10px !important; margin-top:18px !important; }",
            ".lp-swal-personal .swal2-confirm{ background:var(--lp-clean) !important; border-radius:12px !important; font-weight:700 !important; box-shadow:0 14px 26px -12px rgba(47,119,224,.6) !important; }",
            ".lp-swal-personal .swal2-confirm:hover{ background:var(--lp-clean-deep) !important; }",
            ".lp-swal-personal .swal2-cancel{ border-radius:12px !important; font-weight:700 !important; }",
            "@media (prefers-reduced-motion: reduce){ .lp-swal-personal .lp-room, .lp-swal-personal .lp-check-box, .lp-swal-personal .lp-check-box i{ transition:none !important; } }"
        ].join('\n');
        (document.head || document.documentElement).appendChild(st);
    }

    /**
     * Muestra el selector obligatorio. Devuelve:
     *  - { trabajadorIds: [..], sinPersonal: bool }        -> continuar
     *  - { trabajadorIds: [], sinPersonal:false, omitido:true } -> sin datos, continuar sin selector
     *  - null                                              -> el usuario cancelo
     */
    async function elegir(opts) {
        opts = opts || {};

        if (typeof Swal === 'undefined') {
            return { trabajadorIds: [], sinPersonal: false, omitido: true };
        }

        let personal = Array.isArray(opts.personal) && opts.personal.length > 0 ? opts.personal : null;
        let preseleccion = opts.preseleccion || null;

        if (!personal && opts.infoUrl) {
            const info = await obtenerInfo(opts.infoUrl);
            if (info && info.success && info.disponible && Array.isArray(info.personal) && info.personal.length > 0) {
                personal = info.personal;
                if (!preseleccion && opts.habitacionId && info.asignadas && info.asignadas[opts.habitacionId]) {
                    preseleccion = info.asignadas[opts.habitacionId];
                }
            }
        }

        if (!personal || personal.length === 0) {
            return { trabajadorIds: [], sinPersonal: false, omitido: true };
        }

        inyectarEstiloLP();

        const result = await Swal.fire({
            title: opts.titulo || '¿Quién hizo la limpieza?',
            html: construirHtml(personal, preseleccion, opts.textoIntro),
            iconHtml: '<i class="fas fa-broom" style="color:#0EA5E9;font-size:0.8em;"></i>',
            iconColor: '#0EA5E9',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: opts.confirmText || '<i class="fas fa-check"></i> Confirmar limpieza',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2F77E0',
            cancelButtonColor: '#6B7280',
            reverseButtons: true,
            allowOutsideClick: false,
            customClass: { popup: 'lp-swal-personal' },
            preConfirm: function() {
                const popup = Swal.getPopup();
                const sin = popup.querySelector('#lpSinPersonal');
                const sinPersonal = !!(sin && sin.checked);
                const ids = Array.prototype.slice.call(popup.querySelectorAll('.lp-check:checked'))
                    .map(function(c) { return parseInt(c.value, 10) || 0; })
                    .filter(function(v) { return v > 0; });

                if (!sinPersonal && ids.length === 0) {
                    Swal.showValidationMessage('Selecciona quién hizo la limpieza o marca "Sin registrar personal".');
                    return false;
                }

                return { trabajadorIds: sinPersonal ? [] : ids, sinPersonal: sinPersonal };
            },
            didOpen: function() {
                const popup = Swal.getPopup();
                const sin = popup.querySelector('#lpSinPersonal');
                const checks = popup.querySelectorAll('.lp-check');
                if (sin) {
                    sin.addEventListener('change', function() {
                        if (sin.checked) {
                            checks.forEach(function(c) { c.checked = false; });
                        }
                    });
                }
                checks.forEach(function(c) {
                    c.addEventListener('change', function() {
                        if (c.checked && sin) sin.checked = false;
                    });
                });
            }
        });

        if (!result.isConfirmed) {
            return null;
        }

        return result.value || { trabajadorIds: [], sinPersonal: false, omitido: true };
    }

    function aplicarAFormData(formData, seleccion) {
        if (!seleccion || seleccion.omitido) return;
        formData.append('personal_confirmado', '1');
        if (seleccion.sinPersonal) formData.append('sin_personal', '1');
        (seleccion.trabajadorIds || []).forEach(function(id) {
            formData.append('trabajador_ids[]', id);
        });
    }

    function aplicarAForm(form, seleccion) {
        if (!seleccion || seleccion.omitido) return;
        const agregar = function(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        };
        agregar('personal_confirmado', '1');
        if (seleccion.sinPersonal) agregar('sin_personal', '1');
        (seleccion.trabajadorIds || []).forEach(function(id) {
            agregar('trabajador_ids[]', id);
        });
    }

    window.LimpiezaPersonal = {
        elegir: elegir,
        aplicarAFormData: aplicarAFormData,
        aplicarAForm: aplicarAForm
    };
})();
