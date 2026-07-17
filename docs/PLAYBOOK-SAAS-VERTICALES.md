# PLAYBOOK: Fábrica de SaaS Verticales White-Label

**Versión 1.0 — 2026-07-12 · Dueño: Maximiliano León · Escrito por: Claude (Fable 5)**

> Este documento SUSTITUYE y absorbe a `plan-siguiente-giro.md` y complementa a
> `analisis-mercado-giros.md`. Es el texto que se le da a cualquier IA (Claude,
> Codex u otra) al arrancar CUALQUIER negocio nuevo de esta fábrica. Se escribió
> para durar: cuando algo cambie, se actualiza AQUÍ, no en chats.

---

## ÍNDICE Y CÓMO USAR ESTE DOCUMENTO

| Si vas a… | Lee |
|---|---|
| Entender la meta y el modelo de negocio | §1 |
| Arrancar un giro nuevo desde cero | §2, §5, §6, luego pega §12 en la IA |
| Decidir qué módulos construir | §7 |
| Diseñar pantallas / agregar temas | §9 |
| Trabajar con la IA sin quemar tokens | §10, §11 |
| Vender y poner precios | §13 |
| No morir por un descuido legal/técnico | §14, §16 |
| Saber si el negocio va bien | §15 |
| Ejecutar los próximos 14 días | §17 |
| Copiar/pegar cosas listas (CLAUDE.md, prompts, guiones, invariantes) | §11, §12, Apéndices A-C |

---

## §1. LA META FINAL Y LA TESIS

**Meta:** construir una FÁBRICA de sistemas SaaS multi-tenant white-label para
PyMEs mexicanas, donde cada nuevo giro cuesta menos que el anterior:

- Giro 1 (hoteles / Medisoft): ~10 meses, artesanal. **Ya factura.** Es la mina
  de la que se extraen los patrones.
- Giro 2 (veterinarias, según análisis de mercado): 6-10 semanas usando este
  playbook. Objetivo: demo vendible en semanas, primer cliente pagando mes 3.
- Giro 3+ (talleres, funerarias…): 2-3 semanas partiendo de un repo template.

**Tesis de negocio:** las PyMEs mexicanas no compran "software"; compran
(a) que se les quite un dolor diario, (b) verse profesionales frente a su
cliente (white-label: SU logo, SU app), y (c) cobrar mejor/más rápido. Todo
giro que elijamos debe pegarle a los tres.

**Modelo de ingresos:** mensualidad base + módulos à la carte (patrón ya
probado en Medisoft con `hotel_modulos` y cobro SaaS). Cero comisión por
transacción como bandera de venta (los competidores que cobran % son odiados).

**Los tres activos que hacen posible la fábrica:**
1. El código de Medisoft (patrones probados en producción con dinero real).
2. Este playbook (las lecciones destiladas — el "cómo").
3. El protocolo de IA (§10-12): la fuerza de construcción es Claude/Codex,
   el criterio de negocio y el gusto visual son de Maximiliano.

---

## §2. PUNTO DE PARTIDA (qué existe hoy, resumen honesto)

- **Medisoft Hoteles:** SaaS multi-hotel en PHP vanilla (50 controladores, 48
  servicios, 182 vistas, 87 migraciones, 113 tablas). Examen final: 6.4/10.
  Tras "Operación 10" (2026-07-11): CSRF global + escudo JS, 38 tests de
  invariantes de dinero y aislamiento multi-tenant en CI, linter ratchet de
  tenancy (deuda congelada: 114 refs), runner de migraciones, backup con
  offsite listo, headers de seguridad. Re-score estimado ~7.8.
- **Mercado analizado (jul 2026):** Top 5 = veterinarias, talleres mecánicos,
  dental, estética premium/cadenas, funerarias. Recomendación: **veterinarias**
  (competencia débil, reuso extremo, expediente = retención) con talleres como
  plan B. Detalle y fuentes: `analisis-mercado-giros.md`.
- **Pendientes del giro hotelero** (independientes de la fábrica): contratar
  VPS + deploy (checklist listo), NominaCreditoTest, bajar deuda del linter.

---

## §3. LO QUE SE REPITE (patrones ganadores, copiar sin discutir)

1. **Dinero con invariantes + candados + tests.** Todo módulo que toque dinero
   nace con: su invariante escrito en una frase, su candado de concurrencia
   (FOR UPDATE + re-verificación dentro del candado + operación atómica), y su
   test. Los invariantes canónicos están en el Apéndice B — son transferibles
   a cualquier giro porque "caja", "corte", "abono" y "reverso" existen igual
   en una veterinaria que en un hotel.
2. **White-label real por tokens** (`--brand-*`): logo, paleta, favicon,
   manifest PWA por tenant. Es el modelo de negocio, no cosmética.
3. **Módulos activables con precio** (core siempre-on + extras): permite
   ticket bajo de entrada y ARPU creciente.
4. **RBAC configurable por tenant** con presets editables desde el panel SaaS.
5. **PWA mobile-first instalable** + push por rol + offline en lo operativo.
6. **Patrón "core agnóstico + adaptador por giro"** (ya existe en
   NominaAdaptadorGiro/Registry): el core no sabe de mascotas ni coches; los
   adaptadores sí.
7. **Comunicación proactiva al cliente final** (WhatsApp de recordatorio/
   avance): en veterinarias y talleres es LA función que vende la demo.
8. **UX boutique** (Deleite Sereno): microinteracciones con propósito, toasts
   y confirms propios, serif de despliegue + sans contenida (700 máx). La PyME
   compra por cómo se siente.
9. **Auditar antes de implementar** y **verificar E2E antes de cerrar**: las
   dos reglas de trabajo que más errores evitaron en hoteles.
10. **Docs cortos por tema + memoria de proyecto**: hechos no-derivables del
    código en archivos de <30 líneas, jamás manuales gigantes.
11. **Navegación fluida DE SERIE (día 1, no retrofit).** En hoteles se
    retro-instaló; en el siguiente giro nace puesto. La receta completa
    (probada en Medisoft, jul 2026):
    - **bfcache habilitado**: el HTML dinámico responde `Cache-Control:
      private, no-cache, must-revalidate` — JAMÁS `no-store` ni `Pragma`/
      `Expires: 0` (eso mata el "regresar instantáneo"). Ojo: el header del
      SERVIDOR WEB (nginx/htaccess) pisa al de PHP; configurarlo ahí.
    - **Speculation Rules** (Chromium): `prerender` con `eagerness: moderate`
      para el menú (clic 0ms) + `prefetch` de respaldo; `<link rel=prefetch>`
      en hover(80ms)/touch para filas clicables y otros navegadores, con
      dedupe + tope por página.
    - **Exclusiones canónicas del prerender**: pantallas de dinero/estado
      vivo (caja), pantallas cuya visita apaga avisos (notificaciones),
      logout. Recarga forzada en `pageshow persisted` para las de dinero.
12. **Áreas operables además de la unidad rentable** (probado en hoteles,
    jul 2026): la unidad que factura (habitación/consultorio/bahía) no es lo
    único que se opera — alberca, lobby, quirófano, patio también se limpian,
    se mantienen y se cierran. Receta: entidad `areas_<giro>` con estado
    espejo de la unidad principal (sin el estado "ocupada/rentada") + FK
    `area_id` NULLABLE en las tablas operativas existentes (tareas,
    mantenimientos, activos) reusando los MISMOS motores/servicios con el id
    principal en NULL — cero módulos nuevos, cero permisos nuevos (vive
    dentro del módulo de la unidad principal; claves de permiso nuevas
    rompen roles legacy), subnav compartida en la misma sección. El detalle
    del área hereda los contratos de la unidad (personal obligatorio en
    limpieza, mantenimiento→gasto).
    - **Guardas de efectos secundarios**: TODO GET con efecto (marcar leído,
      registrar visita, archivar) verifica `Sec-Purpose`/`Purpose` y responde
      503 a peticiones especulativas (helper `is_speculative_request()`).
      Regla dura: ningún GET nuevo con efecto sale sin esta guarda.
    - **Guardia de sesión**: tras logout, restauración desde bfcache fuerza
      reload (equipos compartidos de recepción/mostrador).
    - **View Transitions MPA**: `@view-transition { navigation: auto }` +
      duraciones .16/.2s, con guard `prefers-reduced-motion`. Cross-fade
      entre pantallas gratis.
