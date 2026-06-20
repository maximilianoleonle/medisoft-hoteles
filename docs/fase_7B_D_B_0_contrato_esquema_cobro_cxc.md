# Fase 7B-D-B-0 - Contrato esquema cobro CxC

## Estado

`CONTRATO_7B_D_B_0_ESQUEMA_COBRO_CXC_COMPLETADO`

## Objetivo

Definir el esquema tecnico para registrar cobros de cuentas por cobrar antes de
implementar cualquier migracion, formulario, servicio transaccional o escritura
financiera.

Esta subfase es solo documental. No modifica DB, PHP, rutas, vistas, Caja, cortes,
reservaciones, pagos, abonos, facturacion, PWA/offline ni `/api/sync`.

## Problema a resolver

La CxC operativa ya tiene una cuenta real generada manualmente:

- `cuentas_por_cobrar.id = 1`;
- `reservacion_id = 24`;
- `saldo = 4250.00`;
- movimiento interno `CREACION`.

El simulador 7B-D-A confirma que el cobro real sigue bloqueado porque
`cuentas_por_cobrar_movimientos.tipo_movimiento` no tiene tipo `COBRO`.

Usar `AJUSTE` para un cobro queda prohibido porque mezcla correccion contable con
entrada real de dinero.

## Opciones de esquema

### Opcion A - Agregar tipo `COBRO` al movimiento CxC

Ventajas:

- Es el cambio mas pequeno.
- Reutiliza `cuentas_por_cobrar_movimientos` como ledger interno.
- Se parece al patron de CxP, donde el movimiento interno acompana al movimiento de Caja.

Riesgos:

- Requiere migracion sobre `ENUM`.
- Puede quedarse corta si despues se necesitan recibos, cancelaciones, facturas o
  multiples medios de pago por cobro.

### Opcion B - Crear tabla `cuentas_por_cobrar_cobros`

Ventajas:

- Separa el evento de cobro del ledger interno.
- Permite registrar metodo, corte, caja, referencia, observaciones, usuario, estado,
  anulacion futura y relacion con movimiento Caja.
- Facilita recibos y reversiones posteriores.

Riesgos:

- Requiere tabla nueva, modelo/servicio y mas validaciones.
- El ledger CxC igual necesita un tipo semantico para reflejar la reduccion de saldo.

### Opcion C - Crear entidad amplia de recibos/cobros

Ventajas:

- Prepara recibos formales, multi-CxC, anticipos, facturacion y anulaciones complejas.

Riesgos:

- Es demasiado grande para el siguiente paso inmediato.
- Aumenta el alcance antes de validar el primer cobro controlado.

## Decision recomendada

Usar una estrategia incremental:

1. Migracion aditiva para soportar tipo semantico `COBRO` en
   `cuentas_por_cobrar_movimientos`.
2. Servicio transaccional que escriba:
   - saldo/estado en `cuentas_por_cobrar`;
   - movimiento interno tipo `COBRO`;
   - ingreso en `movimientos_caja`;
   - auditoria en `logs_auditoria`.
3. Dejar tabla de recibos/cobros para una fase posterior, antes de imprimir recibos,
   anular cobros o permitir cobros compuestos.

Motivo: permite validar un primer cobro parcial controlado con el menor cambio de
esquema, sin fingir cobros como ajustes y sin abrir todavia un subsistema de recibos.

## Migracion futura propuesta

Solo con autorizacion explicita y backup previo:

- ampliar `cuentas_por_cobrar_movimientos.tipo_movimiento` para incluir `COBRO`;
- conservar los valores existentes:
  `CREACION`, `AJUSTE`, `CANCELACION`, `NOTA`, `RECLASIFICACION`;
- no modificar registros existentes;
- no crear cobros historicos;
- no tocar `movimientos_caja`.

## Reglas para servicio futuro

Un cobro CxC real debera:

- exigir sesion activa y contexto hotelero;
- exigir modulo `reservaciones` y modulo `caja`;
- validar CSRF;
- usar token de cobro de un solo uso;
- cargar CxC por `id + hotel_id`;
- bloquear CxC y corte con `FOR UPDATE`;
- permitir solo estados `pendiente`, `parcial` o `vencida`;
- exigir saldo positivo;
- exigir monto mayor a cero y menor o igual al saldo;
- exigir corte abierto del mismo hotel;
- crear referencia unica entre movimiento CxC y movimiento Caja;
- actualizar saldo y estado en la misma transaccion;
- registrar auditoria con antes/despues.

## Escrituras no autorizadas por este contrato

Este contrato no autoriza:

- `ALTER TABLE`;
- migraciones;
- rutas `POST`;
- botones de cobro;
- actualizacion de saldos;
- movimientos CxC tipo `COBRO`;
- movimientos de Caja;
- cambios de corte;
- cambios en reservaciones, pagos, abonos o facturacion;
- PWA/offline;
- `/api/sync`.

## Validaciones obligatorias para la fase futura

Antes de implementar cobro real:

1. Crear backup y registrar SHA256.
2. Aplicar migracion en entorno local.
3. Ejecutar `php -l` sobre PHP modificado.
4. Ejecutar preflight CxC.
5. Ejecutar health general.
6. Ejecutar prueba rollback del servicio.
7. Confirmar que `/api/sync` sigue bloqueado.
8. Hacer QA manual con monto pequeno.

## Siguiente paso seguro

7B-D-B-A: migracion aditiva para agregar el tipo `COBRO` a
`cuentas_por_cobrar_movimientos.tipo_movimiento`, solo si el usuario autoriza
explicitamente tocar DB.

## Implementacion posterior

7B-D-B-A fue ejecutada posteriormente como migracion aditiva.

Ver:

- `docs/fase_7B_D_B_A_migracion_cobro_enum.md`

Resultado: `COBRO` quedo disponible en
`cuentas_por_cobrar_movimientos.tipo_movimiento`, sin crear cobros reales ni movimientos
de Caja.
