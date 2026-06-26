# PWA, responsive y accesibilidad

## Mobile-first real

Diseña primero el flujo táctil, no una versión reducida del desktop.

- Prioriza contenido y acciones por frecuencia e impacto.
- Mantén targets táctiles de al menos 44 × 44 CSS px como estándar del producto.
- Separa targets adyacentes para reducir pulsaciones accidentales.
- Sitúa CTAs frecuentes dentro de una zona cómoda para el pulgar cuando el flujo lo permita.
- Evita gestos como única vía de acción; ofrece botón o menú equivalente.
- No dependas de hover.

## Safe areas y viewport

- Usa `env(safe-area-inset-*)` en barras y acciones pegadas a bordes.
- Prefiere unidades dinámicas como `dvh` cuando `100vh` provoque cortes por barras del navegador.
- Comprueba navegación en modo browser y standalone.
- Evita que bottom bars, teclados o banners tapen campos y CTAs.
- Mantén contenido accesible al rotar el dispositivo.

## Teclado virtual y formularios

- Desplaza o revela el campo enfocado sin saltos agresivos.
- Mantén el CTA visible o alcanzable sin superponerse al teclado.
- Usa `inputmode`, `autocomplete`, `enterkeyhint` y tipos nativos adecuados.
- Permite pegar, seleccionar, corregir y usar gestores de contraseñas.
- No bloquees zoom para “arreglar” el layout.

## Breakpoints

Usa breakpoints guiados por el punto donde el contenido deja de funcionar. Como matriz mínima de revisión, prueba alrededor de:

- 360–390 px: móvil compacto/común;
- 768 px: tablet o layout intermedio;
- 1024 px: desktop pequeño;
- 1440 px: desktop amplio.

No diseñes solo para esos anchos; comprueba también el comportamiento entre ellos.

## Navegación responsive

- Conserva el mismo modelo mental aunque cambie la representación.
- Bottom navigation: 3–5 destinos principales, labels visibles y estado activo claro.
- Sidebar: útil en desktop para áreas persistentes y jerarquía estable.
- Menú hamburger: reserva para destinos secundarios o cuando el espacio lo exige.
- Mantén acciones de contexto cerca del contenido al que afectan.
- Preserva posición, filtros y selección al navegar y volver cuando sea esperable.

## PWA standalone

- No asumas que siempre existe barra del navegador o botón atrás visible.
- Proporciona navegación y cierre internos coherentes.
- Evita enlaces que saquen al navegador sin advertencia cuando rompen el flujo.
- Comprueba color de tema, fondo, splash y áreas del sistema según la implementación existente.

## Offline y sincronización

Distingue visual y semánticamente:

- sin conexión;
- datos cacheados o potencialmente desactualizados;
- cambio guardado localmente y pendiente de sincronización;
- sincronización en curso;
- sincronización fallida;
- conflicto entre versión local y remota.

Muestra última sincronización cuando sea relevante. No confirmes “Guardado” si solo está pendiente localmente; usa “Guardado en este dispositivo” o equivalente.

Ofrece reintento y conserva el trabajo del usuario. No descartes datos introducidos por un fallo de red.

## Instalación y actualización

- Ofrece instalación después de demostrar valor, no como interrupción inicial automática.
- Explica el beneficio de instalar en el contexto del producto.
- Comunica que hay una actualización disponible.
- No recargues automáticamente durante captura, pago, edición o cambios sin guardar.
- Permite actualizar en un momento seguro y confirma cuando sea necesario reiniciar.

## Semántica

- Usa elementos HTML nativos antes que roles ARIA personalizados.
- Mantén landmarks, headings y estructura lógica.
- Cada control necesita nombre accesible y estado comprensible.
- Asocia labels, descripciones y errores mediante atributos correctos.
- Usa botones para acciones y enlaces para navegación.
- No hagas clicable un `div` si puede ser un control nativo.

## Teclado y foco

- Todo flujo debe operar con teclado.
- Mantén foco visible con contraste suficiente.
- El orden de foco sigue el orden visual y lógico.
- Al abrir overlay, mueve foco dentro; al cerrar, devuélvelo al disparador.
- Evita trampas de foco salvo en modales correctamente implementados.
- Tras un error o navegación parcial, coloca el foco donde ayude a continuar.
- Soporta Escape cuando el patrón lo espera.

## Contraste y percepción

Como base compatible con WCAG AA:

- texto normal: relación de contraste mínima de 4.5:1;
- texto grande: 3:1;
- límites y estados de componentes esenciales: 3:1 frente al entorno relevante.

No uses solo color para éxito, error, selección o tendencia; añade texto, forma o icono. Comprueba estados disabled sin volverlos indescifrables.

## Lectores de pantalla

- Anuncia errores, resultados y cambios asíncronos relevantes mediante regiones live apropiadas.
- Evita anuncios repetitivos durante escritura.
- Proporciona texto alternativo útil; usa `alt=""` para imágenes puramente decorativas.
- Incluye conteos, unidades y contexto en nombres accesibles cuando el visual dependa de ellos.
- En tablas, conserva headers y asociaciones.

## Preferencias del usuario

- Respeta `prefers-reduced-motion`.
- Mantén usabilidad con zoom y tamaños de texto mayores.
- Soporta temas del sistema solo si el producto los implementa correctamente.
- No fuerces densidad extrema ni texto truncado ante escalado.

## Localización y datos

- Usa formato local de fecha, hora, número y moneda.
- Explicita zona horaria cuando afecte decisiones.
- Diseña para traducciones más largas y nombres extensos.
- Evita concatenar fragmentos que sean difíciles de traducir.
- Mantén lenguaje consistente para la misma entidad en todo el producto.
