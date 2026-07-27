# Análisis interno comercial — Medisoft Hoteles

Fecha de corte: 26 de julio de 2026  
Propósito: completar la etapa interna previa a la investigación de mercado.  
Alcance: producto, implementación, aprendizaje, requisitos, seguridad, respaldo, soporte, fortalezas, debilidades, riesgos y propuesta de valor inicial.

Este documento no sustituye una auditoría técnica integral ni certifica el entorno productivo de cada hotel. Distingue entre lo confirmado en el repositorio, lo observado en clientes y lo que todavía debe validarse.

## 1. Resumen ejecutivo

Medisoft Hoteles ya puede presentarse como un sistema operativo hotelero funcional, no como una idea o prototipo. Su núcleo reúne habitaciones, reservaciones, huéspedes, caja, usuarios, permisos, mantenimiento, notificaciones y acceso instalable mediante PWA. Dos hoteles de la zona lo utilizan actualmente.

La fortaleza más clara para el mercado inicial no es la cantidad de módulos, sino la transición desde libretas y archivos separados hacia una operación centralizada. Los casos de Hotel San Nicolás y Hotel Los Cedros respaldan esa historia, aunque todavía no existen métricas verificadas de ahorro de tiempo, reducción de errores o retorno económico.

La principal debilidad interna no parece ser la ausencia de funciones. Es la falta de madurez comercial y operativa alrededor del producto:

- No existe todavía una oferta y precio definitivos.
- No hay demostración comercial preparada.
- No hay materiales de venta.
- Instalación y soporte dependen principalmente de Max.
- No se ha definido un nivel de servicio.
- No está documentado el proceso estándar de alta, capacitación y salida de un cliente.
- Algunas capacidades existen técnicamente, pero no deben prometerse todavía, como la sincronización offline.

Dictamen: el producto puede venderse, pero debe venderse por su núcleo comprobable y con límites precisos. La expansión de funciones no debe sustituir la preparación de implementación, soporte y confianza.

## 2. Evidencia utilizada

### Hallazgos del sistema

- Arquitectura PHP 8.2 + MySQL 8 con MVC propio.
- Modelo SaaS multihotel.
- Separación de datos y activación de módulos por hotel.
- Usuarios, roles y permisos.
- Protección CSRF en rutas de escritura.
- Contraseñas almacenadas mediante `password_hash`.
- Límite de intentos de inicio de sesión documentado.
- Cookies de producción configurables como seguras y solo HTTP.
- Respaldo automatizable de base de datos y archivos.
- Verificación de integridad del respaldo y procedimiento de restauración.
- Health check protegido mediante token.
- Pruebas funcionales y suites específicas por módulos.
- `/api/sync` bloqueado con HTTP 423.

### Evidencia de clientes

#### Hotel San Nicolás

- Aproximadamente ocho meses de uso.
- Antes llevaba su control en libreta y no contaba con computadora para la gestión.
- Autorizó mencionar su nombre.

#### Hotel Los Cedros

- Aproximadamente catorce a quince meses de uso.
- Utilizaba computadora y Excel para parte de las finanzas, mientras recepción seguía dependiendo de libretas.
- Autorizó mencionar su nombre.
- Puede proporcionar testimonio y servir como referencia.

### Información declarada por el equipo

- El sistema puede instalarse y operar en un hotel real.
- Max realiza la instalación.
- Max y la responsable comercial imparten capacitación.
- Max proporciona soporte.
- Pueden ofrecer un piloto guiado de 30 días.
- La información puede exportarse a Excel al finalizar el piloto.

## 3. Producto real y alcance vendible

### Núcleo comercial

1. Dashboard.
2. Habitaciones.
3. Reservaciones.
4. Huéspedes.
5. Caja.
6. Usuarios.
7. Roles y permisos.
8. Mantenimiento.
9. Notificaciones.
10. PWA instalable.

### Opcionales comerciales vigentes para la oferta inicial

1. Inventario.
2. Facturación, entendida como seguimiento de solicitudes, no timbrado CFDI.
3. Centro documental.
4. Reputación y encuestas, condicionado por la corrección o definición final de su flujo hacia Google.
5. Descuentos de huésped.
6. Tarifas dinámicas.

