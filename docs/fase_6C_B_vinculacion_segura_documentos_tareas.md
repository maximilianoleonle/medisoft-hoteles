# Fase 6C-B - Vinculacion segura de documentos en tareas

## Objetivo

Habilitar la accion contextual de vincular documentos a una tarea operativa usando el Centro Documental existente, sin crear rutas paralelas, sin cambiar storage y sin exponer archivos privados.

## Alcance implementado

- `Documento::ENTIDAD_TIPOS` acepta `tarea`.
- `Documento::entidadExisteEnHotel()` valida `tarea` contra `tareas_operativas` usando `id + hotel_id`.
- `DocumentoController` muestra la etiqueta `Tarea` para el contexto documental.
- `GET /tareas/{id}` habilita los enlaces existentes del partial documental:
  - `Ver todos`
  - `Vincular documento`
- El flujo de carga sigue usando `/documentos/subir?entidad_tipo=tarea&entidad_id={id}`.
- El POST de carga sigue siendo el del Centro Documental, con CSRF, validacion de entidad, validacion de archivo y auditoria existente.

## Seguridad

- No se agregan rutas nuevas.
- No se agrega storage nuevo.
- No se expone `storage_path`.
- No se cambia `tareas_operativas`.
- No se cambia `tarea_eventos`.
- No se cambia `habitaciones.estado`.
- No se toca Caja, pagos, nomina, offline ni `/api/sync`.
- La entidad `tarea` solo es valida si pertenece al hotel actual.

## QA manual diferida

1. Abrir `/tareas/{id}` con una tarea del hotel activo.
2. Confirmar que aparece la seccion "Documentos vinculados".
3. Confirmar que aparece "Vincular documento".
4. Clic en "Vincular documento".
5. Confirmar que abre `/documentos/subir?entidad_tipo=tarea&entidad_id={id}`.
6. Subir un PDF o imagen valida.
7. Confirmar que el documento queda vinculado a la tarea y visible en el detalle.
8. Confirmar que no aparece `storage_path`.
9. Confirmar que una tarea de otro hotel no puede vincularse desde la sesion actual.

## Rollback

- Revertir el commit de 6C-B.
- No borrar documentos cargados.
- No borrar filas de `documento_entidades`.
- Si ya existen documentos vinculados con `entidad_tipo = tarea`, conservarlos para reconciliacion o lectura futura.
