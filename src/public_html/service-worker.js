/**
 * Service Worker - Medisoft Hoteles
 * Estrategia de cachÃ© por capas con soporte offline completo
 */

const SW_VERSION = 'v27'; // v27: cinta "estos datos son de antes" + aviso de filtro no aplicado
const BASE = self.registration.scope; // detecta automÃ¡ticamente el subdirectorio

const CACHE = {
  shell:   `loscedros-shell-${SW_VERSION}`,   // assets estÃ¡ticos locales
  ext:     `loscedros-ext-${SW_VERSION}`,     // librerÃ­as externas (CDN)
  // SIN version a proposito: la memoria offline del hotelero sobrevive a los
  // deploys del SW (antes cada version nueva la borraba y lo dejaba sin nada
  // que abrir hasta su siguiente visita con red). Se limpia por logout o
  // cambio de hotel via CLEAR_PAGES_CACHE, que es lo que importa para aislar.
  pages:   'loscedros-pages',                 // ultima copia buena de pantallas clave
};

// Pantallas operativas que se guardan para navegacion offline (network-first
// con respaldo). Se limpian al cerrar sesion o cambiar de hotel via
// CLEAR_PAGES_CACHE. Login, reportes y pantallas publicas /h/{slug} quedan fuera.
const OFFLINE_PAGE_PATHS = /^(dashboard|habitaciones|reservaciones|huespedes|caja|camarista|offline\/pendientes)([\/?#]|$)/;

// Rutas por las que ARRANCA la app instalada: start_url del manifest (login por
// slug), login generico y la raiz. Ninguna es cacheable (son credenciales o un
// redirect), asi que sin red no tienen copia posible y caian en offline.html
// SIEMPRE — con media operacion guardada al lado. Ahora se resuelven entrando a
// la mejor pantalla operativa disponible. Cubre tambien a las apps ya instaladas
// con el start_url viejo, que no re-leen el manifest hasta reinstalarse.
const OFFLINE_ARRANQUE_PATHS = /^(h\/[a-z0-9-]+\/login|login|)([?#]|$)/;

// Puertas de entrada offline, en orden de preferencia. `camarista` va segundo a
// proposito: un rol acotado a Limpieza NUNCA tiene copia del dashboard (su home
// es /camarista y DashboardController lo rebota), y es justo quien mas trabaja
// con mala señal en los pasillos. Cada rol solo cachea lo que puede abrir, asi
// que el orden no le quita su pantalla a nadie.
const OFFLINE_ENTRY_PAGES = ['dashboard', 'camarista', 'habitaciones', 'reservaciones', 'caja', 'huespedes'];

// â”€â”€â”€ Assets del shell de la app â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const SHELL_ASSETS = [
  'offline.html',
  'manifest.json',
  'css/custom.css',
  'css/tailwind.css',
  'css/sidebar-styles.css',
  'css/sidebar-size-override.css',
  'css/dashboard-layout-fix.css',
  'css/loading-screen.css',
  'css/pwa.css',
  'js/app.js',
  'js/pwa.js',
  'js/offline-data.js',
  'js/buscador-global.js',
  'js/checkout-limpieza.js',
  'js/reservaciones-offline.js',
  'js/habitaciones-offline.js',
  'js/caja-offline.js',
  'js/huespedes-offline.js',
  'js/dashboard.js',
  'js/loading-screen.js',
  'img/logo.png',
  'img/icons/icon-192x192.png',
  'img/icons/icon-512x512.png',
  // LibrerÃ­as self-hosted (fase 6): antes venÃ­an de CDNs externos
  // (tailwindcdn.js eliminado en v24: ahora es css/tailwind.css precompilado)
  'vendor/fontawesome/css/all.min.css',
  'vendor/fontawesome/webfonts/fa-solid-900.woff2',
  'vendor/fontawesome/webfonts/fa-regular-400.woff2',
  'vendor/fontawesome/webfonts/fa-brands-400.woff2',
  'vendor/sweetalert2/sweetalert2.all.min.js',
  'vendor/sweetalert2/sweetalert2.min.css',
  'vendor/jquery/jquery-3.6.0.min.js',
  'vendor/chartjs/chart.umd.min.js',
  'vendor/fonts/fonts.css',
];

// â”€â”€â”€ LibrerÃ­as CDN que necesitamos offline â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Fase 6: vacÃ­a — todo es self-hosted. Se conserva la estrategia SWR para
// cualquier recurso externo residual (p.ej. Google Fonts de pÃ¡ginas pÃºblicas).
const CDN_ASSETS = [];

// â”€â”€â”€ PÃ¡ginas dinamicas excluidas del cache â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
async function cacheKeyPages() {
  // No se cachean paginas privadas o dinamicas: dashboard, reservaciones,
  // caja, facturacion, reportes, huespedes, usuarios o configuracion.
  return Promise.resolve([]);
}

// â”€â”€â”€ INSTALL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
self.addEventListener('install', event => {
  event.waitUntil(
    Promise.all([
      // Cachear shell local
      caches.open(CACHE.shell).then(cache => {
        const urls = SHELL_ASSETS.map(p => BASE + p);
        return Promise.allSettled(urls.map(url =>
          cache.add(url).catch(e => console.warn('[SW] Shell no cacheado:', url, e.message))
        ));
      }),
      // Cachear CDN con no-cors para recursos opacos
      caches.open(CACHE.ext).then(cache => {
        return Promise.allSettled(CDN_ASSETS.map(url =>
          fetch(new Request(url, { mode: 'no-cors' }))
            .then(res => cache.put(url, res))
            .catch(e => console.warn('[SW] CDN no cacheado:', url, e.message))
        ));
      }),
    ]).then(() => self.skipWaiting())
  );
});

// â”€â”€â”€ ACTIVATE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      const validos = Object.values(CACHE);
      return Promise.all(
        keys
          .filter(k => k.startsWith('loscedros-') && !validos.includes(k))
          .map(k => {
            console.log('[SW] Eliminando cache obsoleto:', k);
            return caches.delete(k);
          })
      );
    }).then(() => self.clients.claim())
  );
});

