# Fuentes de verdad - Medisoft Hoteles

## Inventario

Fuente moderna:

- `inventario_productos`
- `movimientos_inventario`

Legacy congelado:

- `productos`
- `inventario_movimientos`
- `inventario_habitacion_config`

Regla:

- No escribir en tablas legacy salvo compatibilidad estrictamente necesaria.
- No borrar ni fusionar tablas legacy sin una fase explicita de reconciliacion.

## Cuentas por pagar

Fuente nueva fundacional:

- `cuentas_por_pagar`
- `cuentas_por_pagar_movimientos`

Estado:

- fundacion read-only hasta Fase 3B;
- Reanclaje Fase 3C: estado formal `FASE_3C_VALIDADA_MANUALMENTE`;
- Fase 3C-A tiene preview read-only ruteado, protegido, navegable y verificado automaticamente;
- Fase 3C-A y 3C-B fueron validadas manualmente por el usuario;
- Fase 3C-B tiene generacion manual POST activa desde compras recibidas elegibles;
- Fase 3C-C queda completada tecnicamente como health/preflights read-only de consistencia CxP;
- sin pagos;
- sin Caja;
- sin generacion automatica desde compras;
- sin integracion con Caja.
- commit de cierre tecnico: `1fa1653`.

Regla vigente despues del reanclaje:

- La siguiente accion formal recomendada es cierre tecnico 3C si se autoriza explicitamente.
- No ampliar hacia pagos, Caja ni Fase 3D sin nueva autorizacion explicita.
- El preview 3C-A no es fuente de datos nueva; solo interpreta `compras` + `proveedores` + `cuentas_por_pagar`.
- Las CxP creadas en pruebas locales controladas (`id=1` compra `#5`, `id=2` compra `#2`) quedan como evidencia local; no borrar ni corregir automaticamente.
- `cuentas_por_pagar_movimientos` queda sin uso operativo.
- Los checkers 3C-C deben fallar si detectan CxP duplicada, sin compra/proveedor, con cruce de hotel, con total/saldo invalido, sin fecha de emision, con movimientos CxP/pagos/abonos o con referencia CxP en `movimientos_caja`.
- El preview solo debe enlazar a proveedor cuando el proveedor existe dentro del mismo `hotel_id`; si no, debe mostrar la compra bloqueada sin link a otro hotel.
- La auditoria de seguridad 3C es una capa de verificacion; no corrige datos automaticamente ni autoriza escrituras nuevas.
- Auditoria seguridad 3C post-3C-C completada: no hay hallazgos bloqueantes y no autoriza pagos, Caja ni Fase 3D.
- Cierre tecnico 3C completado: no cambia fuentes de verdad, no autoriza pagos/abonos/Caja y mantiene `cuentas_por_pagar_movimientos` sin uso operativo.
- QA manual final 3C reportada como OK por el usuario; no cambia fuentes de verdad ni autoriza funcionalidades nuevas.
- Cualquier integracion con Caja requiere nueva fase autorizada.
- Cualquier generacion automatica desde compras requiere nueva fase autorizada.
- Cualquier escritura futura debe validar que `proveedor_id`, `compra_id` y `hotel_id` pertenezcan al mismo hotel antes de persistir datos.

## Compras

Fuente operativa actual:

- `compras`
- `compra_detalles`
- `compras_recibidas`
- `movimientos_inventario`

Regla:

- La recepcion minima ya autorizada puede escribir inventario.
- No debe crear CxP automaticamente en Fase 3C.
- CxP solo podra generarse por accion manual posterior a la recepcion.

## Proveedores

Fuente actual:

- `proveedores`

Regla:

- La ficha de proveedor y su historial son read-only para compras recibidas.

## Centro Documental (Fase 4A)

Estado formal: `CIERRE_TECNICO_4A_COMPLETADO`.

Fuente fundacional creada:

- `documento_tipos`;
- `documentos`;
- `documento_entidades`.

Reglas:

- Existen como tablas base desde 4A-A.
- 4A-B permite consultar solo metadata segura y relaciones documentales por `hotel_id`.
- 4A-C permite crear metadata y archivo privado mediante carga manual segura con CSRF.
- La metadata operativa vive en `documentos` y `documento_entidades`; el binario vive en
  `STORAGE_PATH/documentos`, no en la base de datos.
- Los archivos privados deben vivir bajo `STORAGE_PATH/documentos`.
- `public_html/uploads` no debe ser fuente de documentos privados.
- `reporte_links` sigue siendo fuente especifica de reportes PDF y no debe fusionarse
  con Centro Documental en 4A base.
- Toda relacion documental debe validar `hotel_id` de documento y entidad.
- `storage_path` apunta a almacenamiento privado futuro; no debe usarse como URL publica.
- La relacion `documento_entidades` es polimorfica; la validacion de pertenencia a hotel
  de proveedor/compra/CxP/huesped/reservacion debe vivir en modelo/servicio.
