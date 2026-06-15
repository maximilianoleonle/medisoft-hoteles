# Reconciliacion tecnica de inventario

Documento de Fase 2A. No autoriza borrar, renombrar, fusionar tablas ni migrar datos.

Actualizacion Fase 2B: se habilita el ajuste manual moderno por producto sin migraciones ni cambios estructurales de DB.

Actualizacion Fase 2C: se congela explicitamente el codigo legacy que mezcla `inventario_config_habitacion` con `productos`, y se documenta el contrato moderno para configuracion, reportes, movimientos, APIs y ajuste.

Actualizacion Fase 2D: se valida el guardado real de configuracion con rollback, se agregan validaciones server-side para producto/tipo/cantidad, y se neutralizan escrituras peligrosas del helper legacy.

Actualizacion Fase 2E: se agrega auditoria minima tolerante a fallos para cambios reales en configuracion de inventario, se decide no implementar endpoint nuevo de preview por reservacion, y se documenta el contrato futuro en `purchasing_inventory_contract.md`.

## Estado de base

Base activa: `medisoft_hoteles_import`.

No se hicieron cambios de base de datos en Fase 2A. La reconciliacion se limita a contrato tecnico, documentacion, verificador y una correccion de lectura en el PDF de movimientos.

## Contrato moderno recomendado

| Responsabilidad | Tabla moderna | Codigo oficial |
| --- | --- | --- |
| Productos operativos de inventario | `inventario_productos` | `InventarioController`, `Inventario`, `InventarioService` |
| Movimientos de inventario | `movimientos_inventario` | `MovimientoInventario`, `InventarioService`, `Reservacion::devolverInventarioCancelacion()` |
| Configuracion de consumo por tipo de habitacion | `inventario_config_habitacion` | `Inventario`, `InventarioService`, `ReservacionController` |
| Alertas de stock bajo | derivadas desde `inventario_productos` | `ApiController::alertasInventarioAction()` |

Regla para codigo nuevo: no usar `productos`, `inventario_movimientos` ni `inventario_habitacion_config` para funcionalidades nuevas.

## Contrato Fase 2C: inventario moderno vs legacy congelado

Flujo oficial actual:

- Productos/stock: `inventario_productos`, siempre scoped por `hotel_id`.
- Movimientos: `movimientos_inventario`, siempre scoped por `hotel_id`.
- Configuracion de consumo por tipo de habitacion: `inventario_config_habitacion`, unida a `inventario_productos` por `producto_id` y por el mismo `hotel_id`.
- Configuracion UI oficial: `GET /inventario/configuracion` y `POST /inventario/guardarConfiguracion`.
- Movimientos UI oficial: `GET /inventario/movimientos`.
- Exportacion/PDF oficial: `GET /inventario/exportar` y `POST /inventario/generarPdfMovimientos`.
- APIs oficiales: `/api/inventario/alertas`, `/api/inventario/verificar-stock/{id}`, `/api/inventario/preview-checkin/{id}`.
- Ajuste oficial: `GET/POST /inventario/ajuste/{id}`.

Archivos legacy congelados en Fase 2C:

- `src/app/models/ConfiguracionInventario.php`.
- `src/app/services/ConfiguracionInventarioService.php`.
- `src/app/models/InventarioReportes.php`.
- `src/app/helpers/integracion_inventario.php`.
- `src/app/views/inventario/ConfiguracionHabitacionService.php`.

Motivo del congelamiento:

- Unen `inventario_config_habitacion` contra `productos`, no contra `inventario_productos`.
- No tienen aislamiento multihotel completo.
- Algunos contienen ejemplos o mantenimiento con escrituras peligrosas.
- No son el flujo oficial ruteado actualmente.

Recomendacion temporal:

- No registrar rutas nuevas hacia esos archivos.
- No usarlos para codigo nuevo.
- No borrarlos todavia.
- Migrarlos o retirarlos solo en una fase posterior con backup, pruebas funcionales y rollback.

Notas de riesgo:

- `src/app/controllers/ProductoController.php` permanece como controlador legacy sin rutas activas conocidas.
- `ApiController::getProductosDescuentoReservacion()` contiene una dependencia legacy no ruteada hacia `ConfiguracionHabitacionService`; no debe registrarse. Si se necesita preview real, debe reconstruirse sobre `InventarioService` scoped.
- `src/app/views/inventario/configuracion-habitacion.php` queda como vista legacy desconectada; el flujo oficial es `src/app/views/inventario/configuracion.php`.

## Contrato Fase 2D: guardado de configuracion

Flujo oficial:

- Pantalla: `GET /inventario/configuracion`.
- Guardado: `POST /inventario/guardarConfiguracion`.
- Controller: `InventarioController::configuracionAction()` y `InventarioController::guardarConfiguracionAction()`.
- Modelo: `Inventario::actualizarConfiguracion()`.
- Tabla de escritura: `inventario_config_habitacion`.
- Producto relacionado: `inventario_productos.id`.
- Tipo relacionado: `tipos_habitacion.codigo`.
- Hotel: siempre `hotel_id` del contexto actual.

Validaciones server-side obligatorias:

- `tipo_habitacion` debe existir en `tipos_habitacion` para el hotel actual y estar activo.
- `producto_id` debe existir en `inventario_productos` para el hotel actual, estar activo y tener `descuento_automatico = 1`.
- `cantidad` debe ser numerica y no negativa.
- Producto de otro hotel se bloquea como no encontrado para el hotel actual.
- Tipo de otro hotel se bloquea como no encontrado para el hotel actual.
- Cantidad `0` desactiva configuracion existente o no inserta nada si no existe.
- El controller no castea ni silencia cantidades antes de delegar al modelo.

Prueba Fase 2D con rollback:

- Backup previo: `backups/phase2d_20260614_223635_medisoft_hoteles_import.sql`.
- Caso valido: `tipo=sencilla`, `producto_id=1`, `cantidad=1`.
- Resultado dentro de transaccion: `guardado_valido_transaccion=OK`.
- Fingerprint antes: `total=58`, `hash=c168cf9733e51afecb678f85efdb9ea7`.
- Fingerprint durante la transaccion: `total=58`, `hash=49cce2750b9d257b442a9332077841bf`.
- Fingerprint despues del rollback: `total=58`, `hash=c168cf9733e51afecb678f85efdb9ea7`.
- Resultado: `rollback_intacto=SI`.

Casos bloqueados en la prueba:

- Producto inexistente.
- Producto de otro hotel.
- Cantidad negativa.
- Cantidad no numerica.
- Tipo inexistente.
- Tipo de otro hotel.

No quedaron cambios persistentes en DB.

## Contrato Fase 2D: preview y consumo por check-in

