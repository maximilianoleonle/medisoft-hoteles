<?php

require_once __DIR__ . '/CopilotoService.php';
require_once __DIR__ . '/NotificacionService.php';
require_once __DIR__ . '/VigilanciaFinancieraService.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/ConfiguracionHotelRegistry.php';

/**
 * CopilotoBriefingService (bloque copiloto_briefing)
 *
 * Briefing matutino proactivo: compone el pulso del dia REUTILIZANDO piezas
 * que ya existen (CopilotoService::resumenDelDia y el resumen determinista de
 * VigilanciaFinancieraService) y lo manda por la cadena push PWA existente
 * via NotificacionService::crear con ruteo por rol (gerencia recibe todo).
 *
 * Reglas duras:
 *  - Solo lectura sobre la operacion; el unico INSERT es el evento de
 *    notificaciones (y ese lo hace NotificacionService, como todos).
 *  - Un briefing por hotel por dia: candado propio por tipo+fecha (la
 *    dedupe_key de notificaciones se libera al resolverse; el candado local
 *    evita reenviar si gerencia ya lo atendio temprano).
 *  - Configurable por hotel: copiloto.briefing_activo + copiloto.briefing_hora
 *    (hotel_configuracion). La hora se evalua en la zona horaria del hotel.
 *  - El push real ademas exige el bloque 'notificaciones' (regla de la cadena
 *    push de siempre; aqui solo se reporta el motivo).
 */
class CopilotoBriefingService
{
    public const MODULO = 'copiloto_briefing';

    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /** ¿El hotel tiene el bloque y el briefing encendido en su configuracion? */
    public function activo(int $hotelId): bool
    {
        return $this->tieneModulo(self::MODULO, $hotelId)
            && ConfiguracionHotelRegistry::getBool('copiloto.briefing_activo', true, $hotelId);
    }

