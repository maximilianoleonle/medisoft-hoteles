# Etapa 20 — Guiones de demostración por bloque

Resuelve el pendiente 3 de la auditoría §9. **Todo se demuestra sobre la cuenta de demostración** (`Hotel Demo Medisoft`), nunca sobre Los Cedros ni sobre ningún hotel real: los datos de huéspedes son de terceros y no nos pertenecen.

Sembrar o restablecer la demo:

```bash
MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tools/saas/sembrar_hotel_demo.php
```

**Entrar:** usuario `demo_medisoft`, contraseña `DemoMedisoft2026*`.

### Dónde está cada cosa (para no buscar en vivo)

| Bloque | Qué buscar |
|---|---|
| Inventario | 2 productos bajo mínimo, ya marcados |
| Control de facturación | 3 solicitudes: una completada, una en proceso, una pendiente |
| Centro documental | contrato → huésped **Rocío Estrada** · comprobante → reservación de grupo · identificación **archivada** → **María Fernanda Ruiz** |
| Reputación | 3 encuestas respondidas con 5, 3 y **2 estrellas** |
| Descuentos | **Rocío Estrada**, 10 % en su perfil, aplicado en sus reservaciones |
| Tarifas dinámicas | temporada "Fin de semana largo", **+25 %**, vigente desde hoy y 7 días |

> La ventana de la tarifa dura una semana: si la demo se dejó vieja, el incremento deja de estar vigente. Resembrar antes de cada visita lo resuelve.

## Reglas de la demostración

1. **Un solo opcional por visita**, el que corresponda al dolor que el hotelero ya confirmó. Recorrer todo satura y diluye.
2. **Primero el núcleo** (8-12 min): Inicio → Habitaciones → Reservaciones → Huéspedes → Caja. Luego el opcional.
3. **No tocar lo que no se vende.** Compras, proveedores, cuentas por pagar y Huésped frecuente están fuera de la oferta: no abrirlos ni mencionarlos.
4. **Si algo falla en vivo, se dice.** Nunca improvisar una explicación de una pantalla que no cargó.
5. Cada guion trae su **frontera**: lo que hay que decir en voz alta para no crear una expectativa falsa. No es letra chica, es parte del guion.

---

## 1 · Inventario — $299

**Se ofrece cuando dijeron:** "no sabemos cuánto queda", "se pierde el jabón/papel", "compramos cuando alguien avisa".

1. Abrir Inventario: el tablero ya trae productos con existencias.
2. Señalar el producto **bajo mínimo** y su alerta.
3. Registrar una salida de 3 piezas de un producto → mostrar el stock antes y después.
4. Abrir el historial de ese producto: quién movió qué y cuándo.
5. Cerrar con el valor total del inventario.

**Frontera:** *"Esto le dice qué hay y qué se movió. No lee códigos de barras, no predice compras y el descuento automático por habitación requiere configurarse."*

## 2 · Control de facturación — $249

**Se ofrece cuando dijeron:** "se nos pierden las solicitudes de factura", "el contador y recepción no se entienden".

1. Abrir una solicitud pendiente: reservación, huésped, pagos y datos fiscales en una sola pantalla.
2. Marcarla **en proceso**.
3. Mostrar dónde se captura el folio de la factura ya emitida y marcarla completada.
4. Mostrar la lista filtrada por estado: qué falta, qué va en camino, qué se cerró.

**Frontera (decirla siempre, es el módulo que más se malinterpreta):** *"Medisoft **no** timbra facturas ni se conecta al SAT. La factura la sigue emitiendo su contador o su sistema de facturación; aquí se organiza la solicitud y se registra el folio para que ninguna se pierda."*

## 3 · Centro documental — $149

**Se ofrece cuando dijeron:** "los contratos andan en el celular de alguien", "no encontramos el comprobante".

1. Desde la ficha de un huésped o proveedor, subir un PDF ficticio.
2. Ponerle título y etiquetas; mostrar la vista previa.
3. Volver a la ficha: el documento quedó **vinculado a ese registro**, no suelto en una carpeta.
4. Archivarlo y mostrar cómo se recupera.
5. Mostrar el indicador de espacio: cuánto lleva de sus 2 GB.

**Frontera:** *"2 GB incluidos, 10 MB por archivo, en PDF o imagen. No es firma electrónica ni da validez legal, y no lee el contenido de los documentos."*
Detalle completo en `docs/politica-centro-documental.md`.

## 4 · Reputación y encuestas — $149

**Se ofrece cuando dijeron:** "nos preocupan las reseñas", "no sabemos si el huésped se fue contento".

1. Mostrar un checkout reciente y generar su encuesta.
2. Abrir el enlace **en el celular** (impacta más que en la laptop).
3. Responder con calificación **baja** a propósito.
4. Volver al sistema: mostrar la **alerta interna** que llegó por esa calificación.
5. Mostrar el tablero: promedio, respuestas y NPS.

**Frontera (dos, ambas obligatorias):**
- *"La invitación a dejar reseña en Google se le ofrece a todos los que responden, califiquen bien o mal. Filtrar quién puede reseñar está prohibido por Google y pone en riesgo el perfil del hotel."*
- *"El envío por correo no está disponible: la encuesta se comparte copiando el enlace, por WhatsApp o como usted prefiera."*

> Por qué se demuestra con calificación baja: es la prueba de que la alerta interna funciona, que es el valor real. El paso a Google no depende de la nota.

## 5 · Descuentos de huésped — $99

**Se ofrece cuando dijeron:** "tenemos clientes de siempre a los que les hacemos precio".

1. Abrir el perfil de un huésped frecuente ficticio y mostrar su regla de descuento.
2. Crear una reservación para ese huésped: el descuento **se aplica solo** en la cotización.
3. Mostrar el desglose: tarifa, descuento y total.
4. Mostrar el ajuste manual del descuento por una persona autorizada.

**Frontera:** *"El descuento se guarda en el perfil y se aplica al cotizar. No es un programa de puntos ni genera cupones automáticos."*

## 6 · Tarifas dinámicas — $149

**Se ofrece cuando dijeron:** "en temporada cobramos distinto", "subimos precio en puente y se nos olvida bajarlo".

1. Mostrar una regla de temporada ya creada (fechas y porcentaje).
2. Crear una reservación dentro de esas fechas: el precio sale con el incremento aplicado.
3. Mostrar cómo se ve el precio por noche contra el precio base.
4. Enseñar la revisión de reservaciones existentes al cambiar una tarifa: qué reservas afectaría, antes de confirmar.

**Frontera:** *"Usted define las reglas; el sistema las aplica. No decide precios solo ni analiza a la competencia."*

---

## Cierre común

> "Lo que vio del núcleo va incluido. Este bloque es aparte porque no todos los hoteles lo necesitan. ¿Le hace sentido para lo que me contó al principio?"

## Antes de cada demostración

- [ ] Restablecer la cuenta demo (`--limpiar` y volver a sembrar) para que no queden datos de la visita anterior.
- [ ] Entrar con el usuario demo, **nunca** con una cuenta de hotel real.
- [ ] Verificar que el bloque a demostrar esté contratado en la cuenta demo.
- [ ] Probar el flujo completo una vez antes de salir.

## Punto de atención pendiente

`Huésped frecuente` (Lealtad, $179) y `Compras y proveedores` ($199) siguen **abiertos a la venta en el catálogo** aunque la auditoría los dejó fuera de la oferta. No se demuestran ni se cotizan. Bloquearlos globalmente apagaría la función a los hoteles que ya la tienen contratada, así que la separación es por disciplina comercial, no por candado técnico: **no aparecen en propuestas.**
