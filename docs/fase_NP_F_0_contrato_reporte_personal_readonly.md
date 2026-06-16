# Fase NP-F-0 - Contrato reporte read-only de Personal

Estado: `CONTRATO_NP_F_REPORTE_PERSONAL_READONLY_COMPLETADO`

Fecha: 2026-06-16

## Objetivo

Definir un reporte read-only de Personal que consolide el estado operativo laboral por
hotel sin crear nomina, pagos, abonos, liquidaciones, movimientos de Caja ni cambios en
`/api/sync`.

Este contrato no implementa rutas, vistas, modelos ni migraciones. Solo delimita el
alcance seguro para una futura fase NP-F-A.

## Fuentes previstas

- `trabajadores`: ficha y estado laboral.
- `trabajador_pagos`: conceptos laborales informativos.
- `trabajador_anticipos`: anticipos informativos.
- `trabajador_prestamos`: prestamos informativos.
- `trabajador_asistencias`: asistencia manual.
- `documentos` y `documento_entidades`: documentos laborales modernos con
  `entidad_tipo = 'trabajador'`.
- `tareas_operativas`: tareas asignadas a trabajador, si la tabla existe y esta activa.

## Datos esperados del reporte

- Totales por estado de trabajador.
- Asistencias recientes o resumen por rango.
- Conceptos laborales por efecto: a favor, en contra y neutro.
- Anticipos y prestamos con saldos informativos.
- Documentos vinculados por trabajador.
- Tareas asignadas por estado.
- Alertas read-only de consistencia, sin corregir datos.

## Reglas obligatorias

- Solo GET/read-only en la primera implementacion.
- Filtro obligatorio por `hotel_id` de sesion.
- No aceptar `hotel_id` desde query o formulario.
- No crear, editar, anular ni borrar registros laborales.
- No generar nomina automatica.
- No crear pagos reales, abonos o liquidaciones.
- No crear movimientos de Caja ni categorias de Caja.
- No modificar tareas, documentos ni asistencia desde el reporte.
- No tocar `/api/sync`.
- No exponer rutas internas de documentos.

## Permisos y navegacion

- Reutilizar guardas existentes de Personal/Administracion.
- Si se agrega navegacion, debe ser un enlace GET y coherente con el sidebar actual.
- Si el modulo o permiso no esta disponible, debe bloquear o redirigir segun patron
  existente.

## Definition of Done futura NP-F-A

- Ruta GET protegida.
- Controlador read-only sin POST.
- Modelo/servicio con consultas scoped por `hotel_id`.
- Vista con estados vacios claros.
- Sin botones financieros ni acciones operativas.
- `php -l` en archivos PHP tocados.
- `preflight_personal_ledger.php` sin errores.
- `health_check_fase_1a.php` sin errores.
- SQL read-only confirmando que Caja, pagos reales y `/api/sync` no fueron tocados.
- HTTP sin sesion bloquea/redirige la ruta.

## Riesgos

- Confundir saldos laborales informativos con deuda pagable o salida de dinero.
- Mostrar documentos o rutas internas si se reutiliza informacion documental sin filtro.
- Mezclar tareas operativas con asistencia o nomina automatica.
- Abrir acciones POST desde el reporte por conveniencia.

## Rollback

- Revertir este documento si el contrato cambia.
- Una futura implementacion NP-F-A debe tener rollback propio.
- No limpiar datos laborales desde este bloque.

## Siguiente cola exacta recomendada

`[COLA_NP_F_A_REPORTE_PERSONAL_READ_ONLY]`
