# Fase Inventario 1-C-PREP - Preparacion de hotel_id en catalogos/configuracion

## Objetivo

Preparar una migracion versionada para agregar `hotel_id` a las tablas base de Inventario, con backfill hacia el hotel actual Los Cedros, sin ejecutarla todavia y sin tocar codigo funcional.

La fase queda limitada a catalogos/configuracion para reducir el riesgo antes de entrar a movimientos, reservaciones o check-in/check-out.

Esta migracion debe ejecutarse solamente con backup fresco y autorizacion explicita. El archivo usa `DELIMITER`, por lo que debe ejecutarse con cliente MySQL, no con PDO ni con un runner que no soporte procedimientos almacenados.

## Tablas afectadas

La migracion preparada afecta unicamente:

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`

No toca:

- `movimientos_inventario`
- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- `inventario_habitacion_config`
- `reservaciones`
- `habitaciones`
- caja
- PWA/offline

## Por que solo catalogos/configuracion

`inventario_categorias`, `inventario_productos` e `inventario_config_habitacion` son la base para decidir que productos existen y que configuracion aplica por tipo de habitacion.

`inventario_config_habitacion` es la prioridad porque usa `tipo_habitacion` textual y `producto_id`. Si en el futuro dos hoteles comparten el mismo codigo de tipo, esta tabla podria mezclar configuracion entre hoteles si no tiene `hotel_id`.

`inventario_productos` parece catalogo operativo por hotel porque contiene codigos, nombres, categorias, estado y datos que participan en configuracion y movimientos.

## Por que se validan las tablas objetivo

La migracion valida explicitamente que existan `inventario_categorias`, `inventario_productos` e `inventario_config_habitacion` antes de cualquier `ALTER TABLE`.

Esto evita una ejecucion parcial donde una o dos tablas reciban `hotel_id` y la migracion falle despues por una tabla faltante. Aunque MySQL hace commits implicitos con `ALTER TABLE`, esta validacion previa reduce el riesgo de dejar el schema a medias por precondiciones obvias.

## Por que se dejan fuera movimientos, reservaciones y check-in/check-out

`movimientos_inventario` es una tabla activa y tiene relaciones con `habitacion_id`, `reservacion_id`, `producto_id` y `usuario_id`. Migrarla antes de disenar la estrategia de Reservaciones podria afectar check-in/check-out.

Check-in/check-out y Reservaciones deben esperar una fase propia porque son flujos operativos sensibles y porque Reservaciones todavia no tiene `hotel_id`.

`movimientos_inventario`, `inventario_movimientos` y `alertas_inventario` quedan fuera porque pertenecen a actividad operativa. En especial, `movimientos_inventario` ya contiene movimientos ligados a habitaciones y reservaciones, por lo que requiere una estrategia coordinada con Reservaciones y con los flujos de check-in/check-out.

Las tablas legacy/paralelas (`productos`, `categorias_producto`, `inventario_movimientos`, `inventario_habitacion_config`) tambien quedan fuera hasta una auditoria especifica.

## SQL creado

Archivo creado:

```text
migrations/20260526_006_add_hotel_id_inventario_base.sql
```

La migracion:

- Valida que exista `hoteles.slug = 'los-cedros'`.
- Valida que existan las tablas objetivo.
- Valida que ninguna tabla objetivo tenga ya `hotel_id`.
- Agrega `hotel_id INT NULL` a las tres tablas.
- Hace backfill con el ID de Los Cedros.
- Valida que no queden registros existentes con `hotel_id IS NULL`.
- Crea indices simples por `hotel_id`.
- Crea foreign keys hacia `hoteles(id)`.
- Registra la migracion en `migrations` con `batch = 3`.

No convierte `hotel_id` a `NOT NULL`, no cambia indices unicos existentes y no modifica datos operativos fuera del backfill de `hotel_id`.

## Indices unicos que NO se cambian

Esta fase no cambia:

- `inventario_config_habitacion.uk_tipo_producto (tipo_habitacion, producto_id)`
- `inventario_productos.codigo (codigo)`

Los unicos globales siguen pendientes porque el codigo funcional de Inventario todavia usa claves textuales y productos sin scope completo por hotel. Convertirlos ahora a unicos compuestos podria permitir datos que los servicios actuales todavia no saben distinguir correctamente.

El duplicado de nombre `Cloro` en `inventario_productos` no bloquea esta fase porque no se crea ningun indice unico por nombre.

`inventario_config_habitacion.tipo_habitacion` sigue siendo textual temporalmente. La migracion a `tipo_habitacion_id`, si se decide hacer, debe esperar una fase posterior.

## Ejecucion recomendada

Antes de ejecutar esta migracion se requiere:

- Backup fresco de la base local.
- Confirmacion de rama `feature/saas-multihotel`.
- Confirmacion de working tree limpio o con cambios documentales no relacionados claramente separados.
- Ejecucion con cliente MySQL por el uso de `DELIMITER`.

Comando esperado, cuando se apruebe la ejecucion:

```powershell
docker exec -i medisoft_hoteles_db mysql -u root -proot_pass medisoft_hoteles_import < migrations/20260526_006_add_hotel_id_inventario_base.sql
```

## Rollback SQL

MySQL ejecuta commits implicitos con `ALTER TABLE`, por lo que el rollback debe ejecutarse manualmente si la migracion ya fue aplicada.

```sql
ALTER TABLE inventario_config_habitacion
    DROP FOREIGN KEY fk_inventario_config_habitacion_hotel;

