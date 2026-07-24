<?php
/**
 * Copiloto IA (bloque copiloto_ia): endpoints JSON de las funciones de IA
 * sobre datos existentes. Interno (requiere sesion). Solo lectura.
 *
 * El gate del bloque copiloto_ia NO va aqui: lo resuelve el servicio, porque
 * los hoteles SIN el bloque tienen derecho a la prueba gratis (teaser). Lo que
 * SI se gatea por accion es el bloque fuente de los datos (reputacion/forecast).
 */

require_once __DIR__ . '/../services/CopilotoIaService.php';

class CopilotoIaController extends Controller {

    private const THROTTLE_MAX = 10;      // generaciones
    private const THROTTLE_VENTANA = 60;  // segundos

    /**
     * Permiso por accion (auditoria de accesos, 23 jul 2026). El gate del
     * bloque lo resuelve cada accion mas abajo (permite la prueba gratis), pero
     * el permiso se exige siempre. Aplicar una sugerencia de tarifa cambia
     * precios: pide 'tarifas.edit', no el de usar la IA.
     */
    private const PERMISOS = [
        'resena'        => 'copiloto_ia.usar',
        'analisis'      => 'copiloto_ia.usar',
        'tarifa'        => 'copiloto_ia.usar',
        'aplicarTarifa' => 'tarifas.edit',
    ];

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        $this->requirePermissionForAction(self::PERMISOS);