### Fuera de la oferta actual

- Compras.
- Proveedores.
- Recepción de mercancía.
- Cuentas por pagar.
- Lealtad / huésped frecuente.
- Motor de reservaciones.
- Promociones y cupones.
- Otros bloques avanzados no seleccionados.

Lealtad permanece bloqueado temporalmente porque su propuesta completa depende del motor de reservaciones y promociones/cupones.

## 4. Problemas que Medisoft puede resolver

### Problema 1: información dispersa

**Situación habitual a validar:** reservaciones, huéspedes, pagos y estados de habitación viven en libretas, Excel, mensajes o memoria del personal.

**Respuesta de Medisoft:** centraliza los registros operativos y permite consultarlos por hotel y usuario.

**Evidencia:** casos declarados de San Nicolás y Los Cedros; implementación de Habitaciones, Reservaciones, Huéspedes y Caja.

### Problema 2: dificultad para conocer disponibilidad y movimientos del día

**Respuesta:** estados de habitación, llegadas, salidas, calendario, reservaciones y dashboard.

**Beneficio responsable:** facilita consultar la operación actual sin reconstruirla manualmente.

### Problema 3: poca trazabilidad del dinero del turno

**Respuesta:** apertura de caja, ingresos, gastos, métodos de pago, corte e historial.

**Beneficio responsable:** organiza y conserva los movimientos capturados durante el turno.

### Problema 4: todos acceden a todo

**Respuesta:** cuentas individuales, activación/desactivación, roles y permisos.

**Beneficio responsable:** permite limitar el acceso según responsabilidades.

### Problema 5: fallas y pendientes olvidados

**Respuesta:** mantenimiento, evidencia, programación preventiva y notificaciones.

**Beneficio responsable:** permite registrar, asignar seguimiento y cerrar pendientes operativos.

### Problema 6: desconocimiento de existencias

**Respuesta:** productos, entradas, salidas, ajustes, mínimos e historial.

**Beneficio responsable:** ayuda a conocer el stock capturado y detectar necesidades de reposición.

### Problema 7: solicitudes de factura perdidas

**Respuesta:** concentración de datos fiscales y estados de atención.

**Beneficio responsable:** organiza la coordinación entre recepción, administración y quien emite la factura externamente.

## 5. Diferencias frente a libreta y Excel

Las siguientes son inferencias comerciales basadas en la estructura del producto. Deben contrastarse posteriormente con hoteles locales.

| Dimensión | Libreta | Excel | Medisoft |
|---|---|---|---|
| Consulta simultánea | Limitada a quien tiene la libreta | Depende del archivo y forma de compartirlo | Acceso por usuarios autorizados |
| Relaciones entre datos | Manual | Requiere fórmulas y disciplina | Huésped, reservación, habitación y caja se relacionan |
| Estado de habitación | Se actualiza manualmente | Debe diseñarse y mantenerse | Estados operativos integrados |
| Historial | Difícil de buscar | Posible, pero depende de estructura | Registros consultables por flujo |
| Permisos | No existen | Limitados o externos al archivo | Roles y permisos |
| Alertas | Dependen de memoria | Requieren configuración adicional | Notificaciones integradas para eventos soportados |
| Caja | Suma y conciliación manual | Fórmulas configuradas por el usuario | Flujo de apertura, movimientos y corte |
| Implementación | Inmediata y conocida | Flexible y familiar | Requiere configuración y capacitación |
| Dependencia tecnológica | Muy baja | Computadora y manejo del archivo | Dispositivo, navegador, internet y servidor |
| Costo de cambio | Bajo | Medio | Mayor al inicio por adopción |

### Conclusión

Medisoft no debe venderse diciendo que “Excel no sirve”. Excel es flexible y conocido. La diferencia defendible es que Medisoft ya estructura el flujo hotelero y conecta áreas que en Excel deben diseñarse, mantenerse y coordinarse manualmente.

Frase comercial provisional:

> “Excel puede guardar información; Medisoft organiza la operación del hotel alrededor de habitaciones, reservaciones, huéspedes y caja.”

## 6. Facilidad de implementación

### Confirmado

