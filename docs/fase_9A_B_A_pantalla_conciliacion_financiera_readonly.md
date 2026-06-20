# Fase 9A-B-A - Pantalla conciliacion financiera read-only

## Estado

`PANTALLA_9A_B_A_CONCILIACION_FINANCIERA_READONLY_VALIDADA_MANUALMENTE`

## Objetivo

Implementar una pantalla operativa de conciliacion financiera en modo solo lectura,
basada en el contrato 9A-B-0 y en el preflight 9A-A.

La pantalla permite revisar, por hotel actual, el estado cruzado de CxC, CxP, Caja,
cortes y auditoria sin ejecutar correcciones ni movimientos.

## Alcance implementado

- Ruta GET:
  - `/operacion/conciliacion-financiera`.
- Controlador:
  - `OperacionController::conciliacionFinancieraAction()`.
- Modelo read-only:
  - `ConciliacionFinanciera::reporteReadOnlyPorHotel()`.
- Vista:
  - `operacion/conciliacion_financiera.php`.
- Navegacion:
  - enlace desde `/operacion/diaria`.
- Preflight:
  - `preflight_conciliacion_financiera.php` ahora valida tambien ruta,
    controlador, modelo, vista, tokens visuales y ausencia de POST propio.

## Reglas conservadas

- No se agregan rutas POST para conciliacion financiera.
- No se agregan formularios POST.
- No se agregan botones de corregir, pagar, cobrar, revertir, ajustar ni compensar.
- No se escriben CxC, CxP, Caja, cortes, reservas, compras, proveedores ni auditoria.
- No se crean migraciones.
- No se toca PWA/offline, IndexedDB, cache names ni `/api/sync`.
- El `hotel_id` se toma del contexto actual, no de query string.
- La UI usa tokens `--brand-*` del hotel y no tokens `--ms-*`.

## Filtros disponibles

Todos los filtros son GET:

- `fecha_desde`;
- `fecha_hasta`;
- `tipo`;
- `severidad`;
- `corte_id`;
- `referencia`;
- `page`;
- `limit`.

## Lecturas expuestas

La pantalla muestra:

- resumen CxC;
- resumen CxP;
- resumen Caja financiera;
- resumen de auditoria esperada;
- estado general de alertas;
- matriz paginada de reglas de conciliacion;
- detalle de esquema requerido.

Las alertas se presentan como diagnostico. No son acciones automaticas.

## Validacion automatica inicial

Prueba directa del lector para hotel local `4`:

- esquema: `ok`;
- alertas evaluadas: `26`;
- hallazgos: `0`;
- errores: `0`;
- saldo CxC pendiente: `$4,250.00`;
- saldo CxP pendiente: `$1,910.00`.

Preflight 9A-A/9A-B-A:

- `OK: 96`;
- `WARNING: 0`;
- `ERROR: 0`.

Validaciones complementarias:

- `php -l` OK en modelo, controlador, vista, rutas, preflight y health checker.
- `preflight_cuentas_por_cobrar.php`: `OK: 45`, `WARNING: 6`, `ERROR: 0`.
- `preflight_pagos_proveedores_caja.php`: `OK: 29`, `WARNING: 1`, `ERROR: 0`.
- `health_check_fase_1a.php`: `OK: 281`, `WARNING: 25`, `ERROR: 0`.
- HTTP sin sesion para `/operacion/conciliacion-financiera`: `303` hacia `/login`.
- `git diff --check`: sin errores de whitespace.
- `/api/sync`: health confirma bloqueo en codigo con HTTP 423 y
  `sync_temporarily_disabled`; sin sesion el request HTTP redirige antes por auth.

## QA manual validada

Confirmacion recibida del usuario: la prueba manual con sesion paso correctamente.

Checklist validado:

1. Abrir `/operacion/conciliacion-financiera`.
2. Confirmar etiqueta visible `Solo lectura`.
3. Confirmar resumen CxC/CxP/Caja/Auditoria.
4. Confirmar filtros GET.
5. Confirmar paginacion si aplica.
6. Confirmar que no hay formularios POST ni botones operativos.
7. Confirmar que la pantalla no modifica saldos, movimientos ni cortes.
8. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
   `sync_temporarily_disabled`.

No queda QA manual pendiente para 9A-B-A.

## Rollback

Rollback de codigo:

1. Retirar ruta GET `/operacion/conciliacion-financiera`.
2. Retirar `OperacionController::conciliacionFinancieraAction()` y helpers de filtros.
3. Retirar `app/models/ConciliacionFinanciera.php`.
4. Retirar `app/views/operacion/conciliacion_financiera.php`.
5. Retirar enlace desde `operacion/diaria.php`.
6. Revertir las validaciones 9A-B-A agregadas al preflight.

No ejecutar SQL ni tocar datos financieros durante rollback.
