# Fase 10A-0 - Contrato tablero ejecutivo integral read-only

## Estado

`CONTRATO_10A_0_TABLERO_EJECUTIVO_READONLY_COMPLETADO`

## Objetivo

Definir el siguiente bloque seguro despues de la conciliacion financiera 9A y el
arqueo por metodo 9C: un tablero ejecutivo integral en modo solo lectura.

El tablero debe unir KPIs operativos, financieros y administrativos ya existentes para
que el hotel pueda ver el estado del negocio sin crear pagos, cobros, ajustes,
correcciones, cierres ni automatizaciones.

## Contexto

El sistema ya cuenta con piezas maduras o en avance:

- Dashboard operativo actual: `GET /dashboard`.
- Tablero operativo diario: `GET /operacion/diaria`.
- Conciliacion financiera: `GET /operacion/conciliacion-financiera`.
- Arqueo por metodo: `GET /caja/arqueo-metodos`.
- Reporte gerencial diario existente: `GET /reportes/gerencial-diario`.
- CxC operativa y simulador/cobro/reversion controlados.
- CxP operativa y simulador/pago/reversion controlados.
- Compras recibidas, inventario moderno, tareas, mantenimiento, limpieza, personal y
  documentos.

Observacion importante:

- `GET /reportes/gerencial-diario` existe, pero actualmente puede marcar
  notificaciones como vistas.
- Una futura 10A read-only no debe depender de escrituras por lectura simple, salvo
  que esa escritura se separe en un contrato de notificaciones independiente.

## Superficie futura propuesta

Ruta candidata:

```text
GET /reportes/ejecutivo
```

La ruta no se implementa en esta fase. Cualquier implementacion futura debe abrir una
fase posterior separada. Tras el preflight 10A-A, la pantalla futura queda reservada
para 10A-B.

## Alcance permitido para pantalla futura

Una futura implementacion puede mostrar, siempre filtrado por hotel actual:

- resumen ejecutivo:
  - ingresos del periodo;
  - gastos del periodo;
  - neto estimado;
  - saldo CxC pendiente;
  - saldo CxP pendiente;
  - cortes abiertos;
  - alertas financieras.
- operacion hotelera:
  - ocupacion actual;
  - habitaciones ocupadas, disponibles, limpieza y mantenimiento;
  - check-ins y check-outs del dia;
  - reservaciones pendientes, confirmadas y vencidas.
- Caja y finanzas:
  - movimientos por metodo;
  - cortes con warnings de arqueo;
  - cobros CxC del periodo;
  - pagos CxP del periodo;
  - reversiones del periodo;
  - diferencias o alertas de conciliacion.
- Inventario:
  - productos bajo minimo;
  - productos sin movimiento reciente;
  - compras recibidas del periodo;
  - entradas y salidas de inventario.
- Personal y tareas:
  - trabajadores activos;
  - ledger laboral pendiente;
  - tareas activas;
  - tareas vencidas;
  - tareas por responsable.
- Documentos:
  - documentos recientes;
  - documentos archivados;
  - documentos por entidad;
  - posibles pendientes de metadata.

## Fuentes permitidas

La fase futura solo puede leer:

- `reservaciones`;
- `reservacion_habitaciones`;
- `habitaciones`;
- `tipos_habitacion`;
- `huespedes`;
- `cuentas_por_cobrar`;
- `cuentas_por_cobrar_movimientos`;
- `cuentas_por_pagar`;
- `cuentas_por_pagar_movimientos`;
- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `compras`;
- `compra_detalles`;
- `proveedores`;
- `inventario_productos`;
- `movimientos_inventario`;
- `tareas_operativas`;
- `tarea_eventos`;
- `trabajadores`;
- `ledger_laboral`, si existe con ese nombre en el entorno;
- `documentos`;
- `documento_entidades`;
- `logs_auditoria`, solo como resumen read-only;
- `hoteles`, solo para scope y nombre del hotel actual.

Si alguna tabla no existe o tiene otro nombre, el lector futuro debe degradar con
`schema_ok=false` o alertas de disponibilidad, no crear tablas ni migraciones.

## Filtros GET permitidos

- `fecha_desde`;
- `fecha_hasta`;
- `fecha`;
- `periodo`: `hoy`, `7d`, `30d`, `mes`, `custom`;
- `severidad`: `todos`, `ok`, `warning`, `error`;
- `area`: `todas`, `operacion`, `finanzas`, `inventario`, `personal`, `documentos`;
- `page`;
- `limit`.

