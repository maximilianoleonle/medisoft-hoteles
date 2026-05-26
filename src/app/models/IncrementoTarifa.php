<?php
/**
 * Modelo de Incrementos de Tarifas
 * Los Cedros
 */

class IncrementoTarifa extends Model {
    protected $table = 'incrementos_tarifas';
    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_incremento',
        'valor_incremento',
        'alcance',
        'tipos_habitacion',
        'habitaciones',
        'es_permanente',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'prioridad',
        'usuario_id'
    ];
    
    /**
     * Obtener incrementos activos para una fecha específica
     */
    public function getActivosParaFecha($fecha) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE activo = 1 
                AND fecha_inicio <= ? 
                AND (fecha_fin >= ? OR fecha_fin IS NULL OR es_permanente = 1)
                ORDER BY prioridad DESC, created_at DESC";
        
        $stmt = $this->db->query($sql, [$fecha, $fecha]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener incrementos vigentes (activos hoy)
     */
    public function getVigentes() {
        return $this->getActivosParaFecha(date('Y-m-d'));
    }
    
    /**
     * Calcular precio con incrementos para una habitación en una fecha
     */
    public function calcularPrecioConIncremento($habitacion_id, $tipo_habitacion, $precio_base, $fecha) {
        $incrementos = $this->getIncrementosAplicables($habitacion_id, $tipo_habitacion, $fecha);
        
        $precio_final = $precio_base;
        $incrementos_aplicados = [];
        
        foreach ($incrementos as $incremento) {
            if ($incremento['tipo_incremento'] == 'porcentaje') {
                $aumento = $precio_base * ($incremento['valor_incremento'] / 100);
            } else {
                $aumento = $incremento['valor_incremento'];
            }
            
            $precio_final += $aumento;
            $incrementos_aplicados[] = [
                'id' => $incremento['id'],
                'nombre' => $incremento['nombre'],
                'tipo' => $incremento['tipo_incremento'],
                'valor' => $incremento['valor_incremento'],
                'aumento' => $aumento
            ];
        }
        
        return [
            'precio_base' => $precio_base,
            'precio_final' => $precio_final,
            'incremento_total' => $precio_final - $precio_base,
            'incrementos_aplicados' => $incrementos_aplicados
        ];
    }
    
    /**
     * Obtener incrementos aplicables para una habitación
     */
    private function getIncrementosAplicables($habitacion_id, $tipo_habitacion, $fecha) {
        $incrementos = $this->getActivosParaFecha($fecha);
        $aplicables = [];
        
        foreach ($incrementos as $inc) {
            switch ($inc['alcance']) {
                case 'global':
                    $aplicables[] = $inc;
                    break;
                    
                case 'tipo_habitacion':
                    $tipos = json_decode($inc['tipos_habitacion'], true) ?: [];
                    if (in_array($tipo_habitacion, $tipos)) {
                        $aplicables[] = $inc;
                    }
                    break;
                    
                case 'habitacion':
                    $habitaciones = json_decode($inc['habitaciones'], true) ?: [];
                    if (in_array($habitacion_id, $habitaciones)) {
                        $aplicables[] = $inc;
                    }
                    break;
            }
        }
        
        return $aplicables;
    }
    
    /**
     * Verificar solapamiento de fechas
     */
    public function verificarSolapamiento($fecha_inicio, $fecha_fin, $alcance, $elementos = [], $excluir_id = null) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE activo = 1 
                AND alcance = ?
                AND (
                    (fecha_inicio <= ? AND (fecha_fin >= ? OR fecha_fin IS NULL OR es_permanente = 1))
                    OR (fecha_inicio >= ? AND fecha_inicio <= ?)
                )";
        
        $fecha_fin_comparacion = $fecha_fin ?: '9999-12-31';
        $params = [$alcance, $fecha_fin_comparacion, $fecha_inicio, $fecha_inicio, $fecha_fin_comparacion];
        
        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }
        
        $stmt = $this->db->query($sql, $params);
        $solapamientos = $stmt->fetchAll();
        
        // Verificar si hay conflicto real según el alcance
        foreach ($solapamientos as $solap) {
            if ($alcance == 'global') {
                return true;
            } elseif ($alcance == 'tipo_habitacion') {
                $tipos_existentes = json_decode($solap['tipos_habitacion'], true) ?: [];
                if (array_intersect($elementos, $tipos_existentes)) {
                    return true;
                }
            } elseif ($alcance == 'habitacion') {
                $habs_existentes = json_decode($solap['habitaciones'], true) ?: [];
                if (array_intersect($elementos, $habs_existentes)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Obtener todos con información adicional
     */
    public function getAllConInfo() {
        $sql = "SELECT it.*, u.nombre_completo as usuario_nombre
                FROM {$this->table} it
                LEFT JOIN usuarios u ON it.usuario_id = u.id
                ORDER BY it.prioridad DESC, it.created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estadísticas de incrementos
     */
    public function getEstadisticas() {
        $hoy = date('Y-m-d');
        
        // Total activos
        $sql_activos = "SELECT COUNT(*) as total FROM {$this->table} WHERE activo = 1";
        $stmt = $this->db->query($sql_activos);
        $activos = $stmt->fetch()['total'];
        
        // Vigentes hoy
        $vigentes = count($this->getVigentes());
        
        // Próximos (futuros)
        $sql_futuros = "SELECT COUNT(*) as total FROM {$this->table} 
                        WHERE activo = 1 AND fecha_inicio > ?";
        $stmt = $this->db->query($sql_futuros, [$hoy]);
        $futuros = $stmt->fetch()['total'];
        
        return [
            'activos' => $activos,
            'vigentes' => $vigentes,
            'futuros' => $futuros
        ];
    }
}