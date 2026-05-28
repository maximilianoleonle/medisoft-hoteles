# Reservaciones 1-F-F-E-C-E - Cierre general de controllers PDF/exportacion de Caja

## 1. Objetivo

Documentar el cierre general del bloque de controllers PDF/exportacion de Caja scoped por hotel_id.

## 2. Que quedo cerrado

- CajaController::exportarAction()
- CajaController::descargarPDFAction()
- CajaController::generarYEnviarReporteCorte()
- MovimientoCaja::obtenerParaExportar()
- Caja::obtenerIngresosPorTipoHabitacion()

## 3. Que se logro

- Las rutas principales de exportacion/PDF de Caja validan corte_id + hotel_id.
- Los cortes se validan con cortes_caja.hotel_id.
- Las cajas se validan con cajas.hotel_id.
- Los movimientos de caja se consumen desde metodos ya scoped.
- Los ingresos por tipo de habitacion se consumen desde metodos ya scoped.
- Los datos base para PDF/exportacion de Caja ya estan protegidos por hotel_id antes de renderizar o descargar.

## 4. Que NO se toco

- Renderizadores PDF.
- ReporteCortePDF.php.
- ReportePDF.php.
- ReportesController.
- ReservacionController.
- PDFs complejos de reportes.
- Exportaciones de reservaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones.
- Schema.
- Base de datos.

## 5. Hallazgos pendientes

- exportarExcel() y exportarPDF() siguen sin encontrarse como definiciones PHP en CajaController.
- Renderizadores PDF parecen ser de bajo riesgo si reciben datos scoped, pero deben auditarse formalmente.
- PDFs complejos de ReportesController siguen pendientes.
- Exportaciones de ReservacionController quedan para fase separada.

## 6. Validaciones generales

- php -l en CajaController.php durante las fases funcionales.
- git diff --check.
- verificar_estado.php PASS.
- preflight_hotel_id.php PASS.
- Rutas probadas sin sesion redirigen a login sin errores 500.
- 0 nuevos hotel_id NULL segun herramientas.
- No se tocaron ReportesController, ReservacionController, PWA/Sync/APIs.

## 7. Riesgos pendientes

- Renderizadores PDF aun no tienen cierre propio.
- PDFs complejos de reportes siguen pendientes.
- Exportaciones de reservaciones siguen pendientes.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 8. Siguiente fase recomendada

Reservaciones 1-F-F-E-D-A: auditoria de renderizadores PDF y PDFs complejos de ReportesController, sin implementacion directa.