- El sistema admite múltiples hoteles.
- Cada hotel tiene contexto, módulos y branding propios.
- Existen herramientas de migración y despliegue.
- Pueden crearse habitaciones individualmente o en lote.
- Existen usuarios, roles y configuraciones por hotel.
- Max ya ha instalado el sistema en hoteles reales.

### Trabajo probable de implementación

1. Alta del hotel.
2. Configuración de identidad.
3. Alta de habitaciones, tipos, capacidades y tarifas.
4. Configuración de usuarios y roles.
5. Definición de caja y categorías necesarias.
6. Carga inicial de huéspedes o reservaciones, si se ofrece.
7. Activación de opcionales contratados.
8. Capacitación.
9. Acompañamiento durante los primeros días.

### Datos todavía no medidos

- Horas promedio de instalación.
- Horas promedio para configurar un hotel pequeño.
- Cantidad de información que puede migrarse.
- Tiempo necesario para que recepción opere sin ayuda.
- Incidencias comunes de la primera semana.
- Esfuerzo de soporte durante el primer mes.

### Riesgo comercial

Sin un proceso estándar, cada alta puede convertirse en un proyecto personalizado. Esto dificulta calcular precio, prometer fecha de arranque y atender varios hoteles simultáneamente.

## 7. Facilidad de aprendizaje

### Indicios positivos

- Las pantallas están organizadas por tareas hoteleras reconocibles.
- Existen vistas móviles.
- Los estados utilizan etiquetas operativas.
- Hay accesos rápidos, resúmenes y filtros.
- Dos hoteles con procesos basados en libreta lograron adoptar el sistema.

### Lo que no puede afirmarse todavía

- Que cualquier recepcionista aprende en determinado número de minutos.
- Que no requiere capacitación.
- Que es “el sistema más fácil”.
- Que elimina todos los errores de captura.

### Validación necesaria

Medir durante una capacitación:

- Tiempo para crear una reservación sin ayuda.
- Tiempo para localizar una reservación.
- Tiempo para realizar check-in y check-out.
- Tiempo para abrir y cerrar caja.
- Número de errores o preguntas por tarea.
- Tareas que requieren una segunda explicación.

## 8. Requisitos técnicos para el hotel

### Requisitos mínimos comerciales prudentes

- Conexión estable a internet para la operación normal.
- Computadora, tableta o teléfono con navegador moderno.
- Cuenta individual por usuario.
- Personal responsable de mantener la información actualizada.
- Correo válido cuando se utilicen funciones que envían mensajes.
- HTTPS en producción.

### PWA

Puede instalarse como aplicación web en dispositivos compatibles, pero no debe venderse como aplicación nativa de App Store o Google Play.

### Operación offline

No debe prometerse captura offline sincronizable. `/api/sync` permanece deshabilitado con HTTP 423 y `sync_temporarily_disabled`.

### Datos pendientes

- Velocidad mínima de internet recomendada.
- Navegadores y versiones oficialmente soportadas.
- Resolución o características mínimas del dispositivo.
- Política frente a caídas del internet del hotel.
- Política frente a caída del servidor.

## 9. Seguridad y privacidad

### Controles confirmados en el sistema

- Contraseñas almacenadas mediante hash.
- Protección CSRF en operaciones de escritura.
- Autenticación de rutas privadas.
- Contexto y separación por hotel.
- Roles y permisos.
- Límite de intentos de acceso documentado.
- Cookies de producción configurables como `secure` y `httponly`.
- HTTPS previsto en el despliegue.
- Archivos documentales privados y descargas controladas.
- Webhooks de pago diseñados con verificación e idempotencia.

### Lo que sí puede decirse

> “Medisoft utiliza accesos individuales, permisos y separación de información por hotel.”

### Lo que no debe decirse sin auditoría externa

- “Es imposible de hackear.”
- “Tiene seguridad bancaria.”
- “Está certificado.”
- “Cumple toda norma de privacidad.”
- “Nunca perderá información.”
- “Los datos están cifrados de extremo a extremo.”

### Pendientes empresariales

