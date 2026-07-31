<?php
/**
 * Activos del hotel con mantenimiento preventivo (bloque mantenimiento).
 *
 * "Boiler principal: servicio cada 180 dias". El cron genera el mantenimiento
 * preventivo al vencer proximo_servicio y el cierre del mantenimiento
 * recalcula el siguiente. El historial por activo (servicios + costo
 * acumulado) es el dato que le sirve al dueno.
 */

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class ActivoHotel extends Model {
    protected $table = 'activos_hotel';
    protected $fillable = [
        'hotel_id',
        'nombre',
        'ubicacion',
        'habitacion_id',
        // Un activo vive en UNA habitacion o en UN area (alberca, lobby, azotea),
        // nunca en las dos: lo excluyente lo impone MantenimientoController al
        // guardar. La columna existia desde la migracion de Areas pero no estaba
        // en fillable, asi que jamas se pudo guardar (jul-31).
        'area_id',
        'periodicidad_dias',
        'ultimo_servicio',
        'proximo_servicio',
        'notas',
        'activo',
    ];

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    public function obtenerPorId(int $id, ?int $hotelId = null): ?array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($id <= 0 || $hotelId <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT a.*,
                    h.numero AS habitacion_numero,
                    ar.nombre AS area_nombre,
                    ar.tipo AS area_tipo
             FROM {$this->table} a
             LEFT JOIN habitaciones h
                ON h.id = a.habitacion_id
               AND h.hotel_id = a.hotel_id
             LEFT JOIN areas_hotel ar
                ON ar.id = a.area_id
               AND ar.hotel_id = a.hotel_id
             WHERE a.id = ? AND a.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    /**
     * Listado con categoria de vencimiento y el mantenimiento abierto ligado.
     */
    public function listar(?int $hotelId = null, bool $soloActivos = false): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            return [];
        }

        $whereActivo = $soloActivos ? ' AND a.activo = 1' : '';
        $stmt = $this->db->query(
            "SELECT a.*,
                    h.numero AS habitacion_numero,
                    ar.nombre AS area_nombre,
                    ar.tipo AS area_tipo,
                    (
                        SELECT m.id
                        FROM mantenimientos_habitaciones m
                        WHERE m.hotel_id = a.hotel_id
                          AND m.activo_id = a.id
                          AND m.estado IN ('en_proceso', 'programado')
                        ORDER BY m.id DESC
                        LIMIT 1
                    ) AS mantenimiento_abierto_id
             FROM {$this->table} a
             LEFT JOIN habitaciones h
                ON h.id = a.habitacion_id
               AND h.hotel_id = a.hotel_id
             LEFT JOIN areas_hotel ar
                ON ar.id = a.area_id
               AND ar.hotel_id = a.hotel_id
             WHERE a.hotel_id = ?{$whereActivo}
             ORDER BY a.activo DESC,
                      (a.proximo_servicio IS NULL) ASC,
                      a.proximo_servicio ASC,
                      a.nombre ASC",
            [$hotelId]
        );

        $hoy = date('Y-m-d');
        $rows = $stmt ? ($stmt->fetchAll() ?: []) : [];

        foreach ($rows as &$row) {
            $proximo = (string)($row['proximo_servicio'] ?? '');
            if (!(int)($row['activo'] ?? 1)) {
                $row['vencimiento'] = 'inactivo';
            } elseif ($proximo === '') {
                $row['vencimiento'] = 'sin_programa';
            } elseif ($proximo < $hoy) {
                $row['vencimiento'] = 'vencido';
            } elseif ($proximo <= date('Y-m-d', strtotime('+7 days'))) {
                $row['vencimiento'] = 'por_vencer';
            } else {
                $row['vencimiento'] = 'al_dia';
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Activos de UNA unidad (habitacion o area), con su categoria de vencimiento.
     *
     * Alimenta dos cosas: el bloque "Activos" de la ficha del area/habitacion y el
     * selector opcional "que activo le diste servicio" del mantenimiento. Reusa
     * listar() a proposito: la clasificacion de vencimiento vive en UN solo lugar.
     *
     * @param string $entidad 'habitacion' | 'area'
     */
    public function listarPorUnidad(int $hotelId, string $entidad, int $entidadId, bool $soloActivos = true): array {
        $columnas = ['habitacion' => 'habitacion_id', 'area' => 'area_id'];
        if ($hotelId <= 0 || $entidadId <= 0 || !isset($columnas[$entidad])) {
            return [];
        }

        $columna = $columnas[$entidad];

        return array_values(array_filter(
            $this->listar($hotelId, $soloActivos),
            static function (array $fila) use ($columna, $entidadId) {
                return (int) ($fila[$columna] ?? 0) === $entidadId;
            }
        ));
    }

    /**
     * Activos vencidos (proximo_servicio <= hoy) sin mantenimiento abierto:
     * candidatos del generador preventivo. Query directa por hotel para CLI.
     */
    public function vencidosSinMantenimiento(int $hotelId): array {
        if ($hotelId <= 0) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT a.*, h.numero AS habitacion_numero
             FROM {$this->table} a
             LEFT JOIN habitaciones h
                ON h.id = a.habitacion_id
               AND h.hotel_id = a.hotel_id
             WHERE a.hotel_id = ?
               AND a.activo = 1
               AND a.proximo_servicio IS NOT NULL
               AND a.proximo_servicio <= CURDATE()
               AND NOT EXISTS (
                   SELECT 1
                   FROM mantenimientos_habitaciones m
                   WHERE m.hotel_id = a.hotel_id
                     AND m.activo_id = a.id
                     AND m.estado IN ('en_proceso', 'programado')
               )
             ORDER BY a.proximo_servicio ASC",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    /**
     * Conteo de preventivos que piden atencion (vencidos o por vencer en N
     * dias) para la burbuja del sidebar.
     */
    public function contarPorAtender(int $hotelId, int $diasAnticipacion = 7): int {
        if ($hotelId <= 0) {
            return 0;
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS n
             FROM {$this->table}
             WHERE hotel_id = ?
               AND activo = 1
               AND proximo_servicio IS NOT NULL
               AND proximo_servicio <= DATE_ADD(CURDATE(), INTERVAL ? DAY)",
            [$hotelId, max(0, $diasAnticipacion)]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return (int)($row['n'] ?? 0);
    }

    /**
     * Marca el servicio como realizado y recalcula el proximo. Se llama al
     * cerrar un mantenimiento ligado al activo.
     */
    public function registrarServicioCompletado(int $hotelId, ?int $activoId): bool {
        $activoId = (int)$activoId;
        if ($hotelId <= 0 || $activoId <= 0) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE {$this->table}
             SET ultimo_servicio = CURDATE(),
                 proximo_servicio = DATE_ADD(CURDATE(), INTERVAL periodicidad_dias DAY),
                 updated_at = NOW()
             WHERE id = ? AND hotel_id = ?",
            [$activoId, $hotelId]
        );

        return (bool)$stmt;
    }

    /**
     * Historial de servicios del activo con costo y fotos, mas costo
     * acumulado por anio ("este boiler te ha costado $4,200 en 2026").
     */
    public function historialServicios(int $activoId, ?int $hotelId = null, int $limite = 30): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        if ($activoId <= 0 || $hotelId <= 0) {
            return ['servicios' => [], 'costo_por_anio' => [], 'costo_total' => 0.0];
        }

        $limite = max(1, min(100, $limite));
        $stmt = $this->db->query(
            "SELECT m.id, m.tipo_mantenimiento, m.motivo, m.estado,
                    m.fecha_inicio, m.fecha_fin, m.costo, m.proveedor,
                    m.gasto_movimiento_id,
                    (
                        SELECT COUNT(*)
                        FROM mantenimiento_fotos f
                        WHERE f.hotel_id = m.hotel_id
                          AND f.mantenimiento_id = m.id
                    ) AS fotos_total
             FROM mantenimientos_habitaciones m
             WHERE m.hotel_id = ?
               AND m.activo_id = ?
             ORDER BY m.fecha_inicio DESC
             LIMIT {$limite}",
            [$hotelId, $activoId]
        );
        $servicios = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $stmtCosto = $this->db->query(
            "SELECT YEAR(COALESCE(m.fecha_fin, m.fecha_inicio)) AS anio,
                    COALESCE(SUM(m.costo), 0) AS total,
                    COUNT(*) AS servicios
             FROM mantenimientos_habitaciones m
             WHERE m.hotel_id = ?
               AND m.activo_id = ?
               AND m.estado = 'completado'
             GROUP BY anio
             ORDER BY anio DESC",
            [$hotelId, $activoId]
        );
        $costoPorAnio = $stmtCosto ? ($stmtCosto->fetchAll() ?: []) : [];
        $costoTotal = 0.0;
        foreach ($costoPorAnio as $anio) {
            $costoTotal += (float)($anio['total'] ?? 0);
        }

        return [
            'servicios' => $servicios,
            'costo_por_anio' => $costoPorAnio,
            'costo_total' => $costoTotal,
        ];
    }

    /**
     * Crea o actualiza un activo con datos ya validados por el controlador.
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
