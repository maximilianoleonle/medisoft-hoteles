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
     * Desglose vehículo-por-vehículo de UN día. PURO (sin BD).
     *
     * Mismas reglas de conteo que proyectarDia (dedup por huésped, semántica de
     * vehiculos_estimados): la suma de 'cantidad' de las filas devueltas SIEMPRE
     * coincide con proyectarDia()['total'] para los mismos datos.
     *
     * @param array $reservas Reservas que cubren el día; cada fila trae huesped_id y
     *                        vehiculos_estimados más campos de contexto que se copian
     *                        a la salida (reservacion_id, huesped, habitaciones, estado...).
     * @param array $vehiculosDetallePorHuesped [huesped_id => [['area','vehiculo','placas'], ...]]
     * @return array Filas: ['huesped_id','reservacion_id','huesped','habitaciones','estado',
     *                       'area','vehiculo','placas','cantidad','por_confirmar']
     */
    public static function detalleDia(array $reservas, array $vehiculosDetallePorHuesped) {
        // Dedup por huésped con la MISMA regla que proyectarDia, pero conservando
        // la reserva ganadora para poder enlazarla.
        $porHuesped = [];
        foreach ($reservas as $reserva) {
            $huespedId = (int)($reserva['huesped_id'] ?? 0);
            if ($huespedId <= 0) {
                continue;
            }

            $estimado = $reserva['vehiculos_estimados'] ?? null;
            $estimado = ($estimado === null || $estimado === '') ? null : max(0, (int)$estimado);

            if (!array_key_exists($huespedId, $porHuesped)) {
                $porHuesped[$huespedId] = ['estimado' => $estimado, 'reserva' => $reserva];
                continue;
            }

            if ($estimado !== null && ($porHuesped[$huespedId]['estimado'] === null || $estimado > $porHuesped[$huespedId]['estimado'])) {
                $porHuesped[$huespedId] = ['estimado' => $estimado, 'reserva' => $reserva];
            }
        }

        $filas = [];
        foreach ($porHuesped as $huespedId => $item) {
            $estimado = $item['estimado'];
            $reserva = $item['reserva'];
            $registrados = $vehiculosDetallePorHuesped[$huespedId] ?? [];

            $base = [
                'huesped_id' => $huespedId,
                'reservacion_id' => (int)($reserva['reservacion_id'] ?? $reserva['id'] ?? 0),
                'huesped' => (string)($reserva['huesped'] ?? ''),
                'habitaciones' => (string)($reserva['habitaciones'] ?? ''),
                'estado' => (string)($reserva['estado'] ?? ''),
            ];

            if ($estimado === 0) {
                continue; // Declaró que no trae vehículo.
            }

            $usar = ($estimado === null) ? count($registrados) : min($estimado, count($registrados));
            for ($i = 0; $i < $usar; $i++) {
                $vehiculo = $registrados[$i];
                $filas[] = $base + [
                    'area' => (string)($vehiculo['area'] ?? 'coches'),
                    'vehiculo' => (string)($vehiculo['vehiculo'] ?? ''),
                    'placas' => (string)($vehiculo['placas'] ?? ''),
                    'cantidad' => 1,
                    'por_confirmar' => false,
                ];
            }

            $pendientes = ($estimado === null) ? 0 : max(0, $estimado - count($registrados));
            if ($pendientes > 0) {
                $filas[] = $base + [
                    'area' => self::AREA_POR_CONFIRMAR,
                    'vehiculo' => '',
                    'placas' => '',
                    'cantidad' => $pendientes,
                    'por_confirmar' => true,
                ];
            }
        }

        // Hospedados primero, luego por nombre; los "por confirmar" al final de su huésped.
        usort($filas, function ($a, $b) {
            $pa = $a['estado'] === 'checked_in' ? 0 : 1;
            $pb = $b['estado'] === 'checked_in' ? 0 : 1;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            $nombre = strcasecmp($a['huesped'], $b['huesped']);
            if ($nombre !== 0) {
                return $nombre;
            }
            return ((int)$a['por_confirmar']) <=> ((int)$b['por_confirmar']);
        });

        return $filas;
    }

    /**
     * Nombre presentable de un vehículo registrado. PURO.
     * Ignora los rellenos tipo "sin definir" que arrastra la captura legacy
     * (se veían nombres como "AUTOBUS SIN DEFINIR sin definir" en el dashboard).
     */
    public static function nombreVehiculo($marca, $modelo, $color) {
        $partes = [];
        foreach ([$marca, $modelo, $color] as $parte) {
            $parte = trim((string)$parte);
            if ($parte === '' || self::esRellenoSinDato($parte)) {
                continue;
            }
            $partes[] = $parte;
        }
        return !empty($partes) ? implode(' ', $partes) : 'Vehículo registrado';
    }

    /** true si el texto es un relleno de "no capturado" (sin definir, n/a, -...). PURO. */
    public static function esRellenoSinDato($valor) {
        $norm = mb_strtolower(trim((string)$valor), 'UTF-8');
        return in_array($norm, ['sin definir', 'sin especificar', 'sin dato', 'n/a', 'na', 's/d', '-', '--'], true);
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

    /**
     * Desglose de vehículos de UN día para un hotel (consulta BD).
     * Devuelve filas de detalleDia() con 'area_label' y 'estado_label' resueltos,
     * listas para pintarse como enlaces a cada reservación.
     *
     * @param int    $hotelId
     * @param string $fecha       Y-m-d del día a desglosar
     * @param array  $catalogRows [codigo => ['codigo','label','cupo']]
     * @return array
     */
    public function detallarDia($hotelId, $fecha, array $catalogRows) {
        $hotelId = (int)$hotelId;

        $dia = DateTime::createFromFormat('Y-m-d', (string)$fecha);
        if (!$dia || $dia->format('Y-m-d') !== (string)$fecha) {
            $dia = new DateTime('today');
        }
        $fecha = $dia->format('Y-m-d');

        $db = Database::getInstance();

        // Mismos estados y solape de noches que proyectar() para que el desglose
        // cuadre con la barra del día.
        $stmt = $db->query(
            "SELECT r.id AS reservacion_id, r.huesped_id, r.estado, r.fecha_entrada, r.fecha_salida,
                    r.vehiculos_estimados,
                    h.nombre_completo AS huesped,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones
             FROM reservaciones r
             INNER JOIN huespedes h
                 ON h.id = r.huesped_id
                AND h.hotel_id = r.hotel_id
             LEFT JOIN reservacion_habitaciones rh
                 ON rh.reservacion_id = r.id
                AND rh.hotel_id = r.hotel_id
             LEFT JOIN habitaciones hab
                 ON hab.id = rh.habitacion_id
                AND hab.hotel_id = r.hotel_id
             WHERE r.hotel_id = ?
               AND r.estado IN ('confirmada', 'checked_in')
               AND r.fecha_entrada <= ?
               AND r.fecha_salida > ?
             GROUP BY r.id, r.huesped_id, r.estado, r.fecha_entrada, r.fecha_salida, r.vehiculos_estimados, h.nombre_completo",
            [$hotelId, $fecha, $fecha]
        );
        $reservas = $stmt ? ($stmt->fetchAll() ?: []) : [];
        if (empty($reservas)) {
            return [];
        }

        $vehiculosDetalle = [];
        $huespedIds = array_values(array_unique(array_map(
            function ($r) { return (int)$r['huesped_id']; },
            $reservas
        )));
        if (!empty($huespedIds)) {
            $placeholders = implode(',', array_fill(0, count($huespedIds), '?'));
            $stmtVeh = $db->query(
                "SELECT hv.huesped_id, hv.estacionamiento, hv.marca, hv.modelo, hv.color, hv.placas
                 FROM huesped_vehiculos hv
                 WHERE hv.hotel_id = ?
                   AND hv.activo = 1
                   AND hv.huesped_id IN ({$placeholders})
                 ORDER BY hv.estacionamiento ASC, hv.created_at ASC, hv.id ASC",
                array_merge([$hotelId], $huespedIds)
            );
            foreach (($stmtVeh ? ($stmtVeh->fetchAll() ?: []) : []) as $fila) {
                $hid = (int)$fila['huesped_id'];
                $area = trim((string)($fila['estacionamiento'] ?? ''));
                $placas = trim((string)($fila['placas'] ?? ''));
                $vehiculosDetalle[$hid][] = [
                    'area' => $area !== '' ? $area : 'coches',
                    'vehiculo' => self::nombreVehiculo($fila['marca'] ?? '', $fila['modelo'] ?? '', $fila['color'] ?? ''),
                    'placas' => self::esRellenoSinDato($placas) ? '' : $placas,
                ];
            }
        }

        $filas = self::detalleDia($reservas, $vehiculosDetalle);

        foreach ($filas as $i => $fila) {
            $area = (string)$fila['area'];
            if ($area === self::AREA_POR_CONFIRMAR) {
                $areaLabel = 'Por confirmar';
            } elseif (isset($catalogRows[$area]['label'])) {
                $areaLabel = (string)$catalogRows[$area]['label'];
            } else {
                $areaLabel = ucwords(str_replace(['_', '-'], ' ', $area));
            }

            if ($fila['estado'] === 'checked_in') {
                $estadoLabel = 'Hospedado';
            } else {
                $reservaFila = null;
                foreach ($reservas as $r) {
                    if ((int)$r['reservacion_id'] === (int)$fila['reservacion_id']) {
                        $reservaFila = $r;
                        break;
                    }
                }
                $estadoLabel = ($reservaFila && (string)$reservaFila['fecha_entrada'] === $fecha)
                    ? 'Llega ese día'
                    : 'Confirmada';
            }

            $filas[$i]['area_label'] = $areaLabel;
            $filas[$i]['estado_label'] = $estadoLabel;
        }

        return $filas;
    }
}