- Aviso de privacidad.
- Términos de servicio.
- Acuerdo de tratamiento o confidencialidad.
- Política de contraseñas.
- Procedimiento de incidentes.
- Responsable de comunicar una brecha.
- Política de baja y eliminación de datos.
- Periodo de conservación.

## 10. Respaldos y recuperación

### Capacidad técnica confirmada

Existe un proceso preparado para:

- Respaldar la base de datos.
- Respaldar archivos cargados.
- Verificar que el archivo comprimido sea íntegro.
- Detectar respaldos incompletos.
- Aplicar retención.
- Copiar respaldos fuera del servidor mediante `rclone`.
- Restaurar en una base temporal para comprobar que el respaldo es utilizable.
- Restaurar la base real mediante confirmación explícita.

### Configuración documentada

- Base de datos: retención local de 30 días.
- Archivos: retención local de 7 días.
- Copia externa: disponible si se configura un destino.
- Verificación mensual de restauración: prevista.

### Límite importante

Tener el script no demuestra que esté programado y funcionando en cada servidor. Antes de prometer respaldos automáticos debe comprobarse:

- Cron activo.
- Último respaldo exitoso.
- Copia externa configurada.
- Última prueba de restauración.
- Persona que recibe alertas de fallo.

### Promesa provisional

No utilizar todavía “respaldo garantizado”. Utilizar:

> “El sistema cuenta con mecanismos de respaldo y restauración que se configuran y supervisan durante el despliegue.”

## 11. Instalación, capacitación y soporte

### Capacidad actual

- Instalación: Max.
- Capacitación: Max y responsable comercial.
- Soporte técnico: Max.
- Disponibilidad inicial: alta disposición de tiempo por estar en etapa de expansión.

### Fortalezas

- Contacto directo con el creador.
- Capacidad de adaptar la explicación al hotel.
- Cercanía con clientes iniciales.
- Aprendizaje rápido a partir de casos reales.

### Debilidades

- Max es punto único de falla.
- Desarrollo, instalación y soporte compiten por su tiempo.
- No existe un horario o SLA definido.
- No hay mesa de ayuda documentada.
- No hay clasificación de urgencias.
- No hay tiempo objetivo de respuesta.
- No existe todavía una base de conocimiento para clientes.
- No está definido qué cambios son soporte y cuáles son desarrollo personalizado.

### Riesgo de crecimiento

Vender varios hoteles puede aumentar simultáneamente:

- Configuraciones iniciales.
- Migraciones de datos.
- Capacitaciones.
- Dudas de recepción.
- Incidencias críticas fuera de horario.
- Solicitudes de personalización.

### Decisiones necesarias antes de fijar precio

- Canal oficial de soporte.
- Horario.
- Tiempo objetivo de primera respuesta.
- Qué incluye la mensualidad.
- Qué se cobra aparte.
- Procedimiento para urgencias.
- Quién sustituye a Max si no está disponible.

## 12. Capacidad de atención

### Confirmado

El equipo actual está compuesto, para estas funciones, por:

- Max: desarrollo, instalación y soporte.
- Responsable comercial: venta, seguimiento y capacitación compartida.

### Desconocido

- Cuántas instalaciones simultáneas pueden realizar.
- Cuántos hoteles puede soportar Max sin afectar el desarrollo.
- Promedio de solicitudes mensuales por hotel.
- Horas disponibles fuera de implementación.
- Capacidad ante una incidencia simultánea en varios hoteles.

### Recomendación de control

Durante los primeros pilotos registrar:

- Horas de instalación.
- Horas de carga inicial.
- Horas de capacitación.
- Consultas durante los días 1–7.
- Consultas durante los días 8–30.
- Incidencias técnicas.
- Solicitudes de cambios.

## 13. Costos internos

La decisión de invertir trabajo intensivo en los primeros clientes es válida como estrategia de entrada, pero no elimina los costos.

Deben cuantificarse posteriormente:

- Servidor.
- Dominio y certificados cuando apliquen.
- Almacenamiento y respaldos.
- Correo.
- Servicios externos.
- Tiempo de instalación.
- Tiempo de capacitación.
- Soporte.
- Traslados.
- Impuestos y comisiones.

Estos datos no se usarán todavía para poner precio, pero serán necesarios para evitar una mensualidad insostenible.

