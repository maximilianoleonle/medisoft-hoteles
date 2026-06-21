# Fase 5E-I-F - Cierre recibo laboral informativo

Estado formal:
`CIERRE_5E_I_F_RECIBO_LABORAL_INFORMATIVO_QA_MANUAL_VALIDADA`.

## Resultado

El usuario confirmo que la prueba manual del recibo laboral informativo paso
correctamente.

Queda cerrada la superficie:

- `GET /trabajadores/{id}/recibo-laboral`

## Alcance cerrado

El recibo laboral queda como pantalla informativa por trabajador y periodo. Reutiliza
los calculos read-only del preview de pre-nomina y conserva scope por hotel.

La pantalla muestra:

- trabajador;
- rol laboral;
- estado del trabajador;
- periodo;
- bruto laboral;
- conceptos a favor y en contra;
- anticipos pendientes;
- prestamos vigentes;
- deducciones informativas;
- pagos Caja aplicados;
- pagos Caja revertidos;
- ultimo pago Caja;
- neto sugerido;
- pendiente de pago sugerido;
- avisos visibles `Solo lectura`, `No fiscal` y `No genera pago`.

## Evidencia tecnica registrada

Validaciones automaticas ejecutadas durante la implementacion:

- `php -l` OK en controlador, modelo, vistas, rutas, preflight y health.
- Preflight pagos laborales Caja:
  `OK: 50`, `WARNING: 0`, `ERROR: 0`.
- Health general:
  `OK: 313`, `WARNING: 25`, `ERROR: 0`.
- Ruta local sin sesion:
  `GET /trabajadores/{id}/recibo-laboral` responde `303` a `/login`, sin 404.

## Confirmacion manual

El usuario confirmo la prueba manual despues de revisar el recibo laboral
informativo.

Estado de QA:
`QA_MANUAL_VALIDADA_5E_I_A_RECIBO_LABORAL_INFORMATIVO`.

## Fuera de alcance

Este cierre no autoriza:

- nomina automatica;
- cierre o persistencia de periodos oficiales;
- pago masivo;
- recibos fiscales oficiales;
- PDF oficial;
- timbrado;
- CFDI;
- UUID fiscal;
- dispersion bancaria;
- envio por correo o WhatsApp;
- liquidacion automatica de anticipos/prestamos;
- auditoria por simple visualizacion;
- cambios de Caja;
- movimientos de Caja;
- migraciones;
- cambios de permisos/auth;
- PWA/offline;
- `/api/sync`.

## Siguiente paso recomendado

Abrir contrato independiente antes de implementar recibo descargable, PDF
informativo, cierre formal de periodo, aprobacion de nomina, pago masivo o cualquier
flujo que pueda confundirse con nomina oficial.