    /** Hora configurada (HH:MM); valores invalidos caen al default 08:00. */
    public function horaConfigurada(int $hotelId): string
    {
        $hora = trim((string) ConfiguracionHotelRegistry::get('copiloto.briefing_hora', '08:00', $hotelId));
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) ? $hora : '08:00';
    }

    /** Hora actual HH:MM en la zona horaria del hotel (config operacion.zona_horaria). */
    public function horaLocalHotel(int $hotelId): string
    {
        $tz = trim((string) ConfiguracionHotelRegistry::get('operacion.zona_horaria', 'America/Mexico_City', $hotelId));
        try {
            return (new DateTime('now', new DateTimeZone($tz !== '' ? $tz : 'America/Mexico_City')))->format('H:i');
        } catch (Throwable $e) {
            return date('H:i');
        }
    }

    /**
     * Compone el briefing del dia como texto plano para push:
     * ['titulo', 'mensaje', 'url'].
     */
    public function componer(int $hotelId): array
    {
        $copiloto = new CopilotoService($this->db);
        $resumen = $copiloto->resumenDelDia($hotelId);

        // El texto del intent viene con encabezado y negritas markdown; para
        // el push se queda el puro contenido en texto plano.
        $lineas = [];
        foreach (explode("\n", str_replace('**', '', (string) ($resumen['texto'] ?? ''))) as $linea) {
            $linea = trim($linea);
            if ($linea !== '' && strpos($linea, '•') === 0) {
                $lineas[] = $linea;
            }
        }

        $sucias = $this->suciasConLlegadaHoy($hotelId);
        if ($sucias > 0) {
            $lineas[] = "• Ojo: {$sucias} habitacion(es) sucia(s) con llegada hoy.";
        }

        try {
            $anomalias = (new VigilanciaFinancieraService($this->db))->resumenAnomalias($hotelId);
        } catch (Throwable $e) {
            error_log('Copiloto briefing: error en anomalias: ' . $e->getMessage());
            $anomalias = null;
        }
        if ($anomalias !== null && ($anomalias['errores'] + $anomalias['avisos']) > 0) {
            $lineaFin = "• Finanzas: {$anomalias['errores']} error(es) y {$anomalias['avisos']} aviso(s) en la conciliacion";
            if (!empty($anomalias['titulos'])) {
                $lineaFin .= ' (' . implode('; ', array_slice($anomalias['titulos'], 0, 2)) . ')';
            }
            $lineas[] = $lineaFin . '.';
        }

        return [
            'titulo' => '☀️ Asi arranca tu dia',
            'mensaje' => mb_substr(implode("\n", $lineas), 0, 900),
            'url' => 'dashboard',
        ];
    }

    /**
     * Envia el briefing del dia si corresponde. Pensado para correrse desde
     * cron cada pocos minutos: es idempotente por dia. $forzarHora salta la
     * comparacion de hora (para pruebas); el resto de los candados se queda.
     * Devuelve ['enviado' => bool, 'motivo' => string].
     */
    public function enviarSiCorresponde(int $hotelId, bool $forzarHora = false): array
    {
        if (!$this->tieneModulo(self::MODULO, $hotelId)) {
            return ['enviado' => false, 'motivo' => 'sin bloque copiloto_briefing'];
        }
        if (!ConfiguracionHotelRegistry::getBool('copiloto.briefing_activo', true, $hotelId)) {
            return ['enviado' => false, 'motivo' => 'briefing apagado en la configuracion del hotel'];
        }
        if (!$this->tieneModulo('notificaciones', $hotelId)) {
            return ['enviado' => false, 'motivo' => 'sin bloque notificaciones (la cadena push lo requiere)'];
        }

        $hora = $this->horaConfigurada($hotelId);
        $ahora = $this->horaLocalHotel($hotelId);
        if (!$forzarHora && $ahora < $hora) {
            return ['enviado' => false, 'motivo' => "aun no es la hora configurada ({$hora}; hora del hotel {$ahora})"];
        }

        if ($this->yaEnviadoHoy($hotelId, 'copiloto_briefing')) {
            return ['enviado' => false, 'motivo' => 'ya se envio el briefing de hoy'];
        }

        $briefing = $this->componer($hotelId);
        if (trim($briefing['mensaje']) === '') {
            return ['enviado' => false, 'motivo' => 'no hubo contenido que reportar'];
        }

        $id = NotificacionService::crear([
            'hotel_id' => $hotelId,
            'rol_destino' => 'gerente',
            'modulo' => 'copiloto',
            'tipo' => 'copiloto_briefing',
            'severidad' => 'info',
            'titulo' => $briefing['titulo'],
            'mensaje' => $briefing['mensaje'],
            'url' => $briefing['url'],
            'dedupe_key' => 'copiloto.briefing.' . date('Ymd'),
        ]);

        if ($id === null) {
            return ['enviado' => false, 'motivo' => 'no se pudo crear el evento de notificacion'];
        }

        return ['enviado' => true, 'motivo' => 'briefing enviado (evento #' . $id . ')'];
    }

    // ───────────────────────── Alertas proactivas con criterio ─────────────────────────

    /**
     * Regla de ocupacion baja: si N o mas de los proximos 7 dias quedan por
     * debajo del umbral X% de ocupacion, avisa por push con la sugerencia de
     * revisar el consejo de tarifa (CopilotoIaService, pantalla Forecast).
     *
     * Umbrales por hotel (hotel_configuracion, defaults sensatos):
     *   copiloto.alerta_ocupacion_activa (true) · _umbral (40%) · _dias (4).
     * Anti-insistencia: maximo UNA alerta cada 7 dias por hotel.
     * Devuelve ['enviado' => bool, 'motivo' => string].
     */
    public function evaluarAlertaOcupacion(int $hotelId, bool $forzarHora = false): array
    {
        if (!$this->tieneModulo(self::MODULO, $hotelId)) {
            return ['enviado' => false, 'motivo' => 'sin bloque copiloto_briefing'];
        }
        if (!$this->configBool($hotelId, 'copiloto.alerta_ocupacion_activa', true)) {
            return ['enviado' => false, 'motivo' => 'alerta de ocupacion apagada en la configuracion del hotel'];
        }
        if (!$this->tieneModulo('notificaciones', $hotelId)) {
            return ['enviado' => false, 'motivo' => 'sin bloque notificaciones (la cadena push lo requiere)'];
        }

        // Misma ventana horaria que el briefing: evaluar ya entrada la manana,
        // no a medianoche (el cron corre cada pocos minutos).
        $hora = $this->horaConfigurada($hotelId);
        $ahora = $this->horaLocalHotel($hotelId);
        if (!$forzarHora && $ahora < $hora) {
            return ['enviado' => false, 'motivo' => "aun no es la hora configurada ({$hora}; hora del hotel {$ahora})"];
        }

        if ($this->yaEnviadoEnDias($hotelId, 'copiloto_alerta_ocupacion', 7)) {
            return ['enviado' => false, 'motivo' => 'ya hubo alerta de ocupacion en los ultimos 7 dias'];
        }

        $sem = (new CopilotoService($this->db))->ocupacionProximos7($hotelId);
        if ($sem === null || empty($sem['por_dia']) || (int) $sem['activas'] <= 0) {
            return ['enviado' => false, 'motivo' => 'sin habitaciones activas para evaluar ocupacion'];
        }

        $umbral = max(5, min(95, $this->configInt($hotelId, 'copiloto.alerta_ocupacion_umbral', 40)));
        $diasMin = max(1, min(7, $this->configInt($hotelId, 'copiloto.alerta_ocupacion_dias', 4)));

        $diasBajo = 0;
        foreach ($sem['por_dia'] as $habs) {
            $pct = (int) round((int) $habs * 100 / (int) $sem['activas']);
            if ($pct < $umbral) {
                $diasBajo++;
            }
        }

        if ($diasBajo < $diasMin) {
            return ['enviado' => false, 'motivo' => "ocupacion sana: {$diasBajo} dia(s) bajo el {$umbral}% (umbral: {$diasMin} dias)"];
        }

        $conForecast = $this->tieneModulo('forecast', $hotelId);
        $mensaje = "{$diasBajo} de los proximos 7 dias estan por debajo del {$umbral}% de ocupacion"
            . " (promedio {$sem['promedio']}%, {$sem['libres_hoy']} habitaciones libres hoy).";
        $mensaje .= $conForecast
            ? ' Revisa el consejo de tarifa del Copiloto en Forecast para reaccionar a tiempo.'
            : ' Considera una promocion o ajuste de tarifa para levantar la semana.';

        $id = NotificacionService::crear([
            'hotel_id' => $hotelId,
            'rol_destino' => 'gerente',
            'modulo' => 'copiloto',
            'tipo' => 'copiloto_alerta_ocupacion',
            'severidad' => 'media',
            'titulo' => '📉 Semana floja a la vista',
            'mensaje' => $mensaje,
            'url' => $conForecast ? 'forecast' : 'reservaciones',
            'dedupe_key' => 'copiloto.alerta_ocupacion.' . date('oW'),
        ]);

        if ($id === null) {
            return ['enviado' => false, 'motivo' => 'no se pudo crear el evento de notificacion'];
        }

        return ['enviado' => true, 'motivo' => "alerta enviada: {$diasBajo}/7 dias bajo el {$umbral}% (evento #{$id})"];
    }

    /** Hoteles activos con el bloque contratado (para iterar desde cron/CLI). */
    public function hotelesConBloque(): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT h.id, h.nombre
                 FROM hoteles h
                 INNER JOIN hotel_modulos hm ON hm.hotel_id = h.id AND hm.activo = 1
                 INNER JOIN modulos m ON m.id = hm.modulo_id AND m.clave = ?
                 WHERE h.activo = 1
                 ORDER BY h.id"
            );
            $stmt->execute([self::MODULO]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto briefing: error al listar hoteles: ' . $e->getMessage());
            return [];
        }
    }

    // ───────────────────────── Internos ─────────────────────────

    /**
     * Candado diario propio: ¿ya existe el evento de hoy (en cualquier
     * estado)? La dedupe_key de notificaciones se libera al resolverse, asi
     * que este candado evita un segundo push si gerencia lo atendio temprano.
     */
    protected function yaEnviadoHoy(int $hotelId, string $tipo): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM notificaciones
                 WHERE hotel_id = ? AND tipo = ? AND created_at >= CURDATE()"
            );
            $stmt->execute([$hotelId, $tipo]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            error_log('Copiloto briefing: error en candado diario: ' . $e->getMessage());
            return true; // ante la duda, no duplicar push
        }
    }

    /** Candado por ventana: ¿ya hubo un evento de este tipo en los ultimos N dias? */
    protected function yaEnviadoEnDias(int $hotelId, string $tipo, int $dias): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM notificaciones
                 WHERE hotel_id = ? AND tipo = ? AND created_at >= NOW() - INTERVAL " . max(1, $dias) . " DAY"
            );
            $stmt->execute([$hotelId, $tipo]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            error_log('Copiloto briefing: error en candado por ventana: ' . $e->getMessage());
            return true; // ante la duda, no insistir
        }
    }

    /**
     * Umbrales por hotel via hotel_config_get (lee hotel_configuracion
     * directo, sin depender del registry): mismos valores que edita la
     * pantalla de Configuracion.
     */
    private function configBool(int $hotelId, string $clave, bool $default): bool
    {
        if (!function_exists('hotel_config_get')) {
            return $default;
        }
        $v = hotel_config_get($clave, $default, $hotelId);
        return is_bool($v) ? $v : filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    private function configInt(int $hotelId, string $clave, int $default): int
    {
        if (!function_exists('hotel_config_get')) {
            return $default;
        }
        $v = hotel_config_get($clave, $default, $hotelId);
        return is_numeric($v) ? (int) $v : $default;
    }

    /** Habitaciones activas sucias (estado limpieza) con llegada programada hoy. */
    private function suciasConLlegadaHoy(int $hotelId): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT hab.id)
                 FROM habitaciones hab
                 INNER JOIN reservacion_habitaciones rh ON rh.habitacion_id = hab.id AND rh.hotel_id = hab.hotel_id
                 INNER JOIN reservaciones r ON r.id = rh.reservacion_id AND r.hotel_id = rh.hotel_id
                 WHERE hab.hotel_id = ? AND hab.activa = 1 AND hab.estado = 'limpieza'
                   AND r.fecha_entrada = CURDATE() AND r.estado IN ('confirmada', 'pendiente')"
            );
            $stmt->execute([$hotelId]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Copiloto briefing: error en sucias con llegada: ' . $e->getMessage());
            return 0;
        }
    }

    private function tieneModulo(string $clave, int $hotelId): bool
    {
        return function_exists('hotel_has_module') && hotel_has_module($clave, $hotelId);
    }
}
