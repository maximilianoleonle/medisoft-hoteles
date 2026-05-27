# Reservaciones 1-F-F-D-C-C-B: Cierre de ocupacion por tipo scoped

## Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `obtenerOcupacionPorTipo()`.

## Que se logro

- `obtenerOcupacionPorTipo()` ahora usa `hotelIdActual()`.
- Filtra habitaciones con `h.hotel_id = ?`.
- Valida `reservacion_habitaciones` con `rh.hotel_id = h.hotel_id`.
- Valida reservaciones con `r.hotel_id = h.hotel_id`.
- El reporte de ocupacion por tipo ya usa unicamente datos del hotel actual.

## Archivo modificado

- `src/app/models/Reporte.php`

## Que NO se toco

- `obtenerOcupacionDiaria()`.
- `obtenerOcupacionSemanal()`.
- `obtenerOcupacionMensual()`.
- `obtenerEstadisticasOcupacion()`.
- Metodos con constante global `66`.
- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.

## Validaciones realizadas

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Confirmacion de que no se tocaron metodos con constante `66`.
- Confirmacion de que no se tocaron PDFs/exportaciones/PWA/Sync/APIs.
- 0 nuevos `hotel_id` NULL segun herramientas.

## Riesgos pendientes

- Ocupacion diaria/semanal/mensual sigue usando constante global `66`.
- `obtenerEstadisticasOcupacion()` sigue usando constante global `66`.
- La capacidad historica por fecha sigue pendiente.
- PDFs/exportaciones siguen pendientes.
- PWA/offline, Sync y APIs siguen fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-C-D-A: preparacion tecnica para reemplazar la constante global `66` por el total real de habitaciones activas del hotel actual.

No debe implementarse directo sin revisar el impacto en porcentajes historicos, porque cambiar el denominador de ocupacion puede alterar metricas ejecutivas e historicas.
