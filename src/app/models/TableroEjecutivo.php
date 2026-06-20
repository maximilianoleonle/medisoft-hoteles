<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class TableroEjecutivo extends Model
{
    private const AREAS = ['todas', 'operacion', 'finanzas', 'inventario', 'personal', 'documentos'];
    private const SEVERIDADES = ['todas', 'ok', 'warning', 'error'];

    public function reporteReadOnlyPorHotel(int $hotelId, array $filtros = []): array
    {
        $hotelId = max(0, $hotelId);
        $filtros = $this->normalizarFiltros($filtros);
        $base = $this->respuestaBase($filtros);

        if ($hotelId <= 0) {
            $base['mensaje'] = 'No hay hotel activo para el tablero ejecutivo.';
            return $base;
        }

        $pdo = $this->db->getConnection();

        try {
            $pdo->exec('START TRANSACTION READ ONLY');

            $base['fuentes'] = $this->fuentes();
            $base['schema_ok'] = $this->schemaOk($base['fuentes']);
            $base['resumen'] = $this->resumen($hotelId, $filtros, $base['fuentes']);
            $base['operacion'] = $this->operacion($hotelId, $filtros, $base['fuentes']);
            $base['finanzas'] = $this->finanzas($hotelId, $filtros, $base['fuentes']);
            $base['inventario'] = $this->inventario($hotelId, $filtros, $base['fuentes']);
            $base['personal'] = $this->personal($hotelId, $filtros, $base['fuentes']);
            $base['documentos'] = $this->documentos($hotelId, $filtros, $base['fuentes']);
            $base['alertas'] = $this->filtrarAlertas(
                $this->alertas($base),
                $filtros
            );
            $base['totales_alertas'] = $this->totalesAlertas($base['alertas']);
        } catch (Throwable $e) {
            error_log('Error en tablero ejecutivo read-only: ' . $e->getMessage());
            $base['schema_ok'] = false;
            $base['mensaje'] = 'No se pudo generar el tablero ejecutivo.';
            $base['alertas'] = [[
                'area' => 'finanzas',
                'severidad' => 'error',
                'titulo' => 'Tablero no disponible',
                'detalle' => 'La lectura ejecutiva fallo; revisar logs antes de exponer KPIs.',
                'valor' => 1,
                'href' => '',
            ]];
            $base['totales_alertas'] = $this->totalesAlertas($base['alertas']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }

        return $base;
    }

    private function normalizarFiltros(array $filtros): array
    {
        $periodo = strtolower(trim((string)($filtros['periodo'] ?? 'mes')));
        if (!in_array($periodo, ['hoy', '7d', '30d', 'mes', 'custom'], true)) {
            $periodo = 'mes';
        }

        $hoy = date('Y-m-d');
        $desde = date('Y-m-01');
        $hasta = $hoy;

        if ($periodo === 'hoy') {
            $desde = $hoy;
        } elseif ($periodo === '7d') {
            $desde = date('Y-m-d', strtotime('-6 days'));
        } elseif ($periodo === '30d') {
            $desde = date('Y-m-d', strtotime('-29 days'));
        } elseif ($periodo === 'custom') {
            $desde = $this->fecha($filtros['fecha_desde'] ?? '') ?: $desde;
            $hasta = $this->fecha($filtros['fecha_hasta'] ?? '') ?: $hasta;
        }

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $area = strtolower(trim((string)($filtros['area'] ?? 'todas')));
        if (!in_array($area, self::AREAS, true)) {
            $area = 'todas';
        }

        $severidad = strtolower(trim((string)($filtros['severidad'] ?? 'todas')));
        if (!in_array($severidad, self::SEVERIDADES, true)) {
            $severidad = 'todas';
        }

        $limit = (int)($filtros['limit'] ?? 10);
        if (!in_array($limit, [5, 10, 25], true)) {
            $limit = 10;
        }

        return [
            'periodo' => $periodo,
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta,
            'area' => $area,
            'severidad' => $severidad,
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

    private function respuestaBase(array $filtros): array
    {
        return [
            'schema_ok' => false,
            'generated_at' => date('Y-m-d H:i:s'),
            'filtros' => $filtros,
            'fuentes' => [],
            'resumen' => $this->resumenBase(),
            'operacion' => $this->operacionBase(),
            'finanzas' => $this->finanzasBase(),
            'inventario' => $this->inventarioBase(),
            'personal' => $this->personalBase(),
            'documentos' => $this->documentosBase(),
            'alertas' => [],
            'totales_alertas' => ['ok' => 0, 'warning' => 0, 'error' => 0],
            'mensaje' => '',
        ];
    }

    private function resumenBase(): array
    {
        return [
            'ingresos_periodo' => 0.0,
            'gastos_periodo' => 0.0,
            'neto_periodo' => 0.0,
            'saldo_cxc' => 0.0,
            'saldo_cxp' => 0.0,
            'cortes_abiertos' => 0,
            'alertas_warning' => 0,
            'alertas_error' => 0,
        ];
    }

    private function operacionBase(): array
    {
        return [
            'habitaciones_total' => 0,
            'habitaciones_disponibles' => 0,
            'habitaciones_ocupadas' => 0,
            'habitaciones_limpieza' => 0,
            'habitaciones_mantenimiento' => 0,
            'ocupacion_pct' => 0.0,
            'reservaciones_vigentes' => 0,
            'checkins_hoy' => 0,
            'checkouts_hoy' => 0,
            'proximas_reservaciones' => [],
        ];
    }

    private function finanzasBase(): array
    {
        return [
            'cxc_pendientes' => 0,
            'cxc_vencidas' => 0,
            'cxc_cobros_periodo' => 0,
            'cxc_reversiones_periodo' => 0,
            'cxp_pendientes' => 0,
            'cxp_vencidas' => 0,
            'cxp_pagos_periodo' => 0,
            'cxp_reversiones_periodo' => 0,
            'cortes_con_diferencia' => 0,
            'metodos' => [],
        ];
    }

    private function inventarioBase(): array
    {
        return [
            'productos_bajo_minimo' => 0,
            'productos_sin_movimiento_30d' => 0,
            'compras_recibidas_periodo' => 0,
            'compras_total_periodo' => 0.0,
            'movimientos_entrada' => 0,
            'movimientos_salida' => 0,
        ];
    }

    private function personalBase(): array
    {
        return [
            'trabajadores_activos' => 0,
            'tareas_activas' => 0,
            'tareas_vencidas' => 0,
            'tareas_por_categoria' => [],
        ];
    }

    private function documentosBase(): array
    {
        return [
            'documentos_periodo' => 0,
            'documentos_archivados' => 0,
            'documentos_por_entidad' => [],
            'auditoria_periodo' => 0,
            'auditoria_sin_hotel' => 0,
        ];
    }

    private function fuentes(): array
    {
        $tablas = [
            'hoteles',
            'reservaciones',
            'reservacion_habitaciones',
            'habitaciones',
            'tipos_habitacion',
            'huespedes',
            'cuentas_por_cobrar',
            'cuentas_por_cobrar_movimientos',
            'cuentas_por_pagar',
            'cuentas_por_pagar_movimientos',
            'movimientos_caja',
            'cortes_caja',
            'cajas',
            'compras',
            'compra_detalles',
            'proveedores',
            'inventario_productos',
            'movimientos_inventario',
            'tareas_operativas',
            'tarea_eventos',
            'trabajadores',
            'ledger_laboral',
            'documentos',
            'documento_entidades',
            'logs_auditoria',
        ];

        $fuentes = [];
        foreach ($tablas as $tabla) {
            $fuentes[$tabla] = $this->tablaExiste($tabla);
        }

        return $fuentes;
    }

    private function schemaOk(array $fuentes): bool
    {
        foreach ($fuentes as $tabla => $disponible) {
            if ($tabla === 'ledger_laboral') {
                continue;
            }
            if (!$disponible) {
                return false;
            }
        }

        return true;
    }

    private function resumen(int $hotelId, array $filtros, array $fuentes): array
    {
        $resumen = $this->resumenBase();

        if ($fuentes['movimientos_caja'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) AS ingresos,
                    COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END), 0) AS gastos
                 FROM movimientos_caja
                 WHERE hotel_id = ?
                   AND DATE(created_at) BETWEEN ? AND ?",
                [$hotelId, $filtros['fecha_desde'], $filtros['fecha_hasta']]
            );
            $resumen['ingresos_periodo'] = (float)($row['ingresos'] ?? 0);
            $resumen['gastos_periodo'] = (float)($row['gastos'] ?? 0);
            $resumen['neto_periodo'] = $resumen['ingresos_periodo'] - $resumen['gastos_periodo'];
        }

        if ($fuentes['cuentas_por_cobrar'] ?? false) {
            $row = $this->fetchOne(
                "SELECT COALESCE(SUM(saldo), 0) AS saldo
                 FROM cuentas_por_cobrar
                 WHERE hotel_id = ?
                   AND saldo > 0
                   AND estado NOT IN ('pagada', 'liquidada', 'cancelada', 'incobrable')",
                [$hotelId]
            );
            $resumen['saldo_cxc'] = (float)($row['saldo'] ?? 0);
        }

        if ($fuentes['cuentas_por_pagar'] ?? false) {
            $row = $this->fetchOne(
                "SELECT COALESCE(SUM(saldo), 0) AS saldo
                 FROM cuentas_por_pagar
                 WHERE hotel_id = ?
                   AND saldo > 0
                   AND estado NOT IN ('pagada', 'cancelada')",
                [$hotelId]
            );
            $resumen['saldo_cxp'] = (float)($row['saldo'] ?? 0);
        }

        if ($fuentes['cortes_caja'] ?? false) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM cortes_caja
                 WHERE hotel_id = ?
                   AND estado = 'abierto'",
                [$hotelId]
            );
            $resumen['cortes_abiertos'] = (int)($row['total'] ?? 0);
        }

        return $resumen;
    }

    private function operacion(int $hotelId, array $filtros, array $fuentes): array
    {
        $operacion = $this->operacionBase();

        if ($fuentes['habitaciones'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
                    SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) AS ocupadas,
                    SUM(CASE WHEN estado = 'limpieza' THEN 1 ELSE 0 END) AS limpieza,
                    SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) AS mantenimiento
                 FROM habitaciones
                 WHERE hotel_id = ?
                   AND activa = 1",
                [$hotelId]
            );
            $operacion['habitaciones_total'] = (int)($row['total'] ?? 0);
            $operacion['habitaciones_disponibles'] = (int)($row['disponibles'] ?? 0);
            $operacion['habitaciones_ocupadas'] = (int)($row['ocupadas'] ?? 0);
            $operacion['habitaciones_limpieza'] = (int)($row['limpieza'] ?? 0);
            $operacion['habitaciones_mantenimiento'] = (int)($row['mantenimiento'] ?? 0);
            $operacion['ocupacion_pct'] = $operacion['habitaciones_total'] > 0
                ? round(($operacion['habitaciones_ocupadas'] / $operacion['habitaciones_total']) * 100, 1)
                : 0.0;
        }

        if ($fuentes['reservaciones'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    SUM(CASE WHEN fecha_entrada <= ? AND fecha_salida >= ? AND estado NOT IN ('cancelada', 'cancelado') THEN 1 ELSE 0 END) AS vigentes,
                    SUM(CASE WHEN fecha_entrada = CURDATE() AND estado NOT IN ('cancelada', 'cancelado') THEN 1 ELSE 0 END) AS checkins,
                    SUM(CASE WHEN fecha_salida = CURDATE() AND estado NOT IN ('cancelada', 'cancelado') THEN 1 ELSE 0 END) AS checkouts
                 FROM reservaciones
                 WHERE hotel_id = ?",
                [$filtros['fecha_hasta'], $filtros['fecha_desde'], $hotelId]
            );
            $operacion['reservaciones_vigentes'] = (int)($row['vigentes'] ?? 0);
            $operacion['checkins_hoy'] = (int)($row['checkins'] ?? 0);
            $operacion['checkouts_hoy'] = (int)($row['checkouts'] ?? 0);
            $operacion['proximas_reservaciones'] = $this->fetchAll(
                "SELECT
                    r.id,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.estado,
                    h.nombre_completo AS huesped
                 FROM reservaciones r
                 LEFT JOIN huespedes h ON h.id = r.huesped_id
                 WHERE r.hotel_id = ?
                   AND r.fecha_entrada >= CURDATE()
                   AND r.estado NOT IN ('cancelada', 'cancelado')
                 ORDER BY r.fecha_entrada ASC, r.id ASC
                 LIMIT 6",
                [$hotelId]
            );
        }

        return $operacion;
    }

    private function finanzas(int $hotelId, array $filtros, array $fuentes): array
    {
        $finanzas = $this->finanzasBase();

        if ($fuentes['cuentas_por_cobrar'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    COUNT(*) AS pendientes,
                    SUM(CASE WHEN fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END) AS vencidas
                 FROM cuentas_por_cobrar
                 WHERE hotel_id = ?
                   AND saldo > 0
                   AND estado NOT IN ('pagada', 'liquidada', 'cancelada', 'incobrable')",
                [$hotelId]
            );
            $finanzas['cxc_pendientes'] = (int)($row['pendientes'] ?? 0);
            $finanzas['cxc_vencidas'] = (int)($row['vencidas'] ?? 0);
        }

        if ($fuentes['cuentas_por_cobrar_movimientos'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    SUM(CASE WHEN tipo_movimiento = 'COBRO' THEN 1 ELSE 0 END) AS cobros,
                    SUM(CASE WHEN tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%' THEN 1 ELSE 0 END) AS reversiones
                 FROM cuentas_por_cobrar_movimientos
                 WHERE hotel_id = ?
                   AND DATE(created_at) BETWEEN ? AND ?",
                [$hotelId, $filtros['fecha_desde'], $filtros['fecha_hasta']]
            );
            $finanzas['cxc_cobros_periodo'] = (int)($row['cobros'] ?? 0);
            $finanzas['cxc_reversiones_periodo'] = (int)($row['reversiones'] ?? 0);
        }

        if ($fuentes['cuentas_por_pagar'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    COUNT(*) AS pendientes,
                    SUM(CASE WHEN fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END) AS vencidas
                 FROM cuentas_por_pagar
                 WHERE hotel_id = ?
                   AND saldo > 0
                   AND estado NOT IN ('pagada', 'cancelada')",
                [$hotelId]
            );
            $finanzas['cxp_pendientes'] = (int)($row['pendientes'] ?? 0);
            $finanzas['cxp_vencidas'] = (int)($row['vencidas'] ?? 0);
        }

        if ($fuentes['cuentas_por_pagar_movimientos'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    SUM(CASE WHEN tipo_movimiento = 'PAGO_REFERENCIAL' THEN 1 ELSE 0 END) AS pagos,
                    SUM(CASE WHEN tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXP-%' THEN 1 ELSE 0 END) AS reversiones
                 FROM cuentas_por_pagar_movimientos
                 WHERE hotel_id = ?
                   AND DATE(created_at) BETWEEN ? AND ?",
                [$hotelId, $filtros['fecha_desde'], $filtros['fecha_hasta']]
            );
            $finanzas['cxp_pagos_periodo'] = (int)($row['pagos'] ?? 0);
            $finanzas['cxp_reversiones_periodo'] = (int)($row['reversiones'] ?? 0);
        }

        if ($fuentes['cortes_caja'] ?? false) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM cortes_caja
                 WHERE hotel_id = ?
                   AND estado = 'cerrado'
                   AND ABS(COALESCE(diferencia, 0)) > 0.009",
                [$hotelId]
            );
            $finanzas['cortes_con_diferencia'] = (int)($row['total'] ?? 0);
        }

        if ($fuentes['movimientos_caja'] ?? false) {
            $finanzas['metodos'] = $this->fetchAll(
                "SELECT
                    metodo_pago,
                    SUM(CASE WHEN tipo = 'ingreso' THEN 1 ELSE 0 END) AS ingresos_count,
                    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) AS ingresos_total,
                    SUM(CASE WHEN tipo = 'gasto' THEN 1 ELSE 0 END) AS gastos_count,
                    COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END), 0) AS gastos_total
                 FROM movimientos_caja
                 WHERE hotel_id = ?
                   AND DATE(created_at) BETWEEN ? AND ?
                 GROUP BY metodo_pago
                 ORDER BY metodo_pago",
                [$hotelId, $filtros['fecha_desde'], $filtros['fecha_hasta']]
            );
        }

        return $finanzas;
    }

    private function inventario(int $hotelId, array $filtros, array $fuentes): array
    {
        $inventario = $this->inventarioBase();

        if ($fuentes['inventario_productos'] ?? false) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM inventario_productos
                 WHERE hotel_id = ?
                   AND activo = 1
                   AND stock_actual <= stock_minimo",
                [$hotelId]
            );
            $inventario['productos_bajo_minimo'] = (int)($row['total'] ?? 0);

            if ($fuentes['movimientos_inventario'] ?? false) {
                $row = $this->fetchOne(
                    "SELECT COUNT(*) AS total
                     FROM inventario_productos p
                     LEFT JOIN movimientos_inventario m
                       ON m.producto_id = p.id
                      AND m.hotel_id = p.hotel_id
                      AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     WHERE p.hotel_id = ?
                       AND p.activo = 1
                       AND m.id IS NULL",
                    [$hotelId]
                );
                $inventario['productos_sin_movimiento_30d'] = (int)($row['total'] ?? 0);
            }
        }

        if ($fuentes['compras'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    COUNT(*) AS compras,
                    COALESCE(SUM(total), 0) AS total
                 FROM compras
                 WHERE hotel_id = ?
                   AND estado = 'recibida'
                   AND DATE(fecha_compra) BETWEEN ? AND ?",
                [$hotelId, $filtros['fecha_desde'], $filtros['fecha_hasta']]
            );
            $inventario['compras_recibidas_periodo'] = (int)($row['compras'] ?? 0);
            $inventario['compras_total_periodo'] = (float)($row['total'] ?? 0);
        }

        if ($fuentes['movimientos_inventario'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    SUM(CASE WHEN tipo_movimiento IN ('entrada', 'ajuste_entrada') THEN 1 ELSE 0 END) AS entradas,
                    SUM(CASE WHEN tipo_movimiento IN ('salida', 'ajuste_salida') THEN 1 ELSE 0 END) AS salidas
                 FROM movimientos_inventario
                 WHERE hotel_id = ?
                   AND DATE(created_at) BETWEEN ? AND ?",
                [$hotelId, $filtros['fecha_desde'], $filtros['fecha_hasta']]
            );
            $inventario['movimientos_entrada'] = (int)($row['entradas'] ?? 0);
            $inventario['movimientos_salida'] = (int)($row['salidas'] ?? 0);
        }

        return $inventario;
    }

    private function personal(int $hotelId, array $filtros, array $fuentes): array
    {
        $personal = $this->personalBase();

        if ($fuentes['trabajadores'] ?? false) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM trabajadores
                 WHERE hotel_id = ?
                   AND estado = 'activo'",
                [$hotelId]
            );
            $personal['trabajadores_activos'] = (int)($row['total'] ?? 0);
        }

        if ($fuentes['tareas_operativas'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    SUM(CASE WHEN estado IN ('pendiente', 'asignada', 'en_proceso') THEN 1 ELSE 0 END) AS activas,
                    SUM(CASE WHEN estado IN ('pendiente', 'asignada', 'en_proceso') AND fecha_limite IS NOT NULL AND fecha_limite < NOW() THEN 1 ELSE 0 END) AS vencidas
                 FROM tareas_operativas
                 WHERE hotel_id = ?",
                [$hotelId]
            );
            $personal['tareas_activas'] = (int)($row['activas'] ?? 0);
            $personal['tareas_vencidas'] = (int)($row['vencidas'] ?? 0);
            $personal['tareas_por_categoria'] = $this->fetchAll(
                "SELECT categoria, COUNT(*) AS total
                 FROM tareas_operativas
                 WHERE hotel_id = ?
                   AND estado IN ('pendiente', 'asignada', 'en_proceso')
                 GROUP BY categoria
                 ORDER BY total DESC, categoria ASC",
                [$hotelId]
            );
        }

        return $personal;
    }

    private function documentos(int $hotelId, array $filtros, array $fuentes): array
    {
        $documentos = $this->documentosBase();

        if ($fuentes['documentos'] ?? false) {
            $row = $this->fetchOne(
                "SELECT
                    COUNT(*) AS periodo,
                    SUM(CASE WHEN estado = 'archivado' THEN 1 ELSE 0 END) AS archivados
                 FROM documentos
                 WHERE hotel_id = ?
                   AND DATE(created_at) BETWEEN ? AND ?",
                [$hotelId, $filtros['fecha_desde'], $filtros['fecha_hasta']]
            );
            $documentos['documentos_periodo'] = (int)($row['periodo'] ?? 0);
            $documentos['documentos_archivados'] = (int)($row['archivados'] ?? 0);
        }

        if ($fuentes['documento_entidades'] ?? false) {
            $documentos['documentos_por_entidad'] = $this->fetchAll(
                "SELECT entidad_tipo, COUNT(*) AS total
                 FROM documento_entidades
                 WHERE hotel_id = ?
                 GROUP BY entidad_tipo
                 ORDER BY total DESC, entidad_tipo ASC
                 LIMIT 8",
                [$hotelId]
            );
        }

        if ($fuentes['logs_auditoria'] ?? false) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM logs_auditoria
                 WHERE hotel_id = ?
                   AND DATE(created_at) BETWEEN ? AND ?",
                [$hotelId, $filtros['fecha_desde'], $filtros['fecha_hasta']]
            );
            $documentos['auditoria_periodo'] = (int)($row['total'] ?? 0);
            $row = $this->fetchOne(
                'SELECT COUNT(*) AS total FROM logs_auditoria WHERE hotel_id IS NULL'
            );
            $documentos['auditoria_sin_hotel'] = (int)($row['total'] ?? 0);
        }

        return $documentos;
    }

    private function alertas(array $reporte): array
    {
        $alertas = [];
        $fuentes = $reporte['fuentes'];
        foreach ($fuentes as $tabla => $disponible) {
            if ($tabla === 'ledger_laboral') {
                if (!$disponible) {
                    $alertas[] = $this->alerta('personal', 'warning', 'Ledger laboral opcional no disponible', 'El KPI laboral pendiente queda degradado hasta que exista una fuente dedicada.', 1, '');
                }
                continue;
            }
            if (!$disponible) {
                $alertas[] = $this->alerta('finanzas', 'error', 'Fuente no disponible: ' . $tabla, 'El tablero debe degradar esta seccion hasta completar el esquema.', 1, '');
            }
        }

        $op = $reporte['operacion'];
        $fin = $reporte['finanzas'];
        $inv = $reporte['inventario'];
        $per = $reporte['personal'];
        $doc = $reporte['documentos'];

        $alertas[] = $this->alerta('finanzas', 'warning', 'Reporte gerencial diario separado', 'El tablero ejecutivo no archiva notificaciones al abrirse.', 1, 'reportes/gerencial-diario');

        if (($op['habitaciones_mantenimiento'] ?? 0) > 0) {
            $alertas[] = $this->alerta('operacion', 'warning', 'Habitaciones en mantenimiento', 'Hay habitaciones fuera de disponibilidad operativa.', (int)$op['habitaciones_mantenimiento'], 'habitaciones?estado=mantenimiento');
        }
        if (($op['habitaciones_limpieza'] ?? 0) > 0) {
            $alertas[] = $this->alerta('operacion', 'warning', 'Habitaciones en limpieza', 'Hay habitaciones pendientes de limpieza.', (int)$op['habitaciones_limpieza'], 'habitaciones?estado=limpieza');
        }
        if (($fin['cxc_vencidas'] ?? 0) > 0) {
            $alertas[] = $this->alerta('finanzas', 'warning', 'CxC vencidas', 'Hay cuentas por cobrar con vencimiento anterior a hoy.', (int)$fin['cxc_vencidas'], 'cuentas-por-cobrar');
        }
        if (($fin['cxp_vencidas'] ?? 0) > 0) {
            $alertas[] = $this->alerta('finanzas', 'warning', 'CxP vencidas', 'Hay cuentas por pagar con vencimiento anterior a hoy.', (int)$fin['cxp_vencidas'], 'cuentas-por-pagar');
        }
        if (($reporte['resumen']['cortes_abiertos'] ?? 0) > 0) {
            $alertas[] = $this->alerta('finanzas', 'warning', 'Cortes abiertos', 'Existen cortes de caja abiertos.', (int)$reporte['resumen']['cortes_abiertos'], 'caja/arqueo-metodos?estado_corte=abierto');
        }
        if (($fin['cortes_con_diferencia'] ?? 0) > 0) {
            $alertas[] = $this->alerta('finanzas', 'warning', 'Cortes con diferencia', 'Hay cortes cerrados con diferencia registrada.', (int)$fin['cortes_con_diferencia'], 'caja/arqueo-metodos?severidad=warning');
        }
        if (($inv['productos_bajo_minimo'] ?? 0) > 0) {
            $alertas[] = $this->alerta('inventario', 'warning', 'Productos bajo minimo', 'Hay productos activos con stock igual o inferior al minimo.', (int)$inv['productos_bajo_minimo'], 'inventario');
        }
        if (($inv['productos_sin_movimiento_30d'] ?? 0) > 0) {
            $alertas[] = $this->alerta('inventario', 'warning', 'Productos sin movimiento reciente', 'Hay productos activos sin movimiento en 30 dias.', (int)$inv['productos_sin_movimiento_30d'], 'inventario/movimientos');
        }
        if (($per['tareas_vencidas'] ?? 0) > 0) {
            $alertas[] = $this->alerta('personal', 'warning', 'Tareas vencidas', 'Hay tareas operativas activas con fecha limite vencida.', (int)$per['tareas_vencidas'], 'tareas');
        }
        if (($doc['auditoria_sin_hotel'] ?? 0) > 0) {
            $alertas[] = $this->alerta('documentos', 'warning', 'Auditoria historica sin hotel', 'Existen eventos historicos sin hotel_id; no se usan para automatizaciones.', (int)$doc['auditoria_sin_hotel'], '');
        }

        if (empty($alertas)) {
            $alertas[] = $this->alerta('operacion', 'ok', 'Sin alertas criticas', 'No hay warnings activos para los filtros actuales.', 0, '');
        }

        return $alertas;
    }

    private function alerta(string $area, string $severidad, string $titulo, string $detalle, int $valor, string $href): array
    {
        return [
            'area' => $area,
            'severidad' => $severidad,
            'titulo' => $titulo,
            'detalle' => $detalle,
            'valor' => $valor,
            'href' => $href,
        ];
    }

    private function filtrarAlertas(array $alertas, array $filtros): array
    {
        $filtradas = [];
        foreach ($alertas as $alerta) {
            if ($filtros['area'] !== 'todas' && ($alerta['area'] ?? '') !== $filtros['area']) {
                continue;
            }
            if ($filtros['severidad'] !== 'todas' && ($alerta['severidad'] ?? '') !== $filtros['severidad']) {
                continue;
            }
            $filtradas[] = $alerta;
        }

        return array_slice($filtradas, 0, $filtros['limit']);
    }

    private function totalesAlertas(array $alertas): array
    {
        $totales = ['ok' => 0, 'warning' => 0, 'error' => 0];
        foreach ($alertas as $alerta) {
            $sev = (string)($alerta['severidad'] ?? 'warning');
            if (!isset($totales[$sev])) {
                $sev = 'warning';
            }
            $totales[$sev]++;
        }

        return $totales;
    }

    private function fetchOne(string $sql, array $params = []): array
    {
        $stmt = $this->db->query($sql, $params);
        $row = $stmt ? $stmt->fetch() : [];
        return is_array($row) ? $row : [];
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?',
            [$tabla]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return (int)($row['total'] ?? 0) > 0;
    }
}
