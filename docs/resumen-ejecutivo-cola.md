# Resumen ejecutivo - cola autonoma

## Reanclaje Fase 3C

Estado vigente: `FASE_3C_VALIDADA_MANUALMENTE`.

Motivo: despues del reanclaje, se formalizo 3C-A primero, luego 3C-B como generacion manual controlada y despues 3C-C como validaciones read-only en health/preflights. La revision tecnica 3C confirmo rutas, guards, CSRF, `hotel_id`, relacion compra-proveedor-hotel, CxP `#2`, ausencia de pagos/abonos/Caja y `/api/sync` sin cambios. La auditoria de seguridad 3C confirma que no hay hallazgos bloqueantes.

Estado formal vigente:

- Fase 3C-0 contrato y diagnostico: completada.
- Fase 3C-A simulador read-only: completada tecnicamente, commiteada y validada manualmente.
- Fase 3C-B generacion manual: completada tecnicamente, commiteada y validada manualmente.
- Fase 3C-C validaciones/health/preflights: completada tecnicamente como verificacion read-only.
- Revision tecnica 3C actual: completada sin hallazgos bloqueantes.
- Auditoria seguridad 3C actual: completada sin hallazgos bloqueantes.
- Cierre tecnico 3C: completado documentalmente.
- QA manual final 3C: completada por el usuario.
- Auditoria `2662998`: reclasificada como auditoria prematura/documental.
- Siguiente accion recomendada: continuar solo con Fase 4A autorizada. No avanzar a pagos, abonos, Caja ni Fase 3D.

## Estado final del bloque autorizado anterior

Estado: `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`.

El bloque Fase 2X-3B queda como bloque cerrado anterior; Fase 3C queda con 3C-A y 3C-B completadas y validadas manualmente.

## Fases y commits

- Fase 2X: `d1f1431` - detalle read-only de compra recibida.
- Fase 2Y: `32abb7b` - reporte read-only de compras recibidas.
- Fase 2Z: `052fd7a` - endurecimiento de recepcion de compras.
- Fase 3A: `3d8f997` - ficha read-only de proveedor.
- Fase 3B draft: `673f47f` - borrador CxP base.
- Fase 3B aplicada: `1fa1653` - CxP base read-only.
- Cierre/auditoria: `2535dd1`, `ca2bda4`.
- Cierre tecnico final: `8a49995`.
- Ajuste visual reservaciones post-QA: `dc3c150`.

## Nuevo bloque Fase 3C

Objetivo: generar CxP manualmente desde compras recibidas, sin Caja ni pagos.

Estado actual:

- Fase 3C-0 completada con contrato y diagnostico.
- Fase 3C-A completada tecnicamente como simulador read-only: ruta GET, vista, controller, modelo, navegacion desde CxP y guardas existentes.
- Fase 3C-B completada tecnicamente: POST manual con CSRF, validaciones centrales, auditoria y bloqueo de duplicados.
- Fase 3C-C completada tecnicamente: health/preflights validan duplicados, relaciones, saldos, fechas, ausencia de movimientos CxP, ausencia de Caja y ausencia de pagos/abonos.
- SQL read-only 3C-C confirmo `cuentas_por_pagar=2`, `cuentas_por_pagar_movimientos=0`, inconsistencias CxP=0, Caja-CxP=0 y `compra_pagos` inexistente.
- Revision tecnica 3C actual confirma CxP `#2` desde compra `#2`, compra recibida, mismo `hotel_id=4`, proveedor `#2`, total/saldo `1900.00` y fecha de emision presente.
- Auditoria seguridad 3C actual confirma cero movimientos CxP, cero Caja-CxP, `compra_pagos` inexistente, CSRF/guards activos y `/api/sync` bloqueado.
- Cierre tecnico 3C completado: contrato, simulador, generacion manual, validaciones, revision, auditoria, rollback, fuentes de verdad, QA y warnings quedan documentados.
- QA manual final 3C reportada como OK: preview, simulador, CxP existente bloqueada, links, detalle CxP, origen compra/proveedor, CxP desde compra recibida, no duplicados, sin pagos, sin abonos, sin Caja y sin movimientos de Caja.
- Cambios PWA no relacionados al cierre de auditoria fueron validados manualmente y commiteados por separado en `35abdc7`; no forman parte de CxP.
- Cambios no relacionados ya separados en `e52766e`: `DashboardController.php`, `HabitacionController.php`, `NotificacionController.php`, `sidebar.php` y `notificaciones/index.php`.
- QA manual 3C-A/3C-B reportada por el usuario: preview OK, CxP #2 vinculada, detalle CxP OK, origen compra/proveedor visible, sin pagos, sin abonos y sin Caja.
- Siguiente paso recomendado: Fase 4A-A migracion base documental si se autoriza; no pagos, Caja ni Fase 3D.
- No se implementaron pagos ni Caja.
- No avanzar a pagos, Caja ni Fase 3D.

