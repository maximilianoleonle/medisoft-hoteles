# Reporte de auditoría de lógica funcional - Medisoft Hoteles - 2026-07-01

Rama auditada: `feature/saas-multihotel` (working tree, incluye cambios sin commit)
Alcance: errores de lógica de negocio, chequeo rápido de sintaxis, buenas prácticas UI/UX.
Complementa a `reporte_seguridad_funcional_20260630.md` (auth/IDOR/CSRF ya cubiertos ahí).

## Resumen ejecutivo

- Sintaxis: **0 errores de parseo** en 523 archivos PHP (`php -l`). Solo deprecations de PHP 8.4 (nullable implícito) — no urgente.
- Lógica: **2 hallazgos críticos**, 1 alto, 4 medios. Todos en flujos de dinero o multi-hotel.
- UI/UX: lenguaje claro y buena degradación en los flujos nuevos; 4 observaciones menores.

| ID | Severidad | Módulo | Hallazgo |
|---|---|---|---|
| LOG-001 | CRÍTICO | Sync offline (PWA) | Todo `Sync.php` ignora `hotel_id`: dinero con hotel NULL y corte de otro hotel |
| LOG-002 | CRÍTICO | Anticipos + CxC | Revertir un anticipo NO revierte la liquidación de CxC que ese anticipo generó |
| LOG-003 | ALTO | Anticipos | `saldoPendiente()` de AnticipoService no cuenta cobros CxC → permite sobre-cobro |
| LOG-004 | MEDIO | Facturación | Ajuste de factura por reversión falla en silencio (sin advertencia al usuario) |
| LOG-005 | MEDIO | Facturación | Matching de solicitud por `LIKE '%Abono #5%'` colisiona con `Abono #52` |
| LOG-006 | MEDIO | Dashboard vs Caja | `efectivo_esperado` del dashboard excluye tipo `egreso`; Caja sí lo incluye |
| LOG-007 | MEDIO | Caja | Clasificación de reversos: resumen (PHP, solo categoría) vs listado (SQL, categoría+descripción) pueden diferir con datos legacy |
| LOG-008 | BAJO | Habitaciones | N+1: `resumenPagos()` por cada habitación ocupada (~5 queries c/u, incluye SHOW TABLES) |
| UX-001 | BAJO | Reservación | `tipoTarjetaReservacion` se parsea de notas de factura con regex y se aplica a TODOS los pagos con tarjeta |
| UX-002 | BAJO | Global | `font-weight: 800/900` en 8 vistas nuevas — contra guía propia (máx 700) |
| UX-003 | BAJO | Reporte imprimible | Bloqueo de zoom en el reporte de reservaciones con fuente 7.4pt |
| UX-004 | PREGUNTA | Check-in | No se permite dejar el 100% del saldo pendiente (exige pago parcial) — ¿intencional? |

## Hallazgos de lógica

### LOG-001 - Sync offline no es multi-hotel (CRÍTICO)

**Estado: CORREGIDO** (2026-07-01). `Sync.php` ahora resuelve el hotel de la sesión en `procesarLote()` (rechaza el lote completo si no puede determinarlo) y las 6 operaciones filtran/insertan `hotel_id`. Nota: el endpoint `/api/sync` sigue deshabilitado (HTTP 423) en `ApiController::syncAction`; al reactivarlo, pasar el hotel explícito: `procesarLote($ops, user_id(), $this->hotelIdActual())`.

Archivos: `src/app/models/Sync.php` (todo el archivo; INSERT en :432-451, corte en :406-408)

El archivo completo no contiene ni una referencia a `hotel_id`. Consecuencias concretas:

1. `registrarMovimientoCaja()` inserta en `movimientos_caja` **sin la columna `hotel_id`** (es `INT NULL`, sin default). El movimiento queda con `hotel_id = NULL` y desaparece de TODAS las consultas (resumen de caja, cortes, reportes, dashboard) porque todas filtran `hotel_id = ?`. Dinero cobrado offline = dinero invisible.
2. El corte se busca con `SELECT id FROM cortes_caja WHERE estado = 'abierto' ... LIMIT 1` **sin filtrar hotel**: un cobro sincronizado del hotel A puede colgarse del corte abierto más reciente del hotel B.
3. Aplica a las 6 operaciones offline: `crear_reservacion`, `cambiar_estado_habitacion`, `checkin`, `checkout`, `pago_caja`, `gasto_caja`.
4. Además, la migración `20260526_009` aborta si detecta `movimientos_caja.hotel_id IS NULL`, así que estas filas romperían migraciones futuras.

Recomendación: derivar `hotel_id` de la sesión del usuario que sincroniza (igual que `obtenerHotelIdActualCompat()`), pasarlo a todas las operaciones, filtrar el corte por hotel y validar que las entidades del payload (reservación, habitación) pertenezcan a ese hotel.

### LOG-002 - Reversión de anticipo no revierte la sincronización CxC (CRÍTICO)

**Estado: CORREGIDO** (2026-07-02). Nuevo `CuentaPorCobrar::revertirSincronizacionPagoReservacion()`: busca los AJUSTE con la referencia exacta `RES-ABONO-{res}-{abono}`, descuenta reversiones previas (`REV-...`, idempotente), restaura saldo/estado con AJUSTE inverso auditado y tope en el total de la cuenta. `revertirAnticipoAction` lo invoca tras revertir el anticipo y avisa al usuario ("Saldo restaurado en Cuentas por cobrar: $X" o advertencia si falló).

Archivos: `src/app/controllers/ReservacionController.php:3894-3911` (reversar), `:3830-3843` (sync al registrar), `src/app/models/CuentaPorCobrar.php:562` (sincronizarPagoReservacion), `src/app/services/AnticipoService.php:206-286`

Flujo con fuga:

1. Reservación con cuenta por cobrar real abierta (saldo $500).
2. Se registra un anticipo/pago pendiente de $500 → `sincronizarPagoReservacion()` aplica AJUSTE: CxC saldo 500→0, estado `liquidada`. Correcto.
3. El recepcionista se equivocó y **revierte** el anticipo → `AnticipoService::reversar()` borra el abono y crea el gasto en Caja, pero **nadie restaura la CxC**: queda `liquidada` con saldo 0.
4. Resultado: la ficha de la reservación vuelve a mostrar saldo pendiente, pero el módulo CxC dice que la deuda está pagada. El hotel pierde el rastro de la deuda.

Recomendación: en `reversarAnticipoAction` (o dentro de un servicio), revertir los movimientos AJUSTE creados con la referencia `RES-ABONO-{reservacionId}-{abonoId}` (restaurar saldo/estado y registrar movimiento AJUSTE inverso con nota).

### LOG-003 - Dos definiciones de "saldo pendiente" (ALTO)

**Estado: CORREGIDO** (2026-07-02). `AnticipoService::saldoPendiente()` ahora suma también los cobros CxC (COBRO - CANCELACION, mismo query que `Reservacion::resumenPagos()`, clamp a >= 0 y tolerante a tablas ausentes). Con esto `evaluar()` pinta el mismo saldo que la ficha y `registrar()` ya no permite cobrar de más.

Archivos: `src/app/services/AnticipoService.php:304-320` vs `src/app/models/Reservacion.php:745-820`

`Reservacion::resumenPagos()` ahora suma `pagos + abonos + cobros CxC (COBRO - CANCELACION)`. Pero `AnticipoService::saldoPendiente()` (que valida el tope del anticipo y alimenta `evaluar()` para pintar la UI) solo suma `pagos + abonos`.

Escenario: reservación de $1,000; se cobran $400 vía módulo CxC (movimiento COBRO + ingreso en Caja). La ficha muestra saldo $600, pero el panel de anticipo permite y valida cobrar hasta $1,000 → sobre-cobro de $400 posible, y dos "saldos" distintos visibles en la misma pantalla.

