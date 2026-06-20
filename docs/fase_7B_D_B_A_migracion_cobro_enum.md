# Fase 7B-D-B-A - Migracion tipo COBRO CxC

## Estado

`MIGRACION_7B_D_B_A_COBRO_ENUM_COMPLETADA`

## Objetivo

Agregar el tipo semantico `COBRO` a
`cuentas_por_cobrar_movimientos.tipo_movimiento` para preparar un futuro servicio
transaccional de cobro CxC contra Caja.

Esta fase solo modifica el esquema del enum. No registra cobros, no cambia saldos, no
crea movimientos CxC tipo `COBRO`, no crea movimientos de Caja y no modifica cortes.

## Backup previo

- Archivo:
  `backups/medisoft_hoteles_import_before_7b_d_b_a_cxc_cobro_enum_20260618_180420.sql`
- SHA256:
  `906309EB73EB74B94A62FF493C1B1DAE214F82C02F253AA616C38FFEE3A6C21E`
- Tamano: `1646040` bytes

El primer intento de backup fallo por comillas de PowerShell y no genero archivo valido.
No se aplico ningun cambio de base antes del backup verificado anterior.

## Migracion aplicada

- Archivo:
  `migrations/20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql`
- Registro en `migrations`:
  - `batch = 22`;
  - `estado = ejecutada`;
  - `ejecutada_en = 2026-06-19 00:05:20`.

Cambio aplicado:

```sql
ALTER TABLE cuentas_por_cobrar_movimientos
    MODIFY tipo_movimiento ENUM(
        'CREACION',
        'AJUSTE',
        'CANCELACION',
        'NOTA',
        'RECLASIFICACION',
        'COBRO'
    ) NOT NULL;
```

La migracion es idempotente: si `COBRO` ya existe, solo valida el estado.

## Evidencia post-migracion

Enum actual:

```text
enum('CREACION','AJUSTE','CANCELACION','NOTA','RECLASIFICACION','COBRO')
```

Conteos:

- `cuentas_por_cobrar = 1`;
- `cuentas_por_cobrar_movimientos = 1`;
- `cuentas_por_cobrar_movimientos.tipo_movimiento = COBRO`: `0`;
- auditoria de generacion CxC = `1`;
- movimientos de Caja con referencia textual a CxC = `0`.

## Preflight actualizado

`src/tools/saas/preflight_cuentas_por_cobrar.php` ahora valida:

- que `tipo_movimiento` incluya `COBRO`;
- que la migracion 7B-D-B-A este registrada como `ejecutada`;
- que todavia no existan movimientos CxC tipo `COBRO` antes del servicio transaccional.

Resultado:

- `OK: 36`;
- `WARNING: 3`;
- `ERROR: 0`.

## Validaciones finales

- `php -l tools/saas/preflight_cuentas_por_cobrar.php`: OK.
- Health general:
  - `OK: 277`;
  - `WARNING: 25`;
  - `ERROR: 0`.
- HTTP sin sesion a `/cuentas-por-cobrar/simulador-caja`: `303` a login.
- `git diff --check`: sin errores; solo avisos CRLF del entorno.
- Health confirma que `/api/sync` sigue registrado y bloqueado en codigo con
  `sync_temporarily_disabled` + HTTP `423`.

## Escrituras realizadas

Unicamente:

- cambio de esquema sobre `cuentas_por_cobrar_movimientos.tipo_movimiento`;
- registro de migracion en `migrations`.

## Escrituras no realizadas

No se escribio en:

- `cuentas_por_cobrar`;
- `cuentas_por_cobrar_movimientos` como datos de cobro;
- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `reservaciones`;
- `reservacion_pagos`;
- `reservacion_abonos`;
- `solicitudes_factura`;
- PWA/offline;
- `/api/sync`.

## Rollback

Solo con autorizacion explicita y si no existen movimientos `COBRO` reales:

1. Confirmar:
   ```sql
   SELECT COUNT(*) AS movimientos_cobro
   FROM cuentas_por_cobrar_movimientos
   WHERE tipo_movimiento = 'COBRO';
   ```
2. Si el conteo es `0`, revertir el enum al estado anterior.
3. Eliminar el registro de `migrations` para
   `20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql`.

Si existen movimientos `COBRO`, no revertir el enum sin fase formal de anulacion o
reconciliacion.

## Siguiente paso seguro

7B-D-C-0: contrato del servicio transaccional de cobro CxC con Caja.

Ese contrato debe definir validaciones, orden transaccional, referencia unica,
actualizacion de saldo/estado, movimiento Caja, auditoria, prueba rollback y QA manual
antes de implementar un `POST` real.

## Implementacion posterior

7B-D-C-0 fue creado posteriormente como contrato documental.

Ver:

- `docs/fase_7B_D_C_0_contrato_servicio_cobro_cxc_caja.md`

El cobro real sigue pendiente de autorizacion explicita de escrituras financieras.
