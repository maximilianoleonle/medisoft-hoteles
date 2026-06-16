# Fase NP-B-A - CRUD basico de trabajadores

Estado: `CRUD_TRABAJADORES_NP_B_A_COMPLETADO_QA_DIFERIDA`

## Objetivo

Permitir alta, edicion, baja logica y reactivacion de trabajadores como entidad laboral
independiente, sin pagos, anticipos, prestamos, asistencias operativas, documentos
laborales, Caja ni `/api/sync`.

## Implementacion

- Rutas:
  - `GET /trabajadores/crear`
  - `POST /trabajadores`
  - `GET /trabajadores/{id}/editar`
  - `POST /trabajadores/{id}/actualizar`
  - `POST /trabajadores/{id}/baja-logica`
  - `POST /trabajadores/{id}/reactivar`
- Modelo: `Trabajador::crearParaHotel()`, `actualizarParaHotel()`,
  `cambiarEstadoParaHotel()`.
- Controlador: `TrabajadorController` con acciones `crear`, `guardar`, `editar`,
  `actualizar`, `bajaLogica` y `reactivar`.
- Vista: `src/app/views/trabajadores/form.php`.
- Listado/detalle muestran acciones controladas segun estado.
- Health checker actualizado para validar rutas, CSRF, auditoria y ausencia de Caja.

## Validaciones

- `hotel_id` se toma del hotel actual; no se acepta desde formulario.
- `nombre_completo` obligatorio.
- `email` valido si se captura.
- `salario_base` numerico y no negativo si se captura.
- `usuario_id` opcional y debe pertenecer al hotel actual si se captura.
- Baja logica usa `estado = 'baja'` y `fecha_baja`.
- Reactivar usa `estado = 'activo'` y limpia `fecha_baja`.
- No hay `DELETE FROM trabajadores`.

## Seguridad

- `requireAuth()`.
- `require_hotel_context()`.
- `require_hotel_module('usuarios')`.
- Lectura: `usuarios.view`.
- Crear: `usuarios.create`.
- Editar/baja/reactivar: `usuarios.edit`.
- POST con `validateCSRF()`.
- Auditoria:
  - `trabajadores.creado`
  - `trabajadores.actualizado`
  - `trabajadores.baja_logica`
  - `trabajadores.reactivado`

## Fuera de alcance confirmado

- Pagos, anticipos, prestamos, comisiones, descuentos, saldos definitivos.
- Asistencia operativa.
- Documentos laborales/uploads.
- Caja, cortes, movimientos, categoria Nomina.
- Crear usuarios del sistema automaticamente.
- Cambios en `usuarios`/`hotel_usuarios`.
- `/api/sync`.

## QA manual diferida

- Crear trabajador sin usuario vinculado.
- Crear trabajador con usuario vinculado del hotel actual.
- Validar bloqueo de email invalido.
- Validar bloqueo de salario negativo/no numerico.
- Editar datos basicos.
- Baja logica y reactivacion.
- Confirmar auditoria en `logs_auditoria`.
- Confirmar que no hay botones de pago/anticipo/prestamo/asistencia/Caja.
- Confirmar aislamiento por hotel.

## Rollback

- Revertir commit `feat(phase-np): add controlled worker CRUD`.
- No ejecutar `DELETE` automatico sobre `trabajadores`.
- Si se crearon datos de prueba, documentar IDs y usar baja logica o restaurar backup
  autorizado.
- No tocar Caja, `usuarios`, `hotel_usuarios` ni `/api/sync`.
