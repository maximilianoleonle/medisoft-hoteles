# Bitacora de testeo funcional guiado - 2026-06-28

Rama objetivo: `feature/saas-multihotel`
Plan base: `docs/plan_testeo_funcional_guiado_20260628.md`
Modo de ejecucion: 2 personas juntas en una PC, guiado desde Codex.

## Leyenda

- PASS: resultado esperado confirmado.
- FAIL: comportamiento incorrecto confirmado.
- BLOCKED: no se pudo ejecutar por dato/acceso/estado faltante.
- NA: no aplica en este entorno.

## Ronda 0 - Preflight tecnico

| Caso | Resultado | Evidencia | Notas |
|---|---|---|---|
| R0-01 Docker activo | PASS | app, db y phpMyAdmin arriba | App `http://localhost:8080`, DB healthy, phpMyAdmin `http://localhost:8081`. |
| R0-02 Health check base | PASS | `OK: 413`, `WARNING: 0`, `ERROR: 0` | Resultado `PASS_WITH_WARNINGS_ALLOWED`. |
| R0-03 Preflights funcionales principales | PASS | Operacion, tablero, arqueo, compras, CxC, CxP, personal, nomina, tareas y conciliacion ejecutados | Warnings historicos en tablero/arqueo/CxC, sin errores bloqueantes. |
| R0-04 Preflight hotel_id | NA | `OK: 144`, `WARNING: 17`, `ERROR: 2`, `FAIL` | Herramienta conocida como no apta para semaforo unico por contrato/allowlist pendiente; no bloquea QA manual. |

## Ronda 1 - Acceso y contexto

| Caso | Hotel | Usuario/Rol | Resultado esperado | Resultado real | Estado | Notas/Bug |
|---|---|---|---|---|---|---|
| AUTH-01 Login hotelero general | Los Cedros | `admin` / gerente | Dashboard carga, branding hotelero, sin identidad SaaS | Dashboard y datos cargan, pero UI muestra `Medisoft Hoteles` como hotel activo/titulo en vez de `Los Cedros` | FAIL | BUG-001 |
| AUTH-02 Logout | Los Cedros | `admin` / gerente | Redireccion a login correcto, rutas internas protegidas | Sesion cerrada, pero redirige a `/h/maximiliano/login` tras operar con datos de Los Cedros | FAIL | BUG-002 |
| AUTH-01R Revalidar login general despues del fix | Los Cedros | `admin` / gerente | Con cookie previa `maximiliano`, login general debe activar el hotel real del usuario | `POST /login/authenticate` redirige a `/dashboard`; dashboard queda `Dashboard - Los Cedros`, `MEDISOFT_CONTEXT hotel_id=1` y branding offline `Los Cedros` | PASS | BUG-001 FIX VALIDADO |
| AUTH-02R Revalidar logout despues del fix | Los Cedros | `admin` / gerente | Con sesion Los Cedros y cookie previa `maximiliano`, logout debe volver al login de Los Cedros | `POST /logout` responde `303 Location: http://localhost:8080/h/los-cedros/login`; respuesta no contiene `maximiliano` | PASS | BUG-002 FIX VALIDADO |
| AUTH-03 Login scoped | Los Cedros | `admin` / gerente | `/h/los-cedros/login` entra con contexto correcto | Dashboard carga con `Los Cedros`, 49 habitaciones, menu operativo y logout con CSRF | PASS |  |
| AUTH-04 Multi-hotel scoped | Los Cedros/Demo/Maximiliano | `adminmax` / administrador | No arrastra datos ni branding entre hoteles | Login scoped valido en los 3 hoteles; dashboard, habitaciones y caja conservan `MEDISOFT_CONTEXT` correcto; logout vuelve al login scoped correcto aunque hubiera cookie previa de otro hotel | PASS |  |
| AUTH-05 Ruta interna sin sesion | N/A | Sin sesion | Redireccion a login, sin contenido operativo visible | `GET /habitaciones` sin cookie responde `303` a `/login` | PASS |  |
| AUTH-06 Login scoped sin pertenencia | Maximiliano / QA temporal | `admin`, `adminmax` | Usuario no vinculado al hotel no debe iniciar sesion | `admin` contra `/h/maximiliano/login` y `adminmax` contra `/h/hotel-qa-temporal-20260531174223/login` responden `303` al login scoped y no quedan autenticados | PASS |  |

## Ronda 2 - Panel SaaS

| Caso | Hotel | Usuario/Rol | Resultado esperado | Resultado real | Estado | Notas/Bug |
|---|---|---|---|---|---|---|
| SAAS-01 Listado de hoteles | SaaS | `admin` / owner SaaS | Identidad Medisoft, listado de hoteles y acciones principales | Carga `Panel Medisoft`, 4 hoteles registrados/activos, links Ver/Editar | PASS |  |
| SAAS-02 Detalle Los Cedros | Los Cedros | `admin` / owner SaaS | Resumen, plan, modulos, usuarios y branding con formularios separados + CSRF | Carga detalle, 14/18 modulos, 9 admins, 6 formularios POST con CSRF | PASS | No se guardaron cambios. |
| SAAS-08 Smoke login scoped por hotel | Los Cedros/Demo/Maximiliano | Sin sesion | Cada login scoped muestra branding/nombre de su hotel | `/h/los-cedros/login`, `/h/hotel-demo-saas/login` y `/h/maximiliano/login` muestran titulos correctos | PASS | No se inicio sesion en otros hoteles. |

## Ronda 3 - Operacion hotelera base

