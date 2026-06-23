# Fase 5E-U-B - Health baseline Compras/CxP

Estado formal:
`IMPLEMENTACION_5E_U_B_HEALTH_BASELINE_COMPRAS_CXP_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-23.

## Objetivo

Eliminar los `ERROR: 3` historicos del health general cuando las vistas de
Compras y CxP ya cumplen el contrato actual, pero el checker seguia buscando
marcas literales anteriores al redisenio visual.

## Alcance implementado

- Archivo modificado:
  - `tools/saas/health_check_fase_1a.php`

El ajuste actualiza validaciones estaticas para aceptar las marcas vigentes de:

- listado de Compras con enlace de detalle mediante variable `$compraUrl`;
- recepcion minima de Compras con clase actual `cp-btn-receive` o
  `data-receive-form="1"`;
- listado de CxP con enlace de detalle mediante variable `$cuentaUrl`;
- simulador de Caja CxP con etiqueta actual `Solo consulta`.

## Resultado validado

Los tres errores anteriores pasan a OK:

- `Vistas de Compras Fase 2Y permiten reporte read-only, detalle, borrador y recepcion minima con CSRF.`
- `Vistas CxP Fase 3D/3D-D-A incluyen detalle con pago/reversion Caja controlados y CSRF.`
- `Vista CxP Fase 3D-A simulador Caja es GET/read-only y no expone acciones de egreso.`

## QA tecnica local

Comandos ejecutados:

```bash
docker exec -e APP_ENV=local medisoft_hoteles_app php -l /var/www/html/tools/saas/health_check_fase_1a.php
docker exec -e APP_ENV=local medisoft_hoteles_app php /var/www/html/tools/saas/health_check_fase_1a.php
docker exec -e APP_ENV=local medisoft_hoteles_app php /var/www/html/tools/saas/preflight_nomina_administrativa_suite.php
```

Resultado:

- `php -l`: OK.
- Health general:
  - `OK: 328`;
  - `WARNING: 28`;
  - `ERROR: 0`;
  - `PASS_WITH_WARNINGS_ALLOWED`.
- Suite 5E-U-A:
  - `OK: 3`;
  - `WARNING: 1`;
  - `ERROR: 0`;
  - `PASS_WITH_WARNINGS_ALLOWED`.

## Limites

- No se toco produccion.
- No se modificaron vistas de Compras ni CxP.
- No se agregaron rutas.
- No se agregaron modelos, controladores ni servicios.
- No se agregaron migraciones.
- No se tocaron permisos, auth, storage, PWA/offline, IndexedDB, cache names ni
  `/api/sync`.
- No se escribieron pagos, reversiones, movimientos Caja, compras, CxP ni
  snapshots.
