# Reservaciones 1-F-B-EXEC - Ejecucion local de hotel_id en Caja base

Fecha de ejecucion: 2026-05-26  
Base afectada: `medisoft_hoteles_import`  
Rama: `feature/saas-multihotel`

## Objetivo

Documentar la ejecucion local de la migracion para agregar `hotel_id` a Caja base, cortes y movimientos de caja.

## Backup pre-migracion

Backup pre-migracion confirmado:

```text
backups/backup_pre_caja_1F_B_hotel_id.sql
```

El backup fue generado antes de ejecutar la migracion. Se uso `mysqldump --single-transaction --skip-lock-tables` para evitar bloqueo de tablas.

## Backup post-migracion

No se reporto backup post-migracion para esta fase.

## Migracion ejecutada

```text
migrations/20260526_009_add_hotel_id_caja_base.sql
```

La migracion fue ejecutada localmente con cliente MySQL porque usa `DELIMITER`.

## Tablas migradas

- `cajas`
- `cortes_caja`
- `movimientos_caja`

`hotel_id` existe como `INT NULL` en las tres tablas.

## Conteos por tabla

| Tabla | Total | Los Cedros | NULL |
| --- | ---: | ---: | ---: |
| `cajas` | 1 | 1 | 0 |
| `cortes_caja` | 214 | 214 | 0 |
| `movimientos_caja` | 1381 | 1381 | 0 |

## Confirmacion 0 NULL

- `cajas.hotel_id NULL`: 0
- `cortes_caja.hotel_id NULL`: 0
- `movimientos_caja.hotel_id NULL`: 0

## Indices creados

- `idx_cajas_hotel_id`
- `idx_cortes_caja_hotel_id`
- `idx_movimientos_caja_hotel_id`

## Migracion registrada

La migracion quedo registrada en `migrations`:

```text
20260526_009_add_hotel_id_caja_base.sql
```

## Que NO se toco

- Codigo funcional.
- Caja funcional.
- Reportes/Dashboard.
- PWA/offline.
- Sync.
- APIs.
- `reservacion_pagos`.
- `reservaciones`.
- `reservacion_habitaciones`.
- `habitaciones`.
- `movimientos_inventario`.
- `huespedes`.
- `sync_queue`.
- `push_subscriptions`.

## Resultado de pruebas HTTP

No hubo errores 500.

| Ruta | Resultado |
| --- | --- |
| `/login` | 200 |
| `/` | 200 |
| `/dashboard` | 303 |
| `/habitaciones` | 303 |
| `/reservaciones` | 303 |
| `/caja` | 303 |

## Estado de herramientas antes de esta subfase

`verificar_estado.php` y `preflight_hotel_id.php` fallaban de forma esperada porque todavia no reconocian `hotel_id` en Caja base.

## Riesgos pendientes

- Codigo funcional de Caja todavia no escribe ni filtra por `hotel_id`.
- Reportes y Dashboard financieros siguen pendientes.
- PWA/offline, Sync y APIs siguen fuera de scope.
- `hotel_id` sigue `INT NULL` por diseno conservador.
- No se crearon foreign keys estrictas por datos historicos y riesgos financieros.

## Siguiente paso recomendado

Reservaciones 1-F-B.1: actualizar herramientas SaaS para reconocer Caja base con `hotel_id`.
