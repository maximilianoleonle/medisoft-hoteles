# Contrato previo — Clasificación comercial del catálogo de módulos (2026-07-24)

Cumple `docs/contrato_operativo_cambios_criticos.md` y AGENTS.md. Mandato del owner: preparar
catálogo para paquete base / opcionales / internos / bloqueados / reportes individuales /
planes antiguos, con bloqueo real en servidor y exclusión del cobro. Solo local; sin commit,
push ni producción.

## Objetivo

1. Columnas `tipo_comercial` (base|opcional|interno) y `motivo_bloqueo` en `modulos`
   (`activo_global` ya existe; se reutiliza).
2. Paquete base autorizado: dashboard, habitaciones, reservaciones, huespedes, caja,
   usuarios, roles_avanzados ("Roles y permisos"), mantenimiento — es_core=1, precio 0.
3. Internos no vendibles: configuracion (solo equipo Medisoft), anticipos (es_core=1 para
   no romper flujos de Reservaciones/Caja), reportes (contenedor del Centro de Reportes),
   más re-registro trazable de `tareas` y `reportes_distribucion` (interno, bloqueado).
4. Opcionales: documentos/facturacion/compras/camarista (bloqueados: auditoría pendiente),
   inventario (disponible si su suite pasa). Resto de opcionales no autorizados: bloqueados
   provisionalmente. Obligatorios: llaves_remotos, cuentas_cobrar, vehiculos con su motivo.
5. Reportes individuales como módulos: reporte_ingresos_egresos, reporte_procedencia
   (incluye ranking), reporte_habitaciones_rentables, reporte_ocupacion,
   reporte_promedio_estancia — precio provisional 0, bloqueados hasta precio autorizado.
   Gates de servidor por pantalla/gráfica/export; export exige además `exportaciones`.
6. Planes: Básico=paquete base exacto (sin `reportes` general), Pro/Premium `activo=0`
   conservando `plan_modulos`, Personalizado intacto.
7. Cobro mensual: solo opcionales activos globales contratados; internos/bloqueados/base
   jamás suman; `precio_override` sigue; POST manipulado no reactiva bloqueados/internos.

## Archivos que voy a tocar

- `migrations/20260724_001_clasificacion_comercial_modulos.sql` (nueva, idempotente).
- `src/app/models/Modulo.php` — tipos, cobro, congelar internos/bloqueados en
  `actualizarModulosHotel`, invalidación de caché al bloquear global.
- `src/app/models/Plan.php` — presets no reactivan bloqueados/internos (lectura).
- `src/app/controllers/SaasAdminController.php` — no aplicar planes inactivos.
- `src/app/controllers/ReportesController.php` — gates por reporte (before/acciones,
  exportarPdf, datosGrafica). SIN tocar cálculos ni queries de datos.
- `src/app/controllers/ConfiguracionController.php` — gate isSaasAdmin() en `before()`.
- `src/app/helpers/modulos.php` — mapa reporte→módulo + helpers de gate.
- `src/app/views/layout/sidebar.php`, `src/config/navegacion.php`,
  `src/app/helpers/navegacion.php` (flag solo_medisoft), `src/app/helpers/footer_nav.php`
  (solo si lista reportes/configuración) — visibilidad de menú.
- `src/app/views/reportes/index.php` — tarjetas condicionadas por módulo contratado.
- `src/app/views/admin/saas/hotel_detalle.php`, `src/app/views/admin/saas/modulos.php` —
  FASE 10 mínima: internos fuera de contratación, motivo de bloqueo visible.
- `src/tests/casos/` — casos nuevos; `src/tools/saas/` — script de verificación.
- `src/database/schema.sql` — regenerado tras migración.
- `CLAUDE.md` / `docs/PLAYBOOK-SAAS-VERTICALES.md` — alimentación al cierre.

## Archivos que NO voy a tocar

- PWA: service-worker.js, pwa.js, offline-data.js, reservaciones-offline.js, caches,
  IndexedDB, `/api/sync` (sigue 423 `sync_temporarily_disabled`), PwaPushController.
