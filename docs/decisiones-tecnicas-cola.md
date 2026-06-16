# Decisiones tecnicas - cola autonoma

## Base de datos

- La base local principal sigue siendo `medisoft_hoteles_import`.
- La migracion de Fase 3B fue aplicada localmente con backup previo.
- Las tablas de CxP quedan vacias hasta que exista una fase autorizada para generacion de saldos.

## Cuentas por pagar

- `cuentas_por_pagar` y `cuentas_por_pagar_movimientos` son fuente fundacional nueva.
- El codigo de Fase 3B solo usa operaciones de lectura.
- No se agregan formularios POST.
- No se agregan acciones de pago.
- No se integra Caja.
- No se genera CxP automaticamente desde compras.
- La navegacion se agrega bajo el mismo alcance funcional que inventario, sin crear permisos profundos nuevos.
- Riesgo residual documentado: la estructura permite referencias a proveedor/compra por ID; cualquier escritura futura debe comprobar `hotel_id` de proveedor y compra antes de insertar.

## Compras y proveedores

- Compras recibidas y proveedores pueden enlazarse desde CxP solo en modo lectura.
- Las fases anteriores de compras/proveedores permanecen read-only salvo el flujo minimo de recepcion ya autorizado.
- La recepcion de compra debe seguir protegida contra doble recepcion.

## Inventario

- `inventario_productos` y `movimientos_inventario` siguen siendo tablas modernas.
- `productos`, `inventario_movimientos` e `inventario_habitacion_config` permanecen como legacy congelado.
- No se fusionan ni eliminan tablas duplicadas en este cierre.

## Sync y PWA

- `/api/sync` no se toca.
- Debe seguir bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.
- No se modifican service workers, IndexedDB, cache names ni archivos offline.

## Git

- `src/app/views/reservaciones/ver.php` se considera cambio no relacionado con Fase 3B y queda fuera del commit.
- El commit de cierre debe ser selectivo e incluir solo CxP, checkers, docs y rutas/navegacion relacionadas.
- El commit de cierre Fase 3B fue `1fa1653`.
- Cualquier decision sobre `src/app/views/reservaciones/ver.php` debe hacerse en commit separado tras QA visual.

## Cierre tecnico post-commit

- El bloque 2X-3B quedo en estado `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`; Fase 3C quedo validada manualmente despues.
- No se avanza a Fase 3C sin nuevo mensaje real.
- No se implementan pagos, Caja, CxC, nomina ni permisos profundos.
- La documentacion final puede seguir ajustandose sin cambiar comportamiento funcional.

## Auditoria de seguridad post-cierre

- Sin rutas sensibles nuevas sin `requireAuth`.
- Sin POST operativo en CxP.
- Sin tokens de escritura CxP en controlador/modelo/vistas CxP.
- Sin integracion accidental con Caja.
- `/api/sync` sigue registrado y bloqueado.
- Las tablas legacy de inventario siguen congeladas; el bloque de compras usa `inventario_productos` y `movimientos_inventario`.

## Fase 3C

- Nuevo mensaje real autoriza Fase 3C.
- La generacion CxP sera manual, nunca automatica al recibir compra.
- Fase 3C-A debe ser simulador read-only antes de cualquier escritura.
- Fase 3C-B podra escribir `cuentas_por_pagar`, pero no Caja ni pagos.
- Toda CxP nacida de compra debe validar compra recibida, proveedor del mismo hotel, `total > 0` y no duplicado `(hotel_id, compra_id)`.
- La auditoria debera usar `AuditService` si el patron sigue disponible.
- `cuentas_por_pagar_movimientos` solo podra usarse como trazabilidad interna de CREACION si se mantiene sin pagos ni Caja.
- `/api/sync` sigue fuera de alcance.

## Reanclaje Fase 3C

- Nuevo mensaje real `REANCLAR_FASE_3C_VERDAD_ACTUAL` corrige el estado documental.
- Estado formal vigente: `FASE_3C_VALIDADA_MANUALMENTE`.
- `9897465` queda como contrato 3C-0 completado.
- `c216dc5` contiene codigo de simulador; queda como base parcial pendiente de verificacion formal.
- `cb83121` contiene el antecedente historico de generacion manual; la implementacion vigente reintroduce 3C-B con validacion automatica.
- `5dfe665` contiene validaciones; se conservan como checkers read-only de consistencia CxP.
- `8765258` queda reclasificado como antecedente de revision prematura; la revision tecnica actual ya se ejecuto despues de 3C-C.
- `2662998` queda reclasificado como auditoria prematura/documental.
- Siguiente accion: triage separado de cambios PWA/no relacionados. No avanzar a pagos, abonos, Caja ni Fase 3D.

## Fase 3C-A

- El simulador se ubica dentro de CxP, no dentro de recepcion de compras, para evitar que recibir una compra sugiera generacion automatica.
- La ruta nueva es solo `GET /cuentas-por-pagar/generacion-preview`.
- Se reutilizan los guards existentes de CxP: autenticacion, contexto hotelero y modulo `inventario`.
- El modelo usa solo consultas `SELECT` sobre `compras`, `compra_detalles`, `proveedores`, `hoteles` y `cuentas_por_pagar`.
- La elegibilidad se evalua por compra recibida, proveedor del mismo hotel, total positivo, detalles vinculados y ausencia de CxP previa.
- Una compra bloqueada muestra motivo explicito en la vista.
- La vista solo enlaza a recursos read-only ya existentes: detalle de compra, proveedor y CxP.
- No se agregan formularios POST ni botones de generacion en 3C-A.

## Fase 3C-B

