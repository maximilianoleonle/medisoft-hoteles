<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/ConfiguracionHotelRegistry.php';

/**
 * GuardianPatrones (bloque premium: vigilancia_financiera / "el Guardian")
 *
 * Motor determinista READ-ONLY de patrones de COMPORTAMIENTO por usuario.
 * Complementa a ConciliacionFinanciera (integridad de libros): aquel valida
 * que los numeros cuadren; este observa COMO se comportan los usuarios que
 * los generan (descuentos atipicos, cancelaciones sospechosas, movimientos
 * fuera de horario, cortes que descuadran de forma recurrente, reversiones
 * concentradas).
 *
 * Filosofia innegociable:
 *  - 100% read-only sobre dinero: solo SELECT dentro de una transaccion
 *    READ ONLY. Jamas escribe en caja, cortes, movimientos ni reservaciones.
 *  - Lenguaje NO acusatorio: los textos hablan de "patron a revisar" y
 *    "conviene confirmar con el equipo". La estadistica señala donde mirar;
 *    nunca dictamina mala fe.
 *  - Umbrales RELATIVOS y configurables por hotel (ConfiguracionHotelRegistry,
 *    claves guardian.*) + minimo de volumen: con 3 movimientos no hay
 *    estadistica y un hotel chico no debe recibir falsas alarmas.
 *
 * Atribucion de cancelaciones: reservaciones no guarda quien cancelo; se
 * atribuye al usuario del gasto de devolucion en movimientos_caja (quien
 * proceso el dinero al cancelar) y, si no hubo devolucion, al usuario que
 * registro la reservacion (se marca 'atribucion' en el caso).
 */
class GuardianPatrones extends Model
{
    /** Codigos de regla (estables: la UI, el estado y el informe v2 los usan). */
    public const REGLAS = [
        'GD_DESCUENTOS_ATIPICOS',
        'GD_CANCELACIONES_SOSPECHOSAS',
        'GD_FUERA_HORARIO',
        'GD_CORTE_AJENO',
        'GD_CORTES_DIFERENCIA',
        'GD_REVERSIONES_CONCENTRADAS',
    ];

    private const MAX_CASOS_POR_USUARIO = 10;

    /** Umbrales efectivos del hotel (defaults sensatos si no configuro nada). */
    public function configuracion(int $hotelId): array
    {
        return [
            'horario_inicio' => (string) ConfiguracionHotelRegistry::get('guardian.horario_inicio', null, $hotelId),
            'horario_fin' => (string) ConfiguracionHotelRegistry::get('guardian.horario_fin', null, $hotelId),
            'horario_configurado' => $this->horarioConfiguradoExplicito($hotelId),
            'ventana_dias' => max(7, ConfiguracionHotelRegistry::getInt('guardian.ventana_dias', 30, $hotelId)),
            'min_volumen' => max(1, ConfiguracionHotelRegistry::getInt('guardian.min_volumen', 10, $hotelId)),
            'descuento_factor' => max(1.1, (float) ConfiguracionHotelRegistry::get('guardian.descuento_factor', null, $hotelId)),
            'descuento_piso_pct' => max(0.5, (float) ConfiguracionHotelRegistry::get('guardian.descuento_piso_pct', null, $hotelId)),
            'cancelaciones_min' => max(1, ConfiguracionHotelRegistry::getInt('guardian.cancelaciones_min', 2, $hotelId)),
            'fuera_horario_min' => max(1, ConfiguracionHotelRegistry::getInt('guardian.fuera_horario_min', 3, $hotelId)),
            'diferencia_monto' => max(0.01, (float) ConfiguracionHotelRegistry::get('guardian.diferencia_monto', null, $hotelId)),
            'diferencia_repeticiones' => max(2, ConfiguracionHotelRegistry::getInt('guardian.diferencia_repeticiones', 3, $hotelId)),
            'reversiones_pct' => min(95, max(20, ConfiguracionHotelRegistry::getInt('guardian.reversiones_pct', 60, $hotelId))),
        ];
    }

