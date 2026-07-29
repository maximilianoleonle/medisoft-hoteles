# Auditoría comercial de módulos — Medisoft Hoteles

Fecha de corte: 25 de julio de 2026  
Alcance: paquete base vigente y opcionales seleccionados para la oferta inicial en Santa Catarina Juquila.  
Naturaleza: auditoría comercial basada en la implementación actual del repositorio. No sustituye QA técnico, pruebas operativas con datos reales ni autorización final de precios.

## 1. Cómo leer este documento

Cada módulo se analiza desde seis preguntas:

1. ¿Qué hace realmente?
2. ¿Qué problema operativo resuelve?
3. ¿A quién le aporta valor?
4. ¿Cómo se demuestra sin abrumar?
5. ¿Qué se puede prometer con seguridad?
6. ¿Qué no debe prometerse o qué falta validar?

Estados utilizados:

- **Confirmado en código:** existe un flujo visible y rastreable.
- **Promesa segura:** puede expresarse comercialmente sin exagerar el alcance.
- **Validación pendiente:** requiere prueba funcional, evidencia del cliente o definición de producto.

## 2. Estructura comercial confirmada

### Paquete base

1. Dashboard
2. Habitaciones
3. Reservaciones
4. Huéspedes
5. Caja
6. Usuarios
7. Roles y permisos
8. Mantenimiento
9. Notificaciones
10. PWA / aplicación instalable

Funciones internas incluidas en la operación, pero que no se venden por separado:

- Configuración.
- Anticipos y abonos.
- Centro de reportes como contenedor.
- Infraestructura interna de tareas.

### Opcionales seleccionados por la dueña

1. Inventario.
2. Facturación.
3. Centro documental.
4. Reputación y encuestas.
5. Descuentos de huésped.
6. Tarifas dinámicas.

La clave técnica `compras` permanece en el sistema, pero por decisión de la dueña no forma parte de esta oferta: no se promociona, demuestra ni cotiza. También quedan fuera del discurso comercial proveedores, recepción de mercancía y cuentas por pagar.

### Decisión sobre Lealtad

**Lealtad / Huésped frecuente queda bloqueado temporalmente y fuera de la oferta.** No se integra ni se vende como parte de Descuentos de huésped. Descuentos conserva únicamente su alcance propio.

## 3. Evaluación del paquete base

### 3.1 Dashboard

**Qué hace**

- Resume la operación del día.
- Muestra estado y ocupación de habitaciones.
- Presenta llegadas, salidas y huéspedes actuales.
- Resume ingresos, gastos, reversos y balance operativo.
- Integra pendientes y notificaciones.
- Presenta gráficas de ocupación.

**Problema que resuelve**

Evita que el dueño, administrador o recepción tengan que revisar libretas, reservaciones y caja por separado para saber cómo está el hotel.

**Usuario con mayor valor**

- Dueño.
- Administrador o gerente.
- Recepción al iniciar turno.

**Demostración recomendada**

Abrir el dashboard con datos preparados y responder en menos de dos minutos:

1. ¿Cuántas habitaciones están ocupadas y disponibles?
2. ¿Quién llega o sale hoy?
3. ¿Cómo va el dinero del turno?
4. ¿Qué requiere atención?

**Promesa segura**

> “Al entrar puedes ver en un solo lugar cómo está la operación del hotel hoy: habitaciones, movimientos del día y pendientes.”

**No prometer todavía**

- Inteligencia predictiva.
- Rentabilidad completa.
- Estados financieros contables.
- Exactitud de indicadores que dependan de que el personal no capture la operación.

**Valor comercial:** muy alto.  
**Papel en la venta:** apertura visual de la demostración, no explicación exhaustiva.

---

### 3.2 Habitaciones

**Qué hace**

- Registra y edita habitaciones, tipos, capacidad, tarifa, características e imágenes.
- Muestra disponibilidad y estados operativos.
- Distingue libres, ocupadas, por llegar, en limpieza y en mantenimiento.
- Muestra llegadas, salidas, check-ins pendientes y situaciones vencidas.
- Permite cambios de estado.
- Permite altas individuales y por lote.
- Conserva historial.
- Integra mantenimiento inmediato y programado.

**Problema que resuelve**

