/**
 * Arnes del cambio de hotel en el service-worker REAL (v31).
 *
 * Hasta v30, cambiar de hotel BORRABA todas las pantallas guardadas: un usuario con
 * varios hoteles perdia su memoria offline cada vez que alternaba, y volver a
 * llenarla exige pasar por cada pantalla con red. Ahora se archivan por scope.
 *
 * El caso que mas importa NO es el que se gano, es el que no se puede perder: al
 * CERRAR SESION tiene que desaparecer todo, incluidos los archivos de los otros
 * hoteles — el equipo puede cambiar de manos y ese HTML lleva nombres de huespedes
 * y movimientos de caja.
 *
 * Correr desde la raiz del repo: node tools/tests_js/sw_multihotel.test.js
 */
const fs = require('fs');

const SRC = fs.readFileSync('src/public_html/service-worker.js', 'utf8');
const ORIGIN = 'https://medisoft-hoteles.com';

function nuevoEntorno() {
  const stores = new Map();

  class FakeCache {
    constructor() { this.map = new Map(); }
    async put(req, res) {
      const url = typeof req === 'string' ? req : req.url;
      this.map.set(url, { res, conUA: typeof req !== 'string' });
    }
    async match(req, opts = {}) {
      const url = typeof req === 'string' ? req : req.url;
      // Una Request salida de keys() recuerda como se guardo: en el navegador
      // real, cache.match(requestDeKeys) casa siempre con su propia entrada.
      const buscaConUA = typeof req === 'string' ? false : (req._conUA ?? true);
      const casa = (e) => opts.ignoreVary === true || e.conUA === buscaConUA;

      const exacta = this.map.get(url);
      if (exacta && casa(exacta)) return exacta.res;

      if (opts.ignoreSearch) {
        const base = url.split('?')[0];
        for (const [k, e] of this.map) {
          if (k.split('?')[0] === base && casa(e)) return e.res;
        }
      }
      return undefined;
    }
    async keys() {
      return [...this.map.entries()].map(([url, e]) => ({ url, _conUA: e.conUA }));
    }
    async delete(req) {
      const url = typeof req === 'string' ? req : req.url;
      return this.map.delete(url);
    }
  }

  const caches = {
    async open(name) {
      if (!stores.has(name)) stores.set(name, new FakeCache());
      return stores.get(name);
    },
    async has(name) { return stores.has(name); },
    async match(req, opts) {
      for (const c of stores.values()) {
        const r = await c.match(req, opts);
        if (r) return r;
      }
      return undefined;
    },
    async keys() { return [...stores.keys()]; },
    async delete(name) { return stores.delete(name); },
  };

  const listeners = {};
  const self = {
    registration: { scope: ORIGIN + '/' },
    location: { origin: ORIGIN },
    addEventListener: (t, f) => { (listeners[t] = listeners[t] || []).push(f); },
    skipWaiting: () => {},
    clients: { matchAll: async () => [], claim: async () => {} },
  };

  const fetchMock = async (req) => new Response('<html>RED</html>', {
    status: 200, headers: { 'Content-Type': 'text/html' },
  });
  const silencio = { log() {}, warn() {}, info() {}, error() {} };

  new Function('self', 'caches', 'fetch', 'console', SRC)(self, caches, fetchMock, silencio);

  const dispararMensaje = async (data) => {
    const pendientes = [];
    const event = { data, waitUntil: p => pendientes.push(p) };
    (listeners.message || []).forEach(fn => fn(event));
    await Promise.allSettled(pendientes);
  };

  const dispararActivate = async () => {
    const pendientes = [];
    const event = { waitUntil: p => pendientes.push(p) };
    (listeners.activate || []).forEach(fn => fn(event));
    await Promise.allSettled(pendientes);
  };

  /** Guarda una pantalla como lo hace la navegacion real (Request, no string). */
  const guardarPantalla = async (ruta, cuerpo) => {
    const cache = await caches.open('loscedros-pages');
    await cache.put(new Request(ORIGIN + '/' + ruta), new Response(cuerpo || ruta));
  };

  const pantallasActivas = async () => {
    if (!stores.has('loscedros-pages')) return [];
    return (await stores.get('loscedros-pages').keys()).map(r => r.url.replace(ORIGIN + '/', ''));
  };

  const archivos = async () => (await caches.keys()).filter(n => n.startsWith('loscedros-pages-'));

  const pantallasDe = async (scope) => {
    const nombre = 'loscedros-pages-' + scope;
    if (!stores.has(nombre)) return null;
    return (await stores.get(nombre).keys()).map(r => r.url.replace(ORIGIN + '/', ''));
  };

  return { caches, stores, dispararMensaje, dispararActivate, guardarPantalla, pantallasActivas, archivos, pantallasDe };
}

const casos = [];
const caso = (nombre, fn) => casos.push([nombre, fn]);

