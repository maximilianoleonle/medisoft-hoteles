# Reservaciones 1-F-F-E-C-B - Cierre tecnico de CajaController::exportarAction() scoped

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en CajaController::exportarAction().

## 2. Que se logro

- exportarAction() ahora resuelve el hotel actual con obtenerHotelIdActualCompat().
- Valida corte_id + hotel_id antes de exportar.
- Usa Caja::obtenerCortePorId($corte_id, $hotel_id).
- Si el corte no pertenece al hotel actual, redirige con mensaje de error.
- Consume MovimientoCaja::obtenerParaExportar(), que ya estaba scoped por hotel_id.
- Mantiene la logica original de exportacion.

## 3. Archivo modificado

- src/app/controllers/CajaController.php

## 4. Que NO se toco

- descargarPDFAction().
- generarYEnviarReporteCorte().
- renderizadores PDF.
- ReporteCortePDF.php.
- ReportePDF.php.
- ReportesController.
- ReservacionController.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- base de datos.

## 5. Hallazgo pendiente

- exportarExcel() y exportarPDF() son llamados por exportarAction(), pero no se encontraron definiciones PHP en CajaController.
- No se inventaron ni se implementaron en esta fase.
- Deben revisarse en una fase posterior si esa ruta necesita completarse.

## 6. Validaciones realizadas

- php -l en CajaController.php.
- git diff --check.
- verificar_estado.php PASS.
- preflight_hotel_id.php PASS.
- GET /caja/exportar?formato=excel&corte_id=1 respondio 303 a /login sin error 500.
- Confirmacion de que no se tocaron PDFs/renderizadores/PWA/Sync/APIs.
- 0 nuevos hotel_id NULL segun herramientas.

## 7. Riesgos pendientes

- descargarPDFAction() sigue pendiente.
- generarYEnviarReporteCorte() sigue pendiente.
- renderizadores PDF siguen pendientes.
- exportarExcel()/exportarPDF() no encontrados.
- PDFs complejos de ReportesController siguen pendientes.
- Exportaciones de ReservacionController quedan para fase separada.

## 8. Siguiente fase recomendada

Reservaciones 1-F-F-E-C-C: implementar descargarPDFAction() scoped por hotel_id, sin tocar todavia generarYEnviarReporteCorte() ni renderizadores PDF.
