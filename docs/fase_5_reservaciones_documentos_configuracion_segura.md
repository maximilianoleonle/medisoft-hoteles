# Fase 5 - Reservaciones y documentos configurables

## Alcance aplicado

Esta fase avanza sobre reservaciones solo desde configuracion segura por hotel.
No migra tablas, no cambia consultas, no modifica disponibilidad, no cambia
estados de reservacion y no toca check-in/check-out.

Se agregaron claves editables en `hotel_configuracion` para textos
informativos:

- `reservaciones.politica_reserva`
- `reservaciones.politica_cancelacion`
- `reservaciones.mensaje_confirmacion_whatsapp`
- `documentos.texto_cotizacion`
- `documentos.texto_ticket`
- `documentos.texto_footer`

Estas claves se guardan con fallback y validacion desde la pantalla de
Configuracion del hotel.

## Motivo

El plan SaaS marca Fase 5 como Reservaciones, pero reservaciones es una zona
critica. Por seguridad, esta fase se limita a copy configurable y plantillas
preparatorias. La integracion visual en PDFs, WhatsApp, tickets o cotizaciones
debe hacerse en subfases separadas, una salida a la vez, con pruebas de
regresion.

## No modificado

- Migraciones.
- Base de datos/schema.
- Modelos.
- Rutas.
- Permisos/auth.
- Disponibilidad.
- Calendario.
- Tarifas.
- Facturacion.
- Caja.
- Check-in/check-out.
- Cancelaciones.
- PWA/offline/sync.
- `/api/sync`.

## Siguiente subfase recomendada

Elegir una sola salida de documentos y conectarla a estas claves sin cambiar
calculos:

1. Cotizacion PDF.
2. Ticket visual de reservacion.
3. Mensaje WhatsApp de confirmacion.

Antes de integrar cualquiera, validar que el render use fallback cuando la
clave este vacia y que las variables permitidas no ejecuten HTML ni scripts.
