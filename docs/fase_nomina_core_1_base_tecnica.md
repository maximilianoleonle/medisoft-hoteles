# Nomina Core - Fase 1: base tecnica del modulo (cierre)

Fecha: 2026-07-04
Bloque comercial: `nomina_avanzada` ($399/mes, opt-in, categoria administracion, ruta `/nomina`)
Relacion con `personal`: lo complementa; NO lo reemplaza, NO cambia su precio ni sus rutas.

## Alcance implementado

- Bloque `nomina_avanzada` en el catalogo `modulos` + preset del plan premium. Opt-in
  (sin activacion retroactiva): ningun hotel existente cambia de comportamiento ni de cobro.
- Permisos `nomina.*` (12) en `config/permisos.php` con cruce `modulo => nomina_avanzada`
  (la UI de roles bloquea los checkboxes si el hotel no contrata el bloque).
  Presets: gerente += `nomina.all`; administrador += `nomina.view`, `nomina.incidencias`,
  `nomina.calcular`. Hoteles existentes cubiertos por la migracion (JSON_ARRAY_APPEND
  idempotente, solo roles `es_sistema = 1`, omite comodin `*`).
- 7 claves `nomina.*` en `ConfiguracionHotelRegistry` (modo, pais, redondeo y 4 politicas).
- `NominaController` con gate estandar: requireAuth -> require_hotel_context ->
  require_hotel_module('nomina_avanzada') -> require_permission('nomina.view').
  Configuracion en pantalla PROPIA (`/nomina/configuracion`) tras `nomina.configurar`
  (no en /configuracion global, que ve cualquier gerente). Guardado con CSRF, whitelist
  de valores en servidor, upsert a `hotel_configuracion` (grupo nomina), invalidacion de
  cache y `AuditService::record` con datos_antes/datos_despues.
- Dashboard `/nomina` solo lectura: KPIs desde el subsistema laboral existente
  (defensivo: sin bloque personal o sin tablas muestra estado vacio, no rompe).
- Navegacion: sidebar (4 ediciones estandar), `config/navegacion.php` (buscador/favoritos).
- Preflight propio: `src/tools/saas/preflight_nomina_core.php` (27 checks).

## Archivos

Nuevos: `migrations/20260704_001_nomina_core_bloque_permisos.sql`,
`src/app/controllers/NominaController.php`, `src/app/views/nomina/index.php`,
`src/app/views/nomina/configuracion.php`, `src/tools/saas/preflight_nomina_core.php`.
Modificados: `src/config/permisos.php`, `src/config/routes.php`, `src/config/navegacion.php`,
`src/app/views/layout/sidebar.php`, `src/app/models/ConfiguracionHotelRegistry.php`.

Tablas creadas: NINGUNA (solo filas en modulos/plan_modulos y permisos en roles).
Rutas nuevas: GET `/nomina`, GET+POST `/nomina/configuracion`.

## Pruebas y resultado

- `php -l` en los 9 PHP tocados: sin errores.
- Migracion aplicada en BD local real (`medisoft_hoteles_import`).
- `preflight_nomina_core.php`: 27 OK / 1 WARNING / 0 ERROR.
  - WARNING restante (deliberado): `DB_STRICT_ERRORS` inactivo — requisito de diseño para
    el motor de calculo (Fase 3+): PDO estricto obligatorio, un SELECT roto no debe
    producir nominas en ceros.
- Test funcional con sesion real (patron tools/test_bloques_funcional.sh): 12/12 PASS.
  Gate ON/OFF del bloque, visibilidad de menu, contenido del dashboard, denegacion de
  configuracion a rol administrador (GET y POST), POST denegado no escribe, y aislamiento
  de claves de config por hotel.
- No-regresion: `preflight_frontera_nomina_oficial.php` no reporta NINGUN error de nomina
  (rutas/simbolos/migraciones limpias). Su unico ERROR es preexistente y ajeno al diff:
  "/api/sync no muestra el bloqueo esperado" (zona critica, no tocada; ver pendientes).
- Backfill aplicado: `hotel_usuarios.role_id` de `qa_bloques` (unico activo en NULL),
  con el UPDATE canonico de 20260628_001. Quedan 0 activos sin role_id.

## Riesgos pendientes

1. PREEXISTENTE (no de esta fase): /api/sync no responde el 423 esperado por el preflight
   de frontera. Requiere revision aparte del owner; es limite critico de AGENTS.md.
2. `DB_STRICT_ERRORS` inactivo: aceptado hasta Fase 3, donde el motor usara PDO estricto.
3. Decision comercial delegada: si un hotel debe poder contratar nomina_avanzada SIN
   personal, hoy puede (el dashboard lo avisa y guia); operar nomina sin empleados no hace nada.

## Siguiente fase

Fase 2: catalogos internos (departamentos, puestos, tipos de contrato, grupos de nomina,
conceptos), extension aditiva de `trabajadores` (asignaciones NULL-ables) e historial
salarial con vigencias (`trabajador_salarios`) con backfill desde `salario_base`.
