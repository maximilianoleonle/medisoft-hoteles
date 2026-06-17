# Fase 8A-0 - Contrato dashboard operativo con KPIs nuevos

## Estado

`CONTRATO_8A_DASHBOARD_KPIS_READONLY_COMPLETADO`

## Objetivo

Definir una evolucion segura del tablero operativo existente para agregar KPIs nuevos
sin crear acciones operativas, sin escribir datos y sin tocar Caja.

Esta subfase es solo contrato y diagnostico. No implementa codigo.

## Estado previo

Ya existe un tablero operativo read-only:

- Contrato OP-0.
- Implementacion OP-A.
- Cierre OP-F.
- Ruta: `GET /operacion/diaria`.
- Modelo: `OperacionDiaria`.
- Controlador: `OperacionController`.
- Vista: `operacion/diaria`.
- Preflight: `preflight_operacion_diaria.php`.

Por tanto, 8A no debe crear otro dashboard paralelo. Debe extender el tablero existente
o documentar KPIs futuros reutilizables.

## KPIs candidatos

KPIs seguros, read-only y derivados:

- CxC estimada pendiente desde 7A-A.
- Reservaciones con saldo estimado excedente.
- Tareas activas por prioridad.
- Tareas vencidas.
- Mantenimientos programados vencidos o de hoy.
- Documentos recientes por entidad critica.
- Trabajadores activos con tareas asignadas.
- Habitaciones con tareas activas.

## Fuera de alcance

- Acciones desde dashboard.
- Cambiar estado de habitaciones.
- Crear, asignar o completar tareas.
- Cobros, abonos o pagos.
- Caja y cortes.
- Nomina.
- Migraciones.
- `/api/sync`, service worker, IndexedDB u offline.

## Semaforo de riesgo

- Verde: tarjetas read-only calculadas desde tablas ya existentes y filtradas por
  `hotel_id`.
- Amarillo: KPIs derivados de saldos financieros; deben marcarse como estimados.
- Rojo: botones de accion, POST, Caja, cambios de estados o pagos.

## Reglas futuras obligatorias

- Todo KPI debe filtrar por `hotel_id`.
- Todo KPI financiero debe etiquetarse como estimado si viene de fuentes derivadas.
- La vista no debe contener formularios POST.
- No debe mostrar `storage_path` documental.
- No debe crear rutas API nuevas si el KPI puede renderizarse server-side.
- No debe tocar `/api/sync`.

## Subfases sugeridas

### 8A-A KPIs CxC/tareas en tablero operativo

- Extender `OperacionDiaria` con KPIs read-only.
- Agregar tarjetas al tablero existente.
- Reutilizar el contrato 7A-A para CxC estimada.
- No escribir datos.

### 8A-B Preflight dashboard KPIs

- Validar que los KPIs no agregan POST ni Caja.
- Validar ausencia de `INSERT`, `UPDATE`, `DELETE`.
- Validar scoping por `hotel_id`.

### 8A-F Cierre tecnico

- QA critica/funcional/visual/regresion.
- Rollback.
- Fuentes de verdad.

## Definition of Done futura

- `php -l`.
- `preflight_operacion_diaria.php`.
- Health checker.
- Preflight especifico si se crea.
- HTTP sin sesion bloquea.
- SQL read-only de control.
- `git diff --check`.
- QA manual diferida o completada segun disponibilidad del usuario.

## Rollback

Como 8A-0 solo documenta contrato, rollback = revertir el commit documental.

Para 8A-A, rollback esperado:

1. Revertir cambios en modelo/vista/preflight.
2. No tocar datos.
3. No tocar Caja ni `/api/sync`.

## Siguiente accion segura

Implementar 8A-A como extension read-only pequena sobre `/operacion/diaria`, priorizando
KPI CxC estimada y tareas vencidas/urgentes, sin acciones.
