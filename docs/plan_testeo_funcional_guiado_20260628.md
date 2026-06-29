# Plan de testeo funcional guiado - Medisoft Hoteles

Fecha base: 2026-06-28
Rama objetivo: `feature/saas-multihotel`
Alcance: funcionamiento manual y guiado. Seguridad profunda queda fuera de este ciclo.

## 1. Objetivo

Validar que el sistema funcione de punta a punta para dos superficies:

- Panel Medisoft / SaaS: alta y administracion de hoteles, planes, modulos, branding y usuarios vinculados.
- Operacion hotelera: recepcion, habitaciones, huespedes, reservaciones, caja, inventario, compras, cuentas, documentos, personal, tareas, reportes, facturacion y PWA smoke.

El objetivo de aprobacion no es "clicar todo una vez"; es confirmar que los flujos principales crean, consultan, cambian estado, calculan y exportan lo esperado sin romper tenant, modulos, caja ni formularios.

## 2. Fuera de alcance en este ciclo

No probar como seguridad profunda:

- XSS, SQL injection, brute force extensivo, pentest, bypass avanzado de permisos.
- Cambios directos en base de datos.
- Migraciones nuevas.
- Correcciones de datos historicos.
- Modificar o depurar `service-worker.js`, `pwa.js`, `offline-data.js`, `reservaciones-offline.js`.
- Pruebas destructivas sobre `/api/sync`.

Si se toca `/api/sync`, solo debe ser smoke autenticado para confirmar HTTP 423 y JSON `sync_temporarily_disabled`.

## 3. Estado observado del entorno local

Servicios activos:

- App: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`
- MySQL externo: `localhost:3307`

Base activa detectada por la app: `medisoft_hoteles_import`.

Hoteles activos detectados:

- `Los Cedros` (`los-cedros`)
- `Hotel Demo SaaS` (`hotel-demo-saas`)
- `Hotel QA Temporal 20260531174223 Editado`
- `Maximiliano Leon` (`maximiliano`)

Usuarios visibles en local:

- `admin` - gerente - Los Cedros
- `Rafael` - gerente - Los Cedros
- `Maximiliano` - recepcionista - Los Cedros
- `LETICIA` - recepcionista - Los Cedros
- `JOSE` - recepcionista - Los Cedros
- `Adriana` - recepcionista - Los Cedros
- `Monica` - gerente - Los Cedros
- `Wilberto` - recepcionista - Los Cedros
- `admin_demo_saas` - administrador - Hotel Demo SaaS
- `qa_admin_174223` - administrador - hotel QA temporal
- `adminmax` - administrador - Los Cedros, Demo SaaS y Maximiliano

Conteos funcionales globales detectados:

- Habitaciones: 70
- Huespedes: 1031
- Reservaciones: 30
- Movimientos caja: 1424
- Productos inventario: 35
- Compras: 4
- CxP: 2
- CxC: 2
- Documentos: 14
- Trabajadores: 1
- Tareas: 5
- Solicitudes de factura: 177

Cajas abiertas detectadas:

- Los Cedros: corte abierto `#229`
- Hotel Demo SaaS: corte abierto `#230`
- Maximiliano Leon: corte abierto `#241`

Nota tecnica: `tools/saas/verificar_estado.php` actualmente devuelve `FAIL` por una allowlist/contrato tecnico que parece quedar atras respecto a tablas nuevas con `hotel_id`. No usarlo como unico semaforo de QA manual hasta revisar esa herramienta. Para baseline tecnico, preferir `health_check_fase_1a.php` y los preflights especificos read-only.

## 4. Reglas de datos de prueba

Usar prefijo obligatorio en todo registro creado:

`QA-FUNC-20260628-<A|B>-<modulo>`

Ejemplos:

- Huesped: `QA-FUNC-20260628-A Huesped Principal`
- Proveedor: `QA-FUNC-20260628-B Proveedor`
- Tarea: `QA-FUNC-20260628-B Limpieza`
- Documento: `QA-FUNC-20260628-B Documento`

Registrar cada ID creado en la bitacora:

| ID prueba | Modulo | Registro creado | ID generado | Persona | Estado | Notas |
|---|---|---:|---:|---|---|---|

Reglas:

- No borrar ni modificar registros reales salvo que el caso lo pida y sea reversible.
- Preferir crear datos nuevos con prefijo QA.
- Si una prueba mueve dinero o caja, anotar corte abierto, monto, metodo de pago y movimiento generado.
- No hacer cambios directos en DB durante QA manual.
- Si se requiere reset de password local, hacerlo como preparacion controlada, no como parte del test funcional.

## 5. Equipo de 2 personas

