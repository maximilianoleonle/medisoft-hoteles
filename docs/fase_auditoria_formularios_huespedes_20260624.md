# Auditoria de formularios de huespedes - 2026-06-24

## Alcance

- Vistas revisadas:
  - `src/app/views/huespedes/crear.php`
  - `src/app/views/huespedes/editar.php`
- Controlador revisado:
  - `src/app/controllers/HuespedController.php`
- Revision limitada al contrato superficial de formularios y recuperacion despues de errores.
- No se modificaron rutas, modelos, permisos, migraciones, base de datos, auth ni archivos PWA/offline.
- No se cambiaron `action`, `method`, `name`, CSRF ni hidden inputs existentes.
- No se anidaron formularios.

## Contrato detectado

Campos base del formulario de alta/edicion:

- `nombre_completo`
- `telefono`
- `email`
- `procedencia_estado`
- `procedencia_ciudad`
- `notas`
- `extras[...]` para campos personalizados del hotel
- `identificacion_archivo` cuando la configuracion lo muestra

Campos adicionales del alta:

- `vehiculos[index][...]`
- `vehiculos[index][extras][...]`
- `documentos_huesped[tipo][]`
- `documentos_huesped[titulo][]`
- `documentos_huesped[descripcion][]`
- `documentos_huesped[archivo][]`

Formulario auxiliar en edicion:

- `formAgregarVehiculo`
- `huesped_id`
- campos de vehiculo segun configuracion del hotel

## Problemas encontrados

1. Edicion de huesped sin recuperacion de datos.

   `actualizarAction()` validaba y redirigia al formulario de edicion, pero no guardaba `old_input` ni `form_errors`. Si fallaba telefono, email, identificacion u otro campo requerido, el usuario perdia lo capturado y solo veia un mensaje general.

2. Crear huesped no mostraba errores por campo.

   `guardarAction()` ya guardaba errores por campo, pero `huespedes/crear.php` no los mostraba junto a los inputs principales.

3. Vehiculos del alta no se restauraban despues de error.

   Si el alta fallaba por placas repetidas u otro error de vehiculo, la vista volvia con un solo vehiculo vacio y no conservaba lo escrito.

4. Edicion mezclaba visibilidad de vehiculos con notas.

   En `huespedes/editar.php`, la seccion de vehiculos estaba dentro de la condicion de visibilidad de `notas`. Si el hotel ocultaba notas, tambien podia ocultar la captura de vehiculos.

## Correcciones aplicadas

- `HuespedController::actualizarAction()` ahora:
  - guarda `old_input` en errores de validacion;
  - guarda `form_errors` por campo;
  - limpia `old_input` despues de actualizar exitosamente;
  - conserva datos y errores si ocurre una excepcion.
- `huespedes/crear.php` ahora muestra errores en:
  - `nombre_completo`
  - `telefono`
  - `email`
  - `procedencia_estado`
  - `procedencia_ciudad`
  - `notas`
  - campos personalizados via `extras[...]`
  - campos de vehiculo por indice.
- `huespedes/crear.php` ahora reconstruye los vehiculos desde `old_input['vehiculos']`.
- `huespedes/editar.php` ahora:
  - usa `old()` en campos principales;
  - restaura extras desde `old_input['extras']`;
  - muestra errores por campo;
  - separa la condicion de vehiculos de la condicion de notas.

## Validaciones ejecutadas

```bash
docker compose exec -T app php -l app/controllers/HuespedController.php
docker compose exec -T app php -l app/views/huespedes/crear.php
docker compose exec -T app php -l app/views/huespedes/editar.php
```

Resultado: sin errores de sintaxis.

Validaciones adicionales:

- `git diff --check` sobre los archivos tocados: sin errores.
- Estructura de formularios:
  - `huespedes/crear.php`: 1 formulario, balanceado, sin anidamiento.
  - `huespedes/editar.php`: 2 formularios, balanceados, sin anidamiento.

## Riesgo residual

- Los campos personalizados dependen de la configuracion activa del hotel; si se agregan nuevos tipos de input al catalogo, conviene repetir esta auditoria.
- La auditoria no cambio reglas de negocio de huespedes, vehiculos, documentos ni estacionamiento; solo se corrigio recuperacion visual y mapeo de errores.
