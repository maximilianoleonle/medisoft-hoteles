# QA pendiente - cola autonoma

## QA critica

- Confirmar que CxP es solo lectura.
- Confirmar que no hay botones de pago en `/cuentas-por-pagar`.
- Confirmar que no hay botones de pago en `/cuentas-por-pagar/{id}`.
- Confirmar que no existe ningun POST operativo de CxP.
- Confirmar que no se generan movimientos de Caja al abrir CxP.
- Confirmar que no se genera CxP automaticamente desde compras.
- Confirmar que una compra recibida no puede recibirse dos veces.
- Confirmar que cada linea recibida conserva exactamente un `movimiento_inventario_id`.
- Confirmar que proveedores, compras y CxP no muestran datos de otro hotel.
- Confirmar que `/api/sync` sigue bloqueado.

## QA funcional

- Iniciar sesion en un hotel con modulo de inventario activo.
- Abrir `/cuentas-por-pagar`.
- Validar estado vacio cuando no hay CxP.
- Revisar filtros de estado/proveedor/compra en el listado.
- Revisar navegacion desde sidebar.
- Cuando exista un registro real o de prueba autorizado, abrir `/cuentas-por-pagar/{id}` y validar detalle read-only.
- Validar que el detalle preserve links de lectura hacia compra/proveedor cuando existan.

## QA regresion

- Revisar `/compras/reportes/recibidas`.
- Revisar `/proveedores/{id}`.
- Revisar detalle de compra recibida.
- Confirmar que recepcion de compra no permite doble recepcion.
- Confirmar que compras recibidas no crean CxP automaticamente todavia.
- Confirmar que Caja/cortes/movimientos no cambian al consultar CxP.

## QA visual

- Revisar diseno de tabla CxP en escritorio.
- Revisar diseno de tabla CxP en movil.
- Revisar mensajes cuando no hay datos.
- Revisar consistencia visual con compras y proveedores.
- Revisar que sidebar no mezcle identidad Medisoft SaaS con branding hotelero.

## Estado

- QA automatica post-commit Fase 3B: completada sin errores bloqueantes.
- Auditoria de seguridad post-cierre: completada sin hallazgos bloqueantes.
- Cierre tecnico del bloque: completado.
- QA manual del bloque 2X-3B: reportada como realizada por el usuario.
- Bloqueante para Fase 3C: no; Fase 3C fue autorizada por nuevo mensaje real.

## QA Fase 3C

### QA critica

- Confirmar que el simulador 3C-A no escribe en DB.
- Confirmar que la generacion 3C-B es manual, no automatica.
- Confirmar que solo compras `recibida` generan CxP.
- Confirmar que no se duplica CxP por compra.
- Confirmar que CxP generada respeta `hotel_id`.
- Confirmar que proveedor y compra pertenecen al mismo hotel.
- Confirmar que no hay Caja, pagos ni abonos.
- Confirmar que `/api/sync` sigue bloqueado.

### QA funcional

- Revisar listado de compras recibidas elegibles.
- Revisar motivos de bloqueo cuando una compra ya tiene CxP.
- Generar CxP manualmente desde una compra recibida autorizada.
- Revisar listado y detalle CxP despues de generar.
- Revisar detalle de compra para ver CxP vinculada.

### QA visual

- Revisar simulador en escritorio y movil.
- Revisar estados elegible/bloqueado.
- Revisar mensajes de exito/error.
- Revisar consistencia con compras/proveedores/CxP.

### QA regresion

- Confirmar que recepcion de compra no genera CxP automaticamente.
- Confirmar que compras/proveedores siguen aislados por hotel.
- Confirmar que Caja/cortes/movimientos no cambian.
- Confirmar que reportes de compras recibidas siguen funcionando.

## Triage no relacionado

- Sin cambios no relacionados pendientes al iniciar 3C-0.
- El ajuste visual del modal de check-in tardio fue commiteado en `dc3c150`.
