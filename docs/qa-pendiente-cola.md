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
- Confirmar que `/cuentas-por-pagar/generacion-preview` muestra boton solo en compras elegibles.
- Generar CxP manualmente desde una compra recibida autorizada.
- Revisar listado y detalle CxP despues de generar.
- Revisar detalle de compra y preview para ver CxP vinculada.

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

### QA especifica Fase 3C-A

- Abrir `/cuentas-por-pagar/generacion-preview` autenticado.
- Confirmar que muestra compras recibidas del hotel actual.
- Confirmar que muestra links a compra y proveedor.
- Confirmar que muestra link a CxP solo si ya existe.
- Confirmar que las compras elegibles indican motivo de elegibilidad.
- Confirmar que las compras no elegibles indican motivo de bloqueo.
- Confirmar que no hay botones de pago, abono ni Caja.

### QA especifica Fase 3C-B

- Usar una compra recibida elegible.
- Presionar `Generar CxP`.
- Confirmar redireccion al detalle de la CxP.
- Confirmar que el preview cambia la compra a bloqueada por CxP existente.
- Intentar generar de nuevo y confirmar error claro.
- Confirmar que no cambia `movimientos_caja`.
- Confirmar que no hay pagos ni abonos.

## Resultado automatico Fase 3C-B

- Prueba local controlada ejecutada sobre compra `#5` del hotel `4`.
- Se genero CxP `#1`.
- La doble generacion fue bloqueada limpiamente.
- No se crearon movimientos en `cuentas_por_pagar_movimientos`.
- No cambiaron `cajas` ni `movimientos_caja`.
- Pendiente: QA manual autenticada en navegador.

## Resultado automatico Fase 3C-C

- Health checker actualizado para detectar inconsistencias CxP generadas desde compras.
- Preflight de compras minimas actualizado con las mismas reglas de consistencia.
- Preflight de recepcion de compras actualizado con las mismas reglas de consistencia.
- Validaciones automaticas agregadas:
  - CxP duplicada por compra/hotel;
  - CxP con compra inexistente;
  - CxP con proveedor inexistente;
  - CxP con `hotel_id` nulo;
  - CxP con compra de otro hotel;
  - CxP con proveedor de otro hotel;
  - CxP generada desde compra no recibida;
  - CxP con saldo mayor al total;
  - CxP con total invalido o saldo negativo;
  - CxP sin fecha de emision;
  - movimientos de Caja con referencia textual a CxP.
- Resultado actual de las validaciones: 0 inconsistencias CxP y 0 movimientos de Caja relacionados con CxP.
- Pendiente: QA manual autenticada en navegador para confirmar mensajes/flujo visual de 3C-B.

## Triage no relacionado

- Sin cambios no relacionados pendientes al iniciar 3C-0.
- El ajuste visual del modal de check-in tardio fue commiteado en `dc3c150`.

## QA Bloque Personal y Nomina (Fase NP)

### Estado NP-0

- Contrato y diagnostico documentados; sin funcionalidad, sin migraciones aplicadas, sin escritura en DB.
- No hay QA manual pendiente especifica de NP-0 mas alla de validar lectura del contrato.
- Git limpio al iniciar; HEAD `5dfe665`.

### QA critica planificada (NP)

- Confirmar que NO existe ningun movimiento de Caja generado por nomina (debe seguir en 0).
- Confirmar que un trabajador puede existir SIN usuario del sistema (sin login).
- Confirmar que `usuarios` no se altera de forma destructiva ni se borra.
- Confirmar que toda tabla `trabajador*` respeta `hotel_id` y no muestra datos de otro hotel.
- Confirmar que pagos/anticipos/prestamos son acciones explicitas (POST + CSRF), nunca automaticas.
- Confirmar que los saldos son derivados del ledger y no editables manualmente.
- Confirmar que `/api/sync` sigue bloqueado.

### QA funcional planificada (NP)

- NP-A: listado de trabajadores por hotel, ficha read-first, alta/edicion controlada, baja logica, documentos.
- NP-B: registrar pago/anticipo/prestamo con validacion de hotel, trabajador activo y monto valido; mensajes claros.
- NP-C: ver saldo a favor/en contra, historial y reporte semanal/quincenal por hotel y trabajador.
- NP-D: registrar asistencia y comisiones/bonos/descuentos y ver su efecto en saldos.

### QA regresion planificada (NP)

- Confirmar que Caja/cortes/movimientos no cambian al usar el modulo de nomina.
- Confirmar que `usuarios`/`hotel_usuarios` siguen intactos.
- Confirmar que mantenimiento/limpieza siguen funcionando (la referencia de responsable es logica/opcional).

### QA visual planificada (NP)

- Listado y ficha de trabajador en escritorio y movil.
- Estados vacios claros (sin trabajadores, sin movimientos).
- Consistencia visual con el resto del sistema; sin mezclar branding Medisoft SaaS con branding hotelero.
