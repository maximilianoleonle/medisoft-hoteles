# Reservaciones 1-F-F-E-E-E-D-D-D-D-A - Auditoria de estadisticas de facturacion

## 1. Objetivo

Auditar `FacturacionController::obtenerEstadisticas()` para preparar una correccion segura por `hotel_id` en conteos, sumas y metricas de `solicitudes_factura`.

## 2. Hallazgo principal

- `FacturacionController::obtenerEstadisticas()` sigue global.
- Todas sus metricas hacen `COUNT` o `SUM` directamente sobre `solicitudes_factura` sin `hotel_id`.
- La vista `facturacion/index.php` consume esas estadisticas.
- `indexAction()` ya lista por hotel, pero las tarjetas superiores aun pueden mostrar totales globales mientras `obtenerEstadisticas()` no este scoped.

## 3. Archivos encontrados

- `src/app/controllers/FacturacionController.php`.
- `src/app/views/facturacion/index.php`.
- `src/config/routes.php`.

## 4. Metodos o zonas auditadas

- `FacturacionController::indexAction()`.
- `FacturacionController::obtenerEstadisticas()`.
- `facturacion/index.php`.
- Ruta `/facturacion`.

## 5. Metricas globales detectadas

- `pendientes`: `COUNT` con `estatus = pendiente`.
- `en_proceso`: `COUNT` con `estatus = en_proceso`.
- `completadas_mes`: `COUNT` de completadas por mes/anio.
- `pendientes_cliente`: `COUNT` de pendientes tipo cliente.
- `pendientes_interno`: `COUNT` de pendientes tipo uso interno.
- `monto_pendiente`: `SUM(monto_total)` de pendientes/en proceso.

## 6. Riesgos por metrica

Alto:

- `pendientes`.
- `monto_pendiente`.

Medio:

- `en_proceso`.
- `completadas_mes`.
- `pendientes_cliente`.
- `pendientes_interno`.

Bajo:

- `facturacion/index.php`, porque solo renderiza metricas recibidas.

## 7. Propuesta tecnica

- Scopear `obtenerEstadisticas()` usando `obtenerHotelIdActualCompat()`.
- Convertir los `query()` globales en `prepare/execute`.
- Cada `COUNT/SUM` debe filtrar `sf.hotel_id = ?`.
- Conviene unir con `reservaciones` para validar:
  - `sf.reservacion_id = r.id`.
  - `sf.hotel_id = r.hotel_id`.
  - `r.hotel_id = ?`.
- Mantener la logica actual de las metricas.
- No tocar `indexAction()`, ya cerrado.
- No tocar detalle, pagos ni acciones POST.

## 8. Que dejar fuera

- `indexAction()`, ya cerrado.
- `obtenerSolicitudCompleta()`, ya cerrado.
- Pagos de `verAction()`, ya cerrado.
- `guardarAction()`.
- `completarAction()`.
- `enProcesoAction()`.
- `cancelarAction()`.
- Cancelaciones.
- Modificar dias.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.
- Usuarios tenant.

## 9. Pruebas necesarias

- `php -l src/app/controllers/FacturacionController.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET controlado de `/facturacion`; sin sesion puede redirigir a login, pero no debe dar 500.
- Confirmar que PWA/Sync/APIs no se tocaron.
- Confirmar 0 nuevos `hotel_id` NULL.

## 10. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-D-B: implementar unicamente `FacturacionController::obtenerEstadisticas()` scoped por `hotel_id`, sin tocar acciones POST, cancelaciones ni modificar dias.
