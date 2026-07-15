<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

/**
 * Estado de revision de los hallazgos del Guardian (vigilancia financiera).
 *
 * Es la UNICA superficie de escritura del Guardian y vive en su tabla propia
 * (guardian_hallazgos_estado): registra cada hallazgo de patron detectado y
 * permite al dueno marcarlo revisado/resuelto. Jamas escribe en tablas de
 * dinero. Todas las consultas son tolerantes: si la migracion no se ha
 * aplicado en esta BD, la vista simplemente no muestra historico.
 */
class GuardianHallazgoEstado extends Model
{
    protected $table = 'guardian_hallazgos_estado';
    protected $fillable = [
        'hotel_id', 'clave', 'codigo_regla', 'usuario_id', 'severidad',
        'titulo', 'resumen', 'casos_conteo', 'detalle_json', 'estado',
        'detectado_en', 'ultima_vez_en', 'revisado_por', 'revisado_en',
    ];

    public const ESTADOS = ['nuevo', 'revisado', 'resuelto'];

    /** Clave estable de un hallazgo: misma regla + mismo usuario = mismo hallazgo. */
    public static function clavePara(string $codigoRegla, ?int $usuarioId): string
    {
        return $codigoRegla . ':' . (int) $usuarioId;
    }

    /**
     * Registra/actualiza los hallazgos del reporte de GuardianPatrones.
     * Devuelve mapa clave => fila ('id', 'estado', ...) para que la vista
     * pinte el estado junto a cada tarjeta. Un hallazgo 'resuelto' se reabre
     * como 'nuevo' SOLO si sus casos crecieron desde que se resolvio (algo
     * nuevo paso); si sigue igual, se respeta la decision del dueno.
     */
    public function sincronizarDesdeReporte(int $hotelId, array $reporte): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $hoy = date('Y-m-d');

        foreach ((array) ($reporte['alertas'] ?? []) as $alerta) {
            if ((int) ($alerta['conteo'] ?? 0) === 0) {
                continue;
            }
            $codigo = (string) ($alerta['codigo'] ?? '');
            $titulo = (string) ($alerta['titulo'] ?? $codigo);

            foreach ((array) ($alerta['usuarios'] ?? []) as $u) {
                $usuarioId = (int) ($u['usuario_id'] ?? 0) ?: null;
                $clave = self::clavePara($codigo, $usuarioId);
                $severidad = in_array($u['severidad'] ?? '', ['alta', 'media'], true) ? $u['severidad'] : 'media';
                $casos = (int) ($u['metrica']['casos'] ?? count((array) ($u['casos'] ?? [])));
                $resumen = mb_substr((string) ($u['valor'] ?? ''), 0, 500);
                $detalle = json_encode([
                    'contexto' => $u['contexto'] ?? '',
                    'metrica' => $u['metrica'] ?? [],
                    'casos' => $u['casos'] ?? [],
                ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

                try {
                    $this->db->query(
                        "INSERT INTO {$this->table}
                            (hotel_id, clave, codigo_regla, usuario_id, severidad, titulo,
                             resumen, casos_conteo, detalle_json, estado, detectado_en, ultima_vez_en)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'nuevo', ?, ?)
                         ON DUPLICATE KEY UPDATE
                            severidad = VALUES(severidad),
                            titulo = VALUES(titulo),
                            resumen = VALUES(resumen),
                            detalle_json = VALUES(detalle_json),
                            ultima_vez_en = VALUES(ultima_vez_en),
                            -- Si un 'resuelto' reaparece con casos nuevos se reabre y se
                            -- limpia la marca de push para poder avisar otra vez.
                            -- (notificado_en va ANTES de estado: las asignaciones se
                            -- evaluan en orden y aqui 'estado' aun es el valor viejo.)
                            notificado_en = CASE
                                WHEN estado = 'resuelto' AND VALUES(casos_conteo) > casos_conteo THEN NULL
                                ELSE notificado_en
                            END,
                            estado = CASE
                                WHEN estado = 'resuelto' AND VALUES(casos_conteo) > casos_conteo THEN 'nuevo'
                                ELSE estado
                            END,
                            casos_conteo = VALUES(casos_conteo),
                            updated_at = NOW()",
                        [$hotelId, $clave, $codigo, $usuarioId, $severidad, $titulo, $resumen, $casos, $detalle, $hoy, $hoy]
                    );
                } catch (Throwable $e) {
                    error_log('GuardianHallazgoEstado: error al sincronizar: ' . $e->getMessage());
                }
            }
        }

        return $this->mapaPorClave($hotelId);
    }

    /** Mapa clave => fila para las claves activas del hotel. */
    public function mapaPorClave(int $hotelId): array
    {
        $mapa = [];
        foreach ($this->listarPorHotel($hotelId, 'todos', 200) as $fila) {
            $mapa[(string) $fila['clave']] = $fila;
        }
        return $mapa;
    }

