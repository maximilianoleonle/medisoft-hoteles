# Cierre tecnico del bloque autorizado

## Estado

Estado objetivo: `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`.

Este cierre fue seguido por QA manual reportada como realizada por el usuario y por commit separado del ajuste visual de reservaciones.

## Fases cerradas

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 2X | Detalle read-only de compra recibida | `d1f1431` | Cerrada |
| 2Y | Reporte read-only de compras recibidas | `32abb7b` | Cerrada |
| 2Z | Endurecimiento de recepcion de compras | `052fd7a` | Cerrada |
| 3A | Ficha read-only de proveedor | `3d8f997` | Cerrada |
| 3B draft | Borrador tecnico CxP base | `673f47f` | Cerrada como draft |
| 3B aplicada | CxP base read-only | `1fa1653` | Cerrada |
| Post 3B | Auditoria/cierre documental | `2535dd1`, `ca2bda4`, `8a49995` | Cerrada |
| Reservaciones visual | Ajuste modal check-in tardio | `dc3c150` | Cerrada como cambio separado |

## Confirmaciones tecnicas

- Rutas de compras/proveedores/CxP revisadas.
- Controladores y modelos revisados para guards y `hotel_id`.
- Vistas revisadas para estados vacios y ausencia de acciones financieras no autorizadas.
- Sidebar revisado: Proveedores, Compras y CxP bajo gate visual/funcional de Inventario.
- CxP conserva solo rutas GET.
- CxP no tiene POST operativo.
- CxP no expone botones de pago.
- CxP no toca Caja.
- Compras no genera CxP automaticamente.
- Recepcion de compras mantiene proteccion contra doble recepcion.
- Inventario moderno usa `inventario_productos` y `movimientos_inventario`.
- Tablas legacy quedan congeladas y documentadas.
- `/api/sync` sigue fuera de alcance y bloqueado.

## Verificaciones automaticas registradas

- `php -l` sobre archivos PHP del bloque: sin errores.
- `src/tools/saas/health_check_fase_1a.php`: PASS con warnings permitidos.
- `src/tools/saas/preflight_compras_minimas.php`: PASS con warnings permitidos.
- `src/tools/saas/preflight_recepcion_compras.php`: PASS con warnings permitidos.
- SQL read-only:
  - CxP registrada en `migrations`;
  - `cuentas_por_pagar` vacia;
  - `cuentas_por_pagar_movimientos` vacia;
  - sin movimientos de Caja vinculados a CxP;
  - sin detalles recibidos sin movimiento de inventario.
- HTTP sin sesion:
  - `/compras/reportes/recibidas` redirige a login;
  - `/proveedores/1` redirige a login;
  - `/cuentas-por-pagar` redirige a login.

## Warnings conocidos

- El contenedor `app` no monta `docs/technical` ni `migrations/` completos, por eso algunos checkers emiten warnings de visibilidad documental.
- Existe migracion historica fuera de `migrations/`: `database/migrations/2026_05_21_create_operaciones_sync.sql`.
- Maximiliano tiene excepciones de modulos respecto a su plan: `configuracion`, `usuarios`.
- Tablas legacy/duplicadas siguen presentes y no deben borrarse:
  - `productos` vs `inventario_productos`;
  - `inventario_movimientos` vs `movimientos_inventario`;
  - `inventario_habitacion_config` vs `inventario_config_habitacion`;
  - `push_subscriptions` vs `pwa_push_subscriptions`;
  - `huespedes_vehiculos` vs `huesped_vehiculos`.
- `src/app/views/reservaciones/ver.php` fue resuelto en commit separado `dc3c150`.

## QA manual

- QA manual del bloque 2X-3B fue reportada como realizada por el usuario.
- QA de Fase 3C vive en `docs/qa-pendiente-cola.md`.

## Estado Git esperado

- HEAD funcional/documental del bloque anterior antes de 3C: `dc3c150`.
- Pendientes no relacionados al iniciar 3C-0: ninguno.

## Cierre tecnico Fase 3C

Estado final: `FASE_3C_VALIDADA_MANUALMENTE`.

La Fase 3C queda cerrada tecnicamente como generacion manual controlada de CxP desde compras recibidas, sin pagos, sin abonos, sin Caja, sin movimientos de Caja y sin cambios en `/api/sync`.

