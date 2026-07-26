<?php
/**
 * Catalogo de pantallas del hotel para navegacion rapida.
 *
 * Fuente unica para: registro de vistas recientes, accesos rapidos del
 * sidebar y busqueda de pantallas en el buscador global.
 *
 * Reglas de visibilidad por entrada (misma logica que sidebar.php):
 *   - 'modulo': clave del modulo que debe estar activo en el hotel
 *     (hotel_menu_module_enabled). null = sin requisito de modulo.
 *   - 'permiso': el permiso que exige la pantalla. Puede ser un string o una
 *     lista any-of (ej. Limpieza: ['camarista.view','tareas.view']).
 *     null = solo aplica el requisito de modulo.
 *
 * UNA SOLA VERDAD (auditoria de accesos, 23 jul 2026): el menu se gatea SOLO
 * por permiso. Antes existia ademas 'roles' (lista de rol-string) evaluado en
 * OR, y eso rompia por dos lados:
 *   - un rol personalizado guarda el ENUM legacy 'recepcionista' (ver
 *     UsuarioController::resolverAsignacionRol), asi que NUNCA cumplia el gate
 *     por rol aunque el propietario le diera el permiso;
 *   - al reves, un gerente veia entradas cuyo controlador luego le rechazaba,
 *     porque el OR le abria el menu sin tener el permiso.
 * Ahora lo que se ve es exactamente lo que el servidor deja pasar.
 *
 * 'buscar' son palabras clave adicionales para la busqueda de pantallas.
 */

