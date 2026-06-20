# Fase 3D-D-0 - Contrato reversion pago proveedor con Caja

## Estado

`CONTRATO_3D_D_0_REVERSION_PAGO_PROVEEDOR_CAJA_COMPLETADO`

## Objetivo

Definir el contrato seguro para revertir/anular un pago de cuenta por pagar ya
registrado contra Caja.

Esta fase no implementa codigo, rutas, formularios, migraciones ni escrituras. Solo fija
reglas antes de una futura implementacion operativa.

## Contexto validado

3D-C fue validada con pagos reales sobre la CxP `#1` del hotel `4`:

- CxP `#1`: proveedor `#2`, compra `#5`, total `1000.00`.
- Estado despues de 3D-C: saldo `0.00`, estado `pagada`.
- Estado vigente despues de 3D-D-A: saldo `10.00`, estado `parcial`.
- Movimiento CxP `#4`: `PAGO_REFERENCIAL`, monto `10.00`, saldo `1000.00 -> 990.00`.
- Movimiento Caja `#1478`: tipo `gasto`, categoria `Pago proveedor`, monto `10.00`,
  referencia `CXP-1-MOV-4`, corte `#237`.
- Movimiento CxP `#5`: `PAGO_REFERENCIAL`, monto `990.00`, saldo `990.00 -> 0.00`,
  referencia `1212331312`.
- Movimiento Caja `#1479`: tipo `gasto`, categoria `Pago proveedor`, monto `990.00`,
  referencia `1212331312`, corte `#237`.

Esos datos son historicos y no deben editarse ni borrarse con SQL directo.

## Esquema disponible

`cuentas_por_pagar_movimientos.tipo_movimiento` ya incluye:

```text
CREACION, AJUSTE, CANCELACION, PAGO_REFERENCIAL
```

Por lo tanto, la primera implementacion de reversion no requiere migracion de enum para
usar `CANCELACION`.

## Principio contable

La reversion no debe editar ni borrar el pago original.

Debe crear movimientos inversos trazables:

- movimiento CxP nuevo tipo `CANCELACION`;
- movimiento Caja nuevo tipo `ingreso`, categoria `Reversion Pago proveedor`;
- actualizacion de saldo/estado en `cuentas_por_pagar`;
- auditoria diferencial.

## Alcance futuro 3D-D-A

La implementacion futura podra agregar:

- servicio transaccional `CuentaPorPagarReversionPagoService`;
- accion POST controlada en `CuentaPorPagarController`;
- panel/boton de reversion solo sobre movimientos `PAGO_REFERENCIAL` elegibles;
- prueba CLI con rollback;
- checks en `preflight_pagos_proveedores_caja.php`.

## Ruta futura sugerida

```text
POST /cuentas-por-pagar/{id}/movimientos/{movimientoid}/revertir-pago-caja
```

La ruta debe requerir:

- sesion activa;
- contexto hotelero;
- modulo `inventario`;
- modulo `caja`;
- CSRF;
- token de reversion de un solo uso;
- POST exclusivamente.

## Elegibilidad del pago a revertir

Un pago solo puede revertirse si:

- el movimiento existe en `cuentas_por_pagar_movimientos`;
- pertenece al mismo `hotel_id`;
- pertenece a la CxP del detalle;
- `tipo_movimiento = PAGO_REFERENCIAL`;
- `monto > 0`;
- la CxP existe y pertenece al mismo hotel;
- la CxP no esta `cancelada`;
- el proveedor pertenece al mismo hotel;
- si hay compra vinculada, pertenece al mismo hotel;
- existe el gasto de Caja original asociado;
- no existe una reversion previa para ese movimiento;
- existe corte de Caja abierto actual para registrar el ingreso inverso;
- existe caja activa del hotel;
- el saldo resultante no excede el total de la CxP.

La primera implementacion debe ser reversion total del movimiento
`PAGO_REFERENCIAL`. No implementar reversion parcial en 3D-D-A.

## Referencia canonica

La reversion debe usar referencia canonica independiente de la referencia capturada por
el usuario:

```text
REV-CXP-{cuenta_id}-MOV-{movimiento_pago_id}
```

Ejemplos con datos actuales:

```text
REV-CXP-1-MOV-4
REV-CXP-1-MOV-5
```

Esa referencia debe aparecer tanto en:

- `cuentas_por_pagar_movimientos.referencia`;
- `movimientos_caja.referencia`.

## Orden transaccional futuro

Dentro de una unica transaccion:

1. Bloquear CxP por `id + hotel_id` con `FOR UPDATE`.
2. Bloquear movimiento `PAGO_REFERENCIAL` objetivo con `FOR UPDATE`.
3. Verificar que no exista `CANCELACION` previa para ese pago.
4. Localizar y bloquear el gasto Caja original.
5. Bloquear corte abierto actual con `FOR UPDATE`.
6. Calcular `saldo_posterior = saldo_actual + monto_pago`.
7. Bloquear si `saldo_posterior > total`.
8. Insertar movimiento CxP tipo `CANCELACION`.
9. Insertar movimiento Caja tipo `ingreso`, categoria `Reversion Pago proveedor`.
10. Actualizar `cuentas_por_pagar.saldo` y `estado`.
11. Registrar auditoria.
12. Hacer commit.

Si cualquier paso falla, la transaccion completa debe hacer rollback.

## Estado posterior

El estado debe recalcularse desde el saldo final:

- `pagada` si `saldo <= 0`;
- `vencida` si `saldo > 0` y `fecha_vencimiento < CURDATE()`;
- `pendiente` si `saldo >= total` y no esta vencida;
- `parcial` si `saldo > 0`, `saldo < total` y no esta vencida.

Revertir el pago total `#5` de la CxP `#1` reabriria la cuenta con saldo `990.00`.
Revertir tambien el pago parcial `#4` la regresaria a saldo `1000.00`.

## Auditoria futura

Accion esperada:

```text
cuentas_por_pagar.pago_caja_revertido
```

La auditoria debe guardar:

- CxP id;
- proveedor id;
- movimiento `PAGO_REFERENCIAL` original;
- movimiento `CANCELACION` creado;
- movimiento Caja original;
- movimiento Caja de reversion;
- saldo/estado antes;
- saldo/estado despues;
- usuario;
- corte actual usado para la reversion;
- referencia canonica;
- motivo.

## Prohibiciones

La reversion no debe:

- borrar movimientos CxP;
- borrar movimientos de Caja;
- editar el movimiento `PAGO_REFERENCIAL` original;
- editar el movimiento Caja original;
- tocar `compras`;
- tocar `proveedores`;
- crear abonos;
- tocar `cajas`;
- tocar `cortes_caja` salvo lectura/lock;
- tocar PWA, offline, IndexedDB, caches ni `/api/sync`;
- permitir GET para ejecutar reversion;
- permitir doble reversion del mismo pago;
- revertir pagos de otro hotel.

## Preflight futuro

El preflight debera validar:

- ruta POST registrada;
- accion de controlador con CSRF/token;
- servicio transaccional con locks;
- uso de `CANCELACION`;
- ingreso Caja inverso tipo `ingreso`;
- ausencia de escrituras prohibidas;
- prueba rollback disponible;
- todo `CANCELACION` de pago proveedor tiene ingreso Caja asociado;
- todo ingreso Caja `Reversion Pago proveedor` tiene `CANCELACION` asociada;
- no existen dobles reversiones del mismo `PAGO_REFERENCIAL`;
- todo pago `PAGO_REFERENCIAL` persistente mantiene gasto Caja asociado.

## Prueba rollback futura

Crear una prueba CLI similar a:

```bash
php tools/saas/probar_reversion_pago_proveedor_caja.php
```

La prueba debe:

- elegir una CxP elegible con saldo suficiente;
- abrir transaccion externa;
- crear un pago temporal con `CuentaPorPagarPagoService` y `manage_transaction=false`;
- ejecutar la reversion temporal con el servicio futuro y `manage_transaction=false`;
- validar movimiento `CANCELACION`, ingreso Caja, auditoria y saldo temporal;
- intentar doble reversion y esperar bloqueo;
- hacer rollback;
- confirmar que conteos, saldo, estado y auditoria quedan iguales.

## Definition of Done 3D-D-A

La implementacion futura solo se considera completa si:

- hay backup previo;
- `php -l` pasa en todos los PHP modificados;
- prueba rollback pasa;
- preflight CxP/Caja queda con `ERROR: 0`;
- health general queda con `ERROR: 0`;
- HTTP sin sesion redirige o bloquea el POST;
- QA manual valida una reversion real pequena o controlada;
- la evidencia queda documentada.

## Siguiente paso

3D-D-A solo debe implementarse con autorizacion explicita de escritura financiera,
backup previo y prueba rollback. Hasta entonces no escalar mas pagos reales ni
automatizaciones de pagos proveedor.

## Implementacion posterior

3D-D-A fue implementada posteriormente con servicio transaccional, ruta POST, token de
un solo uso, panel en detalle CxP, preflight y prueba rollback. La QA manual fue
validada con la reversion del pago `#4`: `CANCELACION #9`, Caja `#1494`, referencia
`REV-CXP-1-MOV-4`.

Documento:

- `docs/fase_3D_D_A_reversion_pago_proveedor_caja.md`
