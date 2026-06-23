# Fase 5E-O-B - UX lectura de pago desde snapshot de pre-nomina

Estado formal:
`IMPLEMENTACION_5E_O_B_UX_LECTURA_PAGO_SNAPSHOT_PRENOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-21.

## Objetivo

Reducir la confusion visual cuando un snapshot aprobado conserva su pendiente
congelado, pero el pago individual se calcula contra saldo vivo disponible.

## Alcance aplicado

- Vista modificada:
  `src/app/views/trabajadores/nomina_periodo_detalle.php`.
- En la columna `Pago Caja` se agrego una lectura compacta por trabajador:
  - `Snapshot`: pendiente congelado del detalle.
  - `Saldo vivo`: disponible recalculado por Caja.
  - `Maximo`: tope permitido para el pago individual.
- La misma lectura se muestra tambien cuando el pago esta bloqueado y existe
  evaluacion disponible.

## Limites respetados

- No cambia `action`, `method` ni `name` del formulario de pago.
- No quita CSRF ni token de un solo uso.
- No mueve el submit fuera del formulario.
- No agrega formularios anidados.
- No toca rutas, controladores, modelos, servicios, permisos, migraciones, base
  de datos, Caja, cortes, movimientos, storage, PWA/offline ni `/api/sync`.
- No cambia reglas de elegibilidad, monto maximo, transacciones ni auditoria.

## QA tecnica local

Comandos ejecutados:

```bash
docker compose exec -T app php -l app/views/trabajadores/nomina_periodo_detalle.php
docker compose exec -T app php tools/saas/preflight_personal_pagos_caja.php
docker compose exec -T app php tools/saas/health_check_fase_1a.php
```

Resultado:

- Lint PHP: OK.
- Preflight pagos laborales Caja: `OK: 65`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 320`, `WARNING: 25`, `ERROR: 0`.

## QA manual sugerida

1. Abrir un snapshot aprobado con trabajador elegible.
2. Confirmar que en `Pago Caja` aparecen `Snapshot`, `Saldo vivo` y `Maximo`.
3. Confirmar que `Maximo` coincide con el monto cargado por defecto.
4. Registrar un pago parcial y volver al snapshot.
5. Confirmar que el snapshot congelado conserva su pendiente original, mientras
   `Saldo vivo` y `Maximo` reflejan el disponible real.