- Estado: implementada tecnicamente y validada manualmente por el usuario.
- La generacion queda como accion manual en CxP, no como efecto secundario de `CompraService::recibirCompra()`.
- La ruta POST activa es `POST /cuentas-por-pagar/generar-desde-compra/{id}`.
- El preview muestra `Generar CxP` solo cuando la compra es elegible.
- El modelo centraliza validaciones en `CuentaPorPagar::generarDesdeCompraRecibida()`.
- La compra se bloquea con `FOR UPDATE`; se valida recibida, proveedor del hotel, total positivo, detalles y ausencia de CxP previa.
- Se inserta solo en `cuentas_por_pagar`; no se inserta en `cuentas_por_pagar_movimientos`.
- La trazabilidad queda en `logs_auditoria` via `AuditService::record()`.
- Caja debe seguir completamente fuera del flujo.

## Revision tecnica Fase 3C

- Estado: completada despues de 3C-A, 3C-B y 3C-C.
- La QA manual de 3C-A/3C-B queda vigente y documentada.
- El preview debe renderizar link a proveedor solo cuando el proveedor fue resuelto por el join scoped al `hotel_id` de la compra.
- Si una compra conserva `proveedor_id` pero el proveedor no existe en el hotel actual, la fila queda bloqueada y no debe enlazar a otro proveedor.
- No se agrega indice/migracion en esta revision; la prevencion de duplicados sigue basada en bloqueo transaccional de la compra con `FOR UPDATE` y verificacion de CxP existente.
- CxP `#2` se verifico como generada desde compra `#2`, compra recibida, mismo hotel/proveedor, total y saldo validos.
- No se autoriza pago, abono, Caja ni Fase 3D.

## Auditoria seguridad Fase 3C

- Estado: completada post-3C-C sin hallazgos bloqueantes.
- No se agrega migracion ni indice en esta auditoria.
- Los riesgos de concurrencia se mantienen como residuales documentados; cualquier endurecimiento con indice unico requerira migracion futura autorizada.
- La ausencia de pagos/Caja sigue siendo una regla de fase, no solo una decision visual.
- Health/preflights y SQL read-only confirman cero movimientos CxP/pagos/abonos, cero Caja-CxP y `/api/sync` bloqueado.
- Cambios no relacionados de dashboard/habitaciones/notificaciones quedan fuera del bloque 3C.

## Cierre tecnico Fase 3C

- Estado: completado documentalmente.
- No habilita pagos, abonos, Caja, Fase 3D ni cambios en `/api/sync`.
- La QA manual de 3C-A, 3C-B y cierre final 3C ya fue reportada como OK por el usuario.
- Cambios no relacionados ya separados en `e52766e`: dashboard, habitaciones, notificaciones, sidebar y vista de notificaciones.
- Cambios PWA no relacionados (`PwaPushService.php`, `service-worker.js`) fueron validados manualmente y commiteados por separado en `35abdc7`; no forman parte de CxP.

## Fase 3C-C

- Estado: completada tecnicamente como validaciones read-only en health/preflights.
- Las validaciones fallan con ERROR si detectan CxP duplicada por compra/hotel, compra o proveedor inexistente, `hotel_id` nulo, cruce de hotel, compra no recibida, saldo mayor al total, total invalido, fecha de emision nula, movimientos CxP/pagos/abonos o referencias CxP en Caja.
- No se agregan rutas, vistas, pagos, abonos, Caja ni cambios en `/api/sync`.

## Bloque Personal y Nomina (Fase NP)

## Fase 4A Centro Documental

- Estado formal vigente: `CIERRE_TECNICO_4A_COMPLETADO`.
- Centro Documental debe iniciar con storage privado, no con enlaces publicos directos.
- `public_html/uploads` queda reservado para assets publicos/imagenes ya existentes.
- El patron de descarga segura de `ReporteLinkController` es la referencia tecnica para
  resolver rutas con `realpath`, limitar raices permitidas y servir con headers privados.
- Las tablas base creadas son aditivas: `documento_tipos`, `documentos`,
  `documento_entidades`.
- La relacion con entidades sera polimorfica; la validacion de pertenencia al mismo
  `hotel_id` debe vivir en modelo/servicio, no solo en vista.
- 4A-A no inserta datos operativos y deja las tres tablas vacias.
- `mime_permitidos` y `etiquetas` quedan como `TEXT` para mantener compatibilidad sin
  depender de JSON en esta fundacion.
- `documento_entidades` usa indices no unicos para no bloquear relaciones futuras
  multiples con la misma entidad bajo roles distintos.
- `storage_path` queda como path privado futuro, nunca como URL publica.
- 4A-0 no creo migraciones; 4A-A crea solo esquema. No implementa uploads, no crea POST,
  no crea descargas y no escribe documentos.
- 4A-B introduce un modelo read-only (`Documento`) en lugar de un servicio separado para
  mantener el patron local de modelos MVC ya usado por CxP, proveedores y compras.
- Las consultas 4A-B seleccionan metadata segura y excluyen `storage_path` y
  `nombre_archivo` de las vistas.
- El guard 4A-B usa autenticacion, contexto hotelero y modulos relacionados visibles
  (`inventario`, `huespedes`, `reservaciones`) sin crear permisos profundos nuevos.
- La navegacion `Documentos` se muestra solo si existe al menos un modulo relacionado
  activo en el hotel.
- 4A-C agrega carga manual segura con `POST /documentos/subir`, CSRF y validaciones en
  modelo, pero no agrega descarga, edicion ni borrado.