## Situacion historica

El historial contiene commits que implementan partes de Fase 3C, pero el estado documental vigente no debe tratarlos como cierre formal completo.

Las pruebas locales de 3C-B crearon CxP controladas desde compras recibidas (`id=1` para compra `#5` historica y `id=2` para compra `#2` vigente). Esos datos no deben borrarse ni corregirse automaticamente.

## Alcance de Fase 3B

Se permite:

- consultar CxP;
- listar cuentas por pagar;
- ver detalle de una cuenta por pagar;
- preparar checkers y documentacion;
- exponer navegacion basica.

No se permite:

- pagar proveedores;
- tocar Caja;
- generar CxP automaticamente desde compras;
- cambiar calculos financieros;
- modificar `/api/sync`;
- avanzar a Fase 3C.

## Estado tecnico auditado

- Codigo CxP read-only.
- Rutas GET solamente.
- Vistas sin formularios de pago.
- Health/preflight compatibles.
- Documentacion de metodologia vigente.
- Commit selectivo de cierre: `1fa1653 feat(phase-3b): add read-only accounts payable foundation`.
- `src/app/views/reservaciones/ver.php` queda fuera por no pertenecer al bloque.

## Decisiones clave

- CxP entra como fundacion, no como modulo operativo financiero.
- Caja queda intacta.
- Compras no genera CxP todavia.
- Las tablas duplicadas/legacy siguen congeladas.
- La siguiente accion despues del commit es revision manual, no nueva feature.

## Estado de riesgo

Riesgo naranja por tocar estructuras financieras de DB, mitigado por:

- backup previo;
- tablas vacias;
- implementacion read-only;
- sin integracion con Caja;
- sin pagos;
- verificaciones automaticas.

## Cierre tecnico post-commit

- CxP 3C-B tiene un unico POST manual con CSRF desde compra recibida elegible.
- CxP sigue sin integracion con Caja.
- Compras y proveedores no generan CxP automaticamente.
- Las consultas revisadas mantienen filtros por `hotel_id`.
- `POST /cuentas-por-pagar/generar-desde-compra/{id}` sin sesion redirige a login.
- `/api/sync` sigue fuera de alcance y bloqueado segun checker.

## Auditoria de seguridad

- Resultado: sin hallazgos bloqueantes.
- Perdida de datos: no detectada; no hay borrado ni migracion destructiva.
- Doble recepcion: protegida por contrato transaccional y validaciones de estado/detalles.
- Inventario: se mantiene fuente moderna `inventario_productos` + `movimientos_inventario`.
- CxP: en 3C-B permite generacion manual; no hay pagos, abonos ni movimientos de Caja.
- Riesgo residual: futuras escrituras CxP deben validar estrictamente `hotel_id` de proveedor/compra.
- 3C-C agrega validacion automatica para duplicados, compras/proveedores inexistentes, cruces de hotel, compras no recibidas, saldos/totales invalidos, fechas faltantes y referencias CxP en Caja.
- Auditoria corregida confirma: unico POST con CSRF, guardas de autenticacion/contexto/modulo, sin escrituras en Caja/pagos y sin cambios en `/api/sync`.
- Verificacion actual: Docker disponible; `php -l`, health, preflights, POST sin sesion, backup, doble generacion y conteos DB antes/despues ejecutados.

## Pendiente antes de avanzar

- No avanzar a pagos, Caja ni Fase 3D sin nuevo mensaje real o cola especifica.
- La siguiente accion recomendada es Fase 4A-A migracion base documental si se autoriza.

## Nuevo bloque Fase 4A Centro Documental

Estado: `CIERRE_TECNICO_4A_COMPLETADO`.

Objetivo: crear una fundacion segura para adjuntar, consultar y relacionar documentos
con proveedor, compra, CxP, huesped, reservacion y trabajador futuro, sin Caja, pagos,
abonos, Fase 3D ni `/api/sync`.

Resultado 4A-0:

- Git inicial limpio en HEAD `35abdc7`.
- Diagnostico de uploads publicos: `public_html/uploads` sirve assets publicos.
- Diagnostico de storage privado: `storage/reportes` y `ReporteLinkController` son el
  patron de descarga segura.
- No existen tablas generales `documentos`, `documento_entidades`,
  `documento_tipos`.
- Propuesta de tablas aditivas documentada.
- Siguiente cola recomendada: `[COLA_4A_A_MIGRACION_BASE_DOCUMENTOS]`.
- No se implementaron uploads, POST, descargas ni migraciones en 4A-0.

Resultado 4A-A:

- Backup previo confirmado antes de aplicar DB:
  `src/storage/backups/phase4a_20260615_182352_before_document_center_medisoft_hoteles_import.sql`.
