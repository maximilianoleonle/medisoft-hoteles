# Fase 7B-D-F - Cierre simulador cobro CxC con Caja

## Estado

`BLOQUE_7B_D_SIMULADOR_COBRO_CXC_CERRADO_QA_VALIDADA`

## Objetivo

Cerrar documentalmente el bloque 7B-D de diagnostico previo a cobros CxC contra Caja,
sin autorizar todavia cobros reales ni migraciones de esquema.

## Alcance cerrado

7B-D-0 dejo definido el contrato del cobro futuro de CxC contra Caja.

7B-D-A implemento el simulador `GET /cuentas-por-cobrar/simulador-caja` para revisar
CxC operativas, corte abierto y Caja activa por `hotel_id`.

## Resultado

- La pantalla es solo lectura.
- No hay formularios `POST`.
- No hay botones de cobro.
- No se modifican saldos CxC.
- No se crean movimientos CxC nuevos.
- No se crean movimientos de Caja.
- No se modifican cortes.
- `/api/sync` sigue fuera de alcance.

## QA manual

El usuario reporto que la prueba manual paso correctamente.

Evidencia consolidada:

- CxC operativa vigente: `cuentas_por_cobrar.id = 1`.
- Movimiento interno vigente: `cuentas_por_cobrar_movimientos.id = 1`, tipo
  `CREACION`.
- El simulador muestra la CxC y mantiene bloqueo para cobro real.
- El bloqueo esperado se debe a que aun no existe tipo semantico `COBRO`.
- No hay movimientos de Caja relacionados con CxC.

## Validaciones post-QA

- Preflight CxC: `OK: 33`, `WARNING: 3`, `ERROR: 0`.
- HTTP sin sesion a `/cuentas-por-cobrar/simulador-caja`: `303` a login.
- Conteos post-QA:
  - `cuentas_por_cobrar = 1`;
  - `cuentas_por_cobrar_movimientos = 1`;
  - auditoria de generacion CxC = `1`;
  - Caja-CxC textual = `0`.

## Riesgo residual

La CxC `#1` ya es dato operativo real. No debe borrarse ni modificarse sin fase formal
de anulacion, rollback o reconciliacion.

El cobro real sigue bloqueado porque `cuentas_por_cobrar_movimientos.tipo_movimiento`
no contiene un tipo de cobro. No se debe usar `AJUSTE` para representar cobros.

## Siguiente paso seguro

7B-D-B-A ya agrego el tipo semantico `COBRO` al ledger CxC.

El siguiente paso seguro es 7B-D-C-0: contrato del servicio transaccional de cobro CxC
con Caja.

No implementar cobro real hasta tener contrato de servicio, backup, prueba rollback,
token de un solo uso, auditoria y QA manual especifica.
