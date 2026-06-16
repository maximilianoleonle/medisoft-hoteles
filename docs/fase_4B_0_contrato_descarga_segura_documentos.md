# Fase 4B-0 - Contrato descarga segura de documentos

## Estado

Estado: `CONTRATO_4B_DESCARGA_SEGURA_COMPLETADO`.

Esta subfase es solo documental. No implementa rutas, no lee archivos privados, no cambia
modelos, no agrega migraciones, no escribe en base de datos y no toca `/api/sync`.

## Contexto

Fase 4A dejo cerrado el Centro Documental Base:

- tablas `documento_tipos`, `documentos` y `documento_entidades`;
- listado y detalle read-only;
- carga segura con CSRF hacia `STORAGE_PATH/documentos`;
- documentos privados sin URL publica;
- sin descarga, edicion ni borrado.

El siguiente paso natural es permitir descarga autenticada de archivos privados, pero esa
accion es sensible porque puede exponer documentos de hotel, proveedor, compra, CxP,
huesped o reservacion si no se valida correctamente el contexto.

## Objetivo de Fase 4B

Agregar descarga segura autenticada de documentos ya cargados en Centro Documental,
manteniendo el almacenamiento privado y sin habilitar enlaces publicos.

## Alcance permitido para 4B futura

- Ruta GET autenticada para descargar un documento del hotel actual.
- Validacion de sesion, contexto hotelero y modulo relacionado.
- Validacion de `documentos.hotel_id` contra hotel actual.
- Validacion de estado del documento (`activo` inicialmente).
- Resolucion segura de archivo con `realpath`.
- Raiz permitida unica: `realpath(STORAGE_PATH . '/documentos')`.
- Headers privados:
  - `Content-Type` desde metadata validada;
  - `Content-Disposition` seguro;
  - `X-Content-Type-Options: nosniff`;
  - `Cache-Control: private, max-age=0, must-revalidate`;
  - `Content-Length`.
- Auditoria opcional/gradual de descarga sensible con `AuditService`.
- Mensajes claros si el documento no existe, no pertenece al hotel, esta archivado,
  falta el archivo fisico o la ruta no esta dentro del storage permitido.
- Pruebas HTTP sin sesion y con sesion.
- SQL read-only para confirmar que no se crean pagos, Caja ni cambios CxP.

## Prohibido

- Enlaces publicos o tokens publicos de documentos.
- Servir archivos desde `public_html/uploads`.
- Exponer `storage_path` o `nombre_archivo` en vistas.
- Descargar documentos de otro hotel.
- Descargar documentos con entidad cruzada de otro hotel.
- Crear pagos, abonos, Caja o movimientos financieros.
- Editar, borrar, archivar o restaurar documentos.
- Tocar PWA/offline/cache/IndexedDB.
- Tocar `/api/sync`.
- Hacer migraciones destructivas.
- Borrar archivos fisicos.
- Hacer push.

## Patron tecnico recomendado

Usar como referencia segura `ReporteLinkController::descargarInternoAction()` y su
resolucion por `realpath`, pero con estas diferencias:

- no usar acceso publico por token;
- no limitar a PDF, porque Centro Documental permite PDF/JPG/PNG/WEBP;
- no inferir permisos de reportes, sino del guard documental vigente;
- no devolver rutas internas al usuario;
- resolver el archivo desde `documentos.storage_path` como path relativo bajo
  `STORAGE_PATH`.

## Diseño propuesto

Ruta futura:

```text
GET /documentos/{id}/descargar
```

Controlador futuro:

```text
DocumentoController::descargarAction()
```

Modelo futuro:

```text
Documento::buscarDescargablePorIdHotel(int $id, int $hotelId): ?array
Documento::resolverRutaPrivada(array $documento): ?string
```

Reglas:

- Buscar por `id + hotel_id`.
- Rechazar si `estado` no es `activo`.
- Construir ruta absoluta desde `STORAGE_PATH . '/' . storage_path`.
- Normalizar separadores.
- Confirmar `realpath($ruta)` existe, es archivo y vive dentro de
  `realpath(STORAGE_PATH . '/documentos')`.
