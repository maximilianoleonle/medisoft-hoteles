# Fase NP-C-B-F - Cierre tecnico de conceptos laborales

## Estado

`BLOQUE_NP_C_B_CONCEPTOS_LABORALES_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- NP-C-B-0 contrato de conceptos laborales manuales.
- NP-C-B-A implementacion de registro manual controlado en `trabajador_pagos`.
- Revision tecnica posterior con correcciones menores de textos/contrato.
- Health checker y preflight de ledger laboral actualizados a NP-C-B-A.

## Validaciones tecnicas

- Rutas: solo se agrego `POST /trabajadores/{id}/conceptos-laborales`.
- Controlador: exige autenticacion, hotel actual, permiso existente, POST y CSRF.
- Modelo: valida trabajador activo del hotel actual y escribe solo en `trabajador_pagos`.
- Vista: formulario solo para trabajador activo y con texto de no Caja/no pago real.
- Auditoria: registra `trabajadores.concepto_laboral_registrado`.
- Checkers: `preflight_personal_ledger.php` y `health_check_fase_1a.php` pasan sin
  errores.

## Seguridad

- No hay pagos reales.
- No hay abonos.
- No hay anticipos ni prestamos operativos.
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

- Ver formulario en `/trabajadores/{id}`.
- Registrar concepto permitido.
- Confirmar aparicion en ledger.
- Confirmar cero movimientos de Caja.
- Ejecutar `preflight_personal_ledger.php` tras la prueba.

## Siguiente paso recomendado

No abrir pagos reales ni Caja. Si se continua con Personal, la siguiente accion segura es
un contrato nuevo y explicito para anticipos/prestamos o asistencia, antes de cualquier
implementacion.
