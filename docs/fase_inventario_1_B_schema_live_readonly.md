# Fase Inventario 1-B - Auditoria live read-only de schema y datos

## Objetivo

Documentar el estado real de las tablas relacionadas con Inventario antes de disenar una migracion con `hotel_id`.

La auditoria fue live read-only sobre la base local. Solo se usaron consultas de lectura (`SELECT`, `INFORMATION_SCHEMA`, `SHOW`/metadatos equivalentes). No se ejecutaron migraciones, no se hizo `ALTER TABLE`, no se hicieron `INSERT`, `UPDATE` ni `DELETE`, y no se aplicaron cambios sobre la base de datos.

## Tablas revisadas

Se revisaron las siguientes tablas:

- `inventario_config_habitacion`
- `inventario_productos`
- `inventario_categorias`
- `movimientos_inventario`
- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- `inventario_habitacion_config`

## Conteos encontrados

| Tabla | Registros |
| --- | ---: |
| `inventario_config_habitacion` | 50 |
| `inventario_productos` | 30 |
| `inventario_categorias` | 4 |
| `movimientos_inventario` | 936 |
| `inventario_movimientos` | 0 |
| `alertas_inventario` | 0 |
| `productos` | 5 |
| `categorias_producto` | 4 |
| `inventario_habitacion_config` | 0 |

## Indices unicos detectados

| Tabla | Indice unico | Columnas |
| --- | --- | --- |
| `inventario_config_habitacion` | `uk_tipo_producto` | `tipo_habitacion`, `producto_id` |
| `inventario_productos` | `codigo` | `codigo` |
| `productos` | `codigo` | `codigo` |

## Foreign keys relevantes

| Tabla | Columna | Referencia |
| --- | --- | --- |
| `inventario_config_habitacion` | `producto_id` | `inventario_productos.id` |
| `inventario_productos` | `categoria_id` | `inventario_categorias.id` |
| `movimientos_inventario` | `producto_id` | `inventario_productos.id` |
| `movimientos_inventario` | `habitacion_id` | `habitaciones.id` |
| `movimientos_inventario` | `reservacion_id` | `reservaciones.id` |
| `alertas_inventario` | `producto_id` | `productos.id` |
| `productos` | `categoria_id` | `categorias_producto.id` |

## Duplicados detectados

| Revision | Duplicados |
| --- | ---: |
| `inventario_config_habitacion` por `tipo_habitacion`, `producto_id` | 0 |
| `inventario_productos.codigo` | 0 |
| `inventario_productos.nombre` | 1 |
| `productos.codigo` | 0 |
| `productos.nombre` | 0 |
| `inventario_categorias.nombre` | 0 |
| `categorias_producto.nombre` | 0 |

Detalle del duplicado detectado en `inventario_productos.nombre`:

- `Cloro`: 2 filas en `inventario_productos`
- `6:PAPA1:activo=0`
- `19:001:activo=1`

## Conclusion

`inventario_config_habitacion` necesita `hotel_id` como primera prioridad, porque define configuraciones por `tipo_habitacion` y producto. Mientras no tenga tenant, un futuro codigo de tipo repetido entre hoteles podria leer configuracion del hotel incorrecto.

`inventario_productos` parece ser catalogo operativo por hotel, no un catalogo global puro. Contiene datos operativos como codigo, nombre, categoria, estado y relaciones con configuracion/movimientos.

`movimientos_inventario` es tabla activa. Tiene volumen significativo y relaciones con `habitaciones`, `reservaciones`, `usuarios` e `inventario_productos`, por lo que debe tratarse con mas cuidado que catalogos/configuracion.

`inventario_movimientos` parece legacy o no usada actualmente porque no tiene registros, pero no debe eliminarse ni ignorarse sin una auditoria especifica.

`productos` y `categorias_producto` parecen un modulo paralelo o legacy respecto a `inventario_productos` e `inventario_categorias`. No conviene unificarlos sin una fase dedicada.

## Orden recomendado

Primero migrar catalogos/configuracion:

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`

Despues migrar actividad:

- `movimientos_inventario`
- `alertas_inventario`

Despues revisar tablas legacy/paralelas:

- `productos`
- `categorias_producto`
- `inventario_movimientos`
- `inventario_habitacion_config`

Luego adaptar servicios de Inventario para leer/escribir usando `hotel_id`.

Check-in/check-out y Reservaciones deben esperar una fase propia, porque `movimientos_inventario` esta ligado a `reservacion_id` y esos flujos son operativos sensibles.

## Riesgos principales

- Descontar inventario del hotel equivocado.
- Leer configuracion por tipo de habitacion desde otro hotel.
- Crear movimientos sin tenant en `movimientos_inventario`.
- Romper relaciones con `reservacion_id` y `habitacion_id`.
- Duplicado de nombre en `inventario_productos` (`Cloro`) si se decide endurecer unicidad por nombre.
- Confusion entre `inventario_productos` y `productos`.
- Tocar check-in/check-out antes de que Reservaciones tenga una estrategia multi-hotel propia.

## Que NO se recomienda tocar todavia

- Reservaciones.
- Check-in/check-out.
- Caja.
- PWA/offline.
- Unificacion de `productos` vs `inventario_productos`.
- Migracion a `tipo_habitacion_id`.
- Indices unicos compuestos de Habitaciones/tipos.

## Siguiente fase recomendada

La siguiente fase recomendada es Inventario 1-C: disenar una migracion schema/backfill para catalogos/configuracion, sin ejecutarla todavia.

Esa fase deberia preparar, pero no ejecutar, una migracion para agregar `hotel_id` a:

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`

Tambien debe documentar backfill hacia Los Cedros, indices propuestos, foreign keys, riesgos, rollback y pruebas esperadas antes de tocar servicios funcionales.
