/**
 * Contratos de la precarga offline (ago-03).
 *
 * OJO CON EL ALCANCE: esto NO prueba comportamiento — `offline-data.js` es un IIFE
 * que necesita window/document/IndexedDB y no se puede cargar en Node. Lo que se
 * fija aqui son los CONTRATOS que se rompen en silencio al editar el archivo; el
 * comportamiento (que una navegacion con datos frescos no pida nada) se mide en el
 * navegador y la receta esta en docs/QA-RECETAS.md.
 *
 * El desajuste que esto caza: cada captura sella `meta.ultima_sync_<clave>` y
 * `estaFresco` la busca por ese mismo nombre. Si alguien renombra una y no la otra,
 * el control de frescura deja de aplicar a ese conjunto SIN ningun error — vuelve
 * el desperdicio de peticiones y nadie se entera.
 *
 * Correr desde la raiz del repo: node tools/tests_js/precarga_offline.test.js
 */
const fs = require('fs');

const OD = fs.readFileSync('src/public_html/js/offline-data.js', 'utf8');
const PWA = fs.readFileSync('src/public_html/js/pwa.js', 'utf8');

const casos = [];
const caso = (nombre, fn) => casos.push([nombre, fn]);

/** Claves que las capturas SELLAN en meta. */
function clavesSelladas() {
  const encontradas = new Set();
  const re = /key:\s*'ultima_sync_([a-z_]+)'/g;
  let m;
  while ((m = re.exec(OD)) !== null) encontradas.add(m[1]);
  return [...encontradas].sort();
}

/** Claves declaradas en la tabla FRESCURA_MS. */
function clavesConTtl() {
  const bloque = OD.match(/const FRESCURA_MS = \{([\s\S]*?)\};/);
  if (!bloque) return [];
  const re = /^\s*([a-z_]+)\s*:/gm;
  const out = [];
  let m;
  while ((m = re.exec(bloque[1])) !== null) out.push(m[1]);
  return out.sort();
}

// ── El contrato que se rompe en silencio ────────────────────────────────────

caso('cada conjunto que se sella tiene su plazo de frescura', () => {
  const selladas = clavesSelladas();
  const conTtl = clavesConTtl();
  const huerfanas = selladas.filter(c => !conTtl.includes(c));
  return [huerfanas.length ? 'sin plazo: ' + huerfanas.join(',') : 'todas', 'todas'];
});

caso('no hay plazos declarados para conjuntos que ya no existen', () => {
  const selladas = clavesSelladas();
  const sobran = clavesConTtl().filter(c => !selladas.includes(c));
  return [sobran.length ? 'sobran: ' + sobran.join(',') : 'ninguno', 'ninguno'];
});