### Fases cerradas 3C

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 3C-0 | Contrato y diagnostico | `9897465` | Cerrada |
| Reanclaje 3C | Estado real corregido | `41e6c18` | Cerrada |
| 3C-A | Simulador read-only de CxP generable | `c0ff5a1` | Cerrada y validada manualmente |
| 3C-B | Generacion manual sin Caja | `c5e9de8` | Cerrada y validada manualmente |
| QA 3C-A/B | Validacion manual documentada | `b9ca075` | Cerrada |
| 3C-C | Validaciones, health y preflights | `e91cab5` | Cerrada tecnicamente |
| Revision tecnica 3C | Estabilizacion post-3C-C | `59feafc` | Cerrada |
| Auditoria seguridad 3C | Auditoria post-3C-C | `c14d14d` | Cerrada |
| QA manual final 3C | Validacion final del usuario | reporte manual | Completada |

### Confirmaciones 3C

- 3C-0 contrato y diagnostico documentado.
- 3C-A implementado como GET read-only, ruteado, protegido, navegable y validado manualmente.
- 3C-B implementado como POST manual con CSRF, transaccion, validaciones centrales, auditoria y validacion manual.
- 3C-C implementado como health/preflights read-only de consistencia CxP.
- Revision tecnica 3C completada sin hallazgos bloqueantes.
- Auditoria seguridad 3C completada sin hallazgos bloqueantes.
- Rollback por subfase documentado.
- Fuentes de verdad actualizadas.
- QA critica, funcional, visual y regresion actualizada.
- Warnings conocidos listados.
- Commits registrados.
- Sin pagos.
- Sin abonos.
- Sin Caja.
- Sin movimientos de Caja.
- Sin cambios en `/api/sync`.
- Sin Fase 3D.
- QA manual final reportada como OK por el usuario.

### Warnings y pendientes separados

- La QA manual final del bloque 3C fue reportada como OK por el usuario. No autoriza pagos, abonos, Caja ni Fase 3D.
- Cambios no relacionados ya separados en commit `e52766e`:
  - `src/app/controllers/DashboardController.php`;
  - `src/app/controllers/HabitacionController.php`;
  - `src/app/controllers/NotificacionController.php`;
  - `src/app/views/layout/sidebar.php`;
  - `src/app/views/notificaciones/index.php`.
- Cambios no relacionados pendientes en Git al cierre:
  - `src/app/services/PwaPushService.php`;
  - `src/public_html/service-worker.js`.
- `src/public_html/service-worker.js` esta en zona protegida por `AGENTS.md`; requiere triage separado antes de cualquier commit o cambio adicional.

### Estado Git al cierre 3C

- El commit de cierre 3C debe incluir solo documentacion del bloque 3C.
- No incluir cambios PWA/no relacionados en el commit de cierre.
- No hacer push.

## Cierre tecnico Fase 4A

Estado final: `CIERRE_TECNICO_4A_COMPLETADO`.

La Fase 4A queda cerrada tecnicamente como Centro Documental Base: contrato,
migracion base, consultas read-only y carga segura hacia storage privado. No habilita
descarga, edicion, borrado, pagos, abonos, Caja, Fase 3D ni `/api/sync`.

### Fases cerradas 4A

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 4A-0 | Contrato y diagnostico | `af126f7` | Cerrada |
| 4A-A | Migracion base documental | `6eae89b` | Cerrada |
| 4A-B | Capa read-only documental | `dc32c3a` | Cerrada |
| 4A-C | Upload seguro documental | `4668571` | Cerrada |
| Hotfix 4A-C | Tipos documentales y carga general | `a5b15f1` | Cerrada y probada manualmente |
| Revision tecnica 4A | Guard contextual por entidad/hotel | `c435ddb` | Cerrada |
| Auditoria seguridad 4A | Auditoria post-QA | `d34886f` | Cerrada |

### Confirmaciones 4A

- Tablas base creadas: `documento_tipos`, `documentos`, `documento_entidades`.
- Tipos documentales globales disponibles: Contrato, Comprobante, Identificacion,
  Factura, Evidencia y Otro.
