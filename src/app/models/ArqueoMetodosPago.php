<?php
/**
 * Lector read-only para arqueo por corte y metodo de pago.
 */

class ArqueoMetodosPago extends Model
{
    private const METHODS = ['efectivo', 'tarjeta', 'transferencia'];
    private const STATUSES = ['todos', 'abierto', 'cerrado', 'cancelado'];
    private const SEVERITIES = ['todos', 'ok', 'warning', 'error'];

    public function reporteReadOnlyPorHotel(int $hotelId, array $filtros = []): array
    {
        $hotelId = max(0, $hotelId);
        $filtros = $this->normalizarFiltros($filtros);

        if ($hotelId <= 0) {
            return $this->respuestaVacia($filtros, 'No hay hotel activo para arqueo.');
        }

        $pdo = $this->db->getConnection();

        try {
            $this->db->query('START TRANSACTION READ ONLY');

            $cortes = $this->cortes($hotelId, $filtros);
            $resumen = $this->resumen($hotelId, $filtros, $cortes);
            $alertas = $this->alertas($hotelId, $filtros);
            $anomalias = $this->anomalias($hotelId, $filtros);
            $paginado = $this->paginarCortes($cortes, $filtros);

            return [
                'schema_ok' => true,
                'generated_at' => date('Y-m-d H:i:s'),
                'filtros' => $filtros,
                'resumen' => $resumen,
                'metodos' => $this->metodos($hotelId, $filtros),
                'alertas' => $alertas,
                'anomalias' => $anomalias,
                'cortes' => $paginado['items'],
                'pagination' => $paginado['pagination'],
                'mensaje' => '',
            ];
        } catch (Throwable $e) {
            error_log('Error en arqueo read-only por metodo: ' . $e->getMessage());
            return $this->respuestaVacia($filtros, 'No se pudo generar el arqueo read-only.');
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    private function normalizarFiltros(array $filtros): array
    {
        $fechaDesde = $this->fecha($filtros['fecha_desde'] ?? '');
        $fechaHasta = $this->fecha($filtros['fecha_hasta'] ?? '');

        if ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
        }

        $estado = strtolower(trim((string)($filtros['estado_corte'] ?? 'todos')));
        if (!in_array($estado, self::STATUSES, true)) {
            $estado = 'todos';
        }

        $metodo = strtolower(trim((string)($filtros['metodo_pago'] ?? 'todos')));
        if ($metodo !== 'todos' && !in_array($metodo, self::METHODS, true)) {
            $metodo = 'todos';
        }

        $severidad = strtolower(trim((string)($filtros['severidad'] ?? 'todos')));
        if (!in_array($severidad, self::SEVERITIES, true)) {
            $severidad = 'todos';
        }

        $limit = (int)($filtros['limit'] ?? 25);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 25;
        }

        return [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'corte_id' => max(0, (int)($filtros['corte_id'] ?? 0)),
            'caja_id' => max(0, (int)($filtros['caja_id'] ?? 0)),
            'estado_corte' => $estado,
            'metodo_pago' => $metodo,
            'severidad' => $severidad,
            'page' => max(1, (int)($filtros['page'] ?? 1)),
            'limit' => $limit,
        ];
    }

