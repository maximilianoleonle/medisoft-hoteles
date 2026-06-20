# Fase 10A-B-A - Pantalla tablero ejecutivo read-only

## Estado

`PANTALLA_10A_B_A_TABLERO_EJECUTIVO_READONLY_QA_MANUAL_VALIDADA`

## Objetivo

Implementar la pantalla `GET /reportes/ejecutivo` en modo solo lectura, usando el
contrato 10A-B-0 y el preflight 10A-A como base.

## Cambios realizados

- Ruta nueva:
  `GET /reportes/ejecutivo`.
- Controlador:
  `ReportesController::ejecutivoAction`.
- Lector read-only:
  `src/app/models/TableroEjecutivo.php`.
- Vista:
  `src/app/views/reportes/ejecutivo.php`.
- Enlace GET desde:
  `src/app/views/reportes/index.php`.
- Preflight extendido:
  `src/tools/saas/preflight_tablero_ejecutivo.php`.
- Health extendido:
  `src/tools/saas/health_check_fase_1a.php`.

## Alcance cumplido

- La pantalla usa solo `GET`.
- No existe `POST /reportes/ejecutivo`.
- La vista muestra etiqueta `Solo lectura`.
- La vista usa filtros GET.
- No recibe `hotel_id` editable.
- Usa tokens `--brand-*`.
- No usa tokens `--ms-*`.
- El lector filtra por hotel actual cuando la fuente tiene `hotel_id`.
- `huespedes` se lee solo mediante join desde reservaciones del hotel.
- `ledger_laboral` queda degradado como fuente opcional ausente.
- `logs_auditoria` historica sin hotel queda como warning.
- No llama `archivarNotificacionReporteGerencialVisto`.
- No reutiliza `/reportes/gerencial-diario` como fuente read-only pura.

## Fuera de alcance

No se implementan:

- pagos;
- cobros;
- reversiones;
- cierres o reaperturas de corte;
- recalculos persistentes;
- ajustes de inventario;
- cambios de tareas;
- archivado documental;
- exports PDF/CSV/XLSX;
- links publicos;
- migraciones;
- permisos/auth;
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Validaciones

Sintaxis PHP:

- `php -l app/models/TableroEjecutivo.php`: OK.
- `php -l app/controllers/ReportesController.php`: OK.
- `php -l app/views/reportes/ejecutivo.php`: OK.
- `php -l app/views/reportes/index.php`: OK.
- `php -l config/routes.php`: OK.
- `php -l tools/saas/preflight_tablero_ejecutivo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.

Prueba directa del lector:

- `schema=ok`.
- `alertas=8`.
- `ingresos=18300`.
- `ocupacion=0`.

Preflight:

- `OK: 86`.
- `WARNING: 5`.
- `ERROR: 0`.
- `Resultado general: PASS_WITH_WARNINGS_ALLOWED`.

Health general:

- `OK: 294`.
- `WARNING: 26`.
- `ERROR: 0`.
- `Resultado general: PASS_WITH_WARNINGS_ALLOWED`.

HTTP sin sesion:

- `GET /reportes/ejecutivo`: `303` hacia `/login`.

## Warnings conocidos

- `/reportes/gerencial-diario` archiva notificaciones al abrirse.
- `archivarNotificacionReporteGerencialVisto` modifica `notificaciones`.
- `ledger_laboral` no existe y el KPI queda degradado.
- `huespedes` no tiene `hotel_id` directo y debe consultarse por joins scoped.
- `logs_auditoria` conserva historico con `hotel_id` nulo.

## QA manual validada

El usuario confirmo que la prueba manual paso.

Checklist validado:

1. Abrir `/reportes/ejecutivo` con sesion de hotel.
2. Confirmar etiqueta `Solo lectura`.
3. Confirmar filtros GET: periodo, desde, hasta, area, estado y alertas.
4. Confirmar que no hay POST ni botones operativos.
5. Confirmar KPIs y secciones: operacion, finanzas, inventario, tareas/personal,
   documentos/auditoria y alertas.
6. Confirmar enlaces GET a reportes, conciliacion, operacion diaria, arqueo,
   inventario y tareas.
7. Filtrar y confirmar que no cambian notificaciones, saldos, cortes, movimientos,
   inventario, tareas ni documentos.

## Siguiente paso seguro

10A-B-F quedo documentado como cierre del bloque.

El siguiente bloque recomendado es separar lectura de reporte gerencial diario y
archivado automatico de notificaciones en un contrato independiente.

## Seguimiento 10B-0

El contrato de separacion quedo documentado en
`docs/fase_10B_0_contrato_reporte_gerencial_notificaciones.md`.

## Seguimiento 10B-A

La separacion quedo implementada en
`docs/fase_10B_A_separacion_reporte_gerencial_notificaciones.md`.

El warning sobre archivado por lectura directa de `/reportes/gerencial-diario` queda
resuelto para el estado actual del codigo.
