# Fase Inventario 1-E-B-PREP - Preparacion de hotel_id en movimientos_inventario

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Preparar una migracion versionada para agregar `hotel_id` a `movimientos_inventario`, derivandolo desde `inventario_productos.hotel_id` y validandolo contra `habitaciones.hotel_id` cuando exista `habitacion_id`.

Esta fase solo prepara archivos. No ejecuta SQL, no modifica base de datos y no cambia codigo funcional.

## Por que movimientos_inventario es de alto riesgo

`movimientos_inventario` no es un catalogo. Es una tabla operativa con trazabilidad de stock y relaciones directas con:

- productos de inventario
- habitaciones
- reservaciones
- usuarios
- check-in
- entradas/salidas manuales
- ajustes de stock

En la auditoria live se confirmo:

- total de movimientos: 936
- movimientos con `reservacion_id`: 865
- movimientos con `habitacion_id`: 865
- movimientos con ambos campos: 865

Por eso la migracion debe ser conservadora y no debe tocar Reservaciones, check-in/check-out ni el codigo que genera movimientos.

## Por que se deriva hotel_id desde inventario_productos

La fuente mas estable en esta fase es `inventario_productos.hotel_id` porque:

- `inventario_productos` ya fue migrada y backfilled a Los Cedros.
- Todo movimiento tiene `producto_id`.
- La FK actual `movimientos_inventario.producto_id -> inventario_productos(id)` ya existe.
- Los 936 movimientos actuales apuntan a productos con `hotel_id` de Los Cedros.

La migracion usa:

```sql
movimientos_inventario.producto_id -> inventario_productos.id -> inventario_productos.hotel_id
```

## Por que solo se valida habitaciones cuando existe habitacion_id

No todos los movimientos tienen `habitacion_id`. Las entradas manuales, por ejemplo, pueden representar abastecimiento general y no una salida asociada a una habitacion.

Cuando `habitacion_id` existe, la migracion valida que:

```text
habitaciones.hotel_id = inventario_productos.hotel_id
```

Si hay diferencias, se detiene con `SIGNAL SQLSTATE '45000'`.

## Por que no se usa Reservaciones todavia

Reservaciones todavia no tiene `hotel_id`. Usar `reservaciones` como fuente en esta fase obligaria a definir tenant para:

- `reservaciones`
- `reservacion_habitaciones`
- pagos
- check-in/check-out
- cancelaciones

Eso pertenece a una fase posterior. En esta preparacion, `reservacion_id` se conserva como relacion historica, pero no se usa para derivar `hotel_id`.

## Tablas que se tocan

La migracion preparada modifica unicamente:

- `movimientos_inventario`
- `migrations` para registrar la ejecucion

## Tablas usadas solo para lectura/validacion

- `inventario_productos`
- `habitaciones`
- `hoteles`
- `migrations`

## Tablas que NO se tocan

- `reservaciones`
- `reservacion_habitaciones`
- `inventario_productos`
- `inventario_categorias`
- `inventario_config_habitacion`
- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- `cajas`
- `movimientos_caja`
- `sync_queue`
- `push_subscriptions`
- PWA/offline
- Codigo funcional

## SQL creado

Archivo:

```text
migrations/20260526_007_add_hotel_id_movimientos_inventario.sql
```

La migracion:

- Valida que exista `hoteles.slug = 'los-cedros'`.
- Valida que exista `movimientos_inventario`.
- Valida que existan `inventario_productos.hotel_id` y `habitaciones.hotel_id`.
- Valida que `movimientos_inventario` todavia no tenga `hotel_id`.
- Valida que todos los movimientos tengan producto con `hotel_id` derivable.
- Valida que, cuando exista `habitacion_id`, el hotel de la habitacion coincida con el hotel del producto.
- Agrega `hotel_id INT NULL` a `movimientos_inventario`.
- Hace backfill desde `inventario_productos.hotel_id`.
- Valida que no queden movimientos con `hotel_id IS NULL`.
- Valida nuevamente consistencia contra `habitaciones.hotel_id`.
- Crea el indice `idx_movimientos_inventario_hotel_id`.
- Crea la FK `fk_movimientos_inventario_hotel -> hoteles(id)`.
- Registra la migracion en `migrations` con `batch = 4`.

