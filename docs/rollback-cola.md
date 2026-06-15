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

`src/app/views/reservaciones/ver.php` tiene un ajuste visual fuera del commit de Fase 3B.

Rollback de ese cambio, si el usuario lo autoriza despues:

1. Revisar diff actual.
2. Si se decide descartar, revertir solo ese archivo con un comando no destructivo y explicito.
3. Si se decide conservar, hacer commit separado despues de `php -l` y prueba visual del modal.

No mezclar este archivo con rollback de CxP.
