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
    ['ruta' => 'dashboard',             'etiqueta' => 'Dashboard',           'icono' => 'fa-th-large',            'modulo' => 'dashboard',        'permiso' => null, 'roles' => null, 'buscar' => 'inicio panel resumen'],
    ['ruta' => 'operacion/diaria',      'etiqueta' => 'Operación diaria',    'icono' => 'fa-clipboard-check',     'modulo' => 'tablero_ejecutivo','permiso' => null, 'roles' => null, 'buscar' => 'tablero ejecutivo pendientes'],
    ['ruta' => 'habitaciones',          'etiqueta' => 'Habitaciones',        'icono' => 'fa-bed',                 'modulo' => 'habitaciones',     'permiso' => null, 'roles' => null, 'buscar' => 'cuartos ocupacion limpieza mantenimiento'],
    ['ruta' => 'reservaciones',         'etiqueta' => 'Reservaciones',       'icono' => 'fa-calendar-check',      'modulo' => 'reservaciones',    'permiso' => null, 'roles' => null, 'buscar' => 'reservas checkin checkout entradas salidas'],
    ['ruta' => 'reservaciones/crear',   'etiqueta' => 'Nueva reservación',   'icono' => 'fa-calendar-plus',       'modulo' => 'reservaciones',    'permiso' => null, 'roles' => null, 'buscar' => 'crear reserva nueva'],
    ['ruta' => 'huespedes',             'etiqueta' => 'Huéspedes',           'icono' => 'fa-users',               'modulo' => 'huespedes',        'permiso' => null, 'roles' => null, 'buscar' => 'clientes visitantes vehiculos'],
    ['ruta' => 'checkin-digital',       'etiqueta' => 'Check-in digital',    'icono' => 'fa-id-badge',            'modulo' => 'checkin_digital',  'permiso' => null, 'roles' => null, 'buscar' => 'pre-registro links'],
    ['ruta' => 'caja',                  'etiqueta' => 'Caja',                'icono' => 'fa-hand-holding-dollar', 'modulo' => 'caja',             'permiso' => null, 'roles' => null, 'buscar' => 'dinero cobros turno'],
    ['ruta' => 'caja/movimientos',      'etiqueta' => 'Movimientos de caja', 'icono' => 'fa-list-ul',             'modulo' => 'caja',             'permiso' => null, 'roles' => null, 'buscar' => 'ingresos gastos'],
    ['ruta' => 'caja/corte',            'etiqueta' => 'Corte de caja',       'icono' => 'fa-cash-register',       'modulo' => 'caja',             'permiso' => null, 'roles' => null, 'buscar' => 'cierre arqueo turno'],
    ['ruta' => 'caja/historial',        'etiqueta' => 'Historial de cortes', 'icono' => 'fa-clock-rotate-left',   'modulo' => 'caja',             'permiso' => null, 'roles' => null, 'buscar' => 'cortes anteriores'],
    ['ruta' => 'cuentas-por-cobrar',    'etiqueta' => 'Cuentas por cobrar',  'icono' => 'fa-money-bill-wave',     'modulo' => 'cuentas_cobrar',   'permiso' => null, 'roles' => null, 'buscar' => 'cxc deudas creditos'],
    ['ruta' => 'facturacion',           'etiqueta' => 'Facturación',         'icono' => 'fa-file-invoice',        'modulo' => 'facturacion',      'permiso' => null, 'roles' => null, 'buscar' => 'facturas fiscal rfc'],
    ['ruta' => 'cuentas-por-pagar',     'etiqueta' => 'Cuentas por pagar',   'icono' => 'fa-file-invoice-dollar', 'modulo' => 'compras',          'permiso' => null, 'roles' => null, 'buscar' => 'cxp proveedores pagos'],
    ['ruta' => 'tareas',                'etiqueta' => 'Tareas',              'icono' => 'fa-tasks',               'modulo' => 'tareas',           'permiso' => null, 'roles' => null, 'buscar' => 'pendientes operativas'],
    ['ruta' => 'camarista',             'etiqueta' => 'Limpieza',            'icono' => 'fa-broom',               'modulo' => 'camarista',        'permiso' => null, 'roles' => null, 'buscar' => 'camarista housekeeping'],
    ['ruta' => 'inventario',            'etiqueta' => 'Inventarios',         'icono' => 'fa-box',                 'modulo' => 'inventario',       'permiso' => null, 'roles' => null, 'buscar' => 'productos stock existencias'],
    ['ruta' => 'compras',               'etiqueta' => 'Compras',             'icono' => 'fa-clipboard-list',      'modulo' => 'compras',          'permiso' => null, 'roles' => null, 'buscar' => 'ordenes recepcion'],
    ['ruta' => 'proveedores',           'etiqueta' => 'Proveedores',         'icono' => 'fa-truck',               'modulo' => 'compras',          'permiso' => null, 'roles' => null, 'buscar' => 'suministros'],
    ['ruta' => 'documentos',            'etiqueta' => 'Documentos',          'icono' => 'fa-folder-open',         'modulo' => 'documentos',       'permiso' => null, 'roles' => null, 'buscar' => 'archivos expedientes'],
    ['ruta' => 'motor-reservas',        'etiqueta' => 'Motor de reservas',   'icono' => 'fa-globe',               'modulo' => 'motor_reservas',   'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'reservas online booking web'],
    ['ruta' => 'canales',               'etiqueta' => 'Canales (iCal)',      'icono' => 'fa-calendar-alt',        'modulo' => 'canales_ical',     'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'ota booking airbnb sincronizacion'],
    ['ruta' => 'whatsapp',              'etiqueta' => 'WhatsApp',            'icono' => 'fa-comment-dots',        'modulo' => 'whatsapp',         'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'mensajes confirmaciones'],
    ['ruta' => 'ia/resumen-diario',     'etiqueta' => 'Asesor IA',           'icono' => 'fa-wand-magic-sparkles', 'modulo' => 'ia_ejecutiva',     'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'resumen inteligencia ejecutivo'],
    ['ruta' => 'reportes',              'etiqueta' => 'Reportes',            'icono' => 'fa-chart-line',          'modulo' => 'reportes',         'permiso' => null, 'roles' => null, 'buscar' => 'estadisticas graficas exportar'],
    ['ruta' => 'usuarios',              'etiqueta' => 'Usuarios',            'icono' => 'fa-user-shield',         'modulo' => 'usuarios',         'permiso' => 'usuarios.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'cuentas accesos'],
    ['ruta' => 'trabajadores',          'etiqueta' => 'Personal',            'icono' => 'fa-id-card',             'modulo' => 'personal',         'permiso' => 'usuarios.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'empleados nomina pagos'],
    ['ruta' => 'nomina',                'etiqueta' => 'Nómina',              'icono' => 'fa-file-invoice-dollar', 'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'nomina sueldos salarios periodos incidencias'],
    ['ruta' => 'nomina/empleados',      'etiqueta' => 'Empleados (nómina)',  'icono' => 'fa-address-book',        'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'empleados ficha salario puesto departamento'],
    ['ruta' => 'nomina/catalogos',      'etiqueta' => 'Catálogos de nómina', 'icono' => 'fa-layer-group',         'modulo' => 'nomina_avanzada',  'permiso' => 'nomina.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'puestos departamentos contratos grupos conceptos'],
    ['ruta' => 'notificaciones',        'etiqueta' => 'Notificaciones',      'icono' => 'fa-bell',                'modulo' => 'notificaciones',   'permiso' => null, 'roles' => null, 'buscar' => 'alertas avisos'],
    ['ruta' => 'configuracion',         'etiqueta' => 'Configuración',       'icono' => 'fa-cog',                 'modulo' => 'configuracion',    'permiso' => 'configuracion.view', 'roles' => ['gerente', 'administrador'], 'buscar' => 'ajustes hotel backups apariencia'],
    ['ruta' => 'configuracion/tarifas', 'etiqueta' => 'Tarifas dinámicas',   'icono' => 'fa-tags',                'modulo' => ['tarifas_dinamicas', 'tarifas'], 'permiso' => null, 'roles' => ['gerente', 'administrador'], 'buscar' => 'precios temporadas incrementos'],
    ['ruta' => 'configuracion/roles',   'etiqueta' => 'Roles y permisos',    'icono' => 'fa-user-lock',           'modulo' => 'roles_avanzados',  'permiso' => 'roles.manage', 'roles' => null, 'buscar' => 'rbac accesos permisos'],
];
