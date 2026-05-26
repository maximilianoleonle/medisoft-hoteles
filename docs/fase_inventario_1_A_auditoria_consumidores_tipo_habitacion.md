# Fase Inventario 1-A - Auditoria de consumidores de tipo_habitacion

## Objetivo

Documentar los consumidores del modulo Inventario que usan `habitaciones.tipo`, `tipos_habitacion.codigo`, `inventario_config_habitacion.tipo_habitacion` o joins entre habitaciones y tipos de habitacion antes de permitir codigos repetidos por hotel.

Esta fase fue solo de auditoria y documentacion. No se modificaron archivos funcionales, no se ejecuto SQL, no se toco base de datos, no se crearon migraciones y no se hizo `ALTER TABLE`.

## Conclusion Principal

Inventario todavia usa `habitaciones.tipo`, `tipos_habitacion.codigo` e `inventario_config_habitacion.tipo_habitacion` como claves globales.

El riesgo principal esta en el check-in/check-out automatico, porque Inventario descuenta productos, revisa configuraciones y crea movimientos a partir del tipo de habitacion. Si dos hoteles usan el mismo codigo de tipo y las consultas no estan filtradas por `hotel_id`, el sistema podria descontar productos o leer configuraciones del hotel equivocado.

Por ahora:

- No se deben permitir codigos duplicados por hotel.
- No se debe ejecutar una migracion de indices unicos compuestos para `tipos_habitacion.codigo`.
- No se deben tocar servicios de check-in/check-out de Inventario sin una fase propia.
- No se debe tocar Reservaciones dentro de la fase de Inventario.

## Hallazgos Por Archivo

### `src/app/services/InventarioService.php`

Hallazgos:

```sql
SELECT tipo FROM habitaciones WHERE id = ?
```

```sql
FROM inventario_config_habitacion ich
JOIN inventario_productos ip ON ich.producto_id = ip.id
WHERE ich.tipo_habitacion = ?
```

Tambien actualiza stock e inserta movimientos:

```sql
UPDATE inventario_productos
SET stock_actual = ?
WHERE id = ?
```

```sql
INSERT INTO movimientos_inventario (...)
```

Tablas involucradas:

- `habitaciones`
- `inventario_config_habitacion`
- `inventario_productos`
- `movimientos_inventario`
- `reservaciones` de forma indirecta por `reservacion_id`

Riesgo: alto.

Motivo: esta clase participa en check-in/check-out automatico. Puede descontar productos del hotel equivocado si `inventario_config_habitacion` e `inventario_productos` no tienen `hotel_id`.

Recomendacion: esperar a migrar tablas de Inventario y no tocar antes de una fase especifica de check-in/check-out.

### `src/app/services/ConfiguracionInventarioService.php`

Hallazgos:

```sql
WHERE ich.tipo_habitacion = ? AND ich.activo = 1
```

```sql
WHERE tipo_habitacion = ? AND producto_id = ?
```

```sql
INSERT INTO inventario_config_habitacion
(tipo_habitacion, producto_id, cantidad_descontar, activo)
```

Tablas involucradas:

- `inventario_config_habitacion`
- `productos`

Riesgo: alto.

Motivo: la configuracion se identifica por `tipo_habitacion` textual y `producto_id`, sin `hotel_id`.

Recomendacion: migrar primero `inventario_config_habitacion` y definir indice compuesto por hotel antes de cambiar servicios.

### `src/app/models/Inventario.php`

Hallazgos:

```sql
FROM inventario_config_habitacion ich
JOIN inventario_productos p ON ich.producto_id = p.id
WHERE ich.activo = 1 AND p.activo = 1
ORDER BY ich.tipo_habitacion, p.nombre
```

```sql
WHERE tipo_habitacion = ? AND producto_id = ?
```

```sql
INSERT INTO inventario_config_habitacion
(tipo_habitacion, producto_id, cantidad_descontar, activo, created_at)
```

Tablas involucradas:

- `inventario_config_habitacion`
- `inventario_productos`

Riesgo: alto.

Motivo: organiza y actualiza configuracion por tipo textual global.

Recomendacion: esperar a migrar configuracion/productos de Inventario.

### `src/app/models/InventarioReportes.php`

Hallazgos:

```sql
WHERE ich.tipo_habitacion = :tipo
```

```sql
UPDATE inventario_config_habitacion
SET activo = FALSE
WHERE tipo_habitacion = :tipo
```

```sql
INSERT INTO inventario_config_habitacion
(tipo_habitacion, tipo_habitacion_id, producto_id, ...)
```

Tablas involucradas:

- `inventario_config_habitacion`
- `productos`
- `categorias_producto`
- `unidades_medida`

Riesgo: medio-alto.

Motivo: combina consultas de configuracion con reportes/consumo estimado. Sin `hotel_id`, los calculos podrian mezclar configuraciones entre hoteles.

Recomendacion: esperar a migrar Inventario y Reportes de Inventario.

### `src/app/models/ConfiguracionInventario.php`

Hallazgos:

```sql
FROM inventario_config_habitacion ich
JOIN productos p ON ich.producto_id = p.id
WHERE ich.tipo_habitacion = :tipo
```

```sql
INSERT INTO inventario_config_habitacion
(tipo_habitacion, producto_id, cantidad_descontar)
```

Tablas involucradas:

- `inventario_config_habitacion`
- `productos`

Riesgo: alto.

Motivo: modelo directo de configuracion por tipo textual sin tenant.

Recomendacion: esperar a migrar tablas de configuracion y productos.

### `src/app/views/inventario/ConfiguracionHabitacionService.php`