- Rutas activas: `GET /documentos`, `GET /documentos/subir`,
  `POST /documentos/subir`, `GET /documentos/entidad/{tipo}/{id}` y
  `GET /documentos/{id}`.
- Upload con `multipart/form-data`, CSRF, validacion MIME/extension/tamano y storage
  privado bajo `STORAGE_PATH/documentos`.
- Ruta contextual valida entidad existente del hotel actual antes de mostrar listado o
  enlace de carga.
- Vistas no muestran `storage_path` ni `nombre_archivo`.
- Sin rutas documentales de descarga, edicion ni borrado.
- Sin Caja, pagos, abonos, Fase 3D ni cambios en `/api/sync`.
- QA manual post-hotfix reportada como funcional por el usuario.

### Verificaciones 4A registradas

- `php -l` en `DocumentoController.php`: sin errores.
- `health_check_fase_1a.php`: PASS con warnings permitidos.
- `preflight_compras_minimas.php`: PASS con warnings permitidos.
- `preflight_recepcion_compras.php`: PASS con warnings permitidos.
- HTTP sin sesion en `GET /documentos/subir` y `POST /documentos/subir`: redirige a
  login.
- HTTP autenticado: `/documentos` y `/documentos/entidad/proveedor/8` responden `200`;
  entidad invalida redirige `303` a `/documentos`.
- SQL read-only de cierre: `documento_tipos=6`, `documentos=3`,
  `documento_entidades=1`, `cuentas_por_pagar_movimientos=0`, `movimientos_caja=1403`,
  `logs_auditoria=29`.

### Warnings y pendientes 4A

- `documentos.id=1`, `documentos.id=2` y `documentos.id=3` existen por pruebas locales
  y/o QA manual; no borrar ni reconciliar sin autorizacion explicita.
- `src/storage/documentos/` es runtime privado e ignorado por Git.
- La descarga segura aun no existe; debe abrirse como fase nueva con contrato, guardias,
  `realpath`, headers privados y pruebas manuales.
- No hacer push.

## Cierre tecnico Fase 4C

Estado final: `CIERRE_TECNICO_4C_COMPLETADO_QA_MANUAL_VALIDADA`.

La Fase 4C queda cerrada tecnicamente como integracion contextual de documentos en
fichas operativas. Reutiliza la infraestructura documental existente y no habilita
borrado, reemplazo de archivo, links publicos, pagos, abonos, Caja, Fase 3D, NP-A ni
`/api/sync`.

### Fases cerradas 4C

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 4C-0 | Contrato y diagnostico de documentos por entidad | `87a611f` | Cerrada |
| 4C-A | Secciones documentales contextuales por entidad | `ebd764d` | Cerrada |
| Hotfix 4C-A | Accion `Vincular documento` hacia carga contextual existente | `64f81d2` | Cerrada y probada manualmente |
| QA manual 4C-A | Validacion reportada por el usuario | `4542d83` | Documentada |

### Confirmaciones 4C

- Entidades cubiertas: proveedor, compra, cuenta por pagar, huesped y reservacion.
- Partial reutilizable: `src/app/views/partials/documentos_entidad.php`.
- Las fichas muestran metadata segura y estado vacio claro.
- La consulta documental usa `Documento::documentosPorEntidad()` con filtro `hotel_id`.
- La accion `Vincular documento` abre `GET /documentos/subir?entidad_tipo=...&entidad_id=...`
  y queda bajo los guards existentes del flujo de carga documental.
- No se muestran `storage_path`, `nombre_archivo`, rutas absolutas ni links publicos.
- No hay formularios nuevos, POST nuevos, edicion de metadata desde fichas, borrado ni
  reemplazo de archivo en 4C-A.
- Sin Caja, pagos, abonos, Fase 3D, NP-A ni cambios en `/api/sync`.
- QA manual post-hotfix reportada como correcta por el usuario.

### Verificaciones 4C registradas

- `php -l` en partial, controladores/vistas tocadas y health checker: sin errores en la
  verificacion previa de implementacion.
- `health_check_fase_1a.php`: PASS con warnings permitidos en la verificacion previa.
- HTTP sin sesion en ruta de carga contextual: redirige a login segun patron existente.
- SQL read-only previo: sin escrituras nuevas por la seccion contextual; no se crean
  documentos ni relaciones durante el render de fichas.
