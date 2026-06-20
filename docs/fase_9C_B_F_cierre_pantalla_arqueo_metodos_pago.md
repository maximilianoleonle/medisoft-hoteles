# Fase 9C-B-F - Cierre pantalla arqueo por corte y metodo read-only

## Estado

`CIERRE_9C_B_F_PANTALLA_ARQUEO_METODOS_PAGO_COMPLETADO`

## Objetivo

Cerrar formalmente el bloque 9C-B despues de la validacion manual exitosa de la
pantalla de arqueo por corte y metodo de pago en modo solo lectura.

## Alcance

Este cierre es documental.

No agrega:

- codigo PHP;
- rutas;
- formularios;
- migraciones;
- escrituras de DB;
- cambios en movimientos de Caja;
- cambios en cortes;
- cambios en saldos;
- cambios en CxC;
- cambios en CxP;
- cambios en reservaciones, compras, proveedores o facturacion;
- cambios en PWA/offline o `/api/sync`.

## Bloques cubiertos

- 9C-0 contrato de arqueo por corte y metodo read-only.
- 9C-A preflight CLI de arqueo por corte y metodo read-only.
- 9C-B-0 contrato de pantalla de arqueo por corte y metodo read-only.
- 9C-B-A pantalla GET/read-only validada manualmente.

## Evidencia final

- Ruta validada: `GET /caja/arqueo-metodos`.
- Controlador: `CajaController::arqueoMetodosAction()`.
- Modelo: `ArqueoMetodosPago::reporteReadOnlyPorHotel()`.
- Vista: `caja/arqueo_metodos.php`.
- Filtros: GET por fecha, corte, caja, estado, metodo, severidad, pagina y limite.
- UI: etiqueta visible `Solo lectura`, resumen general, resumen por metodo, alertas,
  cortes y movimientos anomalos.
- Branding: tokens `--brand-*`, sin tokens `--ms-*`.

## Validaciones automaticas

- `php -l`: OK en archivos PHP tocados durante la fase.
- Prueba directa del lector:
  - esquema: `ok`;
  - cortes evaluados para hotel de prueba: `7`;
  - warnings: `0`;
  - errores: `0`;
  - movimientos: `21`;
  - metodos: `3`.
- `preflight_arqueo_metodos_pago.php`:
  - `OK: 39`;
  - `WARNING: 2`;
  - `ERROR: 0`.
- `health_check_fase_1a.php`:
  - `OK: 287`;
  - `WARNING: 25`;
  - `ERROR: 0`.
- HTTP sin sesion: `/caja/arqueo-metodos` responde `303` hacia `/login`.
- `git diff --check` limitado a 9C-B-A/9C-B-F: sin errores.
- `/api/sync`: health confirma bloqueo en codigo con HTTP 423 y
  `sync_temporarily_disabled`.

Warnings esperados:

- `4` cortes cerrados con totales guardados distintos a movimientos.
- `1` corte cerrado con `efectivo_esperado` distinto a formula guardada.

Estos warnings son historicos y no autorizan correcciones automaticas.

## Validacion manual

Confirmacion recibida del usuario: la pantalla se ve correctamente.

Se considera validado:

- acceso con sesion de hotel;
- etiqueta `Solo lectura`;
- filtros GET;
- resumen general;
- resumen por metodo;
- tabla de alertas;
- tabla de cortes;
- ausencia de botones operativos de cierre, recalculo, correccion, ajuste o edicion.

## Decision de cierre

El bloque 9C-B queda cerrado como pantalla diagnostica read-only.

La pantalla puede usarse para observar estado de cortes y metodos de pago, pero no
corrige datos ni habilita operacion nueva de Caja.

## Limites despues del cierre

No avanzar desde este cierre a:

- cerrar cortes;
- reabrir cortes;
- recalcular cortes;
- corregir diferencias;
- ajustar saldos;
- editar movimientos;
- cambiar metodos de pago;
- automatizar cierres;
- exportaciones sensibles sin contrato;
- permisos nuevos;
- integracion PWA/offline;
- cambios en `/api/sync`.

Cualquier ampliacion debe abrir contrato independiente, con alcance, rollback y
validaciones propias.
