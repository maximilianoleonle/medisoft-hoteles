<?php

require_once __DIR__ . '/../../core/Database.php';

/**
 * LoginRateLimiter (SEC-001)
 *
 * Contador de intentos fallidos de login persistido en DB (tabla
 * login_intentos), no reiniciable con una cookie/sesion nueva.
 *
 * Dos alcances por cada fallo:
 *  - Especifico: ip + usuario + hotel_slug → 5 intentos / bloqueo 15 min
 *    (misma UX que el contador de sesion que reemplaza).
 *  - Global por IP: 20 intentos / bloqueo 15 min, frena credential stuffing
 *    contra muchos usuarios desde la misma IP.
 *
 * La ventana de conteo es de 15 minutos: si el ultimo intento fallido es mas
 * viejo, el contador se reinicia. Fail-open: si la tabla no existe o la DB
 * falla, el login NO se bloquea (se registra en error_log); la proteccion es
 * de mejor esfuerzo y nunca debe dejar fuera a un hotel completo.
 */
class LoginRateLimiter
{
    private const MAX_INTENTOS_ESPECIFICO = 5;
    private const MAX_INTENTOS_IP = 20;
    private const VENTANA_SEGUNDOS = 900;
    private const BLOQUEO_SEGUNDOS = 900;
    private const RETENCION_SEGUNDOS = 86400;

    private $pdo;
    private $disponible;

    public function __construct(?Database $db = null)
    {
        try {
            $database = $db ?: Database::getInstance();
            $this->pdo = $database->getConnection();
            $this->disponible = $this->tablaExiste();
        } catch (Throwable $e) {
            error_log('[LoginRateLimiter] Sin DB, rate limit inactivo: ' . $e->getMessage());
            $this->pdo = null;
            $this->disponible = false;
        }
    }

    /**
     * Segundos de bloqueo restantes para esta combinacion, o 0 si puede intentar.
     */
    public function segundosBloqueado(string $ip, string $nombreUsuario, ?string $hotelSlug): int
    {
        if (!$this->disponible) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT MAX(TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta)) AS restante
                 FROM login_intentos
                 WHERE clave IN (?, ?) AND bloqueado_hasta > NOW()'
            );
            $stmt->execute([
                $this->claveEspecifica($ip, $nombreUsuario, $hotelSlug),
                $this->claveIp($ip),
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return max(0, (int)($row['restante'] ?? 0));
        } catch (Throwable $e) {
            error_log('[LoginRateLimiter] Error consultando bloqueo: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Registra un intento fallido en ambos alcances.
     * Devuelve ['bloqueado' => bool, 'restantes' => int] del alcance especifico.
     */
    public function registrarFallo(string $ip, string $nombreUsuario, ?string $hotelSlug): array
    {
        if (!$this->disponible) {
            return ['bloqueado' => false, 'restantes' => self::MAX_INTENTOS_ESPECIFICO - 1];
        }

        try {
            $intentosEspecifico = $this->incrementar(
                $this->claveEspecifica($ip, $nombreUsuario, $hotelSlug),
                $ip,
                $nombreUsuario,
                $hotelSlug,
                self::MAX_INTENTOS_ESPECIFICO
            );
            $this->incrementar($this->claveIp($ip), $ip, null, null, self::MAX_INTENTOS_IP);
            $this->limpiarViejos();

            return [
                'bloqueado' => $intentosEspecifico >= self::MAX_INTENTOS_ESPECIFICO,
                'restantes' => max(0, self::MAX_INTENTOS_ESPECIFICO - $intentosEspecifico),
            ];
        } catch (Throwable $e) {
            error_log('[LoginRateLimiter] Error registrando fallo: ' . $e->getMessage());
            return ['bloqueado' => false, 'restantes' => self::MAX_INTENTOS_ESPECIFICO - 1];
        }
    }

    /**
     * Login exitoso: limpia el contador especifico (el global por IP se
     * conserva — un stuffing con un acierto no debe resetear la ventana).
     */
    public function registrarExito(string $ip, string $nombreUsuario, ?string $hotelSlug): void
    {
        if (!$this->disponible) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM login_intentos WHERE clave = ?');
            $stmt->execute([$this->claveEspecifica($ip, $nombreUsuario, $hotelSlug)]);
        } catch (Throwable $e) {
            error_log('[LoginRateLimiter] Error limpiando intentos: ' . $e->getMessage());
        }
    }

    // ───────────────────────── Helpers ─────────────────────────

    /**
     * Upsert atomico: reinicia el contador si la ventana expiro, incrementa si
     * no, y fija bloqueado_hasta al alcanzar el maximo. Devuelve los intentos
     * acumulados tras el incremento.
     */
    private function incrementar(string $clave, string $ip, ?string $nombreUsuario, ?string $hotelSlug, int $maximo): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_intentos (clave, ip, nombre_usuario, hotel_slug, intentos, bloqueado_hasta, ultimo_intento)
             VALUES (?, ?, ?, ?, 1, NULL, NOW())
             ON DUPLICATE KEY UPDATE
                intentos = IF(ultimo_intento < NOW() - INTERVAL ' . self::VENTANA_SEGUNDOS . ' SECOND, 1, intentos + 1),
                -- Las asignaciones se evaluan en orden: aqui intentos YA tiene el valor nuevo.
                bloqueado_hasta = IF(
                    intentos >= ' . $maximo . ',
                    NOW() + INTERVAL ' . self::BLOQUEO_SEGUNDOS . ' SECOND,
                    bloqueado_hasta
                ),
                ultimo_intento = NOW()'
        );
        $stmt->execute([
            $clave,
            substr($ip, 0, 45),
            $nombreUsuario !== null ? substr($nombreUsuario, 0, 100) : null,
            $hotelSlug !== null ? substr($hotelSlug, 0, 120) : null,
        ]);

        $stmt = $this->pdo->prepare('SELECT intentos FROM login_intentos WHERE clave = ? LIMIT 1');
        $stmt->execute([$clave]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['intentos'] ?? 1);
    }

    /** Poda oportunista de registros viejos sin bloqueo vigente. */
    private function limpiarViejos(): void
    {
        try {
            $this->pdo->exec(
                'DELETE FROM login_intentos
                 WHERE ultimo_intento < NOW() - INTERVAL ' . self::RETENCION_SEGUNDOS . ' SECOND
                   AND (bloqueado_hasta IS NULL OR bloqueado_hasta < NOW())
                 LIMIT 200'
            );
        } catch (Throwable $e) {
            // La poda nunca debe romper el flujo de login.
        }
    }

    private function claveEspecifica(string $ip, string $nombreUsuario, ?string $hotelSlug): string
    {
        return hash('sha256', 'esp|' . $ip . '|' . mb_strtolower(trim($nombreUsuario)) . '|' . (string)$hotelSlug);
    }

    private function claveIp(string $ip): string
    {
        return hash('sha256', 'ip|' . $ip);
    }

    private function tablaExiste(): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
            );
            $stmt->execute(['login_intentos']);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }
}
