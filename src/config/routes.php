<?php
/**
 * DefiniciÃ³n de Rutas
 * Los Cedros
 */

// Rutas AJAX para vehÃ­culos de huÃ©spedes
$router->post('/huespedes/agregar-vehiculo', [
    'controller' => 'Huesped',
    'action' => 'agregarVehiculo'
]);

$router->post('/huespedes/actualizar-vehiculo', [
    'controller' => 'Huesped',
    'action' => 'actualizarVehiculo'
]);

// Check-in tardÃ­o - Vista
$router->post('/reservaciones/cotizacion-reservacion-pdf', ['controller' => 'Reservacion', 'action' => 'cotizacionReservacionPdf']);
// Modificar dÃ­as de reservaciÃ³n (AJAX)
$router->post('/reservaciones/verificar-modificar-dias', ['controller' => 'Reservacion', 'action' => 'verificarModificarDias']);
$router->post('/reservaciones/modificar-dias', ['controller' => 'Reservacion', 'action' => 'modificarDias']);

$router->get('/facturacion', [
    'controller' => 'Facturacion',
    'action' => 'index'
]);

// Ver detalle de solicitud
$router->get('/facturacion/ver/{id:[0-9]+}', [
    'controller' => 'Facturacion',
    'action' => 'ver'
]);

$router->post('/reservaciones/cambiar-metodo-pago/{id:[0-9]+}', [
    'controller' => 'Reservacion',
    'action' => 'cambiarMetodoPago'
]);
// Guardar datos fiscales
$router->post('/facturacion/guardar', [
    'controller' => 'Facturacion',
    'action' => 'guardar'
]);

// Marcar como completada/facturada
$router->post('/facturacion/completar', [
    'controller' => 'Facturacion',
    'action' => 'completar'
]);

// Cancelar solicitud
$router->post('/facturacion/cancelar', [
    'controller' => 'Facturacion',
    'action' => 'cancelar'
]);

// Marcar en proceso
$router->post('/facturacion/en-proceso', [
    'controller' => 'Facturacion',
    'action' => 'enProceso'
]);



$router->post('/huespedes/eliminar-vehiculo', [
    'controller' => 'Huesped',
    'action' => 'eliminarVehiculo'
]);
$router->get('/api/reservaciones/{id:[0-9]+}/habitaciones', [
    'controller' => 'Reservacion',
    'action' => 'habitacionesApi'
]);
// Rutas de control de remotos
$router->post('/reservaciones/entregar-remoto', [
    'controller' => 'Reservacion',
    'action' => 'entregarRemoto'
]);

$router->post('/reservaciones/recibir-remoto', [
    'controller' => 'Reservacion',
    'action' => 'recibirRemoto'
]);

// Rutas de control de llaves
// Rutas de control de llaves
$router->post('/reservaciones/entregar-llave', [
    'controller' => 'Reservacion',
    'action' => 'entregarLlave'
]);

$router->post('/reservaciones/recibir-llave', [
    'controller' => 'Reservacion',
    'action' => 'recibirLlave'
]);
// RUTAS PARA GESTIÃ“N DE MÃšLTIPLES IMÃGENES
$router->get('/habitaciones/{id:[0-9]+}/imagenes', [
    'controller' => 'Habitacion',
    'action' => 'gestionarImagenes'
]);

$router->post('/habitaciones/{id:[0-9]+}/agregar-imagenes', [
    'controller' => 'Habitacion',
    'action' => 'agregarImagenes'
]);

$router->post('/habitaciones/imagen/eliminar', [
    'controller' => 'Habitacion', 
    'action' => 'eliminarImagen'
]);

$router->post('/habitaciones/imagen/principal', [
    'controller' => 'Habitacion',
    'action' => 'establecerPrincipal'
]);

// Pendiente Fase 1B: no existe HabitacionController::reordenarImagenesAction().
// $router->post('/habitaciones/{id:[0-9]+}/reordenar-imagenes', [
//     'controller' => 'Habitacion',
//     'action' => 'reordenarImagenes'
// ]);



// CAMBIAR add() por get() para rutas de API de inventario
$router->get('/api/inventario/preview-checkin/{id:[0-9]+}', [
    'controller' => 'Api',
    'action' => 'previewCheckinInventario'
]);

$router->get('/api/inventario/alertas', [
    'controller' => 'Api',
    'action' => 'alertasInventario'
]);

$router->get('/api/inventario/verificar-stock/{id:[0-9]+}', [
    'controller' => 'Api',
    'action' => 'verificarStockHabitacion'
]);

// Ruta para el check-in - cambiar add() por post()
$router->post('/reservaciones/checkin/{id:[0-9]+}', [
    'controller' => 'Reservacion',
    'action' => 'checkIn'
]);

$router->get('/caja/descargar-pdf/{id:[0-9]+}', ['controller' => 'Caja', 'action' => 'descargarPDF']);

// Remotos mÃºltiples
$router->post('/reservaciones/entregar-remotos-multiples', ['controller' => 'Reservacion', 'action' => 'entregarRemotosMultiples']);
$router->post('/reservaciones/recibir-remotos-multiples', ['controller' => 'Reservacion', 'action' => 'recibirRemotosMultiples']);

