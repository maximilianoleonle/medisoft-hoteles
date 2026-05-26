/**
 * caja-offline.js — Los Cedros
 *
 * Intercepta los formularios de la vista de caja para que
 * funcionen sin internet:
 *
 *   - Formulario "Registrar Ingreso" (#modalIngreso form)
 *   - Formulario "Registrar Gasto"   (#modalGasto form)
 *
 * Cuando hay internet:   el formulario se envía al servidor normalmente (sin tocar nada).
 * Cuando no hay internet: se previene el envío, los datos se encolan en IndexedDB
 *                          con UUID, y la UI muestra el movimiento pendiente.
 *
 * Al volver internet:   offline-data.js detecta el evento 'online' y llama
 *                        sincronizar() → POST /api/sync → Sync.php aplica.
 *
 * Depende de: offline-data.js (window.OfflineData) y pwa.js (window.PWA)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    // Solo aplica en la vista de caja
    const esCaja = document.getElementById('modalIngreso') || document.getElementById('modalGasto');
    if (!esCaja) return;

    _instalarInterceptorIngreso();
    _instalarInterceptorGasto();
    _actualizarBannerOffline();

    window.addEventListener('online',  () => _actualizarBannerOffline());
    window.addEventListener('offline', () => _actualizarBannerOffline());

    // Al sincronizar: recargar la página para mostrar los movimientos reales
    window.addEventListener('online', async () => {
      // offline-data.js ya llama sincronizar() al detectar 'online'
      // Esperamos 3s para que termine y recargamos
      setTimeout(() => {
        if (navigator.onLine) window.location.reload();
      }, 3500);
    });
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // 1. INTERCEPTOR: FORMULARIO DE INGRESO
  // ═══════════════════════════════════════════════════════════════════════════

  function _instalarInterceptorIngreso() {
    const modal = document.getElementById('modalIngreso');
    if (!modal) return;

    const form = modal.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
      // Online → dejar pasar al servidor
      if (navigator.onLine) return;

      e.preventDefault();
      e.stopPropagation();

      const datos = _extraerDatosForm(form, 'ingreso');
      if (!datos) return;  // validación falló, el form lo muestra

      await _encolarMovimiento('pago_caja', datos, 'ingreso');

      // Cerrar modal y mostrar feedback
      if (typeof cerrarModalIngreso === 'function') cerrarModalIngreso();
      form.reset();
    });
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 2. INTERCEPTOR: FORMULARIO DE GASTO
  // ═══════════════════════════════════════════════════════════════════════════

  function _instalarInterceptorGasto() {
    const modal = document.getElementById('modalGasto');
    if (!modal) return;

    const form = modal.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
      if (navigator.onLine) return;

      e.preventDefault();
      e.stopPropagation();

      const datos = _extraerDatosForm(form, 'gasto');
      if (!datos) return;

      await _encolarMovimiento('gasto_caja', datos, 'gasto');

      if (typeof cerrarModalGasto === 'function') cerrarModalGasto();
      form.reset();
    });
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 3. EXTRACCIÓN Y VALIDACIÓN DE DATOS DEL FORMULARIO
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Lee los campos del formulario y devuelve un objeto limpio para IndexedDB.
   * Devuelve null si hay errores de validación.
   */
  function _extraerDatosForm(form, tipo) {
    const monto       = parseFloat(form.querySelector('[name="monto"]')?.value || '0');
    const descripcion = (form.querySelector('[name="descripcion"]')?.value || '').trim();
    const metodoPago  = form.querySelector('[name="metodo_pago"]')?.value || 'efectivo';
    const referencia  = (form.querySelector('[name="referencia"]')?.value || '').trim();
    const comprobante = (form.querySelector('[name="comprobante"]')?.value || '').trim();

    // Obtener nombre de categoría desde el <select> (no solo el ID)
    const catSelect   = form.querySelector('[name="categoria_id"]');
    const categoriaId = catSelect?.value || '';
    const categoriaNombre = catSelect?.options[catSelect.selectedIndex]?.text?.trim() || '';

    // Validaciones mínimas
    if (!monto || monto <= 0) {
      _mostrarError('El monto debe ser mayor a $0');
      return null;
    }
    if (!descripcion) {
      _mostrarError('La descripción es obligatoria');
      return null;
    }

    return {
      monto,
      descripcion,
      metodo_pago:  metodoPago,
      categoria:    categoriaNombre || (tipo === 'ingreso' ? 'Hospedaje' : 'Gastos Generales'),
      categoria_id: categoriaId,
      referencia:   referencia  || null,
      comprobante:  comprobante || null,
    };
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 4. ENCOLAR Y ACTUALIZAR UI
  // ═══════════════════════════════════════════════════════════════════════════

  async function _encolarMovimiento(tipo, datos, tipoLabel) {
    if (!window.OfflineData) {
      _mostrarError('Módulo offline no disponible. Recarga la página.');
      return;
    }

    const label = tipoLabel === 'ingreso'
      ? `Ingreso $${datos.monto.toFixed(2)} — ${datos.descripcion}`
      : `Gasto $${datos.monto.toFixed(2)} — ${datos.descripcion}`;

    await window.OfflineData.encolarOperacion(tipo, datos, label);

    // Mostrar el movimiento en la lista de pendientes offline de la UI
    _agregarMovimientoPendienteUI(datos, tipoLabel);

    // Toast de confirmación
    const simbolo = tipoLabel === 'ingreso' ? '+' : '-';
    window.PWA?.showToast(
      `${simbolo}$${datos.monto.toFixed(2)} guardado sin conexión (${datos.metodo_pago}) — se sincronizará al volver internet.`,
      'warning',
      5000
    );

    _actualizarContadorPendientes();
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 5. UI: LISTA DE MOVIMIENTOS PENDIENTES OFFLINE
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Inserta una fila visual en la lista de movimientos de la página
   * para que la recepcionista vea lo que quedó pendiente.
   */
  function _agregarMovimientoPendienteUI(datos, tipo) {
    // Buscar el contenedor de movimientos recientes
    const tabla = document.querySelector('.movimientos-list, [data-movimientos], tbody');

    // Si no encontramos la tabla, al menos creamos el panel de pendientes
    _actualizarPanelPendientes(datos, tipo);
  }

  /**
   * Crea o actualiza un panel flotante que muestra los movimientos
   * pendientes de sincronizar.
   */
  function _actualizarPanelPendientes(datosNuevo, tipo) {
    let panel = document.getElementById('caja-offline-panel');

    if (!panel) {
      panel = document.createElement('div');
      panel.id = 'caja-offline-panel';
      panel.style.cssText = `
        position:      fixed;
        bottom:        80px;
        right:         20px;
        width:         300px;
        max-height:    320px;
        background:    white;
        border:        2px solid #D97706;
        border-radius: 12px;
        box-shadow:    0 8px 30px rgba(0,0,0,0.15);
        z-index:       9000;
        overflow:      hidden;
        font-size:     0.82rem;
      `;
      panel.innerHTML = `
        <div style="background:linear-gradient(135deg,#D97706,#B45309);color:white;padding:8px 12px;
                    display:flex;justify-content:space-between;align-items:center;">
          <span style="font-weight:700;font-size:0.85em;">
            ⏳ Movimientos sin sincronizar
          </span>
          <span id="caja-offline-count" style="background:rgba(255,255,255,0.25);
                border-radius:10px;padding:1px 8px;font-size:0.8em;font-weight:700;">0</span>
        </div>
        <div id="caja-offline-list" style="overflow-y:auto;max-height:240px;padding:6px 8px;"></div>
        <div style="padding:6px 10px;border-top:1px solid #fde68a;background:#fffbeb;
                    font-size:0.75em;color:#92400e;text-align:center;">
          Se enviarán automáticamente al volver internet
        </div>
      `;
      document.body.appendChild(panel);
    }

    // Agregar el nuevo movimiento a la lista
    if (datosNuevo) {
      const lista = document.getElementById('caja-offline-list');
      if (lista) {
        const item = document.createElement('div');
        item.style.cssText = `
          display:flex;justify-content:space-between;align-items:center;
          padding:5px 4px;border-bottom:1px solid #f3f4f6;
        `;
        const colorMonto = tipo === 'ingreso' ? '#059669' : '#DC2626';
        const simbolo    = tipo === 'ingreso' ? '+' : '-';
        item.innerHTML = `
          <div>
            <div style="font-weight:600;color:#374151;">${_truncar(datosNuevo.descripcion, 22)}</div>
            <div style="color:#6B7280;font-size:0.78em;">${datosNuevo.metodo_pago} · ${datosNuevo.categoria}</div>
          </div>
          <div style="font-weight:700;color:${colorMonto};white-space:nowrap;">
            ${simbolo}$${datosNuevo.monto.toFixed(2)}
          </div>
        `;
        lista.prepend(item);
      }
    }

    _actualizarContadorPendientes();
  }

  async function _actualizarContadorPendientes() {
    const counter = document.getElementById('caja-offline-count');
    if (!counter || !window.OfflineData) return;
    try {
      const pendientes = await window.OfflineData.obtenerPendientes();
      const cajaPend   = pendientes.filter(o => o.tipo === 'pago_caja' || o.tipo === 'gasto_caja');
      counter.textContent = cajaPend.length;

      // Mostrar panel solo si hay pendientes
      const panel = document.getElementById('caja-offline-panel');
      if (panel) {
        panel.style.display = cajaPend.length > 0 ? 'block' : 'none';
      }
    } catch (_) {}
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 6. BANNER DE MODO OFFLINE EN LA VISTA DE CAJA
  // ═══════════════════════════════════════════════════════════════════════════

  function _actualizarBannerOffline() {
    document.getElementById('caja-offline-banner')?.remove();
    if (!navigator.onLine) {
      _deshabilitarBotonesRiesgosos();
      _cargarPendientesExistentes();
      return;
    }
    _habilitarBotonesRiesgosos();
    _actualizarContadorPendientes();
    return;
    const estaOffline = !navigator.onLine;
    let banner = document.getElementById('caja-offline-banner');

    if (estaOffline) {
      if (!banner) {
        banner = document.createElement('div');
        banner.id = 'caja-offline-banner';
        banner.style.cssText = `
          background:  linear-gradient(135deg, #D97706, #B45309);
          color:       white;
          padding:     10px 20px;
          display:     flex;
          align-items: center;
          gap:         12px;
          font-size:   0.88em;
          position:    sticky;
          top:         0;
          z-index:     100;
          box-shadow:  0 2px 8px rgba(0,0,0,0.15);
        `;
        banner.innerHTML = `
          <span style="font-size:1.2em">📡</span>
          <div>
            <strong>Caja en modo offline</strong>
            <span style="display:block;font-size:0.82em;opacity:0.9;margin-top:1px;">
              Los ingresos y gastos que registres se guardan localmente y se envían al volver internet.
              <strong>No cierres la caja offline.</strong>
            </span>
          </div>
        `;

        // Insertar antes del primer elemento del contenido principal
        const main = document.querySelector('main, .lc-bg, [class*="min-h"]');
        if (main) main.insertBefore(banner, main.firstChild);
      }

      // Deshabilitar botones que NUNCA deben usarse offline
      _deshabilitarBotonesRiesgosos();

      // Cargar lista de pendientes existentes al entrar en offline
      _cargarPendientesExistentes();

    } else {
      // Online: quitar banner y habilitar botones
      if (banner) banner.remove();
      _habilitarBotonesRiesgosos();

      // Ocultar panel si no hay pendientes
      _actualizarContadorPendientes();
    }
  }

  /** Botones que no deben usarse offline: apertura/cierre de corte */
  function _deshabilitarBotonesRiesgosos() {
    document.querySelectorAll(
      '[href*="caja/corte"], [href*="caja/apertura"], [onclick*="corte"], [data-action="corte"]'
    ).forEach(btn => {
      btn.dataset.offlineDisabled = 'true';
      btn.style.opacity           = '0.4';
      btn.style.pointerEvents     = 'none';
      btn.title                   = 'No disponible sin internet';
    });
  }

  function _habilitarBotonesRiesgosos() {
    document.querySelectorAll('[data-offline-disabled="true"]').forEach(btn => {
      delete btn.dataset.offlineDisabled;
      btn.style.opacity       = '';
      btn.style.pointerEvents = '';
      btn.title               = '';
    });
  }

  /** Muestra en el panel los pendientes que ya existían antes de abrir la página */
  async function _cargarPendientesExistentes() {
    if (!window.OfflineData) return;
    try {
      const pendientes = await window.OfflineData.obtenerPendientes();
      const cajaPend   = pendientes.filter(o => o.tipo === 'pago_caja' || o.tipo === 'gasto_caja');

      if (cajaPend.length > 0) {
        // Mostrar el panel con los pendientes existentes
        _actualizarPanelPendientes(null, null); // crea el panel vacío
        cajaPend.forEach(op => {
          const tipo = op.tipo === 'pago_caja' ? 'ingreso' : 'gasto';
          _actualizarPanelPendientes(op.payload, tipo);
        });
      }
    } catch (_) {}
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 7. HELPERS
  // ═══════════════════════════════════════════════════════════════════════════

  function _mostrarError(mensaje) {
    if (window.Swal) {
      Swal.fire({ icon: 'error', title: 'Error', text: mensaje, timer: 3000, showConfirmButton: false });
    } else {
      window.PWA?.showToast(mensaje, 'error');
    }
  }

  function _truncar(texto, max) {
    return texto.length > max ? texto.substring(0, max) + '…' : texto;
  }

})();
