# Reservaciones 1-F-F-E-E-D-A: auditoria de cotizaciones PDF

## 1. Objetivo

Auditar las cotizaciones PDF de Reservaciones antes de hacerlas tenant-aware.

## 2. Hallazgo principal

- El riesgo principal esta en `cotizacionReservacionPdfAction()`.
- `cotizacionReservacionPdfAction()` usa `Reservacion::find()`, heredado de `Model::find()`, que consulta por id sin `hotel_id`.
- `cotizacionPdfAction()` no usa reservaciones guardadas, pero usa `Huesped::find()` global.
- Las habitaciones de `cotizacionPdfAction()` pasan por `Habitacion::find()`, que ya esta scoped.

## 3. Archivos encontrados

- `src/app/controllers/ReservacionController.php`
- `src/app/models/Reservacion.php`
- `src/app/models/Habitacion.php`
- `src/app/models/Huesped.php`
- `src/core/Model.php`
- `src/config/routes.php`
- `src/app/views/reservaciones/crear.php`
- `src/app/views/reservaciones/ver.php`
- `src/public_html/fdpdf/fpdf.php`

## 4. Metodos auditados

- `cotizacionReservacionPdfAction()`
- `cotizacionPdfAction()`
- `Reservacion::find()`
- `Reservacion::obtenerPorId()`
- `Reservacion::getHabitaciones()`
- `Huesped::find()`
- `Habitacion::find()`

## 5. Estado por metodo

- `cotizacionReservacionPdfAction()`: riesgo alto, usa `Reservacion::find()` global.
- `cotizacionPdfAction()`: riesgo medio, usa `Huesped::find()` global, pero habitaciones ya van por `Habitacion::find()` scoped.
- `Reservacion::find()`: global por `Model::find()`.
- `Reservacion::obtenerPorId()`: scoped por `hotel_id`.
- `Reservacion::getHabitaciones()`: scoped por `hotel_id`.
- `Huesped::find()`: global, tratar como auxiliar.
- `Habitacion::find()`: scoped por `hotel_id`.
- FPDF/render inline: render-only, riesgo bajo si recibe datos scoped.

## 6. Consultas globales detectadas

- `Reservacion::find($reservacion_id)` carga reservaciones solo por id.
- `Huesped::find()` carga huesped solo por id.
- `huesped_vehiculos` se consulta por `huesped_id` sin scope, aceptable solo despues de validar la reservacion scoped.

## 7. Que debe cambiarse primero

Reservaciones 1-F-F-E-E-D-B:

- `cotizacionReservacionPdfAction()` debe usar `obtenerHotelIdActualCompat()`.
- Reemplazar `Reservacion::find()` por `Reservacion::obtenerPorId()` o query equivalente scoped.
- Validar `reservaciones.hotel_id`.
- Mantener `Reservacion::getHabitaciones()` porque ya valida `reservacion_habitaciones` y `habitaciones`.
- Usar `huespedes` y `huesped_vehiculos` solo como datos auxiliares derivados de una reservacion ya scoped.

## 8. Que debe esperar

- `cotizacionPdfAction()` scoped en fase posterior.
- pagos/factura/caja relacionados.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones/schema.
- cambios de datos.
- huespedes tenant.
- usuarios tenant.

## 9. Pruebas necesarias

- `php -l src/app/controllers/ReservacionController.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- POST controlado a `/reservaciones/cotizacion-reservacion-pdf`.
- POST controlado a `/reservaciones/cotizacion-pdf` cuando toque su fase.
- Confirmar que PWA/Sync/APIs no se tocaron.
- Confirmar 0 nuevos `hotel_id` NULL.

## 10. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-D-B: implementar unicamente `cotizacionReservacionPdfAction()` scoped por `hotel_id`, sin tocar todavia `cotizacionPdfAction()`.