- `git diff --check`: sin errores de whitespace en el cierre documental.

### Warnings y pendientes 4C

- 4C-A consume la carga contextual existente de 4A-C; cualquier cambio futuro en carga
  debe validar entidad/hotel y CSRF.
- No existe autorizacion para borrado, reemplazo de archivo, links publicos, permisos
  profundos, Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.
- No hacer push.

## Cierre tecnico Fase 4D-A

Estado final: `FASE_4D_A_VALIDADA_MANUALMENTE`.

La Fase 4D-A queda cerrada tecnicamente como archivado/restauracion reversible de
documentos. No habilita baja logica `eliminado`, borrado fisico, `DELETE`, reemplazo
de archivo, links publicos, Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

### Fases cerradas 4D

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 4D-0 | Contrato y diagnostico de archivado documental | `67e00ec` | Cerrada |
| 4D-A | Archivado/restauracion documental controlada | `01733ee` | Cerrada y validada manualmente |

### Confirmaciones 4D-A

- Rutas POST: `/documentos/{id}/archivar` y `/documentos/{id}/restaurar`.
- Ambas acciones usan CSRF y pasan por los guards existentes del controlador documental.
- El modelo central `Documento::actualizarEstado()` valida `id + hotel_id`.
- Transiciones permitidas: `activo -> archivado` y `archivado -> activo`.
- La vista de detalle solo muestra la accion compatible con el estado actual.
- No se envia `estado`, `hotel_id`, `storage_path` ni `nombre_archivo` desde formularios.
- Se audita `documentos.estado_actualizado`.
- No hay `DELETE`, no se borra archivo fisico ni se borran relaciones.
- Sin Caja, pagos, abonos, Fase 3D, NP-A ni cambios en `/api/sync`.

### Verificaciones 4D-A registradas

- `php -l` en modelo, controlador, vista y health checker: sin errores.
- `health_check_fase_1a.php`: PASS con warnings permitidos y `ERROR: 0`.
- `preflight_compras_minimas.php`: PASS con warnings permitidos.
- `preflight_recepcion_compras.php`: PASS con warnings permitidos.
- HTTP sin sesion en `POST /documentos/1/archivar`: `303` a login.
- SQL read-only: documentos y relaciones conservados; movimientos CxP en cero y Caja
  sin cambios atribuibles a 4D-A.
- Prueba transaccional con rollback: cambio `activo -> archivado`, auditoria dentro de
  la transaccion y rollback sin persistencia.
- `git diff --check`: sin errores de whitespace.

### QA manual 4D-A

- QA manual reportada por el usuario como correcta: todas las pruebas responden
  perfectamente.
- Se valida archivar/restaurar documento desde navegador y conservar archivo,
  relaciones y auditoria.

### Warnings y pendientes 4D-A

- Si la QA manual cambia estados, el rollback operativo debe hacerse con la accion
  inversa desde UI, no con `DELETE`.
- La baja logica hacia `eliminado` queda diferida a una fase futura con contrato propio.
- No hacer push.

## Cierre documental Fase 4D-B-0

Estado final: `CONTRATO_4D_B_BAJA_LOGICA_DOCUMENTAL_COMPLETADO`.

La Fase 4D-B-0 queda cerrada solo como contrato de baja logica documental. No agrega
rutas, controladores, modelos, vistas, DB, migraciones ni acciones operativas.

### Confirmaciones 4D-B-0

- Documento creado: `docs/fase_4D_B_0_contrato_baja_logica_documental.md`.
- Baja logica futura definida como cambio de `documentos.estado` hacia `eliminado`.
- Transiciones futuras propuestas: `activo -> eliminado` y `archivado -> eliminado`.
- Restaurar desde `eliminado` queda fuera de alcance y requiere contrato separado.
- Implementacion futura debe usar POST + CSRF, filtro `id + hotel_id`, confirmacion
  fuerte y auditoria.
- Sin borrado fisico, `DELETE`, baja masiva, Caja, pagos, abonos, Fase 3D, NP-A ni
  cambios en `/api/sync`.

