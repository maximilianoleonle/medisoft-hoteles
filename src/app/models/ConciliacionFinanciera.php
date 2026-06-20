<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class ConciliacionFinanciera extends Model
{
    public function reporteReadOnlyPorHotel(int $hotelId, array $filtros = []): array
    {
        $filtros = $this->normalizarFiltros($filtros);
        $base = [
            'consultado_en' => date('Y-m-d H:i:s'),
            'filtros' => $filtros,
            'schema_ok' => false,
            'tablas' => [],
            'resumen' => $this->resumenVacio(),
            'alertas' => [],
            'alertas_paginadas' => [],
            'totales_alertas' => ['ok' => 0, 'warning' => 0, 'error' => 0, 'hallazgos' => 0],
            'paginacion' => [
                'page' => $filtros['page'],
                'limit' => $filtros['limit'],
                'total' => 0,
                'pages' => 1,
            ],
        ];

        if ($hotelId <= 0) {
            return $base;
        }

        $pdo = $this->db->getConnection();
        try {
            $pdo->exec('START TRANSACTION READ ONLY');
            $base['tablas'] = $this->tablasRequeridas();
            $base['schema_ok'] = !in_array(false, $base['tablas'], true);

            if (!$base['schema_ok']) {
                $base['alertas'][] = [
                    'tipo' => 'sistema',
                    'severidad' => 'error',
                    'estado' => 'ERROR',
                    'codigo' => 'SCHEMA_INCOMPLETO',
                    'titulo' => 'Esquema financiero incompleto',
                    'conteo' => 1,
                    'detalle' => 'Falta al menos una tabla requerida para la conciliacion.',
                    'referencia' => '9A-B-A',
                    'destino' => '',
                ];
            } else {
                $base['resumen'] = $this->resumenGeneral($hotelId);
                $base['alertas'] = $this->alertas($hotelId, $filtros);
            }

            $base = $this->aplicarFiltrosYPaginacion($base, $filtros);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }

        return $base;
    }

    public function normalizarFiltros(array $filtros): array
    {
        $tipo = strtolower(trim((string)($filtros['tipo'] ?? 'todos')));
        if (!in_array($tipo, ['todos', 'cxc', 'cxp', 'caja', 'auditoria', 'sistema'], true)) {
            $tipo = 'todos';
        }

        $severidad = strtolower(trim((string)($filtros['severidad'] ?? 'todos')));
        if (!in_array($severidad, ['todos', 'ok', 'warning', 'error'], true)) {
            $severidad = 'todos';
        }

        $fechaDesde = $this->fechaFiltro($filtros['fecha_desde'] ?? '');
        $fechaHasta = $this->fechaFiltro($filtros['fecha_hasta'] ?? '');
        if ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
        }

        $corteId = max(0, (int)($filtros['corte_id'] ?? 0));
        $referencia = trim((string)($filtros['referencia'] ?? ''));
        $referencia = substr($referencia, 0, 100);

        $page = max(1, (int)($filtros['page'] ?? 1));
        $limit = (int)($filtros['limit'] ?? 25);
        if (!in_array($limit, [10, 25, 50], true)) {
            $limit = 25;
        }

        return [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'tipo' => $tipo,
            'severidad' => $severidad,
            'corte_id' => $corteId,
            'referencia' => $referencia,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    private function resumenVacio(): array
    {
        return [
            'cxc' => [
                'cuentas_abiertas' => 0,
                'cuentas_liquidadas' => 0,
                'saldo_pendiente' => '0.00',
                'cobros' => 0,
                'cobros_importe' => '0.00',
                'reversiones' => 0,
                'reversiones_importe' => '0.00',
            ],
            'cxp' => [
                'cuentas_abiertas' => 0,
                'cuentas_pagadas' => 0,
                'saldo_pendiente' => '0.00',
                'pagos' => 0,
                'pagos_importe' => '0.00',
                'reversiones' => 0,
                'reversiones_importe' => '0.00',
            ],
            'caja' => [
                'movimientos_cxc' => 0,
                'neto_cxc' => '0.00',
                'movimientos_cxp' => 0,
                'neto_cxp' => '0.00',
                'cortes_abiertos' => 0,
                'cortes_cerrados' => 0,
            ],
            'auditoria' => [
                'cobros_cxc' => 0,
                'reversiones_cxc' => 0,
                'pagos_cxp' => 0,
                'reversiones_cxp' => 0,
            ],
        ];
    }

    private function tablasRequeridas(): array
    {
        $tablas = [
            'hoteles',
            'cuentas_por_cobrar',
            'cuentas_por_cobrar_movimientos',
            'cuentas_por_pagar',
            'cuentas_por_pagar_movimientos',
            'movimientos_caja',
            'cortes_caja',
            'cajas',
            'logs_auditoria',
            'reservaciones',
            'compras',
            'proveedores',
        ];

        $resultado = [];
        foreach ($tablas as $tabla) {
            $resultado[$tabla] = $this->tablaExiste($tabla);
        }

        return $resultado;
    }

    private function resumenGeneral(int $hotelId): array
    {
        $resumen = $this->resumenVacio();

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN estado IN ('pendiente', 'parcial', 'vencida') AND saldo > 0 THEN 1 ELSE 0 END) AS abiertas,
                SUM(CASE WHEN estado = 'liquidada' OR saldo = 0 THEN 1 ELSE 0 END) AS liquidadas,
                COALESCE(SUM(CASE WHEN estado NOT IN ('cancelada', 'incobrable') THEN saldo ELSE 0 END), 0) AS saldo
             FROM cuentas_por_cobrar
             WHERE hotel_id = ?",
            [$hotelId]
        );
        $resumen['cxc']['cuentas_abiertas'] = (int)($row['abiertas'] ?? 0);
        $resumen['cxc']['cuentas_liquidadas'] = (int)($row['liquidadas'] ?? 0);
        $resumen['cxc']['saldo_pendiente'] = $this->decimal($row['saldo'] ?? 0);

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN tipo_movimiento = 'COBRO' THEN 1 ELSE 0 END) AS cobros,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'COBRO' THEN monto ELSE 0 END), 0) AS cobros_importe,
                SUM(CASE WHEN tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%' THEN 1 ELSE 0 END) AS reversiones,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%' THEN monto ELSE 0 END), 0) AS reversiones_importe
             FROM cuentas_por_cobrar_movimientos
             WHERE hotel_id = ?",
            [$hotelId]
        );
        $resumen['cxc']['cobros'] = (int)($row['cobros'] ?? 0);
        $resumen['cxc']['cobros_importe'] = $this->decimal($row['cobros_importe'] ?? 0);
        $resumen['cxc']['reversiones'] = (int)($row['reversiones'] ?? 0);
        $resumen['cxc']['reversiones_importe'] = $this->decimal($row['reversiones_importe'] ?? 0);

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN estado IN ('pendiente', 'parcial', 'vencida') AND saldo > 0 THEN 1 ELSE 0 END) AS abiertas,
                SUM(CASE WHEN estado = 'pagada' OR saldo = 0 THEN 1 ELSE 0 END) AS pagadas,
                COALESCE(SUM(CASE WHEN estado <> 'cancelada' THEN saldo ELSE 0 END), 0) AS saldo
             FROM cuentas_por_pagar
             WHERE hotel_id = ?",
            [$hotelId]
        );
        $resumen['cxp']['cuentas_abiertas'] = (int)($row['abiertas'] ?? 0);
        $resumen['cxp']['cuentas_pagadas'] = (int)($row['pagadas'] ?? 0);
        $resumen['cxp']['saldo_pendiente'] = $this->decimal($row['saldo'] ?? 0);

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN tipo_movimiento = 'PAGO_REFERENCIAL' THEN 1 ELSE 0 END) AS pagos,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'PAGO_REFERENCIAL' THEN monto ELSE 0 END), 0) AS pagos_importe,
                SUM(CASE WHEN tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXP-%' THEN 1 ELSE 0 END) AS reversiones,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXP-%' THEN monto ELSE 0 END), 0) AS reversiones_importe
             FROM cuentas_por_pagar_movimientos
             WHERE hotel_id = ?",
            [$hotelId]
        );
        $resumen['cxp']['pagos'] = (int)($row['pagos'] ?? 0);
        $resumen['cxp']['pagos_importe'] = $this->decimal($row['pagos_importe'] ?? 0);
        $resumen['cxp']['reversiones'] = (int)($row['reversiones'] ?? 0);
        $resumen['cxp']['reversiones_importe'] = $this->decimal($row['reversiones_importe'] ?? 0);

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN categoria IN ('Cobro CxC', 'Reversion Cobro CxC') THEN 1 ELSE 0 END) AS movimientos_cxc,
                COALESCE(SUM(CASE WHEN categoria IN ('Cobro CxC', 'Reversion Cobro CxC') THEN CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END ELSE 0 END), 0) AS neto_cxc,
                SUM(CASE WHEN categoria IN ('Pago proveedor', 'Reversion Pago proveedor') THEN 1 ELSE 0 END) AS movimientos_cxp,
                COALESCE(SUM(CASE WHEN categoria IN ('Pago proveedor', 'Reversion Pago proveedor') THEN CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END ELSE 0 END), 0) AS neto_cxp
             FROM movimientos_caja
             WHERE hotel_id = ?",
            [$hotelId]
        );
        $resumen['caja']['movimientos_cxc'] = (int)($row['movimientos_cxc'] ?? 0);
        $resumen['caja']['neto_cxc'] = $this->decimal($row['neto_cxc'] ?? 0);
        $resumen['caja']['movimientos_cxp'] = (int)($row['movimientos_cxp'] ?? 0);
        $resumen['caja']['neto_cxp'] = $this->decimal($row['neto_cxp'] ?? 0);

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN cc.estado = 'abierto' THEN 1 ELSE 0 END) AS abiertos,
                SUM(CASE WHEN cc.estado = 'cerrado' THEN 1 ELSE 0 END) AS cerrados
             FROM movimientos_caja mc
             INNER JOIN cortes_caja cc
                ON cc.id = mc.corte_id
               AND cc.hotel_id = mc.hotel_id
             WHERE mc.hotel_id = ?
               AND mc.categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor')",
            [$hotelId]
        );
        $resumen['caja']['cortes_abiertos'] = (int)($row['abiertos'] ?? 0);
        $resumen['caja']['cortes_cerrados'] = (int)($row['cerrados'] ?? 0);

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN accion = 'cuentas_por_cobrar.cobro_caja_registrado' THEN 1 ELSE 0 END) AS cobros_cxc,
                SUM(CASE WHEN accion = 'cuentas_por_cobrar.cobro_caja_revertido' THEN 1 ELSE 0 END) AS reversiones_cxc,
                SUM(CASE WHEN accion = 'cuentas_por_pagar.pago_caja_registrado' THEN 1 ELSE 0 END) AS pagos_cxp,
                SUM(CASE WHEN accion = 'cuentas_por_pagar.pago_caja_revertido' THEN 1 ELSE 0 END) AS reversiones_cxp
             FROM logs_auditoria
             WHERE hotel_id = ?
               AND entidad_tipo IN ('cuentas_por_cobrar', 'cuentas_por_pagar')",
            [$hotelId]
        );
        $resumen['auditoria']['cobros_cxc'] = (int)($row['cobros_cxc'] ?? 0);
        $resumen['auditoria']['reversiones_cxc'] = (int)($row['reversiones_cxc'] ?? 0);
        $resumen['auditoria']['pagos_cxp'] = (int)($row['pagos_cxp'] ?? 0);
        $resumen['auditoria']['reversiones_cxp'] = (int)($row['reversiones_cxp'] ?? 0);

        return $resumen;
    }

    private function alertas(int $hotelId, array $filtros): array
    {
        $alertas = [];

        $this->agregar($alertas, 'cxc', 'error', 'CXC_SALDO_RANGO', 'CxC con saldo fuera de rango', $this->countCxcCuenta($hotelId, $filtros, 'saldo < 0 OR total < 0 OR saldo > total'), 'saldo menor a cero, total menor a cero o saldo mayor al total.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'cxc', 'error', 'CXC_LIQUIDADA_SALDO', 'CxC liquidada con saldo positivo', $this->countCxcCuenta($hotelId, $filtros, "estado = 'liquidada' AND saldo > 0"), 'estado liquidada con saldo pendiente.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'cxc', 'error', 'CXC_MOV_SIN_CUENTA', 'Movimiento CxC sin cuenta del mismo hotel', $this->countCxcMovimiento($hotelId, $filtros, 'c.id IS NULL', 'LEFT JOIN cuentas_por_cobrar c ON c.id = m.cuenta_por_cobrar_id AND c.hotel_id = m.hotel_id'), 'movimiento sin cuenta CxC asociada por hotel.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'cxc', 'error', 'CXC_COBRO_SIN_CAJA', 'COBRO CxC sin ingreso Caja', $this->countCxcCobroSinCaja($hotelId, $filtros), 'COBRO sin movimiento Caja categoria Cobro CxC.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'cxc', 'error', 'CAJA_CXC_SIN_COBRO', 'Ingreso Caja CxC sin COBRO asociado', $this->countCajaCxcSinCobro($hotelId, $filtros), 'ingreso Caja Cobro CxC sin movimiento CxC asociado.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'cxc', 'error', 'CXC_REV_SIN_CAJA', 'Reversion CxC sin gasto Caja', $this->countCxcReversionSinCaja($hotelId, $filtros), 'CANCELACION REV-CXC sin gasto Caja asociado.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'cxc', 'error', 'CAJA_REV_CXC_SIN_MOV', 'Gasto Caja de reversion sin CANCELACION CxC', $this->countCajaCxcReversionSinMovimiento($hotelId, $filtros), 'gasto Caja Reversion Cobro CxC sin movimiento CxC.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'cxc', 'error', 'CXC_DOBLE_REVERSION', 'Cobros CxC con doble reversion', $this->countCxcDobleReversion($hotelId, $filtros), 'mas de una CANCELACION con la misma referencia.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'cxc', 'warning', 'CXC_REV_FORMATO', 'Referencias REV-CXC con formato inesperado', $this->countCxcMovimiento($hotelId, $filtros, "m.tipo_movimiento = 'CANCELACION' AND m.referencia LIKE 'REV-CXC-%' AND m.referencia NOT REGEXP '^REV-CXC-[0-9]+-MOV-[0-9]+$'"), 'referencia de reversion fuera del formato canonico.', 'cuentas-por-cobrar/operativas');

        $this->agregar($alertas, 'cxp', 'error', 'CXP_SALDO_RANGO', 'CxP con saldo fuera de rango', $this->countCxpCuenta($hotelId, $filtros, 'saldo < 0 OR total < 0 OR saldo > total'), 'saldo menor a cero, total menor a cero o saldo mayor al total.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'cxp', 'error', 'CXP_PAGADA_SALDO', 'CxP pagada con saldo positivo', $this->countCxpCuenta($hotelId, $filtros, "estado = 'pagada' AND saldo > 0"), 'estado pagada con saldo pendiente.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'cxp', 'error', 'CXP_MOV_SIN_CUENTA', 'Movimiento CxP sin cuenta del mismo hotel', $this->countCxpMovimiento($hotelId, $filtros, 'c.id IS NULL', 'LEFT JOIN cuentas_por_pagar c ON c.id = m.cuenta_por_pagar_id AND c.hotel_id = m.hotel_id'), 'movimiento sin cuenta CxP asociada por hotel.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'cxp', 'error', 'CXP_PAGO_SIN_CAJA', 'Pago proveedor sin gasto Caja', $this->countCxpPagoSinCaja($hotelId, $filtros), 'PAGO_REFERENCIAL sin movimiento Caja Pago proveedor.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'cxp', 'error', 'CAJA_CXP_SIN_PAGO', 'Gasto Caja proveedor sin PAGO_REFERENCIAL', $this->countCajaCxpSinPago($hotelId, $filtros), 'gasto Caja Pago proveedor sin movimiento CxP asociado.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'cxp', 'error', 'CXP_REV_SIN_CAJA', 'Reversion CxP sin ingreso Caja', $this->countCxpReversionSinCaja($hotelId, $filtros), 'CANCELACION REV-CXP sin ingreso Caja asociado.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'cxp', 'error', 'CAJA_REV_CXP_SIN_MOV', 'Ingreso Caja de reversion sin CANCELACION CxP', $this->countCajaCxpReversionSinMovimiento($hotelId, $filtros), 'ingreso Caja Reversion Pago proveedor sin movimiento CxP.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'cxp', 'error', 'CXP_DOBLE_REVERSION', 'Pagos proveedor con doble reversion', $this->countCxpDobleReversion($hotelId, $filtros), 'mas de una CANCELACION con la misma referencia.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'cxp', 'warning', 'CXP_REV_FORMATO', 'Referencias REV-CXP con formato inesperado', $this->countCxpMovimiento($hotelId, $filtros, "m.tipo_movimiento = 'CANCELACION' AND m.referencia LIKE 'REV-CXP-%' AND m.referencia NOT REGEXP '^REV-CXP-[0-9]+-MOV-[0-9]+$'"), 'referencia de reversion fuera del formato canonico.', 'cuentas-por-pagar');

        $this->agregar($alertas, 'caja', 'error', 'CAJA_TIPO_CATEGORIA', 'Movimientos Caja con tipo/categoria incompatible', $this->countCaja($hotelId, $filtros, "(categoria = 'Cobro CxC' AND tipo <> 'ingreso') OR (categoria = 'Reversion Cobro CxC' AND tipo <> 'gasto') OR (categoria = 'Pago proveedor' AND tipo <> 'gasto') OR (categoria = 'Reversion Pago proveedor' AND tipo <> 'ingreso')"), 'categoria financiera con tipo de movimiento incompatible.', 'caja');
        $this->agregar($alertas, 'caja', 'error', 'CAJA_SIN_CORTE', 'Movimientos Caja financieros sin corte valido', $this->countCajaSinCorteValido($hotelId, $filtros), 'movimiento financiero sin corte del mismo hotel.', 'caja');
        $this->agregar($alertas, 'caja', 'error', 'CAJA_HOTEL_CRUZADO', 'Cortes financieros con caja de otro hotel', $this->countCajaHotelCruzado($hotelId, $filtros), 'corte/caja con hotel cruzado.', 'caja');
        $this->agregar($alertas, 'caja', 'warning', 'CAJA_REFERENCIAS_DUP', 'Referencias Caja financieras duplicadas', $this->countCajaReferenciasDuplicadas($hotelId, $filtros), 'misma referencia repetida en Caja para categoria financiera.', 'caja');

        $this->agregar($alertas, 'auditoria', 'warning', 'AUD_COBRO_CXC', 'Cobros CxC sin cobertura minima de auditoria', $this->faltantesAuditoria($hotelId, 'cuentas_por_cobrar.cobro_caja_registrado', 'Cobro CxC', 'ingreso', $filtros), 'logs esperados menores a movimientos Caja.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'auditoria', 'warning', 'AUD_REV_CXC', 'Reversiones CxC sin cobertura minima de auditoria', $this->faltantesAuditoria($hotelId, 'cuentas_por_cobrar.cobro_caja_revertido', 'Reversion Cobro CxC', 'gasto', $filtros), 'logs esperados menores a movimientos Caja.', 'cuentas-por-cobrar/operativas');
        $this->agregar($alertas, 'auditoria', 'warning', 'AUD_PAGO_CXP', 'Pagos proveedor sin cobertura minima de auditoria', $this->faltantesAuditoria($hotelId, 'cuentas_por_pagar.pago_caja_registrado', 'Pago proveedor', 'gasto', $filtros), 'logs esperados menores a movimientos Caja.', 'cuentas-por-pagar');
        $this->agregar($alertas, 'auditoria', 'warning', 'AUD_REV_CXP', 'Reversiones proveedor sin cobertura minima de auditoria', $this->faltantesAuditoria($hotelId, 'cuentas_por_pagar.pago_caja_revertido', 'Reversion Pago proveedor', 'ingreso', $filtros), 'logs esperados menores a movimientos Caja.', 'cuentas-por-pagar');

        return $alertas;
    }

    private function agregar(array &$alertas, string $tipo, string $severidadSiPositiva, string $codigo, string $titulo, int $conteo, string $detalle, string $destino): void
    {
        $estado = $conteo > 0 ? strtoupper($severidadSiPositiva) : 'OK';
        $alertas[] = [
            'tipo' => $tipo,
            'severidad' => $conteo > 0 ? $severidadSiPositiva : 'ok',
            'estado' => $estado,
            'codigo' => $codigo,
            'titulo' => $titulo,
            'conteo' => $conteo,
            'detalle' => $detalle,
            'referencia' => $codigo,
            'destino' => $destino,
        ];
    }

    private function aplicarFiltrosYPaginacion(array $reporte, array $filtros): array
    {
        $alertas = array_values(array_filter($reporte['alertas'], function (array $alerta) use ($filtros): bool {
            if ($filtros['tipo'] !== 'todos' && $alerta['tipo'] !== $filtros['tipo']) {
                return false;
            }
            if ($filtros['severidad'] !== 'todos' && $alerta['severidad'] !== $filtros['severidad']) {
                return false;
            }
            return true;
        }));

        foreach ($alertas as $alerta) {
            $estado = (string)$alerta['severidad'];
            if (isset($reporte['totales_alertas'][$estado])) {
                $reporte['totales_alertas'][$estado]++;
            }
            if ((int)$alerta['conteo'] > 0) {
                $reporte['totales_alertas']['hallazgos'] += (int)$alerta['conteo'];
            }
        }

        usort($alertas, function (array $a, array $b): int {
            $peso = ['error' => 1, 'warning' => 2, 'ok' => 3];
            return ($peso[$a['severidad']] ?? 9) <=> ($peso[$b['severidad']] ?? 9)
                ?: strcmp((string)$a['codigo'], (string)$b['codigo']);
        });

        $total = count($alertas);
        $pages = max(1, (int)ceil($total / max(1, $filtros['limit'])));
        $page = min($filtros['page'], $pages);
        $offset = ($page - 1) * $filtros['limit'];

        $reporte['alertas'] = $alertas;
        $reporte['alertas_paginadas'] = array_slice($alertas, $offset, $filtros['limit']);
        $reporte['paginacion'] = [
            'page' => $page,
            'limit' => $filtros['limit'],
            'total' => $total,
            'pages' => $pages,
        ];

        return $reporte;
    }

    private function countCxcCuenta(int $hotelId, array $filtros, string $condicion): int
    {
        if ($this->soloMovimientosFiltrados($filtros)) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('c', $params, $filtros);
        return $this->countSql("SELECT COUNT(*) FROM cuentas_por_cobrar c WHERE c.hotel_id = ? AND ({$condicion}){$extra}", $params);
    }

    private function countCxpCuenta(int $hotelId, array $filtros, string $condicion): int
    {
        if ($this->soloMovimientosFiltrados($filtros)) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('c', $params, $filtros);
        return $this->countSql("SELECT COUNT(*) FROM cuentas_por_pagar c WHERE c.hotel_id = ? AND ({$condicion}){$extra}", $params);
    }

    private function countCxcMovimiento(int $hotelId, array $filtros, string $condicion, string $join = ''): int
    {
        if ($filtros['corte_id'] > 0) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('m', $params, $filtros) . $this->referenciaSql('m', $params, $filtros);
        return $this->countSql("SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos m {$join} WHERE m.hotel_id = ? AND ({$condicion}){$extra}", $params);
    }

    private function countCxpMovimiento(int $hotelId, array $filtros, string $condicion, string $join = ''): int
    {
        if ($filtros['corte_id'] > 0) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('m', $params, $filtros) . $this->referenciaSql('m', $params, $filtros);
        return $this->countSql("SELECT COUNT(*) FROM cuentas_por_pagar_movimientos m {$join} WHERE m.hotel_id = ? AND ({$condicion}){$extra}", $params);
    }

    private function countCaja(int $hotelId, array $filtros, string $condicion): int
    {
        $params = [$hotelId];
        $extra = $this->fechaSql('mc', $params, $filtros) . $this->referenciaSql('mc', $params, $filtros) . $this->corteSql('mc', $params, $filtros);
        return $this->countSql("SELECT COUNT(*) FROM movimientos_caja mc WHERE mc.hotel_id = ? AND ({$condicion}){$extra}", $params);
    }

    private function countCxcCobroSinCaja(int $hotelId, array $filtros): int
    {
        if ($filtros['corte_id'] > 0) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('m', $params, $filtros) . $this->referenciaSql('m', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM cuentas_por_cobrar_movimientos m
             LEFT JOIN movimientos_caja mc
               ON mc.hotel_id = m.hotel_id
              AND mc.tipo = 'ingreso'
              AND mc.categoria = 'Cobro CxC'
              AND mc.monto = m.monto
              AND (mc.referencia = CONCAT('CXC-', m.cuenta_por_cobrar_id, '-MOV-', m.id)
                   OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia))
             WHERE m.hotel_id = ?
               AND m.tipo_movimiento = 'COBRO'
               AND mc.id IS NULL{$extra}",
            $params
        );
    }

    private function countCajaCxcSinCobro(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $extra = $this->fechaSql('mc', $params, $filtros) . $this->referenciaSql('mc', $params, $filtros) . $this->corteSql('mc', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             LEFT JOIN cuentas_por_cobrar_movimientos m
               ON m.hotel_id = mc.hotel_id
              AND m.tipo_movimiento = 'COBRO'
              AND m.monto = mc.monto
              AND (mc.referencia = CONCAT('CXC-', m.cuenta_por_cobrar_id, '-MOV-', m.id)
                   OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia))
             WHERE mc.hotel_id = ?
               AND mc.tipo = 'ingreso'
               AND mc.categoria = 'Cobro CxC'
               AND m.id IS NULL{$extra}",
            $params
        );
    }

    private function countCxcReversionSinCaja(int $hotelId, array $filtros): int
    {
        if ($filtros['corte_id'] > 0) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('m', $params, $filtros) . $this->referenciaSql('m', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM cuentas_por_cobrar_movimientos m
             LEFT JOIN movimientos_caja mc
               ON mc.hotel_id = m.hotel_id
              AND mc.tipo = 'gasto'
              AND mc.categoria = 'Reversion Cobro CxC'
              AND mc.monto = m.monto
              AND mc.referencia = m.referencia
             WHERE m.hotel_id = ?
               AND m.tipo_movimiento = 'CANCELACION'
               AND m.referencia LIKE 'REV-CXC-%'
               AND mc.id IS NULL{$extra}",
            $params
        );
    }

    private function countCajaCxcReversionSinMovimiento(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $extra = $this->fechaSql('mc', $params, $filtros) . $this->referenciaSql('mc', $params, $filtros) . $this->corteSql('mc', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             LEFT JOIN cuentas_por_cobrar_movimientos m
               ON m.hotel_id = mc.hotel_id
              AND m.tipo_movimiento = 'CANCELACION'
              AND m.monto = mc.monto
              AND m.referencia = mc.referencia
             WHERE mc.hotel_id = ?
               AND mc.tipo = 'gasto'
               AND mc.categoria = 'Reversion Cobro CxC'
               AND m.id IS NULL{$extra}",
            $params
        );
    }

    private function countCxpPagoSinCaja(int $hotelId, array $filtros): int
    {
        if ($filtros['corte_id'] > 0) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('m', $params, $filtros) . $this->referenciaSql('m', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM cuentas_por_pagar_movimientos m
             LEFT JOIN movimientos_caja mc
               ON mc.hotel_id = m.hotel_id
              AND mc.tipo = 'gasto'
              AND mc.categoria = 'Pago proveedor'
              AND mc.monto = m.monto
              AND (mc.referencia = CONCAT('CXP-', m.cuenta_por_pagar_id, '-MOV-', m.id)
                   OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia))
             WHERE m.hotel_id = ?
               AND m.tipo_movimiento = 'PAGO_REFERENCIAL'
               AND mc.id IS NULL{$extra}",
            $params
        );
    }

    private function countCajaCxpSinPago(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $extra = $this->fechaSql('mc', $params, $filtros) . $this->referenciaSql('mc', $params, $filtros) . $this->corteSql('mc', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             LEFT JOIN cuentas_por_pagar_movimientos m
               ON m.hotel_id = mc.hotel_id
              AND m.tipo_movimiento = 'PAGO_REFERENCIAL'
              AND m.monto = mc.monto
              AND (mc.referencia = CONCAT('CXP-', m.cuenta_por_pagar_id, '-MOV-', m.id)
                   OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia))
             WHERE mc.hotel_id = ?
               AND mc.tipo = 'gasto'
               AND mc.categoria = 'Pago proveedor'
               AND m.id IS NULL{$extra}",
            $params
        );
    }

    private function countCxpReversionSinCaja(int $hotelId, array $filtros): int
    {
        if ($filtros['corte_id'] > 0) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('m', $params, $filtros) . $this->referenciaSql('m', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM cuentas_por_pagar_movimientos m
             LEFT JOIN movimientos_caja mc
               ON mc.hotel_id = m.hotel_id
              AND mc.tipo = 'ingreso'
              AND mc.categoria = 'Reversion Pago proveedor'
              AND mc.monto = m.monto
              AND mc.referencia = m.referencia
             WHERE m.hotel_id = ?
               AND m.tipo_movimiento = 'CANCELACION'
               AND m.referencia LIKE 'REV-CXP-%'
               AND mc.id IS NULL{$extra}",
            $params
        );
    }

    private function countCajaCxpReversionSinMovimiento(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $extra = $this->fechaSql('mc', $params, $filtros) . $this->referenciaSql('mc', $params, $filtros) . $this->corteSql('mc', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             LEFT JOIN cuentas_por_pagar_movimientos m
               ON m.hotel_id = mc.hotel_id
              AND m.tipo_movimiento = 'CANCELACION'
              AND m.monto = mc.monto
              AND m.referencia = mc.referencia
             WHERE mc.hotel_id = ?
               AND mc.tipo = 'ingreso'
               AND mc.categoria = 'Reversion Pago proveedor'
               AND m.id IS NULL{$extra}",
            $params
        );
    }

    private function countCxcDobleReversion(int $hotelId, array $filtros): int
    {
        if ($filtros['corte_id'] > 0) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('m', $params, $filtros) . $this->referenciaSql('m', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*) FROM (
                SELECT m.hotel_id, m.cuenta_por_cobrar_id, m.referencia, COUNT(*) AS total
                FROM cuentas_por_cobrar_movimientos m
                WHERE m.hotel_id = ?
                  AND m.tipo_movimiento = 'CANCELACION'
                  AND m.referencia LIKE 'REV-CXC-%'{$extra}
                GROUP BY m.hotel_id, m.cuenta_por_cobrar_id, m.referencia
                HAVING COUNT(*) > 1
             ) x",
            $params
        );
    }

    private function countCxpDobleReversion(int $hotelId, array $filtros): int
    {
        if ($filtros['corte_id'] > 0) {
            return 0;
        }
        $params = [$hotelId];
        $extra = $this->fechaSql('m', $params, $filtros) . $this->referenciaSql('m', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*) FROM (
                SELECT m.hotel_id, m.cuenta_por_pagar_id, m.referencia, COUNT(*) AS total
                FROM cuentas_por_pagar_movimientos m
                WHERE m.hotel_id = ?
                  AND m.tipo_movimiento = 'CANCELACION'
                  AND m.referencia LIKE 'REV-CXP-%'{$extra}
                GROUP BY m.hotel_id, m.cuenta_por_pagar_id, m.referencia
                HAVING COUNT(*) > 1
             ) x",
            $params
        );
    }

    private function countCajaSinCorteValido(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $extra = $this->fechaSql('mc', $params, $filtros) . $this->referenciaSql('mc', $params, $filtros) . $this->corteSql('mc', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             LEFT JOIN cortes_caja cc
               ON cc.id = mc.corte_id
              AND cc.hotel_id = mc.hotel_id
             WHERE mc.hotel_id = ?
               AND mc.categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor')
               AND (mc.corte_id IS NULL OR cc.id IS NULL){$extra}",
            $params
        );
    }

    private function countCajaHotelCruzado(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $extra = $this->fechaSql('mc', $params, $filtros) . $this->referenciaSql('mc', $params, $filtros) . $this->corteSql('mc', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             INNER JOIN cortes_caja cc
                ON cc.id = mc.corte_id
             INNER JOIN cajas c
                ON c.id = cc.caja_id
             WHERE mc.hotel_id = ?
               AND mc.categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor')
               AND c.hotel_id IS NOT NULL
               AND cc.hotel_id IS NOT NULL
               AND c.hotel_id <> cc.hotel_id{$extra}",
            $params
        );
    }

    private function countCajaReferenciasDuplicadas(int $hotelId, array $filtros): int
    {
        $params = [$hotelId];
        $extra = $this->fechaSql('mc', $params, $filtros) . $this->referenciaSql('mc', $params, $filtros) . $this->corteSql('mc', $params, $filtros);
        return $this->countSql(
            "SELECT COUNT(*) FROM (
                SELECT mc.hotel_id, mc.tipo, mc.categoria, mc.referencia, COUNT(*) AS total
                FROM movimientos_caja mc
                WHERE mc.hotel_id = ?
                  AND mc.categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor')
                  AND mc.referencia IS NOT NULL
                  AND mc.referencia <> ''{$extra}
                GROUP BY mc.hotel_id, mc.tipo, mc.categoria, mc.referencia
                HAVING COUNT(*) > 1
             ) x",
            $params
        );
    }

    private function faltantesAuditoria(int $hotelId, string $accion, string $categoriaCaja, string $tipoCaja, array $filtros): int
    {
        $paramsCaja = [$hotelId];
        $extraCaja = $this->fechaSql('mc', $paramsCaja, $filtros) . $this->referenciaSql('mc', $paramsCaja, $filtros) . $this->corteSql('mc', $paramsCaja, $filtros);
        $movimientos = $this->countSql(
            "SELECT COUNT(*)
             FROM movimientos_caja mc
             WHERE mc.hotel_id = ?
               AND mc.tipo = ?
               AND mc.categoria = ?{$extraCaja}",
            array_merge([$hotelId, $tipoCaja, $categoriaCaja], array_slice($paramsCaja, 1))
        );

        if ($movimientos === 0) {
            return 0;
        }

        if ($filtros['corte_id'] > 0 || $filtros['referencia'] !== '') {
            return 0;
        }

        $paramsLog = [$hotelId, $accion];
        $extraLog = $this->fechaSql('la', $paramsLog, $filtros);
        $logs = $this->countSql(
            "SELECT COUNT(*)
             FROM logs_auditoria la
             WHERE la.hotel_id = ?
               AND la.accion = ?{$extraLog}",
            $paramsLog
        );

        return max(0, $movimientos - $logs);
    }

    private function fechaSql(string $alias, array &$params, array $filtros): string
    {
        $sql = '';
        if ($filtros['fecha_desde'] !== '') {
            $sql .= " AND {$alias}.created_at >= ?";
            $params[] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        if ($filtros['fecha_hasta'] !== '') {
            $sql .= " AND {$alias}.created_at <= ?";
            $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
        }
        return $sql;
    }

    private function referenciaSql(string $alias, array &$params, array $filtros): string
    {
        if ($filtros['referencia'] === '') {
            return '';
        }
        $params[] = '%' . $filtros['referencia'] . '%';
        return " AND COALESCE({$alias}.referencia, '') LIKE ?";
    }

    private function corteSql(string $alias, array &$params, array $filtros): string
    {
        if ($filtros['corte_id'] <= 0) {
            return '';
        }
        $params[] = $filtros['corte_id'];
        return " AND {$alias}.corte_id = ?";
    }

    private function soloMovimientosFiltrados(array $filtros): bool
    {
        return $filtros['referencia'] !== '' || $filtros['corte_id'] > 0;
    }

    private function countSql(string $sql, array $params = []): int
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    private function fetchOne(string $sql, array $params = []): array
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetch() ?: []) : [];
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?",
            [$tabla]
        );
        $row = $stmt ? $stmt->fetch() : null;

        return (int)($row['total'] ?? 0) > 0;
    }

    private function fechaFiltro($value): string
    {
        $value = trim((string)$value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private function decimal($value): string
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}
