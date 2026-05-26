<?php
/**
 * GUÍA DE INTEGRACIÓN - SISTEMA DE INVENTARIO AUTOMÁTICO
 * Los Cedros
 * 
 * Este archivo muestra cómo integrar el sistema de inventario automático
 * con los módulos de Reservaciones y Habitaciones
 */

// ============================================================================
// INTEGRACIÓN CON RESERVACIONCONTROLLER
// ============================================================================

/**
 * EJEMPLO: Integración en ReservacionController::checkIn()
 * 
 * Agregar este código en el método checkIn después de actualizar el estado de la habitación
 */

// En ReservacionController.php - método checkInAction()
/*
public function checkInAction() {
    // ... código existente de check-in ...
    
    try {
        $this->db->beginTransaction();
        
        // 1. Procesar check-in normal (código existente)
        $sql = "UPDATE reservaciones SET estado = 'checkin', fecha_checkin = NOW() WHERE id = ?";
        $this->db->query($sql, [$reservacion_id]);
        
        // 2. Actualizar estado de habitación
        $sql = "UPDATE habitaciones SET estado = 'ocupada' WHERE id = ?";
        $this->db->query($sql, [$habitacion_id]);
        
        // 3. NUEVO: Procesar inventario automático en check-in
        require_once APP_PATH . '/helpers/inventario_helpers.php';
        
        $inventario_resultado = procesar_inventario_automatico_checkin($habitacion_id, $huesped_id);
        
        if ($inventario_resultado['success']) {
            // Log exitoso
            error_log("Check-in automático exitoso - Habitación {$habitacion_id}");
            
            // Opcional: Guardar resumen en la reservación
            if (!empty($inventario_resultado['movimientos'])) {
                $resumen_productos = json_encode($inventario_resultado['movimientos']);
                $sql = "UPDATE reservaciones SET inventario_checkin = ? WHERE id = ?";
                $this->db->query($sql, [$resumen_productos, $reservacion_id]);
            }
        } else {
            // Log de advertencia - el check-in continúa pero sin inventario automático
            error_log("Advertencia en inventario automático: " . $inventario_resultado['message']);
        }
        
        $this->db->commit();
        
        // 4. Mostrar mensaje de éxito con información de inventario
        $mensaje_exito = 'Check-in realizado exitosamente';
        
        if ($inventario_resultado['success'] && !empty($inventario_resultado['movimientos'])) {
            $mensaje_exito .= formatear_resumen_inventario($inventario_resultado['movimientos']);
        }
        
        set_mensaje($mensaje_exito, 'success');
        redirect("/reservaciones/ver/{$reservacion_id}");
        
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Error en check-in: " . $e->getMessage());
        set_mensaje('Error al procesar el check-in: ' . $e->getMessage(), 'error');
        redirect("/reservaciones/ver/{$reservacion_id}");
    }
}
*/

// ============================================================================
// INTEGRACIÓN CON HABITACIONCONTROLLER  
// ============================================================================

/**
 * EJEMPLO: Integración en HabitacionController::cambiarEstado()
 * 
 * Agregar este código cuando se cambia el estado a 'disponible' (después de limpieza)
 */

// En HabitacionController.php - método cambiarEstadoAction()
/*
public function cambiarEstadoAction() {
    // ... código existente ...
    
    $habitacion_id = $this->params['id'];
    $nuevo_estado = $_POST['estado'];
    
    try {
        $this->db->beginTransaction();
        
        // 1. Cambiar estado de habitación (código existente)
        $sql = "UPDATE habitaciones SET estado = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$nuevo_estado, $habitacion_id]);
        
        // 2. NUEVO: Si el estado cambia a 'disponible', procesar limpieza automática
        if ($nuevo_estado === 'disponible') {
            require_once APP_PATH . '/helpers/inventario_helpers.php';
            
            $inventario_resultado = procesar_inventario_automatico_limpieza($habitacion_id, $_SESSION['user_id']);
            
            if ($inventario_resultado['success']) {
                error_log("Limpieza automática exitosa - Habitación {$habitacion_id}");
                
                // Opcional: Registrar en log de habitación
                if (!empty($inventario_resultado['movimientos'])) {
                    $sql = "INSERT INTO habitacion_logs (habitacion_id, accion, descripcion, usuario_id) 
                            VALUES (?, 'limpieza_inventario', ?, ?)";
                    
                    $productos_usados = array_column($inventario_resultado['movimientos'], 'producto');
                    $descripcion = 'Productos de limpieza: ' . implode(', ', $productos_usados);
                    
                    $this->db->query($sql, [$habitacion_id, $descripcion, $_SESSION['user_id']]);
                }
            } else {
                error_log("Advertencia en limpieza automática: " . $inventario_resultado['message']);
            }
        }
        
        $this->db->commit();
        
        // 3. Mensaje de respuesta
        $mensaje = 'Estado de habitación actualizado';
        
        if ($nuevo_estado === 'disponible' && 
            isset($inventario_resultado) && 
            $inventario_resultado['success'] && 
            !empty($inventario_resultado['movimientos'])) {
            
            $mensaje .= formatear_resumen_inventario($inventario_resultado['movimientos']);
        }
        
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => true,
                'message' => $mensaje,
                'inventario' => $inventario_resultado ?? null
            ]);
        } else {
            set_mensaje($mensaje, 'success');
            redirect('/habitaciones');
        }
        
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("Error cambiando estado: " . $e->getMessage());
        
        if ($this->isAjax()) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        } else {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            redirect('/habitaciones');
        }
    }
}
*/

