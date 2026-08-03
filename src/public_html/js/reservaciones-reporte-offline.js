/**
 * Reporte del dia de reservaciones, armado EN EL NAVEGADOR con el snapshot offline.
 *
 * Con internet manda el servidor (ReservacionController::exportarPDFAction /
 * exportarExcelAction): es la fuente de verdad, alcanza cualquier fecha y ve los
 * estados que el snapshot no guarda. Este modulo entra solo cuando NO hay red.
 *
 * PARIDAD CON EL SERVIDOR — lo que hay aqui es un ESPEJO de tres piezas de PHP y
 * se desincroniza en silencio si se toca una sola de ellas:
 *   - ReservacionController::costoReporteDia()      -> costoDeHabitacion()
 *   - ReservacionController::formatoMonedaReporteDia() -> formatoMoneda()
 *   - IncrementoTarifa::getIncrementosAplicables()  -> reglasAplicables()
 * Las 8 columnas tambien estan fijadas en los dos lados. La suite
 * ReporteOfflineParidadTest.php compara ambos y truena si alguien cambia uno.
 *
 * EL ERROR QUE ESTO NO PUEDE REPETIR (ago-01): una habitacion LIBRE se cotiza con
 * la temporada vigente de ESA fecha, no con precio_base crudo. Con el precio base
 * el reporte SUBCOTIZA y el hotelero ve dos cuartos identicos a precios distintos.
 */
