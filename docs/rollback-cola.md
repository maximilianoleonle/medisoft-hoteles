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

- Rollback de codigo: revertir commit de generacion (`feat(phase-3c): generate payable from received purchase` cuando exista).
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

- `src/storage/backups/phase3c_b_20260615_053711_before_manual_cxp_medisoft_hoteles_import_notablespaces.sql`
- SHA256: `8086F91DF17DB09CFBB28E7E12BED475FDD81FB538948F4B60141A90BE9E801D`
- tamano: `1528988`

Dato creado en la prueba local:

- `cuentas_por_pagar.id = 1`
- `compra_id = 5`
- `hotel_id = 4`
- `total = saldo = 1000.00`

Si se decide retirar ese dato de prueba, no hacer `DELETE` directo sin autorizacion; restaurar backup o acordar una estrategia de anulacion/reconciliacion.

### 3C-C validaciones, health y preflights

- Rollback de codigo/docs: revertir el commit `test(phase-3c): add payable consistency checks` si alguna regla genera falsos positivos bloqueantes.
- Rollback de datos: no aplica; la fase solo ejecuta consultas de lectura.
- No se crean nuevas rutas, vistas, pagos, abonos ni movimientos de Caja.
- Validacion posterior:
  - `php -l` en health/preflights;
  - health checker;
  - preflight de compras minimas;
  - preflight de recepcion de compras;
  - SQL read-only de consistencia CxP;
  - `git diff --check`.
- Si alguna validacion detecta datos inconsistentes, no corregir con `UPDATE`/`DELETE` sin nueva autorizacion y backup.

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
