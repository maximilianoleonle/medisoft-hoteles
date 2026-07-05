<?php
/**
 * Adaptador HOTEL (Fase 8 nomina core).
 *
 * Convierte datos operativos del giro hotelero en PROPUESTAS de incidencia:
 * v1 toma las horas extra registradas en trabajador_asistencias (bloque
 * personal) y las propone contra el concepto activo de clasificacion
 * 'horas_extra' del negocio. Idempotente por referencia_origen 'ASI-{id}'.
 *
 * El motor core NUNCA lee asistencias directamente: solo este adaptador.
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/NominaAdaptadorGiro.php';
require_once __DIR__ . '/AuditService.php';

class NominaAdaptadorHotel implements NominaAdaptadorGiro {

    private $db;
    private $pdo;

    public function __construct(?Database $db = null) {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function giro(): string {
        return 'hotel';
    }

    public function descripcion(): string {
        return 'Propone horas extra desde las asistencias del bloque Personal.';
    }

    public function proponerIncidencias(int $hotelId, string $fechaInicio, string $fechaFin, ?int $usuarioId = null): array {
        $avisos = [];

        // Concepto de horas extra del negocio (requerido, con tarifa default).
        $st = $this->pdo->prepare(
            "SELECT * FROM nomina_conceptos
             WHERE hotel_id = ? AND clasificacion = 'horas_extra' AND activo = 1
             ORDER BY es_sistema DESC, id ASC LIMIT 1"
        );
        $st->execute([$hotelId]);
        $concepto = $st->fetch();

        if (!$concepto) {
            return ['propuestas' => 0, 'omitidas' => 0, 'avisos' => ['No hay concepto activo de horas extra en el catalogo.']];
        }
        if ($concepto['monto_default'] === null) {
            $avisos[] = 'El concepto "' . $concepto['nombre'] . '" no tiene monto default (tarifa por hora): las propuestas valdran $0 hasta configurarlo.';
        }

        // Asistencias con horas extra del rango sin propuesta previa.
        $st = $this->pdo->prepare(
            "SELECT a.id, a.trabajador_id, a.fecha, a.horas_extra
             FROM trabajador_asistencias a
             INNER JOIN trabajadores t ON t.id = a.trabajador_id AND t.estado = 'activo'
             WHERE a.hotel_id = ? AND a.fecha BETWEEN ? AND ?
               AND a.horas_extra IS NOT NULL AND a.horas_extra > 0
               AND NOT EXISTS (
                   SELECT 1 FROM nomina_incidencias i
                   WHERE i.hotel_id = a.hotel_id
                     AND i.referencia_origen = CONCAT('ASI-', a.id)
                     AND i.estado != 'rechazada'
               )
             ORDER BY a.fecha ASC"
        );
        $st->execute([$hotelId, $fechaInicio, $fechaFin]);
        $asistencias = $st->fetchAll();

        $stInsert = $this->pdo->prepare(
            "INSERT INTO nomina_incidencias
                (hotel_id, trabajador_id, concepto_id, fecha, cantidad, monto, descripcion,
                 origen, referencia_origen, estado, created_by)
             VALUES (?, ?, ?, ?, ?, NULL, ?, 'adaptador', ?, 'pendiente', ?)"
        );

        $propuestas = 0;
        foreach ($asistencias as $a) {
            $stInsert->execute([
                $hotelId,
                (int) $a['trabajador_id'],
                (int) $concepto['id'],
                $a['fecha'],
                number_format((float) $a['horas_extra'], 2, '.', ''),
                'Propuesta del adaptador hotel (asistencia ' . $a['fecha'] . ')',
                'ASI-' . (int) $a['id'],
                $usuarioId,
            ]);
            $propuestas++;
        }

        if ($propuestas > 0) {
            AuditService::record('nomina.adaptador_hotel_propuestas', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'nomina_incidencias',
                'entidad_id' => $fechaInicio . '_' . $fechaFin,
                'descripcion' => 'Adaptador hotel propuso ' . $propuestas . ' incidencia(s) de horas extra',
            ]);
        }

        return ['propuestas' => $propuestas, 'omitidas' => 0, 'avisos' => $avisos];
    }
}
