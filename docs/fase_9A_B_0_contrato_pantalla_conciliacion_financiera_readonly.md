# Fase 9A-B-0 - Contrato pantalla conciliacion financiera read-only

## Estado

`CONTRATO_9A_B_0_PANTALLA_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`

## Objetivo

Definir la futura pantalla operativa de conciliacion financiera solo lectura,
apoyada en el preflight 9A-A, sin implementar todavia rutas, controladores, modelos,
vistas ni acciones.

## Contexto

Ya existe una herramienta CLI read-only:

- `src/tools/saas/preflight_conciliacion_financiera.php`;
- resultado validado: `OK: 75`, `WARNING: 0`, `ERROR: 0`;
- cruza CxC, CxP, Caja, cortes, cajas y auditoria;
- no corrige datos.

La pantalla futura debe ser una traduccion visual de ese diagnostico, no una
superficie de operacion financiera.

## Superficie futura propuesta

Ruta candidata:

```text
GET /operacion/conciliacion-financiera
```

La ruta no se implementa en esta fase. Cualquier implementacion futura debe abrir una
fase 9A-B-A separada.

## Alcance permitido para 9A-B-A futura

La pantalla podra mostrar, siempre filtrado por hotel actual:

- resumen general:
  - CxC abiertas;
  - saldo CxC pendiente;
  - CxP abiertas;
  - saldo CxP pendiente;
  - movimientos Caja vinculados a CxC;
  - movimientos Caja vinculados a CxP;
  - alertas criticas;
  - warnings informativos.
- conciliacion CxC/Caja:
  - cobros con ingreso Caja asociado;
  - cobros sin ingreso Caja;
  - ingresos Caja sin movimiento CxC;
  - reversiones con gasto Caja asociado;
  - gastos Caja sin cancelacion CxC;
  - doble reversion.
- conciliacion CxP/Caja:
  - pagos proveedor con gasto Caja asociado;
  - pagos proveedor sin gasto Caja;
  - gastos Caja sin movimiento CxP;
  - reversiones con ingreso Caja asociado;
  - ingresos Caja sin cancelacion CxP;
  - doble reversion.
- conciliacion Caja:
  - movimientos con corte abierto;
  - movimientos con corte cerrado;
  - movimientos sin corte valido;
  - referencias duplicadas;
  - categoria/tipo incompatible;
  - hotel/corte/caja cruzado.
- auditoria:
  - cobros CxC con log esperado;
  - reversiones CxC con log esperado;
  - pagos proveedor con log esperado;
  - reversiones proveedor con log esperado.

## Filtros GET permitidos

Solo se permiten filtros por query string:

- `fecha_desde`;
- `fecha_hasta`;
- `tipo`: `todos`, `cxc`, `cxp`, `caja`, `auditoria`;
- `severidad`: `todos`, `ok`, `warning`, `error`;
- `corte_id`;
- `referencia`;
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
- mostrar cards/resumen y tablas de alertas;
- diferenciar `OK`, `WARNING` y `ERROR` sin ocultar errores;
- enlazar solo a detalles existentes:
  - CxC operativa;
  - CxP;
  - cortes o Caja si ya existe enlace seguro;
  - reservacion/compra/proveedor solo como contexto.

La pantalla futura no debe:

- mostrar botones de `corregir`;
- mostrar botones de `ajustar`;
- mostrar botones de `pagar`;
- mostrar botones de `cobrar`;
- mostrar botones de `revertir`;
- mostrar botones de `compensar`;
- mostrar SQL interno;
- mostrar rutas de storage;
- usar modales de confirmacion operativa;
- incluir formularios POST.

## Reglas de backend

La implementacion futura debe:

- usar un lector/read model dedicado o consultas read-only;
- filtrar siempre por `hotel_id` de sesion;
- reutilizar las reglas conceptuales del preflight 9A-A;
- mantener paginacion en listados grandes;
- ordenar alertas por severidad y fecha;
- no registrar auditoria por lectura simple;
- no llamar servicios de cobro, pago, reversion, caja, corte ni reservacion;
- no recalcular cortes;
- no actualizar saldos;
- no intentar autocorrecciones.

## Prohibiciones absolutas

9A-B y cualquier implementacion derivada no pueden:

- crear, editar o borrar CxC;
- crear, editar o borrar CxP;
- crear, editar o borrar movimientos de Caja;
- abrir, cerrar, cancelar o recalcular cortes;
- editar cajas;
- modificar saldos;
- crear pagos, cobros, abonos, ajustes, cancelaciones o reversiones;
- tocar reservaciones, compras, proveedores, facturacion o documentos;
- tocar permisos, roles o auth;
- tocar PWA/offline, IndexedDB, cache names ni `/api/sync`;
- ejecutar `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP` ni `TRUNCATE`.

## Criterios de aceptacion futuros

Una futura 9A-B-A solo se considerara completa si:

- agrega una unica ruta GET;
- no agrega rutas POST;
- no modifica datos;
- no toca migraciones;
- pasa `php -l` en archivos PHP modificados;
- el preflight 9A-A queda en `ERROR: 0`;
- health general queda en `ERROR: 0`;
- `git diff --check` no reporta errores;
- la pantalla sin sesion redirige o bloquea segun el patron del sistema;
- la pantalla con sesion muestra resumen, filtros GET y alertas sin acciones
  operativas;
- se documenta QA manual.

## QA futura recomendada

1. Abrir la pantalla sin sesion y confirmar redireccion/bloqueo.
2. Abrir con sesion de hotel.
3. Confirmar que solo muestra datos del hotel actual.
4. Confirmar filtros GET.
5. Confirmar que no hay POST ni botones operativos.
6. Confirmar que CxC/CxP/Caja coinciden con el preflight 9A-A.
7. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
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

La siguiente fase posible es 9A-B-A: implementar la pantalla GET/read-only definida
en este contrato, manteniendo el preflight 9A-A como verificacion previa y posterior.
