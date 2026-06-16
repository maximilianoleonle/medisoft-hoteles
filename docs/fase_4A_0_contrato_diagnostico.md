# Fase 4A-0 - Contrato y diagnostico Centro Documental

## Objetivo

Definir la fundacion segura del **Centro Documental Base** para adjuntar, consultar y
relacionar documentos con entidades operativas del sistema, sin afectar Caja, pagos,
abonos, CxP operativa, facturacion ni `/api/sync`.

Esta subfase 4A-0 solo produce contrato y diagnostico. No implementa uploads, no crea
POST de carga, no crea acciones de borrado, no ejecuta migraciones y no escribe datos.

## Estado inicial verificado

- Rama: `feature/saas-multihotel`.
- HEAD al iniciar: `35abdc7 fix(pwa): use hotel branding assets for push notifications`.
- `git status`: arbol limpio.
- Rama por delante de origin: 35 commits.
- Fase 3C: `FASE_3C_VALIDADA_MANUALMENTE`.
- PWA push branding: validado manualmente y commiteado por separado.

## Alcance autorizado

Entidades objetivo iniciales:

- proveedor;
- compra;
- cuenta por pagar;
- huesped;
- reservacion;
- trabajador en fases futuras.

Permitido en el bloque 4A completo:

- diagnosticar infraestructura de uploads/documentos;
- crear migraciones aditivas no destructivas si se autorizan subfases posteriores;
- crear tablas base si no existen;
- crear modelos/controladores/vistas base;
- preparar adjuntos con permisos;
- actualizar documentacion, QA, rollback y fuentes de verdad.

No permitido:

- borrar archivos o datos;
- tocar Caja, pagos, abonos, Fase 3D o `/api/sync`;
- exponer documentos privados como archivos publicos;
- subir archivos sin validacion MIME/extension/tamano;
- mezclar cambios PWA;
- hacer push;
- ejecutar migraciones destructivas;
- eliminar tablas o columnas;
- modificar secretos.

## Semaforo de riesgo

Riesgo: **naranja**.

Motivo:

- un centro documental maneja archivos potencialmente sensibles;
- debe respetar multi-hotel y permisos por entidad;
- un error de almacenamiento podria exponer documentos privados;
- pero la subfase 4A-0 es solo documental y no escribe datos.

Mitigaciones requeridas para fases posteriores:

- almacenamiento privado por defecto en `STORAGE_PATH`, no en `public_html/uploads`;
- descarga siempre por controlador con `requireAuth`, contexto hotelero y validacion de entidad;
- validacion de ruta con `realpath` y raices permitidas;
- MIME real por `finfo`, extension permitida, tamano maximo y nombre seguro;
- baja logica, no borrado fisico inicial;
- auditoria de cargas, descargas sensibles y baja logica;
- health/preflight para tablas, rutas y documentos huerfanos.

## Diagnostico de infraestructura existente

### Uploads publicos

Existe `src/public_html/uploads` con `.htaccess` que:

- deshabilita indices y ejecucion CGI;
- bloquea extensiones ejecutables (`php`, `phtml`, `phar`, scripts, etc.);
- permite servir imagenes y PDF.

Uso actual observado:

- `uploads/branding`: assets publicos de marca por hotel;
- `uploads/habitaciones`: imagenes publicas de habitaciones;
- `uploads/temp`: temporales.

Conclusion: `public_html/uploads` sirve para imagenes o assets publicos. No debe ser la
ubicacion por defecto para documentos privados de proveedor, huesped, reservacion, compra
o CxP.

### Uploads validados existentes

Patrones detectados:

- `hotel_branding_upload_asset()` valida error de upload, `is_uploaded_file`, tamano,
  extension, MIME real con `finfo`, `getimagesize`, dimensiones y nombre aleatorio.
- `HabitacionController::procesarImagenHabitacion()` valida tamano, MIME real, imagen
  valida y usa `ensure_upload_directory('habitaciones')`.
