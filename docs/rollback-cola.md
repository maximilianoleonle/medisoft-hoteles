# Rollback - cola autonoma

## Rollback por fase

### Fase 2X

- Commit: `d1f1431 feat: add read-only received purchase detail`.
- Rollback: revertir el commit si el detalle read-only de compra recibida causa regresion.
- DB: no requiere rollback de datos.
- Validacion posterior: `php -l`, rutas de compras y preflights.

### Fase 2Y

- Commit: `32abb7b feat: add read-only received purchases reports`.
- Rollback: revertir el commit si el reporte read-only genera errores.
- DB: no requiere rollback de datos.
- Validacion posterior: reporte `/compras/reportes/recibidas`, `php -l` y preflights.

### Fase 2Z

- Commit: `052fd7a fix: harden minimal purchase receiving flow`.
- Rollback: revertir solo si la guarda impide recepcion valida; revisar antes porque protege doble recepcion.
- DB: no borrar movimientos. Si hubo recepcion real, tratar como dato operativo y no revertir por SQL sin autorizacion.
- Validacion posterior: prueba anti doble recepcion y consistencia `compra_detalles.movimiento_inventario_id`.

### Fase 3A

- Commit: `3d8f997 feat: expand supplier profile and purchase history`.
- Rollback: revertir si la ficha read-only de proveedor genera error de vista/controlador.
- DB: no requiere rollback de datos.
- Validacion posterior: `/proveedores/{id}` autenticado y sin sesion.

### Fase 3B draft

- Commit: `673f47f docs: draft accounts payable foundation`.
- Rollback: revertir documentacion/draft si se descarta el diseno CxP.
- DB: no aplica para el draft.

## Fase 3B aplicada

Commit de cierre:

- `1fa1653 feat(phase-3b): add read-only accounts payable foundation`

### Codigo

Rollback seguro de codigo:

1. Revertir el commit de Fase 3B.
2. Verificar que desaparezcan rutas/vistas/modelo/controlador de CxP.
3. Ejecutar `php -l` sobre archivos afectados si el revert genera cambios.
4. Ejecutar health/preflight para confirmar que el sistema vuelve al estado anterior.

### Base de datos

La migracion ya aplicada creo tablas nuevas vacias:

- `cuentas_por_pagar`;
- `cuentas_por_pagar_movimientos`.

No borrar tablas ni datos sin autorizacion explicita.

Si se requiere rollback de DB:

1. Confirmar backup existente:
   - `src/storage/backups/phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql`
   - SHA256: `24663D206AE15B86B001708D8BC2665541548443A0EA363548CAFC3FDF3A4D2C`
2. Solicitar autorizacion explicita antes de cualquier `DROP`, `DELETE` o restauracion.
3. Documentar conteos antes y despues.
4. No tocar Caja, cortes ni movimientos.

### Riesgo de datos

Bajo para datos operativos actuales porque:

- las tablas CxP estan vacias;
- no se generaron pagos;
- no se genero saldo automatico desde compras;
- no se integraron movimientos de Caja.

### Auditoria de seguridad post-cierre

- Perdida de datos: sin indicios; la fase solo agrego tablas nuevas vacias y codigo read-only.
- Caja: sin integracion CxP; no hay movimientos de Caja relacionados con CxP.
- Doble recepcion: protegida por estado, `fecha_recepcion`, bloqueo transaccional y `movimiento_inventario_id`.
- Migracion: idempotente para estructura base mediante `CREATE TABLE IF NOT EXISTS` y registro con `ON DUPLICATE KEY UPDATE`.
- Riesgo residual futuro: si una fase posterior escribe CxP, debe validar que proveedor y compra pertenezcan al mismo `hotel_id` antes de insertar cualquier saldo.

### No hacer sin autorizacion

- `DROP TABLE cuentas_por_pagar`;
- `DROP TABLE cuentas_por_pagar_movimientos`;
- borrar registros de `migrations`;
- restaurar dump completo encima de datos vivos;
- reset destructivo de Git;
- push.

## Cambio no relacionado pendiente

`src/app/views/reservaciones/ver.php` tuvo un ajuste visual fuera del commit de Fase 3B y quedo commiteado en `dc3c150`.

Rollback de ese cambio, si el usuario lo autoriza despues:

1. Revisar diff actual.
2. Si se decide descartar, revertir solo ese archivo con un comando no destructivo y explicito.
3. Si se decide conservar, hacer commit separado despues de `php -l` y prueba visual del modal.

No mezclar este archivo con rollback de CxP.

## Fase 3C

### Reanclaje de estado

- Estado formal vigente: `FASE_3C_VALIDADA_MANUALMENTE`.
- 3C-A esta implementada como GET read-only; 3C-B esta implementada como POST manual controlado; 3C-C queda implementada como checkers read-only.
- No borrar codigo ni datos automaticamente.
- No hacer rollback destructivo de la CxP historica creada por prueba local sin nueva autorizacion y backup.
- Antes de tocar pagos, Caja o Fase 3D, recibir autorizacion explicita de una fase nueva y hacer backup si hubiera escritura de datos.

### 3C-0 contrato y diagnostico

- Rollback: revertir el commit documental si se descarta el diseno.
- DB: no aplica; no hay escrituras.

### 3C-A simulador read-only

- Rollback: revertir commit de simulador (`feat(phase-3c): add payable generation preview` cuando exista).
- DB: no aplica; debe ser solo lectura.
- Archivos esperados:
  - `src/config/routes.php`;
  - `src/app/controllers/CuentaPorPagarController.php`;
  - `src/app/models/CuentaPorPagar.php`;
  - `src/app/views/cuentas_por_pagar/index.php`;
  - `src/app/views/cuentas_por_pagar/generacion_preview.php`;
  - checkers/preflights y documentacion.
- Validacion posterior: `php -l`, checkers/preflights, conteos CxP antes/despues sin cambios.

### 3C-B generacion manual

- Estado vigente: implementada tecnicamente y validada manualmente por el usuario.
- Rollback de codigo: revertir el commit `feat(phase-3c): generate payable from received purchase` si causa regresion.
- Rollback de datos: no borrar CxP sin autorizacion explicita.
- Si se crean CxP reales, primero exportar conteos y filas afectadas.
- No tocar Caja, cortes ni movimientos.
- Si hay que anular datos, requerir autorizacion y documentar si se marca estado o se restaura backup.
- Backup requerido antes de prueba local de escritura.
- Validaciones posteriores:
  - una sola CxP por compra;
  - cero movimientos de Caja nuevos;
  - doble generacion bloqueada;
  - auditoria registrada si `logs_auditoria` esta disponible.

