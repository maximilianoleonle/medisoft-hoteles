# Fase NP-C-C-0 - Contrato de anticipos y prestamos laborales

Estado: `CONTRATO_NP_C_C_ANTICIPOS_PRESTAMOS_COMPLETADO`

## Objetivo

Definir una futura escritura controlada para registrar anticipos y prestamos laborales en
las tablas de Personal, sin Caja y sin pagos reales:

- `trabajador_anticipos`
- `trabajador_prestamos`

Esta fase solo documenta el contrato. No agrega rutas, formularios, controladores,
modelos, migraciones ni datos.

## Estado previo

- NP-C-B-0 contrato de conceptos laborales completado.
- NP-C-B-A conceptos laborales manuales implementado y cerrado tecnicamente.
- Ledger laboral read-only disponible en la ficha de trabajador.
- Preflight `preflight_personal_ledger.php` existe y pasa sin errores.
- No hay categoria Nomina en Caja.
- No hay movimientos de Caja generados por Personal.
- `/api/sync` sigue fuera de alcance.

## Alcance futuro permitido

### Anticipos

- Registrar un anticipo manual en `trabajador_anticipos`.
- Campos minimos: `hotel_id`, `trabajador_id`, `monto`, `saldo_pendiente`, `fecha`,
  `motivo`, `estado`, `referencia`, `notas`, `created_by`, `updated_by`.
- Estado inicial permitido: `pendiente`.
- `saldo_pendiente` inicial debe ser igual a `monto`.
- No crear movimiento de Caja.
- No descontar automaticamente de conceptos laborales.

### Prestamos

- Registrar un prestamo manual en `trabajador_prestamos`.
- Campos minimos: `hotel_id`, `trabajador_id`, `monto`, `saldo_pendiente`, `fecha`,
  `plazo_meses`, `abono_periodico`, `motivo`, `estado`, `referencia`, `notas`,
  `created_by`, `updated_by`.
- Estado inicial permitido: `vigente`.
- `saldo_pendiente` inicial debe ser igual a `monto`.
- `plazo_meses` y `abono_periodico` son informativos en esta fase.
- No crear movimiento de Caja.
- No crear abonos ni calendario de pagos.

## Fuera de alcance

- Pagos reales.
- Abonos a anticipos o prestamos.
- Liquidacion, condonacion o anulacion de saldos.
- Caja, cortes, movimientos y categoria Nomina.
- Automatizacion desde asistencia, tareas, compras o reservaciones.
- Nomina calculada.
- Permisos profundos nuevos.
- Documentos laborales/uploads.
- `/api/sync`, PWA/offline/cache.

## Reglas de seguridad

- No aceptar `hotel_id` desde formulario.
- No aceptar `trabajador_id` de otro hotel.
- Solo trabajadores activos pueden recibir anticipos/prestamos nuevos.
- Monto mayor a cero obligatorio.
- Fecha obligatoria.
- No permitir `saldo_pendiente` desde formulario; se deriva del monto.
- No permitir `estado` desde formulario; se define por el flujo.
- No usar `DELETE`.
- No escribir en `movimientos_caja`, `cajas`, `cortes_caja` ni categorias de Caja.
- Todo POST futuro debe usar CSRF.
- Toda escritura futura debe auditarse con `AuditService`.
- Preflight de ledger laboral debe pasar antes y despues.

## Definition of Done futura

- Modelo central para registrar anticipo/prestamo.
- Controlador con guardas existentes, permiso administrativo, POST y CSRF.
- Formulario en ficha de trabajador sin formularios anidados.
- Mensajes claros de exito/error indicando "sin Caja".
- Health checker reconoce las rutas nuevas y valida que no tocan Caja.
- Preflight permite solo los INSERT controlados correspondientes y conserva `ERROR: 0`.
- SQL read-only confirma cero categorias Nomina y cero movimientos de Caja relacionados.
- QA manual diferida documentada si el usuario mantiene esa instruccion.

## Rollback futuro

- Revertir el commit de implementacion.
- Si QA manual crea anticipos/prestamos de prueba, no usar `DELETE` sin autorizacion.
- Documentar IDs creados y esperar una fase autorizada de anulacion/correccion, o restaurar
  backup de prueba si aplica.

## Siguiente subfase sugerida

`NP-C-C-A`: registro manual controlado de anticipos y prestamos, sin Caja, sin abonos y
sin pagos reales.
