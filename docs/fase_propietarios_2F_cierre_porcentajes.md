# Fase Propietarios 2F - Porcentajes por propietario

## Objetivo

Permitir que cada propietario tenga un porcentaje de participacion configurable sin cambiar tablas, rutas ni migraciones.

## Regla implementada

- Cada propietario tiene `participacion_pct`.
- El valor predeterminado es `100`.
- Si el propietario asignado a una habitacion tiene `participacion_pct` menor a `100`, recibe ese porcentaje del monto que le corresponderia.
- El remanente se asigna al `propietario_default`.
- El propietario predeterminado conserva 100% de lo que ya le corresponda directamente para evitar perdidas o redistribuciones circulares.

Ejemplo:

```json
{
  "propietario_default": "elia",
  "propietarios": {
    "manolo": {
      "nombre": "Manolo",
      "activo": true,
      "participacion_pct": 70
    },
    "elia": {
      "nombre": "Elia",
      "activo": true,
      "participacion_pct": 100
    }
  }
}
```

Si una habitacion de Manolo produce `$1,000.00`, Manolo recibe `$700.00` y Elia recibe `$300.00`.

## Archivos modificados

- `src/app/services/PropietarioDistribucionService.php`
- `src/app/helpers/hotel_config.php`
- `src/app/views/configuracion/index.php`
- `docs/fase_propietarios_2C_cierre_configuracion_json.md`
- `docs/fase_propietarios_2E_cierre_configuracion_ui.md`

## Notas

- La compatibilidad legacy se conserva porque Manolo y Elia quedan con `100%`.
- El campo en Configuracion acepta valores de `0` a `100`, con decimales.
- Los reportes y cortes no requieren cambios adicionales porque consumen el servicio central.
