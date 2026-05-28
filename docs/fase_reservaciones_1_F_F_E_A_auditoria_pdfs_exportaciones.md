# Reservaciones 1-F-F-E-A - Auditoría de PDFs y exportaciones

## Objetivo

Auditar PDFs, exportaciones y renderizadores de reportes para identificar cuáles siguen usando datos globales o consumen consultas no scoped antes de hacerlos tenant-aware.

## Hallazgo principal

- Los mayores riesgos están en PDFs/exportaciones que hacen consultas directas a `movimientos_caja`, `cortes_caja`, `reservaciones`, `reservacion_habitaciones` y `habitaciones` sin `hotel_id`.
- Algunos renderizadores solo pintan datos recibidos y son de menor riesgo.
- Los datos de entrada deben quedar scoped antes de generar documentos.

## Archivos encontrados

- `src/app/controllers/ReportesController.php`
- `src/app/controllers/CajaController.php`
- `src/app/controllers/ReservacionController.php`, detectado por exportaciones.
- `src/app/models/Reporte.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`
- `src/includes/ReporteCortePDF.php`
- `src/app/views/reportes/ReportePDF.php`
- Vistas relacionadas:
  - `reportes/index.php`
  - `reportes/ingresos-gastos.php`
  - `reportes/procedencia.php`
  - `reportes/habitaciones-rentables.php`
  - `caja/ver_corte.php`
  - `caja/historialdecortes.php`
  - `caja/corte.php`

## Métodos y zonas auditadas

- `ReportesController::exportarIngresosGastosPdf()`
- `ReportesController::exportarIngresosGastosUsuarioPdf()`
- `ReportesController::exportarIngresosTotalesPdf()`
- `ReportesController::obtenerIngresosPorPropiedad()`
- `ReportesController::exportarPdfAction()`
- `CajaController::generarYEnviarReporteCorte()`
- `CajaController::descargarPDFAction()`
- `CajaController::exportarAction()`
- `MovimientoCaja::obtenerParaExportar()`
- `Caja::obtenerIngresosPorTipoHabitacion()`
- `Reporte::generarPDF()`
- `Reporte::generarContenidoPDF()`
- `Reporte::generarPDFIngresosGastos()`
- `ReportePDF.php`
- `ReporteCortePDF.php`

## Clasificación de riesgo

### Alto

- `ReportesController::exportarIngresosGastosPdf()`
- `ReportesController::exportarIngresosGastosUsuarioPdf()`
- `ReportesController::exportarIngresosTotalesPdf()`
- `ReportesController::obtenerIngresosPorPropiedad()`
- `CajaController::generarYEnviarReporteCorte()`
- `CajaController::descargarPDFAction()`
- `CajaController::exportarAction()`
- `MovimientoCaja::obtenerParaExportar()`
- `Caja::obtenerIngresosPorTipoHabitacion()`
- `Reporte::generarPDFIngresosGastos()`

### Medio

- `ReportesController::exportarPdfAction()`
- `Reporte::generarPDF()`
- `Reporte::generarContenidoPDF()`

### Bajo

- `ReportePDF.php` como renderizador.
- `ReporteCortePDF.php` como renderizador, siempre que reciba datos ya scoped.

## Consultas a scopear

- `movimientos_caja`: `mc.hotel_id = ?`
- `cortes_caja`: `cc.hotel_id = ?`
- `cajas`: `c.hotel_id = cc.hotel_id`
- `reservaciones`: `r.hotel_id = ?` o `r.hotel_id = mc.hotel_id`
- `reservacion_habitaciones`: `rh.hotel_id = r.hotel_id` o `rh.hotel_id = mc.hotel_id`
- `habitaciones`: `h.hotel_id = rh.hotel_id`

## Decisiones técnicas

- `usuarios` debe quedar como join auxiliar, no como fuente de scope.
- `huespedes` debe quedar como join auxiliar, no como fuente de scope.
- Los renderizadores PDF no deben considerarse seguros si reciben datos globales.
- Primero deben scopearse los métodos que preparan datos, no solo el archivo que renderiza el PDF.

## Qué implementar primero

Reservaciones 1-F-F-E-B: exportaciones/PDF básicos de Caja scoped.

Orden recomendado:

1. `MovimientoCaja::obtenerParaExportar()`
2. `Caja::obtenerIngresosPorTipoHabitacion()`
3. `CajaController::descargarPDFAction()`
4. `CajaController::generarYEnviarReporteCorte()`

## Qué dejar fuera

- PDFs complejos de `ReportesController`.
- `Reporte::generarPDF()` legacy.
- PDFs históricos avanzados.
- `ReservacionController::exportarPDFAction()` / `exportarExcelAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huéspedes tenant.
- Usuarios tenant.

## Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET/descarga controlada de `/caja/descargar-pdf/{id}` si aplica.
- Exportación de movimientos por `corte_id` validando `hotel_id`.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que PWA/Sync/APIs no se tocaron.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-B: implementar exportaciones/PDF básicos de Caja scoped, empezando por `MovimientoCaja::obtenerParaExportar()` y `Caja::obtenerIngresosPorTipoHabitacion()`, sin tocar todavía PDFs complejos de `ReportesController`.
