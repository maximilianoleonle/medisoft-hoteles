# Fase 9C-C - Diagnostico read-only de cortes con diferencias de arqueo

Estado: `IMPLEMENTACION_9C_C_DIAGNOSTICO_ARQUEO_CORTES_QA_TECNICA_LOCAL_COMPLETADA`.

## Objetivo

Agregar una herramienta local, CLI y read-only para explicar los warnings de
arqueo detectados por `preflight_arqueo_metodos_pago.php`.

La herramienta no corrige datos, no recalcula cortes, no abre ni cierra caja y
no modifica movimientos.

## Alcance implementado

- Nueva herramienta:
  `src/tools/saas/diagnosticar_arqueo_cortes.php`.
- Ejecucion exclusiva por CLI.
- Requiere `APP_ENV=local`.
- Abre transaccion `START TRANSACTION READ ONLY`.
- Cierra con `rollBack()`.
- Lista:
  - cortes cerrados con totales guardados distintos a movimientos;
  - efectivo esperado distinto a formula guardada;
  - efectivo esperado distinto a formula por movimientos;
  - diferencia de arqueo inconsistente;
  - movimientos posteriores al cierre en cortes con diferencias;
  - categorias agrupadas en cortes con diferencias.
- Ajuste complementario en `preflight_arqueo_metodos_pago.php`:
  agrega warning especifico para cortes descuadrados con movimientos creados
  despues del cierre.

## Resultado local observado

Comando:

```bash
php tools/saas/diagnosticar_arqueo_cortes.php --limit=20
```

Resultado:

- `OK: 34`
- `WARNING: 4`
- `ERROR: 0`
- `PASS_WITH_WARNINGS_ALLOWED`

Cortes identificados con diferencias entre totales guardados y movimientos:

- Corte `#21`: delta ingresos efectivo `+$2,000.00`.
- Corte `#149`: delta ingresos efectivo `-$1,600.00`.
- Corte `#15`: delta ingresos efectivo `+$1,000.00`.
- Corte `#48`: delta ingresos efectivo `-$600.00`.

El diagnostico muestra movimientos creados despues de `fecha_cierre` en esos
cortes. El codigo actual de movimientos manuales y servicios recientes usa corte
abierto (`estado = 'abierto'`), por lo que el hallazgo se trata como dato
historico/local hasta que se autorice una revision funcional de Caja.

## QA tecnica

Comandos ejecutados:

```bash
php -l /var/www/html/tools/saas/diagnosticar_arqueo_cortes.php
php -l /var/www/html/tools/saas/preflight_arqueo_metodos_pago.php
php tools/saas/diagnosticar_arqueo_cortes.php --limit=20
php tools/saas/preflight_arqueo_metodos_pago.php
```

Resultados:

- Lint PHP: OK.
- Diagnostico: `ERROR: 0`.
- Preflight 9C-A/9C-B-A: `OK: 39`, `WARNING: 3`, `ERROR: 0`.

## Limites

No se tocaron:

- rutas;
- controladores;
- modelos operativos;
- servicios de Caja;
- vistas;
- migraciones;
- base de datos;
- produccion;
- PWA/offline;
- `/api/sync`.

## Siguiente decision recomendada

Antes de corregir datos o agregar guardas operativas en Caja, decidir si estos
cortes son basura historica/local o si representan un flujo vigente que todavia
permite crear movimientos despues del cierre.

No ejecutar correcciones de datos en produccion sin autorizacion explicita.
