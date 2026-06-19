# Operacion diaria - paquete para rediseño

Objetivo: rediseñar la vista `Operacion diaria` sin omitir ninguna seccion ni cambiar la logica PHP/MVC.

Ruta funcional:
- URL: `/operacion/diaria`
- Controller: `src/app/controllers/OperacionController.php`
- View: `src/app/views/operacion/diaria.php`
- Model read-only: `src/app/models/OperacionDiaria.php`
- Route: `src/config/routes.php`
- Sidebar entry: `src/app/views/layout/sidebar.php`

Alcance del rediseño:
- Puedes rehacer HTML/CSS de la vista `src/app/views/operacion/diaria.php`.
- Conserva las variables PHP, helpers y datos existentes.
- Conserva todos los enlaces existentes.
- Mantén la pantalla como tablero read-only.
- No agregues acciones que cambien estados.
- No agregues formularios de guardado.
- No agregues llamadas nuevas a endpoints.

Secciones que NO se deben omitir:
- Hero / encabezado: kicker, titulo "Tablero operativo diario", subtitulo y acciones a Dashboard y Reporte TLM.
- Resumen operativo diario con 8 metricas:
  - Habitaciones
  - Disponibles
  - Ocupadas
  - Llegadas hoy
  - Tareas activas
  - Mantenimiento
  - CxC estimada
  - CxC pendientes
- Panel "KPIs financieros estimados".
- Alerta read-only de CxC con enlace a Cuentas por cobrar.
- Panel "Reservaciones y ocupacion".
- Panel "Riesgo operativo".
- Panel "Agenda de hoy" con tabla y estado vacio.
- Panel "Tareas prioritarias" con estado vacio.
- Panel "Mantenimiento reciente" con estado vacio.
- Panel "Documentos recientes" con estado vacio.
- Alerta final: la pantalla es solo lectura y no usa `/api/sync`.

Restricciones criticas del proyecto:
- No tocar service workers, PWA, IndexedDB, cache names ni `/api/sync`.
- No tocar migraciones, base de datos, modelos, rutas, permisos ni auth.
- No tocar logica profunda de reservaciones, check-in/check-out, caja, cortes, reportes o calculos financieros.
- `/api/sync` debe seguir bloqueado con HTTP 423 y JSON `sync_temporarily_disabled`.
- Si agregas o modificas formularios, no cambies `action`, `method`, `name`, CSRF ni hidden inputs. En esta vista idealmente no agregues formularios.

Lenguaje visual esperado:
- Usar branding del hotel con tokens `--brand-*`, no tokens `--ms-*`.
- Seguir la secuencia visual ya aplicada en Tarifas dinamicas, Notificaciones, Configuracion, Inventario, Huespedes y Reservaciones:
  - Icono cuadrado con degradado.
  - Kicker "Operacion hotelera".
  - Titulo serif grande.
  - Subtitulo alineado y sobrio.
  - Superficies limpias, blancas o marfil suave.
  - Cards con jerarquia clara, sin saturar.
  - Responsive desktop/mobile.

Archivos de referencia visual incluidos:
- `references/current-visual-language/tarifas-index.php`
- `references/current-visual-language/notificaciones-index.php`
- `references/current-visual-language/configuracion-index.php`
- `references/current-visual-language/inventario-index.php`
- `references/current-visual-language/huespedes-index.php`
- `references/current-visual-language/reservaciones-index.php`

Notas para implementacion:
- El modelo entrega el arreglo `$reporte` con subarreglos `habitaciones`, `reservaciones`, `tareas`, `mantenimiento`, `trabajadores`, `documentos` y `cuentas_por_cobrar`.
- La vista actual define helpers `op_daily_safe`, `op_daily_num`, `op_daily_money` y `op_daily_date`; conservarlos o reemplazarlos solo si mantienes el mismo comportamiento seguro.
- Usa `htmlspecialchars` o los helpers existentes para cualquier salida dinamica.
- Validar con `php -l src/app/views/operacion/diaria.php` cuando se implemente el diseño en el proyecto.