Hallazgos:

```sql
FROM tipos_habitacion th
LEFT JOIN habitaciones h ON h.tipo = th.codigo
WHERE th.activo = TRUE
```

```sql
SELECT codigo FROM tipos_habitacion WHERE id = :id
```

```sql
SELECT h.tipo, th.id as tipo_habitacion_id
FROM habitaciones h
INNER JOIN tipos_habitacion th ON h.tipo = th.codigo
WHERE h.id = :habitacion_id
```

```sql
INNER JOIN tipos_habitacion th ON h.tipo = th.codigo
INNER JOIN inventario_config_habitacion ich ON ich.tipo_habitacion_id = th.id
```

Tablas involucradas:

- `tipos_habitacion`
- `habitaciones`
- `inventario_config_habitacion`
- `productos`

Riesgo: alto.

Motivo: contiene joins directos `h.tipo = th.codigo` sin `hotel_id`. Es el ejemplo mas claro de mezcla posible si dos hoteles comparten codigo.

Recomendacion: en una fase futura, cambiar joins a condiciones que incluyan hotel, por ejemplo `h.tipo = th.codigo AND h.hotel_id = th.hotel_id`, siempre que ambas tablas y configuracion de inventario ya esten migradas.

### `src/app/helpers/integracion_inventario.php`

Hallazgos:

```php
mostrar_preview_productos_habitacion($tipo_habitacion_id)
```

```php
configurar_inventario_tipo_habitacion($tipo_habitacion_id, $configuracion = [])
```

```sql
INSERT INTO inventario_config_habitacion
(tipo_habitacion_id, producto_id, cantidad_checkin, cantidad_limpieza, activo)
```

Tablas involucradas:

- `inventario_config_habitacion`
- `movimientos_inventario`
- `productos`

Riesgo: medio-alto.

Motivo: usa `tipo_habitacion_id`, pero sin un `hotel_id` explicito en la configuracion. Tambien contiene funciones auxiliares relacionadas con check-in, dashboard y reportes.

Recomendacion: esperar a Inventario y no integrar con Reservaciones/Dashboard todavia.

### `src/app/controllers/ProductoController.php`

Hallazgos:

```sql
SELECT * FROM tipos_habitacion WHERE activo = 1 ORDER BY id
```

Tambien crea configuraciones con:

```php
'tipo_habitacion_id' => $tipo_id
```

Tablas involucradas:

- `tipos_habitacion`
- `inventario_config_habitacion`
- productos/categorias/unidades relacionadas

Riesgo: medio.

Motivo: obtiene tipos globales para configurar productos. En multi-hotel debe filtrar por hotel.

Recomendacion: corregir dentro de fase Inventario despues de migrar schema de inventario.

## Riesgos Principales

- Descuento de productos del hotel equivocado.
- Configuracion de habitacion leida desde otro hotel.
- Movimientos de inventario sin tenant.
- Check-in/check-out tocando inventario antes de que Reservaciones tenga aislamiento por hotel.
- Joins `h.tipo = th.codigo` sin `hotel_id`.
- Stock operativo compartido globalmente si `inventario_productos` no se migra por hotel.
- Reportes de inventario mezclados entre hoteles.

## Tablas Que Probablemente Necesitan `hotel_id`

Primera prioridad:

- `inventario_config_habitacion`
- `inventario_productos`
- `inventario_categorias`

Segunda prioridad:

- `movimientos_inventario`
- `inventario_movimientos`
- `alertas_inventario`

Revisar segun existencia y uso real:

- `productos`
- `categorias_producto`
- `inventario_habitacion_config`

## Decisiones Tecnicas

- Mantener `inventario_config_habitacion.tipo_habitacion` textual temporalmente.
- No migrar todavia a `tipo_habitacion_id` como unica fuente de verdad.
- No tocar servicios de check-in/check-out todavia.
- No tocar Reservaciones todavia.
- No permitir codigos duplicados por hotel hasta que Inventario este scoped.
- No cambiar indices unicos de Habitaciones/tipos antes de aislar Inventario.

## Orden Recomendado

1. **Inventario 1-B:** auditoria live read-only de schema/datos de Inventario.
2. Migrar catalogos y configuracion:
   - `inventario_productos`
   - `inventario_categorias`
   - `inventario_config_habitacion`
3. Migrar movimientos y alertas:
   - `movimientos_inventario`
   - `inventario_movimientos`
   - `alertas_inventario`
4. Integrar servicios de Inventario con `hotel_id`.
5. Probar check-in/check-out solo como verificacion controlada.
6. Reservaciones debe quedar en fase propia.

## Pruebas Necesarias En Una Implementacion Futura

- Validar que configuracion por tipo solo muestra datos del hotel actual.
- Crear/editar configuracion de inventario por tipo y confirmar `hotel_id`.
- Simular check-in con rollback y confirmar descuento del stock correcto.
- Confirmar que `movimientos_inventario` registra `hotel_id`.
- Confirmar que no se descuenta stock de otro hotel.
- Confirmar que Productos/Inventario siguen cargando.
- Confirmar que Reservaciones no cambia comportamiento.
- Confirmar que Caja no se toca.
- Ejecutar herramientas SaaS despues de cada subfase.

## Que NO Se Toco

- No se modifico Inventario funcional.
- No se tocaron Reservaciones.
- No se toco Caja.
- No se toco PWA/offline.
- No se toco base de datos.
- No se cambiaron indices.
- No se crearon migraciones.
- No se hizo `ALTER TABLE`.
- No se modifico codigo funcional.
