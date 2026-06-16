# Fase NP-F-A - Reporte read-only de Personal

Estado: `REPORTE_PERSONAL_NP_F_A_COMPLETADO_QA_DIFERIDA`

Fecha: 2026-06-16

## Objetivo

Implementar un reporte GET/read-only de Personal que consolide trabajadores, ledger
laboral, asistencia, documentos laborales y tareas asignadas por hotel, sin crear nuevas
escrituras ni habilitar flujos financieros.

## Cambios aplicados

- Ruta GET protegida: `/trabajadores/reporte`.
- Accion `TrabajadorController::reporteAction()`.
- Metodo read-only `Trabajador::reporteReadOnlyPorHotel()`.
- Vista `trabajadores/reporte.php` sin formularios POST.
- Enlace GET "Reporte" desde el listado de Personal.
- Health checker y preflight laboral actualizados para reconocer NP-F-A.

## Reglas cumplidas

- Filtro por `hotel_id` desde contexto de sesion.
- No acepta `hotel_id` por query ni formulario.
- No crea, edita, anula ni borra trabajadores.
- No crea nomina automatica.
- No crea pagos reales, abonos ni liquidaciones.
- No crea movimientos de Caja ni categorias de Caja.
- No modifica tareas ni documentos.
- No toca `/api/sync`.
- No expone rutas internas de documentos.

## Datos mostrados

- Resumen de trabajadores por estado.
- Totales informativos de conceptos laborales.
- Saldos informativos de anticipos y prestamos.
- Resumen de asistencia manual.
- Conteo de documentos laborales modernos vinculados.
- Tareas asignadas por estado.
- Tabla de trabajadores con saldos laborales informativos.

## Verificacion esperada

- `php -l` en archivos PHP modificados.
- `health_check_fase_1a.php` con `ERROR: 0`.
- `preflight_personal_ledger.php` con `ERROR: 0`.
- HTTP sin sesion a `/trabajadores/reporte` bloquea o redirige.
- SQL read-only confirma que `trabajador_*`, Caja y `/api/sync` no fueron modificados.
- `git diff --check`.

## QA manual diferida

El usuario autorizo omitir QA manual temporalmente. Cuando se retome:

- Abrir `/trabajadores/reporte` con sesion de hotel.
- Confirmar estados vacios si no hay trabajadores.
- Confirmar que el reporte solo muestra informacion.
- Confirmar que no hay formularios POST, botones de pago, abono, nomina ni Caja.
- Confirmar link de vuelta a Personal y links a ficha de trabajador si existen registros.

## Rollback

- Revertir el commit `feat(phase-np): add read-only worker report`.
- DB: no aplica; la fase no crea migraciones ni escribe datos.
- No limpiar tablas `trabajador_*`, documentos, tareas ni Caja desde este rollback.

## Siguiente paso recomendado

Revision tecnica y auditoria de seguridad NP-F-A antes de abrir cualquier nueva escritura
laboral.
