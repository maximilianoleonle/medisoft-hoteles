# Fase 3C-0 - Contrato y diagnostico

## Objetivo

Definir el contrato seguro para CxP operativa controlada sin Caja.

La Fase 3C autoriza crear cuentas por pagar manualmente desde compras recibidas, pero no autoriza pagos, abonos, Caja, conciliacion, CxC, nomina, permisos profundos ni cambios en `/api/sync`.

## Estado heredado

- Bloque Fase 2X-3B cerrado tecnicamente.
- QA manual del bloque anterior reportada como realizada por el usuario.
- Cambio visual de reservaciones commiteado en `dc3c150 fix: improve late check-in modal layout`.
- HEAD al iniciar 3C-0: `dc3c150`.
- Estado Git al iniciar: limpio.

## Riesgo

Naranja.

Motivo:

- escribe en una tabla financiera (`cuentas_por_pagar`);
- crea saldos por pagar;
- debe evitar duplicados por compra;
- no debe tocar Caja;
- no debe registrar pagos.

## Diagnostico DB read-only

Base local principal: `medisoft_hoteles_import`.

Tablas relevantes existentes:

- `compras`
- `compra_detalles`
- `proveedores`
- `cuentas_por_pagar`
- `cuentas_por_pagar_movimientos`
- `inventario_productos`
- `movimientos_inventario`

Conteos observados:

- compras recibidas: 2.
- CxP existentes: 0.
- compras recibidas con CxP: 0.
- compras recibidas elegibles: 2.
- movimientos de Caja relacionados con CxP: 0.

Compras recibidas elegibles observadas:

| compra_id | hotel_id | hotel | proveedor_id | proveedor | total | cxp_id |
| --- | --- | --- | --- | --- | ---: | --- |
| 2 | 4 | Maximiliano Leon | 2 | Juan Pedro | 1900.00 | NULL |
| 5 | 4 | Maximiliano Leon | 2 | Juan Pedro | 1000.00 | NULL |

Controles de consistencia actuales:

- CxP duplicada por compra: 0.
- CxP con compra inexistente: 0.
- CxP con proveedor/hotel inconsistente: 0.
- CxP desde compra no recibida: 0.
- CxP con saldo mayor a total: 0.
- Movimientos Caja-CxP: 0.

## Reglas de elegibilidad 3C

Una compra recibida solo puede generar CxP si:

- pertenece al hotel actual;
- existe en `compras`;
- tiene `estado = 'recibida'`;
- tiene `proveedor_id` valido en el mismo `hotel_id`;
- tiene `total > 0`;
- no existe `cuentas_por_pagar` con el mismo `(hotel_id, compra_id)`;
- la accion es manual y explicita;
- el usuario pasa los guards existentes de autenticacion, contexto hotelero y modulo de inventario.

## Diseno seguro propuesto

### Fase 3C-A

Crear un simulador/read-only de compras recibidas generables como CxP:

- no escribe DB;
- lista compras recibidas;
- muestra proveedor, hotel, fecha, total, estado;
- indica si ya tiene CxP;
- muestra motivo de elegibilidad o bloqueo.

### Fase 3C-B

Agregar accion manual para generar CxP:

- POST explicito con CSRF;
- transaccion;
- bloqueo/validacion de compra;
- validacion de proveedor por hotel;
- validacion de no duplicado;
- insert en `cuentas_por_pagar`;
- movimiento referencial `CREACION` en `cuentas_por_pagar_movimientos` solo si se decide registrar trazabilidad interna;
- auditoria si el patron `AuditService` sigue disponible;
- redireccion segura;
- sin Caja, sin pagos, sin abonos.

Nota: aunque `cuentas_por_pagar_movimientos` permite `PAGO_REFERENCIAL`, Fase 3C no debe crear pagos.

### Fase 3C-C

Actualizar checkers para detectar:

- duplicados por compra;
- compra inexistente;
- proveedor/hotel inconsistente;
- saldo mayor a total;
- CxP sin `hotel_id`;
- CxP desde compra no recibida;
- movimientos Caja-CxP.

## No tocar

- `/api/sync`;
- Caja;
- pagos;
- abonos;
- CxC;
- nomina;
- permisos profundos;
- migraciones destructivas;
- tablas legacy.

## Rollback conceptual

- Antes de crear CxP real, hacer backup si la fase va a escribir datos.
- Para simulador 3C-A: revertir commit de codigo/docs.
- Para generacion 3C-B: revertir codigo; si se crearon CxP, no borrar datos sin autorizacion. Marcar/reconciliar manualmente o restaurar backup solo con autorizacion explicita.

## Estado de 3C-0

Contrato y diagnostico completados. No se implemento simulador ni generacion manual en esta subfase.
