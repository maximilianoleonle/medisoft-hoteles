# Fase 5E-L-A - Cierre/aprobacion persistente de pre-nomina

Estado: implementado en local; QA manual pendiente.

## Alcance

Esta fase agrega snapshots persistentes para periodos de pre-nomina del hotel.
El snapshot congela la lectura del preview laboral por rango, permite aprobacion
administrativa y permite anulacion con motivo.

No es nomina oficial. No genera CFDI, timbrado, dispersion, pago masivo,
liquidacion automatica de anticipos/prestamos ni movimientos de Caja.

## Componentes

- Migracion:
  `migrations/20260621_001_fase_5e_l_a_nomina_periodos_persistentes.sql`.
- Modelo:
  `src/app/models/Trabajador.php`.
- Servicio:
  `src/app/services/TrabajadorNominaPeriodoService.php`.
- Controlador:
  `src/app/controllers/TrabajadorController.php`.
- Vistas:
  `src/app/views/trabajadores/nomina_periodos.php`.
  `src/app/views/trabajadores/nomina_periodo_detalle.php`.
- Checkers:
  `src/tools/saas/preflight_personal_pagos_caja.php`.
  `src/tools/saas/health_check_fase_1a.php`.

## Rutas

- `GET /trabajadores/nomina/periodos`.
- `GET /trabajadores/nomina/periodos/preview`.
- `GET /trabajadores/nomina/periodos/{id}`.
- `POST /trabajadores/nomina/periodos/cerrar`.
- `POST /trabajadores/nomina/periodos/{id}/aprobar`.
- `POST /trabajadores/nomina/periodos/{id}/anular`.

Los POST usan CSRF, token de un solo uso y permiso de escritura existente.

## Tablas

- `trabajador_nomina_periodos`.
- `trabajador_nomina_periodo_detalles`.
- `trabajador_nomina_periodo_eventos`.

La llave unica `hotel_id + fecha_inicio + fecha_fin` bloquea doble cierre del
mismo rango dentro del mismo hotel.

## Reglas operativas

1. Solo se permite cerrar periodos con trabajadores activos.
2. El cierre reutiliza `nominaPreviewPorHotel` y bloquea si hay trabajadores con
   estado no elegible.
3. Aprobar cambia el estado del snapshot; no recalcula ni modifica el detalle.
4. Anular exige motivo y no borra registros fisicamente.
5. Los eventos quedan trazados por usuario.
6. No toca PWA/offline, IndexedDB, cache names ni `/api/sync`.

## QA manual

1. Aplicar la migracion local con backup SQL previo.
2. Abrir `/trabajadores/nomina/periodos`.
3. Elegir un rango con saldo laboral elegible.
4. Cerrar snapshot.
5. Confirmar que aparece en el historial y abre su detalle.
6. Intentar cerrar de nuevo el mismo rango y confirmar bloqueo.
7. Aprobar el snapshot cerrado.
8. Confirmar que el detalle no cambia al aprobar.
9. Anular un snapshot con motivo y confirmar evento.
10. Confirmar que no se crean movimientos de Caja ni pagos laborales.

## Rollback

Ver `docs/rollback-cola.md`, seccion `Rollback Fase 5E-L-A`.
