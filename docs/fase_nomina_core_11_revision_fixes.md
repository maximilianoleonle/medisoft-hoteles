# Nomina Core - Revision adversarial y correcciones (cierre)

Fecha: 2026-07-04

## Origen

La revision multi-agente del modulo (3 dimensiones + verificacion adversarial)
quedo interrumpida por limite de sesion. Los hallazgos levantados se
verificaron manualmente contra el codigo en esta fase: 2 confirmados y
corregidos; la tercera dimension (regresiones) se cubrio con verificaciones
dirigidas.

## Fix 1 - El credito NOMV2 se emitia al CERRAR (bypass de aprobacion)

CONFIRMADO: el cierre insertaba el credito del neto en trabajador_pagos con
estado activo; el riel de pago LIBRE de Caja (que paga contra saldo vivo del
ledger) permitia pagar un periodo cerrado SIN aprobar, brincandose el permiso
nomina.aprobar y el paso de revision.

Correccion en `NominaCierreService`:
- cerrar(): YA NO emite creditos (snapshot y lineas solamente).
- aprobar(): emite los creditos desde los detalles congelados (neto > 0),
  registrando cuantos en el evento. Desde este momento (y solo desde este)
  el periodo es pagable por cualquier riel.
- reabrir(): ANULA los creditos (sin aprobacion no hay saldo pagable),
  ademas de cancelar recibos.
- anular(): sin cambios (ya anulaba creditos; si se anula desde cerrado no
  hay creditos y el UPDATE afecta 0 filas).
- Preflight endurecido: el invariante paso de "sin creditos de periodos
  anulados" a "creditos NOMV2 activos SOLO en periodos APROBADOS" (ERROR si
  se viola).

## Fix 2 - Fila de ledger v1 contada en DOS periodos consecutivos

CONFIRMADO: el filtro de inclusion del ledger usaba SOLAPE de rango
(COALESCE(periodo_fin, fecha) >= inicio AND ...): una fila v1 cuyo rango
abarcara dos periodos v2 consecutivos se sumaba COMPLETA en ambos, inflando
bruto y creditos NOMV2.

Correccion en `NominaCalculoService`: atribucion por FECHA de la fila
(fecha BETWEEN inicio AND fin). Una fila cuenta EXACTAMENTE UNA VEZ entre
periodos consecutivos del grupo. Semantica documentada: la fila se atribuye
al periodo donde cae su fecha de captura.

## Dimension 3 - Regresiones/convenciones (verificacion dirigida)

- Proteccion v1 contra periodos duplicados: INTACTA a nivel servicio
  (Trabajador.php ~1443: SELECT ... FOR UPDATE + excepcion). La UNIQUE vieja
  retirada por 20260704_003 solo era el respaldo de BD; ventana de carrera
  residual estrecha, documentada desde la Fase 3.
- Archivos tocados fuera del modulo: SOLO los 5 aditivos esperados
  (registry de config, sidebar, navegacion, permisos, routes). CERO cambios
  en service worker, PWA, /api/sync, Caja, ApiController o TrabajadorController.
- XSS: los 10 candidatos del scan son arrays internos hardcodeados o ENUMs
  de BD; todo texto capturado por usuarios pasa por htmlspecialchars.

## Pruebas tras las correcciones

- Fase 3: 24/24 PASS (nuevo invariante verificado: SIN credito al cerrar,
  credito 3500.00 activo AL APROBAR, anulado al anular).
- Fase 4: 20/20 PASS (reapertura ahora tambien anula creditos; re-aprobar
  re-emite; recibos 1 vigente / 1 cancelado).
- Integracion fiscal: PASS (rollback limpio).
- Preflight: 49 OK / 1 WARNING (DB_STRICT_ERRORS, deliberado) / 0 ERROR.
- Hallazgo de QA (no del producto): los scripts de prueba capturaban el rol
  baseline del estado actual; una corrida interrumpida dejaba al usuario QA
  como gerente y la seccion "administrador" corria con permisos de mas. Los
  4 scripts ahora resuelven el rol administrador POR CLAVE. El "bypass" que
  parecia real era este artefacto: el modelo de permisos esta intacto.

## Estado

Los 3 ejes de la revision quedan cerrados. Pendientes previos sin cambio:
captura de reglas DOF 2026, validacion del primer periodo legal con contador,
y el preexistente de /api/sync (ajeno a nomina).