- El storage de 4A-C queda bajo `STORAGE_PATH/documentos/hotel_{hotel_id}/YYYY/MM` con
  nombre fisico aleatorio y permisos `0640`.
- Se bloquean extensiones peligrosas y se valida MIME real con `finfo`; tipos iniciales:
  PDF, JPG/JPEG, PNG y WEBP.
- La relacion con entidad se valida en modelo contra la tabla real y el `hotel_id` actual
  antes de insertar en `documento_entidades`.
- La auditoria de carga usa `AuditService::record('documentos.cargado')`.
- `src/storage/documentos/` se ignora en Git porque contiene runtime privado local, no
  codigo versionado.
- La descarga segura queda fuera de 4A-C y requiere una fase posterior con controlador
  autenticado, `realpath` y headers privados.
- Hotfix post-QA 4A-C: se agregan tipos documentales globales por migracion seed
  idempotente para evitar un select vacio.
- La carga general ya no solicita manualmente `entidad_tipo`/`entidad_id`; el vinculo se
  conserva solo si la ruta llega con contexto de entidad validado.
- El CSS del layout debe cargarse con `asset()` para evitar 404 en subrutas.
- Revision tecnica post-QA 4A: el listado contextual `/documentos/entidad/{tipo}/{id}`
  debe validar existencia real de la entidad en el hotel actual antes de mostrar la vista
  o el enlace de carga contextual.
- Auditoria seguridad post-QA 4A confirma que no hay descarga, edicion ni borrado
  documental; `storage_path` no es URL publica; PWA/offline y `/api/sync` quedan fuera
  del bloque.
- Cierre tecnico 4A no autoriza descargas, edicion, borrado, pagos, Caja, Fase 3D ni
  `/api/sync`; cualquier descarga segura requiere nuevo bloque explicito.
- No se permite tocar Caja, pagos, abonos, Fase 3D ni `/api/sync`.

## Fase 4B Descarga segura documental

- Estado formal vigente: `DESCARGA_SEGURA_4B_VALIDADA_MANUALMENTE`.
- 4B-0 documento contrato; 4B-A implementa descarga autenticada.
- La ruta implementada es `GET /documentos/{id}/descargar`.
- La descarga debe ser autenticada, scoped por `hotel_id` y restringida a documentos
  `activo`.
- La ruta fisica debe resolverse con `realpath` bajo
  `realpath(STORAGE_PATH . '/documentos')`.
- No se permiten links publicos de documentos en esta fase.
- El patron de `ReporteLinkController` sirve como referencia para headers privados y
  `realpath`, pero no debe copiarse el acceso publico por token.
- No se agrega auditoria de descarga en 4B-A para evitar escrituras DB en una accion de
  lectura de archivo; puede autorizarse despues como fase separada.
- 4B no autoriza edicion, borrado, Caja, pagos, abonos, PWA/offline ni `/api/sync`.

### Fase 4B-B auditoria de descargas

- Estado formal vigente: `AUDITORIA_DESCARGAS_4B_B_VALIDADA_MANUALMENTE`.
- Se decide registrar `documentos.descargado` y `documentos.descarga_bloqueada` en
  `logs_auditoria` usando `AuditService::record()`.
- La auditoria queda en controlador, cerca del flujo HTTP de descarga, porque no cambia
  la fuente de verdad del archivo ni el modelo de storage.
- No se registra `storage_path` ni `nombre_archivo`.
- Los fallos de auditoria no bloquean la descarga.

### Fase 4B-C-A metadata documental

- Estado formal vigente: `METADATA_DOCUMENTAL_4B_C_A_VALIDADA_MANUALMENTE`.
- La edicion permitida es solo metadata segura.
- No se deben editar archivo fisico, storage, hash, MIME, tamano ni hotel.
- El POST usa CSRF y auditoria diferencial.

### Cierre tecnico 4B post-QA

- Estado formal vigente: `CIERRE_TECNICO_4B_COMPLETADO`.
- Se cierra 4B despues de QA manual de descarga, auditoria de descargas y metadata.
- El cierre no agrega funcionalidad: solo registra revision tecnica, auditoria de
  seguridad y verificaciones.
- Cualquier fase futura de archivado, baja logica, reemplazo o links publicos requiere
  contrato nuevo y autorizacion explicita.

### Fase 4C-0 documentos por entidad

- Estado formal vigente: `CONTRATO_4C_DOCUMENTOS_ENTIDAD_COMPLETADO`.
- Se elige integrar documentos por entidad antes de archivado/baja logica porque es
  menos destructivo y reutiliza infraestructura existente.
- 4C-0 no agrega codigo ni DB; solo define contrato, riesgos y subfases.
- La implementacion futura debe usar `documento_entidades` y no duplicar tablas por
  entidad.
- No se autorizan borrado, reemplazo, links publicos, Caja, pagos, abonos, Fase 3D,
  NP-A ni `/api/sync`.

### Fase 4C-A documentos por entidad contextual

- Estado formal vigente: `DOCUMENTOS_ENTIDAD_CONTEXTUAL_4C_VALIDADA_MANUALMENTE`.
- Se crea un partial reutilizable para evitar duplicar tablas de documentos en cada
  ficha.
- Los controladores consultan documentos por entidad con `hotel_id` antes de renderizar.
- Las fichas de proveedor, compra, CxP, huesped y reservacion solo muestran metadata
  segura y enlaces GET a detalle/descarga documental ya protegida.
- Se agrega enlace GET `Vincular documento` porque el flujo contextual ya existe y
  valida entidad/hotel antes de mostrar el formulario.
- La QA manual posterior confirma que el enlace contextual aparece y funciona en las
  fichas esperadas.
