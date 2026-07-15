<?php
/**
 * Score del dia para el Modo Dueno (bloque modo_dueno).
 *
 * Calculo DETERMINISTA y 100% lectura: sin IA, sin tablas nuevas. Combina
 * hasta cuatro senales del dia con pesos fijos y renormaliza sobre las que
 * esten disponibles (permisos del usuario + datos existentes):
 *
 *   caja      (30) caja trabajando hoy y ultimo cierre cuadrado
 *   limpieza  (20) habitaciones listas vs pendientes de limpieza
 *   ocupacion (30) ocupacion de hoy vs el promedio propio del hotel
 *                  (ultimas 4 semanas, mismo dia de la semana)
 *   resenas   (20) calificacion promedio reciente de huespedes
 *
 * Salida: numero 0-100 + etiqueta en palabras (la etiqueta pesa mas que el
 * numero en la UI) + desglose de 3-4 lineas en lenguaje hablado, sin jerga.
 */

class DuenoScoreService {

    /** @var Database */
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * @param array $puede Gates ya resueltos por el llamador:
     *                     ['caja' => bool, 'habitaciones' => bool, 'resenas' => bool]
     */
    public function scoreDelDia(int $hotelId, array $puede): array {
        $factores = [];

        if (!empty($puede['caja'])) {
            $factores[] = ['peso' => 30, 'factor' => $this->factorCaja($hotelId)];
        }

        if (!empty($puede['habitaciones'])) {
            $factores[] = ['peso' => 20, 'factor' => $this->factorLimpieza($hotelId)];
            $factores[] = ['peso' => 30, 'factor' => $this->factorOcupacion($hotelId)];
        }

        if (!empty($puede['resenas'])) {
            $factores[] = ['peso' => 20, 'factor' => $this->factorResenas($hotelId)];
        }

        // Solo factores con datos; renormaliza pesos sobre los disponibles.
        $conDatos = array_values(array_filter($factores, static function ($f) {
            return $f['factor'] !== null;
        }));

        if (empty($conDatos)) {
            return [
                'score' => null,
                'estado' => 'verde',
                'etiqueta' => 'Día tranquilo',
                'detalle' => 'Todavía no hay movimiento que contar hoy.',
                'desglose' => [],
            ];
        }

        $pesoTotal = 0;
        $suma = 0.0;
        $desglose = [];

        foreach ($conDatos as $f) {
            $pesoTotal += $f['peso'];
            $suma += $f['peso'] * $f['factor']['puntos'];
            $desglose[] = $f['factor']['linea'];
        }

        $score = (int) round($suma / max(1, $pesoTotal));
        $score = max(0, min(100, $score));

        if ($score >= 80) {
            $estado = 'verde';
            $etiqueta = 'Muy buen día';
            $detalle = 'Tu hotel está trabajando muy bien hoy.';
        } elseif ($score >= 55) {
            $estado = 'verde';
            $etiqueta = 'Día tranquilo';
            $detalle = 'Todo marcha con calma; nada urge.';
        } else {
            $estado = 'ambar';
            $etiqueta = 'Hay pendientes';
            $detalle = 'Hay cosas del día que vale la pena revisar.';
        }

        return [
            'score' => $score,
            'estado' => $estado,
            'etiqueta' => $etiqueta,
            'detalle' => $detalle,
            'desglose' => array_slice($desglose, 0, 4),
        ];
    }

    /* ------------------------------------------------------------------
     * Factores: cada uno devuelve ['puntos' => 0-100, 'linea' => texto]
     * o null si no hay datos suficientes (el factor se omite).
     * ---------------------------------------------------------------- */

    /**
     * Caja del dia: trabajando hoy = bien; el ultimo cierre reciente que
     * cuadro suma; una diferencia grande resta. Sin jerga de "cortes".
     */
    private function factorCaja(int $hotelId): ?array {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS abiertos
             FROM cortes_caja
             WHERE hotel_id = ? AND estado = 'abierto'",
            [$hotelId]
        );
        $abiertos = (int) (($stmt ? $stmt->fetch() : [])['abiertos'] ?? 0);

        // Ultimo cierre de las ultimas 48 h: ¿cuadro el dinero?
        $stmt = $this->db->query(
            "SELECT diferencia
             FROM cortes_caja
             WHERE hotel_id = ? AND estado = 'cerrado'
               AND fecha_cierre >= NOW() - INTERVAL 48 HOUR
             ORDER BY fecha_cierre DESC
             LIMIT 1",
            [$hotelId]
        );
        $cierre = $stmt ? $stmt->fetch() : null;

        if ($abiertos === 0 && !$cierre) {
            // Ni caja abierta ni cierres recientes.
            return [
                'puntos' => 40,
                'linea' => 'La caja aún no se abre hoy.',
            ];
        }

        $puntos = $abiertos > 0 ? 70 : 55;
        $linea = $abiertos > 0 ? 'La caja está trabajando.' : 'La caja ya cerró por hoy.';