- Helpers generales: `format_file_size`, `sanitize_filename`, `ensure_upload_directory`.

Conclusion: las fases futuras deben reutilizar validacion fuerte, pero adaptada a
documentos privados: PDF/JPG/PNG/WEBP iniciales, con opcion futura para DOC/DOCX solo si
se decide que es seguro.

### Almacenamiento privado existente

Existe `src/storage/reportes` con PDFs generados. El patron mas seguro esta en
`ReporteLinkController`:

- descarga interna exige `requireAuth`, permiso `reportes.view/all` y modulo `reportes`;
- descarga publica usa token hash, expiracion, estado y conteo de accesos;
- resuelve ruta con `realpath`;
- limita raices permitidas (`storage/reportes`, `app/uploads/reportes`);
- sirve PDF con `Content-Type`, `Content-Disposition`, `X-Content-Type-Options` y
  `Cache-Control: private`;
- no expone carpeta privada directamente.

Conclusion: Centro Documental debe seguir este patron, preferentemente con
`STORAGE_PATH . '/documentos'` y descarga por controlador autenticado.

### Tablas existentes

Consulta read-only:

- No se detectaron tablas generales `documentos`, `documento_entidades` ni
  `documento_tipos`.
- Existen tablas especificas de reportes: `reporte_links` y `reporte_link_envios`.

Conclusion: Fase 4A debe ser aditiva; no debe reutilizar `reporte_links` como tabla
general porque su contrato es de reportes PDF y links tokenizados.

### Guards/permisos

Patrones existentes:

- `Controller::requireAuth()`;
- `require_hotel_context()`;
- `require_hotel_module($clave)`;
- permisos puntuales por `can(...)` en reportes;
- CSRF via `validateCSRF()` para POST;
- hotel actual via `obtenerHotelIdActualCompat()` o helpers equivalentes.

Conclusion: 4A debe tener guard comun de autenticacion + contexto hotelero + modulo
relacionado. Si se crea un modulo documental global, debe ser explicito y no sustituir
los permisos de las entidades.

## Propuesta de tablas base

No crear en 4A-0. Propuesta para una migracion futura idempotente y aditiva:

### documento_tipos

Catalogo de tipos permitidos.

- `id`;
- `hotel_id INT NULL` (NULL = tipo global, valor = override por hotel);
- `clave VARCHAR(80) NOT NULL`;
- `nombre VARCHAR(120) NOT NULL`;
- `entidad_tipo VARCHAR(60) NULL`;
- `mimes_permitidos JSON NULL`;
- `max_size_bytes INT UNSIGNED NULL`;
- `requiere_vencimiento TINYINT(1) DEFAULT 0`;
- `activo TINYINT(1) DEFAULT 1`;
- `created_at`, `updated_at`.

Indices propuestos:

- `idx_documento_tipos_hotel_activo (hotel_id, activo)`;
- `uniq_documento_tipos_hotel_clave (hotel_id, clave)`.

### documentos

Metadata del archivo. El archivo fisico debe vivir en storage privado.

- `id`;
- `hotel_id INT NOT NULL`;
- `documento_tipo_id INT NULL`;
- `titulo VARCHAR(180) NULL`;
- `descripcion VARCHAR(255) NULL`;
- `storage_disk VARCHAR(40) NOT NULL DEFAULT 'local_private'`;
- `storage_path VARCHAR(500) NOT NULL`;
- `nombre_original VARCHAR(255) NOT NULL`;
- `nombre_seguro VARCHAR(255) NOT NULL`;
- `extension VARCHAR(20) NOT NULL`;
- `mime_type VARCHAR(120) NOT NULL`;
- `tamano_bytes BIGINT UNSIGNED NOT NULL`;
- `sha256 CHAR(64) NULL`;
- `visibilidad ENUM('privado') NOT NULL DEFAULT 'privado'`;
- `estado ENUM('activo','archivado','eliminado') NOT NULL DEFAULT 'activo'`;
- `subido_por INT NULL`;
- `archivado_por INT NULL`;
- `archivado_en DATETIME NULL`;
- `created_at`, `updated_at`.

