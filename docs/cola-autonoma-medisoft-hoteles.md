# Cola autonoma - Medisoft Hoteles

## Ultimo mensaje real usado

NUEVO_BLOQUE_AUTORIZADO_FASE_4A_CENTRO_DOCUMENTAL: iniciar Centro Documental Base.

Mensaje actual procesado: el usuario autoriza continuar autonomamente y omitir QA
manual por ahora; se aplica NP-A migracion base de Personal.

## Estado vigente

- Bloque actual: Personal y Nomina independiente.
- Fase actual: NP-A migracion base de Personal.
- Riesgo: naranja.
- Estado: `MIGRACION_NP_A_PERSONAL_BASE_COMPLETADA_QA_DIFERIDA`.
- HEAD base antes del reanclaje: `2662998 docs(phase-3c): record payable generation security audit`.
- Estado Git al iniciar reanclaje: limpio.
- Base local principal: `medisoft_hoteles_import`.
- No avanzar a pagos reales, Caja, Fase 3D ni `/api/sync`.
- Nota: existen commits de 3C-A/B/C y revisiones posteriores, pero el nuevo reanclaje no los considera cierre formal.
- Verificacion previa del bloque documental: `php -l`, health, preflights, HTTP sin
  sesion, prueba de upload controlada, rechazo de extension invalida, conteos DB
  antes/despues y `git diff --check` ejecutados. QA manual post-hotfix 4C-A reportada
  por el usuario como funcional.

## Fase 4A Centro Documental

- 4A-0 contrato y diagnostico: completada documentalmente.
- 4A-A migracion base: completada con migracion aditiva, backup previo, tablas vacias y registro en `migrations`.
- 4A-B read-only: completada con modelo `Documento`, controlador GET, vistas de listado/detalle y navegacion segura.
- 4A-C upload seguro: completada tecnicamente con formulario, POST + CSRF, validacion MIME/extension/tamano, storage privado, vinculo opcional por entidad y auditoria. QA manual post-hotfix reportada por el usuario como funcional.
- HEAD al iniciar: `35abdc7 fix(pwa): use hotel branding assets for push notifications`.
- Git al iniciar: limpio.
- Patrones detectados: `public_html/uploads` para assets publicos; `storage/reportes` + `ReporteLinkController` como patron privado seguro.
- No existen tablas generales `documentos`, `documento_entidades` ni `documento_tipos`; existen `reporte_links` y `reporte_link_envios` para reportes PDF.
- Propuesta: tablas aditivas `documento_tipos`, `documentos`, `documento_entidades`.
- Storage recomendado: `STORAGE_PATH/documentos/{hotel_id}/{yyyy}/{mm}` con descarga por controlador.
- Backup 4A-A: `src/storage/backups/phase4a_20260615_182352_before_document_center_medisoft_hoteles_import.sql`, SHA256 `698A304F69B312EF06EABA787C83969629F2B14BCA096906CC08CADDC7D898F0`, tamano `1535817`.
- Migracion 4A-A: `migrations/20260615_004_fase_4a_centro_documental_base.sql`.
- Tablas documentales creadas y vacias: `documento_tipos=0`, `documentos=0`, `documento_entidades=0`.
- Sin uploads, sin POST, sin descargas, sin acciones de borrado y sin exposicion publica de documentos.
- Rutas read-only 4A-B: `GET /documentos`, `GET /documentos/{id}`, `GET /documentos/entidad/{tipo}/{id}`.
- Las vistas no muestran `storage_path` ni rutas internas.
- Backup 4A-C: `src/storage/backups/phase4a_c_20260615_190912_before_document_upload_medisoft_hoteles_import.sql`, SHA256 `DF150F705824973621B9A1276980DC73ECB7AE5261B67A5D71E541FE13797446`, tamano `1541523`.
- Prueba 4A-C: documento `#1` creado en hotel `1`, vinculado a proveedor `#8`, storage privado `documentos/hotel_1/2026/06/doc_20260615_191313_37017248e4f9b6e2.pdf`, auditoria `documentos.cargado`.
- Hotfix post-QA 4A-C: tipos documentales globales creados (`6`), carga general sin IDs
  manuales de entidad, CSS relativo corregido, prueba controlada documento `#2` con tipo
  Contrato.
- Revision tecnica post-QA 4A: la ruta contextual `/documentos/entidad/{tipo}/{id}` valida que la entidad exista en el hotel actual antes de mostrar documentos o enlace de carga.
- Auditoria seguridad 4A: sin rutas de descarga, edicion ni borrado documental; sin exposicion de `storage_path`; sin referencias documentales en PWA/offline; sin Caja, pagos, abonos ni `/api/sync`.
- Cierre tecnico 4A: contrato, migracion base, read-only, upload seguro, hotfix post-QA, revision, auditoria, rollback, QA y fuentes de verdad documentados.

## Fase 4B Descarga segura de documentos

- 4B-0 contrato y diagnostico: completado documentalmente.
- 4B-A descarga segura autenticada: implementada tecnicamente y validada manualmente por el usuario.
- 4B-B auditoria de descargas: implementada tecnicamente, verificada y validada manualmente por el usuario.
- 4B-C-A edicion metadata: implementada tecnicamente con GET/POST, CSRF, validacion por hotel y auditoria diferencial.
- Verificacion 4B-C-A: GET/POST sin sesion bloqueados, GET con sesion `200`, POST no-op
  sin cambios persistidos, CSRF invalido bloqueado y prueba transaccional con rollback
  valida `documentos.metadata_actualizada`.
- QA manual 4B-C-A: reportada por el usuario como correcta.
- Revision tecnica post-QA 4B: `php -l`, health, preflights, HTTP sin sesion y SQL
  read-only ejecutados sin errores bloqueantes.
- Auditoria seguridad post-QA 4B: sin exposicion de `storage_path` en vistas, sin
  links publicos, sin borrado, sin reemplazo, sin Caja, sin pagos, sin abonos y sin
  cambios en `/api/sync`.
