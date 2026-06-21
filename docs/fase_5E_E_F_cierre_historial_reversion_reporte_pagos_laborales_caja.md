# Fase 5E-E-F - Cierre historial, reversion y reporte de pagos laborales con Caja

## Estado

`CIERRE_5E_E_F_HISTORIAL_REVERSION_REPORTE_PAGOS_LABORALES_CAJA_QA_MANUAL_VALIDADA`

## Objetivo

Cerrar formalmente el bloque posterior a 5E-D-F donde se agregaron superficies de
seguimiento, reversion controlada, reporte y exportacion CSV para pagos laborales con
Caja.

## Alcance cerrado

Este cierre documenta trabajo ya implementado y validado manualmente en local.

Incluye:

- Historial read-only de pagos laborales con Caja en la ficha del trabajador.
- Reversion controlada de pago laboral individual.
- Reporte read-only de pagos laborales con Caja.
- Export CSV read-only del reporte, respetando filtros GET.

No agrega nuevas migraciones, permisos, PWA/offline, IndexedDB, cache names ni cambios
en `/api/sync`.

## Superficies implementadas

- `GET /trabajadores/{id}` muestra el historial laboral Caja por trabajador.
- `POST /trabajadores/{id}/pagos-caja/{pagoid}/revertir` revierte un pago laboral
  individual con CSRF, token y servicio transaccional.
- `GET /trabajadores/pagos-caja/reporte` muestra el cuadre read-only de pagos,
  reversiones, cortes, metodos y trabajadores.
- `GET /trabajadores/pagos-caja/reporte/exportar` descarga CSV read-only del reporte.

## Archivos operativos del bloque

- `src/app/services/TrabajadorPagoCajaService.php`
- `src/app/controllers/TrabajadorController.php`
- `src/app/models/Trabajador.php`
- `src/app/views/trabajadores/ver.php`
- `src/app/views/trabajadores/reporte_pagos_caja.php`
- `src/config/routes.php`
- `src/tools/saas/preflight_personal_pagos_caja.php`
- `src/tools/saas/health_check_fase_1a.php`

## Contrato operativo

La fuente de verdad laboral-financiera sigue siendo:

- `trabajador_pagos`: conceptos laborales informativos, no pagos reales.
- `trabajador_pagos_caja`: pagos laborales reales aplicados desde Caja.
- `movimientos_caja`: egresos `Pago laboral` e ingresos `Reversion Pago laboral`.
- `logs_auditoria`: trazabilidad de pago y reversion.

La reversion no elimina registros. Marca el pago laboral como `revertido` y crea un
movimiento de Caja inverso controlado cuando el corte y la cuenta son elegibles.

El reporte y el CSV son GET/read-only. No registran pagos, no revierten pagos, no
modifican Caja y no cambian saldos.

## Evidencia manual

El usuario confirmo manualmente:

- El historial de pagos laborales con Caja se muestra en ficha.
- La reversion controlada funciona.
- El reporte de pagos laborales con Caja se muestra correctamente.
- El export CSV descarga los registros filtrados.

## Validaciones automaticas recientes

Ultima corrida posterior al export CSV:

- `app/controllers/TrabajadorController.php`: lint OK.
- `app/views/trabajadores/reporte_pagos_caja.php`: lint OK.
- `config/routes.php`: lint OK.
- `tools/saas/preflight_personal_pagos_caja.php`: lint OK.
- `tools/saas/health_check_fase_1a.php`: lint OK.
- Preflight pagos laborales con Caja: `OK: 45`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Ruta local de export CSV sin sesion: `303` a `/login`, sin 404.
- Carga de rutas: `ROUTES_LOAD_OK`.

## Fuera de alcance despues del cierre

No queda autorizado avanzar automaticamente a:

- pagos laborales masivos;
- nomina automatica;
- calculo de periodos de nomina;
- recibos de nomina oficiales;
- dispersion bancaria;
- abonos o liquidaciones automaticas de anticipos/prestamos;
- nuevas categorias de Caja;
- cambios de permisos/auth;
- PWA/offline, IndexedDB, cache names o `/api/sync`.

## Siguiente paso seguro

Abrir un contrato independiente antes de cualquier nuevo frente.

Opciones seguras:

- 5E-G-0 contrato de nomina por periodo en modo preview/read-only.
- 5E-H-0 contrato de recibo laboral informativo sin dispersion ni Caja.
- Cierre del bloque actual y regreso a otro modulo no financiero.

Cualquier implementacion que toque modelo, ruta, controlador, vista con formularios,
servicio, Caja, cortes, movimientos o base de datos requiere autorizacion explicita.