Lecturas seguras:

- `GET /api/inventario/verificar-stock/{id}` delega a `InventarioService::verificarDisponibilidad()`.
- `InventarioService::verificarDisponibilidad()` lee `habitaciones`, `inventario_config_habitacion` e `inventario_productos` con `hotel_id`.
- No modifica stock.

Consumo real:

- `ReservacionController::procesarDescuentoInventario()` llama a `InventarioService::verificarDisponibilidad()` y `InventarioService::descontarInventarioCheckIn()`.
- `InventarioService::descontarInventarioCheckIn()` actualiza `inventario_productos` y registra `movimientos_inventario`, ambos con `hotel_id`.
- No se cambio este flujo en Fase 2D.

Preview por reservacion:

- `GET /api/inventario/preview-checkin/{id}` valida reservacion/habitaciones por `hotel_id`, pero mantiene respuesta controlada: `Preview de inventario aun no disponible con fuente scoped`.
- `ApiController::getProductosDescuentoReservacion()` queda como metodo legacy no ruteado y congelado. No debe exponerse; si una fase futura requiere preview por reservacion, debe reconstruirse sobre `InventarioService` y tablas modernas.

## Neutralizacion Fase 2D: `integracion_inventario.php`

`src/app/helpers/integracion_inventario.php` sigue siendo guia historica congelada, no helper activo.

Medidas aplicadas:

- Marca `LEGACY INVENTARIO FASE 2D`.
- Funciones de escritura bloqueadas por defecto mediante `inventario_legacy_fase2d_escritura_permitida()`.
- Para permitirlas haria falta definir explicitamente `MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES === true`, lo cual no debe hacerse en operacion normal.
- Quedan bloqueadas por defecto:
  - `configurar_inventario_tipo_habitacion()`.
  - `mantenimiento_inventario()`.

Motivo:

- El archivo contiene SQL historico peligroso, incluyendo `DELETE FROM movimientos_inventario` y `UPDATE productos`.
- No se borra el archivo porque puede servir para auditoria historica, pero no debe ejecutarse accidentalmente.

## Contrato Fase 2E: auditoria de configuracion

Evento agregado:

- `inventario.configuracion_actualizada`.

Alcance:

- Solo se registra cuando `Inventario::actualizarConfiguracion()` reporta cambios reales.
- No audita envios sin cambios reales.
- No audita entrada/salida/check-in.
- No agrega columnas ni migraciones.
- No cambia el formulario ni la UI.
- Si la auditoria falla, el guardado no debe romperse.

Datos registrados:

- `hotel_id`.
- `usuario_id`.
- `entidad_tipo = inventario_configuracion`.
- `entidad_id = hotel:{hotel_id}`.
- `datos_antes` con tipo de habitacion, producto, cantidad anterior y estado anterior.
- `datos_despues` con tipo de habitacion, producto, cantidad nueva, estado nuevo, `descuento_automatico_cambio = false` y origen `inventario/configuracion`.
- IP y user agent quedan a cargo de `AuditService` si estan disponibles.

Implementacion:

- `Inventario::actualizarConfiguracion()` devuelve metadatos auditables: `changed`, `tipo_habitacion`, `producto_id`, `producto_nombre`, `cantidad_anterior`, `cantidad_nueva`, `activo_anterior`, `activo_nuevo`.
- `InventarioController::guardarConfiguracionAction()` acumula cambios y llama a `registrarAuditoriaConfiguracionInventario()`.
- La auditoria se agrega como un solo evento por guardado para evitar ruido por celda.

Prueba Fase 2E con rollback:

- Backup previo: `backups/phase2e_20260614_230754_medisoft_hoteles_import.sql`.
- Ejecutada dentro de transaccion para `tipo=sencilla`, `producto_id=1`, `cantidad_prueba=2`.
- Antes: `inventario_config_habitacion total=58`, `hash=77d163676e338b74aa747fe0d99fb250`, `logs_auditoria=4`.
- Durante: `inventario_config_habitacion total=58`, `hash=7ade83ec5efed097dff82a1e6d8e2223`, `logs_auditoria=5`.
- Auditoria dentro de transaccion: `inventario.configuracion_actualizada`, `entidad_tipo=inventario_configuracion`, con `datos_antes` y `datos_despues`.
- Despues del rollback: `inventario_config_habitacion total=58`, `hash=77d163676e338b74aa747fe0d99fb250`, `logs_auditoria=4`.
- Resultado: `rollback_intacto=SI`.

## Contrato Fase 2E: compras hacia inventario

Compras/Proveedores no se implementan en esta fase.

Documento rector:

- `docs/technical/purchasing_inventory_contract.md`.

Decision:

- Una compra futura en `borrador` no debe modificar stock.
- Una compra futura `recibida` debe incrementar `inventario_productos.stock_actual`.
- La recepcion futura debe registrar `tipo_movimiento = ENTRADA` en `movimientos_inventario`.
- El motivo debe identificar compra/folio hasta que exista `compra_id`.
- En fase futura conviene agregar referencia formal a compra, sin romper reportes actuales.
- Caja, Cuentas por Pagar y Documentos quedan fuera.

## Alcance Fase 2F: proveedores sin compras

Decision:

- Se permite crear solo el catalogo `proveedores` por `hotel_id`.
- La migracion controlada es `migrations/20260614_004_fase_2f_catalogo_proveedores.sql`.
- El CRUD minimo de proveedores no modifica `inventario_productos`.
- El CRUD minimo de proveedores no crea registros en `movimientos_inventario`.
- El CRUD minimo de proveedores no toca `movimientos_caja`, cortes, cajas, pagos ni reportes financieros.
- No se reconcilia `movimientos_caja.proveedor`; ese texto queda como historico hasta una fase especifica de Caja.
- La auditoria permitida es solo sobre eventos `proveedores.creado`, `proveedores.actualizado`, `proveedores.desactivado` y `proveedores.reactivado`.

Siguiente paso despues de validar esta fase:

- Mantener proveedores como catalogo aislado.
- Antes de construir compras, definir tablas `compras` y `compra_detalles` con transaccion de recepcion y rollback probado.
- No agregar Cuentas por Pagar, pagos, documentos ni movimientos de caja en la fase inmediata.

## Alcance Fase 2G: exposicion controlada de proveedores

Decision:

- Se agrega acceso visual a `/proveedores` desde el sidebar operativo.
- El enlace depende del gate visual de `inventario`; no se crea clave nueva en `modulos`.
- No se modifica `hotel_modulos`.
- No se agregan rutas de compras, pagos, CxP ni documentos.
- No se modifica stock ni movimientos de inventario.
- No se reconcilia `movimientos_caja.proveedor`.