- Cálculos/lógica de Caja, cortes, movimientos; check-in/check-out; AnticipoService;
  CuentaPorCobrar*Service; estados de reservación/habitación.
- `ReservacionController` (sus gates de `anticipos` quedan cubiertos con es_core=1).
- `TarifasController`, `RolController` (rutas /configuracion/tarifas|roles siguen del hotel).
- `config/permisos.php` y lógica RBAC (roles de Max ya corregidos, no se retocan).
- Queries de datos de `Reporte.php` (los cálculos corregidos no se modifican).
- Migraciones previas (incluidas las de retiro 20260722/23, se conservan tal cual).
- Trabajo local sin commit de otros agentes (se conserva íntegro).

## Flujos que pueden afectarse

- Visibilidad de menú y acceso a módulos bloqueados globalmente (esperado: 403/redirect).
- Centro de Reportes: acceso pasa de módulo `reportes` a "≥1 reporte permitido";
  mantenimiento (base) garantiza reportes de mantenimiento para todo hotel.
- /configuracion (index/update/backup) queda solo-Medisoft.
- Cobro mensual SaaS deja de sumar internos (anticipos $129, reportes $249) y bloqueados.
- Guardado de bloques por hotel ya no puede activar bloqueados ni tocar internos
  (además corrige bug latente: checkbox disabled no viaja en POST y hoy desactivaría
  el módulo bloqueado, borrando historial de contratación).

## Datos/tablas que se leen

`modulos`, `hotel_modulos`, `planes`, `plan_modulos`, `hoteles`, `saas_admins`.

## Datos/tablas que se escriben

- `modulos` (columnas nuevas + clasificación + 5+2 filas nuevas re-registradas).
- `planes` (activo de pro/premium), `plan_modulos` (preset básico: incluido on/off, sin DELETE).
- `hotel_modulos`: NO se borra ni desactiva nada por migración. Sin cambios en tablas operativas.

## Formularios afectados

- `form-modulos-hotel` (hotel_detalle.php): mismos action/method/name/CSRF; solo se
  filtran filas mostradas y disabled/motivo. Formularios de planes: sin cambios de contrato.

## Pruebas manuales/automáticas obligatorias

Suite completa antes (baseline) y después; casos nuevos: base siempre activo, mantenimiento
y roles no desactivables, configuración solo-Medisoft, inventario sin compras, checkout→
limpieza sin camarista, llaves/CxC no activables, bloqueado/interno no cobra, Pro/Premium
inactivos conservados, Básico exacto, gates por reporte (procedencia no abre ocupación),
gráficas con tenancy, export exige exportaciones, migración re-ejecutable, /api/sync 423,
sin regresión en Caja/Reservaciones/check-in/check-out (suite existente).
Validaciones: php -l por archivo, lint_tenancy, git diff --check, QA visual del panel SaaS.

## Comandos de validación

- `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tests/run.php`
- `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php -l /var/www/html/...`
- `php src/tools/lint_tenancy.php` · `git diff --check`
- `MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tools/saas/verificar_clasificacion_comercial.php`

## Cómo se revierte si rompe algo

1. Respaldo previo: `backups/respaldo_catalogo_modulos_20260724.sql` (modulos,
   hotel_modulos, planes, plan_modulos con datos) — restaurar con
   `docker exec -i medisoft_hoteles_db mysql -umedisoft_user -pmedisoft_pass medisoft_hoteles_import < backups/respaldo_catalogo_modulos_20260724.sql`.
2. Código: `git checkout -- <archivos tocados>` (cambios de otros agentes quedan intactos
   porque los archivos que toco no se solapan salvo Modulo.php/Plan.php — para esos,
   revertir solo mis hunks con git diff guardado).
3. La migración es idempotente y NO destruye datos: no borra filas ni columnas; el DROP de
   columnas nuevas solo en reversión manual documentada en la propia migración.

## Decisión del mentor/jefe

- [x] Aprobado — el mandato del owner (2026-07-24) define alcance, reglas y entregables;
  esta ficha lo instrumenta sin ampliarlo.
