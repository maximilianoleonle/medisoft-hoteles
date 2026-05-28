# Reservaciones 1-F-F-D-D-D-B - Cierre técnico de ranking de estados

## Objetivo

Documentar el cierre técnico del scope por `hotel_id` en `obtenerRankingEstados()`.

## Qué se logró

- `obtenerRankingEstados()` ahora usa `hotelIdActual()`.
- La consulta principal filtra reservaciones con `r.hotel_id = ?`.
- `huespedes` queda como join auxiliar por `h.id = r.huesped_id`.
- `huespedes` no se usa como fuente de scope.
- La subconsulta de `porcentaje_del_total` filtra por el mismo `hotel_id`.
- Se evita que el numerador quede scoped y el denominador global.
- Se conserva la lógica original del reporte.

## Archivo modificado

- `src/app/models/Reporte.php`

## Qué NO se tocó

- `obtenerEvolucionEstados()`.
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
- Confirmación de que la consulta principal usa `hotel_id`.
- Confirmación de que la subconsulta de porcentaje usa `hotel_id`.
- Confirmación de que no se tocaron evolución/comparativa/PDFs/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL según herramientas.

## Riesgos pendientes

- `obtenerEvolucionEstados()` sigue global.
- `obtenerComparativaEstados()` sigue global.
- Vista física `ranking-estados.php` no existe.
- PDFs/exportaciones siguen pendientes.
- `huespedes` sigue sin modelo tenant.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-D-C: implementar `obtenerEvolucionEstados()` scoped por `hotel_id`, sin tocar comparativa ni PDFs/exportaciones.
