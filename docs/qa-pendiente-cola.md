# QA pendiente - cola autonoma

## QA critica

- Confirmar que CxP es solo lectura.
- Confirmar que no hay botones de pago en `/cuentas-por-pagar`.
- Confirmar que no hay botones de pago en `/cuentas-por-pagar/{id}`.
- Confirmar que no se generan movimientos de Caja al abrir CxP.
- Confirmar que no se genera CxP automaticamente desde compras.
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

- QA automatica: pendiente de cierre en esta fase.
- QA manual: recomendada antes de autorizar Fase 3C.
- Bloqueante para commit Fase 3B: no, si las verificaciones automaticas pasan.
