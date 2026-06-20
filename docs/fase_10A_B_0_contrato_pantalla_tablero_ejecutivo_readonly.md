# Fase 10A-B-0 - Contrato pantalla tablero ejecutivo read-only

## Estado

`CONTRATO_10A_B_0_PANTALLA_TABLERO_EJECUTIVO_READONLY_COMPLETADO`

## Objetivo

Definir la implementacion futura de una pantalla ejecutiva integral para el hotel,
en modo solo lectura, usando los hallazgos del preflight 10A-A.

Esta fase no implementa ruta, controlador, modelo, vista, navegacion, migracion ni
datos. Solo establece el contrato que debe cumplir una futura fase 10A-B-A si se
autoriza tocar codigo.

## Ruta futura permitida

Ruta candidata unica:

```text
GET /reportes/ejecutivo
```

La ruta no se registra en esta fase.

Prohibido:

- `POST /reportes/ejecutivo`;
- endpoints AJAX de escritura;
- exports PDF/CSV/XLSX sin contrato separado;
- links publicos o descargas temporales sin contrato separado.

## Archivos que podria tocar 10A-B-A

Solo con autorizacion explicita, una fase futura podria tocar:

- `src/config/routes.php`, solo para agregar `GET /reportes/ejecutivo`;
- `src/app/controllers/ReportesController.php`, solo para una accion read-only;
- un lector/read model dedicado, por ejemplo `src/app/models/TableroEjecutivo.php`;
- una vista nueva, por ejemplo `src/app/views/reportes/ejecutivo.php`;
- `src/tools/saas/preflight_tablero_ejecutivo.php`, para validar la pantalla;
- `src/tools/saas/health_check_fase_1a.php`, para vigilar la pantalla.

No debe tocar:

- migraciones;
- base de datos;
- permisos/auth;
- servicios de pago, cobro, reversion, compra, inventario o tareas;
- PWA/offline, IndexedDB, cache names ni `/api/sync`.

## Contrato funcional

La pantalla futura debe mostrar un resumen ejecutivo con secciones de lectura:

- panorama general:
  - ingresos del periodo;
  - gastos del periodo;
  - neto estimado;
  - saldo CxC pendiente;
  - saldo CxP pendiente;
  - alertas principales.
- operacion hotelera:
  - ocupacion actual;
  - habitaciones disponibles, ocupadas, en limpieza y en mantenimiento;
  - check-ins y check-outs del dia;
  - reservaciones vigentes o proximas.
- finanzas y Caja:
  - movimientos por metodo;
  - cortes abiertos;
  - cortes con diferencias;
  - cobros CxC y pagos CxP del periodo;
  - reversiones solo como conteo o alerta, sin acciones.
- inventario y compras:
  - productos bajo minimo;
  - productos sin movimiento reciente;
  - compras recibidas del periodo;
  - entradas y salidas recientes.
- tareas y personal:
  - tareas activas;
  - tareas vencidas;
  - tareas por categoria o responsable;
  - trabajadores activos.
- documentos y auditoria:
  - documentos recientes;
  - documentos por entidad;
  - eventos de auditoria recientes como resumen.

## Filtros GET permitidos

- `periodo`: `hoy`, `7d`, `30d`, `mes`, `custom`;
- `fecha_desde`;
- `fecha_hasta`;
- `area`: `todas`, `operacion`, `finanzas`, `inventario`, `personal`, `documentos`;
- `severidad`: `todas`, `ok`, `warning`, `error`;
- `limit`.

El `hotel_id` no puede recibirse desde query string. Debe salir del contexto de hotel
actual.

## Reglas visuales

La vista futura debe:

- usar identidad del hotel cliente con tokens `--brand-*`;
- no usar tokens `--ms-*`;
- mostrar etiqueta visible `Solo lectura`;
- usar solo formularios `GET` para filtros;
- no incluir CSRF si no hay POST;
- no mostrar botones de accion operativa;
- mostrar datos incompletos como advertencias, no como errores fatales;
- indicar cuando un importe sea estimado o derivado.

La vista futura no debe incluir:

- pagar;
- cobrar;
- revertir;
- cerrar corte;
- reabrir corte;
- recalcular;
- ajustar;
- editar movimientos;
- cambiar metodo de pago;
- recibir compra;
- modificar inventario;
- asignar/cerrar tareas;
- archivar documentos;
- marcar notificaciones como vistas por abrir la pantalla.

## Reglas backend

La implementacion futura debe:

- requerir sesion y contexto de hotel actual;
- usar un lector dedicado de solo lectura;
- filtrar todas las fuentes por `hotel_id` cuando la tabla lo soporte;
- acceder a `huespedes` solo mediante joins con entidades scoped por hotel;
- tratar `ledger_laboral` como fuente opcional ausente;
- degradar `logs_auditoria` con `hotel_id` nulo como warning historico;
- usar transaccion read-only cuando aplique;
- cerrar la lectura con rollback;
- no llamar `archivarNotificacionReporteGerencialVisto`;
- no reutilizar `GET /reportes/gerencial-diario` como fuente read-only pura mientras
  archive notificaciones.

## Dependencia del preflight

Antes de implementar pantalla, debe pasar:

```bash
php tools/saas/preflight_tablero_ejecutivo.php
```

Resultado base esperado desde 10A-A:

- `ERROR: 0`;
- warnings conocidos y documentados;
- sin ruta `GET /reportes/ejecutivo` registrada todavia;
- sin `POST /reportes/ejecutivo`.

## Criterios de aceptacion para 10A-B-A futura

Una implementacion futura solo se considerara completa si:

- registra una unica ruta `GET /reportes/ejecutivo`;
- no registra rutas POST;
- no modifica datos al abrir, filtrar o navegar;
- no toca migraciones;
- usa tokens `--brand-*` y cero `--ms-*`;
- muestra `Solo lectura`;
- usa filtros GET;
- pasa `php -l` en PHP modificado;
- pasa `preflight_tablero_ejecutivo.php` con `ERROR: 0`;
- pasa `health_check_fase_1a.php` con `ERROR: 0`;
- sin sesion redirige o bloquea segun patron del sistema;
- con sesion muestra KPIs y warnings sin acciones operativas;
- `/api/sync` sigue bloqueado con HTTP 423 y `sync_temporarily_disabled`.

## QA futura recomendada

1. Abrir `/reportes/ejecutivo` sin sesion y confirmar redireccion/bloqueo.
2. Abrir con sesion de hotel.
3. Confirmar etiqueta `Solo lectura`.
4. Confirmar filtros GET y ausencia de POST.
5. Confirmar que no aparece `hotel_id` editable.
6. Confirmar que no hay botones operativos.
7. Confirmar KPIs por area.
8. Confirmar warnings para fuentes incompletas.
9. Confirmar que filtrar no modifica notificaciones, saldos, cortes, movimientos,
   inventario, tareas ni documentos.
10. Confirmar que `/api/sync` sigue bloqueado.

## Siguiente paso seguro

La fase 10A-B-A quedo implementada como pantalla GET/read-only en
`docs/fase_10A_B_A_pantalla_tablero_ejecutivo_readonly.md`.

La QA manual fue validada y el cierre quedo documentado en
`docs/fase_10A_B_F_cierre_tablero_ejecutivo.md`.
