# Fase 6C-0 - Contrato evidencias/documentos en tareas

## Objetivo

Permitir que las tareas operativas puedan consultar y, en una subfase posterior, vincular evidencias documentales usando el Centro Documental moderno, sin crear almacenamiento paralelo y sin afectar habitaciones, mantenimiento, Caja, nomina, pagos, offline ni `/api/sync`.

## Diagnostico actual

- `tareas_operativas` ya es la fuente de verdad de tareas.
- `tarea_eventos` ya conserva historial tecnico de acciones de tarea.
- El Centro Documental moderno ya usa:
  - `documentos`;
  - `documento_entidades`;
  - `documento_tipos`.
- El partial `documentos_entidad` ya muestra documentos vinculados y usa rutas seguras del Centro Documental.
- `Documento::ENTIDAD_TIPOS` permite `proveedor`, `compra`, `cuenta_por_pagar`, `huesped`, `reservacion` y `trabajador`.
- Todavia no existe soporte formal para entidad documental `tarea`.
- El detalle de tarea `GET /tareas/{id}` esta protegido por sesion, hotel, modulo de habitaciones y permiso de vista.

## Propuesta tecnica

### 6C-A - Documentos read-only en tarea

- Agregar `tarea` como entidad permitida en `Documento`.
- Validar existencia con `tareas_operativas.id` + `hotel_id`.
- Consultar documentos vinculados a tarea en `TareaController::verAction()`.
- Mostrar seccion documental en `tareas/ver.php`.
- No crear nuevas tablas, rutas ni storage.
- No exponer `storage_path`.

### 6C-B - Vinculacion segura desde tarea

- Reutilizar `GET /documentos/subir?entidad_tipo=tarea&entidad_id={id}`.
- Reutilizar `POST /documentos/subir` existente con CSRF, validacion MIME, storage privado y auditoria.
- No crear endpoint nuevo de upload para tareas.
- No permitir eliminar archivos desde tarea.

### 6C-F - Revision, auditoria y cierre

- Validar filtros por hotel.
- Validar que documentos de tarea no cambian estado de tarea.
- Confirmar que descargas siguen pasando por `DocumentoController::descargarAction()`.
- Confirmar que Caja, nomina, pagos y `/api/sync` no participan.

## Semaforo de riesgo

- Verde: lectura contextual de documentos por `hotel_id` y entidad `tarea`.
- Amarillo: exponer boton de vincular documento en tarea puede incentivar carga de evidencias reales; debe usar el flujo documental existente.
- Rojo diferido: borrar fisicamente archivos, exponer rutas privadas o crear storage paralelo queda prohibido.

## Definition of Done

- `tarea` es entidad documental validada contra `tareas_operativas.hotel_id`.
- El detalle de tarea muestra documentos vinculados sin `storage_path`.
- La carga/vinculacion, si se habilita, reutiliza Centro Documental y no crea POST nuevo.
- No se modifica `habitaciones.estado`.
- No se modifica `tarea_eventos` salvo por acciones propias de tareas ya existentes.
- No se toca Caja, nomina, pagos, offline ni `/api/sync`.

## QA manual diferida

1. Abrir `/tareas/{id}`.
2. Confirmar seccion de documentos vinculados.
3. Confirmar estado vacio claro si no hay documentos.
4. Si se habilita vinculacion en 6C-B, cargar un PDF/imagen valida desde el flujo documental.
5. Confirmar que el documento aparece en la tarea y en Centro Documental.
6. Confirmar que una tarea de otro hotel no puede ver ni vincular documentos del hotel actual.

## Rollback

- Revertir commits 6C-A/6C-B.
- No borrar documentos reales ni registros de `documento_entidades`.
- Si QA manual sube evidencias reales, tratarlas como datos operativos y no eliminarlas sin fase autorizada.
- No tocar `tareas_operativas`, `tarea_eventos`, Caja, nomina, offline ni `/api/sync`.
