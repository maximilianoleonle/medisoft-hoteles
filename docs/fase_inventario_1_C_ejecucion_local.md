# Fase Inventario 1-C - Ejecucion local de hotel_id en base de Inventario

## Objetivo

Documentar la ejecucion local de la migracion que agrega `hotel_id` a las tablas base de Inventario, limitada a catalogos/configuracion y sin tocar movimientos ni flujos operativos.

## Fecha y base afectada

- Fecha de ejecucion: 2026-05-26
- Base afectada: `medisoft_hoteles_import`
- Entorno: local

## Migracion ejecutada

```text
migrations/20260526_006_add_hotel_id_inventario_base.sql
```

La migracion se ejecuto con cliente MySQL porque usa `DELIMITER`.

## Backups

- Backup pre-migracion: `backups/backup_pre_inventario_1C_hotel_id_base.sql`
- Backup post-migracion: no creado en esta fase

## Tablas migradas

Se agrego `hotel_id INT NULL` a:

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`

La columna queda `NULL` temporalmente para no romper inserts mientras el codigo funcional de Inventario no escribe `hotel_id`.

## Conteos verificados

| Tabla | Total | Los Cedros | `hotel_id NULL` |
| --- | ---: | ---: | ---: |
| `inventario_categorias` | 4 | 4 | 0 |
| `inventario_productos` | 30 | 30 | 0 |
| `inventario_config_habitacion` | 50 | 50 | 0 |

Todos los registros existentes quedaron asociados al hotel Los Cedros.

## Indices creados

- `idx_inventario_categorias_hotel_id`
- `idx_inventario_productos_hotel_id`
- `idx_inventario_config_habitacion_hotel_id`

## Foreign keys creadas

- `fk_inventario_categorias_hotel`
- `fk_inventario_productos_hotel`
- `fk_inventario_config_habitacion_hotel`

Todas apuntan a `hoteles(id)`.

## Registro en migrations

La migracion quedo registrada como ejecutada:

```text
20260526_006_add_hotel_id_inventario_base.sql
```

## Tablas no tocadas

Se confirmo que no se agrego `hotel_id` a:

- `movimientos_inventario`
- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- `reservaciones`
- `cajas`
- `movimientos_caja`
- `sync_queue`
- `push_subscriptions`

Tampoco se tocaron Check-in/Check-out, Caja, Reservaciones, PWA/offline ni codigo funcional.

## Pruebas HTTP

Resultado de smoke tests:

```text
/login -> 200 http://localhost:8080/login
/ -> 200 http://localhost:8080/
/dashboard -> 200 http://localhost:8080/login
/habitaciones -> 200 http://localhost:8080/login
```

`/dashboard` y `/habitaciones` redirigen a `/login` sin sesion, comportamiento esperado.

## Resultado inicial de herramientas

Antes de Inventario 1-C.1, las herramientas `verificar_estado.php` y `preflight_hotel_id.php` fallaban de forma esperada porque todavia no reconocian `hotel_id` en:

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`

No se corrigieron automaticamente durante la ejecucion de la migracion.

## Riesgos pendientes

- El codigo funcional de Inventario todavia no escribe ni filtra por `hotel_id`.
- Nuevos inserts en catalogos/configuracion podrian quedar con `hotel_id NULL` hasta integrar codigo.
- `movimientos_inventario` todavia no tiene `hotel_id`.
- Check-in/Check-out y Reservaciones siguen fuera de alcance.
- `inventario_productos.codigo` sigue siendo unico global.
- `inventario_config_habitacion.uk_tipo_producto` sigue siendo unico global.
- `productos` y `inventario_productos` siguen coexistiendo como posibles modulos paralelos o legacy.

## Siguiente paso recomendado

Actualizar las herramientas SaaS para reconocer el nuevo estado post-Inventario 1-C y volver a `PASS`.

Despues, preparar una fase de auditoria de codigo de Inventario base antes de tocar servicios funcionales.