- Conteos iniciales post-migracion: `documento_tipos=0`, `documentos=0`,
  `documento_entidades=0`.
- Conteos post-prueba 4A-C local: `documento_tipos=0`, `documentos=1`,
  `documento_entidades=1`.
- Documento de prueba 4A-C: `documentos.id=1`, hotel `1`, vinculado a proveedor `8`.
- Hotfix post-QA 4A-C: `documento_tipos=6` con tipos globales Contrato, Comprobante,
  Identificacion, Factura, Evidencia y Otro.
- Documento de prueba del hotfix: `documentos.id=2`, hotel `1`, tipo Contrato, sin
  vinculo inicial.
- Las vistas read-only no deben mostrar `storage_path`, `nombre_archivo` ni rutas internas.
- Las rutas documentales de consulta son GET: `/documentos`, `/documentos/{id}` y
  `/documentos/entidad/{tipo}/{id}`.
- `/documentos/entidad/{tipo}/{id}` debe validar que la entidad exista y pertenezca al
  hotel actual antes de mostrar documentos o permitir carga contextual.
- Las rutas documentales de carga son: `GET /documentos/subir` y
  `POST /documentos/subir`.
- No hay descarga, edicion ni borrado en 4A-C.
- Auditoria seguridad 4A confirma que PWA/offline y `/api/sync` no participan en Centro
  Documental base.
- `src/storage/documentos/` queda fuera de Git; no se debe versionar storage runtime.
- No Caja, pagos, abonos, Fase 3D ni `/api/sync`.

## Descarga segura documental (Fase 4B)

Estado formal: `CIERRE_TECNICO_4B_COMPLETADO`.

Contrato:

- `docs/fase_4B_0_contrato_descarga_segura_documentos.md`.

Reglas de fuente de verdad:

- La metadata autorizada vive en `documentos`.
- El archivo fisico vive bajo `STORAGE_PATH/documentos`.
- La descarga futura debe buscar por `documentos.id + documentos.hotel_id`.
- `storage_path` es path privado relativo, no URL publica.
- La ruta fisica solo es valida si `realpath` queda dentro de
  `realpath(STORAGE_PATH . '/documentos')`.
- No existen links publicos de documentos en el contrato 4B-0.
- 4B-A implementa descarga autenticada desde `documentos` y `STORAGE_PATH/documentos`.
- 4B-B registra trazabilidad en `logs_auditoria` mediante `AuditService`; la fuente de
  verdad del archivo sigue siendo `documentos.storage_path` bajo storage privado.
- `logs_auditoria` no sustituye metadata documental; solo registra eventos de acceso.
- 4B-C-A metadata: la metadata editable vive en `documentos` y `documento_tipos`;
  el archivo privado y su integridad siguen gobernados por `storage_path` y `sha256`.
- `logs_auditoria` registra `documentos.metadata_actualizada`, pero no sustituye la
  fila de `documentos` como fuente de verdad.
- La ruta activa de descarga es `GET /documentos/{id}/descargar`.
- Solo documentos `activo` del hotel actual son descargables.
- No hay links publicos ni tokens publicos de documentos.
- La edicion vigente solo modifica metadata segura en `documentos`; archivo fisico,
  `storage_path`, `nombre_archivo`, `sha256`, MIME, tamano, hotel y vinculos no se
  cambian en 4B-C-A.
- El cierre 4B no autoriza borrado, reemplazo de archivo, links publicos, pagos, Caja,
  abonos, Fase 3D, NP-A ni cambios en `/api/sync`.

## Documentos por entidad (Fase 4C)

Estado formal: `DOCUMENTOS_ENTIDAD_CONTEXTUAL_4C_VALIDADA_MANUALMENTE`.

Fuente de verdad:

- La relacion documento-entidad vive en `documento_entidades`.
- La metadata vive en `documentos`.
- El archivo fisico sigue bajo `STORAGE_PATH/documentos`.
- La entidad vinculada debe existir y pertenecer al mismo `hotel_id`.
- Entidades iniciales: `proveedor`, `compra`, `cuenta_por_pagar`, `huesped`,
  `reservacion`.
- `documento_entidades` no reemplaza datos de negocio de proveedores, compras, CxP,
  huespedes ni reservaciones; solo registra vinculos documentales.
- 4C-A muestra metadata vinculada en fichas operativas mediante
  `Documento::documentosPorEntidad()` y `src/app/views/partials/documentos_entidad.php`.
- Las fichas no son fuente de verdad documental; solo consumen metadata ya registrada.
- No hay links publicos ni permisos nuevos en 4C-A.
- La accion `Vincular documento` apunta a la carga contextual existente; la fuente de
  verdad sigue siendo `documentos` + `documento_entidades`.
