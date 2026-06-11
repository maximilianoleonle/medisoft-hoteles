# Configuracion tenant-safe y catalogos por hotel

## 1. Objetivo de la fase

Esta fase busca convertir configuraciones fijas heredadas de Los Cedros en configuracion segura por hotel, manteniendo aislamiento tenant-safe y sin romper operacion historica.

El alcance inicial es documentar y ordenar que debe ser configurable por hotel, que debe vivir como configuracion simple en `hotel_configuracion`, que debe modelarse en tablas normalizadas y que no debe hacerse configurable por ahora.

La prioridad es proteger caja, reservaciones, reportes, check-in/check-out y PWA/offline. Cualquier implementacion futura debe preservar compatibilidad con Los Cedros mediante fallbacks controlados.

## 2. Estado actual detectado

Ya existen bases SaaS para separar hoteles, configuracion, branding, usuarios, modulos y planes:

- `hoteles`
- `hotel_configuracion`
- `hotel_branding`
- `hotel_usuarios`
- `hotel_modulos`
- `planes`
- `plan_modulos`

Tambien existen configuraciones hardcoded o legacy que todavia deben ordenarse antes de implementar cambios:

- `Configuracion` usa la tabla `configuracion` sin `hotel_id`.
- Tipos de habitacion, pisos, amenidades, estacionamientos, metodos de pago y unidades de medida estan fijos en codigo o vistas.
- Huespedes y vehiculos todavia deben analizarse para tenant-safety.
- Caja y reportes tienen reglas sensibles que no deben modificarse sin QA financiero.
- PWA/manifest ya usa branding, pero no se debe tocar offline/cache/sync en esta fase.

## 3. Principio rector

No todo debe ser configurable. Solo debe hacerse configurable lo que no rompa la operacion historica, caja, reportes, reservaciones o seguridad.

La configuracion por hotel debe ser explicita, auditable y con fallback seguro. Los catalogos operativos deben aislarse por `hotel_id` cuando su contenido pueda variar entre hoteles.

## 4. Clasificacion propuesta

