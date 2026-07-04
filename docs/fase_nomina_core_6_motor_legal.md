# Nomina Core - Fase 6: motor legal/fiscal MX (cierre)

Fecha: 2026-07-04

## Alcance implementado

- `NominaFiscalService`: ISR retenido por tabla de tramos (limite inferior,
  cuota fija, % excedente) segun la PERIODICIDAD del grupo; subsidio al empleo
  por tabla; IMSS obrero por cuotas JSON sobre SBC (salario diario x factor de
  integracion, tope 25 UMA, base sbc o sbc_excedente_3uma). TODO se resuelve
  desde `nomina_reglas_legales` por fecha: CERO valores legales en codigo.
- Politica de fallas explicita: sin tabla ISR de la periodicidad, el periodo
  legal queda BLOQUEADO (no cierra); sin cuotas IMSS o factor, la retencion se
  omite con alerta visible. Nunca calcula en silencio con datos incompletos.
- Integracion en `NominaCalculoService::preview` SOLO cuando el negocio tiene
  `nomina.modo = legal`: base gravable = sueldo (siempre grava) + incidencias
  cuyo concepto tiene `gravable_isr = 1`; lineas del ledger v1 NO gravan (con
  alerta). Las lineas fiscales entran como deducciones origen 'fiscal'.
- El cierre congela en `reglas_snapshot_json` el modo y las reglas legales
  APLICADAS (id, tipo, ejercicio, vigencia, valor, fuente): una nomina cerrada
  jamas cambia aunque el catalogo se actualice despues.
- Subsidio > ISR: retencion 0 con alerta (subsidio entregable no automatizado
  en v1; decision documentada).
- Frontera respetada: es calculo interno auditable; sigue sin existir CFDI,
  timbrado, dispersion ni pago masivo (preflight de frontera sin errores de
  nomina).

## Archivos

Nuevos: `migrations/20260704_006_nomina_fiscal.sql` (ENUM origen += fiscal),
`src/app/services/NominaFiscalService.php`,
`src/tools/saas/probar_nomina_fiscal.php`,
`src/tools/saas/probar_nomina_fiscal_integracion.php`.
Modificados: `NominaCalculoService` (contexto fiscal, base gravable, salario
diario), `NominaCierreService` (reglas legales al snapshot),
`preflight_nomina_core.php` (checks Fase 6).

## Pruebas y resultado

- `php -l` sin errores; migracion aplicada y registrada.
- Prueba CLI del motor (9/9 PASS, rollback): resolucion completa no bloquea;
  sin tabla de la periodicidad SI bloquea; ISR tramo 2 (base 3000 -> 220.00);
  ISR tramo abierto (base 10000 -> 1420.00); subsidio > ISR -> retencion 0 con
  alerta; IMSS 2% SBC 15 dias = 62.71; IMSS con tope 25 UMA = 1296.00.
- Prueba CLI de integracion (9/9 PASS, rollback): negocio en modo legal +
  tabla de prueba -> preview con linea fiscal ISR 220.00 sobre sueldo
  quincenal 3000, neto 2780.00, reglas congelables presentes y alerta de IMSS
  omitido. Nada persistio.
- Preflight: checks nuevos (origen fiscal soportado; negocios en modo legal
  sin tabla ISR resoluble -> aviso critico).

## Advertencia honesta (para el owner)

La MECANICA del calculo esta probada con tablas sinteticas. La EXACTITUD
legal depende de capturar las tablas reales del ejercicio (DOF) en
/admin/saas/nomina/reglas: isr_tabla_semanal/quincenal/mensual,
subsidio_empleo_tabla, imss_cuotas_obrero, factor_integracion_minimo,
uma_diaria. Recomendado validar el primer periodo legal contra el calculo
del contador antes de confiar operativamente.

## Siguiente fase

Fase 7 (hibrido): paquete de exportacion para contador externo (CSV por
periodo con lineas congeladas), gated por bloque exportaciones.
