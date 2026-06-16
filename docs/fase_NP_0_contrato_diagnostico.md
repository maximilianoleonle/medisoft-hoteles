# Fase NP-0 - Contrato y diagnostico

## Objetivo

Definir el contrato seguro del bloque **Personal y Nomina (Fase NP)**: un modulo
INDEPENDIENTE de trabajadores con ledger laboral, saldos por persona, asistencia y
comisiones, multi-hotel y auditable, PERO sin integracion con Caja ni salida real de
dinero en este bloque.

La Fase NP-0 solo produce contrato, diagnostico y diseno de tablas. No crea
funcionalidad, no crea migraciones aplicadas y no escribe en la base de datos.

## Estado heredado

- Bloque Fase 2X-3C cerrado tecnicamente; QA manual del bloque anterior reportada como
  realizada por el usuario.
- HEAD al iniciar NP-0: `5dfe665 test(phase-3c): add payable consistency checks`.
- Estado Git al iniciar NP-0: limpio (working tree clean).
- Rama: `feature/saas-multihotel`, 21 commits por delante de `origin` (sin push).
- Ultimo commit del usuario que cierra el bloque previo: la cadena `9897465`..`5dfe665`
  de Fase 3C, precedida del ajuste visual `dc3c150 fix: improve late check-in modal layout`.
  No se inventan hashes: estos son los hashes reales detectados con `git log`.

## Problema que resuelve

- Hoy "trabajador" = usuario del sistema (`usuarios` + pivote `hotel_usuarios`).
- No existe deuda laboral por persona.
- No existen pagos / anticipos / prestamos por trabajador.
- No existen saldos ni reporte semanal / quincenal por persona.
- No existe asistencia ni comisiones por trabajador.

## Riesgo

Naranja.

Motivo:

- crea un modulo financiero-laboral nuevo (deuda y saldos por persona);
- toca un concepto sensible (trabajadores antes ligados a `usuarios`);
- pero NO mueve dinero;
- NO toca Caja;
- NO altera `usuarios` de forma destructiva;
- NO toca `/api/sync`.

Mitigaciones: migraciones aditivas y reversibles, commits pequenos por subfase,
validaciones fuertes, filtro `hotel_id` en todo, rollback documentado, health/preflight,
QA critica, revision tecnica y auditoria antes del cierre.

## Diagnostico DB read-only

Base local principal: `medisoft_hoteles_import` (Docker `db`, MySQL 8.0, healthy).

### Como se representan hoy los "trabajadores"

`usuarios` es una tabla **global** (no tiene `hotel_id`):

- columnas relevantes: `id`, `nombre_usuario` (UNIQUE), `password`, `nombre_completo`,
  `email`, `telefono`, `rol ENUM('gerente','administrador','recepcionista')`, `activo`,
  `ultimo_login`, `ip_ultimo_login`, timestamps.
- `rol` es un **rol de sistema/acceso**, no un rol laboral.

El vinculo trabajador-hotel hoy se hace por el pivote `hotel_usuarios`:

- `hotel_id`, `usuario_id`, `rol ENUM('superadmin','propietario','gerente','administrador','recepcionista')`,
  `es_principal`, `activo`, `permisos_json`.
- UNIQUE `(hotel_id, usuario_id)`; FKs a `hoteles` y `usuarios` con `ON DELETE CASCADE`.

Conclusion: hoy un "trabajador" solo existe si tiene cuenta de sistema. No hay tabla de
personal, ni rol laboral, ni saldo, ni deuda por persona.

Conteos observados:

- `usuarios`: 11.
- `hotel_usuarios`: 11.
- `hoteles`: 4 (objetivo de `hotel_id`).

### Caja (solo lectura, confirmacion de que NO se tocara)

Tablas de Caja existentes: `cajas`, `movimientos_caja`, `cortes_caja`,
`categorias_movimientos`, `denominaciones_efectivo`, vista `vista_caja_actual`.

- `movimientos_caja`: `tipo ENUM('ingreso','gasto')`, `categoria VARCHAR(50)`,
  `categoria_id`, `monto`, `metodo_pago`, `usuario_id`, `corte_id`, etc. 1402 filas.
- `categorias_movimientos` NO contiene ninguna categoria "Nomina". Confirmado: la Fase NP
  NO debe crear esa categoria ni ningun movimiento de Caja.

Movimientos de Caja relacionados con nomina hoy: 0. Debe seguir en 0 durante todo el bloque.

### Mantenimiento / limpieza / tareas (para disenar la referencia de "responsable")

