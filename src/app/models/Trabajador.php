<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class Trabajador extends Model
{
    protected $table = 'trabajadores';

    public function tablaDisponible(): bool
    {
        return $this->tablaExiste('trabajadores');
    }

    public function tablasLedgerDisponibles(): array
    {
        $tablas = [
            'trabajador_pagos',
            'trabajador_anticipos',
            'trabajador_prestamos',
            'trabajador_asistencias',
            'trabajador_documentos',
        ];

        $disponibles = [];
        foreach ($tablas as $tabla) {
            $disponibles[$tabla] = $this->tablaExiste($tabla);
        }

        return $disponibles;
    }

    public function tablasSimuladorPagoCajaDisponibles(): bool
    {
        foreach ([
            'trabajadores',
            'trabajador_pagos',
            'trabajador_anticipos',
            'trabajador_prestamos',
            'trabajador_pagos_caja',
            'cajas',
            'cortes_caja',
            'movimientos_caja',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function tablasReportePagosCajaDisponibles(): bool
    {
        foreach ([
            'trabajadores',
            'trabajador_pagos_caja',
            'cajas',
            'cortes_caja',
            'movimientos_caja',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function tablasNominaPreviewDisponibles(): bool
    {
        foreach ([
            'trabajadores',
            'trabajador_pagos',
            'trabajador_anticipos',
            'trabajador_prestamos',
            'trabajador_pagos_caja',
            'movimientos_caja',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function tablasNominaPeriodoPersistenteDisponibles(): bool
    {
        if (!$this->tablasNominaPreviewDisponibles()) {
            return false;
        }

        foreach ([
            'trabajador_nomina_periodos',
            'trabajador_nomina_periodo_detalles',
            'trabajador_nomina_periodo_eventos',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $limite = max(1, min(300, $limite));
        $where = ['t.hotel_id = ?'];
        $params = [$hotelId];

        $estado = (string)($filtros['estado'] ?? 'activos');
        if ($estado === 'inactivos') {
            $where[] = "t.estado = 'inactivo'";
        } elseif ($estado === 'baja') {
            $where[] = "t.estado = 'baja'";
        } elseif ($estado !== 'todos') {
            $where[] = "t.estado = 'activo'";
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(t.nombre_completo LIKE ? OR t.identificacion LIKE ? OR t.rol_laboral LIKE ? OR t.telefono LIKE ? OR t.email LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.usuario_id,
                    t.nombre_completo,
                    t.identificacion,
                    t.rol_laboral,
                    t.telefono,
                    t.email,
                    t.estado,
                    t.fecha_alta,
                    t.fecha_baja,
                    t.salario_base,
                    t.periodicidad_pago,
                    t.created_at,
                    t.updated_at,
                    u.nombre_completo AS usuario_nombre,
                    u.nombre_usuario AS usuario_login
             FROM trabajadores t
             LEFT JOIN usuarios u
                ON u.id = t.usuario_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY FIELD(t.estado, 'activo', 'inactivo', 'baja'), t.nombre_completo ASC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumenPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'baja' => 0,
            ];
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) AS inactivos,
                    SUM(CASE WHEN estado = 'baja' THEN 1 ELSE 0 END) AS baja
             FROM trabajadores
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'activos' => (int)($row['activos'] ?? 0),
            'inactivos' => (int)($row['inactivos'] ?? 0),
            'baja' => (int)($row['baja'] ?? 0),
        ];
    }

    public function reporteReadOnlyPorHotel(int $hotelId): array
    {
        $reporte = [
            'trabajadores' => $this->resumenPorHotel($hotelId),
            'ledger' => [
                'conceptos_count' => 0,
                'conceptos_a_favor' => '0.00',
                'conceptos_en_contra' => '0.00',
                'conceptos_neutros' => '0.00',
                'anticipos_count' => 0,
                'anticipos_saldo' => '0.00',
                'prestamos_count' => 0,
                'prestamos_saldo' => '0.00',
                'saldo_informativo' => '0.00',
            ],
            'asistencias' => [
                'total' => 0,
                'asistencia' => 0,
                'falta' => 0,
                'retardo' => 0,
                'permiso' => 0,
                'incapacidad' => 0,
                'descanso' => 0,
                'horas_extra' => 0,
                'primera_fecha' => null,
                'ultima_fecha' => null,
            ],
            'documentos' => [
                'total' => 0,
                'trabajadores_con_documentos' => 0,
            ],
            'tareas' => [
                'total' => 0,
                'pendiente' => 0,
                'asignada' => 0,
                'en_proceso' => 0,
                'completada' => 0,
                'cancelada' => 0,
            ],
            'trabajadores_relevantes' => [],
        ];

        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return $reporte;
        }

        if ($this->tablaExiste('trabajador_pagos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'a_favor' THEN monto ELSE 0 END), 0) AS a_favor,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'en_contra' THEN monto ELSE 0 END), 0) AS en_contra,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'neutro' THEN monto ELSE 0 END), 0) AS neutro
                 FROM trabajador_pagos
                 WHERE hotel_id = ?",
                [$hotelId]
            );
            $reporte['ledger']['conceptos_count'] = (int)($row['total'] ?? 0);
            $reporte['ledger']['conceptos_a_favor'] = $this->decimal($row['a_favor'] ?? 0);
            $reporte['ledger']['conceptos_en_contra'] = $this->decimal($row['en_contra'] ?? 0);
            $reporte['ledger']['conceptos_neutros'] = $this->decimal($row['neutro'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_anticipos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_anticipos
                 WHERE hotel_id = ?",
                [$hotelId]
            );
            $reporte['ledger']['anticipos_count'] = (int)($row['total'] ?? 0);
            $reporte['ledger']['anticipos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_prestamos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'vigente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_prestamos
                 WHERE hotel_id = ?",
                [$hotelId]
            );
            $reporte['ledger']['prestamos_count'] = (int)($row['total'] ?? 0);
            $reporte['ledger']['prestamos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_asistencias')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN tipo = 'asistencia' THEN 1 ELSE 0 END) AS asistencia,
                        SUM(CASE WHEN tipo = 'falta' THEN 1 ELSE 0 END) AS falta,
                        SUM(CASE WHEN tipo = 'retardo' THEN 1 ELSE 0 END) AS retardo,
                        SUM(CASE WHEN tipo = 'permiso' THEN 1 ELSE 0 END) AS permiso,
                        SUM(CASE WHEN tipo = 'incapacidad' THEN 1 ELSE 0 END) AS incapacidad,
                        SUM(CASE WHEN tipo = 'descanso' THEN 1 ELSE 0 END) AS descanso,
                        SUM(CASE WHEN tipo = 'horas_extra' THEN 1 ELSE 0 END) AS horas_extra,
                        MIN(fecha) AS primera_fecha,
                        MAX(fecha) AS ultima_fecha
                 FROM trabajador_asistencias
                 WHERE hotel_id = ?",
                [$hotelId]
            );

            foreach (['total', 'asistencia', 'falta', 'retardo', 'permiso', 'incapacidad', 'descanso', 'horas_extra'] as $key) {
                $reporte['asistencias'][$key] = (int)($row[$key] ?? 0);
            }
            $reporte['asistencias']['primera_fecha'] = $row['primera_fecha'] ?? null;
            $reporte['asistencias']['ultima_fecha'] = $row['ultima_fecha'] ?? null;
        }

        if ($this->tablaExiste('documentos') && $this->tablaExiste('documento_entidades')) {
            $row = $this->fetchOne(
                "SELECT COUNT(DISTINCT d.id) AS total,
                        COUNT(DISTINCT de.entidad_id) AS trabajadores_con_documentos
                 FROM documento_entidades de
                 INNER JOIN documentos d
                    ON d.id = de.documento_id
                   AND d.hotel_id = de.hotel_id
                 WHERE de.hotel_id = ?
                   AND de.entidad_tipo = 'trabajador'
                   AND d.estado <> 'eliminado'",
                [$hotelId]
            );
            $reporte['documentos']['total'] = (int)($row['total'] ?? 0);
            $reporte['documentos']['trabajadores_con_documentos'] = (int)($row['trabajadores_con_documentos'] ?? 0);
        }

        if ($this->tablaExiste('tareas_operativas')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendiente,
                        SUM(CASE WHEN estado = 'asignada' THEN 1 ELSE 0 END) AS asignada,
                        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
                        SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) AS completada,
                        SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS cancelada
                 FROM tareas_operativas
                 WHERE hotel_id = ?
                   AND trabajador_id IS NOT NULL",
                [$hotelId]
            );

            foreach (['total', 'pendiente', 'asignada', 'en_proceso', 'completada', 'cancelada'] as $key) {
                $reporte['tareas'][$key] = (int)($row[$key] ?? 0);
            }
        }

        $reporte['ledger']['saldo_informativo'] = $this->decimal(
            (float)$reporte['ledger']['conceptos_a_favor']
            - (float)$reporte['ledger']['conceptos_en_contra']
            - (float)$reporte['ledger']['anticipos_saldo']
            - (float)$reporte['ledger']['prestamos_saldo']
        );

        $trabajadores = $this->listarPorHotel($hotelId, ['estado' => 'todos'], 80);
        foreach ($trabajadores as $trabajador) {
            $trabajadorId = (int)($trabajador['id'] ?? 0);
            $trabajador['resumen_laboral'] = $this->resumenLedgerPorTrabajador($trabajadorId, $hotelId);
            $reporte['trabajadores_relevantes'][] = $trabajador;
        }

        return $reporte;
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.usuario_id,
                    t.nombre_completo,
                    t.identificacion,
                    t.rol_laboral,
                    t.telefono,
                    t.email,
                    t.estado,
                    t.fecha_alta,
                    t.fecha_baja,
                    t.salario_base,
                    t.periodicidad_pago,
                    t.notas,
                    t.created_by,
                    t.updated_by,
                    t.created_at,
                    t.updated_at,
                    u.nombre_completo AS usuario_nombre,
                    u.nombre_usuario AS usuario_login
             FROM trabajadores t
             LEFT JOIN usuarios u
                ON u.id = t.usuario_id
             WHERE t.id = ?
               AND t.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function simuladorPagoCajaPorHotel(int $hotelId, array $filtros = [], int $limite = 200): array
    {
        $filtros = $this->normalizarFiltrosSimuladorPagoCaja($filtros);
        $corte = $this->corteAbiertoSimuladorPagoCaja($hotelId);
        $trabajadores = [];

        if ($hotelId > 0 && $this->tablasSimuladorPagoCajaDisponibles()) {
            $limite = max(1, min(300, $limite));
            $filas = [];

            if ((int)$filtros['trabajador_id'] > 0) {
                $trabajador = $this->buscarPorIdHotel((int)$filtros['trabajador_id'], $hotelId);
                if ($trabajador) {
                    $filas[] = $trabajador;
                }
            } else {
                $filas = $this->listarPorHotel($hotelId, [
                    'estado' => 'todos',
                    'buscar' => $filtros['buscar'],
                ], $limite);
            }

            foreach ($filas as $fila) {
                $resumenLaboral = $this->resumenLaboralSimuladorPagoCaja(
                    (int)($fila['id'] ?? 0),
                    $hotelId,
                    $filtros['periodo_inicio'],
                    $filtros['periodo_fin']
                );
                $pagosCaja = $this->pagosCajaResumenPorTrabajador(
                    (int)($fila['id'] ?? 0),
                    $hotelId,
                    $filtros['periodo_inicio'],
                    $filtros['periodo_fin']
                );

                $trabajadores[] = $this->evaluarSimuladorPagoCajaTrabajador(
                    $fila,
                    $resumenLaboral,
                    $pagosCaja,
                    $corte,
                    $filtros,
                    $hotelId
                );
            }
        }

        return [
            'corte' => $corte,
            'trabajadores' => $trabajadores,
            'resumen' => $this->resumenSimuladorPagoCaja($trabajadores, $corte),
            'filtros_normalizados' => $filtros,
        ];
    }

    public function reportePagosCajaPorHotel(int $hotelId, array $filtros = [], int $limite = 300): array
    {
        $filtros = $this->normalizarFiltrosReportePagosCaja($filtros);
        $reporte = [
            'registros' => [],
            'resumen' => $this->resumenVacioReportePagosCaja(),
            'por_estado' => [],
            'por_metodo' => [],
            'por_corte' => [],
            'filtros_normalizados' => $filtros,
        ];

        if ($hotelId <= 0 || !$this->tablasReportePagosCajaDisponibles()) {
            return $reporte;
        }

        $limite = max(1, min(500, $limite));
        [$whereSql, $params] = $this->whereReportePagosCaja($hotelId, $filtros);
        $joins = $this->joinsReportePagosCaja();

        $stmt = $this->db->query(
            "SELECT pc.id,
                    pc.hotel_id,
                    pc.trabajador_id,
                    pc.movimiento_caja_id,
                    pc.corte_id,
                    pc.monto,
                    pc.metodo_pago,
                    pc.referencia,
                    pc.periodo_inicio,
                    pc.periodo_fin,
                    pc.concepto,
                    pc.fecha_pago,
                    pc.estado,
                    pc.notas,
                    pc.created_at,
                    pc.updated_at,
                    t.nombre_completo AS trabajador_nombre,
                    t.identificacion AS trabajador_identificacion,
                    t.rol_laboral AS trabajador_rol,
                    cc.estado AS corte_estado,
                    cc.fecha_apertura AS corte_fecha_apertura,
                    c.nombre AS caja_nombre,
                    mc.tipo AS movimiento_tipo,
                    mc.categoria AS movimiento_categoria,
                    mc.descripcion AS movimiento_descripcion,
                    mc.created_at AS movimiento_created_at,
                    mcr.id AS movimiento_reversion_id,
                    mcr.corte_id AS corte_reversion_id,
                    mcr.monto AS movimiento_reversion_monto,
                    mcr.created_at AS movimiento_reversion_created_at,
                    u.nombre_completo AS creado_por_nombre,
                    u.nombre_usuario AS creado_por_login,
                    uu.nombre_completo AS actualizado_por_nombre,
                    uu.nombre_usuario AS actualizado_por_login
             FROM trabajador_pagos_caja pc
             {$joins}
             WHERE {$whereSql}
             ORDER BY pc.fecha_pago DESC, pc.id DESC
             LIMIT {$limite}",
            $params
        );
        $reporte['registros'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN pc.estado = 'pagado' THEN 1 ELSE 0 END) AS pagados,
                    SUM(CASE WHEN pc.estado = 'revertido' THEN 1 ELSE 0 END) AS revertidos,
                    COALESCE(SUM(pc.monto), 0) AS egreso_original_total,
                    COALESCE(SUM(CASE WHEN pc.estado = 'pagado' THEN pc.monto ELSE 0 END), 0) AS pagado_vigente_total,
                    COALESCE(SUM(CASE WHEN pc.estado = 'revertido' THEN pc.monto ELSE 0 END), 0) AS revertido_total,
                    COALESCE(SUM(CASE WHEN mcr.id IS NOT NULL THEN mcr.monto ELSE 0 END), 0) AS reversion_caja_total
             FROM trabajador_pagos_caja pc
             {$joins}
             WHERE {$whereSql}",
            $params
        );
        $egresoOriginal = (float)($row['egreso_original_total'] ?? 0);
        $reversionCaja = (float)($row['reversion_caja_total'] ?? 0);
        $reporte['resumen'] = [
            'total_registros' => (int)($row['total'] ?? 0),
            'pagados_count' => (int)($row['pagados'] ?? 0),
            'revertidos_count' => (int)($row['revertidos'] ?? 0),
            'egreso_original_total' => $this->decimal($egresoOriginal),
            'pagado_vigente_total' => $this->decimal($row['pagado_vigente_total'] ?? 0),
            'revertido_total' => $this->decimal($row['revertido_total'] ?? 0),
            'reversion_caja_total' => $this->decimal($reversionCaja),
            'impacto_caja_neto' => $this->decimal($egresoOriginal - $reversionCaja),
        ];

        $stmt = $this->db->query(
            "SELECT pc.estado,
                    COUNT(*) AS total,
                    COALESCE(SUM(pc.monto), 0) AS monto,
                    COALESCE(SUM(CASE WHEN mcr.id IS NOT NULL THEN mcr.monto ELSE 0 END), 0) AS reversion_caja
             FROM trabajador_pagos_caja pc
             {$joins}
             WHERE {$whereSql}
             GROUP BY pc.estado
             ORDER BY FIELD(pc.estado, 'pagado', 'revertido')",
            $params
        );
        $reporte['por_estado'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $stmt = $this->db->query(
            "SELECT pc.metodo_pago,
                    COUNT(*) AS total,
                    SUM(CASE WHEN pc.estado = 'pagado' THEN 1 ELSE 0 END) AS pagados,
                    SUM(CASE WHEN pc.estado = 'revertido' THEN 1 ELSE 0 END) AS revertidos,
                    COALESCE(SUM(CASE WHEN pc.estado = 'pagado' THEN pc.monto ELSE 0 END), 0) AS pagado_vigente,
                    COALESCE(SUM(CASE WHEN pc.estado = 'revertido' THEN pc.monto ELSE 0 END), 0) AS revertido,
                    COALESCE(SUM(CASE WHEN mcr.id IS NOT NULL THEN mcr.monto ELSE 0 END), 0) AS reversion_caja
             FROM trabajador_pagos_caja pc
             {$joins}
             WHERE {$whereSql}
             GROUP BY pc.metodo_pago
             ORDER BY pc.metodo_pago ASC",
            $params
        );
        $reporte['por_metodo'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $stmt = $this->db->query(
            "SELECT pc.corte_id,
                    c.nombre AS caja_nombre,
                    cc.estado AS corte_estado,
                    cc.fecha_apertura AS corte_fecha_apertura,
                    COUNT(*) AS total,
                    SUM(CASE WHEN pc.estado = 'pagado' THEN 1 ELSE 0 END) AS pagados,
                    SUM(CASE WHEN pc.estado = 'revertido' THEN 1 ELSE 0 END) AS revertidos,
                    COALESCE(SUM(CASE WHEN pc.estado = 'pagado' THEN pc.monto ELSE 0 END), 0) AS pagado_vigente,
                    COALESCE(SUM(CASE WHEN pc.estado = 'revertido' THEN pc.monto ELSE 0 END), 0) AS revertido,
                    COALESCE(SUM(CASE WHEN mcr.id IS NOT NULL THEN mcr.monto ELSE 0 END), 0) AS reversion_caja
             FROM trabajador_pagos_caja pc
             {$joins}
             WHERE {$whereSql}
             GROUP BY pc.corte_id, c.nombre, cc.estado, cc.fecha_apertura
             ORDER BY pc.corte_id DESC
             LIMIT 80",
            $params
        );
        $reporte['por_corte'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        return $reporte;
    }

    public function nominaPreviewPorHotel(int $hotelId, array $filtros = [], int $limite = 200): array
    {
        $filtros = $this->normalizarFiltrosNominaPreview($filtros);
        $preview = [
            'trabajadores' => [],
            'resumen' => $this->resumenVacioNominaPreview(),
            'filtros_normalizados' => $filtros,
            'bloqueos' => [],
        ];

        if ($hotelId <= 0) {
            $preview['bloqueos'][] = 'No hay hotel activo para calcular el preview.';
            return $preview;
        }

        if (!$this->tablasNominaPreviewDisponibles()) {
            $preview['bloqueos'][] = 'Faltan tablas laborales o de Caja para calcular el preview con seguridad.';
            return $preview;
        }

        if (!empty($filtros['periodo_invalido'])) {
            $preview['bloqueos'][] = 'El periodo seleccionado no es valido.';
            return $preview;
        }

        if (!empty($filtros['periodo_requerido'])) {
            $preview['bloqueos'][] = 'Selecciona fecha inicio y fecha fin para revisar la pre-nomina.';
            return $preview;
        }

        $limite = max(1, min(300, $limite));
        $filas = $this->trabajadoresNominaPreviewBase($hotelId, $filtros, $limite);
        foreach ($filas as $fila) {
            $resumenLaboral = $this->resumenNominaPreviewPorTrabajador(
                (int)($fila['id'] ?? 0),
                $hotelId,
                $filtros['fecha_inicio'],
                $filtros['fecha_fin']
            );
            $pagosCaja = $filtros['incluir_pagos_caja']
                ? $this->pagosCajaNominaPreviewPorTrabajador(
                    (int)($fila['id'] ?? 0),
                    $hotelId,
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                )
                : $this->pagosCajaVacioNominaPreview();

            $trabajador = $this->evaluarNominaPreviewTrabajador(
                $fila,
                $resumenLaboral,
                $pagosCaja,
                $filtros
            );

            if (!empty($filtros['solo_con_saldo']) && (float)($trabajador['neto_sugerido'] ?? 0) <= 0) {
                continue;
            }

            $preview['trabajadores'][] = $trabajador;
        }

        $preview['resumen'] = $this->resumenNominaPreview($preview['trabajadores']);

        return $preview;
    }

    public function reciboLaboralInformativoPorHotel(int $hotelId, int $trabajadorId, array $filtros = []): array
    {
        $recibo = [
            'trabajador' => null,
            'calculo' => null,
            'periodo' => [],
            'resumen' => $this->resumenVacioNominaPreview(),
            'bloqueos' => [],
            'generado_en' => date('Y-m-d H:i:s'),
        ];

        if ($hotelId <= 0) {
            $recibo['bloqueos'][] = 'No hay hotel activo para generar el recibo informativo.';
            return $recibo;
        }

        if ($trabajadorId <= 0) {
            $recibo['bloqueos'][] = 'Trabajador invalido para generar el recibo informativo.';
            return $recibo;
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            $recibo['bloqueos'][] = 'Trabajador no encontrado para el hotel actual.';
            return $recibo;
        }

        $filtros['trabajador_id'] = $trabajadorId;
        $filtros['buscar'] = '';
        $filtros['rol_laboral'] = '';
        $filtros['estado'] = 'todos';
        $filtros['solo_con_saldo'] = '0';

        $preview = $this->nominaPreviewPorHotel($hotelId, $filtros, 1);
        $filtrosNormalizados = is_array($preview['filtros_normalizados'] ?? null)
            ? $preview['filtros_normalizados']
            : [];

        $recibo['trabajador'] = $trabajador;
        $recibo['periodo'] = [
            'fecha_inicio' => $filtrosNormalizados['fecha_inicio'] ?? null,
            'fecha_fin' => $filtrosNormalizados['fecha_fin'] ?? null,
            'fecha_inicio_raw' => $filtrosNormalizados['fecha_inicio_raw'] ?? '',
            'fecha_fin_raw' => $filtrosNormalizados['fecha_fin_raw'] ?? '',
            'incluir_pagos_caja' => !empty($filtrosNormalizados['incluir_pagos_caja']),
        ];
        $recibo['resumen'] = is_array($preview['resumen'] ?? null)
            ? $preview['resumen']
            : $this->resumenVacioNominaPreview();
        $recibo['bloqueos'] = is_array($preview['bloqueos'] ?? null) ? $preview['bloqueos'] : [];

        if (!empty($recibo['bloqueos'])) {
            return $recibo;
        }

        $trabajadores = is_array($preview['trabajadores'] ?? null) ? $preview['trabajadores'] : [];
        $recibo['calculo'] = $trabajadores[0] ?? null;

        if (empty($recibo['calculo'])) {
            $recibo['bloqueos'][] = 'No se encontro calculo laboral para el periodo seleccionado.';
        }

        return $recibo;
    }

    public function nominaPeriodosReadOnlyPorHotel(int $hotelId, array $filtros = [], int $limite = 6): array
    {
        $filtros = $this->normalizarFiltrosNominaPeriodosReadOnly($filtros);
        $resultado = [
            'periodos' => [],
            'periodo_detalle' => null,
            'preview' => [
                'trabajadores' => [],
                'resumen' => $this->resumenVacioNominaPreview(),
                'filtros_normalizados' => [],
                'bloqueos' => [],
            ],
            'filtros_normalizados' => $filtros,
            'bloqueos' => [],
            'generado_en' => date('Y-m-d H:i:s'),
        ];

        if ($hotelId <= 0) {
            $resultado['bloqueos'][] = 'No hay hotel activo para revisar periodos de pre-nomina.';
            return $resultado;
        }

        if (!$this->tablasNominaPreviewDisponibles()) {
            $resultado['bloqueos'][] = 'Faltan tablas laborales o de Caja para revisar periodos con seguridad.';
            return $resultado;
        }

        if (!empty($filtros['fecha_base_invalida'])) {
            $resultado['bloqueos'][] = 'La fecha base del periodo no es valida.';
        }

        if (!empty($filtros['periodo_manual_invalido'])) {
            $resultado['bloqueos'][] = 'El periodo manual no es valido.';
        }

        $limite = max(1, min(12, $limite));
        $candidatos = $this->construirPeriodosNominaCandidatos($filtros, $limite);
        if (empty($candidatos)) {
            $resultado['bloqueos'][] = 'No hay periodos validos para revisar.';
            return $resultado;
        }

        $detalleKey = null;
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $detalleKey = $filtros['fecha_inicio'] . ':' . $filtros['fecha_fin'];
        }

        $indiceDetalle = 0;
        foreach ($candidatos as $indice => $periodo) {
            $previewFiltros = [
                'fecha_inicio' => $periodo['fecha_inicio'],
                'fecha_fin' => $periodo['fecha_fin'],
                'trabajador_id' => '',
                'buscar' => '',
                'rol_laboral' => $filtros['rol_laboral'],
                'estado' => $filtros['estado'],
                'solo_con_saldo' => '0',
                'incluir_pagos_caja' => $filtros['incluir_pagos_caja'] ? '1' : '0',
            ];
            $preview = $this->nominaPreviewPorHotel($hotelId, $previewFiltros, 250);
            $periodo['preview'] = $preview;
            $periodo['resumen'] = is_array($preview['resumen'] ?? null)
                ? $preview['resumen']
                : $this->resumenVacioNominaPreview();
            $periodo['bloqueos'] = is_array($preview['bloqueos'] ?? null) ? $preview['bloqueos'] : [];
            $periodo = array_merge($periodo, $this->evaluarEstadoNominaPeriodoReadOnly($periodo));

            if ($detalleKey !== null && $detalleKey === ($periodo['fecha_inicio'] . ':' . $periodo['fecha_fin'])) {
                $indiceDetalle = $indice;
            }

            $resultado['periodos'][] = $periodo;
        }

        $resultado['periodo_detalle'] = $resultado['periodos'][$indiceDetalle] ?? $resultado['periodos'][0] ?? null;
        if (is_array($resultado['periodo_detalle'])) {
            $resultado['preview'] = is_array($resultado['periodo_detalle']['preview'] ?? null)
                ? $resultado['periodo_detalle']['preview']
                : $resultado['preview'];
        }

        return $resultado;
    }

    public function nominaPeriodosPersistentesPorHotel(int $hotelId, int $limite = 12): array
    {
        if ($hotelId <= 0 || !$this->tablasNominaPeriodoPersistenteDisponibles()) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT p.id,
                    p.hotel_id,
                    p.tipo_periodo,
                    p.etiqueta,
                    p.fecha_inicio,
                    p.fecha_fin,
                    p.estado,
                    p.trabajadores_total,
                    p.bruto_total,
                    p.deducciones_total,
                    p.pagos_caja_aplicados_total,
                    p.reversiones_detectadas_total,
                    p.neto_sugerido_total,
                    p.pendiente_pago_total,
                    p.cerrado_por,
                    p.cerrado_at,
                    p.aprobado_por,
                    p.aprobado_at,
                    p.anulado_por,
                    p.anulado_at,
                    p.motivo_anulacion,
                    uc.nombre_completo AS cerrado_por_nombre,
                    ua.nombre_completo AS aprobado_por_nombre,
                    un.nombre_completo AS anulado_por_nombre
             FROM trabajador_nomina_periodos p
             LEFT JOIN usuarios uc ON uc.id = p.cerrado_por
             LEFT JOIN usuarios ua ON ua.id = p.aprobado_por
             LEFT JOIN usuarios un ON un.id = p.anulado_por
             WHERE p.hotel_id = ?
             ORDER BY p.fecha_inicio DESC, p.id DESC
             LIMIT {$limite}",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function mapaNominaPeriodosPersistentesPorRango(int $hotelId, array $periodos): array
    {
        if ($hotelId <= 0 || empty($periodos) || !$this->tablasNominaPeriodoPersistenteDisponibles()) {
            return [];
        }

        $rangos = [];
        $params = [$hotelId];
        foreach ($periodos as $periodo) {
            $inicio = $this->nullableFecha($periodo['fecha_inicio'] ?? null);
            $fin = $this->nullableFecha($periodo['fecha_fin'] ?? null);
            if ($inicio === null || $fin === null) {
                continue;
            }

            $rangos[] = '(fecha_inicio = ? AND fecha_fin = ?)';
            $params[] = $inicio;
            $params[] = $fin;
        }

        if (empty($rangos)) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT id,
                    fecha_inicio,
                    fecha_fin,
                    estado,
                    cerrado_at,
                    aprobado_at,
                    anulado_at
             FROM trabajador_nomina_periodos
             WHERE hotel_id = ?
               AND (" . implode(' OR ', $rangos) . ")",
            $params
        );

        $mapa = [];
        $filas = $stmt ? ($stmt->fetchAll() ?: []) : [];
        foreach ($filas as $fila) {
            $key = (string)($fila['fecha_inicio'] ?? '') . ':' . (string)($fila['fecha_fin'] ?? '');
            $mapa[$key] = $fila;
        }

        return $mapa;
    }

    public function nominaPeriodoPersistentePorHotel(int $periodoId, int $hotelId): ?array
    {
        if ($periodoId <= 0 || $hotelId <= 0 || !$this->tablasNominaPeriodoPersistenteDisponibles()) {
            return null;
        }

        $periodo = $this->fetchOne(
            "SELECT p.id,
                    p.hotel_id,
                    p.tipo_periodo,
                    p.etiqueta,
                    p.fecha_inicio,
                    p.fecha_fin,
                    p.estado,
                    p.filtros_json,
                    p.resumen_json,
                    p.trabajadores_total,
                    p.bruto_total,
                    p.deducciones_total,
                    p.pagos_caja_aplicados_total,
                    p.reversiones_detectadas_total,
                    p.neto_sugerido_total,
                    p.pendiente_pago_total,
                    p.cerrado_por,
                    p.cerrado_at,
                    p.aprobado_por,
                    p.aprobado_at,
                    p.anulado_por,
                    p.anulado_at,
                    p.motivo_anulacion,
                    p.created_at,
                    p.updated_at,
                    uc.nombre_completo AS cerrado_por_nombre,
                    ua.nombre_completo AS aprobado_por_nombre,
                    un.nombre_completo AS anulado_por_nombre
             FROM trabajador_nomina_periodos p
             LEFT JOIN usuarios uc ON uc.id = p.cerrado_por
             LEFT JOIN usuarios ua ON ua.id = p.aprobado_por
             LEFT JOIN usuarios un ON un.id = p.anulado_por
             WHERE p.id = ?
               AND p.hotel_id = ?
             LIMIT 1",
            [$periodoId, $hotelId]
        );

        if (empty($periodo)) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    periodo_id,
                    hotel_id,
                    trabajador_id,
                    trabajador_nombre,
                    trabajador_identificacion,
                    trabajador_rol,
                    trabajador_estado,
                    estado_preview_nomina,
                    motivo_bloqueo_nomina,
                    conceptos_count,
                    conceptos_a_favor,
                    conceptos_en_contra,
                    bruto_periodo,
                    anticipos_count,
                    anticipos_saldo,
                    prestamos_count,
                    prestamos_saldo,
                    deducciones_informativas,
                    pagos_caja_count,
                    pagos_caja_pagados,
                    pagos_caja_revertidos,
                    pagos_caja_aplicados,
                    pagos_caja_revertidos_total,
                    reversiones_detectadas,
                    ultimo_pago_caja,
                    neto_sugerido,
                    pendiente_pago_sugerido,
                    snapshot_json,
                    created_at
             FROM trabajador_nomina_periodo_detalles
             WHERE periodo_id = ?
               AND hotel_id = ?
             ORDER BY trabajador_nombre ASC, id ASC",
            [$periodoId, $hotelId]
        );
        $periodo['detalles'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $stmt = $this->db->query(
            "SELECT e.id,
                    e.periodo_id,
                    e.hotel_id,
                    e.tipo,
                    e.estado_resultante,
                    e.descripcion,
                    e.motivo,
                    e.created_by,
                    e.created_at,
                    u.nombre_completo AS creado_por_nombre,
                    u.nombre_usuario AS creado_por_login
             FROM trabajador_nomina_periodo_eventos e
             LEFT JOIN usuarios u ON u.id = e.created_by
             WHERE e.periodo_id = ?
               AND e.hotel_id = ?
             ORDER BY e.created_at DESC, e.id DESC",
            [$periodoId, $hotelId]
        );
        $periodo['eventos'] = $stmt ? ($stmt->fetchAll() ?: []) : [];
        $periodo['filtros'] = $this->decodificarJsonArray($periodo['filtros_json'] ?? null);
        $periodo['resumen_snapshot'] = $this->decodificarJsonArray($periodo['resumen_json'] ?? null);

        return $periodo;
    }

    public function cerrarNominaPeriodoPersistenteParaHotel(int $hotelId, array $datos, ?int $usuarioId = null, bool $manageTransaction = true): int
    {
        if ($hotelId <= 0 || !$this->tablasNominaPeriodoPersistenteDisponibles()) {
            throw new Exception('Persistencia de periodos de pre-nomina no disponible');
        }

        $cierre = $this->normalizarDatosCierreNominaPeriodo($datos);
        $previewFiltros = [
            'fecha_inicio' => $cierre['fecha_inicio'],
            'fecha_fin' => $cierre['fecha_fin'],
            'trabajador_id' => '',
            'buscar' => '',
            'rol_laboral' => '',
            'estado' => 'activos',
            'solo_con_saldo' => '0',
            'incluir_pagos_caja' => $cierre['incluir_pagos_caja'] ? '1' : '0',
        ];

        $this->iniciarTransaccionNominaPeriodo($manageTransaction);

        try {
            $duplicado = $this->fetchOne(
                "SELECT id, estado
                 FROM trabajador_nomina_periodos
                 WHERE hotel_id = ?
                   AND fecha_inicio = ?
                   AND fecha_fin = ?
                 LIMIT 1
                 FOR UPDATE",
                [$hotelId, $cierre['fecha_inicio'], $cierre['fecha_fin']]
            );
            if (!empty($duplicado)) {
                throw new Exception('El periodo ya tiene un cierre persistente registrado');
            }

            $preview = $this->nominaPreviewPorHotel($hotelId, $previewFiltros, 300);
            $bloqueos = is_array($preview['bloqueos'] ?? null) ? $preview['bloqueos'] : [];
            if (!empty($bloqueos)) {
                throw new Exception('El preview del periodo esta bloqueado: ' . implode(' ', $bloqueos));
            }

            $trabajadores = is_array($preview['trabajadores'] ?? null) ? $preview['trabajadores'] : [];
            if (empty($trabajadores)) {
                throw new Exception('No hay trabajadores activos para cerrar este periodo');
            }

            $resumen = is_array($preview['resumen'] ?? null) ? $preview['resumen'] : $this->resumenVacioNominaPreview();
            if ((int)($resumen['bloqueado_count'] ?? 0) > 0) {
                throw new Exception('El periodo contiene trabajadores bloqueados');
            }

            $filtrosSnapshot = is_array($preview['filtros_normalizados'] ?? null)
                ? $preview['filtros_normalizados']
                : $previewFiltros;

            $stmt = $this->db->query(
                "INSERT INTO trabajador_nomina_periodos
                    (hotel_id, tipo_periodo, etiqueta, fecha_inicio, fecha_fin, estado,
                     filtros_json, resumen_json, trabajadores_total, bruto_total,
                     deducciones_total, pagos_caja_aplicados_total,
                     reversiones_detectadas_total, neto_sugerido_total,
                     pendiente_pago_total, cerrado_por, cerrado_at, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, 'cerrado',
                     ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())",
                [
                    $hotelId,
                    $cierre['tipo_periodo'],
                    $cierre['etiqueta'],
                    $cierre['fecha_inicio'],
                    $cierre['fecha_fin'],
                    $this->jsonSeguro($filtrosSnapshot),
                    $this->jsonSeguro($resumen),
                    (int)($resumen['trabajadores_total'] ?? count($trabajadores)),
                    $this->decimal($resumen['bruto_total'] ?? 0),
                    $this->decimal($resumen['deducciones_total'] ?? 0),
                    $this->decimal($resumen['pagos_caja_aplicados_total'] ?? 0),
                    $this->decimal($resumen['reversiones_detectadas_total'] ?? 0),
                    $this->decimal($resumen['neto_sugerido_total'] ?? 0),
                    $this->decimal($resumen['pendiente_pago_total'] ?? 0),
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo cerrar el periodo de pre-nomina');
            }

            $periodoId = (int)$this->db->lastInsertId();
            foreach ($trabajadores as $trabajador) {
                $this->insertarDetalleNominaPeriodo($periodoId, $hotelId, $trabajador);
            }

            $this->registrarEventoNominaPeriodo(
                $periodoId,
                $hotelId,
                'cierre',
                'cerrado',
                'Periodo de pre-nomina cerrado como snapshot persistente',
                null,
                $usuarioId
            );

            $this->confirmarTransaccionNominaPeriodo($manageTransaction);

            return $periodoId;
        } catch (Throwable $e) {
            $this->revertirTransaccionNominaPeriodo($manageTransaction);
            throw $e;
        }
    }

    public function aprobarNominaPeriodoParaHotel(int $periodoId, int $hotelId, ?int $usuarioId = null, bool $manageTransaction = true): bool
    {
        if ($periodoId <= 0 || $hotelId <= 0 || !$this->tablasNominaPeriodoPersistenteDisponibles()) {
            throw new Exception('Persistencia de periodos de pre-nomina no disponible');
        }

        $this->iniciarTransaccionNominaPeriodo($manageTransaction);

        try {
            $periodo = $this->fetchOne(
                "SELECT id, estado
                 FROM trabajador_nomina_periodos
                 WHERE id = ?
                   AND hotel_id = ?
                 LIMIT 1
                 FOR UPDATE",
                [$periodoId, $hotelId]
            );

            if (empty($periodo)) {
                throw new Exception('Periodo de pre-nomina no encontrado para el hotel actual');
            }

            if ((string)($periodo['estado'] ?? '') !== 'cerrado') {
                throw new Exception('Solo se pueden aprobar periodos en estado cerrado');
            }

            $stmt = $this->db->query(
                "UPDATE trabajador_nomina_periodos
                 SET estado = 'aprobado',
                     aprobado_por = ?,
                     aprobado_at = NOW(),
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?",
                [$usuarioId, $periodoId, $hotelId]
            );

            if (!$stmt) {
                throw new Exception('No se pudo aprobar el periodo de pre-nomina');
            }

            $this->registrarEventoNominaPeriodo(
                $periodoId,
                $hotelId,
                'aprobacion',
                'aprobado',
                'Periodo de pre-nomina aprobado administrativamente',
                null,
                $usuarioId
            );

            $this->confirmarTransaccionNominaPeriodo($manageTransaction);

            return true;
        } catch (Throwable $e) {
            $this->revertirTransaccionNominaPeriodo($manageTransaction);
            throw $e;
        }
    }

    public function anularNominaPeriodoParaHotel(int $periodoId, int $hotelId, string $motivo, ?int $usuarioId = null, bool $manageTransaction = true): bool
    {
        if ($periodoId <= 0 || $hotelId <= 0 || !$this->tablasNominaPeriodoPersistenteDisponibles()) {
            throw new Exception('Persistencia de periodos de pre-nomina no disponible');
        }

        $motivo = $this->limpiarTexto($motivo, 255);
        if ($motivo === '') {
            throw new Exception('El motivo de anulacion es obligatorio');
        }

        $this->iniciarTransaccionNominaPeriodo($manageTransaction);

        try {
            $periodo = $this->fetchOne(
                "SELECT id, estado
                 FROM trabajador_nomina_periodos
                 WHERE id = ?
                   AND hotel_id = ?
                 LIMIT 1
                 FOR UPDATE",
                [$periodoId, $hotelId]
            );

            if (empty($periodo)) {
                throw new Exception('Periodo de pre-nomina no encontrado para el hotel actual');
            }

            if ((string)($periodo['estado'] ?? '') === 'anulado') {
                throw new Exception('El periodo ya esta anulado');
            }

            $stmt = $this->db->query(
                "UPDATE trabajador_nomina_periodos
                 SET estado = 'anulado',
                     anulado_por = ?,
                     anulado_at = NOW(),
                     motivo_anulacion = ?,
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?",
                [$usuarioId, $motivo, $periodoId, $hotelId]
            );

            if (!$stmt) {
                throw new Exception('No se pudo anular el periodo de pre-nomina');
            }

            $this->registrarEventoNominaPeriodo(
                $periodoId,
                $hotelId,
                'anulacion',
                'anulado',
                'Periodo de pre-nomina anulado sin borrar snapshot',
                $motivo,
                $usuarioId
            );

            $this->confirmarTransaccionNominaPeriodo($manageTransaction);

            return true;
        } catch (Throwable $e) {
            $this->revertirTransaccionNominaPeriodo($manageTransaction);
            throw $e;
        }
    }

    public function usuariosVinculablesPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('hotel_usuarios') || !$this->tablaExiste('usuarios')) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT u.id,
                    u.nombre_completo,
                    u.nombre_usuario,
                    u.email,
                    hu.rol AS rol_hotel
             FROM hotel_usuarios hu
             INNER JOIN usuarios u
                ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ?
               AND hu.activo = 1
               AND u.activo = 1
             ORDER BY u.nombre_completo ASC, u.nombre_usuario ASC",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function crearParaHotel(int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            throw new Exception('Modulo de Personal no disponible');
        }

        $datos = $this->normalizarDatos($datos);
        $this->validarDatos($hotelId, $datos);

        $stmt = $this->db->query(
            "INSERT INTO trabajadores
                (hotel_id, usuario_id, nombre_completo, identificacion, rol_laboral,
                 telefono, email, estado, fecha_alta, fecha_baja, salario_base,
                 periodicidad_pago, notas, created_by, updated_by, created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, 'activo', ?, NULL, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $hotelId,
                $datos['usuario_id'],
                $datos['nombre_completo'],
                $datos['identificacion'],
                $datos['rol_laboral'],
                $datos['telefono'],
                $datos['email'],
                $datos['fecha_alta'],
                $datos['salario_base'],
                $datos['periodicidad_pago'],
                $datos['notas'],
                $usuarioId,
                $usuarioId,
            ]
        );

        if (!$stmt) {
            throw new Exception('No se pudo crear el trabajador');
        }

        return (int)$this->db->lastInsertId();
    }

    public function actualizarParaHotel(int $id, int $hotelId, array $datos, ?int $usuarioId = null): bool
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        if (!$this->buscarPorIdHotel($id, $hotelId)) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        $datos = $this->normalizarDatos($datos);
        $this->validarDatos($hotelId, $datos);

        $stmt = $this->db->query(
            "UPDATE trabajadores
             SET usuario_id = ?,
                 nombre_completo = ?,
                 identificacion = ?,
                 rol_laboral = ?,
                 telefono = ?,
                 email = ?,
                 fecha_alta = ?,
                 salario_base = ?,
                 periodicidad_pago = ?,
                 notas = ?,
                 updated_by = ?,
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?",
            [
                $datos['usuario_id'],
                $datos['nombre_completo'],
                $datos['identificacion'],
                $datos['rol_laboral'],
                $datos['telefono'],
                $datos['email'],
                $datos['fecha_alta'],
                $datos['salario_base'],
                $datos['periodicidad_pago'],
                $datos['notas'],
                $usuarioId,
                $id,
                $hotelId,
            ]
        );

        return $stmt !== false;
    }

    public function registrarConceptoLaboralParaHotel(int $trabajadorId, int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaDisponible() || !$this->tablaExiste('trabajador_pagos')) {
            throw new Exception('Ledger laboral no disponible');
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($trabajador['estado'] ?? '') !== 'activo') {
            throw new Exception('Solo se pueden registrar conceptos a trabajadores activos');
        }

        $concepto = $this->normalizarConceptoLaboral($datos);
        $this->validarConceptoLaboral($concepto);

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO trabajador_pagos
                    (hotel_id, trabajador_id, tipo, efecto, monto, concepto,
                     periodo_inicio, periodo_fin, fecha, referencia, notas,
                     estado, created_by, updated_by, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                     'activo', ?, ?, NOW(), NOW())",
                [
                    $hotelId,
                    $trabajadorId,
                    $concepto['tipo'],
                    $concepto['efecto'],
                    $concepto['monto'],
                    $concepto['concepto'],
                    $concepto['periodo_inicio'],
                    $concepto['periodo_fin'],
                    $concepto['fecha'],
                    $concepto['referencia'],
                    $concepto['notas'],
                    $usuarioId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo registrar el concepto laboral');
            }

            $conceptoId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();

            return $conceptoId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function conceptoLaboralPorIdHotel(int $conceptoId, int $trabajadorId, int $hotelId): ?array
    {
        if ($conceptoId <= 0 || $trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_pagos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    trabajador_id,
                    tipo,
                    efecto,
                    monto,
                    concepto,
                    periodo_inicio,
                    periodo_fin,
                    fecha,
                    referencia,
                    notas,
                    estado,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
             FROM trabajador_pagos
             WHERE id = ?
               AND trabajador_id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$conceptoId, $trabajadorId, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function registrarAnticipoLaboralParaHotel(int $trabajadorId, int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaDisponible() || !$this->tablaExiste('trabajador_anticipos')) {
            throw new Exception('Ledger de anticipos no disponible');
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($trabajador['estado'] ?? '') !== 'activo') {
            throw new Exception('Solo se pueden registrar anticipos a trabajadores activos');
        }

        $anticipo = $this->normalizarAnticipoLaboral($datos);
        $this->validarAnticipoLaboral($anticipo);

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO trabajador_anticipos
                    (hotel_id, trabajador_id, monto, saldo_pendiente, fecha,
                     motivo, estado, referencia, notas, created_by, updated_by,
                     created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, ?, NOW(), NOW())",
                [
                    $hotelId,
                    $trabajadorId,
                    $anticipo['monto'],
                    $anticipo['monto'],
                    $anticipo['fecha'],
                    $anticipo['motivo'],
                    $anticipo['referencia'],
                    $anticipo['notas'],
                    $usuarioId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo registrar el anticipo laboral');
            }

            $anticipoId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();

            return $anticipoId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function registrarPrestamoLaboralParaHotel(int $trabajadorId, int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaDisponible() || !$this->tablaExiste('trabajador_prestamos')) {
            throw new Exception('Ledger de prestamos no disponible');
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($trabajador['estado'] ?? '') !== 'activo') {
            throw new Exception('Solo se pueden registrar prestamos a trabajadores activos');
        }

        $prestamo = $this->normalizarPrestamoLaboral($datos);
        $this->validarPrestamoLaboral($prestamo);

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO trabajador_prestamos
                    (hotel_id, trabajador_id, monto, saldo_pendiente, fecha,
                     plazo_meses, abono_periodico, motivo, estado, referencia,
                     notas, created_by, updated_by, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, 'vigente', ?, ?, ?, ?, NOW(), NOW())",
                [
                    $hotelId,
                    $trabajadorId,
                    $prestamo['monto'],
                    $prestamo['monto'],
                    $prestamo['fecha'],
                    $prestamo['plazo_meses'],
                    $prestamo['abono_periodico'],
                    $prestamo['motivo'],
                    $prestamo['referencia'],
                    $prestamo['notas'],
                    $usuarioId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo registrar el prestamo laboral');
            }

            $prestamoId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();

            return $prestamoId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function anticipoLaboralPorIdHotel(int $anticipoId, int $trabajadorId, int $hotelId): ?array
    {
        if ($anticipoId <= 0 || $trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_anticipos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    trabajador_id,
                    monto,
                    saldo_pendiente,
                    fecha,
                    motivo,
                    estado,
                    referencia,
                    notas,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
             FROM trabajador_anticipos
             WHERE id = ?
               AND trabajador_id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$anticipoId, $trabajadorId, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function prestamoLaboralPorIdHotel(int $prestamoId, int $trabajadorId, int $hotelId): ?array
    {
        if ($prestamoId <= 0 || $trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_prestamos')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    trabajador_id,
                    monto,
                    saldo_pendiente,
                    fecha,
                    plazo_meses,
                    abono_periodico,
                    motivo,
                    estado,
                    referencia,
                    notas,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
             FROM trabajador_prestamos
             WHERE id = ?
               AND trabajador_id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$prestamoId, $trabajadorId, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function registrarAsistenciaLaboralParaHotel(int $trabajadorId, int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaDisponible() || !$this->tablaExiste('trabajador_asistencias')) {
            throw new Exception('Ledger de asistencias no disponible');
        }

        $trabajador = $this->buscarPorIdHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($trabajador['estado'] ?? '') !== 'activo') {
            throw new Exception('Solo se pueden registrar asistencias a trabajadores activos');
        }

        $asistencia = $this->normalizarAsistenciaLaboral($datos);
        $this->validarAsistenciaLaboral($asistencia);

        $duplicada = $this->fetchOne(
            "SELECT id
             FROM trabajador_asistencias
             WHERE hotel_id = ?
               AND trabajador_id = ?
               AND fecha = ?
             LIMIT 1",
            [$hotelId, $trabajadorId, $asistencia['fecha']]
        );

        if (!empty($duplicada)) {
            throw new Exception('Ya existe asistencia para este trabajador en la fecha indicada');
        }

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO trabajador_asistencias
                    (hotel_id, trabajador_id, fecha, tipo, hora_entrada, hora_salida,
                     horas, horas_extra, observaciones, created_by, updated_by,
                     created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $hotelId,
                    $trabajadorId,
                    $asistencia['fecha'],
                    $asistencia['tipo'],
                    $asistencia['hora_entrada'],
                    $asistencia['hora_salida'],
                    $asistencia['horas'],
                    $asistencia['horas_extra'],
                    $asistencia['observaciones'],
                    $usuarioId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new Exception('No se pudo registrar la asistencia laboral');
            }

            $asistenciaId = (int)$this->db->lastInsertId();
            $this->db->safeCommit();

            return $asistenciaId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function asistenciaLaboralPorIdHotel(int $asistenciaId, int $trabajadorId, int $hotelId): ?array
    {
        if ($asistenciaId <= 0 || $trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_asistencias')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id,
                    hotel_id,
                    trabajador_id,
                    fecha,
                    tipo,
                    hora_entrada,
                    hora_salida,
                    horas,
                    horas_extra,
                    observaciones,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
             FROM trabajador_asistencias
             WHERE id = ?
               AND trabajador_id = ?
               AND hotel_id = ?
             LIMIT 1",
            [$asistenciaId, $trabajadorId, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function cambiarEstadoParaHotel(int $id, int $hotelId, string $estado, ?int $usuarioId = null): bool
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return false;
        }

        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['activo', 'inactivo', 'baja'], true)) {
            throw new Exception('Estado de trabajador no valido');
        }

        $actual = $this->buscarPorIdHotel($id, $hotelId);
        if (!$actual) {
            throw new Exception('Trabajador no encontrado para el hotel actual');
        }

        if (($actual['estado'] ?? null) === $estado) {
            return true;
        }

        $fechaBajaSql = $estado === 'baja'
            ? 'COALESCE(fecha_baja, CURDATE())'
            : 'NULL';

        $stmt = $this->db->query(
            "UPDATE trabajadores
             SET estado = ?,
                 fecha_baja = {$fechaBajaSql},
                 updated_by = ?,
                 updated_at = NOW()
             WHERE id = ?
               AND hotel_id = ?",
            [$estado, $usuarioId, $id, $hotelId]
        );

        return $stmt !== false;
    }

    public function resumenLedgerPorTrabajador(int $trabajadorId, int $hotelId): array
    {
        $resumen = [
            'pagos_count' => 0,
            'pagos_total' => '0.00',
            'conceptos_a_favor' => '0.00',
            'conceptos_en_contra' => '0.00',
            'anticipos_count' => 0,
            'anticipos_saldo' => '0.00',
            'prestamos_count' => 0,
            'prestamos_saldo' => '0.00',
            'asistencias_count' => 0,
            'documentos_count' => 0,
            'saldo_informativo' => '0.00',
        ];

        if ($trabajadorId <= 0 || $hotelId <= 0) {
            return $resumen;
        }

        if ($this->tablaExiste('trabajador_pagos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'activo' THEN monto ELSE 0 END), 0) AS monto,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'a_favor' THEN monto ELSE 0 END), 0) AS a_favor,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'en_contra' THEN monto ELSE 0 END), 0) AS en_contra
                 FROM trabajador_pagos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['pagos_count'] = (int)($row['total'] ?? 0);
            $resumen['pagos_total'] = $this->decimal($row['monto'] ?? 0);
            $resumen['conceptos_a_favor'] = $this->decimal($row['a_favor'] ?? 0);
            $resumen['conceptos_en_contra'] = $this->decimal($row['en_contra'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_anticipos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_anticipos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['anticipos_count'] = (int)($row['total'] ?? 0);
            $resumen['anticipos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_prestamos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'vigente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_prestamos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['prestamos_count'] = (int)($row['total'] ?? 0);
            $resumen['prestamos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_asistencias')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM trabajador_asistencias
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['asistencias_count'] = (int)($row['total'] ?? 0);
        }

        if ($this->tablaExiste('documentos') && $this->tablaExiste('documento_entidades')) {
            $row = $this->fetchOne(
                "SELECT COUNT(DISTINCT d.id) AS total
                 FROM documento_entidades de
                 INNER JOIN documentos d
                    ON d.id = de.documento_id
                   AND d.hotel_id = de.hotel_id
                 WHERE de.hotel_id = ?
                   AND de.entidad_tipo = 'trabajador'
                   AND de.entidad_id = ?
                   AND d.estado <> 'eliminado'",
                [$hotelId, $trabajadorId]
            );
            $resumen['documentos_count'] = (int)($row['total'] ?? 0);
        } elseif ($this->tablaExiste('trabajador_documentos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM trabajador_documentos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?
                   AND estado = 'activo'",
                [$hotelId, $trabajadorId]
            );
            $resumen['documentos_count'] = (int)($row['total'] ?? 0);
        }

        $resumen['saldo_informativo'] = $this->decimal(
            (float)$resumen['conceptos_a_favor']
            - (float)$resumen['conceptos_en_contra']
            - (float)$resumen['anticipos_saldo']
            - (float)$resumen['prestamos_saldo']
        );

        return $resumen;
    }

    public function conceptosLaboralesPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_pagos')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    tipo,
                    efecto,
                    monto,
                    concepto,
                    periodo_inicio,
                    periodo_fin,
                    fecha,
                    referencia,
                    estado,
                    created_at
             FROM trabajador_pagos
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function pagosCajaPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_pagos_caja')) {
            return [];
        }

        foreach (['movimientos_caja', 'cortes_caja', 'cajas'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return [];
            }
        }

        $limite = max(1, min(80, $limite));
        $stmt = $this->db->query(
            "SELECT pc.id,
                    pc.hotel_id,
                    pc.trabajador_id,
                    pc.movimiento_caja_id,
                    pc.corte_id,
                    pc.monto,
                    pc.metodo_pago,
                    pc.referencia,
                    pc.periodo_inicio,
                    pc.periodo_fin,
                    pc.concepto,
                    pc.fecha_pago,
                    pc.estado,
                    pc.notas,
                    pc.created_at,
                    pc.updated_at,
                    cc.estado AS corte_estado,
                    cc.fecha_apertura AS corte_fecha_apertura,
                    c.nombre AS caja_nombre,
                    mc.tipo AS movimiento_tipo,
                    mc.categoria AS movimiento_categoria,
                    mc.descripcion AS movimiento_descripcion,
                    mc.created_at AS movimiento_created_at,
                    u.nombre_completo AS creado_por_nombre,
                    u.nombre_usuario AS creado_por_login,
                    uu.nombre_completo AS actualizado_por_nombre,
                    uu.nombre_usuario AS actualizado_por_login
             FROM trabajador_pagos_caja pc
             INNER JOIN movimientos_caja mc
                ON mc.id = pc.movimiento_caja_id
               AND mc.hotel_id = pc.hotel_id
             INNER JOIN cortes_caja cc
                ON cc.id = pc.corte_id
               AND cc.hotel_id = pc.hotel_id
             INNER JOIN cajas c
                ON c.id = cc.caja_id
               AND c.hotel_id = pc.hotel_id
             LEFT JOIN usuarios u
                ON u.id = pc.created_by
             LEFT JOIN usuarios uu
                ON uu.id = pc.updated_by
             WHERE pc.hotel_id = ?
               AND pc.trabajador_id = ?
             ORDER BY pc.fecha_pago DESC, pc.id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function anticiposPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_anticipos')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    monto,
                    saldo_pendiente,
                    fecha,
                    motivo,
                    estado,
                    referencia,
                    created_at
             FROM trabajador_anticipos
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function prestamosPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_prestamos')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    monto,
                    saldo_pendiente,
                    fecha,
                    plazo_meses,
                    abono_periodico,
                    motivo,
                    estado,
                    referencia,
                    created_at
             FROM trabajador_prestamos
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function ultimosMovimientosPorTrabajador(int $trabajadorId, int $hotelId, int $limite = 20): array
    {
        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_asistencias')) {
            return [];
        }

        $limite = max(1, min(50, $limite));
        $stmt = $this->db->query(
            "SELECT id,
                    fecha,
                    tipo,
                    hora_entrada,
                    hora_salida,
                    horas,
                    horas_extra,
                    observaciones
             FROM trabajador_asistencias
             WHERE hotel_id = ?
               AND trabajador_id = ?
             ORDER BY fecha DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $trabajadorId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    private function normalizarFiltrosNominaPeriodosReadOnly(array $filtros): array
    {
        $tipoPeriodo = strtolower(trim((string)($filtros['tipo_periodo'] ?? 'semanal')));
        if (!in_array($tipoPeriodo, ['semanal', 'quincenal', 'mensual', 'manual'], true)) {
            $tipoPeriodo = 'semanal';
        }

        $fechaBaseRaw = trim((string)($filtros['fecha_base'] ?? date('Y-m-d')));
        $fechaBase = $this->nullableFecha($fechaBaseRaw) ?: date('Y-m-d');
        $fechaInicioRaw = trim((string)($filtros['fecha_inicio'] ?? ''));
        $fechaFinRaw = trim((string)($filtros['fecha_fin'] ?? ''));
        $fechaInicio = $this->nullableFecha($fechaInicioRaw);
        $fechaFin = $this->nullableFecha($fechaFinRaw);
        $periodoManualSolicitado = $tipoPeriodo === 'manual' || $fechaInicioRaw !== '' || $fechaFinRaw !== '';

        $estado = strtolower(trim((string)($filtros['estado'] ?? 'activos')));
        if (!in_array($estado, ['activos', 'todos', 'inactivos', 'baja'], true)) {
            $estado = 'activos';
        }

        return [
            'tipo_periodo' => $tipoPeriodo,
            'fecha_base' => $fechaBase,
            'fecha_base_raw' => $fechaBaseRaw,
            'fecha_base_invalida' => $fechaBaseRaw !== '' && $this->nullableFecha($fechaBaseRaw) === null,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_inicio_raw' => $fechaInicioRaw,
            'fecha_fin_raw' => $fechaFinRaw,
            'periodo_manual_solicitado' => $periodoManualSolicitado,
            'periodo_manual_invalido' => $periodoManualSolicitado && (
                $fechaInicio === null
                || $fechaFin === null
                || $fechaFin < $fechaInicio
                || ($fechaInicioRaw !== '' && $fechaInicio === null)
                || ($fechaFinRaw !== '' && $fechaFin === null)
            ),
            'estado' => $estado,
            'rol_laboral' => $this->limpiarTexto($filtros['rol_laboral'] ?? '', 80),
            'incluir_pagos_caja' => $this->normalizarBooleanoNominaPreview($filtros['incluir_pagos_caja'] ?? null, true),
        ];
    }

    private function construirPeriodosNominaCandidatos(array $filtros, int $limite): array
    {
        $periodos = [];
        $vistos = [];

        if (empty($filtros['periodo_manual_invalido']) && !empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $manual = [
                'tipo_periodo' => 'manual',
                'etiqueta' => 'Periodo seleccionado',
                'fecha_inicio' => $filtros['fecha_inicio'],
                'fecha_fin' => $filtros['fecha_fin'],
                'origen' => 'manual',
            ];
            $key = $manual['fecha_inicio'] . ':' . $manual['fecha_fin'];
            $periodos[] = $manual;
            $vistos[$key] = true;
        }

        if (($filtros['tipo_periodo'] ?? '') === 'manual') {
            return array_slice($periodos, 0, $limite);
        }

        $fechaBase = (string)($filtros['fecha_base'] ?? date('Y-m-d'));
        $tipo = (string)($filtros['tipo_periodo'] ?? 'semanal');
        for ($i = 0; $i < $limite; $i++) {
            $periodo = $this->periodoNominaPorBase($tipo, $fechaBase);
            $key = $periodo['fecha_inicio'] . ':' . $periodo['fecha_fin'];
            if (empty($vistos[$key])) {
                $periodos[] = $periodo;
                $vistos[$key] = true;
            }

            $fechaBase = (new DateTimeImmutable($periodo['fecha_inicio']))->modify('-1 day')->format('Y-m-d');
        }

        return array_slice($periodos, 0, $limite);
    }

    private function periodoNominaPorBase(string $tipo, string $fechaBase): array
    {
        $base = new DateTimeImmutable($fechaBase);
        if ($tipo === 'mensual') {
            $inicio = $base->modify('first day of this month');
            $fin = $base->modify('last day of this month');
            $etiqueta = 'Mensual ' . $inicio->format('m/Y');
        } elseif ($tipo === 'quincenal') {
            $dia = (int)$base->format('j');
            if ($dia <= 15) {
                $inicio = $base->setDate((int)$base->format('Y'), (int)$base->format('m'), 1);
                $fin = $base->setDate((int)$base->format('Y'), (int)$base->format('m'), 15);
                $etiqueta = 'Quincena 1 ' . $inicio->format('m/Y');
            } else {
                $inicio = $base->setDate((int)$base->format('Y'), (int)$base->format('m'), 16);
                $fin = $base->modify('last day of this month');
                $etiqueta = 'Quincena 2 ' . $inicio->format('m/Y');
            }
        } else {
            $inicio = $base->modify('monday this week');
            $fin = $inicio->modify('+6 days');
            $etiqueta = 'Semana ' . $inicio->format('d/m/Y');
            $tipo = 'semanal';
        }

        return [
            'tipo_periodo' => $tipo,
            'etiqueta' => $etiqueta,
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $fin->format('Y-m-d'),
            'origen' => 'calculado',
        ];
    }

    private function evaluarEstadoNominaPeriodoReadOnly(array $periodo): array
    {
        $resumen = is_array($periodo['resumen'] ?? null) ? $periodo['resumen'] : $this->resumenVacioNominaPreview();
        $bloqueos = is_array($periodo['bloqueos'] ?? null) ? $periodo['bloqueos'] : [];

        if (!empty($bloqueos)) {
            return [
                'estado_periodo' => 'bloqueado',
                'estado_label' => 'Bloqueado',
                'cerrable_readonly' => false,
                'motivo_bloqueo' => implode(' ', $bloqueos),
            ];
        }

        if ((int)($resumen['trabajadores_total'] ?? 0) <= 0) {
            return [
                'estado_periodo' => 'sin_movimientos',
                'estado_label' => 'Sin movimientos',
                'cerrable_readonly' => false,
                'motivo_bloqueo' => 'No hay trabajadores visibles para este periodo.',
            ];
        }

        if ((int)($resumen['bloqueado_count'] ?? 0) > 0) {
            return [
                'estado_periodo' => 'requiere_revision',
                'estado_label' => 'Requiere revision',
                'cerrable_readonly' => false,
                'motivo_bloqueo' => 'Hay trabajadores bloqueados o no activos en el periodo.',
            ];
        }

        if ((float)($resumen['pendiente_pago_total'] ?? 0) > 0) {
            return [
                'estado_periodo' => 'listo_revision',
                'estado_label' => 'Listo para revision',
                'cerrable_readonly' => true,
                'motivo_bloqueo' => '',
            ];
        }

        return [
            'estado_periodo' => 'sin_saldo',
            'estado_label' => 'Sin saldo disponible',
            'cerrable_readonly' => true,
            'motivo_bloqueo' => '',
        ];
    }
    private function normalizarFiltrosNominaPreview(array $filtros): array
    {
        $fechaInicioRaw = trim((string)($filtros['fecha_inicio'] ?? ''));
        $fechaFinRaw = trim((string)($filtros['fecha_fin'] ?? ''));
        $fechaInicio = $this->nullableFecha($fechaInicioRaw);
        $fechaFin = $this->nullableFecha($fechaFinRaw);

        $estado = strtolower(trim((string)($filtros['estado'] ?? 'activos')));
        if (!in_array($estado, ['activos', 'todos', 'inactivos', 'baja'], true)) {
            $estado = 'activos';
        }

        $soloConSaldo = $this->normalizarBooleanoNominaPreview($filtros['solo_con_saldo'] ?? null, false);
        $incluirPagosCaja = $this->normalizarBooleanoNominaPreview($filtros['incluir_pagos_caja'] ?? null, true);

        return [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_inicio_raw' => $fechaInicioRaw,
            'fecha_fin_raw' => $fechaFinRaw,
            'periodo_requerido' => $fechaInicio === null || $fechaFin === null,
            'periodo_invalido' => (
                ($fechaInicioRaw !== '' && $fechaInicio === null)
                || ($fechaFinRaw !== '' && $fechaFin === null)
                || ($fechaInicio !== null && $fechaFin !== null && $fechaFin < $fechaInicio)
            ),
            'trabajador_id' => max(0, (int)($filtros['trabajador_id'] ?? 0)),
            'buscar' => $this->limpiarTexto($filtros['buscar'] ?? '', 120),
            'rol_laboral' => $this->limpiarTexto($filtros['rol_laboral'] ?? '', 80),
            'estado' => $estado,
            'solo_con_saldo' => $soloConSaldo,
            'incluir_pagos_caja' => $incluirPagosCaja,
        ];
    }

    private function trabajadoresNominaPreviewBase(int $hotelId, array $filtros, int $limite): array
    {
        $where = ['t.hotel_id = ?'];
        $params = [$hotelId];

        if ((int)($filtros['trabajador_id'] ?? 0) > 0) {
            $where[] = 't.id = ?';
            $params[] = (int)$filtros['trabajador_id'];
        }

        $estado = (string)($filtros['estado'] ?? 'activos');
        if ($estado === 'inactivos') {
            $where[] = "t.estado = 'inactivo'";
        } elseif ($estado === 'baja') {
            $where[] = "t.estado = 'baja'";
        } elseif ($estado !== 'todos') {
            $where[] = "t.estado = 'activo'";
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(t.nombre_completo LIKE ? OR t.identificacion LIKE ? OR t.rol_laboral LIKE ? OR t.telefono LIKE ? OR t.email LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $rol = trim((string)($filtros['rol_laboral'] ?? ''));
        if ($rol !== '') {
            $where[] = 't.rol_laboral LIKE ?';
            $params[] = '%' . $rol . '%';
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.usuario_id,
                    t.nombre_completo,
                    t.identificacion,
                    t.rol_laboral,
                    t.telefono,
                    t.email,
                    t.estado,
                    t.fecha_alta,
                    t.fecha_baja,
                    t.salario_base,
                    t.periodicidad_pago
             FROM trabajadores t
             WHERE " . implode(' AND ', $where) . "
             ORDER BY FIELD(t.estado, 'activo', 'inactivo', 'baja'), t.nombre_completo ASC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    private function resumenNominaPreviewPorTrabajador(
        int $trabajadorId,
        int $hotelId,
        ?string $fechaInicio,
        ?string $fechaFin
    ): array {
        $resumen = [
            'conceptos_count' => 0,
            'conceptos_a_favor' => '0.00',
            'conceptos_en_contra' => '0.00',
            'bruto_periodo' => '0.00',
            'anticipos_count' => 0,
            'anticipos_saldo' => '0.00',
            'prestamos_count' => 0,
            'prestamos_saldo' => '0.00',
            'deducciones_informativas' => '0.00',
        ];

        if ($trabajadorId <= 0 || $hotelId <= 0 || $fechaInicio === null || $fechaFin === null) {
            return $resumen;
        }

        if ($this->tablaExiste('trabajador_pagos')) {
            $row = $this->fetchOne(
                "SELECT SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'a_favor' THEN monto ELSE 0 END), 0) AS a_favor,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'en_contra' THEN monto ELSE 0 END), 0) AS en_contra
                 FROM trabajador_pagos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?
                   AND COALESCE(periodo_fin, fecha) >= ?
                   AND COALESCE(periodo_inicio, fecha) <= ?",
                [$hotelId, $trabajadorId, $fechaInicio, $fechaFin]
            );

            $resumen['conceptos_count'] = (int)($row['total'] ?? 0);
            $resumen['conceptos_a_favor'] = $this->decimal($row['a_favor'] ?? 0);
            $resumen['conceptos_en_contra'] = $this->decimal($row['en_contra'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_anticipos')) {
            $row = $this->fetchOne(
                "SELECT SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_anticipos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['anticipos_count'] = (int)($row['total'] ?? 0);
            $resumen['anticipos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_prestamos')) {
            $row = $this->fetchOne(
                "SELECT SUM(CASE WHEN estado = 'vigente' THEN 1 ELSE 0 END) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'vigente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_prestamos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['prestamos_count'] = (int)($row['total'] ?? 0);
            $resumen['prestamos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        $bruto = (float)$resumen['conceptos_a_favor'] - (float)$resumen['conceptos_en_contra'];
        $deducciones = (float)$resumen['anticipos_saldo'] + (float)$resumen['prestamos_saldo'];
        $resumen['bruto_periodo'] = $this->decimal($bruto);
        $resumen['deducciones_informativas'] = $this->decimal($deducciones);

        return $resumen;
    }

    private function pagosCajaNominaPreviewPorTrabajador(
        int $trabajadorId,
        int $hotelId,
        ?string $fechaInicio,
        ?string $fechaFin
    ): array {
        $resumen = $this->pagosCajaVacioNominaPreview();

        if ($trabajadorId <= 0 || $hotelId <= 0 || $fechaInicio === null || $fechaFin === null) {
            return $resumen;
        }

        if (!$this->tablaExiste('trabajador_pagos_caja') || !$this->tablaExiste('movimientos_caja')) {
            return $resumen;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN pc.estado = 'pagado' THEN 1 ELSE 0 END) AS pagados,
                    SUM(CASE WHEN pc.estado = 'revertido' THEN 1 ELSE 0 END) AS revertidos,
                    COALESCE(SUM(CASE WHEN pc.estado = 'pagado' THEN pc.monto ELSE 0 END), 0) AS pagado_total,
                    COALESCE(SUM(CASE WHEN pc.estado = 'revertido' THEN pc.monto ELSE 0 END), 0) AS revertido_total,
                    COALESCE(SUM(CASE WHEN mcr.id IS NOT NULL THEN mcr.monto ELSE 0 END), 0) AS reversion_caja_total,
                    MAX(pc.fecha_pago) AS ultimo_pago
             FROM trabajador_pagos_caja pc
             LEFT JOIN movimientos_caja mcr
                ON mcr.hotel_id = pc.hotel_id
               AND mcr.tipo = 'ingreso'
               AND mcr.categoria = 'Reversion Pago laboral'
               AND mcr.referencia = CONCAT('REV-NOM-TRAB-', pc.trabajador_id, '-PAGO-', pc.id)
             WHERE pc.hotel_id = ?
               AND pc.trabajador_id = ?
               AND COALESCE(pc.periodo_fin, DATE(pc.fecha_pago)) >= ?
               AND COALESCE(pc.periodo_inicio, DATE(pc.fecha_pago)) <= ?",
            [$hotelId, $trabajadorId, $fechaInicio, $fechaFin]
        );

        $resumen['pagos_caja_count'] = (int)($row['total'] ?? 0);
        $resumen['pagos_caja_pagados'] = (int)($row['pagados'] ?? 0);
        $resumen['pagos_caja_revertidos'] = (int)($row['revertidos'] ?? 0);
        $resumen['pagos_caja_aplicados'] = $this->decimal($row['pagado_total'] ?? 0);
        $resumen['pagos_caja_revertidos_total'] = $this->decimal($row['revertido_total'] ?? 0);
        $resumen['reversiones_detectadas'] = $this->decimal($row['reversion_caja_total'] ?? 0);
        $resumen['ultimo_pago_caja'] = $row['ultimo_pago'] ?? null;

        return $resumen;
    }

    private function pagosCajaVacioNominaPreview(): array
    {
        return [
            'pagos_caja_count' => 0,
            'pagos_caja_pagados' => 0,
            'pagos_caja_revertidos' => 0,
            'pagos_caja_aplicados' => '0.00',
            'pagos_caja_revertidos_total' => '0.00',
            'reversiones_detectadas' => '0.00',
            'ultimo_pago_caja' => null,
        ];
    }

    private function evaluarNominaPreviewTrabajador(
        array $trabajador,
        array $resumenLaboral,
        array $pagosCaja,
        array $filtros
    ): array {
        $bruto = (float)($resumenLaboral['bruto_periodo'] ?? 0);
        $deducciones = (float)($resumenLaboral['deducciones_informativas'] ?? 0);
        $pagosAplicados = !empty($filtros['incluir_pagos_caja'])
            ? (float)($pagosCaja['pagos_caja_aplicados'] ?? 0)
            : 0.0;
        $neto = $bruto - $deducciones - $pagosAplicados;

        $movimientos = (int)($resumenLaboral['conceptos_count'] ?? 0)
            + (int)($resumenLaboral['anticipos_count'] ?? 0)
            + (int)($resumenLaboral['prestamos_count'] ?? 0)
            + (int)($pagosCaja['pagos_caja_count'] ?? 0);

        $bloqueos = [];
        if (($trabajador['estado'] ?? '') !== 'activo') {
            $bloqueos[] = 'Trabajador no activo.';
        }

        if (!empty($bloqueos)) {
            $estadoPreview = 'bloqueado';
        } elseif ($movimientos === 0) {
            $estadoPreview = 'sin_movimientos';
        } elseif ($neto > 0) {
            $estadoPreview = 'por_pagar';
        } else {
            $estadoPreview = 'cubierto';
        }

        $trabajador['conceptos_count'] = (int)($resumenLaboral['conceptos_count'] ?? 0);
        $trabajador['conceptos_a_favor'] = $this->decimal($resumenLaboral['conceptos_a_favor'] ?? 0);
        $trabajador['conceptos_en_contra'] = $this->decimal($resumenLaboral['conceptos_en_contra'] ?? 0);
        $trabajador['bruto_periodo'] = $this->decimal($bruto);
        $trabajador['anticipos_count'] = (int)($resumenLaboral['anticipos_count'] ?? 0);
        $trabajador['anticipos_saldo'] = $this->decimal($resumenLaboral['anticipos_saldo'] ?? 0);
        $trabajador['prestamos_count'] = (int)($resumenLaboral['prestamos_count'] ?? 0);
        $trabajador['prestamos_saldo'] = $this->decimal($resumenLaboral['prestamos_saldo'] ?? 0);
        $trabajador['deducciones_informativas'] = $this->decimal($deducciones);
        $trabajador['pagos_caja_count'] = (int)($pagosCaja['pagos_caja_count'] ?? 0);
        $trabajador['pagos_caja_pagados'] = (int)($pagosCaja['pagos_caja_pagados'] ?? 0);
        $trabajador['pagos_caja_revertidos'] = (int)($pagosCaja['pagos_caja_revertidos'] ?? 0);
        $trabajador['pagos_caja_aplicados'] = $this->decimal($pagosAplicados);
        $trabajador['pagos_caja_revertidos_total'] = $this->decimal($pagosCaja['pagos_caja_revertidos_total'] ?? 0);
        $trabajador['reversiones_detectadas'] = $this->decimal($pagosCaja['reversiones_detectadas'] ?? 0);
        $trabajador['ultimo_pago_caja'] = $pagosCaja['ultimo_pago_caja'] ?? null;
        $trabajador['neto_sugerido'] = $this->decimal($neto);
        $trabajador['pendiente_pago_sugerido'] = $this->decimal(max(0, $neto));
        $trabajador['estado_preview_nomina'] = $estadoPreview;
        $trabajador['motivo_bloqueo_nomina'] = implode(' ', $bloqueos);

        return $trabajador;
    }

    private function resumenNominaPreview(array $trabajadores): array
    {
        $resumen = $this->resumenVacioNominaPreview();
        foreach ($trabajadores as $trabajador) {
            $estado = (string)($trabajador['estado_preview_nomina'] ?? '');
            $resumen['trabajadores_total']++;
            if ($estado === 'por_pagar') {
                $resumen['por_pagar_count']++;
            } elseif ($estado === 'cubierto') {
                $resumen['cubierto_count']++;
            } elseif ($estado === 'bloqueado') {
                $resumen['bloqueado_count']++;
            } elseif ($estado === 'sin_movimientos') {
                $resumen['sin_movimientos_count']++;
            }

            $resumen['bruto_total'] = $this->decimal((float)$resumen['bruto_total'] + (float)($trabajador['bruto_periodo'] ?? 0));
            $resumen['deducciones_total'] = $this->decimal((float)$resumen['deducciones_total'] + (float)($trabajador['deducciones_informativas'] ?? 0));
            $resumen['pagos_caja_aplicados_total'] = $this->decimal((float)$resumen['pagos_caja_aplicados_total'] + (float)($trabajador['pagos_caja_aplicados'] ?? 0));
            $resumen['reversiones_detectadas_total'] = $this->decimal((float)$resumen['reversiones_detectadas_total'] + (float)($trabajador['reversiones_detectadas'] ?? 0));
            $resumen['neto_sugerido_total'] = $this->decimal((float)$resumen['neto_sugerido_total'] + (float)($trabajador['neto_sugerido'] ?? 0));
            $resumen['pendiente_pago_total'] = $this->decimal((float)$resumen['pendiente_pago_total'] + (float)($trabajador['pendiente_pago_sugerido'] ?? 0));
        }

        return $resumen;
    }

    private function resumenVacioNominaPreview(): array
    {
        return [
            'trabajadores_total' => 0,
            'por_pagar_count' => 0,
            'cubierto_count' => 0,
            'bloqueado_count' => 0,
            'sin_movimientos_count' => 0,
            'bruto_total' => '0.00',
            'deducciones_total' => '0.00',
            'pagos_caja_aplicados_total' => '0.00',
            'reversiones_detectadas_total' => '0.00',
            'neto_sugerido_total' => '0.00',
            'pendiente_pago_total' => '0.00',
        ];
    }

    private function normalizarBooleanoNominaPreview($value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $texto = strtolower(trim((string)$value));
        if (in_array($texto, ['1', 'true', 'si', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($texto, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    private function normalizarFiltrosReportePagosCaja(array $filtros): array
    {
        $estado = strtolower(trim((string)($filtros['estado'] ?? 'todos')));
        if (!in_array($estado, ['todos', 'pagado', 'revertido'], true)) {
            $estado = 'todos';
        }

        $metodo = strtolower(trim((string)($filtros['metodo_pago'] ?? 'todos')));
        if (!in_array($metodo, ['todos', 'efectivo', 'tarjeta', 'transferencia'], true)) {
            $metodo = 'todos';
        }

        $fechaInicio = $this->nullableFecha($filtros['fecha_inicio'] ?? null);
        $fechaFin = $this->nullableFecha($filtros['fecha_fin'] ?? null);
        if ($fechaInicio !== null && $fechaFin !== null && $fechaFin < $fechaInicio) {
            $temporal = $fechaInicio;
            $fechaInicio = $fechaFin;
            $fechaFin = $temporal;
        }

        return [
            'trabajador_id' => max(0, (int)($filtros['trabajador_id'] ?? 0)),
            'buscar' => $this->limpiarTexto($filtros['buscar'] ?? '', 120),
            'estado' => $estado,
            'metodo_pago' => $metodo,
            'corte_id' => max(0, (int)($filtros['corte_id'] ?? 0)),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];
    }

    private function resumenVacioReportePagosCaja(): array
    {
        return [
            'total_registros' => 0,
            'pagados_count' => 0,
            'revertidos_count' => 0,
            'egreso_original_total' => '0.00',
            'pagado_vigente_total' => '0.00',
            'revertido_total' => '0.00',
            'reversion_caja_total' => '0.00',
            'impacto_caja_neto' => '0.00',
        ];
    }

    private function joinsReportePagosCaja(): string
    {
        return "INNER JOIN trabajadores t
                   ON t.id = pc.trabajador_id
                  AND t.hotel_id = pc.hotel_id
                INNER JOIN movimientos_caja mc
                   ON mc.id = pc.movimiento_caja_id
                  AND mc.hotel_id = pc.hotel_id
                INNER JOIN cortes_caja cc
                   ON cc.id = pc.corte_id
                  AND cc.hotel_id = pc.hotel_id
                INNER JOIN cajas c
                   ON c.id = cc.caja_id
                  AND c.hotel_id = pc.hotel_id
                LEFT JOIN movimientos_caja mcr
                   ON mcr.hotel_id = pc.hotel_id
                  AND mcr.tipo = 'ingreso'
                  AND mcr.categoria = 'Reversion Pago laboral'
                  AND mcr.referencia = CONCAT('REV-NOM-TRAB-', pc.trabajador_id, '-PAGO-', pc.id)
                LEFT JOIN usuarios u
                   ON u.id = pc.created_by
                LEFT JOIN usuarios uu
                   ON uu.id = pc.updated_by";
    }

    private function whereReportePagosCaja(int $hotelId, array $filtros): array
    {
        $where = ['pc.hotel_id = ?'];
        $params = [$hotelId];

        if ((int)($filtros['trabajador_id'] ?? 0) > 0) {
            $where[] = 'pc.trabajador_id = ?';
            $params[] = (int)$filtros['trabajador_id'];
        }

        if (($filtros['estado'] ?? 'todos') !== 'todos') {
            $where[] = 'pc.estado = ?';
            $params[] = (string)$filtros['estado'];
        }

        if (($filtros['metodo_pago'] ?? 'todos') !== 'todos') {
            $where[] = 'pc.metodo_pago = ?';
            $params[] = (string)$filtros['metodo_pago'];
        }

        if ((int)($filtros['corte_id'] ?? 0) > 0) {
            $where[] = 'pc.corte_id = ?';
            $params[] = (int)$filtros['corte_id'];
        }

        if (($filtros['fecha_inicio'] ?? null) !== null) {
            $where[] = 'DATE(pc.fecha_pago) >= ?';
            $params[] = (string)$filtros['fecha_inicio'];
        }

        if (($filtros['fecha_fin'] ?? null) !== null) {
            $where[] = 'DATE(pc.fecha_pago) <= ?';
            $params[] = (string)$filtros['fecha_fin'];
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(t.nombre_completo LIKE ? OR t.identificacion LIKE ? OR t.rol_laboral LIKE ? OR pc.referencia LIKE ? OR pc.concepto LIKE ? OR mc.referencia LIKE ? OR c.nombre LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }

        return [implode(' AND ', $where), $params];
    }

    private function normalizarFiltrosSimuladorPagoCaja(array $filtros): array
    {
        $monto = trim((string)($filtros['monto'] ?? ''));
        $montoNormalizado = '';
        $montoInvalido = false;
        if ($monto !== '') {
            if (is_numeric($monto)) {
                $montoNormalizado = $this->decimal($monto);
            } else {
                $montoInvalido = true;
            }
        }

        $metodo = strtolower(trim((string)($filtros['metodo_pago'] ?? 'efectivo')));
        $metodoInvalido = !in_array($metodo, ['efectivo', 'tarjeta', 'transferencia'], true);
        if ($metodoInvalido) {
            $metodo = 'efectivo';
        }

        $periodoInicio = $this->nullableFecha($filtros['periodo_inicio'] ?? null);
        $periodoFin = $this->nullableFecha($filtros['periodo_fin'] ?? null);

        return [
            'trabajador_id' => max(0, (int)($filtros['trabajador_id'] ?? 0)),
            'buscar' => $this->limpiarTexto($filtros['buscar'] ?? '', 120),
            'periodo_inicio' => $periodoInicio,
            'periodo_fin' => $periodoFin,
            'periodo_invalido' => $periodoInicio !== null && $periodoFin !== null && $periodoFin < $periodoInicio,
            'metodo_pago' => $metodo,
            'metodo_pago_invalido' => $metodoInvalido,
            'monto' => $montoNormalizado,
            'monto_invalido' => $montoInvalido,
            'referencia' => $this->limpiarTexto($filtros['referencia'] ?? '', 120),
        ];
    }

    private function resumenLaboralSimuladorPagoCaja(int $trabajadorId, int $hotelId, ?string $periodoInicio, ?string $periodoFin): array
    {
        $resumen = [
            'conceptos_count' => 0,
            'conceptos_a_favor' => '0.00',
            'conceptos_en_contra' => '0.00',
            'anticipos_count' => 0,
            'anticipos_saldo' => '0.00',
            'prestamos_count' => 0,
            'prestamos_saldo' => '0.00',
            'saldo_estimado' => '0.00',
        ];

        if ($trabajadorId <= 0 || $hotelId <= 0) {
            return $resumen;
        }

        if ($this->tablaExiste('trabajador_pagos')) {
            $where = ['hotel_id = ?', 'trabajador_id = ?'];
            $params = [$hotelId, $trabajadorId];
            if ($periodoInicio !== null) {
                $where[] = 'COALESCE(periodo_fin, fecha) >= ?';
                $params[] = $periodoInicio;
            }
            if ($periodoFin !== null) {
                $where[] = 'COALESCE(periodo_inicio, fecha) <= ?';
                $params[] = $periodoFin;
            }

            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'a_favor' THEN monto ELSE 0 END), 0) AS a_favor,
                        COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'en_contra' THEN monto ELSE 0 END), 0) AS en_contra
                 FROM trabajador_pagos
                 WHERE " . implode(' AND ', $where),
                $params
            );
            $resumen['conceptos_count'] = (int)($row['total'] ?? 0);
            $resumen['conceptos_a_favor'] = $this->decimal($row['a_favor'] ?? 0);
            $resumen['conceptos_en_contra'] = $this->decimal($row['en_contra'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_anticipos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_anticipos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['anticipos_count'] = (int)($row['total'] ?? 0);
            $resumen['anticipos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        if ($this->tablaExiste('trabajador_prestamos')) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN estado = 'vigente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
                 FROM trabajador_prestamos
                 WHERE hotel_id = ?
                   AND trabajador_id = ?",
                [$hotelId, $trabajadorId]
            );
            $resumen['prestamos_count'] = (int)($row['total'] ?? 0);
            $resumen['prestamos_saldo'] = $this->decimal($row['saldo'] ?? 0);
        }

        $resumen['saldo_estimado'] = $this->decimal(
            (float)$resumen['conceptos_a_favor']
            - (float)$resumen['conceptos_en_contra']
            - (float)$resumen['anticipos_saldo']
            - (float)$resumen['prestamos_saldo']
        );

        return $resumen;
    }

    private function pagosCajaResumenPorTrabajador(int $trabajadorId, int $hotelId, ?string $periodoInicio, ?string $periodoFin): array
    {
        $resumen = [
            'pagos_caja_count' => 0,
            'pagos_caja_pagados' => 0,
            'pagos_caja_revertidos' => 0,
            'pagos_caja_total' => '0.00',
            'ultimo_pago_caja' => null,
        ];

        if ($trabajadorId <= 0 || $hotelId <= 0 || !$this->tablaExiste('trabajador_pagos_caja')) {
            return $resumen;
        }

        $where = ['hotel_id = ?', 'trabajador_id = ?'];
        $params = [$hotelId, $trabajadorId];
        if ($periodoInicio !== null) {
            $where[] = 'COALESCE(periodo_fin, DATE(fecha_pago)) >= ?';
            $params[] = $periodoInicio;
        }
        if ($periodoFin !== null) {
            $where[] = 'COALESCE(periodo_inicio, DATE(fecha_pago)) <= ?';
            $params[] = $periodoFin;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) AS pagados,
                    SUM(CASE WHEN estado = 'revertido' THEN 1 ELSE 0 END) AS revertidos,
                    COALESCE(SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END), 0) AS monto,
                    MAX(fecha_pago) AS ultimo_pago
             FROM trabajador_pagos_caja
             WHERE " . implode(' AND ', $where),
            $params
        );

        $resumen['pagos_caja_count'] = (int)($row['total'] ?? 0);
        $resumen['pagos_caja_pagados'] = (int)($row['pagados'] ?? 0);
        $resumen['pagos_caja_revertidos'] = (int)($row['revertidos'] ?? 0);
        $resumen['pagos_caja_total'] = $this->decimal($row['monto'] ?? 0);
        $resumen['ultimo_pago_caja'] = $row['ultimo_pago'] ?? null;

        return $resumen;
    }

    private function evaluarSimuladorPagoCajaTrabajador(
        array $trabajador,
        array $resumenLaboral,
        array $pagosCaja,
        ?array $corte,
        array $filtros,
        int $hotelId
    ): array {
        $bloqueos = [];
        $saldoBase = (float)($resumenLaboral['saldo_estimado'] ?? 0);
        $pagosCajaTotal = (float)($pagosCaja['pagos_caja_total'] ?? 0);
        $saldoEstimado = $saldoBase - $pagosCajaTotal;
        $montoSimulado = $filtros['monto'] !== ''
            ? (float)$filtros['monto']
            : max(0, $saldoEstimado);
        $referenciaSugerida = 'NOM-TRAB-' . (int)($trabajador['id'] ?? 0) . '-' . date('YmdHis');
        $referenciaEvaluada = $filtros['referencia'] !== ''
            ? $filtros['referencia']
            : $referenciaSugerida;

        if (empty($corte)) {
            $bloqueos[] = 'No hay corte de Caja abierto para el hotel actual.';
        }
        if (($trabajador['estado'] ?? '') !== 'activo') {
            $bloqueos[] = 'El trabajador no esta activo.';
        }
        if ((int)($trabajador['hotel_id'] ?? 0) !== $hotelId) {
            $bloqueos[] = 'El trabajador no pertenece al hotel actual.';
        }
        if (!empty($filtros['periodo_invalido'])) {
            $bloqueos[] = 'El periodo evaluado no es valido.';
        }
        if (!empty($filtros['metodo_pago_invalido'])) {
            $bloqueos[] = 'El metodo de pago no es valido.';
        }
        if (!empty($filtros['monto_invalido']) || $montoSimulado <= 0) {
            $bloqueos[] = 'El monto simulado debe ser mayor a cero.';
        }
        if ($saldoEstimado <= 0) {
            $bloqueos[] = 'El saldo laboral estimado no es positivo.';
        } elseif ($montoSimulado > $saldoEstimado) {
            $bloqueos[] = 'El monto simulado excede el saldo laboral estimado.';
        }
        if (in_array($filtros['metodo_pago'], ['tarjeta', 'transferencia'], true) && $filtros['referencia'] === '') {
            $bloqueos[] = 'Tarjeta o transferencia requieren referencia manual.';
        }
        if ($referenciaEvaluada === '') {
            $bloqueos[] = 'La referencia no es valida.';
        } elseif ($this->referenciaDuplicadaPagoLaboralCaja($hotelId, $referenciaEvaluada)) {
            $bloqueos[] = 'La referencia ya existe en pagos laborales con Caja.';
        } elseif ($this->referenciaDuplicadaMovimientoCaja($hotelId, $referenciaEvaluada)) {
            $bloqueos[] = 'La referencia ya existe en movimientos de Caja.';
        }

        $trabajador['conceptos_count'] = (int)($resumenLaboral['conceptos_count'] ?? 0);
        $trabajador['conceptos_a_favor'] = $this->decimal($resumenLaboral['conceptos_a_favor'] ?? 0);
        $trabajador['conceptos_en_contra'] = $this->decimal($resumenLaboral['conceptos_en_contra'] ?? 0);
        $trabajador['anticipos_count'] = (int)($resumenLaboral['anticipos_count'] ?? 0);
        $trabajador['anticipos_saldo'] = $this->decimal($resumenLaboral['anticipos_saldo'] ?? 0);
        $trabajador['prestamos_count'] = (int)($resumenLaboral['prestamos_count'] ?? 0);
        $trabajador['prestamos_saldo'] = $this->decimal($resumenLaboral['prestamos_saldo'] ?? 0);
        $trabajador['saldo_base_caja'] = $this->decimal($saldoBase);
        $trabajador['saldo_estimado'] = $this->decimal($saldoEstimado);
        $trabajador['monto_simulado'] = $this->decimal($montoSimulado);
        $trabajador['monto_maximo_sugerido'] = $this->decimal(max(0, $saldoEstimado));
        $trabajador['metodo_pago_simulado'] = $filtros['metodo_pago'];
        $trabajador['referencia_sugerida'] = $referenciaSugerida;
        $trabajador['referencia_evaluada'] = $referenciaEvaluada;
        $trabajador['periodo_inicio_simulado'] = $filtros['periodo_inicio'];
        $trabajador['periodo_fin_simulado'] = $filtros['periodo_fin'];
        $trabajador['pagos_caja_count'] = (int)($pagosCaja['pagos_caja_count'] ?? 0);
        $trabajador['pagos_caja_pagados'] = (int)($pagosCaja['pagos_caja_pagados'] ?? 0);
        $trabajador['pagos_caja_revertidos'] = (int)($pagosCaja['pagos_caja_revertidos'] ?? 0);
        $trabajador['pagos_caja_total'] = $this->decimal($pagosCaja['pagos_caja_total'] ?? 0);
        $trabajador['ultimo_pago_caja'] = $pagosCaja['ultimo_pago_caja'] ?? null;
        $trabajador['es_elegible_caja'] = empty($bloqueos);
        $trabajador['motivo_elegibilidad_caja'] = empty($bloqueos)
            ? 'Trabajador activo, saldo positivo, corte abierto y referencia disponible. Esta pantalla no registra pagos.'
            : '';
        $trabajador['motivo_bloqueo_caja'] = implode(' ', $bloqueos);

        return $trabajador;
    }

    private function corteAbiertoSimuladorPagoCaja(int $hotelId): ?array
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

    private function referenciaDuplicadaPagoLaboralCaja(int $hotelId, string $referencia): bool
    {
        if ($hotelId <= 0 || $referencia === '' || !$this->tablaExiste('trabajador_pagos_caja')) {
            return false;
        }

        $row = $this->fetchOne(
            "SELECT id
             FROM trabajador_pagos_caja
             WHERE hotel_id = ?
               AND referencia = ?
             LIMIT 1",
            [$hotelId, $referencia]
        );

        return !empty($row);
    }

    private function referenciaDuplicadaMovimientoCaja(int $hotelId, string $referencia): bool
    {
        if ($hotelId <= 0 || $referencia === '' || !$this->tablaExiste('movimientos_caja')) {
            return false;
        }

        $row = $this->fetchOne(
            "SELECT id
             FROM movimientos_caja
             WHERE hotel_id = ?
               AND referencia = ?
             LIMIT 1",
            [$hotelId, $referencia]
        );

        return !empty($row);
    }

    private function resumenSimuladorPagoCaja(array $trabajadores, ?array $corte): array
    {
        $resumen = [
            'total' => count($trabajadores),
            'elegibles' => 0,
            'bloqueadas' => 0,
            'saldo_estimado_total' => '0.00',
            'monto_simulado_total' => '0.00',
            'pagos_caja_count' => 0,
            'pagos_caja_total' => '0.00',
            'corte_abierto' => !empty($corte),
        ];

        foreach ($trabajadores as $trabajador) {
            $resumen['saldo_estimado_total'] = $this->decimal(
                (float)$resumen['saldo_estimado_total'] + (float)($trabajador['saldo_estimado'] ?? 0)
            );
            $resumen['pagos_caja_count'] += (int)($trabajador['pagos_caja_count'] ?? 0);
            $resumen['pagos_caja_total'] = $this->decimal(
                (float)$resumen['pagos_caja_total'] + (float)($trabajador['pagos_caja_total'] ?? 0)
            );

            if (!empty($trabajador['es_elegible_caja'])) {
                $resumen['elegibles']++;
                $resumen['monto_simulado_total'] = $this->decimal(
                    (float)$resumen['monto_simulado_total'] + (float)($trabajador['monto_simulado'] ?? 0)
                );
            } else {
                $resumen['bloqueadas']++;
            }
        }

        return $resumen;
    }

    private function normalizarDatosCierreNominaPeriodo(array $datos): array
    {
        $tipoPeriodo = strtolower(trim((string)($datos['tipo_periodo'] ?? 'manual')));
        if (!in_array($tipoPeriodo, ['semanal', 'quincenal', 'mensual', 'manual'], true)) {
            $tipoPeriodo = 'manual';
        }

        $fechaInicio = $this->nullableFecha($datos['fecha_inicio'] ?? null);
        $fechaFin = $this->nullableFecha($datos['fecha_fin'] ?? null);
        if ($fechaInicio === null || $fechaFin === null || $fechaFin < $fechaInicio) {
            throw new Exception('El periodo de cierre no es valido');
        }

        $estado = strtolower(trim((string)($datos['estado'] ?? 'activos')));
        if ($estado !== 'activos') {
            throw new Exception('El cierre persistente solo permite trabajadores activos');
        }

        $rolLaboral = $this->limpiarTexto($datos['rol_laboral'] ?? '', 80);
        if ($rolLaboral !== '') {
            throw new Exception('El cierre persistente no permite filtro por rol laboral');
        }

        $etiqueta = $this->limpiarTexto($datos['etiqueta'] ?? '', 120);
        if ($etiqueta === '') {
            $etiqueta = 'Periodo ' . $fechaInicio . ' a ' . $fechaFin;
        }

        return [
            'tipo_periodo' => $tipoPeriodo,
            'etiqueta' => $etiqueta,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estado' => 'activos',
            'rol_laboral' => '',
            'incluir_pagos_caja' => $this->normalizarBooleanoNominaPreview($datos['incluir_pagos_caja'] ?? null, true),
        ];
    }

    private function insertarDetalleNominaPeriodo(int $periodoId, int $hotelId, array $trabajador): void
    {
        $stmt = $this->db->query(
            "INSERT INTO trabajador_nomina_periodo_detalles
                (periodo_id, hotel_id, trabajador_id, trabajador_nombre,
                 trabajador_identificacion, trabajador_rol, trabajador_estado,
                 estado_preview_nomina, motivo_bloqueo_nomina, conceptos_count,
                 conceptos_a_favor, conceptos_en_contra, bruto_periodo,
                 anticipos_count, anticipos_saldo, prestamos_count, prestamos_saldo,
                 deducciones_informativas, pagos_caja_count, pagos_caja_pagados,
                 pagos_caja_revertidos, pagos_caja_aplicados,
                 pagos_caja_revertidos_total, reversiones_detectadas,
                 ultimo_pago_caja, neto_sugerido, pendiente_pago_sugerido,
                 snapshot_json, created_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $periodoId,
                $hotelId,
                (int)($trabajador['id'] ?? 0),
                $this->limpiarTexto($trabajador['nombre_completo'] ?? '', 160),
                $this->nullableTexto($trabajador['identificacion'] ?? null, 80),
                $this->nullableTexto($trabajador['rol_laboral'] ?? null, 100),
                $this->limpiarTexto($trabajador['estado'] ?? '', 30),
                $this->limpiarTexto($trabajador['estado_preview_nomina'] ?? '', 40),
                $this->nullableTexto($trabajador['motivo_bloqueo_nomina'] ?? null, 255),
                (int)($trabajador['conceptos_count'] ?? 0),
                $this->decimal($trabajador['conceptos_a_favor'] ?? 0),
                $this->decimal($trabajador['conceptos_en_contra'] ?? 0),
                $this->decimal($trabajador['bruto_periodo'] ?? 0),
                (int)($trabajador['anticipos_count'] ?? 0),
                $this->decimal($trabajador['anticipos_saldo'] ?? 0),
                (int)($trabajador['prestamos_count'] ?? 0),
                $this->decimal($trabajador['prestamos_saldo'] ?? 0),
                $this->decimal($trabajador['deducciones_informativas'] ?? 0),
                (int)($trabajador['pagos_caja_count'] ?? 0),
                (int)($trabajador['pagos_caja_pagados'] ?? 0),
                (int)($trabajador['pagos_caja_revertidos'] ?? 0),
                $this->decimal($trabajador['pagos_caja_aplicados'] ?? 0),
                $this->decimal($trabajador['pagos_caja_revertidos_total'] ?? 0),
                $this->decimal($trabajador['reversiones_detectadas'] ?? 0),
                $this->nullableFechaHora($trabajador['ultimo_pago_caja'] ?? null),
                $this->decimal($trabajador['neto_sugerido'] ?? 0),
                $this->decimal($trabajador['pendiente_pago_sugerido'] ?? 0),
                $this->jsonSeguro($trabajador),
            ]
        );

        if (!$stmt) {
            throw new Exception('No se pudo guardar el detalle del snapshot de pre-nomina');
        }
    }

    private function iniciarTransaccionNominaPeriodo(bool $manageTransaction): void
    {
        if ($manageTransaction) {
            $this->db->safeBeginTransaction();
            return;
        }

        if (!$this->db->enTransaccion()) {
            throw new Exception('La operacion requiere una transaccion externa activa');
        }
    }

    private function confirmarTransaccionNominaPeriodo(bool $manageTransaction): void
    {
        if ($manageTransaction) {
            $this->db->safeCommit();
        }
    }

    private function revertirTransaccionNominaPeriodo(bool $manageTransaction): void
    {
        if ($manageTransaction) {
            $this->db->safeRollBack();
        }
    }

    private function registrarEventoNominaPeriodo(
        int $periodoId,
        int $hotelId,
        string $tipo,
        string $estadoResultante,
        string $descripcion,
        ?string $motivo,
        ?int $usuarioId
    ): void {
        $stmt = $this->db->query(
            "INSERT INTO trabajador_nomina_periodo_eventos
                (periodo_id, hotel_id, tipo, estado_resultante, descripcion, motivo, created_by, created_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $periodoId,
                $hotelId,
                $tipo,
                $estadoResultante,
                $this->limpiarTexto($descripcion, 180),
                $this->nullableTexto($motivo, 255),
                $usuarioId,
            ]
        );

        if (!$stmt) {
            throw new Exception('No se pudo registrar el evento del periodo de pre-nomina');
        }
    }

    private function jsonSeguro(array $datos): string
    {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : '{}';
    }

    private function decodificarJsonArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $json = json_decode((string)$value, true);
        return is_array($json) ? $json : [];
    }

    public function normalizarDatos(array $datos): array
    {
        $usuarioId = (int)($datos['usuario_id'] ?? 0);
        $salarioBase = trim((string)($datos['salario_base'] ?? ''));
        $salarioNormalizado = null;
        if ($salarioBase !== '') {
            $salarioNormalizado = is_numeric($salarioBase)
                ? number_format((float)$salarioBase, 2, '.', '')
                : 'INVALIDO';
        }

        return [
            'usuario_id' => $usuarioId > 0 ? $usuarioId : null,
            'nombre_completo' => $this->limpiarTexto($datos['nombre_completo'] ?? '', 150),
            'identificacion' => $this->nullableTexto($datos['identificacion'] ?? null, 60),
            'rol_laboral' => $this->nullableTexto($datos['rol_laboral'] ?? null, 80),
            'telefono' => $this->nullableTexto($datos['telefono'] ?? null, 30),
            'email' => $this->nullableTexto(strtolower((string)($datos['email'] ?? '')), 120),
            'fecha_alta' => $this->nullableFecha($datos['fecha_alta'] ?? null),
            'salario_base' => $salarioNormalizado,
            'periodicidad_pago' => $this->nullablePeriodicidad($datos['periodicidad_pago'] ?? null),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    public function normalizarConceptoLaboral(array $datos): array
    {
        $tipo = strtolower(trim((string)($datos['tipo'] ?? '')));
        $efecto = strtolower(trim((string)($datos['efecto'] ?? '')));
        $monto = trim((string)($datos['monto'] ?? ''));
        $montoNormalizado = is_numeric($monto)
            ? number_format((float)$monto, 2, '.', '')
            : 'INVALIDO';

        if (in_array($tipo, ['comision', 'bono'], true)) {
            $efecto = 'a_favor';
        } elseif ($tipo === 'descuento') {
            $efecto = 'en_contra';
        }

        return [
            'tipo' => $tipo,
            'efecto' => in_array($efecto, ['a_favor', 'en_contra'], true) ? $efecto : '',
            'monto' => $montoNormalizado,
            'concepto' => $this->limpiarTexto($datos['concepto'] ?? '', 160),
            'periodo_inicio' => $this->nullableFecha($datos['periodo_inicio'] ?? null),
            'periodo_fin' => $this->nullableFecha($datos['periodo_fin'] ?? null),
            'fecha' => $this->nullableFecha($datos['fecha'] ?? null),
            'referencia' => $this->nullableTexto($datos['referencia'] ?? null, 120),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    private function validarConceptoLaboral(array $datos): void
    {
        if (!in_array($datos['tipo'], ['comision', 'bono', 'descuento', 'ajuste'], true)) {
            throw new Exception('Tipo de concepto laboral no permitido');
        }

        if (!in_array($datos['efecto'], ['a_favor', 'en_contra'], true)) {
            throw new Exception('Efecto de concepto laboral no valido');
        }

        if ($datos['monto'] === 'INVALIDO' || !is_numeric($datos['monto']) || (float)$datos['monto'] <= 0) {
            throw new Exception('El monto debe ser mayor a cero');
        }

        if ($datos['concepto'] === '') {
            throw new Exception('El concepto es obligatorio');
        }

        if ($datos['fecha'] === null) {
            throw new Exception('La fecha del concepto es obligatoria');
        }

        if ($datos['periodo_inicio'] !== null && $datos['periodo_fin'] !== null && $datos['periodo_fin'] < $datos['periodo_inicio']) {
            throw new Exception('El periodo fin no puede ser anterior al periodo inicio');
        }
    }

    private function normalizarAnticipoLaboral(array $datos): array
    {
        return [
            'monto' => $this->normalizarMontoLaboral($datos['monto'] ?? ''),
            'fecha' => $this->nullableFecha($datos['fecha'] ?? null),
            'motivo' => $this->limpiarTexto($datos['motivo'] ?? '', 160),
            'referencia' => $this->nullableTexto($datos['referencia'] ?? null, 120),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    private function normalizarPrestamoLaboral(array $datos): array
    {
        $plazo = trim((string)($datos['plazo_meses'] ?? ''));
        $abono = trim((string)($datos['abono_periodico'] ?? ''));

        return [
            'monto' => $this->normalizarMontoLaboral($datos['monto'] ?? ''),
            'fecha' => $this->nullableFecha($datos['fecha'] ?? null),
            'plazo_meses' => $plazo === '' ? null : (is_numeric($plazo) ? (int)$plazo : 'INVALIDO'),
            'abono_periodico' => $abono === '' ? null : $this->normalizarMontoLaboral($abono, true),
            'motivo' => $this->limpiarTexto($datos['motivo'] ?? '', 160),
            'referencia' => $this->nullableTexto($datos['referencia'] ?? null, 120),
            'notas' => $this->nullableTexto($datos['notas'] ?? null, 1000),
        ];
    }

    private function normalizarAsistenciaLaboral(array $datos): array
    {
        return [
            'fecha' => $this->nullableFecha($datos['fecha'] ?? null),
            'tipo' => strtolower(trim((string)($datos['tipo'] ?? ''))),
            'hora_entrada' => $this->nullableHora($datos['hora_entrada'] ?? null),
            'hora_salida' => $this->nullableHora($datos['hora_salida'] ?? null),
            'horas' => $this->normalizarHorasLaborales($datos['horas'] ?? null),
            'horas_extra' => $this->normalizarHorasLaborales($datos['horas_extra'] ?? null),
            'observaciones' => $this->nullableTexto($datos['observaciones'] ?? null, 255),
        ];
    }

    private function validarAnticipoLaboral(array $datos): void
    {
        $this->validarMontoLaboral($datos['monto']);

        if ($datos['fecha'] === null) {
            throw new Exception('La fecha del anticipo es obligatoria');
        }

        if ($datos['motivo'] === '') {
            throw new Exception('El motivo del anticipo es obligatorio');
        }
    }

    private function validarPrestamoLaboral(array $datos): void
    {
        $this->validarMontoLaboral($datos['monto']);

        if ($datos['fecha'] === null) {
            throw new Exception('La fecha del prestamo es obligatoria');
        }

        if ($datos['motivo'] === '') {
            throw new Exception('El motivo del prestamo es obligatorio');
        }

        if ($datos['plazo_meses'] === 'INVALIDO' || ($datos['plazo_meses'] !== null && (int)$datos['plazo_meses'] <= 0)) {
            throw new Exception('El plazo del prestamo debe ser mayor a cero cuando se capture');
        }

        if ($datos['abono_periodico'] === 'INVALIDO' || ($datos['abono_periodico'] !== null && (float)$datos['abono_periodico'] < 0)) {
            throw new Exception('El abono periodico debe ser cero o mayor cuando se capture');
        }
    }

    private function validarAsistenciaLaboral(array $datos): void
    {
        $tiposPermitidos = ['asistencia', 'falta', 'retardo', 'permiso', 'incapacidad', 'descanso', 'horas_extra'];

        if ($datos['fecha'] === null) {
            throw new Exception('La fecha de asistencia es obligatoria');
        }

        if (!in_array($datos['tipo'], $tiposPermitidos, true)) {
            throw new Exception('Tipo de asistencia laboral no permitido');
        }

        if ($datos['hora_entrada'] === 'INVALIDO' || $datos['hora_salida'] === 'INVALIDO') {
            throw new Exception('Formato de hora invalido');
        }

        if ($datos['hora_entrada'] !== null && $datos['hora_salida'] !== null && $datos['hora_salida'] < $datos['hora_entrada']) {
            throw new Exception('La hora de salida no puede ser anterior a la hora de entrada');
        }

        if ($datos['horas'] === 'INVALIDO' || ($datos['horas'] !== null && (float)$datos['horas'] < 0)) {
            throw new Exception('Las horas deben ser cero o mayores cuando se capturen');
        }

        if ($datos['horas_extra'] === 'INVALIDO' || ($datos['horas_extra'] !== null && (float)$datos['horas_extra'] < 0)) {
            throw new Exception('Las horas extra deben ser cero o mayores cuando se capturen');
        }
    }

    private function normalizarMontoLaboral($value, bool $permiteCero = false): string
    {
        $monto = trim((string)($value ?? ''));
        if (!is_numeric($monto)) {
            return 'INVALIDO';
        }

        $numero = (float)$monto;
        if ($permiteCero && $numero === 0.0) {
            return '0.00';
        }

        return number_format($numero, 2, '.', '');
    }

    private function normalizarHorasLaborales($value): ?string
    {
        $horas = trim((string)($value ?? ''));
        if ($horas === '') {
            return null;
        }

        return $this->normalizarMontoLaboral($horas, true);
    }

    private function validarMontoLaboral($monto): void
    {
        if ($monto === 'INVALIDO' || !is_numeric($monto) || (float)$monto <= 0) {
            throw new Exception('El monto debe ser mayor a cero');
        }
    }

    private function validarDatos(int $hotelId, array $datos): void
    {
        if ($datos['nombre_completo'] === '') {
            throw new Exception('El nombre completo del trabajador es obligatorio');
        }

        if ($datos['email'] !== null && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El correo del trabajador no es valido');
        }

        if ($datos['salario_base'] !== null && !is_numeric($datos['salario_base'])) {
            throw new Exception('El salario base debe ser numerico');
        }

        if ($datos['salario_base'] !== null && (float)$datos['salario_base'] < 0) {
            throw new Exception('El salario base no puede ser negativo');
        }

        if ($datos['usuario_id'] !== null && !$this->usuarioPerteneceAlHotel((int)$datos['usuario_id'], $hotelId)) {
            throw new Exception('El usuario vinculado no pertenece al hotel actual');
        }
    }

    private function usuarioPerteneceAlHotel(int $usuarioId, int $hotelId): bool
    {
        if ($usuarioId <= 0 || $hotelId <= 0 || !$this->tablaExiste('hotel_usuarios')) {
            return false;
        }

        $stmt = $this->db->query(
            "SELECT 1
             FROM hotel_usuarios hu
             INNER JOIN usuarios u
                ON u.id = hu.usuario_id
             WHERE hu.hotel_id = ?
               AND hu.usuario_id = ?
               AND hu.activo = 1
               AND u.activo = 1
             LIMIT 1",
            [$hotelId, $usuarioId]
        );

        return $stmt !== false && (bool)$stmt->fetch();
    }

    private function fetchOne(string $sql, array $params = []): array
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetch() ?: []) : [];
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

    private function nullableFecha($value): ?string
    {
        $texto = trim((string)($value ?? ''));
        if ($texto === '') {
            return null;
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $texto);
        return $fecha && $fecha->format('Y-m-d') === $texto ? $texto : null;
    }

    private function nullableFechaHora($value): ?string
    {
        $texto = trim((string)($value ?? ''));
        if ($texto === '') {
            return null;
        }

        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $texto);
        return $fecha && $fecha->format('Y-m-d H:i:s') === $texto ? $texto : null;
    }

    private function nullableHora($value)
    {
        $texto = trim((string)($value ?? ''));
        if ($texto === '') {
            return null;
        }

        $hora = DateTime::createFromFormat('H:i', $texto);
        return $hora && $hora->format('H:i') === $texto ? $texto : 'INVALIDO';
    }

    private function nullablePeriodicidad($value): ?string
    {
        $texto = strtolower(trim((string)($value ?? '')));
        return in_array($texto, ['semanal', 'quincenal', 'mensual', 'por_evento'], true)
            ? $texto
            : null;
    }

    private function nullableTexto($value, int $limite): ?string
    {
        $texto = $this->limpiarTexto($value, $limite);
        return $texto === '' ? null : $texto;
    }

    private function limpiarTexto($value, int $limite): string
    {
        $texto = trim((string)($value ?? ''));
        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $limite, 'UTF-8');
        }

        return substr($texto, 0, $limite);
    }
}
