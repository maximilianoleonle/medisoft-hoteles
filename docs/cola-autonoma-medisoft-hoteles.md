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

## Reanclaje 5A Personal laboral basico

- Estado formal: `FASE_5A_PERSONAL_LABORAL_BASICO_YA_CUBIERTA_POR_NP`.
- Documento creado: `docs/fase_5A_reanclaje_personal_laboral_basico.md`.
- El roadmap nuevo 5A queda cubierto por NP-A/NP-B/NP-C/NP-D/NP-E/NP-F.
- No se reconstruye Personal ni se crean tablas nuevas.
- No hay pagos reales, nomina automatica, Caja ni cambios en `/api/sync`.

## Contrato 6B Integracion tareas + habitaciones

- Estado formal: `CONTRATO_6B_INTEGRACION_TAREAS_HABITACIONES_COMPLETADO`.
- Documento creado: `docs/fase_6B_0_contrato_integracion_tareas_habitaciones.md`.
- La integracion empezara por indicadores/enlaces read-only y preflights.
- `habitaciones.estado` no debe cambiar automaticamente por tareas.
- La creacion de limpieza sigue solo en `/reportes/limpieza`.
- Siguiente accion segura: 6B-A indicadores read-only de tareas activas en habitaciones.

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

## Cierre tecnico MANT-C-F

- Estado formal: `BLOQUE_MANT_C_MANTENIMIENTO_PROGRAMADO_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_C_F_cierre_mantenimiento_programado.md`.
- Revision tecnica y auditoria completadas.
- No agrega funcionalidad nueva.
- Siguiente accion segura: nuevo contrato independiente; no activar mantenimientos
  vencidos automaticamente sin contrato.

## Contrato MANT-D-0 preview vencidos

- Estado formal: `CONTRATO_MANT_D_0_PREVIEW_VENCIDOS_COMPLETADO`.
- Documento creado: `docs/fase_MANT_D_0_contrato_preview_vencidos.md`.
- No modifica codigo ni DB.
- Siguiente accion segura: MANT-D-A preview GET/read-only de candidatos.

## Preview MANT-D-A mantenimiento programado

- Estado formal: `PREVIEW_MANT_D_A_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_D_A_preview_mantenimiento_programado.md`.
- Ruta GET/read-only: `/reportes/mantenimiento-programado`.
- No activa mantenimientos pendientes automaticamente.
- No cambia habitaciones ni disponibilidad.
- No toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: cierre tecnico documental MANT-D o nuevo contrato
  independiente.

## Cierre tecnico MANT-D-F

- Estado formal: `BLOQUE_MANT_D_PREVIEW_VENCIDOS_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_D_F_cierre_preview_vencidos.md`.
- Revision tecnica/auditoria del preview read-only completadas.
- No agrega activacion, cron, escrituras, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- Siguiente accion segura: contrato independiente para la proxima fase.

## Contrato MANT-E-0 activacion manual

- Estado formal: `CONTRATO_MANT_E_0_ACTIVACION_MANUAL_COMPLETADO`.
- Documento creado: `docs/fase_MANT_E_0_contrato_activacion_manual_vencidos.md`.
- No modifica codigo ni DB.
- La futura activacion debe ser manual, individual, con CSRF, auditoria, transaccion y
  QA manual explicita.
- No se autoriza activacion masiva, automatica, cron ni `activarMantenimientosPendientes()`
  desde web.

## Activacion MANT-E-A mantenimiento programado

- Estado formal: `ACTIVACION_MANUAL_MANT_E_A_COMPLETADA_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_E_A_activacion_manual_vencidos.md`.
- Implementa POST individual desde preview para candidatos vencidos/hoy.
- No usa activacion masiva ni automatica.
- No toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- QA manual queda diferida y debe ejecutarse con backup previo.

## Cierre tecnico MANT-E-F

- Estado formal: `BLOQUE_MANT_E_ACTIVACION_MANUAL_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_E_F_cierre_activacion_manual.md`.
- Revision tecnica/auditoria del POST manual completadas.
- No agrega activacion automatica, cron, acciones masivas, Caja, pagos, abonos, nomina,
  offline ni `/api/sync`.
- Siguiente accion segura: contrato independiente.

## Contrato MANT-G-0 tareas desde mantenimiento

- Estado formal: `CONTRATO_MANT_G_0_TAREAS_DESDE_MANTENIMIENTO_COMPLETADO`.
- Documento creado: `docs/fase_MANT_G_0_contrato_tareas_desde_mantenimiento.md`.
- No modifica codigo ni DB.
- Define como futura integracion que `tareas_operativas` pueda dar seguimiento a un
  mantenimiento mediante `mantenimiento_id`.
- No autoriza automatizacion, cron, cambios de disponibilidad, Caja, pagos, abonos,
  nomina, offline ni `/api/sync`.
- Siguiente accion segura: `[COLA_MANT_G_A_TAREAS_CONTEXTUALES_MANTENIMIENTO]`.

## MANT-G-A tareas contextuales desde mantenimiento

- Estado formal: `TAREAS_CONTEXTUALES_MANT_G_A_COMPLETADAS_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_G_A_tareas_contextuales_mantenimiento.md`.
- Extiende lectura contextual de tareas para entidad `mantenimiento`.
- El preview `GET /reportes/mantenimiento-programado` muestra tareas vinculadas si
  existen.
- No crea tareas, no agrega POST, no cambia habitaciones/mantenimientos y no toca Caja,
  pagos, abonos, nomina, offline ni `/api/sync`.
- Siguiente accion segura: contrato MANT-G-B antes de cualquier creacion manual.

## Contrato MANT-G-B-0 creacion manual de tarea desde mantenimiento

- Estado formal: `CONTRATO_MANT_G_B_0_CREACION_MANUAL_TAREA_MANTENIMIENTO_COMPLETADO`.
- Documento creado:
  `docs/fase_MANT_G_B_0_contrato_creacion_manual_tarea_mantenimiento.md`.
- No modifica codigo ni DB.
- Define una futura accion manual para crear una tarea vinculada a un mantenimiento.
- Prohibe automatizacion, cron, cambios de disponibilidad, Caja, pagos, abonos, nomina,
  offline y `/api/sync`.
- Siguiente accion segura: `[COLA_MANT_G_B_A_CREACION_MANUAL_TAREA_MANTENIMIENTO]`.

## MANT-G-B-A creacion manual de tarea desde mantenimiento

- Estado formal: `CREACION_MANUAL_TAREA_MANT_G_B_A_COMPLETADA_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_G_B_A_creacion_manual_tarea_mantenimiento.md`.
- Ruta POST: `/tareas/desde-mantenimiento/{id}`.
- Crea tarea pendiente con `mantenimiento_id`, `habitacion_id` y
  `origen = mantenimiento`.
- Bloquea duplicado activo por mantenimiento.
- No modifica habitaciones, mantenimientos, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- Siguiente accion segura: revision/auditoria/cierre MANT-G antes de automatizaciones.

## MANT-G-F cierre tecnico tareas desde mantenimiento

- Estado formal: `BLOQUE_MANT_G_TAREAS_DESDE_MANTENIMIENTO_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_MANT_G_F_cierre_tareas_mantenimiento.md`.
- Commits del bloque:
  - `e07e321`
  - `1d33ee8`
  - `46fcf70`
  - `8841430`
- Revision tecnica y auditoria completadas.
- Health y preflights pasan con `ERROR: 0`.
- QA manual queda diferida.
- Siguiente accion segura: ejecutar QA manual MANT-G o abrir contrato nuevo para un
  bloque independiente.

## Contrato LIM-0 limpieza operativa

- Estado formal: `CONTRATO_LIM_0_LIMPIEZA_OPERATIVA_COMPLETADO`.
- Documento creado: `docs/fase_LIM_0_contrato_limpieza_operativa.md`.
- Diagnostica estado `limpieza`, flujo de liberacion existente, tareas de categoria
  limpieza y riesgos de inventario/offline.
- No modifica codigo, DB, rutas, modelos, vistas, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- Siguiente accion segura: LIM-A reporte GET/read-only de limpieza.

## LIM-A reporte limpieza read-only

- Estado formal: `REPORTE_LIM_A_LIMPIEZA_READONLY_COMPLETADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_LIM_A_reporte_limpieza_readonly.md`.
- Ruta GET: `/reportes/limpieza`.
- Vista read-only con habitaciones en limpieza, distribucion por piso y tareas activas de
  limpieza.
- Preflight: `tools/saas/preflight_limpieza_operativa.php`.
- No hay POST, formularios, liberacion automatica, cambios de estado, Caja, pagos,
  abonos, nomina, offline ni `/api/sync`.
- Siguiente accion segura: revision/auditoria/cierre LIM-A.

## LIM-F cierre tecnico limpieza read-only

- Estado formal: `BLOQUE_LIM_LIMPIEZA_READONLY_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_LIM_F_cierre_limpieza_readonly.md`.
- Cierra LIM-0 y LIM-A.
- Verificaciones automaticas pasan con `ERROR: 0`.
- QA manual queda diferida.
- Siguiente accion segura: contrato independiente para un bloque nuevo o QA manual
  diferida.

## Contrato LIM-B-0 creacion manual de tarea de limpieza

- Estado formal: `CONTRATO_LIM_B_0_CREACION_MANUAL_TAREA_LIMPIEZA_COMPLETADO`.
- Documento creado: `docs/fase_LIM_B_0_contrato_creacion_manual_tarea_limpieza.md`.
- Define futura accion manual para crear tarea de limpieza desde habitacion en estado
  `limpieza`.
- No modifica codigo, DB, rutas, modelos, vistas, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- Siguiente accion segura: LIM-B-A implementacion manual controlada.

## LIM-B-A creacion manual de tarea de limpieza