12. **Tableros operativos AGRUPADOS por "qué necesita cada item", no un muro
    plano de tarjetas iguales.** Un grid uniforme de N tarjetas obliga a leerlas
    una por una; agrupar en secciones con ícono + conteo + pista corta lo vuelve
    escaneable de un vistazo. Receta (probada en el tablero de limpieza de
    Medisoft, jul 2026, transferible a colas de veterinaria/taller):
    - Una sección por estado accionable, ordenadas por urgencia (pendiente →
      en curso → resuelto → fuera de servicio); la sección vacía no se pinta.
    - Lo YA-resuelto va en `<details>` colapsable (cero JS): plegado cuando hay
      trabajo pendiente (enfoca), abierto cuando no lo hay (no deja pantalla
      vacía) — `<?= $pendientes === 0 ? ' open' : '' ?>`.
    - La acción "revertir" (marcar limpio→sucio, reabrir) es SECUNDARIA (botón
      ghost/mini), nunca compite visualmente con el CTA primario de avanzar.
    - El copy del encabezado describe el tablero, no da una orden que solo
      aplica a un estado ("cada sección te dice qué hacer", no "toca cuando
      termines" cuando no hay nada por terminar).
    - **Cada tarjeta carga el dato de dominio que vuelve accionable la
      decisión**, no solo el estado. En limpieza: qué día SALE cada cuarto
      ocupado + quién lo ocupa (el controlador ya tiene el JOIN
      reservacion_habitaciones→reservaciones→huespedes; se etiqueta relativo:
      "Sale hoy/mañana/el dd/mm · N noches"). Transferible: en veterinaria la
      tarjeta de "en consulta" carga la mascota + hora de cita; en taller, la
      orden carga el vehículo + fecha prometida.
13. **Listas móviles = bandeja de teléfono, no tabla web.** En móvil el usuario
    espera gestos de teléfono, no pestañas ni botones densos. Receta (probada en
    /notificaciones de Medisoft, jul 2026, transferible a cualquier bandeja:
    tareas, tickets, avisos):
    - **Swipe-para-archivar** con pointer events: seguir el dedo con
      `translateX`, umbral ~84px, POST real al endpoint de descarte con
      `csrf_token` en el body + `X-Requested-With`; al éxito colapsar altura y
      remover. Guardar `dataset.dragged` para NO navegar tras un arrastre.
      (Reusar el mecanismo del `notification_bell.php`, no reinventarlo.)
    - **En móvil se ocultan las pestañas de estado** (Atendidas/Archivadas): la
      bandeja móvil es solo "pendientes" + gesto; el historial vive en PC.
    - **Confirmar destructivo con un control que se transforma** (× → "Borrar
      todo") antes de la acción masiva, con barrido escalonado de las tarjetas.
    - **GOTCHA CSS**: NO animar `width` de un botón que es flex-item o de tamaño
      intrínseco — la transición no progresa (se queda en el valor inicial).
      Crecer por CONTENIDO: botón `width:auto` + animar `max-width` del texto
      interno. Y ojo: el preview headless no avanza transiciones CSS; verificar
      estados finales desactivándolas (`*{transition:none}`) + la lógica JS.
14. **"Modo Dueño": la vista remota del dueño que NO opera, como bloque
    vendible (asiento adicional).** El dueño de 55-75 años abre el teléfono y
    entiende su negocio en 10 segundos. Receta (probada en Medisoft /dueno,
    jul 2026; transferible: dueño de veterinaria/taller es el MISMO usuario):
    - **Rol preset de solo-lectura** (`dueno_remoto`) + módulo comercial
      propio (`modo_dueno`) + ruta propia. Cero botones que escriban. Ni uno.
    - **Aterrizaje por rol**: helper `home_route_for_current_user()` decide
      el hogar (valida módulo + permiso para no crear bucles); login,
      dashboard y TODOS los redirects amables (`require_permission`,
      `require_hotel_module`) regresan al hogar del rol, no al dashboard.
    - **Una tarjeta por PREGUNTA del dueño** en orden fijo (¿cómo va el día?
      / ¿cuánto entró? / ¿cómo está el negocio? / ¿todo en orden? / ¿qué
      dicen los clientes?), cada una gateada por permiso + módulo en el
      CONTROLADOR (bloque null = tarjeta no se pinta; la vista queda
      armónica con cualquier subconjunto).
    - **Textos redactados en el SERVIDOR en lenguaje hablado** (cero jerga:
      nada de cortes/CxC/RevPAR) y compartidos entre el render y el endpoint
      JSON de "Actualizar" — un solo lugar para el copy.
    - **Score del día determinista** (sin IA, sin tablas): pesos fijos por
      señal (caja cuadrada / limpieza o cola al día / ocupación vs promedio
      PROPIO mismo día de semana 4 semanas / calificación reciente),
      renormalizados sobre las señales que el usuario puede ver. La ETIQUETA
      en palabras manda; el número va en chip chico. Desglose al tocar.
    - **Push selectivo por clave de rol**: el ruteo de suscripciones resuelve
      el rol efectivo por `roles.clave` via `role_id` (el ENUM de
      compatibilidad guarda 'recepcionista' y ruteaba al dueño como
      recepción); el rol dueño solo recibe briefing + alertas de vigilancia,
      jamás el ruido operativo.
    - **Chips del copiloto contextuales por superficie**: en /dueno pregunta
      de negocio ("¿Cuánto entró hoy?"), no de operación.
    - **GOTCHA**: si la vista esconde el header móvil fijo, quitar también el
      `padding-top` que el body reserva para él; y un `display:flex` propio
      pisa el atributo `hidden` (agregar `[hidden]{display:none}`).
15. **IA accionable = "IA propone JSON → PHP valida → humano confirma → registro
    ESTÁNDAR"** (circuito de tarifas del Copiloto, jul 2026; transferible a
    cualquier consejo IA que pueda convertirse en acción: precios, compras,
    agenda). Reglas que no se negocian:
    - La IA emite, al final de su texto normal, un bloque `<sugerencias>` con
      JSON; un parser PHP puro y testeado aplica límites duros (|pct| ≤ 15,
      fechas ≤ 90 días, desde ≤ hasta) y DESCARTA entradas inválidas sin tirar
      el consejo. Sin bloque o JSON roto = texto plano como siempre. El bloque
      viaja dentro del mismo contenido cacheado (regenerar la vista no re-paga
      tokens).
    - El endpoint de aplicar NO confía en el cliente: relee el consejo cacheado
      en el servidor (fechas/acción/motivo no forjables), el cliente solo manda
      ventana y %, y el % se re-valida contra los topes. Gate = permiso RBAC
      del recurso afectado ADEMÁS del bloque comercial IA.
    - Lo creado es un registro ESTÁNDAR del módulo existente (mismo motor,
      editable/borrable como cualquiera — esa ES la reversibilidad; no
      construir "deshacer" aparte) + trazabilidad: `origen`, `consejo_ref`,
      `aprobado_por`.
    - Conflictos NUNCA se apilan en silencio: verificar solapamiento y explicar
      el choque con el registro vigente nombrado.
    - Cierre del círculo: snapshot (proyección/tarifa) al aplicar y resultado
      REAL calculado on-demand al renderizar cuando la ventana pasó (sin cron),
      mostrado junto al registro y re-inyectado al prompt del consejo siguiente
      con orden de honestidad total (si no mejoró, se dice).
    - Arranque en frío: si no hay histórico NI contexto capturado, aviso ámbar
      con captura exprés (chips de meses → registros recurrentes); comparativa
      multi-año promedia solo años CON datos manteniendo el formato de salida.
    - **GOTCHA**: `Model::create` devuelve el ID insertado, no la fila; los
      festivos calculados y las temporadas del hotel deben llevar `tipo`
      explícito al mezclarse en una sola lista de eventos.
16. **Candados que explican: el error nombra al bloqueador Y la acción.**
    Un candado que solo dice "recurso no disponible, libérelo" genera confusión
    y soporte; el que dice QUIÉN lo bloquea y QUÉ hacer se auto-resuelve
    (check-in de hoteles, jul 2026; transferible a cualquier recurso compartido:
    consultorio ocupado, bahía de taller, mesa). Receta:
    - Al bloquear, consultar el registro que ocupa el recurso y nombrarlo:
      "ocupada por la reservación #X de <nombre> (salida dd/mm, check-out
      pendiente). Para continuar, haz el check-out de esa reservación".
    - Agrupar recursos por bloqueador (3 habitaciones del mismo huésped = una
      frase, no tres) y distinguir "vencido con cierre pendiente" (acción:
      cerrar) de "vigente hasta fecha" (acción: esperar/reasignar).
    - **GOTCHA**: la validación vive en UN método compartido por TODOS los
      caminos de la acción — en hoteles el check-in normal validaba pero el
      tardío no, y marcaba ocupado encima de otro huésped (overbooking
      silencioso). Asignar limpieza/tareas NUNCA libera el recurso; solo el
      cierre del registro que lo ocupa.
