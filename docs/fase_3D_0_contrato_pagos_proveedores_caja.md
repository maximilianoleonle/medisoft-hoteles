# Fase 3D-0 - Contrato pagos proveedores con Caja

## Estado

`CONTRATO_3D_PAGOS_PROVEEDORES_CAJA_COMPLETADO`

## Objetivo

Definir el camino seguro para pagar cuentas por pagar de proveedores usando Caja, sin
implementar todavia pagos, sin crear rutas nuevas y sin modificar datos.

Esta subfase es solo contrato y diagnostico.

## Estado previo

- CxP base existe y fue validada en fases 3B/3C.
- CxP manual desde compras recibidas existe.
- `cuentas_por_pagar`: 2 registros.
- `cuentas_por_pagar_movimientos`: 0 registros.
- `compras`: 2 registros.
- `proveedores`: 30 registros.
- `cajas`: 3 registros.
- `cortes_caja`: 223 registros.
- `movimientos_caja`: 1404 registros.

## Esquema relevante observado

### `cuentas_por_pagar`

- `hotel_id`
- `proveedor_id`
- `compra_id`
- `folio`
- `descripcion`
- `fecha_emision`
- `fecha_vencimiento`
- `estado`
- `total`
- `saldo`

### `cuentas_por_pagar_movimientos`

- `cuenta_por_pagar_id`
- `hotel_id`
- `tipo_movimiento`
- `monto`
- `saldo_anterior`
- `saldo_posterior`
- `referencia`
- `usuario_id`

`tipo_movimiento` ya permite `PAGO_REFERENCIAL`, pero no hay registros.

### `movimientos_caja`

- `hotel_id`
- `tipo`
- `categoria`
- `categoria_id`
- `descripcion`
- `monto`
- `metodo_pago`
- `referencia`
- `proveedor`
- `usuario_id`
- `corte_id`

## Riesgo principal

3D toca dinero real y Caja. Un error podria:

- descuadrar cortes abiertos;
- registrar pagos duplicados;
- pagar una CxP de otro hotel;
- pagar una CxP cancelada o ya pagada;
- dejar saldo CxP y Caja inconsistentes;
- mezclar proveedores legacy por texto con proveedores modernos;
- afectar reportes financieros.

Por eso 3D no debe implementarse sin subfase de migracion/servicio/preflight y QA
manual especifica.

## Reglas futuras obligatorias

- Backup antes de cualquier cambio de DB.
- No pagar CxP sin `hotel_id`.
- No pagar CxP de otro hotel.
- No pagar proveedor de otro hotel.
- No pagar CxP cancelada o pagada.
- No permitir monto menor o igual a cero.
- No permitir monto mayor al saldo.
- Exigir corte de Caja abierto del mismo hotel.
- Registrar transaccion atomica entre CxP, movimiento CxP y movimiento Caja.
- Crear movimiento de Caja tipo `gasto`.
- No tocar cortes cerrados.
- Auditar intento, exito y fallo.
- Usar CSRF en cualquier POST.
- Bloquear doble submit.
- No tocar `/api/sync`.

## Diseno futuro sugerido

### 3D-A Simulador read-only

- Mostrar CxP pagables.
- Mostrar motivo de elegibilidad/bloqueo.
- Mostrar corte abierto disponible.
- Sin POST.

### 3D-B Servicio transaccional de pago

- Servicio central, no logica en vista.
- Valida hotel/proveedor/CxP/corte/saldo.
- Inserta `cuentas_por_pagar_movimientos`.
- Inserta `movimientos_caja`.
- Actualiza saldo/estado de CxP.
- Todo en una transaccion.

### 3D-C UI POST controlada

- Boton visible solo si elegible.
- CSRF.
- Confirmacion clara.
- Mensajes de exito/error.

### 3D-D Preflights

- CxP pagadas con saldo distinto de cero.
- Movimientos CxP sin movimiento Caja cuando deban tenerlo.
- Movimiento Caja sin CxP relacionada.
- Pago duplicado.
- Corte cerrado modificado.
- Hotel/proveedor cruzado.

### 3D-F Revision, auditoria y cierre

- QA manual obligatoria antes de marcar como validado.

## Fuera de alcance en 3D-0

- Rutas nuevas.
- POST de pago.
- Movimiento de Caja.
- Cambio de saldo.
- Cambio de estado CxP.
- Migraciones.
- `/api/sync`.

## Definition of Done futura

- Backup verificado.
- Migracion/servicio idempotente o rollback manual.
- `php -l`.
- Preflight CxP/Caja.
- Health checker.
- SQL antes/despues.
- Prueba de pago parcial.
- Prueba de pago total.
- Prueba de doble pago fallida limpiamente.
- Confirmar cero cambios en cortes cerrados.
- QA manual del usuario.

## Siguiente accion segura

Solo 3D-A simulador read-only o contrato mas detallado del servicio. No implementar pagos
reales sin autorizacion explicita, backup y QA manual disponible.

## Actualizacion Fase 3D-A

Estado: `SIMULADOR_3D_A_CAJA_READONLY_VALIDADO_MANUALMENTE`.

Se implemento el simulador read-only en `/cuentas-por-pagar/simulador-caja`.

El simulador:

- lee CxP del hotel actual;
- valida proveedor del mismo hotel;
- valida compra vinculada del mismo hotel si existe;
- valida estado y saldo;
- lee el corte de Caja abierto del hotel actual;
- muestra motivo de elegibilidad o bloqueo.

No implementa:

- POST de pago;
- abonos;
- movimientos CxP;
- movimientos de Caja;
- cambios de saldo;
- cambios de estado;
- migraciones;
- `/api/sync`.

QA manual del simulador completada por el usuario.

La implementacion real de pago sigue bloqueada hasta backup, servicio transaccional y
autorizacion explicita de escrituras financieras.

## Actualizacion Fase 3D-B

Estado: `CONTRATO_3D_B_SERVICIO_PAGO_TRANSACCIONAL_COMPLETADO`.

Revision manual: el usuario reviso el bloque pendiente y confirmo que esta bien.

Se documento el contrato tecnico del servicio transaccional futuro en
`docs/fase_3D_B_contrato_servicio_pago_transaccional.md`.

Esta subfase:

- no implementa codigo;
- no crea rutas;
- no crea formularios POST;
- no registra pagos;
- no crea movimientos CxP;
- no crea movimientos de Caja;
- no cambia saldos;
- no toca `/api/sync`.

El pago real sigue bloqueado hasta backup verificado, prueba local controlada y
autorizacion explicita de escrituras financieras.
