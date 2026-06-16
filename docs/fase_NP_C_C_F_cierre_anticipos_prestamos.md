# Fase NP-C-C-F - Cierre tecnico de anticipos y prestamos

## Estado

`BLOQUE_NP_C_C_ANTICIPOS_PRESTAMOS_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- NP-C-C-0 contrato de anticipos y prestamos laborales.
- NP-C-C-A captura manual controlada en `trabajador_anticipos` y
  `trabajador_prestamos`.
- Revision tecnica posterior con ajuste de copy para no contradecir el nuevo alcance.
- Health checker y preflight de ledger laboral actualizados a NP-C-C-A.

## Validaciones tecnicas

- Rutas:
  - `POST /trabajadores/{id}/anticipos`
  - `POST /trabajadores/{id}/prestamos`
- Controlador: exige autenticacion, hotel actual, permiso existente, POST y CSRF.
- Modelo: valida trabajador activo del hotel actual y escribe solo en tablas permitidas.
- Vista: formularios solo para trabajador activo; no envia `hotel_id`, `estado` ni
  `saldo_pendiente`.
- Auditoria: registra movimientos laborales con banderas `sin_caja`, `sin_pago_real` y
  `sin_abono`.
- Checkers: `preflight_personal_ledger.php` y `health_check_fase_1a.php` pasan sin
  errores.

## Seguridad

- No hay pagos reales.
- No hay abonos.
- No hay liquidaciones ni cancelaciones de saldo.
- No hay asistencia operativa.
- No hay categoria Nomina.
- No hay movimientos de Caja.
- No hay cambios en `/api/sync`.

## Warnings conocidos

- `health_check_fase_1a.php` mantiene warnings historicos por docs/migrations no visibles
  desde el contenedor y tablas legacy congeladas.
- La tabla `trabajadores` local esta vacia; por eso no se ejecuto QA manual de escritura.

## QA manual diferida

Pendiente probar con trabajador activo autorizado:

- Registrar anticipo.
- Registrar prestamo.
- Confirmar que ambos aparecen en el ledger.
- Confirmar que saldo pendiente inicia igual al monto.
- Confirmar cero movimientos de Caja, cero pagos reales y cero abonos.
- Ejecutar `preflight_personal_ledger.php` tras la prueba.

## Siguiente paso recomendado

No abrir abonos, liquidaciones ni Caja. Si se continua con Personal, la siguiente accion
segura es contrato nuevo para asistencia manual basica, sin nomina automatica.
