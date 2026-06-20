# Fase 7B-D-D-0 - Contrato reversion cobro CxC

## Estado

`CONTRATO_7B_D_D_0_ANULACION_REVERSION_COBRO_CXC_COMPLETADO`

## Objetivo

Definir el contrato seguro para anular/revertir un cobro CxC ya registrado contra Caja.

Esta fase no implementa codigo, rutas, migraciones ni escrituras. Solo fija reglas antes
de autorizar una futura implementacion operativa.

## Contexto validado

7B-D-C-A ya fue validada manualmente con un cobro real:

- CxC `#1`;
- movimiento CxC `#4`, tipo `COBRO`, monto `1.00`;
- movimiento Caja `#1482`, tipo `ingreso`, categoria `Cobro CxC`;
- referencia compartida `QA-CXC-20260619-001`;
- CxC queda `parcial` con saldo `4249.00`.

Ese dato no debe eliminarse ni corregirse con SQL directo.

## Principio contable

La reversion no debe editar ni borrar el cobro original.

Debe crear movimientos inversos trazables:

- movimiento CxC nuevo tipo `CANCELACION`;
- movimiento Caja nuevo tipo `gasto`, categoria `Reversion Cobro CxC`;
- actualizacion de saldo/estado en `cuentas_por_cobrar`;
- auditoria diferencial.

## Alcance futuro 7B-D-D-A

La implementacion futura podra agregar:

- servicio transaccional de reversion, preferentemente
  `CuentaPorCobrarReversionCobroService`;
- accion POST controlada en `CuentaPorCobrarController`;
- formulario/boton de anulacion solo sobre movimientos `COBRO` elegibles;
- prueba CLI con rollback;
- checks en `preflight_cuentas_por_cobrar.php`.

## Ruta futura sugerida

```text
POST /cuentas-por-cobrar/operativas/{id}/movimientos/{movimientoid}/revertir-cobro-caja
```

La ruta debe requerir:

- sesion activa;
- contexto hotelero;
- modulo `reservaciones`;
- modulo `caja`;
- CSRF;
- token de reversion de un solo uso;
- POST exclusivamente.

## Elegibilidad del cobro a revertir

Un cobro solo puede revertirse si:

- el movimiento existe en `cuentas_por_cobrar_movimientos`;
- pertenece al mismo `hotel_id`;
- pertenece a la CxC del detalle;
- `tipo_movimiento = COBRO`;
- `monto > 0`;
- la cuenta existe y pertenece al mismo hotel;
- no existe una reversion previa para ese movimiento;
- existe corte de Caja abierto actual para registrar la salida;
- existe caja activa del hotel;
- el saldo resultante no excede el total de la CxC.

La primera implementacion debe ser de reversion total del movimiento `COBRO`.

No implementar reversion parcial en 7B-D-D-A.

## Referencia canonica

La reversion debe usar referencia canonica independiente de la referencia capturada por
el usuario:

```text
REV-CXC-{cuenta_id}-MOV-{movimiento_cobro_id}
```

Esa referencia debe aparecer tanto en:

- `cuentas_por_cobrar_movimientos.referencia`;
- `movimientos_caja.referencia`.

Esto evita ambiguedad cuando el cobro original uso una referencia manual.

## Orden transaccional futuro

Dentro de una unica transaccion:

1. Bloquear CxC por `id + hotel_id` con `FOR UPDATE`.
2. Bloquear movimiento `COBRO` objetivo con `FOR UPDATE`.
3. Verificar que no exista `CANCELACION` previa para ese cobro.
4. Bloquear corte abierto actual con `FOR UPDATE`.
5. Calcular `saldo_posterior = saldo_actual + monto_cobro`.
6. Bloquear si `saldo_posterior > total`.
7. Insertar movimiento CxC tipo `CANCELACION`.
8. Insertar movimiento Caja tipo `gasto`, categoria `Reversion Cobro CxC`.
9. Actualizar `cuentas_por_cobrar.saldo` y `estado`.
10. Registrar auditoria.
11. Hacer commit.

