# Fase Inventario 1-G-B - Cierre de devolucion por cancelacion

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Documentar la correccion de devolucion de inventario por cancelacion.

## Problema corregido

- Check-in descontaba inventario en `movimientos_inventario`.
- Cancelacion intentaba devolver leyendo `inventario_movimientos`.
- `inventario_movimientos` estaba vacia.
- Eso podia provocar que el stock descontado no se restaurara correctamente.

## Cambio realizado

- `Reservacion::devolverInventarioCancelacion()` deja de usar `inventario_movimientos`.
- Ahora lee movimientos `SALIDA` desde `movimientos_inventario`.
- Valida `hotel_id`, producto y habitacion.
- Calcula devolucion neta por `producto_id + habitacion_id`.
- Evita doble devolucion.
- Restaura stock con scope por hotel.
- Registra movimiento `ENTRADA` en `movimientos_inventario`.
- No elimina ni modifica `inventario_movimientos`.

## Pruebas realizadas

- `php -l` en `Reservacion.php`.
- `git diff --check`.
- `verificar_estado.php`: `PASS`.
- `preflight_hotel_id.php`: `PASS`.
- Prueba transaccional con reservacion `1282`.
- Primera devolucion: `success`, `productos_devueltos = 1`.
- Reintento: `success`, `productos_devueltos = 0`.
- No duplico devolucion.
- Rollback `OK`.
- Movimientos: `936/936`.
- Entradas persistidas: `0/0`.
- Stock volvio a su valor original.

## Que NO se toco

- `InventarioService`.
- `ReservacionController`.
- Check-in/check-out general.
- Caja.
- PWA/offline.
- Migraciones.
- Schema de `inventario_movimientos`.
- Eliminacion de `inventario_movimientos`.

## Riesgos pendientes

- `InventarioService` todavia no escribe `hotel_id` en nuevos descuentos de check-in.
- Reservaciones todavia no tiene `hotel_id`.
- `inventario_movimientos` sigue existiendo como tabla paralela/legacy.
- Check-in/check-out completo sigue pendiente.
- Cancelaciones quedan mejor, pero aun dependen de movimientos con `hotel_id` correcto.

## Siguiente fase recomendada

Inventario 1-H-A: auditoria/propuesta para integrar `hotel_id` en `InventarioService::descontarInventarioCheckIn()`, sin implementar todavia.
