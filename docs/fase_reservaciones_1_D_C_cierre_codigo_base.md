# Fase Reservaciones 1-D-C - Cierre tecnico de codigo base

## Objetivo

Documentar el cierre tecnico de Reservaciones base despues de integrar `hotel_id` en el codigo funcional acotado a creacion, listado, detalle, calendario, disponibilidad base, asignacion de habitaciones y notas.

Esta fase es documental:

- No modifica codigo funcional.
- No ejecuta SQL.
- No toca base de datos.
- No hace migraciones.
- No hace `ALTER TABLE`.
- No avanza a check-in/check-out, Caja, PWA/offline, Sync ni APIs.

## Que se logro

El modulo Reservaciones base queda en un estado tenant-aware inicial y controlado:

- `hotel_id` ya existe en tablas base de Reservaciones.
- El backfill a Los Cedros quedo completo.
- Las herramientas SaaS estan en PASS.
- El codigo base de Reservaciones ya escribe `hotel_id`.
- Listado, detalle, calendario y disponibilidad base quedan scoped por `hotel_id`.
- La asignacion de habitaciones queda scoped por `hotel_id`.
- Las notas de reservacion quedan scoped por `hotel_id`.
- Las habitaciones seleccionadas se validan contra el hotel actual.

Tablas base ya migradas:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_abonos`
- `reservacion_notas`
- `solicitudes_factura`

## Commits relacionados

Commits funcionales y documentales relacionados con esta etapa:

- Auditoria multi-hotel de Reservaciones.
- Auditoria de reservaciones sin habitacion, huerfanos historicos y fallback.
- Migracion 008 preparada para `hotel_id` en Reservaciones base.
- Migracion 008 ejecutada localmente.
- Actualizacion de herramientas SaaS para aceptar el estado post Reservaciones 1-B.
- Auditoria de codigo base de Reservaciones.
- Implementacion de codigo base scoped por hotel.

Commits de referencia:

- `docs: document reservations multihotel audit`
- `docs: document reservations orphan fallback audit`
- `chore: prepare reservations hotel_id migration`
- `chore: update saas tools for reservations hotel_id`
- `docs: document reservations base code audit`
- `feat: scope reservations base to current hotel`

## Que NO se toco

Quedaron fuera de esta fase:

- check-in;
- check-out;
- cancelaciones;
- Caja;
- pagos/cortes funcionales;
- PWA/offline;
- Sync;
- APIs globales;
- Dashboard/reportes;
- Huespedes como modelo tenant;
- Facturacion funcional;
- conversion de `hotel_id` a `NOT NULL`;
- foreign keys estrictas.

Tambien quedan fuera cambios de schema, indices unicos y migraciones adicionales.

## Riesgos pendientes

Persisten riesgos importantes que deben tratarse en fases separadas:

- Check-in/check-out todavia operan sobre Reservaciones sin fase tenant especifica.
- Caja y pagos siguen pendientes.
- PWA/offline y Sync pueden crear o modificar reservaciones sin `hotel_id` si no se migran.
- Huespedes siguen pendientes de decision: globales vs por hotel.
- APIs globales siguen pendientes.
- Dashboard/reportes siguen pendientes.
- `hotel_id` sigue `INT NULL` por diseno conservador.
- No hay FKs estrictas por huerfanos historicos.
- Las tablas financieras y operativas relacionadas todavia requieren auditoria antes de endurecer integridad.

## Validaciones actuales

Estado validado al cierre de la fase:

- `verificar_estado.php` da PASS.
- `preflight_hotel_id.php` da PASS.
- 0 `hotel_id NULL` en tablas base de Reservaciones con registros.
- Reservaciones 48 y 49 estan asignadas a Los Cedros.
- Pruebas transaccionales de creacion, edicion/asignacion y notas terminaron con rollback.
- No quedaron datos QA persistidos.
- No se tocaron Caja, PWA/offline, Sync ni APIs.

Tablas base con 0 `hotel_id NULL`:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_notas`
- `solicitudes_factura`

`reservacion_abonos` esta vacia y conserva `hotel_id` listo para escrituras futuras.

## Siguiente fase recomendada

La siguiente fase recomendada es:

```text
Reservaciones 1-E-A: auditoria de check-in/check-out con hotel_id
```

Debe ser auditoria primero y no implementacion directa porque check-in/check-out conecta varios dominios sensibles:

- estados de `reservaciones`;
- estados de `habitaciones`;
- inventario automatico;
- pagos;
- Caja;
- llaves/remotos;
- historial operativo;
- posibles flujos express o tardios.

Implementar directo en check-in/check-out podria mezclar cambios de Reservaciones, Caja, Inventario y operacion diaria en una sola fase. La ruta segura es auditar primero todos los puntos de lectura/escritura, definir el alcance exacto y separar despues la implementacion por riesgo.
