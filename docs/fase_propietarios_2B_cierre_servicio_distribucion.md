# Fase propietarios 2B - cierre de servicio de distribucion

## Objetivo

Conectar el servicio de distribucion de propietarios a los puntos legacy de Caja y Reportes, preservando la regla actual Manolo/Elia.

## Cambios realizados

- `src/app/services/PropietarioDistribucionService.php`
  - Centraliza la distribucion proporcional por `precio_base`.
  - Conserva la regla legacy: tipos que contienen `manolo` se asignan a Manolo; el resto se asigna al propietario default Elia.
  - Mantiene los formatos de salida usados por Caja y Reportes.

- `src/app/models/Caja.php`
  - `obtenerIngresosPorTipoHabitacion()` ahora delega el reparto al servicio.
  - Se conservaron las consultas existentes.
  - Se conserva `_otros` para ingresos sin reservacion.

- `src/app/controllers/ReportesController.php`
  - `obtenerIngresosPorPropiedad()` ahora delega el reparto al servicio.
  - Se conservaron la consulta por rango, el filtro por usuario y el filtro por hotel.

## No incluido en esta fase

- No se agregan tablas ni migraciones.
- No se cambia la base de datos.
- No se activa configuracion dinamica de propietarios.
- No se modifican PDFs para propietarios dinamicos.
- No se cambia la logica de caja, cortes, reservaciones, check-in o check-out fuera del reemplazo de duplicacion.
- No se toca PWA, offline, IndexedDB ni `/api/sync`.

## Validacion

Sintaxis PHP:

```bash
docker exec medisoft_hoteles_app php -l /var/www/html/app/services/PropietarioDistribucionService.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/models/Caja.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/controllers/ReportesController.php
```

Resultado: sin errores de sintaxis.

Paridad basica en memoria:

- Mixto 100/300
- Solo Manolo
- Solo Elia
- Redondeo
- Sin precios

Resultado: todos los casos coinciden con el algoritmo legacy.

Smoke autenticado en hotel Maximiliano:

- `http://localhost:8080/dashboard` -> 200
- `http://localhost:8080/reportes` -> 200
- `http://localhost:8080/caja/historial` -> 200

## Siguiente fase sugerida

Fase 2C: habilitar lectura de configuracion JSON `propietarios.distribucion` desde `hotel_configuracion`, manteniendo fallback legacy cuando no exista configuracion.
