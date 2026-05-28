# Reservaciones 1-F-F-E-E-E-D-D-D-C-A - Auditoria de detalle y pagos de facturacion

## 1. Objetivo

Auditar el detalle de facturacion y los pagos mostrados en `FacturacionController::verAction()`, para preparar una correccion segura por `hotel_id`.

## 2. Hallazgo principal

- El riesgo principal esta en `FacturacionController::obtenerSolicitudCompleta()`.
- `obtenerSolicitudCompleta()` consulta `solicitudes_factura` por `sf.id = ?` sin filtrar `sf.hotel_id`.
- El join con `reservaciones` no valida `sf.hotel_id = r.hotel_id`.
- `FacturacionController::verAction()` depende de ese detalle global.
- `verAction()` ademas consulta pagos en `movimientos_caja` por `reservacion_id` sin `hotel_id`.

## 3. Metodos auditados

- `FacturacionController::obtenerSolicitudCompleta()`.
- `FacturacionController::verAction()`.
- `Reservacion::getHabitaciones()`, consumido por `verAction()`.
- Vista `facturacion/detalle.php`.
- Ruta `/facturacion/ver/{id}`.

## 4. Estado por metodo

- `obtenerSolicitudCompleta()`: riesgo alto, detalle global por `sf.id`.
- `verAction()`: riesgo medio, pagos globales por `reservacion_id`.
- `Reservacion::getHabitaciones()`: riesgo bajo, ya tratado como scoped en fases previas.
- `facturacion/detalle.php`: riesgo bajo, render-only.
- Ruta `/facturacion/ver/{id}`: riesgo medio, expone detalle por id.

## 5. Consultas globales pendientes

`obtenerSolicitudCompleta()` debe agregar:

- `sf.hotel_id = ?`.
- `sf.hotel_id = r.hotel_id`.
- `r.hotel_id = ?`.

`verAction()` debe filtrar pagos con:

- `movimientos_caja.hotel_id = ?`.

## 6. Validaciones requeridas

- `sf.hotel_id = ?`.
- `sf.reservacion_id = r.id`.
- `sf.hotel_id = r.hotel_id`.
- `r.hotel_id = ?`.
- `movimientos_caja.hotel_id = ?`.
- `huespedes` y `usuarios` deben quedar como auxiliares, no como fuente de scope.

## 7. Riesgos

Alto:

- `obtenerSolicitudCompleta()`, porque puede cargar una solicitud de otro hotel por id.

Medio:

- `verAction()`, porque los pagos desde `movimientos_caja` pueden mezclarse por `reservacion_id` si no se filtra `hotel_id`.
- Ruta `/facturacion/ver/{id}`, porque expone detalle por id.

Bajo:

- `Reservacion::getHabitaciones()`, porque ya esta scoped.
- `facturacion/detalle.php`, porque solo renderiza datos recibidos.

## 8. Que implementar primero

Reservaciones 1-F-F-E-E-E-D-D-D-C-B:

- `FacturacionController::obtenerSolicitudCompleta()` scoped por `hotel_id`.

## 9. Que implementar despues

Reservaciones 1-F-F-E-E-E-D-D-D-C-C:

- `FacturacionController::verAction()` pagos scoped con `movimientos_caja.hotel_id = ?`.

## 10. Que dejar fuera

- `obtenerEstadisticas()`.
- `guardarAction()`.
- `completarAction()`.
- `enProcesoAction()`.
- `cancelarAction()`.
- Cancelaciones de reservacion.
- Modificar dias.
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
- GET controlado de `/facturacion/ver/{id}` si aplica.
- Confirmar que PWA/Sync/APIs no se tocaron.
- Confirmar 0 nuevos `hotel_id` NULL.

## 12. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-C-B: implementar unicamente `FacturacionController::obtenerSolicitudCompleta()` scoped por `hotel_id`, sin tocar todavia pagos de `verAction()` ni estadisticas.
