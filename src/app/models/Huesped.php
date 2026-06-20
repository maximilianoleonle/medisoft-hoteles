<?php
/**
 * Modelo de Huésped
 * Los Cedros
 */

class Huesped extends Model {
    protected $table = 'huespedes';
    protected $fillable = [
        'hotel_id',
        'nombre_completo',
        'telefono',
        'email',
        'procedencia_estado',
        'procedencia_ciudad',
        'vehiculo_marca',
        'vehiculo_placas',
        'notas',
        'datos_extra_json'
    ];
    
    /**
     * Obtener lista de estados de México
     */
    public static function getEstados() {
        return [
            'Aguascalientes' => 'Aguascalientes',
            'Baja California' => 'Baja California',
            'Baja California Sur' => 'Baja California Sur',
            'Campeche' => 'Campeche',
            'Chiapas' => 'Chiapas',
            'Chihuahua' => 'Chihuahua',
            'Ciudad de México' => 'Ciudad de México',
            'Coahuila' => 'Coahuila',
            'Colima' => 'Colima',
            'Durango' => 'Durango',
            'Estado de México' => 'Estado de México',
            'Guanajuato' => 'Guanajuato',
            'Guerrero' => 'Guerrero',
            'Hidalgo' => 'Hidalgo',
            'Jalisco' => 'Jalisco',
            'Michoacán' => 'Michoacán',
            'Morelos' => 'Morelos',
            'Nayarit' => 'Nayarit',
            'Nuevo León' => 'Nuevo León',
            'Oaxaca' => 'Oaxaca',
            'Puebla' => 'Puebla',
            'Querétaro' => 'Querétaro',
            'Quintana Roo' => 'Quintana Roo',
            'San Luis Potosí' => 'San Luis Potosí',
            'Sinaloa' => 'Sinaloa',
            'Sonora' => 'Sonora',
            'Tabasco' => 'Tabasco',
            'Tamaulipas' => 'Tamaulipas',
            'Tlaxcala' => 'Tlaxcala',
            'Veracruz' => 'Veracruz',
            'Yucatán' => 'Yucatán',
            'Zacatecas' => 'Zacatecas'
        ];
    }
    
