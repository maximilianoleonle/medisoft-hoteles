# Fase Inventario 1-H-B - Cierre de descuento automatico en check-in

## Objetivo

Documentar la integracion de `hotel_id` en el descuento automatico de inventario durante check-in.

## Que se corrigio

- `InventarioService::verificarDisponibilidad()` ahora valida la habitacion con `hotel_id`.
- `InventarioService::descontarInventarioCheckIn()` ahora valida la habitacion con `hotel_id`.
- La configuracion de inventario se lee con `inventario_config_habitacion.hotel_id`.
- Los productos se unen validando `inventario_productos.hotel_id`.
- El stock se actualiza con `WHERE id = ? AND hotel_id = ?`.
- Los movimientos `SALIDA` se insertan en `movimientos_inventario` con `hotel_id`.

## Pruebas realizadas

- `php -l` en `InventarioService.php`: PASS.
- `git diff --check`: PASS.
- `verificar_estado.php`: PASS.
- `preflight_hotel_id.php`: PASS.
- `verificarDisponibilidad(1)` leyo configuracion/productos del hotel actual.
- `verificarDisponibilidad(-999999)` devolvio habitacion no encontrada.
- `descontarInventarioCheckIn(1, 1283)` en transaccion creo un movimiento `SALIDA` con `hotel_id` de Los Cedros.
- El stock cambio dentro de la transaccion y volvio a su valor original despues del rollback.
- La cancelacion/devolucion siguio funcionando con `movimientos_inventario`.
- No quedaron datos QA persistidos.

## Que NO se toco

- `ReservacionController`.
- `Reservacion.php`.
- Caja.
- PWA/offline.
- Dashboard/reportes.
- Migraciones.
- Schema.
- `inventario_movimientos`.

## Riesgos pendientes

- Reservaciones todavia no tiene `hotel_id`.
- `tipo_habitacion` sigue siendo textual.
- Check-in completo aun depende de Reservaciones sin tenant.
- `debugUltimosMovimientos()` sigue fuera de scope.
- PWA/API/Caja siguen pendientes.
- `inventario_movimientos` sigue existiendo como tabla paralela/legacy.

## Siguiente fase recomendada

Auditoria de Reservaciones para `hotel_id`, antes de tocar mas flujos de check-in/check-out.
