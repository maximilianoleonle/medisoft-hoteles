# Fase 5E-L-B - QA rollback local de snapshots de pre-nomina

Estado formal:
`QA_5E_L_B_ROLLBACK_SNAPSHOT_PRENOMINA_COMPLETADO_MANUAL_PENDIENTE`.

## Objetivo

Validar automaticamente que la implementacion 5E-L-A puede cerrar, aprobar y
anular snapshots de pre-nomina sin dejar datos persistidos durante una prueba
local reversible.

Esta fase no agrega nuevas rutas, migraciones, vistas operativas ni logica de
negocio. Documenta y consolida la prueba rollback local agregada para 5E-L-A.

## Prueba ejecutada

Comando:

```bash
docker compose exec -T app php tools/saas/probar_nomina_periodo_snapshot.php
```

Resultado:

- crea hotel temporal dentro de transaccion;
- vincula usuario existente al hotel temporal;
- crea trabajador temporal activo;
- crea concepto laboral temporal a favor;
- cierra snapshot persistente de pre-nomina;
- bloquea cierre duplicado del mismo rango;
- aprueba el snapshot;
- bloquea anulacion sin motivo;
- anula con motivo;
- revierte toda la transaccion;
- confirma que no quedan hoteles, trabajadores, conceptos, snapshots, detalles ni
  eventos temporales persistidos.

## Validaciones automaticas registradas

- `php -l` OK en:
  - `app/models/Trabajador.php`;
  - `app/services/TrabajadorNominaPeriodoService.php`;
  - `tools/saas/probar_nomina_periodo_snapshot.php`;
  - `tools/saas/preflight_personal_pagos_caja.php`;
  - `tools/saas/health_check_fase_1a.php`.
- `preflight_personal_pagos_caja.php`: `OK: 60`, `WARNING: 0`, `ERROR: 0`.
- `health_check_fase_1a.php`: `OK: 316`, `WARNING: 25`, `ERROR: 0`.

Los warnings del health son historicos/de contexto de montaje y no bloquean esta
fase.

## Alcance validado

- Cierre persistente de snapshot.
- Bloqueo de duplicado por hotel/rango.
- Aprobacion administrativa.
- Anulacion con motivo obligatorio.
- Rollback local sin persistencia.
- Separacion de pagos reales con Caja.

## Fuera de alcance

Esta fase no valida manualmente la experiencia en navegador. Sigue pendiente:

- cerrar snapshot desde `/trabajadores/nomina/periodos`;
- abrir el detalle del snapshot;
- aprobar desde la vista;
- probar anulacion sin motivo y con motivo desde la vista;
- confirmar visualmente mensajes, botones y estados.

No autoriza nomina oficial, CFDI, timbrado, dispersion, pago masivo,
liquidaciones automaticas, movimientos de Caja, PWA/offline ni `/api/sync`.

## Siguiente paso seguro

QA manual en navegador de 5E-L-A. Si pasa, entonces si corresponde crear un cierre
documental 5E-L-F con confirmacion manual del usuario.
