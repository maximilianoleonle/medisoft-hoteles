# Fase Reservaciones 1-B-PREP-A.1 - Huerfanos y fallback

## Objetivo

Auditar reservaciones sin habitacion y huerfanos historicos antes de preparar la migracion 008 de `hotel_id` en Reservaciones.

## Reservaciones sin habitacion

### Reservacion 48

- Estado: `checked_out`.
- Fechas: 2026-02-25 a 2026-02-26.
- `huesped_id`: 9, inexistente en `huespedes`.
- Monto: 1000 efectivo.
- Usuario: Max Leon.
- Pista: habitacion liberada `CHOCOLATE`.
- `CHOCOLATE` corresponde a `habitaciones.id = 19`, `hotel_id = 1`, Los Cedros.

### Reservacion 49

- Estado: `checked_out`.
- Fechas: 2026-02-27 a 2026-02-28.
- `huesped_id`: 19, inexistente en `huespedes`.
- Monto: 800 efectivo.
- Usuario: Administrador Principal.
- Pista: habitacion liberada `MARRON`.
- `MARRON` corresponde a `habitaciones.id = 5`, `hotel_id = 1`, Los Cedros.
- Ademas tiene `movimientos_inventario.id = 87` con `hotel_id = 1`.

## Decision

Asignar `hotel_id` de Los Cedros a las reservaciones 48 y 49 en la migracion 008, con fallback documentado.

## Huerfanos historicos

### reservacion_habitaciones huerfanas

- `reservacion_id = 53`, habitaciones 6 y 8, ambas con `hotel_id = 1`.
- `reservacion_id = 206`, habitacion 6, con `hotel_id = 1`.

### reservacion_pagos huerfano

- `id = 56`, `reservacion_id = 53`, efectivo, 1300, creado `2026-03-02 19:59:37`.

### reservacion_notas huerfanas

- 7 notas para `reservacion_id` 1, 3, 34, 45 y 54.

### movimientos_caja huerfano

- `id = 68`, `reservacion_id = 53`, ingreso hospedaje 1300, corte 15 cerrado.

### movimientos_inventario con reservacion inexistente

- 83 movimientos.
- Todos tienen `hotel_id = 1`.
- 0 sin `hotel_id`.
- 0 con habitacion inexistente.
- 0 con hotel distinto entre movimiento y habitacion.

## Decision para huerfanos

- En tablas objetivo de Reservaciones, usar fallback Los Cedros cuando exista evidencia o por compatibilidad mono-hotel.
- No tocar `movimientos_caja` en migracion 008.
- No tocar `movimientos_inventario` en migracion 008.
- No crear foreign keys estrictas todavia.

## Decision sobre hotel_id

- Agregar `hotel_id INT NULL` temporalmente.
- Hacer backfill con objetivo de 0 NULL en tablas objetivo si el fallback queda aprobado.
- No convertir a `NOT NULL` todavia.
- No crear foreign keys estrictas todavia.

## Tabla migrations

Columnas reales:

- `id`
- `nombre`
- `batch`
- `checksum`
- `estado`
- `ejecutada_en`

La migracion 008 debe registrar en `nombre`, no `migration`.

La migracion 008 debe usar `batch`, no `lote`.

## Riesgos

- Huespedes de 48 y 49 ya no existen.
- Hay huerfanos historicos.
- Caja tiene un movimiento huerfano en corte cerrado; no tocar en 008.
- `movimientos_inventario` tiene referencias a reservaciones inexistentes, pero tenant ya esta consistente.
- `NOT NULL` debe esperar a que el codigo escriba `hotel_id`.

## Decision para migracion 008

- Preparar migracion con `hotel_id INT NULL`.
- Backfill normal desde `reservacion_habitaciones -> habitaciones.hotel_id`.
- Backfill explicito de 48 y 49 a Los Cedros.
- Backfill de huerfanos de tablas objetivo a Los Cedros cuando aplique.
- Crear indices simples por `hotel_id`.
- Registrar correctamente en `migrations`.
- No tocar Caja, PWA, Sync, check-in/check-out ni codigo funcional.

## Siguiente fase recomendada

Reservaciones 1-B-PREP-B: crear migracion 008 y documentacion, sin ejecutarla todavia.