- Usar `nombre_original` para el `filename` de descarga, saneado para header.
- Usar `mime_type` persistido solo si pertenece a la lista permitida actual.
- No leer ni imprimir contenido antes de enviar headers.

## Definition of Done

- Ruta GET documentada e implementada con `requireAuth`.
- Sin POST nuevo.
- Sin enlaces publicos.
- Sin exposicion de rutas internas.
- HTTP sin sesion redirige a login.
- Documento inexistente o de otro hotel no se descarga.
- Documento con archivo fisico faltante muestra error controlado.
- Documento valido descarga con headers privados.
- `php -l` en archivos PHP tocados.
- Health/preflights siguen en PASS con warnings permitidos.
- SQL read-only confirma cero cambios en Caja, pagos, abonos, CxP y `/api/sync`.
- QA manual confirma descarga de PDF e imagen validos del hotel actual.

## Rollback esperado

- Revertir el commit de implementacion de 4B.
- No borrar registros de `documentos`.
- No borrar archivos de `src/storage/documentos/`.
- No tocar migraciones, salvo que una fase futura agregue tabla/columna con contrato
  separado.

## Riesgos

- Exposicion cross-hotel si se omite `hotel_id`.
- Path traversal si no se usa `realpath` con raiz permitida.
- Cache del navegador si faltan headers privados.
- Descarga de archivo inexistente si metadata y storage se desincronizan.
- Mezcla accidental con descarga publica de reportes si se reutiliza token publico.

## Siguiente cola recomendada

`[COLA_4B_A_DESCARGA_SEGURA_DOCUMENTOS]`

Solo debe implementarse si se acepta este contrato. No avanzar a edicion, borrado,
links publicos, Caja, pagos, abonos, Fase 3D ni `/api/sync`.

## Resultado Fase 4B-A - Descarga segura autenticada

Estado: `DESCARGA_SEGURA_4B_VALIDADA_MANUALMENTE`.

Implementacion:

- Ruta agregada: `GET /documentos/{id}/descargar`.
- Controlador: `DocumentoController::descargarAction()`.
- Modelo:
  - `Documento::buscarDescargablePorIdHotel()`;
  - `Documento::resolverRutaPrivada()`.
- Vistas actualizadas:
  - `src/app/views/documentos/index.php`;
  - `src/app/views/documentos/ver.php`.

Reglas aplicadas:

- La ruta pasa por `requireAuth`, contexto hotelero y modulo relacionado.
- La busqueda usa `id + hotel_id`.
- Solo descarga documentos `activo`.
- El archivo se resuelve con `realpath`.
- La raiz permitida es `realpath(STORAGE_PATH . '/documentos')`.
- No se expone `storage_path` ni `nombre_archivo`.
- No se crean links publicos.
- En 4B-A no hay POST nuevo.
- En 4B-A no hay edicion ni borrado.
- No se toca Caja, pagos, abonos, PWA/offline ni `/api/sync`.
- No se agrego auditoria de descarga en esta fase para mantener la descarga sin
  escrituras DB.

Verificacion automatica:

- `php -l` limpio en controlador, modelo, vistas documentales y rutas.
- `health_check_fase_1a.php`: PASS con warnings conocidos.
- `preflight_compras_minimas.php`: PASS con warnings conocidos.
- `preflight_recepcion_compras.php`: PASS con warnings conocidos.
- HTTP sin sesion en `/documentos/1/descargar`: `303` a login.
- HTTP con sesion Los Cedros en `/documentos/1/descargar`: `200`, `Content-Type:
  application/pdf`, `Content-Disposition: attachment`,
  `X-Content-Type-Options: nosniff`, `Content-Length: 132`.
- Archivo descargado de `documentos.id=1` conserva SHA256
  `18B0855BC03494BD6C3059AC14BF342366B7F3DD598C118FA98CF91F987EAC64`, coincidente
  con `documentos.sha256`.
- HTTP con sesion Los Cedros en `/documentos/3/descargar`: `303` a `/documentos`
  porque pertenece a otro hotel.
- La vista de detalle muestra `Descargar` y `Descarga privada`, sin `storage_path` ni
  `nombre_archivo`.
