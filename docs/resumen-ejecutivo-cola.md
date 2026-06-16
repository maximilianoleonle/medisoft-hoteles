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

## Nuevo bloque Fase 4C Documentos por entidad

Estado: `CIERRE_TECNICO_4C_COMPLETADO_QA_MANUAL_VALIDADA`.

Objetivo: definir como integrar documentos en fichas operativas de proveedor, compra,
cuenta por pagar, huesped y reservacion usando la relacion existente
`documento_entidades`, sin implementar todavia nuevas secciones ni escrituras.

Resultado 4C-0:

- Documento creado: `docs/fase_4C_0_contrato_documentos_entidad.md`.
- Diagnostico confirma infraestructura existente:
  - `Documento::documentosPorEntidad()`;
  - `DocumentoController::entidadAction()`;
  - `GET /documentos/entidad/{tipo}/{id}`;
  - carga contextual ya validada por entidad/hotel.
- No se tocaron controladores, modelos, rutas, vistas, DB ni migraciones.
- No hay nuevas escrituras, Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.
- Siguiente paso recomendado: `COLA_4C_A_DOCUMENTOS_POR_ENTIDAD_READ_ONLY`.

Resultado 4C-A:

- Estado tecnico: `DOCUMENTOS_ENTIDAD_CONTEXTUAL_4C_VALIDADA_MANUALMENTE`.
- Documento creado: `docs/fase_4C_A_documentos_entidad_readonly.md`.
- Partial creado: `src/app/views/partials/documentos_entidad.php`.
- Fichas con seccion documental contextual:
  - `proveedores/ver.php`;
  - `compras/ver.php`;
  - `cuentas_por_pagar/ver.php`;
  - `huespedes/ver.php`;
  - `reservaciones/ver.php`.
- Controladores consultan documentos por entidad con filtro `hotel_id`.
- La vista no expone `storage_path` ni `nombre_archivo`.
- Hotfix UX: se agrega `Vincular documento` hacia la carga contextual existente
  `documentos/subir?entidad_tipo=...&entidad_id=...`.
- No hay nuevos POST, edicion desde ficha, borrado, Caja, pagos, abonos, Fase 3D,
  NP-A ni cambios en `/api/sync`.
- QA manual post-hotfix reportada por el usuario como correcta: la accion
  `Vincular documento` aparece, abre la carga contextual existente y el flujo funciona
  correctamente.
- Cierre tecnico documental: registrado en `docs/cierre-tecnico-bloque-cola.md`.
- Siguiente paso recomendado: abrir un nuevo bloque solo con autorizacion explicita. No
  avanzar automaticamente a borrado, reemplazo, links publicos, Caja, pagos, abonos,
  Fase 3D, NP-A ni `/api/sync`.

## Nuevo bloque Fase 4D Archivado documental

Estado: `CIERRE_TECNICO_4D_COMPLETADO_QA_DIFERIDA`.

Objetivo: preparar una fase segura para archivar, restaurar y eventualmente dar baja
logica a documentos sin borrar archivos fisicos ni registros.

Resultado 4D-0:

- Documento creado: `docs/fase_4D_0_contrato_archivado_documental.md`.
- Diagnostico confirma que `documentos.estado` ya soporta `activo`, `archivado` y
  `eliminado`.
- Se define 4D-A como archivado/restauracion reversible (`activo <-> archivado`) con
  POST + CSRF, auditoria y filtro `hotel_id`.
- Se deja 4D-B como baja logica futura hacia `eliminado`, con confirmacion fuerte y
  sin borrado fisico.
- No hay codigo, rutas nuevas, POST nuevo, DB, migraciones, Caja, pagos, abonos,
  Fase 3D, NP-A ni cambios en `/api/sync`.
- Siguiente paso recomendado: `COLA_4D_A_ARCHIVADO_DOCUMENTAL_CONTROLADO`, solo si se
  autoriza implementacion.

Resultado 4D-A:

- Estado tecnico: `FASE_4D_A_VALIDADA_MANUALMENTE`.
- Documento creado: `docs/fase_4D_A_archivado_documental_controlado.md`.
- Rutas POST agregadas: `/documentos/{id}/archivar` y `/documentos/{id}/restaurar`.
- Modelo central: `Documento::actualizarEstado()` valida `id + hotel_id` y solo permite
  `activo <-> archivado`.
