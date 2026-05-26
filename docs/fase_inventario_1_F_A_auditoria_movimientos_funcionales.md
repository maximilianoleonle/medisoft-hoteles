# Fase Inventario 1-F-A - Auditoria de movimientos funcionales

## Objetivo

Auditar que codigo crea o modifica movimientos de inventario y stock para planear como escribir `hotel_id` en movimientos nuevos sin romper check-in/check-out ni Reservaciones.

## Conclusion principal

- Inventario manual puede ser la primera implementacion.
- Check-in/check-out debe esperar.
- Cancelaciones deben esperar mas por la inconsistencia entre `inventario_movimientos` y `movimientos_inventario`.
- No se debe tocar `InventarioService` todavia.

## Hallazgos principales

- `InventarioController::guardarAction` crea producto y movimiento `ENTRADA` por stock inicial.
- `InventarioController::procesarEntradaAction` suma stock y crea `ENTRADA`.
- `InventarioController::procesarSalidaAction` resta stock y crea `SALIDA`, a veces con `habitacion_id`.
- `InventarioController::procesarAjusteAction` cambia stock y crea `AJUSTE` dos veces. Esto queda documentado como bug a corregir en Inventario 1-F-B.
- `MovimientoInventario` lee historial global y debe ser scoped por `hotel_id` en una subfase de lecturas/historial.
- `InventarioService::descontarInventarioCheckIn` descuenta stock en check-in y debe esperar.
- `Reservacion::devolverInventarioCancelacion` usa `inventario_movimientos` y debe esperar una fase propia.

## Que tocar primero

Inventario 1-F-B debe limitarse a movimientos manuales:

- Entrada manual.
- Salida manual.
- Ajuste manual.
- Movimiento inicial por creacion de producto, si aplica.

## Que dejar fuera

- `InventarioService`.
- `Reservacion.php`.
- `ReservacionController.php`.
- Check-in/check-out.
- Cancelaciones/devoluciones.
- `inventario_movimientos`.
- Caja.
- PWA/offline.
- Reportes/debugs publicos salvo fase aprobada.

## Propuesta para Inventario 1-F-B

- Agregar `hotel_id` a `MovimientoInventario::$fillable`.
- Crear un metodo seguro como `crearMovimientoManual()`.
- Validar que el producto pertenezca al hotel actual.
- Validar que la habitacion pertenezca al hotel actual cuando venga `habitacion_id`.
- Actualizar stock con `WHERE id = ? AND hotel_id = ?`.
- Corregir el doble `create()` de `procesarAjusteAction`.
- No tocar `InventarioService`.

## Pruebas necesarias

- Ejecutar `php -l` en archivos tocados.
- Ejecutar `verificar_estado.php` y confirmar `PASS`.
- Ejecutar `preflight_hotel_id.php` y confirmar `PASS`.
- Crear entrada manual y confirmar `movimientos_inventario.hotel_id = Los Cedros`.
- Crear salida manual con y sin habitacion y validar producto/habitacion por hotel.
- Ejecutar ajuste manual y confirmar un solo movimiento, no duplicado.
- Confirmar stock correcto.
- Confirmar `0` nuevos `hotel_id NULL` en `movimientos_inventario`.
- Confirmar que check-in/check-out, Reservaciones, Caja y PWA no fueron tocados.

## Decision

No se implementa nada en esta fase. La siguiente fase recomendada es Inventario 1-F-B, enfocada solo en movimientos manuales y correccion del doble registro de ajuste.
