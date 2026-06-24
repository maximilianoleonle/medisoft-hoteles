# Fase Propietarios 2E - Configuracion UI

## Objetivo

Agregar una primera interfaz operativa para configurar la distribucion de ingresos por propietario sin crear tablas, rutas ni migraciones nuevas.

## Alcance implementado

- Se agrego una seccion `Propietarios` dentro de `Configuracion`.
- La seccion guarda datos en `hotel_configuracion` con la clave `propietarios.distribucion`.
- El formulario existente conserva su `action`, `method`, CSRF y submit global.
- Se agregaron validaciones para:
  - Claves duplicadas de propietario.
  - Al menos un propietario activo.
  - Propietario predeterminado existente y activo.
  - Porcentaje de participacion por propietario entre 0 y 100.
  - Reglas por tipo apuntando a propietarios activos.
  - Asignaciones por habitacion con numero, tipo o id.
- Se mantiene fallback Manolo/Elia si no hay configuracion guardada.

## Estructura del JSON

```json
{
  "version": 1,
  "propietario_default": "elia",
  "propietarios": {
    "manolo": {
      "key": "manolo",
      "nombre": "Manolo",
      "activo": true,
      "participacion_pct": 100
    },
    "elia": {
      "key": "elia",
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
      "numero": "101",
      "propietario_key": "elia"
    }
  ]
}
```

## Archivos modificados

- `src/app/helpers/hotel_config.php`
- `src/app/controllers/ConfiguracionController.php`
- `src/app/views/configuracion/index.php`

## Validaciones realizadas

- `php -l /var/www/html/app/helpers/hotel_config.php`
- `php -l /var/www/html/app/controllers/ConfiguracionController.php`
- `php -l /var/www/html/app/views/configuracion/index.php`
- Prueba manual del normalizador con tres propietarios, reglas y asignaciones.
- Smoke autenticado:
  - Login `303`
  - `/configuracion` `200`
  - HTML contiene nav `#hc-owners`
  - HTML contiene seccion `id="hc-owners"`
  - HTML contiene `owner_config[propietario_default]`

## Pendiente sugerido

- Prueba visual en navegador real cuando haya herramienta disponible.
- En una fase posterior, evaluar si conviene hacer insensibles a mayusculas/minusculas las reglas `tipo contiene`; no se cambio ahora para conservar el comportamiento legacy.
