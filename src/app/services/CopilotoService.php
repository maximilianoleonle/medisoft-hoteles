<?php

require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/ConfiguracionHotelRegistry.php';

/**
 * CopilotoService (bloque copiloto, $149)
 *
 * Asistente hibrido, REGLAS PRIMERO:
 *  - Las preguntas de datos (ocupacion, caja, llegadas, salidas, pendientes,
 *    pagos por conciliar) se responden con consultas de SOLO LECTURA + una
 *    plantilla. Instantaneo, sin costo de API, offline, y jamas inventa cifras.
 *  - Lo que no casa con una regla cae a Claude SOLO si el hotel tiene la IA
 *    encendida (config copiloto.ia_activa) y hay ANTHROPIC_API_KEY. Aun ahi,
 *    los numeros los pone el snapshot del servidor, no el modelo.
 *
 * REGLA DURA: nunca escribe en Caja, reservaciones ni datos operativos. A lo
 * mucho sugiere una accion con un enlace; ejecutarla es decision humana.
 */
class CopilotoService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const MODELO = 'claude-opus-4-8';
    private const MAX_TOKENS = 1024;

    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function iaDisponible(int $hotelId): bool
    {
        return trim((string) (getenv('ANTHROPIC_API_KEY') ?: '')) !== ''
            && ConfiguracionHotelRegistry::getBool('copiloto.ia_activa', true, $hotelId);
    }

    /**
     * Responde una pregunta. Devuelve
     * ['success', 'texto', 'fuente' => 'reglas'|'ia'|'fallback', 'enlace' => ?['url','texto']].
     */
    public function responder(int $hotelId, string $pregunta, ?int $usuarioId = null): array
    {
        $pregunta = trim($pregunta);
        if ($pregunta === '') {
            return ['success' => false, 'texto' => 'Escribe tu pregunta.', 'fuente' => 'fallback', 'enlace' => null];
        }
        $pregunta = mb_substr($pregunta, 0, 500);
        $norm = $this->normalizar($pregunta);

        // 1) Ayuda "como hago X" con FAQ deterministo. Va PRIMERO: sus frases son
        //    especificas ("como hago un corte") y no deben confundirse con la
        //    pregunta de dato ("como voy de caja" -> saldo).
        $faq = $this->detectarFaq($norm);
        if ($faq !== null) {
            $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', 'faq:' . $faq['clave'], 0, 0);
            return ['success' => true, 'texto' => $faq['texto'], 'fuente' => 'reglas', 'enlace' => $faq['enlace']];
        }

        // 2) Reglas de datos (deterministas).
        $intent = $this->detectarIntent($norm, $hotelId);
        if ($intent !== null) {
            $r = $this->responderIntent($hotelId, $intent);
            $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', $intent, 0, 0);
            return $r + ['success' => true, 'fuente' => 'reglas'];
        }

        // 3) IA opcional para lo abierto.
        if ($this->iaDisponible($hotelId)) {
            $ia = $this->responderConIa($hotelId, $pregunta);
            $this->registrar($hotelId, $usuarioId, $pregunta, $ia['success'] ? 'ia' : 'fallback', null, (int) ($ia['tokens_entrada'] ?? 0), (int) ($ia['tokens_salida'] ?? 0));
            if (!empty($ia['success'])) {
                return ['success' => true, 'texto' => $ia['texto'], 'fuente' => 'ia', 'enlace' => null];
            }
            return ['success' => true, 'texto' => $ia['message'] ?? $this->textoFallback(), 'fuente' => 'fallback', 'enlace' => null];
        }

        // 4) Sin IA: sugerencias.
        $this->registrar($hotelId, $usuarioId, $pregunta, 'fallback', null, 0, 0);
        return ['success' => true, 'texto' => $this->textoFallback(), 'fuente' => 'fallback', 'enlace' => null];
    }

    // ───────────────────────── Reglas de datos ─────────────────────────

    private function detectarIntent(string $norm, int $hotelId): ?string
    {
        $tiene = static function (array $palabras) use ($norm) {
            foreach ($palabras as $p) {
                if (strpos($norm, $p) !== false) {
                    return true;
                }
            }
            return false;
        };

        if ($tiene(['ocupacion', 'cuartos libres', 'habitaciones libres', 'cuartos disponibles', 'habitaciones disponibles', 'cuanto tengo lleno', 'que tan lleno', 'disponibilidad hoy'])) {
            return 'ocupacion';
        }
        if ($tiene(['caja', 'efectivo', 'corte', 'cuanto llevo', 'cuanto hay en'])) {
            return 'caja';
        }
        if ($tiene(['llegan hoy', 'llegadas', 'quien llega', 'entradas de hoy', 'check-in de hoy', 'checkin de hoy', 'quienes llegan'])) {
            return 'llegadas';
        }
        if ($tiene(['salidas', 'quien se va', 'se van hoy', 'checkout de hoy', 'check-out de hoy', 'quienes salen'])) {
            return 'salidas';
        }
        if ($tiene(['no show', 'no-show', 'no llego', 'no llegaron', 'no se presento'])) {
            return 'no_shows';
        }
        if ($tiene(['no ha salido', 'sigue dentro', 'checkout vencid', 'checkouts vencid', 'vencidos', 'no han hecho checkout', 'no ha hecho checkout', 'deberia haber salido', 'siguen adentro'])) {
            return 'checkouts_vencidos';
        }
        if (($tiene(['conciliar', 'pagos online', 'pagos del motor', 'por conciliar', 'anticipos online']))
            && $this->tieneModulo('motor_reservas', $hotelId)) {
            return 'motor_conciliar';
        }

        return null;
    }

    private function responderIntent(int $hotelId, string $intent): array
    {
        switch ($intent) {
            case 'ocupacion':
                $o = $this->ocupacionHoy($hotelId);
                if ($o['activas'] <= 0) {
                    return ['texto' => 'No tienes habitaciones activas registradas todavia.', 'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones']];
                }
                return [
                    'texto' => "Hoy tienes **{$o['libres']} habitaciones libres** de {$o['activas']} ({$o['ocupadas']} ocupadas · {$o['pct']}% de ocupacion).",
                    'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones'],
                ];

            case 'caja':
                $c = $this->caja($hotelId);
                if (!$c['abierto']) {
                    return ['texto' => 'No hay ningun corte de caja abierto en este momento.', 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                return [
                    'texto' => "El corte de caja abierto tiene un **efectivo esperado de \${$c['esperado']}** (abierto desde {$c['desde']}).",
                    'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
                ];

            case 'llegadas':
                $l = $this->reservasPorFecha($hotelId, 'entrada');
                if ($l['total'] === 0) {
                    return ['texto' => 'No tienes llegadas programadas para hoy.', 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                return [
                    'texto' => "Hoy llegan **{$l['total']} reservacion(es)**" . ($l['nombres'] !== '' ? ': ' . $l['nombres'] : '') . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'salidas':
                $s = $this->reservasPorFecha($hotelId, 'salida');
                if ($s['total'] === 0) {
                    return ['texto' => 'No hay salidas programadas para hoy.', 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                return [
                    'texto' => "Hoy salen **{$s['total']} reservacion(es)**" . ($s['nombres'] !== '' ? ': ' . $s['nombres'] : '') . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'no_shows':
                $n = $this->pendientes($hotelId, 'no_show');
                return [
                    'texto' => $n === 0
                        ? 'No tienes no-shows pendientes. Todo en orden.'
                        : "Tienes **{$n} no-show(s)**: reservaciones confirmadas cuya llegada ya paso y no hicieron check-in.",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'checkouts_vencidos':
                $v = $this->pendientes($hotelId, 'checkout_vencido');
                return [
                    'texto' => $v === 0
                        ? 'No hay checkouts vencidos: nadie sigue dentro despues de su fecha de salida.'
                        : "Hay **{$v} checkout(s) vencido(s)**: huespedes con check-in cuya salida ya paso.",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'motor_conciliar':
                $m = $this->motorPorConciliar($hotelId);
                return [
                    'texto' => $m['n'] === 0
                        ? 'No tienes pagos online pendientes de conciliar a Caja.'
                        : "Tienes **{$m['n']} pago(s) online por conciliar** a Caja, por \${$m['monto']} en total.",
                    'enlace' => ['url' => 'motor-reservas', 'texto' => 'Ir al motor'],
                ];
        }

        return ['texto' => $this->textoFallback(), 'enlace' => null];
    }

    // ───────────────────────── Consultas de solo lectura ─────────────────────────

    private function ocupacionHoy(int $hotelId): array
    {
        $activas = 0;
        $ocupadas = 0;
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND activa = 1");
            $stmt->execute([$hotelId]);
            $activas = (int) $stmt->fetchColumn();

            $hoy = date('Y-m-d');
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(rh.id)
                 FROM reservaciones r
                 INNER JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ? AND r.estado NOT IN ('cancelada', 'pendiente')
                   AND r.fecha_entrada <= ? AND r.fecha_salida > ?"
            );
            $stmt->execute([$hotelId, $hoy, $hoy]);
            $ocupadas = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Copiloto: error ocupacion: ' . $e->getMessage());
        }

        $libres = max(0, $activas - $ocupadas);
        $pct = $activas > 0 ? (int) round($ocupadas * 100 / $activas) : 0;

        return ['activas' => $activas, 'ocupadas' => $ocupadas, 'libres' => $libres, 'pct' => $pct];
    }

    private function caja(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT efectivo_esperado, fecha_apertura FROM cortes_caja
                 WHERE hotel_id = ? AND fecha_cierre IS NULL
                 ORDER BY fecha_apertura DESC LIMIT 1"
            );
            $stmt->execute([$hotelId]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Copiloto: error caja: ' . $e->getMessage());
            $fila = null;
        }

        if (!$fila) {
            return ['abierto' => false, 'esperado' => '0.00', 'desde' => ''];
        }

        return [
            'abierto' => true,
            'esperado' => number_format((float) $fila['efectivo_esperado'], 2),
            'desde' => date('d/m H:i', strtotime((string) $fila['fecha_apertura'])),
        ];
    }

    private function reservasPorFecha(int $hotelId, string $tipo): array
    {
        $columna = $tipo === 'salida' ? 'fecha_salida' : 'fecha_entrada';
        $estados = $tipo === 'salida' ? "('checked_in', 'confirmada')" : "('confirmada', 'pendiente')";

        try {
            $stmt = $this->pdo->prepare(
                "SELECT h.nombre_completo
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE r.hotel_id = ? AND r.{$columna} = CURDATE() AND r.estado IN {$estados}
                 ORDER BY r.id LIMIT 30"
            );
            $stmt->execute([$hotelId]);
            $nombres = array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'nombre_completo');
        } catch (Throwable $e) {
            error_log('Copiloto: error reservas por fecha: ' . $e->getMessage());
            $nombres = [];
        }

        $total = count($nombres);
        $muestra = array_slice($nombres, 0, 5);
        $texto = implode(', ', $muestra);
        if ($total > count($muestra)) {
            $texto .= ' y ' . ($total - count($muestra)) . ' mas';
        }

        return ['total' => $total, 'nombres' => $texto];
    }

    private function pendientes(int $hotelId, string $tipo): int
    {
        $where = $tipo === 'checkout_vencido'
            ? "estado = 'checked_in' AND fecha_salida < CURDATE()"
            : "estado = 'confirmada' AND fecha_entrada < CURDATE()";

        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reservaciones WHERE hotel_id = ? AND {$where}");
            $stmt->execute([$hotelId]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Copiloto: error pendientes: ' . $e->getMessage());
            return 0;
        }
    }

    private function motorPorConciliar(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) AS n, COALESCE(SUM(monto), 0) AS monto
                 FROM motor_pagos_online WHERE hotel_id = ? AND estado = 'pagado'"
            );
            $stmt->execute([$hotelId]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto: error motor conciliar: ' . $e->getMessage());
            $fila = [];
        }

        return ['n' => (int) ($fila['n'] ?? 0), 'monto' => number_format((float) ($fila['monto'] ?? 0), 2)];
    }

    // ───────────────────────── Ayuda (FAQ deterministo) ─────────────────────────

    private function detectarFaq(string $norm): ?array
    {
        $faqs = [
            ['clave' => 'corte_caja', 'palabras' => ['como hago un corte', 'como cierro la caja', 'como hago el corte', 'cerrar caja'],
             'texto' => 'Para hacer un corte de caja: entra a **Caja**, revisa los movimientos del turno, captura el efectivo contado y confirma el cierre. El sistema calcula la diferencia contra lo esperado.',
             'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']],
            ['clave' => 'cupon', 'palabras' => ['como hago un cupon', 'como creo un cupon', 'crear cupon', 'codigo de descuento', 'como hago un descuento'],
             'texto' => 'Los cupones viven en **Motor de reservas > Cupones**. Crea un codigo, elige % o monto fijo, su vigencia y limite de usos. El huesped lo captura al reservar en linea.',
             'enlace' => ['url' => 'motor-reservas/cupones', 'texto' => 'Ir a Cupones']],
            ['clave' => 'reservacion', 'palabras' => ['como hago una reservacion', 'como creo una reserva', 'nueva reservacion', 'registrar reserva'],
             'texto' => 'Para una reservacion nueva: entra a **Reservaciones > Nueva**, elige fechas y habitacion, captura al huesped y guarda. Desde ahi puedes hacer el check-in cuando llegue.',
             'enlace' => ['url' => 'reservaciones', 'texto' => 'Ir a Reservaciones']],
            ['clave' => 'extras', 'palabras' => ['como vendo extras', 'como agrego extras', 'desayuno extra', 'late checkout'],
             'texto' => 'Los extras (desayuno, late checkout) se configuran en **Motor de reservas > Extras**. Defines nombre, precio y como se cobra; el huesped los agrega al reservar.',
             'enlace' => ['url' => 'motor-reservas/extras', 'texto' => 'Ir a Extras']],
            ['clave' => 'encuesta', 'palabras' => ['como mando una encuesta', 'encuesta de satisfaccion', 'como pido una resena', 'como pido calificacion'],
             'texto' => 'En **Reputacion** puedes generar y enviar la encuesta post-estancia a tus huespedes con checkout. Las buenas calificaciones se invitan a Google; las bajas te llegan como alerta.',
             'enlace' => ['url' => 'reputacion', 'texto' => 'Ir a Reputacion']],
        ];

        foreach ($faqs as $faq) {
            foreach ($faq['palabras'] as $p) {
                if (strpos($norm, $p) !== false) {
                    return $faq;
                }
            }
        }

        return null;
    }

    // ───────────────────────── IA (fallback opcional) ─────────────────────────

    private function responderConIa(int $hotelId, string $pregunta): array
    {
        $sistema = 'Eres el Copiloto de Medisoft Hoteles, un asistente dentro del sistema de gestion de un hotel '
            . 'pequeno o mediano en Mexico. Respondes SOLO con la informacion que se te da (datos en vivo del hotel '
            . 'y la guia de uso). Reglas estrictas:'
            . "\n- Nunca inventes cifras: si un numero no esta en los datos, di que no lo tienes a la mano y sugiere donde verlo."
            . "\n- Si preguntan como hacer algo, da los pasos en 2-4 lineas y menciona la seccion del sistema."
            . "\n- Eres de solo lectura: nunca afirmes haber hecho un cambio; a lo mucho sugieres la accion."
            . "\n- Escribe en espanol claro y breve, sin jerga ni tecnicismos, sin explicar que eres una IA."
            . "\n- Montos con formato \$1,234.56.";

        $usuario = "Datos en vivo del hotel (calculados por el servidor, son la unica fuente de cifras):\n"
            . $this->snapshotTexto($hotelId)
            . "\n\nGuia de uso disponible:\n" . $this->ayudaConocimiento()
            . "\n\nPregunta del usuario del hotel:\n" . $pregunta;

        return $this->llamarClaude($sistema, $usuario);
    }

    private function snapshotTexto(int $hotelId): string
    {
        $o = $this->ocupacionHoy($hotelId);
        $c = $this->caja($hotelId);
        $lleg = $this->reservasPorFecha($hotelId, 'entrada');
        $sal = $this->reservasPorFecha($hotelId, 'salida');
        $noShow = $this->pendientes($hotelId, 'no_show');
        $venc = $this->pendientes($hotelId, 'checkout_vencido');

        $lineas = [
            "- Fecha de hoy: " . date('Y-m-d'),
            "- Habitaciones activas: {$o['activas']}; ocupadas hoy: {$o['ocupadas']}; libres: {$o['libres']}; ocupacion: {$o['pct']}%",
            "- Llegadas de hoy: {$lleg['total']}",
            "- Salidas de hoy: {$sal['total']}",
            "- No-shows pendientes: {$noShow}",
            "- Checkouts vencidos: {$venc}",
            $c['abierto'] ? "- Caja: corte abierto, efectivo esperado \${$c['esperado']}" : "- Caja: sin corte abierto",
        ];

        if ($this->tieneModulo('motor_reservas', $hotelId)) {
            $m = $this->motorPorConciliar($hotelId);
            $lineas[] = "- Pagos online por conciliar: {$m['n']} (\${$m['monto']})";
        }

        return implode("\n", $lineas);
    }

    private function ayudaConocimiento(): string
    {
        return "- Corte de caja: seccion Caja.\n"
            . "- Reservacion nueva y check-in/check-out: seccion Reservaciones.\n"
            . "- Cupones de descuento y extras del motor: Motor de reservas > Cupones / Extras.\n"
            . "- Encuestas y resenas: seccion Reputacion.\n"
            . "- Huespedes frecuentes: seccion Huesped frecuente.\n"
            . "- Proyeccion de ocupacion: seccion Forecast.\n"
            . "- Cierre nocturno (no-shows, checkouts vencidos): seccion Night audit.";
    }

    /** Devuelve ['success', 'texto', 'tokens_entrada', 'tokens_salida', 'message']. */
    private function llamarClaude(string $sistema, string $usuario): array
    {
        $payload = json_encode([
            'model' => self::MODELO,
            'max_tokens' => self::MAX_TOKENS,
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
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $respuesta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            error_log('Copiloto: error curl Claude: ' . $errorCurl);
            return ['success' => false, 'message' => 'No pude conectar con el asistente. Intenta de nuevo o revisa tu internet.'];
        }

        $json = json_decode((string) $respuesta, true);
        if ($status === 429) {
            return ['success' => false, 'message' => 'El asistente esta saturado. Intenta en un minuto.'];
        }
        if ($status < 200 || $status >= 300 || !is_array($json)) {
            error_log('Copiloto: API Claude HTTP ' . $status);
            return ['success' => false, 'message' => 'El asistente no pudo responder ahora mismo.'];
        }
        if ((string) ($json['stop_reason'] ?? '') === 'refusal') {
            return ['success' => false, 'message' => 'No puedo responder eso.'];
        }

        $texto = '';
        foreach ((array) ($json['content'] ?? []) as $bloque) {
            if (($bloque['type'] ?? '') === 'text') {
                $texto .= (string) ($bloque['text'] ?? '');
            }
        }
        $texto = trim($texto);
        if ($texto === '') {
            return ['success' => false, 'message' => 'El asistente devolvio una respuesta vacia.'];
        }

        return [
            'success' => true,
            'texto' => $texto,
            'tokens_entrada' => (int) ($json['usage']['input_tokens'] ?? 0),
            'tokens_salida' => (int) ($json['usage']['output_tokens'] ?? 0),
        ];
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function textoFallback(): string
    {
        return 'No estoy seguro de esa. Puedo ayudarte con cosas como: '
            . '"¿cuantas habitaciones libres tengo?", "¿como voy de caja?", "¿quien llega hoy?", '
            . '"¿hay checkouts vencidos?" o "¿como hago un corte de caja?".';
    }

    private function tieneModulo(string $clave, int $hotelId): bool
    {
        return function_exists('hotel_has_module') && hotel_has_module($clave, $hotelId);
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        return preg_replace('/\s+/', ' ', $texto);
    }

    private function registrar(int $hotelId, ?int $usuarioId, string $pregunta, string $fuente, ?string $intent, int $tin, int $tout): void
    {
        try {
            $this->pdo->prepare(
                "INSERT INTO copiloto_mensajes
                    (hotel_id, usuario_id, pregunta, fuente, intent, tokens_entrada, tokens_salida, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            )->execute([$hotelId, $usuarioId, mb_substr($pregunta, 0, 500), $fuente, $intent, $tin, $tout]);
        } catch (Throwable $e) {
            error_log('Copiloto: no se pudo registrar mensaje: ' . $e->getMessage());
        }
    }
}