        if ($cierre !== null && $cierre['diferencia'] !== null) {
            $diferencia = round(abs((float) $cierre['diferencia']), 2);

            if ($diferencia <= 0.009) {
                $puntos = 100;
                $linea = $abiertos > 0
                    ? 'El dinero cuadró en el último cierre y la caja sigue trabajando.'
                    : 'Todo cuadró en caja.';
            } elseif ($diferencia <= 20) {
                $puntos = 75;
                $linea = 'El último cierre quedó casi exacto (por $' . number_format($diferencia, 2, '.', ',') . ').';
            } else {
                $puntos = 35;
                $linea = 'El último cierre no cuadró por $' . number_format($diferencia, 2, '.', ',') . '.';
            }
        }

        return ['puntos' => $puntos, 'linea' => $linea];
    }

    /**
     * Limpieza del dia: habitaciones listas vs pendientes de limpieza.
     */
    private function factorLimpieza(int $hotelId): ?array {
        $stmt = $this->db->query(
            "SELECT SUM(estado = 'disponible') AS listas,
                    SUM(estado = 'limpieza') AS pendientes
             FROM habitaciones
             WHERE hotel_id = ? AND activa = 1",
            [$hotelId]
        );
        $fila = $stmt ? $stmt->fetch() : null;
        $listas = (int) ($fila['listas'] ?? 0);
        $pendientes = (int) ($fila['pendientes'] ?? 0);
        $ciclo = $listas + $pendientes;

        if ($ciclo === 0) {
            return null; // hotel sin habitaciones en ciclo de limpieza
        }

        $puntos = (int) round($listas * 100 / $ciclo);

        if ($pendientes === 0) {
            $linea = 'Todas las habitaciones están listas.';
        } elseif ($pendientes === 1) {
            $linea = 'Falta 1 habitación por limpiar.';
        } else {
            $linea = 'Faltan ' . $pendientes . ' habitaciones por limpiar.';
        }

        return ['puntos' => $puntos, 'linea' => $linea];
    }

    /**
     * Ocupacion de hoy contra el promedio PROPIO del hotel: mismas 4 fechas
     * del mismo dia de semana (hace 7, 14, 21 y 28 dias), reconstruidas de
     * las reservaciones que si se quedaron.
     */
    private function factorOcupacion(int $hotelId): ?array {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total, SUM(estado = 'ocupada') AS ocupadas
             FROM habitaciones
             WHERE hotel_id = ? AND activa = 1",
            [$hotelId]
        );
        $fila = $stmt ? $stmt->fetch() : null;
        $total = (int) ($fila['total'] ?? 0);
        $hoy = (int) ($fila['ocupadas'] ?? 0);

        if ($total === 0) {
            return null;
        }

        // Habitaciones ocupadas en cada una de las 4 fechas espejo.
        $historico = [];
        foreach ([7, 14, 21, 28] as $dias) {
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT rh.habitacion_id) AS ocupadas
                 FROM reservacion_habitaciones rh
                 INNER JOIN reservaciones r
                     ON r.id = rh.reservacion_id AND r.hotel_id = rh.hotel_id
                 WHERE rh.hotel_id = ?
                   AND r.estado IN ('checked_in', 'checked_out', 'completada')
                   AND r.fecha_entrada <= CURDATE() - INTERVAL {$dias} DAY
                   AND r.fecha_salida > CURDATE() - INTERVAL {$dias} DAY",
                [$hotelId]
            );
            $historico[] = (int) (($stmt ? $stmt->fetch() : [])['ocupadas'] ?? 0);
        }

        $promedio = array_sum($historico) / 4;

        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $nombreDia = $dias[(int) date('w')];

        if ($promedio < 0.5) {
            // Sin historia comparable: usa la ocupacion cruda como senal suave.
            if ($hoy === 0) {
                return null;
            }
            return [
                'puntos' => (int) round($hoy * 100 / $total),
                'linea' => 'Hoy hay ' . $hoy . ' de ' . $total . ' habitaciones ocupadas.',
            ];
        }

        $razon = $hoy / $promedio;

        if ($razon >= 1.1) {
            $puntos = 100;
            $linea = 'Mejor ocupación que un ' . $nombreDia . ' normal tuyo.';
        } elseif ($razon >= 0.85) {
            $puntos = 85;
            $linea = 'Ocupación como un ' . $nombreDia . ' normal tuyo.';
        } elseif ($razon >= 0.6) {
            $puntos = 60;
            $linea = 'Un poco más flojo que un ' . $nombreDia . ' normal tuyo.';
        } else {
            $puntos = 35;
            $linea = 'Más flojo que un ' . $nombreDia . ' normal tuyo.';
        }

        return ['puntos' => $puntos, 'linea' => $linea];
    }

    /**
     * Calificacion reciente de huespedes (30 dias; si no hay, 90).
     */
    private function factorResenas(int $hotelId): ?array {
        foreach ([30, 90] as $dias) {
            $stmt = $this->db->query(
                "SELECT AVG(calificacion) AS promedio
                 FROM reputacion_encuestas
                 WHERE hotel_id = ? AND estado = 'respondida'
                   AND respondida_at >= CURDATE() - INTERVAL {$dias} DAY",
                [$hotelId]
            );
            $promedio = ($stmt ? $stmt->fetch() : [])['promedio'] ?? null;

            if ($promedio !== null) {
                $promedio = (float) $promedio;
                return [
                    'puntos' => (int) round(($promedio - 1) * 100 / 4),
                    'linea' => 'Los huéspedes te califican con ' . number_format($promedio, 1) . ' de 5.',
                ];
            }
        }

        return null;
    }
}
