# Fase 5E-R-0 - Contrato auditoria consolidada de nomina

Estado formal:
`CONTRATO_5E_R_0_AUDITORIA_CONSOLIDADA_NOMINA_READONLY_COMPLETADO`.

Fecha: 2026-06-22.

## Objetivo

Definir el contrato para una futura pantalla read-only que consolide la
trazabilidad laboral de Personal/Nomina en un solo punto de revision.

Esta fase es solo documental. No agrega codigo, rutas, controladores, modelos,
servicios, vistas, migraciones, permisos, datos, storage, Caja, PWA/offline ni
`/api/sync`.

## Contexto

El bloque 5E ya tiene flujos locales separados y validados para:

- preview de pre-nomina por periodo;
- cierre persistente de snapshot;
- aprobacion/anulacion administrativa;
- pago individual desde snapshot aprobado con Caja;
- trazabilidad fuerte hacia periodo y detalle;
- conciliacion read-only de pagos trazados;
- recibos/reportes/exportaciones informativas.

El siguiente paso seguro antes de pago masivo, nomina oficial, CFDI o
automatizaciones es una vista de auditoria que permita revisar todo el recorrido
sin escribir datos.

## Principio central

La auditoria consolidada debe ser GET/read-only.

Debe leer datos existentes, cruzarlos por hotel y periodo, y marcar
inconsistencias sin corregirlas automaticamente.

## Pantallas futuras candidatas

Una futura fase 5E-R-A podria agregar:

```text
GET /trabajadores/nomina/auditoria
GET /trabajadores/nomina/auditoria/exportar
```

Opcionalmente, si se justifica por ergonomia y sin duplicar logica:

```text
GET /trabajadores/nomina/auditoria/trabajador/{id}
GET /trabajadores/nomina/auditoria/periodo/{id}
```

Estas rutas futuras deben seguir siendo read-only.

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
- tablas de anticipos/prestamos;
- tablas de asistencia laboral;
- tablas de documentos vinculados;
- tabla de auditoria del sistema, solo para eventos laborales ya registrados.

Toda lectura debe quedar scoped por `hotel_id`.

## Informacion minima a consolidar

La futura vista deberia mostrar, como minimo:

- periodo o rango consultado;
- trabajador;
- estado del snapshot;
- bruto congelado;
- deducciones congeladas;
- pagos Caja aplicados;
- pendiente congelado;
- pagos reales vigentes;
- reversiones detectadas;
- movimiento de Caja asociado;
- corte y caja;
- referencia;
- usuario/fecha cuando el dato este disponible;
- estado de conciliacion: `OK`, `Revisar` o `Sin pago`.

## Reglas de lectura

La auditoria debe distinguir claramente:

- snapshot administrativo;
- saldo laboral vivo;
- pago laboral real con Caja;
- reversion controlada;
- movimiento de Caja;
- conciliacion read-only.

No debe presentar el snapshot como deuda viva sin explicar la diferencia.

## Reglas de conciliacion futura

Cada fila puede marcar `OK` cuando:

- el pago pertenece al hotel actual;
- el periodo pertenece al hotel actual;
- el detalle pertenece al periodo;
- el trabajador del pago coincide con el trabajador del detalle;
- el movimiento de Caja existe y pertenece al mismo hotel;
- el monto y referencia son consistentes;
- una reversion, si existe, esta ligada al pago correcto.

Debe marcar `Revisar` cuando:

- falte movimiento de Caja;
- falte periodo o detalle;
- haya cruce de hotel;
- haya relacion parcial;
- el monto no coincida;
- la referencia no coincida;
- el pago este revertido sin evidencia suficiente;
- exista cualquier dato ambiguo.

Debe marcar `Sin pago` cuando el snapshot tenga pendiente pero no exista pago
real vigente asociado.

## Exportacion futura

Una futura exportacion CSV debe:

- generarse en memoria;
- no escribir en storage;
- respetar filtros de hotel, periodo, trabajador, estado y conciliacion;
- incluir columnas suficientes para auditoria contable interna;
- evitar datos sensibles no necesarios.

## Cambios futuros permitidos solo con autorizacion

Una futura 5E-R-A podria tocar, con autorizacion explicita:

- rutas GET read-only;
- controlador de Personal/Nomina;
- modelo `Trabajador` con consultas read-only;
- vista de auditoria consolidada;
- exportador CSV en memoria;
- checkers CLI/read-only;
- enlaces desde pantallas de pre-nomina o trabajador.

## Prohibido por este contrato

5E-R-0 no autoriza:

- crear rutas POST;
- registrar pagos;
- revertir pagos;
- modificar Caja;
- modificar cortes;
- modificar snapshots;
- recalcular snapshots;
- reabrir periodos;
- aprobar/anular periodos;
- crear nomina oficial;
- CFDI;
- timbrado;
- dispersion bancaria;
- pago masivo;
- liquidacion automatica de anticipos o prestamos;
- migraciones;
- backfill;
- escritura en storage;
- cambios en PWA/offline, IndexedDB, cache names o `/api/sync`.

## QA futura minima

1. Confirmar que las rutas futuras son GET/read-only.
2. Confirmar que no hay formularios POST ni CSRF en la vista read-only.
3. Confirmar que los filtros no escriben datos.
4. Confirmar que el pago trazado de QA aparece con `OK`.
5. Confirmar que un periodo con pendiente y sin pago aparece como `Sin pago`.
6. Confirmar que pagos revertidos se muestran como revertidos y no como pagos
   vigentes.
7. Confirmar que el CSV se genera en memoria.
8. Confirmar que no se escribe en storage.
9. Ejecutar preflight de pagos laborales Caja con `ERROR: 0`.
10. Ejecutar health general con `ERROR: 0`.
11. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

## Rollback futuro esperado

Si una futura 5E-R-A falla antes de produccion:

1. Retirar rutas GET agregadas.
2. Retirar metodos del controlador.
3. Retirar consultas read-only del modelo.
4. Eliminar la vista de auditoria consolidada.
5. Retirar enlaces agregados desde vistas existentes.
6. Revertir checkers si fueron modificados.

No deberia requerir rollback SQL si se respeta este contrato.

## Criterio de avance futuro

Una futura 5E-R-A solo debe iniciar con autorizacion explicita para tocar rutas,
controlador, modelo read-only, vista, exportador CSV y checkers.
