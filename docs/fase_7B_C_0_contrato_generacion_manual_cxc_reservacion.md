# Fase 7B-C-0 - Contrato generacion manual CxC desde reservacion

## Estado

`CONTRATO_7B_C_0_GENERACION_MANUAL_CXC_RESERVACION_COMPLETADO`

## Objetivo

Definir la futura generacion manual de una cuenta por cobrar operativa desde una
reservacion elegible, sin implementar todavia rutas POST, botones, escrituras ni
movimientos.

Esta subfase es documental. No modifica PHP, DB, rutas, modelos, vistas, Caja,
reservaciones, pagos, abonos, facturacion ni `/api/sync`.

## Contexto vigente

- 7A-A existe como reporte estimado derivado de reservaciones.
- 7A-S-B adopta politica conservadora:
  - pagos huerfanos excluidos de CxC operativa;
  - facturas huerfanas excluidas de CxC operativa;
  - excedentes solo informativos;
  - facturas scoped validas solo contexto read-only.
- 7B-A creo tablas vacias:
  - `cuentas_por_cobrar`;
  - `cuentas_por_cobrar_movimientos`.
- 7B-B expone listado/detalle read-only sobre esas tablas.
- Ambas tablas CxC operativas siguen vacias.

## Principio central

La generacion futura debe crear una CxC por el saldo pendiente neto elegible, no por el
total historico de la reservacion.

Motivo:

- los pagos y abonos existentes ya cubren parte del total;
- generar por el total duplicaria deuda;
- los excedentes no deben convertirse en deuda;
- los historicos huerfanos no deben alimentar CxC operativa.

## Elegibilidad futura

Una reservacion sera candidata solo si cumple todo:

- pertenece al `hotel_id` actual;
- existe y no esta fuera del hotel activo;
- tiene `precio_total > 0`;
- tiene saldo estimado neto `> 0`;
- no tiene saldo estimado negativo;
- no esta cancelada, salvo autorizacion posterior explicita;
- no existe ya una CxC con:
  - `hotel_id = hotel actual`;
  - `origen_tipo = reservacion`;
  - `origen_id = reservacion.id`;
- pagos considerados solo si coinciden por `hotel_id + reservacion_id`;
- abonos considerados solo si coinciden por `hotel_id + reservacion_id`;
- solicitudes de factura solo como contexto si coinciden por `hotel_id + reservacion_id`.

Quedan excluidos:

- pagos huerfanos;
- solicitudes de factura huerfanas;
- reservaciones con saldo negativo;
- excedentes;
- reservaciones de otro hotel;
- cualquier dato sin `hotel_id` valido.

## Escritura futura propuesta

Solo en una fase posterior 7B-C-A, con backup y autorizacion explicita, se podria agregar
un POST manual:

- `POST /cuentas-por-cobrar/generar-desde-reservacion/{id}`

Escrituras permitidas en esa fase futura:

- insertar una fila en `cuentas_por_cobrar`;
- insertar un movimiento interno `CREACION` en `cuentas_por_cobrar_movimientos`;
- registrar auditoria en `logs_auditoria`.

Escrituras prohibidas:

- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `reservaciones`;
- `reservacion_pagos`;
- `reservacion_abonos`;
- `solicitudes_factura`;
- PWA/offline;
- `/api/sync`.

## Datos propuestos para la CxC futura

Para una reservacion elegible:

- `hotel_id`: hotel actual;
- `origen_tipo`: `reservacion`;
- `origen_id`: `reservacion.id`;
- `huesped_id`: `reservacion.huesped_id`;
- `reservacion_id`: `reservacion.id`;
- `solicitud_factura_id`: ultima solicitud scoped valida si existe;
- `folio`: `CXC-RES-{reservacion_id}`;
- `concepto`: saldo pendiente de reservacion;
- `fecha_emision`: fecha actual de generacion manual;
- `fecha_vencimiento`: fecha de salida si existe, o `NULL`;
- `estado`: `pendiente`;
- `moneda`: `MXN`;
- `total`: saldo pendiente neto elegible;
- `saldo`: mismo saldo pendiente neto elegible;
- `notas`: indicar origen manual y ausencia de Caja automatica.

Movimiento interno inicial:

- `tipo_movimiento`: `CREACION`;
- `monto`: saldo pendiente neto elegible;
- `saldo_anterior`: `0.00`;
- `saldo_posterior`: saldo pendiente neto elegible;
- `referencia`: `CXC-RES-{reservacion_id}`;
- `notas`: generacion manual sin Caja.

## Validaciones obligatorias futuras

Antes de insertar:

- sesion activa;
- contexto hotelero activo;
- modulo `reservaciones` activo;
- CSRF valido;
- `id` de reservacion numerico positivo;
- reservacion buscada por `id + hotel_id`;
- bloqueo transaccional de la reservacion candidata;
- calculo de saldo con pagos/abonos scoped por hotel;
- saldo pendiente neto mayor a cero;
- no duplicado por `hotel_id + origen_tipo + origen_id`;
- si se vincula factura, debe existir por `id + hotel_id + reservacion_id`;
- usuario actual normalizado para auditoria.

Despues de insertar:

- `cuentas_por_cobrar.saldo <= cuentas_por_cobrar.total`;
- movimiento inicial coincide con el saldo;
- no existe movimiento de Caja nuevo;
- auditoria registra datos antes/despues suficientes.

## Preview recomendado antes del POST

Antes de implementar el POST real, la fase 7B-C-A debe preferir mostrar una accion manual
solo cuando la reservacion sea elegible desde una superficie ya autenticada.

La UI debe comunicar:

- saldo pendiente neto;
- pagos considerados;
- abonos considerados;
- factura scoped si existe;
- motivo de bloqueo si no es elegible;
- confirmacion de que no crea Caja ni pago.

## Rollback futuro

Si una prueba futura crea una CxC manual:

1. No borrar filas sin autorizacion.
2. Identificar `cuentas_por_cobrar.id`.
3. Identificar movimiento `CREACION`.
4. Si la cuenta no tiene movimientos posteriores y no fue usada operativamente, se podra
   definir anulacion o baja logica en una fase de rollback autorizada.
5. Si existen movimientos posteriores, no eliminar; reconciliar con una fase nueva.
6. Nunca revertir por `DELETE` directo sin backup y autorizacion explicita.

## Definition of Done futura 7B-C-A

- Backup previo verificado.
- PHP lint en archivos tocados.
- Preflight CxC actualizado.
- Health general `ERROR: 0`.
- HTTP sin sesion al POST redirige a login y no crea datos.
- CSRF invalido bloquea.
- Reservacion no elegible bloqueada.
- Duplicado bloqueado.
- CxC creada solo por saldo pendiente neto.
- Movimiento interno `CREACION` creado.
- Caja sin cambios.
- `/api/sync` sin cambios.
- Documentacion, auditoria y rollback actualizados.

## Siguiente accion segura

Implementar 7B-C-A solo si el usuario autoriza explicitamente escrituras CxC manuales
desde reservacion elegible.

Sin esa autorizacion, el siguiente paso debe ser revision/QA manual de 7B-B.

## Implementacion posterior

7B-C-A fue implementada posteriormente con autorizacion explicita y backup previo.

Ver:

- `docs/fase_7B_C_A_generacion_manual_cxc_reservacion.md`

La implementacion mantiene el contrato central: CxC por saldo pendiente neto, movimiento
interno `CREACION`, auditoria y sin Caja, pagos, abonos, facturacion nueva ni
modificacion de reservaciones.