Backup valido usado antes de la prueba local:

- `src/storage/backups/phase3c_b_20260615_144908_before_manual_cxp_medisoft_hoteles_import.sql`
- SHA256: `C0403F7B5ACBDA35EF4C05E2840546D5D9978802A21C9736BEBC6FF462518061`
- tamano: `1532615`

Backup historico usado antes de la primera prueba local:

- `src/storage/backups/phase3c_b_20260615_053711_before_manual_cxp_medisoft_hoteles_import_notablespaces.sql`
- SHA256: `8086F91DF17DB09CFBB28E7E12BED475FDD81FB538948F4B60141A90BE9E801D`
- tamano: `1528988`

Datos creados en pruebas locales:

- `cuentas_por_pagar.id = 1`
- `compra_id = 5`
- `hotel_id = 4`
- `total = saldo = 1000.00`
- `cuentas_por_pagar.id = 2`
- `compra_id = 2`
- `hotel_id = 4`
- `total = saldo = 1900.00`

Si se decide retirar ese dato de prueba, no hacer `DELETE` directo sin autorizacion; restaurar backup o acordar una estrategia de anulacion/reconciliacion.

### 3C-C validaciones, health y preflights

- Estado vigente: completada tecnicamente como verificacion read-only.
- Rollback de codigo/docs: revertir el commit `test(phase-3c): add payable consistency checks` si alguna regla genera falsos positivos bloqueantes.
- Rollback de datos: no aplica; la fase solo ejecuta consultas de lectura.
- No se crean nuevas rutas, vistas, pagos, abonos ni movimientos de Caja.
- Validaciones cubiertas: duplicados, compra/proveedor inexistente, `hotel_id`, cruce de hotel, compra no recibida, saldo/total, fecha de emision, movimientos CxP/pagos/abonos y referencias CxP en Caja.
- Validacion posterior:
  - `php -l` en health/preflights;
  - health checker;
  - preflight de compras minimas;
  - preflight de recepcion de compras;
  - SQL read-only de consistencia CxP;
  - `git diff --check`.
- Si alguna validacion detecta datos inconsistentes, no corregir con `UPDATE`/`DELETE` sin nueva autorizacion y backup.

### Revision tecnica post-QA Fase 3C

- Rollback de codigo/docs: revertir el commit `fix(review): stabilize phase 3c payable generation` si el ajuste visual no se desea.
- DB: no aplica; no hay migracion ni escritura de datos.
- Alcance del ajuste: el preview no enlaza a proveedor si el proveedor no fue resuelto dentro del mismo `hotel_id`.
- Validacion posterior:
  - `php -l` sobre la vista si PHP esta disponible;
  - health/preflights cuando Docker o PHP esten disponibles;
  - prueba visual del preview con compra elegible y compra bloqueada.
- No tocar Caja, pagos, abonos ni movimientos financieros.

### Auditoria seguridad post-QA Fase 3C

- Estado vigente: completada sin hallazgos bloqueantes.
- Rollback de documentacion: revertir el commit `docs(phase-3c): record payable generation security audit` si se quiere retirar la matriz de auditoria.
- DB: no aplica; auditoria documental/estatica sin escrituras.
- Codigo: no aplica si no hay cambios funcionales en el commit de auditoria.

### Cierre tecnico Fase 3C

- Estado vigente: `FASE_3C_VALIDADA_MANUALMENTE`.
- Rollback de documentacion: revertir el commit `docs(phase-3c): close controlled payable generation block` si se quiere retirar solo el cierre documental.
- DB: no aplica; cierre documental sin escrituras.
- Codigo: no aplica si no hay cambios funcionales en el commit de cierre.
- No revertir ni borrar CxP de prueba (`id=1`, `id=2`) sin autorizacion explicita, backup y estrategia de reconciliacion.
- No mezclar rollback 3C con cambios PWA/no relacionados.
- La validacion manual final no requiere rollback de datos; es documentacion de QA reportada por el usuario.

## Fase 4A Centro Documental

### 4A-0 contrato y diagnostico

- Estado vigente: `CONTRATO_4A_COMPLETADO`.
- Rollback: revertir el commit `docs(phase-4a): define document center foundation contract`.
- DB: no aplica; no se ejecutaron migraciones ni escrituras.
- Archivos: no aplica; no se crearon uploads ni rutas funcionales.
- No tocar Caja, pagos, abonos, Fase 3D ni `/api/sync`.

### 4A-A migracion base documental

- Estado vigente: `MIGRACION_4A_COMPLETADA`.
- Backup previo:
  `src/storage/backups/phase4a_20260615_182352_before_document_center_medisoft_hoteles_import.sql`.
- SHA256: `698A304F69B312EF06EABA787C83969629F2B14BCA096906CC08CADDC7D898F0`.
- Tamano: `1535817` bytes.
- Migracion: `migrations/20260615_004_fase_4a_centro_documental_base.sql`.
- Rollback de codigo: revertir el commit `feat(phase-4a): add document center base schema`.
- Rollback DB manual solo con autorizacion explicita y si las tablas estan vacias:
  1. `SELECT COUNT(*) FROM documento_entidades;`
  2. `SELECT COUNT(*) FROM documentos;`
  3. `SELECT COUNT(*) FROM documento_tipos;`
  4. si los tres conteos son `0`, ejecutar `DROP TABLE documento_entidades;`
  5. ejecutar `DROP TABLE documentos;`
  6. ejecutar `DROP TABLE documento_tipos;`
  7. borrar el registro de `migrations` para `20260615_004_fase_4a_centro_documental_base.sql`.
- Si alguna tabla tiene datos, no ejecutar `DROP` ni `DELETE`; exportar y decidir
  reconciliacion o restauracion desde backup.
- No borrar archivos de storage; 4A-A no crea archivos fisicos.

### 4A-B capa read-only documental