- Vista de detalle muestra `Archivar` o `Restaurar` segun estado, con CSRF.
- Auditoria: `documentos.estado_actualizado`.
- No hay baja logica `eliminado`, borrado fisico, `DELETE`, Caja, pagos, abonos,
  Fase 3D, NP-A ni cambios en `/api/sync`.
- Verificacion automatica: `php -l`, health, preflights, HTTP sin sesion, SQL read-only,
  prueba transaccional con rollback y `git diff --check`.
- Revision tecnica/auditoria: sin hallazgos bloqueantes; el bloque queda cerrado
  tecnicamente.
- QA manual: el usuario reporto que todas las pruebas QA responden perfectamente.
- Pendiente real: no avanzar a baja logica `eliminado`, Caja, pagos, abonos, Fase 3D,
  NP-A ni `/api/sync` sin nuevo bloque explicito.

Resultado 4D-B-0:

- Estado tecnico: `CONTRATO_4D_B_BAJA_LOGICA_DOCUMENTAL_COMPLETADO`.
- Documento creado: `docs/fase_4D_B_0_contrato_baja_logica_documental.md`.
- Se define baja logica futura hacia `eliminado` sin borrar archivo fisico, registros
  ni relaciones.
- Ruta futura propuesta: `POST /documentos/{id}/eliminar`.
- Transiciones futuras propuestas: `activo -> eliminado` y `archivado -> eliminado`.
- Se mantiene prohibida la restauracion desde `eliminado` hasta contrato separado.
- No se implementaron rutas, controladores, modelos, vistas, DB ni migraciones.
- No hay borrado fisico, `DELETE`, Caja, pagos, abonos, Fase 3D, NP-A ni cambios en
  `/api/sync`.
- Siguiente paso recomendado: implementar 4D-B-A solo con nueva autorizacion explicita.

Resultado 4D-B-A:

- Estado tecnico: `BAJA_LOGICA_DOCUMENTAL_4D_B_A_COMPLETADA_QA_DIFERIDA`.
- Ruta POST implementada: `/documentos/{id}/eliminar`.
- Controlador: `DocumentoController::eliminarAction()`.
- Modelo central: `Documento::actualizarEstado()` permite `activo/archivado -> eliminado`
  y bloquea cambios desde `eliminado`.
- Vista de detalle muestra `Baja logica` solo en documentos `activo` o `archivado`,
  con CSRF y confirmacion fuerte.