// Sistema de Caja - MANTENER SOLO ESTAS RUTAS
$router->get('/caja', ['controller' => 'Caja', 'action' => 'index']);
$router->post('/caja/abrir', ['controller' => 'Caja', 'action' => 'abrir']);
$router->post('/caja/ingreso', ['controller' => 'Caja', 'action' => 'registrarIngreso']);
$router->post('/caja/gasto', ['controller' => 'Caja', 'action' => 'registrarGasto']);
$router->get('/caja/movimientos', ['controller' => 'Caja', 'action' => 'movimientos']);
$router->get('/caja/corte', ['controller' => 'Caja', 'action' => 'corte']);
$router->post('/caja/corte/cerrar', ['controller' => 'Caja', 'action' => 'cerrarCorte']);
$router->get('/caja/historial', ['controller' => 'Caja', 'action' => 'historial']);
$router->get('/caja/corte/{id:[0-9]+}', ['controller' => 'Caja', 'action' => 'verCorte']);
$router->get('/caja/exportar', ['controller' => 'Caja', 'action' => 'exportar']);
$router->get('/caja/reporte-metodos', ['controller' => 'Caja', 'action' => 'reporteMetodos']);
$router->get('/caja/arqueo-metodos', ['controller' => 'Caja', 'action' => 'arqueoMetodos']);



// GestiÃ³n de categorÃ­as (solo gerente)
$router->get('/caja/categorias', ['controller' => 'Caja', 'action' => 'categorias']);

$router->post('/caja/categoria/crear', ['controller' => 'Caja', 'action' => 'crearCategoria']);
$router->post('/caja/categoria/actualizar', ['controller' => 'Caja', 'action' => 'actualizarCategoria']);
$router->post('/caja/categoria/toggle', ['controller' => 'Caja', 'action' => 'toggleCategoria']);
$router->post('/caja/categoria/orden', ['controller' => 'Caja', 'action' => 'ordenCategoria']);

// AJAX endpoints para caja
$router->get('/caja/movimiento', ['controller' => 'Caja', 'action' => 'obtenerMovimiento']);
$router->post('/caja/movimiento/editar', ['controller' => 'Caja', 'action' => 'editarMovimiento']);

// APIs para AJAX
$router->get('/api/huespedes/vehiculos/{id:[0-9]+}', ['controller' => 'Api', 'action' => 'vehiculosHuesped']);

// Rutas de autenticaciÃ³n
$router->get('/', ['controller' => 'Auth', 'action' => 'login']);
$router->get('/login', ['controller' => 'Auth', 'action' => 'login']);
$router->post('/login/authenticate', ['controller' => 'Auth', 'action' => 'authenticate']);
$router->get('/h/{slug:[a-z0-9-]+}/login', ['controller' => 'Auth', 'action' => 'hotelLogin']);
$router->post('/h/{slug:[a-z0-9-]+}/login/authenticate', ['controller' => 'Auth', 'action' => 'hotelAuthenticate']);
$router->get('/h/{slug:[a-z0-9-]+}/manifest.webmanifest', ['controller' => 'Pwa', 'action' => 'manifest']);
$router->post('/logout', ['controller' => 'Auth', 'action' => 'logout']);

// Dashboard
$router->get('/dashboard', ['controller' => 'Dashboard', 'action' => 'index']);
$router->get('/dashboard/stats', ['controller' => 'Dashboard', 'action' => 'stats']);
$router->get('/dashboard/charts', ['controller' => 'Dashboard', 'action' => 'charts']);

// Fase OP-A: tablero operativo diario GET/read-only. Sin acciones, Caja, pagos ni /api/sync.
$router->get('/operacion/diaria', ['controller' => 'Operacion', 'action' => 'diaria']);
// Fase 9A-B-A: conciliacion financiera GET/read-only. Sin acciones, Caja operativa ni /api/sync.
$router->get('/operacion/conciliacion-financiera', ['controller' => 'Operacion', 'action' => 'conciliacionFinanciera']);

// Centro de notificaciones
$router->get('/notificaciones', ['controller' => 'Notificacion', 'action' => 'index']);
$router->get('/notificaciones/{id:[0-9]+}/abrir', ['controller' => 'Notificacion', 'action' => 'abrir']);
$router->post('/notificaciones/marcar-todas-leidas', ['controller' => 'Notificacion', 'action' => 'marcarTodasLeidas']);
$router->post('/notificaciones/{id:[0-9]+}/leer', ['controller' => 'Notificacion', 'action' => 'marcarLeida']);
$router->post('/notificaciones/{id:[0-9]+}/resolver', ['controller' => 'Notificacion', 'action' => 'resolver']);
$router->post('/notificaciones/{id:[0-9]+}/descartar', ['controller' => 'Notificacion', 'action' => 'descartar']);

