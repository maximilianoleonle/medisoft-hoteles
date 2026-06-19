# Fase 7A-S-0 - Contrato reconciliacion CxC controlada

## Estado

`CONTRATO_7A_S_RECONCILIACION_CXC_CONTROLADA_COMPLETADO`

## Objetivo

Definir el camino seguro para reconciliar los datos historicos que bloquean una CxC
operativa, sin ejecutar todavia ninguna escritura.

Esta fase es solo contrato documental. No modifica base de datos, no crea rutas, no
crea migraciones, no crea cobros, no crea abonos, no toca Caja y no toca `/api/sync`.

## Estado previo

- 7A-0 contrato CxC read-only: completado.
- 7A-A reporte CxC read-only: completado.
- 7A-F cierre CxC read-only: completado.
- 7A-R diagnostico de reconciliacion read-only: completado.
- 7B-0 contrato CxC operativa sin Caja: completado, pero bloqueado para ejecucion
  por warnings historicos.

Hallazgos que bloquean CxC operativa:

- 3 pagos en `reservacion_pagos` con reservacion inexistente.
- 170 solicitudes en `solicitudes_factura` con reservacion inexistente.
- 3 reservaciones en Maximiliano Leon con saldo estimado negativo por doble cobertura
  de abono + pago completo.

## Principio rector

La reconciliacion no debe borrar historicos ni alterar finanzas sin politica explicita.

Primero se debe clasificar cada caso; despues se debe decidir si queda solo excluido de
CxC, archivado logicamente, asociado mediante migracion controlada o corregido con un
movimiento/auditoria especifica.

## Matriz de decision obligatoria

Antes de cualquier escritura futura se debe llenar una matriz con una decision por tipo
de dato:

| Caso | Opcion segura por defecto | Opciones futuras posibles | Requiere backup | Requiere auditoria |
| --- | --- | --- | --- | --- |
| Pagos huerfanos | Excluir de CxC operativa | marcar como historico excluido, asociar a reservacion valida, anular contablemente | si | si |
| Facturas huerfanas | Excluir de CxC operativa | conservar historico, archivar logicamente, asociar a reservacion valida | si | si |
| Saldos excedentes | Mostrar como excedente informativo | credito a favor, devolucion, ajuste, excluir de deuda inicial | si | si |

La opcion segura por defecto no requiere escritura: el reporte actual ya excluye pagos y
facturas huerfanas por `hotel_id + reservacion_id`, y etiqueta excedentes como
`excedente`.

## Regla para pagos huerfanos

No se deben convertir automaticamente en cobros ni pagos CxC.

Fase futura permitida solo con autorizacion:

1. Generar un preview read-only de pagos huerfanos.
2. Clasificar cada pago como:
   - prueba/demo;
   - historico migrado;
   - pago valido con reservacion perdida;
   - pago a anular contablemente.
3. Mostrar impacto estimado por hotel y fecha.
4. No escribir hasta tener backup y confirmacion manual.

Prohibido sin nuevo contrato:

- borrar filas de `reservacion_pagos`;
- moverlas a otra reservacion;
- crear movimientos de Caja;
- crear CxC desde ellas;
- modificar cortes.

## Regla para solicitudes de factura huerfanas

No se deben usar para crear saldos CxC.

Fase futura permitida solo con autorizacion:

1. Generar un preview read-only por rango de fechas, estatus, `requiere_factura` y
   monto.
2. Separar solicitudes facturables (`requiere_factura = si`) de solicitudes historicas
   no facturables (`requiere_factura = no`).
3. Clasificar cada bloque como:
   - historico informativo;
   - solicitud a archivar logicamente;
   - solicitud que debe asociarse a reservacion existente;
   - solicitud que debe permanecer fuera de CxC permanentemente.
4. No escribir hasta tener backup, politica y confirmacion manual.

Prohibido sin nuevo contrato:

- borrar filas de `solicitudes_factura`;
- crear o recrear reservaciones para hacerlas coincidir;
- emitir facturacion nueva;
- cambiar estatus masivamente;
- mezclar facturacion con Caja.

## Regla para excedentes

Un saldo negativo no es deuda a cobrar.

Fase futura permitida solo con autorizacion:

1. Generar preview read-only de excedentes.
2. Mostrar total, pagos, abonos, saldo raw y causa probable.
3. Definir politica:
   - credito a favor informativo;
   - devolucion futura;
   - ajuste contable;
   - mantener como excedente sin accion.
4. No tocar pagos, abonos ni Caja sin contrato financiero separado.

Prohibido sin nuevo contrato:

- convertir excedente en saldo a cobrar;
- borrar abonos demo;
- borrar pagos;
- crear devoluciones de Caja;
- alterar reservaciones cerradas.

## Requisitos antes de cualquier escritura futura

- Backup SQL completo y verificado con SHA256.
- Preview read-only de los registros candidatos.
- Matriz de decision aprobada por el usuario.
- Script o flujo con transaccion y rollback documentado.
- Auditoria con usuario, hotel, motivo, antes/despues y referencia.
- Preflight antes y despues.
- Verificacion de que `/api/sync` sigue bloqueado.
- QA manual puntual en navegador o CLI controlado segun el tipo de operacion.

## Subfases futuras sugeridas

### 7A-S-A Preview reconciliacion CxC

- Solo GET/read-only o script CLI read-only.
- Listar pagos huerfanos, facturas huerfanas y excedentes.
- Permitir exportar o copiar la matriz de decision.
- Sin escrituras.

Estado posterior: completado documentalmente en
`docs/fase_7A_S_A_preview_reconciliacion_cxc.md`.

### 7A-S-B Politica de clasificacion

- Documento de decision del usuario.
- Sin codigo obligatorio.
- Define si se excluyen, archivan, asocian o corrigen casos.

Estado posterior: completado documentalmente en
`docs/fase_7A_S_B_politica_clasificacion_cxc.md`.

### 7A-S-C Escritura controlada opcional

Solo si se autoriza despues:

- Backup previo.
- Script transaccional.
- Nada de Caja automatica.
- Auditoria obligatoria.
- Rollback funcional.

### 7A-S-F Cierre

- Preflight CxC.
- Health checker.
- SQL read-only de conteos antes/despues.
- Documentar si CxC operativa queda desbloqueada o sigue bloqueada.

Estado posterior: completado documentalmente en
`docs/fase_7A_S_F_cierre_reconciliacion_cxc.md`.

## Definition of Done de este contrato

- Documento creado.
- Resumen/cola/auditoria actualizados.
- Sin cambios PHP.
- Sin migraciones.
- Sin escrituras.
- Sin cambios en Caja, reservaciones, pagos, abonos, facturacion ni `/api/sync`.

## Siguiente accion segura

El bloque 7A-S queda cerrado. Preparar 7B-A solo como migracion base vacia si se
autoriza explicitamente tocar DB/migraciones.

No avanzar a CxC operativa ni a escrituras de reconciliacion todavia.
