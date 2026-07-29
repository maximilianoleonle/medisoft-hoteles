# Etapa 7 — Posicionamiento, oferta, piloto y precio

## Dictamen corregido

La primera versión trató el piloto principalmente como filtro de prospectos y propuso un depósito. Ese enfoque no corresponde a la situación de Medisoft: la marca está entrando al mercado y necesita reducir el riesgo percibido, demostrar el producto y ganar confianza. **Se retira completamente el depósito de $500.**

El piloto será una herramienta de persuasión basada en experiencia real, no una presión para que el hotel pague antes de conocer el sistema.

## Posicionamiento recomendado

> Medisoft ayuda a hoteles independientes a pasar de libretas, archivos y mensajes separados a una operación organizada de habitaciones, reservaciones, huéspedes y caja, con configuración, capacitación y acompañamiento directo.

No posicionar como “el sistema más completo”. Posicionar como **el cambio acompañado y entendible para la operación real del hotel**.

## Promesa central

> Centralizamos el control que el hotel capture y acompañamos al personal para incorporarlo a su trabajo diario.

El verbo “capturar” importa: ningún sistema ordena datos que el equipo no registra.

## Oferta

### Base

Dashboard, Habitaciones, Reservaciones, Huéspedes, Caja, Usuarios, Roles y permisos, Mantenimiento, Notificaciones y PWA instalable.

### Opcionales vigentes

- Inventario.
- Seguimiento de solicitudes de facturación.
- Centro documental.
- Reputación y encuestas, con flujo de Google sujeto a corrección.
- Descuentos de huésped.
- Tarifas dinámicas por reglas.

### Implementación incluida — definición provisional

- alta y configuración del hotel;
- hasta 40 habitaciones y sus tipos/tarifas iniciales;
- hasta 5 usuarios iniciales;
- configuración de roles estándar;
- una capacitación inicial de hasta 2 horas;
- una sesión de refuerzo de hasta 60 minutos;
- acompañamiento de arranque por WhatsApp;
- exportación a Excel al finalizar el piloto.

Carga histórica, integraciones, hardware, captura masiva y desarrollo personalizado se cotizan aparte.

## Centro documental y sus 2 GB

El precio del Centro documental incluye actualmente una cuota técnica de 2 GB por hotel.

Comportamiento comprobado:

- el sistema avisa cuando se utiliza 80%;
- al alcanzar la cuota, bloquea cualquier archivo nuevo que no quepa;
- muestra espacio usado y disponible;
- cada archivo admite normalmente hasta 10 MB;
- archivos activos y archivados cuentan contra la cuota;
- al dar de baja un documento, deja de contar contra la cuota;
- pero el archivo físico continúa en el servidor y la purga definitiva todavía está pendiente.

Por tanto, la oferta correcta no puede decir solamente “2 GB y después se elimina”. Debe decir:

> Incluye hasta 2 GB de almacenamiento documental. Al acercarse al límite revisaremos con el hotel qué documentos deben conservarse, darse de baja o trasladarse. Cualquier ampliación se cotizará y autorizará antes de aplicarse.

Antes de vender ampliaciones deben definirse:

1. precio por bloque adicional;
2. tamaño de bloque —por ejemplo, 1 GB—;
3. conservación de documentos dados de baja;
4. plazo de recuperación;
5. purga física segura;
6. respaldo de archivos;
7. qué ocurre al cancelar;
8. quién autoriza una eliminación irreversible.

Mientras esto no exista, el Centro documental puede mostrarse, pero no debe ofrecerse como almacenamiento ilimitado ni cobrarse automáticamente por excedentes.

## Arquitectura de precio para investigar, no para imponer

El precio final no puede calcularse con rigor hasta conocer costos, soporte y disposición de pago. La siguiente tabla es una **hipótesis interna**, no una tarifa aprobada para salir a vender:

| Tamaño | Base mensual provisional |
|---|---:|
| 1–15 habitaciones | $899 MXN |
| 16–30 habitaciones | $1,199 MXN |
| 31–50 habitaciones | $1,499 MXN |
| Más de 50 | Cotización |

Opcionales provisionales:

| Opcional | Mensual |
|---|---:|
| Inventario | $199 |
| Seguimiento de facturación | $149 |
| Centro documental | $199 con 2 GB; ampliación todavía no vendible |
| Reputación y encuestas | $199, no vender hasta resolver Google |
| Descuentos de huésped | $99 |
| Tarifas dinámicas | $199 |

### Por qué esta estructura

- cobra por escala aproximada de operación sin volver compleja la cotización;
- conserva modularidad;
- queda por encima de referencias internacionales de entrada que no incluyen acompañamiento local equivalente;
- evita un paquete “premium” artificial;
- permite aprender disposición de pago.

