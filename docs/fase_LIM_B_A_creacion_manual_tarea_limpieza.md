# Fase LIM-B-A - Creacion manual de tarea de limpieza

Estado: `CREACION_MANUAL_TAREA_LIM_B_A_COMPLETADA_QA_DIFERIDA`.

## Objetivo

Permitir crear manualmente una tarea operativa de categoria `limpieza` desde una
habitacion que ya esta en estado `limpieza`, sin liberar la habitacion ni automatizar
inventario.

## Cambios implementados

- Ruta POST:
  - `/tareas/desde-limpieza/{id}`
- Controlador:
  - `TareaController::crearDesdeLimpiezaAction()`
- Modelo:
  - `TareaOperativa::buscarTareaActivaLimpiezaPorHabitacionHotel()`
  - `TareaOperativa::crearDesdeLimpiezaHabitacionParaHotel()`
- Vista:
  - `reportes/limpieza-operativa.php` muestra boton solo cuando no hay tarea activa.
  - `habitaciones/ver.php` muestra acceso directo cuando la habitacion esta en limpieza y no tiene tarea activa.
- Preflight:
  - `tools/saas/preflight_limpieza_operativa.php` valida LIM-A/LIM-B-A.

## Guardrails

- Requiere sesion.
- Requiere permiso existente `habitaciones.mantenimiento`.
- Requiere CSRF.
- Valida `hotel_id`.
- Valida habitacion activa del hotel actual.
- Valida `habitaciones.estado = limpieza`.
- Bloquea duplicado activo de categoria `limpieza` por habitacion.
- Escribe solo en `tareas_operativas`, `tarea_eventos` y `logs_auditoria`.

## Prohibido y no implementado

- No libera habitaciones.
- No cambia `habitaciones.estado`.
- No crea tareas automaticas por checkout.
- No crea tareas masivas.
- No descuenta inventario.
- No toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Verificacion automatica requerida

- `php -l` en archivos PHP tocados.
- `tools/saas/preflight_limpieza_operativa.php`.
- `tools/saas/health_check_fase_1a.php`.
- HTTP sin sesion a POST debe redirigir/bloquear.
- SQL read-only antes/despues de intento sin sesion debe confirmar cero escrituras.
- `git diff --check`.

## QA manual diferida

1. Abrir `/reportes/limpieza`.
2. Abrir la ficha de una habitacion en limpieza y crear tarea desde acciones rapidas.
3. Crear tarea desde una habitacion en limpieza.
4. Confirmar detalle de tarea.
5. Confirmar `categoria = limpieza`, `habitacion_id`, `hotel_id` y
   `origen = habitacion`.
6. Intentar crear otra tarea activa para la misma habitacion y confirmar bloqueo limpio.
7. Confirmar que la habitacion sigue en estado `limpieza`.
8. Confirmar que no hay inventario automatico, Caja, pagos, abonos, nomina, offline ni
   cambios en `/api/sync`.

## Rollback

- Revertir el commit `feat(phase-lim): create housekeeping tasks manually`.
- Si QA manual crea tareas reales, no borrar datos con SQL manual; usar el flujo
  existente de cancelacion de tareas o documentar IDs.
- No tocar habitaciones, reservaciones, inventario, Caja, pagos, abonos, nomina ni
  `/api/sync`.

## Siguiente paso recomendado

Revision tecnica/auditoria/cierre LIM-B antes de considerar cualquier integracion con
liberacion o inventario.
