<?php
/**
 * Modelo de Habitación
 * 66 Habitaciones Reales con nuevos tipos
 * Sistema hotelero
 */

require_once __DIR__ . '/../helpers/hotel_config.php';

class Habitacion extends Model {
    protected $table = 'habitaciones';
    protected $fillable = [
        'hotel_id',
        'numero',
        'tipo',
        'capacidad_personas',
        'camas_individuales',
        'camas_matrimoniales',
        'piso',
        'precio_base',
        'estado',
        'caracteristicas',
        'activa',
        'foto_url'
    ];

    protected function hotelIdActual()
    {
        return obtenerHotelIdActualCompat();
    }

    public function find($id, $columns = ['*']) {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [$id, $this->hotelIdActual()]);
        return $stmt ? $stmt->fetch() : false;
    }

    public function where($conditions, $columns = ['*']) {
        $conditions['hotel_id'] = $this->hotelIdActual();
        return parent::where($conditions, $columns);
    }

    public function first($conditions, $columns = ['*']) {
        $results = $this->where($conditions, $columns);
        return !empty($results) ? $results[0] : null;
    }

    public function count($conditions = []) {
        $conditions['hotel_id'] = $this->hotelIdActual();
        return parent::count($conditions);
    }

    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }

    public function create($data) {
        $data['hotel_id'] = $data['hotel_id'] ?? $this->hotelIdActual();
        $id = parent::create($data);
        return $id ? $this->find($id) : false;
    }

    public function update($id, $data) {
        $data = $this->filterFillable($data);
        unset($data['hotel_id']);

        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        if (empty($data)) {
            return false;
        }

        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $values[] = $this->hotelIdActual();

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) .
               " WHERE {$this->primaryKey} = ? AND hotel_id = ?";

        $stmt = $this->db->query($sql, $values);

        if ($stmt) {
            return $this->find($id);
        }

        return false;
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [$id, $this->hotelIdActual()]);
        return $stmt !== false;
    }
    
    /**
     * Estados posibles de la habitación
     */
    public static function getEstados() {
        return [
            'disponible' => ['label' => 'Disponible', 'color' => 'green', 'icon' => 'check-circle'],
            'ocupada' => ['label' => 'Ocupada', 'color' => 'red', 'icon' => 'user'],
            'limpieza' => ['label' => 'En limpieza', 'color' => 'yellow', 'icon' => 'broom'],
            'mantenimiento' => ['label' => 'En mantenimiento', 'color' => 'gray', 'icon' => 'tools']
        ];
    }
    
    /**
     * Tipos de habitación disponibles - ACTUALIZADO SEGÚN PDF
     */
    public static function getTipos() {
        return [
            'sencilla' => 'Sencilla',
            'doble' => 'Doble',
            'triple' => 'Triple',
            'cuadruple' => 'Cuádruple',
            'doble_jacuzzi' => 'Doble con Jacuzzi',
            'sencilla_jacuzzi' => 'Sencilla con Jacuzzi'
        ];
    }
    
    /**
     * Obtener información de pisos del hotel - ACTUALIZADO
     */
    public static function getPisos() {
        return [
            -4 => '4 niveles abajo',
            -2 => '2 niveles abajo',
            -1 => 'Un nivel abajo',
            1 => 'Nivel de piso',
            2 => '2º Nivel',
            3 => '3º Nivel'
        ];
    }
    
    /**
     * Obtener rango de precios por tipo - ACTUALIZADO
     */
    public static function getRangoPrecios() {
        return [
            'sencilla' => ['min' => 550, 'max' => 550],
            'doble' => ['min' => 700, 'max' => 1000],
            'triple' => ['min' => 1100, 'max' => 1200],
            'cuadruple' => ['min' => 1400, 'max' => 1400],
            'doble_jacuzzi' => ['min' => 1600, 'max' => 1600],
            'sencilla_jacuzzi' => ['min' => 1000, 'max' => 1000]
        ];
    }
    
    /**
     * Obtener habitaciones por estado
     */
    public function porEstado($estado) {
        return $this->where(['estado' => $estado, 'activa' => 1]);
    }
    
    /**
 * Verificar si la habitación tiene reservación pendiente para hoy
 */
