# Sistema visual y dirección estética

Sigue primero los tokens existentes. Usa estos criterios como valores por defecto cuando el proyecto no tenga una decisión explícita.

## Dirección

Busca una estética de producto refinada:

- moderna, limpia y humana;
- profesional sin ser fría;
- alta claridad con profundidad visual sutil;
- densidad adecuada a la tarea;
- una composición con foco, ritmo y respiración;
- decoración subordinada al contenido.

No persigas tendencias si debilitan legibilidad, velocidad o coherencia.

## Jerarquía

Cada vista debe contestar rápidamente:

1. ¿Dónde estoy?
2. ¿Qué información importa ahora?
3. ¿Qué puedo hacer?
4. ¿Qué cambió o requiere atención?

Usa tamaño, peso, contraste, posición y espacio antes que colores adicionales. En cards métricas, el valor domina; la etiqueta, periodo y tendencia lo contextualizan.

## Tipografía

- Mantén pocas escalas y pesos.
- Usa 16 px como base móvil cuando no exista token equivalente.
- Evita texto secundario menor a 14 px salvo metadatos no esenciales y accesibles.
- Mantén line-height cercano a 1.45–1.6 para lectura continua.
- Limita líneas de texto largo a unas 45–75 caracteres.
- Alinea a la izquierda párrafos de cuatro líneas o más.
- Evita usar mayúsculas sostenidas en frases largas.
- Permite wrapping; no trunques datos críticos sin acceso al valor completo.

## Espaciado

Si no hay escala, usa una base de 4 px y compón con 4, 8, 12, 16, 24, 32, 48 y 64.

- Espacio interno pequeño: 8–12.
- Padding estándar de controles/cards: 12–24 según densidad.
- Separación entre elementos relacionados: 8–16.
- Separación entre grupos: 24–32.
- Separación entre secciones: 32–64.

Usa el mismo gap horizontal y vertical en grids equivalentes. El espacio comunica relación: elementos cercanos pertenecen juntos; grupos distintos necesitan aire claro.

## Layout

- Diseña mobile-first.
- Usa containers y columnas consistentes.
- Mantén alineaciones compartidas entre header, contenido, filtros y acciones.
- Evita anchos máximos arbitrarios; elige según legibilidad y tarea.
- En desktop, aumenta información útil antes que tamaño decorativo.
- En móvil, prioriza el orden de lectura y el alcance del pulgar.
- No uses scroll horizontal salvo patrones donde sea deliberado y comprensible.

## Color

Mantén una paleta contenida:

- color de marca principal;
- acento opcional;
- escala neutral;
- colores semánticos para success, warning, error e info.

No uses rojo, verde o azul puros y saturados en grandes superficies si el producto no los exige. Prefiere tonos ajustados a la marca y con contraste suficiente.

El color semántico conserva el mismo significado en todo el sistema. No uses rojo como decoración si también significa error o destrucción.

En dark mode, evita contrastes abrasivos entre negro puro y blanco puro cuando neutrales tintados mantengan la legibilidad. No reduzcas contraste por estética.

## Cards, superficies y bordes

- Usa cards solo cuando exista una agrupación o acción común.
- Prefiere separadores, headings y espacio cuando una card no aporta estructura.
- Mantén bordes discretos y sombras suaves; combina ambos solo con intención.
- Usa pocos niveles de elevación.
- El radio interno debe ser menor que el radio exterior cuando un elemento queda contenido dentro de otro. Como guía, reduce 4–8 px.
- Evita anidar cards dentro de cards sin una razón funcional clara.

## Iconografía

- Usa el set existente y conserva grosor, tamaño y estilo.
- Alinea iconos ópticamente con texto y controles.
- Añade etiqueta visible o accesible cuando el significado no sea universal.
- No uses iconos distintos para la misma acción.
- No uses emojis como iconos de interfaz salvo que formen parte explícita de la marca o contenido.

## Imágenes

- Define `aspect-ratio` y dimensiones para evitar saltos.
- Usa crop consistente y `object-fit` apropiado.
- Proporciona fallback para imagen ausente o fallida.
- Sobre texto, añade overlay/gradiente/superficie que preserve legibilidad en cualquier imagen.
- No uses imágenes decorativas pesadas que compitan con la tarea.

## Motion

- Anima cambios de estado, continuidad espacial y feedback; no adornes por adornar.
- Usa duraciones breves, aproximadamente 120–240 ms para microinteracciones cuando no haya tokens.
- Conserva curvas y duraciones consistentes.
- Respeta `prefers-reduced-motion`.
- Evita mover controles que el usuario está intentando pulsar.

## Señales de calidad

Una pantalla refinada suele tener:

- un foco visual claro;
- pocos colores bien usados;
- tipografía con contraste de jerarquía;
- alineaciones precisas;
- ritmo de espaciado estable;
- estados interactivos visibles;
- detalles coherentes en bordes, iconos y motion;
- contenido real que no rompe la composición.