## 14. Matriz problema–capacidad–beneficio–evidencia

| Problema | Capacidad | Beneficio responsable | Evidencia actual | Estado |
|---|---|---|---|---|
| Reservaciones en libreta | Reservaciones + calendario | Centraliza y facilita la consulta | Código + San Nicolás/Los Cedros | Comprobable |
| No saber qué habitación está libre | Estados de Habitaciones | Vista operativa actual | Código | Comprobable |
| Volver a capturar huéspedes | Directorio e historial | Recupera información existente | Código | Comprobable |
| Cuentas manuales de turno | Caja y cortes | Organiza movimientos capturados | Código + QA | Comprobable |
| Todos comparten acceso | Usuarios + Roles | Control por persona y función | Código | Comprobable |
| Reparaciones olvidadas | Mantenimiento + Notificaciones | Registra y da seguimiento | Código | Comprobable |
| No conocer existencias | Inventario | Controla stock e historial | Código + QA | Comprobable |
| Solicitudes fiscales dispersas | Seguimiento de Facturación | Centraliza solicitudes y estados | Código + QA | Comprobable con límite |
| Documentos dispersos | Centro documental | Organiza archivos privados | Código + QA | Comprobable con límites |
| No conocer opinión postestancia | Encuestas | Recoge retroalimentación | Código | Requiere resolver flujo Google |
| Precios especiales recordados manualmente | Descuentos de huésped | Aplica reglas y desglose | Código | Comprobable |
| Cambiar tarifas una por una | Tarifas dinámicas | Programa reglas con vigencia | Código + pruebas | Comprobable |
| Trabajar sin internet | PWA/offline | — | Sync bloqueado | No prometible |
| Timbrar facturas | Facturación | — | No existe timbrado | No prometible |
| Sincronizar con OTA | — | — | No incluido en alcance | No prometible |

## 15. Fortalezas internas

1. Producto ya utilizado por hoteles reales.
2. Casos locales que comenzaron con libretas.
3. Núcleo operativo amplio.
4. Diseño multihotel y módulos activables.
5. Branding por hotel.
6. Acceso móvil mediante PWA.
7. Usuarios, roles y permisos.
8. Trazabilidad de caja y procesos.
9. Mecanismos de respaldo y restauración.
10. Pruebas funcionales y contratos de cambio en áreas críticas.
11. Contacto directo con el creador.
12. Posibilidad de piloto acompañado.
13. Capacidad de exportar información a Excel al terminar el piloto, según declaración del equipo.

## 16. Debilidades internas

1. Marca nueva y poco conocida.
2. Solo dos referencias locales declaradas.
3. Dependencia operativa de Max.
4. Precio y oferta aún no definidos.
5. Sin presentación, demo comercial ni materiales.
6. Sin proceso estándar de onboarding.
7. Sin SLA o política de soporte.
8. Sin métricas de impacto en clientes.
9. Sin testimonios estructurados todavía.
10. Algunas funciones siguen cambiando.
11. Sin documentación comercial de seguridad y privacidad.
12. Respaldo productivo sujeto a configuración y supervisión.
13. Sin soporte offline sincronizable.
14. Riesgo de personalizaciones no controladas.
15. Oferta modular todavía necesita alinearse completamente con el catálogo técnico.

## 17. Oportunidades que surgen del análisis interno

Estas son hipótesis que deberán probarse externamente:

1. Hoteles que todavía usan libreta pueden percibir una mejora visible rápidamente.
2. La cercanía geográfica y soporte directo pueden compensar parcialmente la falta de marca.
3. Dos hoteles locales pueden reducir el riesgo percibido.
4. Un piloto acompañado puede facilitar la adopción.
5. La oferta modular puede permitir entrada a hoteles con necesidades diferentes.
6. El caso “de libreta a control digital” puede ser más persuasivo que una lista de funciones.

## 18. Amenazas y riesgos

