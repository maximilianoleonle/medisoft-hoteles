# Reservaciones 1-F-F-D-D-C-A: Auditoria de estancia avanzada

## Objetivo

Auditar los reportes de estancia avanzada antes de hacerlos tenant-aware.

## Hallazgo principal

- Estancia avanzada esta parcialmente scoped.
- `obtenerPromedioEstancia()`, `obtenerDistribucionEstancia()` y `obtenerTendenciaEstancia()` ya filtran por `hotel_id`.
- Los pendientes reales son `obtenerEstanciaPorTipo()` y `obtenerEstanciaPorProcedencia()`.
- No existe vista fisica `src/app/views/reportes/estancia.php`, aunque `estanciaAction()` intenta renderizar `reportes/estancia`.

## Archivos encontrados

- `src/app/models/Reporte.php`
- `src/app/controllers/ReportesController.php`
- Vistas de reportes disponibles relacionadas.

## Metodos auditados

- `obtenerPromedioEstancia()`
- `obtenerDistribucionEstancia()`
- `obtenerTendenciaEstancia()`
- `obtenerEstanciaPorTipo()`
- `obtenerEstanciaPorProcedencia()`
- `estanciaAction()`

## Estado por metodo

- `obtenerPromedioEstancia()`: ya scoped por `hotel_id`, riesgo bajo.
- `obtenerDistribucionEstancia()`: ya scoped por `hotel_id`, riesgo bajo.
- `obtenerTendenciaEstancia()`: ya scoped por `hotel_id`, riesgo bajo.
- `obtenerEstanciaPorTipo()`: global, riesgo alto.
- `obtenerEstanciaPorProcedencia()`: global, riesgo medio/alto.
- `estanciaAction()`: mixto, riesgo medio.

## Consultas criticas

Para `obtenerEstanciaPorTipo()`:

- Agregar `r.hotel_id = ?`.
- Validar `rh.hotel_id = r.hotel_id`.
- Validar `h.hotel_id = rh.hotel_id`.

Para `obtenerEstanciaPorProcedencia()`:

- Agregar `r.hotel_id = ?`.
- `huespedes` debe quedar como join auxiliar por `r.huesped_id = h.id`, no como fuente de scope.

## Que implementar primero

Reservaciones 1-F-F-D-D-C-B:

- `obtenerEstanciaPorTipo()` scoped por `hotel_id`.

## Que dejar para despues

Reservaciones 1-F-F-D-D-C-C:

- `obtenerEstanciaPorProcedencia()` scoped por `hotel_id`, manteniendo `huespedes` como auxiliar.

## Que dejar fuera

- Ranking completo.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.

## Pruebas necesarias

- `php -l src/app/models/Reporte.php`.
- `php -l src/app/controllers/ReportesController.php` si se toca.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/reportes/estancia`, documentando si falla por vista faltante.
- Validacion read-only de totales por `reservaciones.hotel_id`.
- Confirmar que no se tocaron PDFs/PWA/Sync/APIs.
- Confirmar 0 nuevos `hotel_id` NULL.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-C-B: implementar unicamente `obtenerEstanciaPorTipo()` scoped por `hotel_id`.