Recomendación: hacer que `AnticipoService::saldoPendiente()` reuse `Reservacion::resumenPagos()` (o replique la suma de cobros CxC).

### LOG-004 - Ajuste de factura por reversión falla en silencio (MEDIO)

**Estado: CORREGIDO** (2026-07-02). Los tres callers ahora revisan el retorno de `crearSolicitudFactura()` (false = no se aplicó) y muestran advertencia: `revertirAnticipoAction`, `sincronizarFacturaPorCobroCxc` y `sincronizarFacturaPorReversionCxc`. Además el ajuste de factura en `revertirAnticipoAction` quedó aislado en su propio try/catch: antes, una excepción ahí mostraba "No se pudo revertir el anticipo" aunque la reversión SÍ se había ejecutado.

Archivos: `src/app/models/Reservacion.php:3355-3363` (crearSolicitudFactura, rama crear con monto ≤ 0), `src/app/controllers/ReservacionController.php:3897-3910`, `src/app/controllers/CuentaPorCobrarController.php:376-424`

Al revertir un anticipo o un cobro CxC se llama `crearSolicitudFactura()` con monto **negativo** y `modo_monto=acumular`. Si NO se encuentra la solicitud existente (p. ej. ya pasó a `facturada`, o la `referencia_nota` no coincide), cae a la rama de creación, donde `monto_total <= 0.004` → `return false` **sin excepción**. El controlador solo muestra advertencia cuando hay excepción, así que el usuario ve "revertido correctamente" y la solicitud de factura queda con el monto viejo, sin aviso.

Recomendación: distinguir "no encontré qué ajustar" de "ok" en el retorno y mostrar la advertencia (` La factura vinculada no se ajustó...`) también en ese caso.

### LOG-005 - Matching de referencia por LIKE sin delimitador (MEDIO)

**Estado: CORREGIDO** (2026-07-02). `crearSolicitudFactura()` cambia `LIKE '%ref%'` por `REGEXP 'ref([^0-9]|$)'` (con escape de metacaracteres): la referencia debe terminar en no-dígito o fin de nota, así 'Abono #5' ya no matchea 'Abono #52'. Funciona también con notas legacy (no cambió el formato de las notas).

Archivos: `src/app/models/Reservacion.php:3300-3303` (`sf.notas LIKE ?` con `'%' . referencia_nota . '%'`)

`referencia_nota = 'Abono #5'` también matchea notas que contienen `Abono #52` / `Abono #513`. Igual con `'Movimiento CxC #12'` vs `#123`. En reservaciones con varias solicitudes separadas, la reversión puede descontar el monto de la solicitud equivocada.

Recomendación: delimitar la referencia al generarla (p. ej. `[Abono#5]`) o comparar contra un patrón con frontera (`LIKE '%Abono #5 %'` + variante fin de cadena), idealmente guardar la referencia en columna propia.

### LOG-006 - `efectivo_esperado` del dashboard vs `efectivo_en_caja` de Caja (MEDIO)

**Estado: CORREGIDO** (2026-07-02). `DashboardController::getCajaInfo()` ahora usa `tipo IN ('gasto','egreso')` en `total_gastos` y `efectivo_esperado`, igual que `Caja::obtenerResumenCaja()`. Durante el fix se encontró el mismo patrón en `ArqueoMetodosPago` (conciliación): sus recálculos `mov_gastos_*` y `gastos_total` usaban solo `'gasto'` mientras los totales guardados al cierre incluyen egresos → habría marcado falsas diferencias; también corregido (3 queries).

Archivos: `src/app/controllers/DashboardController.php:589-592` vs `src/app/models/Caja.php:368-371`

En `getCajaInfo()` el efectivo esperado resta solo `tipo = 'gasto'`; `Caja::obtenerResumenCaja()` resta `gasto` + `egreso`. El mismo query del dashboard ya reconoce `('gasto','egreso')` para `total_gastos_reales`, pero no para `efectivo_esperado` ni `total_gastos`. Si existen movimientos tipo `egreso` en efectivo dentro del corte, dashboard y pantalla de Caja muestran efectivo distinto → confusión al hacer el corte.

