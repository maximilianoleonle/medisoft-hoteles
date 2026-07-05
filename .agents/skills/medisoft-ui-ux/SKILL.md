\---

name: medisoft-ui-ux

description: Use this skill when improving Medisoft Hoteles UI/UX, visual design, white-label styling, dashboard, habitaciones, reservaciones, huéspedes, caja, reportes, sidebar, header, responsive layouts, badges, cards, buttons, tables, or visible copy. Use it for creative visual improvements that must not touch sensitive backend, PWA/offline, sync, routes, models, migrations, auth, or business logic.

\---



\# Medisoft UI/UX Skill



\## Goal



Improve the visual quality, usability, clarity, and commercial perception of Medisoft Hoteles screens while preserving functionality.



The system must feel:



\- professional

\- modern

\- SaaS-like

\- clear for hotel reception

\- white-label by hotel

\- consistent

\- operationally useful

\- visually credible



\## Identity rules



Panel Medisoft / SaaS:



\- Uses `--ms-\*`.

\- Must not inherit hotel branding.

\- Must not load hotel offline context.



Hotel operational system:



\- Uses `--brand-\*`.

\- Must follow each hotel branding.

\- Must not inherit Medisoft admin tokens.



Never mix `--ms-\*` and `--brand-\*` incorrectly.



\## Absolute no-touch areas



Do not touch:



\- `service-worker.js`

\- `pwa.js`

\- `offline-data.js`

\- `reservaciones-offline.js`

\- IndexedDB

\- cache names

\- `/api/sync`

\- migrations

\- database schema

\- models

\- routes

\- auth

\- permissions

\- caja calculations

\- report calculations

\- reservation business logic

\- check-in/check-out business logic

\- caja/corte/movimiento business logic



If a visual improvement needs backend or business logic, report it as a proposal instead of implementing it.



\## Safe areas



You may improve:



\- views

\- CSS

\- Tailwind classes

\- visual structure

\- cards

\- badges

\- buttons

\- tables

\- spacing

\- typography

\- empty states

\- visible copy

\- responsive layout

\- semantic colors

\- header/sidebar visuals

\- dashboard visual hierarchy

\- room status presentation

\- reservation status presentation

\- caja/reportes visual clarity



\## Forms rules



If editing a view with forms:



\- Do not change `action`.

\- Do not change `method`.

\- Do not change `name`.

\- Do not remove CSRF.

\- Do not remove hidden inputs.

\- Do not move submit buttons outside their form.

\- Do not create nested forms.



\## Design principles



Prioritize:



1\. clarity

2\. hierarchy

3\. consistency

4\. semantic colors

5\. readable states

6\. strong primary actions

7\. differentiated dangerous actions

8\. responsive usability

9\. credible SaaS product feel

10\. no fake metrics



Avoid:



\- excessive cards

\- noisy colors

\- brown/legacy palettes unless intentionally part of hotel branding

\- fake KPIs

\- debug UI

\- dead buttons

\- generic copy

\- confusing states

\- beautiful but unusable layouts



\## Operational state colors



Suggested semantics:



\- Disponible: green

\- Ocupada: slate / calm neutral

\- Por llegar: purple / indigo

\- Limpieza: blue

\- Mantenimiento: amber / tan

\- No llegó / vencido / critical: red

\- Ingresos: green

\- Gastos: red

\- Esperado / brand accent: brand blue



Do not turn all operational states into brand color. Brand color is for actions, headers, accents, and navigation.



\## Required checks



After changes:



```bash

git diff --check

git diff --name-only

git status --short