ALTER TABLE inventario_productos
    DROP FOREIGN KEY fk_inventario_productos_hotel;

ALTER TABLE inventario_categorias
    DROP FOREIGN KEY fk_inventario_categorias_hotel;

DROP INDEX idx_inventario_config_habitacion_hotel_id
    ON inventario_config_habitacion;

DROP INDEX idx_inventario_productos_hotel_id
    ON inventario_productos;

DROP INDEX idx_inventario_categorias_hotel_id
    ON inventario_categorias;

ALTER TABLE inventario_config_habitacion
    DROP COLUMN hotel_id;

ALTER TABLE inventario_productos
    DROP COLUMN hotel_id;

ALTER TABLE inventario_categorias
    DROP COLUMN hotel_id;

DELETE FROM migrations
WHERE nombre = '20260526_006_add_hotel_id_inventario_base.sql';
```

El rollback no toca movimientos, reservaciones, habitaciones, caja, PWA/offline ni datos operativos fuera de la columna `hotel_id` agregada por esta migracion.

## Riesgos

- Los servicios de Inventario todavia no escriben ni filtran por `hotel_id`; por eso `hotel_id` queda `NULL` temporalmente para nuevos inserts hasta que el codigo se integre.
- Si se ejecuta la migracion y luego el sistema crea categorias, productos o configuraciones antes de adaptar codigo, podrian aparecer nuevos `hotel_id NULL`.
- `inventario_config_habitacion.uk_tipo_producto` sigue siendo global. Esto evita duplicados globales por ahora, pero todavia no permite configuraciones duplicadas por hotel.
- `inventario_productos.codigo` sigue siendo unico global. Esto evita codigos repetidos entre hoteles hasta una fase posterior.
- `movimientos_inventario` queda sin tenant en esta fase, por lo que check-in/check-out no debe modificarse todavia.
- `productos` e `inventario_productos` siguen coexistiendo como modulos paralelos o legacy.

## Pruebas necesarias despues de ejecutar

Antes de ejecutar:

- Crear backup fresco de la base local.
- Confirmar que `hoteles.slug = 'los-cedros'` existe.
- Confirmar que las tres tablas objetivo no tienen `hotel_id`.

Despues de ejecutar:

- Verificar que `hotel_id` existe en `inventario_categorias`, `inventario_productos` e `inventario_config_habitacion`.
- Confirmar que todos los registros existentes tienen `hotel_id` de Los Cedros.
- Confirmar que no hay `hotel_id IS NULL` en registros existentes de esas tres tablas.
- Confirmar indices:
  - `idx_inventario_categorias_hotel_id`
  - `idx_inventario_productos_hotel_id`
  - `idx_inventario_config_habitacion_hotel_id`
- Confirmar foreign keys:
  - `fk_inventario_categorias_hotel`
  - `fk_inventario_productos_hotel`
  - `fk_inventario_config_habitacion_hotel`
- Confirmar registro en `migrations`:
  - `20260526_006_add_hotel_id_inventario_base.sql`
- Ejecutar herramientas SaaS y documentar si requieren actualizacion para aceptar el nuevo estado.
- Probar `/login`, `/`, `/dashboard` y vistas principales sin cambiar flujos operativos.
- Confirmar que no se tocaron Reservaciones, Caja, PWA/offline ni check-in/check-out.

## Siguiente fase recomendada

La siguiente fase recomendada es Inventario 1-C-EJECUCION: ejecutar esta migracion en local con cliente MySQL, despues de backup fresco y autorizacion explicita.

Despues de ejecutar y verificar, la fase posterior debe actualizar herramientas de verificacion para reconocer `hotel_id` en las tres tablas base de Inventario, y luego preparar la integracion de codigo de Inventario para escribir y filtrar por hotel.
