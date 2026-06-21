# Fase 5E-H-F - Cierre export CSV del preview de pre-nomina

Estado formal:
`CIERRE_5E_H_F_EXPORT_CSV_NOMINA_PREVIEW_QA_MANUAL_VALIDADA`.

## Resultado

El usuario confirmo que la prueba manual del export CSV del preview de pre-nomina paso
correctamente.

Queda cerrada la superficie:

- `GET /trabajadores/nomina/preview/exportar`

## Alcance cerrado

La exportacion CSV queda como lectura descargable del preview de pre-nomina por
periodo. Reutiliza los mismos filtros y calculos de `GET /trabajadores/nomina/preview`.

El CSV incluye:

- periodo;
- trabajador;
- identificacion;
- rol;
- estado del trabajador;
- estado del preview;
- bruto del periodo;
- conceptos a favor y en contra;
- anticipos y prestamos pendientes;
- deducciones informativas;
- pagos Caja aplicados;
- pagos Caja pagados y revertidos;
- reversiones detectadas;
- neto sugerido;
- pendiente de pago sugerido;
- ultimo pago Caja.

## Evidencia tecnica registrada

Validaciones automaticas ejecutadas durante la implementacion:

- `php -l` OK en controlador, vista, rutas, preflight y health.
- Preflight pagos laborales Caja:
  `OK: 48`, `WARNING: 0`, `ERROR: 0`.
- Health general:
  `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Ruta local sin sesion:
  `GET /trabajadores/nomina/preview/exportar` responde `303` a `/login`, sin 404.

## Confirmacion manual

El usuario confirmo la prueba manual despues de revisar la descarga CSV.

Estado de QA:
`QA_MANUAL_VALIDADA_5E_H_A_EXPORT_CSV_NOMINA_PREVIEW`.

## Fuera de alcance

Este cierre no autoriza:

- nomina automatica;
- cierre o persistencia de periodos oficiales;
- pago masivo;
- recibos oficiales;
- timbrado;
- dispersion bancaria;
- liquidacion automatica de anticipos/prestamos;
- auditoria por descarga;
- cambios de Caja;
- movimientos de Caja;
- migraciones;
- cambios de permisos/auth;
- PWA/offline;
- `/api/sync`.

## Siguiente paso recomendado

Abrir contrato independiente antes de implementar recibo laboral informativo,
preparacion de recibo descargable, cierre formal de periodo, pago masivo o cualquier
flujo que pueda confundirse con nomina oficial.
