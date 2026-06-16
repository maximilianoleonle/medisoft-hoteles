# Fase NP-C-A - Ledger laboral read-only

Estado: `LEDGER_LABORAL_NP_C_A_COMPLETADO_QA_DIFERIDA`

## Objetivo

Mostrar en la ficha de trabajador una vista de solo lectura del ledger laboral existente,
sin crear pagos reales, sin abonos, sin movimientos de Caja y sin rutas POST nuevas.

## Implementacion

- `Trabajador::resumenLedgerPorTrabajador()` ahora calcula:
  - conceptos a favor;
  - conceptos en contra;
  - anticipos pendientes;
  - prestamos pendientes;
  - saldo informativo derivado.
- Nuevas consultas read-only:
  - `conceptosLaboralesPorTrabajador()`;
  - `anticiposPorTrabajador()`;
  - `prestamosPorTrabajador()`.
- `TrabajadorController::verAction()` entrega esas listas a la ficha.
- `src/app/views/trabajadores/ver.php` muestra:
  - resumen laboral;
  - saldo informativo;
  - conceptos laborales;
  - anticipos;
  - prestamos;
  - estados vacios claros.
- `health_check_fase_1a.php` valida el contrato NP-C-A.

## Seguridad

- No se agregan rutas.
- No se agregan POST.
- No se crea, actualiza ni borra ningun registro.
- Todas las consultas se filtran por `hotel_id` y `trabajador_id`.
- No se expone `ruta_archivo` ni documentos laborales.
- No se toca Caja, `movimientos_caja`, `cortes_caja`, categoria Nomina ni `/api/sync`.
- La vista avisa que el saldo es informativo y no representa pago real.

## Verificacion automatica requerida

- `php -l` en:
  - `src/app/models/Trabajador.php`;
  - `src/app/controllers/TrabajadorController.php`;
  - `src/app/views/trabajadores/ver.php`;
  - `src/tools/saas/health_check_fase_1a.php`.
- Health checker sin errores.
- SQL read-only de conteos `trabajador_*`, Caja y categoria Nomina.
- HTTP sin sesion en `/trabajadores/1` debe redirigir/bloquear.
- `git diff --check`.

## QA manual diferida

- Abrir `/trabajadores`.
- Crear o usar un trabajador de prueba autorizado.
- Abrir `/trabajadores/{id}`.
- Confirmar bloque "Ledger laboral".
- Confirmar estado vacio claro si no hay conceptos, anticipos ni prestamos.
- Confirmar que no hay botones de pago, abono, Caja ni nomina.
- Confirmar que el saldo se muestra como informativo.

## Rollback

- Revertir el commit `feat(phase-np): add read-only worker ledger view`.
- DB: no aplica; NP-C-A no escribe datos ni crea migraciones.
- No tocar tablas `trabajador_*`, Caja ni `/api/sync`.