- No hay restauracion desde `eliminado`, borrado fisico, `DELETE`, Caja, pagos,
  abonos, Fase 3D, NP-A ni cambios en `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

Cierre tecnico 4D:

- Estado final: `CIERRE_TECNICO_4D_COMPLETADO_QA_DIFERIDA`.
- Bloque documental 4D culminado tecnicamente.
- QA manual pendiente solo para 4D-B-A baja logica.
- Siguiente bloque recomendado: Personal base, iniciando por contrato/diagnostico o
  migracion base segun estado real del repo.

## Nuevo bloque Personal y Nomina (Fase NP)

Objetivo: modulo INDEPENDIENTE de trabajadores con ledger laboral, saldos por persona,
asistencia y comisiones, multi-hotel, SIN integracion con Caja ni salida real de dinero.

Estado actual:

- Fase NP-0 completada: contrato, diagnostico read-only y diseno aditivo de 6 tablas.
- Fase NP-A completada tecnicamente: migracion aditiva de Personal base aplicada y
  tablas creadas vacias.
- HEAD al iniciar NP-0: `5dfe665`; Git limpio.
- Hoy "trabajador" = `usuarios` + `hotel_usuarios`; sin rol laboral, deuda ni saldo por persona.
- Caja revisada en solo lectura; NO existe categoria "Nomina"; movimientos Caja-nomina: 0.
- Tablas `trabajador*` existen desde NP-A y estan vacias.
- No se implemento funcionalidad visual ni se insertaron trabajadores/movimientos.

Riesgo: naranja (modulo financiero-laboral nuevo y concepto sensible), mitigado por
migraciones aditivas/reversibles, sin Caja, sin tocar `usuarios` destructivamente,
filtro `hotel_id` y validaciones fuertes.

Subfases planificadas: NP-A (ficha basica), NP-B (pagos/anticipos/prestamos sin Caja),
NP-C (saldos y reportes), NP-D (asistencia y comisiones), NP-E (validaciones/health),
NP-F (cierre). Contrato: `docs/fase_NP_0_contrato_diagnostico.md`.

Resultado NP-A:

- Estado tecnico: `MIGRACION_NP_A_PERSONAL_BASE_COMPLETADA_QA_DIFERIDA`.
- Backup valido: `src/storage/backups/phase_np_a_20260616_021311_before_personal_base_medisoft_hoteles_import.sql`.
- SHA256: `0F9E64B040437A73D559534E5753133F4C3508C29F5B9EA0278B351097666246`.
- Migracion: `migrations/20260616_001_fase_np_a_personal_base.sql`.
- Conteos finales de las seis tablas: 0.
- Caja/Nomina: movimientos 0, categorias 0.
- UI read-first implementada: `GET /trabajadores` y `GET /trabajadores/{id}` con
  guardas administrativas, estados vacios y sin POST.
- Sin pagos reales, anticipos, prestamos, asistencia operativa ni Caja.
- Siguiente paso recomendado: contrato NP-B para alta/edicion segura de trabajador o
  bloque Tareas/Limpieza/Mantenimiento base, segun prioridad.

Resultado NP-B-0:

- Estado tecnico: `CONTRATO_NP_B_CRUD_TRABAJADORES_COMPLETADO`.
- Documento: `docs/fase_NP_B_0_contrato_crud_trabajadores.md`.
- Proxima implementacion permitida: alta/edicion/baja logica/reactivar trabajador con
  POST + CSRF, auditoria y filtro `hotel_id`.
- Sigue fuera de alcance: pagos, anticipos, prestamos, asistencia operativa, documentos
  laborales, Caja, categoria Nomina y `/api/sync`.

Resultado NP-B-A:

- Estado tecnico: `CRUD_TRABAJADORES_NP_B_A_COMPLETADO_QA_DIFERIDA`.
- Rutas CRUD basicas de trabajador agregadas bajo `/trabajadores`.
- Validaciones centrales en `Trabajador`: hotel actual, usuario opcional del mismo hotel,
  nombre obligatorio, email valido y salario no negativo.
- POST con CSRF y auditoria para crear, actualizar, baja logica y reactivar.
- Sin pagos, anticipos, prestamos, asistencia operativa, documentos laborales, Caja ni
  `/api/sync`.

## Nuevo bloque Tareas, Limpieza y Mantenimiento (Fase TLM)

Objetivo: preparar una capa operativa de tareas para limpieza, mantenimiento ligero y
tareas generales, sin sustituir el flujo actual de habitaciones ni el historial de
mantenimiento.

Resultado TLM-0:

- Estado tecnico: `CONTRATO_TLM_0_COMPLETADO`.
- Documento: `docs/fase_TLM_0_contrato_diagnostico.md`.
- Diagnostico read-only ejecutado.
- No se crearon migraciones, rutas, controladores, modelos ni vistas.
- No se escribio en DB.
- Fuente actual de disponibilidad: `habitaciones.estado`.
- Fuente actual de mantenimiento: `mantenimientos_habitaciones`.
- Conteos revisados:
  - `mantenimientos_habitaciones`: 10 registros.
  - registros de mantenimiento sin `hotel_id`: 0.
  - habitaciones en `limpieza`: 12.
  - habitaciones en `mantenimiento`: 2.
  - `trabajadores`: 0.
- Riesgo: naranja, porque limpieza/mantenimiento afecta disponibilidad y reservaciones.
- Siguiente paso recomendado: TLM-A migracion base aditiva de tareas, con backup previo.

Resultado TLM-A:

- Estado tecnico: `MIGRACION_TLM_A_TAREAS_BASE_COMPLETADA_QA_DIFERIDA`.
- Backup valido:
  `src/storage/backups/phase_tlm_a_20260616_030648_before_tasks_base_medisoft_hoteles_import.sql`.
- SHA256: `C76243D8AF98A2D9AA9D94FB5B1BB4D29369A861594141C7EC397A13AF3CF242`.
- Migracion: `migrations/20260616_002_fase_tlm_a_tareas_base.sql`.
- Tablas creadas vacias: `tareas_operativas`, `tarea_eventos`.
- Migracion registrada en batch local `20`.
- No se crearon tareas ni eventos.
- No se crearon rutas, UI ni POST.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.
- Siguiente paso recomendado: TLM-B read-only de tareas operativas.

Resultado TLM-B:

- Estado tecnico: `TAREAS_READ_ONLY_TLM_B_COMPLETADO_QA_DIFERIDA`.
- Modelo/controlador/vistas read-only creados para tareas operativas.
- Rutas GET creadas: `/tareas` y `/tareas/{id}`.
- Sidebar expone `Tareas` bajo Operaciones.
- Health valida rutas, modelo, vistas y ausencia de POST.
- HTTP sin sesion redirige a login.
- No hay creacion/asignacion/cierre/cancelacion de tareas.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.
- Siguiente paso recomendado: TLM-C creacion manual controlada, si se autoriza.

Resultado TLM-C:

- Estado tecnico: `CREACION_MANUAL_TLM_C_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_C_creacion_manual_tareas.md`.
- Rutas agregadas:
  - `GET /tareas/crear`;
  - `POST /tareas`.
- El listado `/tareas` muestra accion `Nueva tarea` para usuarios con
  `habitaciones.mantenimiento`.
- El alta manual crea solo registros en `tareas_operativas` y `tarea_eventos`.
- La tarea nace en estado `pendiente`, con `origen = manual`.
- Se valida `hotel_id` del contexto y habitacion opcional del mismo hotel.
- POST protegido con CSRF, permiso `habitaciones.mantenimiento` y auditoria con
  `AuditService`.
- No hay asignacion, inicio, cierre, cancelacion ni cambio de `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente paso recomendado: TLM-D asignacion opcional a trabajador activo del mismo
  hotel.

