# Fase 7A-S-B - Politica de clasificacion CxC

## Estado

`POLITICA_7A_S_B_CLASIFICACION_CXC_CONSERVADORA_COMPLETADA`

## Objetivo

Definir la politica vigente para clasificar los registros detectados en el preview
7A-S-A, sin ejecutar escrituras y sin habilitar CxC operativa.

Esta fase es una decision documental. No modifica base de datos, no crea rutas, no crea
migraciones, no crea cobros, no toca Caja y no toca `/api/sync`.

## Base de decision

Documento base: `docs/fase_7A_S_A_preview_reconciliacion_cxc.md`.

Datos clasificados:

- 3 pagos huerfanos.
- 170 solicitudes de factura huerfanas.
- 5 solicitudes de factura scoped validas.
- 3 reservaciones con excedente.

## Politica vigente

Se adopta la politica conservadora por defecto:

| Grupo | Politica vigente | Efecto operativo |
| --- | --- | --- |
| Pagos huerfanos | Excluir de CxC operativa | No crean deuda, cobro, pago nuevo ni Caja |
| Facturas huerfanas | Excluir de CxC operativa | No crean deuda ni se enlazan al reporte CxC operativo futuro |
| Facturas scoped validas | Mantener en reporte read-only | Siguen visibles como contexto, no como cobro |
| Excedentes | Mantener como excedente informativo | No se convierten en deuda ni en devolucion automatica |

## Reglas por grupo

### Pagos huerfanos

Politica:

- Se mantienen como historico sensible.
- Quedan excluidos de cualquier CxC operativa futura.
- No se asocian automaticamente a reservaciones.
- No se anulan en esta fase.

Para cambiar esta politica se requiere:

- evidencia de la reservacion correcta;
- backup;
- script/flujo transaccional;
- auditoria;
- autorizacion explicita.

### Solicitudes de factura huerfanas

Politica:

- Se mantienen como historico sensible.
- Quedan excluidas de cualquier CxC operativa futura.
- No se borran.
- No se archivan logicamente todavia.
- No se asocian automaticamente a reservaciones.

Para cambiar esta politica se requiere:

- definir si se conservaran, archivaran logicamente o asociaran;
- backup;
- auditoria;
- autorizacion explicita;
- contrato separado si afecta facturacion real.

### Solicitudes de factura scoped validas

Politica:

- Se mantienen visibles en el reporte read-only actual.
- No se convierten por si solas en CxC operativa.
- No generan cobros ni Caja.

Para usarlas como origen de CxC futura se requiere:

- tabla CxC nueva autorizada;
- regla de origen y duplicados;
- simulador/preview;
- accion manual con CSRF;
- auditoria.

### Excedentes

Politica:

- Se mantienen como excedente informativo.
- No se convierten en deuda.
- No generan devolucion automatica.
- No borran pagos ni abonos.

Para cambiar esta politica se requiere contrato financiero separado:

- credito a favor;
- devolucion;
- ajuste contable;
- o anulacion/reclasificacion documentada.

## Lo que queda permitido

- Mantener `/cuentas-por-cobrar` como reporte read-only.
- Mantener KPIs CxC estimados/read-only.
- Preparar documentos o previews read-only.
- Planear CxC operativa con tabla nueva, siempre sin poblar desde historicos huerfanos.

## Lo que sigue prohibido

- Crear tabla `cuentas_por_cobrar` poblada con historicos.
- Crear cobros CxC.
- Crear pagos o abonos CxC.
- Crear movimientos de Caja.
- Asociar pagos/facturas huerfanas a reservaciones.
- Borrar pagos, abonos o solicitudes de factura.
- Convertir excedentes en deuda.
- Tocar `/api/sync`.

## Impacto sobre 7B

7B-A solo podria avanzar como migracion base vacia y aditiva si se autoriza
explicitamente.

Queda prohibido poblar CxC inicial desde:

- los 3 pagos huerfanos;
- las 170 solicitudes de factura huerfanas;
- los 3 excedentes.

Si una CxC futura se genera desde reservacion scoped valida, debe ser manual, con
simulador, bloqueo de duplicados, CSRF, auditoria y sin Caja automatica.

## Definition of Done

- Documento de politica creado.
- Resumen/cola/auditoria/fuentes/rollback actualizados.
- Sin cambios PHP.
- Sin migraciones.
- Sin escrituras.
- Preflight CxC se mantiene en `ERROR: 0`.
- `/api/sync` sigue fuera de alcance.

## Siguiente accion segura

Con esta politica, hay dos caminos seguros:

1. Cerrar el bloque 7A-S con revision/auditoria documental.
2. Preparar 7B-A solo como migracion base vacia, si se autoriza explicitamente.

No ejecutar escrituras de reconciliacion sobre historicos.