- QA manual post-hotfix confirmo el flujo contextual de vinculacion documental.
- No hay edicion, borrado ni reemplazo de archivo desde fichas de entidad.

## Archivado documental (Fase 4D)

Estado formal: `FASE_4D_A_VALIDADA_MANUALMENTE`.

Fuente de verdad:

- El estado documental vive en `documentos.estado`.
- Estados validos actuales: `activo`, `archivado`, `eliminado`.
- La metadata y archivo fisico siguen en `documentos` + `STORAGE_PATH/documentos`.
- Las relaciones se mantienen en `documento_entidades`.
- Archivar o eliminar logicamente no debe borrar `documentos`, `documento_entidades`
  ni archivos fisicos.
- Cualquier cambio futuro de estado debe auditarse en `logs_auditoria`.
- 4D-A cambia estado solo con `Documento::actualizarEstado()` y auditoria
  `documentos.estado_actualizado`.
- La revision tecnica/auditoria de cierre confirma que 4D-A solo permite
  `activo <-> archivado`; `eliminado` sigue fuera de alcance.
- QA manual de archivar/restaurar reportada por el usuario como correcta.
- 4D-B-0 documenta que una baja logica futura debera usar `documentos.estado =
  eliminado`, sin borrar archivo fisico ni relaciones.
- 4D-B-A implementa esa baja logica con `Documento::actualizarEstado()` y auditoria
  `documentos.estado_actualizado`.
- La recuperacion desde `eliminado` no es fuente de verdad vigente y requerira contrato
  separado si algun dia se autoriza.
- No hay Caja, pagos, abonos, Fase 3D, NP-A ni `/api/sync` en 4D-A/4D-B-A.

## Personal y Nomina (Fase NP)

Fuente nueva e independiente (modulo de trabajadores):

- `trabajadores`
- `trabajador_pagos`
- `trabajador_anticipos`
- `trabajador_prestamos`
- `trabajador_asistencias`
- `trabajador_documentos`

Estado:

- Fase NP-0 define contrato, diagnostico y diseno aditivo de las 6 tablas.
- Fase NP-A crea las seis tablas en la base `medisoft_hoteles_import`; actualmente estan
  vacias.
- Fase NP-A UI read-first consume esas tablas en modo lectura mediante
  `Trabajador::listarPorHotel()` y `Trabajador::buscarPorIdHotel()`.
- Fase NP-B-0 define que el CRUD futuro escribira solo en `trabajadores` y mantendra
  el ledger laboral intacto.
- Fase NP-B-A ya escribe solo en `trabajadores` para crear/actualizar/baja/reactivar.
- Migracion fuente: `migrations/20260616_001_fase_np_a_personal_base.sql`.
- Backup previo valido: `src/storage/backups/phase_np_a_20260616_021311_before_personal_base_medisoft_hoteles_import.sql`.
- No hay categoria Nomina ni movimientos de Caja generados por NP-A.
- El bloque NP es un modulo financiero-laboral INDEPENDIENTE: ledger laboral, saldos por
  persona, asistencia y comisiones, multi-hotel.
- Sin integracion con Caja, sin movimientos de Caja, sin salida real de dinero en este bloque.

Reglas:

- El "trabajador" es una entidad independiente: NO requiere usuario del sistema ni login.
- El vinculo opcional a un `usuario` es por `trabajadores.usuario_id` con `ON DELETE SET NULL`;
  nunca se altera `usuarios` de forma destructiva ni se fusionan usuarios en trabajadores.
- Un "pago a trabajador" es un REGISTRO LABORAL que afecta el saldo del trabajador, NO un
  movimiento de Caja.
- El saldo por trabajador es DERIVADO del ledger (`trabajador_pagos`, `trabajador_anticipos`,
  `trabajador_prestamos`); no es editable manualmente.
- La UI NP-A solo muestra metadata y agregados; no crea ni corrige saldos.
- Toda escritura valida `hotel_id` y `trabajador_id` del mismo hotel antes de persistir.
- La baja de trabajador debe persistirse como `trabajadores.estado = 'baja'`, no como
  borrado fisico.
- `logs_auditoria` registra cambios de trabajador, pero no sustituye a `trabajadores`.
- Cualquier integracion con Caja o salida real de dinero requiere una Fase NP-Caja autorizada.
- La referencia trabajador-responsable de mantenimiento es logica/opcional y no altera
  `mantenimientos_habitaciones`.

## Sync

Fuente de verdad operativa:

- `/api/sync` sigue deshabilitado temporalmente.

Regla:

- Debe permanecer bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.
- No tocar PWA, service worker, IndexedDB, cache names ni archivos offline sin nuevo mensaje real explicito.