public function tieneReservacionPendienteHoy($habitacion_id) {
    if (!$this->find($habitacion_id)) {
        return false;
    }

    $hotelId = $this->hotelIdActual();
    $sql = "SELECT COUNT(*) as total 
            FROM reservaciones r
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            WHERE rh.habitacion_id = ?
            AND hab_scope.hotel_id = ?
            AND r.fecha_entrada = CURDATE()
            AND r.estado = 'confirmada'";
    
    $stmt = $this->db->query($sql, [$habitacion_id, $hotelId]);
    $result = $stmt->fetch();
    return $result['total'] > 0;
}
    
    /**
     * Obtener habitaciones por piso - ACTUALIZADO para manejar sótanos
     */
    public function porPiso($piso) {
        return $this->where(['piso' => $piso, 'activa' => 1]);
    }
    
    /**
 * Obtener habitaciones disponibles entre fechas específicas
 * AGREGAR este método al modelo Habitacion.php si no existe
 */
public function disponiblesEntreFechas($fecha_entrada, $fecha_salida, $excluir_reservacion_id = null) {
    $db = Database::getInstance();
    $hotelId = $this->hotelIdActual();
    
    // Primero obtener todas las habitaciones activas
    $sql = "SELECT h.* FROM habitaciones h 
            WHERE h.hotel_id = ?
            AND h.activa = 1
            AND h.estado != 'mantenimiento'
            AND h.id NOT IN (
                SELECT m2.habitacion_id 
                FROM mantenimientos_habitaciones m2
                WHERE m2.hotel_id = ?
                AND m2.estado = 'programado'
                AND m2.programado = 1 
                AND m2.fecha_programada IS NOT NULL
                AND m2.fecha_programada < ?
                AND (m2.fecha_programada_fin IS NULL OR m2.fecha_programada_fin > ?)
            )";
    
    $stmt = $db->query($sql, [$hotelId, $hotelId, $fecha_salida, $fecha_entrada]);
    $todas_habitaciones = $stmt->fetchAll();
    
    // Luego obtener las habitaciones que tienen reservaciones en esas fechas
    // LÓGICA SIMPLIFICADA: Hay solapamiento si:
    // fecha_salida_nueva > fecha_entrada_existente Y fecha_entrada_nueva < fecha_salida_existente
    $sql_ocupadas = "SELECT DISTINCT rh.habitacion_id 
                     FROM reservacion_habitaciones rh
                     INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                     INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
                     WHERE hab_scope.hotel_id = ?
                     AND r.estado IN ('confirmada', 'checked_in')
                     AND ? > r.fecha_entrada
                     AND ? < r.fecha_salida";
    
    $params = [$hotelId, $fecha_salida, $fecha_entrada];
    
    if ($excluir_reservacion_id) {
        $sql_ocupadas .= " AND r.id != ?";
        $params[] = $excluir_reservacion_id;
    }
    
    $stmt = $db->query($sql_ocupadas, $params);
    $ocupadas = $stmt->fetchAll();
    $ocupadas_ids = array_column($ocupadas, 'habitacion_id');
    
    // Bloqueos de canales externos (bloque canales_ical): [] si no esta contratado.
    if (!class_exists('IcalCanalesService')) {
        require_once __DIR__ . '/../services/IcalCanalesService.php';
    }
    $bloqueadas_ical = IcalCanalesService::habitacionesBloqueadas((int) $hotelId, $fecha_entrada, $fecha_salida);

    // Filtrar las habitaciones disponibles
    $disponibles = array_filter($todas_habitaciones, function($hab) use ($ocupadas_ids, $bloqueadas_ical) {
        return !in_array($hab['id'], $ocupadas_ids) && !in_array((int) $hab['id'], $bloqueadas_ical, true);
    });

    return array_values($disponibles);
}
    
    /**
     * Obtener habitaciones por tipo
     */
    public function porTipo($tipo) {
        return $this->where(['tipo' => $tipo, 'activa' => 1]);
    }
    
    /**
     * Obtener habitaciones disponibles
     */
    public function disponibles() {
        return $this->porEstado('disponible');
    }
    
    /**
     * Obtener habitaciones disponibles en un rango de fechas - ACTUALIZADO
     */
    public function disponiblesEnFechas($fecha_entrada, $fecha_salida, $tipo = null) {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT h.* FROM {$this->table} h
                WHERE h.hotel_id = ?
                AND h.activa = 1
                AND h.estado = 'disponible'
                AND h.id NOT IN (
                    SELECT rh.habitacion_id 
                    FROM reservacion_habitaciones rh
                    INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                    INNER JOIN habitaciones h_res ON rh.habitacion_id = h_res.id
                    WHERE h_res.hotel_id = ?
                    AND r.estado IN ('confirmada', 'checked_in')
                    AND ((r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                    OR (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                    OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?))
                )
                AND h.id NOT IN (
                    SELECT m.habitacion_id 
                    FROM mantenimientos_habitaciones m
                    WHERE m.hotel_id = ?
                    AND m.estado IN ('programado', 'en_proceso')
                    AND (
                        (m.programado = 1 AND m.fecha_programada IS NOT NULL AND (
                            (m.fecha_programada < ? AND (m.fecha_programada_fin IS NULL OR m.fecha_programada_fin > ?))
                            OR (m.fecha_programada >= ? AND m.fecha_programada < ?)
                            OR (m.fecha_programada_fin IS NOT NULL AND m.fecha_programada_fin > ? AND m.fecha_programada < ?)
                        ))
                        OR (m.estado = 'en_proceso' AND m.programado = 0)
                    )
                )";
        
        $params = [
            $hotelId,
            $hotelId,
            $fecha_entrada, $fecha_entrada,
            $fecha_salida, $fecha_salida,
            $fecha_entrada, $fecha_salida,
            $hotelId,
            $fecha_salida, $fecha_entrada,
            $fecha_entrada, $fecha_salida,
            $fecha_entrada, $fecha_salida
        ];
        
        if ($tipo) {
            $sql .= " AND h.tipo = ?";
            $params[] = $tipo;
        }
        
        $sql .= " ORDER BY h.piso, CAST(h.numero AS UNSIGNED)";

        $stmt = $this->db->query($sql, $params);
        $resultado = $stmt->fetchAll();

        // Bloqueos de canales externos (bloque canales_ical): [] si no esta contratado.
        if (!class_exists('IcalCanalesService')) {
            require_once __DIR__ . '/../services/IcalCanalesService.php';
        }
        $bloqueadas_ical = IcalCanalesService::habitacionesBloqueadas((int) $hotelId, $fecha_entrada, $fecha_salida);

        if (!empty($bloqueadas_ical)) {
            $resultado = array_values(array_filter($resultado, function ($hab) use ($bloqueadas_ical) {
                return !in_array((int) $hab['id'], $bloqueadas_ical, true);
            }));
        }

        return $resultado;
    }
    
    /**
     * Cambiar estado de habitación
     */
    public function cambiarEstado($id, $nuevoEstado) {
        $estadosValidos = array_keys(self::getEstados());
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }
        
        return $this->update($id, ['estado' => $nuevoEstado]);
    }
    
    /**
     * Obtener estadísticas de habitaciones - ACTUALIZADO
     */
    public function estadisticas() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                    SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                    SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) as mantenimiento,
                    SUM(CASE WHEN estado = 'limpieza' THEN 1 ELSE 0 END) as limpieza,
                    SUM(CASE WHEN tipo = 'sencilla' THEN 1 ELSE 0 END) as sencillas,
                    SUM(CASE WHEN tipo = 'doble' THEN 1 ELSE 0 END) as dobles,
                    SUM(CASE WHEN tipo = 'triple' THEN 1 ELSE 0 END) as triples,
                    SUM(CASE WHEN tipo = 'cuadruple' THEN 1 ELSE 0 END) as cuadruples,
                    SUM(CASE WHEN tipo = 'doble_jacuzzi' THEN 1 ELSE 0 END) as dobles_jacuzzi,
                    SUM(CASE WHEN tipo = 'sencilla_jacuzzi' THEN 1 ELSE 0 END) as sencillas_jacuzzi,
                    SUM(CASE WHEN caracteristicas LIKE '%jacuzzi%' THEN 1 ELSE 0 END) as con_jacuzzi,
                    SUM(CASE WHEN caracteristicas LIKE '%pantalla%' THEN 1 ELSE 0 END) as con_pantalla,
                    SUM(CASE WHEN caracteristicas LIKE '%balcón%' THEN 1 ELSE 0 END) as con_balcon,
                    MIN(precio_base) as precio_minimo,
                    MAX(precio_base) as precio_maximo,
                    AVG(precio_base) as precio_promedio
                FROM {$this->table}
                WHERE hotel_id = ? AND activa = 1";
        
        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener estadísticas por piso
     */
    public function estadisticasPorPiso() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT 
                    piso,
                    CASE 
                        WHEN piso = -4 THEN '4 niveles abajo'
                        WHEN piso = -2 THEN '2 niveles abajo'
                        WHEN piso = -1 THEN 'Un nivel abajo'
                        WHEN piso = 1 THEN 'Nivel de piso'
                        WHEN piso = 2 THEN '2º Nivel'
                        WHEN piso = 3 THEN '3º Nivel'
                        ELSE CONCAT('Piso ', piso)
                    END as nombre_piso,
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                    SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                    AVG(precio_base) as precio_promedio
                FROM {$this->table}
                WHERE hotel_id = ? AND activa = 1
                GROUP BY piso
                ORDER BY piso";
        
        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estadísticas por tipo
     */
    public function estadisticasPorTipo() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT 
                    tipo,
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                    SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                    MIN(precio_base) as precio_minimo,
                    MAX(precio_base) as precio_maximo,
                    AVG(precio_base) as precio_promedio
                FROM {$this->table}
                WHERE hotel_id = ? AND activa = 1
                GROUP BY tipo
                ORDER BY precio_promedio";
        
        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt->fetchAll();
    }
    
 
