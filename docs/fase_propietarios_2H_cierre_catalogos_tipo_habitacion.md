# Fase propietarios 2H - Catalogos visibles por hotel

## Objetivo

Evitar que los tipos legacy `Sencilla Manolo` y `Doble Manolo` aparezcan como opciones globales en pantallas operativas de hoteles que no los usan.

## Cambios aplicados

- Se agrego `hotel_room_catalog_types_for_select()` en `src/app/helpers/hotel_config.php`.
- El helper mantiene compatibilidad por hotel:
  - usa los tipos activos del catalogo del hotel;
  - conserva `sencilla_manolo` y `doble_manolo` solo si el catalogo los trae o si hay habitaciones activas de ese hotel con esos codigos;
  - no los inyecta como defaults globales.
- `src/app/views/reservaciones/crear.php` ahora usa el helper para construir las etiquetas de tipo de habitacion.
- El ordenamiento visual de habitaciones en reservaciones ya no depende de textos hardcodeados como `sencilla manolo` o `doble manolo`; agrupa cualquier variante de sencilla/doble sin jacuzzi.
- `src/app/controllers/InventarioController.php` usa el mismo helper para la matriz de descuentos por tipo de habitacion.
- `src/app/views/inventario/configuracion.php` ya no define estilos base para los tipos legacy como si fueran parte del catalogo estandar.

## Alcance protegido

No se tocaron migraciones, modelos, rutas, auth, calculos de caja, calculos de reportes, sync, service worker ni almacenamiento offline.

## Resultado esperado

En hoteles nuevos o sin esos tipos legacy, las pantallas operativas muestran solamente los tipos configurados para el hotel. En hoteles donde esos tipos ya existen como catalogo o como habitaciones reales, se siguen mostrando para no romper operacion historica.
