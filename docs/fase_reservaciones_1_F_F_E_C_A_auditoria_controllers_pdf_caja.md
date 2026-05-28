# Reservaciones 1-F-F-E-C-A - Auditoria de controllers PDF/exportacion de Caja

## 1. Objetivo

Auditar los controllers de PDF/exportacion de Caja para preparar una implementacion segura usando datos ya scoped por hotel_id.

## 2. Hallazgo principal

- MovimientoCaja::obtenerParaExportar() y Caja::obtenerIngresosPorTipoHabitacion() ya estan scoped.
- Los controllers de PDF/exportacion todavia necesitan validaciones explicitas por hotel_id antes de renderizar o exportar.
- El riesgo principal esta en consultas directas de corte/caja antes de generar documentos.

## 3. Archivos revisados

- src/app/controllers/CajaController.php
- src/app/models/Caja.php
- src/app/models/MovimientoCaja.php
- src/includes/ReporteCortePDF.php
- vistas de Caja:
  - caja/ver_corte.php
  - caja/historial.php
  - caja/corte.php
  - caja/movimientos.php

## 4. Metodos auditados

- CajaController::exportarAction()
- CajaController::descargarPDFAction()
- CajaController::generarYEnviarReporteCorte()
- MovimientoCaja::obtenerParaExportar()
- Caja::obtenerIngresosPorTipoHabitacion()
- Caja::obtenerResumenCaja()
- MovimientoCaja::obtenerMovimientosDetallados()
- ReporteCortePDF::generar()

## 5. Hallazgos por metodo

- exportarAction(): riesgo medio; consume datos scoped, pero debe validar corte_id + hotel_id antes de exportar.
- descargarPDFAction(): riesgo alto; SELECT directo por cc.id sin hotel_id.
- generarYEnviarReporteCorte(): riesgo alto; SELECT directo por cc.id sin hotel_id.
- MovimientoCaja::obtenerParaExportar(): riesgo bajo; ya scoped.
- Caja::obtenerIngresosPorTipoHabitacion(): riesgo bajo; ya scoped.
- Caja::obtenerResumenCaja(): riesgo bajo; ya valida corte y movimientos por hotel.
- MovimientoCaja::obtenerMovimientosDetallados(): riesgo medio; scoped por mc.hotel_id, pero revisar corte_id.
- ReporteCortePDF::generar(): riesgo bajo si recibe datos scoped; solo renderiza.

## 6. Validaciones pendientes

### exportarAction()

- Validar corte_id + hotel_id, idealmente con Caja::obtenerCortePorId().
- Confirmar si exportarExcel() / exportarPDF() existen o resolver la ruta.

### descargarPDFAction()

- Usar obtenerHotelIdActualCompat().
- Cambiar SELECT directo a cc.id = ? AND cc.hotel_id = ?.
- Validar cajas con c.hotel_id = cc.hotel_id.

### generarYEnviarReporteCorte()

- Aplicar la misma validacion que descargarPDFAction().
- Blindarlo aunque venga de cerrarCorteAction().

## 7. Que implementar primero

Reservaciones 1-F-F-E-C-B:

- CajaController::exportarAction() scoped.

## 8. Que dejar para despues

- Reservaciones 1-F-F-E-C-C: CajaController::descargarPDFAction() scoped.
- Reservaciones 1-F-F-E-C-D: CajaController::generarYEnviarReporteCorte() scoped.
- Reservaciones 1-F-F-E-D: PDFs complejos de ReportesController.

## 9. Que dejar fuera

- ReportesController.
- ReservacionController.
- PDFs complejos de reportes.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.

## 10. Pruebas necesarias

- php -l en archivos tocados.
- git diff --check.
- verificar_estado.php PASS.
- preflight_hotel_id.php PASS.
- Descarga/exportacion controlada de corte.
- Confirmar que ReportesController, ReservacionController, PWA/Sync/APIs no se tocaron.
- Confirmar 0 nuevos hotel_id NULL.

## 11. Siguiente fase recomendada

Reservaciones 1-F-F-E-C-B: implementar unicamente CajaController::exportarAction() scoped por hotel_id, sin tocar todavia descargarPDFAction(), generarYEnviarReporteCorte(), renderizadores ni ReportesController.
