<?php
/**
 * Operaciones offline — pendientes, rechazadas y sincronizadas.
 *
 * El servidor solo entrega el cascarón: la lista se renderiza en el cliente
 * desde IndexedDB (window.OfflineData), por lo que esta pantalla funciona
 * igual con o sin internet. Cacheada por el service worker (network-first).
 */
?>
<main class="flex-1 overflow-y-auto p-4 md:p-6" id="ops-offline-page">
    <div class="max-w-4xl mx-auto">

        <!-- Encabezado -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                    <i class="fas fa-cloud-arrow-up mr-2" style="color:#4A6340;"></i>Operaciones offline
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Lo que se capturó sin internet en este equipo: qué está esperando, qué ya se aplicó y qué fue rechazado.
                </p>
            </div>
            <button type="button" id="ops-sync-btn"
                    class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm"
                    style="background:#4A6340;">
                <i class="fas fa-rotate mr-1.5"></i>Sincronizar ahora
            </button>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-red-200 p-3 text-center">
                <div class="text-2xl font-bold text-red-600" id="ops-count-error">0</div>
                <div class="text-xs text-gray-500">Rechazadas</div>
            </div>
            <div class="bg-white rounded-xl border border-amber-200 p-3 text-center">
                <div class="text-2xl font-bold text-amber-600" id="ops-count-pendiente">0</div>
                <div class="text-xs text-gray-500">Pendientes</div>
            </div>
            <div class="bg-white rounded-xl border border-green-200 p-3 text-center">
                <div class="text-2xl font-bold text-green-700" id="ops-count-ok">0</div>
                <div class="text-xs text-gray-500">Sincronizadas (7 días)</div>
            </div>
        </div>

        <!-- Rechazadas -->
        <section id="ops-seccion-error" class="mb-6 hidden">
            <h2 class="text-sm font-bold text-red-700 uppercase tracking-wide mb-2">
                <i class="fas fa-triangle-exclamation mr-1"></i>Rechazadas — requieren tu atención
            </h2>
            <div id="ops-lista-error" class="space-y-2"></div>
        </section>

        <!-- Pendientes -->
        <section id="ops-seccion-pendiente" class="mb-6 hidden">
            <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wide mb-2">
                <i class="fas fa-hourglass-half mr-1"></i>Esperando internet para sincronizar
            </h2>
            <div id="ops-lista-pendiente" class="space-y-2"></div>
        </section>

        <!-- Sincronizadas -->
        <section id="ops-seccion-ok" class="mb-6 hidden">
            <h2 class="text-sm font-bold text-green-700 uppercase tracking-wide mb-2">
                <i class="fas fa-circle-check mr-1"></i>Sincronizadas recientemente
            </h2>
            <div id="ops-lista-ok" class="space-y-2"></div>
        </section>

        <!-- Vacío -->
        <div id="ops-vacio" class="hidden bg-white rounded-xl border border-[#DDE8D5] p-10 text-center">
            <div style="width:64px;height:64px;border-radius:50%;background:rgba(74,99,64,.08);
                        display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-check-double text-2xl" style="color:#4A6340;"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-700 mb-1">Todo en orden</h3>
            <p class="text-gray-400 text-sm">No hay operaciones offline pendientes ni rechazadas en este equipo.</p>
        </div>

    </div>
</main>

