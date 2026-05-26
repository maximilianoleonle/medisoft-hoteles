# Fase Reservaciones 1-C - Actualizacion de herramientas SaaS

Fecha: 2026-05-26  
Rama: `feature/saas-multihotel`

## Objetivo

Actualizar las herramientas SaaS locales para que el estado post Reservaciones 1-B sea considerado valido.

Esta fase no ejecuta migraciones, no modifica base de datos y no toca codigo funcional de Reservaciones, check-in/check-out, Caja, PWA/offline, Sync ni APIs.

## Herramientas actualizadas

Se actualizaron:

- `src/tools/saas/verificar_estado.php`
- `src/tools/saas/preflight_hotel_id.php`

## Nuevo estado aceptado

Las herramientas ahora reconocen que las siguientes tablas ya fueron migradas con `hotel_id`:

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_abonos`
- `reservacion_notas`
- `solicitudes_factura`

`verificar_estado.php` valida:

- la migracion `20260526_008_add_hotel_id_reservaciones_base.sql` registrada como `ejecutada`;
- columnas `hotel_id` existentes;
- 0 registros con `hotel_id NULL`;
- registros asignados a Los Cedros;
- indices simples por `hotel_id`;
- reservaciones 48 y 49 asignadas a Los Cedros.

`preflight_hotel_id.php` valida:

- la migracion 008 registrada como `ejecutada`;
- las tablas base de Reservaciones como migradas;
- indices simples por `hotel_id`;
- 0 registros `hotel_id NULL`;
- que no se requiera foreign key estricta en esta fase.

## Tablas que siguen pendientes

Siguen pendientes y no deben tratarse como migradas todavia:

- `movimientos_caja`
- `cortes_caja`
- `cajas`
- `huespedes`
- `sync_queue`
- `push_subscriptions`
- `inventario_movimientos`
- `alertas_inventario`
- `productos`
- `categorias_producto`
- tablas de tarifas/configuracion historica
- tablas operativas de llaves/remotos
- APIs/PWA

## Riesgos que siguen activos

- Reservaciones funcional todavia no escribe ni filtra por `hotel_id`.
- Check-in/check-out todavia no debe tocarse.
- Caja y movimientos de caja siguen sin tenant y son de alto riesgo.
- PWA/offline, Sync y APIs siguen pendientes.
- `huespedes` requiere decision de modelo global o por hotel.
- No se deben crear foreign keys estrictas hasta tratar huerfanos historicos.
- `hotel_id` sigue `INT NULL` por seguridad mientras falta integracion funcional.

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

Resultado esperado despues de esta fase:

- `verificar_estado.php` -> PASS
- `preflight_hotel_id.php` -> PASS
- 0 errores criticos

## Siguiente fase recomendada

La siguiente fase recomendada es Reservaciones 1-D-A: auditoria y propuesta para integrar `hotel_id` en codigo base de Reservaciones, sin tocar todavia check-in/check-out, Caja, PWA/offline, Sync ni APIs.
