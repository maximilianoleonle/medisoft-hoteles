# Fase Reservaciones 1-B-PREP-B - Preparacion de hotel_id base

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Preparar una migracion versionada para agregar `hotel_id` a Reservaciones base y sus tablas hijas directas, con backfill hacia Los Cedros, sin ejecutarla todavia y sin tocar codigo funcional.

Esta fase solo crea archivos. No ejecuta SQL, no modifica base de datos, no hace `ALTER TABLE` en ejecucion real y no cambia flujos de check-in/check-out, caja, PWA/offline ni APIs.

## Tablas afectadas

La migracion preparada afecta unicamente:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_abonos`
- `reservacion_notas`
- `solicitudes_factura`
- `migrations`, solo para registrar la migracion al ejecutarla

## Tablas excluidas

Quedan fuera de esta fase:

- `movimientos_caja`
- `cortes_caja`
- `cajas`
- `huespedes`
- `movimientos_inventario`
- `Sync.php`
- APIs/PWA
- check-in/check-out
- facturacion funcional
- Caja
- codigo funcional

## Por que hotel_id queda INT NULL

`hotel_id` queda como `INT NULL` temporalmente porque el codigo funcional de Reservaciones todavia no escribe ni filtra por hotel.

Aunque el backfill busca dejar 0 valores `NULL` en las tablas objetivo actuales, convertir la columna a `NOT NULL` debe esperar hasta que:

- creacion y edicion de reservaciones escriban `hotel_id`;
- check-in/check-out este scoped por hotel;
- pagos, caja y facturacion esten coordinados;
- PWA/offline y APIs de reservaciones esten actualizadas.

## Por que no se crean FKs estrictas

No se crean foreign keys estrictas hacia `hoteles(id)` ni hacia tablas de Reservaciones porque existen datos historicos huerfanos:

- `reservacion_habitaciones` con reservaciones inexistentes;
- `reservacion_pagos` huerfano;
- `reservacion_notas` huerfanas;
- `movimientos_caja` con reservacion huerfana;
- `movimientos_inventario` con referencias historicas a reservaciones inexistentes.

Crear FKs estrictas ahora podria bloquear la migracion o forzar limpieza historica fuera del alcance aprobado.

## Decision de fallback para reservaciones 48 y 49

La auditoria Reservaciones 1-B-PREP-A.1 aprobo asignar Los Cedros a las reservaciones sin habitacion:

- `reservaciones.id = 48`, con pista historica de habitacion `CHOCOLATE`, que corresponde a `habitaciones.id = 19`, `hotel_id = 1`.
- `reservaciones.id = 49`, con pista historica de habitacion `MARRON`, que corresponde a `habitaciones.id = 5`, `hotel_id = 1`, y movimiento de inventario con `hotel_id = 1`.

La migracion aplica fallback explicito:

```sql
UPDATE reservaciones
SET hotel_id = v_los_cedros_id
WHERE id IN (48, 49)
  AND hotel_id IS NULL;
```

## Decision de fallback para huerfanos

En tablas objetivo de Reservaciones, el fallback Los Cedros queda aprobado cuando no se pueda derivar `hotel_id` desde `reservaciones` o `habitaciones`.

La migracion:

- backfillea `reservacion_habitaciones` desde `habitaciones.hotel_id`, incluso para reservaciones huerfanas;
- backfillea `reservacion_pagos`, `reservacion_notas` y `solicitudes_factura` desde `reservaciones.hotel_id`;
- asigna Los Cedros a huerfanos restantes en tablas objetivo;
- no toca `movimientos_caja`;
- no toca `movimientos_inventario`;
- no crea FKs estrictas.

## SQL creado

Archivo:

```text
migrations/20260526_008_add_hotel_id_reservaciones_base.sql
```

La migracion:

- valida que exista `hoteles.slug = 'los-cedros'`;
- valida que exista `migrations` con columnas `nombre` y `batch`;
- valida que existan todas las tablas objetivo;
- valida que `habitaciones.hotel_id` exista;
- valida que ninguna tabla objetivo tenga ya `hotel_id`;
- valida que no existan reservaciones con habitaciones de hoteles mixtos;
- agrega `hotel_id INT NULL` a las seis tablas objetivo;
- backfillea `reservaciones` desde `reservacion_habitaciones -> habitaciones.hotel_id`;
- aplica fallback explicito de `reservaciones.id IN (48, 49)` a Los Cedros;
- backfillea `reservacion_habitaciones` desde `habitaciones.hotel_id`;
- backfillea tablas hijas desde `reservaciones.hotel_id`;
- aplica fallback Los Cedros a huerfanos restantes de tablas objetivo;
- valida que no queden `hotel_id NULL` en tablas objetivo con registros;
- crea indices simples por `hotel_id`;
- registra la migracion en `migrations(nombre, batch, checksum, estado, ejecutada_en)`;
- no usa columna `migration`;
- no usa columna `lote`;
- no crea FKs estrictas;
- no convierte `hotel_id` a `NOT NULL`.

La ejecucion debe hacerse con cliente MySQL porque el archivo usa `DELIMITER`.

## Indices creados al ejecutar

- `idx_reservaciones_hotel_id`
- `idx_reservacion_habitaciones_hotel_id`
- `idx_reservacion_pagos_hotel_id`
- `idx_reservacion_abonos_hotel_id`
- `idx_reservacion_notas_hotel_id`
- `idx_solicitudes_factura_hotel_id`

## Rollback SQL

MySQL ejecuta commits implicitos con `ALTER TABLE`, por lo que el rollback debe ejecutarse manualmente si la migracion ya fue aplicada.

```sql
DROP INDEX idx_solicitudes_factura_hotel_id
    ON solicitudes_factura;