| Caso | Hotel | Usuario/Rol | Resultado esperado | Resultado real | Estado | Notas/Bug |
|---|---|---|---|---|---|---|
| DASH-01 Dashboard | Los Cedros | `admin` / gerente | Totales de ocupacion, caja, llegadas/salidas y enlaces consistentes | Carga `Dashboard - Los Cedros`, 0/49 ocupacion, caja abierta, enlaces a habitaciones/caja/reservaciones | PASS |  |
| HAB-01 Habitaciones listado/filtros | Los Cedros | `admin` / gerente | Listado carga, filtros funcionan, no hay 500 | Carga 49 habitaciones; filtro `estado=mantenimiento` muestra habitacion en mantenimiento | PASS | GET filtro sin CSRF correcto; POSTs visibles con CSRF. |
| HUE-01 Huespedes listado/busqueda | Los Cedros | `admin` / gerente | Listado y busqueda cargan con datos del hotel | Carga 1,015 huespedes; busqueda `Maximiliano` muestra 1 resultado visible | PASS |  |
| HUE-02 Crear huesped QA | Los Cedros | `admin` / gerente | Huesped creado, detalle accesible y datos visibles | Creado `QA-FUNC-20260628-JUNTOS Huesped Principal`, ID `1065` | PASS | Vehiculo `QA-6828` visible. |
| RES-01 Reservaciones listado/filtros | Los Cedros | `admin` / gerente | Listado y filtros principales cargan | Lista por fecha carga; calendario muestra 49 habitaciones y 4 reservas | PASS |  |
| RES-02 Crear reservacion QA | Los Cedros | `admin` / gerente | Reservacion creada y redireccion a detalle/listado sin error | DB creo reservacion ID `39`, pero UI termino en `404` en `/reservaciones/guardar` tras confirmar | FAIL | BUG-003 |
| RES-03 Revalidar crear reservacion despues del fix | Los Cedros | `admin` / gerente | Reservacion creada y navegacion estable al detalle, sin 404 en `/reservaciones/guardar` | Reservacion `51` creada por AJAX; UI quedo en `/reservaciones/ver/51` tras esperar 10s; logs: `POST /reservaciones/guardar` 200 JSON y `GET /reservaciones/ver/51` 200, sin `GET /reservaciones/guardar` | PASS | BUG-003 FIX VALIDADO |
| RES-06 Detalle de reservacion | Los Cedros | `admin` / gerente | Ficha muestra huesped, habitacion, saldo, acciones y formularios con CSRF | `/reservaciones/ver/39` carga con huesped QA, AMBAR, saldo `$600.00`, acciones y formularios con CSRF | PASS |  |
| RES-DET-LOG-01 Detalle de reservacion sin warnings PHP | Los Cedros | `admin` / gerente | Abrir detalle no debe generar warnings PHP | Al abrir `/reservaciones/ver/51`, logs muestran `Undefined variable $remotos_info` y `Undefined variable $noches` en `reservaciones/ver.php`; UI carga sin 404 | FAIL | BUG-008 |
| RES-DET-LOG-02 Revalidar detalle sin warnings despues del fix | Los Cedros | `admin` / gerente | Abrir detalle no debe generar warnings PHP | `/reservaciones/ver/51` carga estable; body sin warnings; logs nuevos no repiten `$remotos_info` ni `$noches` | PASS | BUG-008 FIX VALIDADO |
| RES-13 Check-in normal | Los Cedros | `admin` / gerente | Reservacion pasa a check-in, habitacion ocupada, pago/caja coherentes | Reservacion `39` paso a `checked_in`, AMBAR a `ocupada`, saldo `$0.00` | PASS | Pago efectivo `$600`, `reservacion_pagos #34`, movimiento caja `#1527`, corte `#229`. |
| RES-15 Check-out normal | Los Cedros | `admin` / gerente | Reservacion completada, habitacion pasa al estado esperado, caja se conserva | Reservacion `39` paso a `checked_out`; AMBAR paso a `limpieza`; pago/caja sin duplicados | PASS | Requiere doble confirmacion en modal, funciono correctamente. |
| RES-MDD-01 Modificar dias: extender post-check-in pagado | Los Cedros | `admin` / gerente | Si se agrega una noche a una reservacion checked-in ya pagada, el saldo, pagos, caja y UI deben quedar conciliados | Reservacion `40` pasa de `$600` a `$1,200`; Caja registra ingreso adicional `$600`, pero `reservacion_pagos` y `monto_recibido` siguen en `$600`, UI muestra saldo `$600` | FAIL | BUG-004 |
| RES-MDD-02 Modificar dias: reducir post-check-in pagado | Los Cedros | `admin` / gerente | Si se reduce una noche ya pagada, devolucion, total, pagos y subtotales deben quedar conciliados | Reservacion `41` pasa de `$1,200` a `$600`; Caja registra devolucion `$600`, pero `reservacion_pagos`, `monto_recibido` y subtotal de habitacion siguen en `$1,200` | FAIL | BUG-005 |
| RES-MDD-03 Modificar dias antes de check-in sin pago | Los Cedros | `admin` / gerente | Al extender/reducir una reservacion confirmada sin pagos, total, saldo y subtotal por habitacion deben quedar conciliados, sin movimientos de caja | Reservaciones `42` y `43` actualizan `precio_total` y no crean caja/pagos, pero `reservacion_habitaciones.precio` queda con el subtotal anterior | FAIL | BUG-006 |
| RES-MDD-04 Modificar dias antes de check-in con anticipo parcial | Los Cedros | `admin` / gerente | Al extender con anticipo parcial, saldo debe recalcularse, no debe duplicarse caja y subtotal por habitacion debe actualizarse | Reservacion `44` mantiene caja/abono correcto `$300` y saldo `$900`, pero `reservacion_habitaciones.precio` queda en `$600` aunque total es `$1,200` | FAIL | BUG-006 |
| RES-CHKOUT-01 Check-out con saldo visible | Los Cedros | `admin` / gerente | Si la ficha muestra saldo pendiente, check-out debe bloquearse o advertir/cobrar antes de confirmar | En reservacion `40`, con saldo visible `$600`, el modal permite seleccionar habitacion y habilita `Confirmar Check-out` sin advertencia de saldo | FAIL | BUG-007 |
| RES-CHKOUT-02 Revalidar check-out con saldo despues del fix | Los Cedros | `admin` / gerente | Con saldo pendiente, no debe aparecer accion de check-out y backend debe rechazar el cierre; sin saldo, la accion debe seguir disponible | Reservacion `40`: saldo `$600.00`, no hay botones visibles de check-out y `Reservacion::checkOut(40)` devuelve `false` sin cambiar estado. Reservacion `47`: saldo `$0.00`, muestra `Registrar check-out` y `Check-out` | PASS | BUG-007 FIX VALIDADO |
| CAJA-01 Caja dashboard | Los Cedros | `admin` / gerente | Caja abierta muestra resumen y movimientos recientes | Carga `Caja - Los Cedros`, caja abierta, 7 movimientos, ingresos `$18,900.00` | PASS | Formularios ingreso/gasto POST con CSRF. |
| CAJA-06 Corte actual / movimiento flujo QA | Los Cedros | `admin` / gerente | Movimiento de reservacion aparece en caja/corte actual | Caja muestra `Hospedaje - Reservacion #39`, ingreso efectivo `$600.00`, corte `#229` | PASS | Movimiento DB `movimientos_caja #1527`. |
| CAJA-07 Ingreso manual | Los Cedros | `admin` / gerente | Registrar ingreso manual debe crear movimiento en corte abierto y reflejarse en Caja | `POST /caja/ingreso` crea movimiento `#1536`, categoria `Otros`, efectivo `$12.34`, corte `#229`; aparece en Caja, Movimientos y Corte | PASS | `QA-FUNC-20260628-CAJA-ING-082102 ingreso manual efectivo`. |
| CAJA-08 Gasto manual | Los Cedros | `admin` / gerente | Registrar gasto manual debe crear movimiento en corte abierto y reflejarse en Caja | `POST /caja/gasto` crea movimiento `#1537`, categoria `Despensa`, efectivo `$4.56`, proveedor `QA Proveedor Caja`, corte `#229`; aparece en Caja, Movimientos y Corte | PASS | `QA-FUNC-20260628-CAJA-GAS-082102 gasto manual efectivo`. |
| CAJA-09 Validacion monto invalido | Los Cedros | `admin` / gerente | Montos `0` o negativos no deben crear movimientos | Ingreso con `$0.00` y gasto con `-$5.00` responden `303` a `/caja`; conteo DB queda `0 -> 0` para ambas descripciones invalidas | PASS | No se insertaron movimientos invalidos. |
| TENANT-01 Vistas operativas por hotel | Los Cedros/Demo/Maximiliano | `adminmax` / administrador | Dashboard, habitaciones, reservaciones y caja usan el hotel activo | Habitaciones muestra 49/7/14 cards para hoteles 1/2/4; reservaciones y caja cargan titulos/contexto del hotel activo | PASS | Sin cruce visible de branding/datos. |
| TENANT-02 URL directa cruzada | Los Cedros/Demo/Maximiliano | `adminmax` / administrador | Un hotel no debe ver detalles de habitaciones/reservaciones de otro hotel por ID directo | URLs cruzadas de habitaciones/reservaciones no exponen el registro externo; caen a la vista del hotel activo. Las mismas URLs con el hotel dueño si abren el detalle esperado | PASS |  |
| FACT-01 Listado de facturacion | Los Cedros | `admin` / gerente | `/facturacion` carga solicitudes del hotel activo, filtros y busqueda sin 500 | `GET /facturacion` carga 200; al cierre del bloque hotel 1 queda con 176 solicitudes: 4 pendientes, 1 en proceso, 169 completadas, 2 canceladas | PASS | Busquedas por reservacion/folio funcionaron. |
| FACT-02 Crear solicitud cliente desde check-in | Los Cedros | `admin` / gerente | Check-in con `requiere_factura=si` debe crear una solicitud tipo `cliente` por el total de la reservacion | Reservacion `#53`, VINO, total `$800.00`: check-in efectivo crea solicitud `#232`, `requiere_factura=si`, tipo `cliente`, estatus `pendiente`, monto `$800.00` | PASS | Movimiento nuevo caja `#1541`, pago `reservacion_pagos #41`. |
| FACT-03 Pagos visibles en detalle de factura | Los Cedros | `admin` / gerente | El detalle de factura debe listar solo pagos reales de la reservacion actual | `/facturacion/ver/232` muestra pagos por `$2,100.00`: incluye movimiento antiguo `#68` por `$1,300.00` mas el movimiento actual `#1541` por `$800.00` | FAIL | BUG-010 |
| FACT-03R Revalidar pagos visibles despues del fix | Los Cedros | `admin` / gerente | El detalle de factura debe excluir movimientos anteriores a la creacion de la reservacion | `/facturacion/ver/232` ya no muestra `$2,100.00` ni `$1,300.00`; solo muestra `$800.00` y la consulta valida devuelve movimiento `#1541` | PASS | BUG-010 FIX VALIDADO |
| FACT-04 Validaciones fiscales | Los Cedros | `admin` / gerente | RFC/CP invalidos no deben persistir; datos fiscales validos deben guardar y pasar a `en_proceso` | RFC `ABC` no escribe DB y conserva `pendiente`; luego RFC `XAXX010101000`, regimen `616`, CFDI `S01`, CP `64000` guardan y `#232` pasa a `en_proceso` | PASS | Notificacion `factura_lista #161`. |
| FACT-05 Completar factura | Los Cedros | `admin` / gerente | Marcar como facturada debe guardar folio y fecha | `POST /facturacion/completar` en `#232` guarda `numero_factura=QA-FAC-232`, `fecha_facturada=2026-06-28 09:06:11`, estatus `completada` | PASS | Notificacion `factura_completada #162`. |
| FACT-06 No factura + efectivo | Los Cedros | `admin` / gerente | Check-in con solo efectivo y `requiere_factura=no` no debe crear solicitud | Reservacion limpia `#57`, CORAL, check-in efectivo `$1,000.00`: `solicitudes_factura` queda en `0` | PASS | Pago `#42`, caja `#1542`. |
| FACT-07 Cambio a tarjeta sin factura | Los Cedros | `admin` / gerente | Cambiar metodo a tarjeta/transferencia con `requiere_factura=no` debe crear solicitud de uso interno | En `#57`, cambio a tarjeta `$1,000.00` crea solicitud `#233`, tipo `uso_interno`, metodo principal `tarjeta`, estatus `pendiente` | PASS | Caja actualizada a movimiento `#1543`. |
| FACT-08 Segundo cambio de metodo sin factura | Los Cedros | `admin` / gerente | Si ya existe solicitud activa para la reservacion, no debe duplicarla; debe actualizar/reusar la existente | En `#57`, segundo cambio a transferencia crea otra solicitud `#234` ademas de `#233`; quedan dos solicitudes para la misma reservacion y monto `$1,000.00` | FAIL | BUG-009 |
| FACT-08R Revalidar segundo cambio despues del fix | Los Cedros | `admin` / gerente | Un cambio posterior debe actualizar la solicitud activa, sin crear una nueva | Nuevo cambio de `#57` a tarjeta actualiza solicitud activa `#234` a `metodo_pago_principal=tarjeta`; no crea `#235`; pagos/caja quedan en tarjeta `QA-TARJ-57-FIX` | PASS | BUG-009 FIX VALIDADO |
| FACT-09 Acciones en proceso/cancelar | Los Cedros | `admin` / gerente | Acciones operativas deben cambiar estado y registrar notas/notificaciones | Solicitud `#233` pasa a `en_proceso` y luego `cancelada` con motivo QA; notas agregan bloque `[CANCELADA]` | PASS | Notificaciones `#163` y `#164`. |
| FACT-10 Aislamiento multi-hotel facturacion | Maximiliano -> Los Cedros | `adminmax` / administrador | Un hotel distinto no debe ver solicitud `#232` de Los Cedros por URL directa | Sesion Maximiliano (`hotel_id=4`) abre `/facturacion/ver/232`; respuesta final `200 http://localhost:8080/facturacion`, sin `QA-FAC-232`, `Reserva #53` ni razon social QA | PASS |  |
| INV-01 Crear producto fraccionable | Los Cedros | `admin` / gerente | Crear producto en litros con stock inicial 0 debe persistir catalogo sin movimientos | Producto `#43`, codigo `QAINV0628A`, stock `0.00`, unidad `litro`; no crea movimiento inicial | PASS |  |
| INV-02 Evitar codigo duplicado al crear | Los Cedros | `admin` / gerente | No debe crear otro producto con el mismo codigo del hotel | Intento con codigo `QAINV0628A` redirige a `/inventario/nuevo`; conteo DB se mantiene en `1` | PASS |  |
| INV-03 Crear producto con stock inicial | Los Cedros | `admin` / gerente | Stock inicial debe crear movimiento de entrada y trazabilidad de usuario | Producto `#44`, codigo `QAINV0628B`, stock `3.00`; movimiento `#1141` por `3.00`, pero `usuario_id=NULL` | FAIL | BUG-012 |
| INV-04 Editar producto sin mover stock | Los Cedros | `admin` / gerente | Editar nombre/costo/minimo no debe alterar stock ni crear movimientos | Producto `#43` queda stock `7.75`, nombre/costo/minimo actualizados; conteo movimientos permanece `10` | PASS |  |
| INV-05 Evitar codigo duplicado al editar | Los Cedros | `admin` / gerente | No debe permitir cambiar codigo a otro existente | Intento de cambiar `#43` a `QAINV0628B` redirige a `/inventario/editar/43`; producto conserva `QAINV0628A` | PASS |  |
| INV-06 Campo descripcion de producto | Los Cedros | `admin` / gerente | La descripcion capturada al crear/editar debe persistirse | Se envia descripcion al crear y editar `#43`, pero DB queda `descripcion=NULL` | FAIL | BUG-013 |
| INV-06R Revalidar descripcion de producto despues del fix | Los Cedros | `admin` / gerente | Crear y editar producto debe persistir descripcion | Producto `#46`, codigo `QAINV0628D`, guarda `QA BUG-013 descripcion creada` y luego actualiza a `QA BUG-013 descripcion editada` | PASS | BUG-013 FIX VALIDADO |
| INV-07 Entrada manual valida | Los Cedros | `admin` / gerente | Entrada de `5` debe subir stock y crear movimiento con usuario | Stock `#43` pasa `0.00 -> 5.00`; movimiento `#1131` entrada `5.00`, pero `usuario_id=NULL` | FAIL | BUG-012 |
| INV-08 Entrada manual decimal | Los Cedros | `admin` / gerente | Entrada `1.75` litros debe subir stock exacto `5.00 -> 6.75` | Backend guarda movimiento `#1132` por `1.00` y deja stock `6.00` | FAIL | BUG-011 |
| INV-09 Entrada manual cero | Los Cedros | `admin` / gerente | Cantidad `0` debe rechazarse sin movimiento | Backend crea movimiento `#1133` entrada `0.00`, stock `6.00 -> 6.00` | FAIL | BUG-011 |
| INV-10 Entrada manual negativa | Los Cedros | `admin` / gerente | Cantidad negativa debe rechazarse | Backend convierte `-3` en entrada `3.00`; stock `6.00 -> 9.00`, movimiento `#1134` | FAIL | BUG-011 |
| INV-11 Salida manual valida | Los Cedros | `admin` / gerente | Salida de `2` debe bajar stock, respetar habitacion y usuario | Stock `#43` pasa `9.00 -> 7.00`; movimiento `#1135`, habitacion `48`, pero `usuario_id=NULL` | FAIL | BUG-012 |
| INV-12 Salida sobre stock | Los Cedros | `admin` / gerente | Cantidad mayor al stock debe rechazarse sin movimiento | Salida `99` redirige a `/inventario/salida`; stock queda `7.00`, conteo de motivo QA sobre-stock `0` | PASS |  |
| INV-13 Salida manual cero | Los Cedros | `admin` / gerente | Cantidad `0` debe rechazarse sin movimiento | Backend crea movimiento `#1136` salida `0.00`, stock `7.00 -> 7.00` | FAIL | BUG-011 |
| INV-14 Salida manual negativa | Los Cedros | `admin` / gerente | Cantidad negativa debe rechazarse | Backend convierte `-2` en salida `2.00`; stock `7.00 -> 5.00`, movimiento `#1137` | FAIL | BUG-011 |
| INV-15 Salida manual decimal | Los Cedros | `admin` / gerente | Salida `1.50` litros debe descontar exacto `5.00 -> 3.50` | Backend guarda salida `1.00` y deja stock `4.00`, movimiento `#1138` | FAIL | BUG-011 |
| INV-08R Revalidar entrada decimal despues del fix | Los Cedros | `admin` / gerente | Entrada `1.75` debe respetar decimales | Stock `#43` pasa `7.75 -> 9.50`; movimiento `#1142` queda `ENTRADA 1.75`, stock anterior/posterior exactos | PASS | BUG-011 FIX VALIDADO |
| INV-09R Revalidar entrada cero/negativa despues del fix | Los Cedros | `admin` / gerente | Entradas `0` y negativas deben rechazarse sin movimiento | `0` y `-3` redirigen a `/inventario/entrada`; conteo de motivos `QA-FIX-BUG011-ENT-ZERO/NEG` queda `0`; stock queda `9.50` | PASS | BUG-011 FIX VALIDADO |
| INV-15R Revalidar salida decimal despues del fix | Los Cedros | `admin` / gerente | Salida `1.50` debe respetar decimales | Stock `#43` pasa `9.50 -> 8.00`; movimiento `#1143` queda `SALIDA 1.50`, habitacion `48` | PASS | BUG-011 FIX VALIDADO |
| INV-13R Revalidar salida cero/negativa despues del fix | Los Cedros | `admin` / gerente | Salidas `0` y negativas deben rechazarse sin movimiento | `0` y `-2` redirigen a `/inventario/salida`; conteo de motivos `QA-FIX-BUG011-SAL-ZERO/NEG` queda `0`; stock queda `8.00` | PASS | BUG-011 FIX VALIDADO |
| INV-12R Revalidar salida mayor al stock despues del fix | Los Cedros | `admin` / gerente | Salida mayor al stock debe seguir bloqueada | Salida `99` redirige a `/inventario/salida`; stock queda `8.00`; conteo `QA-FIX-BUG011-SAL-OVER` queda `0` | PASS | Regresion OK |
| INV-03R Revalidar usuario en stock inicial | Los Cedros | `admin` / gerente | Producto con stock inicial debe crear movimiento con usuario autenticado | Producto `#45`, codigo `QAINV0628C`; movimiento inicial `#1144` queda `usuario_id=1` | PASS | BUG-012 FIX VALIDADO |
| INV-07R Revalidar usuario en entrada manual | Los Cedros | `admin` / gerente | Entrada manual debe registrar usuario autenticado | Movimiento `#1145` entrada `0.50` para producto `#43` queda `usuario_id=1`; stock `8.00 -> 8.50` | PASS | BUG-012 FIX VALIDADO |
| INV-11R Revalidar usuario en salida manual | Los Cedros | `admin` / gerente | Salida manual debe registrar usuario autenticado | Movimiento `#1146` salida `0.25` para producto `#43`, habitacion `48`, queda `usuario_id=1`; stock `8.50 -> 8.25` | PASS | BUG-012 FIX VALIDADO |
| INV-16 Ajuste cero | Los Cedros | `admin` / gerente | Ajuste `0` debe rechazarse sin movimiento | Redirige a `/inventario/ajuste/43`; no crea movimiento `QA ajuste cero` | PASS |  |
| INV-17 Ajuste decimal | Los Cedros | `admin` / gerente | Ajuste decimal debe guardar stock exacto y usuario | POST directo entrada `1.25` deja stock `4.00 -> 5.25`; movimiento `#1139`, `usuario_id=1` | PASS | La vista usa `step=1` y etiqueta `pzs`, ver BUG-015. |
| INV-18 Compra borrador no mueve stock | Los Cedros | `admin` / gerente | Crear borrador de compra no debe modificar inventario | Compra `#13`, folio `QA-COMP-INV-0628A`, detalle `2.50`; stock `#43` sigue `5.25`, sin movimiento de recepcion | PASS |  |
| INV-19 Recibir compra mueve stock una vez | Los Cedros | `admin` / gerente | Recibir compra debe crear una entrada exacta y vincular detalle | Compra `#13` pasa a `recibida`; stock `5.25 -> 7.75`; movimiento `#1140` entrada `2.50`, usuario `1`, detalle `#24` vinculado | PASS |  |
| INV-20 Re-recibir compra no duplica | Los Cedros | `admin` / gerente | Recibir de nuevo una compra recibida no debe duplicar stock/movimiento | Segundo POST a `/compras/13/recibir` redirige a `/compras`; stock queda `7.75`, conteo movimientos `Recepcion compra #13` queda `1` | PASS |  |
| INV-21 API alertas y stock habitacion | Los Cedros | `admin` / gerente | Alertas y verificacion deben responder JSON scoped por hotel | `/api/inventario/alertas` lista productos bajo stock; `/api/inventario/verificar-stock/25` devuelve `disponible=false` con 7 faltantes de habitacion doble | PASS |  |
| INV-22 API preview check-in inventario | Los Cedros | `admin` / gerente | Si el endpoint existe, debe devolver preview util o estado funcional claro | `/api/inventario/preview-checkin/51` responde 200 con `success=false`, `Preview de inventario aun no disponible con fuente scoped` | NA | Funcionalidad pendiente/placeholder, no se marco bug operativo. |
| INV-23 Aislamiento multi-hotel inventario | Los Cedros -> Hotel Demo | `adminmax` / administrador | Un hotel distinto no debe ver/editar productos de Los Cedros | Hotel 2 carga inventario propio vacio; `/inventario/editar/43` redirige a `/inventario`; alertas hotel 2 vacias | PASS |  |
| INV-24 Exportacion PDF movimientos | Los Cedros | `admin` / gerente | Rango correcto debe generar PDF; boton rapido de hoy debe usar fecha actual | POST con `2026-06-28` genera PDF `inventario_los_cedros_20260628_132417.pdf` de 13 KB; vista/botones rapidos siguen hardcodeados a `2025-09-12/13` | FAIL | BUG-014 |
| INV-24R Revalidar exportacion rapida despues del fix | Los Cedros | `admin` / gerente | Vista y botones rapidos deben usar fecha actual del sistema | `/inventario/exportar` renderiza `fecha_desde=2026-06-01`, `fecha_hasta=2026-06-28`, JS `fechaHoyExportacion=2026-06-28`; PDF hoy genera `inventario_los_cedros_20260628_142314.pdf` de 14 KB y stock actual genera `inventario_los_cedros_20260628_142609.pdf` de 17 KB | PASS | BUG-014 FIX VALIDADO |
| INV-25 UI ajuste para unidades fraccionables | Los Cedros | `admin` / gerente | Producto en litros debe mostrar unidad correcta y permitir decimales | Backend acepta `1.25`, pero vista `inventario/ajuste` muestra `pzs`, `number_format(...,0)` y `step=1` | FAIL | BUG-015 |
| INV-25R Revalidar ajuste fraccionable despues del fix | Los Cedros | `admin` / gerente | Producto en litros debe mostrar unidad real, permitir decimales y previsualizar exacto | `/inventario/ajuste/43` renderiza `8.25 litro`, input `min=0.01`, `step=0.01`, `inputmode=decimal`; preview entrada `1.25` muestra `9.50 litro` y salida `7.00 litro`; POST entrada `0.25` crea movimiento `#1147`, POST salida `0.25` crea `#1148`, stock final vuelve a `8.25` | PASS | BUG-015 FIX VALIDADO |
| CXC-01 Generar CxC desde reservacion con anticipo | Los Cedros | `admin` / gerente | Reservacion `#44` con total `$1,200.00` y anticipo `$300.00` debe generar CxC solo por saldo pendiente, sin Caja automatica | POST `/cuentas-por-cobrar/generar-desde-reservacion/44` crea CxC `#3` por `$900.00`, movimiento `CREACION #14`; no crea movimiento Caja `Cobro CxC` | PASS |  |
| CXC-02 Rechazar sobrecobro | Los Cedros | `admin` / gerente | Cobro mayor al saldo no debe crear movimiento ni caja | Intento de cobrar `$901.00` en CxC `#3` redirige al detalle; saldo sigue `$900.00`, solo existe movimiento `#14`, no hay caja con referencia `QA-CXC-20260628-OVER-001` | PASS |  |
| CXC-03 Cobro parcial con Caja | Los Cedros | `admin` / gerente | Cobro valido debe descontar saldo, crear movimiento CxC y crear ingreso Caja en corte abierto | Cobro `$100.00` efectivo crea movimiento CxC `COBRO #15` y Caja `#1548`, corte `#229`; saldo `$900.00 -> $800.00`, estado `parcial` | PASS |  |
| CXC-04 Rechazar referencia duplicada | Los Cedros | `admin` / gerente | Una segunda captura con la misma referencia en la misma cuenta debe bloquearse sin caja | Intento de cobrar `$50.00` con referencia `QA-CXC-20260628-PAY-001` no crea segundo movimiento; saldo queda `$800.00`; conteo caja con esa referencia queda `1` | PASS |  |
| CXC-05 Revertir cobro | Los Cedros | `admin` / gerente | Reversion debe crear CANCELACION y gasto Caja, sin borrar el cobro original | Reversion de movimiento `#15` crea `CANCELACION #16` y gasto Caja `#1549` con `REV-CXC-3-MOV-15`; saldo vuelve `$800.00 -> $900.00`, estado `pendiente` | PASS |  |
| CXC-06 Bloquear doble reversion | Los Cedros | `admin` / gerente | Un cobro ya revertido no debe mostrar ni aceptar segunda reversion | Al recargar CxC `#3` ya no se genera `reversion_token` para `3:15`; no hay CANCELACION duplicada | PASS |  |
| CXP-01 Generar CxP desde compra recibida | Los Cedros | `admin` / gerente | Compra recibida `#13` debe generar CxP por total pendiente, sin Caja automatica | POST `/cuentas-por-pagar/generar-desde-compra/13` crea CxP `#3` por `$31.25`, estado `pendiente`, sin movimiento Caja de pago automatico | PASS |  |
| CXP-02 Rechazar sobrepago | Los Cedros | `admin` / gerente | Pago mayor al saldo no debe crear movimiento ni caja | Intento de pagar `$32.00` en CxP `#3` no crea movimientos; saldo queda `$31.25`, estado `pendiente` | PASS |  |
| CXP-03 Pago parcial proveedor con Caja | Los Cedros | `admin` / gerente | Pago valido debe descontar saldo, crear movimiento CxP y gasto Caja | Pago `$10.00` efectivo crea `PAGO_REFERENCIAL #13` y gasto Caja `#1550`, corte `#229`; saldo `$31.25 -> $21.25`, estado `parcial` | PASS |  |
| CXP-04 Rechazar referencia duplicada | Los Cedros | `admin` / gerente | Una segunda captura con la misma referencia en la misma cuenta debe bloquearse sin caja | Intento de pagar `$5.00` con referencia `QA-CXP-20260628-PAY-001` no crea segundo movimiento; saldo queda `$21.25`; conteo caja con esa referencia queda `1` | PASS |  |
| CXP-05 Revertir pago proveedor | Los Cedros | `admin` / gerente | Reversion debe crear CANCELACION e ingreso Caja, sin borrar el pago original | Reversion de movimiento `#13` crea `CANCELACION #14` e ingreso Caja `#1551` con `REV-CXP-3-MOV-13`; saldo vuelve `$21.25 -> $31.25`, estado `pendiente` | PASS |  |
| CXP-06 Bloquear doble reversion | Los Cedros | `admin` / gerente | Un pago ya revertido no debe mostrar ni aceptar segunda reversion | Al recargar CxP `#3` ya no se genera `reversion_token` para `3:13`; no hay CANCELACION duplicada | PASS |  |
| CXC-CXP-01 Aislamiento multi-hotel CxC/CxP | Maximiliano -> Los Cedros | `adminmax` / administrador | Hotel 4 no debe ver ni generar cuentas usando IDs de Los Cedros | GET CxC `#3` redirige a `/cuentas-por-cobrar/operativas`; GET CxP `#3` redirige a `/cuentas-por-pagar`; POST cruzados con reservacion `#44` y compra `#13` no crean registros en hotel 4 | PASS |  |
| DOC-01 Centro documental y formulario contextual | Los Cedros | `admin` / gerente | Listado y formulario de subida deben cargar con CSRF y contexto de entidad | `/documentos` carga `Centro documental - Los Cedros`; `/documentos/subir?entidad_tipo=huesped&entidad_id=1065` carga hidden `entidad_tipo=huesped`, `entidad_id=1065` y CSRF | PASS |  |
| DOC-02 Subir documento valido a huesped | Los Cedros | `admin` / gerente | JPG permitido debe guardarse privado, crear documento y vinculo a huesped | Upload crea documento `#18`, tipo `Identificacion`, estado `activo`, `storage_path=documentos/hotel_1/2026/06/doc_20260628_162751_e46f74924443e4a4.jpg`, vinculo `huesped #1065`, relacion `identificacion_qa`; aparece en `/huespedes/1065` | PASS |  |
| DOC-03 Descarga y preview | Los Cedros | `admin` / gerente | Documento activo debe descargar como attachment y previsualizar inline | `/documentos/18/descargar` responde `200 image/jpeg`, `Content-Disposition: attachment`, `Content-Length: 68219`; `?preview=1` responde `inline`, `X-Frame-Options: SAMEORIGIN`, `nosniff` | PASS |  |
| DOC-04 Rechazar extension peligrosa | Los Cedros | `admin` / gerente | Archivo `.php` debe rechazarse sin DB ni storage | Upload `qa_invalid.php` redirige al formulario, muestra `Extension de archivo no permitida`; conteo de documento/titulo `QA-DOC-20260628-INVALID-PHP` queda `0`, sin archivo `.php` en storage | PASS |  |
| DOC-05 Editar metadata | Los Cedros | `admin` / gerente | Metadata debe actualizar tipo/titulo/descripcion/etiquetas sin reemplazar archivo | Documento `#18` cambia a tipo `Evidencia`, titulo `QA-DOC-20260628-HUESPED-IMG-EDIT`, descripcion y etiquetas editadas; detalle refleja cambios | PASS |  |
| DOC-06 Archivar y restaurar | Los Cedros | `admin` / gerente | Archivar debe bloquear descarga; restaurar debe reactivar descarga | Documento `#18` pasa a `archivado`; descarga directa redirige a `/documentos`; la ficha muestra `Restaurar`; al restaurar vuelve a `activo` y descarga responde `200` | PASS |  |
| DOC-07 Baja logica | Los Cedros | `admin` / gerente | Baja logica debe dejar ficha consultable y bloquear descarga/acciones activas | Documento `#19` creado como `QA-DOC-20260628-LIFECYCLE`, luego `eliminado`; descarga directa redirige a `/documentos`, ficha muestra estado `Eliminado` sin acciones de descarga/edicion | PASS |  |
| DOC-08 Filtros/listado | Los Cedros | `admin` / gerente | Busqueda y filtros por estado deben separar activos/eliminados | `buscar=QA-DOC-20260628` muestra 2 documentos; `estado=activo` muestra solo `#18`; `estado=eliminado` muestra solo `#19` | PASS |  |
| DOC-09 Vincular documento a CxP | Los Cedros | `admin` / gerente | Cuenta por pagar debe aceptar documento vinculado y mostrarlo en su ficha | Upload crea documento `#21`, vinculo `cuenta_por_pagar #3`, relacion `comprobante_pago_qa`; `/cuentas-por-pagar/3` muestra `QA-DOC-20260628-CXP3` en documentos vinculados | PASS |  |
| DOC-10 Aislamiento multi-hotel documentos | Maximiliano -> Los Cedros | `adminmax` / administrador | Hotel 4 no debe ver, descargar ni vincular documentos/entidades de Los Cedros | GET `/documentos/18` y descarga redirigen a `/documentos`; `/documentos/entidad/huesped/1065` redirige; busqueda hotel 4 no lista `QA-DOC`; POST a `huesped #1065` no crea documentos ni vinculos | PASS |  |
| DOC-11 Vincular documento a tarea | Maximiliano | `adminmax` / administrador | Desde una tarea con panel de documentos, la carga debe crear documento y vinculo a tarea | Retest post-fix: `POST /documentos/subir` con `entidad_tipo=tarea`, `entidad_id=5`, archivo permitido y titulo `QA-DOC-20260630-TAREA-BUG016` responde `303` a `/documentos/22`; DB crea documento `#22` y vinculo `tarea #5`; `/tareas/5` muestra el titulo. | PASS | BUG-016 FIX VALIDADO |
| REPORT-01 Centro de reportes | Los Cedros / Maximiliano | `admin`, `adminmax` | `/reportes` debe cargar con branding/contexto del hotel activo | Ambos hoteles responden `200`; Los Cedros queda `MEDISOFT_CONTEXT hotel_id=1`, Maximiliano `hotel_id=4`, sin cruce visual en el indice | PASS |  |
| REPORT-02 Ingresos vs gastos | Los Cedros / Maximiliano | `admin`, `adminmax` | Totales por fecha deben coincidir con `movimientos_caja` scoped por hotel y zona horaria de la app | Para `2026-06-28`, Los Cedros muestra ingresos `$1,922.34`, gastos `$114.56`; Maximiliano ingresos `$12,600.00`, gastos `$0.00`; ambos cuadran contra DB con `time_zone=-06:00` | PASS |  |
| REPORT-03 Selector reporte por usuario | Los Cedros / Maximiliano | `admin`, `adminmax` | El selector de usuarios del reporte financiero debe listar solo usuarios vinculados al hotel activo | El selector muestra usuarios de otros hoteles (`Admin Demo SaaS`, `QA Admin Temporal 174223`) en Los Cedros y Maximiliano; ademas Los Cedros permite generar PDF para `usuario_id=22` de Hotel Demo | FAIL | BUG-017 |
| REPORT-04 Gerencial diario | Los Cedros / Maximiliano | `admin`, `adminmax` | Finanzas, ocupacion, agenda, caja, facturacion e inventario deben cuadrar por hotel | `2026-06-28`: Los Cedros muestra `$1,922.34/$114.56`, 6/49 ocupacion, 4 salidas, caja abierta 1, facturas 4, inventario bajo 9; Maximiliano muestra `$12,600.00/$0.00`, 7/14, 1 entrada, caja abierta 1, facturas 3, inventario bajo 0 | PASS |  |
| REPORT-05 Tablero ejecutivo custom | Los Cedros / Maximiliano | `admin`, `adminmax` | `periodo=custom` debe respetar fechas y consolidar CxC/CxP/compras/inventario/documentos por hotel | Para `2026-06-28`, Los Cedros muestra ingresos `$1,922.34`, gastos `$114.56`, CxC `$900.00`, CxP `$31.25`, compras `$31.25`, documentos 3, auditoria 28; Maximiliano muestra sus saldos propios (`$5,750.00` CxC, `$1,910.00` CxP) | PASS |  |
| REPORT-06 AJAX datos de grafica | Los Cedros / Maximiliano | `admin`, `adminmax` | Endpoint AJAX debe devolver JSON scoped; sin header AJAX debe redirigir | Con `X-Requested-With` devuelve JSON correcto para ingresos/gastos (`1922.34/114.56` y `12600.00/0.00`); sin header redirige `303` a `/reportes` | PASS |  |
| REPORT-07 PDF gerencial, ingresos totales y links seguros | Los Cedros / Maximiliano | `admin`, `adminmax` | Exportaciones disponibles deben generar PDF, registrar link seguro y aislar descargas internas por hotel | Gerencial crea links `#15` Los Cedros y `#16` Maximiliano; ingresos totales crea `#17`; `/reportes/links/15/descargar` sirve PDF a Los Cedros y redirige/bloquea a Maximiliano | PASS |  |
| REPORT-08 PDF principal ingresos/gastos | Los Cedros | `admin` / gerente | `tipo=ingresos-gastos` debe descargar PDF y registrar link seguro | Devuelve `200 text/html` de 113 bytes con `TCPDF ERROR: TCPDF requires the Imagick or GD extension to handle PNG images with alpha channel`; no crea `reporte_links` | FAIL | BUG-018 |
| REPORT-09 PDFs procedencia y habitaciones rentables | Los Cedros | `admin` / gerente | Tipos expuestos en `exportar-pdf` deben generar PDF o no mostrarse como exportables | `tipo=habitaciones-rentables` y `tipo=procedencia` devuelven `500`; logs: `Metodo exportarHabitacionesRentablesPdfAction no encontrado` y `Metodo exportarProcedenciaPdfAction no encontrado` | FAIL | BUG-019 |
| REPORT-10 Pantallas secundarias que si cargan | Los Cedros | `admin` / gerente | Reportes secundarios activos deben cargar sin 500 | `/reportes/limpieza`, `/reportes/mantenimiento-programado`, `/reportes/procedencia`, `/reportes/habitaciones-rentables` y `/reportes/mantenimiento` responden `200` | PASS |  |
| REPORT-11 Pantalla ocupacion | Los Cedros | `admin` / gerente | `/reportes/ocupacion` debe cargar para `diario`, `semanal` y `mensual` | Los tres tipos devuelven `500`; logs muestran consulta incompatible con `ONLY_FULL_GROUP_BY` y fatal `Call to a member function fetchAll() on bool` en `Reporte.php` | FAIL | BUG-020 |
| REPORT-12 Pantallas estancia y ranking estados | Los Cedros | `admin` / gerente | Rutas expuestas deben cargar vistas funcionales | `/reportes/estancia` y `/reportes/ranking-estados` devuelven `500`; logs: `Vista reportes/estancia no encontrada` y `Vista reportes/ranking-estados no encontrada` | FAIL | BUG-021 |
| REPORT-13R Revalidar selector/export por usuario | Los Cedros | `admin` / gerente | Selector y export deben limitar usuarios al hotel activo | `/reportes/ingresos-gastos` responde `200`; selector ya no contiene `Admin Demo SaaS` ni `QA Admin Temporal`; export con `usuario_id=22` responde `303` a `/reportes/ingresos-gastos` y el conteo de links cross-hotel se mantiene en `1` (solo evidencia vieja `#18`); export con `usuario_id=1` genera PDF `%PDF` de `14874` bytes y link `#19` | PASS | BUG-017 FIX VALIDADO. La sesion QA de Maximiliano expiro durante el retest; el filtro corregido usa `hotel_usuarios.hotel_id`. |
| REPORT-14R Revalidar PDFs expuestos | Los Cedros | `admin` / gerente | Exportaciones PDF visibles deben responder `application/pdf` y registrar link seguro | `ingresos-gastos` responde `200 application/pdf`, `%PDF`, `13258` bytes y link `#22`; `procedencia` responde `200 application/pdf`, `%PDF`, `8667` bytes y link `#20`; `habitaciones-rentables` responde `200 application/pdf`, `%PDF`, `10012` bytes y link `#21` | PASS | BUG-018 y BUG-019 FIX VALIDADO |
| REPORT-15R Revalidar ocupacion | Los Cedros | `admin` / gerente | `/reportes/ocupacion` debe cargar sin 500 para `diario`, `semanal`, `mensual` | Los tres tipos responden `200 text/html`; no aparece `SQLSTATE`, `Fatal error`, `ONLY_FULL_GROUP_BY` ni `Call to a member` en HTML revisado | PASS | BUG-020 FIX VALIDADO |
| REPORT-16R Revalidar estancia/ranking | Los Cedros | `admin` / gerente | `/reportes/estancia` y `/reportes/ranking-estados` deben renderizar vistas funcionales | Ambas rutas responden `200 text/html`; no aparece `Vista no encontrada`, `Fatal error` ni errores SQL en HTML revisado | PASS | BUG-021 FIX VALIDADO |
| REPORT-17R Regresion reportes centrales | Los Cedros | `admin` / gerente | Reportes aprobados antes del fix deben seguir cargando | `/reportes`, `/reportes/gerencial-diario?fecha=2026-06-28` y `/reportes/ejecutivo?periodo=custom&fecha_desde=2026-06-28&fecha_hasta=2026-06-28` responden `200`; AJAX `datos-grafica` responde JSON `ingresos=1922.34`, `gastos=114.56` | PASS | Regresion post BUG-017..021 |
| RESP-01 Login responsive base | Los Cedros | Sin sesion | Login debe ajustarse a movil/tablet/desktop sin scroll horizontal | Prueba visual real en 360x740, 390x844, 768x1024 y 1366x768: `scrollWidth == clientWidth`, sin overflow horizontal; formulario principal accesible | PASS |  |
| RESP-02 Zoom mobile / accesibilidad global | Los Cedros | Sin sesion y paginas autenticadas por HTML | El usuario movil debe poder hacer zoom/pinch cuando lo necesite | Retest post-fix: login, layout autenticado y offline usan viewport sin `maximum-scale=1.0` ni `user-scalable=no`; login/offline no registran listeners `gesture*` ni bloqueo global de `touchmove/touchend`; `pwa.js` servido no contiene `lockMobileZoomGestures`; `/dashboard` 390x844 queda sin overflow. | PASS | BUG-022 FIX VALIDADO |
| RESP-03 Targets tactiles login | Los Cedros | Sin sesion | Controles interactivos secundarios deben tener area tactil comoda en movil | Retest aislado post-fix en `/h/los-cedros/login`: 360x740, 390x844 y 768x1024 sin overflow horizontal; boton `Mostrar contrasena` `44x44`, label `Recordarme` `44px` de alto, checkbox visual `22x22` y link `Olvidaste tu contrasena` `44px` de alto. Click en el ojo cambia password a texto. | PASS | BUG-023 FIX VALIDADO |
| RESP-04 Paginas autenticadas / estructura responsive | Los Cedros | `admin` / gerente | Pantallas principales deben servir HTML con contexto y patrones responsive para tablas/listados/formularios | 22 rutas autenticadas responden `200` con `MEDISOFT_CONTEXT`; huespedes, compras, facturacion, CxC/CxP y reservaciones tienen tablas desktop con tarjetas moviles o contenedores de scroll; formularios largos tienen breakpoints a una columna; calendario usa scroll interno controlado | PASS (estatico) |  |
| RESP-05 Validacion visual autenticada base | Los Cedros | `admin` / gerente | Sidebar movil, overlay, scroll real y header auto-hide deben funcionar con sesion activa | En 390x844, dashboard muestra header movil y boton hamburguesa; sidebar abre `active`, overlay cubre pantalla, cierra con overlay; en `/documentos` el scroll ocurre en `.main-content`, el header se oculta al bajar y reaparece al subir | PASS |  |
| RESP-06 Matriz responsive autenticada | Los Cedros | `admin` / gerente | Pantallas criticas deben responder en movil/tablet/desktop sin romper navegacion | Se probaron dashboard, operacion diaria, habitaciones, huespedes, reservaciones, detalle/crear reservacion, caja, facturacion, inventario, documentos, reportes, ingresos/gastos y calendario en 390x844, 768x1024 y 1366x768; tras fix responsive local, dashboard/documentos/inventario/habitaciones mantienen `scrollWidth == clientWidth` en los retests criticos o usan scroll interno intencional | PASS CON OBSERVACIONES | BUG-022, BUG-023, BUG-024, BUG-025, BUG-026 FIX VALIDADO |
| RESP-07 Modal caja ingreso movil | Los Cedros | `admin` / gerente | Modal debe abrir/cerrar sin overflow y sin perder acciones en telefono | En 390x844, `Registrar Ingreso` abre `#modalIngreso` como bottom sheet de `370x613`; form tiene scroll interno, campos `336px`, acciones `Cancelar/Guardar` de `163x42`, sin overflow horizontal; se cerro sin enviar formulario | PASS |  |
| RESP-08 Modal check-in disponible | Los Cedros | `admin` / gerente | Modal de check-in debe validarse sin enviar formulario cuando haya boton visible | No hubo boton visible `abrirModalCheckIn` en listado ni en reservaciones QA confirmadas `#42,#43,#45,#48,#49,#50,#51,#55,#56`; queda no aplicable con datos actuales | N/A | Revalidar cuando exista una reservacion con accion visible de check-in. |
| RESP-09 Dashboard movil agenda overflow | Los Cedros | `admin` / gerente | Dashboard en movil no debe recortar ni desbordar contenido operativo | Retest post-fix en 360x740: `bodyScrollWidth=360`, `docScrollWidth=360`; `.dm-ag.is-link` `left=23/right=329` y `.dm-ag .tm` `left=268/right=321`. En 390x844: `bodyScrollWidth=390`, `.dm-ag .tm` `left=297/right=351`. Agenda visible dentro del renglon. | PASS | BUG-024 FIX VALIDADO |
| RESP-10 Targets tactiles autenticados | Los Cedros | `admin` / gerente | Acciones operativas en mobile/tablet deben tener area tactil comoda | Retest post-fix: dashboard links `44px` y acciones `45px`; documentos `.dc-btn/.dc-card-btn` `44px`; inventario `.btn-inv/.btn-config-inv/.inv-search` `44px`; habitaciones `.hb-filter-trigger/.hb-chip/.filter-date/.filter-btn/.btn-action` `44px`. Login queda cubierto aparte por RESP-03. | PASS PARCIAL | BUG-025 FIX VALIDADO en autenticadas; BUG-023 FIX VALIDADO en login |
| RESP-11 Documentos tablet hero clipping | Los Cedros | `admin` / gerente | Tablet 768 no debe recortar contenido del hero | Retest post-fix en 768x1024: `bodyScrollWidth=768`, `docScrollWidth=768`; `.dc-hero-section` `left=16/right=744`, `.dc-title-lockup` `left=16/right=744`, `.dc-filter-form` `left=33/right=727`. | PASS | BUG-026 FIX VALIDADO |
| PWA-03 Smoke `/api/sync` autenticado | Los Cedros | `admin` / gerente | POST autenticado responde HTTP 423 y JSON `sync_temporarily_disabled` | Retest post BUG-022 con sesion temporal Los Cedros: POST JSON `{}` responde `HTTP/1.1 423 Locked`, `Content-Type: application/json; charset=utf-8`, body con `success=false`, `error=sync_temporarily_disabled`, mensaje de sincronizacion offline temporalmente deshabilitada y `pending_operations_preserved=true` | PASS | No se tocaron `service-worker.js`, IndexedDB, cache names ni `/api/sync`; BUG-022 solo retiro bloqueo de zoom en viewport/JS. |