17. **Acciones deterministas por chat = "frase → PROPUESTA → confirmación →
    motor de la pantalla"** (acciones del Copiloto: limpieza, mantenimiento,
    bloquear/liberar recurso, gasto de caja, jul 2026; transferible a
    cualquier asistente del giro). Reglas:
    - Detectar la orden con verbos + entidades contra el CATÁLOGO real del
      tenant (habitación/trabajador/categoría por nombre completo y luego por
      token de palabra completa; ambigüedad = preguntar, jamás adivinar). El
      parser de monto/concepto es una función PURA y testeada (si hay varios
      números gana el que trae `$` y si no el mayor: separa dinero de
      cantidades).
    - La detección solo arma una PROPUESTA con payload + textos de
      confirmación; ejecutar es otro endpoint POST con CSRF que REVALIDA
      permiso del rol y catálogo del tenant (la confirmación del widget es UX,
      no seguridad) y llama al MISMO servicio/modelo que usa la pantalla, con
      sus candados (corte abierto FOR UPDATE, conflictos de reservas, estado
      del recurso).
    - Dinero por chat: SOLO egresos (gasto), solo efectivo (los métodos con
      referencia obligatoria se mandan a la pantalla), el método lo fija el
      SERVIDOR, y con la caja cerrada se avisa en la propuesta (no se deja
      fallar en la ejecución). Cobros/ingresos jamás por chat.
    - Precondiciones se validan al PROPONER (caja cerrada, habitación que no
      está bloqueada, motivo obligatorio): una confirmación que va a fallar es
      peor que un aviso temprano.
    - Valores dictados ambiguos se PREGUNTAN, no se adivinan ("cupón de 10"
      → ¿10% o $10?). Si el usuario no dicta identificador, generarlo legible
      y verificar disponibilidad (AGOSTO10 → AGOSTO10-2); el UNIQUE de la
      tabla es el candado final. Un mes referido que ya pasó se corre al año
      siguiente (una vigencia no puede nacer vencida).
    - La transacción compleja del giro (reservación/cita/orden: precio,
      tarifas, anticipo) NO se crea por chat: el chat entiende la frase,
      valida lo que sí sabe (disponibilidad real con el motor de la
      pantalla, entidad existente) y entrega el FORMULARIO PRELLENADO por
      URL (la pantalla de crear debe aceptar preselección por query). Si el
      recurso está ocupado se avisa y el link va sin recurso para elegir
      otro ahí. Ojo con el gate: un enlace no debe exigir MÁS permiso que
      la pantalla a la que apunta (con roles legacy, un can() extra bloquea
      a todos en silencio).
    - Pagos a deuda por chat (CxP): siempre la cuenta MAS ANTIGUA del
      acreedor (vence primero gana) diciéndolo y nombrando cuántas más hay;
      sin monto dictado se propone SALDAR; monto > saldo se frena al
      proponer. Un nombre que también casa con personal y sin la palabra
      desambiguadora ("proveedor") NO se adivina: se pide la frase explícita
      (la nómina tiene su propia pantalla). Los métodos con referencia se
      aceptan solo si el motor la genera trazable (CXP-id-MOV-id); si la
      referencia es obligatoria y manual, a la pantalla.
    - **GOTCHA (tests)**: el bootstrap de pruebas debe cargar los MISMOS
      helpers que el front controller — un gate con `function_exists()`
      degrada en SILENCIO y el caso negativo pasa por la razón equivocada
      (en hoteles: `modulos.php` no estaba en el bootstrap y el gate de
      bloques daba falso para todos). Y en un harness casero, el caso que no
      llama al finalizador (`t_fin()`) no cuenta sus FAIL: el runner reporta
      verde con aserciones rotas.
18. **Multi-turno barato para el asistente híbrido: el CLIENTE guarda el
    hilo, el SERVIDOR lo sanea, solo la IA lo ve** (copiloto de hoteles, jul
    2026). Sin tabla nueva ni sesiones de chat: el widget acumula los pares
    pregunta/respuesta de la página y los reenvía como JSON; las rutas
    deterministas (intents, acciones, FAQ) lo ignoran por completo. Reglas:
    - Un saneador PURO y testeado garantiza el contrato del API de mensajes:
      solo roles user/assistant, consecutivos fusionados (alternancia),
      empieza en user y termina en assistant (la pregunta nueva con el
      snapshot fresco siempre es el turno final), topes de turnos y de
      caracteres. Basura tipada del cliente jamás revienta.
    - El historial da CONTEXTO ("¿y eso por qué?"), nunca cifras: el system
      prompt ordena que los números válidos son solo los datos en vivo del
      turno actual — un número del historial puede estar viejo.
    - El hilo viene del cliente y es tan confiable como la pregunta misma
      (mismo nivel de confianza, mismo scope de hotel del lado servidor).