// â”€â”€â”€ FETCH â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar extensiones de Chrome / no-http
  if (!request.url.startsWith('http')) return;

  // â”€â”€ 1. POST/PUT/DELETE â†’ network con cola offline â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (request.method !== 'GET') {
    event.respondWith(
      fetch(request.clone()).catch(() => {
        // NO prometer envio: la captura de escrituras sin conexion esta apagada
        // y /api/sync responde 423, asi que esto NO se ejecuta despues. Decia
        // "la accion se ejecutara cuando vuelva internet" — la misma mentira que
        // se quito de los interceptores el 26-jul, sobrevivio aqui hasta jul-30.
        return new Response(JSON.stringify({
          success: false,
          offline: true,
          message: 'Sin conexión: la acción no quedó guardada. Vuelve a intentarla cuando regrese el internet.',
        }), {
          status: 503,
          headers: { 'Content-Type': 'application/json' },
        });
      })
    );
    return;
  }

  // â”€â”€ 2. Recursos CDN externos â†’ Stale-While-Revalidate â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (url.origin !== self.location.origin) {
    event.respondWith(staleWhileRevalidate(request, CACHE.ext));
    return;
  }

  // â”€â”€ 3. Rutas de API/AJAX â†’ Network Only con respuesta offline â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (url.pathname.includes('/api/') ||
      request.headers.get('X-Requested-With') === 'XMLHttpRequest') {
    event.respondWith(
      fetch(request).catch(() =>
        new Response(JSON.stringify({
          success: false,
          offline: true,
          message: 'Sin conexiÃ³n a internet',
        }), {
          status: 503,
          headers: { 'Content-Type': 'application/json' },
        })
      )
    );
    return;
  }

  // â”€â”€ 4. Assets estÃ¡ticos locales â†’ Cache First â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (isSafeStaticAsset(url)) {
    event.respondWith(cacheFirst(request, CACHE.shell));
    return;
  }

  // â”€â”€ 5. PÃ¡ginas HTML privadas/dinamicas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (request.mode === 'navigate' ||
      (request.headers.get('accept') || '').includes('text/html')) {
    // Pantallas operativas clave: network-first + ultima copia buena offline
    if (isOfflineCacheablePage(url)) {
      event.respondWith(networkFirstPage(request));
      return;
    }
    // Arranque de la app: sin red entra a lo que si esta guardado.
    if (esRutaDeArranque(url)) {
      event.respondWith(arranquePage(request));
      return;
    }
    event.respondWith(networkOnlyPage(request));
    return;
  }

  event.respondWith(fetch(request));
});

