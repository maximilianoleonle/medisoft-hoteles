# Fase 9A-0 - Contrato conciliacion financiera operativa read-only

## Estado

`CONTRATO_9A_0_CONCILIACION_FINANCIERA_READONLY_COMPLETADO`

## Objetivo

Definir una fase futura de conciliacion financiera operativa solo lectura que cruce
CxC, CxP y Caja para detectar diferencias, duplicados, movimientos faltantes o saldos
inconsistentes sin corregir datos automaticamente.

## Contexto

Los bloques sensibles ya cerrados son:

- CxC con Caja:
  - cobro CxC validado;
  - reversion de cobro CxC validada;
  - cierre documental `7B-D-D-F`.
- CxP con Caja:
  - pago proveedor validado;
  - reversion de pago proveedor validada;
  - cierre documental `3D-D-F`.

El siguiente paso seguro no debe crear nuevas escrituras financieras. Primero se debe
observar y comparar el estado operativo.

## Alcance permitido

La fase futura 9A-A podra implementar una vista GET/read-only o un preflight read-only
para mostrar:

- resumen CxC:
  - cuentas abiertas;
  - cuentas liquidadas;
  - saldo pendiente;
  - cobros registrados;
  - reversiones de cobro;
  - movimientos CxC sin Caja asociada;
  - movimientos Caja sin movimiento CxC asociado.
- resumen CxP:
  - cuentas abiertas;
  - cuentas pagadas;
  - saldo pendiente;
  - pagos proveedor;
  - reversiones de pago proveedor;
  - movimientos CxP sin Caja asociada;
  - movimientos Caja sin movimiento CxP asociado.
- resumen Caja:
  - movimientos de Caja vinculados a CxC;
  - movimientos de Caja vinculados a CxP;
  - movimientos con corte abierto/cerrado;
  - referencias duplicadas;
  - referencias canonicas esperadas y faltantes.
- alertas read-only:
  - doble reversion;
  - cobro o pago sin contrapartida Caja;
  - Caja sin contrapartida ledger;
  - saldo fuera de rango;
  - hotel_id cruzado;
  - corte inexistente o de otro hotel;
  - movimientos con categoria inesperada.

## Fuentes permitidas

La conciliacion solo puede leer:

- `cuentas_por_cobrar`;
- `cuentas_por_cobrar_movimientos`;
- `cuentas_por_pagar`;
- `cuentas_por_pagar_movimientos`;
- `movimientos_caja`;
- `cortes_caja`;
- `cajas`;
- `logs_auditoria`;
- `reservaciones`, solo como contexto de origen CxC;
- `compras` y `proveedores`, solo como contexto de origen CxP;
- `hoteles`, solo para scope y nombre del hotel actual.

## Prohibiciones

La fase 9A no puede:

- crear, editar o borrar CxC;
- crear, editar o borrar CxP;
- crear, editar o borrar movimientos de Caja;
- abrir, cerrar o recalcular cortes;
- modificar saldos;
- compensar automaticamente diferencias;
- crear pagos, cobros, abonos, ajustes o cancelaciones;
- tocar reservaciones, compras, proveedores, facturacion o documentos;
- tocar PWA/offline, IndexedDB, cache names ni `/api/sync`;
- ejecutar SQL de correccion;
- hacer reconciliacion destructiva.

## Reglas de UI futura

Si se implementa pantalla:

- ruta GET unica;
- sin formularios POST;
- sin botones de "corregir", "ajustar", "pagar", "cobrar", "revertir" o "compensar";
- filtros GET por fecha, corte, hotel actual, tipo y severidad;
- enlaces solo a detalles existentes;
- etiquetas visibles de "solo lectura";
- no mostrar SQL interno ni rutas de storage.

## Reglas de backend futuro

Si se implementa modelo, servicio o preflight:

- usar transaccion read-only cuando aplique;
- filtrar siempre por `hotel_id`;
- no llamar servicios de pago, cobro ni reversion;
- no usar `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP` ni `TRUNCATE`;
- no registrar auditoria por simples lecturas, salvo que se defina un contrato separado;
- no depender de texto libre si existe referencia canonica.

## Referencias canonicas esperadas

CxC:

```text
CXC-{cuenta_id}-MOV-{movimiento_cxc_id}
REV-CXC-{cuenta_id}-MOV-{movimiento_cobro_id}
```

CxP:

```text
CXP-{cuenta_id}-MOV-{movimiento_cxp_id}
REV-CXP-{cuenta_id}-MOV-{movimiento_pago_id}
```

## Estado actual conocido para validar la futura lectura

CxC:

- CxC `#1`: estado `pendiente`, saldo `4250.00`.
- Cobro real: movimiento CxC `#4`, Caja `#1482`.
- Reversion real: movimiento CxC `#8`, Caja `#1486`.

CxP:

- CxP `#1`: estado `parcial`, saldo `10.00`.
- Pago parcial: movimiento CxP `#4`, Caja `#1478`.
- Pago total restante: movimiento CxP `#5`, Caja `#1479`.
- Reversion real: movimiento CxP `#9`, Caja `#1494`.
- CxP `#2`: estado `pendiente`, saldo `1900.00`.

## Definition of Done para 9A-A futura

La implementacion futura solo se considera completa si:

- es GET/read-only;
- no agrega rutas POST;
- no modifica datos;
- no toca Caja mas alla de lectura;
- no toca `/api/sync`;
- pasa `php -l` en archivos modificados;
- tiene preflight o verificacion read-only;
- documenta warnings esperados y descuadres reales sin corregirlos.

## Siguiente paso seguro

9A-A puede ser una implementacion read-only pequena si se autoriza:

- un preflight `tools/saas/preflight_conciliacion_financiera.php`; o
- una vista GET en el tablero operativo existente.

La opcion recomendada primero es preflight read-only antes de UI.