- Estado formal: `CREACION_MANUAL_TAREA_LIM_B_A_COMPLETADA_QA_DIFERIDA`.
- Documento creado: `docs/fase_LIM_B_A_creacion_manual_tarea_limpieza.md`.
- Ruta POST: `/tareas/desde-limpieza/{id}`.
- Crea tarea pendiente de categoria `limpieza` con `origen = habitacion`.
- Bloquea duplicado activo por habitacion.
- No modifica habitaciones, inventario, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.
- Siguiente accion segura: revision/auditoria/cierre LIM-B.

## LIM-B-F cierre tecnico tareas desde limpieza

- Estado formal: `BLOQUE_LIM_B_TAREAS_DESDE_LIMPIEZA_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_LIM_B_F_cierre_tareas_limpieza.md`.
- Commits del bloque:
  - `0165d5c`
  - `c6d6743`
- Revision tecnica y auditoria completadas.
- Health y preflight LIM pasan con `ERROR: 0`.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: contrato independiente para el siguiente bloque de
  tareas/limpieza/mantenimiento o volver a QA manual acumulada.

## Contrato TLM-J-0 agenda de tareas por trabajador

- Estado formal: `CONTRATO_TLM_J_0_AGENDA_TAREAS_TRABAJADOR_COMPLETADO`.
- Documento creado: `docs/fase_TLM_J_0_contrato_agenda_tareas_trabajador.md`.
- Define futura agenda GET/read-only para tareas por fecha/trabajador/categoria/estado.
- No crea rutas ni codigo en esta subfase.
- No toca habitaciones, mantenimientos, inventario, Caja, pagos, abonos, nomina,
  offline ni `/api/sync`.
- Siguiente accion segura: TLM-J-A implementacion GET/read-only.

## TLM-J-A agenda de tareas por trabajador

- Estado formal: `AGENDA_TLM_J_A_TAREAS_TRABAJADOR_READONLY_COMPLETADA_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_J_A_agenda_tareas_trabajador.md`.
- Ruta GET/read-only: `/tareas/agenda`.
- Filtros por fecha, trabajador, categoria y estado.
- No agrega POST, asignaciones, cambios de estado, cambios de habitacion, Caja, nomina,
  offline ni `/api/sync`.
- Siguiente accion segura: revision/auditoria/cierre TLM-J.

## TLM-J-F cierre tecnico agenda de tareas

- Estado formal: `BLOQUE_TLM_J_AGENDA_TAREAS_CERRADO_QA_DIFERIDA`.
- Documento creado: `docs/fase_TLM_J_F_cierre_agenda_tareas.md`.
- Commits del bloque:
  - `968c4be`
  - `8c035a7`
- Revision tecnica y auditoria completadas.
- Health y preflight TLM pasan con `ERROR: 0`.
- QA manual queda diferida.
- Siguiente accion segura: contrato nuevo independiente o QA diferida acumulada.
## 6B-A indicadores read-only de tareas en habitaciones

Estado: implementado tecnicamente, QA manual diferida por instruccion del usuario.

- Documento creado: `docs/fase_6B_A_indicadores_tareas_habitaciones.md`.
- Se agrega resumen read-only de tareas activas por habitacion/hotel desde `TareaOperativa`.
- El listado de habitaciones muestra indicador compacto solo cuando hay tareas activas.
- No se agregan POST, no se crean tareas desde la tarjeta, no se cambia disponibilidad.
- `/reportes/limpieza` sigue siendo la superficie para crear tareas manuales de limpieza.
- Siguiente accion segura: 6B-C evidencias/documentos en tareas o cierre tecnico 6B si se desea consolidar.

## 6C-0 contrato evidencias/documentos en tareas

Estado: contrato completado.

- Documento creado: `docs/fase_6C_0_contrato_evidencias_documentos_tareas.md`.
- Diagnostico: Centro Documental moderno ya soporta entidades, pero falta entidad
  `tarea`.
- La integracion futura debe validar `tareas_operativas.id` + `hotel_id`.
- No se implementaron rutas, modelos, uploads ni cambios DB.
- Siguiente accion segura: 6C-A documentos read-only en detalle de tarea.

## 6C-A documentos read-only en tareas

Estado: implementado tecnicamente, QA manual diferida.

- Documento creado: `docs/fase_6C_A_documentos_readonly_tareas.md`.
- Se agrega lectura de documentos vinculados a tarea con validacion por hotel.
- `GET /tareas/{id}` muestra la seccion documental sin boton de vincular.
- No se habilita upload contextual, no hay rutas nuevas, no hay POST nuevo.
- Siguiente accion segura: 6C-B vinculacion segura desde tarea o cierre tecnico 6C si
  se quiere pausar antes de cargas.

## 6C-B vinculacion segura de documentos en tareas

Estado: implementado tecnicamente, QA manual diferida.

- Documento creado: `docs/fase_6C_B_vinculacion_segura_documentos_tareas.md`.
- Se habilita `tarea` como entidad documental valida.
- `Documento::entidadExisteEnHotel()` valida tareas contra `tareas_operativas` usando
  `id + hotel_id`.
- `GET /tareas/{id}` permite usar los enlaces existentes del Centro Documental para
  ver todos y vincular documentos.
- No se agregan rutas nuevas; se reutiliza `/documentos/subir`.
- No se cambia storage, no se expone `storage_path`, no se toca Caja, nomina, offline
  ni `/api/sync`.
- Siguiente accion segura: cierre tecnico/revision 6C o avanzar a un bloque nuevo
  independiente con QA manual diferida.

## 6C-F cierre tecnico documentos en tareas

Estado: `BLOQUE_6C_DOCUMENTOS_TAREAS_CERRADO_QA_DIFERIDA`.

- Documento creado: `docs/fase_6C_F_cierre_documentos_tareas.md`.
- Cubre 6C-0, 6C-A y 6C-B.
- Verifica que la lectura y vinculacion documental de tareas usen Centro Documental,
  `hotel_id`, CSRF del flujo existente y sin exponer `storage_path`.
- No hay rutas paralelas, cambios de disponibilidad, Caja, nomina, offline ni
  `/api/sync`.
- Siguiente accion segura: 5B Asistencia laboral basica iniciando por contrato.

## Reanclaje 5B Asistencia laboral basica

Estado formal: `FASE_5B_ASISTENCIA_LABORAL_BASICA_YA_CUBIERTA_POR_NP_C_D`.

- Documento creado: `docs/fase_5B_reanclaje_asistencia_laboral_basica.md`.
- La asistencia laboral basica ya existe en `trabajador_asistencias`.
- Ruta existente: `POST /trabajadores/{id}/asistencias`.
- Mantiene sesion, hotel actual, CSRF, trabajador activo, bloqueo de duplicado por dia
  y auditoria.
- No se crean tablas, rutas ni modelos duplicados.
- No hay nomina automatica, pagos reales, abonos, Caja ni `/api/sync`.
- Siguiente accion segura: reanclar 5C contra anticipos/prestamos/saldos ya existentes
  en NP-C-C.

## Reanclaje 5C Anticipos/prestamos/saldos laborales

Estado formal: `FASE_5C_ANTICIPOS_PRESTAMOS_SALDOS_YA_CUBIERTA_POR_NP_C_C`.

- Documento creado: `docs/fase_5C_reanclaje_anticipos_prestamos_saldos.md`.
- Anticipos vigentes: `trabajador_anticipos`.
- Prestamos vigentes: `trabajador_prestamos`.
- Saldo vigente: saldo informativo calculado desde anticipos/prestamos pendientes.
- Rutas existentes: `POST /trabajadores/{id}/anticipos` y
  `POST /trabajadores/{id}/prestamos`.
- No se crean tablas, rutas ni modelos duplicados.
- No hay liquidaciones, descuentos automaticos, pagos reales, abonos, Caja ni
  `/api/sync`.
- Siguiente accion segura: 5D pagos laborales sin Caja automatica iniciando con contrato.

## Contrato 5D-0 Pagos laborales sin Caja automatica

Estado formal: `CONTRATO_5D_PAGOS_LABORALES_SIN_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_5D_0_contrato_pagos_laborales_sin_caja.md`.
- Diagnostico: `trabajador_pagos` esta reservado para conceptos laborales, no para pagos
  reales.
- Se prohibe reutilizar `trabajador_pagos` como pago real sin contrato/migracion futura.
- No se implementan rutas, modelos, migraciones ni pagos.
- No hay Caja, nomina automatica, abonos, liquidaciones, descuentos automaticos ni
  `/api/sync`.
- Siguiente accion segura: 5D-A solo con autorizacion de entidad de pago laboral
  independiente, o 7A CxC read-only.

## Contrato 7A-0 Cuentas por cobrar read-only

Estado formal: `CONTRATO_7A_CXC_READONLY_COMPLETADO`.

- Documento creado: `docs/fase_7A_0_contrato_cuentas_por_cobrar_readonly.md`.
- No existe tabla `cuentas_por_cobrar`.
- Fuentes candidatas: `reservaciones`, `reservacion_pagos`, `reservacion_abonos` y
  `solicitudes_factura`.
- Conteos locales: reservaciones=17, pagos=19, abonos=3, solicitudes_factura=175.
- No se crean rutas, modelos, migraciones ni escrituras.
- No hay cobros, abonos, pagos, Caja, facturacion nueva ni `/api/sync`.
- Siguiente accion segura: 7A-A reporte GET/read-only derivado.

## 7A-A Reporte CxC read-only

Estado formal: `REPORTE_7A_A_CXC_READONLY_COMPLETADO_QA_DIFERIDA`.

- Documento creado: `docs/fase_7A_A_reporte_cxc_readonly.md`.
- Ruta nueva: `GET /cuentas-por-cobrar`.
- Modelo nuevo: `CuentaPorCobrar`.
- Controlador nuevo: `CuentaPorCobrarController`.
- Vista nueva: `cuentas_por_cobrar/index`.
- Preflight nuevo: `tools/saas/preflight_cuentas_por_cobrar.php`.
- El reporte deriva saldos estimados desde reservaciones, pagos, abonos y facturacion
  existente.
