# Nomina Core - Fase 4: recibos internos y reapertura controlada (cierre)

Fecha: 2026-07-04

## Alcance implementado

- `nomina_recibos`: recibos INTERNOS NO FISCALES emitidos desde el snapshot de
  un periodo v2 APROBADO. Folio consecutivo por negocio (NOM-000001, UNIQUE
  hotel+folio_numero con FOR UPDATE en la secuencia), totales y lineas
  congeladas en JSON (el PDF nunca recalcula). Un recibo vigente por detalle
  (UNIQUE detalle+cancelacion_uk); cancelable con motivo y re-emitible.
- PDF con TCPDF (patron del repo): encabezado del negocio, folio, empleado,
  periodo, tabla de conceptos +/-, totales, firmas y leyenda explicita de
  DOCUMENTO NO FISCAL. Marca CANCELADO si aplica. Documento operativo: NO se
  gatea bajo el bloque exportaciones (regla de AGENTS.md).
- Reapertura controlada (`NominaCierreService::reabrir`): aprobado -> cerrado
  SOLO si `nomina.permitir_reapertura` esta activa en la config del negocio;
  motivo obligatorio; bloqueada con pagos de Caja vigentes; cancela los
  recibos emitidos; evento tipo 'reapertura' + auditoria con antes/despues.
  El snapshot NO se recalcula jamas (para corregir montos: anular + re-cerrar).
- La anulacion v2 ahora tambien cancela recibos vigentes del periodo.
- Permisos: emitir recibos = nomina.aprobar; descargar PDF = nomina.salarios
  (contiene sueldos; coherente con la ficha); cancelar/reabrir = nomina.reabrir.

## Archivos

Nuevos: `migrations/20260704_004_nomina_recibos_reapertura.sql`,
`src/app/services/NominaReciboService.php`.
Modificados: `NominaCierreService` (+reabrir, +cancelacion de recibos en
anular), `NominaController` (+4 acciones), `routes.php` (+4 rutas),
`nomina/periodo_ver.php` (recibos por detalle, botones emitir/reabrir),
`preflight_nomina_core.php` (checks Fase 4).

## Pruebas y resultado

- `php -l`: 6 archivos sin errores. Migracion aplicada y registrada.
- Preflight: 42 OK / 1 WARNING (DB_STRICT_ERRORS global, deliberado) / 0 ERROR.
  Checks nuevos: tabla recibos, folios unicos, cero recibos vigentes de
  periodos no aprobados, ENUM de eventos con reapertura.
- Test funcional (sesion real, 20/20 PASS): emitir sobre CERRADO rechazado ->
  aprobar -> emitir (folio NOM-000001, neto == detalle) -> re-emision
  idempotente -> PDF real (application/pdf, %PDF) -> reabrir con config
  apagada rechazado -> sin motivo rechazado -> reapertura valida (estado
  cerrado, recibo cancelado, evento registrado) -> re-aprobar + re-emitir
  (1 vigente / 1 cancelado) -> PDF DENEGADO a rol administrador.

## Siguiente fase

Fase 5: catalogos legales versionados por ejercicio (UMA, salario minimo,
tablas ISR, cuotas IMSS, prestaciones) como tablas GLOBALES administradas
desde el panel SaaS, con vigencias, historial de cambios y servicio de
resolucion por fecha. Sin calculo fiscal todavia (Fase 6).
