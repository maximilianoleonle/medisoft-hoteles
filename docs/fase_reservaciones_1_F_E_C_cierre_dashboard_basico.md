# Reservaciones 1-F-E-C - Cierre tecnico de Dashboard basico scoped por hotel_id

## Objetivo

Documentar el cierre tecnico del Dashboard basico scoped por `hotel_id`.

## Que se logro

- `DashboardController` usa el `hotel_id` actual.
- Habitaciones se filtran por `habitaciones.hotel_id`.
- Reservaciones se filtran por `reservaciones.hotel_id`.
- `reservacion_habitaciones` se filtra por `hotel_id`.
- `movimientos_caja` se filtra por `hotel_id`.
- `cortes_caja` se filtra por `hotel_id`.
- Entradas, salidas y huespedes actuales quedan scoped.
- Proximas llegadas y proximas salidas quedan scoped.
- Graficos de ocupacion e ingresos por tipo quedan scoped.
- SQL directo en `views/dashboard/index.php` quedo scoped.
- El grafico ya usa el total real de habitaciones del hotel actual en lugar de una constante global.

## Archivos modificados en la implementacion

- `src/app/controllers/DashboardController.php`
- `src/app/views/dashboard/index.php`

## Que NO se toco

- `ReportesController`.
- `Reporte.php`.
- PDFs/exportaciones.
- Caja funcional.
- Reservaciones funcional.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.

## Validaciones realizadas

- `php -l` en `DashboardController.php`.
- `php -l` en `views/dashboard/index.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- `GET /dashboard` sin error 500.
- 0 `hotel_id` NULL en tablas migradas.

## Riesgos pendientes

- Dashboard historico/avanzado sigue pendiente.
- `ReportesController` sigue pendiente.
- `Reporte.php` sigue pendiente.
- PDFs/exportaciones siguen pendientes.
- PWA/offline, Sync y APIs siguen pendientes.
- Reportes historicos financieros siguen globales o fuera de scope.

## Siguiente fase recomendada

Reservaciones 1-F-F-A: auditoria especifica de `ReportesController` y `Reporte.php` antes de tocar PDFs/exportaciones.

Esta fase debe iniciar como auditoria porque `ReportesController`, `Reporte.php` y las exportaciones pueden mezclar metricas financieras historicas, cortes, reservaciones, habitaciones y datos de caja. Implementar directo aumentaria el riesgo de alterar reportes ejecutivos o documentos financieros sin un mapa preciso de consultas y dependencias.
