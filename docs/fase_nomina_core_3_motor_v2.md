# Nomina Core - Fase 3: motor operativo v2 (cierre)

Fecha: 2026-07-04

## Alcance implementado

- `nomina_incidencias`: incidencias normalizadas por concepto del catalogo
  (origen manual/adaptador/api, ciclo pendiente/aprobada/rechazada, politicas
  del negocio aplicadas en captura: horas extra y descuentos manuales
  configurables). Una incidencia congelada en un periodo vigente NO puede
  modificarse (hay que anular el periodo).
- `nomina_periodo_conceptos`: lineas congeladas del snapshot (percepcion/
  deduccion, clasificacion, origen salario/incidencia/ledger, referencia
  trazable SAL-/INC-/LED-). Base de recibos (Fase 4) y fiscal (Fase 6).
- Motor de calculo `NominaCalculoService` (PDO ESTRICTO: un SELECT roto lanza,
  jamas nomina en ceros): sueldo del periodo por salario vigente y esquema
  (match exacto = salario completo; diario = x dias; por_hora/por_evento = 0
  con alerta, sale de incidencias; distinto = prorrateo diario), incidencias
  aprobadas del rango, ledger v1 con solape (compatibilidad, excluye NOMV2-*),
  deducciones informativas (saldos de anticipos/prestamos). Alertas por
  trabajador y globales; SOLAPE POR GRUPO ES BLOQUEANTE (corrige el bug v1 de
  doble conteo). Redondeo configurable (centavos/pesos).
- Cierre `NominaCierreService`: snapshot en las MISMAS tablas de la pre-nomina
  (motor='v2', grupo_nomina_id, reglas de config congeladas en
  reglas_snapshot_json) + lineas + CREDITO DEL NETO en el ledger laboral
  (referencia NOMV2-{periodo}-{trabajador}) para que el riel de pagos por Caja
  existente funcione SIN tocar Caja. Aprobacion (2o paso) y anulacion v2
  (motivo obligatorio, bloqueada si hay pagos vigentes, revierte creditos
  NOMV2 en la misma transaccion).
- Unicidad correcta de periodos: la UNIQUE vieja (hotel, inicio, fin) impedia
  dos grupos con el mismo rango; la nueva (hotel, grupo, inicio, fin,
  anulacion_uk) permite grupos simultaneos y re-cerrar un rango ANULADO
  (correccion = anular + re-cerrar), manteniendo un solo periodo VIGENTE por
  grupo/rango a nivel BD.
- Pantallas: `/nomina/incidencias` (captura + filtros + rechazo con confirm),
  `/nomina/periodos` (sugerencia de rango por periodicidad e historial,
  historial v1+v2 unificado), `/nomina/periodos/preview` (desglose por
  trabajador con lineas y alertas, boton cerrar deshabilitado si bloqueado),
  `/nomina/periodos/{id}` (snapshot con lineas congeladas, aprobar/anular).
- Permisos: capturar=nomina.incidencias, preview=nomina.calcular,
  cerrar=nomina.cerrar, aprobar=nomina.aprobar, anular=nomina.reabrir.

## Archivos

Nuevos: `migrations/20260704_003_nomina_motor_v2.sql`, servicios
`NominaIncidenciaService/NominaCalculoService/NominaCierreService`, vistas
`nomina/incidencias|periodos|periodo_preview|periodo_ver.php`.
Modificados: `NominaController` (+9 acciones), `routes.php` (+9 rutas),
`navegacion.php` (+2), `nomina/index.php`, `preflight_nomina_core.php`.

## Pruebas y resultado

- `php -l`: 12 archivos sin errores. Migracion aplicada y registrada.
- Preflight: 38 OK / 1 WARNING (DB_STRICT_ERRORS global; el motor v2 ya es
  estricto por diseno) / 0 ERROR. Checks nuevos: tablas v2, columnas de
  periodos, unicidad vigente, cero creditos NOMV2 huerfanos, cero periodos v2
  sin lineas, cero anulados sin liberar rango.
- Test funcional end-to-end (sesion real, 24/24 PASS): incidencia bono ->
  preview -> cierre (bruto 3500 = salario quincenal 3000 + bono 500; 2 lineas;
  credito ledger activo) -> cierre duplicado bloqueado por solape -> aprobar ->
  anular sin motivo rechazado -> anular con motivo (credito ledger anulado +
  evento) -> RE-CIERRE del mismo rango permitido -> permisos: administrador
  puede preview pero NO cerrar (denegado sin insertar).
- Defecto detectado y corregido EN FASE: la primera version de la UNIQUE por
  grupo incluia periodos anulados e impedia re-cerrar un rango corregido; se
  reemplazo por (…, anulacion_uk) con backfill.
- No-regresion frontera: sin errores de nomina (persiste solo el preexistente
  de /api/sync, ajeno a este trabajo).

## Riesgos/decisiones documentadas

1. El credito NOMV2 en `trabajador_pagos` es la integracion deliberada con el
   riel de pagos v1: si un hotel mezcla flujo v1 (preview de pre-nomina) y v2,
   el preview v1 contaria esos creditos (el v2 los excluye). Recomendacion
   operativa: un hotel usa UN flujo. Se documenta para el owner.
2. La anulacion v1 (desde /trabajadores) de un periodo v2 no revertiria los
   creditos NOMV2; la UI v2 es el camino soportado (el preflight detecta
   huerfanos si ocurriera).
3. Pagos de periodos v2: por el riel existente desde la vista v1 del periodo
   (boton en el detalle v2 cuando esta aprobado). Recibos internos v2: Fase 4.

## Siguiente fase

Fase 4: recibos internos v2 (PDF por linea congelada), historial/auditoria
consolidada y endurecimiento de reapertura (comparacion antes/despues).