Validacion esperada:

- El health checker debe confirmar que el sidebar expone Proveedores bajo Inventario.
- El health checker debe fallar si aparecen enlaces de Compras/CxP en el sidebar.
- `/api/sync` debe seguir bloqueado con HTTP 423.

## Alcance Fase 2H: gate de modulo para proveedores

Decision:

- `ProveedorController::before()` requiere el modulo `inventario`.
- Esto alinea el acceso directo a `/proveedores` con el sidebar, que solo muestra Proveedores cuando Inventario esta visible.
- No se crea modulo `proveedores`.
- No se modifica `modulos`, `hotel_modulos` ni `plan_modulos`.
- No se agregan compras, pagos, CxP, documentos ni movimientos de inventario.

Validacion esperada:

- El health checker debe confirmar `require_hotel_module('inventario')` en `ProveedorController`.
- Todos los hoteles activos que usan Proveedores deben conservar `inventario` activo.
- `/api/sync` debe seguir bloqueado con HTTP 423.

## Alcance Fase 2I: validacion de duplicados en proveedores

Decision:

- `Proveedor` valida duplicados antes de crear o actualizar.
- RFC no debe repetirse dentro del mismo hotel cuando se captura.
- Nombre no debe repetirse dentro del mismo hotel entre proveedores activos.
- Editar el mismo proveedor no cuenta como duplicado.
- No se agrega indice unico ni migracion en esta fase.
- No se fusionan proveedores duplicados existentes.
- No se crean compras, CxP, documentos ni movimientos de inventario.

Limitacion consciente:

- La proteccion es de aplicacion, no de constraint SQL. Una fase posterior puede agregar indices unicos si se aprueba una migracion con plan de limpieza previo.

## Alcance Fase 2J: constraints SQL de proveedores

Decision:

- Se agrega la migracion `migrations/20260615_001_fase_2j_unique_proveedores.sql`.
- `uk_proveedores_hotel_rfc` bloquea RFC repetido por hotel cuando `rfc` no es `NULL`.
- `nombre_activo_key` es una columna generada desde `nombre` solo cuando `activo = 1`.
- `uk_proveedores_hotel_nombre_activo` bloquea nombres activos repetidos por hotel.
- Proveedores inactivos pueden conservar nombres historicos repetidos porque `nombre_activo_key` queda `NULL`.
- La validacion de aplicacion de Fase 2I se mantiene para dar mensajes claros antes de que MySQL rechace el cambio.

Rollback manual:

- `ALTER TABLE proveedores DROP INDEX uk_proveedores_hotel_nombre_activo;`
- `ALTER TABLE proveedores DROP INDEX uk_proveedores_hotel_rfc;`
- `ALTER TABLE proveedores DROP COLUMN nombre_activo_key;`
- `DELETE FROM migrations WHERE nombre = '20260615_001_fase_2j_unique_proveedores.sql';`

Exclusiones:

- No se crean compras.
- No se toca stock ni movimientos de inventario.
- No se toca Caja, pagos, CxP ni documentos.
- No se migra `movimientos_caja.proveedor`.

## Contrato Fase 2E: preview por reservacion

Decision: no implementar endpoint nuevo en Fase 2E.

Motivo:

- No se encontro consumo frontend activo que exija preview por reservacion.
- `GET /api/inventario/verificar-stock/{id}` ya cubre verificacion por habitacion usando `InventarioService::verificarDisponibilidad()`.
- `GET /api/inventario/preview-checkin/{id}` queda como respuesta controlada y de lectura pura.
- `ApiController::getProductosDescuentoReservacion()` sigue congelado/no ruteado.

Si una fase futura requiere preview por reservacion:

- Debe construirse como lectura pura.
- Debe validar reservacion, habitaciones y productos por `hotel_id`.
- Debe usar `inventario_productos` e `inventario_config_habitacion`.
- No debe modificar stock.
- No debe usar `productos` ni `inventario_habitacion_config`.

## Contrato Fase 2B: ajuste manual de stock

Rutas oficiales:

- `GET /inventario/ajuste/{id}` muestra el formulario de ajuste para un producto de `inventario_productos` del hotel actual.
- `POST /inventario/ajuste/{id}` procesa el ajuste del mismo producto.

Entradas del formulario:

- `tipo`: `ENTRADA` o `SALIDA`, usado como direccion del ajuste.
- `cantidad`: cantidad positiva mayor a cero.
- `motivo`: obligatorio.
- `observaciones`: opcional.

Reglas de escritura:

- El producto se obtiene con `Inventario::getByIdWithCategory($id)`, que filtra por `hotel_id`.
- El stock se actualiza con `Inventario::actualizarProductoBase()`, que aplica `WHERE id = ? AND hotel_id = ?`.
- El movimiento se registra con `MovimientoInventario::crearMovimientoManual()`.
- El movimiento usa `tipo_movimiento = AJUSTE`; la direccion queda reflejada por `stock_anterior`, `stock_posterior` y `diferencia` en auditoria.
- No permite dejar `stock_actual` negativo.
- La operacion corre en transaccion.
- La auditoria se registra con `AuditService::record('inventario.ajuste_stock', ...)` en `logs_auditoria`.
- Si la auditoria falla, `AuditService` es tolerante y no debe romper el ajuste.

Tablas usadas:

- `inventario_productos`.
- `movimientos_inventario`.
- `logs_auditoria`.

Tablas no usadas por el ajuste Fase 2B:

- `productos`.
- `inventario_movimientos`.
- `inventario_habitacion_config`.

## Tablas modernas

### `inventario_productos`

- Registros: 35.
- Tiene `hotel_id`: si.
- Hoteles presentes: 2.
- Fechas: de `2025-08-26 21:53:04` a `2026-06-10 04:14:00`.
- Uso principal: catalogo/stock operativo por hotel.
- Codigo que la usa: `InventarioController`, `Inventario`, `MovimientoInventario`, `InventarioService`, `ApiController`, `Reservacion`, `NotificacionReglasService`, `ReporteGerencialDiarioService`.
- Riesgo: medio. Es la fuente activa de stock.
- Recomendacion: mantener como fuente principal y no mezclar con `productos`.

### `movimientos_inventario`

- Registros: 112.
- Tiene `hotel_id`: si.
- Hoteles presentes: 2.
- Fechas: de `2026-02-07 02:21:09` a `2026-06-13 00:30:49`.
- Uso principal: trazabilidad de entradas, salidas, ajustes y descuentos por check-in.
- Codigo que la usa: `MovimientoInventario`, `InventarioService`, `InventarioController`, `Reservacion`, `integracion_inventario.php`, algunos debug helpers.
- Riesgo: alto para escritura; medio para lectura. Afecta stock y devoluciones de reservacion.
- Recomendacion: mantener como tabla oficial de movimientos; auditar acciones manuales en Fase 2B antes de extender.

