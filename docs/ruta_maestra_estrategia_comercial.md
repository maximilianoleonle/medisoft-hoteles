# Ruta maestra de estrategia comercial — Medisoft Hoteles

Última actualización: 26 de julio de 2026.

Este documento controla el orden del trabajo comercial. No se considera terminada una etapa solo por haber conversado sobre ella: debe producir sus entregables y decisiones.

## Decisiones vigentes

- Objetivo principal: conseguir una venta nueva en la zona de Santa Catarina Juquila.
- Alternativas de cierre: contratación directa o piloto formal de 30 días.
- El piloto debe tener precio posterior conocido, responsables, fechas, objetivos, revisiones y decisión programada.
- Medisoft ya opera en Hotel San Nicolás y Hotel Los Cedros.
- Hotel San Nicolás: aproximadamente 8 meses de uso; antes trabajaba en libreta.
- Hotel Los Cedros: aproximadamente 14–15 meses de uso; combinaba Excel y libretas; acepta dar testimonio y servir como referencia.
- Paquete base: Dashboard, Habitaciones, Reservaciones, Huéspedes, Caja, Usuarios, Roles y permisos, Mantenimiento, Notificaciones y PWA.
- Opcionales actualmente considerados: Inventario, Facturación, Centro documental, Reputación y encuestas, Descuentos de huésped y Tarifas dinámicas.
- Compras, proveedores, recepción de mercancía y cuentas por pagar quedan fuera de la oferta.
- Lealtad queda bloqueado temporalmente y no se promociona. Su valor completo depende del Motor de reservaciones y Promociones/cupones.
- No utilizar los precios provisionales existentes como precios comerciales definitivos.
- No prometer operación offline sincronizable mientras `/api/sync` permanezca bloqueado. **Sí se puede vender el offline de CONSULTA** — ver la sección siguiente, que define exactamente qué prometer.

## Modo sin internet: qué prometer y qué no (auditoría 2026-07-30)

**La frase que sí se sostiene:** *"Si se cae el internet, Medisoft sigue abriendo y puedes consultar la información del hotel. Para registrar movimientos hace falta conexión."*

**La frase que NO:** *"Sigue operando sin internet"* o *"guarda y envía solo cuando vuelve la señal"*. La captura sin conexión está apagada desde el 26-jul-2026 porque encolaba cobros que nunca llegaban al servidor.

Lo que el hotelero puede esperar de verdad:

- La app instalada **abre sin internet** y entra a la última pantalla útil, no a un error del navegador.
- **Consultar** habitaciones, reservaciones (llegadas de los próximos 30 días), huéspedes y el corte de caja tal como estaban en la última sincronización.
- **Buscar de verdad** por nombre o teléfono sobre ~1,000 huéspedes y ~3,000 reservaciones guardados en el equipo: no es una lista congelada.
- El **tablero de Limpieza** es la pantalla más sólida sin conexión, y es la del rol que más se mueve por el hotel.
- La app **avisa siempre de cuándo son los datos** que muestra ("información guardada hace 2 horas") y avisa cuando no pudo aplicar una búsqueda.
- Al volver el internet **se actualiza sola**, sin apretar nada.

Límites que hay que decir ANTES de vender, no después:

| Límite | Por qué |
|---|---|
| No se puede cobrar, hacer check-in/check-out ni crear reservaciones sin internet | La captura sin conexión está apagada a propósito: encolaba cobros que no llegaban |
| El equipo necesita haber entrado **con internet al menos una vez**, y haber abierto las pantallas que querrá consultar | Solo se guarda lo que se visitó |
| Los datos son de la última sincronización (se refresca cada 15 min con señal), no del minuto exacto | Es una copia, no una conexión |
| Los precios que se ven sin internet son la **tarifa base**: no incluyen temporada ni descuentos | El cálculo definitivo lo hace el servidor |
| En iPhone la app se cierra sola en segundo plano y no hay sincronización automática oculta | Límite de iOS, no del sistema; ninguna PWA lo evita |
| El sistema operativo puede borrar la copia local si el equipo se queda sin espacio | Comportamiento del navegador |
| Al cerrar sesión la copia se borra del equipo (por protección de datos de huéspedes) | Decisión deliberada: ver abajo |

