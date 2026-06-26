# Tablas duplicadas o solapadas

Documento tecnico iniciado en Fase 1A y actualizado en Fase 2A. No autoriza borrar, renombrar ni fusionar tablas.

## Criterio general

Las tablas marcadas como "moderna probable" son las que usa el flujo operativo actual o las que ya tienen `hotel_id`.
Las tablas marcadas como "legacy probable" todavia pueden estar referenciadas por codigo antiguo, helpers o vistas no conectadas.

Antes de limpiar cualquier tabla se debe hacer una fase especifica con:

- backup completo;
- conteos por tabla;
- busqueda de referencias en codigo;
- validacion de foreign keys;
- prueba funcional del modulo afectado;
- plan de rollback.

## productos vs inventario_productos

Tabla moderna probable: `inventario_productos`.

Tabla legacy probable: `productos`.

Referencias relevantes:

- `src/app/models/Inventario.php` usa `inventario_productos`.
- `src/app/models/MovimientoInventario.php` usa `inventario_productos`.
- `src/app/services/InventarioService.php` usa `inventario_productos`.
- `src/app/controllers/InventarioController.php` usa `inventario_productos`.
- `src/app/controllers/ApiController.php` consulta `inventario_productos`.
- `src/app/models/Reservacion.php` usa `inventario_productos` para devoluciones/descuentos.
- `src/app/models/Producto.php` usa `productos`.
- `src/app/controllers/ProductoController.php` usa `productos`.
- `src/app/helpers/integracion_inventario.php` contiene referencias legacy a `productos`.
- Algunos servicios/vistas antiguos de configuracion de inventario referencian `productos`.

Riesgo de borrar:

Alto. Aunque el flujo moderno usa `inventario_productos`, todavia existen referencias legacy. Borrar `productos` puede romper pantallas antiguas, helpers o procesos no enlazados en rutas actuales.

Recomendacion temporal:

Mantener ambas. Documentar `inventario_productos` como tabla operativa principal. No crear codigo nuevo contra `productos`.

Actualizacion Fase 2A:

`productos` queda congelada como legacy sin rutas activas conocidas hacia `ProductoController`. El contrato moderno de inventario se documenta en `docs/technical/inventory_reconciliation.md`.

Actualizacion Fase 2B:

El ajuste manual `GET/POST /inventario/ajuste/{id}` usa `inventario_productos` mediante `Inventario` y no introduce nuevas referencias a `productos`.

Actualizacion Fase 2C:

Se marcaron como legacy congelado los archivos que todavia mezclan `productos` con configuracion o reportes de inventario:

- `src/app/models/ConfiguracionInventario.php`.
- `src/app/services/ConfiguracionInventarioService.php`.
- `src/app/models/InventarioReportes.php`.
- `src/app/helpers/integracion_inventario.php`.
- `src/app/views/inventario/ConfiguracionHabitacionService.php`.

Tambien queda identificado `src/app/controllers/ProductoController.php` como controlador legacy sin rutas activas conocidas. No borrar `productos` todavia: primero hay que retirar o migrar esos consumidores historicos.

Actualizacion Fase 2D:

`Inventario::actualizarConfiguracion()` valida que el producto pertenezca a `inventario_productos` del hotel actual, este activo y tenga `descuento_automatico = 1`; no usa `productos`.

`ApiController::getProductosDescuentoReservacion()` queda congelado como metodo legacy no ruteado. No debe exponerse como API nueva porque conserva dependencia historica; el preview seguro debe salir por `InventarioService::verificarDisponibilidad()`.

`src/app/helpers/integracion_inventario.php` queda neutralizado para escrituras legacy mediante `MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES`. No activar esta constante en operacion normal.

Actualizacion Fase 2E:

El contrato futuro de compras queda documentado en `docs/technical/purchasing_inventory_contract.md`. Compras/Proveedores no se implementan todavia. Cuando se reciban compras, los detalles deben referenciar `inventario_productos` con `hotel_id`; no se debe reusar `productos` para proveedores, compras ni recepciones.

Actualizacion Fase 2F:

Se habilita solo el catalogo moderno `proveedores` por `hotel_id`. No se migra ni reconcilia el texto libre `movimientos_caja.proveedor`, porque eso tocaria Caja y calculos financieros. Las compras futuras deben referenciar `proveedores.id`; mientras tanto, cualquier dato historico en `movimientos_caja.proveedor` queda solo como evidencia operativa.

