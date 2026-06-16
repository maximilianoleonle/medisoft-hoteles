# Fase LIM-F - Cierre tecnico limpieza read-only

Estado: `BLOQUE_LIM_LIMPIEZA_READONLY_CERRADO_QA_DIFERIDA`.

## Alcance cerrado

- LIM-0: contrato de limpieza operativa.
- LIM-A: reporte read-only de limpieza.

## Commits del bloque

- `f0ff65b docs(phase-lim): define housekeeping operations contract`
- `e22eb61 feat(phase-lim): add read-only housekeeping report`

## Revision tecnica

- La unica ruta nueva funcional es `GET /reportes/limpieza`.
- La ruta usa `ReportesController`, por lo que hereda guardas de sesion, permisos y
  modulo `reportes`.
- La vista no contiene formularios.
- Las consultas filtran por `hotel_id`.
- Las tareas de limpieza son contexto operativo; no liberan habitaciones.
- No se modifican `habitaciones`, `reservaciones`, `tareas_operativas` ni inventario.

## Auditoria de seguridad

- No hay POST nuevo.
- No hay cambio de estado de habitacion.
- No hay liberacion automatica.
- No hay creacion automatica de tareas.
- No hay descuento automatico de inventario por limpieza.
- No hay Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Verificacion automatica

- `php -l` en archivos PHP tocados: OK.
- `tools/saas/preflight_limpieza_operativa.php`: `ERROR: 0`, `WARNING: 0`.
- `tools/saas/health_check_fase_1a.php`: `ERROR: 0`, warnings conocidos.
- HTTP sin sesion a `/reportes/limpieza`: redirige a login.
- `git diff --check`: sin errores reales; solo avisos normales de CRLF en Windows.

## QA manual diferida

Por instruccion del usuario, la QA manual queda diferida. Cuando se ejecute:

1. Abrir `/reportes/limpieza`.
2. Confirmar que muestra habitaciones en limpieza del hotel actual.
3. Confirmar que la vista no muestra botones de liberar ni crear tareas.
4. Confirmar enlaces GET a habitaciones y tareas.
5. Confirmar que no hay efectos en Caja, inventario, offline ni `/api/sync`.

## Rollback

- Para retirar el cierre documental, revertir
  `docs(phase-lim): close read-only housekeeping block`.
- Para retirar el reporte, revertir
  `e22eb61 feat(phase-lim): add read-only housekeeping report`.
- DB: no aplica; el bloque no crea migraciones ni datos.

## Siguiente paso recomendado

No abrir POST de limpieza todavia. El siguiente bloque seguro puede ser un contrato
independiente para tareas manuales de limpieza desde habitacion en estado `limpieza`, o
volver a QA manual de los bloques diferidos.
