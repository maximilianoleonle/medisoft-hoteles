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