- Conteos read-only post-verificacion: `documento_tipos=6`, `documentos=3`,
  `documento_entidades=1`, `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`,
  `logs_auditoria=29`.

Nota de headers:

- El controlador emite headers privados de descarga.
- La capa global del sistema mantiene `Cache-Control: no-cache, no-store,
  must-revalidate`, que es mas restrictivo para documentos sensibles.

QA manual completada:

El usuario reporto `QA_MANUAL_COMPLETADA_4B_A` despues de probar la descarga en
navegador. La descarga segura queda validada manualmente sin autorizar edicion, borrado,
links publicos, pagos, abonos, Caja ni `/api/sync`.

Siguiente paso:

No avanzar a edicion, borrado, links publicos ni auditoria de descargas sin nueva
autorizacion explicita de fase.

## Resultado Fase 4B-B - Auditoria de descargas documentales

Estado: `AUDITORIA_DESCARGAS_4B_B_VALIDADA_MANUALMENTE`.

Objetivo:

Registrar trazabilidad minima de descargas de documentos privados sin cambiar el
contrato de almacenamiento ni exponer rutas internas.

Implementacion:

- `DocumentoController::descargarAction()` registra intentos bloqueados cuando el
  documento solicitado no esta disponible para el hotel actual.
- `DocumentoController::descargarAction()` registra intentos bloqueados cuando la
  metadata existe pero el archivo privado no se puede resolver.
- `DocumentoController::servirArchivoPrivado()` registra `documentos.descargado`
  antes de emitir el archivo.
- La auditoria usa `AuditService::record()` y es tolerante a fallos: si no se puede
  escribir en `logs_auditoria`, la descarga no se rompe.

Eventos:

- `documentos.descargado`: descarga exitosa desde storage privado.
- `documentos.descarga_bloqueada`: documento no disponible, archivo privado no
  disponible o archivo no legible.

Datos registrados:

- `hotel_id`.
- `usuario_id` resuelto de la sesion actual.
- `entidad_tipo = documento`.
- `entidad_id = documento_id`.
- Motivo para bloqueos.
- Metadata segura: `nombre_original`, MIME, tamano, estado y marca de storage privado.

Exclusiones:

- No se registra `storage_path`.
- No se registra `nombre_archivo`.
- No hay nuevas rutas.
- En 4B-B no hay POST nuevo.
- No hay descargas publicas ni tokens.
- No hay edicion ni borrado.
- No se toca Caja, pagos, abonos, PWA/offline ni `/api/sync`.

Verificacion automatica:

- `php -l` limpio en `DocumentoController.php` y `health_check_fase_1a.php`.
- `health_check_fase_1a.php`: PASS con warnings conocidos; detecta ruta documental
  segura y auditoria Fase 4B-B.
- `preflight_compras_minimas.php`: PASS con warnings conocidos.
- `preflight_recepcion_compras.php`: PASS con warnings conocidos.
- HTTP con sesion Los Cedros en `/documentos/1/descargar`: `200`.
- HTTP con sesion Los Cedros en `/documentos/3/descargar`: `303` a `/documentos`.
- Auditoria generada: `documentos.descargado=1`,
  `documentos.descarga_bloqueada=1`.
- Hash SHA256 del archivo descargado `documentos.id=1`:
  `18B0855BC03494BD6C3059AC14BF342366B7F3DD598C118FA98CF91F987EAC64`.
- Conteos de control: `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`.
- `git diff --check`: OK.

QA manual completada:

El usuario reporto que la auditoria de descargas funciona. La Fase 4B-B queda validada
manualmente sin autorizar edicion, borrado, links publicos, Caja, pagos, abonos ni
`/api/sync`.

## Fase 4B-C - Contrato de edicion controlada de metadata documental

Estado: `METADATA_DOCUMENTAL_4B_C_A_VALIDADA_MANUALMENTE`.

Objetivo:

Permitir en una fase posterior editar solo metadata segura de documentos existentes,
sin reemplazar archivos, sin mover storage, sin borrar documentos y sin crear links
publicos.

Alcance permitido futuro:

- Editar `titulo`.
- Editar `descripcion`.
- Editar `etiquetas`.
- Editar `documento_tipo_id` solo si el tipo esta activo y pertenece al hotel actual o
  es global.