- No se agrega metadata edit, borrado ni reemplazo desde fichas para mantener el
  alcance controlado.

### Fase 4D-0 archivado documental

- Estado formal vigente: `CONTRATO_4D_ARCHIVADO_DOCUMENTAL_COMPLETADO`.
- Se elige archivado/restauracion reversible antes que baja logica porque reduce riesgo
  y aprovecha `documentos.estado = activo/archivado/eliminado` ya existente.
- 4D-0 no agrega codigo ni DB; solo define contrato, transiciones y controles.
- La implementacion futura debe validar transiciones en modelo/servicio central, no en
  vistas.
- Toda accion futura debe ser POST + CSRF, buscar por `id + hotel_id` y auditar
  antes/despues.
- No se autoriza borrado fisico, `DELETE` SQL, reemplazo de archivo, links publicos,
  Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync`.

### Fase 4D-A archivado documental controlado

- Estado formal vigente: `FASE_4D_A_VALIDADA_MANUALMENTE`.
- Se implementa solo `activo <-> archivado`; `eliminado` queda fuera para evitar baja
  logica irreversible o ambigua.
- Las acciones viven en el detalle documental, no en listados masivos.
- Se usa POST + CSRF y confirmacion del navegador para reducir clic accidental.
- La vista no acepta `estado` libre; el controlador decide el estado objetivo y el
  modelo valida la transicion.
- La auditoria usa `documentos.estado_actualizado` con estado antes/despues seguro.
- La revision tecnica y auditoria de cierre no detectaron hallazgos bloqueantes; la
  baja logica `eliminado` permanece diferida a contrato futuro.
- La QA manual del flujo archivar/restaurar fue reportada por el usuario como correcta.

### Fase 4D-B-0 contrato de baja logica documental

- Estado formal vigente: `CONTRATO_4D_B_BAJA_LOGICA_DOCUMENTAL_COMPLETADO`.
- Se define baja logica como cambio de estado a `eliminado`, nunca como borrado fisico.
- La implementacion no se agrega en 4D-B-0 para evitar un cambio destructivo accidental.
- Las transiciones futuras permitidas seran `activo -> eliminado` y
  `archivado -> eliminado`; recuperar desde `eliminado` requiere otro contrato.
- El boton futuro debe vivir en detalle documental y requerir confirmacion fuerte,
  POST + CSRF y auditoria.

### Fase 4D-B-A baja logica documental controlada

- Estado formal vigente: `BAJA_LOGICA_DOCUMENTAL_4D_B_A_COMPLETADA_QA_DIFERIDA`.
- Se reutiliza `Documento::actualizarEstado()` para mantener una sola politica central
  de cambios de estado documental.
- Se autoriza solo `activo/archivado -> eliminado`; cualquier recuperacion desde
  `eliminado` queda fuera de alcance.
- La vista muestra `Baja logica` solo en detalle documental y no en listados masivos.
- No se elimina archivo fisico ni relaciones; la baja logica es reversible solo por
  contrato futuro, no por accion actual.

### Decisiones de diagnostico NP-0

- Hoy "trabajador" = `usuarios` (tabla global, sin `hotel_id`, `rol` de sistema) + pivote
  `hotel_usuarios`. No hay rol laboral, deuda ni saldo por persona.
- El trabajador NP es una **entidad nueva e independiente**: no requiere usuario del sistema
  ni login. El vinculo a un usuario es opcional via `trabajadores.usuario_id` con
  `ON DELETE SET NULL`; jamas se altera `usuarios` de forma destructiva.
- No se convierte ni fusiona ningun `usuario` existente en trabajador.

### Decisiones de diseno de tablas NP

- 6 tablas aditivas, todas con `hotel_id NOT NULL` (FK `hoteles` RESTRICT) y, las hijas,
  `trabajador_id` (FK `trabajadores` RESTRICT). Baja logica via `estado`, nunca borrado fisico.
- El ledger laboral se reparte en `trabajador_pagos` (pago/comision/bono/descuento/ajuste
  con `efecto` a_favor/en_contra), `trabajador_anticipos` y `trabajador_prestamos`
  (ambos con `saldo_pendiente`).
- Un `tipo='pago'` en `trabajador_pagos` es una liquidacion laboral entregada; es un
  REGISTRO LABORAL, NO un movimiento de Caja.
- El saldo por trabajador es DERIVADO del ledger (no editable manualmente). Formula
  documentada en `docs/fase_NP_0_contrato_diagnostico.md`.
- Asistencia: `UNIQUE (hotel_id, trabajador_id, fecha)` para evitar duplicados por dia.
- Documentos: ruta de archivo siguiendo el patron de uploads del proyecto; baja logica.

### Decisiones sobre Caja y nomina

- NO se crea categoria "Nomina" en `categorias_movimientos` en este bloque.
- NO se inserta en `movimientos_caja` por nomina. La integracion Caja se difiere a una
  Fase NP-Caja autorizada por separado.

### Decision NP-A migracion base

- Se crea primero la base de datos vacia de Personal antes de UI o movimientos.
- La migracion es 100% aditiva y no crea datos semilla.
- Se mantiene `usuarios` intacta; `trabajadores.usuario_id` es opcional.
- Caja queda separada: no se crea categoria Nomina ni movimientos.
- Las fases visuales y operativas deben consumir estas tablas con filtro `hotel_id`.

### Decision NP-A UI read-first

- Se expone primero listado/ficha en GET para validar el contrato visual sin habilitar
  altas ni movimientos laborales.
- Se reutiliza `usuarios.view` y modulo `usuarios` como guard temporal conservador,
  porque Personal contiene datos laborales sensibles y aun no existe permisos profundos
  propios de nomina.
- La ficha muestra agregados de `trabajador_pagos`, `trabajador_anticipos`,
  `trabajador_prestamos`, `trabajador_asistencias` y `trabajador_documentos` solo como
  lectura; no calcula ni persiste saldos definitivos en esta subfase.
- No se integra con Centro Documental central para trabajadores todavia porque
  `Documento::ENTIDAD_TIPOS` no incluye `trabajador`; se evita mezclar contratos.
- No se muestra `ruta_archivo` de `trabajador_documentos`.

### Decision NP-B-0 CRUD trabajadores

- La siguiente escritura autorizable debe limitarse a `trabajadores`; el ledger laboral
  queda para otra subfase.
- La baja de trabajador sera logica (`estado = baja`) para evitar borrar historico y
  preservar relaciones futuras.
- No se crearan usuarios automaticamente; `usuario_id` permanece opcional.
- El guard temporal seguira usando administracion/usuarios hasta definir permisos
  profundos de Personal.
- Reglas de formularios: POST + CSRF, sin forms anidados y sin acciones financieras.

### Decision NP-B-A CRUD trabajadores

- Se implementa baja logica con `estado = baja` y no con borrado fisico.
- La reactivacion limpia `fecha_baja` para que el estado activo no conserve baja vigente.
- La creacion de trabajadores no crea usuarios ni cambia `hotel_usuarios`.
- `usuarios.create` protege alta; `usuarios.edit` protege edicion/baja/reactivacion.
- La auditoria se integra sin bloquear si `logs_auditoria` no estuviera disponible.

### Decision sobre "responsable" de mantenimiento

- En NP la referencia trabajador-responsable es **logica y opcional**, de solo lectura,
  basada en el campo de texto libre `mantenimientos_habitaciones.realizado_por`.
- NO se altera `mantenimientos_habitaciones`. Una columna FK real se difiere a una
  migracion aditiva posterior autorizada.

### Decisiones de seguridad/operacion NP

- Auditoria con `AuditService::record()` si la tabla `logs_auditoria` esta disponible.
- Escrituras explicitas (POST + CSRF + transaccion cuando el patron lo permita).
- `/api/sync` fuera de alcance.

## Decisiones TLM-0 Tareas, Limpieza y Mantenimiento

- TLM se define como una capa nueva de tareas operativas, no como reemplazo de
  `mantenimientos_habitaciones`.
- Se conserva `habitaciones.estado` como fuente de verdad para disponibilidad y estado
  operativo de habitacion.
- Se conserva `mantenimientos_habitaciones` como fuente historica y programada de
  mantenimiento de habitacion.
- La primera migracion futura debe ser aditiva, idempotente y separada; no debe alterar
  ni recalcular estados de habitacion.
- La relacion futura tarea-trabajador sera opcional y validada contra `trabajadores`
  activos del mismo hotel.
- No se automatizara el cambio de `habitaciones.estado` desde tareas en la primera etapa
  operativa.
- No se toca Caja, pagos, abonos, nomina, PWA/offline/cache ni `/api/sync`.

## Decision TLM-A migracion base

- Se crean solo `tareas_operativas` y `tarea_eventos`; `tarea_checklist_items` queda
  diferida para evitar sobrealcance.
- La migracion registra relaciones opcionales con habitacion, reservacion, huesped,
  trabajador y mantenimiento, pero no crea automatizaciones.
- `tarea_eventos` conserva historial tecnico sin reemplazar `logs_auditoria`.
- No hay datos semilla: las dos tablas quedan vacias.
- La primera UI futura debe ser read-only antes de habilitar cualquier POST.

## Decision TLM-B read-only

- La primera superficie de tareas se limita a GET listado/detalle.
- Se usa el guard conservador de `habitaciones` + `habitaciones.view`, porque TLM afecta
  contexto operativo de habitaciones.
- Sidebar muestra `Tareas` bajo Operaciones y solo si habitaciones/limpieza/mantenimiento
  estan activos visualmente.
- No se crean permisos profundos nuevos en esta subfase.
- No se muestran acciones de crear/asignar/iniciar/completar/cancelar hasta contrato
  separado.

## Decision TLM-C creacion manual

- Se habilita un unico POST autorizado: `POST /tareas`.
- La creacion manual usa `habitaciones.mantenimiento` como permiso conservador porque
  las tareas pueden impactar operacion de habitaciones.
- `hotel_id` se toma del contexto activo; no se envia ni se acepta desde el formulario.
- La habitacion vinculada es opcional y se valida por `id + hotel_id`.
- La tarea nace en `estado = pendiente` y `origen = manual`.
- Se registra evento inicial `creada` en `tarea_eventos`.
- Se usa transaccion para insertar tarea y evento juntos.
- Auditoria central con `AuditService::record()` se ejecuta sin bloquear el flujo si
  `logs_auditoria` no estuviera disponible.
- No se implementan asignacion, inicio, cierre, cancelacion ni cambios automaticos de
  `habitaciones.estado`.
- No se toca `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.

