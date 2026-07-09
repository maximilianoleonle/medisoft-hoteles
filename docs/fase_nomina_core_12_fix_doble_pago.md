# Nomina Core - Fix de los 2 criticos de doble pago (cierre)

Fecha: 2026-07-08 (consolidado 2026-07-09 en el merge de las dos PCs)
Origen: auditoria de testing 2026-07-08 (memoria nomina-hallazgos-test-2026-07-08).
Ambos criticos verificados contra el codigo actual y CORREGIDOS con prueba de
regresion (rollback) que los demuestra.

NOTA de consolidacion: los mismos 2 criticos se corrigieron en paralelo en las
dos PCs. En el merge quedo la implementacion mas completa (commits b275390d y
1bc3d003): invariante de credito = bruto - ledger absorbido y candados dentro
de la transaccion. Lo descrito abajo refleja esa version final; la prueba de
regresion se adapto a ella (4/4 PASS).

## Critico #1 - Doble disponibilidad de saldo (sobrepago)

Sintoma: al APROBAR un periodo v2 se emitia un credito NOMV2 por el NETO
completo. Pero el neto ya incluye las lineas de ledger v1 (bono/comision de
`trabajador_pagos` con fecha en el rango), que siguen `activo`.
`TrabajadorPagoCajaService::saldoLaboralDisponible()` suma TODO lo activo
a_favor -> el bono se contaba dos veces (una en su fila, otra dentro del neto).
El riel LIBRE (`TrabajadorController::registrarPagoCajaAction`) paga contra ese
saldo global inflado -> sobrepago. (El riel de snapshot NO se afecta: topa en
min(pendiente_snapshot, saldo_vivo).)

Fix (`NominaCierreService::aprobar`): el credito NOMV2 es el BRUTO del snapshot
MENOS el neto de las lineas ledger v1 absorbidas por el periodo:
`credito = bruto_periodo - SUM(percepcion - deduccion)` de
`nomina_periodo_conceptos` con `origen = 'ledger'` (atribucion exacta por
snapshot, no por rango de fechas). Si el ledger absorbido supera al bruto se
emite el ajuste `en_contra`. Resultado: saldo pagable == bruto exacto, sin
exceso; los anticipos/prestamos (ya restados del neto sugerido) los descuenta
el propio riel de pago. Las incidencias (nomina_incidencias) NO son ledger v1,
asi que un periodo cuyo bono viene de incidencia mantiene el credito completo.

## Critico #2 - Doble pago tras reabrir/anular

Sintoma: los guards de reabrir/anular solo contaban
`trabajador_pagos_caja WHERE nomina_periodo_id = ?`. El riel libre inserta
`nomina_periodo_id = NULL` (datosPagoCajaLaboral no lo manda) -> sus pagos eran
invisibles al guard. Se podia reabrir un periodo ya cobrado por el riel libre,
anular su credito y re-cobrar.

Fix (`NominaCierreService`): dos candados DENTRO de la transaccion de
anular()/reabrir():
- `contarPagosSnapshotVigentes()`: pagos trazados (`nomina_periodo_id = ?`)
  con FOR UPDATE, serializado contra un pago concurrente.
- `verificarCreditosNoConsumidos()`: los pagos del riel libre no viajan con
  nomina_periodo_id, asi que el consumo se verifica con el invariante del pool
  laboral por trabajador: `(conceptos activos netos - pagos de Caja vigentes)
  >= credito NOMV2 a retirar`. Si no alcanza, un pago ya salio respaldado por
  el credito y anular/reabrir se bloquea hasta revertirlo. Bloquea las mismas
  filas que el riel de pago (FOR UPDATE) para serializar contra pagos en vuelo.

## Archivos

- `src/app/services/NominaCierreService.php` (aprobar: credito = bruto - ledger
  absorbido; anular/reabrir: candados en transaccion
  contarPagosSnapshotVigentes + verificarCreditosNoConsumidos).
- `src/tools/saas/probar_nomina_doble_pago.php` (prueba de regresion,
  rollback, 4 asserts).

## Pruebas

- `probar_nomina_doble_pago.php`: 4/4 PASS. Demuestra: credito NOMV2 =
  bruto - bono absorbido (2000 con sueldo 2000 + bono 500), saldo pagable ==
  bruto (2500, no 3000), y anular bloqueado cuando un pago del riel libre
  (periodo NULL) ya consumio el credito.
- No-regresion: fase 3 24/24, fase 4 20/20, integracion fiscal PASS,
  preflight 49 OK / 0 ERROR.

## Pendientes de la misma auditoria (NO abordados aqui)

- RESUELTO en el merge (1bc3d003): TOCTOU de solape en cerrar - ahora
  serializa por grupo (FOR UPDATE sobre nomina_grupos) y re-valida el solape
  dentro de la transaccion.
- ALTOS fiscales del MODO LEGAL (aun no activado): gravable_isr default 0 no lo
  setea la siembra; subsidio sin sufijo de periodicidad; tabla ISR vacia no
  bloquea; salario a fecha FIN sin prorrateo; horas extra a tarifa plana;
  anticipos/prestamos restan saldo TOTAL sin abono.
- Seguridad: CSV injection en export al contador; apiPeriodoVer con SELECT *.

Estos requieren decision del owner (sobre todo los fiscales, ligados a capturar
las reglas DOF 2026) antes de tocarlos.
