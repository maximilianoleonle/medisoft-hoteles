# Fase 5E-D-F - Cierre pago laboral con Caja

## Estado

`CIERRE_5E_D_F_PAGO_LABORAL_CAJA_QA_MANUAL_VALIDADA`

## Objetivo

Cerrar formalmente la implementacion 5E-D-A de pago laboral con Caja despues de la
validacion manual exitosa del usuario.

## Alcance

Este cierre es documental.

No agrega codigo PHP, rutas, controladores, modelos, vistas, formularios, migraciones,
datos, escrituras de Caja, cambios de permisos/auth, cambios PWA/offline ni cambios
en `/api/sync`.

## Bloques cubiertos

- 5E-0 contrato de pagos laborales con Caja.
- 5E-A preflight read-only.
- 5E-B-0 contrato de migracion aditiva.
- 5E-B-A migracion aditiva de `trabajador_pagos_caja`.
- 5E-C-0 contrato del simulador.
- 5E-C-A simulador GET/read-only.
- 5E-C-F cierre manual del simulador.
- 5E-D-0 contrato del servicio.
- 5E-D-A pago laboral con Caja controlado.

## Evidencia manual 5E-D-A

El usuario confirmo que la prueba manual paso correctamente.

Evidencia observada en la ficha de trabajador:

- Mensaje de exito de pago laboral registrado con movimiento de Caja.
- `trabajador_pagos_caja` queda reflejado en el total de pagos Caja.
- El resumen laboral separa:
  - saldo bruto laboral;
  - pagos Caja aplicados;
  - saldo disponible para pago.
- Tras pagar el saldo completo, el disponible queda en `$0.00`.
- El panel `Pago laboral con Caja` queda bloqueado por saldo no positivo.
- El ledger laboral conserva el saldo bruto informativo y aclara el disponible despues
  de Caja.

## Validaciones automaticas de cierre

Ultima corrida previa al cierre:

- `docker compose exec app php -l app/views/trabajadores/ver.php`: OK.
- `docker compose exec app php tools/saas/preflight_personal_pagos_caja.php`:
  `OK: 41`, `WARNING: 0`, `ERROR: 0`.
- `docker compose exec app php tools/saas/health_check_fase_1a.php`:
  `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- `git diff --check`: sin errores; solo advertencias CRLF existentes en el working
  tree.

## Decision de cierre

5E-D-A queda cerrado como flujo controlado de pago laboral individual con Caja.

La fuente de verdad del pago real es `trabajador_pagos_caja`; el egreso financiero
vinculado vive en `movimientos_caja` con categoria `Pago laboral`; la auditoria queda
en `logs_auditoria`.

`trabajador_pagos` permanece como ledger de conceptos laborales y no representa pago
real.

## Limites despues del cierre

No existe todavia reversion de pago laboral con Caja.

No avanzar desde este cierre a:

- reversiones;
- pagos masivos;
- nomina automatica;
- abonos o liquidaciones automaticas de anticipos/prestamos;
- cambios de Caja/cortes fuera del servicio;
- cambios de permisos/auth;
- PWA/offline, IndexedDB, cache names o `/api/sync`.

## Siguiente paso seguro

El siguiente paso seguro es abrir un contrato independiente antes de cualquier nueva
superficie operativa. Opciones recomendadas:

- `5E-E-0`: contrato de reversion de pago laboral con Caja.
- `5E-D-B-0`: contrato de historial/read-only detallado de pagos laborales Caja en
  ficha y/o simulador.

Cualquier implementacion posterior debe pedir autorizacion explicita para tocar los
archivos correspondientes.
