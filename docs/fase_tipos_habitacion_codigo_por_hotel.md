# Fase Tipos Habitacion - Codigo Unico Por Hotel

Fecha: 2026-06-24

## Objetivo

Resolver el riesgo de unicidad global en `tipos_habitacion.codigo`, permitiendo que distintos hoteles usen el mismo codigo de tipo de habitacion sin bloquearse entre si.

## Prerrequisito Resuelto

Antes de migrar `tipos_habitacion`, se aislaron las tarifas dinamicas porque `IncrementoTarifa` aplicaba reglas por `tipo_habitacion` sin `hotel_id`.

Migracion aplicada:

```text
migrations/20260624_005_incrementos_tarifas_hotel_id.sql
```

Cambios:

- Se agrego `incrementos_tarifas.hotel_id`.
- Se agrego `idx_incrementos_tarifas_hotel_fecha`.
- Se agrego `fk_incrementos_tarifas_hotel`.
- `IncrementoTarifa` ahora crea, busca, actualiza, elimina, lista y calcula incrementos por hotel.
- `TarifasController` ahora filtra por hotel tambien en el fallback de tipos desde `habitaciones`.

La tabla local `incrementos_tarifas` estaba vacia al momento de ejecutar la migracion.

## Migracion Principal

```text
migrations/20260624_006_tipos_habitacion_codigo_unico_por_hotel.sql
```

Cambio de schema:

- Se agrego `uk_tipos_habitacion_hotel_codigo ((COALESCE(hotel_id, 0)), codigo)`.
- Se elimino el indice unico global `uk_codigo (codigo)`.

El indice funcional permite reutilizar codigos entre hoteles y mantiene un scope global controlado para registros con `hotel_id NULL`.

## Validacion Local Ejecutada

- `tipos_habitacion`: 10 registros.
- `tipos_habitacion.hotel_id NULL`: 0.
- Sin duplicados por `COALESCE(hotel_id, 0), codigo`.
- Prueba transaccional exitosa permitiendo `codigo = sencilla` en hotel 1 y hotel 4.
- Prueba transaccional exitosa bloqueando duplicado dentro del hotel 4.
- Prueba transaccional exitosa bloqueando duplicado global con `hotel_id NULL`.
- `php -l` correcto en:
  - `src/app/models/IncrementoTarifa.php`
  - `src/app/controllers/TarifasController.php`

## Alcance No Tocado

- No se cambio `habitaciones.tipo`.
- No se migraron tipos textuales a `tipo_habitacion_id`.
- No se tocaron Reservaciones, Caja, PWA/offline, `/api/sync`, permisos ni auth.
- No se reactivaron servicios legacy de inventario/productos.

## Pendiente

- Revisar en una fase separada los consumidores legacy:
  - `src/app/controllers/ProductoController.php`
  - `src/app/views/inventario/ConfiguracionHabitacionService.php`
  - `src/app/services/ConfiguracionInventarioService.php`
  - `src/app/models/ConfiguracionInventario.php`
  - `src/app/models/InventarioReportes.php`

Estos consumidores ya estaban documentados como legacy/congelados o fuera del flujo moderno; no fueron usados como base para esta migracion.
