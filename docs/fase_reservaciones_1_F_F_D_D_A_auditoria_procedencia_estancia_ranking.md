# Reservaciones 1-F-F-D-D-A: Auditoria de procedencia, estancia y ranking

## Objetivo

Auditar reportes historicos avanzados restantes: procedencia, estancia y ranking de estados, antes de hacerlos tenant-aware.

## Hallazgo principal

- Los reportes de procedencia, estancia y ranking siguen usando consultas globales.
- Las consultas usan `reservaciones`, `reservacion_habitaciones`, `habitaciones` y `huespedes` sin `hotel_id`.
- `huespedes` debe tratarse como join auxiliar, no como fuente de scope, porque todavia no tiene modelo tenant.
- Existen rutas para ocupacion, estancia y ranking-estados, pero no se encontraron vistas fisicas `estancia.php`, `ranking-estados.php` ni `ocupacion.php`.

## Archivos encontrados

- `src/app/models/Reporte.php`
- `src/app/controllers/ReportesController.php`
- `src/app/views/reportes/procedencia.php`
- `src/app/views/reportes/habitaciones-rentables.php`
- `src/app/views/reportes/index.php`

## Metodos auditados

- `obtenerProcedenciaPorEstado()`
- `obtenerEstanciaPorTipo()`
- `obtenerEstanciaPorProcedencia()`
- `obtenerRankingEstados()`
- `obtenerEvolucionEstados()`
- `obtenerComparativaEstados()`
- `procedenciaAction()`
- `estanciaAction()`
- `rankingEstadosAction()`

## Consultas criticas

- Toda consulta con `reservaciones r` debe filtrar `r.hotel_id = ?`.
- Toda consulta con `reservacion_habitaciones rh` debe validar `rh.hotel_id = r.hotel_id`.
- Toda consulta con `habitaciones h` debe validar `h.hotel_id = rh.hotel_id` o `h.hotel_id = ?`.
- `huespedes` debe permanecer como join auxiliar por `h.id = r.huesped_id`.

## Riesgos por metodo

- `obtenerProcedenciaPorEstado()`: alto.
- `obtenerEstanciaPorTipo()`: alto.
- `obtenerEstanciaPorProcedencia()`: medio/alto.
- `obtenerRankingEstados()`: alto.
- `obtenerEvolucionEstados()`: alto.
- `obtenerComparativaEstados()`: alto.
- `procedenciaAction()`: medio/alto.
- `estanciaAction()`: medio.
- `rankingEstadosAction()`: alto.

## Que implementar primero

Reservaciones 1-F-F-D-D-B: procedencia avanzada scoped:

- `obtenerProcedenciaPorEstado()`.
- Revisar que `procedenciaAction()` use datos scoped.
- Mantener `huespedes` como auxiliar.

## Que dejar para fases posteriores

- Reservaciones 1-F-F-D-D-C: estancia avanzada scoped.
- Reservaciones 1-F-F-D-D-D: ranking de estados scoped.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.

## Pruebas necesarias

- `php -l` en `Reporte.php`.
- `php -l` en `ReportesController.php` si se toca.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/reportes/procedencia`.
- GET `/reportes/estancia`, documentando si falta vista.
- GET `/reportes/ranking-estados`, documentando si falta vista.
- Validacion read-only de totales por `reservaciones.hotel_id`.
- Confirmar que no se tocaron PDFs/PWA/Sync/APIs.
- Confirmar 0 nuevos `hotel_id` NULL.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-B: implementar procedencia avanzada scoped por `hotel_id`, sin tocar estancia/ranking ni PDFs/exportaciones.
