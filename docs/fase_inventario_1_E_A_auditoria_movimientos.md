# Fase Inventario 1-E-A - Auditoria de movimientos de inventario

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Auditar `movimientos_inventario` y su relacion con Inventario, Reservaciones, Habitaciones, Check-in y Check-out antes de proponer una migracion con `hotel_id`.

La auditoria fue read-only:

- No se modificaron archivos.
- No se ejecutaron migraciones.
- No se hizo `ALTER TABLE`.
- No se hicieron `INSERT`, `UPDATE` ni `DELETE`.
- No se tocaron datos.

## Datos live

Estado observado en la base local `medisoft_hoteles_import`:

- `movimientos_inventario` total: 936
- `SALIDA`: 902
- `ENTRADA`: 34
- Con `reservacion_id`: 865
- Con `habitacion_id`: 865
- Con `reservacion_id` y `habitacion_id`: 865
- Sin `producto_id`: 0
- Movimientos cuyo producto pertenece a `Los Cedros`: 936
- Movimientos cuya habitacion pertenece a `Los Cedros`: 865
- Producto/habitacion con hotel distinto: 0
- `inventario_movimientos` total: 0

## Estados de reservacion en movimientos

Distribucion de `movimientos_inventario` por estado de reservacion:

- `checked_out`: 742
- `SIN_RESERVACION`: 154
- `cancelada`: 24
- `checked_in`: 16

## Schema de movimientos_inventario

### Columnas

- `id`
- `producto_id`
- `tipo_movimiento`
- `cantidad`
- `stock_anterior`
- `stock_posterior`
- `motivo`
- `habitacion_id`
- `reservacion_id`
- `usuario_id`
- `created_at`
- `updated_at`

### Indices

- `PRIMARY(id)`
- `idx_producto(producto_id)`
- `habitacion_id`
- `reservacion_id`
- `usuario_id`
- `idx_fecha(created_at)`

### Foreign keys

- `producto_id -> inventario_productos(id)`
- `habitacion_id -> habitaciones(id)`
- `reservacion_id -> reservaciones(id)`
- `usuario_id -> usuarios(id)`

## Archivos encontrados y riesgo

| Archivo | Hallazgo | Riesgo |
| --- | --- | --- |
| `src/app/services/InventarioService.php` | Descuenta inventario en check-in, actualiza stock e inserta en `movimientos_inventario`. | Alto |
| `src/app/controllers/ReservacionController.php` | Orquesta descuento automatico desde check-in y proceso express. | Alto |
| `src/app/controllers/InventarioController.php` | Entradas, salidas y ajustes manuales actualizan stock y crean movimientos. | Alto |
| `src/app/models/MovimientoInventario.php` | Lee movimientos sin scope por hotel. | Medio |
| `src/app/models/Reservacion.php` | La devolucion por cancelacion usa `inventario_movimientos`, no `movimientos_inventario`. | Alto |
| `src/app/helpers/integracion_inventario.php` | Archivo guia/legacy con mantenimiento que borra movimientos antiguos si se ejecuta. | Alto si se activa |
| `src/app/views/inventario/ConfiguracionHabitacionService.php` | Servicio alterno con stored procedure y tablas paralelas. | Medio/legacy |

## Que puede usar hotel_id ya

- Movimientos con `producto_id` pueden inferir hotel desde `inventario_productos.hotel_id`.
- Movimientos con `habitacion_id` pueden validar contra `habitaciones.hotel_id`.
- Configuracion de descuento puede usar `inventario_config_habitacion.hotel_id` e `inventario_productos.hotel_id`.

## Que debe esperar a Reservaciones

- Reglas definitivas sobre `reservacion_id`.
- Check-in/check-out completo.
- Cancelaciones con devolucion de inventario.
- Movimientos ligados a reservaciones historicas.
- Integridad tenant de `reservacion_habitaciones`.

## Riesgos principales

### Agregar hotel_id a movimientos_inventario ahora

Riesgo alto. El `hotel_id` puede derivarse desde `inventario_productos`, `habitaciones` o `reservaciones`, pero Reservaciones todavia no tiene `hotel_id`. Antes de migrar hay que definir una regla de derivacion y validar inconsistencias historicas.

### Tocar InventarioService antes de Reservaciones

Riesgo alto. `InventarioService` afecta check-in y puede modificar stock operativo. Cambiarlo antes de scopear Reservaciones puede romper descuentos, disponibilidad o trazabilidad.

### Devolucion por cancelacion inconsistente

Riesgo alto. `Reservacion::devolverInventarioCancelacion()` consulta e inserta en `inventario_movimientos`, pero el check-in real escribe en `movimientos_inventario`. Esto puede impedir devoluciones correctas o dejar trazabilidad partida.

### Entrada/salida/ajuste manual

Riesgo alto. Los flujos manuales todavia usan operaciones globales de producto/stock y generan movimientos sin `hotel_id`.

### inventario_movimientos

Riesgo medio. Esta tabla esta vacia, pero sigue referenciada por codigo de cancelacion. No debe declararse legacy ni eliminarse hasta completar una auditoria especifica.

## Decision

- No migrar `movimientos_inventario` todavia.
- No tocar `InventarioService` todavia.
- No tocar check-in/check-out todavia.
- No tocar cancelaciones todavia.
- No tocar Reservaciones todavia.
- No tocar Caja.
- No tocar PWA/offline.

## Orden recomendado

1. Documentar esta auditoria.
2. Preparar `Inventario 1-E-B-PREP`: migracion propuesta para `movimientos_inventario.hotel_id`, sin ejecutarla.
3. Resolver estrategia de derivacion: preferir `inventario_productos.hotel_id` y validar contra `habitaciones.hotel_id` cuando exista.
4. No tocar check-in/check-out hasta preparar pruebas transaccionales y revisar Reservaciones.
5. Auditar/corregir inconsistencia `inventario_movimientos` vs `movimientos_inventario`.
6. Integrar movimientos manuales antes que check-in/check-out.
7. Tratar check-in/check-out y cancelaciones en una fase separada.

## Que NO se recomienda tocar todavia

- `Reservacion.php`
- `ReservacionController.php`
- `InventarioService.php`
- Check-in/check-out
- Cancelaciones
- Schema de `movimientos_inventario`
- `inventario_movimientos`
- Caja
- PWA/offline
