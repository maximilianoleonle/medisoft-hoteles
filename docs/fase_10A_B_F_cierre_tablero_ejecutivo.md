# Fase 10A-B-F - Cierre tablero ejecutivo read-only

## Estado

`CIERRE_10A_B_TABLERO_EJECUTIVO_READONLY_QA_MANUAL_VALIDADA`

## Objetivo

Cerrar documentalmente el bloque 10A-B del tablero ejecutivo read-only despues de la
validacion manual del usuario.

## Resultado

La pantalla `GET /reportes/ejecutivo` queda validada manualmente como tablero
ejecutivo de solo lectura.

El usuario confirmo que la prueba manual paso.

## Alcance cerrado

Quedan cerradas como bloque:

- 10A-0: contrato general de tablero ejecutivo integral read-only.
- 10A-A: preflight CLI/read-only.
- 10A-B-0: contrato de pantalla GET/read-only.
- 10A-B-A: implementacion de pantalla GET/read-only.
- 10A-B-F: cierre documental con QA manual validada.

## Archivos funcionales cerrados

- `src/config/routes.php`.
- `src/app/controllers/ReportesController.php`.
- `src/app/models/TableroEjecutivo.php`.
- `src/app/views/reportes/ejecutivo.php`.
- `src/app/views/reportes/index.php`.
- `src/tools/saas/preflight_tablero_ejecutivo.php`.
- `src/tools/saas/health_check_fase_1a.php`.

## Validaciones registradas

Desde 10A-B-A:

- PHP lint en archivos tocados: OK.
- Preflight: `OK: 86`, `WARNING: 5`, `ERROR: 0`.
- Health general: `OK: 294`, `WARNING: 26`, `ERROR: 0`.
- HTTP sin sesion: `303` hacia `/login`.
- QA manual con sesion: validada por el usuario.

## Confirmaciones manuales

El usuario confirmo:

- la pantalla abre correctamente con sesion;
- se ve el tablero ejecutivo;
- la prueba manual paso.

## Guardas mantenidas

- No existe `POST /reportes/ejecutivo`.
- La pantalla sigue siendo GET/read-only.
- No hay botones operativos de pago, cobro, reversion, cierre, reapertura, recalculo
  o ajuste.
- No se agregaron migraciones ni datos.
- No se tocaron permisos/auth.
- No se tocaron PWA/offline, IndexedDB, cache names ni `/api/sync`.
- `/api/sync` debe seguir bloqueado con HTTP 423 y `sync_temporarily_disabled`.

## Warnings conocidos aceptados

- `/reportes/gerencial-diario` archiva notificaciones al abrirse.
- `archivarNotificacionReporteGerencialVisto` modifica `notificaciones`.
- `ledger_laboral` no existe y el KPI queda degradado.
- `huespedes` no tiene `hotel_id` directo y debe consultarse por joins scoped.
- `logs_auditoria` conserva historico con `hotel_id` nulo.

## Siguiente bloque recomendado

Abrir un contrato independiente para separar la lectura de
`/reportes/gerencial-diario` del archivado automatico de notificaciones.

Ese bloque debe ser documental primero y no debe tocar permisos, Caja, CxC, CxP,
inventario, tareas, documentos, PWA/offline ni `/api/sync` sin autorizacion explicita.

## Seguimiento 10B-0

El contrato independiente quedo documentado en
`docs/fase_10B_0_contrato_reporte_gerencial_notificaciones.md`.

10B-0 no toca codigo ni cambia el comportamiento actual. Solo define una futura
separacion entre lectura directa del reporte gerencial y archivado controlado desde
notificaciones.

## Seguimiento 10B-A

10B-A resolvio el warning operativo de reporte gerencial directo: HTML y PDF ya no
archivan notificaciones desde `ReportesController`.

El archivado queda reservado al flujo controlado de notificaciones.
