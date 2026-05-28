# Reservaciones 1-F-F-E-E-E-D-D-D-C-B - Cierre de detalle de facturacion scoped

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `FacturacionController::obtenerSolicitudCompleta()`.

## 2. Que se logro

- `obtenerSolicitudCompleta()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- La consulta filtra `solicitudes_factura` con `sf.id = ? AND sf.hotel_id = ?`.
- Se valida `sf.hotel_id = r.hotel_id`.
- Se valida `r.hotel_id = ?`.
- El detalle de facturacion ya carga unicamente solicitudes del hotel actual.
- `huespedes` y `usuarios` quedan como joins auxiliares, no como fuente de scope.
- Se conserva la logica original del detalle.

## 3. Archivo modificado

- `src/app/controllers/FacturacionController.php`.

## 4. Que NO se toco

- `verAction()`.
- Pagos de `movimientos_caja`.
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
- Confirmacion de que no se tocaron pagos/`verAction`/estadisticas/cancelaciones/modificar dias/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- Pagos de `verAction()` siguen pendientes porque `movimientos_caja` aun debe filtrarse por `hotel_id`.
- `obtenerEstadisticas()` sigue pendiente.
- Acciones POST de facturacion siguen pendientes de revision por dependencia de detalle.
- Cancelaciones y modificar dias siguen pendientes.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-C-C: implementar unicamente pagos de `FacturacionController::verAction()` scoped por `movimientos_caja.hotel_id`, sin tocar todavia estadisticas ni acciones POST.