Persona A - Recepcion y operacion:

- Login hotelero.
- Dashboard.
- Habitaciones.
- Huespedes.
- Reservaciones.
- Check-in, check-out, llaves, remotos, notas, anticipos.
- Facturacion desde reservacion.
- Validacion responsive de flujos de recepcion.

Persona B - Administracion, finanzas y backoffice:

- Panel SaaS.
- Modulos, planes, branding.
- Usuarios, configuracion y tarifas.
- Caja, cortes, movimientos.
- Inventario, proveedores, compras, CxP, CxC.
- Documentos, personal, tareas, reportes.
- Validacion responsive de backoffice.

Pruebas cruzadas:

- A crea o modifica datos, B verifica reflejo en reportes/caja.
- B cambia modulos/branding en segundo hotel, A verifica menu/login/identidad.
- A crea reservacion, B valida CxC, caja, reportes y facturacion.

## 6. Severidad de bugs

P0 - Bloqueante:

- No se puede iniciar sesion o usar el sistema.
- Datos de un hotel aparecen en otro hotel.
- Caja, check-in, check-out, cobros, pagos o saldos quedan corruptos.
- Formularios criticos no guardan o pierden CSRF/hidden/actions.
- `/api/sync` deja de responder 423 estando autenticado.

P1 - Alto:

- Un flujo principal falla pero hay workaround.
- PDF/exportacion critica no se genera.
- Error 500 en modulo operativo.
- El sistema guarda, pero muestra estado incorrecto.
- Validacion incorrecta permite datos claramente invalidos.

P2 - Medio:

- Error visual o UX que confunde, sin corromper datos.
- Filtro, busqueda, paginacion o modal falla parcialmente.
- Responsive con solapamientos no bloqueantes.

P3 - Bajo:

- Texto, acento, alineacion menor, copy, icono, formato cosmetico.

## 7. Criterios de aprobacion

El ciclo funcional se aprueba cuando:

- 0 bugs P0 abiertos.
- 0 bugs P1 abiertos sin decision explicita.
- Todos los flujos P0/P1 de este plan estan `PASS` o `NA justificado`.
- Al menos 2 hoteles fueron revisados para tenant/branding/modulos.
- Al menos 1 flujo completo pasa de punta a punta:
  huesped -> reservacion -> check-in -> caja/facturacion -> check-out -> reporte.
- Reportes/exportaciones principales abren sin 500.
- Formularios modificados recientemente conservan datos y muestran errores por campo al fallar.
- Mobile no bloquea operaciones principales de recepcion.
- `/api/sync` sigue bloqueado con 423 en sesion autenticada.

## 8. Preparacion antes de empezar

Ambos:

1. Confirmar `docker compose ps`.
2. Abrir `http://localhost:8080`.
3. Abrir una ventana normal y una incognito.
4. Confirmar credenciales disponibles para:
   - SaaS/admin global.
   - Gerente o administrador de Los Cedros.
   - Recepcionista de Los Cedros.
   - Administrador de Hotel Demo SaaS o Maximiliano.
5. Crear hoja de seguimiento con columnas:
   - `Caso`
   - `Persona`
   - `Hotel`
   - `Usuario/Rol`
   - `Resultado esperado`
   - `Resultado real`
   - `PASS/FAIL/BLOCKED/NA`
   - `Evidencia`
   - `Bug ID`

Preflight tecnico recomendado, solo lectura:

```bash
docker compose ps
docker compose exec -T app php tools/saas/health_check_fase_1a.php
docker compose exec -T app php tools/saas/preflight_hotel_id.php
docker compose exec -T app php tools/saas/preflight_operacion_diaria.php
docker compose exec -T app php tools/saas/preflight_tablero_ejecutivo.php
docker compose exec -T app php tools/saas/preflight_arqueo_metodos_pago.php
docker compose exec -T app php tools/saas/preflight_recepcion_compras.php
docker compose exec -T app php tools/saas/preflight_cuentas_por_cobrar.php
docker compose exec -T app php tools/saas/preflight_pagos_proveedores_caja.php
docker compose exec -T app php tools/saas/preflight_personal_pagos_caja.php
docker compose exec -T app php tools/saas/preflight_nomina_administrativa_suite.php
docker compose exec -T app php tools/saas/preflight_tareas_operativas.php
docker compose exec -T app php tools/saas/preflight_conciliacion_financiera.php
```

Si algun preflight falla, anotar y decidir si bloquea la QA manual. No corregir datos historicos sin autorizacion.

## 9. Orden recomendado de ejecucion

Dia/ronda 1 - Base y accesos:

- A: login hotelero, menu, dashboard, responsive recepcion.
- B: SaaS, modulos, usuarios, branding, tenant.

