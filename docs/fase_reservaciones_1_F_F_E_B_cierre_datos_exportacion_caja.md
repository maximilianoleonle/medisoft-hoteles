# Reservaciones 1-F-F-E-B - Cierre tecnico de datos base para exportaciones/PDF de Caja

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en los metodos base que preparan datos para exportaciones/PDF de Caja.

## 2. Que se logro

- MovimientoCaja::obtenerParaExportar() ahora respeta hotel_id.
- Caja::obtenerIngresosPorTipoHabitacion() ahora respeta hotel_id.
- Los movimientos de caja se filtran por movimientos_caja.hotel_id.
- Los cortes se validan por cortes_caja.hotel_id.
- Las reservaciones se validan por reservaciones.hotel_id.
- Las habitaciones de reservacion se validan por reservacion_habitaciones.hotel_id.
- Las habitaciones se validan por habitaciones.hotel_id.
- usuarios y categorias_movimientos quedan como joins auxiliares.
- Se conserva la logica original de reparto Manolo/Elia.

## 3. Archivos modificados

- src/app/models/MovimientoCaja.php
- src/app/models/Caja.php

## 4. Que NO se toco

- CajaController.php.
- ReportesController.php.
- ReservacionController.php.
- renderizadores PDF.
- vistas PDF/exportacion.
- ReporteCortePDF.php.
- ReportePDF.php.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- base de datos.

## 5. Validaciones realizadas

- php -l en MovimientoCaja.php.
- php -l en Caja.php.
- git diff --check.
- verificar_estado.php PASS.
- preflight_hotel_id.php PASS.
- Confirmacion de 0 hotel_id NULL segun herramientas SaaS.
- Confirmacion de que no se tocaron controllers/PDFs/PWA/Sync/APIs.

## 6. Riesgos pendientes

- CajaController::exportarAction() todavia debe validarse/consumir datos scoped.
- CajaController::descargarPDFAction() sigue pendiente.
- CajaController::generarYEnviarReporteCorte() sigue pendiente.
- Renderizadores PDF siguen pendientes.
- PDFs complejos de ReportesController siguen pendientes.
- Exportaciones de ReservacionController quedan para fase separada.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-C-A: auditoria/propuesta para controllers de PDF/exportacion de Caja:

- CajaController::exportarAction()
- CajaController::descargarPDFAction()
- CajaController::generarYEnviarReporteCorte()
