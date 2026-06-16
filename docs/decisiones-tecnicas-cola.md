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

- Estado formal vigente: `ARCHIVADO_DOCUMENTAL_4D_A_COMPLETADO_QA_MANUAL_PENDIENTE`.
- Se implementa solo `activo <-> archivado`; `eliminado` queda fuera para evitar baja
  logica irreversible o ambigua.
- Las acciones viven en el detalle documental, no en listados masivos.
- Se usa POST + CSRF y confirmacion del navegador para reducir clic accidental.
- La vista no acepta `estado` libre; el controlador decide el estado objetivo y el
  modelo valida la transicion.
- La auditoria usa `documentos.estado_actualizado` con estado antes/despues seguro.

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

### Decision sobre "responsable" de mantenimiento

- En NP la referencia trabajador-responsable es **logica y opcional**, de solo lectura,
  basada en el campo de texto libre `mantenimientos_habitaciones.realizado_por`.
- NO se altera `mantenimientos_habitaciones`. Una columna FK real se difiere a una
  migracion aditiva posterior autorizada.

### Decisiones de seguridad/operacion NP

- Auditoria con `AuditService::record()` si la tabla `logs_auditoria` esta disponible.
- Escrituras explicitas (POST + CSRF + transaccion cuando el patron lo permita).
- `/api/sync` fuera de alcance.