- SHA256: `698A304F69B312EF06EABA787C83969629F2B14BCA096906CC08CADDC7D898F0`.
- Tamano: `1535817` bytes.
- Migracion creada: `migrations/20260615_004_fase_4a_centro_documental_base.sql`.
- Migracion aplicada localmente y registrada en `migrations` como batch `17`.
- Tablas creadas y vacias: `documento_tipos=0`, `documentos=0`, `documento_entidades=0`.
- No hay uploads, POST, descargas, borrados ni exposicion publica de documentos.
- No se tocaron Caja, pagos, abonos, CxP operativa ni `/api/sync`.
- Siguiente cola recomendada tras 4A-A: `[COLA_4A_B_DOCUMENTOS_READ_ONLY]`.

Resultado 4A-B:

- Modelo read-only creado: `src/app/models/Documento.php`.
- Controlador read-only creado: `src/app/controllers/DocumentoController.php`.
- Vistas read-only creadas: `src/app/views/documentos/index.php` y
  `src/app/views/documentos/ver.php`.
- Rutas GET creadas:
  - `/documentos`;
  - `/documentos/{id}`;
  - `/documentos/entidad/{tipo}/{id}`.
- Navegacion agregada en sidebar solo cuando hay modulos relacionados activos.
- No se crean uploads, POST, descargas, edicion ni borrado.
- No se expone `storage_path`, `nombre_archivo` ni rutas internas.
- Tablas documentales siguen vacias en entorno local.
- No se tocaron Caja, pagos, abonos, CxP operativa ni `/api/sync`.

Resultado 4A-C:

- Backup previo antes de escritura:
  `src/storage/backups/phase4a_c_20260615_190912_before_document_upload_medisoft_hoteles_import.sql`.
- SHA256: `DF150F705824973621B9A1276980DC73ECB7AE5261B67A5D71E541FE13797446`.
- Tamano: `1541523` bytes.
- Rutas nuevas:
  - `GET /documentos/subir`;
  - `POST /documentos/subir`.
- Formulario de carga con `multipart/form-data` y CSRF.
- Validacion de archivo: `is_uploaded_file`, tamano maximo, extension permitida, extensiones
  peligrosas bloqueadas y MIME real con `finfo`.
- Tipos permitidos iniciales: PDF, JPG/JPEG, PNG y WEBP.
- Storage privado bajo `STORAGE_PATH/documentos/hotel_{hotel_id}/YYYY/MM`.
- No se expone `storage_path` ni `nombre_archivo` en vistas.
- Se registra metadata en `documentos`, vinculo opcional en `documento_entidades` y
  auditoria `documentos.cargado`.
- Prueba controlada: documento `#1` creado en hotel `1`, vinculado a proveedor `#8`.
- Conteos post-prueba: `documentos=1`, `documento_entidades=1`,
  `documento_tipos=0`, `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`.
- Archivo `.html` invalido fue rechazado sin crear registros.
- No hay descarga, edicion, borrado, pagos, abonos, Caja ni `/api/sync`.
- Hotfix post-QA 4A-C:
  - tipos documentales globales creados por migracion idempotente;
  - carga general simplificada sin IDs manuales de entidad;
  - CSS relativo del layout corregido con `asset()`;
  - prueba controlada creo `documentos.id=2` con tipo Contrato y sin vinculo inicial.
- QA manual post-hotfix 4A-C: el usuario reporto que la carga documental ya funciona.
- Revision tecnica 4A: se reforzo `/documentos/entidad/{tipo}/{id}` para validar que la entidad exista y pertenezca al hotel actual antes de mostrar listado contextual o enlace de carga.
- Auditoria seguridad 4A: no hay rutas de descarga, edicion ni borrado documental; no se exponen rutas internas; no hay referencias documentales en PWA/offline; sin Caja, pagos, abonos ni `/api/sync`.
- Cierre tecnico 4A: bloque 4A-0..4A-C documentado y cerrado; no autoriza descargas, edicion, borrado, pagos, Caja, Fase 3D ni `/api/sync`.
- Siguiente paso recomendado: nuevo mensaje real para autorizar una fase posterior, por ejemplo descarga segura autenticada.

## Nuevo bloque Fase 4B Descarga segura documental

Estado: `DESCARGA_SEGURA_4B_VALIDADA_MANUALMENTE`.

Objetivo: definir descarga autenticada de documentos privados ya cargados en Centro
Documental, sin exponer `storage_path`, sin links publicos, sin Caja, pagos, abonos ni
`/api/sync`.

Resultado 4B-0:

- Documento creado: `docs/fase_4B_0_contrato_descarga_segura_documentos.md`.
- Ruta futura propuesta: `GET /documentos/{id}/descargar`.
- Implementacion futura propuesta: `DocumentoController::descargarAction()` con
  validacion `id + hotel_id`, documento `activo`, `realpath` bajo
  `STORAGE_PATH/documentos` y headers privados.
