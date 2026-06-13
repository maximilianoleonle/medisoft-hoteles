<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class ReporteLink extends Model {
    protected $table = 'reporte_links';
    protected $fillable = [
        'hotel_id',
        'tipo_reporte',
        'titulo',
        'descripcion',
        'archivo_path',
        'archivo_nombre',
        'mime_type',
        'tamano_bytes',
        'parametros_json',
        'token_hash',
        'token_hint',
        'estado',
        'expira_en',
        'creado_por',
        'primer_acceso_en',
        'ultimo_acceso_en',
        'accesos',
    ];

    private function hotelIdActual(): int {
        return (int) obtenerHotelIdActualCompat();
    }

    public function tablaDisponible(): bool {
        $stmt = $this->db->query(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'reporte_links'
             LIMIT 1"
        );

        return $stmt !== false && (bool) $stmt->fetch();
    }

    public function listarPorHotel($hotelId = null, array $filtros = [], int $limite = 100): array {
        $hotelId = (int) ($hotelId ?: $this->hotelIdActual());
        $limite = max(1, min(250, $limite));
        $where = ['rl.hotel_id = ?'];
        $params = [$hotelId];

        $estado = trim((string)($filtros['estado'] ?? ''));
        if ($estado !== '' && in_array($estado, ['activo', 'expirado', 'revocado'], true)) {
            if ($estado === 'expirado') {
                $where[] = "(rl.estado = 'expirado' OR (rl.estado = 'activo' AND rl.expira_en < NOW()))";
            } else {
                $where[] = 'rl.estado = ?';
                $params[] = $estado;
            }
        }

        $tipo = trim((string)($filtros['tipo_reporte'] ?? ''));
        if ($tipo !== '') {
            $where[] = 'rl.tipo_reporte = ?';
            $params[] = $tipo;
        }

        $sql = "SELECT
                    rl.*,
                    u.nombre_completo AS creado_por_nombre,
                    CASE
                        WHEN rl.estado = 'activo' AND rl.expira_en < NOW() THEN 'expirado'
                        ELSE rl.estado
                    END AS estado_calculado,
                    (
                        SELECT e.estado
                        FROM reporte_link_envios e
                        WHERE e.reporte_link_id = rl.id
                        ORDER BY e.created_at DESC
                        LIMIT 1
                    ) AS ultimo_correo_estado,
                    (
                        SELECT e.created_at
                        FROM reporte_link_envios e
                        WHERE e.reporte_link_id = rl.id
                        ORDER BY e.created_at DESC
                        LIMIT 1
                    ) AS ultimo_correo_en,
                    (
                        SELECT e.error_mensaje
                        FROM reporte_link_envios e
                        WHERE e.reporte_link_id = rl.id
                        ORDER BY e.created_at DESC
                        LIMIT 1
                    ) AS ultimo_correo_error
                FROM reporte_links rl
                LEFT JOIN usuarios u ON u.id = rl.creado_por
                WHERE " . implode(' AND ', $where) . "
                ORDER BY rl.created_at DESC
                LIMIT {$limite}";

        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumenPorHotel($hotelId = null): array {
        $hotelId = (int) ($hotelId ?: $this->hotelIdActual());
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'activo' AND expira_en >= NOW() THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN estado = 'revocado' THEN 1 ELSE 0 END) AS revocados,
                SUM(CASE WHEN estado = 'expirado' OR (estado = 'activo' AND expira_en < NOW()) THEN 1 ELSE 0 END) AS expirados,
                COALESCE(SUM(accesos), 0) AS accesos
             FROM reporte_links
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $resumen = $stmt ? $stmt->fetch() : null;

        return [
            'total' => (int)($resumen['total'] ?? 0),
            'activos' => (int)($resumen['activos'] ?? 0),
            'revocados' => (int)($resumen['revocados'] ?? 0),
            'expirados' => (int)($resumen['expirados'] ?? 0),
            'accesos' => (int)($resumen['accesos'] ?? 0),
        ];
    }

    public function tiposPorHotel($hotelId = null): array {
        $hotelId = (int) ($hotelId ?: $this->hotelIdActual());
        $stmt = $this->db->query(
            "SELECT tipo_reporte, COUNT(*) AS total
             FROM reporte_links
             WHERE hotel_id = ?
             GROUP BY tipo_reporte
             ORDER BY tipo_reporte",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array {
        $stmt = $this->db->query(
            "SELECT *
             FROM reporte_links
             WHERE id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $registro = $stmt ? $stmt->fetch() : null;
        return $registro ?: null;
    }

    public function buscarActivoPorToken(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return null;
        }

        $tokenHash = hash('sha256', strtolower($token));
        $stmt = $this->db->query(
            "SELECT *
             FROM reporte_links
             WHERE token_hash = ?
             LIMIT 1",
            [$tokenHash]
        );

        $registro = $stmt ? $stmt->fetch() : null;
        if (!$registro) {
            return null;
        }

        if (($registro['estado'] ?? '') !== 'activo') {
            return null;
        }

        if (strtotime((string)$registro['expira_en']) < time()) {
            $this->marcarExpirado((int)$registro['id']);
            return null;
        }

        return $registro;
    }

    public function crearDesdeArchivo(array $datos, ?string $token = null): array {
        $token = strtolower($token ?: bin2hex(random_bytes(32)));

        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new InvalidArgumentException('Token de reporte invalido.');
        }

        $hotelId = (int)($datos['hotel_id'] ?? $this->hotelIdActual());
        $expirationDays = function_exists('hotel_report_link_expiration_days')
            ? hotel_report_link_expiration_days($hotelId)
            : 7;

        $payload = [
            'hotel_id' => $hotelId,
            'tipo_reporte' => trim((string)($datos['tipo_reporte'] ?? 'general')),
            'titulo' => trim((string)($datos['titulo'] ?? 'Reporte PDF')),
            'descripcion' => $datos['descripcion'] ?? null,
            'archivo_path' => trim((string)($datos['archivo_path'] ?? '')),
            'archivo_nombre' => trim((string)($datos['archivo_nombre'] ?? 'reporte.pdf')),
            'mime_type' => trim((string)($datos['mime_type'] ?? 'application/pdf')),
            'tamano_bytes' => isset($datos['tamano_bytes']) ? (int)$datos['tamano_bytes'] : null,
            'parametros_json' => isset($datos['parametros']) ? json_encode($datos['parametros'], JSON_UNESCAPED_UNICODE) : ($datos['parametros_json'] ?? null),
            'token_hash' => hash('sha256', $token),
            'token_hint' => substr($token, -8),
            'estado' => 'activo',
            'expira_en' => $datos['expira_en'] ?? date('Y-m-d H:i:s', strtotime('+' . $expirationDays . ' days')),
            'creado_por' => $datos['creado_por'] ?? (function_exists('user_id') ? user_id() : null),
        ];

        $id = $this->create($payload);
        $registro = $this->find($id) ?: $payload;
        $registro['id'] = (int)$id;
        $registro['token'] = $token;

        return $registro;
    }

    public function renovarTokenParaEnvio(int $id, int $hotelId, ?string $token = null): ?array {
        $registro = $this->buscarPorIdHotel($id, $hotelId);
        if (!$registro || ($registro['estado'] ?? '') === 'revocado') {
            return null;
        }

        $token = strtolower($token ?: bin2hex(random_bytes(32)));

        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new InvalidArgumentException('Token de reporte invalido.');
        }

        $expirationDays = function_exists('hotel_report_link_expiration_days')
            ? hotel_report_link_expiration_days($hotelId)
            : 7;
        $expiraEn = date('Y-m-d H:i:s', strtotime('+' . $expirationDays . ' days'));

        $stmt = $this->db->query(
            "UPDATE reporte_links
             SET token_hash = ?,
                 token_hint = ?,
                 estado = 'activo',
                 expira_en = ?,
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?
               AND estado <> 'revocado'",
            [
                hash('sha256', $token),
                substr($token, -8),
                $expiraEn,
                $id,
                $hotelId,
            ]
        );

        if ($stmt === false) {
            return null;
        }

        $registro = $this->buscarPorIdHotel($id, $hotelId);
        if (!$registro) {
            return null;
        }

        $registro['token'] = $token;
        return $registro;
    }

    public function registrarEnvioCorreo(array $datos): bool {
        $stmt = $this->db->query(
            "INSERT INTO reporte_link_envios
                (reporte_link_id, hotel_id, canal, destinatarios, asunto, estado, error_mensaje, enviado_por, created_at)
             VALUES
                (?, ?, 'email', ?, ?, ?, ?, ?, NOW())",
            [
                (int)($datos['reporte_link_id'] ?? 0),
                (int)($datos['hotel_id'] ?? 0),
                (string)($datos['destinatarios'] ?? ''),
                (string)($datos['asunto'] ?? ''),
                (string)($datos['estado'] ?? 'fallido'),
                $datos['error_mensaje'] ?? null,
                $datos['enviado_por'] ?? null,
            ]
        );

        return $stmt !== false;
    }

    public function registrarAcceso(int $id): bool {
        $stmt = $this->db->query(
            "UPDATE reporte_links
             SET accesos = accesos + 1,
                 primer_acceso_en = COALESCE(primer_acceso_en, NOW()),
                 ultimo_acceso_en = NOW()
             WHERE id = ?",
            [$id]
        );

        return $stmt !== false;
    }

    public function revocar(int $id, int $hotelId): bool {
        $stmt = $this->db->query(
            "UPDATE reporte_links
             SET estado = 'revocado',
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?",
            [$id, $hotelId]
        );

        return $stmt !== false;
    }

    public function marcarExpirado(int $id): bool {
        $stmt = $this->db->query(
            "UPDATE reporte_links
             SET estado = 'expirado',
                 updated_at = NOW()
             WHERE id = ?
               AND estado = 'activo'",
            [$id]
        );

        return $stmt !== false;
    }
}
