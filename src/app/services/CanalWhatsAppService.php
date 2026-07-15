<?php

require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/ConfiguracionHotelRegistry.php';
require_once __DIR__ . '/ReputacionService.php';

/**
 * CanalWhatsAppService (bloque canal_whatsapp, $199)
 *
 * Nivel 1 del canal WhatsApp: envio ASISTIDO por links wa.me. El sistema arma
 * el mensaje con los datos de la reservacion y recepcion lo manda con un toque
 * desde su propio WhatsApp; aqui solo se registra el hecho (tabla
 * mensajes_whatsapp, un timeline por reservacion y tipo).
 *
 * Reglas duras:
 * - Toda query va scopeada por hotel_id (multi-tenant).
 * - CERO escrituras sobre dinero/caja: el mensaje de anticipo es INFORMATIVO
 *   (instrucciones de deposito); registrar el pago sigue siendo trabajo de
 *   Caja/AnticipoService, nunca de este servicio.
 * - White-label: todo texto sale a nombre del hotel, jamas de la plataforma.
 * - El campo `canal` ('manual') deja listo el terreno para un futuro
 *   'cloud_api' sin migrar la tabla.
 */
class CanalWhatsAppService
{
    public const TIPOS = ['confirmacion', 'recordatorio', 'anticipo', 'encuesta'];

    /** Tipos que alimentan la cola "por enviar hoy" (anticipo es contextual, vive en la ficha). */
    public const TIPOS_COLA = ['confirmacion', 'recordatorio', 'encuesta'];

    /** Dias hacia atras que una reserva recien creada espera su confirmacion en la cola. */
    private const DIAS_VENTANA_CONFIRMACION = 7;

    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    // ───────────────────────────── Telefono ─────────────────────────────

