# Fase 6C-F - Cierre tecnico de evidencias/documentos en tareas

## Estado

`BLOQUE_6C_DOCUMENTOS_TAREAS_CERRADO_QA_DIFERIDA`

## Fases cubiertas

- 6C-0 contrato de evidencias/documentos en tareas.
- 6C-A documentos read-only en detalle de tarea.
- 6C-B vinculacion segura desde tarea usando Centro Documental.

## Commits

- `2ae5bd0 docs(phase-6c): define task evidence document contract`
- `82d1c17 feat(phase-6c): show read-only task documents`
- `b691b97 feat(phase-6c): enable safe task document linking`

## Revision tecnica

- `GET /tareas/{id}` muestra documentos vinculados por `hotel_id`.
- `Documento::documentosPorTareaHotel()` consulta `documento_entidades` con
  `entidad_tipo = 'tarea'` y valida contra `tareas_operativas`.
- `Documento::entidadExisteEnHotel()` acepta `tarea` y valida `id + hotel_id`.
- La vinculacion usa `/documentos/subir` existente.
- No se crearon rutas paralelas ni storage paralelo.
- No se expone `storage_path` en la vista de tarea.

## Auditoria de seguridad

- Acceso protegido por sesion y contexto hotelero del Centro Documental.
- POST de carga existente conserva CSRF.
- Validacion de archivo queda centralizada en el Centro Documental.
- La entidad `tarea` no puede apuntar a tareas de otro hotel si el controlador/modelo se usa correctamente.
- Sin Caja, pagos, nomina, offline ni `/api/sync`.
- Sin cambios automaticos en `habitaciones.estado`.

## Verificaciones automaticas

- `php -l` en PHP tocado durante 6C-A/6C-B.
- `tools/saas/preflight_tareas_operativas.php`: `ERROR: 0`.
- `tools/saas/health_check_fase_1a.php`: `ERROR: 0`; warnings historicos permitidos.
- HTTP sin sesion a tarea y carga contextual: redireccion a login.
- SQL read-only: `documento_entidades` con `entidad_tipo = tarea` sin cruces de hotel.
- `git diff --check`: sin errores; solo avisos CRLF historicos.

## QA manual diferida

1. Abrir una tarea existente.
2. Confirmar seccion documental.
3. Usar "Vincular documento".
4. Subir un archivo permitido.
5. Confirmar que queda vinculado a la tarea.
6. Confirmar que no aparece `storage_path`.
7. Confirmar que no se modifica estado de habitacion.

## Rollback

- Revertir commits 6C-A/6C-B si el bloque completo debe apagarse.
- No borrar documentos reales ni filas `documento_entidades`.
- Si hay documentos ya vinculados a `tarea`, conservarlos para reconciliacion.

## Siguiente accion segura

Pasar a un bloque independiente. Por el roadmap vigente, el siguiente candidato seguro
es 5B Asistencia laboral basica, iniciando con contrato/diagnostico antes de tocar DB o
crear acciones operativas.