No convierte `hotel_id` a `NOT NULL`, no modifica indices unicos y no deriva desde Reservaciones.

## Rollback SQL

MySQL ejecuta commits implicitos con `ALTER TABLE`, por lo que el rollback debe ejecutarse manualmente si la migracion ya fue aplicada.

```sql
ALTER TABLE movimientos_inventario
    DROP FOREIGN KEY fk_movimientos_inventario_hotel;

DROP INDEX idx_movimientos_inventario_hotel_id
    ON movimientos_inventario;

ALTER TABLE movimientos_inventario
    DROP COLUMN hotel_id;

DELETE FROM migrations
WHERE nombre = '20260526_007_add_hotel_id_movimientos_inventario.sql';
```

El rollback no toca productos, habitaciones, reservaciones, caja, PWA/offline ni otros datos operativos fuera de la columna `hotel_id` agregada por esta migracion.

## Riesgos

- `movimientos_inventario` tiene muchos registros ligados a `reservacion_id` y `habitacion_id`.
- Reservaciones aun no tiene `hotel_id`.
- `InventarioService`, check-in y check-out siguen fuera de alcance.
- Cancelaciones tienen inconsistencia con `inventario_movimientos`: el check-in real escribe en `movimientos_inventario`, pero la devolucion por cancelacion consulta `inventario_movimientos`.
- Nuevos movimientos podrian quedar con `hotel_id NULL` hasta integrar codigo funcional de movimientos.
- Entradas/salidas/ajustes manuales siguen usando flujos globales hasta una fase posterior.
- No se debe activar multi-hotel real con movimientos hasta cerrar codigo funcional, Reservaciones y pruebas transaccionales.

## Pruebas necesarias despues de ejecutar

Antes de ejecutar:

- Crear backup fresco de la base local.
- Confirmar rama `feature/saas-multihotel`.
- Confirmar que `hoteles.slug = 'los-cedros'` existe.
- Confirmar que `movimientos_inventario` no tiene `hotel_id`.
- Confirmar que `inventario_productos.hotel_id` existe.
- Confirmar que `habitaciones.hotel_id` existe.
- Confirmar que no hay diferencias entre `inventario_productos.hotel_id` y `habitaciones.hotel_id` para movimientos con `habitacion_id`.

Ejecucion esperada, cuando se apruebe:

```powershell
docker exec -i medisoft_hoteles_db mysql -u root -proot_pass medisoft_hoteles_import < migrations/20260526_007_add_hotel_id_movimientos_inventario.sql
```

Despues de ejecutar:

- Verificar que `movimientos_inventario.hotel_id` existe.
- Confirmar que los 936 movimientos existentes tienen `hotel_id` de Los Cedros.
- Confirmar 0 movimientos con `hotel_id IS NULL`.
- Confirmar indice `idx_movimientos_inventario_hotel_id`.
- Confirmar FK `fk_movimientos_inventario_hotel`.
- Confirmar registro en `migrations`.
- Confirmar que no se agrego `hotel_id` a `inventario_movimientos`, `alertas_inventario`, `reservaciones`, caja ni PWA/offline.
- Ejecutar `verificar_estado.php` y `preflight_hotel_id.php`.
- Si las herramientas fallan solo porque ahora existe `hotel_id` en `movimientos_inventario`, reportarlo como ajuste esperado para una subfase posterior.
- Probar `/login`, `/`, `/inventario` y `/habitaciones`.
- No probar check-in/check-out como escritura hasta fase autorizada.

## Siguiente fase recomendada

Siguiente paso recomendado:

`Inventario 1-E-B-EJECUCION`: ejecutar la migracion en local con cliente MySQL, despues de backup fresco y aprobacion explicita.

Despues de ejecutar, la fase siguiente deberia ser:

`Inventario 1-E-B.1`: actualizar herramientas SaaS para reconocer `movimientos_inventario.hotel_id`, sin tocar codigo funcional.

La integracion de codigo funcional de movimientos manuales debe venir despues, y check-in/check-out/cancelaciones deben quedar en una fase separada.