- Documento: `docs/fase_4B_0_contrato_descarga_segura_documentos.md`.
- Ruta implementada: `GET /documentos/{id}/descargar`.
- Controlador: `DocumentoController::descargarAction()`.
- Reglas clave: `requireAuth`, contexto hotelero, validacion `id + hotel_id`, documento `activo`, `realpath` bajo `STORAGE_PATH/documentos`, headers privados y sin rutas publicas.
- Hay POST de carga documental y POST de metadata ya autorizados; no hay POST de
  borrado, reemplazo, links publicos, Caja, pagos ni abonos.
- 4B-B agrega escritura controlada en `logs_auditoria` para descargas exitosas y bloqueadas.
- Verificacion HTTP: sin sesion `303` a login; documento `#1` de Los Cedros descarga `200`; documento `#3` de otro hotel redirige `303` a `/documentos`.
- Verificacion 4B-B: `documentos.descargado=1`, `documentos.descarga_bloqueada=1`,
  `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`.
- Prohibido: links publicos, edicion de archivo/storage, borrado, Caja, pagos, abonos,
  PWA/offline, Fase 3D y `/api/sync`.
- Siguiente accion: nuevo bloque autorizado por el usuario; no avanzar automaticamente a borrado, links publicos, reemplazo, pagos, Caja, Fase 3D ni NP-A.

## Fase 4C Documentos por entidad

- 4C-0 contrato y diagnostico: completado documentalmente.
- Documento: `docs/fase_4C_0_contrato_documentos_entidad.md`.
- Entidades objetivo: proveedor, compra, cuenta por pagar, huesped y reservacion.
- Infraestructura reutilizada: `documento_entidades`, `Documento::documentosPorEntidad()`,
  `DocumentoController::entidadAction()` y `GET /documentos/entidad/{tipo}/{id}`.
- 4C-A implementa secciones contextuales en fichas de proveedor, compra, CxP, huesped y
  reservacion mediante `src/app/views/partials/documentos_entidad.php`.
- Los controladores pasan metadata segura consultada por `hotel_id`.
- La accion `Vincular documento` abre el flujo existente
  `/documentos/subir?entidad_tipo=...&entidad_id=...`.
- Sin DB, sin migraciones, sin nuevos POST ni rutas nuevas en 4C-A.
- Prohibido: borrado, reemplazo de archivo, links publicos, Caja, pagos, abonos,
  Fase 3D, NP-A y `/api/sync`.
- QA manual 4C-A: completada por el usuario despues del hotfix de `Vincular documento`.
- Cierre tecnico 4C: documentado en `docs/cierre-tecnico-bloque-cola.md`.
- Siguiente accion recomendada: nuevo bloque explicito; no avanzar automaticamente a
  borrado, reemplazo, links publicos, Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

## Fase 4D Archivado documental

- 4D-0 contrato y diagnostico: completado documentalmente.
- Documento: `docs/fase_4D_0_contrato_archivado_documental.md`.
- Diagnostico: `documentos.estado` ya soporta `activo`, `archivado` y `eliminado`;
  todavia no hay accion formal de archivado, restauracion ni baja logica.
- Regla central futura: cambio de estado solo por POST + CSRF, busqueda `id + hotel_id`,
  auditoria antes/despues y sin borrar archivos fisicos.
- 4D-A recomendado: archivado/restauracion reversible (`activo <-> archivado`).
- 4D-B queda diferido: baja logica hacia `eliminado` con confirmacion fuerte.
- Sin codigo, sin DB, sin migraciones, sin rutas nuevas, sin Caja, pagos, abonos,
  Fase 3D, NP-A ni `/api/sync`.
- 4D-A implementado: POST `/documentos/{id}/archivar` y
  `/documentos/{id}/restaurar`, CSRF, `Documento::actualizarEstado()`, auditoria
  `documentos.estado_actualizado` y botones en detalle documental.
- Verificacion 4D-A: `php -l`, health, preflights, HTTP sin sesion, SQL read-only,
  prueba transaccional con rollback y `git diff --check` ejecutados sin errores
  bloqueantes.
- Revision tecnica/auditoria 4D-A: sin hallazgos bloqueantes; se confirma POST + CSRF,
  filtro `id + hotel_id`, auditoria, ausencia de `DELETE`, ausencia de borrado fisico
  y ausencia de Caja, pagos, abonos, NP-A, Fase 3D y `/api/sync`.
- QA manual 4D-A: reportada por el usuario como correcta.
- 4D-B-0 contrato: documenta baja logica futura hacia `eliminado`, sin rutas nuevas,
  sin POST, sin modelo nuevo, sin DB y sin migraciones.
- 4D-B-A implementado: POST `/documentos/{id}/eliminar`, CSRF,
  `Documento::actualizarEstado()` con `activo/archivado -> eliminado`, auditoria y
  boton `Baja logica` en detalle documental.
- QA manual 4D-B-A queda diferida por instruccion del usuario.
- Cierre tecnico 4D: bloque documental culminado tecnicamente con QA manual de 4D-B-A
  diferida.
- Siguiente accion recomendada: pasar a Personal base como siguiente bloque seguro.

## Reanclaje Fase 3C

- 3C-0 contrato y diagnostico: completada en `9897465`.
- 3C-A simulador: codigo vigente con ruta/vista/modelo verificados y commiteados en `c0ff5a1`.
- 3C-B generacion manual: implementada tecnicamente y validada manualmente por el usuario.
- 3C-C validaciones: checkers read-only refuerzan reglas de consistencia CxP y ausencia de movimientos/pagos/abonos.
- SQL read-only 3C-C: CxP=2, movimientos CxP=0, inconsistencias=0, Caja-CxP=0, `compra_pagos` inexistente.
- Revision tecnica 3C actual: completada sin hallazgos bloqueantes.
- Auditoria seguridad 3C actual: completada sin hallazgos bloqueantes.
- Cierre tecnico 3C: completado documentalmente, sin autorizar pagos, abonos, Caja, Fase 3D ni `/api/sync`.
- QA manual final 3C: reportada por el usuario como OK.
- Auditoria `2662998`: documentacion de auditoria prematura bajo el reanclaje.
- Cambios PWA no relacionados fueron diagnosticados, validados manualmente y commiteados por separado en `35abdc7`.
- Cambios no relacionados ya separados en `e52766e`: `src/app/controllers/DashboardController.php`, `src/app/controllers/HabitacionController.php`, `src/app/controllers/NotificacionController.php`, `src/app/views/layout/sidebar.php`, `src/app/views/notificaciones/index.php`.
- Siguiente accion recomendada: continuar solo con Fase 4A autorizada. No avanzar a pagos, abonos, Caja ni Fase 3D.

