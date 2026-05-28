# Reservaciones 1-F-F-E-D-D-B-PREP - Decision sobre Reporte.php legacy PDF

## 1. Contexto

* `Reporte::generarPDF()` existe como entrypoint legacy.
* No se encontraron llamadas externas desde otros PHP del proyecto.
* `ReportesController` ya usa metodos modernos/scoped para los PDFs principales.
* El riesgo real es `Reporte::obtenerIngresosGastos()`, porque consulta `movimientos_caja` sin `hotel_id`.

## 2. Opciones tecnicas

### Opcion A: deprecar/documentar `Reporte::generarPDF()`

Documentar el flujo como legacy y evitar nuevas dependencias sobre ese entrypoint.

### Opcion B: mantenerlo como legacy documentado sin tocarlo

Conservar el comportamiento actual, dejando registrada la deuda tecnica y el riesgo si el flujo vuelve a usarse.

### Opcion C: conservarlo y scopear unicamente `Reporte::obtenerIngresosGastos()`

Aplicar un scope minimo con `hotelIdActual()` y `mc.hotel_id = ?`, sin restaurar ni reescribir el resto del flujo legacy.

## 3. Recomendacion

* No restaurar metodos `generarPDF*` faltantes.
* No duplicar logica que ya existe scoped en `ReportesController`.
* Si se conserva el flujo legacy, implementar solo `obtenerIngresosGastos()` scoped por `hotel_id`.
* Mantener `generarPDF()` y `generarContenidoPDF()` sin cambios salvo documentacion futura.

## 4. Riesgos

* Si alguien llama `generarPDF('ingresos-gastos')`, podria generar datos globales mientras `obtenerIngresosGastos()` siga sin scope.
* Metodos `generarPDF*` faltantes pueden romper rutas antiguas si se invocan.
* Scopear todo el flujo legacy puede duplicar logica ya corregida en `ReportesController`.
* Deprecar sin verificar rutas puede ocultar deuda tecnica.

## 5. Siguiente fase recomendada

Reservaciones 1-F-F-E-D-D-B: scope minimo de `Reporte::obtenerIngresosGastos()`, solo si se decide conservar el flujo legacy.

## 6. Que NO tocar todavia

* `ReportesController` funcional.
* Renderizadores PDF.
* `CajaController`.
* `ReservacionController`.
* PWA/offline.
* Sync.
* APIs globales.
* Migraciones/schema.
* Cambios de datos.
