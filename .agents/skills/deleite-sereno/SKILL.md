---
name: deleite-sereno
description: Aplica el lenguaje visual "Deleite Sereno" de Medisoft — base premium serena (marfil cálido, serif de despliegue, sombras suaves) + microinteracciones de recompensa con propósito (check que se rellena, barra de progreso, destello, brillo que barre el botón, motivo contextual, anillo de selección, burst de éxito). Es white-label: TODO el cromado se deriva de --brand-* para que respete la paleta que elija cada hotel; los colores semánticos (éxito, limpieza, error…) se reservan para su significado. Úsala cuando el usuario pida que una pantalla, modal, tarjeta, formulario, estado de éxito/carga o flujo "se sienta agradable, pulido, con recompensa, satisfactorio, boutique o premium", o cuando quiera replicar el estilo del modal de limpieza. Preserva lógica, rutas, permisos y datos; implementa mobile-first y verifica el render.
argument-hint: "[pantalla, componente, modal o momento a deleitar]"
compatibility: Claude Code dentro del repo Medisoft Hoteles (PHP MVC a medida, vistas monolíticas, Tailwind por CDN). Agnóstica al componente; se apoya en los tokens --brand-* globales.
metadata:
  author: Maximiliano
  version: "1.0.0"
---

# Deleite Sereno — lenguaje de UI con recompensa para Medisoft

## Qué es este estilo (y cómo se llama de verdad)

Lo que te encantó del modal de limpieza no es un solo truco, es una **combinación** con nombre propio en la industria. Vale la pena conocer los términos reales para poder buscar referencias:

- **Micro-interactions** (Dan Saffer): pequeñas respuestas a cada acción del usuario.
- **"Juicy" UI / "juice"** (game feel): feedback positivo exagerado y satisfactorio — el check que hace *pop*, el número que sube, el brillo que barre. Es la "generación de recompensa" que mencionaste.
- **Moments of delight / Delightful design**: detalles que hacen sonreír sin estorbar.
- **Emotional / Affective design** (Don Norman): la capa visceral y reflexiva, no solo la funcional.
- **Positive feedback / reward loops**: refuerzo positivo al completar una tarea.
- **Choreographed / purposeful motion**: movimiento con intención, nunca decorativo.

En Medisoft lo empaquetamos como un lenguaje de casa: **"Deleite Sereno"**. La idea en una frase:

> **La calma es el lienzo; la recompensa es el acento.** Una base premium tranquila (marfil, serif, sombras suaves) sobre la que aparecen microrecompensas puntuales, satisfactorias y con propósito. Se siente limpio, ordenado y gratificante de usar — nunca ruidoso.

Referencia canónica implementada: el **modal "Marcar Habitaciones Limpias"** (`src/app/views/habitaciones/index.php`, bloque `#lm-cleaning-redesign`).

---

## Los dos pilares (no rompas el equilibrio)

1. **Base serena (95% de la superficie).** Marfil cálido, tipografía serif de despliegue para títulos/números, sans para el cuerpo, mucho aire, sombras suaves, jerarquía clarísima. Si quitaras todas las animaciones, debería seguir viéndose premium.
2. **Recompensa con propósito (5% de acento).** Microinteracciones que confirman, celebran o guían. Cada una responde a una acción real del usuario. Si una animación no comunica nada, **sobra**.

Regla de oro: **por cada momento de recompensa, tres superficies en calma.** Si todo brilla, nada brilla.

---

## White-label primero (esto es innegociable)

El hotel podrá elegir su paleta. Por eso **el cromado nunca se hardcodea**: se deriva de las variables globales de marca.

- `--brand-primary`, `--brand-secondary`, `--brand-accent` → header, CTA primario, acentos de marca. Fallback boutique navy/oro.
- **Colores semánticos se reservan para su significado** y NO cambian con la marca: éxito verde, limpieza azul (`--c-cleaning #2F77E0`), alerta ámbar, error rojo, ocupada terracota. Un check de éxito es verde aunque el hotel sea azul.
- Deriva tonos con `color-mix(in srgb, var(--brand-primary) N%, #fff|#000)` en vez de inventar hex sueltos.

> Prueba mental: si mañana el hotel cambia `--brand-primary` de olivo a vino tinto, ¿el componente se re-tematiza solo y sigue viéndose bien? Si no, hardcodeaste algo.

Ver [[white-label-palette-system]] para cómo se inyectan las vars y el hallazgo de fragmentación de paletas.

---

## Bloque de tokens (copia y adapta por componente)

Define tokens propios prefijados por componente, derivados de la marca. Esto además resuelve el gotcha de que muchos modales viven **fuera** del wrapper de la vista (donde los `--hb-*`/`--res-*` no resuelven):