- No se crea tabla CxC, no hay POST, cobros, abonos, pagos, Caja ni `/api/sync`.
- Preflight: `ERROR: 0`, con warnings historicos de reconciliacion.
- QA manual queda diferida por instruccion del usuario.
- Siguiente accion segura: revision/cierre de 7A-A o contrato 7B sin implementar cobros.

## 7A-F Cierre CxC read-only

Estado formal: `BLOQUE_7A_CXC_READONLY_CERRADO_QA_DIFERIDA`.

- Documento creado: `docs/fase_7A_F_cierre_cxc_readonly.md`.
- Cubre contrato 7A-0 y reporte 7A-A.
- Confirma ausencia de POST, cobros, abonos, pagos, Caja y `/api/sync`.
- Mantiene warnings historicos como bloqueo para CxC operativa.
- Reconciliacion 7A-R posterior diagnosticada en
  `docs/fase_7A_R_reconciliacion_cxc_readonly.md`.
- 7A-R confirma: 3 pagos huerfanos, 170 solicitudes de factura huerfanas de Los
  Cedros y 3 excedentes por abono demo + pago completo en Maximiliano Leon.
- Contrato 7A-S-0 creado en
  `docs/fase_7A_S_0_contrato_reconciliacion_cxc_controlada.md`.
- 7A-S-0 exige backup, preview read-only, matriz de decision, auditoria y rollback
  antes de cualquier escritura futura.
- Preview 7A-S-A creado en `docs/fase_7A_S_A_preview_reconciliacion_cxc.md`.
- 7A-S-A define decision default: excluir pagos/facturas huerfanas de CxC operativa,
  mantener facturas scoped en reporte read-only y conservar excedentes como
  informativos.
- Politica 7A-S-B creada en `docs/fase_7A_S_B_politica_clasificacion_cxc.md`.
- 7A-S-B adopta politica conservadora: exclusion de huerfanos, facturas scoped solo
  read-only y excedentes informativos.
- Cierre 7A-S-F creado en `docs/fase_7A_S_F_cierre_reconciliacion_cxc.md`.
- 7A-S-F cierra reconciliacion CxC sin escrituras y mantiene politica conservadora.
- Siguiente accion segura vigente despues de 7B-B: 7B-C-0 solo como contrato de
  generacion manual futura, sin implementar escrituras.

## 7B-0 Contrato CxC operativa sin Caja

Estado formal: `CONTRATO_7B_CXC_OPERATIVA_SIN_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_7B_0_contrato_cxc_operativa_sin_caja.md`.
- No se implementan rutas, modelos, migraciones ni UI.
- No hay cobros, abonos, pagos, Caja, facturacion nueva ni `/api/sync`.
- Se propone una entidad futura `cuentas_por_cobrar` solo con migracion aditiva
  autorizada.
- Warnings de 7A bloquean cualquier operacion real hasta reconciliacion o decision
  explicita.
- 7B-A ya fue aplicada despues como migracion base vacia y aditiva, sin datos
  historicos, sin Caja y sin `/api/sync`.
- 7B-B ya fue implementada como listado/detalle read-only sobre tablas vacias.
- Siguiente accion segura: 7B-C-0 solo como contrato de generacion manual futura.

## 7B-A Migracion base CxC vacia

Estado formal: `MIGRACION_7B_A_CXC_BASE_VACIA_COMPLETADA`.

- Documento creado: `docs/fase_7B_A_migracion_cxc_base_vacia.md`.
- Migracion aplicada: `migrations/20260618_001_fase_7b_a_cxc_base_vacia.sql`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_a_cxc_base_20260618_163625.sql`.
- SHA256: `0E13547DF43359E71C1A3503EFD8F3A5B5CD096A9BF843EB19F282C0FBE90514`.
- Tablas nuevas: `cuentas_por_cobrar` y `cuentas_por_cobrar_movimientos`.
- Conteos posteriores: `cuentas_por_cobrar=0` y
  `cuentas_por_cobrar_movimientos=0`.
- No se crearon rutas, modelos PHP, vistas, cobros, pagos, abonos ni movimientos de
  Caja.
- `/api/sync` sigue fuera de alcance.

## 7B-B Listado CxC operativa read-only

Estado formal: `LISTADO_7B_B_CXC_OPERATIVA_READONLY_COMPLETADO`.

- Documento creado: `docs/fase_7B_B_listado_cxc_operativa_readonly.md`.
- Rutas GET:
  - `/cuentas-por-cobrar/operativas`;
  - `/cuentas-por-cobrar/operativas/{id}`.
- El reporte estimado 7A-A permanece en `/cuentas-por-cobrar`.
- Las consultas operativas leen `cuentas_por_cobrar` y
  `cuentas_por_cobrar_movimientos` por `hotel_id`.
- Las tablas siguen vacias.
- No hay POST, botones de cobro, pagos, abonos, Caja ni `/api/sync`.
- 7B-C-0 ya quedo documentado como contrato de generacion manual futura desde reservacion
  elegible.

## 7B-C-0 Contrato generacion manual CxC desde reservacion

Estado formal: `CONTRATO_7B_C_0_GENERACION_MANUAL_CXC_RESERVACION_COMPLETADO`.

- Documento creado: `docs/fase_7B_C_0_contrato_generacion_manual_cxc_reservacion.md`.
- No modifica codigo, DB, rutas, modelos ni vistas.
- Define una futura accion manual desde reservacion elegible.
- La CxC futura debe ser por saldo pendiente neto elegible, no por total historico.
- No se permite generar desde huerfanos, excedentes, saldos negativos ni reservaciones de
  otro hotel.
- No hay Caja, pagos, abonos, facturacion nueva, offline ni `/api/sync`.
- Siguiente accion segura: 7B-C-A solo con autorizacion explicita de escrituras CxC
  manuales y backup previo.

## 7B-C-A Generacion manual CxC desde reservacion

Estado formal: `GENERACION_MANUAL_CXC_7B_C_A_VALIDADA_MANUALMENTE`.

- Documento creado: `docs/fase_7B_C_A_generacion_manual_cxc_reservacion.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_c_a_cxc_manual_20260618_170535.sql`.
- SHA256: `877BA8D0E9F97D8DA3047392EE2C5CC91DACEE20A94F5069BF57A2164377EE7B`.
- Ruta POST nueva:
  `/cuentas-por-cobrar/generar-desde-reservacion/{id}`.
- La accion exige sesion, contexto hotelero, modulo `reservaciones`, CSRF, transaccion y
  bloqueo `FOR UPDATE`.
- Inserta solo en `cuentas_por_cobrar`, `cuentas_por_cobrar_movimientos` y
  `logs_auditoria`.
- No modifica reservaciones, pagos, abonos, solicitudes de factura, Caja, offline ni
  `/api/sync`.
- QA manual validada por el usuario: CxC `#1` desde reservacion `#24`, hotel `4`,
  saldo `4250.00`, movimiento `CREACION #1`, auditoria `#105`, sin Caja-CxC.

## 7B-C-F Cierre generacion manual CxC

Estado formal: `BLOQUE_7B_C_GENERACION_MANUAL_CXC_CERRADO_QA_VALIDADA`.

- Documento creado: `docs/fase_7B_C_F_cierre_generacion_manual_cxc.md`.
- Cierra 7B-C-0 y 7B-C-A con QA manual validada.
- Consistencia post-QA: duplicados por reservacion `0`, CxC sin movimiento `CREACION`
  `0`, movimientos CxC huerfanos `0`, Caja-CxC textual `0`.
- La CxC `#1` queda protegida como dato operativo real.
- No se implementan cobros, abonos, Caja, facturacion nueva, anulaciones ni `/api/sync`.
- Siguiente accion segura: 7B-D-0 contrato/diagnostico de cobro futuro CxC.

## 7B-D-0 Contrato cobro CxC con Caja

Estado formal: `CONTRATO_7B_D_0_COBRO_CXC_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_7B_D_0_contrato_cobro_cxc_caja.md`.
- No agrega codigo, rutas, DB, formularios, servicios ni escrituras.
- Diagnostico: `cuentas_por_cobrar_movimientos.tipo_movimiento` no tiene tipo
  `COBRO`, por lo que no se debe improvisar cobro como `AJUSTE`.
- Define que cualquier cobro futuro requiere backup, CSRF, token de un solo uso,
  corte abierto del mismo hotel, servicio transaccional y auditoria.
- No toca Caja, cortes, saldos CxC, reservaciones, pagos, abonos, facturacion, offline ni
  `/api/sync`.
- Siguiente accion segura: 7B-D-A simulador read-only de cobro CxC.

## 7B-D-A Simulador cobro CxC con Caja

Estado formal: `SIMULADOR_COBRO_CXC_CAJA_7B_D_A_VALIDADO_MANUALMENTE`.

- Documento creado: `docs/fase_7B_D_A_simulador_cobro_cxc_caja.md`.
- Ruta GET nueva: `/cuentas-por-cobrar/simulador-caja`.
- Lee CxC operativas, corte abierto y Caja activa por `hotel_id`.
- No agrega POST, botones de cobro ni servicios de cobro.
- No modifica saldos, CxC, movimientos CxC, Caja, cortes ni `/api/sync`.
- El simulador bloquea cobro real porque falta tipo semantico `COBRO` en
  `cuentas_por_cobrar_movimientos.tipo_movimiento`.
- QA manual validada por el usuario: CxC `#1` visible, sin boton de cobro y bloqueo
  esperado por falta de tipo `COBRO`.

## 7B-D-F Cierre simulador cobro CxC

