<?php
/**
 * Modo Dueno (bloque modo_dueno): resumen remoto del hotel, 100% LECTURA.
 *
 * Pensado para el dueno que NO opera el hotel: abre el telefono y entiende
 * en segundos como va su negocio. Sin sidebar, sin tabs: la vista ES la app
 * para este rol. Cero endpoints de escritura en este controlador.
 *
 * Gates: sesion + contexto de hotel + bloque modo_dueno + permiso dueno.view.
 * Cada tarjeta se gatea ademas por su propio permiso de lectura (caja.view,
 * habitaciones.view, reservaciones.view, guardian.view, reputacion.view) y
 * por el modulo del hotel: si falta alguno, ese bloque llega null y la vista
 * simplemente no lo pinta. La vista se arma con cualquier subconjunto.
 *
 * Todos los textos se redactan AQUI en lenguaje hablado (cero jerga) para
 * que la vista y el refresco JSON muestren exactamente lo mismo.
 */

class DuenoController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('modo_dueno');
        }

        $this->requirePermission('dueno.view');

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();

        View::renderTemplate('dueno/index', [
            'title' => 'Mi hotel - ' . (current_hotel_nombre() ?: 'Medisoft'),
            'resumen' => $this->armarResumen($hotelId),
        ]);
    }

    /**
     * Datos frescos en JSON para el boton "Actualizar" de la vista.
     * GET y solo lectura; mismos textos que el render inicial.
     */
    public function datosAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();

        View::renderJSON([
            'success' => true,
            'resumen' => $this->armarResumen($hotelId),
        ]);
    }

    /* ------------------------------------------------------------------
     * Resumen del dia (una clave por tarjeta; null = tarjeta ausente)
     * ---------------------------------------------------------------- */

    private function armarResumen(int $hotelId): array {
        $dinero = $this->bloqueDinero($hotelId);
        $hotel = $this->bloqueHotel($hotelId);
        $guardian = $this->bloqueGuardian($hotelId);
        $resenas = $this->bloqueResenas($hotelId);

        return [
            'generado_en' => time(),
            'saludo' => $this->saludo(),
            'hotel_nombre' => (string) (current_hotel_nombre() ?: ''),
            'semaforo' => $this->bloqueSemaforo($dinero, $guardian),
            'dinero' => $dinero,
            'hotel' => $hotel,
            'guardian' => $guardian,
            'resenas' => $resenas,
        ];
    }

    private function saludo(): string {
        $hora = (int) date('G');

        if ($hora < 12) {
            return 'Buenos días';
        }

        return $hora < 19 ? 'Buenas tardes' : 'Buenas noches';
    }

    private function moneda($valor): string {
        $valor = round((float) $valor, 2);
        $decimales = (fmod($valor, 1.0) == 0.0) ? 0 : 2;

        return '$' . number_format($valor, $decimales, '.', ',');
    }

    /**
     * "¿Cuánto ha entrado hoy?" — ingresos/gastos del dia + estado de caja.
     * Mismo criterio de reversos que el dashboard: las devoluciones restan
     * del dinero que entro y no cuentan como gasto del hotel.
     */
    private function bloqueDinero(int $hotelId): ?array {
        if (!can('caja.view') || !hotel_has_module('caja', $hotelId)) {
            return null;
        }

        try {
            $db = Database::getInstance();

            $condicionReverso = "(
                mc.tipo = 'gasto'
                AND (
                    LOWER(COALESCE(mc.categoria, '')) LIKE 'devoluc%'
                    OR LOWER(COALESCE(mc.categoria, '')) LIKE 'reverso anticipo%'
                    OR LOWER(COALESCE(mc.categoria, '')) LIKE 'reverso de anticipo%'
                    OR LOWER(COALESCE(mc.categoria, '')) LIKE 'reversion cobro cxc%'
                    OR LOWER(COALESCE(mc.descripcion, '')) LIKE 'reverso de anticipo%'
                    OR LOWER(COALESCE(mc.descripcion, '')) LIKE 'reversion cobro cxc%'
                )
            )";

            $stmt = $db->query(
                "SELECT
                    SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END) AS ingresos_brutos,
                    SUM(CASE WHEN {$condicionReverso} THEN mc.monto ELSE 0 END) AS reversos,
                    SUM(CASE WHEN mc.tipo IN ('gasto', 'egreso') AND NOT {$condicionReverso} THEN mc.monto ELSE 0 END) AS gastos
                 FROM movimientos_caja mc
                 WHERE mc.hotel_id = ?
                   AND DATE(mc.created_at) = CURDATE()",
                [$hotelId]
            );
            $fila = $stmt ? $stmt->fetch() : null;

            $ingresos = max(0, (float) ($fila['ingresos_brutos'] ?? 0) - (float) ($fila['reversos'] ?? 0));
            $gastos = (float) ($fila['gastos'] ?? 0);

            // Estado de caja en una linea, sin jerga de "cortes".
            $cajaModel = new Caja();
            $corte = $cajaModel->obtenerCorteActual();
            $cajaAbierta = !empty($corte);

            if ($cajaAbierta) {
                $apertura = strtotime((string) ($corte['fecha_apertura'] ?? ''));
                if ($apertura && date('Y-m-d', $apertura) === date('Y-m-d')) {
                    $cajaLinea = 'Caja abierta desde las ' . date('G:i', $apertura);
                } elseif ($apertura) {
                    $cajaLinea = 'Caja abierta desde ayer';
                } else {
                    $cajaLinea = 'Caja abierta';
                }
            } else {
                $cajaLinea = ($ingresos > 0 || $gastos > 0)
                    ? 'La caja ya cerró por hoy'
                    : 'La caja aún no se abre hoy';
            }

            return [
                'ingresos' => $this->moneda($ingresos),
                'gastos' => $this->moneda($gastos),
                'gastos_linea' => 'Gastos del día: ' . $this->moneda($gastos),
                'caja_linea' => $cajaLinea,
                'caja_abierta' => $cajaAbierta,
            ];
        } catch (Throwable $e) {
            error_log('ModoDueno dinero: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * "¿Cómo está el hotel?" — ocupacion en lenguaje natural + llegadas y
     * salidas de hoy con nombres de pila.
     */
    private function bloqueHotel(int $hotelId): ?array {
        if (!can('habitaciones.view')) {
            return null;
        }

        try {
            $db = Database::getInstance();

            $stmt = $db->query(
                "SELECT COUNT(*) AS total,
                        SUM(estado = 'ocupada') AS ocupadas
                 FROM habitaciones
                 WHERE hotel_id = ? AND activa = 1",
                [$hotelId]
            );
            $fila = $stmt ? $stmt->fetch() : null;
            $total = (int) ($fila['total'] ?? 0);
            $ocupadas = (int) ($fila['ocupadas'] ?? 0);
            $pct = $total > 0 ? (int) round($ocupadas * 100 / $total) : 0;

            $bloque = [
                'ocupacion_texto' => $ocupadas . ' de ' . $total . ' habitaciones ocupadas',
                'ocupacion_pct' => $pct,
                'llegadas_linea' => null,
                'salidas_linea' => null,
            ];

            if (can('reservaciones.view')) {
                $bloque['llegadas_linea'] = $this->lineaPersonas(
                    $db,
                    $hotelId,
                    "r.fecha_entrada = CURDATE() AND r.estado IN ('confirmada', 'checked_in')",
                    ['llega hoy', 'llegan hoy'],
                    'Hoy no llegan huéspedes'
                );
                $bloque['salidas_linea'] = $this->lineaPersonas(
                    $db,
                    $hotelId,
                    "r.fecha_salida = CURDATE() AND r.estado IN ('checked_in', 'checked_out')",
                    ['se va hoy', 'se van hoy'],
                    'Hoy no se va nadie'
                );
            }

            return $bloque;
        } catch (Throwable $e) {
            error_log('ModoDueno hotel: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Frase natural con nombres de pila: "Llegan hoy: Roberto, Ana y Luis".
     */
    private function lineaPersonas($db, int $hotelId, string $condicion, array $verbo, string $vacio): string {
        $stmt = $db->query(
            "SELECT h.nombre_completo
             FROM reservaciones r
             INNER JOIN huespedes h ON h.id = r.huesped_id
             WHERE r.hotel_id = ? AND {$condicion}
             ORDER BY h.nombre_completo
             LIMIT 12",
            [$hotelId]
        );

        $nombres = [];
        foreach (($stmt ? $stmt->fetchAll() : []) as $fila) {
            $pila = trim(explode(' ', trim((string) $fila['nombre_completo']))[0]);
            if ($pila !== '') {
                $nombres[] = $pila;
            }
        }

        $cuantos = count($nombres);

        if ($cuantos === 0) {
            return $vacio;
        }

        $mostrar = array_slice($nombres, 0, 4);
        $resto = $cuantos - count($mostrar);
        $lista = count($mostrar) > 1
            ? implode(', ', array_slice($mostrar, 0, -1)) . ' y ' . end($mostrar)
            : $mostrar[0];

        if ($resto > 0) {
            $lista .= ' y ' . $resto . ' más';
            return ucfirst($verbo[1]) . ': ' . $lista;
        }

        return ($cuantos === 1 ? ucfirst($verbo[0]) : ucfirst($verbo[1])) . ': ' . $lista;
    }

    /**
     * "¿Todo en orden?" — estado del Guardian financiero (si el hotel tiene
     * el bloque y el usuario permiso). Cache corto: el motor revisa 30 dias.
     */
    private function bloqueGuardian(int $hotelId): ?array {
        if (!can('guardian.view') || !hotel_has_module('ia_ejecutiva', $hotelId)) {
            return null;
        }

        if (!class_exists('GuardianPatrones')) {
            $ruta = APP_PATH . '/models/GuardianPatrones.php';
            if (!is_readable($ruta)) {
                return null;
            }
            require_once $ruta;
        }

        try {
            $consultar = static function () use ($hotelId) {
                $guardian = new GuardianPatrones();
                $reporte = $guardian->reporteReadOnlyPorHotel($hotelId);
                return [
                    'alta' => (int) ($reporte['totales']['alta'] ?? 0),
                    'media' => (int) ($reporte['totales']['media'] ?? 0),
                ];
            };

            $totales = function_exists('ms_cache_remember')
                ? ms_cache_remember('dueno_guardian_' . $hotelId, 300, $consultar)
                : $consultar();

            if (!is_array($totales)) {
                return null;
            }

            $temas = $totales['alta'] + $totales['media'];
            $ok = $temas === 0;

            return [
                'ok' => $ok,
                'texto' => $ok
                    ? 'Todo cuadró'
                    : ($temas === 1 ? '1 tema a revisar' : $temas . ' temas a revisar'),
                'detalle' => $ok
                    ? 'Revisamos los movimientos del último mes y no vimos nada raro.'
                    : 'Vale la pena echarle un ojo con calma.',
                'url' => $ok ? null : url('ia/vigilancia-financiera'),
            ];
        } catch (Throwable $e) {
            error_log('ModoDueno guardian: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * "¿Qué dicen los huéspedes?" — calificacion promedio (90 dias) y la
     * opinion mas reciente en una linea.
     */
    private function bloqueResenas(int $hotelId): ?array {
        if (!can('reputacion.view') || !hotel_has_module('reputacion', $hotelId)) {
            return null;
        }

        try {
            require_once APP_PATH . '/services/ReputacionService.php';
            $servicio = new ReputacionService();
            $kpis = $servicio->kpis($hotelId);
            $promedio = $kpis['promedio'];

            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT e.calificacion, e.comentario, h.nombre_completo
                 FROM reputacion_encuestas e
                 INNER JOIN reservaciones r ON r.id = e.reservacion_id AND r.hotel_id = e.hotel_id
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE e.hotel_id = ? AND e.estado = 'respondida'
                 ORDER BY e.respondida_at DESC
                 LIMIT 1",
                [$hotelId]
            );
            $ultima = $stmt ? $stmt->fetch() : null;

            $reciente = null;
            if ($ultima) {
                $pila = trim(explode(' ', trim((string) $ultima['nombre_completo']))[0]);
                $comentario = trim((string) ($ultima['comentario'] ?? ''));
                if (function_exists('mb_strimwidth') && $comentario !== '') {
                    $comentario = mb_strimwidth($comentario, 0, 90, '…', 'UTF-8');
                }
                $reciente = $comentario !== ''
                    ? '“' . $comentario . '” — ' . ($pila !== '' ? $pila : 'un huésped')
                    : ($pila !== '' ? $pila : 'Un huésped') . ' calificó con ' . (int) $ultima['calificacion'] . ' de 5';
            }

            return [
                'promedio' => $promedio !== null ? number_format((float) $promedio, 1) : null,
                'promedio_linea' => $promedio !== null
                    ? 'Calificación de ' . number_format((float) $promedio, 1) . ' de 5'
                    : 'Aún no hay calificaciones',
                'estrellas' => $promedio !== null ? (int) round((float) $promedio) : 0,
                'reciente' => $reciente ?: 'Todavía no hay opiniones escritas.',
            ];
        } catch (Throwable $e) {
            error_log('ModoDueno resenas: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * "¿Cómo va el día?" — placeholder simple de Fase 2: verde si la caja
     * esta trabajando y el Guardian no vio nada; ambar si hay pendientes.
     * La Fase 3 lo reemplaza por el score determinista del dia.
     */
    private function bloqueSemaforo(?array $dinero, ?array $guardian): array {
        $temasGuardian = $guardian !== null && empty($guardian['ok']);
        $cajaSinAbrir = $dinero !== null && empty($dinero['caja_abierta'])
            && ($dinero['caja_linea'] ?? '') === 'La caja aún no se abre hoy';

        if ($temasGuardian) {
            return [
                'estado' => 'ambar',
                'etiqueta' => 'Hay pendientes',
                'detalle' => 'El vigilante financiero encontró temas para revisar con calma.',
            ];
        }

        if ($cajaSinAbrir) {
            return [
                'estado' => 'ambar',
                'etiqueta' => 'Día tranquilo',
                'detalle' => 'La caja aún no se abre hoy.',
            ];
        }

        return [
            'estado' => 'verde',
            'etiqueta' => 'El día va bien',
            'detalle' => 'La operación marcha con normalidad.',
        ];
    }
}