```css
#miComponente{
  /* Marca (se re-tematiza por hotel) */
  --dx-brand: var(--brand-primary, #1B2746);
  --dx-brand-2: var(--brand-secondary, #0F172A);
  --dx-gold: var(--brand-accent, #BD9441);
  /* Semánticos (fijos por significado) */
  --dx-success:#1E9E63; --dx-success-soft:#E7F4EC;
  --dx-clean:#2F77E0;   --dx-clean-soft:#E6EFFC;
  --dx-warn:#C2841C;    --dx-error:#D64539;
  /* Superficies serenas */
  --dx-surface:#FFFFFF; --dx-surface-warm:#FCFAF5; --dx-ivory:#F6F2EA;
  --dx-line:#E7E1D4; --dx-line-cool:#D5E3F6;
  --dx-ink:#20293A; --dx-ink-soft:#5C6675; --dx-ink-faint:#8B94A3;
  /* Ritmo */
  --dx-radius:22px; --dx-radius-md:15px; --dx-radius-sm:11px;
  --dx-serif:'Cormorant Garamond', Georgia, 'Times New Roman', serif;
  --dx-shadow:0 30px 70px -28px rgba(20,40,80,.5), 0 8px 24px -16px rgba(20,40,80,.3);
  --dx-ease:cubic-bezier(.22,1,.36,1);
}
```

---

## Catálogo de momentos de recompensa (las primitivas)

Elige 2-3 por componente, no todas. Cada una debe responder a una acción.

**1. Check que se rellena (selección/confirmación).** Input nativo oculto + caja custom que hace *pop* al marcar. El "ajá" de completar.
```css
.dx-check{width:24px;height:24px;border-radius:8px;display:grid;place-items:center;background:#fff;border:2px solid var(--dx-line-cool);transition:all .18s var(--dx-ease)}
.dx-check i{opacity:0;transform:scale(.4);transition:all .18s var(--dx-ease);color:#fff}
.dx-input:checked + .dx-check{background:linear-gradient(135deg,var(--dx-clean),color-mix(in srgb,var(--dx-clean) 70%,#000));border-color:transparent;box-shadow:0 6px 14px -6px var(--dx-clean)}
.dx-input:checked + .dx-check i{opacity:1;transform:scale(1)}
```

**2. Barra de progreso que se llena (avance de una tarea).** Comunica "vas 3 de 8". Se actualiza por JS aditivo (`bar.style.width = pct + '%'`), nunca reescribiendo la lógica.
```css
.dx-progress{height:6px;border-radius:999px;background:var(--dx-clean-soft);overflow:hidden}
.dx-progress>span{display:block;height:100%;width:0;border-radius:999px;background:linear-gradient(90deg,var(--dx-clean),color-mix(in srgb,var(--dx-clean) 60%,#fff));transition:width .3s var(--dx-ease)}
```

**3. Brillo que barre el CTA (recompensa al hover del botón principal).** Un solo botón primario por vista.
```css
.dx-btn{position:relative;overflow:hidden}
.dx-btn__shine{position:absolute;inset:0 auto 0 0;width:40%;pointer-events:none;background:linear-gradient(100deg,transparent,rgba(255,255,255,.5),transparent);transform:translateX(-160%) skewX(-18deg)}
.dx-btn:hover:not(:disabled) .dx-btn__shine{transition:transform .7s ease;transform:translateX(320%) skewX(-18deg)}
```

**4. Destello / twinkle (marca de "premium", en emblemas).** Estrella pequeña con el oro de marca. Sutil, lenta.
```css
.dx-spark{width:14px;height:14px;background:linear-gradient(135deg,#fff,var(--dx-gold));clip-path:polygon(50% 0,60% 40%,100% 50%,60% 60%,50% 100%,40% 60%,0 50%,40% 40%);animation:dxTwinkle 2.4s ease-in-out infinite}
@keyframes dxTwinkle{0%,100%{transform:scale(.7) rotate(0);opacity:.6}50%{transform:scale(1) rotate(90deg);opacity:1}}
```

**5. Motivo contextual (identidad temática del componente).** Un detalle que "cuenta" de qué es la pantalla: burbujas de jabón para limpieza, un check que crece para éxito, monedas para caja. Siempre `aria-hidden`, detrás del contenido, discreto.

**6. Count-chip en el CTA + número que sube.** El botón muestra cuántos elementos afecta ("Marcar 3 como limpias"). Número de resultado en serif, tamaño grande, sobre su etiqueta pequeña.

**7. Anillo/tinte de estado seleccionado (`:has()`).** La tarjeta seleccionada se ilumina con un anillo de acento.
```css
.dx-item:has(.dx-input:checked){border-color:var(--dx-clean);background:var(--dx-clean-soft);box-shadow:0 0 0 1px var(--dx-clean) inset,0 12px 24px -16px color-mix(in srgb,var(--dx-clean) 60%,transparent)}
```

**8. Burst de éxito (momento de celebración al terminar un flujo).** Check grande que hace *pop* con un anillo que se expande + destellos. Verde semántico. Reservado para completar algo importante (check-in hecho, pago recibido, reserva creada). Uno por flujo.

**9. Lift al hover (tarjetas interactivas).** `transform:translateY(-1px)` + sombra fría suave. Todo lo clicable parece clicable.

---

## Movimiento

