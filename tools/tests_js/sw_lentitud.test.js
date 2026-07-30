/**
 * Arnes que carga el service-worker.js REAL y le pone una red LENTA (no caida).
 * Verifica el arreglo de v28: con senal debil el SW ya no espera a que el
 * navegador se rinda (30 s o mas) — sirve lo guardado en segundos y lo DICE.
 *
 * Tarda ~40 s a proposito: mide plazos reales del archivo, sin tocarle los
 * numeros. Si se reescribieran aqui estariamos probando una copia, no el SW.
 *
 * Correr desde la raiz del repo: node tools/tests_js/sw_lentitud.test.js
 */
const fs = require('fs');

const SRC = fs.readFileSync('src/public_html/service-worker.js', 'utf8');
const ORIGIN = 'https://medisoft-hoteles.com';

// Redes falsas. NUNCA es la mala senal de verdad: la antena responde el
// handshake y despues nada, que es justo lo que `fetch` no sabe reportar.
const NUNCA = () => new Promise(() => {});
const CAE = () => Promise.reject(new TypeError('Failed to fetch'));
const RAPIDO = () => Promise.resolve(paginaHtml('FRESCO'));
const TARDA = (ms) => () => new Promise(r => setTimeout(() => r(paginaHtml('FRESCO')), ms));

function paginaHtml(cuerpo, minutosDeAntiguedad = 0) {
  return new Response('<html><body>' + cuerpo + '</body></html>', {
    status: 200,
    headers: {
      'Content-Type': 'text/html; charset=utf-8',
      'Vary': 'Accept-Encoding,User-Agent',
      'Date': new Date(Date.now() - minutosDeAntiguedad * 60000).toUTCString(),
    },
  });
}

