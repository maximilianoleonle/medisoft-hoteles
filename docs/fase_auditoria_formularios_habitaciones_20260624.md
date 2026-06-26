# Fase Auditoria Formularios Habitaciones 2026-06-24

Objetivo: revisar el contrato entre las vistas de crear/editar habitacion y `HabitacionController::guardarAction()` / `actualizarAction()`.

## Alcance

- `src/app/views/habitaciones/crear.php`.
- `src/app/views/habitaciones/editar.php`.
- `src/app/controllers/HabitacionController.php`.

No se cambiaron `action`, `method`, `name`, CSRF, hidden inputs ni ubicacion del submit.

## Campos esperados por backend

Ambas vistas quedan con los campos que leen `guardarAction()` y `actualizarAction()`:

- `numero`.
- `tipo`.
- `piso`.
- `precio_base`.
- `capacidad_personas`.
- `camas_matrimoniales`.
- `camas_individuales`.
- `caracteristicas_especiales[]`.
- `caracteristicas`.
- `fotos[]`.
- `activa`.

## Hallazgos corregidos

### Edicion no recuperaba datos capturados

`actualizarAction()` guardaba `old_input` al fallar, pero `editar.php` pintaba varios valores desde `$habitacion` en lugar de usar el intento anterior.

Se corrigio para recuperar:

- Numero.
- Tipo.
- Piso.
- Precio.
- Capacidad.
- Camas matrimoniales.
- Camas individuales.
- Caracteristicas.
- Caracteristicas especiales.
- Estado activo/inactivo.

### Errores por campo no visibles

El controlador ya clasificaba errores para varios campos, pero algunas vistas no los mostraban junto al input.

Se agrego salida visible para:

- `tipo`.
- `piso`.
- `precio_base`.
- `camas_matrimoniales`.
- `camas_individuales`.
- `fotos[]`.

### Edicion tenia contrato incompleto para fotos

`actualizarAction()` aceptaba `fotos[]`, la vista tenia `multipart/form-data` y el JavaScript buscaba `#fotos-input`, pero el input no existia en `editar.php`.

Se agrego el control de carga con:

- `name="fotos[]"`.
- `id="fotos-input"`.
- `multiple`.
- `accept="image/*"`.

Tambien se corrigio el cierre HTML del bloque de carga para mantener el `if/else` balanceado dentro de la tarjeta.

## Validaciones ejecutadas

- `docker compose exec -T app php -l app/views/habitaciones/crear.php`.
- `docker compose exec -T app php -l app/views/habitaciones/editar.php`.
- Inventario automatico de `name="..."`: sin campos faltantes.
- Conteo de formularios estaticos: `crear.php` 1/1 y `editar.php` 1/1.
- `git diff --check` sin errores. Solo mostro warnings de finales de linea en Windows.

## Resultado

El contrato de formularios de habitaciones queda consistente para crear y editar, con errores visibles por campo y recuperacion de datos capturados despues de fallos de validacion.
