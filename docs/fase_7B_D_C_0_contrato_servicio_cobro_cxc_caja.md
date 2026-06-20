# Fase 7B-D-C-0 - Contrato servicio cobro CxC con Caja

## Estado

`CONTRATO_7B_D_C_0_SERVICIO_COBRO_CXC_CAJA_COMPLETADO`

## Objetivo

Definir el contrato tecnico del futuro servicio transaccional para registrar cobros de
cuentas por cobrar contra Caja.

Esta fase es documental. No implementa codigo PHP, rutas, formularios, botones,
migraciones, servicios ni escrituras financieras.

## Contexto vigente

- 7B-C-A creo manualmente la CxC operativa `#1` desde reservacion `#24`.
- 7B-D-A implemento el simulador GET/read-only de cobro CxC.
- 7B-D-B-A agrego el tipo semantico `COBRO` al enum
  `cuentas_por_cobrar_movimientos.tipo_movimiento`.
- Todavia no existen movimientos CxC tipo `COBRO`.
- Todavia no existen movimientos de Caja relacionados con CxC.

## Superficie futura propuesta

Servicio futuro:

- `CuentaPorCobrarCobroService`

Ruta futura, solo con autorizacion explicita posterior:

- `POST /cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja`

Pantallas futuras posibles:

- formulario en `/cuentas-por-cobrar/operativas/{id}`;
- diagnostico previo desde `/cuentas-por-cobrar/simulador-caja`;
- sin acciones desde el listado masivo.

## Fuentes de verdad

- Deuda y saldo CxC: `cuentas_por_cobrar`.
- Trazabilidad interna CxC: `cuentas_por_cobrar_movimientos`.
- Ingreso real de Caja: `movimientos_caja`.
- Corte operativo: `cortes_caja`.
- Caja activa: `cajas`.
- Contexto de reservacion: `reservaciones`.
- Contexto de huesped: `huespedes`.
- Auditoria: `logs_auditoria`.

`reservacion_pagos` y `reservacion_abonos` quedan como historicos sensibles. El cobro
CxC futuro no debe escribir ahi para evitar doble contabilidad.

## Validaciones obligatorias del servicio

El servicio futuro debera validar:

- sesion activa;
- contexto hotelero activo;
- modulo `reservaciones` activo;
- modulo `caja` activo;
- permiso operativo adecuado;
- CSRF valido en el controlador;
- token de cobro de un solo uso por cuenta;
- CxC por `id + hotel_id`;
- `hotel_id` valido y no recibido desde formulario;
- estado CxC permitido: `pendiente`, `parcial` o `vencida`;
- total mayor a cero;
- saldo mayor a cero;
- saldo menor o igual al total;
- monto mayor a cero;
- monto menor o igual al saldo;
- metodo de pago permitido por Caja: `efectivo`, `tarjeta`, `transferencia`;
- corte de Caja abierto del mismo hotel;
- caja activa del mismo hotel;
- reservacion vinculada existente y del mismo hotel, si aplica;
- factura vinculada existente y del mismo hotel, si aplica;
- referencia externa normalizada y no duplicada para la misma CxC, si el usuario la
  captura.

## Bloqueos obligatorios

El servicio debe bloquear:

- CxC inexistente o de otro hotel;
- CxC `liquidada`, `cancelada` o `incobrable`;
- CxC sin saldo;
- monto mayor al saldo;
- corte inexistente, cerrado o de otro hotel;
- caja inactiva;
- reservacion cancelada si la cuenta depende de esa reservacion;
- referencia duplicada;
- doble envio por token;
- cualquier intento de escribir fuera de las tablas autorizadas.

## Orden transaccional futuro

El servicio debe controlar su propia transaccion.

Orden recomendado:

1. Validar IDs, monto, metodo, referencia y notas.
2. Iniciar transaccion.
3. Leer CxC con `FOR UPDATE`.
4. Leer corte abierto con `FOR UPDATE`.
5. Validar bloqueos con datos bloqueados.
6. Validar referencia duplicada con `FOR UPDATE`.
7. Calcular:
   - `saldo_anterior`;
   - `saldo_posterior`;
   - `estado_posterior`.
8. Insertar movimiento CxC tipo `COBRO`.
9. Insertar ingreso en `movimientos_caja`.
10. Actualizar `cuentas_por_cobrar.saldo`, `estado`, `actualizado_por_usuario_id` y
    `updated_at`.
11. Registrar auditoria `cuentas_por_cobrar.cobro_caja_registrado`.
12. Confirmar transaccion.

Si cualquier paso falla, hacer rollback completo.

## Semantica del movimiento CxC

El movimiento interno futuro debera usar:

- `tipo_movimiento = COBRO`;
- `monto = monto cobrado`;
- `saldo_anterior = saldo antes del cobro`;
- `saldo_posterior = saldo despues del cobro`;
- `referencia = referencia externa o referencia generada`;
- `notas = notas normalizadas`;
- `usuario_id = usuario que registra`.

No usar:

- `AJUSTE` para cobros;
- `CANCELACION` para cobros;
- `RECLASIFICACION` para cobros.

## Semantica del movimiento Caja

El movimiento de Caja futuro debera ser:

- `tipo = ingreso`;
- `categoria = Cobro CxC` o categoria equivalente aprobada;
- `descripcion = Cobro CxC #ID - huesped/folio`;
- `monto = monto cobrado`;
- `metodo_pago = metodo validado`;
- `referencia = CXC-{cuenta_id}-MOV-{movimiento_cxc_id}` si no hay referencia externa;
- `reservacion_id = reservacion vinculada si existe`;
- `usuario_id = usuario actual`;
- `corte_id = corte abierto validado`.

No debe usar campos de proveedor.

## Estado posterior de CxC

Reglas:

- Si `saldo_posterior <= 0.004`, estado `liquidada`.
- Si `saldo_posterior > 0`, estado `parcial`.
- No cambiar a `vencida` automaticamente desde el cobro; vencimiento debe ser otra fase
  o un proceso read-only/administrativo separado.

## Token de un solo uso

El controlador futuro debera crear un token por cuenta y sesion, por ejemplo:

- `$_SESSION['cxc_cobro_tokens'][$cuentaId]`.

El POST debe consumirlo antes de llamar al servicio. Un reintento del mismo formulario
debe fallar.

## Prueba rollback futura

Antes de habilitar UI real, crear herramienta CLI:

- `tools/saas/probar_cobro_cxc_caja.php`

La prueba debe:

1. abrir transaccion externa;
2. instanciar el servicio en modo `manage_transaction = false`;
3. registrar un cobro temporal pequeno sobre una CxC elegible;
4. verificar movimiento CxC tipo `COBRO`;
5. verificar movimiento Caja tipo `ingreso`;
6. verificar saldo/estado temporal;
7. hacer rollback;
8. confirmar que los conteos reales vuelven al estado previo.

## Auditoria futura

Evento propuesto:

- `cuentas_por_cobrar.cobro_caja_registrado`

Debe registrar:

- `hotel_id`;
- `usuario_id`;
- `entidad_tipo = cuentas_por_cobrar`;
- `entidad_id`;
- estado/saldo antes;
- estado/saldo despues;
- monto;
- metodo de pago;
- `cuenta_por_cobrar_movimiento_id`;
- `movimiento_caja_id`;
- `corte_id`;
- referencia.

No registrar datos sensibles innecesarios ni rutas internas.

## Escrituras autorizables en fase futura

Solo si se autoriza 7B-D-C-A:

- insertar `cuentas_por_cobrar_movimientos` tipo `COBRO`;
- insertar `movimientos_caja` tipo `ingreso`;
- actualizar `cuentas_por_cobrar.saldo`;
- actualizar `cuentas_por_cobrar.estado`;
- registrar auditoria en `logs_auditoria`.

## Escrituras prohibidas por este contrato

Este contrato no autoriza escribir en:

- `reservaciones`;
- `reservacion_pagos`;
- `reservacion_abonos`;
- `solicitudes_factura`;
- `cortes_caja`;
- `cajas`;
- PWA/offline;
- `/api/sync`.

Tampoco autoriza:

- rutas POST;
- formularios de cobro;
- botones de cobro;
- pruebas que dejen datos reales;
- cobros masivos;
- anulaciones/reversiones.

## Rollback funcional futuro

Si un cobro real se registra por error, no borrar fisicamente sin fase formal.

Estrategia recomendada:

1. Identificar movimiento CxC tipo `COBRO`.
2. Identificar movimiento Caja asociado por referencia.
3. Definir si se hara anulacion contable o restauracion controlada.
4. Ejecutar rollback solo con script transaccional revisado.
5. Registrar auditoria de anulacion/reversion.

Antes de escalar cobros reales conviene disenar 7B-D-R-0 como contrato de anulacion o
reversion de cobros CxC.

## Definition of Done para implementacion futura

Cuando se autorice 7B-D-C-A, debera completarse:

- backup con SHA256;
- servicio `CuentaPorCobrarCobroService`;
- ruta POST controlada;
- formulario solo en detalle elegible;
- CSRF;
- token de un solo uso;
- prueba rollback CLI;
- `php -l` en PHP tocado;
- preflight CxC actualizado;
- health general;
- HTTP sin sesion bloqueado/redirigido;
- `/api/sync` intacto;
- QA manual con monto pequeno.

## Siguiente paso seguro

7B-D-C-A solo debe implementarse con autorizacion explicita de escrituras financieras.

Si no se autoriza escritura financiera, el siguiente paso alternativo es mejorar el
simulador para mostrar el flujo esperado del cobro sin generar POST ni datos.

## Implementacion posterior

7B-D-C-A fue implementada posteriormente con servicio transaccional, ruta POST,
formulario condicionado y prueba rollback.

Ver:

- `docs/fase_7B_D_C_A_cobro_cxc_caja.md`

La implementacion queda pendiente de QA manual real antes de marcarse como validada.
