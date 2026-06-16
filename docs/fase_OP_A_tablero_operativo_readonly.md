# Fase OP-A - Tablero Operativo Diario read-only

Estado formal: `TABLERO_OPERATIVO_OP_A_COMPLETADO_QA_DIFERIDA`.

## Objetivo

Implementar una vista GET/read-only para consolidar la operacion diaria del hotel actual
sin crear acciones nuevas ni modificar datos.

## Implementacion

- Ruta: `GET /operacion/diaria`.
- Controlador: `OperacionController::diariaAction`.
- Modelo: `OperacionDiaria::reporteReadOnlyPorHotel`.
- Vista: `app/views/operacion/diaria.php`.
- Preflight: `tools/saas/preflight_operacion_diaria.php`.
- Health general actualizado para reconocer OP-A.

## Alcance real

El tablero muestra:

- resumen de habitaciones por estado;
- llegadas, salidas, estancias activas y alertas de check-in/check-out;
- tareas activas, urgentes, vencidas y tareas prioritarias;
- mantenimiento en proceso/programado;
- trabajadores activos y trabajadores con tareas activas;
- documentos recientes como metadata segura.

## Seguridad

- Solo GET.
- Sin formularios.
- Sin POST.
- Sin CSRF porque no hay escritura.
- Consultas filtradas por `hotel_id`.
- No muestra `storage_path`.
- No usa `movimientos_caja`.
- No toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.
- Si falta alguna tabla, el modelo degrada a estados vacios.

## QA manual diferida

- Abrir `/operacion/diaria` con sesion de hotel.
- Confirmar que se ve el enlace en sidebar cuando `dashboard` esta activo.
- Confirmar que la ruta sin sesion bloquea o redirige.
- Confirmar que no hay formularios ni botones de accion.
- Confirmar que los enlaces van a detalle de reservacion, tarea y documento.
- Confirmar que no aparecen rutas internas de documentos.
- Confirmar que `/api/sync` sigue bloqueado por checker.

## Rollback

- Revertir commit `feat(phase-op): add read-only daily operations dashboard`.
- No hay rollback de DB porque OP-A no crea migraciones ni escribe datos.
- Si se retira, quitar ruta GET, controlador, modelo, vista, preflight, enlace sidebar y
  checks/documentacion OP-A.

