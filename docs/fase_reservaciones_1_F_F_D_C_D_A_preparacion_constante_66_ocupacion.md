# Reservaciones 1-F-F-D-C-D-A: Preparacion para reemplazar constante 66 en ocupacion

## Objetivo

Documentar la propuesta tecnica para reemplazar la constante global `66` en reportes de ocupacion avanzada.

## Hallazgo principal

La constante global `66` aparece en metodos de ocupacion avanzada de `Reporte.php` y representa la capacidad total de habitaciones del hotel, pero esta hardcodeada y no respeta `hotel_id`, habitaciones inactivas ni futuros hoteles.

## Metodos afectados

- `obtenerOcupacionDiaria()`
- `obtenerOcupacionSemanal()`
- `obtenerOcupacionMensual()`
- `obtenerEstadisticasOcupacion()`

## Formulas actuales

- Diario: habitaciones ocupadas del dia / `66`.
- Semanal: habitaciones ocupadas-dia / `(66 * 7)`.
- Mensual: habitaciones ocupadas-dia / `(66 * dias del mes)`.
- Estadisticas: habitaciones ocupadas-dia / `(66 * dias del periodo)`.

## Formulas nuevas propuestas

- Diario: habitaciones ocupadas del dia / `total_habitaciones_activas_hotel`.
- Semanal: habitaciones ocupadas-dia / `(total_habitaciones_activas_hotel * 7)`.
- Mensual: habitaciones ocupadas-dia / `(total_habitaciones_activas_hotel * dias del mes)`.
- Estadisticas: habitaciones ocupadas-dia / `(total_habitaciones_activas_hotel * dias del periodo)`.

## Helper propuesto

Crear helper privado en `Reporte.php`:

```php
private function totalHabitacionesActivasHotel($hotel_id = null): int
```

Consulta propuesta:

```sql
SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND activa = 1
```

## Reglas tecnicas

- Usar `hotelIdActual()` para resolver `hotel_id`.
- Calcular el total una vez por metodo.
- Scopear consultas de reservaciones con `r.hotel_id = ?`.
- Scopear `reservacion_habitaciones` con `rh.hotel_id = r.hotel_id`.
- Evitar division entre cero.
- Si `total_habitaciones_activas <= 0`, devolver porcentaje `0` y disponibilidad `0`.

## Riesgos

- Cambiaran porcentajes historicos que antes usaban capacidad fija `66`.
- El total actual de habitaciones activas puede no representar capacidad historica.
- Habitaciones inactivas/eliminadas pueden alterar comparativos pasados.
- Para precision retrospectiva real haria falta una tabla futura de capacidad historica por fecha/hotel.
- El cambio afecta metricas ejecutivas, por eso debe implementarse en fase separada.

## Decision recomendada

Si conviene implementar, pero en una fase separada y explicita.

Usar total actual de habitaciones activas del hotel actual como solucion SaaS inicial.

Documentar que la capacidad historica por fecha queda para una fase futura.

## Que NO tocar todavia

- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Ranking completo.
- Huespedes tenant.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-C-D-B: implementar helper `totalHabitacionesActivasHotel()` y reemplazar la constante `66` en ocupacion diaria/semanal/mensual/estadisticas.

## Pruebas necesarias

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Validacion read-only del total activo por hotel.
- GET de rutas de ocupacion si existen.
- Confirmar que PDFs/exportaciones/PWA/Sync/APIs no se tocaron.
- Confirmar 0 nuevos `hotel_id` NULL.
