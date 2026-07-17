<?php
/**
 * Areas del hotel (bloque habitaciones y areas).
 *
 * Zonas que no son habitacion pero se operan igual: alberca, lobby,
 * restaurante... Reciben limpieza (tareas_operativas.area_id), mantenimiento
 * (mantenimientos_habitaciones.area_id) y bloqueo (estado 'cerrada'). No se
 * reservan: su enum de estado es el de habitaciones sin 'ocupada'.
 */

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class Area extends Model {
    protected $table = 'areas_hotel';
    protected $fillable = [
        'hotel_id',
        'nombre',
        'tipo',
        'piso',
        'descripcion',
        'estado',
        'foto_url',
        'activa',
    ];

    public const ESTADOS = ['disponible', 'limpieza', 'mantenimiento', 'cerrada'];

    /**
     * Catalogo sugerido de tipos; 'otra' cubre lo libre. tipo es VARCHAR:
     * el enum vive aqui y se valida en PHP (gotcha enum MySQL).
     */
    public static function catalogoTipos(): array {
        return [
            'alberca'         => ['label' => 'Alberca',          'icono' => 'fa-person-swimming'],
            'lobby'           => ['label' => 'Lobby / Recepción', 'icono' => 'fa-bell-concierge'],
            'restaurante'     => ['label' => 'Restaurante / Bar', 'icono' => 'fa-utensils'],
            'jardin'          => ['label' => 'Jardín / Exterior', 'icono' => 'fa-tree'],
            'estacionamiento' => ['label' => 'Estacionamiento',  'icono' => 'fa-square-parking'],
            'salon_eventos'   => ['label' => 'Salón de eventos', 'icono' => 'fa-champagne-glasses'],
            'gimnasio'        => ['label' => 'Gimnasio / Spa',   'icono' => 'fa-dumbbell'],
            'lavanderia'      => ['label' => 'Lavandería',       'icono' => 'fa-jug-detergent'],
            'pasillos'        => ['label' => 'Pasillos / Escaleras', 'icono' => 'fa-stairs'],
            'azotea'          => ['label' => 'Azotea / Terraza', 'icono' => 'fa-umbrella-beach'],
            'otra'            => ['label' => 'Otra área',        'icono' => 'fa-location-dot'],
        ];
    }

    public static function tipoValido(string $tipo): bool {
        return array_key_exists($tipo, self::catalogoTipos());
    }

    public static function estadoValido(string $estado): bool {
        return in_array($estado, self::ESTADOS, true);
    }

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    public function obtenerPorId(int $id, ?int $hotelId = null): ?array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($id <= 0 || $hotelId <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$id, $hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function obtenerPorNombre(string $nombre, ?int $hotelId = null): ?array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        $nombre = trim($nombre);
        if ($nombre === '' || $hotelId <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT * FROM {$this->table} WHERE hotel_id = ? AND nombre = ? LIMIT 1",
            [$hotelId, $nombre]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    /**
     * Listado para el mapa/indice: cada area con su tarea de limpieza y su
     * mantenimiento abiertos (si los hay), para pintar estado accionable.
     */
    public function listar(?int $hotelId = null, bool $soloActivas = false): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            return [];
        }

        $whereActiva = $soloActivas ? ' AND a.activa = 1' : '';
        $stmt = $this->db->query(
            "SELECT a.*,
                    (
                        SELECT t.id
                        FROM tareas_operativas t
                        WHERE t.hotel_id = a.hotel_id
                          AND t.area_id = a.id
                          AND t.categoria = 'limpieza'
                          AND t.estado IN ('pendiente', 'asignada', 'en_proceso')
                        ORDER BY t.id DESC
                        LIMIT 1
                    ) AS tarea_limpieza_id,
                    (
                        SELECT m.id
                        FROM mantenimientos_habitaciones m
                        WHERE m.hotel_id = a.hotel_id
                          AND m.area_id = a.id
                          AND m.estado IN ('en_proceso', 'programado')
                        ORDER BY m.id DESC
                        LIMIT 1
                    ) AS mantenimiento_abierto_id
             FROM {$this->table} a
             WHERE a.hotel_id = ?{$whereActiva}
             ORDER BY a.activa DESC, a.piso ASC, a.nombre ASC",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    /**
     * Conteo por estado para KPIs del mapa (solo areas activas).
     */
    public function contarPorEstado(?int $hotelId = null): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        $conteo = array_fill_keys(self::ESTADOS, 0);
        if ($hotelId <= 0) {
            return $conteo;
        }

        $stmt = $this->db->query(
            "SELECT estado, COUNT(*) AS n
             FROM {$this->table}
             WHERE hotel_id = ? AND activa = 1
             GROUP BY estado",
            [$hotelId]
        );
        foreach (($stmt ? ($stmt->fetchAll() ?: []) : []) as $row) {
            $estado = (string)($row['estado'] ?? '');
            if (isset($conteo[$estado])) {
                $conteo[$estado] = (int)($row['n'] ?? 0);
            }
        }

        return $conteo;
    }

    /**
     * Cambia el estado del area validando contra el enum PHP.
     */
    public function cambiarEstado(int $id, string $estado, ?int $hotelId = null): bool {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($id <= 0 || $hotelId <= 0 || !self::estadoValido($estado)) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE {$this->table} SET estado = ?, updated_at = NOW()
             WHERE id = ? AND hotel_id = ?",
            [$estado, $id, $hotelId]
        );

        return (bool)$stmt;
    }

    /**
     * Historial operativo del area: limpiezas (tareas_operativas) y
     * mantenimientos, lo mas reciente primero.
     */
    public function historial(int $areaId, ?int $hotelId = null, int $limite = 15): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($areaId <= 0 || $hotelId <= 0) {
            return ['limpiezas' => [], 'mantenimientos' => []];
        }

        $limite = max(1, min(50, $limite));

        $stmt = $this->db->query(
            "SELECT t.id, t.titulo, t.estado, t.fecha_programada, t.fecha_cierre,
                    t.notas_cierre, t.created_at
             FROM tareas_operativas t
             WHERE t.hotel_id = ?
               AND t.area_id = ?
               AND t.categoria = 'limpieza'
             ORDER BY t.id DESC
             LIMIT {$limite}",
            [$hotelId, $areaId]
        );
        $limpiezas = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $stmt = $this->db->query(
            "SELECT m.id, m.tipo_mantenimiento, m.motivo, m.prioridad, m.estado,
                    m.fecha_inicio, m.fecha_fin, m.costo, m.proveedor
             FROM mantenimientos_habitaciones m
             WHERE m.hotel_id = ?
               AND m.area_id = ?
             ORDER BY m.id DESC
             LIMIT {$limite}",
            [$hotelId, $areaId]
        );
        $mantenimientos = $stmt ? ($stmt->fetchAll() ?: []) : [];

        return ['limpiezas' => $limpiezas, 'mantenimientos' => $mantenimientos];
    }

    /**
     * Crea o actualiza un area con datos ya validados por el controlador.
     */
    public function guardar(array $datos, ?int $id = null) {
        $hotelId = (int)$this->hotelIdActual();
        $datos['hotel_id'] = $hotelId;

        if ($id) {
            unset($datos['hotel_id']);
            $sets = [];
            $valores = [];
            foreach ($datos as $campo => $valor) {
                if (!in_array($campo, $this->fillable, true)) {
                    continue;
                }
                $sets[] = "{$campo} = ?";
                $valores[] = $valor;
            }
            if (empty($sets)) {
                return false;
            }
            $valores[] = $id;
            $valores[] = $hotelId;
            $stmt = $this->db->query(
                "UPDATE {$this->table} SET " . implode(', ', $sets) . ", updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?",
                $valores
            );
            return $stmt ? $id : false;
        }

        return $this->create($datos);
    }
}
