<?php
/**
 * Catalogo de permisos y presets de roles base.
 *
 * FUENTE DE VERDAD del sistema de roles configurables por hotel.
 *
 * - 'catalogo': agrupa los permisos por modulo funcional para renderizar la
 *   matriz de la UI (panel del hotel y Panel Medisoft interno). Cada permiso
 *   declara su etiqueta y tipo:
 *     - 'acceso'   => permiso base para ver/entrar al modulo (<modulo>.view)
 *     - 'accion'   => accion sensible dentro del modulo (cortes, ajustes, etc.)
 *     - 'wildcard' => control total del modulo (<modulo>.all)
 *   'modulo' cruza con el catalogo `modulos`/`hotel_modulos`: el permiso solo
 *   tiene efecto si el hotel tiene contratado ese modulo (null = sin cruce).
 *
 * - 'presets': roles base precargados al sembrar un hotel. Replican el
 *   comportamiento de can() previo a roles configurables para garantizar
 *   PARIDAD exacta en los call-sites que hoy verifican permisos. El permiso
 *   especial '*' concede todo. Los permisos '<modulo>.all' conceden todo el
 *   modulo (lo entiende can()).
 *
 * IMPORTANTE: los JSON de permisos de la migracion
 * 20260628_001_create_roles_base.sql deben coincidir con estos presets.
 * Al editar presets, actualiza tambien Rol::sembrarPresetsParaHotel().
 */

