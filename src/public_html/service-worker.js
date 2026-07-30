/**
 * Service Worker - Medisoft Hoteles
 * Estrategia de cachÃ© por capas con soporte offline completo
 */

const SW_VERSION = 'v28'; // v28: con senal debil no se espera a la red, se entra a lo guardado
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

// ─── Paciencia con la red ────────────────────────────────────────────────────
// Un `fetch` con mala senal NO falla: se queda colgado. La antena sigue viva y
// el navegador espera su propio limite (30 s, a veces mas de un minuto) antes de
// rendirse. Mientras tanto la pantalla anterior queda congelada, el hotelero
// vuelve a picarle y concluye "el sistema esta lentisimo" — cuando el sistema ni
// se ha enterado y la copia guardada estaba lista desde el primer segundo.
//
// La paciencia se mide contra LO QUE PODEMOS DAR A CAMBIO: si hay copia, esperar
// mas no compra nada; si no hay nada que servir, aguantar si vale la pena porque
// la alternativa es el muro. Los plazos NO cancelan la peticion (salvo las de
// API): la respuesta que llegue tarde sigue sirviendo para refrescar el cache y
// que la siguiente pantalla ya salga fresca.
const ESPERA = {
  conCopia: 3000, // hay copia guardada de esta pantalla
  sinCopia: 9000, // no hay nada que ofrecer: se le da su oportunidad a la red
  arranque: 4000, // abrir la app: el precio de equivocarse es entrar a lo viejo
  api:      7000, // el widget cae a su estado sin conexion en vez de girar
  asset:    2500, // hay otra version del mismo archivo en cache
};

const AGOTADO = Symbol('agotado'); // se acabo el plazo (la red sigue intentando)
const FALLO   = Symbol('fallo');   // la red dijo que no de inmediato

/**
 * Mantiene vivo al service worker hasta que aterrice la respuesta tardia, para
 * que alcance a dejar el cache fresco. Defensivo a proposito: si la ventana del
 * evento ya se cerro, `waitUntil` LANZA — y seria absurdo tumbar la navegacion
 * entera por un lujo (que la proxima pantalla salga al dia).
 */
function mantenerVivo(event, promesa) {
  try {
    event?.waitUntil(promesa.catch(() => {}));
  } catch {
    // El evento ya no acepta mas trabajo; la peticion sigue su curso igual.
  }
}

/**
 * Espera `promesa` como mucho `ms`. Nunca lanza: resuelve con el valor, con
 * FALLO si la promesa se rompio o con AGOTADO si se acabo el plazo. Se puede
 * volver a esperar la MISMA promesa despues (una promesa se puede leer muchas
 * veces), que es como se le da una segunda oportunidad a la red sin repetir el
 * viaje.
 */