## Ronda final - Regresion funcional post-fixes

Fecha de ejecucion: 2026-06-30.

Alcance: regresion de humo profundo, sin registrar nuevos pagos/cobros/check-in/check-out ni movimientos financieros. Se usaron sesiones QA temporales para Los Cedros y Maximiliano.

| Caso | Hotel | Resultado esperado | Resultado real | Estado | Notas |
|---|---|---|---|---|---|
| REG-FINAL-01 Rutas operativas Los Cedros | Los Cedros | Modulos centrales deben responder `200` y sin errores PHP/SQL visibles | `32/32` checks totales pasaron; en Los Cedros respondieron `200`: `/dashboard`, `/habitaciones`, `/huespedes`, `/reservaciones`, `/reservaciones/ver/51`, `/reservaciones/crear`, `/facturacion`, `/facturacion/ver/232`, `/inventario`, `/documentos`, `/caja`, CxC/CxP y reportes principales | PASS | Detector reviso `PHP Warning`, `Fatal error`, `SQLSTATE`, `Uncaught`, `ONLY_FULL_GROUP_BY`, `Undefined variable` y patrones equivalentes. |
| REG-FINAL-02 Rutas documentos/tareas Maximiliano | Maximiliano | Documento `#22` debe seguir ligado a tarea `#5` y ser visible/descargable | Respondieron `200`: `/dashboard`, `/tareas`, `/tareas/5`, `/tareas/reporte`, `/documentos`, `/documentos/22`, `/documentos/entidad/tarea/5`, `/documentos/22/descargar?preview=1`, `/reportes`, `/habitaciones`; preview de documento respondio `image/png` | PASS | Confirma regresion del fix BUG-016. |
| REG-FINAL-03 PWA sync bloqueado | Los Cedros | `/api/sync` debe seguir bloqueado con `423 sync_temporarily_disabled` | POST autenticado a `/api/sync` respondio `423`, `application/json`, body con `sync_temporarily_disabled` y `pending_operations_preserved` | PASS | No se toco el endpoint ni `service-worker.js`, IndexedDB o cache names. |
| REG-FINAL-04 Logs post-regresion | App container | La corrida no debe generar warnings/fatals/SQL nuevos | `docker compose logs --since=5m app` sin matches para `PHP Warning`, `PHP Fatal`, `Fatal error`, `SQLSTATE`, `Uncaught`, `Undefined variable`, `ONLY_FULL_GROUP_BY` ni `Call to a member` | PASS | La primera pasada marco falsos positivos por textos JS `warning:`; se repitio con detector de errores reales. |
| REG-FINAL-05 Checks tecnicos finales | Codigo/DB | Lints, JS y datos de migracion deben quedar correctos | `php -l` OK en `login.php`, `header.php`, `Documento.php`, `DocumentoController.php`, `TareaController.php`; `node --check src/public_html/js/pwa.js` OK; enum `documento_entidades.entidad_tipo` incluye `tarea`; migracion `20260630_001_documento_entidades_tarea_enum.sql` registrada como ejecutada; vinculo documento `#22` -> tarea `#5` existe | PASS | `rg` no encuentra `user-scalable=no`, `maximum-scale=1.0`, `lockMobileZoomGestures` ni listeners globales `gesture*`/bloqueo zoom en `src/app/views` y `src/public_html`. |

