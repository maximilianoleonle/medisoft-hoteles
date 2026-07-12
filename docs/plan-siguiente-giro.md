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

DISEÑO Y TEMAS (reglas permanentes):
- Componentes usan SOLO tokens semánticos (--superficie, --tinta, --acento...);
  un tema = un archivo que re-mapea tokens; jamás selectores por vista; el CI
  rechaza hex directos fuera de /themes. El white-label (--brand-*) del tenant
  convive por encima del tema. Modo oscuro = un re-mapeo más.
- Tú NO diseñas pantallas de usuario final de cero: cuando toque una, generas
  el paquete de encargo en design/encargos/<pantalla>/ (PROMPT.md listo para
  Claude Design + referencias + datos-ejemplo.json + contrato.md con lo
  intocable) y lo marcas [DISEÑO] para el humano. Sigues con backend mientras
  tanto; al volver la entrega, la porteas a componentes y validas el contrato.

MODO DE TRABAJO PERMANENTE:
Al final de CADA respuesta: (a) qué quedó hecho y su prueba, (b) EL SIGUIENTE
PASO (uno solo), (c) pendientes [MANUAL] o [DISEÑO] si los hay. Tienes
libertad total para ejecutar sin preguntar todo lo reversible; pregunta solo
decisiones de negocio. Optimiza tokens: lecturas quirúrgicas, sesiones por
módulo, verifica con tests en lugar de relecturas.
```

---

## 7. Catálogo completo de secciones (lo que YA existe y lo que se puede AUMENTAR)

Este catálogo es el menú del nuevo sistema: si un giro necesita una sección "así o similar", aquí está la receta. **Regla:** nada de esto se construye por adelantado — se construye cuando un giro lo pide, pero ya sabiendo cómo.

### 7a. Lo que YA existe en Medisoft (probado en producción, se PORTA no se inventa)

| Sección | Qué hace hoy | Nota de porteo al nuevo giro |
|---|---|---|
| Auth + sesiones | Login por tenant (slug), remember-me ligado al tenant, rate limit persistente | Lo da Laravel casi gratis; portar solo el "login por slug" |
| RBAC configurable | Roles por tenant editables desde panel SaaS, permisos finos, presets | Portar el modelo de permisos; policies de Laravel |
| Panel SaaS admin | Alta de tenants, módulos activables con precio, cobro mensual (Stripe), branding | Filament lo acelera 10x |
| White-label/branding | Logo, paleta `--brand-*`, favicon, manifest PWA por tenant | Se porta el concepto tal cual |
| Agenda/Reservas | Calendario, disponibilidad, estados, wizard móvil, descuentos, anticipos | El corazón reusable: cita médica = reserva sin habitación |
| Ficha del cliente | Huésped: datos, documentos, vehículos, historial, lealtad | = paciente/cliente/alumno según giro |
| Caja + cortes | Cortes con candados de concurrencia, arqueo por método, categorías por tenant | Portar CON sus tests (ya escritos) |
| Anticipos/abonos | Dinero real ligado a caja, saldo-aware, reversos únicos | Portar CON sus tests |
| CxC / CxP | Cuentas por cobrar/pagar, simuladores de caja, reversiones | = colegiaturas, pólizas, proveedores |
| Compras + proveedores | Órdenes, recepción, reporte | Genérico ya |
| Inventario | Stock, movimientos, config por "ubicación" (habitación) | = productos de salón, refacciones, insumos clínica |
| Personal + Nómina | Empleados, incidencias, periodos, recibos, **adaptador por giro ya existente** | = comisiones de estilistas, honorarios médicos |
| Tareas operativas | Limpieza con personal obligatorio, programación | = esterilización, mantenimiento de equipos |
| Notificaciones + push | Centro de avisos, PWA push con enrutamiento por rol | Genérico ya |
| WhatsApp | Recordatorios/avisos por tenant (Green-API) | Recordatorio de cita = oro en clínicas/salones |
| Reportes + links públicos | Gerencial diario, por email, links firmados que expiran | Genérico ya |
| Motor público de venta | Página pública por tenant: disponibilidad, cupones, extras, pago Stripe, holds | = agenda pública de citas con apartado pagado |
| Check-in digital | Formulario público pre-llegada con token efímero | = pre-registro de paciente |
| Reputación | Encuesta post-servicio pública + score | Genérico ya |
| Lealtad | Puntos/beneficios por cliente | Genérico ya |
| Canales iCal | Sync con calendarios externos | Solo si el giro lo pide |
| Copiloto IA | Asistente con datos del negocio (API Anthropic, se revende) | Diferenciador de venta: portar |
| Night audit / cierre | Cierre diario automático | = corte de día genérico |
| Auditoría + vigilancia | Log de acciones, alertas financieras | Genérico ya |
| Health + backups + offsite | /health con token, dumps verificados, rclone | Infra: se porta la receta |

### 7b. Lo que NO existe y se puede AUMENTAR (con su receta corta)

| Sección nueva | Para qué giro brilla | Receta (cómo se haría) |
|---|---|---|
| **Expediente clínico** (notas SOAP, recetas, alergias) | Clínicas | Ficha del cliente + versionado de notas + PDF de receta (TCPDF ya dominado); cifrado at-rest de campos sensibles |
| **Membresías/suscripciones del cliente final** | Gimnasios, spas | Cashier ya cobra recurrente al TENANT; replicar el patrón hacia el cliente del tenant |
| **Control de acceso QR/asistencia** | Gimnasios, escuelas | QR firmado por cliente + scanner PWA (cámara) + log de accesos |
| **Comisiones por empleado/servicio** | Salones | Ya hay motor de nómina con adaptador: nuevo adaptador "comisiones" (% por servicio cobrado en caja) |
| **Órdenes de servicio con estados y evidencia** | Talleres | Reserva + checklist + fotos (uploads ya resuelto) + firma del cliente en pantalla |
| **Facturación CFDI real (timbrado MX)** | Todos (México) | Integrar PAC (Facturama/SW Sapien API); el módulo Facturación actual ya guarda los datos fiscales |
| **Portal del cliente final** | Todos | Motor público ya existe; ampliarlo a "mi cuenta": historial, pagos, próximas citas |
| **Campañas WhatsApp/marketing** | Salones, gimnasios | Cola + plantillas + segmentos (clientes sin visita en 60 días); cuidar límites de Green-API |
| **Multi-sucursal por tenant** | Cadenas | tenant_id + sucursal_id (2 niveles); diseñar la columna desde Fase 1 aunque no se use |
| **API pública + webhooks salientes** | Integraciones | Laravel Sanctum + eventos; solo cuando un cliente grande lo pida |
| **Importadores CSV** (clientes, inventario) | Onboarding | Vale ORO para migrar clientes desde Excel; hacerlo en Fase 2 |
| **Constructor de reportes / BI ligero** | Dueños | Empezar con 5 reportes fijos bien hechos; el "constructor" casi nunca se usa — no construir de inicio |
| **Multi-idioma / multi-moneda** | Futuro | Laravel lo trae (lang files); decidir en Fase 0 si los textos nacen en archivos de idioma (sí, cuesta poco) |

---

## 8. Diseño: sistema de temas + protocolo Claude Design

### 8a. Temas listos para crecer (Maximiliano los crea; la arquitectura los hace fáciles)

Lección de Medisoft: los temas se volvieron caros porque visten VISTA por VISTA (§24b, §41 de cupertino.css). En el nuevo sistema, **agregar un tema = crear UN archivo**:

1. **Todo componente usa solo tokens semánticos** (`--superficie`, `--tinta`, `--acento`, `--radio`, `--sombra-1`…). Prohibido un hex directo en una vista — el linter de CI lo rechaza (grep de `#[0-9a-f]{3,6}` fuera de /themes).
2. Un tema es un archivo que **solo re-mapea tokens** (`themes/cupertino.css`, `themes/deleite.css`). Nunca selectores por vista.
3. `--brand-*` (white-label del tenant) vive POR ENCIMA del tema: el tenant elige tema Y paleta, y ambos conviven — igual que hoy, pero sin fragmentación.
4. Registro de temas en BD (`themes` table: clave, nombre, archivo, preview) + selector en el panel del tenant → agregar tema no toca código, solo suma el archivo CSS y su fila.
5. **Modo oscuro es un re-mapeo más**, no un tema aparte (lección del dark-theme.css actual).