function conLimite(promesa, ms) {
  let temporizador;
  const reloj = new Promise(resolve => {
    temporizador = setTimeout(() => resolve(AGOTADO), ms);
  });

  return Promise.race([promesa.catch(() => FALLO), reloj])
    .then(resultado => {
      clearTimeout(temporizador);
      return resultado;
    });
}

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
    event.respondWith(apiConEspera(request));
    return;
  }

  // â”€â”€ 4. Assets estÃ¡ticos locales â†’ Cache First â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (isSafeStaticAsset(url)) {
    event.respondWith(cacheFirst(request, CACHE.shell, event));
    return;
  }

  // â”€â”€ 5. PÃ¡ginas HTML privadas/dinamicas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (request.mode === 'navigate' ||
      (request.headers.get('accept') || '').includes('text/html')) {
    // Precarga especulativa (instant-nav): esta NO debe recibir copia guardada.
    // El navegador la guarda en su cache de precarga, asi que el clic abriria una
    // pantalla vieja con cinta de "sin conexion" aunque para entonces la red ya
    // este bien. Que la precarga falle es lo correcto: al hacer clic se navega de
    // verdad y ahi si aplica todo lo demas.
    if (esEspeculativa(request)) {
      event.respondWith(soloRed(request, event));
      return;
    }
    // Pantallas operativas clave: network-first + ultima copia buena offline
    if (isOfflineCacheablePage(url)) {
      event.respondWith(networkFirstPage(request, event));
      return;
    }
    // Arranque de la app: sin red entra a lo que si esta guardado.
    if (esRutaDeArranque(url)) {
      event.respondWith(arranquePage(request, event));
      return;
    }
    event.respondWith(networkOnlyPage(request, event));
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
async function cacheFirst(request, cacheName, event) {
  const cached = await caches.match(request);
  if (cached) return cached;

  const red = fetch(request).then(async response => {
    if (response.ok) {
      const cache = await caches.open(cacheName);
      await cache.put(request, response.clone());
    }
    return response;
  });
  mantenerVivo(event, red);

  // El caso normal de miss NO es "asset nuevo": es que la URL trae ?v=filemtime
  // y el precache lo guardo sin query. Con senal debil eso significa que CADA
  // css/js de la pantalla se cuelga — la pagina llega desnuda y ahi si parece
  // que el sistema se rompio. Por eso el plazo corto y la version vieja: un CSS
  // de la semana pasada se ve bien; ninguno, no.
  const pronto = await conLimite(red, ESPERA.asset);
  if (pronto instanceof Response) return pronto;

  // ignoreVary por la misma razon que en las pantallas: Apache manda
  // `Vary: ...,User-Agent` y el UA cambia cuando el navegador se actualiza. Sin
  // esto, un update de Safari dejaba este respaldo sin encontrar NADA (venia
  // faltando desde antes de los plazos; lo destapo el arnes de v28).
  const anterior = await caches.match(request, { ignoreSearch: true, ignoreVary: true });
  if (anterior) return anterior;

  // No hay version anterior que servir: se le da a la red el resto del plazo.
  const tarde = pronto === AGOTADO ? await conLimite(red, ESPERA.sinCopia - ESPERA.asset) : pronto;
  if (tarde instanceof Response) return tarde;

  return new Response('Recurso no disponible offline', { status: 503 });
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
async function networkFirstPage(request, event) {
  const red = fetch(request).then(async response => {
    // Se guarda aunque ya le hayamos servido la copia al hotelero: la respuesta
    // que llega tarde deja lista la siguiente navegacion.
    if (response.ok && !response.redirected && response.status === 200) {
      const cache = await caches.open(CACHE.pages);
      await cache.put(request, response.clone());
    }
    return response;
  });
  mantenerVivo(event, red);

  const pronto = await conLimite(red, ESPERA.conCopia);
  if (pronto instanceof Response) {
    notifyClients({ type: 'ONLINE' });
    return pronto;
  }

  const guardada = await copiaGuardadaDePagina(request);
  if (guardada) {
    const lenta = pronto === AGOTADO;
    notifyClients({ type: 'OFFLINE', lenta });
    return conCintaOffline(guardada.respuesta, guardada.filtroNoAplicado, lenta);
  }

  // De esta pantalla no hay copia: la unica alternativa es el muro, asi que
  // vale la pena darle a la red lo que le queda de plazo antes de rendirse.
  const tarde = pronto === AGOTADO
    ? await conLimite(red, ESPERA.sinCopia - ESPERA.conCopia)
    : pronto;

  if (tarde instanceof Response) {
    notifyClients({ type: 'ONLINE' });
    return tarde;
  }

  const lenta = tarde === AGOTADO;
  notifyClients({ type: 'OFFLINE', lenta });
  return respuestaOffline(lenta);
}

/** Peticion que el navegador hizo POR SU CUENTA, adelantandose al clic. */
function esEspeculativa(request) {
  const proposito = request.headers.get('Sec-Purpose') ||
    request.headers.get('Purpose') || '';

  return proposito.includes('prefetch') || proposito.includes('prerender');
}

/**
 * Red pelada, sin respaldo: si falla, falla. Guarda la copia igual cuando la
 * pantalla es de las cacheables — una precarga que si alcanzo a llegar deja
 * lista la memoria offline, que es justo lo que queremos que pase con red buena.
 */
async function soloRed(request, event) {
  const red = fetch(request).then(async response => {
    if (isOfflineCacheablePage(new URL(request.url)) &&
        response.ok && !response.redirected && response.status === 200) {
      const cache = await caches.open(CACHE.pages);
      await cache.put(request, response.clone());
    }
    return response;
  });

  mantenerVivo(event, red);
  return red;
}

/**
 * La copia guardada de una pantalla, con la advertencia que le toca. null si de
 * esa ruta no hay nada.
 */
async function copiaGuardadaDePagina(request) {
  const cache = await caches.open(CACHE.pages);

  // 1) Copia EXACTA de lo que se pidio (misma URL, misma query).
  // ignoreVary: la respuesta trae `Vary: ...,User-Agent` (Apache) y el UA cambia
  // cuando el navegador se actualiza -> sin esto, un update de Safari invalidaria
  // en silencio TODA la memoria offline del hotelero aunque siga guardada.
  const exacta = await cache.match(request) ||
    await cache.match(request, { ignoreVary: true });
  if (exacta) return { respuesta: exacta, filtroNoAplicado: false };

  // 2) Sin copia exacta: se sirve la de la misma ruta con OTRA query. Util
  // (mejor eso que nada) pero hay que DECIRLO: pedir /huespedes?buscar=Ramirez
  // y recibir la lista completa sin aviso es un resultado silenciosamente
  // equivocado, y sobre eso se toman decisiones en el mostrador.
  const otraQuery = await cache.match(request, { ignoreSearch: true, ignoreVary: true });
  if (otraQuery) {
    return { respuesta: otraQuery, filtroNoAplicado: pidioBusquedaOFiltro(request.url) };
  }

  return null;
}

/**
 * API/AJAX con plazo. Aqui SI se cancela la peticion al vencer, a diferencia de
 * las pantallas: son GET que las vistas repiten solas y una fila de peticiones
 * colgadas sobre una antena mala se estorba entre si. El 503 es el que la app ya
 * entiende — cada widget cae a su estado sin conexion en vez de girar sin fin.
 */
async function apiConEspera(request) {
  const control = new AbortController();
  const red = fetch(request, { signal: control.signal });
  const resultado = await conLimite(red, ESPERA.api);

  if (resultado instanceof Response) return resultado;

  const lenta = resultado === AGOTADO;
  if (lenta) control.abort();
  notifyClients({ type: 'OFFLINE', lenta });

  return new Response(JSON.stringify({
    success: false,
    offline: true,
    lenta,
    message: lenta
      ? 'La conexión está muy lenta y no alcanzó a responder.'
      : 'Sin conexión a internet.',
  }), {
    status: 503,
    headers: { 'Content-Type': 'application/json' },
  });
}

// ─── Cinta "estos datos son de antes" ────────────────────────────────────────
// Una pantalla servida del cache se ve EXACTA a la de internet: mismos numeros,
// misma hora impresa. Recepcion puede vender un cuarto que se ocupo hace horas.
// Los 4 avisos que vivian en las vistas estaban MUERTOS (un `return;` al inicio),
// asi que se resuelve aqui: una sola cinta que cubre CUALQUIER pantalla servida
// sin red, sin depender de que cada vista traiga la suya.

// Parametros que NO cambian el contenido de la pantalla: son de navegacion o de
// rastreo. Sin esta lista, abrir el alta de huesped desde una reservacion rapida
// (/huespedes/create?return_to=...) mostraba "no se pudo aplicar tu busqueda o
// filtro" en un formulario donde no habia ninguna busqueda — se veia como un
// error del sistema. Reportado desde el iPhone del owner.
const PARAMS_QUE_NO_FILTRAN = new Set([
  'return_to', 'nc', 'v', '_', 'ref', 'from',
  'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid',
]);

function pidioBusquedaOFiltro(url) {
  try {
    for (const clave of new URL(url).searchParams.keys()) {
      if (!PARAMS_QUE_NO_FILTRAN.has(clave)) return true;
    }
  } catch {}
  return false;
}

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

function cintaOfflineHtml(fechaHttp, filtroNoAplicado, lenta) {
  const edad = edadHumana(fechaHttp);
  const cuando = edad
    ? `Estás viendo información guardada <strong>${edad}</strong>`
    : 'Estás viendo la última información guardada en este equipo';
  const filtro = filtroNoAplicado
    ? ' · <strong>no se pudo aplicar tu búsqueda o filtro</strong>, esto es la lista completa guardada'
    : '';

  // Dos avisos distintos a proposito. Decirle "sin conexión" a alguien que SI
  // tiene internet (lento, pero vivo) lo manda a reiniciar el módem y a
  // desconfiar del sistema; lo que de verdad pasó es que no lo hicimos esperar.
  const encabezado = lenta ? 'Tu internet está muy lento. ' : 'Sin conexión. ';
  const cierre = lenta
    ? '. Guardar cambios puede tardar o fallar mientras siga así.'
    : '. Para guardar cambios hace falta internet.';
  const icono = lenta ? '&#9203;' : '&#128246;'; // reloj de arena / antena sin senal
  const fondo = lenta ? '#1F3B63' : '#7C4A03';

  // Estilos EN LINEA a proposito: la cinta debe verse aunque el CSS no cachee.
  return '<div id="ms-offline-cinta" role="status" aria-live="polite"' +
    ' data-motivo="' + (lenta ? 'lenta' : 'sin-conexion') + '" style="' +
    'display:flex;gap:8px;align-items:flex-start;padding:9px 14px;' +
    'background:' + fondo + ';color:#FFF8EC;font-size:13px;line-height:1.35;' +
    'font-weight:600;position:relative;z-index:2147483000;' +
    'font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">' +
    '<span aria-hidden="true">' + icono + '</span><span>' + encabezado + cuando + filtro +
    cierre + '</span></div>';
}

/**
 * Copia cacheada + cinta. Si algo no cuadra (no es HTML, no hay <body>, falla el
 * parseo) devuelve la copia INTACTA: es un aviso, jamas debe impedir que la
 * pantalla se vea.
 */
async function conCintaOffline(respuesta, filtroNoAplicado = false, lenta = false) {
  try {
    if (!respuesta) return respuesta;

    const tipo = respuesta.headers.get('content-type') || '';
    if (!tipo.includes('text/html')) return respuesta;

    const html = await respuesta.clone().text();
    const cinta = cintaOfflineHtml(respuesta.headers.get('date'), filtroNoAplicado, lenta);
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
async function respuestaOffline(lenta = false) {
  const offline = await caches.match(BASE + 'offline.html');
  if (offline) return lenta ? conMotivoLento(offline) : offline;

  return new Response(lenta
    ? '<h1>Medisoft Hoteles</h1><p>Tu internet está muy lento y la pantalla no alcanzó a cargar. Vuelve a intentarlo.</p>'
    : '<h1>Medisoft Hoteles</h1><p>Sin conexión. Vuelve a intentarlo cuando tengas internet.</p>', {
    status: 503,
    headers: { 'Content-Type': 'text/html' },
  });
}

/**
 * El muro es un archivo estatico, asi que para que sepa distinguir "no hay
 * internet" de "lo hay pero no responde" se le pone la bandera antes de su
 * propio script. Si algo no cuadra devuelve el muro intacto: un aviso jamas debe
 * impedir que la pantalla se vea.
 */
async function conMotivoLento(respuesta) {
  try {
    const html = await respuesta.clone().text();
    const marca = '<script>window.MEDISOFT_RED_LENTA=true;</script>';
    const conMarca = html.replace(/<body([^>]*)>/i, (m, attrs) => `<body${attrs}>${marca}`);
    if (conMarca === html) return respuesta;

    const headers = new Headers(respuesta.headers);
    headers.delete('content-length');

    return new Response(conMarca, {
      status: respuesta.status,
      statusText: respuesta.statusText,
      headers,
    });
  } catch {
    return respuesta;
  }
}

/**
 * Arranque de la PWA (start_url / login). Con red se comporta como siempre; sin
 * red entrega la mejor pantalla guardada para que la app ABRA en vez de morir en
 * el muro. La URL queda en la del login hasta la siguiente carga con red: es
 * cosmetico y preferible a no poder trabajar. Nunca se cachea esta respuesta
 * (son credenciales y casi siempre un redirect).
 */
async function arranquePage(request, event) {
  const red = fetch(request);
  mantenerVivo(event, red);

  // Es el momento mas caro de todos: el hotelero le picó al ícono y no ve NADA,
  // ni siquiera la pantalla anterior. Un splash congelado medio minuto es lo que
  // se recuerda como "esta app no sirve".
  const pronto = await conLimite(red, ESPERA.arranque);
  if (pronto instanceof Response) {
    notifyClients({ type: 'ONLINE' });
    return pronto;
  }

  const guardada = await mejorPantallaGuardada();
  if (guardada) {
    const lenta = pronto === AGOTADO;
    notifyClients({ type: 'OFFLINE', lenta });
    return conCintaOffline(guardada, false, lenta);
  }

  const tarde = pronto === AGOTADO
    ? await conLimite(red, ESPERA.sinCopia - ESPERA.arranque)
    : pronto;

  if (tarde instanceof Response) {
    notifyClients({ type: 'ONLINE' });
    return tarde;
  }

  const lenta = tarde === AGOTADO;
  notifyClients({ type: 'OFFLINE', lenta });
  return respuestaOffline(lenta);
}

async function clearPagesCache() {
  await caches.delete(CACHE.pages);
  console.log('[SW] Cache de pantallas limpiado (logout o cambio de hotel)');
}

/**
 * Network Only para HTML privado/dinamico: no guarda pantallas con datos de
 * hotel. Como no hay copia posible, el plazo es el largo: rendirse pronto solo
 * convertiria "lento" en "fallo". Aun asi se rinde — el muro dice qué pasa y
 * ofrece volver a lo que sí está guardado, que es mejor que una pantalla
 * congelada sin explicación.
 */
async function networkOnlyPage(request, event) {
  const red = fetch(request);
  mantenerVivo(event, red);

  const resultado = await conLimite(red, ESPERA.sinCopia);
  if (resultado instanceof Response) {
    notifyClients({ type: 'ONLINE' });
    return resultado;
  }

  const lenta = resultado === AGOTADO;
  notifyClients({ type: 'OFFLINE', lenta });
  return respuestaOffline(lenta);
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
