# Reservaciones 1-F-F-E-E-E-D-D-D-B - Cierre de FacturacionController::indexAction

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `FacturacionController::indexAction()`.

## 2. Que se logro

- `indexAction()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- El listado de `solicitudes_factura` se filtra con `sf.hotel_id = ?`.
- Las reservaciones se filtran con `r.hotel_id = ?`.
- Se valida `sf.reservacion_id = r.id`.
- Se valida `sf.hotel_id = r.hotel_id`.
- Se mantiene la logica original de filtros, busqueda, paginacion y listado.
- `huespedes` y `usuarios` quedan como joins auxiliares, no como fuente de scope.

## 3. Archivo modificado

- `src/app/controllers/FacturacionController.php`

## 4. Que NO se toco

- `verAction()`.
- `obtenerSolicitudCompleta()`.
- `obtenerEstadisticas()`.
- `guardarAction()`.
- `completarAction()`.
- `enProcesoAction()`.
- `cancelarAction()`.
- `Reservacion.php`.
- Cancelaciones.
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
- `GET /facturacion` respondio 303 a `/login` sin error 500.
- Confirmacion de que no se tocaron detalle/pagos/estadisticas/cancelaciones/modificar dias/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- `obtenerSolicitudCompleta()` sigue global.
- `verAction()` sigue pendiente, especialmente pagos desde `movimientos_caja`.
- `obtenerEstadisticas()` sigue global.
- Acciones POST de facturacion deben revisarse por dependencia de detalle global.
- Cancelaciones y modificar dias siguen pendientes.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-C-A: auditoria/preparacion de `obtenerSolicitudCompleta()` y `verAction()`, incluyendo pagos desde `movimientos_caja`, sin tocar todavia estadisticas.
