# Reservaciones 1-F-F-E-D-A - Auditoria de PDFs de ReportesController

## 1. Objetivo

Auditar renderizadores PDF y PDFs complejos de ReportesController antes de hacerlos tenant-aware.

## 2. Hallazgo principal

- ReportePDF.php y ReporteCortePDF.php solo renderizan datos recibidos.
- El riesgo esta en las consultas previas que preparan datos.
- ReportesController todavia tiene rutas PDF con consultas globales.
- Reporte.php conserva un camino legacy PDF con obtenerIngresosGastos() global.

## 3. Archivos encontrados

- src/app/controllers/ReportesController.php
- src/app/models/Reporte.php
- src/app/views/reportes/ReportePDF.php
- src/includes/ReporteCortePDF.php

Vistas relacionadas:

- reportes/ingresos-gastos.php
- reportes/procedencia.php
- reportes/test-usuario.php
- reportes/index.php

## 4. Metodos auditados

- ReportesController::exportarPdfAction()
- ReportesController::exportarIngresosGastosPdf()
- ReportesController::exportarIngresosGastosUsuarioPdf()
- ReportesController::exportarIngresosTotalesPdf()
- ReportesController::obtenerIngresosPorPropiedad()
- Reporte::generarPDF()
- Reporte::generarContenidoPDF()
- Reporte::generarPDFIngresosGastos()
- ReportePDF.php
- ReporteCortePDF.php

## 5. Riesgos

Alto:

- exportarIngresosGastosPdf()
- exportarIngresosGastosUsuarioPdf()
- exportarIngresosTotalesPdf()
- obtenerIngresosPorPropiedad()
- Reporte::generarPDFIngresosGastos()

Medio:

- exportarPdfAction()
- Reporte::generarPDF()
- Reporte::generarContenidoPDF()

Bajo:

- ReportePDF.php si recibe datos scoped.
- ReporteCortePDF.php si recibe datos scoped.

## 6. Consultas a scopear

- movimientos_caja: mc.hotel_id = ?
- reservaciones: r.hotel_id = ? o r.hotel_id = mc.hotel_id
- reservacion_habitaciones: rh.hotel_id = r.hotel_id o rh.hotel_id = mc.hotel_id
- habitaciones: h.hotel_id = rh.hotel_id
- usuarios como join auxiliar, no fuente de scope
- huespedes como join auxiliar si aparece en PDFs futuros

## 7. Que implementar primero

Reservaciones 1-F-F-E-D-B:

- ReportesController::exportarIngresosGastosPdf()
- ReportesController::obtenerIngresosPorPropiedad()

Motivo:

exportarIngresosGastosPdf() ya consume varios datos scoped, pero sigue contaminado si obtenerIngresosPorPropiedad() permanece global.

## 8. Que dejar para despues

- exportarIngresosGastosUsuarioPdf()
- exportarIngresosTotalesPdf()
- Reporte.php legacy PDF
- metodos PDF no encontrados
- PDFs historicos avanzados
- ReservacionController exportaciones
- PWA/offline
- Sync
- APIs globales
- migraciones/schema
- cambios de datos

## 9. Pruebas necesarias

- php -l en archivos tocados.
- git diff --check.
- verificar_estado.php PASS.
- preflight_hotel_id.php PASS.
- descarga controlada de /reportes/exportar-pdf?tipo=ingresos-gastos si aplica.
- confirmar que PWA/Sync/APIs no se tocaron.
- confirmar 0 nuevos hotel_id NULL.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-D-B: implementar ReportesController::exportarIngresosGastosPdf() y ReportesController::obtenerIngresosPorPropiedad() scoped por hotel_id, sin tocar todavia PDFs de usuario, ingresos totales, Reporte.php legacy, ReservacionController, PWA/offline, Sync ni APIs.