## Tareas, Limpieza y Mantenimiento (Fase TLM)

Estado formal: `ESTADOS_TLM_E_COMPLETADOS_QA_DIFERIDA`.

Fuentes actuales:

- Disponibilidad y estado operativo de habitacion: `habitaciones.estado`.
- Historial y programacion de mantenimiento de habitaciones: `mantenimientos_habitaciones`.
- Trabajadores asignables futuros: `trabajadores`.
- Notificaciones operativas: `notificaciones`.
- Tareas operativas futuras: `tareas_operativas`.
- Eventos tecnicos de tarea: `tarea_eventos`.

Reglas:

- Una tarea futura NO sustituye automaticamente `habitaciones.estado`.
- `mantenimientos_habitaciones` se conserva como fuente historica de mantenimiento.
- No se borra, renombra ni fusiona mantenimiento existente.
- Una asignacion futura a trabajador debe validar `trabajadores.hotel_id` y estado activo.
- Caja, pagos, abonos y nomina no son fuente de verdad de TLM.
- `/api/sync` queda fuera de alcance.
- TLM-A creo tablas vacias; a partir de TLM-C pueden existir tareas manuales.
- TLM-B solo lee `tareas_operativas` y `tarea_eventos`; no crea ni corrige datos.
- TLM-C crea tareas manuales solo en `tareas_operativas` y su evento inicial en
  `tarea_eventos`.
- `hotel_id` siempre viene del contexto de sesion; no se acepta desde formulario.
- La habitacion opcional debe pertenecer al mismo hotel.
- La tarea manual nace `pendiente` y `origen = manual`.
- TLM-C no cambia `habitaciones.estado` ni `mantenimientos_habitaciones`.
- TLM-D asigna tareas solo a `trabajadores` activos del mismo hotel.
- La asignacion de tarea NO crea asistencia, nomina, pago, abono ni Caja.
- La asignacion no inicia ni cierra tarea; solo deja `estado = asignada`.
- TLM-E cambia estados de tarea solo en `tareas_operativas` y registra eventos en
  `tarea_eventos`.
- TLM-E no convierte completar/cancelar una tarea en cambio de disponibilidad de
  habitacion.
- TLM-F muestra tareas contextuales desde habitacion/trabajador leyendo
  `tareas_operativas` por `hotel_id`; no crea nuevas fuentes de datos.
- La ficha de trabajador no es fuente de nomina: solo muestra tareas asignadas, sin
  asistencia, pagos, abonos ni Caja.
- TLM-G no crea datos; solo valida que `tareas_operativas` y `tarea_eventos` sigan siendo
  la fuente tecnica de tareas y eventos.
- `huespedes` no es fuente multihotel directa para TLM porque no tiene `hotel_id`; si una
  tarea necesita contexto de huesped, debe resolverse por reservacion u otra entidad con
  hotel.
- TLM-H cierra el bloque base sin cambiar fuentes de verdad: disponibilidad sigue en
  `habitaciones`, mantenimiento historico sigue en `mantenimientos_habitaciones` y tareas
  operativas siguen en `tareas_operativas`.

### NP-C ledger laboral

Estado formal: `CONTRATO_NP_C_LEDGER_LABORAL_COMPLETADO`.

- Fuente de trabajador: `trabajadores`.
- Fuente de conceptos laborales: `trabajador_pagos`.
- Fuente de anticipos: `trabajador_anticipos`.
- Fuente de prestamos: `trabajador_prestamos`.
- Fuente de asistencias: `trabajador_asistencias`.
- El saldo laboral es informativo y derivado; no es movimiento de Caja.
- Caja, cortes, movimientos, categoria Nomina y `/api/sync` no son fuente de verdad de
  NP-C.
- NP-C-A solo lee estas fuentes desde la ficha de trabajador; no crea ni corrige datos.
- NP-C-E solo valida consistencia; no corrige ni sustituye ninguna fuente.
- NP-C-F cierra el bloque read-only sin cambiar fuentes de verdad.
- NP-C-B-0 documenta la primera escritura controlada en `trabajador_pagos`.
- NP-C-B-A permite crear manualmente conceptos `comision`, `bono`, `descuento` y
  `ajuste` en `trabajador_pagos`.
- `trabajador_pagos` sigue siendo ledger laboral informativo, no Caja ni pago real.
- NP-C-C-0 documenta futura escritura en `trabajador_anticipos` y
  `trabajador_prestamos`, pero todavia no crea datos.
- NP-C-C-A permite crear manualmente anticipos y prestamos con saldo pendiente inicial
  igual al monto.
- Anticipos y prestamos siguen siendo ledger laboral informativo, no Caja ni pago real.
- NP-C-D-0 documenta futura escritura manual en `trabajador_asistencias`, pero todavia no
  crea datos.
