# Fase Auditoria Vistas Rutas 2026-06-24

Objetivo: revisar formularios, enlaces y referencias `url(...)` en vistas contra las rutas activas registradas en `src/config/routes.php`.

## Alcance validado

- Rutas activas: 157 `GET` y 132 `POST`.
- Formularios encontrados en vistas: 159.
- Formularios estaticos comparables: 73.
- Enlaces encontrados en vistas: 611.
- Enlaces estaticos comparables: 298.
- Llamadas `url(...)` estaticas comparables: 471.

Las URLs dinamicas con IDs concatenados se clasificaron aparte para evitar falsos positivos.

## Resultado operativo

No se encontraron formularios o enlaces estaticos rotos en vistas activas despues de clasificar falsos positivos.

## Hallazgos legacy no corregidos

### `app/views/inventario/configuracion-habitacion.php`

- Referencia: `POST /inventario/configuracion-habitacion/guardar`.
- Estado: no existe ruta activa registrada.
- Clasificacion: vista legacy desconectada.
- Decision: no cambiar `action` del formulario en esta fase. El flujo oficial es `GET /inventario/configuracion` y `POST /inventario/guardarConfiguracion`.

### `app/views/inventario/detalle.php`

- Referencias: `POST /inventarios/crear` y `GET /inventarios`.
- Estado: no existen rutas activas registradas.
- Clasificacion: vista legacy desconectada.
- Decision: no cambiar `action` del formulario en esta fase. El alta oficial de producto usa `GET /inventario/nuevo` y `POST /inventario/guardar`.

### `app/views/inventario/reportes.php`

- Referencias: `GET /inventarios`, `GET /inventarios/reportes`, `GET /inventarios/exportar-reporte` y `GET /inventarios/exportar-reporte-pdf`.
- Estado: no existen rutas activas registradas.
- Clasificacion: vista legacy desconectada.
- Decision: no reactivar sin migrar a `InventarioController` moderno y tablas `inventario_productos`/`movimientos_inventario`.

### `app/views/reportes/test-usuario.php`

- Referencia: `GET /reportes/test-datos`.
- Estado: ruta de prueba comentada/deshabilitada en `src/config/routes.php`.
- Clasificacion: vista de prueba legacy.
- Decision: mantener fuera del flujo operativo.

### `app/views/layout/header.php`

- Referencias: `window.API_URL = url('api')` y meta `api-url`.
- Estado: `/api` no es endpoint registrado.
- Clasificacion: prefijo/base informativo, no enlace ni formulario.
- Decision: no cambiar en esta fase porque no se detecto consumo directo como endpoint.

## Reglas para fases futuras

- No registrar rutas hacia vistas legacy de inventario sin migrarlas al contrato moderno.
- No cambiar `action`, `method`, `name`, CSRF ni hidden inputs de formularios legacy sin una fase especifica.
- Si se retira una vista legacy, hacerlo con busqueda de referencias, backup y validacion funcional del modulo.

