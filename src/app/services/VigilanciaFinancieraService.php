<?php

require_once __DIR__ . '/../models/ConciliacionFinanciera.php';

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
 *  - Nivel 3 (claude-fable-5, FUTURO v2): cuando se enriquezca el prompt con
 *    los movimientos crudos detras de las top-alertas (montos, referencias,
 *    timestamps), escalar a Fable 5 para deteccion de patrones (constante
 *    MODELO_FORENSE_V2 abajo). Notas del API de Fable 5 para ese momento:
 *    OMITIR el campo 'thinking' (siempre activo; disabled=400), sin
 *    temperature/top_p/top_k, effort via output_config, fallback opt-in a
 *    opus-4-8 (header anthropic-beta: server-side-fallback-2026-06-01 +
 *    fallbacks:[{model:...}]), timeout curl 300s, requiere retencion 30 dias.
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
    /** Reservado para la v2 forense con movimientos crudos (ver docblock). */
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
     * Resumen determinista de anomalias para consumo interno (briefing push):
     * misma fuente que analizar() (conciliacion read-only), $0 y sin IA.
     * Devuelve ['errores', 'avisos', 'titulos' => hasta 3 titulos de alertas
     * con hallazgos] o null si el esquema financiero no esta completo.
     */
    public function resumenAnomalias(int $hotelId): ?array
    {
        if ($hotelId <= 0) {
            return null;
        }

        try {
            $modelo = new ConciliacionFinanciera();
            $reporte = $modelo->reporteReadOnlyPorHotel($hotelId, ['page' => 1, 'limit' => 50]);
        } catch (Throwable $e) {
            error_log('Vigilancia financiera: error en resumen de anomalias: ' . $e->getMessage());
            return null;
        }

        if (empty($reporte['schema_ok'])) {
            return null;
        }

        $totales = (array) ($reporte['totales_alertas'] ?? []);
        $titulos = [];
        foreach ((array) ($reporte['alertas'] ?? []) as $alerta) {
            if (count($titulos) >= 3) {
                break;
            }
            if ((int) ($alerta['conteo'] ?? 0) > 0 && in_array((string) ($alerta['severidad'] ?? ''), ['error', 'warning'], true)) {
                $titulos[] = (string) ($alerta['titulo'] ?? '');
            }
        }

        return [
            'errores' => (int) ($totales['error'] ?? 0),
            'avisos' => (int) ($totales['warning'] ?? 0),
            'titulos' => array_values(array_filter($titulos)),
        ];
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

        // ── Nivel 1: conciliacion limpia -> informe verde de plantilla, $0 ──
        if ((int) ($totales['error'] ?? 0) === 0 && (int) ($totales['warning'] ?? 0) === 0) {
            $respuesta = [
                'texto' => $this->informeVerdePlantilla($reporte),
                'modelo' => self::MODELO_PLANTILLA,
                'tokens_entrada' => 0,
                'tokens_salida' => 0,
            ];
            $this->guardarCache($hotelId, $fecha, $respuesta, $usuarioId);

            return [
                'success' => true,
                'informe' => (string) $respuesta['texto'],
                'hallazgos_deterministas' => 0,
                'generado_en' => date('Y-m-d H:i:s'),
                'modelo' => self::MODELO_PLANTILLA,
                'desde_cache' => false,
            ];
        }

        // ── Nivel 2: hay hallazgos -> Opus 4.8 interpreta ──
        if (!$this->configurado()) {
            return [
                'success' => false,
                'message' => 'La vigilancia financiera no esta configurada en el servidor (falta ANTHROPIC_API_KEY). Contacta a Medisoft.',
            ];
        }

        $respuesta = $this->llamarClaude($this->promptSistema(), $this->promptUsuario($fecha, $reporte));
        if (!$respuesta['success']) {
            return $respuesta;
        }

        $this->guardarCache($hotelId, $fecha, $respuesta, $usuarioId);

        return [
            'success' => true,
            'informe' => (string) $respuesta['texto'],
            'hallazgos_deterministas' => $hallazgos,
            'generado_en' => date('Y-m-d H:i:s'),
            'modelo' => (string) ($respuesta['modelo'] ?? self::MODELO),
            'desde_cache' => false,
        ];
    }

    // ───────────────────────── Nivel 1: plantilla verde ─────────────────────────

    /** Informe verde sin IA: todo cuadro, se narra con los numeros del resumen. */
    private function informeVerdePlantilla(array $reporte): string
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

    // ───────────────────────── API de Claude (nivel 2) ─────────────────────────

    /** Devuelve ['success', 'texto', 'modelo', 'tokens_entrada', 'tokens_salida', 'message']. */
    private function llamarClaude(string $sistema, string $usuario): array
    {
        $payload = json_encode([
            'model' => self::MODELO,
            'max_tokens' => self::MAX_TOKENS,
            'thinking' => ['type' => 'adaptive'],
            'system' => $sistema,
            'messages' => [
                ['role' => 'user', 'content' => $usuario],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . trim((string) getenv('ANTHROPIC_API_KEY')),
                'anthropic-version: ' . self::API_VERSION,
            ],
            CURLOPT_TIMEOUT => 120,
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
            'modelo' => (string) ($json['model'] ?? self::MODELO),
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
