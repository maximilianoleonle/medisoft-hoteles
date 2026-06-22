# Fase 5E-M-B - QA tecnica reporte/export snapshots de pre-nomina

Estado formal:
`QA_5E_M_B_REPORTE_EXPORT_SNAPSHOTS_PRENOMINA_MANUAL_VALIDADA_EN_5E_M_F`.

## Objetivo

Validar por HTTP local que el reporte/export GET/read-only de snapshots
persistentes de pre-nomina implementado en 5E-M-A responde con sesion activa,
protege rutas sin sesion y no mueve Caja ni pagos laborales.

Esta fase no agrega codigo, rutas, migraciones, modelos, vistas, servicios,
permisos, storage, PWA/offline ni `/api/sync`.

## Contexto de prueba

- URL local: `http://localhost:8080`.
- Sesion temporal local: `adminmax` (`usuario_id=24`).
- Hotel: `Maximiliano` (`hotel_id=4`).
- Snapshot disponible: `trabajador_nomina_periodos.id=2`, estado `anulado`.

La sesion se creo solo como artefacto temporal de QA dentro del contenedor para
evitar cambios de usuarios, passwords, permisos o auth.

## Flujo HTTP validado

1. `GET /trabajadores/nomina/periodos/reporte` con sesion activa respondio
   HTTP `200`.
2. El HTML del reporte incluyo titulo de pre-nomina, enlace de exportacion y
   enlace al detalle del snapshot existente.
3. El HTML no mostro pantalla de login con sesion activa.
4. La vista propia del reporte mantiene formulario `GET`; el unico `POST`
   detectado en el HTML completo pertenece al logout global del layout.
5. `GET /trabajadores/nomina/periodos/exportar?estado=anulado&tipo_periodo=semanal`
   respondio HTTP `200`.
6. El CSV incluyo encabezado y el snapshot `#2` con estado `anulado`, periodo
   semanal `2026-06-15` a `2026-06-21` y motivo
   `QA manual 5E-L local`.
7. `GET /trabajadores/nomina/periodos/reporte` sin sesion respondio HTTP `303`
   a `/login`.
8. `GET /trabajadores/nomina/periodos/exportar` sin sesion respondio HTTP `303`
   a `/login`.
9. `POST /api/sync` con sesion activa sigue bloqueado:
   - HTTP `423`;
   - JSON `sync_temporarily_disabled`.

## Conteos sensibles

Antes y despues de la prueba HTTP:

- `movimientos_caja`: `1413`.
- `trabajador_pagos_caja`: `1`.
- `trabajador_nomina_periodos`: `1`.
- `trabajador_nomina_periodo_detalles`: `1`.
- `trabajador_nomina_periodo_eventos`: `3`.

Conclusion: el reporte y el export consultan snapshots persistentes existentes,
sin crear pagos laborales, movimientos de Caja, cortes, storage ni datos nuevos.

## Validaciones automaticas

- `php -l` OK en:
  - `config/routes.php`;
  - `app/controllers/TrabajadorController.php`;
  - `app/models/Trabajador.php`;
  - `app/views/trabajadores/nomina_periodos_reporte.php`.
- `tools/saas/preflight_personal_pagos_caja.php`:
  - `OK: 62`;
  - `WARNING: 0`;
  - `ERROR: 0`.
- `tools/saas/health_check_fase_1a.php`:
  - `OK: 317`;
  - `WARNING: 25`;
  - `ERROR: 0`.

Los warnings del health son historicos/de contexto de montaje y no bloquean esta
fase.

## Fuera de alcance

Esta QA tecnica no autoriza:

- nomina oficial;
- CFDI;
- timbrado;
- dispersion;
- pago masivo;
- liquidacion automatica de anticipos o prestamos;
- movimientos de Caja desde pre-nomina;
- auditoria por descarga;
- storage;
- PWA/offline;
- cambios en `/api/sync`.

## Confirmacion manual

El usuario confirmo que la prueba manual del reporte/export paso correctamente.
El cierre documental queda registrado en:

`docs/fase_5E_M_F_cierre_reporte_export_snapshots_prenomina.md`.
