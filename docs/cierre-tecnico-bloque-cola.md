# Cierre tecnico del bloque autorizado

## Estado

Estado objetivo: `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`.

Este cierre no equivale a validacion manual completa. Falta QA en navegador por parte del usuario antes de autorizar nuevas fases operativas.

## Fases cerradas

| Fase | Alcance | Commit | Estado |
| --- | --- | --- | --- |
| 2X | Detalle read-only de compra recibida | `d1f1431` | Cerrada |
| 2Y | Reporte read-only de compras recibidas | `32abb7b` | Cerrada |
| 2Z | Endurecimiento de recepcion de compras | `052fd7a` | Cerrada |
| 3A | Ficha read-only de proveedor | `3d8f997` | Cerrada |
| 3B draft | Borrador tecnico CxP base | `673f47f` | Cerrada como draft |
| 3B aplicada | CxP base read-only | `1fa1653` | Cerrada |
| Post 3B | Auditoria/cierre documental | `2535dd1`, `ca2bda4` | Cerrada |

## Confirmaciones tecnicas

- Rutas de compras/proveedores/CxP revisadas.
- Controladores y modelos revisados para guards y `hotel_id`.
- Vistas revisadas para estados vacios y ausencia de acciones financieras no autorizadas.
- Sidebar revisado: Proveedores, Compras y CxP bajo gate visual/funcional de Inventario.
- CxP conserva solo rutas GET.
- CxP no tiene POST operativo.
- CxP no expone botones de pago.
- CxP no toca Caja.
- Compras no genera CxP automaticamente.
- Recepcion de compras mantiene proteccion contra doble recepcion.
- Inventario moderno usa `inventario_productos` y `movimientos_inventario`.
- Tablas legacy quedan congeladas y documentadas.
- `/api/sync` sigue fuera de alcance y bloqueado.

## Verificaciones automaticas registradas

- `php -l` sobre archivos PHP del bloque: sin errores.
- `src/tools/saas/health_check_fase_1a.php`: PASS con warnings permitidos.
- `src/tools/saas/preflight_compras_minimas.php`: PASS con warnings permitidos.
- `src/tools/saas/preflight_recepcion_compras.php`: PASS con warnings permitidos.
- SQL read-only:
  - CxP registrada en `migrations`;
  - `cuentas_por_pagar` vacia;
  - `cuentas_por_pagar_movimientos` vacia;
  - sin movimientos de Caja vinculados a CxP;
  - sin detalles recibidos sin movimiento de inventario.
- HTTP sin sesion:
  - `/compras/reportes/recibidas` redirige a login;
  - `/proveedores/1` redirige a login;
  - `/cuentas-por-pagar` redirige a login.

## Warnings conocidos

- El contenedor `app` no monta `docs/technical` ni `migrations/` completos, por eso algunos checkers emiten warnings de visibilidad documental.
- Existe migracion historica fuera de `migrations/`: `database/migrations/2026_05_21_create_operaciones_sync.sql`.
- Maximiliano tiene excepciones de modulos respecto a su plan: `configuracion`, `usuarios`.
- Tablas legacy/duplicadas siguen presentes y no deben borrarse:
  - `productos` vs `inventario_productos`;
  - `inventario_movimientos` vs `movimientos_inventario`;
  - `inventario_habitacion_config` vs `inventario_config_habitacion`;
  - `push_subscriptions` vs `pwa_push_subscriptions`;
  - `huespedes_vehiculos` vs `huesped_vehiculos`.
- `src/app/views/reservaciones/ver.php` queda como cambio no relacionado pendiente.

## QA manual pendiente

- QA critica, funcional, visual y regresion vive en `docs/qa-pendiente-cola.md`.
- No avanzar a Fase 3C, pagos, Caja ni CxP operativa hasta completar QA manual y recibir nuevo mensaje real.

## Estado Git esperado

- HEAD funcional/documental del bloque: `ca2bda4`.
- Pendiente no relacionado: `src/app/views/reservaciones/ver.php`.
- Ese archivo debe resolverse en commit separado o descartarse solo con autorizacion explicita.