| Elemento | Estado actual | Destino recomendado | Prioridad | Riesgo | Comentario |
| --- | --- | --- | --- | --- | --- |
| Nombre comercial del hotel | SaaS/branding parcial | `hotel_branding` o `hotel_configuracion` | Alta | Bajo | Debe mostrarse por hotel sin afectar operacion. |
| Logo | Branding parcial | `hotel_branding` | Alta | Bajo | Ya se usa en identidad visual; mantener separado de identidad Medisoft. |
| Colores de marca | Branding parcial | `hotel_branding` | Alta | Bajo | Usar tokens `--brand-*` para sistema hotelero, no `--ms-*`. |
| Telefono | Configuracion simple o hardcoded | `hotel_configuracion` | Media | Bajo | Clave simple de contacto por hotel. |
| Direccion | Configuracion simple o hardcoded | `hotel_configuracion` | Media | Bajo | Usada en documentos, tickets y comunicacion. |
| Check-in / check-out base | Posible valor fijo | `hotel_configuracion` | Alta | Medio | Afecta operacion; requiere fallback y pruebas. |
| Moneda | Posible valor fijo | `hotel_configuracion` | Alta | Medio | No debe reescribir importes historicos. |
| Pisos / zonas | Fijo en codigo/vistas | Tablas normalizadas por hotel | Alta | Medio | Catalogo operativo base para habitaciones. |
| Tipos de habitacion | Fijo en codigo/vistas | `hotel_tipos_habitacion` | Alta | Alto | Puede afectar disponibilidad, tarifas y reportes. |
| Amenidades | Fijo en codigo/vistas | `hotel_amenidades` | Media | Bajo | Catalogo descriptivo con bajo impacto financiero. |
| Estacionamientos | Fijo en codigo/vistas | `hotel_estacionamientos` | Media | Medio | Puede afectar check-in, consumo o cobros. |
| Metodos de pago | Fijo o legacy | `hotel_metodos_pago` | Alta | Alto | Impacta caja, cortes y reportes financieros. |
| Categorias de caja | Fijo o legacy | `hotel_categorias_caja` | Alta | Alto | Requiere QA financiero y compatibilidad historica. |
| Cajas / puntos de cobro | Parcial o fijo | Tabla normalizada por hotel | Alta | Alto | Debe amarrarse a cortes, movimientos y usuarios. |
| Tarifas | Logica operativa sensible | Tablas normalizadas existentes o futuras | Alta | Alto | No iniciar aqui; afecta reservaciones y disponibilidad. |
| Incrementos de tarifa | Logica operativa sensible | Tabla normalizada o reglas controladas | Media | Alto | Requiere pruebas contra historial y cotizaciones. |
| Unidades de medida | Fijo en codigo/vistas | `hotel_unidades_medida` | Media | Medio | Afecta inventario y consumos. |
| Categorias de inventario | Catalogo operativo | Tabla normalizada por hotel | Media | Medio | Debe preservar movimientos historicos. |
| Productos de inventario | Catalogo operativo | Tabla normalizada por hotel | Media | Alto | Impacta consumos, existencias y reportes. |
| Reglas de consumo automatico | Logica sensible | Reglas controladas por hotel | Baja | Alto | No implementar sin mapa completo de impactos. |
| Politicas de reservacion | Posible texto o reglas fijas | `hotel_configuracion` para textos simples; tablas/reglas para logica | Media | Alto | Separar copy configurable de reglas operativas. |
| Politicas de cancelacion | Posible texto o reglas fijas | `hotel_configuracion` o tabla normalizada | Media | Alto | Puede impactar cobros, penalizaciones y reportes. |
| Plantillas PDF/tickets/cotizaciones | Parcial o fijo | `hotel_plantillas_documento` | Media | Medio | Configurar presentacion sin alterar calculos. |
| Mensajes WhatsApp | Posible fijo | `hotel_configuracion` o plantillas por hotel | Media | Bajo | Requiere variables permitidas y fallback. |
| Roles y permisos | Sistema sensible | Mantener base; evolucionar con reglas SaaS | Baja | Alto | No tocar permisos criticos sin diseno aprobado. |
| Modulos por plan | SaaS existente | `plan_modulos` y `hotel_modulos` | Media | Alto | Debe ser controlado por plan, no por vistas sueltas. |
| Huespedes | Pendiente de analisis tenant-safe | Por definir | Alta | Alto | Definir si seran globales o aislados por hotel. |
| Vehiculos | Pendiente de analisis tenant-safe | Por definir | Alta | Alto | Definir relacion con huespedes, estancias y hoteles. |

## 5. Que deberia vivir en hotel_configuracion

`hotel_configuracion` deberia almacenar configuraciones simples tipo key-value, acotadas y faciles de validar. Estas claves no deben reemplazar catalogos completos ni reglas profundas de negocio.

Claves candidatas:

- `operacion.checkin_hora`
- `operacion.checkout_hora`
- `operacion.moneda`
- `contacto.telefono`
- `contacto.direccion`
- `reservaciones.tolerancia_minutos`
- `documentos.mostrar_logo`
- `whatsapp.numero_caja`
- `pwa.nombre_app`

Cada clave debe tener tipo esperado, valor por defecto, alcance por hotel y fallback controlado. Si una configuracion necesita multiples filas, relaciones, orden, estado activo/inactivo o historial, debe ir en tabla normalizada y no como key-value.

## 6. Que deberia vivir en tablas normalizadas

Los catalogos operativos deben vivir en tablas por hotel cuando requieren relaciones, busqueda, ordenamiento, activacion/desactivacion o integridad referencial.

Ejemplos de tablas candidatas:

- `hotel_pisos`
- `hotel_zonas`
- `hotel_tipos_habitacion`
- `hotel_amenidades`
- `hotel_estacionamientos`
- `hotel_metodos_pago`
- `hotel_categorias_caja`
- `hotel_unidades_medida`
- `hotel_plantillas_documento`

