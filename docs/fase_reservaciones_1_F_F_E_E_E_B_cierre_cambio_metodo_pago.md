# Reservaciones 1-F-F-E-E-E-B-CIERRE - Cierre de cambio de metodo de pago scoped

## Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `ReservacionController::cambiarMetodoPagoAction()`.

## Que se logro

- `cambiarMetodoPagoAction()` ahora resuelve el hotel actual con `obtenerHotelIdActualCompat()`.
- La reservacion se carga con `obtenerPorId()`.
- Se valida `hotel_id` antes de modificar pagos o caja.
- `UPDATE reservaciones` usa `id + hotel_id`.
- `DELETE` de `reservacion_pagos` usa `reservacion_id + hotel_id`.
- `INSERT` en `reservacion_pagos` incluye `hotel_id`.
- `SELECT/DELETE/INSERT` en `movimientos_caja` filtra o escribe `hotel_id`.
- Se valida corte activo y relaciones con `cortes_caja`/`cajas` por hotel.
- `usuarios` queda como dato auxiliar.
- La logica original del cambio de metodo de pago se conserva.

## Archivo modificado

- `src/app/controllers/ReservacionController.php`

## Que NO se toco

- `cancelarAction()`.
- `modificarDiasAction()`.
- `Reservacion::cancelar()`.
- `Reservacion::checkInConPagosMixtos()`.
- `crearSolicitudFactura()`.
- `obtenerSolicitudFactura()`.
- `solicitudes_factura`.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- base de datos.

## Validaciones realizadas

- `php -l` en `ReservacionController.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- No se hizo POST funcional porque modificaria pagos/caja sin rollback seguro.
- Confirmacion de 0 nuevos `hotel_id` NULL segun herramientas.
- Confirmacion de que no se tocaron cancelaciones, modificar dias, factura, PWA/Sync/APIs.

## Riesgos pendientes

- `Reservacion::checkInConPagosMixtos()` todavia inserta `movimientos_caja` sin `hotel_id`.
- `cancelarAction()` y `Reservacion::cancelar()` siguen pendientes.
- `modificarDiasAction()` sigue pendiente.
- `solicitudes_factura` sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-C-A: auditoria/preparacion de `Reservacion::checkInConPagosMixtos()` para corregir insercion de `movimientos_caja` con `hotel_id`, sin tocar todavia cancelaciones ni modificar dias.
