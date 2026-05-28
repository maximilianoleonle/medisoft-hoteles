# Reservaciones 1-F-F-E-D-D-A - Auditoria de Reporte.php legacy PDF

## 1. Objetivo

Auditar el flujo legacy de PDF en Reporte.php para decidir si debe scopearse por `hotel_id`, mantenerse como legacy documentado o deprecarse.

## 2. Hallazgo principal

* El flujo legacy esta concentrado en `Reporte.php`.
* No se encontraron llamadas externas a `Reporte::generarPDF()` desde otros PHP del proyecto.
* `ReportesController` usa `getIngresosVsGastos()`, no `obtenerIngresosGastos()`.
* El riesgo real es `Reporte::obtenerIngresosGastos()`, porque consulta `movimientos_caja` sin `hotel_id` y alimenta indirectamente `generarPDFIngresosGastos()`.

## 3. Metodos auditados

* `Reporte::generarPDF()`.
* `Reporte::generarContenidoPDF()`.
* `Reporte::generarPDFIngresosGastos()`.
* `Reporte::obtenerIngresosGastos()`.
* `ReportePDF.php` como renderizador.

## 4. Metodos referenciados pero no encontrados

* `generarPDFProcedencia()`.
* `generarPDFOcupacion()`.
* `generarPDFHabitacionesRentables()`.
* `generarPDFEstancia()`.
* `generarPDFRankingEstados()`.

## 5. Consulta global detectada

* `Reporte::obtenerIngresosGastos()` tiene consultas sobre `movimientos_caja` para ingresos y gastos sin `mc.hotel_id`.
* `categorias_movimientos` queda como catalogo auxiliar.

## 6. Riesgos

* Alto: PDF financiero legacy con datos globales si alguien llama `generarPDF('ingresos-gastos')`.
* Medio: ramas legacy hacia metodos inexistentes pueden romper rutas antiguas.
* Bajo: `ReportePDF.php` y `ReporteCortePDF.php` solo renderizan datos recibidos.

## 7. Recomendacion tecnica

Dividir decision:

* Documentar/deprecar `Reporte::generarPDF()` si no hay ruta activa.
* Si se conserva, scopear primero `Reporte::obtenerIngresosGastos()` con `hotelIdActual()` y `mc.hotel_id = ?`.
* No restaurar metodos `generarPDF*` faltantes todavia.
* Si alguna ruta los necesita, delegar a metodos ya scoped en vez de duplicar logica.

## 8. Que NO tocar

* `ReportesController` funcional.
* Renderizadores PDF.
* `CajaController`.
* `ReservacionController`.
* PWA/offline.
* Sync.
* APIs globales.
* Migraciones/schema.
* Cambios de datos.

## 9. Siguiente fase recomendada

Reservaciones 1-F-F-E-D-D-B: decision/implementacion limitada para `Reporte::obtenerIngresosGastos()`, o documentacion de deprecacion si se confirma que el flujo legacy no se usa.
