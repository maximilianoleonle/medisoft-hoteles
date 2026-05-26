# Fase Reservaciones 1-B-EXEC - Ejecucion local de hotel_id base

Fecha de ejecucion: 2026-05-26  
Base afectada: `medisoft_hoteles_import`  
Rama: `feature/saas-multihotel`

## Objetivo

Documentar la ejecucion local de la migracion base de Reservaciones para agregar `hotel_id` a Reservaciones y tablas hijas directas, sin tocar codigo funcional, Caja, PWA/offline, Sync, APIs ni check-in/check-out.

## Backup pre-migracion

Backup pre-migracion:

```text
backups/backup_pre_reservaciones_1B_hotel_id_base.sql
```

Durante la verificacion previa, el backup indicado no existia fisicamente con ese nombre. Se genero un backup fresco con `mysqldump --single-transaction --quick --skip-lock-tables` antes de ejecutar la migracion.

## Backup post-migracion

No se genero backup post-migracion en esta fase.

## Migracion ejecutada

Archivo ejecutado:

```text
migrations/20260526_008_add_hotel_id_reservaciones_base.sql
```

La ejecucion se realizo con cliente MySQL porque la migracion usa `DELIMITER`.

## Tablas migradas

Se agrego `hotel_id INT NULL` a:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_abonos`
- `reservacion_notas`
- `solicitudes_factura`

No se crearon foreign keys estrictas y no se convirtio `hotel_id` a `NOT NULL`.

## Conteos por tabla

| Tabla | Total | Los Cedros | NULL |
| --- | ---: | ---: | ---: |
| `reservaciones` | 1232 | 1232 | 0 |
| `reservacion_habitaciones` | 2389 | 2389 | 0 |
| `reservacion_pagos` | 1202 | 1202 | 0 |
| `reservacion_abonos` | 0 | 0 | 0 |
| `reservacion_notas` | 211 | 211 | 0 |
| `solicitudes_factura` | 205 | 205 | 0 |

## Confirmacion de 0 NULL

Todas las tablas objetivo quedaron con 0 registros `hotel_id IS NULL`.

`reservacion_abonos` esta vacia, por lo que su conteo total y NULL es 0.

## Reservaciones 48 y 49

Las reservaciones sin habitacion aprobadas para fallback quedaron asignadas a Los Cedros:

- `reservaciones.id = 48` -> `hotel_id = 1`
- `reservaciones.id = 49` -> `hotel_id = 1`

## Indices creados

Se confirmaron los indices simples:

- `idx_reservaciones_hotel_id`
- `idx_reservacion_habitaciones_hotel_id`
- `idx_reservacion_pagos_hotel_id`
- `idx_reservacion_abonos_hotel_id`
- `idx_reservacion_notas_hotel_id`
- `idx_solicitudes_factura_hotel_id`

## Migracion registrada

Registro confirmado en `migrations`:

```text
20260526_008_add_hotel_id_reservaciones_base.sql
```

Estado: `ejecutada`  
Batch: `5`

## Que NO se toco

No se agrego `hotel_id` ni se modificaron:

- `movimientos_caja`
- `cortes_caja`
- `cajas`
- `huespedes`
- `movimientos_inventario`
- `sync_queue`
- `push_subscriptions`
- APIs/PWA
- Sync
- check-in/check-out
- codigo funcional

## Resultado de pruebas HTTP

Pruebas HTTP realizadas sin errores 500:

- `GET /login` -> 200
- `GET /` -> 200
- `GET /dashboard` -> 303 a `/login`
- `GET /habitaciones` -> 303 a `/login`
- `GET /reservaciones` -> 303 a `/login`

## Herramientas SaaS antes de actualizacion

Despues de ejecutar la migracion, `verificar_estado.php` y `preflight_hotel_id.php` fallaron de forma esperada porque todavia no reconocian `hotel_id` en tablas base de Reservaciones como estado valido.

## Riesgos pendientes

- El codigo funcional de Reservaciones todavia no escribe ni filtra por `hotel_id`.
- Check-in/check-out todavia depende de Reservaciones sin scope funcional por hotel.
- Caja, PWA/offline, Sync y APIs siguen fuera de alcance.
- `huespedes` sigue pendiente hasta decidir si sera global o por hotel.
- No hay foreign keys estrictas hacia `hoteles(id)` en las tablas migradas por decision conservadora.
- `hotel_id` sigue permitiendo `NULL` hasta integrar codigo funcional.

## Siguiente fase recomendada

La siguiente fase recomendada es Reservaciones 1-C: actualizar herramientas SaaS para aceptar Reservaciones 1-B como estado valido, sin tocar codigo funcional.