Dia/ronda 2 - Recepcion pura:

- A: huespedes, habitaciones, reservaciones, check-in/check-out.
- B: caja abierta, movimientos, cortes, arqueo, tarifas.

Dia/ronda 3 - Flujos con dinero:

- A: reservacion con pagos, cambio metodo pago, anticipo, factura.
- B: CxC, CxP, pagos/reversiones, conciliacion, reportes.

Dia/ronda 4 - Backoffice:

- A: documentos desde entidades, tareas desde habitacion/reservacion.
- B: inventario, proveedores, compras, personal/nomina administrativa.

Dia/ronda 5 - Cierre:

- Ambos: responsive, PWA smoke, exports, busqueda global, segundo hotel, regresion rapida.

## 10. Checklist comun por cada pantalla

Aplicar en todas las vistas principales:

- Carga sin 500.
- Titulo y hotel correcto.
- Sidebar muestra solo modulos activos.
- Busqueda/filtros no rompen la pantalla.
- Botones llevan a rutas esperadas.
- Formularios POST tienen CSRF y no pierden datos en error.
- Mensajes success/error aparecen y desaparecen correctamente.
- Registros nuevos aparecen en listado/detalle.
- Paginacion y filtros conservan contexto.
- En mobile: no se enciman header, sidebar, botones, tablas ni modales.
- En otro hotel: no aparece informacion del hotel anterior.

## 11. Casos por modulo

### AUTH - Login, logout y contexto

AUTH-01 Persona A:

1. Entrar a `/login`.
2. Iniciar sesion con usuario gerente de Los Cedros.
3. Validar redireccion a `/dashboard`.

Esperado: dashboard carga, sidebar hotelero usa branding de hotel, no identidad SaaS.

AUTH-02 Persona A:

1. Cerrar sesion desde menu de usuario.
2. Confirmar redireccion al login correcto.

Esperado: sesion cerrada; rutas internas redirigen a login.

AUTH-03 Persona A:

1. Entrar a `/h/los-cedros/login`.
2. Loguear usuario vinculado a Los Cedros.

Esperado: entra con `hotel_id` Los Cedros, menu segun modulos del hotel.

AUTH-04 Persona B:

1. Entrar con `adminmax` o usuario multi-hotel.
2. Confirmar hotel activo.
3. Cerrar sesion.
4. Entrar a otro hotel vinculado.

Esperado: no arrastra datos ni branding del hotel anterior.

AUTH-05 Persona B:

1. Intentar entrar a una ruta interna sin sesion, por ejemplo `/habitaciones`.

Esperado: redireccion a login, no contenido operativo visible.

### SAAS - Panel Medisoft

SAAS-01 Persona B:

1. Entrar a `/admin/saas/hoteles`.
2. Revisar listado de hoteles.

Esperado: identidad visual Medisoft, no branding de hotel.

SAAS-02 Persona B:

1. Abrir detalle de Los Cedros.
2. Revisar resumen, plan, modulos, usuarios y branding.

Esperado: datos coherentes, formularios separados y con CSRF.

SAAS-03 Persona B:

1. Crear hotel QA con prefijo.
2. Dejarlo inactivo.
3. Abrir detalle.

Esperado: hotel creado inactivo, sin romper hoteles existentes.

SAAS-04 Persona B:

1. Editar hotel QA.
2. Cambiar nombre/codigo/datos no criticos.

Esperado: cambios guardados y visibles.

SAAS-05 Persona B:

1. Activar hotel QA.
2. Intentar login scoped con su slug si tiene usuario vinculado.

Esperado: si no hay usuario, login no debe permitir acceso; si se vincula usuario, debe entrar.

SAAS-06 Persona B:

1. Vincular usuario administrador al hotel QA.
2. Probar login hotelero.

Esperado: usuario entra solo a su hotel vinculado.

SAAS-07 Persona B:

1. Cambiar modulos del hotel QA.
2. Entrar al hotel.

Esperado: sidebar oculta/despliega modulos segun seleccion.

SAAS-08 Persona B:

1. Cambiar branding basico del hotel QA o Demo.
2. Revisar login, sidebar, manifest scoped.

Esperado: hotel usa `--brand-*`; panel SaaS mantiene `--ms-*`.

SAAS-09 Persona A:

1. Mientras B cambia modulos/branding de hotel QA, A revisa Los Cedros.

Esperado: Los Cedros no cambia visual ni funcionalmente.

### DASH - Dashboard y operacion diaria

DASH-01 Persona A:

1. Entrar a `/dashboard`.
2. Revisar ocupacion, caja, llegadas, salidas, notificaciones y estacionamiento.

