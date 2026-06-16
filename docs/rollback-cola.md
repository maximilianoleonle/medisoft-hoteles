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

- Estado vigente: `REVISION_TECNICA_4A_COMPLETADA`.
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

### Reglas duras de rollback NP

- No borrar ni alterar `usuarios` ni `hotel_usuarios` durante ningun rollback NP.
- No tocar Caja, cortes ni movimientos durante ningun rollback NP.
- No hacer reset destructivo de Git ni push.
