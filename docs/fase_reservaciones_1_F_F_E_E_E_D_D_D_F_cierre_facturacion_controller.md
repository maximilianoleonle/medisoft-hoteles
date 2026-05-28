# Reservaciones 1-F-F-E-E-E-D-D-D-F-CIERRE

## Cierre general de FacturacionController scoped por hotel_id

## 1. Objetivo

Documentar el cierre general del bloque de `FacturacionController` scoped por `hotel_id`.

## 2. Que quedo cerrado

- `indexAction()`
- `obtenerSolicitudCompleta()`
- `verAction()`, bloque de pagos
- `obtenerEstadisticas()`
- `guardarAction()`
- `completarAction()`
- `enProcesoAction()`
- `cancelarAction()`

## 3. Que se logro

- Los listados de facturacion ya se filtran por `hotel_id`.
- El detalle de solicitud de factura ya se carga por `hotel_id`.
- Los pagos mostrados en el detalle ya se filtran por `movimientos_caja.hotel_id`.
- Las estadisticas de facturacion ya calculan metricas del hotel actual.
- Las acciones POST validan la solicitud scoped antes de modificar datos.
- `cancelarAction()` valida antes de concatenar notas.
- `solicitudes_factura` se mantiene alineado con `reservaciones.hotel_id`.
- `huespedes` y `usuarios` quedan como auxiliares, no como fuente de scope.

## 4. Que NO se toco

- Cancelaciones de reservacion.
- `Reservacion::cancelar()`.
- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.
- Base de datos.
- Huespedes tenant.
- Usuarios tenant.

## 5. Validaciones generales

- `php -l` en archivos modificados durante el bloque.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Rutas GET revisadas redirigieron a login sin error 500 cuando aplico.
- No se hicieron POST funcionales sin rollback seguro.
- 0 nuevos `hotel_id` NULL segun herramientas.
- Confirmacion de que no se tocaron PWA/Sync/APIs.

## 6. Riesgos pendientes

- Cancelaciones de reservacion siguen pendientes.
- `Reservacion::cancelar()` sigue pendiente.
- `modificarDiasAction()` sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.
- Huespedes tenant y usuarios tenant quedan como decisiones futuras.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-E-A: auditoria de cancelaciones de reservacion, incluyendo `cancelarAction()` y `Reservacion::cancelar()`, sin implementacion directa.
