/**
 * buscador-global.js — Los Cedros
 * Buscador global en tiempo real para el sidebar.
 * Busca huéspedes, reservaciones y habitaciones.
 */
(function () {
  'use strict';

  const BASE    = window.BASE_URL ? window.BASE_URL.replace(/\/$/, '') : '';
  const DELAY   = 280;   // ms de debounce
  const MIN_LEN = 2;     // mínimo de caracteres

  let _timer   = null;
  let _ultimo  = '';
  let _abierto = false;
  let _indiceSolicitado = false;

  // ── Elementos del DOM ──────────────────────────────────────────────────────
  const input     = document.getElementById('buscador-global-input');
  const dropdown  = document.getElementById('buscador-global-dropdown');
  const clearBtn  = document.getElementById('buscador-global-clear');
  const searchBox = input?.closest('.search-box');

  if (!input || !dropdown) return; // el sidebar no está en esta página

  input.disabled = false;
  input.removeAttribute('readonly');
  input.style.pointerEvents = 'auto';

  if (searchBox) {
    searchBox.style.pointerEvents = 'auto';
    searchBox.addEventListener('click', () => input.focus());
  }

  input.addEventListener('click', event => event.stopPropagation());

  // ── Eventos ────────────────────────────────────────────────────────────────
  input.addEventListener('input', () => {
    const q = input.value.trim();

    // Mostrar/ocultar botón de limpiar
    if (clearBtn) clearBtn.style.display = q ? 'flex' : 'none';

    if (q === _ultimo) return;
    _ultimo = q;

    clearTimeout(_timer);

    const qId = q.replace(/^#\s*/, '').trim();
    const esBusquedaId = /^\d+$/.test(qId);

    if (q.length < MIN_LEN && !esBusquedaId) {
      _cerrar();
      return;
    }

    _mostrarCargando();
    _timer = setTimeout(() => _buscar(q), DELAY);
  });

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { _cerrar(); input.blur(); }
    if (e.key === 'Enter' && _abierto) {
      const primero = dropdown.querySelector('.bg-item');
      if (primero) primero.click();
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      _moverFoco(1);
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      _moverFoco(-1);
    }
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      input.value = '';
      _ultimo = '';
      clearBtn.style.display = 'none';
      _cerrar();
      input.focus();
    });
  }

  // Cerrar al hacer clic fuera
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      _cerrar();
    }
  });

  // ── Búsqueda ───────────────────────────────────────────────────────────────
  setTimeout(() => {
    if (navigator.onLine && window.OfflineData?.capturarIndiceGlobal) {
      window.OfflineData.capturarIndiceGlobal().catch(() => {});
    }
  }, 1500);

  async function _buscar(q) {
    const sinConexion = !navigator.onLine || window.PWA?.isOnline?.() === false;

    if (sinConexion && window.OfflineData?.buscarGlobal) {
      _buscarOffline(q);
      return;
    }

    try {
      const res  = await fetch(`${BASE}/api/buscar?q=${encodeURIComponent(q)}`, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json();

      if (!data.success) { _mostrarError(); return; }
      if (!_indiceSolicitado && Array.isArray(data.resultados) && window.OfflineData?.capturarIndiceGlobal) {
        _indiceSolicitado = true;
        window.OfflineData.capturarIndiceGlobal().catch(() => {});
      }
      _renderizar(data.resultados, q);
    } catch (_) {
      _buscarOffline(q);
    }
  }

  async function _buscarOffline(q) {
    if (!window.OfflineData?.buscarGlobal) {
      _mostrarError();
      return;
    }

    try {
      const resultados = await window.OfflineData.buscarGlobal(q);
      _renderizar(resultados, q);
    } catch (_) {
      _mostrarError();
    }
  }

  // ── Pantallas (catálogo local del sidebar, filtrado por módulos/permisos) ──
  function _normalizarTexto(s) {
    return String(s ?? '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  }

  function _filtrarPantallas(q) {
    const cat = window.MS_NAV_PANTALLAS;
    if (!Array.isArray(cat) || !q) return [];
    const nq = _normalizarTexto(q);
    return cat
      .filter(p => _normalizarTexto(`${p.etiqueta} ${p.buscar || ''}`).includes(nq))
      .slice(0, 5);
  }

  // Permite iconos con prefijo de estilo (p.ej. "fab fa-whatsapp"); si viene
  // suelto ("fa-user") se asume solido. Asi el buscador respeta iconos de marca.
  function _iconClass(ic) {
    ic = String(ic || '').trim();
    return ic.indexOf(' ') !== -1 ? ic : ('fas ' + ic);
  }

  function _htmlPantallas(pantallas, q) {
    if (!pantallas.length) return '';
    let html = `<div style="padding:6px 14px 2px;font-size:.68rem;font-weight:700;color:#9CA3AF;letter-spacing:.07em;text-transform:uppercase;">Pantallas</div>`;
    pantallas.forEach(p => {
      html += `
        <a href="${BASE}/${_esc(p.ruta)}"
           class="bg-item"
           style="display:flex;align-items:center;gap:10px;padding:9px 14px;text-decoration:none;
                  transition:background .12s;cursor:pointer;border-radius:0;"
           onmouseover="this.style.background='#F3F4F6'"
           onmouseout="this.style.background='transparent'">
          <span style="width:30px;height:30px;border-radius:8px;flex-shrink:0;display:flex;
                       align-items:center;justify-content:center;background:#6366F118;">
            <i class="${_iconClass(_esc(p.icono))}" style="font-size:.75rem;color:#6366F1;"></i>
          </span>
          <span style="min-width:0;overflow:hidden;">
            <span style="display:block;font-size:.83rem;font-weight:600;color:#1F2937;
                         white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              ${_resaltar(_esc(p.etiqueta), q)}${p.favorito ? ' <i class="fas fa-star" style="font-size:.6rem;color:#D4AF37;"></i>' : ''}
            </span>
            <span style="display:block;font-size:.73rem;color:#6B7280;">Ir a la pantalla</span>
          </span>
        </a>`;
    });
    return html;
  }

  // ── Render ─────────────────────────────────────────────────────────────────
  function _renderizar(resultados, q) {
    const pantallasHtml = _htmlPantallas(_filtrarPantallas(q), q);

    if (resultados.length === 0) {
      dropdown.innerHTML = pantallasHtml || `
        <div style="padding:20px;text-align:center;color:#9CA3AF;">
          <i class="fas fa-search" style="font-size:1.4rem;opacity:.4;display:block;margin-bottom:8px;"></i>
          <span style="font-size:.83rem;">Sin resultados para "<strong>${_esc(q)}</strong>"</span>
        </div>`;
      _abrir();
      return;
    }

    // Agrupar por tipo
    const grupos = { huesped: [], reservacion: [], habitacion: [] };
    resultados.forEach(r => grupos[r.tipo]?.push(r));

    const etiquetas = {
      huesped:     { label: 'Huéspedes',     icono: 'fa-user' },
      reservacion: { label: 'Reservaciones', icono: 'fa-calendar-check' },
      habitacion:  { label: 'Habitaciones',  icono: 'fa-bed' },
    };

    let html = '';
    for (const [tipo, items] of Object.entries(grupos)) {
      if (!items.length) continue;
      const { label } = etiquetas[tipo];
      html += `<div style="padding:6px 14px 2px;font-size:.68rem;font-weight:700;color:#9CA3AF;letter-spacing:.07em;text-transform:uppercase;">${label}</div>`;
      items.forEach(item => {
        html += `
          <a href="${BASE}/${item.url}"
             class="bg-item"
             style="display:flex;align-items:center;gap:10px;padding:9px 14px;text-decoration:none;
                    transition:background .12s;cursor:pointer;border-radius:0;"
             onmouseover="this.style.background='#F3F4F6'"
             onmouseout="this.style.background='transparent'">
            <span style="width:30px;height:30px;border-radius:8px;flex-shrink:0;display:flex;
                         align-items:center;justify-content:center;background:${item.color}18;">
              <i class="fas ${item.icono}" style="font-size:.75rem;color:${item.color};"></i>
            </span>
            <span style="min-width:0;overflow:hidden;">
              <span style="display:block;font-size:.83rem;font-weight:600;color:#1F2937;
                           white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                ${_resaltar(_esc(item.titulo), q)}
              </span>
              <span style="display:block;font-size:.73rem;color:#6B7280;
                           white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                ${_esc(item.subtitulo)}
              </span>
            </span>
          </a>`;
      });
    }

    dropdown.innerHTML = html;
    _abrir();
  }

  function _mostrarCargando() {
    dropdown.innerHTML = `
      <div style="padding:18px;text-align:center;color:#9CA3AF;">
        <i class="fas fa-circle-notch fa-spin" style="font-size:1.1rem;display:block;margin-bottom:6px;"></i>
        <span style="font-size:.8rem;">Buscando...</span>
      </div>`;
    _abrir();
  }

  function _mostrarError() {
    dropdown.innerHTML = `
      <div style="padding:16px;text-align:center;color:#EF4444;font-size:.8rem;">
        <i class="fas fa-exclamation-circle" style="display:block;margin-bottom:4px;"></i>
        Error al buscar. Intenta de nuevo.
      </div>`;
    _abrir();
  }

  // ── Helpers ────────────────────────────────────────────────────────────────
  function _abrir() {
    dropdown.style.display = 'block';
    _abierto = true;
  }

  function _cerrar() {
    dropdown.style.display = 'none';
    _abierto = false;
  }

  function _moverFoco(dir) {
    const items = [...dropdown.querySelectorAll('.bg-item')];
    if (!items.length) return;
    const activo = dropdown.querySelector('.bg-item:focus');
    const idx    = activo ? items.indexOf(activo) : -1;
    const next   = items[Math.max(0, Math.min(items.length - 1, idx + dir))];
    next?.focus();
  }

  function _resaltar(texto, q) {
    const re = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return texto.replace(re, '<mark style="background:#FEF08A;border-radius:2px;padding:0 1px;">$1</mark>');
  }

  function _esc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

})();
