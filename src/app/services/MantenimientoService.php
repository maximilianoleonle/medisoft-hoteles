<?php

require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/NotificacionService.php';

/**
 * MantenimientoService — nucleo transaccional del mantenimiento de
 * habitaciones, extraido de HabitacionController::mantenimientoAction para
 * reutilizarlo (pantalla de habitaciones y accion del copiloto) sin duplicar
 * reglas. Mismas validaciones, mismos mensajes y mismas notificaciones que
 * el flujo original; la capa que llama decide la UX (flash, redirect,
 * como se confirma un conflicto).
 *
 * Diferencias deliberadas vs el inline original:
 *  - Lecturas y escrituras de la habitacion SIEMPRE con scope de hotel.
 *  - Un error crudo de BD jamas sube con su mensaje SQL: se relanza generico.
 */
class MantenimientoService
{
    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    /**
     * Inicia mantenimiento. Si hay reservaciones proximas (7 dias) y NO se
     * confirmo el conflicto, no escribe nada y devuelve
     * ['ok' => false, 'conflictos' => [...]]. Validaciones invalidas lanzan
     * InvalidArgumentException/RuntimeException con mensaje humano.
     */
    public function iniciarParaHotel(int $hotelId, int $habitacionId, string $tipo, string $prioridad, string $motivo, ?int $usuarioId = null, bool $confirmoConflicto = false): array
    {
        $motivo = trim($motivo);
        if (!in_array($tipo, array_keys(Mantenimiento::getTipos()), true)) {
            throw new InvalidArgumentException('Debe seleccionar un tipo de mantenimiento valido');
        }
        if (!in_array($prioridad, array_keys(Mantenimiento::getPrioridades()), true)) {
            throw new InvalidArgumentException('Debe seleccionar una prioridad valida');
        }
        if ($motivo === '') {
            throw new InvalidArgumentException('El motivo es obligatorio');
        }

        $habitacion = $this->habitacion($hotelId, $habitacionId);
        if (!$habitacion) {
            throw new RuntimeException('Habitacion no encontrada para el hotel actual.');
        }
        if ((string) ($habitacion['estado'] ?? '') === 'mantenimiento') {
            throw new RuntimeException('La habitacion ya esta en mantenimiento');
        }

        $conflictos = [];
        $mantenimientoId = 0;
        $this->db->safeBeginTransaction();
        try {
            $stmtActivo = $this->db->query(
                "SELECT COUNT(*) FROM mantenimientos_habitaciones
                 WHERE habitacion_id = ? AND hotel_id = ? AND estado = 'en_proceso'",
                [$habitacionId, $hotelId]
            );
            if ($stmtActivo && (int) $stmtActivo->fetchColumn() > 0) {
                throw new RuntimeException('La habitacion ya tiene un mantenimiento en proceso');
            }

            $conflictos = $this->reservacionesEnConflicto($hotelId, $habitacionId, date('Y-m-d'), date('Y-m-d', strtotime('+7 days')), 8);
            if (!empty($conflictos) && !$confirmoConflicto) {
                $this->db->rollBack();
                return ['ok' => false, 'conflictos' => $conflictos, 'habitacion' => $habitacion];
            }

            $this->db->query(
                "UPDATE habitaciones SET estado = 'mantenimiento' WHERE id = ? AND hotel_id = ?",
                [$habitacionId, $hotelId]
            );
            $stmtInsert = $this->db->query(
                "INSERT INTO mantenimientos_habitaciones
                    (hotel_id, habitacion_id, tipo_mantenimiento, prioridad, motivo, usuario_registro_id, fecha_inicio, estado)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), 'en_proceso')",
                [$hotelId, $habitacionId, $tipo, $prioridad, $motivo, $usuarioId]
            );
            if (!$stmtInsert) {
                throw new RuntimeException('No se pudo registrar el mantenimiento.');
            }
            $mantenimientoId = (int) $this->db->lastInsertId();
            $this->db->commit();
        } catch (Throwable $e) {
            try {
                $this->db->rollBack();
            } catch (Throwable $e2) {
                // sin transaccion activa: nada que revertir
            }
            if ($e instanceof PDOException) {
                error_log('Mantenimiento: error BD al iniciar: ' . $e->getMessage());
                throw new RuntimeException('No se pudo procesar el mantenimiento. Intenta de nuevo.');
            }
            throw $e;
        }

        $numero = (string) ($habitacion['numero'] ?? $habitacionId);
        $this->notificar(
            $hotelId,
            $habitacionId,
            'mantenimiento_iniciado',
            'Mantenimiento iniciado en habitacion ' . $numero,
            $motivo !== '' ? $motivo : 'La habitacion paso a mantenimiento.',
            $prioridad === 'alta' ? 'alta' : 'media',
            $usuarioId
        );
        if (!empty($conflictos)) {
            $this->notificarReservaProxima($hotelId, $habitacionId, $numero, $conflictos, 'iniciado', $usuarioId);
        }

        return [
            'ok' => true,
            'conflictos' => $conflictos,
            'habitacion' => $habitacion,
            'mantenimiento_id' => $mantenimientoId,
        ];
    }

    /** Finaliza el mantenimiento en proceso y libera la habitacion. */
    public function finalizarParaHotel(int $hotelId, int $habitacionId, ?int $usuarioId = null): array
    {
        $habitacion = $this->habitacion($hotelId, $habitacionId);
        if (!$habitacion) {
            throw new RuntimeException('Habitacion no encontrada para el hotel actual.');
        }
        if ((string) ($habitacion['estado'] ?? '') !== 'mantenimiento') {
            throw new RuntimeException('Solo se puede finalizar mantenimiento de una habitacion en mantenimiento');
        }

        $activosServidos = [];
        $this->db->safeBeginTransaction();
        try {
            $activosServidos = $this->activosServidosEnProceso($hotelId, $habitacionId);

            $this->db->query(
                "UPDATE habitaciones SET estado = 'disponible' WHERE id = ? AND hotel_id = ?",
                [$habitacionId, $hotelId]
            );
            $this->db->query(
                "UPDATE mantenimientos_habitaciones
                 SET estado = 'completado', fecha_fin = NOW()
                 WHERE habitacion_id = ? AND hotel_id = ? AND estado = 'en_proceso'",
                [$habitacionId, $hotelId]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            try {
                $this->db->rollBack();
            } catch (Throwable $e2) {
            }
            if ($e instanceof PDOException) {
                error_log('Mantenimiento: error BD al finalizar: ' . $e->getMessage());
                throw new RuntimeException('No se pudo procesar el mantenimiento. Intenta de nuevo.');
            }
            throw $e;
        }

        $numero = (string) ($habitacion['numero'] ?? $habitacionId);
        $this->notificar(
            $hotelId,
            $habitacionId,
            'mantenimiento_finalizado',
            'Mantenimiento finalizado en habitacion ' . $numero,
            'La habitacion fue marcada como disponible.',
            'info',
            $usuarioId
        );

        $activosActualizados = $this->registrarActivosServidos($hotelId, $activosServidos);

        return [
            'ok' => true,
            'habitacion' => $habitacion,
            'activos_servidos' => $activosServidos,
            'activos_actualizados' => $activosActualizados,
        ];
    }

    /**
     * Inicia mantenimiento de un AREA (bloque habitaciones y areas): mismo
     * registro en mantenimientos_habitaciones pero con area_id y habitacion
     * NULL. Sin conflicto de reservas: las areas no se reservan.
     */
    public function iniciarParaAreaHotel(int $hotelId, int $areaId, string $tipo, string $prioridad, string $motivo, ?int $usuarioId = null): array
    {
        $motivo = trim($motivo);
        if (!in_array($tipo, array_keys(Mantenimiento::getTipos()), true)) {
            throw new InvalidArgumentException('Debe seleccionar un tipo de mantenimiento valido');
        }
        if (!in_array($prioridad, array_keys(Mantenimiento::getPrioridades()), true)) {
            throw new InvalidArgumentException('Debe seleccionar una prioridad valida');
        }
        if ($motivo === '') {
            throw new InvalidArgumentException('El motivo es obligatorio');
        }

        $area = $this->area($hotelId, $areaId);
        if (!$area) {
            throw new RuntimeException('Area no encontrada para el hotel actual.');
        }
        if ((string) ($area['estado'] ?? '') === 'mantenimiento') {
            throw new RuntimeException('El area ya esta en mantenimiento');
        }

        $mantenimientoId = 0;
        $this->db->safeBeginTransaction();
        try {
            $stmtActivo = $this->db->query(
                "SELECT COUNT(*) FROM mantenimientos_habitaciones
                 WHERE area_id = ? AND hotel_id = ? AND estado = 'en_proceso'",
                [$areaId, $hotelId]
            );
            if ($stmtActivo && (int) $stmtActivo->fetchColumn() > 0) {
                throw new RuntimeException('El area ya tiene un mantenimiento en proceso');
            }

            $this->db->query(
                "UPDATE areas_hotel SET estado = 'mantenimiento', updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?",
                [$areaId, $hotelId]
            );
            $stmtInsert = $this->db->query(
                "INSERT INTO mantenimientos_habitaciones
                    (hotel_id, area_id, tipo_mantenimiento, prioridad, motivo, usuario_registro_id, fecha_inicio, estado)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), 'en_proceso')",
                [$hotelId, $areaId, $tipo, $prioridad, $motivo, $usuarioId]
            );
            if (!$stmtInsert) {
                throw new RuntimeException('No se pudo registrar el mantenimiento del area.');
            }
            $mantenimientoId = (int) $this->db->lastInsertId();
            $this->db->commit();
        } catch (Throwable $e) {
            try {
                $this->db->rollBack();
            } catch (Throwable $e2) {
                // sin transaccion activa: nada que revertir
            }
            if ($e instanceof PDOException) {
                error_log('Mantenimiento: error BD al iniciar en area: ' . $e->getMessage());
                throw new RuntimeException('No se pudo procesar el mantenimiento. Intenta de nuevo.');
            }
            throw $e;
        }

        return [
            'ok' => true,
            'area' => $area,
            'mantenimiento_id' => $mantenimientoId,
        ];
    }

    /** Finaliza el mantenimiento en proceso del area y la deja disponible. */
    public function finalizarParaAreaHotel(int $hotelId, int $areaId, ?int $usuarioId = null): array
    {
        $area = $this->area($hotelId, $areaId);
        if (!$area) {
            throw new RuntimeException('Area no encontrada para el hotel actual.');
        }
        if ((string) ($area['estado'] ?? '') !== 'mantenimiento') {
            throw new RuntimeException('Solo se puede finalizar mantenimiento de un area en mantenimiento');
        }

        $this->db->safeBeginTransaction();
        try {
            $this->db->query(
                "UPDATE areas_hotel SET estado = 'disponible', updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?",
                [$areaId, $hotelId]
            );
            $this->db->query(
                "UPDATE mantenimientos_habitaciones
                 SET estado = 'completado', fecha_fin = NOW()
                 WHERE area_id = ? AND hotel_id = ? AND estado = 'en_proceso'",
                [$areaId, $hotelId]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            try {
                $this->db->rollBack();
            } catch (Throwable $e2) {
            }
            if ($e instanceof PDOException) {
                error_log('Mantenimiento: error BD al finalizar en area: ' . $e->getMessage());
                throw new RuntimeException('No se pudo procesar el mantenimiento. Intenta de nuevo.');
            }
            throw $e;
        }

        return ['ok' => true, 'area' => $area];
    }

    private function area(int $hotelId, int $areaId): ?array
    {
        try {
            $stmt = $this->db->query(
                "SELECT id, nombre, estado FROM areas_hotel WHERE id = ? AND hotel_id = ? AND activa = 1 LIMIT 1",
                [$areaId, $hotelId]
            );
            $row = $stmt ? $stmt->fetch() : null;
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Reservaciones activas que se cruzan con la ventana de mantenimiento
     * (misma consulta que usaba el controlador, con hotel explicito).
     */
    public function reservacionesEnConflicto(int $hotelId, int $habitacionId, string $fechaInicio, ?string $fechaFin = null, int $limite = 8): array
    {
        if ($habitacionId <= 0 || $hotelId <= 0) {
            return [];
        }

        $fechaInicio = substr(trim($fechaInicio), 0, 10);
        $fechaFin = substr(trim((string) ($fechaFin ?: $fechaInicio)), 0, 10);

        if ($fechaInicio === '' || strtotime($fechaInicio) === false) {
            return [];
        }
        if ($fechaFin === '' || strtotime($fechaFin) === false || $fechaFin < $fechaInicio) {
            $fechaFin = $fechaInicio;
        }

        $limite = max(1, min(40, (int) $limite));
        $fechaFinExclusiva = date('Y-m-d', strtotime($fechaFin . ' +1 day'));

        try {
            $sql = "SELECT
                        r.id,
                        r.fecha_entrada,
                        r.fecha_salida,
                        r.hora_llegada_estimada,
                        r.estado,
                        h.nombre_completo,
                        h.telefono,
                        COUNT(DISTINCT rh2.habitacion_id) AS total_habitaciones
                    FROM reservaciones r
                    INNER JOIN reservacion_habitaciones rh
                        ON rh.reservacion_id = r.id
                    INNER JOIN habitaciones hab_scope
                        ON hab_scope.id = rh.habitacion_id
                       AND hab_scope.hotel_id = r.hotel_id
                    INNER JOIN huespedes h
                        ON h.id = r.huesped_id
                       AND h.hotel_id = r.hotel_id
                    LEFT JOIN reservacion_habitaciones rh2
                        ON rh2.reservacion_id = r.id
                    WHERE r.hotel_id = ?
                      AND rh.habitacion_id = ?
                      AND hab_scope.hotel_id = ?
                      AND r.estado IN ('confirmada', 'checked_in')
                      AND r.fecha_entrada < ?
                      AND r.fecha_salida > ?
                    GROUP BY r.id, r.fecha_entrada, r.fecha_salida, r.hora_llegada_estimada, r.estado, h.nombre_completo, h.telefono
                    ORDER BY r.fecha_entrada ASC, COALESCE(r.hora_llegada_estimada, '23:59:59') ASC, r.id ASC
                    LIMIT {$limite}";

            $stmt = $this->db->query($sql, [
                $hotelId,
                $habitacionId,
                $hotelId,
                $fechaFinExclusiva,
                $fechaInicio,
            ]);

            $rows = $stmt ? $stmt->fetchAll() : [];
            return is_array($rows) ? $rows : [];
        } catch (Throwable $e) {
            error_log('No se pudieron consultar reservaciones para mantenimiento: ' . $e->getMessage());
            return [];
        }
    }

    // ───────────────────────── Internos ─────────────────────────

    private function habitacion(int $hotelId, int $habitacionId): ?array
    {
        try {
            $stmt = $this->db->query(
                "SELECT id, numero, estado FROM habitaciones WHERE id = ? AND hotel_id = ? LIMIT 1",
                [$habitacionId, $hotelId]
            );
            $fila = $stmt ? $stmt->fetch() : null;
            return $fila ?: null;
        } catch (Throwable $e) {
            error_log('Mantenimiento: error al leer habitacion: ' . $e->getMessage());
            return null;
        }
    }

    /** Misma notificacion que registraba el controlador (habitaciones/*). */
    private function notificar(int $hotelId, int $habitacionId, string $tipo, string $titulo, string $mensaje, string $severidad, ?int $usuarioId): void
    {
        if ($habitacionId <= 0) {
            return;
        }
        NotificacionService::crear([
            'hotel_id' => $hotelId,
            'modulo' => 'habitaciones',
            'tipo' => $tipo,
            'severidad' => $severidad,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'entidad_tipo' => 'habitacion',
            'entidad_id' => $habitacionId,
            'url' => 'habitaciones/' . $habitacionId,
            'dedupe_key' => 'habitaciones.' . $tipo . '.' . $habitacionId . '.' . date('YmdHis'),
            'creada_por' => $usuarioId,
        ]);
    }

    /**
     * Activos preventivos ligados a mantenimientos abiertos. Si la migracion
     * de activos no existe aun, el flujo de mantenimiento sigue cerrando.
     */
    private function activosServidosEnProceso(int $hotelId, int $habitacionId): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT DISTINCT activo_id
                 FROM mantenimientos_habitaciones
                 WHERE habitacion_id = ?
                   AND hotel_id = ?
                   AND estado = 'en_proceso'
                   AND activo_id IS NOT NULL",
                [$habitacionId, $hotelId]
            );
            if (!$stmt) {
                return [];
            }

            $ids = [];
            foreach (($stmt->fetchAll() ?: []) as $row) {
                $activoId = (int) ($row['activo_id'] ?? 0);
                if ($activoId > 0) {
                    $ids[$activoId] = $activoId;
                }
            }

            return array_values($ids);
        } catch (Throwable $e) {
            error_log('Mantenimiento: no se pudieron leer activos servidos: ' . $e->getMessage());
            return [];
        }
    }

    private function registrarActivosServidos(int $hotelId, array $activosServidos): int
    {
        if (empty($activosServidos)) {
            return 0;
        }

        require_once __DIR__ . '/../models/ActivoHotel.php';

        $actualizados = 0;
        $activoModel = new ActivoHotel();
        foreach ($activosServidos as $activoId) {
            try {
                if ($activoModel->registrarServicioCompletado($hotelId, (int) $activoId)) {
                    $actualizados++;
                }
            } catch (Throwable $e) {
                error_log('Mantenimiento: no se pudo actualizar activo servido #' . (int) $activoId . ': ' . $e->getMessage());
            }
        }

        return $actualizados;
    }

    /** Aviso de alta severidad cuando el mantenimiento cruza una reserva. */
    private function notificarReservaProxima(int $hotelId, int $habitacionId, string $numero, array $reservas, string $accion, ?int $usuarioId): void
    {
        if ($habitacionId <= 0 || empty($reservas)) {
            return;
        }

        $primera = $reservas[0];
        $nombre = trim((string) ($primera['nombre_completo'] ?? 'Huesped'));
        $entrada = !empty($primera['fecha_entrada']) ? date('d/m/Y', strtotime((string) $primera['fecha_entrada'])) : 'sin fecha';
        $hora = trim((string) ($primera['hora_llegada_estimada'] ?? ''));
        $horaTexto = $hora !== '' ? ' a las ' . substr($hora, 0, 5) : '';
        $accionTexto = $accion === 'programado' ? 'programado' : 'iniciado';
        $total = count($reservas);

        NotificacionService::crear([
            'hotel_id' => $hotelId,
            'modulo' => 'habitaciones',
            'tipo' => 'mantenimiento_reserva_proxima',
            'severidad' => 'alta',
            'titulo' => 'Mantenimiento con reserva proxima',
            'mensaje' => 'Hab. ' . $numero . ': mantenimiento ' . $accionTexto .
                ' con ' . $total . ' reservacion(es) activa(s). Proxima llegada: ' . $nombre . ' el ' . $entrada . $horaTexto .
                '. Revisar reasignacion o seguimiento operativo.',
            'entidad_tipo' => 'habitacion',
            'entidad_id' => $habitacionId,
            'url' => 'habitaciones/' . $habitacionId,
            'dedupe_key' => 'habitaciones.mantenimiento_reserva.' . $habitacionId . '.' . $accion . '.' . date('YmdHi'),
            'creada_por' => $usuarioId,
        ]);
    }
}