## Decision TLM-D asignacion a trabajador

- La asignacion se limita a tareas en estado `pendiente` o `asignada`.
- El trabajador debe existir, estar `activo` y pertenecer al mismo `hotel_id`.
- Asignar una tarea cambia solo `trabajador_id`, `asignada_por_usuario_id` y
  `estado = asignada`.
- La asignacion registra evento `asignada` en `tarea_eventos`.
- La asignacion registra auditoria con `AuditService::record()`.
- No se registra asistencia ni movimiento laboral financiero.
- No se crean pagos, abonos, Caja ni nomina.
- No se inicia ni cierra la tarea.
- No se cambia `habitaciones.estado`.
- No se modifica `mantenimientos_habitaciones` ni `/api/sync`.

## Decision TLM-E estados manuales

- Se implementan tres acciones explicitas: iniciar, completar y cancelar.
- Las transiciones se centralizan en `TareaOperativa::transicionManual()`.
- Completar/cancelar una tarea registra `fecha_cierre`, pero no modifica habitacion ni
  mantenimiento historico.
- Iniciar una tarea registra `fecha_inicio`, pero no genera asistencia laboral.
- Las notas opcionales se guardan como `notas_cierre` solo al completar/cancelar.
- Cada transicion registra evento tecnico y auditoria.
- No se automatizan disponibilidad, notificaciones, Caja, pagos, abonos ni nomina.
- `/api/sync` queda fuera de alcance.

