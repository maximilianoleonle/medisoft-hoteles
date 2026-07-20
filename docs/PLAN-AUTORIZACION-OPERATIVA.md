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

- [x] Aprobado  <!-- Maximiliano, 2026-07-19: autorizacion monetaria explicita; ver "Ampliacion aprobada" abajo -->
- [x] Aprobado con alcance reducido  <!-- Maximiliano, 2026-07-18 -->
- [ ] Rechazado por riesgo operativo

### Alcance reducido aprobado (2026-07-18)

Se autoriza SOLO la fase de bajo riesgo (paso 2 del orden, acotado a lecturas):
gates de servidor en operaciones de reservaciones **inequivocamente no
monetarias**, cerrado por defecto, con compatibilidad legacy + roles RBAC
personalizados, proteccion cross-hotel y 403 correcto para HTML y JSON. Toda
accion que combine reservaciones con anticipos, devoluciones, caja, check-in,
check-out, cancelacion o correccion de pagos queda FUERA y solo se documenta
para la siguiente fase (requiere autorizacion monetaria explicita aparte).

### Ampliacion aprobada (2026-07-19): autorizacion monetaria explicita

A raiz de una auditoria de seguridad (4 agentes), Maximiliano autoriza cerrar la
frontera monetaria: se GATEAN todas las escrituras de reservaciones + los modulos
con PII y la administracion por rol del hotel. Enforcement = espejo del filtrado
de menu (jamas exige mas permiso que la pantalla que apunta). Mapeo aplicado:

- **Escrituras de reservaciones** (ReservacionController): `guardarAction`→
  `reservaciones.create`; `modificarDiasAction`/`actualizarHabitacionesAction`→
  `reservaciones.edit`; `checkInAction`/`procesarCheckInTardioAction`→
  `habitaciones.checkin`; `checkOutAction`/`checkOutRapidoAction`/
  `checkOutParcialAction`→`habitaciones.checkout`; `registrarAnticipoAction`/
  `revertirAnticipoAction`/`cambiarMetodoPagoAction`→`caja.cobros`; `cancelarAction`/
  `noShowAction`→ any-of `reservaciones.cancelar` ∨ `reservaciones.edit` (recepcion
  trae `.edit` pero no `.cancelar`); llaves/remotos→ any-of `llaves.control` ∨
  `habitaciones.checkin/checkout`. Lecturas GET de escritura (`editar*`, notas) NO
  se gatearon (se mantiene el alcance de lecturas reducido).
- **Rol GLOBAL → rol del hotel**: `UsuarioController`/`ConfiguracionController`/
  `TarifasController` dejan de usar `is_gerente()/is_admin()` (leen `usuarios.rol`
  global) y pasan a `can()`/`require_permission_or_403()` (`usuarios.view/create/
  edit`, `configuracion.view/edit`, `tarifas.view/edit`).
- **Modulos con PII**: gate base en `HuespedController` (`huespedes.view` + create/
  edit), `DocumentoController` (`documentos.view` + `documentos.all`),
  `ProveedorController` (`proveedores.view`), `AreaController`/`HabitacionController`
  (`habitaciones.view`).

Cambios de comportamiento a vigilar (verificados contra presets): recepcion
conserva TODA su operacion (crear/editar/cancelar via `.edit`, check-in/out via
`habitaciones.*`, cobros/anticipos via `caja.cobros`, llaves via `llaves.control`);
camarista/dueno_remoto → 403 en escrituras; **administrador del hotel pierde
`/configuracion` y `/configuracion/tarifas`** (su preset no trae `configuracion.*`
ni `tarifas.*` — el menu ya se los ocultaba; para devolverselos, ampliar su preset
en `config/permisos.php` + `Rol::sembrarPresetsParaHotel`). Suite
`ReservacionGatesRbacTest` actualizada al nuevo contrato (50 asserts, verde).

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

