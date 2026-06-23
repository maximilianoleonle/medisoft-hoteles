# Fase 5E-T-A - Preflight frontera de nomina oficial

Estado formal:
`IMPLEMENTACION_5E_T_A_PREFLIGHT_FRONTERA_NOMINA_OFICIAL_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-22.

## Objetivo

Agregar un checker CLI/read-only que confirme que el bloque actual de
Personal/Nomina sigue siendo administrativo y no expone nomina oficial, CFDI
laboral, timbrado, dispersion bancaria ni pago masivo.

## Alcance implementado

- Herramienta nueva:
  - `tools/saas/preflight_frontera_nomina_oficial.php`

La herramienta valida:

- rutas activas en `config/routes.php`;
- clases/metodos de la superficie Personal/Nomina;
- migraciones SQL, si estan montadas;
- objetos DB locales relacionados con nomina oficial, si hay conexion;
- bloqueo de `/api/sync` con `sync_temporarily_disabled` y HTTP 423.

## Superficie revisada

La validacion de codigo se limita a Personal/Nomina:

- `TrabajadorController.php`;
- `Trabajador.php`;
- servicios `Trabajador*` o `*Nomina*`.

Esto evita falsos positivos con la facturacion normal del hotel, por ejemplo
catalogos CFDI usados por facturacion de huespedes.

## Tokens prohibidos

El preflight falla si detecta superficie activa de nomina laboral con:

- `cfdi`;
- `timbrado`;
- `dispersion`;
- `pago masivo`;
- `nomina oficial`;
- `recibo fiscal`;
- `uuid fiscal`.

## QA tecnica local

Comandos ejecutados:

```bash
docker exec -e APP_ENV=local medisoft_hoteles_app php -l /var/www/html/tools/saas/preflight_frontera_nomina_oficial.php
docker exec -e APP_ENV=local medisoft_hoteles_app php /var/www/html/tools/saas/preflight_frontera_nomina_oficial.php
```

Resultado:

- `php -l`: OK.
- Preflight 5E-T-A:
  - `OK: 10`;
  - `WARNING: 0`;
  - `ERROR: 0`;
  - `PASS_WITH_WARNINGS_ALLOWED`.

## Resultado validado

- No hay rutas activas de nomina oficial, CFDI laboral, timbrado, dispersion ni
  pago masivo.
- No hay clases ni metodos activos de Personal/Nomina con nombres oficiales o
  fiscales.
- La DB local no tiene objetos de nomina oficial, CFDI laboral, timbrado,
  dispersion ni pago masivo.
- `/api/sync` conserva bloqueo `sync_temporarily_disabled` con HTTP 423.

## Limites

- No se toco produccion.
- No se agregaron rutas.
- No se agregaron modelos, controladores ni vistas.
- No se agregaron migraciones.
- No se tocaron permisos, auth, storage, PWA/offline, IndexedDB, cache names ni
  `/api/sync`.
- No se escribieron pagos, reversiones, movimientos Caja ni snapshots.

## Uso futuro

Antes de abrir cualquier fase de nomina oficial, ejecutar:

```bash
docker exec -e APP_ENV=local medisoft_hoteles_app php /var/www/html/tools/saas/preflight_frontera_nomina_oficial.php
```

Si falla, no avanzar a nomina oficial hasta revisar los hallazgos.