Resultado TLM-D:

- Estado tecnico: `ASIGNACION_TLM_D_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_D_asignacion_trabajador.md`.
- Ruta agregada: `POST /tareas/{id}/asignar`.
- El detalle de tarea muestra seccion de asignacion solo para tareas `pendiente` o
  `asignada` y usuario con permiso.
- La asignacion valida trabajador activo del mismo hotel.
- La tarea queda en estado `asignada` y registra evento `asignada`.
- Se audita la asignacion con `AuditService`.
- No crea trabajadores, asistencia, pagos, abonos ni movimientos de Caja.
- No cambia `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, nomina ni `/api/sync`.
- QA manual queda diferida; la base local tiene 0 trabajadores activos al momento de
  implementar esta subfase.
- Siguiente paso recomendado: TLM-E inicio/cierre/cancelacion manual de tareas, si se
  autoriza.

Resultado TLM-E:

- Estado tecnico: `ESTADOS_TLM_E_COMPLETADOS_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_E_estados_tarea.md`.
- Rutas agregadas:
  - `POST /tareas/{id}/iniciar`;
  - `POST /tareas/{id}/completar`;
  - `POST /tareas/{id}/cancelar`.
- El detalle de tarea muestra acciones de estado solo para tareas activas.
- Transiciones centralizadas en `TareaOperativa::cambiarEstadoManualParaHotel()`.
- Eventos registrados: `iniciada`, `completada`, `cancelada`.
- Auditoria con `AuditService`.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea asistencia, pagos, abonos, nomina ni movimientos de Caja.
- No se toco `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente paso recomendado: TLM-F contexto visual de tareas en habitacion/trabajador.

Resultado TLM-F:

- Estado tecnico: `CONTEXTUAL_TLM_F_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_F_contextual_tareas.md`.
- Se agrega lectura contextual de tareas por `habitacion` y `trabajador` en
  `TareaOperativa::listarPorEntidadHotel()`.
- La ficha de habitacion muestra tareas operativas vinculadas con estado vacio claro.
- La ficha de trabajador muestra tareas asignadas con estado vacio claro.
- No se agregan rutas nuevas ni POST nuevos.
- No se crean, asignan, inician, completan ni cancelan tareas desde las fichas
  contextuales.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea asistencia, pagos, abonos, nomina ni movimientos de Caja.