    /**
     * Normaliza un telefono al formato que espera wa.me, sin signos:
     * 10 digitos MX -> 521XXXXXXXXXX. Tolera espacios, guiones y +52/+521.
     * Numeros internacionales (11-15 digitos con otra lada) pasan tal cual.
     * Devuelve null si el telefono no es usable.
     */
    public function normalizarTelefono($telefono): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono);

        if ($digitos === '' || $digitos === null) {
            return null;
        }

        $largo = strlen($digitos);

        if ($largo === 10) {
            return '521' . $digitos;
        }

        if ($largo === 12 && strpos($digitos, '52') === 0) {
            return '521' . substr($digitos, 2);
        }

        if ($largo === 13 && strpos($digitos, '521') === 0) {
            return $digitos;
        }

        // Otra lada internacional completa: se respeta tal cual.
        if ($largo >= 11 && $largo <= 15 && strpos($digitos, '52') !== 0) {
            return $digitos;
        }

        return null;
    }

    /** Explicacion humana de por que un telefono no es usable (para mostrar en la cola). */
    public function motivoTelefono($telefono): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono);

        if ($digitos === '' || $digitos === null) {
            return 'Sin teléfono registrado';
        }

        if (strlen($digitos) < 10) {
            return 'Teléfono incompleto (' . strlen($digitos) . ' dígitos)';
        }

        return 'Teléfono no reconocible como número de WhatsApp';
    }

    // ───────────────────────────── Plantillas ─────────────────────────────

    /**
     * Plantillas sugeridas por tipo (espanol calido, a nombre del hotel).
     * El hotel puede sobreescribirlas en su configuracion; vacio = estas.
     */
    public static function plantillasSugeridas(): array
    {
        return [
            'confirmacion' => "¡Hola {huesped}! 🌟 Te saludamos de {hotel}.\n"
                . "Tu reservación está confirmada:\n"
                . "📅 Llegada: {fecha_llegada}\n"
                . "🧳 Salida: {fecha_salida}\n"
                . "🛏️ Habitación: {habitacion}\n"
                . "💵 Total de tu estancia: {total}\n"
                . "El check-in es a partir de las {hora_checkin}. "
                . "Cualquier duda o cambio, escríbenos por aquí. ¡Te esperamos con gusto!",

            'recordatorio' => "¡Hola {huesped}! Mañana te esperamos en {hotel} 🎉\n"
                . "Tu habitación estará lista a partir de las {hora_checkin}.\n"
                . "📍 Cómo llegar: {link_maps}\n"
                . "Si tu hora de llegada cambia, avísanos por aquí. ¡Buen viaje!",

            'anticipo' => "Hola {huesped}, te saludamos de {hotel} 😊\n"
                . "Para asegurar tu reservación del {fecha_llegada} te sugerimos "
                . "un anticipo de {anticipo}. Puedes depositarlo así:\n"
                . "{datos_deposito}\n"
                . "En cuanto lo hagas, mándanos tu comprobante por aquí para "
                . "registrarlo. ¡Gracias!",

            'encuesta' => "¡Gracias por tu visita, {huesped}! 💛 En {hotel} nos "
                . "encantó recibirte.\n"
                . "¿Nos regalas un minuto? Cuéntanos cómo fue tu estancia:\n"
                . "{link_encuesta}\n"
                . "Tu opinión nos ayuda a atenderte aún mejor la próxima vez. ¡Buen viaje!",
        ];
    }

    /** Plantilla efectiva de un tipo para el hotel (propia o sugerida). */
    public function plantilla(string $tipo, int $hotelId): string
    {
        $sugeridas = self::plantillasSugeridas();

        if (!isset($sugeridas[$tipo])) {
            return '';
        }

        $propia = trim((string) ConfiguracionHotelRegistry::get('canal_whatsapp.plantilla_' . $tipo, '', $hotelId));

        return $propia !== '' ? $propia : $sugeridas[$tipo];
    }

    /**
     * Toggles por tipo segun la configuracion del hotel. La encuesta ademas
     * exige el bloque reputacion activo (su link publico vive alla).
     */
    public function tiposActivos(int $hotelId): array
    {
        $activos = [
            'confirmacion' => ConfiguracionHotelRegistry::getBool('canal_whatsapp.confirmacion_activa', true, $hotelId),
            'recordatorio' => ConfiguracionHotelRegistry::getBool('canal_whatsapp.recordatorio_activo', true, $hotelId),
            'anticipo' => ConfiguracionHotelRegistry::getBool('canal_whatsapp.anticipo_activo', true, $hotelId),
            'encuesta' => ConfiguracionHotelRegistry::getBool('canal_whatsapp.encuesta_activa', true, $hotelId),
        ];

        if ($activos['encuesta'] && function_exists('hotel_has_module')) {
            $activos['encuesta'] = (bool) hotel_has_module('reputacion', $hotelId);
        }

        return $activos;
    }

    /**
     * Configuracion que falta para que los tipos ACTIVOS salgan completos
     * (aviso ambar "configuracion incompleta"). Solo reclama una clave si la
     * plantilla efectiva de un tipo activo realmente la usa.
     */
    public function configuracionFaltante(int $hotelId): array
    {
        $faltantes = [];
        $activos = $this->tiposActivos($hotelId);

        $reclama = function (string $tipo, string $variable) use ($activos, $hotelId): bool {
            return !empty($activos[$tipo])
                && strpos($this->plantilla($tipo, $hotelId), '{' . $variable . '}') !== false;
        };

        if ($reclama('anticipo', 'datos_deposito')
            && trim((string) ConfiguracionHotelRegistry::get('canal_whatsapp.datos_deposito', '', $hotelId)) === '') {
            $faltantes[] = 'datos_deposito';
        }

        if ($reclama('recordatorio', 'link_maps')
            && trim((string) ConfiguracionHotelRegistry::get('canal_whatsapp.link_maps', '', $hotelId)) === '') {
            $faltantes[] = 'link_maps';
        }

        return $faltantes;
    }

    // ───────────────────────────── Composicion ─────────────────────────────

    /**
     * Rellena la plantilla del tipo con los datos de la reservacion.
     * $reservacion espera: id, huesped_nombre, fecha_entrada, fecha_salida,
     * precio_total y habitaciones (lista "101, 102" o null).
     *
     * $generarLinks: true solo al ENVIAR (puede escribir el token de encuesta
     * via ReputacionService, que es idempotente); en previews va false y el
     * link pendiente se muestra como nota.
     *
     * Devuelve ['texto' => string, 'faltantes' => string[]] donde faltantes
     * son variables de configuracion del hotel sin capturar que la plantilla
     * necesita (con faltantes el mensaje NO debe enviarse).
     */
    public function componer(string $tipo, array $reservacion, int $hotelId, bool $generarLinks = false): array
    {
        $plantilla = $this->plantilla($tipo, $hotelId);
        $faltantes = [];

        $variables = [
            '{huesped}' => $this->primerNombre((string) ($reservacion['huesped_nombre'] ?? '')),
            '{hotel}' => $this->nombreHotel($hotelId),
            '{fecha_llegada}' => $this->fechaHumana((string) ($reservacion['fecha_entrada'] ?? '')),
            '{fecha_salida}' => $this->fechaHumana((string) ($reservacion['fecha_salida'] ?? '')),
            '{habitacion}' => trim((string) ($reservacion['habitaciones'] ?? '')) !== ''
                ? (string) $reservacion['habitaciones']
                : 'por asignar',
            '{total}' => $this->dinero((float) ($reservacion['precio_total'] ?? 0)),
            '{hora_checkin}' => $this->horaHumana(
                (string) ConfiguracionHotelRegistry::get('operacion.checkin_hora', '15:00', $hotelId)
            ),
        ];

        if (strpos($plantilla, '{anticipo}') !== false) {
            $variables['{anticipo}'] = $this->dinero($this->anticipoSugerido($hotelId, $reservacion));
        }

        if (strpos($plantilla, '{datos_deposito}') !== false) {
            $datos = trim((string) ConfiguracionHotelRegistry::get('canal_whatsapp.datos_deposito', '', $hotelId));
            if ($datos === '') {
                $faltantes[] = 'datos_deposito';
                $datos = '[falta capturar los datos de depósito del hotel]';
            }
            $variables['{datos_deposito}'] = $datos;
        }

        if (strpos($plantilla, '{link_maps}') !== false) {
            $maps = trim((string) ConfiguracionHotelRegistry::get('canal_whatsapp.link_maps', '', $hotelId));
            if ($maps === '') {
                $faltantes[] = 'link_maps';
                $maps = '[falta capturar el link de Maps del hotel]';
            }
            $variables['{link_maps}'] = $maps;
        }

        if (strpos($plantilla, '{link_encuesta}') !== false) {
            $link = $this->linkEncuesta($hotelId, (int) ($reservacion['id'] ?? 0), $generarLinks);
            if ($link === null) {
                if ($generarLinks) {
                    // Al enviar, sin link real no hay mensaje que valga.
                    $faltantes[] = 'link_encuesta';
                    $link = '[no se pudo generar el link de la encuesta]';
                } else {
                    $link = '(tu link de encuesta se genera al enviar)';
                }
            }
            $variables['{link_encuesta}'] = $link;
        }

        return [
            'texto' => strtr($plantilla, $variables),
            'faltantes' => $faltantes,
        ];
    }

    /** Link wa.me listo para abrir: telefono normalizado + texto URL-encoded (UTF-8). */
    public function linkWa(string $telefono, string $texto): string
    {
        return 'https://wa.me/' . $telefono . '?text=' . rawurlencode($texto);
    }

    /**
     * Anticipo sugerido (INFORMATIVO) reutilizando la regla de anticipos del
     * hotel (motor.anticipo_tipo/valor), topado al saldo pendiente real para
     * jamas sugerir mas de lo que se debe. Solo lecturas.
     */
    public function anticipoSugerido(int $hotelId, array $reservacion): float
    {
        $total = (float) ($reservacion['precio_total'] ?? 0);
        if ($total <= 0) {
            return 0.0;
        }

        $tipo = (string) ConfiguracionHotelRegistry::get('motor.anticipo_tipo', 'porcentaje', $hotelId);
        $valor = (float) ConfiguracionHotelRegistry::get('motor.anticipo_valor', 30.0, $hotelId);

        switch ($tipo) {
            case 'monto_fijo':
                $sugerido = $valor;
                break;

            case 'primera_noche':
                $noches = 1;
                $entrada = strtotime((string) ($reservacion['fecha_entrada'] ?? ''));
                $salida = strtotime((string) ($reservacion['fecha_salida'] ?? ''));
                if ($entrada && $salida && $salida > $entrada) {
                    $noches = max(1, (int) round(($salida - $entrada) / 86400));
                }
                $sugerido = $total / $noches;
                break;

            case 'porcentaje':
            default:
                $sugerido = $total * ($valor / 100);
                break;
        }

        $saldo = $this->saldoPendiente($hotelId, (int) ($reservacion['id'] ?? 0), $total);

        return round(max(0.0, min($sugerido, $saldo)), 2);
    }

    /** Saldo pendiente de la reservacion via el resumen oficial del modelo (solo lectura). */
    public function saldoPendiente(int $hotelId, int $reservacionId, float $fallbackTotal): float
    {
        if ($reservacionId <= 0) {
            return round(max(0.0, $fallbackTotal), 2);
        }

        try {
            $reservacionModel = new Reservacion();
            $resumen = $reservacionModel->resumenPagos($reservacionId, $hotelId);
            return (float) ($resumen['saldo'] ?? $fallbackTotal);
        } catch (Throwable $e) {
            error_log('CanalWhatsApp: no se pudo leer el saldo de la reservacion ' . $reservacionId . ': ' . $e->getMessage());
            return round(max(0.0, $fallbackTotal), 2);
        }
    }

    // ───────────────────────────── Cola del dia ─────────────────────────────

    /**
     * Candidatos "por enviar hoy" de los tipos de cola activos:
     * - confirmacion: reservas confirmadas creadas estos dias, sin mensaje.
     * - recordatorio: llegadas de MANANA aun confirmadas.
     * - encuesta: salidas de HOY ya con check-out (bloque reputacion).
     * Cada item trae el texto compuesto y el link wa.me si el telefono sirve.
     */
    public function pendientesDeHoy(int $hotelId): array
    {
        $items = [];
        $activos = $this->tiposActivos($hotelId);

        foreach (self::TIPOS_COLA as $tipo) {
            if (empty($activos[$tipo])) {
                continue;
            }

            foreach ($this->candidatos($hotelId, $tipo) as $fila) {
                $items[] = $this->armarItem($hotelId, $tipo, $fila);
            }
        }

        return $items;
    }

    /**
     * Cuantos mensajes esperan accion hoy (burbuja del sidebar, dashboard y
     * copiloto). Mismas condiciones que pendientesDeHoy, en COUNT baratos.
     */
    public function contarPendientesHoy(int $hotelId): int
    {
        $total = 0;
        $activos = $this->tiposActivos($hotelId);

        foreach (self::TIPOS_COLA as $tipo) {
            if (empty($activos[$tipo])) {
                continue;
            }

            $where = $this->condicionesTipo($tipo);
            if ($where === null) {
                continue;
            }

            try {
                $stmt = $this->db->query(
                    "SELECT COUNT(*) AS n
                     FROM reservaciones r
                     WHERE r.hotel_id = ? AND {$where} AND " . $this->sinMensajeSql(),
                    [$hotelId, $tipo]
                );
                $row = $stmt ? $stmt->fetch() : null;
                $total += (int) ($row['n'] ?? 0);
            } catch (Throwable $e) {
                error_log('CanalWhatsApp: fallo el conteo de pendientes (' . $tipo . '): ' . $e->getMessage());
            }
        }

        return $total;
    }

    /** Timeline reciente del hotel: que se envio/descarto, a quien y cuando. */
    public function historial(int $hotelId, int $limite = 60): array
    {
        $limite = max(1, min(200, $limite));

        try {
            $stmt = $this->db->query(
                "SELECT mw.id, mw.reservacion_id, mw.tipo, mw.telefono, mw.contenido,
                        mw.estado, mw.canal, mw.motivo, mw.enviado_en, mw.updated_at,
                        h.nombre_completo AS huesped_nombre,
                        u.nombre_completo AS usuario_nombre
                 FROM mensajes_whatsapp mw
                 INNER JOIN reservaciones r ON r.id = mw.reservacion_id AND r.hotel_id = mw.hotel_id
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 LEFT JOIN usuarios u ON u.id = mw.enviado_por
                 WHERE mw.hotel_id = ?
                 ORDER BY COALESCE(mw.enviado_en, mw.updated_at) DESC
                 LIMIT {$limite}",
                [$hotelId]
            );

            return $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable $e) {
            error_log('CanalWhatsApp: fallo el historial: ' . $e->getMessage());
            return [];
        }
    }

    /** Mensajes de UNA reservacion como mapa tipo => fila (para la ficha). */
    public function porReservacion(int $hotelId, int $reservacionId): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT tipo, telefono, contenido, estado, canal, motivo, enviado_por, enviado_en
                 FROM mensajes_whatsapp
                 WHERE hotel_id = ? AND reservacion_id = ?",
                [$hotelId, $reservacionId]
            );

            $mapa = [];
            foreach (($stmt ? $stmt->fetchAll() : []) as $fila) {
                $mapa[(string) $fila['tipo']] = $fila;
            }

            return $mapa;
        } catch (Throwable $e) {
            error_log('CanalWhatsApp: fallo porReservacion: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Que tipos tienen sentido para esta reservacion segun su estado (la cola
     * y la ficha ofrecen solo estos):
     * confirmacion/recordatorio/anticipo antes de llegar; encuesta tras salir.
     * El anticipo ademas exige saldo pendiente.
     */
    public function tiposParaReservacion(int $hotelId, array $reservacion): array
    {
        $estado = (string) ($reservacion['estado'] ?? '');
        $activos = $this->tiposActivos($hotelId);
        $tipos = [];

        foreach (self::TIPOS as $tipo) {
            if (empty($activos[$tipo])) {
                continue;
            }

            if (in_array($tipo, ['confirmacion', 'recordatorio', 'anticipo'], true) && $estado !== 'confirmada') {
                continue;
            }

            if ($tipo === 'encuesta' && $estado !== 'checked_out') {
                continue;
            }

            if ($tipo === 'anticipo'
                && $this->saldoPendiente($hotelId, (int) ($reservacion['id'] ?? 0), (float) ($reservacion['precio_total'] ?? 0)) <= 0.004) {
                continue;
            }

            $tipos[] = $tipo;
        }

        return $tipos;
    }

    // ───────────────────────────── Acciones ─────────────────────────────

    /**
     * Registra el envio de un mensaje (la unica escritura de este bloque,
     * siempre sobre mensajes_whatsapp). Revalida todo en servidor:
     * - estado de la reservacion acorde al tipo;
     * - telefono usable (si no, la fila nace 'descartado' con motivo visible);
     * - configuracion completa (si falta, NO escribe y avisa que capturar).
     * Devuelve ['success', 'link', 'texto', 'motivo', 'descartado', 'faltantes'].
     */
    public function marcarEnviado(int $hotelId, int $reservacionId, string $tipo, ?int $usuarioId): array
    {
        if (!in_array($tipo, self::TIPOS, true)) {
            return ['success' => false, 'motivo' => 'Tipo de mensaje desconocido.'];
        }

        $reservacion = $this->cargarReservacion($hotelId, $reservacionId);
        if (!$reservacion) {
            return ['success' => false, 'motivo' => 'Reservación no encontrada para este hotel.'];
        }

        if (!in_array($tipo, $this->tiposParaReservacion($hotelId, $reservacion), true)) {
            return ['success' => false, 'motivo' => 'Este mensaje ya no aplica para el estado actual de la reservación.'];
        }

        $telefonoWa = $this->normalizarTelefono($reservacion['huesped_telefono'] ?? null);
        if ($telefonoWa === null) {
            $motivo = $this->motivoTelefono($reservacion['huesped_telefono'] ?? null);
            $this->guardarMensaje($hotelId, $reservacionId, $tipo, [
                'estado' => 'descartado',
                'telefono' => null,
                'contenido' => null,
                'motivo' => $motivo,
                'usuario_id' => $usuarioId,
            ]);

            return ['success' => false, 'descartado' => true, 'motivo' => $motivo];
        }

        $composicion = $this->componer($tipo, $reservacion, $hotelId, true);
        if (!empty($composicion['faltantes'])) {
            return [
                'success' => false,
                'faltantes' => $composicion['faltantes'],
                'motivo' => 'Falta configurar: ' . implode(', ', $composicion['faltantes']) . '. Captúralo en Mensajes → Configuración.',
            ];
        }

        $this->guardarMensaje($hotelId, $reservacionId, $tipo, [
            'estado' => 'enviado',
            'telefono' => $telefonoWa,
            'contenido' => $composicion['texto'],
            'motivo' => null,
            'usuario_id' => $usuarioId,
        ]);

        return [
            'success' => true,
            'link' => $this->linkWa($telefonoWa, $composicion['texto']),
            'texto' => $composicion['texto'],
        ];
    }

    /**
     * Descarta un mensaje pendiente (no todos los huespedes quieren WhatsApp).
     * Nunca pisa un envio ya registrado.
     */
    public function descartar(int $hotelId, int $reservacionId, string $tipo, ?int $usuarioId, string $motivo = ''): array
    {
        if (!in_array($tipo, self::TIPOS, true)) {
            return ['success' => false, 'motivo' => 'Tipo de mensaje desconocido.'];
        }

        $reservacion = $this->cargarReservacion($hotelId, $reservacionId);
        if (!$reservacion) {
            return ['success' => false, 'motivo' => 'Reservación no encontrada para este hotel.'];
        }

        $existente = $this->porReservacion($hotelId, $reservacionId)[$tipo] ?? null;
        if ($existente && (string) $existente['estado'] === 'enviado') {
            return ['success' => false, 'motivo' => 'Este mensaje ya se había enviado; no se puede descartar.'];
        }

        $motivo = trim($motivo) !== '' ? trim($motivo) : 'Descartado por recepción';

        $this->guardarMensaje($hotelId, $reservacionId, $tipo, [
            'estado' => 'descartado',
            'telefono' => $this->normalizarTelefono($reservacion['huesped_telefono'] ?? null),
            'contenido' => null,
            'motivo' => mb_substr($motivo, 0, 200),
            'usuario_id' => $usuarioId,
        ]);

        return ['success' => true];
    }

    // ───────────────────────────── Privados ─────────────────────────────

    /** Condicion WHERE (sobre alias r) que define a los candidatos de cada tipo de la cola. */
    private function condicionesTipo(string $tipo): ?string
    {
        switch ($tipo) {
            case 'confirmacion':
                // Reservas nuevas sin confirmar por WhatsApp. La ventana de dias
                // evita inundar la cola con historia vieja al activar el bloque.
                return "r.estado = 'confirmada'
                        AND r.fecha_entrada >= CURDATE()
                        AND r.created_at >= CURDATE() - INTERVAL " . self::DIAS_VENTANA_CONFIRMACION . " DAY";

            case 'recordatorio':
                return "r.estado = 'confirmada' AND r.fecha_entrada = CURDATE() + INTERVAL 1 DAY";

            case 'encuesta':
                return "r.estado = 'checked_out' AND r.fecha_salida = CURDATE()";
        }

        return null;
    }

    /** Un candidato deja de serlo cuando su mensaje de ese tipo ya se envio o descarto. */
    private function sinMensajeSql(): string
    {
        return "NOT EXISTS (
                    SELECT 1 FROM mensajes_whatsapp mw
                    WHERE mw.hotel_id = r.hotel_id
                      AND mw.reservacion_id = r.id
                      AND mw.tipo = ?
                      AND mw.estado IN ('enviado', 'descartado')
                )";
    }

    /** Filas candidatas de un tipo, con huesped y habitaciones (scope de hotel). */
    private function candidatos(int $hotelId, string $tipo): array
    {
        $where = $this->condicionesTipo($tipo);
        if ($where === null) {
            return [];
        }

        try {
            $stmt = $this->db->query(
                "SELECT r.id, r.fecha_entrada, r.fecha_salida, r.precio_total,
                        r.estado, r.created_at,
                        h.nombre_completo AS huesped_nombre,
                        h.telefono AS huesped_telefono,
                        GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones
                 FROM reservaciones r
                 INNER JOIN huespedes h ON r.huesped_id = h.id
                 LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                 LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ? AND {$where} AND " . $this->sinMensajeSql() . "
                 GROUP BY r.id
                 ORDER BY r.fecha_entrada, r.id
                 LIMIT 100",
                [$hotelId, $tipo]
            );

            return $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable $e) {
            error_log('CanalWhatsApp: fallo la cola (' . $tipo . '): ' . $e->getMessage());
            return [];
        }
    }

    /** Item de la cola listo para pintar: texto, telefono normalizado y link si se puede. */
    private function armarItem(int $hotelId, string $tipo, array $fila): array
    {
        $telefonoWa = $this->normalizarTelefono($fila['huesped_telefono'] ?? null);
        $composicion = $this->componer($tipo, $fila, $hotelId, false);

        return [
            'reservacion_id' => (int) $fila['id'],
            'tipo' => $tipo,
            'huesped_nombre' => (string) ($fila['huesped_nombre'] ?? ''),
            'telefono' => (string) ($fila['huesped_telefono'] ?? ''),
            'telefono_wa' => $telefonoWa,
            'motivo_telefono' => $telefonoWa === null ? $this->motivoTelefono($fila['huesped_telefono'] ?? null) : '',
            'fecha_entrada' => (string) ($fila['fecha_entrada'] ?? ''),
            'fecha_salida' => (string) ($fila['fecha_salida'] ?? ''),
            'habitaciones' => (string) ($fila['habitaciones'] ?? ''),
            'precio_total' => (float) ($fila['precio_total'] ?? 0),
            'texto' => $composicion['texto'],
            'faltantes' => $composicion['faltantes'],
        ];
    }

    /** Reservacion + huesped + habitaciones para componer/validar (scope de hotel). */
    private function cargarReservacion(int $hotelId, int $reservacionId): ?array
    {
        if ($hotelId <= 0 || $reservacionId <= 0) {
            return null;
        }

        try {
            $stmt = $this->db->query(
                "SELECT r.id, r.estado, r.fecha_entrada, r.fecha_salida, r.precio_total,
                        h.nombre_completo AS huesped_nombre,
                        h.telefono AS huesped_telefono,
                        GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones
                 FROM reservaciones r
                 INNER JOIN huespedes h ON r.huesped_id = h.id
                 LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                 LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ? AND r.id = ?
                 GROUP BY r.id
                 LIMIT 1",
                [$hotelId, $reservacionId]
            );

            $fila = $stmt ? $stmt->fetch() : null;
            return $fila ?: null;
        } catch (Throwable $e) {
            error_log('CanalWhatsApp: fallo cargarReservacion ' . $reservacionId . ': ' . $e->getMessage());
            return null;
        }
    }

    /** Unica escritura del servicio: upsert del timeline (hotel + reservacion + tipo unicos). */
    private function guardarMensaje(int $hotelId, int $reservacionId, string $tipo, array $datos): void
    {
        $esEnviado = ($datos['estado'] ?? '') === 'enviado';

        $this->db->query(
            "INSERT INTO mensajes_whatsapp
                (hotel_id, reservacion_id, tipo, telefono, contenido, estado, canal, motivo, enviado_por, enviado_en, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 'manual', ?, ?, " . ($esEnviado ? 'NOW()' : 'NULL') . ", NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                telefono = VALUES(telefono),
                contenido = VALUES(contenido),
                estado = VALUES(estado),
                canal = VALUES(canal),
                motivo = VALUES(motivo),
                enviado_por = VALUES(enviado_por),
                enviado_en = " . ($esEnviado ? 'NOW()' : 'NULL') . ",
                updated_at = NOW()",
            [
                $hotelId,
                $reservacionId,
                $tipo,
                $datos['telefono'] ?? null,
                $datos['contenido'] ?? null,
                $datos['estado'] ?? 'pendiente',
                $datos['motivo'] ?? null,
                $datos['usuario_id'] ?? null,
            ]
        );
    }

    /** Link publico de la encuesta post-estancia (mecanismo del bloque reputacion). */
    private function linkEncuesta(int $hotelId, int $reservacionId, bool $generar): ?string
    {
        if ($reservacionId <= 0) {
            return null;
        }

        try {
            $token = null;

            if ($generar) {
                // Idempotente: reusa la encuesta si ya existia.
                $token = (new ReputacionService($this->db))->generarLink($hotelId, $reservacionId);
            } else {
                $stmt = $this->db->query(
                    "SELECT token FROM reputacion_encuestas
                     WHERE hotel_id = ? AND reservacion_id = ? LIMIT 1",
                    [$hotelId, $reservacionId]
                );
                $fila = $stmt ? $stmt->fetch() : null;
                $token = $fila['token'] ?? null;
            }

            if (!$token) {
                return null;
            }

            $slug = $this->slugHotel($hotelId);
            if ($slug === '') {
                return null;
            }

            $ruta = 'h/' . $slug . '/encuesta/' . $token;

            // En CLI (tests/crons) no hay host que resolver: link relativo util.
            if (function_exists('url') && !empty($_SERVER['HTTP_HOST'])) {
                try {
                    return url($ruta);
                } catch (Throwable $e) {
                    // base_url irresoluble: cae al relativo.
                }
            }

            return '/' . $ruta;
        } catch (Throwable $e) {
            error_log('CanalWhatsApp: fallo linkEncuesta (reservacion ' . $reservacionId . '): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Nombre publico del hotel para los mensajes. White-label estricto: nombre
     * visual del branding y si no, el nombre del hotel; jamas la plataforma.
     */
    private function nombreHotel(int $hotelId): string
    {
        static $cache = [];

        if (isset($cache[$hotelId])) {
            return $cache[$hotelId];
        }

        $nombre = '';

        try {
            $stmt = $this->db->query(
                "SELECT b.nombre_visual, ho.nombre
                 FROM hoteles ho
                 LEFT JOIN hotel_branding b ON b.hotel_id = ho.id
                 WHERE ho.id = ? LIMIT 1",
                [$hotelId]
            );
            $fila = $stmt ? $stmt->fetch() : null;

            if ($fila) {
                $nombre = trim((string) ($fila['nombre_visual'] ?? ''));
                if ($nombre === '') {
                    $nombre = trim((string) ($fila['nombre'] ?? ''));
                }
            }
        } catch (Throwable $e) {
            error_log('CanalWhatsApp: fallo nombreHotel: ' . $e->getMessage());
        }

        return $cache[$hotelId] = ($nombre !== '' ? $nombre : 'tu hotel');
    }

    /** Slug del hotel para links publicos. */
    private function slugHotel(int $hotelId): string
    {
        static $cache = [];

        if (isset($cache[$hotelId])) {
            return $cache[$hotelId];
        }

        try {
            $stmt = $this->db->query("SELECT slug FROM hoteles WHERE id = ? LIMIT 1", [$hotelId]);
            $fila = $stmt ? $stmt->fetch() : null;
            return $cache[$hotelId] = trim((string) ($fila['slug'] ?? ''));
        } catch (Throwable $e) {
            return $cache[$hotelId] = '';
        }
    }

    /** Primer nombre del huesped para saludar con calidez ("Hola María"). */
    private function primerNombre(string $nombreCompleto): string
    {
        $nombreCompleto = trim($nombreCompleto);
        if ($nombreCompleto === '') {
            return 'huésped';
        }

        $partes = preg_split('/\s+/', $nombreCompleto);
        return $partes[0] !== '' ? $partes[0] : $nombreCompleto;
    }

    /** "martes 15 de julio" (con año solo si no es el año en curso). */
    private function fechaHumana(string $fecha): string
    {
        $ts = strtotime($fecha);
        if (!$ts) {
            return $fecha;
        }

        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        $texto = $dias[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' de ' . $meses[(int) date('n', $ts)];

        if (date('Y', $ts) !== date('Y')) {
            $texto .= ' de ' . date('Y', $ts);
        }

        return $texto;
    }

    /** "15:00" -> "3:00 pm" para leerse como se dice. */
    private function horaHumana(string $hora): string
    {
        $ts = strtotime('2000-01-01 ' . trim($hora));
        if (!$ts) {
            return $hora;
        }

        return date('g:i', $ts) . ' ' . (date('a', $ts) === 'am' ? 'am' : 'pm');
    }

    /** Formato de dinero del sistema: $1,234.50 */
    private function dinero(float $monto): string
    {
        return '$' . number_format($monto, 2, '.', ',');
    }
}
