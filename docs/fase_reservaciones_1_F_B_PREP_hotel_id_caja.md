# Reservaciones 1-F-B-PREP - Preparacion de hotel_id para Caja base

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Preparar una migracion conservadora para agregar `hotel_id` a Caja, cortes y movimientos de caja, sin ejecutarla todavia y sin tocar logica funcional ni reportes.

Esta fase solo crea archivos. No ejecuta SQL, no modifica base de datos, no hace `ALTER TABLE` en ejecucion real y no cambia codigo funcional.

## Tablas afectadas

La migracion preparada afecta unicamente:

- `cajas`
- `cortes_caja`
- `movimientos_caja`
- `migrations`, solo para registrar la migracion al ejecutarla

## Tablas excluidas

Quedan fuera de esta fase:

- `reservacion_pagos`, porque ya tiene `hotel_id`
- `reservaciones`
- `reservacion_habitaciones`
- `habitaciones`
- `movimientos_inventario`
- `huespedes`
- PWA/offline
- Sync
- APIs
- Dashboard/reportes
- codigo funcional

## Por que hotel_id queda INT NULL

`hotel_id` queda como `INT NULL` temporalmente porque el codigo funcional de Caja, cortes, reportes y dashboard todavia no escribe ni filtra por hotel.

Aunque el backfill busca dejar 0 valores `NULL` en las tablas objetivo actuales, convertir la columna a `NOT NULL` debe esperar hasta que:

- pagos/caja escriban `hotel_id` desde codigo funcional;
- cortes y reportes esten scoped por hotel;
- PWA/offline, Sync y APIs esten auditados;
- se definan reglas finales para caja por hotel.

## Por que no se crean FKs estrictas

No se crean foreign keys estrictas hacia `hoteles(id)` porque Caja tiene datos financieros historicos y relaciones logicas sin FKs declaradas.

Los riesgos que justifican esta decision son:

- `movimientos_caja` tiene movimientos sin `reservacion_id`;
- existe un movimiento de caja huerfano con `reservacion_id = 53`;
- existen cortes historicos cerrados;
- `cajas` actualmente es un registro global;
- los reportes financieros todavia no son tenant-aware.

## Estrategia de backfill

### cajas

La caja global actual se asigna a Los Cedros:

```sql
UPDATE cajas
SET hotel_id = v_los_cedros_id
WHERE hotel_id IS NULL;
```

### cortes_caja

Los cortes se derivan desde `cajas.hotel_id` por `caja_id`:

```sql
UPDATE cortes_caja cc
INNER JOIN cajas c ON c.id = cc.caja_id
SET cc.hotel_id = c.hotel_id
WHERE cc.hotel_id IS NULL
  AND c.hotel_id IS NOT NULL;
```

Si quedara algun corte historico sin caja derivable, se aplica fallback Los Cedros por compatibilidad mono-hotel.

### movimientos_caja

El backfill usa tres pasos:

1. Si `movimientos_caja.reservacion_id` apunta a una reservacion valida, deriva desde `reservaciones.hotel_id`.
2. Si no tiene reservacion valida, deriva desde `cortes_caja.hotel_id` por `corte_id`.
3. Si aun queda `NULL`, aplica fallback Los Cedros por compatibilidad mono-hotel historica.

El movimiento huerfano conocido con `reservacion_id = 53`, monto 1300 y corte cerrado 15 queda cubierto por derivacion desde `cortes_caja.hotel_id` o por fallback documentado.

## SQL creado

Archivo:

```text
migrations/20260526_009_add_hotel_id_caja_base.sql
```

La migracion:

- valida que exista `hoteles.slug = 'los-cedros'`;
- valida que exista `migrations` con columnas `nombre`, `batch`, `checksum`, `estado` y `ejecutada_en`;
- valida que existan `cajas`, `cortes_caja` y `movimientos_caja`;
- valida que `reservaciones.hotel_id` exista;
- valida que ninguna tabla objetivo tenga ya `hotel_id`;
- agrega `hotel_id INT NULL` a las tres tablas objetivo;
- backfillea `cajas` a Los Cedros;
- backfillea `cortes_caja` desde `cajas.hotel_id`;
- backfillea `movimientos_caja` desde `reservaciones.hotel_id` cuando hay reservacion valida;
- backfillea `movimientos_caja` desde `cortes_caja.hotel_id` cuando no hay reservacion valida o es huerfana;
- aplica fallback Los Cedros a cualquier remanente;
- valida 0 `hotel_id NULL` en las tres tablas objetivo;
- valida consistencia entre caja/corte, movimiento/reservacion valida y movimiento/corte;
- crea indices simples por `hotel_id`;
- registra la migracion en `migrations(nombre, batch, checksum, estado, ejecutada_en)`;
- no usa columna `migration`;
- no usa columna `lote`;
- no crea FKs estrictas;
- no convierte `hotel_id` a `NOT NULL`.

