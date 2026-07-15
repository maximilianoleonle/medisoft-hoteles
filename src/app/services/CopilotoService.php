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
        //    pregunta de dato ("como voy de caja" -> saldo). Consciente de modulos:
        //    si el bloque no esta activo, avisa en vez de mandar a una pantalla vacia.
        $faq = $this->detectarFaq($norm, $hotelId);
        if ($faq !== null) {
            $this->registrar($hotelId, $usuarioId, $pregunta, 'reglas', $faq['intent'], 0, 0);
            return ['success' => true, 'texto' => $faq['texto'], 'fuente' => 'reglas', 'enlace' => $faq['enlace']];
        }

        // 2) Reglas de datos (deterministas).
        $intent = $this->detectarIntent($norm, $hotelId);
        if ($intent !== null) {
            $r = $this->responderIntent($hotelId, $intent, $norm);
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

        if ($tiene(['resumen del dia', 'resumen de hoy', 'como va el dia', 'como vamos hoy', 'como esta el dia', 'briefing', 'dame el resumen'])) {
            return 'resumen_dia';
        }
        if ($tiene(['manana llega', 'llegan manana', 'quien llega manana', 'llegadas de manana', 'entradas de manana', 'reservas de manana'])) {
            return 'llegadas_manana';
        }
        if (($tiene(['semana']) && $tiene(['ganancia', 'ingreso', 'vendi', 'gane', 'venta', 'utilidad']))
            || $tiene(['como va la semana', 'como vamos esta semana', 'como va esta semana', 'que tal la semana'])) {
            return 'ganancias_semana';
        }
        if ($tiene(['esta semana', 'proximos dias', 'fin de semana', 'ocupacion de la semana', 'como pinta la semana', 'proximos 7'])) {
            return 'ocupacion_semana';
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

            case 'resumen_dia':
                return $this->resumenDelDia($hotelId);

            case 'llegadas_manana':
                $lm = $this->reservasPorFecha($hotelId, 'entrada', 1);
                return [
                    'texto' => $lm['total'] === 0
                        ? 'Manana no tienes llegadas programadas.'
                        : "Manana llegan **{$lm['total']} reservacion(es)**" . ($lm['nombres'] !== '' ? ': ' . $lm['nombres'] : '') . '.',
                    'enlace' => ['url' => 'reservaciones', 'texto' => 'Ver reservaciones'],
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
                return ['texto' => $texto, 'enlace' => $enlace];

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
                return [
                    'texto' => 'Asi estan tus habitaciones ahorita: **' . implode(', ', $partes) . '**.',
                    'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones'],
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
                return [
                    'texto' => "Comparando los primeros {$cmp['dia']} dias del mes: en {$cmp['mesActual']} llevas **\${$cmp['netoActual']}** de ganancia neta, "
                        . "contra **\${$cmp['netoPasado']}** en el mismo tramo de {$cmp['mesPasado']}.\n{$veredicto}",
                    'enlace' => $this->tieneModulo('reportes', $hotelId) ? ['url' => 'reportes', 'texto' => 'Ver reportes'] : ['url' => 'caja', 'texto' => 'Ir a Caja'],
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
                return [
                    'texto' => "Ingresos de {$pm['mes']} por metodo: **efectivo \${$pm['efectivo']}**, **tarjeta \${$pm['tarjeta']}**, transferencia \${$pm['transferencia']}.",
                    'enlace' => ['url' => 'caja', 'texto' => 'Ir a Caja'],
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
                return ['texto' => 'Para limpieza: ' . implode(' y ', $partesLh) . '.', 'enlace' => ['url' => 'habitaciones', 'texto' => 'Ver habitaciones']];

            case 'calificaciones_bajas':
                $cb = $this->calificacionesBajas($hotelId);
                return [
                    'texto' => $cb['n'] === 0
                        ? 'No tienes calificaciones bajas (3 o menos) en los ultimos 90 dias. 👏'
                        : "Tienes **{$cb['n']} calificacion(es) baja(s)** (3 o menos) en los ultimos 90 dias, promedio {$cb['prom']}/5. Vale la pena revisarlas.",
                    'enlace' => ['url' => 'reputacion', 'texto' => 'Ver reputacion'],
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
                return [
                    'texto' => "Tienes **{$ib['n']} producto(s) por agotarse** (en o bajo su minimo): {$muestraIb}.",
                    'enlace' => ['url' => 'inventario', 'texto' => 'Ver inventario'],
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

    /** Mini-briefing del dia: ocupacion + llegadas/salidas + pendientes + caja. */
    private function resumenDelDia(int $hotelId): array
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

        if ($this->tieneModulo('motor_reservas', $hotelId)) {
            $m = $this->motorPorConciliar($hotelId);
            if ($m['n'] > 0) {
                $lineas[] = "• Motor: **{$m['n']} pago(s) online por conciliar** (\${$m['monto']}).";
            }
        }

        return ['texto' => implode("\n", $lineas), 'enlace' => ['url' => 'dashboard', 'texto' => 'Ir al dashboard']];
    }

    /** Ocupacion promedio de los proximos 7 dias y el mejor dia. */
    private function ocupacionProximos7(int $hotelId): ?array
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
            'mes' => $this->nombreMes((int) date('n')),
        ];
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

    private function responderConIa(int $hotelId, string $pregunta): array
    {
        $sistema = 'Eres el Copiloto de Medisoft Hoteles, un asistente dentro del sistema de gestion de un hotel '
            . 'pequeno o mediano en Mexico. Respondes SOLO con la informacion que se te da (datos en vivo del hotel '
            . 'y la guia de uso). Reglas estrictas:'
            . "\n- Nunca inventes cifras: si un numero no esta en los datos, di que no lo tienes a la mano y sugiere donde verlo."
            . "\n- Si preguntan como hacer algo, da los pasos en 2-4 lineas y menciona la seccion del sistema."
            . "\n- Eres de solo lectura: nunca afirmes haber hecho un cambio; a lo mucho sugieres la accion."
            . "\n- Escribe en espanol claro y breve, sin jerga ni tecnicismos, sin mencionar que usas un modelo externo."
            . "\n- Montos con formato \$1,234.56.";

        $usuario = "Datos en vivo del hotel (calculados por el servidor, son la unica fuente de cifras):\n"
            . $this->snapshotTexto($hotelId)
            . "\n\nSecciones que ESTE hotel tiene activas (no menciones ni recomiendes ninguna que no este en esta lista):\n" . $this->ayudaConocimiento($hotelId)
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
        return "No estoy seguro de esa. Prueba con algo como:\n"
            . "• \"dame el resumen del dia\"\n"
            . "• \"¿cuantas habitaciones libres tengo?\" o \"¿quien llega hoy/manana?\"\n"
            . "• \"¿como pinta la semana?\" o \"¿quien esta hospedado?\"\n"
            . "• \"¿como voy de caja?\" o \"¿hay checkouts vencidos?\"\n"
            . "• \"¿cuales fueron las ganancias del mes pasado?\" o \"¿voy mejor o peor que el mes pasado?\"\n"
            . "• \"¿como nos fue el jueves?\" o \"¿cuanto vendi ayer?\"\n"
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
