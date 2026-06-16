# Fase MANT-B - Guardrails de mantenimiento inmediato existente

Estado: `MANTENIMIENTO_INMEDIATO_MANT_B_COMPLETADO_QA_DIFERIDA`

## Objetivo

Endurecer la accion existente `POST /habitaciones/{id}/mantenimiento` sin crear nuevas
funcionalidades ni nuevas rutas, manteniendo el flujo actual de iniciar/finalizar
mantenimiento desde la ficha de habitacion.

## Diagnostico

- La ruta ya existia y se usa desde `habitaciones/ver.php`.
- El controlador ya exigia POST, CSRF y permiso `habitaciones.mantenimiento`.
- `Habitacion` y `Mantenimiento` ya tienen `find/update/delete` scoped por `hotel_id`.
- Riesgos encontrados en la accion inmediata:
  - `accion` no tenia whitelist explicita.
  - `tipo_mantenimiento` y `prioridad` dependian del frontend.
  - `motivo` podia llegar vacio por POST manual.
  - Un POST repetido podia intentar iniciar mantenimiento duplicado.
  - Finalizar podia ejecutarse sobre una habitacion que no estuviera en mantenimiento.

## Cambios aplicados

- `mantenimientoAction()` normaliza `id`, `accion` y `hotelId`.
- Se valida `accion` contra `iniciar` y `finalizar`.
- Al iniciar:
  - valida `tipo_mantenimiento` contra `Mantenimiento::getTipos()`;
  - valida `prioridad` contra `Mantenimiento::getPrioridades()`;
  - exige `motivo`;
  - bloquea si la habitacion ya esta en estado `mantenimiento`;
  - bloquea si ya hay mantenimiento `en_proceso` para la misma habitacion/hotel;
  - registra el mantenimiento con valores validados.
- Al finalizar:
  - exige que la habitacion este en estado `mantenimiento`;
  - mantiene update scoped por `hotel_id`.
- Se agrega `src/tools/saas/preflight_mantenimiento_operativo.php`.
- Se actualiza el health general para validar MANT-B.

## Fuera de alcance

- No se crean rutas nuevas.
- No se cambian formularios ni actions existentes.
- No se crea automatizacion de mantenimiento programado.
- No se cambian reglas de reservaciones.
- No se toca Caja, pagos, abonos, nomina, offline ni `/api/sync`.

## Fuentes de verdad

- Habitacion: `habitaciones`.
- Mantenimiento historico/inmediato: `mantenimientos_habitaciones`.
- Hotel actual: `obtenerHotelIdActualCompat()`.

## Semaforo de riesgo

- Verde: CSRF, permiso y `hotel_id` ya presentes.
- Amarillo: existe historico donde una habitacion esta en mantenimiento sin registro
  `en_proceso`; se documenta como warning, no se corrige automaticamente.
- Rojo: duplicar mantenimientos `en_proceso`, iniciar sobre otro hotel o permitir tipo/
  prioridad fuera de catalogo.

## QA manual diferida

1. Abrir una habitacion disponible.
2. Iniciar mantenimiento con tipo, prioridad y motivo validos.
3. Confirmar que cambia a estado mantenimiento y aparece el panel activo.
4. Intentar repetir inicio desde navegador/POST manual y confirmar bloqueo limpio.
5. Finalizar mantenimiento y confirmar que vuelve a disponible.
6. Confirmar que no hay Caja, pagos, abonos ni `/api/sync`.

## Rollback

- Revertir el commit `fix(phase-mant): harden immediate maintenance action`.
- DB: no aplica; MANT-B no crea migraciones ni corrige datos historicos.
- Si se hicieron pruebas manuales que crearon mantenimientos, documentar IDs y revertir
  solo mediante flujo autorizado, no con SQL manual.
