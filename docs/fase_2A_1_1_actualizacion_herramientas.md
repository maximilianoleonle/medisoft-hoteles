# Fase 2A.1.1 - Actualizacion de herramientas post hotel_id Habitaciones

## Objetivo

Actualizar las herramientas locales de verificacion para que reconozcan como valido el estado posterior a Fase 2A.1.

Despues de ejecutar la migracion de Habitaciones, `hotel_id` ya debe existir en:

- `tipos_habitacion`
- `habitaciones`
- `habitacion_imagenes`
- `mantenimientos_habitaciones`

## Herramientas actualizadas

- `src/tools/saas/verificar_estado.php`
- `src/tools/saas/preflight_hotel_id.php`

## Nuevo estado aceptado

Las herramientas ahora aceptan `hotel_id` en:

- `hotel_configuracion`
- `hotel_usuarios`
- `logs_auditoria`
- `tipos_habitacion`
- `habitaciones`
- `habitacion_imagenes`
- `mantenimientos_habitaciones`

Tambien verifican que las cuatro tablas de Habitaciones:

- Tengan columna `hotel_id`.
- No tengan registros existentes con `hotel_id IS NULL`.
- Tengan todos sus registros existentes asignados al hotel Los Cedros.
- Tengan los indices `idx_*_hotel_id` esperados.
- Tengan las foreign keys `fk_*_hotel` hacia `hoteles(id)`.
- Tengan registrada la migracion `20260526_003_add_hotel_id_habitaciones.sql`.

## Riesgo que sigue vigente

Las herramientas siguen considerando riesgo o pendiente que no se haya migrado:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_abonos`
- `cajas`
- `cortes_caja`
- `movimientos_caja`
- `sync_queue`
- `push_subscriptions`

Estas tablas no deben tocarse hasta una fase posterior aprobada.

## Como ejecutar

Desde la raiz del proyecto:

```bash
docker compose exec app php tools/saas/verificar_estado.php
docker compose exec app php tools/saas/preflight_hotel_id.php
```

Validacion de sintaxis:

```bash
docker compose exec app php -l tools/saas/verificar_estado.php
docker compose exec app php -l tools/saas/preflight_hotel_id.php
```

## Resultado esperado

Ambas herramientas deben terminar con:

```text
Resultado general: PASS
```

No debe haber errores criticos.

## Que no modifica esta fase

Esta fase no:

- Ejecuta migraciones.
- Hace `ALTER TABLE`.
- Inserta datos.
- Actualiza datos.
- Borra datos.
- Cambia codigo funcional.
- Integra `TenantContext`.
- Cambia login o sesiones.
- Cambia dashboard.
- Cambia PWA/offline.
- Toca caja o reservaciones.

Las herramientas siguen siendo de solo lectura.

## Siguiente paso recomendado

Preparar Fase 2A.2: integracion de codigo solo en el modulo Habitaciones para escribir y filtrar por `hotel_id`, manteniendo reservaciones, caja y PWA/offline fuera del alcance.