    private function fecha($value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function respuestaVacia(array $filtros, string $mensaje): array
    {
        return [
            'schema_ok' => false,
            'generated_at' => date('Y-m-d H:i:s'),
            'filtros' => $filtros,
            'resumen' => $this->resumenBase(),
            'metodos' => $this->metodosBase(),
            'alertas' => [],
            'anomalias' => [],
            'cortes' => [],
            'pagination' => ['page' => 1, 'limit' => 25, 'total' => 0, 'pages' => 1],
            'mensaje' => $mensaje,
        ];
    }

    private function resumenBase(): array
    {
        return [
            'cortes_total' => 0,
            'cortes_abiertos' => 0,
            'cortes_cerrados' => 0,
            'cortes_cancelados' => 0,
            'movimientos_total' => 0,
            'ingresos_total' => 0.0,
            'gastos_total' => 0.0,
            'neto_total' => 0.0,
            'alertas_error' => 0,
            'alertas_warning' => 0,
            'cortes_con_warning' => 0,
            'cortes_con_error' => 0,
        ];
    }

    private function metodosBase(): array
    {
        $base = [];
        foreach (self::METHODS as $metodo) {
            $base[$metodo] = [
                'metodo' => $metodo,
                'ingresos_count' => 0,
                'ingresos_total' => 0.0,
                'gastos_count' => 0,
                'gastos_total' => 0.0,
                'neto' => 0.0,
            ];
        }

        return $base;
    }

    private function cortes(int $hotelId, array $filtros): array
    {
        $whereParams = [$hotelId];
        $where = $this->corteWhere($filtros, $whereParams);
        $method = $filtros['metodo_pago'];
        $methodCondition = '';
        $params = $whereParams;
        if ($method !== 'todos') {
            $methodCondition = ' AND mc.metodo_pago = ?';
            $params = array_merge([$method], $whereParams);
        }

        $sql = "
            SELECT
                cc.id,
                cc.hotel_id,
                cc.caja_id,
                cc.estado,
                cc.fecha_apertura,
                cc.fecha_cierre,
                cc.monto_inicial,
                cc.total_ingresos_efectivo,
                cc.total_ingresos_tarjeta,
                cc.total_ingresos_transferencia,
                cc.total_gastos_efectivo,
                cc.total_gastos_tarjeta,
                cc.total_gastos_transferencia,
                cc.efectivo_esperado,
                cc.efectivo_contado,
                cc.diferencia,
                c.nombre AS caja_nombre,
                SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END) AS mov_ingresos_efectivo,
                SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END) AS mov_ingresos_tarjeta,
                SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END) AS mov_ingresos_transferencia,
                SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END) AS mov_gastos_efectivo,
                SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END) AS mov_gastos_tarjeta,
                SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END) AS mov_gastos_transferencia,
                COUNT(mc.id) AS movimientos_total
            FROM cortes_caja cc
            LEFT JOIN cajas c
              ON c.id = cc.caja_id
             AND c.hotel_id = cc.hotel_id
            LEFT JOIN movimientos_caja mc
              ON mc.corte_id = cc.id
             AND mc.hotel_id = cc.hotel_id
             {$methodCondition}
            WHERE cc.hotel_id = ?{$where}
            GROUP BY
                cc.id,
                cc.hotel_id,
                cc.caja_id,
                cc.estado,
                cc.fecha_apertura,
                cc.fecha_cierre,
                cc.monto_inicial,
                cc.total_ingresos_efectivo,
                cc.total_ingresos_tarjeta,
                cc.total_ingresos_transferencia,
                cc.total_gastos_efectivo,
                cc.total_gastos_tarjeta,
                cc.total_gastos_transferencia,
                cc.efectivo_esperado,
                cc.efectivo_contado,
                cc.diferencia,
                c.nombre
            ORDER BY FIELD(cc.estado, 'abierto', 'cerrado', 'cancelado'), cc.fecha_apertura DESC, cc.id DESC
        ";

        $stmt = $this->db->query($sql, $params);
        $rows = $stmt ? $stmt->fetchAll() : [];
        $cortes = [];

        foreach ($rows as $row) {
            $cortes[] = $this->decorarCorte($row);
        }

        if ($filtros['severidad'] !== 'todos') {
            $cortes = array_values(array_filter($cortes, function ($corte) use ($filtros) {
                return ($corte['severidad'] ?? 'ok') === $filtros['severidad'];
            }));
        }

        return $cortes;
    }

    private function decorarCorte(array $row): array
    {
        $estado = (string)($row['estado'] ?? '');
        $mov = [
            'ingresos_efectivo' => (float)($row['mov_ingresos_efectivo'] ?? 0),
            'ingresos_tarjeta' => (float)($row['mov_ingresos_tarjeta'] ?? 0),
            'ingresos_transferencia' => (float)($row['mov_ingresos_transferencia'] ?? 0),
            'gastos_efectivo' => (float)($row['mov_gastos_efectivo'] ?? 0),
            'gastos_tarjeta' => (float)($row['mov_gastos_tarjeta'] ?? 0),
            'gastos_transferencia' => (float)($row['mov_gastos_transferencia'] ?? 0),
        ];
        $guardado = [
            'ingresos_efectivo' => (float)($row['total_ingresos_efectivo'] ?? 0),
            'ingresos_tarjeta' => (float)($row['total_ingresos_tarjeta'] ?? 0),
            'ingresos_transferencia' => (float)($row['total_ingresos_transferencia'] ?? 0),
            'gastos_efectivo' => (float)($row['total_gastos_efectivo'] ?? 0),
            'gastos_tarjeta' => (float)($row['total_gastos_tarjeta'] ?? 0),
            'gastos_transferencia' => (float)($row['total_gastos_transferencia'] ?? 0),
        ];

        $diferencias = [];
        $totalDiferencia = 0.0;
        foreach ($mov as $key => $value) {
            $diff = round($value - ($guardado[$key] ?? 0), 2);
            $diferencias[$key] = $diff;
            $totalDiferencia += abs($diff);
        }

        $efectivoCalculado = round(
            (float)($row['monto_inicial'] ?? 0) + $guardado['ingresos_efectivo'] - $guardado['gastos_efectivo'],
            2
        );
        $efectivoDiff = round($efectivoCalculado - (float)($row['efectivo_esperado'] ?? 0), 2);
        $diferenciaCalculada = round((float)($row['efectivo_contado'] ?? 0) - (float)($row['efectivo_esperado'] ?? 0), 2);
        $diferenciaDiff = round($diferenciaCalculada - (float)($row['diferencia'] ?? 0), 2);

        $severidad = 'ok';
        $hallazgos = [];
        if (empty($row['caja_nombre'])) {
            $severidad = 'error';
            $hallazgos[] = 'Caja no valida para el hotel.';
        }
        if ($estado === 'cerrado' && $totalDiferencia > 0.01) {
            $severidad = $severidad === 'error' ? 'error' : 'warning';
            $hallazgos[] = 'Totales guardados distintos a movimientos.';
        }
        if ($estado === 'cerrado' && abs($efectivoDiff) > 0.01) {
            $severidad = $severidad === 'error' ? 'error' : 'warning';
            $hallazgos[] = 'Efectivo esperado distinto a formula.';
        }
        if ($estado === 'cerrado' && abs($diferenciaDiff) > 0.01) {
            $severidad = $severidad === 'error' ? 'error' : 'warning';
            $hallazgos[] = 'Diferencia guardada no coincide.';
        }

        $row['movimientos'] = $mov;
        $row['guardado'] = $guardado;
        $row['diferencias'] = $diferencias;
        $row['diferencia_total_abs'] = round($totalDiferencia, 2);
        $row['efectivo_calculado'] = $efectivoCalculado;
        $row['efectivo_diff'] = $efectivoDiff;
        $row['diferencia_calculada'] = $diferenciaCalculada;
        $row['diferencia_diff'] = $diferenciaDiff;
        $row['severidad'] = $severidad;
        $row['hallazgos'] = $hallazgos;
        $row['neto_movimientos'] = round(
            $mov['ingresos_efectivo'] + $mov['ingresos_tarjeta'] + $mov['ingresos_transferencia']
            - $mov['gastos_efectivo'] - $mov['gastos_tarjeta'] - $mov['gastos_transferencia'],
            2
        );

        return $row;
    }

    private function resumen(int $hotelId, array $filtros, array $cortes): array
    {
        $resumen = $this->resumenBase();
        $params = [$hotelId];
        $where = $this->movimientoWhere($filtros, $params);

        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS movimientos_total,
                COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END), 0) AS ingresos_total,
                COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' THEN mc.monto ELSE 0 END), 0) AS gastos_total
             FROM movimientos_caja mc
             LEFT JOIN cortes_caja cc
               ON cc.id = mc.corte_id
              AND cc.hotel_id = mc.hotel_id
             WHERE mc.hotel_id = ?{$where}",
            $params
        );
        $movs = $stmt ? $stmt->fetch() : [];

        $resumen['cortes_total'] = count($cortes);
        $resumen['cortes_abiertos'] = count(array_filter($cortes, fn($corte) => ($corte['estado'] ?? '') === 'abierto'));
        $resumen['cortes_cerrados'] = count(array_filter($cortes, fn($corte) => ($corte['estado'] ?? '') === 'cerrado'));
        $resumen['cortes_cancelados'] = count(array_filter($cortes, fn($corte) => ($corte['estado'] ?? '') === 'cancelado'));
        $resumen['movimientos_total'] = (int)($movs['movimientos_total'] ?? 0);
        $resumen['ingresos_total'] = (float)($movs['ingresos_total'] ?? 0);
        $resumen['gastos_total'] = (float)($movs['gastos_total'] ?? 0);
        $resumen['neto_total'] = $resumen['ingresos_total'] - $resumen['gastos_total'];
        $resumen['cortes_con_warning'] = count(array_filter($cortes, fn($corte) => ($corte['severidad'] ?? '') === 'warning'));
        $resumen['cortes_con_error'] = count(array_filter($cortes, fn($corte) => ($corte['severidad'] ?? '') === 'error'));

        return $resumen;
    }

    private function metodos(int $hotelId, array $filtros): array
    {
        $metodos = $this->metodosBase();
        $params = [$hotelId];
        $where = $this->movimientoWhere($filtros, $params);

        $stmt = $this->db->query(
            "SELECT
                mc.metodo_pago,
                SUM(CASE WHEN mc.tipo = 'ingreso' THEN 1 ELSE 0 END) AS ingresos_count,
                COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END), 0) AS ingresos_total,
                SUM(CASE WHEN mc.tipo = 'gasto' THEN 1 ELSE 0 END) AS gastos_count,
                COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' THEN mc.monto ELSE 0 END), 0) AS gastos_total
             FROM movimientos_caja mc
             INNER JOIN cortes_caja cc
               ON cc.id = mc.corte_id
              AND cc.hotel_id = mc.hotel_id
             WHERE mc.hotel_id = ?{$where}
             GROUP BY mc.metodo_pago
             ORDER BY mc.metodo_pago",
            $params
        );

        foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
            $key = (string)($row['metodo_pago'] ?? '');
            if ($key === '') {
                continue;
            }
            $metodos[$key] = [
                'metodo' => $key,
                'ingresos_count' => (int)($row['ingresos_count'] ?? 0),
                'ingresos_total' => (float)($row['ingresos_total'] ?? 0),
                'gastos_count' => (int)($row['gastos_count'] ?? 0),
                'gastos_total' => (float)($row['gastos_total'] ?? 0),
                'neto' => (float)($row['ingresos_total'] ?? 0) - (float)($row['gastos_total'] ?? 0),
            ];
        }

        return $metodos;
    }

    private function alertas(int $hotelId, array $filtros): array
    {
        return [
            $this->alerta('error', 'MOV_SIN_METODO', 'Movimientos sin metodo_pago', $this->countMovimientos($hotelId, $filtros, "mc.metodo_pago IS NULL OR mc.metodo_pago = ''")),
            $this->alerta('error', 'MOV_SIN_CORTE', 'Movimientos sin corte_id', $this->countMovimientos($hotelId, $filtros, 'mc.corte_id IS NULL')),
            $this->alerta('error', 'MOV_CORTE_INVALIDO', 'Movimientos con corte inexistente o de otro hotel', $this->countCorteInvalido($hotelId, $filtros)),
            $this->alerta('error', 'CORTE_CAJA_INVALIDA', 'Cortes sin caja valida del mismo hotel', $this->countCorteCajaInvalida($hotelId, $filtros)),
            $this->alerta('error', 'MOV_TIPO_INVALIDO', 'Movimientos con tipo invalido', $this->countMovimientos($hotelId, $filtros, "mc.tipo NOT IN ('ingreso', 'gasto') OR mc.tipo IS NULL")),
            $this->alerta('error', 'MOV_MONTO_INVALIDO', 'Movimientos con monto no positivo', $this->countMovimientos($hotelId, $filtros, 'mc.monto <= 0 OR mc.monto IS NULL')),
            $this->alerta('error', 'MOV_METODO_INVALIDO', 'Movimientos con metodo fuera de enum esperado', $this->countMovimientos($hotelId, $filtros, "mc.metodo_pago NOT IN ('efectivo', 'tarjeta', 'transferencia') OR mc.metodo_pago IS NULL")),
            $this->alerta('warning', 'CORTE_TOTALES_DIFF', 'Cortes cerrados con totales guardados distintos a movimientos', $this->countCortesTotalesDiff($hotelId, $filtros)),
            $this->alerta('warning', 'CORTE_EFECTIVO_DIFF', 'Cortes cerrados con efectivo esperado distinto a formula', $this->countCortesEfectivoDiff($hotelId, $filtros)),
            $this->alerta('warning', 'CORTE_DIFERENCIA_DIFF', 'Cortes cerrados con diferencia distinta a formula', $this->countCortesDiferenciaDiff($hotelId, $filtros)),
        ];
    }

    private function alerta(string $severidad, string $codigo, string $titulo, int $hallazgos): array
    {
        return [
            'severidad' => $severidad,
            'codigo' => $codigo,
            'titulo' => $titulo,
            'hallazgos' => $hallazgos,
        ];
    }

    private function anomalias(int $hotelId, array $filtros): array
    {
        $params = [$hotelId];
        $where = $this->movimientoWhere($filtros, $params);
        $stmt = $this->db->query(
            "SELECT
                mc.id,
                mc.corte_id,
                mc.tipo,
                mc.metodo_pago,
                mc.categoria,
                mc.monto,
                mc.referencia,
                mc.created_at,
                cc.estado AS corte_estado,
                c.nombre AS caja_nombre
             FROM movimientos_caja mc
             LEFT JOIN cortes_caja cc
               ON cc.id = mc.corte_id
              AND cc.hotel_id = mc.hotel_id
             LEFT JOIN cajas c
               ON c.id = cc.caja_id
              AND c.hotel_id = cc.hotel_id
             WHERE mc.hotel_id = ?{$where}
               AND (
                    mc.metodo_pago IS NULL
                 OR mc.metodo_pago = ''
                 OR mc.metodo_pago NOT IN ('efectivo', 'tarjeta', 'transferencia')
                 OR mc.corte_id IS NULL
                 OR cc.id IS NULL
                 OR mc.tipo NOT IN ('ingreso', 'gasto')
                 OR mc.tipo IS NULL
                 OR mc.monto <= 0
                 OR mc.monto IS NULL
               )
             ORDER BY mc.created_at DESC, mc.id DESC
             LIMIT 50",
            $params
        );

        return $stmt ? $stmt->fetchAll() : [];
    }

    private function countMovimientos(int $hotelId, array $filtros, string $condition): int
    {
        $params = [$hotelId];
        $where = $this->movimientoWhere($filtros, $params);
        $stmt = $this->db->query(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             LEFT JOIN cortes_caja cc
               ON cc.id = mc.corte_id
              AND cc.hotel_id = mc.hotel_id
             WHERE mc.hotel_id = ?{$where}
               AND ({$condition})",
            $params
        );

        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    private function countCorteInvalido(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $where = $this->movimientoWhere($filtros, $params);
        $stmt = $this->db->query(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             LEFT JOIN cortes_caja cc
               ON cc.id = mc.corte_id
              AND cc.hotel_id = mc.hotel_id
             WHERE mc.hotel_id = ?{$where}
               AND (mc.corte_id IS NULL OR cc.id IS NULL)",
            $params
        );

        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    private function countCorteCajaInvalida(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $where = $this->corteWhere($filtros, $params);
        $stmt = $this->db->query(
            "SELECT COUNT(*)
             FROM cortes_caja cc
             LEFT JOIN cajas c
               ON c.id = cc.caja_id
             WHERE cc.hotel_id = ?{$where}
               AND (c.id IS NULL OR c.hotel_id <> cc.hotel_id)",
            $params
        );

        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    private function countCortesTotalesDiff(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $where = $this->corteWhere($filtros, $params);
        return $this->countCortesDiff($params, $where, 'totales');
    }

    private function countCortesEfectivoDiff(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $where = $this->corteWhere($filtros, $params);
        return $this->countCortesDiff($params, $where, 'efectivo');
    }

    private function countCortesDiferenciaDiff(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $where = $this->corteWhere($filtros, $params);
        return $this->countCortesDiff($params, $where, 'diferencia');
    }

    private function countCortesDiff(array $params, string $where, string $tipo): int
    {
        $having = [
            'totales' => "ABS(mov_ingresos_efectivo - corte_ingresos_efectivo) > 0.01
                       OR ABS(mov_ingresos_tarjeta - corte_ingresos_tarjeta) > 0.01
                       OR ABS(mov_ingresos_transferencia - corte_ingresos_transferencia) > 0.01
                       OR ABS(mov_gastos_efectivo - corte_gastos_efectivo) > 0.01
                       OR ABS(mov_gastos_tarjeta - corte_gastos_tarjeta) > 0.01
                       OR ABS(mov_gastos_transferencia - corte_gastos_transferencia) > 0.01",
            'efectivo' => 'ABS(efectivo_calculado - efectivo_guardado) > 0.01',
            'diferencia' => 'ABS(diferencia_calculada - diferencia_guardada) > 0.01',
        ][$tipo];

        $stmt = $this->db->query(
            "SELECT COUNT(*)
             FROM (
                SELECT
                    cc.id,
                    ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ingresos_efectivo,
                    ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ingresos_tarjeta,
                    ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ingresos_transferencia,
                    ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gastos_efectivo,
                    ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gastos_tarjeta,
                    ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gastos_transferencia,
                    COALESCE(cc.total_ingresos_efectivo, 0) AS corte_ingresos_efectivo,
                    COALESCE(cc.total_ingresos_tarjeta, 0) AS corte_ingresos_tarjeta,
                    COALESCE(cc.total_ingresos_transferencia, 0) AS corte_ingresos_transferencia,
                    COALESCE(cc.total_gastos_efectivo, 0) AS corte_gastos_efectivo,
                    COALESCE(cc.total_gastos_tarjeta, 0) AS corte_gastos_tarjeta,
                    COALESCE(cc.total_gastos_transferencia, 0) AS corte_gastos_transferencia,
                    ROUND(COALESCE(cc.monto_inicial, 0) + COALESCE(cc.total_ingresos_efectivo, 0) - COALESCE(cc.total_gastos_efectivo, 0), 2) AS efectivo_calculado,
                    ROUND(COALESCE(cc.efectivo_esperado, 0), 2) AS efectivo_guardado,
                    ROUND(COALESCE(cc.efectivo_contado, 0) - COALESCE(cc.efectivo_esperado, 0), 2) AS diferencia_calculada,
                    ROUND(COALESCE(cc.diferencia, 0), 2) AS diferencia_guardada
                FROM cortes_caja cc
                LEFT JOIN movimientos_caja mc
                  ON mc.corte_id = cc.id
                 AND mc.hotel_id = cc.hotel_id
                WHERE cc.hotel_id = ?{$where}
                  AND cc.estado = 'cerrado'
                GROUP BY
                    cc.id,
                    cc.total_ingresos_efectivo,
                    cc.total_ingresos_tarjeta,
                    cc.total_ingresos_transferencia,
                    cc.total_gastos_efectivo,
                    cc.total_gastos_tarjeta,
                    cc.total_gastos_transferencia,
                    cc.monto_inicial,
                    cc.efectivo_esperado,
                    cc.efectivo_contado,
                    cc.diferencia
                HAVING {$having}
             ) x",
            $params
        );

        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    private function corteWhere(array $filtros, array &$params): string
    {
        $sql = '';
        if ($filtros['fecha_desde'] !== '') {
            $sql .= ' AND DATE(cc.fecha_apertura) >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if ($filtros['fecha_hasta'] !== '') {
            $sql .= ' AND DATE(cc.fecha_apertura) <= ?';
            $params[] = $filtros['fecha_hasta'];
        }
        if ($filtros['corte_id'] > 0) {
            $sql .= ' AND cc.id = ?';
            $params[] = $filtros['corte_id'];
        }
        if ($filtros['caja_id'] > 0) {
            $sql .= ' AND cc.caja_id = ?';
            $params[] = $filtros['caja_id'];
        }
        if ($filtros['estado_corte'] !== 'todos') {
            $sql .= ' AND cc.estado = ?';
            $params[] = $filtros['estado_corte'];
        }

        return $sql;
    }

    private function movimientoWhere(array $filtros, array &$params): string
    {
        $sql = '';
        if ($filtros['fecha_desde'] !== '') {
            $sql .= ' AND DATE(mc.created_at) >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if ($filtros['fecha_hasta'] !== '') {
            $sql .= ' AND DATE(mc.created_at) <= ?';
            $params[] = $filtros['fecha_hasta'];
        }
        if ($filtros['corte_id'] > 0) {
            $sql .= ' AND mc.corte_id = ?';
            $params[] = $filtros['corte_id'];
        }
        if ($filtros['caja_id'] > 0) {
            $sql .= ' AND cc.caja_id = ?';
            $params[] = $filtros['caja_id'];
        }
        if ($filtros['estado_corte'] !== 'todos') {
            $sql .= ' AND cc.estado = ?';
            $params[] = $filtros['estado_corte'];
        }
        if ($filtros['metodo_pago'] !== 'todos') {
            $sql .= ' AND mc.metodo_pago = ?';
            $params[] = $filtros['metodo_pago'];
        }

        return $sql;
    }

    private function paginarCortes(array $cortes, array $filtros): array
    {
        $total = count($cortes);
        $limit = $filtros['limit'];
        $pages = max(1, (int)ceil($total / $limit));
        $page = min($filtros['page'], $pages);
        $offset = ($page - 1) * $limit;

        return [
            'items' => array_slice($cortes, $offset, $limit),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages,
            ],
        ];
    }
}