Estado formal: `BLOQUE_7B_D_SIMULADOR_COBRO_CXC_CERRADO_QA_VALIDADA`.

- Documento creado: `docs/fase_7B_D_F_cierre_simulador_cobro_cxc.md`.
- Cierra 7B-D-0 y 7B-D-A con QA manual validada.
- Confirma conteos post-QA: `cuentas_por_cobrar=1`,
  `cuentas_por_cobrar_movimientos=1`, auditoria CxC `1`, Caja-CxC textual `0`.
- No hay POST, cobros, movimientos de Caja, cambios de corte ni `/api/sync`.

## 7B-D-B-0 Contrato esquema cobro CxC

Estado formal: `CONTRATO_7B_D_B_0_ESQUEMA_COBRO_CXC_COMPLETADO`.

- Documento creado: `docs/fase_7B_D_B_0_contrato_esquema_cobro_cxc.md`.
- Define que el siguiente paso debe resolver la semantica de cobro antes de escribir
  saldos o Caja.
- Recomendacion incremental: agregar tipo `COBRO` a
  `cuentas_por_cobrar_movimientos.tipo_movimiento` antes del servicio transaccional.
- No implementa migraciones, rutas, formularios, servicios ni escrituras.
- Siguiente accion segura historica: 7B-D-B-A migracion aditiva con autorizacion
  explicita de DB y backup previo; ya ejecutada posteriormente.

## 7B-D-B-A Migracion tipo COBRO CxC

Estado formal: `MIGRACION_7B_D_B_A_COBRO_ENUM_COMPLETADA`.

- Documento creado: `docs/fase_7B_D_B_A_migracion_cobro_enum.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_d_b_a_cxc_cobro_enum_20260618_180420.sql`.
- SHA256: `906309EB73EB74B94A62FF493C1B1DAE214F82C02F253AA616C38FFEE3A6C21E`.
- Migracion:
  `migrations/20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql`.
- Se agrega `COBRO` al enum `cuentas_por_cobrar_movimientos.tipo_movimiento`.
- Registro en `migrations`: batch `22`, estado `ejecutada`.
- Movimientos CxC tipo `COBRO`: `0`.
- No hay cobros reales, cambios de saldo, movimientos de Caja, cambios de corte ni
  `/api/sync`.
- Siguiente accion segura: 7B-D-C-0 contrato de servicio transaccional de cobro CxC.

## 7B-D-C-0 Contrato servicio cobro CxC con Caja

Estado formal: `CONTRATO_7B_D_C_0_SERVICIO_COBRO_CXC_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_7B_D_C_0_contrato_servicio_cobro_cxc_caja.md`.
- Define validaciones, bloqueos, orden transaccional, semantica de movimiento CxC tipo
  `COBRO`, movimiento Caja tipo `ingreso`, auditoria y prueba rollback futura.
- Propone servicio futuro `CuentaPorCobrarCobroService`.
- Propone ruta futura:
  `POST /cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja`.
- No implementa codigo, rutas, botones, formularios, migraciones ni escrituras.
- Siguiente accion segura: 7B-D-C-A solo con autorizacion explicita de escrituras
  financieras, backup y prueba rollback.

## 7B-D-C-A Cobro CxC con Caja

Estado formal: `COBRO_CXC_CAJA_7B_D_C_A_VALIDADO_MANUALMENTE`.

- Documento creado: `docs/fase_7B_D_C_A_cobro_cxc_caja.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_d_c_a_cxc_cash_service_20260618_232434.sql`.
- SHA256: `5708BC91E1BD7A54EFA53A8BA6A92A282ACC98A2AD9237F274AAB87AB11796CD`.
- Servicio nuevo: `CuentaPorCobrarCobroService`.
- Ruta POST nueva:
  `/cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja`.
- Formulario en detalle CxC con CSRF y token de un solo uso.
- QA manual validada: CxC `#1` parcial, saldo `4249.00`, movimiento `COBRO #4`
  e ingreso Caja `#1482`.
- Prueba rollback post-QA `probar_cobro_cxc_caja.php` ejecutada sin cambios
  persistentes adicionales.
- Conteos post-QA: movimientos `COBRO=1`, Caja-CxC persistentes `1`.
- Preflight CxC: `OK: 39`, `WARNING: 5`, `ERROR: 0`.
- Siguiente accion segura: contrato de anulacion/reversion de cobro CxC.

## 7B-D-D-0 Contrato reversion cobro CxC

Estado formal: `CONTRATO_7B_D_D_0_ANULACION_REVERSION_COBRO_CXC_COMPLETADO`.

- Documento creado: `docs/fase_7B_D_D_0_contrato_reversion_cobro_cxc.md`.
- No implementa codigo, rutas, migraciones ni escrituras.
- Define reversion por movimiento inverso, no por borrado ni edicion del cobro original.
- 7B-D-D-A fue autorizada posteriormente y crea `CANCELACION` CxC, gasto Caja
  `Reversion Cobro CxC`, auditoria y prueba rollback.
- Siguiente accion segura queda cubierta por 7B-D-D-A validada; cualquier ampliacion
  requiere contrato independiente.

## 7B-D-D-A Reversion cobro CxC

Estado formal: `REVERSION_COBRO_CXC_CAJA_7B_D_D_A_VALIDADA_MANUALMENTE`.

- Documento creado: `docs/fase_7B_D_D_A_reversion_cobro_cxc.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_7b_d_d_a_cxc_reversal_20260619_091552.sql`.
- SHA256: `9B0157802EF9A959983879CF43505F6ED446613533B0E3A7C4FFCED4BFEEC0AE`.
- Servicio nuevo: `CuentaPorCobrarReversionCobroService`.
- Ruta POST nueva:
  `/cuentas-por-cobrar/operativas/{id}/movimientos/{movimientoid}/revertir-cobro-caja`.
- Panel en detalle CxC con CSRF y token de reversion de un solo uso.
- QA manual validada: reversion real del `COBRO #4` con `CANCELACION #8`,
  gasto Caja `#1486`, referencia `REV-CXC-1-MOV-4`.
- CxC `#1` queda `pendiente`, saldo `4250.00`; el cobro original y el ingreso Caja
  original se conservan como historicos.
- Prueba rollback `probar_reversion_cobro_cxc_caja.php` post-QA ejecutada sin
  cambios persistentes: crea cobro temporal, lo revierte y hace rollback.
- Conteos finales: movimientos `CANCELACION REV=1`, Caja-Reversion `1`,
  doble reversion `0`.
- Preflight CxC: `OK: 45`, `WARNING: 6`, `ERROR: 0`.
- Siguiente accion segura cubierta por 7B-D-D-F; cualquier ampliacion requiere contrato
  independiente.

## 7B-D-D-F Cierre reversion cobro CxC

Estado formal: `CIERRE_7B_D_D_F_REVERSION_COBRO_CXC_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_7B_D_D_F_cierre_reversion_cobro_cxc.md`.
- Cubre 7B-D-0, 7B-D-A, 7B-D-B-0, 7B-D-B-A, 7B-D-C-0, 7B-D-C-A,
  7B-D-D-0 y 7B-D-D-A.
- No implementa codigo, rutas, formularios, migraciones ni escrituras.
- Confirma evidencia final: CxC `#1`, cobro `#4`, Caja `#1482`,
  `CANCELACION #8`, Caja `#1486`, referencia `REV-CXC-1-MOV-4`.
- Siguiente accion segura: abrir contrato independiente para un bloque nuevo. No hacer
  cobros masivos, reversiones parciales, reversiones masivas ni automatizaciones desde
  este cierre.

## 8A-0 Contrato dashboard operativo KPIs read-only

Estado formal: `CONTRATO_8A_DASHBOARD_KPIS_READONLY_COMPLETADO`.

- Documento creado: `docs/fase_8A_0_contrato_dashboard_operativo_kpis.md`.
- 8A queda reanclada sobre `/operacion/diaria`.
- No se crea dashboard paralelo.
- No se implementa codigo, rutas, DB ni acciones.
- KPIs futuros deben ser server-side/read-only, filtrados por `hotel_id` y sin Caja.
- Siguiente accion segura: 8A-A extension pequena de KPIs read-only en
  `OperacionDiaria`.

## 8A-A KPIs read-only en tablero operativo

Estado formal: `KPIS_8A_A_DASHBOARD_READONLY_COMPLETADOS_QA_DIFERIDA`.

- Documento creado: `docs/fase_8A_A_kpis_readonly_tablero_operativo.md`.
- Se extiende `OperacionDiaria` con CxC estimada derivada.
- Se actualiza `/operacion/diaria` con tarjetas y panel de KPIs financieros estimados.
- Se actualiza `preflight_operacion_diaria.php` para validar OP-A/8A-A.
- No hay rutas nuevas, POST, cobros, pagos, abonos, Caja ni `/api/sync`.
- QA manual queda diferida.
- Siguiente accion segura: cierre tecnico 8A-F o contrato 3D antes de tocar Caja.

## 8A-F Cierre dashboard KPIs

Estado formal: `BLOQUE_8A_DASHBOARD_KPIS_CERRADO_QA_DIFERIDA`.

- Documento creado: `docs/fase_8A_F_cierre_dashboard_kpis.md`.
- Cubre 8A-0 y 8A-A.
- Confirma dashboard unico, KPIs read-only, sin POST, sin Caja y sin `/api/sync`.
- QA manual queda diferida.
- Siguiente accion segura: 3D-0 contrato/diagnostico de pagos proveedores con Caja.

## 3D-0 Contrato pagos proveedores con Caja

Estado formal: `CONTRATO_3D_PAGOS_PROVEEDORES_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_3D_0_contrato_pagos_proveedores_caja.md`.
- Diagnostico read-only de CxP/Caja.
- No se implementan rutas, POST, pagos, movimientos, migraciones ni cambios de saldo.
- Se exige servicio transaccional futuro, corte abierto del mismo hotel, CSRF, auditoria,
  backup y QA manual.
