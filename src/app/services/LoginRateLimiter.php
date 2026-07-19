<?php

require_once __DIR__ . '/../../core/Database.php';

/**
 * LoginRateLimiter (SEC-001)
 *
 * Contador de intentos fallidos de login persistido en DB (tabla
 * login_intentos), no reiniciable con una cookie/sesion nueva.
 *
 * Tres alcances por cada fallo:
 *  - Especifico: ip + usuario + hotel_slug → 5 intentos / bloqueo 15 min
 *    (misma UX que el contador de sesion que reemplaza).
 *  - Por CUENTA: nombre_usuario sin IP → 15 intentos / bloqueo 30 min. Frena
 *    brute force DISTRIBUIDO: sin este alcance, cada IP nueva del pool
 *    reiniciaba el contador contra la misma cuenta y el limite jamas saltaba.
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
    /**
     * Tope por CUENTA (independiente de la IP): 15 fallos en la ventana de 15
     * min bloquean la cuenta 30 min. Es 3x el tope especifico a proposito: un
     * usuario legitimo que olvida su contrasena falla desde SU IP y lo frena
     * el alcance especifico en 5 (el contador de cuenta apenas llega a 5);
     * para disparar este tope se necesitan fallos desde 3+ IPs distintas en
     * 15 minutos — patron de ataque distribuido, no de olvido humano.
     */
    private const MAX_INTENTOS_USUARIO = 15;
    private const MAX_INTENTOS_IP = 20;
    private const VENTANA_SEGUNDOS = 900;
    private const BLOQUEO_SEGUNDOS = 900;
    /**
     * Backoff del bloqueo de cuenta: 30 min (2x el bloqueo base). Acota un
     * stuffing distribuido a ~15 conjeturas por ciclo de ~45 min por cuenta.
     */
    private const BLOQUEO_USUARIO_SEGUNDOS = 1800;
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
                 WHERE clave IN (?, ?, ?) AND bloqueado_hasta > NOW()'
            );
            $stmt->execute([
                $this->claveEspecifica($ip, $nombreUsuario, $hotelSlug),
                $this->claveUsuario($nombreUsuario),
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
     * Registra un intento fallido en los tres alcances.
     * Devuelve ['bloqueado' => bool, 'restantes' => int]; 'restantes' es del
     * alcance especifico (mensaje de UX) y 'bloqueado' se enciende si el
     * especifico O el de cuenta alcanzaron su maximo.
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
            $intentosUsuario = $this->incrementar(
                $this->claveUsuario($nombreUsuario),
                $ip,
                $nombreUsuario,
                null,
                self::MAX_INTENTOS_USUARIO,
                self::BLOQUEO_USUARIO_SEGUNDOS
            );
            $this->incrementar($this->claveIp($ip), $ip, null, null, self::MAX_INTENTOS_IP);
            $this->limpiarViejos();

            return [
                'bloqueado' => $intentosEspecifico >= self::MAX_INTENTOS_ESPECIFICO
                    || $intentosUsuario >= self::MAX_INTENTOS_USUARIO,
                'restantes' => max(0, self::MAX_INTENTOS_ESPECIFICO - $intentosEspecifico),
            ];
        } catch (Throwable $e) {
            error_log('[LoginRateLimiter] Error registrando fallo: ' . $e->getMessage());
            return ['bloqueado' => false, 'restantes' => self::MAX_INTENTOS_ESPECIFICO - 1];
        }
    }

    /**
     * Login exitoso: limpia el contador especifico. Los contadores de cuenta
     * y global por IP se CONSERVAN — un stuffing con un acierto no debe
     * resetear la ventana; el de cuenta expira solo a los 15 min sin fallos.
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
    private function incrementar(string $clave, string $ip, ?string $nombreUsuario, ?string $hotelSlug, int $maximo, int $bloqueoSegundos = self::BLOQUEO_SEGUNDOS): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_intentos (clave, ip, nombre_usuario, hotel_slug, intentos, bloqueado_hasta, ultimo_intento)
             VALUES (?, ?, ?, ?, 1, NULL, NOW())
             ON DUPLICATE KEY UPDATE
                intentos = IF(ultimo_intento < NOW() - INTERVAL ' . self::VENTANA_SEGUNDOS . ' SECOND, 1, intentos + 1),
                -- Las asignaciones se evaluan en orden: aqui intentos YA tiene el valor nuevo.
                bloqueado_hasta = IF(
                    intentos >= ' . $maximo . ',
                    NOW() + INTERVAL ' . $bloqueoSegundos . ' SECOND,
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

    /**
     * Clave por CUENTA, independiente de la IP: un pool de IPs distintas ya
     * no reinicia el contador contra el mismo usuario. Sin hotel_slug a
     * proposito: la misma cuenta atacada via /login y via /h/{slug}/login
     * comparte contador.
     */
    private function claveUsuario(string $nombreUsuario): string
    {
        return hash('sha256', 'usuario|' . mb_strtolower(trim($nombreUsuario)));
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
