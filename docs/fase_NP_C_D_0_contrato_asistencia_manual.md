# Fase NP-C-D-0 - Contrato de asistencia manual

Estado: `CONTRATO_NP_C_D_ASISTENCIA_MANUAL_COMPLETADO`

## Objetivo

Definir una futura captura manual basica de asistencia laboral en `trabajador_asistencias`,
sin nomina automatica, sin pagos reales y sin Caja.

Esta fase solo documenta el contrato. No agrega rutas, formularios, controladores,
modelos, migraciones ni datos.

## Estado previo

- NP-C-C-F anticipos y prestamos manuales cerrado tecnicamente.
- Ledger laboral read-only disponible en ficha de trabajador.
- Preflight `preflight_personal_ledger.php` actualizado a NP-C-C-A y sin errores.
- Tabla `trabajador_asistencias` existe con llave unica `(hotel_id, trabajador_id, fecha)`.
- No hay categoria Nomina en Caja.
- No hay movimientos de Caja generados por Personal.
- `/api/sync` sigue fuera de alcance.

## Alcance futuro permitido

- Registrar una asistencia manual por trabajador y dia.
- Campos permitidos:
  - `fecha`
  - `tipo`
  - `hora_entrada`
  - `hora_salida`
  - `horas`
  - `horas_extra`
  - `observaciones`
- Tipos permitidos por esquema:
  - `asistencia`
  - `falta`
  - `retardo`
  - `permiso`
  - `incapacidad`
  - `descanso`
  - `horas_extra`
- Estado tecnico: un registro por trabajador/dia.

## Fuera de alcance

- Edicion de asistencia existente.
- Correccion de duplicados.
- Borrado o anulacion de asistencia.
- Calculo automatico de nomina.
- Descuentos automaticos por falta/retardo.
- Pagos reales, abonos, Caja, cortes y movimientos.
- Automatizacion desde tareas, limpieza o mantenimiento.
- Permisos profundos nuevos.
- `/api/sync`, PWA/offline/cache.

## Reglas de seguridad

- No aceptar `hotel_id` desde formulario.
- No aceptar `trabajador_id` de otro hotel.
- Solo trabajadores activos pueden recibir asistencia nueva.
- Fecha obligatoria.
- Tipo obligatorio y dentro del enum permitido.
- No permitir duplicado por `(hotel_id, trabajador_id, fecha)`.
- Horas y horas extra deben ser nulas o mayores/iguales a cero.
- Si hay hora entrada y salida, salida no debe ser menor a entrada en la misma fecha.
- No usar `DELETE`.
- No escribir en Caja ni crear categoria Nomina.
- Todo POST futuro debe usar CSRF.
- Toda escritura futura debe auditarse con `AuditService`.
- Preflight de ledger laboral debe pasar antes y despues.

## Definition of Done futura

- Modelo central para registrar asistencia manual.
- Controlador con guardas existentes, permiso administrativo, POST y CSRF.
- Formulario en ficha de trabajador sin formularios anidados.
- Mensajes claros ante duplicado de dia.
- Health checker reconoce la ruta nueva y valida que no toca Caja.
- Preflight permite solo INSERT controlado en `trabajador_asistencias` y conserva
  `ERROR: 0`.
- SQL read-only confirma cero categorias Nomina y cero movimientos de Caja relacionados.
- QA manual diferida documentada si el usuario mantiene esa instruccion.

## Rollback futuro

- Revertir el commit de implementacion.
- Si QA manual crea asistencias de prueba, no usar `DELETE` sin autorizacion.
- Documentar IDs creados y esperar una fase autorizada de anulacion/correccion, o restaurar
  backup de prueba si aplica.

## Siguiente subfase sugerida

`NP-C-D-A`: captura manual basica de asistencia, sin nomina automatica, sin Caja y sin
edicion de registros existentes.