    /**
     * Reporte completo de patrones del hotel en la ventana.
     * $filtros: fecha_desde/fecha_hasta (YYYY-MM-DD; default hoy - ventana_dias).
     *
     * Devuelve:
     *  ['consultado_en','ventana','config','volumen','alertas','por_persona','totales']
     * Cada alerta: ['tipo'=>'patron','codigo','titulo','severidad'
     *  ('alta'|'media'|'ok'),'conteo','detalle','usuarios'=>[...],'destino'].
     * Cada usuario de alerta trae 'casos' (max 10) con montos/fechas/referencias
     * para la UI y para el prompt forense v2.
     */
    public function reporteReadOnlyPorHotel(int $hotelId, array $filtros = []): array
    {
        $config = $this->configuracion($hotelId);
        $hasta = $this->fechaFiltro($filtros['fecha_hasta'] ?? '') ?: date('Y-m-d');
        $desde = $this->fechaFiltro($filtros['fecha_desde'] ?? '')
            ?: date('Y-m-d', strtotime($hasta . ' -' . $config['ventana_dias'] . ' days'));
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $ventana = ['desde' => $desde, 'hasta' => $hasta, 'dias' => $config['ventana_dias']];

        $base = [
            'consultado_en' => date('Y-m-d H:i:s'),
            'ventana' => $ventana,
            'config' => $config,
            'volumen' => ['movimientos' => 0, 'reservaciones' => 0, 'suficiente' => false, 'minimo' => $config['min_volumen']],
            'alertas' => [],
            'por_persona' => [],
            'totales' => ['alta' => 0, 'media' => 0, 'hallazgos' => 0],
        ];

        if ($hotelId <= 0) {
            return $base;
        }

        $pdo = $this->db->getConnection();
        try {
            $pdo->exec('START TRANSACTION READ ONLY');

            $base['volumen'] = $this->volumen($hotelId, $ventana, $config['min_volumen']);
            $suficiente = $base['volumen']['suficiente'];

            $alertas = [];
            $alertas[] = $this->reglaDescuentosAtipicos($hotelId, $ventana, $config, $suficiente);
            $alertas[] = $this->reglaCancelacionesSospechosas($hotelId, $ventana, $config);
            $alertas[] = $this->reglaFueraDeHorario($hotelId, $ventana, $config);
            $alertas[] = $this->reglaCorteAjeno($hotelId, $ventana, $config);
            $alertas[] = $this->reglaCortesDiferenciaRecurrente($hotelId, $ventana, $config);
            $alertas[] = $this->reglaReversionesConcentradas($hotelId, $ventana, $config, $suficiente);

            foreach ($alertas as $a) {
                if ($a['severidad'] === 'alta') {
                    $base['totales']['alta']++;
                } elseif ($a['severidad'] === 'media') {
                    $base['totales']['media']++;
                }
                $base['totales']['hallazgos'] += (int) $a['conteo'];
            }

            $base['alertas'] = $alertas;
            $base['por_persona'] = $this->porPersona($hotelId, $ventana, $config);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }

        return $base;
    }

    // ───────────── Regla 1: descuentos atipicos por usuario ─────────────