- Existe `mantenimientos_habitaciones` (10 filas) con:
  - `tipo_mantenimiento ENUM('preventivo','correctivo','emergencia','limpieza_profunda')`,
  - `realizado_por VARCHAR(100)` = **texto libre**, hoy mayormente `NULL`,
  - `usuario_registro_id INT`.
- NO existe tabla dedicada `limpieza` ni `tareas`. La limpieza vive como
  `tipo_mantenimiento = 'limpieza_profunda'` y como estado de habitacion.

Conclusion de diseno: el unico punto de "responsable" existente es el campo de texto libre
`mantenimientos_habitaciones.realizado_por`. La Fase NP NO alterara esa tabla. La
referencia trabajador-responsable sera **logica y opcional** (ver seccion de diseno);
agregar una columna FK real a mantenimiento requeriria una migracion aditiva separada y
autorizada en un bloque posterior.

### Patrones reutilizables detectados

- Auditoria: `AuditService::record($accion, $context)` inserta en `logs_auditoria`
  (`hotel_id`, `usuario_id`, `accion`, `entidad_tipo`, `entidad_id`, `descripcion`,
  `datos_antes`, `datos_despues`). Tolera ausencia de tabla. 35 filas actuales.
- Migraciones: patron aditivo idempotente (`CREATE TABLE IF NOT EXISTS`, `START
  TRANSACTION`, verificacion de columnas via `information_schema`, registro en
  `migrations` con `ON DUPLICATE KEY UPDATE`, bloque de rollback comentado). Modelo:
  `migrations/20260615_003_fase_3b_cxp_base.sql`.
- Modelos: extienden `Model`, usan `Database::getInstance()`, helper `tablaExiste()`,
  filtran SIEMPRE por `hotel_id`. Modelo de referencia: `src/app/models/CuentaPorPagar.php`.
- Tabla `migrations`: `nombre` (UNIQUE), `batch`, `checksum`, `estado`, `ejecutada_en`.

### Confirmacion de no-necesidad de tocar zonas prohibidas

- NO hace falta tocar Caja: el ledger laboral es independiente y NO genera movimientos.
- NO hace falta alterar `usuarios` de forma destructiva: el trabajador es una entidad
  nueva e independiente; el vinculo a un usuario sera opcional via columna nueva en
  `trabajadores`, nunca un cambio destructivo sobre `usuarios`.
- NO hace falta tocar `/api/sync`.

## Diseno aditivo propuesto (6 tablas nuevas)

Reglas transversales:

- Toda tabla lleva `hotel_id INT NOT NULL` con FK a `hoteles(id)` `ON DELETE RESTRICT`.
- Toda tabla hija lleva `trabajador_id INT NOT NULL` con FK a `trabajadores(id)`
  `ON DELETE RESTRICT` (sin borrado fisico; baja logica via estado).
- Importes `DECIMAL(12,2)` con `CHECK` de no-negatividad.
- Auditoria de autoria con `created_by` / `updated_by` -> `usuarios(id)` `ON DELETE SET NULL`.
- Motor InnoDB, `utf8mb4_unicode_ci`.
- Ninguna tabla referencia Caja ni inserta en Caja.

### 1) trabajadores

Entidad independiente. NO requiere usuario del sistema.

- `id`, `hotel_id` (FK hoteles RESTRICT),
- `usuario_id INT NULL` FK `usuarios(id)` `ON DELETE SET NULL` -> vinculo **opcional**,
  jamas obligatorio; no implica login ni permisos,
- `nombre_completo VARCHAR(150) NOT NULL`,
- `identificacion VARCHAR(60) NULL`,
- `rol_laboral VARCHAR(80) NULL` -> rol laboral (ej. "camarista", "mantenimiento"),
  NO es permiso del sistema,
- `telefono VARCHAR(30) NULL`, `email VARCHAR(120) NULL`,
- `estado ENUM('activo','inactivo','baja') NOT NULL DEFAULT 'activo'`,
- `fecha_alta DATE NULL`, `fecha_baja DATE NULL`,
- `salario_base DECIMAL(12,2) NULL` (`CHECK >= 0`),
- `periodicidad_pago ENUM('semanal','quincenal','mensual','por_evento') NULL`,
- `notas TEXT NULL`, `created_by`, `updated_by`, timestamps.
- Indices: `idx (hotel_id, estado)`, `idx (hotel_id, rol_laboral)`, `idx usuario_id`.

### 2) trabajador_pagos (ledger de conceptos laborales: pagos y comisiones/bonos/descuentos)

NO es Caja. Cada fila es un REGISTRO LABORAL que afecta el saldo del trabajador.

