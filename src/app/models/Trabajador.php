<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class Trabajador extends Model
{
    protected $table = 'trabajadores';

    public function tablaDisponible(): bool
    {
        return $this->tablaExiste('trabajadores');
    }

    public function tablasLedgerDisponibles(): array
    {
        $tablas = [
            'trabajador_pagos',
            'trabajador_anticipos',
            'trabajador_prestamos',
            'trabajador_asistencias',
            'trabajador_documentos',
        ];

        $disponibles = [];
        foreach ($tablas as $tabla) {
            $disponibles[$tabla] = $this->tablaExiste($tabla);
        }

        return $disponibles;
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $limite = max(1, min(300, $limite));
        $where = ['t.hotel_id = ?'];
        $params = [$hotelId];

        $estado = (string)($filtros['estado'] ?? 'activos');
        if ($estado === 'inactivos') {
            $where[] = "t.estado = 'inactivo'";
        } elseif ($estado === 'baja') {
            $where[] = "t.estado = 'baja'";
        } elseif ($estado !== 'todos') {
            $where[] = "t.estado = 'activo'";
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(t.nombre_completo LIKE ? OR t.identificacion LIKE ? OR t.rol_laboral LIKE ? OR t.telefono LIKE ? OR t.email LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.usuario_id,
                    t.nombre_completo,
                    t.identificacion,
                    t.rol_laboral,
                    t.telefono,
                    t.email,
                    t.estado,
                    t.fecha_alta,
                    t.fecha_baja,
                    t.salario_base,
                    t.periodicidad_pago,
                    t.created_at,
                    t.updated_at,
                    u.nombre_completo AS usuario_nombre,
                    u.nombre_usuario AS usuario_login
             FROM trabajadores t
             LEFT JOIN usuarios u
                ON u.id = t.usuario_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY FIELD(t.estado, 'activo', 'inactivo', 'baja'), t.nombre_completo ASC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumenPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'baja' => 0,
            ];
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) AS inactivos,
                    SUM(CASE WHEN estado = 'baja' THEN 1 ELSE 0 END) AS baja
             FROM trabajadores
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'activos' => (int)($row['activos'] ?? 0),
            'inactivos' => (int)($row['inactivos'] ?? 0),
            'baja' => (int)($row['baja'] ?? 0),
        ];
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.usuario_id,
                    t.nombre_completo,
                    t.identificacion,
                    t.rol_laboral,
                    t.telefono,
                    t.email,
                    t.estado,
                    t.fecha_alta,
                    t.fecha_baja,
                    t.salario_base,
                    t.periodicidad_pago,
                    t.notas,
                    t.created_by,
                    t.updated_by,
                    t.created_at,
                    t.updated_at,
                    u.nombre_completo AS usuario_nombre,
                    u.nombre_usuario AS usuario_login
             FROM trabajadores t
             LEFT JOIN usuarios u
                ON u.id = t.usuario_id
             WHERE t.id = ?
               AND t.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function resumenLedgerPorTrabajador(int $trabajadorId, int $hotelId): array
    {
        $resumen = [
            'pagos_count' => 0,
            'pagos_total' => '0.00',
            'anticipos_count' => 0,
            'anticipos_saldo' => '0.00',
            'prestamos_count' => 0,
            'prestamos_saldo' => '0.00',
            'asistencias_count' => 0,
            'documentos_count' => 0,
        ];

        if ($trabajadorId <= 0 || $hotelId <= 0) {
            return $resumen;
        }

        if ($this->tablaExiste('trabajador_pagos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'activo' THEN monto ELSE 0 END), 0) AS monto
                 FROM trabajador_pagos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['pagos_count'] = (int)($row['total'] ?? 0);
            $resumen['pagos_total'] = $this->decimal($row['monto'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_anticipos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_anticipos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['anticipos_count'] = (int)($row['total'] ?? 0);
            $resumen['anticipos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_prestamos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'vigente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_prestamos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['prestamos_count'] = (int)($row['total'] ?? 0);
            $resumen['prestamos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_asistencias')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM trabajador_asistencias
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['asistencias_count'] = (int)($row['total'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_documentos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM trabajador_documentos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?
                   AND estado = 'activo'",
                [$hotelId, $trabajadorId]
            );
            $resumen['documentos_count'] = (int)($row['total'] ?? 0);
        }

        return $resumen;
    }

    public function ultimosMovimientosPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_asistencias')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    fecha,
                    tipo,
                    hora_entrada,
                    hora_salida,
                    horas,
                    horas_extra,
                    observaciones
             FROM trabajador_asistencias
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    private function fetchOne(string $sql, array $params = []): array
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetch() ?: []) : [];
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->db->query(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
             LIMIT 1",
            [$tabla]
        );

        return $stmt !== false && (bool)$stmt->fetch();
    }

    private function decimal($value): string
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}