    private function reglaDescuentosAtipicos(int $hotelId, array $ventana, array $config, bool $volumenSuficiente): array
    {
        $alerta = $this->alertaBase(
            'GD_DESCUENTOS_ATIPICOS',
            'Descuentos por encima del patron del hotel',
            'porcentaje de descuento otorgado por un usuario muy por encima de la mediana del equipo en la ventana.',
            'reservaciones'
        );

        if (!$volumenSuficiente) {
            $alerta['nota'] = 'volumen_insuficiente';
            return $alerta;
        }

        $rows = $this->fetchAll(
            "SELECT r.usuario_registro_id AS usuario_id,
                    u.nombre_completo AS nombre,
                    COUNT(*) AS reservas,
                    COALESCE(SUM(r.descuento_total), 0) AS descuento_sum,
                    COALESCE(SUM(r.precio_total + r.descuento_total), 0) AS base_sum
             FROM reservaciones r
             LEFT JOIN usuarios u ON u.id = r.usuario_registro_id
             WHERE r.hotel_id = ?
               AND r.usuario_registro_id IS NOT NULL
               AND r.estado <> 'cancelada'
               AND r.created_at >= ? AND r.created_at <= ?
             GROUP BY r.usuario_registro_id, u.nombre_completo",
            [$hotelId, $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59']
        );

        // Porcentaje por usuario; la mediana se calcula solo entre usuarios con
        // volumen propio suficiente (mitad del minimo del hotel, al menos 3).
        $minReservasUsuario = max(3, (int) ceil($config['min_volumen'] / 2));
        $pcts = [];
        $porUsuario = [];
        foreach ($rows as $r) {
            $base = (float) $r['base_sum'];
            if ($base <= 0) {
                continue;
            }
            $pct = 100.0 * (float) $r['descuento_sum'] / $base;
            $porUsuario[] = [
                'usuario_id' => (int) $r['usuario_id'],
                'nombre' => (string) ($r['nombre'] ?? 'Usuario #' . $r['usuario_id']),
                'reservas' => (int) $r['reservas'],
                'descuento_sum' => $this->decimal($r['descuento_sum']),
                'pct' => round($pct, 2),
            ];
            if ((int) $r['reservas'] >= $minReservasUsuario) {
                $pcts[] = $pct;
            }
        }

        if (count($pcts) < 2) {
            // Con un solo usuario activo no hay "patron del hotel" contra que comparar.
            $alerta['nota'] = 'sin_comparativa';
            return $alerta;
        }

        $mediana = $this->mediana($pcts);
        $umbral = max($mediana * $config['descuento_factor'], $config['descuento_piso_pct']);

        foreach ($porUsuario as $u) {
            if ($u['reservas'] < $minReservasUsuario || $u['pct'] < $umbral) {
                continue;
            }
            $casos = $this->fetchAll(
                "SELECT r.id, r.created_at, r.descuento_total, r.precio_total,
                        r.descuento_detalle_json
                 FROM reservaciones r
                 WHERE r.hotel_id = ?
                   AND r.usuario_registro_id = ?
                   AND r.estado <> 'cancelada'
                   AND r.descuento_total > 0
                   AND r.created_at >= ? AND r.created_at <= ?
                 ORDER BY r.descuento_total DESC
                 LIMIT " . self::MAX_CASOS_POR_USUARIO,
                [$hotelId, $u['usuario_id'], $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59']
            );

            $alerta['usuarios'][] = [
                'usuario_id' => $u['usuario_id'],
                'nombre' => $u['nombre'],
                'valor' => $u['pct'] . '% de descuento sobre ' . $u['reservas'] . ' reservas (' . '$' . number_format((float) $u['descuento_sum'], 2) . ' otorgados)',
                'contexto' => 'Mediana del hotel: ' . round($mediana, 2) . '% · umbral: ' . round($umbral, 2) . '%',
                'metrica' => ['pct' => $u['pct'], 'mediana' => round($mediana, 2), 'umbral' => round($umbral, 2)],
                'casos' => array_map(static function (array $c): array {
                    return [
                        'reservacion_id' => (int) $c['id'],
                        'fecha' => (string) $c['created_at'],
                        'descuento' => number_format((float) $c['descuento_total'], 2, '.', ''),
                        'precio_final' => number_format((float) $c['precio_total'], 2, '.', ''),
                    ];
                }, $casos),
            ];
        }

        return $this->cerrarAlerta($alerta, static function (array $u): string {
            return $u['metrica']['pct'] >= $u['metrica']['umbral'] * 2 ? 'alta' : 'media';
        });
    }

    // ───────── Regla 2: cancelaciones el dia del check-in o despues ─────────

    private function reglaCancelacionesSospechosas(int $hotelId, array $ventana, array $config): array
    {
        $alerta = $this->alertaBase(
            'GD_CANCELACIONES_SOSPECHOSAS',
            'Cancelaciones el dia de la llegada o tras el check-in',
            'reservaciones canceladas ya con la llegada encima; si ademas hubo cobro en efectivo previo, conviene confirmar la devolucion con el equipo.',
            'reservaciones'
        );

        // updated_at es el mejor proxy del momento de cancelacion (estado
        // terminal). La atribucion sale del gasto de devolucion en Caja.
        $rows = $this->fetchAll(
            "SELECT r.id, r.fecha_entrada, r.hora_entrada, r.updated_at,
                    r.precio_total, r.descuento_total,
                    COALESCE(dev.usuario_id, r.usuario_registro_id) AS usuario_id,
                    CASE WHEN dev.usuario_id IS NOT NULL THEN 'devolucion_caja' ELSE 'registro' END AS atribucion,
                    COALESCE(efe.monto_efectivo, 0) AS monto_efectivo,
                    u.nombre_completo AS nombre
             FROM reservaciones r
             LEFT JOIN (
                 SELECT mc.reservacion_id, mc.hotel_id,
                        SUBSTRING_INDEX(GROUP_CONCAT(mc.usuario_id ORDER BY mc.id DESC), ',', 1) AS usuario_id
                 FROM movimientos_caja mc
                 WHERE mc.hotel_id = ? AND mc.tipo = 'gasto' AND mc.reservacion_id IS NOT NULL
                 GROUP BY mc.reservacion_id, mc.hotel_id
             ) dev ON dev.reservacion_id = r.id AND dev.hotel_id = r.hotel_id
             LEFT JOIN (
                 SELECT mc.reservacion_id, mc.hotel_id, SUM(mc.monto) AS monto_efectivo
                 FROM movimientos_caja mc
                 WHERE mc.hotel_id = ? AND mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo'
                   AND mc.reservacion_id IS NOT NULL
                 GROUP BY mc.reservacion_id, mc.hotel_id
             ) efe ON efe.reservacion_id = r.id AND efe.hotel_id = r.hotel_id
             LEFT JOIN usuarios u ON u.id = COALESCE(dev.usuario_id, r.usuario_registro_id)
             WHERE r.hotel_id = ?
               AND r.estado = 'cancelada'
               AND r.updated_at >= ? AND r.updated_at <= ?
               AND (r.hora_entrada IS NOT NULL OR DATE(r.updated_at) >= r.fecha_entrada)",
            [$hotelId, $hotelId, $hotelId, $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59']
        );

        $porUsuario = [];
        foreach ($rows as $r) {
            $uid = (int) ($r['usuario_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $porUsuario[$uid]['nombre'] = (string) ($r['nombre'] ?? 'Usuario #' . $uid);
            $porUsuario[$uid]['casos'][] = [
                'reservacion_id' => (int) $r['id'],
                'fecha_cancelacion' => (string) $r['updated_at'],
                'llegada' => (string) $r['fecha_entrada'],
                'tenia_checkin' => $r['hora_entrada'] !== null,
                'efectivo_cobrado' => number_format((float) $r['monto_efectivo'], 2, '.', ''),
                'atribucion' => (string) $r['atribucion'],
            ];
            $porUsuario[$uid]['con_efectivo'] = ($porUsuario[$uid]['con_efectivo'] ?? 0)
                + ((float) $r['monto_efectivo'] > 0 ? 1 : 0);
        }

        foreach ($porUsuario as $uid => $datos) {
            $total = count($datos['casos']);
            if ($total < $config['cancelaciones_min']) {
                continue;
            }
            $conEfectivo = (int) ($datos['con_efectivo'] ?? 0);
            $alerta['usuarios'][] = [
                'usuario_id' => $uid,
                'nombre' => $datos['nombre'],
                'valor' => $total . ' cancelacion(es) tardia(s), ' . $conEfectivo . ' con cobro en efectivo previo',
                'contexto' => 'Se atribuye a quien proceso la devolucion en Caja (o a quien registro la reserva si no hubo devolucion).',
                'metrica' => ['casos' => $total, 'con_efectivo' => $conEfectivo],
                'casos' => array_slice($datos['casos'], 0, self::MAX_CASOS_POR_USUARIO),
            ];
        }

        return $this->cerrarAlerta($alerta, static function (array $u): string {
            return $u['metrica']['con_efectivo'] >= 2 ? 'alta' : 'media';
        });
    }

    // ───────── Regla 3: movimientos de caja fuera de horario ─────────

    private function reglaFueraDeHorario(int $hotelId, array $ventana, array $config): array
    {
        $alerta = $this->alertaBase(
            'GD_FUERA_HORARIO',
            'Movimientos de caja fuera del horario operativo',
            'movimientos registrados fuera de la ventana operativa del hotel (' . $config['horario_inicio'] . ' a ' . $config['horario_fin'] . ').',
            'caja'
        );

        $inicio = $this->horaSegura($config['horario_inicio'], '06:00');
        $fin = $this->horaSegura($config['horario_fin'], '23:59');

        // Ventana normal (inicio < fin): fuera = antes del inicio o despues del fin.
        // Ventana nocturna (inicio > fin, ej. 20:00-08:00): fuera = entre fin e inicio.
        $condHorario = ($inicio <= $fin)
            ? "(TIME(mc.created_at) < ? OR TIME(mc.created_at) > ?)"
            : "(TIME(mc.created_at) > ? AND TIME(mc.created_at) < ?)";
        $paramsHorario = ($inicio <= $fin) ? [$inicio . ':00', $fin . ':59'] : [$fin . ':59', $inicio . ':00'];

        $rows = $this->fetchAll(
            "SELECT mc.usuario_id, u.nombre_completo AS nombre,
                    COUNT(*) AS casos, COALESCE(SUM(mc.monto), 0) AS monto
             FROM movimientos_caja mc
             LEFT JOIN usuarios u ON u.id = mc.usuario_id
             WHERE mc.hotel_id = ?
               AND mc.created_at >= ? AND mc.created_at <= ?
               AND {$condHorario}
             GROUP BY mc.usuario_id, u.nombre_completo
             HAVING COUNT(*) >= ?",
            array_merge(
                [$hotelId, $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59'],
                $paramsHorario,
                [$config['fuera_horario_min']]
            )
        );

        foreach ($rows as $r) {
            $casos = $this->fetchAll(
                "SELECT mc.id, mc.created_at, mc.tipo, mc.categoria, mc.monto, mc.metodo_pago, mc.referencia, mc.corte_id
                 FROM movimientos_caja mc
                 WHERE mc.hotel_id = ? AND mc.usuario_id = ?
                   AND mc.created_at >= ? AND mc.created_at <= ?
                   AND {$condHorario}
                 ORDER BY mc.created_at DESC
                 LIMIT " . self::MAX_CASOS_POR_USUARIO,
                array_merge(
                    [$hotelId, (int) $r['usuario_id'], $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59'],
                    $paramsHorario
                )
            );

            $alerta['usuarios'][] = [
                'usuario_id' => (int) $r['usuario_id'],
                'nombre' => (string) ($r['nombre'] ?? 'Usuario #' . $r['usuario_id']),
                'valor' => (int) $r['casos'] . ' movimiento(s) fuera de horario por $' . number_format((float) $r['monto'], 2),
                'contexto' => 'Horario operativo del hotel: ' . $inicio . ' a ' . $fin . '.',
                'metrica' => ['casos' => (int) $r['casos'], 'monto' => number_format((float) $r['monto'], 2, '.', '')],
                'casos' => array_map(static function (array $c): array {
                    return [
                        'movimiento_id' => (int) $c['id'],
                        'fecha' => (string) $c['created_at'],
                        'tipo' => (string) $c['tipo'],
                        'categoria' => (string) $c['categoria'],
                        'monto' => number_format((float) $c['monto'], 2, '.', ''),
                        'metodo' => (string) $c['metodo_pago'],
                        'referencia' => (string) ($c['referencia'] ?? ''),
                        'corte_id' => $c['corte_id'] !== null ? (int) $c['corte_id'] : null,
                    ];
                }, $casos),
            ];
        }

        return $this->cerrarAlerta($alerta, static function (array $u) use ($config): string {
            return $u['metrica']['casos'] >= $config['fuera_horario_min'] * 2 ? 'alta' : 'media';
        });
    }

    // ───────── Regla 3b: movimientos en un corte de otro turno ─────────

    private function reglaCorteAjeno(int $hotelId, array $ventana, array $config): array
    {
        $alerta = $this->alertaBase(
            'GD_CORTE_AJENO',
            'Movimientos en cortes abiertos por otra persona',
            'movimientos registrados dentro de un corte que abrio otro usuario; puede ser normal en turnos compartidos, conviene confirmarlo con el equipo.',
            'caja'
        );

        $rows = $this->fetchAll(
            "SELECT mc.usuario_id, u.nombre_completo AS nombre,
                    COUNT(*) AS casos, COALESCE(SUM(mc.monto), 0) AS monto
             FROM movimientos_caja mc
             INNER JOIN cortes_caja cc
                ON cc.id = mc.corte_id AND cc.hotel_id = mc.hotel_id
             LEFT JOIN usuarios u ON u.id = mc.usuario_id
             WHERE mc.hotel_id = ?
               AND mc.created_at >= ? AND mc.created_at <= ?
               AND mc.usuario_id <> cc.usuario_apertura_id
             GROUP BY mc.usuario_id, u.nombre_completo
             HAVING COUNT(*) >= ?",
            [$hotelId, $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59', $config['fuera_horario_min']]
        );

        foreach ($rows as $r) {
            $casos = $this->fetchAll(
                "SELECT mc.id, mc.created_at, mc.tipo, mc.categoria, mc.monto, mc.corte_id,
                        cc.usuario_apertura_id, ua.nombre_completo AS abrio
                 FROM movimientos_caja mc
                 INNER JOIN cortes_caja cc
                    ON cc.id = mc.corte_id AND cc.hotel_id = mc.hotel_id
                 LEFT JOIN usuarios ua ON ua.id = cc.usuario_apertura_id
                 WHERE mc.hotel_id = ? AND mc.usuario_id = ?
                   AND mc.created_at >= ? AND mc.created_at <= ?
                   AND mc.usuario_id <> cc.usuario_apertura_id
                 ORDER BY mc.created_at DESC
                 LIMIT " . self::MAX_CASOS_POR_USUARIO,
                [$hotelId, (int) $r['usuario_id'], $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59']
            );

            $alerta['usuarios'][] = [
                'usuario_id' => (int) $r['usuario_id'],
                'nombre' => (string) ($r['nombre'] ?? 'Usuario #' . $r['usuario_id']),
                'valor' => (int) $r['casos'] . ' movimiento(s) en cortes de otro turno por $' . number_format((float) $r['monto'], 2),
                'contexto' => 'Puede ser operacion normal si comparten turno; vale confirmarlo.',
                'metrica' => ['casos' => (int) $r['casos'], 'monto' => number_format((float) $r['monto'], 2, '.', '')],
                'casos' => array_map(static function (array $c): array {
                    return [
                        'movimiento_id' => (int) $c['id'],
                        'fecha' => (string) $c['created_at'],
                        'tipo' => (string) $c['tipo'],
                        'categoria' => (string) $c['categoria'],
                        'monto' => number_format((float) $c['monto'], 2, '.', ''),
                        'corte_id' => (int) $c['corte_id'],
                        'corte_abierto_por' => (string) ($c['abrio'] ?? 'Usuario #' . $c['usuario_apertura_id']),
                    ];
                }, $casos),
            ];
        }

        return $this->cerrarAlerta($alerta, static function (): string {
            return 'media';
        });
    }

    // ───────── Regla 4: cortes con diferencia recurrente ─────────

    private function reglaCortesDiferenciaRecurrente(int $hotelId, array $ventana, array $config): array
    {
        $alerta = $this->alertaBase(
            'GD_CORTES_DIFERENCIA',
            'Cortes que descuadran de forma recurrente',
            'usuarios cuyo corte cierra con diferencia (mas de $' . number_format($config['diferencia_monto'], 2) . ') varias veces en la ventana; la diferencia puntual ya la muestra Caja, aqui pesa la repeticion.',
            'caja'
        );

        $rows = $this->fetchAll(
            "SELECT cc.usuario_cierre_id AS usuario_id, u.nombre_completo AS nombre,
                    COUNT(*) AS casos,
                    COALESCE(SUM(ABS(cc.diferencia)), 0) AS monto,
                    SUM(CASE WHEN cc.diferencia < 0 THEN 1 ELSE 0 END) AS faltantes
             FROM cortes_caja cc
             LEFT JOIN usuarios u ON u.id = cc.usuario_cierre_id
             WHERE cc.hotel_id = ?
               AND cc.estado = 'cerrado'
               AND cc.usuario_cierre_id IS NOT NULL
               AND cc.fecha_cierre >= ? AND cc.fecha_cierre <= ?
               AND ABS(COALESCE(cc.diferencia, 0)) >= ?
             GROUP BY cc.usuario_cierre_id, u.nombre_completo
             HAVING COUNT(*) >= ?",
            [
                $hotelId,
                $ventana['desde'] . ' 00:00:00',
                $ventana['hasta'] . ' 23:59:59',
                $config['diferencia_monto'],
                $config['diferencia_repeticiones'],
            ]
        );

        foreach ($rows as $r) {
            $casos = $this->fetchAll(
                "SELECT cc.id, cc.fecha_cierre, cc.diferencia, cc.efectivo_esperado, cc.efectivo_contado, cc.caja_id
                 FROM cortes_caja cc
                 WHERE cc.hotel_id = ? AND cc.usuario_cierre_id = ?
                   AND cc.estado = 'cerrado'
                   AND cc.fecha_cierre >= ? AND cc.fecha_cierre <= ?
                   AND ABS(COALESCE(cc.diferencia, 0)) >= ?
                 ORDER BY cc.fecha_cierre DESC
                 LIMIT " . self::MAX_CASOS_POR_USUARIO,
                [
                    $hotelId,
                    (int) $r['usuario_id'],
                    $ventana['desde'] . ' 00:00:00',
                    $ventana['hasta'] . ' 23:59:59',
                    $config['diferencia_monto'],
                ]
            );

            $alerta['usuarios'][] = [
                'usuario_id' => (int) $r['usuario_id'],
                'nombre' => (string) ($r['nombre'] ?? 'Usuario #' . $r['usuario_id']),
                'valor' => (int) $r['casos'] . ' corte(s) con diferencia acumulada de $' . number_format((float) $r['monto'], 2)
                    . ' (' . (int) $r['faltantes'] . ' con faltante)',
                'contexto' => 'Umbral del hotel: $' . number_format($config['diferencia_monto'], 2)
                    . ' repetido ' . $config['diferencia_repeticiones'] . '+ veces en ' . $config['ventana_dias'] . ' dias.',
                'metrica' => [
                    'casos' => (int) $r['casos'],
                    'monto' => number_format((float) $r['monto'], 2, '.', ''),
                    'faltantes' => (int) $r['faltantes'],
                ],
                'casos' => array_map(static function (array $c): array {
                    return [
                        'corte_id' => (int) $c['id'],
                        'fecha_cierre' => (string) $c['fecha_cierre'],
                        'diferencia' => number_format((float) $c['diferencia'], 2, '.', ''),
                        'esperado' => number_format((float) $c['efectivo_esperado'], 2, '.', ''),
                        'contado' => number_format((float) $c['efectivo_contado'], 2, '.', ''),
                    ];
                }, $casos),
            ];
        }

        return $this->cerrarAlerta($alerta, static function (array $u) use ($config): string {
            return ($u['metrica']['faltantes'] >= $config['diferencia_repeticiones']) ? 'alta' : 'media';
        });
    }

    // ───────── Regla 5: reversiones/ajustes concentrados en un usuario ─────────

    private function reglaReversionesConcentradas(int $hotelId, array $ventana, array $config, bool $volumenSuficiente): array
    {
        $alerta = $this->alertaBase(
            'GD_REVERSIONES_CONCENTRADAS',
            'Reversiones y ajustes concentrados en una persona',
            'un usuario concentra un porcentaje desproporcionado de las reversiones o ediciones manuales de caja del hotel.',
            'caja'
        );

        if (!$volumenSuficiente) {
            $alerta['nota'] = 'volumen_insuficiente';
            return $alerta;
        }

        $condReversion = "(mc.categoria LIKE 'Reversion%' OR mc.editado = 1)";
        $totalHotel = $this->countSql(
            "SELECT COUNT(*) FROM movimientos_caja mc
             WHERE mc.hotel_id = ?
               AND mc.created_at >= ? AND mc.created_at <= ?
               AND {$condReversion}",
            [$hotelId, $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59']
        );

        // Sin un piso de reversiones en el hotel no hay concentracion que medir.
        if ($totalHotel < max(4, (int) ceil($config['min_volumen'] / 2))) {
            $alerta['nota'] = 'volumen_insuficiente';
            return $alerta;
        }

        $rows = $this->fetchAll(
            "SELECT mc.usuario_id, u.nombre_completo AS nombre,
                    COUNT(*) AS casos, COALESCE(SUM(mc.monto), 0) AS monto
             FROM movimientos_caja mc
             LEFT JOIN usuarios u ON u.id = mc.usuario_id
             WHERE mc.hotel_id = ?
               AND mc.created_at >= ? AND mc.created_at <= ?
               AND {$condReversion}
             GROUP BY mc.usuario_id, u.nombre_completo",
            [$hotelId, $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59']
        );

        foreach ($rows as $r) {
            $casosN = (int) $r['casos'];
            $pct = 100.0 * $casosN / $totalHotel;
            if ($pct < $config['reversiones_pct'] || $casosN < 3) {
                continue;
            }

            $casos = $this->fetchAll(
                "SELECT mc.id, mc.created_at, mc.tipo, mc.categoria, mc.monto, mc.referencia, mc.editado, mc.motivo_edicion
                 FROM movimientos_caja mc
                 WHERE mc.hotel_id = ? AND mc.usuario_id = ?
                   AND mc.created_at >= ? AND mc.created_at <= ?
                   AND {$condReversion}
                 ORDER BY mc.created_at DESC
                 LIMIT " . self::MAX_CASOS_POR_USUARIO,
                [$hotelId, (int) $r['usuario_id'], $ventana['desde'] . ' 00:00:00', $ventana['hasta'] . ' 23:59:59']
            );

            $alerta['usuarios'][] = [
                'usuario_id' => (int) $r['usuario_id'],
                'nombre' => (string) ($r['nombre'] ?? 'Usuario #' . $r['usuario_id']),
                'valor' => $casosN . ' de ' . $totalHotel . ' reversiones/ajustes del hotel (' . round($pct) . '%) por $' . number_format((float) $r['monto'], 2),
                'contexto' => 'Umbral de concentracion del hotel: ' . $config['reversiones_pct'] . '%.',
                'metrica' => ['casos' => $casosN, 'total_hotel' => $totalHotel, 'pct' => round($pct, 1)],
                'casos' => array_map(static function (array $c): array {
                    return [
                        'movimiento_id' => (int) $c['id'],
                        'fecha' => (string) $c['created_at'],
                        'categoria' => (string) $c['categoria'],
                        'monto' => number_format((float) $c['monto'], 2, '.', ''),
                        'referencia' => (string) ($c['referencia'] ?? ''),
                        'edicion_manual' => (int) $c['editado'] === 1,
                        'motivo' => (string) ($c['motivo_edicion'] ?? ''),
                    ];
                }, $casos),
            ];
        }

        return $this->cerrarAlerta($alerta, static function (array $u): string {
            return ($u['metrica']['pct'] >= 85 && $u['metrica']['casos'] >= 5) ? 'alta' : 'media';
        });
    }

    // ───────── Radiografia neutral "Por persona" ─────────

    /**
     * Indicadores por usuario activo en la ventana, SIEMPRE con el promedio del
     * hotel al lado. Es contexto, no lista negra: la vista debe presentarlo
     * de forma neutral.
     */
    public function porPersona(int $hotelId, array $ventana, ?array $config = null): array
    {
        $config = $config ?: $this->configuracion($hotelId);
        $desde = $ventana['desde'] . ' 00:00:00';
        $hasta = $ventana['hasta'] . ' 23:59:59';

        $usuarios = [];

        $res = $this->fetchAll(
            "SELECT r.usuario_registro_id AS usuario_id, u.nombre_completo AS nombre,
                    COUNT(*) AS reservas,
                    COALESCE(SUM(r.descuento_total), 0) AS descuento_sum,
                    COALESCE(SUM(r.precio_total + r.descuento_total), 0) AS base_sum,
                    SUM(CASE WHEN r.estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas
             FROM reservaciones r
             LEFT JOIN usuarios u ON u.id = r.usuario_registro_id
             WHERE r.hotel_id = ? AND r.usuario_registro_id IS NOT NULL
               AND r.created_at >= ? AND r.created_at <= ?
             GROUP BY r.usuario_registro_id, u.nombre_completo",
            [$hotelId, $desde, $hasta]
        );
        foreach ($res as $r) {
            $uid = (int) $r['usuario_id'];
            $usuarios[$uid]['nombre'] = (string) ($r['nombre'] ?? 'Usuario #' . $uid);
            $usuarios[$uid]['reservas'] = (int) $r['reservas'];
            $usuarios[$uid]['canceladas'] = (int) $r['canceladas'];
            $base = (float) $r['base_sum'];
            $usuarios[$uid]['descuento_pct'] = $base > 0 ? round(100.0 * (float) $r['descuento_sum'] / $base, 2) : 0.0;
            $usuarios[$uid]['descuento_monto'] = number_format((float) $r['descuento_sum'], 2, '.', '');
        }

        $mov = $this->fetchAll(
            "SELECT mc.usuario_id, u.nombre_completo AS nombre, COUNT(*) AS movimientos,
                    SUM(CASE WHEN mc.categoria LIKE 'Reversion%' OR mc.editado = 1 THEN 1 ELSE 0 END) AS reversiones
             FROM movimientos_caja mc
             LEFT JOIN usuarios u ON u.id = mc.usuario_id
             WHERE mc.hotel_id = ? AND mc.created_at >= ? AND mc.created_at <= ?
             GROUP BY mc.usuario_id, u.nombre_completo",
            [$hotelId, $desde, $hasta]
        );
        foreach ($mov as $r) {
            $uid = (int) $r['usuario_id'];
            $usuarios[$uid]['nombre'] = $usuarios[$uid]['nombre'] ?? (string) ($r['nombre'] ?? 'Usuario #' . $uid);
            $usuarios[$uid]['movimientos'] = (int) $r['movimientos'];
            $usuarios[$uid]['reversiones'] = (int) $r['reversiones'];
        }

        $cortes = $this->fetchAll(
            "SELECT cc.usuario_cierre_id AS usuario_id, COUNT(*) AS cortes,
                    SUM(CASE WHEN ABS(COALESCE(cc.diferencia, 0)) >= ? THEN 1 ELSE 0 END) AS cortes_con_diferencia,
                    COALESCE(SUM(ABS(COALESCE(cc.diferencia, 0))), 0) AS diferencia_abs
             FROM cortes_caja cc
             WHERE cc.hotel_id = ? AND cc.estado = 'cerrado' AND cc.usuario_cierre_id IS NOT NULL
               AND cc.fecha_cierre >= ? AND cc.fecha_cierre <= ?
             GROUP BY cc.usuario_cierre_id",
            [$config['diferencia_monto'], $hotelId, $desde, $hasta]
        );
        foreach ($cortes as $r) {
            $uid = (int) $r['usuario_id'];
            if (!isset($usuarios[$uid])) {
                continue;
            }
            $usuarios[$uid]['cortes'] = (int) $r['cortes'];
            $usuarios[$uid]['cortes_con_diferencia'] = (int) $r['cortes_con_diferencia'];
            $usuarios[$uid]['diferencia_abs'] = number_format((float) $r['diferencia_abs'], 2, '.', '');
        }

        // Promedios del hotel para dar contexto (nunca se muestra un numero
        // individual sin su comparativa).
        $n = max(1, count($usuarios));
        $prom = [
            'descuento_pct' => round(array_sum(array_column($usuarios, 'descuento_pct')) / $n, 2),
            'canceladas' => round(array_sum(array_column($usuarios, 'canceladas')) / $n, 1),
            'reversiones' => round(array_sum(array_map(static fn ($u) => (int) ($u['reversiones'] ?? 0), $usuarios)) / $n, 1),
            'cortes_con_diferencia' => round(array_sum(array_map(static fn ($u) => (int) ($u['cortes_con_diferencia'] ?? 0), $usuarios)) / $n, 1),
        ];

        $lista = [];
        foreach ($usuarios as $uid => $u) {
            $lista[] = [
                'usuario_id' => $uid,
                'nombre' => $u['nombre'],
                'reservas' => (int) ($u['reservas'] ?? 0),
                'descuento_pct' => (float) ($u['descuento_pct'] ?? 0),
                'descuento_monto' => (string) ($u['descuento_monto'] ?? '0.00'),
                'canceladas' => (int) ($u['canceladas'] ?? 0),
                'movimientos' => (int) ($u['movimientos'] ?? 0),
                'reversiones' => (int) ($u['reversiones'] ?? 0),
                'cortes' => (int) ($u['cortes'] ?? 0),
                'cortes_con_diferencia' => (int) ($u['cortes_con_diferencia'] ?? 0),
                'diferencia_abs' => (string) ($u['diferencia_abs'] ?? '0.00'),
            ];
        }
        usort($lista, static fn (array $a, array $b): int => strcmp($a['nombre'], $b['nombre']));

        return ['usuarios' => $lista, 'promedio_hotel' => $prom];
    }

    // ───────── Utilidades internas ─────────

    private function volumen(int $hotelId, array $ventana, int $minimo): array
    {
        $desde = $ventana['desde'] . ' 00:00:00';
        $hasta = $ventana['hasta'] . ' 23:59:59';

        $movs = $this->countSql(
            "SELECT COUNT(*) FROM movimientos_caja mc WHERE mc.hotel_id = ? AND mc.created_at >= ? AND mc.created_at <= ?",
            [$hotelId, $desde, $hasta]
        );
        $reservas = $this->countSql(
            "SELECT COUNT(*) FROM reservaciones r WHERE r.hotel_id = ? AND r.created_at >= ? AND r.created_at <= ?",
            [$hotelId, $desde, $hasta]
        );

        return [
            'movimientos' => $movs,
            'reservaciones' => $reservas,
            'minimo' => $minimo,
            'suficiente' => ($movs + $reservas) >= $minimo,
        ];
    }

    private function alertaBase(string $codigo, string $titulo, string $detalle, string $destino): array
    {
        return [
            'tipo' => 'patron',
            'codigo' => $codigo,
            'titulo' => $titulo,
            'severidad' => 'ok',
            'conteo' => 0,
            'detalle' => $detalle,
            'destino' => $destino,
            'usuarios' => [],
        ];
    }

    /** Fija conteo y severidad final de la alerta segun sus usuarios. */
    private function cerrarAlerta(array $alerta, callable $severidadUsuario): array
    {
        $alerta['conteo'] = count($alerta['usuarios']);
        if ($alerta['conteo'] === 0) {
            return $alerta;
        }

        $severidad = 'media';
        foreach ($alerta['usuarios'] as $i => $u) {
            $s = $severidadUsuario($u);
            $alerta['usuarios'][$i]['severidad'] = $s;
            if ($s === 'alta') {
                $severidad = 'alta';
            }
        }
        $alerta['severidad'] = $severidad;

        return $alerta;
    }

    /** True si el hotel configuro explicitamente su horario operativo. */
    private function horarioConfiguradoExplicito(int $hotelId): bool
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM hotel_configuracion
                 WHERE hotel_id = ? AND activo = 1
                   AND clave IN ('guardian.horario_inicio', 'guardian.horario_fin')",
                [$hotelId]
            );
            return $stmt && (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function mediana(array $valores): float
    {
        sort($valores);
        $n = count($valores);
        if ($n === 0) {
            return 0.0;
        }
        $mitad = intdiv($n, 2);
        return ($n % 2 === 1)
            ? (float) $valores[$mitad]
            : ((float) $valores[$mitad - 1] + (float) $valores[$mitad]) / 2.0;
    }

    private function horaSegura($valor, string $default): string
    {
        $valor = trim((string) $valor);
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) ? $valor : $default;
    }

    private function fechaFiltro($value): string
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private function decimal($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }

    private function countSql(string $sql, array $params = []): int
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }
}
