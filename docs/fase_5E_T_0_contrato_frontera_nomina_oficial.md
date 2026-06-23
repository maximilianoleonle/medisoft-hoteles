# Fase 5E-T-0 - Contrato frontera de nomina oficial

Estado formal:
`CONTRATO_5E_T_0_FRONTERA_NOMINA_OFICIAL_COMPLETADO`.

Fecha: 2026-06-22.

## Objetivo

Definir una frontera tecnica y funcional entre la nomina administrativa ya
implementada en el bloque 5E y cualquier futura nomina oficial.

Esta fase es documental. No agrega codigo, rutas, controladores, modelos,
servicios, vistas, migraciones, permisos, datos, storage, Caja, PWA/offline ni
`/api/sync`.

## Contexto

El bloque 5E ya permite en local:

- ledger laboral informativo;
- pago laboral individual con Caja;
- reversion controlada de pago laboral;
- recibo laboral informativo HTML/PDF;
- pre-nomina con snapshots persistentes;
- aprobacion/anulacion administrativa de snapshots;
- pago individual desde snapshot aprobado;
- trazabilidad fuerte hacia snapshot y detalle;
- conciliacion de pagos snapshot;
- auditoria consolidada;
- expediente administrativo read-only.

Todo eso sigue siendo nomina administrativa. No es nomina oficial.

## Frontera obligatoria

Antes de cualquier nomina oficial futura, el sistema debe seguir bloqueando o
detectando cualquier intento de agregar:

- CFDI laboral;
- timbrado de nomina;
- dispersion bancaria;
- pago masivo de nomina;
- recibos fiscales;
- UUID fiscal laboral;
- polizas contables oficiales de nomina;
- calculo fiscal patronal;
- liquidacion automatica de anticipos o prestamos;
- nomina oficial persistida sin contrato propio.

## Siguiente paso seguro

El siguiente paso seguro es `5E-T-A`: un preflight CLI/read-only que revise que
la superficie actual de Personal/Nomina no expone rutas, metodos, migraciones u
objetos de base que parezcan nomina oficial.

El preflight puede leer:

- `config/routes.php`;
- controlador/modelo/servicios de Personal/Nomina;
- migraciones disponibles, si estan montadas;
- base local en transaccion read-only, si esta disponible;
- estado de bloqueo de `/api/sync`.

## Permitido en 5E-T-A

5E-T-A puede:

- crear una herramienta CLI read-only en `tools/saas`;
- validar rutas activas;
- validar nombres de clases/metodos de la superficie Personal/Nomina;
- validar migraciones montadas;
- consultar `information_schema` en modo read-only;
- confirmar que `/api/sync` conserva `sync_temporarily_disabled` y HTTP 423;
- documentar resultados.

## Prohibido en 5E-T-A

5E-T-A no autoriza:

- rutas nuevas;
- modelos nuevos;
- controladores nuevos;
- vistas nuevas;
- formularios;
- POST;
- permisos;
- auth;
- migraciones;
- cambios DB;
- pagos;
- reversiones;
- movimientos Caja;
- snapshots;
- CFDI;
- timbrado;
- dispersion;
- pago masivo;
- storage;
- PWA/offline;
- IndexedDB;
- cache names;
- cambios en `/api/sync`.

## Criterio de avance futuro

Despues de 5E-T-A, cualquier avance hacia nomina oficial debe iniciar con un
contrato mayor e independiente, no como continuacion automatica del bloque
administrativo.

Ese contrato futuro deberia definir, antes de tocar codigo:

- alcance fiscal y laboral;
- modelo de autorizaciones;
- tablas y migraciones;
- integracion CFDI/timbrado, si aplica;
- dispersion bancaria, si aplica;
- relacion con Caja;
- relacion con contabilidad;
- manejo de errores y rollback;
- pruebas legales/fiscales;
- proteccion de datos sensibles;
- estrategia de produccion.

Sin ese contrato, el sistema debe permanecer en nomina administrativa.
