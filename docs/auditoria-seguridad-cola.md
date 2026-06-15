# Auditoria de seguridad - cola autonoma

## Alcance

Auditoria post-commit del bloque autorizado hasta Fase 3C:

- Fase 2X: detalle read-only de compra recibida.
- Fase 2Y: reportes read-only de compras recibidas.
- Fase 2Z: endurecimiento de recepcion minima.
- Fase 3A: ficha read-only de proveedor con historial.
- Fase 3B: cuentas por pagar base read-only.
- Fase 3C-A: simulador read-only de CxP generable desde compras recibidas.
- Fase 3C-B: generacion manual controlada de CxP desde compra recibida.
- Fase 3C-C: validaciones de consistencia CxP en health/preflights.

## Reanclaje Fase 3C

Estado vigente: `REVISION_TECNICA_3C_COMPLETADA`.

La auditoria previa de Fase 3C queda reclasificada como prematura/documental. La revision tecnica post-3C-C ya fue completada, pero la auditoria de seguridad/cierre aun debe ejecutarse si el usuario la autoriza.

Motivo:

- el repositorio ya tenia antecedente historico de simulador, generacion manual y checkers;
- el nuevo flujo reanclado activo 3C-A primero y despues 3C-B;
- Docker/PHP estan disponibles y se re-ejecutaron verificaciones completas;
- el usuario reporto QA manual completada para 3C-A y 3C-B.

## Resultado historico previo

- Bloque revisado: Fase 3C.
- Resultado historico: sin hallazgos bloqueantes por revision estatica.
- Reclasificacion: auditoria prematura; no sustituye la auditoria de seguridad post-3C-C.
- Cambios de codigo historicos: ajuste menor en preview para no enlazar proveedor si el proveedor no pertenece al hotel actual.

## Controles revisados

- CxP conserva rutas GET de consulta:
  - `/cuentas-por-pagar`;
  - `/cuentas-por-pagar/generacion-preview`;
  - `/cuentas-por-pagar/{id}`.
- CxP tiene un unico POST activo en 3C-B:
  - `/cuentas-por-pagar/generar-desde-compra/{id}`.
- El boton de generacion solo aparece en compras elegibles del preview.
- CxP no contiene acciones de pago.
- CxP 3C-B escribe solo en `cuentas_por_pagar` durante generacion manual.
- CxP no escribe en `cuentas_por_pagar_movimientos`.
- Health/preflights fallan si `cuentas_por_pagar_movimientos` deja de estar en cero durante Fase 3C-C.
- SQL read-only 3C-C confirma cero movimientos CxP/pagos/abonos y cero movimientos de Caja con referencia CxP.
- CxP no toca `movimientos_caja`, `cortes_caja` ni `cajas`.
- Modelo CxP filtra por `hotel_id`.
- La generacion valida compra recibida, proveedor del mismo hotel, total positivo, detalles existentes y no duplicado.
- Proveedor/Compras no generan CxP automaticamente.
- Sidebar expone CxP bajo el gate de Inventario.
- Acceso sin sesion a `/cuentas-por-pagar` redirige a login.
- `/api/sync` sigue fuera de alcance y validado por checker como bloqueado.

## Auditoria especifica Fase 3C post-QA historica

