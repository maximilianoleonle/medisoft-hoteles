# Decisiones de componentes y patrones

Elige el componente por la naturaleza de la decisión, no por hábito ni conveniencia de implementación.

## Controles de selección

### Binario

Usa switch cuando la acción cambia un estado de forma inmediata y el resultado es evidente: activar notificaciones, habilitar una función, mostrar una opción.

Usa checkbox cuando el usuario confirma una condición dentro de un flujo: aceptar términos, incluir un servicio, seleccionar una fila.

No uses select Sí/No.

### Pocas opciones

Con 2–4 opciones exclusivas, muestra todas mediante:

- radio group;
- segmented control;
- chips seleccionables;
- tarjetas compactas si cada opción necesita descripción.

Con cinco opciones, decide según longitud, espacio y frecuencia. Evita ocultar decisiones frecuentes si caben sin saturar.

### Valor conocido

Usa input para valores que el usuario conoce y puede escribir más rápido que buscar:

- folio, código, año, cantidad, habitación, teléfono, correo o identificador;
- números con `inputmode` y límites adecuados;
- máscaras solo cuando mejoran la captura y no bloquean pegar/editar.

### Lista larga

Usa combobox buscable con:

- label persistente;
- placeholder específico;
- búsqueda por atributos útiles;
- autocompletado y coincidencias resaltadas si ayuda;
- navegación por teclado;
- scroll y virtualización cuando corresponda;
- loading, vacío, error y limpiar selección;
- creación de nuevo registro solo si el flujo lo permite.

Permite escribir y hacer scroll; no obligues a una sola estrategia.

### Rangos

- Slider: exploración rápida y tolerancia aproximada.
- Dos inputs: precisión, accesibilidad y rangos amplios.
- Slider + inputs: filtros donde exploración y precisión importan.
- Date range picker: periodos de calendario.

Muestra unidad, límites y valor actual. Evita sliders cuando cada unidad es crítica o el rango es enorme.

### Selección múltiple

- Lista corta/media y etiquetas breves: chips/tokens.
- Lista larga: multiselect buscable con contador o resumen.
- Opciones complejas: checklist agrupado con búsqueda.

No conviertas una selección grande en una nube de chips imposible de escanear.

## Búsqueda y filtros

- El placeholder indica qué se puede buscar: “Buscar huésped, folio o habitación”.
- Mantén búsqueda visible en vistas donde es una acción principal.
- Usa filtros frecuentes como chips, segmented controls o controles inline.
- Agrupa filtros avanzados en drawer/popover accesible.
- Muestra filtros activos y permite retirarlos individualmente.
- Incluye “Limpiar filtros” cuando haya más de un filtro.
- Conserva filtros y posición al volver cuando el flujo lo espere.
- Distingue “sin datos” de “sin resultados con estos filtros”.

## Botones

### Primario

Una acción principal por contexto. Usa mayor contraste y etiqueta orientada al resultado.

### Secundario

Acciones de apoyo: cancelar, volver, previsualizar, guardar como borrador.

### Terciario

Acciones de baja prioridad: limpiar, ver historial, abrir ayuda. Usa button/link con affordance clara, no texto estático.

### Destructivo

- Etiqueta explícita: “Eliminar reservación”.
- Separa visualmente de acciones seguras.
- Confirma cuando sea irreversible o afecte datos relevantes.
- Explica el objeto y la consecuencia.
- Solicita texto de confirmación solo para riesgo excepcional.
- Ofrece deshacer cuando el backend y la integridad lo permitan.

## Formularios

- Labels visibles y asociación semántica.
- Help text antes del error; el error reemplaza o complementa sin causar salto excesivo.
- Campos relacionados agrupados con `fieldset`/`legend` cuando corresponda.
- Orden de tabulación igual al orden visual.
- Botón principal al final del flujo natural o sticky en móvil si no tapa contenido.
- Estado de envío dentro del botón o región cercana; evita reemplazar toda la pantalla.
- Resumen accesible para formularios largos con múltiples errores.
- No deshabilites el CTA sin explicar qué falta si la causa no es obvia.

## Tablas y listas

### Desktop

- Prioriza columnas esenciales.
- Encabezado sticky en tablas largas cuando ayuda.
- Alinea texto a la izquierda y números de forma consistente.
- Usa formato tabular para números comparables si la fuente lo permite.
- Incluye unidad, moneda, zona horaria o periodo.
- Coloca acciones frecuentes visibles y secundarias en menú.
- Soporta orden, filtros, selección y paginación solo cuando aporten valor.

### Móvil

Elige una estrategia deliberada:

- lista/card de resumen con detalles expandibles;
- columnas prioritarias y detalle en drawer/página;
- tabla con scroll horizontal solo si preservar comparación es esencial.

Nunca comprimas todas las columnas hasta volverlas ilegibles.

## Dashboards y métricas

- Muestra primero los indicadores que permiten decidir o actuar.
- El valor domina; label, periodo, delta y comparación lo contextualizan.
- Usa color de tendencia con icono/texto, no solo color.
- Evita gráficas decorativas sin escala, periodo o explicación.
- Permite acceder al detalle desde la métrica cuando exista.
- Define loading, datos incompletos y periodo sin información.

## Navegación

- Mantén ubicación y patrones estables.
- Usa 3–5 destinos en navegación inferior móvil cuando represente el nivel principal.
- Evita menús profundos y categorías ambiguas.
- Usa breadcrumbs solo para jerarquías reales, no como decoración.
- Mantén atrás/cerrar coherente con el tipo de superficie.
- No escondas la acción principal dentro de un overflow menu.

## Overlays

### Modal

Usa para decisiones focales, confirmaciones de riesgo o tareas breves que requieren contexto bloqueado.

### Drawer/sheet

Usa para filtros, detalles o tareas secundarias donde conviene conservar la pantalla de origen. En móvil, bottom sheet solo si el contenido y gesto son accesibles.

### Popover

Usa para opciones cortas y contextualizadas; debe cerrar con Escape, clic exterior y devolución de foco.

### Tooltip

Úsalo como ayuda complementaria, nunca como única forma de acceder a información esencial. Debe funcionar con hover y focus.

### Toast

Úsalo para confirmación no crítica y no bloqueante. No escondas errores que requieren acción dentro de un toast fugaz.
