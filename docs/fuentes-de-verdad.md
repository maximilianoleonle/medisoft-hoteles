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
- Cualquier integracion con Caja o salida real de dinero requiere una Fase NP-Caja autorizada.
- La referencia trabajador-responsable de mantenimiento es logica/opcional y no altera
  `mantenimientos_habitaciones`.

## Sync

Fuente de verdad operativa:

- `/api/sync` sigue deshabilitado temporalmente.

Regla:

- Debe permanecer bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.
- No tocar PWA, service worker, IndexedDB, cache names ni archivos offline sin nuevo mensaje real explicito.
