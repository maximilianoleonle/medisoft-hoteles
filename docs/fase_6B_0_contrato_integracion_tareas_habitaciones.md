# Fase 6B-0 - Contrato Integracion tareas + habitaciones

Estado: `CONTRATO_6B_INTEGRACION_TAREAS_HABITACIONES_COMPLETADO`.

## Objetivo

Definir una integracion segura entre tareas operativas y habitaciones para mejorar la
visibilidad operativa, sin automatizar disponibilidad ni cambiar los flujos que el
usuario ya valido.

## Fuentes de verdad

- Habitaciones y disponibilidad: `habitaciones`.
- Tareas operativas: `tareas_operativas`.
- Eventos de tarea: `tarea_eventos`.
- Mantenimiento historico/programado: `mantenimientos_habitaciones`.
- Trabajadores asignables: `trabajadores`.

## Alcance permitido para 6B

- Mejorar lectura contextual de tareas en habitaciones.
- Agregar indicadores read-only de tareas activas por habitacion.
- Agregar filtros o enlaces GET desde reportes/listados hacia tareas relacionadas.
- Reforzar health/preflights de consistencia tareas-habitaciones.
- Documentar QA, rollback, fuentes de verdad y decisiones.

## Prohibido en 6B sin subfase explicita

- Crear tareas automaticamente al liberar/check-out.
- Completar tareas automaticamente al liberar una habitacion.
- Cambiar `habitaciones.estado` al iniciar/completar/cancelar una tarea.
- Cambiar `mantenimientos_habitaciones` al completar una tarea.
- Mover la creacion de tareas de limpieza a la ficha de habitacion.
- Agregar botones de creacion de tarea en `habitaciones/ver.php`.
- Crear movimientos de Caja, pagos, abonos o nomina.
- Tocar PWA/offline/cache/IndexedDB.
- Tocar `/api/sync`.

## Regla de UX vigente

La creacion manual de tareas de limpieza permanece solo en `/reportes/limpieza`.
La ficha de habitacion puede mostrar tareas relacionadas, pero no debe ofrecer el boton
"Crear tarea de limpieza" salvo nuevo mensaje real explicito del usuario.

## Semaforo de riesgo

- Verde: indicadores read-only, links GET, estados vacios, checkers.
- Amarillo: acciones manuales que modifiquen solo `tareas_operativas` con CSRF y
  auditoria.
- Rojo: automatizar disponibilidad, liberar habitaciones, integrar con Caja/nomina,
  tocar `/api/sync`, crear tareas desde checkout o desde offline.

## Subfases sugeridas

- 6B-0: contrato y diagnostico.
- 6B-A: indicadores read-only de tareas activas en vistas de habitaciones.
- 6B-B: preflight de consistencia tareas-habitaciones ampliado.
- 6B-F: revision, auditoria y cierre tecnico.

## Definition of Done 6B-0

- Contrato documentado.
- Fuentes de verdad actualizadas.
- QA pendiente documentada.
- Rollback documentado.
- Decision tecnica registrada.
- Sin codigo funcional nuevo.
- Sin DB, sin migraciones y sin escrituras.
- Git con commit documental separado.

## Rollback

Revertir el commit documental de 6B-0. No aplica SQL porque esta subfase no crea rutas,
modelos, vistas ni datos.
