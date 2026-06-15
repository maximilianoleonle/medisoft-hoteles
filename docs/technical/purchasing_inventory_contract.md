# Contrato de compras hacia inventario

Documento de diseno tecnico para Fase 2E. No autoriza crear tablas, ejecutar migraciones, construir Proveedores/Compras, tocar Caja ni activar Cuentas por Pagar.

## Objetivo

Preparar el contrato del futuro modulo de Proveedores/Compras para que, cuando se implemente, alimente el inventario moderno sin usar tablas legacy.

Contrato moderno obligatorio:

- Productos: `inventario_productos`.
- Movimientos: `movimientos_inventario`.
- Configuracion de consumo: `inventario_config_habitacion`.
- Hotel: todo registro operativo debe validar `hotel_id`.
- Usuario: toda accion sensible debe registrar `usuario_id`.

No usar para codigo nuevo:

- `productos`.
- `inventario_movimientos`.
- `inventario_habitacion_config`.
- `ProductoController`.
- Servicios legacy de configuracion de inventario.

## Contrato de recepcion de compras

Flujo recomendado:

1. Proveedor seleccionado.
2. Compra creada en estado `borrador`.
3. Detalle de compra agregado con productos de `inventario_productos`.
4. Cantidades y costos capturados.
5. Totales calculados en la compra, no en inventario.
6. Recepcion confirmada por usuario autorizado.
7. Stock incrementado en `inventario_productos`.
8. Movimiento `ENTRADA` registrado en `movimientos_inventario`.
9. Auditoria registrada para recepcion, cancelacion y pagos.

Cuando se crea la compra:

- No debe modificar stock.
- No debe crear movimientos de inventario.
- Debe quedar en `borrador`.
- Debe validar que proveedor y productos pertenezcan al hotel actual.
- Debe guardar usuario creador y fecha.

Cuando se recibe la compra:

- Debe validar que la compra pertenece al hotel actual.
- Debe validar que el estado permite recepcion.
- Debe validar que cada producto pertenece al hotel actual.
- Debe bloquear recepcion doble.
- Debe incrementar `inventario_productos.stock_actual`.
- Debe crear un movimiento `ENTRADA` por producto recibido.
- Debe registrar motivo `Compra recibida` con folio/id de compra.
- Debe guardar usuario responsable.
- Debe auditar antes/despues.

Cuando se cancela una compra recibida:

- No debe borrar movimientos.
- Debe generar movimientos compensatorios si se revierte stock.
- Debe bloquear cancelacion si ya existe pago aplicado, salvo flujo especial futuro.
- Debe auditar motivo, usuario y productos afectados.
- Debe conservar trazabilidad de la compra original.

Cuando se paga la compra:

- No debe tocar Caja en esta fase.
- No debe tocar Cuentas por Pagar en esta fase.
- En fase futura, el pago debe quedar separado del movimiento de inventario.
- El estado de pago puede cambiar a `pagada_parcial` o `pagada_total`.

## Estados sugeridos

Estados operativos de compra:

- `borrador`: captura editable; no afecta stock.
- `recibida`: productos ya ingresaron a inventario.
- `cancelada`: anulada; si habia recepcion requiere reversa documentada.
- `pagada_parcial`: hay pagos parciales.
- `pagada_total`: compra liquidada.

Reglas:

- Solo `borrador` puede pasar a `recibida`.
- Solo compras no recibidas pueden eliminarse logicamente; no borrar fisicamente.
- `recibida` no debe recibirse otra vez.
- Una compra pagada no debe cancelarse sin flujo contable posterior.

## Tablas sugeridas para fase futura

### `proveedores`

Proposito: catalogo de proveedores por hotel.

Columnas minimas:

- `id`.
- `hotel_id`.
- `nombre`.
- `razon_social`.
- `rfc`.
- `telefono`.
- `email`.
- `direccion`.
- `activo`.
- `created_by`.
- `updated_by`.
- `created_at`.
- `updated_at`.

Indices recomendados:

- `hotel_id`.
- `hotel_id, nombre`.
- `hotel_id, rfc`.

Relaciones:

- `hotel_id` hacia `hoteles.id`.
- `created_by` y `updated_by` hacia `usuarios.id` si se decide exigir FK.

Obligatorio:

- `hotel_id`.
- `nombre`.
- `activo`.

Puede esperar:

- Contactos multiples.
- Condiciones de credito.
- Datos fiscales avanzados.

Riesgo de implementarla ahora: medio, porque abre modulo nuevo.

Fase recomendada: Fase 2F, solo catalogo de proveedores, sin compras ni pagos.

### `proveedor_contactos`

Proposito: contactos multiples por proveedor.

Columnas minimas:

- `id`.
- `hotel_id`.
- `proveedor_id`.
- `nombre`.
- `puesto`.
- `telefono`.
- `email`.
- `principal`.
- `activo`.
- `created_at`.
- `updated_at`.

Indices recomendados:

- `hotel_id, proveedor_id`.
- `hotel_id, email`.

Relaciones:

- `proveedor_id` hacia `proveedores.id`.
- `hotel_id` para aislamiento y consultas.

Obligatorio:

- `hotel_id`.
- `proveedor_id`.
- `nombre`.

Puede esperar:

- Preferencias de contacto.
- Historial de comunicaciones.

Riesgo de implementarla ahora: medio-bajo, pero no es necesaria para una primera compra.

Fase recomendada: posterior a proveedores basicos.

### `compras`

Proposito: encabezado de compra.

Columnas minimas:

- `id`.
- `hotel_id`.
- `proveedor_id`.
- `folio`.
- `fecha_compra`.
- `fecha_recepcion`.
- `estado`.
- `subtotal`.
- `descuento_total`.
- `impuesto_total`.
- `total`.
- `notas`.
- `recibida_por`.
- `created_by`.
- `updated_by`.
- `created_at`.
- `updated_at`.

Indices recomendados:

- `hotel_id, folio`.
- `hotel_id, proveedor_id`.
- `hotel_id, estado`.
- `hotel_id, fecha_compra`.

