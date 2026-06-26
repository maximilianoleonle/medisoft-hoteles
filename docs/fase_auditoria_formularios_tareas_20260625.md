# Auditoria de formularios: tareas

Fecha: 2026-06-25

## Alcance

- Controlador: `src/app/controllers/TareaController.php`.
- Vista de creacion: `src/app/views/tareas/form.php`.
- Vista de detalle: `src/app/views/tareas/ver.php`.
- Vistas revisadas sin cambios POST: `src/app/views/tareas/index.php`, `src/app/views/tareas/agenda.php`, `src/app/views/tareas/reporte.php`, `src/app/views/tareas/_contextual_list.php`.

## Hallazgos

- Crear tarea ya conservaba datos con `old()`, pero la vista no mostraba errores debajo de los campos.
- El mapeo de errores no normalizaba acentos y descartaba errores sin campo reconocido.
- Asignar trabajador no conservaba la seleccion si fallaba.
- Completar o cancelar tarea no conservaba el comentario si fallaba.
- Los formularios de detalle no mostraban errores junto a trabajador o comentario.
- Las vistas de listado, agenda y reporte usan filtros GET o contenido read-only; no requirieron cambios de recuperacion POST.

## Correcciones aplicadas

- Se agrego normalizacion de errores con acentos y soporte para `_global`.
- Asignar trabajador conserva `trabajador_id` y muestra errores del select.
- Completar/cancelar conservan `comentario` y muestran errores en el formulario correspondiente.
- Crear tarea muestra errores por campo en titulo, categoria, prioridad, habitacion, fecha programada, fecha limite y descripcion.
- Se agregaron estados visuales de error, `aria-invalid`, `aria-describedby` y resumen accesible para errores globales.
- Se limpian datos antiguos en operaciones exitosas de asignacion y cambios de estado.

## Fuera de alcance

- No se modificaron modelos, rutas, base de datos, migraciones, permisos ni autenticacion.
- No se modifico logica profunda de tareas, reportes, habitaciones, limpieza o mantenimiento.
- No se modificaron calculos de caja, reportes, reservaciones, check-in/check-out ni PWA/offline.
- No se modifico `/api/sync`.

## Validacion

- `php -l` en controlador, vista de creacion y vista de detalle: sin errores.
- `git diff --check` del bloque: sin errores reales; solo avisos CRLF.
- Formularios balanceados: `form.php` 1 apertura y 1 cierre; `ver.php` 4 aperturas y 4 cierres.
