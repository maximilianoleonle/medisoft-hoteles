# Fase 3D-B - Contrato tecnico del servicio transaccional de pago proveedor

## Estado

`CONTRATO_3D_B_SERVICIO_PAGO_TRANSACCIONAL_COMPLETADO`

Revision manual: el usuario reviso el bloque pendiente y confirmo que esta bien.

## Objetivo

Definir el contrato tecnico del servicio que, en una fase posterior, permitira pagar una
cuenta por pagar de proveedor usando Caja. Esta subfase no implementa el servicio, no crea
rutas, no registra pagos y no modifica datos.

## Alcance de esta subfase

- Documentar responsabilidades del servicio.
- Definir validaciones obligatorias.
- Definir orden transaccional esperado.
- Definir rollback manual.
- Definir preflights requeridos antes y despues.
- Definir puntos de auditoria.
- Mantener bloqueada la implementacion operativa hasta backup y QA manual.

## Fuera de alcance

- Codigo PHP nuevo.
- Rutas nuevas.
- Formularios POST.
- Botones de pago.
- Inserciones en `cuentas_por_pagar_movimientos`.
- Inserciones en `movimientos_caja`.
- Updates a `cuentas_por_pagar`.
- Updates a `cortes_caja`.
- Migraciones.
- `/api/sync`.

## Servicio futuro propuesto

Nombre sugerido:

- `CuentaPorPagarPagoService`

Metodo sugerido:

- `registrarPagoProveedor(int $hotelId, int $cuentaId, array $payload, ?int $usuarioId): array`

Dependencias previstas:

- `Database`
- `CuentaPorPagar`
- `Caja` o consultas scoped de corte abierto
- `AuditService`

El servicio debe vivir fuera de la vista y fuera del controlador para centralizar reglas
financieras.

## Payload futuro minimo

- `monto`
- `metodo_pago`
- `referencia` opcional
- `notas` opcional
- `corte_id` opcional si se quiere fijar explicitamente el corte abierto validado

No debe aceptar `hotel_id`, `proveedor_id`, `saldo_anterior` ni `saldo_posterior` desde el
request; esos valores deben calcularse desde DB dentro de la transaccion.

## Validaciones obligatorias

Antes de escribir:

- `hotel_id` valido.
- usuario autenticado.
- CxP existe y pertenece al hotel actual.
- proveedor existe y pertenece al mismo hotel.
- CxP estado en `pendiente`, `parcial` o `vencida`.
- CxP no esta `pagada` ni `cancelada`.
- saldo actual mayor a cero.
- monto mayor a cero.
- monto menor o igual al saldo actual.
- compra vinculada, si existe, pertenece al mismo hotel.
- compra vinculada, si existe, esta `recibida`.
- existe corte de Caja abierto para el hotel actual.
- caja del corte pertenece al mismo hotel y esta activa.
- metodo de pago permitido por contrato de Caja.
- no hay transaccion externa abierta si el servicio debe controlar su propia transaccion.

## Orden transaccional futuro

Dentro de una unica transaccion:

1. Bloquear CxP con `FOR UPDATE`.
2. Releer proveedor scoped por hotel.
3. Releer corte abierto scoped por hotel.
4. Revalidar saldo, estado y monto.
5. Calcular `saldo_anterior` y `saldo_posterior`.
6. Insertar movimiento en `cuentas_por_pagar_movimientos` con tipo `PAGO_REFERENCIAL`.
7. Insertar movimiento en `movimientos_caja` con tipo `gasto`.
8. Actualizar saldo y estado de `cuentas_por_pagar`.
9. Registrar auditoria de exito.
10. Commit.

Si cualquier paso falla:

- rollback completo;
- auditoria de fallo si el patron lo permite sin romper la transaccion principal;
- mensaje claro al usuario;
- cero escrituras parciales.

## Movimiento CxP futuro

Campos esperados:

- `cuenta_por_pagar_id`
- `hotel_id`
- `tipo_movimiento = PAGO_REFERENCIAL`
- `monto`
- `saldo_anterior`
- `saldo_posterior`
- `referencia`
- `usuario_id`

No debe registrar abonos genericos ni tipos no documentados.

## Movimiento Caja futuro

Campos esperados:

- `hotel_id`
- `tipo = gasto`
- `categoria` o `categoria_id` segun contrato existente de Caja
- `descripcion`
- `monto`
- `metodo_pago`
- `referencia`
- `proveedor` como texto descriptivo, no fuente de verdad
- `usuario_id`
- `corte_id`

La fuente de verdad del proveedor debe seguir siendo `cuentas_por_pagar.proveedor_id` y
`proveedores.id`.

## Auditoria futura

Acciones sugeridas:

- `cuentas_por_pagar.pago_intentado`
- `cuentas_por_pagar.pago_registrado`
- `cuentas_por_pagar.pago_fallido`

Datos minimos:

- `hotel_id`
- `usuario_id`
- `cuenta_por_pagar_id`
- `proveedor_id`
- `corte_id`
- `monto`
- `saldo_anterior`
- `saldo_posterior`
- `movimiento_cxp_id`
- `movimiento_caja_id`

## Preflights requeridos antes de implementar

- CxP duplicada por compra.
- CxP con proveedor de otro hotel.
- CxP con compra de otro hotel.
- CxP pagada/cancelada con saldo positivo.
- CxP activa con saldo negativo.
- Corte abierto inexistente para el hotel de prueba.
- Mas de un corte abierto por caja/hotel.
- Movimiento CxP sin movimiento Caja asociado cuando aplique.
- Movimiento Caja de pago proveedor sin referencia trazable.
- Cambios en cortes cerrados.

## Prueba controlada futura

Debe usarse un registro local documentado y con backup previo.

Pruebas minimas:

1. Pago parcial menor al saldo.
2. Pago total exacto.
3. Monto mayor al saldo debe fallar.
4. CxP pagada debe fallar.
5. CxP cancelada debe fallar.
6. CxP de otro hotel debe fallar.
7. Proveedor de otro hotel debe fallar.
8. Sin corte abierto debe fallar.
9. Doble submit debe fallar o quedar idempotente por bloqueo transaccional.
10. Cero cambios en cortes cerrados.

## Rollback manual futuro

Si una prueba local escribe datos y debe revertirse:

1. Confirmar backup previo.
2. Identificar `cuenta_por_pagar_id`.
3. Identificar `movimiento_cxp_id`.
4. Identificar `movimiento_caja_id`.
5. Restaurar saldo/estado de CxP desde backup o desde `saldo_anterior`.
6. Reversar o eliminar solo los movimientos de prueba con IDs documentados.
7. Confirmar que el corte abierto vuelve a cuadrar.
8. Ejecutar preflight CxP/Caja.

No hacer rollback manual en produccion sin procedimiento aprobado.

## Definition of Done para implementar 3D-B real

- Backup verificado con ruta, tamano y SHA256.
- Servicio implementado y cubierto por preflight.
- Sin UI de pago todavia, salvo que se autorice 3D-C.
- `php -l`.
- Health checker sin errores.
- SQL antes/despues documentado.
- Prueba rollback local documentada.
- QA manual disponible.

## Estado final de esta subfase

3D-B queda como contrato tecnico. La implementacion real sigue bloqueada hasta que el
usuario autorice explicitamente backup, escrituras locales controladas y QA manual.
