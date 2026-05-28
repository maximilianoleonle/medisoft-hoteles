# Reservaciones 1-F-F-E-E-E-D-B-CIERRE - Cierre de creacion de solicitudes_factura

## Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en la creacion de `solicitudes_factura` relacionadas con reservaciones.

## Que se logro

- `procesarSolicitudFactura()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- `procesarSolicitudFactura()` valida la reservacion con `obtenerPorId()`.
- `procesarSolicitudFactura()` pasa `hotel_id` a `crearSolicitudFactura()`.
- `Reservacion::crearSolicitudFactura()` valida `reservacion_id` contra `reservaciones.hotel_id`.
- `INSERT INTO solicitudes_factura` ahora incluye `hotel_id`.
- Las nuevas `solicitudes_factura` nacen alineadas con el hotel de la reservacion.
- Se conserva la logica original de creacion de solicitud.

## Archivos modificados

- `src/app/controllers/ReservacionController.php`
- `src/app/models/Reservacion.php`

## Que NO se toco

- `obtenerSolicitudFactura()`.
- `obtenerSolicitudesPendientes()`.
- `actualizarSolicitudFactura()`.
- `FacturacionController`.
- `cancelarAction()`.
- `Reservacion::cancelar()`.
- `modificarDiasAction()`.
- pagos/caja no relacionados directamente.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- base de datos.

## Validaciones realizadas

- `php -l` en `ReservacionController.php`.
- `php -l` en `Reservacion.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- No se hizo prueba funcional porque crear solicitud de factura escribiria en base de datos sin rollback seguro autorizado.
- Confirmacion de 0 nuevos `hotel_id` NULL segun herramientas.
- Confirmacion de que no se tocaron `FacturacionController`, cancelaciones, modificar dias, PWA/Sync/APIs.

## Riesgos pendientes

- `obtenerSolicitudFactura()` sigue pendiente.
- `obtenerSolicitudesPendientes()` sigue pendiente.
- `actualizarSolicitudFactura()` sigue pendiente.
- `FacturacionController` sigue global.
- Cancelaciones y `Reservacion::cancelar()` siguen pendientes.
- Modificar dias sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-C-A: auditoria/preparacion de lecturas de `solicitudes_factura`, empezando por `obtenerSolicitudFactura()`, sin tocar todavia `FacturacionController` completo ni cancelaciones.
