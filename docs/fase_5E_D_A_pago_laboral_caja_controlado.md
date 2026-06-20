# Fase 5E-D-A - Pago laboral con Caja controlado

Estado formal:
`SERVICIO_5E_D_A_PAGO_LABORAL_CAJA_CONTROLADO_COMPLETADO`.

Fecha de cierre tecnico: 2026-06-20.

## Alcance implementado

- Servicio transaccional nuevo:
  `src/app/services/TrabajadorPagoCajaService.php`.
- Ruta POST nueva:
  `POST /trabajadores/{id}/registrar-pago-caja`.
- Integracion en `TrabajadorController` con:
  - `usuarios.edit`.
  - modulo `caja`.
  - CSRF.
  - token de pago de un solo uso.
  - delegacion al servicio.
- Panel nuevo en ficha de trabajador:
  `src/app/views/trabajadores/ver.php`.
- Simulador ajustado para descontar pagos laborales ya registrados en
  `trabajador_pagos_caja`.
- Herramienta rollback:
  `src/tools/saas/probar_pago_laboral_caja.php`.
- Checkers actualizados:
  `src/tools/saas/preflight_personal_pagos_caja.php` y
  `src/tools/saas/health_check_fase_1a.php`.

## Contrato operativo

El pago laboral real ya no se registra en `trabajador_pagos`.

`trabajador_pagos` conserva el rol de conceptos laborales manuales:
bonos, comisiones, descuentos y ajustes.

El pago real queda en:

- `trabajador_pagos_caja`: entidad laboral del pago.
- `movimientos_caja`: egreso de Caja con categoria `Pago laboral`.
- `logs_auditoria`: traza del evento.

El servicio recalcula saldo dentro de la transaccion:

`conceptos a favor - conceptos en contra - anticipos pendientes - prestamos vigentes - pagos laborales Caja pagados`.

## Guardas

- Requiere trabajador activo y del hotel actual.
- Requiere corte de Caja abierto.
- Requiere monto mayor a cero.
- Bloquea monto mayor al saldo disponible.
- Requiere referencia.
- Bloquea referencia duplicada en `trabajador_pagos_caja`.
- Bloquea referencia duplicada en `movimientos_caja`.
- Usa `FOR UPDATE` en trabajador, corte y filas laborales relacionadas.
- Puede correr con transaccion propia o con transaccion externa de prueba mediante
  `manage_transaction=false`.

## Pruebas automaticas

Lint PHP dentro del contenedor Docker:

- `app/services/TrabajadorPagoCajaService.php`: OK.
- `app/controllers/TrabajadorController.php`: OK.
- `app/models/Trabajador.php`: OK.
- `app/views/trabajadores/ver.php`: OK.
- `tools/saas/probar_pago_laboral_caja.php`: OK.
- `tools/saas/preflight_personal_pagos_caja.php`: OK.
- `tools/saas/health_check_fase_1a.php`: OK.
- `config/routes.php`: OK.

Preflight 5E:

- `OK: 41`
- `WARNING: 0`
- `ERROR: 0`

Rollback automatizado:

- Crea trabajador/concepto temporal si no existe saldo elegible.
- Crea pago laboral temporal.
- Crea movimiento Caja temporal.
- Bloquea referencia duplicada.
- Revierte pagos, Caja, auditoria, trabajador y concepto.
- Resultado: completado sin cambios persistentes.

Health general:

- `OK: 313`
- `WARNING: 25`
- `ERROR: 0`

## Fuera de alcance

- Reversion de pago laboral.
- Abonos o liquidaciones automaticas de anticipos/prestamos.
- Nomina automatica.
- Cambios a permisos/auth mas alla de reutilizar `usuarios.edit` y modulo `caja`.
- Nuevas migraciones.
- Cambios PWA/offline, IndexedDB, cache names o `/api/sync`.

`/api/sync` permanece bloqueado por health con `sync_temporarily_disabled` y HTTP 423.

## QA manual recomendada

1. Abrir una ficha de trabajador activo.
2. Confirmar que se muestra el panel `Pago laboral con Caja`.
3. Confirmar que si no hay saldo elegible se muestra bloqueo y enlace al simulador.
4. Crear o usar un concepto laboral a favor para generar saldo positivo.
5. Confirmar que el panel muestra `Maximo elegible`.
6. Registrar pago con referencia unica.
7. Confirmar mensaje de exito con movimiento Caja.
8. Confirmar que `trabajador_pagos_caja` aumenta en 1.
9. Confirmar que `movimientos_caja` aumenta en 1 con categoria `Pago laboral`.
10. Intentar repetir la misma referencia y confirmar bloqueo.
11. Confirmar que el simulador descuenta el pago ya registrado.

## Siguiente paso seguro

5E-D-F cierre documental/manual de QA del pago laboral con Caja.

El cierre quedo documentado en:

- `docs/fase_5E_D_F_cierre_pago_laboral_caja.md`.
