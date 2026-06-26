# Quality gates

Ejecuta esta revisión antes de afirmar que el trabajo está terminado. Adapta los comandos y herramientas al repositorio.

## 1. Funcionalidad

- El flujo principal completa su objetivo.
- Rutas, enlaces, submits y redirecciones funcionan.
- Permisos, roles y feature flags se conservan.
- Validaciones de cliente y servidor siguen operando.
- Filtros, orden, paginación, selección y query params se mantienen.
- No hay doble submit ni acciones duplicadas.
- Los datos no se pierden ante error, navegación accidental u offline cuando el producto promete persistencia.

## 2. Estados

Revisa los aplicables:

- loading inicial;
- actualización parcial;
- vacío real;
- búsqueda sin resultados;
- error recuperable;
- error bloqueante;
- éxito;
- disabled;
- permisos insuficientes;
- offline;
- pendiente de sincronización;
- conflicto;
- contenido largo, faltante o extremo.

## 3. Responsive

Comprueba al menos:

- móvil compacto alrededor de 360 px;
- móvil común alrededor de 390 px;
- tablet alrededor de 768 px;
- desktop alrededor de 1024 px;
- desktop amplio alrededor de 1440 px.

En cada uno verifica:

- sin overflow horizontal accidental;
- contenido y acciones no cortados;
- barras sticky sin tapar contenido;
- navegación comprensible;
- tablas/listas adaptadas deliberadamente;
- imágenes y gráficos legibles;
- modales/drawers dentro del viewport;
- teclado virtual y safe areas cuando pueda probarse.

## 4. Accesibilidad

- Orden de headings lógico.
- Landmarks y semántica apropiada.
- Labels persistentes y asociados.
- Operación completa con teclado.
- Foco visible y devolución correcta tras overlays.
- Escape cierra overlays cuando corresponde.
- Targets táctiles de 44 × 44 CSS px o mayores.
- Contraste suficiente en texto, controles y focus.
- Estados no dependen solo de color.
- Iconos ambiguos tienen label o ayuda.
- Errores y cambios asíncronos se anuncian cuando corresponde.
- `prefers-reduced-motion` no rompe la experiencia.
- Zoom/texto aumentado no corta contenido crítico.

## 5. Calidad visual

- Acción primaria inequívoca.
- Dato importante domina su etiqueta.
- Gaps, padding y alineaciones son consistentes.
- No hay valores visuales arbitrarios sin token/patrón.
- Paleta y semántica de color son coherentes.
- Elementos clicables tienen affordance y estados.
- Texto largo está alineado y tiene ancho legible.
- Texto sobre imagen mantiene contraste en distintos contenidos.
- Cards, sombras y radios no están sobreutilizados.
- Iconografía pertenece al mismo sistema.
- No hay saltos de layout evitables.

## 6. Contenido

Prueba con:

- nombres muy largos;
- campos vacíos;
- números grandes y negativos;
- fechas y monedas locales;
- textos traducidos más extensos;
- imágenes ausentes;
- estados desconocidos o nuevos.

Confirma que mensajes y botones describen resultados y que la terminología del dominio es consistente.

## 7. Rendimiento

- No se agregaron dependencias innecesarias.
- Imágenes tienen dimensiones y tamaño apropiados.
- Listas extensas tienen estrategia de escala.
- Loading no borra datos útiles sin necesidad.
- No hay animaciones costosas o bloqueantes.
- No aparecen solicitudes duplicadas evitables.
- La carga conserva estabilidad visual.

## 8. Verificación técnica

Detecta y ejecuta los scripts existentes relevantes, por ejemplo:

- lint;
- typecheck;
- unit/integration tests;
- tests del componente o flujo;
- build de producción;
- E2E si existe y el entorno lo permite.

No inventes nombres de scripts. Lee el manifiesto y usa el gestor de paquetes correspondiente.

Inspecciona también:

- consola del navegador;
- errores de red relevantes;
- warnings del framework;
- hidratación si aplica;
- service worker/manifest si se tocó comportamiento PWA.

## 9. Revisión renderizada

Cuando exista entorno ejecutable:

- abre la pantalla real;
- completa el happy path;
- provoca al menos un error;
- revisa loading y vacío;
- navega con teclado;
- cambia viewport;
- compara con pantallas adyacentes;
- comprueba que la implementación se siente parte del mismo producto.

Si no pudiste ejecutar o renderizar, indícalo de forma explícita y no afirmes que la validación visual pasó.

## 10. Informe final

Usa un cierre breve:

### Resultado
Qué cambió y qué mejora para el usuario.

### Decisiones UX
Solo las decisiones relevantes o no obvias.

### Verificación
Comandos y pruebas ejecutados, con resultado real.

### Pendientes
Únicamente limitaciones, bloqueos o verificaciones que no fueron posibles. Omite la sección si no existen.

## Severidad para auditorías

- P0: pérdida de datos, seguridad, bloqueo total o acción crítica incorrecta.
- P1: flujo principal roto, inaccesible o muy confuso.
- P2: fricción importante, inconsistencia o error frecuente con workaround.
- P3: refinamiento visual, claridad o consistencia de bajo riesgo.

En auditorías, cita archivo/componente y evidencia concreta. Prioriza impacto sobre cantidad de hallazgos.
