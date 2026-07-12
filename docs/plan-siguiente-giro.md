# Plan maestro: el siguiente giro de negocio

**Fecha:** 2026-07-12 · **Origen:** examen final de Medisoft Hoteles (6.4/10 → Operación 10 ejecutada) · **Dueño:** Maximiliano

Este documento es TRES cosas a la vez:
1. El **contexto completo** que el nuevo proyecto necesita para nacer sabiendo todo lo que aprendimos aquí (sin repetir errores, sin doble trabajo).
2. El **plan de construcción** del nuevo sistema, diseñado para máxima velocidad, mejores prácticas y mínimo gasto de tokens.
3. El **prompt maestro listo para pegar** — idéntico para Claude y para Codex — con el protocolo para comparar sus respuestas y arrancar de la mejor combinación.

---

## 1. Candidatos de giro (para el estudio de mercado)

Criterio de selección: negocios PyME mexicanos con operación diaria parecida a un hotel — porque así **reutilizamos los módulos ya probados**: agenda/calendario, caja con cortes, personal + nómina, inventario, RBAC, white-label, PWA móvil, reportes. Entre más módulos reusa, más barato y rápido el desarrollo.

| Giro | Módulos que reusa | A favor | En contra |
|---|---|---|---|
| **Clínicas / consultorios** | Citas (=reservas), caja, personal/nómina, expediente (=huésped), recordatorios WhatsApp | Pagan bien, dolor real con agenda y cobranza, poca digitalización en MX | Tema regulatorio (datos de salud) exige más cuidado |
| **Salones de belleza / barberías / spas** | Citas, caja, comisiones por empleado (=nómina), inventario de productos, lealtad | Muchísimos locales, dueños jóvenes que sí adoptan apps, ticket recurrente | Ticket bajo por local; hay competencia (Booksy) pero cara y genérica |
| **Talleres mecánicos** | Órdenes de servicio (=reservas con estados), inventario refacciones, caja, CxC | Dolor enorme con seguimiento de órdenes y refacciones; casi nadie digitalizado | Flujo de orden más complejo que una cita |
| **Gimnasios / estudios (yoga, crossfit)** | Membresías recurrentes (=cobro SaaS ya construido), control de asistencia, caja | El módulo de cobro recurrente YA existe (SaasCobroService); churn visible = valor claro | Mercado con players (Fitpass, etc.) |
| **Escuelas / academias privadas** | Colegiaturas (=CxC ya construido), grupos, personal, comunicación a padres | CxC y recordatorios ya existen; cobranza es SU dolor #1 | Ciclo de venta más lento (deciden directores) |
| **Property managers / rentas vacacionales** | Casi TODO tal cual (es el giro hermano del hotel) | Reuso ~90%; canales iCal ya construidos | Es casi el mismo mercado; no diversifica |

**Mi recomendación para el estudio de mercado:** empezar por **clínicas/consultorios** y **salones de belleza** — máximo reuso, mercados enormes en México, y el white-label ya construido permite venderles "su" sistema con su marca. El estudio debe validar: disposición de pago mensual, quién decide la compra, y qué 3 funciones venden la primera demo.

---

## 2. Lecciones de Medisoft Hoteles (lo que el nuevo proyecto hereda)

### 2a. Lo que se REPITE porque funcionó (copiar sin discutir)

1. **Servicios de dinero con invariantes escritos y candados** — el patrón Caja (FOR UPDATE + re-verificar dentro del candado + cierre atómico) y Anticipos (dinero real ligado, nunca excede saldo, reverso único) es oro. En el nuevo sistema, TODO módulo de dinero nace con su invariante escrito Y su test.
2. **White-label por tokens de diseño** (`--brand-*`) + temas intercambiables + módulos activables con cobro à la carte (es_core siempre on). Es el modelo de negocio, no solo estética.
3. **RBAC configurable por tenant desde panel SaaS** (roles editables + presets).
4. **PWA instalable con offline** para el personal operativo + push con enrutamiento por rol.
5. **El patrón adaptador de giro ya existe** (`NominaAdaptadorGiro`/`Registry` en el código actual): la nómina ya sabe ser "de hotel" o "de otro giro". Ese patrón se generaliza: core agnóstico + adaptador por vertical.
6. **Documentar decisiones en memoria/docs cortos por tema** (no manuales gigantes) y **verificar E2E antes de dar por cerrado** (la regla "auditar antes de implementar").
7. **UX boutique como diferenciador**: toasts/confirm propios, microinteracciones con propósito, mobile-first real. Los clientes PyME compran por cómo se siente.

