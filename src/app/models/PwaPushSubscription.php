<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class PwaPushSubscription extends Model {
    protected $table = 'pwa_push_subscriptions';
    protected $fillable = [
        'hotel_id',
        'usuario_id',
        'endpoint_hash',
        'endpoint',
        'p256dh',
        'auth',
        'navegador',
        'activo',
        'last_seen_at',
        'revoked_at',
    ];

    public function tablaDisponible(): bool {
        $stmt = $this->db->query(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'pwa_push_subscriptions'
             LIMIT 1"
        );

        return $stmt !== false && (bool) $stmt->fetch();
    }

    public function guardarSuscripcion(int $hotelId, ?int $usuarioId, array $subscription, string $userAgent = ''): bool {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $endpoint = trim((string)($subscription['endpoint'] ?? ''));
        $p256dh = trim((string)($subscription['keys']['p256dh'] ?? ''));
        $auth = trim((string)($subscription['keys']['auth'] ?? ''));

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return false;
        }

        $stmt = $this->db->query(
            "INSERT INTO pwa_push_subscriptions
                (hotel_id, usuario_id, endpoint_hash, endpoint, p256dh, auth, navegador, activo, last_seen_at, revoked_at, created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NULL, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                usuario_id = VALUES(usuario_id),
                endpoint = VALUES(endpoint),
                p256dh = VALUES(p256dh),
                auth = VALUES(auth),
                navegador = VALUES(navegador),
                activo = 1,
                last_seen_at = NOW(),
                revoked_at = NULL,
                updated_at = NOW()",
            [
                $hotelId,
                $usuarioId ?: null,
                hash('sha256', $endpoint),
                $endpoint,
                $p256dh,
                $auth,
                $this->limitarTexto($userAgent, 180),
            ]
        );

        return $stmt !== false;
    }

    public function desactivarSuscripcion(int $hotelId, string $endpoint): bool {
        if ($hotelId <= 0 || trim($endpoint) === '' || !$this->tablaDisponible()) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE pwa_push_subscriptions
             SET activo = 0,
                 revoked_at = NOW(),
                 updated_at = NOW()
             WHERE hotel_id = ?
               AND endpoint_hash = ?",
            [$hotelId, hash('sha256', trim($endpoint))]
        );

        return $stmt !== false;
    }

    public function desactivarPorId(int $id): bool {
        if ($id <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE pwa_push_subscriptions
             SET activo = 0,
                 revoked_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?",
            [$id]
        );

        return $stmt !== false;
    }

    public function desactivarPorIdHotel(int $id, int $hotelId): bool {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE pwa_push_subscriptions
             SET activo = 0,
                 revoked_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?",
            [$id, $hotelId]
        );

        return $stmt !== false;
    }

    public function listarPorHotel(int $hotelId, int $limite = 30): array {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $limite = max(1, min(100, $limite));
        $stmt = $this->db->query(
            "SELECT *
             FROM pwa_push_subscriptions
             WHERE hotel_id = ?
             ORDER BY activo DESC, last_seen_at DESC, created_at DESC, id DESC
             LIMIT {$limite}",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function listarActivasPorHotel(int $hotelId): array {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT *
             FROM pwa_push_subscriptions
             WHERE hotel_id = ?
               AND activo = 1
             ORDER BY last_seen_at DESC, id DESC",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function listarActivasPorHotelRoles(int $hotelId, array $roles): array {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $roles = array_values(array_unique(array_filter(array_map(static function ($rol) {
            $rol = strtolower(trim((string)$rol));
            return preg_match('/^[a-z_]+$/', $rol) ? $rol : null;
        }, $roles))));

        if (empty($roles)) {
            return $this->listarActivasPorHotel($hotelId);
        }

        $placeholders = implode(', ', array_fill(0, count($roles), '?'));
        $params = array_merge([$hotelId], $roles, $roles);

        $stmt = $this->db->query(
            "SELECT s.*
             FROM pwa_push_subscriptions s
             LEFT JOIN hotel_usuarios hu
               ON hu.hotel_id = s.hotel_id
              AND hu.usuario_id = s.usuario_id
             LEFT JOIN usuarios u
               ON u.id = s.usuario_id
             WHERE s.hotel_id = ?
               AND s.activo = 1
               AND u.activo = 1
               AND (
                    (hu.id IS NOT NULL AND hu.activo = 1 AND hu.rol IN ({$placeholders}))
                    OR (hu.id IS NULL AND u.rol IN ({$placeholders}))
               )
             ORDER BY s.last_seen_at DESC, s.id DESC",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    private function limitarTexto(string $texto, int $max): string {
        $texto = trim(preg_replace('/\s+/', ' ', $texto));

        if ($texto === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($texto, 'UTF-8') > $max
                ? mb_substr($texto, 0, $max, 'UTF-8')
                : $texto;
        }

        return strlen($texto) > $max ? substr($texto, 0, $max) : $texto;
    }
}
