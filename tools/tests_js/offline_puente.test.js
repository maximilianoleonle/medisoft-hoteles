/**
 * Ejecuta el <script> REAL de offline.html sobre un DOM minimo simulado.
 * Verifica que la pantalla deje de ser un callejon sin salida: enlaces a lo que
 * si esta guardado + conteo de la cola pendiente.
 */
const fs = require('fs');

const HTML = fs.readFileSync('src/public_html/offline.html', 'utf8');
const SCRIPT = [...HTML.matchAll(/<script>([\s\S]*?)<\/script>/g)][1][1];
const ORIGIN = 'https://medisoft-hoteles.com';

function nuevoDom() {
  const nodos = {};
  const crear = (id) => ({
    id, textContent: '', hidden: true, className: '', href: '', src: '', alt: '',
    hijos: [], style: {},
    appendChild(n) { this.hijos.push(n); },
    classList: { toggle() {}, add() {}, remove() {} },
  });

  ['hotelName', 'hotelLogo', 'hotelLogoFallback', 'offlineMensaje',
   'pendientesAviso', 'accesosOffline', 'accesosLista'].forEach(id => { nodos[id] = crear(id); });

  return {
    nodos,
    document: {
      documentElement: { style: { setProperty() {} } },
      title: '',
      getElementById: (id) => nodos[id] || null,
      createElement: () => crear('nuevo'),
      addEventListener() {},
    },
  };
}

function cacheStorageCon(rutas) {
  return {
    async open() {
      return { async keys() { return rutas.map(r => ({ url: ORIGIN + '/' + r })); } };
    },
  };
}

function indexedDbCon(operaciones) {
  return {
    open() {
      const solicitud = {};
      setImmediate(() => {
        solicitud.onsuccess({
          target: {
            result: {
              objectStoreNames: { contains: (n) => n === 'operaciones_offline' },
              transaction: () => ({
                objectStore: () => ({
                  getAll() {
                    const consulta = {};
                    setImmediate(() => { consulta.result = operaciones; consulta.onsuccess(); });
                    return consulta;
                  },
                }),
              }),
              close() {},
            },
          },
        });
      });
      return solicitud;
    },
  };
}

async function correr({ rutasEnCache = [], operaciones = [], conIndexedDb = true }) {
  const dom = nuevoDom();
  const almacen = { 'loscedros_offline_db_name': 'medisoft-hotel-2-user-1' };

  const window = {
    localStorage: {
      getItem: (k) => (k in almacen ? almacen[k] : null),
      setItem() {},
    },
    caches: cacheStorageCon(rutasEnCache),
    indexedDB: conIndexedDb ? indexedDbCon(operaciones) : null,
    location: { origin: ORIGIN, reload() {} },
    addEventListener() {},
  };

  new Function('window', 'document', 'URL', 'setTimeout', 'console', SCRIPT)(
    window, dom.document, URL, setTimeout, { log() {}, warn() {}, error() {} }
  );

  await new Promise(r => setTimeout(r, 30));
  return dom.nodos;
}

const casos = [];
function caso(nombre, fn) { casos.push([nombre, fn]); }

caso('Con dashboard, habitaciones y caja guardados -> pinta esos 3 accesos', async () => {
  const n = await correr({ rutasEnCache: ['dashboard', 'habitaciones', 'caja'] });
  const etiquetas = n.accesosLista.hijos.map(h => h.textContent).join(', ');
  return [etiquetas, 'Inicio, Habitaciones, Caja'];
});

caso('El tablero de Limpieza aparece con su nombre del menu, no "camarista"', async () => {
  const n = await correr({ rutasEnCache: ['camarista'] });
  return [n.accesosLista.hijos.map(h => h.textContent).join(','), 'Limpieza'];
});

caso('El bloque de accesos se revela', async () => {
  const n = await correr({ rutasEnCache: ['dashboard'] });
  return [String(n.accesosOffline.hidden), 'false'];
});

caso('Los enlaces apuntan a la URL guardada real', async () => {
  const n = await correr({ rutasEnCache: ['reservaciones'] });
  return [n.accesosLista.hijos[0].href, ORIGIN + '/reservaciones'];
});

caso('Rutas con subruta cuentan para su pantalla (caja/movimientos -> Caja)', async () => {
  const n = await correr({ rutasEnCache: ['caja/movimientos'] });
  return [n.accesosLista.hijos.map(h => h.textContent).join(','), 'Caja'];
});

caso('El mensaje cambia de "no se pudo conectar" a algo util', async () => {
  const n = await correr({ rutasEnCache: ['dashboard'] });
  return [n.offlineMensaje.textContent.includes('para consultarlo') ? 'UTIL' : 'MURO', 'UTIL'];
});

caso('Sin nada guardado -> no inventa accesos y conserva el mensaje original', async () => {
  const n = await correr({ rutasEnCache: [] });
  return [n.accesosLista.hijos.length + '|' + String(n.accesosOffline.hidden), '0|true'];
});

caso('2 operaciones sin enviar -> avisa que no se perdieron', async () => {
  const n = await correr({
    rutasEnCache: ['dashboard'],
    operaciones: [{ estado: 'pendiente' }, { estado: 'error' }, { estado: 'sincronizado' }],
  });
  const ok = n.pendientesAviso.hidden === false &&
    n.pendientesAviso.textContent.includes('2 cambios guardados') &&
    n.pendientesAviso.textContent.includes('regístralos a mano');
  return [ok ? 'AVISA 2' : 'MAL: ' + n.pendientesAviso.textContent, 'AVISA 2'];
});

caso('1 sola operacion -> texto en singular', async () => {
  const n = await correr({ rutasEnCache: [], operaciones: [{ estado: 'pendiente' }] });
  const t = n.pendientesAviso.textContent;
  const ok = n.pendientesAviso.hidden === false &&
    t.includes('1 cambio guardado') && t.includes('a mano') && !t.includes('cambios');
  return [ok ? 'SINGULAR' : 'MAL: ' + t, 'SINGULAR'];
});

caso('Cola vacia -> no muestra aviso', async () => {
  const n = await correr({ rutasEnCache: ['dashboard'], operaciones: [{ estado: 'sincronizado' }] });
  return [String(n.pendientesAviso.hidden), 'true'];
});

caso('Sin IndexedDB disponible -> no truena', async () => {
  const n = await correr({ rutasEnCache: ['dashboard'], conIndexedDb: false });
  return [String(n.accesosOffline.hidden), 'false'];
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