## Bloque base previo: Fase 3B CxP read-only

- Ultimo mensaje real del bloque previo: Fase 3C-B generacion manual controlada de CxP desde compra recibida, sin Caja ni pagos.
- Estado: cerrado tecnicamente; QA manual reportada por el usuario.
- Backup previo a DB del bloque previo:
  - `src/storage/backups/phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql`
  - SHA256: `24663D206AE15B86B001708D8BC2665541548443A0EA363548CAFC3FDF3A4D2C`
  - tamano: `3017728`

## Contrato activo

La Fase 3B aplicada solo permite una fundacion read-only de cuentas por pagar:

- tablas nuevas vacias de CxP;
- modelo/controlador/vistas read-only;
- rutas GET de listado y detalle;
- navegacion basica;
- health/preflight compatibles;
- documentacion de QA, decisiones, rollback y fuentes de verdad.

No permite pagos, Caja, generacion automatica desde compras, saldos operativos, CxC, nomina, permisos profundos ni cambios en `/api/sync`.

## Fases completadas con commit

- `d1f1431` - `feat: add read-only received purchase detail`
- `32abb7b` - `feat: add read-only received purchases reports`
- `052fd7a` - `fix: harden minimal purchase receiving flow`
- `3d8f997` - `feat: expand supplier profile and purchase history`
- `673f47f` - `docs: draft accounts payable foundation`
- `1fa1653` - `feat(phase-3b): add read-only accounts payable foundation`
- `2535dd1` - `docs: record post-3b audit closure`
- `ca2bda4` - `docs: record security audit closure`
- `8a49995` - `docs: close authorized technical block`
- `dc3c150` - `fix: improve late check-in modal layout`

## Diagnostico 3C-0

- Estado Git al inicio de 3C-0: limpio.
- QA manual del bloque anterior: reportada como realizada por el usuario.
- Cambio visual de reservaciones: commiteado en `dc3c150`.
- Compras recibidas detectadas: 2.
- Compras recibidas elegibles para CxP: 2.
- CxP actuales: 0.
- Movimientos Caja-CxP: 0.

## Implementacion 3C-A

- Ruta nueva: `GET /cuentas-por-pagar/generacion-preview`.
- Vista nueva: `src/app/views/cuentas_por_pagar/generacion_preview.php`.
- Modelo CxP consulta compras, proveedores, hoteles, detalles y CxP con `hotel_id`.
- La vista muestra compra, proveedor, hotel, fecha, total, estado, CxP existente, elegibilidad y bloqueo.
- No hay POST, pagos, Caja ni generacion automatica.
- Estado corregido: ruteado, protegido por `CuentaPorPagarController::before()`, navegable desde CxP y verificado automaticamente.

## Implementacion 3C-B

- Ruta nueva activa: `POST /cuentas-por-pagar/generar-desde-compra/{id}`.
- Controller: `CuentaPorPagarController::generarDesdeCompraAction()`.
- Modelo: `CuentaPorPagar::generarDesdeCompraRecibida()`.
- Usa CSRF, transaccion y bloqueo `FOR UPDATE`.
- Valida compra recibida, proveedor del mismo hotel, total positivo, detalles existentes y no duplicado.
- Inserta solo en `cuentas_por_pagar`.
- Registra auditoria en `logs_auditoria`.
- No inserta pagos, abonos, movimientos de CxP ni movimientos de Caja.

## Prueba local historica 3C-B

- Backup valido previo:
  - `src/storage/backups/phase3c_b_20260615_053711_before_manual_cxp_medisoft_hoteles_import_notablespaces.sql`
  - SHA256: `8086F91DF17DB09CFBB28E7E12BED475FDD81FB538948F4B60141A90BE9E801D`
  - tamano: `1528988`
- Primer intento de backup con routines/tablespaces genero advertencias de privilegios y no se toma como respaldo valido:
  - `src/storage/backups/phase3c_b_20260615_053658_before_manual_cxp_medisoft_hoteles_import.sql`
- Registro usado para prueba:
  - hotel_id: `4`
  - compra_id: `5`
  - total: `1000.00`
- Resultado:
  - CxP generada: `id = 1`
  - doble generacion: bloqueada con mensaje de CxP existente
  - `cuentas_por_pagar`: `0 -> 1`
  - `cuentas_por_pagar_movimientos`: `0 -> 0`
  - `logs_auditoria`: `23 -> 24`
  - `cajas`: `3 -> 3`
  - `movimientos_caja`: `1402 -> 1402`

## Prueba local vigente 3C-B

- Backup valido previo:
  - `src/storage/backups/phase3c_b_20260615_144908_before_manual_cxp_medisoft_hoteles_import.sql`
  - SHA256: `C0403F7B5ACBDA35EF4C05E2840546D5D9978802A21C9736BEBC6FF462518061`
  - tamano: `1532615`
- Registro usado para prueba:
  - hotel_id: `4`
  - compra_id: `2`
  - total: `1900.00`
- Resultado:
  - CxP generada: `id = 2`
  - doble generacion: bloqueada con mensaje de CxP existente
  - `cuentas_por_pagar`: `1 -> 2`
  - `cuentas_por_pagar_movimientos`: `0 -> 0`
  - `logs_auditoria`: `25 -> 26`
  - `cajas`: `3 -> 3`
  - `movimientos_caja`: `1402 -> 1402`

## Cambios pendientes clasificados post-commit

### Relacionados con Fase 3B

- Ninguno pendiente.

### No relacionado con Fase 3B

- Ninguno pendiente. El ajuste visual de reservaciones quedo commiteado en `dc3c150`.