- Cambiar `estado` solo entre `activo` y `archivado` si se autoriza explicitamente en
  la implementacion.
- Registrar auditoria con `AuditService::record()`.

Fuera de alcance:

- Reemplazar archivo fisico.
- Cambiar `storage_path`.
- Cambiar `nombre_archivo`.
- Cambiar `hotel_id`.
- Cambiar `sha256`, `mime_type` o `size_bytes`.
- Borrado fisico.
- Baja logica `eliminado` sin contrato separado.
- Links publicos.
- Caja, pagos, abonos, Fase 3D, PWA/offline y `/api/sync`.

Reglas de seguridad:

- Toda edicion debe requerir autenticacion, contexto hotelero y modulo relacionado.
- Toda busqueda debe usar `id + hotel_id`.
- Debe usar CSRF en cualquier POST.
- Debe validar longitudes y limpiar texto.
- No debe mostrar ni aceptar `storage_path` ni `nombre_archivo`.
- Debe comparar antes/despues para auditar solo cambios reales.
- Si no hay cambios reales, debe responder sin escribir auditoria ruidosa.

Definition of Done futura:

- GET de formulario de metadata protegido.
- POST de actualizacion con CSRF.
- Validaciones centrales en modelo o servicio.
- Auditoria `documentos.metadata_actualizada` con campos antes/despues seguros.
- Sin cambios en archivo fisico.
- Sin nuevas rutas publicas.
- `php -l`, health checker, prueba POST sin sesion bloqueada, prueba CSRF y QA manual.

## Resultado Fase 4B-C-A - Edicion controlada de metadata

Implementacion:

- Rutas agregadas:
  - `GET /documentos/{id}/editar`;
  - `POST /documentos/{id}/actualizar`.
- Controlador:
  - `DocumentoController::editarAction()`;
  - `DocumentoController::actualizarAction()`.
- Modelo:
  - `Documento::actualizarMetadata()`.
- Vista:
  - `src/app/views/documentos/editar.php`.

Reglas aplicadas:

- Busqueda por `id + hotel_id`.
- POST con CSRF.
- Metadata editable limitada a `titulo`, `descripcion`, `etiquetas` y
  `documento_tipo_id`.
- Tipo documental validado por hotel actual o tipo global activo.
- No se editan archivo fisico, `storage_path`, `nombre_archivo`, `sha256`,
  `mime_type`, `size_bytes`, `hotel_id` ni vinculos.
- No se agregan descargas publicas, links publicos, borrado, Caja, pagos, abonos ni
  `/api/sync`.
- Auditoria `documentos.metadata_actualizada` solo cuando hay cambios reales.

Verificacion automatica:

- `php -l` limpio en controlador, modelo, vistas documentales, rutas y health checker.
- `GET /documentos/1/editar` sin sesion: `303` a login.
- `POST /documentos/1/actualizar` sin sesion: `303` a login.
- `GET /documentos/1/editar` con sesion Los Cedros: `200`.
- `POST /documentos/1/actualizar` no-op con CSRF valido: `303` a detalle, sin cambios
  persistidos y sin auditoria ruidosa.
- `POST /documentos/1/actualizar` con CSRF invalido: `303` a dashboard, sin cambios.
- Prueba transaccional directa: update de titulo + auditoria dentro de transaccion y
  rollback exitoso; `audit_delta_inside=1`, `audit_delta_after=0`.
- `health_check_fase_1a.php`: PASS con warnings conocidos; detecta rutas 4B-C y
  auditoria diferencial.
- `preflight_compras_minimas.php` y `preflight_recepcion_compras.php`: PASS con
  warnings conocidos.
- `git diff --check`: OK.

QA manual completada:

1. El usuario confirmo que la prueba manual fue correcta.
2. La edicion desde `/documentos/{id}/editar` funciona.
3. La metadata se actualiza sin cambiar descarga, archivo, hash, MIME, tamano, hotel ni vinculos.
4. No aparecen acciones de borrado, reemplazo de archivo, links publicos, pagos, abonos, Caja ni `/api/sync`.
