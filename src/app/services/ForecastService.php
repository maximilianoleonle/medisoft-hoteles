<?php

/**
 * ForecastService (bloque forecast, $199)
 *
 * Proyeccion de ocupacion y ritmo de reservas. 100% LECTURA:
 * jamas escribe en reservaciones, habitaciones ni Caja. Cuenta habitaciones
 * ocupadas por dia a partir de reservacion_habitaciones (una fila por
 * habitacion reservada) para que las reservas multi-habitacion pesen bien.
 */
class ForecastService
{
    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /** Habitaciones activas del hotel (denominador de la ocupacion). */
    public function habitacionesActivas(int $hotelId): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND activa = 1"
            );
            $stmt->execute([$hotelId]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Forecast: error al contar habitaciones: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Habitaciones ocupadas por dia dentro de [$desde, $desde + $dias).
     * Devuelve ['YYYY-MM-DD' => int]. $soloVigentes = true usa solo
     * confirmada/checked_in (futuro); false excluye solo canceladas (pasado).
     */
    public function ocupacionPorDia(int $hotelId, string $desde, int $dias, bool $soloVigentes = true): array
    {
        $dias = max(1, min(180, $dias));
        $hasta = date('Y-m-d', strtotime($desde . ' +' . $dias . ' days'));

        $porDia = [];
        $cursor = strtotime($desde);
        for ($i = 0; $i < $dias; $i++) {
            $porDia[date('Y-m-d', $cursor)] = 0;
            $cursor = strtotime('+1 day', $cursor);
        }

        $filtroEstado = $soloVigentes
            ? "r.estado IN ('confirmada', 'checked_in')"
            : "r.estado NOT IN ('cancelada', 'pendiente')";

        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.fecha_entrada, r.fecha_salida, COUNT(rh.id) AS habs
                 FROM reservaciones r
                 INNER JOIN reservacion_habitaciones rh
                     ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ?
                   AND {$filtroEstado}
                   AND r.fecha_salida > ?
                   AND r.fecha_entrada < ?
                 GROUP BY r.id, r.fecha_entrada, r.fecha_salida"
            );
            $stmt->execute([$hotelId, $desde, $hasta]);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $reserva) {
                $inicio = max(strtotime((string) $reserva['fecha_entrada']), strtotime($desde));
                $fin = min(strtotime((string) $reserva['fecha_salida']), strtotime($hasta));
                for ($d = $inicio; $d < $fin; $d = strtotime('+1 day', $d)) {
                    $clave = date('Y-m-d', $d);
                    if (isset($porDia[$clave])) {
                        $porDia[$clave] += (int) $reserva['habs'];
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Forecast: error al calcular ocupacion por dia: ' . $e->getMessage());
        }

        return $porDia;
    }

    /** Ocupacion promedio (%) de los primeros $dias del arreglo diario. */
    public static function promedio(array $porDia, int $dias, int $totalHabitaciones): ?float
    {
        if ($totalHabitaciones <= 0) {
            return null;
        }

        $valores = array_slice(array_values($porDia), 0, $dias);
        if (empty($valores)) {
            return null;
        }

        return round(array_sum($valores) * 100 / (count($valores) * $totalHabitaciones), 1);
    }

    /**
     * Pickup: reservas tomadas (created_at) en los ultimos 7 dias vs los 7
     * anteriores. Noches-habitacion y monto para dimensionar el ritmo.
     */
    public function pickup(int $hotelId): array
    {
        $vacio = [
            'reservas_7' => 0, 'noches_7' => 0, 'monto_7' => 0.0,
            'reservas_prev' => 0, 'noches_prev' => 0, 'monto_prev' => 0.0,
        ];

        try {
            $stmt = $this->pdo->prepare(
                "SELECT
                    SUM(created_at >= NOW() - INTERVAL 7 DAY) AS reservas_7,
                    SUM(CASE WHEN created_at >= NOW() - INTERVAL 7 DAY
                        THEN GREATEST(1, DATEDIFF(fecha_salida, fecha_entrada)) * GREATEST(1, total_habitaciones) ELSE 0 END) AS noches_7,
                    SUM(CASE WHEN created_at >= NOW() - INTERVAL 7 DAY THEN precio_total ELSE 0 END) AS monto_7,
                    SUM(created_at < NOW() - INTERVAL 7 DAY) AS reservas_prev,
                    SUM(CASE WHEN created_at < NOW() - INTERVAL 7 DAY
                        THEN GREATEST(1, DATEDIFF(fecha_salida, fecha_entrada)) * GREATEST(1, total_habitaciones) ELSE 0 END) AS noches_prev,
                    SUM(CASE WHEN created_at < NOW() - INTERVAL 7 DAY THEN precio_total ELSE 0 END) AS monto_prev
                 FROM reservaciones
                 WHERE hotel_id = ?
                   AND created_at >= NOW() - INTERVAL 14 DAY
                   AND estado <> 'cancelada'"
            );
            $stmt->execute([$hotelId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return $vacio;
            }

            return [
                'reservas_7' => (int) $row['reservas_7'],
                'noches_7' => (int) $row['noches_7'],
                'monto_7' => (float) $row['monto_7'],
                'reservas_prev' => (int) $row['reservas_prev'],
                'noches_prev' => (int) $row['noches_prev'],
                'monto_prev' => (float) $row['monto_prev'],
            ];
        } catch (Throwable $e) {
            error_log('Forecast: error al calcular pickup: ' . $e->getMessage());
            return $vacio;
        }
    }

    /**
     * Resumen semanal de las proximas $semanas: ocupacion proyectada y la del
     * mismo periodo de anios anteriores. Cuando el hotel tiene historia, la
     * comparativa promedia hasta 3 anios del mismo periodo (solo anios CON
     * datos cuentan); con 1 anio de historia todo se comporta como antes.
     * Formato de salida estable para los consumidores existentes; agrega
     * 'anios_comparados' (informativo) a cada fila.
     */
    public function resumenSemanal(int $hotelId, int $semanas, int $totalHabitaciones): array
    {
        $dias = $semanas * 7;
        $hoy = date('Y-m-d');

        $futuro = $this->ocupacionPorDia($hotelId, $hoy, $dias, true);

        // Series del mismo periodo de hasta 3 anios atras; un anio sin una
        // sola noche vendida no existe para la comparativa.
        $series = [];
        $serieAnio1 = null;
        for ($y = 1; $y <= 3; $y++) {
            $inicioAnio = date('Y-m-d', strtotime($hoy . ' -' . $y . ' year'));
            $serie = array_values($this->ocupacionPorDia($hotelId, $inicioAnio, $dias, false));
            if ($y === 1) {
                $serieAnio1 = $serie;
            }
            if (array_sum($serie) > 0) {
                $series[] = $serie;
            }
        }
        // Sin historia en ningun anio: comparar contra el anio pasado (ceros),
        // exactamente como se comportaba antes.
        if (empty($series)) {
            $series[] = $serieAnio1 ?? array_fill(0, $dias, 0);
        }
        $aniosComparados = count($series);

        $valoresFuturo = array_values($futuro);
        $clavesFuturo = array_keys($futuro);

        $filas = [];
        for ($s = 0; $s < $semanas; $s++) {
            $sliceFuturo = array_slice($valoresFuturo, $s * 7, 7);
            $inicio = $clavesFuturo[$s * 7] ?? $hoy;

            $nochesFuturo = array_sum($sliceFuturo);
            $ocupacion = $totalHabitaciones > 0
                ? round($nochesFuturo * 100 / (7 * $totalHabitaciones), 1)
                : null;

            $ocupacionPasado = null;
            if ($totalHabitaciones > 0) {
                $sumaPct = 0.0;
                foreach ($series as $serie) {
                    $sumaPct += array_sum(array_slice($serie, $s * 7, 7)) * 100 / (7 * $totalHabitaciones);
                }
                $ocupacionPasado = round($sumaPct / $aniosComparados, 1);
            }

            $filas[] = [
                'inicio' => $inicio,
                'fin' => date('Y-m-d', strtotime($inicio . ' +6 days')),
                'noches' => $nochesFuturo,
                'ocupacion' => $ocupacion,
                'ocupacion_anterior' => $ocupacionPasado,
                'delta' => ($ocupacion !== null && $ocupacionPasado !== null)
                    ? round($ocupacion - $ocupacionPasado, 1)
                    : null,
                'anios_comparados' => $aniosComparados,
            ];
        }

        return $filas;
    }

    /**
     * true si el hotel ya tiene historia de hace ~1 anio (alguna estancia no
     * cancelada iniciada hace 11 meses o mas). Sirve para detectar el
     * arranque en frio del consejo de tarifa.
     */
    public function tieneHistoricoAnual(int $hotelId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT 1
                 FROM reservaciones
                 WHERE hotel_id = ?
                   AND estado NOT IN ('cancelada', 'pendiente')
                   AND fecha_entrada <= ?
                 LIMIT 1"
            );
            $stmt->execute([$hotelId, date('Y-m-d', strtotime('-11 months'))]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Forecast: error al detectar historico anual: ' . $e->getMessage());
            return true; // ante la duda, no molestar con el aviso
        }
    }
}
