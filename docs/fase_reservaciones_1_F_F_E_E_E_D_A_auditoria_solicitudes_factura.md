# Reservaciones 1-F-F-E-E-E-D-A - Auditoria de solicitudes_factura

## Objetivo

Auditar el flujo de `solicitudes_factura` relacionadas con reservaciones para detectar consultas globales y preparar una correccion segura por `hotel_id`.

## Hallazgo principal

- El riesgo principal esta en el flujo que crea y administra `solicitudes_factura`.
- `ReservacionController::procesarSolicitudFactura()` delega en `Reservacion::crearSolicitudFactura()`.
- `Reservacion::crearSolicitudFactura()` todavia inserta en `solicitudes_factura` sin `hotel_id`.
- Tampoco valida la reservacion contra el hotel actual antes de crear la solicitud.
- `FacturacionController` lista, consulta, actualiza y calcula estadisticas de facturacion con consultas globales.

## Archivos encontrados

- `src/app/controllers/ReservacionController.php`
- `src/app/controllers/FacturacionController.php`
- `src/app/models/Reservacion.php`
- `src/app/views/facturacion/index.php`
- `src/app/views/facturacion/detalle.php`
- `src/config/routes.php`

## Metodos auditados

- `ReservacionController::procesarSolicitudFactura()`
- `Reservacion::crearSolicitudFactura()`
- `Reservacion::obtenerSolicitudFactura()`
- `Reservacion::obtenerSolicitudesPendientes()`
- `Reservacion::actualizarSolicitudFactura()`
- `Reservacion::cancelar()`, solo bloque factura
- `FacturacionController::indexAction()`
- `FacturacionController::verAction()`
- `FacturacionController::obtenerSolicitudCompleta()`
- `FacturacionController::obtenerEstadisticas()`

## Riesgos por metodo

### Alto

- `ReservacionController::procesarSolicitudFactura()`
- `Reservacion::crearSolicitudFactura()`
- `Reservacion::obtenerSolicitudesPendientes()`
- `Reservacion::actualizarSolicitudFactura()`
- `Reservacion::cancelar()`, bloque factura
- `FacturacionController::indexAction()`
- `FacturacionController::obtenerSolicitudCompleta()`
- `FacturacionController::obtenerEstadisticas()`

### Medio

- `Reservacion::obtenerSolicitudFactura()`
- `FacturacionController::verAction()`

### Bajo

- Exportaciones PDF/Excel de reservaciones que ya validan `solicitudes_factura` con `hotel_id`.

## Consultas globales detectadas

- `INSERT INTO solicitudes_factura` sin `hotel_id`.
- `SELECT * FROM solicitudes_factura WHERE reservacion_id = ?`.
- `UPDATE solicitudes_factura WHERE id = ?`.
- `UPDATE solicitudes_factura WHERE reservacion_id = ?`.
- Listados y estadisticas de `FacturacionController` sin `sf.hotel_id`.
- Joins `sf.reservacion_id = r.id` sin validar `sf.hotel_id = r.hotel_id`.

## Validaciones requeridas

- `solicitudes_factura.hotel_id = reservaciones.hotel_id`.
- `reservaciones.hotel_id = ?`.
- Para inserts: obtener/validar la reservacion scoped antes de crear la solicitud y escribir ese mismo `hotel_id`.
- Para selects/updates: filtrar por `sf.hotel_id = ?`.
- Cuando haya join con reservaciones: agregar `sf.hotel_id = r.hotel_id`.
- `huespedes` y `usuarios` deben seguir como auxiliares, no como fuente de scope.

## Que implementar primero

Reservaciones 1-F-F-E-E-E-D-B:

- `procesarSolicitudFactura()`
- `Reservacion::crearSolicitudFactura()`

Motivo:

Es el punto de creacion. Si no escribe `hotel_id`, puede generar nuevas solicitudes mal scoped aunque pagos/caja ya esten corregidos.

## Que debe esperar

- `Reservacion::obtenerSolicitudFactura()`.
- `Reservacion::obtenerSolicitudesPendientes()`.
- `Reservacion::actualizarSolicitudFactura()`.
- `FacturacionController` completo.
- Cancelaciones y `Reservacion::cancelar()`.
- Modificar dias.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.

## Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Pruebas controladas solo con rollback seguro o entorno de prueba.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que no se tocaron cancelaciones, modificar dias, pagos/caja no relacionados, PWA/Sync/APIs.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-B: implementar unicamente `procesarSolicitudFactura()` y `Reservacion::crearSolicitudFactura()` scoped por `hotel_id`, sin tocar todavia `FacturacionController`, cancelaciones ni modificar dias.