- Curva por defecto: `cubic-bezier(.22,1,.36,1)` (salida suave con leve overshoot).
- Duraciones: micro 120–180 ms; entradas de panel 260–340 ms; barridos 600–700 ms.
- Entrada de modal: `pop` en escritorio (`translateY + scale`), **hoja inferior** en móvil (`translateY(100%)→0`).
- **`prefers-reduced-motion: reduce` es obligatorio**: apaga burbujas/twinkle/shine y deja solo transiciones de opacidad/estado. Copia este bloque siempre:
```css
@media (prefers-reduced-motion: reduce){
  #miComponente [class*="__shine"], #miComponente .dx-spark, #miComponente .dx-bubble{animation:none!important}
  #miComponente .dx-bubble{display:none}
  #miComponente *{transition-duration:.01ms!important}
}
```

---

## Tipografía y superficies (la base serena)

- **Serif de despliegue** (`Cormorant Garamond`) para títulos de panel y NÚMEROS de resultado. Sans (`DM Sans`/`Outfit`) para cuerpo y controles.
- **Peso ≤ 700 siempre. Nunca 800/900** (se ve tosco). Ver [[boutique-font-weight-lighter]].
- **Sentence case**, microcopy del dominio, nombra el resultado en los botones ("Marcar como Limpias", no "Aceptar").
- Superficies **marfil cálido** (`--dx-surface-warm`/`--dx-ivory`), no blanco puro en grandes áreas.
- **Sombras suaves y frías**, nunca duras. Un solo borde: si es cálido rodeando un acento frío, mejor quítalo y usa una hairline fría por `box-shadow` (lección del bug de esquinas del header).
- **Radios anidados**: el radio del hijo < radio del contenedor. Header redondeado arriba igual que el diálogo.

---

## Accesibilidad y responsive (parte del "terminado")

- Targets táctiles ≥ 44×44px. Foco visible (`:focus-visible`) en cada control.
- Móvil (≤640px, mismo breakpoint de la app): modal → **hoja inferior** con agarradera, botones a ancho completo, `padding-bottom: max(16px, env(safe-area-inset-bottom))`.
- Nunca comuniques estado solo con color: acompaña con ícono/texto (badge "En limpieza" con escoba + texto).
- Un input real debajo de cada control custom (queda operable por teclado); estiliza el custom con `:checked`/`:focus-visible` del input.

---

## Gotchas técnicos del repo (te ahorran horas)

- **Componente fuera del wrapper de la vista** (modales al final del archivo): los tokens scopeados (`--hb-*`, `--res-*`) NO resuelven ahí. Define tus `--dx-*` en el propio `#id` derivados de `--brand-*` (globales). Ver [[white-label-palette-system]].
- **Gana la cascada por especificidad, no borres overrides ajenos.** Muchos componentes comparten reglas dispersas (p. ej. `#modalLimpieza` comparte selectores con `#vistaRapidaModal`). Prefija TODO con el `#id` del componente (`#miComponente .dx-...`) para ganar sin tocar lo demás.
- **Tailwind es por CDN**: usa clases utilitarias mínimas para posicionar el overlay y CSS propio namespaced para el diseño. No dependas de que un color utilitario mapee a la marca.
- **JS aditivo y defensivo**: para progreso/chips/estados extra, extiende las funciones existentes con guardas (`if (el) …`), sin cambiar el contrato (IDs, callbacks, CSRF, endpoints).

---

## Anti-patrones (qué NO hacer)

- No confeti/animación en cada elemento. El deleite se gasta si se abusa.
- No degradados apilados, glassmorphism, ni sombras neón por todos lados.
- No pesos 800/900, ni párrafos largos centrados, ni placeholder como label.
- No hardcodear la marca; no usar un color semántico como decoración (ni marca donde toca semántico).
- No animación sin significado ni sin `prefers-reduced-motion`.
- No romper lógica/rutas/permisos por estética. No modal para algo que cabe inline.

---

## Flujo de trabajo

1. **Audita** el componente objetivo y 1–2 vistas hermanas: tokens, dónde vive en el DOM, overrides que le afectan, funciones/IDs que debe preservar.
2. **Diseña la base serena** primero (jerarquía, tipografía, superficies, responsive). Debe verse bien sin una sola animación.
3. **Suma 2–3 momentos de recompensa** del catálogo, atados a acciones reales.
4. **White-label**: deriva todo de `--brand-*`; reserva semánticos.
5. **Verifica el render** (preview / harness estático con Tailwind + FA + marca real). Comprueba estilos computados de color/medida, estados (hover, seleccionado, disabled, éxito), y móvil (hoja inferior). Corre `php -l` si tocaste una vista.
6. **Informa**: qué cambiaste, decisiones de UX, verificaciones y limitaciones. Ofrece variantes de paleta si aplica.

## Definición de "terminado"

Base premium legible sin animación · 2–3 recompensas con propósito · white-label real (re-tematiza con `--brand-*`) · semánticos respetados · responsive con hoja inferior en móvil · `prefers-reduced-motion` · foco y teclado · sin overflow/clipping · verificado renderizado.

## Relacionadas
[[white-label-palette-system]] · [[boutique-font-weight-lighter]] · [[mobile-compact-redesign-pass]] · skill `pwa-ux-ui` (proceso general) · skill `medisoft-impeccable-ui`.
