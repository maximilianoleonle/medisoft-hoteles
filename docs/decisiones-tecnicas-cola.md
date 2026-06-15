# Decisiones tecnicas - cola autonoma

## Base de datos

- La base local principal sigue siendo `medisoft_hoteles_import`.
- La migracion de Fase 3B fue aplicada localmente con backup previo.
- Las tablas de CxP quedan vacias hasta que exista una fase autorizada para generacion de saldos.

## Cuentas por pagar

- `cuentas_por_pagar` y `cuentas_por_pagar_movimientos` son fuente fundacional nueva.
- El codigo de Fase 3B solo usa operaciones de lectura.
- No se agregan formularios POST.
- No se agregan acciones de pago.
- No se integra Caja.
- No se genera CxP automaticamente desde compras.
- La navegacion se agrega bajo el mismo alcance funcional que inventario, sin crear permisos profundos nuevos.
- Riesgo residual documentado: la estructura permite referencias a proveedor/compra por ID; cualquier escritura futura debe comprobar `hotel_id` de proveedor y compra antes de insertar.

## Compras y proveedores

- Compras recibidas y proveedores pueden enlazarse desde CxP solo en modo lectura.
- Las fases anteriores de compras/proveedores permanecen read-only salvo el flujo minimo de recepcion ya autorizado.
- La recepcion de compra debe seguir protegida contra doble recepcion.

## Inventario

- `inventario_productos` y `movimientos_inventario` siguen siendo tablas modernas.
- `productos`, `inventario_movimientos` e `inventario_habitacion_config` permanecen como legacy congelado.
- No se fusionan ni eliminan tablas duplicadas en este cierre.

## Sync y PWA

- `/api/sync` no se toca.
- Debe seguir bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.
- No se modifican service workers, IndexedDB, cache names ni archivos offline.

## Git

- `src/app/views/reservaciones/ver.php` se considera cambio no relacionado con Fase 3B y queda fuera del commit.
- El commit de cierre debe ser selectivo e incluir solo CxP, checkers, docs y rutas/navegacion relacionadas.
- El commit de cierre Fase 3B fue `1fa1653`.
- Cualquier decision sobre `src/app/views/reservaciones/ver.php` debe hacerse en commit separado tras QA visual.

## Cierre tecnico post-commit

- El bloque autorizado queda en estado `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`.
- No se avanza a Fase 3C sin nuevo mensaje real.
- No se implementan pagos, Caja, CxC, nomina ni permisos profundos.
- La documentacion final puede seguir ajustandose sin cambiar comportamiento funcional.

## Auditoria de seguridad post-cierre

- Sin rutas sensibles nuevas sin `requireAuth`.
- Sin POST operativo en CxP.
- Sin tokens de escritura CxP en controlador/modelo/vistas CxP.
- Sin integracion accidental con Caja.
- `/api/sync` sigue registrado y bloqueado.
- Las tablas legacy de inventario siguen congeladas; el bloque de compras usa `inventario_productos` y `movimientos_inventario`.

## Fase 3C

- Nuevo mensaje real autoriza Fase 3C.
- La generacion CxP sera manual, nunca automatica al recibir compra.
- Fase 3C-A debe ser simulador read-only antes de cualquier escritura.
- Fase 3C-B podra escribir `cuentas_por_pagar`, pero no Caja ni pagos.
- Toda CxP nacida de compra debe validar compra recibida, proveedor del mismo hotel, `total > 0` y no duplicado `(hotel_id, compra_id)`.
- La auditoria debera usar `AuditService` si el patron sigue disponible.
- `cuentas_por_pagar_movimientos` solo podra usarse como trazabilidad interna de CREACION si se mantiene sin pagos ni Caja.
- `/api/sync` sigue fuera de alcance.

## Fase 3C-A

- El simulador se ubica dentro de CxP, no dentro de recepcion de compras, para evitar que recibir una compra sugiera generacion automatica.
- La ruta nueva es solo `GET /cuentas-por-pagar/generacion-preview`.
- Se reutilizan los guards existentes de CxP: autenticacion, contexto hotelero y modulo `inventario`.
- El modelo usa solo consultas `SELECT` sobre `compras`, `compra_detalles`, `proveedores`, `hoteles` y `cuentas_por_pagar`.
- La elegibilidad se evalua por compra recibida, proveedor del mismo hotel, total positivo, detalles vinculados y ausencia de CxP previa.
- Una compra bloqueada muestra motivo explicito en la vista.
- La vista solo enlaza a recursos read-only ya existentes: detalle de compra, proveedor y CxP.
- No se agregan formularios POST ni botones de generacion en 3C-A.