## Datos QA creados

| ID prueba | Modulo | Registro creado | ID generado | Estado | Notas |
|---|---|---:|---:|---|---|
| QA-FUNC-20260628-JUNTOS-HUE | Huespedes | QA-FUNC-20260628-JUNTOS Huesped Principal | 1065 | Creado | Vehiculo `QA-6828`, email `qa.func.20260628.juntos@example.local`. |
| QA-FUNC-20260628-JUNTOS-RES | Reservaciones | Reservacion 2026-06-27 a 2026-06-28, hab. AMBAR | 39 | Checked-out | Huesped `1065`, habitacion `16`, total `$600.00`, pago efectivo `$600.00`, caja `#1527`, estado `checked_out`, habitacion en `limpieza`; UI post-save cayo en BUG-003. |
| QA-FUNC-20260628-JUNTOS-MDD-EXT | Reservaciones | Reservacion 2026-06-27 a 2026-06-28, hab. MENTA; luego extendida a 2026-06-29 | 40 | Checked-in | Huesped `1065`, habitacion `15`, total `$1,200.00`, pagos registrados `$600.00`, caja `#1528` + `#1529`; inconsistencia BUG-004. |
| QA-FUNC-20260628-JUNTOS-MDD-RED | Reservaciones | Reservacion 2026-06-27 a 2026-06-29, hab. UVA; luego reducida a 2026-06-28 | 41 | Checked-in | Huesped `1065`, habitacion `14`, total `$600.00`, pagos registrados `$1,200.00`, caja `#1530` + `#1531`; inconsistencia BUG-005. |
| QA-FUNC-20260628-JUNTOS-MDD-PRE-EXT | Reservaciones | Reservacion 2026-07-10 a 2026-07-11, hab. PURPURA; luego extendida a 2026-07-12 | 42 | Confirmada | Huesped `1065`, habitacion `2`, total `$1,200.00`, sin pagos/caja, `reservacion_habitaciones.precio=$600.00`; BUG-006. |
| QA-FUNC-20260628-JUNTOS-MDD-PRE-RED | Reservaciones | Reservacion 2026-07-15 a 2026-07-17, hab. VIOLETA; luego reducida a 2026-07-16 | 43 | Confirmada | Huesped `1065`, habitacion `7`, total `$600.00`, sin pagos/caja, `reservacion_habitaciones.precio=$1,200.00`; BUG-006. |
| QA-FUNC-20260628-JUNTOS-MDD-PRE-ANT | Reservaciones | Reservacion 2026-07-20 a 2026-07-21, hab. LIMON, anticipo `$300`; luego extendida a 2026-07-22 | 44 | Confirmada | Huesped `1065`, habitacion `8`, total `$1,200.00`, abono/caja `$300.00`, saldo `$900.00`, `reservacion_habitaciones.precio=$600.00`; BUG-006. |
| QA-FUNC-20260628-JUNTOS-MDD-FIX-006 | Reservaciones | Reservacion 2026-07-25 a 2026-07-26, hab. MOKA; extendida a 2 noches y reducida a 1 noche despues del fix | 45 | Confirmada | Huesped `1065`, habitacion `1`; tras extender: total/subtotal `$2,000.00`; tras reducir: total/subtotal `$1,000.00`; sin pagos/caja. |
| QA-FUNC-20260628-JUNTOS-MDD-FIX-004 | Reservaciones | Reservacion 2026-06-27 a 2026-06-28, hab. ORO; check-in exacto `$800`, extendida a 2 noches y reducida a 1 sin sobrepago | 46 | Checked-in | Extension dejo saldo `$800.00` sin caja extra; reduccion regreso a total/pagado/subtotal/caja neta `$800.00`, sin devolucion indebida. |
| QA-FUNC-20260628-JUNTOS-MDD-FIX-005 | Reservaciones | Reservacion 2026-06-27 a 2026-06-29, hab. AMARILLO; check-in exacto `$2,000`, reducida a 1 noche | 47 | Checked-in | Devolucion caja `#1535` por `$1,000.00`; pagos netos `$1,000.00`; caja neta `$1,000.00`; total/subtotal `$1,000.00`. |
| QA-FUNC-20260628-JUNTOS-BUG003-REPRO | Reservaciones | Reservaciones AMBAR creadas durante reproduccion del 404 de guardado | 48, 49, 50 | Confirmadas | DB guarda correctamente, pero antes del fix la UI caia o quedaba en `/reservaciones/guardar` 404. |
| QA-FUNC-20260628-JUNTOS-BUG003-FIX | Reservaciones | Reservacion 2026-08-07 a 2026-08-08, hab. AMBAR | 51 | Confirmada | Validacion fix BUG-003: UI queda en `/reservaciones/ver/51`; logs sin `GET /reservaciones/guardar`. |
| QA-FUNC-20260628-FACT-CLIENTE | Reservaciones/Facturacion | Reservacion VINO 2026-09-10 a 2026-09-11 + solicitud cliente | 53, 232 | Checked-in / completada | Total `$800.00`; check-in efectivo; solicitud `#232` completada con folio `QA-FAC-232`. BUG-010 validado: detalle ya solo muestra pago `#1541`. |
| QA-FUNC-20260628-FACT-SKIP | Reservaciones | Reservaciones creadas para detectar/saltar IDs con registros antiguos asociados | 55, 56 | Confirmadas | `#55` y `#56` quedaron como evidencia de IDs contaminados por movimientos antiguos; no se usaron para el flujo limpio. |
| QA-FUNC-20260628-FACT-INTERNA | Reservaciones/Facturacion | Reservacion CORAL 2026-09-16 a 2026-09-17 + solicitudes internas por cambio de metodo | 57, 233, 234 | Checked-in | Efectivo sin factura no crea solicitud; `#233` queda historica cancelada; BUG-009 validado: `#234` se actualiza sin crear nuevas solicitudes. |
| QA-FUNC-20260628-CAJA-ING-082102 | Caja | Ingreso manual efectivo, categoria `Otros` | 1536 | Creado | Corte `#229`, hotel Los Cedros, monto `$12.34`, referencia `QA-ING-20260628`, comprobante `QA-ING-COMP`. |
| QA-FUNC-20260628-CAJA-GAS-082102 | Caja | Gasto manual efectivo, categoria `Despensa` | 1537 | Creado | Corte `#229`, hotel Los Cedros, monto `$4.56`, proveedor `QA Proveedor Caja`, referencia `QA-GAS-20260628`, comprobante `QA-GAS-COMP`. |
| QA-FUNC-20260628-INV-LITROS | Inventario | Producto fraccionable QA Inventario Litros 0628 | 43 | Activo | Codigo `QAINV0628A`, unidad `litro`, stock final `8.25`; BUG-011 validado con movimientos `#1142` y `#1143`; BUG-012 validado con `#1145` y `#1146`; BUG-015 validado con movimientos `#1147` y `#1148`; conserva evidencia historica de BUG-013. |
| QA-FUNC-20260628-INV-INICIAL | Inventario | Producto QA Inventario Inicial 0628 | 44 | Activo | Codigo `QAINV0628B`, stock inicial `3.00`; movimiento inicial `#1141` queda sin usuario. |
| QA-FUNC-20260628-INV-USUARIO | Inventario | Producto QA Inventario Usuario 0628 | 45 | Activo | Codigo `QAINV0628C`, stock inicial `2.00`; movimiento inicial `#1144` queda con `usuario_id=1`. |
| QA-FUNC-20260628-INV-DESC | Inventario | Producto QA Inventario Descripcion 0628 | 46 | Activo | Codigo `QAINV0628D`; descripcion creada/editada persistida; BUG-013 validado. |
| QA-FUNC-20260628-COMPRA-INV | Compras/Inventario | Compra QA-COMP-INV-0628A recibida | 13 / 24 / 1140 | Recibida | Borrador no movio stock; recepcion sumo `2.50` exacto a producto `#43`; segundo recibir no duplico. |
| QA-FUNC-20260628-CXC-RES44 | CxC/Caja | Cuenta por cobrar generada desde reservacion `#44` | 3 / 14, 15, 16 / 1548, 1549 | Pendiente | CxC total/saldo final `$900.00`; cobro parcial `$100.00` revertido, neto Caja `0.00`; sobrecobro, referencia duplicada, doble reversion y cruce hotel 4 bloqueados. |
| QA-FUNC-20260628-CXP-COMPRA13 | CxP/Caja | Cuenta por pagar generada desde compra `#13` | 3 / 13, 14 / 1550, 1551 | Pendiente | CxP total/saldo final `$31.25`; pago parcial `$10.00` revertido, neto Caja `0.00`; sobrepago, referencia duplicada, doble reversion y cruce hotel 4 bloqueados. |
| QA-FUNC-20260628-DOC-HUESPED | Documentos/Huespedes | Documento QA vinculado a huesped `#1065` | 18 / 13 | Activo | `QA-DOC-20260628-HUESPED-IMG-EDIT`; storage privado `0640`; descarga/preview OK; metadata editada; archivado/restaurado validado. |
| QA-FUNC-20260628-DOC-LIFECYCLE | Documentos | Documento QA para baja logica | 19 | Eliminado | Descarga bloqueada tras baja logica; ficha conserva estado `Eliminado`; sin vinculos. |
| QA-FUNC-20260628-DOC-CXP | Documentos/CxP | Documento QA vinculado a CxP `#3` | 21 / 14 | Activo | `QA-DOC-20260628-CXP3`; visible en `/cuentas-por-pagar/3`; relacion `comprobante_pago_qa`. |
| QA-FUNC-20260628-REPORT-GD-LC | Reportes | Link seguro PDF gerencial Los Cedros | 15 | Activo | `Reporte_Gerencial_Diario_20260628_los_cedros.pdf`, `tamano_bytes=10759`; descarga interna bloqueada desde Maximiliano. |
| QA-FUNC-20260628-REPORT-GD-MAX | Reportes | Link seguro PDF gerencial Maximiliano | 16 | Activo | `Reporte_Gerencial_Diario_20260628_hotel_maximiliano.pdf`, `tamano_bytes=10747`; visible solo en historial de Maximiliano. |
| QA-FUNC-20260628-REPORT-ING-TOT | Reportes | Link seguro PDF ingresos totales Los Cedros | 17 | Activo | `Reporte_Ingresos_Totales_los_cedros_20260628_170236.pdf`, `tamano_bytes=16955`. |
| QA-FUNC-20260628-REPORT-USR-CROSS | Reportes | PDF por usuario de otro hotel generado desde Los Cedros | 18 | Activo / bug | `Reporte_Movimientos_los_cedros_20260628_170445.pdf`, parametros `usuario_id=22`, `usuario_nombre=Admin Demo SaaS`; evidencia BUG-017. |
| QA-FUNC-20260628-REPORT-USR-VALID-FIX | Reportes | PDF por usuario valido Los Cedros | 19 | Activo / fix | `Reporte_Movimientos_los_cedros_20260628_181057.pdf`, `tamano_bytes=14874`; evidencia BUG-017 fix. |
| QA-FUNC-20260628-REPORT-PROC-FIX | Reportes | PDF procedencia Los Cedros | 20 | Activo / fix | `Reporte_Procedencia_los_cedros_20260628_181057.pdf`, `tamano_bytes=8667`; evidencia BUG-019 fix. |
| QA-FUNC-20260628-REPORT-RENT-FIX | Reportes | PDF habitaciones rentables Los Cedros | 21 | Activo / fix | `Reporte_Habitaciones_Rentables_los_cedros_20260628_181057.pdf`, `tamano_bytes=10012`; evidencia BUG-019 fix. |
| QA-FUNC-20260628-REPORT-ING-GAS-FIX | Reportes | PDF ingresos/gastos Los Cedros | 22 | Activo / fix | `Ingresos_Gastos_los_cedros_20260628_181057.pdf`, `tamano_bytes=13258`; evidencia BUG-018 fix. |