// Fase 2F/3A: catalogo de proveedores y ficha read-only con historial. No incluye pagos ni caja.
$router->get('/proveedores', ['controller' => 'Proveedor', 'action' => 'index']);
$router->get('/proveedores/crear', ['controller' => 'Proveedor', 'action' => 'crear']);
$router->post('/proveedores', ['controller' => 'Proveedor', 'action' => 'guardar']);
$router->get('/proveedores/{id:[0-9]+}', ['controller' => 'Proveedor', 'action' => 'ver']);
$router->get('/proveedores/{id:[0-9]+}/editar', ['controller' => 'Proveedor', 'action' => 'editar']);
$router->post('/proveedores/{id:[0-9]+}/actualizar', ['controller' => 'Proveedor', 'action' => 'actualizar']);
$router->post('/proveedores/{id:[0-9]+}/desactivar', ['controller' => 'Proveedor', 'action' => 'desactivar']);
$router->post('/proveedores/{id:[0-9]+}/reactivar', ['controller' => 'Proveedor', 'action' => 'reactivar']);

// Fase 2R/2V/2X/2Y: compras en borrador, recepcion minima, detalle y reportes read-only. Sin pagos, caja, CxP ni documentos.
$router->get('/compras', ['controller' => 'Compra', 'action' => 'index']);
$router->get('/compras/crear', ['controller' => 'Compra', 'action' => 'crear']);
$router->get('/compras/reportes/recibidas', ['controller' => 'Compra', 'action' => 'reporteRecibidas']);
$router->get('/compras/{id:[0-9]+}', ['controller' => 'Compra', 'action' => 'ver']);
$router->post('/compras', ['controller' => 'Compra', 'action' => 'guardar']);
$router->post('/compras/{id:[0-9]+}/recibir', ['controller' => 'Compra', 'action' => 'recibir']);

// Fase 3B/3C-B/3D: CxP base, preview GET, generacion manual, simulador Caja y pago proveedor controlado.
$router->get('/cuentas-por-pagar', ['controller' => 'CuentaPorPagar', 'action' => 'index']);
$router->get('/cuentas-por-pagar/generacion-preview', ['controller' => 'CuentaPorPagar', 'action' => 'generacionPreview']);
$router->get('/cuentas-por-pagar/simulador-caja', ['controller' => 'CuentaPorPagar', 'action' => 'simuladorCaja']);
$router->post('/cuentas-por-pagar/generar-desde-compra/{id:[0-9]+}', ['controller' => 'CuentaPorPagar', 'action' => 'generarDesdeCompra']);
$router->post('/cuentas-por-pagar/{id:[0-9]+}/registrar-pago-caja', ['controller' => 'CuentaPorPagar', 'action' => 'registrarPagoCaja']);
$router->post('/cuentas-por-pagar/{id:[0-9]+}/movimientos/{movimientoid:[0-9]+}/revertir-pago-caja', ['controller' => 'CuentaPorPagar', 'action' => 'revertirPagoCaja']);
$router->get('/cuentas-por-pagar/{id:[0-9]+}', ['controller' => 'CuentaPorPagar', 'action' => 'ver']);

$router->get('/cuentas-por-cobrar', ['controller' => 'CuentaPorCobrar', 'action' => 'index']);
$router->get('/cuentas-por-cobrar/operativas', ['controller' => 'CuentaPorCobrar', 'action' => 'operativas']);
$router->get('/cuentas-por-cobrar/simulador-caja', ['controller' => 'CuentaPorCobrar', 'action' => 'simuladorCaja']);
$router->get('/cuentas-por-cobrar/operativas/{id:[0-9]+}', ['controller' => 'CuentaPorCobrar', 'action' => 'verOperativa']);
$router->post('/cuentas-por-cobrar/generar-desde-reservacion/{id:[0-9]+}', ['controller' => 'CuentaPorCobrar', 'action' => 'generarDesdeReservacion']);
$router->post('/cuentas-por-cobrar/operativas/{id:[0-9]+}/registrar-cobro-caja', ['controller' => 'CuentaPorCobrar', 'action' => 'registrarCobroCaja']);
$router->post('/cuentas-por-cobrar/operativas/{id:[0-9]+}/movimientos/{movimientoid:[0-9]+}/revertir-cobro-caja', ['controller' => 'CuentaPorCobrar', 'action' => 'revertirCobroCaja']);

// Fase 4A/4B/4D: Centro Documental con metadata, carga segura, descarga autenticada, edicion limitada, archivado reversible y baja logica. Sin borrado fisico.
$router->get('/documentos', ['controller' => 'Documento', 'action' => 'index']);
$router->get('/documentos/subir', ['controller' => 'Documento', 'action' => 'subir']);
$router->post('/documentos/subir', ['controller' => 'Documento', 'action' => 'guardar']);
$router->get('/documentos/entidad/{tipo:[a-z_]+}/{id:[0-9]+}', ['controller' => 'Documento', 'action' => 'entidad']);
$router->get('/documentos/{id:[0-9]+}/editar', ['controller' => 'Documento', 'action' => 'editar']);
$router->post('/documentos/{id:[0-9]+}/actualizar', ['controller' => 'Documento', 'action' => 'actualizar']);
$router->post('/documentos/{id:[0-9]+}/archivar', ['controller' => 'Documento', 'action' => 'archivar']);
$router->post('/documentos/{id:[0-9]+}/restaurar', ['controller' => 'Documento', 'action' => 'restaurar']);
$router->post('/documentos/{id:[0-9]+}/eliminar', ['controller' => 'Documento', 'action' => 'eliminar']);
$router->get('/documentos/{id:[0-9]+}/descargar', ['controller' => 'Documento', 'action' => 'descargar']);
$router->get('/documentos/{id:[0-9]+}', ['controller' => 'Documento', 'action' => 'ver']);

