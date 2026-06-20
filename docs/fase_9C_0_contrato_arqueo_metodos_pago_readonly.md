# Fase 9C-0 - Contrato arqueo por corte y metodo read-only

## Estado

`CONTRATO_9C_0_ARQUEO_METODOS_PAGO_READONLY_COMPLETADO`

## Objetivo

Definir el siguiente bloque financiero seguro despues de la conciliacion 9A-B:
un diagnostico de arqueo por corte y metodo de pago, en modo solo lectura.

El objetivo es comparar lo registrado en `movimientos_caja` contra los totales por
corte y metodo, detectando diferencias sin cerrar cortes, recalcular importes ni
modificar movimientos.

## Contexto

El sistema ya cuenta con bases relacionadas:

- ruta existente `GET /caja/reporte-metodos`;
- `movimientos_caja.metodo_pago`;
- `movimientos_caja.corte_id`;
- `cortes_caja` con totales por metodo al cerrar corte;
- vistas de corte e historial de Caja;
- conciliacion financiera 9A-B cerrada como pantalla read-only.

Lectura local inicial:

- cortes totales: `223`;
- cortes abiertos: `3`;
- movimientos de Caja: `1409`;
- metodos distintos en movimientos: `3`;
- movimientos sin metodo: `0`;
- movimientos sin corte: `0`.

Estos conteos son diagnostico, no autorizan cambios.

## Alcance permitido para 9C-A futura

Una implementacion futura 9C-A puede ser:

- preflight CLI read-only; o
- pantalla GET/read-only apoyada en Caja; o
- ambos, si primero se valida el preflight.

Debe mostrar, siempre filtrado por hotel actual:

- cortes abiertos y cerrados;
- caja asociada al corte;
- usuario de apertura/cierre como contexto;
- ingresos por metodo;
- gastos por metodo;
- neto por metodo;
- efectivo esperado;
- diferencia registrada en corte cerrado;
- movimientos sin metodo;
- movimientos sin corte valido;
- cortes cerrados donde los totales guardados no coinciden con movimientos;
- cortes abiertos con actividad financiera relevante;
- metodos usados fuera del catalogo permitido, si existe catalogo formal.

## Fuentes permitidas

La fase futura solo puede leer:

- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `usuarios`, solo para mostrar usuario de apertura/cierre;
- `categorias_movimientos`, solo como contexto de categoria;
- `hoteles`, solo para scope y nombre del hotel actual;
- `logs_auditoria`, solo para verificar trazabilidad si se requiere.

Tambien puede enlazar, solo como contexto read-only, a:

- detalle de corte existente;
- reporte de metodos existente;
- movimientos de Caja existentes.

## Filtros GET permitidos

Si se implementa una pantalla:

- `fecha_desde`;
- `fecha_hasta`;
- `corte_id`;
- `caja_id`;
- `estado_corte`;
- `metodo_pago`;
- `severidad`;
- `page`;
- `limit`.

El `hotel_id` no debe recibirse desde query string para cambiar contexto.

## Reglas de comparacion futuras

Para cortes cerrados:

- sumar ingresos por metodo desde `movimientos_caja`;
- sumar gastos por metodo desde `movimientos_caja`;
- comparar con los campos guardados en `cortes_caja`;
- reportar diferencia por metodo y diferencia total;
- no recalcular ni corregir el corte.

Para cortes abiertos:

- mostrar acumulados actuales por metodo;
- advertir que son valores vivos;
- no cerrar ni prellenar cierre desde la pantalla 9C.

Para movimientos:

- detectar movimiento sin `metodo_pago`;
- detectar movimiento sin `corte_id`;
- detectar movimiento cuyo corte pertenece a otro hotel;
- detectar metodo no reconocido si existe catalogo formal;
- detectar montos negativos o cero en movimientos financieros.

## Prohibiciones

9C no puede:

- abrir cortes;
- cerrar cortes;
- reabrir cortes;
- recalcular cortes;
- actualizar totales guardados en `cortes_caja`;
- crear, editar o borrar movimientos de Caja;
- cambiar metodo de pago de movimientos;
- cambiar categorias;
- crear pagos, cobros, abonos, ajustes o reversiones;
- tocar CxC, CxP, reservaciones, compras, proveedores o facturacion;
- tocar permisos, roles o auth;
- tocar PWA/offline, IndexedDB, cache names ni `/api/sync`;
- ejecutar `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP` ni `TRUNCATE`.

## Reglas de UI futura

La pantalla futura debe:

- usar identidad del hotel con tokens `--brand-*`;
- no usar tokens `--ms-*`;
- mostrar etiqueta visible `Solo lectura`;
- usar filtros GET;
- mostrar diferencias como alertas, no como acciones;
- enlazar solo a vistas existentes y seguras;
- diferenciar cortes abiertos de cortes cerrados.

La pantalla futura no debe mostrar botones de:

- cerrar corte;
- recalcular;
- corregir;
- ajustar;
- compensar;
- editar movimiento;
- cambiar metodo.

## Backend futuro

Si se implementa modelo o preflight:

- usar transaccion read-only cuando aplique;
- filtrar por `hotel_id`;
- no llamar `Caja::cerrarCaja()`;
- no llamar acciones POST de Caja;
- no registrar auditoria por simple lectura;
- no escribir en tablas de Caja ni financieras;
- mantener paginacion en listados grandes.

## Criterios de aceptacion futuros

9C-A solo se considerara completa si:

- no agrega POST;
- no modifica datos;
- pasa `php -l` en PHP tocado;
- health general queda con `ERROR: 0`;
- preflight financiero relevante queda con `ERROR: 0`;
- HTTP sin sesion bloquea o redirige segun patron del sistema;
- `git diff --check` no reporta errores;
- QA manual confirma que filtrar/navegar no cambia cortes ni movimientos.

## Rollback futuro

Si una futura implementacion 9C-A agrega codigo:

1. Retirar ruta GET o preflight nuevo.
2. Retirar modelo/vista/controlador agregado.
3. Retirar referencias documentales 9C-A.
4. No ejecutar SQL de correccion.

Si 9C-0 se revierte, solo retirar este documento y sus referencias acumulativas.

## Siguiente accion segura

La siguiente accion segura es 9C-A como preflight CLI/read-only de arqueo por corte y
metodo antes de cualquier pantalla nueva.
