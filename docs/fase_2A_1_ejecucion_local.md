# Fase 2A.1 - Ejecucion local hotel_id Habitaciones

## Fecha de ejecucion

2026-05-26

## Base afectada

- `medisoft_hoteles_import`

## Migracion ejecutada

- `migrations/20260526_003_add_hotel_id_habitaciones.sql`

La migracion se ejecuto localmente con cliente MySQL por el uso de `DELIMITER`.

## Backup previo

- `backups/backup_pre_fase2A1_hotel_id_habitaciones.sql`

El backup se genero antes de ejecutar la migracion con `mysqldump --single-transaction --skip-lock-tables` porque el intento con locks fallo por un `DEFINER` inexistente en la base importada.

## Tablas migradas

Solo se migraron estas tablas del modulo Habitaciones:

- `tipos_habitacion`
- `habitaciones`
- `habitacion_imagenes`
- `mantenimientos_habitaciones`

## Conteos y backfill

| Tabla | Registros | Registros con hotel_id Los Cedros | hotel_id NULL |
| --- | ---: | ---: | ---: |
| `tipos_habitacion` | 6 | 6 | 0 |
| `habitaciones` | 49 | 49 | 0 |
| `habitacion_imagenes` | 2 | 2 | 0 |
| `mantenimientos_habitaciones` | 9 | 9 | 0 |

Todos los registros existentes quedaron asociados al hotel inicial Los Cedros.

## Indices creados

- `idx_tipos_habitacion_hotel_id`
- `idx_habitaciones_hotel_id`
- `idx_habitacion_imagenes_hotel_id`
- `idx_mantenimientos_habitaciones_hotel_id`

## Foreign keys creadas

- `fk_tipos_habitacion_hotel`
- `fk_habitaciones_hotel`
- `fk_habitacion_imagenes_hotel`
- `fk_mantenimientos_habitaciones_hotel`

Todas apuntan a `hoteles(id)`.

## Registro de migracion

La tabla `migrations` registro:

- `20260526_003_add_hotel_id_habitaciones.sql`

Estado:

- `ejecutada`

## Tablas no tocadas

No se agrego `hotel_id` ni se hicieron cambios en:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `cajas`
- `cortes_caja`
- `movimientos_caja`
- `sync_queue`
- `push_subscriptions`

Tampoco se tocaron login, sesiones, dashboard, caja, check-in/check-out ni PWA/offline.

## Pruebas HTTP

Resultado de pruebas locales:

- `GET /login => 200 OK`
- `GET / => 200 OK`
- `GET /dashboard => 200 OK`
- `GET /habitaciones => 200 OK`

## Resultado de herramientas antes de actualizarlas

Despues de la migracion, las herramientas antiguas fallaron de forma esperada porque aun consideraban invalido que tablas operativas tuvieran `hotel_id`.

Esa diferencia fue atendida en la Fase 2A.1.1 actualizando:

- `src/tools/saas/verificar_estado.php`
- `src/tools/saas/preflight_hotel_id.php`

## Riesgos pendientes

- El codigo funcional de Habitaciones todavia no escribe `hotel_id` automaticamente.
- Registros nuevos creados antes de integrar codigo pueden quedar con `hotel_id = NULL`.
- `habitaciones.numero` sigue siendo unico global.
- `tipos_habitacion.codigo` sigue siendo unico global.
- Aun no existe filtrado funcional por hotel activo en Habitaciones.
- Reservaciones, caja y PWA/offline siguen pendientes y no deben tocarse sin aprobacion explicita.