### Dudosos

- Ninguno identificado.

## Pruebas obligatorias para cierre

- `php -l` sobre controlador, modelo, vistas, rutas, sidebar y checkers modificados.
- `health_check_fase_1a.php`.
- `preflight_compras_minimas.php`.
- `preflight_recepcion_compras.php`.
- SQL read-only para validar tablas, migracion registrada, conteos CxP en cero y no generacion de pagos.
- HTTP sin sesion `GET /cuentas-por-pagar`.
- `git diff --check`.

## Auditoria de seguridad post-cierre

- Estado: completada sin hallazgos bloqueantes.
- CxP conserva solo lectura y rutas GET.
- No hay integracion accidental con Caja.
- No hay escritura CxP desde compras/proveedores.
- `/api/sync` sigue bloqueado y fuera de alcance.
- Riesgo residual futuro: escrituras CxP deben validar proveedor/compra por `hotel_id` antes de insertar.

## QA manual

Fase 3C-A/3C-B: QA manual reportada por el usuario como completada. Ver `docs/qa-pendiente-cola.md`.

## Documento de cierre

Ver `docs/cierre-tecnico-bloque-cola.md`.

## Diagnostico NP-0 (Personal y Nomina)

- HEAD al iniciar: `5dfe665`; Git limpio; rama `feature/saas-multihotel` 21 commits por delante de origin (sin push).
- Hoy "trabajador" = `usuarios` (tabla global, sin `hotel_id`, `rol` de sistema) + pivote `hotel_usuarios`. No hay rol laboral, deuda ni saldo por persona.
- Caja revisada en solo lectura: `cajas`, `movimientos_caja`, `cortes_caja`, `categorias_movimientos`. NO existe categoria "Nomina". Movimientos Caja-nomina: 0 (debe seguir en 0).
- Responsable de mantenimiento/limpieza: hoy es texto libre `mantenimientos_habitaciones.realizado_por`; no hay tabla `limpieza`/`tareas`. La referencia NP sera logica/opcional, sin alterar mantenimiento.
- NP-0 confirmo que no existia ninguna tabla `trabajador*`; NP-A ya crea las seis
  tablas base vacias.
- Patrones reutilizables: `AuditService::record()` (auditoria), migracion aditiva idempotente (modelo `20260615_003_fase_3b_cxp_base.sql`), modelos con filtro `hotel_id`.
- Confirmado: NO hace falta tocar Caja, ni `usuarios` destructivamente, ni `/api/sync`.
- Diseno de 6 tablas (`trabajadores`, `trabajador_pagos`, `trabajador_anticipos`, `trabajador_prestamos`, `trabajador_asistencias`, `trabajador_documentos`) documentado en `docs/fase_NP_0_contrato_diagnostico.md`.
- Backup NP-A valido:
  `src/storage/backups/phase_np_a_20260616_021311_before_personal_base_medisoft_hoteles_import.sql`.
- SHA256 NP-A: `0F9E64B040437A73D559534E5753133F4C3508C29F5B9EA0278B351097666246`.
- Migracion NP-A: `migrations/20260616_001_fase_np_a_personal_base.sql`.
- Conteos NP-A posteriores: seis tablas `trabajador*` en 0 registros.
- Caja/Nomina sigue en cero: no hay movimientos ni categoria Nomina.

## Siguiente accion

NP-A UI read-first queda implementada con listado/ficha basica en GET y QA manual
diferida. NP-B-0 deja definido el contrato de CRUD basico de trabajadores. NP-B-A queda
implementado con alta/edicion/baja logica/reactivacion, sin pagos, sin Caja y sin
`/api/sync`.
Siguiente paso formal recomendado: NP-C-0 contrato de ledger laboral o bloque
Tareas/Limpieza/Mantenimiento base, sin integracion con Caja.
No hacer push.

## Diagnostico TLM-0 (Tareas, Limpieza y Mantenimiento)

- Estado formal: `CONTRATO_TLM_0_COMPLETADO`.
- Documento creado: `docs/fase_TLM_0_contrato_diagnostico.md`.
- Rama: `feature/saas-multihotel`.
- El diagnostico fue read-only y no escribio datos.
- Fuente actual de disponibilidad: `habitaciones.estado`.
- Fuente actual de mantenimiento: `mantenimientos_habitaciones`.
- No existe tabla propia de tareas operativas todavia.
- Conteos observados:
  - `mantenimientos_habitaciones`: 10.
  - mantenimientos sin `hotel_id`: 0.
  - habitaciones en limpieza: 12.
  - habitaciones en mantenimiento: 2.
  - trabajadores: 0.
- Modulos `limpieza` y `mantenimiento` existen y estan activos en los 4 hoteles
  revisados.
- Riesgo: naranja por impacto potencial en disponibilidad y reservaciones.
- Limites: no Caja, no pagos, no abonos, no nomina, no PWA/offline/cache, no `/api/sync`.

## Siguiente accion

Siguiente cola exacta recomendada:

`[COLA_TLM_A_MIGRACION_BASE_TAREAS]`

Crear migracion base aditiva de tareas operativas con backup previo de
`medisoft_hoteles_import`. No crear UI ni POST funcional todavia si la migracion no queda
verificada.

## Migracion TLM-A (Tareas operativas base)

- Estado formal: `MIGRACION_TLM_A_TAREAS_BASE_COMPLETADA_QA_DIFERIDA`.
- Backup valido:
  `src/storage/backups/phase_tlm_a_20260616_030648_before_tasks_base_medisoft_hoteles_import.sql`.
- SHA256: `C76243D8AF98A2D9AA9D94FB5B1BB4D29369A861594141C7EC397A13AF3CF242`.
- Migracion aplicada: `migrations/20260616_002_fase_tlm_a_tareas_base.sql`.
- Tablas creadas vacias: `tareas_operativas`, `tarea_eventos`.
- No se creo UI, rutas ni POST.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.

## Siguiente accion

Siguiente cola exacta recomendada:

`[COLA_TLM_B_TAREAS_READ_ONLY]`

