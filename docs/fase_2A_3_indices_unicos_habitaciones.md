# Fase 2A.3 - Auditoria de indices unicos de Habitaciones

## Objetivo

Documentar el estado actual de los indices unicos del modulo Habitaciones y decidir si conviene convertirlos de unicos globales a unicos por hotel en esta etapa de la migracion SaaS multi-hotel.

Esta fase fue solo de auditoria y documentacion. No se ejecutaron migraciones, no se hizo `ALTER TABLE`, no se modifico codigo funcional y no se tocaron datos.

## Estado Actual De Indices

### `habitaciones`

Indice unico actual:

```sql
UNIQUE KEY `numero` (`numero`)
```

Este indice hace que el numero de habitacion sea unico globalmente en toda la base.

Indice recomendado a futuro:

```sql
UNIQUE KEY `uk_habitaciones_hotel_numero` (`hotel_id`, `numero`)
```

### `tipos_habitacion`

Indice unico actual:

```sql
UNIQUE KEY `uk_codigo` (`codigo`)
```

Este indice hace que el codigo del tipo de habitacion sea unico globalmente en toda la base.

Indice recomendado a futuro:

```sql
UNIQUE KEY `uk_tipos_habitacion_hotel_codigo` (`hotel_id`, `codigo`)
```

## Resultados Live Read-Only

Consultas `SELECT` ejecutadas en modo solo lectura sobre `medisoft_hoteles_import`:

| Revision | Resultado |
| --- | ---: |
| `habitaciones` total | 49 |
| `tipos_habitacion` total | 6 |
| `habitaciones.hotel_id IS NULL` | 0 |
| `tipos_habitacion.hotel_id IS NULL` | 0 |
| Duplicados `(hotel_id, numero)` | 0 |
| Duplicados `(hotel_id, codigo)` | 0 |

Tambien se confirmo que:

- `habitaciones.numero` sigue siendo `UNIQUE` global.
- `tipos_habitacion.uk_codigo` sigue siendo `UNIQUE` global.
- La tabla `migrations` usa la columna `batch`, no `lote`.

## Conclusion

A nivel datos, es viable convertir los indices unicos del modulo Habitaciones a indices compuestos por hotel porque:

- No hay `hotel_id NULL` en `habitaciones`.
- No hay `hotel_id NULL` en `tipos_habitacion`.
- No hay duplicados por `(hotel_id, numero)`.
- No hay duplicados por `(hotel_id, codigo)`.

A nivel sistema completo, todavia no conviene convertirlos porque varios consumidores siguen asumiendo que `tipos_habitacion.codigo` y `habitaciones.tipo` son claves textuales globales.

## Riesgo De `habitaciones.numero`

En SaaS multi-hotel, distintos hoteles deben poder tener habitaciones con el mismo numero o identificador, por ejemplo:

- Hotel A: habitacion `101`
- Hotel B: habitacion `101`

El indice unico global actual bloquea ese caso.

El cambio correcto a futuro es reemplazar:

```sql
UNIQUE KEY `numero` (`numero`)
```

por:

```sql
UNIQUE KEY `uk_habitaciones_hotel_numero` (`hotel_id`, `numero`)
```

Riesgo estimado: medio.

Motivo: el codigo funcional de Habitaciones ya escribe y respeta `hotel_id`, pero aun existen APIs, PWA/offline y flujos operativos no migrados que deben revisarse antes de habilitar duplicados reales entre hoteles.

## Riesgo De `tipos_habitacion.codigo`

Este caso es mas delicado que `habitaciones.numero`.

`tipos_habitacion.codigo` se usa como clave textual del tipo de habitacion y se relaciona conceptualmente con `habitaciones.tipo`. Al permitir codigos repetidos por hotel, cualquier consulta o logica que una por `codigo` sin `hotel_id` puede mezclar configuraciones entre hoteles.

El cambio correcto a futuro es reemplazar:

```sql
UNIQUE KEY `uk_codigo` (`codigo`)
```

por:

```sql
UNIQUE KEY `uk_tipos_habitacion_hotel_codigo` (`hotel_id`, `codigo`)
```

Riesgo estimado: alto.

Motivo: `codigo` aparece en Inventario, Tarifas, Reportes, Dashboard, APIs/PWA y Reservaciones. Es necesario auditar esos consumidores antes de permitir codigos repetidos por hotel.

## Usos Detectados De `tipos_habitacion.codigo` Y `habitaciones.tipo`

### Inventario

Se detectaron usos de `habitaciones.tipo`, `tipo_habitacion`, `tipo_habitacion_id` y joins entre `habitaciones` y `tipos_habitacion`.

Riesgo: alto.

Motivo: Inventario puede ejecutar descuentos, configuraciones y consultas por tipo de habitacion. Si dos hoteles comparten el mismo codigo, las consultas sin `hotel_id` pueden aplicar configuracion incorrecta.

### Configuracion De Inventario

Se detectaron joins como:

```sql
LEFT JOIN habitaciones h ON h.tipo = th.codigo
INNER JOIN tipos_habitacion th ON h.tipo = th.codigo
```

Riesgo: alto.

Motivo: estos joins deben incluir `hotel_id` antes de permitir codigos repetidos por hotel.

### Tarifas

Las tarifas usan tipos de habitacion como codigos textuales, incluyendo JSON en `incrementos_tarifas.tipos_habitacion`.

Riesgo: medio-alto.

