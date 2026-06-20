# Release produccion 2026-06-20

## Estado

Preparado para despliegue controlado, no para copia ciega.

Rama local:

- `feature/saas-multihotel`

Remote detectado:

- `origin https://github.com/maximilianoleonle/medisoft-hoteles.git`

## Alcance funcional incluido

- CxC operativa con cobro y reversion controlada con Caja.
- CxP con pago y reversion controlada con Caja.
- Conciliacion financiera read-only.
- Arqueo por metodo read-only.
- Tablero ejecutivo read-only.
- Perfil operativo de huesped read-only.
- Personal/trabajadores con ledger, simulador y pago laboral controlado con Caja.
- Cierres documentales de QA manual ya validados localmente.

## Validaciones locales previas

- `docker compose exec -T app php tools/saas/preflight_personal_pagos_caja.php`
  - `OK: 41`, `WARNING: 0`, `ERROR: 0`
- `docker compose exec -T app php tools/saas/preflight_cuentas_por_cobrar.php`
  - `OK: 45`, `WARNING: 6`, `ERROR: 0`
- `docker compose exec -T app php tools/saas/preflight_pagos_proveedores_caja.php`
  - `OK: 29`, `WARNING: 1`, `ERROR: 0`
- `docker compose exec -T app php tools/saas/preflight_conciliacion_financiera.php`
  - `OK: 96`, `WARNING: 0`, `ERROR: 0`
- `docker compose exec -T app php tools/saas/preflight_arqueo_metodos_pago.php`
  - `OK: 39`, `WARNING: 2`, `ERROR: 0`
- `docker compose exec -T app php tools/saas/health_check_fase_1a.php`
  - `OK: 313`, `WARNING: 25`, `ERROR: 0`
- `git diff --check`
  - Sin errores; solo advertencias CRLF del working tree.

## Migraciones nuevas a revisar en produccion

Antes de aplicar, consultar `migrations` en produccion y confirmar cuales ya existen.

Orden recomendado:

1. `migrations/20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql`
2. `migrations/20260619_001_huespedes_campos_configurables.sql`
3. `migrations/20260619_002_huespedes_hotel_id_documentos.sql`
4. `migrations/20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql`

No aplicar una migracion si la estructura ya existe pero la tabla `migrations` no esta
reconciliada. En ese caso, detener y comparar estructura antes de registrar nada.

## Punto sensible PWA

Hay cambios locales en:

- `src/public_html/js/pwa.js`

Antes de subir, confirmar explicitamente si entra al release. Este archivo afecta
comportamiento de actualizacion/cache del navegador. No tocar `service-worker.js`,
`offline-data.js`, `reservaciones-offline.js`, IndexedDB, cache names ni `/api/sync`
durante el despliegue.

`/api/sync` debe seguir respondiendo HTTP 423 con `sync_temporarily_disabled`.

## Secuencia recomendada de despliegue

1. Congelar cambios locales.
2. Crear backup completo de produccion:
   - archivos actuales;
   - base de datos completa;
   - guardar ruta, tamano y hash si es posible.
3. Crear commit/tag de release o paquete ZIP del corte exacto.
4. Subir codigo a produccion.
5. Aplicar migraciones nuevas una por una, revisando resultado despues de cada una.
6. Limpiar cache PHP/opcache si el hosting lo usa.
7. Probar login.
8. Probar rutas de smoke test.
9. Confirmar `/api/sync` bloqueado.
10. Dejar monitoreo manual activo durante las primeras pruebas.

## Smoke tests minimos en produccion

Usar un hotel/controlado y montos pequenos.

- Login y dashboard.
- Habitaciones: abrir ficha y confirmar que carga.
- Huespedes: abrir ficha read-only.
- CxC:
  - abrir listado;
  - abrir una cuenta;
  - si se prueba cobro, usar monto minimo y confirmar Caja/movimiento.
- CxP:
  - abrir listado;
  - abrir detalle;
  - no revertir pagos reales sin plan.
- Personal:
  - abrir trabajador;
  - abrir simulador pago Caja;
  - si se prueba pago, usar trabajador y monto controlado.
- Caja:
  - abrir corte actual;
  - abrir arqueo por metodo read-only.
- Reportes:
  - abrir tablero ejecutivo read-only.
- Operacion:
  - abrir conciliacion financiera read-only.
- Confirmar que no se generan errores 500 en logs.

## Rollback

Si falla antes de migraciones:

- Restaurar archivos anteriores o volver al commit/tag anterior.

Si falla despues de migraciones:

- No ejecutar `DROP`, `DELETE` ni ajustes manuales directos sin plan.
- Restaurar backup completo en base separada para comparar.
- Si hay pagos/cobros de prueba reales, usar reversiones controladas disponibles
  cuando apliquen; para pago laboral no existe reversion automatica todavia.

## Decision pendiente

Antes de subir falta decidir el metodo:

- push a GitHub y pull en servidor;
- ZIP/FTP a hosting;
- panel de Hostinger;
- otro flujo manual.
