<?php

/**
 * IcalCanalesService (bloque canales_ical, $249)
 *
 * Sincronizacion de calendarios con OTAs para evitar overbooking:
 *  - Exporta un feed .ics por habitacion (reservas confirmadas/checked_in) que
 *    el hotel pega en Airbnb/Booking. Protegido por token secreto del hotel.
 *  - Importa los calendarios externos (ical_feeds) y materializa sus eventos
 *    como ical_bloqueos; la disponibilidad (interna y motor) los respeta solo
 *    mientras el bloque este contratado.
 *
 * Parser iCal minimo y defensivo: VEVENT con DTSTART/DTEND (DATE o DATETIME),
 * UID y SUMMARY. Sin dependencias externas (el proyecto no usa composer).
 */
class IcalCanalesService
{
    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    // ───────────────────────── Token de exportacion ─────────────────────────

    /** Devuelve el token del hotel; lo genera y persiste la primera vez. */
    public function tokenExportacion(int $hotelId): string
    {
        $token = (string) ConfiguracionHotelRegistry::get('ical.token_exportacion', '', $hotelId);

        if ($token !== '') {
            return $token;
        }

        $token = bin2hex(random_bytes(16));
        $this->db->query(
            "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
             VALUES (?, 'ical.token_exportacion', ?, 'string', 'ical', 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), activo = 1, updated_at = NOW()",
            [$hotelId, $token]
        );

        return $token;
    }

    public function validarToken(int $hotelId, string $token): bool
    {
        $guardado = (string) ConfiguracionHotelRegistry::get('ical.token_exportacion', '', $hotelId);
        return $guardado !== '' && hash_equals($guardado, $token);
    }

    // ───────────────────────── Exportacion (.ics) ─────────────────────────

    /**
     * Genera el calendario .ics de una habitacion: sus noches ocupadas por
     * reservaciones confirmadas o en casa, desde hace 30 dias en adelante.
     */
    public function generarIcs(int $hotelId, int $habitacionId): ?string
    {
        $stmt = $this->db->query(
            "SELECT numero, tipo FROM habitaciones WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$habitacionId, $hotelId]
        );
        $habitacion = $stmt ? $stmt->fetch() : null;

        if (!$habitacion) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT r.id, r.fecha_entrada, r.fecha_salida
             FROM reservacion_habitaciones rh
             INNER JOIN reservaciones r ON r.id = rh.reservacion_id AND r.hotel_id = rh.hotel_id
             WHERE rh.habitacion_id = ?
               AND rh.hotel_id = ?
               AND r.estado IN ('confirmada', 'checked_in')
               AND r.fecha_salida >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             ORDER BY r.fecha_entrada",
            [$habitacionId, $hotelId]
        );
        $reservas = $stmt ? $stmt->fetchAll() : [];

        $etiqueta = trim((string) ($habitacion['numero'] ?? '')) !== ''
            ? (string) $habitacion['numero']
            : (string) ($habitacion['tipo'] ?? $habitacionId);

        $lineas = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Medisoft Hoteles//Canales iCal//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        $ahora = gmdate('Ymd\THis\Z');

        foreach ($reservas as $r) {
            $inicio = date('Ymd', strtotime((string) $r['fecha_entrada'] . ' 12:00:00'));
            $fin = date('Ymd', strtotime((string) $r['fecha_salida'] . ' 12:00:00'));

            if ($fin <= $inicio) {
                continue;
            }

            $lineas[] = 'BEGIN:VEVENT';
            $lineas[] = 'UID:res-' . (int) $r['id'] . '-hab-' . $habitacionId . '@medisoft-hoteles';
            $lineas[] = 'DTSTAMP:' . $ahora;
            $lineas[] = 'DTSTART;VALUE=DATE:' . $inicio;
            $lineas[] = 'DTEND;VALUE=DATE:' . $fin;
            $lineas[] = 'SUMMARY:Reservado (Hab ' . $this->escaparIcs($etiqueta) . ')';
            $lineas[] = 'END:VEVENT';
        }

        $lineas[] = 'END:VCALENDAR';