Relaciones:

- `hotel_id` hacia `hoteles.id`.
- `proveedor_id` hacia `proveedores.id`.
- `recibida_por`, `created_by`, `updated_by` hacia `usuarios.id`.

Obligatorio:

- `hotel_id`.
- `proveedor_id`.
- `estado`.
- `fecha_compra`.
- `total`.

Puede esperar:

- Serie fiscal.
- Moneda multiple.
- Autorizaciones por monto.

Riesgo de implementarla ahora: alto, porque implica flujo de compras.

Fase recomendada: despues del catalogo de proveedores.

### `compra_detalles`

Proposito: productos y cantidades de una compra.

Columnas minimas:

- `id`.
- `hotel_id`.
- `compra_id`.
- `producto_id`.
- `cantidad`.
- `costo_unitario`.
- `descuento`.
- `impuesto`.
- `subtotal`.
- `total`.
- `cantidad_recibida`.
- `created_at`.
- `updated_at`.

Indices recomendados:

- `hotel_id, compra_id`.
- `hotel_id, producto_id`.

Relaciones:

- `compra_id` hacia `compras.id`.
- `producto_id` hacia `inventario_productos.id`.
- `hotel_id` para aislamiento.

Obligatorio:

- `hotel_id`.
- `compra_id`.
- `producto_id`.
- `cantidad`.
- `costo_unitario`.

Puede esperar:

- Lotes.
- Caducidades.
- Unidades equivalentes.

Riesgo de implementarla ahora: alto, porque afecta recepcion e inventario.

Fase recomendada: junto con `compras`, no antes.

### `compra_pagos`

Proposito: pagos vinculados a compras.

Columnas minimas:

- `id`.
- `hotel_id`.
- `compra_id`.
- `monto`.
- `metodo_pago`.
- `fecha_pago`.
- `referencia`.
- `estado`.
- `created_by`.
- `created_at`.

Indices recomendados:

- `hotel_id, compra_id`.
- `hotel_id, fecha_pago`.
- `hotel_id, estado`.

Relaciones:

- `compra_id` hacia `compras.id`.
- Futuro enlace con Caja o Cuentas por Pagar.

Obligatorio:

- `hotel_id`.
- `compra_id`.
- `monto`.
- `fecha_pago`.

Puede esperar:

- Conciliacion con caja.
- Comprobantes.
- Polizas.

Riesgo de implementarla ahora: alto, porque roza Caja y finanzas.

Fase recomendada: despues de Compras recibidas; no en 2F inicial.

### `cuentas_por_pagar`

Proposito: deuda administrativa derivada de compras recibidas o facturas.

Columnas minimas:

- `id`.
- `hotel_id`.
- `proveedor_id`.
- `compra_id`.
- `folio`.
- `monto_total`.
- `saldo`.
- `fecha_emision`.
- `fecha_vencimiento`.
- `estado`.
- `created_by`.
- `created_at`.
- `updated_at`.

Indices recomendados:

- `hotel_id, proveedor_id`.
- `hotel_id, estado`.
- `hotel_id, fecha_vencimiento`.

Relaciones:

- `proveedor_id` hacia `proveedores.id`.
- `compra_id` hacia `compras.id`.

Obligatorio:

- `hotel_id`.
- `proveedor_id`.
- `monto_total`.
- `saldo`.
- `estado`.

Puede esperar:

- Parcialidades avanzadas.
- Intereses.
- Reportes financieros.

Riesgo de implementarla ahora: muy alto.

Fase recomendada: fase separada de Cuentas por Pagar.

### Documentos o adjuntos

Proposito: comprobantes, facturas, remisiones o archivos asociados.

Columnas minimas futuras:

- `id`.
- `hotel_id`.
- `entidad_tipo`.
- `entidad_id`.
- `nombre_archivo`.
- `ruta`.
- `mime_type`.
- `tamano`.
- `uploaded_by`.
- `created_at`.

Indices recomendados:

- `hotel_id, entidad_tipo, entidad_id`.

Relaciones:

- Relacion polimorfica controlada por `entidad_tipo` y `entidad_id`, o tablas especificas si se prefiere mayor integridad.

Obligatorio:

- `hotel_id`.
- `entidad_tipo`.
- `entidad_id`.
- `ruta`.

Puede esperar:

- Versionado.
- OCR.
- Firma o validacion fiscal.

Riesgo de implementarla ahora: alto, porque abre Centro Documental.

Fase recomendada: integracion posterior, no en Proveedores/Compras inicial.

## Contrato de movimiento de inventario para compras

El esquema actual de `movimientos_inventario` sirve parcialmente:

- `hotel_id`: obligatorio para aislamiento.
- `producto_id`: producto de `inventario_productos`.
- `tipo_movimiento`: usar `ENTRADA` para compras recibidas.
- `cantidad`: cantidad recibida.
- `stock_anterior`: stock antes de recibir.
- `stock_posterior`: stock despues de recibir.
- `motivo`: texto `Compra recibida: {folio o id}`.
- `usuario_id`: usuario que confirma recepcion.
- `created_at`: fecha de movimiento.

Limitacion actual:

- No existe `compra_id`.
- No existe `compra_detalle_id`.
- No hay campo `origen` o `referencia_tipo`.

Recomendacion futura:

- Agregar una referencia formal en fase de migracion posterior, por ejemplo `compra_id` y opcionalmente `compra_detalle_id`.
- No sobrecargar `reservacion_id`.
- No usar `habitacion_id`.
- No romper reportes actuales: seguir usando `tipo_movimiento = ENTRADA`.
- Diferenciar entrada manual vs compra con `motivo` y futura columna de origen.

Entrada manual:

- `tipo_movimiento = AJUSTE`.
- Motivo manual.
- Sale desde `/inventario/ajuste/{id}`.

Entrada por compra:

- `tipo_movimiento = ENTRADA`.
- Motivo `Compra recibida`.
- Debe estar vinculada a `compras` cuando exista la columna futura.