1. El dueño puede percibir que su libreta “funciona suficientemente”.
2. Resistencia del personal a capturar datos.
3. Internet inestable.
4. Competidores con marca, integraciones o soporte mayor.
5. Expectativas de personalización ilimitada.
6. Precio demasiado bajo que haga insostenible el soporte.
7. Precio alto sin evidencia de retorno.
8. Incidencia crítica que supere la capacidad de Max.
9. Pérdida de confianza si se promete una función todavía inmadura.
10. Confusión de Facturación con timbrado CFDI.
11. Riesgo de política de Google en Reputación.
12. Datos incompletos al migrar desde libretas.
13. Falta de uso durante el piloto.
14. Cancelación por no acompañar la adopción.

## 19. FODA resumido

| Fortalezas | Debilidades |
|---|---|
| Producto operativo y casos locales | Marca nueva |
| Núcleo integrado | Dependencia de Max |
| Personalización visual por hotel | Sin oferta/precio final |
| Módulos y permisos | Sin onboarding/SLA formal |
| Piloto acompañado | Sin métricas de resultados |

| Oportunidades | Amenazas |
|---|---|
| Hoteles que aún usan libreta | Resistencia al cambio |
| Digitalización gradual | Internet inestable |
| Referencias locales | Competidores consolidados |
| Soporte cercano | Personalización excesiva |
| Oferta modular | Sobrecarga de soporte |

## 20. Propuesta de valor inicial

### Versión principal

> Medisoft Hoteles reúne habitaciones, reservaciones, huéspedes y caja en un solo sistema para que recepción trabaje con mayor orden y el dueño pueda consultar con claridad lo que ocurre en su hotel.

### Para hoteles que usan libreta

> Pasa el control de habitaciones y reservaciones de la libreta a un sistema organizado, acompañado por personas que ya han realizado esa transición con hoteles de la zona.

### Para hoteles que usan Excel

> Centraliza la operación que hoy está repartida entre Excel, libretas y mensajes, relacionando huéspedes, reservaciones, habitaciones y caja.

### Lo que todavía no debe agregarse

- Porcentajes de ahorro.
- Reducción cuantificada de errores.
- Incremento garantizado de ingresos.
- “El mejor sistema”.
- “Sin fallas”.
- “Funciona sin internet”.
- “Facturación SAT incluida”.

## 21. Promesa de implementación provisional

> Configuramos el hotel, damos de alta su estructura inicial, capacitamos al equipo y acompañamos el arranque para que Medisoft se incorpore a la operación real.

Esta promesa necesita definir exactamente qué significa “estructura inicial”, cuántas horas de capacitación incluye y qué acompañamiento se ofrece.

## 22. Preguntas que deben responder el equipo y los clientes actuales

### Para Max

1. ¿Dónde están alojados actualmente San Nicolás y Los Cedros?
2. ¿Los respaldos automáticos están activos y se han probado restauraciones?
3. ¿Cuánto tarda normalmente en configurar un hotel?
4. ¿Qué datos migra sin costo?
5. ¿Qué navegadores y dispositivos soportará oficialmente?
6. ¿Qué ocurre si falla el servidor?
7. ¿Qué canal y horario de soporte puede comprometer?
8. ¿Qué tiempo de respuesta puede sostener?
9. ¿Cuántos hoteles podría implementar simultáneamente?
10. ¿Qué personalizaciones estarán excluidas?

### Para Hotel Los Cedros y Hotel San Nicolás

1. ¿Qué tareas hacían antes en libreta o Excel?
2. ¿Qué fue lo más difícil al cambiar?
3. ¿Cuánto tardó recepción en acostumbrarse?
4. ¿Qué pantalla usan más?
5. ¿Qué problema dejó de repetirse?
6. ¿Qué todavía les cuesta trabajo?
7. ¿Qué soporte han necesitado?
8. ¿Recomendarían Medisoft y por qué?

## 23. Criterio de cierre de la etapa interna

La etapa interna puede considerarse suficientemente completa para iniciar investigación externa porque:

- El producto vendible está delimitado.
- Las capacidades y límites están identificados.
- Existe una propuesta de valor inicial.
- Se identificaron fortalezas, debilidades y riesgos.
- Se conocen las incógnitas que no puede resolver el código.

Las preguntas de Max y clientes no bloquean el inicio de la investigación de mercado, pero deberán resolverse antes de cerrar oferta, precio, contrato y promesa de soporte.