- Siguiente accion segura: detener avance operativo hasta que el usuario pueda validar
  QA/manual y autorizar 3D-A simulador read-only.

## 3D-A Simulador Caja read-only para CxP

Estado formal: `SIMULADOR_3D_A_CAJA_READONLY_VALIDADO_MANUALMENTE`.

- Documento creado: `docs/fase_3D_A_simulador_caja_readonly.md`.
- Ruta creada: GET `/cuentas-por-pagar/simulador-caja`.
- Se muestra corte abierto del hotel actual y diagnostico por CxP.
- No hay POST, botones de pago, abonos, cambios de saldo ni movimientos.
- Se agrega `preflight_pagos_proveedores_caja.php`.
- QA manual completada por el usuario.
- Siguiente accion segura: no avanzar a pago real sin backup y autorizacion especifica
  de escrituras financieras.

## 3D-B Contrato servicio transaccional pago proveedor

Estado formal: `CONTRATO_3D_B_SERVICIO_PAGO_TRANSACCIONAL_COMPLETADO`.

- Documento creado: `docs/fase_3D_B_contrato_servicio_pago_transaccional.md`.
- Define servicio futuro, validaciones, orden transaccional, auditoria y rollback.
- No implementa codigo, rutas, UI, POST, pagos, abonos ni movimientos.
- No toca CxP, Caja, cortes ni `/api/sync`.
- Revision manual del contrato completada por el usuario.
- Siguiente accion segura: detener avance operativo de 3D hasta backup verificado y
  autorizacion explicita de escrituras financieras; no pasar a 3D-C ni pagos reales en
  cola automatica.

## 3D-C Pago proveedor con Caja

Estado formal: `PAGO_PROVEEDOR_CAJA_3D_C_VALIDADO_MANUALMENTE`.

- Documento creado: `docs/fase_3D_C_pago_proveedor_caja.md`.
- Backup limpio confirmado:
  `backups/medisoft_hoteles_import_before_3d_payments_20260617_105846.sql`.
- Se agrega `CuentaPorPagarPagoService`.
- Se agrega POST `/cuentas-por-pagar/{id}/registrar-pago-caja`.
- Se agrega formulario de pago en detalle CxP solo para cuentas elegibles.
- Se agrega prueba rollback `tools/saas/probar_pago_proveedor_caja.php`.
- No se agregan pagos automaticos, abonos, cambios UI de Caja ni `/api/sync`.
- QA manual completada por el usuario: pago parcial y pago total restante sobre CxP
  `#1` del hotel `Maximiliano Leon`.
- Evidencia post-QA: pago parcial `#4` por `10.00` con Caja `#1478` y pago total
  restante `#5` por `990.00` con Caja `#1479`; CxP `#1` queda `pagada`, saldo
  `0.00`.
- Preflight post-QA `preflight_pagos_proveedores_caja.php`: `OK: 22`,
  `WARNING: 0`, `ERROR: 0`.
- Siguiente accion segura: no escalar mas pagos reales hasta definir anulacion/reversion
  formal o abrir un bloque nuevo independiente.

## 3D-D-0 Contrato reversion pago proveedor con Caja

Estado formal: `CONTRATO_3D_D_0_REVERSION_PAGO_PROVEEDOR_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_3D_D_0_contrato_reversion_pago_proveedor_caja.md`.
- No implementa codigo, rutas, formularios, migraciones ni escrituras.
- Define reversion por movimiento inverso, no por borrado ni edicion del pago original.
- La futura 3D-D-A debe crear `CANCELACION` CxP, ingreso Caja
  `Reversion Pago proveedor`, auditoria y prueba rollback.
- El enum `cuentas_por_pagar_movimientos.tipo_movimiento` ya incluye `CANCELACION`.
- 3D-D-A fue implementada y validada manualmente despues de este contrato.

## 3D-D-A Reversion pago proveedor con Caja

Estado formal: `REVERSION_PAGO_PROVEEDOR_CAJA_3D_D_A_VALIDADA_MANUALMENTE`.

- Documento creado: `docs/fase_3D_D_A_reversion_pago_proveedor_caja.md`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_3d_d_a_cxp_payment_reversal_20260619_095320.sql`.
- SHA256: `3BB71EF0C696E1AB0CB3FC4A4B2E51319DCD654F0CEAA7E83280378F9A9001FB`.
- Servicio nuevo: `CuentaPorPagarReversionPagoService`.
- Ruta POST nueva:
  `/cuentas-por-pagar/{id}/movimientos/{movimientoid}/revertir-pago-caja`.
- Panel en detalle CxP con CSRF y token de reversion de un solo uso.
- Prueba rollback `probar_reversion_pago_proveedor_caja.php` ejecutada sin cambios
  persistentes.
- QA manual validada: CxP `#1`, pago `#4`, `CANCELACION #9`, ingreso Caja
  `#1494`, referencia `REV-CXP-1-MOV-4`.
- Conteos finales: movimientos `CANCELACION REV=1`, Caja-Reversion `1`.
- Preflight pagos proveedor: `OK: 29`, `WARNING: 1`, `ERROR: 0`.
- Health general: `OK: 278`, `WARNING: 25`, `ERROR: 0`.
- Siguiente accion segura cubierta por 3D-D-F; cualquier ampliacion requiere contrato
  propio. No automatizar reversiones masivas sin fase nueva.

## 3D-D-F Cierre reversion pago proveedor con Caja

Estado formal: `CIERRE_3D_D_F_REVERSION_PAGO_PROVEEDOR_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_3D_D_F_cierre_reversion_pago_proveedor_caja.md`.
- Cubre 3D-0, 3D-A, 3D-B, 3D-C, 3D-D-0 y 3D-D-A.
- No implementa codigo, rutas, formularios, migraciones ni escrituras.
- Confirma evidencia final: CxP `#1`, pago `#4`, `CANCELACION #9`, Caja `#1494`,
  referencia `REV-CXP-1-MOV-4`.
- Siguiente accion segura: abrir contrato independiente para un bloque nuevo. No hacer
  pagos masivos, reversiones parciales, reversiones masivas ni automatizaciones desde
  este cierre.

## 9A-0 Contrato conciliacion financiera read-only

Estado formal: `CONTRATO_9A_0_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`.

- Documento creado: `docs/fase_9A_0_contrato_conciliacion_financiera_readonly.md`.
- Define conciliacion solo lectura entre CxC, CxP y Caja.
- No implementa codigo, rutas, formularios, migraciones ni escrituras.
- No corrige datos automaticamente.
- No crea pagos, cobros, reversiones, ajustes, cancelaciones ni movimientos de Caja.
- No toca reservaciones, compras, proveedores, facturacion, PWA/offline ni `/api/sync`.
- Siguiente accion segura: 9A-A como preflight read-only antes de cualquier UI.

## 9A-A Preflight conciliacion financiera read-only

Estado formal: `PREFLIGHT_9A_A_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`.

- Documento creado: `docs/fase_9A_A_preflight_conciliacion_financiera_readonly.md`.
- Herramienta creada: `src/tools/saas/preflight_conciliacion_financiera.php`.
- Ejecuta solo por CLI, con transaccion `READ ONLY` y rollback final.
- Cruza CxC, CxP, Caja, cortes, cajas, auditoria y tablas de contexto.
- Detecta movimientos sin contrapartida, doble reversion, referencias inesperadas,
  saldos fuera de rango y hotel/corte cruzado.
- No implementa rutas, UI, formularios, POST, migraciones, modelos ni escrituras.
- Resultado: `OK: 75`, `WARNING: 0`, `ERROR: 0`.
- Siguiente accion segura: abrir contrato 9A-B para pantalla GET/read-only, sin
  acciones de correccion.

## 9A-B-0 Contrato pantalla conciliacion financiera read-only

Estado formal:
`CONTRATO_9A_B_0_PANTALLA_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_9A_B_0_contrato_pantalla_conciliacion_financiera_readonly.md`.
- Define ruta candidata futura `GET /operacion/conciliacion-financiera`.
- No implementa ruta, controlador, modelo, vista, formulario ni migracion.
- La pantalla futura debe basarse en el preflight 9A-A y mantener solo filtros GET.
- Debe usar identidad del hotel con tokens `--brand-*`, no identidad Medisoft
  `--ms-*`.
- No puede incluir botones ni flujos de correccion, pago, cobro, reversion, ajuste o
  compensacion.
- Siguiente accion segura: 9A-B-A solo con implementacion GET/read-only y
  validaciones automaticas antes/despues.

## 9A-B-A Pantalla conciliacion financiera read-only

Estado formal:
`PANTALLA_9A_B_A_CONCILIACION_FINANCIERA_READONLY_VALIDADA_MANUALMENTE`.

- Documento creado:
  `docs/fase_9A_B_A_pantalla_conciliacion_financiera_readonly.md`.
- Ruta nueva: GET `/operacion/conciliacion-financiera`.
- Modelo nuevo: `ConciliacionFinanciera`, lector con transaccion `READ ONLY` y
  rollback final.
- Vista nueva: matriz de conciliacion con filtros GET, resumen CxC/CxP/Caja/Auditoria
  y etiqueta `Solo lectura`.
- Navegacion agregada desde `/operacion/diaria`.
- Preflight 9A-A ampliado para validar tambien ruta, controlador, modelo, vista,
  ausencia de POST propio y tokens `--brand-*`.
- Resultado inicial: lector directo con `hallazgos=0`, `errores=0`; preflight
  `OK: 96`, `WARNING: 0`, `ERROR: 0`.
- Health general post ajuste: `OK: 281`, `WARNING: 25`, `ERROR: 0`.
- No hay POST, migraciones, pagos, cobros, reversiones, ajustes, correcciones ni
  escrituras financieras.
