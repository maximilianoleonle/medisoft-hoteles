<?php
/**
 * Incidencias de nomina normalizadas por concepto (Fase 3 nomina core).
 *
 * Cada incidencia referencia un concepto del catalogo del MISMO hotel.
 * Las manuales nacen aprobadas; las de adaptadores (fases futuras) nacen
 * pendientes y requieren aprobacion humana. Una incidencia usada por un
 * periodo cerrado no puede rechazarse.
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class NominaIncidenciaService {

    private $db;

    public function __construct(?Database $db = null) {
        $this->db = $db ?: Database::getInstance();
    }

    public function listar(int $hotelId, array $filtros = []): array {
        $sql = "SELECT i.*, c.nombre AS concepto_nombre, c.tipo AS concepto_tipo,
                       c.clasificacion, t.nombre_completo AS trabajador_nombre
                FROM nomina_incidencias i
                INNER JOIN nomina_conceptos c ON c.id = i.concepto_id
                INNER JOIN trabajadores t ON t.id = i.trabajador_id
                WHERE i.hotel_id = ?";
        $params = [$hotelId];

        if (!empty($filtros['desde'])) {
            $sql .= " AND i.fecha >= ?";
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $sql .= " AND i.fecha <= ?";
            $params[] = $filtros['hasta'];
        }
        if (!empty($filtros['estado']) && in_array($filtros['estado'], ['pendiente', 'aprobada', 'rechazada'], true)) {
            $sql .= " AND i.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['trabajador_id'])) {
            $sql .= " AND i.trabajador_id = ?";
            $params[] = (int) $filtros['trabajador_id'];
        }

        $sql .= " ORDER BY i.fecha DESC, i.id DESC LIMIT 500";

        $st = $this->db->query($sql, $params);
        return $st !== false ? $st->fetchAll() : [];
    }

    public function registrar(int $hotelId, array $datos, ?int $usuarioId = null): int {
        $trabajadorId = (int) ($datos['trabajador_id'] ?? 0);
        $conceptoId = (int) ($datos['concepto_id'] ?? 0);

        $st = $this->db->query(
            "SELECT id, nombre_completo, estado FROM trabajadores WHERE id = ? AND hotel_id = ?",
            [$trabajadorId, $hotelId]
        );
        $trabajador = $st !== false ? $st->fetch() : null;
        if (!$trabajador) {
            throw new Exception('El trabajador no existe en este negocio.');
        }
        if ($trabajador['estado'] !== 'activo') {
            throw new Exception('Solo se registran incidencias de trabajadores activos.');
        }

        $st = $this->db->query(
            "SELECT * FROM nomina_conceptos WHERE id = ? AND hotel_id = ? AND activo = 1",
            [$conceptoId, $hotelId]
        );
        $concepto = $st !== false ? $st->fetch() : null;
        if (!$concepto) {
            throw new Exception('El concepto no existe o esta inactivo en este negocio.');
        }

        // Politicas del negocio (configuracion de nomina).
        if ($concepto['clasificacion'] === 'horas_extra'
            && !ConfiguracionHotelRegistry::getBool('nomina.permitir_horas_extra', true, $hotelId)) {
            throw new Exception('Este negocio no permite registrar horas extra.');
        }
        if ($concepto['tipo'] === 'deduccion' && $concepto['clasificacion'] === 'descuento'
            && !ConfiguracionHotelRegistry::getBool('nomina.permitir_descuentos_manuales', true, $hotelId)) {
            throw new Exception('Este negocio no permite descuentos manuales.');
        }

        $fecha = $this->normalizarFecha((string) ($datos['fecha'] ?? ''));
        $this->assertFechaFueraDePeriodoCongelado($hotelId, $trabajadorId, $fecha);

        $cantidad = null;
        if (isset($datos['cantidad']) && $datos['cantidad'] !== '') {
            $cantidad = round((float) $datos['cantidad'], 2);
            if ($cantidad <= 0) {
                throw new Exception('La cantidad debe ser mayor a cero.');
            }
        }

        $monto = null;
        if (isset($datos['monto']) && $datos['monto'] !== '') {
            $monto = round((float) $datos['monto'], 2);
            if ($monto < 0) {
                throw new Exception('El monto no puede ser negativo.');
            }
        }

        if ($concepto['modo_calculo'] === 'por_cantidad') {
            if ($cantidad === null) {
                throw new Exception('Este concepto se calcula por cantidad: captura la cantidad.');
            }
            if ($monto === null && $concepto['monto_default'] === null) {
                throw new Exception('Este concepto no tiene monto default: captura el monto por unidad total.');
            }
        } else {
            if ($monto === null && $concepto['monto_default'] === null) {
                throw new Exception('Captura el monto de la incidencia.');
            }
        }

        $descripcion = trim((string) ($datos['descripcion'] ?? ''));
        $descripcion = $descripcion === '' ? null : mb_substr($descripcion, 0, 200);

        $st = $this->db->query(
            "INSERT INTO nomina_incidencias
                (hotel_id, trabajador_id, concepto_id, fecha, cantidad, monto, descripcion,
                 origen, estado, aprobado_por, aprobado_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'manual', 'aprobada', ?, NOW(), ?)",
            [
                $hotelId, $trabajadorId, $conceptoId, $fecha,
                $cantidad !== null ? number_format($cantidad, 2, '.', '') : null,
                $monto !== null ? number_format($monto, 2, '.', '') : null,
                $descripcion, $usuarioId, $usuarioId,
            ]
        );
        if ($st === false) {
            throw new Exception('No se pudo registrar la incidencia.');
        }

        $incidenciaId = (int) $this->db->lastInsertId();

        AuditService::record('nomina.incidencia_registrada', [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => 'nomina_incidencias',
            'entidad_id' => (string) $incidenciaId,
            'descripcion' => 'Incidencia ' . $concepto['nombre'] . ' de ' . $trabajador['nombre_completo'] . ' el ' . $fecha,
            'datos_despues' => [
                'concepto' => $concepto['nombre'],
                'fecha' => $fecha,
                'cantidad' => $cantidad,
                'monto' => $monto,
            ],
        ]);

        return $incidenciaId;
    }

    public function cambiarEstado(int $hotelId, int $incidenciaId, string $estado, ?int $usuarioId = null): bool {
        if (!in_array($estado, ['aprobada', 'rechazada'], true)) {
            throw new Exception('Estado de incidencia no valido.');
        }

        $st = $this->db->query(
            "SELECT i.*, c.nombre AS concepto_nombre FROM nomina_incidencias i
             INNER JOIN nomina_conceptos c ON c.id = i.concepto_id
             WHERE i.id = ? AND i.hotel_id = ?",
            [$incidenciaId, $hotelId]
        );
        $incidencia = $st !== false ? $st->fetch() : null;
        if (!$incidencia) {
            throw new Exception('La incidencia no existe en este negocio.');
        }
        if ($incidencia['estado'] === $estado) {
            return true;
        }

        // Una incidencia ya congelada en un periodo NO anulado no puede cambiar.
        $st = $this->db->query(
            "SELECT COUNT(*) AS total
             FROM nomina_periodo_conceptos l
             INNER JOIN trabajador_nomina_periodos p ON p.id = l.periodo_id
             WHERE l.hotel_id = ? AND l.referencia = ? AND p.estado != 'anulado'",
            [$hotelId, 'INC-' . $incidenciaId]
        );
        $usada = $st !== false ? (int) ($st->fetch()['total'] ?? 0) : 0;
        if ($usada > 0) {
            throw new Exception('La incidencia ya forma parte de un periodo cerrado: anula el periodo antes de modificarla.');
        }

        // Aprobar una pendiente cuya fecha ya quedo dentro de un periodo
        // cerrado la dejaria huerfana (nunca entraria a ninguna nomina).
        if ($estado === 'aprobada') {
            $this->assertFechaFueraDePeriodoCongelado($hotelId, (int) $incidencia['trabajador_id'], (string) $incidencia['fecha']);
        }

        $st = $this->db->query(
            "UPDATE nomina_incidencias
             SET estado = ?, aprobado_por = ?, aprobado_at = NOW(), updated_by = ?
             WHERE id = ? AND hotel_id = ?",
            [$estado, $usuarioId, $usuarioId, $incidenciaId, $hotelId]
        );
        if ($st === false) {
            throw new Exception('No se pudo actualizar la incidencia.');
        }

        AuditService::record('nomina.incidencia_' . $estado, [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => 'nomina_incidencias',
            'entidad_id' => (string) $incidenciaId,
            'descripcion' => 'Incidencia ' . $incidencia['concepto_nombre'] . ' marcada como ' . $estado,
            'datos_antes' => ['estado' => $incidencia['estado']],
            'datos_despues' => ['estado' => $estado],
        ]);

        return true;
    }

    /**
     * Una incidencia cuya fecha cae dentro de un periodo v2 NO anulado del
     * trabajador quedaria HUERFANA: ese periodo ya congelo su snapshot y
     * ningun periodo futuro puede solapar el rango, asi que jamas se pagaria.
     * Mismo candado que el ledger v1 (Trabajador::assertFechaFueraDePeriodoNominaCongelado).
     */
    private function assertFechaFueraDePeriodoCongelado(int $hotelId, int $trabajadorId, string $fecha): void {
        $st = $this->db->query(
            "SELECT grupo_nomina_id FROM trabajadores WHERE id = ? AND hotel_id = ?",
            [$trabajadorId, $hotelId]
        );
        $grupoId = $st !== false ? (int) (($st->fetch()['grupo_nomina_id'] ?? 0)) : 0;

        $st = $this->db->query(
            "SELECT p.etiqueta, p.fecha_inicio, p.fecha_fin
             FROM trabajador_nomina_periodos p
             WHERE p.hotel_id = ? AND p.motor = 'v2' AND p.estado != 'anulado'
               AND ? BETWEEN p.fecha_inicio AND p.fecha_fin
               AND (
                    (p.grupo_nomina_id IS NOT NULL AND p.grupo_nomina_id = ?)
                 OR EXISTS (
                        SELECT 1 FROM trabajador_nomina_periodo_detalles d
                        WHERE d.periodo_id = p.id AND d.trabajador_id = ?
                    )
               )
             LIMIT 1",
            [$hotelId, $fecha, $grupoId, $trabajadorId]
        );
        $periodo = $st !== false ? $st->fetch() : null;
        if ($periodo) {
            throw new Exception(
                'La fecha cae dentro del periodo de nomina ya cerrado "' . (string) $periodo['etiqueta'] . '" ('
                . (string) $periodo['fecha_inicio'] . ' a ' . (string) $periodo['fecha_fin']
                . '): la incidencia no entraria a esa nomina ni a ninguna futura. Captura una fecha posterior al periodo o anula el periodo.'
            );
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