## Bugs detectados

### BUG-011 - Entradas/salidas manuales aceptan cero/negativos y truncan decimales

Severidad: P1
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Inventario / Entrada manual / Salida manual
URL: `/inventario/entrada`, `/inventario/salida`

Pasos:
1. Crear producto fraccionable `#43`, codigo `QAINV0628A`, unidad `litro`.
2. Registrar entrada `1.75`.
3. Registrar entrada `0`.
4. Registrar entrada `-3`.
5. Registrar salida `0`.
6. Registrar salida `-2`.
7. Registrar salida `1.50`.

Resultado esperado:

El backend debe rechazar cantidades `<= 0` y preservar cantidades decimales cuando la unidad lo permite o cuando la tabla maneja `decimal(10,2)`.

Resultado real:

- Entrada `1.75` se guarda como `1.00`; stock `5.00 -> 6.00` en vez de `6.75`.
- Entrada `0` crea movimiento `#1133` por `0.00`.
- Entrada `-3` se convierte en entrada positiva `3.00`; stock `6.00 -> 9.00`.
- Salida `0` crea movimiento `#1136` por `0.00`.
- Salida `-2` se convierte en salida positiva `2.00`; stock `7.00 -> 5.00`.
- Salida `1.50` se guarda como `1.00`; stock `5.00 -> 4.00` en vez de `3.50`.

Impacto:

El stock puede quedar incorrecto por litros/kilos/paquetes fraccionables, y los reportes/historial pueden llenarse de movimientos fantasma de `0.00`. Las cantidades negativas pueden cambiar stock en la direccion opuesta a lo esperado sin advertencia.

Datos creados/IDs:

- Producto `#43`.
- Movimientos `#1132`, `#1133`, `#1134`, `#1136`, `#1137`, `#1138`.

Fix aplicado:

- Entradas y salidas manuales ya usan normalizacion decimal con validacion estricta: numerico, mayor a cero, redondeado a 2 decimales.
- Se elimina la conversion `abs(intval(...))` que truncaba decimales y convertia negativos en positivos.
- `stock_actual`, `cantidad`, `stock_anterior` y `stock_posterior` se guardan con formato decimal `0.00`.
- Las vistas de entrada/salida usan `step="0.01"` y `parseFloat` para que la vista previa coincida con el backend.

Validacion del fix:

- Entrada `1.75`: stock `7.75 -> 9.50`; movimiento `#1142` queda `ENTRADA 1.75`.
- Entrada `0` y `-3`: redirigen a `/inventario/entrada`; no crean movimientos QA invalidos.
- Salida `1.50`: stock `9.50 -> 8.00`; movimiento `#1143` queda `SALIDA 1.50`.
- Salida `0` y `-2`: redirigen a `/inventario/salida`; no crean movimientos QA invalidos.
- Salida `99` sigue bloqueada por stock insuficiente y no crea movimiento.

### BUG-012 - Movimientos manuales de inventario no registran usuario

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Inventario / Auditoria de movimientos
URL: `/inventario/guardar`, `/inventario/procesarEntrada`, `/inventario/procesarSalida`

Pasos:
1. Crear producto con stock inicial `#44`.
2. Registrar entrada manual valida para `#43`.
3. Registrar salida manual valida para `#43`.
4. Revisar `movimientos_inventario.usuario_id`.

Resultado esperado:

Los movimientos manuales deben registrar el usuario autenticado (`user_id=1`) para trazabilidad operativa.

Resultado real:

- Movimiento inicial `#1141` queda `usuario_id=NULL`.
- Entrada manual `#1131` queda `usuario_id=NULL`.
- Salida manual `#1135` queda `usuario_id=NULL`.
- El ajuste manual `#1139` si registra `usuario_id=1`, por lo que la inconsistencia esta acotada a crear/entrada/salida.

Impacto:

No se puede auditar quien creo stock inicial ni quien hizo entradas/salidas manuales. Esto afecta cortes operativos, investigacion de diferencias y responsabilidad interna.

Fix aplicado:

- Stock inicial, entrada manual y salida manual ahora usan `usuarioIdActualInventario()`, que resuelve `user_id` o `usuario_id` de la sesion.
- Se mantiene el modelo de movimientos sin cambios; el controlador envia el usuario correcto en cada movimiento manual.

Validacion del fix:

- Producto `#45` creado con stock inicial `2.00`; movimiento `#1144` queda `usuario_id=1`.
- Entrada manual `0.50` en producto `#43`; movimiento `#1145` queda `usuario_id=1`.
- Salida manual `0.25` en producto `#43`; movimiento `#1146` queda `usuario_id=1`.

### BUG-013 - Campo descripcion de producto no persiste

Severidad: P3
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Inventario / Catalogo de productos
URL: `/inventario/nuevo`, `/inventario/editar/43`, `/inventario/editar/46`

Pasos:
1. Crear producto `QAINV0628A` enviando descripcion.
2. Editar el mismo producto enviando nueva descripcion.
3. Consultar `inventario_productos.descripcion`.

Resultado esperado:

La descripcion capturada en formulario debe persistirse.

Resultado real:

`inventario_productos.descripcion` queda `NULL` despues de crear y editar.

Fix aplicado:

- Se agrego `descripcion` a `$fillable` del modelo `Inventario`, ya que el controlador si enviaba el campo pero el modelo lo filtraba antes de persistir.

Validacion del fix:

- Crear producto `#46`, codigo `QAINV0628D`, con descripcion `QA BUG-013 descripcion creada` guarda el valor en `inventario_productos.descripcion`.
- Editar producto `#46` con descripcion `QA BUG-013 descripcion editada` actualiza correctamente la columna.

Impacto:

El usuario captura informacion que la app descarta silenciosamente. Puede perder instrucciones de uso, presentacion, proveedor sugerido o notas internas del producto.

### BUG-014 - Exportacion rapida de inventario usa fechas hardcodeadas de 2025

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Inventario / Exportar PDF
URL: `/inventario/exportar`

Pasos:
1. Abrir `/inventario/exportar`.
2. Revisar los botones rapidos `Stock actual` y `Movimientos de hoy`.
3. Generar PDF con rango correcto `2026-06-28` por POST directo.

Resultado esperado:

Los botones rapidos deben usar la fecha real del dia o el rango elegido por el usuario.

Resultado real:

- La vista muestra nota: `Los movimientos en el sistema estan registrados con fechas de 2025`.
- `getFechaActual()` retorna fijo `2025-09-13`.
- `exportarStockActual()` envia `2025-09-12` a `2025-09-13`.
- Con rango correcto `2026-06-28`, el PDF si se genera: `inventario_los_cedros_20260628_132417.pdf`, 13 KB.

Fix aplicado:

- `exportarAction()` envia a la vista `fecha_hoy` e `fecha_inicio_mes` con la fecha real del sistema.
- La vista usa esos valores como defaults del formulario y como constantes JS para `Stock actual` y `Movimientos de hoy`.
- Se elimino la nota visible que indicaba fechas de 2025.

