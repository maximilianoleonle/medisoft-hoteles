# Fase 1.3 - Preflight de migracion hotel_id

## Objetivo

La Fase 1.3 agrega una herramienta interna, local y de solo lectura para analizar las tablas que podrian necesitar `hotel_id` en fases futuras.

Esta fase no agrega `hotel_id`, no modifica tablas operativas y no cambia la logica funcional actual. Su objetivo es preparar el mapa tecnico antes de una migracion progresiva multi-hotel.

## Archivo creado

- `src/tools/saas/preflight_hotel_id.php`

El archivo esta fuera de `public_html`, por lo que no queda expuesto como ruta publica.

## Como ejecutar

Desde la raiz del proyecto:

```bash
docker compose exec app php tools/saas/preflight_hotel_id.php
```

La herramienta solo corre por CLI y se bloquea si `APP_ENV` no es `local`.

## Que revisa

La herramienta analiza, si existen, tablas de estos grupos:

- SaaS/base:
  - `hoteles`
  - `hotel_configuracion`
  - `hotel_usuarios`
  - `logs_auditoria`
  - `migrations`
- Habitaciones:
  - `habitaciones`
  - `tipos_habitacion`
  - `habitacion_imagenes`
  - `mantenimientos_habitaciones`
- Huespedes/reservaciones:
  - `huespedes`
  - `reservaciones`
  - `reservacion_habitaciones`
  - `reservacion_pagos`
  - `reservacion_abonos`
  - `reservacion_notas`
  - `solicitudes_factura`
- Caja:
  - `cajas`
  - `cortes_caja`
  - `movimientos_caja`
  - `denominaciones_efectivo`
  - `categorias_movimientos`
- Inventario:
  - `inventario_productos`
  - `inventario_categorias`
  - `inventario_config_habitacion`
  - `inventario_habitacion_config`
  - `inventario_movimientos`
  - `movimientos_inventario`
  - `productos`
  - `categorias_producto`
  - `alertas_inventario`
- Tarifas/configuracion:
  - `tarifas_temporada`
  - `incrementos_tarifas`
  - `configuracion`
- Operacion:
  - `control_llaves`
  - `historial_llaves`
  - `control_remotos`
  - `historial_remotos`
- PWA/sync/acceso:
  - `sync_queue`
  - `push_subscriptions`
  - `remember_tokens`
  - `logs_acceso`

Por tabla reporta:

- Si existe.
- Conteo de registros.
- Llave primaria.
- Llaves foraneas.
- Indices unicos.
- Si ya tiene `hotel_id`.
- Si necesitara `hotel_id` en Fase 2 o posterior.
- Riesgo estimado: bajo, medio o alto.
- Motivo del riesgo.
- Orden recomendado de migracion.
- Posibles indices unicos que deberan revisarse como compuestos con `hotel_id`.

## Que no modifica

La herramienta no:

- Ejecuta migraciones.
- Ejecuta `ALTER TABLE`.
- Inserta datos.
- Actualiza datos.
- Borra datos.
- Modifica tablas operativas.
- Cambia login o sesiones.
- Cambia dashboard.
- Cambia consultas existentes.
- Integra `TenantContext`.
- Agrega `hotel_id` a tablas existentes.

Todas sus operaciones son consultas `SELECT` contra la base y `information_schema`.

## Como interpretar resultados

- `[OK]`: la verificacion paso correctamente.
- `[INFO]`: dato informativo para planear la migracion.
- `[WARN]`: algo requiere revision, pero no necesariamente bloquea. Por ejemplo, una tabla esperada no existe con ese nombre.
- `[ERROR]`: problema critico. Por ejemplo, una tabla operativa ya contiene `hotel_id` antes de la fase autorizada.

Al final muestra:

- Total de tablas revisadas.
- Total de tablas existentes.
- Total de tablas faltantes.
- Tablas con `hotel_id`.
- Tablas sin `hotel_id` que lo necesitaran.
- Top 5 riesgos.
- Recomendacion de siguiente fase.

## Riesgos detectados esperados

La herramienta marca como alto riesgo:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_abonos`
- `cajas`
- `cortes_caja`
- `movimientos_caja`
- `sync_queue`
- `push_subscriptions`

Estos grupos deben migrarse despues de tener probado el aislamiento en tablas menos sensibles.

Tambien marca como primeras candidatas:

- `tipos_habitacion`
- `habitaciones`
- `habitacion_imagenes`
- `mantenimientos_habitaciones`

Estas tablas son el mejor punto de entrada para una Fase 2A, siempre con backup fresco, migracion reversible y pruebas de que no se mezclan datos entre hoteles.

## Siguiente fase recomendada

La siguiente fase recomendada es una Fase 2A limitada a habitaciones:

1. Backup fresco.
2. Migracion reversible para `tipos_habitacion`, `habitaciones`, `habitacion_imagenes` y `mantenimientos_habitaciones`.
3. Poblar `hotel_id` con el hotel inicial Los Cedros.
4. Agregar indices compuestos necesarios.
5. Probar listados, busquedas y reportes relacionados sin tocar reservaciones ni caja.

Reservaciones, caja, PWA/offline y sync deben quedar fuera hasta una aprobacion posterior explicita.

## Rollback

No hay rollback de base de datos porque la herramienta no modifica datos ni estructura.

Si se necesita revertir antes de commit:

```bash
Remove-Item -LiteralPath "src\tools\saas\preflight_hotel_id.php"
Remove-Item -LiteralPath "docs\fase_1_3_preflight_hotel_id.md"
```

Si ya fue versionada:

```bash
git rm src/tools/saas/preflight_hotel_id.php docs/fase_1_3_preflight_hotel_id.md
git commit -m "revert: remove hotel_id preflight tool"
```
