<?php
/**
 * Proyección de estacionamiento a días futuros.
 *
 * Para cada día D cuenta los vehículos de reservaciones activas (confirmada/checked_in)
 * cuya estancia CUBRE el día (fecha_entrada <= D < fecha_salida, mismas noches que cobra
 * el hotel). Fuente del conteo por reservación:
 *   - reservaciones.vehiculos_estimados NOT NULL → manda ese número (lo declaró el operador
 *     al reservar; 0 = "no trae vehículo" y silencia los registrados).
 *   - NULL (reservas previas a la pregunta) → fallback: vehículos activos registrados del
 *     huésped (huesped_vehiculos), cada uno en su área.
 * Los estimados sin vehículo registrado que los respalde caen al área 'por_confirmar'
 * (aún no se sabe si es coche o camioneta).
 *
 * Métodos estáticos puros (proyectarDia / clasificarNivel) con suite propia
 * (tests/casos/EstacionamientoProyeccionTest.php).
 */

class EstacionamientoProyeccionService {

    const AREA_POR_CONFIRMAR = 'por_confirmar';

    /**
     * Conteo de UN día a partir de datos ya cargados. PURO (sin BD).
     *
     * @param array $reservas            Reservas que cubren el día: [['huesped_id'=>int,'vehiculos_estimados'=>int|null], ...]
     * @param array $vehiculosPorHuesped [huesped_id => [area => cantidad]] (solo activos)
     * @return array ['total'=>int, 'por_area'=>[area=>int]]
     */
    public static function proyectarDia(array $reservas, array $vehiculosPorHuesped) {
        // Dedup por huésped: si tiene varias reservas cubriendo el día, manda la que
        // traiga estimado declarado (y entre varias declaradas, la mayor: determinista).
        $porHuesped = [];
        foreach ($reservas as $reserva) {
            $huespedId = (int)($reserva['huesped_id'] ?? 0);
            if ($huespedId <= 0) {
                continue;
            }

            $estimado = $reserva['vehiculos_estimados'];
            $estimado = ($estimado === null || $estimado === '') ? null : max(0, (int)$estimado);

            if (!array_key_exists($huespedId, $porHuesped)) {
                $porHuesped[$huespedId] = $estimado;
                continue;
            }

            if ($estimado !== null && ($porHuesped[$huespedId] === null || $estimado > $porHuesped[$huespedId])) {
                $porHuesped[$huespedId] = $estimado;
            }
        }

        $total = 0;
        $porArea = [];

        foreach ($porHuesped as $huespedId => $estimado) {
            $registrados = $vehiculosPorHuesped[$huespedId] ?? [];

            if ($estimado === null) {
                // Sin declaración: cuentan todos los registrados en su área.
                foreach ($registrados as $area => $cantidad) {
                    $cantidad = max(0, (int)$cantidad);
                    if ($cantidad > 0) {
                        $porArea[$area] = ($porArea[$area] ?? 0) + $cantidad;
                        $total += $cantidad;
                    }
                }
                continue;
            }

            if ($estimado === 0) {
                continue; // Declaró que no trae vehículo.
            }

            // Declarado: repartir hasta $estimado empezando por sus registrados;
            // lo que exceda va a 'por_confirmar'.
            $restante = $estimado;
            foreach ($registrados as $area => $cantidad) {
                if ($restante <= 0) {
                    break;
                }
                $usar = min($restante, max(0, (int)$cantidad));
                if ($usar > 0) {
                    $porArea[$area] = ($porArea[$area] ?? 0) + $usar;
                    $restante -= $usar;
                }
            }
            if ($restante > 0) {
                $porArea[self::AREA_POR_CONFIRMAR] = ($porArea[self::AREA_POR_CONFIRMAR] ?? 0) + $restante;
            }

            $total += $estimado;
        }

        return ['total' => $total, 'por_area' => $porArea];
    }

    /**
     * Semáforo del día (mismos umbrales que la tarjeta del dashboard). PURO.
     * @return string sin_cupo | ok | ocupado | casi_lleno | sobrecupo
     */
    public static function clasificarNivel($total, $cupoTotal) {
        $total = max(0, (int)$total);
        $cupoTotal = max(0, (int)$cupoTotal);

        if ($cupoTotal <= 0) {
            return 'sin_cupo';
        }
        if ($total > $cupoTotal) {
            return 'sobrecupo';
        }

        $pct = ($total / $cupoTotal) * 100;
        if ($pct >= 90) {
            return 'casi_lleno';
        }
        if ($pct >= 70) {
            return 'ocupado';
        }

        return 'ok';
    }

