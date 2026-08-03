/**
 * Arnes del generador del reporte del dia SIN INTERNET.
 *
 * Carga el modulo REAL (src/public_html/js/reservaciones-reporte-offline.js) y
 * comprueba que sus tres espejos de PHP siguen diciendo lo mismo que el servidor:
 * costoReporteDia, formatoMonedaReporteDia y getIncrementosAplicables.
 *
 * El caso que da nombre a media suite es el bug de ago-01: una habitacion LIBRE
 * cotizada en precio_base cuando hay temporada vigente SUBCOTIZA el reporte, y el
 * hotelero ve dos cuartos identicos a precios distintos en la misma hoja.
 *
 * Correr desde la raiz del repo: node tools/tests_js/reporte_offline.test.js
 */
const R = require('../../src/public_html/js/reservaciones-reporte-offline.js');

const FECHA = '2026-11-28';

function habitacion(id, numero, extra) {
  return Object.assign({ id: id, numero: numero, tipo: 'sencilla', precio_base: 1000 }, extra || {});
}

function reserva(extra) {
  return Object.assign({
    id: 900,
    estado: 'checked_in',
    fecha_entrada: '2026-11-28',
    fecha_salida: '2026-11-30',
    huesped_nombre: 'Ana Ruiz',
    huesped_telefono: '8112223344',
    procedencia_ciudad: 'Monterrey',
    procedencia_estado: 'Nuevo León',
    metodo_pago: 'efectivo',
    tiene_factura: false,
    vehiculo: null,
    habitaciones: [{ id: 1, numero: '101', precio: 1200, es_cortesia: false }],
  }, extra || {});
}

function tarifa(extra) {
  return Object.assign({
    id: 5,
    clase: 'incremento',
    activo: 1,
    alcance: 'global',
    tipo_incremento: 'monto_fijo',
    valor_incremento: 200,
    fecha_inicio: '2026-11-01',
    fecha_fin: '2027-02-01',
    es_permanente: 0,
  }, extra || {});
}

/** Costo que sale en la fila de esa habitacion. */
function costoDe(datos, numero, fecha) {
  const rep = R.construirReporte(datos, fecha || FECHA);
  const fila = rep.secciones
    .reduce(function (acc, s) { return acc.concat(s.filas); }, [])
    .find(function (f) { return f.habitacion === numero; });
  return fila ? fila.costo : '(sin fila)';
}

const casos = [];
const caso = (nombre, fn) => casos.push([nombre, fn]);

// ── El bug de ago-01 ────────────────────────────────────────────────────────

caso('habitacion LIBRE lleva la temporada vigente, no el precio base', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL')],
    reservaciones: [],
    tarifas: [tarifa()],
  };
  return [costoDe(datos, 'CORAL'), '$1,200'];
});

caso('libre y vendida del mismo precio salen IGUAL en la hoja', () => {
  const datos = {
    habitaciones: [habitacion(1, '101'), habitacion(2, 'CORAL')],
    reservaciones: [reserva()],
    tarifas: [tarifa()],
  };
  return [costoDe(datos, '101') + ' / ' + costoDe(datos, 'CORAL'), '$1,200 / $1,200'];
});

caso('habitacion VENDIDA conserva su precio congelado, no se recotiza', () => {
  // La regla de temporada subio a 400: la vendida sigue en los 1200 que se cobraron.
  const datos = {
    habitaciones: [habitacion(1, '101')],
    reservaciones: [reserva()],
    tarifas: [tarifa({ valor_incremento: 400 })],
  };
  return [costoDe(datos, '101'), '$1,200'];
});

caso('sin temporada vigente la libre sale en su precio base', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL')],
    reservaciones: [],
    tarifas: [tarifa({ fecha_inicio: '2027-06-01', fecha_fin: '2027-08-01' })],
  };
  return [costoDe(datos, 'CORAL'), '$1,000'];
});

caso('la temporada NO se aplica dos veces sobre el precio del snapshot', () => {
  // El snapshot manda `precio_base` YA incrementado y el crudo en
  // `precio_base_original`. Partir del primero cobraba la temporada doble:
  // $500 con +$100 salia en $700 (medido contra el export real del servidor).
  const datos = {
    habitaciones: [{ id: 2, numero: 'CORAL', tipo: 'sencilla', precio_base: 600, precio_base_original: '500.00' }],
    reservaciones: [],
    tarifas: [tarifa({ valor_incremento: 100 })],
  };
  return [costoDe(datos, 'CORAL'), '$600'];
});

caso('sin precio_base_original se usa precio_base (otro origen de datos)', () => {
  const datos = {
    habitaciones: [{ id: 2, numero: 'CORAL', tipo: 'sencilla', precio_base: 500 }],
    reservaciones: [],
    tarifas: [tarifa({ valor_incremento: 100 })],
  };
  return [costoDe(datos, 'CORAL'), '$600'];
});

