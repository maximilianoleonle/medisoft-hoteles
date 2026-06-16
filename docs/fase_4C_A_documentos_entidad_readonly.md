# Fase 4C-A - Documentos por entidad contextual

Estado tecnico: `DOCUMENTOS_ENTIDAD_CONTEXTUAL_4C_COMPLETADO`.

## Objetivo

Mostrar documentos vinculados dentro de fichas operativas y ofrecer acceso a la carga
contextual existente, sin crear rutas nuevas ni exponer rutas internas de storage.

Entidades incluidas:

- proveedor;
- compra;
- cuenta por pagar;
- huesped;
- reservacion.

## Implementacion

- Se reutiliza `Documento::documentosPorEntidad($hotelId, $entidadTipo, $entidadId)`.
- Se crea el partial `src/app/views/partials/documentos_entidad.php`.
- Las fichas autorizadas incluyen el partial en modo lista segura.
- Los controladores pasan solo metadata documental segura a la vista.
- Los enlaces disponibles son GET a detalle documental y descarga autenticada ya existente.
- Se agrega enlace GET a `documentos/subir?entidad_tipo=...&entidad_id=...` para
  vincular documento mediante el flujo protegido existente.

## Seguridad

- Todo listado se filtra por `hotel_id`.
- No se muestra `storage_path`.
- No se muestra `nombre_archivo`.
- No se agregan formularios ni POST nuevos en las fichas.
- La carga contextual usa `DocumentoController::subirAction()` y `guardarAction()` ya
  existentes, con validacion de entidad por hotel y CSRF.
- No se agrega edicion desde ficha, borrado ni reemplazo de archivo.
- No se tocan Caja, pagos, abonos, Fase 3D, NP-A, PWA/offline ni `/api/sync`.

## Definition of Done

- Las cinco fichas muestran estado vacio claro si no hay documentos.
- Las cinco fichas muestran documentos vinculados si existen relaciones en
  `documento_entidades`.
- Los enlaces de documentos usan rutas documentales existentes y protegidas.
- Las cinco fichas muestran la accion `Vincular documento` hacia el flujo contextual.
- `php -l` pasa en archivos PHP tocados.
- Health checker detecta partial, vistas y consultas por entidad.
- SQL de verificacion confirma que el cambio de UI no inserta documentos ni relaciones.

## Rollback

- Revertir el commit `feat(phase-4c): add read-only entity document sections`.
- DB: no aplica; esta subfase no crea ni modifica datos.
- Archivos subidos y metadata documental previa permanecen intactos.