Indices propuestos:

- `idx_documentos_hotel_estado (hotel_id, estado)`;
- `idx_documentos_tipo (documento_tipo_id)`;
- `idx_documentos_sha (sha256)`;

### documento_entidades

Relacion polimorfica entre un documento y una o varias entidades.

- `id`;
- `hotel_id INT NOT NULL`;
- `documento_id INT NOT NULL`;
- `entidad_tipo ENUM('proveedor','compra','cuenta_por_pagar','huesped','reservacion','trabajador') NOT NULL`;
- `entidad_id INT NOT NULL`;
- `rol VARCHAR(60) NULL`;
- `created_by INT NULL`;
- `created_at`.

Indices propuestos:

- `idx_documento_entidades_lookup (hotel_id, entidad_tipo, entidad_id)`;
- `idx_documento_entidades_documento (documento_id)`;
- `uniq_documento_entidad (hotel_id, documento_id, entidad_tipo, entidad_id)`.

Notas:

- No se recomiendan FKs directas por entidad polimorfica.
- El modelo/servicio debe validar que cada entidad pertenece al mismo `hotel_id` antes de
  insertar la relacion.
- `documentos.hotel_id` y `documento_entidades.hotel_id` deben coincidir siempre.

## Almacenamiento propuesto

Raiz privada:

```text
STORAGE_PATH/documentos/{hotel_id}/{yyyy}/{mm}/...
```

Reglas:

- nunca servir por URL publica directa;
- descarga por controlador;
- nombres fisicos aleatorios con extension validada;
- conservar `nombre_original` solo como metadata;
- `realpath` obligatorio antes de leer;
- raiz permitida obligatoria: `realpath(STORAGE_PATH . '/documentos')`;
- headers: `X-Content-Type-Options: nosniff`, `Cache-Control: private, max-age=0`;
- para imagenes preview futuras, tambien pasar por controlador o crear thumbnails seguros.

## Propuesta de subfases 4A

### 4A-0 Contrato y diagnostico

Solo documentacion. Sin codigo funcional, sin migraciones aplicadas y sin escrituras.

### 4A-A Migracion base idempotente

Crear tablas `documento_tipos`, `documentos`, `documento_entidades` con migracion
aditiva, rollback manual documentado y backup previo si se ejecuta.

### 4A-B Modelo/servicio read-only

Modelo para listar documentos por entidad/hotel y resolver metadata sin descargar
archivo. Sin uploads.

### 4A-C Vistas read-only por entidad

Secciones de documentos en proveedor, compra, CxP, huesped y reservacion, mostrando
estado vacio y lista si existen documentos. Sin POST.

### 4A-D Upload controlado

POST con CSRF, MIME real, tamano, extension, storage privado, auditoria y validacion de
entidad/hotel. Sin borrado fisico.

### 4A-E Descarga segura

GET autenticado con validacion de permisos, hotel y entidad; servir archivo privado por
controlador.

### 4A-F Auditoria, health/preflight y cierre

Validar documentos huerfanos, rutas fuera de storage, entidades inexistentes, hotel_id
cruzado, archivos faltantes, MIME invalido y ausencia de acceso publico directo.

## Definition of Done del bloque 4A completo

- Contrato aprobado y actualizado.
- Backup previo antes de cualquier migracion o escritura.
- Migracion aditiva e idempotente.
- Tablas base creadas y registradas en `migrations`.
- Modelos/controladores con `hotel_id` obligatorio.
- Vistas read-only antes de POST.
- Upload con CSRF y validacion fuerte.
- Descarga por controlador, no por path publico.
- Auditoria en `logs_auditoria`.
- Health/preflight sin errores bloqueantes.
- QA critica/funcional/visual/regresion documentada.
- Rollback manual documentado.
- Revision tecnica y auditoria de seguridad completadas.
- Sin Caja, pagos, abonos, Fase 3D ni `/api/sync`.

