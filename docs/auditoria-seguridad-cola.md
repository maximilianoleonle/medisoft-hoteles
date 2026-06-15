# Auditoria de seguridad - cola autonoma

## Alcance

Auditoria post-commit del bloque autorizado hasta Fase 3B:

- Fase 2X: detalle read-only de compra recibida.
- Fase 2Y: reportes read-only de compras recibidas.
- Fase 2Z: endurecimiento de recepcion minima.
- Fase 3A: ficha read-only de proveedor con historial.
- Fase 3B: cuentas por pagar base read-only.

## Resultado

Estado: `AUDITORIA_SEGURIDAD_COMPLETADA_QA_MANUAL_PENDIENTE`.

No se detectaron riesgos bloqueantes en la auditoria automatica/local.

Ultima auditoria:

- Bloque: Fase 2X, 2Y, 2Z, 3A y 3B.
- Resultado: sin hallazgos bloqueantes.
- Cambios de codigo requeridos: ninguno.
- Cambios de documentacion: matriz de riesgo/QA actualizada.

## Controles revisados

- CxP tiene rutas GET solamente:
  - `/cuentas-por-pagar`;
  - `/cuentas-por-pagar/{id}`.
- CxP no tiene formularios POST.
- CxP no llama `validateCSRF`, porque no escribe datos.
- CxP no contiene acciones de pago.
- CxP no escribe en `cuentas_por_pagar`.
- CxP no escribe en `cuentas_por_pagar_movimientos`.
- CxP no toca `movimientos_caja`, `cortes_caja` ni `cajas`.
- Modelo CxP filtra por `hotel_id`.
- Proveedor/Compras no escriben CxP ni enlazan CxP desde sus vistas.
- Sidebar expone CxP bajo el gate de Inventario.
- Acceso sin sesion a `/cuentas-por-pagar` redirige a login.
- `/api/sync` sigue fuera de alcance y validado por checker como bloqueado.

## Warnings conocidos

- Los checkers ejecutados dentro del contenedor no ven `docs/technical` ni `migrations/` completos por el montaje actual.
- Hay tablas legacy/duplicadas documentadas que no se deben borrar ni fusionar.
- `src/app/views/reservaciones/ver.php` tiene un cambio visual pendiente y no relacionado.

## Riesgos residuales

- Falta QA manual autenticada de listado/detalle CxP.
- Falta QA visual del estado vacio y filtros.
- Falta decidir el destino del cambio visual de `reservaciones/ver.php`.
- Si una fase futura escribe CxP, debe validar estrictamente `hotel_id` de proveedor/compra antes de insertar o actualizar saldos.

## Recomendacion

Fase 3C fue autorizada por nuevo mensaje real. Mantener prohibidos pagos, Caja, CxC, nomina, permisos profundos y `/api/sync`.

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