Esperado: totales consistentes con habitaciones/reservaciones visibles.

DASH-02 Persona A:

1. Usar tarjetas/enlaces del dashboard hacia habitaciones, caja y reservaciones.

Esperado: enlaces llevan al modulo correcto y preservan hotel.

DASH-03 Persona B:

1. Entrar a `/operacion/diaria`.

Esperado: tablero read-only; no crea tareas, no cambia habitaciones, no toca caja.

DASH-04 Persona B:

1. Entrar a `/operacion/conciliacion-financiera`.

Esperado: filtros funcionan; pantalla read-only.

### HAB - Habitaciones

HAB-01 Persona A:

1. Listar `/habitaciones`.
2. Filtrar por estado, tipo, piso y busqueda.

Esperado: resultados coinciden y no hay errores visuales.

HAB-02 Persona A:

1. Crear habitacion QA.
2. Agregar numero unico, tipo, piso, precio, camas, capacidad, caracteristicas.

Esperado: habitacion creada y visible.

HAB-03 Persona A:

1. Intentar crear habitacion con numero duplicado.

Esperado: error por campo, datos rehidratados.

HAB-04 Persona A:

1. Editar habitacion QA.
2. Cambiar precio/capacidad/caracteristicas/fotos.

Esperado: guarda y detalle refleja cambios.

HAB-05 Persona A:

1. Gestionar imagenes.
2. Subir imagen valida, marcar principal, eliminar una imagen.

Esperado: imagen principal cambia, errores claros si archivo invalido.

HAB-06 Persona A:

1. Programar mantenimiento de habitacion QA.
2. Activarlo/cancelarlo segun botones disponibles.

Esperado: estado y reportes de mantenimiento reflejan cambios.

HAB-07 Persona A:

1. Cambiar estado manual a limpieza/mantenimiento/disponible cuando sea permitido.

Esperado: reglas bloquean cambios invalidos.

HAB-08 Persona A:

1. Liberar habitacion individual y multiple si aplica.

Esperado: solo habitaciones elegibles cambian.

HAB-09 Persona B:

1. Abrir historial de habitacion.

Esperado: eventos coherentes por hotel.

### HUE - Huespedes y vehiculos

HUE-01 Persona A:

1. Abrir `/huespedes`.
2. Filtrar/buscar por nombre/telefono.

Esperado: resultados del hotel actual.

HUE-02 Persona A:

1. Crear huesped QA con telefono/email/procedencia/notas.
2. Agregar extras si el hotel los muestra.

Esperado: huesped creado y detalle accesible.

HUE-03 Persona A:

1. Intentar crear con email invalido o campo requerido vacio.

Esperado: error por campo y datos preservados.

HUE-04 Persona A:

1. Crear huesped con vehiculo.
2. Registrar placas, marca/modelo/color/estacionamiento/extras.

Esperado: vehiculo aparece en perfil y dashboard de estacionamiento.

HUE-05 Persona A:

1. Editar huesped.
2. Cambiar telefono, email, extras, notas.

Esperado: cambios visibles, errores rehidratan datos.

HUE-06 Persona A:

1. Agregar, editar y eliminar vehiculo desde perfil.

Esperado: no hay duplicados indebidos; vehiculo eliminado no aparece activo.

HUE-07 Persona A:

1. Cargar documento/identificacion si el campo esta visible.

Esperado: documento aparece en perfil/centro documental.

### RES - Reservaciones

RES-01 Persona A:

1. Abrir `/reservaciones`.
2. Filtrar por estado/fecha/busqueda.

Esperado: listado coincide con filtros.

RES-02 Persona A:

1. Crear reservacion QA para huesped existente.
2. Seleccionar fechas validas y habitaciones disponibles.

Esperado: precio calculado, habitaciones seleccionadas y resumen correcto.

RES-03 Persona A:

1. Crear reservacion con datos incompletos.

Esperado: error por campo; huesped, fechas, habitaciones y cortesias se restauran.

RES-04 Persona A:

1. Crear reservacion desde habitaciones o acceso rapido.

Esperado: fechas/habitacion preseleccionadas correctamente.

RES-05 Persona A:

1. Generar cotizacion PDF antes de guardar o desde reservacion.

Esperado: PDF abre/descarga con branding del hotel.

RES-06 Persona A:

1. Abrir detalle de reservacion.
2. Agregar nota.

Esperado: nota visible, orden correcto.

RES-07 Persona A:

1. Entregar llave/remoto.
2. Recibir llave/remoto.

Esperado: estado de control cambia y queda trazabilidad.

RES-08 Persona A:

1. Editar habitaciones de una reservacion futura.

