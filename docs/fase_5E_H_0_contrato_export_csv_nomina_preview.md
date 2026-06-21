# Fase 5E-H-0 - Contrato export CSV del preview de pre-nomina

Estado formal:
`CONTRATO_5E_H_0_EXPORT_CSV_NOMINA_PREVIEW_READ_ONLY_COMPLETADO`.

## Objetivo

Definir el contrato para una futura exportacion CSV del preview read-only de
pre-nomina por periodo, sin implementar todavia codigo, rutas, controladores,
modelos, vistas, formularios, migraciones, permisos, servicios, Caja, datos ni
`/api/sync`.

La exportacion futura debe servir como evidencia operativa descargable de la misma
lectura que ya muestra `GET /trabajadores/nomina/preview`.

## Ruta candidata futura

- `GET /trabajadores/nomina/preview/exportar`

La ruta futura debe ser solo lectura y debe responder como descarga CSV. No debe
existir POST equivalente ni accion que registre nomina, pagos, recibos o movimientos
de Caja.

## Filtros obligatorios

La exportacion debe respetar los mismos filtros del preview:

- `fecha_inicio`
- `fecha_fin`
- `trabajador_id`
- `buscar`
- `rol_laboral`
- `estado`
- `solo_con_saldo`
- `incluir_pagos_caja`

El periodo debe seguir siendo obligatorio. Si falta o es invalido, la ruta no debe
descargar un CSV ambiguo; debe redirigir o mostrar un error controlado equivalente al
preview.

## Fuente de calculo

La implementacion futura debe reutilizar la misma fuente read-only del preview,
preferentemente `Trabajador::nominaPreviewPorHotel()`, para evitar divergencias entre
pantalla y exportacion.

El CSV no debe recalcular con reglas distintas ni consultar otro hotel.

## Columnas esperadas

Columnas minimas recomendadas para el CSV:

1. `periodo_inicio`
2. `periodo_fin`
3. `trabajador_id`
4. `trabajador`
5. `identificacion`
6. `rol`
7. `estado_trabajador`
8. `estado_preview`
9. `bruto_periodo`
10. `conceptos_a_favor`
11. `conceptos_en_contra`
12. `anticipos_saldo`
13. `prestamos_saldo`
14. `deducciones_informativas`
15. `pagos_caja_aplicados`
16. `pagos_caja_pagados`
17. `pagos_caja_revertidos`
18. `reversiones_detectadas`
19. `neto_sugerido`
20. `pendiente_pago_sugerido`
21. `ultimo_pago_caja`

Los importes deben exportarse en formato decimal plano, sin simbolos monetarios.

## Headers esperados

La respuesta futura debe usar headers de descarga seguros:

- `Content-Type: text/csv; charset=UTF-8`
- `Content-Disposition: attachment`
- `X-Content-Type-Options: nosniff`

Se recomienda incluir BOM UTF-8 para compatibilidad con Excel.

## Limite de filas

La implementacion futura debe mantener un limite razonable de filas por descarga
para evitar exportaciones pesadas accidentales. Limite sugerido inicial: `500`
trabajadores, salvo que el preview ya aplique un limite menor.

## Prohibido en esta fase

Queda prohibido en 5E-H-0 y en la futura exportacion CSV:

- crear, cerrar o persistir periodos de nomina;
- registrar pagos masivos;
- crear recibos oficiales;
- timbrar nomina;
- generar dispersion bancaria;
- liquidar anticipos o prestamos automaticamente;
- crear, editar o revertir movimientos de Caja;
- modificar `trabajador_pagos_caja`;
- modificar `trabajador_pagos`, `trabajador_anticipos` o `trabajador_prestamos`;
- modificar cortes, cajas, categorias o movimientos de Caja;
- crear auditorias por simple descarga salvo contrato independiente;
- tocar permisos/auth;
- tocar PWA/offline, IndexedDB, cache names o `/api/sync`.

## Archivos candidatos para una futura 5E-H-A

Solo con autorizacion explicita se podrian tocar:

- `src/config/routes.php`
- `src/app/controllers/TrabajadorController.php`
- `src/app/models/Trabajador.php` si hiciera falta reutilizar o exponer lectura
  existente sin duplicarla
- `src/app/views/trabajadores/nomina_preview.php`
- `src/tools/saas/preflight_personal_pagos_caja.php`
- `src/tools/saas/health_check_fase_1a.php`
- documentacion de fase

## QA futura sugerida

1. Abrir `/trabajadores/nomina/preview` con periodo valido.
2. Descargar CSV con los mismos filtros visibles.
3. Confirmar que el CSV contiene solo trabajadores del hotel actual.
4. Confirmar que los totales coinciden con la pantalla.
5. Activar y desactivar `incluir_pagos_caja` y validar que el CSV cambia igual que el
   preview.
6. Activar `solo_con_saldo` y confirmar que el CSV respeta el filtro.
7. Intentar exportar sin periodo y confirmar bloqueo controlado.
8. Confirmar que no hay POST, pagos, recibos, dispersion, movimientos de Caja ni
   escrituras laborales.

## Criterio de cierre futuro

La futura 5E-H-A solo debera cerrarse si:

- la ruta sin sesion redirige a login y no responde 404;
- `php -l` pasa en archivos PHP tocados;
- preflight pagos laborales Caja termina con `ERROR: 0`;
- health general termina con `ERROR: 0`;
- `/api/sync` sigue bloqueado;
- QA manual confirma descarga y consistencia de datos.