### `inventario_config_habitacion`

- Registros: 58.
- Tiene `hotel_id`: si.
- Hoteles presentes: 2.
- Fechas: de `2025-09-09 19:55:33` a `2026-06-10 04:14:00`.
- Uso principal: consumo automatico por tipo de habitacion.
- Codigo que la usa: `Inventario`, `InventarioService`, `ReservacionController`, `ConfiguracionInventario` y servicios legacy.
- Riesgo: medio. Controla descuentos automaticos por check-in.
- Recomendacion: mantener como tabla oficial, pero revisar servicios legacy que la unen contra `productos`.

## Tablas legacy congeladas

### `productos`

- Registros: 5.
- Tiene `hotel_id`: no.
- Fechas: todos los registros visibles son de `2025-08-25 03:34:05`.
- Parece semilla o modulo antiguo paralelo.
- Codigo que la usa: `Producto`, `ProductoController`, `ConfiguracionInventario`, `ConfiguracionInventarioService`, `InventarioReportes`, `integracion_inventario.php` y vistas legacy/orfanas.
- Rutas activas: no se encontraron rutas activas hacia `ProductoController`.
- Riesgo de borrar: alto, por referencias legacy.
- Recomendacion temporal: congelar. No enlazar desde navegacion ni usar en codigo nuevo.

### `inventario_movimientos`

- Registros: 0.
- Tiene `hotel_id`: no.
- Fechas: sin registros.
- Parece tabla historica o paralela no usada por el flujo actual.
- Codigo activo encontrado: no se encontraron consumidores directos en `src/app`.
- Riesgo de borrar: medio, por incertidumbre historica.
- Recomendacion temporal: congelar. Mantener hasta fase de limpieza con respaldo.

### `inventario_habitacion_config`

- Registros: 0.
- Tiene `hotel_id`: no.
- Fechas: sin registros.
- Parece variante antigua por habitacion, distinta a la configuracion moderna por tipo de habitacion.
- Codigo activo encontrado: no se encontraron consumidores directos en `src/app`.
- Riesgo de borrar: medio, porque el nombre sugiere otra granularidad funcional.
- Recomendacion temporal: congelar. Mantener sin uso nuevo.

## Rutas oficiales de inventario

Rutas activas recomendadas:

- `GET /inventario`
- `GET /inventario/nuevo`
- `POST /inventario/guardar`
- `GET /inventario/editar/{id}`
- `POST /inventario/actualizar/{id}`
- `POST /inventario/eliminar/{id}`
- `GET /inventario/entrada`
- `POST /inventario/procesarEntrada`
- `GET /inventario/salida`
- `POST /inventario/procesarSalida`
- `GET /inventario/configuracion`
- `POST /inventario/guardarConfiguracion`
- `GET /inventario/movimientos`
- `GET /inventarios/movimientos` como alias temporal
- `GET /inventario/exportar`
- `POST /inventario/generarPdfMovimientos`
- `GET /inventario/ajuste/{id}`
- `POST /inventario/ajuste/{id}`
- `GET /api/inventario/alertas`
- `GET /api/inventario/verificar-stock/{id}`
- `GET /api/inventario/preview-checkin/{id}` con respuesta controlada mientras no haya preview scoped completo

## Rutas legacy o no activas

- `inventario/ajuste/{id}`: no registrar en Fase 2A.
- `inventario/configuracion-habitacion`: vista legacy/orfana; el flujo oficial actual es `inventario/configuracion`.
- `inventarios/reportes`, `inventarios/exportar-reporte`, `inventarios/exportar-reporte-pdf`: vistas/reportes legacy sin rutas oficiales actuales.
- `inventarios/crear`: aparece en vista legacy `inventario/detalle.php`; no es ruta oficial.
- `ProductoController`: mantener solo por compatibilidad de codigo, sin rutas visibles.

## Decision sobre `inventario/ajuste/{id}`

No se registra la ruta en Fase 2A.

Motivos:

- La vista `inventario/ajuste.php` espera `$producto`, pero `InventarioController::ajusteAction()` entrega `$productos`.
- El formulario publica a `inventario/ajuste/{id}`, pero `procesarAjusteAction()` no toma `{id}` y espera `producto_id` en POST.
- Ya existen flujos oficiales para `entrada` y `salida`.
- Activar ajuste manual toca stock y `movimientos_inventario`; debe auditarse y probarse en una fase especifica.

Recomendacion: Fase 2B debe decidir si el ajuste sera una pantalla por producto, una accion desde editar producto o un flujo general con selector.

Decision Fase 2B:

- Se eligio pantalla por producto porque la vista ya publicaba a `inventario/ajuste/{id}`.
- Se conserva el formulario por diferencia (`tipo` + `cantidad`) para no renombrar campos existentes.
- El controller ya no espera `producto_id` oculto ni `stock_nuevo` en POST.
- La ruta queda activa solo para productos del hotel actual.
- La accion registra movimiento moderno y auditoria minima.

## Correccion aplicada en Fase 2A

`InventarioController::generarPdfMovimientosAction()` ahora filtra `movimientos_inventario` por `hotel_id` y une `inventario_productos`/`habitaciones` contra el mismo hotel. Antes el reporte podia leer movimientos de otros hoteles.

No se cambiaron calculos financieros, stock, entradas, salidas ni check-in/check-out.

## Auditoria recomendada para Fase 2B

Acciones de inventario que deberian auditarse despues:

- Entrada manual de inventario.
- Salida manual de inventario.
- Ajuste manual de stock.
- Cambio de stock minimo.
- Edicion de producto.
- Desactivacion de producto.
- Cambio de configuracion por habitacion.

No se implementa auditoria de inventario en Fase 2A.

## Validaciones antes de fusionar o limpiar

1. Backup completo de `medisoft_hoteles_import` y `medisoft_hoteles`.
2. Confirmar cero rutas activas hacia `ProductoController`.
3. Confirmar cero inserts recientes en `productos`, `inventario_movimientos` e `inventario_habitacion_config`.
4. Mapear cualquier referencia externa o script cron.
5. Probar entrada, salida, movimientos, configuracion y check-in en entorno local.
6. Definir rollback manual por tabla.

## Pendiente para Fase 2B

- Completado: ajuste manual de stock con contrato unico por diferencia.
- Completado: auditoria gradual solo para ajuste manual.
- Mantener `/api/sync` bloqueado.

## Cierre Fase 2C