### Warnings y pendientes 4D-B-0

- No existe ruta operativa `POST /documentos/{id}/eliminar` todavia.
- No hay QA manual de navegador porque 4D-B-0 no implementa funcionalidad.
- Implementar 4D-B-A requiere nueva autorizacion explicita.
- No hacer push.

## Implementacion tecnica Fase 4D-B-A

Estado: `BAJA_LOGICA_DOCUMENTAL_4D_B_A_COMPLETADA_QA_DIFERIDA`.

La Fase 4D-B-A implementa baja logica documental controlada. QA manual queda diferida
por instruccion del usuario para permitir avance autonomo.

### Confirmaciones 4D-B-A

- Ruta POST: `/documentos/{id}/eliminar`.
- Controlador: `DocumentoController::eliminarAction()`.
- Modelo central: `Documento::actualizarEstado()`.
- Transiciones permitidas: `activo -> eliminado` y `archivado -> eliminado`.
- Cambios desde `eliminado` siguen bloqueados.
- Vista de detalle muestra `Baja logica` solo para documentos `activo` o `archivado`.
- Formulario con CSRF y confirmacion fuerte.
- Auditoria: `documentos.estado_actualizado`.
- Sin borrado fisico, sin `DELETE`, sin borrar relaciones, sin Caja, pagos, abonos,
  Fase 3D, NP-A ni `/api/sync`.

### QA diferida 4D-B-A

- Validar `activo -> eliminado`.
- Validar `archivado -> eliminado`.
- Validar que `eliminado` no muestra descarga, edicion, archivar, restaurar ni baja
  logica.
- Validar que archivo fisico y relaciones se conservan.
- Validar auditoria.

## Cierre tecnico Fase 4D

Estado final: `CIERRE_TECNICO_4D_COMPLETADO_QA_DIFERIDA`.

El bloque documental 4D queda culminado tecnicamente:

- 4D-0 contrato y diagnostico: completado.
- 4D-A archivado/restauracion reversible: implementado y validado manualmente.
- 4D-B-0 contrato de baja logica: completado.
- 4D-B-A baja logica controlada: implementada y verificada automaticamente.

QA manual pendiente diferida por instruccion del usuario:

- Baja logica `activo/archivado -> eliminado`.
- Bloqueo visual de descarga/edicion/restauracion en estado `eliminado`.
- Conservacion de archivo fisico, metadata y relaciones.
- Auditoria `documentos.estado_actualizado`.

No se implemento recuperacion desde `eliminado`, borrado fisico, `DELETE`, baja masiva,
reemplazo de archivo, links publicos, Caja, pagos, abonos, Fase 3D, NP-A ni cambios en
`/api/sync`.

## Checkpoint tecnico Fase NP-A

Estado: `PERSONAL_READ_ONLY_NP_A_COMPLETADO_QA_DIFERIDA`.

La base de Personal queda creada como estructura vacia y con primera UI read-first, sin
movimientos.

### Confirmaciones NP-A

- Backup previo valido con SHA256 documentado.
- Migracion aplicada: `migrations/20260616_001_fase_np_a_personal_base.sql`.
- Tablas creadas vacias:
  - `trabajadores`;
  - `trabajador_pagos`;
  - `trabajador_anticipos`;
  - `trabajador_prestamos`;
  - `trabajador_asistencias`;
  - `trabajador_documentos`.
- Rutas GET implementadas:
  - `/trabajadores`;
  - `/trabajadores/{id}`.
- Modelo/controlador/vistas read-only implementados.
- Sidebar muestra `Personal` bajo administracion.
- Health checker valida estructura NP-A y migracion registrada.
- Caja/Nomina sigue en cero.
- No se tocaron `usuarios` destructivamente, Caja ni `/api/sync`.
- No hay POST, alta, edicion, pagos, anticipos, prestamos ni asistencia operativa.

QA manual diferida:

- Validar listado/ficha en navegador.
- Validar filtros GET y estados vacios.
- Validar bloqueo sin sesion/permiso.
- Validar que no hay botones operativos de nomina/Caja.

## Contrato Fase NP-B-0

Estado: `CONTRATO_NP_B_CRUD_TRABAJADORES_COMPLETADO`.