## Decision TLM-F contexto visual de tareas

- La integracion contextual en habitacion/trabajador es read-only y reutiliza
  `TareaOperativa::listarPorEntidadHotel()`.
- No se agregan rutas nuevas ni POST nuevos.
- El partial contextual no contiene formularios ni acciones de estado; solo metadata y
  enlaces GET a detalle de tarea.
- La ficha de trabajador no se convierte en modulo de nomina ni asistencia.
- La ficha de habitacion no cambia disponibilidad ni mantenimiento por mostrar tareas.

## Decision TLM-G health y preflights

- Se agrega un preflight dedicado para tareas en lugar de ampliar los preflights de
  compras.
- Los checks de consistencia fallan ante corrupcion estructural y advierten ante casos
  operativos corregibles, como trabajador inactivo asignado a tarea activa.
- Como `huespedes` no tiene `hotel_id`, TLM-G no usa `huespedes.hotel_id` y considera
  `huesped_id` directo como vinculo no seguro hasta tener scope por reservacion u otra
  entidad hotelera.
- No se automatiza correccion de datos desde el checker.
- No se agregan rutas ni funcionalidades de usuario.

## Decision TLM-H cierre tecnico

- El bloque TLM se cierra tecnicamente sin automatizar disponibilidad de habitaciones.
- La QA manual queda diferida por instruccion del usuario.
- Cualquier fase futura de automatizacion debe abrir contrato nuevo y no puede inferirse
  desde este cierre.

## Decision NP-C-0 ledger laboral

- El ledger laboral se define como informacion derivada de tablas `trabajador_*`.
- La primera subfase implementable debe ser read-only para evitar confundir saldos
  laborales con pagos reales.
- Caja queda separada: no se crea categoria Nomina ni movimientos.
- Cualquier salida real de dinero requiere bloque NP-Caja independiente y autorizacion
  explicita.

## Decision NP-C-A read-only

- Se integra el ledger en la ficha existente de trabajador para evitar nuevas rutas.
- No se agregan botones operativos ni POST.
- El saldo se etiqueta como informativo para evitar tratarlo como pago real.
- Se mantienen vacios claros cuando las tablas `trabajador_*` no tienen registros.

## Decision NP-C-E preflight

- Se agrega preflight dedicado en vez de ampliar preflights de compras o tareas.
- El checker falla ante inconsistencias estructurales y no corrige datos.
- La ausencia de categoria Nomina en Caja se mantiene como condicion de seguridad.

## Decision NP-C-F cierre read-only

- El bloque NP-C se cierra solo como lectura y validacion.
- No se infiere autorizacion para registrar conceptos laborales por haber mostrado el
  ledger.
- La primera escritura laboral futura debe abrir contrato propio y mantener Caja fuera.

## Decision NP-C-B-0 conceptos laborales

- La primera escritura laboral futura se limita a conceptos no Caja: comision, bono,
  descuento y ajuste.
- El tipo `pago` no se habilita como salida real en este contrato.
- Cualquier egreso real requiere bloque NP-Caja independiente.

## Decision NP-C-B-A conceptos laborales manuales

- Se implementa la primera escritura laboral solo como `INSERT` en `trabajador_pagos`.
- La UI vive en la ficha de trabajador para mantener contexto y evitar navegacion nueva.
- No se habilita tipo `pago`; pagos reales quedan fuera del contrato.
- `hotel_id` se deriva del contexto de sesion y se valida contra el trabajador.
- La auditoria registra explicitamente `sin_caja` y `sin_pago_real`.
- No se crean categorias Nomina ni movimientos en Caja.

## Decision NP-C-B-F cierre conceptos laborales

- Se cierra tecnicamente el bloque sin abrir anticipos, prestamos ni asistencia.
- Las siguientes escrituras laborales requieren contratos separados por riesgo operativo.
- QA manual queda diferida y no se toma como validacion de usuario.

## Decision NP-C-C-0 anticipos y prestamos

- Anticipos y prestamos se separan de conceptos laborales para controlar mejor el riesgo.
- El saldo pendiente inicial se derivara del monto; no debe venir del formulario.
- Esta captura futura no representara salida real de dinero ni movimiento de Caja.
- Abonos, liquidaciones o anulaciones de saldo requieren contrato propio.

## Decision NP-C-C-A anticipos y prestamos manuales

- Se implementa captura manual inicial sin abonos ni liquidaciones.
- El estado inicial se fija en backend: `pendiente` para anticipos y `vigente` para
  prestamos.
- La vista no envia `estado`, `saldo_pendiente` ni `hotel_id`.
- Los abonos periodicos de prestamos son informativos y no crean pagos.
- Caja queda completamente fuera del flujo.

