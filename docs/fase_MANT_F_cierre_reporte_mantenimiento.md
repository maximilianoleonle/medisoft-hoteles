# Fase MANT-F - Cierre tecnico reporte mantenimiento read-only

Estado: `BLOQUE_MANT_REPORTE_MANTENIMIENTO_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- Ruta existente: `GET /reportes/mantenimiento`.
- Vista existente: `src/app/views/reportes/mantenimiento.php`.
- Modelo afectado: `src/app/models/Reporte.php`.
- Guardrail nuevo: `src/tools/saas/preflight_reporte_mantenimiento.php`.
- Health general actualizado: `src/tools/saas/health_check_fase_1a.php`.

## Confirmaciones tecnicas

- El reporte sigue siendo GET/read-only.
- No existe `POST /reportes/mantenimiento`.
- Las consultas activas usadas por `ReportesController::mantenimientoAction()` filtran
  por `hotel_id`.
- Los joins con `habitaciones` validan que la habitacion pertenezca al mismo hotel.
- El preflight MANT-A pasa con `ERROR: 0` y `WARNING: 0`.
- El health general pasa con `ERROR: 0`; mantiene warnings historicos conocidos.
- HTTP sin sesion a `/reportes/mantenimiento` redirige a login.
- `/api/sync` sigue bloqueado segun health checker.

## No incluido

- No se crean rutas nuevas.
- No se crean migraciones.
- No se crean acciones de mantenimiento.
- No se automatizan cambios de habitacion.
- No se toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## QA manual diferida

La QA manual queda diferida por instruccion del usuario. Cuando se retome, validar:

1. Abrir `/reportes/mantenimiento` con sesion de cada hotel relevante.
2. Confirmar que solo aparecen datos del hotel activo.
3. Probar filtros de fecha y tipo.
4. Confirmar estado vacio si no hay mantenimientos.
5. Confirmar que no hay botones de accion ni formularios POST.

## Rollback

- Revertir `test(phase-mant): add read-only maintenance report guardrails`.
- Revertir `docs(phase-mant): close read-only maintenance report block`.
- No ejecutar operaciones de base de datos para revertir este bloque.
