<?php

require_once __DIR__ . '/ReporteGerencialDiarioService.php';

/**
 * IaEjecutivaService (bloque ia_ejecutiva)
 *
 * Genera el resumen gerencial diario narrado en lenguaje natural para el dueno
 * del hotel: toma el JSON estructurado de ReporteGerencialDiarioService y lo
 * convierte en un briefing accionable via el API de Claude.
 *
 * Integracion: POST https://api.anthropic.com/v1/messages (REST directo con
 * curl, sin SDK — el proyecto no usa composer; mismo patron que las pasarelas).
 * Modelo: claude-opus-4-8. Notas del API que NO deben "corregirse":
 *  - thinking adaptativo via {"type":"adaptive"} (budget_tokens ya no existe).
 *  - NO enviar temperature/top_p/top_k: Opus 4.8 responde 400 si se incluyen.
 * Llave global en env ANTHROPIC_API_KEY (Medisoft paga el API y revende el
 * bloque por hotel). Resultado cacheado en ia_resumenes por hotel/fecha.
 */
class IaEjecutivaService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const MODELO = 'claude-opus-4-8';
    private const MAX_TOKENS = 4096;

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
     * Resumen del dia para el hotel. Usa el cache salvo $regenerar.
     * Devuelve ['success', 'resumen', 'generado_en', 'desde_cache', 'message'].
     */
    public function resumenGerencialDiario(int $hotelId, string $fecha, bool $regenerar = false, ?int $usuarioId = null): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        if (!$regenerar) {
            $cacheado = $this->leerCache($hotelId, $fecha);
            if ($cacheado) {
                return [
                    'success' => true,
                    'resumen' => (string) $cacheado['contenido'],
                    'generado_en' => (string) $cacheado['updated_at'],
                    'desde_cache' => true,
                ];
            }
        }

        if (!$this->configurado()) {
            return [
                'success' => false,
                'message' => 'El asistente IA no esta configurado en el servidor (falta ANTHROPIC_API_KEY). Contacta a Medisoft.',
            ];
        }

        // Datos reales del dia: mismo servicio que alimenta el reporte gerencial.
        try {
            $servicio = new ReporteGerencialDiarioService();
            $reporte = $servicio->generar($hotelId, $fecha);
        } catch (Throwable $e) {
            error_log('IA Ejecutiva: error al generar datos del reporte gerencial: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudieron obtener los datos del dia para el resumen.'];
        }

        $respuesta = $this->llamarClaude($this->promptSistema(), $this->promptUsuario($fecha, $reporte));
        if (!$respuesta['success']) {
            return $respuesta;
        }

        $this->guardarCache($hotelId, $fecha, $respuesta, $usuarioId);

        return [
            'success' => true,
            'resumen' => (string) $respuesta['texto'],
            'generado_en' => date('Y-m-d H:i:s'),
            'desde_cache' => false,
        ];
    }

    // ───────────────────────── Prompts ─────────────────────────

    private function promptSistema(): string
    {
        return 'Eres el asesor ejecutivo de un hotel pequeno o mediano en Mexico. Cada manana '
            . 'recibes el reporte operativo del dia anterior en JSON y se lo narras al dueno del hotel. '
            . 'El dueno no es tecnico y tiene 2 minutos: escribe en espanol claro y directo, sin jerga. '
            . "\n\nFormato de tu respuesta (Markdown sencillo):\n"
            . "1. Un parrafo inicial de 2-3 oraciones con lo mas importante del dia (ocupacion, dinero, y el dato que mas destaque).\n"
            . "2. Seccion \"Lo bueno\": maximo 3 puntos.\n"
            . "3. Seccion \"Ojo con esto\": maximo 3 puntos con riesgos o pendientes accionables (facturas vencidas, diferencias de caja, mantenimientos, baja ocupacion futura). Si no hay nada preocupante, dilo en una linea.\n"
            . "4. Una recomendacion concreta para hoy, en una oracion.\n\n"
            . 'Reglas: usa solo los datos del JSON, nunca inventes cifras; escribe montos como $1,234.56; '
            . 'si un dato viene vacio o en cero, no lo menciones salvo que la ausencia sea relevante; '
            . 'no expliques que eres una IA ni describas el JSON.';
    }

    private function promptUsuario(string $fecha, array $reporte): string
    {
        $json = json_encode($reporte, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        // Techo defensivo para no exceder contexto/costo con payloads anomalos.
        if ($json !== false && strlen($json) > 180000) {
            $json = substr($json, 0, 180000);
        }

        return "Reporte operativo del hotel correspondiente al dia {$fecha}:\n\n" . ($json ?: '{}');
    }

    // ───────────────────────── API de Claude ─────────────────────────

    /** Devuelve ['success', 'texto', 'tokens_entrada', 'tokens_salida', 'message']. */
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
            error_log('IA Ejecutiva: error curl contra el API de Claude: ' . $errorCurl);
            return ['success' => false, 'message' => 'No se pudo contactar al asistente IA. Intenta de nuevo.'];
        }

        $json = json_decode((string) $respuesta, true);

        if ($status === 429) {
            return ['success' => false, 'message' => 'El asistente IA esta saturado en este momento. Intenta en un minuto.'];
        }

        if ($status < 200 || $status >= 300 || !is_array($json)) {
            $detalle = is_array($json) ? (string) ($json['error']['message'] ?? '') : '';
            error_log('IA Ejecutiva: API de Claude HTTP ' . $status . ': ' . $detalle);
            return ['success' => false, 'message' => 'El asistente IA no pudo generar el resumen (error del servicio).'];
        }

        // Revisar stop_reason antes de leer contenido (refusal/max_tokens).
        $stopReason = (string) ($json['stop_reason'] ?? '');
        if ($stopReason === 'refusal') {
            return ['success' => false, 'message' => 'El asistente IA declino generar este resumen.'];
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
            return ['success' => false, 'message' => 'El asistente IA devolvio una respuesta vacia. Intenta regenerar.'];
        }

        return [
            'success' => true,
            'texto' => $texto,
            'tokens_entrada' => (int) ($json['usage']['input_tokens'] ?? 0),
            'tokens_salida' => (int) ($json['usage']['output_tokens'] ?? 0),
        ];
    }

    // ───────────────────────── Cache ─────────────────────────

    private function leerCache(int $hotelId, string $fecha): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT contenido, updated_at FROM ia_resumenes
                 WHERE hotel_id = ? AND tipo = 'gerencial_diario' AND fecha = ? LIMIT 1"
            );
            $stmt->execute([$hotelId, $fecha]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fila ?: null;
        } catch (Throwable $e) {
            error_log('IA Ejecutiva: error al leer cache de resumen: ' . $e->getMessage());
            return null;
        }
    }

    private function guardarCache(int $hotelId, string $fecha, array $respuesta, ?int $usuarioId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO ia_resumenes
                    (hotel_id, tipo, fecha, contenido, modelo, tokens_entrada, tokens_salida, generado_por, created_at, updated_at)
                 VALUES (?, 'gerencial_diario', ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
                $fecha,
                (string) $respuesta['texto'],
                self::MODELO,
                (int) ($respuesta['tokens_entrada'] ?? 0),
                (int) ($respuesta['tokens_salida'] ?? 0),
                $usuarioId,
            ]);
        } catch (Throwable $e) {
            error_log('IA Ejecutiva: error al guardar cache de resumen: ' . $e->getMessage());
        }
    }
}
