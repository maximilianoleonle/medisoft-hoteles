# Fase 3D-A - Simulador read-only de egresos proveedor con Caja

## Estado

`SIMULADOR_3D_A_CAJA_READONLY_COMPLETADO_QA_DIFERIDA`

## Objetivo

Agregar una pantalla segura para evaluar que cuentas por pagar de proveedores podrian
egresarse desde Caja en una fase posterior, sin registrar pagos, sin crear movimientos y
sin modificar saldos.

## Alcance implementado

- Ruta GET `/cuentas-por-pagar/simulador-caja`.
- Accion `CuentaPorPagarController::simuladorCajaAction()`.
- Metodo `CuentaPorPagar::simuladorCajaProveedor()`.
- Vista `app/views/cuentas_por_pagar/simulador_caja.php`.
- Preflight CLI `src/tools/saas/preflight_pagos_proveedores_caja.php`.
- Enlaces desde listado y detalle de CxP.

## Reglas de seguridad

- Solo GET.
- Sin formularios POST.
- Sin CSRF porque no hay accion de escritura.
- Sin pagos reales.
- Sin abonos.
- Sin inserciones en `cuentas_por_pagar_movimientos`.
- Sin inserciones en `movimientos_caja`.
- Sin cambios en `cuentas_por_pagar`.
- Sin cambios en `cortes_caja` ni `cajas`.
- Sin `/api/sync`.

## Criterios de elegibilidad mostrados

Una CxP se muestra como elegible solo si:

- pertenece al hotel actual;
- tiene proveedor del mismo hotel;
- esta en estado `pendiente`, `parcial` o `vencida`;
- tiene saldo mayor a cero;
- si tiene compra vinculada, la compra existe en el mismo hotel;
- si la compra tiene estado, esta recibida;
- existe corte de Caja abierto para el hotel actual.

Si alguna condicion falla, la vista muestra el motivo de bloqueo.

## Datos que muestra

- cuenta por pagar;
- proveedor;
- compra vinculada;
- estado;
- total;
- saldo;
- corte abierto detectado;
- caja asociada;
- diagnostico de elegibilidad o bloqueo.

## Fuera de alcance

- POST de pago.
- Servicio transaccional.
- Movimiento CxP.
- Movimiento Caja.
- Cambio de saldo.
- Cambio de estado.
- Edicion de corte.
- Cierre de corte.
- Migraciones.

## Rollback

Revertir el commit de 3D-A retira:

- ruta GET `/cuentas-por-pagar/simulador-caja`;
- accion del controlador;
- metodos read-only del modelo;
- vista del simulador;
- preflight 3D-A;
- enlaces desde CxP.

No requiere rollback de base de datos porque no crea ni modifica datos.

## QA manual diferida

Cuando el usuario pueda probar:

1. Abrir `/cuentas-por-pagar/simulador-caja`.
2. Confirmar que la ruta exige sesion.
3. Confirmar que muestra el corte abierto del hotel activo.
4. Confirmar que muestra CxP elegibles o bloqueadas con motivo claro.
5. Confirmar que los enlaces a CxP, proveedor, compra y Caja funcionan.
6. Confirmar que no hay botones para pagar.
7. Confirmar que no hay formularios POST.
8. Confirmar que no cambian saldos.
9. Confirmar que no se crean movimientos CxP ni Caja.

## Siguiente accion segura

No avanzar a pago real sin backup, servicio transaccional, prueba controlada y QA manual.
La siguiente subfase posible es 3D-B solo como diseno/servicio transaccional revisable,
pero debe requerir autorizacion explicita antes de ejecutar escrituras.
