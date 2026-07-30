/**
 * Arnes que carga el service-worker.js REAL y le corta la red.
 * Verifica el arreglo: arrancar la PWA sin internet debe ENTRAR a la app,
 * no morir en offline.html.
 */
const fs = require('fs');

const SRC = fs.readFileSync('src/public_html/service-worker.js', 'utf8');
const ORIGIN = 'https://medisoft-hoteles.com';

function nuevoEntorno({ online }) {
  const stores = new Map();

  /**
   * Simula el Vary REAL de produccion. Apache manda `Vary: Accept-Encoding,User-Agent`
   * y las paginas se guardan con la Request de navegacion (con User-Agent), asi que
   * buscarlas por URL string NO casa salvo que se pase `ignoreVary`. Sin esta regla
   * el arnes daba verde a un fallback que en el navegador real nunca encontraba nada
   * (bug real cazado en QA el 30-jul-2026; no simplificar este mock).
   */
  class FakeCache {
    constructor() { this.map = new Map(); }
    async put(req, res) {
      const url = typeof req === 'string' ? req : req.url;
      // Guardado por Request de navegacion => la entrada "recuerda" su User-Agent
      const conUA = typeof req !== 'string';
      this.map.set(url, { res, conUA });
    }
    async match(req, opts = {}) {
      const url = typeof req === 'string' ? req : req.url;
      const buscaConUA = typeof req !== 'string';
      const casaVary = (e) => opts.ignoreVary === true || e.conUA === buscaConUA;

      const exacta = this.map.get(url);
      if (exacta && casaVary(exacta)) return exacta.res;

      if (opts.ignoreSearch) {
        const base = url.split('?')[0];
        for (const [k, e] of this.map) {
          if (k.split('?')[0] === base && casaVary(e)) return e.res;
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
  const self = {
    registration: { scope: ORIGIN + '/' },
    location: { origin: ORIGIN },
    addEventListener: (t, f) => { (listeners[t] = listeners[t] || []).push(f); },
    skipWaiting: () => {},
    clients: { matchAll: async () => [], claim: async () => {} },
  };

  const fetchMock = async (req) => {
    if (!online) throw new TypeError('Failed to fetch');
    const url = typeof req === 'string' ? req : req.url;
    return new Response('<html>RED: ' + url + '</html>', {
      status: 200,
      headers: { 'Content-Type': 'text/html' },
    });
  };

  const silencio = { log() {}, warn() {}, info() {}, error() {} };
  new Function('self', 'caches', 'fetch', 'console', SRC)(self, caches, fetchMock, silencio);

  const dispararFetch = (url) => new Promise((resolve, reject) => {
    const request = new Request(url, { headers: { accept: 'text/html' } });
    const event = { request, respondWith: p => Promise.resolve(p).then(resolve, reject), waitUntil: () => {} };
    listeners.fetch[0](event);
  });

  const dispararPost = (url) => new Promise((resolve, reject) => {
    const request = new Request(url, { method: 'POST', body: 'x=1' });
    const event = { request, respondWith: p => Promise.resolve(p).then(resolve, reject), waitUntil: () => {} };
    listeners.fetch[0](event);
  });

  return { caches, stores, dispararFetch, dispararPost };
}

// Las pantallas se guardan con la Request de NAVEGACION real (lleva User-Agent),
// como hace networkFirstPage en produccion.
async function sembrarPagina(env, ruta, cuerpo) {
  const cache = await env.caches.open('loscedros-pages');
  const request = new Request(ORIGIN + '/' + ruta, { headers: { accept: 'text/html' } });
  await cache.put(request, new Response(cuerpo, {
    status: 200,
    headers: { 'Content-Type': 'text/html', 'Vary': 'Accept-Encoding,User-Agent' },
  }));
}

// El shell se precachea con `cache.add(string)`: sin User-Agent de ninguno de los
// dos lados, por eso el muro siempre caso aun con Vary.
async function sembrarShellOffline(env) {
  const cache = await env.caches.open('loscedros-shell-v25');
  await cache.put(ORIGIN + '/offline.html', new Response('MURO SIN CONEXION', {
    status: 200,
    headers: { 'Content-Type': 'text/html', 'Vary': 'Accept-Encoding,User-Agent' },
  }));
}

const casos = [];
function caso(nombre, fn) { casos.push([nombre, fn]); }

// ── El bug reportado: arrancar la PWA sin internet ───────────────────────────
caso('SIN RED: arranque en /h/{slug}/login CON dashboard guardado -> entra al dashboard', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'dashboard', 'DASHBOARD DEL HOTEL');
  const res = await env.dispararFetch(ORIGIN + '/h/hotel-los-cedros/login');
  return [await res.text(), 'DASHBOARD DEL HOTEL'];
});

caso('SIN RED: arranque en la raiz / CON habitaciones guardado -> entra a habitaciones', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'habitaciones', 'HABITACIONES');
  const res = await env.dispararFetch(ORIGIN + '/');
  return [await res.text(), 'HABITACIONES'];
});

