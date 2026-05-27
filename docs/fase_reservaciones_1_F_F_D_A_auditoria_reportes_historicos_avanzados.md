# Reservaciones 1-F-F-D-A - Auditoria de reportes historicos avanzados

## 1. Objetivo

Auditar reportes historicos avanzados antes de hacerlos tenant-aware.

## 2. Hallazgo principal

- Los reportes historicos avanzados siguen globales.
- Casi todas las consultas usan `reservaciones`, `reservacion_habitaciones`, `habitaciones` y `huespedes` sin `hotel_id`.
- Ocupacion conserva constante global `66`.
- `huespedes` debe tratarse como join auxiliar, no como fuente de scope.

## 3. Archivos encontrados

- `src/app/controllers/ReportesController.php`
- `src/app/models/Reporte.php`
- `src/app/views/reportes/procedencia.php`
- `src/app/views/reportes/habitaciones-rentables.php`
- `src/app/views/reportes/index.php`

## 4. Nota de vistas faltantes

- Existen rutas para `ocupacion`, `estancia` y `ranking-estados`.
- No se encontraron vistas fisicas `ocupacion.php`, `estancia.php` ni `ranking-estados.php`.

## 5. Metodos auditados

- `procedenciaAction()`
- `habitacionesRentablesAction()`
- `ocupacionAction()`
- `estanciaAction()`
- `rankingEstadosAction()`
- `obtenerProcedenciaPorEstado()`
- `obtenerProcedenciaPorCiudad()`
- `obtenerEvolucionProcedencia()`
- `obtenerRentabilidadHabitaciones()`
- `obtenerOcupacionPorTipo()`
- `obtenerIngresoPromedioPorHabitacion()`
- `obtenerOcupacionDiaria()`
- `obtenerOcupacionSemanal()`
- `obtenerOcupacionMensual()`
- `obtenerEstadisticasOcupacion()`
- `obtenerOcupacionPorDiaSemana()`
- `obtenerPromedioEstancia()`
- `obtenerEstanciaPorTipo()`
- `obtenerEstanciaPorProcedencia()`
- `obtenerDistribucionEstancia()`
- `obtenerTendenciaEstancia()`
- `obtenerRankingEstados()`
- `obtenerEvolucionEstados()`
- `obtenerComparativaEstados()`

## 6. Consultas criticas

- Todas las consultas con `reservaciones r` deben agregar `r.hotel_id = ?`.
- Joins con `reservacion_habitaciones rh` deben validar `rh.hotel_id = r.hotel_id` o `rh.hotel_id = ?`.
- Joins con `habitaciones h` deben validar `h.hotel_id = rh.hotel_id` o `h.hotel_id = ?`.
- `huespedes` no debe ser fuente de scope todavia.
- La constante global `66` debe reemplazarse por conteo real de habitaciones del hotel actual.

## 7. Riesgos

- Reportes de rentabilidad y ocupacion pueden mezclar hoteles.
- Procedencia/estancia/ranking dependen de `huespedes` sin modelo tenant.
- Ocupacion usa constante global.
- Vistas faltantes pueden indicar rutas incompletas o legacy.
- PDFs/exportaciones deben esperar.

## 8. Que implementar primero

Reservaciones 1-F-F-D-B debe limitarse a reportes historicos operativos basicos scoped:

- `obtenerPromedioEstancia()`
- `obtenerDistribucionEstancia()`
- `obtenerTendenciaEstancia()`
- `obtenerOcupacionPorDiaSemana()`
- quiza `obtenerProcedenciaPorCiudad()`
- quiza `obtenerEvolucionProcedencia()`

## 9. Que dejar para fases posteriores

- Rentabilidad/ocupacion avanzada.
- Procedencia/estancia/ranking completos.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Decision de huespedes tenant.

## 10. Fases propuestas

- 1-F-F-D-B: reportes historicos operativos basicos scoped.
- 1-F-F-D-C: rentabilidad/ocupacion avanzada scoped.
- 1-F-F-D-D: procedencia/estancia/ranking scoped.
- 1-F-F-E: PDFs/exportaciones.

## 11. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET de rutas historicas disponibles.
- Validar resultados por `hotel_id` contra consultas read-only.
- Confirmar que no se tocaron PDFs/exportaciones/PWA/Sync/APIs.
- Confirmar 0 nuevos `hotel_id` NULL.
