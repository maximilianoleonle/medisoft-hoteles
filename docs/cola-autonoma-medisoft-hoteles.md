# Cola autonoma - Medisoft Hoteles

## Ultimo mensaje real usado

NUEVO_BLOQUE_AUTORIZADO_FASE_4A_CENTRO_DOCUMENTAL: iniciar Centro Documental Base.

Mensaje actual procesado: "Continua"; se implementa Fase 4B-A descarga segura autenticada de documentos.

## Estado vigente

- Bloque actual: Fase 4B Descarga segura de documentos.
- Fase actual: 4B-A descarga segura autenticada.
- Riesgo: naranja.
- Estado: `DESCARGA_SEGURA_4B_COMPLETADA_QA_MANUAL_PENDIENTE`.
- HEAD base antes del reanclaje: `2662998 docs(phase-3c): record payable generation security audit`.
- Estado Git al iniciar reanclaje: limpio.
- Base local principal: `medisoft_hoteles_import`.
- No avanzar a pagos, Caja, Fase 3D ni nuevas funcionalidades.
- Nota: existen commits de 3C-A/B/C y revisiones posteriores, pero el nuevo reanclaje no los considera cierre formal.
- Verificacion actual: Docker disponible; `php -l`, health, preflights, HTTP sin sesion, prueba de upload controlada, rechazo de extension invalida, conteos DB antes/despues y `git diff --check` ejecutados. QA manual post-hotfix reportada por el usuario como funcional.

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
- 4B-A descarga segura autenticada: implementada tecnicamente.
- Documento: `docs/fase_4B_0_contrato_descarga_segura_documentos.md`.
- Ruta implementada: `GET /documentos/{id}/descargar`.
- Controlador: `DocumentoController::descargarAction()`.
- Reglas clave: `requireAuth`, contexto hotelero, validacion `id + hotel_id`, documento `activo`, `realpath` bajo `STORAGE_PATH/documentos`, headers privados y sin rutas publicas.
- No hay POST nuevo, links publicos, edicion, borrado, migraciones ni escrituras DB.
- Verificacion HTTP: sin sesion `303` a login; documento `#1` de Los Cedros descarga `200`; documento `#3` de otro hotel redirige `303` a `/documentos`.
- Prohibido: links publicos, edicion, borrado, Caja, pagos, abonos, PWA/offline, Fase 3D y `/api/sync`.
- Siguiente accion: QA manual de descarga en navegador.

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
- No existe ninguna tabla `trabajador*`: el bloque es 100% aditivo.
- Patrones reutilizables: `AuditService::record()` (auditoria), migracion aditiva idempotente (modelo `20260615_003_fase_3b_cxp_base.sql`), modelos con filtro `hotel_id`.
- Confirmado: NO hace falta tocar Caja, ni `usuarios` destructivamente, ni `/api/sync`.
- Diseno de 6 tablas (`trabajadores`, `trabajador_pagos`, `trabajador_anticipos`, `trabajador_prestamos`, `trabajador_asistencias`, `trabajador_documentos`) documentado en `docs/fase_NP_0_contrato_diagnostico.md`.

## Siguiente accion

Siguiente paso formal recomendado: QA manual de Fase 4B-A antes de avanzar a edicion, borrado, links publicos, auditoria de descargas o fases posteriores. No avanzar a descargas publicas, pagos, Caja, Fase 3D, NP-A ni salida real de dinero. No alterar `usuarios` de forma destructiva. No tocar `/api/sync`. No hacer push.
