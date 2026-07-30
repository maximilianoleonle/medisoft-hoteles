/**
 * caja-offline.js — Los Cedros
 *
 * Intercepta los formularios de la vista de caja:
 *
 *   - Formulario "Registrar Ingreso" (#modalIngreso form)
 *   - Formulario "Registrar Gasto"   (#modalGasto form)
 *
 * Cuando hay internet:   el formulario se envía al servidor normalmente (sin tocar nada).
 * Cuando no hay internet: el movimiento se ENCOLA con candados (política 2026-07-08,
 *                          reemplaza el bloqueo de 2026-07-02):
 *
 *   1. Solo se encola si hay un corte de caja abierto en el caché offline.
 *   2. El payload lleva corte_id_capturado: si al sincronizar el corte ya
 *      cambió, el servidor RECHAZA el movimiento y recepción lo ve marcado
 *      como fallido (nunca se aplica a un corte equivocado).
 *   3. El servidor marca la descripción como "capturado offline" para auditoría.
 *
 * La apertura y el cierre de corte siguen siendo online-only.
 *
 * Depende de: offline-data.js (window.OfflineData) y pwa.js (window.PWA)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    // Solo aplica en la vista de caja
    const esCaja = document.getElementById('modalIngreso') || document.getElementById('modalGasto');
    if (!esCaja) return;

    _instalarInterceptor('modalIngreso', 'pago_caja', 'ingreso');
    _instalarInterceptor('modalGasto', 'gasto_caja', 'gasto');
    _instalarInterceptorCorte();
    _actualizarBannerOffline();

    window.addEventListener('online',  () => _actualizarBannerOffline());
    window.addEventListener('offline', () => _actualizarBannerOffline());

    // Al sincronizar: recargar la página para mostrar los movimientos reales
    window.addEventListener('online', async () => {
      // offline-data.js ya llama sincronizar() al detectar 'online'
      // Esperamos 3.5s para que termine y recargamos
      setTimeout(() => {
        if (navigator.onLine) window.location.reload();
      }, 3500);
    });
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // 1. INTERCEPTORES DE FORMULARIOS (ingreso y gasto)
  // ═══════════════════════════════════════════════════════════════════════════

  function _instalarInterceptor(modalId, tipoOperacion, tipoLabel) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const form = modal.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      // Online real → dejar pasar al servidor. navigator.onLine solo detecta
      // la interfaz de red: si el wifi sigue arriba pero no hay internet,
      // PWA.isOnline() (ping real) es quien detecta el corte.
      if (navigator.onLine && window.PWA?.isOnline?.() !== false) return;

      e.preventDefault();
      e.stopPropagation();

      // `isOnline()` es una foto que pudo quedarse en false tras un microcorte
      // (el navegador no dispara 'online' si el wifi nunca se cayo). Con dinero
      // de por medio, medir de verdad antes de rechazar el cobro.
      Promise.resolve(window.PWA?.hayConexionAhora?.()).then(hayRed => {
        if (hayRed === true) {
          form.submit(); // nativo: no re-dispara este handler
          return;
        }
        _encolarMovimientoOffline(form, modalId, tipoOperacion, tipoLabel);
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 2. ENCOLAR MOVIMIENTO OFFLINE (con candados)
  // ═══════════════════════════════════════════════════════════════════════════

  async function _encolarMovimientoOffline(form, modalId, tipoOperacion, tipoLabel) {
    // Captura offline apagada (/api/sync cerrado): un movimiento de caja
    // encolado aqui NUNCA llegaria al corte. Es dinero: se avisa y no se
    // finge que quedo guardado.
    if (window.OfflineData?.escriturasHabilitadas?.() !== true) {
      _avisar(
        'warning',
        'Sin conexión',
        'No se puede registrar el movimiento ahora. Anótalo y captúralo en cuanto regrese el internet: <strong>no quedó guardado</strong>.'
      );
      return;
    }

    if (!window.OfflineData) {
      _avisar('error', 'Offline no disponible', 'No se pudo abrir el almacenamiento local.');
      return;
    }

    // Candado: si ya hay un pre-corte encolado, el efectivo ya fue contado.
    // Registrar más movimientos después del conteo descuadraría el corte.
    if (await _hayPreCortePendiente()) {
      _avisar(
        'warning',
        'Cierre de caja pendiente',
        'Ya capturaste el cierre de este corte sin internet. No se pueden registrar ' +
        'más movimientos hasta que sincronice y se abra un corte nuevo.'
      );
      return;
    }

    // Candado 1: debe existir un corte abierto en el caché offline.
    let corte = null;
    try {
      const snapshot = await window.OfflineData.obtenerCajaSnapshot();
      corte = snapshot?.corte || null;
    } catch (_) {}

    if (!corte || corte.estado !== 'abierto' || !corte.id) {
      _avisar(
        'warning',
        'No hay corte abierto en este equipo',
        'No hay un corte de caja abierto en los datos offline de este equipo. ' +
        'Este movimiento NO quedó guardado — regístralo cuando vuelva la conexión.'
      );
      return;
    }

    // Recolectar datos del formulario
    const data = new FormData(form);
    const monto = parseFloat(String(data.get('monto') || '').replace(/,/g, ''));
    const metodo = String(data.get('metodo_pago') || '').trim();
    const descripcion = String(data.get('descripcion') || '').trim();
    const categoriaId = String(data.get('categoria_id') || '').trim();
    const categoriaNombre = _textoOpcionSeleccionada(form, 'categoria_id');

    if (!isFinite(monto) || monto <= 0 || !metodo || !descripcion || !categoriaId) {
      _avisar('warning', 'Datos incompletos', 'Completa categoría, descripción, monto y método de pago.');
      return;
    }

    const payload = {
      monto,
      metodo_pago: metodo,
      descripcion,
      categoria_id: Number(categoriaId),
      categoria: categoriaNombre || undefined,
      referencia: String(data.get('referencia') || '').trim() || undefined,
      comprobante: String(data.get('comprobante') || '').trim() || undefined,
      proveedor: String(data.get('proveedor') || '').trim() || undefined,
      // Candado 2: el servidor rechaza el movimiento si el corte cambió
      corte_id_capturado: Number(corte.id),
      capturado_offline_at: new Date().toISOString(),
    };

    const etiqueta = tipoLabel === 'ingreso'
      ? `Ingreso $${monto.toFixed(2)} (${metodo})`
      : `Gasto $${monto.toFixed(2)} (${metodo})`;

    try {
      await window.OfflineData.encolarOperacion(tipoOperacion, payload, etiqueta);
    } catch (err) {
      console.error('[CajaOffline] No se pudo encolar el movimiento:', err);
      _avisar('error', 'No se pudo guardar', 'El movimiento NO quedó guardado. Intenta de nuevo o espera la conexión.');
      return;
    }

    // Cerrar modal y limpiar el formulario
    form.reset();
    if (typeof window.cerrarModalCaja === 'function') {
      window.cerrarModalCaja(modalId);
    } else {
      document.getElementById(modalId)?.classList.add('hidden');
    }

    _actualizarPanelPendientes(
      { descripcion, metodo_pago: metodo, categoria: categoriaNombre || 'Sin categoría', monto },
      tipoLabel
    );

    _avisar(
      'success',
      tipoLabel === 'ingreso' ? 'Ingreso guardado offline' : 'Gasto guardado offline',
      'Quedó guardado en este equipo dentro del corte actual y se enviará a Caja al volver internet. ' +
      'Si el corte se cierra antes de sincronizar, se te avisará para registrarlo a mano.'
    );
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 2b. PRE-CORTE DE CAJA OFFLINE (fase 4)
  //     Offline, el botón "Hacer Corte" abre este flujo: se calcula el efectivo
  //     esperado con los datos locales (snapshot + movimientos encolados), se
  //     captura el efectivo contado y se encola. Al volver internet el servidor
  //     recalcula con TODOS los movimientos y cierra el corte de verdad.
  // ═══════════════════════════════════════════════════════════════════════════

  async function _hayPreCortePendiente() {
    try {
      const pendientes = await window.OfflineData.obtenerPendientes();
      return pendientes.some(o => o.tipo === 'pre_corte_caja');
    } catch (_) { return false; }
  }

  function _instalarInterceptorCorte() {
    document.addEventListener('click', function (e) {
      const link = e.target.closest('a[href], button');
      if (!link) return;

      // Solo el botón "Hacer Corte" (la página caja/corte exacta, no caja/corte/123)
      const href = link.getAttribute('href') || '';
      let esBotonCorte = false;
      try {
        esBotonCorte = href && new URL(href, location.origin).pathname.replace(/\/$/, '').endsWith('/caja/corte');
      } catch (_) {}
      if (!esBotonCorte) return;

      // Online real → flujo normal (página de corte)
      if (navigator.onLine && window.PWA?.isOnline?.() !== false) return;

      e.preventDefault();
      e.stopPropagation();

      // La foto pudo quedarse en false tras un microcorte: medir de verdad
      // antes de mandar al pre-corte offline en vez de al corte real.
      Promise.resolve(window.PWA?.hayConexionAhora?.()).then(hayRed => {
        if (hayRed === true) {
          window.location.href = href;
          return;
        }
        _preCorteOffline();
      });
    }, true);
  }

  async function _preCorteOffline() {
    if (!window.OfflineData?.obtenerCajaSnapshot) {
      _avisar('error', 'Offline no disponible', 'No se pudo abrir el almacenamiento local.');
      return;
    }

    if (await _hayPreCortePendiente()) {
      _avisar('warning', 'Cierre ya capturado', 'El cierre de este corte ya está esperando internet para aplicarse.');
      return;
    }

    const snapshot = await window.OfflineData.obtenerCajaSnapshot().catch(() => null);
    const corte = snapshot?.corte;
    if (!corte || corte.estado !== 'abierto' || !corte.id) {
      _avisar('warning', 'No hay corte abierto en este equipo', 'No hay un corte de caja abierto en la copia de este equipo. Registra el movimiento a mano y captúralo al volver internet.');
      return;
    }

    // Efectivo esperado local = lo del último snapshot + lo encolado en este equipo
    const base = Number(snapshot?.resumen?.efectivo_en_caja ?? 0);
    let pendienteEfectivo = 0;
    try {
      const pendientes = await window.OfflineData.obtenerPendientes();
      for (const op of pendientes) {
        if (op.payload?.metodo_pago !== 'efectivo') continue;
        if (op.tipo === 'pago_caja') pendienteEfectivo += Number(op.payload.monto || 0);
        if (op.tipo === 'gasto_caja') pendienteEfectivo -= Number(op.payload.monto || 0);
      }
    } catch (_) {}
    const esperadoLocal = base + pendienteEfectivo;

    if (!window.Swal) {
      _avisar('error', 'No disponible', 'El cierre offline necesita la interfaz completa. Intenta desde la pantalla de Caja.');
      return;
    }

    const fmt = n => '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2 });
    const { value: valores, isConfirmed } = await Swal.fire({
      icon: 'warning',
      title: 'Cierre de caja sin internet',
      html: `
        <div style="text-align:left;font-size:.92rem;">
          <p style="background:#FEF3C7;color:#92400E;border-radius:8px;padding:8px 10px;margin-bottom:12px;">
            <strong>Se cerrará con datos de este equipo.</strong> Si otra recepción registró
            movimientos, el esperado real puede cambiar: el sistema lo recalculará y te avisará al volver internet.
          </p>
          <p style="margin-bottom:4px;">Corte <strong>#${corte.id}</strong></p>
          <p style="margin-bottom:10px;">Efectivo esperado según este equipo: <strong>${fmt(esperadoLocal)}</strong>
            ${pendienteEfectivo !== 0 ? `<br><span style="font-size:.8rem;color:#92400E;">(incluye ${fmt(pendienteEfectivo)} de movimientos offline sin sincronizar)</span>` : ''}
          </p>
          <label style="display:block;font-weight:600;margin-bottom:4px;">Efectivo contado</label>
          <input id="precorte-contado" type="number" step="0.01" min="0" class="swal2-input" style="margin:0 0 10px;width:100%;" placeholder="0.00">
          <label style="display:block;font-weight:600;margin-bottom:4px;">Observaciones (opcional)</label>
          <textarea id="precorte-obs" class="swal2-textarea" style="margin:0;width:100%;" rows="2"></textarea>
        </div>`,
      showCancelButton: true,
      confirmButtonText: 'Capturar cierre',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#B45309',
      reverseButtons: true,
      focusConfirm: false,
      preConfirm: () => {
        const contado = parseFloat(document.getElementById('precorte-contado').value);
        if (!isFinite(contado) || contado < 0) {
          Swal.showValidationMessage('Escribe el efectivo contado (puede ser 0).');
          return false;
        }
        return { contado, obs: document.getElementById('precorte-obs').value.trim() };
      },
    });

    if (!isConfirmed || !valores) return;

    try {
      await window.OfflineData.encolarOperacion('pre_corte_caja', {
        corte_id_capturado: Number(corte.id),
        efectivo_contado: valores.contado,
        efectivo_esperado_local: esperadoLocal,
        observaciones: valores.obs || undefined,
        capturado_offline_at: new Date().toISOString(),
      }, `Cierre de caja corte #${corte.id} — contado ${fmt(valores.contado)}`);
    } catch (err) {
      console.error('[CajaOffline] No se pudo encolar el pre-corte:', err);
      _avisar('error', 'No se pudo capturar', 'El cierre NO quedó guardado. Intenta de nuevo.');
      return;
    }

    _actualizarPanelPendientes(
      { descripcion: `Cierre corte #${corte.id}`, metodo_pago: 'efectivo', categoria: 'Pre-corte', monto: valores.contado },
      'gasto'
    );

    const diferenciaLocal = valores.contado - esperadoLocal;
    _avisar(
      'success',
      'Cierre capturado en este equipo',
      `Contado ${fmt(valores.contado)} vs esperado local ${fmt(esperadoLocal)} ` +
      `(diferencia ${fmt(diferenciaLocal)}). El corte se cerrará al volver internet con TODOS los movimientos ` +
      'y se te avisará si el esperado real cambió. Ya no registres movimientos en este corte.'
    );
  }

  function _textoOpcionSeleccionada(form, nombreSelect) {
    const select = form.querySelector(`select[name="${nombreSelect}"]`);
    const opcion = select?.selectedOptions?.[0];
    return (opcion?.textContent || '').trim();
  }

  function _avisar(icon, titulo, texto) {
    if (window.Swal) {
      Swal.fire({
        icon,
        title: titulo,
        text: texto,
        confirmButtonText: 'Entendido',
        confirmButtonColor: icon === 'success' ? '#4A6340' : '#B45309',
      });
    } else {
      window.PWA?.showToast(`${titulo}: ${texto}`, icon === 'success' ? 'success' : 'error', 8000);
    }
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 3. UI: LISTA DE MOVIMIENTOS PENDIENTES OFFLINE
  // ═══════════════════════════════════════════════════════════════════════════

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
        <div id="caja-offline-list" style="overflow-y:auto;max-height:240px;padding:6px 8px;overscroll-behavior:contain;"></div>
        <div style="padding:6px 10px;border-top:1px solid #fde68a;background:#fffbeb;
                    font-size:0.75em;color:#92400e;text-align:center;">
          Se enviarán a Caja automáticamente al volver internet
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
            <div style="font-weight:600;color:#374151;">${_esc(_truncar(datosNuevo.descripcion, 22))}</div>
            <div style="color:#6B7280;font-size:0.78em;">${_esc(datosNuevo.metodo_pago)} · ${_esc(datosNuevo.categoria)}</div>
          </div>
          <div style="font-weight:700;color:${colorMonto};white-space:nowrap;">
            ${simbolo}$${Number(datosNuevo.monto).toFixed(2)}
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
  // 4. BANNER DE MODO OFFLINE EN LA VISTA DE CAJA
  // ═══════════════════════════════════════════════════════════════════════════

  function _actualizarBannerOffline() {
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
              Los ingresos, gastos y el cierre de corte que registres se guardan en este equipo
              y se aplican al volver internet. La apertura de un corte nuevo sí requiere conexión.
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

  /**
   * Offline solo se bloquea la APERTURA de corte (crear un corte requiere
   * servidor). El cierre ya NO se bloquea: el botón "Hacer Corte" abre el
   * flujo de pre-corte offline (ver _instalarInterceptorCorte).
   */
  function _deshabilitarBotonesRiesgosos() {
    document.querySelectorAll('[href*="caja/apertura"], [href*="caja/abrir"]').forEach(btn => {
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
          _actualizarPanelPendientes({
            descripcion: op.payload?.descripcion || op.label || 'Movimiento',
            metodo_pago: op.payload?.metodo_pago || '-',
            categoria: op.payload?.categoria || 'Sin categoría',
            monto: Number(op.payload?.monto || 0),
          }, tipo);
        });
      }
    } catch (_) {}
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 5. HELPERS
  // ═══════════════════════════════════════════════════════════════════════════

  function _truncar(texto, max) {
    return texto.length > max ? texto.substring(0, max) + '…' : texto;
  }

  function _esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

})();