- Asistencia no es nomina automatica ni Caja; es un registro laboral por trabajador/dia.
- NP-C-D-A permite crear manualmente una asistencia por trabajador/dia en
  `trabajador_asistencias`.
- `trabajador_asistencias` sigue siendo ledger laboral operativo; no calcula nomina, no
  descuenta ni crea pagos reales.
- NP-D-0 define que documentos laborales nuevos deben usar el Centro Documental moderno:
  `documentos`, `documento_entidades` y `documento_tipos`.
- La relacion con trabajadores debe vivir como `documento_entidades.entidad_tipo =
  'trabajador'`; `trabajador_documentos` queda congelada como tabla legacy/aditiva hasta
  reconciliacion autorizada.
- NP-D-A implementa esa relacion moderna en la ficha de trabajador; los conteos de
  documentos laborales deben preferir `documento_entidades` sobre `trabajador_documentos`.
- NP-E cierra el bloque Personal operativo base sin cambiar fuentes de verdad; el ledger
  laboral sigue en tablas `trabajador_*`, documentos laborales siguen en Centro
  Documental moderno y Caja no participa.
- NP-F-0 define un reporte futuro read-only; no crea fuente nueva. Debe derivar datos de
  `trabajadores`, tablas `trabajador_*`, Centro Documental moderno y `tareas_operativas`
  cuando aplique.
- NP-F-A implementa ese reporte sin crear fuente nueva: trabajadores en `trabajadores`,
  ledger en tablas `trabajador_*`, documentos en Centro Documental moderno y tareas en
  `tareas_operativas`; Caja no participa.
- NP-F-F cierra el reporte sin cambiar fuentes de verdad ni convertir saldos laborales en
  obligaciones pagables.

### TLM-I reporte operativo read-only

- TLM-I-0 no crea fuente nueva.
- El reporte futuro debe leer de `tareas_operativas` y `tarea_eventos`.
- `habitaciones`, `trabajadores` y `mantenimientos_habitaciones` son contexto de lectura,
  no destino de escritura desde el reporte.
- Caja, nomina, pagos y `/api/sync` no participan.
- TLM-I-A implementa ese reporte sin crear fuente nueva: tareas en
  `tareas_operativas`, eventos en `tarea_eventos` y contexto de lectura desde
  habitaciones/trabajadores/mantenimiento.
- TLM-I-F cierra el reporte sin cambiar fuentes de verdad ni automatizar disponibilidad,
  limpieza, mantenimiento, asistencia, Caja o nomina.

### Roadmap 5A Personal laboral basico

- 5A no crea fuente nueva adicional: queda cubierto por el bloque NP.
- Fuente principal: `trabajadores`.
- Fuentes relacionadas: `trabajador_pagos`, `trabajador_anticipos`,
  `trabajador_prestamos`, `trabajador_asistencias` y Centro Documental moderno para
  documentos laborales.
- No usar `usuarios` como sustituto de trabajadores; solo puede existir vinculo opcional.
- No crear otro conjunto de tablas para 5A.

### Roadmap 6B Integracion tareas + habitaciones

- Habitaciones: `habitaciones` sigue siendo fuente de estado/disponibilidad.
- Tareas: `tareas_operativas` sigue siendo fuente de tareas activas e historicas.
- Eventos: `tarea_eventos` sigue siendo fuente de auditoria tecnica de tareas.
- La integracion 6B no debe convertir tareas en fuente de disponibilidad.
- La creacion de tareas de limpieza sigue naciendo desde `/reportes/limpieza`, no desde
  la ficha de habitacion.

### OP-0 tablero operativo diario read-only

- Fuente de verdad: ninguna tabla nueva; el tablero futuro debe ser solo agregador.
- Ocupacion/reservaciones: `reservaciones`.
- Habitaciones: `habitaciones` y `tipos_habitacion`.
- Tareas: `tareas_operativas` y `tarea_eventos`.
- Mantenimiento historico: `mantenimientos_habitaciones` solo en lectura.
- Personal operativo: `trabajadores` solo en lectura.
- Documentos: `documentos` y `documento_entidades` solo como metadatos seguros.
- Restriccion: todas las consultas deben filtrar por `hotel_id` y no deben exponer rutas
  internas de storage.

### OP-A tablero operativo diario

- Implementacion: `OperacionDiaria` no crea fuente nueva.
- No lee `movimientos_caja` ni fuentes financieras.
- `documentos.storage_path` y `documentos.nombre_archivo` no son parte del reporte.
- La fuente del tablero sigue siendo agregada/read-only y todo debe venir del
  `hotel_id` actual.

### MANT-A reporte mantenimiento read-only