### 2b. Errores que NO se repiten (y qué se hace en su lugar)

| Error en Medisoft | Costo que tuvo | Regla para el nuevo proyecto |
|---|---|---|
| Tests llegaron al mes 8+ (Operación 10) | Meses de "verificar a mano" cada regresión; miedo a refactorizar | **Tests + CI en el commit #1.** Ningún módulo de dinero se mergea sin su test de invariantes |
| Tenancy por convención (hotel_id a mano en 544 queries) | 114 queries en deuda; auditorías enteras dedicadas a fugas | **Scoping automático del framework** (global scopes): imposible olvidar el filtro. El linter ratchet existe desde el día 1 |
| Framework casero (router/autoloader/todo a mano) | Cada pieza de infraestructura (CSRF, migraciones, colas) hubo que construirla y auditarla | **Framework con baterías** (ver stack §3): CSRF, migraciones, colas, validación y auth vienen resueltos y auditados por la comunidad |
| Vistas monolito (16,892 líneas) | Ediciones a ciegas, regresiones, tokens quemados leyendo archivos gigantes | **Tope duro: ningún archivo >800 líneas.** Componentes desde el día 1 |
| Migraciones a mano por PC | 500s fantasma por BDs desalineadas (pasó el 2026-07-11) | Runner de migraciones del framework, corre en deploy automáticamente |
| Sin staging ni pipeline | Todo se prueba en local y se reza | Staging + deploy con un comando desde la semana 1 |
| Trabajo pesado síncrono (WhatsApp, push, emails en el request) | Un canal lento le pega al usuario en caja | **Cola de trabajos desde el día 1** (viene con el framework) |
| Temas CSS vistiendo vista por vista (§24b, §41…) | Cada vista nueva requiere pase de diseño manual | **Sistema de componentes** (un solo "card/hero/botón" que los temas visten) |
| Dumps y artefactos conviviendo con el código | Confusión de qué es fuente y qué es basura | .gitignore estricto día 1; artefactos jamás al repo |

### 2c. Reglas de eficiencia de tokens (cómo trabajamos con la IA)

1. **CLAUDE.md del proyecto desde el commit #1** con: stack, convenciones, comandos de test/deploy, y el mapa de módulos. La IA no re-descubre el proyecto cada sesión.
2. **Archivos chicos** (tope 800 líneas) = lecturas quirúrgicas baratas.
3. **Convención sobre configuración**: con framework estándar, la IA ya "sabe" dónde va todo sin explorar.
4. **Un módulo = una sesión**: sesiones cortas y enfocadas; el contexto largo quema tokens y degrada calidad.
5. **Memoria del proyecto por temas** (como la de Medisoft): hechos no-derivables del código, en archivos de <30 líneas.
6. **Plantilla de módulo**: el primer módulo (p.ej. Citas) se construye perfecto y se convierte en la plantilla que la IA replica para los demás (menos diseño desde cero = menos tokens).
7. **La IA verifica con tests, no releyendo**: `php artisan test` de 5 segundos reemplaza 20 lecturas de archivos.

---

## 3. Stack recomendado (mi recomendación fuerte, aceptando el mandato de "mejores herramientas")

**Laravel 12 (PHP 8.3+) + MySQL 8 + Redis + Tailwind + Livewire/Alpine + Filament (panel admin) + Pest (tests) + Laravel Cloud o VPS Docker.**

Por qué Laravel y no repetir el framework casero ni saltar a otro lenguaje:

- **Es PHP**: todo lo aprendido (y el código de referencia de Medisoft) sigue siendo legible y portable conceptualmente. Cero curva de lenguaje.
- **Resuelve de fábrica exactamente lo que nos costó meses**: CSRF global (middleware), migraciones con runner, colas, scheduler (adiós crons a mano), validación, auth con roles (policies), rate limiting, testing integrado (Pest), Eloquent con **global scopes** (tenancy automático: `where tenant_id` en TODA query sin escribirlo).
- **Filament** da el panel SaaS de administración (tenants, módulos, cobros) en días, no meses.
- **La IA es dramáticamente más eficiente en Laravel**: hay tantísimo Laravel en su entrenamiento que produce código correcto a la primera, con menos contexto (= menos tokens, menos errores).
- **Multi-tenancy**: columna `tenant_id` + global scope (igual que Medisoft pero automático). Un solo esquema, una BD. `stancl/tenancy` solo si algún cliente exige BD separada.
- **Stripe** vía Laravel Cashier para el cobro SaaS recurrente (reemplaza SaasCobroService casero).

Herramientas de trabajo: **GitHub + Actions (CI día 1) · Claude Code (constructor principal) · Codex (segundo par de ojos, ver §5) · Sentry o similar gratis (errores en prod día 1) · UptimeRobot (monitoreo) · staging en subdominio.**

Decisión consciente: **NO** microservicios, **NO** React/SPA (Livewire da interactividad sin API paralela), **NO** Kubernetes. Monolito modular bien hecho — igual que Medisoft, pero con cimientos de fábrica.

---

## 4. Plan de construcción del nuevo sistema (fases)

> Regla de oro: cada fase termina con tests en verde en CI + demo funcional. No se abre la siguiente con la anterior a medias.

- **Fase 0 — Cimientos (1 sesión):** repo + Laravel + CI (lint/Pest/linter de tenancy portado) + staging + CLAUDE.md + tope de 800 líneas como regla escrita. *Aquí es donde Medisoft tardó 8 meses en llegar; el nuevo llega en el día 1.*
- **Fase 1 — Núcleo SaaS (el "core agnóstico de giro"):** tenants + global scope, auth + RBAC configurable, panel SaaS (Filament), white-label (tokens `--brand-*`, logo, paleta por tenant), módulos activables con precio. **Todo esto es traducción de Medisoft, no invención.**
- **Fase 2 — Módulo estrella del giro** (el que vende la demo; p.ej. Citas si es clínica/salón): construido como PLANTILLA perfecta — componentes, tests, permisos, mobile. Los demás módulos la copian.
- **Fase 3 — Dinero:** caja con cortes (portar invariantes + tests tal cual de Medisoft), pagos/anticipos, CxC. *Los tests se escriben ANTES que el código en esta fase.*
- **Fase 4 — Personal:** empleados, comisiones/nómina según el giro (portar el patrón adaptador).
- **Fase 5 — Comunicación:** WhatsApp/recordatorios (en cola, nunca síncrono), push, notificaciones por rol.
- **Fase 6 — Pulido boutique + PWA offline** (el lenguaje Deleite Sereno se porta como sistema de componentes, no vista por vista).
- **Fase 7 — Cobro SaaS (Cashier) + onboarding self-service del tenant.**

Estimación honesta con este stack y las lecciones aplicadas: **6-10 semanas de sesiones** hasta demo vendible (vs ~10 meses del ciclo hotel), porque las fases 1, 3, 4 y 5 son porteo de lógica ya diseñada.

---

## 5. Protocolo de trabajo con la IA (el modo "siguiente paso")

**Cadencia:** la IA siempre cierra cada respuesta con: *(a)* qué acaba de completar y su prueba (test/captura), *(b)* **EL SIGUIENTE PASO** (uno solo, concreto), *(c)* si algo es manual para Maximiliano, marcado como **[MANUAL]** con instrucciones exactas de clic por clic. Libertad total para ejecutar lo automatizable sin preguntar; solo se detiene en decisiones de negocio o acciones irreversibles.

**Protocolo dual Claude + Codex (arranque del proyecto):**

1. Pegar el **prompt maestro** (§6) idéntico en Claude y en Codex. Esperar ambas respuestas completas (plan detallado de Fase 0+1).
2. A cada uno, pegarle la respuesta del otro con esta instrucción exacta:
   > "Esta es la propuesta de otra IA para el mismo encargo. Compárala honestamente con la tuya: di qué hace mejor la otra, qué haces mejor tú, y produce una VERSIÓN FUSIONADA que tome lo mejor de ambas. Sé específico, no diplomático."
