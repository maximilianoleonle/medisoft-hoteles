# Fase 2A.1 - hotel_id en modulo Habitaciones

## Objetivo

Crear la primera migracion real de `hotel_id` en tablas operativas, limitada al modulo Habitaciones y sin integrar todavia `TenantContext` al codigo funcional.

Esta fase prepara el schema y hace backfill local hacia el hotel inicial Los Cedros, pero deja `hotel_id` como `NULL` temporal para no romper inserts existentes mientras el sistema sigue funcionando en modo mono-hotel.

## SQL creado

Archivo:

- `migrations/20260526_003_add_hotel_id_habitaciones.sql`

La migracion:

1. Valida que exista `hoteles.slug = 'los-cedros'`.
2. Detiene la ejecucion con `SIGNAL SQLSTATE '45000'` si Los Cedros no existe.
3. Valida que ninguna tabla permitida tenga ya columna `hotel_id`.
4. Detiene la ejecucion con `SIGNAL SQLSTATE '45000'` si detecta `hotel_id` previo.
5. Agrega `hotel_id INT NULL` a las tablas permitidas.
6. Hace backfill de registros existentes con el ID de Los Cedros.
7. Valida que no queden registros con `hotel_id IS NULL` despues del backfill.
8. Crea indices simples sobre `hotel_id`.
9. Crea foreign keys hacia `hoteles(id)`.
10. Registra la migracion en `migrations`.

## Requisito de backup

El backup fresco es obligatorio antes de ejecutar esta migracion.

Motivo: MySQL hace commits implicitos con `ALTER TABLE`, por lo que un error a media ejecucion puede dejar parte del schema aplicado. Si eso ocurre, la recuperacion depende del rollback manual documentado y, si fuera necesario, del backup previo.

## Forma de ejecucion

La migracion usa `DELIMITER` para crear procedimientos temporales de validacion. Por eso debe ejecutarse con el cliente MySQL, no con un runner PDO simple que envie el archivo completo como una unica sentencia.

Comando esperado para ejecucion local, cuando sea aprobada:

```bash
docker compose exec -T db mysql -u"$DB_USER" -p"$DB_PASS" medisoft_hoteles_import < migrations/20260526_003_add_hotel_id_habitaciones.sql
```

Si se usa el usuario root local de Docker, ajustar usuario/password segun `.env`.

## Tablas afectadas

Solo estas tablas:

- `tipos_habitacion`
- `habitaciones`
- `habitacion_imagenes`
- `mantenimientos_habitaciones`

## Que NO se toca

Esta fase no toca:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- caja
- cortes
- movimientos de caja
- check-in/check-out
- login
- sesiones
- PWA/offline
- dashboard
- consultas funcionales actuales
- indices unicos globales existentes

## Por que hotel_id queda NULL temporalmente

`hotel_id` queda como `NULL` temporal porque el codigo funcional actual todavia no escribe `hotel_id` al crear registros nuevos.

Si se definiera `NOT NULL` en esta fase, los inserts actuales de habitaciones, imagenes o mantenimientos podrian fallar antes de integrar el tenant al codigo.

El endurecimiento recomendado es:

1. Fase 2A.1: agregar `hotel_id NULL`, backfill, indices y foreign keys.
2. Fase 2A.2: integrar codigo para escribir y filtrar por `hotel_id`.
3. Fase 2A.3: revisar registros nuevos, convertir indices unicos a compuestos y evaluar `NOT NULL`.

## Por que no se cambian unicos globales todavia

Actualmente existen unicos globales relevantes:

- `habitaciones.numero`
- `tipos_habitacion.codigo`

No se cambian todavia porque hacerlo podria impactar consultas, formularios o validaciones existentes.

La conversion recomendada para una subfase posterior es:

- `habitaciones`: cambiar `numero(numero)` por un unico compuesto equivalente a `(hotel_id, numero)`.
- `tipos_habitacion`: cambiar `uk_codigo(codigo)` por un unico compuesto equivalente a `(hotel_id, codigo)`.

Esa conversion debe hacerse despues de que el codigo ya filtre y escriba `hotel_id`.

## Validaciones previas

La migracion crea temporalmente el procedimiento `validar_fase_2a1_precondiciones`.

Ese procedimiento primero revisa que exista Los Cedros:

```sql
SELECT 1
FROM hoteles
WHERE slug = 'los-cedros'
LIMIT 1;
```

Si no existe el hotel, ejecuta:

```sql
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Fase 2A.1 detenida: no existe hoteles.slug = los-cedros';
```

La validacion ocurre antes de cualquier `ALTER TABLE`. Si falla, la migracion no debe agregar columnas ni modificar las tablas de Habitaciones.

El mismo procedimiento valida que ninguna de las tablas permitidas tenga ya columna `hotel_id`:

```sql
SELECT 1
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
      'tipos_habitacion',
      'habitaciones',
      'habitacion_imagenes',
      'mantenimientos_habitaciones'
  )
  AND COLUMN_NAME = 'hotel_id'
LIMIT 1;
```

Si alguna tabla ya tiene `hotel_id`, la migracion se detiene con:

```sql
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Fase 2A.1 detenida: alguna tabla de Habitaciones ya tiene hotel_id';
```

## Validacion posterior del backfill

Despues de agregar columnas y ejecutar el backfill, la migracion crea temporalmente el procedimiento `validar_fase_2a1_backfill`.

Ese procedimiento valida que no queden registros con `hotel_id IS NULL` en:

- `tipos_habitacion`
- `habitaciones`
- `habitacion_imagenes`
- `mantenimientos_habitaciones`

Si queda cualquier registro sin hotel, la migracion se detiene con `SIGNAL SQLSTATE '45000'` antes de crear indices y foreign keys.

## Riesgos

Riesgos principales:

- MySQL hace commits implicitos con `ALTER TABLE`; no se puede confiar en una transaccion para revertir todo automaticamente.
- Si se crean registros nuevos antes de integrar codigo, pueden quedar con `hotel_id = NULL`.
- Los unicos globales siguen siendo globales, por lo que aun no permiten duplicar numeros/codigos entre hoteles.
- Las foreign keys hacia `hoteles(id)` impiden borrar Los Cedros mientras existan registros asociados.
- Si la migracion se ejecuta parcialmente y falla en algun `ALTER`, se debe aplicar rollback manual revisando primero que pasos alcanzaron a completarse.
- Si la migracion falla durante una validacion con `SIGNAL`, puede quedar creado temporalmente un procedimiento de validacion; el rollback incluye `DROP PROCEDURE IF EXISTS`.

## Plan de pruebas

Antes de ejecutar:

```bash
docker compose exec app php tools/saas/verificar_estado.php
docker compose exec app php tools/saas/preflight_hotel_id.php
```

Despues de ejecutar en local:

1. Verificar que las cuatro tablas tengan columna `hotel_id`.
2. Verificar que todos los registros existentes tengan `hotel_id` de Los Cedros.
3. Verificar que existan los indices `idx_*_hotel_id`.
4. Verificar que existan las foreign keys `fk_*_hotel`.
5. Verificar que `/login` responda.
6. Verificar dashboard.
7. Verificar vista de habitaciones.
8. Confirmar que caja y reservaciones no cambiaron.
9. Ejecutar de nuevo:

```bash
docker compose exec app php tools/saas/verificar_estado.php
docker compose exec app php tools/saas/preflight_hotel_id.php
```

## Rollback SQL

Este rollback quita foreign keys, indices y columnas `hotel_id` creadas por la migracion. No toca datos operativos fuera de `hotel_id`.

```sql
DROP PROCEDURE IF EXISTS validar_fase_2a1_precondiciones;
DROP PROCEDURE IF EXISTS validar_fase_2a1_backfill;

ALTER TABLE mantenimientos_habitaciones
    DROP FOREIGN KEY fk_mantenimientos_habitaciones_hotel;

ALTER TABLE habitacion_imagenes
    DROP FOREIGN KEY fk_habitacion_imagenes_hotel;

ALTER TABLE habitaciones
    DROP FOREIGN KEY fk_habitaciones_hotel;

ALTER TABLE tipos_habitacion
    DROP FOREIGN KEY fk_tipos_habitacion_hotel;

ALTER TABLE mantenimientos_habitaciones
    DROP INDEX idx_mantenimientos_habitaciones_hotel_id;

ALTER TABLE habitacion_imagenes
    DROP INDEX idx_habitacion_imagenes_hotel_id;

ALTER TABLE habitaciones
    DROP INDEX idx_habitaciones_hotel_id;

ALTER TABLE tipos_habitacion
    DROP INDEX idx_tipos_habitacion_hotel_id;

ALTER TABLE mantenimientos_habitaciones
    DROP COLUMN hotel_id;

ALTER TABLE habitacion_imagenes
    DROP COLUMN hotel_id;

ALTER TABLE habitaciones
    DROP COLUMN hotel_id;

ALTER TABLE tipos_habitacion
    DROP COLUMN hotel_id;

DELETE FROM migrations
WHERE nombre = '20260526_003_add_hotel_id_habitaciones.sql';
```

Si la migracion falla parcialmente, revisar primero que constraints, indices y columnas existen antes de ejecutar cada bloque del rollback.

## Siguiente fase recomendada

La siguiente fase recomendada es Fase 2A.2: integracion de codigo en Habitaciones.

Esa fase debe hacer que el modulo Habitaciones:

- Escriba `hotel_id` automaticamente.
- Filtre listados por hotel activo.
- Mantenga compatibilidad mono-hotel mientras `feature.multi_hotel` siga en `false`.
- No toque reservaciones, caja, check-in/check-out, login/sesiones ni PWA/offline sin aprobacion explicita.
