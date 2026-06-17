# Fase 8A-F - Cierre tecnico dashboard operativo KPIs

## Estado

`BLOQUE_8A_DASHBOARD_KPIS_CERRADO_QA_DIFERIDA`

## Alcance cerrado

- 8A-0 contrato de dashboard operativo con KPIs nuevos.
- 8A-A KPIs read-only en `/operacion/diaria`.

## Commits del bloque

- `cfdbd64 docs(phase-8a): define operational dashboard kpi contract`
- `68c8714 feat(phase-8a): add read-only operational kpis`

## Confirmaciones tecnicas

- No se creo dashboard paralelo.
- No se agregaron rutas nuevas.
- `/operacion/diaria` sigue siendo GET/read-only.
- `OperacionDiaria` agrega KPIs CxC estimados filtrados por `hotel_id`.
- La vista no contiene formularios ni POST.
- No hay cobros, pagos, abonos, Caja ni `/api/sync`.
- Los KPIs financieros se etiquetan como estimados.

## Verificacion registrada

- `php -l app/models/OperacionDiaria.php`: OK.
- `php -l app/views/operacion/diaria.php`: OK.
- `php -l tools/saas/preflight_operacion_diaria.php`: OK.
- `preflight_operacion_diaria.php`: `OK: 32`, `WARNING: 0`, `ERROR: 0`.
- `preflight_cuentas_por_cobrar.php`: `ERROR: 0`, warnings historicos esperados.
- `health_check_fase_1a.php`: `ERROR: 0`.
- HTTP sin sesion a `/operacion/diaria`: `303` a login.
- Navegador local: `/operacion/diaria` redirige a `/login` sin sesion.
- `git diff --check`: sin errores, solo warnings CRLF.

## QA manual diferida

Queda pendiente porque el usuario pidio omitir QA manual temporalmente.

Checklist:

1. Abrir `/operacion/diaria` con sesion hotelera.
2. Confirmar tarjetas CxC estimada y CxC pendientes.
3. Confirmar panel de KPIs financieros estimados.
4. Confirmar enlace a `/cuentas-por-cobrar`.
5. Confirmar ausencia de botones de accion financiera.
6. Confirmar que la vista no se rompe en movil.

## Riesgos residuales

- Los saldos CxC siguen siendo estimados.
- Warnings historicos de 7A siguen bloqueando CxC operativa.
- No avanzar a Caja/pagos sin contrato y QA manual especifica.

## Rollback

1. Revertir `68c8714`.
2. Si se requiere retirar solo documentacion, revertir este cierre.
3. No tocar datos, Caja ni `/api/sync`.

## Siguiente accion segura

El siguiente bloque del roadmap es `3D` pagos proveedores con Caja, pero es de alto
riesgo. La unica accion segura inmediata es contrato/diagnostico 3D-0 con backup plan,
sin implementar pagos todavia.