// â”€â”€â”€ BACKGROUND SYNC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
self.addEventListener('sync', event => {
  if (event.tag === 'sync-pending-actions') {
    event.waitUntil(syncPendingActions());
  }
});

async function syncPendingActions() {
  const clients = await self.clients.matchAll();
  clients.forEach(client => {
    client.postMessage({ type: 'SYNC_STARTED' });
  });

  // Notificar a la app para que procese su cola de IndexedDB
  clients.forEach(client => {
    client.postMessage({ type: 'PROCESS_QUEUE' });
  });
}

// â”€â”€â”€ PUSH NOTIFICATIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
self.addEventListener('push', event => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (error) {
    data = { body: 'Nueva notificación de Medisoft Hoteles' };
  }

  const notificationAsset = (value, fallback) => {
    if (!value || typeof value !== 'string') return fallback;

    try {
      const url = new URL(value, BASE);
      return url.origin === self.location.origin ? url.href : fallback;
    } catch (error) {
      return fallback;
    }
  };

  const options = {
    body: data.body || 'Nueva notificación de Medisoft Hoteles',
    icon: notificationAsset(data.icon, BASE + 'img/icons/icon-192x192.png'),
    badge: notificationAsset(data.badge, BASE + 'img/icons/icon-72x72.png'),
    vibrate: [100, 50, 100],
    tag: data.tag || undefined,
    renotify: Boolean(data.tag),
    data: data,
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'Medisoft Hoteles', options)
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const data = event.notification.data || {};
  const targetUrl = new URL(data.url || 'dashboard', BASE).href;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(openClients => {
      for (const client of openClients) {
        const clientUrl = new URL(client.url);
        const target = new URL(targetUrl);
        if (clientUrl.origin === target.origin && clientUrl.pathname === target.pathname) {
          return client.focus();
        }
      }

      return clients.openWindow(targetUrl);
    })
  );
});

// â”€â”€â”€ MENSAJES DESDE LA APP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
self.addEventListener('message', event => {
  if (!event.data) return;

  switch (event.data.type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;

    case 'CACHE_PAGE':
      // Cache de paginas dinamicas deshabilitado por aislamiento multi-hotel.
      if (event.data.url) {
        event.waitUntil(cachePage(event.data.url));
      }
      break;

    case 'CACHE_KEY_PAGES':
      event.waitUntil(cacheKeyPages());
      break;

    case 'GET_CACHE_LIST':
      // No hay cache de paginas privadas.
      if (event.source) {
        event.source.postMessage({
          type: 'CACHE_LIST',
          urls: [],
        });
      }
      break;

    case 'CLEAR_PRIVATE_DATA':
      event.waitUntil(clearPrivateCaches());
      break;

    case 'CLEAR_PAGES_CACHE':
      event.waitUntil(clearPagesCache());
      break;

    case 'CACHE_OFFLINE_BRANDING':
      event.waitUntil(cacheOfflineBrandingAssets(event.data.assets));
      break;
  }
});

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// ESTRATEGIAS DE CACHÃ‰
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

