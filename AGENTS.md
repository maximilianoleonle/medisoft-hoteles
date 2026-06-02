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



\## Validaciones obligatorias



Después de modificar PHP:



```bash

php -l archivo.php

