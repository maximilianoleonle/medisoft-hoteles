# Fase 10B-A - Separacion reporte gerencial y notificaciones

## Estado

`SEPARACION_10B_A_REPORTE_GERENCIAL_NOTIFICACIONES_QA_MANUAL_VALIDADA`

## Objetivo

Separar la lectura directa del reporte gerencial diario del archivado automatico de
notificaciones.

## Cambios realizados

En `src/app/controllers/ReportesController.php`:

- `gerencialDiarioAction` ya no llama archivado automatico de notificaciones.
- `gerencialDiarioPdfAction` ya no llama archivado automatico de notificaciones.
- Se retiro el metodo privado `archivarNotificacionReporteGerencialVisto`.

En `src/tools/saas/preflight_tablero_ejecutivo.php`:

- Se extendio el preflight a `10B-A`.
- Se valida que el reporte gerencial HTML no archive notificaciones por lectura
  directa.
- Se valida que el PDF del reporte gerencial no archive notificaciones por descarga
  directa.
- Se valida que `ReportesController` ya no conserve el archivado automatico.
- Se confirma que el archivado queda en el flujo controlado de notificaciones.

En `src/tools/saas/health_check_fase_1a.php`:

- Se agrega `10B-A` al health general.
- Se agregan las mismas guardas de separacion para HTML, PDF y flujo controlado de
  notificaciones.

## Alcance cumplido

- `GET /reportes/gerencial-diario` conserva la lectura del reporte.
- `GET /reportes/gerencial-diario/pdf` conserva la descarga del PDF.
- Abrir el reporte directo ya no debe cambiar el estado de `notificaciones`.
- Descargar el PDF directo ya no debe cambiar el estado de `notificaciones`.
- El flujo de `/notificaciones/{id}/abrir` conserva el archivado controlado para
  notificaciones de reporte gerencial.

## Fuera de alcance

No se tocaron:

- rutas;
- modelos de negocio;
- migraciones;
- base de datos;
- permisos/auth;
- calculos del reporte gerencial;
- calculos de Caja, cortes, pagos, cobros, CxC o CxP;
- reglas de generacion de notificaciones automaticas;
- vistas o formularios;
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

`/api/sync` debe seguir bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Validaciones automaticas

Sintaxis PHP:

- `php -l app/controllers/ReportesController.php`: OK.
- `php -l app/controllers/NotificacionController.php`: OK.
- `php -l tools/saas/preflight_tablero_ejecutivo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.

Preflight:

- Comando: `php tools/saas/preflight_tablero_ejecutivo.php`.
- `OK: 91`.
- `WARNING: 3`.
- `ERROR: 0`.
- `Resultado general: PASS_WITH_WARNINGS_ALLOWED`.

Health general:

- Comando: `php tools/saas/health_check_fase_1a.php`.
- `OK: 298`.
- `WARNING: 25`.
- `ERROR: 0`.
- `Resultado general: PASS_WITH_WARNINGS_ALLOWED`.

HTTP sin sesion:

- `GET /reportes/gerencial-diario?fecha=2026-06-19`: `303`.
- `GET /reportes/gerencial-diario/pdf?fecha=2026-06-19`: `303`.

## QA manual validada

El usuario confirmo que la prueba manual paso perfectamente.

Checklist validado:

1. Abrir `/reportes/gerencial-diario?fecha=YYYY-MM-DD` con sesion.
2. Confirmar que el reporte carga correctamente.
3. Confirmar que una notificacion activa de reporte gerencial no cambia de estado por
   abrir el reporte directo.
4. Abrir `/reportes/gerencial-diario/pdf?fecha=YYYY-MM-DD` con sesion.
5. Confirmar que el PDF se descarga o renderiza correctamente.
6. Confirmar que la notificacion no cambia de estado por descargar el PDF directo.
7. Abrir la notificacion desde el centro de notificaciones.
8. Confirmar que solo ese flujo controlado aplica el archivado esperado.

## Seguimiento 10B-F

10B-F quedo documentado como cierre del bloque en
`docs/fase_10B_F_cierre_reporte_gerencial_notificaciones.md`.

## Rollback

Si se requiere revertir 10B-A:

1. Restaurar las llamadas de archivado en `gerencialDiarioAction` y
   `gerencialDiarioPdfAction`.
2. Restaurar el metodo privado `archivarNotificacionReporteGerencialVisto`.
3. Retirar las validaciones 10B-A del preflight y del health.
4. Retirar esta documentacion y referencias de seguimiento.

No ejecutar SQL ni tocar Caja, CxC, CxP, inventario, tareas, documentos,
permisos/auth, PWA/offline ni `/api/sync`.
