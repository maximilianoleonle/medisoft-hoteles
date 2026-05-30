<?php
/**
 * Definición de Rutas
 * Los Cedros
 */

// Rutas AJAX para vehículos de huéspedes
$router->post('/huespedes/agregar-vehiculo', [
    'controller' => 'Huesped',
    'action' => 'agregarVehiculo'
]);

$router->post('/huespedes/actualizar-vehiculo', [
    'controller' => 'Huesped',
    'action' => 'actualizarVehiculo'
]);

// Check-in tardío - Vista
$router->post('/reservaciones/cotizacion-reservacion-pdf', ['controller' => 'Reservacion', 'action' => 'cotizacionReservacionPdf']);
// Modificar días de reservación (AJAX)
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
// RUTAS PARA GESTIÓN DE MÚLTIPLES IMÁGENES
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

$router->post('/habitaciones/{id:[0-9]+}/reordenar-imagenes', [
    'controller' => 'Habitacion',
    'action' => 'reordenarImagenes'
]);



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
    'action' => 'checkin'
]);

$router->get('/caja/descargar-pdf/{id:[0-9]+}', ['controller' => 'Caja', 'action' => 'descargarPDF']);

// Remotos múltiples
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



// Gestión de categorías (solo gerente)
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

// Rutas de autenticación
$router->get('/', ['controller' => 'Auth', 'action' => 'login']);
$router->get('/login', ['controller' => 'Auth', 'action' => 'login']);
$router->post('/login/authenticate', ['controller' => 'Auth', 'action' => 'authenticate']);
$router->get('/h/{slug:[a-z0-9-]+}/login', ['controller' => 'Auth', 'action' => 'hotelLogin']);
$router->post('/h/{slug:[a-z0-9-]+}/login/authenticate', ['controller' => 'Auth', 'action' => 'hotelAuthenticate']);
$router->post('/logout', ['controller' => 'Auth', 'action' => 'logout']);

// Dashboard
$router->get('/dashboard', ['controller' => 'Dashboard', 'action' => 'index']);
$router->get('/dashboard/stats', ['controller' => 'Dashboard', 'action' => 'stats']);
$router->get('/dashboard/charts', ['controller' => 'Dashboard', 'action' => 'charts']);

// Panel Medisoft interno SaaS
$router->get('/admin/saas/hoteles', ['controller' => 'SaasAdmin', 'action' => 'hoteles']);
$router->get('/admin/saas/hoteles/crear', ['controller' => 'SaasAdmin', 'action' => 'crearHotel']);
$router->post('/admin/saas/hoteles', ['controller' => 'SaasAdmin', 'action' => 'guardarHotel']);
$router->get('/admin/saas/hoteles/{id:[0-9]+}', ['controller' => 'SaasAdmin', 'action' => 'verHotel']);
$router->get('/admin/saas/hoteles/{id:[0-9]+}/editar', ['controller' => 'SaasAdmin', 'action' => 'editarHotel']);
$router->post('/admin/saas/hoteles/{id:[0-9]+}/actualizar', ['controller' => 'SaasAdmin', 'action' => 'actualizarHotel']);
$router->post('/admin/saas/hoteles/{id:[0-9]+}/estado', ['controller' => 'SaasAdmin', 'action' => 'estadoHotel']);

// APIs del Dashboard para actualización en tiempo real
$router->get('/api/dashboard/ocupacion', ['controller' => 'Api', 'action' => 'ocupacionActual']);
$router->get('/api/dashboard/movimientos-recientes', ['controller' => 'Api', 'action' => 'movimientosRecientes']);
$router->get('/api/dashboard/alertas', ['controller' => 'Api', 'action' => 'alertasDashboard']);

