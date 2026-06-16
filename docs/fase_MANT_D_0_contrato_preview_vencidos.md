# Fase MANT-D-0 - Contrato preview read-only de mantenimientos vencidos

Estado: `CONTRATO_MANT_D_0_PREVIEW_VENCIDOS_COMPLETADO`

## Objetivo

Definir una fase segura para observar mantenimientos programados que ya deberian iniciar,
sin activarlos automaticamente, sin cambiar habitaciones y sin conectar cron/dashboard.

La fase futura MANT-D-A debe funcionar como preview read-only de candidatos a activacion,
para que el usuario pueda revisar riesgos antes de autorizar cualquier accion operativa.

## Diagnostico

- `Mantenimiento::activarMantenimientosPendientes()` ya existe y escribe:
  - cambia mantenimientos `programado` a `en_proceso`;
  - cambia habitaciones `disponible` a `mantenimiento`.
- Ese metodo no debe conectarse automaticamente sin contrato posterior.
- MANT-C-A ya endurecio programacion/cancelacion y detecta solapes programados.
- La fuente de verdad sigue siendo `mantenimientos_habitaciones`.
- Reservaciones ya consideran mantenimientos programados/en proceso al validar
  disponibilidad.

## Alcance permitido para MANT-D-A

- Crear una ruta GET/read-only protegida, por ejemplo
  `/reportes/mantenimiento-programado`.
- Mostrar mantenimientos programados:
  - vencidos;
  - proximos;
  - con habitacion no disponible;
  - con posible conflicto de reservacion.
- Mostrar motivo de bloqueo/advertencia por registro.
- Enlazar a ficha de habitacion.
- Filtrar por `hotel_id`.
- Agregar health/preflight read-only.
- No agregar botones POST ni formularios de activacion.

## Fuera de alcance

- No activar mantenimientos vencidos.
- No llamar `activarMantenimientosPendientes()`.
- No cambiar `habitaciones.estado`.
- No modificar reservaciones.
- No crear tareas operativas automaticamente.
- No crear cron, jobs, listeners, triggers ni automatizaciones.
- No tocar Caja, pagos, abonos, nomina, offline, PWA ni `/api/sync`.
- No crear migraciones.

## Fuentes de verdad

- Mantenimiento programado: `mantenimientos_habitaciones`.
- Habitaciones: `habitaciones`.
- Reservaciones: `reservaciones` y `reservacion_habitaciones`.
- Hotel actual: contexto de sesion / `hotel_id`.

## Semaforo de riesgo

- Verde: GET/read-only, conteos, estados vacios, links a fichas.
- Amarillo: joins contra reservaciones y habitaciones; todos deben incluir `hotel_id`.
- Rojo: cualquier POST, cron, llamada a `activarMantenimientosPendientes()`, cambio de
  estado de habitaciones o disponibilidad automatica.

## Definition of Done de MANT-D-A

- Ruta GET protegida por sesion, modulo reportes y permisos existentes.
- Modelo o metodo read-only centraliza consultas con `hotel_id`.
- Vista no contiene formularios POST ni botones operativos.
- Estado vacio claro.
- HTTP sin sesion redirige o bloquea.
- Health/preflight detecta que la superficie sigue read-only.
- `php -l` pasa en archivos PHP tocados.
- `git diff --check` sin errores.

## Rollback previsto

MANT-D-0 solo documenta contrato; rollback documental:

- Revertir el commit `docs(phase-mant): define overdue maintenance preview contract`.
- DB: no aplica.
- Codigo: no aplica.

Para una futura MANT-D-A, el rollback esperado seria retirar ruta GET, vista, metodo
read-only y checks, sin tocar datos reales.