        return implode("\r\n", $lineas) . "\r\n";
    }

    // ───────────────────────── Feeds importados ─────────────────────────

    public function feedsDelHotel(int $hotelId): array
    {
        $stmt = $this->db->query(
            "SELECT f.*, h.numero AS habitacion_numero, h.tipo AS habitacion_tipo
             FROM ical_feeds f
             INNER JOIN habitaciones h ON h.id = f.habitacion_id
             WHERE f.hotel_id = ?
             ORDER BY h.numero, f.nombre",
            [$hotelId]
        );

        return $stmt ? $stmt->fetchAll() : [];
    }

    public function guardarFeed(int $hotelId, int $habitacionId, string $nombre, string $url): array
    {
        $nombre = trim($nombre);
        $url = trim($url);

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            return ['success' => false, 'message' => 'Ponle un nombre corto al calendario (ej. "Airbnb Hab 5").'];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $url)) {
            return ['success' => false, 'message' => 'La URL del calendario no es valida. Copia el link iCal (.ics) de la plataforma.'];
        }

        $stmt = $this->db->query(
            "SELECT id FROM habitaciones WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$habitacionId, $hotelId]
        );

        if (!$stmt || !$stmt->fetch()) {
            return ['success' => false, 'message' => 'Habitacion no encontrada.'];
        }

        $this->db->query(
            "INSERT INTO ical_feeds (hotel_id, habitacion_id, nombre, url, activo, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, NOW(), NOW())",
            [$hotelId, $habitacionId, $nombre, $url]
        );

        return ['success' => true, 'message' => 'Calendario agregado. Sincroniza para traer sus reservas.'];
    }

    public function eliminarFeed(int $hotelId, int $feedId): bool
    {
        // ON DELETE CASCADE limpia sus ical_bloqueos.
        $stmt = $this->db->query(
            "DELETE FROM ical_feeds WHERE id = ? AND hotel_id = ?",
            [$feedId, $hotelId]
        );

        return $stmt !== false;
    }

    // ───────────────────────── Sincronizacion ─────────────────────────

    /** Sincroniza todos los feeds activos del hotel. Devuelve resumen por feed. */
    public function sincronizarHotel(int $hotelId): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM ical_feeds WHERE hotel_id = ? AND activo = 1",
            [$hotelId]
        );
        $feeds = $stmt ? $stmt->fetchAll() : [];

        $resultados = [];

        foreach ($feeds as $feed) {
            $resultados[] = $this->sincronizarFeed($feed);
        }

        return $resultados;
    }

    /**
     * Descarga y aplica un feed: upsert de eventos vigentes, libera los que ya
     * no vienen en el calendario. Nunca lanza: registra el error en el feed.
     */
    public function sincronizarFeed(array $feed): array
    {
        $feedId = (int) $feed['id'];
        $hotelId = (int) $feed['hotel_id'];
        $habitacionId = (int) $feed['habitacion_id'];

        try {
            $contenido = $this->descargar((string) $feed['url']);

            if ($contenido === null) {
                throw new RuntimeException('No se pudo descargar el calendario (revisa la URL).');
            }

            $eventos = $this->parsearEventos($contenido);
            $uidsVigentes = [];
            $hoy = date('Y-m-d');

            foreach ($eventos as $ev) {
                // Solo eventos que aun bloquean fechas futuras.
                if ($ev['fecha_fin'] <= $hoy) {
                    continue;
                }

                $uidsVigentes[] = $ev['uid'];
                $this->db->query(
                    "INSERT INTO ical_bloqueos
                        (hotel_id, habitacion_id, feed_id, uid, resumen, fecha_inicio, fecha_fin, estado, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'activo', NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                        resumen = VALUES(resumen),
                        fecha_inicio = VALUES(fecha_inicio),
                        fecha_fin = VALUES(fecha_fin),
                        estado = 'activo',
                        updated_at = NOW()",
                    [$hotelId, $habitacionId, $feedId, $ev['uid'], $ev['resumen'], $ev['fecha_inicio'], $ev['fecha_fin']]
                );
            }

            // Eventos que desaparecieron del calendario = cancelados alla.
            if (empty($uidsVigentes)) {
                $this->db->query(
                    "UPDATE ical_bloqueos SET estado = 'liberado', updated_at = NOW()
                     WHERE feed_id = ? AND estado = 'activo'",
                    [$feedId]
                );
            } else {
                $placeholders = implode(', ', array_fill(0, count($uidsVigentes), '?'));
                $this->db->query(
                    "UPDATE ical_bloqueos SET estado = 'liberado', updated_at = NOW()
                     WHERE feed_id = ? AND estado = 'activo' AND uid NOT IN ({$placeholders})",
                    array_merge([$feedId], $uidsVigentes)
                );
            }

            $activos = count($uidsVigentes);
            $this->db->query(
                "UPDATE ical_feeds
                 SET last_sync_at = NOW(), last_sync_estado = 'ok', last_sync_error = NULL,
                     eventos_activos = ?, updated_at = NOW()
                 WHERE id = ?",
                [$activos, $feedId]
            );

            return ['feed_id' => $feedId, 'success' => true, 'eventos' => $activos];
        } catch (Throwable $e) {
            $mensaje = mb_substr($e->getMessage(), 0, 250);
            $this->db->query(
                "UPDATE ical_feeds
                 SET last_sync_at = NOW(), last_sync_estado = 'error', last_sync_error = ?, updated_at = NOW()
                 WHERE id = ?",
                [$mensaje, $feedId]
            );
            error_log('iCal: sync fallida del feed ' . $feedId . ' (hotel ' . $hotelId . '): ' . $e->getMessage());

            return ['feed_id' => $feedId, 'success' => false, 'error' => $mensaje];
        }
    }

    // ───────────────────────── Disponibilidad ─────────────────────────

    /**
     * IDs de habitaciones bloqueadas por canales externos en el rango dado.
     * Devuelve [] si el hotel no tiene el bloque canales_ical (cero impacto).
     */
    public static function habitacionesBloqueadas(int $hotelId, string $fechaEntrada, string $fechaSalida): array
    {
        try {
            if ($hotelId <= 0
                || !function_exists('hotel_has_module')
                || !hotel_has_module('canales_ical', $hotelId)) {
                return [];
            }

            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT DISTINCT habitacion_id
                 FROM ical_bloqueos
                 WHERE hotel_id = ?
                   AND estado = 'activo'
                   AND fecha_inicio < ?
                   AND fecha_fin > ?",
                [$hotelId, $fechaSalida, $fechaEntrada]
            );

            return $stmt ? array_map('intval', array_column($stmt->fetchAll(), 'habitacion_id')) : [];
        } catch (Throwable $e) {
            // Nunca tirar la disponibilidad por un fallo del bloque iCal.
            error_log('iCal: habitacionesBloqueadas fallo (hotel ' . $hotelId . '): ' . $e->getMessage());
            return [];
        }
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function descargar(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => 'MedisoftHoteles-iCal/1.0',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        $respuesta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($respuesta === false || $status !== 200) {
            return null;
        }

        // 2 MB de tope: un calendario legitimo jamas pesa eso.
        if (strlen($respuesta) > 2 * 1024 * 1024) {
            return null;
        }

        return (string) $respuesta;
    }

    /** Parser minimo de VEVENTs: devuelve uid, resumen, fecha_inicio, fecha_fin (Y-m-d). */
    private function parsearEventos(string $ics): array
    {
        // Unfold RFC 5545: la continuacion de linea empieza con espacio o tab.
        $ics = preg_replace('/\r?\n[ \t]/', '', $ics);
        $lineas = preg_split('/\r?\n/', $ics) ?: [];

        $eventos = [];
        $actual = null;

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            if ($linea === 'BEGIN:VEVENT') {
                $actual = ['uid' => '', 'resumen' => null, 'fecha_inicio' => '', 'fecha_fin' => ''];
                continue;
            }

            if ($linea === 'END:VEVENT') {
                if ($actual && $actual['fecha_inicio'] !== '' && $actual['fecha_fin'] !== ''
                    && $actual['fecha_fin'] > $actual['fecha_inicio']) {
                    if ($actual['uid'] === '') {
                        // Sin UID (raro pero legal-ish): derivar uno estable de las fechas.
                        $actual['uid'] = 'sin-uid-' . md5($actual['fecha_inicio'] . '|' . $actual['fecha_fin'] . '|' . (string) $actual['resumen']);
                    }
                    $eventos[] = $actual;
                }
                $actual = null;
                continue;
            }

            if ($actual === null || strpos($linea, ':') === false) {
                continue;
            }

            [$propiedad, $valor] = explode(':', $linea, 2);
            $nombreProp = strtoupper(explode(';', $propiedad, 2)[0]);

            switch ($nombreProp) {
                case 'UID':
                    $actual['uid'] = mb_substr(trim($valor), 0, 191);
                    break;
                case 'SUMMARY':
                    $actual['resumen'] = mb_substr($this->desescaparIcs(trim($valor)), 0, 150);
                    break;
                case 'DTSTART':
                    $actual['fecha_inicio'] = $this->fechaIcs($valor);
                    break;
                case 'DTEND':
                    $actual['fecha_fin'] = $this->fechaIcs($valor);
                    break;
            }
        }

        return $eventos;
    }

    /** Normaliza DATE (20260710) o DATETIME (20260710T140000Z) a Y-m-d. */
    private function fechaIcs(string $valor): string
    {
        $valor = trim($valor);

        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $valor, $m)) {
            $fecha = $m[1] . '-' . $m[2] . '-' . $m[3];
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $fecha : '';
        }

        return '';
    }

    private function escaparIcs(string $texto): string
    {
        return str_replace(["\\", ';', ',', "\n"], ["\\\\", '\\;', '\\,', '\\n'], $texto);
    }

    private function desescaparIcs(string $texto): string
    {
        return str_replace(['\\n', '\\,', '\\;', "\\\\"], ["\n", ',', ';', "\\"], $texto);
    }
}
