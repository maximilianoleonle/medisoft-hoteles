# Fase 10B-F - Cierre reporte gerencial y notificaciones

## Estado

`CIERRE_10B_REPORTE_GERENCIAL_NOTIFICACIONES_QA_MANUAL_VALIDADA`

## Objetivo

Cerrar documentalmente el bloque 10B despues de la validacion manual del usuario.

## Resultado

La separacion entre lectura directa del reporte gerencial y archivado controlado de
notificaciones queda validada manualmente.

El usuario confirmo que la prueba manual paso perfectamente.

## Alcance cerrado

Quedan cerradas como bloque:

- 10B-0: contrato reporte gerencial y notificaciones.
- 10B-A: separacion funcional de reporte gerencial HTML/PDF y notificaciones.
- 10B-F: cierre documental con QA manual validada.

## Archivos funcionales cerrados

- `src/app/controllers/ReportesController.php`.
- `src/tools/saas/preflight_tablero_ejecutivo.php`.
- `src/tools/saas/health_check_fase_1a.php`.

## Archivos documentales cerrados

- `docs/fase_10B_0_contrato_reporte_gerencial_notificaciones.md`.
- `docs/fase_10B_A_separacion_reporte_gerencial_notificaciones.md`.
- `docs/fase_10B_F_cierre_reporte_gerencial_notificaciones.md`.

## Validaciones registradas

Desde 10B-A:

- PHP lint en archivos tocados: OK.
- Preflight tablero ejecutivo: `OK: 91`, `WARNING: 3`, `ERROR: 0`.
- Health general: `OK: 298`, `WARNING: 25`, `ERROR: 0`.
- HTTP sin sesion en HTML/PDF gerencial: `303`.
- QA manual con sesion: validada por el usuario.

## Confirmaciones manuales

El usuario confirmo:

- abrir el reporte gerencial directo funciona correctamente;
- abrir o descargar el PDF directo no archiva la notificacion por lectura simple;
- abrir desde el centro de notificaciones conserva el comportamiento esperado;
- la prueba manual paso perfectamente.

## Guardas mantenidas

- No se agregaron rutas nuevas.
- No se agregaron formularios.
- No se agregaron migraciones ni datos.
- No se tocaron permisos/auth.
- No se tocaron calculos de reporte gerencial.
- No se tocaron Caja, cortes, pagos, cobros, CxC, CxP, inventario, tareas ni
  documentos.
- No se tocaron PWA/offline, IndexedDB, cache names ni `/api/sync`.
- `/api/sync` debe seguir bloqueado con HTTP 423 y `sync_temporarily_disabled`.

## Estado final del bloque

`GET /reportes/gerencial-diario` y `GET /reportes/gerencial-diario/pdf` quedan como
lecturas directas sin archivado automatico de notificaciones.

El archivado de notificaciones de reporte gerencial queda reservado al flujo
controlado de notificaciones.

## Siguiente bloque recomendado

Abrir un contrato independiente para el siguiente frente de mejora. No extender 10B
hacia calculos del reporte, permisos, rutas nuevas, notificaciones automaticas,
PWA/offline ni `/api/sync` sin contrato y autorizacion explicita.

## Seguimiento 11A-0

El siguiente contrato independiente quedo documentado en
`docs/fase_11A_0_contrato_perfil_huesped_readonly.md`.
