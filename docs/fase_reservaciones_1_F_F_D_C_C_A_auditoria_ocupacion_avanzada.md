# Reservaciones 1-F-F-D-C-C-A: Auditoria de ocupacion avanzada

## Objetivo

Auditar reportes de ocupacion avanzada antes de reemplazar la constante global `66` y hacerlos tenant-aware.

## Hallazgo principal

- La constante global `66` sigue concentrada en reportes de ocupacion avanzada de `Reporte.php`.
- Representa la capacidad total de habitaciones del hotel.
- Esta hardcodeada y no respeta `hotel_id`, habitaciones inactivas ni futuros hoteles.

## Metodos auditados

- `obtenerOcupacionDiaria()`
- `obtenerOcupacionSemanal()`
- `obtenerOcupacionMensual()`
- `obtenerEstadisticasOcupacion()`
- `obtenerOcupacionPorTipo()`
- `ocupacionAction()`

## Donde aparece la constante 66

La constante global `66` aparece en:

- `obtenerOcupacionDiaria()`
- `obtenerOcupacionSemanal()`
- `obtenerOcupacionMensual()`
- `obtenerEstadisticasOcupacion()`

## Calculo actual

- Diario: habitaciones ocupadas del dia / `66`.
- Semanal: habitaciones ocupadas-dia / `66 * 7`.
- Mensual: habitaciones ocupadas-dia / `66 * dias del mes`.
- Estadisticas: habitaciones ocupadas-dia / `66 * dias del periodo`.

## Propuesta tecnica

Usar el total real de habitaciones activas del hotel actual:

```sql
SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND activa = 1
```

Recomendaciones:

- Crear helper privado en `Reporte.php`, por ejemplo `totalHabitacionesActivasHotel()`.
- Calcularlo una vez por metodo.
- Para diario: divisor = total habitaciones activas.
- Para semanal: divisor = total habitaciones activas * 7.
- Para mensual: divisor = total habitaciones activas * dias del mes.
- Para estadisticas: divisor = total habitaciones activas * dias del periodo.
- Scopear joins con `r.hotel_id = ?` y `rh.hotel_id = r.hotel_id`.

## Riesgos

- Cambiara porcentajes historicos porque antes usaban capacidad fija `66`.
- Puede diferir si hubo habitaciones inactivas, eliminadas o cambios historicos de capacidad.
- El total actual de habitaciones activas no representa necesariamente capacidad historica.
- Multi-hotel futuro requiere capacidad por hotel.
- La precision historica por fecha podria requerir una tabla futura de capacidad historica.

## Decision recomendada

- Usar por ahora el total actual de habitaciones activas del hotel actual.
- Documentar que la capacidad historica por fecha queda para una fase futura.
- Dividir implementacion:
  - `1-F-F-D-C-C-B`: `obtenerOcupacionPorTipo()` scoped por hotel.
  - `1-F-F-D-C-D`: diaria/semanal/mensual + estadisticas con total real de habitaciones.
  - Fase futura: capacidad historica si se requiere precision retrospectiva.

## Que dejar fuera

- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Ranking completo.
- Huespedes tenant.

## Pruebas necesarias

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/reportes/habitaciones-rentables`.
- GET `/reportes/ocupacion` si la vista existe, o documentar comportamiento actual si falta.
- Validacion read-only de total de habitaciones activas por hotel.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que no se tocaron PDFs/exportaciones/PWA/Sync/APIs.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-C-C-B: implementar `obtenerOcupacionPorTipo()` scoped por `hotel_id`, sin tocar todavia la constante `66` de diaria/semanal/mensual.
