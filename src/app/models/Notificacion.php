<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class Notificacion extends Model {
    protected $table = 'notificaciones';
    protected $fillable = [
        'hotel_id',
        'usuario_id',
        'rol_destino',
        'modulo',
        'tipo',
        'severidad',
        'titulo',
        'mensaje',
        'entidad_tipo',
        'entidad_id',
        'url',
        'estado',
        'dedupe_key',
        'leida_en',
        'resuelta_en',
        'descartada_en',
        'creada_por',
    ];

    private const SEVERIDADES = ['info', 'media', 'alta', 'critica'];
    private const ESTADOS = ['nueva', 'leida', 'resuelta', 'descartada'];
    private $ultimoEventoFueCreado = false;

    public function tablaDisponible(): bool {
        // Cache por request: cada metodo del modelo consulta esto y llegaban
        // 12 hits a information_schema por pagina.
        static $disponible = null;

        if ($disponible !== null) {
            return $disponible;
        }

        $stmt = $this->db->query(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'notificaciones'
             LIMIT 1"
        );

        return $disponible = ($stmt !== false && (bool) $stmt->fetch());
    }

    public function contarNoLeidas(int $hotelId, ?string $rolUsuario = null, ?int $usuarioId = null): int {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return 0;
        }

        $where = [
            'hotel_id = ?',
            "estado IN ('nueva', 'leida')",
        ];
        $params = [$hotelId];
        $this->aplicarFiltroVisibilidadRol($where, $params, $rolUsuario, $usuarioId);

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total
             FROM notificaciones
             WHERE " . implode(' AND ', $where),
            $params
        );

        $row = $stmt ? $stmt->fetch() : null;
        return (int)($row['total'] ?? 0);
    }

    public function resumenPorHotel(int $hotelId, ?string $rolUsuario = null, ?int $usuarioId = null): array {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [
                'total' => 0,
                'pendientes' => 0,
                'nuevas' => 0,
                'prioritarias' => 0,
                'resueltas' => 0,
                'historial' => 0,
                'hoy' => 0,
            ];
        }

        $where = ['hotel_id = ?'];
        $params = [$hotelId];
        $this->aplicarFiltroVisibilidadRol($where, $params, $rolUsuario, $usuarioId);

        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado IN ('nueva', 'leida') THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = 'nueva' THEN 1 ELSE 0 END) AS nuevas,
                SUM(CASE WHEN estado IN ('nueva', 'leida') AND severidad IN ('alta', 'critica') THEN 1 ELSE 0 END) AS prioritarias,
                SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) AS resueltas,
                SUM(CASE WHEN estado IN ('resuelta', 'descartada') THEN 1 ELSE 0 END) AS historial,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS hoy
             FROM notificaciones
             WHERE " . implode(' AND ', $where),
            $params
        );

        $row = $stmt ? $stmt->fetch() : null;

        return [
            'total' => (int)($row['total'] ?? 0),
            'pendientes' => (int)($row['pendientes'] ?? 0),
            'nuevas' => (int)($row['nuevas'] ?? 0),
            'prioritarias' => (int)($row['prioritarias'] ?? 0),
            'resueltas' => (int)($row['resueltas'] ?? 0),
            'historial' => (int)($row['historial'] ?? 0),
            'hoy' => (int)($row['hoy'] ?? 0),
        ];
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 80): array {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $where = ['hotel_id = ?'];
        $params = [$hotelId];

        $estado = trim((string)($filtros['estado'] ?? ''));
        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $where[] = 'estado = ?';
            $params[] = $estado;
        } elseif ($estado === 'activas') {
            $where[] = "estado IN ('nueva', 'leida')";
        }

        $modulo = trim((string)($filtros['modulo'] ?? ''));
        if ($modulo !== '') {
            $where[] = 'modulo = ?';
            $params[] = $modulo;
        }

        $severidad = trim((string)($filtros['severidad'] ?? ''));
        if ($severidad !== '' && in_array($severidad, self::SEVERIDADES, true)) {
            $where[] = 'severidad = ?';
            $params[] = $severidad;
        }

        $this->aplicarFiltroVisibilidadRol(
            $where,
            $params,
            $filtros['rol_usuario'] ?? null,
            isset($filtros['usuario_id']) ? (int)$filtros['usuario_id'] : null
        );

        $sql = "SELECT *
                FROM notificaciones
                WHERE " . implode(' AND ', $where) . "
                ORDER BY
                    CASE estado WHEN 'nueva' THEN 1 WHEN 'leida' THEN 2 WHEN 'resuelta' THEN 3 ELSE 4 END,
                    CASE severidad WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END,
                    created_at DESC
                LIMIT {$limite}";

        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function modulosPorHotel(int $hotelId, ?string $rolUsuario = null, ?int $usuarioId = null): array {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $where = ['hotel_id = ?'];
        $params = [$hotelId];
        $this->aplicarFiltroVisibilidadRol($where, $params, $rolUsuario, $usuarioId);

        $stmt = $this->db->query(
            "SELECT modulo, COUNT(*) AS total
             FROM notificaciones
             WHERE " . implode(' AND ', $where) . "
             GROUP BY modulo
             ORDER BY modulo",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array {
        $stmt = $this->db->query(
            "SELECT *
             FROM notificaciones
             WHERE id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function crearEvento(array $datos): ?int {
        $this->ultimoEventoFueCreado = false;

        if (!$this->tablaDisponible()) {
            return null;
        }

        $hotelId = (int)($datos['hotel_id'] ?? 0);
        if ($hotelId <= 0) {
            return null;
        }

        $tipoSolicitado = $this->limpiarTexto($datos['tipo'] ?? 'evento', 80) ?: 'evento';
        $dedupeKey = $this->limpiarTexto($datos['dedupe_key'] ?? '', 160);
        if ($dedupeKey !== '') {
            $existente = $this->buscarPorDedupe($hotelId, $dedupeKey);
            if ($existente) {
                if ($this->debeRecrearReglaAutomatica($tipoSolicitado, $existente)) {
                    if (!$this->liberarDedupeResuelto((int)$existente['id'], $hotelId)) {
                        return (int)$existente['id'];
                    }
                } else {
                    return (int)$existente['id'];
                }
            }
        }

        $severidad = (string)($datos['severidad'] ?? 'info');
        $estado = (string)($datos['estado'] ?? 'nueva');

        $payload = [
            'hotel_id' => $hotelId,
            'usuario_id' => isset($datos['usuario_id']) ? (int)$datos['usuario_id'] : null,
            'rol_destino' => $this->limpiarTexto($datos['rol_destino'] ?? '', 40) ?: null,
            'modulo' => $this->limpiarTexto($datos['modulo'] ?? 'sistema', 40) ?: 'sistema',
            'tipo' => $tipoSolicitado,
            'severidad' => in_array($severidad, self::SEVERIDADES, true) ? $severidad : 'info',
            'titulo' => $this->limpiarTexto($datos['titulo'] ?? 'Notificacion', 180) ?: 'Notificacion',
            'mensaje' => $this->limpiarTexto($datos['mensaje'] ?? '', 500) ?: 'Evento registrado en el sistema.',
            'entidad_tipo' => $this->limpiarTexto($datos['entidad_tipo'] ?? '', 80) ?: null,
            'entidad_id' => isset($datos['entidad_id']) ? (int)$datos['entidad_id'] : null,
            'url' => $this->normalizarUrl($datos['url'] ?? null),
            'estado' => in_array($estado, self::ESTADOS, true) ? $estado : 'nueva',
            'dedupe_key' => $dedupeKey ?: null,
            'creada_por' => isset($datos['creada_por']) ? (int)$datos['creada_por'] : (function_exists('user_id') ? user_id() : null),
        ];

        $id = $this->create($payload);
        $this->ultimoEventoFueCreado = (bool)$id;
        return $id ? (int)$id : null;
    }

    public function ultimoEventoFueCreado(): bool {
        return $this->ultimoEventoFueCreado;
    }

    public function cambiarEstado(int $id, int $hotelId, string $estado): bool {
        if (!in_array($estado, self::ESTADOS, true)) {
            return false;
        }

        $fields = ['estado = ?', 'updated_at = NOW()'];
        $params = [$estado];

        if ($estado === 'leida') {
            $fields[] = 'leida_en = COALESCE(leida_en, NOW())';
        } elseif ($estado === 'resuelta') {
            $fields[] = 'leida_en = COALESCE(leida_en, NOW())';
            $fields[] = 'resuelta_en = NOW()';
        } elseif ($estado === 'descartada') {
            $fields[] = 'leida_en = COALESCE(leida_en, NOW())';
            $fields[] = 'descartada_en = NOW()';
        }

        $params[] = $id;
        $params[] = $hotelId;

        $stmt = $this->db->query(
            "UPDATE notificaciones
             SET " . implode(', ', $fields) . "
             WHERE id = ?
               AND hotel_id = ?",
            $params
        );

        return $stmt !== false;
    }

    public function marcarLeidaAlAbrir(int $id, int $hotelId): bool {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE notificaciones
             SET estado = 'leida',
                 leida_en = COALESCE(leida_en, NOW()),
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?
               AND estado = 'nueva'",
            [$id, $hotelId]
        );

        return $stmt !== false;
    }

    public function marcarTodasLeidas(int $hotelId): bool {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE notificaciones
             SET estado = 'leida',
                 leida_en = COALESCE(leida_en, NOW()),
                 updated_at = NOW()
             WHERE hotel_id = ?
               AND estado = 'nueva'",
            [$hotelId]
        );

        return $stmt !== false;
    }

    public function resolverAutomaticasInactivas(int $hotelId, array $tiposActivos = []): int {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return 0;
        }

        $tiposActivos = array_values(array_unique(array_filter(array_map(static function ($tipo) {
            $tipo = trim((string)$tipo);
            return preg_match('/^regla_[a-z0-9_]+$/', $tipo) ? $tipo : null;
        }, $tiposActivos))));

        $where = [
            'hotel_id = ?',
            "estado IN ('nueva', 'leida')",
            "LEFT(tipo, 6) = 'regla_'",
        ];
        $params = [$hotelId];

        if (!empty($tiposActivos)) {
            $placeholders = implode(', ', array_fill(0, count($tiposActivos), '?'));
            $where[] = "tipo NOT IN ({$placeholders})";
            $params = array_merge($params, $tiposActivos);
        }

        $stmt = $this->db->query(
            "UPDATE notificaciones
             SET estado = 'resuelta',
                 leida_en = COALESCE(leida_en, NOW()),
                 resuelta_en = NOW(),
                 dedupe_key = CASE
                    WHEN dedupe_key IS NULL OR dedupe_key LIKE '%.resuelta.%' THEN dedupe_key
                    ELSE LEFT(CONCAT(dedupe_key, '.resuelta.', id), 160)
                 END,
                 updated_at = NOW()
             WHERE " . implode(' AND ', $where),
            $params
        );

        return $stmt ? (int)$stmt->rowCount() : 0;
    }

    private function buscarPorDedupe(int $hotelId, string $dedupeKey): ?array {
        $stmt = $this->db->query(
            "SELECT id, estado, tipo, dedupe_key
             FROM notificaciones
             WHERE hotel_id = ?
               AND dedupe_key = ?
             LIMIT 1",
            [$hotelId, $dedupeKey]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    private function debeRecrearReglaAutomatica(string $tipo, array $existente): bool {
        return strpos($tipo, 'regla_') === 0
            && ($existente['estado'] ?? '') === 'resuelta';
    }

    private function liberarDedupeResuelto(int $id, int $hotelId): bool {
        $stmt = $this->db->query(
            "UPDATE notificaciones
             SET dedupe_key = CASE
                    WHEN dedupe_key IS NULL OR dedupe_key LIKE '%.resuelta.%' THEN dedupe_key
                    ELSE LEFT(CONCAT(dedupe_key, '.resuelta.', id), 160)
                 END,
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?
               AND estado = 'resuelta'",
            [$id, $hotelId]
        );

        return $stmt !== false;
    }

    public function visibleParaUsuario(array $notificacion, ?string $rolUsuario = null, ?int $usuarioId = null): bool {
        if ($usuarioId && (int)($notificacion['usuario_id'] ?? 0) === (int)$usuarioId) {
            return true;
        }

        $rol = $this->normalizarRol($rolUsuario);
        if ($rol === '' || in_array($rol, ['superadmin', 'propietario', 'administrador'], true)) {
            return true;
        }

        return in_array($rol, $this->rolesDestinoParaNotificacion($notificacion), true);
    }

    private function aplicarFiltroVisibilidadRol(array &$where, array &$params, ?string $rolUsuario = null, ?int $usuarioId = null): void {
        $rol = $this->normalizarRol($rolUsuario);
        if ($rol === '' || in_array($rol, ['superadmin', 'propietario', 'administrador'], true)) {
            return;
        }

        $or = [];
        if ($usuarioId && $usuarioId > 0) {
            $or[] = 'usuario_id = ?';
            $params[] = (int)$usuarioId;
        }

        if ($rol === 'gerente') {
            $or[] = "(
                rol_destino IS NULL
                AND (
                    severidad = 'critica'
                    OR modulo IN ('caja', 'facturacion', 'reportes')
                    OR modulo NOT IN ('reservaciones', 'habitaciones')
                )
            )";
            $or[] = $this->rolDestinoSql(['gerente', 'gerencia', 'administrador', 'propietario', 'superadmin'], $params);
        } elseif ($rol === 'recepcionista') {
            $or[] = "(
                rol_destino IS NULL
                AND (
                    modulo IN ('reservaciones', 'habitaciones')
                    OR LOWER(tipo) LIKE '%check-in%'
                    OR LOWER(tipo) LIKE '%check_in%'
                    OR LOWER(tipo) LIKE '%check-out%'
                    OR LOWER(tipo) LIKE '%check_out%'
                )
            )";
            $or[] = $this->rolDestinoSql(['recepcionista', 'recepcion'], $params);
        } elseif ($rol === 'limpieza') {
            $or[] = "(
                rol_destino IS NULL
                AND modulo = 'habitaciones'
                AND (
                    LOWER(tipo) LIKE '%limpieza%'
                    OR LOWER(titulo) LIKE '%limpieza%'
                    OR LOWER(mensaje) LIKE '%limpieza%'
                )
            )";
            $or[] = $this->rolDestinoSql(['limpieza', 'housekeeping'], $params);
        } elseif ($rol === 'mantenimiento') {
            $or[] = "(
                rol_destino IS NULL
                AND modulo = 'habitaciones'
                AND (
                    LOWER(tipo) LIKE '%mantenimiento%'
                    OR LOWER(titulo) LIKE '%mantenimiento%'
                    OR LOWER(mensaje) LIKE '%mantenimiento%'
                )
            )";
            $or[] = $this->rolDestinoSql(['mantenimiento'], $params);
        }

        if (!empty($or)) {
            $where[] = '(' . implode(' OR ', array_filter($or)) . ')';
        }
    }

    private function rolDestinoSql(array $roles, array &$params): string {
        $partes = [];
        foreach ($roles as $rol) {
            $rol = $this->normalizarRol($rol);
            if ($rol === '') {
                continue;
            }

            $partes[] = "LOWER(COALESCE(rol_destino, '')) LIKE ?";
            $params[] = '%' . $rol . '%';
        }

        return '(' . implode(' OR ', $partes ?: ['1 = 0']) . ')';
    }

    private function rolesDestinoParaNotificacion(array $notificacion): array {
        $rolesConfigurados = $this->parseRolesDestino($notificacion['rol_destino'] ?? '');
        if (!empty($rolesConfigurados)) {
            return $this->expandirRolesDestino($rolesConfigurados);
        }

        $modulo = strtolower((string)($notificacion['modulo'] ?? 'sistema'));
        $tipo = strtolower((string)($notificacion['tipo'] ?? ''));
        $severidad = strtolower((string)($notificacion['severidad'] ?? 'info'));
        $texto = strtolower(trim($tipo . ' ' . (string)($notificacion['titulo'] ?? '') . ' ' . (string)($notificacion['mensaje'] ?? '')));

        if ($severidad === 'critica' || in_array($modulo, ['caja', 'facturacion', 'reportes'], true)) {
            return $this->expandirRolesDestino(['gerente']);
        }

        if ($modulo === 'habitaciones') {
            if (strpos($texto, 'mantenimiento') !== false) {
                return $this->expandirRolesDestino(['mantenimiento']);
            }

            if (strpos($texto, 'limpieza') !== false) {
                return $this->expandirRolesDestino(['limpieza']);
            }

            return $this->expandirRolesDestino(['recepcionista']);
        }

        if ($modulo === 'reservaciones'
            || strpos($texto, 'check-in') !== false
            || strpos($texto, 'check_in') !== false
            || strpos($texto, 'check-out') !== false
            || strpos($texto, 'check_out') !== false) {
            return $this->expandirRolesDestino(['recepcionista']);
        }

        return $this->expandirRolesDestino(['gerente']);
    }

    private function parseRolesDestino($valor): array {
        $roles = is_array($valor)
            ? $valor
            : preg_split('/[,;\s]+/', (string)$valor, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(array_map(function ($rol) {
            return $this->normalizarRol($rol);
        }, $roles ?: []))));
    }

    private function expandirRolesDestino(array $roles): array {
        $expandir = [
            'gerente' => ['superadmin', 'propietario', 'gerente', 'administrador'],
            'administrador' => ['superadmin', 'propietario', 'gerente', 'administrador'],
            'propietario' => ['superadmin', 'propietario', 'gerente', 'administrador'],
            'superadmin' => ['superadmin', 'propietario', 'gerente', 'administrador'],
            'recepcionista' => ['recepcionista', 'administrador'],
            'limpieza' => ['limpieza', 'recepcionista', 'administrador'],
            'mantenimiento' => ['mantenimiento', 'recepcionista', 'administrador'],
        ];

        $resultado = [];
        foreach ($roles as $rol) {
            foreach (($expandir[$rol] ?? [$rol]) as $rolExpandido) {
                $rolExpandido = $this->normalizarRol($rolExpandido);
                if ($rolExpandido !== '') {
                    $resultado[] = $rolExpandido;
                }
            }
        }

        return array_values(array_unique($resultado));
    }

    private function normalizarRol($rol): string {
        $rol = strtolower(trim((string)$rol));
        if (function_exists('iconv')) {
            $rolAscii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $rol);
            if ($rolAscii !== false && $rolAscii !== '') {
                $rol = strtolower(trim($rolAscii));
            }
        }

        $rol = str_replace([' ', '-'], '_', $rol);
        $aliases = [
            'admin' => 'administrador',
            'administracion' => 'administrador',
            'recepcion' => 'recepcionista',
            'gerencia' => 'gerente',
            'mantenimiento_habitaciones' => 'mantenimiento',
            'housekeeping' => 'limpieza',
        ];
        $rol = $aliases[$rol] ?? $rol;

        return preg_match('/^[a-z_]+$/', $rol) ? $rol : '';
    }

    private function limpiarTexto($valor, int $limite): string {
        $texto = trim((string)($valor ?? ''));
        $texto = preg_replace('/\s+/', ' ', $texto);
        return mb_substr($texto, 0, $limite, 'UTF-8');
    }

    private function normalizarUrl($url): ?string {
        $url = trim((string)($url ?? ''));
        if ($url === '' || strpos($url, '://') !== false || strpos($url, '..') !== false) {
            return null;
        }

        return ltrim(mb_substr($url, 0, 255, 'UTF-8'), '/');
    }
}
