# Reservaciones 1-F-F-D-D-D-A - Auditoría de ranking de estados

## Objetivo

Auditar el ranking de estados antes de hacerlo tenant-aware.

## Hallazgo principal

- El ranking de estados sigue global en `Reporte.php`.
- Los tres métodos usan `reservaciones` y `huespedes` sin `hotel_id`.
- `huespedes` debe seguir como join auxiliar por `r.huesped_id = h.id`, no como fuente de scope.
- `rankingEstadosAction()` llama los tres métodos y renderiza `reportes/ranking-estados`.
- No se encontró vista física `src/app/views/reportes/ranking-estados.php`.

## Archivos encontrados

- `src/app/models/Reporte.php`
- `src/app/controllers/ReportesController.php`
- Vistas existentes en `src/app/views/reportes`:
  - `index.php`
  - `ingresos-gastos.php`
  - `procedencia.php`
  - `habitaciones-rentables.php`
  - `mantenimiento.php`
  - `ReportePDF.php`
  - `test-datos.php`
  - `test-usuario.php`
- Vista no encontrada:
  - `ranking-estados.php`

## Métodos auditados

- `obtenerRankingEstados()`
- `obtenerEvolucionEstados()`
- `obtenerComparativaEstados()`
- `rankingEstadosAction()`

## Hallazgos por método

- `obtenerRankingEstados()`: riesgo alto; main query y subconsulta de porcentaje son globales.
- `obtenerEvolucionEstados()`: riesgo alto; evolución mensual por estado sin `r.hotel_id`.
- `obtenerComparativaEstados()`: riesgo alto; subconsultas actual/anterior globales y lista base sale directo de `huespedes`.
- `rankingEstadosAction()`: riesgo medio/alto; controller delgado, consume métodos globales y renderiza vista faltante.

## Consultas críticas

- `obtenerRankingEstados()` debe agregar `r.hotel_id = ?` en la consulta principal.
- La subconsulta de `porcentaje_del_total` también debe filtrar `hotel_id`.
- `obtenerEvolucionEstados()` debe agregar `r.hotel_id = ?`.
- `obtenerComparativaEstados()` debe scopear las subconsultas actual y anterior con `r.hotel_id = ?`.
- En `obtenerComparativaEstados()`, la lista de estados no debería depender únicamente de `huespedes` global; debe derivarse de reservaciones scoped actuales/anteriores o mantenerse sin usarla como fuente de tenant.

## Qué implementar primero

Reservaciones 1-F-F-D-D-D-B:

- `obtenerRankingEstados()` scoped por `hotel_id`.

## Qué dejar para después

- Reservaciones 1-F-F-D-D-D-C: `obtenerEvolucionEstados()` scoped.
- Reservaciones 1-F-F-D-D-D-D: `obtenerComparativaEstados()` scoped.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.

## Riesgos

- Numerador scoped y denominador global puede generar porcentajes incorrectos.
- Comparativas por estado tienen subconsultas más delicadas.
- Huespedes no tiene modelo tenant.
- Vista `ranking-estados.php` no existe físicamente.
- Ranking puede alimentar reportes ejecutivos o PDFs futuros.

## Pruebas necesarias

- `php -l src/app/models/Reporte.php`
- `php -l src/app/controllers/ReportesController.php` si se toca.
- `git diff --check`
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Validación read-only de totales por `reservaciones.hotel_id`.
- GET `/reportes/ranking-estados`, documentando si falla por vista faltante.
- Confirmar que PDFs/PWA/Sync/APIs no se tocaron.
- Confirmar 0 nuevos `hotel_id` NULL.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-D-B: implementar únicamente `obtenerRankingEstados()` scoped por `hotel_id`, cuidando que el `porcentaje_del_total` use el mismo `hotel_id`.