return [
    // Permiso comodin que concede todo.
    'comodin' => '*',

    'catalogo' => [
        'habitaciones' => [
            'label' => 'Habitaciones',
            'modulo' => 'habitaciones',
            'permisos' => [
                'habitaciones.view'          => ['label' => 'Ver habitaciones', 'tipo' => 'acceso'],
                'habitaciones.checkin'       => ['label' => 'Registrar check-in', 'tipo' => 'accion'],
                'habitaciones.checkout'      => ['label' => 'Registrar check-out', 'tipo' => 'accion'],
                'habitaciones.mantenimiento' => ['label' => 'Marcar mantenimiento', 'tipo' => 'accion'],
                'habitaciones.all'           => ['label' => 'Control total de habitaciones', 'tipo' => 'wildcard'],
            ],
        ],
        'reservaciones' => [
            'label' => 'Reservaciones',
            'modulo' => 'reservaciones',
            'permisos' => [
                'reservaciones.view'     => ['label' => 'Ver reservaciones', 'tipo' => 'acceso'],
                'reservaciones.create'   => ['label' => 'Crear reservaciones', 'tipo' => 'accion'],
                'reservaciones.edit'     => ['label' => 'Editar reservaciones', 'tipo' => 'accion'],
                'reservaciones.cancelar' => ['label' => 'Cancelar reservaciones', 'tipo' => 'accion'],
                'reservaciones.all'      => ['label' => 'Control total de reservaciones', 'tipo' => 'wildcard'],
            ],
        ],
        'huespedes' => [
            'label' => 'Huespedes',
            'modulo' => 'huespedes',
            'permisos' => [
                'huespedes.view'   => ['label' => 'Ver huespedes', 'tipo' => 'acceso'],
                'huespedes.create' => ['label' => 'Crear huespedes', 'tipo' => 'accion'],
                'huespedes.edit'   => ['label' => 'Editar huespedes', 'tipo' => 'accion'],
                'huespedes.all'    => ['label' => 'Control total de huespedes', 'tipo' => 'wildcard'],
            ],
        ],
        'caja' => [
            'label' => 'Caja',
            'modulo' => 'caja',
            'permisos' => [
                'caja.view'        => ['label' => 'Ver caja', 'tipo' => 'acceso'],
                'caja.cobros'      => ['label' => 'Registrar cobros', 'tipo' => 'accion'],
                'caja.movimientos' => ['label' => 'Registrar movimientos', 'tipo' => 'accion'],
                'caja.corte'       => ['label' => 'Hacer cortes de caja', 'tipo' => 'accion'],
                'caja.ajustes'     => ['label' => 'Ajustes y correcciones de caja', 'tipo' => 'accion'],
            ],
        ],
        'facturacion' => [
            'label' => 'Facturacion',
            'modulo' => 'facturacion',
            'permisos' => [
                'facturacion.view' => ['label' => 'Ver facturacion', 'tipo' => 'acceso'],
                'facturacion.all'  => ['label' => 'Control total de facturacion', 'tipo' => 'wildcard'],
            ],
        ],
        'cuentas_por_cobrar' => [
            'label' => 'Cuentas por cobrar',
            'modulo' => null,
            'permisos' => [
                'cuentas_por_cobrar.view'   => ['label' => 'Ver cuentas por cobrar', 'tipo' => 'acceso'],
                'cuentas_por_cobrar.cobrar' => ['label' => 'Registrar y revertir cobros de CxC', 'tipo' => 'accion'],
                'cuentas_por_cobrar.all'    => ['label' => 'Control total de cuentas por cobrar', 'tipo' => 'wildcard'],
            ],
        ],
        'inventario' => [
            'label' => 'Inventario',
            'modulo' => 'inventario',
            'permisos' => [
                'inventarios.view' => ['label' => 'Ver inventario', 'tipo' => 'acceso'],
                'inventarios.all'  => ['label' => 'Control total de inventario', 'tipo' => 'wildcard'],
            ],
        ],
        'compras' => [
            'label' => 'Compras',
            'modulo' => 'inventario',
            'permisos' => [
                'compras.view' => ['label' => 'Ver compras', 'tipo' => 'acceso'],
                'compras.all'  => ['label' => 'Control total de compras', 'tipo' => 'wildcard'],
            ],
        ],
        'proveedores' => [
            'label' => 'Proveedores',
            'modulo' => 'inventario',
            'permisos' => [
                'proveedores.view' => ['label' => 'Ver proveedores', 'tipo' => 'acceso'],
                'proveedores.all'  => ['label' => 'Control total de proveedores', 'tipo' => 'wildcard'],
            ],
        ],
        'cuentas_por_pagar' => [
            'label' => 'Cuentas por pagar',
            'modulo' => null,
            'permisos' => [
                'cuentas_por_pagar.view'  => ['label' => 'Ver cuentas por pagar', 'tipo' => 'acceso'],
                'cuentas_por_pagar.pagar' => ['label' => 'Registrar y revertir pagos de CxP', 'tipo' => 'accion'],
                'cuentas_por_pagar.all'   => ['label' => 'Control total de cuentas por pagar', 'tipo' => 'wildcard'],
            ],
        ],
        'documentos' => [
            'label' => 'Documentos',
            'modulo' => null,
            'permisos' => [
                'documentos.view' => ['label' => 'Ver documentos', 'tipo' => 'acceso'],
                'documentos.all'  => ['label' => 'Control total de documentos', 'tipo' => 'wildcard'],
            ],
        ],
        'tareas' => [
            'label' => 'Tareas y mantenimiento',
            'modulo' => null,
            'permisos' => [
                'tareas.view' => ['label' => 'Ver tareas', 'tipo' => 'acceso'],
                'tareas.all'  => ['label' => 'Control total de tareas', 'tipo' => 'wildcard'],
            ],
        ],
        'lavanderia' => [
            'label' => 'Lavanderia',
            'modulo' => 'lavanderia',
            'permisos' => [
                'lavanderia.view'   => ['label' => 'Ver lavanderia', 'tipo' => 'acceso'],
                'lavanderia.operar' => ['label' => 'Operar lavanderia (blancos, ciclos de lavado y pedidos)', 'tipo' => 'accion'],
                'lavanderia.cobrar' => ['label' => 'Cobrar pedidos y registrar gastos de lavanderia en Caja', 'tipo' => 'accion'],
                'lavanderia.all'    => ['label' => 'Control total de lavanderia', 'tipo' => 'wildcard'],
            ],
        ],
        'camarista' => [
            'label' => 'Limpieza (camarista)',
            'modulo' => 'camarista',
            'permisos' => [
                // Acceso al tablero movil de limpieza. Otorgar SOLO este permiso
                // (sin tareas ni habitaciones) crea un rol camarista que ve
                // unicamente la pantalla de Limpieza y aterriza en ella.
                'camarista.view' => ['label' => 'Ver el tablero de limpieza', 'tipo' => 'acceso'],
            ],
        ],
        'reportes' => [
            'label' => 'Reportes',
            'modulo' => 'reportes',
            'permisos' => [
                'reportes.view'   => ['label' => 'Ver reportes', 'tipo' => 'acceso'],
                'reportes.export' => ['label' => 'Exportar reportes', 'tipo' => 'accion'],
                'reportes.all'    => ['label' => 'Control total de reportes', 'tipo' => 'wildcard'],
            ],
        ],
        'personal' => [
            'label' => 'Personal y nomina',
            'modulo' => null,
            'permisos' => [
                'personal.view'      => ['label' => 'Ver personal y pre-nomina', 'tipo' => 'acceso'],
                'personal.gestionar' => ['label' => 'Gestionar personal (altas, conceptos, anticipos, prestamos, asistencia y pre-nomina)', 'tipo' => 'accion'],
                'personal.pagar'     => ['label' => 'Pagar nomina por Caja y revertir pagos', 'tipo' => 'accion'],
                'personal.all'       => ['label' => 'Control total de personal', 'tipo' => 'wildcard'],
            ],
        ],
        'nomina' => [
            'label' => 'Nomina avanzada',
            'modulo' => 'nomina_avanzada',
            'permisos' => [
                'nomina.view'        => ['label' => 'Ver nomina', 'tipo' => 'acceso'],
                'nomina.empleados'   => ['label' => 'Gestionar empleados en nomina', 'tipo' => 'accion'],
                'nomina.salarios'    => ['label' => 'Editar sueldos y esquemas de pago', 'tipo' => 'accion'],
                'nomina.incidencias' => ['label' => 'Registrar incidencias', 'tipo' => 'accion'],
                'nomina.calcular'    => ['label' => 'Calcular y previsualizar nomina', 'tipo' => 'accion'],
                'nomina.cerrar'      => ['label' => 'Cerrar periodos de nomina', 'tipo' => 'accion'],
                'nomina.aprobar'     => ['label' => 'Aprobar periodos cerrados', 'tipo' => 'accion'],
                'nomina.reabrir'     => ['label' => 'Reabrir o recalcular periodos', 'tipo' => 'accion'],
                'nomina.pagar'       => ['label' => 'Registrar pagos de nomina', 'tipo' => 'accion'],
                'nomina.exportar'    => ['label' => 'Exportar reportes de nomina', 'tipo' => 'accion'],
                'nomina.configurar'  => ['label' => 'Configurar la nomina del negocio', 'tipo' => 'accion'],
                'nomina.all'         => ['label' => 'Control total de nomina', 'tipo' => 'wildcard'],
            ],
        ],
        'tarifas' => [
            'label' => 'Tarifas dinamicas',
            'modulo' => null,
            'permisos' => [
                'tarifas.view' => ['label' => 'Ver tarifas', 'tipo' => 'acceso'],
                'tarifas.edit' => ['label' => 'Editar tarifas', 'tipo' => 'accion'],
            ],
        ],
        'usuarios' => [
            'label' => 'Usuarios',
            'modulo' => null,
            'permisos' => [
                'usuarios.view'   => ['label' => 'Ver usuarios', 'tipo' => 'acceso'],
                'usuarios.create' => ['label' => 'Crear usuarios', 'tipo' => 'accion'],
                'usuarios.edit'   => ['label' => 'Editar usuarios', 'tipo' => 'accion'],
                'usuarios.delete' => ['label' => 'Eliminar usuarios', 'tipo' => 'accion'],
            ],
        ],
        'roles' => [
            'label' => 'Roles y permisos',
            'modulo' => null,
            'permisos' => [
                'roles.manage' => ['label' => 'Gestionar roles y permisos', 'tipo' => 'accion'],
            ],
        ],
        'configuracion' => [
            'label' => 'Configuracion',
            'modulo' => null,
            'permisos' => [
                'configuracion.view' => ['label' => 'Ver configuracion', 'tipo' => 'acceso'],
                'configuracion.edit' => ['label' => 'Editar configuracion', 'tipo' => 'accion'],
            ],
        ],
        'notificaciones' => [
            'label' => 'Notificaciones',
            'modulo' => null,
            'permisos' => [
                'notificaciones.view' => ['label' => 'Ver notificaciones', 'tipo' => 'acceso'],
            ],
        ],
        'guardian' => [
            'label' => 'Guardian financiero',
            'modulo' => 'ia_ejecutiva',
            'permisos' => [
                // Sensible: incluye patrones de comportamiento POR USUARIO.
                // Solo direccion (dueno/gerencia); un operativo jamas debe ver
                // su propio perfil ni el de un companero.
                'guardian.view' => ['label' => 'Ver el Guardian financiero (patrones por usuario)', 'tipo' => 'acceso'],
            ],
        ],
        'reputacion' => [
            'label' => 'Reputacion',
            'modulo' => 'reputacion',
            'permisos' => [
                'reputacion.view' => ['label' => 'Ver reputacion y encuestas', 'tipo' => 'acceso'],
            ],
        ],
        'dueno' => [
            'label' => 'Modo Dueno',
            'modulo' => 'modo_dueno',
            'permisos' => [
                'dueno.view' => ['label' => 'Ver el Modo Dueno (resumen remoto de solo lectura)', 'tipo' => 'acceso'],
            ],
        ],
    ],

    'presets' => [
        'superadmin' => [
            'nombre'      => 'Superadministrador',
            'descripcion' => 'Acceso total. Rol tecnico reservado.',
            'es_sistema'  => 1,
            'permisos'    => ['*'],
        ],
        'propietario' => [
            'nombre'      => 'Propietario',
            'descripcion' => 'Dueno del hotel. Acceso total, incluida la gestion de roles.',
            'es_sistema'  => 1,
            'permisos'    => ['*'],
        ],
        'gerente' => [
            'nombre'      => 'Gerente',
            'descripcion' => 'Direccion operativa y financiera del hotel.',
            'es_sistema'  => 1,
            'permisos'    => [
                'usuarios.view', 'usuarios.create', 'usuarios.edit', 'usuarios.delete',
                'roles.manage',
                'configuracion.view', 'configuracion.edit',
                'reportes.all',
                'caja.view', 'caja.movimientos', 'caja.cobros', 'caja.corte', 'caja.ajustes',
                'habitaciones.all', 'reservaciones.all', 'huespedes.all', 'inventarios.all',
                'compras.all', 'proveedores.all', 'facturacion.view',
                'cuentas_por_cobrar.all', 'cuentas_por_pagar.all',
                'documentos.all', 'tareas.all', 'lavanderia.all',
                'personal.view', 'personal.gestionar', 'personal.pagar', 'nomina.all',
                'tarifas.view', 'tarifas.edit', 'notificaciones.view',
                'guardian.view',
            ],
        ],
        'administrador' => [
            'nombre'      => 'Administrador',
            'descripcion' => 'Administracion operativa del hotel.',
            'es_sistema'  => 1,
            'permisos'    => [
                'usuarios.view', 'usuarios.create', 'usuarios.edit',
                'reportes.view', 'reportes.export',
                'caja.view', 'caja.movimientos', 'caja.cobros', 'caja.corte',
                'habitaciones.all', 'reservaciones.all', 'huespedes.all', 'inventarios.all',
                'compras.all', 'proveedores.all', 'facturacion.view',
                'cuentas_por_cobrar.view', 'cuentas_por_cobrar.cobrar',
                'cuentas_por_pagar.view', 'cuentas_por_pagar.pagar',
                'documentos.all', 'tareas.all', 'lavanderia.all',
                'personal.view', 'personal.gestionar', 'personal.pagar',
                'nomina.view', 'nomina.incidencias', 'nomina.calcular',
                'notificaciones.view',
            ],
        ],
        'recepcionista' => [
            'nombre'      => 'Recepcionista',
            'descripcion' => 'Operacion de recepcion: check-in/out, huespedes y cobros.',
            'es_sistema'  => 1,
            'permisos'    => [
                'habitaciones.view', 'habitaciones.checkin', 'habitaciones.checkout', 'habitaciones.mantenimiento',
                'reservaciones.view', 'reservaciones.create', 'reservaciones.edit',
                'huespedes.view', 'huespedes.create', 'huespedes.edit',
                'caja.view', 'caja.cobros',
                'documentos.view', 'tareas.view',
                'lavanderia.view', 'lavanderia.operar', 'lavanderia.cobrar',
                'notificaciones.view',
                'llaves.control',
            ],
        ],
        'dueno_remoto' => [
            'nombre'      => 'Dueno (remoto)',
            'descripcion' => 'Dueno que no opera el hotel: solo lectura del resumen del dia (Modo Dueno).',
            'es_sistema'  => 1,
            'permisos'    => [
                'dueno.view',
                'caja.view',
                'habitaciones.view',
                'reservaciones.view',
                'lavanderia.view',
                'reputacion.view',
                'guardian.view',
                'notificaciones.view',
            ],
        ],
    ],
];
