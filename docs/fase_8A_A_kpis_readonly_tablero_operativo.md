# Fase 8A-A - KPIs read-only en tablero operativo

## Estado

`KPIS_8A_A_DASHBOARD_READONLY_COMPLETADOS_QA_DIFERIDA`

## Objetivo

Extender el tablero operativo existente `/operacion/diaria` con KPIs adicionales
read-only, sin crear un dashboard paralelo y sin agregar acciones.

## Implementacion

- Modelo actualizado: `OperacionDiaria`.
- Vista actualizada: `operacion/diaria`.
- Preflight actualizado: `preflight_operacion_diaria.php`.

## KPIs agregados

- CxC estimada pendiente.
- Reservaciones pendientes por saldo.
- Reservaciones liquidadas.
- Reservaciones con excedente.
- Acceso GET al reporte detallado `/cuentas-por-cobrar`.

## Fuentes

- `reservaciones`.
- `reservacion_pagos`.
- `reservacion_abonos`.

El saldo es estimado y derivado. No crea una cuenta contable nueva.

## Guardrails

- Solo lectura.
- Sin rutas nuevas.
- Sin POST.
- Sin formularios.
- Sin Caja.
- Sin pagos.
- Sin abonos nuevos.
- Sin cambios en reservaciones.
- Sin `/api/sync`.
- Todo filtrado por `hotel_id`.

## Verificacion automatica

- `php -l app/models/OperacionDiaria.php`: OK.
- `php -l app/views/operacion/diaria.php`: OK.
- `php -l tools/saas/preflight_operacion_diaria.php`: OK.
- `preflight_operacion_diaria.php`: `OK: 32`, `WARNING: 0`, `ERROR: 0`.
- HTTP sin sesion a `/operacion/diaria`: `303` a login.
- SQL read-only de conteos base:
  - `reservaciones`: 17.
  - `reservacion_pagos`: 19.
  - `reservacion_abonos`: 3.
  - `movimientos_caja`: 1404.
  - `tareas_operativas`: 4.
  - `documentos`: 5.
- `git diff --check`: sin errores, solo warnings CRLF.

## QA manual diferida

Por instruccion del usuario, QA manual queda diferida.

Pruebas recomendadas:

1. Abrir `/operacion/diaria`.
2. Confirmar que aparecen tarjetas de CxC estimada y CxC pendientes.
3. Confirmar que el panel "KPIs financieros estimados" aparece debajo del resumen.
4. Confirmar que el enlace "Cuentas por cobrar" abre `/cuentas-por-cobrar`.
5. Confirmar que no hay botones de cobro, pago, abono ni Caja.
6. Confirmar que no hay formularios ni POST.

## Rollback

1. Revertir el commit de 8A-A.
2. No tocar datos.
3. No tocar Caja ni `/api/sync`.

## Siguiente accion segura

Cierre tecnico 8A-F o, si se requiere seguir avanzando sin QA manual, solo contrato de
3D antes de cualquier pago proveedor con Caja.