    /**
     * Buscar huéspedes por término
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

    public function findForHotel($id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || (int)$id <= 0) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1";
        $stmt = $this->db->query($sql, [(int)$id, $hotelId]);
        $huesped = $stmt ? $stmt->fetch() : null;
        return $huesped ?: null;
    }

    public function buscarPorHotel($termino, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return [];
        }

        $sql = "SELECT DISTINCT h.*
                FROM {$this->table} h
                LEFT JOIN huesped_vehiculos v ON h.id = v.huesped_id AND v.activo = 1
                WHERE h.hotel_id = ?
                  AND (
                    h.nombre_completo LIKE ?
                    OR h.telefono LIKE ?
                    OR h.email LIKE ?
                    OR h.vehiculo_placas LIKE ?
                    OR v.placas LIKE ?
                  )
                ORDER BY h.nombre_completo";

        $termino = '%' . $termino . '%';
        $stmt = $this->db->query($sql, [$hotelId, $termino, $termino, $termino, $termino, $termino]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function buscarPorTelefonoPorHotel($telefono, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || trim((string)$telefono) === '') {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE telefono = ? AND hotel_id = ? LIMIT 1";
        $stmt = $this->db->query($sql, [$telefono, $hotelId]);
        $huesped = $stmt ? $stmt->fetch() : null;
        return $huesped ?: null;
    }

    public function paginatePorHotel($perPage = 20, $page = 1, $conditions = [], $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 0,
            ];
        }

        $where = ['hotel_id = ?'];
        $params = [$hotelId];

        foreach ($conditions as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $where[] = "{$field} = ?";
            $params[] = $value;
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereSql}";
        $countStmt = $this->db->query($countSql, $params);
        $totalRow = $countStmt ? $countStmt->fetch() : ['total' => 0];
        $total = (int)($totalRow['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM {$this->table}
                WHERE {$whereSql}
                ORDER BY created_at DESC, nombre_completo ASC
                LIMIT ? OFFSET ?";
        $selectParams = array_merge($params, [$perPage, $offset]);
        $stmt = $this->db->query($sql, $selectParams);

        return [
            'data' => $stmt ? $stmt->fetchAll() : [],
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    public function getReservacionesPorHotel($huesped_id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return [];
        }

        $sql = "SELECT r.*,
                GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') as habitaciones_numeros,
                GROUP_CONCAT(DISTINCT h.tipo) as tipos_habitacion,
                COUNT(DISTINCT rh.habitacion_id) as total_habitaciones,
                SUM(CASE WHEN rh.es_cortesia = 1 THEN 1 ELSE 0 END) as habitaciones_cortesia
                FROM reservaciones r
                LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                LEFT JOIN habitaciones h ON rh.habitacion_id = h.id
                    AND h.hotel_id = rh.hotel_id
                WHERE r.huesped_id = ?
                  AND r.hotel_id = ?
                GROUP BY r.id
                ORDER BY r.fecha_entrada DESC";

        $stmt = $this->db->query($sql, [(int)$huesped_id, $hotelId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function getVehiculosPorHotel($huesped_id, $hotelId = null) {
        $vehiculoModel = new HuespedVehiculo();
        return $vehiculoModel->porHuespedHotel((int)$huesped_id, $this->hotelIdActual($hotelId));
    }

    public function contarVehiculosPorHotel($huesped_id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as total
                FROM huesped_vehiculos v
                INNER JOIN huespedes h ON v.huesped_id = h.id
                WHERE v.huesped_id = ?
                  AND h.hotel_id = ?
                  AND v.activo = 1";
        $stmt = $this->db->query($sql, [(int)$huesped_id, $hotelId]);
        $result = $stmt ? $stmt->fetch() : null;
        return (int)($result['total'] ?? 0);
    }

    public function contarReservacionesPorHotel($huesped_id, $hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as total FROM reservaciones WHERE huesped_id = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [(int)$huesped_id, $hotelId]);
        $result = $stmt ? $stmt->fetch() : null;
        return (int)($result['total'] ?? 0);
    }

    public function existeTelefonoPorHotel($telefono, $hotelId = null, $excluir_id = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0 || empty($telefono)) {
            return false;
        }

        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE telefono = ? AND hotel_id = ?";
        $params = [$telefono, $hotelId];

        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = (int)$excluir_id;
        }

        $stmt = $this->db->query($sql, $params);
        $result = $stmt ? $stmt->fetch() : null;
        return (int)($result['total'] ?? 0) > 0;
    }

    public function obtenerEstadisticasPorHotel($hotelId = null) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return [];
        }

        $sql = "SELECT
                COUNT(*) as total_huespedes,
                COUNT(CASE WHEN (
                    (vehiculo_marca IS NOT NULL AND vehiculo_marca != '')
                    OR EXISTS (
                        SELECT 1
                        FROM huesped_vehiculos v
                        WHERE v.huesped_id = {$this->table}.id
                          AND v.activo = 1
                    )
                ) THEN 1 END) as con_vehiculo,
                COUNT(DISTINCT procedencia_estado) as estados_diferentes,
                COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as con_email,
                COUNT(CASE WHEN telefono IS NOT NULL AND telefono != '' THEN 1 END) as con_telefono
                FROM {$this->table}
                WHERE hotel_id = ?";

        $stmt = $this->db->query($sql, [$hotelId]);
        $result = $stmt ? $stmt->fetch() : null;
        return $result ?: [];
    }

    public function perfilOperativoReadOnlyPorHotel($huespedId, $hotelId = null) {
        $huespedId = (int)$huespedId;
        $hotelId = $this->hotelIdActual($hotelId);
        $perfil = $this->perfilOperativoBase();

        if ($huespedId <= 0 || $hotelId <= 0) {
            $perfil['alertas'][] = [
                'tipo' => 'scope',
                'nivel' => 'warning',
                'titulo' => 'Sin contexto de hotel',
                'mensaje' => 'No se pudo calcular el perfil operativo.',
            ];
            return $perfil;
        }

        if (!$this->huespedPerteneceAlHotel($huespedId, $hotelId)) {
            $perfil['alertas'][] = [
                'tipo' => 'scope',
                'nivel' => 'warning',
                'titulo' => 'Huesped fuera de alcance',
                'mensaje' => 'El perfil operativo solo lee huespedes del hotel actual.',
            ];
            return $perfil;
        }

        $reservaciones = $this->metricasReservacionesPerfil($huespedId, $hotelId);
        $vehiculos = $this->metricasVehiculosPerfil($huespedId, $hotelId);
        $cxc = $this->metricasCxcPerfil($huespedId, $hotelId);
        $documentos = $this->metricasDocumentosPerfil($huespedId, $hotelId);

        $perfil['reservaciones'] = $reservaciones;
        $perfil['vehiculos'] = $vehiculos;
        $perfil['cxc'] = $cxc;
        $perfil['documentos'] = $documentos;
        $perfil['score'] = $this->scoreRecurrenciaPerfil($reservaciones, $vehiculos, $cxc, $documentos);
        $perfil['clasificacion'] = $this->clasificacionPerfil($perfil['score'], $reservaciones, $cxc);
        $perfil['alertas'] = $this->alertasPerfil($reservaciones, $vehiculos, $cxc, $documentos);

        return $perfil;
    }

    private function perfilOperativoBase(): array {
        return [
            'solo_lectura' => true,
            'clasificacion' => 'Sin historial',
            'score' => 0,
            'reservaciones' => [
                'total' => 0,
                'validas' => 0,
                'proximas' => 0,
                'activas' => 0,
                'canceladas' => 0,
                'noches' => 0,
                'total_gastado' => 0.0,
                'ultima_visita' => null,
                'proxima_visita' => null,
            ],
            'vehiculos' => [
                'activos' => 0,
            ],
            'cxc' => [
                'cuentas_pendientes' => 0,
                'saldo_pendiente' => 0.0,
            ],
            'documentos' => [
                'total' => 0,
                'activos' => 0,
            ],
            'alertas' => [],
        ];
    }

    private function huespedPerteneceAlHotel(int $huespedId, int $hotelId): bool {
        $stmt = $this->db->query(
            "SELECT id FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$huespedId, $hotelId]
        );

        return $stmt && (bool)$stmt->fetch();
    }

    private function metricasReservacionesPerfil(int $huespedId, int $hotelId): array {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN estado IN ('checked_in', 'checked_out', 'completada') THEN 1 ELSE 0 END), 0) AS validas,
                    COALESCE(SUM(CASE WHEN fecha_entrada >= CURDATE() AND estado <> 'cancelada' THEN 1 ELSE 0 END), 0) AS proximas,
                    COALESCE(SUM(CASE WHEN estado = 'checked_in' THEN 1 ELSE 0 END), 0) AS activas,
                    COALESCE(SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END), 0) AS canceladas,
                    COALESCE(SUM(CASE
                        WHEN estado IN ('checked_in', 'checked_out', 'completada')
                        THEN GREATEST(DATEDIFF(fecha_salida, fecha_entrada), 0)
                        ELSE 0
                    END), 0) AS noches,
                    COALESCE(SUM(CASE
                        WHEN estado IN ('checked_in', 'checked_out', 'completada')
                        THEN COALESCE(precio_total, 0)
                        ELSE 0
                    END), 0) AS total_gastado,
                    MAX(CASE
                        WHEN estado IN ('checked_in', 'checked_out', 'completada')
                        THEN fecha_salida
                        ELSE NULL
                    END) AS ultima_visita,
                    MIN(CASE
                        WHEN fecha_entrada >= CURDATE() AND estado <> 'cancelada'
                        THEN fecha_entrada
                        ELSE NULL
                    END) AS proxima_visita
             FROM reservaciones
             WHERE huesped_id = ?
               AND hotel_id = ?",
            [$huespedId, $hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'validas' => (int)($row['validas'] ?? 0),
            'proximas' => (int)($row['proximas'] ?? 0),
            'activas' => (int)($row['activas'] ?? 0),
            'canceladas' => (int)($row['canceladas'] ?? 0),
            'noches' => (int)($row['noches'] ?? 0),
            'total_gastado' => (float)($row['total_gastado'] ?? 0),
            'ultima_visita' => $row['ultima_visita'] ?? null,
            'proxima_visita' => $row['proxima_visita'] ?? null,
        ];
    }

    private function metricasVehiculosPerfil(int $huespedId, int $hotelId): array {
        if (!$this->tablaPerfilExiste('huesped_vehiculos')) {
            return ['activos' => 0];
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS activos
             FROM huesped_vehiculos v
             INNER JOIN {$this->table} h
                ON h.id = v.huesped_id
             WHERE v.huesped_id = ?
               AND h.hotel_id = ?
               AND v.activo = 1",
            [$huespedId, $hotelId]
        );
        $row = $stmt ? ($stmt->fetch() ?: []) : [];

        return ['activos' => (int)($row['activos'] ?? 0)];
    }

    private function metricasCxcPerfil(int $huespedId, int $hotelId): array {
        if (!$this->tablaPerfilExiste('cuentas_por_cobrar')) {
            return ['cuentas_pendientes' => 0, 'saldo_pendiente' => 0.0];
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS cuentas_pendientes,
                    COALESCE(SUM(saldo), 0) AS saldo_pendiente
             FROM cuentas_por_cobrar
             WHERE hotel_id = ?
               AND huesped_id = ?
               AND estado IN ('pendiente', 'parcial', 'vencida')
               AND saldo > 0",
            [$hotelId, $huespedId]
        );
        $row = $stmt ? ($stmt->fetch() ?: []) : [];

        return [
            'cuentas_pendientes' => (int)($row['cuentas_pendientes'] ?? 0),
            'saldo_pendiente' => (float)($row['saldo_pendiente'] ?? 0),
        ];
    }

    private function metricasDocumentosPerfil(int $huespedId, int $hotelId): array {
        if (!$this->tablaPerfilExiste('documentos') || !$this->tablaPerfilExiste('documento_entidades')) {
            return ['total' => 0, 'activos' => 0];
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN d.estado = 'activo' THEN 1 ELSE 0 END), 0) AS activos
             FROM documento_entidades de
             INNER JOIN documentos d
                ON d.id = de.documento_id
               AND d.hotel_id = de.hotel_id
             WHERE de.hotel_id = ?
               AND de.entidad_tipo = 'huesped'
               AND de.entidad_id = ?",
            [$hotelId, $huespedId]
        );
        $row = $stmt ? ($stmt->fetch() ?: []) : [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'activos' => (int)($row['activos'] ?? 0),
        ];
    }

    private function scoreRecurrenciaPerfil(array $reservaciones, array $vehiculos, array $cxc, array $documentos): int {
        $score = 0;
        $score += min(40, (int)$reservaciones['validas'] * 10);
        $score += min(20, (int)$reservaciones['noches'] * 2);
        $score += ((int)$reservaciones['proximas'] > 0) ? 15 : 0;
        $score += ((int)$vehiculos['activos'] > 0) ? 8 : 0;
        $score += ((int)$documentos['activos'] > 0) ? 7 : 0;
        $score -= ((float)$cxc['saldo_pendiente'] > 0) ? 10 : 0;

        return max(0, min(100, $score));
    }

    private function clasificacionPerfil(int $score, array $reservaciones, array $cxc): string {
        if ((float)$cxc['saldo_pendiente'] > 0) {
            return 'Con adeudo';
        }

        if ((int)$reservaciones['proximas'] > 0) {
            return 'Proxima estancia';
        }

        if ($score >= 60 || (int)$reservaciones['validas'] >= 3) {
            return 'Frecuente';
        }

        if ((int)$reservaciones['validas'] > 0) {
            return 'Activo';
        }

        return 'Sin historial';
    }

    private function alertasPerfil(array $reservaciones, array $vehiculos, array $cxc, array $documentos): array {
        $alertas = [];

        if ((float)$cxc['saldo_pendiente'] > 0) {
            $alertas[] = [
                'tipo' => 'cxc',
                'nivel' => 'warning',
                'titulo' => 'Saldo pendiente',
                'mensaje' => 'Existe saldo CxC pendiente para este huesped.',
            ];
        }

        if ((int)$documentos['activos'] === 0) {
            $alertas[] = [
                'tipo' => 'documentos',
                'nivel' => 'info',
                'titulo' => 'Sin documentos activos',
                'mensaje' => 'No hay documentos activos vinculados a este huesped.',
            ];
        }

        if ((int)$vehiculos['activos'] === 0) {
            $alertas[] = [
                'tipo' => 'vehiculos',
                'nivel' => 'info',
                'titulo' => 'Sin vehiculos activos',
                'mensaje' => 'No hay vehiculos activos registrados.',
            ];
        }

        if ((int)$reservaciones['proximas'] > 0) {
            $alertas[] = [
                'tipo' => 'reservaciones',
                'nivel' => 'success',
                'titulo' => 'Proxima estancia',
                'mensaje' => 'El huesped tiene reservaciones futuras en este hotel.',
            ];
        }

        return $alertas;
    }

    private function tablaPerfilExiste(string $tabla): bool {
        try {
            $database = defined('DB_NAME') ? DB_NAME : (string)(getenv('DB_DATABASE') ?: '');
            if ($database === '') {
                $stmtDb = $this->db->query('SELECT DATABASE() AS db_name');
                $rowDb = $stmtDb ? ($stmtDb->fetch() ?: []) : [];
                $database = (string)($rowDb['db_name'] ?? '');
            }

            if ($database === '') {
                return false;
            }

            $stmt = $this->db->query(
                "SELECT COUNT(*) AS total
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME = ?",
                [$database, $tabla]
            );
            $row = $stmt ? ($stmt->fetch() ?: []) : [];

            return (int)($row['total'] ?? 0) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function buscarAutocompletadoPorHotel($termino, $hotelId = null, $limite = 10) {
        $hotelId = $this->hotelIdActual($hotelId);
        if ($hotelId <= 0) {
            return [];
        }

        $sql = "SELECT id, nombre_completo, telefono, procedencia_estado
                FROM {$this->table}
                WHERE hotel_id = ?
                  AND (nombre_completo LIKE ? OR telefono LIKE ?)
                ORDER BY nombre_completo
                LIMIT ?";

        $termino = '%' . $termino . '%';
        $stmt = $this->db->query($sql, [$hotelId, $termino, $termino, (int)$limite]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function buscar($termino) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE nombre_completo LIKE ? 
                OR telefono LIKE ?
                OR email LIKE ?
                OR vehiculo_placas LIKE ?
                ORDER BY nombre_completo";
        
        $termino = '%' . $termino . '%';
        $stmt = $this->db->query($sql, [$termino, $termino, $termino, $termino]);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar por teléfono exacto
     */
    public function buscarPorTelefono($telefono) {
        return $this->first(['telefono' => $telefono]);
    }
    