// Fase NP-A/NP-F-A/5E-C/5E-D-A: Personal base, ledger laboral, reporte, simulador Caja y pago laboral controlado.
$router->get('/trabajadores', ['controller' => 'Trabajador', 'action' => 'index']);
$router->get('/trabajadores/reporte', ['controller' => 'Trabajador', 'action' => 'reporte']);
$router->get('/trabajadores/nomina/periodos', ['controller' => 'Trabajador', 'action' => 'nominaPeriodos']);
$router->get('/trabajadores/nomina/periodos/preview', ['controller' => 'Trabajador', 'action' => 'nominaPeriodoPreview']);
$router->get('/trabajadores/nomina/periodos/{id:[0-9]+}', ['controller' => 'Trabajador', 'action' => 'nominaPeriodoDetalle']);
$router->post('/trabajadores/nomina/periodos/cerrar', ['controller' => 'Trabajador', 'action' => 'cerrarNominaPeriodo']);
$router->post('/trabajadores/nomina/periodos/{id:[0-9]+}/aprobar', ['controller' => 'Trabajador', 'action' => 'aprobarNominaPeriodo']);
$router->post('/trabajadores/nomina/periodos/{id:[0-9]+}/anular', ['controller' => 'Trabajador', 'action' => 'anularNominaPeriodo']);
$router->get('/trabajadores/nomina/preview', ['controller' => 'Trabajador', 'action' => 'nominaPreview']);
$router->get('/trabajadores/nomina/preview/exportar', ['controller' => 'Trabajador', 'action' => 'exportarNominaPreview']);
$router->get('/trabajadores/pagos-caja/reporte/exportar', ['controller' => 'Trabajador', 'action' => 'exportarReportePagosCaja']);
$router->get('/trabajadores/pagos-caja/reporte', ['controller' => 'Trabajador', 'action' => 'reportePagosCaja']);
$router->get('/trabajadores/pagos-caja/simulador', ['controller' => 'Trabajador', 'action' => 'simuladorPagoCaja']);
$router->get('/trabajadores/crear', ['controller' => 'Trabajador', 'action' => 'crear']);
$router->post('/trabajadores', ['controller' => 'Trabajador', 'action' => 'guardar']);
$router->get('/trabajadores/{id:[0-9]+}/recibo-laboral/pdf', ['controller' => 'Trabajador', 'action' => 'reciboLaboralPdf']);
$router->get('/trabajadores/{id:[0-9]+}/recibo-laboral', ['controller' => 'Trabajador', 'action' => 'reciboLaboral']);
$router->get('/trabajadores/{id:[0-9]+}', ['controller' => 'Trabajador', 'action' => 'ver']);
$router->post('/trabajadores/{id:[0-9]+}/registrar-pago-caja', ['controller' => 'Trabajador', 'action' => 'registrarPagoCaja']);
$router->post('/trabajadores/{id:[0-9]+}/pagos-caja/{pagoid:[0-9]+}/revertir', ['controller' => 'Trabajador', 'action' => 'revertirPagoCaja']);
$router->get('/trabajadores/{id:[0-9]+}/editar', ['controller' => 'Trabajador', 'action' => 'editar']);
$router->post('/trabajadores/{id:[0-9]+}/actualizar', ['controller' => 'Trabajador', 'action' => 'actualizar']);
$router->post('/trabajadores/{id:[0-9]+}/conceptos-laborales', ['controller' => 'Trabajador', 'action' => 'registrarConceptoLaboral']);
$router->post('/trabajadores/{id:[0-9]+}/anticipos', ['controller' => 'Trabajador', 'action' => 'registrarAnticipoLaboral']);
$router->post('/trabajadores/{id:[0-9]+}/prestamos', ['controller' => 'Trabajador', 'action' => 'registrarPrestamoLaboral']);
$router->post('/trabajadores/{id:[0-9]+}/asistencias', ['controller' => 'Trabajador', 'action' => 'registrarAsistenciaLaboral']);
$router->post('/trabajadores/{id:[0-9]+}/baja-logica', ['controller' => 'Trabajador', 'action' => 'bajaLogica']);
$router->post('/trabajadores/{id:[0-9]+}/reactivar', ['controller' => 'Trabajador', 'action' => 'reactivar']);