## Validaciones multihotel

Toda operacion futura debe:

- Tomar `hotel_id` del contexto activo.
- Validar proveedor por `hotel_id`.
- Validar compra por `hotel_id`.
- Validar detalle por `hotel_id`.
- Validar producto por `inventario_productos.hotel_id`.
- Rechazar productos de otro hotel como no encontrados.
- Rechazar proveedores de otro hotel como no encontrados.
- Registrar `usuario_id` responsable.

## Relacion futura con Caja

No implementar todavia.

Compras recibidas no deben crear movimientos de caja automaticamente. En fase futura, Caja debe registrar pagos reales, no recepciones de inventario. La relacion con Caja debe ser opcional y trazable mediante pagos o egresos aprobados.

## Relacion futura con Cuentas por Pagar

No implementar todavia.

Una compra recibida y no pagada podria crear una cuenta por pagar en fase futura. Esa decision debe hacerse en un modulo financiero separado para no mezclar stock con deuda.

## Relacion futura con documentos

No implementar todavia.

Facturas, remisiones o comprobantes deben asociarse a `compras` cuando exista un contrato de documentos. No guardar rutas ni archivos desde compras inicial si no existe politica de almacenamiento.

## Relacion futura con auditoria

Eventos sugeridos:

- `compras.creada`.
- `compras.actualizada`.
- `compras.recibida`.
- `compras.cancelada`.
- `compras.pago_registrado`.

Datos minimos:

- `hotel_id`.
- `usuario_id`.
- `proveedor_id`.
- `compra_id`.
- Totales antes/despues.
- Productos y cantidades afectadas.
- IP y user agent si `AuditService` lo soporta.

## Que implementar en la siguiente fase

Fase sugerida 2F:

- Solo catalogo `proveedores`.
- CRUD minimo scoped por `hotel_id`.
- Sin compras.
- Sin caja.
- Sin cuentas por pagar.
- Sin documentos.
- Auditoria minima de altas/cambios/desactivaciones si es segura.

## Actualizacion Fase 2F

Implementado de forma minima:

- Migracion `migrations/20260614_004_fase_2f_catalogo_proveedores.sql`.
- Tabla `proveedores` con `hotel_id`, datos basicos, estado `activo`, usuario creador/actualizador y timestamps.
- Rutas CRUD minimas bajo `/proveedores`.
- Modelo `Proveedor` aislado por `hotel_id`.
- Auditoria gradual para `proveedores.creado`, `proveedores.actualizado`, `proveedores.desactivado` y `proveedores.reactivado`.

Exclusiones confirmadas:

- Sin compras.
- Sin recepcion de inventario.
- Sin movimientos de caja.
- Sin pagos.
- Sin cuentas por pagar.
- Sin documentos ni adjuntos.
- Sin migracion del texto historico `movimientos_caja.proveedor`.

Rollback manual documentado:

- Si la fase debe retirarse antes de ser usada por fases posteriores, verificar primero `SELECT COUNT(*) FROM proveedores;`.
- Despues de confirmar que no existen referencias futuras, ejecutar `DROP TABLE proveedores;`.
- Quitar el registro `20260614_004_fase_2f_catalogo_proveedores.sql` de `migrations`.
- No ejecutar rollback si una fase posterior ya creo compras, documentos, cuentas por pagar o referencias hacia `proveedores`.

## Actualizacion Fase 2G

Implementado de forma minima:

- Enlace de sidebar hacia `/proveedores`.
- El enlace se muestra bajo el mismo gate visual de `inventario`.
- Health checker valida que Proveedores este expuesto sin activar Compras/CxP.

Exclusiones confirmadas:

- Sin nueva clave en `modulos`.
- Sin cambios en `hotel_modulos`.
- Sin compras.
- Sin recepcion de inventario.
- Sin movimientos de caja.
- Sin pagos ni cuentas por pagar.
- Sin documentos.

## Actualizacion Fase 2H

Implementado de forma minima:

- `ProveedorController::before()` valida `require_hotel_module('inventario')`.
- El acceso directo a `/proveedores` queda alineado con el sidebar de Fase 2G.
- Health checker valida que el gate de modulo siga presente.

Exclusiones confirmadas:

- Sin modulo nuevo `proveedores`.
- Sin cambios en `modulos`, `hotel_modulos` ni `plan_modulos`.
- Sin compras.
- Sin recepcion de inventario.
- Sin movimientos de caja.
- Sin pagos ni cuentas por pagar.
- Sin documentos.

## Actualizacion Fase 2I

Implementado de forma minima:

- Validacion en `Proveedor` para evitar RFC repetido por hotel.
- Validacion en `Proveedor` para evitar nombre activo repetido por hotel.
- La edicion del mismo proveedor queda excluida de la validacion de duplicado.
- Health checker revisa duplicados existentes de RFC y nombre activo.

Exclusiones confirmadas:

- Sin indice unico nuevo.
- Sin migracion.
- Sin limpieza o fusion de proveedores existentes.
- Sin compras.
- Sin recepcion de inventario.
- Sin movimientos de caja.
- Sin pagos ni cuentas por pagar.
- Sin documentos.

## Actualizacion Fase 2J

Implementado con backup previo:

- Migracion `migrations/20260615_001_fase_2j_unique_proveedores.sql`.
- Indice unico `uk_proveedores_hotel_rfc (hotel_id, rfc)`.
- Columna generada `nombre_activo_key`.
- Indice unico `uk_proveedores_hotel_nombre_activo (hotel_id, nombre_activo_key)`.

Comportamiento esperado:

- RFC repetido por hotel queda bloqueado por MySQL si `rfc` no es `NULL`.
- Nombre activo repetido por hotel queda bloqueado por MySQL.
- Nombres de proveedores inactivos pueden repetirse porque `nombre_activo_key` es `NULL`.
- La validacion de aplicacion sigue dando mensajes de error mas claros antes del constraint.

Exclusiones confirmadas:

- Sin compras.
- Sin recepcion de inventario.
- Sin movimientos de caja.
- Sin pagos ni cuentas por pagar.
- Sin documentos.
- Sin fusion ni limpieza de proveedores historicos.

Fase posterior:

- `compras` y `compra_detalles` en borrador.
- Recepcion con transaccion y rollback probado.
- Movimiento `ENTRADA` por producto recibido.
- Auditoria de recepcion.

## Actualizacion Fase 2K

Implementado solo como preparacion de reconciliacion:

- Script de lectura `src/tools/saas/reconciliar_proveedores_dry_run.php`.
- Origen por defecto: `medisoft_hoteles.movimientos_caja.proveedor`.
- Destino por defecto: `medisoft_hoteles_import.proveedores`.
- Hotel destino por defecto: `los-cedros`.
- La base historica no tiene tabla `proveedores`; los nombres historicos de Caja son evidencia operativa, no un catalogo autorizado.
- El script clasifica candidatos y conflictos antes de cualquier importacion real.

Exclusiones confirmadas:

- Sin inserts en `proveedores`.
- Sin updates en `movimientos_caja`.
- Sin tabla staging en DB.
- Sin compras.
- Sin recepcion de inventario.
- Sin pagos ni cuentas por pagar.
- Sin documentos.

Comando dry-run recomendado:

```bash
docker compose exec -T app php tools/saas/reconciliar_proveedores_dry_run.php --source-db=medisoft_hoteles --target-db=medisoft_hoteles_import --target-hotel-slug=los-cedros
```

Regla para fase posterior:

- Una importacion real debe ser un script separado, idempotente, con backup previo y rollback documentado.
- Debe excluir nombres ambiguos (`no se`, `sin definir`, `Huesped`) salvo validacion humana.
- Debe conservar los indices unicos de Fase 2J como defensa final.

## Actualizacion Fase 2L

Aplicado con backup previo:

- Script `src/tools/saas/importar_proveedores_los_cedros.php`.
- Archivo revisable `src/tools/saas/proveedores_los_cedros_reconciliation.json`.
- Backup `src/storage/backups/phase2l_20260615_010612_medisoft_hoteles_import.sql`.
- SHA256 `061CAFCBC91F9AD45D342CD0FEB29D38B0C3F183B0AE6070EDEE8DB00349793D`.
- Inserta 29 proveedores aprobados para Los Cedros desde evidencia historica de `movimientos_caja.proveedor`.
- Omite placeholders y textos ambiguos marcados como no aprobados: `huésped`, `no se`, `sin definir`.
- Revisa existencia por nombre normalizado y hotel antes de insertar.
- Usa los constraints de Fase 2J como defensa final contra duplicados.
- El dry-run posterior devuelve `already_exists=29`, confirmando idempotencia.

Exclusiones confirmadas:

- Sin actualizacion de `movimientos_caja`.
- Sin campo `proveedor_id` en Caja.
- Sin compras.
- Sin recepcion de inventario.
- Sin pagos ni cuentas por pagar.
- Sin documentos.
- Sin cambios financieros.

Comando de simulacion:

```bash
docker compose exec -T app php tools/saas/importar_proveedores_los_cedros.php
```

Comando de aplicacion futura:

```bash
docker compose exec -T app php tools/saas/importar_proveedores_los_cedros.php --apply --backup-file=/ruta/backup.sql
```

## Actualizacion Fase 2M

Preparacion tecnica solamente:

- Script de solo lectura `src/tools/saas/preflight_compras_minimas.php`.
- Valida prerrequisitos para una futura implementacion minima de compras.
- Confirma que Los Cedros tiene proveedores activos importados.
- Confirma que el inventario moderno usa `inventario_productos`, `inventario_categorias` y `movimientos_inventario` con `hotel_id`.
- Confirma que no existen tablas futuras de compras/CxP/documentos.
- Confirma que no existen rutas activas `/compras`, `/cuentas-por-pagar`, `/proveedor-contactos` ni `/documentos-proveedor`.

Contrato futuro minimo propuesto:

- `compras`: encabezado por hotel, proveedor, folio opcional, estado y totales informativos.
- `compra_detalles`: productos recibidos, cantidad, costo unitario y subtotal.
- Estados iniciales permitidos: `borrador`, `recibida`, `cancelada`.
- Solo `recibida` podria generar entradas en `movimientos_inventario`.
- La recepcion debe ser transaccional e idempotente.
- La recepcion debe validar `hotel_id` de proveedor y productos.
- La auditoria minima debe registrar creacion, recepcion y cancelacion.

Exclusiones confirmadas:

- Sin migracion.
- Sin tablas nuevas.
- Sin rutas nuevas.
- Sin controladores/modelos/vistas de compras.
- Sin movimientos de caja.
- Sin pagos de compras.
- Sin cuentas por pagar.
- Sin documentos.
- Sin cambios financieros.

Comando de preflight:

```bash
docker compose exec -T app php tools/saas/preflight_compras_minimas.php
```

## Actualizacion Fase 2N

Preparacion de migracion en borrador, sin ejecucion:

- Borrador SQL `docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql`.
- No ejecutar desde `docs/technical/sql_drafts`.
- No esta dentro de `migrations/` para evitar ejecucion accidental o falso pendiente del checker.
- Debe promoverse a `migrations/20260615_002_fase_2n_compras_minimas.sql` solo con backup completo y autorizacion explicita.

Tablas propuestas:

- `compras`: encabezado con `hotel_id`, `proveedor_id`, `folio`, fechas, estado y totales informativos.
- `compra_detalles`: detalle con `compra_id`, `hotel_id`, `producto_id`, cantidad, costo y referencia opcional a `movimientos_inventario`.

Estados propuestos:

- `borrador`
- `recibida`
- `cancelada`

Guardas del borrador:

- `CREATE TABLE IF NOT EXISTS` para ejecucion repetida.
- Validacion posterior de columnas minimas mediante `information_schema`.
- Llaves foraneas a `hoteles`, `proveedores`, `inventario_productos`, `movimientos_inventario` y `usuarios`.
- Checks de cantidades e importes no negativos.
- `uk_compras_hotel_folio` para evitar folios repetidos por hotel cuando exista folio.
- `uk_compra_detalles_movimiento` para evitar enlazar un mismo movimiento de inventario a mas de un detalle.

Exclusiones confirmadas:

- Sin ejecucion de migracion.
- Sin tablas nuevas en la base actual.
- Sin rutas, controladores, modelos ni vistas.
- Sin recepcion de inventario implementada.
- Sin movimientos de caja.
- Sin pagos ni cuentas por pagar.
- Sin documentos.
- Sin cambios financieros.

Rollback futuro documentado en el borrador:

- Confirmar backup.
- Confirmar conteos de `compras` y `compra_detalles`.
- Si no hay datos operativos, dropear primero `compra_detalles` y luego `compras`.
- Quitar registro de `migrations`.
- Si existen datos reales, no ejecutar rollback generico.

## Actualizacion Fase 2O

Aplicada en local con backup previo:

- Backup `src/storage/backups/phase2o_20260615_013321_medisoft_hoteles_import.sql`.
- SHA256 `1EB06B9AC7992F6DDC103BF56A694355BEE278646451EE82C792AA50C87BB889`.
- Migracion oficial `migrations/20260615_002_fase_2n_compras_minimas.sql`.
- Tablas creadas: `compras` y `compra_detalles`.
- Registro en `migrations`: `20260615_002_fase_2n_compras_minimas.sql`.
- Conteo inicial confirmado: `compras = 0`, `compra_detalles = 0`.

Alcance aplicado:

- Base tecnica minima para compras.
- Sin rutas nuevas.
- Sin controladores nuevos.
- Sin modelos nuevos.
- Sin vistas nuevas.
- Sin recepcion de inventario.
- Sin movimientos de caja.
- Sin pagos ni cuentas por pagar.
- Sin documentos.
- Sin cambios financieros.

Regla de uso:

- No insertar datos manualmente en `compras` ni `compra_detalles`.
- La proxima fase debe crear un servicio transaccional antes de cualquier UI.
- La recepcion futura debe validar `hotel_id` de compra, proveedor, producto y movimiento.
- No registrar pagos/CxP/Caja desde compras minimas.

Comando de validacion:

```bash
docker compose exec -T app php tools/saas/preflight_compras_minimas.php
```

## Actualizacion Fase 2P

Capa interna minima agregada, sin exposicion publica:

- Servicio `src/app/services/CompraService.php`.
- Metodo `crearBorrador()` para registrar encabezado y detalles de compra.
- Metodo `recibirCompra()` para recepcion transaccional futura.
- Metodo `obtenerCompra()` para lectura interna.

Contrato tecnico:

- `crearBorrador()` valida hotel, proveedor activo, productos activos y folio por hotel.
- `crearBorrador()` inserta en `compras` y `compra_detalles`.
- `recibirCompra()` solo opera compras en estado `borrador`.
- `recibirCompra()` bloquea compra, detalles y productos con transaccion.
- `recibirCompra()` incrementa `inventario_productos.stock_actual`.
- `recibirCompra()` registra `movimientos_inventario.tipo_movimiento = 'ENTRADA'`.
- `recibirCompra()` vincula cada detalle con `movimiento_inventario_id`.
- `recibirCompra()` marca la compra como `recibida`.
- Ambas operaciones registran auditoria mediante `AuditService`.

Guardas:

- Sin rutas publicas.
- Sin vistas.
- Sin controladores.
- Sin menu/sidebar.
- Sin ejecucion automatica.
- Sin movimientos de caja.
- Sin pagos.
- Sin cuentas por pagar.
- Sin documentos.
- Sin cambios financieros.

Validacion obligatoria antes de una fase con UI:

```bash
docker compose exec -T app php -l app/services/CompraService.php
docker compose exec -T app php tools/saas/preflight_compras_minimas.php
```

## Actualizacion Fase 2Q

Herramienta CLI de prueba controlada:

- Script `src/tools/saas/probar_compra_service.php`.
- Por defecto ejecuta solo lectura.
- El modo de ejercicio real requiere `--apply`, `--backup-file` y `--confirm=ROLLBACK_TEST`.
- El modo `--apply` usa transaccion externa sobre `CompraService`.
- El modo `--apply` ejecuta `crearBorrador()` y `recibirCompra()` dentro de la misma transaccion.
- El modo `--apply` siempre ejecuta rollback; no debe dejar `compras`, `compra_detalles`, stock, movimientos ni auditoria persistidos.
- El script no contiene `commit()`.

Comando solo lectura:

```bash
docker compose exec -T app php tools/saas/probar_compra_service.php
```

Comando de ejercicio con rollback, solo despues de backup:

```bash
docker compose exec -T app php tools/saas/probar_compra_service.php --apply --backup-file=/var/www/html/storage/backups/archivo.sql --confirm=ROLLBACK_TEST
```

Guardas:

- Sin rutas publicas.
- Sin UI.
- Sin controller.
- Sin pagos.
- Sin CxP.
- Sin documentos.
- Sin movimientos de caja.
- Sin persistencia esperada despues del rollback.

Ejecucion local controlada:

- Backup `src/storage/backups/phase2q_20260615_015327_medisoft_hoteles_import.sql`.
- SHA256 `B44E581BDDE09A08C1451E02B4E06B53AB55041303CD705560EF7BA91713D5CE`.
- Comando ejecutado con `--apply --backup-file=/var/www/html/storage/backups/phase2q_20260615_015327_medisoft_hoteles_import.sql --confirm=ROLLBACK_TEST`.
- Fixture: Los Cedros, proveedor `Tortillería San José`, producto `Papel Higiénico`.
- Resultado: rollback verificado.
- Antes: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`, `stock=339.00`.
- Dentro de transaccion: `compras=1`, `compra_detalles=1`, `movimientos_inventario=86`, `logs_auditoria=5`, `stock=340.00`.
- Despues de rollback: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`, `stock=339.00`.