    /**
     * Obtener historial de reservaciones del huésped (ACTUALIZADO)
     */
    public function getReservaciones($huesped_id) {
        $sql = "SELECT r.*, 
                GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') as habitaciones_numeros,
                GROUP_CONCAT(DISTINCT h.tipo) as tipos_habitacion,
                COUNT(DISTINCT rh.habitacion_id) as total_habitaciones,
                SUM(CASE WHEN rh.es_cortesia = 1 THEN 1 ELSE 0 END) as habitaciones_cortesia
                FROM reservaciones r
                LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                LEFT JOIN habitaciones h ON rh.habitacion_id = h.id
                WHERE r.huesped_id = ?
                GROUP BY r.id
                ORDER BY r.fecha_entrada DESC";
        
        $stmt = $this->db->query($sql, [$huesped_id]);
        return $stmt->fetchAll();
    }
    
    // Agregar estos métodos a la clase Huesped existente:

/**
 * Obtener vehículos del huésped
 */
public function getVehiculos($huesped_id) {
    $vehiculoModel = new HuespedVehiculo();
    return $vehiculoModel->porHuesped($huesped_id);
}

/**
 * Contar vehículos activos del huésped
 */
public function contarVehiculos($huesped_id) {
    $sql = "SELECT COUNT(*) as total FROM huesped_vehiculos 
            WHERE huesped_id = ? AND activo = 1";
    $stmt = $this->db->query($sql, [$huesped_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Obtener huéspedes con sus vehículos
 */
public function buscarConVehiculos($termino = null) {
    $sql = "SELECT h.*, 
            COUNT(DISTINCT v.id) as total_vehiculos,
            GROUP_CONCAT(DISTINCT CONCAT(v.marca, ' - ', v.placas) SEPARATOR ', ') as vehiculos_info
            FROM {$this->table} h
            LEFT JOIN huesped_vehiculos v ON h.id = v.huesped_id AND v.activo = 1";
    
    $params = [];
    
    if ($termino) {
        $sql .= " WHERE h.nombre_completo LIKE ? 
                  OR h.telefono LIKE ?
                  OR h.email LIKE ?
                  OR v.placas LIKE ?";
        $termino_like = '%' . $termino . '%';
        $params = [$termino_like, $termino_like, $termino_like, $termino_like];
    }
    
    $sql .= " GROUP BY h.id ORDER BY h.nombre_completo";
    
    $stmt = $this->db->query($sql, $params);
    return $stmt->fetchAll();
}
    /**
     * Contar reservaciones del huésped
     */
    public function contarReservaciones($huesped_id) {
        $sql = "SELECT COUNT(*) as total FROM reservaciones WHERE huesped_id = ?";
        $stmt = $this->db->query($sql, [$huesped_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Obtener estadísticas de huéspedes por estado
     */
    public function estadisticasPorEstado() {
        $sql = "SELECT procedencia_estado, COUNT(*) as total 
                FROM {$this->table} 
                WHERE procedencia_estado IS NOT NULL 
                GROUP BY procedencia_estado 
                ORDER BY total DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Validar datos del huésped
     */
    public function validar($data, $id = null) {
        $errores = [];
        
        // Validar nombre completo
        if (empty($data['nombre_completo'])) {
            $errores[] = 'El nombre completo es obligatorio';
        } elseif (strlen($data['nombre_completo']) < 3) {
            $errores[] = 'El nombre debe tener al menos 3 caracteres';
        } elseif (strlen($data['nombre_completo']) > 200) {
            $errores[] = 'El nombre no puede exceder 200 caracteres';
        }
        
        // Validar teléfono (opcional pero si se proporciona debe ser válido)
        if (!empty($data['telefono'])) {
            // Eliminar espacios y guiones
            $telefono_limpio = preg_replace('/[\s\-\(\)]/', '', $data['telefono']);
            if (!preg_match('/^[0-9]{10,15}$/', $telefono_limpio)) {
                $errores[] = 'El teléfono debe contener entre 10 y 15 dígitos';
            }
        }
        
        // Validar email (opcional)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }
        
        // Validar placas (opcional)
        if (!empty($data['vehiculo_placas'])) {
            if (strlen($data['vehiculo_placas']) > 20) {
                $errores[] = 'Las placas no pueden exceder 20 caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Verificar si existe un huésped con el mismo teléfono
     */
    public function existeTelefono($telefono, $excluir_id = null) {
        if (empty($telefono)) return false;
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE telefono = ?";
        $params = [$telefono];
        
        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }
    
    /**
     * Obtener huéspedes frecuentes (más de 3 visitas) - ACTUALIZADO
     */
    public function getHuespedesFrecuentes($limite = 10) {
        $sql = "SELECT h.*, COUNT(DISTINCT r.id) as total_visitas,
                MAX(r.fecha_entrada) as ultima_visita,
                SUM(r.precio_total) as total_gastado
                FROM {$this->table} h
                INNER JOIN reservaciones r ON h.id = r.huesped_id
                WHERE r.estado IN ('checked_out', 'checked_in')
                GROUP BY h.id
                HAVING total_visitas >= 3
                ORDER BY total_visitas DESC, ultima_visita DESC
                LIMIT ?";
        
        $stmt = $this->db->query($sql, [$limite]);
        return $stmt->fetchAll();
    }
    
    /**
     * Contar huéspedes con vehículo
     */
    public function contarConVehiculo() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE vehiculo_marca IS NOT NULL 
                AND vehiculo_marca != ''";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Obtener estadísticas generales
     */
    public function obtenerEstadisticas() {
        $sql = "SELECT 
                COUNT(*) as total_huespedes,
                COUNT(CASE WHEN vehiculo_marca IS NOT NULL AND vehiculo_marca != '' THEN 1 END) as con_vehiculo,
                COUNT(DISTINCT procedencia_estado) as estados_diferentes,
                COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as con_email,
                COUNT(CASE WHEN telefono IS NOT NULL AND telefono != '' THEN 1 END) as con_telefono
                FROM {$this->table}";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result ?: [];
    }
    
    /**
     * Buscar huéspedes para autocompletado
     */
    public function buscarAutocompletado($termino, $limite = 10) {
        $sql = "SELECT id, nombre_completo, telefono, procedencia_estado
                FROM {$this->table}
                WHERE nombre_completo LIKE ? OR telefono LIKE ?
                ORDER BY nombre_completo
                LIMIT ?";
        
        $termino = '%' . $termino . '%';
        $stmt = $this->db->query($sql, [$termino, $termino, $limite]);
        return $stmt->fetchAll();
    }
}