Sustituye el pizarrón o libreta usados para saber qué habitación está libre, ocupada, pendiente de limpieza o fuera de servicio.

**Usuario con mayor valor**

- Recepción.
- Gerencia.
- Personal que coordina limpieza y mantenimiento.

**Demostración recomendada**

Mostrar una pantalla con varios estados y simular el recorrido:

1. Identificar una habitación libre.
2. Ver una llegada pendiente.
3. Cambiar una habitación a limpieza.
4. Abrir su ficha e historial.

**Promesa segura**

> “Recepción puede consultar el estado actual de cada habitación y distinguir rápidamente cuáles están libres, ocupadas, por llegar, en limpieza o mantenimiento.”

**No prometer todavía**

- Sincronización automática con Booking, Expedia o Airbnb.
- Cerraduras electrónicas.
- Que el estado sea correcto si nadie registra los cambios.

**Valor comercial:** crítico.  
**Papel en la venta:** uno de los tres flujos centrales.

---

### 3.3 Reservaciones

**Qué hace**

- Crea y consulta reservaciones de una o varias habitaciones.
- Muestra listado y calendario de ocupación.
- Registra fechas, huéspedes, habitaciones, notas y datos económicos.
- Genera cotizaciones y documentos operativos.
- Gestiona check-in, check-out, cancelación y no-show.
- Permite ajustes de estancia y habitaciones.
- Registra anticipos y abonos dentro del flujo operativo.
- Presenta saldos pendientes.
- Conserva notas e historial relacionado.

**Problema que resuelve**
Reduce la dispersión de reservaciones entre libreta, mensajes y memoria del personal; facilita conocer disponibilidad, estancia, saldo y estado de cada huésped.

**Usuario con mayor valor**

- Recepción.
- Administrador.
- Dueño que supervisa ocupación.

**Demostración recomendada**

Utilizar una reservación ficticia y mostrar únicamente:

1. Selección de fechas y habitación disponible.
2. Asociación del huésped.
3. Cotización o total.
4. Confirmación.
5. Aparición en calendario.
6. Vista del saldo y siguiente acción.

No ejecutar un check-in o check-out real sobre datos productivos durante una visita.

**Promesa segura**

> “Las reservaciones quedan organizadas por fecha, huésped y habitación; recepción puede consultar su estado, saldo y próximas acciones sin buscar en diferentes libretas.”

**No prometer todavía**

- Channel manager.
- Reservas automáticas desde OTA.
- Cero sobreventas en canales externos no conectados.
- Operación completamente automática.
- Cambios profundos de check-in/check-out todavía no cubiertos por QA.

**Valor comercial:** crítico.  
**Papel en la venta:** demostración principal después del diagnóstico.

---

### 3.4 Huéspedes

**Qué hace**

- Mantiene un directorio de huéspedes.
- Permite buscar por nombre, teléfono, correo y otros datos disponibles.
- Conserva datos de contacto e historial de reservaciones.
- Facilita iniciar una nueva reservación desde el perfil.
- Admite documentos ligados al huésped cuando Centro documental está contratado.
- Integra descuentos del huésped cuando el opcional correspondiente está activo.

**Problema que resuelve**

Evita volver a capturar o buscar manualmente la información de clientes recurrentes y conserva su relación histórica con el hotel.

**Usuario con mayor valor**

- Recepción.
- Administración.
- Personal que atiende huéspedes frecuentes.

**Demostración recomendada**

Buscar a un huésped ficticio, abrir su perfil, mostrar historial y comenzar una nueva reservación.

**Promesa segura**

> “El hotel conserva un expediente operativo del huésped y puede encontrarlo rápidamente cuando regresa.”

**No prometer todavía**

- CRM de campañas.
- Automatización de marketing.
- Segmentación avanzada.
- Expediente documental si Centro documental no está contratado.

**Valor comercial:** alto.  
**Papel en la venta:** demuestra continuidad y servicio personalizado.

---

### 3.5 Caja

**Qué hace**

- Abre caja con monto inicial.
- Registra ingresos y gastos.
- Clasifica movimientos.
- Separa métodos de pago.
- Presenta balance del corte y movimientos del turno.
- Realiza corte y conserva historial.
- Permite consultar cortes anteriores.
- Incluye arqueo por métodos de pago.
- Genera documento de corte.

