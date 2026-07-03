/**
 * Service Worker - Medisoft Hoteles
 * Estrategia de cachÃ© por capas con soporte offline completo
 */

const SW_VERSION = 'v19';
const BASE = self.registration.scope; // detecta automÃ¡ticamente el subdirectorio

const CACHE = {
  shell:   `loscedros-shell-${SW_VERSION}`,   // assets estÃ¡ticos locales
  ext:     `loscedros-ext-${SW_VERSION}`,     // librerÃ­as externas (CDN)
};

// â”€â”€â”€ Assets del shell de la app â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const SHELL_ASSETS = [
  'offline.html',
  'manifest.json',
  'css/custom.css',
  'css/sidebar-styles.css',
  'css/sidebar-size-override.css',
  'css/dashboard-layout-fix.css',
  'css/loading-screen.css',
  'css/pwa.css',
  'js/app.js',
  'js/pwa.js',
  'js/offline-data.js',
  'js/buscador-global.js',
  'js/reservaciones-offline.js',
  'js/habitaciones-offline.js',
  'js/caja-offline.js',
  'js/dashboard.js',
  'js/loading-screen.js',
  'img/logo.png',
  'img/icons/icon-192x192.png',
  'img/icons/icon-512x512.png',
];

// â”€â”€â”€ LibrerÃ­as CDN que necesitamos offline â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const CDN_ASSETS = [
  'https://cdn.tailwindcss.com',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11',
  'https://code.jquery.com/jquery-3.6.0.min.js',
  'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
  'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
  'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js',
  'https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js',
];

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
        return new Response(JSON.stringify({
          success: false,
          offline: true,
          message: 'Sin conexiÃ³n. La acciÃ³n se ejecutarÃ¡ cuando vuelva internet.',
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

  // â”€â”€ 5. PÃ¡ginas HTML privadas/dinamicas â†’ Network Only + offline.html â”€â”€â”€â”€â”€â”€â”€
  if (request.mode === 'navigate' ||
      (request.headers.get('accept') || '').includes('text/html')) {
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
    data = { body: 'Nueva notificacion de Medisoft Hoteles' };
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
    body: data.body || 'Nueva notificacion de Medisoft Hoteles',
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

/** Network Only para HTML privado/dinamico: no guarda pantallas con datos de hotel. */
async function networkOnlyPage(request) {
  try {
    const response = await fetch(request);
    notifyClients({ type: 'ONLINE' });
    return response;
  } catch {
    notifyClients({ type: 'OFFLINE' });

    const offline = await caches.match(BASE + 'offline.html');
    if (offline) return offline;

    return new Response('<h1>Medisoft Hoteles</h1><p>Sin conexion. Vuelve a intentarlo cuando tengas internet.</p>', {
      status: 503,
      headers: { 'Content-Type': 'text/html' },
    });
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