- Estado vigente: `DOCUMENTOS_READ_ONLY_4A_COMPLETADO`.
- Rollback de codigo: revertir el commit `feat(phase-4a): add read-only document center layer`.
- DB: no aplica; 4A-B no escribe datos ni crea migraciones.
- Archivos esperados del rollback:
  - `src/app/models/Documento.php`;
  - `src/app/controllers/DocumentoController.php`;
  - `src/app/views/documentos/index.php`;
  - `src/app/views/documentos/ver.php`;
  - rutas GET de `/documentos`;
  - entrada de sidebar `Documentos`.
- No tocar las tablas creadas por 4A-A durante rollback de 4A-B.
- Validar despues de rollback: `php -l` en rutas/sidebar si se modifican,
  health/preflights y ausencia de rutas `/documentos`.

### 4A-C upload seguro documental

- Estado vigente: `CIERRE_TECNICO_4A_COMPLETADO`.
- Backup previo:
  `src/storage/backups/phase4a_c_20260615_190912_before_document_upload_medisoft_hoteles_import.sql`.
- SHA256: `DF150F705824973621B9A1276980DC73ECB7AE5261B67A5D71E541FE13797446`.
- Tamano: `1541523` bytes.
- Rollback de codigo: revertir el commit `feat(phase-4a): add secure document upload`.
- Rollback DB/archivos: no borrar registros ni archivos sin autorizacion explicita.
- Prueba local controlada creada:
  - `documentos.id = 1`;
  - `documento_entidades.id = 1`;
  - storage privado:
    `src/storage/documentos/hotel_1/2026/06/doc_20260615_191313_37017248e4f9b6e2.pdf`.
- Si se decide retirar esa prueba local, opciones seguras:
  1. restaurar el backup completo si el entorno local puede volver al punto anterior;
  2. solicitar autorizacion explicita para una anulacion/reconciliacion puntual;
  3. documentar conteos antes/despues.
- No ejecutar `DELETE`, `DROP`, `unlink` ni limpieza masiva sin autorizacion nueva.
- `src/storage/documentos/` queda ignorado por Git porque es storage runtime privado.

### 4A-C hotfix tipos documentales y carga general

- Backup previo:
  `src/storage/backups/phase4a_fix_20260615_193644_before_document_upload_fixes_medisoft_hoteles_import.sql`.
- SHA256: `FC9E0F68106A7ED49EC2228EFF0452F24710BDEF8E435AE3DE24ACD0A013C673`.
- Migracion: `migrations/20260615_005_fase_4a_seed_documento_tipos.sql`.
- Rollback de codigo: revertir el commit del hotfix.
- Rollback DB solo con autorizacion explicita:
  1. revisar si algun documento real usa los tipos globales;
  2. si se decide retirar tipos, poner `documentos.documento_tipo_id = NULL` para esos
     IDs o restaurar backup completo;
  3. borrar filas globales de `documento_tipos` solo si no hay uso real;
  4. borrar registro de `migrations` del seed.
- Prueba local del hotfix creo `documentos.id = 2`; no borrar sin autorizacion.

### 4A revision tecnica post-QA

- Rollback de codigo/documentacion: revertir el commit `fix(review): stabilize phase 4a document center`.
- DB: no aplica; la revision tecnica no crea ni modifica datos.
- Cambio funcional: la ruta contextual `/documentos/entidad/{tipo}/{id}` valida la existencia de la entidad en el hotel actual antes de mostrar la vista.
- Si se revierte, la carga contextual sigue validando entidad antes de subir, pero el listado contextual volveria a poder mostrar una entidad inexistente como pantalla vacia.

### 4A auditoria seguridad post-QA

- Rollback documental: revertir el commit `docs(phase-4a): record document center security audit`.
- DB: no aplica; auditoria sin escrituras.
- Codigo: no aplica si el commit solo contiene documentacion de auditoria.

### 4A cierre tecnico

- Rollback documental: revertir el commit `docs(phase-4a): close document center foundation block`.
- DB: no aplica; cierre tecnico sin escrituras.
- Codigo: no aplica si el commit solo contiene documentacion de cierre.
- No borrar documentos de prueba, tipos documentales ni archivos de storage sin autorizacion explicita.

### 4B-0 contrato descarga segura documental

- Estado vigente: `DESCARGA_SEGURA_4B_VALIDADA_MANUALMENTE`.
- Rollback documental: revertir el commit `docs(phase-4b): define secure document download contract`.
- DB: no aplica; 4B-0 no escribe datos.
- Codigo: no aplica; 4B-0 no crea rutas ni controladores.
- Archivos fisicos: no aplica; no se leen ni borran archivos en 4B-0.

### 4B-A descarga segura autenticada

- Rollback de codigo/documentacion: revertir el commit `feat(phase-4b): add secure document download`.
- DB: no aplica; 4B-A no crea ni modifica datos.
- Archivos fisicos: no borrar archivos en `src/storage/documentos/`.
- No retirar `documentos`, `documento_entidades` ni `documento_tipos`; pertenecen a 4A.
- Despues del rollback, validar que no exista ruta `GET /documentos/{id}/descargar` y
  que `/documentos` y `/documentos/{id}` sigan funcionando.

### 4B-B auditoria de descargas documentales

- Rollback de codigo/documentacion: revertir el commit `feat(phase-4b): audit secure document downloads`.
- DB: no borrar auditorias existentes salvo autorizacion explicita; `logs_auditoria`
  es historico.
- Efecto esperado del rollback: la descarga sigue funcionando si 4B-A permanece, pero
  deja de registrar `documentos.descargado` y `documentos.descarga_bloqueada`.
- No hay migraciones, storage, Caja, pagos, abonos ni `/api/sync` involucrados.

### 4B-C-A metadata documental

- Rollback codigo/documentacion: revertir el commit `feat(phase-4b): add document metadata editing`.
- DB: no borrar auditorias existentes salvo autorizacion explicita.
- Si se hicieron cambios manuales de metadata durante QA, restaurar los valores desde
  `logs_auditoria.datos_antes` o backup antes de revertir codigo.
- No borrar auditorias 4B-B ni documentos existentes.

### 4B cierre tecnico post-QA

- Rollback documental: revertir el commit de cierre 4B si se quiere retirar solo la
  marca `CIERRE_TECNICO_4B_COMPLETADO`.
- DB: no aplica; el cierre es documentacion y verificaciones read-only.
- Codigo: no aplica si el commit solo contiene documentacion de cierre.
- No borrar documentos, auditorias ni archivos en `src/storage/documentos/`.

