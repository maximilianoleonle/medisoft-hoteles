# Fase 5E-G-0 - Contrato preview read-only de nomina por periodo

## Estado

`CONTRATO_5E_G_0_NOMINA_PERIODO_PREVIEW_READ_ONLY_COMPLETADO`

## Objetivo

Definir el contrato tecnico para una futura pantalla GET/read-only que permita revisar
una pre-nomina operativa por periodo dentro del modulo de Personal.

Esta fase es documental. No implementa codigo PHP, rutas, controladores, modelos,
vistas, formularios, migraciones, permisos, servicios, escrituras, movimientos de Caja
ni cambios en `/api/sync`.

## Contexto vigente

El bloque 5E ya tiene:

- conceptos laborales manuales en `trabajador_pagos`;
- anticipos informativos en `trabajador_anticipos`;
- prestamos informativos en `trabajador_prestamos`;
- pagos laborales reales con Caja en `trabajador_pagos_caja`;
- reversion controlada individual de pago laboral Caja;
- reporte y export CSV read-only de pagos laborales Caja.

El sistema todavia no tiene nomina automatica, periodos oficiales de nomina, recibos
oficiales, dispersion bancaria ni liquidacion automatica de anticipos/prestamos.

## Superficie futura recomendada

Ruta candidata, solo para una futura fase autorizada:

- `GET /trabajadores/nomina/preview`

Parametros GET candidatos:

- `fecha_inicio`;
- `fecha_fin`;
- `trabajador_id`;
- `buscar`;
- `rol_laboral`;
- `estado`;
- `solo_con_saldo`;
- `incluir_pagos_caja`.

Reglas:

- Debe usar solo `method="GET"`.
- No debe incluir `POST`.
- No debe incluir CSRF porque no escribe.
- No debe crear tokens de pago.
- No debe mostrar boton de pagar masivo.
- No debe mostrar boton de generar nomina oficial.
- No debe cambiar Caja, trabajador, ledger, pagos ni saldos.

## Datos que puede leer

La futura pantalla puede leer:

- `trabajadores` filtrado por `hotel_id`;
- `trabajador_pagos` como conceptos laborales manuales;
- `trabajador_anticipos` como saldos informativos;
- `trabajador_prestamos` como saldos informativos;
- `trabajador_pagos_caja` como pagos reales ya aplicados;
- `movimientos_caja` solo para trazabilidad de pagos laborales y reversiones;
- `cortes_caja` y `cajas` solo para contexto read-only;
- `logs_auditoria` solo si se requiere mostrar trazabilidad.

Todas las consultas deben filtrar por hotel actual. No se debe leer ni mezclar datos de
otro hotel.

## Calculos read-only permitidos

La futura pantalla puede calcular al vuelo:

```text
bruto_periodo =
  conceptos_a_favor_del_periodo
  - conceptos_en_contra_del_periodo

deducciones_informativas =
  anticipos_pendientes
  + prestamos_vigentes

pagos_caja_aplicados =
  pagos_laborales_caja_pagados_del_periodo
  - reversiones_caja_detectadas_del_periodo

neto_sugerido =
  bruto_periodo
  - deducciones_informativas
  - pagos_caja_aplicados
```

Reglas:

- El calculo no se persiste.
- El calculo no liquida anticipos ni prestamos.
- El calculo no registra pagos.
- El calculo no crea movimientos de Caja.
- Los pagos revertidos no deben contar como pago vigente.
- Las reversiones solo sirven para explicar el historial y no para escribir ajustes.
- Si `fecha_inicio` o `fecha_fin` faltan o son invalidas, la pantalla debe bloquear el
  preview con mensaje claro.

## Estados sugeridos para el preview

Cada trabajador puede mostrarse con un estado calculado:

- `sin_movimientos`: no tiene conceptos laborales en el periodo;
- `por_pagar`: neto sugerido mayor a cero;
- `cubierto`: neto sugerido igual o menor a cero por pagos Caja ya aplicados o
  deducciones;