- Documento: `docs/fase_NP_B_0_contrato_crud_trabajadores.md`.
- Define la siguiente implementacion segura: alta, edicion, baja logica y reactivacion
  de trabajadores.
- No implementa codigo, rutas, DB ni escrituras.
- No autoriza pagos, anticipos, prestamos, asistencia operativa, documentos laborales,
  Caja, categoria Nomina ni `/api/sync`.

## Implementacion Fase NP-B-A

Estado: `CRUD_TRABAJADORES_NP_B_A_COMPLETADO_QA_DIFERIDA`.

- Rutas CRUD basicas de trabajadores implementadas.
- Modelo central valida datos y hotel.
- Vista de formulario con POST + CSRF.
- Listado/detalle muestran editar, baja logica o reactivar segun estado.
- Auditoria en cambios de trabajador.
- Sin pagos, anticipos, prestamos, asistencia operativa, documentos laborales, Caja ni
  `/api/sync`.

## Contrato Fase TLM-0

Estado: `CONTRATO_TLM_0_COMPLETADO`.

- Documento: `docs/fase_TLM_0_contrato_diagnostico.md`.
- Diagnostico read-only completado.
- No hay tabla propia de tareas; existe `mantenimientos_habitaciones` como fuente actual.
- `habitaciones.estado` sigue siendo fuente de disponibilidad.
- No se implementaron migraciones, rutas, controladores, modelos ni vistas.
- No se escribio en DB.
- No se toco Caja, pagos, abonos, nomina, PWA/offline/cache ni `/api/sync`.
- Siguiente subfase segura: TLM-A migracion base aditiva de tareas con backup previo.

## Migracion Fase TLM-A

Estado: `MIGRACION_TLM_A_TAREAS_BASE_COMPLETADA_QA_DIFERIDA`.

- Backup previo validado con SHA256.
- Migracion aplicada: `migrations/20260616_002_fase_tlm_a_tareas_base.sql`.
- Tablas creadas y vacias:
  - `tareas_operativas`;
  - `tarea_eventos`.
- Migracion registrada en `migrations`.
- Health checker actualizado para validar TLM-A.
- No hay rutas, UI, controladores, modelos ni POST de tareas.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.

## Read-only Fase TLM-B

Estado: `TAREAS_READ_ONLY_TLM_B_COMPLETADO_QA_DIFERIDA`.

- Modelo read-only: `TareaOperativa`.
- Controlador GET: `TareaController`.
- Rutas: `/tareas` y `/tareas/{id}`.
- Vistas de listado/detalle con filtros GET y estado vacio.
- Sidebar muestra `Tareas` bajo Operaciones.
- Health checker valida rutas, modelo, vistas, sidebar y ausencia de POST.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.

## Creacion manual Fase TLM-C

Estado: `CREACION_MANUAL_TLM_C_COMPLETADA_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_C_creacion_manual_tareas.md`.
- Rutas autorizadas:
  - `GET /tareas/crear`;
  - `POST /tareas`.
- La tarea se crea `pendiente`, `manual` y con evento inicial `creada`.
- POST protegido con CSRF, permiso `habitaciones.mantenimiento` y auditoria.
- No se implementa asignacion, inicio, cierre ni cancelacion.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

## Asignacion Fase TLM-D

Estado: `ASIGNACION_TLM_D_COMPLETADA_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_D_asignacion_trabajador.md`.
- Ruta autorizada:
  - `POST /tareas/{id}/asignar`.
- La asignacion valida tarea y trabajador activo del mismo hotel.
- La tarea queda en estado `asignada` y registra evento `asignada`.
- POST protegido con CSRF, permiso `habitaciones.mantenimiento` y auditoria.
- No se implementa inicio, cierre ni cancelacion.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

## Estados manuales Fase TLM-E

Estado: `ESTADOS_TLM_E_COMPLETADOS_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_E_estados_tarea.md`.
- Rutas autorizadas:
  - `POST /tareas/{id}/iniciar`;
  - `POST /tareas/{id}/completar`;
  - `POST /tareas/{id}/cancelar`.
- Transiciones manuales centralizadas en modelo.
- Se registran eventos y auditoria.
- No se implementa liberacion/bloqueo automatico de habitacion.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

