/**
 * reservaciones-offline.js — Los Cedros
 *
 * Muestra las reservaciones del día desde el caché IndexedDB
 * cuando el dispositivo está sin internet.
 *
 * Qué hace:
 *   - Detecta modo offline al cargar y al cambiar estado de red.
 *   - Lee el snapshot de reservaciones de IndexedDB (capturado por offline-data.js).
 *   - Renderiza tarjetas idénticas a las del PHP en el grid #gridCards.
 *   - Muestra un banner amber con la hora del último snapshot.
 *   - Mantiene funcional el buscador (#buscarReservacion) y los filtros de estado.
 *   - Al volver internet: recarga la página para mostrar datos frescos del servidor.
 *
 * Solo se activa en la vista de Reservaciones (footer.php la carga condicionalmente).
 * Depende de: offline-data.js (window.OfflineData)
 */
(function () {
  'use strict';

  // ═══════════════════════════════════════════════════════════════════════════
  // MAPA DE COLORES POR NÚMERO DE HABITACIÓN (espejo del PHP)
  // ═══════════════════════════════════════════════════════════════════════════
  const COLORES = {
    MOKA:      { bg: '#7B5B3A', dark: '#5C3D20' },
    PURPURA:   { bg: '#8B45A6', dark: '#6B2586' },
    ORO:       { bg: '#C8A832', dark: '#A08820' },
    AMARILLO:  { bg: '#D4B830', dark: '#B89E18' },
    MARRON:    { bg: '#8B6B4A', dark: '#6B4B2A' },
    CEREZA:    { bg: '#C0334D', dark: '#9B1830' },
    VIOLETA:   { bg: '#7B5EA7', dark: '#5B3E87' },
    LIMON:     { bg: '#A8B820', dark: '#8A9A10' },
    NARANJA:   { bg: '#E07830', dark: '#C05818' },
    MARFIL:    { bg: '#B8A878', dark: '#988858' },
    AZUL:      { bg: '#4080C0', dark: '#2860A0' },
    ROSA:      { bg: '#D46B8A', dark: '#B44B6A' },
    VERDE:     { bg: '#4CAF50', dark: '#357A38' },
    UVA:       { bg: '#7B4B8B', dark: '#5B2B6B' },
    MENTA:     { bg: '#5BBF8A', dark: '#3B9F6A' },
    AMBAR:     { bg: '#D49B30', dark: '#B47B10' },
    VINO:      { bg: '#8B2040', dark: '#6B0020' },
    GRIS:      { bg: '#7A8890', dark: '#5A6870' },
    CHOCOLATE: { bg: '#6B3F20', dark: '#4B2010' },
    CORAL:     { bg: '#E07060', dark: '#C05040' },
    TURQUESA:  { bg: '#30A8A0', dark: '#188880' },
    MAGENTA:   { bg: '#C030A0', dark: '#A01080' },
  };
  const COLOR_NUMERICO = { bg: '#5C7A4E', dark: '#4A6340' };
  const COLOR_DEFAULT  = { bg: '#5C7A4E', dark: '#4A6340' };

  // ═══════════════════════════════════════════════════════════════════════════
  // ESTADO DE BADGES (espejo del PHP $estados)
  // ═══════════════════════════════════════════════════════════════════════════
  const ESTADO_LABELS = {
    confirmada:  'Confirmada',
    checked_in:  'Check-in',
    completada:  'Check-out',
    cancelada:   'Cancelada',
    pendiente:   'Pendiente',
  };

  // ═══════════════════════════════════════════════════════════════════════════
  // INICIALIZACIÓN
  // ═══════════════════════════════════════════════════════════════════════════

  document.addEventListener('DOMContentLoaded', function () {
    // Solo aplica en la vista de reservaciones
    if (!document.getElementById('gridCards') &&
        !document.querySelector('.res-page, [id="buscarReservacion"]')) return;

    // Evaluar estado inicial
    _evaluarModoOffline();

    // Escuchar cambios de red
    window.addEventListener('offline', () => _evaluarModoOffline());
    window.addEventListener('loscedros:network-change', event => {
      if (event.detail?.online === false) _evaluarModoOffline();
    });
    window.addEventListener('online',  () => {
      _quitarBannerOffline();
      // Esperar 2.5s para que offline-data.js sincronice y luego recargar
      setTimeout(() => {
        if (navigator.onLine) window.location.reload();
      }, 2500);
    });
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // LÓGICA PRINCIPAL
  // ═══════════════════════════════════════════════════════════════════════════

  async function _evaluarModoOffline() {
    const sinConexion = !navigator.onLine || window.PWA?.isOnline?.() === false;
    if (!sinConexion) return;

    // Verificar si hay cache disponible
    if (!window.OfflineData) {
      _mostrarBannerOffline('Sin caché disponible. Conéctate para ver reservaciones.');
      return;
    }

    try {
      const reservaciones = await window.OfflineData.obtenerReservaciones();
      const meta          = await _leerMetaSnapshot();

      if (!reservaciones || reservaciones.length === 0) {
        _mostrarBannerOffline('Sin datos en caché. Conéctate al menos una vez para guardar las reservaciones.');
        _ocultarGridOriginal();
        return;
      }

      _mostrarBannerOffline(null, meta?.valor || null);
      _renderizarTarjetas(reservaciones);
      _activarBuscadorOffline(reservaciones);
      _activarFiltrosEstadoOffline(reservaciones);

    } catch (err) {
      console.error('[ReservacionesOffline] Error al leer caché:', err);
      _mostrarBannerOffline('Error al leer el caché. Recarga cuando tengas internet.');
    }
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // RENDERIZADO DE TARJETAS
  // ═══════════════════════════════════════════════════════════════════════════

  function _renderizarTarjetas(reservaciones, filtroEstado = 'todos', filtroBuscar = '') {
    const grid = document.getElementById('gridCards');
    if (!grid) return;

    // Filtrar
    const visibles = reservaciones.filter(r => {
      if (filtroEstado !== 'todos' && r.estado !== filtroEstado) return false;
      if (filtroBuscar) {
        const texto = filtroBuscar.toLowerCase();
        const search = [
          r.huesped_nombre    || '',
          r.huesped_telefono  || '',
          r.habitaciones_numeros || '',
          String(r.id),
        ].join(' ').toLowerCase();
        if (!search.includes(texto)) return false;
      }
      return true;
    });

    // Limpiar grid y mostrar tarjetas cacheadas
    grid.innerHTML = '';

    if (visibles.length === 0) {
      grid.innerHTML = `
        <div class="col-span-3 bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-10 text-center">
          <i class="fas fa-search text-gray-300 text-3xl mb-3"></i>
          <p class="text-gray-400 text-sm">Sin resultados para la búsqueda</p>
        </div>`;
      return;
    }

    visibles.forEach(r => {
      grid.appendChild(_crearTarjeta(r));
    });
    _activarAccionesOffline(visibles);

    // Actualizar contador
    const countEl = document.getElementById('searchCount');
    const resultsEl = document.getElementById('searchResults');
    if (countEl) countEl.textContent = visibles.length;
    if (resultsEl) {
      resultsEl.classList.toggle('hidden', !filtroBuscar && filtroEstado === 'todos');
    }
  }

  function _crearTarjeta(r) {
    const color      = _obtenerColorHab(r.habitaciones_numeros || '');
    const estado     = r.estado || 'confirmada';
    const estadoLabel = ESTADO_LABELS[estado] || estado;
    const tienePago  = !!r.metodo_pago && estado === 'checked_in';

    // Múltiples habitaciones
    const totalHabs = r.total_habitaciones || 1;
    const multiBadge = totalHabs > 1
      ? `<span style="background:rgba(255,255,255,0.2);border-radius:8px;padding:1px 7px;font-size:.68rem;font-weight:700;">
           <i class="fas fa-link" style="font-size:.5rem;"></i> ${totalHabs} habitaciones
         </span>`
      : '';

    // Tipo habitación
    const habTipo = r.habitaciones_tipos
      ? r.habitaciones_tipos.split('||')[0].replace(/_/g, ' ').toUpperCase()
      : '';
    const tipoHtml = habTipo
      ? `<div style="font-size:.7rem;opacity:.8;margin-top:1px;">${_esc(habTipo)}</div>`
      : '';

    // Badge de estado
    let badgeHtml;
    if (tienePago) {
      badgeHtml = `<span class="badge-estado badge-pagado">PAGADO · ${r.metodo_pago.toUpperCase()}</span>`;
    } else {
      badgeHtml = `<span class="badge-estado badge-${estado}">${estadoLabel.toUpperCase()}</span>`;
    }

    // Múltiples habitaciones — fila extra
    const idReservacion = String(r.id);
    const esTemporal = idReservacion.startsWith('tmp_res_');
    const syncBadge = r.offline_pendiente
      ? `<span class="badge-estado" style="background:#FFF7ED;color:#9A3412;">PENDIENTE SYNC</span>`
      : '';
    const accionHtml = estado === 'confirmada'
      ? `<button type="button" class="btn-ticket-full" data-offline-res-action="checkin" data-res-id="${_esc(idReservacion)}" style="background:#2563EB;">
           <i class="fas fa-user-check"></i> check-in
         </button>`
      : (estado === 'checked_in'
          ? `<button type="button" class="btn-ticket-full" data-offline-res-action="checkout" data-res-id="${_esc(idReservacion)}" style="background:#EA580C;">
               <i class="fas fa-person-walking-arrow-right"></i> check-out
             </button>`
          : '');
    const verHtml = esTemporal
      ? `<span class="btn-ticket-full" style="background:#6B7280;cursor:not-allowed;opacity:.9;">
           <i class="fas fa-clock"></i> pendiente
         </span>`
      : `<a href="${_baseUrl()}/reservaciones/ver/${r.id}" class="btn-ticket-full">
           <i class="fas fa-receipt"></i> ver
         </a>`;

    const multiRow = totalHabs > 1
      ? `<div class="info-row-card">
           <span class="info-label-card"><i class="fas fa-door-open mr-1 text-gray-300"></i>Habs:</span>
           <span class="info-value-card" style="color:#2563EB;">${_esc(r.habitaciones_numeros || '')}</span>
         </div>`
      : '';

    const card = document.createElement('div');
    card.className = 'card-reservacion bg-white rounded-xl shadow-sm border border-[#DDE8D5] overflow-hidden offline-card';
    card.dataset.search = [
      r.huesped_nombre   || '',
      r.huesped_telefono || '',
      r.habitaciones_numeros || '',
      r.id,
    ].join(' ').toLowerCase();
    card.dataset.estado = estado;

    card.innerHTML = `
      <!-- Header -->
      <div class="card-header-room"
           style="background:linear-gradient(135deg,${color.bg},${color.dark});">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2.5 min-w-0">
            <i class="fas fa-bed text-lg opacity-80"></i>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="room-number">${_esc(r.habitaciones_numeros || '?')}</span>
                ${multiBadge}
              </div>
              ${tipoHtml}
            </div>
          </div>
          <span class="badge-id">#${r.id}</span>
        </div>
      </div>

      <!-- Body -->
      <div class="card-body-info">
        <div class="flex items-center justify-between mb-2">
          <span class="badge-fecha" style="background:#F0F5ED;color:#4A6340;">
            <i class="far fa-calendar-alt"></i>
            ${_formatFecha(r.fecha_entrada)}
            <span style="color:#A8C4A0;margin:0 1px;">&rarr;</span>
            ${_formatFecha(r.fecha_salida)}
          </span>
        </div>
        <div class="info-row-card">
          <span class="info-label-card"><i class="fas fa-user mr-1 text-gray-300"></i>Cliente:</span>
          <span class="info-value-card">${_esc(r.huesped_nombre || 'Sin nombre')}</span>
        </div>
        <div class="info-row-card">
          <span class="info-label-card"><i class="fas fa-phone mr-1 text-gray-300"></i>Teléfono:</span>
          <span class="info-value-card">${_esc(r.huesped_telefono || 'N/A')}</span>
        </div>
        ${multiRow}

        <div class="flex items-center justify-between mt-3 pt-2" style="border-top:1px solid #F0F5ED;">
          <div class="flex flex-wrap gap-1.5">${badgeHtml}${syncBadge}</div>
          <span class="price-display">${_formatMoney(r.precio_total || 0)}</span>
        </div>
      </div>

      <!-- Action -->
      <div class="card-actions" style="display:grid;grid-template-columns:${accionHtml ? '1fr 1fr' : '1fr'};gap:8px;">
        ${accionHtml}
        ${verHtml}
      </div>
    `;

    return card;
  }

  function _activarAccionesOffline(reservaciones) {
    const grid = document.getElementById('gridCards');
    if (!grid) return;

    grid.querySelectorAll('[data-offline-res-action]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.resId;
        const accion = btn.dataset.offlineResAction;
        await _registrarAccionOffline(id, accion, reservaciones);
      });
    });
  }

  async function _registrarAccionOffline(id, accion, reservaciones) {
    if (!window.OfflineData || !id) return;

    const esCheckin = accion === 'checkin';
    const tipo = esCheckin ? 'checkin' : 'checkout';
    const estado = esCheckin ? 'checked_in' : 'completada';
    const label = esCheckin
      ? `Check-in reservacion ${id}`
      : `Check-out reservacion ${id}`;
    const texto = esCheckin ? 'Check-in registrado localmente.' : 'Check-out registrado localmente.';

    try {
      await window.OfflineData.encolarOperacion(
        tipo,
        esCheckin
          ? { reservacion_id: id }
          : { reservacion_id: id, estado_habitacion_destino: 'limpieza' },
        label
      );
      await window.OfflineData.actualizarReservacionLocal(id, { estado, offline_pendiente: true });

      const idx = reservaciones.findIndex(r => String(r.id) === String(id));
      if (idx >= 0) reservaciones[idx] = { ...reservaciones[idx], estado, offline_pendiente: true };

      window.PWA?.showToast(texto, 'success');
      _renderizarTarjetas(reservaciones, _estadoFiltroActivo(), (document.getElementById('buscarReservacion')?.value || '').trim());
    } catch (err) {
      console.error('[ReservacionesOffline] No se pudo registrar accion:', err);
      window.PWA?.showToast('No se pudo guardar la accion offline.', 'error');
    }
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // BUSCADOR Y FILTROS OFFLINE
  // ═══════════════════════════════════════════════════════════════════════════

  function _activarBuscadorOffline(reservaciones) {
    const input = document.getElementById('buscarReservacion');
    if (!input) return;

    // Evitar listeners duplicados
    input.removeEventListener('input', input._offlineHandler);

    input._offlineHandler = function () {
      const texto        = this.value.trim();
      const estadoActivo = _estadoFiltroActivo();
      _renderizarTarjetas(reservaciones, estadoActivo, texto);
    };

    input.addEventListener('input', input._offlineHandler);
  }

  function _activarFiltrosEstadoOffline(reservaciones) {
    document.querySelectorAll('[data-estado]').forEach(btn => {
      btn.removeEventListener('click', btn._offlineHandler);

      btn._offlineHandler = function () {
        const estado = this.dataset.estado;
        const buscar = (document.getElementById('buscarReservacion')?.value || '').trim();
        _renderizarTarjetas(reservaciones, estado, buscar);

        // Actualizar estilos activos (reutiliza la función global si existe)
        if (typeof filtrarEstado === 'function') {
          // La función original del PHP, si está disponible, maneja estilos
        } else {
          document.querySelectorAll('[data-estado]').forEach(b => b.classList.remove('active'));
          this.classList.add('active');
        }
      };

      btn.addEventListener('click', btn._offlineHandler);
    });
  }

  function _estadoFiltroActivo() {
    const activo = document.querySelector('[data-estado].active');
    return activo?.dataset.estado || 'todos';
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // BANNER OFFLINE
  // ═══════════════════════════════════════════════════════════════════════════

  function _mostrarBannerOffline(mensajeExtra, cacheFecha) {
    return;
    if (document.getElementById('res-offline-banner')) return;

    const banner = document.createElement('div');
    banner.id    = 'res-offline-banner';

    const horaCache = cacheFecha ? _formatHoraCache(cacheFecha) : null;
    const subtitulo = horaCache
      ? `Datos del caché guardado a las <strong>${horaCache}</strong>. Solo lectura sin conexión.`
      : (mensajeExtra || 'Mostrando datos del caché local. Conéctate para datos actualizados.');

    banner.innerHTML = `
      <span style="font-size:1.2em;flex-shrink:0;">📡</span>
      <div style="flex:1;min-width:0;">
        <strong>Reservaciones en modo offline</strong>
        <span style="display:block;font-size:0.82em;opacity:0.9;margin-top:1px;">
          ${subtitulo}
        </span>
      </div>
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

    const contenedor = document.querySelector('.res-page, main');
    if (contenedor) {
      contenedor.insertBefore(banner, contenedor.firstChild);
    } else {
      document.body.insertBefore(banner, document.body.firstChild);
    }
  }

  function _quitarBannerOffline() {
    const banner = document.getElementById('res-offline-banner');
    if (banner) banner.remove();
  }

  function _ocultarGridOriginal() {
    const grid = document.getElementById('gridCards');
    if (grid) grid.innerHTML = `
      <div class="col-span-3 bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-10 text-center">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(217,119,6,.08);
                    display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
          <i class="fas fa-wifi-slash text-2xl" style="color:#D97706;"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-700 mb-2">Sin datos en caché</h3>
        <p class="text-gray-400 text-sm">
          Conéctate a internet al menos una vez para guardar las reservaciones del día.
        </p>
      </div>`;
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // HELPERS
  // ═══════════════════════════════════════════════════════════════════════════

  /** Obtiene el color del header de la card según el número/nombre de la habitación */
  function _obtenerColorHab(numerosStr) {
    if (!numerosStr) return COLOR_DEFAULT;

    // Tomar el primer número si hay varios separados por coma
    const primero = numerosStr.split(',')[0].trim().toUpperCase();

    // Si es puramente numérico → color numerico
    if (/^\d+$/.test(primero)) return COLOR_NUMERICO;

    // Buscar en el mapa por nombre exacto o como subcadena
    for (const [nombre, color] of Object.entries(COLORES)) {
      if (primero === nombre || primero.includes(nombre)) return color;
    }
    return COLOR_DEFAULT;
  }

  /**
   * Lee la metadata del snapshot de reservaciones desde la DB scoped.
   * offline-data.js guarda la key 'ultima_sync_reservaciones' con campo 'valor'.
   */
  async function _leerMetaSnapshot() {
    try {
      if (!window.OfflineData?.obtenerMetaSync) return null;
      const meta = await window.OfflineData.obtenerMetaSync();
      return meta?.reservaciones || null;
    } catch (_) {
      return null;
    }
  }

  /** Formatea 'Y-m-d' → 'd/m/Y' */
  function _formatFecha(fechaStr) {
    if (!fechaStr) return 'N/A';
    const [y, m, d] = fechaStr.split('-');
    return `${d}/${m}/${y}`;
  }

  /** Formatea número como moneda: $1,234.00 */
  function _formatMoney(num) {
    return '$' + Number(num).toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  /** Formatea 'Y-m-d H:i:s' → 'HH:MM' */
  function _formatHoraCache(fechaHoraStr) {
    if (!fechaHoraStr) return null;
    try {
      const d = new Date(fechaHoraStr.replace(' ', 'T'));
      return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    } catch (_) {
      return fechaHoraStr.substring(11, 16);
    }
  }

  /** Obtiene la base URL de la aplicación (definida por header.php en window.BASE_URL) */
  function _baseUrl() {
    return (window.BASE_URL || '').replace(/\/$/, '');
  }

  /** Escape HTML básico para prevenir XSS */
  function _esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

})();
