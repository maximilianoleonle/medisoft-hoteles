# Fase NP-F-C - Health baseline Personal detalle redisenado

Estado: `IMPLEMENTACION_NP_F_C_HEALTH_BASELINE_PERSONAL_DETALLE_QA_TECNICA_LOCAL_COMPLETADA`.

## Objetivo

Actualizar el health general para reconocer las marcas actuales de la ficha de
Personal despues del redisenio visual de `trabajadores/ver.php`.

La vista ya conservaba el contrato funcional:

- CRUD y baja/reactivacion controladas.
- Reporte GET/read-only.
- Cuenta laboral manual con conceptos, anticipos, prestamos y asistencias.
- Pago laboral con Caja mediante formulario POST, CSRF y token de un solo uso.
- Reversion de pago laboral con POST, CSRF y token.
- Sin rutas internas de archivos ni referencias directas a tablas de Caja.

## Alcance implementado

- Se ajusto `src/tools/saas/health_check_fase_1a.php`.
- El checker acepta las marcas visuales actuales:
  - `Cuenta del trabajador` + `wk-ledger-grid` como bloque de ledger laboral.
  - `$ledgerSaldoInformativo` como marca del saldo laboral calculado.
  - `Pagar al trabajador (desde Caja)` como panel de pago laboral.
- No se modifico la vista.
- No se cambiaron formularios, rutas, nombres de inputs, CSRF ni submits.

## QA tecnica

Comandos ejecutados:

```bash
php -l /var/www/html/tools/saas/health_check_fase_1a.php
php tools/saas/health_check_fase_1a.php
```

Resultados:

- Lint PHP: OK.
- Health general: `OK: 413`, `WARNING: 0`, `ERROR: 0`.

## Limites

No se tocaron:

- vistas;
- rutas;
- controladores;
- modelos;
- servicios;
- migraciones;
- base de datos;
- Caja;
- nomina oficial;
- produccion;
- PWA/offline;
- `/api/sync`.
