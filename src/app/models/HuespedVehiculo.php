<?php
/**
 * Modelo de Vehículos de Huéspedes
 * Los Cedros
 */

class HuespedVehiculo extends Model {
    protected $table = 'huesped_vehiculos';
    protected $fillable = [
        'hotel_id',
        'huesped_id',
        'marca',
        'modelo',
        'placas',
        'color',
        'estacionamiento',
        'datos_extra_json',
        'activo'
    ];
    
    /**
     * Obtener opciones de estacionamiento
     */
    public static function getEstacionamientos() {
        return [
            'coches' => 'Coches',
            'camionetas' => 'Camionetas',
            'discos' => 'Discos',
            'nikkos' => 'Nikkos'
        ];
    }
    
    /**
     * ¿Estas placas son el token INTERNO que inventa el sistema?
     *
     * Cuando el hotel no exige placas, HuespedController las genera solas
     * ('SINPLACA' + 12 hex) para no romper el índice único por hotel. Es un
     * relleno de base de datos, JAMÁS algo que el hotelero deba leer.
     *
     * El patrón es EXACTO a propósito: 'SINPLACAS' o 'SINPLACAS1' los escribió
     * una persona a mano (hay 2 así en producción) y su texto se respeta.
     */
    public static function esPlacaInterna($placas): bool {
        return (bool)preg_match('/^SINPLACA[0-9A-F]{12}$/i', trim((string)$placas));
    }

    /**
     * Placas listas para pintar: el token interno se lee como "sin placas".
     * Devuelve '' cuando no hay nada que mostrar y no se pasó respaldo, para
     * que cada vista decida (unas ocultan el renglón, otras dicen 'S/P').
     */
    public static function placasVisibles($placas, string $siNoHay = ''): string {
        $placas = trim((string)$placas);

        if ($placas === '' || self::esPlacaInterna($placas)) {
            return $siNoHay;
        }

        return $placas;
    }

    /**
     * Misma limpieza para lo que sale por JSON: el JS de reservaciones y de la
     * ficha ya pintan 'Sin placas' cuando el campo viene vacío, así que basta
     * con vaciarlo. Se limpia SOLO la copia que viaja al navegador; el token
     * sigue intacto en la BD (es lo que sostiene el índice único) y el guardado
     * lo conserva porque relee las placas actuales desde la base.
     */
    public static function paraMostrar($vehiculo) {
        if (!is_array($vehiculo) || !array_key_exists('placas', $vehiculo)) {
            return $vehiculo;
        }

        $vehiculo['placas'] = self::placasVisibles($vehiculo['placas']);

        return $vehiculo;
    }

    public static function listaParaMostrar($vehiculos): array {
        if (!is_array($vehiculos)) {
            return [];
        }

        return array_map([self::class, 'paraMostrar'], $vehiculos);
    }

