# Reservaciones 1-F-F-E-D-B - Cierre PDF principal de ingresos/gastos

## 1. Objetivo

Documentar el cierre tecnico del scope por hotel_id en el PDF principal de ingresos/gastos.

## 2. Que se logro

- exportarIngresosGastosPdf() ahora resuelve/pasa hotel_id.
- obtenerIngresosPorPropiedad() ahora acepta hotel_id.
- movimientos_caja se filtra por mc.hotel_id = ?.
- reservaciones se valida con r.hotel_id = mc.hotel_id.
- reservacion_habitaciones se valida con rh.hotel_id = r.hotel_id.
- habitaciones se valida con hab.hotel_id = rh.hotel_id.
- El PDF principal de ingresos/gastos ya consume datos de propiedad/habitacion scoped.
- Se conserva la logica original de reparto Manolo/Elia.

## 3. Archivo modificado

- src/app/controllers/ReportesController.php

## 4. Que NO se toco

- exportarIngresosGastosUsuarioPdf().
- exportarIngresosTotalesPdf().
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
- GET /reportes/exportar-pdf?tipo=ingresos-gastos respondio 303 a /login sin error 500.
- Confirmacion de que no se tocaron renderizadores/PDFs de usuario/totales/PWA/Sync/APIs.
- 0 nuevos hotel_id NULL segun herramientas.

## 6. Riesgos pendientes

- exportarIngresosGastosUsuarioPdf() sigue pendiente.
- exportarIngresosTotalesPdf() sigue pendiente.
- Reporte.php legacy PDF sigue pendiente.
- Metodos PDF no encontrados siguen pendientes.
- Exportaciones de ReservacionController quedan para fase separada.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-D-C: implementar PDFs de ingresos/gastos por usuario y totales scoped por hotel_id, sin tocar todavia Reporte.php legacy.