- QA manual con sesion completada por el usuario.
- Siguiente accion segura cubierta por 9A-B-F; cualquier ampliacion requiere contrato
  independiente.

## 9A-B-F Cierre pantalla conciliacion financiera read-only

Estado formal:
`CIERRE_9A_B_F_PANTALLA_CONCILIACION_FINANCIERA_COMPLETADO`.

- Documento creado:
  `docs/fase_9A_B_F_cierre_pantalla_conciliacion_financiera.md`.
- Cubre 9A-0, 9A-A, 9A-B-0 y 9A-B-A.
- No implementa codigo, rutas, formularios, migraciones ni escrituras.
- Confirma ruta GET, lector read-only, filtros GET, branding `--brand-*` y ausencia
  de POST propio.
- QA manual 9A-B-A validada por el usuario.
- Siguiente accion segura: abrir contrato independiente para el siguiente bloque; no
  convertir conciliacion en correccion automatica, pagos masivos, cobros masivos ni
  reversiones masivas desde este cierre.

## 9C-0 Contrato arqueo por corte y metodo read-only

Estado formal:
`CONTRATO_9C_0_ARQUEO_METODOS_PAGO_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_9C_0_contrato_arqueo_metodos_pago_readonly.md`.
- Define el siguiente bloque financiero seguro despues de 9A-B-F.
- El alcance futuro es diagnosticar cortes y metodos de pago en modo solo lectura.
- Se apoya en fuentes existentes: `movimientos_caja`, `cortes_caja`, `cajas` y
  `categorias_movimientos`.
- Lectura local inicial: cortes `223`, cortes abiertos `3`, movimientos Caja `1409`,
  metodos distintos `3`, movimientos sin metodo `0`, movimientos sin corte `0`.
- No implementa codigo, rutas, formularios, modelos, migraciones ni escrituras.
- Prohibe abrir/cerrar/recalcular cortes, editar movimientos, cambiar metodos, ajustar
  saldos o tocar `/api/sync`.
- Siguiente accion segura: 9C-A como preflight CLI/read-only antes de cualquier
  pantalla nueva.

## 9C-A Preflight arqueo por corte y metodo read-only

Estado formal:
`PREFLIGHT_9C_A_ARQUEO_METODOS_PAGO_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_9C_A_preflight_arqueo_metodos_pago_readonly.md`.
- Herramienta creada:
  `src/tools/saas/preflight_arqueo_metodos_pago.php`.
- Ejecuta solo por CLI con `APP_ENV=local`.
- Abre transaccion `READ ONLY` y cierra con rollback.
- Lee `movimientos_caja`, `cortes_caja`, `cajas` y `categorias_movimientos`.
- Detecta integridad de corte/metodo, cortes cruzados, metodos invalidos y diferencias
  entre movimientos y totales guardados.
- Resultado local: `OK: 34`, `WARNING: 2`, `ERROR: 0`.
- Warnings esperados: `4` cortes cerrados con totales guardados distintos a movimientos
  y `1` corte con `efectivo_esperado` distinto a formula guardada.
- Health checker actualizado para reconocer 9C-A como CLI/read-only.
- Health general: `OK: 282`, `WARNING: 25`, `ERROR: 0`.
- No agrega rutas, vistas, formularios, migraciones ni escrituras.
- Siguiente accion segura: 9C-B-0 contrato de pantalla GET/read-only de arqueo por
  corte y metodo.

## 9C-B-0 Contrato pantalla arqueo por corte y metodo read-only

Estado formal:
`CONTRATO_9C_B_0_PANTALLA_ARQUEO_METODOS_PAGO_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_9C_B_0_contrato_pantalla_arqueo_metodos_pago_readonly.md`.
- Define ruta candidata futura `GET /caja/arqueo-metodos`.
- No implementa ruta, controlador, modelo, vista, formulario ni migracion.
- La pantalla futura debe basarse en `preflight_arqueo_metodos_pago.php`.
- Debe usar identidad del hotel con tokens `--brand-*`, no identidad Medisoft
  `--ms-*`.
- No puede incluir botones ni flujos de cerrar, reabrir, recalcular, corregir, ajustar,
  compensar, editar movimiento o cambiar metodo.
- Siguiente accion segura: 9C-B-A solo con implementacion GET/read-only y validaciones
  automaticas antes/despues.

## 9C-B-A Pantalla arqueo por corte y metodo read-only

Estado formal:
`PANTALLA_9C_B_A_ARQUEO_METODOS_PAGO_READONLY_COMPLETADA_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_9C_B_A_pantalla_arqueo_metodos_pago_readonly.md`.
- Ruta implementada:
  `GET /caja/arqueo-metodos`.
- Controlador/vista/modelo agregados:
  `CajaController::arqueoMetodosAction()`,
  `app/views/caja/arqueo_metodos.php`,
  `app/models/ArqueoMetodosPago.php`.
- El modelo abre transaccion `READ ONLY`, filtra por hotel actual y cierra con
  rollback.
- La vista usa filtros `GET`, etiqueta `Solo lectura`, tokens `--brand-*` y no usa
  tokens `--ms-*`.
- No hay `POST /caja/arqueo-metodos`, CSRF, `hotel_id` editable, botones de cierre,
  recalculo, ajuste, correccion ni edicion.
- Preflight 9C-A/9C-B-A extendido:
  `OK: 39`, `WARNING: 2`, `ERROR: 0`.
- Health general extendido:
  `OK: 287`, `WARNING: 25`, `ERROR: 0`.
- `/api/sync` sigue fuera de alcance y validado como bloqueado.
- QA manual con sesion completada por el usuario: la pantalla se ve correctamente.
- Siguiente accion segura cubierta por 9C-B-F; cualquier ampliacion requiere contrato
  independiente.

## 9C-B-F Cierre pantalla arqueo por corte y metodo read-only

Estado formal:
`CIERRE_9C_B_F_PANTALLA_ARQUEO_METODOS_PAGO_COMPLETADO`.

- Documento creado:
  `docs/fase_9C_B_F_cierre_pantalla_arqueo_metodos_pago.md`.
- Cierra 9C-0, 9C-A, 9C-B-0 y 9C-B-A como bloque diagnostico/read-only.
- No agrega codigo, rutas, formularios, migraciones ni escrituras.
- Consolida evidencia de ruta `GET /caja/arqueo-metodos`, lector
  `ArqueoMetodosPago`, vista `caja/arqueo_metodos.php` y QA manual validada.
- Mantiene bloqueadas correcciones automaticas, cierre/reapertura/recalculo de cortes,
  ajustes de saldo, edicion de movimientos, cambios de metodo y `/api/sync`.
- Siguiente accion segura: abrir contrato independiente para el siguiente bloque; no
  convertir arqueo en correccion automatica ni flujo operativo de Caja.

## 10A-0 Contrato tablero ejecutivo integral read-only

Estado formal:
`CONTRATO_10A_0_TABLERO_EJECUTIVO_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md`.
- Define el siguiente bloque seguro despues de conciliacion 9A y arqueo 9C.
- Ruta candidata futura: `GET /reportes/ejecutivo`.
- No implementa ruta, controlador, modelo, vista, formulario, migracion ni escritura.
- El tablero futuro debe unir KPIs de operacion hotelera, CxC, CxP, Caja, inventario,
  tareas, personal y documentos, siempre en modo read-only.
- Detecta como riesgo que `GET /reportes/gerencial-diario` existente puede marcar
  notificaciones como vistas; 10A futura no debe escribir por lectura simple.
- Debe usar identidad del hotel con tokens `--brand-*`, no identidad Medisoft `--ms-*`.
- Prohibe pagos, cobros, reversiones, cierres/reaperturas de corte, recalculos,
  ajustes, ediciones de movimientos, exports/links sin contrato y `/api/sync`.
- Accion segura ejecutada despues del contrato: 10A-A como preflight CLI/read-only
  antes de construir UI.

## 10A-A Preflight tablero ejecutivo integral read-only

Estado formal:
`PREFLIGHT_10A_A_TABLERO_EJECUTIVO_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_10A_A_preflight_tablero_ejecutivo_readonly.md`.
- Herramienta creada:
  `src/tools/saas/preflight_tablero_ejecutivo.php`.
- Health general extendido:
  `src/tools/saas/health_check_fase_1a.php`.
- No implementa ruta, controlador, modelo, vista, formulario, migracion ni escritura.
- Confirma que `POST /reportes/ejecutivo` no existe y que `GET /reportes/ejecutivo`
  sigue sin registrarse en esta fase.
- Diagnostica fuentes candidatas: operacion hotelera, CxC, CxP, Caja, cortes,
  compras, inventario, tareas, personal, documentos y auditoria.
- Resultado del preflight:
  `OK: 79`, `WARNING: 5`, `ERROR: 0`.
- Resultado del health general:
  `OK: 290`, `WARNING: 26`, `ERROR: 0`.
- Warnings conocidos: `gerencial-diario` archiva notificaciones, `ledger_laboral`
  no existe, `huespedes` no tiene `hotel_id` directo y `logs_auditoria` conserva
  historico sin hotel.
- Siguiente accion segura: 10A-B-0 como contrato de pantalla ejecutiva GET/read-only
  antes de tocar rutas, controlador, modelo o vista.

## 10A-B-0 Contrato pantalla tablero ejecutivo read-only

Estado formal:
`CONTRATO_10A_B_0_PANTALLA_TABLERO_EJECUTIVO_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_10A_B_0_contrato_pantalla_tablero_ejecutivo_readonly.md`.
- No implementa ruta, controlador, modelo, vista, navegacion, formulario, migracion ni
  escritura.
- Reserva como ruta futura unica: `GET /reportes/ejecutivo`.
- Prohibe `POST /reportes/ejecutivo`, endpoints de escritura, exports o links
  publicos sin contrato separado.
