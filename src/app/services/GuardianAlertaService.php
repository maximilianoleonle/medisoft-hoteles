<?php

require_once __DIR__ . '/../models/GuardianPatrones.php';
require_once __DIR__ . '/../models/GuardianHallazgoEstado.php';
require_once __DIR__ . '/PwaPushService.php';

/**
 * Proactividad del Guardian (vigilancia financiera).
 *
 *  - Alerta inmediata: cuando aparece un hallazgo de severidad ALTA nuevo,
 *    push por la cadena PWA existente, pero dirigido SOLO a los usuarios con
 *    el permiso guardian.view (resuelto contra roles.permisos_json, con
 *    fallback legacy). El texto del push NO lleva nombres ni montos: los
 *    detalles viven dentro de la app, detras del permiso.
 *  - Digest semanal (lunes): resumen de la semana. El verde tambien se
 *    comunica ("Semana limpia: N movimientos, todo cuadro") porque ese
 *    mensaje ES el valor del bloque.
 *
 * Solo lee dinero; su unica escritura es guardian_hallazgos_estado (marca de
 * notificado). CLI: src/tools/saas/guardian_notificar.php.
 */
class GuardianAlertaService
{
    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();

        // permission_in_list vive en helpers/auth.php; en CLI puede no estar cargado.
        if (!function_exists('permission_in_list')) {
            require_once __DIR__ . '/../helpers/auth.php';
        }
    }

    /**
     * Usuarios del hotel con guardian.view. Roles configurables mandan
     * (roles.permisos_json); si el usuario no tiene rol configurable, cae al
     * fallback legacy (gerente/propietario/superadmin/dueno), la misma
     * poblacion que can_legacy autoriza.
     */
    public function usuariosConPermiso(int $hotelId): array
    {
        if ($hotelId <= 0) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT hu.usuario_id, hu.rol AS rol_legacy, r.permisos_json
             FROM hotel_usuarios hu
             LEFT JOIN roles r
                ON r.id = hu.role_id
               AND r.hotel_id = hu.hotel_id
               AND r.activo = 1
             WHERE hu.hotel_id = ?
               AND hu.activo = 1",
            [$hotelId]
        );

        $ids = [];
        foreach (($stmt ? $stmt->fetchAll() : []) ?: [] as $fila) {
            $permisos = null;
            if (!empty($fila['permisos_json'])) {
                $decodificado = json_decode((string) $fila['permisos_json'], true);
                $permisos = is_array($decodificado) ? $decodificado : null;
            }

            if (is_array($permisos)) {
                if (permission_in_list('guardian.view', $permisos)) {
                    $ids[] = (int) $fila['usuario_id'];
                }
                continue;
            }

            // Sin rol configurable resoluble: paridad con can_legacy.
            $rol = strtolower(trim((string) ($fila['rol_legacy'] ?? '')));
            if (in_array($rol, ['gerente', 'propietario', 'superadmin', 'dueno'], true)) {
                $ids[] = (int) $fila['usuario_id'];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Corre los patrones, registra hallazgos y manda push inmediato si hay
     * hallazgos ALTOS nuevos sin notificar. Devuelve
     * ['success','hallazgos_altos','enviados','fallidos','destinatarios','mensaje'].
     */
    public function evaluarYNotificar(int $hotelId, bool $dryRun = false): array
    {
        if ($hotelId <= 0) {
            return ['success' => false, 'mensaje' => 'Hotel invalido.'];
        }

        $patrones = (new GuardianPatrones())->reporteReadOnlyPorHotel($hotelId);
        $estadoModel = new GuardianHallazgoEstado();
        $estadoModel->sincronizarDesdeReporte($hotelId, $patrones);

        $pendientes = $estadoModel->listarParaNotificar($hotelId);
        if (empty($pendientes)) {
            return [
                'success' => true,
                'hallazgos_altos' => 0,
                'enviados' => 0,
                'fallidos' => 0,
                'destinatarios' => 0,
                'mensaje' => 'Sin hallazgos altos nuevos que notificar.',
            ];
        }

        $usuarios = $this->usuariosConPermiso($hotelId);
        $n = count($pendientes);

        // Texto deliberadamente SIN detalles sensibles (nombres/montos solo en la app).
        $payload = [
            'title' => 'El Guardián',
            'body' => $n === 1
                ? 'Detectó un patrón a revisar en caja. Los detalles te esperan dentro de la app.'
                : "Detectó {$n} patrones a revisar en caja. Los detalles te esperan dentro de la app.",
            'url' => 'ia/vigilancia-financiera',
            'tag' => 'guardian-alerta-' . $hotelId,
            'severity' => 'alta',
            'module' => 'guardian',
        ];

        $envio = ['sent' => 0, 'failed' => 0, 'skipped' => true];
        if (!$dryRun && !empty($usuarios)) {
            try {
                $envio = (new PwaPushService())->enviarDirectoAUsuarios($hotelId, $payload, $usuarios);
                // Se marca notificado SOLO si el envio no exploto: si el push
                // truena, el hallazgo queda pendiente y el proximo --scan lo
                // reintenta (una alerta alta jamas se pierde en silencio).
                $estadoModel->marcarNotificados(array_column($pendientes, 'id'), $hotelId);
            } catch (Throwable $e) {
                error_log('GuardianAlertaService: error al enviar push: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'hallazgos_altos' => $n,
            'enviados' => (int) ($envio['sent'] ?? 0),
            'fallidos' => (int) ($envio['failed'] ?? 0),
            'destinatarios' => count($usuarios),
            'dry_run' => $dryRun,
            'mensaje' => $dryRun
                ? "{$n} hallazgo(s) alto(s) pendiente(s); dry-run, no se envio push ni se marco notificado."
                : "{$n} hallazgo(s) alto(s) notificado(s) a " . count($usuarios) . ' usuario(s) con permiso.',
        ];
    }

    /**
     * Digest de la semana pasada (lunes a domingo). Pensado para correr los
     * lunes por cron. Devuelve el texto ademas de enviarlo, para que el CLI
     * lo muestre.
     */
    public function digestSemanal(int $hotelId, bool $dryRun = false): array
    {
        if ($hotelId <= 0) {
            return ['success' => false, 'mensaje' => 'Hotel invalido.'];
        }

        $desde = date('Y-m-d', strtotime('monday last week'));
        $hasta = date('Y-m-d', strtotime('sunday last week'));

        $movs = $this->contar(
            "SELECT COUNT(*) FROM movimientos_caja mc WHERE mc.hotel_id = ? AND mc.created_at >= ? AND mc.created_at <= ?",
            [$hotelId, $desde . ' 00:00:00', $hasta . ' 23:59:59']
        );
        $reservas = $this->contar(
            "SELECT COUNT(*) FROM reservaciones r WHERE r.hotel_id = ? AND r.created_at >= ? AND r.created_at <= ?",
            [$hotelId, $desde . ' 00:00:00', $hasta . ' 23:59:59']
        );

        $hallazgos = (new GuardianHallazgoEstado())->contarDetectadosEntre($hotelId, $desde, $hasta);

        if ($hallazgos === 0) {
            $cuerpo = "Semana limpia: {$movs} movimientos y {$reservas} reservas, todo dentro del patrón de tu hotel.";
        } else {
            $cuerpo = "Semana con {$hallazgos} patrón(es) a revisar entre {$movs} movimientos. El detalle está en la app.";
        }

        $usuarios = $this->usuariosConPermiso($hotelId);
        $payload = [
            'title' => 'El Guardián · resumen semanal',
            'body' => $cuerpo,
            'url' => 'ia/vigilancia-financiera',
            'tag' => 'guardian-digest-' . $hotelId,
            'severity' => $hallazgos === 0 ? 'info' : 'media',
            'module' => 'guardian',
        ];

        $envio = ['sent' => 0, 'failed' => 0, 'skipped' => true];
        if (!$dryRun && !empty($usuarios)) {
            try {
                $envio = (new PwaPushService())->enviarDirectoAUsuarios($hotelId, $payload, $usuarios);
            } catch (Throwable $e) {
                error_log('GuardianAlertaService: error al enviar digest: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'semana' => ['desde' => $desde, 'hasta' => $hasta],
            'movimientos' => $movs,
            'reservaciones' => $reservas,
            'hallazgos' => $hallazgos,
            'destinatarios' => count($usuarios),
            'enviados' => (int) ($envio['sent'] ?? 0),
            'fallidos' => (int) ($envio['failed'] ?? 0),
            'dry_run' => $dryRun,
            'mensaje' => $cuerpo,
        ];
    }

    private function contar(string $sql, array $params): int
    {
        try {
            $stmt = $this->db->query($sql, $params);
            return $stmt ? (int) $stmt->fetchColumn() : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}
