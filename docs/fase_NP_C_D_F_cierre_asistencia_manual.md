# Fase NP-C-D-F - Cierre tecnico de asistencia manual

## Estado

`BLOQUE_NP_C_D_ASISTENCIA_MANUAL_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- NP-C-D-0 contrato de asistencia manual.
- NP-C-D-A captura manual controlada en `trabajador_asistencias`.
- Health checker y preflight de ledger laboral actualizados a NP-C-D-A.
- Documentacion de QA, rollback, fuentes de verdad, decisiones y auditoria actualizada.

## Validaciones tecnicas

- Ruta:
  - `POST /trabajadores/{id}/asistencias`
- Controlador: exige autenticacion, hotel actual, permiso existente, POST y CSRF.
- Modelo: valida trabajador activo del hotel actual y escribe solo
  `trabajador_asistencias`.
- Vista: formulario solo para trabajador activo; no envia `hotel_id`, `trabajador_id` ni
  campos de auditoria.
- Duplicados: se bloquea una segunda asistencia para el mismo trabajador y fecha.
- Auditoria: registra movimiento laboral con banderas `sin_caja`, `sin_pago_real` y
  `sin_abono`.
- Checkers: `preflight_personal_ledger.php` y `health_check_fase_1a.php` pasan sin
  errores.

## Seguridad

- No hay nomina automatica.
- No hay descuentos automaticos.
- No hay pagos reales.
- No hay abonos.
- No hay categorias Nomina.
- No hay movimientos de Caja.
- No hay cambios en `/api/sync`.

## Warnings conocidos

- `health_check_fase_1a.php` mantiene warnings historicos por docs/migrations no visibles
  desde el contenedor y tablas legacy congeladas.
- La tabla `trabajadores` local esta vacia; por eso no se ejecuto QA manual de escritura.

## QA manual diferida

Pendiente probar con trabajador activo autorizado:

- Registrar asistencia.
- Confirmar que aparece en asistencias recientes.
- Intentar duplicado de la misma fecha y confirmar error claro.
- Confirmar cero movimientos de Caja, cero pagos reales, cero abonos y cero categorias
  Nomina.
- Ejecutar `preflight_personal_ledger.php` tras la prueba.

## Siguiente paso recomendado

No abrir nomina, descuentos automaticos, abonos, liquidaciones ni Caja. La siguiente fase
segura debe ser un contrato nuevo independiente, por ejemplo documentos laborales read-only
o cierre general de Personal base, sin escritura financiera.