    /**
     * Obtener vehículos por huésped
     */
    private function hotelIdActual($hotelId = null) {
        if ($hotelId !== null && (int)$hotelId > 0) {
            return (int)$hotelId;
        }

        if (function_exists('obtenerHotelIdActualCompat')) {
            return (int)obtenerHotelIdActualCompat();
        }

        return (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function hotelIdPorHuesped($huespedId) {
        $huespedId = (int)$huespedId;
        if ($huespedId <= 0) {
            return 0;
        }

        $stmt = $this->db->query(
            "SELECT hotel_id FROM huespedes WHERE id = ? LIMIT 1",
            [$huespedId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return (int)($row['hotel_id'] ?? 0);
    }

    public function create($data) {
        if (empty($data['hotel_id']) && !empty($data['huesped_id'])) {
            $hotelId = $this->hotelIdPorHuesped($data['huesped_id']);
            if ($hotelId > 0) {
                $data['hotel_id'] = $hotelId;
            }
        }

        return parent::create($data);
    }

    public function porHuespedHotel($huesped_id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || (int)$huesped_id <= 0) {
            return [];
        }

        $sql = "SELECT v.*
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.huesped_id = ?
                  AND v.hotel_id = ?
                  AND v.activo = 1
                ORDER BY v.created_at DESC";

        $stmt = $this->db->query($sql, [(int)$huesped_id, $hotelId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function findForHotel($id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || (int)$id <= 0) {
            return null;
        }

        $sql = "SELECT v.*
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.id = ?
                  AND v.hotel_id = ?
                LIMIT 1";

        $stmt = $this->db->query($sql, [(int)$id, $hotelId]);
        $vehiculo = $stmt ? $stmt->fetch() : null;
        return $vehiculo ?: null;
    }

    public function buscarPorPlacasPorHotel($placas, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || trim((string)$placas) === '') {
            return null;
        }

        $sql = "SELECT v.*
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.placas = ?
                  AND v.hotel_id = ?
                  AND v.activo = 1
                LIMIT 1";

        $stmt = $this->db->query($sql, [$placas, $hotelId]);
        $vehiculo = $stmt ? $stmt->fetch() : null;
        return $vehiculo ?: null;
    }

    public function existenPlacasPorHotel($placas, $hotelId = null, $excluir_id = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || empty($placas)) {
            return false;
        }

        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.placas = ?
                  AND v.hotel_id = ?
                  AND v.activo = 1";
        $params = [$placas, $hotelId];

        if ($excluir_id) {
            $sql .= " AND v.id != ?";
            $params[] = (int)$excluir_id;
        }

        $stmt = $this->db->query($sql, $params);
        $result = $stmt ? $stmt->fetch() : null;
        return (int)($result['total'] ?? 0) > 0;
    }

    public function desactivarParaHotel($id, $hotelId = null) {
        $vehiculo = $this->findForHotel((int)$id, $hotelId);
        if (!$vehiculo) {
            return false;
        }

        return $this->update((int)$id, ['activo' => 0]);
    }

    public function obtenerConHuespedParaHotel($id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || (int)$id <= 0) {
            return null;
        }

        $sql = "SELECT v.*, h.nombre_completo, h.telefono
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.id = ?
                  AND v.hotel_id = ?
                  AND v.activo = 1";

        $stmt = $this->db->query($sql, [(int)$id, $hotelId]);
        $vehiculo = $stmt ? $stmt->fetch() : null;
        return $vehiculo ?: null;
    }

    public function porHuesped($huesped_id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId > 0) {
            return $this->porHuespedHotel($huesped_id, $hotelId);
        }

        $sql = "SELECT v.*
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.huesped_id = ?
                  AND v.activo = 1
                ORDER BY v.created_at DESC";

        $stmt = $this->db->query($sql, [$huesped_id]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Buscar vehículo por placas
     */
    public function buscarPorPlacas($placas, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId > 0) {
            return $this->buscarPorPlacasPorHotel($placas, $hotelId);
        }

        $sql = "SELECT v.*
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.placas = ?
                  AND v.activo = 1
                LIMIT 1";

        $stmt = $this->db->query($sql, [$placas]);
        $vehiculo = $stmt ? $stmt->fetch() : null;
        return $vehiculo ?: null;
    }
    
    /**
     * Verificar si las placas ya existen (excluyendo un ID específico)
     */
    public function existenPlacas($placas, $excluir_id = null, $hotelId = null) {
        if (empty($placas)) return false;

        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId > 0) {
            return $this->existenPlacasPorHotel($placas, $hotelId, $excluir_id);
        }

        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.placas = ?
                  AND v.activo = 1";
        $params = [$placas];

        if ($excluir_id) {
            $sql .= " AND v.id != ?";
            $params[] = $excluir_id;
        }

        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }
    
    /**
     * Contar vehículos activos por estacionamiento
     */
    public function contarPorEstacionamiento($hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);

        $sql = "SELECT v.estacionamiento, COUNT(*) as total
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.activo = 1";
        $params = [];

        if ($hotelId > 0) {
            $sql .= " AND v.hotel_id = ?";
            $params[] = $hotelId;
        }

        $sql .= " GROUP BY v.estacionamiento";

        $stmt = $this->db->query($sql, $params);
        $resultados = $stmt ? $stmt->fetchAll() : [];
        
        // Formatear resultados con los nuevos estacionamientos
        $conteo = [
            'coches' => 0,
            'camionetas' => 0,
            'discos' => 0,
            'nikkos' => 0
        ];
        
        foreach ($resultados as $resultado) {
            if (isset($conteo[$resultado['estacionamiento']])) {
                $conteo[$resultado['estacionamiento']] = $resultado['total'];
            }
        }
        
        return $conteo;
    }
    
    /**
     * Desactivar vehículo (soft delete)
     */
    public function desactivar($id) {
        return $this->update($id, ['activo' => 0]);
    }
    
    /**
     * Obtener información completa del vehículo con huésped
     */
    public function obtenerConHuesped($id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId > 0) {
            return $this->obtenerConHuespedParaHotel($id, $hotelId);
        }

        $sql = "SELECT v.*, h.nombre_completo, h.telefono
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.id = ? AND v.activo = 1";
        
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->fetch();
    }
    
    /**
     * Buscar vehículos con término de búsqueda
     */
    public function buscar($termino, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);

        $sql = "SELECT v.*, h.nombre_completo
                FROM {$this->table} v
                INNER JOIN huespedes h
                    ON v.huesped_id = h.id
                   AND h.hotel_id = v.hotel_id
                WHERE v.activo = 1";

        $params = [];
        if ($hotelId > 0) {
            $sql .= " AND v.hotel_id = ?";
            $params[] = $hotelId;
        }

        $sql .= " AND (v.placas LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?)
                ORDER BY v.created_at DESC";

        $termino = '%' . $termino . '%';
        $params = array_merge($params, [$termino, $termino, $termino]);
        $stmt = $this->db->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Migrar vehículos existentes de la tabla huespedes
     * (Función de utilidad para migración inicial)
     */
    public function migrarVehiculosExistentes() {
        // Primero migrar los datos existentes con valores temporales
        $sql = "INSERT INTO {$this->table} (hotel_id, huesped_id, marca, placas, estacionamiento, created_at)
                SELECT hotel_id, id, vehiculo_marca, vehiculo_placas,
                       CASE
                           WHEN vehiculo_marca LIKE '%camioneta%' OR vehiculo_marca LIKE '%pickup%' THEN 'camionetas'
                           ELSE 'coches'
                       END as estacionamiento,
                       created_at
                FROM huespedes
                WHERE vehiculo_marca IS NOT NULL 
                AND vehiculo_marca != ''
                AND NOT EXISTS (
                    SELECT 1 FROM {$this->table} 
                    WHERE huesped_id = huespedes.id 
                    AND placas = huespedes.vehiculo_placas
                )";
        
        return $this->db->query($sql);
    }
}
