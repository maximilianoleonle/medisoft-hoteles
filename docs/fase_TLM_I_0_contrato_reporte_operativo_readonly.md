# Fase TLM-I-0 - Contrato reporte operativo read-only

Estado: `CONTRATO_TLM_I_REPORTE_OPERATIVO_READONLY_COMPLETADO`

Fecha: 2026-06-16

## Objetivo

Definir un reporte operativo de Tareas, Limpieza y Mantenimiento en modo solo lectura,
derivado de `tareas_operativas` y `tarea_eventos`, sin crear nuevas tareas, sin cambiar
estados, sin modificar habitaciones, sin tocar mantenimiento historico y sin integrar
Caja, pagos, abonos, nomina ni `/api/sync`.

Este contrato no implementa rutas, vistas, modelos ni migraciones. Solo delimita el
alcance seguro para una futura fase TLM-I-A.

## Fuentes previstas

- `tareas_operativas`: fuente principal de tareas operativas.
- `tarea_eventos`: fuente de historial tecnico de tarea.
- `habitaciones`: contexto de habitacion, solo lectura.
- `trabajadores`: contexto de trabajador asignado, solo lectura.
- `mantenimientos_habitaciones`: contexto historico de mantenimiento, solo lectura.

## Datos esperados del reporte

- Totales por estado: pendiente, asignada, en proceso, completada y cancelada.
- Totales por categoria: limpieza, mantenimiento y general.
- Totales por prioridad.
- Tareas vencidas o con fecha limite vencida.
- Tareas proximas a vencer.
- Tareas sin asignar.
- Tareas activas por trabajador.
- Tareas activas por habitacion.
- Ultimas tareas y ultimos eventos.

## Reglas obligatorias

- Solo GET/read-only en la primera implementacion.
- Filtro obligatorio por `hotel_id` del contexto de sesion.
- No aceptar `hotel_id` desde query o formulario.
- No crear tareas.
- No asignar trabajadores.
- No iniciar, completar ni cancelar tareas desde el reporte.
- No cambiar `habitaciones.estado`.
- No crear ni modificar `mantenimientos_habitaciones`.
- No crear asistencia laboral, nomina, pagos reales ni abonos.
- No crear movimientos de Caja.
- No tocar `/api/sync`.

## Permisos y navegacion

- Reutilizar guardas existentes de TLM: sesion, contexto hotelero, modulo `habitaciones`
  y permiso `habitaciones.view`.
- Si se agrega navegacion, debe ser enlace GET dentro del modulo Tareas.
- Si el usuario no tiene sesion o contexto de hotel, debe bloquear/redirigir segun patron
  existente.

## Definition of Done futura TLM-I-A

- Ruta GET protegida.
- Controlador read-only sin POST.
- Modelo/servicio con consultas scoped por `hotel_id`.
- Vista con estados vacios claros.
- Sin formularios POST ni botones de accion operativa.
- `php -l` en archivos PHP tocados.
- `preflight_tareas_operativas.php` con `ERROR: 0`.
- `health_check_fase_1a.php` con `ERROR: 0`.
- SQL read-only confirmando que tareas/eventos/Caja no fueron modificados.
- HTTP sin sesion bloquea/redirige la ruta.
- `git diff --check`.

## Riesgos

- Convertir el reporte en pantalla operativa con acciones de asignar/iniciar/completar.
- Confundir tareas operativas con cambios directos de disponibilidad de habitacion.
- Mezclar tareas con asistencia laboral o nomina.
- Crear dependencia con Caja o reportes financieros.

## Rollback

- Revertir este documento si el contrato cambia.
- Una futura implementacion TLM-I-A debe tener rollback propio.
- No limpiar tareas/eventos desde este contrato.

## Siguiente cola exacta recomendada

`[COLA_TLM_I_A_REPORTE_OPERATIVO_READ_ONLY]`
