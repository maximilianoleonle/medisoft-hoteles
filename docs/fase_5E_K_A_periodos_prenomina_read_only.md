# Fase 5E-K-A - Periodos de pre-nomina read-only

Estado formal:
`PERIODOS_5E_K_A_PRENOMINA_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

## Objetivo

Implementar una pantalla GET/read-only para revisar periodos de pre-nomina sin cerrar,
aprobar, anular, persistir snapshots, registrar pagos, crear movimientos de Caja ni
modificar datos laborales.

La fase usa el contrato 5E-K-0 y reutiliza el calculo existente de
`Trabajador::nominaPreviewPorHotel()`.

## Rutas implementadas

- `GET /trabajadores/nomina/periodos`
- `GET /trabajadores/nomina/periodos/preview`

Ambas rutas son solo lectura y requieren sesion como el resto del modulo Personal.

## Archivos tocados

- `src/config/routes.php`
- `src/app/controllers/TrabajadorController.php`
- `src/app/models/Trabajador.php`
- `src/app/views/trabajadores/nomina_periodos.php`
- `src/app/views/trabajadores/index.php`
- `src/app/views/trabajadores/nomina_preview.php`
- `src/tools/saas/preflight_personal_pagos_caja.php`
- `src/tools/saas/health_check_fase_1a.php`

## Alcance funcional

La vista permite:

- elegir tipo de periodo semanal, quincenal, mensual o manual;
- definir fecha base o rango manual;
- filtrar por estado laboral y rol;
- decidir si los pagos Caja existentes se incluyen solo como lectura;
- ver tarjetas de periodos candidatos;
- abrir detalle read-only del periodo seleccionado;
- saltar al preview de pre-nomina existente;
- abrir recibos laborales informativos por trabajador.

## Garantias

5E-K-A mantiene:

- solo metodos GET;
- sin formularios POST;
- sin CSRF porque no hay escritura;
- sin pago masivo;
- sin cierre real;
- sin aprobacion real;
- sin anulacion real;
- sin escritura en `trabajadores`;
- sin escritura en `trabajador_pagos`;
- sin escritura en `trabajador_anticipos`;
- sin escritura en `trabajador_prestamos`;
- sin escritura en `trabajador_pagos_caja`;
- sin escritura en `movimientos_caja`, `cortes_caja` o `cajas`;
- sin migraciones;
- sin permisos/auth nuevos;
- sin PWA/offline, IndexedDB, cache names ni `/api/sync`.

## QA manual sugerida

1. Abrir `/trabajadores/nomina/periodos` con sesion activa.
2. Confirmar que carga sin 404.
3. Cambiar tipo semanal, quincenal y mensual.
4. Probar rango manual con `fecha_inicio` y `fecha_fin` validos.
5. Probar rango manual invalido y confirmar bloqueo visible.
6. Entrar a `Detalle` de un periodo.
7. Abrir `Preview completo` y confirmar que conserva periodo/filtros.
8. Abrir `Recibo` de un trabajador y confirmar que conserva periodo.
9. Confirmar que no hay botones de cierre, aprobacion, anulacion o pago real.
10. Confirmar que no se genera movimiento de Caja ni cambio de datos.

## Validaciones automaticas esperadas

- `php -l` OK en PHP tocados.
- `preflight_personal_pagos_caja.php` termina con `ERROR: 0`.
- `health_check_fase_1a.php` termina con `ERROR: 0`.
- Ruta local sin sesion debe redirigir a login sin 404.