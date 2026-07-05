<?php
/**
 * Historial salarial con vigencias (Fase 2 nomina core).
 *
 * Regla central: el salario NUNCA se pisa sin rastro. Cada cambio cierra la
 * vigencia anterior (vigente_hasta = nuevo_desde - 1 dia) e inserta una nueva
 * vigencia abierta. trabajadores.salario_base se mantiene sincronizado como
 * "salario vigente" por compatibilidad con el bloque personal existente.
 *
 * Transaccional con FOR UPDATE sobre el trabajador y su vigencia abierta.
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class NominaSalarioService {

    private $db;
    private $pdo;

    private $esquemasValidos = ['semanal', 'quincenal', 'mensual', 'diario', 'por_hora', 'por_evento'];

    /** Esquemas que tambien existen en trabajadores.periodicidad_pago. */
    private $esquemasCompatPeriodicidad = ['semanal', 'quincenal', 'mensual', 'por_evento'];

    public function __construct(?Database $db = null) {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function historial(int $hotelId, int $trabajadorId): array {
        $st = $this->db->query(
            "SELECT * FROM trabajador_salarios
             WHERE hotel_id = ? AND trabajador_id = ?
             ORDER BY vigente_desde DESC, id DESC",
            [$hotelId, $trabajadorId]
        );
        return $st !== false ? $st->fetchAll() : [];
    }

    public function vigente(int $hotelId, int $trabajadorId, ?string $fecha = null): ?array {
        $fecha = $this->normalizarFecha($fecha ?: date('Y-m-d'));

        $st = $this->db->query(
            "SELECT * FROM trabajador_salarios
             WHERE hotel_id = ? AND trabajador_id = ?
               AND vigente_desde <= ?
               AND (vigente_hasta IS NULL OR vigente_hasta >= ?)
             ORDER BY vigente_desde DESC, id DESC
             LIMIT 1",
            [$hotelId, $trabajadorId, $fecha, $fecha]
        );
        if ($st === false) {
            return null;
        }
        $fila = $st->fetch();
        return $fila ?: null;
    }

    /**
     * Registra un cambio salarial con vigencia.
     * $datos: salario (>0), esquema, vigente_desde (Y-m-d), motivo (opcional).
     */
    public function registrarCambio(int $hotelId, int $trabajadorId, array $datos, ?int $usuarioId = null): array {
        if ($hotelId <= 0 || $trabajadorId <= 0) {
            throw new Exception('Contexto no valido.');
        }

        $salario = round((float) ($datos['salario'] ?? 0), 2);
        if ($salario <= 0) {
            throw new Exception('El salario debe ser mayor a cero.');
        }
        $salarioStr = number_format($salario, 2, '.', '');

        $esquema = (string) ($datos['esquema'] ?? '');
        if (!in_array($esquema, $this->esquemasValidos, true)) {
            throw new Exception('Esquema de pago no valido.');
        }

        $desde = $this->normalizarFecha((string) ($datos['vigente_desde'] ?? ''));
        $motivo = trim((string) ($datos['motivo'] ?? ''));
        $motivo = $motivo === '' ? null : mb_substr($motivo, 0, 200);

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            // Bloquear al trabajador (y validar tenant).
            $st = $this->pdo->prepare(
                "SELECT id, nombre_completo, salario_base, periodicidad_pago
                 FROM trabajadores WHERE id = ? AND hotel_id = ? FOR UPDATE"
            );
            $st->execute([$trabajadorId, $hotelId]);
            $trabajador = $st->fetch();
            if (!$trabajador) {
                throw new Exception('El trabajador no existe en este negocio.');
            }

            // Bloquear la vigencia abierta actual (si existe).
            $st = $this->pdo->prepare(
                "SELECT * FROM trabajador_salarios
                 WHERE hotel_id = ? AND trabajador_id = ? AND vigente_hasta IS NULL
                 ORDER BY vigente_desde DESC, id DESC
                 LIMIT 1 FOR UPDATE"
            );
            $st->execute([$hotelId, $trabajadorId]);
            $abierta = $st->fetch();

            $antes = [
                'salario' => $abierta['salario'] ?? $trabajador['salario_base'],
                'esquema' => $abierta['esquema'] ?? $trabajador['periodicidad_pago'],
                'vigente_desde' => $abierta['vigente_desde'] ?? null,
            ];

            if ($abierta) {
                if ($desde === $abierta['vigente_desde']) {
                    // Correccion del mismo dia: se actualiza la vigencia en lugar de duplicarla.
                    $st = $this->pdo->prepare(
                        "UPDATE trabajador_salarios
                         SET salario = ?, esquema = ?, motivo = ?, created_by = COALESCE(created_by, ?)
                         WHERE id = ? AND hotel_id = ?"
                    );
                    $st->execute([$salarioStr, $esquema, $motivo, $usuarioId, $abierta['id'], $hotelId]);
                } elseif ($desde < $abierta['vigente_desde']) {
                    throw new Exception('La nueva vigencia no puede iniciar antes que la vigencia actual (' . $abierta['vigente_desde'] . ').');
                } else {
                    $cierre = date('Y-m-d', strtotime($desde . ' -1 day'));
                    $st = $this->pdo->prepare(
                        "UPDATE trabajador_salarios SET vigente_hasta = ? WHERE id = ? AND hotel_id = ?"
                    );
                    $st->execute([$cierre, $abierta['id'], $hotelId]);

                    $st = $this->pdo->prepare(
                        "INSERT INTO trabajador_salarios
                            (hotel_id, trabajador_id, salario, esquema, vigente_desde, vigente_hasta, motivo, created_by)
                         VALUES (?, ?, ?, ?, ?, NULL, ?, ?)"
                    );
                    $st->execute([$hotelId, $trabajadorId, $salarioStr, $esquema, $desde, $motivo, $usuarioId]);
                }
            } else {
                $st = $this->pdo->prepare(
                    "INSERT INTO trabajador_salarios
                        (hotel_id, trabajador_id, salario, esquema, vigente_desde, vigente_hasta, motivo, created_by)
                     VALUES (?, ?, ?, ?, ?, NULL, ?, ?)"
                );
                $st->execute([$hotelId, $trabajadorId, $salarioStr, $esquema, $desde, $motivo, $usuarioId]);
            }

            // Sincronizar el "vigente" en trabajadores por compatibilidad.
            if (in_array($esquema, $this->esquemasCompatPeriodicidad, true)) {
                $st = $this->pdo->prepare(
                    "UPDATE trabajadores SET salario_base = ?, periodicidad_pago = ?, updated_by = ? WHERE id = ? AND hotel_id = ?"
                );
                $st->execute([$salarioStr, $esquema, $usuarioId, $trabajadorId, $hotelId]);
            } else {
                $st = $this->pdo->prepare(
                    "UPDATE trabajadores SET salario_base = ?, updated_by = ? WHERE id = ? AND hotel_id = ?"
                );
                $st->execute([$salarioStr, $usuarioId, $trabajadorId, $hotelId]);
            }

            AuditService::record('nomina.salario_actualizado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'trabajador_salarios',
                'entidad_id' => (string) $trabajadorId,
                'descripcion' => 'Cambio salarial de ' . ($trabajador['nombre_completo'] ?? $trabajadorId)
                    . ' con vigencia desde ' . $desde . ($motivo ? ' (' . $motivo . ')' : ''),
                'datos_antes' => $antes,
                'datos_despues' => ['salario' => $salarioStr, 'esquema' => $esquema, 'vigente_desde' => $desde],
            ]);

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return ['success' => true, 'message' => 'Cambio salarial registrado con vigencia desde ' . $desde . '.'];
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    private function normalizarFecha(string $valor): string {
        $fecha = DateTime::createFromFormat('Y-m-d', $valor);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            throw new Exception('Fecha no valida (formato requerido: AAAA-MM-DD).');
        }
        return $valor;
    }
}