**Problema que resuelve**

Da trazabilidad al dinero recibido y gastado durante el turno, evitando depender únicamente de cuentas manuales al final del día.

**Usuario con mayor valor**

- Recepción/cajero.
- Administrador.
- Dueño.

**Demostración recomendada**

Con una caja de demostración:

1. Mostrar monto inicial.
2. Registrar un ingreso pequeño ficticio.
3. Mostrar la separación por método.
4. Explicar el corte sin cerrarlo.

**Promesa segura**

> “Cada turno puede registrar sus ingresos y gastos, separarlos por método de pago y cerrar con un corte consultable.”

**No prometer todavía**

- Contabilidad formal.
- Conciliación bancaria automática.
- Que sustituye al contador.
- Que evita fraude por sí solo.
- Integración directa con terminal bancaria.

**Valor comercial:** crítico.  
**Papel en la venta:** argumento fuerte para dueño y administrador.

---

### 3.6 Usuarios

**Qué hace**

- Crea cuentas para el personal.
- Edita datos de acceso.
- Activa y desactiva usuarios.
- Asocia usuarios con roles.

**Problema que resuelve**

Evita compartir una sola contraseña entre todo el personal y permite retirar acceso cuando alguien deja de trabajar en el hotel.

**Usuario con mayor valor**

- Dueño.
- Administrador.

**Demostración recomendada**

Mostrar la lista, crear un usuario ficticio o abrir el formulario y explicar activación/desactivación.

**Promesa segura**

> “Cada integrante del equipo puede tener su propio acceso, y el administrador puede habilitarlo o bloquearlo.”

**No prometer todavía**

- Biometría.
- Inicio de sesión con proveedores externos.
- Control laboral o asistencia.

**Valor comercial:** necesario, aunque rara vez cierra una venta por sí solo.

---

### 3.7 Roles y permisos

**Qué hace**

- Crea roles personalizados.
- Asigna permisos por función.
- Permite editar y eliminar roles bajo condiciones.
- Impide eliminar un rol que todavía tiene usuarios asignados.

**Problema que resuelve**

Permite que recepción, administración y dueño no tengan necesariamente el mismo nivel de acceso.

**Usuario con mayor valor**

- Dueño.
- Gerente.
- Administrador del sistema.

**Demostración recomendada**

Comparar un rol de recepción con uno de administración y mostrar dos o tres permisos relevantes, no todo el catálogo.

**Promesa segura**

> “Puedes decidir qué puede consultar o realizar cada tipo de usuario.”

**No prometer todavía**

- Que cualquier combinación de permisos es adecuada sin configuración.
- Auditoría legal completa de acciones.

**Valor comercial:** alto para confianza y control; secundario para hoteles muy pequeños.

---

### 3.8 Mantenimiento

**Qué hace**

- Registra incidencias de mantenimiento vinculadas con habitaciones.
- Permite iniciar, dar seguimiento y cerrar trabajos.
- Admite evidencia fotográfica.
- Registra gastos asociados.
- Maneja activos o equipos con servicio programado.
- Genera mantenimientos preventivos conforme a programación.
- Conserva reportes de mantenimiento dentro del centro interno.

**Problema que resuelve**

Evita que fallas, reparaciones y servicios preventivos dependan de recados verbales o se olviden; vincula la situación con la habitación o activo correspondiente.

**Usuario con mayor valor**

- Recepción que reporta.
- Personal de mantenimiento.
- Administrador.
- Dueño que revisa costos e incidencias.

**Demostración recomendada**

Crear una incidencia ficticia en una habitación, mostrar evidencia y explicar el cierre. Después enseñar un activo con próximo servicio.

**Promesa segura**

> “El hotel puede registrar una falla, darle seguimiento, documentar su atención y programar servicios preventivos de sus equipos.”

**No prometer todavía**

- Diagnóstico automático de fallas.
- Órdenes de trabajo enviadas a proveedores externos.
- Control de refacciones si Inventario no está configurado para ello.

**Valor comercial:** alto para hoteles con varias habitaciones o problemas recurrentes.

---

### 3.9 Notificaciones

**Qué hace**

