# Reservaciones 1-F-F-D-D-B: Cierre de procedencia avanzada scoped

## Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en procedencia avanzada.

## Que se logro

- `obtenerProcedenciaPorEstado()` ahora usa `hotelIdActual()`.
- La consulta filtra reservaciones con `r.hotel_id = ?`.
- La consulta valida `reservacion_habitaciones` con `rh.hotel_id = r.hotel_id`.
- `huespedes` queda como join auxiliar por `h.id = r.huesped_id`.
- El reporte de procedencia por estado ya usa unicamente reservaciones del hotel actual.

## Archivo modificado

- `src/app/models/Reporte.php`

## Que NO se toco

- `obtenerEstanciaPorTipo()`.
- `obtenerEstanciaPorProcedencia()`.
- `obtenerRankingEstados()`.
- `obtenerEvolucionEstados()`.
- `obtenerComparativaEstados()`.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.
- Huespedes tenant.

## Validaciones realizadas

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/reportes/procedencia` respondio sin error 500.
- Confirmacion de que no se tocaron estancia/ranking/PDFs/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## Riesgos pendientes

- Estancia avanzada sigue pendiente.
- Ranking completo sigue pendiente.
- PDFs/exportaciones siguen pendientes.
- Huespedes sigue sin modelo tenant.
- Vistas historicas pueden requerir revision posterior.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-C-A: auditoria/propuesta para estancia avanzada scoped por `hotel_id`.
