# Fase 10B-0 - Contrato reporte gerencial y notificaciones

## Estado

`CONTRATO_10B_0_REPORTE_GERENCIAL_NOTIFICACIONES_COMPLETADO`

## Objetivo

Definir el siguiente bloque seguro despues del cierre 10A-B: separar la lectura de
`/reportes/gerencial-diario` y `/reportes/gerencial-diario/pdf` del archivado
automatico de notificaciones.

Esta fase es solo documental. No implementa codigo, rutas, controladores, modelos,
vistas, formularios, migraciones, datos ni escrituras.

## Problema actual detectado

El reporte gerencial diario existente mezcla dos responsabilidades:

- generar y mostrar el reporte gerencial diario;
- archivar automaticamente una notificacion de tipo
  `regla_reporte_gerencial_diario`.

Puntos observados:

- `ReportesController::gerencialDiarioAction` genera el reporte y despues llama
  `archivarNotificacionReporteGerencialVisto($fecha)`.
- `ReportesController::gerencialDiarioPdfAction` genera el PDF y tambien llama
  `archivarNotificacionReporteGerencialVisto($fecha)`.
- `archivarNotificacionReporteGerencialVisto` ejecuta `UPDATE notificaciones` para
  cambiar estado a `descartada`.

Por eso `GET /reportes/gerencial-diario` y `GET /reportes/gerencial-diario/pdf` no
deben tratarse como lectura pura hasta que esa escritura quede desacoplada.

## Principio de separacion

Una lectura directa de reporte debe leer y renderizar, sin modificar estado de
notificaciones.

Una accion de notificacion puede modificar estado solo cuando el usuario entra por un
flujo de notificaciones existente, acotado y visible, por ejemplo
`/notificaciones/{id}/abrir`.

## Alcance de una futura 10B-A

Solo con autorizacion explicita, una futura implementacion podria:

- retirar la llamada a `archivarNotificacionReporteGerencialVisto` desde
  `gerencialDiarioAction`;
- retirar la llamada a `archivarNotificacionReporteGerencialVisto` desde
  `gerencialDiarioPdfAction`;
- conservar la generacion del reporte y del PDF sin cambiar calculos;
- mantener el archivado en el flujo propio de notificaciones, si ya existe y esta
  scoped por hotel;
- agregar o extender una validacion CLI/read-only que detecte que los GET directos del
  reporte no escriben notificaciones;
- documentar QA manual antes/despues.

## Archivos que podria tocar una futura 10B-A

Solo con autorizacion explicita:

- `src/app/controllers/ReportesController.php`.
- `src/app/controllers/NotificacionController.php`, si se requiere verificar o acotar
  el flujo de apertura de notificaciones.
- `src/app/models/Notificacion.php`, solo si el flujo necesita reutilizar un metodo
  ya existente o encapsular una escritura acotada.
- `src/tools/saas/preflight_tablero_ejecutivo.php` o un preflight nuevo dedicado.
- `src/tools/saas/health_check_fase_1a.php`, si se agrega validacion automatica.
- Documentacion de seguimiento.

La fase 10B-0 no autoriza esos cambios. Solo los enumera para un paso futuro.

## Reglas que no deben cambiar

No cambiar sin contrato y autorizacion separados:

- rutas publicas nuevas;
- permisos o auth;
- calculos del reporte gerencial diario;
- calculos de Caja, cortes, pagos, cobros, saldos, CxC o CxP;
- reglas de generacion de notificaciones automaticas;
- PWA/offline, service worker, IndexedDB, cache names ni `/api/sync`;
- migraciones o estructura de base de datos.

`/api/sync` debe seguir bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Contrato funcional futuro

Una futura 10B-A solo se considerara correcta si:

1. `GET /reportes/gerencial-diario` no ejecuta `UPDATE`, `INSERT`, `DELETE`,
   `REPLACE`, `ALTER`, `TRUNCATE` ni escrituras indirectas sobre `notificaciones`.
2. `GET /reportes/gerencial-diario/pdf` tampoco modifica `notificaciones`.
3. El reporte HTML conserva los mismos filtros GET y la misma fecha seleccionada.
4. El PDF conserva el mismo contenido calculado y el mismo flujo de descarga.
5. El archivado de notificaciones queda solo en el centro de notificaciones o en un
   flujo equivalente explicitamente controlado.
6. Cualquier escritura de notificacion queda scoped por hotel actual.
7. No se crean rutas POST nuevas para el reporte gerencial.
8. No se agregan formularios nuevos al reporte gerencial.
9. No se toca Caja, cortes, pagos, cobros, reversiones, inventario, tareas,
   documentos, permisos/auth, PWA/offline ni `/api/sync`.

## QA manual futura

Cuando se implemente 10B-A, la QA manual minima debera cubrir:

1. Crear o identificar una notificacion activa de reporte gerencial diario para una
   fecha.
2. Abrir directamente `/reportes/gerencial-diario?fecha=YYYY-MM-DD`.
3. Confirmar que la notificacion no cambia de estado por abrir el reporte directo.
4. Abrir directamente `/reportes/gerencial-diario/pdf?fecha=YYYY-MM-DD`.
5. Confirmar que la notificacion no cambia de estado por descargar el PDF directo.
6. Abrir la misma notificacion desde `/notificaciones`.
7. Confirmar que solo ese flujo de notificacion aplica el cambio de estado esperado.
8. Confirmar que el reporte y PDF siguen mostrando los mismos totales.
9. Confirmar que `php tools/saas/preflight_tablero_ejecutivo.php` o el preflight
   dedicado termina con `ERROR: 0`.
10. Confirmar que `php tools/saas/health_check_fase_1a.php` termina con `ERROR: 0`.

## Rollback esperado de una futura 10B-A

Si la separacion funcional falla:

- restaurar las llamadas retiradas en `ReportesController`;
- retirar validaciones CLI nuevas;
- retirar referencias documentales de 10B-A;
- no ejecutar SQL;
- no tocar reportes financieros, Caja, cortes, CxC, CxP, inventario, tareas,
  documentos, permisos/auth, PWA/offline ni `/api/sync`.

## Siguiente paso seguro

Despues de este contrato, el paso seguro es una fase 10B-A solo con autorizacion
explicita para tocar controlador y, si hace falta, validaciones CLI/read-only.

## Seguimiento 10B-A

La separacion funcional quedo implementada en
`docs/fase_10B_A_separacion_reporte_gerencial_notificaciones.md`.

10B-A retiro el archivado automatico desde `ReportesController` y agrego guardas en
preflight/health para que el reporte gerencial HTML/PDF siga sin cambiar
notificaciones por lectura directa.

## Seguimiento 10B-F

El bloque quedo cerrado con QA manual validada en
`docs/fase_10B_F_cierre_reporte_gerencial_notificaciones.md`.