- Centraliza avisos internos.
- Distingue nuevas, atendidas y archivadas.
- Permite abrir, resolver o descartar notificaciones.
- Muestra avisos en dashboard, encabezado y menú.
- Puede enviar notificaciones push al dispositivo cuando el hotel las activa deliberadamente.
- Recibe eventos de distintos módulos, según configuración.

**Problema que resuelve**

Reduce el riesgo de que un pendiente operativo quede oculto entre pantallas o dependa de que alguien lo recuerde.

**Usuario con mayor valor**

- Recepción.
- Gerencia.
- Dueño.

**Demostración recomendada**

Mostrar una notificación existente, abrirla, ir al registro relacionado y marcarla como atendida.

**Promesa segura**

> “Los pendientes importantes pueden concentrarse en una bandeja y marcarse como atendidos para que no dependan solo de avisos verbales.”

**No prometer todavía**

- Que todos los eventos posibles generan una alerta.
- Push automático sin consentimiento/configuración.
- WhatsApp o SMS incluidos.

**Valor comercial:** medio-alto como refuerzo del control operativo.

---

### 3.10 PWA / aplicación instalable

**Qué hace confirmado**

- Permite instalar el sistema como aplicación web desde navegadores compatibles.
- Utiliza identidad visual del hotel en el manifiesto.
- Ofrece experiencia móvil y accesos rápidos.
- Incluye interfaz para estado de conexión.

**Problema que resuelve**

Facilita que el personal abra Medisoft desde el teléfono o equipo como una aplicación, sin buscar cada vez la dirección en el navegador.

**Usuario con mayor valor**

- Recepción.
- Dueño que consulta desde móvil.
- Personal autorizado que necesita acceso rápido.

**Demostración recomendada**

Abrir la versión móvil y, si el navegador lo permite, mostrar “Instalar aplicación”; después enseñar el icono y la navegación.

**Promesa segura**

> “Medisoft puede instalarse en dispositivos compatibles como una aplicación web con la identidad del hotel.”

**Advertencia crítica**

`/api/sync` debe permanecer bloqueado con HTTP 423 y `sync_temporarily_disabled`. Aunque existen interfaces y código de apoyo offline, **no debe venderse actualmente la sincronización offline ni prometerse que se pueden capturar operaciones sin internet y enviarlas después**.

**No prometer**

- Publicación en App Store o Google Play.
- Compatibilidad idéntica con todos los navegadores.
- Operación offline sincronizable.
- Funcionamiento sin internet para flujos críticos.

**Valor comercial:** medio como facilidad de acceso; no presentarlo como “app nativa”.

## 4. Evaluación de opcionales

### 4.1 Inventario

**Qué hace**

- Registra productos, categorías, unidades, costos y existencias.
- Define stock mínimo.
- Registra entradas, salidas y ajustes.
- Conserva historial con stock anterior y nuevo.
- Muestra alertas de stock bajo.
- Calcula valor visible del inventario.
- Configura consumos por tipo de habitación.
- Puede descontar consumibles configurados durante el flujo operativo correspondiente.
- Ofrece reportes y exportaciones cuando el bloque de exportaciones aplicable esté disponible.

**Problema que resuelve**

Permite saber qué insumos existen, qué se consumió, qué debe reponerse y por qué cambió una existencia.

**Cliente con mayor valor**

- Hoteles que almacenan amenidades, blancos, artículos de limpieza, bebidas o consumibles.
- Hoteles donde varias personas toman productos.
- Hoteles que descubren faltantes demasiado tarde.

**Señales para ofrecerlo**

- “No sabemos cuánto queda.”
- “Compramos cuando alguien avisa.”
- “Se pierde mucho jabón/papel/producto.”
- “No tenemos registro de quién sacó qué.”

**Demostración recomendada**

1. Abrir tablero de inventario.
2. Mostrar un producto con mínimo.
3. Registrar una entrada o salida ficticia.
4. Ver el stock antes/después y el historial.
5. Enseñar una alerta de reposición.

**Promesa segura**

> “Puedes controlar existencias, registrar entradas y salidas, recibir alertas por mínimos y consultar el historial de movimientos.”

**No prometer todavía**

- Contabilidad de costos completa.
- Lectura de códigos de barras.
- Predicción automática de compras.
- Descuento automático perfecto sin configurar consumos.