// ============================================================================
// FUNCIONES ADICIONALES PARA TEMPLATES
// ============================================================================

/**
 * Mostrar vista previa de productos en formulario de reservación
 * 
 * Usar en las vistas de crear/editar reservación para mostrar qué productos
 * se incluirán automáticamente con la habitación
 */
function mostrar_preview_productos_habitacion($tipo_habitacion_id) {
    if (!$tipo_habitacion_id) {
        return '';
    }
    
    require_once APP_PATH . '/helpers/inventario_helpers.php';
    
    $productos_checkin = obtener_productos_checkin($tipo_habitacion_id);
    
    if (empty($productos_checkin)) {
        return '';
    }
    
    $html = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-3">';
    $html .= '<h4 class="text-sm font-medium text-blue-800 mb-2 flex items-center">';
    $html .= '<i class="fas fa-gift mr-2"></i>Productos incluidos automáticamente';
    $html .= '</h4>';
    $html .= '<div class="text-xs text-blue-700 space-y-1">';
    
    foreach ($productos_checkin as $producto) {
        $disponible = $producto['stock_actual'] >= $producto['cantidad_checkin'];
        $clase_estado = $disponible ? 'text-blue-700' : 'text-red-600';
        
        $html .= '<div class="flex justify-between ' . $clase_estado . '">';
        $html .= '<span>' . htmlspecialchars($producto['nombre']) . '</span>';
        $html .= '<span>' . number_format($producto['cantidad_checkin'], 2) . ' ' . $producto['unidad_abrev'] . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    
    return $html;
}

// ============================================================================
// VALIDACIONES PREVIAS AL CHECK-IN
// ============================================================================

/**
 * Función para validar inventario antes de confirmar check-in
 * 
 * Llamar antes de procesar el check-in para verificar disponibilidad
 */
function validar_inventario_antes_checkin($habitacion_id) {
    require_once APP_PATH . '/helpers/inventario_helpers.php';
    
    $verificacion = verificar_inventario_disponible_checkin($habitacion_id);
    
    if (!$verificacion['disponible']) {
        return [
            'puede_proceder' => true, // El check-in puede continuar
            'advertencia' => true,
            'mensaje' => $verificacion['mensaje'],
            'productos_faltantes' => $verificacion['faltantes'] ?? []
        ];
    }
    
    return [
        'puede_proceder' => true,
        'advertencia' => false,
        'mensaje' => 'Inventario disponible para check-in automático'
    ];
}

// ============================================================================
// INTEGRACIÓN CON DASHBOARD PRINCIPAL
// ============================================================================

/**
 * Widget de inventario para incluir en DashboardController
 * 
 * Agregar esta función en DashboardController::indexAction()
 */
/*
// En DashboardController.php - método indexAction()
public function indexAction() {
    // ... código existente del dashboard ...
    
    // Agregar estadísticas de inventario
    require_once APP_PATH . '/helpers/inventario_helpers.php';
    
    $estadisticas_inventario = obtener_estadisticas_inventario();
    $alertas_inventario = obtener_alertas_inventario(5);
    
    View::renderTemplate('dashboard/index', [
        'title' => 'Dashboard',
        // ... variables existentes ...
        'inventario_stats' => $estadisticas_inventario,
        'inventario_alertas' => $alertas_inventario
    ]);
}
*/

// En la vista dashboard/index.php, agregar:
/*
<!-- Widget de Inventario -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Inventario</h3>
        <a href="<?= url('inventarios/dashboard') ?>" class="text-hotel-brown hover:text-hotel-brown-dark text-sm">Ver todo</a>
    </div>
    
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="text-center">
            <div class="text-2xl font-bold text-gray-900"><?= $inventario_stats['total_productos'] ?></div>
            <div class="text-xs text-gray-500">Productos</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold <?= $inventario_stats['stock_bajo'] > 0 ? 'text-red-600' : 'text-green-600' ?>">
                <?= $inventario_stats['stock_bajo'] ?>
            </div>
            <div class="text-xs text-gray-500">Stock Bajo</div>
        </div>
    </div>
    
    <?php if (!empty($inventario_alertas)): ?>
    <div class="border-t pt-3">
        <h4 class="text-sm font-medium text-red-600 mb-2">Alertas</h4>
        <?php foreach (array_slice($inventario_alertas, 0, 3) as $alerta): ?>
        <div class="text-xs text-gray-600 mb-1 flex justify-between">
            <span><?= htmlspecialchars($alerta['nombre']) ?></span>
            <span class="text-red-600"><?= number_format($alerta['stock_actual'], 1) ?></span>
        </div>
        <?php endforeach; ?>
        <a href="<?= url('inventarios?alertas=1') ?>" class="text-xs text-hotel-brown hover:underline">Ver todas →</a>
    </div>
    <?php endif; ?>
</div>
*/

// ============================================================================
// MIDDLEWARE PARA ALERTAS AUTOMÁTICAS
// ============================================================================

/**
 * Función para verificar alertas de inventario en cada carga de página
 * 
 * Agregar en un middleware o en el layout principal
 */
function verificar_alertas_criticas_inventario() {
    // Solo verificar para usuarios autenticados
    if (!is_authenticated()) {
        return;
    }
    
    // Verificar solo cada 5 minutos para no sobrecargar
    $ultimo_check = $_SESSION['ultimo_check_inventario'] ?? 0;
    if ((time() - $ultimo_check) < 300) { // 5 minutos
        return;
    }
    
    try {
        require_once APP_PATH . '/helpers/inventario_helpers.php';
        
        $alertas_criticas = obtener_alertas_inventario(3);
        $productos_sin_stock = array_filter($alertas_criticas, function($alerta) {
            return $alerta['stock_actual'] == 0;
        });
        
        if (!empty($productos_sin_stock)) {
            $productos_nombres = array_column($productos_sin_stock, 'nombre');
            $mensaje = 'ALERTA CRÍTICA: Sin stock de ' . implode(', ', $productos_nombres);
            
            // Guardar alerta en sesión para mostrar en próxima página
            $_SESSION['alerta_critica_inventario'] = $mensaje;
        }
        
        $_SESSION['ultimo_check_inventario'] = time();
        
    } catch (Exception $e) {
        error_log("Error verificando alertas de inventario: " . $e->getMessage());
    }
}

// En layout/header.php, agregar después del HTML del header:
/*
<?php 
verificar_alertas_criticas_inventario();

if (isset($_SESSION['alerta_critica_inventario'])): 
?>
<div class="bg-red-600 text-white px-4 py-2 text-center text-sm">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    <?= $_SESSION['alerta_critica_inventario'] ?>
    <a href="<?= url('inventarios?alertas=1') ?>" class="underline ml-2">Ver detalles</a>
</div>
<?php 
unset($_SESSION['alerta_critica_inventario']);
endif; 
?>
*/

// ============================================================================
// HOOKS PARA EVENTOS DEL SISTEMA
// ============================================================================

/**
 * Función para llamar cuando se crea una nueva habitación
 * Verificar si hay productos con descuento automático sin configurar
 */
function verificar_configuracion_inventario_nueva_habitacion($tipo_habitacion_id) {
    try {
        require_once APP_PATH . '/helpers/inventario_helpers.php';
        
        $productos_sin_config = validar_configuracion_inventario_automatico();
        
        if (!empty($productos_sin_config)) {
            $productos_nombres = array_column($productos_sin_config, 'nombre');
            $mensaje = 'Hay productos con descuento automático que requieren configuración: ' . implode(', ', $productos_nombres);
            
            set_mensaje($mensaje, 'warning');
        }
        
    } catch (Exception $e) {
        error_log("Error verificando configuración inventario: " . $e->getMessage());
    }
}

// ============================================================================
// INTEGRACIÓN CON REPORTES PRINCIPALES
// ============================================================================

/**
 * Agregar datos de inventario a reportes generales del hotel
 */
function agregar_datos_inventario_reporte_general($fecha_inicio, $fecha_fin) {
    try {
        $reportesModel = new InventarioReportes();
        
        return [
            'resumen_financiero' => $reportesModel->resumenFinanciero($fecha_inicio, $fecha_fin),
            'productos_utilizados' => $reportesModel->productosMasUtilizados(5, 30),
            'eficiencia_automatico' => $reportesModel->eficienciaInventarioAutomatico($fecha_inicio, $fecha_fin),
            'habitaciones_mayor_consumo' => $reportesModel->habitacionesMayorConsumo($fecha_inicio, $fecha_fin, 5)
        ];
        
    } catch (Exception $e) {
        error_log("Error obteniendo datos inventario para reporte: " . $e->getMessage());
        return null;
    }
}

// ============================================================================
// CONFIGURACIÓN DE TIPOS DE HABITACIÓN
// ============================================================================

/**
 * Función para configurar inventario automático al crear/editar tipos de habitación
 */
function configurar_inventario_tipo_habitacion($tipo_habitacion_id, $configuracion = []) {
    try {
        $inventarioModel = new Inventario();
        
        // Ejemplo de configuración:
        // $configuracion = [
        //     1 => ['checkin' => 2, 'limpieza' => 1], // Producto ID 1: 2 en checkin, 1 en limpieza
        //     2 => ['checkin' => 1, 'limpieza' => 0], // Producto ID 2: 1 en checkin, 0 en limpieza
        // ];
        
        foreach ($configuracion as $producto_id => $cantidades) {
            $sql = "INSERT INTO inventario_config_habitacion 
                    (tipo_habitacion_id, producto_id, cantidad_checkin, cantidad_limpieza, activo)
                    VALUES (?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE
                    cantidad_checkin = VALUES(cantidad_checkin),
                    cantidad_limpieza = VALUES(cantidad_limpieza),
                    activo = VALUES(activo)";
            
            $db = Database::getInstance();
            $db->query($sql, [
                $tipo_habitacion_id,
                $producto_id,
                floatval($cantidades['checkin'] ?? 0),
                floatval($cantidades['limpieza'] ?? 0)
            ]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error configurando inventario para tipo habitación: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// COMANDOS DE MANTENIMIENTO
// ============================================================================

/**
 * Función para ejecutar mantenimiento de inventario (ejecutar semanalmente)
 */
function mantenimiento_inventario() {
    try {
        $db = Database::getInstance();
        
        // 1. Limpiar movimientos muy antiguos (opcional)
        $sql = "DELETE FROM movimientos_inventario 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)";
        $db->query($sql);
        
        // 2. Recalcular costos promedio
        $sql = "UPDATE productos p 
                SET costo_promedio = (
                    SELECT AVG(m.costo_total / m.cantidad)
                    FROM movimientos_inventario m 
                    WHERE m.producto_id = p.id 
                    AND m.tipo_movimiento = 'ENTRADA'
                    AND m.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                )
                WHERE p.activo = 1";
        $db->query($sql);
        
        // 3. Generar alertas automáticas para productos críticos
        $inventarioModel = new Inventario();
        $productos_criticos = $inventarioModel->conStockBajo(999);
        
        $productos_urgentes = array_filter($productos_criticos, function($p) {
            return $p['stock_actual'] == 0 || $p['tipo_alerta'] === 'SIN_STOCK';
        });
        
        if (!empty($productos_urgentes)) {
            // Enviar notificación al gerente (implementar según sistema de notificaciones)
            $mensaje = 'Productos sin stock: ' . implode(', ', array_column($productos_urgentes, 'nombre'));
            error_log("ALERTA CRÍTICA INVENTARIO: " . $mensaje);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en mantenimiento de inventario: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// INSTRUCCIONES DE IMPLEMENTACIÓN
// ============================================================================

/*
PASOS PARA IMPLEMENTAR LA INTEGRACIÓN COMPLETA:

1. COPIAR ARCHIVOS:
   - Inventario.php -> /app/models/
   - InventarioReportes.php -> /app/models/
   - inventario_helpers.php -> /app/helpers/
   - Actualizar InventarioController.php

2. ACTUALIZAR CONTROLADORES EXISTENTES:
   
   En ReservacionController::checkInAction():
   - Agregar el código de ejemplo mostrado arriba
   - Incluir inventario_helpers.php
   - Llamar a procesar_inventario_automatico_checkin()
   
   En HabitacionController::cambiarEstadoAction():
   - Agregar el código de ejemplo mostrado arriba
   - Llamar a procesar_inventario_automatico_limpieza()

3. ACTUALIZAR VISTAS:
   
   En reservaciones/crear.php y reservaciones/editar.php:
   - Agregar vista previa de productos usando mostrar_preview_productos_habitacion()
   
   En dashboard/index.php:
   - Agregar widget de inventario usando widget_inventario_dashboard()

4. AGREGAR VERIFICACIONES:
   - En layout/header.php: verificar_alertas_criticas_inventario()
   - En tipos de habitación: usar configurar_inventario_tipo_habitacion()

5. CONFIGURAR MANTENIMIENTO:
   - Crear tarea programada para ejecutar mantenimiento_inventario() semanalmente
   - Agregar en crontab o sistema de tareas del servidor

6. PROBAR INTEGRACIÓN:
   - Crear productos con descuento automático
   - Configurar cantidades por tipo de habitación
   - Probar check-in y verificar descuentos automáticos
   - Cambiar habitación a 'disponible' y verificar limpieza automática

¡Con esto el sistema de inventario quedará completamente integrado!
*/