// Fase TLM-I-A: tareas operativas con alta, asignacion, estados manuales y reporte read-only. Sin cambios de estado de habitacion ni Caja.
$router->get('/tareas', ['controller' => 'Tarea', 'action' => 'index']);
$router->get('/tareas/reporte', ['controller' => 'Tarea', 'action' => 'reporte']);
$router->get('/tareas/agenda', ['controller' => 'Tarea', 'action' => 'agenda']);
$router->get('/tareas/crear', ['controller' => 'Tarea', 'action' => 'crear']);
$router->post('/tareas', ['controller' => 'Tarea', 'action' => 'guardar']);
$router->post('/tareas/desde-limpieza/{id:[0-9]+}', ['controller' => 'Tarea', 'action' => 'crearDesdeLimpieza']);
$router->post('/tareas/desde-mantenimiento/{id:[0-9]+}', ['controller' => 'Tarea', 'action' => 'crearDesdeMantenimiento']);
$router->post('/tareas/{id:[0-9]+}/asignar', ['controller' => 'Tarea', 'action' => 'asignar']);
$router->post('/tareas/{id:[0-9]+}/iniciar', ['controller' => 'Tarea', 'action' => 'iniciar']);
$router->post('/tareas/{id:[0-9]+}/completar', ['controller' => 'Tarea', 'action' => 'completar']);
$router->post('/tareas/{id:[0-9]+}/cancelar', ['controller' => 'Tarea', 'action' => 'cancelar']);
$router->get('/tareas/{id:[0-9]+}', ['controller' => 'Tarea', 'action' => 'ver']);

$router->get('/api/pwa-push/public-key', ['controller' => 'PwaPush', 'action' => 'publicKey']);
$router->post('/api/pwa-push/subscribe', ['controller' => 'PwaPush', 'action' => 'subscribe']);
$router->post('/api/pwa-push/unsubscribe', ['controller' => 'PwaPush', 'action' => 'unsubscribe']);
$router->post('/api/pwa-push/test', ['controller' => 'PwaPush', 'action' => 'test']);

// Panel Medisoft interno SaaS
$router->get('/admin/saas/hoteles', ['controller' => 'SaasAdmin', 'action' => 'hoteles']);
$router->get('/admin/saas/hoteles/crear', ['controller' => 'SaasAdmin', 'action' => 'crearHotel']);
$router->post('/admin/saas/hoteles', ['controller' => 'SaasAdmin', 'action' => 'guardarHotel']);
$router->get('/admin/saas/hoteles/{id:[0-9]+}', ['controller' => 'SaasAdmin', 'action' => 'verHotel']);
$router->get('/admin/saas/hoteles/{id:[0-9]+}/editar', ['controller' => 'SaasAdmin', 'action' => 'editarHotel']);
$router->post('/admin/saas/hoteles/{id:[0-9]+}/actualizar', ['controller' => 'SaasAdmin', 'action' => 'actualizarHotel']);
$router->post('/admin/saas/hoteles/{id:[0-9]+}/estado', ['controller' => 'SaasAdmin', 'action' => 'estadoHotel']);
$router->post('/admin/saas/hoteles/{id:[0-9]+}/usuarios', ['controller' => 'SaasAdmin', 'action' => 'guardarAdminHotel']);
$router->post('/admin/saas/hoteles/{id:[0-9]+}/modulos', ['controller' => 'SaasAdmin', 'action' => 'actualizarModulosHotel']);
$router->post('/admin/saas/hoteles/{id:[0-9]+}/plan', ['controller' => 'SaasAdmin', 'action' => 'actualizarPlanHotel']);
$router->post('/admin/saas/hoteles/{id:[0-9]+}/branding', ['controller' => 'SaasAdmin', 'action' => 'actualizarBrandingHotel']);

// APIs del Dashboard para actualizaciÃ³n en tiempo real
$router->get('/api/dashboard/ocupacion', ['controller' => 'Api', 'action' => 'ocupacionActual']);
$router->get('/api/dashboard/movimientos-recientes', ['controller' => 'Api', 'action' => 'movimientosRecientes']);
$router->get('/api/dashboard/alertas', ['controller' => 'Api', 'action' => 'alertasDashboard']);

// GestiÃ³n de Habitaciones
$router->get('/habitaciones', ['controller' => 'Habitacion', 'action' => 'index']);
$router->get('/habitaciones/disponibles', ['controller' => 'Habitacion', 'action' => 'disponibles']);
$router->get('/habitaciones/create', ['controller' => 'Habitacion', 'action' => 'crear']);
$router->post('/habitaciones/store', ['controller' => 'Habitacion', 'action' => 'guardar']);
$router->get('/habitaciones/{id:[0-9]+}', ['controller' => 'Habitacion', 'action' => 'ver']);
$router->get('/habitaciones/{id:[0-9]+}/edit', ['controller' => 'Habitacion', 'action' => 'editar']);
$router->post('/habitaciones/{id:[0-9]+}/update', ['controller' => 'Habitacion', 'action' => 'actualizar']);
$router->post('/habitaciones/{id:[0-9]+}/delete', ['controller' => 'Habitacion', 'action' => 'eliminar']);
$router->post('/habitaciones/{id:[0-9]+}/mantenimiento', ['controller' => 'Habitacion', 'action' => 'mantenimiento']);
$router->post('/habitaciones/{id:[0-9]+}/programar-mantenimiento', ['controller' => 'Habitacion', 'action' => 'programarMantenimiento']);
$router->post('/habitaciones/cancelar-mantenimiento-programado/{id:[0-9]+}', ['controller' => 'Habitacion', 'action' => 'cancelarMantenimientoProgramado']);
$router->post('/habitaciones/activar-mantenimiento-programado/{id:[0-9]+}', ['controller' => 'Habitacion', 'action' => 'activarMantenimientoProgramado']);
$router->post('/habitaciones/{id:[0-9]+}/liberar', ['controller' => 'Habitacion', 'action' => 'liberar']);
$router->post('/habitaciones/{id:[0-9]+}/cambiar-estado', ['controller' => 'Habitacion', 'action' => 'cambiarEstado']);
$router->post('/habitaciones/liberar-multiples', ['controller' => 'Habitacion', 'action' => 'liberarMultiples']);
// Pendiente Fase 1B: no existe HabitacionController::verImagenAction().
// Usar /habitaciones/{id}/imagenes o /api/habitaciones/{id}/imagen-info.
// $router->get('/habitaciones/{id:[0-9]+}/imagen', ['controller' => 'Habitacion', 'action' => 'verImagen']);
$router->get('/habitaciones/{id:[0-9]+}/historial', ['controller' => 'Habitacion', 'action' => 'historial']);

