# Reservaciones 1-F-F-D-D-D-C - Cierre técnico de evolución de estados

## Objetivo

Documentar el cierre técnico del scope por `hotel_id` en `obtenerEvolucionEstados()`.

## Qué se logró

- `obtenerEvolucionEstados()` ahora usa `hotelIdActual()`.
- La consulta filtra reservaciones con `r.hotel_id = ?`.
- `huespedes` queda como join auxiliar por `r.huesped_id = h.id`.
- `huespedes` no se usa como fuente de scope.
- Se conserva la lógica original de evolución mensual por estado.
- El reporte ahora usa únicamente reservaciones del hotel actual.

## Archivo modificado

- `src/app/models/Reporte.php`

## Qué NO se tocó

- `obtenerComparativaEstados()`.
- `rankingEstadosAction()`.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- huéspedes tenant.
- migraciones.
- schema.
- base de datos.

## Validaciones realizadas

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Confirmación de que no se tocó comparativa.
- Confirmación de que no se tocaron PDFs/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL según herramientas.

## Riesgos pendientes

- `obtenerComparativaEstados()` sigue global.
- Vista física `ranking-estados.php` no existe.
- PDFs/exportaciones siguen pendientes.
- `huespedes` sigue sin modelo tenant.
- La comparativa actual/anterior requiere cuidado porque tiene subconsultas delicadas.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-D-D-A: auditoría/propuesta para `obtenerComparativaEstados()` scoped por `hotel_id` antes de implementar.