- No crea fuente nueva.
- Fuente de mantenimiento historico: `mantenimientos_habitaciones`.
- Fuente de habitaciones: `habitaciones`.
- Metadata de usuario: `usuarios`, solo para mostrar responsable/registro.
- El reporte existente `/reportes/mantenimiento` debe leer exclusivamente datos del
  `hotel_id` actual.
- Los joins con `habitaciones` deben validar que `mantenimientos_habitaciones.hotel_id`
  coincida con `habitaciones.hotel_id`.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-B mantenimiento inmediato existente

- No crea fuente nueva.
- La habitacion operativa sigue en `habitaciones`.
- El registro historico/inmediato de mantenimiento sigue en `mantenimientos_habitaciones`.
- Toda accion debe operar con `hotel_id` del contexto actual.
- Una habitacion no debe tener mas de un mantenimiento `en_proceso` por hotel.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-C mantenimiento programado

- No crea fuente nueva.
- La programacion vive en `mantenimientos_habitaciones` con `programado = 1`,
  `estado = 'programado'`, `fecha_programada` y `fecha_programada_fin`.
- Las habitaciones siguen en `habitaciones`.
- Las reservaciones y `reservacion_habitaciones` solo son fuente de validacion de
  conflictos, no destino de escritura.
- `Mantenimiento::activarMantenimientosPendientes()` no es fuente nueva y sigue
  desconectado hasta contrato posterior.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-D-A preview mantenimiento programado

- No crea fuente nueva.
- El preview es una lectura derivada de:
  - `mantenimientos_habitaciones` para programados vencidos/proximos;
  - `habitaciones` para estado actual y pertenencia por hotel;
  - `reservaciones` y `reservacion_habitaciones` para senales de conflicto.
- El candidato mostrado en la vista no cambia estados ni crea datos.
- La disponibilidad operativa no se recalcula ni se persiste en esta fase.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-E-A activacion manual mantenimiento programado

- No crea fuente nueva.
- La activacion manual escribe exclusivamente en fuentes existentes:
  - `mantenimientos_habitaciones` para pasar de `programado` a `en_proceso`;
  - `habitaciones` para pasar de `disponible` a `mantenimiento`;
  - `logs_auditoria` y `notificaciones` como trazabilidad no financiera.
- `reservaciones` y `reservacion_habitaciones` solo se leen para bloquear conflictos.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-G-0 tareas desde mantenimiento

- No crea fuente nueva.
- `mantenimientos_habitaciones` sigue siendo fuente de verdad de mantenimiento.
- `habitaciones` sigue siendo fuente de verdad de disponibilidad.
- `tareas_operativas` y `tarea_eventos` son seguimiento operativo.
- `tareas_operativas.mantenimiento_id` es enlace contextual, no autorizacion para cambiar
  estados de mantenimiento o habitacion.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-G-A tareas contextuales desde mantenimiento

- No crea fuente nueva.
- El preview lee:
  - `mantenimientos_habitaciones` para programados vencidos/proximos;
  - `habitaciones` para contexto de habitacion;
  - `tareas_operativas` para tareas vinculadas por `mantenimiento_id`;
  - `tarea_eventos` solo indirectamente desde el detalle de tarea existente.
- La presencia o ausencia de una tarea vinculada no modifica disponibilidad ni estado de
  mantenimiento.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-G-B-0 creacion manual de tarea desde mantenimiento

- No crea fuente nueva.
- La futura escritura permitida debera limitarse a:
  - `tareas_operativas` para la tarea vinculada;
  - `tarea_eventos` para el evento inicial;
  - `logs_auditoria` como trazabilidad no financiera.
- `habitaciones` y `mantenimientos_habitaciones` seguiran siendo fuentes de verdad y no
  deben cambiar por crear la tarea.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-G-B-A creacion manual de tarea desde mantenimiento

- No crea fuente nueva.
- Escrituras permitidas:
  - `tareas_operativas`;
  - `tarea_eventos`;
  - `logs_auditoria` como trazabilidad.
- Lecturas de validacion:
  - `mantenimientos_habitaciones`;
  - `habitaciones`.
- `habitaciones` y `mantenimientos_habitaciones` no se actualizan.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### MANT-G-F cierre tecnico tareas desde mantenimiento

- Fuente de verdad documental: `docs/fase_MANT_G_F_cierre_tareas_mantenimiento.md`.
- Fuente de verdad tecnica del bloque:
  - `TareaOperativa` para lectura/creacion centralizada;
  - `TareaController` para guardas HTTP, CSRF y auditoria;
  - `ReportesController::mantenimientoProgramadoAction()` para preview read-only;
  - `tools/saas/preflight_tareas_operativas.php`;
  - `tools/saas/preflight_mantenimiento_operativo.php`;
  - `tools/saas/health_check_fase_1a.php`.
- No hay fuente de verdad en Caja, pagos, abonos, nomina, offline ni `/api/sync`.

### LIM-0 limpieza operativa