## Contexto visual Fase TLM-F

Estado: `CONTEXTUAL_TLM_F_COMPLETADO_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_F_contextual_tareas.md`.
- Se implemento bloque contextual read-only en ficha de habitacion y trabajador.
- No se agregaron rutas nuevas, POST nuevos, migraciones ni escrituras.
- `TareaOperativa::listarPorEntidadHotel()` filtra por hotel y entidad permitida.
- Health checker valida modelo, partial e integracion en ambas fichas.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.
- QA manual queda diferida por instruccion del usuario.

## Health/preflight Fase TLM-G

Estado: `PREFLIGHT_TLM_G_COMPLETADO_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_G_preflight_consistencia.md`.
- Preflight: `src/tools/saas/preflight_tareas_operativas.php`.
- Health checker valida consistencia de tareas/eventos y ausencia de Caja relacionada.
- No se agregaron rutas nuevas, POST nuevos, migraciones ni escrituras.
- No se cambio `habitaciones.estado`.
- No se toco `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina, PWA/offline/cache
  ni `/api/sync`.

## Cierre tecnico Fase TLM-H

Estado: `BLOQUE_TLM_CERRADO_QA_DIFERIDA`.

- Documento: `docs/fase_TLM_H_cierre_bloque.md`.
- Revision tecnica y auditoria del bloque TLM completadas.
- Commits TLM registrados hasta TLM-G.
- Health y preflight TLM-G sin errores.

## Checkpoint tecnico NP-C-0

Estado: `CONTRATO_NP_C_LEDGER_LABORAL_COMPLETADO`.

- Documento: `docs/fase_NP_C_0_contrato_ledger_laboral.md`.
- Solo contrato: no hay cambios de DB ni codigo.
- Caja, pagos reales, abonos, categoria Nomina y `/api/sync` siguen fuera de alcance.
- Siguiente paso seguro: NP-C-A read-only.

## Checkpoint tecnico NP-C-A

Estado: `LEDGER_LABORAL_NP_C_A_COMPLETADO_QA_DIFERIDA`.

- Documento: `docs/fase_NP_C_A_ledger_read_only.md`.
- Codigo read-only integrado en ficha de trabajador.
- No hay rutas nuevas, POST, migraciones ni escrituras.
- Caja, pagos reales, abonos, categoria Nomina y `/api/sync` siguen fuera de alcance.

## Checkpoint tecnico NP-C-E

Estado: `PREFLIGHT_LEDGER_NP_C_E_COMPLETADO_QA_DIFERIDA`.

- Documento: `docs/fase_NP_C_E_preflight_ledger.md`.
- Nuevo preflight read-only de ledger laboral.
- Health checker lo detecta.
- Sin UI, rutas, POST, migraciones ni escrituras.

## Cierre tecnico NP-C-F

Estado: `BLOQUE_NP_C_READ_ONLY_CERRADO_QA_DIFERIDA`.

- Documento: `docs/fase_NP_C_F_cierre_ledger_read_only.md`.
- Cierra el bloque read-only de ledger laboral.
- QA manual queda diferida.
- La siguiente escritura laboral requiere contrato nuevo.

## Contrato NP-C-B-0

Estado: `CONTRATO_NP_C_B_CONCEPTOS_LABORALES_COMPLETADO`.

- Documento: `docs/fase_NP_C_B_0_contrato_conceptos_laborales.md`.
- Solo contrato; no agrega rutas, vistas, POST ni escrituras.
- Define futura escritura manual de conceptos laborales sin Caja.
- QA manual queda diferida por instruccion del usuario.

## Checkpoint tecnico NP-C-B-A

Estado: `CONCEPTOS_LABORALES_NP_C_B_A_COMPLETADO_QA_DIFERIDA`.

- Documento: `docs/fase_NP_C_B_A_conceptos_laborales.md`.
- Ruta POST controlada para registrar conceptos laborales.
- Modelo central con transaccion, validacion de trabajador activo y aislamiento
  `hotel_id`.
- Vista de trabajador con formulario CSRF y advertencia de no Caja/no pago real.
- Health/preflight actualizados y ejecutados sin errores.
- QA manual queda diferida porque no hay trabajadores locales.