- `id`, `hotel_id` (FK), `trabajador_id` (FK),
- `tipo ENUM('pago','comision','bono','descuento','ajuste') NOT NULL`,
  - `pago` = liquidacion/pago laboral entregado (registro laboral, NO movimiento de Caja),
  - `comision`, `bono` = conceptos a favor del trabajador,
  - `descuento` = concepto en contra del trabajador,
  - `ajuste` = correccion manual controlada,
- `efecto ENUM('a_favor','en_contra') NOT NULL` -> efecto sobre "saldo a favor del
  trabajador" (lo que el hotel le debe); el modelo valida coherencia tipo/efecto,
- `monto DECIMAL(12,2) NOT NULL` (`CHECK >= 0`),
- `concepto VARCHAR(160) NULL`, `periodo_inicio DATE NULL`, `periodo_fin DATE NULL`,
- `fecha DATE NOT NULL`, `referencia VARCHAR(120) NULL`, `notas TEXT NULL`,
- `estado ENUM('activo','anulado') NOT NULL DEFAULT 'activo'`,
- `created_by`, `updated_by`, timestamps.
- Indices: `idx (hotel_id, trabajador_id)`, `idx (hotel_id, fecha)`, `idx (trabajador_id, estado)`.

### 3) trabajador_anticipos (anticipos de sueldo entregados; registro laboral, NO Caja)

- `id`, `hotel_id` (FK), `trabajador_id` (FK),
- `monto DECIMAL(12,2) NOT NULL` (`CHECK >= 0`),
- `saldo_pendiente DECIMAL(12,2) NOT NULL` (`CHECK >= 0 AND saldo_pendiente <= monto`),
- `fecha DATE NOT NULL`, `motivo VARCHAR(160) NULL`,
- `estado ENUM('pendiente','descontado','cancelado') NOT NULL DEFAULT 'pendiente'`,
- `referencia VARCHAR(120) NULL`, `notas TEXT NULL`, `created_by`, `updated_by`, timestamps.
- Efecto: el anticipo entregado reduce lo que el hotel le debe (saldo en contra del
  trabajador mientras `saldo_pendiente > 0`).
- Indices: `idx (hotel_id, trabajador_id)`, `idx (trabajador_id, estado)`.

### 4) trabajador_prestamos (prestamos; el trabajador debe al hotel)

- `id`, `hotel_id` (FK), `trabajador_id` (FK),
- `monto DECIMAL(12,2) NOT NULL` (`CHECK >= 0`),
- `saldo_pendiente DECIMAL(12,2) NOT NULL` (`CHECK >= 0 AND saldo_pendiente <= monto`),
- `fecha DATE NOT NULL`, `plazo_meses INT NULL`, `abono_periodico DECIMAL(12,2) NULL`,
- `motivo VARCHAR(160) NULL`,
- `estado ENUM('vigente','liquidado','cancelado') NOT NULL DEFAULT 'vigente'`,
- `referencia VARCHAR(120) NULL`, `notas TEXT NULL`, `created_by`, `updated_by`, timestamps.
- Efecto: el saldo pendiente es deuda del trabajador (saldo en contra del trabajador).
- Indices: `idx (hotel_id, trabajador_id)`, `idx (trabajador_id, estado)`.

### 5) trabajador_asistencias

- `id`, `hotel_id` (FK), `trabajador_id` (FK),
- `fecha DATE NOT NULL`,
- `tipo ENUM('asistencia','falta','retardo','permiso','incapacidad','descanso','horas_extra') NOT NULL DEFAULT 'asistencia'`,
- `hora_entrada TIME NULL`, `hora_salida TIME NULL`,
- `horas DECIMAL(5,2) NULL`, `horas_extra DECIMAL(5,2) NULL`,
- `observaciones VARCHAR(255) NULL`, `created_by`, timestamps.
- `UNIQUE (hotel_id, trabajador_id, fecha)` -> una asistencia por trabajador por dia.
- Indices: `idx (hotel_id, fecha)`, `idx (trabajador_id, fecha)`.

### 6) trabajador_documentos

- `id`, `hotel_id` (FK), `trabajador_id` (FK),
- `tipo VARCHAR(60) NULL` (ej. "identificacion", "contrato", "comprobante"),
- `nombre_original VARCHAR(160) NULL`, `ruta_archivo VARCHAR(255) NULL`,
  `mime VARCHAR(120) NULL`, `tamano INT NULL`,
