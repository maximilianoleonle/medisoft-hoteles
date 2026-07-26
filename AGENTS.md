\# AGENTS.md — Medisoft Hoteles



\## Contexto del proyecto



Proyecto: Medisoft Hoteles  

Stack: PHP + MySQL + MVC propio + vistas PHP + Tailwind/CSS propio  

Rama principal de trabajo actual: `feature/saas-multihotel`



El sistema tiene dos áreas principales:



1\. Panel Medisoft / Panel SaaS

&#x20;  - Administra hoteles/clientes.

&#x20;  - Usa identidad Medisoft.

&#x20;  - Tokens visuales `--ms-\*`.



2\. Sistema hotelero operativo

&#x20;  - Lo usa cada hotel.

&#x20;  - Usa branding del hotel.

&#x20;  - Tokens visuales `--brand-\*`.



Regla importante:



\- `--ms-\*` pertenece al Panel Medisoft / SaaS.

\- `--brand-\*` pertenece al hotel cliente.

\- No mezclar identidades.



\## Límites críticos



No tocar sin autorización explícita:



\- `service-worker.js`

\- `pwa.js`

\- `offline-data.js`

\- `reservaciones-offline.js`

\- IndexedDB

\- cache names

\- `/api/sync`

\- migraciones

\- base de datos

\- modelos

\- rutas

\- permisos

\- auth

\- cálculos de caja

\- cálculos de reportes

\- lógica profunda de reservaciones

\- lógica de check-in/check-out

\- lógica de caja/cortes/movimientos



`/api/sync` debe seguir bloqueado con HTTP 423 y JSON `sync\_temporarily\_disabled`.



\## Formularios



Si se modifica una vista con formularios:



\- No cambiar `action`.

\- No cambiar `method`.

\- No cambiar `name`.

\- No quitar CSRF.

\- No quitar hidden inputs.

\- No mover submit fuera de su form.

\- No anidar forms.

\## Regla obligatoria: contrato operativo antes de cambios criticos

Antes de modificar reservaciones, pagos/anticipos, check-in, check-out,
habitaciones, cuentas por cobrar, facturacion o caja, se debe cumplir el
contrato de `docs/contrato_operativo_cambios_criticos.md`.

No se agregan vistas, botones, endpoints ni automatismos nuevos sobre esa
frontera sin declarar:

\- que archivos se van a tocar;

\- que archivos no se van a tocar;

\- que flujos pueden afectarse;

\- como se prueba;

\- como se revierte si rompe algo.

Si un cambio rompe un flujo ya testeado, se pausa la feature nueva y se corrige
la regresion antes de seguir avanzando.



\## Validaciones obligatorias



Después de modificar PHP:



```bash

php -l archivo.php


## Regla obligatoria: bloques comerciales (módulos activables)

Desde julio 2026 el sistema cobra à la carte: paquete básico + bloques opcionales
con precio por hotel. TODA nueva implementación (sección, feature vendible o
grupo de rutas) DEBE integrarse a este sistema. No hay features "sueltas".

Checklist obligatorio para implementar algo nuevo:

1. **Registrar el bloque en el catálogo** (migración SQL):
   `INSERT INTO modulos (clave, nombre, descripcion, categoria, orden, icono, ruta_base, precio_mensual, tipo_comercial, activo_global, motivo_bloqueo)`.
   - Declarar SIEMPRE `tipo_comercial` ('base'|'opcional'|'interno'; default
     'opcional') y `activo_global` (0 = bloqueado con `motivo_bloqueo` visible
     en el panel; un bloque nuevo SIN precio autorizado por la dueña nace en 0).
   - `es_core = 0` salvo decisión explícita del owner. El paquete base actual
     (10 bloques, jul-25-2026): dashboard, habitaciones, reservaciones,
     huespedes, caja, usuarios, roles_avanzados ("Roles y permisos"),
     mantenimiento, notificaciones y pwa. Internos no vendibles:
     configuracion, anticipos, reportes (contenedor del centro).
   - PROMOVER un opcional a base no es solo el UPDATE de catálogo: actualizar
     `$BASE` del verificador (lista exacta, si no falla), revisar los crons que
     filtran por `INNER JOIN hotel_modulos` (es_core no crea filas → dejan fuera
     a todos los hoteles) y darle a la pantalla su permiso propio, porque el
     módulo deja de ser el candado.
   - Poner precio default; será editable en `/admin/saas/modulos`.
   - Si el bloque reemplaza algo que ya era visible, activarlo retroactivamente
     en hoteles existentes (`fuente = 'migracion'`) para no cambiar comportamiento.
   - Verificar el catálogo tras la migración:
     `docker exec medisoft_hoteles_app php /var/www/html/tools/saas/verificar_clasificacion_comercial.php`.

2. **Gate de servidor**: en el `before()` del controlador (o al inicio de la
   acción) llamar `require_hotel_module('clave')`. Para endpoints JSON usar
   `require_hotel_module_api('clave')` o mapear la acción en
   `ApiController::moduleForCurrentApiAction()`.

3. **Exportaciones**: cualquier descarga Excel/PDF de listados o reportes se
   gatea bajo el bloque `exportaciones` (no bajo el módulo padre). Documentos
   operativos (cotización, recibo) NO se gatean ahí.

4. **Sidebar/UI**: visibilidad de menú con `$menuModuloActivo('clave')` en
   `sidebar.php`; botones de export envueltos en
   `hotel_menu_module_enabled('exportaciones')`.

5. **Semántica core**: los módulos `es_core = 1` están SIEMPRE activos — el SQL
   usa `(m.es_core = 1 OR hm.activo = 1)` y `actualizarModulosHotel()` los
   fuerza. Nunca depender de que exista fila en `hotel_modulos` para un core.

6. **Precios**: nunca hardcodear precios en código o vistas; viven en
   `modulos.precio_mensual` (catálogo) y `hotel_modulos.precio_override`
   (por hotel). El cobro mensual se calcula con `Modulo::resumenCobroMensual()`.

7. **Deploy**: aplicar la migración ANTES de subir código que consulte columnas
   nuevas de `modulos`/`hotel_modulos`. La BD local real es la del `.env`
   (`medisoft_hoteles_import`).

8. **Inputs de dinero en el panel**: `number_format($v, 2, '.', '')` (sin
   separador de miles) para `value` de `<input type="number">`.

## Regla: dinero online (motor de reservas y futuros canales de pago)

- El dinero cobrado por pasarela NUNCA entra directo a Caja. Va al ledger
  `motor_pagos_online` (estado pagado) y recepcion lo CONCILIA despues via
  `AnticipoService::registrar()` (que exige corte de Caja abierto y usuario).
- Los webhooks jamás insertan en `movimientos_caja` / `reservacion_abonos`.
- Montos SIEMPRE recalculados en servidor; nunca confiar en el navegador.
- Webhooks: Stripe se verifica por firma (t/v1 HMAC); MercadoPago consultando
  el pago a su API. Idempotencia obligatoria (los webhooks se reintentan).
- Secrets de pasarela: cifrados AES-256-GCM con `MOTOR_PASARELA_KEY` (env,
  fuera de git); nunca en texto plano ni re-mostrados en UI.
- Paginas publicas del motor: vistas `motor/*` son standalone (View.php no
  les pone layout interno) y sus rutas van en la whitelist de `core/Router.php`.
