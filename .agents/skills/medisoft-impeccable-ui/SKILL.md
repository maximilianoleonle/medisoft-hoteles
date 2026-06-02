\---

name: medisoft-impeccable-ui

description: Use this skill when redesigning or reviewing Medisoft Hoteles UI with high visual standards, responsive design, accessibility, SaaS polish, white-label consistency, hierarchy, cards, badges, tables, forms, dashboards, habitaciones, reservaciones, caja, reportes, or any screen that must look premium and credible. Do not use for backend, PWA/offline, sync, migrations, models, auth, permissions, or business logic.

\---



\# Medisoft Impeccable UI Skill



\## Purpose



Raise the visual quality of Medisoft Hoteles screens to a premium, credible, SaaS-level standard without breaking functionality.



This skill is for high-standard UI/UX work:

\- visual redesign

\- responsive refinement

\- component consistency

\- hierarchy

\- white-label polish

\- semantic colors

\- empty states

\- dashboard/cards/tables/buttons/badges/forms

\- operational clarity for hotel reception



\## Core philosophy



Design must serve hotel operation.



A screen is good only if:

\- the user understands what is happening quickly

\- primary actions are obvious

\- dangerous actions are clearly separated

\- states are easy to distinguish

\- the UI looks credible and sellable

\- responsive behavior is usable

\- the design does not fake data or hide broken flows



Do not make things “pretty” at the cost of clarity.



\## Absolute bans



Never do these:



\- Do not touch PWA/offline.

\- Do not touch `service-worker.js`.

\- Do not touch `pwa.js`.

\- Do not touch `offline-data.js`.

\- Do not touch `reservaciones-offline.js`.

\- Do not touch IndexedDB.

\- Do not touch cache names.

\- Do not enable or alter `/api/sync`.

\- Do not change routes.

\- Do not change models.

\- Do not change migrations.

\- Do not change permissions.

\- Do not change auth.

\- Do not change database structure.

\- Do not change caja/report calculations.

\- Do not change reservation business logic.

\- Do not change check-in/check-out logic.

\- Do not change caja/corte/movimiento logic.

\- Do not add fake metrics.

\- Do not add demo data unless explicitly requested.

\- Do not redesign multiple large modules at once unless explicitly authorized.



\## Form contract rules



When editing views with forms:



\- Do not change `action`.

\- Do not change `method`.

\- Do not change input `name`.

\- Do not remove CSRF.

\- Do not remove hidden inputs.

\- Do not move submit buttons outside their form.

\- Do not nest forms.

\- Do not change POST behavior.

\- Do not change JS endpoints.



Visual changes only.



\## White-label rules



Panel Medisoft / SaaS:

\- Use `--ms-\*`.

\- Do not use hotel branding.



Hotel operational system:

\- Use `--brand-\*`.

\- Do not use `--ms-\*` as operational hotel styling.



Never mix admin tokens and hotel tokens incorrectly.



\## Brand-aware rules



Hotel brand color should be used for:

\- primary actions

\- headers

\- icons

\- accents

\- active navigation

\- neutral brand moments



Hotel brand color should NOT replace semantic state colors.



Semantic colors:

\- Disponible: green

\- Ocupada: slate / calm neutral

\- Por llegar: purple / indigo

\- Limpieza: blue

\- Mantenimiento: amber / tan

\- No llegó / vencido / critical: red

\- Ingresos: green

\- Gastos: red

\- Advertencia: amber

\- Destructivo: red



\## Quality bar



Before accepting a UI change, check:



1\. Does it look more professional?

2\. Does it improve clarity?

3\. Does it reduce visual noise?

4\. Does it respect hotel branding?

5\. Does it preserve semantic colors?

6\. Does it work on desktop?

7\. Does it not break mobile?

8\. Does it avoid fake data?

9\. Does it preserve forms and actions?

10\. Is the diff reviewable?



If not, revise.



\## Anti-patterns to remove



Look for and remove when safe:



\- legacy brown/olive palettes when not part of current hotel branding

\- random mixed button colors

\- debug UI

\- console logs

\- fake KPIs

\- empty states that say nothing useful

\- hardcoded hotel names

\- inconsistent badges

\- identical styling for different operational states

\- oversized cards with weak hierarchy

\- tables with no scannability

\- mobile overflow

\- unclear primary actions

\- dangerous actions styled like normal actions



\## Responsive expectations



For every visual change, consider:



\- desktop

\- tablet

\- mobile

\- touch targets

\- horizontal overflow

\- cards stacking

\- tables becoming cards if necessary

\- sidebar behavior

\- modal usability



Never claim responsive is good without checking or explaining the limitation.



\## Work strategy



Prefer working by module:



1\. Audit visually.

2\. Identify the weakest visual problems.

3\. Decide the safest high-impact changes.

4\. Modify only the smallest necessary files.

5\. Validate.

6\. Report honestly.

7\. Stop before the diff becomes too large.



Do not redesign everything in one uncontrolled pass.



\## Safe implementation areas



You may touch:



\- view files

\- CSS/classes

\- visual markup

\- labels/copy

\- badges

\- cards

\- button classes

\- table layout

\- responsive classes

\- empty states

\- icons if already available



Be careful with:

\- inline JS

\- modals

\- form markup

\- controller-provided data

\- anything that changes behavior



\## Required validation



Always run or report why you could not run:



```bash

git diff --check

git diff --name-only

git status --short

