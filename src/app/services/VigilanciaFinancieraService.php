<?php

require_once __DIR__ . '/../models/ConciliacionFinanciera.php';
require_once __DIR__ . '/../models/GuardianPatrones.php';

/**
 * VigilanciaFinancieraService (bloque premium: vigilancia_financiera)
 *
 * Capa de analisis forense sobre la conciliacion financiera determinista. NO
 * reinventa la deteccion: ConciliacionFinanciera::reporteReadOnlyPorHotel ya
 * corre las reglas SQL read-only (doble reversion, cobro sin caja, referencias
 * duplicadas, etc.). Este servicio toma ese reporte y lo eleva a un informe
 * accionable para el dueno: prioriza por riesgo, distingue error honesto de
 * posible fuga, correlaciona hallazgos y recomienda la accion concreta.
 *
 * ESCALERA DE COSTO (gasta segun lo que hay que analizar):
 *  - Nivel 1 (plantilla, $0): si la conciliacion sale limpia (0 errores y 0
 *    warnings) el informe verde se arma con plantilla local, SIN llamar al API.
 *    La mayoria de los dias caen aqui.
 *  - Nivel 2 (claude-opus-4-8): hay hallazgos -> Opus interpreta el resumen
 *    agregado. Suficiente para la v1 (el payload es compacto) y a mitad de
 *    precio del modelo mayor.
 *  - Nivel 3 (claude-fable-5, ACTIVO desde el Guardian v2): cuando
 *    GuardianPatrones detecta patrones de comportamiento por usuario, el
 *    prompt se enriquece con los movimientos crudos detras de las top-alertas
 *    (montos, referencias, timestamps, usuario) y escala a Fable 5
 *    (MODELO_FORENSE_V2). Notas del API de Fable 5 (vigentes, NO "corregir"):
 *    OMITIR el campo 'thinking' (siempre activo; disabled=400), sin
 *    temperature/top_p/top_k, effort via output_config, fallback opt-in a
 *    opus-4-8 (header anthropic-beta: server-side-fallback-2026-06-01 +
 *    fallbacks:[{model:...}]), timeout curl 300s, requiere retencion 30 dias.
 *    La respuesta puede traer stop_reason 'refusal' (clasificadores) y bloques
 *    'fallback' en content: solo se leen los bloques de tipo text.
 *
 *    VISIBILIDAD: el informe v2 nombra usuarios. Todo lo que lo muestre
 *    (vista, push, Copiloto) debe gatearse con el permiso guardian.view.
 *
 * Integracion: POST https://api.anthropic.com/v1/messages (curl REST, sin SDK;
 * mismo patron que IaEjecutivaService/CopilotoIaService). Notas del API de
 * Opus 4.8 que NO deben "corregirse": thinking adaptativo via
 * {"type":"adaptive"} (budget_tokens ya no existe); NO enviar
 * temperature/top_p/top_k (responde 400).
 * Llave global en env ANTHROPIC_API_KEY (Medisoft paga el API y revende el
 * bloque por hotel). Resultado cacheado en ia_resumenes (tipo
 * 'vigilancia_financiera') por hotel/fecha para no re-pagar tokens.
 */
