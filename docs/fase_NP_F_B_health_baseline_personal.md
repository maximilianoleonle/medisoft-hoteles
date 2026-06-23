# Fase NP-F-B - Health baseline Personal

Estado formal:
`IMPLEMENTACION_NP_F_B_HEALTH_BASELINE_PERSONAL_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-23.

## Objetivo

Eliminar el warning historico de Personal NP-F-A/5E-D-A cuando las vistas
actuales ya cumplen el contrato operativo, pero el health seguia buscando una
marca literal anterior al redisenio del listado.

## Alcance implementado

- Archivo modificado:
  - `tools/saas/health_check_fase_1a.php`

El ajuste actualiza la validacion estatica para aceptar las marcas vigentes del
listado de trabajadores:

- enlace de detalle historico con `url('trabajadores/' . (int...)`;
- enlace actual mediante `$tUrl = url('trabajadores/' . $tId)` usado en
  `href="<?= $tUrl ?>"`.

Se mantienen las guardas existentes para:

- filtros GET en el listado;
- reporte read-only sin POST ni CSRF;
- formularios POST con CSRF en CRUD/ledger;
- panel de pago laboral con Caja con `pago_token`;
- ausencia de rutas internas de archivos y tablas fuera de alcance.

## Resultado validado

El warning anterior pasa a OK:

- `Vistas Personal NP-F-A/5E-D-A muestran CRUD, reporte, ledger manual y panel de pago laboral con CSRF/token.`

## QA tecnica local

Comandos ejecutados:

```bash
docker exec -e APP_ENV=local medisoft_hoteles_app php -l /var/www/html/tools/saas/health_check_fase_1a.php
docker exec -e APP_ENV=local medisoft_hoteles_app php /var/www/html/tools/saas/health_check_fase_1a.php
```

Resultado:

- `php -l`: OK.
- Health general:
  - `OK: 329`;
  - `WARNING: 27`;
  - `ERROR: 0`;
  - `PASS_WITH_WARNINGS_ALLOWED`.
- Validacion filtrada:

- Personal NP-F-A/5E-D-A:
  - listado con enlace GET a detalle: OK;
  - reporte read-only: OK;
  - ledger manual: OK;
  - panel de pago laboral con token: OK.

## Limites

- No se toco produccion.
- No se modificaron vistas de Personal.
- No se agregaron rutas.
- No se agregaron modelos, controladores ni servicios.
- No se agregaron migraciones.
- No se tocaron permisos, auth, storage, PWA/offline, IndexedDB, cache names ni
  `/api/sync`.
- No se escribieron pagos, reversiones, movimientos Caja, nomina oficial ni
  snapshots.