(function (root, factory) {
  const api = factory();
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  if (root) root.MedisoftReporteOffline = api;
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  // Las MISMAS 8 columnas del export del servidor, en el MISMO orden.
  const COLUMNAS = [
    'HUÉSPED', 'TELÉFONO', 'PROCEDENCIA', 'HABITACIÓN',
    'COSTO', 'FACTURA', 'VEHÍCULO', 'TIPO/ESTADO DE PAGO',
  ];

  // Colores del reporte del servidor: el hotelero ya los reconoce de un vistazo.
  const COLOR = {
    encabezado: '#2E7D32',
    checkin:    '#FFCDD2', // hospedado
    confirmada: '#E1BEE7', // por llegar
    libre:      '#C8E6C9',
  };

  const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
    'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  const DIAS_SEMANA = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

  // ─────────────────────────────────────────────────────────────────────────
  // Utilidades
  // ─────────────────────────────────────────────────────────────────────────

  function esc(texto) {
    return String(texto === null || texto === undefined ? '' : texto)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  /** Espejo de formatoMonedaReporteDia: '$' + miles con coma, sin decimales. */
  function formatoMoneda(monto) {
    const n = Math.round(Number(monto) || 0);
    return '$' + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function fechaBonita(fechaISO) {
    const partes = String(fechaISO || '').split('-').map(Number);
    if (partes.length !== 3 || partes.some(Number.isNaN)) return String(fechaISO || '');
    const d = new Date(partes[0], partes[1] - 1, partes[2]);
    return `${DIAS_SEMANA[d.getDay()]}, ${d.getDate()} de ${MESES[d.getMonth()]} de ${d.getFullYear()}`;
  }

  function ucfirst(texto) {
    const s = String(texto || '');
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Motor de tarifas (espejo de IncrementoTarifa)
  // ─────────────────────────────────────────────────────────────────────────

  /** Las columnas tipos_habitacion/habitaciones llegan como texto JSON desde la BD. */
  function parsearLista(valor) {
    if (Array.isArray(valor)) return valor;
    if (!valor) return [];
    try {
      const parsed = JSON.parse(valor);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  /**
   * ¿La regla estaba vigente esa fecha? Espejo de getActivosParaFecha: el snapshot
   * trae las reglas de TODO el rango, asi que hay que filtrar por dia aqui.
   */
  function reglaVigente(regla, fecha) {
    if (!regla || Number(regla.activo) !== 1) return false;
    if (regla.fecha_inicio && String(regla.fecha_inicio).slice(0, 10) > fecha) return false;

    const permanente = Number(regla.es_permanente) === 1;
    const fin = regla.fecha_fin ? String(regla.fecha_fin).slice(0, 10) : null;
    if (!permanente && fin && fin < fecha) return false;

    return true;
  }

  /** Espejo de getIncrementosAplicables (clase 'incremento'). */
  function reglasAplicables(tarifas, habitacionId, tipoHabitacion, fecha) {
    if (!Array.isArray(tarifas)) return [];

    return tarifas.filter(function (regla) {
      if ((regla.clase || 'incremento') !== 'incremento') return false;
      if (!reglaVigente(regla, fecha)) return false;

      switch (regla.alcance) {
        case 'global':
          return true;
        case 'tipo_habitacion':
          return parsearLista(regla.tipos_habitacion)
            .some(function (t) { return String(t) === String(tipoHabitacion); });
        case 'habitacion':
          return parsearLista(regla.habitaciones)
            .some(function (h) { return Number(h) === Number(habitacionId); });
        default:
          return false;
      }
    });
  }

  /**
   * Precio base CRUDO de la habitacion, el de la tabla, sin temporada encima.
   *
   * CUIDADO: en el snapshot (/api/habitaciones/todas-con-ocupacion) el campo
   * `precio_base` NO es el precio base — viene con el incremento del dia de la
   * captura ya aplicado, y el crudo queda en `precio_base_original`. Tomar
   * `precio_base` y volver a incrementarlo cobra la temporada DOS VECES (medido:
   * una habitacion de $500 con +$100 salia en $700 en vez de $600).
   *
   * Se parte siempre del crudo y se aplica la regla de la fecha PEDIDA, que ademas
   * es lo unico correcto para una fecha distinta a la de la captura.
   */
  function precioBaseCrudo(habitacion) {
    if (!habitacion) return 0;
    const original = habitacion.precio_base_original;
    if (original !== undefined && original !== null && original !== '') {
      return Number(original) || 0;
    }
    return Number(habitacion.precio_base) || 0;
  }

  /** Espejo de calcularPrecioConIncremento: porcentaje sobre la base, o monto fijo. */
  function precioConIncremento(habitacion, fecha, tarifas) {
    const base = precioBaseCrudo(habitacion);
    const reglas = reglasAplicables(tarifas, habitacion.id, habitacion.tipo, fecha);

    return reglas.reduce(function (precio, regla) {
      const valor = Number(regla.valor_incremento) || 0;
      const aumento = regla.tipo_incremento === 'porcentaje' ? base * (valor / 100) : valor;
      return precio + aumento;
    }, base);
  }

  /**
   * Precio con temporada de cada habitacion LIBRE. Solo las libres: una vendida ya
   * trae su precio congelado y recalcularla seria reescribir la venta.
   */
  function mapaPreciosTemporada(habitaciones, ocupacion, fecha, tarifas) {
    const mapa = {};

    (habitaciones || []).forEach(function (hab) {
      if (!hab || hab.id === undefined || hab.id === null) return;
      if (ocupacion[hab.id]) return;
      mapa[hab.id] = precioConIncremento(hab, fecha, tarifas);
    });

    return mapa;
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Costo por habitacion (espejo exacto de costoReporteDia)
  // ─────────────────────────────────────────────────────────────────────────

  function costoDeHabitacion(hab, res, preciosTemporada) {
    const base = precioBaseCrudo(hab);

    if (res) {
      if (res.es_cortesia_hab) {
        return { texto: 'Cortesía', monto: 0, conTemporada: false };
      }
      const monto = (res.precio_hab === null || res.precio_hab === undefined)
        ? base
        : Number(res.precio_hab);
      return { texto: formatoMoneda(monto), monto: monto, conTemporada: false };
    }

    const habId = hab ? hab.id : null;
    const monto = (preciosTemporada && preciosTemporada[habId] !== undefined)
      ? Number(preciosTemporada[habId])
      : base;

    return { texto: formatoMoneda(monto), monto: monto, conTemporada: monto > base };
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Armado del reporte
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Que habitacion ocupa quien ESE dia. Mismo predicado que el servidor:
   * fecha >= entrada AND fecha < salida (el dia de salida ya no cuenta).
   */
  function mapaOcupacion(reservaciones, fecha) {
    const ocupacion = {};

    (reservaciones || []).forEach(function (r) {
      if (!r) return;
      if (r.estado === 'cancelada' || r.estado === 'completada') return;

      const entrada = String(r.fecha_entrada || '').slice(0, 10);
      const salida  = String(r.fecha_salida || '').slice(0, 10);
      if (!entrada || !salida) return;
      if (!(fecha >= entrada && fecha < salida)) return;

      (r.habitaciones || []).forEach(function (hab) {
        if (!hab || hab.id === undefined || hab.id === null) return;

        ocupacion[hab.id] = {
          reservacion_id:     r.id,
          nombre_completo:    r.huesped_nombre,
          telefono:           r.huesped_telefono,
          procedencia_ciudad: r.procedencia_ciudad,
          procedencia_estado: r.procedencia_estado,
          estado:             r.estado,
          metodo_pago:        r.metodo_pago,
          vehiculo:           r.vehiculo || null,
          tiene_factura:      !!r.tiene_factura,
          precio_hab:         hab.precio,
          es_cortesia_hab:    !!hab.es_cortesia,
        };
      });
    });

    return ocupacion;
  }

  /**
   * Dos secciones, como el servidor: numeros primero (ordenados como numero) y
   * despues las de color/nombre. Son dos paginas distintas en el PDF.
   */
  function partirHabitaciones(habitaciones) {
    const numericas = [];
    const color = [];

    (habitaciones || []).forEach(function (hab) {
      if (!hab) return;
      if (/^\d+$/.test(String(hab.numero || ''))) numericas.push(hab);
      else color.push(hab);
    });

    numericas.sort(function (a, b) { return Number(a.numero) - Number(b.numero); });
    color.sort(function (a, b) { return String(a.numero).localeCompare(String(b.numero), 'es'); });

    return { numericas: numericas, color: color };
  }

  function filaDeHabitacion(hab, ocupacion, preciosTemporada) {
    const res = ocupacion[hab.id] || null;
    const costo = costoDeHabitacion(hab, res, preciosTemporada);

    if (!res) {
      return {
        ocupada: false,
        fondo: COLOR.libre,
        huesped: '', telefono: '', procedencia: '',
        habitacion: String(hab.numero || ''),
        costo: costo.texto,
        factura: '', vehiculo: '', pago: '',
      };
    }

    let vehiculo = 'Sin vehiculo';
    if (res.vehiculo) {
      const partes = [res.vehiculo.marca, res.vehiculo.modelo, res.vehiculo.color]
        .filter(function (p) { return p; });
      if (partes.length) vehiculo = partes.join(' ');
    }

    const esCheckin = res.estado === 'checked_in';
    const metodo = ucfirst(res.metodo_pago || '');

    return {
      ocupada: true,
      fondo: esCheckin ? COLOR.checkin : COLOR.confirmada,
      huesped: res.nombre_completo || '',
      telefono: res.telefono || '',
      procedencia: String((res.procedencia_ciudad || '') + ' ' + (res.procedencia_estado || '')).trim(),
      habitacion: String(hab.numero || ''),
      costo: costo.texto,
      factura: res.tiene_factura ? 'Sí' : '',
      vehiculo: vehiculo,
      pago: metodo ? metodo + ' - ' + (esCheckin ? 'Pagado' : 'Pendiente') : '',
    };
  }

  /**
   * Arma el reporte completo para una fecha.
   *
   * @param {object} datos  lo que devuelve OfflineData.obtenerDatosReporteDia()
   * @param {string} fecha  'YYYY-MM-DD'
   */
  function construirReporte(datos, fecha) {
    const habitaciones = (datos && datos.habitaciones) || [];
    const ocupacion = mapaOcupacion((datos && datos.reservaciones) || [], fecha);
    const precios = mapaPreciosTemporada(habitaciones, ocupacion, fecha, (datos && datos.tarifas) || []);
    const grupos = partirHabitaciones(habitaciones);

    const armar = function (lista) {
      return lista.map(function (hab) { return filaDeHabitacion(hab, ocupacion, precios); });
    };

    const secciones = [];
    if (grupos.numericas.length) secciones.push({ titulo: 'Habitaciones', filas: armar(grupos.numericas) });
    if (grupos.color.length) secciones.push({ titulo: 'Habitaciones por nombre', filas: armar(grupos.color) });

    const todas = secciones.reduce(function (acc, s) { return acc.concat(s.filas); }, []);

    return {
      fecha: fecha,
      fechaBonita: fechaBonita(fecha),
      columnas: COLUMNAS.slice(),
      secciones: secciones,
      resumen: {
        total: todas.length,
        ocupadas: todas.filter(function (f) { return f.ocupada; }).length,
        libres: todas.filter(function (f) { return !f.ocupada; }).length,
      },
      origen: {
        local: true,
        capturadoEn: (datos && datos.capturadoEn) || null,
      },
    };
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Salidas
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Aviso de procedencia. NO es decoracion: un reporte impreso con datos de hace
   * horas y tratado como actual es un problema real en recepcion.
   */
  function leyendaOrigen(reporte) {
    if (!reporte.origen || !reporte.origen.local) return '';

    let cuando = '';
    if (reporte.origen.capturadoEn) {
      const d = new Date(reporte.origen.capturadoEn);
      if (!Number.isNaN(d.getTime())) {
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const hh = String(d.getHours()).padStart(2, '0');
        const mi = String(d.getMinutes()).padStart(2, '0');
        cuando = ` el ${dd}/${mm} a las ${hh}:${mi}`;
      }
    }

    return `Generado sin conexión con los datos guardados en este equipo${cuando}.`;
  }

  function filasHTML(seccion, estiloCelda) {
    return seccion.filas.map(function (f) {
      const celdas = [
        { v: f.huesped,     extra: 'padding:5px 8px;' },
        { v: f.telefono,    extra: 'text-align:center;' },
        { v: f.procedencia, extra: '' },
        { v: f.habitacion,  extra: 'font-weight:bold;text-align:center;' },
        { v: f.costo,       extra: 'text-align:right;' },
        { v: f.factura,     extra: 'text-align:center;' },
        { v: f.vehiculo,    extra: '' },
        { v: f.pago,        extra: '' },
      ];

      return '<tr>' + celdas.map(function (c) {
        return `<td style="background:${f.fondo};${estiloCelda}${c.extra}">${esc(c.v)}</td>`;
      }).join('') + '</tr>';
    }).join('');
  }

  /** HTML que Excel abre como hoja de calculo (mismo formato que el del servidor). */
  function htmlParaExcel(reporte, opciones) {
    const opts = opciones || {};
    const estiloTh = `background:${COLOR.encabezado};color:white;font-weight:bold;padding:6px 8px;text-align:center;font-size:10pt;`;
    const encabezados = reporte.columnas.map(function (c) {
      return `<td style="${estiloTh}">${esc(c)}</td>`;
    }).join('');

    const tablas = reporte.secciones.map(function (seccion) {
      return '<table border="1" cellpadding="4" cellspacing="0" '
        + 'style="border-collapse:collapse;font-family:Arial;font-size:10pt;margin-bottom:10px;">'
        + `<tr><td colspan="${reporte.columnas.length}" style="font-weight:bold;padding:8px;font-size:11pt;">`
        + esc(reporte.fechaBonita) + '</td></tr>'
        + '<tr>' + encabezados + '</tr>'
        + filasHTML(seccion, '')
        + '</table>';
    }).join('');

    const aviso = leyendaOrigen(reporte);
    const filaAviso = aviso
      ? `<table border="0" cellpadding="4" style="font-family:Arial;font-size:9pt;margin-bottom:8px;">
           <tr><td>${esc(aviso)}</td></tr></table>`
      : '';

    return '﻿'
      + '<html xmlns:o="urn:schemas-microsoft-com:office:office" '
      + 'xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">'
      + '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>'
      + '<x:Name>Reservaciones</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>'
      + '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>'
      + (opts.hotel ? `<table border="0"><tr><td style="font-family:Arial;font-weight:bold;font-size:12pt;">${esc(opts.hotel)}</td></tr></table>` : '')
      + filaAviso
      + tablas
      + '</body></html>';
  }

  /** HTML apaisado listo para imprimir o guardar como PDF desde el navegador. */
  function htmlParaImprimir(reporte, opciones) {
    const opts = opciones || {};
    const estiloTh = `background:${COLOR.encabezado};color:#fff;font-weight:700;padding:5px 6px;text-align:center;`;
    const encabezados = reporte.columnas.map(function (c) {
      return `<th style="${estiloTh}">${esc(c)}</th>`;
    }).join('');

    const secciones = reporte.secciones.map(function (seccion, i) {
      return `<section class="hoja${i > 0 ? ' salto' : ''}">
          <h2>${esc(seccion.titulo)}</h2>
          <table>
            <thead><tr>${encabezados}</tr></thead>
            <tbody>${filasHTML(seccion, 'padding:4px 6px;border:1px solid #cfd4dc;')}</tbody>
          </table>
        </section>`;
    }).join('');

    const aviso = leyendaOrigen(reporte);

    return `<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reporte ${esc(reporte.fecha)}</title>
<style>
  @page { size: A4 landscape; margin: .45cm .5cm; }
  @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .no-print { display: none; } .salto { page-break-before: always; } }
  * { box-sizing: border-box; }
  body { margin: 0; padding: 10px; font-family: 'Segoe UI', Arial, sans-serif; font-size: 8pt; color: #172033; background: #fff; }
  header { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; border-bottom: 2px solid ${esc(opts.colorPrimario || '#1B2746')}; padding-bottom: 6px; margin-bottom: 8px; }
  h1 { margin: 0; font-size: 13pt; font-weight: 700; color: ${esc(opts.colorPrimario || '#1B2746')}; }
  .fecha { font-size: 9.5pt; font-weight: 600; }
  .resumen { font-size: 8pt; color: #667085; margin: 0 0 8px; }
  .aviso { margin: 0 0 9px; padding: 6px 9px; border-radius: 7px; background: #FFF6E6; border: 1px solid #F0C77E; color: #8A5A00; font-size: 8pt; font-weight: 600; }
  h2 { font-size: 9.5pt; margin: 10px 0 5px; font-weight: 700; }
  table { width: 100%; border-collapse: collapse; }
  thead { display: table-header-group; }
  th, td { border: 1px solid #cfd4dc; }
  .no-print { margin-bottom: 10px; }
  .no-print button { font: inherit; font-weight: 700; padding: 9px 16px; border-radius: 9px; border: none; cursor: pointer; background: ${esc(opts.colorPrimario || '#1B2746')}; color: #fff; }
</style></head>
<body>
  <div class="no-print"><button onclick="window.print()">Imprimir o guardar como PDF</button></div>
  <header>
    <h1>${esc(opts.hotel || 'Reporte de reservaciones')}</h1>
    <span class="fecha">${esc(reporte.fechaBonita)}</span>
  </header>
  ${aviso ? `<p class="aviso">${esc(aviso)}</p>` : ''}
  <p class="resumen">${reporte.resumen.total} habitaciones · ${reporte.resumen.ocupadas} ocupadas · ${reporte.resumen.libres} libres</p>
  ${secciones}
</body></html>`;
  }

  return {
    COLUMNAS: COLUMNAS.slice(),
    construirReporte: construirReporte,
    htmlParaImprimir: htmlParaImprimir,
    htmlParaExcel: htmlParaExcel,
    // Expuestos para el arnes de pruebas y para reusar el formato en la UI.
    formatoMoneda: formatoMoneda,
    fechaBonita: fechaBonita,
    costoDeHabitacion: costoDeHabitacion,
    precioBaseCrudo: precioBaseCrudo,
    precioConIncremento: precioConIncremento,
    reglasAplicables: reglasAplicables,
    mapaOcupacion: mapaOcupacion,
    mapaPreciosTemporada: mapaPreciosTemporada,
    partirHabitaciones: partirHabitaciones,
  };
});