Recomendación: unificar a `tipo IN ('gasto','egreso')` en `efectivo_esperado` y `total_gastos` del dashboard.

### LOG-007 - Clasificación de reversos: PHP vs SQL (MEDIO)

**Estado: CORREGIDO** (2026-07-02). El query del resumen de `Caja::obtenerResumenCaja()` ahora calcula `es_reverso` en SQL con `condicionReversoIngresoSql('')` (la misma condición del listado por categorías, incluye descripción) y agrupa por esa marca; el loop PHP solo lee la bandera. Se eliminó el helper `categoriaEsReversoIngreso()` que duplicaba la regla solo por categoría (quedó sin usos).

Archivos: `src/app/models/Caja.php:242-258` (categoriaEsReversoIngreso, solo categoría) vs `:260-278` (condicionReversoIngresoSql, categoría + descripción)

El resumen de caja clasifica reverso/gasto real en PHP mirando **solo la categoría**; el listado por categorías y todos los reportes usan la condición SQL que además mira la **descripción** (`LIKE 'reverso de anticipo%'`). Los movimientos nuevos escriben categorías canónicas ('Reverso anticipo', 'Reversion Cobro CxC') y coinciden en ambas; pero movimientos legacy con categoría genérica y descripción "Reverso de anticipo..." aparecerán como reverso en el listado y como gasto real en las tarjetas del resumen → totales que no cuadran a la vista.

Recomendación: calcular el resumen con la misma condición SQL (agregar la clasificación en el SELECT del resumen) en lugar de duplicar la regla en PHP.

### LOG-008 - N+1 en el grid de habitaciones (BAJO)

**Estado: CORREGIDO** (2026-07-02). Nuevo `Reservacion::resumenPagosLote()`: mapa `reservacion_id => resumen` con una consulta agregada por fuente (reservaciones, pagos, abonos, cobros CxC = 4 queries + 3 SHOW TABLES fijos, en vez de ~5 por habitación ocupada). `HabitacionController::indexAction` junta las reservaciones ocupadas en el loop y anota saldo/pagado/total en una segunda pasada. Mismo criterio de cálculo y mismos defaults defensivos (0.0 si falla).

Archivo: `src/app/controllers/HabitacionController.php:220-239`

Por cada habitación ocupada se llama `resumenPagos()`, que ejecuta 2× `SHOW TABLES` + 3 SUM. Con 40 habitaciones ocupadas son ~200 queries extra en la vista más usada del sistema.

Recomendación: una sola consulta agregada por lote de reservaciones ocupadas (mismo patrón que `aplicarCobrosOperativosReservacion`), o cachear la existencia de tablas.

## Observaciones UI/UX

Lo bueno (mantener): lenguaje claro para no contadores ("Dinero que entró", "Devuelto/cancelado", "Cancelación de anticipo", "cuenta pendiente" en vez de CxC); mensajes de bloqueo accionables ("Este cobro ya fue revertido. Si registras otro cobro..."); radios crédito/débito con `required` dinámico y validación server-side de respaldo; checkbox de saldo pendiente con labels que cambian según el modo; `font-size:16px` en inputs móviles para evitar el auto-zoom de iOS.

### UX-001 - Tipo de tarjeta inferido de notas de factura

**Estado: CORREGIDO** (2026-07-02). Migración `20260702_001_reservacion_pagos_tipo_tarjeta.sql` agrega `reservacion_pagos.tipo_tarjeta` (mismo ENUM que abonos; NULL = pago legacy). `registrarPagosMixtos()` y el flujo "cambiar método de pago" guardan el tipo (defensivo: solo si la columna existe); `checkInAction` lo toma del radio `tipo_tarjeta` que el modal ya postea. La vista ya leía `$payment['tipo_tarjeta']` con fallback al inferido para datos legacy — sin cambios. Extra: se eliminó `intentarRegistrarPagosMixtos()` (código muerto sin callers que además insertaba pagos SIN hotel_id).

