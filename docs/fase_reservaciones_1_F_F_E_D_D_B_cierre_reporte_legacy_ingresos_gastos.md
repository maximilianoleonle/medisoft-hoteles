# Reservaciones 1-F-F-E-D-D-B-CIERRE - Reporte legacy ingresos/gastos

## 1. Objetivo

Documentar el cierre tecnico del scope minimo aplicado al flujo legacy de PDF en `Reporte.php`.

## 2. Que se logro

* `Reporte::obtenerIngresosGastos()` ahora usa `hotelIdActual()`.
* La consulta de ingresos filtra `movimientos_caja` con `mc.hotel_id = ?`.
* La consulta de gastos filtra `movimientos_caja` con `mc.hotel_id = ?`.
* `categorias_movimientos` queda como catalogo auxiliar.
* El camino legacy de ingresos-gastos queda blindado contra datos globales si alguien llama `generarPDF('ingresos-gastos')`.

## 3. Archivo modificado

* `src/app/models/Reporte.php`.

## 4. Que NO se toco

* `Reporte::generarPDF()`.
* `Reporte::generarContenidoPDF()`.
* `Reporte::generarPDFIngresosGastos()`.
* Metodos `generarPDF*` faltantes.
* `ReportesController`.
* Renderizadores PDF.
* `CajaController`.
* `ReservacionController`.
* PWA/offline.
* Sync.
* APIs globales.
* Migraciones.
* Schema.
* Base de datos.

## 5. Validaciones realizadas

* `php -l` en `Reporte.php`.
* `git diff --check`.
* `verificar_estado.php` PASS.
* `preflight_hotel_id.php` PASS.
* Confirmacion de `mc.hotel_id = ?` en ingresos y gastos.
* Confirmacion de que no se tocaron `ReportesController`/renderizadores/PWA/Sync/APIs.
* 0 nuevos `hotel_id` NULL segun herramientas.

## 6. Riesgos pendientes

* `Reporte::generarPDF()` sigue siendo legacy.
* Metodos `generarPDF*` faltantes siguen sin restaurarse.
* El flujo legacy debe mantenerse documentado/deprecado si no hay uso activo.
* Exportaciones de `ReservacionController` siguen pendientes.
* PWA/offline, Sync y APIs siguen fuera de scope.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-E-E-A: auditoria de exportaciones de `ReservacionController`, sin implementacion directa.