### 4C-0 documentos por entidad

- Rollback documental: revertir el commit
  `docs(phase-4c): define contextual document attachments contract`.
- DB: no aplica; 4C-0 no escribe datos ni crea migraciones.
- Codigo: no aplica; 4C-0 no toca rutas, controladores, modelos ni vistas.
- No borrar documentos, relaciones, auditorias ni archivos en `src/storage/documentos/`.

### 4C-A secciones documentales contextuales por entidad

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-4c): add read-only entity document sections`.
- Rollback del enlace contextual post-QA: revertir el commit
  `fix(phase-4c): add contextual document link`.
- DB: no aplica para revertir el enlace/contexto; 4C-A no crea tablas ni rutas nuevas.
- Quitar el partial de las fichas no debe borrar documentos ni relaciones existentes.
- Mantener intactas las rutas documentales globales (`/documentos`, detalle, descarga y
  metadata) porque pertenecen a 4A/4B.
- No borrar archivos fisicos bajo `src/storage/documentos/`.

### 4D-0 archivado documental

- Rollback documental: revertir el commit
  `docs(phase-4d): define document archival contract`.
- DB: no aplica; 4D-0 no escribe datos ni crea migraciones.
- Codigo: no aplica; 4D-0 no toca rutas, controladores, modelos ni vistas.
- No borrar documentos, relaciones, auditorias ni archivos en `src/storage/documentos/`.

### 4D-A/4D-B futuras

- Revertir por commit de implementacion.
- No usar `DELETE` SQL como rollback.
- Si hubo cambios de estado en prueba controlada, revertir solo con UPDATE documentado
  y backup previo si se autoriza explicitamente.
- No borrar archivos fisicos bajo `src/storage/documentos/`.

### 4D-B-0 contrato de baja logica documental

- Rollback documental: revertir el commit
  `docs(phase-4d): define document soft-delete contract`.
- DB: no aplica; 4D-B-0 no escribe datos ni crea migraciones.
- Codigo: no aplica; 4D-B-0 no toca rutas, controladores, modelos ni vistas.
- No borrar documentos, relaciones, auditorias ni archivos en `src/storage/documentos/`.

### 4D-B-A baja logica documental

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-4d): add controlled document soft-delete`.
- DB: no borrar filas. Si un documento fue marcado como `eliminado` durante QA o prueba
  local, definir restauracion operativa explicita antes de revertir datos.
- No usar `DELETE` sobre `documentos`, `documento_entidades` ni `logs_auditoria`.
- No borrar archivos fisicos bajo `src/storage/documentos/`.
- La recuperacion desde `eliminado` no esta implementada en 4D-B-A; requiere contrato
  separado si se necesita.

### 4D-A archivado/restauracion documental

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-4d): add controlled document archival`.
- Rollback de cierre tecnico documental: revertir el commit
  `docs(phase-4d): close document archival technical review` si solo se quiere
  deshacer la reclasificacion documental de cierre.
- DB: no borrar filas. Si un documento fue archivado durante QA, restaurarlo desde la
  UI con `POST /documentos/{id}/restaurar`.
- Si un documento fue restaurado durante QA y debe volver a archivado, usar la accion
  `POST /documentos/{id}/archivar`.
- No usar `DELETE` sobre `documentos`, `documento_entidades` ni `logs_auditoria`.
- No borrar archivos fisicos bajo `src/storage/documentos/`.

### Fases futuras

- Antes de migracion o escritura: backup fresco.
- No borrar archivos subidos sin autorizacion explicita.
- Preferir baja logica sobre borrado fisico.
- Revertir por subfase y validar health/preflights.
- Validacion posterior recomendada cuando Docker este disponible:
  - `docker compose exec -T app php -l app/views/cuentas_por_pagar/generacion_preview.php`;
  - `docker compose exec -T app php tools/saas/health_check_fase_1a.php`;
  - `docker compose exec -T app php tools/saas/preflight_compras_minimas.php`;
  - `docker compose exec -T app php tools/saas/preflight_recepcion_compras.php`.

## Bloque Personal y Nomina (Fase NP)

### NP-0 contrato y diagnostico

- Rollback: revertir el commit documental `docs(phase-np): define independent payroll module contract` si se descarta el diseno.
- DB: no aplica; NP-0 no escribe en la base de datos.

### NP-A en adelante (migraciones aditivas)

- Cada migracion creara tablas nuevas vacias (`trabajadores`, `trabajador_pagos`,
  `trabajador_anticipos`, `trabajador_prestamos`, `trabajador_asistencias`,
  `trabajador_documentos`) con `CREATE TABLE IF NOT EXISTS` y bloque de rollback comentado.
- Backup previo obligatorio antes de cualquier escritura local de prueba, siguiendo el
  patron `src/storage/backups/`.
- Rollback de codigo: revertir el commit de la subfase correspondiente.
- Rollback de DB: `DROP TABLE` de las tablas nuevas SOLO si estan vacias y con
  autorizacion explicita; quitar el registro de `migrations` por `nombre`.
- Si existen datos reales (trabajadores, pagos, anticipos, prestamos, asistencias,
  documentos), NO ejecutar `DROP`/`DELETE`: exportar conteos, reconciliar y documentar
  rollback especifico antes de cualquier cambio.

### NP-A migracion base aplicada

- Backup valido:
  `src/storage/backups/phase_np_a_20260616_021311_before_personal_base_medisoft_hoteles_import.sql`.
- SHA256: `0F9E64B040437A73D559534E5753133F4C3508C29F5B9EA0278B351097666246`.
- Rollback solo con autorizacion explicita y si las seis tablas siguen vacias:
  `trabajador_documentos`, `trabajador_asistencias`, `trabajador_prestamos`,
  `trabajador_anticipos`, `trabajador_pagos`, `trabajadores`.
- Borrar tambien el registro de `migrations` para
  `20260616_001_fase_np_a_personal_base.sql` solo si se hace rollback completo.

### NP-A UI read-first

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-np): add read-only worker directory`.
- DB: no aplica para retirar la UI; no ejecutar `DROP`, `DELETE`, `UPDATE` ni cambios de
  datos.
- Mantener intactas las tablas `trabajador*`; pertenecen a la migracion base NP-A.
- No tocar Caja, `usuarios`, `hotel_usuarios` ni `/api/sync`.

