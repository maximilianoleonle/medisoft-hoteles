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
 * REGLA DURA: toda escritura pasa por una PROPUESTA + confirmacion humana
 * (msConfirm) y reusa el motor de su pantalla (tareas operativas,
 * MantenimientoService, MovimientoCaja). En dinero SOLO registra gastos con
 * el permiso caja.movimientos y caja abierta; jamas cobra, jamas toca cortes
 * ni reservaciones.
 */
class CopilotoService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const MODELO = 'claude-opus-4-8';
    private const MAX_TOKENS = 1024;

    /**
     * Chip sugerido por intent: [etiqueta, pregunta data-q, ?bloque requerido].
     * Lo usa chipsFrecuentes() para convertir el log real de uso del hotel
     * (registrar() en copiloto_mensajes) en los chips del saludo.
     */
    private const CHIPS_POR_INTENT = [
        'resumen_dia' => ['📋 Resumen del día', 'Dame el resumen del día', null],
        'resumen_cierre' => ['🌙 Cierre del día', '¿Cómo cerró el día?', null],
        'ocupacion' => ['Habitaciones libres', '¿Cuántas habitaciones libres tengo hoy?', null],
        'ocupacion_semana' => ['¿Cómo pinta la semana?', '¿Cómo pinta la semana?', null],
        'llegadas' => ['Llegadas de hoy', '¿Quién llega hoy?', null],
        'llegadas_manana' => ['Llegadas de mañana', '¿Quién llega mañana?', null],
        'salidas' => ['Salidas de hoy', '¿Quién se va hoy?', null],
        'salidas_manana' => ['Salidas de mañana', '¿Quién se va mañana?', null],
        'caja' => ['Estado de caja', '¿Cómo voy de caja?', null],
        'ganancias_mes_actual' => ['Ganancias del mes', '¿Cuánto llevo de ganancias este mes?', null],
        'ganancias_mes_pasado' => ['Ganancias del mes pasado', '¿Cuáles fueron las ganancias del mes pasado?', null],
        'comparar_meses' => ['¿Mejor que el mes pasado?', '¿Voy mejor o peor que el mes pasado?', null],
        'ganancias_semana' => ['¿Cómo va la semana?', '¿Cómo va la semana de ventas?', null],
        'ganancias_dia' => ['¿Cuánto vendí ayer?', '¿Cuánto vendí ayer?', null],
        'dia_top' => ['Mi mejor día', '¿Cuál fue mi mejor día del mes?', null],
        'pago_metodo' => ['¿Cómo me pagan?', '¿Cómo me pagaron este mes?', null],
        'gastos_categoria' => ['Gastos del mes', '¿En qué se me va el dinero este mes?', null],
        'habitaciones_estado' => ['Estado de las habitaciones', '¿Cómo están mis habitaciones ahorita?', null],
        'hospedados' => ['¿Quién está hospedado?', '¿Quién está hospedado?', null],
        'limpiar_hoy' => ['¿Qué limpio hoy?', '¿Qué hay que limpiar hoy?', null],
        'no_shows' => ['¿Hay no-shows?', '¿Tengo no-shows pendientes?', null],
        'checkouts_vencidos' => ['Checkouts vencidos', '¿Hay checkouts vencidos?', null],
        'reservas_hoy' => ['Reservas nuevas de hoy', '¿Cuántas reservas entraron hoy?', null],
        'noches_vendidas' => ['Noches vendidas', '¿Cuántas noches vendí este mes?', null],
        'tarifa_promedio' => ['Tarifa promedio', '¿Cuál es mi tarifa promedio?', null],
        'estancia_promedio' => ['Estancia promedio', '¿Cuánto se quedan mis huéspedes?', null],
        'cancelaciones_mes' => ['Cancelaciones', '¿Cuántas cancelaciones llevo este mes?', null],
        'reservaciones_mes' => ['Reservas del mes', '¿Cuántas reservaciones hay para este mes?', null],
        'motor_conciliar' => ['Pagos por conciliar', '¿Tengo pagos online por conciliar?', 'motor_reservas'],
        'calificacion' => ['¿Cómo me califican?', '¿Cómo me califican mis huéspedes?', 'reputacion'],
        'calificaciones_bajas' => ['Calificaciones bajas', '¿Tengo calificaciones bajas?', 'reputacion'],
        'cupones_activos' => ['Cupones activos', '¿Qué cupones tengo activos?', 'promociones'],
        'inventario_bajo' => ['Por agotarse', '¿Qué productos están por agotarse?', 'inventario'],
        'cxp_debo' => ['¿Cuánto debo?', '¿Cuánto debo a proveedores?', 'compras'],
        'cxc_deben' => ['¿Quién me debe?', '¿Quién me debe?', 'cuentas_cobrar'],
        'nomina_periodo' => ['Nómina del periodo', '¿Cuánto es la nómina de este periodo?', 'nomina_avanzada'],
    ];

    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Chips que aprenden: los intents mas consultados por ESTE hotel en los
     * ultimos 60 dias (log de registrar() en copiloto_mensajes), convertidos
     * a chips y filtrados por bloque activo. Cache APCu de 1 hora; si el log
     * es corto devuelve menos de $limite y el widget completa con los fijos.
     */
    public function chipsFrecuentes(int $hotelId, int $limite = 6): array
    {
        if ($hotelId <= 0) {
            return [];
        }

        $consultar = function () use ($hotelId) {
            try {
                $stmt = $this->pdo->prepare(
                    "SELECT intent, COUNT(*) n
                     FROM copiloto_mensajes
                     WHERE hotel_id = ? AND fuente = 'reglas' AND intent IS NOT NULL
                       AND intent NOT LIKE 'faq%' AND intent NOT LIKE 'ctx:%' AND intent NOT LIKE 'accion:%'
                       AND created_at >= NOW() - INTERVAL 60 DAY
                     GROUP BY intent
                     ORDER BY n DESC, intent
                     LIMIT 12"
                );
                $stmt->execute([$hotelId]);
                return array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'intent');
            } catch (Throwable $e) {
                error_log('Copiloto: error chips frecuentes: ' . $e->getMessage());
                return null; // null no se cachea: el fallo no se pega 1 hora
            }
        };

        $intents = function_exists('ms_cache_remember')
            ? ms_cache_remember('copiloto_chips_' . $hotelId, 3600, $consultar)
            : $consultar();

        $chips = [];
        foreach ((array) ($intents ?: []) as $intent) {
            if (count($chips) >= $limite) {
                break;
            }
            $def = self::CHIPS_POR_INTENT[(string) $intent] ?? null;
            if ($def === null) {
                continue;
            }
            if ($def[2] !== null && !$this->tieneModulo($def[2], $hotelId)) {
                continue;
            }
            $chips[] = [$def[0], $def[1]];
        }

        return $chips;
    }

    /**
     * Uso real del copiloto para el panel de valor de gerencia. Todo sale del
     * log existente (copiloto_mensajes) y de la bandeja de notificaciones;
     * cero tablas nuevas. Solo lectura, scope de hotel.
     */
    public function resumenUso(int $hotelId, int $dias = 30): array
    {
        $dias = max(7, min(90, $dias));
        $desde = date('Y-m-d 00:00:00', strtotime("-{$dias} days"));

        $out = [
            'dias' => $dias,
            'total' => 0,
            'por_fuente' => ['reglas' => 0, 'ia' => 0, 'fallback' => 0],
            'usuarios' => 0,
            'acciones_ok' => 0,
            'tokens_entrada' => 0,
            'tokens_salida' => 0,
            'serie' => [],
            'top' => [],
            'briefings' => 0,
            'alertas' => 0,
        ];

        try {
            $stmt = $this->pdo->prepare(
                "SELECT fuente, COUNT(*) n, COUNT(DISTINCT usuario_id) u,
                        COALESCE(SUM(tokens_entrada), 0) tin, COALESCE(SUM(tokens_salida), 0) tout
                 FROM copiloto_mensajes
                 WHERE hotel_id = ? AND created_at >= ?
                 GROUP BY fuente"
            );
            $stmt->execute([$hotelId, $desde]);
            $usuariosMax = 0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $fuente = (string) $f['fuente'];
                if (isset($out['por_fuente'][$fuente])) {
                    $out['por_fuente'][$fuente] = (int) $f['n'];
                }
                $out['total'] += (int) $f['n'];
                $out['tokens_entrada'] += (int) $f['tin'];
                $out['tokens_salida'] += (int) $f['tout'];
                $usuariosMax = max($usuariosMax, (int) $f['u']);
            }

            $stmt = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT usuario_id) FROM copiloto_mensajes WHERE hotel_id = ? AND created_at >= ? AND usuario_id IS NOT NULL"
            );
            $stmt->execute([$hotelId, $desde]);
            $out['usuarios'] = (int) $stmt->fetchColumn();

            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM copiloto_mensajes
                 WHERE hotel_id = ? AND created_at >= ? AND intent LIKE 'accion:%\\_ok'"
            );
            $stmt->execute([$hotelId, $desde]);
            $out['acciones_ok'] = (int) $stmt->fetchColumn();

            // Serie diaria de los ultimos 14 dias (con ceros para dias sin uso).
            $serieDesde = date('Y-m-d 00:00:00', strtotime('-13 days'));
            $stmt = $this->pdo->prepare(
                "SELECT DATE(created_at) f, COUNT(*) n
                 FROM copiloto_mensajes
                 WHERE hotel_id = ? AND created_at >= ?
                 GROUP BY DATE(created_at)"
            );
            $stmt->execute([$hotelId, $serieDesde]);
            $porFecha = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $porFecha[(string) $f['f']] = (int) $f['n'];
            }
            for ($i = 13; $i >= 0; $i--) {
                $fecha = date('Y-m-d', strtotime("-{$i} days"));
                $out['serie'][] = ['fecha' => $fecha, 'n' => (int) ($porFecha[$fecha] ?? 0)];
            }

            $stmt = $this->pdo->prepare(
                "SELECT intent, COUNT(*) n
                 FROM copiloto_mensajes
                 WHERE hotel_id = ? AND created_at >= ? AND intent IS NOT NULL
                 GROUP BY intent ORDER BY n DESC, intent LIMIT 8"
            );
            $stmt->execute([$hotelId, $desde]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $out['top'][] = [
                    'intent' => (string) $f['intent'],
                    'etiqueta' => $this->etiquetaIntent((string) $f['intent']),
                    'n' => (int) $f['n'],
                ];
            }

            $stmt = $this->pdo->prepare(
                "SELECT tipo, COUNT(*) n FROM notificaciones
                 WHERE hotel_id = ? AND created_at >= ? AND tipo IN ('copiloto_briefing', 'copiloto_alerta_ocupacion')
                 GROUP BY tipo"
            );
            $stmt->execute([$hotelId, $desde]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
                if ((string) $f['tipo'] === 'copiloto_briefing') {
                    $out['briefings'] = (int) $f['n'];
                } else {
                    $out['alertas'] = (int) $f['n'];
                }
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error resumen de uso: ' . $e->getMessage());
        }

        return $out;
    }

    /** Etiqueta humana de un intent del log (para el panel de valor). */
    /**
     * Catalogo de capacidades para el saludo del widget, en PAGINAS por
     * categoria (se navegan con flechas). Filtrado por los bloques activos
     * del hotel. Item = [etiqueta, texto, plantilla]: plantilla=1 significa
     * que el chip RELLENA el input (ordenes que necesitan datos del usuario)
     * en vez de enviar directo.
     */
    public function catalogoCapacidades(int $hotelId): array
    {
        $paginas = [];

        $frecuentes = $this->chipsFrecuentes($hotelId, 5);
        if (count($frecuentes) >= 3) {
            $paginas[] = ['titulo' => 'Tus frecuentes', 'items' => array_map(function ($c) {
                return [$c[0], $c[1], 0];
            }, $frecuentes)];
        }

        $paginas[] = ['titulo' => 'Tu día', 'items' => [
            ['📋 Resumen del día', 'Dame el resumen del día', 0],
            ['¿Cuántas habitaciones libres?', '¿Cuántas habitaciones libres tengo hoy?', 0],
            ['¿Quién llega hoy?', '¿Quién llega hoy?', 0],
            ['¿Quién está hospedado?', '¿Quién está hospedado?', 0],
            ['🌙 ¿Cómo cerró el día?', '¿Cómo cerró el día?', 0],
        ]];

        $paginas[] = ['titulo' => 'Dinero', 'items' => [
            ['¿Cómo voy de caja?', '¿Cómo voy de caja?', 0],
            ['Ganancias del mes', '¿Cuánto llevo de ganancias este mes?', 0],
            ['¿Mejor que el mes pasado?', '¿Voy mejor o peor que el mes pasado?', 0],
            ['¿En qué se va el dinero?', '¿En qué se me va el dinero este mes?', 0],
            ['¿Cuánto vendí ayer?', '¿Cuánto vendí ayer?', 0],
        ]];

        $paginas[] = ['titulo' => 'Acciones rápidas', 'items' => [
            ['💸 Registrar un gasto', 'registra un gasto de 450 de ', 1],
            ['🧹 Programar limpieza', 'programa limpieza de la ', 1],
            ['🔧 Bloquear habitación', 'bloquea la  por ', 1],
            ['✅ Liberar habitación', 'desbloquea la ', 1],
            ['🗣️ Asignar limpieza', 'asigna a  la limpieza de la ', 1],
        ]];

        $paginas[] = ['titulo' => 'Reservaciones', 'items' => [
            ['🛎️ Nueva reservación', 'Quiero hacer una reservación', 0],
            ['¿Tiene reserva...?', '¿tiene reserva ', 1],
            ['¿Hay no-shows?', '¿Tengo no-shows pendientes?', 0],
            ['Reservas del mes', '¿Cuántas reservaciones hay para este mes?', 0],
            ['Tarifa promedio', '¿Cuál es mi tarifa promedio?', 0],
        ]];

        // Pagina de bloques contratados (solo lo que el hotel tiene).
        $extras = [];
        if ($this->tieneModulo('promociones', $hotelId)) {
            $extras[] = ['🎟️ Crear un cupón', 'crea un cupón de 10% para ', 1];
            $extras[] = ['Cupones activos', '¿Qué cupones tengo activos?', 0];
        }
        if ($this->tieneModulo('compras', $hotelId)) {
            $extras[] = ['🤝 Pagar a un proveedor', 'págale al proveedor ', 1];
            $extras[] = ['¿Cuánto debo?', '¿Cuánto debo a proveedores?', 0];
        }
        if ($this->tieneModulo('inventario', $hotelId)) {
            $extras[] = ['Por agotarse', '¿Qué productos están por agotarse?', 0];
        }
        if ($this->tieneModulo('reputacion', $hotelId)) {
            $extras[] = ['¿Cómo me califican?', '¿Cómo me califican mis huéspedes?', 0];
        }
        if ($this->tieneModulo('nomina_avanzada', $hotelId)) {
            $extras[] = ['Nómina del periodo', '¿Cuánto es la nómina de este periodo?', 0];
        }
        foreach (array_chunk($extras, 5) as $i => $grupo) {
            $paginas[] = ['titulo' => 'Tus bloques' . ($i > 0 ? ' · ' . ($i + 1) : ''), 'items' => $grupo];
        }

        return $paginas;
    }

    /**
     * Sugerencias contextuales (max 3) para acompañar una respuesta: chips
     * relacionados con el TEMA que se acaba de responder, para que el
     * copiloto siempre proponga el siguiente paso. Mismo formato de item que
     * el catalogo. No aplica cuando hay accion o flujo pendiente.
     */
    public function sugerenciasParaRespuesta(?string $intent, int $hotelId): array
    {
        $intent = (string) $intent;
        $gasto = ['💸 Registrar un gasto', 'registra un gasto de 450 de ', 1];
        $reserva = ['🛎️ Nueva reservación', 'Quiero hacer una reservación', 0];
        $resumen = ['📋 Resumen del día', 'Dame el resumen del día', 0];

        if (preg_match('/^(resumen_dia|resumen_cierre)/', $intent)) {
            return [
                ['¿Cómo voy de caja?', '¿Cómo voy de caja?', 0],
                ['¿Quién llega hoy?', '¿Quién llega hoy?', 0],
                $reserva,
            ];
        }
        if (preg_match('/^(ocupacion|habitaciones_estado|hospedados|limpiar_hoy|ctx:habitacion)/', $intent)) {
            return [
                ['🧹 Programar limpieza', 'programa limpieza de la ', 1],
                ['🔧 Bloquear habitación', 'bloquea la  por ', 1],
                ['¿Cómo pinta la semana?', '¿Cómo pinta la semana?', 0],
            ];
        }
        if (preg_match('/^(caja|ganancias|comparar_meses|pago_metodo|dia_top|gastos_categoria|noches_vendidas|tarifa_promedio)/', $intent)) {
            return [
                $gasto,
                ['¿En qué se va el dinero?', '¿En qué se me va el dinero este mes?', 0],
                ['¿Mejor que el mes pasado?', '¿Voy mejor o peor que el mes pasado?', 0],
            ];
        }
        if (preg_match('/^(llegadas|salidas|reservas_hoy|reservaciones_mes|no_shows|checkouts_vencidos|busca:huesped|estancia_promedio|cancelaciones_mes|ctx:reserva)/', $intent)) {
            return [
                $reserva,
                ['¿Quién está hospedado?', '¿Quién está hospedado?', 0],
                ['¿Hay no-shows?', '¿Tengo no-shows pendientes?', 0],
            ];
        }
        if (strpos($intent, 'cupones') === 0 && $this->tieneModulo('promociones', $hotelId)) {
            return [
                ['🎟️ Crear un cupón', 'crea un cupón de 10% para ', 1],
                ['Reservas del mes', '¿Cuántas reservaciones hay para este mes?', 0],
                $resumen,
            ];
        }
        if (strpos($intent, 'cxp') === 0 && $this->tieneModulo('compras', $hotelId)) {
            return [
                ['🤝 Pagar a un proveedor', 'págale al proveedor ', 1],
                ['¿Cómo voy de caja?', '¿Cómo voy de caja?', 0],
                $resumen,
            ];
        }

        return [$resumen, $gasto, $reserva];
    }

    /**
     * Adjunta sugerencias contextuales a una respuesta terminal (sin accion
     * pendiente ni flujo en curso: ahi la siguiente jugada ya esta clara).
     */
    private function conSugerencias(array $r, ?string $intent, int $hotelId): array
    {
        if (!empty($r['accion']) || !empty($r['flujo'])) {
            return $r;
        }
        $r['sugerencias'] = $this->sugerenciasParaRespuesta($intent, $hotelId);
        return $r;
    }

    public function etiquetaIntent(string $intent): string
    {
        $def = self::CHIPS_POR_INTENT[$intent] ?? null;
        if ($def !== null) {
            return $def[0];
        }
        if (strpos($intent, 'faq_inactivo:') === 0) {
            return 'Modulo no contratado (' . substr($intent, 13) . ')';
        }
        if (strpos($intent, 'faq:') === 0) {
            return 'Como se hace: ' . str_replace('_', ' ', substr($intent, 4));
        }
        $fijas = [
            'ctx:reserva_pagos' => 'Saldo de la reservacion en pantalla',
            'ctx:reserva_fechas' => 'Fechas de la reservacion en pantalla',
            'ctx:reserva_general' => 'Datos de la reservacion en pantalla',
            'ctx:habitacion' => 'Estado de la habitacion en pantalla',
            'busca:huesped' => 'Buscar huesped por nombre',
            'accion:limpieza' => 'Programar limpieza (propuesta)',
            'accion:limpieza_ok' => 'Limpieza programada',
            'accion:asignar' => 'Asignar limpieza (propuesta)',
            'accion:asignar_ok' => 'Limpieza asignada',
            'accion:mant' => 'Iniciar mantenimiento (propuesta)',
            'accion:mant_ok' => 'Mantenimiento iniciado',
            'accion:bloqueo' => 'Bloquear habitacion (propuesta)',
            'accion:desbloqueo' => 'Liberar habitacion (propuesta)',
            'accion:desbloqueo_ok' => 'Habitacion liberada',
            'accion:gasto' => 'Registrar gasto (propuesta)',
            'accion:gasto_ok' => 'Gasto registrado en caja',
            'accion:cupon' => 'Crear cupon (propuesta)',
            'accion:cupon_ok' => 'Cupon creado',
            'accion:pago' => 'Pagar a proveedor (propuesta)',
            'accion:pago_ok' => 'Pago a proveedor registrado',
            'accion:reserva_link' => 'Reservacion armada (formulario prellenado)',
            'accion:reserva_ocupada' => 'Reservacion: habitacion ocupada',
            'accion:reserva_huesped' => 'Reservacion con huesped nuevo (propuesta)',
            'accion:reserva_fin_ok' => 'Huesped registrado + reservacion armada',
            'flujo:reserva_cancel' => 'Reservacion paso a paso cancelada',
            'resumen_dia' => '📋 Resumen del dia',
        ];
        return $fijas[$intent] ?? ucfirst(str_replace(['_', ':'], ' ', $intent));
    }

    public function iaDisponible(int $hotelId): bool
    {
        return trim((string) (getenv('ANTHROPIC_API_KEY') ?: '')) !== ''
            && ConfiguracionHotelRegistry::getBool('copiloto.ia_activa', true, $hotelId);
    }

    /**
     * Nombre white-label del asistente para ESTE hotel (config copiloto.nombre,
     * default "Copiloto"). Lo usan el widget, el briefing push y los textos.
     */
    public static function nombreAsistente(int $hotelId): string
    {
        if ($hotelId > 0 && function_exists('hotel_config_get')) {
            $valor = trim((string) hotel_config_get('copiloto.nombre', 'Copiloto', $hotelId));
            if ($valor !== '') {
                return mb_substr($valor, 0, 40);
            }
        }
        return 'Copiloto';
    }

    /**
     * Responde una pregunta. Devuelve
     * ['success', 'texto', 'fuente' => 'reglas'|'ia'|'fallback', 'enlace' => ?['url','texto'], 'intent' => ?string].
     *
     * $rutaContexto es la ruta relativa de la pantalla desde la que pregunta el
     * usuario ("reservaciones/ver/12"). Solo sirve para saber DE QUE entidad
     * habla; toda lectura va con scope de hotel y valida el permiso del rol.
     *
     * $intentPrevio es el intent que el propio servicio devolvio en la pregunta
     * anterior del usuario (memoria de conversacion): permite que "¿y manana?"
     * herede el tema. Viene del cliente pero SOLO decide que plantilla usar;
     * los datos siempre se leen con scope de hotel.
     *
     * $historial es el hilo reciente del chat (JSON del widget o array) y solo
     * alimenta a la IA como contexto conversacional multi-turno; se sanea con
     * sanearHistorial() y jamas toca las respuestas deterministas.
     */
    public function responder(int $hotelId, string $pregunta, ?int $usuarioId = null, string $rutaContexto = '', string $intentPrevio = '', $historial = null, $flujo = null): array
    {
        $pregunta = trim($pregunta);
        if ($pregunta === '') {
            return ['success' => false, 'texto' => 'Escribe tu pregunta.', 'fuente' => 'fallback', 'enlace' => null];
        }
        $pregunta = mb_substr($pregunta, 0, 500);
        $norm = $this->normalizar($pregunta);

        // 0) Preguntas sobre LA entidad visible en pantalla (la reservacion o
        //    habitacion abierta). Va antes que todo: "¿cuanto debe?" dicho sobre
        //    una reservacion abierta es de ESA reservacion, no una duda general.
        $contexto = $this->parseContexto($rutaContexto);
        if ($contexto !== null) {
            $intentCtx = $this->detectarIntentEntidad($norm, $contexto);
            if ($intentCtx !== null) {
                $r = $this->responderEntidad($hotelId, $intentCtx, $contexto);
                $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', $intentCtx, 0, 0);
                return $this->conSugerencias($r + ['success' => true, 'fuente' => 'reglas', 'intent' => $intentCtx], $intentCtx, $hotelId);
            }
        }

        // 0.4) Flujo conversacional pendiente (reservacion campo por campo):
        //      el estado viaja con el widget y aqui se VALIDA completo. Si el
        //      mensaje no es respuesta del flujo (una pregunta de datos, por
        //      ejemplo), cae al pipeline normal y el flujo sigue vivo: el
        //      widget lo conserva hasta recibir 'flujo' nuevo o 'flujo_fin'.
        $flujoActivo = self::sanearFlujo($flujo);
        if ($flujoActivo !== null) {
            $rf = $this->continuarFlujoReserva($hotelId, $norm, $flujoActivo);
            if ($rf !== null) {
                $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', $rf['intent'], 0, 0);
                return $rf['respuesta'] + ['success' => true, 'fuente' => 'reglas', 'intent' => $rf['intent']];
            }
        }

        // 0.5) Accion ejecutable: aqui solo se PROPONE. El widget pide
        //      confirmacion (msConfirm) y ejecutar pasa por POST
        //      /copiloto/accion con CSRF y permiso del rol. En dinero solo
        //      existe el GASTO (permiso caja.movimientos + caja abierta);
        //      cobros y cortes jamas. Asignar va primero: sus verbos son mas
        //      especificos.
        $accion = $this->detectarAccionAsignar($norm, $hotelId, $contexto)
            ?? $this->detectarAccionFinalizarMantenimiento($norm, $hotelId, $contexto)
            ?? $this->detectarAccionMantenimiento($norm, $hotelId, $contexto)
            ?? $this->detectarAccionGasto($norm, $hotelId)
            ?? $this->detectarAccionPagoProveedor($norm, $hotelId)
            ?? $this->detectarAccionCupon($norm, $hotelId)
            ?? $this->detectarAccionLimpieza($norm, $hotelId, $contexto)
            ?? $this->detectarAccionReservacion($norm, $hotelId, $contexto);
        if ($accion !== null) {
            $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', $accion['intent'], 0, 0);
            return $accion['respuesta'] + ['success' => true, 'fuente' => 'reglas', 'intent' => $accion['intent']];
        }

        // 0.7) Memoria de conversacion: un seguimiento corto ("¿y manana?",
        //      "¿y el mes pasado?") hereda el tema de la pregunta anterior.
        $intentSeguimiento = $this->detectarSeguimiento($norm, $intentPrevio, $hotelId);
        if ($intentSeguimiento !== null) {
            $r = $this->responderIntent($hotelId, $intentSeguimiento, $norm);
            $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', $intentSeguimiento, 0, 0);
            return $this->conSugerencias($r + ['success' => true, 'fuente' => 'reglas', 'intent' => $intentSeguimiento], $intentSeguimiento, $hotelId);
        }

        // 0.8) Busqueda de huesped por nombre: "¿tiene reserva Garcia?",
        //      "¿en que habitacion esta Lopez?". Scope de hotel + permiso.
        $nombreBuscado = $this->detectarBusquedaHuesped($norm);
        if ($nombreBuscado !== null) {
            $r = $this->responderBusquedaHuesped($hotelId, $nombreBuscado);
            $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', 'busca:huesped', 0, 0);
            return $this->conSugerencias($r + ['success' => true, 'fuente' => 'reglas', 'intent' => 'busca:huesped'], 'busca:huesped', $hotelId);
        }

        // 1) Ayuda "como hago X" con FAQ deterministo. Va PRIMERO: sus frases son
        //    especificas ("como hago un corte") y no deben confundirse con la
        //    pregunta de dato ("como voy de caja" -> saldo). Consciente de modulos:
        //    si el bloque no esta activo, avisa en vez de mandar a una pantalla vacia.
        $faq = $this->detectarFaq($norm, $hotelId);
        if ($faq !== null) {
            $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', $faq['intent'], 0, 0);
            return $this->conSugerencias(['success' => true, 'texto' => $faq['texto'], 'fuente' => 'reglas', 'enlace' => $faq['enlace'], 'intent' => $faq['intent']], $faq['intent'], $hotelId);
        }

        // 2) Reglas de datos (deterministas).
        $intent = $this->detectarIntent($norm, $hotelId);
        if ($intent !== null) {
            $r = $this->responderIntent($hotelId, $intent, $norm);
            $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', $intent, 0, 0);
            return $this->conSugerencias($r + ['success' => true, 'fuente' => 'reglas', 'intent' => $intent], $intent, $hotelId);
        }

        // 3) IA opcional para lo abierto, con el hilo reciente como contexto
        //    (multi-turno): "¿y eso por que?" ya sabe de que veniamos.
        if ($this->iaDisponible($hotelId)) {
            $ia = $this->responderConIa($hotelId, $pregunta, self::sanearHistorial($historial));
            $this->registrar($hotelId, $usuarioId, $pregunta, $ia['success'] ? 'ia' : 'fallback', null, (int) ($ia['tokens_entrada'] ?? 0), (int) ($ia['tokens_salida'] ?? 0));
            if (!empty($ia['success'])) {
                return $this->conSugerencias(['success' => true, 'texto' => $ia['texto'], 'fuente' => 'ia', 'enlace' => null], null, $hotelId);
            }
            return $this->conSugerencias(['success' => true, 'texto' => $ia['message'] ?? $this->textoFallback(), 'fuente' => 'fallback', 'enlace' => null], null, $hotelId);
        }

        // 4) Sin IA: sugerencias.
        $this->registrar($hotelId, $usuarioId, $pregunta, 'fallback', null, 0, 0);
        return $this->conSugerencias(['success' => true, 'texto' => $this->textoFallback(), 'fuente' => 'fallback', 'enlace' => null], null, $hotelId);
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

        // Los intents mas especificos van primero para no ser "robados" por los
        // genericos (ej. "ocupacion de la semana" debe caer en semana, no en hoy).

        // Guardian financiero: va ANTES que 'caja' para que "todo en orden con
        // la caja" no caiga en el saldo. Doble gate: modulo + permiso
        // guardian.view (sus hallazgos nombran usuarios; un operativo no debe
        // poder preguntarle al copiloto por ellos).
        if (($tiene(['guardian', 'vigilancia financiera', 'todo en orden con la caja', 'todo en orden en la caja', 'todo bien con la caja', 'algo raro en caja', 'algo raro en la caja', 'patrones a revisar', 'patron a revisar']))
            && $this->tieneModulo('ia_ejecutiva', $hotelId)
            && $this->puedeVerGuardian()) {
            return 'guardian';
        }

        // Cierre vespertino: mas especifico que 'resumen del dia', va antes.
        if ($tiene(['como cerro el dia', 'cierre del dia', 'resumen de cierre', 'como quedo el dia', 'resumen de la noche', 'cerrar el dia como vamos'])) {
            return 'resumen_cierre';
        }

        if ($tiene(['resumen del dia', 'resumen de hoy', 'como va el dia', 'como vamos hoy', 'como esta el dia', 'briefing', 'dame el resumen'])) {
            return 'resumen_dia';
        }
        if ($tiene(['manana llega', 'llegan manana', 'quien llega manana', 'llegadas de manana', 'entradas de manana', 'reservas de manana'])) {
            return 'llegadas_manana';
        }
        if ($tiene(['salen manana', 'se van manana', 'salidas de manana', 'quien se va manana', 'checkouts de manana', 'checkout de manana'])) {
            return 'salidas_manana';
        }
        if (($tiene(['semana']) && $tiene(['ganancia', 'ingreso', 'vendi', 'gane', 'venta', 'utilidad']))
            || $tiene(['como va la semana', 'como vamos esta semana', 'como va esta semana', 'que tal la semana'])) {
            return 'ganancias_semana';
        }
        if ($tiene(['esta semana', 'proximos dias', 'fin de semana', 'ocupacion de la semana', 'como pinta la semana', 'proximos 7'])) {
            return 'ocupacion_semana';
        }
        // Mantenimiento Plus: va ANTES que 'habitaciones_estado' para que
        // "que mantenimientos vienen" no caiga en el estado de cuartos.
        if (($tiene(['que mantenimientos vienen', 'mantenimientos que vienen', 'proximos mantenimientos', 'mantenimientos proximos', 'mantenimiento preventivo', 'preventivos', 'que servicios vienen', 'proximos servicios', 'servicio del boiler', 'vence el boiler', 'mantenimientos pendientes', 'incidencias abiertas']))
            && $this->tieneModulo('mantenimiento_plus', $hotelId)) {
            return 'mantenimientos_proximos';
        }
        if ($tiene(['mantenimiento', 'habitaciones sucias', 'en limpieza', 'fuera de servicio', 'cuartos sucios', 'estado de las habitaciones', 'estado de habitaciones'])) {
            return 'habitaciones_estado';
        }
        if ($tiene(['quien esta hospedado', 'quienes estan hospedados', 'huespedes actuales', 'quien esta en el hotel', 'quienes estan en el hotel', 'huespedes dentro', 'cuantos huespedes tengo'])) {
            return 'hospedados';
        }
        if (($tiene(['calificaciones bajas', 'bajas calificaciones', 'malas calificaciones', 'calificaciones malas', 'resenas negativas', 'malas resenas', 'resenas malas', 'quejas']))
            && $this->tieneModulo('reputacion', $hotelId)) {
            return 'calificaciones_bajas';
        }
        if (($tiene(['calificacion', 'como me califican', 'que dicen los huespedes', 'mis resenas', 'promedio de encuestas', 'como va mi reputacion']))
            && $this->tieneModulo('reputacion', $hotelId)) {
            return 'calificacion';
        }
        if (($tiene(['cupones activos', 'que cupones tengo', 'cupones vigentes', 'codigos activos']))
            && $this->tieneModulo('promociones', $hotelId)) {
            return 'cupones_activos';
        }
        if (($tiene(['mensajes pendientes', 'mensajes por enviar', 'mensajes de whatsapp', 'whatsapp por enviar', 'whatsapps pendientes', 'cuantos mensajes tengo', 'que mensajes tengo', 'mensajes de hoy', 'whatsapp de hoy']))
            && $this->tieneModulo('canal_whatsapp', $hotelId)) {
            return 'mensajes_pendientes';
        }
        if (($tiene(['por agotarse', 'agotandose', 'se esta acabando', 'stock bajo', 'bajo minimo', 'bajo el minimo', 'inventario bajo', 'por acabarse', 'productos por acabar', 'falta de stock', 'bajo de stock']))
            && $this->tieneModulo('inventario', $hotelId)) {
            return 'inventario_bajo';
        }
        if (($tiene(['debo a proveedor', 'cuanto debo', 'le debo a', 'deuda con proveedor', 'pagos pendientes a proveedor', 'que le debo']))
            && $this->tieneModulo('compras', $hotelId)) {
            return 'cxp_debo';
        }
        if (($tiene(['quien me debe', 'quienes me deben', 'me deben', 'cuanto me deben', 'por cobrar', 'deudas de clientes']))
            && $this->tieneModulo('cuentas_cobrar', $hotelId)) {
            return 'cxc_deben';
        }
        if (($tiene(['nomina del periodo', 'cuanto de nomina', 'cuanto llevo de nomina', 'total de nomina', 'nomina de este periodo', 'cuanto es la nomina', 'cuanto pago de nomina']))
            && $this->tieneModulo('nomina_avanzada', $hotelId)) {
            return 'nomina_periodo';
        }
        if ($tiene(['ocupacion', 'cuartos libres', 'habitaciones libres', 'cuartos disponibles', 'habitaciones disponibles', 'cuanto tengo lleno', 'que tan lleno', 'disponibilidad hoy'])) {
            return 'ocupacion';
        }
        if ($tiene(['noches vendidas', 'noches vendi', 'room night', 'room-night', 'habitaciones vendidas', 'cuartos noche', 'cuantas noches vendi'])) {
            return 'noches_vendidas';
        }
        if ($tiene(['tarifa promedio', 'tarifa media', 'precio promedio', 'tarifa por noche', 'cuanto cobro por noche', 'rate promedio'])) {
            return 'tarifa_promedio';
        }
        if ($tiene(['estancia promedio', 'estadia promedio', 'cuanto se quedan', 'cuanto tiempo se quedan', 'cuantas noches se quedan', 'noches promedio', 'duracion de estancia', 'duracion promedio'])) {
            return 'estancia_promedio';
        }
        if ($tiene(['cancelacion', 'canceladas', 'me cancelaron', 'cuantas cancel', 'tasa de cancelacion', 'reservas canceladas'])) {
            return 'cancelaciones_mes';
        }
        if ($tiene(['entraron hoy', 'reservas que entraron', 'cuantas reservas entraron', 'reservas nuevas', 'reservaciones nuevas', 'se registraron hoy', 'cuantas reservas hicieron', 'reservas de hoy nuevas'])) {
            return 'reservas_hoy';
        }
        if ($tiene(['que limpiar', 'limpiar hoy', 'por limpiar', 'que hay que limpiar', 'pendientes de limpieza', 'habitaciones a limpiar', 'cuartos por limpiar', 'que limpio'])) {
            return 'limpiar_hoy';
        }
        if ($tiene(['mejor o peor', 'mejor que el mes', 'peor que el mes', 'comparado con el mes', 'comparada con el mes', 'comparacion con el mes', 'contra el mes pasado', 'vs el mes pasado', 'versus el mes pasado', 'como voy comparado'])) {
            return 'comparar_meses';
        }
        if ($tiene(['mejor dia', 'peor dia', 'dia mas fuerte', 'dia mas flojo', 'mejor jornada', 'mi mejor dia', 'dia que mas'])) {
            return 'dia_top';
        }
        if ($tiene(['tarjeta', 'metodo de pago', 'metodos de pago', 'efectivo o tarjeta', 'como me pagan', 'como me pagaron'])) {
            return 'pago_metodo';
        }
        if ($tiene(['en que gaste', 'en que gasto', 'en que se me va', 'mayores gastos', 'gastos mas grandes', 'categorias de gasto', 'donde se va el dinero', 'en que se gasta'])) {
            return 'gastos_categoria';
        }
        if ($tiene(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo', 'hoy', 'ayer', 'antier', 'anteayer'])
            && $tiene(['ganancia', 'gane', 'ingreso', 'vendi', 'venta', 'utilidad', 'cuanto', 'como nos fue', 'como fue', 'como estuvo', 'como me fue', 'como nos'])) {
            return 'ganancias_dia';
        }
        if ($tiene(['mes pasado', 'mes anterior'])
            && $tiene(['ganancia', 'gane', 'ingreso', 'vendi', 'venta', 'utilidad', 'facture', 'cuanto hice', 'cuanto entro'])) {
            return 'ganancias_mes_pasado';
        }
        if ($tiene(['este mes', 'mes en curso', 'mes actual', 'lo que va del mes', 'del mes'])
            && $tiene(['ganancia', 'gane', 'ingreso', 'vendi', 'venta', 'utilidad', 'facture', 'cuanto hice', 'cuanto entro', 'cuanto llevo'])) {
            return 'ganancias_mes_actual';
        }
        if ($tiene(['reserva'])
            && $tiene(['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'setiembre', 'octubre', 'noviembre', 'diciembre', 'proximo mes', 'mes que viene', 'siguiente mes', 'este mes'])) {
            return 'reservaciones_mes';
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

    /**
     * Deep-link de limpieza: la pantalla operativa que le toque a este hotel.
     * Solo navegacion (las acciones ejecutables no viven en esta capa).
     */
    private function urlLimpieza(int $hotelId): string
    {
        return $this->tieneModulo('camarista', $hotelId) ? 'camarista' : 'habitaciones';
    }

    private function responderIntent(int $hotelId, string $intent, string $norm = ''): array
    {
        switch ($intent) {
            case 'guardian':
                require_once __DIR__ . '/../models/GuardianPatrones.php';
                $g = (new GuardianPatrones())->reporteReadOnlyPorHotel($hotelId);
                $gt = (array) ($g['totales'] ?? []);
                $altos = (int) ($gt['alta'] ?? 0);
                $hallazgos = (int) ($gt['hallazgos'] ?? 0);
                $reglas = count((array) ($g['alertas'] ?? []));
                if ($hallazgos === 0) {
                    return [
                        'texto' => "**Hoy: todo en orden.** El Guardián revisó {$reglas} patrones de comportamiento en los últimos "
                            . (int) ($g['ventana']['dias'] ?? 30) . ' días y nada se sale del patrón de tu hotel.',
                        'enlace' => ['url' => 'ia/vigilancia-financiera', 'texto' => 'Abrir el Guardián'],
                    ];
                }
                return [
                    'texto' => "El Guardián tiene **{$hallazgos} patrón(es) a revisar**"
                        . ($altos > 0 ? " ({$altos} de prioridad alta)" : '')
                        . '. No es una acusación: son puntos donde conviene confirmar con el equipo. El detalle está en la app.',
                    'enlace' => ['url' => 'ia/vigilancia-financiera', 'texto' => 'Abrir el Guardián'],
                ];

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
                    return [
                        'texto' => 'No hay ningun corte de caja abierto en este momento.',
                        'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
                        'acciones' => [['label' => 'Ir a Caja', 'url' => 'caja']],
                    ];
                }
                return [
                    'texto' => "El corte de caja abierto tiene un **efectivo esperado de \${$c['esperado']}** (abierto desde {$c['desde']}).",
                    'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
                    'acciones' => [
                        ['label' => 'Ir a Caja', 'url' => 'caja'],
                        ['label' => 'Hacer el corte', 'url' => 'caja#cop-ancla-corte'],
                    ],
                ];

            case 'llegadas':
                $l = $this->reservasPorFecha($hotelId, 'entrada');
                if ($l['total'] === 0) {
                    return ['texto' => 'No tienes llegadas programadas para hoy.', 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                return [
                    'texto' => "Hoy llegan **{$l['total']} reservacion(es)**" . ($l['nombres'] !== '' ? ': ' . $l['nombres'] : '') . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                    'acciones' => [
                        ['label' => 'Ver llegadas', 'url' => 'reservaciones'],
                        ['label' => 'Ver habitaciones', 'url' => 'habitaciones'],
                    ],
                ];

            case 'salidas':
                $s = $this->reservasPorFecha($hotelId, 'salida');
                if ($s['total'] === 0) {
                    return ['texto' => 'No hay salidas programadas para hoy.', 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                return [
                    'texto' => "Hoy salen **{$s['total']} reservacion(es)**" . ($s['nombres'] !== '' ? ': ' . $s['nombres'] : '') . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                    'acciones' => [
                        ['label' => 'Ver salidas', 'url' => 'reservaciones'],
                        ['label' => 'Ir a limpieza', 'url' => $this->urlLimpieza($hotelId)],
                    ],
                ];

            case 'no_shows':
                $n = $this->pendientes($hotelId, 'no_show');
                return [
                    'texto' => $n === 0
                        ? 'No tienes no-shows pendientes. Todo en orden.'
                        : "Tienes **{$n} no-show(s)**: reservaciones confirmadas cuya llegada ya paso y no hicieron check-in.",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                    'acciones' => $n > 0 ? [['label' => 'Revisar no-shows', 'url' => 'reservaciones']] : [],
                ];

            case 'checkouts_vencidos':
                $v = $this->pendientes($hotelId, 'checkout_vencido');
                return [
                    'texto' => $v === 0
                        ? 'No hay checkouts vencidos: nadie sigue dentro despues de su fecha de salida.'
                        : "Hay **{$v} checkout(s) vencido(s)**: huespedes con check-in cuya salida ya paso.",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                    'acciones' => $v > 0 ? [['label' => 'Revisar checkouts', 'url' => 'reservaciones']] : [],
                ];

            case 'motor_conciliar':
                $m = $this->motorPorConciliar($hotelId);
                return [
                    'texto' => $m['n'] === 0
                        ? 'No tienes pagos online pendientes de conciliar a Caja.'
                        : "Tienes **{$m['n']} pago(s) online por conciliar** a Caja, por \${$m['monto']} en total.",
                    'enlace' => ['url' => 'motor-reservas', 'texto' => 'Ir al motor'],
                    'acciones' => $m['n'] > 0 ? [['label' => 'Conciliar en el motor', 'url' => 'motor-reservas']] : [],
                ];

            case 'mensajes_pendientes':
                require_once __DIR__ . '/CanalWhatsAppService.php';
                $mw = (new CanalWhatsAppService())->contarPendientesHoy($hotelId);
                if ($mw === 0) {
                    return [
                        'texto' => 'La cola de WhatsApp esta al dia: no hay mensajes pendientes de enviar hoy.',
                        'enlace' => ['url' => 'mensajes', 'texto' => 'Abrir Mensajes'],
                    ];
                }
                return [
                    'texto' => "Tienes **{$mw} mensaje" . ($mw === 1 ? '' : 's') . " de WhatsApp por enviar hoy** (confirmaciones, recordatorios de llegada y encuestas de salida). Cada uno ya viene redactado: solo falta tocar Enviar.",
                    'enlace' => ['url' => 'mensajes', 'texto' => 'Abrir la cola de Mensajes'],
                    'acciones' => [['label' => 'Abrir Mensajes', 'url' => 'mensajes']],
                ];

            case 'resumen_dia':
                return $this->resumenDelDia($hotelId);

            case 'resumen_cierre':
                return $this->resumenDeCierre($hotelId);

            case 'llegadas_manana':
                $lm = $this->reservasPorFecha($hotelId, 'entrada', 1);
                return [
                    'texto' => $lm['total'] === 0
                        ? 'Manana no tienes llegadas programadas.'
                        : "Manana llegan **{$lm['total']} reservacion(es)**" . ($lm['nombres'] !== '' ? ': ' . $lm['nombres'] : '') . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'salidas_manana':
                $sm = $this->reservasPorFecha($hotelId, 'salida', 1);
                return [
                    'texto' => $sm['total'] === 0
                        ? 'Manana no tienes salidas programadas.'
                        : "Manana salen **{$sm['total']} reservacion(es)**" . ($sm['nombres'] !== '' ? ': ' . $sm['nombres'] : '') . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                    'acciones' => $sm['total'] > 0 ? [
                        ['label' => 'Ver salidas', 'url' => 'reservaciones'],
                        ['label' => 'Ir a limpieza', 'url' => $this->urlLimpieza($hotelId)],
                    ] : [],
                ];

            case 'ocupacion_semana':
                $sem = $this->ocupacionProximos7($hotelId);
                if ($sem === null) {
                    return ['texto' => 'No tienes habitaciones activas registradas todavia.', 'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones']];
                }
                $texto = "De tus **{$sem['activas']} habitaciones**, los proximos 7 dias promedian **{$sem['promedio']}% de ocupacion**.";
                if ($sem['mejor_dia'] !== null) {
                    $texto .= " El dia mas fuerte es el {$sem['mejor_dia']}: **{$sem['mejor_habs']} de {$sem['activas']} ocupadas** ({$sem['mejor_pct']}%).";
                }
                $texto .= " Hoy tienes **{$sem['libres_hoy']} libres**.";
                $enlace = $this->tieneModulo('forecast', $hotelId)
                    ? ['url' => 'forecast', 'texto' => 'Ver forecast completo']
                    : ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'];
                return ['texto' => $texto, 'enlace' => $enlace, 'viz' => $this->vizOcupacionSemana($sem)];

            case 'habitaciones_estado':
                $he = $this->habitacionesPorEstado($hotelId);
                if (empty($he)) {
                    return ['texto' => 'No tienes habitaciones activas registradas todavia.', 'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones']];
                }
                $partes = [];
                foreach (['disponible' => 'disponibles', 'ocupada' => 'ocupadas', 'limpieza' => 'en limpieza', 'mantenimiento' => 'en mantenimiento'] as $estado => $etiqueta) {
                    if (!empty($he[$estado])) {
                        $partes[] = "{$he[$estado]} {$etiqueta}";
                    }
                }
                $accionesHe = [['label' => 'Ver habitaciones', 'url' => 'habitaciones']];
                if (!empty($he['limpieza'])) {
                    $accionesHe[] = ['label' => 'Ir a limpieza', 'url' => $this->urlLimpieza($hotelId)];
                }
                return [
                    'texto' => 'Asi estan tus habitaciones ahorita: **' . implode(', ', $partes) . '**.',
                    'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones'],
                    'acciones' => $accionesHe,
                ];

            case 'mantenimientos_proximos':
                $mp = $this->mantenimientosProximos($hotelId);
                if (!$mp['hay']) {
                    return [
                        'texto' => 'No tienes preventivos por vencer ni incidencias de mantenimiento abiertas. Todo al dia.',
                        'enlace' => ['url' => 'mantenimientos/activos', 'texto' => 'Ver activos'],
                    ];
                }
                $partes = [];
                if (!empty($mp['proximos'])) {
                    $partes[] = 'Proximos servicios: ' . implode('; ', $mp['proximos']);
                }
                if ($mp['incidencias'] > 0) {
                    $partes[] = '**' . $mp['incidencias'] . ' incidencia(s) de mantenimiento abiertas**';
                }
                return [
                    'texto' => implode('. ', $partes) . '.',
                    'enlace' => ['url' => 'mantenimientos/activos', 'texto' => 'Ver mantenimiento'],
                ];

            case 'hospedados':
                $h = $this->hospedadosAhora($hotelId);
                return [
                    'texto' => $h['total'] === 0
                        ? 'Ahorita no tienes huespedes con check-in activo.'
                        : "Tienes **{$h['total']} reservacion(es) con huespedes dentro**" . ($h['nombres'] !== '' ? ': ' . $h['nombres'] : '') . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'calificacion':
                $cal = $this->calificacionPromedio($hotelId);
                return [
                    'texto' => $cal['respondidas'] === 0
                        ? 'Aun no tienes encuestas respondidas en los ultimos 90 dias.'
                        : "Tu calificacion promedio es **{$cal['promedio']} de 5** con {$cal['respondidas']} encuesta(s) respondida(s) en los ultimos 90 dias.",
                    'enlace' => ['url' => 'reputacion', 'texto' => 'Ver reputacion'],
                ];

            case 'cupones_activos':
                $cu = $this->cuponesActivos($hotelId);
                return [
                    'texto' => $cu['n'] === 0
                        ? 'No tienes cupones activos en este momento.'
                        : "Tienes **{$cu['n']} cupon(es) activo(s)**" . ($cu['codigos'] !== '' ? ': ' . $cu['codigos'] : '') . '.',
                    'enlace' => ['url' => 'motor-reservas/cupones', 'texto' => 'Ver cupones'],
                ];

            case 'ganancias_mes_pasado':
                $g = $this->gananciasMesPasado($hotelId);
                if (!$g['hay']) {
                    return ['texto' => "No tengo movimientos de caja registrados del mes pasado ({$g['mes']} {$g['anio']}).", 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                $enlaceGan = $this->tieneModulo('reportes', $hotelId)
                    ? ['url' => 'reportes', 'texto' => 'Ver reportes']
                    : ['url' => 'caja', 'texto' => 'Ir a Caja'];
                return [
                    'texto' => "El mes pasado ({$g['mes']} {$g['anio']}) registraste **ingresos por \${$g['ingresos']}** y gastos por \${$g['gastos']}, "
                        . "para una **ganancia neta de \${$g['neto']}** (ingresos menos gastos de caja).",
                    'enlace' => $enlaceGan,
                ];

            case 'ganancias_mes_actual':
                $ga = $this->gananciasMesActual($hotelId);
                if (!$ga['hay']) {
                    return ['texto' => "Aun no tienes movimientos de caja registrados este mes ({$ga['mes']} {$ga['anio']}).", 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                $enlaceGa = $this->tieneModulo('reportes', $hotelId)
                    ? ['url' => 'reportes', 'texto' => 'Ver reportes']
                    : ['url' => 'caja', 'texto' => 'Ir a Caja'];
                return [
                    'texto' => "En lo que va de {$ga['mes']} {$ga['anio']} llevas **ingresos por \${$ga['ingresos']}** y gastos por \${$ga['gastos']}, "
                        . "para una **ganancia neta de \${$ga['neto']}** (al dia de hoy, ingresos menos gastos de caja).",
                    'enlace' => $enlaceGa,
                ];

            case 'comparar_meses':
                $cmp = $this->compararMesActualVsPasado($hotelId);
                if (!$cmp['hayActual'] && !$cmp['hayPasado']) {
                    return ['texto' => 'Todavia no tengo movimientos de caja de este mes ni del mismo tramo del mes pasado para compararte.', 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                $dif = $cmp['netoActualNum'] - $cmp['netoPasadoNum'];
                $pct = $cmp['netoPasadoNum'] > 0 ? round(abs($dif) / $cmp['netoPasadoNum'] * 100) : null;
                $difFmt = number_format(abs($dif), 2);
                if ($dif > 0) {
                    $veredicto = "Vas **mejor**: \${$difFmt} mas de ganancia neta" . ($pct !== null ? " (+{$pct}%)" : '') . ' que a estas alturas del mes pasado.';
                } elseif ($dif < 0) {
                    $veredicto = "Vas **por debajo**: \${$difFmt} menos de ganancia neta" . ($pct !== null ? " (-{$pct}%)" : '') . ' que a estas alturas del mes pasado.';
                } else {
                    $veredicto = 'Vas **igual** que a estas alturas del mes pasado.';
                }
                $maxAbs = max(abs($cmp['netoActualNum']), abs($cmp['netoPasadoNum']), 1);
                return [
                    'texto' => "Comparando los primeros {$cmp['dia']} dias del mes: en {$cmp['mesActual']} llevas **\${$cmp['netoActual']}** de ganancia neta, "
                        . "contra **\${$cmp['netoPasado']}** en el mismo tramo de {$cmp['mesPasado']}.\n{$veredicto}",
                    'enlace' => $this->tieneModulo('reportes', $hotelId) ? ['url' => 'reportes', 'texto' => 'Ver reportes'] : ['url' => 'caja', 'texto' => 'Ir a Caja'],
                    'viz' => ['tipo' => 'barras', 'items' => [
                        ['etiqueta' => ucfirst($cmp['mesActual']), 'valor' => '$' . $cmp['netoActual'], 'pct' => (int) round(abs($cmp['netoActualNum']) * 100 / $maxAbs), 'destacar' => $cmp['netoActualNum'] >= $cmp['netoPasadoNum']],
                        ['etiqueta' => ucfirst($cmp['mesPasado']), 'valor' => '$' . $cmp['netoPasado'], 'pct' => (int) round(abs($cmp['netoPasadoNum']) * 100 / $maxAbs), 'destacar' => $cmp['netoPasadoNum'] > $cmp['netoActualNum']],
                    ]],
                ];

            case 'ganancias_dia':
                $dref = $this->resolverDiaReferido($norm);
                if ($dref === null) {
                    return ['texto' => 'No identifique de que dia me hablas. Prueba con "como nos fue el jueves" o "cuanto vendi ayer".', 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                $gd = $this->gananciasEntre($hotelId, $dref['fecha'], date('Y-m-d', strtotime($dref['fecha'] . ' +1 day')));
                if (!$gd['hay']) {
                    return ['texto' => "No tengo movimientos de caja registrados para {$dref['texto']}.", 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                return [
                    'texto' => ucfirst($dref['texto']) . " registraste **ingresos por \${$gd['ingresos']}** y gastos por \${$gd['gastos']}, para una **ganancia neta de \${$gd['neto']}**.",
                    'enlace' => $this->tieneModulo('reportes', $hotelId) ? ['url' => 'reportes', 'texto' => 'Ver reportes'] : ['url' => 'caja', 'texto' => 'Ir a Caja'],
                ];

            case 'dia_top':
                $dt = $this->diasTopMes($hotelId);
                if (!$dt['hay']) {
                    return ['texto' => 'Aun no tengo movimientos de caja este mes para sacar tu mejor dia.', 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                $txtDia = "Tu **mejor dia** de este mes fue el {$dt['mejorFecha']} con **\${$dt['mejorNeto']}** de ganancia neta.";
                if ($dt['mejorFecha'] !== $dt['peorFecha']) {
                    $txtDia .= " El mas flojo fue el {$dt['peorFecha']} (\${$dt['peorNeto']}).";
                }
                return ['texto' => $txtDia, 'enlace' => $this->tieneModulo('reportes', $hotelId) ? ['url' => 'reportes', 'texto' => 'Ver reportes'] : ['url' => 'caja', 'texto' => 'Ir a Caja']];

            case 'ganancias_semana':
                $gs = $this->gananciasEntre($hotelId, date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('+1 day')));
                if (!$gs['hay']) {
                    return ['texto' => 'Aun no tengo movimientos de caja de esta semana.', 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                return [
                    'texto' => "En lo que va de la semana llevas **ingresos por \${$gs['ingresos']}** y gastos por \${$gs['gastos']}, para una **ganancia neta de \${$gs['neto']}**.",
                    'enlace' => $this->tieneModulo('reportes', $hotelId) ? ['url' => 'reportes', 'texto' => 'Ver reportes'] : ['url' => 'caja', 'texto' => 'Ir a Caja'],
                ];

            case 'pago_metodo':
                $pm = $this->ingresosPorMetodo($hotelId);
                if (!$pm['hay']) {
                    return ['texto' => "Aun no tengo ingresos registrados este mes ({$pm['mes']}) para separarlos por metodo.", 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                $maxPm = max($pm['efectivo_num'], $pm['tarjeta_num'], $pm['transferencia_num'], 1);
                return [
                    'texto' => "Ingresos de {$pm['mes']} por metodo: **efectivo \${$pm['efectivo']}**, **tarjeta \${$pm['tarjeta']}**, transferencia \${$pm['transferencia']}.",
                    'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
                    'viz' => ['tipo' => 'barras', 'items' => [
                        ['etiqueta' => 'Efectivo', 'valor' => '$' . $pm['efectivo'], 'pct' => (int) round($pm['efectivo_num'] * 100 / $maxPm)],
                        ['etiqueta' => 'Tarjeta', 'valor' => '$' . $pm['tarjeta'], 'pct' => (int) round($pm['tarjeta_num'] * 100 / $maxPm)],
                        ['etiqueta' => 'Transferencia', 'valor' => '$' . $pm['transferencia'], 'pct' => (int) round($pm['transferencia_num'] * 100 / $maxPm)],
                    ]],
                ];

            case 'gastos_categoria':
                $gc = $this->gastosPorCategoria($hotelId);
                if (!$gc['hay']) {
                    return ['texto' => "No tengo gastos registrados este mes ({$gc['mes']}).", 'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja']];
                }
                return [
                    'texto' => "Tus mayores gastos de {$gc['mes']}: " . implode(', ', $gc['items']) . '.',
                    'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
                ];

            case 'reservaciones_mes':
                $mref = $this->resolverMesReferido($norm);
                if ($mref === null) {
                    return ['texto' => 'No identifique de que mes me hablas. Prueba con "cuantas reservaciones para diciembre".', 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                $rm = $this->reservacionesDelMes($hotelId, $mref['desde'], $mref['hasta']);
                if ($rm['total'] === 0) {
                    return ['texto' => "No hay reservaciones con llegada en **{$mref['etiqueta']}** (sin contar canceladas).", 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                $extraRm = $rm['habitaciones'] > 0 ? " ({$rm['habitaciones']} habitacion(es), {$rm['noches']} noche(s)-habitacion)" : '';
                return [
                    'texto' => "En **{$mref['etiqueta']}** hay **{$rm['total']} reservacion(es)** con llegada" . $extraRm . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'noches_vendidas':
                $mrv = $this->metricasReservasMes($hotelId);
                if ($mrv['n'] === 0) {
                    return ['texto' => "Aun no tienes reservaciones con llegada en {$mrv['mes']}.", 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                return [
                    'texto' => "En {$mrv['mes']} llevas **{$mrv['roomnights']} noches-habitacion vendidas** (de {$mrv['n']} reservacion(es) con llegada este mes).",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'tarifa_promedio':
                $mrv = $this->metricasReservasMes($hotelId);
                if ($mrv['roomnights'] <= 0) {
                    return ['texto' => "Aun no tengo noches vendidas en {$mrv['mes']} para calcular la tarifa promedio.", 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                $adr = number_format($mrv['rev'] / $mrv['roomnights'], 2);
                return [
                    'texto' => "Tu **tarifa promedio (ADR)** de {$mrv['mes']} es **\${$adr} por noche-habitacion** (sobre \$" . number_format($mrv['rev'], 2) . " en {$mrv['roomnights']} noches vendidas).",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'estancia_promedio':
                $mrv = $this->metricasReservasMes($hotelId);
                if ($mrv['n'] === 0) {
                    return ['texto' => "Aun no tengo reservaciones con llegada en {$mrv['mes']} para el promedio de estancia.", 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                $prom = number_format($mrv['nights'] / $mrv['n'], 1);
                return [
                    'texto' => "Tus huespedes con llegada en {$mrv['mes']} se quedan en promedio **{$prom} noche(s)** por reservacion.",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'cancelaciones_mes':
                $cm = $this->cancelacionesMes($hotelId);
                if ($cm['total'] === 0) {
                    return ['texto' => "No tienes reservaciones con llegada en {$cm['mes']} todavia.", 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                if ($cm['canceladas'] === 0) {
                    return ['texto' => "En {$cm['mes']} no tienes cancelaciones de {$cm['total']} reservaciones. 🎉", 'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones']];
                }
                $pctCanc = (int) round($cm['canceladas'] * 100 / $cm['total']);
                return [
                    'texto' => "En {$cm['mes']} llevas **{$cm['canceladas']} cancelacion(es)** de {$cm['total']} reservaciones (**{$pctCanc}% de cancelacion**).",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'reservas_hoy':
                $rhoy = $this->reservasCreadasHoy($hotelId);
                return [
                    'texto' => $rhoy === 0
                        ? 'Hoy no se ha registrado ninguna reservacion nueva todavia.'
                        : "Hoy se han registrado **{$rhoy} reservacion(es) nueva(s)**.",
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
                ];

            case 'limpiar_hoy':
                $lh = $this->limpiezaHoy($hotelId);
                if ($lh['limpieza'] === 0 && $lh['salidas'] === 0) {
                    return ['texto' => 'No tienes habitaciones en limpieza ni salidas pendientes hoy. Todo al dia. ✔', 'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones']];
                }
                $partesLh = [];
                if ($lh['limpieza'] > 0) {
                    $partesLh[] = "**{$lh['limpieza']} habitacion(es) en limpieza** ahora";
                }
                if ($lh['salidas'] > 0) {
                    $partesLh[] = "**{$lh['salidas']} salida(s) de hoy** por limpiar";
                }
                $accionesLh = [['label' => 'Ir a limpieza', 'url' => $this->urlLimpieza($hotelId)]];
                if ($this->tieneModulo('tareas', $hotelId)) {
                    $accionesLh[] = ['label' => 'Ver tareas', 'url' => 'tareas'];
                }
                return [
                    'texto' => 'Para limpieza: ' . implode(' y ', $partesLh) . '.',
                    'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones'],
                    'acciones' => $accionesLh,
                ];

            case 'calificaciones_bajas':
                $cb = $this->calificacionesBajas($hotelId);
                return [
                    'texto' => $cb['n'] === 0
                        ? 'No tienes calificaciones bajas (3 o menos) en los ultimos 90 dias. 👏'
                        : "Tienes **{$cb['n']} calificacion(es) baja(s)** (3 o menos) en los ultimos 90 dias, promedio {$cb['prom']}/5. Vale la pena revisarlas.",
                    'enlace' => ['url' => 'reputacion', 'texto' => 'Ver reputacion'],
                    'acciones' => $cb['n'] > 0 ? [['label' => 'Revisar calificaciones', 'url' => 'reputacion']] : [],
                ];

            case 'inventario_bajo':
                $ib = $this->inventarioBajo($hotelId);
                if ($ib['n'] === 0) {
                    return ['texto' => 'No tienes productos por agotarse; todo esta por encima de su minimo. ✔', 'enlace' => ['url' => 'inventario', 'texto' => 'Ver inventario']];
                }
                $muestraIb = implode(', ', $ib['items']);
                if ($ib['n'] > count($ib['items'])) {
                    $muestraIb .= ' y ' . ($ib['n'] - count($ib['items'])) . ' mas';
                }
                $accionesIb = [['label' => 'Ver inventario', 'url' => 'inventario']];
                if ($this->tieneModulo('compras', $hotelId)) {
                    $accionesIb[] = ['label' => 'Registrar compra', 'url' => 'compras'];
                }
                return [
                    'texto' => "Tienes **{$ib['n']} producto(s) por agotarse** (en o bajo su minimo): {$muestraIb}.",
                    'enlace' => ['url' => 'inventario', 'texto' => 'Ver inventario'],
                    'acciones' => $accionesIb,
                ];

            case 'cxp_debo':
                $cxp = $this->deudaProveedores($hotelId);
                if ($cxp['n'] === 0) {
                    return ['texto' => 'No tienes cuentas por pagar pendientes con proveedores. ✔', 'enlace' => ['url' => 'compras', 'texto' => 'Ver compras']];
                }
                $vtxtP = $cxp['vencidas'] > 0 ? " ({$cxp['vencidas']} vencida(s))" : '';
                return [
                    'texto' => "Debes **\${$cxp['saldo']}** a proveedores en **{$cxp['n']} cuenta(s) por pagar**{$vtxtP}.",
                    'enlace' => ['url' => 'compras', 'texto' => 'Ver compras'],
                ];

            case 'cxc_deben':
                $cxc = $this->porCobrar($hotelId);
                if ($cxc['n'] === 0) {
                    return ['texto' => 'Nadie te debe ahorita; no tienes cuentas por cobrar pendientes. ✔', 'enlace' => ['url' => 'cuentas-por-cobrar', 'texto' => 'Ver cuentas por cobrar']];
                }
                $vtxtC = $cxc['vencidas'] > 0 ? " ({$cxc['vencidas']} vencida(s))" : '';
                return [
                    'texto' => "Te deben **\${$cxc['saldo']}** en **{$cxc['n']} cuenta(s) por cobrar**{$vtxtC}.",
                    'enlace' => ['url' => 'cuentas-por-cobrar', 'texto' => 'Ver cuentas por cobrar'],
                    'acciones' => [['label' => 'Ver quien te debe', 'url' => 'cuentas-por-cobrar']],
                ];

            case 'nomina_periodo':
                $np = $this->nominaPeriodo($hotelId);
                if ($np === null) {
                    return ['texto' => 'Aun no tienes ningun periodo de nomina cerrado.', 'enlace' => ['url' => 'nomina', 'texto' => 'Ir a Nomina']];
                }
                $rango = date('d/m', strtotime((string) $np['fecha_inicio'])) . '-' . date('d/m', strtotime((string) $np['fecha_fin']));
                $neto = number_format((float) $np['neto_sugerido_total'], 2);
                $txtNp = "Tu ultimo periodo de nomina ({$np['tipo_periodo']}, {$rango}, {$np['estado']}): **{$np['trabajadores_total']} trabajador(es)**, neto **\${$neto}**";
                if ((float) $np['pendiente_pago_total'] > 0) {
                    $txtNp .= ', pendiente de pago **$' . number_format((float) $np['pendiente_pago_total'], 2) . '**';
                }
                return ['texto' => $txtNp . '.', 'enlace' => ['url' => 'nomina', 'texto' => 'Ir a Nomina']];
        }

        return ['texto' => $this->textoFallback(), 'enlace' => null];
    }

    // ───────────────────────── Memoria de conversacion ─────────────────────────

    /**
     * Resuelve un seguimiento corto contra el intent de la pregunta anterior:
     * "¿y manana?" tras las llegadas de hoy son las llegadas de manana.
     * Solo aplica a frases elipticas (cortas, sin tema propio) y solo cambia
     * QUE plantilla responde; una pregunta completa sigue su camino normal.
     */
    private function detectarSeguimiento(string $norm, string $intentPrevio, int $hotelId): ?string
    {
        $intentPrevio = strtolower(trim($intentPrevio));
        if ($intentPrevio === '' || !preg_match('/^[a-z0-9_:]{1,40}$/', $intentPrevio)) {
            return null;
        }

        // Forma eliptica: se quita puntuacion y el "y" inicial; lo que queda
        // debe ser corto ("manana", "el mes pasado", "las bajas").
        $limpia = trim((string) preg_replace('/[¿?¡!.,]/u', ' ', $norm));
        $limpia = trim((string) preg_replace('/^y\s+/', '', $limpia));
        $limpia = trim((string) preg_replace('/\s+/', ' ', $limpia));
        if ($limpia === '' || mb_strlen($limpia) > 22) {
            return null;
        }

        // Familia del tema anterior -> transiciones por modificador. El norm
        // original se pasa a responderIntent, asi "y el jueves" o "y diciembre"
        // resuelven su dia/mes con los resolvers de siempre.
        $familias = [
            'llegadas' => 'llegadas', 'llegadas_manana' => 'llegadas',
            'salidas' => 'salidas', 'salidas_manana' => 'salidas',
            'ocupacion' => 'ocupacion', 'ocupacion_semana' => 'ocupacion',
            'ganancias_dia' => 'ganancias', 'ganancias_semana' => 'ganancias',
            'ganancias_mes_actual' => 'ganancias', 'ganancias_mes_pasado' => 'ganancias',
            'comparar_meses' => 'ganancias', 'dia_top' => 'ganancias',
            'reservaciones_mes' => 'reservaciones',
            'calificacion' => 'reputacion', 'calificaciones_bajas' => 'reputacion',
        ];
        $familia = $familias[$intentPrevio] ?? null;
        if ($familia === null) {
            return null;
        }

        $dias = 'lunes|martes|miercoles|jueves|viernes|sabado|domingo|ayer|antier|anteayer|hoy';
        $meses = 'enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre|proximo mes|mes que viene|este mes';

        // [familia => [patron regex sobre $limpia => intent destino]] en orden
        // (los patrones mas especificos primero).
        $transiciones = [
            'llegadas' => [
                '/\bmanana\b/' => 'llegadas_manana',
                '/\bhoy\b/' => 'llegadas',
                '/\bsalidas?\b|se van/' => 'salidas',
            ],
            'salidas' => [
                '/\bmanana\b/' => 'salidas_manana',
                '/\bhoy\b/' => 'salidas',
                '/\bllegadas?\b|llegan/' => 'llegadas',
            ],
            'ocupacion' => [
                '/\bsemana\b|proximos dias/' => 'ocupacion_semana',
                '/\bhoy\b/' => 'ocupacion',
            ],
            'ganancias' => [
                '/mes pasado|mes anterior/' => 'ganancias_mes_pasado',
                '/este mes|mes actual/' => 'ganancias_mes_actual',
                '/\bsemana\b/' => 'ganancias_semana',
                '/\b(' . $dias . ')\b/' => 'ganancias_dia',
            ],
            'reservaciones' => [
                '/\b(' . $meses . ')\b/' => 'reservaciones_mes',
            ],
            'reputacion' => [
                '/\bbajas?\b|\bmalas?\b|quejas/' => 'calificaciones_bajas',
                '/promedio|general/' => 'calificacion',
            ],
        ];

        foreach (($transiciones[$familia] ?? []) as $patron => $intentNuevo) {
            if (preg_match($patron, $limpia)) {
                // Los temas de bloque respetan su gate igual que siempre.
                if (in_array($intentNuevo, ['calificacion', 'calificaciones_bajas'], true) && !$this->tieneModulo('reputacion', $hotelId)) {
                    return null;
                }
                return $intentNuevo;
            }
        }

        return null;
    }

    // ───────────────────────── Busqueda de huesped por nombre ─────────────────────────

    /**
     * Detecta "¿tiene reserva Garcia?" / "¿en que habitacion esta Lopez?" y
     * extrae el nombre a buscar. Devuelve null si la frase no trae un nombre
     * usable (asi "¿quien esta hospedado?" sigue siendo el intent general).
     */
    private function detectarBusquedaHuesped(string $norm): ?string
    {
        $disparadores = [
            'tiene reservacion ', 'tiene reserva ', 'hay reservacion de ', 'hay reserva de ',
            'reservacion a nombre de ', 'reserva a nombre de ', 'a nombre de ',
            'en que habitacion esta ', 'en cual habitacion esta ', 'que habitacion tiene ',
            'esta hospedado ', 'esta hospedada ', 'busca a ', 'buscame a ', 'buscar a ',
            'cuando llega ', 'cuando se va ', 'cuando sale ',
        ];

        foreach ($disparadores as $t) {
            $pos = strpos($norm, $t);
            if ($pos === false) {
                continue;
            }
            $nombre = $this->limpiarNombreBuscado(substr($norm, $pos + strlen($t)));
            if ($nombre !== null) {
                return $nombre;
            }
        }

        return null;
    }

    /** Recorta la cola de la frase a un nombre buscable; null si no hay nombre real. */
    private function limpiarNombreBuscado(string $resto): ?string
    {
        $resto = trim((string) preg_replace('/[¿?¡!.,;]/u', ' ', $resto));
        // Articulos/tratamientos al inicio.
        $resto = (string) preg_replace('/^(el|la|los|las|a|al|don|dona|sr|sra|srta)\s+/u', '', $resto);
        // Colas que no son nombre ("hoy", "manana", "por favor").
        $resto = (string) preg_replace('/\b(hoy|manana|pasado manana|por favor|porfa)\b/u', ' ', $resto);
        $resto = trim((string) preg_replace('/\s+/', ' ', $resto));

        if ($resto === '' || mb_strlen($resto) < 3 || mb_strlen($resto) > 60) {
            return null;
        }
        if (!preg_match('/[a-z]/', $resto)) {
            return null;
        }
        // Si empieza con preposicion/relleno no es un nombre ("...hospedado en el hotel").
        if (preg_match('/^(en|con|de|del|para|por|mi|mis|tu|tus|algun|alguna)\b/', $resto)) {
            return null;
        }
        // Palabras que delatan que NO es un nombre propio.
        foreach (['alguien', 'cuanto', 'cuanta', 'quien', 'reserva', 'habitacion', 'huesped', 'cliente'] as $generica) {
            if ($resto === $generica) {
                return null;
            }
        }

        return $resto;
    }

    /**
     * Busca al huesped por nombre en las reservaciones VIGENTES del hotel
     * (dentro ahora o con llegada/salida por venir) y responde segun haya
     * cero, uno o varios resultados. Mismo permiso que la pantalla.
     */
    private function responderBusquedaHuesped(int $hotelId, string $nombre): array
    {
        if (function_exists('can') && !can('reservaciones.view')) {
            return ['texto' => 'Tu rol no tiene permiso para ver los datos de reservaciones. Pidele el acceso a tu gerente.', 'enlace' => null];
        }

        $like = '%' . addcslashes($nombre, "%_\\") . '%';
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.id, r.estado, r.fecha_entrada, r.fecha_salida, h.nombre_completo
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id AND h.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ?
                   AND h.nombre_completo LIKE ?
                   AND r.estado IN ('pendiente', 'confirmada', 'checked_in')
                   AND (r.estado = 'checked_in' OR r.fecha_salida >= CURDATE())
                 ORDER BY (r.estado = 'checked_in') DESC, r.fecha_entrada ASC
                 LIMIT 4"
            );
            $stmt->execute([$hotelId, $like]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto: error busqueda huesped: ' . $e->getMessage());
            $filas = [];
        }

        $nombreBonito = ucwords($nombre);

        if (empty($filas)) {
            $enlace = $this->tieneModulo('huespedes', $hotelId)
                ? ['url' => 'huespedes', 'texto' => 'Buscar en Huespedes']
                : ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'];
            return [
                'texto' => "No encuentro reservaciones vigentes a nombre de **{$nombreBonito}** (busque en huespedes dentro y llegadas por venir).",
                'enlace' => $enlace,
            ];
        }

        $estados = ['pendiente' => 'pendiente de confirmar', 'confirmada' => 'confirmada', 'checked_in' => 'con el huesped dentro'];

        if (count($filas) === 1) {
            $r = $filas[0];
            $quien = trim((string) $r['nombre_completo']);
            $estado = $estados[(string) $r['estado']] ?? (string) $r['estado'];

            if ((string) $r['estado'] === 'checked_in') {
                $numeros = $this->numerosHabitacionesDeReserva($hotelId, (int) $r['id']);
                $texto = "**{$quien}** esta **hospedado ahora**"
                    . (!empty($numeros) ? ' en la(s) habitacion(es) **' . implode(', ', $numeros) . '**' : '')
                    . ", con salida el {$this->fechaCortaConAnio((string) $r['fecha_salida'])}.";
            } else {
                $texto = "**{$quien}** tiene una reservacion **{$estado}**: llega el {$this->fechaCortaConAnio((string) $r['fecha_entrada'])} y sale el {$this->fechaCortaConAnio((string) $r['fecha_salida'])}.";
            }

            return [
                'texto' => $texto,
                'enlace' => null,
                'acciones' => [['label' => 'Ver la reservacion', 'url' => 'reservaciones/ver/' . (int) $r['id']]],
            ];
        }

        $lineas = ['Encontre **' . count($filas) . ' reservaciones vigentes** que casan con "' . $nombreBonito . '":'];
        foreach ($filas as $r) {
            $quien = trim((string) $r['nombre_completo']);
            $lineas[] = (string) $r['estado'] === 'checked_in'
                ? "• {$quien} — hospedado ahora, sale el " . date('d/m', strtotime((string) $r['fecha_salida']))
                : "• {$quien} — llega el " . date('d/m', strtotime((string) $r['fecha_entrada'])) . ' (' . ($estados[(string) $r['estado']] ?? $r['estado']) . ')';
        }

        return [
            'texto' => implode("\n", $lineas),
            'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
        ];
    }

    /** Numeros de habitacion asignados a una reservacion (scope de hotel). */
    private function numerosHabitacionesDeReserva(int $hotelId, int $reservacionId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT hab.numero
                 FROM reservacion_habitaciones rh
                 INNER JOIN habitaciones hab ON hab.id = rh.habitacion_id AND hab.hotel_id = rh.hotel_id
                 WHERE rh.reservacion_id = ? AND rh.hotel_id = ?
                 ORDER BY hab.numero LIMIT 5"
            );
            $stmt->execute([$reservacionId, $hotelId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'numero');
        } catch (Throwable $e) {
            error_log('Copiloto: error numeros de reserva: ' . $e->getMessage());
            return [];
        }
    }

    // ───────────────────────── Contexto de pantalla ─────────────────────────

    /**
     * Interpreta la ruta relativa que manda el widget ("reservaciones/ver/12",
     * "habitaciones/8", "habitaciones/8/historial") como contexto de entidad.
     * La ruta viene del cliente: NO se confia en ella para permisos; solo dice
     * de que registro habla el usuario.
     */
    private function parseContexto(string $ruta): ?array
    {
        $ruta = strtolower(trim($ruta));
        if ($ruta === '' || strlen($ruta) > 200) {
            return null;
        }
        $ruta = trim((string) (parse_url($ruta, PHP_URL_PATH) ?: ''), '/');

        if (preg_match('#^reservaciones/ver/([0-9]+)#', $ruta, $m)) {
            return ['tipo' => 'reservacion', 'id' => (int) $m[1]];
        }
        if (preg_match('#^habitaciones/([0-9]+)#', $ruta, $m)) {
            return ['tipo' => 'habitacion', 'id' => (int) $m[1]];
        }

        return null;
    }

    /**
     * Intents sobre la entidad visible. Frases especificas + guardas de tema
     * global para no robarle preguntas a los intents de siempre ("cuanto vendi
     * este mes" sigue siendo global aunque haya una reservacion abierta).
     */
    private function detectarIntentEntidad(string $norm, array $contexto): ?string
    {
        foreach (['proveedor', 'nomina', 'online', 'conciliar', 'inventario', 'vendi', 'del mes', 'mes pasado', 'de la semana', 'promedio', 'caja', 'corte'] as $global) {
            if (strpos($norm, $global) !== false) {
                return null;
            }
        }

        $tiene = static function (array $palabras) use ($norm) {
            foreach ($palabras as $p) {
                if (strpos($norm, $p) !== false) {
                    return true;
                }
            }
            return false;
        };

        if ($contexto['tipo'] === 'reservacion') {
            if ($tiene(['cuanto debe', 'cuanto le falta', 'cuanto falta', 'saldo', 'ya pago', 'anticipo', 'cuanto ha pagado', 'esta pagada', 'esta pagado', 'por pagar', 'cuanto es el total', 'total de la reserva', 'total de esta reserva', 'debe algo', 'al corriente'])) {
                return 'ctx:reserva_pagos';
            }
            if ($tiene(['cuando llega', 'cuando entra', 'cuando se va', 'cuando sale', 'hasta cuando se queda', 'que fechas', 'cuantas noches se queda', 'cuantos dias se queda', 'cuantas noches son', 'fecha de entrada', 'fecha de salida'])) {
                return 'ctx:reserva_fechas';
            }
            if ($tiene(['ya hizo check', 'ya llego', 'esta hospedad', 'como va esta reserva', 'estado de esta reserva', 'quien es el huesped', 'de quien es esta reserva', 'que habitacion tiene', 'que habitaciones tiene'])) {
                return 'ctx:reserva_general';
            }
        }

        if ($contexto['tipo'] === 'habitacion') {
            if ($tiene(['esta ocupada', 'esta libre', 'esta disponible', 'quien esta en esta habitacion', 'quien la ocupa', 'quien esta aqui', 'estado de esta habitacion', 'cuando se desocupa', 'cuando se libera', 'hasta cuando esta ocupada', 'quien llega a esta habitacion'])) {
                return 'ctx:habitacion';
            }
        }

        return null;
    }

    /** Valida el permiso del rol y despacha a la lectura de la entidad. */
    private function responderEntidad(int $hotelId, string $intent, array $contexto): array
    {
        if ($contexto['tipo'] === 'reservacion') {
            // Mismo permiso que la pantalla que muestra estos datos.
            if (function_exists('can') && !can('reservaciones.view')) {
                return ['texto' => 'Tu rol no tiene permiso para ver los datos de reservaciones. Pidele el acceso a tu gerente.', 'enlace' => null];
            }
            return $this->responderReservacionVisible($hotelId, (int) $contexto['id'], $intent);
        }

        if (function_exists('can') && !can('habitaciones.view')) {
            return ['texto' => 'Tu rol no tiene permiso para ver los datos de habitaciones. Pidele el acceso a tu gerente.', 'enlace' => null];
        }
        return $this->responderHabitacionVisible($hotelId, (int) $contexto['id']);
    }

    /** Lee la reservacion en pantalla (scope de hotel) y responde el intent. */
    private function responderReservacionVisible(int $hotelId, int $reservacionId, string $intent): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.id, r.estado, r.fecha_entrada, r.fecha_salida, r.precio_total, h.nombre_completo
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE r.id = ? AND r.hotel_id = ?
                 LIMIT 1"
            );
            $stmt->execute([$reservacionId, $hotelId]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Copiloto: error reservacion visible: ' . $e->getMessage());
            $r = null;
        }

        if (!$r) {
            return ['texto' => 'No encuentro esa reservacion en tu hotel. Recarga la pantalla e intenta de nuevo.', 'enlace' => null];
        }

        $huesped = trim((string) $r['nombre_completo']);
        $estados = ['pendiente' => 'pendiente de confirmar', 'confirmada' => 'confirmada', 'checked_in' => 'con el huesped dentro (check-in hecho)', 'checked_out' => 'ya con check-out', 'cancelada' => 'cancelada'];
        $estado = $estados[(string) $r['estado']] ?? (string) $r['estado'];

        if ($intent === 'ctx:reserva_pagos') {
            require_once __DIR__ . '/../models/Reservacion.php';
            $resumen = (new Reservacion())->resumenPagos($reservacionId, $hotelId);
            $totalNum = (float) $resumen['total'];
            $pagadoNum = (float) $resumen['pagado'];
            $saldoNum = (float) $resumen['saldo'];
            $total = number_format($totalNum, 2);
            $pagado = number_format($pagadoNum, 2);

            if ($totalNum <= 0) {
                $texto = "La reservacion de **{$huesped}** no tiene precio total registrado, asi que no hay saldo que cobrar.";
            } elseif ($saldoNum <= 0) {
                $texto = "La reservacion de **{$huesped}** esta **pagada por completo**: total \${$total}, pagado \${$pagado}. ✔";
            } elseif ($pagadoNum <= 0) {
                $texto = "La reservacion de **{$huesped}** no tiene ningun pago registrado (ni anticipo): **debe el total, \${$total}**.";
            } else {
                $texto = "La reservacion de **{$huesped}** tiene un total de \${$total}; lleva pagado \${$pagado} (anticipos y abonos) y **debe \$" . number_format($saldoNum, 2) . '**.';
            }
            return ['texto' => $texto, 'enlace' => null];
        }

        if ($intent === 'ctx:reserva_fechas') {
            $noches = max(0, (int) round((strtotime((string) $r['fecha_salida']) - strtotime((string) $r['fecha_entrada'])) / 86400));
            return [
                'texto' => "La reservacion de **{$huesped}** ({$estado}) va del **{$this->fechaCortaConAnio((string) $r['fecha_entrada'])}** al **{$this->fechaCortaConAnio((string) $r['fecha_salida'])}**: {$noches} noche(s).",
                'enlace' => null,
            ];
        }

        // ctx:reserva_general — estado + habitaciones asignadas.
        $numeros = $this->numerosHabitacionesDeReserva($hotelId, $reservacionId);

        $texto = "Esta reservacion es de **{$huesped}**, esta **{$estado}** y va del {$this->fechaCortaConAnio((string) $r['fecha_entrada'])} al {$this->fechaCortaConAnio((string) $r['fecha_salida'])}.";
        if (!empty($numeros)) {
            $texto .= ' Habitacion(es): **' . implode(', ', $numeros) . '**.';
        }
        return ['texto' => $texto, 'enlace' => null];
    }

    /** Lee la habitacion en pantalla (scope de hotel): estado, ocupante y proxima llegada. */
    private function responderHabitacionVisible(int $hotelId, int $habitacionId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT numero, estado, activa FROM habitaciones WHERE id = ? AND hotel_id = ? LIMIT 1");
            $stmt->execute([$habitacionId, $hotelId]);
            $hab = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Copiloto: error habitacion visible: ' . $e->getMessage());
            $hab = null;
        }

        if (!$hab) {
            return ['texto' => 'No encuentro esa habitacion en tu hotel. Recarga la pantalla e intenta de nuevo.', 'enlace' => null];
        }

        $estados = ['disponible' => 'disponible', 'ocupada' => 'ocupada', 'limpieza' => 'en limpieza', 'mantenimiento' => 'en mantenimiento'];
        $estado = $estados[(string) $hab['estado']] ?? (string) $hab['estado'];
        $texto = "La habitacion **{$hab['numero']}** esta **{$estado}**" . ((int) $hab['activa'] === 1 ? '' : ' (y marcada como inactiva)') . '.';

        try {
            // Ocupante actual: reservacion con check-in sobre esta habitacion.
            $stmt = $this->pdo->prepare(
                "SELECT h.nombre_completo, r.fecha_salida
                 FROM reservaciones r
                 INNER JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE r.hotel_id = ? AND rh.habitacion_id = ? AND r.estado = 'checked_in'
                 ORDER BY r.fecha_salida ASC LIMIT 1"
            );
            $stmt->execute([$hotelId, $habitacionId]);
            $ocupante = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($ocupante) {
                $texto .= " La ocupa **" . trim((string) $ocupante['nombre_completo']) . "** con salida el {$this->fechaCortaConAnio((string) $ocupante['fecha_salida'])}.";
            } else {
                $stmt = $this->pdo->prepare(
                    "SELECT h.nombre_completo, r.fecha_entrada
                     FROM reservaciones r
                     INNER JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                     INNER JOIN huespedes h ON h.id = r.huesped_id
                     WHERE r.hotel_id = ? AND rh.habitacion_id = ? AND r.estado IN ('confirmada', 'pendiente')
                       AND r.fecha_entrada >= CURDATE()
                     ORDER BY r.fecha_entrada ASC LIMIT 1"
                );
                $stmt->execute([$hotelId, $habitacionId]);
                $proxima = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                $texto .= $proxima
                    ? " La proxima llegada para esta habitacion es de **" . trim((string) $proxima['nombre_completo']) . "** el {$this->fechaCortaConAnio((string) $proxima['fecha_entrada'])}."
                    : ' No tiene llegadas proximas reservadas.';
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error ocupante habitacion: ' . $e->getMessage());
        }

        return ['texto' => $texto, 'enlace' => null];
    }

    /** fechaCorta() + el anio cuando no es el actual ("sabado 3 de enero de 2027"). */
    private function fechaCortaConAnio(string $ymd): string
    {
        $texto = $this->fechaCorta($ymd);
        $anio = date('Y', strtotime($ymd));
        return $anio === date('Y') ? $texto : $texto . ' de ' . $anio;
    }

    // ───────────────────────── Accion ejecutable: programar limpieza ─────────────────────────

    /**
     * Detecta "programa limpieza de la 204 hoy" y arma la PROPUESTA (no
     * ejecuta). Devuelve null si la frase no es una orden de limpieza.
     * La habitacion se busca contra el catalogo real del hotel (los numeros
     * pueden ser nombres); si no viene en la frase pero el usuario esta
     * parado sobre una habitacion (contexto de pantalla), se usa esa.
     */
    private function detectarAccionLimpieza(string $norm, int $hotelId, ?array $contexto = null): ?array
    {
        $verbos = ['programa limpieza', 'programar limpieza', 'programa la limpieza', 'programa una limpieza',
            'agenda limpieza', 'agendar limpieza', 'agenda la limpieza', 'agenda una limpieza',
            'manda limpiar', 'manda a limpiar', 'mandar limpiar', 'pon a limpiar', 'que limpien la', 'que limpien el'];
        $hayVerbo = false;
        foreach ($verbos as $v) {
            if (strpos($norm, $v) !== false) {
                $hayVerbo = true;
                break;
            }
        }
        if (!$hayVerbo) {
            return null;
        }

        // Mismo permiso que gatea el modulo de tareas operativas.
        if (function_exists('can') && !can('habitaciones.view')) {
            return ['intent' => 'accion:limpieza_perm', 'respuesta' => [
                'texto' => 'Tu rol no tiene permiso para programar limpiezas. Pidele el acceso a tu gerente.',
                'enlace' => null,
            ]];
        }

        // Fecha referida: hoy (default), manana o pasado manana.
        if (strpos($norm, 'pasado manana') !== false) {
            $fecha = date('Y-m-d', strtotime('+2 days'));
            $fechaTexto = 'pasado manana (' . date('d/m', strtotime($fecha)) . ')';
        } elseif (strpos($norm, 'manana') !== false) {
            $fecha = date('Y-m-d', strtotime('+1 day'));
            $fechaTexto = 'manana (' . date('d/m', strtotime($fecha)) . ')';
        } else {
            $fecha = date('Y-m-d');
            $fechaTexto = 'hoy (' . date('d/m') . ')';
        }

        $hab = $this->buscarHabitacionEnTexto($norm, $hotelId);
        if ($hab === null && $contexto !== null && ($contexto['tipo'] ?? '') === 'habitacion') {
            $hab = $this->habitacionPorId($hotelId, (int) $contexto['id']);
        }
        if ($hab === null) {
            return ['intent' => 'accion:limpieza_sin_hab', 'respuesta' => [
                'texto' => "No identifique la habitacion. Dimelo asi: \"programa limpieza de la 204 hoy\" (con el numero o nombre tal como aparece en **Habitaciones**).",
                'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones'],
            ]];
        }

        return ['intent' => 'accion:limpieza', 'respuesta' => [
            'texto' => "Puedo programar la limpieza de la habitacion **{$hab['numero']}** para {$fechaTexto}. "
                . 'La tarea queda pendiente de asignar personal (eso se hace en el tablero de limpieza). Confirmalo y la creo.',
            'enlace' => null,
            'accion' => [
                'tipo' => 'programar_limpieza',
                'habitacion_id' => (int) $hab['id'],
                'habitacion' => (string) $hab['numero'],
                'fecha' => $fecha,
                'confirm_titulo' => '¿Programar limpieza?',
                'confirm_msg' => "Habitacion {$hab['numero']}, {$fechaTexto}. Se creara la tarea de limpieza pendiente de asignar personal.",
                'confirm_ok' => 'Programar',
            ],
        ]];
    }

    /**
     * Detecta "asigna a Maria la limpieza de la 204" y arma la PROPUESTA de
     * asignacion (no ejecuta). Cierra el ciclo de la limpieza programada sin
     * personal: la tarea pasa a 'asignada' y aparece en la agenda de la
     * persona. Devuelve null si la frase no es una asignacion de limpieza.
     */
    private function detectarAccionAsignar(string $norm, int $hotelId, ?array $contexto = null): ?array
    {
        if (!preg_match('/\b(asigna|asignale|asignarle|asignar|encarga|encargale)\b/', $norm) || strpos($norm, 'limpi') === false) {
            return null;
        }

        // Mismo permiso que el resto de las acciones de limpieza.
        if (function_exists('can') && !can('habitaciones.view')) {
            return ['intent' => 'accion:asignar_perm', 'respuesta' => [
                'texto' => 'Tu rol no tiene permiso para asignar limpiezas. Pidele el acceso a tu gerente.',
                'enlace' => null,
            ]];
        }

        $hab = $this->buscarHabitacionEnTexto($norm, $hotelId);
        if ($hab === null && $contexto !== null && ($contexto['tipo'] ?? '') === 'habitacion') {
            $hab = $this->habitacionPorId($hotelId, (int) $contexto['id']);
        }
        if ($hab === null) {
            return ['intent' => 'accion:asignar_sin_hab', 'respuesta' => [
                'texto' => "No identifique la habitacion. Dimelo asi: \"asigna a Maria la limpieza de la 204\".",
                'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones'],
            ]];
        }

        $trab = $this->buscarTrabajadorEnTexto($norm, $hotelId);
        if ($trab !== null && isset($trab['ambiguos'])) {
            return ['intent' => 'accion:asignar_ambiguo', 'respuesta' => [
                'texto' => 'Hay varias personas que casan con ese nombre: **' . implode('**, **', $trab['ambiguos']) . '**. Dimelo con el nombre completo.',
                'enlace' => null,
            ]];
        }
        if ($trab === null) {
            return ['intent' => 'accion:asignar_sin_quien', 'respuesta' => [
                'texto' => 'No identifique a quien asignarle la limpieza. Dimelo con su nombre tal como aparece en **Personal**: "asigna a Maria la limpieza de la ' . $hab['numero'] . '".',
                'enlace' => $this->tieneModulo('personal', $hotelId) ? ['url' => 'trabajadores', 'texto' => 'Ver personal'] : null,
            ]];
        }

        // Si ya hay tarea de limpieza activa se respeta su fecha (si sigue
        // vigente); si no, la asignacion es para hoy.
        $fecha = date('Y-m-d');
        try {
            require_once __DIR__ . '/../models/TareaOperativa.php';
            $tarea = (new TareaOperativa())->buscarTareaActivaLimpiezaPorHabitacionHotel($hotelId, (int) $hab['id']);
            if ($tarea && !empty($tarea['fecha_programada'])) {
                $fechaTarea = date('Y-m-d', strtotime((string) $tarea['fecha_programada']));
                if ($fechaTarea >= date('Y-m-d')) {
                    $fecha = $fechaTarea;
                }
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error tarea activa para asignar: ' . $e->getMessage());
        }
        $fechaTexto = $fecha === date('Y-m-d') ? 'hoy (' . date('d/m') . ')' : 'el ' . date('d/m', strtotime($fecha));

        return ['intent' => 'accion:asignar', 'respuesta' => [
            'texto' => "Puedo dejar a **{$trab['nombre']}** a cargo de la limpieza de la habitacion **{$hab['numero']}** para {$fechaTexto}. Confirmalo y queda en su agenda.",
            'enlace' => null,
            'accion' => [
                'tipo' => 'asignar_limpieza',
                'habitacion_id' => (int) $hab['id'],
                'habitacion' => (string) $hab['numero'],
                'trabajador_id' => (int) $trab['id'],
                'trabajador' => (string) $trab['nombre'],
                'fecha' => $fecha,
                'confirm_titulo' => '¿Asignar la limpieza?',
                'confirm_msg' => "{$trab['nombre']} — habitacion {$hab['numero']}, {$fechaTexto}. La tarea queda asignada y visible en su agenda.",
                'confirm_ok' => 'Asignar',
            ],
        ]];
    }

    /**
     * Detecta "manda a mantenimiento la 204 por fuga de agua" y arma la
     * PROPUESTA (no ejecuta). El motivo es obligatorio (regla del dominio):
     * sin "por ..." se guia al usuario. Tipo correctivo y prioridad media por
     * default (lo tipico de un reporte hablado); lo fino se ajusta en la
     * ficha. Si la habitacion tiene reservaciones proximas, la ejecucion NO
     * procede desde el chat (eso se confirma en la pantalla, como siempre).
     */
    private function detectarAccionMantenimiento(string $norm, int $hotelId, ?array $contexto = null): ?array
    {
        // "bloquea la 204 por pintura" es la misma accion con otro verbo: en
        // este dominio bloquear una habitacion = ponerla en mantenimiento.
        $esBloqueo = (bool) preg_match('/\b(bloquea|bloquear|bloqueame|bloqueen)\b/', $norm);
        $esMantenimiento = preg_match('/\b(manda|mandar|pon|poner|mete|meter|marca|marcar)\b/', $norm) && preg_match('/\b(a|en) mantenimiento\b/', $norm);
        if (!$esBloqueo && !$esMantenimiento) {
            return null;
        }

        if (function_exists('can') && !can('habitaciones.mantenimiento')) {
            return ['intent' => 'accion:mant_perm', 'respuesta' => [
                'texto' => 'Tu rol no tiene permiso para marcar mantenimientos. Pidele el acceso a tu gerente.',
                'enlace' => null,
            ]];
        }

        // Motivo obligatorio: lo que venga despues del ultimo " por ".
        $motivo = '';
        $posPor = strrpos($norm, ' por ');
        $parteHabitacion = $norm;
        if ($posPor !== false) {
            $motivo = trim((string) preg_replace('/[¿?¡!]/u', '', substr($norm, $posPor + 5)));
            $parteHabitacion = substr($norm, 0, $posPor);
        }

        $hab = $this->buscarHabitacionEnTexto($parteHabitacion, $hotelId);
        if ($hab === null && $contexto !== null && ($contexto['tipo'] ?? '') === 'habitacion') {
            $hab = $this->habitacionPorId($hotelId, (int) $contexto['id']);
        }
        if ($hab === null) {
            $ejemplo = $esBloqueo ? 'bloquea la 204 por pintura' : 'manda a mantenimiento la 204 por fuga de agua';
            return ['intent' => 'accion:mant_sin_hab', 'respuesta' => [
                'texto' => "No identifique la habitacion. Dimelo asi: \"{$ejemplo}\".",
                'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones'],
            ]];
        }
        if (mb_strlen($motivo) < 5) {
            $ejemplo = $esBloqueo ? "bloquea la {$hab['numero']} por pintura de paredes" : "manda a mantenimiento la {$hab['numero']} por fuga de agua en el bano";
            return ['intent' => 'accion:mant_sin_motivo', 'respuesta' => [
                'texto' => "El motivo es obligatorio para " . ($esBloqueo ? 'bloquear una habitacion' : 'un mantenimiento') . ". Dimelo asi: \"{$ejemplo}\".",
                'enlace' => null,
            ]];
        }

        $verboTexto = $esBloqueo
            ? "Puedo **bloquear** la habitacion **{$hab['numero']}** (queda en mantenimiento correctivo, prioridad media) por: {$motivo}. "
                . 'Para volver a rentarla dime "desbloquea la ' . $hab['numero'] . '". '
            : "Puedo poner la habitacion **{$hab['numero']}** en **mantenimiento correctivo** (prioridad media) por: {$motivo}. ";

        return ['intent' => $esBloqueo ? 'accion:bloqueo' : 'accion:mant', 'respuesta' => [
            'texto' => $verboTexto . 'Si tiene reservaciones proximas te avisare sin ejecutar. Confirmalo y queda registrado.',
            'enlace' => null,
            'accion' => [
                'tipo' => 'iniciar_mantenimiento',
                'habitacion_id' => (int) $hab['id'],
                'habitacion' => (string) $hab['numero'],
                'motivo' => mb_substr($motivo, 0, 300),
                'fecha' => date('Y-m-d'),
                'confirm_titulo' => $esBloqueo ? '¿Bloquear la habitacion?' : '¿Iniciar mantenimiento?',
                'confirm_msg' => "Habitacion {$hab['numero']} pasa a mantenimiento correctivo (prioridad media). Motivo: {$motivo}.",
                'confirm_ok' => $esBloqueo ? 'Bloquear' : 'Iniciar',
            ],
        ]];
    }

    /**
     * Detecta "desbloquea la 204" / "ya quedo el mantenimiento de la 204" y
     * arma la PROPUESTA de liberar la habitacion (finalizar el mantenimiento
     * en proceso). Devuelve null si la frase no es una liberacion.
     */
    private function detectarAccionFinalizarMantenimiento(string $norm, int $hotelId, ?array $contexto = null): ?array
    {
        $esDesbloqueo = (bool) preg_match('/\b(desbloquea|desbloquear|libera|liberar)\b/', $norm);
        $esFinalizar = preg_match('/\b(finaliza|finalizar|termina|terminar|saca|sacar|quita|quitar|cierra|cerrar)\b/', $norm)
            && strpos($norm, 'mantenimiento') !== false;
        if (!$esDesbloqueo && !$esFinalizar) {
            return null;
        }

        if (function_exists('can') && !can('habitaciones.mantenimiento')) {
            return ['intent' => 'accion:desbloqueo_perm', 'respuesta' => [
                'texto' => 'Tu rol no tiene permiso para gestionar mantenimientos. Pidele el acceso a tu gerente.',
                'enlace' => null,
            ]];
        }

        $hab = $this->buscarHabitacionEnTexto($norm, $hotelId);
        if ($hab === null && $contexto !== null && ($contexto['tipo'] ?? '') === 'habitacion') {
            $hab = $this->habitacionPorId($hotelId, (int) $contexto['id']);
        }
        if ($hab === null) {
            return ['intent' => 'accion:desbloqueo_sin_hab', 'respuesta' => [
                'texto' => "No identifique la habitacion. Dimelo asi: \"desbloquea la 204\".",
                'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones'],
            ]];
        }

        // Solo tiene sentido sobre una habitacion en mantenimiento; avisar
        // aqui evita una confirmacion que va a fallar.
        $estado = $this->estadoHabitacion($hotelId, (int) $hab['id']);
        if ($estado !== 'mantenimiento') {
            return ['intent' => 'accion:desbloqueo_no_aplica', 'respuesta' => [
                'texto' => "La habitacion **{$hab['numero']}** no esta bloqueada ni en mantenimiento (su estado es \"{$estado}\"), asi que no hay nada que liberar.",
                'enlace' => ['url' => 'habitaciones/' . $hab['id'], 'texto' => 'Ver la ficha'],
            ]];
        }

        return ['intent' => 'accion:desbloqueo', 'respuesta' => [
            'texto' => "Puedo liberar la habitacion **{$hab['numero']}**: su mantenimiento se cierra y vuelve a quedar **disponible** para rentar. Confirmalo y la libero.",
            'enlace' => null,
            'accion' => [
                'tipo' => 'finalizar_mantenimiento',
                'habitacion_id' => (int) $hab['id'],
                'habitacion' => (string) $hab['numero'],
                'fecha' => date('Y-m-d'),
                'confirm_titulo' => '¿Liberar la habitacion?',
                'confirm_msg' => "La habitacion {$hab['numero']} sale de mantenimiento y queda disponible.",
                'confirm_ok' => 'Liberar',
            ],
        ]];
    }

    /**
     * Detecta "registra un gasto de 450 de plomeria" y arma la PROPUESTA del
     * gasto en caja (no ejecuta). Unica accion de dinero del copiloto y solo
     * en su version mas segura: gasto en EFECTIVO, con caja abierta, permiso
     * caja.movimientos y categoria del catalogo del hotel; el registro final
     * pasa por MovimientoCaja::registrarMovimiento (mismo motor y candados
     * que la pantalla de Caja). Cobros/ingresos jamas se registran por chat.
     */
    private function detectarAccionGasto(string $norm, int $hotelId): ?array
    {
        if (!preg_match('/\b(registra|registrar|registrame|anota|anotar|anotame|apunta|apuntar|apuntame|mete|meter)\b/', $norm)
            || !preg_match('/\bgastos?\b/', $norm)) {
            return null;
        }

        // Mismo permiso que CajaController::registrarGastoAction.
        if (function_exists('can') && !can('caja.movimientos')) {
            return ['intent' => 'accion:gasto_perm', 'respuesta' => [
                'texto' => 'Tu rol no tiene permiso para registrar gastos en caja. Pidele el acceso a tu gerente.',
                'enlace' => null,
            ]];
        }

        // Sin caja abierta el movimiento no tiene donde caer.
        if (empty($this->caja($hotelId)['abierto'])) {
            return ['intent' => 'accion:gasto_caja_cerrada', 'respuesta' => [
                'texto' => 'La caja esta cerrada y un gasto necesita una caja abierta. Abre la caja y vuelve a decirmelo.',
                'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
            ]];
        }

        $parse = self::parsearGasto($norm);
        if ($parse['metodo'] !== 'efectivo') {
            return ['intent' => 'accion:gasto_metodo', 'respuesta' => [
                'texto' => 'Desde el chat solo registro gastos en **efectivo** (tarjeta y transferencia piden referencia). Ese registralo en la pantalla de Caja.',
                'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
            ]];
        }
        if ($parse['monto'] === null || $parse['monto'] <= 0) {
            return ['intent' => 'accion:gasto_sin_monto', 'respuesta' => [
                'texto' => "No identifique el monto. Dimelo asi: \"registra un gasto de 450 de plomeria\".",
                'enlace' => null,
            ]];
        }
        if ($parse['concepto'] === null) {
            return ['intent' => 'accion:gasto_sin_concepto', 'respuesta' => [
                'texto' => "Me falta el concepto (que se compro o pago). Dimelo asi: \"registra un gasto de " . number_format($parse['monto'], 2) . " de plomeria\".",
                'enlace' => null,
            ]];
        }

        $cat = $this->buscarCategoriaGastoEnTexto($norm, $hotelId);
        if ($cat !== null && isset($cat['ambiguos'])) {
            return ['intent' => 'accion:gasto_cat_ambigua', 'respuesta' => [
                'texto' => 'Ese gasto casa con varias categorias: **' . implode('**, **', $cat['ambiguos']) . '**. Repitemelo mencionando una, por ejemplo: "registra un gasto de '
                    . number_format($parse['monto'], 2) . ' de ' . $parse['concepto'] . ' en ' . $cat['ambiguos'][0] . '".',
                'enlace' => null,
            ]];
        }
        if ($cat === null) {
            $nombres = $this->categoriasGasto($hotelId);
            if (empty($nombres)) {
                return ['intent' => 'accion:gasto_sin_catalogo', 'respuesta' => [
                    'texto' => 'Este hotel aun no tiene categorias de gasto en Caja. Crea al menos una en **Caja → Categorias** y vuelve a decirmelo.',
                    'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
                ]];
            }
            $lista = array_column(array_slice($nombres, 0, 6), 'nombre');
            return ['intent' => 'accion:gasto_sin_categoria', 'respuesta' => [
                'texto' => '¿En que categoria lo anoto? Tengo: **' . implode('**, **', $lista) . '**. Repitemelo asi: "registra un gasto de '
                    . number_format($parse['monto'], 2) . ' de ' . $parse['concepto'] . ' en ' . $lista[0] . '".',
                'enlace' => null,
            ]];
        }

        $montoTexto = '$' . number_format($parse['monto'], 2);
        $descripcion = mb_substr(ucfirst($parse['concepto']), 0, 200);

        return ['intent' => 'accion:gasto', 'respuesta' => [
            'texto' => "Puedo registrar un **gasto de {$montoTexto}** en efectivo — {$descripcion} (categoria **{$cat['nombre']}**). "
                . 'Cae en la caja abierta de hoy y queda a tu nombre. Confirmalo y lo anoto.',
            'enlace' => null,
            'accion' => [
                'tipo' => 'registrar_gasto',
                'monto' => round($parse['monto'], 2),
                'categoria_id' => (int) $cat['id'],
                'categoria' => (string) $cat['nombre'],
                'descripcion' => $descripcion,
                'fecha' => date('Y-m-d'),
                'confirm_titulo' => '¿Registrar el gasto?',
                'confirm_msg' => "{$montoTexto} en efectivo — {$descripcion} (categoria {$cat['nombre']}). Se registra en la caja abierta.",
                'confirm_ok' => 'Registrar',
            ],
        ]];
    }

    /**
     * Parser puro (sin BD) del gasto dictado. Espera texto ya normalizado
     * (minusculas, sin acentos). Devuelve ['monto' => ?float, 'concepto' =>
     * ?string, 'metodo' => 'efectivo'|'tarjeta'|'transferencia'].
     *
     * Monto: si hay varios numeros gana el que trae "$" y, si no, el mayor
     * (separa el dinero de las cantidades: "2 focos por 100" -> 100).
     * Concepto: lo que sigue al monto ("...450 de plomeria") o lo que esta
     * entre "gasto de|por" y el monto ("gasto de plomeria de 450").
     */
    public static function parsearGasto(string $norm): array
    {
        $metodo = 'efectivo';
        if (preg_match('/\btarjeta\b/', $norm)) {
            $metodo = 'tarjeta';
        } elseif (strpos($norm, 'transferencia') !== false) {
            $metodo = 'transferencia';
        }

        // La mencion del metodo no debe ensuciar el concepto.
        $frase = (string) preg_replace('/\b(en|con|de|por)\s+(efectivo|tarjeta|transferencia)\b/', ' ', $norm);

        $reNumero = '/(\$\s*)?(\d{1,3}(?:,\d{3})+(?:\.\d{1,2})?|\d+(?:\.\d{1,2})?)/';
        if (!preg_match_all($reNumero, $frase, $todos, PREG_OFFSET_CAPTURE)) {
            return ['monto' => null, 'concepto' => null, 'metodo' => $metodo];
        }

        $elegido = 0;
        if (count($todos[2]) > 1) {
            $mejorValor = -1.0;
            foreach ($todos[2] as $i => $m) {
                $valor = (float) str_replace(',', '', $m[0]);
                $conSigno = ($todos[1][$i][0] ?? '') !== '';
                if ($conSigno) {
                    $elegido = $i;
                    break;
                }
                if ($valor > $mejorValor) {
                    $mejorValor = $valor;
                    $elegido = $i;
                }
            }
        }

        $token = $todos[2][$elegido];
        $monto = (float) str_replace(',', '', $token[0]);
        $inicio = (int) $todos[0][$elegido][1];
        $fin = (int) $token[1] + strlen($token[0]);

        $despues = trim(substr($frase, $fin));
        $despues = trim((string) preg_replace('/^(?:(?:de|del|por|en|para)\s+)+/', '', $despues));
        $concepto = trim((string) preg_replace('/[¿?¡!.]+$/u', '', $despues));

        if (mb_strlen($concepto) < 3) {
            $antes = substr($frase, 0, $inicio);
            if (preg_match('/gastos?\s+(?:de|por|en)\s+(.+)$/', $antes, $g)) {
                $concepto = trim((string) preg_replace('/\s+(?:de|del|por|en|para)\s*$/', '', trim($g[1])));
            }
        }
        if (mb_strlen($concepto) < 3) {
            $concepto = null;
        }

        return ['monto' => $monto > 0 ? $monto : null, 'concepto' => $concepto, 'metodo' => $metodo];
    }

    /** Categorias de GASTO activas del hotel: [['id','nombre'], ...]. */
    private function categoriasGasto(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, nombre FROM categorias_movimientos
                 WHERE hotel_id = ? AND tipo = 'gasto' AND activa = 1
                 ORDER BY orden, nombre LIMIT 60"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto: error catalogo categorias gasto: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca una categoria de gasto DEL hotel mencionada en la frase (nombre
     * completo primero, luego tokens de palabra completa >= 4 letras).
     * Devuelve ['id','nombre'], ['ambiguos' => nombres] o null.
     */
    private function buscarCategoriaGastoEnTexto(string $norm, int $hotelId): ?array
    {
        $candidatos = [];
        foreach ($this->categoriasGasto($hotelId) as $f) {
            $nombre = trim((string) $f['nombre']);
            $nc = $this->normalizar($nombre);
            if ($nc === '') {
                continue;
            }
            if (strpos($norm, $nc) !== false) {
                return ['id' => (int) $f['id'], 'nombre' => $nombre];
            }
            foreach (explode(' ', $nc) as $token) {
                if (mb_strlen($token) < 4) {
                    continue;
                }
                if (preg_match('/(^|[^a-z0-9])' . preg_quote($token, '/') . '($|[^a-z0-9])/', $norm)) {
                    $candidatos[(int) $f['id']] = $nombre;
                    break;
                }
            }
        }

        if (count($candidatos) === 1) {
            return ['id' => (int) array_key_first($candidatos), 'nombre' => (string) reset($candidatos)];
        }
        if (count($candidatos) > 1) {
            return ['ambiguos' => array_slice(array_values($candidatos), 0, 3)];
        }

        return null;
    }

    /**
     * Detecta "pagale 500 al proveedor Garcia" y arma la PROPUESTA del pago
     * de una cuenta por pagar (no ejecuta). Segunda accion de dinero del
     * copiloto y tambien solo egreso: el registro pasa por
     * CuentaPorPagarPagoService::registrarPago (mismo motor y candados que
     * la pantalla: corte abierto, proveedor del hotel, compra recibida,
     * monto <= saldo, todo FOR UPDATE). Sin monto dictado se propone saldar
     * la cuenta. Se paga la cuenta MAS ANTIGUA pendiente del proveedor.
     */
    private function detectarAccionPagoProveedor(string $norm, int $hotelId): ?array
    {
        if (!preg_match('/\b(paga|pagale|pagarle|pagar|abona|abonale|abonarle|abonar)\b/', $norm)) {
            return null;
        }

        $prov = $this->buscarProveedorEnTexto($norm, $hotelId);
        $mencionaProveedor = strpos($norm, 'proveedor') !== false;
        // Sin proveedor identificado NI la palabra "proveedor", la frase no es
        // nuestra ("paga la nomina" debe caer a sus propias respuestas).
        if ($prov === null && !$mencionaProveedor) {
            return null;
        }

        // Mismo gate que la pantalla de Cuentas por pagar.
        if (!$this->tieneModulo('compras', $hotelId)) {
            return ['intent' => 'accion:pago_sin_bloque', 'respuesta' => [
                'texto' => 'Los pagos a proveedores son del bloque **Compras**, que este hotel no tiene activo. Se contrata desde Configuracion.',
                'enlace' => ['url' => 'configuracion', 'texto' => 'Ver bloques'],
            ]];
        }
        if (function_exists('can') && !can('cuentas_por_pagar.pagar')) {
            return ['intent' => 'accion:pago_perm', 'respuesta' => [
                'texto' => 'Tu rol no tiene permiso para pagar a proveedores. Pidele el acceso a tu gerente.',
                'enlace' => null,
            ]];
        }

        if ($prov !== null && isset($prov['ambiguos'])) {
            return ['intent' => 'accion:pago_ambiguo', 'respuesta' => [
                'texto' => 'Hay varios proveedores que casan con ese nombre: **' . implode('**, **', $prov['ambiguos']) . '**. Dimelo con el nombre completo.',
                'enlace' => null,
            ]];
        }

        // Un nombre que tambien es de un trabajador y sin la palabra
        // "proveedor" huele a nomina: eso se paga en su pantalla, no aqui.
        if ($prov !== null && !$mencionaProveedor) {
            $trab = $this->buscarTrabajadorEnTexto($norm, $hotelId);
            if ($trab !== null && !isset($trab['ambiguos'])) {
                return ['intent' => 'accion:pago_quiza_nomina', 'respuesta' => [
                    'texto' => "**{$prov['nombre']}** tambien casa con alguien de tu personal. Si es pago a proveedor dime \"pagale al proveedor {$prov['nombre']}\"; los pagos de nomina se hacen desde **Personal**.",
                    'enlace' => $this->tieneModulo('personal', $hotelId) ? ['url' => 'trabajadores', 'texto' => 'Ver personal'] : null,
                ]];
            }
        }

        if ($prov === null) {
            $conDeuda = $this->proveedoresConDeuda($hotelId);
            if (empty($conDeuda)) {
                return ['intent' => 'accion:pago_sin_deuda', 'respuesta' => [
                    'texto' => 'No tienes cuentas por pagar pendientes con ningun proveedor. ✔',
                    'enlace' => ['url' => 'cuentas-por-pagar', 'texto' => 'Ver cuentas por pagar'],
                ]];
            }
            $lista = array_map(function ($p) {
                return $p['nombre'] . ' ($' . number_format((float) $p['saldo'], 2) . ')';
            }, $conDeuda);
            return ['intent' => 'accion:pago_sin_proveedor', 'respuesta' => [
                'texto' => '¿A que proveedor? Tienes saldo pendiente con: **' . implode('**, **', $lista) . '**. Dimelo asi: "pagale 500 al proveedor ' . $conDeuda[0]['nombre'] . '".',
                'enlace' => ['url' => 'cuentas-por-pagar', 'texto' => 'Ver cuentas por pagar'],
            ]];
        }

        $cuenta = $this->cuentaPendienteProveedor($hotelId, (int) $prov['id']);
        if ($cuenta === null) {
            return ['intent' => 'accion:pago_sin_deuda', 'respuesta' => [
                'texto' => "No le debes nada a **{$prov['nombre']}**; no tiene cuentas por pagar pendientes. ✔",
                'enlace' => ['url' => 'cuentas-por-pagar', 'texto' => 'Ver cuentas por pagar'],
            ]];
        }

        // Precondiciones del motor (corte abierto, compra recibida...) se
        // avisan al proponer, con el motivo humano del propio servicio.
        require_once __DIR__ . '/CuentaPorPagarPagoService.php';
        try {
            $eval = (new CuentaPorPagarPagoService($this->db))->evaluarPago($hotelId, (int) $cuenta['id']);
        } catch (Throwable $e) {
            error_log('Copiloto: error al evaluar pago CxP: ' . $e->getMessage());
            $eval = ['elegible' => false, 'motivo_bloqueo' => 'No se pudo evaluar la cuenta. Intenta de nuevo.'];
        }
        if (empty($eval['elegible'])) {
            return ['intent' => 'accion:pago_bloqueado', 'respuesta' => [
                'texto' => 'No puedo proponer ese pago: ' . (string) ($eval['motivo_bloqueo'] ?? 'la cuenta no es elegible.'),
                'enlace' => ['url' => 'cuentas-por-pagar', 'texto' => 'Ver cuentas por pagar'],
            ]];
        }

        $saldo = (float) $cuenta['saldo'];
        $p = self::parsearPagoProveedor($norm);
        $monto = $p['monto'] !== null ? round($p['monto'], 2) : $saldo;
        if ($monto > $saldo + 0.004) {
            return ['intent' => 'accion:pago_excede', 'respuesta' => [
                'texto' => 'El saldo con **' . $prov['nombre'] . '** en su cuenta mas antigua es **$' . number_format($saldo, 2)
                    . '** y no puedo pagar de mas. Dime un monto hasta esa cantidad, o solo "pagale al proveedor ' . $prov['nombre'] . '" para saldarla.',
                'enlace' => ['url' => 'cuentas-por-pagar', 'texto' => 'Ver cuentas por pagar'],
            ]];
        }

        $montoTexto = '$' . number_format($monto, 2);
        $restante = round($saldo - $monto, 2);
        $desenlace = $restante <= 0.004
            ? 'la cuenta queda **pagada**'
            : 'la cuenta queda en **$' . number_format($restante, 2) . '** (parcial)';
        $folio = trim((string) ($cuenta['folio'] ?? ''));
        $refCuenta = $folio !== '' ? "cuenta {$folio}" : 'cuenta #' . $cuenta['id'];
        $extra = ((int) $cuenta['n_pendientes'] > 1)
            ? ' Ojo: tiene ' . (int) $cuenta['n_pendientes'] . ' cuentas pendientes por $' . number_format((float) $cuenta['saldo_total'], 2) . ' en total; esta es la mas antigua.'
            : '';

        return ['intent' => 'accion:pago', 'respuesta' => [
            'texto' => "Puedo pagarle **{$montoTexto}** en {$p['metodo']} a **{$prov['nombre']}** ({$refCuenta}, saldo \$" . number_format($saldo, 2) . "): {$desenlace}. "
                . "El egreso sale de la caja abierta.{$extra} Confirmalo y lo registro.",
            'enlace' => null,
            'accion' => [
                'tipo' => 'pagar_proveedor',
                'cuenta_id' => (int) $cuenta['id'],
                'proveedor' => (string) $prov['nombre'],
                'monto' => $monto,
                'metodo' => $p['metodo'],
                'fecha' => date('Y-m-d'),
                'confirm_titulo' => '¿Pagar al proveedor?',
                'confirm_msg' => "{$montoTexto} en {$p['metodo']} a {$prov['nombre']} ({$refCuenta}). Sale de la caja abierta y {$desenlace}.",
                'confirm_ok' => 'Pagar',
            ],
        ]];
    }

    /**
     * Parser puro (sin BD) del pago dictado. Espera texto normalizado.
     * Devuelve ['monto' => ?float (null = saldar la cuenta), 'metodo' =>
     * 'efectivo'|'tarjeta'|'transferencia']. Mismo criterio de monto que el
     * gasto: con varios numeros gana el que trae $ y si no el mayor.
     */
    public static function parsearPagoProveedor(string $norm): array
    {
        $metodo = 'efectivo';
        if (preg_match('/\btarjeta\b/', $norm)) {
            $metodo = 'tarjeta';
        } elseif (strpos($norm, 'transferencia') !== false) {
            $metodo = 'transferencia';
        }

        $monto = null;
        if (preg_match_all('/(\$\s*)?(\d{1,3}(?:,\d{3})+(?:\.\d{1,2})?|\d+(?:\.\d{1,2})?)/', $norm, $todos)) {
            $elegido = 0;
            if (count($todos[2]) > 1) {
                $mejorValor = -1.0;
                foreach ($todos[2] as $i => $m) {
                    if (($todos[1][$i] ?? '') !== '') {
                        $elegido = $i;
                        $mejorValor = null;
                        break;
                    }
                    $valor = (float) str_replace(',', '', $m);
                    if ($valor > $mejorValor) {
                        $mejorValor = $valor;
                        $elegido = $i;
                    }
                }
            }
            $monto = (float) str_replace(',', '', $todos[2][$elegido]);
            if ($monto <= 0) {
                $monto = null;
            }
        }

        return ['monto' => $monto, 'metodo' => $metodo];
    }

    /**
     * Busca un proveedor ACTIVO del hotel en la frase (nombre completo
     * primero, luego tokens >= 3 letras). Devuelve ['id','nombre'],
     * ['ambiguos' => nombres] o null.
     */
    private function buscarProveedorEnTexto(string $norm, int $hotelId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, nombre FROM proveedores WHERE hotel_id = ? AND activo = 1 LIMIT 300");
            $stmt->execute([$hotelId]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto: error catalogo proveedores: ' . $e->getMessage());
            return null;
        }

        $candidatos = [];
        foreach ($filas as $f) {
            $nombre = trim((string) $f['nombre']);
            $nc = $this->normalizar($nombre);
            if ($nc === '') {
                continue;
            }
            if (strpos($norm, $nc) !== false) {
                return ['id' => (int) $f['id'], 'nombre' => $nombre];
            }
            foreach (explode(' ', $nc) as $token) {
                if (mb_strlen($token) < 3 || in_array($token, ['del', 'los', 'las'], true)) {
                    continue;
                }
                if (preg_match('/(^|[^a-z0-9])' . preg_quote($token, '/') . '($|[^a-z0-9])/', $norm)) {
                    $candidatos[(int) $f['id']] = $nombre;
                    break;
                }
            }
        }

        if (count($candidatos) === 1) {
            return ['id' => (int) array_key_first($candidatos), 'nombre' => (string) reset($candidatos)];
        }
        if (count($candidatos) > 1) {
            return ['ambiguos' => array_slice(array_values($candidatos), 0, 3)];
        }

        return null;
    }

    /** Proveedores con saldo pendiente: [['nombre','saldo'], ...] (top 6). */
    private function proveedoresConDeuda(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT p.nombre, SUM(cxp.saldo) saldo
                 FROM cuentas_por_pagar cxp
                 INNER JOIN proveedores p ON p.id = cxp.proveedor_id AND p.hotel_id = cxp.hotel_id
                 WHERE cxp.hotel_id = ? AND cxp.estado IN ('pendiente', 'parcial', 'vencida') AND cxp.saldo > 0
                 GROUP BY p.id, p.nombre
                 ORDER BY saldo DESC LIMIT 6"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto: error proveedores con deuda: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta pendiente MAS ANTIGUA del proveedor (vence primero gana), con el
     * total de pendientes del mismo proveedor para dar contexto. Null si no
     * debe nada.
     */
    private function cuentaPendienteProveedor(int $hotelId, int $proveedorId): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT cxp.id, cxp.folio, cxp.saldo,
                        (SELECT COUNT(*) FROM cuentas_por_pagar c2
                          WHERE c2.hotel_id = cxp.hotel_id AND c2.proveedor_id = cxp.proveedor_id
                            AND c2.estado IN ('pendiente', 'parcial', 'vencida') AND c2.saldo > 0) n_pendientes,
                        (SELECT COALESCE(SUM(c3.saldo), 0) FROM cuentas_por_pagar c3
                          WHERE c3.hotel_id = cxp.hotel_id AND c3.proveedor_id = cxp.proveedor_id
                            AND c3.estado IN ('pendiente', 'parcial', 'vencida') AND c3.saldo > 0) saldo_total
                 FROM cuentas_por_pagar cxp
                 WHERE cxp.hotel_id = ? AND cxp.proveedor_id = ?
                   AND cxp.estado IN ('pendiente', 'parcial', 'vencida') AND cxp.saldo > 0
                 ORDER BY COALESCE(cxp.fecha_vencimiento, '9999-12-31') ASC, cxp.fecha_emision ASC, cxp.id ASC
                 LIMIT 1"
            );
            $stmt->execute([$hotelId, $proveedorId]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC);
            return $f ?: null;
        } catch (Throwable $e) {
            error_log('Copiloto: error cuenta pendiente proveedor: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Detecta "crea un cupon de 10% para agosto" y arma la PROPUESTA del
     * cupon del motor (no ejecuta). Mismo gate que la pantalla de Cupones:
     * bloques motor_reservas + promociones. El registro final pasa por
     * MotorCuponService::crear (mismas validaciones de codigo, rango y
     * fechas). Si no dictan codigo se genera uno legible y disponible.
     */
    private function detectarAccionCupon(string $norm, int $hotelId): ?array
    {
        if (!preg_match('/\b(crea|crear|creame|haz|hazme|genera|generame|arma|armame|lanza|lanzar|lanzame)\b/', $norm)
            || !preg_match('/\bcupon(es)?\b/', $norm)) {
            return null;
        }

        // Mismo gate que MotorReservasController::cuponesAction.
        if (!$this->tieneModulo('motor_reservas', $hotelId) || !$this->tieneModulo('promociones', $hotelId)) {
            return ['intent' => 'accion:cupon_sin_bloque', 'respuesta' => [
                'texto' => 'Los cupones son del bloque **Promociones** (con el motor de reservas), que este hotel no tiene activo. Se contrata desde Configuracion.',
                'enlace' => ['url' => 'configuracion', 'texto' => 'Ver bloques'],
            ]];
        }

        $p = self::parsearCupon($norm);
        if ($p['valor'] === null) {
            return ['intent' => 'accion:cupon_sin_valor', 'respuesta' => [
                'texto' => "No identifique el descuento. Dimelo asi: \"crea un cupon de 10% para agosto\" o \"crea un cupon de \$100 con 20 usos\".",
                'enlace' => null,
            ]];
        }
        if ($p['valor_ambiguo']) {
            $n = rtrim(rtrim(number_format($p['valor'], 2), '0'), '.');
            return ['intent' => 'accion:cupon_valor_ambiguo', 'respuesta' => [
                'texto' => "¿El descuento es **{$n}%** o **\${$n}**? Repitemelo con el signo: \"crea un cupon de {$n}%...\" o \"crea un cupon de \${$n}...\".",
                'enlace' => null,
            ]];
        }
        if ($p['tipo'] === 'porcentaje' && ($p['valor'] < 1 || $p['valor'] > 100)) {
            return ['intent' => 'accion:cupon_valor_fuera', 'respuesta' => [
                'texto' => 'El porcentaje del cupon debe estar entre 1 y 100.',
                'enlace' => null,
            ]];
        }
        if ($p['tipo'] === 'monto' && $p['valor'] < 1) {
            return ['intent' => 'accion:cupon_valor_fuera', 'respuesta' => [
                'texto' => 'El monto del descuento debe ser mayor a cero.',
                'enlace' => null,
            ]];
        }

        // Vigencia: mes referido ("para agosto", "el proximo mes"). Un mes ya
        // pasado se corre al proximo año (un cupon no puede nacer vencido).
        $mes = $this->resolverMesReferido($norm);
        $desde = null;
        $hasta = null;
        $vigenciaTexto = 'sin fecha de expiracion (lo puedes desactivar cuando quieras)';
        if ($mes !== null) {
            if ($mes['hasta'] <= date('Y-m-d')) {
                $mes['desde'] = date('Y-m-d', strtotime($mes['desde'] . ' +1 year'));
                $mes['hasta'] = date('Y-m-d', strtotime($mes['hasta'] . ' +1 year'));
                $mes['etiqueta'] = preg_replace_callback('/\d{4}/', function ($a) {
                    return (string) ((int) $a[0] + 1);
                }, $mes['etiqueta']);
            }
            $desde = $mes['desde'];
            // resolverMesReferido devuelve 'hasta' EXCLUSIVO (dia 1 del mes
            // siguiente); la vigencia del cupon es inclusiva.
            $hasta = date('Y-m-d', strtotime($mes['hasta'] . ' -1 day'));
            $vigenciaTexto = 'vigente todo ' . $mes['etiqueta'] . ' (' . date('d/m', strtotime($desde)) . ' al ' . date('d/m', strtotime($hasta)) . ')';
        }

        $valorEntero = rtrim(rtrim(number_format($p['valor'], 2), '0'), '.');
        $beneficio = $p['tipo'] === 'porcentaje' ? "{$valorEntero}% de descuento" : "\${$valorEntero} de descuento";

        $codigo = $p['codigo'];
        if ($codigo === null) {
            $base = ($mes !== null ? strtok($mes['etiqueta'], ' ') : 'CUPON') . $valorEntero;
            $codigo = $this->generarCodigoCupon($hotelId, $base);
        }

        $limiteTexto = $p['limite'] !== null ? "limite de {$p['limite']} uso(s)" : 'sin limite de usos';

        return ['intent' => 'accion:cupon', 'respuesta' => [
            'texto' => "Puedo crear el cupon **{$codigo}** — {$beneficio}, {$vigenciaTexto}, {$limiteTexto}. "
                . 'Queda activo en el motor de reservas al confirmar.',
            'enlace' => null,
            'accion' => [
                'tipo' => 'crear_cupon',
                'codigo' => $codigo,
                'cupon_tipo' => $p['tipo'],
                'valor' => $p['valor'],
                'vigente_desde' => (string) $desde,
                'vigente_hasta' => (string) $hasta,
                'limite_usos' => $p['limite'] !== null ? (string) $p['limite'] : '',
                'fecha' => date('Y-m-d'),
                'confirm_titulo' => '¿Crear el cupon?',
                'confirm_msg' => "{$codigo}: {$beneficio}, {$vigenciaTexto}, {$limiteTexto}.",
                'confirm_ok' => 'Crear cupon',
            ],
        ]];
    }

    /**
     * Parser puro (sin BD) del cupon dictado. Espera texto ya normalizado.
     * Devuelve ['tipo' => 'porcentaje'|'monto'|null, 'valor' => ?float,
     * 'valor_ambiguo' => bool, 'codigo' => ?string, 'limite' => ?int].
     * Un numero sin "%" ni "$"/"pesos" es ambiguo: se pregunta, no se adivina.
     */
    public static function parsearCupon(string $norm): array
    {
        $frase = $norm;

        $codigo = null;
        if (preg_match('/\bcodigo\s+([a-z0-9][a-z0-9_-]{2,29})\b/', $frase, $m)) {
            $codigo = strtoupper($m[1]);
            $frase = str_replace($m[0], ' ', $frase);
        }

        $limite = null;
        if (preg_match('/\b(\d{1,5})\s+usos?\b/', $frase, $m)
            || preg_match('/\blimite\s+(?:de\s+)?(\d{1,5})\b/', $frase, $m)) {
            $limite = (int) $m[1] > 0 ? (int) $m[1] : null;
            $frase = str_replace($m[0], ' ', $frase);
        }

        $tipo = null;
        $valor = null;
        $ambiguo = false;
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:%|por\s*ciento|porciento)/', $frase, $m)) {
            $tipo = 'porcentaje';
            $valor = (float) $m[1];
        } elseif (preg_match('/\$\s*(\d[\d,]*(?:\.\d{1,2})?)/', $frase, $m)
            || preg_match('/(\d[\d,]*(?:\.\d{1,2})?)\s*pesos\b/', $frase, $m)) {
            $tipo = 'monto';
            $valor = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/\b(\d[\d,]*(?:\.\d{1,2})?)\b/', $frase, $m)) {
            $ambiguo = true;
            $valor = (float) str_replace(',', '', $m[1]);
        }

        return ['tipo' => $tipo, 'valor' => $valor, 'valor_ambiguo' => $ambiguo, 'codigo' => $codigo, 'limite' => $limite];
    }

    /**
     * Codigo legible y DISPONIBLE en este hotel ("AGOSTO10", "AGOSTO10-2"...).
     * Si todo esta tomado cae a un sufijo de hora; el UNIQUE de la tabla es
     * el candado final en crear().
     */
    private function generarCodigoCupon(int $hotelId, string $base): string
    {
        $base = strtoupper((string) preg_replace('/[^A-Z0-9_-]/', '', strtoupper($base)));
        if ($base === '' || strlen($base) < 3) {
            $base = 'CUPON' . $base;
        }
        $base = substr($base, 0, 26);

        $candidato = $base;
        for ($i = 2; $i <= 9; $i++) {
            try {
                $stmt = $this->pdo->prepare("SELECT id FROM motor_cupones WHERE hotel_id = ? AND codigo = ? LIMIT 1");
                $stmt->execute([$hotelId, $candidato]);
                if (!$stmt->fetch()) {
                    return $candidato;
                }
            } catch (Throwable $e) {
                error_log('Copiloto: error al verificar codigo de cupon: ' . $e->getMessage());
                break;
            }
            $candidato = $base . '-' . $i;
        }

        return $base . '-' . date('His');
    }

    /**
     * Detecta "reservale la 204 a Juan del 20 al 22 de agosto" y arma el
     * FORMULARIO PRELLENADO (no crea nada). Deliberadamente NO es una accion
     * POST: crear una reservacion involucra precio, tarifas y anticipo, y
     * eso vive en la pantalla de crear (que ya acepta preseleccion por URL).
     * El copiloto aporta lo que si sabe: entender la frase, verificar que la
     * habitacion este libre esas noches y encontrar al huesped.
     */
    private function detectarAccionReservacion(string $norm, int $hotelId, ?array $contexto = null): ?array
    {
        // "como hago/creo una reservacion" es ayuda, no orden: FAQ la atiende.
        if (strpos($norm, 'como ') !== false || strpos($norm, 'limpi') !== false) {
            return null;
        }

        $fechas = self::parsearFechasReserva($norm);
        $imperativo = (bool) preg_match('/\b(reservale|reservame|apartale|apartame|aparta)\b/', $norm);
        $verboConFechas = preg_match('/\b(reserva|agenda|agendale|crea|creame|haz|hazme)\b/', $norm)
            && preg_match('/\breserva(cion)?\b/', $norm) && $fechas !== null;
        // "quiero hacer una reservacion" arranca el flujo campo por campo.
        $arranque = (bool) preg_match('/\b(quiero|necesito|hazme|vamos a|nueva)\b.*\breservacion\b/', $norm);
        // "¿tiene reserva Garcia?" trae el sustantivo pero ni imperativo ni
        // fechas ni arranque: cae a la busqueda de huesped.
        if (!$imperativo && !$verboConFechas && !$arranque) {
            return null;
        }

        // Sin gate de permiso a proposito: esto termina en un ENLACE al
        // formulario de crear, y esa pantalla no exige permiso adicional
        // (solo sesion del hotel). Gatear aqui mas fuerte que la pantalla
        // rompe con los roles legacy (can_legacy no conoce reservaciones.*).

        $f = self::flujoReservaVacio();

        if ($fechas !== null) {
            $f['fe'] = $fechas['entrada'];
            $f['fs'] = $fechas['salida'];
        }

        $hab = $this->buscarHabitacionEnTexto($norm, $hotelId);
        if ($hab === null && $contexto !== null && ($contexto['tipo'] ?? '') === 'habitacion') {
            $hab = $this->habitacionPorId($hotelId, (int) $contexto['id']);
        }
        if ($hab !== null && $fechas !== null) {
            $libre = $this->habitacionLibre($hotelId, (int) $hab['id'], $f['fe'], $f['fs']);
            if ($libre === false) {
                // Ocupada en el one-shot: aviso + link con fechas sin habitacion.
                return ['intent' => 'accion:reserva_ocupada', 'respuesta' => [
                    'texto' => "La habitacion **{$hab['numero']}** NO esta libre {$this->rangoNochesTexto($f['fe'], $f['fs'])}: tiene una reserva o mantenimiento que se cruza. "
                        . 'Te dejo el formulario con las fechas puestas para que elijas otra habitacion ahi (te muestra solo las disponibles).',
                    'enlace' => null,
                    'acciones' => [['label' => 'Crear la reservacion', 'url' => $this->urlCrearReserva($f)]],
                ]];
            }
            $f['hab_id'] = (int) $hab['id'];
            $f['hab_num'] = (string) $hab['numero'];
        } elseif ($hab !== null) {
            $f['hab_id'] = (int) $hab['id'];
            $f['hab_num'] = (string) $hab['numero'];
        }

        $huesped = $this->buscarHuespedParaReserva($norm, $hotelId);
        if ($huesped !== null && isset($huesped['id'])) {
            $f['huesped_id'] = (int) $huesped['id'];
            $f['nombre'] = (string) $huesped['nombre'];
        } elseif ($huesped !== null && isset($huesped['nuevo'])) {
            $f['nombre'] = (string) $huesped['nuevo'];
            $f['nuevo'] = 1;
        } elseif ($huesped === null && $fechas !== null) {
            // Modo rapido (la frase ya traia fechas) sin nombre: no se
            // estorba preguntando, el formulario captura al huesped. En el
            // arranque generico ("quiero hacer una reservacion") si se pide.
            $f['nom_skip'] = 1;
        }
        // 'varios' se resuelve en el formulario (nom_skip).
        if ($huesped !== null && isset($huesped['varios'])) {
            $f['nom_skip'] = 1;
        }

        // ¿Que falta? Si nada (o solo cosas que el formulario resuelve),
        // cierre directo; si falta algo que el chat puede pedir, arranca el
        // flujo campo por campo desde ahi.
        $paso = self::siguientePasoReserva($f);
        if ($paso === 'listo') {
            return $this->cerrarReserva($hotelId, $f, $huesped !== null && isset($huesped['varios']));
        }

        $f['paso'] = $paso;
        return ['intent' => 'flujo:reserva_' . $paso, 'respuesta' => $this->preguntaFlujoReserva($f, '', $hotelId)];
    }

    /** Estado inicial del flujo conversacional de reservacion. */
    private static function flujoReservaVacio(): array
    {
        return ['t' => 'reserva', 'paso' => '', 'fe' => '', 'fs' => '', 'hab_id' => 0, 'hab_num' => '',
            'hab_skip' => 0, 'huesped_id' => 0, 'nombre' => '', 'nom_skip' => 0, 'nuevo' => 0, 'tel' => '', 'tel_ok' => 0];
    }

    /**
     * Saneador PURO del estado del flujo que reenvia el widget. Viene del
     * cliente: aqui se valida tipo, paso y formato de cada campo; cualquier
     * cosa rara invalida el flujo completo (null) y se sigue normal. Los
     * datos criticos (habitacion del hotel, disponibilidad, huesped) se
     * REVALIDAN contra la base al cerrar, no aqui.
     */
    public static function sanearFlujo($crudo): ?array
    {
        if (is_string($crudo)) {
            $crudo = trim($crudo) === '' ? null : json_decode(mb_substr($crudo, 0, 2000), true);
        }
        if (!is_array($crudo) || ($crudo['t'] ?? '') !== 'reserva') {
            return null;
        }
        $paso = (string) ($crudo['paso'] ?? '');
        if (!in_array($paso, ['fechas', 'habitacion', 'nombre', 'telefono'], true)) {
            return null;
        }

        $f = self::flujoReservaVacio();
        $f['paso'] = $paso;
        foreach (['fe', 'fs'] as $k) {
            $v = (string) ($crudo[$k] ?? '');
            if ($v !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) || strtotime($v) === false)) {
                return null;
            }
            $f[$k] = $v;
        }
        if ($f['fe'] !== '' && $f['fs'] !== '' && $f['fs'] <= $f['fe']) {
            return null;
        }
        $f['hab_id'] = max(0, (int) ($crudo['hab_id'] ?? 0));
        $f['hab_num'] = mb_substr(trim((string) ($crudo['hab_num'] ?? '')), 0, 20);
        $f['huesped_id'] = max(0, (int) ($crudo['huesped_id'] ?? 0));
        $f['nombre'] = mb_substr(trim((string) ($crudo['nombre'] ?? '')), 0, 60);
        $f['tel'] = mb_substr(preg_replace('/[^\d+ -]/', '', (string) ($crudo['tel'] ?? '')), 0, 20);
        foreach (['hab_skip', 'nom_skip', 'nuevo', 'tel_ok'] as $k) {
            $f[$k] = ((int) ($crudo[$k] ?? 0)) === 1 ? 1 : 0;
        }

        return $f;
    }

    /** Siguiente dato faltante del flujo, o 'listo'. */
    private static function siguientePasoReserva(array $f): string
    {
        if ($f['fe'] === '' || $f['fs'] === '') {
            return 'fechas';
        }
        if ($f['hab_id'] === 0 && $f['hab_skip'] === 0) {
            return 'habitacion';
        }
        if ($f['huesped_id'] === 0 && $f['nombre'] === '' && $f['nom_skip'] === 0) {
            return 'nombre';
        }
        if ($f['nuevo'] === 1 && $f['tel_ok'] === 0) {
            return 'telefono';
        }
        return 'listo';
    }

    /** Pregunta del paso actual, con el estado del flujo para el widget. */
    private function preguntaFlujoReserva(array $f, string $prefacio = '', int $hotelId = 0): array
    {
        $preguntas = [
            'fechas' => '¿Para que fechas? Dime por ejemplo "del 20 al 22 de agosto", "el 15 de agosto por 3 noches" o "manana por 2 noches".',
            'habitacion' => $this->preguntaHabitacion($hotelId),
            'nombre' => '¿A nombre de quien va? Dime el nombre del huesped, o "sin nombre" para capturarlo en el formulario.',
            'telefono' => 'No encuentro a **' . ($f['nombre'] !== '' ? ucwords($f['nombre']) : 'ese huesped') . '** en tus huespedes; lo registro como nuevo. ¿Cual es su telefono? (o dime "sin telefono")',
        ];

        return [
            'texto' => ($prefacio !== '' ? $prefacio . ' ' : '') . ($preguntas[$f['paso']] ?? '¿Seguimos?') . ' _(puedes decir "cancelar" en cualquier momento)_',
            'enlace' => null,
            'flujo' => $f,
        ];
    }

    /**
     * Procesa la respuesta del usuario al paso pendiente del flujo. Devuelve
     * la siguiente pregunta/cierre, o null si el mensaje NO parece respuesta
     * del flujo (una pregunta de datos): en ese caso el pipeline normal la
     * atiende y el widget conserva el flujo para el siguiente mensaje.
     */
    private function continuarFlujoReserva(int $hotelId, string $norm, array $f): ?array
    {
        if (preg_match('/\b(cancela|cancelar|cancelalo|olvidalo|dejalo|ya no|olvida)\b/', $norm)) {
            return ['intent' => 'flujo:reserva_cancel', 'respuesta' => [
                'texto' => 'Listo, cancele la reservacion que traiamos a medias. Aqui sigo para lo que necesites.',
                'enlace' => null,
                'flujo_fin' => true,
            ]];
        }
        // Una pregunta ("¿cuanto tengo en caja?") no es respuesta del flujo:
        // que la atienda el pipeline normal sin matar la captura.
        if (strpos($norm, '?') !== false
            || preg_match('/^(cuanto|cuanta|cuantos|cuantas|quien|que|cual|cuales|hay|tiene|dame|dime|como)\b/', $norm)) {
            return null;
        }

        $prefacio = '';

        switch ($f['paso']) {
            case 'fechas':
                $fechas = self::parsearFechasReserva($norm);
                if ($fechas === null) {
                    return ['intent' => 'flujo:reserva_fechas_reask', 'respuesta' => $this->preguntaFlujoReserva($f, 'No entendi esas fechas.', $hotelId)];
                }
                $f['fe'] = $fechas['entrada'];
                $f['fs'] = $fechas['salida'];
                // Si ya traiamos habitacion, revalidar que este libre en las
                // fechas recien dadas.
                if ($f['hab_id'] > 0 && $this->habitacionLibre($hotelId, $f['hab_id'], $f['fe'], $f['fs']) === false) {
                    $prefacio = 'Ojo: la habitacion ' . $f['hab_num'] . ' NO esta libre esas noches, elegimos otra.';
                    $f['hab_id'] = 0;
                    $f['hab_num'] = '';
                }
                break;

            case 'habitacion':
                if (preg_match('/\b(cualquiera|la que sea|sin habitacion|no se|tu dime|ninguna|luego)\b/', $norm)) {
                    $f['hab_skip'] = 1;
                    break;
                }
                // 1) ¿Dio un numero exacto? (como aparece en Habitaciones)
                $hab = $this->buscarHabitacionEnTexto($norm, $hotelId);
                if ($hab !== null) {
                    if ($this->habitacionLibre($hotelId, (int) $hab['id'], $f['fe'], $f['fs']) === false) {
                        return ['intent' => 'flujo:reserva_hab_ocupada', 'respuesta' => $this->preguntaFlujoReserva($f, 'La ' . $hab['numero'] . ' NO esta libre esas noches.', $hotelId)];
                    }
                    $f['hab_id'] = (int) $hab['id'];
                    $f['hab_num'] = (string) $hab['numero'];
                    break;
                }
                // 2) ¿Dio un TIPO (sencilla, doble…)? Le aparto una libre de ese tipo.
                $porTipo = $this->buscarHabitacionLibrePorTipo($hotelId, $norm, $f['fe'], $f['fs']);
                if ($porTipo === null) {
                    return ['intent' => 'flujo:reserva_hab_reask', 'respuesta' => $this->preguntaFlujoReserva($f, 'No reconoci esa habitacion ni el tipo.', $hotelId)];
                }
                if (isset($porTipo['sin_libre'])) {
                    return ['intent' => 'flujo:reserva_hab_tipo_ocupado', 'respuesta' => $this->preguntaFlujoReserva($f, 'No me queda ninguna **' . $porTipo['tipo_label'] . '** libre esas noches; dime otro tipo o un numero.', $hotelId)];
                }
                $f['hab_id'] = (int) $porTipo['id'];
                $f['hab_num'] = (string) $porTipo['numero'];
                $prefacio = 'Te aparto la **' . $porTipo['numero'] . '** (' . $porTipo['tipo_label'] . ') ✔.';
                break;

            case 'nombre':
                if (preg_match('/\b(sin nombre|luego|en el formulario|despues|omite)\b/', $norm)) {
                    $f['nom_skip'] = 1;
                    break;
                }
                $nombre = trim((string) preg_replace('/^(se llama|a nombre de|para|es|el señor|la señora|sr|sra)\s+/', '', $norm));
                $nombre = trim((string) preg_replace('/[^a-z ]/', '', $nombre));
                if (mb_strlen($nombre) < 3) {
                    return ['intent' => 'flujo:reserva_nombre_reask', 'respuesta' => $this->preguntaFlujoReserva($f, 'Necesito un nombre de al menos 3 letras.', $hotelId)];
                }
                $res = $this->buscarHuespedPorNombre($hotelId, $nombre);
                if (isset($res['id'])) {
                    $f['huesped_id'] = (int) $res['id'];
                    $f['nombre'] = (string) $res['nombre'];
                    $prefacio = 'Encontre a **' . $res['nombre'] . '** en tus huespedes ✔.';
                } elseif (isset($res['varios'])) {
                    return ['intent' => 'flujo:reserva_nombre_varios', 'respuesta' => $this->preguntaFlujoReserva($f, 'Hay varios huespedes que casan: **' . implode('**, **', $res['varios']) . '**. Dimelo con el nombre completo.', $hotelId)];
                } else {
                    $f['nombre'] = $nombre;
                    $f['nuevo'] = 1;
                }
                break;

            case 'telefono':
                if (preg_match('/\b(sin telefono|no tiene|no tengo|no se|luego|omite)\b/', $norm)) {
                    $f['tel'] = '';
                    $f['tel_ok'] = 1;
                    break;
                }
                if (!preg_match('/(\d[\d\s-]{5,18}\d)/', $norm, $m)) {
                    return ['intent' => 'flujo:reserva_tel_reask', 'respuesta' => $this->preguntaFlujoReserva($f, 'No vi un telefono valido (minimo 7 digitos).', $hotelId)];
                }
                $f['tel'] = mb_substr((string) preg_replace('/[^\d]/', '', $m[1]), 0, 20);
                $f['tel_ok'] = 1;
                break;

            default:
                return null;
        }

        $paso = self::siguientePasoReserva($f);
        if ($paso === 'listo') {
            $cierre = $this->cerrarReserva($hotelId, $f, false);
            return ['intent' => $cierre['intent'], 'respuesta' => $cierre['respuesta'] + ['flujo_fin' => true]];
        }

        $f['paso'] = $paso;
        return ['intent' => 'flujo:reserva_' . $paso, 'respuesta' => $this->preguntaFlujoReserva($f, $prefacio, $hotelId)];
    }

    /**
     * Cierre comun del one-shot y del flujo: con huesped NUEVO propone la
     * accion confirmable (registra al huesped y arma el enlace); si no, el
     * enlace prellenado directo. El precio/anticipo siguen en la pantalla.
     */
    private function cerrarReserva(int $hotelId, array $f, bool $huespedVarios): array
    {
        $rango = $this->rangoNochesTexto($f['fe'], $f['fs']);
        $piezas = [];
        if ($f['hab_id'] > 0) {
            $piezas[] = 'habitacion **' . $f['hab_num'] . '** (libre esas noches ✔)';
        }
        $piezas[] = $rango;
        if ($f['huesped_id'] > 0) {
            $piezas[] = 'para **' . ($f['nombre'] !== '' ? ucwords($f['nombre']) : 'el huesped elegido') . '** (ya en tus huespedes)';
        } elseif ($huespedVarios) {
            $piezas[] = 'el nombre casa con varios huespedes: eligelo en el formulario';
        }

        if ($f['nuevo'] === 1 && $f['nombre'] !== '') {
            $nombreBonito = ucwords($f['nombre']);
            $telTexto = $f['tel'] !== '' ? "tel {$f['tel']}" : 'sin telefono';
            $piezas[] = "para **{$nombreBonito}** (huesped NUEVO, {$telTexto})";
            return ['intent' => 'accion:reserva_huesped', 'respuesta' => [
                'texto' => 'Queda asi: ' . implode(', ', $piezas) . '. Confirmalo: registro a ' . $nombreBonito
                    . ' en tus huespedes y te abro el formulario con todo puesto (el precio y el cobro se hacen ahi, como siempre).',
                'enlace' => null,
                'accion' => [
                    'tipo' => 'finalizar_reserva',
                    'fecha_entrada' => $f['fe'],
                    'fecha_salida' => $f['fs'],
                    'habitacion_id' => $f['hab_id'],
                    'huesped_nombre' => $nombreBonito,
                    'telefono' => $f['tel'],
                    'huesped_nuevo' => 1,
                    'fecha' => date('Y-m-d'),
                    'confirm_titulo' => '¿Registrar huesped y armar la reservacion?',
                    'confirm_msg' => "Se registra a {$nombreBonito} ({$telTexto}) como huesped y se abre el formulario: " . strip_tags(str_replace('**', '', implode(', ', array_slice($piezas, 0, -1)))) . '.',
                    'confirm_ok' => 'Registrar y armar',
                ],
            ]];
        }

        return ['intent' => 'accion:reserva_link', 'respuesta' => [
            'texto' => 'Te dejo la reservacion armada: ' . implode(', ', $piezas) . '. '
                . 'Abre el formulario, revisa el precio que calcula el sistema y guardala ahi; el cobro o anticipo se hace en esa pantalla, como siempre.',
            'enlace' => null,
            'acciones' => [['label' => 'Crear la reservacion', 'url' => $this->urlCrearReserva($f)]],
        ]];
    }

    /** URL del formulario de crear con lo que el flujo haya juntado. */
    private function urlCrearReserva(array $f): string
    {
        $params = ['fecha_entrada=' . $f['fe'], 'fecha_salida=' . $f['fs'], 'preseleccion=1'];
        if (($f['hab_id'] ?? 0) > 0) {
            $params[] = 'habitacion_id=' . (int) $f['hab_id'];
        }
        if (($f['huesped_id'] ?? 0) > 0) {
            $params[] = 'huesped_id=' . (int) $f['huesped_id'];
        }
        return 'reservaciones/crear?' . implode('&', $params);
    }

    /** "del 20/12 al 22/12 (2 noches)". */
    private function rangoNochesTexto(string $fe, string $fs): string
    {
        $noches = max(1, (int) round((strtotime($fs) - strtotime($fe)) / 86400));
        return 'del ' . date('d/m', strtotime($fe)) . ' al ' . date('d/m', strtotime($fs))
            . ' (' . $noches . ' noche' . ($noches === 1 ? '' : 's') . ')';
    }

    /** Disponibilidad real (reservas + mantenimientos): true/false/null si fallo. */
    private function habitacionLibre(int $hotelId, int $habitacionId, string $fe, string $fs): ?bool
    {
        try {
            require_once __DIR__ . '/../models/Reservacion.php';
            return (bool) (new Reservacion())->verificarDisponibilidadMultiple([$habitacionId], $fe, $fs);
        } catch (Throwable $e) {
            error_log('Copiloto: error disponibilidad reserva: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca huespedes por nombre (LIKE, scope de hotel). Devuelve
     * ['id','nombre'] si hay UNO, ['varios' => nombres] si hay mas, o [] si
     * ninguno.
     */
    private function buscarHuespedPorNombre(int $hotelId, string $nombre): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, nombre_completo FROM huespedes
                 WHERE hotel_id = ? AND nombre_completo LIKE ?
                 ORDER BY id DESC LIMIT 3"
            );
            $stmt->execute([$hotelId, '%' . addcslashes($nombre, "%_\\") . '%']);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto: error huesped por nombre: ' . $e->getMessage());
            return [];
        }

        if (count($filas) === 1) {
            return ['id' => (int) $filas[0]['id'], 'nombre' => trim((string) $filas[0]['nombre_completo'])];
        }
        if (count($filas) > 1) {
            // Un match EXACTO gana aunque haya parecidos.
            foreach ($filas as $fila) {
                if ($this->normalizar((string) $fila['nombre_completo']) === $this->normalizar($nombre)) {
                    return ['id' => (int) $fila['id'], 'nombre' => trim((string) $fila['nombre_completo'])];
                }
            }
            return ['varios' => array_map(function ($x) {
                return trim((string) $x['nombre_completo']);
            }, array_slice($filas, 0, 3))];
        }

        return [];
    }

    /**
     * Parser puro (sin BD) de fechas de estancia dictadas. Espera texto
     * normalizado; $hoy inyectable para tests. Entiende:
     *  - "del 20 al 22 (de agosto)": rango; sin mes usa el actual y si ya
     *    paso se corre al mes siguiente; con mes nombrado ya pasado, al año
     *    siguiente (una estancia no puede nacer en el pasado).
     *  - "el 15 de agosto (por 3 noches)": entrada + noches (default 1).
     *  - "hoy/manana/pasado manana (por N noches)".
     * Devuelve ['entrada' => Y-m-d, 'salida' => Y-m-d] o null.
     */
    public static function parsearFechasReserva(string $norm, ?string $hoy = null): ?array
    {
        $hoy = $hoy ?: date('Y-m-d');
        $meses = ['enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6, 'julio' => 7,
            'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12];

        $noches = 1;
        if (preg_match('/\b(\d{1,2})\s+noches?\b/', $norm, $m)) {
            $noches = max(1, min(30, (int) $m[1]));
        }

        $reMes = '(' . implode('|', array_keys($meses)) . ')';

        // "del 20 al 22 (de agosto)"
        if (preg_match('/\bdel?\s+(\d{1,2})\s+al\s+(\d{1,2})(?:\s+de\s+' . $reMes . ')?/', $norm, $m)) {
            $d1 = (int) $m[1];
            $d2 = (int) $m[2];
            if ($d1 >= $d2) {
                return null;
            }
            $conMes = isset($m[3]) && $m[3] !== '';
            $mes = $conMes ? $meses[$m[3]] : (int) date('n', strtotime($hoy));
            $anio = (int) date('Y', strtotime($hoy));
            if (!checkdate($mes, $d1, $anio) || !checkdate($mes, $d2, $anio)) {
                return null;
            }
            $entrada = sprintf('%04d-%02d-%02d', $anio, $mes, $d1);
            if ($entrada < $hoy) {
                // Pasado: mes nombrado -> +1 año; mes implicito -> mes siguiente.
                if ($conMes) {
                    $entrada = date('Y-m-d', strtotime($entrada . ' +1 year'));
                } else {
                    $base = strtotime(sprintf('%04d-%02d-01', $anio, $mes) . ' +1 month');
                    $mes = (int) date('n', $base);
                    $anio = (int) date('Y', $base);
                    if (!checkdate($mes, $d1, $anio) || !checkdate($mes, $d2, $anio)) {
                        return null;
                    }
                    $entrada = sprintf('%04d-%02d-%02d', $anio, $mes, $d1);
                }
            }
            return ['entrada' => $entrada, 'salida' => substr($entrada, 0, 8) . sprintf('%02d', $d2)];
        }

        // "el 15 de agosto (por N noches)"
        if (preg_match('/\bel\s+(\d{1,2})\s+de\s+' . $reMes . '\b/', $norm, $m)) {
            $d = (int) $m[1];
            $mes = $meses[$m[2]];
            $anio = (int) date('Y', strtotime($hoy));
            if (!checkdate($mes, $d, $anio)) {
                return null;
            }
            $entrada = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
            if ($entrada < $hoy) {
                $entrada = date('Y-m-d', strtotime($entrada . ' +1 year'));
            }
            return ['entrada' => $entrada, 'salida' => date('Y-m-d', strtotime($entrada . " +{$noches} days"))];
        }

        // "hoy / manana / pasado manana (por N noches)"
        if (strpos($norm, 'pasado manana') !== false) {
            $entrada = date('Y-m-d', strtotime($hoy . ' +2 days'));
        } elseif (preg_match('/\bmanana\b/', $norm)) {
            $entrada = date('Y-m-d', strtotime($hoy . ' +1 day'));
        } elseif (preg_match('/\bhoy\b/', $norm)) {
            $entrada = $hoy;
        } else {
            return null;
        }

        return ['entrada' => $entrada, 'salida' => date('Y-m-d', strtotime($entrada . " +{$noches} days"))];
    }

    /**
     * Extrae el posible nombre de huesped de la frase ("a Juan Perez", "para
     * Maria") y lo busca en el catalogo del hotel. Devuelve ['id','nombre']
     * (existe), ['varios' => true] (ambiguo), ['nuevo' => nombre] (dictado
     * pero no registrado: el flujo ofrece darlo de alta) o null (la frase no
     * trae nombre).
     */
    private function buscarHuespedParaReserva(string $norm, int $hotelId): ?array
    {
        if (!preg_match('/\b(?:a|para)\s+([a-z][a-z ]{2,50}?)(?=\s+(?:del|el|los|la|las|un|una|hoy|manana|por|en|de|\d)|$)/', $norm, $m)) {
            return null;
        }
        $nombre = trim((string) preg_replace('/\s+(?:la|las|el|los|de|del|señor|señora|sr|sra)\s*$/', '', trim($m[1])));
        if (mb_strlen($nombre) < 3 || in_array($nombre, ['alguien', 'huesped', 'cliente', 'nombre'], true)) {
            return null;
        }

        $res = $this->buscarHuespedPorNombre($hotelId, $nombre);
        if (isset($res['id'])) {
            return $res;
        }
        if (isset($res['varios'])) {
            return ['varios' => true];
        }

        return ['nuevo' => $nombre];
    }

    /** Estado actual de la habitacion (scope de hotel) o '' si no se pudo leer. */
    private function estadoHabitacion(int $hotelId, int $habitacionId): string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT estado FROM habitaciones WHERE id = ? AND hotel_id = ? LIMIT 1");
            $stmt->execute([$habitacionId, $hotelId]);
            return (string) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Copiloto: error estado habitacion: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Busca en la frase a un trabajador ACTIVO del hotel: primero por nombre
     * completo, luego por tokens del nombre (palabra completa, >= 3 letras).
     * Devuelve ['id','nombre'], ['ambiguos' => nombres] si varios casan, o null.
     */
    private function buscarTrabajadorEnTexto(string $norm, int $hotelId): ?array
    {
        try {
            require_once __DIR__ . '/../models/TareaOperativa.php';
            $filas = (new TareaOperativa())->trabajadoresActivosOpciones($hotelId);
        } catch (Throwable $e) {
            error_log('Copiloto: error catalogo trabajadores: ' . $e->getMessage());
            return null;
        }

        $candidatos = [];
        foreach ($filas as $f) {
            $nombre = trim((string) ($f['nombre_completo'] ?? ''));
            $nc = $this->normalizar($nombre);
            if ($nc === '') {
                continue;
            }
            // Nombre completo en la frase: gana de inmediato.
            if (strpos($norm, $nc) !== false) {
                return ['id' => (int) $f['id'], 'nombre' => $nombre];
            }
            foreach (explode(' ', $nc) as $token) {
                if (mb_strlen($token) < 3 || in_array($token, ['del', 'los', 'las'], true)) {
                    continue;
                }
                if (preg_match('/(^|[^a-z0-9])' . preg_quote($token, '/') . '($|[^a-z0-9])/', $norm)) {
                    $candidatos[(int) $f['id']] = $nombre;
                    break;
                }
            }
        }

        if (count($candidatos) === 1) {
            return ['id' => (int) array_key_first($candidatos), 'nombre' => (string) reset($candidatos)];
        }
        if (count($candidatos) > 1) {
            return ['ambiguos' => array_slice(array_values($candidatos), 0, 3)];
        }

        return null;
    }

    /**
     * Busca en la frase un numero/nombre de habitacion DEL hotel (palabra
     * completa, el candidato mas largo gana). Los nombres tipo "la" o "el"
     * se ignoran para no confundirlos con articulos.
     */
    private function buscarHabitacionEnTexto(string $norm, int $hotelId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, numero FROM habitaciones WHERE hotel_id = ? AND activa = 1 LIMIT 300");
            $stmt->execute([$hotelId]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto: error catalogo habitaciones: ' . $e->getMessage());
            return null;
        }

        $stopwords = ['la', 'el', 'de', 'del', 'a', 'y', 'o', 'con', 'para', 'hoy', 'esta', 'este'];
        $mejor = null;
        $mejorLen = 0;
        foreach ($filas as $f) {
            $candidato = $this->normalizar((string) $f['numero']);
            if ($candidato === '' || in_array($candidato, $stopwords, true)) {
                continue;
            }
            if (!is_numeric($candidato) && mb_strlen($candidato) < 2) {
                continue;
            }
            $patron = '/(^|[^a-z0-9])' . preg_quote($candidato, '/') . '($|[^a-z0-9])/';
            if (preg_match($patron, $norm) && mb_strlen($candidato) > $mejorLen) {
                $mejor = ['id' => (int) $f['id'], 'numero' => (string) $f['numero']];
                $mejorLen = mb_strlen($candidato);
            }
        }

        return $mejor;
    }

    /**
     * Pregunta del paso 'habitacion' redactada con los tipos REALES del hotel
     * (sencilla, doble, Manolo…, segun su catalogo). Guia por tipo primero —
     * "cualquiera" queda como salida clara, no como opcion principal.
     */
    private function preguntaHabitacion(int $hotelId): string
    {
        $tipos = $hotelId > 0 ? $this->tiposHabitacionDisponibles($hotelId) : [];
        $ejemplos = $tipos !== []
            ? implode(', ', array_slice(array_values($tipos), 0, 4))
            : 'sencilla, doble, triple';
        return '¿De que tipo la quieres? Dime el tipo (' . $ejemplos . ') y te aparto una libre, '
            . 'o el numero exacto si ya sabes cual. Si prefieres elegirla en el formulario, di "cualquiera".';
    }

    /**
     * Tipos de habitacion presentes y activos en el hotel, como
     * ['clave_enum' => 'Etiqueta bonita'] (reusa el catalogo del hotel).
     */
    private function tiposHabitacionDisponibles(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT tipo FROM habitaciones WHERE hotel_id = ? AND activa = 1 ORDER BY tipo"
            );
            $stmt->execute([$hotelId]);
            $tipos = [];
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
                $t = (string) $t;
                if ($t === '') {
                    continue;
                }
                $tipos[$t] = function_exists('get_tipo_habitacion')
                    ? get_tipo_habitacion($t)
                    : ucfirst(str_replace('_', ' ', $t));
            }
            return $tipos;
        } catch (Throwable $e) {
            error_log('Copiloto: error tipos habitacion: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Detecta un TIPO de habitacion en el texto (por clave o etiqueta del
     * catalogo, mas sinonimos comunes) y devuelve la primera libre de ese tipo
     * en las fechas. Retornos: ['id','numero','tipo_label'] si aparto una;
     * ['tipo_label','sin_libre'=>true] si el tipo existe pero no hay libre;
     * null si el texto no menciona ningun tipo conocido.
     */
    private function buscarHabitacionLibrePorTipo(int $hotelId, string $norm, string $fe, string $fs): ?array
    {
        $tipos = $this->tiposHabitacionDisponibles($hotelId);
        if ($tipos === []) {
            return null;
        }

        // Match por clave o etiqueta; gana el candidato mas largo (evita que
        // "doble" pise a "doble con jacuzzi").
        $tipoMatch = null;
        $labelMatch = '';
        $lenMatch = 0;
        foreach ($tipos as $clave => $label) {
            foreach ([$clave, $label] as $cand) {
                $c = trim(str_replace('_', ' ', $this->normalizar((string) $cand)));
                if ($c === '') {
                    continue;
                }
                $patron = '/(^|[^a-z0-9])' . preg_quote($c, '/') . '($|[^a-z0-9])/';
                if (preg_match($patron, $norm) && mb_strlen($c) > $lenMatch) {
                    $tipoMatch = (string) $clave;
                    $labelMatch = (string) $label;
                    $lenMatch = mb_strlen($c);
                }
            }
        }

        // Sinonimos habituales cuando no dijo la palabra exacta del catalogo.
        if ($tipoMatch === null) {
            $sinonimos = ['individual' => 'sencilla', 'simple' => 'sencilla', 'matrimonial' => 'doble', 'king' => 'doble'];
            foreach ($sinonimos as $syn => $clave) {
                if (isset($tipos[$clave]) && preg_match('/(^|[^a-z0-9])' . $syn . '($|[^a-z0-9])/', $norm)) {
                    $tipoMatch = $clave;
                    $labelMatch = (string) $tipos[$clave];
                    break;
                }
            }
        }

        if ($tipoMatch === null) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, numero FROM habitaciones WHERE hotel_id = ? AND activa = 1 AND tipo = ? ORDER BY numero LIMIT 50"
            );
            $stmt->execute([$hotelId, $tipoMatch]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $h) {
                if ($this->habitacionLibre($hotelId, (int) $h['id'], $fe, $fs) === true) {
                    return ['id' => (int) $h['id'], 'numero' => (string) $h['numero'], 'tipo_label' => $labelMatch];
                }
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error habitacion por tipo: ' . $e->getMessage());
            return null;
        }

        return ['tipo_label' => $labelMatch, 'sin_libre' => true];
    }

    private function habitacionPorId(int $hotelId, int $habitacionId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, numero FROM habitaciones WHERE id = ? AND hotel_id = ? AND activa = 1 LIMIT 1");
            $stmt->execute([$habitacionId, $hotelId]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC);
            return $f ? ['id' => (int) $f['id'], 'numero' => (string) $f['numero']] : null;
        } catch (Throwable $e) {
            error_log('Copiloto: error habitacion por id: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ejecuta una accion confirmada por el usuario. Cada tipo reusa el motor
     * de su pantalla (tareas_operativas, MantenimientoService, MovimientoCaja)
     * y revalida el permiso del rol: la confirmacion del widget es UX, no
     * seguridad. En dinero SOLO existe registrar_gasto (efectivo, caja
     * abierta); cobros y cortes jamas se tocan desde aqui.
     */
    public function ejecutarAccion(int $hotelId, string $tipo, array $params, ?int $usuarioId = null): array
    {
        if ($tipo === 'iniciar_mantenimiento') {
            return $this->ejecutarMantenimiento($hotelId, $params, $usuarioId);
        }
        if ($tipo === 'finalizar_mantenimiento') {
            return $this->ejecutarFinalizarMantenimiento($hotelId, $params, $usuarioId);
        }
        if ($tipo === 'registrar_gasto') {
            return $this->ejecutarGasto($hotelId, $params, $usuarioId);
        }
        if ($tipo === 'crear_cupon') {
            return $this->ejecutarCupon($hotelId, $params, $usuarioId);
        }
        if ($tipo === 'pagar_proveedor') {
            return $this->ejecutarPagoProveedor($hotelId, $params, $usuarioId);
        }
        if ($tipo === 'finalizar_reserva') {
            return $this->ejecutarFinalizarReserva($hotelId, $params, $usuarioId);
        }

        if (!in_array($tipo, ['programar_limpieza', 'asignar_limpieza'], true)) {
            return ['success' => false, 'texto' => 'Esa accion no esta disponible desde el copiloto.', 'fuente' => 'reglas'];
        }

        if (function_exists('can') && !can('habitaciones.view')) {
            return ['success' => false, 'texto' => 'Tu rol no tiene permiso para gestionar limpiezas.', 'fuente' => 'reglas'];
        }

        $habitacionId = (int) ($params['habitacion_id'] ?? 0);
        $fecha = (string) ($params['fecha'] ?? date('Y-m-d'));
        $trabajadorId = (int) ($params['trabajador_id'] ?? 0);
        $conPersonal = $tipo === 'asignar_limpieza';
        $logRef = $conPersonal ? 'asignar' : 'limpieza';

        if ($conPersonal && $trabajadorId <= 0) {
            return ['success' => false, 'texto' => 'Falta a quien asignarle la limpieza. Intenta de nuevo desde el chat.', 'fuente' => 'reglas'];
        }

        require_once __DIR__ . '/../models/TareaOperativa.php';
        $tareas = new TareaOperativa();

        try {
            if (!$tareas->tablaDisponible() || !$tareas->eventosDisponibles()) {
                return ['success' => false, 'texto' => 'La base de tareas operativas no esta disponible en este hotel.', 'fuente' => 'reglas'];
            }
            // El modelo valida habitacion, fechas y que el personal siga
            // activo en ESTE hotel; con personal la tarea queda 'asignada'.
            $tareas->programarLimpiezaParaHotel($hotelId, $habitacionId, $fecha, $conPersonal ? [$trabajadorId] : [], $usuarioId);
        } catch (Throwable $e) {
            $this->registrar($hotelId, $usuarioId, "[accion {$logRef} hab {$habitacionId} {$fecha}]", 'reglas', "accion:{$logRef}_error", 0, 0);
            // Los mensajes de validacion del modelo son para humanos; un error
            // de BD crudo jamas llega al usuario.
            $texto = ($e instanceof PDOException) ? 'No se pudo completar la accion. Intenta de nuevo.' : ($e->getMessage() ?: 'No se pudo completar la accion.');
            return ['success' => false, 'texto' => $texto, 'fuente' => 'reglas'];
        }

        $hab = $this->habitacionPorId($hotelId, $habitacionId);
        $numero = $hab !== null ? $hab['numero'] : (string) $habitacionId;
        $etiqueta = $fecha === date('Y-m-d') ? 'hoy' : 'el ' . $this->fechaCortaConAnio($fecha);

        $this->registrar($hotelId, $usuarioId, "[accion {$logRef} hab {$habitacionId} {$fecha}]", 'reglas', "accion:{$logRef}_ok", 0, 0);

        if ($conPersonal) {
            $nombreTrab = $this->nombreTrabajador($hotelId, $trabajadorId) ?? 'La persona elegida';
            $urlAgenda = $this->tieneModulo('tareas', $hotelId) ? 'tareas' : ($this->tieneModulo('camarista', $hotelId) ? 'camarista' : 'habitaciones');
            return [
                'success' => true,
                'texto' => "Listo ✅ **{$nombreTrab}** quedo a cargo de la limpieza de la habitacion **{$numero}** para {$etiqueta}. Ya aparece en su agenda.",
                'fuente' => 'reglas',
                'enlace' => null,
                'acciones' => [['label' => 'Ver la agenda', 'url' => $urlAgenda]],
            ];
        }

        $urlTablero = 'habitaciones';
        $labelTablero = 'Ver habitaciones';
        if ($this->tieneModulo('tareas', $hotelId)) {
            $urlTablero = 'tareas';
            $labelTablero = 'Asignar personal en Tareas';
        } elseif ($this->tieneModulo('camarista', $hotelId)) {
            $urlTablero = 'camarista';
            $labelTablero = 'Asignar personal en Camarista';
        }

        return [
            'success' => true,
            'texto' => "Listo ✅ Limpieza de la habitacion **{$numero}** programada para {$etiqueta}. "
                . 'Quedo **pendiente de asignar personal**; puedes decirme "asigna a Maria la limpieza de la ' . $numero . '" o hacerlo desde el tablero.',
            'fuente' => 'reglas',
            'enlace' => null,
            'acciones' => [['label' => $labelTablero, 'url' => $urlTablero]],
        ];
    }

    /**
     * Ejecuta "iniciar mantenimiento" via MantenimientoService (mismo motor
     * que la pantalla de habitaciones). Con reservaciones proximas NO se
     * ejecuta desde el chat: se informa y se manda a la ficha, donde el
     * flujo de siempre pide la confirmacion del riesgo.
     */
    private function ejecutarMantenimiento(int $hotelId, array $params, ?int $usuarioId): array
    {
        if (function_exists('can') && !can('habitaciones.mantenimiento')) {
            return ['success' => false, 'texto' => 'Tu rol no tiene permiso para marcar mantenimientos.', 'fuente' => 'reglas'];
        }

        $habitacionId = (int) ($params['habitacion_id'] ?? 0);
        $motivo = trim((string) ($params['motivo'] ?? ''));

        require_once __DIR__ . '/MantenimientoService.php';

        try {
            $r = (new MantenimientoService($this->db))->iniciarParaHotel($hotelId, $habitacionId, 'correctivo', 'media', $motivo, $usuarioId, false);
        } catch (Throwable $e) {
            $this->registrar($hotelId, $usuarioId, "[accion mant hab {$habitacionId}]", 'reglas', 'accion:mant_error', 0, 0);
            $texto = ($e instanceof PDOException) ? 'No se pudo iniciar el mantenimiento. Intenta de nuevo.' : ($e->getMessage() ?: 'No se pudo iniciar el mantenimiento.');
            return ['success' => false, 'texto' => $texto, 'fuente' => 'reglas'];
        }

        $hab = $this->habitacionPorId($hotelId, $habitacionId);
        $numero = $hab !== null ? $hab['numero'] : (string) ($params['habitacion'] ?? $habitacionId);

        if (empty($r['ok'])) {
            $conflictos = (array) ($r['conflictos'] ?? []);
            $primera = $conflictos[0] ?? [];
            $quien = trim((string) ($primera['nombre_completo'] ?? 'un huesped'));
            $llega = !empty($primera['fecha_entrada']) ? date('d/m', strtotime((string) $primera['fecha_entrada'])) : '';
            $this->registrar($hotelId, $usuarioId, "[accion mant hab {$habitacionId}]", 'reglas', 'accion:mant_conflicto', 0, 0);
            return [
                'success' => false,
                'texto' => "No lo ejecute: la habitacion **{$numero}** tiene " . count($conflictos) . ' reservacion(es) proxima(s)'
                    . ($quien !== '' ? " (la mas cercana: {$quien}" . ($llega !== '' ? ", llega el {$llega}" : '') . ')' : '')
                    . '. Si el hotel ya gestiono ese riesgo, inicialo desde la ficha de la habitacion, donde se confirma el aviso.',
                'fuente' => 'reglas',
                'acciones' => [['label' => 'Abrir la ficha', 'url' => 'habitaciones/' . $habitacionId]],
            ];
        }

        $this->registrar($hotelId, $usuarioId, "[accion mant hab {$habitacionId}]", 'reglas', 'accion:mant_ok', 0, 0);

        return [
            'success' => true,
            'texto' => "Listo ✅ La habitacion **{$numero}** quedo en **mantenimiento correctivo** (prioridad media): {$motivo}. El equipo ya fue notificado.",
            'fuente' => 'reglas',
            'enlace' => null,
            'acciones' => [['label' => 'Ver la ficha', 'url' => 'habitaciones/' . $habitacionId]],
        ];
    }

    /**
     * Ejecuta "liberar habitacion" via MantenimientoService::finalizarParaHotel
     * (mismo motor que la pantalla): cierra el mantenimiento en proceso y la
     * habitacion vuelve a 'disponible'.
     */
    private function ejecutarFinalizarMantenimiento(int $hotelId, array $params, ?int $usuarioId): array
    {
        if (function_exists('can') && !can('habitaciones.mantenimiento')) {
            return ['success' => false, 'texto' => 'Tu rol no tiene permiso para gestionar mantenimientos.', 'fuente' => 'reglas'];
        }

        $habitacionId = (int) ($params['habitacion_id'] ?? 0);

        require_once __DIR__ . '/MantenimientoService.php';

        try {
            (new MantenimientoService($this->db))->finalizarParaHotel($hotelId, $habitacionId, $usuarioId);
        } catch (Throwable $e) {
            $this->registrar($hotelId, $usuarioId, "[accion desbloqueo hab {$habitacionId}]", 'reglas', 'accion:desbloqueo_error', 0, 0);
            $texto = ($e instanceof PDOException) ? 'No se pudo liberar la habitacion. Intenta de nuevo.' : ($e->getMessage() ?: 'No se pudo liberar la habitacion.');
            return ['success' => false, 'texto' => $texto, 'fuente' => 'reglas'];
        }

        $hab = $this->habitacionPorId($hotelId, $habitacionId);
        $numero = $hab !== null ? $hab['numero'] : (string) ($params['habitacion'] ?? $habitacionId);

        $this->registrar($hotelId, $usuarioId, "[accion desbloqueo hab {$habitacionId}]", 'reglas', 'accion:desbloqueo_ok', 0, 0);

        return [
            'success' => true,
            'texto' => "Listo ✅ La habitacion **{$numero}** quedo **disponible** de nuevo; su mantenimiento se cerro y el equipo fue notificado.",
            'fuente' => 'reglas',
            'enlace' => null,
            'acciones' => [['label' => 'Ver la ficha', 'url' => 'habitaciones/' . $habitacionId]],
        ];
    }

    /**
     * Ejecuta "registrar gasto" via MovimientoCaja::registrarMovimiento (mismo
     * motor y candados que la pantalla de Caja: corte abierto FOR UPDATE,
     * categoria del hotel). Solo EFECTIVO y solo tipo gasto; la categoria se
     * revalida aqui por si el POST viajo alterado.
     */
    private function ejecutarGasto(int $hotelId, array $params, ?int $usuarioId): array
    {
        if (function_exists('can') && !can('caja.movimientos')) {
            return ['success' => false, 'texto' => 'Tu rol no tiene permiso para registrar gastos en caja.', 'fuente' => 'reglas'];
        }

        $monto = round((float) ($params['monto'] ?? 0), 2);
        $categoriaId = (int) ($params['categoria_id'] ?? 0);
        $descripcion = trim((string) ($params['descripcion'] ?? ''));

        if ($monto <= 0 || $monto > 9999999.99) {
            return ['success' => false, 'texto' => 'El monto del gasto no es valido. Intenta de nuevo desde el chat.', 'fuente' => 'reglas'];
        }
        if (mb_strlen($descripcion) < 3) {
            return ['success' => false, 'texto' => 'Falta el concepto del gasto. Intenta de nuevo desde el chat.', 'fuente' => 'reglas'];
        }

        $categoria = null;
        foreach ($this->categoriasGasto($hotelId) as $c) {
            if ((int) $c['id'] === $categoriaId) {
                $categoria = $c;
                break;
            }
        }
        if ($categoria === null) {
            return ['success' => false, 'texto' => 'Esa categoria de gasto no existe en este hotel. Intenta de nuevo desde el chat.', 'fuente' => 'reglas'];
        }

        require_once __DIR__ . '/../models/Caja.php';
        require_once __DIR__ . '/../models/MovimientoCaja.php';

        try {
            $r = (new MovimientoCaja())->registrarMovimiento([
                'tipo' => 'gasto',
                'categoria_id' => $categoriaId,
                'descripcion' => mb_substr($descripcion, 0, 200),
                'monto' => $monto,
                'metodo_pago' => 'efectivo',
            ]);
        } catch (Throwable $e) {
            error_log('Copiloto: error al registrar gasto: ' . $e->getMessage());
            $r = ['success' => false, 'message' => 'No se pudo registrar el gasto. Intenta de nuevo.'];
        }

        if (empty($r['success'])) {
            $this->registrar($hotelId, $usuarioId, "[accion gasto {$monto}]", 'reglas', 'accion:gasto_error', 0, 0);
            return ['success' => false, 'texto' => (string) ($r['message'] ?? 'No se pudo registrar el gasto.'), 'fuente' => 'reglas'];
        }

        $this->registrar($hotelId, $usuarioId, "[accion gasto {$monto}]", 'reglas', 'accion:gasto_ok', 0, 0);

        return [
            'success' => true,
            'texto' => 'Listo ✅ Gasto de **$' . number_format($monto, 2) . "** en efectivo registrado en la caja: {$descripcion} (categoria **{$categoria['nombre']}**).",
            'fuente' => 'reglas',
            'enlace' => null,
            'acciones' => [['label' => 'Ver la caja', 'url' => 'caja']],
        ];
    }

    /**
     * Ejecuta "crear cupon" via MotorCuponService::crear (mismo motor y
     * validaciones que la pantalla de Cupones: formato del codigo, rango del
     * valor, orden de fechas, UNIQUE del codigo). Mismo gate de bloques que
     * la pantalla.
     */
    private function ejecutarCupon(int $hotelId, array $params, ?int $usuarioId): array
    {
        if (!$this->tieneModulo('motor_reservas', $hotelId) || !$this->tieneModulo('promociones', $hotelId)) {
            return ['success' => false, 'texto' => 'Este hotel no tiene activo el bloque Promociones.', 'fuente' => 'reglas'];
        }

        $tipoCupon = (string) ($params['cupon_tipo'] ?? 'porcentaje');
        if (!in_array($tipoCupon, ['porcentaje', 'monto'], true)) {
            $tipoCupon = 'porcentaje';
        }

        require_once __DIR__ . '/MotorCuponService.php';

        try {
            $r = (new MotorCuponService($this->db))->crear($hotelId, [
                'codigo' => (string) ($params['codigo'] ?? ''),
                'tipo' => $tipoCupon,
                'valor' => (string) ($params['valor'] ?? '0'),
                'vigente_desde' => (string) ($params['vigente_desde'] ?? ''),
                'vigente_hasta' => (string) ($params['vigente_hasta'] ?? ''),
                'limite_usos' => (string) ($params['limite_usos'] ?? ''),
            ], $usuarioId);
        } catch (Throwable $e) {
            error_log('Copiloto: error al crear cupon: ' . $e->getMessage());
            $r = ['success' => false, 'message' => 'No se pudo crear el cupon. Intenta de nuevo.'];
        }

        if (empty($r['success'])) {
            $this->registrar($hotelId, $usuarioId, '[accion cupon ' . (string) ($params['codigo'] ?? '') . ']', 'reglas', 'accion:cupon_error', 0, 0);
            return ['success' => false, 'texto' => (string) ($r['message'] ?? 'No se pudo crear el cupon.'), 'fuente' => 'reglas'];
        }

        $this->registrar($hotelId, $usuarioId, '[accion cupon ' . (string) ($params['codigo'] ?? '') . ']', 'reglas', 'accion:cupon_ok', 0, 0);

        $codigo = strtoupper(trim((string) ($params['codigo'] ?? '')));
        return [
            'success' => true,
            'texto' => "Listo ✅ El cupon **{$codigo}** ya esta **activo** en el motor de reservas. Compartelo tal cual: el huesped lo escribe al reservar en linea y el descuento se aplica solo.",
            'fuente' => 'reglas',
            'enlace' => null,
            'acciones' => [['label' => 'Ver cupones', 'url' => 'motor-reservas/cupones']],
        ];
    }

    /**
     * Ejecuta "pagar proveedor" via CuentaPorPagarPagoService::registrarPago
     * (mismo motor que la pantalla: cuenta y corte FOR UPDATE, monto <=
     * saldo, referencia de caja trazable, auditoria). El servicio revalida
     * TODO; aqui solo permiso, bloque y saneo de parametros.
     */
    private function ejecutarPagoProveedor(int $hotelId, array $params, ?int $usuarioId): array
    {
        if (!$this->tieneModulo('compras', $hotelId)) {
            return ['success' => false, 'texto' => 'Este hotel no tiene activo el bloque Compras.', 'fuente' => 'reglas'];
        }
        if (function_exists('can') && !can('cuentas_por_pagar.pagar')) {
            return ['success' => false, 'texto' => 'Tu rol no tiene permiso para pagar a proveedores.', 'fuente' => 'reglas'];
        }

        $cuentaId = (int) ($params['cuenta_id'] ?? 0);
        $monto = round((float) ($params['monto'] ?? 0), 2);
        $metodo = (string) ($params['metodo'] ?? 'efectivo');
        if (!in_array($metodo, ['efectivo', 'tarjeta', 'transferencia'], true)) {
            $metodo = 'efectivo';
        }

        require_once __DIR__ . '/CuentaPorPagarPagoService.php';

        try {
            $r = (new CuentaPorPagarPagoService($this->db))->registrarPago($hotelId, $cuentaId, [
                'monto' => $monto,
                'metodo_pago' => $metodo,
                'notas' => 'Registrado desde el copiloto',
            ], $usuarioId);
        } catch (Throwable $e) {
            $this->registrar($hotelId, $usuarioId, "[accion pago cxp {$cuentaId} {$monto}]", 'reglas', 'accion:pago_error', 0, 0);
            // Los mensajes del servicio son humanos; un error crudo de BD no
            // llega al usuario.
            $texto = ($e instanceof PDOException) ? 'No se pudo registrar el pago. Intenta de nuevo.' : ($e->getMessage() ?: 'No se pudo registrar el pago.');
            return ['success' => false, 'texto' => $texto, 'fuente' => 'reglas'];
        }

        $this->registrar($hotelId, $usuarioId, "[accion pago cxp {$cuentaId} {$monto}]", 'reglas', 'accion:pago_ok', 0, 0);

        $proveedor = trim((string) ($params['proveedor'] ?? 'el proveedor'));
        $saldoPost = (float) ($r['saldo_posterior'] ?? 0);
        $cierre = ($r['estado'] ?? '') === 'pagada'
            ? 'La cuenta quedo **pagada por completo**. 🎉'
            : 'La cuenta quedo con saldo de **$' . number_format($saldoPost, 2) . '** (parcial).';

        return [
            'success' => true,
            'texto' => 'Listo ✅ Pago de **$' . number_format((float) ($r['monto'] ?? $monto), 2) . "** en {$metodo} a **{$proveedor}** registrado en la caja. {$cierre}",
            'fuente' => 'reglas',
            'enlace' => null,
            'acciones' => [['label' => 'Ver cuentas por pagar', 'url' => 'cuentas-por-pagar']],
        ];
    }

    /**
     * Cierra el flujo de reservacion con huesped NUEVO: lo registra en el
     * catalogo (unica escritura; sin dinero) y devuelve el enlace al
     * formulario prellenado. Si el nombre aparecio mientras tanto (carrera o
     * doble clic), se reusa el existente en vez de duplicar.
     */
    private function ejecutarFinalizarReserva(int $hotelId, array $params, ?int $usuarioId): array
    {
        if (function_exists('can') && !can('huespedes.create')) {
            return ['success' => false, 'texto' => 'Tu rol no tiene permiso para registrar huespedes.', 'fuente' => 'reglas'];
        }

        $fe = (string) ($params['fecha_entrada'] ?? '');
        $fs = (string) ($params['fecha_salida'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fe) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fs) || $fs <= $fe) {
            return ['success' => false, 'texto' => 'Las fechas de la reservacion no son validas. Intenta de nuevo desde el chat.', 'fuente' => 'reglas'];
        }

        $nombre = trim((string) ($params['huesped_nombre'] ?? ''));
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 60) {
            return ['success' => false, 'texto' => 'Falta el nombre del huesped. Intenta de nuevo desde el chat.', 'fuente' => 'reglas'];
        }
        $telefono = mb_substr((string) preg_replace('/[^\d]/', '', (string) ($params['telefono'] ?? '')), 0, 20);

        $huespedId = 0;
        $yaExistia = false;
        $res = $this->buscarHuespedPorNombre($hotelId, $nombre);
        if (isset($res['id'])) {
            $huespedId = (int) $res['id'];
            $yaExistia = true;
        } else {
            try {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO huespedes (hotel_id, nombre_completo, telefono, created_at) VALUES (?, ?, ?, NOW())"
                );
                $stmt->execute([$hotelId, ucwords(mb_strtolower($nombre, 'UTF-8')), $telefono !== '' ? $telefono : null]);
                $huespedId = (int) $this->pdo->lastInsertId();
            } catch (Throwable $e) {
                error_log('Copiloto: error al registrar huesped: ' . $e->getMessage());
                $this->registrar($hotelId, $usuarioId, "[accion reserva huesped]", 'reglas', 'accion:reserva_fin_error', 0, 0);
                return ['success' => false, 'texto' => 'No se pudo registrar al huesped. Intenta de nuevo.', 'fuente' => 'reglas'];
            }
        }

        // Habitacion: se revalida scope y disponibilidad al momento (pudo
        // ocuparse mientras chateabamos); si ya no esta libre, el enlace va
        // sin ella y se avisa.
        $habId = (int) ($params['habitacion_id'] ?? 0);
        $notaHab = '';
        if ($habId > 0) {
            $hab = $this->habitacionPorId($hotelId, $habId);
            if ($hab === null || $this->habitacionLibre($hotelId, $habId, $fe, $fs) === false) {
                $notaHab = ' Ojo: la habitacion elegida ya no esta libre esas noches; elige otra en el formulario.';
                $habId = 0;
            }
        }

        $url = $this->urlCrearReserva(['fe' => $fe, 'fs' => $fs, 'hab_id' => $habId, 'huesped_id' => $huespedId]);

        $this->registrar($hotelId, $usuarioId, "[accion reserva huesped {$huespedId}]", 'reglas', 'accion:reserva_fin_ok', 0, 0);

        $nombreBonito = ucwords(mb_strtolower($nombre, 'UTF-8'));
        $quien = $yaExistia
            ? "**{$nombreBonito}** ya estaba en tus huespedes, asi que lo reuse (sin duplicar)."
            : "Listo ✅ Registre a **{$nombreBonito}** en tus huespedes" . ($telefono !== '' ? " (tel {$telefono})" : '') . '.';

        return [
            'success' => true,
            'texto' => $quien . ' Te dejo el formulario de la reservacion con todo puesto; revisa el precio y guardala ahi.' . $notaHab,
            'fuente' => 'reglas',
            'enlace' => null,
            'acciones' => [['label' => 'Crear la reservacion', 'url' => $url]],
        ];
    }

    /** Nombre del trabajador activo (scope de hotel) para los textos. */
    private function nombreTrabajador(int $hotelId, int $trabajadorId): ?string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT nombre_completo FROM trabajadores WHERE id = ? AND hotel_id = ? LIMIT 1");
            $stmt->execute([$trabajadorId, $hotelId]);
            $nombre = trim((string) $stmt->fetchColumn());
            return $nombre !== '' ? $nombre : null;
        } catch (Throwable $e) {
            error_log('Copiloto: error nombre trabajador: ' . $e->getMessage());
            return null;
        }
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

    private function reservasPorFecha(int $hotelId, string $tipo, int $offsetDias = 0): array
    {
        $columna = $tipo === 'salida' ? 'fecha_salida' : 'fecha_entrada';
        $estados = $tipo === 'salida' ? "('checked_in', 'confirmada')" : "('confirmada', 'pendiente')";
        $offsetDias = max(0, min(30, $offsetDias));

        try {
            $stmt = $this->pdo->prepare(
                "SELECT h.nombre_completo
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE r.hotel_id = ? AND r.{$columna} = CURDATE() + INTERVAL {$offsetDias} DAY AND r.estado IN {$estados}
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

    /**
     * Mini-briefing del dia: ocupacion + llegadas/salidas + pendientes + caja.
     * Publico: tambien lo compone el briefing matutino push (CopilotoBriefingService).
     */
    public function resumenDelDia(int $hotelId): array
    {
        $o = $this->ocupacionHoy($hotelId);
        $lleg = $this->reservasPorFecha($hotelId, 'entrada');
        $sal = $this->reservasPorFecha($hotelId, 'salida');
        $noShow = $this->pendientes($hotelId, 'no_show');
        $venc = $this->pendientes($hotelId, 'checkout_vencido');
        $c = $this->caja($hotelId);

        $lineas = [
            "Asi va tu dia:",
            "• Ocupacion: **{$o['ocupadas']} de {$o['activas']}** habitaciones ({$o['pct']}%).",
            "• Llegadas de hoy: **{$lleg['total']}** · Salidas: **{$sal['total']}**.",
        ];

        $pendientes = [];
        if ($noShow > 0) {
            $pendientes[] = "{$noShow} no-show(s)";
        }
        if ($venc > 0) {
            $pendientes[] = "{$venc} checkout(s) vencido(s)";
        }
        $lineas[] = empty($pendientes)
            ? "• Pendientes: ninguno. ✔"
            : "• Pendientes: **" . implode(' y ', $pendientes) . "**.";

        $lineas[] = $c['abierto']
            ? "• Caja: corte abierto, efectivo esperado \${$c['esperado']}."
            : "• Caja: sin corte abierto.";

        $acciones = [];
        if ($lleg['total'] > 0 || $sal['total'] > 0 || $noShow > 0 || $venc > 0) {
            $acciones[] = ['label' => 'Ver reservaciones', 'url' => 'reservaciones'];
        }
        $acciones[] = ['label' => 'Ir a Caja', 'url' => 'caja'];

        if ($this->tieneModulo('motor_reservas', $hotelId)) {
            $m = $this->motorPorConciliar($hotelId);
            if ($m['n'] > 0) {
                $lineas[] = "• Motor: **{$m['n']} pago(s) online por conciliar** (\${$m['monto']}).";
                $acciones[] = ['label' => 'Conciliar en el motor', 'url' => 'motor-reservas'];
            }
        }

        return [
            'texto' => implode("\n", $lineas),
            'enlace' => ['url' => 'dashboard', 'texto' => 'Ir al dashboard'],
            'acciones' => $acciones,
        ];
    }

    /**
     * Cierre del dia: como quedo la noche, el dinero del dia, pendientes y
     * que viene manana. Publico: tambien lo compone el briefing vespertino
     * push (CopilotoBriefingService). Solo lectura.
     */
    public function resumenDeCierre(int $hotelId): array
    {
        $o = $this->ocupacionHoy($hotelId);
        $dia = $this->gananciasEntre($hotelId, date('Y-m-d'), date('Y-m-d', strtotime('+1 day')));
        $noShow = $this->pendientes($hotelId, 'no_show');
        $venc = $this->pendientes($hotelId, 'checkout_vencido');
        $c = $this->caja($hotelId);
        $manana = $this->reservasPorFecha($hotelId, 'entrada', 1);

        $lineas = [
            'Asi cerro tu dia:',
            "• Esta noche: **{$o['ocupadas']} de {$o['activas']}** habitaciones ocupadas ({$o['pct']}%).",
        ];

        $lineas[] = $dia['hay']
            ? "• Hoy en caja: ingresos **\${$dia['ingresos']}**, gastos \${$dia['gastos']} (neto **\${$dia['neto']}**)."
            : '• Hoy en caja: sin movimientos registrados.';

        $pendientes = [];
        if ($noShow > 0) {
            $pendientes[] = "{$noShow} no-show(s)";
        }
        if ($venc > 0) {
            $pendientes[] = "{$venc} checkout(s) vencido(s)";
        }
        if ($c['abierto']) {
            $pendientes[] = "corte de caja abierto (desde {$c['desde']})";
        }
        $lineas[] = empty($pendientes)
            ? '• Pendientes: ninguno, todo cerrado. ✔'
            : '• Antes de cerrar: **' . implode(', ', $pendientes) . '**.';

        $lineas[] = $manana['total'] === 0
            ? '• Manana: sin llegadas programadas.'
            : "• Manana llegan **{$manana['total']} reservacion(es)**" . ($manana['nombres'] !== '' ? ': ' . $manana['nombres'] : '') . '.';

        $accionesCierre = [];
        if ($c['abierto']) {
            $accionesCierre[] = ['label' => 'Hacer el corte', 'url' => 'caja#cop-ancla-corte'];
        }
        if ($noShow > 0 || $venc > 0 || $manana['total'] > 0) {
            $accionesCierre[] = ['label' => 'Ver reservaciones', 'url' => 'reservaciones'];
        }

        return [
            'texto' => implode("\n", $lineas),
            'enlace' => ['url' => 'dashboard', 'texto' => 'Ir al dashboard'],
            'acciones' => $accionesCierre,
        ];
    }

    /**
     * Ocupacion promedio de los proximos 7 dias, el mejor dia y la serie
     * diaria ('por_dia': fecha => habitaciones ocupadas). Publico: tambien lo
     * usan las alertas proactivas (CopilotoBriefingService).
     */
    public function ocupacionProximos7(int $hotelId): ?array
    {
        $o = $this->ocupacionHoy($hotelId);
        if ($o['activas'] <= 0) {
            return null;
        }

        $porDia = array_fill_keys(
            array_map(static function ($i) { return date('Y-m-d', strtotime("+{$i} days")); }, range(0, 6)),
            0
        );

        try {
            $desde = date('Y-m-d');
            $hasta = date('Y-m-d', strtotime('+7 days'));
            $stmt = $this->pdo->prepare(
                "SELECT r.fecha_entrada, r.fecha_salida, COUNT(rh.id) AS habs
                 FROM reservaciones r
                 INNER JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ? AND r.estado IN ('confirmada', 'checked_in')
                   AND r.fecha_salida > ? AND r.fecha_entrada < ?
                 GROUP BY r.id, r.fecha_entrada, r.fecha_salida"
            );
            $stmt->execute([$hotelId, $desde, $hasta]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $reserva) {
                $ini = max(strtotime((string) $reserva['fecha_entrada']), strtotime($desde));
                $fin = min(strtotime((string) $reserva['fecha_salida']), strtotime($hasta));
                for ($d = $ini; $d < $fin; $d = strtotime('+1 day', $d)) {
                    $clave = date('Y-m-d', $d);
                    if (isset($porDia[$clave])) {
                        $porDia[$clave] += (int) $reserva['habs'];
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error ocupacion semana: ' . $e->getMessage());
        }

        $promedio = (int) round(array_sum($porDia) * 100 / (7 * $o['activas']));
        $mejorFecha = null;
        $mejorHabs = -1;
        foreach ($porDia as $fecha => $habs) {
            if ($habs > $mejorHabs) {
                $mejorHabs = $habs;
                $mejorFecha = $fecha;
            }
        }

        $dias = ['Monday' => 'lunes', 'Tuesday' => 'martes', 'Wednesday' => 'miercoles', 'Thursday' => 'jueves', 'Friday' => 'viernes', 'Saturday' => 'sabado', 'Sunday' => 'domingo'];

        return [
            'activas' => $o['activas'],
            'libres_hoy' => $o['libres'],
            'promedio' => $promedio,
            'mejor_dia' => $mejorHabs > 0 ? ($dias[date('l', strtotime($mejorFecha))] ?? $mejorFecha) . ' ' . date('d/m', strtotime($mejorFecha)) : null,
            'mejor_pct' => $mejorHabs > 0 ? (int) round($mejorHabs * 100 / $o['activas']) : 0,
            'mejor_habs' => max(0, $mejorHabs),
            'por_dia' => $porDia,
        ];
    }

    /** Conteo de habitaciones activas por estado operativo. */
    private function habitacionesPorEstado(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT estado, COUNT(*) AS n FROM habitaciones
                 WHERE hotel_id = ? AND activa = 1 GROUP BY estado"
            );
            $stmt->execute([$hotelId]);
            $out = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $out[(string) $fila['estado']] = (int) $fila['n'];
            }
            return $out;
        } catch (Throwable $e) {
            error_log('Copiloto: error habitaciones por estado: ' . $e->getMessage());
            return [];
        }
    }

    /** Reservaciones con check-in activo ahora mismo. */
    private function hospedadosAhora(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT h.nombre_completo
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE r.hotel_id = ? AND r.estado = 'checked_in'
                 ORDER BY r.fecha_salida ASC LIMIT 30"
            );
            $stmt->execute([$hotelId]);
            $nombres = array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'nombre_completo');
        } catch (Throwable $e) {
            error_log('Copiloto: error hospedados: ' . $e->getMessage());
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

    /** Promedio de calificacion (bloque reputacion) de los ultimos 90 dias. */
    private function calificacionPromedio(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT AVG(calificacion) AS prom, COUNT(*) AS n
                 FROM reputacion_encuestas
                 WHERE hotel_id = ? AND estado = 'respondida'
                   AND respondida_at >= NOW() - INTERVAL 90 DAY"
            );
            $stmt->execute([$hotelId]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Copiloto: error calificacion: ' . $e->getMessage());
            $fila = [];
        }

        $n = (int) ($fila['n'] ?? 0);

        return [
            'respondidas' => $n,
            'promedio' => $n > 0 ? number_format((float) $fila['prom'], 1) : null,
        ];
    }

    /** Cupones vigentes del motor (bloque promociones). */
    private function cuponesActivos(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT codigo FROM motor_cupones
                 WHERE hotel_id = ? AND activo = 1
                   AND (vigente_hasta IS NULL OR vigente_hasta >= CURDATE())
                   AND (limite_usos IS NULL OR usos < limite_usos)
                 ORDER BY id DESC LIMIT 20"
            );
            $stmt->execute([$hotelId]);
            $codigos = array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'codigo');
        } catch (Throwable $e) {
            error_log('Copiloto: error cupones activos: ' . $e->getMessage());
            $codigos = [];
        }

        $n = count($codigos);
        $muestra = array_slice($codigos, 0, 4);
        $texto = implode(', ', $muestra);
        if ($n > count($muestra)) {
            $texto .= ' y ' . ($n - count($muestra)) . ' mas';
        }

        return ['n' => $n, 'codigos' => $texto];
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

    /**
     * Suma ingresos, gastos y ganancia neta de movimientos_caja en el rango
     * [desde, hasta) — misma fuente que Caja/Reportes. Solo lectura y por hotel.
     */
    private function gananciasEntre(int $hotelId, string $desde, string $hasta): array
    {
        $ingresos = 0.0;
        $gastos = 0.0;
        try {
            $stmt = $this->pdo->prepare(
                "SELECT tipo, COALESCE(SUM(monto), 0) AS total
                 FROM movimientos_caja
                 WHERE hotel_id = ? AND created_at >= ? AND created_at < ?
                 GROUP BY tipo"
            );
            $stmt->execute([$hotelId, $desde, $hasta]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                if ((string) $fila['tipo'] === 'ingreso') {
                    $ingresos = (float) $fila['total'];
                } elseif ((string) $fila['tipo'] === 'gasto') {
                    $gastos = (float) $fila['total'];
                }
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error ganancias: ' . $e->getMessage());
        }

        return [
            'hay' => ($ingresos > 0 || $gastos > 0),
            'ingresos' => number_format($ingresos, 2),
            'gastos' => number_format($gastos, 2),
            'neto' => number_format($ingresos - $gastos, 2),
            'neto_num' => $ingresos - $gastos,
        ];
    }

    /** Ingresos/gastos/neto del MES CALENDARIO ANTERIOR. */
    private function gananciasMesPasado(int $hotelId): array
    {
        $ref = strtotime('first day of last month');

        return $this->gananciasEntre($hotelId, date('Y-m-01', $ref), date('Y-m-01'))
            + ['mes' => $this->nombreMes((int) date('n', $ref)), 'anio' => date('Y', $ref)];
    }

    /** Ingresos/gastos/neto del MES EN CURSO, del dia 1 hasta hoy inclusive. */
    private function gananciasMesActual(int $hotelId): array
    {
        // 'hasta' es manana (exclusivo) para incluir todo lo de hoy.
        return $this->gananciasEntre($hotelId, date('Y-m-01'), date('Y-m-d', strtotime('+1 day')))
            + ['mes' => $this->nombreMes((int) date('n')), 'anio' => date('Y')];
    }

    /**
     * Compara la ganancia neta del mes en curso contra el MISMO tramo del mes
     * pasado (primeros N dias, N = dia de hoy), para una lectura justa de ritmo.
     */
    private function compararMesActualVsPasado(int $hotelId): array
    {
        $diaHoy = (int) date('j');
        $refLM = strtotime('first day of last month');

        $actual = $this->gananciasEntre($hotelId, date('Y-m-01'), date('Y-m-d', strtotime('+1 day')));
        // Mismo tramo del mes pasado, sin invadir el mes en curso (cap en el dia 1 de este mes).
        $hastaLM = date('Y-m-d', min(strtotime("+{$diaHoy} days", $refLM), strtotime(date('Y-m-01'))));
        $pasado = $this->gananciasEntre($hotelId, date('Y-m-01', $refLM), $hastaLM);

        return [
            'dia' => $diaHoy,
            'mesActual' => $this->nombreMes((int) date('n')),
            'mesPasado' => $this->nombreMes((int) date('n', $refLM)),
            'netoActual' => $actual['neto'],
            'netoPasado' => $pasado['neto'],
            'netoActualNum' => $actual['neto_num'],
            'netoPasadoNum' => $pasado['neto_num'],
            'hayActual' => $actual['hay'],
            'hayPasado' => $pasado['hay'],
        ];
    }

    /**
     * Resuelve una referencia de dia en la pregunta a una fecha concreta:
     * un dia de la semana ("jueves") -> su ocurrencia mas reciente (hoy si aplica,
     * si no el ultimo pasado); tambien "ayer" y "antier". Null si no reconoce.
     */
    private function resolverDiaReferido(string $norm): ?array
    {
        $dias = ['lunes' => 'Monday', 'martes' => 'Tuesday', 'miercoles' => 'Wednesday', 'jueves' => 'Thursday', 'viernes' => 'Friday', 'sabado' => 'Saturday', 'domingo' => 'Sunday'];
        foreach ($dias as $es => $en) {
            if (strpos($norm, $es) !== false) {
                $ts = strtolower(date('l')) === strtolower($en) ? strtotime('today') : strtotime("last {$en}");
                return ['fecha' => date('Y-m-d', $ts), 'texto' => "el {$es} " . date('j', $ts) . ' de ' . $this->nombreMes((int) date('n', $ts))];
            }
        }
        if (strpos($norm, 'hoy') !== false) {
            $ts = strtotime('today');
            return ['fecha' => date('Y-m-d', $ts), 'texto' => 'hoy (' . date('j', $ts) . ' de ' . $this->nombreMes((int) date('n', $ts)) . ')'];
        }
        // 'antier'/'anteayer' antes que 'ayer' (anteayer contiene 'ayer').
        if (strpos($norm, 'antier') !== false || strpos($norm, 'anteayer') !== false) {
            $ts = strtotime('-2 days');
            return ['fecha' => date('Y-m-d', $ts), 'texto' => 'antier (' . date('j', $ts) . ' de ' . $this->nombreMes((int) date('n', $ts)) . ')'];
        }
        if (strpos($norm, 'ayer') !== false) {
            $ts = strtotime('-1 day');
            return ['fecha' => date('Y-m-d', $ts), 'texto' => 'ayer (' . date('j', $ts) . ' de ' . $this->nombreMes((int) date('n', $ts)) . ')'];
        }
        return null;
    }

    /** Fecha 'Y-m-d' a texto corto en espanol: "jueves 3 de julio". */
    private function fechaCorta(string $ymd): string
    {
        $ts = strtotime($ymd);
        $diasES = ['Monday' => 'lunes', 'Tuesday' => 'martes', 'Wednesday' => 'miercoles', 'Thursday' => 'jueves', 'Friday' => 'viernes', 'Saturday' => 'sabado', 'Sunday' => 'domingo'];
        return ($diasES[date('l', $ts)] ?? '') . ' ' . date('j', $ts) . ' de ' . $this->nombreMes((int) date('n', $ts));
    }

    /** Mejor y peor dia (por ganancia neta) del mes en curso. */
    private function diasTopMes(int $hotelId): array
    {
        $porDia = [];
        try {
            $stmt = $this->pdo->prepare(
                "SELECT DATE(created_at) f, SUM(CASE WHEN tipo='ingreso' THEN monto ELSE -monto END) neto
                 FROM movimientos_caja
                 WHERE hotel_id = ? AND created_at >= ? AND created_at < ?
                 GROUP BY DATE(created_at)"
            );
            $stmt->execute([$hotelId, date('Y-m-01'), date('Y-m-d', strtotime('+1 day'))]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $porDia[(string) $r['f']] = (float) $r['neto'];
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error dias top: ' . $e->getMessage());
        }

        if (empty($porDia)) {
            return ['hay' => false];
        }

        $mejorF = array_keys($porDia, max($porDia))[0];
        $peorF = array_keys($porDia, min($porDia))[0];

        return [
            'hay' => true,
            'mejorFecha' => $this->fechaCorta($mejorF),
            'mejorNeto' => number_format($porDia[$mejorF], 2),
            'peorFecha' => $this->fechaCorta($peorF),
            'peorNeto' => number_format($porDia[$peorF], 2),
        ];
    }

    /** Ingresos del mes en curso separados por metodo de pago. */
    private function ingresosPorMetodo(int $hotelId): array
    {
        $out = ['efectivo' => 0.0, 'tarjeta' => 0.0, 'transferencia' => 0.0];
        try {
            $stmt = $this->pdo->prepare(
                "SELECT metodo_pago, COALESCE(SUM(monto), 0) t
                 FROM movimientos_caja
                 WHERE hotel_id = ? AND tipo = 'ingreso' AND created_at >= ? AND created_at < ?
                 GROUP BY metodo_pago"
            );
            $stmt->execute([$hotelId, date('Y-m-01'), date('Y-m-d', strtotime('+1 day'))]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $out[(string) $r['metodo_pago']] = (float) $r['t'];
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error metodo pago: ' . $e->getMessage());
        }

        return [
            'hay' => array_sum($out) > 0,
            'efectivo' => number_format($out['efectivo'], 2),
            'tarjeta' => number_format($out['tarjeta'], 2),
            'transferencia' => number_format($out['transferencia'], 2),
            'efectivo_num' => $out['efectivo'],
            'tarjeta_num' => $out['tarjeta'],
            'transferencia_num' => $out['transferencia'],
            'mes' => $this->nombreMes((int) date('n')),
        ];
    }

    /**
     * Mini-grafica de columnas (una por dia) para la ocupacion de los
     * proximos 7 dias. El dia mas fuerte va destacado; los porcentajes ya
     * vienen acotados 0-100 por construccion.
     */
    private function vizOcupacionSemana(array $sem): ?array
    {
        if (empty($sem['por_dia']) || (int) $sem['activas'] <= 0) {
            return null;
        }

        $diasCortos = ['Mon' => 'lun', 'Tue' => 'mar', 'Wed' => 'mié', 'Thu' => 'jue', 'Fri' => 'vie', 'Sat' => 'sáb', 'Sun' => 'dom'];
        $mejorPct = -1;
        $items = [];
        foreach ($sem['por_dia'] as $fecha => $habs) {
            $ts = strtotime((string) $fecha);
            $pct = (int) round((int) $habs * 100 / (int) $sem['activas']);
            $items[] = [
                'etiqueta' => ($diasCortos[date('D', $ts)] ?? '') . ' ' . date('j', $ts),
                'valor' => $pct . '%',
                'pct' => max(0, min(100, $pct)),
            ];
            $mejorPct = max($mejorPct, $pct);
        }
        foreach ($items as &$item) {
            $item['destacar'] = $mejorPct > 0 && $item['pct'] === $mejorPct;
        }
        unset($item);

        return ['tipo' => 'columnas', 'items' => $items];
    }

    /** Top 3 categorias de gasto del mes en curso. */
    private function gastosPorCategoria(int $hotelId): array
    {
        $items = [];
        try {
            $stmt = $this->pdo->prepare(
                "SELECT categoria, COALESCE(SUM(monto), 0) t
                 FROM movimientos_caja
                 WHERE hotel_id = ? AND tipo = 'gasto' AND created_at >= ? AND created_at < ?
                 GROUP BY categoria ORDER BY t DESC LIMIT 3"
            );
            $stmt->execute([$hotelId, date('Y-m-01'), date('Y-m-d', strtotime('+1 day'))]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $items[] = ucfirst((string) $r['categoria']) . ' $' . number_format((float) $r['t'], 2);
            }
        } catch (Throwable $e) {
            error_log('Copiloto: error gastos categoria: ' . $e->getMessage());
        }

        return ['hay' => !empty($items), 'items' => $items, 'mes' => $this->nombreMes((int) date('n'))];
    }

    /**
     * Mantenimiento Plus: proximos preventivos (vencidos o en 30 dias) e
     * incidencias abiertas. Defensivo: sin las tablas del bloque no truena.
     */
    private function mantenimientosProximos(int $hotelId): array
    {
        $proximos = [];
        $incidencias = 0;

        try {
            $stmt = $this->pdo->prepare(
                "SELECT a.nombre, a.proximo_servicio,
                        h.numero AS habitacion_numero,
                        DATEDIFF(a.proximo_servicio, CURDATE()) AS dias
                 FROM activos_hotel a
                 LEFT JOIN habitaciones h
                    ON h.id = a.habitacion_id
                   AND h.hotel_id = a.hotel_id
                 WHERE a.hotel_id = ?
                   AND a.activo = 1
                   AND a.proximo_servicio IS NOT NULL
                   AND a.proximo_servicio <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 ORDER BY a.proximo_servicio ASC
                 LIMIT 3"
            );
            $stmt->execute([$hotelId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $dias = (int)($r['dias'] ?? 0);
                $cuando = $dias < 0
                    ? 'VENCIDO hace ' . abs($dias) . ' dia(s)'
                    : ($dias === 0 ? 'vence HOY' : 'en ' . $dias . ' dia(s)');
                $ubic = trim((string)($r['habitacion_numero'] ?? '')) !== '' ? ' (hab. ' . $r['habitacion_numero'] . ')' : '';
                $proximos[] = '**' . (string)$r['nombre'] . '**' . $ubic . ' ' . $cuando;
            }

            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM mantenimientos_habitaciones
                 WHERE hotel_id = ? AND estado = 'en_proceso'"
            );
            $stmt->execute([$hotelId]);
            $incidencias = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Copiloto: error mantenimientos proximos: ' . $e->getMessage());
        }

        return [
            'hay' => !empty($proximos) || $incidencias > 0,
            'proximos' => $proximos,
            'incidencias' => $incidencias,
        ];
    }

    /**
     * Resuelve un mes nombrado ("diciembre") o relativo ("este mes", "proximo mes")
     * a su OCURRENCIA MAS CERCANA (past. o fut., dentro de ~6 meses). Devuelve el
     * rango [desde, hasta) y una etiqueta "diciembre 2026". Null si no reconoce.
     */
    private function resolverMesReferido(string $norm): ?array
    {
        $meses = ['enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12];

        $anio = (int) date('Y');
        $n = null;
        foreach ($meses as $es => $num) {
            if (strpos($norm, $es) !== false) {
                $n = $num;
                $diff = $num - (int) date('n');
                if ($diff < -6) {
                    $anio++;
                } elseif ($diff > 6) {
                    $anio--;
                }
                break;
            }
        }

        if ($n === null) {
            if (strpos($norm, 'proximo mes') !== false || strpos($norm, 'mes que viene') !== false || strpos($norm, 'siguiente mes') !== false) {
                $ref = strtotime('first day of next month');
            } elseif (strpos($norm, 'este mes') !== false) {
                $ref = strtotime('first day of this month');
            } else {
                return null;
            }
            $anio = (int) date('Y', $ref);
            $n = (int) date('n', $ref);
        }

        $desde = sprintf('%04d-%02d-01', $anio, $n);

        return [
            'desde' => $desde,
            'hasta' => date('Y-m-01', strtotime($desde . ' +1 month')),
            'etiqueta' => $this->nombreMes($n) . ' ' . $anio,
        ];
    }

    /** Reservaciones (no canceladas) con llegada en el rango, con habitaciones y noches-habitacion. */
    private function reservacionesDelMes(int $hotelId, string $desde, string $hasta): array
    {
        $total = 0;
        $habs = 0;
        $noches = 0;
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT r.id) total, COUNT(rh.id) habs,
                        COALESCE(SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada)), 0) noches
                 FROM reservaciones r
                 LEFT JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ? AND r.estado <> 'cancelada'
                   AND r.fecha_entrada >= ? AND r.fecha_entrada < ?"
            );
            $stmt->execute([$hotelId, $desde, $hasta]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $total = (int) ($f['total'] ?? 0);
            $habs = (int) ($f['habs'] ?? 0);
            $noches = (int) ($f['noches'] ?? 0);
        } catch (Throwable $e) {
            error_log('Copiloto: error reservaciones mes: ' . $e->getMessage());
        }

        return ['total' => $total, 'habitaciones' => $habs, 'noches' => $noches];
    }

    /** Metricas de las reservaciones (no canceladas) con llegada en el mes en curso. */
    private function metricasReservasMes(int $hotelId): array
    {
        $out = ['n' => 0, 'roomnights' => 0, 'nights' => 0, 'rev' => 0.0];
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) n,
                        COALESCE(SUM(DATEDIFF(fecha_salida, fecha_entrada) * total_habitaciones), 0) roomnights,
                        COALESCE(SUM(DATEDIFF(fecha_salida, fecha_entrada)), 0) nights,
                        COALESCE(SUM(precio_total), 0) rev
                 FROM reservaciones
                 WHERE hotel_id = ? AND estado <> 'cancelada' AND fecha_entrada >= ? AND fecha_entrada < ?"
            );
            $stmt->execute([$hotelId, date('Y-m-01'), date('Y-m-01', strtotime('first day of next month'))]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $out['n'] = (int) ($f['n'] ?? 0);
            $out['roomnights'] = (int) ($f['roomnights'] ?? 0);
            $out['nights'] = (int) ($f['nights'] ?? 0);
            $out['rev'] = (float) ($f['rev'] ?? 0);
        } catch (Throwable $e) {
            error_log('Copiloto: error metricas reservas: ' . $e->getMessage());
        }
        $out['mes'] = $this->nombreMes((int) date('n'));
        return $out;
    }

    /** Cancelaciones vs total de reservaciones con llegada en el mes en curso. */
    private function cancelacionesMes(int $hotelId): array
    {
        $canc = 0;
        $noc = 0;
        try {
            $stmt = $this->pdo->prepare(
                "SELECT SUM(estado = 'cancelada') canc, SUM(estado <> 'cancelada') noc
                 FROM reservaciones
                 WHERE hotel_id = ? AND fecha_entrada >= ? AND fecha_entrada < ?"
            );
            $stmt->execute([$hotelId, date('Y-m-01'), date('Y-m-01', strtotime('first day of next month'))]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $canc = (int) ($f['canc'] ?? 0);
            $noc = (int) ($f['noc'] ?? 0);
        } catch (Throwable $e) {
            error_log('Copiloto: error cancelaciones: ' . $e->getMessage());
        }
        return ['canceladas' => $canc, 'total' => $canc + $noc, 'mes' => $this->nombreMes((int) date('n'))];
    }

    /** Reservaciones registradas hoy (por fecha de creacion). */
    private function reservasCreadasHoy(int $hotelId): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reservaciones WHERE hotel_id = ? AND DATE(created_at) = CURDATE()");
            $stmt->execute([$hotelId]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Copiloto: error reservas hoy: ' . $e->getMessage());
            return 0;
        }
    }

    /** Carga de limpieza de hoy: habitaciones en estado limpieza + salidas del dia. */
    private function limpiezaHoy(int $hotelId): array
    {
        $estados = $this->habitacionesPorEstado($hotelId);
        $salidas = $this->reservasPorFecha($hotelId, 'salida');
        return ['limpieza' => (int) ($estados['limpieza'] ?? 0), 'salidas' => (int) $salidas['total']];
    }

    /** Calificaciones bajas (<=3) respondidas en los ultimos 90 dias (bloque reputacion). */
    private function calificacionesBajas(int $hotelId): array
    {
        $n = 0;
        $prom = null;
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) n, AVG(calificacion) prom FROM reputacion_encuestas
                 WHERE hotel_id = ? AND estado = 'respondida' AND calificacion IS NOT NULL
                   AND calificacion <= 3 AND respondida_at >= NOW() - INTERVAL 90 DAY"
            );
            $stmt->execute([$hotelId]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $n = (int) ($f['n'] ?? 0);
            $prom = $n > 0 ? number_format((float) $f['prom'], 1) : null;
        } catch (Throwable $e) {
            error_log('Copiloto: error calificaciones bajas: ' . $e->getMessage());
        }
        return ['n' => $n, 'prom' => $prom];
    }

    /** Productos en o bajo su stock minimo (bloque inventario). */
    private function inventarioBajo(int $hotelId): array
    {
        $items = [];
        $n = 0;
        try {
            $stmt = $this->pdo->prepare(
                "SELECT nombre FROM inventario_productos
                 WHERE hotel_id = ? AND activo = 1 AND stock_minimo > 0 AND stock_actual <= stock_minimo
                 ORDER BY (stock_actual / NULLIF(stock_minimo, 0)) ASC LIMIT 5"
            );
            $stmt->execute([$hotelId]);
            $items = array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'nombre');

            $c = $this->pdo->prepare("SELECT COUNT(*) FROM inventario_productos WHERE hotel_id = ? AND activo = 1 AND stock_minimo > 0 AND stock_actual <= stock_minimo");
            $c->execute([$hotelId]);
            $n = (int) $c->fetchColumn();
        } catch (Throwable $e) {
            error_log('Copiloto: error inventario bajo: ' . $e->getMessage());
        }
        return ['n' => $n, 'items' => $items];
    }

    /** Saldo pendiente por pagar a proveedores (bloque compras). */
    private function deudaProveedores(int $hotelId): array
    {
        $n = 0;
        $saldo = 0.0;
        $venc = 0;
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) n, COALESCE(SUM(saldo), 0) saldo, SUM(estado = 'vencida') venc
                 FROM cuentas_por_pagar WHERE hotel_id = ? AND estado IN ('pendiente', 'parcial', 'vencida')"
            );
            $stmt->execute([$hotelId]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $n = (int) ($f['n'] ?? 0);
            $saldo = (float) ($f['saldo'] ?? 0);
            $venc = (int) ($f['venc'] ?? 0);
        } catch (Throwable $e) {
            error_log('Copiloto: error cxp: ' . $e->getMessage());
        }
        return ['n' => $n, 'saldo' => number_format($saldo, 2), 'vencidas' => $venc];
    }

    /** Saldo pendiente por cobrar a clientes (bloque cuentas_cobrar). */
    private function porCobrar(int $hotelId): array
    {
        $n = 0;
        $saldo = 0.0;
        $venc = 0;
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) n, COALESCE(SUM(saldo), 0) saldo, SUM(estado = 'vencida') venc
                 FROM cuentas_por_cobrar WHERE hotel_id = ? AND estado IN ('pendiente', 'parcial', 'vencida')"
            );
            $stmt->execute([$hotelId]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $n = (int) ($f['n'] ?? 0);
            $saldo = (float) ($f['saldo'] ?? 0);
            $venc = (int) ($f['venc'] ?? 0);
        } catch (Throwable $e) {
            error_log('Copiloto: error cxc: ' . $e->getMessage());
        }
        return ['n' => $n, 'saldo' => number_format($saldo, 2), 'vencidas' => $venc];
    }

    /** Ultimo periodo de nomina cerrado (bloque nomina_avanzada). */
    private function nominaPeriodo(int $hotelId): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT tipo_periodo, fecha_inicio, fecha_fin, estado, trabajadores_total, neto_sugerido_total, pendiente_pago_total
                 FROM trabajador_nomina_periodos WHERE hotel_id = ? ORDER BY fecha_fin DESC, id DESC LIMIT 1"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('Copiloto: error nomina periodo: ' . $e->getMessage());
            return null;
        }
    }

    private function nombreMes(int $m): string
    {
        $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
        return $meses[$m] ?? (string) $m;
    }

    // ───────────────────────── Ayuda (FAQ deterministo) ─────────────────────────

    private function detectarFaq(string $norm, int $hotelId): ?array
    {
        // 'modulo' => null significa seccion core (siempre disponible). Con clave
        // de modulo, se responde con los pasos SOLO si el hotel lo tiene activo.

        // Preguntas por la CIFRA de un periodo ("ganancias/ingresos del mes pasado")
        // son dato, no ayuda: se dejan pasar a detectarIntent para dar el numero
        // real (evita que "cuanto vendi el mes pasado" caiga en la FAQ de Reportes).
        if (preg_match('/(mes pasado|mes anterior|este mes|mes en curso|mes actual|del mes)/', $norm)
            && preg_match('/(ganancia|gane|ingreso|vendi|venta|utilidad|factur|cuanto (hice|entro))/', $norm)) {
            return null;
        }
        // Referencia a un dia concreto ("el jueves", "ayer") + dato de dinero/desempeno
        // tambien es intent (evita que "cuanto vendi ayer" caiga en la FAQ de Reportes).
        if (preg_match('/(lunes|martes|miercoles|jueves|viernes|sabado|domingo|hoy|ayer|antier|anteayer)/', $norm)
            && preg_match('/(ganancia|gane|ingreso|vendi|venta|utilidad|cuanto|como nos|como fue|como estuvo|como me fue)/', $norm)) {
            return null;
        }
        // "quien me debe" / "me deben" es dato (monto por cobrar), no ayuda de la seccion.
        if (preg_match('/(quien(es)? me debe|me deben|cuanto me deben)/', $norm)) {
            return null;
        }

        $faqs = [
            ['clave' => 'corte_caja', 'modulo' => null, 'nombre' => 'Caja',
             'palabras' => ['como hago un corte', 'como cierro la caja', 'como hago el corte', 'cerrar caja'],
             'texto' => 'Para hacer un corte de caja: entra a **Caja**, revisa los movimientos del turno, captura el efectivo contado y confirma el cierre. El sistema calcula la diferencia contra lo esperado.',
             'enlace' => ['url' => 'caja#cop-ancla-corte', 'texto' => 'Ir a hacer el corte']],
            ['clave' => 'registrar_ingreso', 'modulo' => null, 'nombre' => 'Caja',
             'palabras' => ['registrar un ingreso', 'ingreso que no es de una reserva', 'ingreso que no es de reserva', 'ingreso manual', 'meter dinero a caja', 'como registro un ingreso', 'agregar un ingreso', 'registrar dinero', 'ingreso extra', 'entrada de dinero'],
             'texto' => 'Para registrar un ingreso que no viene de una reservacion (una venta suelta, una propina, etc.): entra a **Caja** y usa el boton **Registrar Ingreso**. Eliges la categoria, el monto y el concepto. Para una salida de dinero es el boton Registrar Gasto.',
             'enlace' => ['url' => 'caja#cop-ancla-ingreso', 'texto' => 'Registrar un ingreso']],
            ['clave' => 'registrar_gasto', 'modulo' => null, 'nombre' => 'Caja',
             'palabras' => ['registrar un gasto', 'gasto manual', 'como registro un gasto', 'sacar dinero de caja', 'pagar algo de caja', 'registrar una salida', 'salida de dinero'],
             'texto' => 'Para registrar un gasto o salida de dinero: entra a **Caja** y usa el boton **Registrar Gasto**. Eliges la categoria, el monto y el concepto.',
             'enlace' => ['url' => 'caja#cop-ancla-gasto', 'texto' => 'Registrar un gasto']],
            ['clave' => 'reservacion', 'modulo' => null, 'nombre' => 'Reservaciones',
             'palabras' => ['como hago una reservacion', 'como creo una reserva', 'nueva reservacion', 'registrar reserva', 'como agrego una reserva'],
             'texto' => 'Para una reservacion nueva: entra a **Reservaciones** y usa el boton **Nueva reservacion**; elige fechas y habitacion, captura al huesped y guarda. Desde ahi puedes hacer el check-in cuando llegue.',
             'enlace' => ['url' => 'reservaciones#cop-ancla-nueva-reserva', 'texto' => 'Crear reservacion']],
            ['clave' => 'checkin', 'modulo' => null, 'nombre' => 'Reservaciones',
             'palabras' => ['como hago un check-in', 'como hago un checkin', 'como registro la llegada', 'como doy entrada', 'como hago la entrada'],
             'texto' => 'Para el check-in: entra a **Reservaciones**, abre la reservacion del huesped que llega y usa la opcion de check-in. Ahi confirmas habitacion y datos, y se marca la habitacion como ocupada.',
             'enlace' => ['url' => 'reservaciones', 'texto' => 'Ir a Reservaciones']],
            ['clave' => 'checkout', 'modulo' => null, 'nombre' => 'Reservaciones',
             'palabras' => ['como hago un check-out', 'como hago un checkout', 'como registro la salida', 'como doy salida', 'como cierro una estancia'],
             'texto' => 'Para el check-out: entra a **Reservaciones**, abre la reservacion activa y usa la opcion de check-out. Se libera la habitacion y se cierra la cuenta del huesped.',
             'enlace' => ['url' => 'reservaciones', 'texto' => 'Ir a Reservaciones']],
            ['clave' => 'cancelar_reserva', 'modulo' => null, 'nombre' => 'Reservaciones',
             'palabras' => ['como cancelo una reserva', 'cancelar una reservacion', 'como anulo una reserva', 'como cancelo una reservacion'],
             'texto' => 'Para cancelar una reservacion: entra a **Reservaciones**, abre la reservacion y usa la opcion de cancelar. Si habia anticipo, el sistema te guia con la devolucion en Caja.',
             'enlace' => ['url' => 'reservaciones', 'texto' => 'Ir a Reservaciones']],
            ['clave' => 'reportes', 'modulo' => 'reportes', 'nombre' => 'Reportes',
             'palabras' => ['ver mis reportes', 'ver mis ganancias', 'cuanto vendi', 'cuanto he ganado', 'reporte del mes', 'mis ingresos del mes', 'como veo mis reportes', 'quiero ver mis ganancias'],
             'texto' => 'Tus reportes de ingresos, ventas y ocupacion estan en **Reportes**. Puedes filtrar por periodo y descargarlos.',
             'enlace' => ['url' => 'reportes', 'texto' => 'Ir a Reportes']],
            ['clave' => 'cupon', 'modulo' => 'promociones', 'nombre' => 'Cupones y promociones',
             'palabras' => ['como hago un cupon', 'como creo un cupon', 'crear cupon', 'codigo de descuento', 'como hago un descuento'],
             'texto' => 'Los cupones viven en **Motor de reservas > Cupones**. Crea un codigo, elige % o monto fijo, su vigencia y limite de usos. El huesped lo captura al reservar en linea.',
             'enlace' => ['url' => 'motor-reservas/cupones', 'texto' => 'Ir a Cupones']],
            ['clave' => 'extras', 'modulo' => 'upsells', 'nombre' => 'Extras y upselling',
             'palabras' => ['como vendo extras', 'como agrego extras', 'desayuno extra', 'late checkout'],
             'texto' => 'Los extras (desayuno, late checkout) se configuran en **Motor de reservas > Extras**. Defines nombre, precio y como se cobra; el huesped los agrega al reservar.',
             'enlace' => ['url' => 'motor-reservas/extras', 'texto' => 'Ir a Extras']],
            ['clave' => 'encuesta', 'modulo' => 'reputacion', 'nombre' => 'Reputacion',
             'palabras' => ['como mando una encuesta', 'encuesta de satisfaccion', 'como pido una resena', 'como pido calificacion'],
             'texto' => 'En **Reputacion** puedes generar y enviar la encuesta post-estancia a tus huespedes con checkout. Las buenas calificaciones se invitan a Google; las bajas te llegan como alerta.',
             'enlace' => ['url' => 'reputacion', 'texto' => 'Ir a Reputacion']],
            ['clave' => 'forecast', 'modulo' => 'forecast', 'nombre' => 'Forecast',
             'palabras' => ['como veo el forecast', 'proyeccion de ocupacion', 'como veo mi ocupacion futura', 'pronostico de ocupacion'],
             'texto' => 'En **Forecast** ves la proyeccion de ocupacion a 30, 60 y 90 dias, el ritmo de reservas y la comparativa con el anio pasado.',
             'enlace' => ['url' => 'forecast', 'texto' => 'Ir a Forecast']],
            ['clave' => 'lealtad', 'modulo' => 'lealtad', 'nombre' => 'Huesped frecuente',
             'palabras' => ['huesped frecuente', 'cliente frecuente', 'como premio a mis clientes', 'programa de lealtad'],
             'texto' => 'En **Huesped frecuente** ves a tus huespedes que regresan y les generas un cupon personal de agradecimiento para su siguiente reserva en linea.',
             'enlace' => ['url' => 'lealtad', 'texto' => 'Ir a Huesped frecuente']],
            ['clave' => 'huesped_nuevo', 'modulo' => null, 'nombre' => 'Huespedes',
             'palabras' => ['como registro un huesped', 'como agrego un huesped', 'como busco un huesped', 'datos de un huesped', 'alta de huesped'],
             'texto' => 'En **Huespedes** puedes buscar, registrar y editar los datos de tus huespedes (contacto, procedencia, historial). Al crear una reservacion tambien puedes capturar al huesped ahi mismo.',
             'enlace' => ['url' => 'huespedes', 'texto' => 'Ir a Huespedes']],
            ['clave' => 'habitacion_estado', 'modulo' => null, 'nombre' => 'Habitaciones',
             'palabras' => ['como pongo una habitacion en mantenimiento', 'como cambio el estado de una habitacion', 'marcar habitacion sucia', 'habitacion fuera de servicio', 'como agrego una habitacion', 'crear habitacion'],
             'texto' => 'En **Habitaciones** ves cada cuarto con su estado (disponible, ocupada, limpieza, mantenimiento) y desde su tarjeta puedes cambiarlo o editar sus datos y fotos.',
             'enlace' => ['url' => 'habitaciones', 'texto' => 'Ir a Habitaciones']],
            ['clave' => 'inventario', 'modulo' => 'inventario', 'nombre' => 'Inventario',
             'palabras' => ['como veo mi inventario', 'cuanto stock tengo', 'como registro un movimiento de inventario', 'existencias', 'productos del inventario'],
             'texto' => 'En **Inventario** ves tus productos, existencias y movimientos. Ahi registras entradas, salidas y ajustes, y el sistema te alerta cuando algo baja del minimo.',
             'enlace' => ['url' => 'inventario', 'texto' => 'Ir a Inventario']],
            ['clave' => 'tareas', 'modulo' => 'tareas', 'nombre' => 'Tareas operativas',
             'palabras' => ['como creo una tarea', 'asignar una tarea', 'tareas del personal', 'pendientes del equipo'],
             'texto' => 'En **Tareas** creas pendientes operativos y los asignas a tu personal (limpieza, mantenimiento, encargos). Cada quien ve su agenda y va marcando avance.',
             'enlace' => ['url' => 'tareas', 'texto' => 'Ir a Tareas']],
            ['clave' => 'personal', 'modulo' => 'personal', 'nombre' => 'Personal y Nomina',
             'palabras' => ['como registro a un trabajador', 'alta de empleado', 'asistencia del personal', 'como pago la nomina', 'anticipos del personal', 'prestamos al personal'],
             'texto' => 'En **Personal** llevas a tus trabajadores: datos, asistencia, anticipos y prestamos, y el pago de nomina ligado a Caja con recibos.',
             'enlace' => ['url' => 'trabajadores', 'texto' => 'Ir a Personal']],
            ['clave' => 'compras', 'modulo' => 'compras', 'nombre' => 'Compras y proveedores',
             'palabras' => ['como registro una compra', 'alta de proveedor', 'pagar a un proveedor', 'cuentas por pagar'],
             'texto' => 'En **Compras** registras proveedores y compras, y llevas las cuentas por pagar; los pagos a proveedor salen de Caja con su trazabilidad.',
             'enlace' => ['url' => 'compras', 'texto' => 'Ir a Compras']],
            ['clave' => 'facturacion', 'modulo' => 'facturacion', 'nombre' => 'Facturacion',
             'palabras' => ['como facturo', 'solicitud de factura', 'el huesped quiere factura', 'datos fiscales'],
             'texto' => 'En **Facturacion** llevas las solicitudes de factura de tus huespedes: capturas datos fiscales, marcas en proceso y completas cuando emites la factura.',
             'enlace' => ['url' => 'facturacion', 'texto' => 'Ir a Facturacion']],
            ['clave' => 'cxc', 'modulo' => 'cuentas_cobrar', 'nombre' => 'Credito a clientes (CxC)',
             'palabras' => ['cuentas por cobrar', 'credito a un cliente', 'quien me debe', 'cobrar una deuda'],
             'texto' => 'En **Cuentas por cobrar** ves los creditos a clientes y registras sus cobros a Caja, con reversion controlada si algo se capturo mal.',
             'enlace' => ['url' => 'cuentas-por-cobrar', 'texto' => 'Ir a CxC']],
            ['clave' => 'documentos', 'modulo' => 'documentos', 'nombre' => 'Centro documental',
             'palabras' => ['donde subo documentos', 'guardar un documento', 'archivos del hotel', 'centro documental'],
             'texto' => 'En **Documentos** guardas los archivos del hotel (contratos, identificaciones, evidencias) ligados a huespedes, reservaciones o personal, con descarga segura.',
             'enlace' => ['url' => 'documentos', 'texto' => 'Ir a Documentos']],
            ['clave' => 'tarifas', 'modulo' => 'tarifas_dinamicas', 'nombre' => 'Tarifas dinamicas',
             'palabras' => ['como cambio los precios', 'precios por temporada', 'tarifa de fin de semana', 'subir tarifas', 'tarifas dinamicas'],
             'texto' => 'En **Tarifas dinamicas** defines incrementos por temporada, fecha o dia de la semana; el motor y las reservaciones los aplican en automatico.',
             'enlace' => ['url' => 'configuracion/tarifas', 'texto' => 'Ir a Tarifas']],
            ['clave' => 'branding', 'modulo' => null, 'nombre' => 'Configuracion',
             'palabras' => ['como cambio el logo', 'colores del hotel', 'branding', 'datos del hotel', 'como configuro mi hotel'],
             'texto' => 'En **Configuracion** ajustas los datos del hotel, su logo y colores (que tambien visten tu pagina publica de reservas) y las opciones de cada modulo.',
             'enlace' => ['url' => 'configuracion', 'texto' => 'Ir a Configuracion']],
            ['clave' => 'usuarios', 'modulo' => null, 'nombre' => 'Usuarios',
             'palabras' => ['como creo un usuario', 'alta de usuario', 'dar acceso a alguien', 'cambiar contrasena de un usuario', 'permisos de un usuario'],
             'texto' => 'En **Usuarios** das de alta a quienes usan el sistema y les asignas su rol (recepcion, gerente, etc.). Los permisos finos se afinan en Roles si tienes ese bloque.',
             'enlace' => ['url' => 'usuarios', 'texto' => 'Ir a Usuarios']],
            ['clave' => 'checkin_digital', 'modulo' => 'checkin_digital', 'nombre' => 'Check-in digital',
             'palabras' => ['pre-registro', 'pre registro', 'link de check-in', 'checkin digital', 'check-in digital', 'que el huesped llene sus datos'],
             'texto' => 'Con **Check-in digital** le mandas al huesped un link antes de llegar: el llena sus datos y sube su identificacion, y tu haces el check-in en un minuto.',
             'enlace' => ['url' => 'checkin-digital', 'texto' => 'Ir a Check-in digital']],
            ['clave' => 'camarista', 'modulo' => 'camarista', 'nombre' => 'App de camarista',
             'palabras' => ['app de limpieza', 'camarista', 'que las camaristas vean', 'marcar habitacion limpia'],
             'texto' => 'La **App de camarista** es un tablero movil para tu personal de limpieza: ven las salidas del dia y marcan cada habitacion como limpia con un toque.',
             'enlace' => ['url' => 'camarista', 'texto' => 'Ir a Camarista']],
            ['clave' => 'canales', 'modulo' => 'canales_ical', 'nombre' => 'Canales (iCal)',
             'palabras' => ['conectar airbnb', 'conectar booking', 'sincronizar calendario', 'canales ical', 'evitar sobreventa'],
             'texto' => 'En **Canales** sincronizas tus calendarios de Airbnb/Booking via iCal para evitar dobles ventas: lo que se ocupa alla se bloquea aca y viceversa.',
             'enlace' => ['url' => 'canales', 'texto' => 'Ir a Canales']],
            ['clave' => 'whatsapp', 'modulo' => 'whatsapp', 'nombre' => 'WhatsApp',
             'palabras' => ['conectar whatsapp', 'mensajes automaticos', 'whatsapp del hotel'],
             'texto' => 'En **WhatsApp** conectas el numero del hotel para mandar confirmaciones automaticas al huesped y avisos al dueno cuando entra una reserva online.',
             'enlace' => ['url' => 'whatsapp', 'texto' => 'Ir a WhatsApp']],
            ['clave' => 'motor_activar', 'modulo' => 'motor_reservas', 'nombre' => 'Motor de reservas',
             'palabras' => ['como activo las reservas en linea', 'pagina de reservas', 'reservar en linea', 'motor de reservas', 'link para que reserven'],
             'texto' => 'En **Motor de reservas** configuras tu pagina publica: enciendes las reservas en linea, defines el anticipo, conectas tu pasarela y copias el link para compartir.',
             'enlace' => ['url' => 'motor-reservas', 'texto' => 'Ir al Motor']],
            ['clave' => 'tablero', 'modulo' => 'tablero_ejecutivo', 'nombre' => 'Tablero de direccion',
             'palabras' => ['operacion diaria', 'tablero ejecutivo', 'conciliacion financiera', 'reporte gerencial'],
             'texto' => 'En **Operacion diaria** esta el tablero de direccion: el pulso del dia, la conciliacion financiera y el reporte gerencial.',
             'enlace' => ['url' => 'operacion/diaria', 'texto' => 'Ir a Operacion diaria']],
            ['clave' => 'notificaciones', 'modulo' => 'notificaciones', 'nombre' => 'Notificaciones',
             'palabras' => ['ver mis notificaciones', 'alertas del sistema', 'donde veo las alertas'],
             'texto' => 'En **Notificaciones** se juntan las alertas del sistema (calificaciones bajas, cierres con pendientes, inventario bajo). La campanita del menu te marca las nuevas.',
             'enlace' => ['url' => 'notificaciones', 'texto' => 'Ir a Notificaciones']],
            ['clave' => 'nomina', 'modulo' => 'nomina_avanzada', 'nombre' => 'Nomina avanzada',
             'palabras' => ['como corro la nomina', 'calcular la nomina', 'nomina legal', 'nomina avanzada'],
             'texto' => 'En **Nomina** corres el calculo del periodo con las reglas configuradas, revisas la prenomina y cierras con recibos y trazabilidad a Caja.',
             'enlace' => ['url' => 'nomina', 'texto' => 'Ir a Nomina']],
        ];

        foreach ($faqs as $faq) {
            foreach ($faq['palabras'] as $p) {
                if (strpos($norm, $p) === false) {
                    continue;
                }

                // Bloque no core que el hotel no tiene: avisar, no mandar a vacio.
                if ($faq['modulo'] !== null && !$this->tieneModulo($faq['modulo'], $hotelId)) {
                    return [
                        'intent' => 'faq_inactivo:' . $faq['clave'],
                        'texto' => 'El modulo de **' . $faq['nombre'] . '** no esta activo en tu hotel, por eso no te aparece en el menu. Si te interesa activarlo, contacta a Medisoft.',
                        'enlace' => null,
                    ];
                }

                return [
                    'intent' => 'faq:' . $faq['clave'],
                    'texto' => $faq['texto'],
                    'enlace' => $faq['enlace'],
                ];
            }
        }

        return null;
    }

    // ───────────────────────── IA (fallback opcional) ─────────────────────────

    /**
     * Saneador PURO del historial de conversacion que manda el widget
     * (JSON [{r:'u'|'a', t:'...'}, ...]). El historial viene del cliente y
     * solo da CONTEXTO conversacional a la IA; las cifras validas siguen
     * siendo las del snapshot del servidor. Garantiza el contrato del API:
     * roles alternados (consecutivos se fusionan), empieza en user, termina
     * en assistant (la pregunta nueva va aparte), maximo 6 turnos de hasta
     * 600 caracteres.
     */
    public static function sanearHistorial($crudo): array
    {
        if (is_string($crudo)) {
            $crudo = $crudo === '' ? [] : json_decode(mb_substr($crudo, 0, 8000), true);
        }
        if (!is_array($crudo)) {
            return [];
        }

        $limpio = [];
        foreach ($crudo as $e) {
            if (!is_array($e)) {
                continue;
            }
            $r = (string) ($e['r'] ?? '');
            $rol = $r === 'a' ? 'assistant' : ($r === 'u' ? 'user' : null);
            $texto = trim((string) ($e['t'] ?? ''));
            if ($rol === null || $texto === '') {
                continue;
            }
            $texto = mb_substr($texto, 0, 600);
            $ultimo = count($limpio) - 1;
            if ($ultimo >= 0 && $limpio[$ultimo]['role'] === $rol) {
                $limpio[$ultimo]['content'] = mb_substr($limpio[$ultimo]['content'] . "\n" . $texto, 0, 900);
                continue;
            }
            $limpio[] = ['role' => $rol, 'content' => $texto];
        }

        $limpio = array_slice($limpio, -6);
        while (!empty($limpio) && $limpio[0]['role'] !== 'user') {
            array_shift($limpio);
        }
        while (!empty($limpio) && $limpio[count($limpio) - 1]['role'] !== 'assistant') {
            array_pop($limpio);
        }

        return array_values($limpio);
    }

    private function responderConIa(int $hotelId, string $pregunta, array $historial = []): array
    {
        // White-label: el asistente se presenta con el nombre que el hotel
        // configuro, no con la marca de la plataforma.
        $sistema = 'Te llamas ' . self::nombreAsistente($hotelId) . ' y eres el asistente dentro del sistema de gestion de un hotel '
            . 'pequeno o mediano en Mexico. Respondes SOLO con la informacion que se te da (datos en vivo del hotel '
            . 'y la guia de uso). Reglas estrictas:'
            . "\n- Nunca inventes cifras: si un numero no esta en los datos, di que no lo tienes a la mano y sugiere donde verlo."
            . "\n- Si preguntan como hacer algo, da los pasos en 2-4 lineas y menciona la seccion del sistema."
            . "\n- Eres de solo lectura: nunca afirmes haber hecho un cambio; a lo mucho sugieres la accion."
            . "\n- Escribe en espanol claro y breve, sin jerga ni tecnicismos, sin mencionar que usas un modelo externo."
            . "\n- Si hay mensajes previos, usalos para entender a que se refiere el usuario (\"¿y eso por que?\", \"¿y manana?\"),"
            . ' pero las cifras validas son SOLO las de los datos en vivo actuales: un numero del historial puede estar viejo.'
            . "\n- Montos con formato \$1,234.56.";

        $usuario = "Datos en vivo del hotel (calculados por el servidor, son la unica fuente de cifras):\n"
            . $this->snapshotTexto($hotelId)
            . "\n\nSecciones que ESTE hotel tiene activas (no menciones ni recomiendes ninguna que no este en esta lista):\n" . $this->ayudaConocimiento($hotelId)
            . "\n\nPregunta del usuario del hotel:\n" . $pregunta;

        return $this->llamarClaude($sistema, $usuario, $historial);
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

        $gAct = $this->gananciasMesActual($hotelId);
        if ($gAct['hay']) {
            $lineas[] = "- Este mes en curso ({$gAct['mes']} {$gAct['anio']}, al dia de hoy): ingresos \${$gAct['ingresos']}, gastos \${$gAct['gastos']}, ganancia neta \${$gAct['neto']}";
        }
        $g = $this->gananciasMesPasado($hotelId);
        if ($g['hay']) {
            $lineas[] = "- Mes pasado ({$g['mes']} {$g['anio']}): ingresos \${$g['ingresos']}, gastos \${$g['gastos']}, ganancia neta \${$g['neto']}";
        }

        $manana = $this->reservasPorFecha($hotelId, 'entrada', 1);
        $lineas[] = "- Llegadas de manana: {$manana['total']}";

        $estados = $this->habitacionesPorEstado($hotelId);
        if (!empty($estados)) {
            $partes = [];
            foreach ($estados as $estado => $n) {
                $partes[] = "{$n} {$estado}";
            }
            $lineas[] = "- Habitaciones por estado: " . implode(', ', $partes);
        }

        if ($this->tieneModulo('motor_reservas', $hotelId)) {
            $m = $this->motorPorConciliar($hotelId);
            $lineas[] = "- Pagos online por conciliar: {$m['n']} (\${$m['monto']})";
        }

        if ($this->tieneModulo('reputacion', $hotelId)) {
            $cal = $this->calificacionPromedio($hotelId);
            $lineas[] = $cal['respondidas'] > 0
                ? "- Calificacion promedio (90 dias): {$cal['promedio']}/5 con {$cal['respondidas']} encuesta(s)"
                : "- Calificacion promedio: sin encuestas respondidas aun";
        }

        if ($this->tieneModulo('promociones', $hotelId)) {
            $cu = $this->cuponesActivos($hotelId);
            $lineas[] = "- Cupones activos: {$cu['n']}";
        }

        return implode("\n", $lineas);
    }

    private function ayudaConocimiento(int $hotelId): string
    {
        // Core siempre presente; el resto solo si el hotel tiene el bloque.
        $lineas = [
            "- Caja: corte de caja, y registrar ingresos o gastos manuales que NO vienen de una reserva (botones Registrar Ingreso / Registrar Gasto).",
            "- Reservaciones: reserva nueva, check-in, check-out y cancelaciones.",
            "- Habitaciones y su estado: seccion Habitaciones.",
        ];

        $opcionales = [
            'huespedes' => "- Huespedes (buscar, registrar, historial): seccion Huespedes.",
            'reportes' => "- Reportes de ingresos, ventas y ocupacion (filtrables y descargables): seccion Reportes.",
            'motor_reservas' => "- Reservas en linea con pago de anticipo: seccion Motor de reservas.",
            'promociones' => "- Cupones de descuento del motor: Motor de reservas > Cupones.",
            'upsells' => "- Extras del motor (desayuno, late checkout): Motor de reservas > Extras.",
            'reputacion' => "- Encuestas y resenas: seccion Reputacion.",
            'lealtad' => "- Huespedes frecuentes y su cupon: seccion Huesped frecuente.",
            'forecast' => "- Proyeccion de ocupacion 30/60/90 dias: seccion Forecast.",
            'night_audit' => "- Cierre nocturno (no-shows, checkouts vencidos): seccion Night audit.",
            'auditoria' => "- Bitacora de quien hizo que: seccion Bitacora (solo gerencia).",
            'ia_ejecutiva' => "- Resumen gerencial diario narrado: seccion Asesor inteligente.",
            'inventario' => "- Inventario: productos, existencias y movimientos, con alertas de stock bajo.",
            'tareas' => "- Tareas operativas asignables al personal: seccion Tareas.",
            'personal' => "- Personal: trabajadores, asistencia, anticipos/prestamos y pago de nomina: seccion Personal.",
            'nomina_avanzada' => "- Nomina avanzada (calculo del periodo, prenomina y recibos): seccion Nomina.",
            'compras' => "- Compras, proveedores y cuentas por pagar: seccion Compras.",
            'facturacion' => "- Solicitudes de factura de huespedes: seccion Facturacion.",
            'cuentas_cobrar' => "- Credito a clientes y sus cobros: seccion Cuentas por cobrar.",
            'documentos' => "- Archivos del hotel ligados a huespedes/reservas/personal: seccion Documentos.",
            'tarifas_dinamicas' => "- Precios por temporada o dia de la semana: Configuracion > Tarifas.",
            'checkin_digital' => "- Pre-registro del huesped antes de llegar (link con token): seccion Check-in digital.",
            'camarista' => "- Tablero movil de limpieza: seccion Camarista.",
            'canales_ical' => "- Sincronizacion de calendarios Airbnb/Booking: seccion Canales.",
            'whatsapp' => "- Mensajes automaticos de WhatsApp: seccion WhatsApp.",
            'tablero_ejecutivo' => "- Tablero de direccion y conciliacion financiera: seccion Operacion diaria.",
            'notificaciones' => "- Alertas del sistema: seccion Notificaciones (campanita del menu).",
        ];

        foreach ($opcionales as $modulo => $linea) {
            if ($this->tieneModulo($modulo, $hotelId)) {
                $lineas[] = $linea;
            }
        }

        return implode("\n", $lineas);
    }

    /** Devuelve ['success', 'texto', 'tokens_entrada', 'tokens_salida', 'message']. */
    private function llamarClaude(string $sistema, string $usuario, array $mensajesPrevios = []): array
    {
        // $mensajesPrevios ya viene saneado (sanearHistorial): alternado,
        // empieza en user y termina en assistant; el turno final con el
        // snapshot fresco siempre es esta pregunta.
        $payload = json_encode([
            'model' => self::MODELO,
            'max_tokens' => self::MAX_TOKENS,
            'thinking' => ['type' => 'adaptive'],
            'system' => $sistema,
            'messages' => array_merge($mensajesPrevios, [['role' => 'user', 'content' => $usuario]]),
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
        return "No estoy seguro de esa. Prueba con algo como:\n"
            . "• \"dame el resumen del dia\"\n"
            . "• \"¿cuantas habitaciones libres tengo?\" o \"¿quien llega hoy/manana?\"\n"
            . "• \"¿tiene reserva Garcia?\" o \"¿en que habitacion esta Lopez?\"\n"
            . "• \"¿como pinta la semana?\" o \"¿quien esta hospedado?\"\n"
            . "• \"¿como voy de caja?\" o \"¿hay checkouts vencidos?\"\n"
            . "• \"¿cuales fueron las ganancias del mes pasado?\" o \"¿voy mejor o peor que el mes pasado?\"\n"
            . "• \"¿como nos fue el jueves?\" o \"¿cuanto vendi ayer?\"\n"
            . "• o pideme una accion: \"registra un gasto de 450 de plomeria\", \"bloquea la 204 por pintura\", \"desbloquea la 204\", \"programa limpieza de la 204\", \"crea un cupon de 10% para agosto\", \"pagale 500 al proveedor Garcia\", \"reservale la 204 a Juan del 20 al 22\"\n"
            . "• o preguntame como hacer algo: un corte, un ingreso, un check-in, un cupon...";
    }

    private function tieneModulo(string $clave, int $hotelId): bool
    {
        return function_exists('hotel_has_module') && hotel_has_module($clave, $hotelId);
    }

    /** El intent del Guardian exige el mismo permiso que su vista (guardian.view). */
    private function puedeVerGuardian(): bool
    {
        return function_exists('can') && can('guardian.view');
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