- Fuente de verdad documental: `docs/fase_LIM_0_contrato_limpieza_operativa.md`.
- Fuente de verdad operativa:
  - `habitaciones.estado` para disponibilidad fisica;
  - `reservaciones` y `reservacion_habitaciones` para checkout;
  - `tareas_operativas` y `tarea_eventos` solo como seguimiento.
- `movimientos_caja`, pagos, abonos, nomina, offline y `/api/sync` no participan.

### LIM-A reporte limpieza read-only

- Fuente de verdad documental: `docs/fase_LIM_A_reporte_limpieza_readonly.md`.
- Fuente tecnica de consulta: `ReportesController::reporteLimpiezaOperativa()`.
- Fuente visual: `app/views/reportes/limpieza-operativa.php`.
- Fuente de verificacion: `tools/saas/preflight_limpieza_operativa.php`.
- `habitaciones.estado` sigue siendo la autoridad de disponibilidad.
- `tareas_operativas` solo aporta contexto operativo.

### LIM-F cierre tecnico limpieza read-only

- Fuente de verdad documental: `docs/fase_LIM_F_cierre_limpieza_readonly.md`.
- Fuente tecnica vigente: LIM-A, sin POST ni escrituras.
- No hay fuente de verdad en inventario automatico, Caja, pagos, abonos, nomina, offline
  ni `/api/sync`.

### LIM-B-0 creacion manual de tarea de limpieza

- No crea fuente nueva.
- La futura escritura permitida debera limitarse a:
  - `tareas_operativas`;
  - `tarea_eventos`;
  - `logs_auditoria`.
- `habitaciones.estado` seguira siendo la fuente de verdad de disponibilidad.
- Inventario automatico, Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### LIM-B-A creacion manual de tarea de limpieza

- No crea fuente nueva.
- Escrituras permitidas:
  - `tareas_operativas`;
  - `tarea_eventos`;
  - `logs_auditoria`.
- Lecturas de validacion:
  - `habitaciones`.
- `habitaciones.estado` no se actualiza y sigue siendo autoridad de disponibilidad.
- Inventario automatico, Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### LIM-B-F cierre tecnico tareas desde limpieza

- Estado formal: `BLOQUE_LIM_B_TAREAS_DESDE_LIMPIEZA_CERRADO_QA_DIFERIDA`.
- Fuente de verdad documental: `docs/fase_LIM_B_F_cierre_tareas_limpieza.md`.
- Fuentes tecnicas cerradas:
  - `tareas_operativas`;
  - `tarea_eventos`;
  - `logs_auditoria`.
- `habitaciones.estado = limpieza` es condicion de entrada, no resultado modificado
  por LIM-B-A.
- No hay migraciones ni nuevas tablas.

### TLM-J-0 agenda de tareas por trabajador

- Fuente de verdad documental:
  `docs/fase_TLM_J_0_contrato_agenda_tareas_trabajador.md`.
- Fuentes tecnicas futuras de lectura:
  - `tareas_operativas`;
  - `trabajadores`;
  - `habitaciones`;
  - `mantenimientos_habitaciones`.
- No crea fuentes nuevas.
- Caja, pagos, abonos, nomina, offline y `/api/sync` no participan.

### TLM-J-A agenda de tareas por trabajador

- Fuente de verdad documental: `docs/fase_TLM_J_A_agenda_tareas_trabajador.md`.
- Fuente tecnica de consulta: `TareaOperativa::agendaReadOnlyPorHotel()`.
- Fuente visual: `app/views/tareas/agenda.php`.
- Ruta: `GET /tareas/agenda`.
- Fuentes de lectura:
  - `tareas_operativas`;
  - `trabajadores`;
  - `habitaciones`;
  - `mantenimientos_habitaciones`.
- No crea fuentes nuevas ni escrituras.

### TLM-J-F cierre tecnico agenda de tareas

- Fuente de verdad documental: `docs/fase_TLM_J_F_cierre_agenda_tareas.md`.
- Estado formal: `BLOQUE_TLM_J_AGENDA_TAREAS_CERRADO_QA_DIFERIDA`.
- Fuentes tecnicas vigentes: las mismas de TLM-J-A.
- No hay migraciones, nuevas tablas ni cambios de datos.

### 6B-A indicadores read-only de tareas en habitaciones

- Fuente de verdad documental: `docs/fase_6B_A_indicadores_tareas_habitaciones.md`.
- Disponibilidad fisica: `habitaciones.estado`.
- Tareas activas: `tareas_operativas` filtrada por `hotel_id`, `habitacion_id` y
  estados `pendiente`, `asignada`, `en_proceso`.
- Historial tecnico: `tarea_eventos`.
- La existencia de tareas activas no cambia automaticamente disponibilidad ni estado de
  habitacion.