Crear capa GET read-only de tareas operativas: listado, detalle y estado vacio, sin POST,
sin cambio de estados de habitacion y sin tocar Caja.

## Read-only TLM-B (Tareas operativas)

- Estado formal: `TAREAS_READ_ONLY_TLM_B_COMPLETADO_QA_DIFERIDA`.
- Modelo: `TareaOperativa`.
- Controlador: `TareaController`.
- Rutas GET:
  - `/tareas`;
  - `/tareas/{id}`.
- Vistas:
  - `app/views/tareas/index.php`;
  - `app/views/tareas/ver.php`.
- Sidebar: `Tareas` bajo Operaciones.
- Sin POST, sin creacion/asignacion/cierre/cancelacion, sin cambios de habitacion.
- Health valida TLM-B con `ERROR: 0`.

## Siguiente accion

Siguiente cola exacta recomendada:

`[COLA_TLM_C_CREACION_MANUAL_TAREAS]`

Implementar creacion manual controlada con POST + CSRF, auditoria y validacion de
hotel. Mantener prohibido cambiar automaticamente `habitaciones.estado`.

## Creacion manual TLM-C

- Estado formal: `CREACION_MANUAL_TLM_C_COMPLETADA_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_C_creacion_manual_tareas.md`.
- Rutas:
  - `GET /tareas/crear`;
  - `POST /tareas`.
- Modelo: `TareaOperativa::crearParaHotel()` crea tarea y evento inicial en transaccion.
- Controlador: `TareaController::guardarAction()` exige POST, CSRF y permiso
  `habitaciones.mantenimiento`.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

## Siguiente accion

Siguiente cola exacta recomendada:

`[COLA_TLM_D_ASIGNACION_TRABAJADOR]`

Implementar asignacion opcional de tareas a trabajadores activos del mismo hotel, sin
pagos, sin Caja y sin modificar automaticamente estados de habitacion.

## Asignacion TLM-D

- Estado formal: `ASIGNACION_TLM_D_COMPLETADA_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_D_asignacion_trabajador.md`.
- Ruta nueva:
  - `POST /tareas/{id}/asignar`.
- El detalle de tarea permite asignar trabajador activo cuando la tarea esta `pendiente`
  o `asignada`.
- La asignacion valida hotel, trabajador activo, CSRF y permiso
  `habitaciones.mantenimiento`.
- No se inicia, completa ni cancela tarea.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.
- QA manual queda diferida; no habia trabajadores activos en la base local al implementar.

## Siguiente accion

Siguiente cola exacta recomendada:

`[COLA_TLM_E_ESTADOS_TAREA]`

Implementar inicio, cierre y cancelacion manual de tareas con auditoria, sin Caja, sin
pagos y sin cambios automaticos de disponibilidad de habitacion.

## Estados manuales TLM-E

- Estado formal: `ESTADOS_TLM_E_COMPLETADOS_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_E_estados_tarea.md`.
- Rutas nuevas:
  - `POST /tareas/{id}/iniciar`;
  - `POST /tareas/{id}/completar`;
  - `POST /tareas/{id}/cancelar`.
- El detalle de tarea muestra acciones para tareas activas.
- Cada transicion valida hotel, CSRF y permiso `habitaciones.mantenimiento`.
- Se registran eventos y auditoria.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.
- QA manual queda diferida.

## Siguiente accion

Siguiente cola exacta recomendada:

`[COLA_TLM_F_CONTEXTUAL_HABITACION_TRABAJADOR]`

Mostrar tareas relacionadas en fichas de habitacion y trabajador, sin crear nuevos POST y
sin automatizar disponibilidad.

## Contexto visual TLM-F

- Estado formal: `CONTEXTUAL_TLM_F_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_F_contextual_tareas.md`.
- Se agrego lectura contextual de tareas desde habitacion y trabajador.
- La integracion es read-only y reutiliza `GET /tareas/{id}` para detalle.
- No agrega rutas, POST, migraciones ni escrituras.
- No cambia disponibilidad, mantenimiento historico, Caja, pagos, abonos, nomina ni
  `/api/sync`.
- QA manual queda diferida.

## Siguiente accion

Siguiente cola exacta recomendada:

`[COLA_TLM_G_HEALTH_PREFLIGHTS]`

Agregar validaciones de consistencia para tareas operativas y eventos, sin crear
funcionalidad nueva ni tocar disponibilidad de habitaciones.

## Health/preflight TLM-G

- Estado formal: `PREFLIGHT_TLM_G_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_G_preflight_consistencia.md`.
- Preflight nuevo: `src/tools/saas/preflight_tareas_operativas.php`.
- Health checker actualizado a TLM-G.
- No agrega rutas, UI, POST, migraciones ni escrituras.
- No toca disponibilidad, mantenimiento historico, Caja, pagos, abonos, nomina ni
  `/api/sync`.

## Siguiente accion

Siguiente cola exacta recomendada:

`[COLA_TLM_H_REVISION_AUDITORIA_CIERRE]`

Revisar tecnicamente, auditar y cerrar el bloque Tareas/Limpieza/Mantenimiento sin
agregar funcionalidades nuevas.

## Cierre tecnico TLM-H

- Estado formal: `BLOQUE_TLM_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_H_cierre_bloque.md`.
- Bloque TLM queda cerrado tecnicamente hasta TLM-G.
- QA manual global queda diferida por instruccion del usuario.
- Siguiente accion segura: nuevo bloque autorizado independiente o QA manual TLM cuando
  el usuario vuelva.

## Contrato NP-C-0 (ledger laboral)

- Estado formal: `CONTRATO_NP_C_LEDGER_LABORAL_COMPLETADO`.
- Documento creado: `docs/fase_NP_C_0_contrato_ledger_laboral.md`.
- Objetivo: definir ledger laboral informativo sobre `trabajador_pagos`,
  `trabajador_anticipos`, `trabajador_prestamos` y `trabajador_asistencias`.
- NP-C-0 no agrega rutas, vistas, migraciones, POST ni escrituras.
- Caja, pagos reales, abonos, categoria Nomina y `/api/sync` siguen fuera de alcance.
- Siguiente accion segura: NP-C-A ledger laboral read-only por trabajador y resumen por
  hotel, sin POST y sin crear datos.

