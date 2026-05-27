# Reservaciones 1-F-A - Auditoria de pagos, caja y cortes

## 1. Objetivo

Auditar pagos, caja y cortes vinculados a Reservaciones antes de preparar una migracion tenant-aware.

Esta fase fue solo de lectura:

- No se modificaron archivos funcionales.
- No se ejecuto SQL de escritura.
- No se hicieron migraciones.
- No se hizo `ALTER TABLE`.
- No se implementaron cambios.

## 2. Hallazgos clave

- `reservacion_pagos` ya tiene `hotel_id`.
- `movimientos_caja`, `cajas` y `cortes_caja` todavia no tienen `hotel_id`.
- `movimientos_caja` tiene 1381 registros.
- `cortes_caja` tiene 214 cortes historicos.
- `cajas` tiene 1 registro global.
- `movimientos_caja` tiene 158 movimientos sin `reservacion_id`.
- Hay 1 movimiento de caja huerfano con `reservacion_id = 53`, monto 1300, corte cerrado 15.
- `reservacion_pagos` tiene 1 pago huerfano con `reservacion_id = 53`.
- `reservacion_pagos` y `reservacion_habitaciones` son consistentes contra `reservaciones.hotel_id`.

## 3. Conteos

| Tabla | Total | hotel_id |
| --- | ---: | --- |
| `reservacion_pagos` | 1202 | si |
| `movimientos_caja` | 1381 | no |
| `cajas` | 1 | no |
| `cortes_caja` | 214 | no |
| `reservaciones` | 1232 | si |
| `reservacion_habitaciones` | 2389 | si |
| `habitaciones` | 49 | si |
| `usuarios` | 8 | no |

## 4. Schema, indices y foreign keys

### reservacion_pagos

Columnas principales:

- `id`
- `hotel_id`
- `reservacion_id`
- `metodo_pago`
- `monto`
- `referencia`
- `created_at`

Indices detectados:

- `PRIMARY(id)`
- `idx_fecha(created_at)`
- `idx_metodo_pago(metodo_pago)`
- `idx_reservacion(reservacion_id)`
- `idx_reservacion_pagos_hotel_id(hotel_id)`

No se detectaron foreign keys estrictas.

### movimientos_caja

Columnas principales:

- `id`
- `tipo`
- `categoria`
- `categoria_id`
- `descripcion`
- `monto`
- `metodo_pago`
- `referencia`
- `comprobante`
- `proveedor`
- `reservacion_id`
- `usuario_id`
- `corte_id`
- `created_at`
- campos de edicion/auditoria

Indices detectados:

- `PRIMARY(id)`
- indices por `corte_id`
- indices por `reservacion_id`
- indices por `tipo`
- indices por `created_at`
- indices por `usuario_id`
- indices por `categoria_id`

No se detectaron foreign keys estrictas.

### cajas

Columnas principales:

- `id`
- `nombre`
- `ubicacion`
- `monto_inicial`
- `activa`
- `created_at`
- `updated_at`

Indices detectados:

- `PRIMARY(id)`

No se detectaron foreign keys estrictas.

### cortes_caja

Columnas principales:

- `id`
- `caja_id`
- `fecha_apertura`
- `fecha_cierre`
- `monto_inicial`
- totales por metodo de pago
- totales de gastos
- `efectivo_esperado`
- `efectivo_contado`
- `diferencia`
- `estado`
- `observaciones`
- `usuario_apertura_id`
- `usuario_cierre_id`
- `created_at`
- `updated_at`

Indices detectados:

- `PRIMARY(id)`
- indices por `caja_id`
- indices por `estado`
- indices por fechas
- indices por usuarios de apertura/cierre

No se detectaron foreign keys estrictas.

## 5. Donde se crean datos

