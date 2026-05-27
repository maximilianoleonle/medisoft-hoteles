# Fase Reservaciones 1-E-B-PREP - Scope check-in/check-out por hotel_id

## Objetivo

Disenar una implementacion minima para que los flujos de check-in y check-out respeten `hotel_id` sin tocar Caja, PWA/offline, Sync ni APIs globales.

Esta fase fue solo de analisis y propuesta:

- No modifico codigo funcional.
- No ejecuto SQL.
- No toco base de datos.
- No ejecuto migraciones.
- No implemento cambios.

## Alcance permitido para Reservaciones 1-E-B

La futura implementacion 1-E-B debe limitarse a proteger las transiciones de Reservaciones/Habitaciones/Inventario por hotel actual.

Se permite:

- Cargar reservaciones por `id + hotel_id`.
- Validar `reservacion_habitaciones.hotel_id`.
- Validar `habitaciones.hotel_id`.
- Actualizar `reservaciones` con `WHERE id = ? AND hotel_id = ?`.
- Actualizar `habitaciones` con `hotel_id`.
- Marcar habitacion en limpieza con `hotel_id`.
- Scopear subconsultas de otras reservaciones activas con `r2.hotel_id` y `rh2.hotel_id`.
- Scopear preconsultas de `procesarDescuentoInventario()`.
- Llamar `InventarioService`, que ya quedo scoped por `hotel_id`.

## Decision sobre reservacion_pagos

Si `Reservacion::checkInConPagosMixtos()` ya inserta pagos en `reservacion_pagos` dentro del flujo existente, se permite agregar `hotel_id` al `INSERT`.

Esta excepcion queda aprobada solo para evitar nuevos registros con `reservacion_pagos.hotel_id IS NULL`.

No se permite:

- Modificar logica de Caja.
- Tocar `movimientos_caja`.
- Refactorizar pagos.
- Refactorizar cortes.
- Cambiar el comportamiento funcional de cobros.

## Fuera de alcance

Queda fuera de Reservaciones 1-E-B:

- Caja funcional.
- `movimientos_caja`.
- `cortes_caja`.
- PWA/offline.
- `Sync.php`.
- APIs globales.
- Dashboard/reportes.
- Facturacion funcional.
- Huespedes como modelo tenant.
- Llaves/remotos si requieren una fase propia.

## Riesgos

- `checkInConPagosMixtos()` mezcla reservacion, habitacion, caja y pagos.
- `checkInCheckOutExpress()` combina check-in, pagos, inventario y check-out.
- Caja/Pagos aun no son tenant-aware.
- Sync/PWA pueden tener caminos paralelos sin scope por hotel.
- Llaves/remotos operan por `habitacion_id` y `reservacion_id` globales.
- Si queda algun camino usando `find($id)` global, podria operar una reservacion de otro hotel.
- Si un flujo inserta en `reservacion_pagos` sin `hotel_id`, las herramientas SaaS volverian a detectar deuda.

## Archivos candidatos para implementacion

- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`

No se recomienda tocar otros archivos en 1-E-B.

## Metodos candidatos

En `ReservacionController.php`:

- `ReservacionController::checkInAction()`
- `ReservacionController::checkOutAction()`
- `ReservacionController::checkOutRapidoAction()`
- `ReservacionController::checkOutParcialAction()`
- `ReservacionController::checkInTardioAction()`
- `ReservacionController::procesarCheckInTardioAction()`
- `ReservacionController::procesarDescuentoInventario()`

En `Reservacion.php`:

- `Reservacion::checkInConPagosMixtos()`
- `Reservacion::checkIn()`
- `Reservacion::checkOut()`
- `Reservacion::checkOutParcial()`
- `Reservacion::verificarEstadoCheckIn()`
- `Reservacion::checkInTardio()`
- `Reservacion::checkInCheckOutExpress()`

## Consultas a scopear

Las consultas de lectura de reservacion deben usar `hotel_id`:

```sql
SELECT ...
FROM reservaciones
WHERE id = ?
  AND hotel_id = ?
```

Las actualizaciones de reservacion deben usar `hotel_id`:

```sql
UPDATE reservaciones
SET estado = ?
WHERE id = ?
  AND hotel_id = ?
```

Las lecturas y actualizaciones de habitaciones por reservacion deben validar `reservacion_habitaciones.hotel_id` y `habitaciones.hotel_id`:

```sql
SELECT h.*
FROM reservacion_habitaciones rh
INNER JOIN habitaciones h
  ON h.id = rh.habitacion_id
 AND h.hotel_id = rh.hotel_id
WHERE rh.reservacion_id = ?
  AND rh.hotel_id = ?
```

Las actualizaciones de habitaciones deben quedar scoped por hotel:

```sql
UPDATE habitaciones h
INNER JOIN reservacion_habitaciones rh
  ON h.id = rh.habitacion_id
 AND h.hotel_id = rh.hotel_id
SET h.estado = ?
WHERE rh.reservacion_id = ?
  AND rh.hotel_id = ?
  AND h.hotel_id = ?
```

Las subconsultas de otras reservaciones activas deben validar hotel en reservaciones y en la tabla pivote:

```sql
SELECT 1
FROM reservacion_habitaciones rh2
INNER JOIN reservaciones r2
  ON r2.id = rh2.reservacion_id
 AND r2.hotel_id = rh2.hotel_id
WHERE rh2.habitacion_id = ?
  AND rh2.hotel_id = ?
  AND r2.hotel_id = ?
  AND r2.estado = 'checked_in'
```

Si el flujo existente inserta pagos, el `INSERT` debe incluir `hotel_id` para evitar nuevos NULL:

```sql
INSERT INTO reservacion_pagos (
  reservacion_id,
  hotel_id,
  metodo_pago,
  monto,
  referencia,
  created_at
) VALUES (?, ?, ?, ?, ?, NOW())
```

## Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `docker compose exec app php tools/saas/verificar_estado.php`.
- `docker compose exec app php tools/saas/preflight_hotel_id.php`.
- Check-in transaccional con rollback.
- Check-out transaccional con rollback.
- Confirmar `reservaciones.hotel_id`.
- Confirmar `reservacion_habitaciones.hotel_id`.
- Confirmar `habitaciones.hotel_id`.
- Confirmar `movimientos_inventario.hotel_id`.
- Confirmar que `reservacion_pagos` no crea nuevos `hotel_id NULL` si el flujo inserta pagos.
- Confirmar que Caja/PWA/Sync no fueron tocados.
- Confirmar 0 nuevos `hotel_id NULL` en tablas base de Reservaciones.

## Recomendacion

Si conviene implementar Reservaciones 1-E-B, pero debe presentarse como una fase de scope minimo para Reservacion/Habitacion/Inventario.

No debe llamarse cierre completo multi-hotel de check-in/check-out, porque todavia quedan pendientes Caja/Pagos, PWA/offline, Sync, APIs globales, llaves/remotos y la decision tenant de Huespedes.

La implementacion debe ser conservadora:

- Primero proteger carga y updates por `hotel_id`.
- Luego proteger cambios de habitaciones.
- Luego scopear preconsultas de inventario.
- Solo agregar `hotel_id` a `reservacion_pagos` si el flujo ya inserta pagos y sin cambiar logica de Caja.