- Completado: los servicios legacy que unen `inventario_config_habitacion` con `productos` quedan congelados.
- Completado: `InventarioReportes`, `ConfiguracionInventario`, `ConfiguracionInventarioService`, `integracion_inventario.php` y `ConfiguracionHabitacionService.php` quedan marcados como legacy.
- Completado: el checker valida contrato moderno y advierte si esos archivos vuelven a usarse como base de nuevas rutas.
- Completado en Fase 2D: escritura real de configuracion por habitacion probada con transaccion y rollback.

## Cierre Fase 2D

- Completado: guardado de configuracion probado con rollback.
- Completado: validaciones server-side minimas agregadas para configuracion.
- Completado: decision sobre `ApiController::getProductosDescuentoReservacion()` como legacy congelado no ruteado.
- Completado: `integracion_inventario.php` neutralizado con guarda para escrituras legacy.

## Pendiente antes de Proveedores/Compras

- Definir si Compras alimentara `inventario_productos` directamente o mediante una tabla nueva de recepciones.
- No reutilizar `productos` ni `ProductoController`.
- Diseñar auditoria para cambios de configuracion, recepciones de compra y ajustes de stock.
- Si se necesita preview por reservacion, crear endpoint nuevo scoped y no reactivar `getProductosDescuentoReservacion()`.

## Alcance Fase 2K: dry-run de reconciliacion de proveedores historicos

Implementado solo como herramienta de lectura:

- Script `src/tools/saas/reconciliar_proveedores_dry_run.php`.
- Compara `medisoft_hoteles.movimientos_caja.proveedor` contra `medisoft_hoteles_import.proveedores`.
- La base historica `medisoft_hoteles` no tiene tabla `proveedores`; por eso los textos de caja se tratan como candidatos, no como catalogo confiable.
- El hotel destino por defecto es `los-cedros`, porque la base historica es mono-hotel.
- Clasifica nombres como `candidato_catalogo`, `requiere_revision`, `ya_existe_catalogo` o `conflicto_nombre_normalizado`.
- No inserta proveedores.
- No modifica `movimientos_caja`.
- No vincula movimientos existentes a `proveedores.id`.
- No crea compras, recepciones, CxP, pagos ni documentos.

Uso recomendado:

```bash
docker compose exec -T app php tools/saas/reconciliar_proveedores_dry_run.php --source-db=medisoft_hoteles --target-db=medisoft_hoteles_import --target-hotel-slug=los-cedros
```

Antes de una importacion real futura:

1. Revisar manualmente los candidatos `requiere_revision`.
2. Excluir placeholders como `no se`, `sin definir` o `Huesped`.
3. Decidir si nombres personales representan proveedores reales.
4. Preparar backup completo.
5. Crear un script idempotente separado que use `Proveedor::crearParaHotel()` o SQL transaccional controlado.
6. Mantener los constraints de Fase 2J como ultima defensa contra duplicados.

## Alcance Fase 2L: importacion controlada de proveedores Los Cedros

Aplicado con backup previo:

- Script `src/tools/saas/importar_proveedores_los_cedros.php`.
- Archivo de revision `src/tools/saas/proveedores_los_cedros_reconciliation.json`.
- Backup `src/storage/backups/phase2l_20260615_010612_medisoft_hoteles_import.sql`.
- SHA256 `061CAFCBC91F9AD45D342CD0FEB29D38B0C3F183B0AE6070EDEE8DB00349793D`.
- Insertados 29 proveedores activos para Los Cedros.
- Excluidos candidatos ambiguos con `"approved": false`: `huésped`, `no se` y `sin definir`.
- La idempotencia se valida por nombre normalizado de proveedor activo dentro del hotel destino.
- No modifica `movimientos_caja`.
- No vincula movimientos historicos a `proveedores.id`.
- No crea compras, recepciones, CxP, pagos ni documentos.

Comando de simulacion:

```bash
docker compose exec -T app php tools/saas/importar_proveedores_los_cedros.php
```

Comando de aplicacion futura, solo despues de backup:

```bash
docker compose exec -T app php tools/saas/importar_proveedores_los_cedros.php --apply --backup-file=/ruta/backup.sql
```

Validacion post-aplicacion:

- `proveedores` tiene 29 registros para `hotel_id=1`.
- El dry-run posterior devuelve `already_exists=29` y `would_insert=0`.
- No hay nombres activos duplicados por hotel.
- No se insertaron los excluidos `huésped`, `no se` ni `sin definir`.

Rollback manual si se aplica:

1. Identificar proveedores con notas que contengan `Importado desde movimientos_caja.proveedor en Fase 2L`.
2. Confirmar que no tienen referencias futuras en compras, pagos, documentos o CxP.
3. Desactivarlos desde la UI o eliminarlos manualmente solo si no tienen referencias, segun la politica operativa vigente.
4. Restaurar backup si se requiere volver al estado exacto anterior.

## Alcance Fase 2M: preflight de compras minimas

Implementado solo como verificacion tecnica:

- Script `src/tools/saas/preflight_compras_minimas.php`.
- No crea migraciones.
- No crea tablas `compras` ni `compra_detalles`.
- No crea rutas ni controladores de compras.
- No toca `movimientos_caja`, pagos, CxP ni documentos.
- Valida que el futuro flujo de compras use `proveedores`, `inventario_productos` y `movimientos_inventario` con `hotel_id`.
- Valida que no se reactive `productos`, `inventario_movimientos` ni `ProductoController`.

Reglas para futura recepcion:

1. Recibir productos solo contra `inventario_productos` del hotel actual.
2. Registrar entradas solo en `movimientos_inventario`.
3. No escribir en `productos` ni `inventario_movimientos`.
4. No escribir en Caja desde la recepcion minima.
5. Separar pagos/CxP/documentos para una fase posterior.

## Alcance Fase 2N: borrador SQL de compras minimas

Implementado solo como documento tecnico ejecutable en el futuro:

- Borrador `docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql`.
- No se mueve a `migrations/`.
- No se ejecuta contra ninguna base.
- No crea `compras` ni `compra_detalles` en el estado actual.
- No crea rutas, controladores, modelos ni vistas.
- No toca Caja, pagos, CxP, documentos ni `/api/sync`.

Relacion futura con inventario:

- `compra_detalles.producto_id` apunta a `inventario_productos.id`.
- `compra_detalles.movimiento_inventario_id` queda opcional para enlazar la entrada generada al recibir.
- La recepcion futura debe crear `movimientos_inventario.tipo_movimiento = 'ENTRADA'`.
- La recepcion futura debe validar que compra, proveedor, producto y movimiento pertenecen al mismo `hotel_id`.
- La idempotencia futura debe impedir recibir dos veces una misma compra.

Validacion requerida antes de promover:

```bash
docker compose exec -T app php tools/saas/preflight_compras_minimas.php
```

## Alcance Fase 2O: migracion aplicada de compras minimas

Aplicado en local despues de backup:

- Backup `src/storage/backups/phase2o_20260615_013321_medisoft_hoteles_import.sql`.
- SHA256 `1EB06B9AC7992F6DDC103BF56A694355BEE278646451EE82C792AA50C87BB889`.
- Migracion `migrations/20260615_002_fase_2n_compras_minimas.sql`.
- Tablas creadas: `compras` y `compra_detalles`.
- Ambas tablas quedaron vacias.

Relacion con inventario despues de aplicar:

- No se genero ningun `movimientos_inventario`.
- `compra_detalles.movimiento_inventario_id` queda preparado como referencia opcional.
- No hay recepcion implementada.
- No hay rutas ni UI para escribir compras.
- No hay integracion con Caja, pagos, CxP ni documentos.

Validacion post-aplicacion:

- `compras` tiene `hotel_id`, `proveedor_id`, estado y totales informativos.
- `compra_detalles` tiene `hotel_id`, `producto_id` y referencia opcional a `movimientos_inventario`.
- La migracion quedo registrada en `migrations`.
- `/api/sync` debe seguir bloqueado con HTTP 423.

## Alcance Fase 2P: servicio interno de compras

Implementado solo como capa interna:

- Servicio `src/app/services/CompraService.php`.
- Sin rutas.
- Sin UI.
- Sin controlador.
- Sin enlaces en sidebar.
- No se ejecuto ninguna compra desde el servicio durante esta fase.

Relacion con inventario:

- La recepcion futura usa `inventario_productos` por `hotel_id`.
- Cada producto se bloquea en transaccion antes de actualizar stock.
- Cada entrada futura queda en `movimientos_inventario` con tipo `ENTRADA`.
- Cada detalle queda enlazado a su `movimiento_inventario_id`.
- La idempotencia se apoya en estado `borrador` y en que el detalle no tenga movimiento vinculado.

Exclusiones:

- No se genera ningun movimiento de inventario en esta fase.
- No se toca `productos`.
- No se toca `inventario_movimientos`.
- No se toca Caja.
- No se crean pagos, CxP ni documentos.

## Alcance Fase 2Q: prueba CLI con rollback

Implementado como herramienta tecnica:

- Script `src/tools/saas/probar_compra_service.php`.
- Modo por defecto: solo lectura.
- Modo `--apply`: requiere backup, `--confirm=ROLLBACK_TEST` y rollback obligatorio.
- El script ejercita `CompraService::crearBorrador()` y `CompraService::recibirCompra()`.
- La prueba con rollback puede crear una entrada `movimientos_inventario.tipo_movimiento = 'ENTRADA'` solo dentro de la transaccion.
- El rollback debe restaurar conteos de `compras`, `compra_detalles`, `movimientos_inventario`, `logs_auditoria` y stock del producto.

Validacion esperada despues del rollback:

- `compras` queda con el mismo conteo inicial.
- `compra_detalles` queda con el mismo conteo inicial.
- `movimientos_inventario` queda con el mismo conteo inicial.
- `logs_auditoria` queda con el mismo conteo inicial.
- `inventario_productos.stock_actual` queda con el mismo valor inicial.
- No existen pagos, CxP, documentos ni Caja asociados.

Ejecucion local controlada:

- Backup `src/storage/backups/phase2q_20260615_015327_medisoft_hoteles_import.sql`.
- SHA256 `B44E581BDDE09A08C1451E02B4E06B53AB55041303CD705560EF7BA91713D5CE`.
- Producto probado: `Papel Higiénico`.
- Stock antes: `339.00`.
- Stock dentro de transaccion: `340.00`.
- Stock despues de rollback: `339.00`.
- `movimientos_inventario` antes: `85`.
- `movimientos_inventario` dentro de transaccion: `86`.
- `movimientos_inventario` despues de rollback: `85`.
- Resultado: rollback verificado sin persistencia operativa.

## Alcance Fase 2R: UI minima de compras en borrador

Implementado como exposicion interna controlada:

- `CompraController.php` con `indexAction`, `crearAction` y `guardarAction`.
- Rutas minimas: `GET /compras`, `GET /compras/crear` y `POST /compras`.
- Vistas `compras/index.php` y `compras/form.php`.
- Sidebar enlaza Compras bajo el gate visual de `inventario`.
- El acceso directo tambien requiere `require_hotel_module('inventario')`.
- La captura solo llama a `CompraService::crearBorrador()`.

Relacion con inventario:

- No modifica stock al crear borradores.
- No crea `movimientos_inventario`.
- No vincula `compra_detalles.movimiento_inventario_id`.
- No usa `productos` ni `inventario_movimientos`.
- Los productos disponibles se leen desde `inventario_productos` del hotel actual.

Exclusiones:

- Sin recepcion desde UI.
- Sin pagos.
- Sin CxP.
- Sin documentos.
- Sin caja.
- Sin cambios financieros.

Validacion esperada:

- Health checker debe aceptar solo las tres rutas de borrador.
- Health checker debe fallar si aparecen rutas de recepcion, pago, CxP o documentos.
- `/api/sync` debe seguir bloqueado con HTTP 423.

## Cierre Fase 2S: validacion de borrador real

Validacion realizada despues de prueba manual exitosa del usuario.

Alcance:

- Solo consultas de lectura.
- Sin migraciones.
- Sin cambios de DB por parte de la validacion.
- Sin recepcion de compras.
- Sin movimientos de caja.
- Sin pagos, CxP ni documentos.

Resultado observado:

- `compras = 1`.
- `compra_detalles = 3`.
- `compras_no_borrador = 0`.
- `detalles_con_movimiento = 0`.
- `movimientos_recepcion_compra = 0`.
- `logs_auditoria` registra `compras.creada = 1`.

Borrador validado:

- Compra `#2`.
- Hotel `Maximiliano Leon` (`hotel_id = 4`).
- Proveedor `Juan Pedro`.
- Estado `borrador`.
- Total `1900.00`.
- Creado en `2026-06-15 02:11:54` desde sesion de app DB `-06:00`.
- 3 lineas de detalle.
- 0 movimientos de inventario vinculados.

Conclusion:

- La UI minima de Fase 2R queda validada con un borrador real.
- No modifica stock al crear borradores.
- No crea `movimientos_inventario`.
- No toca `movimientos_caja`.
- No crea pagos, CxP ni documentos.

Riesgo consciente para la proxima fase:

- El borrador manual contiene lineas repetidas del mismo producto. Esto no rompe la fase de borrador, pero antes de recepcion debe decidirse si se permite recibir lineas repetidas o si se consolidan por producto.

