# Fase 7B-D-D-A - Reversion cobro CxC con Caja

## Estado

`REVERSION_COBRO_CXC_CAJA_7B_D_D_A_VALIDADA_MANUALMENTE`

## Objetivo

Implementar la reversion controlada de un cobro CxC registrado contra Caja.

La implementacion quedo validada con prueba rollback y QA manual real en navegador.

## Backup previo

- Archivo:
  `backups/medisoft_hoteles_import_before_7b_d_d_a_cxc_reversal_20260619_091552.sql`
- SHA256:
  `9B0157802EF9A959983879CF43505F6ED446613533B0E3A7C4FFCED4BFEEC0AE`
- Tamano: `1649562` bytes

## Alcance implementado

- Servicio transaccional:
  `src/app/services/CuentaPorCobrarReversionCobroService.php`.
- Ruta POST controlada:
  `/cuentas-por-cobrar/operativas/{id}/movimientos/{movimientoid}/revertir-cobro-caja`.
- Controlador actualizado:
  `CuentaPorCobrarController::revertirCobroCajaAction()`.
- Token de reversion de un solo uso por cuenta/movimiento/sesion.
- Panel "Reversion de cobros" en:
  `src/app/views/cuentas_por_cobrar/ver_operativa.php`.
- Prueba rollback:
  `src/tools/saas/probar_reversion_cobro_cxc_caja.php`.
- Preflight CxC actualizado para validar superficie 7B-D-D-A y consistencia entre
  `CANCELACION` y gasto Caja.

## Reglas implementadas

La reversion exige:

- sesion activa;
- contexto hotelero;
- modulo `reservaciones`;
- modulo `caja` en el POST;
- CSRF;
- token de reversion valido y no reutilizado;
- CxC por `id + hotel_id`;
- movimiento `COBRO` por `id + cuenta + hotel`;
- ingreso Caja original asociado;
- ausencia de reversion previa;
- corte de Caja abierto;
- caja activa;
- motivo obligatorio;
- tipo semantico `CANCELACION` disponible;
- saldo posterior no mayor al total de la CxC.

## Escrituras del servicio

El servicio escribe atomica y transaccionalmente:

- `cuentas_por_cobrar_movimientos` con `tipo_movimiento = CANCELACION`;
- `movimientos_caja` con `tipo = gasto` y `categoria = Reversion Cobro CxC`;
- `cuentas_por_cobrar.saldo`;
- `cuentas_por_cobrar.estado`;
- `logs_auditoria` con `cuentas_por_cobrar.cobro_caja_revertido`.

No borra ni edita el cobro original.

## Referencia

La reversion usa referencia canonica:

```text
REV-CXC-{cuenta_id}-MOV-{movimiento_cobro_id}
```

Para el cobro real validado:

```text
REV-CXC-1-MOV-4
```

## QA manual validada

El usuario valido en navegador la reversion real del cobro `COBRO #4`.

Datos persistidos post-QA:

- CxC `#1`: estado `pendiente`, saldo `4250.00`.
- Movimiento CxC `#4`: `COBRO`, monto `1.00`, referencia
  `QA-CXC-20260619-001`.
- Movimiento Caja `#1482`: tipo `ingreso`, categoria `Cobro CxC`, monto `1.00`,
  referencia `QA-CXC-20260619-001`.
- Movimiento CxC `#8`: `CANCELACION`, monto `1.00`, referencia
  `REV-CXC-1-MOV-4`, saldo anterior `4249.00`, saldo posterior `4250.00`.
- Movimiento Caja `#1486`: tipo `gasto`, categoria `Reversion Cobro CxC`,
  monto `1.00`, referencia `REV-CXC-1-MOV-4`, corte `#237`.
- Auditoria `#113`: `cuentas_por_cobrar.cobro_caja_revertido`.

La ruta fue corregida para usar el parametro interno lowercase `movimientoid`, evitando
el `404` observado inicialmente por el usuario.

## Prueba rollback

Comando:

```bash
php tools/saas/probar_reversion_cobro_cxc_caja.php
```

Resultado post-QA manual:

- Cuenta elegida: CxC `#1`, hotel `4`, saldo `4250.00`.
- Corte abierto detectado: `#237`.
- Movimiento CxC `COBRO` temporal: `#10`.
- Movimiento CxC `CANCELACION` temporal: `#11`.
- Movimiento Caja ingreso temporal: `#1488`.
- Movimiento Caja gasto temporal: `#1489`.
- Saldo temporal: `4250.00`.
- Doble reversion bloqueada dentro de la transaccion.
- Rollback confirmado: `COBRO`, `CANCELACION`, Caja, auditoria, saldo y estado
  quedaron iguales.

La prueba ahora crea su propio cobro temporal dentro de la transaccion, lo revierte y
hace rollback. No depende de que exista un `COBRO` real sin reversion previa.

## Conteos finales post-QA

- CxC `#1`: estado `pendiente`, saldo `4250.00`.
- movimientos `COBRO`: `1`.
- movimientos `CANCELACION` de reversion: `1`.
- ingresos Caja `Cobro CxC`: `1`.
- gastos Caja `Reversion Cobro CxC`: `1`.
- cobros CxC con doble reversion: `0`.

## Validaciones automaticas

- `php -l` OK en:
  - `app/services/CuentaPorCobrarReversionCobroService.php`;
  - `app/controllers/CuentaPorCobrarController.php`;
  - `app/views/cuentas_por_cobrar/ver_operativa.php`;
  - `tools/saas/probar_reversion_cobro_cxc_caja.php`;
  - `tools/saas/preflight_cuentas_por_cobrar.php`;
  - `config/routes.php`.
- Preflight CxC:
  - `OK: 45`;
  - `WARNING: 6`;
  - `ERROR: 0`.
- Health general:
  - `OK: 277`;
  - `WARNING: 25`;
  - `ERROR: 0`.
- HTTP sin sesion:
  - `POST /cuentas-por-cobrar/operativas/1/movimientos/4/revertir-cobro-caja`:
    `303` a login.
- Regresion cobro CxC:
  - `php tools/saas/probar_cobro_cxc_caja.php`;
  - movimiento CxC temporal `#12`;
  - movimiento Caja temporal `#1490`;
  - rollback sin cambios persistentes.

## Riesgo residual

La QA manual real dejo datos financieros persistentes: `CANCELACION #8` y gasto Caja
`#1486`. No deben borrarse con SQL directo.

No avanzar a reversiones parciales, cobros masivos ni automatizaciones hasta abrir un
contrato independiente y documentar backup, prueba rollback y QA manual especifica.