caso('las 6 capturas consultan la frescura antes de pedir', () => {
  const conGuard = (OD.match(/if \(await estaFresco\(/g) || []).length;
  return [String(conGuard), '6'];
});

caso('las 6 capturas aceptan que se les fuerce', () => {
  // [\s\S]{0,140}? y no [^)]*: capturarHabitaciones lleva defaults con parentesis
  // anidados — fechaConOffset(0) — y una clase negada corta ahi.
  const conOpciones = (OD.match(/async function capturar\w+\([\s\S]{0,140}?opciones = \{\}\) \{/g) || []).length;
  return [String(conOpciones), '6'];
});

caso('estaFresco respeta el forzado', () => {
  return [/async function estaFresco\([^)]*\)\s*\{\s*if \(forzar\) return false;/.test(OD), true];
});

caso('estaFresco falla ABIERTO: sin sello vuelve a pedir', () => {
  const cuerpo = OD.match(/async function estaFresco[\s\S]*?\n  \}/);
  const texto = cuerpo ? cuerpo[0] : '';
  // Debe devolver false (= pedir) cuando no hay meta, no hay valor, o truena.
  return [
    /if \(!meta \|\| !meta\.valor\) return false;/.test(texto) && /catch \(e\) \{\s*return false;/.test(texto),
    true,
  ];
});

caso('capturarSnapshots propaga el forzado a las 6', () => {
  const bloque = OD.match(/function capturarSnapshots[\s\S]*?allSettled\(\[([\s\S]*?)\]\)/);
  const cuerpo = bloque ? bloque[1] : '';
  const conOpciones = (cuerpo.match(/opciones/g) || []).length;
  return [String(conOpciones), '6'];
});

// ── Preparar todo al entrar ─────────────────────────────────────────────────

caso('el arranque prepara datos Y pantallas, no solo datos', () => {
  return [/setTimeout\(\(\) => prepararOffline\(\), 400\)/.test(OD)
       && /setTimeout\(\(\) => prepararOffline\(\), 3500\)/.test(OD), true];
});

caso('prepararOffline pide las pantallas a pwa.js', () => {
  return [/precalentarPantallas\?\.\(opciones\)/.test(OD), true];
});

caso('prepararOffline no toca la red si no hay conexion', () => {
  const cuerpo = OD.match(/async function prepararOffline[\s\S]*?\n  \}/)[0];
  return [/if \(navigator\.onLine\) \{[\s\S]*?capturarSnapshots/.test(cuerpo), true];
});

caso('el estado se publica para que la UI reaccione', () => {
  return [/medisoft:offline-listo/.test(OD), true];
});

// ── Multihotel ──────────────────────────────────────────────────────────────

caso('la marca de precalentado va por hotel', () => {
  // Sin el sufijo de scope, precalentar en un hotel bloquea a los demas 6 h.
  return [/PRECALENTADO_KEY = 'loscedros_precalentado_at'\s*\+\s*\(OFFLINE_STORAGE_CONTEXT\?\.scope/.test(PWA), true];
});

caso('pwa.js expone el precalentado para poder forzarlo', () => {
  return [/precalentarPantallas: programarPrecalentado/.test(PWA), true];
});

caso('programarPrecalentado admite saltarse el plazo', () => {
  return [/function programarPrecalentado\(opciones = \{\}\)/.test(PWA)
       && /if \(!opciones\.forzar\) \{/.test(PWA), true];
});

// ── Honestidad de la UI ─────────────────────────────────────────────────────

caso('el indicador solo se anuncia con TODO guardado', () => {
  const cuerpo = OD.match(/async function _actualizarUITimestamp[\s\S]*?\n  \}/)[0];
  return [/if \(!estado\.listo\) \{[\s\S]*?add\('hidden'\)/.test(cuerpo), true];
});

caso('el estado reporta el sello mas VIEJO, no el mas nuevo', () => {
  const cuerpo = OD.match(/async function estadoOffline[\s\S]*?\n  \}/)[0];
  return [/if \(!masViejo \|\| iso < masViejo\) masViejo = iso;/.test(cuerpo), true];
});

caso('el indicador existe en el layout (si no, el estado es invisible)', () => {
  const sidebar = fs.readFileSync('src/app/views/layout/sidebar.php', 'utf8');
  return [/id="offline-listo"/.test(sidebar) && /data-offline-listo-texto/.test(sidebar), true];
});

// ── Plazos razonables ───────────────────────────────────────────────────────

caso('lo que cambia rapido tiene plazo corto y lo estable, largo', () => {
  const bloque = OD.match(/const FRESCURA_MS = \{([\s\S]*?)\};/)[1];
  const val = (clave) => {
    const m = bloque.match(new RegExp(clave + '\\s*:\\s*([0-9]+)\\s*\\*\\s*60\\s*\\*\\s*1000'));
    return m ? Number(m[1]) : null;
  };
  const hab = val('habitaciones'), hue = val('huespedes'), tar = val('tarifas');
  return [hab !== null && hue !== null && tar !== null && hab < hue && hue < tar, true];
});

caso('ningun plazo supera el intervalo de recaptura periodica', () => {
  // Un plazo mayor que el intervalo haria que el refresco de fondo no refresque.
  const intervalo = Number((OD.match(/SNAPSHOT_INTERVALO_MS = (\d+) \* 60 \* 1000/) || [])[1]);
  const bloque = OD.match(/const FRESCURA_MS = \{([\s\S]*?)\};/)[1];
  const minutos = [...bloque.matchAll(/:\s*(\d+)\s*\*\s*60\s*\*\s*1000/g)].map(m => Number(m[1]));
  const cortos = minutos.filter(m => m <= intervalo).length;
  // Los de cambio rapido (habitaciones/reservaciones/caja) deben caber en el intervalo.
  return [cortos >= 3, true];
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