## inventario_movimientos vs movimientos_inventario

Tabla moderna probable: `movimientos_inventario`.

Tabla legacy probable: `inventario_movimientos`.

Referencias relevantes:

- `src/app/models/MovimientoInventario.php` usa `movimientos_inventario`.
- `src/app/models/Reservacion.php` usa `movimientos_inventario`.
- `src/app/services/InventarioService.php` usa `movimientos_inventario`.
- `src/app/controllers/InventarioController.php` usa `movimientos_inventario`.
- `src/app/helpers/integracion_inventario.php` usa `movimientos_inventario`.
- `inventario_movimientos` aparece como tabla historica/paralela y puede existir vacia.

Riesgo de borrar:

Medio. Si `inventario_movimientos` esta vacia, el riesgo de datos puede ser bajo, pero el riesgo tecnico sigue existiendo hasta confirmar cero consumidores reales.

Recomendacion temporal:

Mantener ambas. Usar `movimientos_inventario` para codigo nuevo. Marcar `inventario_movimientos` como candidata a limpieza futura solo despues de auditoria de referencias y respaldo.

Actualizacion Fase 2A:

`inventario_movimientos` tiene 0 registros en la base activa y queda congelada. `movimientos_inventario` es la fuente oficial de trazabilidad, con `hotel_id` obligatorio.

Actualizacion Fase 2B:

El ajuste manual registra `tipo_movimiento = AJUSTE` en `movimientos_inventario` mediante `MovimientoInventario::crearMovimientoManual()`. `inventario_movimientos` sigue congelada.

Actualizacion Fase 2C:

`src/app/helpers/integracion_inventario.php` contiene ejemplos/mantenimiento historico sobre `movimientos_inventario`, incluyendo operaciones de mantenimiento que no deben ejecutarse en esta fase. El flujo oficial de movimientos sigue siendo `MovimientoInventario`, `InventarioService`, `Reservacion` e `InventarioController`.

Actualizacion Fase 2D:

La funcion legacy `mantenimiento_inventario()` queda bloqueada por defecto. Aunque el archivo conserva `DELETE FROM movimientos_inventario` como codigo historico, no se ejecuta salvo que una fase futura defina explicitamente `MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES === true`.

Actualizacion Fase 2E:

La recepcion futura de compras debe registrarse en `movimientos_inventario` como `tipo_movimiento = ENTRADA`, con motivo identificable de compra. `inventario_movimientos` no debe usarse para compras nuevas ni como tabla de recepcion.

Actualizacion Fase 2F:

El catalogo `proveedores` no crea movimientos de inventario. Ninguna alta, edicion, desactivacion o reactivacion de proveedor debe escribir en `movimientos_inventario` ni en `inventario_movimientos`.

## push_subscriptions vs pwa_push_subscriptions

Tabla moderna probable: `pwa_push_subscriptions`.

Tabla legacy probable: `push_subscriptions`.

Referencias relevantes:

- `src/app/models/PwaPushSubscription.php` usa `pwa_push_subscriptions`.
- `src/app/controllers/PwaPushController.php` trabaja con el modelo moderno.
- `src/app/helpers/pwa.php` contiene referencias a `push_subscriptions`.

Riesgo de borrar:

Alto en esta fase, porque PWA/offline/sync son areas protegidas y no deben tocarse sin autorizacion explicita.

Recomendacion temporal:

Mantener ambas. No tocar service worker, PWA ni sync. Para codigo nuevo de push, usar `pwa_push_subscriptions`.

## inventario_habitacion_config vs inventario_config_habitacion

Tabla moderna probable: `inventario_config_habitacion`.

Tabla legacy probable: `inventario_habitacion_config`.

Referencias relevantes:

- `src/app/models/Inventario.php` usa `inventario_config_habitacion`.
- `src/app/services/InventarioService.php` usa `inventario_config_habitacion`.
- `src/app/controllers/ReservacionController.php` usa `inventario_config_habitacion`.
- `src/app/models/ConfiguracionInventario.php` usa `inventario_config_habitacion`.
- `src/app/services/ConfiguracionInventarioService.php` usa `inventario_config_habitacion`.
- `inventario_habitacion_config` parece una variante por habitacion y puede existir vacia.

