# Análisis de mercado: siguiente giro de negocio

**Fecha:** 2026-07-12 · **Método:** investigación web de competidores/precios vigentes (jul 2026) + censos INEGI/DENUE + criterio de reuso del core Medisoft · **Complementa a:** plan-siguiente-giro.md

## 1. Metodología (qué evalúa este análisis)

Cada vertical se calificó 1-10 en ocho dimensiones, ponderadas para responder UNA pregunta: **¿dónde puede un equipo de 1-2 personas con IA generar más ganancia real en 24 meses?**

| Dimensión | Peso | Por qué importa |
|---|---|---|
| Tamaño de mercado MX (unidades) | 15% | Techo de clientes alcanzables |
| Intensidad de competencia | 20% | Un gratis dominante (tipo Fresha) mata márgenes |
| Ticket mensual alcanzable | 15% | Define cuántos clientes necesitas para vivir |
| Reuso del core Medisoft | 20% | Velocidad al demo = costo de oportunidad |
| Retención / switching cost | 10% | Datos acumulados (expedientes) = churn bajo |
| Acceso al decisor / ciclo de venta | 10% | PyME de dueño único cierra en semanas; comités, en meses |
| Riesgo regulatorio | 5% | Datos de salud exigen más; también crean foso |
| Expansión de ARPU (módulos/transacciones) | 5% | Cobrar más al mismo cliente con módulos à la carte |

## 2. Datos duros recabados (julio 2026)

- **Estética/belleza**: 310,133 unidades económicas en MX (DENUE/DataMéxico) — el mercado más grande, PERO competencia feroz: AgendaPro (20,000+ negocios LatAm, USD $29-299/mes), Booksy, **Fresha gratis sin mensualidad**, AgenditApp desde $10.
- **Veterinarias**: competencia fragmentada y débil — Vetmanger (mexicano, planes flexibles), GVET, OkVet (gratis básicos); rango USD $10-200/mes. Sin líder dominante en MX.
- **Talleres mecánicos**: competidores locales chicos (Mecanica.mx ~400 talleres, AutoSoft, VisorUS); los gringos cuestan USD $179-199 (Shopmonkey, Tekmetric) — hueco de precio enorme en medio. Digitalización bajísima.
- **Gimnasios**: guerra de precios ya declarada — Fitco $999-3,299 MXN, Gym&i $799, GYMmx desde $199, Connect Gym desde $0. Piso destruido.
- **Consultorios/dental**: Dentalink (Healthatom) líder regional, Medilink caro (1.5x promedio), planes básicos desde ~$600 MXN; Doctoralia domina la agenda mental del paciente. Mercado gigante, precios sanos, competencia seria pero no infinita.
- **Escuelas privadas**: Cometa (bien fondeado), SKOLI, Servoescolar, Gestor Escolar, Aspel — saturado, y el ciclo de venta lo deciden comités.
- **Funerarias**: 13,056 unidades económicas MX, derrama ~$8,700 MDP/año, 64,000 empleos; **42% sin tecnología integrada**; SaaS PyME mexicano casi inexistente (los jugadores son españoles: Gularis, Zankuda; SOFI es app interna de J. García López).

## 3. Matriz de evaluación (score ponderado /10)

| Vertical | Mercado | Competencia | Ticket | Reuso | Retención | Venta | Regul. | ARPU+ | **TOTAL** |
|---|---|---|---|---|---|---|---|---|---|
| **Veterinarias** | 7 | 9 | 7 | 9 | 9 | 9 | 8 | 8 | **8.4** |
| **Talleres mecánicos** | 9 | 8 | 8 | 7 | 8 | 8 | 9 | 8 | **8.1** |
| **Consultorios/dental** | 9 | 5 | 9 | 8 | 9 | 7 | 5 | 9 | **7.6** |
| **Estética premium/cadenas** | 10 | 3 | 6 | 9 | 6 | 8 | 9 | 7 | **6.9** |
| **Funerarias** | 5 | 9 | 8 | 7 | 8 | 7 | 8 | 8 | **7.4**¹ |
| Gimnasios | 6 | 3 | 5 | 8 | 7 | 8 | 9 | 7 | 6.2 |
| Escuelas privadas | 7 | 4 | 8 | 7 | 9 | 4 | 7 | 8 | 6.5 |
| Property mgmt/Airbnb | 5 | 4 | 7 | 10 | 8 | 7 | 9 | 7 | 6.6² |

¹ Funerarias puntúa alto en score pero su techo de mercado es chico: entra al top 5 como "nicho monopolizable", no como apuesta principal.
² Reuso perfecto pero no diversifica (mismo mercado que hoteles) y compite global (Guesty, Lodgify, Hostaway).

## 4. TOP 5 (ordenado por ganancia esperada / esfuerzo)

### 🥇 1. Veterinarias — el punto dulce
- **Por qué gana:** mercado desatendido (ningún líder mexicano), dueños jóvenes que sí pagan apps, gasto en mascotas creciendo a doble dígito, y el reuso es EXTREMO: citas=reservas, expediente=huésped+documentos, inventario/POS ya existen, y las veterinarias con **hotel y estética de mascotas** usan nuestro core hotelero LITERAL (pensiones con calendario de ocupación — nadie más lo tiene nativo).
- **Retención brutal:** el expediente médico de años de las mascotas no se migra fácil → churn bajísimo.
- **Modelo:** $549-899 MXN/mes base + módulos (recordatorio vacunas WhatsApp, pensión/hotel, laboratorio). Meta 24 meses: 150-250 clínicas → **$100-200k MXN/mes**.
- **Riesgo principal:** ticket medio-bajo; se mitiga con módulos y cero comisiones (vs los que cobran por transacción).

