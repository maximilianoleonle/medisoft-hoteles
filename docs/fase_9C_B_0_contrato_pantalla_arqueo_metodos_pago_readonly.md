# Fase 9C-B-0 - Contrato pantalla arqueo por corte y metodo read-only

## Estado

`CONTRATO_9C_B_0_PANTALLA_ARQUEO_METODOS_PAGO_READONLY_COMPLETADO`

## Objetivo

Definir la futura pantalla operativa de arqueo por corte y metodo de pago en modo
solo lectura, apoyada en el preflight 9C-A.

La pantalla debe traducir visualmente el diagnostico de Caja por corte/metodo sin
cerrar cortes, recalcular importes, editar movimientos ni modificar saldos.

## Contexto

Ya existe una herramienta CLI read-only:

- `src/tools/saas/preflight_arqueo_metodos_pago.php`;
- resultado validado: `OK: 34`, `WARNING: 2`, `ERROR: 0`;
- lee `movimientos_caja`, `cortes_caja`, `cajas` y `categorias_movimientos`;
- detecta integridad de corte/metodo y diferencias historicas;
- no corrige datos.

Warnings actuales esperados:

- `4` cortes cerrados con totales guardados distintos a movimientos;
- `1` corte cerrado con `efectivo_esperado` distinto a formula guardada.

Estos warnings son diagnosticos historicos. La pantalla futura no debe intentar
corregirlos.

## Superficie futura propuesta

Ruta candidata:

```text
GET /caja/arqueo-metodos
```

La ruta no se implementa en esta fase. Cualquier implementacion futura debe abrir una
fase 9C-B-A separada.

## Alcance permitido para 9C-B-A futura

La pantalla podra mostrar, siempre filtrado por hotel actual:

- resumen general:
  - cortes totales;
  - cortes abiertos;
  - cortes cerrados;
  - movimientos Caja totales;
  - ingresos, gastos y neto por movimientos;
  - metodos distintos;
  - alertas criticas;
  - warnings historicos.
- resumen por metodo:
  - ingresos efectivo;
  - gastos efectivo;
  - neto efectivo;
  - ingresos tarjeta;
  - gastos tarjeta;
  - neto tarjeta;
  - ingresos transferencia;
  - gastos transferencia;
  - neto transferencia.
- cortes cerrados:
  - totales guardados por metodo;
  - sumas derivadas de movimientos;
  - diferencia por metodo;
  - efectivo esperado guardado;
  - efectivo esperado calculado;
  - diferencia registrada;
  - estado de alerta.
- cortes abiertos:
  - acumulados vivos por metodo;
  - neto actual;
  - total de movimientos;
  - etiqueta clara de datos vivos/no cerrados.
- movimientos anomalos:
  - movimiento sin metodo;
  - movimiento sin corte;
  - movimiento con corte inexistente o de otro hotel;
  - metodo fuera de enum;
  - monto no positivo;
  - tipo invalido.

## Filtros GET permitidos

Solo se permiten filtros por query string:

- `fecha_desde`;
- `fecha_hasta`;
- `corte_id`;
- `caja_id`;
- `estado_corte`: `todos`, `abierto`, `cerrado`, `cancelado`;
- `metodo_pago`: `todos`, `efectivo`, `tarjeta`, `transferencia`;
- `severidad`: `todos`, `ok`, `warning`, `error`;
- `page`;
- `limit`.

El `hotel_id` no debe recibirse desde query string para cambiar contexto. Debe venir
exclusivamente de la sesion/hotel actual.

## Reglas de UI

La pantalla futura debe:

- usar branding del hotel cliente y tokens `--brand-*`;
- no usar tokens `--ms-*`;
- mostrar etiqueta visible `Solo lectura`;
- mostrar fecha/hora de consulta;
- mostrar filtros GET sin acciones POST;
- diferenciar cortes abiertos de cortes cerrados;
- diferenciar `OK`, `WARNING` y `ERROR` sin ocultar warnings historicos;
- mostrar warnings como diagnostico;
- enlazar solo a vistas existentes y seguras:
  - detalle de corte;
  - movimientos de Caja;
  - reporte de metodos existente si aplica.

La pantalla futura no debe:

- mostrar botones de `cerrar corte`;
- mostrar botones de `reabrir corte`;
- mostrar botones de `recalcular`;
- mostrar botones de `corregir`;
- mostrar botones de `ajustar`;
- mostrar botones de `compensar`;
- mostrar botones de `editar movimiento`;
- mostrar botones de `cambiar metodo`;
- mostrar SQL interno;
- mostrar rutas de storage;
- usar modales de confirmacion operativa;
- incluir formularios POST.

## Reglas de backend

La implementacion futura debe:

- usar un lector/read model dedicado o consultas read-only;
- filtrar siempre por `hotel_id` de sesion;
- reutilizar reglas conceptuales de `preflight_arqueo_metodos_pago.php`;
- mantener paginacion en listados grandes;
- ordenar alertas por severidad, corte y fecha;
- no registrar auditoria por lectura simple;
- no llamar `Caja::cerrarCaja()`;
- no llamar acciones POST de Caja;
- no recalcular ni persistir totales de corte;
- no actualizar movimientos;
- no intentar autocorrecciones.

## Prohibiciones absolutas

9C-B y cualquier implementacion derivada no pueden:

- abrir cortes;
- cerrar cortes;
- reabrir cortes;
- cancelar cortes;
- recalcular cortes;
- editar cajas;
- crear, editar o borrar movimientos de Caja;
- cambiar metodo de pago;
- cambiar categorias;
- modificar saldos;
- crear pagos, cobros, abonos, ajustes, cancelaciones o reversiones;
- tocar CxC, CxP, reservaciones, compras, proveedores, facturacion o documentos;
- tocar permisos, roles o auth;
- tocar PWA/offline, IndexedDB, cache names ni `/api/sync`;
- ejecutar `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP` ni `TRUNCATE`.

## Criterios de aceptacion futuros

Una futura 9C-B-A solo se considerara completa si:

- agrega una unica ruta GET o reutiliza una ruta GET existente de forma compatible;
- no agrega rutas POST;
- no modifica datos;
- no toca migraciones;
- pasa `php -l` en archivos PHP modificados;
- `preflight_arqueo_metodos_pago.php` queda con `ERROR: 0`;
- health general queda con `ERROR: 0`;
- `git diff --check` no reporta errores;
- la pantalla sin sesion redirige o bloquea segun patron del sistema;
- la pantalla con sesion muestra resumen, filtros GET y alertas sin acciones
  operativas;
- se documenta QA manual.

## QA futura recomendada

1. Abrir la pantalla sin sesion y confirmar redireccion/bloqueo.
2. Abrir con sesion de hotel.
3. Confirmar etiqueta `Solo lectura`.
4. Confirmar que solo muestra datos del hotel actual.
5. Confirmar filtros GET.
6. Confirmar que cortes abiertos se muestran como datos vivos.
7. Confirmar que warnings historicos coinciden con el preflight 9C-A.
8. Confirmar que no hay POST ni botones operativos.
9. Confirmar que navegar/filtrar no cambia cortes ni movimientos.
10. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

## Resultado de esta fase

Esta fase es solo contrato documental.

No agrega:

- codigo PHP;
- rutas;
- controladores;
- modelos;
- vistas;
- formularios;
- migraciones;
- escrituras de DB.

## Siguiente accion segura

La siguiente fase posible es 9C-B-A: implementar la pantalla GET/read-only definida en
este contrato, manteniendo `preflight_arqueo_metodos_pago.php` como verificacion previa
y posterior.
