# Reservaciones 1-F-F-D-D-C-C - Cierre técnico de estancia por procedencia

## Objetivo

Documentar el cierre técnico del scope por `hotel_id` en el reporte de estancia por procedencia.

## Qué se logró

- `obtenerEstanciaPorProcedencia()` ahora usa `hotelIdActual()`.
- La consulta filtra reservaciones con `r.hotel_id = ?`.
- `huespedes` queda como join auxiliar por `r.huesped_id = h.id`.
- `huespedes` no se usa como fuente de scope.
- El reporte conserva su lógica original.
- El reporte ahora usa únicamente reservaciones del hotel actual.

## Archivo modificado

- `src/app/models/Reporte.php`

## Qué NO se tocó

- `obtenerRankingEstados()`.
- `obtenerEvolucionEstados()`.
- `obtenerComparativaEstados()`.
- Ranking completo.
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
- Confirmación de que no se tocó ranking.
- Confirmación de que no se tocaron PDFs/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL según herramientas.

## Riesgos pendientes

- Ranking completo sigue global.
- PDFs/exportaciones siguen pendientes.
- Huéspedes sigue sin modelo tenant.
- Vistas históricas pueden requerir revisión posterior.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-D-A: auditoría/propuesta para ranking de estados scoped por `hotel_id`.