## QA inicial propuesta

Critica:

- Documento de un hotel no visible en otro hotel.
- Archivo privado no accesible por URL directa.
- Descarga sin sesion redirige/bloquea.
- Upload rechaza extension/MIME invalido.
- Upload rechaza archivo excedido.
- No hay escritura en Caja, pagos, abonos ni CxP operativa.
- `/api/sync` sigue bloqueado.

Funcional:

- Proveedor/compra/CxP/huesped/reservacion muestran estado vacio.
- Documento adjunto aparece en la entidad correcta.
- Descarga funciona autenticado con permisos.

Visual:

- Estado vacio claro.
- Lista compacta y consistente con UI operativa.
- No mezclar identidad Medisoft SaaS con branding hotelero.

Regresion:

- Fase 3C sigue funcionando.
- PWA push sigue funcionando.
- Reportes seguros siguen descargando.
- Login/logout normal.

## Rollback propuesto

4A-0:

- Revertir solo documentacion si se descarta el diseno.
- No hay DB ni archivos funcionales que revertir.

Fases futuras:

- Revertir commits por subfase.
- No borrar archivos subidos sin autorizacion.
- Para DB, restaurar backup o aplicar rollback manual solo con autorizacion explicita.
- Baja logica preferida sobre borrado fisico.

## Riesgos abiertos

- Definir si `documento_tipos` sera global, por hotel o mixto.
- Definir modulo/gate visual: `documentos` propio o heredar el modulo de la entidad.
- Definir tipos iniciales permitidos: recomendacion inicial PDF/JPG/PNG/WEBP.
- Definir si DOC/DOCX se permite; riesgo mayor por macros/contenido activo.
- Definir retencion y politica de eliminacion fisica futura.
- Definir si habra links publicos temporales; no incluir en 4A base.

## Siguiente cola recomendada

`[COLA_4A_B_DOCUMENTOS_READ_ONLY]`

Objetivo: crear modelos/controladores/vistas read-only para consultar metadata
documental por hotel y por entidad, sin uploads, sin POST, sin descarga de archivos, sin
Caja, sin pagos, sin abonos y sin `/api/sync`.

## Resultado Fase 4A-A - Migracion base documental

Estado: `MIGRACION_4A_COMPLETADA`.

Backup previo confirmado antes de aplicar DB:

- `src/storage/backups/phase4a_20260615_182352_before_document_center_medisoft_hoteles_import.sql`
- tamano: `1535817` bytes
- SHA256: `698A304F69B312EF06EABA787C83969629F2B14BCA096906CC08CADDC7D898F0`

Migracion creada y aplicada localmente:

- `migrations/20260615_004_fase_4a_centro_documental_base.sql`
- registrada en `migrations` como `ejecutada`, batch `17`

Tablas creadas:

- `documento_tipos`
- `documentos`
- `documento_entidades`

Conteos posteriores:

- `documento_tipos`: `0`
- `documentos`: `0`
- `documento_entidades`: `0`

No se implementaron uploads, POST, descargas, borrados ni exposicion publica de
documentos. El campo `storage_path` queda reservado para almacenamiento privado futuro,
no para `public_html/uploads`.

Validaciones de no afectacion:

- `cuentas_por_pagar`: `2`
- `cuentas_por_pagar_movimientos`: `0`
- `movimientos_caja`: `1403`
- tablas de pagos/abonos CxP buscadas: inexistentes

Rollback manual de 4A-A:

1. Confirmar con backup vigente y autorizacion explicita.
2. Confirmar que `documento_entidades`, `documentos` y `documento_tipos` siguen en `0`.
3. Si estan vacias, revertir el commit de la migracion y ejecutar rollback SQL manual en
   orden hijo-padre.
4. Si contienen datos, no ejecutar `DROP`; exportar conteos y definir reconciliacion.