**Estado comercial:** confirmado y disponible. Se vende únicamente el alcance de Inventario; no incluye Compras, proveedores, recepción de mercancía ni cuentas por pagar.

---

### 4.2 Facturación

**Qué hace realmente**

- Administra solicitudes de factura generadas desde la operación.
- Reúne reservación, huésped, pagos y datos fiscales.
- Permite capturar RFC, razón social, régimen y uso de CFDI.
- Maneja estados como pendiente, en proceso, facturada y cancelada.
- Permite registrar el folio o número de una factura emitida externamente.
- Notifica cambios relevantes.

**Problema que resuelve**

Evita que las solicitudes de factura se pierdan entre papeles o mensajes y ayuda a recepción/administración a saber cuáles faltan, están en proceso o ya fueron atendidas.

**Cliente con mayor valor**

- Hoteles con huéspedes de empresa.
- Hoteles que reciben solicitudes frecuentes.
- Hoteles donde recepción y contador se coordinan manualmente.

**Demostración recomendada**

Abrir una solicitud ficticia, revisar sus datos fiscales, marcarla en proceso y enseñar dónde se registra el folio al completarla.

**Promesa segura**

> “Medisoft organiza las solicitudes de factura y su seguimiento para que recepción y administración sepan cuáles faltan y cuáles ya fueron emitidas.”

**Límite comercial obligatorio**

Medisoft **no timbra CFDI en este módulo**, no se conecta al SAT ni sustituye al PAC o sistema de facturación del contador. La propia interfaz indica que la factura se emite con el sistema externo y después se marca como completada.

No utilizar frases como:

- “Facturamos ante el SAT.”
- “El sistema genera CFDI.”
- “Ya no necesitas sistema de facturación.”

**Estado comercial:** funcional como gestor de solicitudes; vender con nombre y explicación que eviten confundirlo con facturación electrónica.

---

### 4.3 Centro documental

**Qué hace**

- Sube PDF, JPG, PNG o WEBP de hasta 10 MB.
- Guarda título, descripción, tipo y etiquetas.
- Permite visualizar o descargar archivos autorizados.
- Archiva, restaura y realiza baja lógica.
- Separa permisos de consulta y control total.
- Registra acciones de descarga y bloqueos.
- Liga documentos con proveedor, compra, cuenta por pagar, huésped o reservación.
- Mantiene los archivos fuera del acceso público directo.

**Problema que resuelve**

Evita que contratos, comprobantes, identificaciones y archivos queden dispersos entre celulares, carpetas y conversaciones.

**Cliente con mayor valor**

- Hoteles que conservan documentos de huéspedes o proveedores.
- Administración.
- Hoteles con múltiples usuarios.

**Demostración recomendada**

Subir un PDF ficticio desde la ficha de un proveedor o huésped, etiquetarlo, previsualizarlo y archivarlo.

**Promesa segura**

> “Los documentos pueden guardarse de forma privada, organizarse y vincularse con el registro al que pertenecen.”

**No prometer todavía**

- Firma electrónica.
- Validez legal automática.
- Almacenamiento ilimitado.
- OCR o extracción automática de datos.
- Cifrado extremo a extremo.

**Estado comercial:** funcional. Antes de cerrar ventas debe definirse política de almacenamiento, respaldo, conservación y límite contratado.

---

### 4.4 Reputación y encuestas

**Qué hace**

- Trabaja con reservaciones que ya realizaron checkout.
- Genera enlaces individuales de encuesta.
- Permite copiar el enlace o enviarlo por correo cuando el envío está configurado.
- Registra calificación y respuesta.
- Muestra promedio, encuestas respondidas, tasa de respuesta y NPS calculado.
- Invita a calificaciones altas a continuar hacia el enlace de reseña de Google configurado.
- Genera una notificación interna cuando una evaluación baja cruza el umbral establecido.

**Problema que resuelve**

Ayuda a pedir retroalimentación después de la estancia, detectar insatisfacción y facilitar que huéspedes satisfechos lleguen al perfil de Google del hotel.

**Cliente con mayor valor**

- Hoteles que dependen de reputación local y Google.
- Dueños preocupados por reseñas negativas.
- Hoteles sin proceso de seguimiento postestancia.

**Demostración recomendada**

