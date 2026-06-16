# Fase NP-C-B-0 - Contrato de conceptos laborales manuales

Estado: `CONTRATO_NP_C_B_CONCEPTOS_LABORALES_COMPLETADO`

## Objetivo

Definir una futura escritura controlada sobre `trabajador_pagos` para registrar conceptos
laborales manuales sin Caja:

- comision;
- bono;
- descuento;
- ajuste.

La palabra `pago` existe en el esquema por compatibilidad del ledger, pero NP-C-B no debe
tratarla como salida real de dinero ni integrarla con Caja. Cualquier pago real requiere
un bloque NP-Caja separado.

## Alcance futuro permitido

- Formulario POST en ficha de trabajador para registrar un concepto laboral.
- CSRF obligatorio.
- Validacion de trabajador activo del mismo `hotel_id`.
- Monto mayor a cero.
- `tipo` permitido: `comision`, `bono`, `descuento`, `ajuste`.
- `efecto` derivado o validado:
  - `comision` y `bono`: `a_favor`;
  - `descuento`: `en_contra`;
  - `ajuste`: seleccion controlada `a_favor` o `en_contra`.
- Fecha obligatoria.
- Auditoria con `AuditService`.
- Mensajes claros de exito/error.
- Redireccion a ficha del trabajador.

## Fuera de alcance

- Tipo `pago` como salida real de dinero.
- Caja, cortes, movimientos y categoria `Nomina`.
- Abonos, prestamos y anticipos.
- Asistencia.
- Automatizacion desde tareas.
- CxP, CxC, compras, reservaciones.
- `/api/sync`, PWA/offline/cache.
- Edicion o borrado de conceptos existentes.

## Reglas de seguridad

- No aceptar `hotel_id` desde formulario.
- No aceptar `trabajador_id` de otro hotel.
- No permitir conceptos a trabajadores en `baja` o `inactivo`.
- No usar `DELETE`; cualquier anulacion futura requiere contrato propio.
- No escribir en `movimientos_caja`.
- No crear categoria `Nomina`.
- Preflight `preflight_personal_ledger.php` debe pasar antes y despues.

## Definition of Done futura

- Modelo central para crear concepto laboral.
- Controlador con guardas existentes, permiso administrativo, POST y CSRF.
- Vista sin formularios anidados.
- Health checker reconoce la ruta nueva y valida que no toca Caja.
- Preflight NP-C-E mantiene `ERROR: 0`.
- SQL read-only confirma que Caja/Nomina sigue en cero.
- QA manual diferida documentada si el usuario mantiene esa instruccion.

## Rollback futuro

- Revertir el commit de implementacion.
- Si se crean conceptos de prueba, no usar `DELETE` sin autorizacion; documentar IDs y
  anular con fase autorizada o restaurar backup de prueba.

## Estado

NP-C-B-0 solo documenta el contrato. No agrega rutas, modelos, vistas ni escrituras.