// ── Motor de tarifas ────────────────────────────────────────────────────────

caso('porcentaje: 1000 + 25% = 1250', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL')],
    reservaciones: [],
    tarifas: [tarifa({ tipo_incremento: 'porcentaje', valor_incremento: 25 })],
  };
  return [costoDe(datos, 'CORAL'), '$1,250'];
});

caso('alcance tipo_habitacion solo toca a su tipo', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL', { tipo: 'suite' }), habitacion(3, 'GRIS', { tipo: 'sencilla' })],
    reservaciones: [],
    tarifas: [tarifa({ alcance: 'tipo_habitacion', tipos_habitacion: '["suite"]' })],
  };
  return [costoDe(datos, 'CORAL') + ' / ' + costoDe(datos, 'GRIS'), '$1,200 / $1,000'];
});

caso('alcance habitacion solo toca a las de su lista', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL'), habitacion(3, 'GRIS')],
    reservaciones: [],
    tarifas: [tarifa({ alcance: 'habitacion', habitaciones: '[2]' })],
  };
  return [costoDe(datos, 'CORAL') + ' / ' + costoDe(datos, 'GRIS'), '$1,200 / $1,000'];
});

caso('una regla de DESCUENTO no se aplica como incremento', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL')],
    reservaciones: [],
    tarifas: [tarifa({ clase: 'descuento' })],
  };
  return [costoDe(datos, 'CORAL'), '$1,000'];
});

caso('regla inactiva no aplica', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL')],
    reservaciones: [],
    tarifas: [tarifa({ activo: 0 })],
  };
  return [costoDe(datos, 'CORAL'), '$1,000'];
});

caso('regla permanente sigue viva aunque su fecha_fin ya paso', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL')],
    reservaciones: [],
    tarifas: [tarifa({ es_permanente: 1, fecha_fin: '2020-01-01' })],
  };
  return [costoDe(datos, 'CORAL'), '$1,200'];
});

caso('dos reglas vigentes se suman', () => {
  const datos = {
    habitaciones: [habitacion(2, 'CORAL')],
    reservaciones: [],
    tarifas: [tarifa(), tarifa({ id: 6, valor_incremento: 150 })],
  };
  return [costoDe(datos, 'CORAL'), '$1,350'];
});

// ── Cortesia y formato ──────────────────────────────────────────────────────

caso('la cortesia gana sobre cualquier precio', () => {
  const datos = {
    habitaciones: [habitacion(1, '101')],
    reservaciones: [reserva({ habitaciones: [{ id: 1, numero: '101', precio: 1200, es_cortesia: true }] })],
    tarifas: [tarifa()],
  };
  return [costoDe(datos, '101'), 'Cortesía'];
});

caso('formato de moneda igual que number_format(...,0,".",",")', () => {
  return [
    [999, 1000, 1200, 12500, 1000000].map(R.formatoMoneda).join(' '),
    '$999 $1,000 $1,200 $12,500 $1,000,000',
  ];
});

// ── Ocupacion del dia ───────────────────────────────────────────────────────

caso('el dia de ENTRADA cuenta como ocupada', () => {
  const datos = { habitaciones: [habitacion(1, '101')], reservaciones: [reserva()], tarifas: [] };
  const rep = R.construirReporte(datos, '2026-11-28');
  return [String(rep.resumen.ocupadas), '1'];
});

caso('el dia de SALIDA ya no cuenta (el cuarto se vende)', () => {
  const datos = { habitaciones: [habitacion(1, '101')], reservaciones: [reserva()], tarifas: [] };
  const rep = R.construirReporte(datos, '2026-11-30');
  return [String(rep.resumen.ocupadas) + '/' + String(rep.resumen.libres), '0/1'];
});

caso('una reservacion cancelada no ocupa', () => {
  const datos = {
    habitaciones: [habitacion(1, '101')],
    reservaciones: [reserva({ estado: 'cancelada' })],
    tarifas: [],
  };
  const rep = R.construirReporte(datos, FECHA);
  return [String(rep.resumen.ocupadas), '0'];
});

// ── Contenido de la fila ────────────────────────────────────────────────────

caso('sin vehiculo registrado dice "Sin vehiculo"', () => {
  const datos = { habitaciones: [habitacion(1, '101')], reservaciones: [reserva()], tarifas: [] };
  const rep = R.construirReporte(datos, FECHA);
  return [rep.secciones[0].filas[0].vehiculo, 'Sin vehiculo'];
});

caso('el vehiculo se arma marca + modelo + color', () => {
  const datos = {
    habitaciones: [habitacion(1, '101')],
    reservaciones: [reserva({ vehiculo: { marca: 'Nissan', modelo: 'Versa', color: 'Rojo' } })],
    tarifas: [],
  };
  const rep = R.construirReporte(datos, FECHA);
  return [rep.secciones[0].filas[0].vehiculo, 'Nissan Versa Rojo'];
});

