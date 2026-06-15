# Resumen ejecutivo - cola autonoma

## Estado final del bloque autorizado

Estado: `CIERRE_TECNICO_COMPLETADO_QA_MANUAL_PENDIENTE`.

El bloque Fase 2X-3B queda tecnicamente cerrado y el usuario reporto QA manual realizada.

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
- Fase 3C-A implementa preview/simulador.
- Fase 3C-B implementa generacion manual controlada con POST + CSRF.
- Fase 3C-C refuerza health/preflights con consistencia CxP y deteccion de Caja relacionada.
- No se implementaron pagos ni Caja.
- CxP puede crearse solo desde compra recibida elegible.
- Compras recibidas elegibles antes de la prueba local: 2.
- Prueba local generada: compra `#5` -> CxP `#1`.
- CxP actuales despues de prueba local: 1.
- Movimientos de Caja relacionados con CxP: 0.

## Situacion

Medisoft Hoteles cerro Fase 3B aplicada: una base tecnica read-only para cuentas por pagar.

La fase se apoya en una migracion local ya aplicada sobre `medisoft_hoteles_import`, con backup previo confirmado. Las tablas nuevas existen y deben permanecer vacias hasta que una fase posterior autorice generacion de saldos.

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

- CxP sigue sin acciones POST.
- CxP sigue sin integracion con Caja.
- Compras y proveedores no generan ni escriben CxP.
- Las consultas revisadas mantienen filtros por `hotel_id`.
- `/cuentas-por-pagar` sin sesion redirige a login.
- `/api/sync` sigue fuera de alcance y bloqueado segun checker.

## Auditoria de seguridad

- Resultado: sin hallazgos bloqueantes.
- Perdida de datos: no detectada; no hay borrado ni migracion destructiva.
- Doble recepcion: protegida por contrato transaccional y validaciones de estado/detalles.
- Inventario: se mantiene fuente moderna `inventario_productos` + `movimientos_inventario`.
- CxP: se mantiene read-only y vacio.
- Riesgo residual: futuras escrituras CxP deben validar estrictamente `hotel_id` de proveedor/compra.
- 3C-C agrega validacion automatica para duplicados, compras/proveedores inexistentes, cruces de hotel, compras no recibidas, saldos/totales invalidos, fechas faltantes y referencias CxP en Caja.

## Pendiente antes de avanzar

- QA manual autenticada de listado/detalle CxP, preview 3C-A y generacion manual 3C-B.
- QA visual/regresion de compras, proveedores y recepcion.
- No avanzar a pagos/Caja sin nueva autorizacion.

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
