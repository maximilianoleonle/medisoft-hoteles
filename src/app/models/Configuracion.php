<?php
/**
 * Modelo de Configuración
 * Los Cedros
 */

class Configuracion extends Model {
    protected $table = 'configuracion';
    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion'
    ];
    protected $timestamps = false;
    
    /**
     * Obtener valor de configuración
     */
    public function get($clave, $default = null) {
        $config = $this->first(['clave' => $clave]);
        
        if (!$config) {
            return $default;
        }
        
        // Convertir según tipo
        switch ($config['tipo']) {
            case 'boolean':
                return filter_var($config['valor'], FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return intval($config['valor']);
            case 'float':
                return floatval($config['valor']);
            case 'json':
                return json_decode($config['valor'], true);
            default:
                return $config['valor'];
        }
    }
    
    /**
     * Establecer valor de configuración
     */
    public function set($clave, $valor, $tipo = 'string', $descripcion = null) {
        // Convertir valor según tipo
        if ($tipo == 'boolean') {
            $valor = $valor ? '1' : '0';
        } elseif ($tipo == 'json') {
            $valor = json_encode($valor);
        } else {
            $valor = strval($valor);
        }
        
        $config = $this->first(['clave' => $clave]);
        
        if ($config) {
            // Actualizar existente
            return $this->update($config['id'], [
                'valor' => $valor,
                'tipo' => $tipo,
                'descripcion' => $descripcion ?: $config['descripcion']
            ]);
        } else {
            // Crear nuevo
            return $this->create([
                'clave' => $clave,
                'valor' => $valor,
                'tipo' => $tipo,
                'descripcion' => $descripcion
            ]);
        }
    }
    
    /**
     * Obtener toda la configuración
     */
    public function getAll() {
        $configs = $this->all();
        $result = [];
        
        foreach ($configs as $config) {
            $result[$config['clave']] = $this->get($config['clave']);
        }
        
        return $result;
    }
    
    /**
     * Obtener configuración por grupo
     */
    public function getByGroup($grupo) {
        $sql = "SELECT * FROM {$this->table} WHERE clave LIKE ?";
        $stmt = $this->db->query($sql, [$grupo . '.%']);
        $configs = $stmt->fetchAll();
        
        $result = [];
        foreach ($configs as $config) {
            $key = str_replace($grupo . '.', '', $config['clave']);
            $result[$key] = $this->get($config['clave']);
        }
        
        return $result;
    }
    
    /**
     * Inicializar configuración por defecto
     */
    public function inicializarDefaults() {
        $defaults = [
            // Datos del hotel
            ['hotel.nombre', 'Los Cedros', 'string', 'Nombre del hotel'],
            ['hotel.direccion', 'Santa Catarina Juquila, Oaxaca', 'string', 'Dirección del hotel'],
            ['hotel.telefono', '', 'string', 'Teléfono principal'],
            ['hotel.email', '', 'string', 'Email de contacto'],
            ['hotel.habitaciones', '66', 'integer', 'Número total de habitaciones'],
            ['hotel.check_in_time', '15:00', 'string', 'Hora de check-in'],
            ['hotel.check_out_time', '12:00', 'string', 'Hora de check-out'],
            ['hotel.horas_estancia', '24', 'integer', 'Horas de estancia estándar'],
            
            // Tarifas
            ['tarifas.incremento_fin_semana', '0', 'float', 'Porcentaje de incremento fin de semana'],
            ['tarifas.descuento_grupo_minimo', '10', 'integer', 'Habitaciones mínimas para descuento grupal'],
            ['tarifas.descuento_grupo_gratis', '1', 'integer', 'Habitaciones gratis por grupo'],
            
            // Inventario
            ['inventario.auto_papel_higienico', '1', 'integer', 'Cantidad automática de papel higiénico'],
            ['inventario.auto_jabon', '1', 'integer', 'Cantidad automática de jabón'],
            
            // Sistema
            ['sistema.session_lifetime', '120', 'integer', 'Duración de sesión en minutos'],
            ['sistema.backup_enabled', '1', 'boolean', 'Habilitar respaldos automáticos'],
            ['sistema.backup_frequency', 'weekly', 'string', 'Frecuencia de respaldos'],
            ['sistema.backup_keep_last', '4', 'integer', 'Número de respaldos a mantener'],
            
            // Uploads
            ['upload.max_size', '5242880', 'integer', 'Tamaño máximo de archivos (bytes)'],
            ['upload.allowed_images', 'jpg,jpeg,png,gif', 'string', 'Extensiones de imagen permitidas'],
        ];
        
        foreach ($defaults as $default) {
            if (!$this->exists(['clave' => $default[0]])) {
                $this->set($default[0], $default[1], $default[2], $default[3]);
            }
        }
    }
}