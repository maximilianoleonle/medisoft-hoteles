# Fase 1.2 - Herramienta local de verificacion SaaS

## Objetivo

La Fase 1.2 agrega una herramienta interna de solo lectura para revisar el estado local de la base SaaS multi-hotel sin integrar todavia el tenant al flujo real del sistema.

La herramienta valida que la base local tenga las tablas SaaS esperadas, el hotel inicial Los Cedros, su configuracion base, feature flags, relaciones de usuarios y que la columna `hotel_id` no se haya agregado todavia a tablas operativas.

## Archivo creado

- `src/tools/saas/verificar_estado.php`

El archivo esta fuera de `public_html`, por lo que no queda expuesto como ruta publica del sistema.

## Como ejecutarla

Desde la raiz del proyecto:

```bash
docker compose exec app php tools/saas/verificar_estado.php
```

La herramienta debe ejecutarse dentro del contenedor `app`, donde `APP_ENV` debe estar configurado como `local`.

## Que verifica

La herramienta revisa:

- Que existan las tablas SaaS:
  - `migrations`
  - `hoteles`
  - `hotel_configuracion`
  - `hotel_usuarios`
  - `logs_auditoria`
- Que esten registradas como ejecutadas las migraciones:
  - `20260526_001_crear_base_saas_multihotel.sql`
  - `20260526_002_seed_hotel_los_cedros.sql`
- Que exista el hotel:
  - Nombre: `Los Cedros`
  - Slug: `los-cedros`
  - Codigo: `LOS_CEDROS`
- Que existan las configuraciones base:
  - `hotel.nombre`
  - `hotel.direccion`
  - `hotel.zona_horaria`
  - `hotel.moneda_codigo`
  - `hotel.moneda_simbolo`
  - `hotel.habitaciones_actuales`
  - `sistema.modo_compatibilidad_mono_hotel`
- Que existan los feature flags:
  - `feature.multi_hotel`
  - `feature.selector_hotel`
  - `feature.audit_logs`
  - `feature.hotel_configuracion`
  - `feature.saas_billing`
  - `feature.bitacora_inteligente`
  - `feature.whatsapp_ejecutivo`
  - `feature.ia_ejecutiva`
- Que haya relaciones en `hotel_usuarios` para Los Cedros.
- Que `hotel_id` solo exista por ahora en:
  - `hotel_configuracion`
  - `hotel_usuarios`
  - `logs_auditoria`
- Que `hotel_id` no exista todavia en tablas operativas conocidas como:
  - `habitaciones`
  - `reservaciones`
  - `huespedes`
  - `cajas`
  - `cortes_caja`
  - `movimientos_caja`
  - `inventario_productos`
  - `tarifas_temporada`

## Que no modifica

La herramienta no:

- Ejecuta migraciones.
- Inserta datos.
- Actualiza datos.
- Borra datos.
- Modifica tablas operativas.
- Cambia login o sesiones.
- Cambia dashboard.
- Cambia consultas existentes.
- Integra `TenantContext`.
- Agrega `hotel_id` a tablas existentes.

Todas sus consultas son de lectura.

## Como interpretar resultados

- `[OK]`: la verificacion paso correctamente.
- `[WARN]`: hay algo que revisar, pero no rompe la validacion general. Por ejemplo, una tabla operativa esperada no existe con ese nombre en la base actual.
- `[ERROR]`: hay un problema critico. Por ejemplo, falta una tabla SaaS, falta el hotel Los Cedros o `hotel_id` aparece en una tabla operativa.

Al final muestra:

- Total de verificaciones OK.
- Total de warnings.
- Total de errores.
- Resultado general:
  - `PASS` si no hay errores.
  - `FAIL` si existe al menos un error critico.

## Riesgos

El riesgo es bajo porque el script es local, CLI y de solo lectura.

Riesgos a vigilar:

- Ejecutarlo fuera de `APP_ENV=local`: el script se bloquea.
- Ejecutarlo contra una base incorrecta: reportara errores si no encuentra la estructura SaaS esperada.
- Interpretar un `[WARN]` como fallo critico: los warnings son avisos de revision, no bloqueo.

## Rollback

No hay rollback de base de datos porque esta fase no modifica datos.

Si se necesita revertir la herramienta antes de commit:

```bash
Remove-Item -LiteralPath "src\tools\saas\verificar_estado.php"
Remove-Item -LiteralPath "docs\fase_1_2_migration_tool.md"
```

Si ya fue versionada:

```bash
git rm src/tools/saas/verificar_estado.php docs/fase_1_2_migration_tool.md
git commit -m "revert: remove local saas verification tool"
```

## Siguiente fase recomendada

Antes de avanzar a Fase 2, usar esta herramienta como checklist local para confirmar que la base SaaS inicial sigue consistente.

La siguiente fase debe seguir siendo gradual: primero disenar el orden de integracion por modulo, preparar pruebas de aislamiento y solo despues empezar a agregar `hotel_id` a tablas operativas con migraciones controladas.