class VigilanciaFinancieraService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const MODELO = 'claude-opus-4-8';
    /** Nivel 3: forense con movimientos crudos y patrones por usuario. */
    private const MODELO_FORENSE_V2 = 'claude-fable-5';
    private const MODELO_PLANTILLA = 'plantilla';
    private const MAX_TOKENS = 4096;
    private const TIPO_CACHE = 'vigilancia_financiera';

    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function configurado(): bool
    {
        return trim((string) (getenv('ANTHROPIC_API_KEY') ?: '')) !== '';
    }

    /**
     * Informe forense del hotel. Usa el cache del dia salvo $regenerar.
     * $filtros se pasan tal cual a la conciliacion (fecha_desde/fecha_hasta,
     * tipo, severidad). Devuelve:
     *   ['success', 'informe', 'hallazgos_deterministas', 'generado_en',
     *    'desde_cache', 'modelo', 'message'].
     * 'modelo' = 'plantilla' cuando el informe verde se armo sin IA (nivel 1).
     */
    public function analizar(int $hotelId, array $filtros = [], bool $regenerar = false, ?int $usuarioId = null): array
    {
        if ($hotelId <= 0) {
            return ['success' => false, 'message' => 'Hotel invalido para la vigilancia financiera.'];
        }

        $fecha = date('Y-m-d');

        if (!$regenerar) {
            $cacheado = $this->leerCache($hotelId, $fecha);
            if ($cacheado) {
                return [
                    'success' => true,
                    'informe' => (string) $cacheado['contenido'],
                    'generado_en' => (string) $cacheado['updated_at'],
                    'modelo' => (string) ($cacheado['modelo'] ?? self::MODELO),
                    'desde_cache' => true,
                ];
            }
        }

        // Datos reales: conciliacion determinista read-only (misma fuente que la
        // pantalla de Conciliacion). Traemos todas las alertas sin paginar.
        try {
            $modelo = new ConciliacionFinanciera();
            $reporte = $modelo->reporteReadOnlyPorHotel($hotelId, array_merge($filtros, [
                'page' => 1,
                'limit' => 50,
            ]));
        } catch (Throwable $e) {
            error_log('Vigilancia financiera: error al generar la conciliacion: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudieron obtener los datos financieros para el analisis.'];
        }

        if (empty($reporte['schema_ok'])) {
            return [
                'success' => false,
                'message' => 'El esquema financiero esta incompleto en esta base; la vigilancia requiere las tablas de CxC, CxP y Caja.',
            ];
        }

        $totales = (array) ($reporte['totales_alertas'] ?? []);
        $hallazgos = (int) ($totales['hallazgos'] ?? 0);

        // Patrones de comportamiento por usuario (Guardian, determinista y $0).
        // Si el motor de patrones falla, se degrada al comportamiento v1 sin
        // tumbar el informe de integridad.
        $patrones = null;
        try {
            $patrones = (new GuardianPatrones())->reporteReadOnlyPorHotel($hotelId);
        } catch (Throwable $e) {
            error_log('Vigilancia financiera: error en GuardianPatrones: ' . $e->getMessage());
        }
        $patronesTotales = (array) ($patrones['totales'] ?? ['alta' => 0, 'media' => 0, 'hallazgos' => 0]);
        $hayPatrones = ((int) ($patronesTotales['alta'] ?? 0) + (int) ($patronesTotales['media'] ?? 0)) > 0;

        // ── Nivel 1: integridad Y patrones limpios -> plantilla verde, $0 ──
        if ((int) ($totales['error'] ?? 0) === 0 && (int) ($totales['warning'] ?? 0) === 0 && !$hayPatrones) {
            $respuesta = [
                'texto' => $this->informeVerdePlantilla($reporte, $patrones),
                'modelo' => self::MODELO_PLANTILLA,
                'tokens_entrada' => 0,
                'tokens_salida' => 0,
            ];
            $this->guardarCache($hotelId, $fecha, $respuesta, $usuarioId);

            return [
                'success' => true,
                'informe' => (string) $respuesta['texto'],
                'hallazgos_deterministas' => 0,
                'hallazgos_patrones' => 0,
                'nivel' => 1,
                'generado_en' => date('Y-m-d H:i:s'),
                'modelo' => self::MODELO_PLANTILLA,
                'desde_cache' => false,
            ];
        }

        // ── Nivel 2/3: hay hallazgos -> el analista interpreta ──
        if (!$this->configurado()) {
            return [
                'success' => false,
                'message' => 'La vigilancia financiera no esta configurada en el servidor (falta ANTHROPIC_API_KEY). Contacta a Medisoft.',
            ];
        }

        if ($hayPatrones && $patrones !== null) {
            // Nivel 3: patrones por usuario -> forense (Fable 5) con los
            // movimientos crudos detras de las top-alertas.
            $nivel = 3;
            $respuesta = $this->llamarClaude(
                $this->promptSistemaForense(),
                $this->promptUsuarioForense($fecha, $reporte, $patrones),
                self::MODELO_FORENSE_V2
            );
        } else {
            // Nivel 2: solo integridad agregada -> Opus 4.8 (v1).
            $nivel = 2;
            $respuesta = $this->llamarClaude($this->promptSistema(), $this->promptUsuario($fecha, $reporte));
        }
        if (!$respuesta['success']) {
            return $respuesta;
        }

        $this->guardarCache($hotelId, $fecha, $respuesta, $usuarioId);

        return [
            'success' => true,
            'informe' => (string) $respuesta['texto'],
            'hallazgos_deterministas' => $hallazgos,
            'hallazgos_patrones' => (int) ($patronesTotales['hallazgos'] ?? 0),
            'nivel' => $nivel,
            'generado_en' => date('Y-m-d H:i:s'),
            'modelo' => (string) ($respuesta['modelo'] ?? self::MODELO),
            'desde_cache' => false,
        ];
    }

    // ───────────────────────── Nivel 1: plantilla verde ─────────────────────────

    /** Informe verde sin IA: todo cuadro, se narra con los numeros del resumen. */
    private function informeVerdePlantilla(array $reporte, ?array $patrones = null): string
    {
        $r = (array) ($reporte['resumen'] ?? []);
        $cxc = (array) ($r['cxc'] ?? []);
        $cxp = (array) ($r['cxp'] ?? []);
        $caja = (array) ($r['caja'] ?? []);
        $verificaciones = count((array) ($reporte['alertas'] ?? []));

        $monto = static function ($v): string {
            return '$' . number_format((float) $v, 2);
        };

        $lineas = [];
        $lineas[] = '🟢 **Verde** — Tus libros cuadran: las ' . $verificaciones
            . ' verificaciones automaticas de Cuentas por Cobrar, Cuentas por Pagar y Caja pasaron sin ningun hallazgo.';
        $lineas[] = '';
        $lineas[] = '**Resumen de tus libros**';
        $lineas[] = '- Cuentas por cobrar: ' . (int) ($cxc['cuentas_abiertas'] ?? 0)
            . ' abiertas con saldo pendiente de ' . $monto($cxc['saldo_pendiente'] ?? 0)
            . ' y ' . (int) ($cxc['cobros'] ?? 0) . ' cobros registrados por ' . $monto($cxc['cobros_importe'] ?? 0) . '.';
        $lineas[] = '- Cuentas por pagar: ' . (int) ($cxp['cuentas_abiertas'] ?? 0)
            . ' abiertas con saldo pendiente de ' . $monto($cxp['saldo_pendiente'] ?? 0)
            . ' y ' . (int) ($cxp['pagos'] ?? 0) . ' pagos a proveedor por ' . $monto($cxp['pagos_importe'] ?? 0) . '.';
        $lineas[] = '- Caja: ' . ((int) ($caja['movimientos_cxc'] ?? 0) + (int) ($caja['movimientos_cxp'] ?? 0))
            . ' movimientos financieros conciliados contra sus cortes.';

        if ($patrones !== null) {
            $reglas = count((array) ($patrones['alertas'] ?? []));
            $vol = (array) ($patrones['volumen'] ?? []);
            $lineas[] = '- Guardian: ' . $reglas . ' patrones de comportamiento vigilados sobre '
                . ((int) ($vol['movimientos'] ?? 0) + (int) ($vol['reservaciones'] ?? 0))
                . ' operaciones de los ultimos ' . (int) ($patrones['ventana']['dias'] ?? 30)
                . ' dias, sin nada que revisar.';
        }

        $lineas[] = '';
        $lineas[] = 'No se requiere ninguna accion hoy. La proxima revision volvera a verificar todo automaticamente.';

        return implode("\n", $lineas);
    }

    // ───────────────────────── Prompts (nivel 2) ─────────────────────────

    private function promptSistema(): string
    {
        return 'Eres un auditor financiero forense que asesora al dueno de un hotel pequeno o mediano '
            . 'en Mexico. Recibes un reporte de conciliacion generado por reglas automaticas (read-only) '
            . 'con un resumen de los libros (Cuentas por Cobrar, Cuentas por Pagar y Caja) y una lista de '
            . 'alertas ya detectadas, cada una con codigo, severidad (error/warning/ok), conteo de casos, '
            . 'una descripcion tecnica y un destino en el sistema para revisarla. El dueno no es tecnico y '
            . "tiene poco tiempo: escribe en espanol claro, directo y prudente.\n\n"
            . "Tu trabajo NO es repetir la lista tecnica, sino interpretarla:\n"
            . "1. Empieza con un semaforo de riesgo en una sola linea: 🟡 Amarillo (revisar) o "
            . "🔴 Rojo (atender hoy), con una frase que lo justifique.\n"
            . "2. Seccion \"Hallazgos por atender\" (Markdown, ordenados de mayor a menor riesgo, maximo 6). "
            . "Por cada uno: que significa en palabras del dueno; si parece un error honesto de captura o un "
            . "patron que podria indicar una fuga de dinero, y por que; el riesgo o impacto; y la accion "
            . "concreta con donde revisarlo en el sistema (usa el campo destino).\n"
            . "3. Seccion \"Patrones\" (opcional): correlaciona hallazgos entre libros cuando varias alertas "
            . "juntas cuenten una misma historia (por ejemplo, reversiones y referencias duplicadas que "
            . "coinciden). Si no hay correlacion relevante, omite la seccion.\n"
            . "4. Cierra con el proximo paso mas importante, en una oracion.\n\n"
            . "Reglas estrictas: usa SOLO los datos del reporte, nunca inventes cifras, conteos ni nombres. "
            . "Escribe montos como \$1,234.56. Una alerta con estado ok o conteo 0 significa que ESO esta "
            . "bien: no la conviertas en problema. El reporte es agregado y no identifica personas: señala "
            . "donde revisar, nunca acuses a un empleado por nombre ni asumas mala fe; distingue siempre "
            . "'posible' de 'confirmado'. Prioriza severidad error sobre warning. No menciones que usas un "
            . "modelo externo ni describas el formato JSON.";
    }

    private function promptUsuario(string $fecha, array $reporte): string
    {
        // Compactamos a lo util para el analisis: resumen de libros + alertas con
        // conteo (quitamos la paginacion y campos de UI que no aportan al modelo).
        $alertas = [];
        foreach ((array) ($reporte['alertas'] ?? []) as $a) {
            $alertas[] = [
                'tipo' => $a['tipo'] ?? '',
                'severidad' => $a['severidad'] ?? '',
                'estado' => $a['estado'] ?? '',
                'codigo' => $a['codigo'] ?? '',
                'titulo' => $a['titulo'] ?? '',
                'conteo' => (int) ($a['conteo'] ?? 0),
                'detalle' => $a['detalle'] ?? '',
                'destino' => $a['destino'] ?? '',
            ];
        }

        $payload = [
            'consultado_en' => $reporte['consultado_en'] ?? $fecha,
            'filtros' => $reporte['filtros'] ?? [],
            'resumen_libros' => $reporte['resumen'] ?? [],
            'totales_alertas' => $reporte['totales_alertas'] ?? [],
            'alertas' => $alertas,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        // Techo defensivo (el reporte es compacto, pero por si acaso).
        if ($json !== false && strlen($json) > 180000) {
            $json = substr($json, 0, 180000);
        }

        return "Reporte de conciliacion financiera del hotel al {$fecha}:\n\n" . ($json ?: '{}');
    }

    // ───────────────────────── Prompts (nivel 3, forense) ─────────────────────────

    private function promptSistemaForense(): string
    {
        return 'Eres un auditor financiero forense que asesora al dueno de un hotel pequeno o mediano '
            . 'en Mexico. Recibes dos bloques de datos generados por reglas automaticas read-only: '
            . '(1) la conciliacion de integridad de los libros (CxC, CxP y Caja) y (2) los patrones de '
            . 'comportamiento por usuario del Guardian, cada uno con los movimientos crudos que lo '
            . "respaldan (montos, fechas, referencias) y el contexto estadistico del hotel.\n\n"
            . "El dueno no es tecnico y este informe puede afectar relaciones laborales reales, asi que "
            . "el tono importa tanto como el analisis:\n"
            . "- JAMAS uses palabras como robo, fraude, culpable, ladron o hormiga. Habla de 'patron a "
            . "revisar' y 'conviene confirmarlo con el equipo'.\n"
            . "- Nunca afirmes mala fe: la estadistica señala donde mirar, no dictamina. Distingue "
            . "siempre 'posible' de 'confirmado'.\n"
            . "- En cada hallazgo clasifica explicitamente si el patron PARECE UN ERROR HONESTO (captura, "
            . "proceso mal entendido, turno compartido) o si es UN PATRON QUE CONVIENE REVISAR con calma, "
            . "y explica en una frase por que lo clasificas asi (frecuencia, montos, combinacion de señales).\n\n"
            . "Estructura del informe (Markdown):\n"
            . "1. Una linea de semaforo: 🟡 Amarillo (revisar esta semana) o 🔴 Rojo (revisar hoy), con "
            . "la razon en una frase.\n"
            . "2. Seccion \"Hallazgos por riesgo\" ordenada de mayor a menor riesgo (maximo 6). Por cada "
            . "hallazgo: que se detecto y a quien involucra (usa el nombre solo como referencia de con "
            . "quien confirmar); por que es atipico VS el patron del hotel (usa la mediana o el umbral que "
            . "viene en los datos); si parece error honesto o patron a revisar y por que; y UNA sola "
            . "accion concreta recomendada (ej. 'revisa el corte #123 del martes con Ana', no una lista).\n"
            . "3. Seccion \"Historia completa\" (opcional): cuando varias señales del mismo usuario o del "
            . "mismo dia cuenten una sola historia, narrala en 2-3 frases. Si no la hay, omite la seccion.\n"
            . "4. Cierra con el proximo paso mas importante en una oracion.\n\n"
            . "Reglas estrictas: usa SOLO los datos recibidos, jamas inventes cifras, fechas ni nombres. "
            . "Escribe montos como \$1,234.56. Una regla en 'ok' o con conteo 0 esta bien: no la conviertas "
            . "en problema. Prioriza severidad alta sobre media y error sobre warning. Si el volumen del "
            . "hotel es bajo, dilo como atenuante. No menciones que usas un modelo externo ni el formato "
            . 'de los datos.';
    }

    private function promptUsuarioForense(string $fecha, array $reporte, array $patrones): string
    {
        // Integridad compacta (como el nivel 2).
        $alertasIntegridad = [];
        foreach ((array) ($reporte['alertas'] ?? []) as $a) {
            $alertasIntegridad[] = [
                'tipo' => $a['tipo'] ?? '',
                'severidad' => $a['severidad'] ?? '',
                'codigo' => $a['codigo'] ?? '',
                'titulo' => $a['titulo'] ?? '',
                'conteo' => (int) ($a['conteo'] ?? 0),
                'detalle' => $a['detalle'] ?? '',
            ];
        }

        // Patrones: solo las reglas con hallazgo, con sus usuarios y los casos
        // crudos (ya vienen limitados a 10 por usuario desde GuardianPatrones).
        $alertasPatrones = [];
        foreach ((array) ($patrones['alertas'] ?? []) as $a) {
            if ((int) ($a['conteo'] ?? 0) === 0) {
                continue;
            }
            $alertasPatrones[] = [
                'codigo' => $a['codigo'] ?? '',
                'titulo' => $a['titulo'] ?? '',
                'severidad' => $a['severidad'] ?? '',
                'detalle' => $a['detalle'] ?? '',
                'usuarios' => $a['usuarios'] ?? [],
            ];
        }

        $payload = [
            'consultado_en' => $reporte['consultado_en'] ?? $fecha,
            'resumen_libros' => $reporte['resumen'] ?? [],
            'integridad' => [
                'totales' => $reporte['totales_alertas'] ?? [],
                'alertas' => $alertasIntegridad,
            ],
            'patrones_guardian' => [
                'ventana' => $patrones['ventana'] ?? [],
                'volumen' => $patrones['volumen'] ?? [],
                'umbrales' => $patrones['config'] ?? [],
                'totales' => $patrones['totales'] ?? [],
                'alertas' => $alertasPatrones,
                'contexto_por_persona' => $patrones['por_persona']['promedio_hotel'] ?? [],
            ],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        // Techo defensivo: los casos crudos crecen con los hallazgos.
        if ($json !== false && strlen($json) > 300000) {
            $json = substr($json, 0, 300000);
        }

        return "Datos de vigilancia del hotel al {$fecha} (integridad de libros + patrones de comportamiento del Guardian):\n\n" . ($json ?: '{}');
    }

    // ───────────────────────── API de Claude (niveles 2 y 3) ─────────────────────────

    /** Devuelve ['success', 'texto', 'modelo', 'tokens_entrada', 'tokens_salida', 'message']. */
    private function llamarClaude(string $sistema, string $usuario, string $modelo = self::MODELO): array
    {
        $cuerpo = [
            'model' => $modelo,
            'max_tokens' => self::MAX_TOKENS,
            'system' => $sistema,
            'messages' => [
                ['role' => 'user', 'content' => $usuario],
            ],
        ];
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . trim((string) getenv('ANTHROPIC_API_KEY')),
            'anthropic-version: ' . self::API_VERSION,
        ];

        if ($modelo === self::MODELO_FORENSE_V2) {
            // Fable 5: thinking siempre activo (se OMITE el campo; enviarlo
            // 'disabled' es 400), profundidad via output_config.effort, y
            // fallback opt-in a Opus 4.8 por si los clasificadores rechazan.
            $cuerpo['output_config'] = ['effort' => 'high'];
            $cuerpo['fallbacks'] = [['model' => self::MODELO]];
            $headers[] = 'anthropic-beta: server-side-fallback-2026-06-01';
            $timeout = 300; // los turnos de Fable 5 pueden tomar minutos
        } else {
            $cuerpo['thinking'] = ['type' => 'adaptive'];
            $timeout = 120;
        }

        $payload = json_encode($cuerpo, JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $respuesta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            error_log('Vigilancia financiera: error curl contra el API: ' . $errorCurl);
            return ['success' => false, 'message' => 'No se pudo contactar al analista inteligente. Intenta de nuevo.'];
        }

        $json = json_decode((string) $respuesta, true);

        if ($status === 429) {
            return ['success' => false, 'message' => 'El analista inteligente esta ocupado en este momento. Intenta en un minuto.'];
        }

        if ($status < 200 || $status >= 300 || !is_array($json)) {
            $detalle = is_array($json) ? (string) ($json['error']['message'] ?? '') : '';
            error_log('Vigilancia financiera: API HTTP ' . $status . ': ' . $detalle);
            return ['success' => false, 'message' => 'El analista inteligente no pudo generar el informe (error del servicio).'];
        }

        // Revisar stop_reason antes de leer contenido (refusal/max_tokens).
        $stopReason = (string) ($json['stop_reason'] ?? '');
        if ($stopReason === 'refusal') {
            return ['success' => false, 'message' => 'El analista inteligente no pudo generar este informe.'];
        }

        // content es una lista de bloques polimorficos: tomar los de tipo text.
        $texto = '';
        foreach ((array) ($json['content'] ?? []) as $bloque) {
            if (($bloque['type'] ?? '') === 'text') {
                $texto .= (string) ($bloque['text'] ?? '');
            }
        }

        $texto = trim($texto);
        if ($texto === '') {
            return ['success' => false, 'message' => 'El analista inteligente devolvio una respuesta vacia. Intenta regenerar.'];
        }

        return [
            'success' => true,
            'texto' => $texto,
            // El API reporta el modelo que realmente respondio (con fallback
            // puede ser Opus 4.8 aunque se pidiera Fable 5).
            'modelo' => (string) ($json['model'] ?? $modelo),
            'tokens_entrada' => (int) ($json['usage']['input_tokens'] ?? 0),
            'tokens_salida' => (int) ($json['usage']['output_tokens'] ?? 0),
        ];
    }

    // ───────────────────────── Cache ─────────────────────────

    private function leerCache(int $hotelId, string $fecha): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT contenido, modelo, updated_at FROM ia_resumenes
                 WHERE hotel_id = ? AND tipo = ? AND fecha = ? LIMIT 1"
            );
            $stmt->execute([$hotelId, self::TIPO_CACHE, $fecha]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fila ?: null;
        } catch (Throwable $e) {
            error_log('Vigilancia financiera: error al leer cache: ' . $e->getMessage());
            return null;
        }
    }

    private function guardarCache(int $hotelId, string $fecha, array $respuesta, ?int $usuarioId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO ia_resumenes
                    (hotel_id, tipo, fecha, contenido, modelo, tokens_entrada, tokens_salida, generado_por, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    contenido = VALUES(contenido),
                    modelo = VALUES(modelo),
                    tokens_entrada = VALUES(tokens_entrada),
                    tokens_salida = VALUES(tokens_salida),
                    generado_por = VALUES(generado_por),
                    updated_at = NOW()"
            );
            $stmt->execute([
                $hotelId,
                self::TIPO_CACHE,
                $fecha,
                (string) $respuesta['texto'],
                (string) ($respuesta['modelo'] ?? self::MODELO),
                (int) ($respuesta['tokens_entrada'] ?? 0),
                (int) ($respuesta['tokens_salida'] ?? 0),
                $usuarioId,
            ]);
        } catch (Throwable $e) {
            error_log('Vigilancia financiera: error al guardar cache: ' . $e->getMessage());
        }
    }
}
