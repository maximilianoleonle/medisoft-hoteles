/**
 * instant-nav.js — Medisoft Hoteles
 * Navegación fluida sin cambiar la arquitectura (sigue siendo multipágina/PHP):
 *
 *   1) Regresar instantáneo (back-forward cache): el navegador restaura la
 *      pantalla anterior desde memoria, sin recargar desde cero. Se habilita
 *      al quitar el "Cache-Control: no-store" del HTML (ver .htaccess/index.php).
 *      Salvaguardas:
 *        · Caja y Habitaciones se recargan siempre (dinero/estados al día).
 *        · Tras cerrar sesión, "atrás" no muestra una pantalla autenticada
 *          (importante en equipos compartidos de recepción).
 *
 *   2) Precarga en intención: al pasar el cursor o tocar un destino de
 *      navegación, la siguiente pantalla se baja en segundo plano → el clic se
 *      siente instantáneo, y la página sigue siendo FRESCA (se bajó un instante
 *      antes, no es una copia vieja). Dos superficies:
 *        · Enlaces <a> del menú (sidebar + barra inferior + data-prefetch):
 *          Chromium/Edge usa Speculation Rules API (nativo, consciente del SW);
 *          otros navegadores caen a <link rel="prefetch"> en hover/touch.
 *        · Filas clicables [data-easy-href] (listas de reservaciones, huéspedes,
 *          etc., que navegan por JS): <link rel="prefetch"> en todos los
 *          navegadores — las Speculation Rules solo entienden <a> reales.
 *      Protecciones anti-desperdicio: retraso de intención (~80ms de hover real,
 *      no de paso), deduplicación por URL y tope de precargas por página.
 *      Solo destinos GET del mismo origen; nunca logout ni descargas.
 */