## Ledger laboral NP-C-A

- Estado formal: `LEDGER_LABORAL_NP_C_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_A_ledger_read_only.md`.
- La ficha de trabajador muestra resumen, conceptos, anticipos y prestamos en modo
  read-only.
- No agrega rutas, POST, migraciones ni escrituras.
- No toca Caja, pagos reales, abonos, categoria Nomina ni `/api/sync`.
- Siguiente accion segura: NP-C-E health/preflight especifico de ledger laboral, o
  revision/auditoria si se decide cerrar esta parte antes de habilitar escrituras.

## Preflight ledger NP-C-E

- Estado formal: `PREFLIGHT_LEDGER_NP_C_E_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_E_preflight_ledger.md`.
- Herramienta: `src/tools/saas/preflight_personal_ledger.php`.
- Valida consistencia de tablas `trabajador_*`, ausencia de categoria Nomina en Caja y
  ausencia de rutas/escrituras operativas de ledger.
- No crea rutas, vistas, POST, migraciones ni datos.
- Siguiente accion segura: revision tecnica/auditoria/cierre del bloque NP-C read-only,
  o contrato futuro para primera escritura laboral sin Caja.

## Cierre tecnico NP-C-F

- Estado formal: `BLOQUE_NP_C_READ_ONLY_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_F_cierre_ledger_read_only.md`.
- Cierra contrato, vista read-only y preflight del ledger laboral.
- No hay pagos reales, abonos, Caja, categoria Nomina ni `/api/sync`.
- Siguiente accion segura: contrato NP-C-B-0 para primera escritura laboral controlada,
  sin Caja, o QA manual diferida del bloque read-only.

## Contrato NP-C-B-0 conceptos laborales

- Estado formal: `CONTRATO_NP_C_B_CONCEPTOS_LABORALES_COMPLETADO`.
- Documento creado: `docs/fase_NP_C_B_0_contrato_conceptos_laborales.md`.
- Define una futura escritura manual en `trabajador_pagos` para comision, bono,
  descuento y ajuste.
- No implementa rutas, vistas, POST ni escrituras.
- Mantiene fuera tipo `pago` como salida real, Caja, categoria Nomina y `/api/sync`.

## Conceptos laborales NP-C-B-A

- Estado formal: `CONCEPTOS_LABORALES_NP_C_B_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_B_A_conceptos_laborales.md`.
- Se implementa POST manual controlado en `trabajador_pagos` para comision, bono,
  descuento y ajuste.
- La accion exige trabajador activo del hotel actual, CSRF y permiso existente.
- Auditoria registra el concepto con banderas `sin_caja` y `sin_pago_real`.
- No crea pagos reales, abonos, anticipos, prestamos, asistencia, categoria Nomina ni
  movimientos de Caja.
- No se toco `/api/sync`.
- Siguiente accion segura: revision tecnica/auditoria/cierre de NP-C-B-A antes de abrir
  anticipos, prestamos, asistencia o cualquier salida real.

## Cierre tecnico NP-C-B-F

- Estado formal: `BLOQUE_NP_C_B_CONCEPTOS_LABORALES_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_B_F_cierre_conceptos_laborales.md`.
- Bloque de conceptos laborales manuales queda cerrado tecnicamente.
- Revision tecnica posterior aplicada en `fix(review): stabilize worker concept entry`.
- QA manual queda diferida porque no hay trabajadores locales y el usuario pidio omitir QA
  manual por ahora.
- Siguiente accion segura: contrato nuevo para anticipos/prestamos o asistencia; no
  implementar sin contrato explicito.

## Contrato NP-C-C-0 anticipos y prestamos

- Estado formal: `CONTRATO_NP_C_C_ANTICIPOS_PRESTAMOS_COMPLETADO`.
- Documento creado: `docs/fase_NP_C_C_0_contrato_anticipos_prestamos.md`.
- Define futura escritura manual en `trabajador_anticipos` y `trabajador_prestamos`.
- No implementa rutas, vistas, POST ni escrituras.
- Caja, pagos reales, abonos, liquidaciones, Nomina y `/api/sync` siguen fuera de alcance.
- Siguiente accion segura: NP-C-C-A registro manual controlado de anticipos/prestamos sin
  Caja, solo si se mantiene la autorizacion.

## Anticipos y prestamos NP-C-C-A

- Estado formal: `ANTICIPOS_PRESTAMOS_NP_C_C_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_C_A_anticipos_prestamos.md`.
- Se implementa captura manual de anticipos y prestamos desde ficha de trabajador activo.
- La accion exige hotel actual, CSRF, permiso existente y trabajador activo.
- `saldo_pendiente` inicial se deriva del monto y no se recibe desde formulario.
- No crea pagos reales, abonos, liquidaciones, asistencia, categoria Nomina ni movimientos
  de Caja.
- No se toco `/api/sync`.
- Siguiente accion segura: revision tecnica/auditoria/cierre de NP-C-C-A antes de abrir
  asistencia o abonos/liquidaciones.

## Cierre tecnico NP-C-C-F

- Estado formal: `BLOQUE_NP_C_C_ANTICIPOS_PRESTAMOS_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_C_F_cierre_anticipos_prestamos.md`.
- Bloque de anticipos y prestamos manuales queda cerrado tecnicamente.
- Revision tecnica posterior aplicada en `fix(review): stabilize worker advances and loans copy`.
- QA manual queda diferida porque no hay trabajadores locales.
- Siguiente accion segura: contrato nuevo para asistencia manual basica, sin nomina
  automatica.

## Contrato NP-C-D-0 asistencia manual

- Estado formal: `CONTRATO_NP_C_D_ASISTENCIA_MANUAL_COMPLETADO`.
- Documento creado: `docs/fase_NP_C_D_0_contrato_asistencia_manual.md`.
- Define futura captura manual basica en `trabajador_asistencias`.
- No implementa rutas, vistas, POST ni escrituras.
- No autoriza nomina automatica, descuentos, Caja, pagos reales ni `/api/sync`.
- Siguiente accion segura: NP-C-D-A captura manual de asistencia sin Caja.

