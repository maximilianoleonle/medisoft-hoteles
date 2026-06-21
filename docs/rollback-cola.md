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

### 5A reanclaje Personal laboral basico

- Rollback documental: revertir el commit
  `docs(phase-5a): map basic labor staff to existing personal module`.
- DB: no aplica; no crea migraciones ni datos.
- Codigo: no aplica; no agrega rutas, modelos ni vistas.
- No tocar tablas `trabajador*`.

### 6B-0 contrato integracion tareas + habitaciones

- Rollback documental: revertir el commit
  `docs(phase-6b): define task room integration contract`.
- DB: no aplica; no crea migraciones ni datos.
- Codigo: no aplica; no agrega rutas, modelos ni vistas.
- No tocar `habitaciones`, `tareas_operativas`, `tarea_eventos`, Caja ni `/api/sync`.

### 6B-A indicadores read-only de tareas en habitaciones

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-6b): add read-only room task indicators`.
- DB: no aplica; no crea migraciones ni datos.
- El rollback retiraria el metodo agregado en `TareaOperativa`, el anexo en
  `HabitacionController`, el indicador visual en `habitaciones/index.php` y checks/docs
  asociados.
- No borrar ni modificar registros reales de `tareas_operativas` o `tarea_eventos`.
- No tocar `habitaciones.estado`, Caja, nomina, offline ni `/api/sync`.

### 6C-0 contrato evidencias/documentos en tareas

- Rollback documental: revertir el commit
  `docs(phase-6c): define task evidence document contract`.
- DB: no aplica; no crea migraciones ni datos.
- Codigo: no aplica; no agrega rutas, modelos ni vistas.
- No tocar documentos reales, `documento_entidades`, `tareas_operativas`,
  `tarea_eventos`, Caja, nomina, offline ni `/api/sync`.

### 6C-A documentos read-only en tareas

- Rollback de codigo/documentacion: revertir el commit
  `feat(phase-6c): show read-only task documents`.
- DB: no aplica; no crea migraciones ni datos.
- Retira la consulta documental por tarea, la seccion en `tareas/ver.php` y checks/docs
  asociados.
- No borrar documentos ni registros de `documento_entidades`.
- No tocar `tareas_operativas`, `tarea_eventos`, habitaciones, Caja, nomina, offline ni
  `/api/sync`.
## Rollback Fase 6C-B

La fase 6C-B solo habilita `tarea` como entidad documental y enciende enlaces
contextuales existentes en el detalle de tarea.

Rollback recomendado:

1. Revertir el commit `feat(phase-6c): enable safe task document linking`.
2. No borrar documentos.
3. No borrar filas de `documento_entidades`.
4. Si existen documentos reales vinculados a `entidad_tipo = tarea`, conservarlos como
   historico y evaluar reconciliacion posterior.
5. Ejecutar `php -l`, `preflight_tareas_operativas.php` y health checker.

## Rollback Fase 6C-F

El cierre 6C-F es documental.

Rollback:

1. Revertir el commit de cierre si solo se requiere corregir documentacion.
2. Para desactivar funcionalmente la integracion, revertir 6C-B y/o 6C-A.
3. No borrar documentos ni vinculos reales existentes.

## Rollback Fase 5B

5B es un reanclaje documental hacia NP-C-D.

Rollback:

1. Revertir el commit documental de reanclaje si la clasificacion cambia.
2. No revertir NP-C-D sin decision explicita, porque es la fuente vigente de asistencia.
3. No borrar filas de `trabajador_asistencias`.
4. Para datos de prueba, documentar IDs y esperar fase de anulacion/correccion.

## Rollback Fase 5C

5C es un reanclaje documental hacia NP-C-C.

Rollback:

1. Revertir el commit documental de reanclaje si la clasificacion cambia.
2. No revertir NP-C-C sin decision explicita.
3. No borrar filas de `trabajador_anticipos` ni `trabajador_prestamos`.
4. No liquidar ni modificar saldos manualmente fuera de una fase autorizada.

## Rollback Fase 5D-0

5D-0 es solo documental.

Rollback:

1. Revertir el commit documental si cambia la decision.
2. No tocar `trabajador_pagos`.
3. No crear ni eliminar pagos laborales.
4. No tocar Caja.

## Rollback Fase 7A-0

7A-0 es solo documental.

Rollback:

1. Revertir el commit documental si cambia la decision.
2. No tocar reservaciones, pagos, abonos, facturacion ni Caja.
3. No crear tabla CxC sin contrato nuevo.

## Rollback Fase 7A-A

7A-A agrega solo una capa GET/read-only derivada.

Rollback:

1. Revertir el commit `feat(phase-7a): add read-only receivables report`.
2. Retirar ruta GET `/cuentas-por-cobrar`, controlador, modelo, vista, preflight y
   enlace de sidebar asociados.
3. No tocar datos de `reservaciones`, `reservacion_pagos`, `reservacion_abonos` ni
   `solicitudes_factura`.
4. No borrar ni crear tablas.
5. No tocar Caja ni `/api/sync`.

## Rollback Fase 7A-F

7A-F es solo cierre documental.

Rollback:

1. Revertir el commit documental de cierre.
2. Si se requiere retirar funcionalidad, revertir 7A-A.
3. No tocar datos historicos ni Caja.

## Rollback Fase 7A-R

7A-R es solo diagnostico documental/read-only.

Rollback:

1. Revertir el documento `docs/fase_7A_R_reconciliacion_cxc_readonly.md`.
2. Retirar referencias a 7A-R en resumen, cola y auditoria.
3. No tocar `reservaciones`, `reservacion_pagos`, `reservacion_abonos`,
   `solicitudes_factura`, Caja ni `/api/sync`.

## Rollback Fase 7A-S-0

7A-S-0 es solo contrato documental.

Rollback:

1. Revertir el documento
   `docs/fase_7A_S_0_contrato_reconciliacion_cxc_controlada.md`.
2. Retirar referencias a 7A-S-0 en resumen, cola, auditoria, fuentes de verdad y cierre
   7A-F.
3. No tocar datos historicos.
4. No crear ni borrar tablas CxC.
5. No tocar Caja ni `/api/sync`.

## Rollback Fase 7A-S-A

7A-S-A es solo preview/matriz documental read-only.

Rollback:

1. Revertir el documento `docs/fase_7A_S_A_preview_reconciliacion_cxc.md`.
2. Retirar referencias a 7A-S-A en resumen, cola, auditoria, fuentes de verdad,
   cierre 7A-F y contrato 7A-S-0.
3. No tocar datos historicos.
4. No crear ni borrar tablas CxC.
5. No tocar Caja ni `/api/sync`.

## Rollback Fase 7A-S-B

7A-S-B es solo politica documental.

Rollback:

1. Revertir el documento `docs/fase_7A_S_B_politica_clasificacion_cxc.md`.
2. Retirar referencias a 7A-S-B en resumen, cola, auditoria, fuentes de verdad,
   cierre 7A-F, preview 7A-S-A y contrato 7A-S-0.
3. No tocar datos historicos.
4. No crear ni borrar tablas CxC.
5. No tocar Caja ni `/api/sync`.

## Rollback Fase 7A-S-F

7A-S-F es solo cierre documental.

Rollback:

1. Revertir el documento `docs/fase_7A_S_F_cierre_reconciliacion_cxc.md`.
2. Retirar referencias a 7A-S-F en resumen, cola, auditoria, cierre 7A-F y contrato
   7A-S-0.
3. No tocar datos historicos.
4. No crear ni borrar tablas CxC.
5. No tocar Caja ni `/api/sync`.

## Rollback Fase 7B-0

7B-0 es solo contrato documental.

Rollback:

1. Revertir el commit documental de 7B-0.
2. No tocar reservaciones, pagos, abonos, facturacion ni Caja.
3. No crear ni borrar tablas CxC.
4. Si se revierte solo 7B-0 documentalmente, revisar tambien las referencias posteriores
   a 7B-A antes de dejar la documentacion como fuente vigente.

## Rollback Fase 7B-A

7B-A crea estructura DB vacia y aditiva para CxC futura.

Backup previo:

- `backups/medisoft_hoteles_import_before_7b_a_cxc_base_20260618_163625.sql`
- SHA256: `0E13547DF43359E71C1A3503EFD8F3A5B5CD096A9BF843EB19F282C0FBE90514`
- Tamano: `3269772` bytes

Rollback DB manual solo con autorizacion explicita y si las tablas siguen vacias:

1. Confirmar `SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos;`.
2. Confirmar `SELECT COUNT(*) FROM cuentas_por_cobrar;`.
3. Si ambos conteos son `0`, ejecutar `DROP TABLE cuentas_por_cobrar_movimientos;`.
4. Ejecutar `DROP TABLE cuentas_por_cobrar;`.
5. Borrar el registro de `migrations` para
   `20260618_001_fase_7b_a_cxc_base_vacia.sql`.

Si alguna tabla tiene datos, no ejecutar `DROP` ni `DELETE`; exportar, reconciliar y
pedir autorizacion especifica. No tocar Caja, cortes, movimientos, reservaciones,
pagos, abonos, facturacion ni `/api/sync`.

## Rollback Fase 7B-B

7B-B agrega solo lectura de CxC operativa vacia.

Rollback de codigo:

1. Retirar rutas GET `/cuentas-por-cobrar/operativas` y
   `/cuentas-por-cobrar/operativas/{id}`.
2. Revertir metodos `operativasAction()` y `verOperativaAction()` del controlador.
3. Revertir metodos operativos read-only agregados al modelo `CuentaPorCobrar`.
4. Eliminar vistas `cuentas_por_cobrar/operativas.php` y
   `cuentas_por_cobrar/ver_operativa.php`.
5. Revertir checks 7B-B en `preflight_cuentas_por_cobrar.php`.

DB: no aplica; 7B-B no escribe datos ni ejecuta migraciones.

No borrar tablas `cuentas_por_cobrar` ni `cuentas_por_cobrar_movimientos`; pertenecen a
7B-A.

## Rollback Fase 7B-C-0

7B-C-0 es solo contrato documental.

Rollback:

1. Revertir el documento
   `docs/fase_7B_C_0_contrato_generacion_manual_cxc_reservacion.md`.
2. Retirar referencias a 7B-C-0 en resumen, cola, auditoria, fuentes de verdad y cierre
   7B-B.
3. No tocar `cuentas_por_cobrar`.
4. No tocar `cuentas_por_cobrar_movimientos`.
5. No tocar reservaciones, pagos, abonos, facturacion, Caja ni `/api/sync`.

DB: no aplica; no hay escrituras ni migraciones.

## Rollback Fase 7B-C-A

7B-C-A agrega codigo para generar CxC manual desde reservacion elegible y puede crear
datos reales cuando se use desde navegador.

Backup previo:

- `backups/medisoft_hoteles_import_before_7b_c_a_cxc_manual_20260618_170535.sql`
- SHA256: `877BA8D0E9F97D8DA3047392EE2C5CC91DACEE20A94F5069BF57A2164377EE7B`
- Tamano: `1647809` bytes

Rollback de codigo si se decide desactivar la funcion:

1. Retirar la ruta POST
   `/cuentas-por-cobrar/generar-desde-reservacion/{id}`.
2. Retirar `CuentaPorCobrarController::generarDesdeReservacionAction()`.
3. Retirar `CuentaPorCobrar::generarDesdeReservacionElegible()` y helpers exclusivos.
4. Retirar el formulario POST de `cuentas_por_cobrar/index.php`.
5. Ajustar `preflight_cuentas_por_cobrar.php` para volver al contrato anterior.

Rollback de datos solo con autorizacion explicita:

Dato operativo actual creado por QA manual:

- `cuentas_por_cobrar.id = 1`;
- `cuentas_por_cobrar_movimientos.id = 1`;
- `reservacion_id = 24`;
- `saldo = 4250.00`.

1. Identificar `cuentas_por_cobrar.id`.
2. Validar que la cuenta fue creada por `origen_tipo = reservacion`.
3. Validar que solo tenga movimiento `CREACION` y ningun movimiento posterior.
4. Si no fue usada operativamente, definir anulacion o baja logica autorizada.
5. Si ya tiene movimientos posteriores, no borrar; abrir fase de reconciliacion.

No ejecutar `DELETE` directo sobre CxC sin backup y autorizacion especifica. No tocar
reservaciones, pagos, abonos, facturacion, Caja ni `/api/sync`.

## Rollback Fase 7B-C-F

7B-C-F es cierre documental post-QA.

Rollback:

1. Revertir `docs/fase_7B_C_F_cierre_generacion_manual_cxc.md`.
2. Revertir referencias a 7B-C-F en resumen, cola y auditoria.
3. No tocar la CxC `#1` ni su movimiento `CREACION`; pertenecen a la prueba manual
   validada de 7B-C-A.

