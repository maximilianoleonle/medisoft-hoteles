# Fase 2A.2-FIX-B - Estado programado en mantenimientos

## Objetivo

Preparar una migracion minima para permitir el estado `programado` en `mantenimientos_habitaciones.estado`, sin tocar datos existentes ni cambiar la logica funcional.

## Bug Detectado

`Mantenimiento::programar()` crea mantenimientos futuros usando:

```php
$data['estado'] = 'programado';
$data['programado'] = 1;
```

Pero el esquema actual define `mantenimientos_habitaciones.estado` como:

```sql
ENUM('en_proceso','completado','cancelado')
```

Por eso, programar mantenimiento falla en MySQL con modo estricto. En modo no estricto podria guardar un valor invalido o vacio, lo que seria peor para las consultas posteriores.

## Por Que Agregar `programado` Al ENUM

El codigo ya distingue entre:

- `programado`: mantenimiento futuro, aun no activo.
- `en_proceso`: mantenimiento activo.
- `completado`: mantenimiento finalizado.
- `cancelado`: mantenimiento cancelado.

Cambiar la logica para usar `en_proceso` en mantenimientos futuros distorsionaria la semantica del modulo y podria marcar habitaciones como no disponibles antes de tiempo.

## Por Que `programado` Va Al Final

No se reordenan los valores existentes del ENUM para reducir riesgo. La migracion conserva el orden actual:

```sql
ENUM('en_proceso','completado','cancelado')
```

y agrega el nuevo valor al final:

```sql
ENUM('en_proceso','completado','cancelado','programado')
```

## SQL De Migracion

Archivo:

```text
migrations/20260526_004_fix_estado_programado_mantenimientos.sql
```

Contenido:

```sql
-- Fase 2A.2-FIX-B - Permitir estado programado en mantenimientos_habitaciones
-- Migracion minima de schema. No toca datos, hotel_id, indices unicos ni logica funcional.

DELIMITER $$

DROP PROCEDURE IF EXISTS fix_estado_programado_mantenimientos$$

CREATE PROCEDURE fix_estado_programado_mantenimientos()
BEGIN
    DECLARE v_table_count INT DEFAULT 0;
    DECLARE v_column_type TEXT DEFAULT NULL;

    SELECT COUNT(*)
    INTO v_table_count
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mantenimientos_habitaciones';

    IF v_table_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.2-FIX-B detenida: no existe la tabla mantenimientos_habitaciones';
    END IF;

    SELECT COLUMN_TYPE
    INTO v_column_type
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mantenimientos_habitaciones'
      AND COLUMN_NAME = 'estado';

    IF v_column_type IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.2-FIX-B detenida: no existe la columna mantenimientos_habitaciones.estado';
    END IF;

    IF v_column_type NOT LIKE '%''programado''%' THEN
        IF v_column_type <> 'enum(''en_proceso'',''completado'',''cancelado'')' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Fase 2A.2-FIX-B detenida: enum estado tiene valores inesperados';
        END IF;

        ALTER TABLE mantenimientos_habitaciones
            MODIFY COLUMN estado ENUM('en_proceso','completado','cancelado','programado')
            COLLATE utf8mb4_unicode_ci
            DEFAULT 'en_proceso';
    END IF;
END$$

CALL fix_estado_programado_mantenimientos()$$

DROP PROCEDURE IF EXISTS fix_estado_programado_mantenimientos$$

DELIMITER ;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_004_fix_estado_programado_mantenimientos.sql', 2, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
```

## Rollback Seguro

El rollback debe detenerse si ya existen registros con `estado = 'programado'`, porque eliminar el valor del ENUM en ese caso podria truncar o invalidar datos.

```sql
DELIMITER $$

DROP PROCEDURE IF EXISTS rollback_estado_programado_mantenimientos$$

CREATE PROCEDURE rollback_estado_programado_mantenimientos()
BEGIN
    DECLARE v_table_count INT DEFAULT 0;
    DECLARE v_column_type TEXT DEFAULT NULL;

    SELECT COUNT(*)
    INTO v_table_count
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mantenimientos_habitaciones';

    IF v_table_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Rollback detenido: no existe la tabla mantenimientos_habitaciones';
    END IF;

    SELECT COLUMN_TYPE
    INTO v_column_type
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mantenimientos_habitaciones'
      AND COLUMN_NAME = 'estado';

    IF v_column_type IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Rollback detenido: no existe la columna mantenimientos_habitaciones.estado';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM mantenimientos_habitaciones
        WHERE estado = 'programado'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Rollback detenido: existen mantenimientos con estado programado';
    END IF;

    IF v_column_type LIKE '%''programado''%' THEN
        ALTER TABLE mantenimientos_habitaciones
            MODIFY COLUMN estado ENUM('en_proceso','completado','cancelado')
            COLLATE utf8mb4_unicode_ci
            DEFAULT 'en_proceso';
    END IF;

    DELETE FROM migrations
    WHERE nombre = '20260526_004_fix_estado_programado_mantenimientos.sql';
END$$

CALL rollback_estado_programado_mantenimientos()$$

DROP PROCEDURE IF EXISTS rollback_estado_programado_mantenimientos$$

DELIMITER ;
```

## Riesgos

- `ALTER TABLE` hace commit implicito en MySQL; no se debe depender de una transaccion para revertirlo.
- Si se ejecuta rollback despues de crear mantenimientos `programado`, debe decidirse manualmente que hacer con esos registros.
- La migracion valida el ENUM esperado antes de modificarlo; si el schema local difiere, se detiene para evitar sobrescribir valores no auditados.
- Debe ejecutarse con cliente MySQL, porque usa `DELIMITER`.

## Pruebas Necesarias Despues De Ejecutar

1. Verificar `COLUMN_TYPE` antes y despues:

```sql
SELECT COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'mantenimientos_habitaciones'
  AND COLUMN_NAME = 'estado';
```

2. Programar mantenimiento desde detalle de habitacion.
3. Confirmar que el registro queda con `estado = 'programado'`.
4. Confirmar que `programado = 1`.
5. Confirmar que `hotel_id` corresponde a Los Cedros.
6. Cancelar mantenimiento programado.
7. Iniciar mantenimiento programado, si aplica.
8. Ejecutar:

```powershell
docker compose exec app php tools/saas/verificar_estado.php
docker compose exec app php tools/saas/preflight_hotel_id.php
```

9. Probar `/habitaciones`.
10. Probar detalle de habitacion.
11. Confirmar que no se tocaron caja, reservaciones, login/sesiones, dashboard ni PWA/offline.

## Que NO Se Toca

- No se cambian datos existentes.
- No se cambia `hotel_id`.
- No se modifica la columna booleana `programado`.
- No se cambian fechas de programacion.
- No se tocan indices unicos globales.
- No se modifica codigo funcional.
- No se tocan caja, reservaciones, check-in/check-out, login/sesiones, dashboard ni PWA/offline.

## Siguiente Paso Recomendado

Revisar esta migracion y, si se aprueba, ejecutar Fase 2A.2-FIX-B en local con backup fresco previo. Despues de validar mantenimiento programado, retomar Fase 2A.2-C-B.
