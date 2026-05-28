# Reservaciones 1-F-F-E-E-E-D-D-D-D-B - Cierre de estadisticas de facturacion scoped

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `FacturacionController::obtenerEstadisticas()`.

## 2. Que se logro

- `obtenerEstadisticas()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- Las consultas globales `query()` fueron reemplazadas por `prepare/execute`.
- Cada metrica filtra `solicitudes_factura` con `sf.hotel_id = ?`.
- Se valida `sf.reservacion_id = r.id`.
- Se valida `sf.hotel_id = r.hotel_id`.
- Se valida `r.hotel_id = ?`.
- Las estadisticas de facturacion ya calculan metricas unicamente del hotel actual.
- Se conserva la logica original de metricas.

## 3. Metricas cerradas

- `pendientes`.
- `en_proceso`.
- `completadas_mes`.
- `pendientes_cliente`.
- `pendientes_interno`.
- `monto_pendiente`.

## 4. Archivo modificado

- `src/app/controllers/FacturacionController.php`.

## 5. Que NO se toco

- `indexAction()`.
- `obtenerSolicitudCompleta()`.
- `verAction()`.
- Pagos.
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

## 6. Validaciones realizadas

- `php -l` en `FacturacionController.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/facturacion` respondio 303 a `/login` sin error 500.
- Confirmacion de que no se tocaron acciones POST, cancelaciones, modificar dias, PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 7. Riesgos pendientes

- Acciones POST de facturacion siguen pendientes:
  - `guardarAction()`.
  - `completarAction()`.
  - `enProcesoAction()`.
  - `cancelarAction()`.
- Cancelaciones de reservacion siguen pendientes.
- `modificarDiasAction()` sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 8. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-E-A: auditoria/preparacion de acciones POST de `FacturacionController`, sin implementacion directa.