`ReservacionController.php:2978-2996`: se toma la última solicitud de factura con método tarjeta, se parsea "Tarjeta de crédito/débito" de las notas con regex y se aplica a TODOS los pagos con tarjeta sin tipo. Si el huésped pagó una vez con crédito y otra con débito, se etiquetan igual. Solo display, pero puede confundir en aclaraciones bancarias. Mejor: columna `tipo_tarjeta` en `reservacion_pagos` (ya existe en abonos).

### UX-002 - font-weight 800/900 en vistas nuevas

**Estado: CORREGIDO (alcance rama)** (2026-07-02). Las 22 ocurrencias agregadas por esta rama (+1 de `font-weight: 850` encontrada al revisar) bajaron a 700. OJO: el barrido reveló ~570 ocurrencias PREEXISTENTES en ~70 vistas — eso es deuda de tipografía global que pertenece a la microfase de Fase 10 (junto con la unificación de paleta), no a esta rama.

Contra la guía propia del proyecto (máx 700 en sans). Archivos con adiciones: `caja/index.php` (3), `tareas/form.php` (6), `tareas/index.php` (3), `reservaciones/ver.php` (3), `cuentas_por_cobrar/ver_operativa.php` (2), `tareas/agenda.php` (2), `tareas/ver.php` (2), `tareas/reporte.php` (1).

### UX-003 - Bloqueo de zoom en el reporte imprimible

**Estado: CORREGIDO** (2026-07-02). El reporte imprimible de reservaciones (`generarHTMLReservacionesPersonalizado`) recuperó el zoom libre: viewport sin `user-scalable=no`, sin `touch-action: pan-x pan-y` y sin el script de bloqueo de gestos. El bloqueo se conserva deliberadamente en el shell de la PWA (header, login, errores, offline) como comportamiento de app.

El bloqueo global de pinch-zoom (`user-scalable=no` + listeners `gesturestart/touchmove`) es un patrón deliberado de PWA, aceptable en pantallas de app. Pero se agregó también al reporte imprimible de reservaciones (`ReservacionController::generarHTMLReservacionesPersonalizado`), que usa fuente de **7.4pt**: en móvil ese documento se vuelve ilegible sin zoom. Recomendación: excluir los HTML de reporte/impresión del bloqueo (WCAG 1.4.4).

### UX-004 - (Pregunta de producto) Check-in sin pago alguno

`ReservacionController.php:3494-3506`: con "dejar saldo pendiente" activo, si no se registra ningún pago se lanza "Para dejar saldo pendiente registra al menos un pago parcial". Un huésped corporativo/a crédito no puede hacer check-in con $0 cobrado. Si es política deliberada, OK; si no, permitir $0 con permiso específico.

## Sintaxis

- `php -l` sobre 523 archivos: sin errores.
- Deprecations PHP 8.4 (parámetro nullable implícito) en `branding.php`, `hotel_config.php`, `HotelBranding.php`, `ReportesController.php`, `ReportePDF.php` — cosmético, corregir con `?Type` cuando se toquen esos archivos.
- Código muerto: `Reporte::obtenerIngresosGastos()` hace `return` en la primera línea y deja ~40 líneas inalcanzables; la variable `$sqlIngresosNeto` en `getIngresosVsGastos()` en realidad calcula ingresos brutos (renombrar).

## Orden recomendado

1. LOG-001 (Sync multi-hotel) — bloqueante para operar la PWA offline con más de un hotel.
2. LOG-002 + LOG-003 (consistencia anticipos/CxC) — juntos, son el mismo dominio.
3. LOG-006 (efectivo esperado) — arreglo de una línea, evita sustos en cortes.
4. LOG-004 + LOG-005 (facturación) — antes de que facturación tenga volumen.
5. LOG-007, LOG-008 y UX-001..003 — en la siguiente pasada de pulido.
