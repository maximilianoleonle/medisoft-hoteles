# Fase 7B-D-0 - Contrato cobro CxC con Caja

## Estado

`CONTRATO_7B_D_0_COBRO_CXC_CAJA_COMPLETADO`

## Objetivo

Definir el contrato para un cobro futuro de cuentas por cobrar contra Caja, sin
implementar todavia rutas, formularios, servicios, migraciones ni escrituras.

Esta fase es documental y de diagnostico. No toca Caja, cortes, saldos, reservaciones,
pagos, abonos, facturacion, PWA/offline ni `/api/sync`.

## Contexto vigente

- 7B-A creo `cuentas_por_cobrar` y `cuentas_por_cobrar_movimientos`.
- 7B-B expuso listado/detalle operativos read-only.
- 7B-C-A genero manualmente la primera CxC real:
  - CxC `#1`;
  - reservacion `#24`;
  - hotel `4`;
  - saldo `4250.00`;
  - movimiento interno `CREACION #1`.
- 7B-C-F cerro la generacion manual como bloque validado.
- Caja no participa todavia en CxC.

## Diagnostico de bloqueo actual

La columna `cuentas_por_cobrar_movimientos.tipo_movimiento` actualmente permite:

- `CREACION`
- `AJUSTE`
- `CANCELACION`
- `NOTA`
- `RECLASIFICACION`

No existe tipo `COBRO`, `PAGO`, `ABONO` ni equivalente.

Conclusion:

- No se debe registrar un cobro como `AJUSTE`.
- No se debe reducir saldo de CxC sin movimiento semantico de cobro.
- Una fase futura de cobro real necesita antes una decision de esquema:
  - agregar tipo `COBRO` a `cuentas_por_cobrar_movimientos`; o
  - crear tabla separada de cobros CxC; o
  - definir una entidad de recibos/cobros independiente.

## Superficie futura propuesta

Primero, una fase read-only:

- `GET /cuentas-por-cobrar/simulador-caja`
- o diagnostico dentro de `/cuentas-por-cobrar/operativas/{id}`

Luego, solo con autorizacion explicita y backup:

- `POST /cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja`

## Reglas futuras minimas

Un cobro futuro debera validar:

- sesion activa;
- contexto hotelero activo;
- modulo `reservaciones` activo;
- modulo `caja` activo;
- CSRF valido;
- token de cobro de un solo uso;
- CxC por `id + hotel_id`;
- estado `pendiente`, `parcial` o `vencida`;
- saldo mayor a cero;
- monto mayor a cero y no mayor al saldo;
- corte de Caja abierto del mismo hotel;
- metodo de pago permitido;
- referencia unica para evitar doble envio;
- bloqueo transaccional `FOR UPDATE` sobre CxC y corte.

## Escrituras futuras posibles

Solo en una fase posterior autorizada:

- actualizar saldo/estado de `cuentas_por_cobrar`;
- insertar movimiento interno de cobro en `cuentas_por_cobrar_movimientos`
  con tipo semantico aprobado;
- insertar ingreso en `movimientos_caja`;
- registrar auditoria en `logs_auditoria`.

## Escrituras prohibidas por este contrato

Este contrato no autoriza:

- cobros reales;
- cambios en `cuentas_por_cobrar`;
- cambios en `cuentas_por_cobrar_movimientos`;
- movimientos de Caja;
- cambios en cortes;
- cambios en reservaciones;
- cambios en `reservacion_pagos`;
- cambios en `reservacion_abonos`;
- cambios en `solicitudes_factura`;
- facturacion nueva;
- PWA/offline;
- `/api/sync`.

## Riesgos principales

- Duplicar ingreso si se registra en Caja dos veces.
- Reducir saldo CxC sin trazabilidad semantica.
- Mezclar CxC con pagos historicos de reservacion.
- Cobrar una CxC de otro hotel.
- Cobrar una CxC cancelada/liquidada/incobrable.
- Registrar ingreso en corte cerrado o de otro hotel.
- Reintentos de formulario por doble clic.

## Decision recomendada

Antes del primer cobro real:

1. Implementar 7B-D-A como simulador/read-only de cobro CxC con Caja.
2. Definir si el esquema tendra `tipo_movimiento = COBRO` o tabla de cobros CxC.
3. Crear backup.
4. Implementar servicio transaccional con prueba rollback.
5. Hacer QA manual con monto pequeno.

## Siguiente accion segura

7B-D-A: simulador read-only de cobro CxC contra corte de Caja abierto, sin POST y sin
escrituras.

## Implementacion posterior

7B-D-A fue implementada posteriormente como simulador GET/read-only.

Ver:

- `docs/fase_7B_D_A_simulador_cobro_cxc_caja.md`

La implementacion no registra cobros, no modifica saldos, no crea movimientos CxC ni
movimientos de Caja, y mantiene bloqueado el cobro real mientras no exista un tipo
semantico `COBRO` o una entidad de cobros definida.

## Cierre y contrato posterior

7B-D-A fue validada manualmente por el usuario y cerrada en:

- `docs/fase_7B_D_F_cierre_simulador_cobro_cxc.md`

El siguiente contrato documental define el esquema previo al primer cobro real:

- `docs/fase_7B_D_B_0_contrato_esquema_cobro_cxc.md`

Ese contrato no autoriza migraciones ni cobros. Solo fija la decision de no usar
`AJUSTE` como cobro y recomienda agregar un tipo semantico `COBRO` antes de implementar
un servicio transaccional.
