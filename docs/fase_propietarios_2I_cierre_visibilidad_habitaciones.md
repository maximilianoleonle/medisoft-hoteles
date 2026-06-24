# Fase propietarios 2I - Visibilidad de propietario en habitaciones

## Objetivo

Hacer visible en la operacion diaria que propietario aplica a cada habitacion, usando la misma configuracion multi-dueno que ya alimenta caja y reportes.

## Cambios aplicados

- `HabitacionController` ahora anexa a cada habitacion:
  - `propietario_key`
  - `propietario_nombre`
  - `propietario_participacion_pct`
  - `propietario_es_default`
- La resolucion usa `PropietarioDistribucionService::propietarioParaHabitacion()`, por lo que respeta:
  - asignaciones especificas por habitacion;
  - reglas por tipo;
  - propietario predeterminado;
  - porcentaje configurado por propietario.
- El listado de habitaciones muestra el propietario en la tarjeta compacta.
- El reverso de la tarjeta muestra `Propietario: nombre` y, si aplica, el porcentaje.
- El detalle de habitacion muestra el propietario en:
  - chips del hero;
  - lectura operativa;
  - datos laterales;
  - enlace rapido a `Configuracion > Propietarios por habitacion`.

## Alcance protegido

No se modificaron calculos de caja, calculos de reportes, rutas, permisos, auth, modelos, migraciones, sync ni comportamiento offline.

## Resultado esperado

Recepcion y administracion pueden confirmar visualmente a que propietario pertenece cada habitacion sin abrir reportes. Si necesitan cambiarlo, tienen acceso directo al bloque de configuracion existente.