function nuevoEntorno(fetchMock) {
  const stores = new Map();

  /**
   * Mismo mock de Vary que `sw_arranque.test.js` (se duplica a proposito: cada
   * arnes es autocontenido). Apache manda `Vary: Accept-Encoding,User-Agent` y
   * las paginas se guardan con la Request de navegacion, asi que buscarlas por
   * URL string NO casa salvo con `ignoreVary`. Sin esta regla el arnes da verde
   * a un fallback que en el navegador real nunca encuentra nada.
   */
  class FakeCache {
    constructor() { this.map = new Map(); }
    async put(req, res) {
      const url = typeof req === 'string' ? req : req.url;
      this.map.set(url, { res, conUA: typeof req !== 'string' });
    }
    async match(req, opts = {}) {
      const url = typeof req === 'string' ? req : req.url;
      const buscaConUA = typeof req !== 'string';
      const casaVary = (e) => opts.ignoreVary === true || e.conUA === buscaConUA;

      const exacta = this.map.get(url);
      if (exacta && casaVary(exacta)) return exacta.res.clone();

      if (opts.ignoreSearch) {
        const base = url.split('?')[0];
        for (const [k, e] of this.map) {
          if (k.split('?')[0] === base && casaVary(e)) return e.res.clone();
        }
      }
      return undefined;
    }
    async keys() { return [...this.map.keys()].map(u => ({ url: u })); }
    async delete() { return true; }
  }

  const caches = {
    async open(name) {
      if (!stores.has(name)) stores.set(name, new FakeCache());
      return stores.get(name);
    },
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
  const avisos = [];
  const pendientes = [];

  const self = {
    registration: { scope: ORIGIN + '/' },
    location: { origin: ORIGIN },
    addEventListener: (t, f) => { (listeners[t] = listeners[t] || []).push(f); },
    skipWaiting: () => {},
    clients: {
      matchAll: async () => [{ postMessage: (m) => avisos.push(m) }],
      claim: async () => {},
    },
  };

  const silencio = { log() {}, warn() {}, info() {}, error() {} };
  new Function('self', 'caches', 'fetch', 'console', SRC)(self, caches, fetchMock, silencio);

  const pedir = (request) => new Promise((resolve, reject) => {
    listeners.fetch[0]({
      request,
      respondWith: p => Promise.resolve(p).then(resolve, reject),
      waitUntil: p => pendientes.push(Promise.resolve(p).catch(() => {})),
    });
  });

  return { caches, avisos, pendientes, pedir };
}

const navegacion = (ruta, cabeceras = {}) =>
  new Request(ORIGIN + '/' + ruta, { headers: Object.assign({ accept: 'text/html' }, cabeceras) });

const ajax = (ruta) =>
  new Request(ORIGIN + '/' + ruta, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

// Las pantallas se guardan con la Request de NAVEGACION real, como en produccion.
async function sembrarPagina(env, ruta, cuerpo, minutos = 0) {
  const cache = await env.caches.open('loscedros-pages');
  await cache.put(navegacion(ruta), paginaHtml(cuerpo, minutos));
}

async function sembrarMuro(env) {
  const cache = await env.caches.open('loscedros-shell');
  await cache.put(ORIGIN + '/offline.html', new Response(
    '<html><body><h1 id="offline-title">Sin conexión</h1></body></html>',
    { status: 200, headers: { 'Content-Type': 'text/html' } }
  ));
}

async function cronometrar(fn) {
  const t0 = Date.now();
  const valor = await fn();
  return { valor, ms: Date.now() - t0 };
}

// El plazo se cumple "alrededor de": se afirma la ventana, no el milisegundo.
function dentroDe(ms, min, max) {
  return (ms >= min && ms <= max) ? 'EN PLAZO' : 'FUERA: ' + ms + 'ms (esperado ' + min + '-' + max + ')';
}

const casos = [];
function caso(nombre, fn) { casos.push([nombre, fn]); }

// ── El bug: la pantalla congelada mientras la copia esperaba al lado ──────────
caso('LENTA: pantalla con copia guardada -> responde a los ~3 s, no a los 30', async () => {
  const env = nuevoEntorno(NUNCA);
  await sembrarPagina(env, 'dashboard', 'GUARDADO', 12);
  const { ms } = await cronometrar(() => env.pedir(navegacion('dashboard')));
  return [dentroDe(ms, 2900, 4500), 'EN PLAZO'];
});

caso('LENTA: sirve la copia y dice que el internet esta lento (no que no hay)', async () => {
  const env = nuevoEntorno(NUNCA);
  await sembrarPagina(env, 'dashboard', 'GUARDADO', 12);
  const html = await (await env.pedir(navegacion('dashboard'))).text();
  const ok = html.includes('GUARDADO') &&
    html.includes('data-motivo="lenta"') &&
    html.includes('Tu internet está muy lento') &&
    html.includes('hace 12 minutos') &&
    !html.includes('Sin conexión. Estás');
  return [ok ? 'COPIA + AVISO LENTO' : 'MAL: ' + html.slice(0, 200), 'COPIA + AVISO LENTO'];
});

caso('LENTA: le avisa a la app con lenta:true (para el toast y la etiqueta)', async () => {
  const env = nuevoEntorno(NUNCA);
  await sembrarPagina(env, 'dashboard', 'GUARDADO');
  await env.pedir(navegacion('dashboard'));
  const aviso = env.avisos.find(a => a.type === 'OFFLINE');
  return [aviso ? 'OFFLINE lenta=' + aviso.lenta : 'SIN AVISO', 'OFFLINE lenta=true'];
});

// ── Lo que ya funcionaba no debe pagar el arreglo ────────────────────────────
caso('CAIDA: sigue siendo instantanea y sigue diciendo "Sin conexión"', async () => {
  const env = nuevoEntorno(CAE);
  await sembrarPagina(env, 'dashboard', 'GUARDADO', 3);
  const { valor, ms } = await cronometrar(() => env.pedir(navegacion('dashboard')));
  const html = await valor.text();
  const aviso = env.avisos.find(a => a.type === 'OFFLINE');
  const ok = ms < 400 && html.includes('data-motivo="sin-conexion"') &&
    html.includes('Sin conexión.') && aviso.lenta === false;
  return [ok ? 'INSTANTANEA Y SIN CONEXION' : 'MAL (' + ms + 'ms)', 'INSTANTANEA Y SIN CONEXION'];
});

caso('RED BUENA: no paga ninguna latencia nueva ni ensucia la pantalla', async () => {
  const env = nuevoEntorno(RAPIDO);
  const { valor, ms } = await cronometrar(() => env.pedir(navegacion('dashboard')));
  const html = await valor.text();
  const ok = ms < 300 && html.includes('FRESCO') && !html.includes('ms-offline-cinta') &&
    !env.avisos.some(a => a.type === 'OFFLINE');
  return [ok ? 'LIMPIO' : 'MAL (' + ms + 'ms)', 'LIMPIO'];
});

caso('LENTA: la respuesta que llega tarde deja el cache al dia', async () => {
  const env = nuevoEntorno(TARDA(4500));
  await sembrarPagina(env, 'dashboard', 'GUARDADO', 30);

  const { valor } = await cronometrar(() => env.pedir(navegacion('dashboard')));
  const servido = await valor.text();
  await Promise.all(env.pendientes);

  const cache = await env.caches.open('loscedros-pages');
  const guardada = await cache.match(navegacion('dashboard'));
  const ok = servido.includes('GUARDADO') && (await guardada.text()).includes('FRESCO');
  return [ok ? 'SIRVE VIEJO, GUARDA NUEVO' : 'MAL', 'SIRVE VIEJO, GUARDA NUEVO'];
});

// ── Sin nada que ofrecer, la paciencia es otra ───────────────────────────────
caso('LENTA sin copia: aguanta los 9 s completos antes de rendirse', async () => {
  const env = nuevoEntorno(NUNCA);
  await sembrarMuro(env);
  const { ms } = await cronometrar(() => env.pedir(navegacion('caja')));
  return [dentroDe(ms, 8800, 11000), 'EN PLAZO'];
});

caso('LENTA sin copia: el muro recibe la bandera para no acusar al modem', async () => {
  const env = nuevoEntorno(NUNCA);
  await sembrarMuro(env);
  const html = await (await env.pedir(navegacion('reportes'))).text();
  return [html.includes('MEDISOFT_RED_LENTA=true') ? 'CON BANDERA' : 'SIN BANDERA', 'CON BANDERA'];
});

// ── Arranque: el momento en que no se ve NADA ────────────────────────────────
caso('LENTA: abrir la app entra a lo guardado a los ~4 s, sin splash congelado', async () => {
  const env = nuevoEntorno(NUNCA);
  await sembrarPagina(env, 'habitaciones', 'HABITACIONES', 45);
  const { valor, ms } = await cronometrar(() => env.pedir(navegacion('h/los-cedros/login')));
  const html = await valor.text();
  const ok = html.includes('HABITACIONES') && html.includes('data-motivo="lenta"');
  return [dentroDe(ms, 3900, 5500) + (ok ? '' : ' PERO CONTENIDO MAL'), 'EN PLAZO'];
});

// ── API: el widget que gira para siempre ─────────────────────────────────────
caso('LENTA: una API cuelga -> 503 a los ~7 s, cancelada y marcada como lenta', async () => {
  let abortada = false;
  const env = nuevoEntorno((req, init) => new Promise((_, rechazar) => {
    init?.signal?.addEventListener('abort', () => { abortada = true; rechazar(new Error('abort')); });
  }));

  const { valor, ms } = await cronometrar(() => env.pedir(ajax('api/dashboard/estacionamiento-proyeccion')));
  const cuerpo = await valor.json();
  const ok = valor.status === 503 && cuerpo.offline === true && cuerpo.lenta === true && abortada;
  return [dentroDe(ms, 6800, 8500) + (ok ? '' : ' PERO RESPUESTA MAL'), 'EN PLAZO'];
});

// ── Assets: la pantalla que llega desnuda ────────────────────────────────────
caso('LENTA: un css con ?v= nuevo cae a la version anterior en ~2.5 s', async () => {
  const env = nuevoEntorno(NUNCA);
  const cache = await env.caches.open('loscedros-shell');
  await cache.put(ORIGIN + '/css/custom.css', new Response('.viejo{}', {
    headers: { 'Content-Type': 'text/css' },
  }));

  const { valor, ms } = await cronometrar(() => env.pedir(new Request(ORIGIN + '/css/custom.css?v=999')));
  const ok = (await valor.text()).includes('.viejo');
  return [dentroDe(ms, 2400, 4000) + (ok ? '' : ' PERO CONTENIDO MAL'), 'EN PLAZO'];
});

// ── Precarga: no contaminar el cache de especulacion del navegador ───────────
caso('La precarga de instant-nav NO recibe copia guardada (fallaria el clic)', async () => {
  const env = nuevoEntorno(CAE);
  await sembrarPagina(env, 'reservaciones', 'GUARDADO', 8);

  let fallo = false;
  const valor = await env.pedir(navegacion('reservaciones', { 'Sec-Purpose': 'prefetch' }))
    .catch(() => { fallo = true; });

  return [fallo && valor === undefined ? 'FALLA LIMPIO' : 'SIRVIO COPIA', 'FALLA LIMPIO'];
});

caso('La precarga con red buena sigue alimentando la memoria offline', async () => {
  const env = nuevoEntorno(RAPIDO);
  await env.pedir(navegacion('reservaciones', { 'Sec-Purpose': 'prefetch' }));
  await Promise.all(env.pendientes);

  const cache = await env.caches.open('loscedros-pages');
  const guardada = await cache.match(navegacion('reservaciones'));
  return [guardada ? 'GUARDADA' : 'NO GUARDADA', 'GUARDADA'];
});

// ── Regresion de lo que ya vivia en v27 ──────────────────────────────────────
caso('CAIDA: el aviso de filtro no aplicado sobrevivio a la reorganizacion', async () => {
  const env = nuevoEntorno(CAE);
  await sembrarPagina(env, 'huespedes', 'LISTA', 5);
  const html = await (await env.pedir(navegacion('huespedes?buscar=Ramirez'))).text();
  return [html.includes('no se pudo aplicar tu búsqueda o filtro') ? 'AVISA' : 'NO AVISA', 'AVISA'];
});

// La lista blanca de parametros llego con Ola 1 y no tenia arnes; el codigo que
// la usa se movio a `copiaGuardadaDePagina` en v28, asi que se afirma aqui.
caso('CAIDA: un parametro de navegacion (return_to) NO cuenta como filtro', async () => {
  const env = nuevoEntorno(CAE);
  await sembrarPagina(env, 'huespedes', 'ALTA', 5);
  const html = await (await env.pedir(navegacion('huespedes?return_to=%2Freservaciones%2Fcrear&nc=3'))).text();
  return [html.includes('no se pudo aplicar tu búsqueda') ? 'AVISA DE MAS' : 'CALLA', 'CALLA'];
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
