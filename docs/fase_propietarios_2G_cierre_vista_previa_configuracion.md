# Fase Propietarios 2G - Vista previa en Configuracion

## Objetivo

Reducir errores de captura al configurar propietarios mostrando una vista previa clara antes de guardar cambios o generar reportes.

## Alcance implementado

- Se agrego un resumen dentro de `Configuracion > Propietarios`.
- El resumen muestra:
  - Duenos activos.
  - Dueno predeterminado.
  - Numero de reglas por tipo.
  - Numero de asignaciones especificas.
- Se agrego una vista previa del reparto por dueno.
- La vista previa se actualiza en vivo cuando el usuario edita:
  - Clave.
  - Nombre.
  - Participacion.
  - Estado activo.
  - Dueno predeterminado.
  - Reglas.
  - Asignaciones.

## Comportamiento

- Si un dueno tiene `participacion_pct` menor a `100`, la vista muestra que el remanente ira al dueno predeterminado.
- Si no hay duenos activos, se muestra un estado vacio inline.
- No se agregan rutas, tablas, migraciones ni llamadas nuevas.
- El formulario existente conserva `action`, `method`, CSRF y submit global.

## Archivos modificados

- `src/app/views/configuracion/index.php`

## Validacion esperada

- `php -l /var/www/html/app/views/configuracion/index.php`
- Smoke autenticado de `/configuracion#hc-owners`.
- Revision visual sin overflow horizontal en desktop y mobile.