### 8b. Protocolo Claude Design (cuándo y cómo la IA te pide diseño)

El flujo que ya usaste con habitaciones/reservaciones (exports → Claude Design → porteo) se vuelve el proceso oficial. **La IA constructora NO diseña pantallas de cero: te prepara el encargo y tú lo corres en Claude Design.**

Cuándo la IA dispara el protocolo — al llegar a cualquier pantalla nueva "de cara al usuario" (no formularios internos triviales), te dice:

> **[DISEÑO]** Toca diseñar la pantalla X. Te dejé el paquete en `design/encargos/X/`. Córrelo en Claude Design y regrésame el HTML/capturas a `design/entregas/X/`.

Qué contiene el paquete que la IA te genera (auto, cada vez):
1. `PROMPT.md` — el prompt listo para pegar en Claude Design: objetivo de la pantalla, datos que muestra, acciones del usuario, breakpoints (móvil primero), y las reglas de tokens (§8a) para que lo que devuelva ya use `--superficie/--acento` y sea porteable sin re-trabajo.
2. `referencias/` — 1-2 vistas ya existentes del sistema (el lenguaje visual vigente) + captura del tema activo.
3. `datos-ejemplo.json` — datos reales de muestra para que el diseño no sea lorem ipsum.
4. `contrato.md` — qué NO puede cambiar: rutas, nombres de campos, permisos, componentes obligatorios (tabla, toast, confirm).

