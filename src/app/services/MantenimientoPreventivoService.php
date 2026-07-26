<?php
/**
 * Generador de mantenimiento preventivo por activo (bloque mantenimiento).
 *
 * Al vencer proximo_servicio de un activo crea el mantenimiento preventivo
 * REUTILIZANDO el flujo correctivo existente (mantenimientos_habitaciones +
 * TareaOperativa::crearDesdeMantenimientoParaHotel con su candado de "una
 * tarea activa por mantenimiento") y avisa por la cadena de notificaciones.
 *
 * Reglas:
 *  - Idempotente: un activo con mantenimiento abierto (en_proceso/programado)
 *    no genera otro.
 *  - NO toca habitaciones.estado: bloquear una habitacion vendible es
 *    decision humana, no del cron.
 *  - El recalculo de proximo_servicio ocurre al CERRAR el mantenimiento
 *    (ActivoHotel::registrarServicioCompletado), no aqui.
 */

require_once __DIR__ . '/../models/ActivoHotel.php';
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/NotificacionService.php';

class MantenimientoPreventivoService
{
    /** @var Database */
    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    /**
     * Genera los preventivos vencidos de un hotel.
     * Retorna resumen: generados, omitidos, errores (mensajes legibles).
     */
    public function generarParaHotel(int $hotelId): array
    {
        $resumen = ['generados' => 0, 'omitidos' => 0, 'errores' => []];

        if ($hotelId <= 0) {
            $resumen['errores'][] = 'Hotel no valido';
            return $resumen;
        }

        $activoModel = new ActivoHotel();
        $vencidos = $activoModel->vencidosSinMantenimiento($hotelId);

        foreach ($vencidos as $activo) {
            try {
                $mantenimientoId = $this->generarPreventivoDeActivo($hotelId, $activo);
                if ($mantenimientoId > 0) {
                    $resumen['generados']++;
                } else {
                    $resumen['omitidos']++;
                }
            } catch (Throwable $e) {
                $resumen['errores'][] = 'Activo #' . (int)($activo['id'] ?? 0)
                    . ' (' . (string)($activo['nombre'] ?? '') . '): ' . $e->getMessage();
            }
        }

        return $resumen;
    }