- No se toco `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente paso recomendado: TLM-G health/preflights de consistencia de tareas.

Resultado TLM-G:

- Estado tecnico: `PREFLIGHT_TLM_G_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_G_preflight_consistencia.md`.
- Nuevo preflight read-only: `src/tools/saas/preflight_tareas_operativas.php`.
- Health checker actualizado para validar consistencia TLM-G.
- Se detectan tareas/eventos sin hotel, entidades cruzadas de hotel, estados invalidos,
  fechas incoherentes y movimientos de Caja con referencia a tarea operativa.
- No se agregan rutas, vistas ni acciones de usuario.
- No se escriben datos.
- No cambia `habitaciones.estado`.
- No modifica `mantenimientos_habitaciones`.
- No crea asistencia, pagos, abonos, nomina ni movimientos de Caja.
- No se toco `/api/sync`.
- Siguiente paso recomendado: TLM-H revision tecnica, auditoria y cierre del bloque.

Resultado TLM-H:

- Estado tecnico: `BLOQUE_TLM_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_TLM_H_cierre_bloque.md`.
- Se revisaron rutas, controladores, modelo, vistas, partial contextual, permisos,
  CSRF, auditoria, filtros `hotel_id`, ausencia de Caja, ausencia de pagos/abonos/nomina
  y ausencia de cambios en `/api/sync`.
- No se agregan funcionalidades nuevas.
- QA manual sigue diferida por instruccion del usuario.
- Siguiente paso recomendado: esperar QA manual TLM o pasar a un nuevo bloque autorizado
  independiente.

Resultado NP-C-0:

- Estado tecnico: `CONTRATO_NP_C_LEDGER_LABORAL_COMPLETADO`.
- Documento: `docs/fase_NP_C_0_contrato_ledger_laboral.md`.
- Se define el contrato de ledger laboral informativo sobre tablas `trabajador_*`.
- No se agregan rutas, vistas, POST, migraciones ni escrituras.
- Caja, pagos reales, abonos, categoria Nomina y `/api/sync` quedan explicitamente fuera
  de alcance.
- Siguiente paso recomendado: NP-C-A read-only de ledger laboral, sin crear datos.

Resultado NP-C-A:

- Estado tecnico: `LEDGER_LABORAL_NP_C_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_A_ledger_read_only.md`.
- La ficha de trabajador muestra ledger laboral read-only con saldo informativo,
  conceptos, anticipos y prestamos.
- No se agregan rutas ni POST.
- No se crean datos, pagos reales, abonos, movimientos de Caja ni categoria Nomina.
- Health checker conoce NP-C-A.
- Siguiente paso recomendado: preflight de consistencia NP-C antes de cualquier escritura.

Resultado NP-C-E:

- Estado tecnico: `PREFLIGHT_LEDGER_NP_C_E_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_E_preflight_ledger.md`.
- Nuevo preflight read-only: `src/tools/saas/preflight_personal_ledger.php`.
- Health checker detecta el preflight de ledger laboral.
- No agrega UI, rutas, POST, migraciones ni escrituras.
- Siguiente paso recomendado: revision tecnica/auditoria/cierre de NP-C read-only o
  contrato futuro para escrituras laborales sin Caja.

Resultado NP-C-F:

- Estado tecnico: `BLOQUE_NP_C_READ_ONLY_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_F_cierre_ledger_read_only.md`.
- Se cierra el bloque read-only de ledger laboral: contrato, vista y preflight.
- No hay pagos reales, abonos, Caja, categoria Nomina ni cambios en `/api/sync`.
- Siguiente paso recomendado: contrato NP-C-B-0 si se autoriza primera escritura laboral
  sin Caja, o QA manual diferida de NP-C.

Resultado NP-C-B-0:

- Estado tecnico: `CONTRATO_NP_C_B_CONCEPTOS_LABORALES_COMPLETADO`.
- Documento: `docs/fase_NP_C_B_0_contrato_conceptos_laborales.md`.
- Se define contrato futuro para registrar comision, bono, descuento y ajuste en
  `trabajador_pagos`.
- No se implementan rutas, vistas, POST ni escrituras.
- Caja, categoria Nomina, pagos reales y `/api/sync` siguen fuera de alcance.

Resultado NP-C-B-A:

- Estado tecnico: `CONCEPTOS_LABORALES_NP_C_B_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_B_A_conceptos_laborales.md`.
- Se habilita registro manual de concepto laboral en `trabajador_pagos` desde la ficha de
  trabajador activo.
- La escritura esta centralizada en el modelo, usa transaccion, valida `hotel_id`,
  trabajador activo, tipo, efecto, monto y fecha.
- La UI usa CSRF y deja claro que no es pago real ni movimiento de Caja.
- Health checker y preflight de ledger laboral validan NP-C-B-A.
- No se crean pagos reales, abonos, anticipos, prestamos, asistencia, categoria Nomina ni
  movimientos de Caja.
- No se toco `/api/sync`.

Resultado NP-C-B-F:

- Estado tecnico: `BLOQUE_NP_C_B_CONCEPTOS_LABORALES_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_B_F_cierre_conceptos_laborales.md`.
- Se cierra tecnicamente el bloque de conceptos laborales manuales.
- Commits incluidos: contrato, implementacion y revision tecnica.
- Checkers sin errores: preflight de ledger laboral y health general.
- QA manual queda diferida hasta que exista un trabajador activo autorizado.
- Siguiente paso recomendado: abrir contrato nuevo antes de anticipos, prestamos o
  asistencia.

Resultado NP-C-C-0:

- Estado tecnico: `CONTRATO_NP_C_C_ANTICIPOS_PRESTAMOS_COMPLETADO`.
- Documento: `docs/fase_NP_C_C_0_contrato_anticipos_prestamos.md`.
- Se define contrato para futura captura manual de anticipos y prestamos laborales.
- No se implementan rutas, vistas, POST ni escrituras.
- Caja, pagos reales, abonos, Nomina y `/api/sync` siguen fuera de alcance.

Resultado NP-C-C-A:

- Estado tecnico: `ANTICIPOS_PRESTAMOS_NP_C_C_A_COMPLETADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_C_A_anticipos_prestamos.md`.
- Se agregan formularios POST controlados para anticipos y prestamos en la ficha de
  trabajador.
- Modelo central valida hotel, trabajador activo, monto, fecha y motivo.
- `saldo_pendiente` inicia igual al monto.
- Health checker y preflight conocen NP-C-C-A.
- No se crean pagos reales, abonos, Nomina ni movimientos de Caja.
- No se toco `/api/sync`.

Resultado NP-C-C-F:

- Estado tecnico: `BLOQUE_NP_C_C_ANTICIPOS_PRESTAMOS_CERRADO_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_C_F_cierre_anticipos_prestamos.md`.
- Se cierra tecnicamente el bloque de anticipos/prestamos manuales.
- Checkers sin errores: preflight de ledger laboral y health general.
- QA manual queda diferida hasta que exista un trabajador activo autorizado.
- Siguiente paso recomendado: contrato nuevo de asistencia manual basica o QA manual NP-C.

Resultado NP-C-D-0:

- Estado tecnico: `CONTRATO_NP_C_D_ASISTENCIA_MANUAL_COMPLETADO`.
- Documento: `docs/fase_NP_C_D_0_contrato_asistencia_manual.md`.
- Se define contrato para futura captura manual de asistencia por trabajador/dia.
- No se implementan rutas, vistas, POST ni escrituras.
- No se autoriza nomina automatica, Caja, pagos reales ni `/api/sync`.

Resultado NP-C-D-A:

- Estado tecnico: `ASISTENCIA_MANUAL_NP_C_D_A_COMPLETADA_QA_DIFERIDA`.
- Documento: `docs/fase_NP_C_D_A_asistencia_manual.md`.
- Se habilita captura manual de asistencia en `trabajador_asistencias` desde la ficha de
  trabajador activo.
- La accion exige hotel actual, permiso existente, POST, CSRF y trabajador activo.
- El modelo valida fecha, tipo, horas, entrada/salida y duplicado por trabajador/dia.
- Health checker y preflight conocen NP-C-D-A.
- No se crea nomina, pagos reales, abonos, categorias Nomina ni movimientos de Caja.
- No se toco `/api/sync`.
