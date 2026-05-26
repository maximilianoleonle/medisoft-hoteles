# Fase Inventario 1-E-B.1 - Actualizacion de herramientas de verificacion

## Objetivo

Actualizar las herramientas locales de verificacion SaaS para reconocer como valido el estado posterior a Inventario 1-E-B, donde `movimientos_inventario` ya tiene `hotel_id`.

## Herramientas actualizadas

- `src/tools/saas/verificar_estado.php`
- `src/tools/saas/preflight_hotel_id.php`

## Nuevo estado aceptado

Las herramientas ahora aceptan que `movimientos_inventario` tenga `hotel_id` cuando se cumple lo siguiente:

- La migracion `20260526_007_add_hotel_id_movimientos_inventario.sql` esta registrada como `ejecutada`.
- La columna `hotel_id` existe en `movimientos_inventario`.
- No existen registros con `hotel_id NULL`.
- Los 936 registros actuales apuntan a Los Cedros.
- Existe el indice `idx_movimientos_inventario_hotel_id`.
- Existe la foreign key `fk_movimientos_inventario_hotel -> hoteles(id)`.

## Riesgos que siguen vigentes

- `InventarioService` no esta integrado con `hotel_id` en movimientos.
- Check-in/check-out no deben modificarse todavia.
- Reservaciones no tiene `hotel_id`.
- Caja sigue fuera de alcance.
- PWA/offline y sincronizacion siguen fuera de alcance.
- Nuevos movimientos podrian quedar con `hotel_id NULL` hasta integrar codigo funcional.

## Tablas pendientes

Siguen pendientes o fuera de alcance:

- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- `reservaciones`
- `cajas`
- `movimientos_caja`
- `sync_queue`
- `push_subscriptions`

## Como ejecutar

Desde el host:

```bash
docker compose exec app php tools/saas/verificar_estado.php
docker compose exec app php tools/saas/preflight_hotel_id.php
```

## Resultado esperado

Ambas herramientas deben terminar con:

```text
Resultado general: PASS
```

## Que no modifica esta fase

Esta fase no ejecuta migraciones, no hace `ALTER TABLE`, no inserta, no actualiza y no borra datos. Tampoco modifica codigo funcional de Inventario, `InventarioService`, Reservaciones, check-in/check-out, Caja ni PWA/offline.