## Asistencia manual NP-C-D-A

- Estado formal: `ASISTENCIA_MANUAL_NP_C_D_A_COMPLETADA_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_D_A_asistencia_manual.md`.
- Se implementa POST manual controlado en `trabajador_asistencias`.
- La accion exige hotel actual, CSRF, permiso existente y trabajador activo.
- Se bloquea duplicado por trabajador/dia antes de insertar.
- No crea nomina, pagos reales, abonos, descuentos automaticos ni movimientos de Caja.
- No se toco `/api/sync`.
- QA manual queda diferida porque no hay trabajadores locales y el usuario pidio omitir QA
  manual por ahora.
- Siguiente accion segura: revision/auditoria/cierre de NP-C-D-A antes de abrir
  edicion/anulacion de asistencia, nomina, abonos o Caja.

## Cierre tecnico NP-C-D-F

- Estado formal: `BLOQUE_NP_C_D_ASISTENCIA_MANUAL_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_C_D_F_cierre_asistencia_manual.md`.
- Bloque de asistencia manual queda cerrado tecnicamente.
- No hay nomina automatica, descuentos automaticos, pagos reales, abonos, Caja ni
  cambios en `/api/sync`.
- QA manual queda diferida porque no hay trabajadores locales.
- Siguiente accion segura: contrato nuevo independiente; no abrir Caja ni nomina sin
  autorizacion explicita.

## Contrato NP-D-0 documentos laborales

- Estado formal: `CONTRATO_NP_D_DOCUMENTOS_LABORALES_COMPLETADO`.
- Documento creado: `docs/fase_NP_D_0_contrato_documentos_laborales.md`.
- Define que documentos laborales futuros deben extender el Centro Documental moderno con
  `entidad_tipo = trabajador`.
- `trabajador_documentos` queda congelada como tabla legacy/aditiva de Personal base.
- No agrega codigo, rutas, migraciones, POST ni escrituras.
- No toca Caja, pagos reales, abonos, nomina ni `/api/sync`.
- Siguiente accion segura: NP-D-A integracion read-only/contextual de documentos de
  trabajador, reutilizando Centro Documental existente.

## Documentos laborales NP-D-A

- Estado formal: `DOCUMENTOS_LABORALES_NP_D_A_COMPLETADOS_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_D_A_documentos_laborales_contextuales.md`.
- `trabajador` queda como entidad permitida del Centro Documental moderno.
- La ficha de trabajador muestra documentos vinculados y reutiliza el flujo existente de
  vincular/ver/descargar documentos.
- No se crean rutas nuevas ni uploads propios de Personal.
- `trabajador_documentos` queda congelada como tabla legacy/aditiva.
- No se toca Caja, pagos reales, abonos, nomina ni `/api/sync`.
- Siguiente accion segura: revision/auditoria/cierre de NP-D-A.

## Cierre tecnico NP-D-F

- Estado formal: `BLOQUE_NP_D_DOCUMENTOS_LABORALES_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_D_F_cierre_documentos_laborales.md`.
- Bloque de documentos laborales queda cerrado tecnicamente.
- Centro Documental moderno es la fuente; `trabajador_documentos` sigue congelada.
- QA manual queda diferida.
- Siguiente accion segura: contrato nuevo independiente para la siguiente necesidad.

## Cierre tecnico NP-E Personal operativo base

- Estado formal: `BLOQUE_NP_PERSONAL_OPERATIVO_BASE_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_E_cierre_personal_operativo_base.md`.
- Se cierra documentalmente el conjunto de Personal base, ledger laboral, conceptos,
  anticipos/prestamos, asistencia manual y documentos laborales.
- No agrega codigo, rutas, migraciones ni escrituras.
- No hay nomina automatica, pagos reales, abonos, Caja ni cambios en `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: contrato read-only de reporte de Personal, si se autoriza.

## Contrato NP-F-0 reporte Personal read-only

- Estado formal: `CONTRATO_NP_F_REPORTE_PERSONAL_READONLY_COMPLETADO`.
- Documento creado: `docs/fase_NP_F_0_contrato_reporte_personal_readonly.md`.
- Define un reporte futuro que consolide trabajadores, ledger laboral, asistencia,
  documentos y tareas asignadas solo en lectura.
- No agrega rutas, vistas, modelos, migraciones ni escrituras.
- Caja, nomina, pagos reales, abonos, liquidaciones y `/api/sync` quedan fuera.
- Siguiente accion segura: NP-F-A implementacion read-only del reporte.

## Reporte Personal NP-F-A

- Estado formal: `REPORTE_PERSONAL_NP_F_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_F_A_reporte_personal_readonly.md`.
- Se agrega `/trabajadores/reporte` como GET read-only protegido por guardas de Personal.
- El reporte consolida trabajadores, ledger laboral, asistencia, documentos laborales y
  tareas asignadas por `hotel_id`.
- No agrega POST, nomina, pagos reales, abonos, Caja ni cambios en `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: revision tecnica/auditoria/cierre de NP-F-A.

## Cierre tecnico NP-F-F

- Estado formal: `BLOQUE_NP_F_REPORTE_PERSONAL_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_NP_F_F_cierre_reporte_personal.md`.
- Revision tecnica y auditoria del reporte read-only completadas.
- Verificaciones automaticas pasan con `ERROR: 0` y warnings historicos permitidos.
- QA manual queda diferida.
- No hay nomina, pagos reales, abonos, Caja ni cambios en `/api/sync`.
- Siguiente accion segura: contrato documental independiente para el proximo bloque.

## Contrato TLM-I-0 reporte operativo read-only

- Estado formal: `CONTRATO_TLM_I_REPORTE_OPERATIVO_READONLY_COMPLETADO`.
- Documento creado: `docs/fase_TLM_I_0_contrato_reporte_operativo_readonly.md`.
- Define un reporte futuro de tareas operativas solo lectura.
- No agrega rutas, vistas, modelos, migraciones ni escrituras.
- No crea tareas, no asigna trabajadores, no cambia estados, no toca habitaciones,
  mantenimiento historico, Caja, nomina ni `/api/sync`.
