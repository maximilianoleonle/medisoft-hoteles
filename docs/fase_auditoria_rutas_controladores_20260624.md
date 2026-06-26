# Fase Auditoria Rutas Controladores 2026-06-24

Objetivo: detectar rutas activas que apunten a controladores o acciones no resolubles por el router actual.

## Hallazgos corregidos

### `POST /reservaciones/check-out-parcial/{id}`

- Archivo: `src/config/routes.php`.
- Problema: la ruta tenia `action => checkOutParcialAction`.
- Efecto: el router agrega el sufijo `Action`, por lo que intentaba resolver `checkOutParcialActionAction`.
- Correccion: la accion queda como `checkOutParcial`.

### `GET /habitaciones/{id}/historial`

- Archivo: `src/app/controllers/HabitacionController.php`.
- Problema: la ruta apuntaba a `historial`, pero el metodo publico estaba declarado como `historial()`.
- Efecto: el router esperaba `historialAction()`.
- Correccion: el metodo se renombro a `historialAction()`.

### `POST /habitaciones/liberar-multiples`

- Archivo: `src/app/controllers/HabitacionController.php`.
- Problema: la ruta apuntaba a `liberarMultiples`, pero el metodo publico estaba declarado como `liberarMultiples()`.
- Efecto: el router esperaba `liberarMultiplesAction()`.
- Correccion: el metodo se renombro a `liberarMultiplesAction()`.

## Validacion esperada

- Todas las rutas activas registradas en `src/config/routes.php` deben resolver contra un archivo `*Controller.php`.
- Cada accion debe resolver contra un metodo publico `*Action`.
- No se agregaron rutas nuevas.
- No se modifico la logica interna de check-out, historial ni liberacion multiple; solo se alineo el contrato de despacho.