## Rollback Fase 7B-D-0

7B-D-0 es solo contrato documental.

Rollback:

1. Revertir `docs/fase_7B_D_0_contrato_cobro_cxc_caja.md`.
2. Retirar referencias a 7B-D-0 en resumen, cola, auditoria y fuentes de verdad.
3. No tocar `cuentas_por_cobrar`.
4. No tocar `cuentas_por_cobrar_movimientos`.
5. No tocar Caja, cortes, movimientos, reservaciones, pagos, abonos, facturacion ni
   `/api/sync`.

DB: no aplica; no hay migraciones ni escrituras.

## Rollback Fase 7B-D-A

7B-D-A agrega solo un simulador GET/read-only de cobro CxC.

Rollback de codigo:

1. Retirar ruta `GET /cuentas-por-cobrar/simulador-caja`.
2. Retirar `CuentaPorCobrarController::simuladorCajaAction()`.
3. Retirar metodos de simulador en `CuentaPorCobrar`.
4. Eliminar vista `cuentas_por_cobrar/simulador_caja.php`.
5. Retirar enlaces al simulador desde vistas CxC operativas.
6. Revertir checks 7B-D-A en `preflight_cuentas_por_cobrar.php`.

DB: no aplica; 7B-D-A no escribe datos ni ejecuta migraciones.

