# Fase 7B-D-D-F - Cierre reversion cobro CxC con Caja

## Estado

`CIERRE_7B_D_D_F_REVERSION_COBRO_CXC_CAJA_COMPLETADO`

## Objetivo

Cerrar formalmente el bloque de cobro CxC con Caja y reversion de cobro CxC despues de
la validacion manual exitosa de 7B-D-C-A y 7B-D-D-A.

## Alcance

Este cierre es documental.

No agrega:

- codigo PHP;
- rutas;
- formularios;
- migraciones;
- escrituras de DB;
- cambios en Caja;
- cambios en reservaciones;
- cambios en pagos, abonos o facturacion;
- cambios en PWA/offline o `/api/sync`.

## Bloques cubiertos

- 7B-D-0 contrato/diagnostico de cobro CxC con Caja.
- 7B-D-A simulador Caja read-only de cobro CxC.
- 7B-D-B-0 contrato de esquema de cobro.
- 7B-D-B-A migracion aditiva del tipo `COBRO`.
- 7B-D-C-0 contrato del servicio transaccional.
- 7B-D-C-A cobro CxC con Caja validado manualmente.
- 7B-D-D-0 contrato de reversion de cobro.
- 7B-D-D-A reversion de cobro CxC validada manualmente.

## Evidencia final

- CxC: `#1`, hotel `4`, estado `pendiente`, saldo `4250.00`.
- Movimiento inicial CxC: `#1`, tipo `CREACION`, referencia `CXC-RES-24`.
- Cobro real validado: movimiento CxC `#4`, tipo `COBRO`, monto `1.00`,
  referencia `QA-CXC-20260619-001`.
- Caja del cobro: movimiento `#1482`, tipo `ingreso`, categoria `Cobro CxC`,
  referencia `QA-CXC-20260619-001`.
- Reversion real validada: movimiento CxC `#8`, tipo `CANCELACION`, monto `1.00`,
  referencia `REV-CXC-1-MOV-4`.
- Caja de reversion: movimiento `#1486`, tipo `gasto`,
  categoria `Reversion Cobro CxC`, referencia `REV-CXC-1-MOV-4`.
- Auditoria de reversion: `logs_auditoria #113`.

## Validaciones de cierre

- `php tools/saas/preflight_cuentas_por_cobrar.php`:
  - `OK: 45`;
  - `WARNING: 6`;
  - `ERROR: 0`;
  - advertencias esperadas por datos historicos y los movimientos reales validados.
- `php tools/saas/health_check_fase_1a.php`:
  - `OK: 278`;
  - `WARNING: 25`;
  - `ERROR: 0`.

## Decision de cierre

El bloque queda cerrado para operacion controlada individual:

- generacion manual de CxC desde reservacion elegible;
- cobro manual total/parcial con Caja;
- reversion manual total de un cobro CxC;
- trazabilidad CxC/Caja/auditoria;
- bloqueo de doble cobro/reversion mediante token y referencia;
- pruebas rollback no persistentes.

## Limites despues del cierre

No avanzar desde este cierre a:

- cobros masivos;
- reversiones masivas;
- reversiones parciales;
- automatizaciones desde reservaciones;
- conciliaciones automaticas;
- cambios automaticos de facturacion;
- cambios de corte/caja masivos.

Cualquier ampliacion debe abrir contrato independiente con backup previo, prueba rollback
y autorizacion explicita.
