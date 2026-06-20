# Fase 10A-A - Preflight tablero ejecutivo integral read-only

## Estado

`PREFLIGHT_10A_A_TABLERO_EJECUTIVO_READONLY_COMPLETADO`

## Objetivo

Agregar una herramienta CLI de diagnostico para el futuro tablero ejecutivo integral,
sin construir pantalla, sin registrar rutas y sin modificar datos.

La fase valida que las fuentes transversales necesarias para KPIs ejecutivos puedan
leerse de forma segura antes de tocar controlador, modelo, vista o navegacion.

## Cambios realizados

- Se agrega `src/tools/saas/preflight_tablero_ejecutivo.php`.
- Se extiende `src/tools/saas/health_check_fase_1a.php` para vigilar 10A-A.
- No se agrega `GET /reportes/ejecutivo`.
- No se agrega `POST /reportes/ejecutivo`.
- No se agregan controlador, modelo, vista, formulario, migracion ni escritura.

## Fuentes diagnosticadas

El preflight revisa disponibilidad, columnas minimas, scope por hotel y KPIs
candidatos sobre:

- `hoteles`;
- `reservaciones`;
- `reservacion_habitaciones`;
- `habitaciones`;
- `tipos_habitacion`;
- `huespedes`;
- `cuentas_por_cobrar`;
- `cuentas_por_cobrar_movimientos`;
- `cuentas_por_pagar`;
- `cuentas_por_pagar_movimientos`;
- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `compras`;
- `compra_detalles`;
- `proveedores`;
- `inventario_productos`;
- `movimientos_inventario`;
- `tareas_operativas`;
- `tarea_eventos`;
- `trabajadores`;
- `ledger_laboral`, como fuente opcional;
- `documentos`;
- `documento_entidades`;
- `logs_auditoria`.

## Guardas read-only

- Solo ejecuta por CLI.
- Exige `APP_ENV=local`.
- Abre transaccion `START TRANSACTION READ ONLY`.
- Cierra con `rollBack`.
- No registra rutas.
- Verifica ausencia de `POST /reportes/ejecutivo`.
- Detecta que `GET /reportes/gerencial-diario` no debe tratarse como lectura pura
  mientras archive notificaciones al abrirse.
- No toca permisos, auth, PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Resultado del preflight

Comando:

```bash
php tools/saas/preflight_tablero_ejecutivo.php
```

Resultado:

- `OK: 79`
- `WARNING: 5`
- `ERROR: 0`
- `Resultado general: PASS_WITH_WARNINGS_ALLOWED`

Warnings esperados:

- `GET /reportes/gerencial-diario` archiva notificaciones al abrirse.
- `archivarNotificacionReporteGerencialVisto` modifica `notificaciones`.
- `ledger_laboral` no existe y debe degradarse como KPI opcional.
- `huespedes` no tiene `hotel_id` directo; debe usarse mediante joins hotel-scoped.
- `logs_auditoria` tiene filas historicas con `hotel_id` nulo.

## Health general

Comando:

```bash
php tools/saas/health_check_fase_1a.php
```

Resultado tras integrar 10A-A:

- `OK: 290`
- `WARNING: 26`
- `ERROR: 0`
- `Resultado general: PASS_WITH_WARNINGS_ALLOWED`

El warning nuevo relevante confirma que `/reportes/gerencial-diario` no debe
reutilizarse como fuente ejecutiva read-only hasta separar lectura y archivado de
notificaciones.

## Siguiente paso seguro

La fase `10A-B-0` quedo documentada como contrato de pantalla ejecutiva
GET/read-only antes de tocar rutas, controlador, modelo o vista.

## Seguimiento 10A-B-A

El preflight fue extendido para validar tambien la pantalla
`GET /reportes/ejecutivo`.

Resultado actualizado:

- `OK: 86`
- `WARNING: 5`
- `ERROR: 0`
