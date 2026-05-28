# Reservaciones 1-F-F-D-D-C-B: Cierre de estancia por tipo scoped

## Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en el reporte de estancia por tipo.

## Que se logro

- `obtenerEstanciaPorTipo()` ahora usa `hotelIdActual()`.
- La consulta filtra reservaciones con `r.hotel_id = ?`.
- La consulta valida `reservacion_habitaciones` con `rh.hotel_id = r.hotel_id`.
- La consulta valida habitaciones con `h.hotel_id = rh.hotel_id`.
- El reporte conserva su logica original: promedio de estancia y total de reservaciones por tipo.
- El reporte ahora usa unicamente datos del hotel actual.

## Archivo modificado

- `src/app/models/Reporte.php`

## Que NO se toco

- `obtenerEstanciaPorProcedencia()`.
- Ranking completo.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Huespedes tenant.
- Migraciones.
- Schema.
- Base de datos.

## Validaciones realizadas

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Confirmacion de que no se toco `obtenerEstanciaPorProcedencia()`.
- Confirmacion de que no se tocaron ranking/PDFs/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## Riesgos pendientes

- `obtenerEstanciaPorProcedencia()` sigue global.
- Ranking completo sigue pendiente.
- PDFs/exportaciones siguen pendientes.
- Huespedes sigue sin modelo tenant.
- La vista fisica `reportes/estancia.php` no fue encontrada durante auditoria previa.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-C-C: implementar `obtenerEstanciaPorProcedencia()` scoped por `hotel_id`, manteniendo `huespedes` como join auxiliar y sin tocar ranking ni PDFs/exportaciones.
