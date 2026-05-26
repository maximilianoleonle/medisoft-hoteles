# Fase Reservaciones 1-A - Auditoria multi-hotel

## Objetivo

Auditar el modulo Reservaciones para preparar una futura migracion multi-hotel sin romper check-in, check-out, pagos, caja, inventario, huespedes ni PWA/offline.

## Hallazgos principales

- `reservaciones` aun no tiene `hotel_id`.
- 1230 de 1232 reservaciones pueden inferir `hotel_id` por `reservacion_habitaciones -> habitaciones.hotel_id`.
- Hay 2 reservaciones sin habitacion: 48 y 49.
- Hay 466 reservaciones multi-habitacion.
- Reservaciones con habitaciones de hoteles mixtos: 0.
- Existen datos huerfanos historicos que deben tratarse antes de endurecer foreign keys o convertir columnas a `NOT NULL`.

## Conteos live

- `reservaciones`: 1232
- `reservacion_habitaciones`: 2389
- `reservacion_pagos`: 1202
- `reservacion_abonos`: 0
- `reservacion_notas`: 211
- `huespedes`: 1012
- `solicitudes_factura`: 205
- `cajas`: 1
- `cortes_caja`: 214
- `movimientos_caja`: 1381
- `movimientos_inventario`: 936
- `habitaciones`: 49

## Datos problematicos

- Reservaciones sin habitacion: 2.
- `reservacion_habitaciones` huerfanas: 3 filas, con `reservacion_id` 53 y 206.
- `reservacion_pagos` huerfanos: 1 fila, con `reservacion_id` 53.
- `reservacion_notas` huerfanas: 7.
- `movimientos_caja` con reservacion huerfana: 1.
- `movimientos_inventario` con reservacion inexistente: 83.
- `huespedes` sin reservacion: 10.

## Relaciones actuales

- `reservaciones.huesped_id` apunta logicamente a `huespedes.id`.
- `reservacion_habitaciones` asigna habitaciones a reservaciones.
- Pagos, caja y facturacion cuelgan de `reservacion_id`.
- Inventario ya tiene `hotel_id`, pero sigue referenciando `reservacion_id`.

## Archivos criticos

- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/models/Sync.php`
- `src/app/controllers/ApiController.php`
- `src/api/reservaciones/hoy.php`
- `src/api/buscar.php`
- `src/app/models/Huesped.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`
- Vistas de reservaciones.
- JS de reservaciones.
- PWA/offline relacionado.

## Riesgos principales

- Check-in/check-out actualiza reservaciones y habitaciones sin tenant.
- Pagos/caja dependen de `reservacion_id` global.
- PWA/sync puede crear reservaciones y cambiar estados sin `hotel_id`.
- 2 reservaciones sin habitacion no se pueden backfillear solo desde habitaciones.
- Datos huerfanos historicos pueden bloquear foreign keys estrictas.
- Huespedes pueden ser globales o por hotel; falta decision.
- Facturacion depende de reservacion y debe seguir a Reservaciones.

## Tablas que probablemente necesitan hotel_id

Primera ola:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_notas`
- `solicitudes_factura`

Segunda ola:

- `reservacion_abonos`
- `movimientos_caja`
- `cortes_caja`
- `cajas`, si se decide tenant por caja

Decision pendiente:

- `huespedes`

## Orden recomendado

- Reservaciones 1-B-PREP: preparar migracion `hotel_id` en reservaciones y tablas hijas directas, sin ejecutar.
- Reservaciones 1-B-EXEC: backup, backfill y manejo explicito de las 2 reservaciones sin habitacion.
- Reservaciones 1-C: actualizar herramientas SaaS.
- Reservaciones 1-D: codigo base de creacion/edicion/listado.
- Reservaciones 1-E: check-in/check-out.
- Reservaciones 1-F: pagos/caja/facturacion.
- Reservaciones 1-G: API/PWA/sync/offline.

## Que NO se debe tocar todavia

- Caja.
- PWA/offline.
- `Sync.php`.
- APIs globales.
- Check-in/check-out.
- Facturacion.
- Huespedes.
- Foreign keys estrictas.
- `NOT NULL`.