3. Con las dos fusiones en mano, quedarse con la más completa (o pedir a Claude una síntesis final de ambas fusiones) → ese es el **Plan v1.0** que se ejecuta.
4. Durante la construcción: Claude Code construye; cada fin de fase, Codex hace code review del diff como segundo par de ojos (`revisa este diff buscando bugs de dinero, fugas de tenant y regresiones`). Los hallazgos se arreglan antes de abrir la siguiente fase.

---

## 6. PROMPT MAESTRO (pegar tal cual en Claude y en Codex)

```
Eres el arquitecto y constructor principal de un nuevo SaaS multi-tenant white-label
para PyMEs mexicanas del giro: [GIRO ELEGIDO TRAS EL ESTUDIO DE MERCADO].

CONTEXTO HEREDADO (léelo como ley):
Venimos de construir Medisoft Hoteles: SaaS multi-hotel en PHP vanilla (50
controladores, 48 servicios, 182 vistas) con caja/cortes con candados de
concurrencia, anticipos ligados a caja, nómina con adaptador por giro, RBAC
configurable por tenant, white-label por tokens CSS (--brand-*), módulos
activables con cobro, PWA offline con push, y motor de reservas público.
Funciona y factura, pero el examen final lo calificó 6.4/10 por deudas
ESTRUCTURALES que en este proyecto quedan PROHIBIDAS desde el día 1:
- Sin tests ni CI hasta muy tarde → aquí: CI + tests desde el commit #1;
  ningún módulo de dinero se mergea sin test de invariantes.
- Multi-tenancy por convención (WHERE tenant_id a mano) → aquí: global scopes
  automáticos; una query sin scope debe ser imposible de escribir por accidente.
- Framework casero → aquí: Laravel 12 + MySQL 8 + Redis + Livewire + Filament
  + Pest + Cashier (Stripe). Monolito modular. NADA de microservicios ni SPA.
- Vistas de 16,000 líneas → aquí: tope duro 800 líneas/archivo, componentes.
- Migraciones a mano, sin staging, trabajo pesado síncrono → aquí: migraciones
  del framework en el deploy, staging desde la semana 1, colas para todo lo
  externo (WhatsApp, correos, push).
Lo que SÍ se replica de Medisoft: invariantes de dinero escritos y testeados
(un corte abierto por caja, cierre atómico, pagos nunca exceden saldo, reversos
únicos), white-label por tokens de diseño, módulos activables con precio,
RBAC por tenant, PWA mobile-first con UX boutique, y el patrón "core agnóstico
de giro + adaptador por vertical".

TU ENCARGO AHORA:
Produce el plan detallado y ejecutable de las Fases 0 y 1:
- Fase 0: repo, Laravel, CI (lint + Pest + linter de tenancy), staging,
  CLAUDE.md/AGENTS.md con convenciones, estructura de carpetas.
- Fase 1: modelo de datos multi-tenant (tenants, users, roles, módulos,
  branding), global scopes, panel admin Filament, white-label básico.
Para cada paso: qué archivo se crea/modifica, qué test lo protege, y qué
comando verifica que quedó bien. Marca [MANUAL] lo que el humano deba hacer
(crear cuentas, DNS, llaves) con instrucciones exactas.

MODO DE TRABAJO PERMANENTE:
Al final de CADA respuesta: (a) qué quedó hecho y su prueba, (b) EL SIGUIENTE
PASO (uno solo), (c) pendientes [MANUAL] si los hay. Tienes libertad total
para ejecutar sin preguntar todo lo reversible; pregunta solo decisiones de
negocio. Optimiza tokens: lecturas quirúrgicas, sesiones por módulo, verifica
con tests en lugar de relecturas.
```

---

## 7. Qué sigue (en orden)

1. **[MANUAL] Estudio de mercado** de los 2 giros recomendados (§1): 5 entrevistas por giro a dueños reales — ¿pagarían $X/mes? ¿qué 3 funciones les venden la demo? Con eso se llena el `[GIRO ELEGIDO]` del prompt.
2. **[MANUAL] Crear el repo nuevo** (privado) + pegar el prompt maestro en Claude y Codex (protocolo §5).
3. Mientras tanto, **este repo (hoteles) sigue su camino**: VPS + deploy (checklist listo) — es independiente y es el que ya factura.
4. El re-examen de Medisoft (post pasos manuales) alimentará la versión 1.1 de este documento si cambia alguna lección.
