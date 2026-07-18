# Plan de autorizacion operativa

Fecha de auditoria: 2026-07-17

## Decision de alcance

No se activan todavia gates nuevos en Reservaciones, Huespedes, Documentos o
API. La auditoria encontro usuarios que aun dependen del fallback legacy y ese
fallback no define permisos `reservaciones.*`. Aplicar la matriz directamente
provocaria bloqueos operativos legitimos.

Estado observado en la base configurada por `.env` (consulta solo lectura):

- 24 asignaciones de usuario a hotel.
- 21 asignaciones con `role_id` (RBAC).
- 3 asignaciones legacy sin `role_id`: dos administradores y un gerente.

La siguiente implementacion debe resolver primero esa compatibilidad y probarla
antes de proteger acciones que escriben reservas, pagos, caja o estados de
habitacion.

## Contrato previo

Objetivo:

Aplicar autorizacion de servidor por accion, cerrada por defecto, sin cambiar
las capacidades efectivas de los roles existentes y sin permitir escrituras
parciales cuando falte un permiso monetario.

Archivos que se propone tocar, por fases:

- `src/app/helpers/auth.php`, solo para resolver de forma explicita la
  compatibilidad legacy.
- Controladores de Reservaciones, Huespedes, Documentos y API, uno por fase.
- Pruebas nuevas bajo `src/tests/casos/`.
- Vistas unicamente despues de que los gates de servidor esten probados, para
  ocultar acciones no autorizadas sin usar la UI como control de seguridad.

Archivos que NO se van a tocar:

- Modelos y calculos de reservaciones, caja, cortes, movimientos y reportes.
- Rutas, migraciones, esquema o datos de la base operativa.
- Servicios de anticipos, pagos, check-in o check-out.
- `service-worker.js`, `pwa.js`, `offline-data.js`,
  `reservaciones-offline.js`, IndexedDB y cache names.
- El bloqueo de `/api/sync`.

Flujos que pueden afectarse:

- Consulta, creacion, edicion y cancelacion de reservaciones.
- Check-in y check-out.
- Anticipos, devoluciones y correcciones de metodo de pago.
- Consulta y edicion de datos de huespedes y documentos.
- Endpoints JSON que exponen ocupacion, reservas, huespedes o caja.

Datos o tablas que se leen:

- `hotel_usuarios`, `roles`, `role_permissions` y catalogo de permisos.
- La reserva o recurso scoped al hotel cuando una condicion monetaria dependa
  de su estado.

Datos o tablas que se escriben:

- Ninguna en la fase de compatibilidad y caracterizacion.
- Las fases de enforcement no agregan escrituras; solamente autorizan o
  rechazan antes de que la accion actual haga su primera escritura.

Formularios afectados:

- Ninguno en la primera fase.
- En fases posteriores no se cambiaran `action`, `method`, `name`, CSRF,
  hidden inputs ni ubicacion del submit.

Pruebas manuales obligatorias:

- Gerente y administrador legacy conservan sus flujos actuales.
- Recepcionista puede crear/editar, hacer check-in/out y cobrar, pero no
  cancelar, revertir ni cambiar metodos de pago.
- Administrador sin `caja.ajustes` no puede producir devoluciones.
- Dueno remoto puede consultar, pero no mutar ni cobrar.
- Un usuario sin permisos y un ID de otro hotel no reciben datos.
- Una denegacion monetaria no deja reserva, abono, CxC, factura o movimiento
  parcial.

Comandos de validacion:

```bash
php -l archivo.php
php src/tools/lint_tenancy.php
php src/tests/run.php
```

Ademas, cada fase debe incorporar una prueba que compruebe que todas sus
acciones publicas enrutadas tienen una politica y que una accion desconocida se
deniega.

Como se revierte si rompe algo:

- Revertir exclusivamente el mapa/gate de la fase afectada.
- No revertir modelos ni datos, porque la autorizacion debe ejecutarse antes de
  cualquier escritura.
- Pausar la fase y restablecer primero el flujo operativo que haya presentado
  la regresion.

Decision del mentor/jefe:

- [ ] Aprobado
- [ ] Aprobado con alcance reducido
- [ ] Rechazado por riesgo operativo

## Matriz resumida propuesta

| Operacion | Permiso base | Permiso adicional |
|---|---|---|
| Consultar reservaciones | `reservaciones.view` | — |
| Crear reservacion | `reservaciones.create` | `caja.cobros` si registra anticipo |
| Editar reservacion | `reservaciones.view` + `reservaciones.edit` | `caja.ajustes` si genera devolucion |
| Check-in | `reservaciones.view` + `habitaciones.checkin` | `caja.cobros` si registra dinero |
| Check-out | `reservaciones.view` + `habitaciones.checkout` | — |
| Cancelar/no-show | `reservaciones.view` + `reservaciones.cancelar` | `caja.ajustes` si devuelve dinero |
| Registrar anticipo | `reservaciones.view` + `caja.cobros` | — |
| Revertir/corregir pago | `reservaciones.view` + `caja.ajustes` | — |
| Exportar reservas | `reservaciones.view` + `reportes.export` | modulo `exportaciones` activo |
| Consultar huesped | `huespedes.view` | — |
| Crear/editar huesped | `huespedes.create` o `huespedes.edit` | `huespedes.view` al editar |
| Consultar documento | `documentos.view` | — |
| Mutar documento | `documentos.all` | — |

Los permisos monetarios condicionales se deben comprobar despues de cargar el
recurso scoped al hotel y antes de iniciar una transaccion o ejecutar la primera
escritura.

## Orden de implementacion

1. Compatibilidad legacy y pruebas de presets/roles personalizados.
2. Reservaciones sin dinero y denegacion por defecto.
3. Check-in/check-out y cobro condicional.
4. Anticipos, ajustes, cancelaciones y devoluciones.
5. Huespedes y documentos.
6. API con respuesta JSON 403 y busqueda global filtrada por permiso.