**Protección de datos (responde a "¿y si se roban la tablet de recepción?"):** los datos de huéspedes guardados en el equipo se borran al cerrar sesión y cuando la sesión caduca. Recomendación comercial: pedir al hotel que su personal cierre sesión al terminar el turno — y decirles que ese es justamente el precio de tener la información disponible sin internet.
- Facturación administra solicitudes; no timbra CFDI ni sustituye al SAT, PAC o sistema del contador.

## Criterio de evidencia

Toda conclusión debe identificarse como una de estas categorías:

- **Dato verificado:** respaldado por fuente y fecha.
- **Hallazgo del sistema:** comprobado en código, interfaz o prueba.
- **Testimonio:** declarado por un hotel o usuario identificado.
- **Inferencia:** conclusión razonada a partir de evidencia.
- **Hipótesis:** pendiente de validación con el mercado.
- **Dato no disponible:** no se inventa ni se presenta como hecho.

## 1. Definir el objetivo comercial

Definir:

- Qué se quiere conseguir en Juquila.
- En cuánto tiempo.
- Cuántos hoteles pueden atender.
- Cuánto pueden invertir.
- Capacidad de instalación, capacitación y soporte.
- Qué se considerará una prueba exitosa.

**Estado:** avanzado. El objetivo principal está definido; falta convertirlo posteriormente en metas y métricas finales según la duración real de la estancia.

## 2. Auditoría interna de Medisoft

Analizar:

- Funciones terminadas y funciones inmaduras.
- Problemas concretos que resuelve.
- Diferencias frente a Excel, libreta y otros sistemas.
- Facilidad de implementación y aprendizaje.
- Requisitos de internet, equipos y operación.
- Seguridad, respaldo y soporte.
- Costos y capacidad de atención.
- Modelo de precios y módulos.
- Fortalezas, debilidades y riesgos.
- Qué puede prometerse y qué todavía no.

Entregables:

- Auditoría comercial de módulos.
- Análisis interno.
- Matriz problema–capacidad–beneficio–evidencia.
- Fortalezas, debilidades y riesgos.
- Requisitos de implementación.
- Límites de la promesa comercial.
- Propuesta de valor inicial.

**Estado:** documentado y utilizable como base provisional. Existen `docs/auditoria_comercial_modulos_20260725.md` y `docs/analisis_interno_comercial_medisoft_20260726.md`. Los datos que solo Max o los hoteles pueden confirmar quedaron concentrados en `docs/estrategia_comercial/12_pendientes_imprescindibles.md`; no bloquean investigación ni prospección.

## 3. Investigación del entorno y del mercado

Investigar:

- Hoteles de Santa Catarina Juquila y zona definida.
- Tamaño aproximado, categoría, ubicación y presencia digital.
- Turismo, temporadas, peregrinación y demanda.
- Competidores directos e indirectos.
- Sistemas hoteleros y precios públicos comprobables.
- Nivel de digitalización y formas de administración.
- Contexto económico, tecnológico, legal y social.
- Canales de captación de huéspedes.

Herramientas:

- PESTEL con implicaciones comerciales.
- Análisis de industria.
- Análisis competitivo.
- Cinco Fuerzas de Porter, solo si aporta decisiones.
- Tendencias.
- TAM, SAM y SOM con límites explícitos.
- Directorio y mapa de prospectos.

**Estado:** completado documentalmente en `docs/estrategia_comercial/03_mercado_entorno_y_competencia.md`. El directorio nominal y la digitalización por hotel requieren verificación de campo.

## 4. Investigación directa con hoteles

Entrevistar brevemente a dueños, administradores y recepcionistas para conocer:

- Cómo administran reservaciones y habitaciones.
- Errores frecuentes.
- Costos de tiempo o dinero.
- Herramientas actuales.
- Decisor e influenciadores.
- Experiencias con otros sistemas.
- Temores frente al cambio.
- Valor atribuido a soporte, capacitación y facilidad.
- Evento que provocaría una compra ahora.

Las preguntas deben investigar hechos y experiencias recientes, no preguntar simplemente si comprarían.

**Estado:** protocolo, muestra, guion, ficha y criterio de análisis completados en `docs/estrategia_comercial/04_investigacion_directa.md`. Las entrevistas reales no se sustituyen con datos inventados.

## 5. Segmentación y selección del mercado meta

Segmentar por:

- Número de habitaciones.
- Tipo de hotel.
- Nivel de digitalización.
- Complejidad operativa.
- Dependencia de OTA o venta directa.
- Problema dominante.
- Capacidad y disposición de pago.
- Acceso al decisor.
- Urgencia de cambio.

