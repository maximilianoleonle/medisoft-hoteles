# Contratos de fase - cola autonoma

## Fase 3B aplicada: cuentas por pagar base read-only

### Objetivo

Alinear codigo y base de datos local despues de haber aplicado la migracion CxP, dejando una fundacion read-only segura para cuentas por pagar.

### Riesgo

Naranja.

Motivo:

- toca base de datos;
- crea estructuras financieras;
- debe mantenerse read-only;
- no debe tocar Caja;
- no debe registrar pagos;
- no debe generar saldos automaticamente desde compras.

### Incluye

- migracion no destructiva ya aplicada:
  - `cuentas_por_pagar`;
  - `cuentas_por_pagar_movimientos`.
- modelo read-only de CxP;
- controlador read-only;
- rutas:
  - listado CxP;
  - detalle CxP.
- vistas read-only:
  - indice;
  - detalle.
- navegacion basica en sidebar bajo alcance de inventario;
- health/preflight compatible con las nuevas tablas;
- documentacion de QA, rollback y decisiones.

### No incluye

- pagos a proveedores;
- integracion con Caja;
- generacion automatica de CxP desde compras;
- edicion operativa de saldos;
- eliminacion de datos;
- cambios en facturacion;
- cambios en `/api/sync`;
- CxC;
- nomina;
- permisos profundos.

### Definition of Done

- `git status` revisado.
- Cambios pendientes clasificados.
- Archivos CxP identificados.
- Cambio no relacionado en `src/app/views/reservaciones/ver.php` separado o reportado.
- `php -l` ejecutado sobre:
  - `src/app/controllers/CuentaPorPagarController.php`;
  - `src/app/models/CuentaPorPagar.php`;
  - `src/app/views/cuentas_por_pagar/index.php`;
  - `src/app/views/cuentas_por_pagar/ver.php`;
  - `src/config/routes.php`;
  - `src/app/views/layout/sidebar.php`;
  - checkers modificados.
- Checkers ejecutados:
  - `src/tools/saas/health_check_fase_1a.php`;
  - `src/tools/saas/preflight_compras_minimas.php`;
  - `src/tools/saas/preflight_recepcion_compras.php`.
- Validacion SQL confirmada:
  - tablas existen;
  - migracion registrada;
  - tablas CxP siguen vacias si no hay datos de prueba;
  - no se generaron pagos;
  - no se toco Caja.
- Prueba HTTP sin sesion:
  - `/cuentas-por-pagar` redirige a login o bloquea acceso segun patron del sistema.
- `/api/sync` sigue bloqueado con HTTP 423 segun checker.
- `git diff --check` sin errores bloqueantes.
- Documentacion actualizada:
  - estado autonomo;
  - QA pendiente;
  - contrato;
  - decisiones;
  - rollback;
  - resumen ejecutivo;
  - fuentes de verdad.
- Commit estable realizado solo con archivos relacionados con Fase 3B y documentacion correspondiente.

### Commit esperado

`feat(phase-3b): add read-only accounts payable foundation`

### Estado posterior

Al cerrar esta fase, no avanzar a Fase 3C sin nuevo mensaje real. El estado debe quedar en revision de usuario o cierre del bloque autorizado.

## Fase 3C: CxP operativa controlada sin Caja

### Objetivo

Permitir generar cuentas por pagar manualmente desde compras recibidas, de forma controlada, auditable y sin integracion con Caja ni pagos.

### Riesgo

Naranja.

Motivo:

- escribe en `cuentas_por_pagar`;
- crea saldos financieros;
- requiere evitar duplicados por compra;
- no debe mover dinero;
- no debe tocar Caja.

### Incluye

- contrato y diagnostico;
- simulador read-only de CxP generable desde compras recibidas;
- vista de compras recibidas elegibles y bloqueadas;
- accion manual para generar CxP desde compra recibida, solo despues del simulador;
- validaciones por hotel, proveedor, estado, total y no duplicado;
- auditoria si aplica;
- health/preflight/checkers;
- QA, rollback, fuentes de verdad y cierre tecnico.

### No incluye

- pagos a proveedores;
- abonos;
- Caja;
- movimientos de Caja;
- conciliacion;
- generacion automatica al recibir compra;
- edicion manual de saldos;
- cancelacion operativa de CxP;
- CxC;
- nomina;
- permisos profundos;
- cambios en `/api/sync`.

### Definition of Done

- Contrato documentado.
- Simulador read-only verificado.
- Generacion manual implementada solo si simulador quedo estable.
- No hay generacion automatica.
- No hay pagos ni Caja.
- CxP no duplica compras.
- CxP respeta `hotel_id`.
- CxP respeta proveedor/compra del mismo hotel.
- CxP solo nace de compra recibida.
- Checkers sin errores bloqueantes.
- `php -l` en archivos tocados.
- HTTP sin sesion bloquea rutas sensibles.
- QA manual documentada.
- Rollback documentado.
- Revision tecnica y auditoria de seguridad completadas.
- Commits por subfase.

### Estado 3C-0

Contrato y diagnostico documentados en `docs/fase_3C_0_contrato_diagnostico.md`. No se implemento simulador ni generacion manual en 3C-0.
