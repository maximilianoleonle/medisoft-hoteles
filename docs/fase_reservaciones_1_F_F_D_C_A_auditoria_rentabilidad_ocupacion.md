# Reservaciones 1-F-F-D-C-A - Auditoria de rentabilidad y ocupacion avanzada

## 1. Objetivo

Auditar reportes de rentabilidad y ocupacion avanzada antes de hacerlos tenant-aware.

## 2. Hallazgo principal

- Los reportes de rentabilidad y ocupacion avanzada siguen globales en `Reporte.php`.
- El riesgo principal esta en joins sin `hotel_id` entre `habitaciones`, `reservacion_habitaciones` y `reservaciones`.
- La constante global `66` se usa para porcentajes de ocupacion y debe reemplazarse por el total real de habitaciones del hotel actual.

## 3. Archivos encontrados

- `src/app/models/Reporte.php`
- `src/app/controllers/ReportesController.php`
- `src/app/views/reportes/habitaciones-rentables.php`
- `src/app/views/reportes/index.php`

## 4. Metodos auditados

- `obtenerRentabilidadHabitaciones()`
- `obtenerOcupacionPorTipo()`
- `obtenerIngresoPromedioPorHabitacion()`
- `obtenerOcupacionDiaria()`
- `obtenerOcupacionSemanal()`
- `obtenerOcupacionMensual()`
- `obtenerEstadisticasOcupacion()`
- `habitacionesRentablesAction()`
- `ocupacionAction()`

## 5. Constante global 66

La constante global `66` aparece en:

- `obtenerOcupacionDiaria()`
- `obtenerOcupacionSemanal()`
- `obtenerOcupacionMensual()`
- `obtenerEstadisticasOcupacion()`

Reemplazo recomendado:

```sql
SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND activa = 1
```

## 6. Consultas a scopear

- `reservaciones`: `r.hotel_id = ?`
- `reservacion_habitaciones`: `rh.hotel_id = r.hotel_id`
- `habitaciones`: `h.hotel_id = rh.hotel_id` o `h.hotel_id = ?`

## 7. Riesgos

- `obtenerRentabilidadHabitaciones()`: alto.
- `obtenerOcupacionPorTipo()`: alto.
- `obtenerIngresoPromedioPorHabitacion()`: medio/alto.
- `obtenerOcupacionDiaria()`: alto por constante `66`.
- `obtenerOcupacionSemanal()`: alto por constante `66`.
- `obtenerOcupacionMensual()`: alto por constante `66`.
- `obtenerEstadisticasOcupacion()`: alto por constante global y calculo por periodo.
- `ocupacionAction()`: medio por vista fisica no encontrada.
- `habitacionesRentablesAction()`: bajo/medio porque es controller delgado.

## 8. Que implementar primero

Reservaciones 1-F-F-D-C-B:

- `obtenerRentabilidadHabitaciones()`
- `obtenerIngresoPromedioPorHabitacion()`

## 9. Que dejar para fases posteriores

- `obtenerOcupacionPorTipo()`
- Ocupacion diaria/semanal/mensual.
- Estadisticas de ocupacion.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.

## 10. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET de rutas relacionadas si existen.
- Comparacion read-only de totales por `hotel_id`.
- Confirmar que no se tocaron PDFs/exportaciones/PWA/Sync/APIs.
- Confirmar 0 nuevos `hotel_id` NULL.

## 11. Siguiente fase recomendada

Reservaciones 1-F-F-D-C-B: implementar rentabilidad de habitaciones e ingreso promedio por habitacion scoped por `hotel_id`, sin tocar ocupacion con constante `66` todavia.