return [
    ['ruta' => 'dashboard',             'etiqueta' => 'Inicio',              'icono' => 'fa-compass',             'modulo' => 'dashboard',        'permiso' => null, 'roles' => null, 'buscar' => 'dashboard inicio panel resumen'],
    ['ruta' => 'operacion/diaria',      'etiqueta' => 'El hotel hoy',        'icono' => 'fa-clipboard-check',     'modulo' => 'tablero_ejecutivo','permiso' => 'operacion.view', 'roles' => null, 'buscar' => 'operacion diaria tablero ejecutivo pendientes hoy resumen'],
    ['ruta' => 'forecast',              'etiqueta' => 'Pronóstico de ocupación', 'icono' => 'fa-arrow-trend-up',  'modulo' => 'forecast',         'permiso' => 'forecast.view', 'roles' => null, 'buscar' => 'forecast pronostico proyeccion ocupacion temporadas eventos'],
    ['ruta' => 'habitaciones',          'etiqueta' => 'Habitaciones',        'icono' => 'fa-bed',                 'modulo' => 'habitaciones',     'permiso' => 'habitaciones.view', 'roles' => null, 'buscar' => 'cuartos ocupacion limpieza mantenimiento'],
    ['ruta' => 'areas',                 'etiqueta' => 'Áreas del hotel',     'icono' => 'fa-map-location-dot',    'modulo' => 'habitaciones',     'permiso' => 'habitaciones.view', 'roles' => null, 'buscar' => 'alberca lobby jardin estacionamiento zonas espacios'],
    ['ruta' => 'mapa',                  'etiqueta' => 'Mapa del hotel',      'icono' => 'fa-map',                 'modulo' => 'habitaciones',     'permiso' => 'habitaciones.view', 'roles' => null, 'buscar' => 'mapa pisos plano estados habitaciones areas'],
    ['ruta' => 'reservaciones',         'etiqueta' => 'Reservaciones',       'icono' => 'fa-calendar-check',      'modulo' => 'reservaciones',    'permiso' => 'reservaciones.view', 'roles' => null, 'buscar' => 'reservas checkin checkout entradas salidas'],
    ['ruta' => 'reservaciones/crear',   'etiqueta' => 'Nueva reservación',   'icono' => 'fa-calendar-plus',       'modulo' => 'reservaciones',    'permiso' => 'reservaciones.view', 'roles' => null, 'buscar' => 'crear reserva nueva'],
    ['ruta' => 'huespedes',             'etiqueta' => 'Huéspedes',           'icono' => 'fa-users',               'modulo' => 'huespedes',        'permiso' => 'huespedes.view', 'roles' => null, 'buscar' => 'clientes visitantes vehiculos'],
    ['ruta' => 'checkin-digital',       'etiqueta' => 'Check-in digital',    'icono' => 'fa-qrcode',              'modulo' => 'checkin_digital',  'permiso' => 'checkin_digital.view', 'roles' => null, 'buscar' => 'pre-registro links qr codigo digital'],
    ['ruta' => 'caja',                  'etiqueta' => 'Caja',                'icono' => 'fa-wallet',              'modulo' => 'caja',             'permiso' => 'caja.view', 'roles' => null, 'buscar' => 'dinero cobros turno'],
    ['ruta' => 'caja/movimientos',      'etiqueta' => 'Movimientos de caja', 'icono' => 'fa-list-ul',             'modulo' => 'caja',             'permiso' => 'caja.view', 'roles' => null, 'buscar' => 'ingresos gastos'],
    ['ruta' => 'caja/corte',            'etiqueta' => 'Corte de caja',       'icono' => 'fa-cash-register',       'modulo' => 'caja',             'permiso' => 'caja.view', 'roles' => null, 'buscar' => 'cierre arqueo turno'],
    ['ruta' => 'caja/historial',        'etiqueta' => 'Historial de cortes', 'icono' => 'fa-clock-rotate-left',   'modulo' => 'caja',             'permiso' => 'caja.view', 'roles' => null, 'buscar' => 'cortes anteriores'],
    ['ruta' => 'facturacion',           'etiqueta' => 'Facturación',         'icono' => 'fa-file-invoice',        'modulo' => 'facturacion',      'permiso' => 'facturacion.view', 'roles' => null, 'buscar' => 'facturas fiscal rfc'],
    ['ruta' => 'cuentas-por-pagar',     'etiqueta' => 'Cuentas por pagar',   'icono' => 'fa-file-invoice-dollar', 'modulo' => 'compras',          'permiso' => 'cuentas_por_pagar.view', 'roles' => null, 'buscar' => 'cxp proveedores pagos'],
    ['ruta' => 'mantenimientos/activos','etiqueta' => 'Mantenimiento',       'icono' => 'fa-toolbox',             'modulo' => 'mantenimiento','permiso' => ['tareas.view', 'habitaciones.mantenimiento'], 'roles' => null, 'buscar' => 'activos preventivo boiler servicio incidencias evidencia'],
    ['ruta' => 'camarista',             'etiqueta' => 'Limpieza',            'icono' => 'fa-broom',               'modulo' => 'camarista',        'permiso' => ['camarista.view', 'tareas.view'], 'roles' => null, 'buscar' => 'camarista housekeeping'],
    ['ruta' => 'lavanderia',            'etiqueta' => 'Lavandería',          'icono' => 'fa-shirt',               'modulo' => 'lavanderia',       'permiso' => 'lavanderia.view', 'roles' => null, 'buscar' => 'blancos sabanas toallas lavado ropa pedidos tintoreria'],
    ['ruta' => 'inventario',            'etiqueta' => 'Inventarios',         'icono' => 'fa-boxes-stacked',       'modulo' => 'inventario',       'permiso' => 'inventarios.view', 'roles' => null, 'buscar' => 'productos stock existencias'],
    ['ruta' => 'compras',               'etiqueta' => 'Compras',             'icono' => 'fa-clipboard-list',      'modulo' => 'compras',          'permiso' => 'compras.view', 'roles' => null, 'buscar' => 'ordenes recepcion'],
    ['ruta' => 'proveedores',           'etiqueta' => 'Proveedores',         'icono' => 'fa-truck',               'modulo' => 'compras',          'permiso' => 'proveedores.view', 'roles' => null, 'buscar' => 'suministros'],
    ['ruta' => 'documentos',            'etiqueta' => 'Documentos',          'icono' => 'fa-folder-open',         'modulo' => 'documentos',       'permiso' => 'documentos.view', 'roles' => null, 'buscar' => 'archivos expedientes'],
    ['ruta' => 'motor-reservas',        'etiqueta' => 'Reservas en línea',   'icono' => 'fa-globe',               'modulo' => 'motor_reservas',   'permiso' => 'motor_reservas.view', 'roles' => null, 'buscar' => 'motor de reservas online booking web pagina publica'],
    ['ruta' => 'canales',               'etiqueta' => 'Airbnb y Booking',    'icono' => 'fa-calendar-alt',        'modulo' => 'canales_ical',     'permiso' => 'canales.view', 'roles' => null, 'buscar' => 'canales ical ota booking airbnb sincronizacion calendarios'],
    ['ruta' => 'whatsapp',              'etiqueta' => 'Conectar WhatsApp',   'icono' => 'fab fa-whatsapp',        'modulo' => 'whatsapp',         'permiso' => 'whatsapp.view', 'roles' => null, 'buscar' => 'whatsapp numero conexion mensajes confirmaciones'],
    ['ruta' => 'mensajes',              'etiqueta' => 'Mensajes a huéspedes','icono' => 'fa-comment-dots',        'modulo' => 'canal_whatsapp',   'permiso' => 'mensajes.view', 'roles' => null, 'buscar' => 'whatsapp cola confirmaciones recordatorios encuestas anticipo huesped'],
    ['ruta' => 'ia/resumen-diario',     'etiqueta' => 'Asesor inteligente',  'icono' => 'fa-wand-magic-sparkles', 'modulo' => 'ia_ejecutiva',     'permiso' => 'ia.view', 'roles' => null, 'buscar' => 'resumen asesor inteligente ejecutivo'],
    // OJO: permiso/roles se evaluan en OR; el Guardian se gatea SOLO por permiso
    // (roles=null) para que un administrador sin guardian.view no lo vea.
    ['ruta' => 'ia/vigilancia-financiera', 'etiqueta' => 'Guardián financiero', 'icono' => 'fa-shield-halved',   'modulo' => 'ia_ejecutiva',     'permiso' => 'guardian.view', 'roles' => null, 'buscar' => 'fuga auditoria forense conciliacion semaforo vigilancia financiera guardian patrones'],
    ['ruta' => 'copiloto/valor',        'etiqueta' => 'Uso del asistente',   'icono' => 'fa-robot',               'modulo' => 'copiloto',         'permiso' => 'copiloto.valor', 'roles' => null, 'buscar' => 'copiloto asistente ia uso valor numeros'],
    ['ruta' => 'reputacion',            'etiqueta' => 'Opiniones y encuestas', 'icono' => 'fa-star',              'modulo' => 'reputacion',       'permiso' => 'reputacion.view', 'roles' => null, 'buscar' => 'reputacion reseñas encuestas nps satisfaccion opiniones'],
    ['ruta' => 'lealtad',               'etiqueta' => 'Huésped frecuente',   'icono' => 'fa-heart',               'modulo' => 'lealtad',          'permiso' => 'lealtad.view', 'roles' => null, 'buscar' => 'lealtad puntos recompensas frecuente regresa'],
    ['ruta' => 'night-audit',           'etiqueta' => 'Cierre del día',      'icono' => 'fa-moon',                'modulo' => 'night_audit',      'permiso' => 'night_audit.view', 'roles' => null, 'buscar' => 'night audit cierre nocturno no-show noshow checkouts vencidos cortes'],
    // El modulo `reportes` es contenedor INTERNO y ya no da acceso: el centro
    // abre si el hotel tiene >=1 pantalla de reporte contratada (mapa
    // hotel_report_screen_modules); mantenimiento es base, asi que siempre existe.
    ['ruta' => 'reportes',              'etiqueta' => 'Reportes',            'icono' => 'fa-chart-line',          'modulo' => ['reporte_ingresos_egresos', 'reporte_procedencia', 'reporte_habitaciones_rentables', 'reporte_ocupacion', 'reporte_promedio_estancia', 'mantenimiento', 'camarista', 'limpieza', 'tablero_ejecutivo'], 'permiso' => 'reportes.view', 'roles' => null, 'buscar' => 'estadisticas graficas exportar'],
    ['ruta' => 'usuarios',              'etiqueta' => 'Usuarios',            'icono' => 'fa-user',                'modulo' => 'usuarios',         'permiso' => 'usuarios.view', 'roles' => null, 'buscar' => 'cuentas accesos'],
    ['ruta' => 'trabajadores',          'etiqueta' => 'Personal',            'icono' => 'fa-id-card',             'modulo' => 'personal',         'permiso' => 'personal.view', 'roles' => null, 'buscar' => 'empleados nomina pagos'],
    ['ruta' => 'trabajadores/informes', 'etiqueta' => 'Personal · Informes', 'icono' => 'fa-chart-pie',           'modulo' => 'personal',         'permiso' => 'personal.view', 'roles' => null, 'buscar' => 'reportes informes auditoria expediente conciliacion snapshots'],
    ['ruta' => 'nomina',                'etiqueta' => 'Nómina',              'icono' => 'fa-money-check-dollar',  'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => null, 'buscar' => 'nomina sueldos salarios periodos incidencias'],
    ['ruta' => 'nomina/empleados',      'etiqueta' => 'Nómina · Empleados',  'icono' => 'fa-address-book',        'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => null, 'buscar' => 'empleados ficha salario puesto departamento'],
    ['ruta' => 'nomina/catalogos',      'etiqueta' => 'Nómina · Catálogos',  'icono' => 'fa-layer-group',         'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => null, 'buscar' => 'puestos departamentos contratos grupos conceptos'],
    ['ruta' => 'nomina/incidencias',    'etiqueta' => 'Nómina · Incidencias','icono' => 'fa-clipboard-list',      'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => null, 'buscar' => 'faltas horas extra bonos descuentos incidencias'],
    ['ruta' => 'nomina/periodos',       'etiqueta' => 'Nómina · Periodos',   'icono' => 'fa-calendar-week',       'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => null, 'buscar' => 'periodos cierre calculo preview nomina'],
    ['ruta' => 'notificaciones',        'etiqueta' => 'Notificaciones',      'icono' => 'fa-bell',                'modulo' => 'notificaciones',   'permiso' => 'notificaciones.view', 'roles' => null, 'buscar' => 'alertas avisos'],
    // solo_medisoft: /configuracion (index/update/backup) es del equipo Medisoft
    // (saas_admins), no del hotel. nav_pantalla_visible la esconde y el
    // ConfiguracionController::before la bloquea con isSaasAdmin().
    ['ruta' => 'configuracion',         'etiqueta' => 'Configuración',       'icono' => 'fa-cog',                 'modulo' => 'configuracion',    'permiso' => 'configuracion.view', 'roles' => null, 'solo_medisoft' => true, 'buscar' => 'ajustes hotel backups apariencia'],
    ['ruta' => 'configuracion/tarifas', 'etiqueta' => 'Precios y temporadas','icono' => 'fa-tags',                'modulo' => ['tarifas_dinamicas', 'tarifas'], 'permiso' => 'tarifas.view', 'roles' => null, 'buscar' => 'tarifas dinamicas precios temporadas incrementos'],
    ['ruta' => 'configuracion/roles',   'etiqueta' => 'Roles y permisos',    'icono' => 'fa-user-shield',         'modulo' => 'roles_avanzados',  'permiso' => 'roles.manage', 'roles' => null, 'buscar' => 'rbac accesos permisos'],
    ['ruta' => 'auditoria',             'etiqueta' => 'Historial de actividad', 'icono' => 'fa-clock-rotate-left','modulo' => 'auditoria',        'permiso' => 'auditoria.view', 'roles' => null, 'buscar' => 'bitacora auditoria historial actividad quien hizo cambios'],
];