No tocar la CxC `#1`, su movimiento `CREACION`, Caja, cortes, reservaciones, pagos,
abonos, facturacion ni `/api/sync`.

## Rollback Fase 7B-D-F

7B-D-F es cierre documental post-QA.

Rollback:

1. Revertir `docs/fase_7B_D_F_cierre_simulador_cobro_cxc.md`.
2. Retirar referencias a 7B-D-F en resumen, cola, auditoria y fuentes de verdad.
3. No tocar la CxC `#1` ni su movimiento `CREACION`.
4. No tocar Caja, cortes, reservaciones, pagos, abonos, facturacion ni `/api/sync`.

## Rollback Fase 7B-D-B-0

7B-D-B-0 es solo contrato documental de esquema.

Rollback:

1. Revertir `docs/fase_7B_D_B_0_contrato_esquema_cobro_cxc.md`.
2. Retirar referencias a 7B-D-B-0 en resumen, cola, auditoria y fuentes de verdad.
3. No ejecutar `ALTER TABLE`.
4. No crear rutas POST ni botones de cobro.
5. No tocar CxC, Caja, cortes, reservaciones, pagos, abonos, facturacion ni `/api/sync`.

## Rollback Fase 7B-D-B-A

7B-D-B-A agrega `COBRO` al enum
`cuentas_por_cobrar_movimientos.tipo_movimiento`.

Backup previo:

- `backups/medisoft_hoteles_import_before_7b_d_b_a_cxc_cobro_enum_20260618_180420.sql`
- SHA256: `906309EB73EB74B94A62FF493C1B1DAE214F82C02F253AA616C38FFEE3A6C21E`
- Tamano: `1646040` bytes

Rollback DB solo con autorizacion explicita y si no existen movimientos `COBRO`:

1. Confirmar:
   ```sql
   SELECT COUNT(*) AS movimientos_cobro
   FROM cuentas_por_cobrar_movimientos
   WHERE tipo_movimiento = 'COBRO';
   ```
2. Si el conteo es `0`, ejecutar `ALTER TABLE` para volver al enum anterior:
   `CREACION`, `AJUSTE`, `CANCELACION`, `NOTA`, `RECLASIFICACION`.
3. Borrar el registro de `migrations` para
   `20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql`.
4. Ejecutar preflight CxC.

Si existen movimientos `COBRO`, no revertir el enum sin una fase formal de anulacion o
reconciliacion. No tocar Caja, cortes, saldos, reservaciones, pagos, abonos,
facturacion ni `/api/sync`.

## Rollback Fase 7B-D-C-0

7B-D-C-0 es solo contrato documental del servicio transaccional de cobro CxC.

Rollback:

1. Revertir `docs/fase_7B_D_C_0_contrato_servicio_cobro_cxc_caja.md`.
2. Retirar referencias a 7B-D-C-0 en resumen, cola, auditoria y fuentes de verdad.
3. No tocar el enum `COBRO`, porque pertenece a 7B-D-B-A.
4. No tocar la CxC `#1`.
5. No tocar Caja, cortes, saldos, reservaciones, pagos, abonos, facturacion ni
   `/api/sync`.

DB: no aplica; 7B-D-C-0 no escribe datos ni ejecuta migraciones.

## Rollback Fase 7B-D-C-A

7B-D-C-A habilita cobro CxC con Caja mediante servicio transaccional.

Backup previo:

- `backups/medisoft_hoteles_import_before_7b_d_c_a_cxc_cash_service_20260618_232434.sql`
- SHA256: `5708BC91E1BD7A54EFA53A8BA6A92A282ACC98A2AD9237F274AAB87AB11796CD`
- Tamano: `1646216` bytes

Rollback de codigo:

1. Retirar ruta POST
   `/cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja`.
2. Retirar `CuentaPorCobrarController::registrarCobroCajaAction()`.
3. Retirar `CuentaPorCobrarCobroService`.
4. Retirar formulario de cobro del detalle CxC.
5. Retirar `tools/saas/probar_cobro_cxc_caja.php`.
6. Ajustar preflight CxC al contrato anterior.

Rollback de datos si se ejecuto un cobro real:

1. No borrar datos con SQL directo.
2. Identificar movimiento CxC tipo `COBRO`.
3. Identificar movimiento Caja por referencia compartida manual o por referencia
   automatica `CXC-{cuenta_id}-MOV-{movimiento_cxc_id}`.
4. Restaurar saldo/estado solo mediante script transaccional revisado o fase formal de
   anulacion/reversion.
5. Registrar auditoria del rollback.

En el QA manual validado existe cobro real persistente:

- movimiento CxC `#4`;
- movimiento Caja `#1482`;
- referencia compartida `QA-CXC-20260619-001`;
- CxC `#1` fue revertida posteriormente en 7B-D-D-A y queda pendiente con saldo
  `4250.00`.

Si solo se ejecuta la prueba CLI rollback adicional, no deja datos persistentes que
revertir.

No tocar reservaciones, pagos, abonos, facturacion, PWA/offline ni `/api/sync`.

## Rollback Fase 7B-D-D-0

7B-D-D-0 es contrato documental de reversion de cobro CxC.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_7B_D_D_0_contrato_reversion_cobro_cxc.md`.
2. Retirar referencias 7B-D-D-0 de resumen, cola, auditoria y fuentes de verdad.

No ejecutar SQL ni tocar el cobro real `#4`/Caja `#1482` durante rollback documental.

## Rollback Fase 7B-D-D-A

7B-D-D-A implementa reversion de cobro CxC con Caja mediante servicio transaccional.

Backup previo:

- `backups/medisoft_hoteles_import_before_7b_d_d_a_cxc_reversal_20260619_091552.sql`
- SHA256: `9B0157802EF9A959983879CF43505F6ED446613533B0E3A7C4FFCED4BFEEC0AE`
- Tamano: `1649562` bytes

Rollback de codigo:

1. Retirar ruta POST
   `/cuentas-por-cobrar/operativas/{id}/movimientos/{movimientoid}/revertir-cobro-caja`.
2. Retirar `CuentaPorCobrarController::revertirCobroCajaAction()`.
3. Retirar helpers de token `generarReversionCobroToken()` y
   `consumirReversionCobroToken()`.
4. Retirar `CuentaPorCobrarReversionCobroService`.
5. Retirar panel "Reversion de cobros" del detalle CxC.
6. Retirar `tools/saas/probar_reversion_cobro_cxc_caja.php`.
7. Ajustar preflight CxC al contrato anterior.

Rollback de datos:

