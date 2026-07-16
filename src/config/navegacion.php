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
 *   - 'permiso' y 'roles' se evaluan en OR (basta cumplir uno), igual que el
 *     sidebar: can(permiso) || rol del hotel en la lista. Si ambos son null,
 *     solo aplica el requisito de modulo.
 *
 * 'buscar' son palabras clave adicionales para la busqueda de pantallas.
 */

return [
    ['ruta' => 'dashboard',             'etiqueta' => 'Dashboard',           'icono' => 'fa-compass',             'modulo' => 'dashboard',        'permiso' => null, 'roles' => null, 'buscar' => 'inicio panel resumen'],
    ['ruta' => 'operacion/diaria',      'etiqueta' => 'Operación diaria',    'icono' => 'fa-clipboard-check',     'modulo' => 'tablero_ejecutivo','permiso' => null, 'roles' => null, 'buscar' => 'tablero ejecutivo pendientes'],
    ['ruta' => 'habitaciones',          'etiqueta' => 'Habitaciones',        'icono' => 'fa-bed',                 'modulo' => 'habitaciones',     'permiso' => null, 'roles' => null, 'buscar' => 'cuartos ocupacion limpieza mantenimiento'],
    ['ruta' => 'reservaciones',         'etiqueta' => 'Reservaciones',       'icono' => 'fa-calendar-check',      'modulo' => 'reservaciones',    'permiso' => null, 'roles' => null, 'buscar' => 'reservas checkin checkout entradas salidas'],
    ['ruta' => 'reservaciones/crear',   'etiqueta' => 'Nueva reservación',   'icono' => 'fa-calendar-plus',       'modulo' => 'reservaciones',    'permiso' => null, 'roles' => null, 'buscar' => 'crear reserva nueva'],
    ['ruta' => 'huespedes',             'etiqueta' => 'Huéspedes',           'icono' => 'fa-users',               'modulo' => 'huespedes',        'permiso' => null, 'roles' => null, 'buscar' => 'clientes visitantes vehiculos'],
    ['ruta' => 'checkin-digital',       'etiqueta' => 'Check-in digital',    'icono' => 'fa-qrcode',              'modulo' => 'checkin_digital',  'permiso' => null, 'roles' => null, 'buscar' => 'pre-registro links qr codigo digital'],
    ['ruta' => 'caja',                  'etiqueta' => 'Caja',                'icono' => 'fa-wallet',              'modulo' => 'caja',             'permiso' => null, 'roles' => null, 'buscar' => 'dinero cobros turno'],
    ['ruta' => 'caja/movimientos',      'etiqueta' => 'Movimientos de caja', 'icono' => 'fa-list-ul',             'modulo' => 'caja',             'permiso' => null, 'roles' => null, 'buscar' => 'ingresos gastos'],
    ['ruta' => 'caja/corte',            'etiqueta' => 'Corte de caja',       'icono' => 'fa-cash-register',       'modulo' => 'caja',             'permiso' => null, 'roles' => null, 'buscar' => 'cierre arqueo turno'],
    ['ruta' => 'caja/historial',        'etiqueta' => 'Historial de cortes', 'icono' => 'fa-clock-rotate-left',   'modulo' => 'caja',             'permiso' => null, 'roles' => null, 'buscar' => 'cortes anteriores'],
    ['ruta' => 'cuentas-por-cobrar',    'etiqueta' => 'Cuentas por cobrar',  'icono' => 'fa-hand-holding-dollar', 'modulo' => 'cuentas_cobrar',   'permiso' => null, 'roles' => null, 'buscar' => 'cxc deudas creditos'],
    ['ruta' => 'facturacion',           'etiqueta' => 'Facturación',         'icono' => 'fa-file-invoice',        'modulo' => 'facturacion',      'permiso' => null, 'roles' => null, 'buscar' => 'facturas fiscal rfc'],
    ['ruta' => 'cuentas-por-pagar',     'etiqueta' => 'Cuentas por pagar',   'icono' => 'fa-file-invoice-dollar', 'modulo' => 'compras',          'permiso' => null, 'roles' => null, 'buscar' => 'cxp proveedores pagos'],
    ['ruta' => 'tareas',                'etiqueta' => 'Tareas',              'icono' => 'fa-tasks',               'modulo' => 'tareas',           'permiso' => null, 'roles' => null, 'buscar' => 'pendientes operativas'],
    ['ruta' => 'mantenimientos/activos','etiqueta' => 'Mantenimiento',       'icono' => 'fa-toolbox',             'modulo' => 'mantenimiento_plus','permiso' => null, 'roles' => null, 'buscar' => 'activos preventivo boiler servicio incidencias evidencia'],
    ['ruta' => 'camarista',             'etiqueta' => 'Limpieza',            'icono' => 'fa-broom',               'modulo' => 'camarista',        'permiso' => null, 'roles' => null, 'buscar' => 'camarista housekeeping'],
    ['ruta' => 'inventario',            'etiqueta' => 'Inventarios',         'icono' => 'fa-boxes-stacked',       'modulo' => 'inventario',       'permiso' => null, 'roles' => null, 'buscar' => 'productos stock existencias'],
    ['ruta' => 'compras',               'etiqueta' => 'Compras',             'icono' => 'fa-clipboard-list',      'modulo' => 'compras',          'permiso' => null, 'roles' => null, 'buscar' => 'ordenes recepcion'],
    ['ruta' => 'proveedores',           'etiqueta' => 'Proveedores',         'icono' => 'fa-truck',               'modulo' => 'compras',          'permiso' => null, 'roles' => null, 'buscar' => 'suministros'],
    ['ruta' => 'documentos',            'etiqueta' => 'Documentos',          'icono' => 'fa-folder-open',         'modulo' => 'documentos',       'permiso' => null, 'roles' => null, 'buscar' => 'archivos expedientes'],
    ['ruta' => 'motor-reservas',        'etiqueta' => 'Motor de reservas',   'icono' => 'fa-globe',               'modulo' => 'motor_reservas',   'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'reservas online booking web'],
    ['ruta' => 'canales',               'etiqueta' => 'Canales (iCal)',      'icono' => 'fa-calendar-alt',        'modulo' => 'canales_ical',     'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'ota booking airbnb sincronizacion'],
    ['ruta' => 'whatsapp',              'etiqueta' => 'WhatsApp',            'icono' => 'fab fa-whatsapp',        'modulo' => 'whatsapp',         'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'mensajes confirmaciones'],
    ['ruta' => 'mensajes',              'etiqueta' => 'Mensajes',            'icono' => 'fa-comment-dots',        'modulo' => 'canal_whatsapp',   'permiso' => null, 'roles' => null, 'buscar' => 'whatsapp cola confirmaciones recordatorios encuestas anticipo huesped'],
    ['ruta' => 'ia/resumen-diario',     'etiqueta' => 'Asesor inteligente',  'icono' => 'fa-wand-magic-sparkles', 'modulo' => 'ia_ejecutiva',     'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'resumen asesor inteligente ejecutivo'],
    // OJO: permiso/roles se evaluan en OR; el Guardian se gatea SOLO por permiso
    // (roles=null) para que un administrador sin guardian.view no lo vea.
    ['ruta' => 'ia/vigilancia-financiera', 'etiqueta' => 'Vigilancia financiera', 'icono' => 'fa-shield-halved',   'modulo' => 'ia_ejecutiva',     'permiso' => 'guardian.view', 'roles' => null, 'buscar' => 'fuga auditoria forense conciliacion semaforo vigilancia guardian patrones'],
    ['ruta' => 'reputacion',            'etiqueta' => 'Reputación',          'icono' => 'fa-star',                'modulo' => 'reputacion',       'permiso' => null, 'roles' => null, 'buscar' => 'reseñas encuestas nps satisfaccion opiniones'],
    ['ruta' => 'reportes',              'etiqueta' => 'Reportes',            'icono' => 'fa-chart-line',          'modulo' => 'reportes',         'permiso' => null, 'roles' => null, 'buscar' => 'estadisticas graficas exportar'],
    ['ruta' => 'usuarios',              'etiqueta' => 'Usuarios',            'icono' => 'fa-user',                'modulo' => 'usuarios',         'permiso' => 'usuarios.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'cuentas accesos'],
    ['ruta' => 'trabajadores',          'etiqueta' => 'Personal',            'icono' => 'fa-id-card',             'modulo' => 'personal',         'permiso' => 'personal.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'empleados nomina pagos'],
    ['ruta' => 'trabajadores/informes', 'etiqueta' => 'Personal · Informes', 'icono' => 'fa-chart-pie',           'modulo' => 'personal',         'permiso' => 'personal.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'reportes informes auditoria expediente conciliacion snapshots'],
    ['ruta' => 'nomina',                'etiqueta' => 'Nómina',              'icono' => 'fa-money-check-dollar',  'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'nomina sueldos salarios periodos incidencias'],
    ['ruta' => 'nomina/empleados',      'etiqueta' => 'Nómina · Empleados',  'icono' => 'fa-address-book',        'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'empleados ficha salario puesto departamento'],
    ['ruta' => 'nomina/catalogos',      'etiqueta' => 'Nómina · Catálogos',  'icono' => 'fa-layer-group',         'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'puestos departamentos contratos grupos conceptos'],
    ['ruta' => 'nomina/incidencias',    'etiqueta' => 'Nómina · Incidencias','icono' => 'fa-clipboard-list',      'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'faltas horas extra bonos descuentos incidencias'],
    ['ruta' => 'nomina/periodos',       'etiqueta' => 'Nómina · Periodos',   'icono' => 'fa-calendar-week',       'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'periodos cierre calculo preview nomina'],
    ['ruta' => 'notificaciones',        'etiqueta' => 'Notificaciones',      'icono' => 'fa-bell',                'modulo' => 'notificaciones',   'permiso' => null, 'roles' => null, 'buscar' => 'alertas avisos'],
    ['ruta' => 'configuracion',         'etiqueta' => 'Configuración',       'icono' => 'fa-cog',                 'modulo' => 'configuracion',    'permiso' => 'configuracion.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'ajustes hotel backups apariencia'],
    ['ruta' => 'configuracion/tarifas', 'etiqueta' => 'Tarifas dinámicas',   'icono' => 'fa-tags',                'modulo' => ['tarifas_dinamicas', 'tarifas'], 'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'precios temporadas incrementos'],
    ['ruta' => 'configuracion/roles',   'etiqueta' => 'Roles y permisos',    'icono' => 'fa-user-shield',         'modulo' => 'roles_avanzados',  'permiso' => 'roles.manage', 'roles' => null, 'buscar' => 'rbac accesos permisos'],
];
