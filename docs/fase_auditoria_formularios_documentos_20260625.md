# Auditoria de formularios de documentos y adjuntos - 2026-06-25

## Alcance

Bloque revisado:

- `src/app/controllers/DocumentoController.php`
- `src/app/views/documentos/subir.php`
- `src/app/views/documentos/editar.php`
- `src/app/views/documentos/index.php`
- `src/app/views/documentos/ver.php`

El enfoque fue revisar carga de adjuntos, edicion de metadata, errores de archivo y conservacion de datos capturados.

## Problemas encontrados

- El controlador ya guardaba `old_input` y `form_errors` cuando fallaba subir o editar documentos, pero las vistas no mostraban esos errores junto al campo correspondiente.
- En subida, errores del servidor sobre archivo, tipo, titulo, descripcion, etiquetas o relacion quedaban como mensaje general.
- En edicion, errores de metadata quedaban como mensaje general y no marcaban el campo afectado.
- El mapeo de errores de archivo no contemplaba de forma clara casos frecuentes como extension, formato, carga, peso o storage.
- Si el archivo fallaba en servidor y despues el usuario seleccionaba un archivo valido, el estado visual de error podia quedarse visible si no se limpiaba explicitamente.

## Correcciones aplicadas

- Se reforzo `erroresCamposDocumento(...)` para mapear errores de archivo por extension, formato, carga, peso y storage.
- `documentos/subir.php` ahora lee `$layoutFieldErrors` y muestra errores inline para:
  - `archivo`
  - `titulo`
  - `descripcion`
  - `etiquetas`
  - `documento_tipo_id`
  - `relacion`
- `documentos/editar.php` ahora lee `$layoutFieldErrors` y muestra errores inline para:
  - `titulo`
  - `descripcion`
  - `etiquetas`
  - `documento_tipo_id`
- Se agregaron clases de error visual y atributos `aria-invalid` / `aria-describedby`.
- La validacion cliente del archivo ahora limpia tambien el error visual proveniente del servidor al seleccionar un archivo valido.

## Formularios verificados

- Subir documento.
- Editar informacion del documento.
- Acciones de estado en detalle documental: revisadas, sin campos editables que requieran `old_input`.
- Filtros de listado: GET, fuera del patron de recuperacion POST.

## Fuera de alcance

No se modificaron:

- Rutas.
- Modelos.
- Base de datos ni migraciones.
- Permisos ni auth.
- Storage privado ni reglas de descarga.
- Auditoria de descargas.
- PWA, service worker, IndexedDB, cache names ni `/api/sync`.

## Validacion

- `php -l` en controlador y vistas modificadas dentro del contenedor `medisoft_hoteles_app`.
- Revision de formularios: se mantienen `method`, `action`, `name`, `enctype`, CSRF e inputs ocultos existentes.
- `git diff --check` sin errores reales; solo avisos esperados de CRLF en Windows.
