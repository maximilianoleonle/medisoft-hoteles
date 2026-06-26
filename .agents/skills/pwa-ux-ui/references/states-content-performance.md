# Estados, contenido y rendimiento

## Modelo de estados

Para cada componente que depende de datos, define solo los estados aplicables, pero no omitas los previsibles.

### Carga inicial

Muestra skeleton con estructura parecida al contenido. Mantén dimensiones estables y evita simular datos exactos que puedan confundirse con contenido real.

### Actualización parcial

Conserva los datos existentes cuando sea seguro y muestra un indicador discreto. No vacíes toda la pantalla por cada refetch.

### Vacío real

Explica:

- qué falta;
- por qué importa;
- qué acción puede iniciar el usuario.

Ejemplo: “Aún no hay reservaciones para hoy” + acción pertinente. No uses ilustraciones grandes como sustituto de información.

### Sin resultados

Reconoce filtros o búsqueda activos y ofrece limpiar o modificar criterios. No lo presentes como ausencia total de datos.

### Error recuperable

Describe qué falló en lenguaje humano, conserva el trabajo y ofrece reintento o alternativa.

### Error bloqueante

Explica la consecuencia, siguiente paso y canal de soporte o retorno cuando exista. No muestres stack traces ni códigos internos como mensaje principal.

### Permisos insuficientes

Indica que la acción o dato requiere otro permiso sin revelar información sensible. Ofrece volver, solicitar acceso o contactar a quien corresponda.

### Éxito

Confirma el resultado concreto: “Pago registrado” o “Cambios guardados”. Mantén la confirmación cerca del contexto y no interrumpas innecesariamente.

### Disabled

Usa disabled solo cuando la acción no puede ejecutarse. Si la razón no es evidente, muestra ayuda inline o tooltip accesible. No uses disabled para ocultar validaciones que el usuario necesita conocer.

### Offline y sync

Diferencia “sin conexión”, “pendiente”, “sincronizando”, “falló” y “conflicto”. Conserva datos capturados y evita promesas falsas de guardado remoto.

## Mensajes de error

Un error útil contiene:

1. qué ocurrió;
2. qué campo o acción afecta;
3. cómo corregirlo o continuar.

Evita culpar al usuario.

Mal: “Contraseña inválida”.

Mejor: “La contraseña debe tener al menos 8 caracteres, una mayúscula y un número”.

No expongas detalles técnicos sensibles. Registra detalles en el canal técnico correspondiente y presenta un mensaje orientado a la tarea.

## Microcopy

- Usa verbos concretos y frases breves.
- Nombra acciones por su resultado.
- Mantén términos del dominio consistentes.
- Evita jerga técnica innecesaria.
- Explica consecuencias antes de acciones sensibles.
- Usa placeholders específicos: “Buscar huésped, folio o habitación”.
- No uses placeholder como label.
- Incluye unidad y formato esperado cuando ayude.
- Evita signos de exclamación y tono celebratorio excesivo en tareas rutinarias.

## Feedback por canal

### Inline

Errores de campo, ayuda contextual, progreso local y restricciones.

### Toast

Éxito breve o información no crítica que no requiere decisión. Mantén duración suficiente y alternativa accesible.

### Banner

Estado persistente o global: offline, mantenimiento, datos desactualizados, permisos o incidente.

### Modal

Decisión focal e importante. No uses modal para mensajes que podrían ser inline, banner o toast.

### Progress

Usa progreso determinado cuando se conoce; indeterminado cuando no. Para tareas largas, explica etapa, permite continuar en segundo plano o cancelar cuando sea seguro.

## Loading

- Skeleton para estructuras de contenido.
- Spinner pequeño para una acción local breve.
- Barra/progreso para tareas largas.
- Estado dentro del botón para submit.
- Evita bloquear toda la interfaz si solo cambia una región.
- Impide múltiples envíos sin desactivar navegación innecesaria.

## Optimistic UI

Úsala cuando:

- la operación suele tener éxito;
- es reversible;
- existe reconciliación clara;
- el error puede comunicarse y recuperar sin pérdida.

Evítala para pagos, borrados irreversibles, permisos, inventario crítico o acciones cuyo éxito deba confirmarse por servidor antes de mostrarse.

## Rendimiento

### Layout estable

- Reserva espacio de imágenes y contenido asíncrono.
- Evita cambios de altura bruscos por errores o help text.
- Mantén skeleton y contenido final con geometría similar.

### Recursos

- Usa formatos y tamaños de imagen apropiados.
- Carga diferida fuera del viewport cuando aporte valor.
- No precargues todo por defecto.
- Reutiliza iconos y fuentes existentes.
- Evita dependencias pesadas para una única interacción simple.

### Datos

- Pagina, busca en servidor o virtualiza según volumen real.
- Debounce solo entradas donde reduzca trabajo sin volver lenta la interacción.
- Cancela o ignora respuestas obsoletas de búsquedas rápidas.
- Conserva cache útil y comunica desactualización cuando sea relevante.

### Animación

- Anima propiedades eficientes.
- Evita blur, filtros y sombras animadas costosas en grandes áreas.
- No bloquees input esperando que termine una animación.

## Privacidad visual

- No muestres datos sensibles en previews, logs visuales, toasts o estados de error.
- En pantallas compartidas o móviles, evita revelar información innecesaria.
- Confirma que skeletons y mocks no contienen información real copiada.
