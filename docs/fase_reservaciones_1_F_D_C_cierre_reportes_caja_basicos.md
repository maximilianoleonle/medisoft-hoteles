# Reservaciones 1-F-D-C - Cierre de reportes basicos de Caja

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en reportes/caja basicos.

## 2. Que se logro

- `CajaController::reporteMetodosAction()` filtra `movimientos_caja` por `hotel_id`.
- `CajaController::verCorteAction()` valida `cortes_caja.id + hotel_id`.
- `Caja::obtenerEstadisticasMes()` filtra `cortes_caja.hotel_id` y `movimientos_caja.hotel_id`.
- `MovimientoCaja::obtenerTotalesPorPeriodo()` filtra por `hotel_id`.
- Las lecturas basicas de Caja ya respetan el hotel actual.

## 3. Archivos modificados

- `src/app/controllers/CajaController.php`
- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`

## 4. Que NO se toco

- `DashboardController`.
- `ReportesController`.
- `Reporte.php`.
- PDFs/exportaciones.
- `CajaController::exportarAction()`.
- `CajaController::descargarPDFAction()`.
- `CajaController::generarYEnviarReporteCorte()`.
- `Caja::obtenerIngresosPorTipoHabitacion()`.
- `MovimientoCaja::obtenerParaExportar()`.
- PWA/offline.
- Sync.
- APIs globales.
- Reservaciones funcional.
- Migraciones.
- Schema.

## 5. Validaciones

- `php -l` en los 3 archivos.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- GET `/caja`, `/caja/historial` y `/caja/reporte-metodos` sin errores 500.
- 0 `hotel_id` NULL en `cajas`, `cortes_caja` y `movimientos_caja`.

## 6. Riesgos pendientes

- Dashboard financiero sigue global.
- `ReportesController` sigue pendiente.
- `Reporte.php` sigue pendiente.
- PDFs/exportaciones siguen pendientes.
- Reportes historicos avanzados siguen pendientes.
- `denominaciones_efectivo` y `categorias_movimientos` aun no tienen `hotel_id`.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-E-A: auditoria especifica de Dashboard financiero y reportes historicos.

No debe implementarse directo porque puede afectar reportes financieros historicos y pantallas ejecutivas. Primero conviene separar las consultas de dashboard, reportes historicos, exportaciones/PDF y calculos ejecutivos antes de tocar codigo.
