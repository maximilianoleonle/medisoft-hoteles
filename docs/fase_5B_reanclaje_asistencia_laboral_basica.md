# Fase 5B - Reanclaje de asistencia laboral basica

## Estado

`FASE_5B_ASISTENCIA_LABORAL_BASICA_YA_CUBIERTA_POR_NP_C_D`

## Motivo

El roadmap nuevo nombra `5B - Asistencia laboral basica`, pero el repositorio ya tiene
implementada y cerrada tecnicamente la asistencia manual dentro del bloque NP-C-D.

No conviene crear nuevas tablas, rutas ni modelos duplicados para la misma necesidad.

## Cobertura existente

- Tabla fuente: `trabajador_asistencias`.
- Ruta operativa existente: `POST /trabajadores/{id}/asistencias`.
- Controlador: `TrabajadorController::registrarAsistenciaLaboralAction()`.
- Modelo: `Trabajador::registrarAsistenciaLaboralParaHotel()`.
- Vista: ficha de trabajador en `GET /trabajadores/{id}`.
- Preflight: `tools/saas/preflight_personal_ledger.php`.
- Health checker: `tools/saas/health_check_fase_1a.php`.

## Guardrails existentes

- Requiere sesion y contexto de hotel.
- Usa CSRF.
- No recibe `hotel_id` desde el formulario.
- Valida trabajador activo del hotel actual.
- Bloquea duplicado por trabajador y fecha.
- Escribe solo en `trabajador_asistencias`.
- Audita la accion.

## Fuera de alcance

- Nomina automatica.
- Pagos reales.
- Abonos.
- Caja.
- Descuentos automaticos.
- Edicion o borrado de asistencias.
- `/api/sync`.

## QA manual diferida

La QA manual sigue diferida por instruccion del usuario. Cuando exista un trabajador
activo:

1. Abrir `/trabajadores/{id}`.
2. Registrar asistencia.
3. Confirmar que aparece en asistencias recientes.
4. Intentar duplicado de la misma fecha.
5. Confirmar que el duplicado se bloquea.
6. Confirmar que no se crean movimientos de Caja, pagos ni abonos.

## Decision

Marcar 5B como cubierta por NP-C-D y continuar con el siguiente bloque del roadmap sin
duplicar arquitectura.

Siguiente candidato seguro: 5C Anticipos/prestamos/saldos laborales, que tambien debe
reanclarse contra NP-C-C antes de implementar nada nuevo.