- Siguiente accion segura: TLM-I-A implementacion GET/read-only del reporte.

## Reporte operativo TLM-I-A

- Estado formal: `REPORTE_TLM_I_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_I_A_reporte_operativo_readonly.md`.
- Se agrega `/tareas/reporte` como GET read-only protegido por guardas TLM.
- Consolida estados, categorias, prioridades, riesgos, carga por trabajador/habitacion,
  tareas recientes y eventos recientes.
- No agrega POST, asignaciones, cambios de estado, cambios de habitacion, Caja, nomina ni
  `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: revision tecnica/auditoria/cierre de TLM-I-A.

## Cierre tecnico TLM-I-F

- Estado formal: `BLOQUE_TLM_I_REPORTE_OPERATIVO_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_I_F_cierre_reporte_operativo.md`.
- Revision tecnica y auditoria del reporte operativo read-only completadas.
- Verificaciones automaticas pasan con `ERROR: 0` y warnings historicos permitidos.
- QA manual queda diferida.
- No hay nuevas acciones de tarea, cambios de habitacion, Caja, nomina ni `/api/sync`.
- Siguiente accion segura: contrato documental independiente para el proximo bloque.

## Contrato OP-0 Tablero Operativo Diario read-only

- Estado formal: `CONTRATO_OP_0_TABLERO_OPERATIVO_READONLY_COMPLETADO`.
- Documento creado: `docs/fase_OP_0_contrato_tablero_operativo_readonly.md`.
- Define un tablero futuro de observacion diaria solo lectura para ocupacion,
  reservaciones, habitaciones, tareas, mantenimiento, trabajadores y documentos.
- No agrega rutas, vistas, modelos, migraciones, formularios ni escrituras.
- Caja, pagos, abonos, nomina, cambios de habitacion, offline y `/api/sync` quedan fuera.
- Siguiente accion segura: OP-A implementacion GET/read-only del tablero operativo.

## Tablero operativo OP-A

- Estado formal: `TABLERO_OPERATIVO_OP_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_OP_A_tablero_operativo_readonly.md`.
- Ruta GET/read-only: `/operacion/diaria`.
- Modelo `OperacionDiaria` consolida habitaciones, reservaciones, tareas, mantenimiento,
  trabajadores y documentos por `hotel_id`.
- No agrega POST, migraciones, escrituras, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: revision tecnica/auditoria/cierre OP-A o nuevo contrato
  independiente.

## Cierre tecnico OP-F

- Estado formal: `BLOQUE_OP_TABLERO_OPERATIVO_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_OP_F_cierre_tablero_operativo.md`.
- Revision tecnica y auditoria del tablero operativo read-only completadas.
- Verificaciones automaticas pasan con `ERROR: 0` y warnings historicos permitidos.
- QA manual queda diferida.
- No hay POST, migraciones, escrituras, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- Siguiente accion segura: nuevo contrato independiente.

## Reporte mantenimiento MANT-A

- Estado formal: `REPORTE_MANTENIMIENTO_MANT_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_A_reporte_mantenimiento_readonly.md`.
- No crea modulo nuevo; estabiliza `/reportes/mantenimiento` existente.
- Se corrige scope multihotel de las consultas activas del reporte en `Reporte.php`.
- Se agrega `src/tools/saas/preflight_reporte_mantenimiento.php`.
- Health checker general cubre MANT-A.
- No hay POST, migraciones, escrituras, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: nuevo contrato independiente; no convertir mantenimiento en
  acciones automaticas sin contrato.

## Cierre tecnico MANT-F

- Estado formal: `BLOQUE_MANT_REPORTE_MANTENIMIENTO_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_F_cierre_reporte_mantenimiento.md`.
- Revision tecnica y auditoria del reporte de mantenimiento read-only completadas.
- Verificaciones automaticas pasan con `ERROR: 0` y warnings historicos permitidos.
- QA manual queda diferida.
- No hay POST, migraciones, escrituras, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- Siguiente accion segura: nuevo contrato independiente.

## Mantenimiento inmediato MANT-B

- Estado formal: `MANTENIMIENTO_INMEDIATO_MANT_B_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_B_mantenimiento_inmediato_guardrails.md`.
- Se endurece la ruta existente `POST /habitaciones/{id}/mantenimiento`.
- Se validan accion, tipo, prioridad, motivo y duplicado `en_proceso` por habitacion/hotel.
- Se agrega `src/tools/saas/preflight_mantenimiento_operativo.php`.
- Health checker general cubre MANT-B.
- No hay rutas nuevas, migraciones, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: cierre tecnico MANT-B o nuevo contrato independiente.

## Cierre tecnico MANT-B-F

- Estado formal: `BLOQUE_MANT_B_MANTENIMIENTO_INMEDIATO_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_B_F_cierre_mantenimiento_inmediato.md`.
- Revision tecnica y auditoria del mantenimiento inmediato completadas.
- Verificaciones automaticas pasan con `ERROR: 0` y warnings historicos permitidos.
- QA manual queda diferida.
- No hay rutas nuevas, migraciones, Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Siguiente accion segura: nuevo contrato independiente.

## Contrato MANT-C-0 mantenimiento programado

- Estado formal: `CONTRATO_MANT_C_0_MANTENIMIENTO_PROGRAMADO_COMPLETADO`.
- Documento creado: `docs/fase_MANT_C_0_contrato_mantenimiento_programado.md`.
- No modifica codigo ni DB.
- Siguiente accion segura: MANT-C-A guardrails de programacion/cancelacion existente,
  sin activar mantenimientos pendientes automaticamente.

## Mantenimiento programado MANT-C-A

- Estado formal: `MANTENIMIENTO_PROGRAMADO_MANT_C_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_C_A_guardrails_mantenimiento_programado.md`.
- Endurece rutas existentes sin crear rutas nuevas.
- No activa mantenimientos pendientes automaticamente.
- No toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Siguiente accion segura: revision tecnica/auditoria/cierre MANT-C o QA manual
  diferida.
