<?php

require_once __DIR__ . '/ForecastService.php';

/**
 * CopilotoIaService (bloque copiloto_ia, $299 premium)
 *
 * IA sobre datos que el sistema YA genera. Tres funciones:
 *  - Borrador de respuesta a una resena/encuesta (bloque reputacion).
 *  - Analisis mensual de encuestas: quejas y elogios recurrentes (reputacion).
 *  - Consejo de tarifa segun ocupacion proyectada y pickup (bloque forecast).
 *
 * Integracion: POST https://api.anthropic.com/v1/messages (REST directo con
 * curl, sin SDK — el proyecto no usa composer; mismo patron que CopilotoService
 * e IaEjecutivaService). Modelo: claude-opus-4-8. Notas del API que NO deben
 * "corregirse": thinking adaptativo via {"type":"adaptive"}; NO enviar
 * temperature/top_p/top_k (Opus 4.8 responde 400 si se incluyen).
 *
 * Reglas duras:
 *  - Los numeros del prompt los pone SIEMPRE el servidor; la IA solo redacta.
 *  - Solo lectura: jamas escribe en tarifas, Caja ni datos operativos. El
 *    consejo de tarifa es texto; aplicarlo es decision del dueno.
 *  - Cache por (hotel, tipo, referencia) en copiloto_ia_generaciones: abrir lo
 *    ya generado no vuelve a pagar el API.
 *  - Prueba gratis: hoteles SIN el bloque tienen PRUEBAS_GRATIS usos por
 *    funcion (en_prueba=1); despues, mensaje de contratacion con el precio
 *    leido del catalogo modulos (nunca hardcodeado).
 */
