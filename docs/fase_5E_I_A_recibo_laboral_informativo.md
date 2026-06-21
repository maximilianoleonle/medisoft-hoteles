# Fase 5E-I-A - Recibo laboral informativo read-only

Estado formal:
`RECIBO_5E_I_A_LABORAL_INFORMATIVO_READ_ONLY_COMPLETADO_QA_MANUAL_PENDIENTE`.

## Objetivo

Agregar una pantalla local de recibo laboral informativo por trabajador y periodo,
sin convertirlo en nomina oficial, recibo fiscal, timbrado, dispersion ni pago masivo.

## Alcance implementado

- Ruta GET/read-only:
  `/trabajadores/{id}/recibo-laboral`.
- Accion `TrabajadorController::reciboLaboralAction`.
- Filtros GET minimos:
  `fecha_inicio`, `fecha_fin`, `incluir_pagos_caja`.
- Metodo read-only `Trabajador::reciboLaboralInformativoPorHotel`.
- Vista `trabajadores/recibo_laboral_informativo`.
- Enlace `Recibo` desde el preview de pre-nomina.
- Validaciones en preflight de pagos laborales Caja.
- Validaciones en health general de rutas Personal.

## Fuente de calculo

El recibo reutiliza `Trabajador::nominaPreviewPorHotel()` con:

- `trabajador_id` fijo al trabajador de la ruta;
- `estado = todos`;
- `solo_con_saldo = 0`;
- mismo periodo GET;
- pagos Caja incluidos o excluidos segun filtro.

Esto evita duplicar reglas financieras y mantiene el recibo como lectura derivada del
preview de pre-nomina.

## Garantias

- No agrega migraciones.
- No escribe en base de datos.
- No crea movimientos de Caja.
- No registra pagos laborales.
- No revierte pagos.
- No crea recibos persistidos.
- No toca permisos/auth.
- No toca PWA/offline, IndexedDB, cache names ni `/api/sync`.
- La vista muestra avisos visibles `Solo lectura`, `No fiscal` y `No genera pago`.

## QA automatica ejecutada

- `php -l` en archivos PHP tocados.
- `tools/saas/preflight_personal_pagos_caja.php`: `OK: 50`, `WARNING: 0`,
  `ERROR: 0`.
- `tools/saas/health_check_fase_1a.php`: `OK: 313`, `WARNING: 25`,
  `ERROR: 0`.
- Ruta local sin sesion:
  `GET /trabajadores/{id}/recibo-laboral?...` responde `303` a login, sin 404.

## QA manual pendiente

1. Abrir `/trabajadores/nomina/preview` con periodo valido.
2. Entrar a `Recibo` desde una fila de trabajador.
3. Confirmar que conserva periodo e inclusion de pagos Caja.
4. Confirmar que los montos coinciden con el preview:
   bruto, deducciones, pagos Caja, neto y pendiente.
5. Confirmar avisos visibles `Solo lectura`, `No fiscal` y `No genera pago`.
6. Probar sin periodo y confirmar bloqueo controlado.
7. Confirmar que no hay POST, boton de pagar, timbrado, dispersion ni movimiento de
   Caja.

## Fuera de alcance

- Nomina oficial.
- CFDI o UUID fiscal.
- Timbrado.
- PDF oficial.
- Envio por correo o WhatsApp.
- Cierre de periodo.
- Pago masivo.
- Liquidacion automatica de anticipos o prestamos.
- Auditoria por simple visualizacion.