## Decision NP-C-C-F cierre anticipos y prestamos

- Se cierra tecnicamente el bloque sin abrir abonos, liquidaciones ni asistencia.
- La siguiente escritura laboral recomendada debe empezar por contrato de asistencia
  manual, sin nomina automatica.
- QA manual queda diferida y no se toma como validacion de usuario.

## Decision NP-C-D-0 asistencia manual

- La asistencia se manejara como registro laboral manual, no como nomina calculada.
- Se respeta la llave unica `(hotel_id, trabajador_id, fecha)`.
- Edicion, anulacion o correccion de asistencia requieren contrato separado.
- Tareas operativas no generan asistencia automaticamente.

## Decision NP-C-D-A asistencia manual

- Se implementa solo alta manual inicial en `trabajador_asistencias`.
- La vista no envia `hotel_id`, `trabajador_id`, `created_by` ni `updated_by`.
- La duplicidad por trabajador/dia se bloquea antes del `INSERT`.
- No se habilita edicion, anulacion, correccion, calculo de nomina ni descuento
  automatico.
- Caja queda completamente fuera del flujo.

## Decision NP-C-D-F cierre asistencia manual

- Se cierra asistencia manual sin convertirla en nomina.
- No se asume que el cierre habilita edicion/anulacion de asistencias.
- El siguiente avance de Personal debe ser contrato independiente y no financiero, salvo
  autorizacion explicita.

## Decision NP-D-0 documentos laborales

- No se abrira un segundo flujo documental basado en `trabajador_documentos` mientras ya
  exista Centro Documental moderno.
- La extension futura debe agregar `trabajador` como entidad documental y validar contra
  `trabajadores.hotel_id`.
- `trabajador_documentos` se mantiene congelada hasta reconciliacion autorizada.

## Decision NP-D-A documentos laborales

- Se reutiliza `View::partial('documentos_entidad')` en la ficha de trabajador.
- No se crean rutas nuevas porque el Centro Documental ya provee listado contextual,
  upload seguro, detalle y descarga segura.
- El contador de documentos laborales prefiere la fuente moderna y solo cae a
  `trabajador_documentos` si no existen las tablas documentales modernas.

## Decision NP-D-F cierre documentos laborales

- Se cierra documentos laborales sin reconciliar `trabajador_documentos`.
- No se asume que el cierre habilita migracion de archivos legacy.
- Cualquier reconciliacion documental laboral debe ser contrato separado.

## Decision NP-E cierre Personal operativo base

- Se agrega un cierre paraguas documental para evitar que las subfases laborales se
  interpreten como autorizacion de nomina o Caja.
- El siguiente avance recomendado es lectura/reporte antes que nuevas escrituras.
- QA manual queda diferida, no omitida definitivamente.
- Cualquier integracion financiera laboral debe abrir contrato independiente.

## Decision NP-F-0 reporte Personal read-only

- Se prioriza un reporte de lectura antes de abrir nuevas escrituras laborales.
- El reporte futuro debe operar como consolidacion, no como nomina.
- Tareas asignadas y documentos laborales pueden mostrarse como contexto, pero no deben
  generar asistencia, pagos, abonos ni Caja.
- Cualquier accion operativa desde el reporte queda fuera del contrato.

## Decision NP-F-A reporte Personal read-only

- La ruta vive en `/trabajadores/reporte` para mantenerla dentro del modulo Personal y no
  mezclarla con reportes financieros.
- El reporte reutiliza guardas existentes de Personal: sesion, hotel actual, modulo
  `usuarios` y permiso `usuarios.view`.
- No se agrega filtro de hotel visible; el hotel sale del contexto de sesion.
- El reporte muestra saldos como informacion laboral, no como deuda pagable.

## Decision NP-F-F cierre reporte Personal

- El reporte read-only se considera tecnicamente cerrado antes de abrir cualquier
  operacion financiera laboral.
- La QA manual queda diferida por instruccion del usuario, no sustituida por los checkers.
- El siguiente bloque debe ser independiente y contractual para evitar alcance implicito.

## Decision TLM-I-0 reporte operativo read-only

- Se prioriza lectura/reporting antes de nuevas acciones operativas de tareas.
- El reporte futuro debe vivir dentro de Tareas para no mezclarlo con reportes
  financieros.
- Tareas no sustituyen `habitaciones.estado` ni `mantenimientos_habitaciones`.
- Cualquier accion de asignar/iniciar/completar/cancelar desde el reporte queda fuera del
  contrato.

## Decision TLM-I-A reporte operativo read-only

- La ruta vive en `/tareas/reporte` para mantener contexto operativo y evitar mezclar con
  reportes financieros.
- El reporte reutiliza guardas TLM existentes: sesion, hotel actual, modulo `habitaciones`
  y permiso `habitaciones.view`.
- No se agrega filtro visible de hotel; el hotel sale del contexto de sesion.
- El reporte no ofrece acciones de tarea para mantener separacion con el detalle operativo.

## Decision TLM-I-F cierre reporte operativo

- El reporte operativo read-only queda cerrado antes de abrir automatizaciones.
- QA manual queda diferida por instruccion del usuario, no sustituida por los checkers.
- El siguiente bloque debe empezar con contrato independiente para evitar alcance
  implicito sobre habitaciones, limpieza, mantenimiento o Personal.

## Decision OP-0

- El siguiente bloque seguro se define primero como contrato de tablero operativo diario
  read-only antes de escribir codigo.
