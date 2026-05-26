<?php
/**
 * Helper de Inventario para Check-in
 * Los Cedros
 */

function descontar_inventario_checkin($habitacion_ids, $reservacion_id = null) {
    $inventario = new Inventario();
    $resultados = [];
    
    foreach ($habitacion_ids as $habitacion_id) {
        $resultado = $inventario->descontarCheckIn($habitacion_id, $reservacion_id);
        if ($resultado['success'] && !empty($resultado['descuentos'])) {
            $resultados = array_merge($resultados, $resultado['descuentos']);
        }
    }
    
    return $resultados;
}