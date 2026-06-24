# Fase propietarios 2C - cierre configuracion JSON

## Objetivo

Permitir que el motor de distribucion de propietarios lea una configuracion por hotel desde `hotel_configuracion`, conservando fallback legacy Manolo/Elia cuando no exista configuracion.

## Cambios realizados

- `src/app/services/PropietarioDistribucionService.php`
  - Agrega la clave `propietarios.distribucion`.
  - Agrega `configuracionParaHotel($hotelId)`.
  - Normaliza configuraciones incompletas o invalidas.
  - Si no existe `hotel_config_get()` o falla la lectura, usa la regla legacy.

- `src/app/models/Caja.php`
  - `obtenerIngresosPorTipoHabitacion()` carga la configuracion del hotel actual.
  - Pasa esa configuracion al servicio al crear el resultado y al aplicar movimientos.

- `src/app/controllers/ReportesController.php`
  - `obtenerIngresosPorPropiedad()` carga la configuracion del hotel solicitado.
  - Pasa esa configuracion al servicio al crear el resultado y al aplicar movimientos.

## Formato JSON esperado

La clave sugerida en `hotel_configuracion` es:

- `clave`: `propietarios.distribucion`
- `tipo`: `json`
- `grupo`: `propietarios`

Ejemplo:

```json
{
  "version": 1,
  "propietario_default": "elia",
  "propietarios": {
    "manolo": {
      "nombre": "Manolo",
      "activo": true,
      "participacion_pct": 100
    },
    "elia": {
      "nombre": "Elia",
      "activo": true,
      "participacion_pct": 100
    }
  },
  "reglas_tipo_contiene": {
    "manolo": "manolo"
  },
  "habitaciones": [
    {
      "numero": "102",
      "propietario_key": "manolo"
    }
  ]
}
```

## Importante

El motor ya soporta multiples propietarios, pero los PDFs y algunas vistas todavia renderizan columnas fijas Manolo/Elia.

Hasta completar la fase de render dinamico, la configuracion operativa recomendada debe conservar las claves:

- `manolo`
- `elia`

## Validacion

Sintaxis PHP:

```bash
docker exec medisoft_hoteles_app php -l /var/www/html/app/services/PropietarioDistribucionService.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/models/Caja.php
docker exec medisoft_hoteles_app php -l /var/www/html/app/controllers/ReportesController.php
```

Resultado: sin errores.

Prueba en memoria:

- Fallback legacy Manolo/Elia: OK.
- Configuracion por numero de habitacion: OK.

Smoke autenticado en hotel Maximiliano:

- `http://localhost:8080/dashboard` -> 200
- `http://localhost:8080/reportes` -> 200
- `http://localhost:8080/caja/historial` -> 200

## Siguiente fase sugerida

Fase 2D: adaptar PDFs y vistas de reportes para renderizar propietarios dinamicos en vez de columnas fijas Manolo/Elia.
