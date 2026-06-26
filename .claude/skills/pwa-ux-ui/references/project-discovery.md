# Descubrimiento del proyecto

Usa esta guía antes de implementar cualquier cambio visual o de interacción.

## 1. Identificar el entorno real

Localiza y lee, según existan:

- `CLAUDE.md`, `README*` y documentación de arquitectura;
- manifiesto y lockfile del gestor de paquetes;
- configuración del framework, bundler, TypeScript y lint;
- router, app shell, layouts y providers;
- manifest de PWA, service worker y estrategia de cache/sync;
- configuración de estilos: CSS global, Tailwind, Sass, CSS Modules, styled components, theme provider o equivalente;
- librería de componentes, primitives, iconos, gráficos y formularios;
- pruebas y scripts de verificación.

No asumas React, Vue, Svelte, Angular, Tailwind ni una librería concreta. Adáptate a lo encontrado.

## 2. Mapear el flujo afectado

Traza el recorrido del usuario y sus dependencias:

- ruta de entrada y salida;
- datos cargados y mutaciones;
- permisos, roles y feature flags;
- parámetros de URL, filtros y estado persistido;
- validaciones de cliente y servidor;
- analítica, telemetría o eventos;
- mensajes, redirecciones y efectos posteriores;
- comportamiento offline o de reintento.

Distingue claramente entre estilo, interacción, lógica de presentación y lógica de negocio.

## 3. Inventariar el lenguaje visual

Inspecciona al menos:

- dos pantallas similares o adyacentes;
- componentes compartidos que usan;
- tokens de color, tipografía, spacing, radius, shadow y motion;
- patrones de header, navegación, formularios, tablas, cards, dialogs, toast y loading;
- breakpoints, containers y densidad;
- estados hover, focus, active, disabled, error y success;
- tema claro/oscuro si existe.

El código existente es la fuente visual principal. Esta skill corrige inconsistencias, no crea otra marca dentro del producto.

## 4. Crear un contrato de diseño interno

Antes de editar, resume mentalmente o en un plan breve:

- primitives que reutilizarás;
- tokens válidos;
- patrón de pantalla elegido;
- acción primaria;
- estados requeridos;
- comportamiento móvil y desktop;
- invariantes funcionales que no deben cambiar;
- comandos de verificación.

## 5. Decidir cuándo preguntar

Pregunta únicamente si la respuesta cambia producto, datos o seguridad, por ejemplo:

- qué rol puede ejecutar una acción;
- si una operación es reversible;
- cuál es la fuente de verdad de un dato;
- qué comportamiento offline debe prometerse;
- si una nueva dependencia o cambio de contrato está permitido.

No bloquees el trabajo por decisiones visuales menores que puedan inferirse de componentes y patrones existentes.

## 6. Si no existe un design system

Crea una base mínima dentro de la arquitectura actual, no una plataforma paralela:

- tokens semánticos centralizados;
- primitives necesarias para el cambio actual;
- estados completos;
- API simple y reutilizable;
- documentación breve junto al código cuando sea útil.

Evita construir una librería completa si el alcance solo requiere dos o tres primitives.

## 7. Preservación funcional

Antes y después del cambio, comprueba:

- rutas y enlaces;
- envío de formularios;
- validaciones y mensajes del servidor;
- permisos y estados ocultos/deshabilitados;
- filtros, orden, paginación y query params;
- selección, edición y acciones masivas;
- foco, scroll y retorno a la vista anterior;
- datos pendientes y recuperación tras error;
- pruebas existentes relacionadas.
