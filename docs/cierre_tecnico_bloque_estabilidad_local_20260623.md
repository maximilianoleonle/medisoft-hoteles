# Cierre tecnico de bloque - estabilidad local 2026-06-23

Estado: `CIERRE_TECNICO_BLOQUE_ESTABILIDAD_LOCAL_QA_TECNICA_COMPLETADA`.

## Objetivo

Consolidar el estado tecnico local despues de las implementaciones y
correcciones recientes en inventario, proveedores, compras, CxP, CxC, Centro
Documental, tareas, mantenimiento, limpieza, Personal, nomina administrativa,
Caja, reportes y tableros.

Este cierre no sube nada a produccion y no autoriza nuevas migraciones ni
cambios de datos.

## Resultado automatico local

Comandos principales ejecutados:

```bash
php tools/saas/health_check_fase_1a.php
php tools/saas/preflight_nomina_administrativa_suite.php
php tools/saas/preflight_tablero_ejecutivo.php
php tools/saas/preflight_arqueo_metodos_pago.php
```

Resultados:

- Health general: `OK: 413`, `WARNING: 0`, `ERROR: 0`.
- Suite nomina administrativa: `OK: 4`, `WARNING: 0`, `ERROR: 0`.
- Preflight tablero ejecutivo: `OK: 92`, `WARNING: 2`, `ERROR: 0`.
- Preflight arqueo por metodos: `OK: 39`, `WARNING: 3`, `ERROR: 0`.

Resultado general del bloque: `PASS_WITH_WARNINGS_ALLOWED`.

## Warnings aceptados y explicados

### Tablero Ejecutivo

- `ledger_laboral` no existe.
- Clasificacion: fuente opcional.
- Estado: aceptado por diseno; el tablero degrada ese KPI y existen fuentes
  laborales alternativas con `hotel_id` sano.

- `logs_auditoria` tiene filas `auth.*` sin `hotel_id`.
- Clasificacion: auditoria global de acceso.
- Estado: aceptado por diseno; no representa auditoria operativa de hotel ni se
  usa para automatizaciones por hotel.

### Arqueo por metodos

- Existen cortes locales historicos con diferencias explicadas por movimientos
  posteriores al cierre.
- Cortes diagnosticados previamente: `#15`, `#21`, `#48`, `#149`.
- Estado: aceptado como dato historico/local. No recalcular ni corregir sin
  autorizacion explicita.

## Modulos tecnicamente estabilizados

### Inventario

- Contrato moderno activo con:
  - `inventario_productos`;
  - `movimientos_inventario`;
  - `inventario_config_habitacion`.
- Tablas legacy congeladas y controladas.
- Ajustes de inventario validados con hotel actual y auditoria minima.

### Proveedores

- Catalogo por hotel con validaciones de RFC/nombre activo.
- Ficha read-only e historial disponibles.
- Sin pagos, Caja ni CxP directa desde Proveedores.

### Compras

- Compras minimas, borradores, detalle, recepcion y reportes read-only
  controlados.
- Recepcion real vinculada a inventario.
- Sin pagos ni documentos propios de compra fuera del Centro Documental.

### Cuentas por pagar

- Base CxP, preview, simulador, pago con Caja y reversion controlada.
- Pagos y reversiones viven en servicios transaccionales.
- Trazabilidad con Caja y cortes.

### Cuentas por cobrar

- Base CxC, generacion manual desde reservacion, simulador de cobro, cobro con
  Caja y reversion controlada.
- Flujo protegido con saldo, corte abierto, CSRF y token de un solo uso.

### Centro Documental

- Carga segura, detalle, descarga, metadata, archivado/restauracion, baja
  logica y partial contextual por entidad.
- Fichas contextuales cubiertas: proveedor, compra, CxP, huesped, reservacion y
  trabajador.
- Sin exposicion de `storage_path` en vistas.

### Tareas, mantenimiento y limpieza

- Tareas operativas con alta manual, asignacion, estados y agenda/reporte
  read-only.
- Creacion contextual desde mantenimiento y limpieza.
- Sin movimientos de Caja ni nomina automatica.

### Personal y nomina administrativa

- CRUD de trabajadores.
- Ledger laboral manual: conceptos, anticipos, prestamos y asistencias.
- Simulador de pago laboral con Caja.
- Pago laboral con Caja y reversion controlada.
- Periodos de pre-nomina, preview, snapshot persistente, aprobacion/anulacion,
  recibos informativos, export CSV, pago individual desde snapshot, conciliacion
  de pagos, auditoria consolidada y expediente administrativo.
- Frontera de nomina oficial protegida: no hay nomina oficial, CFDI, timbrado
  ni dispersion.

### Reportes y tableros

- Tablero operativo diario.
- Conciliacion financiera.
- Arqueo por metodos de pago.
- Tablero Ejecutivo read-only.
- Reporte gerencial sin archivado automatico por lectura directa.
- Perfil operativo de huesped.
- Reportes de mantenimiento y limpieza.

## Superficies listas para QA manual/redisenio visual

Estas vistas ya pueden entrar a redisenio visual o QA manual final, siempre que
se respeten formularios, `action`, `method`, `name`, CSRF, hidden inputs y
submits dentro de su form:

- `documentos/index.php` y `documentos/ver.php`.
- `trabajadores/index.php`, `trabajadores/ver.php`, reportes y pantallas de
  pre-nomina administrativa.
- `proveedores` listado/ficha.
- `compras` listado/detalle/reporte/recepcion.
- `cuentas-por-pagar` detalle/simulador/pagos/reversion.
- `cuentas-por-cobrar` detalle/simulador/cobros/reversion.
- `tareas` listado/detalle/reporte/agenda.
- `reportes/ejecutivo.php`, `operacion/diaria`, `conciliacion-financiera` y
  `caja/arqueo-metodos`.

## Limites preservados

No se tocaron en este cierre:

- produccion;
- migraciones;
- base de datos;
- rutas;
- controladores;
- modelos operativos;
- servicios de Caja;
- permisos;
- auth operativo;
- PWA/offline;
- `service-worker.js`;
- `pwa.js`;
- `offline-data.js`;
- `reservaciones-offline.js`;
- `/api/sync`.

`/api/sync` permanece bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Siguiente paso recomendado

Con el health general en cero warnings, el siguiente bloque seguro es elegir una
de estas rutas:

1. QA manual final de vistas estables antes de produccion.
2. Redisenio visual gradual de vistas estables, empezando por documentos,
   trabajadores, proveedores/compras/CxP o tableros.
3. Preparacion de despliegue a produccion con checklist, backup SQL valido y
   migraciones ya auditadas.

No ejecutar produccion ni correcciones de datos historicos sin nueva
autorizacion explicita.