La ejecucion debe hacerse con cliente MySQL porque el archivo usa `DELIMITER`.

## Indices creados al ejecutar

- `idx_cajas_hotel_id`
- `idx_cortes_caja_hotel_id`
- `idx_movimientos_caja_hotel_id`

## Rollback SQL

MySQL ejecuta commits implicitos con `ALTER TABLE`, por lo que el rollback debe ejecutarse manualmente si la migracion ya fue aplicada.

```sql
DROP INDEX idx_movimientos_caja_hotel_id
    ON movimientos_caja;

DROP INDEX idx_cortes_caja_hotel_id
    ON cortes_caja;

DROP INDEX idx_cajas_hotel_id
    ON cajas;

ALTER TABLE movimientos_caja
    DROP COLUMN hotel_id;

ALTER TABLE cortes_caja
    DROP COLUMN hotel_id;

ALTER TABLE cajas
    DROP COLUMN hotel_id;

DELETE FROM migrations
WHERE nombre = '20260526_009_add_hotel_id_caja_base.sql';
```

El rollback no toca datos fuera de `hotel_id`, no modifica reservaciones, pagos, inventario, PWA/offline, Sync, APIs, dashboard/reportes ni codigo funcional.

## Riesgos financieros

- `movimientos_caja` contiene 1381 registros financieros historicos.
- `cortes_caja` contiene 214 cortes historicos cerrados.
- `cajas` actualmente tiene 1 registro global.
- `movimientos_caja` contiene 158 movimientos sin `reservacion_id`.
- Existe 1 movimiento de caja huerfano con `reservacion_id = 53`.
- Los reportes financieros todavia son globales.
- Caja funcional todavia no escribe ni filtra por `hotel_id`.
- PWA/Sync/API pueden tener caminos paralelos fuera de esta fase.

## Como probar despues de aprobacion de ejecucion

Antes de ejecutar:

- Crear backup fresco de la base local.
- Confirmar rama `feature/saas-multihotel`.
- Confirmar que `hoteles.slug = 'los-cedros'` existe.
- Confirmar que `cajas`, `cortes_caja` y `movimientos_caja` no tienen `hotel_id`.
- Confirmar que `reservaciones.hotel_id` existe.
- Confirmar que la tabla `migrations` usa `nombre`, `batch`, `checksum`, `estado` y `ejecutada_en`.

Comando esperado, solo cuando se apruebe la ejecucion:

```powershell
docker exec -i medisoft_hoteles_db mysql -u root -proot_pass medisoft_hoteles_import < migrations/20260526_009_add_hotel_id_caja_base.sql
```

Despues de ejecutar:

- Verificar que `hotel_id INT NULL` existe en `cajas`, `cortes_caja` y `movimientos_caja`.
- Confirmar 0 `hotel_id NULL` en las tres tablas objetivo.
- Confirmar que la caja actual quedo en Los Cedros.
- Confirmar que los 214 cortes quedaron en Los Cedros.
- Confirmar que los 1381 movimientos de caja quedaron en Los Cedros.
- Confirmar que el movimiento huerfano con `reservacion_id = 53` quedo con Los Cedros.
- Confirmar indices `idx_cajas_hotel_id`, `idx_cortes_caja_hotel_id` y `idx_movimientos_caja_hotel_id`.
- Confirmar registro en `migrations` con `nombre = '20260526_009_add_hotel_id_caja_base.sql'`.
- Confirmar que no se toco `reservacion_pagos`, `reservaciones`, `reservacion_habitaciones`, `habitaciones`, `movimientos_inventario`, PWA/offline, Sync, APIs, Dashboard/reportes ni codigo funcional.
- Ejecutar `verificar_estado.php` y `preflight_hotel_id.php`.
- Si las herramientas fallan solo porque ahora Caja tiene `hotel_id`, reportarlo como ajuste esperado para una subfase posterior.
- Probar `/login`, `/`, `/dashboard`, `/habitaciones`, `/reservaciones` y vistas de Caja en modo no destructivo.

## Siguiente fase recomendada

La siguiente fase recomendada es `Reservaciones 1-F-B-EXEC`: ejecutar la migracion 009 en local con cliente MySQL, despues de backup fresco y aprobacion explicita.

Despues de ejecutar, debe venir una fase de actualizacion de herramientas SaaS para reconocer el nuevo estado antes de tocar codigo funcional de Caja, pagos, cortes, reportes o dashboard.
