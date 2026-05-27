# Reservaciones 1-F-D-A - Auditoria de reportes, dashboard y financieros

## 1. Objetivo

Auditar reportes financieros, dashboard, exportaciones/PDF y cortes avanzados antes de hacerlos tenant-aware.

Esta fase fue solo lectura. No se modificaron archivos, no se ejecuto SQL, no se toco base de datos, no se hicieron migraciones y no se implemento nada.

## 2. Hallazgo central

- Caja funcional base ya esta scoped.
- Reportes, dashboard, exportaciones/PDF y cortes avanzados todavia tienen lecturas globales sobre `movimientos_caja`, `cortes_caja`, `reservacion_pagos`, `reservaciones` y `habitaciones`.
- El riesgo principal es mezclar ingresos, egresos, ocupacion o documentos financieros entre hoteles.

## 3. Archivos encontrados

- `src/app/controllers/ReportesController.php`
- `src/app/controllers/DashboardController.php`
- `src/app/models/Reporte.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`
- `src/app/controllers/CajaController.php`
- `src/includes/ReporteCortePDF.php`
- Vistas de dashboard.
- Vistas de reportes.
- Vistas de caja/cortes.

## 4. Consultas criticas

- Reportes ingresos/gastos sobre `movimientos_caja` por fecha.
- Metodos de pago agrupados por `movimientos_caja`.
- PDFs de ingresos por usuario usando `movimientos_caja`, `reservaciones` y `habitaciones`.
- Dashboard financiero con ingresos/gastos/devoluciones.
- Dashboard de ocupacion con `reservaciones`, `reservacion_habitaciones` y `habitaciones`.
- Corte avanzado con `cortes_caja` y `movimientos_caja` por `corte_id`.
- Exportar movimientos por `corte_id`.
- Rentabilidad/habitaciones con `reservaciones` y `habitaciones`.

## 5. Reportes afectados

- `ReportesController::ingresosGastosAction()`
- `ReportesController::exportarPdfAction()`
- `ReportesController::exportarIngresosGastosPdf()`
- `ReportesController::exportarIngresosGastosUsuarioPdf()`
- `ReportesController::exportarIngresosTotalesPdf()`
- `ReportesController::obtenerIngresosPorPropiedad()`
- `Reporte::getIngresosVsGastos()`
- `Reporte::getResumenDiario()`
- `Reporte::getIngresosPorMetodoPago()`
- `Reporte::getMovimientosPorUsuario()`
- `Reporte::getResumenPorUsuario()`
- `Reporte::getEstadisticasAvanzadas()`
- `Reporte::obtenerRentabilidadHabitaciones()`
- `Reporte::obtenerOcupacionPorTipo()`

## 6. Exportaciones/PDF

- `CajaController::exportarAction()`
- `CajaController::descargarPDFAction()`
- `CajaController::generarYEnviarReporteCorte()`
- `ReportesController::exportarPdfAction()`
- `ReporteCortePDF.php` depende de datos ya preparados.
- `views/reportes/ReportePDF.php` renderiza salida; el riesgo esta en los datos de entrada.

## 7. Riesgos

| Riesgo | Nivel | Motivo |
| --- | --- | --- |
| Dashboard financiero global | Alto | Puede mezclar ingresos, egresos y devoluciones entre hoteles. |
| PDFs/exportaciones globales | Alto | Puede generar documentos financieros incorrectos o historicos mezclados. |
| `Caja::obtenerIngresosPorTipoHabitacion()` global | Alto | Cruza ingresos, reservaciones y habitaciones sin tenant. |
| `Reporte.php` central global | Alto | Alimenta multiples reportes financieros e historicos. |
| Vistas con SQL directo en dashboard | Alto | Pueden saltarse modelos/controladores ya scoped. |
| `denominaciones_efectivo` sin `hotel_id` | Medio | Debe validarse via corte scoped, no migrarse improvisadamente. |

## 8. Que implementar primero

Reservaciones 1-F-D-B debe ser limitado a reportes/caja basicos scoped:

- `CajaController::reporteMetodosAction()`
- `Caja::obtenerEstadisticasMes()`
- `MovimientoCaja::obtenerTotalesPorPeriodo()`
- Lecturas simples por `corte_id + hotel_id`.
- Validar `verCorteAction()` con `id + hotel_id`.

## 9. Que dejar fuera

- Dashboard financiero completo.
- PDFs/exportaciones.
- Reportes historicos avanzados.
- `ReportesController` de PDFs.
- `Reporte.php` completo si implica multiples reportes cruzados.
- PWA/offline.
- Sync.
- APIs globales.
- Logica funcional de Caja.
- Logica funcional de Reservaciones.
- Migraciones/schema.

## 10. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/caja`, `/caja/historial`, `/dashboard`, `/reportes`.
- Validar reportes con `movimientos_caja.hotel_id`.
- Validar cortes con `cortes_caja.hotel_id`.
- Confirmar 0 nuevos `hotel_id` NULL.
- Confirmar que PWA/Sync/APIs no se tocaron.

## 11. Siguiente fase recomendada

Reservaciones 1-F-D-B: implementacion limitada de reportes/caja basicos scoped.

No tocar Dashboard completo ni PDFs/exportaciones todavia. Es mejor aislar primero lecturas simples de Caja y cortes basicos antes de entrar a reportes historicos, documentos descargables o vistas con SQL directo.
