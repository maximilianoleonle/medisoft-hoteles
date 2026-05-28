# Reservaciones 1-F-F-E-E-E-D-D-B - Cierre de solicitudes de factura pendientes

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `Reservacion::obtenerSolicitudesPendientes()`.

## 2. Que se logro

- `obtenerSolicitudesPendientes()` ahora usa `hotelIdActual()`.
- El listado filtra `solicitudes_factura` con `sf.hotel_id = ?`.
- Se valida `sf.reservacion_id = r.id`.
- Se valida `sf.hotel_id = r.hotel_id`.
- Se valida `r.hotel_id = ?`.
- Se conserva el filtro opcional por `tipo`.
- `huespedes` queda como join auxiliar, no como fuente de scope.
- La logica original del listado se conserva.

## 3. Archivo modificado

- `src/app/models/Reservacion.php`

## 4. Que NO se toco

- `actualizarSolicitudFactura()`.
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
- Pruebas sin escritura mediante lint, diff y herramientas SaaS.
- Confirmacion de que no se tocaron `FacturacionController`, updates, cancelaciones, modificar dias, PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- `actualizarSolicitudFactura()` sigue pendiente.
- `FacturacionController` sigue global en listados, detalle, estadisticas y pagos.
- Cancelaciones y `Reservacion::cancelar()` siguen pendientes.
- Modificar dias sigue pendiente.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-E-D-D-C: implementar unicamente `Reservacion::actualizarSolicitudFactura()` scoped por `hotel_id`, sin tocar todavia `FacturacionController` ni cancelaciones.