Esperado: disponibilidad se valida, habitaciones cambian y precio queda coherente.

RES-09 Persona A:

1. Verificar modificar dias.
2. Modificar estancia aumentando y reduciendo.

Esperado: ajuste de precio/caja solo si aplica y mensaje claro.

RES-10 Persona A + B:

1. Registrar anticipo en reservacion.
2. B valida movimiento en caja.

Esperado: anticipo visible en reservacion y movimiento caja relacionado.

RES-11 Persona A + B:

1. Revertir anticipo.
2. B valida movimiento inverso.

Esperado: saldo y caja coherentes.

RES-12 Persona A:

1. Cambiar metodo de pago de reservacion.

Esperado: metodo actualizado, pagos mixtos si aplica, caja coherente.

RES-13 Persona A:

1. Check-in normal.

Esperado: reservacion `checked_in`, habitaciones ocupadas, inventario preview aplicado si corresponde.

RES-14 Persona A:

1. Check-in tardio.

Esperado: flujo alterno procesa sin romper estado.

RES-15 Persona A:

1. Check-out normal.

Esperado: reservacion completada, habitacion pasa al estado esperado, caja/saldos correctos.

RES-16 Persona A:

1. Check-out parcial si la reservacion tiene varias habitaciones.

Esperado: solo habitaciones seleccionadas salen.

RES-17 Persona A:

1. Check-out rapido cuando aplique.

Esperado: completa flujo sin pasos innecesarios y mantiene caja.

RES-18 Persona A:

1. Cancelar reservacion futura.

Esperado: habitacion liberada, factura relacionada cancelada si existe, caja no queda inconsistente.

RES-19 Persona A:

1. Exportar PDF y Excel de reservaciones.

Esperado: archivos se generan.

RES-20 Persona B:

1. Revisar `/api/reservaciones/{id}/resumen-pagos` desde la UI.

Esperado: resumen coincide con movimientos visibles.

### CAJA - Caja, cortes, arqueo

CAJA-01 Persona B:

1. Entrar a `/caja`.

Esperado: si hay corte abierto, muestra dashboard; si no, muestra apertura.

CAJA-02 Persona B:

1. Registrar ingreso QA con categoria, monto, metodo, referencia.

Esperado: movimiento aparece y resumen aumenta.

CAJA-03 Persona B:

1. Registrar gasto QA.

Esperado: movimiento aparece y resumen disminuye.

CAJA-04 Persona B:

1. Intentar ingreso/gasto con monto invalido.

Esperado: modal se reabre, datos preservados, error por campo.

CAJA-05 Persona B:

1. Abrir movimientos.
2. Filtrar y editar movimiento permitido.

Esperado: detalle AJAX carga y edicion guarda.

CAJA-06 Persona B:

1. Revisar corte actual.
2. Ver totales por metodo.

Esperado: ingresos/gastos/efectivo esperado consistentes.

CAJA-07 Persona B:

1. Cerrar corte si se decide hacerlo en entorno QA.

Esperado: corte cambia a cerrado, historial lo muestra, PDF disponible.

CAJA-08 Persona B:

1. Abrir nueva caja tras cerrar.

Esperado: nuevo corte abierto.

CAJA-09 Persona B:

1. Reporte por metodos.
2. Arqueo por metodos.

Esperado: filtros y totales funcionan, sin modificar datos.

CAJA-10 Persona B:

1. Gestionar categorias como gerente.

Esperado: crear/editar/toggle/orden funcionan via AJAX.

### FAC - Facturacion

FAC-01 Persona A:

1. Desde reservacion, generar solicitud de factura si el flujo lo permite.

Esperado: solicitud aparece en `/facturacion`.

FAC-02 Persona B:

1. Abrir `/facturacion`.
2. Filtrar por estatus/tipo/fecha/busqueda.

Esperado: resultados correctos.

FAC-03 Persona B:

1. Abrir detalle.
2. Guardar datos fiscales validos.

Esperado: estatus pasa a en proceso cuando los datos requeridos estan completos.

FAC-04 Persona B:

1. Probar RFC/codigo postal invalido.

Esperado: error por campo y datos preservados.

FAC-05 Persona B:

1. Marcar en proceso.
2. Completar con numero de factura.

Esperado: estatus correcto, folio visible.

FAC-06 Persona B:

1. Cancelar solicitud con motivo.

Esperado: estatus cancelado y motivo visible.

### INV - Inventario

INV-01 Persona B:

1. Listar inventario.
2. Filtrar/buscar productos.

Esperado: productos del hotel actual.

INV-02 Persona B:

1. Crear producto QA con codigo unico, categoria, unidad, stock inicial/minimo, costo.