    /** Historico de hallazgos con nombre del usuario observado y de quien reviso. */
    public function listarPorHotel(int $hotelId, string $estado = 'todos', int $limit = 60): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $where = 'h.hotel_id = ?';
        $params = [$hotelId];
        if (in_array($estado, self::ESTADOS, true)) {
            $where .= ' AND h.estado = ?';
            $params[] = $estado;
        }
        $limit = max(1, min(200, $limit));

        try {
            $stmt = $this->db->query(
                "SELECT h.*, u.nombre_completo AS usuario_nombre, r.nombre_completo AS revisado_por_nombre
                 FROM {$this->table} h
                 LEFT JOIN usuarios u ON u.id = h.usuario_id
                 LEFT JOIN usuarios r ON r.id = h.revisado_por
                 WHERE {$where}
                 ORDER BY FIELD(h.estado, 'nuevo', 'revisado', 'resuelto'),
                          FIELD(h.severidad, 'alta', 'media'),
                          h.ultima_vez_en DESC, h.id DESC
                 LIMIT {$limit}",
                $params
            );
            return $stmt ? ($stmt->fetchAll() ?: []) : [];
        } catch (Throwable $e) {
            error_log('GuardianHallazgoEstado: error al listar: ' . $e->getMessage());
            return [];
        }
    }

    public function cambiarEstado(int $id, int $hotelId, string $estado, ?int $usuarioId): bool
    {
        if ($id <= 0 || $hotelId <= 0 || !in_array($estado, self::ESTADOS, true) || !$this->tablaDisponible()) {
            return false;
        }

        try {
            $stmt = $this->db->query(
                "UPDATE {$this->table}
                 SET estado = ?,
                     revisado_por = CASE WHEN ? IN ('revisado', 'resuelto') THEN ? ELSE revisado_por END,
                     revisado_en = CASE WHEN ? IN ('revisado', 'resuelto') THEN NOW() ELSE revisado_en END,
                     updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?",
                [$estado, $estado, $usuarioId, $estado, $id, $hotelId]
            );
            return $stmt !== false;
        } catch (Throwable $e) {
            error_log('GuardianHallazgoEstado: error al cambiar estado: ' . $e->getMessage());
            return false;
        }
    }

    /** Hallazgos de severidad alta, nuevos y aun sin push (para alerta inmediata). */
    public function listarParaNotificar(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        try {
            $stmt = $this->db->query(
                "SELECT id, codigo_regla, titulo, severidad
                 FROM {$this->table}
                 WHERE hotel_id = ?
                   AND severidad = 'alta'
                   AND estado = 'nuevo'
                   AND notificado_en IS NULL",
                [$hotelId]
            );
            return $stmt ? ($stmt->fetchAll() ?: []) : [];
        } catch (Throwable $e) {
            error_log('GuardianHallazgoEstado: error al listar para notificar: ' . $e->getMessage());
            return [];
        }
    }

    public function marcarNotificados(array $ids, int $hotelId): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids) || $hotelId <= 0 || !$this->tablaDisponible()) {
            return;
        }

        try {
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $this->db->query(
                "UPDATE {$this->table} SET notificado_en = NOW(), updated_at = NOW()
                 WHERE hotel_id = ? AND id IN ({$placeholders})",
                array_merge([$hotelId], $ids)
            );
        } catch (Throwable $e) {
            error_log('GuardianHallazgoEstado: error al marcar notificados: ' . $e->getMessage());
        }
    }

    /** Hallazgos detectados dentro de un rango (para el digest semanal). */
    public function contarDetectadosEntre(int $hotelId, string $desde, string $hasta): int
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return 0;
        }

        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM {$this->table}
                 WHERE hotel_id = ? AND detectado_en >= ? AND detectado_en <= ?",
                [$hotelId, $desde, $hasta]
            );
            return $stmt ? (int) $stmt->fetchColumn() : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }

    /** Conteos por estado para chips del historico. */
    public function conteos(int $hotelId): array
    {
        $base = ['nuevo' => 0, 'revisado' => 0, 'resuelto' => 0];
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return $base;
        }

        try {
            $stmt = $this->db->query(
                "SELECT estado, COUNT(*) AS total FROM {$this->table} WHERE hotel_id = ? GROUP BY estado",
                [$hotelId]
            );
            foreach (($stmt ? $stmt->fetchAll() : []) ?: [] as $fila) {
                if (isset($base[$fila['estado']])) {
                    $base[$fila['estado']] = (int) $fila['total'];
                }
            }
        } catch (Throwable $e) {
            error_log('GuardianHallazgoEstado: error en conteos: ' . $e->getMessage());
        }

        return $base;
    }

    private function tablaDisponible(): bool
    {
        static $disponible = null;
        if ($disponible !== null) {
            return $disponible;
        }

        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$this->table]
            );
            $disponible = $stmt && (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            $disponible = false;
        }

        return $disponible;
    }
}