### 🥈 2. Talleres mecánicos — el océano azul sucio
- **Por qué:** ~100k+ talleres, digitalización casi nula, competencia local débil y la gringa cuesta $179-199 USD — hueco gigante en $800-1,500 MXN. El dolor es visceral: órdenes perdidas, refacciones sin control, clientes preguntando "¿ya está mi coche?" por WhatsApp todo el día.
- **Reuso:** orden de servicio = reserva con estados + evidencia fotográfica (uploads resuelto) + inventario de refacciones + caja + WhatsApp de avance automático (¡la killer feature, y ya la tenemos!).
- **Modelo:** $799-1,499 MXN/mes. Meta 24 meses: 100-180 talleres → **$100-200k MXN/mes**, con ticket más alto que veterinarias.
- **Riesgo:** el dueño de taller es el adoptante más terco; la venta exige demo presencial simple ("tu cliente recibe el avance por WhatsApp solo").

### 🥉 3. Consultorios y clínicas dentales — el premio grande a más plazo
- **Por qué:** el mercado más rico (dentistas facturan bien y pagan $600-1,500+ MXN sin pestañear), retención altísima por expediente, y ARPU expandible (recordatorios, financiamiento de tratamientos = CxC ya construido, receta PDF, copiloto IA).
- **Contra:** Dentalink/Medilink son competencia seria y Doctoralia posee la agenda mental. Se entra por el flanco: **white-label** (la clínica quiere SU app con SU marca — nadie se lo da) + precio medio + WhatsApp nativo.
- **Modelo:** $899-1,999 MXN/mes. 100 clínicas → **$120-200k MXN/mes**. Es #3 solo porque cuesta más ganar cada cliente que en 1 y 2.

### 4. Estética/spa PREMIUM y cadenas — solo con el ángulo white-label
- **Por qué entra:** 310k establecimientos es un océano; pero abajo Fresha es gratis y Booksy barato — ahí no se pelea. El juego rentable es arriba: estudios premium y cadenas de 2-10 sucursales que quieren SU app de marca, comisiones de staff bien resueltas (lo que todos hacen mal) y cero comisión por transacción.
- **Modelo:** $1,200-2,500 MXN/mes por cadena. 60-100 cuentas → **$90-200k MXN/mes**.
- **Por qué no es #1:** el riesgo de quedar atrapado en la guerra de abajo es real; exige disciplina de nicho.

### 5. Funerarias — el nicho monopolizable
- **Por qué entra:** 13k unidades con dinero serio ($8,700 MDP/año), 42% sin tecnología, y prácticamente CERO SaaS mexicano para funeraria PyME. Los planes de previsión (pagos mensuales a futuro) son exactamente nuestro CxC + cobranza recurrente. Quien entre primero con producto decente se vuelve el estándar del nicho.
- **Modelo:** $1,500-3,000 MXN/mes (aguantan ticket alto). 40-80 funerarias → **$60-240k MXN/mes**.
- **Por qué es #5:** techo bajo de clientes y tema delicado para marketing; pero como SEGUNDO vertical (giro 3), con el template listo, es dinero casi sin competencia.

## 5. Descartados y por qué
- **Gimnasios:** guerra de precios consumada (desde $0-199 MXN); solo tiene sentido si algún día sobra capacidad.
- **Escuelas:** Cometa fondeado + venta por comité = lento y caro para bootstrap.
- **Property management:** reuso perfecto pero no diversifica y compite global; mejor como extensión del producto hotelero actual que como giro nuevo.

## 6. Decisión recomendada y validación

**Apuesta principal: Veterinarias. Plan B inmediato: Talleres.** Ambos comparten el 90% de la Fase 1-3 del plan, así que el estudio de campo puede correr en paralelo:
- 5 entrevistas con dueños de veterinarias + 5 con talleres (guion: ¿cuánto pagas hoy por administrar? ¿qué te duele cada día? ¿pagarías $700-1,200/mes por [demo de 3 funciones]? ¿quién decide?).
- Si veterinarias valida ≥3/5 con "sí pago", se llena `[GIRO ELEGIDO]` del prompt maestro y arranca Fase 0.
- Talleres queda como giro 3 (o gana si valida mejor).

### Fuentes
- AgendaPro MX / precios y posicionamiento: agendapro.com/mx · capterra.com/p/218709/AgendaPro
- Fresha gratis / Booksy: agendapro.com/blog/agendapro-vs-booksy · turnito.app (mejores software peluquerías MX 2026)
- Veterinarias MX: comparasoftware.com/veterinario · vetmanger.xyz/precios · capterra.com/p/249875/GVET
- Talleres: eligantauto.com (blog 2026, precios Shopmonkey/Tekmetric) · mecanica.mx · autosofttaller.com
- Gimnasios: gymni.mx/blog/mejor-software-gimnasios-mexico-2026 · connectgyms.com/vs/fitco · fitcolatam.com/precios
- Dental/médico: softwaredentalink.com/mx · softwaremedilink.com/planes · akeito.com/blog/software-dental-mexico
- Escuelas: cometaedu.com · skoli.com.mx · servoescolar.mx · forbes.com.mx (colegiaturas)
- Estética 310,133 unidades: economia.gob.mx/datamexico (Salones y Clínicas de Belleza) · DENUE mayo 2026: 6,138,075 establecimientos
- Funerarias 13,056 unidades / 42% sin tecnología: vertigopolitico.com · techla.pro (dic 2025)