Validacion del fix:

- `/inventario/exportar` renderiza `value="2026-06-01"` y `value="2026-06-28"`.
- El JS renderizado define `fechaHoyExportacion = "2026-06-28"` y `fechaInicioMesExportacion = "2026-06-01"`, sin `2025-09` en la vista servida.
- POST tipo `Movimientos de hoy` (`2026-06-28` a `2026-06-28`) genera PDF `inventario_los_cedros_20260628_142314.pdf`, 14 KB.
- POST tipo `Stock actual` (`2026-06-01` a `2026-06-28`) genera PDF `inventario_los_cedros_20260628_142609.pdf`, 17 KB.

Impacto:

El usuario puede generar reportes vacios o desactualizados desde los botones rapidos, aunque existan movimientos reales del dia.

### BUG-015 - Ajuste de stock muestra piezas y fuerza enteros para productos fraccionables

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Inventario / Ajuste
URL: `/inventario/ajuste/43`

Pasos:
1. Crear producto `#43` con unidad `litro`.
2. Abrir `/inventario/ajuste/43`.
3. Revisar campo de cantidad y unidad visible.
4. Enviar por POST directo ajuste decimal `1.25`.

Resultado esperado:

La vista debe mostrar la unidad real del producto y permitir decimales si el backend/tabla los soporta.

Resultado real:

- La vista muestra `pzs` aunque el producto es `litro`.
- El stock actual se muestra con `number_format(..., 0)`.
- El input tiene `step=1`.
- Backend si acepta `1.25` y crea movimiento `#1139`, stock `4.00 -> 5.25`.

Fix aplicado:

- La vista calcula la unidad real desde `unidad_medida`.
- El stock actual se muestra con dos decimales.
- El input `cantidad` usa `min=0.01`, `step=0.01` e `inputmode=decimal`.
- El preview usa `parseFloat` y muestra stock nuevo con `toFixed(2)` y unidad real.

Validacion del fix:

- `/inventario/ajuste/43` renderiza `8.25 litro`, sin `pzs` visible, y no tiene overflow horizontal en viewport movil `390px`.
- Preview entrada `1.25`: `Stock actual: 8.25 -> Stock nuevo: 9.50 litro`.
- Preview salida `1.25`: `Stock actual: 8.25 -> Stock nuevo: 7.00 litro`.
- POST entrada `0.25` crea movimiento `#1147`, stock `8.25 -> 8.50`, `usuario_id=1`.
- POST salida `0.25` crea movimiento `#1148`, stock `8.50 -> 8.25`, `usuario_id=1`.

Impacto:

El usuario puede quedar bloqueado o confundido al ajustar productos en litros/kilos. El backend permite precision, pero la UI empuja capturas enteras.

### BUG-016 - Documentos de tareas no se pueden vincular por incompatibilidad de enum

Severidad: P2
Estado: FIX VALIDADO
Hotel: Maximiliano
Usuario/Rol: `adminmax` / administrador
Modulo: Documentos / Tareas
URL: `/tareas/5`, `/documentos/subir?entidad_tipo=tarea&entidad_id=5`

Pasos:
1. Abrir tarea `#5` de Maximiliano.
2. Presionar `Vincular documento`.
3. Confirmar que el formulario queda con `entidad_tipo=tarea`, `entidad_id=5`.
4. Subir JPG valido con titulo `QA-DOC-20260628-TAREA-BUG`.

Resultado esperado:

El documento debe crearse y vincularse a la tarea `#5`, igual que ocurre con huespedes y cuentas por pagar.

Resultado real:

La UI muestra el formulario, pero el POST falla y redirige al mismo formulario con error:

- `SQLSTATE[01000]: Warning: 1265 Data truncated for column 'entidad_tipo' at row 1`.

Evidencia tecnica:

- `Documento::ENTIDAD_TIPOS` acepta `tarea`.
- La tarea `#5` muestra link a `documentos/subir?entidad_tipo=tarea&entidad_id=5`.
- La tabla `documento_entidades.entidad_tipo` tiene enum `proveedor`, `compra`, `cuenta_por_pagar`, `huesped`, `reservacion`, `trabajador`; no incluye `tarea`.
- Conteos despues del intento: `doc_tarea_after=0`, `link_tarea_after=0`.
- No quedaron archivos recientes huerfanos en `storage/documentos/hotel_4/2026/06`.

Impacto:

Los usuarios ven una accion disponible en tareas, pero no pueden adjuntar evidencia documental. Ademas, el error SQL aparece en la UI y se asigna al campo `Tipo de documento`, no al vinculo de entidad.

Validacion del fix:

- Con autorizacion explicita del usuario, se agrego y aplico la migracion `migrations/20260630_001_documento_entidades_tarea_enum.sql`.
- La migracion modifica solo `documento_entidades.entidad_tipo` para incluir `tarea`, valida el cambio y registra la migracion.
- Verificacion DB: `entidad_tipo` quedo como `enum('proveedor','compra','cuenta_por_pagar','huesped','reservacion','trabajador','tarea')`; migracion `#49`, batch `40`, estado `ejecutada`.
- Retest funcional con sesion temporal Maximiliano (`hotel_id=4`, usuario `adminmax`): `POST /documentos/subir` con `entidad_tipo=tarea`, `entidad_id=5`, archivo permitido y titulo `QA-DOC-20260630-TAREA-BUG016` responde `303` a `/documentos/22`.
- DB creo documento `#22`, `hotel_id=4`, `estado=activo`, `storage_path=documentos/hotel_4/2026/06/doc_20260629_184954_4a8e083554857542.png`.
- DB creo vinculo `documento_entidades`: `entidad_tipo=tarea`, `entidad_id=5`, `relacion=evidencia_qa`.
- `/tareas/5` contiene el titulo `QA-DOC-20260630-TAREA-BUG016`, confirmando que la ficha de tarea muestra el documento vinculado.
- `php -l` sin errores en `Documento.php`, `DocumentoController.php` y `TareaController.php`.

### BUG-017 - Reporte financiero por usuario lista y exporta usuarios de otros hoteles

Severidad: P1
Estado: FIX VALIDADO
Hotel: Los Cedros / Maximiliano
Usuario/Rol: `admin`, `adminmax`
Modulo: Reportes / Ingresos vs gastos / Reporte por usuario
URL: `/reportes/ingresos-gastos`, `/reportes/exportar-pdf?tipo=ingresos-gastos-usuario`

Pasos:
1. Abrir `/reportes/ingresos-gastos?fecha_inicio=2026-06-28&fecha_fin=2026-06-28` en Los Cedros.
2. Abrir el modal o inspeccionar el selector `usuario_id`.
3. Repetir en Maximiliano.
4. En Los Cedros, solicitar PDF por usuario con `usuario_id=22` (`Admin Demo SaaS`, hotel 2).

Resultado esperado:

El selector y la exportacion por usuario deben limitarse a usuarios vinculados al hotel activo.

Resultado real:

- Los Cedros y Maximiliano muestran usuarios activos globales: `Admin Demo SaaS`, `QA Admin Temporal 174223` y usuarios de otros hoteles.
- Los Cedros permite generar un PDF con `usuario_id=22`, queda link seguro `#18` con parametros `{"usuario_id":22,"usuario_nombre":"Admin Demo SaaS"}`.

Impacto:

Rompe aislamiento funcional multi-hotel en la UI de reportes y permite generar archivos administrativos de un usuario que no pertenece al hotel activo. Aunque los movimientos se filtran por `mc.hotel_id`, se expone identidad de usuarios externos y se registra un reporte incoherente en el hotel actual.

### BUG-018 - PDF principal de ingresos/gastos devuelve error TCPDF en vez de PDF

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reportes / Exportar PDF
URL: `/reportes/exportar-pdf?tipo=ingresos-gastos&fecha_inicio=2026-06-28&fecha_fin=2026-06-28`

Resultado esperado:

Debe descargarse un PDF `application/pdf` y registrarse un `reporte_links` de tipo `ingresos-gastos`.

Resultado real:

El endpoint responde `200 text/html`, 113 bytes, con:

- `TCPDF ERROR: TCPDF requires the Imagick or GD extension to handle PNG images with alpha channel.`

No se crea registro en `reporte_links`.

Impacto:

El boton/exportacion principal de ingresos vs gastos queda inutilizable para hoteles con logo PNG con transparencia; el usuario recibe un archivo HTML/error en lugar de PDF.

### BUG-019 - Exportaciones PDF de procedencia y habitaciones rentables terminan en 500

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reportes / Exportar PDF
URL: `/reportes/exportar-pdf?tipo=procedencia`, `/reportes/exportar-pdf?tipo=habitaciones-rentables`

Resultado esperado:

Si esos tipos estan expuestos en el controlador/vistas, deben generar PDF o no ofrecerse como exportables.

Resultado real:

Ambos endpoints responden `500`. Logs:

- `Metodo exportarHabitacionesRentablesPdfAction no encontrado en controlador ReportesController`.
- `Metodo exportarProcedenciaPdfAction no encontrado en controlador ReportesController`.

Impacto:

El usuario puede intentar exportar reportes disponibles y caer en error de servidor.

### BUG-020 - Reporte de ocupacion falla por consultas incompatibles con ONLY_FULL_GROUP_BY

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reportes / Ocupacion
URL: `/reportes/ocupacion?fecha_inicio=2026-06-01&fecha_fin=2026-06-28&tipo=diario|semanal|mensual`

Resultado esperado:

Los tres tipos de ocupacion deben cargar sin 500.

Resultado real:

`diario`, `semanal` y `mensual` devuelven `500`. Logs:

- `SQLSTATE[42000]: ... 1055 Expression ... is not in GROUP BY ... incompatible with sql_mode=only_full_group_by`.
- Fatal posterior: `Call to a member function fetchAll() on bool` en `Reporte.php`.

Impacto:

El reporte de ocupacion esta inutilizable; ademas el modelo no maneja fallas de query y provoca fatal error.

### BUG-021 - Rutas de estancia y ranking-estados apuntan a vistas inexistentes

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reportes / Estancia / Ranking estados
URL: `/reportes/estancia`, `/reportes/ranking-estados`

Resultado esperado:

Las rutas registradas deben renderizar vistas funcionales o no aparecer como reportes disponibles.

Resultado real:

Ambas rutas devuelven `500`. Logs:

- `Vista reportes/estancia no encontrada`.
- `Vista reportes/ranking-estados no encontrada`.

Impacto:

El modulo expone reportes historicos que no pueden abrirse.

### BUG-022 - La app bloquea zoom/pinch en mobile desde meta viewport y JavaScript

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros / layout global
Usuario/Rol: sin sesion y paginas autenticadas
Modulo: Responsive / Accesibilidad mobile / PWA
URL: `/h/los-cedros/login`, paginas autenticadas con layout hotelero

Pasos:
1. Abrir login en viewport movil `360x740` o `390x844`.
2. Revisar meta viewport.
3. Revisar layout autenticado servido por `src/app/views/layout/header.php`.
4. Revisar scripts cargados en paginas autenticadas.

Resultado esperado:

El usuario movil debe poder usar zoom/pinch para leer datos densos, tablas, reportes y formularios.

Resultado real:

- Login: `meta name="viewport"` contiene `maximum-scale=1.0, user-scalable=no`.
- Layout autenticado: `src/app/views/layout/header.php` usa el mismo bloqueo.
- Login bloquea gestos con `touchstart` y `gesturestart` usando `preventDefault()`.
- Paginas autenticadas cargan `pwa.js`, donde `lockMobileZoomGestures()` bloquea `gesturestart`, `gesturechange`, `gestureend`, `touchmove` con mas de un dedo y doble tap.

Impacto:

En telefonos, el usuario no puede ampliar pantallas densas como reportes, reservaciones, caja o documentos. Esto afecta accesibilidad y uso real en recepcion cuando se trabaja desde PWA o navegador movil.

Validacion del fix:

- Con autorizacion explicita del usuario, se retiro el bloqueo de zoom solo en `src/app/views/auth/login.php`, `src/app/views/layout/header.php`, `src/public_html/js/pwa.js` y `src/public_html/offline.html`.
- Login y layout autenticado ahora usan `meta viewport` sin `maximum-scale=1.0` ni `user-scalable=no`.
- Login ya no registra listeners `touchstart`/`gesturestart` para cancelar pinch.
- `pwa.js` ya no contiene ni ejecuta `lockMobileZoomGestures()`; el archivo servido reporto `HasZoomLock=false`.
- `offline.html` ya no contiene la copia del bloqueo por `gesturestart`, `touchmove` multi-touch ni doble toque.
- Retest Chrome mobile aislado en `390x844`: `/h/los-cedros/login` y `/offline.html` cargan con viewport accesible, sin listeners `gesture*`, sin listeners globales no pasivos de `touchmove/touchend` y sin overflow horizontal.
- Retest navegador autenticado en `/dashboard` `390x844`: viewport accesible, `scrollWidth=390`, `clientWidth=390`.
- Smoke `/api/sync` autenticado con sesion temporal Los Cedros: `HTTP/1.1 423 Locked`, `Content-Type: application/json; charset=utf-8`, body `success=false`, `error=sync_temporarily_disabled`, `pending_operations_preserved=true`.
- No se tocaron `service-worker.js`, `offline-data.js`, `reservaciones-offline.js`, IndexedDB, cache names ni la accion `/api/sync`.

### BUG-023 - Login tiene controles tactiles secundarios menores a tamano recomendado

Severidad: P3
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: sin sesion
Modulo: Auth / Login responsive
URL: `/h/los-cedros/login`

Pasos:
1. Abrir login en `360x740`, `390x844`, `768x1024` y desktop.
2. Medir controles visibles interactivos.

Resultado esperado:

Controles tactiles secundarios deben tener area de toque comoda, idealmente cercana a `44x44px` o con area clicable equivalente.

Resultado real previo:

En `360x740` y `390x844`:

- Boton `Mostrar contrasena`: aprox. `26x26`.
- Checkbox `Recordarme`: `18x18`.
- Link `Olvidaste tu contrasena`: ancho suficiente, pero alto visual aprox. `18px`.

Impacto:

No bloquea el inicio de sesion, pero aumenta errores de toque en telefono y empeora accesibilidad. Se agrava por BUG-022 porque el usuario tampoco puede hacer zoom para tocar con mas precision.

Validacion del fix:

- Cambio en `src/app/views/auth/login.php` limitado a CSS de targets tactiles del login.
- No se cambiaron `action`, `method`, `name`, CSRF, hidden inputs ni flujo de autenticacion.
- Retest aislado sin sesion en Chrome:
  - `360x740`: `bodyScrollWidth=360`, `docScrollWidth=360`; boton password `44x44`, `Recordarme` `312x44`, checkbox `22x22`, link recuperacion `163x44`.
  - `390x844`: `bodyScrollWidth=390`, `docScrollWidth=390`; boton password `44x44`, `Recordarme` `342x44`, checkbox `22x22`, link recuperacion `163x44`.
  - `768x1024`: `bodyScrollWidth=768`, `docScrollWidth=768`; boton password `44x44`, `Recordarme` `720x44`, checkbox `22x22`, link recuperacion `163x44`.
- Interaccion validada: click en boton de password cambia el campo de `password` a `text`.
- `php -l /var/www/html/app/views/auth/login.php` sin errores.