class CopilotoIaService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const MODELO = 'claude-opus-4-8';

    private const MAX_TOKENS_RESENA = 1024;
    private const MAX_TOKENS_ANALISIS = 4096;
    private const MAX_TOKENS_TARIFA = 2048;

    // Limites duros de las sugerencias accionables de tarifa. La IA solo
    // PROPONE; estos topes los aplica PHP y no son negociables por el modelo.
    public const SUG_PCT_MAX = 15;         // |pct| maximo por sugerencia
    public const SUG_HORIZONTE_DIAS = 90;  // fechas dentro de los proximos 90 dias

    public const PRUEBAS_GRATIS = 3;      // usos por funcion para hoteles sin el bloque
    private const MAX_REGENERACIONES = 5; // regeneraciones por elemento ya generado
    private const MAX_RESENAS_MES = 100;  // borradores nuevos por mes natural (con bloque)

    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function iaConfigurada(): bool
    {
        return trim((string) (getenv('ANTHROPIC_API_KEY') ?: '')) !== '';
    }

    public function tieneBloque(int $hotelId): bool
    {
        return function_exists('hotel_has_module') && hotel_has_module('copiloto_ia', $hotelId);
    }

    // ───────────────────── Funciones publicas ─────────────────────

    /**
     * Borrador de respuesta a la encuesta/resena de un huesped, listo para
     * copiar y pegar (en Google, WhatsApp o correo).
     * Devuelve ['success','texto','desde_cache','generado_en','prueba','upsell','message'].
     */
    public function borradorResena(int $hotelId, int $encuestaId, bool $regenerar = false, ?int $usuarioId = null): array
    {
        if ($encuestaId <= 0) {
            return $this->falla('Encuesta invalida.');
        }

        $prep = $this->prepararGeneracion($hotelId, 'resena', (string) $encuestaId, $regenerar);
        if (!$prep['listo']) {
            return $prep['respuesta'];
        }

        $datos = $this->datosResena($hotelId, $encuestaId);
        if (isset($datos['error'])) {
            return $this->falla($datos['error']);
        }

        $ia = $this->llamarClaude($this->sistemaResena(), $datos['usuario'], self::MAX_TOKENS_RESENA);
        if (empty($ia['success'])) {
            return $this->falla((string) ($ia['message'] ?? 'La IA no pudo responder ahora mismo.'));
        }

        return $this->guardarYResponder($hotelId, 'resena', (string) $encuestaId, $ia, $prep['en_prueba'], $usuarioId);
    }

    /**
     * Analisis del mes: que se repite en quejas y elogios, y acciones sugeridas.
     * $mes en formato YYYY-MM (default: mes en curso).
     */
    public function analisisEncuestas(int $hotelId, ?string $mes = null, bool $regenerar = false, ?int $usuarioId = null): array
    {
        $mes = trim((string) ($mes ?? ''));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            $mes = date('Y-m');
        }
        if ($mes > date('Y-m')) {
            return $this->falla('Ese mes aun no ocurre.');
        }

        $prep = $this->prepararGeneracion($hotelId, 'analisis', $mes, $regenerar);
        if (!$prep['listo']) {
            return $prep['respuesta'];
        }

        $datos = $this->datosAnalisis($hotelId, $mes);
        if (isset($datos['error'])) {
            return $this->falla($datos['error']);
        }

        $ia = $this->llamarClaude($this->sistemaAnalisis(), $datos['usuario'], self::MAX_TOKENS_ANALISIS);
        if (empty($ia['success'])) {
            return $this->falla((string) ($ia['message'] ?? 'La IA no pudo responder ahora mismo.'));
        }

        return $this->guardarYResponder($hotelId, 'analisis', $mes, $ia, $prep['en_prueba'], $usuarioId);
    }

    /**
     * Consejo de tarifa del dia: lectura de la demanda proyectada y una
     * recomendacion conservadora por ventana (30/60/90). Cache por dia.
     */
    public function consejoTarifa(int $hotelId, bool $regenerar = false, ?int $usuarioId = null): array
    {
        $hoy = date('Y-m-d');

        $prep = $this->prepararGeneracion($hotelId, 'tarifa', $hoy, $regenerar);
        if (!$prep['listo']) {
            return $prep['respuesta'];
        }

        $datos = $this->datosTarifa($hotelId);
        if (isset($datos['error'])) {
            return $this->falla($datos['error']);
        }

        $ia = $this->llamarClaude($this->sistemaTarifa(), $datos['usuario'], self::MAX_TOKENS_TARIFA);
        if (empty($ia['success'])) {
            return $this->falla((string) ($ia['message'] ?? 'La IA no pudo responder ahora mismo.'));
        }

        return $this->guardarYResponder($hotelId, 'tarifa', $hoy, $ia, $prep['en_prueba'], $usuarioId);
    }

    /**
     * Fila cacheada del consejo de tarifa de HOY (id, contenido, updated_at) o
     * null si aun no se genera. El id sirve como consejo_ref al aplicar una
     * sugerencia: el ajuste queda ligado al consejo exacto que lo origino.
     */
    public function consejoTarifaHoy(int $hotelId): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, contenido, updated_at
                 FROM copiloto_ia_generaciones
                 WHERE hotel_id = ? AND tipo = 'tarifa' AND ref_clave = ?
                 LIMIT 1"
            );
            $stmt->execute([$hotelId, date('Y-m-d')]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fila ?: null;
        } catch (Throwable $e) {
            error_log('CopilotoIA: error al leer consejo de hoy: ' . $e->getMessage());
            return null;
        }
    }

    // ───────────────────── Gating: cache, prueba gratis y limites ─────────────────────

    /**
     * Decide si se puede generar. Devuelve ['listo' => true, 'en_prueba' => bool]
     * o ['listo' => false, 'respuesta' => <respuesta final para el cliente>].
     */
    private function prepararGeneracion(int $hotelId, string $tipo, string $refClave, bool $regenerar): array
    {
        $fila = $this->leerCache($hotelId, $tipo, $refClave);

        // Lo ya generado se sirve del cache: no vuelve a pagar API ni consume prueba.
        if ($fila && !$regenerar) {
            return ['listo' => false, 'respuesta' => $this->decorarRespuesta($tipo, [
                'success' => true,
                'texto' => (string) $fila['contenido'],
                'desde_cache' => true,
                'generado_en' => (string) $fila['updated_at'],
                'prueba' => $this->infoPrueba($hotelId, $tipo),
                'upsell' => false,
            ])];
        }

        if (!$this->iaConfigurada()) {
            return ['listo' => false, 'respuesta' => $this->falla(
                'La IA no esta configurada en el servidor (falta ANTHROPIC_API_KEY). Contacta a Medisoft.'
            )];
        }

        $tieneBloque = $this->tieneBloque($hotelId);

        // Regeneracion de un elemento existente: tope por elemento.
        if ($fila) {
            if ((int) $fila['veces'] > self::MAX_REGENERACIONES) {
                return ['listo' => false, 'respuesta' => $this->falla(
                    'Ya regeneraste esto varias veces; con los mismos datos el resultado no va a cambiar mucho. Vuelve cuando haya informacion nueva.'
                )];
            }
            return ['listo' => true, 'en_prueba' => !$tieneBloque];
        }

        // Elemento nuevo sin bloque contratado: prueba gratis limitada.
        if (!$tieneBloque) {
            if ($this->pruebasUsadas($hotelId, $tipo) >= self::PRUEBAS_GRATIS) {
                return ['listo' => false, 'respuesta' => $this->respuestaUpsell()];
            }
            return ['listo' => true, 'en_prueba' => true];
        }

        // Con bloque: tope mensual generoso solo para resenas (las otras dos ya
        // estan acotadas por su referencia: un analisis por mes, un consejo por dia).
        if ($tipo === 'resena' && $this->resenasDelMes($hotelId) >= self::MAX_RESENAS_MES) {
            return ['listo' => false, 'respuesta' => $this->falla(
                'Alcanzaste el limite de ' . self::MAX_RESENAS_MES . ' borradores nuevos este mes. El contador se reinicia el dia 1.'
            )];
        }

        return ['listo' => true, 'en_prueba' => false];
    }

    private function leerCache(int $hotelId, string $tipo, string $refClave): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT contenido, veces, en_prueba, updated_at
                 FROM copiloto_ia_generaciones
                 WHERE hotel_id = ? AND tipo = ? AND ref_clave = ?
                 LIMIT 1"
            );
            $stmt->execute([$hotelId, $tipo, $refClave]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fila ?: null;
        } catch (Throwable $e) {
            error_log('CopilotoIA: error al leer cache: ' . $e->getMessage());
            return null;
        }
    }

    private function pruebasUsadas(int $hotelId, string $tipo): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM copiloto_ia_generaciones
                 WHERE hotel_id = ? AND tipo = ? AND en_prueba = 1"
            );
            $stmt->execute([$hotelId, $tipo]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return self::PRUEBAS_GRATIS; // ante la duda, no regalar llamadas
        }
    }

    private function resenasDelMes(int $hotelId): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM copiloto_ia_generaciones
                 WHERE hotel_id = ? AND tipo = 'resena' AND created_at >= ?"
            );
            $stmt->execute([$hotelId, date('Y-m-01 00:00:00')]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /** ['restantes' => n] para hoteles en prueba; null si ya tienen el bloque. */
    private function infoPrueba(int $hotelId, string $tipo): ?array
    {
        if ($this->tieneBloque($hotelId)) {
            return null;
        }
        return ['restantes' => max(0, self::PRUEBAS_GRATIS - $this->pruebasUsadas($hotelId, $tipo))];
    }

    /** Precio y nombre SIEMPRE del catalogo (regla: nunca hardcodear precios). */
    private function respuestaUpsell(): array
    {
        $nombre = 'Copiloto IA';
        $precio = '';
        try {
            $stmt = $this->pdo->prepare("SELECT nombre, precio_mensual FROM modulos WHERE clave = 'copiloto_ia' LIMIT 1");
            $stmt->execute();
            $m = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($m) {
                $nombre = (string) $m['nombre'];
                $precio = ' ($' . number_format((float) $m['precio_mensual'], 2) . '/mes)';
            }
        } catch (Throwable $e) {
            // sin precio, el mensaje sigue sirviendo
        }

        return [
            'success' => false,
            'upsell' => true,
            'message' => 'Usaste tus ' . self::PRUEBAS_GRATIS . ' pruebas gratis de esta funcion. El bloque '
                . $nombre . $precio . ' incluye respuestas a resenas, analisis mensual de encuestas y consejo de tarifa, sin limites de prueba. Contratalo con Medisoft.',
        ];
    }

    private function guardarYResponder(int $hotelId, string $tipo, string $refClave, array $ia, bool $enPrueba, ?int $usuarioId): array
    {
        try {
            $this->pdo->prepare(
                "INSERT INTO copiloto_ia_generaciones
                    (hotel_id, usuario_id, tipo, ref_clave, contenido, veces, en_prueba, tokens_entrada, tokens_salida, created_at)
                 VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    contenido = VALUES(contenido),
                    veces = veces + 1,
                    usuario_id = VALUES(usuario_id),
                    tokens_entrada = tokens_entrada + VALUES(tokens_entrada),
                    tokens_salida = tokens_salida + VALUES(tokens_salida),
                    updated_at = CURRENT_TIMESTAMP"
            )->execute([
                $hotelId,
                $usuarioId,
                $tipo,
                $refClave,
                (string) $ia['texto'],
                $enPrueba ? 1 : 0,
                (int) ($ia['tokens_entrada'] ?? 0),
                (int) ($ia['tokens_salida'] ?? 0),
            ]);
        } catch (Throwable $e) {
            error_log('CopilotoIA: no se pudo guardar la generacion: ' . $e->getMessage());
        }

        return $this->decorarRespuesta($tipo, [
            'success' => true,
            'texto' => (string) $ia['texto'],
            'desde_cache' => false,
            'generado_en' => date('Y-m-d H:i:s'),
            'prueba' => $this->infoPrueba($hotelId, $tipo),
            'upsell' => false,
        ]);
    }

    private function falla(string $mensaje): array
    {
        return ['success' => false, 'message' => $mensaje, 'upsell' => false];
    }

    // ───────────────────── Datos y prompts: resena ─────────────────────

    private function sistemaResena(): string
    {
        return 'Eres quien responde las resenas y encuestas de un hotel pequeno o mediano en Mexico, '
            . 'escribiendo en nombre de la gerencia. Redacta UNA respuesta lista para copiar y pegar. Reglas estrictas:'
            . "\n- Responde SOLO con el texto de la respuesta: sin comillas, sin titulo, sin explicaciones y sin campos de plantilla tipo [Nombre]."
            . "\n- Entre 50 y 110 palabras. Espanol de Mexico, calido, humano y profesional."
            . "\n- Dirigete al huesped por su primer nombre y agradece siempre."
            . "\n- Personaliza mencionando algo concreto de su comentario; si no dejo comentario, agradece la calificacion."
            . "\n- Calificacion 4-5: agradece con entusiasmo e invita a volver."
            . "\n- Calificacion 1-3: disculpa sincera sin excusas, di que el tema se va a atender e invita a continuar la conversacion directamente con recepcion o gerencia."
            . "\n- NUNCA prometas compensaciones, reembolsos, descuentos ni cambios especificos."
            . "\n- NUNCA inventes detalles que no esten en el comentario."
            . "\n- No menciones que eres una IA ni un asistente.";
    }

    private function datosResena(int $hotelId, int $encuestaId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT e.calificacion, e.nps, e.comentario, e.estado,
                        h.nombre_completo, r.fecha_salida
                 FROM reputacion_encuestas e
                 INNER JOIN reservaciones r ON r.id = e.reservacion_id AND r.hotel_id = e.hotel_id
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE e.id = ? AND e.hotel_id = ?
                 LIMIT 1"
            );
            $stmt->execute([$encuestaId, $hotelId]);
            $e = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $ex) {
            error_log('CopilotoIA: error al leer encuesta: ' . $ex->getMessage());
            return ['error' => 'No pude leer la encuesta.'];
        }

        if (!$e) {
            return ['error' => 'No encontre esa encuesta.'];
        }
        if ((string) $e['estado'] !== 'respondida') {
            return ['error' => 'El huesped aun no responde esa encuesta.'];
        }
        $comentario = trim((string) ($e['comentario'] ?? ''));
        if ($e['calificacion'] === null && $comentario === '') {
            return ['error' => 'La encuesta no tiene calificacion ni comentario que responder.'];
        }

        $lineas = [
            'Hotel: ' . $this->nombreHotel($hotelId),
            'Huesped: ' . trim((string) $e['nombre_completo']),
            'Fecha de salida: ' . (string) $e['fecha_salida'],
            'Calificacion: ' . ($e['calificacion'] !== null ? ((int) $e['calificacion']) . ' de 5 estrellas' : 'no dejo calificacion'),
        ];
        if ($e['nps'] !== null) {
            $lineas[] = 'NPS: ' . (int) $e['nps'] . ' de 10';
        }
        $lineas[] = $comentario !== ''
            ? "Comentario del huesped:\n" . mb_substr($comentario, 0, 1200)
            : 'Comentario del huesped: (no dejo comentario, solo la calificacion)';

        return ['usuario' => implode("\n", $lineas)];
    }

    // ───────────────────── Datos y prompts: analisis de encuestas ─────────────────────

    private function sistemaAnalisis(): string
    {
        return 'Eres consultor de experiencia del huesped para un hotel pequeno o mediano en Mexico. '
            . 'Con las encuestas del periodo entregas un analisis breve y accionable para el dueno. '
            . 'Usa EXACTAMENTE esta estructura, con los encabezados en negritas (**asi**):'
            . "\n**Resumen del mes** — 2 o 3 lineas con el pulso general y el promedio."
            . "\n**Lo que mas se repite (quejas)** — hasta 3 puntos, cada uno con cuantas veces aparece; si no hay quejas, dilo."
            . "\n**Lo que elogian** — hasta 3 puntos."
            . "\n**Acciones sugeridas** — 2 o 3 acciones concretas y de bajo costo, la de mayor impacto primero."
            . "\nReglas: usa SOLO los comentarios y cifras proporcionados; no inventes temas ni numeros; "
            . 'si hay pocas encuestas, advierte que la muestra es chica; espanol claro, sin tecnicismos.';
    }

    private function datosAnalisis(int $hotelId, string $mes): array
    {
        $desde = $mes . '-01 00:00:00';
        $hasta = date('Y-m-01 00:00:00', strtotime($mes . '-01 +1 month'));

        try {
            $stmt = $this->pdo->prepare(
                "SELECT calificacion, nps, comentario
                 FROM reputacion_encuestas
                 WHERE hotel_id = ? AND estado = 'respondida'
                   AND respondida_at >= ? AND respondida_at < ?
                 ORDER BY respondida_at ASC
                 LIMIT 150"
            );
            $stmt->execute([$hotelId, $desde, $hasta]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('CopilotoIA: error al leer encuestas del mes: ' . $e->getMessage());
            return ['error' => 'No pude leer las encuestas del mes.'];
        }

        if (count($filas) === 0) {
            return ['error' => 'No hay encuestas respondidas en ' . $this->mesBonito($mes) . '. Manda encuestas a tus checkouts desde esta pantalla y vuelve aqui.'];
        }

        $conCalif = 0;
        $sumaCalif = 0;
        $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $conNps = 0;
        $sumaNps = 0;
        $comentarios = [];

        foreach ($filas as $f) {
            if ($f['calificacion'] !== null) {
                $c = (int) $f['calificacion'];
                $conCalif++;
                $sumaCalif += $c;
                if (isset($dist[$c])) {
                    $dist[$c]++;
                }
            }
            if ($f['nps'] !== null) {
                $conNps++;
                $sumaNps += (int) $f['nps'];
            }
            $comentario = trim((string) ($f['comentario'] ?? ''));
            if ($comentario !== '' && count($comentarios) < 80) {
                $etiqueta = $f['calificacion'] !== null ? ((int) $f['calificacion']) . '/5' : 'sin calif.';
                $comentarios[] = '- [' . $etiqueta . '] "' . mb_substr(preg_replace('/\s+/', ' ', $comentario), 0, 300) . '"';
            }
        }

        $lineas = [
            'Hotel: ' . $this->nombreHotel($hotelId),
            'Periodo: ' . $this->mesBonito($mes),
            'Encuestas respondidas: ' . count($filas),
            'Con calificacion: ' . $conCalif
                . ($conCalif > 0 ? ' (promedio ' . number_format($sumaCalif / $conCalif, 2) . '/5)' : ''),
            'Distribucion de estrellas: 5*=' . $dist[5] . ', 4*=' . $dist[4] . ', 3*=' . $dist[3] . ', 2*=' . $dist[2] . ', 1*=' . $dist[1],
        ];
        if ($conNps > 0) {
            $lineas[] = 'NPS promedio: ' . number_format($sumaNps / $conNps, 1) . ' de 10 (' . $conNps . ' respuestas)';
        }
        $lineas[] = 'Comentarios con texto: ' . count($comentarios);
        $lineas[] = '';
        $lineas[] = count($comentarios) > 0
            ? "Comentarios del periodo:\n" . implode("\n", $comentarios)
            : 'Comentarios del periodo: (nadie dejo texto, solo calificaciones)';

        return ['usuario' => implode("\n", $lineas)];
    }

    // ───────────────────── Datos y prompts: consejo de tarifa ─────────────────────

    private function sistemaTarifa(): string
    {
        return 'Eres asesor de ingresos (revenue) PRUDENTE para un hotel pequeno o mediano en Mexico. '
            . 'Con la ocupacion proyectada, el ritmo de ventas y la comparativa anual que te da el servidor, '
            . 'entregas una recomendacion de tarifa clara para el dueno. '
            . 'Usa EXACTAMENTE esta estructura, con los encabezados en negritas (**asi**):'
            . "\n**Lectura rapida** — 2 o 3 lineas: como pinta la demanda."
            . "\n**Recomendacion por ventana** — proximos 30 dias, 31-60 y 61-90: subir, mantener o bajar tarifa, con rango porcentual conservador y el porque; senala semanas o dias concretos si los datos lo ameritan."
            . "\n**Ojo con** — 1 o 2 riesgos u oportunidades concretos que se vean en las cifras."
            . "\nReglas: usa SOLO las cifras proporcionadas y cita las que uses; rangos conservadores (5% a 15%); "
            . 'la decision es del dueno, nunca lo presentes como orden ni como cambio ya aplicado; '
            . 'si los datos son pocos o la ocupacion es muy baja, dilo con honestidad; montos con formato $1,234.56; '
            . 'si el servidor incluye temporadas marcadas por el hotel o festivos/puentes, tomalos en cuenta al elegir fechas y direccion, y nombralos; '
            . 'si incluye resultados de ajustes anteriores del copiloto, evalualos con honestidad total en la seccion que corresponda: '
            . 'si el ajuste no mejoro la ocupacion o el ingreso, dilo tal cual y ajusta tu recomendacion (la credibilidad vale mas que el ego).'
            . "\nDespues del texto anterior, agrega al FINAL un bloque <sugerencias>...</sugerencias> con un arreglo JSON "
            . 'que traduzca tu recomendacion por ventana a datos, una entrada por ventana como maximo, con esta forma exacta: '
            . '[{"ventana":"30","accion":"subir","pct":8,"desde":"YYYY-MM-DD","hasta":"YYYY-MM-DD","motivo":"una linea"}]. '
            . 'Reglas del bloque: "ventana" es "30", "60" o "90"; "accion" es "subir", "bajar" o "mantener"; '
            . '"pct" es un entero entre 5 y 15, siempre el extremo CONSERVADOR (el mas bajo) del rango que recomendaste en el texto, y 0 si la accion es mantener; '
            . '"desde" y "hasta" son fechas dentro de los proximos 90 dias que cubran la ventana o los dias concretos que senalaste; '
            . '"motivo" resume en una linea el porque con la cifra clave. '
            . 'El bloque es datos para el sistema: no lo menciones en el texto ni escribas nada despues de </sugerencias>.';
    }

    private function datosTarifa(int $hotelId): array
    {
        try {
            $fs = new ForecastService();
            $total = $fs->habitacionesActivas($hotelId);
            if ($total <= 0) {
                return ['error' => 'No hay habitaciones activas para proyectar.'];
            }

            $porDia = $fs->ocupacionPorDia($hotelId, date('Y-m-d'), 90, true);
            $o30 = ForecastService::promedio($porDia, 30, $total);
            $o60 = ForecastService::promedio($porDia, 60, $total);
            $o90 = ForecastService::promedio($porDia, 90, $total);
            $pickup = $fs->pickup($hotelId);
            $semanas = $fs->resumenSemanal($hotelId, 10, $total);
        } catch (Throwable $e) {
            error_log('CopilotoIA: error al armar datos de forecast: ' . $e->getMessage());
            return ['error' => 'No pude calcular la proyeccion de ocupacion.'];
        }

        $pct = static function ($v) {
            return $v !== null ? number_format((float) $v, 1) . '%' : 'sin dato';
        };

        $lineas = [
            'Hotel: ' . $this->nombreHotel($hotelId),
            'Fecha de hoy: ' . date('Y-m-d'),
            'Habitaciones activas: ' . $total,
            'Ocupacion proyectada proximos 30 dias: ' . $pct($o30),
            'Ocupacion proyectada proximos 60 dias: ' . $pct($o60),
            'Ocupacion proyectada proximos 90 dias: ' . $pct($o90),
            'Ritmo de ventas (pickup): ultimos 7 dias ' . (int) ($pickup['reservas_7'] ?? 0) . ' reservas, '
                . (int) ($pickup['noches_7'] ?? 0) . ' noches, $' . number_format((float) ($pickup['monto_7'] ?? 0), 2)
                . ' | 7 dias previos: ' . (int) ($pickup['reservas_prev'] ?? 0) . ' reservas, '
                . (int) ($pickup['noches_prev'] ?? 0) . ' noches, $' . number_format((float) ($pickup['monto_prev'] ?? 0), 2),
        ];

        $noches7 = (int) ($pickup['noches_7'] ?? 0);
        if ($noches7 > 0) {
            $lineas[] = 'Tarifa promedio de lo vendido en los ultimos 7 dias (monto/noches): $'
                . number_format((float) $pickup['monto_7'] / $noches7, 2);
        }
        $nochesPrev = (int) ($pickup['noches_prev'] ?? 0);
        if ($nochesPrev > 0) {
            $lineas[] = 'Tarifa promedio de lo vendido en los 7 dias previos: $'
                . number_format((float) $pickup['monto_prev'] / $nochesPrev, 2);
        }

        // Dias mas fuertes ya proyectados (para detectar picos concretos).
        $top = $porDia;
        arsort($top);
        $picos = [];
        foreach ($top as $fecha => $ocupadas) {
            if (count($picos) >= 5 || (int) $ocupadas <= 0) {
                break;
            }
            $picos[] = $fecha . ' (' . $this->diaSemana($fecha) . '): ' . (int) $ocupadas . ' de ' . $total
                . ' hab (' . round($ocupadas * 100 / $total) . '%)';
        }
        if (!empty($picos)) {
            $lineas[] = "Dias con mas reservas ya confirmadas:\n- " . implode("\n- ", $picos);
        }

        $aniosCmp = (int) ($semanas[0]['anios_comparados'] ?? 1);
        $etiquetaPasado = $aniosCmp > 1 ? 'promedio de ' . $aniosCmp . ' anios anteriores' : 'hace 1 ano';

        $tabla = [];
        foreach ($semanas as $s) {
            $tabla[] = 'Semana del ' . $s['inicio'] . ' al ' . $s['fin'] . ': proyectada ' . $pct($s['ocupacion'])
                . ', ' . $etiquetaPasado . ' ' . $pct($s['ocupacion_anterior'])
                . ($s['delta'] !== null ? ' (delta ' . number_format((float) $s['delta'], 1) . ' pts)' : '');
        }
        if (!empty($tabla)) {
            $lineas[] = 'Proximas semanas vs ' . ($aniosCmp > 1 ? 'anios anteriores (promedio)' : 'el ano pasado') . ":\n" . implode("\n", $tabla);
        }

        // Temporadas marcadas por el hotel + festivos MX de los proximos 90 dias.
        // Contexto de demanda para la IA; si algo falla aqui, el consejo sale igual.
        try {
            require_once __DIR__ . '/../helpers/festivos_mx.php';

            $hoy = date('Y-m-d');
            $tope = date('Y-m-d', strtotime('+90 days'));

            $temporadas = (new TemporadaHotel())->enRango($hoy, $tope, $hotelId);
            if (!empty($temporadas)) {
                $lt = [];
                foreach ($temporadas as $t) {
                    $lt[] = $t['nombre'] . ' (' . $t['intensidad'] . '): del ' . $t['desde'] . ' al ' . $t['hasta']
                        . (trim((string) ($t['notas'] ?? '')) !== '' ? ' — ' . mb_substr(trim($t['notas']), 0, 120) : '');
                }
                $lineas[] = "Temporadas marcadas por el hotel (proximos 90 dias):\n- " . implode("\n- ", $lt);
            } else {
                $lineas[] = 'Temporadas marcadas por el hotel: ninguna capturada aun.';
            }

            $festivos = festivos_mx_en_rango($hoy, $tope);
            if (!empty($festivos)) {
                $lf = [];
                foreach ($festivos as $f) {
                    $lf[] = $f['nombre'] . ': ' . ($f['desde'] === $f['hasta'] ? $f['desde'] : 'del ' . $f['desde'] . ' al ' . $f['hasta']);
                }
                $lineas[] = "Festivos y puentes en Mexico (proximos 90 dias):\n- " . implode("\n- ", $lf);
            }
        } catch (Throwable $e) {
            error_log('CopilotoIA: error al leer temporadas/festivos: ' . $e->getMessage());
        }

        // Cierre del circulo (Fase 5): resultado real de los ultimos ajustes
        // del copiloto cuya ventana ya termino, para que el consejo aprenda
        // de ellos y hable con honestidad de lo que paso.
        try {
            $stmt = $this->pdo->prepare(
                "SELECT nombre, clase, valor_incremento, fecha_inicio, fecha_fin,
                        snapshot_ocupacion, snapshot_tarifa
                 FROM incrementos_tarifas
                 WHERE hotel_id = ? AND origen = 'copiloto'
                   AND fecha_fin IS NOT NULL AND fecha_fin < ? AND fecha_fin >= ?
                 ORDER BY fecha_fin DESC
                 LIMIT 3"
            );
            $stmt->execute([$hotelId, date('Y-m-d'), date('Y-m-d', strtotime('-90 days'))]);
            $ajustes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!empty($ajustes)) {
                $fs2 = new ForecastService();
                $lr = [];
                foreach ($ajustes as $aj) {
                    $real = $fs2->resultadoVentana($hotelId, (string) $aj['fecha_inicio'], (string) $aj['fecha_fin'], false);
                    if ($real['ocupacion'] === null) {
                        continue;
                    }
                    $signo = ($aj['clase'] === 'descuento' ? '-' : '+') . rtrim(rtrim(number_format((float) $aj['valor_incremento'], 1), '0'), '.') . '%';
                    $linea = 'Ajuste ' . $signo . ' del ' . $aj['fecha_inicio'] . ' al ' . $aj['fecha_fin']
                        . ': ocupacion real ' . number_format((float) $real['ocupacion'], 1) . '%';
                    if ($aj['snapshot_ocupacion'] !== null) {
                        $linea .= ' vs ' . number_format((float) $aj['snapshot_ocupacion'], 1) . '% proyectada al aplicarlo';
                    }
                    if ($real['tarifa_promedio'] !== null) {
                        $linea .= '; tarifa promedio real $' . number_format((float) $real['tarifa_promedio'], 2);
                        if ($aj['snapshot_tarifa'] !== null) {
                            $linea .= ' (al aplicar iba en $' . number_format((float) $aj['snapshot_tarifa'], 2) . ')';
                        }
                    }
                    $lr[] = $linea;
                }
                if (!empty($lr)) {
                    $lineas[] = "Resultados de ajustes anteriores del copiloto (ya concluidos):\n- " . implode("\n- ", $lr);
                }
            }
        } catch (Throwable $e) {
            error_log('CopilotoIA: error al leer resultados de ajustes: ' . $e->getMessage());
        }

        return ['usuario' => implode("\n", $lineas)];
    }

    // ───────────────────── Sugerencias accionables de tarifa ─────────────────────

    /**
     * Extrae y valida el bloque <sugerencias> que la IA agrega al final del
     * consejo de tarifa. La IA solo PROPONE: aqui PHP aplica los limites duros
     * (|pct| <= SUG_PCT_MAX, fechas dentro de los proximos SUG_HORIZONTE_DIAS
     * dias, desde <= hasta) y descarta cada entrada invalida SIN tirar el
     * consejo completo. Bloque ausente o JSON malformado => sugerencias vacias
     * y el consejo se muestra como texto plano (como hoy).
     *
     * Devuelve ['texto' => consejo sin el bloque, 'sugerencias' => entradas
     * validas]. $hoy es inyectable para pruebas (default: hoy).
     */
    public static function parsearSugerenciasTarifa(string $texto, ?string $hoy = null): array
    {
        $hoy = $hoy ?: date('Y-m-d');
        $limpio = trim((string) preg_replace('/<sugerencias>.*?<\/sugerencias>/is', '', $texto));
        if ($limpio === '') {
            $limpio = trim($texto);
        }

        if (!preg_match('/<sugerencias>(.*?)<\/sugerencias>/is', $texto, $m)) {
            return ['texto' => $limpio, 'sugerencias' => []];
        }

        $json = json_decode(trim($m[1]), true);
        if (!is_array($json)) {
            return ['texto' => $limpio, 'sugerencias' => []];
        }

        $tope = date('Y-m-d', strtotime($hoy . ' +' . self::SUG_HORIZONTE_DIAS . ' days'));
        $validas = [];
        $ventanasVistas = [];

        foreach ($json as $s) {
            if (!is_array($s)) {
                continue;
            }

            $ventana = (string) ($s['ventana'] ?? '');
            $accion = strtolower(trim((string) ($s['accion'] ?? '')));
            $desde = trim((string) ($s['desde'] ?? ''));
            $hasta = trim((string) ($s['hasta'] ?? ''));

            if (!in_array($ventana, ['30', '60', '90'], true) || isset($ventanasVistas[$ventana])) {
                continue;
            }
            if (!in_array($accion, ['subir', 'bajar', 'mantener'], true)) {
                continue;
            }

            // pct: numerico, positivo, dentro del tope duro. 'mantener' siempre 0.
            $pct = $s['pct'] ?? null;
            if ($accion === 'mantener') {
                $pct = 0.0;
            } else {
                if (!is_numeric($pct)) {
                    continue;
                }
                $pct = abs((float) $pct);
                if ($pct <= 0 || $pct > self::SUG_PCT_MAX) {
                    continue;
                }
            }

            // Fechas: formato real, desde <= hasta, dentro de [hoy, hoy+90].
            if (!self::fechaValida($desde) || !self::fechaValida($hasta)) {
                continue;
            }
            if ($desde > $hasta || $desde < $hoy || $hasta > $tope) {
                continue;
            }

            $motivo = trim((string) preg_replace('/\s+/', ' ', (string) ($s['motivo'] ?? '')));

            $ventanasVistas[$ventana] = true;
            $validas[] = [
                'ventana' => $ventana,
                'accion' => $accion,
                'pct' => $pct,
                'desde' => $desde,
                'hasta' => $hasta,
                'motivo' => mb_substr($motivo, 0, 200),
            ];
        }

        return ['texto' => $limpio, 'sugerencias' => $validas];
    }

    /** YYYY-MM-DD real (rechaza 2026-02-31 y formatos raros). */
    private static function fechaValida(string $fecha): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    /**
     * Post-proceso comun de la respuesta: para el consejo de tarifa separa el
     * bloque <sugerencias> del texto visible. El bloque viaja DENTRO del
     * contenido cacheado, asi que servir desde cache no re-paga tokens.
     */
    private function decorarRespuesta(string $tipo, array $respuesta): array
    {
        if ($tipo !== 'tarifa' || empty($respuesta['success'])) {
            return $respuesta;
        }

        $parseado = self::parsearSugerenciasTarifa((string) ($respuesta['texto'] ?? ''));
        $respuesta['texto'] = $parseado['texto'];
        $respuesta['sugerencias'] = $parseado['sugerencias'];
        return $respuesta;
    }

    // ───────────────────── Llamada al API (patron del repo) ─────────────────────

    /** Devuelve ['success', 'texto', 'tokens_entrada', 'tokens_salida', 'message']. */
    private function llamarClaude(string $sistema, string $usuario, int $maxTokens): array
    {
        $payload = json_encode([
            'model' => self::MODELO,
            'max_tokens' => $maxTokens,
            'thinking' => ['type' => 'adaptive'],
            'system' => $sistema,
            'messages' => [['role' => 'user', 'content' => $usuario]],
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
            CURLOPT_TIMEOUT => 90,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $respuesta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            error_log('CopilotoIA: error curl Claude: ' . $errorCurl);
            return ['success' => false, 'message' => 'No pude conectar con la IA. Intenta de nuevo o revisa tu internet.'];
        }

        $json = json_decode((string) $respuesta, true);
        if ($status === 401) {
            error_log('CopilotoIA: API Claude 401 (ANTHROPIC_API_KEY invalida o revocada)');
            return ['success' => false, 'message' => 'La llave de IA del servidor no es valida. Contacta a Medisoft para renovarla.'];
        }
        if ($status === 429) {
            return ['success' => false, 'message' => 'La IA esta saturada. Intenta en un minuto.'];
        }
        if ($status < 200 || $status >= 300 || !is_array($json)) {
            error_log('CopilotoIA: API Claude HTTP ' . $status);
            return ['success' => false, 'message' => 'La IA no pudo responder ahora mismo. Intenta de nuevo.'];
        }
        if ((string) ($json['stop_reason'] ?? '') === 'refusal') {
            return ['success' => false, 'message' => 'La IA no puede ayudar con este contenido.'];
        }

        $texto = '';
        foreach ((array) ($json['content'] ?? []) as $bloque) {
            if (($bloque['type'] ?? '') === 'text') {
                $texto .= (string) ($bloque['text'] ?? '');
            }
        }
        $texto = trim($texto);
        if ($texto === '') {
            return ['success' => false, 'message' => 'La IA devolvio una respuesta vacia. Intenta de nuevo.'];
        }

        return [
            'success' => true,
            'texto' => $texto,
            'tokens_entrada' => (int) ($json['usage']['input_tokens'] ?? 0),
            'tokens_salida' => (int) ($json['usage']['output_tokens'] ?? 0),
        ];
    }

    // ───────────────────── Helpers ─────────────────────

    private function nombreHotel(int $hotelId): string
    {
        try {
            $stmt = $this->pdo->prepare('SELECT nombre FROM hoteles WHERE id = ? LIMIT 1');
            $stmt->execute([$hotelId]);
            $nombre = trim((string) $stmt->fetchColumn());
            if ($nombre !== '') {
                return $nombre;
            }
        } catch (Throwable $e) {
            // cae al generico
        }
        return 'nuestro hotel';
    }

    private function mesBonito(string $mes): string
    {
        $nombres = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $n = (int) substr($mes, 5, 2);
        return ($nombres[$n] ?? $mes) . ' ' . substr($mes, 0, 4);
    }

    private function diaSemana(string $fecha): string
    {
        $dias = [1 => 'lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
        $n = (int) date('N', strtotime($fecha . ' 12:00:00'));
        return $dias[$n] ?? '';
    }
}