// GestiÃ³n de HuÃ©spedes
$router->get('/huespedes', ['controller' => 'Huesped', 'action' => 'index']);
$router->get('/huespedes/create', ['controller' => 'Huesped', 'action' => 'crear']);
$router->post('/huespedes/store', ['controller' => 'Huesped', 'action' => 'guardar']);
$router->get('/huespedes/{id:[0-9]+}', ['controller' => 'Huesped', 'action' => 'ver']);
$router->get('/huespedes/{id:[0-9]+}/edit', ['controller' => 'Huesped', 'action' => 'editar']);
$router->post('/huespedes/{id:[0-9]+}/update', ['controller' => 'Huesped', 'action' => 'actualizar']);
$router->get('/huespedes/buscar', ['controller' => 'Huesped', 'action' => 'buscar']);

// Sistema de Reservaciones
$router->get('/reservaciones', ['controller' => 'Reservacion', 'action' => 'index']);
$router->get('/reservaciones/crear', ['controller' => 'Reservacion', 'action' => 'crear']);
$router->post('/reservaciones/guardar', ['controller' => 'Reservacion', 'action' => 'guardar']);
$router->post('/reservaciones/cotizacion-pdf', ['controller' => 'Reservacion', 'action' => 'cotizacionPdf']);
$router->get('/reservaciones/ver/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'ver']);
// Pendiente Fase 1B: no existen ReservacionController::editarAction() ni actualizarAction().
// El flujo seguro registrado hoy es /reservaciones/editar-habitaciones/{id}.
// $router->get('/reservaciones/editar/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'editar']);
// $router->post('/reservaciones/actualizar/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'actualizar']);
$router->post('/reservaciones/check-in/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkIn']);
$router->post('/reservaciones/check-out/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkOut']);

$router->post('/reservaciones/check-out-parcial/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkOutParcialAction']);
$router->post('/reservaciones/cancelar/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'cancelar']);
$router->get('/reservaciones/calendario', ['controller' => 'Reservacion', 'action' => 'calendario']);
$router->post('/reservaciones/check-out-rapido/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkOutRapido']);

// Sistema de Check-in TardÃ­o
$router->get('/reservaciones/check-in-tardio/{id:[0-9]+}', [
    'controller' => 'Reservacion',
    'action' => 'checkInTardio'
]);

$router->post('/reservaciones/procesar-check-in-tardio', [
    'controller' => 'Reservacion',
    'action' => 'procesarCheckInTardio'
]);

$router->get('/api/reservaciones/verificar-checkin/{id:[0-9]+}', [
    'controller' => 'Reservacion',
    'action' => 'verificarCheckIn'
]);
// Rutas para el sistema de notas de reservaciones
$router->post('/reservaciones/agregar-nota', [
    'controller' => 'Reservacion',
    'action' => 'agregarNota'
]);

$router->get('/reservaciones/obtener-notas', [
    'controller' => 'Reservacion',
    'action' => 'obtenerNotas'
]);
// Rutas AJAX para reservaciones
$router->post('/reservaciones/verificar-disponibilidad', ['controller' => 'Api', 'action' => 'verificarDisponibilidad']);
$router->get('/reservaciones/buscar-huesped', ['controller' => 'Api', 'action' => 'buscarHuespedes']);
$router->get('/reservaciones/habitaciones-disponibles', ['controller' => 'Api', 'action' => 'habitacionesDisponibles']);

$router->get('/reservaciones/exportar-pdf', ['controller' => 'Reservacion', 'action' => 'exportarPDF']);
$router->get('/reservaciones/exportar-excel', ['controller' => 'Reservacion', 'action' => 'exportarExcel']);

// Ruta para editar habitaciones de una reservaciÃ³n (GET)
$router->get('/reservaciones/editar-habitaciones/{id:[0-9]+}', [
    'controller' => 'Reservacion',
    'action' => 'editarHabitaciones'
]);

// Ruta para actualizar habitaciones (POST)
$router->post('/reservaciones/actualizar-habitaciones', [
    'controller' => 'Reservacion', 
    'action' => 'actualizarHabitaciones'
]);
// GestiÃ³n de tarifas dinÃ¡micas

// ============================================================================
// SISTEMA COMPLETO DE INVENTARIOS - RUTAS ACTUALIZADAS
// ============================================================================
// ============================================================================
// SISTEMA COMPLETO DE INVENTARIOS - RUTAS ACTUALIZADAS
// ============================================================================
// Rutas de inventario
$router->get('/inventario', ['controller' => 'Inventario', 'action' => 'index']);
$router->get('/inventario/nuevo', ['controller' => 'Inventario', 'action' => 'nuevo']);
$router->post('/inventario/guardar', ['controller' => 'Inventario', 'action' => 'guardar']);

