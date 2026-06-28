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

    const copy = toast.querySelector('.ms-mobile-file-toast__copy');
    if (copy) {
      copy.innerHTML = blocked
        ? `<strong>Abriendo ${label} aqui</strong><span>Si el visor ocupa la pantalla, usa Atras para volver al sistema.</span>`
        : `<strong>${label} abierto aparte</strong><span>El sistema queda en esta pestana. Cambia de pestana o vuelve a la app cuando termines.</span>`;
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

  helper.open = openFile;
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
