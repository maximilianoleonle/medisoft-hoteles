# Fase 6C-A - Documentos read-only en tareas

## Objetivo

Mostrar evidencias/documentos ya vinculados a una tarea operativa dentro de `GET /tareas/{id}`, sin habilitar todavia carga desde la tarea, sin nuevas rutas y sin exponer rutas internas de storage.

## Alcance implementado

- `Documento::documentosPorTareaHotel()` consulta documentos vinculados con `entidad_tipo = 'tarea'`.
- La consulta valida `documento_entidades.hotel_id` contra `tareas_operativas.hotel_id`.
- `TareaController::verAction()` pasa documentos vinculados al detalle de tarea.
- `tareas/ver.php` renderiza el partial `documentos_entidad` en modo read-only.
- `documentos_entidad` ahora permite ocultar acciones de "Ver todos" y "Vincular documento" por contexto.
- No se habilita `tarea` en `Documento::ENTIDAD_TIPOS`; por tanto el upload contextual a tareas queda diferido a 6C-B.

## Seguridad

- Sin POST nuevo.
- Sin cambios en `tareas_operativas`.
- Sin cambios en `tarea_eventos`.
- Sin cambios en `habitaciones.estado`.
- Sin Caja, nomina, pagos, offline ni `/api/sync`.
- Sin exposicion de `storage_path`.

## QA manual diferida

1. Abrir una tarea existente en `/tareas/{id}`.
2. Confirmar seccion "Documentos vinculados".
3. Confirmar estado vacio si no hay evidencias.
4. Confirmar que no aparece boton "Vincular documento" en la tarea.
5. Confirmar que no aparece `storage_path`.
6. Confirmar que los enlaces de documentos existentes apuntan a `GET /documentos/{id}` o descarga autenticada.

## Rollback

- Revertir el commit de 6C-A.
- No borrar documentos ni registros de `documento_entidades`.
- Si existen documentos reales vinculados manualmente como `tarea`, conservarlos para una fase de reconciliacion.