- OP-A, si se autoriza, debe observar datos existentes y no operar sobre ellos.
- El tablero no sera fuente de verdad; solo consolidara fuentes modernas ya existentes
  filtradas por `hotel_id`.
- Cualquier accion futura sobre tareas, habitaciones, reservaciones, Caja, pagos, abonos,
  nomina, offline o `/api/sync` requiere contrato separado.

## Decision OP-A

- El tablero operativo se implementa como modulo de observacion bajo `dashboard`, no como
  pantalla de acciones.
- Se crea `OperacionDiaria` para no reutilizar `DashboardController`, porque el dashboard
  existente mezcla metricas financieras de Caja.
- No se agregan filtros visibles de hotel: el hotel sale del contexto de sesion.
- Los documentos se muestran solo como metadata segura y enlaces al detalle autenticado.

## Decision OP-F

- El tablero operativo read-only se cierra antes de abrir automatizaciones.
- QA manual queda diferida por instruccion del usuario, no sustituida por los checkers.
- El siguiente bloque debe tener contrato propio para evitar que el tablero derive en
  acciones sobre reservaciones, habitaciones, tareas o finanzas.

## Decision MANT-A

- No se crea una pantalla nueva porque ya existe `GET /reportes/mantenimiento`.
- MANT-A se limita a estabilizar esa ruta existente y corregir el scope multihotel de la
  familia activa de consultas.
- La correccion se aplica solo a los metodos usados por
  `ReportesController::mantenimientoAction()`.
- El reporte conserva su formulario GET de filtros y no agrega POST ni acciones.
- `preflight_reporte_mantenimiento.php` y el health general quedan como guardrails para
  detectar lecturas sin `hotel_id`, rutas POST accidentales o datos inconsistentes.
- Caja, pagos, abonos, nomina, offline y `/api/sync` siguen fuera de alcance.

## Decision MANT-F

- El reporte de mantenimiento read-only se cierra antes de abrir acciones operativas.
- QA manual queda diferida por instruccion del usuario, no sustituida por los checkers.
- Cualquier accion futura de crear/iniciar/completar/cancelar mantenimiento debe tener
  contrato separado y no puede inferirse desde este cierre.

## Decision MANT-B

- Se endurece la accion existente `POST /habitaciones/{id}/mantenimiento` en lugar de
  crear un flujo nuevo.
- No se cambian actions ni names de los formularios existentes.
- La validacion de tipo/prioridad se centraliza contra el catalogo de `Mantenimiento`.
- El bloqueo de duplicados se hace antes del `INSERT` para evitar registros `en_proceso`
  paralelos por habitacion/hotel.
- No se automatiza mantenimiento programado ni disponibilidad fuera del flujo existente.

## Decision MANT-B-F

- MANT-B se cierra tecnicamente antes de automatizar mantenimiento programado.
- QA manual queda diferida por instruccion del usuario, no sustituida por los checkers.
- El warning de una habitacion en mantenimiento sin registro activo queda como historico
  conocido y no autoriza correccion SQL automatica.

## Decision MANT-C-0

- El mantenimiento programado se separa de mantenimiento inmediato porque ya afecta
  disponibilidad futura y conflictos de reservaciones.
- `mantenimientos_habitaciones` sigue siendo fuente de verdad para mantenimiento
  programado; `tareas_operativas` solo aporta contexto y no sustituye esa tabla.
- `activarMantenimientosPendientes()` queda expresamente fuera de MANT-C-A hasta tener
  contrato propio, porque escribe en mantenimientos y habitaciones.
- La siguiente implementacion segura debe reforzar validaciones y pertenencia por hotel
  en rutas existentes, sin crear automatizaciones.

## Decision MANT-C-A

- Se refuerzan las rutas existentes en vez de crear un modulo nuevo.
- El bloqueo de solapes vive en `Mantenimiento::tieneProgramadoSolapado()` para no
  depender solo del controlador o de la vista.
- La cancelacion conserva el flujo actual, pero exige que la habitacion asociada sea
  visible en el hotel actual.
- No se toca la activacion automatica de mantenimientos vencidos porque cambia estado de
  habitaciones y requiere contrato propio.

## Decision MANT-C-F

- MANT-C se cierra tecnicamente antes de cualquier automatizacion.
- QA manual queda diferida por instruccion del usuario, no sustituida por los checkers.
- La activacion automatica de mantenimientos vencidos requiere contrato independiente.

## Decision MANT-D-0

- El siguiente paso no sera activacion, sino observabilidad read-only de candidatos.
- La ruta futura debe vivir bajo reportes para evitar convertir la ficha de habitacion en
  una pantalla de automatizacion.
- `activarMantenimientosPendientes()` queda congelado hasta una fase posterior con QA
  manual explicita.

## Decision MANT-D-A

- El preview vive en `/reportes/mantenimiento-programado` porque es observacion y no
  accion operativa.
- `Mantenimiento::previewProgramados()` centraliza el scope `hotel_id` y los motivos de
  bloqueo para evitar reglas duplicadas en la vista.
- La vista puede decir "candidato", pero no puede activar ni presentar POST.
- Las reservaciones solo se consultan como senal de conflicto; no se modifican.
- Cualquier automatizacion futura debe abrir contrato separado antes de llamar
  `activarMantenimientosPendientes()`.

## Decision MANT-D-F

- El bloque se cierra sin evolucionar a activacion porque el siguiente paso ya seria una
  escritura operativa sobre mantenimiento/habitaciones.
- La QA manual queda diferida, pero no bloquea el cierre tecnico del preview read-only.
- La activacion manual o automatica debe iniciar con contrato independiente y pruebas
  manuales explicitas.
