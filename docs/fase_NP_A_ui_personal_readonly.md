# Fase NP-A UI read-first de Personal

Estado: `PERSONAL_READ_ONLY_NP_A_COMPLETADO_QA_DIFERIDA`

## Objetivo

Exponer una primera capa de consulta para Personal basada en las tablas `trabajador*`,
sin crear todavia altas, edicion, pagos, anticipos, prestamos, asistencia operativa,
documentos laborales, integracion con Caja ni movimientos reales de dinero.

## Alcance implementado

- Modelo read-only: `src/app/models/Trabajador.php`.
- Controlador read-only: `src/app/controllers/TrabajadorController.php`.
- Rutas GET:
  - `GET /trabajadores`
  - `GET /trabajadores/{id}`
- Vistas:
  - `src/app/views/trabajadores/index.php`
  - `src/app/views/trabajadores/ver.php`
- Navegacion: enlace `Personal` en administracion, bajo guardas existentes de usuarios.
- Health checker actualizado para validar rutas, guardas, vistas sin POST y ausencia de Caja.

## Guardas

- `requireAuth()`
- `require_hotel_context()`
- `require_hotel_module('usuarios')`
- `require_permission('usuarios.view')`

El guard usa el contrato administrativo existente porque Personal contiene datos
laborales sensibles y aun no existe modulo/permisologia propia de nomina.

## Reglas de seguridad

- Todas las consultas filtran por `hotel_id`.
- La ficha busca por `id + hotel_id`.
- No hay rutas POST.
- No hay `INSERT`, `UPDATE` ni `DELETE`.
- No hay alta/edicion/baja de trabajador.
- No hay registro de pagos, anticipos, prestamos ni asistencias.
- No hay movimiento ni categoria de Caja/Nomina.
- No se toca `usuarios` ni `hotel_usuarios`.
- No se expone `ruta_archivo` de `trabajador_documentos`.
- No se toca `/api/sync`.

## Fuente de verdad

- Directorio laboral: `trabajadores`.
- Resumen laboral read-only:
  - `trabajador_pagos`
  - `trabajador_anticipos`
  - `trabajador_prestamos`
  - `trabajador_asistencias`
  - `trabajador_documentos`

Los saldos mostrados son lecturas agregadas; no son editables en esta fase.

## Definition of Done

- Rutas GET registradas.
- Controlador protegido por sesion, hotel, modulo `usuarios` y permiso `usuarios.view`.
- Modelo con consultas por `hotel_id`.
- Vistas con estados vacios claros.
- Sin POST ni CSRF porque no hay acciones de escritura.
- Sidebar actualizado de forma segura.
- Health checker detecta la subfase.
- `php -l`, health, SQL read-only, HTTP sin sesion y `git diff --check` ejecutados.

## QA manual diferida

Validar despues en navegador:

- `/trabajadores` carga para usuario autorizado.
- Sin trabajadores muestra estado vacio claro.
- Filtros GET no rompen la vista.
- `/trabajadores/{id}` muestra solo datos del hotel actual cuando existan registros.
- Usuario no autenticado redirige/bloquea.
- Usuario sin permiso de usuarios no accede.
- No aparecen botones de alta, editar, pagar, anticipo, prestamo, asistencia, Caja ni nomina.
- Caja y `/api/sync` permanecen sin cambios.

## Rollback

- Revertir el commit `feat(phase-np): add read-only worker directory`.
- No tocar base de datos para retirar esta subfase; las tablas pertenecen a NP-A schema.
- No borrar `trabajadores` ni tablas hijas si existen datos.
