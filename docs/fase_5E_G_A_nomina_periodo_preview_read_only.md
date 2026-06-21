# Fase 5E-G-A - Preview read-only de nomina por periodo

## Estado

`PREVIEW_5E_G_A_NOMINA_PERIODO_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`

## Objetivo

Implementar una pantalla GET/read-only para revisar pre-nomina operativa por periodo
desde Personal, sin generar nomina oficial, sin pagos masivos, sin recibos oficiales y
sin movimientos de Caja.

## Alcance implementado

- Ruta GET:
  `GET /trabajadores/nomina/preview`.
- Controlador:
  `TrabajadorController::nominaPreviewAction`.
- Modelo:
  `Trabajador::nominaPreviewPorHotel`.
- Vista:
  `src/app/views/trabajadores/nomina_preview.php`.
- Enlace GET desde listado de Personal:
  `src/app/views/trabajadores/index.php`.
- Validaciones en:
  `src/tools/saas/preflight_personal_pagos_caja.php` y
  `src/tools/saas/health_check_fase_1a.php`.

## Contrato de lectura

La pantalla usa filtros GET:

- `fecha_inicio`;
- `fecha_fin`;
- `trabajador_id`;
- `buscar`;
- `rol_laboral`;
- `estado`;
- `solo_con_saldo`;
- `incluir_pagos_caja`.

Si el periodo falta o es invalido, la pantalla bloquea el preview con mensaje
informativo y no intenta calcular trabajadores.

## Calculos implementados

Por trabajador:

```text
bruto_periodo =
  conceptos_a_favor_del_periodo
  - conceptos_en_contra_del_periodo

deducciones_informativas =
  anticipos_pendientes
  + prestamos_vigentes

neto_sugerido =
  bruto_periodo
  - deducciones_informativas
  - pagos_caja_aplicados
```

Los pagos Caja aplicados se toman solo de `trabajador_pagos_caja.estado = pagado`.
Los pagos revertidos se muestran como lectura separada y no descuentan como pago
vigente.

## Estados visuales

Cada trabajador puede quedar como:

- `sin_movimientos`;
- `por_pagar`;
- `cubierto`;
- `bloqueado`.

Estos estados no se guardan en base de datos.

## Fuera de alcance

Esta fase no implementa:

- POST;
- CSRF;
- pago masivo;
- generacion de nomina oficial;
- recibos oficiales;
- timbrado;
- dispersion bancaria;
- liquidaciones automaticas;
- nuevas categorias de Caja;
- escrituras en `trabajador_pagos`, `trabajador_pagos_caja` o `movimientos_caja`;
- cambios de permisos/auth;
- PWA/offline, IndexedDB, cache names o `/api/sync`.

## Validaciones automaticas

- `php -l app/models/Trabajador.php`: OK.
- `php -l app/controllers/TrabajadorController.php`: OK.
- `php -l app/views/trabajadores/nomina_preview.php`: OK.
- `php -l app/views/trabajadores/index.php`: OK.
- `php -l config/routes.php`: OK.
- `php -l tools/saas/preflight_personal_pagos_caja.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- Preflight pagos laborales con Caja:
  `OK: 47`, `WARNING: 0`, `ERROR: 0`.
- Health general:
  `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Ruta local sin sesion:
  `GET /trabajadores/nomina/preview` responde `303` a `/login`, sin 404.
- Carga de rutas:
  `ROUTES_LOAD_OK`.
- Prueba CLI del modelo:
  `PAYROLL_PREVIEW_HOTEL=4`,
  `PAYROLL_PREVIEW_TRABAJADORES=1`,
  `PAYROLL_PREVIEW_BLOQUEOS=0`,
  `PAYROLL_PREVIEW_NETO=100.00`.

## QA manual pendiente

1. Abrir `/trabajadores/nomina/preview`.
2. Confirmar que sin fechas muestra bloqueo por periodo requerido.
3. Seleccionar periodo valido, por ejemplo junio 2026.
4. Confirmar que aparecen solo trabajadores del hotel actual.
5. Confirmar bruto, deducciones informativas, pagos Caja y neto sugerido.
6. Activar/desactivar `Pagos Caja` y confirmar que cambia solo el calculo.
7. Activar `Con saldo` y confirmar filtrado visual.
8. Confirmar que no hay botones de pago, POST ni movimientos de Caja.

## Siguiente paso seguro

Despues de QA manual exitosa, el siguiente paso seguro es `5E-G-F`, cierre documental
del preview read-only de nomina por periodo.

Cualquier paso hacia recibos, dispersion, pago masivo o nomina oficial requiere contrato
independiente y autorizacion explicita.
