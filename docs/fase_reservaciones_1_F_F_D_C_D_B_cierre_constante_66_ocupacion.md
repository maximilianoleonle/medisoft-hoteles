# Reservaciones 1-F-F-D-C-D-B: Cierre de reemplazo de constante 66 en ocupacion

## Objetivo

Documentar el cierre tecnico del reemplazo de la constante global `66` en reportes de ocupacion avanzada.

## Que se logro

- Se creo el helper `totalHabitacionesActivasHotel()`.
- El helper obtiene el total real de habitaciones activas del hotel actual.
- Se dejo de usar la capacidad fija `66` en los metodos permitidos.
- Se usa `hotelIdActual()`.
- Se agrego proteccion contra division entre cero.
- Los calculos de ocupacion avanzada ahora se basan en la capacidad real del hotel actual.

## Metodos modificados

- `obtenerOcupacionDiaria()`
- `obtenerOcupacionSemanal()`
- `obtenerOcupacionMensual()`
- `obtenerEstadisticasOcupacion()`

## Formulas reemplazadas

- Diario: ocupadas / `total_habitaciones_activas`.
- Semanal: ocupadas_dia / `(total_habitaciones_activas * 7)`.
- Mensual: ocupadas_dia / `(total_habitaciones_activas * dias_mes)`.
- Estadisticas: ocupadas_dia / `(total_habitaciones_activas * dias_periodo)`.

## Que NO se toco

- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Ranking completo.
- Huespedes tenant.
- Migraciones.
- Schema.
- Base de datos.
- Capacidad historica por fecha.

## Validaciones realizadas

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Confirmacion de que no queda la constante `66` en `Reporte.php`.
- Confirmacion de que no se tocaron PDFs/exportaciones/PWA/Sync/APIs.
- Confirmacion de 0 nuevos `hotel_id` NULL segun herramientas.

## Riesgos pendientes

- Los porcentajes historicos pueden cambiar respecto al calculo anterior basado en `66`.
- El total actual de habitaciones activas puede no representar capacidad historica por fecha.
- Una fase futura podria requerir tabla de capacidad historica por hotel y fecha.
- PDFs/exportaciones siguen pendientes.
- Ranking completo sigue pendiente.
- Huespedes siguen sin modelo tenant.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-A: auditoria de ranking completo, procedencia/estancia avanzadas restantes y preparacion antes de PDFs/exportaciones.