### NP-B-0 contrato CRUD trabajadores

- Rollback documental: revertir el commit
  `docs(phase-np): define worker CRUD contract`.
- DB: no aplica; NP-B-0 no escribe datos ni crea migraciones.
- Codigo: no aplica; NP-B-0 no toca rutas, controladores, modelos ni vistas.

### NP-B-A futura implementacion CRUD trabajadores

- Rollback de codigo/documentacion: revertir el commit de implementacion.
- DB: no usar `DELETE` automaticamente sobre `trabajadores`; si hay datos de prueba,
  documentar IDs y preferir baja logica o restauracion desde backup autorizado.
- No borrar tablas `trabajador*`.
- No tocar Caja, `usuarios`, `hotel_usuarios` ni `/api/sync`.

### NP-B-A CRUD trabajadores aplicado

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-np): add controlled worker CRUD`.
- DB: no usar `DELETE`. Si se crearon trabajadores de prueba durante QA, aplicar baja
  logica o restaurar backup autorizado.
- Auditorias generadas por QA no deben borrarse sin autorizacion.

### Reglas duras de rollback NP

- No borrar ni alterar `usuarios` ni `hotel_usuarios` durante ningun rollback NP.
- No tocar Caja, cortes ni movimientos durante ningun rollback NP.
- No hacer reset destructivo de Git ni push.

## Bloque Tareas, Limpieza y Mantenimiento (Fase TLM)

### TLM-0 contrato y diagnostico

- Rollback documental: revertir el commit
  `docs(phase-tlm): define tasks housekeeping maintenance contract`.
- DB: no aplica; TLM-0 no crea migraciones ni escribe datos.
- Codigo: no aplica; TLM-0 no crea rutas, controladores, modelos ni vistas.

### TLM-A futura migracion base

- Backup previo obligatorio antes de aplicar cualquier migracion.
- La migracion debe ser aditiva e idempotente.
- Rollback de DB solo con autorizacion explicita y solo si las tablas nuevas siguen
  vacias.
- No borrar ni modificar `mantenimientos_habitaciones`.
- No cambiar ni recalcular `habitaciones.estado`.

### TLM-A migracion base aplicada

- Backup valido:
  `src/storage/backups/phase_tlm_a_20260616_030648_before_tasks_base_medisoft_hoteles_import.sql`.
- SHA256: `C76243D8AF98A2D9AA9D94FB5B1BB4D29369A861594141C7EC397A13AF3CF242`.
- Rollback DB solo con autorizacion explicita y si ambas tablas siguen vacias:
  `tarea_eventos`, `tareas_operativas`.
- Quitar registro de `migrations` para
  `20260616_002_fase_tlm_a_tareas_base.sql` solo si se hace rollback completo.
- No tocar `mantenimientos_habitaciones`, `habitaciones`, Caja ni `/api/sync`.

### TLM-B read-only aplicado

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-tlm): add read-only operational tasks layer`.
- DB: no aplica para retirar la UI; no ejecutar `DROP`, `DELETE`, `UPDATE` ni cambios de
  datos.
- Mantener intactas `tareas_operativas` y `tarea_eventos`; pertenecen a TLM-A.
- No tocar `habitaciones.estado`, `mantenimientos_habitaciones`, Caja ni `/api/sync`.

### TLM-C creacion manual aplicada

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-tlm): create operational tasks manually`.
- DB: no ejecutar `DELETE` automaticamente. Si QA manual crea tareas de prueba,
  documentar IDs y esperar autorizacion para cancelarlas o reconciliarlas en una fase
  posterior.
- Mantener intactas `tareas_operativas` y `tarea_eventos`; pertenecen a TLM-A.
- No revertir mediante cambios a `habitaciones.estado` ni
  `mantenimientos_habitaciones`, porque TLM-C no los modifica.
- No tocar Caja, pagos, abonos, nomina ni `/api/sync`.

### TLM-D asignacion aplicada

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-tlm): assign tasks to workers`.
- DB: no ejecutar `DELETE` ni `UPDATE` manual sobre tareas/eventos sin autorizacion.
- Si QA manual asigna una tarea de prueba, documentar IDs y esperar una fase autorizada
  de reasignacion/cancelacion para corregirla.
- No revertir mediante cambios a `habitaciones.estado`.
- No tocar `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.

### TLM-E estados manuales aplicado

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-tlm): manage task lifecycle manually`.
- DB: no ejecutar `DELETE` ni `UPDATE` manual sobre tareas/eventos sin autorizacion.
- Si QA manual inicio, completo o cancelo tareas de prueba, documentar IDs antes de
  cualquier correccion.
- No revertir mediante cambios a `habitaciones.estado`.
- No tocar `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.

### TLM-F contexto visual aplicado

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-tlm): show contextual operational tasks`.
- DB: no ejecutar SQL; TLM-F no crea migraciones ni escribe datos.
- Mantener intactas `tareas_operativas` y `tarea_eventos`; pertenecen a TLM-A.
- No tocar `habitaciones.estado`, `mantenimientos_habitaciones`, Caja, pagos, abonos,
  nomina ni `/api/sync`.
- Si hay tareas reales existentes, no borrarlas; solo retirar la vista contextual si se
  decide revertir.

### TLM-G preflight de consistencia aplicado

- Rollback de codigo/documentacion: revertir el commit
  `test(phase-tlm): add operational task consistency checks`.
- DB: no aplica; TLM-G solo agrega verificadores read-only.
- No tocar `tareas_operativas`, `tarea_eventos`, `habitaciones.estado`,
  `mantenimientos_habitaciones`, Caja, pagos, abonos, nomina ni `/api/sync`.

### TLM-H cierre tecnico aplicado

- Rollback documental: revertir el commit `docs(phase-tlm): close operational tasks block`.
- DB: no aplica; TLM-H solo documenta revision/auditoria/cierre.
- No tocar datos reales ni tablas TLM durante rollback documental.

### Reglas duras de rollback TLM

- No tocar Caja, pagos, abonos ni nomina.
- No tocar `/api/sync`, PWA, offline, IndexedDB ni caches.
- No borrar mantenimientos historicos.
- No ejecutar `DELETE` ni `DROP` sin autorizacion nueva y backup validado.

### NP-C-0 contrato de ledger laboral

