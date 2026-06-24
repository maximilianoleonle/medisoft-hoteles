# Fase propietarios 2D - cierre render dinamico

## Objetivo

Adaptar las salidas de reportes/PDF para que puedan mostrar propietarios dinamicos, en vez de depender solo de columnas fijas Manolo/Elia.

## Cambios realizados

- `src/app/services/PropietarioDistribucionService.php`
  - Agrega `resumenPropietarios()`.
  - Agrega `nombrePropietario()`.
  - El resumen excluye claves internas como `_otros`.
  - Calcula total, cantidad, habitaciones y etiqueta visible por propietario.

- `src/app/controllers/ReportesController.php`
  - La seccion `generarSeccionPropiedadesPDF()` ahora renderiza tabla dinamica por propietario.
  - Muestra propietario, ingresos, reservas, participacion y desglose por metodo de pago.
  - El metodo legacy Manolo/Elia queda aislado como respaldo no usado por las llamadas actuales.

- `src/app/views/reportes/ReportePDF.php`
  - `generarSeccionPropiedades()` ahora renderiza propietarios dinamicos.
  - El metodo legacy queda disponible como `generarSeccionPropiedadesLegacy()`.

- `src/app/includes/ReporteCortePDF.php`
  - Los bloques de habitaciones por propietario ahora intentan render dinamico.
  - Si el resumen dinamico no puede generarse, conserva el bloque legacy.

- `src/includes/ReporteCortePDF.php`
  - Se replica el mismo comportamiento para la version legacy.

## No incluido en esta fase

- No se modifica base de datos.
- No se agregan migraciones.
- No se cambian rutas.
- No se cambia la formula de reparto.
- No se toca PWA, offline, IndexedDB ni `/api/sync`.
- No se agrega aun interfaz visual para editar propietarios.

## Validacion

Sintaxis PHP:

```bash
docker exec medisoft_hoteles_app php -l /var/www/html/app/services/PropietarioDistribucionService.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/controllers/ReportesController.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/views/reportes/ReportePDF.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/includes/ReporteCortePDF.php
docker exec medisoft_hoteles_app php -l /var/www/html/includes/ReporteCortePDF.php
```

Resultado: sin errores.

Prueba en memoria:

- Resumen con 3 propietarios: OK.
- Total acumulado esperado: OK.

Smoke autenticado en hotel Maximiliano:

- `http://localhost:8080/dashboard` -> 200
- `http://localhost:8080/reportes` -> 200
- `http://localhost:8080/caja/historial` -> 200

## Siguiente fase sugerida

Fase 2E: crear interfaz de configuracion para propietarios y asignacion de habitaciones, guardando JSON en `hotel_configuracion` bajo la clave `propietarios.distribucion`.
