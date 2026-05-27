# Reservaciones 1-F-E-A - Auditoria de Dashboard financiero y reportes historicos

## 1. Objetivo

Auditar Dashboard financiero, `ReportesController`, `Reporte.php`, PDFs/exportaciones y reportes historicos antes de hacerlos tenant-aware.

Esta fase fue solo lectura. No se modificaron archivos, no se ejecuto SQL, no se toco base de datos, no se hicieron migraciones y no se implemento nada.

## 2. Hallazgo central

- Dashboard, `ReportesController`, `Reporte.php`, PDFs/exportaciones y reportes historicos todavia tienen lecturas globales.
- Caja base y reportes basicos internos ya estan scoped.
- Todavia quedan rutas avanzadas que leen `movimientos_caja`, `cortes_caja`, `reservaciones`, `reservacion_habitaciones` y `habitaciones` sin `hotel_id`.
- Las vistas de dashboard contienen SQL directo, por lo que no basta con corregir solo controllers o modelos.

## 3. Archivos encontrados

- `src/app/controllers/DashboardController.php`
- `src/app/controllers/ReportesController.php`
- `src/app/models/Reporte.php`
- `src/app/controllers/CajaController.php`, solo exportaciones/PDF pendientes.
- `src/app/models/Caja.php`, metodos pendientes como `obtenerIngresosPorTipoHabitacion()`.
- `src/app/models/MovimientoCaja.php`, metodos pendientes de exportacion.
- `src/includes/ReporteCortePDF.php`
- Vistas de dashboard.
- Vistas de reportes.
- Vistas/PDF/exportaciones relacionadas.

## 4. Metodos y zonas detectadas

- `DashboardController::getEstadisticasCompletas()`
- `DashboardController::getCajaInfo()`
- `DashboardController::getReservacionesHoy()`
- `DashboardController::getProximasLlegadas()`
- `DashboardController::getProximasSalidas()`
- `DashboardController::getDatosGraficos()`
- `views/dashboard/index.php` con SQL directo.
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
- `CajaController::exportarAction()`
- `CajaController::descargarPDFAction()`
- `CajaController::generarYEnviarReporteCorte()`
- `Caja::obtenerIngresosPorTipoHabitacion()`
- `MovimientoCaja::obtenerParaExportar()`

## 5. Consultas criticas

- `movimientos_caja` por fecha, usuario, metodo, categoria y corte.
- `cortes_caja` por estado abierto o `corte_id`.
- Joins `movimientos_caja -> reservaciones -> reservacion_habitaciones -> habitaciones`.
- Dashboard con `habitaciones WHERE activa = 1` sin `hotel_id`.
- Reportes de ocupacion/rentabilidad con constantes globales como 66 habitaciones.
- Vistas que llaman `caja/exportar`, `caja/descargar-pdf` y `reportes/exportar-pdf`.

## 6. Que ya puede filtrar

- `movimientos_caja.hotel_id`.
- `cortes_caja.hotel_id`.
- `reservaciones.hotel_id`.
- `reservacion_habitaciones.hotel_id`.
- `habitaciones.hotel_id`.

## 7. Riesgos

| Hallazgo | Riesgo | Motivo |
| --- | --- | --- |
| Dashboard financiero global | Alto | Puede mezclar ingresos, egresos, ocupacion y estado operativo entre hoteles. |
| PDFs/exportaciones globales | Alto | Puede generar documentos financieros incorrectos. |
| `Reporte.php` central global | Alto | Alimenta multiples reportes financieros e historicos. |
| `Caja::obtenerIngresosPorTipoHabitacion()` | Alto | Cruza caja, reservaciones y habitaciones sin tenant. |
| SQL directo en dashboard | Alto | Puede saltarse controllers/modelos ya corregidos. |
| Reportes de ocupacion/procedencia/estancia | Medio/alto | Cruzan reservaciones, huespedes y habitaciones sin tenant completo. |
| `ReporteCortePDF` como renderer | Bajo/medio | Es seguro solo si los datos llegan scoped desde origen. |

## 8. Que implementar primero

Reservaciones 1-F-E-B debe limitarse a Dashboard financiero basico scoped:

- `DashboardController::getEstadisticasCompletas()`
- `DashboardController::getCajaInfo()`
- `DashboardController::getReservacionesHoy()`
- `DashboardController::getProximasLlegadas()`
- `DashboardController::getProximasSalidas()`
- `DashboardController::getDatosGraficos()`
- `views/dashboard/index.php` si tiene SQL directo necesario para dashboard.

## 9. Que dejar fuera

- PDFs/exportaciones.
- `ReportesController::exportarPdfAction()` y derivados.
- `Reporte.php` historico completo.
- PWA/offline.
- Sync.
- APIs globales.
- Logica funcional de Caja.
- Logica funcional de Reservaciones.
- Migraciones/schema y cambios de datos.

## 10. Pruebas necesarias

- `php -l` en archivos tocados.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/dashboard`, `/caja`, `/reportes`.
- Validar metricas del dashboard contra `hotel_id`.
- Confirmar que no se tocaron PDFs/exportaciones, PWA/Sync/APIs.
- Confirmar 0 nuevos `hotel_id` NULL.

## 11. Siguiente fase recomendada

Reservaciones 1-F-E-B: implementacion limitada de Dashboard financiero basico scoped.

No debe tocar PDFs/exportaciones, `ReportesController`, `Reporte.php` historico completo, PWA/offline, Sync ni APIs globales. Dashboard debe tratarse primero porque es una pantalla ejecutiva de alta visibilidad y combina datos financieros y operativos.
