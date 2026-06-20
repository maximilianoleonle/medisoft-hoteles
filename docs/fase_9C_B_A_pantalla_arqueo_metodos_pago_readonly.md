# Fase 9C-B-A - Pantalla arqueo por corte y metodo read-only

## Estado

`PANTALLA_9C_B_A_ARQUEO_METODOS_PAGO_READONLY_COMPLETADA_QA_MANUAL_VALIDADA`

## Objetivo

Implementar una pantalla operativa de arqueo por corte y metodo de pago en modo
solo lectura, basada en el contrato 9C-B-0 y en el preflight 9C-A.

La pantalla permite revisar cortes, acumulados por metodo, diferencias historicas y
movimientos anomalos sin cerrar cortes, recalcular importes, editar movimientos ni
modificar saldos.

## Superficie implementada

- Ruta nueva:
  `GET /caja/arqueo-metodos`.
- Controlador:
  `CajaController::arqueoMetodosAction()`.
- Modelo read-only:
  `ArqueoMetodosPago::reporteReadOnlyPorHotel()`.
- Vista:
  `app/views/caja/arqueo_metodos.php`.
- Preflight extendido:
  `tools/saas/preflight_arqueo_metodos_pago.php`.
- Health extendido:
  `tools/saas/health_check_fase_1a.php`.

## Alcance implementado

- Filtros GET:
  `fecha_desde`, `fecha_hasta`, `corte_id`, `caja_id`, `estado_corte`,
  `metodo_pago`, `severidad`, `page`, `limit`.
- Resumen general:
  cortes, movimientos, ingresos, gastos, neto, warnings y errores.
- Resumen por metodo:
  efectivo, tarjeta y transferencia.
- Tabla de alertas estructurales e historicas.
- Tabla de cortes con comparativo movimiento vs total guardado.
- Tabla de movimientos anomalos detectados por reglas de consistencia.
- Paginacion de cortes.

## Garantias de seguridad

- La ruta es solo `GET`.
- No se agrega `POST /caja/arqueo-metodos`.
- La vista usa formulario `method="get"`.
- No hay `csrf_field()` porque no existe escritura.
- No hay `hotel_id` editable desde query string.
- El hotel se toma de la sesion actual.
- El modelo abre `START TRANSACTION READ ONLY` y cierra con rollback.
- La UI usa tokens `--brand-*` del hotel.
- La UI no usa tokens `--ms-*`.
- No se tocan migraciones ni base de datos.
- No se toca PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Prohibiciones mantenidas

La pantalla no permite:

- abrir cortes;
- cerrar cortes;
- reabrir cortes;
- recalcular cortes;
- corregir diferencias;
- ajustar saldos;
- editar movimientos;
- cambiar metodos de pago;
- crear cobros, pagos, abonos, reversiones ni compensaciones.

## Validacion automatica

Comandos ejecutados en Docker:

```bash
php -l app/models/ArqueoMetodosPago.php
php -l app/controllers/CajaController.php
php -l app/views/caja/arqueo_metodos.php
php -l config/routes.php
php -l tools/saas/preflight_arqueo_metodos_pago.php
php -l tools/saas/health_check_fase_1a.php
php tools/saas/preflight_arqueo_metodos_pago.php
php tools/saas/health_check_fase_1a.php
```

Resultados:

- Lint PHP: sin errores.
- Preflight 9C-A/9C-B-A:
  `OK: 39`, `WARNING: 2`, `ERROR: 0`.
- Health general:
  `OK: 287`, `WARNING: 25`, `ERROR: 0`.
- `/api/sync` sigue validado por health como bloqueado con HTTP 423 y
  `sync_temporarily_disabled`.

Warnings esperados:

- `4` cortes cerrados con totales guardados distintos a movimientos.
- `1` corte cerrado con `efectivo_esperado` distinto a formula guardada.

Estos warnings son diagnosticos historicos y no disparan correcciones automaticas.

## QA manual validada

El usuario confirmo que la pantalla se ve correctamente.

Validado manualmente:

1. Apertura con sesion de hotel: `/caja/arqueo-metodos`.
2. Etiqueta visible `Solo lectura`.
3. Filtros GET.
4. Resumen, alertas, cortes y movimientos anomalos.
5. Ausencia de acciones operativas de cierre, recalculo, correccion, ajuste o edicion.

La fase queda lista para cierre documental 9C-B-F.