        return true;
    }

    /** Borrador de respuesta a una encuesta/resena (requiere bloque reputacion). */
    public function resenaAction() {
        $this->prepararJson('reputacion');

        $encuestaId = (int) $this->getPost('encuesta_id', 0);

        $this->responderCon(static function (CopilotoIaService $servicio, int $hotelId, bool $regenerar) use ($encuestaId) {
            return $servicio->borradorResena($hotelId, $encuestaId, $regenerar, user_id());
        });
    }

    /** Analisis mensual de encuestas (requiere bloque reputacion). */
    public function analisisAction() {
        $this->prepararJson('reputacion');

        $mes = (string) $this->getPost('mes', '');

        $this->responderCon(static function (CopilotoIaService $servicio, int $hotelId, bool $regenerar) use ($mes) {
            return $servicio->analisisEncuestas($hotelId, $mes !== '' ? $mes : null, $regenerar, user_id());
        });
    }

    /** Consejo de tarifa del dia (requiere bloque forecast). */
    public function tarifaAction() {
        $this->prepararJson('forecast');

        $this->responderCon(function (CopilotoIaService $servicio, int $hotelId, bool $regenerar) {
            $respuesta = $servicio->consejoTarifa($hotelId, $regenerar, user_id());
            return $this->conAccionesTarifa($respuesta, $hotelId);
        });
    }

    /**
     * Aplica UNA sugerencia del consejo de tarifa de hoy como IncrementoTarifa
     * estandar. Cadena obligatoria: la IA solo propuso (Fase 1), PHP ya valido
     * los limites duros al parsear, y AQUI un humano con permiso de tarifas
     * confirma. El cliente solo manda ventana y %: fechas, accion y motivo se
     * releen del consejo cacheado en el servidor (nada forjable).
     */
    public function aplicarTarifaAction() {
        $this->prepararJson('forecast');

        if (!function_exists('can') || !can('tarifas.edit')) {
            View::renderJSON(['success' => false, 'message' => 'No tienes permiso para editar tarifas.'], 403);
        }

        $hotelId = (int) obtenerHotelIdActualCompat();
        $ventana = (string) $this->getPost('ventana', '');
        $pct = $this->getPost('pct');

        try {
            $servicio = new CopilotoIaService();
            $fila = $servicio->consejoTarifaHoy($hotelId);
            if (!$fila) {
                View::renderJSON(['success' => false, 'message' => 'No hay un consejo de tarifa de hoy. Genera el consejo y vuelve a intentar.'], 422);
            }

            $parseado = CopilotoIaService::parsearSugerenciasTarifa((string) $fila['contenido']);
            $sugerencia = null;
            foreach ($parseado['sugerencias'] as $s) {
                if ($s['ventana'] === $ventana) {
                    $sugerencia = $s;
                    break;
                }
            }

            if (!$sugerencia || $sugerencia['accion'] === 'mantener') {
                View::renderJSON(['success' => false, 'message' => 'Esa sugerencia ya no esta vigente. Vuelve a generar el consejo.'], 422);
            }

            // % elegido por el humano: dentro de los limites duros, siempre.
            if (!is_numeric($pct)) {
                View::renderJSON(['success' => false, 'message' => 'Porcentaje invalido.'], 422);
            }
            $pct = round(abs((float) $pct), 1);
            if ($pct < 1 || $pct > CopilotoIaService::SUG_PCT_MAX) {
                View::renderJSON(['success' => false, 'message' => 'El porcentaje debe estar entre 1% y ' . CopilotoIaService::SUG_PCT_MAX . '%.'], 422);
            }

            $tarifaModel = new IncrementoTarifa();

            // Conflicto con un ajuste vigente: NO apilar en silencio.
            if ($tarifaModel->verificarSolapamiento($sugerencia['desde'], $sugerencia['hasta'], 'global', [], null, $hotelId)) {
                View::renderJSON([
                    'success' => false,
                    'conflicto' => true,
                    'conflictos' => $this->ajustesGlobalesEnRango($hotelId, $sugerencia['desde'], $sugerencia['hasta']),
                    'message' => 'Ya tienes un ajuste vigente que cubre esas fechas. Revisalo en Tarifas antes de aplicar otro.',
                ], 200);
            }

            // Foto del momento (Fase 5): ocupacion proyectada y tarifa promedio
            // de la ventana AL APLICAR, para comparar contra el resultado real
            // cuando la ventana pase. Si falla, el ajuste se crea igual.
            $snapOcupacion = null;
            $snapTarifa = null;
            try {
                $snap = (new ForecastService())->resultadoVentana($hotelId, $sugerencia['desde'], $sugerencia['hasta'], true);
                $snapOcupacion = $snap['ocupacion'];
                $snapTarifa = $snap['tarifa_promedio'];
            } catch (Throwable $e) {
                error_log('CopilotoIA: no se pudo tomar el snapshot del ajuste: ' . $e->getMessage());
            }

            $verbo = $sugerencia['accion'] === 'subir' ? 'subir' : 'bajar';
            $nombreAjuste = 'Copiloto: ' . $verbo . ' tarifa ' . rtrim(rtrim(number_format($pct, 1), '0'), '.') . '%';
            // Model::create devuelve el ID insertado (no la fila).
            $registroId = $tarifaModel->create([
                'hotel_id' => $hotelId,
                'nombre' => $nombreAjuste,
                'descripcion' => (string) $sugerencia['motivo'],
                'tipo_incremento' => 'porcentaje',
                'clase' => $sugerencia['accion'] === 'subir' ? 'incremento' : 'descuento',
                'valor_incremento' => $pct,
                'alcance' => 'global',
                'es_permanente' => 0,
                'fecha_inicio' => $sugerencia['desde'],
                'fecha_fin' => $sugerencia['hasta'],
                'prioridad' => 0,
                'activo' => 1,
                'usuario_id' => user_id(),
                'origen' => 'copiloto',
                'consejo_ref' => (int) $fila['id'],
                'aprobado_por' => user_id(),
                'snapshot_ocupacion' => $snapOcupacion,
                'snapshot_tarifa' => $snapTarifa,
            ]);

            if (!$registroId) {
                View::renderJSON(['success' => false, 'message' => 'No se pudo crear el ajuste. Intenta de nuevo.'], 500);
            }

            $this->registrarAccionTarifa('copiloto_aplicar_tarifa', sprintf(
                'Ajuste del Copiloto aplicado: %s %s%% del %s al %s (consejo #%d)',
                $verbo, $pct, $sugerencia['desde'], $sugerencia['hasta'], (int) $fila['id']
            ));

            View::renderJSON([
                'success' => true,
                'id' => (int) $registroId,
                'nombre' => $nombreAjuste,
                'url_tarifas' => url('configuracion/tarifas'),
                'message' => 'Ajuste creado. Las cotizaciones de esas fechas ya lo aplican.',
            ], 200);
        } catch (Throwable $e) {
            error_log('CopilotoIA: error al aplicar sugerencia de tarifa: ' . $e->getMessage());
            View::renderJSON(['success' => false, 'message' => 'Ocurrio un error al aplicar el ajuste. Intenta de nuevo.'], 500);
        }
    }

    // ───────────────────── Internos: acciones de tarifa ─────────────────────

    /**
     * Enriquece la respuesta del consejo de tarifa para la vista: si el
     * usuario puede editar tarifas, cada sugerencia accionable lleva un
     * preview REAL de precios (tarifa actual cotizada vs con el ajuste) para
     * 2-3 tipos de habitacion del hotel. Sin permiso: consejo sin botones.
     */
    private function conAccionesTarifa(array $respuesta, int $hotelId): array {
        if (empty($respuesta['success'])) {
            return $respuesta;
        }

        $respuesta['puede_aplicar'] = function_exists('can') && can('tarifas.edit');
        $respuesta['pct_max'] = CopilotoIaService::SUG_PCT_MAX;
        $respuesta['url_tarifas'] = url('configuracion/tarifas');

        if (!$respuesta['puede_aplicar'] || empty($respuesta['sugerencias'])) {
            return $respuesta;
        }

        try {
            $tipos = $this->tiposRepresentativos($hotelId);
            if (!$tipos) {
                return $respuesta;
            }

            $tarifaModel = new IncrementoTarifa();
            foreach ($respuesta['sugerencias'] as $i => $s) {
                if ($s['accion'] === 'mantener') {
                    continue;
                }
                $preview = [];
                foreach ($tipos as $t) {
                    $calculo = $tarifaModel->calcularPrecioConIncremento(0, $t['tipo'], (float) $t['precio'], $s['desde'], $hotelId);
                    $incrementado = (float) $calculo['precio_final'];
                    $descuento = $tarifaModel->calcularDescuentoPorTipo($t['tipo'], $incrementado, $s['desde'], $hotelId);
                    $actual = max(0, $incrementado - (float) $descuento['descuento_total']);

                    // Como cotiza el motor: subir agrega base*pct; bajar descuenta
                    // pct sobre el precio ya incrementado. delta_punto permite al
                    // cliente recalcular el preview al mover el % sin otra llamada.
                    $deltaPunto = $s['accion'] === 'subir'
                        ? (float) $t['precio'] / 100
                        : $incrementado / 100;

                    $preview[] = [
                        'tipo' => $t['tipo'],
                        'nombre' => $t['nombre'],
                        'actual' => round($actual, 2),
                        'delta_punto' => round($deltaPunto, 4),
                    ];
                }
                $respuesta['sugerencias'][$i]['preview'] = $preview;
            }
        } catch (Throwable $e) {
            error_log('CopilotoIA: error al armar preview de tarifas: ' . $e->getMessage());
        }

        return $respuesta;
    }

    /** Hasta 3 tipos de habitacion del hotel (los mas comunes) con precio base representativo. */
    private function tiposRepresentativos(int $hotelId): array {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT tipo, MIN(precio_base) AS precio, COUNT(*) AS n
             FROM habitaciones
             WHERE hotel_id = ? AND activa = 1 AND precio_base > 0
             GROUP BY tipo
             ORDER BY n DESC, precio ASC
             LIMIT 3",
            [$hotelId]
        );

        $tipos = [];
        foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
            $codigo = (string) $row['tipo'];
            $tipos[] = [
                'tipo' => $codigo,
                'nombre' => function_exists('get_tipo_habitacion')
                    ? get_tipo_habitacion($codigo)
                    : ucwords(str_replace('_', ' ', $codigo)),
                'precio' => (float) $row['precio'],
            ];
        }
        return $tipos;
    }

    /** Ajustes globales activos que tocan [$desde, $hasta], para explicar un conflicto. */
    private function ajustesGlobalesEnRango(int $hotelId, string $desde, string $hasta): array {
        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT nombre, clase, tipo_incremento, valor_incremento, fecha_inicio, fecha_fin, es_permanente
                 FROM incrementos_tarifas
                 WHERE hotel_id = ? AND activo = 1 AND alcance = 'global'
                   AND fecha_inicio <= ?
                   AND (fecha_fin >= ? OR fecha_fin IS NULL OR es_permanente = 1)",
                [$hotelId, $hasta, $desde]
            );

            $conflictos = [];
            foreach (($stmt ? $stmt->fetchAll() : []) as $r) {
                $signo = ($r['clase'] ?? 'incremento') === 'descuento' ? '-' : '+';
                $valor = $r['tipo_incremento'] === 'porcentaje'
                    ? $signo . rtrim(rtrim(number_format((float) $r['valor_incremento'], 1), '0'), '.') . '%'
                    : $signo . '$' . number_format((float) $r['valor_incremento'], 2);
                $conflictos[] = [
                    'nombre' => (string) $r['nombre'],
                    'valor' => $valor,
                    'desde' => (string) $r['fecha_inicio'],
                    'hasta' => $r['es_permanente'] ? null : ($r['fecha_fin'] !== null ? (string) $r['fecha_fin'] : null),
                ];
            }
            return $conflictos;
        } catch (Throwable $e) {
            return [];
        }
    }

    /** Bitacora en logs_acceso (mismo patron que TarifasController). */
    private function registrarAccionTarifa(string $tipo, string $descripcion): void {
        try {
            Database::getInstance()->query(
                "INSERT INTO logs_acceso (tipo, usuario_id, exitoso, ip, user_agent, detalles, created_at)
                 VALUES (?, ?, 1, ?, ?, ?, NOW())",
                [$tipo, user_id(), get_client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? '', $descripcion]
            );
        } catch (Throwable $e) {
            // la bitacora nunca frena la operacion
        }
    }

    // ───────────────────── Internos ─────────────────────

    /** Valida metodo, CSRF, bloque fuente y throttle. Corta con JSON si algo falla. */
    private function prepararJson(string $moduloFuente) {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Metodo no permitido.'], 405);
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module($moduloFuente);
        }

        $hotelId = (int) obtenerHotelIdActualCompat();
        if (!$this->permitirSolicitud($hotelId)) {
            View::renderJSON(['success' => false, 'message' => 'Vas muy rapido. Espera un momento e intenta de nuevo.'], 429);
        }
    }

    private function responderCon(callable $fn) {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $regenerar = (string) $this->getPost('regenerar', '0') === '1';

        try {
            $servicio = new CopilotoIaService();
            $resultado = $fn($servicio, $hotelId, $regenerar);
        } catch (Throwable $e) {
            error_log('CopilotoIA: error en endpoint: ' . $e->getMessage());
            View::renderJSON(['success' => false, 'message' => 'Ocurrio un error. Intenta de nuevo.'], 500);
            return;
        }

        View::renderJSON($resultado, 200);
    }

    /** Rate-limit por usuario reutilizando login_intentos (prefijo copiloto_ia|). */
    private function permitirSolicitud(int $hotelId) {
        $uid = (int) user_id();
        $clave = hash('sha256', 'copiloto_ia|' . $uid . '|' . $hotelId);

        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO login_intentos (clave, ip, nombre_usuario, hotel_slug, intentos, ultimo_intento, created_at)
                 VALUES (?, ?, 'copiloto_ia', ?, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    intentos = IF(ultimo_intento < NOW() - INTERVAL " . self::THROTTLE_VENTANA . " SECOND, 1, intentos + 1),
                    ultimo_intento = NOW()",
                [$clave, substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), 'hotel_' . $hotelId]
            );

            $stmt = $db->query("SELECT intentos FROM login_intentos WHERE clave = ? LIMIT 1", [$clave]);
            $row = $stmt ? $stmt->fetch() : null;

            return (int) ($row['intentos'] ?? 0) <= self::THROTTLE_MAX;
        } catch (Throwable $e) {
            return true;
        }
    }
}