- Revertir el commit documental `docs(phase-np): define worker ledger contract`.
- DB: no aplica; NP-C-0 no crea migraciones ni escribe datos.
- Codigo: no aplica; NP-C-0 no agrega rutas, modelos, controladores ni vistas.
- No tocar tablas `trabajador_*`, Caja, movimientos, categorias ni `/api/sync`.

### NP-C-A ledger laboral read-only

- Revertir el commit `feat(phase-np): add read-only worker ledger view`.
- DB: no aplica; NP-C-A no crea migraciones ni escribe datos.
- No borrar conceptos, anticipos, prestamos ni asistencias reales si ya existieran.
- No tocar Caja, categoria Nomina ni `/api/sync`.

### NP-C-E preflight de ledger laboral

- Revertir el commit `test(phase-np): add worker ledger consistency checks`.
- DB: no aplica; el preflight es solo lectura.
- No tocar tablas `trabajador_*`, Caja ni `/api/sync`.

### NP-C-F cierre tecnico read-only

- Revertir el commit `docs(phase-np): close read-only worker ledger block`.
- DB: no aplica; es cierre documental.
- No tocar tablas `trabajador_*`, Caja ni `/api/sync`.

### NP-C-B-0 contrato de conceptos laborales

- Revertir el commit `docs(phase-np): define controlled worker concept contract`.
- DB: no aplica; es solo documentacion.
- No tocar `trabajador_pagos`, Caja ni `/api/sync`.

### NP-C-B-A conceptos laborales manuales

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-np): add controlled worker concept entry`.
- DB: no ejecutar `DELETE`, `UPDATE` ni correcciones manuales sobre `trabajador_pagos`
  sin autorizacion nueva.
- Si QA manual genero conceptos de prueba, documentar los IDs y esperar una fase
  autorizada de anulacion o correccion.
- No tocar Caja, categorias de Caja, movimientos, pagos reales, abonos, anticipos,
  prestamos, asistencia ni `/api/sync`.

### NP-C-B-F cierre tecnico conceptos laborales

- Rollback documental: revertir el commit
  `docs(phase-np): close controlled worker concept block`.
- DB: no aplica; es cierre documental.
- Mantener intactos conceptos ya creados si existieran.

### NP-C-C-0 contrato de anticipos y prestamos

- Rollback documental: revertir el commit
  `docs(phase-np): define controlled worker advance loan contract`.
- DB: no aplica; NP-C-C-0 no crea rutas, migraciones ni datos.
- No tocar `trabajador_anticipos`, `trabajador_prestamos`, Caja ni `/api/sync`.

### NP-C-C-A anticipos y prestamos manuales

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-np): add controlled worker advances and loans`.
- DB: no ejecutar `DELETE`, `UPDATE` ni correcciones manuales sobre
  `trabajador_anticipos` o `trabajador_prestamos` sin autorizacion nueva.
- Si QA manual genero registros de prueba, documentar IDs y esperar fase autorizada de
  anulacion o correccion.
- No tocar Caja, movimientos, pagos reales, abonos, asistencia ni `/api/sync`.

### NP-C-C-F cierre tecnico anticipos y prestamos

- Rollback documental: revertir el commit
  `docs(phase-np): close controlled worker advance loan block`.
- DB: no aplica; es cierre documental.
- Mantener intactos anticipos/prestamos ya creados si existieran.

### NP-C-D-0 contrato de asistencia manual

- Rollback documental: revertir el commit
  `docs(phase-np): define controlled worker attendance contract`.
- DB: no aplica; NP-C-D-0 no crea rutas, migraciones ni datos.
- No tocar `trabajador_asistencias`, Caja ni `/api/sync`.

### NP-C-D-A asistencia manual

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-np): add controlled worker attendance capture`.
- DB: no ejecutar `DELETE`, `UPDATE` ni correcciones manuales sobre
  `trabajador_asistencias` sin autorizacion nueva.
- Si QA manual genero asistencias de prueba, documentar IDs y esperar fase autorizada de
  anulacion o correccion.
- No tocar Caja, movimientos, pagos reales, abonos, nomina ni `/api/sync`.

### NP-C-D-F cierre tecnico asistencia manual

- Rollback documental: revertir el commit
  `docs(phase-np): close controlled worker attendance block`.
- DB: no aplica; es cierre documental.
- Mantener intactas asistencias ya creadas si existieran.

### NP-D-0 contrato de documentos laborales

- Rollback documental: revertir el commit
  `docs(phase-np): define worker document center contract`.
- DB: no aplica; NP-D-0 no crea rutas, migraciones ni datos.
- No tocar `documentos`, `documento_entidades`, `trabajador_documentos` ni storage.

### NP-D-A documentos laborales contextuales

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-np): link workers to document center`.
- DB: no ejecutar `DELETE`, `UPDATE` ni correcciones manuales sobre `documentos`,
  `documento_entidades` o storage sin autorizacion nueva.
- Si QA manual genero documentos o vinculos de prueba, documentar IDs y usar el flujo
  documental autorizado de baja logica/archivado.
- No tocar `trabajador_documentos`, Caja, pagos, abonos, nomina ni `/api/sync`.

### NP-D-F cierre tecnico documentos laborales

- Rollback documental: revertir el commit
  `docs(phase-np): close worker document center block`.
- DB: no aplica; es cierre documental.
- Mantener intactos documentos y vinculos ya creados si existieran.

### NP-E cierre tecnico Personal operativo base

- Rollback documental: revertir el commit
  `docs(phase-np): close personal base operations block`.
- DB: no aplica; es cierre documental.
- Para codigo o datos, usar los rollback granulares de NP-A, NP-B, NP-C y NP-D.
- No tocar tablas `trabajador_*`, `documentos`, `documento_entidades`, Caja ni
  `/api/sync` sin autorizacion nueva.

### NP-F-0 contrato reporte Personal read-only

- Rollback documental: revertir el commit
  `docs(phase-np): define read-only worker report contract`.
- DB: no aplica; es solo contrato.
- No tocar tablas `trabajador_*`, tareas, documentos, Caja ni `/api/sync`.

