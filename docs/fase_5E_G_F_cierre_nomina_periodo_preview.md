# Fase 5E-G-F - Cierre preview read-only de nomina por periodo

## Estado

`CIERRE_5E_G_F_NOMINA_PERIODO_PREVIEW_QA_MANUAL_VALIDADA`

## Objetivo

Cerrar formalmente la implementacion 5E-G-A del preview read-only de nomina por periodo
despues de la validacion manual exitosa del usuario.

## Alcance

Este cierre es documental.

No agrega codigo PHP, rutas, controladores, modelos, vistas, formularios, migraciones,
datos, escrituras de Caja, cambios de permisos/auth, cambios PWA/offline ni cambios en
`/api/sync`.

## Bloques cubiertos

- 5E-G-0 contrato documental del preview read-only de nomina por periodo.
- 5E-G-A implementacion GET/read-only del preview.

## Evidencia manual 5E-G-A

El usuario confirmo que la prueba manual paso correctamente.

Evidencia esperada validada en la pantalla:

- `/trabajadores/nomina/preview` abre en local.
- Sin fechas, la pantalla bloquea el preview por periodo requerido.
- Con periodo valido, muestra trabajadores del hotel actual.
- Muestra bruto del periodo, deducciones informativas, pagos Caja aplicados y neto
  sugerido.
- El filtro `Pagos Caja` solo cambia el calculo visible.
- El filtro `Con saldo` limita trabajadores con neto positivo.
- No hay botones de pago masivo, recibo oficial, dispersion ni POST.
- No se generan movimientos de Caja ni registros laborales.

## Validaciones automaticas de cierre

Ultima corrida previa al cierre:

- `app/models/Trabajador.php`: lint OK.
- `app/controllers/TrabajadorController.php`: lint OK.
- `app/views/trabajadores/nomina_preview.php`: lint OK.
- `app/views/trabajadores/index.php`: lint OK.
- `config/routes.php`: lint OK.
- `tools/saas/preflight_personal_pagos_caja.php`: lint OK.
- `tools/saas/health_check_fase_1a.php`: lint OK.
- Preflight pagos laborales con Caja: `OK: 47`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Ruta local sin sesion: `GET /trabajadores/nomina/preview` responde `303` a
  `/login`, sin 404.
- Carga de rutas: `ROUTES_LOAD_OK`.
- Prueba CLI del modelo:
  `PAYROLL_PREVIEW_HOTEL=4`,
  `PAYROLL_PREVIEW_TRABAJADORES=1`,
  `PAYROLL_PREVIEW_BLOQUEOS=0`,
  `PAYROLL_PREVIEW_NETO=100.00`.

## Decision de cierre

5E-G-A queda cerrado como preview operativo read-only de pre-nomina por periodo.

La pantalla es una herramienta de revision. No representa nomina oficial, no paga,
no crea recibos, no dispersa fondos y no modifica Caja.

## Limites despues del cierre

No avanzar desde este cierre a:

- pagos laborales masivos;
- nomina automatica;
- periodos oficiales persistidos;
- recibos oficiales;
- timbrado;
- dispersion bancaria;
- abonos o liquidaciones automaticas;
- nuevas categorias de Caja;
- cambios de permisos/auth;
- PWA/offline, IndexedDB, cache names o `/api/sync`.

## Siguiente paso seguro

El siguiente paso seguro es abrir un contrato independiente antes de cualquier nueva
superficie operativa.

Opciones posibles:

- contrato de recibo laboral informativo read-only;
- contrato de export CSV del preview de pre-nomina;
- regresar a otro modulo no financiero.

Cualquier implementacion posterior debe pedir autorizacion explicita para tocar los
archivos correspondientes.