El `hotel_id` no debe recibirse desde query string para cambiar contexto. Debe venir
de la sesion/hotel actual.

## Reglas de UI futura

La pantalla futura debe:

- usar identidad del hotel cliente y tokens `--brand-*`;
- no usar tokens `--ms-*`;
- mostrar etiqueta visible `Solo lectura`;
- usar filtros GET;
- diferenciar KPIs, alertas y pendientes;
- enlazar solo a vistas existentes y seguras;
- mostrar datos incompletos como advertencias, no como errores fatales;
- dejar claro cuando un importe es estimado o derivado de fuentes historicas.

La pantalla futura no debe:

- incluir formularios POST;
- mostrar botones de pagar, cobrar, revertir, cerrar corte, recalcular, corregir,
  ajustar, compensar, editar movimiento o cambiar metodo;
- marcar notificaciones como vistas automaticamente;
- generar PDFs, exports o links publicos sin contrato separado;
- mostrar SQL interno;
- mostrar rutas de storage.

## Reglas de backend futuro

La implementacion futura debe:

- usar un lector/read model dedicado o servicio read-only;
- filtrar siempre por `hotel_id` de sesion;
- usar transaccion read-only cuando aplique;
- no llamar servicios de pago, cobro, reversion, cierre de caja ni recepcion de compra;
- no recalcular ni persistir totales;
- no registrar auditoria por lectura simple;
- no ejecutar escrituras por abrir la pantalla.

## Prohibiciones absolutas

10A no puede:

- crear pagos;
- crear cobros;
- crear abonos;
- crear reversiones;
- cerrar o reabrir cortes;
- recalcular saldos;
- editar movimientos;
- modificar reservaciones;
- modificar inventario;
- modificar tareas;
- modificar trabajadores;
- modificar documentos;
- tocar permisos, roles o auth;
- tocar PWA/offline, IndexedDB, cache names ni `/api/sync`;
- ejecutar `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP` ni `TRUNCATE`.

## Criterios de aceptacion futuros

Una futura pantalla 10A-B solo se considerara completa si:

- agrega una unica ruta GET o reutiliza una ruta GET sin escrituras;
- no agrega rutas POST;
- no modifica datos;
- no toca migraciones;
- no toca `/api/sync`;
- pasa `php -l` en archivos PHP modificados;
- tiene preflight/read-only o health extendido;
- health general queda con `ERROR: 0`;
- `git diff --check` no reporta errores en archivos tocados;
- la pantalla sin sesion redirige o bloquea segun patron del sistema;
- la pantalla con sesion muestra KPIs sin acciones operativas.

## QA futura recomendada

1. Abrir sin sesion y confirmar redireccion/bloqueo.
2. Abrir con sesion de hotel.
3. Confirmar etiqueta `Solo lectura`.
4. Confirmar filtros GET.
5. Confirmar resumen ejecutivo y secciones por area.
6. Confirmar enlaces a CxC, CxP, Caja, tareas, inventario y documentos.
7. Confirmar que no hay formularios POST ni botones operativos.
8. Confirmar que navegar o filtrar no modifica notificaciones, saldos, cortes,
   movimientos, inventario, tareas ni documentos.

## Siguiente paso seguro

La siguiente fase recomendada era 10A-A como preflight CLI/read-only del tablero
ejecutivo antes de construir UI. Ese paso ya quedo implementado en el seguimiento.

## Seguimiento

La fase 10A-A quedo implementada como preflight CLI/read-only en
`src/tools/saas/preflight_tablero_ejecutivo.php`.

El siguiente paso seguro despues del preflight es 10A-B-0: contrato de pantalla
ejecutiva GET/read-only antes de tocar rutas, controlador, modelo o vista.

El contrato 10A-B-0 quedo documentado en
`docs/fase_10A_B_0_contrato_pantalla_tablero_ejecutivo_readonly.md`.

La pantalla 10A-B-A quedo implementada como `GET /reportes/ejecutivo` en
`docs/fase_10A_B_A_pantalla_tablero_ejecutivo_readonly.md`.

El bloque 10A-B quedo cerrado con QA manual validada en
`docs/fase_10A_B_F_cierre_tablero_ejecutivo.md`.