Riesgo de borrar:

Medio. Puede estar vacia, pero el nombre sugiere otra granularidad funcional. Eliminarla sin decision de dominio puede cerrar una ruta futura o romper codigo legacy.

Recomendacion temporal:

Mantener ambas. Usar `inventario_config_habitacion` como tabla activa para configuracion por tipo de habitacion.

Actualizacion Fase 2A:

`inventario_habitacion_config` tiene 0 registros en la base activa y queda congelada. `inventario_config_habitacion` es la fuente oficial para consumo por tipo de habitacion.

Actualizacion Fase 2C:

La tabla moderna `inventario_config_habitacion` se mantiene como oficial, pero solo cuando se une contra `inventario_productos` por `producto_id` y `hotel_id`. Quedan congelados los consumidores legacy que la unen contra `productos` o no filtran por hotel:

- `ConfiguracionInventario`.
- `ConfiguracionInventarioService`.
- `InventarioReportes`.
- `ConfiguracionHabitacionService`.

La vista `inventario/configuracion-habitacion.php` queda desconectada; la pantalla oficial es `inventario/configuracion`.

Actualizacion Fase 2D:

El guardado oficial de configuracion se probo con transaccion y rollback. Se bloquearon producto inexistente, producto de otro hotel, cantidad negativa, cantidad no numerica, tipo inexistente y tipo de otro hotel. No quedaron cambios persistentes.

## huespedes_vehiculos vs huesped_vehiculos

Tabla moderna probable: `huesped_vehiculos`.

Tabla legacy probable: `huespedes_vehiculos`.

Referencias relevantes:

- `src/app/models/HuespedVehiculo.php` usa `huesped_vehiculos`.
- `src/app/models/Huesped.php` usa `huesped_vehiculos`.
- `src/app/controllers/ReservacionController.php` usa `huesped_vehiculos`.
- `src/app/views/dashboard/index.php` consulta `huesped_vehiculos`.
- `huespedes_vehiculos` parece una variante antigua y puede existir vacia.

Riesgo de borrar:

Medio. Si esta vacia, el riesgo de datos es bajo, pero debe confirmarse que no existan referencias dinamicas ni scripts externos.

Recomendacion temporal:

Mantener ambas. Usar `huesped_vehiculos` para codigo nuevo y reportes.

## Actualizacion 2026-06-24 - Auditoria de exposicion legacy

Validacion local de conteos:

- `huespedes_vehiculos`: 0 registros.
- `inventario_habitacion_config`: 0 registros.
- `inventario_movimientos`: 0 registros.
- `productos`: 5 registros semilla legacy.
- `inventario_productos`: 35 registros.
- `inventario_config_habitacion`: 58 registros.
- `movimientos_inventario`: 193 registros.

Exposicion de rutas:

- El router actual solo despacha rutas registradas en `src/config/routes.php`.
- No hay rutas registradas para `/inventario/productos`, `/inventario/productos/crear`, `/inventario/configuracion-habitacion` ni `/productos`.
- `ProductoController` queda sin ruta activa y conserva contrato antiguo no compatible con el dispatcher moderno (`index`, `crear`, etc. sin sufijo `Action`).
- `src/app/views/inventario/dashboard.php` tenia un enlace residual a `inventario/configuracion-habitacion`; se corrigio para apuntar al flujo oficial `inventario/configuracion`.

Decision:

- No borrar tablas legacy en esta fase.
- Mantener `productos` como semilla historica congelada.
- Mantener vacias `huespedes_vehiculos`, `inventario_habitacion_config` e `inventario_movimientos` hasta una fase de retiro con backup.
- No crear rutas nuevas hacia `ProductoController`, vistas legacy ni servicios congelados.
- Todo codigo nuevo de inventario debe seguir usando `inventario_productos`, `inventario_config_habitacion` y `movimientos_inventario` con `hotel_id`.

## Recomendacion Fase 1A

No ejecutar limpieza de tablas en esta fase.

La prioridad actual es mantener el contrato moderno documentado y evitar nuevos enlaces al flujo legacy. La consolidacion de tablas debe ser una fase posterior con pruebas funcionales por modulo.
