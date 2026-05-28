# Reservaciones 1-F-F-E-E-A - Auditoria de exportaciones de ReservacionController

## 1. Objetivo

Auditar exportaciones, PDFs, Excel, CSV o descargas relacionadas con Reservaciones para identificar consultas globales y preparar una futura integracion por `hotel_id`.

## 2. Hallazgo principal

* Los riesgos principales estan en `ReservacionController::exportarPDFAction()` y `ReservacionController::exportarExcelAction()`.
* Ambos hacen consultas directas globales a `habitaciones`, `reservaciones`, `reservacion_habitaciones` y `solicitudes_factura` sin `hotel_id`.
* `cotizacionReservacionPdfAction()` tambien es riesgoso porque usa `Reservacion::find()`, que hereda `Model::find()` global.
* Algunos metodos del modelo ya estan scoped, como `Reservacion::obtenerPorId()`, `Reservacion::getHabitaciones()`, `Reservacion::paraCalendario()` y `Habitacion::find()`.

## 3. Archivos encontrados

* `src/app/controllers/ReservacionController.php`.
* `src/app/models/Reservacion.php`.
* `src/app/models/Habitacion.php`.
* `src/app/models/Huesped.php`.
* `src/core/Model.php`.
* `src/app/views/reservaciones/index.php`.
* `src/app/views/reservaciones/ver.php`.
* `src/app/views/reservaciones/crear.php`.
* `src/app/views/reservaciones/calendario.php`.
* `src/config/routes.php`.
* `src/public_html/fdpdf/fpdf.php`.

## 4. Metodos o zonas auditadas

* `ReservacionController::exportarPDFAction()`.
* `ReservacionController::exportarExcelAction()`.
* `ReservacionController::cotizacionReservacionPdfAction()`.
* `ReservacionController::cotizacionPdfAction()`.
* `generarHTMLReservacionesPersonalizado()`.
* `calendarioAction()` + CSV cliente.
* `imprimirTicketTermico()` en vista.
* `Reservacion::getHabitaciones()`.
* `Reservacion::obtenerPorId()`.
* `Reservacion::obtenerPagos()`.
* `Reservacion::obtenerSolicitudFactura()`.

## 5. Riesgos por metodo

### Alto

* `exportarPDFAction()`.
* `exportarExcelAction()`.
* `cotizacionReservacionPdfAction()`.

### Medio

* `cotizacionPdfAction()`.
* `Reservacion::obtenerPagos()`.
* `Reservacion::obtenerSolicitudFactura()`.

### Bajo

* `generarHTMLReservacionesPersonalizado()`.
* `calendarioAction()` si consume modelo scoped.
* `Reservacion::getHabitaciones()`.
* `Reservacion::obtenerPorId()`.

## 6. Consultas globales detectadas

* `habitaciones WHERE activa = 1` sin `hotel_id`.
* `reservaciones r` sin `r.hotel_id = ?`.
* Joins con `reservacion_habitaciones rh` y `habitaciones hab` sin validar `hotel_id`.
* `solicitudes_factura` por `reservacion_id` sin `hotel_id`.
* `reservacion_habitaciones` por `reservacion_id` sin `hotel_id`.
* `Reservacion::find($reservacion_id)` global en `cotizacionReservacionPdfAction()`.

## 7. Joins a validar

* `reservaciones`: `r.hotel_id = ?`.
* `reservacion_habitaciones`: `rh.hotel_id = r.hotel_id`.
* `habitaciones`: `hab.hotel_id = rh.hotel_id`.
* `reservacion_pagos`: `rp.hotel_id = r.hotel_id`.
* `solicitudes_factura`: `sf.hotel_id = r.hotel_id` o `sf.hotel_id = ?`.
* `movimientos_caja`: `mc.hotel_id = ?` si aparece en fases posteriores.

## 8. Decisiones tecnicas

* `huespedes` debe quedar como join auxiliar, no como fuente de scope.
* `usuarios` debe quedar como join auxiliar, no como fuente de scope.
* No tocar modelo tenant de huespedes todavia.
* No tocar PWA/offline, Sync ni APIs en esta fase.

## 9. Que implementar primero

Reservaciones 1-F-F-E-E-B:

* Implementar unicamente `ReservacionController::exportarPDFAction()` scoped por `hotel_id`.

## 10. Que dejar para despues

* Reservaciones 1-F-F-E-E-C: `ReservacionController::exportarExcelAction()` scoped.
* Reservaciones 1-F-F-E-E-D: `cotizacionReservacionPdfAction()` y `cotizacionPdfAction()`.
* Reservaciones 1-F-F-E-E-E: pagos/factura/caja relacionados.
* Fase separada para PWA/Sync/API.

## 11. Pruebas necesarias

* `php -l` en archivos tocados.
* `git diff --check`.
* `verificar_estado.php` PASS.
* `preflight_hotel_id.php` PASS.
* GET controlado de `/reservaciones/exportar-pdf?fecha=YYYY-MM-DD`.
* GET controlado de `/reservaciones/exportar-excel?fecha=YYYY-MM-DD`.
* Confirmar que PWA/Sync/APIs no se tocaron.
* Confirmar 0 nuevos `hotel_id` NULL.
