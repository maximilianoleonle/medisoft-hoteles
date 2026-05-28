# Reservaciones 1-F-F-D-D-D-D-B - Cierre técnico de comparativa de estados

## Objetivo

Documentar el cierre técnico del scope por `hotel_id` en `obtenerComparativaEstados()`.

## Qué se logró

- `obtenerComparativaEstados()` ahora usa `hotelIdActual()`.
- La lista base de estados ya no sale de `huespedes` global.
- La lista base se deriva de reservaciones scoped del período actual y anterior con `UNION`.
- El período actual filtra reservaciones con `r.hotel_id = ?`.
- El período anterior filtra reservaciones con `r.hotel_id = ?`.
- `huespedes` queda como join auxiliar por `r.huesped_id = h.id`.
- `huespedes` no se usa como fuente global de scope.
- Se conserva la fórmula original de variación.
- Se conserva el manejo cuando anterior = 0.
- Se evita comparar un período scoped contra otro global.

## Archivo modificado

- `src/app/models/Reporte.php`

## Qué NO se tocó

- `obtenerRankingEstados()`.
- `obtenerEvolucionEstados()`.
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
- Confirmación de que lista base, actual y anterior usan `hotel_id`.
- Confirmación de que `huespedes` solo queda como join auxiliar.
- Confirmación de que no se tocaron PDFs/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL según herramientas.

## Riesgos pendientes

- Vista física `ranking-estados.php` no existe.
- PDFs/exportaciones siguen pendientes.
- `huespedes` sigue sin modelo tenant.
- Reportes PDF futuros deben consumir datos ya scoped.
- APIs/PWA/Sync siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-D-E-CIERRE: cierre general del bloque ranking/procedencia/estancia histórica, o bien iniciar auditoría de PDFs/exportaciones si ya no quedan reportes históricos pendientes.