19. **Captura campo por campo por chat (slot-filling determinista, sin
    framework)** (reservación del copiloto de hoteles, jul 2026). El estado
    del wizard viaja en el CLIENTE (JSON) y regresa con cada mensaje; sin
    tabla de sesiones. Reglas:
    - Un saneador PURO invalida el flujo COMPLETO ante cualquier campo fuera
      de contrato (tipo, paso, formato de fechas, coherencia); lo crítico
      (catálogo, disponibilidad, duplicados) se revalida contra la BD al
      usar y al cerrar, no en el saneo.
    - El one-shot no muere: si la frase trae todo, cierra directo; el flujo
      arranca SOLO desde lo que falta, con lo entendido ya capturado.
    - Una pregunta de datos a media captura se responde normal SIN matar el
      flujo: el widget conserva el estado hasta recibir flujo nuevo o
      `flujo_fin`. "Cancelar" disponible en todo paso (y anunciado).
    - Respuestas de escape por paso ("cualquiera", "sin nombre", "sin
      teléfono"): lo opcional jamás secuestra la conversación.
    - Cada pregunta del wizard trae sus RESPUESTAS POSIBLES como chips
      tocables (tocar una ES contestar): las opciones se ven, no se
      adivinan del texto. Las opciones salen del CATÁLOGO real del tenant
      (tipos de habitación del hotel, no genéricos; dictar un tipo aparta
      una unidad libre de ese tipo), el caso "entidad aún no registrada"
      es un chip explícito que salta el catálogo y va directo al alta, y
      "✕ Cancelar" siempre es la última opción de cada paso.
    - Descubribilidad SIN saturar: el saludo trae el catálogo COMPLETO de
      capacidades en páginas por categoría (flechas ‹ › estilo iOS + dots,
      filtrado por bloques del tenant, frecuentes aprendidos primero, la
      página de la pantalla visible antes que todo); después de CADA
      respuesta terminal van máximo 3 sugerencias contextuales del TEMA
      recién respondido (tras caja → registrar gasto). Chips de dos tipos:
      pregunta completa (envía) y PLANTILLA (rellena el input con la orden
      a medias — "registra un gasto de 450 de " — y enfoca): una orden que
      necesita datos jamás se auto-envía. Sin sugerencias cuando hay acción
      o wizard pendiente (la siguiente jugada ya está clara).
    - La escritura ocurre SOLO al final vía la acción confirmable de
      siempre, y el cierre revalida: disponibilidad al momento (pudo
      ocuparse mientras chateaban) y "buscar antes de insertar" para que el
      doble clic reuse en vez de duplicar.
20. **Tool use de SOLO LECTURA en el fallback IA** (copiloto de hoteles,
    jul 2026): además del snapshot fijo de "hoy" (que sigue respondiendo lo
    común en un solo turno, sin round-trips), el modelo recibe 4-5
    herramientas de consulta — dinero por rango de fechas, transacciones
    por rango, disponibilidad futura, búsqueda de entidad — y así responde
    preguntas de CUALQUIER periodo sin inventar. Reglas:
    - Toda herramienta es de solo lectura, con el tenant amarrado del lado
      SERVIDOR (el modelo jamás elige el hotel), fechas validadas (formato,
      orden, tope de días) y salida compacta acotada (mb_substr). Nombre
      fuera del whitelist = rechazado antes de tocar nada.
    - Bucle agéntico acotado (máx 4 rondas de herramientas); los bloques de
      la respuesta (thinking incluido) se devuelven INTACTOS en el turno
      assistant del loop, como exige el API; tokens se acumulan entre
      rondas para el log de costos.
    - El system prompt ordena: lo que la herramienta devuelva ES la cifra;
      error o vacío se dice tal cual. Probar la herramienta con test E2E
      SIN API (cifras, tenancy, validaciones) y el loop completo con UNA
      llamada real de humo.
    - GOTCHA de la prueba de humo: el router determinista atrapa las
      palabras clave del giro ("caja") antes de llegar a la IA — la
      pregunta de prueba debe esquivar los intents.

---

## §4. LO PROHIBIDO (errores pagados una vez; no se pagan dos)

| # | Prohibición | Regla de reemplazo |
|---|---|---|
| P1 | Código de dinero sin test | El test del invariante se escribe ANTES o JUNTO al código. CI lo corre en cada push |
| P2 | Tenancy por convención (WHERE tenant_id a mano) | Global scopes automáticos del ORM. El linter ratchet vive en CI desde el commit #1 con línea base = 0 |
| P3 | Framework casero | Laravel 12 (§5). Cada pieza de infra casera de Medisoft (router, CSRF, migraciones, colas, validación) fue un mes de trabajo + auditoría que Laravel da gratis |
| P4 | Archivos >800 líneas | Tope duro. El CI falla si un archivo lo excede (excepto vendor). Componentes desde el día 1 |
| P5 | Migraciones a mano | `php artisan migrate` corre en el deploy, siempre |
| P6 | Trabajo pesado síncrono | WhatsApp, correos, push, PDFs pesados → cola SIEMPRE. Un webhook lento jamás toca la UX de caja |
| P7 | Sin staging / deploy artesanal | Staging desde semana 1. Deploy = un comando con migraciones y rollback |
| P8 | Temas vistiendo vista por vista | Tokens semánticos + un tema = un archivo (§9). CI rechaza hex fuera de /themes |
| P9 | Artefactos en el repo (dumps, zips, exports) | .gitignore estricto día 1 |
| P10 | Sesiones de IA maratónicas multi-tema | 1 sesión = 1 objetivo con test verde y commit al cierre |
| P11 | Construir features antes de que alguien las pida/pague | La demo se vende con UN módulo estrella. Todo lo demás espera un cliente que lo pida |
| P12 | Paralelizar giros antes de facturar | Un vertical hasta que pague; el template hace barato el siguiente |
| P13 | CSS compilado en el navegador (Tailwind Play CDN) | Tailwind PRECOMPILADO en build (Vite lo da gratis en Laravel). El compilador en cliente cuesta cientos de ms por página en cada carga, peor en móvil, y obliga a `unsafe-eval` en la CSP. GOTCHA pagado: los `content` globs JAMÁS deben tocar archivos vendoreados con líneas gigantes (fuentes CID de TCPDF: 1.5MB, líneas de 1.1M chars) — el extractor pasa de segundos a 5+ MINUTOS y parece colgado. Ante un build lento: bisect por glob con `--content` |
| P14 | Llamar helpers con firmas inventadas (`current_user('id')`) | PHP no truena por argumentos extra: el helper ignora el argumento, devuelve el array completo y el bug aparece hasta la DB como `'Array' for column usuario_registro_id` (rompió Iniciar/Programar mantenimiento en Medisoft). Para el id del usuario SIEMPRE el helper dedicado (`user_id()`); en Laravel, `auth()->id()`. Y los errores SQL crudos JAMÁS llegan al toast: loguear completo, mostrar mensaje genérico |
| P15 | Estilos de un modal/bottom-sheet scopeados bajo un wrapper (`.vista .sheet{…}`) cuando el nodo del sheet puede quedar FUERA de ese wrapper en el DOM | Es la causa REAL de "hoja/modal en blanco en móvil" (pagado en el bottom-sheet de /habitaciones, jul 2026): el bloque entero de reglas (posición fija, `bottom:0`, y el clon del contenido en flujo) vivía bajo `.habitaciones-view`, pero el markup del sheet quedaba fuera de ese contenedor → ninguna regla aplicaba, el sheet no se anclaba al viewport y el contenido clonado conservaba su `position:absolute; top:100%`, empujándose ~una pantalla abajo (fuera de vista) → BLANCO. Reglas: (a) el sheet/modal NO debe depender de un ancestro-wrapper para sus estilos base — o se scopean con su propia clase raíz, o se garantiza en JS que viva dentro del wrapper al abrir (`if (sheet.parentElement !== wrapper) wrapper.appendChild(sheet)`); (b) mover un `position:fixed` dentro de un wrapper es seguro SOLO si ese wrapper no crea bloque-contenedor (sin `transform`/`filter`/`contain`/`will-change`). GOTCHA de diagnóstico que ahorró horas: cuando el bug NO reproduce en escritorio/Chromium (aunque cargues TODAS las hojas y el tema), instrumenta el dispositivo REAL con un panel `position:fixed;z-index:max` que vuelque rects/offsetTop/ancestros al abrir; una sola captura del usuario da la causa exacta. No asumir "es de iOS" sin ese dato — la primera hipótesis (`-webkit-overflow-scrolling:touch`) fue errónea y no costó nada verificarla mal |
| P16 | Dump de esquema para tests que se actualiza "a mano" (o nunca) | Pagado jul 2026: 13 errores/día de `Unknown column 'activo_id'` porque los tests recreaban la BD de prueba desde un `schema.sql` congelado meses atrás de las migraciones — el bug ni existía en la app real, solo en la BD de tests. En Laravel `RefreshDatabase` corre las MIGRACIONES (nunca un dump); si algún test usa dump/squash, su regeneración es un comando del repo y el CI compara dump vs migraciones. Bonus pagado el mismo día: dos suites de tests corriendo EN PARALELO contra la misma BD de prueba (dos sesiones/PCs) producen fallas fantasma (deadlocks, `1146 table doesn't exist`, semillas duplicadas) — ante fallas absurdas, primero `SHOW PROCESSLIST` |

---

## §5. STACK Y HERRAMIENTAS DEFINITIVAS

**Stack de producto:**
- **Laravel 12 + PHP 8.3** — el corazón. Multi-tenancy por columna `tenant_id`
  + global scopes (BD única). `stancl/tenancy` SOLO si un cliente exige BD
  separada (no antes).
- **MySQL 8** (lo dominamos, InnoDB + FOR UPDATE probados) + **Redis** (cache,
  colas, sesiones) desde el día 1 — es una línea de docker-compose y elimina
  el techo de escalar que tiene hoteles.
- **Livewire 3 + Alpine + Tailwind** — interactividad sin SPA ni API paralela.
- **Filament 3** — panel SaaS admin (tenants, módulos, cobros) en días.
- **Pest** — tests. **Laravel Cashier (Stripe)** — cobro SaaS recurrente.
- **PWA** (los recetarios de manifest/push/offline de Medisoft se portan).

**Herramientas de operación:**
- GitHub + Actions (CI: lint, Pest, linter tenancy, linter de hex, tope 800).
- Claude Code = constructor principal. Codex = plan dual + code review por fase.
- Claude Design = diseño de pantallas (protocolo §9b).
- Sentry (plan free) = errores en producción con alerta. Día 1, no "luego".
- UptimeRobot = latido externo. Backups: receta Medisoft (dump verificado +
  rclone offsite + simulacro trimestral).
- WhatsApp: para el negocio NUEVO usar **API oficial de WhatsApp Business**
  (Meta vía 360dialog/Twilio) — Green-API (no oficial) fue aceptable para
  empezar en hoteles, pero un negocio que VENDE recordatorios no puede
  arriesgarse a bloqueos de número. Presupuestar ~$0.3-0.8 MXN por mensaje.
- Dominio por marca del giro + subdominio por tenant (`clinica-x.marca.mx`)
  o slug (`marca.mx/v/clinica-x`) — decidir en Fase 1 (subdominios venden
  mejor el white-label; requieren wildcard DNS + cert, Caddy lo hace solo).

**Decisiones conscientes de NO:** microservicios NO, React/SPA NO, Kubernetes
NO, GraphQL NO, multi-cloud NO. Monolito modular bien probado en un VPS con
Docker + Caddy (receta ya escrita en el repo hotelero) hasta que el MRR
justifique más.

---

## §6. ARQUITECTURA DE REFERENCIA

### 6a. Capas
```
[ PWA / Livewire UI ]  ← tokens de diseño + temas (§9)
[ Módulos de giro ]    ← citas, expediente, órdenes… (adaptadores)
[ CORE AGNÓSTICO ]     ← tenants, RBAC, caja/dinero, notificaciones,
                          documentos, reportes, white-label, módulos $
[ Laravel ]            ← auth, colas, scheduler, migraciones, validación
[ MySQL + Redis ]
```

### 6b. Modelo de datos base (Fase 1, igual en todos los giros)
- `tenants` (id, nombre, slug, plan, activo, branding_json, tema)
- `sucursales` (tenant_id, nombre) — **crear desde el día 1 aunque el giro 2
  no la use**: agregarla después cuesta una migración en cada tabla; tenerla
  de inicio cuesta una columna. Lección directa de hoteles.
- `users` + `tenant_users` (rol, permisos_json, sucursal_id nullable)
- `roles` (tenant_id nullable = preset global, permisos_json)
- `modulos` + `tenant_modulos` (precio, activo, es_core)
- `clientes` (la "ficha" universal: huésped/dueño de mascota/dueño del coche)
  + `contactos`, `documentos` (polimórfico), `notas`
- Dinero: `cajas`, `cortes`, `movimientos`, `abonos`, `cxc`, `cxp`
  (los nombres y candados de Medisoft, traducidos a Eloquent)
- `auditoria` (quién hizo qué, por tenant) — desde Fase 1, es barato al inicio
  e imposible de reconstruir después.

### 6c. Reglas duras de arquitectura
1. TODA tabla de negocio lleva `tenant_id` + global scope + índice compuesto
   `(tenant_id, …)`. Sin excepciones; lo global se justifica por escrito.
2. Los módulos de giro NO tocan tablas del core: extienden vía sus propias
   tablas (`vet_mascotas` → `clientes`) y eventos.
3. Un solo "reloj": timezone de negocio por tenant, guardado en BD, usado por
   scheduler y reportes (la lección del tz CLI vs PDO de hoteles).
4. IDs públicos = ULID/UUID en URLs públicas (no exponer autoincrementos).
5. Todo lo que sale (WhatsApp, mail, push) pasa por UNA tabla `mensajes` con
   estado (cola → enviado → fallido) = auditable, reintentable y facturable.

---

## §7. CATÁLOGO DE MÓDULOS (el menú de la fábrica)

**Regla:** nada se construye por adelantado; todo tiene receta para cuando un
giro (o un cliente que paga) lo pida.

### 7a. Ya construidos en Medisoft (se PORTAN, no se inventan)
Auth por tenant · RBAC configurable · Panel SaaS (tenants/módulos/cobros) ·
White-label/branding · Agenda-reservas (disponibilidad, estados, wizard móvil,
descuentos) · Ficha de cliente (documentos, historial, lealtad) · Caja+cortes
con candados · Anticipos/abonos saldo-aware · CxC/CxP con simuladores ·
Compras+proveedores · Inventario multi-ubicación · Personal+nómina con
adaptador de giro · Tareas operativas con personal obligatorio · Centro de
notificaciones+push por rol · WhatsApp saliente · Reportes+links públicos
firmados · Motor público de venta (pagos Stripe, cupones, holds) · Check-in/
pre-registro digital público · Encuestas de reputación · Lealtad · Sync iCal ·
Copiloto IA (Anthropic, revendible) · Cierre diario (night audit) · Auditoría
+ vigilancia financiera · Guardián (patrones de comportamiento por usuario) ·
Health/backups/offsite.

**Receta portada — el Guardián (jul-2026, vale para TODO giro con caja):**
dos motores read-only separados (integridad de libros + patrones de
comportamiento por usuario: descuentos vs mediana del equipo, cancelación
tardía con cobro previo, fuera de horario, cortes descuadrados recurrentes,
reversiones concentradas) → escalera de costo IA de 3 niveles (día limpio =
plantilla $0 y ESE mensaje verde es el producto; agregados = Opus; patrones
por usuario = Fable 5 forense con movimientos crudos, fallback a Opus) →
única escritura en tabla propia de estados (nuevo/revisado/resuelto, dedupe
de push con notificado_en) → push dirigido por PERMISO resuelto contra
permisos_json (no por rol-string) → umbrales por tenant vía config registry
+ mínimo de volumen (negocio chico NO alerta). Innegociables: lenguaje no
acusatorio en TODA superficie ("patrón a revisar", jamás robo/fraude) y
radiografía por persona SIEMPRE con el promedio del negocio al lado.

**Receta portada — Mantenimiento Plus (jul-2026, vale para TODO giro con
equipos que se descomponen: hoteles, veterinarias, talleres):** el correctivo
básico queda GRATIS (crear/cerrar incidencia); el bloque cobrable agrega las
4 capas que le duelen al dueño: (1) evidencia foto antes/después con el
patrón de uploads existente (máx 3 por momento, prefijo de tenant, compresión
GD) capturada desde el celular frente al problema; (2) costo real al cierre
que cae a gastos SOLO por el flujo de caja existente con sus candados — liga
gasto_movimiento_id + referencia MANT-{id}, candado anti doble clic FOR
UPDATE + IS NULL, y con caja cerrada queda "por registrar" con cola ámbar al
abrir caja (jamás bypass); (3) activos con periodicidad (activos_hotel:
proximo_servicio calculado, habitacion/ubicación opcional → la FK a la unidad
de negocio se hace NULLABLE y las queries de despliegue pasan a LEFT JOIN);
cron idempotente genera preventivo + tarea vinculada (candado "una tarea
activa por mantenimiento") + push por rol SIN tocar el estado de la unidad
(bloquearla es decisión humana); el cierre recalcula proximo_servicio; (4)
visibilidad: burbuja sidebar (vencidos/por vencer), intent de copiloto,
aviso ámbar si el bloque está activo sin activos, y costo del periodo POR
activo en el reporte ("este boiler te ha costado $X en el año" = el dato que
vende). GOTCHA pagado: el validador global de formularios trata cualquier
input *_costo/*_monto como numérico — los campos de texto libre no se nombran
así.

### 7b. Aumentables (receta corta, construir bajo demanda)
| Módulo | Brilla en | Receta |
|---|---|---|
| Expediente clínico (SOAP, recetas, vacunas) | Veterinarias, dental | Ficha + notas versionadas + PDF receta; campos sensibles cifrados |
| Carnet de vacunas + recordatorios | Veterinarias | Calendario por mascota + regla de notificación → cola WhatsApp |
| Pensión/hotel de mascotas | Veterinarias | ¡Es el core hotelero tal cual! Calendario de ocupación por jaula |
| Órdenes de servicio con evidencia | Talleres | Reserva con estados + checklist + fotos + firma en pantalla + avance por WhatsApp |
| Cotizador → orden → factura | Talleres | Pipeline de documentos ligados; aprobación del cliente por link público |
| Comisiones por empleado/servicio | Estética, vet | Nuevo adaptador de nómina: % por servicio cobrado en caja |
| Membresías del cliente final | Gimnasios, spas | Replicar patrón Cashier hacia el cliente del tenant |
| Control de acceso QR / asistencia | Gimnasios, escuelas | QR firmado + scanner PWA (cámara) + log |
| CFDI timbrado (facturación real MX) | Todos | PAC por API (Facturama/SW Sapien); el módulo actual ya guarda datos fiscales |
| Portal "mi cuenta" del cliente final | Todos | Ampliar el motor público: historial, pagos, próximas citas |
| Campañas WhatsApp (reactivación) | Estética, vet | Segmentos ("sin visita en 60 días") + plantillas aprobadas Meta + cola |
| Planes de previsión (pagos a futuro) | Funerarias | CxC + cobranza recurrente + contrato PDF |
| Importador CSV (clientes, inventario) | Onboarding TODOS | Hacerlo en Fase 2: migrar del Excel es la mitad de la venta |
| API pública + webhooks salientes | Integraciones | Sanctum + eventos; solo cuando un cliente grande pague por ello |
| Multi-idioma | Futuro | Textos en lang/ desde Fase 0 (costo ~0 al inicio, carísimo después) |

---

## §8. FASES DE CONSTRUCCIÓN (con criterio de salida)

> Regla de oro: una fase se cierra con (1) tests verdes en CI, (2) demo
> funcional en staging, (3) commit etiquetado. No se abre la siguiente con la
> anterior a medias.

| Fase | Contenido | Criterio de salida ("done") |
|---|---|---|
| **0. Cimientos** (1 sesión) | Repo, Laravel, docker local, CI completo (Pest + lint + linter tenancy + linter hex + tope 800), staging, CLAUDE.md (§11), .gitignore estricto | CI verde en un push; staging responde; Apéndice C ejecutado completo |
| **1. Core SaaS** | Modelo §6b, global scopes, auth por slug/subdominio, RBAC, Filament admin, white-label básico, módulos con precio | Test de aislamiento tenant A/B verde; alta de tenant en 2 min desde el panel; branding visible |
| **2. Módulo estrella** (el que vende) | P.ej. Agenda de citas vet: calendario, recordatorio WhatsApp, ficha mascota. + Importador CSV | La demo de 3 funciones (§13b) corre con datos de ejemplo; PLANTILLA de módulo documentada |
| **3. Dinero** | Caja/cortes, abonos, CxC — portando invariantes | Apéndice B completo en Pest, TODOS verdes; cierre de caja E2E en staging |
| **4. Personal** | Empleados, comisiones (adaptador del giro) | Un ciclo de comisiones calculado y pagado en demo |
| **5. Comunicación** | WhatsApp oficial (cola), push, centro de notificaciones | Mensaje real recibido en un teléfono; reintento de fallidos probado |
| **6. Pulido boutique + PWA** | Sistema de componentes + tokens + 1er tema + offline básico | Linter de hex verde; instalable en iPhone/Android; protocolo [DISEÑO] ejercitado ≥2 pantallas |
| **7. Cobro SaaS + onboarding** | Cashier, trial, alta self-service, emails de ciclo de vida | Un tenant de prueba se registra, paga con tarjeta test y usa el sistema sin tocarnos |

Estimación honesta total: **6-10 semanas** de sesiones enfocadas para giro 2.

---

## §9. DISEÑO: TEMAS Y PROTOCOLO CLAUDE DESIGN

### 9a. Temas que crecen sin dolor (arquitectura; los temas los crea Maximiliano)
1. Componentes usan SOLO tokens semánticos (`--superficie`, `--tinta`,
   `--acento`, `--radio`, `--sombra-1`, `--exito`, `--peligro`…). CERO hex en
   vistas — el CI lo rechaza (grep en /resources fuera de /themes).
2. **Un tema = un archivo** que re-mapea tokens. Jamás selectores por vista
   (la lección de cupertino.css §24b/§41).
3. `--brand-*` del tenant vive ENCIMA del tema: tenant elige tema Y paleta.
4. Registro en BD (`themes`: clave, nombre, archivo, preview) + selector en el
   panel → agregar tema no toca código.
5. Modo oscuro = un re-mapeo más, no un tema aparte.
6. Los semánticos (éxito/error/limpieza) se reservan para significado — nunca
   se usan como decoración (regla Deleite Sereno).

### 9b. Protocolo [DISEÑO] (la IA prepara, Maximiliano diseña, la IA portea)
La IA constructora NO diseña pantallas de usuario final de cero. Al llegar a
una, genera `design/encargos/<pantalla>/` con:
1. `PROMPT.md` — listo para pegar en Claude Design: objetivo, datos que
   muestra, acciones, breakpoints (móvil primero), y las reglas de tokens §9a
   para que la entrega sea porteable sin re-trabajo.
2. `referencias/` — 1-2 vistas ya existentes (lenguaje visual vigente).
3. `datos-ejemplo.json` — datos reales de muestra (nunca lorem ipsum).
4. `contrato.md` — lo intocable: rutas, campos, permisos, componentes
   obligatorios (tabla, toast, confirm).

Y avisa: **[DISEÑO] Pantalla X lista para encargo — córrela en Claude Design y
deja la entrega en design/entregas/X/**. Mientras tanto sigue con backend
(diseñar nunca bloquea). Al volver la entrega: portea a componentes, valida
contrato, pasa linter de tokens.

---

## §10. PROTOCOLO DE TRABAJO CON LA IA

### 10a. El modo "siguiente paso" (permanente)
Toda respuesta de la IA termina con:
- **(a) HECHO:** qué se completó y su prueba (test/captura/comando).
- **(b) SIGUIENTE PASO:** uno solo, concreto.
- **(c) PENDIENTES [MANUAL] / [DISEÑO]:** si los hay, con instrucciones exactas
  de clic por clic para el humano.

Libertad total para ejecutar lo reversible sin preguntar. Se detiene SOLO en:
decisiones de negocio (precio, alcance), acciones irreversibles/públicas, y
cuando falta información que solo el humano tiene.

### 10b. Protocolo dual Claude + Codex
1. Prompt maestro (§12) idéntico a ambos → cada uno entrega plan de Fase 0+1.
2. A cada uno se le pega la respuesta del otro: *"Compárala honestamente con
   la tuya: qué hace mejor la otra, qué haces mejor tú, y produce una VERSIÓN
   FUSIONADA con lo mejor de ambas. Específico, no diplomático."*
3. La mejor fusión (o una síntesis final de ambas) = Plan v1.0 ejecutable.
4. Durante la construcción: Claude Code construye; al cierre de CADA fase,
   Codex revisa el diff: *"busca bugs de dinero, fugas de tenant, y regresiones;
   ignora estilo"*. Hallazgos se arreglan antes de abrir la fase siguiente.

### 10c. Economía de tokens (reglas operativas)
1. CLAUDE.md (§11) siempre al día — la IA no re-descubre el proyecto.
2. 1 sesión = 1 objetivo. Cerrar con test verde + commit + actualizar memoria.
3. Lecturas quirúrgicas; prohibido pedir "léete todo el proyecto".
4. Verificar con tests (`php artisan test`, 5 seg) en vez de releer archivos.
5. Archivos ≤800 líneas = lecturas baratas para siempre.
6. La PLANTILLA de módulo (Fase 2) se reusa: "haz el módulo Y siguiendo la
   plantilla de citas" cuesta 10x menos tokens que diseñar de cero.
7. Los prompts largos viven en archivos del repo (como este), no se re-tipean.

### 10d. Regla de retroalimentación al playbook (permanente)
**Toda mejora o implementación que se haga en el sistema actual se vuelca a
este playbook EN LA MISMA SESIÓN en que se termina y verifica.** Este documento
es la memoria acumulada de la fábrica: el giro siguiente debe nacer con lo
mejor de todo lo aprendido, no redescubrirlo.

Formato del vuelco (elegir el que aplique):
- Patrón ganador transferible → nueva entrada en §3 con la receta condensada
  (qué, cómo, y los gotchas pagados).
- Error pagado / trampa descubierta → nueva fila en §4 (prohibición + regla
  de reemplazo).
- Módulo nuevo portable → línea en §7a.
- Ajuste de stack o tooling → §5.

Criterio de entrada: se vuelca lo TRANSFERIBLE a cualquier giro (patrones,
recetas, invariantes, trampas), no lo específico de hoteles. Si la sesión
cierra una mejora y el playbook no se tocó, la sesión no está cerrada.

### 10e. Sistema de conocimiento vivo (montar en la semana 1 de cada giro)
Arquitectura de tres capas, verificada en Medisoft (jul-2026). Regla madre:
**.md para saber, scripts para estado, hooks para comportamiento.**

1. **CLAUDE.md raíz** (lectura forzada: el harness lo inyecta cada sesión).
   Mapa denso: entorno, rutas del código, comandos, invariantes, gotchas,
   componentes reutilizables. UNA línea por entrada; nada derivable del código.
2. **CEMENTERIO.md raíz**: callejones sin salida. Formato "intento → por qué
   falló → qué se hace". Los fracasos no dejan rastro en el código; esto evita
   re-pagarlos. Leerlo antes de atacar un problema conocido.
3. **docs/QA-RECETAS.md**: flujos de verificación por módulo, paso a paso.
   Los pasos no ejecutados se marcan ⬜ y se completan en la primera corrida
   real — nunca escribir pasos inventados como hechos.
4. **tools/radiografia.php**: el estado VIVO jamás va en un .md (nace
   obsoleto). Un comando imprime: migraciones pendientes, linter vs baseline,
   frescura de assets compilados, errores recientes en logs, tamaño de suite.
   Cada sección con try/catch propio: una falla no tumba el reporte.
5. **Hook de Stop** (`.claude/hooks/` + `.claude/settings.json`, viajan por
   git): si la sesión modificó el repo, UNA vez por sesión (marcador por
   session_id en TEMP) exige alimentar los documentos o declarar que no hubo
   nada. Automatiza el GATILLO (el punto de falla real: acordarse), no la
   redacción — appendear sin criterio convierte el activo en vertedero.
6. **Poda mensual**: pase de consolidación sobre los tres .md (fusionar
   duplicados, borrar lo obsoleto). Sin poda, el sistema engorda hasta costar
   más de lo que ahorra.
7. **tools/centinela.php** (vigilancia): requiere logs estructurados JSON con
   nivel/ruta/origen/contexto (montarlos en semana 1). Agrupa errores por
   FIRMA (mensaje normalizado: números y literales → placeholders) con estado
   persistente: NUEVA y REGRESIÓN (resuelta que volvió) gritan con exit 1;
   conocidas/ignoradas callan. Ciclo: bug arreglado → `resolver <firma>`.
   La salida `--json` es el contrato de autocorrección: cron → centinela →
   cluster nuevo a Claude (API o `claude -p`) → fix como PR → humano aprueba
   → merge marca resolver. Separar canal cli (tests) de web (real).
8. **tools/observatorio del asistente IA** (mismo patrón que el centinela,
   aplicado a calidad): requiere que el chat registre cada pregunta con
   fuente reglas|ia|fallback (tabla mensajes, semana 1). El observatorio
   agrupa las caídas en fallback por firma de TOKENS (minúsculas, sin
   acentos/números/muletillas, únicos y ordenados → redacciones distintas
   del mismo tema caen juntas) + estado atendida/ignorada; REGRESIÓN = se
   le enseñó y sigue cayendo. La enseñanza SIEMPRE entra por el camino
   determinista estándar, nunca auto-modificando prompts en caliente.
   GOTCHA: fechar el estado con el reloj de la BD (created_at es de MySQL;
   mezclar relojes PHP/BD abre ventana de falsas regresiones).

```markdown
# [MARCA] — SaaS para [GIRO]

## Qué es
SaaS multi-tenant white-label para [giro] en México. Mensualidad base +
módulos à la carte. PWA mobile-first. Playbook completo de la fábrica:
docs/PLAYBOOK-SAAS-VERTICALES.md (LEY de este repo).

## Stack
Laravel 12 · PHP 8.3 · MySQL 8 · Redis · Livewire 3 + Alpine + Tailwind ·
Filament 3 (admin) · Pest · Cashier/Stripe · Docker local y prod (Caddy).

## Comandos
- Tests: ./vendor/bin/pest  (SIEMPRE antes de dar algo por cerrado)
- Lint tenancy: php tools/lint_tenancy.php
- Migraciones: php artisan migrate  (jamás SQL a mano)
- Dev: docker compose up -d → http://localhost:8080
- Deploy staging: [llenar]  · Deploy prod: [llenar]

## Reglas duras (CI las vigila)
1. Toda tabla de negocio: tenant_id + global scope + índice (tenant_id,...).
2. Módulo de dinero sin test de invariante = NO se mergea (invariantes
   canónicos: playbook Apéndice B).
3. Archivos ≤800 líneas. Componentes, no vistas monolito.
4. CERO hex en vistas: tokens semánticos; temas solo en /themes.
5. Todo lo externo (WhatsApp/mail/push/PDF) va por cola.
6. Textos en lang/ (es) desde el inicio.

## Modo de trabajo
Cada respuesta termina con: HECHO (con prueba) · SIGUIENTE PASO (uno) ·
[MANUAL]/[DISEÑO] si aplican. Libertad total en lo reversible. Pantallas de
usuario final → protocolo [DISEÑO] (playbook §9b), nunca diseñar de cero.
1 sesión = 1 objetivo, cierre con test verde + commit.

## Estado actual
[la IA actualiza esta sección al cierre de cada sesión: fase, último módulo,
pendientes [MANUAL]]
```

---

## §12. PROMPT MAESTRO v2 (pegar idéntico en Claude y Codex al arrancar)

```
Eres el arquitecto y constructor principal de un nuevo SaaS multi-tenant
white-label para PyMEs mexicanas del giro: [VETERINARIAS — confirmar tras
validación de campo; plan B: talleres mecánicos].

LEY DEL PROYECTO: el archivo docs/PLAYBOOK-SAAS-VERTICALES.md de este repo.
Léelo COMPLETO antes de proponer nada. Resumen no negociable:
- Stack: Laravel 12, MySQL 8, Redis, Livewire+Alpine+Tailwind, Filament,
  Pest, Cashier. Monolito modular. Nada de SPA/microservicios.
- Prohibiciones P1-P12 (§4): tests de dinero primero, tenancy por global
  scopes (jamás WHERE a mano), archivos ≤800 líneas, colas para lo externo,
  staging semana 1, tokens de diseño sin hex en vistas.
- Se PORTAN de Medisoft Hoteles (repo de referencia): invariantes de dinero
  con candados (Apéndice B del playbook), white-label por tokens, módulos
  activables con precio, RBAC por tenant, PWA, patrón core+adaptador de giro.
- Arquitectura de referencia §6: tenants/sucursales/clientes/dinero, IDs
  públicos ULID, tabla única de mensajes salientes, timezone por tenant.

TU PRIMER ENCARGO: plan detallado y ejecutable de Fases 0 y 1 (§8 del
playbook), paso a paso: qué archivo se crea, qué test lo protege, qué comando
lo verifica. Marca [MANUAL] (cuentas, DNS, llaves, Stripe) y [DISEÑO]
(pantallas para Claude Design, protocolo §9b) con instrucciones exactas.

MODO PERMANENTE: cada respuesta termina con HECHO (con prueba) · SIGUIENTE
PASO (uno solo) · pendientes [MANUAL]/[DISEÑO]. Libertad total en lo
reversible; pregunta solo decisiones de negocio. Economía de tokens (§10c):
lecturas quirúrgicas, 1 sesión = 1 objetivo, verificar con tests.
```

---

## §13. GO-TO-MARKET (vender es la mitad del sistema)

### 13a. Precios (patrón para cualquier giro)
- **Base** (core + módulo estrella): veterinarias $649-899 MXN/mes; talleres
  $999-1,499. Bandera: **cero comisión por transacción**.
- **Módulos** à la carte: $99-299 MXN/mes c/u (recordatorios, pensión,
  facturación CFDI…). El ARPU crece sin re-vender desde cero.
- **Anual prepagado = 2 meses gratis** (caja adelantada + churn bloqueado).
- **Setup/onboarding $0** (el importador CSV lo hace gratis en minutos —
  eso ES la barrera de entrada de los competidores viejos).
- **5-10 "clientes fundadores"**: 50% de por vida a cambio de feedback
  semanal y testimonio. Se buscan ANTES de terminar la Fase 3.

### 13b. La demo de 3 funciones (regla de oro de la venta PyME)
Nunca demostrar el sistema completo. Tres funciones que peguen al dolor:
- Veterinaria: (1) agenda del día en el teléfono, (2) el recordatorio de
  vacuna que le llega al dueño por WhatsApp, (3) el expediente de la mascota
  en 2 taps. Cierre: "esto con TU logo".
- Taller: (1) orden con fotos en 60 segundos, (2) el WhatsApp automático de
  "su auto está listo", (3) el corte de caja del día.
Duración: <10 minutos, en SU mostrador, con el tenant demo precargado.

### 13c. Validación de campo (antes de escribir código)
5 entrevistas por giro (guion completo: Apéndice A). Regla de decisión:
≥3/5 dicen "sí pago $X" con X ≥ precio base → GO. Si no, plan B (talleres).

### 13d. Canales (bootstrap, sin presupuesto de ads)
1. Venta directa en frío local (la demo de 10 min) — las primeras 20 cuentas.
2. Referidos con incentivo (1 mes gratis por colega que contrate).
3. El white-label ES marketing: la app con la marca del negocio la ven todos
   sus clientes finales.
4. Grupos de gremio (asociaciones veterinarias, grupos de FB de talleres).
5. Contenido SEO local solo DESPUÉS de 20 clientes (antes es distracción).

### 13e. Onboarding y soporte
- Meta: tenant productivo en 24 horas (importador CSV + 3 videos de 2 min).
- Soporte por WhatsApp con horario; los bugs entran como issues etiquetados
  por la IA en la siguiente sesión.
- Churn playbook: cliente sin login en 14 días = llamada; cancelación =
  entrevista de salida obligada (dato de producto, no derrota).

---

## §14. LEGAL Y CUMPLIMIENTO MX (lo aburrido que te salva)

1. **LFPDPPP**: aviso de privacidad (simple y visible) + acuerdo de
   tratamiento de datos con cada tenant (ellos son responsables; tú encargado).
2. **Datos de salud HUMANA** (giro dental/médico) = datos sensibles: cifrado
   at-rest de campos clínicos, consentimiento expreso, NOM-024 si se llega a
   expediente clínico formal. **Ventaja de veterinarias: los datos de mascotas
   NO son datos personales sensibles** — todo el valor del expediente sin la
   carga regulatoria. (Punto extra para el giro #1 elegido.)
3. **Facturación propia**: para cobrar mensualidades se factura CFDI. Stripe
   MX + un PAC (Facturama API) automatiza esto — montarlo en Fase 7.
4. **Contrato de servicio (T&C)**: límite de responsabilidad, SLA honesto
   (99.5%), propiedad de los datos del tenant + derecho de exportación (CSV) —
   la exportación además VENDE ("tus datos son tuyos", ataca el miedo #1).
5. **WhatsApp**: con API oficial, las plantillas se pre-aprueban con Meta;
   campañas de marketing requieren opt-in del cliente final.
6. Marca: registrar el nombre del giro 2 en IMPI (~$3,100 MXN, en línea)
   cuando la validación dé GO — antes de imprimir nada.

---

## §15. MÉTRICAS (el tablero de los lunes)

**Del negocio (semanal):** MRR y su delta · tenants activos vs pagando ·
churn mensual (meta <3%) · ARPU (meta: crecer con módulos) · demos dadas →
cierres (meta ≥25%) · NPS informal trimestral.

**Del producto (semanal, 5 min):** logins por tenant (¿quién no entra hace
14 días?) · uso del módulo estrella (¿la función que vendió la demo se usa?) ·
mensajes WhatsApp enviados/fallidos.

**Del sistema (automático, alerta si se sale):** uptime (UptimeRobot) ·
errores nuevos en Sentry · p95 de respuesta · backup verificado del día ·
CI verde en main.

**La regla:** si una métrica no cambia una decisión, no se mide.

---

## §16. RIESGOS Y ANTI-PATRONES (leer cuando haya tentación)

1. **Feature creep pre-venta** — el sistema de hoteles tiene 27 secciones;
   la demo vendedora usa 3. Construir la #4 antes del cliente 1 es P11.
2. **El cliente grande que pide "solo una cosita"** — se cotiza como módulo
   con precio o se dice no. Un fork por cliente mata la fábrica.
3. **Guerra de precios abajo** — si aparece un "gratis" (Fresha style), NO
   bajar precio: subir de nicho (premium/cadenas/white-label). Está probado
   que abajo no hay margen.
4. **Dependencia de una persona** (tú): el playbook + CLAUDE.md + docs cortos
   existen para que cualquier sesión de IA (o un segundo humano) continúe sin
   arqueología. Mantenerlos al día ES trabajo de producto.
5. **El VPS único** — aceptable hasta ~$50k MXN de MRR; después: BD gestionada
   o segundo nodo. Redis/S3 desde el día 1 hacen esa mudanza trivial (P6 de
   hoteles, resuelto por diseño aquí).
6. **Quemarse** — la cadencia §17 es sostenible; los sprints heroicos de 3 AM
   producen los bugs que luego cuestan semanas. Medisoft lo demostró en ambas
   direcciones.
7. **Señales de que el giro no valida** (abortar sin pena): <3/5 en
   entrevistas, demos que gustan pero no cierran a 2ª visita, trials que no
   importan sus datos. Abortar un giro cuesta 2 semanas; empujarlo un año.

---

## §17. ROADMAP INMEDIATO (los próximos 14 días)

| Día | Acción | Quién |
|---|---|---|
| 1-2 | [MANUAL] Agendar 5 veterinarias + 5 talleres (conocidos, gremio, en frío) | Max |
| 3-7 | [MANUAL] Entrevistas con guion Apéndice A; anotar TODO | Max |
| 5 | (Paralelo) VPS hotelero: contratar + deploy con checklist — el giro 1 debe quedar en producción y facturando solo | Max + IA |
| 8 | Decisión GO/plan B con la regla ≥3/5 | Max |
| 9 | [MANUAL] Crear repo nuevo + copiar playbook + CLAUDE.md (§11) · Registrar marca si GO | Max |
| 9 | Pegar prompt maestro (§12) en Claude y Codex → protocolo dual §10b | Max |
| 10-11 | Fusionar planes → Plan v1.0 · Ejecutar Fase 0 (Apéndice C) | IA |
| 12-14 | Fase 1 en marcha; primer [DISEÑO] (login + dashboard) a Claude Design | IA + Max |

---

## APÉNDICE A. Guion de entrevista de validación (20 min)

1. Cuéntame un día normal: ¿cómo agendas, cobras y recuerdas pendientes hoy?
   (dejar hablar; anotar herramientas: papel, Excel, WhatsApp)
2. De todo eso, ¿qué es lo que MÁS tiempo te roba o más dinero te cuesta?
3. ¿Qué usas de software hoy y cuánto pagas? ¿Qué odias de eso?
4. (Demo de 3 funciones, §13b, 5 min) ¿Esto resolvería lo que me dijiste?
5. ¿Cuánto pagarías al mes por esto? (SILENCIO; que digan número primero)
6. Si te dijera $[precio base], ¿lo contratas este mes? ¿Qué te frenaría?
7. ¿Quién más decide? ¿Me presentas a 2 colegas si esto te sirve?

Registrar por entrevista: dolor #1 · herramienta actual · gasto actual ·
número que dijeron · objeción principal · GO/NO personal.

## APÉNDICE B-bis. Prueba de carga (k6) — práctica estándar de la fábrica

Antes del primer cliente de cada giro se corre una prueba de carga con k6
(receta portada de Medisoft: `tools/k6/`). No requiere instalar nada — imagen
`grafana/k6` en la red de docker-compose. Tres escenarios: humo (validar
script), carga (20 VUs ≈ 100 usuarios reales, con think-time), estrés (rampa
hasta el punto de quiebre). El mismo script se reusa contra staging con
`-e BASE_URL=`. Regla de la fábrica: **se corre local para cazar cuellos de
código, y se repite en el VPS para medir capacidad real de la máquina.**
Métricas que importan: `http_req_failed` ~0%, `p(95)` <500ms en vistas y
<150ms en APIs, y qué pantalla degrada primero (candidata a optimizar).
Ejercitar SIEMPRE también un escenario de POSTs de dinero contra la BD de
prueba (es donde los candados FOR UPDATE se tensan). Baseline de referencia
de hoteles: `docs/prueba-carga-baseline.md` (aguantó ~100 usuarios reales con
p95 162ms y 0% error).

## APÉNDICE B. Invariantes de dinero canónicos (tests obligatorios, portados de Medisoft)

1. Solo puede existir UN corte abierto por caja (candado de fila + re-check).
2. Ningún movimiento cae en un corte cerrado (serializar cierre vs registro).
3. El cierre es atómico: totales congelados = suma exacta de movimientos.
4. Un corte cerrado no puede re-cerrarse.
5. Un abono/pago jamás excede el saldo pendiente.
6. Con saldo 0 no se aceptan más cobros (anti doble cobro).
7. Todo abono queda ligado a su movimiento de caja (dinero real, no nota).
8. Un reverso solo se aplica UNA vez, y genera contramovimiento (no borra).
9. Crédito/deuda derivada = bruto − ledger absorbido (nunca campo suelto).
10. Anular/reabrir un periodo pagable exige verificar pagos activos (candado
    anti doble pago).
11. Aislamiento: NINGUNA operación de dinero cruza tenants (test A/B).
12. Toda cifra mostrada se recalcula de la fuente, no de acumuladores sueltos.
13. Cancelar y no-show resuelven SIEMPRE el dinero ya cobrado, sin importar el
    estado de la reserva: cancelar → devolución registrada en caja (egreso
    'Devoluciones', maneja pagos de cortes cerrados); no-show → retención
    reclasificada como ingreso 'Penalización no-show'. Un anticipo jamás queda
    huérfano en un estado terminal (bug pagado: anticipos de confirmadas
    canceladas quedaban como ingreso sin rastro).
14. Las vistas de cobranza derivan el "por cobrar" EXCLUYENDO estados
    terminales sin adeudo (canceladas): una reserva cancelada no es deuda
    (bug pagado: $21,150 de 'saldo por cobrar' fantasma, 75% del total).
15. La resolución de un no-show es una ACCIÓN de primera clase en la alerta
    (no solo "hacer check-in"): el estado colgado retiene inventario y
    ensucia los tableros de salidas.

## APÉNDICE C. Checklist Fase 0 (comando por comando, para la IA)

1. `laravel new [marca] --pest` · repo GitHub privado · rama main protegida.
2. Docker: compose con php-fpm+caddy, mysql:8, redis:7 (portar recetas del
   repo hotelero: log rotation, healthcheck, php-prod.ini).
3. `.env.example` completo desde el día 1 (DB, Redis, Stripe test, WhatsApp,
   Sentry DSN, APP_TIMEZONE). `.gitignore`: dumps, exports, artefactos.
4. CI (GitHub Actions): Pest + `php -l` + linter tenancy (portar
   lint_tenancy.php adaptado a Eloquent) + linter hex (`grep -rE '#[0-9a-f]{3,6}'
   resources/views | fuera de themes → fail`) + tope 800 líneas
   (`awk 'END{if(NR>800) exit 1}'` sobre archivos nuevos).
5. Sentry SDK + UptimeRobot al staging. `/health` con token.
6. Staging: subdominio + deploy por Action (build → migrate → restart).
7. CLAUDE.md (§11 llenado) + carpeta `design/{encargos,entregas}` + este
   playbook copiado a `docs/`.
8. Portar `tools/k6/` (prueba de carga) — se ejercita al cerrar Fase 3 (dinero)
   y antes del primer cliente (Apéndice B-bis).
9. Commit `feat: fase 0 — cimientos` con CI verde. → Criterio de salida §8.

---

*Fin del playbook. Cuando la realidad contradiga a este documento, gana la
realidad — y se actualiza el documento en el mismo commit.*
