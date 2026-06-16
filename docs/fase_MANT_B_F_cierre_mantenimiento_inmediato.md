# Fase MANT-B-F - Cierre tecnico mantenimiento inmediato

Estado: `BLOQUE_MANT_B_MANTENIMIENTO_INMEDIATO_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- Accion existente: `POST /habitaciones/{id}/mantenimiento`.
- Controlador: `src/app/controllers/HabitacionController.php`.
- Modelo revisado: `src/app/models/Mantenimiento.php`.
- Vista revisada: `src/app/views/habitaciones/ver.php`.
- Guardrail nuevo: `src/tools/saas/preflight_mantenimiento_operativo.php`.
- Health general actualizado: `src/tools/saas/health_check_fase_1a.php`.

## Confirmaciones tecnicas

- La accion mantiene POST, CSRF y permiso `habitaciones.mantenimiento`.
- `accion` queda limitada a `iniciar` y `finalizar`.
- `tipo_mantenimiento` y `prioridad` se validan contra catalogo backend.
- `motivo` es obligatorio al iniciar.
- Se bloquea iniciar si la habitacion ya esta en mantenimiento.
- Se bloquea iniciar si ya existe mantenimiento `en_proceso` para la misma habitacion/hotel.
- Finalizar exige habitacion en estado `mantenimiento`.
- `hotel_id` sigue saliendo del contexto actual.
- No se agregan rutas nuevas ni migraciones.
- No hay Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Verificacion automatica

- `php -l app/controllers/HabitacionController.php`: OK.
- `php -l tools/saas/preflight_mantenimiento_operativo.php`: OK.
- `php -l tools/saas/health_check_fase_1a.php`: OK.
- `php tools/saas/preflight_mantenimiento_operativo.php`: `ERROR: 0`.
- `php tools/saas/health_check_fase_1a.php`: `ERROR: 0`.
- POST sin sesion a `/habitaciones/1/mantenimiento`: redirige a login.
- `git diff --check`: sin errores.

## Warnings conocidos

- Existe 1 habitacion en estado `mantenimiento` sin registro `en_proceso`. Se conserva
  como warning historico; no se corrige automaticamente ni con SQL manual.

## QA manual diferida

1. Iniciar mantenimiento desde una habitacion disponible.
2. Confirmar validaciones de tipo/prioridad/motivo.
3. Confirmar bloqueo limpio de doble inicio.
4. Finalizar mantenimiento desde habitacion en mantenimiento.
5. Confirmar ausencia de Caja, pagos, abonos y `/api/sync`.

## Rollback

- Revertir `fix(phase-mant): harden immediate maintenance action`.
- Revertir `docs(phase-mant): close immediate maintenance guardrails`.
- DB: no aplica; el bloque no crea migraciones.
