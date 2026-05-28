# Reservaciones 1-F-F-E-E-E-E-A

## Auditoria de cancelaciones de reservacion

## 1. Objetivo

Auditar el flujo de cancelaciones de reservacion para detectar consultas globales, movimientos de caja sin `hotel_id`, `solicitudes_factura` sin scope y actualizaciones que puedan afectar otro hotel.

## 2. Hallazgo principal

- `ReservacionController::cancelarAction()` carga la reservacion con `find($id)`, que es global.
- `Reservacion::cancelar()` vuelve a cargar la reservacion con `find($id)`, tambien global.
- `Reservacion::cancelar()` actualiza `reservaciones`, `habitaciones`, `movimientos_caja` y `solicitudes_factura` sin filtrar por `hotel_id`.
- Caja base ya esta mejor scoped: `Caja::obtenerCorteActual()` valida `cortes_caja.hotel_id` y `cajas.hotel_id`.
- El problema es que la cancelacion no propaga ni valida ese `hotel_id` contra la reservacion.

## 3. Archivos encontrados

- `src/app/controllers/ReservacionController.php`
- `src/app/models/Reservacion.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`
- `src/app/views/reservaciones/ver.php`
- `src/config/routes.php`

## 4. Flujo actual

- `POST /reservaciones/cancelar/{id}` llega a `ReservacionController::cancelarAction()`.
- La vista `reservaciones/ver.php` envia CSRF y `razon_cancelacion`.
- El controller valida POST, CSRF y razon.
- Carga la reservacion con `Reservacion::find($id)`, global.
- Si esta `checked_in` y tiene pago, valida caja abierta con `Caja::obtenerCorteActual()`, ya scoped.
- Consulta `movimientos_caja` por `reservacion_id` sin `hotel_id` para detectar pagos en cortes distintos.
- Llama `Reservacion::cancelar($id, $razon)`.
- El modelo vuelve a cargar con `find($id)`, global.
- Inserta devoluciones en `movimientos_caja` sin `hotel_id`.
- Libera habitaciones con update sin validar `rh.hotel_id` ni `h.hotel_id`.
- Actualiza `reservaciones` por id sin `hotel_id`.
- Cancela `solicitudes_factura` por `reservacion_id` sin `hotel_id`.

## 5. Metodos auditados

- `ReservacionController::cancelarAction()`
- `Reservacion::cancelar()`
- `Caja::obtenerCorteActual()`
- `MovimientoCaja::registrarMovimiento()`
- `Reservacion::devolverInventarioCancelacion()`
- `reservaciones/ver.php`
- Ruta `POST /reservaciones/cancelar/{id}`

## 6. Estado por metodo

- `ReservacionController::cancelarAction()`: riesgo alto; usa `find()` global y consulta `movimientos_caja` por `reservacion_id` sin `hotel_id`.
- `Reservacion::cancelar()`: riesgo alto; usa `find()` global, actualiza varias tablas y crea movimientos sin `hotel_id`.
- `Caja::obtenerCorteActual()`: riesgo bajo; ya scoped.
- `MovimientoCaja::registrarMovimiento()`: riesgo bajo; patron correcto, escribe `hotel_id`, pero no lo usa cancelacion.
- `Reservacion::devolverInventarioCancelacion()`: riesgo medio; mayormente scoped, colateral del flujo.
- `reservaciones/ver.php`: riesgo bajo; render/formulario.
- `POST /reservaciones/cancelar/{id}`: riesgo medio; expone accion por ID.

## 7. Scoped ya

- `Caja::obtenerCorteActual()` filtra `cc.hotel_id = ?` y valida `cajas.hotel_id = cortes_caja.hotel_id`.
- `MovimientoCaja::registrarMovimiento()` usa `hotelIdActual()`, valida reservacion por id + `hotel_id` y escribe `movimientos_caja.hotel_id`.
- `Reservacion::devolverInventarioCancelacion()` valida `movimientos_inventario.hotel_id`, productos y habitaciones de inventario por hotel.

## 8. Global pendiente

- `ReservacionController::cancelarAction()`: `Reservacion::find($id)` global.
- `ReservacionController::cancelarAction()`: `SELECT COUNT(*) FROM movimientos_caja WHERE reservacion_id = ?` sin `hotel_id`.
- `Reservacion::cancelar()`: `find($id)` global.
- `Reservacion::cancelar()`: `SELECT movimientos_caja WHERE reservacion_id = ?` sin `hotel_id`.
- `Reservacion::cancelar()`: `INSERT INTO movimientos_caja` sin `hotel_id`.
- `Reservacion::cancelar()`: `UPDATE habitaciones` sin joins scoped por hotel.
- `Reservacion::cancelar()`: `UPDATE reservaciones WHERE id = ?` sin `hotel_id`.
- `Reservacion::cancelar()`: `UPDATE solicitudes_factura WHERE reservacion_id = ?` sin `hotel_id`.

## 9. Validaciones necesarias

- `reservaciones.hotel_id = ?`
- `reservacion_habitaciones.hotel_id = reservaciones.hotel_id`
- `habitaciones.hotel_id = reservacion_habitaciones.hotel_id`
- `movimientos_caja.hotel_id = reservaciones.hotel_id`
- `cortes_caja.hotel_id = movimientos_caja.hotel_id`
- `solicitudes_factura.hotel_id = reservaciones.hotel_id`
- `huespedes` y `usuarios` como auxiliares, no como fuente de scope.

## 10. Que implementar primero

Reservaciones 1-F-F-E-E-E-E-B:

- `ReservacionController::cancelarAction()` scoped/defensivo.

Motivo:

Es la puerta de entrada. Debe cargar la reservacion con `obtenerPorId()` o query id + `hotel_id`, validar caja/corte del hotel actual y pasar solo una reservacion validada al modelo.

## 11. Que implementar despues

- Reservaciones 1-F-F-E-E-E-E-C: `Reservacion::cancelar()` scoped por `hotel_id`.
- Reservaciones 1-F-F-E-E-E-E-D: `movimientos_caja` y `solicitudes_factura` derivados de cancelacion scoped.
- Fase separada para `modificarDiasAction()`.
- Fase separada para PWA/Sync/API.

## 12. Que dejar fuera

- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.
- Usuarios tenant.

## 13. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Pruebas de cancelacion solo con rollback seguro o entorno controlado.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que PWA/Sync/APIs no se tocaron.

## 14. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-E-B: implementar unicamente `ReservacionController::cancelarAction()` scoped/defensivo, sin tocar todavia `Reservacion::cancelar()` ni `modificarDiasAction()`.
