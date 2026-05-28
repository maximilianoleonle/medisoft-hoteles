# Reservaciones 1-F-F-E-E-E-C-A - Auditoria de check-in con pagos mixtos

## Objetivo

Auditar `Reservacion::checkInConPagosMixtos()` para preparar una correccion segura del insert de `movimientos_caja` con `hotel_id`.

## Hallazgo principal

- El flujo ya valida bastante bien `hotel_id` en `reservaciones`, `reservacion_habitaciones`, `habitaciones`, `cortes_caja` y `reservacion_pagos`.
- El punto pendiente es claro: el `INSERT INTO movimientos_caja` dentro de `Reservacion::checkInConPagosMixtos()` todavia no escribe `hotel_id`.

## Archivos encontrados

- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`

## Flujo actual

- `ReservacionController::checkInAction()` valida caja abierta con `Caja::obtenerCorteActual()`.
- Carga la reservacion con `Reservacion::obtenerPorId()`, ya scoped.
- Arma pagos y llama `Reservacion::checkInConPagosMixtos()`.
- El modelo valida reservacion con `id + hotel_id`.
- Actualiza `reservaciones` con `id + hotel_id`.
- Valida y actualiza habitaciones usando `rh.hotel_id` y `h.hotel_id`.
- Inserta ingresos en `movimientos_caja`, pero sin `hotel_id`.
- Registra pagos mixtos con `registrarPagosMixtos()`, que si usa `hotel_id`.
- El controller luego llama `procesarSolicitudFactura()`, fuera de esta fase.

## Estado por metodo

- `ReservacionController::checkInAction()`: riesgo medio; consume reservacion/caja scoped, pero llama factura despues.
- `Reservacion::checkInConPagosMixtos()`: riesgo alto; casi todo scoped, pero falta `hotel_id` en `movimientos_caja`.
- `Reservacion::registrarPagosMixtos()`: riesgo bajo; ya inserta `reservacion_pagos` con `hotel_id`.
- `Caja::obtenerCorteActual()`: riesgo bajo; ya valida `cc.hotel_id` y `c.hotel_id`.
- `MovimientoCaja::registrarMovimiento()`: riesgo bajo; patron correcto porque escribe `hotel_id`.

## Consultas ya scoped

- `reservaciones WHERE id = ? AND hotel_id = ?`.
- `UPDATE reservaciones WHERE id = ? AND hotel_id = ? AND estado = 'confirmada'`.
- `reservacion_habitaciones` con `rh.hotel_id = ?`.
- `habitaciones` con `h.hotel_id = rh.hotel_id` y `h.hotel_id = ?`.
- `Caja::obtenerCorteActual()` con `cc.hotel_id = ?` y `c.hotel_id = cc.hotel_id`.
- `registrarPagosMixtos()` borra/inserta `reservacion_pagos` con `hotel_id`.

## Consultas o inserts pendientes

- `categorias_movimientos` se consulta globalmente como catalogo auxiliar.
- Si no existe categoria Hospedaje, se inserta en `categorias_movimientos` global; queda como deuda de catalogo.
- El `INSERT INTO movimientos_caja` de `checkInConPagosMixtos()` no incluye `hotel_id`.

## Propuesta tecnica

En la siguiente fase tocar unicamente `Reservacion::checkInConPagosMixtos()` en `src/app/models/Reservacion.php`:

- Confirmar que `$hotel_id = $this->hotelIdActual()` siga siendo la fuente.
- Validar defensivamente que `$corteActual['hotel_id'] === $hotel_id`.
- Agregar `hotel_id` al `INSERT INTO movimientos_caja`.
- Pasar `$hotel_id` como parametro del insert.
- Mantener `usuarios` y `categorias_movimientos` como auxiliares.
- No tocar `registrarPagosMixtos()`, porque ya esta scoped.
- No tocar factura, cancelaciones ni modificar dias.

## Que dejar fuera

- `cancelarAction()`.
- `modificarDiasAction()`.
- `Reservacion::cancelar()`.
- `solicitudes_factura`.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones/schema.
- cambios de datos.

## Pruebas necesarias

- `php -l src/app/models/Reservacion.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Prueba controlada solo con rollback seguro o entorno de prueba.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que no se tocaron cancelaciones, modificar dias, factura, PWA/Sync/APIs.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-C-B: implementar unicamente el `hotel_id` faltante en el `INSERT` de `movimientos_caja` dentro de `Reservacion::checkInConPagosMixtos()`, sin tocar factura, cancelaciones ni modificar dias.