## Alcance Fase 2T: preflight de recepcion de compras

Implementado solo como verificador tecnico:

- Script `src/tools/saas/preflight_recepcion_compras.php`.
- Objetivo: validar pre-recepcion sin recibir compras.
- Ejecuta lecturas dentro de `START TRANSACTION READ ONLY`.
- No recibe compras.
- No modifica stock.
- No crea movimientos de inventario.
- No agrega rutas.
- No agrega botones.
- No toca Caja, pagos, CxP ni documentos.

Validaciones:

- Estructura minima de `compras` y `compra_detalles`.
- Proveedores activos y scoped por `hotel_id`.
- Productos activos de `inventario_productos` y scoped por `hotel_id`.
- Borradores sin movimientos vinculados.
- Rutas publicas de compras limitadas a borrador.
- Vistas sin acciones de recepcion o pago.
- Health checker actualizado para detectar `productos_repetidos` como advertencia.

Decision pendiente:

- El borrador manual actual tiene productos repetidos.
- La Fase 2T solo lo reporta como advertencia.
- Antes de recibir compras se debe decidir si se consolidan lineas o si se permite un movimiento de inventario por linea.

## Alcance Fase 2U: regla de productos repetidos

Decision:

- Los productos repetidos en una compra se permiten.
- La regla oficial es un movimiento por linea de detalle.
- No se consolidan lineas automaticamente.
- No se modifica el borrador capturado por el usuario.

Relacion con inventario:

- Cada linea recibida debe incrementar `inventario_productos.stock_actual`.
- Cada linea recibida debe crear un registro `movimientos_inventario.tipo_movimiento = 'ENTRADA'`.
- Cada detalle debe quedar vinculado con su propio `movimiento_inventario_id`.
- Si el mismo producto aparece dos veces, se esperan dos movimientos de inventario.

Prueba tecnica:

- `src/tools/saas/probar_compra_service.php --duplicate-line`.
- Requiere backup, `--apply` y `--confirm=ROLLBACK_TEST`.
- Ejecuta la recepcion solo dentro de transaccion.
- Verifica rollback al estado inicial.

Ejecucion local controlada:

- Backup `src/storage/backups/phase2u_20260615_023632_medisoft_hoteles_import.sql`.
- SHA256 `E123A6E8D37A6C6978BCE01FC1EF3F837C06D2F61B319AC32AF1224014E93CE6`.
- Compra temporal `#3`.
- Movimientos esperados: 2.
- Movimientos recibidos: 2.
- Detalles vinculados: 2.
- Stock antes: `339.00`.
- Stock dentro de transaccion: `341.00`.
- Stock despues de rollback: `339.00`.
- Conteos antes: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`.
- Conteos dentro de transaccion: `compras=1`, `compra_detalles=2`, `movimientos_inventario=87`, `logs_auditoria=5`.
- Conteos despues de rollback: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`.

Alcance:

- Sin rutas nuevas.
- Sin botones nuevos.
- Sin recepcion visible.
- Sin Caja, pagos, CxP ni documentos.

## Alcance Fase 2V: recepcion minima de compras

Implementacion:

- Ruta `POST /compras/{id}/recibir`.
- Accion `CompraController::recibirAction()`.
- Boton `Recibir` visible solo en compras `borrador`.
- Formulario con CSRF.
- Delegacion directa a `CompraService::recibirCompra()`.

Contrato de inventario:

- Recibir compra incrementa `inventario_productos.stock_actual`.
- Recibir compra crea `movimientos_inventario.tipo_movimiento = 'ENTRADA'`.
- Cada detalle recibido queda vinculado con `movimiento_inventario_id`.
- Productos repetidos siguen la regla Fase 2U: un movimiento por linea.

Exclusiones:

- Sin pagos.
- Sin cuentas por pagar.
- Sin documentos.
- Sin movimientos de caja.
- Sin cambios en reportes financieros.
- Sin cambios en `/api/sync`.

Prueba local con rollback:

- Backup `src/storage/backups/phase2v_20260615_024412_medisoft_hoteles_import.sql`.
- SHA256 `B9FA594C0C960213D9EA3D105DB46216D7C8C5012E7C97BF479B225B0559EB6D`.
- Compra temporal `#4`.
- Movimientos esperados: 2.
- Movimientos recibidos: 2.
- Detalles vinculados: 2.
- Stock antes: `339.00`.
- Stock dentro de transaccion: `341.00`.
- Stock despues de rollback: `339.00`.
- Conteos antes: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`.
- Conteos dentro de transaccion: `compras=1`, `compra_detalles=2`, `movimientos_inventario=87`, `logs_auditoria=5`.
- Conteos despues de rollback: `compras=0`, `compra_detalles=0`, `movimientos_inventario=85`, `logs_auditoria=3`.

## Fase 2W: recepcion real controlada de compra

Ejecucion autorizada despues de backup:

- Backup previo: `src/storage/backups/phase2w_20260615_024841_before_receive_compra_2_medisoft_hoteles_import.sql`.
- SHA256: `CDEE8C67C1AA6C4CFD316C6A1081C6EDB936D1CB507E637B357910B84EE579E6`.
- Hotel: Maximiliano (`hotel_id = 4`).
- Usuario responsable: `adminmax` (`usuario_id = 24`).
- Compra recibida: compra #2.
- Estado anterior: `borrador`.
- Estado posterior: `recibida`.
- Fecha de recepcion: `2026-06-15 02:49:12`.

Impacto en inventario:

- Detalle #2, producto #39 `H4-JABON`: `movimiento_inventario_id = 993`, stock `50.00 -> 150.00`.
- Detalle #3, producto #38 `H4-AGUA`: `movimiento_inventario_id = 994`, stock `74.00 -> 174.00`.
- Detalle #4, producto #39 `H4-JABON`: `movimiento_inventario_id = 995`, stock `150.00 -> 250.00`.

Resultado:

- La compra #2 quedo recibida.
- Los 3 detalles quedaron vinculados a movimientos de inventario.
- La regla de productos repetidos quedo validada con datos reales: un movimiento por linea.
- Se registro auditoria `compras.recibida`.

Exclusiones confirmadas:

- Sin pagos.
- Sin cuentas por pagar.
- Sin documentos.
- Sin movimientos de caja.
- Sin cambios en reportes financieros.
- Sin cambios en `/api/sync`.

## Fase 2X: detalle read-only de compra recibida

Implementacion:

- Ruta `GET /compras/{id}`.
- Accion `CompraController::verAction()`.
- Vista `app/views/compras/ver.php`.
- Lectura mediante `CompraService::obtenerCompra()`.
- `CompraService::obtenerCompra()` agrega datos de `movimientos_inventario` en los detalles con `LEFT JOIN`.

Contrato de inventario:

- La vista no crea ni edita movimientos.
- La vista no modifica `inventario_productos.stock_actual`.
- La vista solo muestra `movimiento_inventario_id`, tipo, fecha, motivo y stock anterior/posterior cuando existe.
- La compra #2 debe mostrar tres movimientos vinculados: #993, #994 y #995.

Validacion manual:

- Usuario confirmo que la compra aparece como `recibida` en la pantalla.
- Resultado manual reportado: correcto.

Exclusiones:

- Sin pagos.
- Sin cuentas por pagar.
- Sin documentos.
- Sin movimientos de caja.
- Sin cambios en reportes financieros.
- Sin cambios en `/api/sync`.

## Fase 2Y: reportes read-only de compras recibidas

Implementacion:

- Ruta `GET /compras/reportes/recibidas`.
- Accion `CompraController::reporteRecibidasAction()`.
- Vista `app/views/compras/reporte_recibidas.php`.
- Lectura mediante `CompraService::reporteRecibidas()`.
- Filtros por proveedor, producto, fecha inicial, fecha final y estado.
- Totales por proveedor y producto derivados de `compra_detalles`.
- Enlaces de regreso al detalle `GET /compras/{id}`.

Contrato de inventario:

- El reporte usa `compras`, `compra_detalles`, `proveedores`, `inventario_productos` y `movimientos_inventario`.
- No modifica `inventario_productos.stock_actual`.
- No crea movimientos.
- No escribe en tablas legacy `productos` ni `inventario_movimientos`.
- Los movimientos vinculados se muestran solo si existe `movimiento_inventario_id`.

Exclusiones:

- Sin pagos.
- Sin cuentas por pagar.
- Sin documentos.
- Sin movimientos de caja.
- Sin cambios en reportes financieros.
- Sin cambios en `/api/sync`.

## Fase 2Z: endurecimiento de compras minimas

Implementacion:

- `CompraService::recibirCompra()` separa precondiciones en guardas internas.
- `assertCompraPuedeRecibirse()` bloquea recepcion doble, compras canceladas, estados no borrador y `fecha_recepcion` previa.
- `assertDetallesPuedenRecibirse()` bloquea detalles ya vinculados a movimiento, cantidades no positivas e importes negativos.
- El mensaje de error al marcar una compra como recibida indica posible procesamiento por otra sesion.
- `compras/ver.php` agrega navegacion read-only al reporte de compras recibidas.

Contrato de inventario:

- No cambia la creacion de movimientos: sigue siendo un `movimientos_inventario` tipo `ENTRADA` por linea.
- No cambia el calculo de stock: `stock_posterior = stock_anterior + cantidad`.
- No cambia la regla de productos repetidos: un movimiento por linea.
- No escribe en tablas legacy `productos` ni `inventario_movimientos`.

Exclusiones:

- Sin pagos.
- Sin cuentas por pagar.
- Sin documentos.
- Sin movimientos de caja.
- Sin cambios en reportes financieros.
- Sin cambios en `/api/sync`.

## Fase 3A: proveedores v2 read-only

Implementacion:

- Ruta `GET /proveedores/{id}`.
- Accion `ProveedorController::verAction()`.
- Vista `app/views/proveedores/ver.php`.
- Lectura mediante `Proveedor::resumenComprasPorProveedor()`.
- Lectura mediante `Proveedor::comprasRecientesPorProveedor()`.
- Enlace desde `app/views/proveedores/index.php` hacia la ficha de proveedor.
- Enlace desde la ficha hacia `GET /compras/{id}` y `GET /compras/reportes/recibidas?proveedor_id={id}`.

Contrato de inventario:

- La ficha no crea ni edita compras.
- La ficha no modifica `inventario_productos.stock_actual`.
- La ficha no crea movimientos en `movimientos_inventario`.
- La ficha solo resume datos ya existentes en `compras` y `compra_detalles`.
- Las lecturas quedan filtradas por `hotel_id` y `proveedor_id`.

Exclusiones:

- Sin pagos.
- Sin cuentas por pagar.
- Sin documentos.
- Sin movimientos de caja.
- Sin cambios en reportes financieros.
- Sin cambios en `/api/sync`.

## Fase 3B draft: cuentas por pagar base

Implementacion:

- Borrador SQL no ejecutado: `docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql`.
- Tablas propuestas: `cuentas_por_pagar` y `cuentas_por_pagar_movimientos`.
- No se agrega archivo oficial a `migrations/` en esta subfase.
- No se ejecuta SQL contra la base.
- No se registra migracion.

Contrato de inventario:

- CxP no modifica `inventario_productos`.
- CxP no crea movimientos en `movimientos_inventario`.
- CxP solo referencia compras ya existentes cuando se promueva la fase.
- La recepcion de compras no se altera en esta subfase.

Exclusiones:

- Sin pagos.
- Sin movimientos de caja.
- Sin documentos.
- Sin rutas activas de CxP.
- Sin vistas activas de CxP.
- Sin cambios en reportes financieros.
- Sin cambios en `/api/sync`.

Promocion futura:

- Requiere backup completo y SHA256 antes de ejecutar cualquier SQL.
- Requiere promover el draft a `migrations/`.
- Requiere validar estructura vacia antes de construir vistas read-only.

## Fase 3B aplicada: CxP read-only

Aplicacion local controlada:

- Backup previo: `src/storage/backups/phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql`.
- SHA256: `24663D206AE15B86B001708D8BC2665541548443A0EA363548CAFC3FDF3A4D2C`.
- Migracion: `migrations/20260615_003_fase_3b_cxp_base.sql`.
- Tablas creadas: `cuentas_por_pagar` y `cuentas_por_pagar_movimientos`.
- Ambas tablas quedaron vacias despues de aplicar la migracion.

Implementacion read-only:

- Rutas:
  - `GET /cuentas-por-pagar`.
  - `GET /cuentas-por-pagar/{id}`.
- Controlador: `CuentaPorPagarController`.
- Modelo: `CuentaPorPagar`.
- Vistas: `cuentas_por_pagar/index.php` y `cuentas_por_pagar/ver.php`.
- Sidebar bajo gate visual de `inventario`.

Contrato de inventario:

- No modifica `inventario_productos`.
- No crea movimientos en `movimientos_inventario`.
- No modifica la recepcion de compras.
- No genera CxP automaticamente desde compras en esta fase.

Exclusiones:

- Sin pagos.
- Sin movimientos de caja.
- Sin documentos.
- Sin formularios POST.
- Sin cambios en reportes financieros.
- Sin cambios en `/api/sync`.