- Define que una futura 10A-B-A podria tocar, con autorizacion explicita:
  `routes.php`, `ReportesController.php`, un lector read-only dedicado, la vista
  `reportes/ejecutivo.php`, preflight y health.
- Reglas visuales futuras: tokens `--brand-*`, cero `--ms-*`, etiqueta `Solo lectura`,
  filtros GET, sin botones operativos.
- Reglas backend futuras: contexto de hotel actual, lector dedicado, joins seguros para
  `huespedes`, degradacion de `ledger_laboral`, warning por auditoria historica sin
  hotel y cero archivado de notificaciones al abrir.
- Mantiene bloqueados pagos, cobros, reversiones, cierres/reaperturas de corte,
  recalculos, ajustes, recepcion de compra, inventario, tareas, documentos, permisos,
  PWA/offline y `/api/sync`.
- Siguiente accion posible: 10A-B-A solo con autorizacion explicita para implementar
  pantalla GET/read-only.

## 10A-B-A Pantalla tablero ejecutivo read-only

Estado formal:
`PANTALLA_10A_B_A_TABLERO_EJECUTIVO_READONLY_IMPLEMENTADA`.

- Documento creado:
  `docs/fase_10A_B_A_pantalla_tablero_ejecutivo_readonly.md`.
- Ruta agregada:
  `GET /reportes/ejecutivo`.
- Controlador/modelo/vista:
  `ReportesController::ejecutivoAction`,
  `app/models/TableroEjecutivo.php`,
  `app/views/reportes/ejecutivo.php`.
- Enlace agregado en `app/views/reportes/index.php`.
- Preflight y health extendidos:
  `tools/saas/preflight_tablero_ejecutivo.php`,
  `tools/saas/health_check_fase_1a.php`.
- No existe `POST /reportes/ejecutivo`.
- Vista con filtros GET, etiqueta `Solo lectura`, tokens `--brand-*`, sin `--ms-*`,
  sin CSRF y sin `hotel_id` editable.
- Lector con `START TRANSACTION READ ONLY`, `rollBack`, hotel actual y degradacion de
  fuentes opcionales.
- Validaciones:
  - PHP lint en archivos tocados: OK.
  - Preflight: `OK: 86`, `WARNING: 5`, `ERROR: 0`.
  - Health general: `OK: 294`, `WARNING: 26`, `ERROR: 0`.
  - HTTP sin sesion: `303` hacia `/login`.
- QA manual con sesion validada por el usuario.

## 10A-B-F Cierre tablero ejecutivo read-only

Estado formal:
`CIERRE_10A_B_TABLERO_EJECUTIVO_READONLY_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_10A_B_F_cierre_tablero_ejecutivo.md`.
- Cierra 10A-0, 10A-A, 10A-B-0 y 10A-B-A como bloque de tablero ejecutivo
  GET/read-only.
- Confirma que `GET /reportes/ejecutivo` paso QA manual con sesion.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, migraciones
  ni datos.
- Mantiene prohibidos POST, pagos, cobros, reversiones, cierres/reaperturas de corte,
  recalculos, ajustes, recepcion de compra, inventario, tareas, documentos, permisos,
  PWA/offline y `/api/sync`.
- Siguiente bloque recomendado: contrato independiente para separar lectura de
  `/reportes/gerencial-diario` y archivado automatico de notificaciones.

## 10B-0 Contrato reporte gerencial y notificaciones

Estado formal:
`CONTRATO_10B_0_REPORTE_GERENCIAL_NOTIFICACIONES_COMPLETADO`.

- Documento creado:
  `docs/fase_10B_0_contrato_reporte_gerencial_notificaciones.md`.
- No implementa codigo, rutas, controladores, modelos, vistas, formularios,
  migraciones, datos ni escrituras.
- Define el contrato para separar lectura directa de
  `GET /reportes/gerencial-diario` y `GET /reportes/gerencial-diario/pdf` del
  archivado automatico de notificaciones.
- Documenta el comportamiento actual: ambas acciones llaman
  `archivarNotificacionReporteGerencialVisto`, que ejecuta `UPDATE notificaciones`.
- Una futura 10B-A podria retirar esas llamadas del reporte, conservando el archivado
  solo en el flujo controlado de notificaciones, con autorizacion explicita.
- Prohibe cambiar calculos del reporte gerencial, Caja, cortes, pagos, cobros, CxC,
  CxP, inventario, tareas, documentos, permisos/auth, PWA/offline y `/api/sync`.
- Accion ejecutada despues: 10B-A con autorizacion explicita para tocar controlador y
  validaciones CLI/read-only.

## 10B-A Separacion reporte gerencial y notificaciones

Estado formal:
`SEPARACION_10B_A_REPORTE_GERENCIAL_NOTIFICACIONES_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_10B_A_separacion_reporte_gerencial_notificaciones.md`.
- Se modifico `src/app/controllers/ReportesController.php` para retirar el archivado
  automatico desde `gerencialDiarioAction` y `gerencialDiarioPdfAction`.
- Se retiro el metodo privado `archivarNotificacionReporteGerencialVisto`.
- Se extendieron:
  `src/tools/saas/preflight_tablero_ejecutivo.php` y
  `src/tools/saas/health_check_fase_1a.php`.
- No se tocaron rutas, modelos de negocio, vistas, formularios, migraciones, datos,
  permisos/auth, PWA/offline ni `/api/sync`.
- El archivado de notificaciones de reporte queda en el flujo controlado de
  `/notificaciones/{id}/abrir`.
- Validaciones:
  - PHP lint en archivos tocados: OK.
  - Preflight tablero ejecutivo: `OK: 91`, `WARNING: 3`, `ERROR: 0`.
  - Health general: `OK: 298`, `WARNING: 25`, `ERROR: 0`.
  - HTTP sin sesion en HTML/PDF gerencial: `303`.
- QA manual validada por el usuario: abrir HTML/PDF directo no cambia la notificacion
  y abrir desde el centro de notificaciones conserva el archivado esperado.

## 10B-F Cierre reporte gerencial y notificaciones

Estado formal:
`CIERRE_10B_REPORTE_GERENCIAL_NOTIFICACIONES_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_10B_F_cierre_reporte_gerencial_notificaciones.md`.
- Cierra 10B-0 y 10B-A como bloque de separacion entre lectura directa de reporte
  gerencial y archivado controlado de notificaciones.
- Confirma QA manual aprobada por el usuario.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, migraciones
  ni datos.
- Mantiene prohibidos cambios de calculos del reporte, Caja, cortes, pagos, cobros,
  CxC, CxP, inventario, tareas, documentos, permisos/auth, PWA/offline y `/api/sync`.
- Siguiente accion segura: contrato independiente para el siguiente frente de mejora.

## 11A-0 Contrato perfil operativo de huesped read-only

Estado formal:
`CONTRATO_11A_0_PERFIL_HUESPED_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_11A_0_contrato_perfil_huesped_readonly.md`.
- No implementa codigo, rutas, controladores, modelos, vistas, formularios,
  migraciones, datos ni escrituras.
- Define el siguiente frente seguro de Clientes/Huespedes: ficha operativa read-only
  con reservaciones, vehiculos, CxC, documentos, recurrencia y alertas visuales.
- Superficie futura preferida: `GET /huespedes/{id}` existente, sin ruta nueva salvo
  contrato separado.
- Mantiene bloqueado cualquier cambio de formularios existentes: no cambiar `action`,
  `method`, `name`, CSRF, hidden inputs ni ubicacion de submits.
- Prohibe crear CxC, cobros, pagos, check-in/check-out, cambios de reservaciones,
  subida/borrado documental, tareas, permisos/auth, PWA/offline y `/api/sync`.
- Siguiente accion posible: 11A-A solo con autorizacion explicita para bloque
  read-only pequeno en ficha de huesped y validacion CLI/read-only.

## 11A-A Perfil operativo de huesped read-only

Estado formal:
`PERFIL_11A_A_HUESPED_READONLY_VALIDADO_MANUALMENTE`.

- Documento creado:
  `docs/fase_11A_A_perfil_huesped_readonly.md`.
- Se conserva la ruta existente `GET /huespedes/{id}`.
- Se agrega `Huesped::perfilOperativoReadOnlyPorHotel()` como lector calculado al
  vuelo.
- `HuespedController::verAction` pasa `perfilOperativo` a la ficha.
- `huespedes/ver.php` agrega bloque `Perfil operativo` con etiqueta `Solo lectura`.
- El bloque nuevo no contiene formularios, botones operativos, enlaces ni CSRF.
- No se agregan rutas, POST, migraciones, datos ni permisos.
- Health general queda extendido para validar el contrato 11A-A.
- Validaciones automaticas:
  - PHP lint en archivos tocados: OK.
  - Health general: `OK: 304`, `WARNING: 25`, `ERROR: 0`.
  - HTTP sin sesion en `/huespedes/1`: `303` hacia `/login`.
  - `git diff --check` scoped: OK.
- Mantiene prohibidos CxC nueva, cobros, pagos, check-in/check-out, cambios de
  reservaciones, documentos, tareas, permisos/auth, PWA/offline y `/api/sync`.
- QA manual validada por el usuario: el bloque se muestra, `Solo lectura` esta visible
  y no hay acciones/formularios dentro del bloque nuevo.

## 11A-F Cierre perfil operativo de huesped read-only

Estado formal:
`CIERRE_11A_PERFIL_HUESPED_READONLY_QA_MANUAL_VALIDADA`.

- Documento creado:
  `docs/fase_11A_F_cierre_perfil_huesped_readonly.md`.
- Cierra 11A-0 y 11A-A como bloque de perfil operativo read-only.
- No agrega codigo, rutas, controladores, modelos, vistas, formularios, migraciones
  ni datos.