1. Mostrar un checkout reciente.
2. Generar una encuesta.
3. Abrir el enlace público en otro dispositivo.
4. Responder con una calificación ficticia.
5. Mostrar el resultado en el tablero.

**Promesa segura**

> “Después del checkout puedes enviar una encuesta, concentrar las respuestas y facilitar que los huéspedes satisfechos lleguen a tu perfil de Google.”

**Límites obligatorios**

- Medisoft no publica reseñas en nombre del huésped.
- No garantiza mejores calificaciones.
- No elimina ni controla reseñas de Google.
- El correo depende de configuración de envío.
- Las funciones de respuesta o análisis con IA que aparecen condicionalmente requieren bloques adicionales y no forman parte de este opcional.

**Hallazgo externo crítico**

La política vigente de contenido de Google Maps prohíbe a los comercios desalentar reseñas negativas o solicitar selectivamente reseñas positivas. La implementación actual invita a Google únicamente a quienes califican con 4–5 estrellas; por tanto, ese comportamiento presenta un riesgo claro de incumplimiento y no debe demostrarse ni venderse así.

Antes de ofrecer el módulo debe modificarse el flujo para que la posibilidad de dejar una reseña en Google no dependa de que la calificación interna sea positiva. La encuesta privada y las alertas internas por insatisfacción sí pueden conservarse como propuesta de valor.

Fuentes oficiales consultadas:

- Google Maps User Generated Content Policy, “Prohibited and restricted content”.
- Google Business Profile Help, “Tips to get more reviews”.

**Estado comercial:** funcional como encuesta interna, pero condicionado para venta por el flujo selectivo hacia Google. Validar también correo, enlace público, expiración y tratamiento de datos antes del piloto.

---

### 4.5 Descuentos de huésped

**Qué hace**

- Permite guardar una regla de descuento en el perfil del huésped.
- Calcula descuentos durante la cotización/reservación.
- Admite descuentos por porcentaje o monto según configuración.
- Permite descuentos ligados a reglas de tarifa.
- Permite ajuste manual del descuento por un operador autorizado.
- Conserva desglose del descuento aplicado.
- Si el hotel no contrata el bloque, los puntos de captura se cierran sin bloquear Reservaciones.

**Problema que resuelve**

Formaliza beneficios para huéspedes frecuentes o situaciones autorizadas y evita depender de que recepción recuerde o calcule cada descuento manualmente.

**Cliente con mayor valor**

- Hoteles con clientes recurrentes.
- Hoteles que negocian tarifas con huéspedes conocidos.
- Hoteles que quieren controlar quién descuenta y cuánto.

**Demostración recomendada**

Configurar un huésped ficticio con descuento, cotizar una reservación y mostrar el precio antes, descuento y total final.

**Promesa segura**

> “El hotel puede definir descuentos autorizados y aplicarlos con un desglose visible al cotizar la reservación.”

**Límite**

Lealtad / Huésped frecuente está bloqueado temporalmente. No usar “programa de lealtad” como sinónimo de Descuentos de huésped.

**No prometer todavía**

- Puntos.
- Niveles de membresía.
- Cupones.
- Campañas.
- Rentabilidad garantizada.

**Estado comercial:** funcional y con gates específicos; requiere política comercial del hotel para evitar descuentos indiscriminados.

---

### 4.6 Tarifas dinámicas

**Qué hace**

- Crea reglas de incremento por porcentaje o monto fijo.
- Define nombre, motivo, prioridad, alcance y vigencia.
- Aplica reglas a habitaciones o tipos de habitación.
- Muestra precios actuales y previsualiza el impacto.
- Permite activar, desactivar, editar y eliminar reglas.
- Calcula el precio aplicable durante la reservación.
- Puede revisar impacto sobre reservaciones, sujeto al flujo autorizado.
- Comparte motor con reglas de descuento cuando el bloque correspondiente está activo.

**Problema que resuelve**

Evita cambiar manualmente el precio de cada habitación en temporada alta, fiestas, eventos o periodos especiales.

**Cliente con mayor valor**

- Hoteles con temporadas marcadas.
- Hoteles que modifican precio por fines de semana, fiestas o eventos.
- Administradores que hoy actualizan precios habitación por habitación.

**Demostración recomendada**

Crear una regla ficticia de temporada:

1. Elegir porcentaje o monto.
2. Seleccionar habitaciones/tipos.
3. Definir fechas.
4. Previsualizar precios.
5. No guardar o aplicar sobre reservaciones reales durante la visita.

**Promesa segura**

> “Puedes programar ajustes de precio por periodo y alcance, previsualizar su efecto y aplicarlos automáticamente al cotizar nuevas reservaciones.”

**Límite de lenguaje**

No es “revenue management automático” ni inteligencia que decide sola el precio. Es un motor de reglas configuradas por el hotel.

**No prometer todavía**

- Predicción de demanda.
- Precios automáticos según ocupación.
- Comparación con competidores.
- Sincronización de tarifas con OTA.
- Incremento de ingresos garantizado.

**Estado comercial:** funcional y con gate de servidor; probar cuidadosamente reglas superpuestas, prioridades, fechas y efecto en reservaciones antes del piloto.

## 5. Mapa problema → módulo

| Frase del prospecto | Módulo a mostrar |
|---|---|
| “Todo lo apuntamos en una libreta” | Habitaciones + Reservaciones |
| “No sé qué cuartos están libres” | Habitaciones |
| “Se nos cruzan las reservaciones” | Reservaciones + Calendario |
| “No encuentro los datos cuando vuelve un huésped” | Huéspedes |
| “Al final del turno no cuadramos” | Caja |
| “Todos usan la misma cuenta” | Usuarios + Roles y permisos |
| “Las reparaciones se olvidan” | Mantenimiento + Notificaciones |
| “Quiero consultarlo desde mi teléfono” | PWA |
| “No sé cuánto producto queda” | Inventario |
| “Las solicitudes de factura se pierden” | Facturación |
| “Los comprobantes están en varios celulares” | Centro documental |
| “Los huéspedes se van y no sabemos qué opinan” | Reputación y encuestas |
| “A los clientes frecuentes les damos precio especial” | Descuentos de huésped |
| “En fiestas subimos precios manualmente” | Tarifas dinámicas |

## 6. Secuencia recomendada para una demostración general

La demostración no debe recorrer los 17 módulos. Para un hotel que todavía trabaja con libreta:

1. Dashboard: panorama del día.
2. Habitaciones: disponibilidad.
3. Reservaciones: registrar una estancia.
4. Huéspedes: recuperar información.
5. Caja: control del turno.
6. Elegir un solo opcional según el dolor descubierto.

Duración objetivo inicial: 6 minutos; puede ampliarse hasta 10 si el prospecto demuestra interés. Los demás módulos se muestran únicamente si confirma que ese problema existe. El recorrido actualizado está en `estrategia_comercial/22_manual_demostracion_express.md`.

## 7. Promesa central provisional

> Medisoft Hoteles reúne habitaciones, reservaciones, huéspedes y caja en un solo sistema para que recepción trabaje con mayor orden y el dueño pueda consultar con claridad lo que ocurre en su hotel.

Esta promesa es deliberadamente prudente. No atribuye ahorros o porcentajes todavía no medidos.

## 8. Evidencia comercial disponible

### Hotel San Nicolás

- Aproximadamente ocho meses de uso.
- Antes trabajaba su control en libreta y no contaba con computadora para la gestión.
- Caso útil para demostrar transición desde una operación completamente manual.
- Nombre autorizado para referencia comercial.

### Hotel Los Cedros

- Aproximadamente catorce a quince meses de uso.
- Utilizaba computadora/Excel para parte de las finanzas, pero recepción dependía de libretas.
- Caso útil para demostrar centralización de una operación parcialmente digitalizada.
- Nombre autorizado.
- Disponible para testimonio y llamada de referencia.

Por ahora solo es seguro afirmar la adopción y el cambio de libreta a control digital. Cualquier ahorro de tiempo, reducción de errores o mejora porcentual debe medirse o ser declarado por el hotel en su testimonio.

## 9. Pendientes antes de convertir la auditoría en oferta

**Estado al 2026-07-26.** Los nueve se atendieron; los que quedan abiertos son decisiones humanas, no trabajo técnico.