    /**
     * Proyección de un rango de días para un hotel (consulta BD).
     *
     * @param int    $hotelId
     * @param string $desde       Y-m-d del primer día proyectado
     * @param int    $dias        1..31
     * @param array  $catalogRows [codigo => ['codigo','label','cupo']] (catálogo de áreas del hotel)
     * @return array ['desde','dias','cupo_total','areas','proyeccion'=>[['fecha','total','por_area','nivel','pct']]]
     */
    public function proyectar($hotelId, $desde, $dias, array $catalogRows) {
        $hotelId = (int)$hotelId;
        $dias = max(1, min(31, (int)$dias));

        $inicio = DateTime::createFromFormat('Y-m-d', (string)$desde);
        if (!$inicio || $inicio->format('Y-m-d') !== (string)$desde) {
            $inicio = new DateTime('today');
        }
        $inicio->setTime(0, 0, 0);
        $fin = (clone $inicio)->modify("+{$dias} days"); // exclusivo

        $cupoTotal = 0;
        foreach ($catalogRows as $row) {
            $cupoTotal += max(0, min(999, (int)($row['cupo'] ?? 0)));
        }

        $db = Database::getInstance();

        // Reservas activas que solapan el rango (noches: salida exclusiva).
        $stmt = $db->query(
            "SELECT r.id, r.huesped_id, r.fecha_entrada, r.fecha_salida, r.vehiculos_estimados
             FROM reservaciones r
             WHERE r.hotel_id = ?
               AND r.estado IN ('confirmada', 'checked_in')
               AND r.fecha_entrada < ?
               AND r.fecha_salida > ?",
            [$hotelId, $fin->format('Y-m-d'), $inicio->format('Y-m-d')]
        );
        $reservas = $stmt ? ($stmt->fetchAll() ?: []) : [];

        // Vehículos registrados activos de los huéspedes involucrados, por área.
        $vehiculosPorHuesped = [];
        $huespedIds = array_values(array_unique(array_map(
            function ($r) { return (int)$r['huesped_id']; },
            $reservas
        )));
        if (!empty($huespedIds)) {
            $placeholders = implode(',', array_fill(0, count($huespedIds), '?'));
            $stmtVeh = $db->query(
                "SELECT hv.huesped_id, hv.estacionamiento, COUNT(*) AS cantidad
                 FROM huesped_vehiculos hv
                 WHERE hv.hotel_id = ?
                   AND hv.activo = 1
                   AND hv.huesped_id IN ({$placeholders})
                 GROUP BY hv.huesped_id, hv.estacionamiento",
                array_merge([$hotelId], $huespedIds)
            );
            foreach (($stmtVeh ? ($stmtVeh->fetchAll() ?: []) : []) as $fila) {
                $hid = (int)$fila['huesped_id'];
                $area = trim((string)($fila['estacionamiento'] ?? ''));
                $area = $area !== '' ? $area : 'coches';
                $vehiculosPorHuesped[$hid][$area] = (int)$fila['cantidad'];
            }
        }

        $proyeccion = [];
        $cursor = clone $inicio;
        for ($i = 0; $i < $dias; $i++) {
            $fecha = $cursor->format('Y-m-d');

            $delDia = array_values(array_filter($reservas, function ($r) use ($fecha) {
                return $r['fecha_entrada'] <= $fecha && $r['fecha_salida'] > $fecha;
            }));

            $conteo = self::proyectarDia($delDia, $vehiculosPorHuesped);
            $proyeccion[] = [
                'fecha' => $fecha,
                'total' => $conteo['total'],
                'por_area' => $conteo['por_area'],
                'nivel' => self::clasificarNivel($conteo['total'], $cupoTotal),
                'pct' => $cupoTotal > 0 ? min(999, (int)round(($conteo['total'] / $cupoTotal) * 100)) : 0,
            ];

            $cursor->modify('+1 day');
        }

        return [
            'desde' => $inicio->format('Y-m-d'),
            'dias' => $dias,
            'cupo_total' => $cupoTotal,
            'areas' => array_values($catalogRows),
            'proyeccion' => $proyeccion,
        ];
    }
}