- Creacion de CxP sin `hotel_id`: bloqueada por `CuentaPorPagar::generarDesdeCompraRecibida()`, que valida `hotelId > 0` y lo inserta explicitamente.
- Creacion de CxP con proveedor de otro hotel: bloqueada por `obtenerCompraParaGeneracion()`, que une proveedor con `p.hotel_id = c.hotel_id`, y por `assertCompraGenerable()`.
- Creacion de CxP con compra de otro hotel: bloqueada por busqueda `WHERE c.id = ? AND c.hotel_id = ?`.
- Duplicacion de CxP: bloqueada por transaccion, `FOR UPDATE` sobre compra y verificacion `cuentas_por_pagar` por `(hotel_id, compra_id)`.
- Generacion desde compra no recibida: bloqueada por `assertCompraGenerable()` con estado exacto `recibida`.
- Generacion desde compra cancelada/borrador: bloqueada por la misma validacion de estado y el boton no aparece para filas no elegibles.
- Escritura accidental en Caja: no hay referencias de escritura a `movimientos_caja`, `cortes_caja` ni `cajas` en el flujo CxP.
- Creacion accidental de pagos: no hay rutas, vistas ni modelo de pagos CxP; `cuentas_por_pagar_movimientos` no se inserta en 3C.
- Endpoints POST sin proteccion: el unico POST 3C llama `validateCSRF()` y pasa por `before()` con `requireAuth`, contexto hotelero y modulo `inventario`.
- Falta de CSRF: el formulario elegible usa `csrf_field()`.
- Botones visibles en estados incorrectos: el boton solo se renderiza cuando `es_elegible` es verdadero.
- Errores de permisos: CxP comparte guardas de modulo `inventario` en controlador y sidebar.
- `/api/sync` modificado accidentalmente: no hay cambios de codigo en `ApiController` ni en la ruta `/api/sync` dentro de esta revision.
- Rollback insuficiente: `docs/rollback-cola.md` documenta rollback por 3C-A, 3C-B, 3C-C y revision post-QA.
- QA critica 3C-A/3C-B: completada manualmente por el usuario.

## Warnings conocidos

- Los checkers ejecutados dentro del contenedor no ven `docs/technical` ni `migrations/` completos por el montaje actual.
- Verificacion automatica 3C-B ejecutada con Docker/PHP disponible: `php -l`, health, preflights, POST sin sesion y prueba local controlada.
- Hay tablas legacy/duplicadas documentadas que no se deben borrar ni fusionar.
- No hay cambios no relacionados pendientes en Git al iniciar esta revision; el ajuste visual de reservaciones quedo commiteado por separado.

## Riesgos residuales

- Si una fase futura agrega pagos, debe crear contrato nuevo y revisar Caja, saldos y movimientos financieros desde cero.
- No hay indice unico fisico documentado para `(hotel_id, compra_id)`; la prevencion actual usa bloqueo transaccional sobre la compra y verificacion de CxP existente.

## Recomendacion

Continuar solo con auditoria de seguridad/cierre 3C si el usuario la autoriza. Mantener prohibidos pagos, Caja, CxC, nomina operativa, permisos profundos y `/api/sync` hasta nuevo bloque explicito.

## Fase 3C - controles esperados

- Simulador primero, sin escritura.
- Generacion manual posterior, nunca automatica.
- Validar compra recibida y `hotel_id`.
- Validar proveedor del mismo hotel.
- Validar no duplicado por `(hotel_id, compra_id)`.
- Validar `total > 0`.
- Registrar auditoria si `AuditService` esta disponible.
- Mantener movimientos Caja-CxP en cero.

## Bloque Personal y Nomina (Fase NP) - controles esperados

### Estado NP-0

- Solo contrato/diagnostico/diseno; sin codigo, sin migraciones aplicadas, sin escritura
  en DB. Sin superficie de ataque nueva todavia.

### Controles a verificar en NP-A..NP-F

- Rutas sensibles del modulo bajo `requireAuth` y contexto hotelero; HTTP sin sesion bloquea.
- Toda escritura (pago/anticipo/prestamo/asistencia/documento/alta-baja) con POST + CSRF.
- Filtro `hotel_id` en todas las consultas; trabajador y movimiento del mismo hotel.
- Trabajador independiente de `usuarios`: no requiere login; vinculo opcional `usuario_id`.
- Sin ALTER destructivo ni borrado sobre `usuarios`/`hotel_usuarios`.
- Sin escritura en Caja (`cajas`, `movimientos_caja`, `cortes_caja`); movimientos
  Caja-nomina deben seguir en cero.
- Sin categoria "Nomina" nueva en `categorias_movimientos`.
- Saldos derivados del ledger, no editables manualmente.
- Baja logica de trabajador/documentos, nunca borrado fisico.
- Auditoria con `AuditService` en escrituras relevantes.
- `/api/sync` fuera de alcance y bloqueado.
- Montos no negativos garantizados por `CHECK` y por validacion de aplicacion.