- `Reservacion::registrarPagosMixtos()` crea pagos en `reservacion_pagos`.
- `Reservacion::intentarRegistrarPagosMixtos()` queda documentado como deuda/riesgo porque tambien maneja pagos.
- `Reservacion::checkInConPagosMixtos()` crea ingresos de caja por hospedaje en `movimientos_caja`.
- `Reservacion::checkInCheckOutExpress()` crea movimientos de caja dentro del flujo express.
- `Reservacion::cancelar()` consulta ingresos y puede crear devoluciones en `movimientos_caja`.
- `ReservacionController::modificarDiasAction()` puede crear ajustes en `movimientos_caja`.
- `ReservacionController::cambiarMetodoPagoAction()` modifica pagos y movimientos de caja asociados.
- `Caja::abrirCaja()` crea cortes abiertos en `cortes_caja`.
- `Caja::cerrarCaja()` cierra cortes y actualiza totales en `cortes_caja`.

## 6. Que puede scopearse ya

- `reservacion_pagos` puede scopearse por `reservaciones.hotel_id`.
- `movimientos_caja` ligados a `reservacion_id` pueden inferir hotel mediante join a `reservaciones.hotel_id`.
- Ingresos por habitacion pueden scopearse mediante `reservacion_habitaciones.hotel_id` y `habitaciones.hotel_id`.

## 7. Que necesita schema primero

- `movimientos_caja.hotel_id`.
- `cajas.hotel_id`.
- `cortes_caja.hotel_id`.

Sin esas columnas, el codigo funcional de caja/cortes seguiria dependiendo de inferencias indirectas por reservacion, y no cubriria movimientos sin `reservacion_id`.

## 8. Riesgos

| Riesgo | Nivel | Motivo |
| --- | --- | --- |
| Agregar `hotel_id` a `movimientos_caja` | Alto | Tiene datos financieros historicos, movimientos sin reservacion y un movimiento huerfano. |
| Agregar `hotel_id` a `cortes_caja` | Alto | Existen 214 cortes historicos cerrados. |
| Agregar `hotel_id` a `cajas` | Medio/alto | Actualmente existe una caja global; falta decidir caja por hotel. |
| Tocar codigo de Caja antes del schema | Alto | Puede generar ingresos o cortes inconsistentes. |
| Reportes financieros globales | Medio/alto | Pueden mezclar hoteles cuando existan mas tenants. |
| `ReservacionController::cambiarMetodoPagoAction()` | Alto | Modifica pagos y movimientos de caja. |
| PWA/Sync/API | Alto | Siguen fuera de scope y pueden tener caminos paralelos. |

## 9. Tablas candidatas a hotel_id

Primera ola:

- `movimientos_caja`
- `cajas`
- `cortes_caja`

Ya migrada pero con codigo funcional pendiente:

- `reservacion_pagos`

Dejar fuera por ahora:

- `usuarios`
- `huespedes`
- PWA/Sync/API
- reportes/dashboard hasta tener caja/cortes tenant-aware

## 10. Orden recomendado

1. Reservaciones 1-F-B-PREP: preparar migracion `hotel_id` para `movimientos_caja`, `cajas` y `cortes_caja`, sin ejecutar.
2. Reservaciones 1-F-C: actualizar herramientas SaaS.
3. Reservaciones 1-F-D: codigo funcional de pagos/caja.
4. Reservaciones 1-F-E: reportes/cortes/dashboard financiero.
5. PWA/Sync/API en fase separada.

## 11. Que NO se debe tocar todavia

- Caja funcional.
- `movimientos_caja`.
- `cortes_caja`.
- Reportes financieros.
- Dashboard.
- PWA/offline.
- Sync.
- APIs globales.
- Huespedes como tenant.
- Migraciones ejecutables sin fase PREP.

## 12. Decision de cierre de auditoria

La fase Reservaciones 1-F-A confirma que pagos/caja/cortes deben tratarse como bloque financiero de alto riesgo.

La recomendacion es preparar una migracion versionada en fase separada antes de tocar codigo funcional de Caja, pagos, cortes, reportes o dashboard.
