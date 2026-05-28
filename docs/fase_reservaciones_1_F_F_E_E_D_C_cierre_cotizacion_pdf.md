# Reservaciones 1-F-F-E-E-D-C-CIERRE: cotizacionPdfAction scoped por hotel_id

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en `ReservacionController::cotizacionPdfAction()`.

## 2. Que se logro

- `cotizacionPdfAction()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- Mantiene `Habitacion::find()`, que ya valida `hotel_id`.
- Valida defensivamente que cada habitacion exista.
- Valida que cada habitacion pertenezca al hotel actual.
- `Huesped::find()` queda como dato auxiliar, no como fuente de scope.
- No se convirtio huespedes a tenant.
- Se conserva la logica original de la cotizacion PDF.
- Si una habitacion no existe o no pertenece al hotel actual, no genera PDF y responde con error controlado.

## 3. Archivo modificado

- `src/app/controllers/ReservacionController.php`

## 4. Que NO se toco

- `cotizacionReservacionPdfAction()`.
- `exportarPDFAction()`.
- `exportarExcelAction()`.
- pagos/factura/caja.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- base de datos.
- huespedes tenant.

## 5. Validaciones realizadas

- `php -l` en `ReservacionController.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- POST `/reservaciones/cotizacion-pdf` respondio 303 a `/login` sin error 500.
- Confirmacion de que no se tocaron exportaciones, pagos/factura/caja, PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- `Huesped::find()` sigue global, tratado solo como auxiliar.
- Pagos/factura/caja relacionados quedan para fases posteriores.
- PWA/offline, Sync y APIs siguen fuera de scope.
- Huespedes tenant queda pendiente para una decision futura.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-A: auditoria de pagos/factura/caja relacionados con reservaciones y documentos, sin implementacion directa.