Si cualquier paso falla, la transaccion completa debe hacer rollback.

## Estado posterior

El estado debe recalcularse desde el saldo final:

- `liquidada` si `saldo <= 0`;
- `vencida` si `saldo > 0` y `fecha_vencimiento < CURDATE()`;
- `pendiente` si `saldo >= total` y no esta vencida;
- `parcial` si `saldo > 0`, `saldo < total` y no esta vencida.

## Auditoria futura

Accion esperada:

```text
cuentas_por_cobrar.cobro_caja_revertido
```

La auditoria debe guardar:

- CxC id;
- movimiento `COBRO` original;
- movimiento `CANCELACION` creado;
- movimiento Caja original si se pudo localizar;
- movimiento Caja de reversion;
- saldo/estado antes;
- saldo/estado despues;
- usuario;
- corte actual usado para la reversion;
- referencia canonica.

## Prohibiciones

La reversion no debe:

- borrar movimientos CxC;
- borrar movimientos de Caja;
- editar el movimiento `COBRO` original;
- editar el movimiento Caja original;
- tocar `reservacion_pagos`;
- tocar `reservacion_abonos`;
- tocar `solicitudes_factura`;
- tocar `reservaciones`;
- tocar `cajas`;
- tocar `cortes_caja` salvo lectura/lock;
- tocar PWA, offline, IndexedDB, caches ni `/api/sync`;
- permitir GET para ejecutar reversion;
- permitir doble reversion del mismo cobro;
- revertir cobros de otro hotel.

## Preflight futuro

El preflight debera validar:

- ruta POST registrada;
- accion de controlador con CSRF/token;
- servicio transaccional con locks;
- uso de `CANCELACION`;
- ingreso Caja inverso tipo `gasto`;
- ausencia de escrituras prohibidas;
- prueba rollback disponible;
- todo `CANCELACION` de cobro con Caja tiene gasto asociado;
- todo gasto `Reversion Cobro CxC` tiene `CANCELACION` asociada;
- no existen dobles reversiones del mismo `COBRO`.

## Prueba rollback futura

Crear una prueba CLI similar a:

```bash
php tools/saas/probar_reversion_cobro_cxc_caja.php
```

La prueba debe:

- elegir un cobro `COBRO` persistente elegible;
- abrir transaccion externa;
- ejecutar la reversion con `manage_transaction=false`;
- validar movimiento `CANCELACION`, gasto Caja, auditoria y saldo temporal;
- intentar doble reversion y esperar bloqueo;
- hacer rollback;
- confirmar que conteos, saldo, estado y auditoria quedan iguales.

## Definition of Done 7B-D-D-A

La implementacion futura solo se considera completa si:

- hay backup previo;
- `php -l` pasa en todos los PHP modificados;
- prueba rollback pasa;
- preflight CxC queda con `ERROR: 0`;
- health general queda con `ERROR: 0`;
- HTTP sin sesion redirige a login;
- QA manual valida una reversion real pequena o controlada;
- la evidencia queda documentada.

## Siguiente paso

7B-D-D-A: implementar reversion/anulacion controlada de cobro CxC con Caja.

Esa fase requiere autorizacion explicita porque escribira en `cuentas_por_cobrar`,
`cuentas_por_cobrar_movimientos`, `movimientos_caja` y `logs_auditoria`.

## Implementacion posterior

7B-D-D-A fue implementada posteriormente con servicio transaccional, ruta POST,
token de un solo uso, panel en detalle CxC, preflight y prueba rollback. La QA manual
real fue validada con `CANCELACION #8`, gasto Caja `#1486` y referencia
`REV-CXC-1-MOV-4`.

Documento:

- `docs/fase_7B_D_D_A_reversion_cobro_cxc.md`
