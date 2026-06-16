# Fase MANT-E-0 - Contrato activacion manual de mantenimiento vencido

Estado: `CONTRATO_MANT_E_0_ACTIVACION_MANUAL_COMPLETADO`

## Objetivo

Definir un contrato seguro para una futura activacion manual, explicita y controlada de
mantenimientos programados vencidos o para hoy.

Esta fase no implementa rutas, botones, POST, migraciones ni escrituras. Solo deja
documentado el alcance minimo, riesgos y condiciones para una subfase futura.

## Diagnostico

- `Mantenimiento::previewProgramados()` ya permite observar candidatos sin escribir.
- `Mantenimiento::iniciarProgramado()` existe y cambia:
  - mantenimiento `programado` a `en_proceso`;
  - habitacion asociada a `mantenimiento`.
- `Mantenimiento::activarMantenimientosPendientes()` existe y puede activar varios
  registros vencidos, por lo que sigue prohibido conectarlo a web, dashboard o cron.
- El warning historico de 1 habitacion en mantenimiento sin registro `en_proceso` debe
  mantenerse visible antes de automatizar.

## Alcance permitido para una futura MANT-E-A

- Accion manual por registro individual, nunca masiva.
- Solo desde un mantenimiento `programado`, vencido o de hoy.
- Validar `hotel_id` del mantenimiento, habitacion y usuario actual.
- Validar que la habitacion este `disponible`.
- Validar que no exista mantenimiento `en_proceso` para la misma habitacion/hotel.
- Validar que no existan reservaciones conflictivas.
- Usar CSRF, permiso `habitaciones.mantenimiento` y auditoria.
- Usar transaccion si el patron local lo permite.
- Redirigir de vuelta a preview o ficha de habitacion con mensaje claro.
- Agregar preflight/health y QA manual obligatoria.

## Fuera de alcance

- No activacion automatica.
- No cron, jobs, listeners ni triggers.
- No activacion masiva.
- No cierre automatico.
- No crear tareas automaticamente.
- No modificar reservaciones.
- No tocar Caja, pagos, abonos, nomina, offline, PWA ni `/api/sync`.
- No usar `activarMantenimientosPendientes()` desde una ruta web.

## Semaforo de riesgo

- Verde: contrato, validaciones read-only, mensajes, pruebas de HTTP sin sesion.
- Amarillo: POST manual individual con transaccion, CSRF, auditoria y rollback claro.
- Rojo: activacion masiva, automatica, por cron, sin QA manual o sin backup.

## Definition of Done futura para MANT-E-A

- POST individual protegido por sesion, permiso, CSRF y modulo correspondiente.
- Validaciones centrales en modelo/servicio, no solo en vista.
- Transaccion atomica para mantenimiento y habitacion.
- Auditoria de intento exitoso y bloqueado.
- Doble activacion bloqueada limpiamente.
- Reservaciones conflictivas bloquean la activacion.
- Health/preflight detectan ausencia de activacion masiva/cron/Caja.
- SQL antes/despues documentado en entorno local.
- QA manual obligatoria antes de cerrar el bloque.

## Rollback futuro previsto

- Si se implementa MANT-E-A, rollback de codigo: retirar ruta POST, boton, metodo de
  controlador, validaciones nuevas y checks.
- DB: si QA manual activa un mantenimiento de prueba, revertir solo mediante flujo
  autorizado de finalizacion/cancelacion segun estado; no borrar SQL manualmente.

## Estado actual

- MANT-E-0 no cambia codigo ni DB.
- La siguiente accion segura, si se autoriza, debe ser MANT-E-A con backup/checklist y
  QA manual explicita, o bien saltar a otro bloque read-only independiente.
