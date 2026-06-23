# Fase 5E-O-A - UX filtros de periodos de pre-nomina

Estado formal:
`IMPLEMENTACION_5E_O_A_UX_FILTROS_PERIODOS_PRENOMINA_QA_TECNICA_LOCAL_COMPLETADA`.

Fecha: 2026-06-21.

## Objetivo

Reducir la confusion operativa del formulario de periodos de pre-nomina, donde
`fecha_base`, `fecha_inicio` y `fecha_fin` se veian como tres filtros de fecha
equivalentes.

## Alcance aplicado

- Vista modificada:
  `src/app/views/trabajadores/nomina_periodos.php`.
- Se agregaron etiquetas visibles:
  - `Fecha base`
  - `Inicio manual`
  - `Fin manual`
- Se agregaron ayudas cortas para distinguir:
  - `Fecha base`: calculo automatico semanal/quincenal/mensual.
  - `Inicio manual` y `Fin manual`: rango obligatorio cuando el tipo es manual.
- Se ajusto la grilla visual del formulario para que los controles se lean como
  campos distintos y no como cajas de fecha repetidas.

## Limites respetados

- No cambia `action`, `method` ni `name` de los controles existentes.
- No quita inputs hidden ni mueve el submit fuera del formulario.
- No agrega formularios anidados.
- No toca rutas, controladores, modelos, permisos, migraciones, base de datos,
  Caja, cortes, movimientos, storage, PWA/offline ni `/api/sync`.
- No modifica calculos de pre-nomina ni reglas de validacion.

## QA tecnica local

Comandos ejecutados:

```bash
docker compose exec -T app php -l app/views/trabajadores/nomina_periodos.php
docker compose exec -T app php tools/saas/preflight_personal_pagos_caja.php
docker compose exec -T app php tools/saas/health_check_fase_1a.php
```

Resultado:

- Lint PHP: OK.
- Preflight pagos laborales Caja: `OK: 65`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 320`, `WARNING: 25`, `ERROR: 0`.

## QA manual sugerida

1. Abrir `Personal / Pre-nomina / Periodos`.
2. Confirmar que el formulario muestra claramente `Fecha base`,
   `Inicio manual` y `Fin manual`.
3. Seleccionar `Manual`.
4. Capturar `Inicio manual` y `Fin manual` con la misma fecha.
5. Evaluar.
6. Confirmar que aparece `Periodo seleccionado` y que ya no se muestra bloqueo
   por periodo manual invalido.
7. Confirmar que el cierre de snapshot sigue funcionando igual que antes.
