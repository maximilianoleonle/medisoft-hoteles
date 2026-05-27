# Reservaciones 1-F-F-D-C-B - Cierre tecnico de rentabilidad de habitaciones scoped

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en reportes de rentabilidad de habitaciones e ingreso promedio por habitacion.

## 2. Que se logro

- `obtenerRentabilidadHabitaciones()` ahora respeta `hotel_id`.
- `obtenerIngresoPromedioPorHabitacion()` ahora respeta `hotel_id`.
- Se uso `hotelIdActual()`.
- Los joins entre `habitaciones`, `reservacion_habitaciones` y `reservaciones` quedaron scoped por `hotel_id`.
- Las metricas de rentabilidad ya usan solo datos del hotel actual.

## 3. Archivo modificado

- `src/app/models/Reporte.php`

## 4. Que NO se toco

- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Metodos con constante global `66`.
- Ocupacion diaria/semanal/mensual.
- `obtenerEstadisticasOcupacion()`.
- Ranking completo.
- Reportes historicos avanzados fuera del alcance.
- Migraciones.
- Schema.

## 5. Validaciones realizadas

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Confirmacion de que no se tocaron PDFs/exportaciones/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

- Ocupacion diaria/semanal/mensual sigue usando constante global `66`.
- `obtenerEstadisticasOcupacion()` sigue pendiente.
- `obtenerOcupacionPorTipo()` sigue pendiente.
- Ranking completo sigue pendiente.
- PDFs/exportaciones siguen pendientes.
- `huespedes` sigue sin modelo tenant.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-D-C-C-A: auditoria/propuesta para ocupacion avanzada y reemplazo de la constante global `66` por el total real de habitaciones del hotel actual.