- `bloqueado`: faltan datos, trabajador inactivo, periodo invalido o inconsistencia
  detectada.

Estos estados son visuales y read-only. No deben guardarse en base de datos.

## Diferencia contra pago laboral con Caja

El preview de nomina por periodo no reemplaza el panel de pago laboral individual.

Si en una fase futura se decide registrar un pago real, debe seguir usando el servicio
controlado `TrabajadorPagoCajaService` o un contrato nuevo equivalente con transaccion,
locks, CSRF, token y auditoria.

5E-G-0 no autoriza pago masivo ni acciones financieras desde el preview.

## Escrituras prohibidas

Este contrato no autoriza escribir en:

- `trabajadores`;
- `trabajador_pagos`;
- `trabajador_anticipos`;
- `trabajador_prestamos`;
- `trabajador_pagos_caja`;
- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `logs_auditoria`;
- `migrations`;
- PWA/offline, IndexedDB, cache names o `/api/sync`.

Tampoco autoriza:

- nomina automatica;
- calculo fiscal oficial;
- recibos oficiales;
- timbrado;
- dispersion bancaria;
- pagos masivos;
- abonos o liquidaciones automaticas;
- nuevas categorias de Caja;
- cambios de permisos/auth.

## Archivos que podria tocar una futura 5E-G-A

Solo con autorizacion explicita:

- `src/config/routes.php`;
- `src/app/controllers/TrabajadorController.php`;
- `src/app/models/Trabajador.php`;
- `src/app/views/trabajadores/nomina_preview.php`;
- `src/app/views/trabajadores/index.php` solo para enlace GET opcional;
- `src/tools/saas/preflight_personal_pagos_caja.php`;
- `src/tools/saas/health_check_fase_1a.php`;
- documentacion de seguimiento.

No se debe tocar en 5E-G-A:

- `TrabajadorPagoCajaService.php`, salvo que el usuario autorice una fase de pago real;
- migraciones;
- permisos/auth;
- PWA/offline;
- `/api/sync`;
- rutas POST;
- vistas con formularios POST de pago masivo.

## Validaciones obligatorias para una futura 5E-G-A

Una implementacion futura debe demostrar:

1. PHP lint en archivos tocados.
2. Ruta GET registrada y protegida por sesion.
3. Sin rutas POST nuevas.
4. Vista con filtros GET y sin CSRF.
5. Modelo/controlador sin `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP` ni `TRUNCATE`.
6. Todas las consultas con `hotel_id`.
7. Preflight pagos laborales con Caja con `ERROR: 0`.
8. Health general con `ERROR: 0`.
9. `/api/sync` sigue bloqueado con HTTP 423 y `sync_temporarily_disabled`.
10. Prueba HTTP local sin sesion redirige a login y no devuelve 404.

## QA manual futura

Checklist sugerido para una futura pantalla:

1. Abrir el preview sin fechas y confirmar bloqueo por periodo requerido.
2. Seleccionar un periodo valido.
3. Confirmar trabajadores del hotel actual solamente.
4. Confirmar que conceptos a favor/en contra se reflejan en bruto.
5. Confirmar que anticipos/prestamos aparecen como deducciones informativas.
6. Confirmar que pagos Caja aplicados descuentan el neto sugerido.
7. Confirmar que pagos revertidos no descuentan como pagos vigentes.
8. Confirmar que no hay botones de pago masivo ni POST.
9. Confirmar que no se crean movimientos de Caja ni registros laborales.

## Definition of Done de este contrato

- Documento creado.
- Resumen ejecutivo actualizado.
- QA pendiente actualizada.
- Rollback documental actualizado.
- Sin cambios de codigo operativo.
- Sin cambios de base de datos.
- Sin cambios PWA/offline ni `/api/sync`.

## Siguiente paso seguro

El siguiente paso seguro seria `5E-G-A`, implementacion GET/read-only del preview de
nomina por periodo, solo con autorizacion explicita para tocar ruta, controlador,
modelo, vista y checkers.
