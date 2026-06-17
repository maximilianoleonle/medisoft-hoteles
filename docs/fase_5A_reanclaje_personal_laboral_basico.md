# Fase 5A - Reanclaje Personal laboral basico

Estado: `FASE_5A_PERSONAL_LABORAL_BASICO_YA_CUBIERTA_POR_NP`.

## Objetivo

Reanclar el roadmap nuevo del usuario con la nomenclatura historica del repositorio.
La Fase 5A "Personal laboral basico" no debe duplicarse: ya existe como bloque NP
implementado y cerrado tecnicamente con QA manual diferida.

## Equivalencia tecnica

- 5A Personal laboral basico = bloque NP base ya implementado.
- NP-0: contrato y diagnostico de Personal independiente.
- NP-A: migracion base y UI read-first.
- NP-B: CRUD basico de trabajadores.
- NP-C: ledger laboral informativo, conceptos, anticipos, prestamos y asistencia manual.
- NP-D: documentos laborales con Centro Documental moderno.
- NP-E: cierre de Personal operativo base.
- NP-F: reporte read-only de Personal.

## Alcance ya disponible

- Trabajadores independientes de usuarios del sistema.
- Listado, ficha, alta, edicion, baja logica y reactivacion.
- Ledger laboral informativo sin Caja.
- Conceptos laborales manuales sin pago real.
- Anticipos y prestamos informativos sin abonos ni Caja.
- Asistencia manual basica.
- Documentos laborales contextuales mediante Centro Documental.
- Reporte read-only de Personal.

## Limites vigentes

- No hay nomina automatica.
- No hay pagos reales.
- No hay abonos/liquidaciones.
- No hay movimientos de Caja.
- No hay categoria Nomina en Caja.
- No se fusionan usuarios con trabajadores.
- No se toca `/api/sync`.

## Riesgos

- Riesgo funcional: QA manual real sigue diferida en varios flujos porque la base local
  historicamente tenia pocos o ningun trabajador activo.
- Riesgo financiero: cualquier salida real de dinero debe ir en bloque separado y
  autorizado.
- Riesgo de duplicacion: crear otro modulo 5A desde cero duplicaria tablas y rutas ya
  existentes.

## Decision

No reconstruir 5A. Usar el modulo NP existente como fuente tecnica de Personal laboral
basico y continuar el roadmap hacia 6B Integracion tareas + habitaciones.

## Siguiente fase segura

Fase 6B-0: contrato y diagnostico de Integracion tareas + habitaciones.
