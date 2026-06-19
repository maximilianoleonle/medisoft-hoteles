<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../services/AuditService.php';

class CuentaPorCobrar extends Model
{
    protected $table = 'reservaciones';

    public function tablasDisponibles(): bool
    {
        foreach (['reservaciones', 'huespedes', 'reservacion_pagos', 'reservacion_abonos', 'solicitudes_factura'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function tablaOperativaDisponible(): bool
    {
        return $this->tablaExiste('cuentas_por_cobrar');
    }

    public function movimientosOperativosDisponibles(): bool
    {
        return $this->tablaExiste('cuentas_por_cobrar_movimientos');
    }

    public function tablasGeneracionManualDisponibles(): bool
    {
        foreach ([
            'reservaciones',
            'huespedes',
            'reservacion_pagos',
            'reservacion_abonos',
            'solicitudes_factura',
            'cuentas_por_cobrar',
            'cuentas_por_cobrar_movimientos',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function tablasSimuladorCajaDisponibles(): bool
    {
        foreach ([
            'cuentas_por_cobrar',
            'cuentas_por_cobrar_movimientos',
            'huespedes',
            'reservaciones',
            'solicitudes_factura',
            'cajas',
            'cortes_caja',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function listarDerivadasPorHotel(int $hotelId, array $filtros = [], int $limite = 200): array
    {
        if ($hotelId <= 0 || !$this->tablasDisponibles()) {
            return [];
        }

        $limite = max(1, min(500, $limite));
        $where = ['r.hotel_id = ?'];
        $params = [$hotelId, $hotelId, $hotelId, $hotelId];

        $estadoReservacion = $this->normalizarEstadoReservacion($filtros['estado_reservacion'] ?? 'todas');
        if ($estadoReservacion !== 'todas') {
            $where[] = 'r.estado = ?';
            $params[] = $estadoReservacion;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(CAST(r.id AS CHAR) LIKE ? OR h.nombre_completo LIKE ? OR h.telefono LIKE ? OR h.email LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT r.id AS reservacion_id,
                    r.hotel_id,
                    r.huesped_id,
                    h.nombre_completo AS huesped_nombre,
                    h.telefono AS huesped_telefono,
                    h.email AS huesped_email,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.estado AS reservacion_estado,
                    r.precio_total,
                    COALESCE(p.pagos_total, 0) AS pagos_total,
                    COALESCE(p.pagos_count, 0) AS pagos_count,
                    COALESCE(a.abonos_total, 0) AS abonos_total,
                    COALESCE(a.abonos_count, 0) AS abonos_count,
                    sf.solicitud_factura_id,
                    COALESCE(sf.solicitud_factura_count, 0) AS solicitud_factura_count,
                    sf.factura_estatus,
                    sf.requiere_factura,
                    sf.numero_factura,
                    (r.precio_total - COALESCE(p.pagos_total, 0) - COALESCE(a.abonos_total, 0)) AS saldo_estimado_raw
             FROM reservaciones r
             INNER JOIN huespedes h
                ON h.id = r.huesped_id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        COUNT(*) AS pagos_count,
                        SUM(monto) AS pagos_total
                 FROM reservacion_pagos
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) p
                ON p.hotel_id = r.hotel_id
               AND p.reservacion_id = r.id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        COUNT(*) AS abonos_count,
                        SUM(monto) AS abonos_total
                 FROM reservacion_abonos
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) a
                ON a.hotel_id = r.hotel_id
               AND a.reservacion_id = r.id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        MAX(id) AS solicitud_factura_id,
                        COUNT(*) AS solicitud_factura_count,
                        MAX(estatus) AS factura_estatus,
                        MAX(requiere_factura) AS requiere_factura,
                        MAX(numero_factura) AS numero_factura
                 FROM solicitudes_factura
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) sf
                ON sf.hotel_id = r.hotel_id
               AND sf.reservacion_id = r.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                CASE WHEN (r.precio_total - COALESCE(p.pagos_total, 0) - COALESCE(a.abonos_total, 0)) > 0 THEN 0 ELSE 1 END,
                r.fecha_entrada DESC,
                r.id DESC
             LIMIT {$limite}",
            $params
        );

        $filas = $stmt ? ($stmt->fetchAll() ?: []) : [];
        $cuentas = array_map([$this, 'normalizarCuentaDerivada'], $filas);
        $estadoSaldo = $this->normalizarEstadoSaldo($filtros['estado_saldo'] ?? 'todas');

        if ($estadoSaldo !== 'todas') {
            $cuentas = array_values(array_filter($cuentas, static function (array $cuenta) use ($estadoSaldo): bool {
                return (string)($cuenta['estado_saldo'] ?? '') === $estadoSaldo;
            }));
        }

        $cuentas = array_slice($cuentas, 0, $limite);

        return $this->anotarGeneracionManual($cuentas, $hotelId);
    }

    public function resumenPorCuentas(array $cuentas): array
    {
        $resumen = [
            'total' => count($cuentas),
            'pendientes' => 0,
            'liquidadas' => 0,
            'excedentes' => 0,
            'total_reservado' => '0.00',
            'total_cubierto' => '0.00',
            'saldo_estimado' => '0.00',
        ];

        foreach ($cuentas as $cuenta) {
            $estado = (string)($cuenta['estado_saldo'] ?? '');
            if ($estado === 'pendiente') {
                $resumen['pendientes']++;
            } elseif ($estado === 'liquidada') {
                $resumen['liquidadas']++;
            } elseif ($estado === 'excedente') {
                $resumen['excedentes']++;
            }

            $resumen['total_reservado'] = $this->decimal((float)$resumen['total_reservado'] + (float)($cuenta['precio_total'] ?? 0));
            $resumen['total_cubierto'] = $this->decimal((float)$resumen['total_cubierto'] + (float)($cuenta['monto_cubierto'] ?? 0));
            $resumen['saldo_estimado'] = $this->decimal((float)$resumen['saldo_estimado'] + (float)($cuenta['saldo_estimado'] ?? 0));
        }

        return $resumen;
    }

    public function resumenOperativasPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaOperativaDisponible()) {
            return $this->resumenOperativoVacio();
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado = 'parcial' THEN 1 ELSE 0 END) AS parciales,
                    SUM(CASE WHEN estado = 'liquidada' THEN 1 ELSE 0 END) AS liquidadas,
                    SUM(CASE WHEN estado = 'vencida' THEN 1 ELSE 0 END) AS vencidas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                    SUM(CASE WHEN estado = 'incobrable' THEN 1 ELSE 0 END) AS incobrables,
                    COALESCE(SUM(total), 0) AS total_importe,
                    COALESCE(SUM(saldo), 0) AS saldo_total,
                    COALESCE(SUM(CASE WHEN fecha_vencimiento < CURDATE()
                                      AND estado IN ('pendiente', 'parcial', 'vencida')
                                      THEN saldo ELSE 0 END), 0) AS saldo_vencido
             FROM cuentas_por_cobrar
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'pendientes' => (int)($row['pendientes'] ?? 0),
            'parciales' => (int)($row['parciales'] ?? 0),
            'liquidadas' => (int)($row['liquidadas'] ?? 0),
            'vencidas' => (int)($row['vencidas'] ?? 0),
            'canceladas' => (int)($row['canceladas'] ?? 0),
            'incobrables' => (int)($row['incobrables'] ?? 0),
            'total_importe' => $this->decimal($row['total_importe'] ?? 0),
            'saldo_total' => $this->decimal($row['saldo_total'] ?? 0),
            'saldo_vencido' => $this->decimal($row['saldo_vencido'] ?? 0),
        ];
    }

    public function listarOperativasPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        if ($hotelId <= 0 || !$this->tablaOperativaDisponible()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $where = ['cxc.hotel_id = ?'];
        $params = [$hotelId];

        $estado = $this->normalizarEstadoOperativo($filtros['estado'] ?? 'todos');
        if ($estado !== 'todos') {
            $where[] = 'cxc.estado = ?';
            $params[] = $estado;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(CAST(cxc.id AS CHAR) LIKE ? OR cxc.folio LIKE ? OR cxc.concepto LIKE ? OR h.nombre_completo LIKE ? OR sf.numero_factura LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT cxc.*,
                    h.nombre_completo AS huesped_nombre,
                    h.telefono AS huesped_telefono,
                    h.email AS huesped_email,
                    r.estado AS reservacion_estado,
                    r.fecha_entrada,
                    r.fecha_salida,
                    sf.numero_factura,
                    sf.estatus AS factura_estatus
             FROM cuentas_por_cobrar cxc
             LEFT JOIN huespedes h
                ON h.id = cxc.huesped_id
             LEFT JOIN reservaciones r
                ON r.id = cxc.reservacion_id
               AND r.hotel_id = cxc.hotel_id
             LEFT JOIN solicitudes_factura sf
                ON sf.id = cxc.solicitud_factura_id
               AND sf.hotel_id = cxc.hotel_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                CASE
                    WHEN cxc.estado IN ('pendiente', 'parcial', 'vencida') THEN 0
                    ELSE 1
                END,
                cxc.fecha_vencimiento IS NULL ASC,
                cxc.fecha_vencimiento ASC,
                cxc.id DESC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function buscarOperativaPorIdHotel(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaOperativaDisponible()) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT cxc.*,
                    h.nombre_completo AS huesped_nombre,
                    h.telefono AS huesped_telefono,
                    h.email AS huesped_email,
                    r.estado AS reservacion_estado,
                    r.fecha_entrada,
                    r.fecha_salida,
                    sf.numero_factura,
                    sf.estatus AS factura_estatus
             FROM cuentas_por_cobrar cxc
             LEFT JOIN huespedes h
                ON h.id = cxc.huesped_id
             LEFT JOIN reservaciones r
                ON r.id = cxc.reservacion_id
               AND r.hotel_id = cxc.hotel_id
             LEFT JOIN solicitudes_factura sf
                ON sf.id = cxc.solicitud_factura_id
               AND sf.hotel_id = cxc.hotel_id
             WHERE cxc.id = ?
               AND cxc.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function movimientosOperativosPorCuenta(int $id, int $hotelId, int $limite = 100): array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->movimientosOperativosDisponibles()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $stmt = $this->db->query(
            "SELECT *
             FROM cuentas_por_cobrar_movimientos
             WHERE cuenta_por_cobrar_id = ?
               AND hotel_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT {$limite}",
            [$id, $hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function simuladorCajaCliente(int $hotelId, array $filtros = [], int $limite = 200): array
    {
        $corte = $this->corteAbiertoSimuladorCaja($hotelId);
        $cuentas = [];

        if ($hotelId > 0 && $this->tablasSimuladorCajaDisponibles()) {
            $limite = max(1, min(300, $limite));
            $where = ['cxc.hotel_id = ?'];
            $params = [$hotelId];

            $estado = $this->normalizarEstadoOperativo($filtros['estado'] ?? 'todos');
            if ($estado !== 'todos') {
                $where[] = 'cxc.estado = ?';
                $params[] = $estado;
            }

            $buscar = trim((string)($filtros['buscar'] ?? ''));
            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $where[] = '(CAST(cxc.id AS CHAR) LIKE ? OR cxc.folio LIKE ? OR cxc.concepto LIKE ? OR h.nombre_completo LIKE ? OR sf.numero_factura LIKE ? OR CAST(cxc.reservacion_id AS CHAR) LIKE ?)';
                array_push($params, $like, $like, $like, $like, $like, $like);
            }

            $stmt = $this->db->query(
                "SELECT cxc.*,
                        h.nombre_completo AS huesped_nombre,
                        h.telefono AS huesped_telefono,
                        h.email AS huesped_email,
                        r.id AS reservacion_hotel_id,
                        r.estado AS reservacion_estado,
                        r.fecha_entrada,
                        r.fecha_salida,
                        sf.id AS factura_hotel_id,
                        sf.numero_factura,
                        sf.estatus AS factura_estatus
                 FROM cuentas_por_cobrar cxc
                 LEFT JOIN huespedes h
                    ON h.id = cxc.huesped_id
                 LEFT JOIN reservaciones r
                    ON r.id = cxc.reservacion_id
                   AND r.hotel_id = cxc.hotel_id
                 LEFT JOIN solicitudes_factura sf
                    ON sf.id = cxc.solicitud_factura_id
                   AND sf.hotel_id = cxc.hotel_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY
                    CASE
                        WHEN cxc.estado IN ('pendiente', 'parcial', 'vencida')
                         AND cxc.saldo > 0
                        THEN 0
                        ELSE 1
                    END,
                    cxc.fecha_vencimiento IS NULL ASC,
                    cxc.fecha_vencimiento ASC,
                    cxc.id DESC
                 LIMIT {$limite}",
                $params
            );

            $tipoCobroDisponible = $this->tipoMovimientoCobroDisponible();
            $filas = $stmt ? ($stmt->fetchAll() ?: []) : [];
            foreach ($filas as $fila) {
                $cuentas[] = $this->evaluarSimuladorCaja($fila, $corte, $tipoCobroDisponible);
            }
        }

        return [
            'corte' => $corte,
            'cuentas' => $cuentas,
            'resumen' => $this->resumenSimuladorCaja($cuentas, $corte),
            'tipo_cobro_disponible' => $this->tipoMovimientoCobroDisponible(),
        ];
    }

    public function generarDesdeReservacionElegible(int $hotelId, int $reservacionId, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $reservacionId = $this->validarId($reservacionId, 'Reservacion invalida');
        $usuarioId = $this->normalizarUsuarioId($usuarioId);
        $pdo = $this->db->getConnection();

        if (!$this->tablasGeneracionManualDisponibles()) {
            throw new Exception('Tablas requeridas de reservaciones y CxC no disponibles');
        }

        if ($pdo->inTransaction()) {
            throw new Exception('La generacion de CxC debe controlar su propia transaccion');
        }

        $pdo->beginTransaction();

        try {
            $reservacion = $this->obtenerReservacionParaGeneracion($hotelId, $reservacionId);
            $saldo = $this->assertReservacionGenerable($reservacion);
            $this->assertSinCxcParaReservacion($hotelId, $reservacionId);

            $total = $this->decimal($saldo);
            $folio = 'CXC-RES-' . $reservacionId;
            $concepto = 'Saldo pendiente de reservacion #' . $reservacionId;
            $fechaEmision = date('Y-m-d');
            $fechaVencimiento = $this->normalizarFechaVencimiento($reservacion['fecha_salida'] ?? null);
            $solicitudFacturaId = !empty($reservacion['solicitud_factura_id'])
                ? (int)$reservacion['solicitud_factura_id']
                : null;
            $notas = 'Generada manualmente desde reservacion #' . $reservacionId
                . ' por saldo pendiente neto. Sin cobro, sin pagos y sin Caja automatica.';

            $stmt = $pdo->prepare(
                "INSERT INTO cuentas_por_cobrar
                    (hotel_id, origen_tipo, origen_id, huesped_id, reservacion_id,
                     solicitud_factura_id, folio, concepto, fecha_emision, fecha_vencimiento,
                     estado, moneda, total, saldo, notas,
                     creado_por_usuario_id, actualizado_por_usuario_id, created_at, updated_at)
                 VALUES
                    (?, 'reservacion', ?, ?, ?, ?, ?, ?, ?, ?,
                     'pendiente', 'MXN', ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $hotelId,
                $reservacionId,
                (int)$reservacion['huesped_id'],
                $reservacionId,
                $solicitudFacturaId,
                $folio,
                $concepto,
                $fechaEmision,
                $fechaVencimiento,
                $total,
                $total,
                $notas,
                $usuarioId,
                $usuarioId,
            ]);

            $cxcId = (int)$pdo->lastInsertId();

            $stmtMovimiento = $pdo->prepare(
                "INSERT INTO cuentas_por_cobrar_movimientos
                    (hotel_id, cuenta_por_cobrar_id, tipo_movimiento, monto,
                     saldo_anterior, saldo_posterior, referencia, notas, usuario_id, created_at)
                 VALUES
                    (?, ?, 'CREACION', ?, 0.00, ?, ?, ?, ?, NOW())"
            );
            $stmtMovimiento->execute([
                $hotelId,
                $cxcId,
                $total,
                $total,
                $folio,
                'Movimiento inicial por generacion manual desde reservacion. Sin Caja.',
                $usuarioId,
            ]);

            $movimientoId = (int)$pdo->lastInsertId();

            AuditService::record('cuentas_por_cobrar.generada_desde_reservacion', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'cuentas_por_cobrar',
                'entidad_id' => (string)$cxcId,
                'descripcion' => 'CxC generada manualmente desde saldo pendiente de reservacion',
                'datos_despues' => [
                    'cxc_id' => $cxcId,
                    'movimiento_id' => $movimientoId,
                    'reservacion_id' => $reservacionId,
                    'huesped_id' => (int)$reservacion['huesped_id'],
                    'huesped_nombre' => $reservacion['huesped_nombre'] ?? null,
                    'solicitud_factura_id' => $solicitudFacturaId,
                    'estado' => 'pendiente',
                    'total' => $total,
                    'saldo' => $total,
                    'precio_total_reservacion' => $this->decimal($reservacion['precio_total'] ?? 0),
                    'pagos_total' => $this->decimal($reservacion['pagos_total'] ?? 0),
                    'abonos_total' => $this->decimal($reservacion['abonos_total'] ?? 0),
                    'sin_caja' => true,
                    'sin_pagos' => true,
                    'sin_modificar_reservacion' => true,
                ],
            ]);

            $pdo->commit();

            return [
                'cxc_id' => $cxcId,
                'movimiento_id' => $movimientoId,
                'reservacion_id' => $reservacionId,
                'hotel_id' => $hotelId,
                'saldo' => $total,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    private function anotarGeneracionManual(array $cuentas, int $hotelId): array
    {
        if (empty($cuentas) || $hotelId <= 0) {
            return $cuentas;
        }

        $generacionDisponible = $this->tablasGeneracionManualDisponibles();
        $existentes = $generacionDisponible ? $this->cxcExistentesPorReservacion($cuentas, $hotelId) : [];

        foreach ($cuentas as &$cuenta) {
            $reservacionId = (int)($cuenta['reservacion_id'] ?? 0);
            $existente = $existentes[$reservacionId] ?? null;
            $bloqueo = '';

            $cuenta['cxc_operativa_id'] = $existente ? (int)$existente['id'] : null;
            $cuenta['cxc_operativa_estado'] = $existente ? (string)($existente['estado'] ?? '') : null;

            if (!$generacionDisponible) {
                $bloqueo = 'Faltan tablas operativas de CxC.';
            } elseif ($reservacionId <= 0) {
                $bloqueo = 'La reservacion no es valida.';
            } elseif ($existente) {
                $bloqueo = 'Ya existe una CxC operativa para esta reservacion.';
            } elseif ((string)($cuenta['reservacion_estado'] ?? '') === 'cancelada') {
                $bloqueo = 'La reservacion esta cancelada.';
            } elseif ((string)($cuenta['estado_saldo'] ?? '') !== 'pendiente') {
                $bloqueo = 'La reservacion no tiene saldo pendiente elegible.';
            } elseif ((float)($cuenta['saldo_estimado'] ?? 0) <= 0.009) {
                $bloqueo = 'El saldo pendiente neto no es mayor a cero.';
            } elseif ((int)($cuenta['huesped_id'] ?? 0) <= 0) {
                $bloqueo = 'La reservacion no tiene huesped valido.';
            }

            $cuenta['es_elegible_generacion_cxc'] = $bloqueo === '';
            $cuenta['motivo_generacion_cxc'] = $bloqueo === ''
                ? 'Saldo pendiente neto elegible; la accion crea CxC y movimiento inicial, sin Caja.'
                : '';
            $cuenta['motivo_bloqueo_generacion_cxc'] = $bloqueo;
        }
        unset($cuenta);

        return $cuentas;
    }

    private function cxcExistentesPorReservacion(array $cuentas, int $hotelId): array
    {
        $reservacionIds = [];
        foreach ($cuentas as $cuenta) {
            $reservacionId = (int)($cuenta['reservacion_id'] ?? 0);
            if ($reservacionId > 0) {
                $reservacionIds[$reservacionId] = $reservacionId;
            }
        }

        if (empty($reservacionIds) || !$this->tablaOperativaDisponible()) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($reservacionIds), '?'));
        $params = array_merge([$hotelId], array_values($reservacionIds));
        $stmt = $this->db->query(
            "SELECT origen_id, MIN(id) AS id, MIN(estado) AS estado
             FROM cuentas_por_cobrar
             WHERE hotel_id = ?
               AND origen_tipo = 'reservacion'
               AND origen_id IN ({$placeholders})
             GROUP BY origen_id",
            $params
        );

        $existentes = [];
        foreach ($stmt ? ($stmt->fetchAll() ?: []) : [] as $row) {
            $existentes[(int)$row['origen_id']] = $row;
        }

        return $existentes;
    }

    private function obtenerReservacionParaGeneracion(int $hotelId, int $reservacionId): array
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare(
            "SELECT r.id AS reservacion_id,
                    r.hotel_id,
                    r.huesped_id,
                    h.nombre_completo AS huesped_nombre,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.estado AS reservacion_estado,
                    r.precio_total,
                    COALESCE(p.pagos_total, 0) AS pagos_total,
                    COALESCE(p.pagos_count, 0) AS pagos_count,
                    COALESCE(a.abonos_total, 0) AS abonos_total,
                    COALESCE(a.abonos_count, 0) AS abonos_count,
                    sf.solicitud_factura_id,
                    sf.numero_factura,
                    sf.factura_estatus
             FROM reservaciones r
             INNER JOIN huespedes h
                ON h.id = r.huesped_id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        COUNT(*) AS pagos_count,
                        SUM(monto) AS pagos_total
                 FROM reservacion_pagos
                 WHERE hotel_id = ?
                   AND reservacion_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) p
                ON p.hotel_id = r.hotel_id
               AND p.reservacion_id = r.id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        COUNT(*) AS abonos_count,
                        SUM(monto) AS abonos_total
                 FROM reservacion_abonos
                 WHERE hotel_id = ?
                   AND reservacion_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) a
                ON a.hotel_id = r.hotel_id
               AND a.reservacion_id = r.id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        MAX(id) AS solicitud_factura_id,
                        MAX(numero_factura) AS numero_factura,
                        MAX(estatus) AS factura_estatus
                 FROM solicitudes_factura
                 WHERE hotel_id = ?
                   AND reservacion_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) sf
                ON sf.hotel_id = r.hotel_id
               AND sf.reservacion_id = r.id
             WHERE r.id = ?
               AND r.hotel_id = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([
            $hotelId,
            $reservacionId,
            $hotelId,
            $reservacionId,
            $hotelId,
            $reservacionId,
            $reservacionId,
            $hotelId,
        ]);
        $reservacion = $stmt->fetch();

        if (!$reservacion) {
            throw new Exception('Reservacion no encontrada para el hotel actual');
        }

        return $reservacion;
    }

    private function assertReservacionGenerable(array $reservacion): float
    {
        if ((string)($reservacion['reservacion_estado'] ?? '') === 'cancelada') {
            throw new Exception('No se puede generar CxC desde una reservacion cancelada');
        }

        if ((int)($reservacion['huesped_id'] ?? 0) <= 0) {
            throw new Exception('La reservacion no tiene huesped valido');
        }

        $total = (float)($reservacion['precio_total'] ?? 0);
        $pagos = (float)($reservacion['pagos_total'] ?? 0);
        $abonos = (float)($reservacion['abonos_total'] ?? 0);
        $saldo = $total - $pagos - $abonos;

        if ($total <= 0) {
            throw new Exception('El total de la reservacion no es valido para CxC');
        }

        if ($saldo < -0.009) {
            throw new Exception('La reservacion tiene excedente; no se genera CxC');
        }

        if ($saldo <= 0.009) {
            throw new Exception('La reservacion no tiene saldo pendiente para CxC');
        }

        return $saldo;
    }

    private function assertSinCxcParaReservacion(int $hotelId, int $reservacionId): void
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare(
            "SELECT id
             FROM cuentas_por_cobrar
             WHERE hotel_id = ?
               AND origen_tipo = 'reservacion'
               AND origen_id = ?
             ORDER BY id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$hotelId, $reservacionId]);
        $existente = $stmt->fetch();

        if ($existente) {
            throw new Exception('Ya existe una cuenta por cobrar para esta reservacion: #' . (int)$existente['id']);
        }
    }

    private function normalizarCuentaDerivada(array $cuenta): array
    {
        $total = (float)($cuenta['precio_total'] ?? 0);
        $pagos = (float)($cuenta['pagos_total'] ?? 0);
        $abonos = (float)($cuenta['abonos_total'] ?? 0);
        $cubierto = $pagos + $abonos;
        $saldoRaw = $total - $cubierto;
        $saldo = max(0, $saldoRaw);

        if ($saldoRaw < -0.009) {
            $estadoSaldo = 'excedente';
        } elseif ($saldo <= 0.009) {
            $estadoSaldo = 'liquidada';
        } else {
            $estadoSaldo = 'pendiente';
        }

        $cuenta['precio_total'] = $this->decimal($total);
        $cuenta['pagos_total'] = $this->decimal($pagos);
        $cuenta['abonos_total'] = $this->decimal($abonos);
        $cuenta['monto_cubierto'] = $this->decimal($cubierto);
        $cuenta['saldo_estimado'] = $this->decimal($saldo);
        $cuenta['saldo_estimado_raw'] = $this->decimal($saldoRaw);
        $cuenta['estado_saldo'] = $estadoSaldo;

        return $cuenta;
    }

    private function normalizarEstadoReservacion($value): string
    {
        $estado = (string)($value ?? 'todas');
        return in_array($estado, ['confirmada', 'checked_in', 'checked_out', 'cancelada', 'todas'], true)
            ? $estado
            : 'todas';
    }

    private function normalizarEstadoSaldo($value): string
    {
        $estado = (string)($value ?? 'todas');
        return in_array($estado, ['pendiente', 'liquidada', 'excedente', 'todas'], true)
            ? $estado
            : 'todas';
    }

    private function normalizarEstadoOperativo($value): string
    {
        $estado = (string)($value ?? 'todos');
        return in_array($estado, ['pendiente', 'parcial', 'liquidada', 'vencida', 'cancelada', 'incobrable', 'todos'], true)
            ? $estado
            : 'todos';
    }

    private function resumenOperativoVacio(): array
    {
        return [
            'total' => 0,
            'pendientes' => 0,
            'parciales' => 0,
            'liquidadas' => 0,
            'vencidas' => 0,
            'canceladas' => 0,
            'incobrables' => 0,
            'total_importe' => '0.00',
            'saldo_total' => '0.00',
            'saldo_vencido' => '0.00',
        ];
    }

    private function corteAbiertoSimuladorCaja(int $hotelId): ?array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('cajas') || !$this->tablaExiste('cortes_caja')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT cc.id,
                    cc.hotel_id,
                    cc.caja_id,
                    cc.fecha_apertura,
                    cc.monto_inicial,
                    cc.estado,
                    c.nombre AS caja_nombre,
                    c.ubicacion AS caja_ubicacion
             FROM cortes_caja cc
             INNER JOIN cajas c
                ON c.id = cc.caja_id
               AND c.hotel_id = cc.hotel_id
             WHERE cc.hotel_id = ?
               AND cc.estado = 'abierto'
               AND COALESCE(c.activa, 1) = 1
             ORDER BY cc.fecha_apertura DESC, cc.id DESC
             LIMIT 1",
            [$hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    private function resumenSimuladorCaja(array $cuentas, ?array $corte): array
    {
        $resumen = [
            'total' => count($cuentas),
            'elegibles' => 0,
            'bloqueadas' => 0,
            'sin_corte' => empty($corte) ? 1 : 0,
            'saldo_elegible' => '0.00',
            'saldo_revisado' => '0.00',
        ];

        foreach ($cuentas as $cuenta) {
            $saldo = (float)($cuenta['saldo'] ?? 0);
            $resumen['saldo_revisado'] = $this->decimal((float)$resumen['saldo_revisado'] + $saldo);

            if (!empty($cuenta['es_elegible_caja'])) {
                $resumen['elegibles']++;
                $resumen['saldo_elegible'] = $this->decimal((float)$resumen['saldo_elegible'] + $saldo);
            } else {
                $resumen['bloqueadas']++;
            }
        }

        return $resumen;
    }

    private function evaluarSimuladorCaja(array $cuenta, ?array $corte, bool $tipoCobroDisponible): array
    {
        $bloqueos = [];

        if (empty($corte)) {
            $bloqueos[] = 'No hay corte de Caja abierto para el hotel actual.';
        }

        if (!$tipoCobroDisponible) {
            $bloqueos[] = 'El esquema CxC aun no tiene tipo de movimiento COBRO aprobado.';
        }

        if (empty($cuenta['hotel_id']) || (int)$cuenta['hotel_id'] <= 0) {
            $bloqueos[] = 'La cuenta no tiene hotel_id valido.';
        }

        if (!in_array((string)($cuenta['estado'] ?? ''), ['pendiente', 'parcial', 'vencida'], true)) {
            $bloqueos[] = 'El estado de la cuenta no permite cobro.';
        }

        if ((float)($cuenta['total'] ?? 0) <= 0) {
            $bloqueos[] = 'El total de la cuenta no es valido.';
        }

        if ((float)($cuenta['saldo'] ?? 0) <= 0) {
            $bloqueos[] = 'La cuenta no tiene saldo pendiente.';
        }

        if ((float)($cuenta['saldo'] ?? 0) > (float)($cuenta['total'] ?? 0)) {
            $bloqueos[] = 'El saldo de la cuenta es mayor al total.';
        }

        if (!empty($cuenta['reservacion_id']) && empty($cuenta['reservacion_hotel_id'])) {
            $bloqueos[] = 'La reservacion vinculada no pertenece al hotel actual o no existe.';
        }

        if (!empty($cuenta['reservacion_estado']) && (string)$cuenta['reservacion_estado'] === 'cancelada') {
            $bloqueos[] = 'La reservacion vinculada esta cancelada.';
        }

        if (!empty($cuenta['solicitud_factura_id']) && empty($cuenta['factura_hotel_id'])) {
            $bloqueos[] = 'La factura vinculada no pertenece al hotel actual o no existe.';
        }

        $cuenta['es_elegible_caja'] = empty($bloqueos);
        $cuenta['motivo_elegibilidad_caja'] = empty($bloqueos)
            ? 'Cuenta con saldo, estado cobrable, corte abierto y movimiento COBRO aprobado. Esta pantalla no registra cobros.'
            : '';
        $cuenta['motivo_bloqueo_caja'] = implode(' ', $bloqueos);

        return $cuenta;
    }

    private function tipoMovimientoCobroDisponible(): bool
    {
        if (!$this->movimientosOperativosDisponibles()) {
            return false;
        }

        $stmt = $this->db->query(
            "SELECT COLUMN_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'cuentas_por_cobrar_movimientos'
               AND COLUMN_NAME = 'tipo_movimiento'
             LIMIT 1"
        );

        $columnType = $stmt ? (string)($stmt->fetchColumn() ?: '') : '';
        return strpos($columnType, "'COBRO'") !== false;
    }

    private function validarId($value, string $message): int
    {
        $id = (int)$value;
        if ($id <= 0) {
            throw new Exception($message);
        }

        return $id;
    }

    private function normalizarUsuarioId($value): ?int
    {
        $id = (int)($value ?? 0);
        return $id > 0 ? $id : null;
    }

    private function normalizarFechaVencimiento($fecha): ?string
    {
        $fecha = trim((string)($fecha ?? ''));
        return $fecha !== '' ? substr($fecha, 0, 10) : null;
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->db->query(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
             LIMIT 1",
            [$tabla]
        );

        return $stmt !== false && (bool)$stmt->fetch();
    }

    private function decimal($value): string
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}