| # | Pendiente | Estado | Dónde quedó |
|---|---|---|---|
| 1 | Inventario no debe activar ni cobrar `compras` | ✅ Resuelto | Verificado: contratar un bloque nunca propaga otro. Blindado por comportamiento en `ClasificacionComercialModulosTest` |
| 2 | Renombrar Facturación para no leerse como CFDI | ✅ Resuelto | Ahora **"Control de facturación"**, con descripción que niega el timbrado (migración `20260726_001`) |
| 3 | Guion de demostración por opcional | ✅ Resuelto | `estrategia_comercial/20_guiones_demostracion.md` |
| 4 | Corregir el flujo selectivo hacia Google | ✅ Resuelto | Se eliminó el filtro por calificación en servicio y vista pública; suite nueva `ReputacionTest` (28 asserts) |
| 5 | Límites y retención del Centro documental | ✅ Resuelto | Cuota de **2 GB por hotel** implementada; política en `docs/politica-centro-documental.md` |
| 6 | Confirmar qué eventos generan notificaciones | ✅ Resuelto | `docs/notificaciones-que-genera-el-sistema.md`, verificado contra producción |
| 7 | No promocionar offline con `/api/sync` bloqueado | ✅ Resuelto y ampliado | Se apagó además la **captura offline de escrituras**, que aceptaba cobros que nunca llegaban al servidor |
| 8 | Entrevistar a Los Cedros y San Nicolás | ⏳ Instrumento listo | `estrategia_comercial/19_entrevista_clientes_actuales.md`. Ejecuta el owner |
| 9 | Cuenta de demostración limpia | ✅ Resuelto | `tools/saas/sembrar_hotel_demo.php` |

### Hallazgos que surgieron al resolverlos

**a) Producción corre un catálogo comercial anterior.** La columna `tipo_comercial` no existe en la base de producción: las migraciones de clasificación (`20260724_001`, `20260725_*`) **no están desplegadas**. Allá los 50 módulos están activos y Los Cedros tiene 45 contratados. Toda la estructura comercial de esta auditoría vive únicamente en desarrollo. **Desplegarla es requisito previo a cobrar conforme a esta oferta**, y para Los Cedros implica revisar qué conserva.

**b) La captura offline aceptaba dinero que se perdía.** No era solo un tema de promesa: los interceptores encolaban movimientos de caja, check-in/out y altas de huésped, y `/api/sync` los rechazaba con HTTP 423. La app decía "se enviará al recuperar la conexión" y esas operaciones nunca llegaban. Apagado por decisión del owner el 2026-07-26.

**c) El correo no funciona en absoluto.** No hay servidor de correo instalado en la imagen. Afecta directamente a **Reputación**: la encuesta se comparte copiando el enlace, no por envío automático. Ver el documento de notificaciones.

**d) Dos bloques fuera de la oferta siguen abiertos a la venta** en el catálogo: `compras` ($199) y `lealtad` ($179). No se bloquearon globalmente porque eso apagaría la función a los hoteles que ya la tienen contratada. La separación es disciplina comercial: no se demuestran ni se cotizan.

**e) El gerente no ve en su campanita los avisos de reservaciones ni habitaciones.** Si el argumento de venta es control gerencial, conviene corregirlo antes de prometerlo.

## 10. Dictamen comercial inicial

El producto tiene un núcleo suficientemente coherente para venderse como sistema operativo hotelero: Habitaciones, Reservaciones, Huéspedes y Caja forman una historia comercial clara. Dashboard, Usuarios, Roles, Mantenimiento, Notificaciones y PWA elevan el control y la facilidad de uso, pero deben presentarse como refuerzos del núcleo.

Los opcionales con propuesta más fácil de entender para hoteles pequeños son:

1. Inventario.
2. Tarifas dinámicas, si existe variación de temporada.
3. Reputación y encuestas, si dependen de Google.
4. Descuentos, si existe recurrencia real.

Inventario se ofrecerá como solución independiente. Compras, proveedores, recepción de mercancía y cuentas por pagar permanecen fuera de la oferta. Facturación tiene utilidad, pero exige una explicación muy precisa porque gestiona solicitudes y no emite CFDI. Centro documental aporta orden, aunque su venta requiere definir límites y políticas de almacenamiento.

La auditoría confirma capacidades, no todavía su prioridad de mercado ni su precio. Esas dos decisiones deben salir de la investigación de hoteles de la zona y del análisis competitivo.
