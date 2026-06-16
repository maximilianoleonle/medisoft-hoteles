# Fase NP-F-F - Cierre tecnico reporte read-only de Personal

Estado: `BLOQUE_NP_F_REPORTE_PERSONAL_CERRADO_QA_DIFERIDA`

Fecha: 2026-06-16

## Objetivo

Cerrar tecnicamente el reporte read-only de Personal implementado en NP-F-A, confirmando
que no se abrieron flujos financieros ni escrituras fuera de alcance.

## Alcance revisado

- GET `/trabajadores/reporte`.
- `TrabajadorController::reporteAction()`.
- `Trabajador::reporteReadOnlyPorHotel()`.
- Vista `trabajadores/reporte.php`.
- Enlace GET desde `trabajadores/index.php`.
- Health checker y preflight laboral.
- Documentacion de QA, rollback, fuentes de verdad y decisiones.

## Resultado de revision tecnica

- Ruta registrada como GET y protegida por `before()` del controller.
- No hay POST nuevo para el reporte.
- No hay CSRF porque no hay formulario de escritura en el reporte.
- Las consultas derivan `hotel_id` del contexto de sesion.
- La vista no acepta `hotel_id` por query ni formulario.
- La vista no muestra rutas internas de documentos.
- La vista mantiene copy explicito de no nomina, no pagos, no abonos y no Caja.

## Resultado de auditoria de seguridad

- Sin escrituras nuevas en `trabajador_*`.
- Sin escrituras en `tareas_operativas`.
- Sin escrituras en documentos.
- Sin integracion con Caja.
- Sin pagos reales.
- Sin abonos o liquidaciones.
- Sin cambios en `/api/sync`.
- HTTP sin sesion a `/trabajadores/reporte` redirige/bloquea con `303`.

## Verificaciones automaticas registradas

- `php -l` en archivos PHP modificados: OK.
- `preflight_personal_ledger.php`: `ERROR: 0`.
- `health_check_fase_1a.php`: `ERROR: 0`, warnings historicos permitidos.
- SQL read-only: `trabajadores`, `trabajador_pagos`, `trabajador_anticipos`,
  `trabajador_prestamos`, `trabajador_asistencias` y `tareas_operativas` siguen en 0;
  `movimientos_caja` se mantiene en 1403.
- `git diff --check`: sin errores; solo warnings CRLF del entorno.

## QA manual diferida

El usuario pidio omitir QA manual temporalmente. El bloque queda cerrado tecnicamente,
pero no validado manualmente en navegador.

Prueba manual futura:

- Entrar con sesion de hotel.
- Abrir `/trabajadores/reporte`.
- Confirmar estado vacio correcto si no hay trabajadores.
- Confirmar datos correctos si hay trabajadores.
- Confirmar ausencia de formularios POST, botones de nomina, pago, abono o Caja.
- Confirmar navegacion hacia Personal y ficha de trabajador.

## Rollback

- Revertir `feat(phase-np): add read-only worker report`.
- Revertir este cierre documental si se reabre NP-F.
- DB: no aplica; no se escribieron datos ni migraciones.

## Siguiente paso recomendado

Si se continua sin QA manual, el siguiente bloque seguro debe ser contrato documental
independiente. No abrir nomina, pagos reales, abonos/liquidaciones ni Caja sin autorizacion
explicita.