// AGREGAR ESTAS DOS LÃNEAS PARA EDITAR:
// Sistema de inventarios
$router->get('/inventario/editar/{id:[0-9]+}', ['controller' => 'Inventario', 'action' => 'editar']);
$router->post('/inventario/actualizar/{id:[0-9]+}', ['controller' => 'Inventario', 'action' => 'actualizar']);
$router->post('/inventario/eliminar/{id:[0-9]+}', ['controller' => 'Inventario', 'action' => 'eliminar']);

$router->get('/inventario/entrada', ['controller' => 'Inventario', 'action' => 'entrada']);
$router->post('/inventario/procesarEntrada', ['controller' => 'Inventario', 'action' => 'procesarEntrada']);
$router->get('/inventario/salida', ['controller' => 'Inventario', 'action' => 'salida']);
$router->post('/inventario/procesarSalida', ['controller' => 'Inventario', 'action' => 'procesarSalida']);
$router->get('/inventario/configuracion', ['controller' => 'Inventario', 'action' => 'configuracion']);
$router->post('/inventario/guardarConfiguracion', ['controller' => 'Inventario', 'action' => 'guardarConfiguracion']);
$router->get('/inventario/movimientos', ['controller' => 'Inventario', 'action' => 'movimientos']);
$router->get('/inventarios/movimientos', ['controller' => 'Inventario', 'action' => 'movimientos']);
// Rutas de inventario - exportaciÃ³n
$router->get('/inventario/exportar', ['controller' => 'Inventario', 'action' => 'exportar']);
$router->post('/inventario/generarPdfMovimientos', ['controller' => 'Inventario', 'action' => 'generarPdfMovimientos']);
$router->get('/inventario/ajuste/{id:[0-9]+}', ['controller' => 'Inventario', 'action' => 'ajuste']);
$router->post('/inventario/ajuste/{id:[0-9]+}', ['controller' => 'Inventario', 'action' => 'procesarAjuste']);

// Ruta debug deshabilitada en produccion.
// $router->get('/inventario/debug-movimientos', ['controller' => 'Inventario', 'action' => 'debugMovimientos']);
// ============================================================================
// SISTEMA COMPLETO DE REPORTES - RUTAS ACTUALIZADAS
// ============================================================================

// PÃ¡gina principal de reportes
// ============================================================================
// SISTEMA COMPLETO DE REPORTES - RUTAS CORREGIDAS
// ============================================================================

// ============================================================================
// SISTEMA COMPLETO DE REPORTES - RUTAS CORREGIDAS
// ============================================================================

// PÃ¡gina principal de reportes
$router->get('/reportes', ['controller' => 'Reportes', 'action' => 'index']);
$router->get('/reportes/ejecutivo', ['controller' => 'Reportes', 'action' => 'ejecutivo']);
$router->get('/reportes/gerencial-diario', ['controller' => 'Reportes', 'action' => 'gerencialDiario']);
$router->get('/reportes/gerencial-diario/pdf', ['controller' => 'Reportes', 'action' => 'gerencialDiarioPdf']);
$router->get('/reportes/links', ['controller' => 'ReporteLink', 'action' => 'historial']);
$router->get('/reportes/links/{id:[0-9]+}/descargar', ['controller' => 'ReporteLink', 'action' => 'descargarInterno']);
$router->post('/reportes/links/{id:[0-9]+}/enviar-correo', ['controller' => 'ReporteLink', 'action' => 'enviarCorreo']);
$router->post('/reportes/links/{id:[0-9]+}/revocar', ['controller' => 'ReporteLink', 'action' => 'revocar']);
$router->get('/reportes/link/{token:[a-f0-9]+}', ['controller' => 'ReporteLink', 'action' => 'descargarPublico']);

// Reportes individuales
$router->get('/reportes/limpieza', ['controller' => 'Reportes', 'action' => 'limpieza']);
$router->get('/reportes/mantenimiento-programado', ['controller' => 'Reportes', 'action' => 'mantenimientoProgramado']);
$router->get('/reportes/ingresos-gastos', ['controller' => 'Reportes', 'action' => 'ingresosGastos']);
$router->get('/reportes/procedencia', ['controller' => 'Reportes', 'action' => 'procedencia']);
$router->get('/reportes/habitaciones-rentables', ['controller' => 'Reportes', 'action' => 'habitacionesRentables']);
$router->get('/reportes/ocupacion', ['controller' => 'Reportes', 'action' => 'ocupacion']);
$router->get('/reportes/estancia', ['controller' => 'Reportes', 'action' => 'estancia']);
$router->get('/reportes/ranking-estados', ['controller' => 'Reportes', 'action' => 'rankingEstados']);
$router->get('/reportes/mantenimiento', ['controller' => 'Reportes', 'action' => 'mantenimiento']); // NUEVA LÃNEA
// En tu archivo routes.php, despuÃ©s de las otras rutas de reportes
// Rutas de prueba deshabilitadas en produccion.
// $router->get('/reportes/test-datos', ['controller' => 'Reportes', 'action' => 'testDatos']);
// $router->get('/reportes/test-usuario', ['controller' => 'Reportes', 'action' => 'testUsuario']);
// ExportaciÃ³n y datos AJAX
$router->get('/reportes/exportar-pdf', ['controller' => 'Reportes', 'action' => 'exportarPdf']);
$router->get('/reportes/datos-grafica', ['controller' => 'Reportes', 'action' => 'datosGrafica']);

