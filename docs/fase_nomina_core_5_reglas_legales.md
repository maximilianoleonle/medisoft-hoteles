# Nomina Core - Fase 5: catalogos legales versionados (cierre)

Fecha: 2026-07-04

## Alcance implementado

- `nomina_reglas_legales`: catalogo GLOBAL por pais (sin hotel_id) con
  vigencias por fecha, valor escalar DECIMAL(12,4) o `valores_json` (tablas de
  tramos), ejercicio fiscal, FUENTE OFICIAL OBLIGATORIA, estado y autoria.
  UNIQUE (pais, tipo_regla, vigente_desde). Version nueva cierra la vigencia
  anterior automaticamente (desde - 1 dia); nada se borra ni se pisa.
- `nomina_reglas_legales_eventos`: historial append-only (creada/actualizada/
  desactivada/reactivada con datos antes/despues y usuario).
- `NominaReglasLegalesService`: resolucion `vigente(pais, tipo, fecha)` y
  `vigentes()` multiple (lo que consumira el motor fiscal de Fase 6 y
  congelara en el snapshot), escritura validada (snake_case, fecha posterior,
  JSON valido, fuente obligatoria), alternar estado, historial. PDO estricto.
- Panel SaaS `/admin/saas/nomina/reglas` (`SaasNominaController`, gate
  `requireSaasAdmin()`): captura de versiones, filtros, tabla con vigencias y
  toggle. Entrada en el sidebar del Panel Medisoft. Los NEGOCIOS no editan
  este catalogo jamas.
- Semillas MINIMAS y honestas: estatutario LFT estable (aguinaldo 15 dias
  art. 87, prima vacacional 25% art. 80, tabla de vacaciones art. 76 reforma
  2023) + referencias 2025 de UMA ($113.14) y salarios minimos ($278.80 /
  $419.88 ZLFN) marcadas "verificar". El ejercicio VIGENTE (2026) y las
  tablas ISR/IMSS se capturan desde el panel con fuente DOF: NINGUN valor
  legal quemado en codigo de calculo.

## Archivos

Nuevos: `migrations/20260704_005_nomina_reglas_legales.sql`,
`src/app/services/NominaReglasLegalesService.php`,
`src/app/controllers/SaasNominaController.php`,
`src/app/views/admin/saas/nomina_reglas.php`,
`src/tools/saas/probar_nomina_reglas.php` (prueba CLI con rollback).
Modificados: `routes.php` (+3 rutas /admin/saas), `sidebar.php` (entrada
panel SaaS), `preflight_nomina_core.php` (checks Fase 5).

## Pruebas y resultado

- `php -l`: 7 archivos sin errores. Migracion aplicada y registrada.
- Prueba CLI del servicio (12/12 PASS, TODO con rollback — cero datos
  persistidos): resolucion de semillas LFT y UMA 2025 por fecha, no-vigencia
  antes de la fecha, version nueva cierra la anterior (2026-01-31) sin borrar
  el historico, vigencia no posterior rechazada, fuente obligatoria,
  desactivada no resuelve, historial con 2 eventos.
- Gate HTTP verificado: usuario de hotel autenticado -> 302 (denegado);
  sin sesion -> 303 login.
- Preflight: 47 OK / 1 WARNING (DB_STRICT_ERRORS global) / 0 ERROR. Checks
  nuevos: tablas + semillas LFT + resolubilidad HOY de uma_diaria y salario
  minimo (avisa capturar ejercicio vigente antes del modo legal) + cero
  solapes de vigencias activas.

## Pendiente operativo (owner)

Capturar en /admin/saas/nomina/reglas los valores del ejercicio vigente 2026
con fuente DOF: uma_diaria, salario_minimo_general/frontera y, antes de la
Fase 6, isr_tabla_* y subsidio_empleo_tabla. El preflight avisa mientras
falten.

## Siguiente fase

Fase 6: motor legal/fiscal MX (ISR retenido por tabla, SBC/SDI base, IMSS
obrero) consumiendo EXCLUSIVAMENTE este catalogo, congelando las reglas
aplicadas en reglas_snapshot_json del periodo, y actualizando la frontera de
nomina oficial (contrato + preflight) de forma deliberada.
