# Reservaciones 1-F-F-E-D-C - Cierre PDFs de usuario y totales

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en los PDFs de ingresos/gastos por usuario y de ingresos totales.

## 2. Que se logro

- exportarIngresosGastosUsuarioPdf() ahora usa hotelIdActual().
- exportarIngresosGastosUsuarioPdf() filtra movimientos_caja con mc.hotel_id = ?.
- exportarIngresosGastosUsuarioPdf() valida reservaciones, reservacion_habitaciones y habitaciones por hotel_id.
- exportarIngresosTotalesPdf() ahora usa hotelIdActual().
- exportarIngresosTotalesPdf() filtra movimientos_caja con mc.hotel_id = ?.
- exportarIngresosTotalesPdf() valida reservaciones, reservacion_habitaciones y habitaciones por hotel_id.
- Ambos metodos pasan hotel_id a obtenerIngresosPorPropiedad().
- usuarios queda como join auxiliar.
- Se conserva la logica original de los PDFs.

## 3. Archivo modificado

- src/app/controllers/ReportesController.php

## 4. Que NO se toco

- exportarIngresosGastosPdf().
- Reporte.php legacy.
- ReportePDF.php.
- ReporteCortePDF.php.
- CajaController.
- ReservacionController.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- base de datos.

## 5. Validaciones realizadas

- php -l en ReportesController.php.
- git diff --check.
- verificar_estado.php PASS.
- preflight_hotel_id.php PASS.
- GET de PDFs por usuario/totales respondio 303 a login sin error 500.
- Confirmacion de que no se tocaron renderizadores/Reporte.php/PWA/Sync/APIs.
- 0 nuevos hotel_id NULL segun herramientas.

## 6. Riesgos pendientes

- Reporte.php legacy PDF sigue pendiente.
- Metodos PDF no encontrados siguen pendientes.
- Exportaciones de ReservacionController quedan para fase separada.
- PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-D-D-A: auditoria de Reporte.php legacy PDF para decidir si se scopea o se documenta/depreca.
