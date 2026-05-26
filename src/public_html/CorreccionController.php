<?php
/**
 * Controlador para Corrección de Precios
 * Los Cedros
 */

class CorreccionController extends Controller {
    
    /**
     * Vista principal del corrector
     */
    public function index() {
        // Solo permitir al gerente
        if (!tiene_permiso('gerente')) {
            set_mensaje('No tienes permisos para acceder a esta sección', 'error');
            redirect('dashboard');
            exit;
        }
        
        $db = Database::getInstance();
        
        // Obtener reservaciones activas
        $sql = "SELECT r.id, r.precio_total, r.fecha_entrada, r.fecha_salida,
                       h.nombre_completo as huesped,
                       DATEDIFF(r.fecha_salida, r.fecha_entrada) as noches
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                WHERE r.estado IN ('confirmada', 'checked_in')
                ORDER BY r.id DESC";
        
        $stmt = $db->query($sql);
        $reservaciones = $stmt->fetchAll();
        
        $incorrectas = [];
        $correctas = 0;
        $total_diferencia = 0;
        
        // Analizar cada reservación
        foreach ($reservaciones as $reservacion) {
            $sql_habs = "SELECT rh.habitacion_id, rh.es_cortesia, 
                                hab.numero, hab.precio_base
                         FROM reservacion_habitaciones rh
                         INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                         WHERE rh.reservacion_id = ?";
            
            $stmt_habs = $db->query($sql_habs, [$reservacion['id']]);
            $habitaciones = $stmt_habs->fetchAll();
            
            $precio_correcto = 0;
            foreach ($habitaciones as $hab) {
                if ($hab['es_cortesia'] != 1) {
                    $precio_correcto += $hab['precio_base'] * $reservacion['noches'];
                }
            }
            
            $diferencia = $reservacion['precio_total'] - $precio_correcto;
            
            if (abs($diferencia) > 0.01) {
                $incorrectas[] = [
                    'reservacion' => $reservacion,
                    'habitaciones' => $habitaciones,
                    'precio_correcto' => $precio_correcto,
                    'diferencia' => $diferencia
                ];
                $total_diferencia += abs($diferencia);
            } else {
                $correctas++;
            }
        }
        
        // Pasar datos a la vista
        $this->view('correccion/index', [
            'total_reservaciones' => count($reservaciones),
            'correctas' => $correctas,
            'incorrectas' => $incorrectas,
            'total_diferencia' => $total_diferencia
        ]);
    }
    
    /**
     * Aplicar correcciones
     */
    public function aplicar() {
        // Solo permitir al gerente
        if (!tiene_permiso('gerente')) {
            set_mensaje('No tienes permisos para acceder a esta sección', 'error');
            redirect('dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('correccion');
            exit;
        }
        
        $db = Database::getInstance();
        
        // Obtener reservaciones incorrectas de nuevo
        $sql = "SELECT r.id, r.precio_total, r.fecha_entrada, r.fecha_salida,
                       DATEDIFF(r.fecha_salida, r.fecha_entrada) as noches
                FROM reservaciones r
                WHERE r.estado IN ('confirmada', 'checked_in')";
        
        $stmt = $db->query($sql);
        $reservaciones = $stmt->fetchAll();
        
        $corregidas = 0;
        
        foreach ($reservaciones as $reservacion) {
            // Calcular precio correcto
            $sql_habs = "SELECT hab.precio_base, rh.es_cortesia
                         FROM reservacion_habitaciones rh
                         INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                         WHERE rh.reservacion_id = ?";
            
            $stmt_habs = $db->query($sql_habs, [$reservacion['id']]);
            $habitaciones = $stmt_habs->fetchAll();
            
            $precio_correcto = 0;
            foreach ($habitaciones as $hab) {
                if ($hab['es_cortesia'] != 1) {
                    $precio_correcto += $hab['precio_base'] * $reservacion['noches'];
                }
            }
            
            $diferencia = $reservacion['precio_total'] - $precio_correcto;
            
            // Si hay diferencia, actualizar
            if (abs($diferencia) > 0.01) {
                $sql_update = "UPDATE reservaciones SET precio_total = ? WHERE id = ?";
                $db->query($sql_update, [$precio_correcto, $reservacion['id']]);
                $corregidas++;
            }
        }
        
        set_mensaje("Se corrigieron $corregidas reservaciones exitosamente", 'success');
        redirect('correccion');
    }
}