// ── Lo que se gana ──────────────────────────────────────────────────────────

caso('al cambiar de hotel, las pantallas del anterior se archivan', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.guardarPantalla('habitaciones');

  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-1', hacia: 'hotel-2' });

  const archivadas = await e.pantallasDe('hotel-1');
  return [archivadas ? archivadas.sort().join(',') : '(sin archivo)', 'dashboard,habitaciones'];
});

caso('el hotel nuevo NO hereda las pantallas del anterior', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');

  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-1', hacia: 'hotel-2' });

  // hotel-2 nunca tuvo pantallas: el cache activo tiene que quedar vacio.
  return [(await e.pantallasActivas()).length, 0];
});

caso('al volver al hotel anterior, sus pantallas se restauran', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.guardarPantalla('caja');

  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-1', hacia: 'hotel-2' });
  await e.guardarPantalla('reservaciones');            // trabajo en el hotel 2
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-2', hacia: 'hotel-1' });

  return [(await e.pantallasActivas()).sort().join(','), 'caja,dashboard'];
});

caso('ida y vuelta conserva los dos hoteles, no solo el ultimo', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-1', hacia: 'hotel-2' });
  await e.guardarPantalla('reservaciones');
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-2', hacia: 'hotel-1' });

  const h2 = await e.pantallasDe('hotel-2');
  return [(h2 || []).join(','), 'reservaciones'];
});

// ── Lo que NO se puede perder: privacidad ───────────────────────────────────

caso('el LOGOUT borra tambien los archivos de los otros hoteles', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-1', hacia: 'hotel-2' });
  await e.guardarPantalla('caja');
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-2', hacia: 'hotel-3' });
  await e.guardarPantalla('huespedes');

  // Tres hoteles con rastro: al cerrar sesion no puede quedar ninguno.
  await e.dispararMensaje({ type: 'CLEAR_PAGES_CACHE' });

  const quedan = (await e.caches.keys()).filter(n => n.startsWith('loscedros-pages'));
  return [quedan.join(',') || '(ninguno)', '(ninguno)'];
});

caso('tras el logout no queda ni una pantalla activa', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.dispararMensaje({ type: 'CLEAR_PAGES_CACHE' });
  return [(await e.pantallasActivas()).length, 0];
});

// ── Que el deploy no se lleve los archivos ──────────────────────────────────

caso('una version nueva del SW conserva los archivos por hotel', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-1', hacia: 'hotel-2' });

  // Simula el deploy anterior dejando su shell viejo, que SI debe morir.
  await e.caches.open('loscedros-shell-v29');
  await e.dispararActivate();

  const nombres = await e.caches.keys();
  return [
    (nombres.includes('loscedros-pages-hotel-1') ? 'archivo vive' : 'archivo BORRADO')
      + ' / ' + (nombres.includes('loscedros-shell-v29') ? 'shell viejo vive' : 'shell viejo borrado'),
    'archivo vive / shell viejo borrado',
  ];
});

// ── Robustez ────────────────────────────────────────────────────────────────

caso('un scope con caracteres raros se sanea antes de tocar caches', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: '../otro hotel!', hacia: 'hotel-2' });

  const nombres = await e.archivos();
  const limpio = nombres.every(n => /^loscedros-pages-[a-z0-9_-]*$/i.test(n));
  return [limpio, true];
});

caso('sin hotel anterior (primer login) no truena y arranca limpio', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: null, hacia: 'hotel-1' });
  return [(await e.pantallasActivas()).length, 0];
});

caso('cambiar dos veces al mismo hotel no duplica ni pierde', async () => {
  const e = nuevoEntorno();
  await e.guardarPantalla('dashboard');
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-1', hacia: 'hotel-2' });
  await e.guardarPantalla('caja');
  // Segundo archivado de hotel-1 estando ya vacio: no debe resucitar 'dashboard'
  await e.dispararMensaje({ type: 'SWITCH_PAGES_CACHE', desde: 'hotel-2', hacia: 'hotel-2' });
  const h1 = await e.pantallasDe('hotel-1');
  return [(h1 || []).join(','), 'dashboard'];
});

(async () => {
  let fallos = 0;
  for (const [nombre, fn] of casos) {
    try {
      const [real, esperado] = await fn();
      const ok = real === esperado;
      if (!ok) fallos++;
      console.log((ok ? '  OK  ' : ' FALLA') + ' | ' + nombre);
      if (!ok) console.log('        obtuvo: "' + real + '"  esperaba: "' + esperado + '"');
    } catch (e) {
      fallos++;
      console.log(' ERROR | ' + nombre + '\n        ' + e.message);
    }
  }
  console.log('\n' + (casos.length - fallos) + '/' + casos.length + ' correctos');
  process.exit(fallos ? 1 : 0);
})();