Motivo: las tarifas por tipo deben quedar asociadas al hotel antes de permitir que dos hoteles usen el mismo codigo con reglas distintas.

### Incrementos De Tarifa

`IncrementoTarifa` compara tipos por codigo textual:

```php
if (in_array($tipo_habitacion, $tipos)) {
```

Riesgo: medio-alto.

Motivo: la comparacion funciona para mono-hotel, pero en multi-hotel necesita contexto de hotel o datos ya scoped.

### Reportes

Reportes agrupa y calcula usando `h.tipo`.

Riesgo: medio-alto.

Motivo: los reportes deben filtrar por hotel antes de interpretar tipos repetidos.

### Dashboard

Dashboard usa conteos y agrupaciones por tipo de habitacion.

Riesgo: medio.

Motivo: no se debe permitir que muestre agregados de mas de un hotel cuando haya codigos repetidos.

### APIs/PWA

APIs como busqueda, reservaciones del dia y endpoints de habitaciones exponen `hab.tipo` o `habitaciones_tipos`.

Riesgo: alto.

Motivo: las APIs/PWA todavia no estan migradas y pueden mezclar datos o cachear informacion global.

### Reservaciones

Reservaciones usa habitaciones y tipos para precios, vistas, edicion y disponibilidad.

Riesgo: alto.

Motivo: Reservaciones todavia no tiene `hotel_id` y sigue siendo una zona critica. No debe usarse como base para habilitar duplicados multi-hotel hasta su fase propia.

## Correccion Para Migracion Futura

La migracion futura 005 debe registrar ejecucion usando la columna `batch`.

Correcto:

```sql
INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
VALUES ('20260526_005_convertir_unicos_habitaciones_por_hotel.sql', 2, NULL, 'ejecutada', NOW())
ON DUPLICATE KEY UPDATE
    estado = 'ejecutada',
    ejecutada_en = NOW();
```

Incorrecto:

```sql
INSERT INTO migrations (nombre, lote, estado, ejecutada_en)
VALUES (...);
```

## Decision

Decision actual:

- No preparar migracion 005 todavia.
- No ejecutar migracion 005.
- No cambiar indices unicos todavia.
- No crear segundo hotel fake todavia.
- Primero auditar consumidores de `tipos_habitacion.codigo` y `habitaciones.tipo`.

## Que NO Se Toco

- No se tocaron Reservaciones.
- No se toco Caja.
- No se toco PWA/offline.
- No se toco Dashboard.
- No se tocaron Reportes.
- No se toco Inventario.
- No se tocaron Tarifas.
- No se cambiaron indices.
- No se toco base de datos con escrituras.
- No se modifico codigo funcional.

## Siguiente Fase Recomendada

Fase 2A.4-A: Auditoria de consumidores de `tipos_habitacion.codigo` en Inventario, Tarifas, Reportes, APIs y Reservaciones.

Objetivo recomendado:

- Listar todos los consumidores de `habitaciones.tipo`.
- Listar todos los consumidores de `tipos_habitacion.codigo`.
- Separar usos seguros de usos que requieren `hotel_id`.
- Detectar joins que deben cambiar de `h.tipo = th.codigo` a una condicion por hotel.
- Decidir si `habitaciones.tipo` debe seguir como codigo textual temporal o migrar gradualmente a `tipo_habitacion_id`.

## Actualizacion 2026-06-24

Con autorizacion explicita del usuario, se habilito la numeracion de habitaciones repetida entre hoteles.

Migracion aplicada:

```text
migrations/20260624_001_habitaciones_numero_unico_por_hotel.sql
```

Cambio realizado:

- Se agrego el indice unico `uk_habitaciones_hotel_numero (hotel_id, numero)`.
- Se elimino el indice unico global solo sobre `numero`.
- La validacion de creacion/edicion de habitaciones ahora bloquea duplicados solo dentro del hotel actual.

Validacion local ejecutada:

- Sin `hotel_id NULL` en `habitaciones`.
- Sin duplicados por `(hotel_id, numero)` antes de migrar.
- Prueba transaccional exitosa permitiendo `numero = 10` en hoteles distintos.

## Actualizacion 2026-06-24 - Tipos de habitacion por hotel

Con autorizacion explicita del usuario, se habilito la reutilizacion de codigos de tipo de habitacion entre hoteles.

Prerrequisito aplicado:

```text
migrations/20260624_005_incrementos_tarifas_hotel_id.sql
```

Migracion aplicada:

```text
migrations/20260624_006_tipos_habitacion_codigo_unico_por_hotel.sql
```

Cambio realizado:

- Se agrego `incrementos_tarifas.hotel_id` y el modelo `IncrementoTarifa` quedo scoped por hotel.
- Se agrego el indice unico `uk_tipos_habitacion_hotel_codigo ((COALESCE(hotel_id, 0)), codigo)`.
- Se elimino el indice unico global `uk_codigo (codigo)`.

Validacion local ejecutada:

- Sin `hotel_id NULL` en `tipos_habitacion`.
- Sin duplicados por `COALESCE(hotel_id, 0), codigo`.
- Prueba transaccional exitosa permitiendo `codigo = sencilla` en hoteles distintos.
- Prueba transaccional exitosa bloqueando duplicados dentro del mismo hotel.
- `php -l` correcto en `IncrementoTarifa.php` y `TarifasController.php`.

Detalle tecnico documentado en:

```text
docs/fase_tipos_habitacion_codigo_por_hotel.md
```