// Rol acotado a Limpieza: su home es /camarista y NUNCA tendra dashboard guardado
// (DashboardController lo rebota). Sin camarista en las puertas de entrada, su
// arranque offline caia al muro — y es quien mas trabaja con mala señal.
caso('SIN RED: camarista (sin dashboard guardado) -> entra a su tablero', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'camarista', 'TABLERO DE LIMPIEZA');
  const res = await env.dispararFetch(ORIGIN + '/h/hotel-los-cedros/login');
  return [await res.text(), 'TABLERO DE LIMPIEZA'];
});

caso('SIN RED: /camarista es ruta operativa y sirve SU copia', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'camarista', 'TABLERO DE LIMPIEZA');
  const res = await env.dispararFetch(ORIGIN + '/camarista');
  return [await res.text(), 'TABLERO DE LIMPIEZA'];
});

caso('CON RED: /camarista se GUARDA como copia buena', async () => {
  const env = nuevoEntorno({ online: true });
  await env.dispararFetch(ORIGIN + '/camarista');
  await new Promise(r => setImmediate(r));
  const cache = await env.caches.open('loscedros-pages');
  const guardada = await cache.match(ORIGIN + '/camarista', { ignoreVary: true });
  return [guardada ? 'GUARDADA' : 'NO GUARDADA', 'GUARDADA'];
});

caso('Con dashboard Y camarista guardados manda el dashboard (gerente)', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'camarista', 'TABLERO DE LIMPIEZA');
  await sembrarPagina(env, 'dashboard', 'DASHBOARD DEL HOTEL');
  const res = await env.dispararFetch(ORIGIN + '/');
  return [await res.text(), 'DASHBOARD DEL HOTEL'];
});

caso('SIN RED: arranque SIN nada guardado -> muro (comportamiento honesto)', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  const res = await env.dispararFetch(ORIGIN + '/h/hotel-los-cedros/login');
  return [await res.text(), 'MURO SIN CONEXION'];
});

caso('SIN RED: /habitaciones con su copia -> sirve SU copia, no el dashboard', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'dashboard', 'DASHBOARD DEL HOTEL');
  await sembrarPagina(env, 'habitaciones', 'HABITACIONES');
  const res = await env.dispararFetch(ORIGIN + '/habitaciones');
  return [await res.text(), 'HABITACIONES'];
});

caso('SIN RED: /reportes (no operativa) -> muro, NO suplanta con otra pantalla', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'dashboard', 'DASHBOARD DEL HOTEL');
  const res = await env.dispararFetch(ORIGIN + '/reportes');
  return [await res.text(), 'MURO SIN CONEXION'];
});

// El offline es de SOLO LECTURA (escrituras apagadas, /api/sync = 423): un POST
// que falla NO se reintenta despues. El SW prometia "se ejecutara cuando vuelva
// internet" — la misma mentira que se quito de los interceptores el 26-jul, que
// sobrevivio aqui hasta jul-30. Este caso existe para que no vuelva.
caso('SIN RED: un POST fallido NO promete que se enviara despues', async () => {
  const env = nuevoEntorno({ online: false });
  const res = await env.dispararPost(ORIGIN + '/huespedes/store');
  const cuerpo = await res.json();
  const prometeEnvio = /se ejecutar|se enviar|cuando vuelva internet/i.test(cuerpo.message || '');
  const diceQueNoSeGuardo = /no qued|no se guard/i.test(cuerpo.message || '');
  return [
    `promete=${prometeEnvio} avisa_que_no_se_guardo=${diceQueNoSeGuardo}`,
    'promete=false avisa_que_no_se_guardo=true',
  ];
});

// ── Cinta "estos datos son de antes" ─────────────────────────────────────────
// Una pantalla del cache se ve IDENTICA a la de internet. Sin este aviso,
// recepcion vende un cuarto que se ocupo hace horas. Los 4 avisos que vivian en
// las vistas estaban muertos (un `return;` al inicio); ahora lo pone el SW.

async function textoCinta(res) {
  const html = await res.text();
  const m = html.match(/<div id="ms-offline-cinta"[\s\S]*?<\/div>/);
  return m ? m[0].replace(/<[^>]+>/g, '').replace(/&#\d+;/g, '').replace(/\s+/g, ' ').trim() : null;
}

caso('SIN RED: la pantalla servida del cache AVISA que los datos son de antes', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'habitaciones', '<html><body>HABITACIONES</body></html>');
  const res = await env.dispararFetch(ORIGIN + '/habitaciones');
  const t = await textoCinta(res);
  return [t && /Sin conexión/.test(t) && /guardada/.test(t) ? 'AVISA' : 'NO AVISA: ' + t, 'AVISA'];
});

