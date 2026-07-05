# Nomina Core - Fase 2: catalogos internos e historial salarial (cierre)

Fecha: 2026-07-04

## Alcance implementado

- 5 catalogos por negocio (tablas nuevas, FK RESTRICT a hoteles, autoria, CHECKs):
  `nomina_departamentos`, `nomina_puestos` (con departamento y salario sugerido),
  `nomina_tipos_contrato`, `nomina_grupos` (periodicidad semanal/quincenal/mensual +
  dia de corte/pago) y `nomina_conceptos` (percepcion/deduccion, clasificacion,
  modo de calculo, banderas gravable_isr/imss para fases fiscales, es_sistema).
- Conceptos base (9) sembrados automaticamente por negocio al abrir catalogos
  con permiso de configuracion (idempotente).
- `trabajador_salarios`: historial salarial con vigencias (UNIQUE hotel+trabajador+
  vigente_desde, CHECKs de rango). Backfill idempotente desde `salario_base`.
  Regla implementada en `NominaSalarioService::registrarCambio` (transaccional,
  FOR UPDATE): cambio nuevo cierra la vigencia anterior (desde-1 dia) e inserta
  vigencia abierta; mismo dia = correccion in-place; fecha anterior = rechazo.
  `trabajadores.salario_base`/`periodicidad_pago` se sincronizan por compatibilidad.
- Extension ADITIVA de `trabajadores`: `puesto_id`, `departamento_id`,
  `tipo_contrato_id`, `grupo_nomina_id` (NULL-ables, FK SET NULL, via procedure
  idempotente). `rol_laboral` texto libre se conserva.
- `NominaCatalogoService`: CRUD generico de los 5 catalogos con hotel_id explicito
  en todo WHERE, validacion de duplicados, whitelist de enums, y
  `asignarATrabajador` que RECHAZA ids de catalogos de otro hotel (tenant isolation).
- Pantallas: `/nomina/catalogos` (tabs), `/nomina/empleados` (listado con filtros),
  `/nomina/empleados/{id}` (ficha: asignaciones + salario). El salario solo es
  visible/editable con `nomina.salarios`; asignaciones con `nomina.empleados`;
  catalogos con `nomina.configurar`.
- Auditoria: toda escritura registra AuditService con datos_antes/datos_despues.

## Archivos

Nuevos: `migrations/20260704_002_nomina_catalogos_salarios.sql`,
`src/app/services/NominaCatalogoService.php`, `src/app/services/NominaSalarioService.php`,
`src/app/views/nomina/catalogos.php`, `src/app/views/nomina/empleados.php`,
`src/app/views/nomina/empleado_ficha.php`.
Modificados: `NominaController` (+10 acciones), `routes.php` (+8 rutas),
`navegacion.php` (+2 pantallas), `nomina/index.php` (navrow),
`preflight_nomina_core.php` (checks Fase 2).

## Pruebas y resultado

- `php -l`: 10 archivos sin errores.
- Migracion aplicada a BD local; registrada en `migrations`.
- Preflight: 32 OK / 1 WARNING (DB_STRICT_ERRORS, deliberado) / 0 ERROR. Incluye:
  backfill completo, cero dobles vigencias abiertas, cero asignaciones cruzadas
  entre hoteles.
- Test funcional con sesion real: 22/22 PASS. Cubre: lectura permitida a
  administrador, escritura DENEGADA sin nomina.configurar (y verificado que no
  inserta), flujo completo como gerente (departamento, puesto, grupo, duplicado
  rechazado, conceptos sembrados), asignaciones persistidas, salario inicial +
  aumento con cierre de vigencia y sincronizacion de salario_base, e INTENTO DE
  FUGA de tenant rechazado (asignar puesto de otro hotel no cambia nada).

## Hallazgo operativo documentado

`current_hotel_role_id()` prefiere el role_id cacheado en la SESION al del DB:
un cambio de rol aplica hasta el siguiente login. Relevante para QA y para
soporte (no es bug de nomina; es comportamiento global del sistema de roles).

## Siguiente fase

Fase 3: motor operativo v2 — incidencias normalizadas por concepto, calculo de
preview por grupo+periodo (salario vigente por dia + incidencias + ledger),
validacion de solape por trabajador y cierre con conceptos por linea.
