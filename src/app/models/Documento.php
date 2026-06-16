<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class Documento extends Model
{
    protected $table = 'documentos';

    private const ENTIDAD_TIPOS = [
        'proveedor',
        'compra',
        'cuenta_por_pagar',
        'huesped',
        'reservacion',
    ];

    public function tablasDisponibles(): bool
    {
        foreach (['documento_tipos', 'documentos', 'documento_entidades'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function tiposActivosPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('documento_tipos')) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    clave,
                    nombre,
                    descripcion,
                    mime_permitidos,
                    max_size_mb
             FROM documento_tipos
             WHERE activo = 1
               AND (hotel_id IS NULL OR hotel_id = ?)
             ORDER BY hotel_id IS NULL DESC, nombre ASC",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumenPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('documentos')) {
            return $this->resumenVacio();
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado = 'archivado' THEN 1 ELSE 0 END) AS archivados,
                    SUM(CASE WHEN estado = 'eliminado' THEN 1 ELSE 0 END) AS eliminados,
                    COALESCE(SUM(size_bytes), 0) AS bytes_total
             FROM documentos
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'activos' => (int)($row['activos'] ?? 0),
            'archivados' => (int)($row['archivados'] ?? 0),
            'eliminados' => (int)($row['eliminados'] ?? 0),
            'bytes_total' => (int)($row['bytes_total'] ?? 0),
        ];
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        if ($hotelId <= 0 || !$this->tablasDisponibles()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $where = ['d.hotel_id = ?'];
        $params = [$hotelId, $hotelId];

        $estado = $this->normalizarEstado($filtros['estado'] ?? 'todos');
        if ($estado !== 'todos') {
            $where[] = 'd.estado = ?';
            $params[] = $estado;
        }

        $tipoId = (int)($filtros['documento_tipo_id'] ?? 0);
        if ($tipoId > 0) {
            $where[] = 'd.documento_tipo_id = ?';
            $params[] = $tipoId;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(d.titulo LIKE ? OR d.nombre_original LIKE ? OR d.descripcion LIKE ? OR d.mime_type LIKE ? OR dt.nombre LIKE ? OR dt.clave LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT d.id,
                    d.hotel_id,
                    d.documento_tipo_id,
                    d.nombre_original,
                    d.mime_type,
                    d.size_bytes,
                    d.titulo,
                    d.descripcion,
                    d.etiquetas,
                    d.estado,
                    d.subido_por_usuario_id,
                    d.created_at,
                    d.updated_at,
                    dt.clave AS tipo_clave,
                    dt.nombre AS tipo_nombre,
                    u.nombre_completo AS subido_por_nombre,
                    COALESCE(rel.entidades_count, 0) AS entidades_count,
                    rel.entidades_resumen
             FROM documentos d
             LEFT JOIN documento_tipos dt
                ON dt.id = d.documento_tipo_id
               AND (dt.hotel_id IS NULL OR dt.hotel_id = d.hotel_id)
             LEFT JOIN usuarios u
                ON u.id = d.subido_por_usuario_id
             LEFT JOIN (
                 SELECT hotel_id,
                        documento_id,
                        COUNT(*) AS entidades_count,
                        GROUP_CONCAT(CONCAT(entidad_tipo, '#', entidad_id) ORDER BY id SEPARATOR ', ') AS entidades_resumen
                 FROM documento_entidades
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, documento_id
             ) rel
                ON rel.hotel_id = d.hotel_id
               AND rel.documento_id = d.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY d.created_at DESC, d.id DESC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaExiste('documentos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT d.id,
                    d.hotel_id,
                    d.documento_tipo_id,
                    d.nombre_original,
                    d.mime_type,
                    d.size_bytes,
                    d.titulo,
                    d.descripcion,
                    d.etiquetas,
                    d.estado,
                    d.subido_por_usuario_id,
                    d.created_at,
                    d.updated_at,
                    dt.clave AS tipo_clave,
                    dt.nombre AS tipo_nombre,
                    dt.descripcion AS tipo_descripcion,
                    u.nombre_completo AS subido_por_nombre
             FROM documentos d
             LEFT JOIN documento_tipos dt
                ON dt.id = d.documento_tipo_id
               AND (dt.hotel_id IS NULL OR dt.hotel_id = d.hotel_id)
             LEFT JOIN usuarios u
                ON u.id = d.subido_por_usuario_id
             WHERE d.id = ?
               AND d.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function documentosPorEntidad(int $hotelId, string $entidadTipo, int $entidadId, int $limite = 100): array
    {
        $entidadTipo = $this->normalizarEntidadTipo($entidadTipo);
        if ($hotelId <= 0 || $entidadTipo === null || $entidadId <= 0 || !$this->tablasDisponibles()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $stmt = $this->db->query(
            "SELECT d.id,
                    d.hotel_id,
                    d.documento_tipo_id,
                    d.nombre_original,
                    d.mime_type,
                    d.size_bytes,
                    d.titulo,
                    d.descripcion,
                    d.etiquetas,
                    d.estado,
                    d.subido_por_usuario_id,
                    d.created_at,
                    d.updated_at,
                    dt.clave AS tipo_clave,
                    dt.nombre AS tipo_nombre,
                    u.nombre_completo AS subido_por_nombre,
                    de.relacion,
                    1 AS entidades_count,
                    CONCAT(de.entidad_tipo, '#', de.entidad_id) AS entidades_resumen
             FROM documento_entidades de
             INNER JOIN documentos d
                ON d.id = de.documento_id
               AND d.hotel_id = de.hotel_id
             LEFT JOIN documento_tipos dt
                ON dt.id = d.documento_tipo_id
               AND (dt.hotel_id IS NULL OR dt.hotel_id = d.hotel_id)
             LEFT JOIN usuarios u
                ON u.id = d.subido_por_usuario_id
             WHERE de.hotel_id = ?
               AND de.entidad_tipo = ?
               AND de.entidad_id = ?
             ORDER BY d.created_at DESC, d.id DESC
             LIMIT {$limite}",
            [$hotelId, $entidadTipo, $entidadId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function entidadesPorDocumento(int $documentoId, int $hotelId): array
    {
        if ($documentoId <= 0 || $hotelId <= 0 || !$this->tablaExiste('documento_entidades')) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    documento_id,
                    entidad_tipo,
                    entidad_id,
                    relacion,
                    created_at
             FROM documento_entidades
             WHERE documento_id = ?
               AND hotel_id = ?
             ORDER BY entidad_tipo ASC, entidad_id ASC, id ASC",
            [$documentoId, $hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function normalizarEntidadTipo(?string $entidadTipo): ?string
    {
        $entidadTipo = trim((string)$entidadTipo);
        return in_array($entidadTipo, self::ENTIDAD_TIPOS, true) ? $entidadTipo : null;
    }

    public function entidadTiposPermitidos(): array
    {
        return self::ENTIDAD_TIPOS;
    }

    private function resumenVacio(): array
    {
        return [
            'total' => 0,
            'activos' => 0,
            'archivados' => 0,
            'eliminados' => 0,
            'bytes_total' => 0,
        ];
    }

    private function normalizarEstado($value): string
    {
        $estado = (string)($value ?? 'todos');
        return in_array($estado, ['activo', 'archivado', 'eliminado', 'todos'], true)
            ? $estado
            : 'todos';
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
}
