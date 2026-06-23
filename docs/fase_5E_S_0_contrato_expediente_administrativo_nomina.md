# Fase 5E-S-0 - Contrato expediente administrativo de nomina

Estado formal:
`CONTRATO_5E_S_0_EXPEDIENTE_ADMINISTRATIVO_NOMINA_COMPLETADO`.

Fecha: 2026-06-22.

## Objetivo

Definir el contrato para una futura capa read-only de expediente administrativo
de nomina que agrupe evidencias de pre-nomina, snapshots aprobados, pagos Caja,
reversiones, auditoria consolidada y documentos relacionados.

Esta fase es solo documental. No agrega codigo, rutas, controladores, modelos,
servicios, vistas, migraciones, permisos, datos, storage, Caja, PWA/offline ni
`/api/sync`.

## Contexto

El bloque 5E ya cuenta en local con:

- ledger laboral informativo;
- pago laboral con Caja;
- reversion controlada de pagos laborales;
- historial read-only de pagos laborales;
- recibos/reportes informativos;
- pre-nomina con snapshots persistentes;
- aprobacion/anulacion administrativa de snapshots;
- pago individual desde snapshot aprobado;
- trazabilidad fuerte del pago hacia snapshot y detalle;
- conciliacion read-only de pagos snapshot;
- auditoria consolidada read-only de nomina.

Despues de la auditoria consolidada, el siguiente paso seguro no es todavia
nomina oficial, CFDI, timbrado, dispersion ni pago masivo. El siguiente paso
seguro es un expediente administrativo que permita decidir si un periodo esta
listo para procesarse fuera o dentro de una futura fase formal.

## Principio central

El expediente administrativo debe ser GET/read-only.

Debe consolidar evidencias, marcar bloqueos y preparar exportaciones internas,
pero no debe crear obligaciones oficiales ni modificar datos operativos.

## Concepto de expediente

Un expediente administrativo de nomina es una lectura agrupada de uno o mas
snapshots aprobados, con sus trabajadores, pagos Caja, saldos de auditoria,
reversiones, documentos y eventos relacionados.

No es:

- nomina oficial;
- CFDI;
- timbrado;
- dispersion bancaria;
- pago masivo;
- poliza contable;
- liquidacion automatica;
- cierre contable definitivo.

## Pantallas futuras candidatas

Una futura fase 5E-S-A podria agregar:

```text
GET /trabajadores/nomina/expediente
GET /trabajadores/nomina/expediente/exportar
```

Opcionalmente, si se justifica por ergonomia y sin duplicar logica:

```text
GET /trabajadores/nomina/expediente/periodo/{id}
GET /trabajadores/nomina/expediente/trabajador/{id}
```

Todas estas rutas futuras deben seguir siendo read-only.

## Fuentes de lectura permitidas

Una futura implementacion puede leer, si existen en el entorno:

- `trabajadores`;
- `trabajador_nomina_periodos`;
- `trabajador_nomina_periodo_detalles`;
- `trabajador_pagos_caja`;
- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- tablas de conceptos laborales;
- tablas de anticipos y prestamos;
- tablas de asistencia laboral;
- tablas de documentos vinculados;
- tablas de auditoria del sistema;
- resultados read-only equivalentes a la auditoria consolidada 5E-R-A.

Toda lectura debe quedar scoped por `hotel_id`.

## Informacion minima del expediente

Una futura vista deberia mostrar, como minimo:

- periodo o rango consultado;
- estado del snapshot;
- estado de auditoria consolidada;
- trabajadores incluidos;
- bruto congelado;
- deducciones congeladas;
- pagos Caja aplicados;
- reversiones detectadas;
- saldo de auditoria;
- documentos laborales vinculados;
- recibos informativos disponibles;
- eventos relevantes de auditoria;
- usuario y fecha de aprobacion/anulacion del snapshot;
- bloqueos de preparacion.

## Estados administrativos sugeridos

El expediente puede mostrar estados de lectura como:

