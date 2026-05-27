# Reservaciones 1-F-F-A - Auditoria de ReportesController y Reporte

## Objetivo

Auditar `ReportesController.php` y `Reporte.php` para identificar reportes financieros, operativos e historicos que todavia leen datos globales.

## Hallazgo central

- `ReportesController.php` y `Reporte.php` siguen mayormente globales.
- El mayor riesgo esta en reportes financieros y PDFs que leen `movimientos_caja`, `reservaciones`, `reservacion_habitaciones` y `habitaciones` sin `hotel_id`.
- Hay reportes operativos e historicos con constantes globales de ocupacion como 66 habitaciones.

## Metodos encontrados en ReportesController

- `indexAction`
- `ingresosGastosAction`
- `getMetodosPagoData`
- `getUsuariosActivos`
- `exportarPdfAction`
- `exportarIngresosGastosUsuarioPdf`
- `testDatosAction`
- `testUsuarioAction`
- `obtenerIngresosPorPropiedad`
- `exportarIngresosGastosPdf`
- `exportarIngresosTotalesPdf`
- `mantenimientoAction`
- `procedenciaAction`
- `habitacionesRentablesAction`
- `ocupacionAction`
- `estanciaAction`
- `rankingEstadosAction`
- `datosGraficaAction`

## Metodos financieros de alto riesgo en Reporte.php

- `obtenerIngresosGastos`
- `getIngresosVsGastos`
- `getResumenDiario`
- `getIngresosPorMetodoPago`
- `getMovimientosPorUsuario`
- `getResumenPorUsuario`
- `getEstadisticasAvanzadas`
- `getTransaccionesMayores`
- `getComparacionPeriodos`
- `obtenerResumenDiario`
- `obtenerResumenPorCategoria`
- `obtenerDatosGraficaIngresosGastos`

## Metodos operativos e historicos

- Procedencia.
- Rentabilidad de habitaciones.
- Ocupacion por tipo.
- Ingreso promedio por habitacion.
- Ocupacion diaria, semanal y mensual.
- Estancia.
- Ranking de estados.

## Que ya puede filtrarse

- `movimientos_caja.hotel_id`.
- `cortes_caja.hotel_id`.
- `reservaciones.hotel_id`.
- `reservacion_habitaciones.hotel_id`.
- `habitaciones.hotel_id`.

## Riesgos criticos

- Reportes financieros mezclan hoteles si no filtran `movimientos_caja.hotel_id`.
- PDFs/exportaciones son alto riesgo porque generan documentos financieros historicos.
- Constantes globales como 66 habitaciones deben reemplazarse por el total real del hotel.
- `usuarios` y `huespedes` aun no son tenant-aware.
- `Reporte.php` alimenta varios reportes historicos.

## Que implementar primero

Reservaciones 1-F-F-B: `Reporte.php` financiero basico scoped, sin PDFs.

Metodos candidatos:

- `getIngresosVsGastos`
- `getResumenDiario`
- `getIngresosPorMetodoPago`
- `getMovimientosPorUsuario`
- `getResumenPorUsuario`
- `getEstadisticasAvanzadas`
- `getTransaccionesMayores`
- `getComparacionPeriodos`
- `obtenerDatosGraficaIngresosGastos`

## Que dejar fuera

- PDFs/exportaciones.
- `ReportesController` de PDFs.
- Procedencia.
- Rentabilidad.
- Ocupacion.
- Estancia.
- Ranking.
- Debug/test.
- Mantenimiento.
- PWA/offline.
- Sync.
- APIs.
- Dashboard.
- Caja funcional.
- Reservaciones funcional.

## Fases propuestas

- 1-F-F-B: `Reporte.php` financiero basico scoped.
- 1-F-F-C: `ReportesController` vistas basicas scoped.
- 1-F-F-D: reportes historicos avanzados.
- 1-F-F-E: PDFs/exportaciones.

## Pruebas necesarias

- `php -l`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/reportes`.
- GET `/reportes/ingresos-gastos`.
- AJAX `/reportes/datos-grafica`.
- Comparar totales read-only por `movimientos_caja.hotel_id`.
- Confirmar que no se tocaron PDFs/exportaciones ni PWA/Sync/APIs.
