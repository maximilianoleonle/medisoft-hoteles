# Reservaciones 1-F-F-C - Cierre tecnico de ReportesController basico scoped

## 1. Objetivo

Documentar el cierre tecnico del scope basico en `ReportesController`.

## 2. Que se logro

- `ReportesController` carga `hotel_config.php`.
- `ReportesController` puede resolver el hotel actual con `obtenerHotelIdActualCompat()`.
- `getMetodosPagoData()` filtra `movimientos_caja` por `hotel_id`.
- `datosGraficaAction()` queda compatible porque para ingresos-gastos usa `Reporte.php` ya scoped.
- `getUsuariosActivos()` queda como filtro auxiliar, no como fuente de scope.

## 3. Archivo modificado

- `src/app/controllers/ReportesController.php`

## 4. Que NO se toco

- `Reporte.php`.
- PDFs/exportaciones.
- `exportarPdfAction()`.
- `exportarIngresosGastosPdf()`.
- `exportarIngresosGastosUsuarioPdf()`.
- `exportarIngresosTotalesPdf()`.
- `procedenciaAction()`.
- `habitacionesRentablesAction()`.
- `ocupacionAction()`.
- `estanciaAction()`.
- `rankingEstadosAction()`.
- `testDatosAction()`.
- `testUsuarioAction()`.
- PWA/offline.
- Sync.
- APIs globales.
- `DashboardController`.
- `CajaController`.
- Reservaciones funcional.
- Migraciones.
- Schema.

## 5. Validaciones

- `php -l` en `ReportesController.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- `GET /reportes` redirige sin 500.
- `GET /reportes/ingresos-gastos` redirige sin 500.
- AJAX `/reportes/datos-grafica` sin sesion devuelve 401, esperado.
- 0 nuevos `hotel_id` NULL.

## 6. Riesgos pendientes

- PDFs/exportaciones siguen fuera de scope.
- Reportes historicos avanzados siguen pendientes.
- Procedencia/rentabilidad/ocupacion/estancia/ranking siguen pendientes.
- Vistas de reportes pueden requerir revision futura.
- PWA/offline, Sync y APIs siguen pendientes.
- Huespedes/usuarios siguen sin modelo tenant.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-D-A: auditoria especifica de reportes historicos avanzados antes de implementacion.
