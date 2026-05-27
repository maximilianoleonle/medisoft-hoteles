# Reservaciones 1-F-B.1 - Actualizacion de herramientas SaaS para Caja base

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Actualizar las herramientas SaaS locales para que el estado post Reservaciones 1-F-B sea considerado valido.

Esta fase no ejecuta migraciones, no modifica base de datos y no toca codigo funcional de Caja, Reservaciones, Reportes/Dashboard, PWA/offline, Sync ni APIs.

## Herramientas actualizadas

- `src/tools/saas/verificar_estado.php`
- `src/tools/saas/preflight_hotel_id.php`

## Nuevo estado aceptado

Las herramientas ahora reconocen como migradas:

- `cajas`
- `cortes_caja`
- `movimientos_caja`

El estado valido esperado incluye:

- columna `hotel_id` existente en las tres tablas;
- 0 registros con `hotel_id NULL`;
- registros actuales asignados a Los Cedros;
- indices simples por `hotel_id`;
- migracion `20260526_009_add_hotel_id_caja_base.sql` registrada como ejecutada.

## Tablas que siguen pendientes

- PWA/offline.
- Sync.
- APIs globales.
- Dashboard/reportes.
- `huespedes`, hasta decidir modelo global o por hotel.
- `denominaciones_efectivo`.
- `categorias_movimientos`.
- `sync_queue`.
- `push_subscriptions`.

## Riesgos que siguen activos

- Codigo funcional de Caja todavia no escribe ni filtra por `hotel_id`.
- Reportes y Dashboard financieros siguen globales.
- PWA/offline, Sync y APIs pueden tener caminos paralelos.
- `hotel_id` sigue `INT NULL` por diseno conservador.
- No se crearon foreign keys estrictas.
- Caja es modulo financiero y requiere fase funcional separada.

## Como ejecutar las herramientas

```powershell
docker compose exec app php tools/saas/verificar_estado.php
docker compose exec app php tools/saas/preflight_hotel_id.php
```

Validacion de sintaxis:

```powershell
docker compose exec app php -l tools/saas/verificar_estado.php
docker compose exec app php -l tools/saas/preflight_hotel_id.php
```

## Resultado esperado

- `verificar_estado.php`: PASS.
- `preflight_hotel_id.php`: PASS.
- 0 errores criticos.

## Siguiente fase recomendada

Cerrar esta actualizacion con commit y mantener fuera de alcance el codigo funcional de Caja hasta una fase aprobada especifica.
