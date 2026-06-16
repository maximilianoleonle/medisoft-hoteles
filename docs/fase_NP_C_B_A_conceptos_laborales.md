# Fase NP-C-B-A - Conceptos laborales manuales

## Objetivo

Habilitar una primera escritura laboral controlada en `trabajador_pagos` para registrar
conceptos manuales de trabajador sin convertirlos en pagos reales.

## Alcance aplicado

- Ruta POST `trabajadores/{id}/conceptos-laborales`.
- Formulario con CSRF en la ficha del trabajador activo.
- Modelo central `Trabajador::registrarConceptoLaboralParaHotel()`.
- Validacion de trabajador activo y `hotel_id` del contexto.
- Tipos permitidos: `comision`, `bono`, `descuento`, `ajuste`.
- Efecto permitido: `a_favor` o `en_contra`.
- Auditoria en `logs_auditoria` con bandera `sin_caja` y `sin_pago_real`.
- Health checker y preflight de ledger laboral actualizados a NP-C-B-A.

## Fuera de alcance

- No crea pagos reales.
- No crea abonos.
- No crea anticipos ni prestamos.
- No crea asistencia.
- No crea categoria Nomina en Caja.
- No crea movimientos de Caja.
- No toca `/api/sync`.
- No modifica calculos financieros profundos.

## Reglas de seguridad

- `hotel_id` se obtiene del contexto de sesion, nunca del formulario.
- El trabajador debe existir en el hotel actual y estar activo.
- La escritura se limita a `INSERT INTO trabajador_pagos`.
- No se permite `UPDATE` ni `DELETE` sobre `trabajador_pagos` en esta fase.
- `comision` y `bono` siempre suman `a_favor`.
- `descuento` siempre queda `en_contra`.
- `ajuste` permite elegir el efecto.
- El monto debe ser numerico y mayor a cero.
- La fecha del concepto es obligatoria.

## Verificacion automatica ejecutada

- `php -l` en `Trabajador.php`.
- `php -l` en `TrabajadorController.php`.
- `php -l` en `trabajadores/ver.php`.
- `php -l` en `health_check_fase_1a.php`.
- `php -l` en `preflight_personal_ledger.php`.
- `preflight_personal_ledger.php`: `ERROR: 0`, `WARNING: 0`.
- `health_check_fase_1a.php`: `ERROR: 0`, warnings historicos conocidos.
- SQL read-only: `trabajadores=0`, `trabajador_pagos=0`, `trabajador_anticipos=0`,
  `trabajador_prestamos=0`, `categorias_nomina=0`.
- HTTP sin sesion en `GET /trabajadores/1`: `303`.
- HTTP sin sesion en `POST /trabajadores/1/conceptos-laborales`: `303`.
- `git diff --check`: sin errores.

## Nota de datos

No se hizo prueba de escritura con datos locales porque la tabla `trabajadores` esta vacia.
No se fabricaron trabajadores ni conceptos de prueba para evitar modificar datos reales o
crear registros artificiales.

## QA manual diferida

Cuando exista un trabajador activo de prueba o real autorizado, validar:

- Abrir `/trabajadores/{id}`.
- Confirmar que el formulario aparece solo para trabajador activo.
- Registrar `comision`, `bono`, `descuento` y `ajuste`.
- Confirmar que el concepto aparece en el ledger.
- Confirmar que no se crean movimientos de Caja.
- Confirmar que no aparecen botones de pago real, abono, anticipo o prestamo.

## Rollback

- Revertir el commit `feat(phase-np): add controlled worker concept entry`.
- No ejecutar `DELETE` manual sobre `trabajador_pagos`.
- Si QA manual creo conceptos de prueba, documentar IDs antes de cualquier correccion
  autorizada futura.
- No tocar Caja, categorias, movimientos ni `/api/sync`.