    /**
     * Crea mantenimiento preventivo + tarea + notificacion para UN activo.
     * Lanza excepcion con mensaje legible si algo falla.
     */
    public function generarPreventivoDeActivo(int $hotelId, array $activo): int
    {
        $activoId = (int)($activo['id'] ?? 0);
        if ($hotelId <= 0 || $activoId <= 0) {
            throw new InvalidArgumentException('Activo no valido');
        }

        $nombre = trim((string)($activo['nombre'] ?? 'Activo'));
        $ubicacion = trim((string)($activo['ubicacion'] ?? ''));
        $habitacionId = !empty($activo['habitacion_id']) ? (int)$activo['habitacion_id'] : null;
        $habitacionNumero = trim((string)($activo['habitacion_numero'] ?? ''));
        $periodicidad = (int)($activo['periodicidad_dias'] ?? 0);
        $vencia = substr((string)($activo['proximo_servicio'] ?? ''), 0, 10);

        $ubicacionLabel = $habitacionNumero !== ''
            ? 'Hab. ' . $habitacionNumero
            : ($ubicacion !== '' ? $ubicacion : 'Instalaciones generales');

        $motivo = mb_substr('Servicio preventivo: ' . $nombre . ' (' . $ubicacionLabel . ')', 0, 255);
        $descripcion = 'Generado automaticamente por vencimiento de servicio.'
            . ($vencia !== '' ? ' Vencia el ' . $vencia . '.' : '')
            . ($periodicidad > 0 ? ' Periodicidad: cada ' . $periodicidad . ' dias.' : '')
            . (trim((string)($activo['notas'] ?? '')) !== '' ? "\nNotas del activo: " . trim((string)$activo['notas']) : '');

        $usuarioId = $this->usuarioSistemaDelHotel($hotelId);

        // Candado de concurrencia: reverifica dentro de una transaccion corta
        // que el activo siga sin mantenimiento abierto antes de insertar.
        $this->db->safeBeginTransaction();
        try {
            $stmt = $this->db->query(
                "SELECT id FROM mantenimientos_habitaciones
                 WHERE hotel_id = ? AND activo_id = ?
                   AND estado IN ('en_proceso', 'programado')
                 LIMIT 1
                 FOR UPDATE",
                [$hotelId, $activoId]
            );
            if ($stmt && $stmt->fetch()) {
                $this->db->safeRollBack();
                return 0; // Ya hay uno abierto: idempotencia.
            }

            $stmt = $this->db->query(
                "INSERT INTO mantenimientos_habitaciones
                    (hotel_id, habitacion_id, activo_id, tipo_mantenimiento, prioridad,
                     motivo, descripcion, usuario_registro_id, fecha_inicio, estado, programado)
                 VALUES (?, ?, ?, 'preventivo', 'media', ?, ?, ?, NOW(), 'en_proceso', 0)",
                [$hotelId, $habitacionId, $activoId, $motivo, $descripcion, $usuarioId]
            );

            if (!$stmt) {
                throw new RuntimeException('No se pudo crear el mantenimiento preventivo');
            }

            $mantenimientoId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();
        } catch (Throwable $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
            throw $e;
        }

        // Tarea operativa vinculada (candado "una tarea activa por
        // mantenimiento" vive dentro de crearDesdeMantenimientoParaHotel).
        try {
            $tareaModel = new TareaOperativa();
            $tareaModel->crearDesdeMantenimientoParaHotel(
                $hotelId,
                $mantenimientoId,
                ['titulo' => mb_substr('Preventivo: ' . $nombre . ' (' . $ubicacionLabel . ')', 0, 160)],
                $usuarioId ?: null
            );
        } catch (Throwable $e) {
            // Sin tarea el preventivo queda cojo: se cancela para que el
            // siguiente run lo reintente completo.
            $this->db->query(
                "UPDATE mantenimientos_habitaciones
                 SET estado = 'cancelado', observaciones = ?, fecha_fin = NOW()
                 WHERE id = ? AND hotel_id = ?",
                ['Cancelado: no se pudo crear la tarea vinculada (' . mb_substr($e->getMessage(), 0, 150) . ')', $mantenimientoId, $hotelId]
            );
            throw new RuntimeException('Tarea vinculada fallo: ' . $e->getMessage());
        }

        // Aviso por la cadena existente (notificaciones + push por rol).
        NotificacionService::crear([
            'hotel_id' => $hotelId,
            'modulo' => 'habitaciones',
            'tipo' => 'mantenimiento_preventivo',
            'severidad' => 'media',
            'titulo' => mb_substr('Servicio preventivo vencido: ' . $nombre, 0, 150),
            'mensaje' => mb_substr($ubicacionLabel . '. Se genero el mantenimiento preventivo y su tarea de seguimiento.', 0, 500),
            'entidad_tipo' => 'habitacion',
            'entidad_id' => $habitacionId,
            'url' => 'mantenimientos/' . $mantenimientoId,
            'rol_destino' => 'gerente,mantenimiento',
            'dedupe_key' => 'mantenimiento_plus.preventivo.' . $activoId . '.' . date('Ymd'),
            'creada_por' => null,
        ]);

        return $mantenimientoId;
    }

    /**
     * Usuario "sistema" para registrar el preventivo en CLI: el primer
     * usuario activo ligado al hotel (gerente de preferencia); 0 si no hay.
     */
    private function usuarioSistemaDelHotel(int $hotelId): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT u.id
                 FROM usuarios u
                 INNER JOIN hotel_usuarios hu
                    ON hu.usuario_id = u.id
                   AND hu.hotel_id = ?
                   AND hu.activo = 1
                 WHERE u.activo = 1
                 ORDER BY FIELD(u.rol, 'gerente', 'administrador', 'recepcionista'), u.id ASC
                 LIMIT 1",
                [$hotelId]
            );
            $row = $stmt ? $stmt->fetch() : null;
            return (int)($row['id'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }
}
