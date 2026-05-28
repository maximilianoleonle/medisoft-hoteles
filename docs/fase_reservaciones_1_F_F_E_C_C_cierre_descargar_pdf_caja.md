# Reservaciones 1-F-F-E-C-C - Cierre tecnico de CajaController::descargarPDFAction() scoped

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en CajaController::descargarPDFAction().

## 2. Que se logro

- descargarPDFAction() ahora resuelve el hotel actual con obtenerHotelIdActualCompat().
- Valida corte_id + hotel_id antes de generar/descargar el PDF.
- El SELECT del corte usa cc.id = ? AND cc.hotel_id = ?.
- El join de cajas valida c.hotel_id = cc.hotel_id.
- Mantiene la logica original de descarga.
- Consume datos ya scoped desde:
  - Caja::obtenerResumenCaja()
  - MovimientoCaja::obtenerMovimientosDetallados()
  - Caja::obtenerIngresosPorTipoHabitacion()

## 3. Archivo modificado

- src/app/controllers/CajaController.php

## 4. Que NO se toco

- generarYEnviarReporteCorte().
- exportarAction().
- renderizadores PDF.
- ReporteCortePDF.php.
- ReportePDF.php.
- ReportesController.
- ReservacionController.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- base de datos.

## 5. Validaciones realizadas

- php -l en CajaController.php.
- git diff --check.
- verificar_estado.php PASS.
- preflight_hotel_id.php PASS.
- GET /caja/descargar-pdf/1 respondio 303 a /login sin error 500.
- Confirmacion de que no se tocaron renderizadores/PDFs complejos/PWA/Sync/APIs.
- 0 nuevos hotel_id NULL segun herramientas.

## 6. Riesgos pendientes

- generarYEnviarReporteCorte() sigue pendiente.
- Renderizadores PDF siguen pendientes.
- exportarExcel() / exportarPDF() siguen no encontrados.
- PDFs complejos de ReportesController siguen pendientes.
- Exportaciones de ReservacionController quedan para fase separada.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-C-D: implementar generarYEnviarReporteCorte() scoped por hotel_id, sin tocar todavia renderizadores PDF.