- Se tomo como referencia tecnica `ReporteLinkController`, pero sin token publico y sin
  limitar a PDF.
- No se implementaron rutas, modelos, vistas, migraciones, lectura de archivos ni
  escrituras DB.

Resultado 4B-A:

- Ruta implementada: `GET /documentos/{id}/descargar`.
- Controlador/modelo actualizados para buscar por `id + hotel_id`, exigir `estado=activo`
  y resolver archivo por `realpath` bajo `STORAGE_PATH/documentos`.
- Vistas de listado y detalle muestran accion `Descargar` sin exponer `storage_path` ni
  `nombre_archivo`.
- HTTP sin sesion redirige a login.
- Documento `#1` de Los Cedros descarga `200`; documento `#3` de otro hotel redirige.
- En 4B-A no hay POST nuevo, links publicos, edicion, borrado, Caja, pagos, abonos ni `/api/sync`;
  la edicion posterior queda limitada a metadata en 4B-C-A.
- QA manual 4B-A reportada por el usuario como completada.

Resultado 4B-B:

- Se agrega auditoria tolerante de descargas documentales con `AuditService::record()`.
- Eventos: `documentos.descargado` y `documentos.descarga_bloqueada`.
- No se registra `storage_path` ni `nombre_archivo`.
- Verificacion local genero una auditoria exitosa para documento `#1` y una bloqueada
  para documento `#3` de otro hotel.
- No hay nuevas rutas, POST, edicion, borrado, links publicos, Caja, pagos, abonos ni
  `/api/sync`.
- QA manual 4B-B reportada por el usuario como funcional.

Resultado 4B-C-A:

- Se implementa edicion controlada de metadata documental.
- Rutas: `GET /documentos/{id}/editar` y `POST /documentos/{id}/actualizar`.
- Metadata editable limitada a `titulo`, `descripcion`, `etiquetas` y
  `documento_tipo_id`.
- Auditoria: `documentos.metadata_actualizada` solo cuando hay cambios reales.
- Verificacion: POST no-op e invalido no modifican datos; prueba transaccional confirma
  update real + auditoria + rollback sin persistencia.
- Quedan prohibidos reemplazo de archivo, cambio de storage, borrado, links publicos,
  Caja, pagos, abonos y `/api/sync`.
- QA manual 4B-C-A reportada por el usuario como correcta.
- Estado formal: `METADATA_DOCUMENTAL_4B_C_A_VALIDADA_MANUALMENTE`.

Cierre tecnico 4B:

- Estado formal: `CIERRE_TECNICO_4B_COMPLETADO`.
- Revision tecnica post-QA ejecutada sin hallazgos bloqueantes.
- Auditoria seguridad post-QA confirma rutas protegidas, CSRF en POST, filtros
  `hotel_id`, storage privado, ausencia de rutas internas en vistas y ausencia de
  borrado, reemplazo, links publicos, Caja, pagos, abonos y cambios en `/api/sync`.
- Verificaciones: `php -l`, health, preflights, HTTP sin sesion, SQL read-only y
  `git diff --check`.
- Siguiente paso recomendado: nuevo bloque autorizado; no avanzar automaticamente a
  borrado, links publicos, reemplazo de archivos, pagos, Caja, Fase 3D ni NP-A.

## Nuevo bloque Personal y Nomina (Fase NP)

Objetivo: modulo INDEPENDIENTE de trabajadores con ledger laboral, saldos por persona,
asistencia y comisiones, multi-hotel, SIN integracion con Caja ni salida real de dinero.

Estado actual:

- Fase NP-0 completada: contrato, diagnostico read-only y diseno aditivo de 6 tablas.
- HEAD al iniciar NP-0: `5dfe665`; Git limpio.
- Hoy "trabajador" = `usuarios` + `hotel_usuarios`; sin rol laboral, deuda ni saldo por persona.
- Caja revisada en solo lectura; NO existe categoria "Nomina"; movimientos Caja-nomina: 0.
- No existe ninguna tabla `trabajador*`: el bloque es 100% aditivo.
- No se implemento funcionalidad ni se escribio en DB en NP-0.

Riesgo: naranja (modulo financiero-laboral nuevo y concepto sensible), mitigado por
migraciones aditivas/reversibles, sin Caja, sin tocar `usuarios` destructivamente,
filtro `hotel_id` y validaciones fuertes.

Subfases planificadas: NP-A (ficha basica), NP-B (pagos/anticipos/prestamos sin Caja),
NP-C (saldos y reportes), NP-D (asistencia y comisiones), NP-E (validaciones/health),
NP-F (cierre). Contrato: `docs/fase_NP_0_contrato_diagnostico.md`.
