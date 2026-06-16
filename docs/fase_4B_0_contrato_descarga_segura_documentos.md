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
