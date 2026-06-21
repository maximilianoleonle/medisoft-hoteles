# Fase 5E-M-0 - Contrato reporte/export snapshots de pre-nomina

Estado formal:
`CONTRATO_5E_M_0_REPORTE_EXPORT_SNAPSHOTS_PRENOMINA_COMPLETADO`.

## Objetivo

Definir el contrato para una fase futura de consulta y exportacion read-only de
snapshots persistentes de pre-nomina.

Esta fase es solo documental. No agrega codigo, rutas, controladores, modelos,
vistas, servicios, migraciones, permisos, datos, storage, Caja, PWA/offline ni
`/api/sync`.

## Contexto

5E-L-A ya permite cerrar, aprobar y anular snapshots administrativos de
pre-nomina. 5E-L-F valido localmente el flujo completo sin generar pagos ni
movimientos de Caja.

El siguiente paso operativo natural es permitir que el hotel consulte y exporte
el historial de snapshots persistentes con filtros claros, sin modificar datos.

## Rutas candidatas futuras

Estas rutas quedan propuestas, no implementadas:

- `GET /trabajadores/nomina/periodos/reporte`
- `GET /trabajadores/nomina/periodos/exportar`

Ambas deben ser GET/read-only. Cualquier cambio de ruta requiere autorizacion
explicita en la fase de implementacion.

## Alcance futuro permitido

Una implementacion 5E-M-A podria:

1. Mostrar un reporte read-only de snapshots persistentes por hotel.
2. Filtrar por rango de fechas, estado (`cerrado`, `aprobado`, `anulado`), tipo
   de periodo y texto libre.
3. Mostrar totales por estado: snapshots, trabajadores, bruto, deducciones,
   pagos Caja aplicados historicos del snapshot, neto sugerido y pendiente
   sugerido.
4. Enlazar al detalle existente de cada snapshot.
5. Exportar CSV en memoria, sin storage, con los mismos filtros del reporte.
6. Reutilizar las tablas 5E-L-A:
   - `trabajador_nomina_periodos`;
   - `trabajador_nomina_periodo_detalles`;
   - `trabajador_nomina_periodo_eventos`.
7. Mantener aislamiento por `hotel_id`.

## Reglas obligatorias

1. El reporte y el CSV deben ser solo lectura.
2. No deben crear, aprobar, anular, reabrir ni recalcular snapshots.
3. No deben crear pagos laborales.
4. No deben crear movimientos de Caja.
5. No deben tocar cortes de Caja.
6. No deben liquidar anticipos ni prestamos.
7. No deben generar nomina oficial, CFDI, timbrado, dispersion, folios oficiales
   ni pagos masivos.
8. No deben escribir archivos en storage.
9. No deben enviar correo, links publicos ni reporte compartible.
10. No deben tocar PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Datos y privacidad

El reporte puede mostrar solo datos ya contenidos en el snapshot:

- folio/id del snapshot;
- periodo;
- estado;
- trabajador agregado o conteo;
- importes congelados;
- responsable de cierre/aprobacion/anulacion;
- motivo de anulacion;
- timestamps de eventos.

No debe mostrar contrasenas, tokens, rutas de storage, datos internos de sesion
ni informacion sensible no incluida en el snapshot administrativo.

## Implementacion futura requerida

Para implementar 5E-M-A se requiere autorizacion explicita para tocar, como
minimo:

- rutas;
- controlador;
- modelo/read-only;
- vista;
- export CSV;
- checkers;
- documentacion.

Si se decide agregar permisos nuevos, indices, migraciones, auditoria de descarga
o storage, eso requiere contrato separado antes de implementarlo.

## QA futura minima

1. Abrir reporte sin sesion y confirmar redireccion a login.
2. Abrir reporte con sesion de hotel y confirmar que no mezcla hoteles.
3. Filtrar por estado `cerrado`, `aprobado` y `anulado`.
4. Filtrar por rango de fechas.
5. Confirmar que cada fila enlaza al detalle existente.
6. Exportar CSV y confirmar que respeta filtros.
7. Confirmar que el CSV no crea archivos en storage.
8. Confirmar que no aparecen botones POST ni acciones de pago.
9. Confirmar que `movimientos_caja` y `trabajador_pagos_caja` no cambian.
10. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

## Rollback

Ver `docs/rollback-cola.md`, seccion `Rollback Fase 5E-M-0`.