caso('pago: hospedado = Pagado, por llegar = Pendiente', () => {
  const hospedado = R.construirReporte(
    { habitaciones: [habitacion(1, '101')], reservaciones: [reserva()], tarifas: [] }, FECHA);
  const porLlegar = R.construirReporte(
    { habitaciones: [habitacion(1, '101')], reservaciones: [reserva({ estado: 'confirmada' })], tarifas: [] }, FECHA);
  return [
    hospedado.secciones[0].filas[0].pago + ' | ' + porLlegar.secciones[0].filas[0].pago,
    'Efectivo - Pagado | Efectivo - Pendiente',
  ];
});

caso('factura pedida marca Sí, sin pedir queda vacia', () => {
  const con = R.construirReporte(
    { habitaciones: [habitacion(1, '101')], reservaciones: [reserva({ tiene_factura: true })], tarifas: [] }, FECHA);
  const sin = R.construirReporte(
    { habitaciones: [habitacion(1, '101')], reservaciones: [reserva()], tarifas: [] }, FECHA);
  return [
    con.secciones[0].filas[0].factura + '|' + sin.secciones[0].filas[0].factura,
    'Sí|',
  ];
});

caso('procedencia junta ciudad y estado', () => {
  const datos = { habitaciones: [habitacion(1, '101')], reservaciones: [reserva()], tarifas: [] };
  const rep = R.construirReporte(datos, FECHA);
  return [rep.secciones[0].filas[0].procedencia, 'Monterrey Nuevo León'];
});

caso('la fila libre no filtra datos de nadie', () => {
  const datos = { habitaciones: [habitacion(2, 'CORAL')], reservaciones: [reserva()], tarifas: [] };
  const rep = R.construirReporte(datos, FECHA);
  const f = rep.secciones[0].filas[0];
  return [[f.huesped, f.telefono, f.procedencia, f.pago, f.factura].join('|'), '||||'];
});

// ── Secciones y orden ───────────────────────────────────────────────────────

caso('numeros y nombres van en secciones distintas', () => {
  const datos = {
    habitaciones: [habitacion(1, '101'), habitacion(2, 'CORAL')],
    reservaciones: [], tarifas: [],
  };
  const rep = R.construirReporte(datos, FECHA);
  return [rep.secciones.length + ':' + rep.secciones.map(s => s.filas.length).join(','), '2:1,1'];
});

caso('las numericas se ordenan como numero (2 antes que 10)', () => {
  const datos = {
    habitaciones: [habitacion(3, '10'), habitacion(1, '2')],
    reservaciones: [], tarifas: [],
  };
  const rep = R.construirReporte(datos, FECHA);
  return [rep.secciones[0].filas.map(f => f.habitacion).join(','), '2,10'];
});

// ── Salidas ─────────────────────────────────────────────────────────────────

caso('el Excel trae las 8 columnas del servidor', () => {
  const rep = R.construirReporte({ habitaciones: [habitacion(1, '101')], reservaciones: [], tarifas: [] }, FECHA);
  const html = R.htmlParaExcel(rep, {});
  const faltan = R.COLUMNAS.filter(c => html.indexOf(c) === -1);
  return [faltan.length ? 'faltan: ' + faltan.join(',') : 'todas', 'todas'];
});

caso('el Excel se declara como hoja de calculo de Office', () => {
  const rep = R.construirReporte({ habitaciones: [habitacion(1, '101')], reservaciones: [], tarifas: [] }, FECHA);
  return [R.htmlParaExcel(rep, {}).indexOf('urn:schemas-microsoft-com:office:excel') !== -1, true];
});

caso('el reporte impreso avisa que salio de datos guardados', () => {
  const rep = R.construirReporte(
    { habitaciones: [habitacion(1, '101')], reservaciones: [], tarifas: [], capturadoEn: '2026-08-03T10:30:00Z' },
    FECHA);
  const html = R.htmlParaImprimir(rep, {});
  return [html.indexOf('Generado sin conexión') !== -1, true];
});

caso('un nombre con HTML no se cuela en el archivo', () => {
  const datos = {
    habitaciones: [habitacion(1, '101')],
    reservaciones: [reserva({ huesped_nombre: '<script>alert(1)</script>' })],
    tarifas: [],
  };
  const html = R.htmlParaExcel(R.construirReporte(datos, FECHA), {});
  return [html.indexOf('<script>alert') === -1 && html.indexOf('&lt;script&gt;') !== -1, true];
});

caso('sin habitaciones no truena: entrega un reporte vacio coherente', () => {
  const rep = R.construirReporte({ habitaciones: [], reservaciones: [], tarifas: [] }, FECHA);
  return [rep.secciones.length + ':' + rep.resumen.total, '0:0'];
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