- La prueba CLI rollback no dejo datos adicionales persistentes.
- La QA manual ya creo una reversion real; no borrar con SQL directo.
- Movimiento CxC real de reversion: `CANCELACION #8`, referencia
  `REV-CXC-1-MOV-4`.
- Movimiento Caja real de reversion: gasto `#1486`, categoria
  `Reversion Cobro CxC`, referencia `REV-CXC-1-MOV-4`.
- Corregir solo mediante fase formal de anulacion de reversion o restauracion
  autorizada con backup.

No tocar reservaciones, pagos, abonos, facturacion, PWA/offline ni `/api/sync`.

## Rollback Fase 7B-D-D-F

7B-D-D-F es cierre documental.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_7B_D_D_F_cierre_reversion_cobro_cxc.md`.
2. Retirar referencias 7B-D-D-F de resumen, cola, auditoria y fuentes de verdad.

No ejecutar SQL ni tocar cobros/reversiones reales durante rollback documental.

## Rollback Fase 8A-0

8A-0 es solo contrato documental.

Rollback:

1. Revertir el commit documental de 8A-0.
2. No tocar `/operacion/diaria`.
3. No tocar Caja, pagos, abonos, nomina ni `/api/sync`.

## Rollback Fase 8A-A

8A-A extiende `/operacion/diaria` con KPIs read-only.

Rollback:

1. Revertir el commit `feat(phase-8a): add read-only operational kpis`.
2. No tocar datos.
3. No tocar `reservaciones`, pagos, abonos ni Caja.
4. No tocar `/api/sync`.

## Rollback Fase 8A-F

8A-F es cierre documental.

Rollback:

1. Revertir el commit documental de cierre.
2. Para retirar funcionalidad, revertir 8A-A.
3. No tocar datos, Caja ni `/api/sync`.

## Rollback Fase 3D-0

3D-0 es solo contrato documental.

Rollback:

1. Revertir el commit documental de 3D-0.
2. No tocar CxP, Caja, cortes ni movimientos.
3. No crear rutas ni migraciones de pago.

## Rollback Fase 3D-A

3D-A agrega solo una pantalla GET/read-only y un preflight.

Rollback:

1. Revertir el commit `feat(phase-3d): add provider cashbox payment preview`.
2. Retirar GET `/cuentas-por-pagar/simulador-caja`.
3. Retirar `CuentaPorPagarController::simuladorCajaAction()`.
4. Retirar los metodos read-only `simuladorCajaProveedor()` y relacionados del modelo.
5. Retirar `app/views/cuentas_por_pagar/simulador_caja.php`.
6. Retirar `src/tools/saas/preflight_pagos_proveedores_caja.php`.
7. No tocar datos, CxP, Caja, cortes, movimientos ni `/api/sync`.

## Rollback Fase 3D-B

3D-B es solo contrato documental del servicio transaccional.

Rollback:

1. Revertir el commit documental de 3D-B.
2. No tocar CxP, Caja, cortes ni movimientos.
3. No crear rutas POST ni botones de pago.
4. No ejecutar pruebas con escritura sin backup.

## Rollback Fase 3D-C

3D-C habilita pago proveedor con Caja mediante servicio transaccional.

Backup previo:

- `backups/medisoft_hoteles_import_before_3d_payments_20260617_105846.sql`
- SHA256:
  `9584636FF545D8370B5E84171A9B1CF4EA2A8637D73285316DC83EB14E499A16`

Rollback de codigo:

1. Revertir el commit `feat(phase-3d): register provider payment with cashbox`.
2. Retirar ruta POST `/cuentas-por-pagar/{id}/registrar-pago-caja`.
3. Retirar `app/services/CuentaPorPagarPagoService.php`.
4. Retirar formulario de pago del detalle de CxP.
5. Retirar `tools/saas/probar_pago_proveedor_caja.php`.

Rollback de datos si se ejecuto un pago real:

1. No borrar datos sin autorizacion financiera.
2. Identificar movimiento CxP en `cuentas_por_pagar_movimientos`.
3. Identificar movimiento Caja asociado por referencia/descripcion/corte.
4. Restaurar saldo/estado de `cuentas_por_pagar` solo con script transaccional revisado.
5. Registrar auditoria del rollback.
6. Si el riesgo es alto, restaurar backup completo en una base separada y reconciliar.

Datos reales de QA 3D-C y estado vigente post 3D-D-A:

- Despues de 3D-C, CxP `#1` quedo `pagada`, saldo `0.00`.
- Despues de 3D-D-A, CxP `#1` queda `parcial`, saldo `10.00`.
- Pago parcial: movimiento CxP `#4`, Caja `#1478`, referencia `CXP-1-MOV-4`.
- Pago total restante: movimiento CxP `#5`, Caja `#1479`, referencia `1212331312`.
- Reversion del pago parcial: movimiento CxP `#9`, Caja `#1494`, referencia
  `REV-CXP-1-MOV-4`.

No borrar esos movimientos con SQL directo.

## Rollback Fase 3D-D-0

3D-D-0 es contrato documental de reversion de pago proveedor con Caja.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_3D_D_0_contrato_reversion_pago_proveedor_caja.md`.
2. Retirar referencias 3D-D-0 de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar pagos reales `#4`/`#5` ni Caja `#1478`/`#1479` durante rollback
documental.

## Rollback Fase 3D-D-A

3D-D-A implementa reversion de pago proveedor con Caja mediante servicio transaccional.

Backup previo:

- `backups/medisoft_hoteles_import_before_3d_d_a_cxp_payment_reversal_20260619_095320.sql`
- SHA256: `3BB71EF0C696E1AB0CB3FC4A4B2E51319DCD654F0CEAA7E83280378F9A9001FB`
- Tamano: `3286902` bytes

Rollback de codigo:

1. Retirar ruta POST
   `/cuentas-por-pagar/{id}/movimientos/{movimientoid}/revertir-pago-caja`.
2. Retirar `CuentaPorPagarController::revertirPagoCajaAction()`.
3. Retirar helpers de token `generarReversionPagoToken()` y
   `consumirReversionPagoToken()`.
4. Retirar `CuentaPorPagarReversionPagoService`.
5. Retirar panel "Reversion de pagos" del detalle CxP.
6. Retirar `tools/saas/probar_reversion_pago_proveedor_caja.php`.
7. Ajustar preflight pagos proveedor y health al contrato anterior.

Rollback de datos:

- La prueba CLI rollback no dejo `CANCELACION` ni ingreso Caja adicionales.
- La QA manual creo una reversion real persistente; no borrar con SQL directo.
- Movimiento CxP de reversion real: `#9`, referencia `REV-CXP-1-MOV-4`.
- Ingreso Caja de reversion real: `#1494`, categoria `Reversion Pago proveedor`.
- Auditoria real: `logs_auditoria #121`.
- Identificar movimiento CxP `CANCELACION` por referencia
  `REV-CXP-{cuenta_id}-MOV-{movimiento_pago_id}`.
