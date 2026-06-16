# Fase NP-C-D-A - Captura manual de asistencia laboral

## Estado

`ASISTENCIA_MANUAL_NP_C_D_A_COMPLETADA_QA_DIFERIDA`

## Objetivo

Habilitar una captura manual basica de asistencia laboral por trabajador y dia en
`trabajador_asistencias`, sin nomina automatica, sin pagos reales y sin Caja.

## Cambios tecnicos

- Ruta nueva:
  - `POST /trabajadores/{id}/asistencias`
- Controlador:
  - `TrabajadorController::registrarAsistenciaLaboralAction()`
  - exige permiso existente, metodo POST y CSRF.
  - no recibe `hotel_id` desde el formulario.
  - registra auditoria mediante `AuditService`.
- Modelo:
  - `Trabajador::registrarAsistenciaLaboralParaHotel()`
  - `Trabajador::asistenciaLaboralPorIdHotel()`
  - valida trabajador activo del hotel actual.
  - valida fecha, tipo, horas y formato de horas de entrada/salida.
  - bloquea duplicados por `(hotel_id, trabajador_id, fecha)`.
  - escribe solo `INSERT INTO trabajador_asistencias`.
- Vista:
  - formulario en ficha de trabajador activo.
  - muestra estado vacio si no hay asistencias.
  - conserva tabla de asistencias recientes.

## Seguridad

- No crea nomina.
- No crea pagos reales.
- No crea abonos.
- No crea movimientos de Caja.
- No crea categorias de Caja.
- No toca `/api/sync`.
- No edita ni borra asistencias existentes.
- No acepta `hotel_id`, `trabajador_id`, `created_by` ni `updated_by` desde el formulario.

## Validaciones automaticas

- `php -l` en modelo, controlador, vista, rutas y checkers.
- `preflight_personal_ledger.php` actualizado a NP-C-D-A y sin errores.
- `health_check_fase_1a.php` actualizado a NP-C-D-A; sin errores, solo warnings historicos.
- SQL read-only confirma:
  - `trabajador_asistencias = 0` en base local.
  - `trabajador_pagos = 0`.
  - `trabajador_anticipos = 0`.
  - `trabajador_prestamos = 0`.
  - `categorias_nomina = 0`.

## QA manual diferida

No se ejecuto escritura real porque la tabla local `trabajadores` esta vacia y el usuario
pidio omitir QA manual temporalmente.

Pasos sugeridos cuando exista trabajador activo:

1. Abrir `/trabajadores/{id}` con trabajador activo del hotel actual.
2. Confirmar que aparece el formulario de asistencia.
3. Registrar una asistencia para la fecha actual.
4. Confirmar que aparece en la tabla de asistencias recientes.
5. Intentar registrar otra asistencia para la misma fecha.
6. Confirmar bloqueo con mensaje claro de duplicado.
7. Confirmar que no se crean movimientos de Caja ni categorias Nomina.
8. Ejecutar `docker compose exec -T app php /var/www/html/tools/saas/preflight_personal_ledger.php`.

## Rollback

- Revertir el commit `feat(phase-np): add controlled worker attendance capture`.
- No ejecutar `DELETE` ni `UPDATE` sobre `trabajador_asistencias` sin autorizacion nueva.
- Si se generan asistencias de prueba en QA manual, documentar IDs y esperar fase de
  anulacion/correccion o restaurar backup de prueba autorizado.

## Siguiente paso recomendado

Cerrar tecnicamente NP-C-D-A con revision/auditoria documental. No avanzar a nomina,
abonos, pagos reales ni Caja.
