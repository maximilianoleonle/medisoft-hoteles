# Fase 5E-0 - Contrato pagos laborales con Caja

## Estado

`CONTRATO_5E_0_PAGOS_LABORALES_CAJA_COMPLETADO`

## Objetivo

Definir el contrato para una futura integracion de pagos laborales reales con Caja,
sin implementarla todavia.

Esta fase es solo documental/diagnostico. No agrega codigo, rutas, controladores,
modelos, vistas, formularios, migraciones, datos ni escrituras.

## Contexto vigente

Personal operativo base ya existe con:

- `trabajadores`.
- `trabajador_pagos`.
- `trabajador_anticipos`.
- `trabajador_prestamos`.
- `trabajador_asistencias`.
- documentos laborales contextuales mediante Centro Documental.
- reporte de Personal read-only.

Regla critica vigente:

- `trabajador_pagos` no representa pagos reales.
- `trabajador_pagos` representa conceptos laborales manuales:
  `comision`, `bono`, `descuento`, `ajuste`.
- Los anticipos y prestamos mantienen saldos informativos.
- No hay abonos ni liquidaciones reales de anticipos/prestamos.
- No existe integracion autorizada entre Personal y Caja.

## Problema a resolver

El hotel necesita poder registrar pagos reales a trabajadores, pero hacerlo
directamente sobre `trabajador_pagos` mezclaria conceptos laborales con egresos de
Caja.

El pago laboral real debe quedar como entidad independiente, trazable y reversible,
igual que los pagos de proveedor y cobros CxC ya formalizados en otros bloques.

## Principio de diseno

Una futura implementacion debe separar tres conceptos:

1. Conceptos laborales:
   - bonos;
   - comisiones;
   - descuentos;
   - ajustes.
2. Saldos informativos:
   - anticipos pendientes;
   - prestamos vigentes;
   - saldo laboral calculado.
3. Pagos reales:
   - egreso de Caja;
   - registro laboral de pago;
   - auditoria;
   - posible reversion futura.

## Modelo futuro recomendado

Crear una tabla nueva, aditiva e idempotente, por ejemplo:

- `trabajador_pagos_caja`.

Campos minimos recomendados:

- `id`.
- `hotel_id`.
- `trabajador_id`.
- `movimiento_caja_id`.
- `corte_id`.
- `monto`.
- `metodo_pago`.
- `referencia`.
- `periodo_inicio`.
- `periodo_fin`.
- `concepto`.
- `estado`: `pagado`, `revertido`.
- `notas`.
- `created_by`.
- `created_at`.
- `updated_at`.

No se debe reutilizar `trabajador_pagos` para pagos reales.

## Contrato de Caja futuro

Una futura fase de implementacion debe:

- exigir corte de Caja abierto del hotel actual;
- crear `movimientos_caja` con:
  - `tipo = 'gasto'`;
  - `categoria = 'Pago nomina'` o `Pago laboral`;
  - `hotel_id` del contexto;
  - `corte_id` abierto del mismo hotel;
  - `metodo_pago` permitido;
  - `referencia` unica;
  - descripcion clara con trabajador y periodo;
- registrar el pago laboral en la tabla nueva;
- enlazar `trabajador_pagos_caja.movimiento_caja_id`;
- auditar con `AuditService`;
- usar transaccion, locks y rollback si falla cualquier paso.

## Elegibilidad futura

Un pago laboral real solo deberia ser elegible si:

- el trabajador existe en el hotel actual;
- el trabajador esta activo;
- existe corte de Caja abierto del hotel actual;
- el monto es mayor a cero;
- el metodo de pago es valido;
- no existe otro pago con la misma referencia;
- el periodo es valido si se captura;
- el usuario tiene permiso operativo suficiente;
- el token de pago de un solo uso es valido.

## Reversion futura

No debe implementarse reversion en la primera fase real salvo contrato separado.

Una futura reversion debe:

- no borrar el pago original;
- crear movimiento laboral de reversion;
- crear movimiento de Caja inverso;
- auditar motivo;
- bloquear doble reversion;
- validar corte abierto;
- conservar trazabilidad de `pago_id` original.

## Fases futuras sugeridas

1. `5E-A` preflight CLI/read-only de pagos laborales con Caja.
2. `5E-B-0` contrato de migracion aditiva para tabla de pagos laborales reales.
3. `5E-B-A` migracion aditiva con backup previo.
4. `5E-C-0` contrato de simulador pago laboral contra Caja.
5. `5E-C-A` simulador GET/read-only.
6. `5E-D-0` contrato del servicio transaccional de pago laboral.
7. `5E-D-A` implementacion real con POST, CSRF, token de un solo uso y prueba rollback.
8. `5E-E-0` contrato de reversion de pago laboral.

