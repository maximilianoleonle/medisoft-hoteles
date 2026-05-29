# Reservaciones 1-F-F-E-E-E-E-C-A

## Auditoria de Reservacion::cancelar()

## 1. Objetivo

Auditar `Reservacion::cancelar()` para preparar una correccion segura por `hotel_id` en cancelaciones de reservacion.

## 2. Hallazgo principal

- `Reservacion::cancelar()` sigue siendo el nucleo pendiente de alto riesgo.
- Aunque `ReservacionController::cancelarAction()` ya valida `hotel_id`, el modelo vuelve a cargar la reservacion con `find($id)`, que es global.
- Despues ejecuta operaciones financieras, de habitaciones y factura sin filtrar por `hotel_id`.

## 3. Archivos encontrados

- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`

## 4. Flujo actual

- `ReservacionController::cancelarAction()` ya carga con `obtenerPorId()` y valida hotel.
- Luego llama `Reservacion::cancelar($id, $razon)`.
- `Reservacion::cancelar()` inicia transaccion.
- Vuelve a cargar con `find($id)`, global.
- Si estaba `checked_in`, obtiene corte actual con `Caja::obtenerCorteActual()`.
- Consulta ingresos en `movimientos_caja` por `reservacion_id` sin `hotel_id`.
- Inserta egresos/devoluciones en `movimientos_caja` sin `hotel_id`.
- Libera habitaciones con `UPDATE` sin validar `rh.hotel_id` ni `h.hotel_id`.
- Devuelve inventario con `devolverInventarioCancelacion()`, mayormente scoped.
- Actualiza `reservaciones` por id sin `hotel_id`.
- Actualiza `solicitudes_factura` por `reservacion_id` sin `hotel_id`.

## 5. Bloques auditados

- Carga inicial en `Reservacion::cancelar()`.
- Corte actual.
- Consulta de ingresos.
- Categoria devolucion.
- Devoluciones caja.
- Liberar habitaciones.
- Devolver inventario.
- Cancelar reservacion.
- Cancelar facturas.

## 6. Riesgos por bloque

Alto:

- Carga inicial con `find($id)`.
- Consulta de ingresos por `reservacion_id` sin `hotel_id`.
- `INSERT` de devoluciones en `movimientos_caja` sin `hotel_id`.
- Liberar habitaciones sin joins scoped.
- `UPDATE reservaciones WHERE id = ?`.
- `UPDATE solicitudes_factura WHERE reservacion_id = ?`.

Medio:

- Categoria devolucion como catalogo global auxiliar.
- `devolverInventarioCancelacion()` como flujo colateral.

Bajo:

- `Caja::obtenerCorteActual()`, ya scoped.
- `MovimientoCaja::registrarMovimiento()`, patron correcto aunque no usado aqui.

## 7. Validaciones necesarias

- `reservaciones.hotel_id = ?`
- `reservacion_habitaciones.hotel_id = reservaciones.hotel_id`
- `habitaciones.hotel_id = reservacion_habitaciones.hotel_id`
- `movimientos_caja.hotel_id = reservaciones.hotel_id`
- `solicitudes_factura.hotel_id = reservaciones.hotel_id`
- `usuarios` y `huespedes` como auxiliares, no como fuente de scope.

## 8. Que implementar primero

Reservaciones 1-F-F-E-E-E-E-C-B:

- Carga y validacion inicial de `Reservacion::cancelar()` scoped.

Debe reemplazar `find($id)` por una carga scoped con `hotelIdActual()`/`obtenerPorId()` o query id + `hotel_id`, antes de cualquier operacion.

## 9. Que implementar despues

- 1-F-F-E-E-E-E-C-C: `movimientos_caja` de devolucion scoped.
- 1-F-F-E-E-E-E-C-D: liberacion de habitaciones y update de reservacion scoped.
- 1-F-F-E-E-E-E-C-E: `solicitudes_factura` derivadas de cancelacion scoped.

## 10. Que dejar fuera

- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.
- Usuarios tenant.

## 11. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Pruebas de cancelacion solo con rollback seguro o entorno controlado.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que PWA/Sync/APIs no se tocaron.

## 12. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-E-C-B: implementar unicamente la carga y validacion inicial scoped de `Reservacion::cancelar()`, sin tocar todavia devoluciones, habitaciones ni `solicitudes_factura`.