### NP-F-A reporte Personal read-only

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-np): add read-only worker report`.
- DB: no aplica; NP-F-A no crea migraciones ni escribe datos.
- No tocar tablas `trabajador_*`, `tareas_operativas`, documentos, Caja ni `/api/sync`.

### NP-F-F cierre reporte Personal read-only

- Rollback documental: revertir el commit
  `docs(phase-np): close read-only worker report block`.
- DB: no aplica; es cierre documental.
- Mantener intacto el codigo NP-F-A salvo que se revierta su commit especifico.

### TLM-I-0 contrato reporte operativo read-only

- Rollback documental: revertir el commit
  `docs(phase-tlm): define read-only operations report contract`.
- DB: no aplica; es solo contrato.
- No tocar `tareas_operativas`, `tarea_eventos`, habitaciones, mantenimiento, Caja ni
  `/api/sync`.

### TLM-I-A reporte operativo read-only

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-tlm): add read-only operations report`.
- DB: no aplica; TLM-I-A no crea migraciones ni escribe datos.
- No tocar `tareas_operativas`, `tarea_eventos`, habitaciones, mantenimiento, Caja ni
  `/api/sync`.

### TLM-I-F cierre reporte operativo read-only

- Rollback documental: revertir el commit
  `docs(phase-tlm): close read-only operations report block`.
- DB: no aplica; es cierre documental.
- Mantener intacto el codigo TLM-I-A salvo que se revierta su commit especifico.

### OP-0 contrato tablero operativo diario read-only

- Rollback documental: revertir el commit
  `docs(phase-op): define read-only daily operations dashboard contract`.
- DB: no aplica; OP-0 no crea migraciones, rutas ni datos.
- No tocar reservaciones, habitaciones, tareas, documentos, Caja ni `/api/sync`.

### OP-A tablero operativo diario read-only

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-op): add read-only daily operations dashboard`.
- DB: no aplica; OP-A no crea migraciones ni escribe datos.
- Retirar ruta `GET /operacion/diaria`, `OperacionController`, `OperacionDiaria`, vista,
  preflight, enlace sidebar y checks OP-A.
- No tocar reservaciones, habitaciones, tareas, documentos, Caja ni `/api/sync`.

### OP-F cierre tecnico tablero operativo

- Rollback documental: revertir el commit
  `docs(phase-op): close read-only daily operations dashboard block`.
- DB: no aplica; es cierre documental.
- Mantener intacto el codigo OP-A salvo que se revierta su commit especifico.

### MANT-A reporte de mantenimiento read-only

- Rollback de codigo/documentacion: revertir el commit
  `test(phase-mant): add read-only maintenance report guardrails`.
- DB: no aplica; MANT-A no crea migraciones ni escribe datos.
- El rollback retiraria el preflight MANT-A, los checks del health y el filtro multihotel
  agregado a la familia activa del reporte.
- Si se revierte, revisar manualmente que `/reportes/mantenimiento` no mezcle datos de
  hoteles distintos antes de exponerlo a usuarios.
- No tocar Caja, pagos, abonos, nomina, offline ni `/api/sync`.

### MANT-F cierre tecnico reporte mantenimiento

- Rollback documental: revertir el commit
  `docs(phase-mant): close read-only maintenance report block`.
- DB: no aplica; es cierre documental.
- Mantener intacto el codigo MANT-A salvo que se revierta su commit especifico.

### MANT-B mantenimiento inmediato existente

- Rollback de codigo/documentacion: revertir el commit
  `fix(phase-mant): harden immediate maintenance action`.
- DB: no aplica; MANT-B no crea migraciones ni modifica datos por si mismo.
- Si QA manual creo mantenimientos de prueba, documentar IDs y usar flujos autorizados
  para finalizar/cancelar; no borrar con SQL manual.
- No tocar Caja, pagos, abonos, nomina, offline ni `/api/sync`.

### MANT-B-F cierre tecnico mantenimiento inmediato

- Rollback documental: revertir el commit
  `docs(phase-mant): close immediate maintenance guardrails`.
- DB: no aplica; es cierre documental.
- Mantener intacto el codigo MANT-B salvo que se revierta su commit especifico.

### MANT-C-0 contrato mantenimiento programado

- Rollback documental: revertir el commit
  `docs(phase-mant): define scheduled maintenance contract`.
- DB: no aplica; MANT-C-0 no crea migraciones, rutas ni datos.
- Codigo: no aplica; no se modifica PHP.
- No tocar registros reales de `mantenimientos_habitaciones`.

### MANT-C-A guardrails mantenimiento programado

- Rollback de codigo/documentacion: revertir el commit
  `fix(phase-mant): harden scheduled maintenance actions`.
- DB: no aplica; MANT-C-A no crea migraciones ni escribe datos por si mismo.
- Si QA manual creo mantenimientos programados, documentar IDs y cancelar con el flujo
  autorizado; no borrar con SQL manual.
- No tocar Caja, pagos, abonos, nomina, offline ni `/api/sync`.

### MANT-C-F cierre tecnico mantenimiento programado

- Rollback documental: revertir el commit
  `docs(phase-mant): close scheduled maintenance guardrails`.
- DB: no aplica; es cierre documental.
- Mantener intacto el codigo MANT-C-A salvo que se revierta su commit especifico.

### MANT-D-0 contrato preview vencidos

- Rollback documental: revertir el commit
  `docs(phase-mant): define overdue maintenance preview contract`.
- DB: no aplica; MANT-D-0 no crea rutas, migraciones ni datos.
- Codigo: no aplica.

### MANT-D-A preview mantenimiento programado

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-mant): add overdue maintenance preview`.
- DB: no aplica; MANT-D-A no crea migraciones ni escribe datos.
- El rollback retiraria ruta GET `/reportes/mantenimiento-programado`, accion de
  `ReportesController`, metodo `Mantenimiento::previewProgramados()`, vista, enlace en
  reportes y checks de health/preflight.
- No tocar registros reales de `mantenimientos_habitaciones`, `habitaciones`,
  reservaciones, Caja ni `/api/sync`.

### MANT-D-F cierre tecnico preview vencidos

- Rollback documental: revertir el commit
  `docs(phase-mant): close overdue maintenance preview`.
- DB: no aplica; es cierre documental.
- Mantener intacto MANT-D-A salvo que se revierta su commit especifico.

### MANT-E-0 contrato activacion manual

- Rollback documental: revertir el commit
  `docs(phase-mant): define manual overdue maintenance activation contract`.
