# Auditoria de formularios de reservaciones - 2026-06-24

## Alcance

- Vista revisada: `src/app/views/reservaciones/crear.php`.
- Controlador revisado: `src/app/controllers/ReservacionController.php`.
- Revision limitada al contrato superficial del formulario de creacion de reservaciones.
- No se modificaron `action`, `method`, `name`, CSRF, hidden inputs ni estructura de formularios.
- No se tocaron archivos PWA/offline, rutas, modelos, base de datos ni logica profunda de reservaciones.

## Contrato detectado

Campos enviados por la vista:

- `csrf_token`
- `huesped_id`
- `fecha_entrada`
- `fecha_salida`
- `hora_llegada_modo`
- `hora_llegada`
- `habitaciones[]`
- `cortesias[]`
- `notas`

Campos leidos por `guardarAction()`:

- `huesped_id`
- `fecha_entrada`
- `fecha_salida`
- `hora_llegada_modo`
- `hora_llegada`
- `habitaciones`
- `cortesias`
- `notas`

Resultado: no faltaban `name` obligatorios en el contrato principal.

## Problemas encontrados

1. Recuperacion incompleta despues de error.

   Al fallar `guardarAction()`, el controlador guardaba `old_input`, pero la vista solo restauraba una parte de los datos. El huesped, habitaciones seleccionadas y cortesias podian perderse visualmente al regresar al formulario.

2. Habitaciones no recargadas con datos antiguos.

   Si el intento fallido volvia a `/reservaciones/crear` sin parametros de reservacion rapida, la lista de habitaciones no se cargaba automaticamente aunque existieran fechas en `old_input`.

3. Errores sin ubicacion por campo.

   El formulario mostraba un mensaje general, pero no errores bajo los campos principales. Esto hacia dificil identificar si fallaba huesped, fechas, hora o habitaciones.

## Correcciones aplicadas

- `ReservacionController::crearAction()` ahora recupera `huesped_id` desde `old_input` cuando no viene por URL.
- `ReservacionController::guardarAction()` guarda errores de formulario asociados al campo probable antes de redirigir.
- `reservaciones/crear.php` ahora muestra errores de campo para:
  - `huesped_id`
  - `fecha_entrada`
  - `fecha_salida`
  - `hora_llegada`
  - `habitaciones`
- `reservaciones/crear.php` ahora expone a JavaScript las habitaciones y cortesias anteriores para reactivar la seleccion.
- La carga inicial de habitaciones ahora tambien se ejecuta cuando hay `old_input`, no solo con parametros de reservacion rapida.

## Validaciones ejecutadas

```bash
docker compose exec -T app php -l app/controllers/ReservacionController.php
docker compose exec -T app php -l app/views/reservaciones/crear.php
```

Resultado: sin errores de sintaxis.

Tambien se verifico automaticamente el inventario del formulario:

- Formularios detectados: 1.
- Campos esperados faltantes: ninguno.
- Repeticiones revisadas manualmente:
  - `huesped_id` aparece como hidden cuando hay huesped preseleccionado o como select cuando se busca manualmente; no quedan ambos activos para el mismo caso.
  - `habitaciones[]`, `cortesias[]` y `notas` tambien aparecen dentro de selectores/plantillas JavaScript usadas por la misma vista.

## Riesgo residual

- La auditoria no cambio la logica profunda de disponibilidad, precios, check-in, check-out ni pagos.
- Si en el futuro se agregan nuevos campos obligatorios al flujo de reservaciones, conviene repetir este inventario para confirmar que la vista, el controlador y la recuperacion de errores sigan sincronizados.