Esperado: producto creado y visible.

INV-03 Persona B:

1. Intentar codigo duplicado o campos invalidos.

Esperado: errores por campo y datos preservados.

INV-04 Persona B:

1. Editar producto QA.

Esperado: cambios visibles.

INV-05 Persona B:

1. Registrar entrada manual.

Esperado: stock aumenta y movimiento aparece.

INV-06 Persona B:

1. Registrar salida manual vinculada a habitacion/motivo.

Esperado: stock baja, no permite stock negativo.

INV-07 Persona B:

1. Hacer ajuste manual permitido.

Esperado: movimiento de ajuste y stock final correcto.

INV-08 Persona B:

1. Revisar configuracion por tipo de habitacion.

Esperado: cantidades se guardan y check-in preview las refleja.

INV-09 Persona A+B:

1. A hace check-in en habitacion con configuracion de inventario.
2. B revisa movimientos de inventario.

Esperado: descuentos automaticos solo si aplica.

INV-10 Persona B:

1. Exportar inventario/movimientos/PDF.

Esperado: archivos se generan con branding del hotel.

### PROV-COMP - Proveedores y compras

PROV-01 Persona B:

1. Crear proveedor QA.

Esperado: proveedor visible.

PROV-02 Persona B:

1. Intentar RFC/nombre duplicado.

Esperado: error por campo.

PROV-03 Persona B:

1. Editar proveedor.
2. Desactivar y reactivar.

Esperado: estado cambia y listado lo refleja.

COMP-01 Persona B:

1. Crear borrador de compra con proveedor y productos.

Esperado: compra en borrador con totales correctos.

COMP-02 Persona B:

1. Intentar compra sin proveedor/producto/cantidad.

Esperado: error en campo o primera linea.

COMP-03 Persona B:

1. Recibir compra.

Esperado: compra recibida, inventario aumenta, doble recepcion bloqueada.

COMP-04 Persona B:

1. Revisar reporte de compras recibidas.

Esperado: compra QA aparece en reporte.

### CXP - Cuentas por pagar

CXP-01 Persona B:

1. Abrir preview generacion CxP.
2. Generar CxP desde compra recibida elegible.

Esperado: CxP creada y detalle disponible.

CXP-02 Persona B:

1. Intentar generar CxP duplicada para la misma compra.

Esperado: bloqueo claro.

CXP-03 Persona B:

1. Abrir simulador caja CxP.

Esperado: muestra elegibilidad segun corte abierto.

CXP-04 Persona B:

1. Registrar pago parcial a proveedor.

Esperado: movimiento caja egreso, saldo CxP parcial.

CXP-05 Persona B:

1. Registrar pago total restante.

Esperado: CxP pagada/liquidada.

CXP-06 Persona B:

1. Revertir pago.

Esperado: movimiento inverso, saldo restaurado, token de reversion de un solo uso.

CXP-07 Persona B:

1. Reintentar pago/reversion sin recargar.

Esperado: token usado bloquea duplicado.

### CXC - Cuentas por cobrar

CXC-01 Persona B:

1. Abrir `/cuentas-por-cobrar`.
2. Revisar cuentas derivadas de reservaciones.

Esperado: filtros y resumen correctos.

CXC-02 Persona B:

1. Generar CxC desde reservacion elegible.

Esperado: CxC operativa creada.

CXC-03 Persona B:

1. Intentar generar duplicado.

Esperado: bloqueo claro.

CXC-04 Persona B:

1. Abrir simulador caja CxC.

Esperado: elegibilidad segun corte abierto.

CXC-05 Persona B:

1. Registrar cobro parcial.

Esperado: movimiento caja ingreso, saldo parcial.

CXC-06 Persona B:

1. Registrar cobro total restante.

Esperado: cuenta liquidada.

CXC-07 Persona B:

1. Revertir cobro.

Esperado: gasto caja/reversion y saldo restaurado.

### DOC - Centro documental

DOC-01 Persona B:

1. Abrir `/documentos`.
2. Filtrar por estado, tipo y busqueda.

Esperado: listado correcto.

DOC-02 Persona B:

1. Subir documento QA general.

Esperado: storage privado, detalle abre.

DOC-03 Persona A:

1. Subir documento contextual desde huesped/reservacion.

Esperado: documento aparece en entidad y centro documental.

DOC-04 Persona B:

1. Probar archivo invalido o faltante.

Esperado: error por campo archivo, datos preservados.

DOC-05 Persona B:

1. Editar metadata.

Esperado: titulo/tipo/etiquetas/descripcion cambian.

DOC-06 Persona B:

1. Descargar o previsualizar.

