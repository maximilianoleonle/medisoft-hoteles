# Reservaciones 1-F-F-E-E-E-D-D-D-C-C - Cierre de pagos en detalle de facturacion scoped

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en los pagos mostrados dentro de `FacturacionController::verAction()`.

## 2. Que se logro

- `verAction()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()` para la consulta de pagos.
- La consulta de pagos filtra `movimientos_caja.hotel_id = ?`.
- Se conserva el uso de `reservacion_id`.
- Se mantiene la logica original de visualizacion de pagos.
- El detalle principal sigue cargandose desde `obtenerSolicitudCompleta()`, que ya estaba scoped por `hotel_id`.
- Los pagos mostrados en el detalle de facturacion ya quedan limitados al hotel actual.

## 3. Archivo modificado

- `src/app/controllers/FacturacionController.php`.

## 4. Que NO se toco

- `indexAction()`.
- `obtenerSolicitudCompleta()`.
- `obtenerEstadisticas()`.
- `guardarAction()`.
- `completarAction()`.
- `enProcesoAction()`.
- `cancelarAction()`.
- Cancelaciones de reservacion.
- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.
- Base de datos.

## 5. Validaciones realizadas

- `php -l` en `FacturacionController.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/facturacion/ver/1` respondio 303 a `/login` sin error 500.
- Confirmacion de que no se tocaron estadisticas, acciones POST, cancelaciones, modificar dias, PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- `obtenerEstadisticas()` sigue global.
- Acciones POST de facturacion siguen pendientes.
- Cancelaciones y modificar dias siguen pendientes.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-D-A: auditoria/preparacion de `FacturacionController::obtenerEstadisticas()` scoped por `hotel_id`, sin implementacion directa.
