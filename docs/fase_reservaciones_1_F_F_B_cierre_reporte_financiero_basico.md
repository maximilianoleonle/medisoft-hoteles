# Reservaciones 1-F-F-B - Cierre tecnico de Reporte.php financiero basico

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en `Reporte.php` financiero basico.

## 2. Que se logro

- `Reporte.php` carga `hotel_config.php`.
- Usa `obtenerHotelIdActualCompat()` para obtener el hotel actual.
- Los metodos financieros base filtran por `movimientos_caja.hotel_id`.
- `usuarios` queda como join auxiliar, no como fuente de scope.
- Las metricas financieras base ya respetan el hotel actual.

## 3. Metodos modificados

- `getIngresosVsGastos`
- `getResumenDiario`
- `getIngresosPorMetodoPago`
- `getMovimientosPorUsuario`
- `getResumenPorUsuario`
- `getEstadisticasAvanzadas`
- `getTransaccionesMayores`
- `getComparacionPeriodos`
- `obtenerResumenDiario`
- `obtenerResumenPorCategoria`
- `obtenerDatosGraficaIngresosGastos`

## 4. Que NO se toco

- `ReportesController`.
- PDFs/exportaciones.
- Generacion PDF.
- `obtenerIngresosGastos`, por pertenecer al camino legacy/PDF.
- Procedencia.
- Rentabilidad.
- Ocupacion.
- Estancia.
- Ranking.
- Mantenimiento.
- Debug/test.
- Dashboard.
- Caja funcional.
- Reservaciones funcional.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.

## 5. Validaciones realizadas

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Confirmacion de que no se tocaron `ReportesController`, PDFs, PWA, Sync ni APIs.
- 0 nuevos `hotel_id` NULL segun herramientas SaaS.

## 6. Riesgos pendientes

- `ReportesController` todavia puede llamar metodos globales.
- PDFs/exportaciones siguen pendientes.
- Reportes historicos avanzados siguen pendientes.
- Ocupacion puede conservar constantes globales.
- Huespedes/usuarios siguen sin modelo tenant.
- Vistas de reportes pueden seguir mostrando datos no scoped hasta su fase.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-C: `ReportesController` vistas basicas scoped, sin PDFs/exportaciones.