Esperado: archivo disponible sin mostrar ruta privada.

DOC-07 Persona B:

1. Archivar, restaurar y baja logica.

Esperado: estados cambian, sin borrado fisico.

### PERS - Personal y nomina administrativa

PERS-01 Persona B:

1. Abrir `/trabajadores`.

Esperado: listado, filtros y resumen.

PERS-02 Persona B:

1. Crear trabajador QA.

Esperado: trabajador visible sin requerir usuario de login.

PERS-03 Persona B:

1. Intentar crear con campos invalidos.

Esperado: errores por campo.

PERS-04 Persona B:

1. Registrar concepto laboral, anticipo, prestamo y asistencia.

Esperado: ledger se actualiza.

PERS-05 Persona B:

1. Simulador pago caja.

Esperado: muestra elegibilidad sin modificar caja.

PERS-06 Persona B:

1. Registrar pago laboral si el entorno lo permite.

Esperado: movimiento caja y saldo laboral coherente.

PERS-07 Persona B:

1. Revertir pago laboral.

Esperado: saldo y caja restaurados.

PERS-08 Persona B:

1. Revisar pre-nomina preview, periodos, cierre, aprobacion/anulacion si aplica.

Esperado: snapshot persistente queda inmutable.

PERS-09 Persona B:

1. Exportar CSV/reportes/recibo PDF.

Esperado: archivos abren.

PERS-10 Persona B:

1. Baja logica y reactivacion del trabajador QA.

Esperado: estado cambia sin perder historial.

### TAR - Tareas operativas

TAR-01 Persona A:

1. Abrir tareas, reporte y agenda.

Esperado: listados read-only cargan.

TAR-02 Persona B:

1. Crear tarea QA manual.

Esperado: tarea creada.

TAR-03 Persona B:

1. Intentar crear con campos faltantes.

Esperado: error por campo.

TAR-04 Persona B:

1. Asignar trabajador.

Esperado: asignacion visible y evento registrado.

TAR-05 Persona B:

1. Iniciar, completar y cancelar segun estado.

Esperado: solo transiciones validas.

TAR-06 Persona A+B:

1. Crear tarea desde mantenimiento.
2. Crear tarea desde limpieza/habitacion.

Esperado: tarea vinculada correctamente.

TAR-07 Persona B:

1. Adjuntar documento a tarea.

Esperado: documento aparece en detalle de tarea.

### REP - Reportes y exportaciones

REP-01 Persona B:

1. Abrir `/reportes`.

Esperado: indice carga.

REP-02 Persona B:

1. Reporte ejecutivo.

Esperado: filtros funcionan, read-only.

REP-03 Persona B:

1. Reporte gerencial diario y PDF.

Esperado: HTML/PDF cargan.

REP-04 Persona B:

1. Reportes limpieza y mantenimiento.

Esperado: muestran datos y botones manuales autorizados.

REP-05 Persona B:

1. Ingresos/gastos, ocupacion, estancia, procedencia, habitaciones rentables, ranking estados.

Esperado: filtros, graficas y totales sin 500.

REP-06 Persona B:

1. Exportar PDF desde reportes.

Esperado: archivo generado con branding.

REP-07 Persona B:

1. Links de reporte: historial, descarga interna, enviar correo si esta configurado, revocar.

Esperado: estados correctos; si correo no esta configurado, mensaje claro.

### USR-CONF - Usuarios, configuracion y tarifas

USR-01 Persona B:

1. Abrir usuarios.
2. Crear usuario QA.

Esperado: usuario creado, password no se rehidrata en errores.

USR-02 Persona B:

1. Editar usuario QA.
2. Toggle activo/inactivo.

Esperado: cambios visibles.

CONF-01 Persona B:

1. Abrir configuracion.
2. Revisar hotel, catalogos, campos huesped, branding, PWA push devices.

Esperado: carga sin errores.

CONF-02 Persona B:

1. Guardar cambio no riesgoso de configuracion QA.

Esperado: cambio persiste.

CONF-03 Persona B:

1. Backup manual si se decide probar.

Esperado: archivo listado/descargable. No restaurar backup en este ciclo.

TARIFA-01 Persona B:

1. Abrir tarifas dinamicas.
2. Crear incremento/descuento QA temporal.

Esperado: tarifa creada y precio de reservacion/habitacion la refleja.

TARIFA-02 Persona B:

1. Editar/toggle/eliminar tarifa QA.

Esperado: cambios visibles y precio recalcula.

TARIFA-03 Persona B:

1. Previsualizar tarifa.

Esperado: preview responde sin guardar cambios.

### API-JS - Busqueda global, APIs AJAX y UI

