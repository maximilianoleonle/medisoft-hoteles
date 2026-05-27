# Fase Reservaciones 1-E-C - Cierre check-in/check-out por hotel_id

## Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en check-in/check-out de Reservaciones.

Esta fase documenta el resultado posterior a la implementacion controlada de Reservaciones 1-E-B. No representa un cierre completo multi-hotel de todos los flujos relacionados, porque Caja, PWA/offline, Sync, APIs globales y reportes siguen pendientes.

## Que se logro

- Check-in scoped por `hotel_id`.
- Check-out scoped por `hotel_id`.
- Check-out rapido scoped por `hotel_id`.
- Check-out parcial scoped por `hotel_id`.
- Check-in tardio scoped por `hotel_id`.
- Check-in/check-out express scoped por `hotel_id`.
- Preconsultas de `procesarDescuentoInventario()` scoped por `hotel_id`.
- Actualizaciones de `reservaciones` usando `WHERE id = ? AND hotel_id = ?`.
- Actualizaciones de `habitaciones` validando `hotel_id`.
- Inserts existentes en `reservacion_pagos` incluyen `hotel_id` cuando aplica para evitar nuevos NULL.

## Commits relacionados

- Auditoria check-in/check-out:
  - `docs: document reservations checkin checkout audit`
- Preparacion de scope check-in/check-out:
  - `docs: prepare reservations checkin checkout scope`
- Implementacion de check-in/check-out scoped:
  - `fix: scope reservations checkin checkout to current hotel`

## Que NO se toco

- Caja funcional.
- `movimientos_caja`.
- `cortes_caja`.
- PWA/offline.
- Sync.
- APIs globales.
- Dashboard/reportes.
- Facturacion funcional.
- Huespedes como modelo tenant.
- Migraciones.
- Schema.

## Validaciones

Se realizaron las siguientes validaciones:

- `php -l` en archivos modificados.
- `git diff --check`.
- `verificar_estado.php` con resultado PASS.
- `preflight_hotel_id.php` con resultado PASS.
- Read-only NULL check en tablas de Reservaciones.

Resultado del read-only NULL check:

- `reservaciones`: 0 `hotel_id` NULL.
- `reservacion_habitaciones`: 0 `hotel_id` NULL.
- `reservacion_pagos`: 0 `hotel_id` NULL.
- `reservacion_notas`: 0 `hotel_id` NULL.
- `solicitudes_factura`: 0 `hotel_id` NULL.
- `movimientos_inventario`: 0 `hotel_id` NULL.

No se hicieron pruebas funcionales con escritura real porque los metodos actuales abren/cierran transacciones internas y no se podia garantizar rollback seguro sin dejar datos persistidos.

## Riesgos pendientes

- Caja/pagos/cortes todavia no son tenant-aware.
- PWA/offline y Sync pueden tener caminos paralelos.
- APIs globales siguen pendientes.
- Huespedes siguen pendientes de decision global vs por hotel.
- Dashboard/reportes siguen pendientes.
- La validacion funcional real de check-in/check-out debe hacerse en entorno controlado con datos de prueba o backup fresco.
- Llaves/remotos siguen fuera de este cierre si requieren tenant propio.

## Siguiente fase recomendada

Reservaciones 1-F-A: Auditoria de pagos/caja/cortes vinculados a Reservaciones.

No se recomienda implementar Caja directo todavia. Caja es un modulo financiero, afecta movimientos, cortes y reportes operativos, y cualquier correccion debe iniciar con auditoria read-only y propuesta tecnica antes de cambiar codigo o datos.