- Identificar ingreso Caja `Reversion Pago proveedor` por la misma referencia.
- Corregir solo mediante fase formal de anulacion de reversion o restauracion
  autorizada con backup.

No tocar compras, proveedores, abonos, PWA/offline ni `/api/sync`.

## Rollback Fase 3D-D-F

3D-D-F es cierre documental.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_3D_D_F_cierre_reversion_pago_proveedor_caja.md`.
2. Retirar referencias 3D-D-F de resumen, cola, auditoria y fuentes de verdad.

No ejecutar SQL ni tocar pagos/reversiones reales durante rollback documental.

## Rollback Fase 9A-0

9A-0 es contrato documental read-only.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_9A_0_contrato_conciliacion_financiera_readonly.md`.
2. Retirar referencias 9A-0 de resumen, cola, auditoria y fuentes de verdad.

No ejecutar SQL ni tocar CxC, CxP, Caja, cortes, reservaciones, compras, proveedores,
PWA/offline ni `/api/sync`.

## Rollback Fase 9A-A

9A-A agrega solo un preflight CLI read-only y documentacion.

No crea rutas, vistas, modelos, migraciones ni datos.

Rollback:

1. Retirar `src/tools/saas/preflight_conciliacion_financiera.php`.
2. Retirar `docs/fase_9A_A_preflight_conciliacion_financiera_readonly.md`.
3. Retirar referencias 9A-A de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar CxC, CxP, Caja, cortes, reservaciones, compras, proveedores,
PWA/offline ni `/api/sync`.

## Rollback Fase 9A-B-0

9A-B-0 es contrato documental read-only.

No crea codigo, rutas, vistas, modelos, migraciones ni datos.

Rollback:

1. Retirar
   `docs/fase_9A_B_0_contrato_pantalla_conciliacion_financiera_readonly.md`.
2. Retirar referencias 9A-B-0 de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar CxC, CxP, Caja, cortes, reservaciones, compras, proveedores,
permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 9A-B-A

9A-B-A agrega una pantalla GET/read-only y un lector de conciliacion sin escrituras.

No crea migraciones ni datos.

Rollback de codigo:

1. Retirar ruta GET `/operacion/conciliacion-financiera`.
2. Retirar `OperacionController::conciliacionFinancieraAction()` y
   `filtrosConciliacion()`.
3. Retirar `app/models/ConciliacionFinanciera.php`.
4. Retirar `app/views/operacion/conciliacion_financiera.php`.
5. Retirar enlace desde `app/views/operacion/diaria.php`.
6. Retirar validaciones 9A-B-A agregadas a
   `tools/saas/preflight_conciliacion_financiera.php`.
7. Retirar referencias documentales 9A-B-A.

No ejecutar SQL ni tocar CxC, CxP, Caja, cortes, reservaciones, compras, proveedores,
permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 9A-B-F

9A-B-F es cierre documental.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_9A_B_F_cierre_pantalla_conciliacion_financiera.md`.
2. Retirar referencias 9A-B-F de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar CxC, CxP, Caja, cortes, reservaciones, compras, proveedores,
permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 9C-0

9C-0 es contrato documental read-only.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_9C_0_contrato_arqueo_metodos_pago_readonly.md`.
2. Retirar referencias 9C-0 de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar movimientos de Caja, cortes, cajas, categorias, CxC, CxP,
reservaciones, compras, proveedores, permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 9C-A

9C-A agrega solo un preflight CLI/read-only y documentacion.

No crea rutas, vistas, modelos, migraciones ni datos.

Rollback:

1. Retirar `src/tools/saas/preflight_arqueo_metodos_pago.php`.
2. Retirar `docs/fase_9C_A_preflight_arqueo_metodos_pago_readonly.md`.
3. Retirar validacion 9C-A agregada a `tools/saas/health_check_fase_1a.php`.
4. Retirar referencias 9C-A de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar movimientos de Caja, cortes, cajas, categorias, CxC, CxP,
reservaciones, compras, proveedores, permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 9C-B-0

9C-B-0 es contrato documental read-only.

No crea codigo, rutas, vistas, modelos, migraciones ni datos.

Rollback:

1. Retirar
   `docs/fase_9C_B_0_contrato_pantalla_arqueo_metodos_pago_readonly.md`.
2. Retirar referencias 9C-B-0 de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar movimientos de Caja, cortes, cajas, categorias, CxC, CxP,
reservaciones, compras, proveedores, permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 9C-B-A

9C-B-A agrega una pantalla GET/read-only, un lector dedicado y validaciones
automaticas.

No crea migraciones ni datos.

Rollback:

1. Retirar ruta `GET /caja/arqueo-metodos` de `src/config/routes.php`.
2. Retirar `ArqueoMetodosPago` de `src/app/models/ArqueoMetodosPago.php`.
3. Retirar `CajaController::arqueoMetodosAction()` y la dependencia
   `ArqueoMetodosPago` de `src/app/controllers/CajaController.php`.
4. Retirar `src/app/views/caja/arqueo_metodos.php`.
5. Retirar validaciones 9C-B-A agregadas a
   `src/tools/saas/preflight_arqueo_metodos_pago.php`.
6. Retirar validaciones 9C-B-A agregadas a
   `src/tools/saas/health_check_fase_1a.php`.
7. Retirar `docs/fase_9C_B_A_pantalla_arqueo_metodos_pago_readonly.md`.
8. Retirar referencias 9C-B-A de resumen, cola, auditoria, fuentes de verdad y QA.

Validacion posterior:

- `php -l` en archivos PHP tocados.
- `php tools/saas/preflight_arqueo_metodos_pago.php`.
- `php tools/saas/health_check_fase_1a.php`.

No ejecutar SQL ni tocar movimientos de Caja, cortes, cajas, categorias, CxC, CxP,
reservaciones, compras, proveedores, permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 9C-B-F

9C-B-F es cierre documental.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_9C_B_F_cierre_pantalla_arqueo_metodos_pago.md`.
2. Retirar referencias 9C-B-F de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar movimientos de Caja, cortes, cajas, categorias, CxC, CxP,
reservaciones, compras, proveedores, permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 10A-0

10A-0 es contrato documental read-only.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md`.
2. Retirar referencias 10A-0 de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar Dashboard, reportes, Caja, CxC, CxP, inventario, tareas,
trabajadores, documentos, permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 10A-B-A

10A-B-A agrega pantalla GET/read-only, lector, ruta y validaciones.

No crea migraciones ni datos.

Rollback:

1. Retirar ruta `GET /reportes/ejecutivo` de `src/config/routes.php`.
2. Retirar `ReportesController::ejecutivoAction`, propiedad/modelo
   `tableroEjecutivoModel` y require de `TableroEjecutivo`.
3. Retirar `src/app/models/TableroEjecutivo.php`.
4. Retirar `src/app/views/reportes/ejecutivo.php`.
5. Retirar enlace a `reportes/ejecutivo` de `src/app/views/reportes/index.php`.
6. Retirar validaciones 10A-B-A de
   `src/tools/saas/preflight_tablero_ejecutivo.php`.
