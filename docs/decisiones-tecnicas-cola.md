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

- El bloque 2X-3B quedo en estado `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`; Fase 3C quedo validada manualmente despues.
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

## Fase 3C-B

- La generacion queda como accion manual en CxP, no como efecto secundario de `CompraService::recibirCompra()`.
- La unica ruta POST nueva es `POST /cuentas-por-pagar/generar-desde-compra/{id}`.
- La vista del preview muestra el boton solo cuando la compra es elegible.
- El modelo centraliza validaciones y usa transaccion propia.
- La compra se bloquea con `FOR UPDATE` antes de validar estado, proveedor, total y duplicado.
- El duplicado se evita consultando `cuentas_por_pagar` por `(hotel_id, compra_id)` dentro de la transaccion.
- La CxP nace en estado `pendiente`, con `saldo = total` y sin fecha de vencimiento automatica.
- No se inserta en `cuentas_por_pagar_movimientos` en 3C-B para no introducir tipos operativos de pago/abono.
- La trazabilidad de creacion se registra en `logs_auditoria` mediante `AuditService`.
- Caja sigue completamente fuera del flujo.

## Revision tecnica Fase 3C

- La QA manual del bloque 3C fue reportada como completada por el usuario.
- El preview debe renderizar link a proveedor solo cuando el proveedor fue resuelto por el join scoped al `hotel_id` de la compra.
- Si una compra conserva `proveedor_id` pero el proveedor no existe en el hotel actual, la fila queda bloqueada y no debe enlazar a otro proveedor.
- No se agrega indice/migracion en esta revision; la prevencion de duplicados sigue basada en bloqueo transaccional de la compra con `FOR UPDATE` y verificacion de CxP existente.
- No se autoriza pago, abono, Caja ni Fase 3D.

## Auditoria seguridad Fase 3C

- La auditoria post-QA queda documentada como revision estatica sin cambios funcionales.
- No se agrega migracion ni indice en esta auditoria.
- Los riesgos de concurrencia se mantienen como residuales documentados; cualquier endurecimiento con indice unico requerira migracion futura autorizada.
- La ausencia de pagos/Caja sigue siendo una regla de fase, no solo una decision visual.
- Health/preflights deben re-ejecutarse cuando Docker/PHP esten disponibles.

## Bloque Personal y Nomina (Fase NP)

### Decisiones de diagnostico NP-0

- Hoy "trabajador" = `usuarios` (tabla global, sin `hotel_id`, `rol` de sistema) + pivote
  `hotel_usuarios`. No hay rol laboral, deuda ni saldo por persona.
- El trabajador NP es una **entidad nueva e independiente**: no requiere usuario del sistema
  ni login. El vinculo a un usuario es opcional via `trabajadores.usuario_id` con
  `ON DELETE SET NULL`; jamas se altera `usuarios` de forma destructiva.
- No se convierte ni fusiona ningun `usuario` existente en trabajador.

### Decisiones de diseno de tablas NP

- 6 tablas aditivas, todas con `hotel_id NOT NULL` (FK `hoteles` RESTRICT) y, las hijas,
  `trabajador_id` (FK `trabajadores` RESTRICT). Baja logica via `estado`, nunca borrado fisico.
- El ledger laboral se reparte en `trabajador_pagos` (pago/comision/bono/descuento/ajuste
  con `efecto` a_favor/en_contra), `trabajador_anticipos` y `trabajador_prestamos`
  (ambos con `saldo_pendiente`).
- Un `tipo='pago'` en `trabajador_pagos` es una liquidacion laboral entregada; es un
  REGISTRO LABORAL, NO un movimiento de Caja.
- El saldo por trabajador es DERIVADO del ledger (no editable manualmente). Formula
  documentada en `docs/fase_NP_0_contrato_diagnostico.md`.
- Asistencia: `UNIQUE (hotel_id, trabajador_id, fecha)` para evitar duplicados por dia.
- Documentos: ruta de archivo siguiendo el patron de uploads del proyecto; baja logica.

### Decisiones sobre Caja y nomina

- NO se crea categoria "Nomina" en `categorias_movimientos` en este bloque.
- NO se inserta en `movimientos_caja` por nomina. La integracion Caja se difiere a una
  Fase NP-Caja autorizada por separado.

### Decision sobre "responsable" de mantenimiento

- En NP la referencia trabajador-responsable es **logica y opcional**, de solo lectura,
  basada en el campo de texto libre `mantenimientos_habitaciones.realizado_por`.
- NO se altera `mantenimientos_habitaciones`. Una columna FK real se difiere a una
  migracion aditiva posterior autorizada.

### Decisiones de seguridad/operacion NP

- Auditoria con `AuditService::record()` si la tabla `logs_auditoria` esta disponible.
- Escrituras explicitas (POST + CSRF + transaccion cuando el patron lo permita).
- `/api/sync` fuera de alcance.