function isSafeStaticAsset(url) {
  const pathname = url.pathname;

  return pathname.endsWith('/offline.html') ||
    pathname.endsWith('/manifest.json') ||
    /\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|otf|webp)(\?.*)?$/i.test(pathname);
}

function normalizeOfflineBrandingAsset(value) {
  const raw = String(value || '').trim();
  if (!raw || /[\u0000-\u001F<>"']/.test(raw)) return '';

  try {
    const url = new URL(raw, BASE);
    const scopePath = new URL(BASE).pathname;
    const pathWithinScope = url.pathname.startsWith(scopePath)
      ? url.pathname.slice(scopePath.length)
      : url.pathname.replace(/^\/+/, '');
    const allowedPath = pathWithinScope.startsWith('uploads/branding/') ||
      pathWithinScope.startsWith('uploads/') ||
      pathWithinScope.startsWith('img/');
    const allowedExtension = /\.(png|jpe?g|webp|ico)$/i.test(url.pathname);

    if (url.origin !== self.location.origin || !allowedPath || !allowedExtension) {
      return '';
    }

    return url.href;
  } catch {
    return '';
  }
}

async function cacheOfflineBrandingAssets(assets) {
  if (!Array.isArray(assets) || !assets.length) return;

  const urls = Array.from(new Set(assets.map(normalizeOfflineBrandingAsset).filter(Boolean)));
  if (!urls.length) return;

  const cache = await caches.open(CACHE.shell);
  await Promise.allSettled(urls.map(async url => {
    const request = new Request(url, { credentials: 'same-origin', cache: 'reload' });
    const response = await fetch(request);
    if (response && response.ok) {
      await cache.put(request, response.clone());
    }
  }));
}

/** Cache First: devuelve del cachÃ©, si no existe lo busca en red y lo guarda */
async function cacheFirst(request, cacheName) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    // Sin red: los assets versionados (?v=filemtime) no coinciden exacto con
    // el precache (guardado sin query). Servir la ultima version conocida
    // es mejor que un 503 estando offline.
    const stale = await caches.match(request, { ignoreSearch: true });
    if (stale) return stale;

    return new Response('Recurso no disponible offline', { status: 503 });
  }
}

