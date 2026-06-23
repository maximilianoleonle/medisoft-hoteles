# Fase 5E-U-A - Suite QA nomina administrativa

Estado formal:
`IMPLEMENTACION_5E_U_A_SUITE_QA_NOMINA_ADMINISTRATIVA_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-23.

## Objetivo

Agregar una suite CLI/local/read-only para ejecutar los controles tecnicos
principales del bloque administrativo de Personal/Nomina antes de continuar con
nuevas fases.

## Alcance implementado

- Herramienta nueva:
  - `tools/saas/preflight_nomina_administrativa_suite.php`

La suite ejecuta:

- `preflight_personal_pagos_caja.php` como bloqueo tecnico actual;
- `preflight_frontera_nomina_oficial.php` como bloqueo de frontera oficial;
- `preflight_personal_ledger.php` como control historico no bloqueante;
- validacion estatica de `health_check_fase_1a.php` para confirmar integracion
  5E-T-B y bloqueo documentado de `/api/sync`.

## Warning esperado

`preflight_personal_ledger.php` es anterior a las fases 5E autorizadas y falla
por rutas que ahora existen de manera controlada. Por eso la suite lo conserva
como lectura historica, pero no lo usa como bloqueo del bloque 5E actual.

## QA tecnica local

Comandos ejecutados:

```bash
docker exec -e APP_ENV=local medisoft_hoteles_app php -l /var/www/html/tools/saas/preflight_nomina_administrativa_suite.php
docker exec -e APP_ENV=local medisoft_hoteles_app php /var/www/html/tools/saas/preflight_nomina_administrativa_suite.php
```

Resultado:

- `php -l`: OK.
- Suite 5E-U-A:
  - `OK: 3`;
  - `WARNING: 1`;
  - `ERROR: 0`;
  - `PASS_WITH_WARNINGS_ALLOWED`.

Resultados internos:

- `preflight_personal_pagos_caja.php`: `OK=78`, `WARNING=0`, `ERROR=0`.
- `preflight_frontera_nomina_oficial.php`: `OK=10`, `WARNING=0`,
  `ERROR=0`.
- `preflight_personal_ledger.php`: no bloqueante, `OK=43`, `WARNING=1`,
  `ERROR=1`, por rutas historicamente fuera de su contrato original.
- Health general conserva integracion 5E-T-B y bloqueo `/api/sync`.

## Limites

- No se toco produccion.
- No se agregaron rutas.
- No se agregaron modelos, controladores, servicios ni vistas.
- No se agregaron migraciones.
- No se tocaron permisos, auth, storage, PWA/offline, IndexedDB, cache names ni
  `/api/sync`.
- No se escribieron pagos, reversiones, movimientos Caja ni snapshots.
- No se genero nomina oficial.

## Uso futuro

Antes de abrir una nueva fase de Personal/Nomina administrativa, ejecutar:

```bash
docker exec -e APP_ENV=local medisoft_hoteles_app php /var/www/html/tools/saas/preflight_nomina_administrativa_suite.php
```

Si la suite muestra `ERROR: 0`, se puede continuar con QA tecnica local. Si
aparecen errores bloqueantes, detener la fase y revisar el preflight indicado.