- Confirma QA manual aprobada por el usuario.
- Mantiene prohibidos CxC nueva, cobros, pagos, check-in/check-out, cambios de
  reservaciones, documentos, tareas, permisos/auth, PWA/offline y `/api/sync`.

## 5E-0 Contrato pagos laborales con Caja

Estado formal:
`CONTRATO_5E_0_PAGOS_LABORALES_CAJA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_0_contrato_pagos_laborales_caja.md`.
- No implementa codigo, rutas, controladores, modelos, vistas, formularios,
  migraciones, datos ni escrituras.
- Reancla el bloque solicitado de nomina/pagos laborales con Caja.
- Confirma que `trabajador_pagos` sigue siendo tabla de conceptos laborales, no pagos
  reales.
- Define que una futura implementacion debe usar una tabla independiente, por ejemplo
  `trabajador_pagos_caja`.
- Define contrato futuro de Caja: egreso con corte abierto, referencia unica,
  transaccion, auditoria y enlace al pago laboral real.
- Prohibe en esta subfase pagos reales, movimientos de Caja, categorias de Caja,
  abonos/liquidaciones de anticipos/prestamos, nomina automatica, reversiones,
  permisos nuevos, PWA/offline y `/api/sync`.
- Siguiente accion segura: 5E-A preflight CLI/read-only de pagos laborales con Caja.

## 5E-A Preflight pagos laborales con Caja

Estado formal:
`PREFLIGHT_5E_A_PAGOS_LABORALES_CAJA_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_A_preflight_pagos_laborales_caja.md`.
- Herramienta nueva:
  `src/tools/saas/preflight_personal_pagos_caja.php`.
- Health actualizado:
  `src/tools/saas/health_check_fase_1a.php`.
- No agrega rutas, controladores, modelos operativos, vistas, formularios,
  migraciones, datos ni escrituras.
- Valida que `trabajador_pagos` siga siendo conceptos laborales y que
  `trabajador_pagos_caja` aun no exista antes de migracion.
- Valida ausencia de rutas/servicio prematuros de pago laboral con Caja.
- Valida ausencia de movimientos/categorias de Caja con nomina o pago laboral.
- Resultado automatico: preflight 5E-A `OK: 34`, `WARNING: 1`, `ERROR: 0`;
  health general `OK: 305`, `WARNING: 25`, `ERROR: 0`.
- Advertencia vigente: no hay trabajadores activos para QA futura de pago real.
- Siguiente accion segura: 5E-B-0 contrato de migracion aditiva para
  `trabajador_pagos_caja`, sin ejecutar migracion todavia.

## 5E-B-0 Contrato migracion pagos laborales con Caja

Estado formal:
`CONTRATO_5E_B_0_MIGRACION_PAGOS_LABORALES_CAJA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_B_0_contrato_migracion_pagos_laborales_caja.md`.
- No crea archivo SQL, no ejecuta migracion, no crea tabla, no modifica DB y no toca
  PHP operativo.
- Define la tabla futura `trabajador_pagos_caja` como fuente independiente de pagos
  laborales reales.
- Reafirma que `trabajador_pagos` sigue siendo conceptos laborales y no debe alterarse
  para pagos reales.
- Define columnas, indices, llaves, checks, contrato de referencia y rollback futuro.
- Mantiene prohibidos pagos reales, movimientos de Caja, categorias de Caja,
  abonos/liquidaciones, nomina automatica, reversiones, permisos nuevos,
  PWA/offline y `/api/sync`.
- Siguiente accion segura: 5E-B-A migracion aditiva solo con autorizacion explicita
  para tocar DB y backup previo.

## 5E-B-A Migracion pagos laborales con Caja

Estado formal:
`MIGRACION_5E_B_A_PAGOS_LABORALES_CAJA_COMPLETADA`.

- Documento creado:
  `docs/fase_5E_B_A_migracion_pagos_laborales_caja.md`.
- Migracion aplicada:
  `migrations/20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql`.
- Backup previo:
  `backups/medisoft_hoteles_import_before_5e_b_a_trabajador_pagos_caja_20260619_220444.sql`.
- SHA256:
  `2E279999DA95C0216942F1FE480E5E43E96AAE42A06DA7C9FD83633BC53898BC`.
- Tamano: `1685511` bytes.
- Se creo `trabajador_pagos_caja` vacia con 18 columnas y contrato de indices/llaves.
- La migracion quedo registrada en `migrations` como `ejecutada`.
- Herramientas actualizadas:
  `src/tools/saas/preflight_personal_pagos_caja.php` y
  `src/tools/saas/health_check_fase_1a.php`.
- Resultado automatico: preflight 5E `OK: 37`, `WARNING: 0`, `ERROR: 0`;
  health general `OK: 307`, `WARNING: 26`, `ERROR: 0`.
- No se crearon pagos reales, movimientos de Caja, categorias de Caja, rutas, vistas,
  formularios, servicios, permisos nuevos ni cambios en `/api/sync`.
- Siguiente accion segura: 5E-C-0 contrato de simulador GET/read-only para pago
  laboral contra Caja.

## 5E-C-0 Contrato simulador pago laboral con Caja

Estado formal:
`CONTRATO_5E_C_0_SIMULADOR_PAGO_LABORAL_CAJA_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_C_0_contrato_simulador_pago_laboral_caja.md`.
- No agrega rutas, controladores, modelos, vistas, formularios, servicios, POST,
  pagos reales, movimientos de Caja, categorias de Caja, migraciones ni datos.
- Define la futura ruta candidata:
  `GET /trabajadores/pagos-caja/simulador`.
- Define que el simulador debe leer Personal, `trabajador_pagos_caja`, corte abierto
  y Caja solo en modo read-only.
- Define motivos de elegibilidad y bloqueo para pago laboral futuro.
- Mantiene prohibidos pagos reales, tokens, servicio transaccional,
  abonos/liquidaciones, nomina automatica, reversiones, permisos nuevos,
  PWA/offline y `/api/sync`.
- Siguiente accion segura: 5E-C-A simulador GET/read-only solo con autorizacion para
  tocar rutas, controlador/modelo read-only, vista y checkers.

## 5E-C-A Simulador pago laboral con Caja

Estado formal:
`SIMULADOR_5E_C_A_PAGO_LABORAL_CAJA_READONLY_COMPLETADO`.

- Documento creado:
  `docs/fase_5E_C_A_simulador_pago_laboral_caja.md`.
- Ruta implementada:
  `GET /trabajadores/pagos-caja/simulador`.
- Archivos tocados: rutas, `TrabajadorController`, `Trabajador`, vista nueva,
  enlaces GET en listado/ficha, preflight, health y documentacion.
- No se implementa pago real, POST, token, servicio transaccional, reversion,
  movimiento de Caja ni migracion.
- Resultado automatico: preflight 5E `OK: 41`, `WARNING: 0`, `ERROR: 0`;
  health general `OK: 309`, `WARNING: 26`, `ERROR: 0`.
- Siguiente accion segura: 5E-D-0 contrato del servicio real, antes de cualquier
  escritura.

## 5E-C-F Cierre simulador pago laboral con Caja

Estado formal:
`CIERRE_5E_C_SIMULADOR_PAGO_LABORAL_CAJA_QA_MANUAL_VALIDADA`.

- Documento creado: `docs/fase_5E_C_F_cierre_simulador_pago_laboral_caja.md`.
- QA manual validada por el usuario.
- No agrega codigo ni escrituras.
- Cierra el simulador como diagnostico GET/read-only.

## 5E-D-0 Contrato servicio pago laboral con Caja

Estado formal:
`CONTRATO_5E_D_0_SERVICIO_PAGO_LABORAL_CAJA_COMPLETADO`.

- Documento creado: `docs/fase_5E_D_0_contrato_servicio_pago_laboral_caja.md`.
- No implementa servicio, ruta POST, formulario, token real, prueba rollback real ni escrituras.
- Define `TrabajadorPagoCajaService`, saldo disponible, orden transaccional, `trabajador_pagos_caja`, egreso Caja, auditoria y rollback futuro.
- Siguiente accion segura: 5E-D-A solo con backup, autorizacion explicita y QA.

## 5E-D-A Pago laboral con Caja controlado

Estado formal:
`SERVICIO_5E_D_A_PAGO_LABORAL_CAJA_CONTROLADO_COMPLETADO`.

- Documento creado: `docs/fase_5E_D_A_pago_laboral_caja_controlado.md`.
- Implementa `TrabajadorPagoCajaService` con transaccion, `FOR UPDATE`, referencia unica, auditoria y rollback externo.
- Agrega `POST /trabajadores/{id}/registrar-pago-caja`.
- La ficha de trabajador muestra panel `Pago laboral con Caja` solo si el servicio lo evalua como elegible.
- `trabajador_pagos` sigue como conceptos; `trabajador_pagos_caja` pasa a ser la fuente del pago real.
- `movimientos_caja` recibe egreso categoria `Pago laboral` solo desde el servicio.
- Resultado automatico: preflight 5E `OK: 41`, `WARNING: 0`, `ERROR: 0`; rollback `OK`; health `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Siguiente accion segura: prueba manual del usuario y cierre 5E-D-F.

## 5E-D-F Cierre pago laboral con Caja

Estado formal:
`CIERRE_5E_D_F_PAGO_LABORAL_CAJA_QA_MANUAL_VALIDADA`.

- Documento creado: `docs/fase_5E_D_F_cierre_pago_laboral_caja.md`.
- QA manual validada por el usuario.
- La ficha separa bruto laboral, pagos Caja aplicados y saldo disponible para pago.
- El pago completo deja disponible `0.00` y bloquea nuevo pago por saldo no positivo.
- No agrega codigo ni escrituras.
- Cierra 5E-D como pago laboral individual con Caja controlado.
- Siguiente accion segura: contrato independiente para reversion o historial
  detallado read-only de pagos laborales con Caja.
