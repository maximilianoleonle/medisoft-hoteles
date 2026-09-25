<?php

require_once __DIR__ . '/../models/ConfiguracionHotelRegistry.php';
require_once __DIR__ . '/NotificacionService.php';

/**
 * EstanciaService — QR en la habitacion (dentro del bloque reputacion).
 *
 * Cada habitacion tiene un token: el QR impreso que se pega en el cuarto. Con
 * el QR el huesped ve el Wi-Fi, los horarios y la informacion del hotel, y
 * MIENTRAS la habitacion tiene una reservacion en check-in puede pedir
 * toallas, amenidades o limpieza, o avisar que algo no funciona (con foto).
 * La peticion cae en la bandeja /peticiones y avisa al personal por el canal
 * de siempre (campana + push por rol, via NotificacionService).
 *
 * Reglas duras:
 * - Toda query va scopeada por hotel_id (multi-tenant).
 * - La pagina publica JAMAS muestra datos del huesped: el QR vive pegado en
 *   el cuarto y lo escanea cualquiera que entre.
 * - Una peticion NO cambia el estado de la habitacion ni crea mantenimientos
 *   sola: iniciar un mantenimiento bloquea el cuarto y aqui el cuarto esta
 *   ocupado. El personal decide desde la bandeja.
 * - Cero dinero: nada de caja, abonos ni cargos.
 */
class EstanciaService
{
    /** Bloque comercial que enciende el QR. Cambiarlo aqui lo cambia en todos lados. */
    public const MODULO = 'reputacion';

    /**
     * Lo que el huesped puede pedir. 'rol' decide a quien le llega el aviso
     * (PwaPushService::expandirRolesDestino ya suma recepcion y gerencia);
     * 'severidad' alta hace que llegue aunque el hotel solo quiera push
     * prioritario.
     */
    public const TIPOS = [
        'toallas' => [
            'etiqueta' => 'Toallas o blancos',
            'corta' => 'Toallas',
            'icono' => 'fa-bath',
            'rol' => 'limpieza',
            'severidad' => 'media',
            'pide_detalle' => false,
            'permite_foto' => false,
            'ayuda' => '¿Cuántas o qué te hace falta? (opcional)',
        ],
        'amenidades' => [
            'etiqueta' => 'Jabón, papel o amenidades',
            'corta' => 'Amenidades',
            'icono' => 'fa-soap',
            'rol' => 'limpieza',
            'severidad' => 'media',
            'pide_detalle' => false,
            'permite_foto' => false,
            'ayuda' => '¿Qué te hace falta? (opcional)',
        ],
        'limpieza' => [
            'etiqueta' => 'Limpieza de la habitación',
            'corta' => 'Limpieza',
            'icono' => 'fa-broom',
            'rol' => 'limpieza',
            'severidad' => 'media',
            'pide_detalle' => false,
            'permite_foto' => false,
            'ayuda' => '¿A qué hora te acomoda? (opcional)',
        ],
        'mantenimiento' => [
            'etiqueta' => 'Algo no funciona',
            'corta' => 'Algo no funciona',
            'icono' => 'fa-screwdriver-wrench',
            'rol' => 'mantenimiento',
            'severidad' => 'alta',
            'pide_detalle' => true,
            'permite_foto' => true,
            'ayuda' => '¿Qué no funciona? Ej.: no sale agua caliente, la TV no prende',
        ],
        'otro' => [
            'etiqueta' => 'Otra petición',
            'corta' => 'Otra petición',
            'icono' => 'fa-bell-concierge',
            'rol' => 'recepcionista',
            'severidad' => 'media',
            'pide_detalle' => true,
            'permite_foto' => false,
            'ayuda' => 'Cuéntanos qué necesitas',
        ],
    ];

    public const ESTADOS_ABIERTOS = ['nueva', 'en_atencion'];

    /** accion => [estados desde los que aplica, estado resultante]. */
    private const TRANSICIONES = [
        'atender' => [['nueva'], 'en_atencion'],
        'resolver' => [['nueva', 'en_atencion'], 'resuelta'],
        'descartar' => [['nueva', 'en_atencion'], 'descartada'],
    ];

    /** Tope de peticiones por habitacion en una hora (freno a bromas y abuso). */
    public const MAX_POR_HORA_HABITACION = 8;

    /** Una peticion identica y abierta dentro de esta ventana es un doble toque. */
    public const VENTANA_DUPLICADO_MIN = 15;

    public const DETALLE_MAX = 500;
    public const FOTO_MAX_BYTES = 8 * 1024 * 1024;

    private const FOTO_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private $db;
    private $pdo;
    private $baseStorage;

