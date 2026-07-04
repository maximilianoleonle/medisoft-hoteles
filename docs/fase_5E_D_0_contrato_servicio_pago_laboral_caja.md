# Fase 5E-D-0 - Contrato servicio pago laboral con Caja

## Estado

`CONTRATO_5E_D_0_SERVICIO_PAGO_LABORAL_CAJA_COMPLETADO`

## Objetivo

Definir el contrato tecnico del futuro servicio transaccional para registrar pagos laborales reales contra Caja.

Esta fase es documental. No implementa codigo PHP, rutas, formularios, botones, migraciones, servicios ni escrituras financieras.

## Contexto vigente

- 5E-B-A creo `trabajador_pagos_caja` como tabla independiente.
- 5E-C-A implemento el simulador GET/read-only.
- 5E-C-F dejo validada la QA manual del simulador.
- `trabajador_pagos_caja` permanece vacia.
- `trabajador_pagos` sigue siendo tabla de conceptos laborales, no pago real.
- Todavia no existe `TrabajadorPagoCajaService`.
- Todavia no existe POST operativo de pago laboral con Caja.

## Servicio futuro propuesto

Servicio futuro: `TrabajadorPagoCajaService`.

Metodo sugerido:

- `registrarPagoLaboralCaja(int $hotelId, int $trabajadorId, array $payload, ?int $usuarioId): array`

Ruta futura, solo con autorizacion explicita posterior:

- `POST /trabajadores/{id:[0-9]+}/registrar-pago-caja`

Pantallas futuras posibles:

- formulario en `/trabajadores/{id}`;
- diagnostico previo desde `/trabajadores/pagos-caja/simulador`;
- sin acciones masivas desde el listado.

## Payload futuro minimo

- `monto`;
- `metodo_pago`;
- `referencia`;
- `periodo_inicio`;
- `periodo_fin`;
- `concepto`;
- `notas`;
- `pago_token`.

No debe aceptar desde request: `hotel_id`, `corte_id`, `movimiento_caja_id`, `saldo_anterior`, `saldo_posterior`, `created_by` ni `updated_by`.

## Fuentes de verdad

- Trabajador: `trabajadores`.
- Conceptos laborales: `trabajador_pagos`.
- Anticipos informativos: `trabajador_anticipos`.
- Prestamos informativos: `trabajador_prestamos`.
- Pago laboral real: `trabajador_pagos_caja`.
- Egreso real de Caja: `movimientos_caja`.
- Corte operativo: `cortes_caja`.
- Caja activa: `cajas`.
- Auditoria: `logs_auditoria`.

`trabajador_pagos` no debe convertirse en fuente de pagos reales.

## Validaciones obligatorias del servicio

El servicio futuro debera validar sesion activa desde el controlador, contexto hotelero activo, modulo de Personal bajo guardas existentes, modulo `caja` activo, permiso operativo adecuado, CSRF valido, token de pago de un solo uso, trabajador por `id + hotel_id`, trabajador activo, monto mayor a cero, metodo permitido, referencia normalizada obligatoria, referencia no duplicada en `trabajador_pagos_caja` ni en `movimientos_caja`, periodo valido, corte abierto del mismo hotel, caja activa del mismo hotel, saldo laboral disponible mayor a cero y monto menor o igual al saldo.

## Saldo laboral disponible

El servicio futuro debe recalcular dentro de la transaccion:

```text
saldo_disponible =
  conceptos_activos_a_favor
  - conceptos_activos_en_contra
  - anticipos_pendientes
  - prestamos_vigentes
  - pagos_laborales_caja_pagados
```

Reglas: filtrar por `hotel_id`, filtrar por `trabajador_id`, aplicar periodo si se captura, ignorar pagos revertidos, no confiar en montos del frontend y no persistir el saldo calculado.

## Orden transaccional futuro

El servicio debe controlar su propia transaccion.

Orden recomendado:

1. Normalizar payload.
2. Validar IDs, monto, metodo, referencia, periodo y notas.
3. Iniciar transaccion.
4. Leer trabajador con `FOR UPDATE`.
5. Leer corte abierto y caja activa con `FOR UPDATE`.
6. Recalcular saldo disponible con datos bloqueados o consistentes.
7. Validar referencia duplicada con bloqueo suficiente.
8. Insertar egreso en `movimientos_caja`.
9. Insertar pago laboral real en `trabajador_pagos_caja` con `movimiento_caja_id`.
10. Registrar auditoria `trabajadores.pago_laboral_caja_registrado`.
11. Confirmar transaccion.

