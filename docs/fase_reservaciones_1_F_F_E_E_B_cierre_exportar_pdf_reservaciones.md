# Reservaciones 1-F-F-E-E-B-CIERRE - Exportar PDF de reservaciones

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `ReservacionController::exportarPDFAction()`.

## 2. Que se logro

* `exportarPDFAction()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
* Habitaciones activas se filtran por `hotel_id`.
* Reservaciones se filtran con `r.hotel_id = ?`.
* `reservacion_habitaciones` se valida con `rh.hotel_id = r.hotel_id`.
* `habitaciones` se valida con `hab.hotel_id = rh.hotel_id`.
* `solicitudes_factura` se valida por `hotel_id`.
* Precios en `reservacion_habitaciones` se validan por `hotel_id`.
* `huespedes` queda como join auxiliar, no como fuente de scope.
* La logica original del PDF se conserva.

## 3. Archivo modificado

* `src/app/controllers/ReservacionController.php`.

## 4. Que NO se toco

* `exportarExcelAction()`.
* `cotizacionReservacionPdfAction()`.
* `cotizacionPdfAction()`.
* PWA/offline.
* Sync.
* APIs globales.
* Migraciones.
* Schema.
* Base de datos.

## 5. Validaciones realizadas

* `php -l` en `ReservacionController.php`.
* `git diff --check`.
* `verificar_estado.php` PASS.
* `preflight_hotel_id.php` PASS.
* GET `/reservaciones/exportar-pdf?fecha=2026-05-28` respondio 303 a `/login` sin error 500.
* Confirmacion de que no se tocaron Excel/cotizaciones/PWA/Sync/APIs.
* 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

* `exportarExcelAction()` sigue pendiente.
* `cotizacionReservacionPdfAction()` sigue pendiente.
* `cotizacionPdfAction()` sigue pendiente.
* Pagos/factura/caja relacionados quedan para fases posteriores.
* PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-C: implementar `exportarExcelAction()` scoped por `hotel_id`, sin tocar todavia cotizaciones PDF.
