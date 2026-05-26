# Fase Inventario 1-C.1 - Actualizacion de herramientas SaaS

## Objetivo

Actualizar las herramientas locales de verificacion para que el estado post-Inventario 1-C sea considerado valido.

Esta fase no modifica base de datos, no ejecuta migraciones, no hace `ALTER TABLE` y no toca codigo funcional de Inventario.

## Herramientas actualizadas

- `src/tools/saas/verificar_estado.php`
- `src/tools/saas/preflight_hotel_id.php`

## Nuevo estado aceptado

Las herramientas ahora aceptan `hotel_id` en las tablas base de Inventario migradas en Inventario 1-C:

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`

Tambien verifican que:

- La migracion `20260526_006_add_hotel_id_inventario_base.sql` este registrada como ejecutada.
- Las tres tablas tengan columna `hotel_id`.
- No existan registros actuales con `hotel_id NULL`.
- Todos los registros existentes apunten a Los Cedros.
- Existan los indices esperados.
- Existan las foreign keys esperadas hacia `hoteles(id)`.

## Tablas que siguen pendientes

Las herramientas deben seguir marcando como pendientes/no migradas las tablas fuera de alcance:

- `movimientos_inventario`
- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- `reservaciones`
- `cajas`
- `movimientos_caja`
- `sync_queue`
- `push_subscriptions`

## Riesgos que siguen vigentes

- Inventario funcional todavia no escribe ni filtra por `hotel_id`.
- `movimientos_inventario` sigue sin tenant.
- Check-in/Check-out sigue fuera de alcance.
- Reservaciones sigue sin `hotel_id`.
- Caja y PWA/offline siguen sin cambios.
- Los indices unicos globales de Inventario siguen pendientes.

## Como ejecutar

Desde el host:

```powershell
docker compose exec app php tools/saas/verificar_estado.php
docker compose exec app php tools/saas/preflight_hotel_id.php
```

## Resultado esperado

Ambas herramientas deben terminar con:

```text
Resultado general: PASS
```

No deben reportar errores criticos por `hotel_id` en:

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`

## Siguiente fase recomendada

Preparar una auditoria de codigo de Inventario base para identificar donde se deben agregar filtros e inserts con `hotel_id`, sin tocar todavia movimientos, Check-in/Check-out, Reservaciones, Caja ni PWA/offline.
