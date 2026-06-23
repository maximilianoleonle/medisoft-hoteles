# Fase 10A-C - Diagnostico read-only de fuentes del tablero ejecutivo

Estado: `IMPLEMENTACION_10A_C_DIAGNOSTICO_FUENTES_TABLERO_EJECUTIVO_QA_TECNICA_LOCAL_COMPLETADA`.

## Objetivo

Agregar una herramienta local, CLI y read-only para explicar los warnings
funcionales pendientes del tablero ejecutivo:

- `ledger_laboral` como fuente opcional no instalada.
- `logs_auditoria.hotel_id IS NULL` como auditoria global/auth sin scope de
  hotel.

La herramienta no corrige datos, no crea migraciones, no modifica auditoria y
no cambia la logica operativa del tablero.

## Alcance implementado

- Nueva herramienta:
  `src/tools/saas/diagnosticar_tablero_ejecutivo_fuentes.php`.
- Ejecucion exclusiva por CLI.
- Requiere `APP_ENV=local`.
- Abre transaccion `START TRANSACTION READ ONLY`.
- Cierra con `rollBack()`.
- Lista fuentes laborales alternativas disponibles:
  - `trabajadores`;
  - `trabajador_pagos`;
  - `trabajador_pagos_caja`;
  - `trabajador_anticipos`;
  - `trabajador_prestamos`;
  - `trabajador_asistencias`;
  - `trabajador_nomina_periodos`;
  - `trabajador_nomina_periodo_detalles`;
  - `trabajador_nomina_periodo_eventos`.
- Clasifica logs sin hotel como `auth.*` global cuando aplica.
- Muestra acciones, entidades y muestras recientes sin `hotel_id`.
- Ajusta `preflight_tablero_ejecutivo.php` para reportar auditoria
  global/auth de forma explicita.

## Resultado local observado

Comando:

```bash
php tools/saas/diagnosticar_tablero_ejecutivo_fuentes.php --limit=20
```

Resultado:

- `OK: 19`
- `WARNING: 2`
- `ERROR: 0`
- `PASS_WITH_WARNINGS_ALLOWED`

Hallazgos:

- `ledger_laboral` no existe, pero el tablero ya lo trata como fuente opcional.
- Existen fuentes laborales actuales con `hotel_id` sano:
  `trabajadores`, `trabajador_pagos`, `trabajador_pagos_caja`,
  `trabajador_nomina_periodos`, `trabajador_nomina_periodo_detalles` y eventos.
- `logs_auditoria` tiene 40 filas sin `hotel_id`.
- Todas las filas sin `hotel_id` pertenecen a `auth.*`:
  `auth.login_success`, `auth.logout`, `auth.login_failed`.
- Esos eventos se clasifican como auditoria global de acceso, no como auditoria
  operativa de hotel.

## QA tecnica

Comandos ejecutados:

```bash
php -l /var/www/html/tools/saas/diagnosticar_tablero_ejecutivo_fuentes.php
php -l /var/www/html/tools/saas/preflight_tablero_ejecutivo.php
php tools/saas/diagnosticar_tablero_ejecutivo_fuentes.php --limit=20
php tools/saas/preflight_tablero_ejecutivo.php
```

Resultados:

- Lint PHP: OK.
- Diagnostico 10A-C: `OK: 19`, `WARNING: 2`, `ERROR: 0`.
- Preflight tablero ejecutivo: `OK: 92`, `WARNING: 2`, `ERROR: 0`.

Warnings restantes:

- `ledger_laboral` opcional no disponible.
- `logs_auditoria` con auditoria global/auth sin `hotel_id`.

## Limites

No se tocaron:

- rutas;
- controladores;
- modelos operativos;
- vistas;
- migraciones;
- base de datos;
- auth operativo;
- permisos;
- Caja;
- produccion;
- PWA/offline;
- `/api/sync`.

## Siguiente decision recomendada

Mantener ambos warnings como permitidos mientras el tablero no requiera un KPI
laboral dedicado ni auditoria de login/logout por hotel.

Si despues se quiere eliminar el warning de auditoria global/auth, hacerlo con
una fase separada y autorizada, porque implicaria decidir si los eventos de
acceso deben tener `hotel_id` aun antes/despues de seleccionar hotel.