<script>
(function () {
    'use strict';

    const TIPOS = {
        crear_huesped:             { icono: 'fa-user-plus',      nombre: 'Registrar huésped',      dinero: false },
        crear_reservacion:         { icono: 'fa-calendar-plus',  nombre: 'Nueva reservación',      dinero: false },
        checkin:                   { icono: 'fa-user-check',     nombre: 'Check-in',               dinero: false },
        checkout:                  { icono: 'fa-person-walking-arrow-right', nombre: 'Check-out',  dinero: false },
        cambiar_estado_habitacion: { icono: 'fa-broom',          nombre: 'Estado de habitación',   dinero: false },
        pago_caja:                 { icono: 'fa-cash-register',  nombre: 'Cobro en caja',          dinero: true },
        gasto_caja:                { icono: 'fa-money-bill-wave', nombre: 'Gasto en caja',         dinero: true },
    };

    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fechaLegible(ts) {
        if (!ts) return '—';
        try {
            return new Date(ts).toLocaleString('es-MX', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        } catch (_) { return '—'; }
    }

    function montoOp(op) {
        const m = Number(op.payload?.monto);
        return isFinite(m) && m > 0 ? '$' + m.toLocaleString('es-MX', { minimumFractionDigits: 2 }) : null;
    }

    function tarjetaOp(op, esError) {
        const t = TIPOS[op.tipo] || { icono: 'fa-circle-question', nombre: op.tipo, dinero: false };
        const monto = montoOp(op);
        const borde = esError ? '#FCA5A5' : (op.estado === 'sincronizado' ? '#BBF7D0' : '#FDE68A');

        const dineroBadge = t.dinero
            ? '<span style="background:#FEF3C7;color:#92400E;border-radius:8px;padding:1px 8px;font-size:.7rem;font-weight:700;">DINERO</span>'
            : '';

        const errorHtml = esError
            ? `<div class="mt-2 text-sm rounded-lg px-3 py-2" style="background:#FEF2F2;color:#991B1B;">
                 <i class="fas fa-circle-exclamation mr-1"></i>${esc(op.error || 'Error desconocido')}
               </div>`
            : '';

        const acciones = esError
            ? `<div class="mt-2 flex gap-2">
                 <button type="button" data-ops-accion="reintentar" data-uuid="${esc(op.uuid)}"
                         class="px-3 py-1.5 rounded-lg text-white text-xs font-semibold" style="background:#2563EB;">
                   <i class="fas fa-rotate-right mr-1"></i>Reintentar
                 </button>
                 <button type="button" data-ops-accion="descartar" data-uuid="${esc(op.uuid)}"
                         class="px-3 py-1.5 rounded-lg text-xs font-semibold" style="background:#F3F4F6;color:#B91C1C;">
                   <i class="fas fa-trash-can mr-1"></i>Descartar
                 </button>
               </div>`
            : (op.estado === 'pendiente'
                ? `<div class="mt-2">
                     <button type="button" data-ops-accion="descartar" data-uuid="${esc(op.uuid)}"
                             class="px-3 py-1.5 rounded-lg text-xs font-semibold" style="background:#F3F4F6;color:#B91C1C;">
                       <i class="fas fa-trash-can mr-1"></i>Descartar
                     </button>
                   </div>`
                : '');

        const fecha = op.estado === 'sincronizado'
            ? `Sincronizada ${fechaLegible(op.sincronizado_at)}`
            : `Capturada ${fechaLegible(op.timestamp)}`;

        return `
            <div class="bg-white rounded-xl p-3 shadow-sm" style="border:1px solid ${borde};">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <i class="fas ${t.icono} mt-0.5" style="color:#4A6340;"></i>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-gray-800 text-sm">${esc(t.nombre)}</span>
                                ${dineroBadge}
                            </div>
                            <div class="text-sm text-gray-600 truncate">${esc(op.label || '')}</div>
                            <div class="text-xs text-gray-400 mt-0.5">${fecha}${op.intentos ? ` · ${op.intentos} intento(s)` : ''}</div>
                        </div>
                    </div>
                    ${monto ? `<div class="font-bold whitespace-nowrap" style="color:${op.tipo === 'gasto_caja' ? '#DC2626' : '#059669'};">${monto}</div>` : ''}
                </div>
                ${errorHtml}
                ${acciones}
            </div>`;
    }

    async function render() {
        if (!window.OfflineData) return;

        let todas = [];
        try { todas = await window.OfflineData.obtenerTodasOperaciones(); } catch (_) {}

        const errores      = todas.filter(o => o.estado === 'error').sort((a, b) => b.timestamp - a.timestamp);
        const pendientes   = todas.filter(o => o.estado === 'pendiente').sort((a, b) => b.timestamp - a.timestamp);
        const sincronizadas = todas.filter(o => o.estado === 'sincronizado').sort((a, b) => b.timestamp - a.timestamp).slice(0, 20);

        document.getElementById('ops-count-error').textContent = errores.length;
        document.getElementById('ops-count-pendiente').textContent = pendientes.length;
        document.getElementById('ops-count-ok').textContent = sincronizadas.length;

        pintar('error', errores, true);
        pintar('pendiente', pendientes, false);
        pintar('ok', sincronizadas, false);

        document.getElementById('ops-vacio').classList.toggle(
            'hidden',
            errores.length + pendientes.length + sincronizadas.length > 0
        );
    }

    function pintar(clave, ops, esError) {
        const seccion = document.getElementById(`ops-seccion-${clave}`);
        const lista = document.getElementById(`ops-lista-${clave}`);
        seccion.classList.toggle('hidden', ops.length === 0);
        lista.innerHTML = ops.map(op => tarjetaOp(op, esError)).join('');
    }

    async function onAccion(event) {
        const btn = event.target.closest('[data-ops-accion]');
        if (!btn || !window.OfflineData) return;

        const uuid = btn.dataset.uuid;
        const accion = btn.dataset.accion || btn.dataset.opsAccion;

        if (accion === 'reintentar') {
            await window.OfflineData.reintentarOperacion(uuid);
            window.PWA?.showToast('Operación reencolada. Se enviará en la próxima sincronización.', 'info');
            await render();
            if (navigator.onLine) window.OfflineData.sincronizar();
            return;
        }

        if (accion === 'descartar') {
            const todas = await window.OfflineData.obtenerTodasOperaciones();
            const op = todas.find(o => o.uuid === uuid);
            const esDinero = op && (op.tipo === 'pago_caja' || op.tipo === 'gasto_caja');

            const confirmado = window.Swal
                ? (await Swal.fire({
                    icon: 'warning',
                    title: '¿Descartar esta operación?',
                    text: esDinero
                        ? 'Es un movimiento de DINERO. Al descartarlo NO se registrará en caja: asegúrate de registrarlo manualmente o el corte no va a cuadrar.'
                        : 'La operación se eliminará de este equipo y no se aplicará en el servidor.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, descartar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#DC2626',
                    reverseButtons: true,
                })).isConfirmed
                : window.confirm('¿Descartar esta operación? No se aplicará en el servidor.');

            if (!confirmado) return;

            await window.OfflineData.descartarOperacion(uuid);
            window.PWA?.showToast('Operación descartada.', 'info');
            await render();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        render();

        document.getElementById('ops-offline-page').addEventListener('click', onAccion);

        document.getElementById('ops-sync-btn').addEventListener('click', async function () {
            if (!window.OfflineData) return;
            if (!navigator.onLine || window.PWA?.isOnline?.() === false) {
                window.PWA?.showToast('Sin conexión: se sincronizará automáticamente al volver internet.', 'warning');
                return;
            }
            this.disabled = true;
            await window.OfflineData.sincronizar();
            this.disabled = false;
            render();
        });

        // Re-render al terminar cualquier sincronización o al cambiar la red
        window.addEventListener('loscedros:sync-done', render);
        window.addEventListener('online', () => setTimeout(render, 3000));
        window.addEventListener('offline', render);
    });
})();
</script>