- DB: no aplica; MANT-E-0 no crea rutas, migraciones ni datos.
- Codigo: no aplica.

### MANT-E-A activacion manual mantenimiento programado

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-mant): activate overdue scheduled maintenance manually`.
- DB: no aplica si no se ejecuto QA manual real.
- Si QA manual activo un mantenimiento real, documentar ID de mantenimiento y habitacion;
  no borrar ni actualizar con SQL manual.
- Retirar ruta POST, accion del controlador, metodo transaccional, boton del preview y
  checks MANT-E-A.

### MANT-E-F cierre tecnico activacion manual

- Rollback documental: revertir el commit
  `docs(phase-mant): close manual maintenance activation block`.
- DB: no aplica; es cierre documental.
- Mantener intacto MANT-E-A salvo que se revierta su commit especifico.

### MANT-G-0 contrato tareas desde mantenimiento

- Rollback documental: revertir el commit
  `docs(phase-mant): define maintenance task linkage contract`.
- DB: no aplica; MANT-G-0 no crea migraciones, rutas, modelos ni datos.
- Codigo: no aplica.
- No borrar ni modificar tareas, mantenimientos o habitaciones.

### MANT-G-A tareas contextuales desde mantenimiento

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-mant): show maintenance linked tasks`.
- DB: no aplica; MANT-G-A no crea migraciones ni escribe datos.
- El rollback retiraria soporte contextual `mantenimiento` en
  `TareaOperativa::listarPorEntidadHotel()`, carga desde `ReportesController` y bloque
  visual de tareas vinculadas en el preview.
- No borrar tareas, mantenimientos, habitaciones ni eventos.
- No tocar Caja, pagos, abonos, nomina ni `/api/sync`.

### MANT-G-B-0 contrato creacion manual de tarea desde mantenimiento

- Rollback documental: revertir el commit
  `docs(phase-mant): define manual maintenance task creation contract`.
- DB: no aplica; MANT-G-B-0 no crea migraciones, rutas, modelos ni datos.
- Codigo: no aplica.
- No borrar ni modificar tareas, mantenimientos, habitaciones o eventos.

### MANT-G-B-A creacion manual de tarea desde mantenimiento

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-mant): create tasks from maintenance manually`.
- DB: no aplica si no se ejecuta QA manual.
- Si QA manual crea tareas reales, no borrar con SQL manual; usar flujo de cancelacion de
  tareas o documentar IDs.
- El rollback retiraria ruta POST, accion del controlador, metodo de modelo, boton en
  preview y checks asociados.
- No tocar Caja, pagos, abonos, nomina ni `/api/sync`.

### MANT-G-F cierre tecnico tareas desde mantenimiento

- Rollback documental: revertir el commit
  `docs(phase-mant): close maintenance task linkage block`.
- DB: no aplica; es cierre documental.
- Mantener intacto MANT-G-A/MANT-G-B-A salvo que se reviertan sus commits especificos.

### LIM-0 contrato limpieza operativa

- Rollback documental: revertir el commit
  `docs(phase-lim): define housekeeping operations contract`.
- DB: no aplica; LIM-0 no crea migraciones, rutas, modelos ni datos.
- Codigo: no aplica.
- No tocar habitaciones, reservaciones, tareas, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.

### LIM-A reporte limpieza read-only

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-lim): add read-only housekeeping report`.
- DB: no aplica; LIM-A no crea migraciones ni datos.
- El rollback retiraria ruta GET, accion de reportes, vista, enlace del centro de
  reportes y preflight LIM-A.
- No tocar habitaciones, reservaciones, tareas, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.

### LIM-F cierre tecnico limpieza read-only

- Rollback documental: revertir el commit
  `docs(phase-lim): close read-only housekeeping block`.
- DB: no aplica; es cierre documental.
- Mantener intacto LIM-A salvo que se revierta su commit especifico.

### LIM-B-0 contrato creacion manual de tarea de limpieza

- Rollback documental: revertir el commit
  `docs(phase-lim): define manual housekeeping task contract`.
- DB: no aplica; LIM-B-0 no crea migraciones, rutas, modelos ni datos.
- Codigo: no aplica.
- No tocar habitaciones, tareas, inventario, Caja, pagos, abonos, nomina, offline ni
  `/api/sync`.

### LIM-B-A creacion manual de tarea de limpieza

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-lim): create housekeeping tasks manually`.
- DB: no aplica si no se ejecuta QA manual.
- Si QA manual crea tareas reales, no borrar con SQL manual; usar flujo de cancelacion de
  tareas o documentar IDs.
- El rollback retiraria ruta POST, accion del controlador, metodos de modelo, boton en
  reporte y checks asociados.
- No tocar habitaciones, inventario, Caja, pagos, abonos, nomina ni `/api/sync`.

### LIM-B-F cierre tecnico tareas desde limpieza

- Rollback documental: revertir el commit
  `docs(phase-lim): close manual housekeeping task block`.
- Para retirar la funcionalidad, revertir
  `c6d6743 feat(phase-lim): create housekeeping tasks manually`.
- DB/migraciones: no aplica.
- Si existen tareas reales creadas por QA posterior, no eliminarlas con SQL
  manual; cancelar por flujo de tareas o documentar reconciliacion.

### TLM-J-0 contrato agenda de tareas por trabajador

- Rollback documental: revertir el commit
  `docs(phase-tlm): define worker task agenda contract`.
- DB: no aplica; TLM-J-0 no crea migraciones, rutas, modelos ni datos.
- Codigo: no aplica.
- No tocar tareas, trabajadores, habitaciones, mantenimientos, Caja, pagos, abonos,
  nomina, offline ni `/api/sync`.

### TLM-J-A agenda de tareas por trabajador

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-tlm): add worker task agenda`.
- DB: no aplica; no crea migraciones ni datos.
- El rollback retiraria ruta GET, accion del controlador, metodo read-only del modelo,
  vista, enlace desde tareas y checks asociados.
- No tocar tareas reales, trabajadores, habitaciones, mantenimientos, Caja, pagos,
  abonos, nomina, offline ni `/api/sync`.

### TLM-J-F cierre tecnico agenda de tareas

- Rollback documental: revertir el commit
  `docs(phase-tlm): close worker task agenda block`.
- DB: no aplica; es cierre documental.
- Mantener intacto TLM-J-A salvo que se revierta su commit especifico.
