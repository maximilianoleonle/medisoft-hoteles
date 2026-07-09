# Nomina Core - Fix de los 2 criticos de doble pago (cierre)

Fecha: 2026-07-08
Origen: auditoria de testing 2026-07-08 (memoria nomina-hallazgos-test-2026-07-08).
Ambos criticos verificados contra el codigo actual y CORREGIDOS con prueba de
regresion (rollback) que los demuestra.

## Critico #1 - Doble disponibilidad de saldo (sobrepago)

Sintoma: al APROBAR un periodo v2 se emitia un credito NOMV2 por el NETO
completo. Pero el neto ya incluye las lineas de ledger v1 (bono/comision de
`trabajador_pagos` con fecha en el rango), que siguen `activo`.
`TrabajadorPagoCajaService::saldoLaboralDisponible()` suma TODO lo activo
a_favor -> el bono se contaba dos veces (una en su fila, otra dentro del neto).
El riel LIBRE (`TrabajadorController::registrarPagoCajaAction`) paga contra ese
saldo global inflado -> sobrepago. (El riel de snapshot NO se afecta: topa en
min(pendiente_snapshot, saldo_vivo).)

Fix (`NominaCierreService::aprobar`): el credito NOMV2 ahora cubre SOLO la
porcion del neto que el ledger v1 aun no aporta:
`credito = neto - ledger_favorable_del_periodo`, donde ledger_favorable =
SUM(a_favor - en_contra) de `trabajador_pagos` activos (no NOMV2) con fecha en
el rango. Si el ledger ya cubre el neto, no se emite credito. Resultado:
saldo pagable == neto exacto, sin exceso. Las incidencias (nomina_incidencias)
NO son ledger v1, asi que un periodo cuyo bono viene de incidencia mantiene el
credito completo (verificado en fase 3: credito 3500 intacto).

## Critico #2 - Doble pago tras reabrir/anular

Sintoma: los guards de reabrir/anular solo contaban
`trabajador_pagos_caja WHERE nomina_periodo_id = ?`. El riel libre inserta
`nomina_periodo_id = NULL` (datosPagoCajaLaboral no lo manda) -> sus pagos eran
invisibles al guard. Se podia reabrir un periodo ya cobrado por el riel libre,
anular su credito y re-cobrar.

Fix (`NominaCierreService`): nuevo helper `contarPagosVigentesDelPeriodo()` que
cuenta pagos 'pagado' del periodo por AMBAS vias: los trazados
(`nomina_periodo_id = ?`) y los del riel libre (`nomina_periodo_id IS NULL`) a
trabajadores del periodo dentro del rango de fechas. anular() y reabrir() usan
ese conteo. Un pago libre en el rango ahora bloquea anular/reabrir.

## Archivos

- `src/app/services/NominaCierreService.php` (aprobar: credito = neto - ledger;
  anular/reabrir: guard ampliado + helper contarPagosVigentesDelPeriodo).
- `src/tools/saas/probar_nomina_doble_pago.php` (NUEVA prueba de regresion,
  rollback, 4 asserts).

## Pruebas

- `probar_nomina_doble_pago.php`: 4/4 PASS. Demuestra: credito NOMV2 = neto-bono
  (0 cuando el bono ES todo el neto), saldo pagable == neto (500, no 1000), y
  anular bloqueado por pago del riel libre con periodo NULL.
- No-regresion: fase 3 24/24, fase 4 20/20, integracion fiscal PASS,
  preflight 49 OK / 0 ERROR.

## Pendientes de la misma auditoria (NO abordados aqui)

- ALTOS de concurrencia (TOCTOU): chequeo de solape en cerrar fuera de lock;
  UNIQUE solo cubre rango identico (rangos solapados-distintos duplican).
- ALTOS fiscales del MODO LEGAL (aun no activado): gravable_isr default 0 no lo
  setea la siembra; subsidio sin sufijo de periodicidad; tabla ISR vacia no
  bloquea; salario a fecha FIN sin prorrateo; horas extra a tarifa plana;
  anticipos/prestamos restan saldo TOTAL sin abono.
- Seguridad: CSV injection en export al contador; apiPeriodoVer con SELECT *.

Estos requieren decision del owner (sobre todo los fiscales, ligados a capturar
las reglas DOF 2026) antes de tocarlos.
