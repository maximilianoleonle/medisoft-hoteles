# Fase 5E-A - Preflight pagos laborales con Caja

## Estado

`PREFLIGHT_5E_A_PAGOS_LABORALES_CAJA_READONLY_COMPLETADO`

## Objetivo

Crear una compuerta CLI/read-only para diagnosticar si Personal y Caja estan listos
para una futura implementacion de pagos laborales reales.

Esta fase no implementa pagos, nomina, rutas, controladores, modelos operativos,
vistas, formularios, migraciones, datos ni movimientos de Caja.

## Archivo nuevo

- `src/tools/saas/preflight_personal_pagos_caja.php`.

## Health actualizado

- `src/tools/saas/health_check_fase_1a.php`.

El health ahora reconoce el preflight 5E-A y valida que declare:

- ejecucion CLI/read-only;
- `START TRANSACTION READ ONLY`;
- `trabajador_pagos` como conceptos laborales;
- entidad futura independiente `trabajador_pagos_caja`;
- guardas contra `TrabajadorPagoCajaService` prematuro;
- guardas contra rutas `registrar-pago-caja`;
- validacion de `movimientos_caja` y `cortes_caja`.

## Validaciones que realiza el preflight

El preflight revisa:

- tablas laborales base: `trabajadores`, `trabajador_pagos`,
  `trabajador_anticipos`, `trabajador_prestamos`;
- tablas de Caja necesarias para una futura integracion: `cajas`,
  `cortes_caja`, `movimientos_caja`;
- ausencia de la tabla futura `trabajador_pagos_caja` antes de la migracion 5E-B;
- ausencia de columnas financieras prematuras en `trabajador_pagos`;
- ausencia de tipos o conceptos laborales que parezcan pagos reales;
- consistencia de trabajador/hotel en conceptos laborales;
- saldos validos en anticipos y prestamos;
- ausencia de movimientos o categorias de Caja con nombre de nomina/pago laboral;
- ausencia de rutas de pago laboral con Caja antes de contrato y migracion;
- ausencia de servicio `TrabajadorPagoCajaService` prematuro;
- ausencia de prueba rollback prematura `probar_pago_laboral_caja.php`;
- que las vistas de Personal conserven el aviso de ledger sin pagos reales ni Caja.

## Resultado automatico

Preflight 5E-A:

- `OK: 34`.
- `WARNING: 1`.
- `ERROR: 0`.

La advertencia vigente es funcional: no hay trabajadores activos para una QA futura
de pago real. No bloquea 5E-A, pero debe resolverse antes de probar una fase real de
pago laboral.

Health general:

- `OK: 305`.
- `WARNING: 25`.
- `ERROR: 0`.

## Hallazgos

- No existe `trabajador_pagos_caja`, como se espera antes de la migracion 5E-B.
- `trabajador_pagos` no contiene columnas financieras de Caja.
- No hay movimientos de Caja con categoria nomina/pago laboral.
- No hay categorias de Caja nomina/pago laboral.
- No hay rutas laborales de pago con Caja.
- No existe `TrabajadorPagoCajaService`.
- No existe prueba rollback de pago laboral.
- Hay 3 cortes de Caja abiertos disponibles para una QA futura.
- No hay trabajadores activos para probar pago real; se debe preparar un trabajador
  activo antes de una fase operativa.

## Fuera de alcance

No se implementa:

- migracion;
- tabla `trabajador_pagos_caja`;
- ruta;
- controlador;
- servicio;
- vista;
- formulario;
- boton de pago;
- movimiento de Caja;
- categoria de Caja;
- pago laboral real;
- abono o liquidacion de anticipos/prestamos;
- nomina automatica;
- reversion;
- permisos nuevos;
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

`/api/sync` debe seguir bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Siguiente paso seguro

El siguiente paso seguro es `5E-B-0`: contrato de migracion aditiva para crear una
tabla independiente de pagos laborales reales, por ejemplo `trabajador_pagos_caja`.

No ejecutar migracion ni escribir datos sin backup previo y autorizacion explicita.

## Seguimiento 5E-B-0

Estado posterior:

`CONTRATO_5E_B_0_MIGRACION_PAGOS_LABORALES_CAJA_COMPLETADO`

Documento:

- `docs/fase_5E_B_0_contrato_migracion_pagos_laborales_caja.md`.

Se definio el contrato tecnico de la futura tabla `trabajador_pagos_caja`, sin crear
migracion, sin ejecutar SQL, sin tocar Caja y sin habilitar pagos reales.

## Seguimiento 5E-B-A

Estado posterior:

`MIGRACION_5E_B_A_PAGOS_LABORALES_CAJA_COMPLETADA`

Documento:

- `docs/fase_5E_B_A_migracion_pagos_laborales_caja.md`.

La tabla `trabajador_pagos_caja` fue creada vacia con backup previo. El preflight 5E
fue actualizado para validar columnas, indices, constraints, tabla vacia y registro
en `migrations`.

Resultado posterior:

- Preflight 5E: `OK: 37`, `WARNING: 0`, `ERROR: 0`.
- Health general: `OK: 307`, `WARNING: 26`, `ERROR: 0`.
