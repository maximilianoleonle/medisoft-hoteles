/**
 * habitaciones-offline.js — Los Cedros
 *
 * Intercepta las funciones globales de la vista de habitaciones
 * para que funcionen sin internet usando IndexedDB + cola de sync.
 *
 * Técnica: monkey-patching de funciones globales.
 * Las funciones originales se definen en index.php como globals;
 * este script las reemplaza DESPUÉS de que carga la página.
 *
 * Funciones interceptadas:
 *   liberarHabitacion(id)             → cambiar estado a disponible
 *   ejecutarCheckOutRapido(resId)      → checkout completo
 *   hacerCheckInRapido(resId)          → check-in (redirige online / encola offline)
 *
 * Depende de: offline-data.js (window.OfflineData)
 */
(function () {
  'use strict';

  // Esperar a que el DOM esté listo y las funciones originales definidas
  document.addEventListener('DOMContentLoaded', function () {

    // Solo aplica en la vista de habitaciones
    if (!document.querySelector('.habitaciones-view, [data-habitacion-id]')) return;

    _instalarInterceptores();
    _mostrarBannerOfflineEnCards();

    // Re-evaluar cada vez que cambia el estado de red
    window.addEventListener('online',  () => _actualizarEstadoOfflineUI(false));
    window.addEventListener('offline', () => _actualizarEstadoOfflineUI(true));
    _actualizarEstadoOfflineUI(!navigator.onLine);
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // 1. INSTALACIÓN DE INTERCEPTORES
  // ═══════════════════════════════════════════════════════════════════════════

  function _instalarInterceptores() {

    // ── liberarHabitacion(id) ─────────────────────────────────────────────
    //    Original: POST /habitaciones/{id}/liberar → marca como "disponible"
    if (typeof window.liberarHabitacion === 'function') {
      const _orig = window.liberarHabitacion.bind(window);

      window.liberarHabitacion = function (id) {
        if (navigator.onLine) return _orig(id);

        // OFFLINE: preguntar y encolar
        Swal.fire({
          icon:                'question',
          title:               '¿Marcar como disponible?',
          html:                `<p>Habitación <strong>#${id}</strong></p>
                                <p class="text-sm text-amber-600 mt-2">
                                  <i class="fas fa-wifi-slash mr-1"></i>
                                  Sin conexión — se aplicará cuando vuelva internet.
                                </p>`,
          showCancelButton:    true,
          confirmButtonColor:  '#4A6741',
          cancelButtonColor:   '#6B7280',
          confirmButtonText:   '<i class="fas fa-check mr-1"></i>Sí, marcar',
          cancelButtonText:    'Cancelar',
          reverseButtons:      true,
        }).then(async result => {
          if (!result.isConfirmed) return;

          await window.OfflineData.encolarOperacion(
            'cambiar_estado_habitacion',
            { habitacion_id: id, estado_nuevo: 'disponible' },
            `Liberar habitación #${id}`
          );

          // Actualizar UI de la card inmediatamente
          _actualizarCardEstado(id, 'disponible');
          _mostrarToastOffline('Habitación marcada como disponible. Se enviará al volver internet.');
        });
      };
    }

    // ── ejecutarCheckOutRapido(reservacionId) ─────────────────────────────
    //    Original: POST /reservaciones/check-out-rapido/{id}
    if (typeof window.ejecutarCheckOutRapido === 'function') {
      const _orig = window.ejecutarCheckOutRapido.bind(window);

      window.ejecutarCheckOutRapido = function (reservacionId) {
        if (navigator.onLine) return _orig(reservacionId);

        Swal.fire({
          icon:                'question',
          title:               '¿Registrar Check-out?',
          html:                `<p>Reservación <strong>#${reservacionId}</strong></p>
                                <p class="text-sm text-amber-600 mt-2">
                                  <i class="fas fa-wifi-slash mr-1"></i>
                                  Sin conexión — la salida se sincronizará al volver internet.
                                </p>`,
          showCancelButton:    true,
          confirmButtonColor:  '#DC2626',
          cancelButtonColor:   '#6B7280',
          confirmButtonText:   '<i class="fas fa-sign-out-alt mr-1"></i>Sí, Check-out',
          cancelButtonText:    'Cancelar',
          reverseButtons:      true,
        }).then(async result => {
          if (!result.isConfirmed) return;

          await window.OfflineData.encolarOperacion(
            'checkout',
            {
              reservacion_id:              reservacionId,
              estado_habitacion_destino:   'limpieza',
            },
            `Check-out reservación #${reservacionId}`
          );

          // Actualizar visualmente las cards de esa reservación
          _marcarReservacionCheckout(reservacionId);
          _mostrarToastOffline('Check-out registrado. Se enviará al volver internet.');
        });
      };
    }

    // ── hacerCheckInRapido(reservacionId) ─────────────────────────────────
    //    Original: redirige a ver.php#{checkin} (requiere internet)
    //    Offline:  encolamos el check-in simple
    if (typeof window.hacerCheckInRapido === 'function') {
      const _orig = window.hacerCheckInRapido.bind(window);

      window.hacerCheckInRapido = function (reservacionId) {
        if (navigator.onLine) return _orig(reservacionId);

        Swal.fire({
          icon:                'question',
          title:               '¿Registrar Check-in?',
          html:                `<p>Reservación <strong>#${reservacionId}</strong></p>
                                <p class="text-sm text-amber-600 mt-2">
                                  <i class="fas fa-wifi-slash mr-1"></i>
                                  Sin conexión — el check-in se sincronizará al volver internet.<br>
                                  <small>El cobro deberá registrarse en caja cuando vuelva internet.</small>
                                </p>`,
          showCancelButton:    true,
          confirmButtonColor:  '#7C3AED',
          cancelButtonColor:   '#6B7280',
          confirmButtonText:   '<i class="fas fa-sign-in-alt mr-1"></i>Sí, Check-in',
          cancelButtonText:    'Cancelar',
          reverseButtons:      true,
        }).then(async result => {
          if (!result.isConfirmed) return;

          await window.OfflineData.encolarOperacion(
            'checkin',
            { reservacion_id: reservacionId },
            `Check-in reservación #${reservacionId}`
          );

          _marcarReservacionCheckin(reservacionId);
          _mostrarToastOffline('Check-in registrado. Se enviará al volver internet.');
        });
      };
    }

    console.log('[HabitacionesOffline] Interceptores instalados');
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 2. ACTUALIZACIONES OPTIMISTAS DE LA UI
  //    Reflejan el cambio visualmente sin recargar la página.
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Cambia el color/clase de la card de habitación al nuevo estado.
   * @param {number|string} habitacionId
   * @param {string} nuevoEstado  - 'disponible' | 'limpieza' | 'ocupada' | 'mantenimiento'
   */
  function _actualizarCardEstado(habitacionId, nuevoEstado) {
    const card = document.querySelector(`[data-habitacion-id="${habitacionId}"]`);
    if (!card) return;

    const front = card.querySelector('.flip-card-front');
    if (!front) return;

    // Quitar todos los estado-* existentes
    const clases = [...front.classList];
    clases.forEach(c => {
      if (c.startsWith('estado-')) front.classList.remove(c);
    });

    // Agregar el nuevo estado
    front.classList.add(`estado-${nuevoEstado}`);

    // Badge de "pendiente sync"
    _agregarBadgeSync(card);
  }

  /**
   * Marca visualmente las cards de una reservación como en proceso de checkout.
   */
  function _marcarReservacionCheckout(reservacionId) {
    // Las cards de reservación tienen el ID en el atributo data o en el botón onclick
    document.querySelectorAll(`[data-reservacion-id="${reservacionId}"]`).forEach(card => {
      const front = card.querySelector('.flip-card-front');
      if (front) {
        front.style.opacity = '0.6';
        front.style.filter  = 'grayscale(0.5)';
      }
      _agregarBadgeSync(card);
    });

    // Intentar también por onclick que contenga el reservacionId
    document.querySelectorAll('.flip-card').forEach(card => {
      const btns = card.querySelectorAll(`[onclick*="${reservacionId}"]`);
      if (btns.length > 0) {
        const front = card.querySelector('.flip-card-front');
        if (front) {
          front.style.opacity = '0.6';
        }
        _agregarBadgeSync(card);
      }
    });
  }

  /**
   * Marca visualmente la card de reservación como "checked_in" pendiente de sync.
   */
  function _marcarReservacionCheckin(reservacionId) {
    document.querySelectorAll(`[data-reservacion-id="${reservacionId}"]`).forEach(card => {
      _agregarBadgeSync(card);
    });

    document.querySelectorAll('.flip-card').forEach(card => {
      const btns = card.querySelectorAll(`[onclick*="${reservacionId}"]`);
      if (btns.length > 0) _agregarBadgeSync(card);
    });
  }

  /**
   * Agrega el badge "⏳ SYNC" a una card si no lo tiene ya.
   */
  function _agregarBadgeSync(card) {
    if (card.querySelector('.offline-sync-badge')) return;

    const badge       = document.createElement('div');
    badge.className   = 'offline-sync-badge';
    badge.innerHTML   = '⏳ SYNC';
    badge.title       = 'Cambio pendiente de sincronización con el servidor';
    badge.style.cssText = `
      position:         absolute;
      top:              4px;
      right:            4px;
      background:       rgba(217, 119, 6, 0.92);
      color:            white;
      font-size:        9px;
      font-weight:      700;
      padding:          2px 5px;
      border-radius:    4px;
      z-index:          20;
      letter-spacing:   0.05em;
      pointer-events:   none;
      box-shadow:       0 1px 4px rgba(0,0,0,0.2);
    `;

    // Necesita position:relative en la card para que el absolute funcione
    card.style.position = 'relative';
    card.appendChild(badge);
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 3. BANNER GLOBAL DE MODO OFFLINE EN LA VISTA
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Muestra/oculta el banner de modo offline dentro de la vista de habitaciones.
   * También deshabilita botones que requieren internet de forma inevitable.
   */
  function _actualizarEstadoOfflineUI(estaOffline) {
    document.getElementById('hab-offline-banner')?.remove();
    if (!estaOffline) _limpiarBadgesSync();
    return;
    const banner = document.getElementById('hab-offline-banner');

    if (estaOffline) {
      if (!banner) _crearBannerHabitaciones();
    } else {
      if (banner) banner.remove();
      // Quitar badges de sync de cards que ya fueron sincronizadas
      _limpiarBadgesSync();
    }
  }

  function _crearBannerHabitaciones() {
    // No duplicar si ya existe
    if (document.getElementById('hab-offline-banner')) return;

    const banner        = document.createElement('div');
    banner.id           = 'hab-offline-banner';
    banner.innerHTML    = `
      <span style="font-size:1.1em">📡</span>
      <div>
        <strong>Modo sin conexión activo</strong>
        <span style="display:block;font-size:0.8em;opacity:0.9;margin-top:2px">
          Check-in, check-out y cambios de estado quedan guardados y se envían al volver internet.
        </span>
      </div>
      <span id="hab-offline-badge" style="
        background: rgba(255,255,255,0.2);
        border-radius: 12px;
        padding: 2px 10px;
        font-size: 0.78em;
        font-weight: 700;
        white-space: nowrap;
      ">cargando...</span>
    `;
    banner.style.cssText = `
      position:        sticky;
      top:             0;
      z-index:         100;
      display:         flex;
      align-items:     center;
      gap:             12px;
      background:      linear-gradient(135deg, #D97706, #B45309);
      color:           white;
      padding:         10px 20px;
      font-size:       0.88em;
      box-shadow:      0 2px 8px rgba(0,0,0,0.15);
    `;

    // Insertar al inicio del contenido de la vista
    const vista = document.querySelector('.habitaciones-view, main');
    if (vista) vista.insertBefore(banner, vista.firstChild);

    // Actualizar contador de pendientes en el badge
    _actualizarContadorBanner();
  }

  async function _actualizarContadorBanner() {
    const badge = document.getElementById('hab-offline-badge');
    if (!badge || !window.OfflineData) return;
    try {
      const pendientes = await window.OfflineData.obtenerPendientes();
      badge.textContent = pendientes.length === 0
        ? 'Sin pendientes'
        : `${pendientes.length} pendiente${pendientes.length > 1 ? 's' : ''}`;
    } catch (_) {}
  }

  function _mostrarBannerOfflineEnCards() {
    // Marcar con badge las cards que ya tienen operaciones pendientes en IndexedDB
    if (!window.OfflineData) return;
    window.OfflineData.obtenerPendientes().then(pendientes => {
      pendientes.forEach(op => {
        const habId = op.payload?.habitacion_id;
        const resId = op.payload?.reservacion_id;
        if (habId) {
          const card = document.querySelector(`[data-habitacion-id="${habId}"]`);
          if (card) _agregarBadgeSync(card);
        }
        if (resId) _marcarReservacionCheckin(resId);
      });
    }).catch(() => {});
  }

  function _limpiarBadgesSync() {
    document.querySelectorAll('.offline-sync-badge').forEach(b => b.remove());
    document.querySelectorAll('.flip-card-front[style*="opacity"]').forEach(el => {
      el.style.opacity = '';
      el.style.filter  = '';
    });
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 4. TOAST LIGERO (reutiliza window.PWA si está disponible)
  // ═══════════════════════════════════════════════════════════════════════════

  function _mostrarToastOffline(mensaje) {
    if (window.PWA?.showToast) {
      window.PWA.showToast(mensaje, 'warning', 4000);
    } else {
      console.warn('[HabitacionesOffline]', mensaje);
    }
    // Actualizar el contador del banner después de encolar
    setTimeout(_actualizarContadorBanner, 300);
  }

})();
