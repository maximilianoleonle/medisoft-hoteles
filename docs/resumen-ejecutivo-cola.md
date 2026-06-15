# Resumen ejecutivo - cola autonoma

## Reanclaje Fase 3C

Estado vigente: `VALIDACIONES_3C_C_COMPLETADAS`.

Motivo: despues del reanclaje, se formalizo 3C-A primero y luego se implemento 3C-B como generacion manual controlada desde compra recibida. El usuario reporto QA manual completada para 3C-A y 3C-B. Fase 3C-C queda dedicada a validaciones read-only en health/preflights y SQL de consistencia, sin pagos, abonos, Caja ni `/api/sync`.

Estado formal vigente:

- Fase 3C-0 contrato y diagnostico: completada.
- Fase 3C-A simulador read-only: completada tecnicamente, commiteada y validada manualmente.
- Fase 3C-B generacion manual: completada tecnicamente, commiteada y validada manualmente.
- Fase 3C-C validaciones/health/preflights: completada tecnicamente como verificacion read-only.
- Revision tecnica `8765258` y auditoria `2662998`: reclasificadas como revision/auditoria prematuras o documentales; no cierran Fase 3C.
- Siguiente accion recomendada: revision tecnica/auditoria de cierre 3C si el usuario la autoriza.

## Estado final del bloque autorizado anterior

Estado: `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`.

El bloque Fase 2X-3B queda como bloque cerrado anterior; Fase 3C queda con 3C-A y 3C-B completadas y validadas manualmente.

## Fases y commits

- Fase 2X: `d1f1431` - detalle read-only de compra recibida.
- Fase 2Y: `32abb7b` - reporte read-only de compras recibidas.
- Fase 2Z: `052fd7a` - endurecimiento de recepcion de compras.
- Fase 3A: `3d8f997` - ficha read-only de proveedor.
- Fase 3B draft: `673f47f` - borrador CxP base.
- Fase 3B aplicada: `1fa1653` - CxP base read-only.
- Cierre/auditoria: `2535dd1`, `ca2bda4`.
- Cierre tecnico final: `8a49995`.
- Ajuste visual reservaciones post-QA: `dc3c150`.

## Nuevo bloque Fase 3C

Objetivo: generar CxP manualmente desde compras recibidas, sin Caja ni pagos.

Estado actual:

- Fase 3C-0 completada con contrato y diagnostico.
- Fase 3C-A completada tecnicamente como simulador read-only: ruta GET, vista, controller, modelo, navegacion desde CxP y guardas existentes.
- Fase 3C-B completada tecnicamente: POST manual con CSRF, validaciones centrales, auditoria y bloqueo de duplicados.
- Fase 3C-C completada tecnicamente: health/preflights validan duplicados, relaciones, saldos, fechas, ausencia de movimientos CxP, ausencia de Caja y ausencia de pagos/abonos.
- SQL read-only 3C-C confirmo `cuentas_por_pagar=2`, `cuentas_por_pagar_movimientos=0`, inconsistencias CxP=0, Caja-CxP=0 y `compra_pagos` inexistente.
- Revision tecnica y auditoria post-QA fueron prematuras respecto al nuevo reanclaje.
- QA manual 3C-A/3C-B reportada por el usuario: preview OK, CxP #2 vinculada, detalle CxP OK, origen compra/proveedor visible, sin pagos, sin abonos y sin Caja.
- Siguiente fase recomendada: cierre tecnico/revision de 3C; no pagos, Caja ni Fase 3D.
- No se implementaron pagos ni Caja.
- No avanzar a pagos, Caja ni Fase 3D.

## Situacion historica

El historial contiene commits que implementan partes de Fase 3C, pero el estado documental vigente no debe tratarlos como cierre formal completo.

Las pruebas locales de 3C-B crearon CxP controladas desde compras recibidas (`id=1` para compra `#5` historica y `id=2` para compra `#2` vigente). Esos datos no deben borrarse ni corregirse automaticamente.

## Alcance de Fase 3B

Se permite:

- consultar CxP;
- listar cuentas por pagar;
- ver detalle de una cuenta por pagar;
- preparar checkers y documentacion;
- exponer navegacion basica.

No se permite:

- pagar proveedores;
- tocar Caja;
- generar CxP automaticamente desde compras;
- cambiar calculos financieros;
- modificar `/api/sync`;
- avanzar a Fase 3C.

## Estado tecnico auditado

- Codigo CxP read-only.
- Rutas GET solamente.
- Vistas sin formularios de pago.
- Health/preflight compatibles.
- Documentacion de metodologia vigente.
- Commit selectivo de cierre: `1fa1653 feat(phase-3b): add read-only accounts payable foundation`.
- `src/app/views/reservaciones/ver.php` queda fuera por no pertenecer al bloque.

## Decisiones clave

- CxP entra como fundacion, no como modulo operativo financiero.
- Caja queda intacta.
- Compras no genera CxP todavia.
- Las tablas duplicadas/legacy siguen congeladas.
- La siguiente accion despues del commit es revision manual, no nueva feature.

## Estado de riesgo

Riesgo naranja por tocar estructuras financieras de DB, mitigado por:

- backup previo;
- tablas vacias;
- implementacion read-only;
- sin integracion con Caja;
- sin pagos;
- verificaciones automaticas.

## Cierre tecnico post-commit

- CxP 3C-B tiene un unico POST manual con CSRF desde compra recibida elegible.
- CxP sigue sin integracion con Caja.
- Compras y proveedores no generan CxP automaticamente.
- Las consultas revisadas mantienen filtros por `hotel_id`.
- `POST /cuentas-por-pagar/generar-desde-compra/{id}` sin sesion redirige a login.
- `/api/sync` sigue fuera de alcance y bloqueado segun checker.

## Auditoria de seguridad

- Resultado: sin hallazgos bloqueantes.
- Perdida de datos: no detectada; no hay borrado ni migracion destructiva.
- Doble recepcion: protegida por contrato transaccional y validaciones de estado/detalles.
- Inventario: se mantiene fuente moderna `inventario_productos` + `movimientos_inventario`.
- CxP: en 3C-B permite generacion manual; no hay pagos, abonos ni movimientos de Caja.
- Riesgo residual: futuras escrituras CxP deben validar estrictamente `hotel_id` de proveedor/compra.
- 3C-C agrega validacion automatica para duplicados, compras/proveedores inexistentes, cruces de hotel, compras no recibidas, saldos/totales invalidos, fechas faltantes y referencias CxP en Caja.
- Auditoria corregida confirma: unico POST con CSRF, guardas de autenticacion/contexto/modulo, sin escrituras en Caja/pagos y sin cambios en `/api/sync`.
- Verificacion actual: Docker disponible; `php -l`, health, preflights, POST sin sesion, backup, doble generacion y conteos DB antes/despues ejecutados.

## Pendiente antes de avanzar

- No avanzar a pagos, Caja ni Fase 3D sin nuevo mensaje real o cola especifica.
- La siguiente accion recomendada es revision tecnica/auditoria de cierre de 3C, si se autoriza explicitamente.

## Nuevo bloque Personal y Nomina (Fase NP)

Objetivo: modulo INDEPENDIENTE de trabajadores con ledger laboral, saldos por persona,
asistencia y comisiones, multi-hotel, SIN integracion con Caja ni salida real de dinero.

Estado actual:

- Fase NP-0 completada: contrato, diagnostico read-only y diseno aditivo de 6 tablas.
- HEAD al iniciar NP-0: `5dfe665`; Git limpio.
- Hoy "trabajador" = `usuarios` + `hotel_usuarios`; sin rol laboral, deuda ni saldo por persona.
- Caja revisada en solo lectura; NO existe categoria "Nomina"; movimientos Caja-nomina: 0.
- No existe ninguna tabla `trabajador*`: el bloque es 100% aditivo.
- No se implemento funcionalidad ni se escribio en DB en NP-0.

Riesgo: naranja (modulo financiero-laboral nuevo y concepto sensible), mitigado por
migraciones aditivas/reversibles, sin Caja, sin tocar `usuarios` destructivamente,
filtro `hotel_id` y validaciones fuertes.

Subfases planificadas: NP-A (ficha basica), NP-B (pagos/anticipos/prestamos sin Caja),
NP-C (saldos y reportes), NP-D (asistencia y comisiones), NP-E (validaciones/health),
NP-F (cierre). Contrato: `docs/fase_NP_0_contrato_diagnostico.md`.
