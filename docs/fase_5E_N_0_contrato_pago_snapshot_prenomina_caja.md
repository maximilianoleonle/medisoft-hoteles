# Fase 5E-N-0 - Contrato pago desde snapshot de pre-nomina con Caja

Estado formal:
`CONTRATO_5E_N_0_PAGO_SNAPSHOT_PRENOMINA_CAJA_COMPLETADO`.

## Objetivo

Definir el contrato para una futura implementacion de pago laboral controlado
desde un snapshot aprobado de pre-nomina, usando Caja y el servicio transaccional
existente de pagos laborales.

Esta fase es solo documental. No agrega codigo, rutas, controladores, modelos,
servicios, vistas, migraciones, permisos, datos, storage, Caja, PWA/offline ni
`/api/sync`.

## Contexto

- 5E-D-A ya permite registrar un pago laboral individual con Caja desde la ficha
  del trabajador.
- 5E-L-A permite cerrar, aprobar y anular snapshots administrativos de
  pre-nomina.
- 5E-M-A/F permite consultar y exportar esos snapshots en modo GET/read-only.

El siguiente paso operativo natural es permitir que un snapshot aprobado sirva
como contexto de pago, sin convertirlo en nomina oficial ni pago masivo.

## Principio central

El snapshot no debe ser fuente unica de saldo real.

El snapshot puede funcionar como:

- evidencia administrativa del periodo aprobado;
- contexto del trabajador y rango de fechas;
- tope sugerido por `pendiente_pago_sugerido`.

El saldo real y la elegibilidad deben recalcularse al momento del pago usando
el flujo de Caja vigente. El monto final autorizado debe ser menor o igual a:

```text
min(
  pendiente_pago_sugerido_del_snapshot,
  saldo_laboral_disponible_recalculado_en_vivo
)
```

Si cualquiera de los dos valores es cero o negativo, el pago debe bloquearse.

## Alcance futuro permitido

Una futura fase 5E-N-A podria:

1. Mostrar en el detalle del snapshot aprobado una accion individual por
   trabajador elegible.
2. Reutilizar el evaluador de pago laboral con Caja para recalcular saldo vivo.
3. Registrar un pago individual, no masivo, desde una fila de detalle del
   snapshot.
4. Enviar al servicio el periodo del snapshot como contexto.
5. Registrar el pago en `trabajador_pagos_caja` y el egreso en
   `movimientos_caja` mediante el servicio transaccional existente.
6. Registrar auditoria que indique que el origen operativo fue un snapshot de
   pre-nomina.
7. Bloquear snapshot cerrado, anulado, de otro hotel o con trabajador no
   elegible.

## Rutas candidatas futuras

Lectura/simulacion contextual, solo si se autoriza:

- `GET /trabajadores/nomina/periodos/{periodoId}/pagos`

Mutacion candidata, solo con autorizacion explicita posterior:

- `POST /trabajadores/nomina/periodos/{periodoId}/detalles/{detalleId}/registrar-pago-caja`

Estas rutas no quedan implementadas ni autorizadas por 5E-N-0.

## Reglas de elegibilidad

Un pago futuro desde snapshot debe bloquearse si:

- no hay sesion activa;
- falta contexto de hotel;
- falta modulo Personal;
- falta modulo Caja;
- falta permiso operativo;
- falta CSRF;
- falta token de pago de un solo uso;
- el snapshot no pertenece al hotel actual;
- el snapshot no esta en estado `aprobado`;
- el snapshot esta `cerrado` sin aprobacion;
- el snapshot esta `anulado`;
- el detalle no pertenece al snapshot;
- el trabajador no pertenece al hotel actual;
- el trabajador esta inactivo;
- `pendiente_pago_sugerido` del detalle no es positivo;
- el evaluador vivo de Caja devuelve saldo no positivo;
- no existe corte de Caja abierto del mismo hotel;
- el monto solicitado excede el tope permitido;
- la referencia ya existe en `trabajador_pagos_caja` o `movimientos_caja`;
- se intenta pagar mas de un trabajador en una sola peticion.

## Payload futuro minimo

El formulario futuro debe aceptar solo:

- `monto`;
- `metodo_pago`;
- `referencia`;
- `notas`;
- `pago_token`;
- `csrf_token`.

No debe aceptar desde request:

- `hotel_id`;
- `trabajador_id`;
- `corte_id`;
- `movimiento_caja_id`;
- `periodo_inicio`;
- `periodo_fin`;
- `saldo_anterior`;
- `saldo_posterior`;
- `created_by`;
- `updated_by`;
- `estado`;
- identificadores de Caja.