caso('SIN RED: pedir una BUSQUEDA sin copia exacta avisa que el filtro no se aplico', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'huespedes', '<html><body>LISTA COMPLETA</body></html>');
  const res = await env.dispararFetch(ORIGIN + '/huespedes?buscar=Ramirez');
  const t = await textoCinta(res);
  return [/no se pudo aplicar tu búsqueda o filtro/.test(t || '') ? 'AVISA DEL FILTRO' : 'NO AVISA: ' + t, 'AVISA DEL FILTRO'];
});

caso('SIN RED: con copia EXACTA no inventa un aviso de filtro', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'huespedes', '<html><body>LISTA</body></html>');
  const res = await env.dispararFetch(ORIGIN + '/huespedes');
  const t = await textoCinta(res);
  return [/no se pudo aplicar/.test(t || '') ? 'AVISA DE MAS' : 'CORRECTO', 'CORRECTO'];
});

caso('CON RED: la pantalla NO lleva cinta (no ensuciar el uso normal)', async () => {
  const env = nuevoEntorno({ online: true });
  const res = await env.dispararFetch(ORIGIN + '/habitaciones');
  const t = await textoCinta(res);
  return [t === null ? 'SIN CINTA' : 'CINTA INDEBIDA: ' + t, 'SIN CINTA'];
});

caso('La cinta NO se inyecta en respuestas que no son HTML', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  const cache = await env.caches.open('loscedros-pages');
  const req = new Request(ORIGIN + '/dashboard', { headers: { accept: 'text/html' } });
  await cache.put(req, new Response('{"a":1}', {
    status: 200,
    headers: { 'Content-Type': 'application/json', 'Vary': 'Accept-Encoding,User-Agent' },
  }));
  const res = await env.dispararFetch(ORIGIN + '/dashboard');
  const cuerpo = await res.text();
  return [cuerpo === '{"a":1}' ? 'INTACTA' : 'MODIFICADA: ' + cuerpo, 'INTACTA'];
});

// ── Que el arreglo no rompa el camino normal ─────────────────────────────────
caso('CON RED: /dashboard -> responde la red y GUARDA la copia buena', async () => {
  const env = nuevoEntorno({ online: true });
  await env.dispararFetch(ORIGIN + '/dashboard');
  await new Promise(r => setImmediate(r));
  const cache = await env.caches.open('loscedros-pages');
  // ignoreVary tambien aqui: se guardo con Request de navegacion y buscamos por URL
  const guardada = await cache.match(ORIGIN + '/dashboard', { ignoreVary: true });
  return [guardada ? 'GUARDADA' : 'NO GUARDADA', 'GUARDADA'];
});

caso('CON RED: el login NUNCA se guarda (son credenciales)', async () => {
  const env = nuevoEntorno({ online: true });
  await env.dispararFetch(ORIGIN + '/h/hotel-los-cedros/login');
  await new Promise(r => setImmediate(r));
  const cache = await env.caches.open('loscedros-pages');
  const guardada = await cache.match(ORIGIN + '/h/hotel-los-cedros/login');
  return [guardada ? 'GUARDADA' : 'NO GUARDADA', 'NO GUARDADA'];
});

// Regresion del bug cazado en el navegador el 30-jul-2026: sin `ignoreVary` el
// fallback NUNCA encontraba la copia en produccion (Apache manda Vary: User-Agent)
// y el arranque seguia muriendo en el muro pese a tener el dashboard guardado.
caso('SIN RED: encuentra la copia AUNQUE la respuesta traiga Vary: User-Agent', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'dashboard', 'DASHBOARD CON VARY');
  const res = await env.dispararFetch(ORIGIN + '/h/hotel-los-cedros/login');
  return [await res.text(), 'DASHBOARD CON VARY'];
});

caso('SIN RED: /habitaciones tambien casa con Vary (ruta operativa, no arranque)', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'habitaciones', 'HABITACIONES CON VARY');
  const res = await env.dispararFetch(ORIGIN + '/habitaciones');
  return [await res.text(), 'HABITACIONES CON VARY'];
});

caso('SIN RED: tras logout (cache de pantallas limpio) -> muro, no la sesion anterior', async () => {
  const env = nuevoEntorno({ online: false });
  await sembrarShellOffline(env);
  await sembrarPagina(env, 'dashboard', 'DASHBOARD DEL HOTEL');
  await env.caches.delete('loscedros-pages'); // lo que hace CLEAR_PAGES_CACHE
  const res = await env.dispararFetch(ORIGIN + '/h/hotel-los-cedros/login');
  return [await res.text(), 'MURO SIN CONEXION'];
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