API-01 Persona A:

1. Usar buscador global del sidebar.
2. Buscar huesped, reservacion y habitacion.

Esperado: resultados correctos y enlaces abren.

API-02 Persona A:

1. En crear reservacion, buscar huesped via autocomplete.

Esperado: resultados desde hotel actual.

API-03 Persona A:

1. En crear reservacion, cambiar fechas.

Esperado: habitaciones disponibles y precios se actualizan.

API-04 Persona B:

1. Notificaciones: abrir, marcar leida, resolver, descartar, marcar todas.

Esperado: contadores y estados cambian.

API-05 Persona B:

1. PWA push: consultar public key, suscribir/desuscribir/test si claves estan configuradas.

Esperado: si no hay VAPID, mensaje claro; si hay, flujo completo.

### PWA - Smoke funcional

PWA-01 Persona A:

1. Entrar en mobile o devtools mobile.
2. Revisar banner offline al cortar red.

Esperado: banner aparece; lectura basica no revienta.

PWA-02 Persona A:

1. Volver online.

Esperado: estado visual vuelve a normal.

PWA-03 Persona B:

1. Desde sesion autenticada, ejecutar en consola:

```js
fetch('/api/sync', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
  .then(async r => ({ status: r.status, body: await r.json() }))
  .then(console.log)
```

Esperado: `status: 423`, `body.error: "sync_temporarily_disabled"`.

PWA-04 Persona A:

1. Usar "Limpiar datos offline" desde menu.

Esperado: pide confirmacion y no borra datos del servidor.

### RESP - Responsive y accesibilidad funcional

RESP-01 Ambos:

1. Probar 390x844, 768x1024, 1366x768 y desktop ancho.

Esperado: no hay solapamientos bloqueantes.

RESP-02 Persona A:

1. Mobile recepcion: dashboard, habitaciones, huespedes, reservaciones/ver.

Esperado: sidebar, header movil, modales y formularios usables.

RESP-03 Persona B:

1. Mobile backoffice: caja, inventario, documentos, trabajadores, reportes.

Esperado: tablas tienen scroll o adaptacion, botones accesibles.

RESP-04 Ambos:

1. Navegacion con teclado basica en formularios.

Esperado: focus visible y orden razonable.

## 12. Flujo completo obligatorio

Este flujo lo hacen juntos y es el semaforo final.

1. B confirma caja abierta en Los Cedros.
2. A crea huesped QA con vehiculo.
3. A crea reservacion QA para manana o fecha disponible.
4. A genera cotizacion PDF.
5. A registra anticipo o pago si el flujo lo permite.
6. B valida movimiento en caja.
7. A hace check-in.
8. B valida inventario descontado si aplica.
9. A solicita factura o captura datos fiscales si aplica.
10. B completa flujo de facturacion hasta estado correspondiente.
11. A hace check-out.
12. B valida caja, CxC si se genero, reportes y ocupacion.
13. Ambos revisan que la habitacion queda en el estado esperado.
14. Ambos revisan que el flujo no aparece en otro hotel.

Resultado esperado:

- Reservacion completa sin errores.
- Habitacion cambia de estado correctamente.
- Caja refleja movimientos esperados.
- Facturacion queda consistente.
- Reportes reflejan informacion.
- No hay fuga cross-hotel.

## 13. Plantilla de reporte de bug

```md
### BUG-XXX - Titulo corto

Severidad: P0/P1/P2/P3
Persona:
Hotel:
Usuario/Rol:
Modulo:
URL:

Pasos:
1.
2.
3.

Resultado esperado:

Resultado real:

Evidencia:

Datos creados/IDs:

Notas:
```

## 14. Semaforo final

Antes de cerrar:

- [ ] Todas las pruebas P0/P1 ejecutadas.
- [ ] Flujo completo obligatorio aprobado.
- [ ] Segundo hotel revisado para tenant/modulos/branding.
- [ ] Mobile aprobado para recepcion.
- [ ] Exports/PDF principales aprobados.
- [ ] Formularios con errores preservan datos.
- [ ] `/api/sync` sigue en 423.
- [ ] Bugs P0/P1 resueltos o formalmente aceptados.
- [ ] Lista de datos QA creados documentada.
- [ ] Decision de limpieza de datos QA tomada.

## 15. Recomendacion de cierre

Cuando terminen, cerrar el ciclo con un resumen:

- Total casos ejecutados.
- PASS / FAIL / BLOCKED / NA.
- Bugs por severidad.
- Modulos aprobados.
- Modulos no aprobados.
- Riesgos aceptados.
- Decision: listo para siguiente fase, requiere correcciones, o requiere segunda ronda.