1. Compatibilidad legacy y pruebas de presets/roles personalizados. **HECHO** (auth.php `legacy_preset_permissions`, `LegacyRbacCompatTest`).
2. Reservaciones sin dinero y denegacion por defecto. **HECHO** (lecturas, 2026-07-18).
3. Check-in/check-out y cobro condicional. **HECHO** (2026-07-19, autorizacion monetaria — ver "Ampliacion aprobada").
4. Anticipos, ajustes, cancelaciones y devoluciones. **HECHO** (2026-07-19, `caja.cobros` / cancelar∨edit).
5. Huespedes y documentos. **HECHO** (2026-07-19, gate base `huespedes.view`/`documentos.view` + `.all`).
6. API con respuesta JSON 403 y busqueda global filtrada por permiso. PENDIENTE (busqueda global; el API de reservaciones ya responde 403 JSON).

## Estado de gates de ReservacionController (2026-07-18)

Helper opt-in nuevo: `require_permission_or_403($permiso)` (auth.php) — 403 REAL
(pagina `errors/403` en HTML, JSON 403 en AJAX), cerrado por defecto, resuelve
por `can()` (rol configurable o fallback legacy por preset). NO altera los 53
call-sites de `require_permission()` existentes.

### Gateadas en esta fase (lectura, `reservaciones.view`, no monetarias)

| Accion | Ruta | Cross-hotel |
|---|---|---|
| `indexAction` | GET /reservaciones | lista scoped por hotel en el modelo |
| `verAction` | GET /reservaciones/ver/{id} | `obtenerPorId` filtra por hotel_id (404 si ajeno) |
| `calendarioAction` | GET /reservaciones/calendario | data scoped por hotel |
| `habitacionesApiAction` | GET /reservaciones/.../habitaciones (JSON) | valida hotel_id explicito (404) |
| `obtenerNotasAction` | GET notas (JSON) | + verificacion de propiedad via `obtenerPorId` (404) |

### Frontera monetaria / combinada — GATEADA el 2026-07-19 (ver "Ampliacion aprobada")

> Actualizado 2026-07-19: estas acciones YA se gatean con su permiso (mapeo en la
> seccion "Ampliacion aprobada" y verificado en `ReservacionGatesRbacTest`). El
> inventario original se conserva abajo como referencia del alcance.

Requieren `reservaciones.*` + un permiso monetario, y la denegacion debe ocurrir
ANTES de la primera escritura/transaccion:

- **Crear/editar**: `crearAction`, `guardarAction` (puede registrar anticipo),
  `editarHabitacionesAction`, `actualizarHabitacionesAction`, `editarEstanciaAction`,
  `modificarDiasAction` / `verificarModificarDiasAction` / `topeModificarDiasAction`
  (cambian precio) → `reservaciones.create/edit` (+ `caja.*` si mueven dinero).
- **Check-in/out**: `checkInAction`, `checkInTardioAction`, `procesarCheckInTardioAction`,
  `verificarCheckInAction`, `checkOutAction`, `checkOutRapidoAction`, `checkOutParcialAction`
  → `habitaciones.checkin/checkout` (+ `caja.cobros` si cobran).
- **Dinero directo**: `registrarAnticipoAction`, `revertirAnticipoAction`,
  `cambiarMetodoPagoAction` → `caja.cobros` / `caja.ajustes`.
- **Cancelacion/no-show**: `cancelarAction`, `noShowAction` → `reservaciones.cancelar`
  (+ `caja.ajustes` si devuelven).
- **Notas (escritura)**: `agregarNotaAction` → `reservaciones.edit` (no monetaria pero
  es escritura; se difiere para tratarla junto con el resto de edicion).
- **Exportaciones**: `exportarPDFAction`, `exportarExcelAction` (ya con
  `require_hotel_module('exportaciones')`) → sumar `reservaciones.view` + `reportes.export`
  (cambia capacidad del recepcionista → decision aparte).
- **Cotizacion PDF**: `cotizacionReservacionPdfAction`, `cotizacionPdfAction` — reciben
  un `anticipo` por POST (solo display, sin mover dinero); se difieren por prudencia.
- **Llaves**: `entregarLlaveAction`/`recibirLlaveAction` (+ variantes remotas, ya con
  modulo `llaves_remotos`) → `llaves.control` (concern distinto, no reservaciones.view).

