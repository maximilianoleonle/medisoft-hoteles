# Cola autonoma - Medisoft Hoteles

## Ultimo mensaje real usado

Cierre seguro de Fase 3B aplicada: cuentas por pagar base read-only, con metodologia por contrato de fase, semaforo de riesgo, Definition of Done, QA acumulada, rollback documentado y commit estable.

## Estado vigente

- Fase actual: Fase 3B aplicada.
- Riesgo: naranja.
- Estado: en cierre tecnico, validacion y commit selectivo.
- Base local principal: `medisoft_hoteles_import`.
- Backup previo a DB:
  - `src/storage/backups/phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql`
  - SHA256: `24663D206AE15B86B001708D8BC2665541548443A0EA363548CAFC3FDF3A4D2C`
  - tamano: `3017728`

## Contrato activo

La Fase 3B aplicada solo permite una fundacion read-only de cuentas por pagar:

- tablas nuevas vacias de CxP;
- modelo/controlador/vistas read-only;
- rutas GET de listado y detalle;
- navegacion basica;
- health/preflight compatibles;
- documentacion de QA, decisiones, rollback y fuentes de verdad.

No permite pagos, Caja, generacion automatica desde compras, saldos operativos, CxC, nomina, permisos profundos ni cambios en `/api/sync`.

## Fases completadas con commit

- `d1f1431` - `feat: add read-only received purchase detail`
- `32abb7b` - `feat: add read-only received purchases reports`
- `052fd7a` - `fix: harden minimal purchase receiving flow`
- `3d8f997` - `feat: expand supplier profile and purchase history`
- `673f47f` - `docs: draft accounts payable foundation`

## Cambios pendientes clasificados

### Relacionados con Fase 3B

- `migrations/20260615_003_fase_3b_cxp_base.sql`
- `src/app/controllers/CuentaPorPagarController.php`
- `src/app/models/CuentaPorPagar.php`
- `src/app/views/cuentas_por_pagar/index.php`
- `src/app/views/cuentas_por_pagar/ver.php`
- `src/app/views/layout/sidebar.php`
- `src/config/routes.php`
- `src/tools/saas/health_check_fase_1a.php`
- `src/tools/saas/preflight_compras_minimas.php`
- `src/tools/saas/preflight_recepcion_compras.php`
- `docs/technical/inventory_reconciliation.md`
- `docs/technical/purchasing_inventory_contract.md`
- `docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql`
- documentos de metodologia en `docs/*-cola.md` y `docs/fuentes-de-verdad.md`

### No relacionado con Fase 3B

- `src/app/views/reservaciones/ver.php`: ajuste visual del modal de check-in tardio. No debe incluirse en el commit de Fase 3B.

### Dudosos

- Ninguno identificado.

## Pruebas obligatorias para cierre

- `php -l` sobre controlador, modelo, vistas, rutas, sidebar y checkers modificados.
- `health_check_fase_1a.php`.
- `preflight_compras_minimas.php`.
- `preflight_recepcion_compras.php`.
- SQL read-only para validar tablas, migracion registrada, conteos CxP en cero y no generacion de pagos.
- HTTP sin sesion `GET /cuentas-por-pagar`.
- `git diff --check`.

## QA manual pendiente

Ver `docs/qa-pendiente-cola.md`.

## Siguiente accion

Cerrar Fase 3B aplicada con commit selectivo si las verificaciones pasan. Despues del commit, entrar en modo de revision de usuario y no avanzar a Fase 3C sin nuevo mensaje real.