`trabajador_id`, `periodo_inicio` y `periodo_fin` deben obtenerse del snapshot y
su detalle, siempre validados por `hotel_id`.

## Trazabilidad recomendada

Para trazar con precision que un pago nacio desde un snapshot, una fase futura
podria requerir una migracion aditiva y nullable en `trabajador_pagos_caja`:

- `nomina_periodo_id INT NULL`;
- `nomina_periodo_detalle_id INT NULL`.

Esa migracion no queda autorizada por este contrato. Requiere backup,
autorizacion explicita y rollback propio.

Sin esa migracion, cualquier implementacion futura solo podria dejar contexto en
`concepto`, `referencia`, `notas` y auditoria, lo cual es menos fuerte para
trazabilidad financiera.

## Orden transaccional futuro

Una implementacion futura debe usar un servicio envoltorio o extension
transaccional, por ejemplo:

- `TrabajadorNominaSnapshotPagoService::registrarPagoDesdeSnapshot(...)`.

Orden recomendado:

1. Validar IDs del snapshot y detalle.
2. Validar CSRF y token de pago de un solo uso en el controlador.
3. Iniciar transaccion.
4. Leer snapshot con `FOR UPDATE`.
5. Leer detalle con `FOR UPDATE`.
6. Validar `hotel_id`, estado `aprobado`, trabajador y rango.
7. Recalcular saldo vivo con el servicio de pago laboral con Caja.
8. Validar tope `min(snapshot_pendiente, saldo_vivo)`.
9. Registrar pago laboral con Caja usando el servicio existente en modo
   transaccional coordinado.
10. Registrar auditoria con `periodo_id`, `detalle_id`, `trabajador_id`,
    `pago_caja_id`, `movimiento_caja_id`, monto y referencia.
11. Confirmar transaccion.

Si cualquier paso falla, hacer rollback completo.

## Inmutabilidad del snapshot

El snapshot aprobado debe permanecer congelado.

Un pago desde snapshot no debe:

- modificar `trabajador_nomina_periodo_detalles`;
- recalcular totales del snapshot;
- cambiar `pendiente_pago_sugerido`;
- cambiar estado del snapshot;
- borrar eventos;
- convertir el snapshot en nomina oficial.

La realidad de pago debe vivir en `trabajador_pagos_caja`,
`movimientos_caja` y auditoria.

## Escrituras autorizables en fase futura

Solo con autorizacion explicita para 5E-N-A:

- insertar `movimientos_caja` tipo `gasto`;
- insertar `trabajador_pagos_caja`;
- registrar auditoria en `logs_auditoria`;
- opcionalmente, con migracion autorizada, guardar relacion nullable hacia
  `trabajador_nomina_periodos` y `trabajador_nomina_periodo_detalles`.

## Prohibido por este contrato

5E-N-0 no autoriza:

- rutas nuevas;
- formularios nuevos;
- botones de pago;
- migraciones;
- cambios en `trabajador_pagos_caja`;
- pago masivo;
- pago automatico de todos los trabajadores del snapshot;
- liquidacion automatica de anticipos o prestamos;
- modificar snapshots;
- reabrir snapshots;
- crear nomina oficial;
- CFDI;
- timbrado;
- dispersion bancaria;
- folios oficiales;
- archivos en storage;
- cambios de permisos/auth;
- cambios en PWA/offline, IndexedDB, cache names o `/api/sync`.

## QA futura minima

1. Intentar pagar desde snapshot sin sesion y confirmar redireccion a login.
2. Intentar pagar sin CSRF y confirmar bloqueo.
3. Intentar pagar sin token de un solo uso y confirmar bloqueo.
4. Intentar pagar desde snapshot `cerrado` y confirmar bloqueo.
5. Intentar pagar desde snapshot `anulado` y confirmar bloqueo.
6. Intentar pagar desde snapshot de otro hotel y confirmar bloqueo.
7. Intentar pagar detalle de otro snapshot y confirmar bloqueo.
8. Intentar pagar monto mayor al pendiente del snapshot y confirmar bloqueo.
9. Intentar pagar monto mayor al saldo vivo recalculado y confirmar bloqueo.
10. Registrar un pago individual valido y confirmar movimiento Caja + pago
    laboral + auditoria.
11. Confirmar que el snapshot no cambia.
12. Confirmar que no se crean pagos masivos, CFDI, timbrado ni dispersion.
13. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

## Criterio de avance futuro

Una futura 5E-N-A solo debe iniciar con autorizacion explicita para tocar rutas,
controlador, servicio/modelo, vista, Caja, auditoria, checkers, prueba rollback y,
si se decide trazabilidad fuerte, migracion con backup.