### BUG-024 - Dashboard movil recorta horarios/acciones de la agenda

Severidad: P2
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Dashboard / Responsive mobile
URL: `/dashboard`

Pasos:
1. Abrir `/dashboard` autenticado.
2. Probar viewport `360x740` y `390x844`.
3. Esperar estabilizacion de la pagina.
4. Medir `documentElement.scrollWidth` contra `clientWidth`.

Resultado esperado:

El dashboard movil debe mantener todo el contenido dentro del viewport o usar un contenedor con scroll intencional.

Resultado real:

- En `360px`, `clientWidth=360` y `scrollWidth=453`.
- En `390px`, `clientWidth=390` y `scrollWidth=453`.
- En la agenda, los horarios `.dm-ag .tm` quedan en `x=403/right=453`, fuera del viewport.
- Los chevrons de agenda tambien quedan fuera (`right=453`).
- Con nombres largos de huesped, el bloque de nombre/habitacion llega a `right=392` en viewport `360`.

Impacto:

El dashboard movil puede mostrar agenda cortada o generar desplazamiento horizontal. Recepcion pierde parte de la informacion rapida de llegadas/salidas desde telefono.

Validacion del fix:

- CSS local de dashboard ajusta `.dm-ag` para permitir encogimiento de nombre/habitacion y mantener hora/flecha dentro del renglon.
- Retest 360x740: `bodyScrollWidth=360`, `docScrollWidth=360`; `.dm-ag.is-link` `left=23/right=329`; `.dm-ag .tm` `left=268/right=321`.
- Retest 390x844: `bodyScrollWidth=390`, `docScrollWidth=390`; `.dm-ag .tm` `left=297/right=351`.
- Links secundarios y botones del dashboard miden `44px`/`45px` de alto.

### BUG-025 - Varias pantallas autenticadas tienen targets tactiles menores a 32px

Severidad: P3
Estado: FIX VALIDADO PARCIAL LOCAL
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Responsive / Accesibilidad tactil
URL: Varias autenticadas

Caso reproducido:

En la matriz responsive autenticada se detectaron controles visibles menores a un area tactil comoda:

- Inventario: acciones `.act-btn` de producto miden aprox. `28x38`.
- Habitaciones: filtros/controles moviles detectados con alto aprox. `14px`.
- Dashboard: links secundarios como `Ver todas` / `Ver agenda` miden aprox. `17-18px` de alto.
- Documentos: nombres/enlaces de documento aprox. `18px`; iconos de acciones en tablet/desktop aprox. `25x34`.

Impacto:

No bloquea el flujo, pero aumenta errores de toque en telefono/tablet y empeora accesibilidad. Se agrava por BUG-022, ya que el usuario tampoco puede hacer zoom.

Validacion del fix:

- CSS local de dashboard, documentos, inventario y habitaciones sube los targets operativos probados a 44px.
- Retest `/inventario` 390x844: `.btn-inv`, `.btn-config-inv` e `.inv-search` miden `44px` de alto.
- Retest `/habitaciones` 390x844: `.hb-filter-trigger`, `.hb-chip`, `.filter-date`, `.filter-btn` y `.btn-action` miden `44px` de alto.
- Retest `/documentos` 768x1024: `.dc-btn` y `.dc-card-btn` miden `44px` de alto.
- Login/auth queda cubierto por BUG-023, ahora con fix validado.

### BUG-026 - Centro documental en tablet recorta el hero por ancho interno mayor al viewport

Severidad: P3
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Documentos / Responsive tablet
URL: `/documentos`

Pasos:
1. Abrir `/documentos` autenticado.
2. Probar viewport `768x1024`.
3. Medir `.dc-hero-section`, `.dc-title-lockup` y `.main-content`.

Resultado esperado:

El hero del centro documental debe ajustarse al ancho disponible en tablet sin quedar recortado.

Resultado real:

- Viewport: `768px`.
- `.main-content`: `width=768`, `overflow-x=hidden`.
- `.dc-shell`: `width=728`.
- `.dc-hero-section`: `width=853`, `right=869`.
- `.dc-title-lockup`: `width=853`, `right=869`.

Como el contenedor principal oculta overflow horizontal, el contenido excedente del hero queda recortado en tablet.

Impacto:

El encabezado del centro documental puede verse cortado en tablets de 768px, afectando presentacion y lectura de contexto.

Validacion del fix:

- CSS local de documentos limita hero/title a `max-width:100%`, permite encogimiento con `min-width:0`, cambia filtros tablet a dos columnas y usa cards hasta `900px`.
- Retest 768x1024: `bodyScrollWidth=768`, `docScrollWidth=768`; `.dc-hero-section` y `.dc-title-lockup` quedan dentro de `left=16/right=744`.
- `.dc-filter-form` queda dentro de `left=33/right=727`; botones `Filtrar/Limpiar` miden `44px`.

### BUG-009 - Cambiar metodo de pago crea solicitudes de factura duplicadas

Severidad: P1
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reservaciones / Cambiar metodo de pago / Facturacion
URL: `/reservaciones/ver/57`, `/facturacion?buscar=57`

Pasos:
1. Crear reservacion `#57`, habitacion CORAL, total `$1,000.00`.
2. Hacer check-in con efectivo `$1,000.00` y `requiere_factura=no`.
3. Cambiar metodo de pago a tarjeta con `requiere_factura=no`.
4. Cambiar metodo de pago otra vez a transferencia con `requiere_factura=no`.

Resultado esperado:

Si ya existe una solicitud activa de facturacion para la reservacion, el sistema debe actualizar/reusar esa solicitud o impedir duplicados activos para el mismo concepto.

Resultado real:

- Paso 2: no crea solicitud, correcto.
- Paso 3: crea solicitud interna `#233`, correcto.
- Paso 4: crea otra solicitud interna `#234` para la misma reservacion, mismo monto y mismo concepto.
- DB queda con `#233` tipo `uso_interno`, metodo `tarjeta`, y `#234` tipo `uso_interno`, metodo `transferencia`, ambas ligadas a `reservacion_id=57`.

Impacto:

Facturacion puede trabajar dos veces la misma estancia, inflar pendientes y reportes, o generar confusion sobre cual solicitud representa el metodo vigente.

Datos creados/IDs:

- Reservacion `57`.
- Solicitudes `233` y `234`.
- Movimientos caja finales `#1543` y `#1544` durante cambios de metodo.

Fix aplicado:

- `Reservacion::crearSolicitudFactura()` ahora busca una solicitud activa (`pendiente` o `en_proceso`) para la misma reservacion, hotel, `requiere_factura` y tipo antes de insertar.
- Si existe, actualiza metodo, monto, usuario/notas y `updated_at` en vez de crear otra fila.
- El cambio de metodo de pago tambien ignora movimientos antiguos anteriores a `reservaciones.created_at` al decidir si puede reemplazar movimientos de caja.

Validacion del fix:

- En `#57`, antes del fix existian `#233` cancelada y `#234` pendiente.
- Nuevo cambio de metodo a tarjeta con referencia `QA-TARJ-57-FIX` devuelve `success=true`.
- No se crea solicitud `#235`; `#234` queda pendiente y actualizada a `metodo_pago_principal=tarjeta`.
- `reservacion_pagos` queda con pago `#45`, tarjeta `$1,000.00`; caja queda con movimiento `#1545`, tarjeta `$1,000.00`.

### BUG-010 - Registros antiguos de caja/facturacion se asocian a nuevas reservaciones por `reservacion_id`

Severidad: P1
Estado: FIX VALIDADO / DATOS HISTORICOS A AUDITAR
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Facturacion / Caja / Reservaciones
URL: `/facturacion/ver/232`, `/reservaciones/ver/53`

Pasos:
1. Crear reservacion QA nueva `#53`.
2. Hacer check-in y crear solicitud de factura `#232`.
3. Abrir `/facturacion/ver/232`.
4. Revisar pagos ligados a la reservacion.

Resultado esperado:

La reservacion nueva debe mostrar solo sus pagos reales y solicitudes generadas despues de su creacion.

Resultado real:

- La reservacion `#53` fue creada el `2026-06-28 14:59:30`.
- Su check-in real genero movimiento caja `#1541` por `$800.00`.
- El detalle de factura muestra pagos por `$2,100.00` porque tambien toma movimiento antiguo `#68` por `$1,300.00`, creado el `2026-03-02`, con el mismo `reservacion_id=53`.
- Tambien se detectaron asociaciones antiguas similares: movimiento `#66` contra `reservacion_id=50`, factura `#22` y movimiento `#80` contra `reservacion_id=51`, y movimientos futuros para IDs `55`, `56`, `58+`.

Impacto:

Las pantallas de facturacion/caja pueden mostrar pagos o facturas que no pertenecen a la reservacion actual. Esto contamina totales, pendientes, auditoria y puede ocultar saldos reales. El problema parece venir de datos historicos/huerfanos o importados sin integridad referencial suficiente.

Datos creados/IDs:

- Reservacion `53`.
- Solicitud `232`.
- Movimiento actual correcto `1541`.
- Movimiento antiguo asociado incorrectamente `68`.

Fix aplicado:

- Listado, detalle y estadisticas de facturacion ahora filtran `solicitudes_factura` con `sf.created_at >= r.created_at`.
- El detalle de factura ahora obtiene pagos de `movimientos_caja` uniendolos con `reservaciones` y filtrando `mc.created_at >= r.created_at`.
- `Reservacion::obtenerSolicitudFactura()`, solicitudes pendientes, cancelacion automatica y actualizacion de solicitud aplican el mismo filtro temporal.
- Exportaciones/ocupacion que marcan reservas con factura tambien ignoran solicitudes anteriores a la creacion de la reservacion.

Validacion del fix:

- `/facturacion/ver/232` ya no muestra `$2,100.00` ni `$1,300.00`; solo `$800.00`.
- Consulta equivalente de pagos validos para `reservacion_id=53` devuelve solo movimiento `#1541`.
- `/facturacion?buscar=51` ya no lista la factura vieja `#22`; la busqueda muestra estado vacio.
- `/facturacion/ver/22` cae al listado de facturacion y no expone el detalle antiguo como si fuera de `#51`.

### BUG-001 - Sesion/login general puede mostrar branding Medisoft con datos de Los Cedros

Severidad: P1
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Dashboard / Habitaciones / layout hotelero
URL: `/dashboard`, `/habitaciones`

Pasos:
1. Entrar con sesion previa/general que muestra datos de Los Cedros.
2. Abrir `/dashboard`.
3. Abrir `/habitaciones`.

Resultado esperado:

La UI operativa debe mostrar identidad/branding del hotel activo (`Los Cedros`) y no identidad Medisoft/SaaS.

Resultado real:

Los datos corresponden a Los Cedros (49 habitaciones), pero el encabezado, titulo y textos visibles muestran `Medisoft Hoteles`, incluyendo `Hotel activo: Medisoft Hoteles`.

Evidencia:

- DB: hotel `1` = `Los Cedros`, branding `nombre_visual = Los Cedros`.
- UI: `/dashboard` muestra `Hotel activo Medisoft Hoteles` y `Dashboard - Medisoft Hoteles`.
- UI: `/habitaciones` muestra `Habitaciones - Medisoft Hoteles`.
- Re-test scoped: al entrar por `/h/los-cedros/login`, `/dashboard` corrige a `Dashboard - Los Cedros`. El bug queda acotado a contexto/login general o sesion previa.

Datos creados/IDs:

No aplica.

Validacion del fix:

- `resolve_default_hotel_context_for_user(1, 'maximiliano')` devuelve hotel `1`, slug `los-cedros`; no acepta la cookie de Maximiliano porque `admin` no pertenece a ese hotel.
- Login general real: `POST /login/authenticate` con cookie `medisoft_last_hotel_slug=maximiliano` redirige a `/dashboard`.
- Dashboard posterior: titulo `Dashboard - Los Cedros`, `MEDISOFT_CONTEXT hotel_id=1`, `usuario_id=1` y `MEDISOFT_OFFLINE_BRANDING name=Los Cedros`.
- Para la prueba se uso password temporal local de `admin`; el hash original fue restaurado y `CodexTest12345` ya no valida despues del test.

### BUG-003 - Crear reservacion guarda en DB pero termina en 404

Severidad: P1
Estado: FIX VALIDADO EN CASO NUEVO `#51`
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reservaciones / Crear
URL: `/reservaciones/crear?huesped_id=1065`

Pasos:
1. Abrir crear reservacion con huesped QA `1065`.
2. Capturar fecha entrada `2026-06-27`, salida `2026-06-28`.
3. Seleccionar habitacion `AMBAR` (`habitacion_id=16`), total `$600`.
4. Presionar `Guardar Reservacion`.
5. Confirmar en el modal `Crear reservacion`.

Resultado esperado:

La reservacion debe crearse y redirigir a detalle de reservacion o listado con mensaje de exito.

Resultado real:

La reservacion se inserta en DB como ID `39`, estado `confirmada`, pero la UI termina en `404 - Pagina No Encontrada` en `/reservaciones/guardar`.

En repeticiones posteriores, las reservaciones `40` y `41` tambien se crearon en DB, pero la UI se quedo en el formulario de creacion sin redireccion clara a detalle/listado ni mensaje de exito confiable.

Impacto:

El usuario puede pensar que no se guardo e intentar crearla de nuevo, generando duplicados o confusion operativa.

Datos creados/IDs:

- Reservacion `39`.
- Reservacion `40`.
- Reservacion `41`.
- Reservacion `42`.
- Reservacion `43`.
- Reservacion `44`.
- Reservacion `48`.
- Reservacion `49`.
- Reservacion `50`.
- Reservacion `51` (validacion fix).
- Huesped `1065`.
- Habitacion `16` / AMBAR.

Validacion del fix:

- Cambio aplicado: el submit confirmado de `#formReservacion` envia el POST por AJAX con el mismo `action`, `method`, CSRF y campos; `guardarAction()` responde JSON con `redirect` cuando detecta AJAX.
- Reservacion nueva `#51`, AMBAR, 2026-08-07 a 2026-08-08, total `$600.00`.
- UI queda estable en `/reservaciones/ver/51` despues de esperar 10s.
- Logs: `POST /reservaciones/guardar` responde 200 JSON y luego `GET /reservaciones/ver/51` 200; ya no aparece `GET /reservaciones/guardar` 404 en el flujo validado.

### BUG-004 - Extender noches post-check-in registra ingreso en Caja pero no actualiza pagos/saldo

Severidad: P0
Estado: FIX VALIDADO EN CASO NUEVO `#46`
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reservaciones / Modificar dias / Caja / Pagos
URL: `/reservaciones/ver/40`

Pasos:
1. Crear reservacion QA `40` para huesped `1065`, habitacion MENTA (`habitacion_id=15`), del `2026-06-27` al `2026-06-28`, total `$600.00`.
2. Hacer check-in con pago exacto en efectivo `$600.00`.
3. Abrir `Modificar dias`.
4. Cambiar noches de `1` a `2`.
5. Confirmar la modificacion.

Resultado esperado:

El sistema debe cobrar o registrar claramente el ajuste de `$600.00` y dejar conciliados `precio_total`, `monto_recibido`, `reservacion_pagos`, UI de saldo, CxC y movimientos de Caja.

