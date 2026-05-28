# Reservaciones 1-F-F-E-E-E-D-D-C - Cierre de actualizacion de solicitud de factura

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `Reservacion::actualizarSolicitudFactura()`.

## 2. Que se logro

- `actualizarSolicitudFactura()` ahora usa `hotelIdActual()`.
- Valida que la solicitud pertenezca al hotel actual antes de actualizar.
- Valida `sf.reservacion_id = r.id`.
- Valida `sf.hotel_id = r.hotel_id`.
- Valida `sf.hotel_id = ?`.
- Valida `r.hotel_id = ?`.
- El `UPDATE` de `solicitudes_factura` ahora usa `WHERE id = ? AND hotel_id = ?`.
- La actualizacion de solicitud de factura ya queda tenant-aware.
- Se conserva la logica original de actualizacion.

## 3. Archivo modificado

- `src/app/models/Reservacion.php`

## 4. Que NO se toco

- `obtenerSolicitudesPendientes()`.
- `obtenerSolicitudFactura()`.
- `crearSolicitudFactura()`.
- `FacturacionController`.
- `cancelarAction()`.
- `Reservacion::cancelar()`.
- `modificarDiasAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.
- Base de datos.

## 5. Validaciones realizadas

- `php -l` en `Reservacion.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- No se hizo prueba funcional con escritura porque actualizar una solicitud modificaria base sin rollback seguro autorizado.
- Confirmacion de que no se tocaron `FacturacionController`, cancelaciones, modificar dias, PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- `FacturacionController` sigue global en listados, detalle, estadisticas y pagos.
- Cancelaciones y `Reservacion::cancelar()` siguen pendientes.
- Modificar dias sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-D-A: auditoria/preparacion de `FacturacionController` listado/detalle/estadisticas/pagos scoped por `hotel_id`, sin implementacion directa.
