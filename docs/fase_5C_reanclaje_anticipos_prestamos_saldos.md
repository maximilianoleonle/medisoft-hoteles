# Fase 5C - Reanclaje de anticipos, prestamos y saldos laborales

## Estado

`FASE_5C_ANTICIPOS_PRESTAMOS_SALDOS_YA_CUBIERTA_POR_NP_C_C`

## Motivo

El roadmap nuevo nombra `5C - Anticipos/prestamos/saldos laborales`, pero el sistema ya
tiene esa fundacion implementada dentro del bloque NP-C-C.

No se debe crear otra capa de anticipos/prestamos ni otra fuente de saldo.

## Cobertura existente

- Anticipos: `trabajador_anticipos`.
- Prestamos: `trabajador_prestamos`.
- Saldos informativos:
  - `anticipos_saldo`
  - `prestamos_saldo`
  - `saldo_informativo`
- Rutas existentes:
  - `POST /trabajadores/{id}/anticipos`
  - `POST /trabajadores/{id}/prestamos`
- Modelo:
  - `Trabajador::registrarAnticipoLaboralParaHotel()`
  - `Trabajador::registrarPrestamoLaboralParaHotel()`
  - `Trabajador::anticiposPorTrabajador()`
  - `Trabajador::prestamosPorTrabajador()`
- Vista: ficha y reporte de trabajador.

## Guardrails existentes

- Requiere sesion, hotel actual, permiso existente y CSRF.
- Valida trabajador activo del hotel actual.
- `saldo_pendiente` se inicializa desde el monto y no viene del formulario.
- Estado inicial controlado:
  - anticipo: `pendiente`
  - prestamo: `vigente`
- No hay `UPDATE` ni `DELETE` de saldos en esta fase.
- No hay liquidacion ni descuento automatico.

## Fuera de alcance

- Pagos laborales reales.
- Abonos.
- Liquidaciones.
- Descuentos automaticos de nomina.
- Caja.
- Categoria Nomina.
- `/api/sync`.

## QA manual diferida

Cuando exista trabajador activo:

1. Registrar anticipo.
2. Confirmar que aparece en ledger.
3. Registrar prestamo.
4. Confirmar que aparece en ledger.
5. Confirmar que `saldo_pendiente` inicia igual al monto.
6. Confirmar que `saldo_informativo` refleja saldos pendientes.
7. Confirmar que no se crean Caja, pagos reales ni abonos.
8. Ejecutar `preflight_personal_ledger.php`.

## Decision

Marcar 5C como cubierta por NP-C-C. Cualquier fase futura de pagos, abonos,
liquidaciones o descuentos debe abrir un contrato separado y seguir sin Caja automatica
salvo autorizacion explicita.

Siguiente candidato seguro: 5D Pagos laborales sin Caja automatica, iniciando con
contrato y diagnostico porque todavia no debe tocar Caja ni liquidar saldos.