Estas tablas deben incluir `hotel_id`, estado activo cuando aplique, timestamps y reglas de fallback o migracion desde valores legacy. Todavia no se deben crear esas migraciones en esta tarea.

## 7. Que NO debe hacerse configurable por ahora

Por seguridad, estabilidad operativa y proteccion del historial, no debe hacerse configurable por ahora:

- CSRF
- Autenticacion
- Sesiones
- Permisos criticos base
- `/api/sync`
- Service worker
- IndexedDB
- Cache offline
- Maquina de estados de reservacion
- Reglas de solapamiento de reservaciones
- Calculos profundos de caja
- Reportes financieros historicos
- Auditoria/logs historicos

Estas areas requieren diseno especifico, pruebas de regresion y aprobacion explicita antes de cualquier cambio.

## 8. Riesgos principales

- Mezcla de datos entre hoteles por ausencia o uso incorrecto de `hotel_id`.
- Romper saldos historicos al cambiar catalogos financieros sin compatibilidad.
- Romper disponibilidad si tipos de habitacion, estados o tarifas se modifican sin mapa de dependencias.
- Romper check-in/check-out al cambiar horarios o reglas usadas por reservaciones existentes.
- Romper reportes financieros por reclasificacion de metodos de pago, categorias o cajas.
- Crear demasiadas opciones y complicar la UX operativa de hoteles pequenos.
- Romper PWA/offline si se toca service worker, cache, IndexedDB o sync fuera de alcance.

## 9. Roadmap recomendado

### Microfase 1: Mapa de configuracion tenant-safe y catalogos base

Solo documentacion y analisis. Identificar valores actuales, origen, uso, riesgos, fallback y dependencia con vistas/controladores/modelos.

### Microfase 2: Registry de configuracion por hotel

Crear lectura segura desde `hotel_configuracion` con fallback controlado. Centralizar claves permitidas, tipos esperados y valores por defecto.

### Microfase 3: Catalogos base por hotel

Modelar pisos, zonas, tipos de habitacion, amenidades, estacionamientos y unidades de medida como catalogos por hotel.

### Microfase 4: Caja configurable

Incorporar metodos de pago, categorias de caja y puntos de cobro con QA financiero, validacion historica y pruebas de cortes/movimientos.

### Microfase 5: Reservaciones y documentos

Configurar politicas, plantillas, PDF, WhatsApp, cotizaciones y tickets sin alterar calculos ni estados sensibles.

### Microfase 6: Roles, modulos y planes

Evolucionar permisos avanzados, modulos por plan y limites SaaS con control explicito y pruebas de seguridad.

## 10. Primera implementacion futura recomendada

No se recomienda empezar por caja ni reservaciones porque concentran el mayor riesgo operativo y financiero.

La primera implementacion futura deberia enfocarse en:

- Documentar claves actuales.
- Crear `ConfiguracionHotelRegistry` o helper equivalente.
- Leer `hotel_configuracion` de forma segura.
- Agregar fallback sin romper Los Cedros.
- No hacer migraciones hasta aprobar diseno.

El objetivo debe ser introducir una capa de lectura controlada antes de mover datos o crear nuevos catalogos.

## 11. Checklist de seguridad antes de implementar

- [ ] Tiene `hotel_id`?
- [ ] Afecta datos historicos?
- [ ] Afecta caja?
- [ ] Afecta reservaciones?
- [ ] Afecta reportes?
- [ ] Afecta PWA/offline?
- [ ] Requiere migracion?
- [ ] Tiene fallback?
- [ ] Se puede probar sin datos reales?

## 12. Preguntas abiertas

- Huespedes seran globales o por hotel?
- Vehiculos seran globales o por hotel?
- Metodos de pago seran libres o controlados?
- Manolo/Elia representan propietarios, centros de ingreso o una regla temporal?
- WhatsApp sera por hotel, sucursal, caja o usuario?
- Cada hotel podra definir estados visuales o solo colores/etiquetas?