7. Retirar validaciones 10A-B-A de `src/tools/saas/health_check_fase_1a.php`.
8. Retirar `docs/fase_10A_B_A_pantalla_tablero_ejecutivo_readonly.md`.
9. Retirar referencias 10A-B-A de resumen, cola, auditoria, fuentes de verdad y QA.

Verificacion posterior:

- `php -l config/routes.php`.
- `php -l app/controllers/ReportesController.php`.
- `php -l tools/saas/preflight_tablero_ejecutivo.php`.
- `php -l tools/saas/health_check_fase_1a.php`.
- `php tools/saas/preflight_tablero_ejecutivo.php`.
- `php tools/saas/health_check_fase_1a.php`.

No ejecutar SQL ni tocar Caja, CxC, CxP, inventario, tareas, documentos, permisos,
auth, PWA/offline ni `/api/sync`.

## Rollback Fase 10A-B-F

10A-B-F es cierre documental con QA manual validada.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_10A_B_F_cierre_tablero_ejecutivo.md`.
2. Restaurar estado de `docs/fase_10A_B_A_pantalla_tablero_ejecutivo_readonly.md` a
   implementada con QA pendiente, si se desea repetir la validacion manual.
3. Retirar referencias 10A-B-F de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar `GET /reportes/ejecutivo`, Dashboard, reportes, Caja, CxC,
CxP, inventario, tareas, trabajadores, documentos, permisos, auth, PWA/offline ni
`/api/sync`.

## Rollback Fase 10B-0

10B-0 es contrato documental para separar reporte gerencial y notificaciones.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_10B_0_contrato_reporte_gerencial_notificaciones.md`.
2. Retirar referencias 10B-0 de resumen, cola, auditoria, fuentes de verdad y QA.
3. Restaurar notas de seguimiento en 10A-B-A y 10A-B-F si se quiere volver al estado
   previo al contrato.

No ejecutar SQL ni tocar `ReportesController`, `NotificacionController`, modelos,
rutas, Caja, CxC, CxP, inventario, tareas, trabajadores, documentos, permisos, auth,
PWA/offline ni `/api/sync`.

## Rollback Fase 10B-A

10B-A separa reporte gerencial directo y archivado de notificaciones.

No crea rutas, migraciones ni datos.

Rollback:

1. Restaurar en `ReportesController` las llamadas de archivado desde
   `gerencialDiarioAction` y `gerencialDiarioPdfAction`.
2. Restaurar el metodo privado `archivarNotificacionReporteGerencialVisto`.
3. Retirar validaciones 10B-A de
   `src/tools/saas/preflight_tablero_ejecutivo.php`.
4. Retirar validaciones 10B-A de `src/tools/saas/health_check_fase_1a.php`.
5. Retirar `docs/fase_10B_A_separacion_reporte_gerencial_notificaciones.md`.
6. Retirar referencias 10B-A de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar modelos de negocio, rutas, vistas, formularios, Caja, CxC,
CxP, inventario, tareas, trabajadores, documentos, permisos, auth, PWA/offline ni
`/api/sync`.

## Rollback Fase 10B-F

10B-F es cierre documental con QA manual validada.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_10B_F_cierre_reporte_gerencial_notificaciones.md`.
2. Restaurar estado de `docs/fase_10B_A_separacion_reporte_gerencial_notificaciones.md`
   a implementada con QA pendiente, si se desea repetir la validacion manual.
3. Retirar referencias 10B-F de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar `ReportesController`, `NotificacionController`, modelos,
rutas, Caja, CxC, CxP, inventario, tareas, trabajadores, documentos, permisos, auth,
PWA/offline ni `/api/sync`.

## Rollback Fase 11A-0

11A-0 es contrato documental de perfil operativo de huesped read-only.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_11A_0_contrato_perfil_huesped_readonly.md`.
2. Retirar referencias 11A-0 de resumen, cola, auditoria, fuentes de verdad y QA.
3. Restaurar la nota de siguiente bloque en
   `docs/fase_10B_F_cierre_reporte_gerencial_notificaciones.md` si se quiere volver
   al estado previo.

No ejecutar SQL ni tocar `HuespedController`, `Huesped`, vistas de huespedes, rutas,
reservaciones, Caja, CxC, documentos, tareas, permisos/auth, PWA/offline ni
`/api/sync`.

## Rollback Fase 11A-A

11A-A agrega un bloque read-only en la ficha de huesped y checks en health.

No crea rutas, migraciones ni datos.

Rollback:

1. Retirar el bloque `guest-readonly-profile` de
   `src/app/views/huespedes/ver.php`.
2. Retirar variables auxiliares `$perfilOperativo`, `$perfilReservaciones`,
   `$perfilVehiculos`, `$perfilCxc`, `$perfilDocumentos` y `$perfilAlertas` de la
   vista si ya no se usan.
3. Retirar de `HuespedController::verAction` la llamada a
   `perfilOperativoReadOnlyPorHotel()` y el parametro `perfilOperativo` enviado a la
   vista.
4. Retirar `perfilOperativoReadOnlyPorHotel()` y metodos auxiliares agregados en
   `src/app/models/Huesped.php`.
5. Retirar validaciones 11A-A de `src/tools/saas/health_check_fase_1a.php`.
6. Retirar `docs/fase_11A_A_perfil_huesped_readonly.md`.
7. Retirar referencias 11A-A de resumen, cola, auditoria, fuentes de verdad y QA.

Verificacion posterior:

- `php -l app/models/Huesped.php`.
- `php -l app/controllers/HuespedController.php`.
- `php -l app/views/huespedes/ver.php`.
- `php -l tools/saas/health_check_fase_1a.php`.
- `php tools/saas/health_check_fase_1a.php`.

No ejecutar SQL ni tocar reservaciones, Caja, CxC, documentos, tareas, permisos/auth,
PWA/offline ni `/api/sync`.

## Rollback Fase 11A-F

11A-F es cierre documental con QA manual validada.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_11A_F_cierre_perfil_huesped_readonly.md`.
2. Restaurar estado de `docs/fase_11A_A_perfil_huesped_readonly.md` a implementada
   con QA pendiente, si se desea repetir la validacion manual.
3. Retirar referencias 11A-F de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar `GET /huespedes/{id}`, `HuespedController`, `Huesped`,
vistas de huespedes, reservaciones, Caja, CxC, documentos, tareas, permisos/auth,
PWA/offline ni `/api/sync`.

## Rollback Fase 5E-0

5E-0 es contrato documental de pagos laborales con Caja.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_5E_0_contrato_pagos_laborales_caja.md`.
2. Retirar referencias 5E-0 de resumen, cola, auditoria, fuentes de verdad y QA.
3. Restaurar la nota de siguiente bloque en 11A-F si se quiere volver al estado
   previo.