- La creacion manual de tareas de limpieza conserva como superficie `/reportes/limpieza`.

### 6C-0 evidencias/documentos en tareas

- Fuente de verdad documental: `docs/fase_6C_0_contrato_evidencias_documentos_tareas.md`.
- Tareas: `tareas_operativas`.
- Eventos de tarea: `tarea_eventos`.
- Documentos: `documentos`.
- Vinculos documentales: `documento_entidades`.
- Tipos documentales: `documento_tipos`.
- `documento_entidades` no es fuente de estado de tarea; solo relaciona documentos con
  entidades del hotel.
- La entidad documental futura `tarea` debe validarse por `tareas_operativas.id` +
  `hotel_id`.

### 6C-A documentos read-only en tareas

- Fuente de verdad documental: `docs/fase_6C_A_documentos_readonly_tareas.md`.
- Consulta tecnica: `Documento::documentosPorTareaHotel()`.
- La vista de tarea solo lee documentos vinculados como `entidad_tipo = tarea`.
- `documento_entidades` conserva el vinculo, pero no modifica ni reemplaza
  `tareas_operativas`.
- Upload contextual a tarea sigue diferido; `Documento::ENTIDAD_TIPOS` aun no registra
  `tarea` como entidad permitida para carga.
## Fase 6C-B - Documentos en tareas

- Fuente de tareas: `tareas_operativas`.
- Fuente de documentos: `documentos`.
- Fuente de vinculos: `documento_entidades`.
- Entidad documental habilitada: `tarea`.
- Regla multihotel: `documento_entidades.hotel_id` debe coincidir con
  `tareas_operativas.hotel_id`.
- Storage privado: se mantiene el Centro Documental existente; no se usa
  `public_html/uploads` como fuente de verdad documental.

## Fase 5B - Asistencia laboral basica

- Fuente de asistencia: `trabajador_asistencias`.
- Fuente de trabajador: `trabajadores`.
- Regla multihotel: `trabajador_asistencias.hotel_id` debe coincidir con
  `trabajadores.hotel_id`.
- Captura vigente: `POST /trabajadores/{id}/asistencias`.
- No hay fuente de verdad en Caja, pagos ni nomina para esta fase.

## Fase 5C - Anticipos/prestamos/saldos laborales

- Fuente de anticipos: `trabajador_anticipos`.
- Fuente de prestamos: `trabajador_prestamos`.
- Fuente de saldo informativo: calculo en `Trabajador` desde saldos pendientes.
- Regla multihotel: `hotel_id` de anticipos/prestamos debe coincidir con
  `trabajadores.hotel_id`.
- Caja no es fuente de verdad en esta fase.
- No hay fuente de pagos reales ni abonos laborales todavia.

## Fase 5D-0 - Pagos laborales sin Caja

- `trabajador_pagos` sigue siendo fuente de conceptos laborales, no de pagos reales.
- No existe fuente de verdad de pagos laborales reales en 5D-0.
- Caja no es fuente de verdad para Personal en esta fase.
- Una fase futura debe definir tabla/entidad independiente antes de registrar pagos.

## Fase 7A-0 - Cuentas por cobrar read-only

- No existe fuente operativa `cuentas_por_cobrar`.
- Fuentes candidatas read-only:
  - `reservaciones`
  - `reservacion_pagos`
  - `reservacion_abonos`
  - `solicitudes_factura`
- Caja no es fuente de verdad para 7A.
- Una CxC operativa futura debe definirse en contrato separado.

## Fase 7A-A - Reporte CxC read-only

- Fuente principal de contexto: `reservaciones` filtrada por `hotel_id`.
- Huesped se lee desde `huespedes` como dato descriptivo asociado a la reservacion.
- Pagos existentes se leen desde `reservacion_pagos` agrupados por
  `hotel_id + reservacion_id`.
- Abonos existentes se leen desde `reservacion_abonos` agrupados por
  `hotel_id + reservacion_id`.
- Facturacion existente se lee desde `solicitudes_factura` agrupada por
  `hotel_id + reservacion_id`.
- El saldo mostrado es estimado y derivado; no es una cuenta contable nueva.
- Caja no es fuente de verdad para este reporte.
- No existe fuente operativa de cobros CxC en 7A-A.

## Fase 7B-0 - CxC operativa futura

- No existe aun fuente de verdad operativa de CxC.
- La fuente futura propuesta debe ser una tabla nueva `cuentas_por_cobrar` si se
  autoriza migracion aditiva.
- La tabla futura no debe reemplazar `reservaciones`, `reservacion_pagos`,
  `reservacion_abonos` ni `solicitudes_factura` sin reconciliacion.
- Caja no es fuente de verdad para 7B-0.
- `movimientos_caja` queda solo como dato historico sensible, no como destino
  automatico.