- `Listo para revision`: snapshot aprobado y sin bloqueos criticos.
- `Con pendientes`: existen saldos de auditoria o pagos parciales.
- `Requiere correccion`: hay filas en estado `Revisar`.
- `Bloqueado`: falta aprobacion del snapshot o hay inconsistencias criticas.
- `Anulado`: el snapshot origen fue anulado.

Estos estados son informativos. No deben cambiar datos.

## Bloqueos minimos sugeridos

Una futura implementacion debe marcar como bloqueo, al menos:

- snapshot no aprobado;
- snapshot anulado;
- auditoria consolidada con estado `Revisar`;
- pagos Caja mayores al pendiente congelado;
- relacion de pago parcial entre periodo y detalle;
- cruce de hotel;
- movimiento Caja faltante;
- saldo de auditoria pendiente cuando el expediente se quiera marcar como
  completo;
- trabajador inactivo sin razon documentada;
- falta de documento laboral requerido, si el hotel configura ese requisito en
  una fase futura.

## Exportacion futura

Una futura exportacion CSV debe:

- generarse en memoria;
- no escribir en storage;
- incluir filtros aplicados;
- incluir totales por periodo, trabajador y estado administrativo;
- incluir bloqueos detectados;
- evitar datos sensibles no necesarios;
- dejar claro que el archivo es administrativo e informativo.

Una futura exportacion PDF solo podria implementarse en fase separada y con
contrato especifico, para no mezclar formato de recibos con expediente.

## Cambios futuros permitidos solo con autorizacion

Una futura 5E-S-A podria tocar, con autorizacion explicita:

- rutas GET read-only;
- controlador de Personal/Nomina;
- modelo `Trabajador` con consultas read-only;
- vista del expediente administrativo;
- exportador CSV en memoria;
- checkers CLI/read-only;
- enlaces desde auditoria consolidada, periodos o trabajador.

No deberia requerir migraciones si se mantiene como lectura derivada.

## Prohibido por este contrato

5E-S-0 no autoriza:

- crear rutas POST;
- registrar pagos;
- revertir pagos;
- modificar Caja;
- modificar cortes;
- modificar movimientos de Caja;
- modificar snapshots;
- recalcular snapshots;
- reabrir periodos;
- aprobar o anular snapshots;
- crear nomina oficial;
- crear recibos oficiales;
- CFDI;
- timbrado;
- dispersion bancaria;
- pago masivo;
- polizas contables;
- liquidacion automatica de anticipos o prestamos;
- migraciones;
- backfill;
- escritura en storage;
- cambios en permisos/auth;
- cambios en PWA/offline, IndexedDB, cache names o `/api/sync`.

## QA futura minima

1. Confirmar que las rutas futuras son GET/read-only.
2. Confirmar que no hay formularios POST ni CSRF en la vista read-only.
3. Confirmar que los filtros no escriben datos.
4. Confirmar que el expediente muestra el periodo `#4` y el trabajador de QA.
5. Confirmar que pagos Caja `$1.00` y saldo auditoria `$99.00` se leen como
   pendientes administrativos, no como nomina oficial.
6. Confirmar que un estado `Revisar` bloquea el expediente.
7. Confirmar que snapshot anulado queda bloqueado o marcado como anulado.
8. Confirmar que el CSV se genera en memoria.
9. Confirmar que no se escribe en storage.
10. Ejecutar preflight de pagos laborales Caja con `ERROR: 0`.
11. Ejecutar health general con los errores historicos conocidos fuera de esta
    fase.
12. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

## Rollback futuro esperado

Si una futura 5E-S-A falla antes de produccion:

1. Retirar rutas GET agregadas.
2. Retirar metodos del controlador.
3. Retirar consultas read-only del modelo.
4. Eliminar la vista del expediente administrativo.
5. Retirar enlaces agregados desde vistas existentes.
6. Revertir checkers si fueron modificados.

No deberia requerir rollback SQL si se respeta este contrato.

## Criterio de avance futuro

Una futura 5E-S-A solo debe iniciar con autorizacion explicita para tocar rutas
GET, controlador, modelo read-only, vista, exportador CSV y checkers.