// Gestión de Habitaciones
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
$router->post('/habitaciones/{id:[0-9]+}/liberar', ['controller' => 'Habitacion', 'action' => 'liberar']);
$router->post('/habitaciones/{id:[0-9]+}/cambiar-estado', ['controller' => 'Habitacion', 'action' => 'cambiarEstado']);
$router->post('/habitaciones/liberar-multiples', ['controller' => 'Habitacion', 'action' => 'liberarMultiples']);
$router->get('/habitaciones/{id:[0-9]+}/imagen', ['controller' => 'Habitacion', 'action' => 'verImagen']);
$router->get('/habitaciones/{id:[0-9]+}/historial', ['controller' => 'Habitacion', 'action' => 'historial']);

// Gestión de Huéspedes
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
$router->get('/reservaciones/editar/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'editar']);
$router->post('/reservaciones/actualizar/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'actualizar']);
$router->post('/reservaciones/check-in/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkIn']);
$router->post('/reservaciones/check-out/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkOut']);

$router->post('/reservaciones/check-out-parcial/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkOutParcialAction']);
$router->post('/reservaciones/cancelar/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'cancelar']);
$router->get('/reservaciones/calendario', ['controller' => 'Reservacion', 'action' => 'calendario']);
$router->post('/reservaciones/check-out-rapido/{id:[0-9]+}', ['controller' => 'Reservacion', 'action' => 'checkOutRapido']);

// Sistema de Check-in Tardío
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
$router->post('/reservaciones/verificar-disponibilidad', ['controller' => 'Reservacion', 'action' => 'verificarDisponibilidad']);
$router->get('/reservaciones/buscar-huesped', ['controller' => 'Reservacion', 'action' => 'buscarHuesped']);
$router->get('/reservaciones/habitaciones-disponibles', ['controller' => 'Reservacion', 'action' => 'habitacionesDisponibles']);

$router->get('/reservaciones/exportar-pdf', ['controller' => 'Reservacion', 'action' => 'exportarPDF']);
$router->get('/reservaciones/exportar-excel', ['controller' => 'Reservacion', 'action' => 'exportarExcel']);

// Ruta para editar habitaciones de una reservación (GET)
$router->get('/reservaciones/editar-habitaciones/{id:[0-9]+}', [
    'controller' => 'Reservacion',
    'action' => 'editarHabitaciones'
]);

// Ruta para actualizar habitaciones (POST)
$router->post('/reservaciones/actualizar-habitaciones', [
    'controller' => 'Reservacion', 
    'action' => 'actualizarHabitaciones'
]);
// Gestión de tarifas dinámicas

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

// AGREGAR ESTAS DOS LÍNEAS PARA EDITAR:
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
// Rutas de inventario - exportación
$router->get('/inventario/exportar', ['controller' => 'Inventario', 'action' => 'exportar']);
$router->post('/inventario/generarPdfMovimientos', ['controller' => 'Inventario', 'action' => 'generarPdfMovimientos']);

// Ruta debug deshabilitada en produccion.
// $router->get('/inventario/debug-movimientos', ['controller' => 'Inventario', 'action' => 'debugMovimientos']);
// ============================================================================
// SISTEMA COMPLETO DE REPORTES - RUTAS ACTUALIZADAS
// ============================================================================

// Página principal de reportes
// ============================================================================
// SISTEMA COMPLETO DE REPORTES - RUTAS CORREGIDAS
// ============================================================================

// ============================================================================
// SISTEMA COMPLETO DE REPORTES - RUTAS CORREGIDAS
// ============================================================================

// Página principal de reportes
$router->get('/reportes', ['controller' => 'Reportes', 'action' => 'index']);

// Reportes individuales
$router->get('/reportes/ingresos-gastos', ['controller' => 'Reportes', 'action' => 'ingresosGastos']);
$router->get('/reportes/procedencia', ['controller' => 'Reportes', 'action' => 'procedencia']);
$router->get('/reportes/habitaciones-rentables', ['controller' => 'Reportes', 'action' => 'habitacionesRentables']);
$router->get('/reportes/ocupacion', ['controller' => 'Reportes', 'action' => 'ocupacion']);
$router->get('/reportes/estancia', ['controller' => 'Reportes', 'action' => 'estancia']);
$router->get('/reportes/ranking-estados', ['controller' => 'Reportes', 'action' => 'rankingEstados']);
$router->get('/reportes/mantenimiento', ['controller' => 'Reportes', 'action' => 'mantenimiento']); // NUEVA LÍNEA
// En tu archivo routes.php, después de las otras rutas de reportes
// Rutas de prueba deshabilitadas en produccion.
// $router->get('/reportes/test-datos', ['controller' => 'Reportes', 'action' => 'testDatos']);
// $router->get('/reportes/test-usuario', ['controller' => 'Reportes', 'action' => 'testUsuario']);
// Exportación y datos AJAX
$router->get('/reportes/exportar-pdf', ['controller' => 'Reportes', 'action' => 'exportarPdf']);
$router->get('/reportes/datos-grafica', ['controller' => 'Reportes', 'action' => 'datosGrafica']);

// $router->get('/reportes/test-calculos', ['controller' => 'Reportes', 'action' => 'testCalculoTotales']);

// Gestión de Usuarios (solo gerente)
$router->get('/usuarios', ['controller' => 'Usuario', 'action' => 'index']);
$router->get('/usuarios/create', ['controller' => 'Usuario', 'action' => 'crear']);
$router->post('/usuarios/store', ['controller' => 'Usuario', 'action' => 'guardar']);
$router->get('/usuarios/{id:[0-9]+}/edit', ['controller' => 'Usuario', 'action' => 'editar']);
$router->post('/usuarios/{id:[0-9]+}/update', ['controller' => 'Usuario', 'action' => 'actualizar']);
$router->post('/usuarios/{id:[0-9]+}/toggle', ['controller' => 'Usuario', 'action' => 'cambiarEstado']);

// API para validación de imágenes
$router->post('/api/validate-image', ['controller' => 'Api', 'action' => 'validarImagen']);
$router->get('/api/habitaciones/{id:[0-9]+}/imagen-info', ['controller' => 'Api', 'action' => 'informacionImagen']);

// Ruta para setup inicial (ejecutar una sola vez)
// Ruta de setup deshabilitada en produccion. Ejecutar tareas de setup por CLI.
// $router->get('/setup/directories', ['controller' => 'Setup', 'action' => 'createDirectories']);

// Configuración (solo gerente)
$router->get('/configuracion', ['controller' => 'Configuracion', 'action' => 'index']);
$router->post('/configuracion/update', ['controller' => 'Configuracion', 'action' => 'actualizar']);
$router->get('/configuracion/backup', ['controller' => 'Configuracion', 'action' => 'backup']);
$router->post('/configuracion/backup/create', ['controller' => 'Configuracion', 'action' => 'crearBackup']);
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
// Agregar esta línea después de las otras rutas de API de habitaciones (alrededor de la línea 295)
$router->get('/api/habitaciones/todas-con-ocupacion', ['controller' => 'Api', 'action' => 'todasConOcupacion']);
$router->get('/api/huespedes/search', ['controller' => 'Api', 'action' => 'buscarHuespedes']);
$router->get('/api/dashboard/stats', ['controller' => 'Api', 'action' => 'estadisticasDashboard']);
$router->post('/api/habitaciones/verificar-disponibilidad', ['controller' => 'Api', 'action' => 'verificarDisponibilidad']);
$router->get('/api/habitaciones/calcular-precio', ['controller' => 'Api', 'action' => 'calcularPrecio']);



// Rutas legacy de correccion deshabilitadas en produccion.
// $router->get('/correccion', ['controller' => 'Correccion', 'action' => 'index']);
// $router->post('/correccion/aplicar', ['controller' => 'Correccion', 'action' => 'aplicar']);