## Actualizacion Fase 2R

UI interna minima para compras en borrador:

- Controlador `src/app/controllers/CompraController.php`.
- Vistas `src/app/views/compras/index.php` y `src/app/views/compras/form.php`.
- Sidebar operativo enlaza `/compras` bajo el mismo gate visual de `inventario`.
- Rutas registradas:
  - `GET /compras`
  - `GET /compras/crear`
  - `POST /compras`
- `CompraController::before()` requiere autenticacion, contexto de hotel y `require_hotel_module('inventario')`.
- `POST /compras` usa CSRF y delega exclusivamente en `CompraService::crearBorrador()`.
- La UI lista compras y permite crear encabezado/detalles en estado `borrador`.

Exclusiones confirmadas:

- Sin recepcion de compra desde UI.
- Sin boton ni ruta de recepcion.
- Sin pagos de compras.
- Sin cuentas por pagar.
- Sin documentos ni adjuntos.
- Sin movimientos de caja.
- Sin cambios financieros.
- Sin cambios de stock al crear borradores.
- Sin rutas nuevas para `proveedor_contactos`.

Regla operativa:

- Las compras creadas desde UI deben permanecer en `estado = borrador`.
- `compra_detalles.movimiento_inventario_id` debe quedar `NULL`.
- Cualquier fase futura de recepcion debe exigir backup, prueba transaccional y confirmacion explicita.

## Actualizacion Fase 2S

Cierre tecnico posterior a prueba manual de UI:

- Validacion realizada con consultas de solo lectura.
- No se ejecutaron migraciones.
- No se hicieron inserts, updates, deletes, alters ni drops durante esta validacion.
- La prueba manual del usuario creo un borrador real correctamente.
- Resultado confirmado por el usuario: "todo correcto".

Estado observado en `medisoft_hoteles_import`:

- `compras = 1`.
- `compra_detalles = 3`.
- `compras_no_borrador = 0`.
- `detalles_con_movimiento = 0`.
- `movimientos_recepcion_compra = 0`.
- Auditoria `compras.creada = 1`.

Borrador observado:

- `compra_id = 2`.
- `hotel_id = 4`.
- Hotel: `Maximiliano Leon`.
- Proveedor: `Juan Pedro`.
- Estado: `borrador`.
- Total: `1900.00`.
- Fecha de creacion en sesion app DB `-06:00`: `2026-06-15 02:11:54`.
- Detalles: 3 lineas.
- Movimientos vinculados: 0.

Conclusiones:

- La UI de Fase 2R escribe solo borradores.
- Crear un borrador no crea `movimientos_inventario`.
- Crear un borrador no enlaza `compra_detalles.movimiento_inventario_id`.
- No se crearon pagos, CxP, documentos ni movimientos de caja.
- El health checker y el preflight aceptan borradores reales siempre que sigan en estado `borrador` y sin movimientos vinculados.

Pendiente para fase futura:

- Definir si la recepcion permitira lineas repetidas del mismo producto o si la UI debera consolidarlas antes de recibir.
- No exponer boton de recepcion sin backup, prueba transaccional y rollback documentado.

## Actualizacion Fase 2T

Preflight de recepcion futura, sin recibir compras:

- Script nuevo: `src/tools/saas/preflight_recepcion_compras.php`.
- Sin recepcion real.
- Solo lectura con `START TRANSACTION READ ONLY`.
- No crea rutas.
- No crea botones.
- No ejecuta `CompraService::recibirCompra()`.
- No modifica stock.
- No crea `movimientos_inventario`.
- No cambia estados de `compras`.
- No toca Caja, pagos, CxP ni documentos.

Validaciones principales:

- Tablas y columnas minimas: `compras`, `compra_detalles`, `proveedores`, `inventario_productos`, `movimientos_inventario`, `logs_auditoria` y `hoteles`.
- Borradores con `hotel_id`.
- Proveedor activo del mismo hotel.
- Productos activos del mismo hotel.
- Detalles con cantidad positiva e importes no negativos.
- `detalles_con_movimiento = 0` antes de habilitar recepcion.
- `movimientos_recepcion_compra = 0` como senal de que no hay recepciones persistidas desde esta fase.
- Rutas publicas limitadas a `GET /compras`, `GET /compras/crear` y `POST /compras`.
- Vistas sin enlaces a recepcion, pagos, CxP o documentos.

Resultado esperado para el borrador manual actual:

- Debe seguir en `estado = borrador`.
- Debe seguir sin `movimiento_inventario_id`.
- Puede emitir `WARNING` por productos repetidos.

Decision pendiente:

- El borrador manual contiene productos repetidos.
- Antes de recepcion real se debe decidir si esos productos repetidos se consolidan en una sola linea por producto o si se permite crear un movimiento de inventario por cada linea.
- Esta decision queda pendiente deliberadamente para no cambiar comportamiento ni datos en Fase 2T.

## Actualizacion Fase 2U

Decision tecnica para productos repetidos:

- Se permite que una compra tenga el mismo producto en mas de una linea.
- La recepcion futura debe generar un movimiento por linea de detalle.
- No se consolidan lineas automaticamente.
- No se modifica el borrador del usuario para fusionar cantidades.
- La trazabilidad queda en `compra_detalles.movimiento_inventario_id`.
- La idempotencia se mantiene con `compras.estado = 'borrador'` antes de recibir y `movimiento_inventario_id IS NULL` por detalle.

Motivo:

- Consolidar silenciosamente cambiaria la captura original.
- Un movimiento por linea conserva el historial exacto de lo que se capturo.
- Los reportes de inventario ya pueden sumar multiples movimientos del mismo producto.

Prueba controlada:

- `src/tools/saas/probar_compra_service.php` acepta `--duplicate-line`.
- El modo `--duplicate-line` crea dos lineas temporales del mismo producto.
- La prueba espera dos movimientos de inventario y dos detalles vinculados.
- La prueba exige `--backup-file` y `--confirm=ROLLBACK_TEST`.
- La prueba siempre usa rollback y no debe dejar compras, detalles, movimientos, auditoria ni stock persistidos.

