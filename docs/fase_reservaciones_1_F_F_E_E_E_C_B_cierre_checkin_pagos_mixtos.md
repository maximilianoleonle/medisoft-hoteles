# Reservaciones 1-F-F-E-E-E-C-B-CIERRE - Cierre de check-in con pagos mixtos

## Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en el movimiento de caja generado por `Reservacion::checkInConPagosMixtos()`.

## Que se logro

- `Reservacion::checkInConPagosMixtos()` mantiene `hotelIdActual()` como fuente del hotel actual.
- Se agrego validacion defensiva de que el corte abierto pertenece al hotel actual.
- El `INSERT INTO movimientos_caja` ahora incluye `hotel_id`.
- Se pasa `$hotel_id` como parametro del insert.
- El movimiento de caja generado durante check-in con pagos mixtos ya queda tenant-aware.
- Se conserva la logica original del check-in con pagos mixtos.
- `registrarPagosMixtos()` no se toco porque ya estaba scoped.

## Archivo modificado

- `src/app/models/Reservacion.php`

## Que NO se toco

- `registrarPagosMixtos()`.
- `cancelarAction()`.
- `modificarDiasAction()`.
- `Reservacion::cancelar()`.
- `solicitudes_factura`.
- `procesarSolicitudFactura()`.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- base de datos.

## Validaciones realizadas

- `php -l` en `Reservacion.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- No se hizo prueba funcional de check-in porque escribiria en reservaciones/caja sin rollback seguro autorizado.
- Confirmacion de 0 nuevos `hotel_id` NULL segun herramientas.
- Confirmacion de que no se tocaron cancelaciones, modificar dias, factura, PWA/Sync/APIs.

## Riesgos pendientes

- `solicitudes_factura` sigue pendiente.
- `cancelarAction()` y `Reservacion::cancelar()` siguen pendientes.
- `modificarDiasAction()` sigue pendiente.
- `categorias_movimientos` sigue como catalogo global auxiliar.
- PWA/offline, Sync y APIs siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-A: auditoria/preparacion de `solicitudes_factura` relacionadas con reservaciones, sin implementacion directa.