## Fuera de alcance de 5E-0

No se implementa:

- migracion;
- tabla nueva;
- ruta nueva;
- controlador;
- servicio;
- vista;
- boton de pago;
- movimiento de Caja;
- categoria de Caja;
- abono de anticipos/prestamos;
- liquidacion automatica;
- nomina automatica;
- timbrado;
- bancos;
- dispersion;
- reversion;
- permisos nuevos;
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

`/api/sync` debe seguir bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Archivos que podria tocar una futura 5E-A

Solo con autorizacion explicita:

- `src/tools/saas/preflight_personal_pagos_caja.php`.
- `src/tools/saas/health_check_fase_1a.php`.
- Documentacion de seguimiento.

## Archivos que podria tocar una futura 5E-D-A

Solo con autorizacion explicita, backup y contrato previo:

- `src/config/routes.php`.
- `src/app/controllers/TrabajadorController.php`.
- `src/app/models/Trabajador.php`.
- `src/app/services/TrabajadorPagoCajaService.php`.
- `src/app/views/trabajadores/ver.php`.
- `src/tools/saas/probar_pago_laboral_caja.php`.
- `src/tools/saas/preflight_personal_pagos_caja.php`.
- `src/tools/saas/health_check_fase_1a.php`.

## Criterios de aceptacion futuros

Una futura implementacion real debe demostrar:

1. Backup previo verificado si hay migracion o escritura financiera.
2. Prueba rollback que no deje pagos ni movimientos de Caja persistidos.
3. Pago real solo con POST, CSRF y token de un solo uso.
4. Trabajador scoped por `hotel_id`.
5. Corte de Caja abierto del mismo hotel.
6. Movimiento Caja tipo `gasto`.
7. Registro laboral nuevo en tabla independiente.
8. No tocar `trabajador_pagos` salvo lectura de conceptos.
9. No liquidar anticipos/prestamos automaticamente.
10. Health/preflight con `ERROR: 0`.
11. QA manual con trabajador activo.

## Siguiente paso seguro

Despues de este contrato, el siguiente paso seguro es `5E-A`: crear un preflight
CLI/read-only de pagos laborales con Caja que diagnostique tablas, rutas prohibidas,
conteos y riesgos, sin registrar pagos ni tocar Caja.

## Seguimiento 5E-A

Estado posterior:

`PREFLIGHT_5E_A_PAGOS_LABORALES_CAJA_READONLY_COMPLETADO`

Documento:

- `docs/fase_5E_A_preflight_pagos_laborales_caja.md`.

Se agrego una herramienta CLI/read-only que valida Personal, Caja, ausencia de rutas
y ausencia de implementacion prematura de pago laboral real.

Resultado automatico:

- Preflight 5E-A: `OK: 34`, `WARNING: 1`, `ERROR: 0`.
- Health general: `OK: 305`, `WARNING: 25`, `ERROR: 0`.

La advertencia del preflight corresponde a que no hay trabajadores activos para QA
futura de pago real. No autoriza migracion, tabla nueva, ruta, vista, formulario,
servicio, pago real ni movimiento de Caja.

## Seguimiento 5E-B-0

Estado posterior:

`CONTRATO_5E_B_0_MIGRACION_PAGOS_LABORALES_CAJA_COMPLETADO`

Documento:

- `docs/fase_5E_B_0_contrato_migracion_pagos_laborales_caja.md`.

Se definio el contrato de migracion aditiva para la tabla futura
`trabajador_pagos_caja`.

No se creo archivo SQL, no se ejecuto migracion, no se creo tabla, no se tocaron datos,
no se modifico Caja y no se habilitaron pagos laborales reales.

## Seguimiento 5E-B-A

Estado posterior:

`MIGRACION_5E_B_A_PAGOS_LABORALES_CAJA_COMPLETADA`

Documento:

- `docs/fase_5E_B_A_migracion_pagos_laborales_caja.md`.

Migracion aplicada:

- `migrations/20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql`.

Se creo `trabajador_pagos_caja` vacia como tabla independiente para pagos laborales
reales futuros.

No se crearon pagos, movimientos de Caja, categorias de Caja, rutas, vistas,
formularios, servicios ni permisos nuevos.

## Seguimiento 5E-C-0

Estado posterior:

`CONTRATO_5E_C_0_SIMULADOR_PAGO_LABORAL_CAJA_COMPLETADO`

Documento:

- `docs/fase_5E_C_0_contrato_simulador_pago_laboral_caja.md`.

Se definio la futura superficie `GET /trabajadores/pagos-caja/simulador` como
simulador read-only de elegibilidad de pago laboral contra Caja.

No se implemento ruta, controlador, modelo, vista, formulario, servicio, pago real,
movimiento de Caja ni categoria de Caja.
