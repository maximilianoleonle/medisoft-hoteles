# Fase 5E-I-0 - Contrato recibo laboral informativo

Estado formal:
`CONTRATO_5E_I_0_RECIBO_LABORAL_INFORMATIVO_READ_ONLY_COMPLETADO`.

## Objetivo

Definir el contrato para una futura pantalla de recibo laboral informativo por
trabajador y periodo, sin implementar todavia codigo, rutas, controladores, modelos,
vistas, formularios, migraciones, permisos, servicios, Caja, datos ni `/api/sync`.

El recibo futuro debe explicar de forma clara el calculo laboral operativo que ya se
usa en el preview de pre-nomina, pero sin convertirse en nomina oficial, recibo fiscal,
timbrado, dispersion o pago masivo.

## Ruta candidata futura

- `GET /trabajadores/{id}/recibo-laboral`

Parametros GET esperados:

- `fecha_inicio`
- `fecha_fin`
- `incluir_pagos_caja`

El periodo debe ser obligatorio. Si falta o es invalido, la pantalla futura no debe
mostrar un recibo ambiguo.

## Naturaleza del recibo

El recibo futuro debe ser:

- informativo;
- read-only;
- scoped por `hotel_id`;
- visible solo para trabajadores del hotel actual;
- derivado de datos ya existentes;
- no oficial;
- no fiscal;
- no timbrado;
- no firmado digitalmente.

Debe mostrar una etiqueta visible como:

`Recibo informativo / No fiscal / No genera pago`

## Fuente de datos

La implementacion futura debe reutilizar, en lo posible, la misma logica read-only de
pre-nomina:

- `Trabajador::nominaPreviewPorHotel()`;
- filtros de periodo;
- pagos Caja aplicados solo como lectura;
- reversiones detectadas solo como lectura.

Si se requiere un metodo auxiliar, debe ser de lectura y scoped por hotel, sin duplicar
reglas financieras.

## Contenido esperado

El recibo futuro debera mostrar:

- hotel actual;
- trabajador;
- identificacion;
- rol laboral;
- periodo;
- conceptos laborales a favor;
- conceptos laborales en contra;
- anticipos pendientes informativos;
- prestamos pendientes informativos;
- pagos Caja aplicados;
- pagos Caja revertidos;
- reversiones detectadas;
- bruto del periodo;
- deducciones informativas;
- neto sugerido;
- pendiente de pago sugerido;
- fecha/hora de generacion visual;
- aviso de que no representa CFDI, nomina oficial ni comprobante fiscal.

## Prohibido

Queda prohibido en 5E-I-0 y en la futura implementacion sin contrato adicional:

- crear nomina oficial;
- cerrar o persistir periodos;
- crear folios oficiales;
- crear UUID fiscal;
- timbrar;
- generar CFDI;
- generar dispersion bancaria;
- pagar masivamente;
- registrar nuevos pagos Caja;
- revertir pagos Caja;
- liquidar anticipos o prestamos automaticamente;
- crear recibos persistidos en base de datos;
- auditar por simple visualizacion;
- descargar PDF oficial;
- enviar por correo o WhatsApp;
- tocar permisos/auth;
- tocar PWA/offline, IndexedDB, cache names o `/api/sync`.

## Archivos candidatos para una futura 5E-I-A

Solo con autorizacion explicita se podrian tocar:

- `src/config/routes.php`
- `src/app/controllers/TrabajadorController.php`
- `src/app/models/Trabajador.php` si hace falta una lectura auxiliar
- `src/app/views/trabajadores/recibo_laboral_informativo.php`
- `src/app/views/trabajadores/nomina_preview.php` si se agrega enlace al recibo
- `src/tools/saas/preflight_personal_pagos_caja.php`
- `src/tools/saas/health_check_fase_1a.php`
- documentacion de fase

## QA futura sugerida

1. Abrir el recibo sin sesion y confirmar redireccion a login.
2. Abrir el recibo con trabajador de otro hotel y confirmar bloqueo o redireccion.
3. Abrir con trabajador valido y periodo valido.
4. Confirmar etiqueta visible `Informativo / No fiscal / No genera pago`.
5. Confirmar que totales coinciden con el preview de pre-nomina para el mismo periodo.
6. Confirmar que pagos Caja y reversiones son solo lectura.
7. Confirmar que no hay POST, CSRF, boton de pagar, boton de timbrar, dispersion ni
   movimiento de Caja.
8. Confirmar que abrir o refrescar el recibo no crea auditorias ni cambia datos.

## Criterio de cierre futuro

La futura 5E-I-A solo debera cerrarse si:

- la ruta sin sesion redirige a login y no responde 404;
- `php -l` pasa en archivos PHP tocados;
- preflight pagos laborales Caja termina con `ERROR: 0`;
- health general termina con `ERROR: 0`;
- `/api/sync` sigue bloqueado;
- QA manual confirma que el recibo es informativo y no operativo.