- `notas VARCHAR(255) NULL`,
- `estado ENUM('activo','eliminado') NOT NULL DEFAULT 'activo'` -> baja logica,
- `subido_por INT NULL` FK `usuarios(id)` `ON DELETE SET NULL`, timestamps.
- Indices: `idx (hotel_id, trabajador_id)`, `idx (trabajador_id, estado)`.

## Formula de saldo por trabajador (a definir en NP-C)

`saldo` desde la perspectiva del trabajador (positivo = el hotel le debe;
negativo = el trabajador debe):

```
saldo = SUM(trabajador_pagos.monto WHERE efecto='a_favor' AND estado='activo')
      - SUM(trabajador_pagos.monto WHERE efecto='en_contra' AND estado='activo')
      - SUM(trabajador_anticipos.saldo_pendiente WHERE estado <> 'cancelado')
      - SUM(trabajador_prestamos.saldo_pendiente WHERE estado <> 'cancelado')
```

Nota: `tipo='pago'` (liquidacion entregada) se registra con `efecto='en_contra'` porque
reduce lo que el hotel le debe. Ningun termino de esta formula toca Caja. Los totales son
DERIVADOS del ledger y no editables manualmente.

## Referencia logica "responsable" (alcance #9)

- En este bloque la relacion trabajador <-> mantenimiento/limpieza/tareas es **logica y
  opcional**, de solo lectura, basada en el campo existente
  `mantenimientos_habitaciones.realizado_por` (texto libre).
- NO se altera `mantenimientos_habitaciones` en la Fase NP.
- Una columna FK real (`trabajador_id`) en mantenimiento se difiere a un bloque posterior
  con su propia migracion aditiva autorizada.

## Reglas de elegibilidad / validacion (resumen para NP-A..NP-E)

- Toda escritura valida `hotel_id` del contexto y lo aplica a la fila.
- Todo pago/anticipo/prestamo/asistencia exige `trabajador_id` existente, del mismo
  `hotel_id` y (para movimientos) trabajador en estado `activo`.
- Montos `> 0` donde aplique; nunca negativos.
- Acciones explicitas (POST + CSRF + transaccion cuando el patron lo permita).
- Auditoria con `AuditService` si esta disponible.
- Baja de trabajador y documentos = baja logica, nunca borrado fisico.

## No tocar (prohibido en este bloque)

- Caja (`cajas`, `movimientos_caja`, `cortes_caja`, `categorias_movimientos`);
- categoria "Nomina" que mueva Caja;
- salida real de dinero / conciliacion;
- edicion manual destructiva de saldos;
- conversion/fusion de `usuarios` existentes en trabajadores;
- ALTER destructivo sobre `usuarios`; borrado de usuarios; permisos profundos / auth;
- CxC; `/api/sync`;
- migraciones destructivas; eliminacion de tablas/columnas; borrado de datos;
- push; produccion; secrets; refactors grandes.

## Rollback conceptual

- NP-0 (solo docs): revertir el commit documental.
- NP-A en adelante (migraciones): cada migracion aditiva creara tablas vacias nuevas con
  bloque de rollback comentado (`DROP TABLE` solo si no hay datos y con autorizacion
  explicita). Backup previo obligatorio antes de cualquier escritura local de prueba.
- Nunca borrar datos reales sin autorizacion explicita y backup verificado.

## Estado de NP-0

Contrato, diagnostico y diseno de las 6 tablas completados. No se implemento
funcionalidad, no se crearon migraciones aplicadas y no se escribio en la base de datos.
Siguiente subfase: NP-A (migraciones aditivas + ficha basica de trabajador), solo tras
confirmar este contrato.

## Resultado NP-A migracion base

Estado tecnico: `MIGRACION_NP_A_PERSONAL_BASE_COMPLETADA_QA_DIFERIDA`.

- Backup valido: `src/storage/backups/phase_np_a_20260616_021311_before_personal_base_medisoft_hoteles_import.sql`.
- SHA256: `0F9E64B040437A73D559534E5753133F4C3508C29F5B9EA0278B351097666246`.
- Migracion: `migrations/20260616_001_fase_np_a_personal_base.sql`.
- Batch local: `19`.
- Tablas creadas vacias:
  - `trabajadores`;
  - `trabajador_pagos`;
  - `trabajador_anticipos`;
  - `trabajador_prestamos`;
  - `trabajador_asistencias`;
  - `trabajador_documentos`.
- No se insertaron trabajadores ni movimientos.
- No se creo categoria Nomina ni movimientos de Caja.
- `/api/sync` sigue fuera de alcance.

Documento: `docs/fase_NP_A_migracion_personal_base.md`.
