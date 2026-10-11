(function () {
  'use strict';

  const helper = window.MedisoftMobileFiles || {};
  const BASE = (window.BASE_URL || document.querySelector('meta[name="base-url"]')?.content || '').replace(/\/$/, '');
  const toastId = 'medisoftMobileFileToast';
  const handledAttr = 'data-mobile-file-return-bound';

  const urlPatterns = [
    /\/caja\/descargar-pdf(?:\/|$)/i,
    /\/caja\/exportar(?:\?|$)/i,
    /\/reservaciones\/exportar-pdf(?:\?|$)/i,
    /\/reservaciones\/cotizacion(?:-[a-z]+)?-pdf(?:\?|$)/i,
    /\/reportes\/exportar-pdf(?:\?|$)/i,
    /\/reportes\/links\/\d+\/descargar(?:\?|$)/i,
    /\/trabajadores\/\d+\/recibo-laboral\/pdf(?:\?|$)/i,
    /formato=pdf/i,
    /\.pdf(?:\?|$)/i
  ];

  const textPatterns = [
    /\bpdf\b/i,
    /ticket/i,
    /cotizaci[o\u00f3]n/i,
    /imprimir/i,
    /descargar/i
  ];

  function isMobileLike() {
    return window.matchMedia('(max-width: 820px)').matches ||
      window.matchMedia('(pointer: coarse)').matches ||
      (window.matchMedia('(display-mode: standalone)').matches && window.innerWidth <= 1024);
  }

  function absoluteUrl(href) {
    try {
      return new URL(href, window.location.href);
    } catch (error) {
      return null;
    }
  }

  function isSameAppUrl(url) {
    if (!url || url.origin !== window.location.origin) {
      return false;
    }

    if (!BASE) {
      return true;
    }

    try {
      const baseUrl = new URL(BASE, window.location.href);
      return url.pathname.indexOf(baseUrl.pathname.replace(/\/$/, '')) === 0 || baseUrl.pathname === '/';
    } catch (error) {
      return true;
    }
  }

  function elementText(element) {
    if (!element) {
      return '';
    }

    return [
      element.getAttribute('aria-label') || '',
      element.getAttribute('title') || '',
      element.textContent || '',
      element.className || ''
    ].join(' ');
  }

  function shouldHandle(url, element) {
    if (!url || !isSameAppUrl(url) || element?.closest?.('[data-mobile-file-ignore]')) {
      return false;
    }

    const href = url.pathname + url.search;
    if (urlPatterns.some(pattern => pattern.test(href))) {
      return true;
    }

    const text = elementText(element);
    const hasFileIcon = Boolean(element?.querySelector?.('.fa-file-pdf, .fa-print, .fa-download'));
    return hasFileIcon && textPatterns.some(pattern => pattern.test(text));
  }

  function labelFromElement(element, fallback) {
    const text = (elementText(element) || '').replace(/\s+/g, ' ').trim();
    if (/ticket/i.test(text)) return 'ticket';
    if (/cotizaci/i.test(text)) return 'cotizacion PDF';
    if (/recibo/i.test(text)) return 'recibo PDF';
    if (/corte/i.test(text)) return 'PDF del corte';
    if (/pdf/i.test(text)) return 'PDF';
    return fallback || 'archivo';
  }

  function injectStyles() {
    if (document.getElementById('medisoftMobileFileStyles')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'medisoftMobileFileStyles';
    style.textContent = `
      .ms-mobile-file-toast {
        position: fixed;
        left: 14px;
        right: 14px;
        bottom: max(16px, env(safe-area-inset-bottom));
        z-index: 20000;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 13px;
        border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 14%, #D9E0E8);
        border-radius: 14px;
        background: rgba(255, 255, 255, .96);
        color: color-mix(in srgb, var(--brand-primary, #1B2746) 58%, #64748B);
        box-shadow: 0 18px 42px rgba(15, 23, 42, .18);
        font-family: inherit;
        transform: translateY(16px);
        opacity: 0;
        pointer-events: none;
        transition: opacity .18s ease, transform .18s ease;
      }

      .ms-mobile-file-toast.is-visible {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
      }

      .ms-mobile-file-toast__icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        background: color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #EEF4FF);
        color: var(--brand-primary, #1B2746);
      }

      .ms-mobile-file-toast__copy {
        min-width: 0;
        flex: 1;
        font-size: .78rem;
        line-height: 1.35;
      }

      .ms-mobile-file-toast__copy strong {
        display: block;
        color: color-mix(in srgb, var(--brand-primary, #1B2746) 70%, #475569);
        font-size: .82rem;
      }

      .ms-mobile-file-toast__close {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 10px;
        background: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, #F8FAFC);
        color: #64748B;
        cursor: pointer;
      }

      @media (min-width: 821px) {
        .ms-mobile-file-toast { max-width: 390px; left: auto; }
      }

      .is-ms-downloading {
        opacity: .6;
        cursor: progress !important;
        pointer-events: none;
      }
    `;
    document.head.appendChild(style);
  }

  function showHint(label, blocked) {
    if (!document.body || !isMobileLike()) {
      return;
    }

    injectStyles();

    let toast = document.getElementById(toastId);
    if (!toast) {
      toast = document.createElement('div');
      toast.id = toastId;
      toast.className = 'ms-mobile-file-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      toast.innerHTML = `
        <span class="ms-mobile-file-toast__icon"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
        <span class="ms-mobile-file-toast__copy"></span>
        <button type="button" class="ms-mobile-file-toast__close" aria-label="Cerrar aviso"><i class="fas fa-times" aria-hidden="true"></i></button>
      `;
      toast.querySelector('button')?.addEventListener('click', () => toast.classList.remove('is-visible'));
      document.body.appendChild(toast);
    }

    const icono = toast.querySelector('.ms-mobile-file-toast__icon');
    if (icono) {
      icono.innerHTML = '<i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>';
    }
    const copy = toast.querySelector('.ms-mobile-file-toast__copy');
    if (copy) {
      copy.innerHTML = blocked
        ? `<strong>Abriendo ${label} aquí</strong><span>Si el visor ocupa la pantalla, usa Atrás para volver al sistema.</span>`
        : `<strong>${label} abierto aparte</strong><span>El sistema queda en esta pestaña. Cambia de pestaña o vuelve a la app cuando termines.</span>`;
    }

    window.clearTimeout(toast._hideTimer);
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    toast._hideTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 7200);
  }

  function openFile(href, options) {
    const url = absoluteUrl(href);
    const label = options?.label || 'archivo';
    if (!url) {
      return null;
    }

    // Un adjunto abierto "aparte" deja una pestaña que se cierra sola y ninguna
    // señal: se descarga aquí con aviso. Respaldo = abrirlo como antes.
    const endpoint = downloadEndpoint(url);
    if (endpoint) {
      startDownload(url.href, {
        label: options?.downloadLabel || endpoint.label,
        trigger: options?.trigger || null,
        fallback: () => openFileDirect(url, label, options)
      });
      return true;
    }

    return openFileDirect(url, label, options);
  }

  function openFileDirect(url, label, options) {
    try {
      window.sessionStorage.setItem('medisoft:last-system-url', window.location.href);
    } catch (error) {}

    const mobile = options?.forceMobile === true || isMobileLike();
    const opened = window.open(url.href, '_blank');

    if (opened) {
      try { opened.opener = null; } catch (error) {}
      if (mobile) {
        showHint(label, false);
      }
      return opened;
    }

    if (mobile) {
      showHint(label, true);
    }
    window.location.href = url.href;
    return null;
  }

  function formUrl(form) {
    const action = absoluteUrl(form.getAttribute('action') || window.location.href);
    if (!action) {
      return null;
    }

    const params = new URLSearchParams(action.search);
    const data = new FormData(form);
    data.forEach((value, key) => {
      if (typeof value === 'string') {
        params.set(key, value);
      }
    });
    action.search = params.toString();
    return action;
  }

  function bindExistingElements() {
    document.querySelectorAll('a[href]').forEach(link => {
      if (link.hasAttribute(handledAttr)) {
        return;
      }

      const url = absoluteUrl(link.getAttribute('href'));
      if (!shouldHandle(url, link)) {
        return;
      }

      link.setAttribute(handledAttr, '1');
      link.setAttribute('target', '_blank');
      link.setAttribute('rel', 'noopener');
    });

    document.querySelectorAll('form[action]').forEach(form => {
      const url = absoluteUrl(form.getAttribute('action'));
      if (shouldHandle(url, form) && isMobileLike()) {
        form.setAttribute('target', '_blank');
      }
    });
  }

  /*
   * Endpoints que responden ADJUNTO (Content-Disposition: attachment). Navegar
   * hacia ellos —enlace, window.open, form, iframe oculto— no deja NINGUNA señal
   * en pantalla y el hotelero lo reporta como "no se descarga" (oct-10). Todo lo
   * que apunte aquí se descarga con download(): aviso "Preparando…" → "descargado".
   * Los PDF que se ABREN en visor (cotizaciones, reporte del día) no van aquí.
   * El orden importa: la primera coincidencia gana.
   */
  const downloadEndpoints = [
    { pattern: /\/caja\/descargar-pdf\/\d+(?:[?#]|$)/i, label: 'PDF del corte' },
    { pattern: /\/caja\/exportar\?(?:.*&)?formato=pdf(?:[&#]|$)/i, label: 'PDF de movimientos' },
    { pattern: /\/caja\/exportar(?:[?#]|$)/i, label: 'Excel' },
    { pattern: /\/inventario\/generarPdfMovimientos(?:[?#]|$)/i, label: 'PDF de inventario' },
    { pattern: /\/nomina\/recibos\/\d+\/pdf(?:[?#]|$)/i, label: 'Recibo PDF' },
    { pattern: /\/trabajadores\/\d+\/recibo-laboral\/pdf(?:[?#]|$)/i, label: 'Recibo PDF' },
    { pattern: /\/reportes\/gerencial-diario\/pdf(?:[?#]|$)/i, label: 'PDF del reporte' },
    { pattern: /\/reportes\/exportar-pdf(?:[?#]|$)/i, label: 'PDF del reporte' },
    { pattern: /\/reservaciones\/exportar-excel(?:[?#]|$)/i, label: 'Excel' },
    { pattern: /\/documentos\/\d+\/descargar(?:[?#]|$)/i, label: 'Documento' }
  ];

  function downloadEndpoint(url) {
    if (!url || !isSameAppUrl(url)) {
      return null;
    }
    const href = url.pathname + url.search;
    return downloadEndpoints.find(endpoint => endpoint.pattern.test(href)) || null;
  }

  // Enlaces a un adjunto, en CUALQUIER tamaño de pantalla. Va antes que el
  // interceptor móvil de abajo, que respeta defaultPrevented.
  document.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!link || link.closest('[data-mobile-file-ignore]')) {
      return;
    }

    const url = absoluteUrl(link.getAttribute('href'));
    const endpoint = downloadEndpoint(url);
    if (!endpoint) {
      return;
    }

    event.preventDefault();
    if (link.getAttribute('aria-busy') === 'true') {
      return;
    }
    startDownload(url.href, {
      label: endpoint.label,
      trigger: link,
      fallback: () => { window.location.href = url.href; }
    });
  }, true);

  // Formularios (GET o POST) cuyo action es un adjunto.
  document.addEventListener('submit', event => {
    if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) {
      return;
    }

    const form = event.target;
    if (form.closest('[data-mobile-file-ignore]')) {
      return;
    }

    const action = absoluteUrl(form.getAttribute('action') || window.location.href);
    const endpoint = downloadEndpoint(action);
    if (!endpoint) {
      return;
    }

    event.preventDefault();
    const trigger = event.submitter || form.querySelector('[type="submit"]');
    if (trigger && trigger.getAttribute('aria-busy') === 'true') {
      return;
    }

    const method = (form.getAttribute('method') || 'GET').toUpperCase();
    if (method === 'GET') {
      const url = formUrl(form);
      if (!url) {
        return;
      }
      startDownload(url.href, {
        label: endpoint.label,
        trigger,
        fallback: () => { window.location.href = url.href; }
      });
      return;
    }

    startDownload(action.href, {
      label: endpoint.label,
      trigger,
      method: 'POST',
      body: new FormData(form),
      // submit() nativo no dispara 'submit': no se vuelve a interceptar.
      fallback: () => HTMLFormElement.prototype.submit.call(form)
    });
  }, true);

  document.addEventListener('click', event => {
    if (!isMobileLike() || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!link) {
      return;
    }

    const url = absoluteUrl(link.getAttribute('href'));
    if (!shouldHandle(url, link)) {
      return;
    }

    event.preventDefault();
    openFile(url.href, { label: labelFromElement(link, 'archivo') });
  }, true);

  document.addEventListener('submit', event => {
    if (!isMobileLike() || event.defaultPrevented || !(event.target instanceof HTMLFormElement)) {
      return;
    }

    const form = event.target;
    const action = absoluteUrl(form.getAttribute('action') || window.location.href);
    if (!shouldHandle(action, form)) {
      return;
    }

    const method = (form.getAttribute('method') || 'GET').toUpperCase();
    const label = labelFromElement(form, 'archivo');

    if (method === 'GET') {
      event.preventDefault();
      const url = formUrl(form);
      if (url) {
        openFile(url.href, { label });
      }
      return;
    }

    form.setAttribute('target', '_blank');
    showHint(label, false);
  }, true);

  /**
   * Descarga un archivo del sistema SIN salir de la pantalla y AVISA que bajó.
   * Navegar al export (pestaña nueva o location.href) dejaba al hotelero sin
   * ninguna señal —la pestaña se cerraba sola y la app instalada no muestra la
   * burbuja de descargas— y lo reportaba como "no se descarga" (oct-10).
   * Un fetch no es navegación: el SW lo manda directo a red, sin caché.
   * Opciones: label, fallbackName, method + body (POST con FormData).
   * Rechaza con Error('sesion') si la sesión venció o Error('servidor') si llegó HTML.
   */
  async function downloadFile(href, options) {
    const url = absoluteUrl(href);
    if (!url) {
      throw new Error('servidor');
    }

    const label = options?.label || 'Archivo';
    showToast('fa-spinner fa-spin', `Preparando ${label}…`, 'En un momento empieza la descarga.', 0);

    let resp;
    try {
      resp = await fetch(url.href, {
        method: options?.method || 'GET',
        body: options?.body,
        credentials: 'same-origin',
        cache: 'no-store'
      });
    } catch (error) {
      hideToast();
      throw new Error('red');
    }

    const tipo = resp.headers.get('content-type') || '';
    if (!resp.ok || resp.redirected || tipo.includes('text/html')) {
      hideToast();
      // Sesión vencida = redirige al login; error del export = redirige a la lista.
      throw new Error(resp.redirected && /\/login(?:[/?#]|$)/.test(resp.url) ? 'sesion' : 'servidor');
    }

    const archivo = await resp.blob();
    const disposicion = resp.headers.get('content-disposition') || '';
    const coincide = disposicion.match(/filename="?([^";]+)"?/i);
    const nombre = coincide ? coincide[1] : (options?.fallbackName || 'archivo');

    const enlace = document.createElement('a');
    const objeto = URL.createObjectURL(archivo);
    enlace.href = objeto;
    enlace.download = nombre;
    document.body.appendChild(enlace);
    enlace.click();
    enlace.remove();
    window.setTimeout(() => URL.revokeObjectURL(objeto), 30000);

    showToast('fa-circle-check', `${label} descargado`, `${nombre} · Lo encuentras en tu carpeta de Descargas.`, 9000);
    return nombre;
  }

  /**
   * download() + estado ocupado en el botón/enlace que lo disparó + respaldo: si
   * algo falla, se hace lo de siempre (navegar o enviar el form), que al menos
   * muestra el error del servidor o el login.
   */
  function startDownload(href, options) {
    const trigger = options?.trigger || null;
    setBusy(trigger, true);
    return downloadFile(href, options)
      .catch(error => {
        if (typeof options?.fallback === 'function') {
          options.fallback(error);
        }
        return null;
      })
      .finally(() => setBusy(trigger, false));
  }

  function setBusy(element, busy) {
    if (!element) {
      return;
    }
    injectStyles();
    element.classList.toggle('is-ms-downloading', busy);
    if (busy) {
      element.setAttribute('aria-busy', 'true');
    } else {
      element.removeAttribute('aria-busy');
    }
    if ('disabled' in element && element.tagName === 'BUTTON') {
      element.disabled = busy;
    }
  }

  function showToast(iconClass, title, detail, hideAfterMs) {
    if (!document.body) {
      return;
    }

    injectStyles();

    let toast = document.getElementById(toastId);
    if (!toast) {
      toast = document.createElement('div');
      toast.id = toastId;
      toast.className = 'ms-mobile-file-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      toast.innerHTML = `
        <span class="ms-mobile-file-toast__icon"></span>
        <span class="ms-mobile-file-toast__copy"></span>
        <button type="button" class="ms-mobile-file-toast__close" aria-label="Cerrar aviso"><i class="fas fa-times" aria-hidden="true"></i></button>
      `;
      toast.querySelector('button')?.addEventListener('click', () => toast.classList.remove('is-visible'));
      document.body.appendChild(toast);
    }

    const icono = toast.querySelector('.ms-mobile-file-toast__icon');
    if (icono) {
      icono.innerHTML = `<i class="fas ${iconClass}" aria-hidden="true"></i>`;
    }
    const copy = toast.querySelector('.ms-mobile-file-toast__copy');
    if (copy) {
      copy.innerHTML = '';
      const titulo = document.createElement('strong');
      titulo.textContent = title;
      const detalle = document.createElement('span');
      detalle.textContent = detail;
      copy.append(titulo, detalle);
    }

    window.clearTimeout(toast._hideTimer);
    // Reflow forzado en vez de requestAnimationFrame: arranca la transición aunque
    // el navegador esté limitando cuadros (pestaña recién enfocada tras la descarga).
    void toast.offsetWidth;
    toast.classList.add('is-visible');
    if (hideAfterMs > 0) {
      toast._hideTimer = window.setTimeout(() => toast.classList.remove('is-visible'), hideAfterMs);
    }
  }

  function hideToast() {
    const toast = document.getElementById(toastId);
    if (toast) {
      window.clearTimeout(toast._hideTimer);
      toast.classList.remove('is-visible');
    }
  }

  helper.open = openFile;
  helper.download = downloadFile;
  helper.isMobile = isMobileLike;
  helper.showHint = showHint;
  helper.bind = bindExistingElements;
  window.MedisoftMobileFiles = helper;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindExistingElements);
  } else {
    bindExistingElements();
  }
})();