Qué haces tú (el humano): pegar PROMPT.md en Claude Design, iterar el gusto visual (tu criterio), y soltar el resultado en `design/entregas/X/`. La IA constructora lo portea a componentes reales, verifica contra `contrato.md`, y pasa el linter de tokens. **Diseñar nunca bloquea la construcción:** la IA sigue con lógica/backend mientras el diseño está contigo.

---

## 9. Cómo trabajar para sacar sistemas así MÁS RÁPIDO (la parte realista)

Medisoft tomó ~10 meses. El objetivo realista del siguiente: **demo vendible en 6-10 semanas, primer cliente pagando en el mes 3.** Qué tiene que cambiar en la forma de trabajo (no solo en el código):

1. **El orden de construcción se invierte.** En hoteles se construyó producto 8 meses y calidad al final (Operación 10). Ahora: Fase 0 (tests+CI+staging) es LA PRIMERA sesión. Costo: 1 día. Ahorro: los meses de re-verificación manual que ya viviste.
2. **Vender antes de construir.** El estudio de mercado (§1) no es trámite: si 10 dueños no dicen "yo pago por eso", no se escribe ni una línea. La demo de venta se hace con la Fase 2 (UN módulo estrella), no con el sistema completo. Primer cliente de diseño = primer cliente de verdad con descuento vitalicio a cambio de feedback semanal.
3. **Sesiones de IA cortas y con un solo objetivo.** Las sesiones maratón queman tokens y acumulan errores de contexto. Ritmo bueno: 1 sesión = 1 módulo o 1 fase chica, con su test verde y su commit al cierre. Tu rol en cada sesión: dar el objetivo al inicio, decidir lo de negocio a la mitad, probar como usuario final al cierre — 20 min tuyos por sesión de la IA.
4. **Tu tiempo va donde la IA es débil:** hablar con clientes, criterio visual (Claude Design), decidir precios/módulos, y probar flujos como usuario real en tu teléfono. Todo lo demás (código, tests, infra, docs) es delegable con los protocolos de este documento.
5. **No paralelices giros.** Un vertical hasta que facture; el segundo sale del MISMO core (Fase 1 ya portable) en semanas, no meses. La tentación de "mientras tanto empiezo otro" es la trampa clásica.
6. **Cadencia semanal fija:** lunes se elige el objetivo de la semana (1 fase o 2 módulos), viernes demo funcionando en staging aunque sea feo. Lo que no cupo, no se arrastra en silencio: se re-planea el lunes.
7. **El multiplicador real es la plantilla.** Hoteles se construyó artesanal; el giro 2 se construye desde este documento; el giro 3 debe salir de un repo template (core SaaS listo + este protocolo). Para el tercer vertical, el objetivo honesto es demo en 2-3 semanas.

---

## 10. Qué sigue (en orden)

1. **[MANUAL] Estudio de mercado** de los 2 giros recomendados (§1): 5 entrevistas por giro a dueños reales — ¿pagarían $X/mes? ¿qué 3 funciones les venden la demo? Con eso se llena el `[GIRO ELEGIDO]` del prompt.
2. **[MANUAL] Crear el repo nuevo** (privado) + pegar el prompt maestro en Claude y Codex (protocolo §5).
3. Mientras tanto, **este repo (hoteles) sigue su camino**: VPS + deploy (checklist listo) — es independiente y es el que ya factura.
4. El re-examen de Medisoft (post pasos manuales) alimentará la versión 1.1 de este documento si cambia alguna lección.