    public function __construct(?Database $db = null, ?string $baseStorage = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
        $this->baseStorage = $baseStorage !== null && $baseStorage !== ''
            ? rtrim($baseStorage, '/')
            : (defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 2) . '/storage');
    }

    /**
     * ¿El hotel tiene el QR? Con hotel explicito consulta ese hotel (panel,
     * pagina publica, crons); sin el usa la semantica de menu del hotel de la
     * sesion. Guard > 0: hotel_has_module trata 0 como "el de la sesion".
     */
    public static function disponible(?int $hotelId = null): bool
    {
        if ($hotelId !== null) {
            return $hotelId > 0
                && function_exists('hotel_has_module')
                && (bool) hotel_has_module(self::MODULO, $hotelId);
        }

        if (function_exists('hotel_menu_module_enabled')) {
            return (bool) hotel_menu_module_enabled(self::MODULO);
        }

        return function_exists('current_hotel_has_module') && (bool) current_hotel_has_module(self::MODULO);
    }

    // ───────────────────────────── Tokens / QR ─────────────────────────────

    /** Token del QR de una habitacion activa del hotel; lo crea si falta y $crear. */
    public function tokenHabitacion(int $hotelId, int $habitacionId, bool $crear = true, ?int $usuarioId = null): ?string
    {
        if ($hotelId <= 0 || $habitacionId <= 0) {
            return null;
        }

        $token = $this->leerToken($hotelId, $habitacionId);
        if ($token !== null || !$crear) {
            return $token;
        }

        if (!$this->habitacionActivaDelHotel($hotelId, $habitacionId)) {
            return null;
        }

        // INSERT IGNORE + relectura: si dos pestañas lo crean a la vez, gana
        // la primera (UNIQUE hotel+habitacion) y ambas devuelven el mismo.
        $this->db->query(
            "INSERT IGNORE INTO estancia_qr (hotel_id, habitacion_id, token, generado_por, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())",
            [$hotelId, $habitacionId, self::nuevoToken(), $usuarioId]
        );

        return $this->leerToken($hotelId, $habitacionId);
    }

    /**
     * Habitaciones activas del hotel con su token (creando los que falten si
     * $crear). Orden de impresion: piso y numero.
     */
    public function habitacionesConToken(int $hotelId, bool $crear = true, ?int $usuarioId = null): array
    {
        if ($hotelId <= 0) {
            return [];
        }

        $sql = "SELECT h.id, h.numero, h.piso, h.tipo, q.token, q.updated_at AS token_actualizado
                FROM habitaciones h
                LEFT JOIN estancia_qr q ON q.habitacion_id = h.id AND q.hotel_id = h.hotel_id
                WHERE h.hotel_id = ? AND h.activa = 1
                ORDER BY h.piso, CAST(h.numero AS UNSIGNED), h.numero";

        $stmt = $this->db->query($sql, [$hotelId]);
        $filas = $stmt ? $stmt->fetchAll() : [];

        if (!$crear) {
            return $filas;
        }

        $faltaron = false;
        foreach ($filas as $fila) {
            if (empty($fila['token'])) {
                $this->tokenHabitacion($hotelId, (int) $fila['id'], true, $usuarioId);
                $faltaron = true;
            }
        }

        if (!$faltaron) {
            return $filas;
        }

        $stmt = $this->db->query($sql, [$hotelId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /** Cambia el token (el QR impreso anterior deja de funcionar). Devuelve el nuevo. */
    public function regenerarToken(int $hotelId, int $habitacionId, ?int $usuarioId = null): ?string
    {
        if (!$this->habitacionActivaDelHotel($hotelId, $habitacionId)) {
            return null;
        }

        if ($this->leerToken($hotelId, $habitacionId) === null) {
            return $this->tokenHabitacion($hotelId, $habitacionId, true, $usuarioId);
        }

        $nuevo = self::nuevoToken();
        $this->db->query(
            "UPDATE estancia_qr SET token = ?, generado_por = ?, updated_at = NOW()
             WHERE hotel_id = ? AND habitacion_id = ?",
            [$nuevo, $usuarioId, $hotelId, $habitacionId]
        );

        return $this->leerToken($hotelId, $habitacionId);
    }

    /** Habitacion (activa) que corresponde a un token del hotel, o null. */
    public function porToken(int $hotelId, string $token): ?array
    {
        if ($hotelId <= 0 || !self::tokenValido($token)) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT h.id AS habitacion_id, h.numero, h.piso, h.tipo
             FROM estancia_qr q
             INNER JOIN habitaciones h ON h.id = q.habitacion_id AND h.hotel_id = q.hotel_id
             WHERE q.hotel_id = ? AND q.token = ? AND h.activa = 1
             LIMIT 1",
            [$hotelId, $token]
        );

        $fila = $stmt ? $stmt->fetch() : null;
        return $fila ?: null;
    }

    /** Link publico del QR de una habitacion (absoluto en web, relativo en CLI). */
    public function linkPublico(int $hotelId, int $habitacionId, bool $crear = true): ?string
    {
        $token = $this->tokenHabitacion($hotelId, $habitacionId, $crear);
        if ($token === null) {
            return null;
        }

        $slug = $this->slugHotel($hotelId);
        if ($slug === '') {
            return null;
        }

        return self::urlAbsoluta(self::rutaPublica($slug, $token));
    }

    public static function rutaPublica(string $slug, string $token): string
    {
        return 'h/' . $slug . '/estancia/' . $token;
    }

    /** En web devuelve la URL completa (lo que va en el QR); en CLI, la ruta relativa. */
    public static function urlAbsoluta(string $ruta): string
    {
        if (function_exists('url') && !empty($_SERVER['HTTP_HOST'])) {
            try {
                return url($ruta);
            } catch (Throwable $e) {
                // base_url irresoluble: cae al relativo.
            }
        }

        return '/' . ltrim($ruta, '/');
    }

    public static function tokenValido(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $token);
    }

    /**
     * Codigo QR en SVG (sin librerias externas: reusa el codificador 2D del
     * TCPDF que ya viaja en el repo). Un solo <path> con las corridas
     * horizontales de cada fila: pesa ~2 KB en vez de un <rect> por modulo.
     * Incluye la zona de silencio de 4 modulos que exige el estandar.
     */
    public static function qrSvg(string $texto, string $etiqueta = 'Código QR'): string
    {
        if (!class_exists('TCPDF2DBarcode')) {
            require_once dirname(__DIR__) . '/libs/TCPDF/tcpdf_barcodes_2d.php';
        }

        // El codificador del TCPDF elige la mascara probando 2 al AZAR
        // (QR_FIND_FROM_RANDOM): el mismo link salia con un dibujo distinto
        // en cada impresion. Ambos se escanean, pero el QR pegado en el
        // cuarto debe ser reproducible: se siembra con el propio texto y
        // despues se vuelve a sembrar al azar para no dejar predecible el
        // rand() del resto del request.
        mt_srand(crc32($texto));
        try {
            $codigo = new TCPDF2DBarcode($texto, 'QRCODE,M');
            $matriz = $codigo->getBarcodeArray();
        } finally {
            mt_srand();
        }

        if (!is_array($matriz) || empty($matriz['bcode'])) {
            return '';
        }

        $margen = 4;
        $filas = (int) $matriz['num_rows'];
        $columnas = (int) $matriz['num_cols'];
        $ancho = $columnas + 2 * $margen;
        $alto = $filas + 2 * $margen;
        $trazo = '';

        for ($y = 0; $y < $filas; $y++) {
            $x = 0;
            while ($x < $columnas) {
                if (empty($matriz['bcode'][$y][$x])) {
                    $x++;
                    continue;
                }

                $inicio = $x;
                while ($x < $columnas && !empty($matriz['bcode'][$y][$x])) {
                    $x++;
                }

                $trazo .= 'M' . ($inicio + $margen) . ' ' . ($y + $margen) . 'h' . ($x - $inicio) . 'v1h-' . ($x - $inicio) . 'z';
            }
        }

        $etiquetaSegura = htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $ancho . ' ' . $alto . '"'
            . ' role="img" aria-label="' . $etiquetaSegura . '" shape-rendering="crispEdges">'
            . '<rect width="' . $ancho . '" height="' . $alto . '" fill="#fff"/>'
            . '<path d="' . $trazo . '" fill="#000"/>'
            . '</svg>';
    }

    // ───────────────────────────── Estancia activa ─────────────────────────────

    /**
     * Reservacion en check-in de la habitacion (la estancia "de ahora"), o
     * null. Sin estancia activa el QR solo informa: no acepta peticiones.
     */
    public function estanciaActiva(int $hotelId, int $habitacionId): ?array
    {
        if ($hotelId <= 0 || $habitacionId <= 0) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT r.id AS reservacion_id, r.huesped_id, r.fecha_entrada, r.fecha_salida
             FROM reservacion_habitaciones rh
             INNER JOIN reservaciones r ON r.id = rh.reservacion_id AND r.hotel_id = rh.hotel_id
             WHERE rh.hotel_id = ? AND rh.habitacion_id = ? AND r.estado = 'checked_in'
             ORDER BY r.fecha_entrada DESC, r.id DESC
             LIMIT 1",
            [$hotelId, $habitacionId]
        );

        $fila = $stmt ? $stmt->fetch() : null;
        return $fila ?: null;
    }

    // ───────────────────────────── Configuracion ─────────────────────────────

    /** Lo que muestra la pagina del QR (Wi-Fi, horarios, info, contacto). */
    public function configuracion(int $hotelId): array
    {
        return [
            'peticiones_activas' => ConfiguracionHotelRegistry::getBool('estancia.peticiones_activas', true, $hotelId),
            'wifi_red' => (string) ConfiguracionHotelRegistry::get('estancia.wifi_red', '', $hotelId),
            'wifi_clave' => (string) ConfiguracionHotelRegistry::get('estancia.wifi_clave', '', $hotelId),
            'contacto_whatsapp' => (string) ConfiguracionHotelRegistry::get('estancia.contacto_whatsapp', '', $hotelId),
            'info' => (string) ConfiguracionHotelRegistry::get('estancia.info', '', $hotelId),
            'reglamento' => (string) ConfiguracionHotelRegistry::get('estancia.reglamento', '', $hotelId),
            'checkin_hora' => (string) ConfiguracionHotelRegistry::get('operacion.checkin_hora', '15:00', $hotelId),
            'checkout_hora' => (string) ConfiguracionHotelRegistry::get('operacion.checkout_hora', '12:00', $hotelId),
        ];
    }

    /**
     * Valida y normaliza lo capturado en la pantalla de configuracion. PURA.
     * Recibe el POST crudo (sin htmlspecialchars: se escapa al pintar).
     * Devuelve ['valores' => clave => [valor, tipo], 'errores' => string[]].
     */
    public static function normalizarConfiguracion(array $datos): array
    {
        $errores = [];

        $texto = static function ($valor, int $max, bool $multilinea): string {
            $valor = str_replace(["\r\n", "\r"], "\n", (string) $valor);
            // Fuera caracteres de control (salvo el salto de linea en textos largos).
            $valor = preg_replace($multilinea ? '/[\x00-\x09\x0B-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u', '', $valor) ?? '';
            $valor = trim($valor);
            if ($multilinea) {
                $valor = preg_replace("/\n{3,}/", "\n\n", $valor) ?? $valor;
            }
            return mb_substr($valor, 0, $max);
        };

        $whatsapp = $texto($datos['contacto_whatsapp'] ?? '', 25, false);
        $digitos = preg_replace('/\D+/', '', $whatsapp) ?? '';
        if ($whatsapp !== '' && (strlen($digitos) < 10 || strlen($digitos) > 15)) {
            $errores[] = 'El WhatsApp de recepción debe tener 10 dígitos (o la lada internacional completa).';
        }

        $activas = $datos['peticiones_activas'] ?? '0';

        return [
            'valores' => [
                'estancia.peticiones_activas' => [in_array((string) $activas, ['1', 'on', 'true'], true) ? '1' : '0', 'boolean'],
                'estancia.wifi_red' => [$texto($datos['wifi_red'] ?? '', 80, false), 'string'],
                'estancia.wifi_clave' => [$texto($datos['wifi_clave'] ?? '', 80, false), 'string'],
                'estancia.contacto_whatsapp' => [$whatsapp, 'string'],
                'estancia.info' => [$texto($datos['info'] ?? '', 1500, true), 'string'],
                'estancia.reglamento' => [$texto($datos['reglamento'] ?? '', 3000, true), 'string'],
            ],
            'errores' => $errores,
        ];
    }

    /** Guarda la configuracion del QR. Devuelve ['success', 'errores']. */
    public function guardarConfiguracion(int $hotelId, array $datos): array
    {
        if ($hotelId <= 0) {
            return ['success' => false, 'errores' => ['Hotel no válido.']];
        }

        $normalizado = self::normalizarConfiguracion($datos);
        if (!empty($normalizado['errores'])) {
            return ['success' => false, 'errores' => $normalizado['errores']];
        }

        foreach ($normalizado['valores'] as $clave => [$valor, $tipo]) {
            $this->db->query(
                "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'estancia', 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), activo = 1, updated_at = NOW()",
                [$hotelId, $clave, $valor, $tipo]
            );
        }

        if (function_exists('hotel_config_cache_invalidar')) {
            hotel_config_cache_invalidar($hotelId);
        }
        ConfiguracionHotelRegistry::limpiarCache($hotelId);

        return ['success' => true, 'errores' => []];
    }

    /**
     * Contacto de recepcion para la pagina del QR: el WhatsApp configurado y,
     * si no hay, el telefono del hotel. 'wa' es el numero listo para wa.me.
     */
    public function contactoRecepcion(int $hotelId): array
    {
        $numero = trim((string) ConfiguracionHotelRegistry::get('estancia.contacto_whatsapp', '', $hotelId));

        if ($numero === '') {
            $stmt = $this->db->query("SELECT telefono FROM hoteles WHERE id = ? LIMIT 1", [$hotelId]);
            $fila = $stmt ? $stmt->fetch() : null;
            $numero = trim((string) ($fila['telefono'] ?? ''));
        }

        $digitos = preg_replace('/\D+/', '', $numero) ?? '';

        return [
            'texto' => $numero,
            'tel' => $digitos !== '' ? $digitos : null,
            'wa' => self::telefonoWa($numero),
        ];
    }

    /** 10 digitos MX -> 52XXXXXXXXXX (wa.me); numero internacional completo pasa tal cual. PURA. */
    public static function telefonoWa(?string $telefono): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono) ?? '';
        $largo = strlen($digitos);

        if ($largo === 10) {
            return '52' . $digitos;
        }
        if ($largo === 12 && strpos($digitos, '52') === 0) {
            return $digitos;
        }
        if ($largo === 13 && strpos($digitos, '521') === 0) {
            return '52' . substr($digitos, 3);
        }
        if ($largo >= 11 && $largo <= 15) {
            return $digitos;
        }

        return null;
    }

    // ───────────────────────────── Peticiones ─────────────────────────────

    /** Texto libre del huesped: sin controles, espacios sanos y tope de largo. PURA. */
    public static function normalizarDetalle(string $texto, int $max = self::DETALLE_MAX): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/u', '', $texto) ?? '';
        $texto = preg_replace('/[ \t]{2,}/', ' ', $texto) ?? $texto;
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

        return mb_substr(trim($texto), 0, $max);
    }

    /** Estado al que lleva una accion desde un estado, o null si no aplica. PURA. */
    public static function transicion(string $desde, string $accion): ?string
    {
        if (!isset(self::TRANSICIONES[$accion])) {
            return null;
        }

        [$origenes, $destino] = self::TRANSICIONES[$accion];
        return in_array($desde, $origenes, true) ? $destino : null;
    }

    /**
     * Registra lo que el huesped pide desde el QR. Revalida TODO en servidor:
     * tipo conocido, detalle cuando hace falta, estancia activa, peticiones
     * encendidas, tope por hora y doble toque. La foto es opcional y no
     * tumba la peticion si no sirve (lo importante es que el aviso llegue).
     *
     * Devuelve ['success', 'codigo', 'mensaje', 'id'?]. Codigos:
     * ok | ok_sin_foto | duplicada | tipo | detalle | sin_estancia |
     * desactivadas | limite | error.
     */
    public function crearPeticion(int $hotelId, int $habitacionId, string $tipo, string $detalle, ?array $foto = null, string $ip = ''): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return $this->rechazo('tipo', 'Elige qué necesitas.');
        }

        $meta = self::TIPOS[$tipo];
        $detalle = self::normalizarDetalle($detalle);

        if ($meta['pide_detalle'] && mb_strlen($detalle) < 3) {
            return $this->rechazo('detalle', $tipo === 'mantenimiento'
                ? 'Cuéntanos en pocas palabras qué no funciona.'
                : 'Cuéntanos qué necesitas.');
        }

        if (!ConfiguracionHotelRegistry::getBool('estancia.peticiones_activas', true, $hotelId)) {
            return $this->rechazo('desactivadas', 'Por ahora las peticiones se atienden directo en recepción.');
        }

        $estancia = $this->estanciaActiva($hotelId, $habitacionId);
        if ($estancia === null) {
            return $this->rechazo('sin_estancia', 'Esta habitación no tiene una estancia activa. Para cualquier cosa, comunícate con recepción.');
        }

        $fotoGuardada = ['ruta' => null, 'error' => null];
        if ($meta['permite_foto'] && $foto !== null) {
            $fotoGuardada = $this->guardarFoto($hotelId, $habitacionId, $foto);
        }

        $reservacionId = (int) $estancia['reservacion_id'];
        $abrioTransaccion = false;

        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $abrioTransaccion = true;
            }

            // Serializa las peticiones de una misma habitacion: el tope y el
            // doble toque se deciden con la fila del QR bloqueada.
            $this->db->query(
                "SELECT id FROM estancia_qr WHERE hotel_id = ? AND habitacion_id = ? FOR UPDATE",
                [$hotelId, $habitacionId]
            );

            $stmt = $this->db->query(
                "SELECT COUNT(*) AS n FROM estancia_peticiones
                 WHERE hotel_id = ? AND habitacion_id = ? AND created_at >= NOW() - INTERVAL 1 HOUR",
                [$hotelId, $habitacionId]
            );
            $recientes = (int) (($stmt ? $stmt->fetch() : [])['n'] ?? 0);

            if ($recientes >= self::MAX_POR_HORA_HABITACION) {
                $this->terminar($abrioTransaccion, false);
                $this->borrarFoto($fotoGuardada['ruta']);
                return $this->rechazo('limite', 'Ya recibimos varias peticiones de esta habitación y el equipo está en ello. Si es urgente, comunícate con recepción.');
            }

            $stmt = $this->db->query(
                "SELECT id FROM estancia_peticiones
                 WHERE hotel_id = ? AND habitacion_id = ? AND reservacion_id = ? AND tipo = ?
                   AND COALESCE(detalle, '') = ?
                   AND estado IN ('nueva', 'en_atencion')
                   AND created_at >= NOW() - INTERVAL " . self::VENTANA_DUPLICADO_MIN . " MINUTE
                 ORDER BY id DESC LIMIT 1",
                [$hotelId, $habitacionId, $reservacionId, $tipo, $detalle]
            );
            $previa = $stmt ? $stmt->fetch() : null;

            if ($previa) {
                $this->terminar($abrioTransaccion, false);
                $this->borrarFoto($fotoGuardada['ruta']);
                return [
                    'success' => true,
                    'codigo' => 'duplicada',
                    'mensaje' => 'Ya teníamos tu petición; el equipo va para allá.',
                    'id' => (int) $previa['id'],
                ];
            }

            $this->db->query(
                "INSERT INTO estancia_peticiones
                    (hotel_id, habitacion_id, reservacion_id, huesped_id, tipo, detalle, foto_path, estado,
                     origen_ip_hash, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'nueva', ?, NOW(), NOW())",
                [
                    $hotelId,
                    $habitacionId,
                    $reservacionId,
                    $estancia['huesped_id'] !== null ? (int) $estancia['huesped_id'] : null,
                    $tipo,
                    $detalle !== '' ? $detalle : null,
                    $fotoGuardada['ruta'],
                    $ip !== '' ? hash('sha256', 'estancia|' . $ip) : null,
                ]
            );
            $id = (int) $this->pdo->lastInsertId();

            $this->terminar($abrioTransaccion, true);
        } catch (Throwable $e) {
            $this->terminar($abrioTransaccion, false);
            $this->borrarFoto($fotoGuardada['ruta']);
            error_log('Estancia: no se pudo registrar la peticion (hab ' . $habitacionId . '): ' . $e->getMessage());
            return $this->rechazo('error', 'No pudimos enviar tu petición. Intenta de nuevo o comunícate con recepción.');
        }

        $this->notificarNueva($hotelId, $id, $habitacionId, $tipo, $detalle);

        if ($fotoGuardada['error'] !== null) {
            return [
                'success' => true,
                'codigo' => 'ok_sin_foto',
                'mensaje' => 'Listo, ya avisamos al equipo. La foto no se pudo adjuntar: ' . $fotoGuardada['error'],
                'id' => $id,
            ];
        }

        return [
            'success' => true,
            'codigo' => 'ok',
            'mensaje' => 'Listo, ya avisamos al equipo. Te atendemos en breve.',
            'id' => $id,
        ];
    }

    /**
     * Lo que ve el huesped en su QR: SOLO las peticiones de SU estancia
     * (la reservacion en check-in), sin texto ni nombres del personal.
     */
    public function peticionesDeEstancia(int $hotelId, int $habitacionId, int $reservacionId, int $limite = 10): array
    {
        if ($hotelId <= 0 || $habitacionId <= 0 || $reservacionId <= 0) {
            return [];
        }

        $limite = max(1, min(30, $limite));
        $stmt = $this->db->query(
            "SELECT id, tipo, estado, created_at
             FROM estancia_peticiones
             WHERE hotel_id = ? AND habitacion_id = ? AND reservacion_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT {$limite}",
            [$hotelId, $habitacionId, $reservacionId]
        );

        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Bandeja del personal. 'abiertas' = nuevas y en atencion (las mas viejas
     * primero: son las que llevan mas esperando); 'cerradas' = resueltas y
     * descartadas de los ultimos 7 dias.
     */
    public function bandeja(int $hotelId, string $vista = 'abiertas', int $limite = 150): array
    {
        if ($hotelId <= 0) {
            return [];
        }

        $limite = max(1, min(300, $limite));
        $base = "SELECT p.id, p.habitacion_id, p.reservacion_id, p.tipo, p.detalle, p.estado, p.nota_cierre,
                        (p.foto_path IS NOT NULL) AS tiene_foto,
                        p.created_at, p.atendida_at, p.cerrada_at,
                        TIMESTAMPDIFF(MINUTE, p.created_at, NOW()) AS minutos_espera,
                        h.numero AS habitacion_numero, h.piso,
                        hu.nombre_completo AS huesped_nombre,
                        ua.nombre_completo AS atendida_por_nombre,
                        uc.nombre_completo AS cerrada_por_nombre
                 FROM estancia_peticiones p
                 INNER JOIN habitaciones h ON h.id = p.habitacion_id AND h.hotel_id = p.hotel_id
                 LEFT JOIN huespedes hu ON hu.id = p.huesped_id AND hu.hotel_id = p.hotel_id
                 LEFT JOIN usuarios ua ON ua.id = p.atendida_por
                 LEFT JOIN usuarios uc ON uc.id = p.cerrada_por
                 WHERE p.hotel_id = ? ";

        if ($vista === 'cerradas') {
            $stmt = $this->db->query(
                $base . "AND p.estado IN ('resuelta', 'descartada')
                         AND p.cerrada_at >= NOW() - INTERVAL 7 DAY
                 ORDER BY p.cerrada_at DESC, p.id DESC
                 LIMIT {$limite}",
                [$hotelId]
            );
        } else {
            $stmt = $this->db->query(
                $base . "AND p.estado IN ('nueva', 'en_atencion')
                 ORDER BY p.created_at ASC, p.id ASC
                 LIMIT {$limite}",
                [$hotelId]
            );
        }

        return $stmt ? $stmt->fetchAll() : [];
    }

    /** Pulso de la bandeja: abiertas, resueltas hoy y minutos promedio de respuesta hoy. */
    public function resumen(int $hotelId): array
    {
        $resumen = ['nuevas' => 0, 'en_atencion' => 0, 'resueltas_hoy' => 0, 'minutos_respuesta_hoy' => null];

        if ($hotelId <= 0) {
            return $resumen;
        }

        $stmt = $this->db->query(
            "SELECT
                SUM(estado = 'nueva') AS nuevas,
                SUM(estado = 'en_atencion') AS en_atencion,
                SUM(estado = 'resuelta' AND cerrada_at >= CURDATE()) AS resueltas_hoy,
                AVG(CASE WHEN created_at >= CURDATE() AND COALESCE(atendida_at, cerrada_at) IS NOT NULL
                         THEN TIMESTAMPDIFF(MINUTE, created_at, COALESCE(atendida_at, cerrada_at)) END) AS minutos
             FROM estancia_peticiones
             WHERE hotel_id = ?
               AND (estado IN ('nueva', 'en_atencion') OR created_at >= CURDATE() OR cerrada_at >= CURDATE())",
            [$hotelId]
        );
        $fila = $stmt ? $stmt->fetch() : null;

        if ($fila) {
            $resumen['nuevas'] = (int) ($fila['nuevas'] ?? 0);
            $resumen['en_atencion'] = (int) ($fila['en_atencion'] ?? 0);
            $resumen['resueltas_hoy'] = (int) ($fila['resueltas_hoy'] ?? 0);
            $resumen['minutos_respuesta_hoy'] = $fila['minutos'] !== null ? (int) round((float) $fila['minutos']) : null;
        }

        return $resumen;
    }

    /** Burbuja del menu: peticiones esperando (nuevas + en atencion). */
    public function contarAbiertas(int $hotelId): int
    {
        if ($hotelId <= 0) {
            return 0;
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS n FROM estancia_peticiones
             WHERE hotel_id = ? AND estado IN ('nueva', 'en_atencion')",
            [$hotelId]
        );

        return (int) (($stmt ? $stmt->fetch() : [])['n'] ?? 0);
    }

    /**
     * Atender / resolver / descartar. El UPDATE es condicional al estado de
     * origen, asi dos personas que pican a la vez no se pisan: la segunda
     * recibe "ya la tomo alguien". Devuelve ['success', 'mensaje', 'estado'?].
     */
    public function cambiarEstado(int $hotelId, int $peticionId, string $accion, ?int $usuarioId, string $nota = ''): array
    {
        if (!isset(self::TRANSICIONES[$accion])) {
            return ['success' => false, 'mensaje' => 'Acción no válida.'];
        }

        [$origenes, $destino] = self::TRANSICIONES[$accion];
        $nota = mb_substr(self::normalizarDetalle($nota, 300), 0, 300);
        $marcadores = implode(', ', array_fill(0, count($origenes), '?'));

        if ($accion === 'atender') {
            $sql = "UPDATE estancia_peticiones
                    SET estado = ?, atendida_por = ?, atendida_at = NOW(), updated_at = NOW()
                    WHERE id = ? AND hotel_id = ? AND estado IN ({$marcadores})";
            $params = array_merge([$destino, $usuarioId, $peticionId, $hotelId], $origenes);
        } else {
            // Resolver tambien cuenta como primer contacto si nadie la habia
            // tomado; descartar no (no es atencion al huesped).
            $contacto = $accion === 'resolver'
                ? ", atendida_por = COALESCE(atendida_por, ?), atendida_at = COALESCE(atendida_at, NOW())"
                : '';
            $sql = "UPDATE estancia_peticiones
                    SET estado = ?, cerrada_por = ?, cerrada_at = NOW(), nota_cierre = ?{$contacto}, updated_at = NOW()
                    WHERE id = ? AND hotel_id = ? AND estado IN ({$marcadores})";
            $params = [$destino, $usuarioId, $nota !== '' ? $nota : null];
            if ($accion === 'resolver') {
                $params[] = $usuarioId;
            }
            $params = array_merge($params, [$peticionId, $hotelId], $origenes);
        }

        $stmt = $this->db->query($sql, $params);
        $afectadas = $stmt ? $stmt->rowCount() : 0;

        if ($afectadas < 1) {
            $actual = $this->db->query(
                "SELECT estado FROM estancia_peticiones WHERE id = ? AND hotel_id = ? LIMIT 1",
                [$peticionId, $hotelId]
            );
            $fila = $actual ? $actual->fetch() : null;

            if (!$fila) {
                return ['success' => false, 'mensaje' => 'Esa petición no existe en este hotel.'];
            }

            return [
                'success' => false,
                'mensaje' => 'Alguien más ya la tomó (ahora está ' . self::etiquetaEstado((string) $fila['estado']) . ').',
                'estado' => (string) $fila['estado'],
            ];
        }

        if (in_array($destino, ['resuelta', 'descartada'], true)) {
            $this->cerrarNotificacion($hotelId, $peticionId, $destino);
        }

        return ['success' => true, 'mensaje' => 'Listo.', 'estado' => $destino];
    }

    /** Foto de una peticion para la descarga interna: ['ruta', 'mime'] o null (confinada al storage). */
    public function foto(int $hotelId, int $peticionId): ?array
    {
        $stmt = $this->db->query(
            "SELECT foto_path FROM estancia_peticiones WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$peticionId, $hotelId]
        );
        $relativa = (string) (($stmt ? $stmt->fetch() : [])['foto_path'] ?? '');

        if ($relativa === '') {
            return null;
        }

        $base = realpath($this->baseStorage);
        $real = realpath($this->baseStorage . '/' . ltrim($relativa, '/'));

        if (!$base || !$real || strpos($real, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($real)) {
            return null;
        }

        $mime = function_exists('mime_content_type') ? (string) mime_content_type($real) : '';
        if (!isset(self::FOTO_MIMES[$mime])) {
            return null;
        }

        return ['ruta' => $real, 'mime' => $mime];
    }

    /**
     * Texto legible sobre un color de marca: blanco sobre colores oscuros y
     * tinta oscura sobre claros (paletas tipo Los Cedros #84B3D7 dejaban el
     * blanco ilegible en los PDF de cotizacion). Umbral por luminancia
     * relativa WCAG. PURA; un color invalido se trata como oscuro.
     */
    public static function colorTextoSobre(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return '#FFFFFF';
        }

        $canal = static function (string $par): float {
            $c = hexdec($par) / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };
        $luminancia = 0.2126 * $canal(substr($hex, 0, 2))
            + 0.7152 * $canal(substr($hex, 2, 2))
            + 0.0722 * $canal(substr($hex, 4, 2));

        // Contraste contra blanco vs contra la tinta #1F2937 (L ~= 0.0194).
        $contraBlanco = 1.05 / ($luminancia + 0.05);
        $contraTinta = ($luminancia + 0.05) / (0.0194 + 0.05);

        return $contraBlanco >= $contraTinta ? '#FFFFFF' : '#1F2937';
    }

    /** '12:00' -> '12:00 p. m.'; '09:30:00' -> '9:30 a. m.'. Vacio si no es hora. PURA. */
    public static function horaHumana(string $hora): string
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})/', trim($hora), $m)) {
            return '';
        }

        $h = (int) $m[1];
        if ($h > 23) {
            return '';
        }

        $sufijo = $h < 12 ? 'a. m.' : 'p. m.';
        $h12 = $h % 12 === 0 ? 12 : $h % 12;

        return $h12 . ':' . $m[2] . ' ' . $sufijo;
    }

    /**
     * A quien le suena la campana: al equipo que lo resuelve, a recepcion
     * (en un hotel chico es quien sube las toallas) y al gerente. Se nombran
     * los tres porque la bandeja de notificaciones filtra rol_destino por
     * TEXTO: un aviso solo para "limpieza" no le aparece ni a recepcion ni
     * al gerente, y en un hotel sin cuentas de camarista no lo veria nadie.
     */
    public static function rolesAviso(string $tipo): string
    {
        $rol = self::TIPOS[$tipo]['rol'] ?? 'recepcionista';
        return implode(',', array_values(array_unique([$rol, 'recepcionista', 'gerente'])));
    }

    public static function etiquetaEstado(string $estado): string
    {
        $mapa = [
            'nueva' => 'recibida',
            'en_atencion' => 'en atención',
            'resuelta' => 'resuelta',
            'descartada' => 'descartada',
        ];

        return $mapa[$estado] ?? $estado;
    }

    // ───────────────────────────── Privados ─────────────────────────────

    private function rechazo(string $codigo, string $mensaje): array
    {
        return ['success' => false, 'codigo' => $codigo, 'mensaje' => $mensaje];
    }

    private function terminar(bool $abrio, bool $confirmar): void
    {
        if (!$abrio || !$this->pdo->inTransaction()) {
            return;
        }

        if ($confirmar) {
            $this->pdo->commit();
        } else {
            $this->pdo->rollBack();
        }
    }

    private function leerToken(int $hotelId, int $habitacionId): ?string
    {
        $stmt = $this->db->query(
            "SELECT token FROM estancia_qr WHERE hotel_id = ? AND habitacion_id = ? LIMIT 1",
            [$hotelId, $habitacionId]
        );
        $token = $stmt ? $stmt->fetchColumn() : false;

        return $token ? (string) $token : null;
    }

    private function habitacionActivaDelHotel(int $hotelId, int $habitacionId): bool
    {
        if ($hotelId <= 0 || $habitacionId <= 0) {
            return false;
        }

        $stmt = $this->db->query(
            "SELECT id FROM habitaciones WHERE id = ? AND hotel_id = ? AND activa = 1 LIMIT 1",
            [$habitacionId, $hotelId]
        );

        return (bool) ($stmt ? $stmt->fetch() : false);
    }

    private function slugHotel(int $hotelId): string
    {
        $stmt = $this->db->query("SELECT slug FROM hoteles WHERE id = ? LIMIT 1", [$hotelId]);
        return trim((string) (($stmt ? $stmt->fetch() : [])['slug'] ?? ''));
    }

    private static function nuevoToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Guarda la foto en storage PRIVADO (fuera de public_html). Nunca tumba la
     * peticion: devuelve ['ruta' => relativa|null, 'error' => texto|null].
     */
    private function guardarFoto(int $hotelId, int $habitacionId, array $archivo): array
    {
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE || empty($archivo['tmp_name'])) {
            return ['ruta' => null, 'error' => null];
        }
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            || (int) ($archivo['size'] ?? 0) > self::FOTO_MAX_BYTES) {
            return ['ruta' => null, 'error' => 'pesa más de 8 MB.'];
        }
        if ($error !== UPLOAD_ERR_OK) {
            return ['ruta' => null, 'error' => 'no terminó de subirse.'];
        }

        $mime = function_exists('mime_content_type') ? (string) mime_content_type((string) $archivo['tmp_name']) : '';
        if (!isset(self::FOTO_MIMES[$mime])) {
            return ['ruta' => null, 'error' => 'el formato no es JPG, PNG ni WEBP.'];
        }

        $relativoDir = 'estancia_fotos/hotel_' . $hotelId;
        $dir = $this->baseStorage . '/' . $relativoDir;

        if (!$this->asegurarDirectorio($dir)) {
            error_log('Estancia: no se pudo crear el directorio de fotos: ' . $dir);
            return ['ruta' => null, 'error' => 'no se pudo guardar.'];
        }

        $nombre = 'hab' . $habitacionId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . self::FOTO_MIMES[$mime];

        if (!$this->moverArchivo((string) $archivo['tmp_name'], $dir . '/' . $nombre)) {
            error_log('Estancia: no se pudo mover la foto a ' . $dir . '/' . $nombre);
            return ['ruta' => null, 'error' => 'no se pudo guardar.'];
        }

        @chmod($dir . '/' . $nombre, 0640);
        $this->darAlServidorWeb($dir . '/' . $nombre);

        return ['ruta' => $relativoDir . '/' . $nombre, 'error' => null];
    }

    /** Punto unico de movimiento del archivo subido (los tests lo sustituyen). */
    protected function moverArchivo(string $origen, string $destino): bool
    {
        return is_uploaded_file($origen) && move_uploaded_file($origen, $destino);
    }

    private function borrarFoto(?string $relativa): void
    {
        if ($relativa === null || $relativa === '') {
            return;
        }

        $ruta = $this->baseStorage . '/' . ltrim($relativa, '/');
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    /**
     * mkdir 0750 recursivo. Si corre como root (un script CLI), cada nivel
     * nuevo se entrega a www-data: una raiz de storage creada por root deja
     * al sitio sin poder escribir ni leer (gotcha del Centro documental).
     */
    private function asegurarDirectorio(string $dir): bool
    {
        if (is_dir($dir)) {
            return is_writable($dir);
        }

        $crear = [];
        $actual = $dir;
        while ($actual !== '' && $actual !== '/' && !is_dir($actual)) {
            $crear[] = $actual;
            $actual = dirname($actual);
        }

        foreach (array_reverse($crear) as $nivel) {
            if (!@mkdir($nivel, 0750) && !is_dir($nivel)) {
                return false;
            }
            $this->darAlServidorWeb($nivel);
        }

        return is_dir($dir) && is_writable($dir);
    }

    private function darAlServidorWeb(string $ruta): void
    {
        if (!function_exists('posix_geteuid') || posix_geteuid() !== 0 || !function_exists('posix_getpwnam')) {
            return;
        }

        $www = posix_getpwnam('www-data');
        if (!$www) {
            return;
        }

        @chown($ruta, (int) $www['uid']);
        @chgrp($ruta, (int) $www['gid']);
    }

    private function notificarNueva(int $hotelId, int $peticionId, int $habitacionId, string $tipo, string $detalle): void
    {
        try {
            $stmt = $this->db->query(
                "SELECT numero FROM habitaciones WHERE id = ? AND hotel_id = ? LIMIT 1",
                [$habitacionId, $hotelId]
            );
            $numero = (string) (($stmt ? $stmt->fetch() : [])['numero'] ?? $habitacionId);
            $meta = self::TIPOS[$tipo];

            NotificacionService::crear([
                'hotel_id' => $hotelId,
                'rol_destino' => self::rolesAviso($tipo),
                'modulo' => 'habitaciones',
                'tipo' => 'peticion_huesped_' . $tipo,
                'severidad' => $meta['severidad'],
                'titulo' => 'Hab. ' . $numero . ': ' . $meta['etiqueta'],
                'mensaje' => $detalle !== ''
                    ? 'El huésped escribió: "' . mb_substr($detalle, 0, 180) . '"'
                    : 'El huésped lo pidió desde el QR de su habitación.',
                'entidad_tipo' => 'estancia_peticion',
                'entidad_id' => $peticionId,
                'url' => 'peticiones',
                'dedupe_key' => 'estancia_peticion_' . $peticionId,
                'creada_por' => null,
            ]);
        } catch (Throwable $e) {
            error_log('Estancia: no se pudo avisar de la peticion ' . $peticionId . ': ' . $e->getMessage());
        }
    }

    /** Al cerrar la peticion, su aviso de la campana tambien se cierra. */
    private function cerrarNotificacion(int $hotelId, int $peticionId, string $estado): void
    {
        try {
            NotificacionService::crear([
                'hotel_id' => $hotelId,
                'modulo' => 'habitaciones',
                'tipo' => 'peticion_huesped',
                'titulo' => 'Petición de huésped',
                'entidad_tipo' => 'estancia_peticion',
                'entidad_id' => $peticionId,
                'url' => 'peticiones',
                'dedupe_key' => 'estancia_peticion_' . $peticionId,
                'estado' => $estado === 'descartada' ? 'descartada' : 'resuelta',
            ]);
        } catch (Throwable $e) {
            error_log('Estancia: no se pudo cerrar el aviso de la peticion ' . $peticionId . ': ' . $e->getMessage());
        }
    }
}
