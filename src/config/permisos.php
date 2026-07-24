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

    // Roles base que NO se le muestran al hotel: no salen en Roles y permisos
    // ni en el selector de rol de una persona. Siguen existiendo en la tabla
    // `roles` (y can() los sigue resolviendo si alguien los tuviera), solo se
    // esconden de la interfaz. Para volver a mostrarlos, saca su clave de aqui.
    'roles_ocultos' => ['superadmin', 'propietario'],

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
            'label' => 'Huéspedes',
            'modulo' => 'huespedes',
            'permisos' => [
                'huespedes.view'   => ['label' => 'Ver huéspedes', 'tipo' => 'acceso'],
                'huespedes.create' => ['label' => 'Crear huéspedes', 'tipo' => 'accion'],
                'huespedes.edit'   => ['label' => 'Editar huéspedes', 'tipo' => 'accion'],
                'huespedes.all'    => ['label' => 'Control total de huéspedes', 'tipo' => 'wildcard'],
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
                'caja.all'         => ['label' => 'Control total de caja', 'tipo' => 'wildcard'],
            ],
        ],
        'facturacion' => [
            'label' => 'Facturación',
            'modulo' => 'facturacion',
            'permisos' => [
                'facturacion.view' => ['label' => 'Ver facturación', 'tipo' => 'acceso'],
                'facturacion.all'  => ['label' => 'Control total de facturación', 'tipo' => 'wildcard'],
            ],
        ],
        'cuentas_por_cobrar' => [
            'label' => 'Cuentas por cobrar',
            'modulo' => 'cuentas_cobrar',
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
            'modulo' => 'compras',
            'permisos' => [
                'compras.view'    => ['label' => 'Ver compras', 'tipo' => 'acceso'],
                'compras.recibir' => ['label' => 'Recibir compras (suma stock al inventario y habilita CxP)', 'tipo' => 'accion'],
                'compras.all'     => ['label' => 'Control total de compras', 'tipo' => 'wildcard'],
            ],
        ],
        'proveedores' => [
            'label' => 'Proveedores',
            'modulo' => 'compras',
            'permisos' => [
                'proveedores.view' => ['label' => 'Ver proveedores', 'tipo' => 'acceso'],
                'proveedores.all'  => ['label' => 'Control total de proveedores', 'tipo' => 'wildcard'],
            ],
        ],
        'cuentas_por_pagar' => [
            'label' => 'Cuentas por pagar',
            'modulo' => 'compras',
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
            'label' => 'Lavandería',
            'modulo' => 'lavanderia',
            'permisos' => [
                'lavanderia.view'   => ['label' => 'Ver lavandería', 'tipo' => 'acceso'],
                'lavanderia.operar' => ['label' => 'Operar lavandería (blancos, ciclos de lavado y pedidos)', 'tipo' => 'accion'],
                'lavanderia.cobrar' => ['label' => 'Cobrar pedidos y registrar gastos de lavandería en Caja', 'tipo' => 'accion'],
                'lavanderia.all'    => ['label' => 'Control total de lavandería', 'tipo' => 'wildcard'],
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
            'label' => 'Personal y nómina',
            'modulo' => null,
            'permisos' => [
                'personal.view'      => ['label' => 'Ver personal y pre-nómina', 'tipo' => 'acceso'],
                'personal.gestionar' => ['label' => 'Gestionar personal (altas, conceptos, anticipos, préstamos, asistencia y pre-nómina)', 'tipo' => 'accion'],
                'personal.pagar'     => ['label' => 'Pagar nómina por Caja y revertir pagos', 'tipo' => 'accion'],
                'personal.all'       => ['label' => 'Control total de personal', 'tipo' => 'wildcard'],
            ],
        ],
        'nomina' => [
            'label' => 'Nómina avanzada',
            'modulo' => 'nomina_avanzada',
            'permisos' => [
                'nomina.view'        => ['label' => 'Ver nómina', 'tipo' => 'acceso'],
                'nomina.empleados'   => ['label' => 'Gestionar empleados en nómina', 'tipo' => 'accion'],
                'nomina.salarios'    => ['label' => 'Editar sueldos y esquemas de pago', 'tipo' => 'accion'],
                'nomina.incidencias' => ['label' => 'Registrar incidencias', 'tipo' => 'accion'],
                'nomina.calcular'    => ['label' => 'Calcular y previsualizar nómina', 'tipo' => 'accion'],
                'nomina.cerrar'      => ['label' => 'Cerrar periodos de nómina', 'tipo' => 'accion'],
                'nomina.aprobar'     => ['label' => 'Aprobar periodos cerrados', 'tipo' => 'accion'],
                'nomina.reabrir'     => ['label' => 'Reabrir o recalcular periodos', 'tipo' => 'accion'],
                'nomina.pagar'       => ['label' => 'Registrar pagos de nómina', 'tipo' => 'accion'],
                'nomina.exportar'    => ['label' => 'Exportar reportes de nómina', 'tipo' => 'accion'],
                'nomina.configurar'  => ['label' => 'Configurar la nómina del negocio', 'tipo' => 'accion'],
                'nomina.all'         => ['label' => 'Control total de nómina', 'tipo' => 'wildcard'],
            ],
        ],
        'tarifas' => [
            'label' => 'Precios y temporadas',
            'modulo' => null,
            'permisos' => [
                'tarifas.view' => ['label' => 'Ver tarifas', 'tipo' => 'acceso'],
                'tarifas.edit' => ['label' => 'Editar tarifas', 'tipo' => 'accion'],
                'tarifas.all'  => ['label' => 'Control total de precios y temporadas', 'tipo' => 'wildcard'],
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
                'usuarios.all'    => ['label' => 'Control total de usuarios', 'tipo' => 'wildcard'],
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
            'label' => 'Configuración',
            'modulo' => null,
            'permisos' => [
                'configuracion.view' => ['label' => 'Ver configuración', 'tipo' => 'acceso'],
                'configuracion.edit' => ['label' => 'Editar configuración', 'tipo' => 'accion'],
                'configuracion.all'  => ['label' => 'Control total de configuración', 'tipo' => 'wildcard'],
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
            'label' => 'Guardián financiero',
            'modulo' => 'ia_ejecutiva',
            'permisos' => [
                // Sensible: incluye patrones de comportamiento POR USUARIO.
                // Solo direccion (dueno/gerencia); un operativo jamas debe ver
                // su propio perfil ni el de un companero.
                'guardian.view' => ['label' => 'Ver el Guardián financiero (patrones por usuario)', 'tipo' => 'acceso'],
            ],
        ],
        'reputacion' => [
            'label' => 'Opiniones y encuestas',
            'modulo' => 'reputacion',
            'permisos' => [
                'reputacion.view'       => ['label' => 'Ver reputación y encuestas', 'tipo' => 'acceso'],
                // Sale hacia el huesped: no puede autorizarlo un permiso de solo ver.
                'reputacion.encuestas'  => ['label' => 'Generar y enviar encuestas a los huéspedes', 'tipo' => 'accion'],
                'reputacion.configurar' => ['label' => 'Configurar las encuestas', 'tipo' => 'accion'],
                'reputacion.all'        => ['label' => 'Control total de opiniones y encuestas', 'tipo' => 'wildcard'],
            ],
        ],
        'dueno' => [
            'label' => 'Modo Dueño',
            'modulo' => 'modo_dueno',
            'permisos' => [
                'dueno.view' => ['label' => 'Ver el Modo Dueño (resumen remoto de solo lectura)', 'tipo' => 'acceso'],
            ],
        ],

        /* -------------------------------------------------------------------
         * Areas incorporadas el 23 jul 2026 (auditoria de accesos, paso 1).
         *
         * Estos modulos existian y se vendian, pero NO tenian ningun permiso
         * definido: sanitizarPermisos() solo deja pasar lo que esta en este
         * catalogo, asi que no habia forma de acotarlos ni casilla que ofrecer
         * en Roles y permisos. Sus controladores hoy solo verifican el modulo
         * contratado, no quien eres dentro del hotel.
         *
         * Declarar el permiso NO lo aplica: hasta que el controlador llame a
         * require_permission_or_403() (paso 2) esto solo puebla la matriz.
         * ----------------------------------------------------------------- */

        'motor_reservas' => [
            'label' => 'Reservas en línea',
            'modulo' => 'motor_reservas',
            'permisos' => [
                'motor_reservas.view'       => ['label' => 'Ver las reservas que entran por internet y sus pagos', 'tipo' => 'acceso'],
                // Mete dinero a Caja: la accion mas sensible del modulo.
                'motor_reservas.conciliar'  => ['label' => 'Conciliar los pagos en línea con la caja', 'tipo' => 'accion'],
                'motor_reservas.configurar' => ['label' => 'Configurar el motor de reservas', 'tipo' => 'accion'],
                'motor_reservas.all'        => ['label' => 'Control total de reservas en línea', 'tipo' => 'wildcard'],
            ],
        ],
        'promociones' => [
            'label' => 'Cupones de descuento',
            'modulo' => 'promociones',
            'permisos' => [
                'promociones.view'      => ['label' => 'Ver los cupones', 'tipo' => 'acceso'],
                'promociones.gestionar' => ['label' => 'Crear cupones y activarlos o desactivarlos', 'tipo' => 'accion'],
                'promociones.all'       => ['label' => 'Control total de cupones', 'tipo' => 'wildcard'],
            ],
        ],
        'upsells' => [
            'label' => 'Extras de venta',
            'modulo' => 'upsells',
            'permisos' => [
                'upsells.view'      => ['label' => 'Ver los extras', 'tipo' => 'acceso'],
                'upsells.gestionar' => ['label' => 'Crear extras y activarlos o desactivarlos', 'tipo' => 'accion'],
                'upsells.all'       => ['label' => 'Control total de extras', 'tipo' => 'wildcard'],
            ],
        ],
        'canales' => [
            'label' => 'Airbnb y Booking',
            'modulo' => 'canales_ical',
            'permisos' => [
                'canales.view'      => ['label' => 'Ver la sincronización con Airbnb y Booking', 'tipo' => 'acceso'],
                // Quitar un calendario deja de bloquear fechas: puede provocar
                // sobreventa. Por eso va separado de la vista.
                'canales.gestionar' => ['label' => 'Conectar, quitar y sincronizar calendarios', 'tipo' => 'accion'],
                'canales.all'       => ['label' => 'Control total de Airbnb y Booking', 'tipo' => 'wildcard'],
            ],
        ],
        'whatsapp' => [
            'label' => 'Conectar WhatsApp',
            'modulo' => 'whatsapp',
            'permisos' => [
                'whatsapp.view'       => ['label' => 'Ver el estado de la conexión de WhatsApp', 'tipo' => 'acceso'],
                // Guarda credenciales del proveedor: solo direccion.
                'whatsapp.configurar' => ['label' => 'Conectar el número y guardar las claves de acceso', 'tipo' => 'accion'],
                'whatsapp.all'        => ['label' => 'Control total de la conexión de WhatsApp', 'tipo' => 'wildcard'],
            ],
        ],
        'mensajes' => [
            'label' => 'Mensajes a huéspedes',
            'modulo' => 'canal_whatsapp',
            'permisos' => [
                'mensajes.view'       => ['label' => 'Ver la cola de mensajes del día', 'tipo' => 'acceso'],
                'mensajes.enviar'     => ['label' => 'Enviar y descartar mensajes', 'tipo' => 'accion'],
                'mensajes.configurar' => ['label' => 'Elegir qué mensajes se envían solos', 'tipo' => 'accion'],
                'mensajes.all'        => ['label' => 'Control total de mensajes a huéspedes', 'tipo' => 'wildcard'],
            ],
        ],
        'checkin_digital' => [
            'label' => 'Check-in digital',
            'modulo' => 'checkin_digital',
            'permisos' => [
                'checkin_digital.view'           => ['label' => 'Ver el tablero de pre-registro', 'tipo' => 'acceso'],
                'checkin_digital.generar'        => ['label' => 'Generar el link de pre-registro del huésped', 'tipo' => 'accion'],
                // Dato personal sensible (INE / pasaporte). Se separa a proposito
                // de la vista del tablero: se puede dar una sin la otra.
                'checkin_digital.identificacion' => ['label' => 'Ver y descargar la identificación del huésped', 'tipo' => 'accion'],
                'checkin_digital.all'            => ['label' => 'Control total de check-in digital', 'tipo' => 'wildcard'],
            ],
        ],
        'auditoria' => [
            'label' => 'Historial de actividad',
            'modulo' => 'auditoria',
            'permisos' => [
                // Es la bitacora que permite revisar a los demas: quien la lee
                // vigila, y quien hizo algo indebido querria leerla o taparla.
                // Solo direccion.
                'auditoria.view' => ['label' => 'Ver el historial de actividad (quién hizo qué)', 'tipo' => 'acceso'],
            ],
        ],
        'night_audit' => [
            'label' => 'Cierre del día',
            'modulo' => 'night_audit',
            'permisos' => [
                'night_audit.view'     => ['label' => 'Ver el cierre del día', 'tipo' => 'acceso'],
                'night_audit.ejecutar' => ['label' => 'Ejecutar el cierre del día', 'tipo' => 'accion'],
                'night_audit.all'      => ['label' => 'Control total del cierre del día', 'tipo' => 'wildcard'],
            ],
        ],
        'lealtad' => [
            'label' => 'Huésped frecuente',
            'modulo' => 'lealtad',
            'permisos' => [
                'lealtad.view'       => ['label' => 'Ver a los huéspedes frecuentes', 'tipo' => 'acceso'],
                // Genera un descuento real: es dinero que deja de entrar.
                'lealtad.cupones'    => ['label' => 'Generar y enviar cupones de agradecimiento', 'tipo' => 'accion'],
                'lealtad.configurar' => ['label' => 'Configurar el programa de huésped frecuente', 'tipo' => 'accion'],
                'lealtad.all'        => ['label' => 'Control total de huésped frecuente', 'tipo' => 'wildcard'],
            ],
        ],
        'forecast' => [
            'label' => 'Pronóstico de ocupación',
            'modulo' => 'forecast',
            'permisos' => [
                'forecast.view'       => ['label' => 'Ver el pronóstico de ocupación', 'tipo' => 'acceso'],
                // El calendario de temporadas alimenta precios y consejos.
                'forecast.temporadas' => ['label' => 'Definir el calendario de temporadas', 'tipo' => 'accion'],
                'forecast.all'        => ['label' => 'Control total del pronóstico', 'tipo' => 'wildcard'],
            ],
        ],
        'operacion' => [
            'label' => 'El hotel hoy',
            'modulo' => 'tablero_ejecutivo',
            'permisos' => [
                'operacion.view'         => ['label' => 'Ver el tablero del día', 'tipo' => 'acceso'],
                // Cruza cobros contra caja: cifras de dinero del hotel completo.
                'operacion.conciliacion' => ['label' => 'Ver la conciliación financiera del día', 'tipo' => 'accion'],
                'operacion.all'          => ['label' => 'Control total del tablero del día', 'tipo' => 'wildcard'],
            ],
        ],
        'ia' => [
            'label' => 'Asesor inteligente',
            'modulo' => 'ia_ejecutiva',
            'permisos' => [
                // Narra cifras de dinero del hotel completo.
                'ia.view'      => ['label' => 'Ver el resumen del día del asesor', 'tipo' => 'acceso'],
                'ia.regenerar' => ['label' => 'Volver a generar el resumen del día', 'tipo' => 'accion'],
                'ia.all'       => ['label' => 'Control total del asesor inteligente', 'tipo' => 'wildcard'],
            ],
        ],
        'copiloto' => [
            'label' => 'Asistente',
            'modulo' => 'copiloto',
            'permisos' => [
                'copiloto.usar'     => ['label' => 'Preguntarle al asistente', 'tipo' => 'acceso'],
                // El asistente ejecuta la accion en nombre de quien pregunta; el
                // servicio revalida permiso y bloque por tipo de accion.
                'copiloto.acciones' => ['label' => 'Dejar que el asistente ejecute lo que se le confirme', 'tipo' => 'accion'],
                'copiloto.valor'    => ['label' => 'Ver el panel de uso del asistente', 'tipo' => 'accion'],
                'copiloto.all'      => ['label' => 'Control total del asistente', 'tipo' => 'wildcard'],
            ],
        ],
        'copiloto_ia' => [
            'label' => 'Asistente con IA',
            'modulo' => 'copiloto_ia',
            'permisos' => [
                // Aplicar una sugerencia de tarifa ya exige aparte 'tarifas.edit'.
                'copiloto_ia.usar' => ['label' => 'Usar el análisis con IA de opiniones, pronóstico y tarifas', 'tipo' => 'acceso'],
            ],
        ],
    ],

    'presets' => [
        'superadmin' => [
            'nombre'      => 'Superadministrador',
            'descripcion' => 'Acceso total. Rol técnico reservado.',
            'es_sistema'  => 1,
            'permisos'    => ['*'],
        ],
        'propietario' => [
            'nombre'      => 'Propietario',
            'descripcion' => 'Dueño del hotel. Acceso total, incluida la gestión de roles.',
            'es_sistema'  => 1,
            'permisos'    => ['*'],
        ],
        // El Gerente es el techo del hotel: manda en TODAS las areas. Se le da
        // el control total de cada area ('<modulo>.all') en vez del comodin '*'
        // a proposito: asi sus permisos se ven en la matriz, se pueden editar y
        // se le pueden recortar a UNA persona (con '*' el sistema bloquea las
        // dos cosas). Al agregar un area nueva al catalogo, agregala tambien
        // aqui o el Gerente se queda sin ella.
        'gerente' => [
            'nombre'      => 'Gerente',
            'descripcion' => 'Manda en todo el hotel: todas las áreas, más usuarios y configuración.',
            'es_sistema'  => 1,
            'permisos'    => [
                'habitaciones.all', 'reservaciones.all', 'huespedes.all',
                'caja.all', 'facturacion.all',
                'cuentas_por_cobrar.all', 'cuentas_por_pagar.all',
                'inventarios.all', 'compras.all', 'proveedores.all',
                'documentos.all', 'tareas.all', 'lavanderia.all', 'camarista.view',
                'reportes.all',
                'personal.all', 'nomina.all',
                'tarifas.all',
                'usuarios.all', 'roles.manage',
                'configuracion.all',
                'notificaciones.view', 'guardian.view', 'reputacion.all', 'dueno.view',
                // Areas incorporadas el 23 jul 2026 (ver bloque del catalogo).
                'motor_reservas.all', 'promociones.all', 'upsells.all',
                'canales.all', 'whatsapp.all', 'mensajes.all',
                'checkin_digital.all', 'night_audit.all', 'lealtad.all',
                'forecast.all', 'operacion.all', 'ia.all',
                'copiloto.all', 'copiloto_ia.usar',
                'auditoria.view',
                // Fuera de la matriz (sin casilla que marcar), pero parte del todo.
                'llaves.control',
            ],
        ],
        'administrador' => [
            'nombre'      => 'Administrador',
            'descripcion' => 'Administración operativa del hotel.',
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
                // Areas incorporadas el 23 jul 2026. Se le da la operacion
                // completa, MENOS lo que es de direccion: la bitacora de
                // actividad (auditoria.view) y las claves de WhatsApp
                // (whatsapp.configurar, solo ve el estado de la conexion).
                'motor_reservas.all', 'promociones.all', 'upsells.all',
                'reputacion.all',
                'canales.all', 'whatsapp.view', 'mensajes.all',
                'checkin_digital.all', 'night_audit.all', 'lealtad.all',
                'forecast.all', 'operacion.all',
                'ia.view',
                // copiloto.valor: el panel de uso hoy lo abre gerencia Y
                // administracion (era un gate por rol-string), se conserva.
                'copiloto.usar', 'copiloto.acciones', 'copiloto.valor', 'copiloto_ia.usar',
            ],
        ],
        'recepcionista' => [
            'nombre'      => 'Recepcionista',
            'descripcion' => 'Operación de recepción: check-in/out, huéspedes y cobros.',
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
                // Areas incorporadas el 23 jul 2026. Solo lo que recepcion usa
                // en el mostrador: ve las reservas que entran por internet
                // (no las concilia), manda las confirmaciones y recibe la
                // identificacion del huesped al registrarlo.
                'motor_reservas.view',
                'mensajes.view', 'mensajes.enviar',
                'checkin_digital.view', 'checkin_digital.generar', 'checkin_digital.identificacion',
                'copiloto.usar',
            ],
        ],
        'dueno_remoto' => [
            'nombre'      => 'Dueño (remoto)',
            'descripcion' => 'Dueño que no opera el hotel: solo lectura del resumen del día (Modo Dueño).',
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
                // Areas incorporadas el 23 jul 2026. Solo lectura, en la linea
                // del Modo Dueno: el resumen del dia, el pronostico, lo que
                // entra por internet y quien hizo que en su hotel.
                'operacion.view',
                'ia.view',
                'forecast.view',
                'motor_reservas.view',
                'auditoria.view',
            ],
        ],
    ],
];