Resultado real:

- UI de reservacion: total `$1,200.00`, pagado `$600.00`, saldo `$600.00`.
- Encabezado sigue mostrando `Pagado - Efectivo`.
- Acciones rapidas no muestran una accion clara de `Cobrar saldo`; la seccion de pagos permite `Registrar anticipo`, lo que podria duplicar el cobro.
- Caja registra un ingreso adicional de `$600.00` como si ya se hubiera cobrado.
- `reservacion_pagos` conserva solo el pago inicial de `$600.00`.
- `reservaciones.monto_recibido` conserva `$600.00`.
- `cuentas_por_cobrar` no genera cuenta para el saldo restante.

Evidencia:

- `reservaciones #40`: estado `checked_in`, `fecha_salida=2026-06-29`, `precio_total=1200.00`, `monto_recibido=600.00`, `metodo_pago=efectivo`.
- `reservacion_pagos`: una fila, `$600.00`.
- `movimientos_caja #1528`: ingreso Hospedaje `$600.00`, `Hospedaje - Reservacion #40`.
- `movimientos_caja #1529`: ingreso Hospedaje `$600.00`, `Cobro adicional por extension de dias - Reservacion #40 (600 -> 1200)`, referencia `Ajuste por modificacion de dias`.
- `cuentas_por_cobrar`: 0 filas para reservacion `40`.

Impacto:

Inconsistencia financiera bloqueante: Caja queda con `$1,200.00` netos, pero la reservacion y el ledger de pagos indican solo `$600.00` recibido y saldo `$600.00`. El hotel puede cobrar dos veces al huesped o cerrar caja con saldos imposibles de reconciliar.

Validacion del fix:

- Reservacion nueva `#46`, ORO, check-in exacto `$800.00`.
- Extension 1 -> 2 noches: total/subtotal `$1,600.00`, pagado `$800.00`, saldo `$800.00`.
- Caja conserva un solo ingreso `#1533` por `$800.00`; no se crea cobro automatico por la extension.
- Reduccion posterior 2 -> 1 noches, sin sobrepago real: total/subtotal/pagado/caja neta `$800.00`; no se genera devolucion indebida.

Datos creados/IDs:

- Reservacion `40`.
- Reservacion `46`.
- Huesped `1065`.
- Habitacion `15` / MENTA.
- Habitacion `3` / ORO.
- Movimiento caja `#1528`.
- Movimiento caja `#1529`.
- Movimiento caja `#1533`.

### BUG-005 - Reducir noches post-check-in registra devolucion en Caja pero deja pagos y subtotal sobrecobrados

Severidad: P0
Estado: FIX VALIDADO EN CASO NUEVO `#47`
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reservaciones / Modificar dias / Caja / Pagos
URL: `/reservaciones/ver/41`

Pasos:
1. Crear reservacion QA `41` para huesped `1065`, habitacion UVA (`habitacion_id=14`), del `2026-06-27` al `2026-06-29`, total `$1,200.00`.
2. Hacer check-in con pago exacto en efectivo `$1,200.00`.
3. Abrir `Modificar dias`.
4. Cambiar noches de `2` a `1`.
5. Confirmar la modificacion.

Resultado esperado:

El sistema debe registrar la devolucion o ajuste y dejar conciliados total, pagos, subtotal de habitaciones, Caja, cambio/devolucion y UI de saldo.

Resultado real:

- UI principal muestra total `$600.00`, pagado `$1,200.00`, saldo `$0.00`.
- La tabla de habitaciones reservadas conserva subtotal `$1,200.00`.
- Caja registra un gasto/devolucion `$600.00`, por lo que el neto de Caja queda `$600.00`.
- `reservacion_pagos` conserva un pago por `$1,200.00`.
- `reservaciones.monto_recibido` conserva `$1,200.00`.
- `reservacion_habitaciones.precio` conserva `$1,200.00`.

Evidencia:

- `reservaciones #41`: estado `checked_in`, `fecha_salida=2026-06-28`, `precio_total=600.00`, `monto_recibido=1200.00`, `metodo_pago=efectivo`.
- `reservacion_habitaciones`: `precio=1200.00` para habitacion UVA.
- `reservacion_pagos #36`: `$1,200.00`.
- `movimientos_caja #1530`: ingreso Hospedaje `$1,200.00`, `Hospedaje - Reservacion #41`.
- `movimientos_caja #1531`: gasto Devoluciones `$600.00`, `Devolucion por reduccion de dias - Reservacion #41 (1200 -> 600)`, referencia `Ajuste por modificacion de dias`.

Impacto:

Inconsistencia financiera bloqueante: Caja neta queda correcta en `$600.00`, pero pagos, monto recibido y subtotal de habitacion siguen en `$1,200.00`. Reportes por pago/reservacion pueden sobrecontar, y la ficha queda contradictoria para recepcion y administracion.

Validacion del fix:

- Cambio aplicado: la devolucion se calcula contra el pagado real antes del cambio (`max(0, pagadoAntes - nuevoTotal)`), no contra la diferencia bruta de precio.
- Si hay devolucion real, se registra gasto en Caja y un ajuste negativo en `reservacion_pagos` para que el resumen quede neto.
- Reservacion nueva `#47`, AMARILLO, check-in exacto `$2,000.00`.
- Reduccion 2 -> 1 noches: total/subtotal `$1,000.00`, `reservacion_pagos` neto `$1,000.00`, `monto_recibido=1000.00`, caja neta `$1,000.00`.
- Movimientos caja: ingreso `#1534` por `$2,000.00`, devolucion `#1535` por `$1,000.00`.

Datos creados/IDs:

- Reservacion `41`.
- Reservacion `47`.
- Huesped `1065`.
- Habitacion `14` / UVA.
- Habitacion `4` / AMARILLO.
- Pago `reservacion_pagos #36`.
- Pagos `reservacion_pagos #38` y ajuste `#39`.
- Movimiento caja `#1530`.
- Movimiento caja `#1531`.
- Movimiento caja `#1534`.
- Movimiento caja `#1535`.

### BUG-006 - Modificar dias antes de check-in deja subtotal por habitacion desactualizado

Severidad: P1
Estado: FIX VALIDADO EN CASO NUEVO `#45` (datos historicos `#42`, `#43`, `#44` siguen como evidencia del bug original)
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reservaciones / Modificar dias / Detalle de habitaciones
URL: `/reservaciones/ver/42`, `/reservaciones/ver/43`

Pasos - extension:
1. Crear reservacion QA `42` para huesped `1065`, habitacion PURPURA (`habitacion_id=2`), del `2026-07-10` al `2026-07-11`, total `$600.00`, sin anticipo.
2. Abrir `Modificar dias`.
3. Cambiar noches de `1` a `2`.
4. Confirmar.

Pasos - reduccion:
1. Crear reservacion QA `43` para huesped `1065`, habitacion VIOLETA (`habitacion_id=7`), del `2026-07-15` al `2026-07-17`, total `$1,200.00`, sin anticipo.
2. Abrir `Modificar dias`.
3. Cambiar noches de `2` a `1`.
4. Confirmar.

Resultado esperado:

Como no hay check-in ni pagos, el sistema no debe crear movimientos de caja, pero si debe dejar conciliados `reservaciones.precio_total`, saldo visible y `reservacion_habitaciones.precio`.

Resultado real:

- Reservacion `42`: `precio_total=1200.00`, saldo `$1,200.00`, sin pagos/caja, pero `reservacion_habitaciones.precio=600.00` y la UI muestra subtotal PURPURA `$600.00`.
- Reservacion `43`: `precio_total=600.00`, saldo `$600.00`, sin pagos/caja, pero `reservacion_habitaciones.precio=1200.00` y la UI muestra subtotal VIOLETA `$1,200.00`.
- Reservacion `44`: `precio_total=1200.00`, abono/caja `$300.00`, saldo `$900.00`, pero `reservacion_habitaciones.precio=600.00` y la UI muestra subtotal LIMON `$600.00`.

Evidencia:

- DB `#42`: `fecha_salida=2026-07-12`, `precio_total=1200.00`, `monto_recibido=NULL`, `precio_habitacion=600.00`, pagos `0`, caja `0`.
- DB `#43`: `fecha_salida=2026-07-16`, `precio_total=600.00`, `monto_recibido=NULL`, `precio_habitacion=1200.00`, pagos `0`, caja `0`.
- DB `#44`: `fecha_salida=2026-07-22`, `precio_total=1200.00`, `monto_recibido=NULL`, `precio_habitacion=600.00`, abonos `$300.00`, caja neta `$300.00`.

Impacto:

Aunque caja/pagos no se corrompen, el detalle por habitacion queda persistido con el subtotal anterior. Esto puede afectar reportes por habitacion, tickets/cotizaciones, auditorias y lectura operativa de la ficha.

Validacion del fix:

- Cambio aplicado: `Reservacion::modificarFechaSalida()` ahora actualiza `reservacion_habitaciones.precio` usando el desglose de `calcularPrecioTotal()` dentro de una transaccion.
- Reservacion nueva `#45`, MOKA, sin pagos/caja.
- Extension 1 -> 2 noches: UI y DB quedan en `precio_total=2000.00` y `precio_habitacion=2000.00`.
- Reduccion 2 -> 1 noche: UI y DB quedan en `precio_total=1000.00` y `precio_habitacion=1000.00`.
- No se generaron movimientos de caja ni pagos/abonos.

Datos creados/IDs:

- Reservacion `42`.
- Reservacion `43`.
- Reservacion `44`.
- Reservacion `45`.
- Huesped `1065`.
- Habitacion `2` / PURPURA.
- Habitacion `7` / VIOLETA.
- Habitacion `8` / LIMON.
- Habitacion `1` / MOKA.

### BUG-007 - Check-out se puede habilitar aunque la ficha muestra saldo pendiente

Severidad: P1
Estado: FIX VALIDADO EN CASOS `#40` Y `#47`
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reservaciones / Check-out
URL: `/reservaciones/ver/40`

Pasos:
1. Abrir reservacion `40`, que muestra total `$1,200.00`, pagado `$600.00`, saldo `$600.00`.
2. Presionar `Registrar check-out`.
3. En el modal, seleccionar la habitacion MENTA.
4. Observar el estado del boton `Confirmar Check-out`.
5. Cancelar el modal sin confirmar.

Resultado esperado:

Si la ficha muestra saldo pendiente, el sistema debe bloquear el check-out o mostrar advertencia/cobro obligatorio antes de habilitar la confirmacion.

Resultado real:

El modal no muestra advertencia de saldo pendiente. Al seleccionar la habitacion, `Confirmar Check-out` queda habilitado aunque la ficha principal indica saldo `$600.00`.

Evidencia:

- UI `#40`: Total `$1,200.00`, Pagado `$600.00`, Saldo `$600.00`.
- Modal check-out: `Confirmar Check-out` cambia a habilitado tras `Seleccionar todas`.
- DB tras cancelar: reservacion `40` sigue `checked_in`, `precio_total=1200.00`, `monto_recibido=600.00`, `fecha_salida=2026-06-29`.

Impacto:

Riesgo alto de cerrar una estancia que el propio sistema presenta como pendiente de pago. Este bug agrava BUG-004 porque facilita completar el flujo sin resolver la inconsistencia de saldo.

Validacion del fix:

- Cambio aplicado: la vista oculta check-out cuando el resumen de pagos tiene saldo positivo; `Reservacion::checkOut()` y `checkOutParcial()` bloquean el cierre con saldo pendiente.
- Ruta normal y ruta rapida ahora validan saldo antes de ejecutar check-out; llaves/remotos solo se recogen si el check-out fue exitoso.
- Reservacion `#40`: total `$1,200.00`, pagado `$600.00`, saldo `$600.00`; no aparecen botones visibles `Registrar check-out` ni `Check-out`.
- Prueba backend controlada: `Reservacion::checkOut(40, '12:00:00')` devolvio `false`; DB quedo `estado=checked_in`, `hora_salida=NULL`, `monto_recibido=600.00`.
- Contraprueba `#47`: saldo `$0.00`; la UI sigue mostrando `Registrar check-out` y `Check-out`.

Datos creados/IDs:

- Reservacion `40`.
- Habitacion `15` / MENTA.

### BUG-008 - Detalle de reservacion genera warnings PHP por variables indefinidas

Severidad: P2
Estado: FIX VALIDADO EN CASO `#51`
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Reservaciones / Detalle
URL: `/reservaciones/ver/51`

Pasos:
1. Crear reservacion `51`.
2. Abrir `/reservaciones/ver/51`.
3. Revisar logs del contenedor app.

Resultado esperado:

La ficha debe cargar sin warnings PHP.

Resultado real:

La UI carga y no bloquea el flujo, pero logs muestran warnings en `app/views/reservaciones/ver.php`:

- `Undefined variable $remotos_info` en lineas `4534` y `4654`.
- `foreach() argument must be of type array|object, null given` asociado a `$remotos_info`.
- `Undefined variable $noches` en lineas `5815` y `5953`.

Impacto:

No rompe la navegacion en el caso probado, pero ensucia logs y puede ocultar errores reales. Si `display_errors` estuviera activo en otro entorno, podria mostrarse al usuario.

Datos creados/IDs:

- Reservacion `51`.
- Huesped `1065`.
- Habitacion `16` / AMBAR.

Validacion del fix:

- Cambio aplicado: la vista define defaults seguros para `$remotos_info` y usa `$noches = $rdNoches`.
- `php -l /var/www/html/app/views/reservaciones/ver.php` sin errores.
- Re-test `/reservaciones/ver/51`: titulo `Reservacion #51 - Los Cedros`, body sin warnings.
- Logs nuevos tras recargar la ficha no repiten warnings de `$remotos_info` ni `$noches`.

### BUG-002 - Logout redirige a login scoped de otro hotel

Severidad: P1
Estado: FIX VALIDADO
Hotel: Los Cedros
Usuario/Rol: `admin` / gerente
Modulo: Auth / Logout / contexto hotelero
URL: `/dashboard` -> `/logout`

Pasos:
1. Con sesion activa que muestra datos de Los Cedros, abrir `/dashboard`.
2. Abrir menu de usuario.
3. Presionar `Cerrar sesion`.

Resultado esperado:

Debe cerrar sesion y redirigir a login general o al login scoped del hotel activo correcto (`/h/los-cedros/login`).

Resultado real:

Cierra sesion, pero redirige a `/h/maximiliano/login` con titulo `Iniciar Sesion - Hotel Maximiliano`.

Evidencia:

- Antes del logout, `/dashboard` mostraba 49 habitaciones, conteo correspondiente a Los Cedros.
- Usuario visible: `Administrador Principal` (`admin`), vinculado a Los Cedros.
- Despues del logout, URL visible: `/h/maximiliano/login`.
- Ruta interna sin cookie sigue protegida: `GET /habitaciones` responde `303` a `/login`.

Datos creados/IDs:

No aplica.

Validacion del fix:

- Con sesion activa Los Cedros (`hotel_id=1`) y cookie previa `medisoft_last_hotel_slug=maximiliano`, `POST /logout` responde `HTTP/1.1 303 See Other`.
- `Location` devuelto: `http://localhost:8080/h/los-cedros/login`.
- La respuesta de logout no contiene `maximiliano` y limpia `PHPSESSID`.