Comando recomendado:

```bash
docker compose exec -T app php tools/saas/probar_compra_service.php --apply --duplicate-line --backup-file=/var/www/html/storage/backups/archivo.sql --confirm=ROLLBACK_TEST
```

Ejecucion local controlada:

- Backup `src/storage/backups/phase2u_20260615_023632_medisoft_hoteles_import.sql`.
- SHA256 `E123A6E8D37A6C6978BCE01FC1EF3F837C06D2F61B319AC32AF1224014E93CE6`.
- Fixture: Los Cedros, proveedor `Tortilleria San Jose`, producto `Papel Higienico`.
- Modo: `--apply --duplicate-line --confirm=ROLLBACK_TEST`.
- Compra temporal: `#3`.
- Movimientos esperados: 2.
- Movimientos recibidos dentro de transaccion: 2.
- Detalles vinculados dentro de transaccion: 2.
- Antes: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`, `stock=339.00`.
- Dentro de transaccion: `compras=1`, `compra_detalles=2`, `movimientos_inventario=87`, `logs_auditoria=5`, `stock=341.00`.
- Despues del rollback: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`, `stock=339.00`.
- Resultado: rollback verificado, sin persistencia operativa.

Exclusiones:

- Sin recepcion visible en UI.
- Sin rutas nuevas.
- Sin botones nuevos.
- Sin pagos, CxP, documentos ni Caja.

## Actualizacion Fase 2V

Recepcion minima real desde Compras:

- Ruta nueva permitida: `POST /compras/{id}/recibir`.
- Controlador: `CompraController::recibirAction()`.
- Servicio reutilizado: `CompraService::recibirCompra()`.
- Vista: boton `Recibir` solo para compras en `estado = borrador`.
- El formulario usa `POST` y `csrf_field()`.
- El acceso mantiene `require_hotel_module('inventario')`.

Efecto esperado al recibir:

- Cambia `compras.estado` de `borrador` a `recibida`.
- Llena `fecha_recepcion` y `recibida_por`.
- Incrementa `inventario_productos.stock_actual`.
- Crea un `movimientos_inventario` tipo `ENTRADA` por cada linea de detalle.
- Vincula cada detalle mediante `compra_detalles.movimiento_inventario_id`.
- Registra auditoria `compras.recibida`.

Guardas:

- Solo recibe compras del hotel actual.
- Solo recibe compras en `estado = borrador`.
- No recibe una linea que ya tenga `movimiento_inventario_id`.
- No registra pagos.
- No crea CxP.
- No toca `movimientos_caja`.
- No crea documentos.
- No cambia calculos financieros.

Prueba previa requerida:

- Ejecutar `probar_compra_service.php --apply --duplicate-line` con backup y rollback.
- Verificar `preflight_recepcion_compras.php` sin errores.
- Verificar `health_check_fase_1a.php` sin errores.

Ejecucion local controlada:

- Backup `src/storage/backups/phase2v_20260615_024412_medisoft_hoteles_import.sql`.
- SHA256 `B9FA594C0C960213D9EA3D105DB46216D7C8C5012E7C97BF479B225B0559EB6D`.
- Prueba ejecutada con `--apply --duplicate-line --confirm=ROLLBACK_TEST`.
- Compra temporal: `#4`.
- Movimientos esperados: 2.
- Movimientos recibidos dentro de transaccion: 2.
- Detalles vinculados dentro de transaccion: 2.
- Antes: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`, `stock=339.00`.
- Dentro de transaccion: `compras=1`, `compra_detalles=2`, `movimientos_inventario=87`, `logs_auditoria=5`, `stock=341.00`.
- Despues del rollback: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`, `stock=339.00`.
- Resultado: rollback verificado, sin persistencia operativa.

## Actualizacion Fase 2W

Recepcion real controlada de la compra #2:

- Backup previo: `src/storage/backups/phase2w_20260615_024841_before_receive_compra_2_medisoft_hoteles_import.sql`.
- SHA256: `CDEE8C67C1AA6C4CFD316C6A1081C6EDB936D1CB507E637B357910B84EE579E6`.
- Hotel: Maximiliano (`hotel_id = 4`).
- Usuario responsable: `adminmax` (`usuario_id = 24`).
- Compra recibida: compra #2.
- Estado anterior: `borrador`.
- Estado posterior: `recibida`.
- `fecha_recepcion`: `2026-06-15 02:49:12`.
- `recibida_por`: `24`.

Movimientos generados:

- Detalle #2, producto #39 `H4-JABON`: movimiento #993, cantidad `100.00`, stock `50.00 -> 150.00`.
- Detalle #3, producto #38 `H4-AGUA`: movimiento #994, cantidad `100.00`, stock `74.00 -> 174.00`.
- Detalle #4, producto #39 `H4-JABON`: movimiento #995, cantidad `100.00`, stock `150.00 -> 250.00`.

Validacion post-recepcion:

- Cada detalle de compra quedo vinculado con `movimiento_inventario_id`.
- Se respetó la regla Fase 2U: producto repetido genera un movimiento por linea.
- Se registro auditoria `compras.recibida` para compra #2.
- `inventario_productos.stock_actual` quedo en `174.00` para `H4-AGUA`.
- `inventario_productos.stock_actual` quedo en `250.00` para `H4-JABON`.

Exclusiones confirmadas:

- Sin pagos.
- Sin cuentas por pagar.
- Sin movimientos de caja.
- Sin documentos.
- Sin cambios en calculos financieros.
- Sin cambios en `/api/sync`.

## Actualizacion Fase 2X

Detalle de compra de solo lectura:

- Ruta nueva permitida: `GET /compras/{id}`.
- Controlador: `CompraController::verAction()`.
- Vista: `app/views/compras/ver.php`.
- Servicio reutilizado: `CompraService::obtenerCompra()`.
- El detalle muestra proveedor, folio, fechas, estado, totales, lineas y movimientos vinculados.
- La consulta de detalles incluye `movimientos_inventario` mediante `LEFT JOIN` para mostrar stock anterior/posterior.

Alcance:

- Solo lectura.
- Sin formularios nuevos de escritura.
- Sin pagos.
- Sin cuentas por pagar.
- Sin movimientos de caja.
- Sin documentos.
- Sin cambios en calculos financieros.
- Sin cambios en `/api/sync`.

Validacion esperada:

- La compra #2 recibida debe mostrar tres lineas.
- Cada linea debe mostrar su `movimiento_inventario_id`.
- `H4-JABON` debe aparecer dos veces con movimientos separados.
- No debe aparecer ningun enlace o accion de pago, CxP o documentos.

Validacion manual:

- Usuario confirmo que la compra aparece como `recibida` en la pantalla.
- Resultado manual reportado: correcto.

## Actualizacion Fase 2Y

Historial y reportes read-only de compras:

- Ruta nueva permitida: `GET /compras/reportes/recibidas`.
- Controlador: `CompraController::reporteRecibidasAction()`.
- Vista: `app/views/compras/reporte_recibidas.php`.
- Servicio: `CompraService::reporteRecibidas()`.
- Catalogos de filtros: `CompraService::catalogosReporteRecibidas()`.
- Filtros soportados: proveedor, producto, rango de fechas y estado.
- Totales mostrados: compras, proveedores, productos, cantidad recibida y subtotal de lineas.
- Agregados read-only: totales por proveedor y totales por producto.
- Cada linea conserva link al detalle `GET /compras/{id}`.

Contrato multihotel:

- Todas las consultas filtran por `c.hotel_id = ?`.
- Los joins a proveedores, detalles, productos y movimientos usan el `hotel_id` de la compra o del detalle.
- No se consulta ni escribe en tablas legacy de inventario.

Alcance:

- Solo lectura.
- Sin formularios POST nuevos.
- Sin pagos.
- Sin cuentas por pagar.
- Sin movimientos de caja.
- Sin documentos.
- Sin cambios en calculos financieros.
- Sin cambios en `/api/sync`.

## Actualizacion Fase 2Z

Endurecimiento de compras minimas antes de CxP:

- `CompraService::recibirCompra()` conserva transaccion propia y bloqueo `FOR UPDATE`.
- Nueva guarda interna: `assertCompraPuedeRecibirse()`.
- Nueva guarda interna: `assertDetallesPuedenRecibirse()`.
- Mensaje explicito si una compra ya esta `recibida`.
- Mensaje explicito si una compra esta `cancelada`.
- Bloqueo si una compra tiene `fecha_recepcion` antes de recibir.
- Bloqueo si un detalle ya tiene `movimiento_inventario_id`.
- Bloqueo si un detalle tiene cantidad o importes invalidos.
- Mensaje mas claro cuando el `UPDATE compras ... estado = 'borrador'` no actualiza filas.
- Navegacion read-only desde detalle hacia `/compras/reportes/recibidas`.

Alcance:

- Sin rutas de escritura nuevas.
- Sin pagos.
- Sin cuentas por pagar.
- Sin movimientos de caja.
- Sin documentos.
- Sin cambios en calculos financieros.
- Sin cambios en `/api/sync`.

## Actualizacion Fase 3A

Ficha read-only de proveedor e historial de compras:

- Ruta nueva permitida: `GET /proveedores/{id}`.
- Controlador: `ProveedorController::verAction()`.
- Vista: `app/views/proveedores/ver.php`.
- Modelo: `Proveedor::resumenComprasPorProveedor()`.
- Modelo: `Proveedor::comprasRecientesPorProveedor()`.
- El listado `app/views/proveedores/index.php` agrega solo un enlace GET hacia la ficha.
- La ficha muestra datos de contacto, estado, resumen de compras y compras recientes del proveedor.
- Las compras recientes enlazan al detalle `GET /compras/{id}`.
- La ficha enlaza al reporte `GET /compras/reportes/recibidas?proveedor_id={id}`.

Contrato multihotel:

- El proveedor se obtiene con `buscarPorIdHotel($id, $hotelId)`.
- Todas las consultas de compras usan `hotel_id` y `proveedor_id`.
- Si las tablas `compras` o `compra_detalles` no existen, la ficha muestra resumen vacio sin fallar.

Alcance:

- Solo lectura.
- Sin formularios POST nuevos.
- Sin pagos.
- Sin cuentas por pagar.
- Sin movimientos de caja.
- Sin documentos.
- Sin escrituras en `compras` o `compra_detalles`.
- Sin escrituras en `inventario_productos` ni `movimientos_inventario`.
- Sin cambios en calculos financieros.
- Sin cambios en `/api/sync`.

## No implementar todavia

- Pagos de compras.
- Cuentas por pagar.
- Movimientos de caja.
- Documentos o adjuntos.
- Recepcion doble.
- Cancelacion contable de compras pagadas.
- Migracion de tablas legacy.
- Nuevas rutas contra `productos`.

## Riesgos

- Duplicar entradas de inventario si una compra recibida puede recibirse dos veces.
- Mezclar productos entre hoteles si no se valida `hotel_id`.
- Mezclar proveedor global con proveedor de hotel si no se define alcance.
- Romper reportes si se introduce un nuevo `tipo_movimiento` sin actualizar vistas.
- Tocar Caja prematuramente y alterar calculos financieros.
- Crear Cuentas por Pagar sin contrato contable.

## Checklist para Fase 2F

- Backup completo antes de cualquier migracion.
- Migracion idempotente y reversible documentada.
- `hotel_id` en toda tabla nueva.
- Validacion de rutas activas antes de exponer pantallas.
- `php -l` en PHP modificado.
- Health checker actualizado.
- Prueba de aislamiento por hotel.
- Confirmar `/api/sync` en HTTP 423.
- No tocar Caja ni calculos financieros.