Seleccionar el segmento inicial mediante una matriz de atractivo y facilidad de entrada. Definir también qué prospectos no conviene perseguir.

**Estado:** segmentación y mercado meta provisional completados en `docs/estrategia_comercial/05_segmentacion_y_mercado_meta.md`; sujetos a recalibración con entrevistas.

## 6. Buyer personas y comité de compra

Construir perfiles basados en evidencia para:

- Dueño.
- Administrador o gerente.
- Recepcionista.
- Contador u otro influenciador.

Definir para cada uno:

- Preocupaciones.
- Ganancia esperada.
- Riesgos percibidos.
- Objeciones.
- Información necesaria.
- Poder en la decisión.

**Estado:** perfiles y mapa de compra provisionales completados en `docs/estrategia_comercial/06_buyer_personas_y_compra.md`; deben validarse en campo.

## 7. Posicionamiento, oferta y precio

Construir:

- Propuesta de valor.
- Problema principal.
- Resultado prometido responsablemente.
- Paquete base y opcionales.
- Implementación.
- Capacitación.
- Soporte.
- Piloto de 30 días.
- Argumentos de retorno sin cifras fabricadas.
- Comparación contra mantener la operación actual.
- Mensajes por segmento.
- Precio basado en mercado, valor y sostenibilidad.

**Estado:** reabierto y corregido después de revisión comercial. Se eliminó el depósito, el piloto quedó gratuito y orientado a ganar confianza, y el precio permanece como hipótesis interna no aprobada. La etapa sigue sujeta a revisión de la responsable comercial.

## 8. Proceso de venta consultiva

Embudo:

1. Prospección.
2. Contacto breve.
3. Diagnóstico.
4. Calificación.
5. Microdemostración basada en el problema.
6. Demostración completa cuando corresponda.
7. Propuesta.
8. Seguimiento.
9. Cierre.
10. Implementación.
11. Capacitación.
12. Retención y referidos.

**Estado:** reabierto por revisión comercial. Para las visitas inmediatas, el proceso fue sustituido por una ruta más cálida y conversacional en `docs/estrategia_comercial/21_capacitacion_venta_presencial_manana.md`.

## 9. Herramientas comerciales

Preparar:

- Guion de visita presencial.
- WhatsApp inicial.
- Respuestas para Facebook e Instagram.
- Preguntas de diagnóstico.
- Ficha de calificación.
- Demostraciones de 3, 10 y 20 minutos.
- Presentación comercial.
- Propuesta y cotización.
- Comparativo.
- Biblioteca de objeciones.
- Seguimientos.
- Registro de prospectos.
- Guion de cierre.
- Solicitud de referidos.
- Contenido por etapa del embudo.

**Estado:** el manual detallado se conserva como biblioteca de consulta, pero su tono fue moderado y condensado para uso real en `docs/estrategia_comercial/21_capacitacion_venta_presencial_manana.md`. Incluye rutas separadas para recomendación y visita en frío.

## 10. Capacitación y práctica

Practicar:

- Apertura de conversaciones.
- Diagnóstico sin interrogatorio.
- Beneficios sin exageración.
- Calificación.
- Demostración selectiva.
- Defensa del precio.
- Objeciones.
- Excusas frente a objeciones reales.
- Solicitud del siguiente compromiso.
- Cierre.
- Seguimiento.

Realizar simulaciones con dueño ocupado, gerente desconfiado, recepcionista sin autoridad y prospecto que prefiere Excel/libreta.

**Estado:** programa, simulaciones y rúbrica completados en `docs/estrategia_comercial/10_capacitacion_y_practica.md`.

## 11. Prueba de campo y medición

Medir:

- Hoteles identificados.
- Contactos.
- Conversaciones con decisores.
- Diagnósticos.
- Prospectos calificados.
- Demostraciones.
- Propuestas.
- Cierres.
- Motivos de pérdida.
- Tiempo por etapa.
- Costo de adquisición.
- Ingreso esperado.
- Retención.

**Estado:** sprints de 4 y 10 días, tablero, KPI y experimentos completados en `docs/estrategia_comercial/11_prueba_campo_y_medicion.md`.

## Próximo trabajo

Ejecutar investigación y visitas con el expediente de `docs/estrategia_comercial/`. Antes de cobrar o firmar, resolver únicamente los puntos de `docs/estrategia_comercial/12_pendientes_imprescindibles.md`.