DROP INDEX idx_reservacion_notas_hotel_id
    ON reservacion_notas;

DROP INDEX idx_reservacion_abonos_hotel_id
    ON reservacion_abonos;

DROP INDEX idx_reservacion_pagos_hotel_id
    ON reservacion_pagos;

DROP INDEX idx_reservacion_habitaciones_hotel_id
    ON reservacion_habitaciones;

DROP INDEX idx_reservaciones_hotel_id
    ON reservaciones;

ALTER TABLE solicitudes_factura
    DROP COLUMN hotel_id;

ALTER TABLE reservacion_notas
    DROP COLUMN hotel_id;

ALTER TABLE reservacion_abonos
    DROP COLUMN hotel_id;

ALTER TABLE reservacion_pagos
    DROP COLUMN hotel_id;

ALTER TABLE reservacion_habitaciones
    DROP COLUMN hotel_id;

ALTER TABLE reservaciones
    DROP COLUMN hotel_id;

DELETE FROM migrations
WHERE nombre = '20260526_008_add_hotel_id_reservaciones_base.sql';
```

El rollback no toca caja, cortes, movimientos de caja, huespedes, inventario, PWA/offline, Sync, check-in/check-out ni datos operativos fuera de `hotel_id`.

## Riesgos

- Reservaciones todavia no escribe `hotel_id` desde codigo funcional.
- Check-in/check-out sigue dependiendo de Reservaciones sin tenant funcional.
- Pagos, caja y facturacion dependen de `reservacion_id` global.
- PWA/offline y Sync pueden crear o modificar reservaciones sin `hotel_id` hasta una fase posterior.
- Existen datos historicos huerfanos.
- Las reservaciones 48 y 49 dependen de fallback documentado, no de relacion activa con habitacion.
- `huespedes` queda fuera porque falta decidir si sera global o por hotel.
- No debe convertirse `hotel_id` a `NOT NULL` hasta actualizar codigo funcional y flujos offline/API.
- No deben crearse FKs estrictas hasta resolver huerfanos historicos.

## Como probar despues de aprobacion de ejecucion

Antes de ejecutar:

- Crear backup fresco de la base local.
- Confirmar rama `feature/saas-multihotel`.
- Confirmar que `hoteles.slug = 'los-cedros'` existe.
- Confirmar que las seis tablas objetivo no tienen `hotel_id`.
- Confirmar que no hay reservaciones con habitaciones de hoteles mixtos.
- Confirmar que la tabla `migrations` usa `nombre`, `batch`, `checksum`, `estado` y `ejecutada_en`.

Comando esperado, solo cuando se apruebe la ejecucion:

```powershell
docker exec -i medisoft_hoteles_db mysql -u root -proot_pass medisoft_hoteles_import < migrations/20260526_008_add_hotel_id_reservaciones_base.sql
```

Despues de ejecutar:

- Verificar que `hotel_id INT NULL` existe en todas las tablas objetivo.
- Confirmar 0 `hotel_id NULL` en tablas objetivo con registros.
- Confirmar que `reservaciones.id IN (48, 49)` quedaron con Los Cedros.
- Confirmar que `reservacion_habitaciones` huerfanas tienen hotel desde `habitaciones.hotel_id`.
- Confirmar fallback en `reservacion_pagos`, `reservacion_notas` y `solicitudes_factura` cuando aplique.
- Confirmar indices `idx_*_hotel_id`.
- Confirmar registro en `migrations` con `nombre = '20260526_008_add_hotel_id_reservaciones_base.sql'`.
- Confirmar que no se toco `movimientos_caja`, `cortes_caja`, `cajas`, `huespedes`, `movimientos_inventario`, PWA/offline ni codigo funcional.
- Ejecutar `verificar_estado.php` y `preflight_hotel_id.php`.
- Si las herramientas fallan solo porque ahora Reservaciones tiene `hotel_id`, reportarlo como ajuste esperado para una subfase posterior.
- Probar `/login`, `/`, `/dashboard`, `/habitaciones` y vistas principales de Reservaciones en modo no destructivo.

## Siguiente fase recomendada

La siguiente fase recomendada es `Reservaciones 1-B-EXEC`: ejecutar la migracion 008 en local con cliente MySQL, despues de backup fresco y aprobacion explicita.

Despues de ejecutar, deberia venir una fase de actualizacion de herramientas SaaS para reconocer el nuevo estado de Reservaciones antes de tocar codigo funcional.