/**
 * Obtener ocupación actual de la habitación - VERSIÓN FINAL
 */
public function getOcupacionActual($habitacion_id) {
    if (!$this->find($habitacion_id)) {
        return null;
    }

    $hotelId = $this->hotelIdActual();
    // Buscar el huésped que debería estar ocupando la habitación HOY
    // Prioridad: 1) checked_in, 2) confirmada con fecha de hoy
    $sql = "SELECT r.*, h.nombre_completo, h.telefono, h.id as huesped_id,
            COUNT(DISTINCT rh2.habitacion_id) as total_habitaciones
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            LEFT JOIN reservacion_habitaciones rh2 ON r.id = rh2.reservacion_id
            WHERE rh.habitacion_id = ?
            AND hab_scope.hotel_id = ?
            AND r.estado IN ('checked_in', 'confirmada')
            AND r.fecha_entrada <= CURDATE()
            AND r.fecha_salida >= CURDATE()
            GROUP BY r.id
            ORDER BY 
                CASE WHEN r.estado = 'checked_in' THEN 0 ELSE 1 END,
                r.fecha_entrada ASC
            LIMIT 1";
    
    $stmt = $this->db->query($sql, [$habitacion_id, $hotelId]);
    return $stmt->fetch() ?: null;
}
    
    /**
     * Verificar si habitación está disponible para reservar - ACTUALIZADO
     */
    public function estaDisponible($habitacion_id, $fecha_entrada, $fecha_salida, $excluir_reservacion_id = null) {
        if (!$this->find($habitacion_id)) {
            return false;
        }

        $hotelId = $this->hotelIdActual();
        $sql = "SELECT COUNT(*) as conflictos 
                FROM reservacion_habitaciones rh
                INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
                WHERE rh.habitacion_id = ? 
                AND hab_scope.hotel_id = ?
                AND r.estado IN ('confirmada', 'checked_in')
                AND ((r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                OR (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?))";
        
        $params = [
            $habitacion_id,
            $hotelId,
            $fecha_entrada, $fecha_entrada,
            $fecha_salida, $fecha_salida,
            $fecha_entrada, $fecha_salida
        ];
        
        if ($excluir_reservacion_id) {
            $sql .= " AND r.id != ?";
            $params[] = $excluir_reservacion_id;
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return $result['conflictos'] == 0;
    }
    
    /**
     * Buscar habitaciones - MEJORADO
     */
    public function buscar($termino) {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT * FROM {$this->table} 
                WHERE hotel_id = ?
                AND activa = 1
                AND (numero LIKE ? OR caracteristicas LIKE ? OR tipo LIKE ?)
                ORDER BY piso, CAST(numero AS UNSIGNED)";
        
        $termino = '%' . $termino . '%';
        $stmt = $this->db->query($sql, [$hotelId, $termino, $termino, $termino]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener próxima salida de la habitación - ACTUALIZADO
     */
    public function getProximaSalida($habitacion_id) {
        if (!$this->find($habitacion_id)) {
            return null;
        }

        $hotelId = $this->hotelIdActual();
        $sql = "SELECT r.*, h.nombre_completo,
                COUNT(DISTINCT rh2.habitacion_id) as total_habitaciones
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
                LEFT JOIN reservacion_habitaciones rh2 ON r.id = rh2.reservacion_id
                WHERE rh.habitacion_id = ?
                AND hab_scope.hotel_id = ?
                AND r.estado = 'checked_in'
                AND r.fecha_salida >= CURDATE()
                GROUP BY r.id
                ORDER BY r.fecha_salida ASC
                LIMIT 1";
        
        $stmt = $this->db->query($sql, [$habitacion_id, $hotelId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Obtener habitaciones con características especiales
     */
    public function conCaracteristicasEspeciales() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT 
                    'Con Jacuzzi' as categoria,
                    COUNT(*) as total,
                    GROUP_CONCAT(numero ORDER BY CAST(numero AS UNSIGNED) SEPARATOR ', ') as habitaciones
                FROM {$this->table}
                WHERE hotel_id = ? AND activa = 1 AND (caracteristicas LIKE '%jacuzzi%' OR tipo LIKE '%jacuzzi%')
                
                UNION ALL
                
                SELECT 
                    'Con Pantalla' as categoria,
                    COUNT(*) as total,
                    GROUP_CONCAT(numero ORDER BY CAST(numero AS UNSIGNED) SEPARATOR ', ') as habitaciones
                FROM {$this->table}
                WHERE hotel_id = ? AND activa = 1 AND caracteristicas LIKE '%pantalla%'
                
                UNION ALL
                
                SELECT 
                    'Con Balcón' as categoria,
                    COUNT(*) as total,
                    GROUP_CONCAT(numero ORDER BY CAST(numero AS UNSIGNED) SEPARATOR ', ') as habitaciones
                FROM {$this->table}
                WHERE hotel_id = ? AND activa = 1 AND caracteristicas LIKE '%balcón%'
                
                UNION ALL
                
                SELECT 
                    'Más Amplias' as categoria,
                    COUNT(*) as total,
                    GROUP_CONCAT(numero ORDER BY CAST(numero AS UNSIGNED) SEPARATOR ', ') as habitaciones
                FROM {$this->table}
                WHERE hotel_id = ? AND activa = 1 AND caracteristicas LIKE '%más amplia%'";
        
        $stmt = $this->db->query($sql, [$hotelId, $hotelId, $hotelId, $hotelId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener habitaciones más rentables
     */
    public function masRentables($limite = 10) {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT h.*, 
                COUNT(DISTINCT r.id) as total_reservaciones,
                SUM(r.precio_total) as ingresos_totales,
                AVG(r.precio_total) as ingreso_promedio
                FROM {$this->table} h
                LEFT JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                LEFT JOIN reservaciones r ON rh.reservacion_id = r.id 
                    AND r.estado IN ('checked_in', 'checked_out')
                WHERE h.hotel_id = ? AND h.activa = 1
                GROUP BY h.id
                ORDER BY ingresos_totales DESC, total_reservaciones DESC
                LIMIT ?";
        
        $stmt = $this->db->query($sql, [$hotelId, $limite]);
        return $stmt->fetchAll();
    }
    
    /**
     * Contar habitaciones por tipo
     */
    public function contarPorTipo() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT tipo, COUNT(*) as total 
                FROM {$this->table} 
                WHERE hotel_id = ? AND activa = 1
                GROUP BY tipo
                ORDER BY total DESC";
        
        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener habitaciones ordenadas por número
     */
    public function obtenerOrdenadas($campo = 'numero', $direccion = 'ASC') {
        $camposValidos = ['numero', 'tipo', 'piso', 'precio_base', 'estado'];
        if (!in_array($campo, $camposValidos)) {
            $campo = 'numero';
        }
        
        $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';
        
        $hotelId = $this->hotelIdActual();

        if ($campo === 'numero') {
            // Ordenar numéricamente los números de habitación
            $sql = "SELECT * FROM {$this->table} WHERE hotel_id = ? AND activa = 1 ORDER BY CAST(numero AS UNSIGNED) {$direccion}, piso";
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE hotel_id = ? AND activa = 1 ORDER BY {$campo} {$direccion}, CAST(numero AS UNSIGNED)";
        }
        
        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Validar datos de habitación - ACTUALIZADO
     */
    public function validar($data) {
        $errores = [];
        
        // Validar número
        if (empty($data['numero'])) {
            $errores[] = 'El número de habitación es obligatorio';
        } elseif (strlen($data['numero']) > 10) {
            $errores[] = 'El número de habitación no puede exceder 10 caracteres';
        }
        
        // Validar tipo
        $tiposValidos = array_keys(self::getTipos());
        if (empty($data['tipo']) || !in_array($data['tipo'], $tiposValidos)) {
            $errores[] = 'Debe seleccionar un tipo de habitación válido';
        }
        
        // Validar precio
        if (!is_numeric($data['precio_base']) || $data['precio_base'] <= 0) {
            $errores[] = 'El precio debe ser un número mayor a cero';
        }
        
        // Validar piso - ACTUALIZADO para permitir sótanos
        if (!is_numeric($data['piso']) || $data['piso'] < -4 || $data['piso'] > 3 || $data['piso'] == 0) {
            $errores[] = 'El piso debe estar entre -4 y 3 (excluyendo 0)';
        }
        
        return $errores;
    }
    
    /**
     * Obtener nombre del piso - HELPER
     */
    public static function getNombrePiso($piso) {
        $pisos = self::getPisos();
        return $pisos[$piso] ?? "Piso {$piso}";
    }
    
    /**
     * Obtener resumen de disponibilidad por tipo
     */
    public function resumenDisponibilidadPorTipo() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT 
                    tipo,
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                    SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                    AVG(precio_base) as precio_promedio
                FROM {$this->table}
                WHERE hotel_id = ? AND activa = 1
                GROUP BY tipo
                ORDER BY precio_promedio DESC";
        
        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt->fetchAll();
    }
}