(function () {
  'use strict';

  var LOGOUT_FLAG = 'medisoft_logged_out';
  // Pantallas donde el dato es dinero/estado operativo y no debe verse "viejo".
  var FRESH_ALWAYS = /\/(caja|habitaciones)(\/|$|\?)/;
  // Enlaces <a> seguros para precargar (menú global + opt-in explícito).
  var NAV_SELECTOR = '#sidebar a[href], #hotel-bottom-nav a[href], a[data-prefetch]';
  var SKIP_SELECTOR = '[data-no-prefetch], [download], [target="_blank"], [rel~="external"]';
  var HOVER_DELAY_MS = 80;   // hover "con intención", no de paso sobre la lista
  var MAX_PREFETCHES = 40;   // tope por página: listas largas no saturan el servidor

  function sessionStore() {
    try { return window.sessionStorage; } catch (e) { return null; }
  }

  // ── 1. Back-forward cache (regresar instantáneo) ────────────────────────────

  // Marca "sesión cerrada" al enviar el formulario de logout (mismo id que pwa.js).
  document.addEventListener('submit', function (event) {
    if (event.target && event.target.id === 'logout-form') {
      var s = sessionStore();
      if (s) { try { s.setItem(LOGOUT_FLAG, '1'); } catch (e) {} }
    }
  }, true);

  // Al abrir una pantalla autenticada (existe el shell), limpia la marca.
  if (document.getElementById('sidebar')) {
    var s0 = sessionStore();
    if (s0) { try { s0.removeItem(LOGOUT_FLAG); } catch (e) {} }
  }

  window.addEventListener('pageshow', function (event) {
    // Solo actuamos cuando la página se RESTAURA desde el bfcache.
    if (!event.persisted) { return; }

    // Guardia de sesión: si se cerró sesión, no dejar ver la pantalla anterior.
    var s = sessionStore();
    if (s) {
      var flag = null;
      try { flag = s.getItem(LOGOUT_FLAG); } catch (e) {}
      if (flag === '1') { location.reload(); return; }
    }

    // Frescura forzada en pantallas de dinero/estado operativo.
    if (FRESH_ALWAYS.test(location.pathname)) { location.reload(); }
  });

  // ── 2. Precarga en intención ────────────────────────────────────────────────

  var yaPrecargados = Object.create(null);
  var totalPrecargas = 0;

  /** Valida y normaliza un href crudo → URL absoluta precargable, o null. */
  function normalizarUrl(href) {
    href = String(href || '').trim();
    if (!href || href.charAt(0) === '#') { return null; }
    if (/^(javascript:|mailto:|tel:|sms:|blob:|data:)/i.test(href)) { return null; }
    if (/logout/i.test(href)) { return null; }

    var absoluta;
    try { absoluta = new URL(href, location.href); } catch (e) { return null; }
    if (absoluta.origin !== location.origin) { return null; }
    if (absoluta.protocol !== 'http:' && absoluta.protocol !== 'https:') { return null; }
    return absoluta.href;
  }

  /** Inyecta <link rel="prefetch"> una sola vez por URL, con tope por página. */
  function precargarUrl(url) {
    if (!url || yaPrecargados[url] || totalPrecargas >= MAX_PREFETCHES) { return; }
    // No precargar la página en la que ya estamos.
    if (url === location.href) { return; }
    yaPrecargados[url] = true;
    totalPrecargas++;
    var l = document.createElement('link');
    l.rel = 'prefetch';
    l.as = 'document';
    l.href = url;
    (document.head || document.documentElement).appendChild(l);
  }

  /** href precargable del elemento (enlace de menú o fila data-easy-href), o null. */
  function urlDeElemento(el) {
    if (!el || typeof el.matches !== 'function') { return null; }
    if (el.matches(SKIP_SELECTOR)) { return null; }

    if (el.hasAttribute('data-easy-href')) {
      return normalizarUrl(el.getAttribute('data-easy-href'));
    }
    if (el.matches(NAV_SELECTOR)) {
      return normalizarUrl(el.getAttribute('href'));
    }
    return null;
  }

  var supportsSpeculationRules =
    typeof HTMLScriptElement !== 'undefined' &&
    typeof HTMLScriptElement.supports === 'function' &&
    HTMLScriptElement.supports('speculationrules');

  if (supportsSpeculationRules) {
    // Chromium/Edge: reglas declarativas para los <a> del menú. eagerness
    // "moderate" = precarga al pasar el cursor / apuntar (no en cada carga).
    var rules = {
      prefetch: [{
        source: 'document',
        eagerness: 'moderate',
        where: {
          and: [
            { selector_matches: NAV_SELECTOR },
            { not: { href_matches: '*logout*' } },
            { not: { selector_matches: SKIP_SELECTOR } }
          ]
        }
      }]
    };
    try {
      var srScript = document.createElement('script');
      srScript.type = 'speculationrules';
      srScript.textContent = JSON.stringify(rules);
      (document.head || document.documentElement).appendChild(srScript);
    } catch (e) {}
  }

  // Superficie por eventos: filas [data-easy-href] en todos los navegadores,
  // y también los <a> del menú cuando NO hay Speculation Rules (fallback).
  var EVENT_TARGETS = supportsSpeculationRules
    ? '[data-easy-href]'
    : '[data-easy-href], a[href]';

  function objetivoDesdeEvento(event) {
    var t = event.target;
    return (t && typeof t.closest === 'function') ? t.closest(EVENT_TARGETS) : null;
  }

  // Hover con intención: espera HOVER_DELAY_MS antes de precargar; si el cursor
  // sale antes (pasada rápida sobre una lista), se cancela. Un timer a la vez
  // basta: no se puede "hovear" dos filas al mismo tiempo.
  var hoverTimer = 0;
  var hoverEl = null;

  document.addEventListener('pointerover', function (event) {
    if (event.pointerType === 'touch') { return; } // el touch va por touchstart
    var el = objetivoDesdeEvento(event);
    if (!el || el === hoverEl) { return; }

    if (hoverTimer) { clearTimeout(hoverTimer); }
    hoverEl = el;
    var url = urlDeElemento(el);
    if (!url) { hoverEl = null; return; }

    hoverTimer = window.setTimeout(function () {
      hoverTimer = 0;
      hoverEl = null;
      precargarUrl(url);
    }, HOVER_DELAY_MS);
  }, { passive: true });

  document.addEventListener('pointerout', function (event) {
    if (!hoverEl) { return; }
    // ¿Salió de verdad del elemento vigilado (y no hacia un hijo suyo)?
    var hacia = event.relatedTarget;
    if (hacia && hoverEl.contains(hacia)) { return; }
    if (event.target instanceof Element && hoverEl.contains(event.target)) {
      if (hoverTimer) { clearTimeout(hoverTimer); hoverTimer = 0; }
      hoverEl = null;
    }
  }, { passive: true });

  // Táctil: el dedo ya es intención — precarga inmediata al primer contacto,
  // gana los ~100-300ms que separan touchstart del click de navegación.
  document.addEventListener('touchstart', function (event) {
    var el = objetivoDesdeEvento(event);
    if (el) { precargarUrl(urlDeElemento(el)); }
  }, { passive: true });
})();
