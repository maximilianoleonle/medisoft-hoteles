/**
 * Service Worker - Los Cedros
 * Estrategia de cachÃ© por capas con soporte offline completo
 */

const SW_VERSION = 'v15';
const BASE = self.registration.scope; // detecta automÃ¡ticamente el subdirectorio

const CACHE = {
  shell:   `loscedros-shell-${SW_VERSION}`,   // assets estÃ¡ticos locales
  ext:     `loscedros-ext-${SW_VERSION}`,     // librerÃ­as externas (CDN)
  pages:   `loscedros-pages-${SW_VERSION}`,   // pÃ¡ginas HTML visitadas
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
  'img/logo-hotel-san-nicolas2.png',
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

// â”€â”€â”€ PÃ¡ginas clave a pre-cachear â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const KEY_PAGES = [
  '',
  'dashboard',
  'reservaciones',
  'reservaciones/crear',
  'reservaciones/calendario',
  'habitaciones',
  'habitaciones/disponibles',
  'huespedes',
  'huespedes/buscar',
  'caja',
  'inventario',
  'facturacion',
  'reportes',
  'usuarios',
  'configuracion/tarifas',
];

async function cacheKeyPages() {
  const cache = await caches.open(CACHE.pages);
  const urls = KEY_PAGES.map(p => BASE + p);

  return Promise.allSettled(urls.map(url =>
    cache.add(url).catch(e => console.warn('[SW] Pagina no cacheada:', url, e.message))
  ));
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
      cacheKeyPages(),
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

  if (request.mode === 'navigate' ||
      (request.headers.get('accept') || '').includes('text/html')) {
    event.respondWith(networkFirstPage(request));
    return;
  }

  // â”€â”€ 3. Assets estÃ¡ticos locales â†’ Cache First â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|otf|webp)(\?.*)?$/i.test(url.pathname)) {
    event.respondWith(cacheFirst(request, CACHE.shell));
    return;
  }

  // â”€â”€ 4. Rutas de API/AJAX â†’ Network Only con respuesta offline â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

  // â”€â”€ 5. PÃ¡ginas HTML â†’ Network First con fallback al cachÃ© â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  event.respondWith(networkFirstPage(request));
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
  const data = event.data ? event.data.json() : {};
  const options = {
    body: data.body || 'Nueva notificaciÃ³n de Los Cedros',
    icon: BASE + 'img/icons/icon-192x192.png',
    badge: BASE + 'img/icons/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: data,
    actions: [
      { action: 'open', title: 'Ver' },
      { action: 'close', title: 'Cerrar' },
    ],
  };
  event.waitUntil(
    self.registration.showNotification('Los Cedros', options)
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  if (event.action === 'open' || !event.action) {
    event.waitUntil(
      clients.openWindow(BASE + (event.notification.data?.url || 'dashboard'))
    );
  }
});

// â”€â”€â”€ MENSAJES DESDE LA APP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
self.addEventListener('message', event => {
  if (!event.data) return;

  switch (event.data.type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;

    case 'CACHE_PAGE':
      // La app pide cachear una pÃ¡gina especÃ­fica
      if (event.data.url) {
        event.waitUntil(cachePage(event.data.url));
      }
      break;

    case 'CACHE_KEY_PAGES':
      event.waitUntil(cacheKeyPages());
      break;

    case 'GET_CACHE_LIST':
      // Devolver lista de pÃ¡ginas en cachÃ©
      caches.open(CACHE.pages).then(cache => cache.keys()).then(keys => {
        event.source.postMessage({
          type: 'CACHE_LIST',
          urls: keys.map(r => r.url),
        });
      });
      break;

    case 'CLEAR_PRIVATE_DATA':
      event.waitUntil(clearPrivateCaches());
      break;
  }
});

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// ESTRATEGIAS DE CACHÃ‰
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

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

/** Network First para pÃ¡ginas HTML: intenta red, si falla devuelve cachÃ© o offline.html */
async function networkFirstPage(request) {
  const cache = await caches.open(CACHE.pages);

  try {
    const response = await fetch(request);

    // Solo cachear respuestas HTML exitosas de pÃ¡ginas de la app
    if (response.ok && response.headers.get('content-type')?.includes('text/html')) {
      cache.put(request, response.clone());
      // Avisar a los clientes que la app estÃ¡ online
      notifyClients({ type: 'ONLINE' });
    }
    return response;
  } catch {
    notifyClients({ type: 'OFFLINE' });

    const cachedPage = await cache.match(request, { ignoreSearch: true });
    if (cachedPage) return cachedPage;

    // Fallback a paginas clave del sistema
    const fallback = await fallbackAppPage(request, cache);
    if (fallback) return fallback;

    return new Response('<h1>Los Cedros</h1><p>Abre el sistema con internet una vez para guardar esta pantalla.</p>', {
      status: 503,
      headers: { 'Content-Type': 'text/html' },
    });
  }
}

async function cachePage(url) {
  try {
    const cache = await caches.open(CACHE.pages);
    const request = new Request(url, {
      cache: 'reload',
      credentials: 'include',
    });
    const response = await fetch(request);

    if (response.ok && response.headers.get('content-type')?.includes('text/html')) {
      await cache.put(url, response.clone());
    }
  } catch (e) {
    console.warn('[SW] Pagina actual no cacheada:', url, e.message);
  }
}

async function fallbackAppPage(request, cache) {
  const url = new URL(request.url);
  const basePath = new URL(BASE).pathname.replace(/\/$/, '');
  let path = url.pathname;

  if (basePath && path.startsWith(basePath)) {
    path = path.slice(basePath.length);
  }

  path = path.replace(/^\/+/, '').replace(/\/$/, '');

  const grupos = [
    [path],
    path.startsWith('reservaciones/crear') ? ['reservaciones/crear', 'reservaciones'] : [],
    path.startsWith('reservaciones/calendario') ? ['reservaciones/calendario', 'reservaciones'] : [],
    path.startsWith('reservaciones') ? ['reservaciones'] : [],
    path.startsWith('habitaciones/disponibles') ? ['habitaciones/disponibles', 'habitaciones'] : [],
    path.startsWith('habitaciones') ? ['habitaciones'] : [],
    path.startsWith('huespedes/buscar') ? ['huespedes/buscar', 'huespedes'] : [],
    path.startsWith('huespedes') ? ['huespedes'] : [],
    path.startsWith('caja') ? ['caja'] : [],
    path.startsWith('inventario') ? ['inventario'] : [],
    path.startsWith('facturacion') ? ['facturacion'] : [],
    path.startsWith('reportes') ? ['reportes'] : [],
    path.startsWith('usuarios') ? ['usuarios'] : [],
    path.startsWith('configuracion/tarifas') ? ['configuracion/tarifas'] : [],
    ['dashboard', ''],
  ];

  for (const grupo of grupos) {
    for (const item of grupo) {
      const response = await cache.match(BASE + item, { ignoreSearch: true });
      if (response) return response;
    }
  }

  return null;
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