No ejecutar SQL ni tocar `trabajadores`, `trabajador_pagos`,
`trabajador_anticipos`, `trabajador_prestamos`, Caja, cortes, movimientos de Caja,
permisos/auth, PWA/offline ni `/api/sync`.

## Rollback Fase 5E-A

5E-A agrega una herramienta CLI/read-only y validacion en health.

No crea rutas, migraciones ni datos.

Rollback:

1. Retirar `src/tools/saas/preflight_personal_pagos_caja.php`.
2. Retirar la variable `$workerCashPaymentPreflight` y el bloque de validacion 5E-A
   de `src/tools/saas/health_check_fase_1a.php`.
3. Restaurar el encabezado del health si se quiere quitar la mencion `5E-A`.
4. Retirar `docs/fase_5E_A_preflight_pagos_laborales_caja.md`.
5. Retirar referencias 5E-A de resumen, cola, auditoria, fuentes de verdad, QA y
   del contrato 5E-0.

Verificacion posterior:

- `php -l tools/saas/health_check_fase_1a.php`.
- `php tools/saas/health_check_fase_1a.php`.

No ejecutar SQL ni tocar `trabajadores`, `trabajador_pagos`,
`trabajador_anticipos`, `trabajador_prestamos`, Caja, cortes, movimientos de Caja,
permisos/auth, PWA/offline ni `/api/sync`.

## Rollback Fase 5E-B-0

5E-B-0 es contrato documental de migracion aditiva para pagos laborales con Caja.

No crea SQL, migracion, tabla ni datos.

Rollback:

1. Retirar `docs/fase_5E_B_0_contrato_migracion_pagos_laborales_caja.md`.
2. Retirar referencias 5E-B-0 de resumen, cola, auditoria, fuentes de verdad y QA.
3. Retirar el seguimiento 5E-B-0 en
   `docs/fase_5E_A_preflight_pagos_laborales_caja.md`.
4. Retirar el seguimiento 5E-B-0 en
   `docs/fase_5E_0_contrato_pagos_laborales_caja.md`.

No ejecutar SQL ni tocar `trabajadores`, `trabajador_pagos`,
`trabajador_anticipos`, `trabajador_prestamos`, Caja, cortes, movimientos de Caja,
permisos/auth, PWA/offline ni `/api/sync`.

Si una futura 5E-B-A crea `trabajador_pagos_caja`, su rollback debe documentarse en
esa fase y solo podra eliminar la tabla si esta vacia y hay autorizacion explicita
para tocar DB.

## Rollback Fase 5E-B-A

5E-B-A crea la tabla `trabajador_pagos_caja` como migracion aditiva.

Backup previo:

- `backups/medisoft_hoteles_import_before_5e_b_a_trabajador_pagos_caja_20260619_220444.sql`.
- SHA256:
  `2E279999DA95C0216942F1FE480E5E43E96AAE42A06DA7C9FD83633BC53898BC`.

Rollback permitido solo con autorizacion explicita y si la tabla sigue vacia.

Verificacion previa:

```sql
SELECT COUNT(*) AS pagos_laborales_caja
FROM trabajador_pagos_caja;
```

Si el conteo es `0`, rollback conceptual:

```sql
DROP TABLE trabajador_pagos_caja;

DELETE FROM migrations
WHERE nombre = '20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql';
```

Verificacion posterior:

- `php tools/saas/preflight_personal_pagos_caja.php`.
- `php tools/saas/health_check_fase_1a.php`.

Si existen pagos laborales reales en el futuro, no eliminar la tabla. Se requiere fase
formal de reversion/reconciliacion.

No tocar `trabajador_pagos`, `trabajador_anticipos`, `trabajador_prestamos`,
movimientos de Caja, categorias de Caja, permisos/auth, PWA/offline ni `/api/sync`.

## Rollback Fase 5E-C-0

5E-C-0 es contrato documental de simulador GET/read-only de pago laboral con Caja.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_5E_C_0_contrato_simulador_pago_laboral_caja.md`.
2. Retirar referencias 5E-C-0 de resumen, cola, auditoria, fuentes de verdad y QA.
3. Retirar el seguimiento 5E-C-0 en
   `docs/fase_5E_B_A_migracion_pagos_laborales_caja.md`.
4. Retirar el seguimiento 5E-C-0 en
   `docs/fase_5E_0_contrato_pagos_laborales_caja.md`.

No ejecutar SQL ni tocar `trabajador_pagos_caja`, `trabajador_pagos`,
`trabajador_anticipos`, `trabajador_prestamos`, Caja, cortes, movimientos de Caja,
permisos/auth, PWA/offline ni `/api/sync`.

## Rollback Fase 5E-C-A

5E-C-A agrega simulador GET/read-only de pago laboral con Caja.

No crea migraciones, no escribe datos y no registra pagos reales.

Rollback:

1. Retirar ruta `GET /trabajadores/pagos-caja/simulador`.
2. Retirar `TrabajadorController::simuladorPagoCajaAction()`.
3. Retirar metodos read-only de simulador en `Trabajador`.
4. Eliminar `src/app/views/trabajadores/simulador_pago_caja.php`.
5. Retirar enlaces `Simulador Caja` de `trabajadores/index.php` y
   `trabajadores/ver.php`.
6. Retirar validaciones 5E-C-A de
   `src/tools/saas/preflight_personal_pagos_caja.php`.
7. Retirar validaciones 5E-C-A de `src/tools/saas/health_check_fase_1a.php`.
8. Retirar `docs/fase_5E_C_A_simulador_pago_laboral_caja.md` y referencias en
   resumen, cola, auditoria, fuentes de verdad y QA.

Verificacion posterior:

- `php -l` en archivos PHP tocados.
- `php tools/saas/preflight_personal_pagos_caja.php`.
- `php tools/saas/health_check_fase_1a.php`.

No ejecutar SQL ni tocar `trabajador_pagos_caja`, `trabajador_pagos`,
`trabajador_anticipos`, `trabajador_prestamos`, Caja, cortes, movimientos de Caja,
permisos/auth, PWA/offline ni `/api/sync`.

## Rollback Fase 10A-B-0

10A-B-0 es contrato documental de pantalla read-only.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_10A_B_0_contrato_pantalla_tablero_ejecutivo_readonly.md`.
2. Retirar referencias 10A-B-0 de resumen, cola, auditoria, fuentes de verdad y QA.
3. Restaurar la nota de siguiente paso en
   `docs/fase_10A_A_preflight_tablero_ejecutivo_readonly.md` si se quiere volver al
   estado previo al contrato de pantalla.

No ejecutar SQL ni tocar Dashboard, reportes, Caja, CxC, CxP, inventario, tareas,
trabajadores, documentos, permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 10A-A

10A-A agrega una herramienta CLI/read-only y validacion en health.

No crea rutas, migraciones ni datos.

Rollback:

1. Retirar `src/tools/saas/preflight_tablero_ejecutivo.php`.
2. Retirar validaciones 10A-A de `src/tools/saas/health_check_fase_1a.php`.
3. Retirar `docs/fase_10A_A_preflight_tablero_ejecutivo_readonly.md`.
4. Retirar referencias 10A-A de resumen, cola, auditoria, fuentes de verdad y QA.

Verificacion posterior:

- `php -l tools/saas/health_check_fase_1a.php`.
- `php tools/saas/health_check_fase_1a.php`.

No ejecutar SQL ni tocar Dashboard, reportes, Caja, CxC, CxP, inventario, tareas,
trabajadores, documentos, permisos, auth, PWA/offline ni `/api/sync`.

## Rollback Fase 5E-C-F

5E-C-F es cierre documental del simulador validado manualmente.

Rollback:

1. Retirar `docs/fase_5E_C_F_cierre_simulador_pago_laboral_caja.md`.
2. Retirar referencias 5E-C-F de resumen, cola, auditoria, fuentes de verdad y QA.

No ejecutar SQL ni tocar codigo operativo.

## Rollback Fase 5E-D-0

5E-D-0 es contrato documental del servicio futuro de pago laboral con Caja.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_5E_D_0_contrato_servicio_pago_laboral_caja.md`.
2. Retirar referencias 5E-D-0 de resumen, cola, auditoria, fuentes de verdad y QA.
3. Restaurar la nota de siguiente paso en `docs/fase_5E_C_F_cierre_simulador_pago_laboral_caja.md` si se quiere volver al cierre sin contrato de servicio.

No ejecutar SQL ni tocar `trabajador_pagos_caja`, `trabajador_pagos`, `trabajador_anticipos`, `trabajador_prestamos`, Caja, cortes, movimientos de Caja, permisos/auth, PWA/offline ni `/api/sync`.

## Rollback Fase 5E-D-A

5E-D-A agrega servicio, ruta POST, panel en ficha de trabajador, herramienta rollback y validaciones.

Rollback de codigo:

1. Retirar `src/app/services/TrabajadorPagoCajaService.php`.
2. Retirar `POST /trabajadores/{id}/registrar-pago-caja` de `src/config/routes.php`.
3. Retirar de `TrabajadorController` la propiedad/instancia `pagoCajaService`, `registrarPagoCajaAction`, tokens y datos de pago Caja.
4. Retirar el panel `Pago laboral con Caja` de `src/app/views/trabajadores/ver.php`.
5. Revertir el ajuste del simulador que descuenta `pagos_caja_total` si se desea volver a 5E-C-A exacto.
6. Retirar `src/tools/saas/probar_pago_laboral_caja.php`.
7. Revertir validaciones 5E-D-A en preflight y health.
8. Retirar `docs/fase_5E_D_A_pago_laboral_caja_controlado.md` y referencias documentales.

Rollback de datos si hubo QA manual real:

- No borrar directo sin autorizacion.
- Identificar `trabajador_pagos_caja.id`, `movimiento_caja_id`, `corte_id`, referencia y auditoria.
- En esta fase no existe reversion automatica; cualquier correccion debe hacerse con plan manual separado y backup.

Verificacion posterior:

- `docker compose exec app php -l` en PHP tocados.
- `docker compose exec app php tools/saas/preflight_personal_pagos_caja.php`.
- `docker compose exec app php tools/saas/health_check_fase_1a.php`.

No tocar PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Rollback Fase 5E-D-F

5E-D-F es cierre documental del pago laboral con Caja validado manualmente.

Rollback:

1. Retirar `docs/fase_5E_D_F_cierre_pago_laboral_caja.md`.
2. Retirar referencias 5E-D-F de resumen, cola, auditoria, fuentes de verdad, QA y
   rollback.
3. Restaurar la nota de siguiente paso en
   `docs/fase_5E_D_A_pago_laboral_caja_controlado.md` si se quiere volver a la fase
   con QA manual pendiente.

No ejecutar SQL ni tocar codigo operativo.

## Rollback Fase 5E-E-F

5E-E-F es cierre documental de historial, reversion, reporte y export CSV de pagos
laborales con Caja validados manualmente.

Rollback:

1. Retirar `docs/fase_5E_E_F_cierre_historial_reversion_reporte_pagos_laborales_caja.md`.
2. Retirar referencias 5E-E-F de resumen, QA y rollback.
3. Restaurar la nota de siguiente paso en `docs/resumen-ejecutivo-cola.md` si se quiere
   volver al estado previo al cierre documental.

No ejecutar SQL ni tocar codigo operativo. No revertir pagos laborales, movimientos de
Caja ni auditorias desde este rollback documental.

## Rollback Fase 5E-G-0

5E-G-0 es contrato documental del preview read-only de nomina por periodo.

No crea codigo, rutas, migraciones ni datos.

Rollback:

1. Retirar `docs/fase_5E_G_0_contrato_nomina_periodo_preview.md`.
2. Retirar referencias 5E-G-0 de resumen, QA y rollback.
3. Restaurar la nota de siguiente paso en
   `docs/fase_5E_E_F_cierre_historial_reversion_reporte_pagos_laborales_caja.md` si
   se quiere volver al cierre previo sin contrato de preview de nomina.

No ejecutar SQL ni tocar `trabajadores`, `trabajador_pagos`,
`trabajador_anticipos`, `trabajador_prestamos`, `trabajador_pagos_caja`, Caja,
cortes, movimientos de Caja, permisos/auth, PWA/offline ni `/api/sync`.

## Rollback Fase 5E-G-A

5E-G-A agrega preview GET/read-only de nomina por periodo.

Rollback de codigo:

1. Retirar `GET /trabajadores/nomina/preview` de `src/config/routes.php`.
2. Retirar `nominaPreviewAction` y `filtrosNominaPreviewDesdeQuery` de
   `src/app/controllers/TrabajadorController.php`.
3. Retirar de `src/app/models/Trabajador.php` los metodos de preview:
   `tablasNominaPreviewDisponibles`, `nominaPreviewPorHotel` y helpers privados
   asociados a `NominaPreview`.
4. Retirar `src/app/views/trabajadores/nomina_preview.php`.
5. Retirar enlace `Pre-nomina` de `src/app/views/trabajadores/index.php`.
6. Revertir validaciones 5E-G-A en
   `src/tools/saas/preflight_personal_pagos_caja.php`.
7. Revertir ruta esperada/firma permitida 5E-G-A en
   `src/tools/saas/health_check_fase_1a.php`.
8. Retirar `docs/fase_5E_G_A_nomina_periodo_preview_read_only.md` y referencias
   documentales.

Verificacion posterior:

- `docker compose exec -T app php -l` en PHP tocados.
- `docker compose exec -T app php tools/saas/preflight_personal_pagos_caja.php`.
- `docker compose exec -T app php tools/saas/health_check_fase_1a.php`.

No ejecutar SQL ni tocar datos. Esta fase no crea nomina, pagos, movimientos de Caja,
recibos, dispersion ni auditorias.