/** Stale While Revalidate: sirve del cachÃ© inmediatamente y actualiza en background */
async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);

  // Fuentes y CSS necesitan CORS para poder usarse; el resto puede ser opaco
  const necesitaCors = /\.(woff2?|ttf|otf|eot|css)(\?.*)?$/i.test(request.url);
  const mode = necesitaCors ? 'cors' : 'no-cors';

  const fetchPromise = fetch(new Request(request.url, { mode, credentials: 'omit' }))
    .then(response => {
      // No cachear respuestas opacas (status 0) ni errores
      if (response && (response.ok || response.type === 'opaque')) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(() => null);

  return cached || fetchPromise || new Response('', { status: 503 });
}

/** Path relativo al scope del SW (ej. "dashboard", "caja/movimientos"). */
function pathWithinScope(url) {
  const scopePath = new URL(BASE).pathname;
  return url.pathname.startsWith(scopePath)
    ? url.pathname.slice(scopePath.length)
    : url.pathname.replace(/^\/+/, '');
}

function isOfflineCacheablePage(url) {
  return url.origin === self.location.origin &&
    OFFLINE_PAGE_PATHS.test(pathWithinScope(url));
}

/**
 * Network First para pantallas operativas: intenta red y guarda la copia buena;
 * sin red sirve la ultima copia guardada (y offline.html como ultimo recurso).
 * No guarda respuestas redirigidas (ej. sesion expirada -> login) ni errores,
 * para nunca "congelar" una pantalla equivocada.
 */
async function networkFirstPage(request) {
  try {
    const response = await fetch(request);
    notifyClients({ type: 'ONLINE' });

    if (response.ok && !response.redirected && response.status === 200) {
      const cache = await caches.open(CACHE.pages);
      cache.put(request, response.clone());
    }

    return response;
  } catch {
    notifyClients({ type: 'OFFLINE' });

    const cache = await caches.open(CACHE.pages);

    // 1) Copia EXACTA de lo que se pidio (misma URL, misma query).
    // ignoreVary: la respuesta trae `Vary: ...,User-Agent` (Apache) y el UA cambia
    // cuando el navegador se actualiza -> sin esto, un update de Safari invalidaria
    // en silencio TODA la memoria offline del hotelero aunque siga guardada.
    const exacta = await cache.match(request) ||
      await cache.match(request, { ignoreVary: true });
    if (exacta) return conCintaOffline(exacta);

    // 2) Sin copia exacta: se sirve la de la misma ruta con OTRA query. Util
    // (mejor eso que nada) pero hay que DECIRLO: pedir /huespedes?buscar=Ramirez
    // y recibir la lista completa sin aviso es un resultado silenciosamente
    // equivocado, y sobre eso se toman decisiones en el mostrador.
    const otraQuery = await cache.match(request, { ignoreSearch: true, ignoreVary: true });
    if (otraQuery) {
      const pidioFiltro = new URL(request.url).search !== '';
      return conCintaOffline(otraQuery, pidioFiltro);
    }

    return respuestaOffline();
  }
}

// ─── Cinta "estos datos son de antes" ────────────────────────────────────────
// Una pantalla servida del cache se ve EXACTA a la de internet: mismos numeros,
// misma hora impresa. Recepcion puede vender un cuarto que se ocupo hace horas.
// Los 4 avisos que vivian en las vistas estaban MUERTOS (un `return;` al inicio),
// asi que se resuelve aqui: una sola cinta que cubre CUALQUIER pantalla servida
// sin red, sin depender de que cada vista traiga la suya.

function edadHumana(fechaHttp) {
  if (!fechaHttp) return null;

  const guardado = new Date(fechaHttp).getTime();
  if (!Number.isFinite(guardado)) return null;

  const minutos = Math.floor((Date.now() - guardado) / 60000);
  if (minutos < 1) return 'hace un momento';
  if (minutos === 1) return 'hace 1 minuto';
  if (minutos < 60) return `hace ${minutos} minutos`;

  const horas = Math.floor(minutos / 60);
  if (horas === 1) return 'hace 1 hora';
  if (horas < 24) return `hace ${horas} horas`;

  const dias = Math.floor(horas / 24);
  if (dias === 1) return 'de ayer';
  return `de hace ${dias} días`;
}

function cintaOfflineHtml(fechaHttp, filtroNoAplicado) {
  const edad = edadHumana(fechaHttp);
  const cuando = edad
    ? `Estás viendo información guardada <strong>${edad}</strong>`
    : 'Estás viendo la última información guardada en este equipo';
  const filtro = filtroNoAplicado
    ? ' · <strong>no se pudo aplicar tu búsqueda o filtro</strong>, esto es la lista completa guardada'
    : '';

  // Estilos EN LINEA a proposito: la cinta debe verse aunque el CSS no cachee.
  return '<div id="ms-offline-cinta" role="status" aria-live="polite" style="' +
    'display:flex;gap:8px;align-items:flex-start;padding:9px 14px;' +
    'background:#7C4A03;color:#FFF8EC;font-size:13px;line-height:1.35;' +
    'font-weight:600;position:relative;z-index:2147483000;' +
    'font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">' +
    '<span aria-hidden="true">&#128246;</span><span>Sin conexión. ' + cuando + filtro +
    '. Para guardar cambios hace falta internet.</span></div>';
}

/**
 * Copia cacheada + cinta. Si algo no cuadra (no es HTML, no hay <body>, falla el
 * parseo) devuelve la copia INTACTA: es un aviso, jamas debe impedir que la
 * pantalla se vea.
 */
async function conCintaOffline(respuesta, filtroNoAplicado = false) {
  try {
    if (!respuesta) return respuesta;

    const tipo = respuesta.headers.get('content-type') || '';
    if (!tipo.includes('text/html')) return respuesta;

    const html = await respuesta.clone().text();
    const cinta = cintaOfflineHtml(respuesta.headers.get('date'), filtroNoAplicado);
    const conCinta = html.replace(/<body([^>]*)>/i, (m, attrs) => `<body${attrs}>${cinta}`);

    if (conCinta === html) return respuesta; // no habia <body>: no tocar

    const headers = new Headers(respuesta.headers);
    headers.delete('content-length'); // el cuerpo cambio de tamaño

    return new Response(conCinta, {
      status: respuesta.status,
      statusText: respuesta.statusText,
      headers,
    });
  } catch {
    return respuesta;
  }
}

function esRutaDeArranque(url) {
  return url.origin === self.location.origin &&
    OFFLINE_ARRANQUE_PATHS.test(pathWithinScope(url));
}

/**
 * Mejor pantalla operativa guardada, en orden de preferencia. null si no hay ninguna.
 *
 * `ignoreVary` NO es opcional: Apache manda `Vary: Accept-Encoding,User-Agent`
 * (el `Header append Vary User-Agent` del .htaccess) y estas paginas se guardan
 * con la Request de NAVEGACION real, que si lleva User-Agent. Buscarlas por URL
 * arma una Request sin ese header, el Vary no casa y el match falla SIEMPRE —
 * el fallback no encontraria nada y el arranque seguiria muriendo en el muro.
 * (offline.html no sufre esto porque se precachea con `cache.add(string)`:
 * ninguno de los dos lados lleva User-Agent y por eso siempre caso.)
 */
async function mejorPantallaGuardada() {
  const cache = await caches.open(CACHE.pages);

  for (const pagina of OFFLINE_ENTRY_PAGES) {
    const copia = await cache.match(BASE + pagina, { ignoreSearch: true, ignoreVary: true });
    if (copia) return copia;
  }

  return null;
}

/** Muro "Sin conexion": ultimo recurso cuando no hay NADA que abrir. */
async function respuestaOffline() {
  const offline = await caches.match(BASE + 'offline.html');
  if (offline) return offline;

  return new Response('<h1>Medisoft Hoteles</h1><p>Sin conexión. Vuelve a intentarlo cuando tengas internet.</p>', {
    status: 503,
    headers: { 'Content-Type': 'text/html' },
  });
}

/**
 * Arranque de la PWA (start_url / login). Con red se comporta como siempre; sin
 * red entrega la mejor pantalla guardada para que la app ABRA en vez de morir en
 * el muro. La URL queda en la del login hasta la siguiente carga con red: es
 * cosmetico y preferible a no poder trabajar. Nunca se cachea esta respuesta
 * (son credenciales y casi siempre un redirect).
 */
async function arranquePage(request) {
  try {
    const response = await fetch(request);
    notifyClients({ type: 'ONLINE' });
    return response;
  } catch {
    notifyClients({ type: 'OFFLINE' });

    const guardada = await mejorPantallaGuardada();
    if (guardada) return conCintaOffline(guardada);

    return respuestaOffline();
  }
}

async function clearPagesCache() {
  await caches.delete(CACHE.pages);
  console.log('[SW] Cache de pantallas limpiado (logout o cambio de hotel)');
}

/** Network Only para HTML privado/dinamico: no guarda pantallas con datos de hotel. */
async function networkOnlyPage(request) {
  try {
    const response = await fetch(request);
    notifyClients({ type: 'ONLINE' });
    return response;
  } catch {
    notifyClients({ type: 'OFFLINE' });

    return respuestaOffline();
  }
}

async function cachePage(url) {
  console.info('[SW] Cache de paginas dinamicas deshabilitado:', url);
}

function notifyClients(message) {
  self.clients.matchAll().then(clients =>
    clients.forEach(c => c.postMessage(message))
  );
}

async function clearPrivateCaches() {
  const keys = await caches.keys();
  await Promise.all(
    keys
      .filter(key => key.startsWith('loscedros-'))
      .map(key => caches.delete(key))
  );
}
