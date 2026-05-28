# Reservaciones 1-F-F-E-E-E-A - Auditoria de pagos, factura y caja relacionados con reservaciones

## Objetivo

Auditar pagos, facturacion y caja relacionados con reservaciones y documentos para detectar consultas globales y preparar una integracion segura por `hotel_id`.

## Hallazgo principal

- Los metodos base de Caja y MovimientoCaja ya tienen buen scope por `hotel_id`.
- Aun hay flujos de reservacion que crean, modifican o consultan pagos/factura/caja de forma global.
- Los riesgos principales estan en ReservacionController y metodos legacy de Reservacion.

## Archivos encontrados

- `src/app/controllers/ReservacionController.php`
- `src/app/models/Reservacion.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`

## Metodos y zonas auditadas

- `ReservacionController::verAction()`
- `ReservacionController::checkInAction()`
- `ReservacionController::cancelarAction()`
- `ReservacionController::cambiarMetodoPagoAction()`
- `ReservacionController::modificarDiasAction()`
- `Reservacion::registrarPagosMixtos()`
- `Reservacion::obtenerPagos()`
- `Reservacion::resumenPagosPorMetodo()`
- `Reservacion::checkInConPagosMixtos()`
- `Reservacion::cancelar()`
- `Reservacion::crearSolicitudFactura()`
- `Reservacion::obtenerSolicitudFactura()`
- `Reservacion::obtenerSolicitudesPendientes()`
- `Reservacion::actualizarSolicitudFactura()`
- Caja methods revisados
- `MovimientoCaja::registrarMovimiento()`

## Riesgos por metodo

### Alto

- `ReservacionController::cancelarAction()`
- `ReservacionController::cambiarMetodoPagoAction()`
- `ReservacionController::modificarDiasAction()`
- `Reservacion::checkInConPagosMixtos()`
- `Reservacion::cancelar()`
- `Reservacion::crearSolicitudFactura()`
- `Reservacion::obtenerSolicitudesPendientes()`
- `Reservacion::actualizarSolicitudFactura()`

### Medio

- `ReservacionController::checkInAction()`
- `Reservacion::obtenerPagos()`
- `Reservacion::resumenPagosPorMetodo()`
- `Reservacion::obtenerSolicitudFactura()`

### Bajo

- `ReservacionController::verAction()`
- `Reservacion::registrarPagosMixtos()`
- Caja methods ya scoped
- `MovimientoCaja::registrarMovimiento()`

## Consultas globales clave

### `cambiarMetodoPagoAction()`

- `Reservacion::find($id)` global.
- `UPDATE reservaciones WHERE id = ?`.
- `DELETE FROM reservacion_pagos WHERE reservacion_id = ?`.
- `INSERT INTO reservacion_pagos` sin `hotel_id`.
- `SELECT/DELETE/INSERT movimientos_caja` sin `hotel_id`.

### `Reservacion::checkInConPagosMixtos()`

- Valida reservacion y habitaciones por hotel.
- Inserta `movimientos_caja` sin `hotel_id`.

### `Reservacion::crearSolicitudFactura()`

- Inserta `solicitudes_factura` sin `hotel_id`.

### `Reservacion::cancelar()`

- Consulta e inserta `movimientos_caja` sin `hotel_id`.
- Actualiza `solicitudes_factura` por `reservacion_id` sin `hotel_id`.

## Joins y validaciones requeridas

- `reservaciones.hotel_id = ?`
- `reservacion_habitaciones.hotel_id = reservaciones.hotel_id`
- `habitaciones.hotel_id = reservacion_habitaciones.hotel_id`
- `reservacion_pagos.hotel_id = reservaciones.hotel_id`
- `solicitudes_factura.hotel_id = reservaciones.hotel_id`
- `movimientos_caja.hotel_id = reservaciones.hotel_id` o `movimientos_caja.hotel_id = ?`
- `cortes_caja.hotel_id = movimientos_caja.hotel_id`
- `cajas.hotel_id = cortes_caja.hotel_id`
- `huespedes` y `usuarios` como joins auxiliares, no como fuente de scope.

## Que implementar primero

Reservaciones 1-F-F-E-E-E-B: pagos de reservacion scoped, empezando por `ReservacionController::cambiarMetodoPagoAction()`.

Motivo:

`cambiarMetodoPagoAction()` concentra cambios financieros directos en `reservaciones`, `reservacion_pagos` y `movimientos_caja`, y hoy puede alterar o crear datos sin `hotel_id`.

## Que implementar despues

- Corregir insert de `movimientos_caja` en `Reservacion::checkInConPagosMixtos()`.
- `solicitudes_factura`:
  - `crearSolicitudFactura()`
  - `obtenerSolicitudFactura()`
  - pendientes
  - updates
- `cancelarAction()` / `Reservacion::cancelar()`
- `modificarDiasAction()`

## Que dejar fuera

- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.
- Usuarios tenant.

## Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Pruebas controladas de pagos/factura/caja solo en entorno seguro o con rollback.
- Confirmar que PWA/Sync/APIs no se tocaron.
- Confirmar 0 nuevos `hotel_id` NULL.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-B: implementar unicamente `ReservacionController::cambiarMetodoPagoAction()` scoped por `hotel_id`, sin tocar todavia cancelaciones, modificar dias, `solicitudes_factura` ni `checkInConPagosMixtos()`.