Si cualquier paso falla, hacer rollback completo.

## Semantica de `trabajador_pagos_caja`

El registro futuro debera usar `hotel_id`, `trabajador_id`, `movimiento_caja_id`, `corte_id`, `monto`, `metodo_pago`, `referencia`, `periodo_inicio`, `periodo_fin`, `concepto`, `fecha_pago`, `estado = pagado`, `notas`, `created_by` y `updated_by`.

No debe alterar `trabajador_pagos`.

## Semantica del movimiento Caja

El movimiento de Caja futuro debera ser `tipo = gasto`, `categoria = Pago laboral`, `descripcion = Pago laboral trabajador #ID`, `monto = monto pagado`, `metodo_pago = metodo validado`, `referencia = referencia unica`, `usuario_id = usuario actual` y `corte_id = corte abierto validado`.

No debe usar campos de proveedor ni reservacion como fuente de verdad.

## Token de un solo uso

El controlador futuro debera crear un token por trabajador y sesion, por ejemplo:

- `$_SESSION['trabajador_pago_caja_tokens'][$trabajadorId]`.

El POST debe consumirlo antes de llamar al servicio. Un reintento del mismo formulario debe fallar.

## Auditoria futura

Evento propuesto: `trabajadores.pago_laboral_caja_registrado`.

Debe registrar `hotel_id`, `usuario_id`, `entidad_tipo = trabajador`, `entidad_id`, `trabajador_pago_caja_id`, `movimiento_caja_id`, `corte_id`, monto, metodo de pago, periodo, referencia y saldo disponible calculado antes del pago.

## Escrituras autorizables en fase futura

Solo si se autoriza 5E-D-A:

- insertar `movimientos_caja` tipo `gasto`;
- insertar `trabajador_pagos_caja`;
- registrar auditoria en `logs_auditoria`.

## Escrituras prohibidas por este contrato

Este contrato no autoriza escribir en `trabajador_pagos`, `trabajador_anticipos`, `trabajador_prestamos`, `trabajadores`, `cortes_caja`, `cajas`, `categorias_movimientos`, `migrations`, PWA/offline ni `/api/sync`.

Tampoco autoriza rutas POST, formularios de pago, botones de pago, servicio PHP, token real, prueba rollback real, reversion, abonos/liquidaciones automaticas, nomina automatica ni pagos masivos.

## Prueba rollback futura

Antes de habilitar UI real, crear herramienta CLI:

- `tools/saas/probar_pago_laboral_caja.php`

La prueba debe abrir transaccion externa, instanciar el servicio en modo `manage_transaction = false`, registrar un pago laboral temporal pequeno, verificar movimiento Caja tipo `gasto`, verificar registro en `trabajador_pagos_caja`, verificar auditoria temporal si aplica, hacer rollback y confirmar que los conteos reales vuelven al estado previo.

## Preflights requeridos antes de implementar

Antes de 5E-D-A:

- `php tools/saas/preflight_personal_pagos_caja.php`;
- `php tools/saas/health_check_fase_1a.php`;
- backup completo con ruta, tamano y SHA256;
- conteos antes de `trabajador_pagos_caja`, `movimientos_caja` y `logs_auditoria`;
- confirmacion de corte abierto;
- confirmacion de trabajador activo elegible;
- validacion manual del simulador.

## Definition of Done para 5E-D-A futura

- Backup verificado.
- Servicio implementado con transaccion y locks.
- Token de un solo uso.
- Prueba rollback sin persistencia.
- `php -l` en archivos tocados.
- Preflight 5E con `ERROR: 0`.
- Health general con `ERROR: 0`.
- HTTP sin sesion protegido.
- QA manual documentada.
- Conteos antes/despues documentados.

## Siguiente paso seguro

El siguiente paso seguro seria `5E-D-A`, implementacion controlada del servicio, solo con autorizacion explicita para tocar servicio, controlador/ruta POST, vista/formulario, Caja, auditoria, prueba rollback y datos locales de QA.
