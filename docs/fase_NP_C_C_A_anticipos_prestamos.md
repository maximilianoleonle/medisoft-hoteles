# Fase NP-C-C-A - Anticipos y prestamos manuales

## Objetivo

Habilitar captura manual controlada de anticipos y prestamos laborales desde la ficha del
trabajador, sin Caja, sin pagos reales y sin abonos.

## Alcance aplicado

- Rutas POST:
  - `trabajadores/{id}/anticipos`
  - `trabajadores/{id}/prestamos`
- Formularios con CSRF en la ficha del trabajador activo.
- Modelo central:
  - `Trabajador::registrarAnticipoLaboralParaHotel()`
  - `Trabajador::registrarPrestamoLaboralParaHotel()`
- Validacion de trabajador activo y `hotel_id` del contexto.
- `saldo_pendiente` inicial derivado del monto; no viene del formulario.
- Estado inicial fijo:
  - anticipo: `pendiente`
  - prestamo: `vigente`
- Auditoria con banderas `sin_caja`, `sin_pago_real` y `sin_abono`.
- Health checker y preflight actualizados a NP-C-C-A.

## Fuera de alcance

- No crea pagos reales.
- No crea abonos.
- No liquida ni descuenta saldos.
- No crea asistencia.
- No crea categoria Nomina.
- No crea movimientos de Caja.
- No toca `/api/sync`.

## Reglas de seguridad

- `hotel_id` se obtiene del contexto de sesion.
- El trabajador debe existir en el hotel actual y estar activo.
- Monto mayor a cero obligatorio.
- Fecha obligatoria.
- Motivo obligatorio.
- `saldo_pendiente` y `estado` no se aceptan desde formulario.
- Prestamo permite `plazo_meses` y `abono_periodico` solo como datos informativos.
- No se permite `UPDATE` ni `DELETE` sobre anticipos/prestamos en esta fase.
- No se escriben `movimientos_caja`, `cajas` ni `cortes_caja`.

## Verificacion automatica ejecutada

- `php -l` en `Trabajador.php`.
- `php -l` en `TrabajadorController.php`.
- `php -l` en `trabajadores/ver.php`.
- `php -l` en `preflight_personal_ledger.php`.
- `php -l` en `health_check_fase_1a.php`.
- `preflight_personal_ledger.php`: `OK: 42`, `WARNING: 0`, `ERROR: 0`.
- `health_check_fase_1a.php`: `ERROR: 0`, warnings historicos conocidos.
- SQL read-only: `trabajadores=0`, `trabajador_pagos=0`, `trabajador_anticipos=0`,
  `trabajador_prestamos=0`, `trabajador_asistencias=0`, `categorias_nomina=0`.
- HTTP sin sesion:
  - `POST /trabajadores/1/anticipos`: `303`
  - `POST /trabajadores/1/prestamos`: `303`

## Nota de datos

No se hizo prueba de escritura con datos locales porque `trabajadores` esta vacia. No se
fabricaron trabajadores ni movimientos laborales de prueba.

## QA manual diferida

Cuando exista un trabajador activo autorizado:

- Registrar un anticipo y confirmar que aparece en ledger con saldo pendiente igual al
  monto.
- Registrar un prestamo y confirmar que aparece en ledger con saldo pendiente igual al
  monto.
- Confirmar que no se crean movimientos de Caja.
- Confirmar que no se crean pagos reales ni abonos.
- Ejecutar `preflight_personal_ledger.php` y confirmar `ERROR: 0`.

## Rollback

- Revertir el commit `feat(phase-np): add controlled worker advances and loans`.
- No ejecutar `DELETE` sobre `trabajador_anticipos` ni `trabajador_prestamos`.
- Si QA manual genero registros de prueba, documentar IDs y esperar fase autorizada de
  anulacion/correccion.
