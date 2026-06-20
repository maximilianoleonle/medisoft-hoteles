# Fase 3D-D-A - Reversion pago proveedor con Caja

## Estado

`REVERSION_PAGO_PROVEEDOR_CAJA_3D_D_A_VALIDADA_MANUALMENTE`

## Objetivo

Implementar la reversion controlada de un pago proveedor registrado contra Caja.

La implementacion queda lista tecnicamente, probada con rollback y validada manualmente
en navegador con una reversion real de bajo impacto.

## Backup previo

- Archivo:
  `backups/medisoft_hoteles_import_before_3d_d_a_cxp_payment_reversal_20260619_095320.sql`
- SHA256:
  `3BB71EF0C696E1AB0CB3FC4A4B2E51319DCD654F0CEAA7E83280378F9A9001FB`
- Tamano: `3286902` bytes

## Alcance implementado

- Servicio transaccional:
  `src/app/services/CuentaPorPagarReversionPagoService.php`.
- Ruta POST controlada:
  `/cuentas-por-pagar/{id}/movimientos/{movimientoid}/revertir-pago-caja`.
- Controlador actualizado:
  `CuentaPorPagarController::revertirPagoCajaAction()`.
- Token de reversion de un solo uso por cuenta/movimiento/sesion.
- Panel "Reversion de pagos" en:
  `src/app/views/cuentas_por_pagar/ver.php`.
- Prueba rollback:
  `src/tools/saas/probar_reversion_pago_proveedor_caja.php`.
- Preflight CxP/Caja actualizado para validar superficie 3D-D-A y consistencia entre
  `CANCELACION` e ingreso Caja.

## Reglas implementadas

La reversion exige:

- sesion activa;
- contexto hotelero;
- modulo `inventario`;
- modulo `caja` en el POST;
- CSRF;
- token de reversion valido y no reutilizado;
- CxP por `id + hotel_id`;
- movimiento `PAGO_REFERENCIAL` por `id + cuenta + hotel`;
- gasto Caja original asociado;
- ausencia de reversion previa;
- corte de Caja abierto;
- caja activa;
- motivo obligatorio;
- tipo semantico `CANCELACION` disponible;
- saldo posterior no mayor al total de la CxP.

## Escrituras del servicio

Cuando se use desde navegador, el servicio escribira atomica y transaccionalmente:

- `cuentas_por_pagar_movimientos` con `tipo_movimiento = CANCELACION`;
- `movimientos_caja` con `tipo = ingreso` y `categoria = Reversion Pago proveedor`;
- `cuentas_por_pagar.saldo`;
- `cuentas_por_pagar.estado`;
- `logs_auditoria` con `cuentas_por_pagar.pago_caja_revertido`.

No borra ni edita el pago original.

## Referencia

La reversion usa referencia canonica:

```text
REV-CXP-{cuenta_id}-MOV-{movimiento_pago_id}
```

Para los pagos reales actuales, las referencias previstas eran:

```text
REV-CXP-1-MOV-4
REV-CXP-1-MOV-5
```

## Prueba rollback

Comando:

```bash
php tools/saas/probar_reversion_pago_proveedor_caja.php
```

Resultado:

- Cuenta elegida: CxP `#2`, hotel `4`, saldo `1900.00`.
- Corte abierto detectado: `#237`.
- Movimiento CxP `PAGO_REFERENCIAL` temporal: `#6`.
- Movimiento CxP `CANCELACION` temporal: `#7`.
- Movimiento Caja gasto temporal: `#1491`.
- Movimiento Caja ingreso temporal: `#1492`.
- Saldo temporal: `1900.00`.
- Doble reversion bloqueada dentro de la transaccion.
- Rollback confirmado: `PAGO_REFERENCIAL`, `CANCELACION`, Caja, auditoria, saldo y
  estado quedaron iguales.

## Conteos finales post-rollback

- CxP `#1`: estado `parcial`, saldo `10.00`.
- CxP `#2`: estado `pendiente`, saldo `1900.00`.
- movimientos `PAGO_REFERENCIAL`: `2`.
- movimientos `CANCELACION` de reversion: `1`.
- gastos Caja `Pago proveedor`: `2`.
- ingresos Caja `Reversion Pago proveedor`: `1`.

## QA manual validada

El usuario confirmo que la prueba manual paso completa.

Evidencia persistente en DB:

- CxP: `#1`, hotel `4`.
- Pago proveedor original revertido: movimiento CxP `#4`, monto `10.00`.
- Caja original: movimiento `#1478`, tipo `gasto`, categoria `Pago proveedor`.
- Movimiento de reversion CxP: `#9`, tipo `CANCELACION`, monto `10.00`.
- Caja de reversion: movimiento `#1494`, tipo `ingreso`,
  categoria `Reversion Pago proveedor`.
- Referencia canonica: `REV-CXP-1-MOV-4`.
- Corte: `#237`.
- Usuario: `#24`.
- Auditoria: `logs_auditoria #121`,
  accion `cuentas_por_pagar.pago_caja_revertido`.
- Resultado financiero: CxP `#1` paso de `pagada/saldo 0.00` a
  `parcial/saldo 10.00`.
- Doble reversion: validada manualmente por bloqueo de flujo/token.

## Validaciones automaticas

- `php -l` OK en:
  - `app/services/CuentaPorPagarReversionPagoService.php`;
  - `app/controllers/CuentaPorPagarController.php`;
  - `app/views/cuentas_por_pagar/ver.php`;
  - `tools/saas/probar_reversion_pago_proveedor_caja.php`;
  - `tools/saas/preflight_pagos_proveedores_caja.php`;
  - `tools/saas/health_check_fase_1a.php`;
  - `config/routes.php`.
- Preflight pagos proveedor:
  - `OK: 29`;
  - `WARNING: 1`;
  - `ERROR: 0`.
  - La advertencia corresponde a la `CANCELACION` persistente validada manualmente.
- Health general:
  - `OK: 278`;
  - `WARNING: 25`;
  - `ERROR: 0`.
- HTTP sin sesion:
  - `POST /cuentas-por-pagar/2/movimientos/6/revertir-pago-caja`: `303` a login.
- Route match directo:
  - `controller=CuentaPorPagar`;
  - `action=revertirPagoCaja`;
  - `id=2`;
  - `movimientoid=6`.
- Regresion pago proveedor:
  - `php tools/saas/probar_pago_proveedor_caja.php`;
  - movimiento CxP temporal `#12`;
  - movimiento Caja temporal `#1497`;
  - rollback sin cambios persistentes.
- Regresion reversion proveedor post-QA:
  - movimiento CxP `PAGO_REFERENCIAL` temporal `#10`;
  - movimiento CxP `CANCELACION` temporal `#11`;
  - gasto Caja temporal `#1495`;
  - ingreso Caja temporal `#1496`;
  - rollback sin cambios persistentes.

## Riesgo residual

Existe un ingreso Caja persistente y una `CANCELACION` CxP creada por QA manual.
No deben borrarse con SQL directo.

No avanzar a reversiones masivas ni automatizaciones hasta definir contrato especifico,
backup previo y prueba rollback dedicada.
