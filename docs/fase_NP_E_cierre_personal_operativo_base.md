# Fase NP-E - Cierre tecnico de Personal operativo base

Estado: `BLOQUE_NP_PERSONAL_OPERATIVO_BASE_CERRADO_QA_DIFERIDA`

Fecha de cierre tecnico: 2026-06-16

## Objetivo

Cerrar documentalmente el bloque de Personal operativo base despues de completar las
subfases seguras de trabajadores, ledger laboral, conceptos, anticipos/prestamos,
asistencia manual y documentos laborales contextuales.

Este cierre no agrega funcionalidad nueva, no ejecuta migraciones y no escribe datos.

## Alcance cerrado

- Personal base multi-hotel con `trabajadores` como entidad independiente.
- Ficha de trabajador con informacion laboral y estados vacios.
- Ledger laboral read-only derivado de tablas `trabajador_*`.
- Conceptos laborales manuales en `trabajador_pagos`, sin Caja ni pago real.
- Anticipos y prestamos manuales en `trabajador_anticipos` y
  `trabajador_prestamos`, sin abonos ni liquidaciones.
- Asistencia manual basica en `trabajador_asistencias`, una por trabajador/dia.
- Integracion de documentos laborales con Centro Documental moderno usando
  `documento_entidades.entidad_tipo = 'trabajador'`.
- Preflight dedicado de ledger laboral y health checker actualizado.

## Fuera de alcance

- Nomina automatica.
- Calculo de sueldos o descuentos automaticos.
- Pagos reales a trabajadores.
- Abonos o liquidaciones de anticipos/prestamos.
- Movimientos de Caja, cortes o categorias de Caja.
- Reconciliacion de `trabajador_documentos`.
- Borrado fisico de trabajadores o documentos.
- Cambios en `/api/sync`.

## Verificacion automatica base

Ultimas verificaciones registradas en el bloque:

- `php -l` en archivos PHP modificados durante NP-C-D-A y NP-D-A.
- `docker compose exec -T app php /var/www/html/tools/saas/preflight_personal_ledger.php`
  con `ERROR: 0`.
- `docker compose exec -T app php /var/www/html/tools/saas/health_check_fase_1a.php`
  con `ERROR: 0` y warnings historicos permitidos.
- SQL read-only confirmo ausencia de datos locales en tablas laborales criticas y que
  Caja no fue alterada por las fases NP-C/NP-D.
- HTTP sin sesion bloqueo/redirigio rutas protegidas probadas.

## QA manual diferida

El usuario autorizo omitir QA manual temporalmente y continuar con bloques seguros.
Por tanto, el bloque queda cerrado tecnicamente, pero no validado manualmente.

QA pendiente minima cuando exista trabajador activo de prueba:

- Crear o usar un trabajador activo del hotel actual.
- Validar ficha de trabajador.
- Registrar concepto laboral permitido y confirmar ledger.
- Registrar anticipo y prestamo, confirmar saldo inicial.
- Registrar asistencia y confirmar bloqueo de duplicado por fecha.
- Vincular/subir documento laboral desde la ficha y confirmar descarga segura.
- Confirmar que no se crean movimientos de Caja, pagos reales, abonos ni nomina.
- Ejecutar preflight de ledger laboral y confirmar `ERROR: 0`.

## Riesgos residuales

- La tabla local `trabajadores` estaba vacia durante las verificaciones automaticas, por
  lo que la QA de navegador con flujo completo queda pendiente.
- `trabajador_documentos` sigue como tabla legacy/aditiva congelada; no debe usarse para
  nuevos flujos documentales.
- Las tablas laborales informativas pueden parecer financieras; toda UI debe mantener
  copy explicito de "sin Caja" y "sin pago real" hasta abrir un bloque financiero formal.

## Rollback

- Revertir este cierre documental si se requiere reabrir la narrativa del bloque.
- Para codigo, usar los rollbacks por subfase documentados en `docs/rollback-cola.md`.
- No ejecutar `DELETE`, `UPDATE` ni correcciones manuales sobre tablas `trabajador_*`,
  `documentos` o `documento_entidades` sin una fase de limpieza/anulacion autorizada.

## Siguiente paso recomendado

Siguiente cola exacta recomendada:

`[COLA_NP_F_0_REPORTE_PERSONAL_READ_ONLY]`

Objetivo sugerido: contrato de un reporte read-only de Personal que consolide trabajadores,
asistencias, conceptos, anticipos, prestamos, documentos y tareas asignadas, sin crear
nomina, pagos, abonos ni Caja.
