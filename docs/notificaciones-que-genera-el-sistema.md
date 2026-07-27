# Qué avisa el sistema hoy — inventario verificado

**Fecha:** 2026-07-26 · Resuelve el pendiente 6 de `auditoria_comercial_modulos_20260725.md` §9.

Verificado contra código, base de desarrollo, **base de producción y crontab del servidor**. La columna "Vive hoy" separa lo que un hotel real recibe de lo que solo existe escrito.

> **Regla de uso comercial:** solo se promete lo marcado ✅. Lo marcado ❌ es función construida pero sin disparador activo — mencionarla como si operara sería vender lo que no ocurre.

---

## 1. Las tres advertencias que cambian el discurso de venta

**a) No hay avisos en tiempo real.** Las 8 reglas automáticas se evalúan cuando **alguien abre el Inicio o la campanita**, no por un proceso de fondo. Si nadie entra al sistema en toda la mañana, no se genera ningún aviso. Decir "te avisa en el momento" es falso.
→ Frase segura: *"cuando alguien del hotel entra al sistema, revisa el estado y avisa lo que está pendiente."*

**b) El push viene apagado de fábrica.** `notificaciones.pwa_push_activo` es `false` por defecto y solo lo enciende Medisoft desde `/configuracion`. Sin encenderlo no llega nada al celular: todo queda en la campanita. En producción hay **un solo dispositivo suscrito** en todo el sistema — es función real, sin adopción probada.

**c) El correo no funciona.** El sistema usa `mail()` de PHP y **no hay ningún servidor de correo instalado** en la imagen (`sendmail` no existe en el contenedor). Todo envío por correo falla, incluida **la encuesta de Reputación**. Al vender Reputación hay que decir que la encuesta se comparte **copiando el enlace** (por WhatsApp o como sea), no por correo automático.

---

## 2. Avisos que sí operan hoy

### Reglas automáticas (al abrir Inicio o la campanita)

| Aviso | A quién | Requiere |
|---|---|---|
| Check-ins pendientes (llegadas vencidas sin registrar) | recepción | — |
| Check-outs pendientes (salidas vencidas) | recepción | — |
| Habitaciones en mantenimiento | mantenimiento | — |
| Habitaciones en limpieza sin liberar | limpieza | — |
| Caja abierta demasiadas horas (12 h / 24 h) | gerencia | — |
| Inventario bajo o sin stock | gerencia | bloque Inventario |
| Solicitudes de factura pendientes | gerencia | bloque Control de facturación |

### Por acción de una persona

| Aviso | Cuándo | A quién |
|---|---|---|
| Corte de caja cerrado (indica sobrante o faltante) | al cerrar el corte | gerencia |
| Mantenimiento iniciado / finalizado | al marcarlo en Habitaciones | mantenimiento |
| **Mantenimiento con reserva próxima o empalmada** | al programarlo sobre un cuarto reservado | mantenimiento |
| Mantenimiento programado, cancelado o activado | al hacerlo | mantenimiento |
| Mantenimiento cerrado con evidencia y costo | al cerrarlo | mantenimiento |
| Solicitud de factura creada, en proceso, completada o cancelada | al cambiar su estado | gerencia |
| **Calificación baja de un huésped** | cuando el huésped responde ≤ umbral (default 3) | gerencia |

### Por proceso programado

| Aviso | Cuándo | Programado en producción |
|---|---|---|
| Cierre del día con pendientes (no-shows, checkouts vencidos, cortes abiertos) | 4:00 AM | **Sí** — verificado, con historial real |

---

## 3. Existe en el código pero NO ocurre — no mencionar

| Función | Por qué no ocurre |
|---|---|
| Recordatorio de servicio preventivo próximo a vencer | su proceso programado **no está instalado** en el servidor (0 avisos generados) |
| Briefing matutino del Copiloto | proceso no instalado |
| Cierre vespertino del Copiloto | proceso no instalado |
| Alerta "semana floja a la vista" | proceso no instalado |
| Avisos del Guardián financiero | proceso no instalado |
| Resumen del día por IA | depende de la llave de API, hoy inválida |

Además, estos avisos **nunca se han disparado** en ningún hotel real, aunque el código es correcto y alcanzable: mantenimiento programado / cancelado / activado y mantenimiento con reserva próxima. Se pueden demostrar en vivo, pero **no presentarlos como historial**.

---

## 4. Quién recibe cada aviso (y un defecto a conocer)

El destinatario se decide por el área a la que pertenece el aviso, y cada área alcanza también a administración:

- gerencia → gerente, administrador, propietario
- recepción → recepcionista, administrador
- limpieza → limpieza, recepcionista, administrador
- mantenimiento → mantenimiento, recepcionista, administrador

**Defecto conocido:** en la campanita, un usuario con rol *gerente* **no ve** los avisos de reservaciones ni de habitaciones (check-ins pendientes, limpieza sin liberar) salvo que sean críticos; esos van a recepción. Sí los ve quien tenga rol *administrador* o *propietario*.
→ **No prometer** "el gerente se entera de los check-ins pendientes" sin antes revisar con qué rol quedará esa persona. Vale la pena corregirlo antes de venderlo como control gerencial.

## 5. Qué se puede apagar

- **Por hotel:** el interruptor general de reglas automáticas, cada una de las 8 reglas por separado, el push, y 5 umbrales (retraso, facturas, limpieza, horas de caja). Todo esto vive en `/configuracion`, que **solo abre Medisoft** — el hotel lo pide, nosotros lo ajustamos.
- **Por dispositivo:** cada persona puede apagar el push de su propio teléfono desde `/notificaciones`.
- **No existe** preferencia por persona para elegir qué tipos recibir: es todo o nada por dispositivo.

## 6. Frases seguras

> "El sistema concentra en una campanita lo que está pendiente: llegadas y salidas sin registrar, habitaciones sin liberar, caja abierta de más, inventario bajo y solicitudes de factura. Cada aviso llega al área que le toca."

> "Si su equipo instala la aplicación en el celular, podemos activarle los avisos push. Viene apagado y lo encendemos a solicitud."

> "El cierre del día corre solo de madrugada y deja un aviso con lo que quedó pendiente."

**Nunca:** "avisa en tiempo real", "le llega un correo", "el sistema le avisa aunque nadie entre".