### Límites

Estos precios son **P**, no “precio de mercado comprobado”. Sirvoy publica desde USD 9/mes y Lodgify desde EUR 12–13/mes, pero el alcance, número de habitaciones, moneda, soporte e integraciones varían. Cloudbeds no publica importe. No existe comparación equivalente suficiente para copiar precios.

Antes de formalizar:

1. calcular costo mensual de hosting, respaldo, soporte y traslados;
2. confirmar si incluye IVA;
3. asegurar margen después de horas de soporte;
4. registrar objeciones de 5 propuestas;
5. revisar precio tras los primeros 3 clientes nuevos.

## Piloto guiado de 30 días

### Propósito

El piloto permite que un hotel que todavía no confía en una empresa nueva conozca Medisoft dentro de su operación. No se utiliza para amenazar con retirar la oferta ni para obligar a pagar.

Se puede ofrecer cuando el hotel muestre curiosidad, una necesidad posible o temor al cambio, aunque todavía no esté listo para comprar. Antes de instalarlo sí necesitamos confirmar que alguien del hotel lo utilizará; de lo contrario, no existiría una prueba real que pudiera convencerlo.

### Forma de ofrecerlo

> Entiendo que todavía no conozca nuestra empresa y que no quiera decidir solo por una demostración. Podemos configurar Medisoft durante 30 días sin costo para que usted y su personal vean cómo funciona en su hotel. Nosotros los capacitamos y acompañamos. Al terminar, ustedes deciden con la experiencia real; si no les sirve, les entregamos la información exportable acordada en Excel.

### Condiciones

- precio posterior escrito desde el inicio;
- fecha de inicio y final;
- responsable del hotel;
- tareas que se usarán;
- datos que cargará cada parte;
- sesiones en días 1, 7, 15 y 30;
- aspectos que el hotel quiere comprobar;
- conversación final para conocer su decisión;
- exportación a Excel si no continúa;
- tratamiento y eliminación/conservación de datos documentados.

### Costo

**Piloto gratuito durante 30 días, sin depósito.**

La reciprocidad solicitada no es dinero. Es:

- permitir la configuración;
- nombrar a una persona de contacto;
- recibir capacitación;
- intentar utilizarlo en tareas reales;
- informar dudas y dificultades;
- aceptar una conversación al concluir.

Esto no es presión: son las condiciones mínimas para poder demostrar el valor.

### Criterios de éxito

- el personal pudo realizar las tareas acordadas;
- el hotel identificó funciones útiles y dificultades;
- hubo uso suficiente para comparar contra el método anterior;
- el dueño o administrador recibió una explicación de resultados;
- al final existe una decisión, una solicitud de extensión justificada o una razón clara para no continuar.

Los indicadores numéricos se acuerdan con el hotel según su volumen. No imponer “80% de reservaciones” sin conocer cómo opera.

### Persuasión durante los 30 días

- Día 0: preguntar qué necesita comprobar para confiar.
- Día 1: acompañar la primera tarea real.
- Día 3: resolver fricción temprana.
- Día 7: mostrar un beneficio que ya haya ocurrido.
- Día 15: comparar el método anterior y el nuevo.
- Día 21: preguntar qué impediría continuar.
- Día 27: resumir evidencia, resolver objeciones y presentar alcance final.
- Día 30: pedir la contratación con claridad, sin castigo ni urgencia falsa.

## Cierre directo

> Por lo que me explicó, el paquete base puede ayudarles con [problemas]. Si usted ya tiene claridad, podemos iniciar directamente. Si primero necesita comprobarlo y conocer cómo trabajamos, podemos acompañarlos durante un piloto gratuito de 30 días. ¿Cuál de las dos opciones le daría más seguridad?

## Comparación responsable

| Mantener situación | Adoptar Medisoft |
|---|---|
| Sin costo de cambio | Requiere configuración y aprendizaje |
| Método conocido | Flujo hotelero estructurado |
| Información puede quedar separada | Registros relacionados |
| Dependencia de libreta/archivo/persona | Usuarios y permisos |
| Soporte interno improvisado | Acompañamiento acordado |

## Límites de promesa

No prometer:

- operación offline sincronizable;
- timbrado CFDI;
- sincronización OTA;
- aplicación nativa en tiendas;
- precio optimizado por IA;
- cero errores, cero caídas o cero pérdida;
- seguridad invulnerable;
- soporte ilimitado;
- desarrollo personalizado incluido.

## Garantía de salida

> Si al terminar el piloto deciden no continuar, entregaremos en Excel la información exportable acordada y definiremos por escrito la eliminación o conservación temporal de la información alojada.

Debe existir documento de entrega y confirmación.