// $router->get('/reportes/test-calculos', ['controller' => 'Reportes', 'action' => 'testCalculoTotales']);

// GestiÃ³n de Usuarios (solo gerente)
$router->get('/usuarios', ['controller' => 'Usuario', 'action' => 'index']);
$router->get('/usuarios/create', ['controller' => 'Usuario', 'action' => 'crear']);
$router->post('/usuarios/store', ['controller' => 'Usuario', 'action' => 'guardar']);
$router->get('/usuarios/{id:[0-9]+}/edit', ['controller' => 'Usuario', 'action' => 'editar']);
$router->post('/usuarios/{id:[0-9]+}/update', ['controller' => 'Usuario', 'action' => 'actualizar']);
$router->post('/usuarios/{id:[0-9]+}/toggle', ['controller' => 'Usuario', 'action' => 'cambiarEstado']);

// API para validaciÃ³n de imÃ¡genes
$router->post('/api/validate-image', ['controller' => 'Api', 'action' => 'validarImagen']);
$router->get('/api/habitaciones/{id:[0-9]+}/imagen-info', ['controller' => 'Api', 'action' => 'informacionImagen']);

// Ruta para setup inicial (ejecutar una sola vez)
// Ruta de setup deshabilitada en produccion. Ejecutar tareas de setup por CLI.
// $router->get('/setup/directories', ['controller' => 'Setup', 'action' => 'createDirectories']);

// ConfiguraciÃ³n (solo gerente)
$router->get('/configuracion', ['controller' => 'Configuracion', 'action' => 'index']);
$router->post('/configuracion/update', ['controller' => 'Configuracion', 'action' => 'actualizar']);
$router->post('/configuracion/pwa-push/{id:[0-9]+}/revocar', ['controller' => 'PwaPush', 'action' => 'revocarDispositivo']);
$router->get('/configuracion/backup', ['controller' => 'Configuracion', 'action' => 'backup']);
$router->post('/configuracion/backup/create', ['controller' => 'Configuracion', 'action' => 'crearBackup']);
$router->get('/configuracion/backup/descargar', ['controller' => 'Configuracion', 'action' => 'descargarBackup']);
$router->get('/configuracion/tarifas', ['controller' => 'Tarifas', 'action' => 'index']);
$router->get('/configuracion/tarifas/crear', ['controller' => 'Tarifas', 'action' => 'crear']);
$router->post('/configuracion/tarifas/crear', ['controller' => 'Tarifas', 'action' => 'crear']);
$router->get('/configuracion/tarifas/editar/{id:\d+}', ['controller' => 'Tarifas', 'action' => 'editar']);
$router->post('/configuracion/tarifas/editar/{id:\d+}', ['controller' => 'Tarifas', 'action' => 'editar']);
$router->post('/configuracion/tarifas/toggle', ['controller' => 'Tarifas', 'action' => 'toggle']);
$router->post('/configuracion/tarifas/eliminar', ['controller' => 'Tarifas', 'action' => 'eliminar']);
$router->post('/configuracion/tarifas/previsualizar', ['controller' => 'Tarifas', 'action' => 'previsualizar']);
$router->get('/configuracion/tarifas/detalle', ['controller' => 'Tarifas', 'action' => 'detalle']);

// APIs para AJAX
$router->get('/api/buscar', ['controller' => 'Api', 'action' => 'buscarGlobal']);
$router->get('/api/reservaciones/hoy', ['controller' => 'Api', 'action' => 'reservacionesHoy']);
$router->post('/api/sync', ['controller' => 'Api', 'action' => 'sync']);
$router->get('/api/habitaciones/disponibles', ['controller' => 'Api', 'action' => 'habitacionesDisponibles']);
// Agregar esta lÃ­nea despuÃ©s de las otras rutas de API de habitaciones (alrededor de la lÃ­nea 295)
$router->get('/api/habitaciones/todas-con-ocupacion', ['controller' => 'Api', 'action' => 'todasConOcupacion']);
$router->get('/api/huespedes/search', ['controller' => 'Api', 'action' => 'buscarHuespedes']);
$router->get('/api/dashboard/stats', ['controller' => 'Api', 'action' => 'estadisticasDashboard']);
$router->post('/api/habitaciones/verificar-disponibilidad', ['controller' => 'Api', 'action' => 'verificarDisponibilidad']);
$router->get('/api/habitaciones/calcular-precio', ['controller' => 'Api', 'action' => 'calcularPrecio']);



// Rutas legacy de correccion deshabilitadas en produccion.
// $router->get('/correccion', ['controller' => 'Correccion', 'action' => 'index']);
// $router->post('/correccion/aplicar', ['controller' => 'Correccion', 'action' => 'aplicar']);
