# Fase 5E-J-0 - Contrato recibo laboral PDF informativo

Estado formal:
`CONTRATO_5E_J_0_RECIBO_LABORAL_PDF_INFORMATIVO_READ_ONLY_COMPLETADO`.

## Objetivo

Definir el contrato para una futura descarga o visualizacion PDF del recibo laboral
informativo, sin implementar todavia codigo, rutas, controladores, modelos, vistas,
servicios PDF, migraciones, permisos, Caja, datos, storage ni `/api/sync`.

El PDF futuro debe ser una representacion descargable del recibo laboral informativo
ya validado, no una nomina oficial ni un comprobante fiscal.

## Ruta candidata futura

- `GET /trabajadores/{id}/recibo-laboral/pdf`

Parametros GET esperados:

- `fecha_inicio`
- `fecha_fin`
- `incluir_pagos_caja`

El periodo debe ser obligatorio. Si falta o es invalido, la accion futura no debe
generar PDF ambiguo.

## Naturaleza del PDF

El PDF futuro debe ser:

- informativo;
- read-only;
- generado bajo demanda;
- scoped por `hotel_id`;
- visible solo para trabajadores del hotel actual;
- derivado del recibo laboral informativo;
- no persistido por defecto;
- no oficial;
- no fiscal;
- no timbrado;
- no firmado digitalmente.

Debe mostrar una etiqueta visible como:

`PDF informativo / No fiscal / No genera pago`

## Fuente de datos

La implementacion futura debe reutilizar, en lo posible, la misma lectura del recibo:

- `Trabajador::reciboLaboralInformativoPorHotel()`;
- filtros de periodo;
- pagos Caja aplicados solo como lectura;
- reversiones detectadas solo como lectura.

Si se requiere un renderer PDF, debe recibir datos ya calculados y no consultar ni
escribir reglas financieras adicionales.

## Contenido esperado

El PDF futuro debera mostrar:

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
- bruto del periodo;
- deducciones informativas;
- neto sugerido;
- pendiente de pago sugerido;
- fecha/hora de generacion visual;
- aviso de que no representa CFDI, nomina oficial ni comprobante fiscal.

## Prohibido

Queda prohibido en 5E-J-0 y en la futura implementacion sin contrato adicional:

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
- guardar archivos en `storage` por defecto;
- auditar por simple descarga;
- enviar por correo o WhatsApp;
- tocar permisos/auth;
- tocar PWA/offline, IndexedDB, cache names o `/api/sync`.

## Archivos candidatos para una futura 5E-J-A

Solo con autorizacion explicita se podrian tocar:

- `src/config/routes.php`
- `src/app/controllers/TrabajadorController.php`
- `src/app/models/Trabajador.php` solo si hace falta lectura auxiliar
- `src/app/views/trabajadores/recibo_laboral_informativo.php` si se agrega enlace
- renderer/helper PDF especifico si existe o se crea
- `src/tools/saas/preflight_personal_pagos_caja.php`
- `src/tools/saas/health_check_fase_1a.php`
- documentacion de fase

## QA futura sugerida

1. Abrir la ruta PDF sin sesion y confirmar redireccion a login.
2. Abrir PDF con trabajador valido y periodo valido.
3. Confirmar etiqueta visible `PDF informativo / No fiscal / No genera pago`.
4. Confirmar que totales coinciden con el recibo HTML y el preview de pre-nomina.
5. Confirmar que el PDF se genera bajo demanda y no guarda archivo en storage.
6. Intentar generar sin periodo y confirmar bloqueo controlado.
7. Confirmar que no hay POST, pago masivo, timbrado, dispersion, movimiento de Caja ni
   escritura laboral.

## Criterio de cierre futuro

La futura 5E-J-A solo debera cerrarse si:

- la ruta sin sesion redirige a login y no responde 404;
- `php -l` pasa en archivos PHP tocados;
- preflight pagos laborales Caja termina con `ERROR: 0`;
- health general termina con `ERROR: 0`;
- `/api/sync` sigue bloqueado;
- QA manual confirma que el PDF es informativo, no oficial y no operativo.
