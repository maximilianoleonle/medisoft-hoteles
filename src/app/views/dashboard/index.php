<?php
/**
 * Vista del Dashboard Principal - sistema hotelero operativo.
 */

$stats = $stats ?? [];
$caja_info = $caja_info ?? null;
$corte_actual = $corte_actual ?? null;
$reservaciones_hoy = $reservaciones_hoy ?? [];
$proximas_llegadas = $proximas_llegadas ?? [];
$proximas_salidas = $proximas_salidas ?? [];
$graficos = $graficos ?? [];
$notificaciones_resumen = $notificaciones_resumen ?? [];
$notificaciones_recientes = $notificaciones_recientes ?? [];

if (!function_exists('dashboard_safe')) {
    function dashboard_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('dashboard_format_date')) {
    function dashboard_format_date($value, $format = 'd/m/Y') {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return '-';
        }

        if ($format === 'l, d \d\e F') {
            $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
            $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $dia_semana = $dias[(int)date('w', $timestamp)];
            $dia = date('d', $timestamp);
            $mes = $meses[(int)date('n', $timestamp) - 1];

            return ucfirst($dia_semana) . ', ' . $dia . ' de ' . $mes;
        }

        if (function_exists('format_date')) {
            return format_date($value, $format);
        }

        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('dashboard_upper')) {
    function dashboard_upper($value) {
        $value = (string)$value;
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}

if (!function_exists('dashboard_initials')) {
    function dashboard_initials($name) {
        $parts = preg_split('/\s+/', trim((string)$name));
        $letters = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
            if (strlen($letters) >= 2) {
                break;
            }
        }

        $letters = $letters !== '' ? $letters : 'M';
        return function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
    }
}

if (!function_exists('dashboard_percent_text')) {
    function dashboard_percent_text($value) {
        $value = (float)$value;
        return rtrim(rtrim(number_format($value, 1), '0'), '.') . '%';
    }
}

if (!function_exists('dashboard_room_count_text')) {
    function dashboard_room_count_text($value, $short = false) {
        $count = max(0, (int)$value);
        if ($short) {
            return $count . ' hab.';
        }

        return $count . ' ' . ($count === 1 ? 'habitacion' : 'habitaciones');
    }
}

if (!function_exists('dashboard_short_date')) {
    function dashboard_short_date($value) {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return '-';
        }

        $meses_cortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return date('d', $timestamp) . ' ' . $meses_cortos[(int)date('n', $timestamp) - 1];
    }
}

if (!function_exists('dashboard_notif_label')) {
    function dashboard_notif_label($value) {
        $labels = [
            'nueva' => 'Pendiente',
            'leida' => 'Pendiente',
            'resuelta' => 'Atendida',
            'descartada' => 'Historial',
            'info' => 'Info',
            'media' => 'Media',
            'alta' => 'Alta',
            'critica' => 'Critica',
            'caja' => 'Caja',
            'habitaciones' => 'Habitaciones',
            'facturacion' => 'Facturacion',
            'inventario' => 'Inventario',
            'reservaciones' => 'Reservaciones',
            'sistema' => 'Sistema',
        ];

        $key = (string)($value ?? '');
        return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}

if (!function_exists('dashboard_notif_icon')) {
    function dashboard_notif_icon($modulo) {
        $icons = [
            'caja' => 'fa-wallet',
            'habitaciones' => 'fa-bed',
            'facturacion' => 'fa-file-invoice',
            'inventario' => 'fa-boxes-stacked',
            'reservaciones' => 'fa-calendar-check',
        ];

        return $icons[(string)($modulo ?? '')] ?? 'fa-bell';
    }
}

if (!function_exists('dashboard_parking_catalog')) {
    function dashboard_parking_catalog() {
        $catalog = [];
        foreach (dashboard_parking_catalog_rows() as $parkingCode => $parkingRow) {
            $parkingLabel = trim((string)($parkingRow['label'] ?? ''));
            if ($parkingLabel !== '') {
                $catalog[(string)$parkingCode] = $parkingLabel;
            }
        }

        return !empty($catalog) ? $catalog : ['coches' => 'Coches'];
    }
}

if (!function_exists('dashboard_parking_capacity')) {
    function dashboard_parking_capacity($value) {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null || !is_numeric($value)) {
            return 0;
        }

        return max(0, min(999, (int)$value));
    }
}

if (!function_exists('dashboard_parking_catalog_rows')) {
    function dashboard_parking_catalog_rows() {
        $catalog = [];
        if (function_exists('hotel_general_catalog_parking_rows')) {
            foreach (hotel_general_catalog_parking_rows(null, false) as $parkingRow) {
                $parkingCode = trim((string)($parkingRow['codigo'] ?? ''));
                $parkingLabel = trim((string)($parkingRow['label'] ?? ''));
                if ($parkingCode !== '' && $parkingLabel !== '') {
                    $catalog[$parkingCode] = [
                        'codigo' => $parkingCode,
                        'label' => $parkingLabel,
                        'cupo' => dashboard_parking_capacity($parkingRow['cupo'] ?? 0),
                    ];
                }
            }
        }

        return !empty($catalog) ? $catalog : [
            'coches' => [
                'codigo' => 'coches',
                'label' => 'Coches',
                'cupo' => 30,
            ],
        ];
    }
}

if (!function_exists('dashboard_parking_total_capacity')) {
    function dashboard_parking_total_capacity(array $catalogRows) {
        $total = 0;
        foreach ($catalogRows as $parkingRow) {
            $total += dashboard_parking_capacity($parkingRow['cupo'] ?? 0);
        }

        return $total;
    }
}

if (!function_exists('dashboard_parking_default_code')) {
    function dashboard_parking_default_code(array $catalog) {
        foreach ($catalog as $code => $label) {
            if (trim((string)$code) !== '') {
                return (string)$code;
            }
        }

        return 'coches';
    }
}

if (!function_exists('dashboard_parking_vehicle_name')) {
    function dashboard_parking_vehicle_name(array $vehiculo) {
        $parts = array_filter([
            trim((string)($vehiculo['marca'] ?? '')),
            trim((string)($vehiculo['modelo'] ?? '')),
        ], 'strlen');

        $name = trim(implode(' ', $parts));
        return $name !== '' ? $name : 'Vehiculo registrado';
    }
}

if (!function_exists('dashboard_parking_datetime_label')) {
    function dashboard_parking_datetime_label($fecha, $hora = null) {
        $fechaLabel = dashboard_format_date($fecha, 'd/m');
        $hora = trim((string)$hora);
        if ($hora !== '') {
            $time = strtotime($hora);
            if ($time) {
                return $fechaLabel . ' ' . date('H:i', $time);
            }
        }

        return $fechaLabel;
    }
}

if (!function_exists('get_estado_estacionamiento_dashboard')) {
    function get_estado_estacionamiento_dashboard($limite = null) {
        $catalogRows = dashboard_parking_catalog_rows();
        $catalog = dashboard_parking_catalog();
        $limiteConfigurado = dashboard_parking_total_capacity($catalogRows);
        if ($limiteConfigurado <= 0 && $limite !== null) {
            $limiteConfigurado = max(0, (int)$limite);
        }

        $defaultParking = dashboard_parking_default_code($catalog);
        $resumen = [];
        foreach ($catalogRows as $parkingCode => $parkingRow) {
            $parkingLabel = (string)($parkingRow['label'] ?? $parkingCode);
            $parkingCapacity = dashboard_parking_capacity($parkingRow['cupo'] ?? 0);
            $resumen[$parkingCode] = [
                'codigo' => (string)$parkingCode,
                'label' => (string)$parkingLabel,
                'cupo' => $parkingCapacity,
                'ocupados' => 0,
                'apartados' => 0,
                'registrados' => 0,
                'total' => 0,
                'disponibles' => $parkingCapacity,
                'sobrecupo' => 0,
            ];
        }

        $finalizarResumen = function (array $items) {
            foreach ($items as $code => $item) {
                $capacity = dashboard_parking_capacity($item['cupo'] ?? 0);
                $committed = (int)($item['total'] ?? 0);
                $items[$code]['cupo'] = $capacity;
                $items[$code]['disponibles'] = $capacity > 0 ? max(0, $capacity - $committed) : 0;
                $items[$code]['sobrecupo'] = $capacity > 0
                    ? max(0, $committed - $capacity)
                    : ($committed > 0 ? $committed : 0);
            }

            return $items;
        };

        $emptyResumen = $finalizarResumen($resumen);
        $empty = [
            'limite' => $limiteConfigurado,
            'resumen' => array_values($emptyResumen),
            'vehiculos' => [],
            'ocupados' => 0,
            'apartados' => 0,
            'registrados' => 0,
            'total_vehiculos' => 0,
            'usados' => 0,
        ];

        try {
            $db = Database::getInstance();
            $hotel_id = obtenerHotelIdActualCompat();
            if ($hotel_id <= 0) {
                return $empty;
            }

            $stmtVehiculos = $db->query("
                SELECT
                    hv.id,
                    hv.huesped_id,
                    hv.estacionamiento,
                    hv.marca,
                    hv.modelo,
                    hv.color,
                    hv.placas,
                    h.nombre_completo AS huesped,
                    h.telefono
                FROM huesped_vehiculos hv
                INNER JOIN huespedes h
                    ON h.id = hv.huesped_id
                   AND h.hotel_id = hv.hotel_id
                WHERE hv.hotel_id = ?
                  AND hv.activo = 1
                ORDER BY h.nombre_completo ASC, hv.created_at DESC, hv.id DESC
            ", [$hotel_id]);

            $vehiculos = $stmtVehiculos ? ($stmtVehiculos->fetchAll() ?: []) : [];
            if (empty($vehiculos)) {
                return $empty;
            }

            $huespedIds = [];
            foreach ($vehiculos as $vehiculo) {
                $huespedId = (int)($vehiculo['huesped_id'] ?? 0);
                if ($huespedId > 0) {
                    $huespedIds[$huespedId] = $huespedId;
                }
            }

            $reservasPorHuesped = [];
            if (!empty($huespedIds)) {
                $placeholders = implode(',', array_fill(0, count($huespedIds), '?'));
                $params = array_merge([$hotel_id], array_values($huespedIds));
                $stmtReservas = $db->query("
                    SELECT
                        r.id,
                        r.huesped_id,
                        r.estado,
                        r.fecha_entrada,
                        r.fecha_salida,
                        r.hora_llegada_estimada,
                        r.hora_entrada,
                        GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones
                    FROM reservaciones r
                    LEFT JOIN reservacion_habitaciones rh
                        ON rh.reservacion_id = r.id
                       AND rh.hotel_id = r.hotel_id
                    LEFT JOIN habitaciones hab
                        ON hab.id = rh.habitacion_id
                       AND hab.hotel_id = r.hotel_id
                    WHERE r.hotel_id = ?
                      AND r.huesped_id IN ($placeholders)
                      AND r.estado IN ('checked_in', 'confirmada', 'reservada')
                      AND DATE(r.fecha_entrada) = CURDATE()
                      AND DATE(r.fecha_salida) >= CURDATE()
                    GROUP BY
                        r.id,
                        r.huesped_id,
                        r.estado,
                        r.fecha_entrada,
                        r.fecha_salida,
                        r.hora_llegada_estimada,
                        r.hora_entrada
                    ORDER BY
                        CASE
                            WHEN r.estado = 'checked_in'
                                 AND DATE(r.fecha_entrada) <= CURDATE()
                                 AND DATE(r.fecha_salida) >= CURDATE()
                            THEN 0
                            WHEN r.estado IN ('confirmada', 'reservada') THEN 1
                            ELSE 2
                        END,
                        DATE(r.fecha_entrada) ASC,
                        COALESCE(r.hora_llegada_estimada, '23:59:59') ASC,
                        r.id ASC
                ", $params);

                $reservas = $stmtReservas ? ($stmtReservas->fetchAll() ?: []) : [];
                $today = date('Y-m-d');
                foreach ($reservas as $reserva) {
                    $huespedId = (int)($reserva['huesped_id'] ?? 0);
                    if ($huespedId <= 0 || isset($reservasPorHuesped[$huespedId])) {
                        continue;
                    }

                    $estado = (string)($reserva['estado'] ?? '');
                    $fechaEntrada = (string)($reserva['fecha_entrada'] ?? '');
                    $fechaSalida = (string)($reserva['fecha_salida'] ?? '');
                    $tipo = null;

                    if ($estado === 'checked_in' && $fechaEntrada === $today && $fechaSalida >= $today) {
                        $tipo = 'ocupado';
                    } elseif (in_array($estado, ['confirmada', 'reservada'], true) && $fechaEntrada === $today && $fechaSalida >= $today) {
                        $tipo = 'apartado';
                    }

                    if ($tipo !== null) {
                        $reserva['tipo_estacionamiento'] = $tipo;
                        $reservasPorHuesped[$huespedId] = $reserva;
                    }
                }
            }

            $lista = [];
            $totales = [
                'ocupados' => 0,
                'apartados' => 0,
                'registrados' => 0,
                'total_vehiculos' => count($vehiculos),
            ];

            foreach ($vehiculos as $vehiculo) {
                $parkingCode = trim((string)($vehiculo['estacionamiento'] ?? ''));
                $parkingCode = $parkingCode !== '' ? $parkingCode : $defaultParking;
                if (!isset($resumen[$parkingCode])) {
                    $resumen[$parkingCode] = [
                        'codigo' => $parkingCode,
                        'label' => ucwords(str_replace(['_', '-'], ' ', $parkingCode)),
                        'cupo' => 0,
                        'ocupados' => 0,
                        'apartados' => 0,
                        'registrados' => 0,
                        'total' => 0,
                        'disponibles' => 0,
                        'sobrecupo' => 0,
                    ];
                }

                $huespedId = (int)($vehiculo['huesped_id'] ?? 0);
                $reserva = $reservasPorHuesped[$huespedId] ?? null;
                $status = 'registrado';
                $statusLabel = 'Registrado';
                $statusClass = 'registered';
                $statusIcon = 'fa-id-card';
                $statusMeta = 'Sin reservacion activa o futura';
                $priority = 2;
                $reservacionId = 0;

                if ($reserva) {
                    $reservacionId = (int)($reserva['id'] ?? 0);
                    if (($reserva['tipo_estacionamiento'] ?? '') === 'ocupado') {
                        $status = 'ocupado';
                        $statusLabel = 'Ocupado';
                        $statusClass = 'occupied';
                        $statusIcon = 'fa-car-side';
                        $statusMeta = 'Hospedado ahora';
                        $priority = 0;
                        $totales['ocupados']++;
                        $resumen[$parkingCode]['ocupados']++;
                    } else {
                        $status = 'apartado';
                        $statusLabel = 'Apartado';
                        $statusClass = 'reserved';
                        $statusIcon = 'fa-calendar-check';
                        $statusMeta = 'Llegada ' . dashboard_parking_datetime_label($reserva['fecha_entrada'] ?? null, $reserva['hora_llegada_estimada'] ?? null);
                        $priority = 1;
                        $totales['apartados']++;
                        $resumen[$parkingCode]['apartados']++;
                    }
                    $resumen[$parkingCode]['total']++;
                } else {
                    $totales['registrados']++;
                    $resumen[$parkingCode]['registrados']++;
                    continue;
                }

                $resumen[$parkingCode]['registrados']++;
                $vehiculoNombre = dashboard_parking_vehicle_name($vehiculo);
                $placas = trim((string)($vehiculo['placas'] ?? ''));
                $habitaciones = $reserva ? trim((string)($reserva['habitaciones'] ?? '')) : '';

                $lista[] = [
                    'id' => (int)($vehiculo['id'] ?? 0),
                    'huesped_id' => $huespedId,
                    'reservacion_id' => $reservacionId,
                    'huesped' => (string)($vehiculo['huesped'] ?? ''),
                    'vehiculo' => $vehiculoNombre,
                    'placas' => $placas,
                    'color' => (string)($vehiculo['color'] ?? ''),
                    'parking_codigo' => $parkingCode,
                    'parking_label' => (string)($resumen[$parkingCode]['label'] ?? $parkingCode),
                    'habitaciones' => $habitaciones,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                    'status_icon' => $statusIcon,
                    'status_meta' => $statusMeta,
                    'priority' => $priority,
                    'fecha_entrada' => $reserva['fecha_entrada'] ?? null,
                ];
            }

            usort($lista, function ($a, $b) {
                $priorityCmp = ((int)($a['priority'] ?? 9)) <=> ((int)($b['priority'] ?? 9));
                if ($priorityCmp !== 0) {
                    return $priorityCmp;
                }

                return strcmp((string)($a['fecha_entrada'] ?? '9999-12-31'), (string)($b['fecha_entrada'] ?? '9999-12-31'));
            });

            $resumen = $finalizarResumen($resumen);

            return [
                'limite' => $limiteConfigurado,
                'resumen' => array_values($resumen),
                'vehiculos' => $lista,
                'ocupados' => $totales['ocupados'],
                'apartados' => $totales['apartados'],
                'registrados' => $totales['registrados'],
                'total_vehiculos' => $totales['total_vehiculos'],
                'usados' => $totales['ocupados'] + $totales['apartados'],
            ];
        } catch (Exception $e) {
            error_log('ERROR estado estacionamiento dashboard: ' . $e->getMessage());
            return $empty;
        }
    }
}

$estado_estacionamiento_dashboard = get_estado_estacionamiento_dashboard();
$lista_vehiculos_estacionamiento = $estado_estacionamiento_dashboard['vehiculos'] ?? [];
$resumen_estacionamientos_dashboard = $estado_estacionamiento_dashboard['resumen'] ?? [];
$limite_estacionamiento = (int)($estado_estacionamiento_dashboard['limite'] ?? 0);
$vehiculos_ocupados_fisicos = (int)($estado_estacionamiento_dashboard['ocupados'] ?? 0);
$vehiculos_apartados_reserva = (int)($estado_estacionamiento_dashboard['apartados'] ?? 0);
$vehiculos_registrados_total = (int)($estado_estacionamiento_dashboard['total_vehiculos'] ?? 0);
$vehiculos_estacionados = (int)($estado_estacionamiento_dashboard['usados'] ?? 0);
$estacionamiento_tiene_cupo = $limite_estacionamiento > 0;
$pct_estacionamiento = $estacionamiento_tiene_cupo
    ? min(100, max(0, round(($vehiculos_estacionados / $limite_estacionamiento) * 100)))
    : ($vehiculos_estacionados > 0 ? 100 : 0);
$espacios_disp = $estacionamiento_tiene_cupo ? max(0, $limite_estacionamiento - $vehiculos_estacionados) : 0;
$espacios_excedidos = $estacionamiento_tiene_cupo
    ? max(0, $vehiculos_estacionados - $limite_estacionamiento)
    : ($vehiculos_estacionados > 0 ? $vehiculos_estacionados : 0);
$estado_visual_estacionamiento = 'is-available';
if (!$estacionamiento_tiene_cupo) {
    $estado_visual_estacionamiento = 'is-unconfigured';
} elseif ($espacios_excedidos > 0) {
    $estado_visual_estacionamiento = 'is-over-capacity';
} elseif ($pct_estacionamiento >= 90) {
    $estado_visual_estacionamiento = 'is-near-capacity';
} elseif ($pct_estacionamiento >= 70) {
    $estado_visual_estacionamiento = 'is-busy';
}
$etiqueta_limite_estacionamiento = $estacionamiento_tiene_cupo ? ('de ' . $limite_estacionamiento) : 'sin cupo';
$nota_cabeza_estacionamiento = $estacionamiento_tiene_cupo
    ? ($vehiculos_estacionados . ' / ' . $limite_estacionamiento . ' comprometidos')
    : ($vehiculos_estacionados . ' comprometidos · sin cupo');
$etiqueta_progreso_estacionamiento = $estacionamiento_tiene_cupo ? ($pct_estacionamiento . '%') : 'Sin cupo';
$ring_circumference = 314;
$ring_offset = $ring_circumference - ($ring_circumference * $pct_estacionamiento / 100);

$hotel_display_name = 'Medisoft Hoteles';
$hotel_branding = [];
if (function_exists('current_hotel_branding')) {
    try {
        $hotel_branding = current_hotel_branding();
    } catch (Throwable $e) {
        $hotel_branding = [];
    }
}

if (function_exists('current_hotel_display_name')) {
    $hotel_display_name = current_hotel_display_name('Medisoft Hoteles');
} elseif (function_exists('current_hotel_nombre')) {
    $hotel_nombre = trim((string)current_hotel_nombre());
    $hotel_display_name = $hotel_nombre !== '' ? $hotel_nombre : 'Medisoft Hoteles';
} elseif (!empty($_SESSION['hotel_nombre'])) {
    $hotel_display_name = $_SESSION['hotel_nombre'];
}

$hero_image_url = null;
if (function_exists('hotel_branding_asset_url') && !empty($hotel_branding['login_background_url'])) {
    $hero_image_url = hotel_branding_asset_url($hotel_branding['login_background_url']);
}
$hero_image_url = $hero_image_url ?: asset('img/hero-dashboard.jpg');

$habitaciones_total = (int)($stats['habitaciones']['total'] ?? 0);
$habitaciones_ocupadas = (int)($stats['habitaciones']['ocupadas'] ?? 0);
$habitaciones_disponibles = (int)($stats['habitaciones']['disponibles_reales'] ?? ($stats['habitaciones']['disponibles'] ?? 0));
$habitaciones_por_llegar = (int)($stats['habitaciones']['por_llegar'] ?? 0);
$habitaciones_mantenimiento = (int)($stats['habitaciones']['mantenimiento'] ?? 0);
$habitaciones_limpieza = (int)($stats['habitaciones']['limpieza'] ?? 0);
$ocupacion_pct = min(100, max(0, (float)($stats['habitaciones']['porcentaje_ocupacion'] ?? 0)));
$habitaciones_libres = max(0, $habitaciones_total - $habitaciones_ocupadas);

$ingresos_total = (float)($stats['finanzas']['ingreso_neto'] ?? ($stats['ingresos']['total_dia'] ?? 0));
$ingresos_brutos_total = (float)($stats['finanzas']['entradas_brutas'] ?? ($stats['ingresos']['brutos_total_dia'] ?? $ingresos_total));
$reversos_total = (float)($stats['finanzas']['reversos'] ?? ($stats['reversos']['total_dia'] ?? ($stats['ingresos']['reversos_total_dia'] ?? 0)));
$egresos_total = (float)($stats['finanzas']['gastos_reales'] ?? ($stats['egresos']['total_dia'] ?? 0));
$balance_dia = (float)($stats['finanzas']['balance'] ?? ($ingresos_total - $egresos_total));
$entradas_total = (int)($stats['entradas']['total'] ?? 0);
$entradas_pendientes = (int)($stats['entradas']['pendientes'] ?? 0);
$salidas_total = (int)($stats['salidas']['total'] ?? 0);
$salidas_pendientes = (int)($stats['salidas']['pendientes'] ?? 0);

$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$fecha_hoy = dashboard_format_date(date('Y-m-d'), 'l, d \d\e F');
$fecha_hero = dashboard_upper(str_replace(' de ', ' ', $fecha_hoy)) . ' · ' . date('H:i');
$caja_abierta = (bool)$corte_actual;
$usuario_nombre = function_exists('user_name') ? user_name() : ($_SESSION['nombre'] ?? 'Usuario');
$usuario_rol = function_exists('user_role') ? user_role() : ($_SESSION['rol'] ?? 'Hotel');
$usuario_iniciales = dashboard_initials($usuario_nombre);
$notificaciones_pendientes = (int)($notificaciones_resumen['pendientes'] ?? ($notificaciones_resumen['nuevas'] ?? 0));
$notificaciones_prioritarias = (int)($notificaciones_resumen['prioritarias'] ?? 0);
$notificaciones_hoy = (int)($notificaciones_resumen['hoy'] ?? 0);

$chart_data = $graficos['ocupacion_semanal'] ?? [];
$weekly_total_active = max(0, $habitaciones_total);
$chart_max = max(1, $weekly_total_active);
$weekly_chart_rows = array_values(array_slice($chart_data, 0, 7));
$weekly_chart_count = count($weekly_chart_rows);
$weekly_chart_avg = 0;
$weekly_chart_peak = 0;
$weekly_chart_peak_day = '-';
$weekly_chart_total_occupied = 0;
$weekly_axis_top = $weekly_total_active;
$weekly_axis_75 = (int)round($weekly_axis_top * .75);
$weekly_axis_50 = (int)round($weekly_axis_top * .50);
$weekly_axis_25 = (int)round($weekly_axis_top * .25);

foreach ($weekly_chart_rows as $index => $row) {
    $ocupadas_week = max(0, (int)($row['ocupadas'] ?? 0));
    $weekly_chart_total_occupied += $ocupadas_week;

    if ($ocupadas_week >= $weekly_chart_peak) {
        $weekly_chart_peak = $ocupadas_week;
        $weekly_chart_peak_day = (string)($row['dia'] ?? '-');
    }
}

if ($weekly_chart_count > 0) {
    $weekly_chart_avg = $weekly_chart_total_occupied / $weekly_chart_count;
}
$weekly_chart_avg_label = rtrim(rtrim(number_format($weekly_chart_avg, 1), '0'), '.') . ' hab.';

// ── Gráfica de línea/área "ocupación semanal" (curva suave) ──
$weekly_line_rows = [];
$weekly_line_peak_date = '';

foreach ($weekly_chart_rows as $index => $row) {
    $ocupadas_line = max(0, (int)($row['ocupadas'] ?? 0));
    $pct_line = $chart_max > 0 ? min(100, max(0, ($ocupadas_line / $chart_max) * 100)) : 0;
    $es_hoy_line = $index === $weekly_chart_count - 1;
    $es_pico_line = $weekly_chart_peak_day !== '-' && $ocupadas_line === $weekly_chart_peak;
    $x_line = $weekly_chart_count > 0 ? ((($index + 0.5) / $weekly_chart_count) * 100) : 50;

    if ($es_pico_line && $weekly_line_peak_date === '') {
        $weekly_line_peak_date = dashboard_short_date($row['fecha'] ?? '');
    }

    $weekly_line_rows[] = [
        'dia' => (string)($row['dia'] ?? '-'),
        'fecha' => dashboard_short_date($row['fecha'] ?? ''),
        'ocupadas' => $ocupadas_line,
        'pct' => $pct_line,
        'x' => $x_line,
        'y' => 100 - $pct_line,
        'es_hoy' => $es_hoy_line,
        'es_pico' => $es_pico_line,
    ];
}

// Curva suave (Catmull-Rom → Bézier) para la línea y el área del gráfico
$weekly_line_path = '';
$weekly_area_path = '';
$weekly_line_count = count($weekly_line_rows);

if ($weekly_line_count === 1) {
    $p = $weekly_line_rows[0];
    $weekly_line_path = sprintf('M%.2f,%.2f L%.2f,%.2f', $p['x'], $p['y'], $p['x'], $p['y']);
} elseif ($weekly_line_count > 1) {
    $weekly_line_path = sprintf('M%.2f,%.2f', $weekly_line_rows[0]['x'], $weekly_line_rows[0]['y']);

    for ($i = 0; $i < $weekly_line_count - 1; $i++) {
        $p0 = $weekly_line_rows[max($i - 1, 0)];
        $p1 = $weekly_line_rows[$i];
        $p2 = $weekly_line_rows[$i + 1];
        $p3 = $weekly_line_rows[min($i + 2, $weekly_line_count - 1)];

        $cp1x = $p1['x'] + ($p2['x'] - $p0['x']) / 6;
        $cp1y = $p1['y'] + ($p2['y'] - $p0['y']) / 6;
        $cp2x = $p2['x'] - ($p3['x'] - $p1['x']) / 6;
        $cp2y = $p2['y'] - ($p3['y'] - $p1['y']) / 6;

        $weekly_line_path .= sprintf(' C%.2f,%.2f %.2f,%.2f %.2f,%.2f', $cp1x, $cp1y, $cp2x, $cp2y, $p2['x'], $p2['y']);
    }
}

if ($weekly_line_path !== '') {
    $weekly_area_path = $weekly_line_path
        . sprintf(' L%.2f,100 L%.2f,100 Z', $weekly_line_rows[$weekly_line_count - 1]['x'], $weekly_line_rows[0]['x']);
}

$weekly_line_updated_at = date('H:i');

// ── Compact mobile dashboard helpers (Claude Design "mobile-foto") ──
// Editorial hero + tight operational cards for phones. Reuses the same
// data as the desktop layout; no extra queries, no logic changes.
$m_ring_circ = 264; // 2·π·r con r = 42
$m_ring_offset = $m_ring_circ * (1 - min(100, max(0, $ocupacion_pct)) / 100);
$m_bar_width = static function ($count) use ($habitaciones_total) {
    if ($habitaciones_total <= 0 || $count <= 0) {
        return 0;
    }
    return min(100, max(4, (int) round($count / $habitaciones_total * 100)));
};
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

:root {
    --dash-primary: var(--brand-action-bg, var(--brand-primary, #1B2746));
    --dash-secondary: var(--brand-action-bg-hover, var(--brand-secondary, #0F172A));
    --dash-accent: var(--brand-accent, #BD9441);
    --dash-on-brand: var(--brand-action-text, #FFFEFB);
    --dash-ivory: color-mix(in srgb, var(--dash-accent) 9%, #F5F5F7);
    --dash-ivory-2: color-mix(in srgb, var(--dash-accent) 6%, #FAFAFC);
    --dash-surface: color-mix(in srgb, var(--dash-accent) 2%, #FFFFFF);
    --dash-surface-warm: color-mix(in srgb, var(--dash-accent) 5%, #FFFFFF);
    --dash-line: color-mix(in srgb, var(--dash-accent) 22%, #E7DEC9);
    --dash-line-soft: color-mix(in srgb, var(--dash-accent) 12%, #F0ECE2);
    --dash-navy: var(--dash-primary);
    --dash-navy-700: color-mix(in srgb, var(--dash-secondary) 86%, var(--dash-primary));
    --dash-ink: var(--brand-text, #1F2937);
    --dash-muted: var(--brand-muted, #667085);
    --dash-slate-700: color-mix(in srgb, var(--dash-ink) 72%, #64748B);
    --dash-slate-500: var(--dash-muted);
    --dash-slate-400: color-mix(in srgb, var(--dash-muted) 72%, #CBD5E1);
    --dash-gold: var(--dash-accent);
    --dash-gold-mid: color-mix(in srgb, var(--dash-accent) 82%, #FFFFFF);
    --dash-gold-soft: color-mix(in srgb, var(--dash-accent) 64%, #FFFFFF);
    --dash-gold-bg: color-mix(in srgb, var(--dash-accent) 16%, #FFFFFF);
    --dash-gold-line: color-mix(in srgb, var(--dash-accent) 28%, #E2E8F0);
    --dash-available: #1E9E63;
    --dash-bg-available: #E7F4EC;
    --dash-occupied: #C2603C;
    --dash-bg-occupied: #F8EAE1;
    --dash-arriving: #5A57D2;
    --dash-bg-arriving: #ECEBFB;
    --dash-cleaning: #2F77E0;
    --dash-bg-cleaning: #E6EFFC;
    --dash-maint: #C2841C;
    --dash-bg-maint: #FAF0DC;
    --dash-critical: #D64539;
    --dash-bg-critical: #FBE9E7;
    --dash-r-xs: 8px;
    --dash-r-sm: 11px;
    --dash-r: 15px;
    --dash-r-lg: 20px;
    --dash-r-xl: 26px;
    --dash-shadow: 0 2px 8px color-mix(in srgb, var(--dash-secondary) 6%, transparent), 0 12px 28px color-mix(in srgb, var(--dash-secondary) 7%, transparent);
    --dash-shadow-lg: 0 18px 48px color-mix(in srgb, var(--dash-secondary) 16%, transparent);
    --dash-serif: 'Cormorant Garamond', Georgia, serif;
    --dash-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

@media (min-width: 1025px) {
    body.hotel-layout-scope .mobile-header-modern,
    body.hotel-layout-scope .hotel-header,
    body.hotel-layout-scope .scroll-progress {
        display: none !important;
    }
}

html,
body.hotel-layout-scope {
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: hidden !important;
}

body.hotel-layout-scope {
    padding-top: 0 !important;
    background: var(--dash-ivory) !important;
}

@media (min-width: 1025px) {
    body.hotel-layout-scope {
        margin-left: 0 !important;
    }
}

@media (max-width: 1024px) {
    body.hotel-layout-scope {
        padding-top: 64px !important;
    }
}

body.hotel-layout-scope > .flex.h-screen.overflow-hidden,
body.hotel-layout-scope > .flex.h-screen.overflow-hidden > .flex-1 {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    min-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}

@media (min-width: 1025px) {
    body.hotel-layout-scope > .flex.h-screen.overflow-hidden {
        box-sizing: border-box !important;
        padding-left: var(--sidebar-width) !important;
    }
}

body.hotel-layout-scope .main-content {
    width: 100% !important;
    max-width: 100% !important;
    min-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
    background: var(--dash-ivory) !important;
}

body.hotel-layout-scope .main-content > .dashboard-boutique {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
}

@media (max-width: 1024px) {
    html {
        height: 100%;
        min-height: 100%;
    }

    body.hotel-layout-scope.page-dashboard {
        height: 100%;
        min-height: 100%;
        overflow: hidden !important;
        overscroll-behavior-y: none;
    }

    body.hotel-layout-scope.page-dashboard > .flex.h-screen.overflow-hidden,
    body.hotel-layout-scope.page-dashboard > .flex.h-screen.overflow-hidden > .flex-1 {
        height: calc(100dvh - 64px) !important;
        min-height: calc(100dvh - 64px) !important;
        max-height: calc(100dvh - 64px) !important;
        overflow: hidden !important;
    }

    body.hotel-layout-scope.page-dashboard .main-content {
        height: 100% !important;
        min-height: 0 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-y: contain;
        touch-action: pan-y;
        /* Suma el alto de la barra inferior flotante (--hbn-offset del footer-nav)
           para que el ultimo contenido no quede tapado; fallback 0px si el hotel
           no tiene barra configurada. */
        padding-bottom: calc(var(--hbn-offset, 0px) + max(24px, env(safe-area-inset-bottom))) !important;
        scroll-padding-bottom: calc(var(--hbn-offset, 0px) + max(24px, env(safe-area-inset-bottom)));
    }

    body.hotel-layout-scope.page-dashboard .dashboard-boutique {
        min-height: 100%;
    }

    @supports not (height: 100dvh) {
        body.hotel-layout-scope.page-dashboard > .flex.h-screen.overflow-hidden,
        body.hotel-layout-scope.page-dashboard > .flex.h-screen.overflow-hidden > .flex-1 {
            height: calc(100vh - 64px) !important;
            min-height: calc(100vh - 64px) !important;
            max-height: calc(100vh - 64px) !important;
        }
    }
}

.dashboard-boutique {
    width: 100%;
    min-height: 100vh;
    display: block;
    color: var(--dash-ink);
    background:
        radial-gradient(circle at 92% 8%, rgba(194,160,90,.16), transparent 28rem),
        var(--dash-ivory);
    font-family: var(--dash-sans);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.dashboard-boutique * {
    box-sizing: border-box;
}

@keyframes dashCardIn {
    from {
        opacity: 0;
        transform: translateY(14px) scale(.985);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes dashHeroLight {
    0%, 100% {
        opacity: .22;
        transform: translate3d(-6%, -2%, 0) scale(1);
    }
    50% {
        opacity: .42;
        transform: translate3d(2%, 2%, 0) scale(1.04);
    }
}

@keyframes dashPulseDot {
    0%, 100% {
        box-shadow: 0 0 0 0 color-mix(in srgb, currentColor 36%, transparent);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 0 0 7px transparent;
        transform: scale(1.08);
    }
}

@keyframes dashIconFloat {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
}

@keyframes dashProgressGrow {
    from {
        transform: scaleX(.08);
        opacity: .6;
    }
    to {
        transform: scaleX(1);
        opacity: 1;
    }
}

@keyframes dashRingDraw {
    from {
        stroke-dashoffset: 314;
    }
}

@keyframes dashWarmShift {
    0%, 100% {
        filter: saturate(1);
    }
    50% {
        filter: saturate(1.1) brightness(1.015);
    }
}

.avatar {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 12px;
    background: linear-gradient(160deg, var(--dash-primary), var(--dash-secondary));
    color: var(--dash-on-brand);
    font-weight: 800;
    font-size: 15px;
}

.boutique-main {
    min-width: 0;
    width: 100%;
    max-width: none;
    padding: clamp(18px, 1.55vw, 28px);
    padding-bottom: 42px;
}

.editorial-hero {
    position: relative;
    min-height: 225px;
    overflow: hidden;
    border-radius: 26px;
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--dash-secondary) 78%, transparent), color-mix(in srgb, var(--dash-secondary) 16%, transparent)),
        url("<?= htmlspecialchars($hero_image_url, ENT_QUOTES, 'UTF-8') ?>") center/cover;
    box-shadow: var(--dash-shadow-lg);
    isolation: isolate;
    animation: dashCardIn .58s cubic-bezier(.2, .78, .22, 1) both;
}

.editorial-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 0;
    background:
        linear-gradient(180deg,
            color-mix(in srgb, var(--dash-secondary) 32%, transparent) 0%,
            color-mix(in srgb, var(--dash-secondary) 15%, transparent) 35%,
            color-mix(in srgb, var(--dash-secondary) 72%, transparent) 100%),
        linear-gradient(90deg,
            color-mix(in srgb, var(--dash-primary) 92%, transparent) 0%,
            color-mix(in srgb, var(--dash-primary) 66%, transparent) 47%,
            color-mix(in srgb, var(--dash-primary) 24%, transparent) 100%),
        radial-gradient(circle at 74% 42%, color-mix(in srgb, var(--dash-accent) 28%, transparent), transparent 21rem);
}

.editorial-hero::after {
    content: "";
    position: absolute;
    inset: -18% -10% auto auto;
    width: min(44vw, 560px);
    height: 260px;
    z-index: 0;
    border-radius: 999px;
    background:
        radial-gradient(circle, color-mix(in srgb, var(--dash-accent) 42%, transparent), transparent 62%),
        radial-gradient(circle at 70% 20%, rgba(255,255,255,.18), transparent 38%);
    filter: blur(10px);
    pointer-events: none;
    animation: dashHeroLight 7s ease-in-out infinite;
}

.hero-top {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 22px 28px 0;
}

.hero-date {
    color: color-mix(in srgb, var(--dash-on-brand) 84%, transparent);
    text-shadow: 0 1px 1px rgba(0,0,0,.24);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .18em;
    text-transform: uppercase;
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.hotel-switch,
.glass-button {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 0 15px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 11px;
    background: rgba(255,255,255,.14);
    color: var(--dash-on-brand);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.26);
    backdrop-filter: blur(12px);
    font-size: 13px;
    font-weight: 700;
}

.glass-button {
    position: relative;
    width: 40px;
    justify-content: center;
    padding: 0;
    text-decoration: none;
    appearance: none;
    cursor: pointer;
}

.notification-dot {
    position: absolute;
    top: 9px;
    right: 10px;
    width: 7px;
    height: 7px;
    border-radius: 99px;
    background: var(--dash-critical);
    border: 1px solid #fff;
    animation: dashPulseDot 2.2s ease-in-out infinite;
}

.notification-count {
    position: absolute;
    top: -7px;
    right: -7px;
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    border-radius: 999px;
    background: var(--dash-critical);
    color: #fff;
    border: 2px solid rgba(255,255,255,.96);
    font-size: 10px;
    font-weight: 900;
    line-height: 1;
    box-shadow: 0 8px 18px rgba(136, 42, 42, .28);
}

.glass-button.has-notifications {
    border-color: rgba(255,255,255,.36);
    background: rgba(255,255,255,.2);
}

.notification-bell-shell {
    display: inline-flex;
}

/* ── Dropdown de notificaciones — boutique (Claude Design notificaciones-dropdown.html).
   El panel se mueve a <body> al abrir: solo tokens de :root (--dash-*), nada del wrapper. ── */
.notification-quick-panel {
    position: fixed;
    z-index: 1200;
    width: min(372px, calc(100vw - 24px));
    border: 1px solid var(--dash-line);
    border-radius: 20px;
    background: var(--dash-surface, #fff);
    color: var(--dash-ink);
    box-shadow: var(--dash-shadow-lg, 0 18px 48px rgba(27,39,70,.12));
    overflow: hidden;
    animation: nqDrop .24s cubic-bezier(.22,1,.36,1);
}

@keyframes nqDrop {
    from { opacity: 0; transform: translateY(-10px) scale(.98); }
    to   { opacity: 1; transform: none; }
}

.notification-quick-panel[hidden] {
    display: none;
}

/* Cierre fluido al alejar el cursor (hover-intent). */
.notification-quick-panel.nq-closing {
    animation: nqLift .18s cubic-bezier(.4, 0, 1, 1) forwards;
    pointer-events: none;
}

@keyframes nqLift {
    from { opacity: 1; transform: none; }
    to   { opacity: 0; transform: translateY(-8px) scale(.98); }
}

.notification-quick-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px 13px;
}

.notification-quick-head span {
    display: block;
    color: #939BAD;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
}

.notification-quick-head strong {
    color: var(--dash-navy);
    font-family: var(--dash-serif);
    font-size: 22px;
    font-weight: 600;
    line-height: 1;
}

.notification-quick-list {
    max-height: min(326px, 52vh);
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
}

.notification-quick-list::-webkit-scrollbar { width: 6px; }
.notification-quick-list::-webkit-scrollbar-thumb { background: #E2D9C8; border-radius: 99px; }

/* Fila deslizable: capa verde "Archivar" debajo de la fila */
.nq-swipe {
    position: relative;
    border-top: 1px solid var(--dash-line-soft);
    overflow: hidden;
}

.nq-swipe.gone {
    transition: height .32s ease, opacity .2s ease;
    opacity: 0;
    border-top: 0;
}

.nq-swipe-bg {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding-right: 22px;
    background: linear-gradient(90deg, #2BA76A, #1E9E63);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
}

.notification-quick-row {
    position: relative;
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 20px;
    background: var(--dash-surface, #fff);
    color: inherit;
    text-decoration: none;
    transition: background .14s ease;
    touch-action: pan-y;
    user-select: none;
    -webkit-user-drag: none;
}

.notification-quick-row.dragging { transition: none; }

.notification-quick-row.settle {
    transition: transform .3s cubic-bezier(.22,1,.36,1);
}

.notification-quick-row:hover,
.notification-quick-row:focus-visible {
    background: var(--dash-surface-warm, #FEFCF7);
    outline: none;
}

.notification-quick-icon {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    flex: none;
    color: #6C7689;
    background: var(--dash-ivory-2, #FAFAFC);
    font-size: 15px;
    pointer-events: none;
}

/* Severidad semántica: crítica/alta rojo, media ámbar (no cambia con la marca) */
.nq-crit .notification-quick-icon { background: #FBE9E7; color: #D64539; }
.nq-warn .notification-quick-icon { background: #FAF0DC; color: #C2841C; }

.notification-quick-body {
    min-width: 0;
    flex: 1;
    pointer-events: none;
}

.notification-quick-title {
    display: block;
    color: var(--dash-navy);
    font-size: 14px;
    font-weight: 700;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notification-quick-meta {
    display: block;
    margin-top: 3px;
    color: #939BAD;
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notification-quick-go {
    margin-left: auto;
    color: #B7BDCB;
    font-size: 13px;
    flex: none;
    pointer-events: none;
}

.notification-quick-empty {
    padding: 26px 20px;
    color: #939BAD;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
}

.notification-quick-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    font-size: 11px;
    font-weight: 600;
    color: #939BAD;
    padding: 8px;
    background: var(--dash-surface-warm, #FEFCF7);
    border-top: 1px solid var(--dash-line-soft);
}

.notification-quick-push {
    padding: 15px 20px 16px;
    border-top: 1px solid var(--dash-line);
    background: var(--dash-surface-warm, #FEFCF7);
}

.notification-quick-push-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--dash-navy);
    font-size: 13px;
    font-weight: 700;
}

.notification-quick-push-title i {
    color: var(--dash-gold, var(--dash-accent));
    font-size: 14px;
}

.notification-quick-push-status-row {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    margin: 5px 0 12px;
}

.notification-quick-push-check {
    display: none;
    color: #1E9E63;
    font-size: 13px;
    margin-top: 1px;
}

.notification-quick-push-status {
    margin: 0;
    color: #6C7689;
    font-size: 12.5px;
    font-weight: 500;
    line-height: 1.5;
}

/* Activo: se esconde el título y queda la línea sutil con check verde (diseño) */
.notification-quick-push[data-push-state="enabled"] .notification-quick-push-title { display: none; }
.notification-quick-push[data-push-state="enabled"] .notification-quick-push-check { display: inline-block; }
.notification-quick-push[data-push-state="enabled"] .notification-quick-push-status-row { margin-top: 0; }
.notification-quick-push[data-push-state="enabled"] { padding-top: 12px; }

.notification-quick-push-actions {
    display: flex;
    gap: 9px;
}

.notification-quick-push-btn {
    flex: 1;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 0;
    border-radius: 12px;
    padding: 12px;
    background: var(--dash-navy);
    color: var(--dash-on-brand);
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    transition: filter .14s ease;
}

.notification-quick-push-btn:hover:not(:disabled) { filter: brightness(1.06); }

.notification-quick-push-btn:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--dash-navy) 30%, transparent);
    outline-offset: 2px;
}

.notification-quick-push-btn.secondary {
    flex: none;
    width: 46px;
    padding: 0;
    background: var(--dash-surface, #fff);
    color: #6C7689;
    border: 1px solid var(--dash-line);
}

.notification-quick-push-btn.secondary:hover:not(:disabled) {
    border-color: color-mix(in srgb, var(--dash-gold, var(--dash-accent)) 45%, var(--dash-line));
    color: var(--dash-navy);
    filter: none;
}

.notification-quick-push-btn:disabled {
    cursor: not-allowed;
    opacity: .62;
}

/* Desactivar = rojo semántico (diseño .nd-actbtn.deactivate) */
.notification-quick-push[data-push-state="enabled"] .notification-quick-push-btn:not(.secondary) {
    background: #A33B32;
    color: #fff;
}

.notification-quick-all {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 46px;
    padding: 0 20px;
    border-top: 1px solid var(--dash-line);
    color: var(--dash-navy);
    font-size: 13.5px;
    font-weight: 700;
    text-decoration: none;
    background: var(--dash-surface, #fff);
}

.notification-quick-all:hover,
.notification-quick-all:focus-visible {
    color: var(--dash-gold, var(--dash-accent));
    outline: none;
}

@media (prefers-reduced-motion: reduce) {
    .notification-quick-panel,
    .notification-quick-panel.nq-closing { animation: none; }
    .notification-quick-row.settle,
    .nq-swipe.gone { transition: none; }
}

.hero-title {
    position: relative;
    z-index: 2;
    margin-top: 18px;
    padding: 0 28px;
}

.hero-title h1 {
    margin: 0;
    color: var(--dash-on-brand);
    font-family: var(--dash-serif);
    font-size: clamp(34px, 4.2vw, 50px);
    line-height: .95;
    font-weight: 650;
    letter-spacing: -.01em;
    text-shadow:
        0 2px 4px rgba(0,0,0,.38),
        0 14px 34px rgba(0,0,0,.45);
}

.hero-title p {
    margin: 9px 0 0;
    color: color-mix(in srgb, var(--dash-on-brand) 91%, transparent);
    font-size: 14px;
    font-weight: 700;
    text-shadow:
        0 1px 2px rgba(0,0,0,.42),
        0 10px 24px rgba(0,0,0,.36);
}


.grid4,
.grid3,
.dashboard-main-flow {
    display: grid;
    gap: 20px;
    margin-top: 22px;
}

.grid4 {
    grid-template-columns: repeat(4, minmax(210px, 1fr));
}

.dashboard-main-flow {
    grid-template-columns: minmax(0, 1fr) clamp(420px, 24vw, 500px);
    align-items: start;
}

.dashboard-left-flow {
    display: grid;
    gap: 20px;
    min-width: 0;
    align-content: start;
}

.dashboard-main-flow > .dashboard-parking-card {
    align-self: start;
}

.dashboard-left-flow > .weekly-occupancy-card,
.dashboard-left-flow > .dashboard-flow-cards {
    width: 100%;
    margin-top: 0;
}

.grid3 {
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.card {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--dash-line);
    border-radius: 18px;
    background: var(--dash-surface);
    box-shadow: var(--dash-shadow);
    isolation: isolate;
    transition: transform .24s ease, border-color .24s ease, box-shadow .24s ease, background .24s ease;
}

.card::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 0;
    border-radius: inherit;
    background:
        radial-gradient(circle at 88% 12%, color-mix(in srgb, var(--card-glow, var(--dash-accent)) 16%, transparent), transparent 12rem),
        linear-gradient(135deg, color-mix(in srgb, var(--card-glow, var(--dash-accent)) 4%, transparent), transparent 42%);
    opacity: 0;
    transition: opacity .24s ease;
    pointer-events: none;
}

.card > :not(.occ-wave) {
    position: relative;
    z-index: 1;
}

.occ-wave {
    z-index: 0;
}

.card:hover {
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--card-glow, var(--dash-accent)) 34%, var(--dash-line));
    background: color-mix(in srgb, var(--card-glow, var(--dash-accent)) 3%, var(--dash-surface));
    box-shadow: 0 20px 46px color-mix(in srgb, var(--dash-secondary) 14%, transparent);
}

.card:hover::before {
    opacity: 1;
}

.grid4 .card,
.grid3 .card,
.dashboard-left-flow > .card,
.dashboard-main-flow > .card {
    animation: dashCardIn .52s cubic-bezier(.2, .78, .22, 1) both;
}

.grid4 .card:nth-child(1) {
    --card-glow: var(--dash-gold);
    animation-delay: .06s;
}

.grid4 .card:nth-child(2) {
    --card-glow: var(--dash-available);
    animation-delay: .12s;
}

.grid4 .card:nth-child(3) {
    --card-glow: var(--dash-arriving);
    animation-delay: .18s;
}

.grid4 .card:nth-child(4) {
    --card-glow: var(--dash-maint);
    animation-delay: .24s;
}

.weekly-occupancy-card {
    --card-glow: var(--dash-navy);
    animation-delay: .14s;
}

.dashboard-parking-card {
    --card-glow: var(--dash-gold);
    --parking-ring-start: var(--dash-gold-mid);
    --parking-ring-end: var(--dash-gold);
    animation-delay: .22s;
}

.dashboard-parking-card.is-near-capacity,
.dashboard-parking-card.is-busy {
    --card-glow: var(--dash-maint);
    --parking-ring-start: color-mix(in srgb, var(--dash-maint) 54%, #fff);
    --parking-ring-end: var(--dash-maint);
}

.dashboard-parking-card.is-over-capacity {
    --card-glow: var(--dash-critical);
    --parking-ring-start: color-mix(in srgb, var(--dash-critical) 54%, #fff);
    --parking-ring-end: var(--dash-critical);
}

.dashboard-parking-card.is-unconfigured {
    --card-glow: var(--dash-slate-400);
    --parking-ring-start: color-mix(in srgb, var(--dash-slate-400) 50%, #fff);
    --parking-ring-end: var(--dash-slate-500);
}

.grid3 .card:nth-child(1) {
    --card-glow: var(--dash-arriving);
    animation-delay: .2s;
}

.grid3 .card:nth-child(2) {
    --card-glow: var(--dash-maint);
    animation-delay: .27s;
}

.grid3 .card:nth-child(3) {
    --card-glow: var(--dash-available);
    animation-delay: .34s;
}

.card-pad {
    padding: 22px;
}

.occ-card {
    min-height: 334px;
}

.occ-wave {
    position: absolute;
    right: -80px;
    bottom: -100px;
    width: 360px;
    height: 180px;
    border-radius: 50%;
    background: linear-gradient(180deg, rgba(246,242,234,.42), rgba(255,255,255,0));
    animation: dashHeroLight 6.5s ease-in-out infinite;
}

.card-row-head,
.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.card-title {
    color: var(--dash-slate-400);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .12em;
    line-height: 1.2;
    text-transform: uppercase;
}

.card-kicker-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    margin-top: 12px;
    color: var(--dash-gold);
    font-size: 12px;
    font-weight: 850;
    text-decoration: none;
    transition: color .18s ease, transform .18s ease;
}

.card-kicker-link i {
    font-size: 10px;
    transition: transform .18s ease;
}

.card-kicker-link:hover,
.card-kicker-link:focus-visible {
    color: var(--dash-navy);
    text-decoration: underline;
    text-underline-offset: 4px;
}

.card-kicker-link:hover i,
.card-kicker-link:focus-visible i {
    transform: translateX(2px);
}

.card-kicker-link:active {
    transform: translateY(1px);
}

.card-kicker-link:focus-visible,
.legend-link:focus-visible,
.list-row.is-action:focus-visible,
.dash-btn:focus-visible,
.dm-sec a:focus-visible,
.dm-rst.is-link:focus-visible,
.dm-ag.is-link:focus-visible,
.dm-btn:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--dash-gold) 70%, #fff);
    outline-offset: 3px;
}

.mini-icon {
    background: var(--dash-bg-occupied);
    color: var(--dash-occupied);
    transition: transform .24s ease, background .24s ease, color .24s ease;
}

.card:hover .mini-icon {
    transform: translateY(-2px) rotate(-3deg);
}

.grid4 .card:nth-child(1) .mini-icon,
.grid4 .card:nth-child(4) .mini-icon {
    animation: dashIconFloat 3.2s ease-in-out infinite;
}

.occ-percent {
    color: var(--dash-navy);
    font-size: 56px;
    line-height: .9;
    font-weight: 900;
    letter-spacing: -.04em;
    font-variant-numeric: tabular-nums;
}

.occ-meta {
    position: relative;
    z-index: 1;
    margin-top: 26px;
}

.occ-meta strong {
    color: var(--dash-navy);
    font-size: 25px;
    font-weight: 900;
}

.muted {
    color: var(--dash-slate-500);
}

.soft-note {
    margin-top: 5px;
    color: var(--dash-slate-500);
    font-size: 12.5px;
}

.money-total,
.metric-total {
    margin-top: 3px;
    color: var(--dash-navy);
    font-size: 26px;
    line-height: 1;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.money-section {
    margin: 18px 0 8px;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .13em;
    text-transform: uppercase;
}

.money-line,
.legend-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    min-height: 27px;
    color: var(--dash-slate-700);
    font-size: 13px;
}

.legend-link {
    margin-inline: -8px;
    padding-inline: 8px;
    border-radius: 11px;
    color: inherit;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s ease, transform .18s ease, color .18s ease;
}

.legend-link:hover,
.legend-link:focus-visible {
    background: color-mix(in srgb, var(--swatch, var(--dash-gold)) 9%, transparent);
    color: var(--dash-navy);
    transform: translateX(2px);
}

.legend-link:active {
    transform: translateX(1px) scale(.995);
}

.legend-value {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--dash-navy);
}

.legend-value i {
    color: var(--dash-slate-400);
    font-size: 10px;
    opacity: .55;
    transition: color .18s ease, opacity .18s ease, transform .18s ease;
}

.legend-link:hover .legend-value i,
.legend-link:focus-visible .legend-value i {
    color: var(--dash-gold);
    opacity: 1;
    transform: translateX(2px);
}

.money-line strong,
.legend-line strong {
    color: var(--dash-navy);
    font-variant-numeric: tabular-nums;
}

.balance-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--dash-line);
}

.balance-line span {
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .14em;
    text-transform: uppercase;
}

.balance-line strong {
    color: var(--dash-available);
    font-size: 20px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.cash-day-card {
    display: flex;
    flex-direction: column;
}

.cash-day-card .card-row-head {
    align-items: flex-start;
}

.cash-day-card .card-row-head > div:last-child {
    min-width: 0;
}

.cash-day-total {
    margin-top: 3px;
    color: var(--dash-navy);
    font-size: clamp(22px, 1.65vw, 26px);
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.cash-day-subtitle {
    margin-top: 5px;
    color: var(--dash-slate-500);
    font-size: 12px;
    font-weight: 700;
}

.cash-day-groups {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 156px), 1fr));
    gap: 10px;
    margin-top: 17px;
}

.cash-day-group {
    min-width: 0;
    overflow: hidden;
    padding: 11px;
    border-radius: 13px;
    background: var(--dash-surface-warm);
    border: 1px solid var(--dash-line-soft);
}

.cash-day-group-title {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 8px;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .08em;
    line-height: 1;
    text-transform: uppercase;
}

.cash-day-group-title.income {
    color: var(--dash-available);
}

.cash-day-group-title.expense {
    color: var(--dash-critical);
}

.cash-day-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) max-content;
    align-items: center;
    column-gap: 8px;
    min-height: 25px;
    color: var(--dash-slate-700);
    font-size: 12.5px;
}

.cash-day-row span {
    min-width: 0;
    overflow-wrap: anywhere;
}

.cash-day-row strong {
    justify-self: end;
    color: var(--dash-navy);
    font-size: clamp(11px, .78vw, 12.5px);
    font-variant-numeric: tabular-nums;
    text-align: right;
    white-space: nowrap;
}

.cash-day-balance {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 12px;
    padding: 12px;
    border-radius: 13px;
    background: linear-gradient(135deg, var(--dash-primary), var(--dash-secondary));
    color: var(--dash-on-brand);
}

.cash-day-balance span {
    flex: 1 1 auto;
    min-width: 0;
    overflow-wrap: anywhere;
    color: color-mix(in srgb, var(--dash-on-brand) 78%, transparent);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.cash-day-balance strong {
    flex: 0 0 auto;
    color: var(--dash-on-brand);
    font-size: clamp(15px, 1.15vw, 18px);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    text-align: right;
    white-space: nowrap;
}

@media (max-width: 900px) {
    .cash-day-groups {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 420px) {
    .cash-day-row {
        grid-template-columns: 1fr;
        row-gap: 2px;
    }

    .cash-day-row strong {
        justify-self: start;
        text-align: left;
    }

    .cash-day-balance {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }

    .cash-day-balance strong {
        text-align: left;
    }
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    margin-top: 15px;
    padding: 7px 13px;
    border-radius: 999px;
    background: var(--dash-bg-available);
    color: var(--dash-available);
    font-size: 12px;
    font-weight: 900;
    transition: transform .2s ease, background .2s ease, color .2s ease;
}

.card:hover .status-badge {
    transform: translateY(-1px);
}

.status-badge.warn {
    background: var(--dash-bg-maint);
    color: var(--dash-maint);
}

.status-badge.critical {
    background: var(--dash-bg-critical);
    color: var(--dash-critical);
}

.led {
    width: 6px;
    height: 6px;
    border-radius: 99px;
    background: currentColor;
    animation: dashPulseDot 2.4s ease-in-out infinite;
}

.swatch {
    width: 8px;
    height: 8px;
    flex: 0 0 auto;
    border-radius: 99px;
    background: var(--swatch, var(--dash-gold));
}

.legend-line span:first-child {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.section-head {
    margin-bottom: 16px;
}

.section-head h2 {
    margin: 0;
    color: var(--dash-navy);
    font-family: var(--dash-serif);
    font-size: 24px;
    line-height: 1;
    font-weight: 650;
}

.weekly-occupancy-card {
    position: relative;
    align-self: start;
    height: auto;
    min-height: 0;
    overflow: hidden;
    background:
        radial-gradient(circle at 88% 4%, color-mix(in srgb, var(--dash-gold) 18%, transparent), transparent 18rem),
        linear-gradient(180deg, rgba(255,253,248,.98), rgba(250,246,238,.96));
}

.weekly-occupancy-card::after {
    content: "";
    position: absolute;
    inset: auto 22px 0;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, transparent, var(--dash-gold), var(--dash-navy));
    opacity: .72;
}

.weekly-chart-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: start;
    margin-bottom: 14px;
}

.weekly-chart-head h2 {
    margin: 0;
    color: var(--dash-navy);
    font-family: var(--dash-serif);
    font-size: 24px;
    line-height: 1;
    font-weight: 650;
}

.weekly-chart-head p {
    margin: 6px 0 0;
    color: var(--dash-slate-500);
    font-size: 12px;
    font-weight: 750;
}

.weekly-chart-summary {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.weekly-summary-chip {
    min-width: 96px;
    border: 1px solid var(--dash-line);
    border-radius: 14px;
    background: rgba(255,255,255,.74);
    padding: 9px 10px;
}

.weekly-summary-chip span {
    display: block;
    color: var(--dash-slate-400);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.weekly-summary-chip strong {
    display: block;
    margin-top: 4px;
    color: var(--dash-navy);
    font-size: 18px;
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.weekly-summary-chip em {
    display: block;
    margin-top: 3px;
    font-style: normal;
    font-size: 12px;
    font-weight: 850;
    color: var(--dash-gold);
    font-variant-numeric: tabular-nums;
}

.weekly-line-chart {
    --weekly-line-plot-height: clamp(220px, 18vw, 280px);
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 14px;
    margin-top: 16px;
}

.weekly-line-axis {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: var(--weekly-line-plot-height);
    padding: 36px 0 12px;
    box-sizing: border-box;
    text-align: right;
    color: var(--dash-slate-400);
    font-size: 10px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.weekly-line-body {
    min-width: 0;
}

.weekly-line-plot {
    position: relative;
    height: var(--weekly-line-plot-height);
    box-sizing: border-box;
    padding: 36px 14px 12px;
    border: 1px solid var(--dash-line);
    border-radius: 18px;
    background-color: rgba(255,255,255,.6);
    background-image: repeating-linear-gradient(to top, color-mix(in srgb, var(--dash-navy) 9%, transparent) 0 1px, transparent 1px 25%);
    background-origin: content-box;
    background-repeat: no-repeat;
}

.weekly-line-svg,
.weekly-line-points {
    position: absolute;
    inset: 36px 14px 12px;
}

.weekly-line-svg {
    width: calc(100% - 28px);
    height: calc(100% - 48px);
    overflow: visible;
}

.weekly-line-stroke {
    fill: none;
    stroke: var(--dash-navy);
    stroke-width: 2.5px;
    stroke-linecap: round;
    stroke-linejoin: round;
    vector-effect: non-scaling-stroke;
}

.weekly-line-area {
    stroke: none;
}

.weekly-line-stop-start {
    stop-color: var(--dash-navy);
    stop-opacity: .22;
}

.weekly-line-stop-end {
    stop-color: var(--dash-navy);
    stop-opacity: 0;
}

.weekly-line-point {
    position: absolute;
    transform: translateX(-50%);
    z-index: 2;
    outline: none;
}

.weekly-line-dot {
    display: block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--dash-navy);
    border: 2px solid var(--dash-surface);
    box-shadow: 0 2px 6px color-mix(in srgb, var(--dash-navy) 30%, transparent);
    transform: translateY(50%);
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.weekly-line-value {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    font-weight: 900;
    color: var(--dash-navy);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    transition: transform .18s ease, color .18s ease;
}

.weekly-line-tooltip {
    position: absolute;
    left: 50%;
    bottom: 30px;
    z-index: 8;
    min-width: 172px;
    padding: 10px 12px;
    border-radius: 13px;
    color: #fff;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--dash-navy) 92%, #111827), color-mix(in srgb, var(--dash-navy) 82%, var(--dash-gold)));
    box-shadow: 0 18px 34px -20px color-mix(in srgb, var(--dash-navy) 86%, transparent);
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, 8px) scale(.96);
    transform-origin: 50% 100%;
    transition: opacity .18s ease, transform .18s ease;
}

.weekly-line-tooltip::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: -6px;
    width: 12px;
    height: 12px;
    border-radius: 2px;
    background: color-mix(in srgb, var(--dash-navy) 82%, var(--dash-gold));
    transform: translateX(-50%) rotate(45deg);
}

.weekly-line-tooltip b {
    position: relative;
    z-index: 1;
    display: block;
    font-size: 13px;
    line-height: 1.1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.weekly-line-tooltip small {
    position: relative;
    z-index: 1;
    display: block;
    margin-top: 5px;
    color: rgba(255,255,255,.72);
    font-size: 10px;
    line-height: 1.25;
    font-weight: 750;
    white-space: nowrap;
}

.weekly-line-point:hover,
.weekly-line-point:focus {
    z-index: 12;
}

.weekly-line-point:hover .weekly-line-dot,
.weekly-line-point:focus .weekly-line-dot {
    transform: translateY(50%) scale(1.28);
    box-shadow: 0 0 0 6px color-mix(in srgb, var(--dash-navy) 12%, transparent), 0 12px 18px -12px color-mix(in srgb, var(--dash-navy) 70%, transparent);
}

.weekly-line-point:hover .weekly-line-value,
.weekly-line-point:focus .weekly-line-value {
    transform: translateX(-50%) translateY(-2px);
    color: var(--dash-gold);
}

.weekly-line-point:hover .weekly-line-tooltip,
.weekly-line-point:focus .weekly-line-tooltip {
    opacity: 1;
    transform: translate(-50%, 0) scale(1);
}

.weekly-line-point:first-child .weekly-line-tooltip {
    left: 0;
    transform: translate(-10px, 8px) scale(.96);
}

.weekly-line-point:first-child:hover .weekly-line-tooltip,
.weekly-line-point:first-child:focus .weekly-line-tooltip {
    transform: translate(-10px, 0) scale(1);
}

.weekly-line-point:first-child .weekly-line-tooltip::after {
    left: 18px;
}

.weekly-line-point:last-child .weekly-line-tooltip {
    left: auto;
    right: 0;
    transform: translate(10px, 8px) scale(.96);
}

.weekly-line-point:last-child:hover .weekly-line-tooltip,
.weekly-line-point:last-child:focus .weekly-line-tooltip {
    transform: translate(10px, 0) scale(1);
}

.weekly-line-point:last-child .weekly-line-tooltip::after {
    left: auto;
    right: 18px;
}

.weekly-line-point.is-peak .weekly-line-dot {
    width: 13px;
    height: 13px;
    background: var(--dash-gold);
    border-width: 2px;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--dash-gold) 22%, transparent);
}

.weekly-line-point.is-peak .weekly-line-value {
    bottom: 17px;
    font-size: 12px;
    color: var(--dash-gold);
}

.weekly-line-labels {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    padding: 0 14px;
}

.weekly-line-label {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    text-align: center;
}

.weekly-line-label .d {
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.weekly-line-label .f {
    color: var(--dash-slate-400);
    font-size: 10px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.weekly-line-label.is-peak .d,
.weekly-line-label.is-peak .f {
    color: var(--dash-gold);
}

.weekly-line-label.is-today .d::after {
    content: "";
    display: inline-block;
    width: 5px;
    height: 5px;
    margin-left: 4px;
    border-radius: 50%;
    background: var(--dash-navy);
    vertical-align: middle;
}

.weekly-line-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--dash-line-soft);
}

.weekly-line-foot-info {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 700;
}

.weekly-line-foot-info svg {
    flex-shrink: 0;
    color: var(--dash-slate-400);
}

.weekly-line-foot-time {
    color: var(--dash-slate-400);
    font-size: 11px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.parking-head-note {
    color: var(--dash-slate-400);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    white-space: nowrap;
}

.dashboard-parking-card.is-busy .parking-head-note,
.dashboard-parking-card.is-near-capacity .parking-head-note {
    color: var(--dash-maint);
}

.dashboard-parking-card.is-over-capacity .parking-head-note {
    color: var(--dash-critical);
}

.dashboard-parking-card.is-unconfigured .parking-head-note {
    color: var(--dash-slate-500);
}

.parking-status-strip {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.parking-status-pill {
    min-width: 0;
    padding: 10px;
    border: 1px solid var(--dash-line);
    border-radius: 12px;
    background: #fff;
}

.parking-status-pill span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--dash-slate-500);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.parking-status-pill strong {
    display: block;
    margin-top: 4px;
    color: var(--dash-navy);
    font-size: 20px;
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.parking-status-pill.is-occupied {
    border-color: color-mix(in srgb, var(--dash-occupied) 26%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-occupied) 7%, #fff);
}

.parking-status-pill.is-occupied span {
    color: var(--dash-occupied);
}

.parking-status-pill.is-reserved {
    border-color: color-mix(in srgb, var(--dash-arriving) 28%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-arriving) 8%, #fff);
}

.parking-status-pill.is-reserved span {
    color: var(--dash-arriving);
}

.parking-status-pill.is-free {
    border-color: color-mix(in srgb, var(--dash-available) 24%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-available) 7%, #fff);
}

.parking-status-pill.is-free span {
    color: var(--dash-available);
}

.park-ring {
    display: grid;
    justify-items: center;
    gap: 14px;
}

.ring-box {
    position: relative;
    width: 136px;
    height: 136px;
}

.ring-box svg {
    width: 136px;
    height: 136px;
}

.ring-box svg circle[stroke-dasharray] {
    animation: dashRingDraw .9s cubic-bezier(.2, .78, .22, 1) both .18s;
}

.ring-num {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    color: var(--dash-navy);
    text-align: center;
}

.ring-num b {
    display: block;
    font-size: 38px;
    line-height: .85;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.ring-num span {
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 800;
}

.park-bar {
    width: 100%;
    margin-top: 2px;
    padding: 15px;
    border: 1px solid var(--dash-line);
    border-radius: 13px;
    background: var(--dash-surface-warm);
}

.park-bar-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--dash-navy);
    font-size: 13.5px;
    font-weight: 850;
}

.park-bar-head b {
    color: var(--parking-ring-end);
    font-variant-numeric: tabular-nums;
}

.progress {
    height: 7px;
    overflow: hidden;
    margin-top: 10px;
    border-radius: 999px;
    background: #E8E1D4;
}

.progress i {
    display: block;
    width: var(--progress);
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--dash-gold-mid), var(--dash-gold));
    transform-origin: left center;
    animation: dashProgressGrow .72s cubic-bezier(.2, .78, .22, 1) both .18s;
}

.dashboard-parking-card.is-busy .progress i,
.dashboard-parking-card.is-near-capacity .progress i {
    background: linear-gradient(90deg, color-mix(in srgb, var(--dash-maint) 54%, #fff), var(--dash-maint));
}

.dashboard-parking-card.is-over-capacity .progress i {
    background: linear-gradient(90deg, color-mix(in srgb, var(--dash-critical) 54%, #fff), var(--dash-critical));
}

.dashboard-parking-card.is-unconfigured .progress i {
    background: linear-gradient(90deg, color-mix(in srgb, var(--dash-slate-400) 50%, #fff), var(--dash-slate-500));
}

.parking-breakdown {
    display: grid;
    gap: 8px;
    margin-top: 13px;
    padding-top: 12px;
    border-top: 1px solid var(--dash-line-soft);
}

.parking-breakdown-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    min-height: 34px;
    padding: 7px 9px;
    border: 1px solid color-mix(in srgb, var(--dash-gold) 18%, var(--dash-line));
    border-radius: 11px;
    background: color-mix(in srgb, var(--dash-gold) 5%, #FFFFFF);
}

.parking-breakdown-item span {
    min-width: 0;
    overflow: hidden;
    color: var(--dash-navy);
    font-size: 12px;
    font-weight: 850;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.parking-breakdown-item span small {
    display: block;
    margin-top: 2px;
    overflow: hidden;
    color: var(--dash-slate-500);
    font-size: 10.5px;
    font-weight: 750;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.parking-breakdown-item strong {
    min-width: 34px;
    height: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    border-radius: 999px;
    background: #FFFFFF;
    color: var(--dash-gold);
    font-size: 12px;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--dash-gold) 24%, transparent);
}

.parking-breakdown-item.has-vehicles {
    background: color-mix(in srgb, var(--dash-gold) 11%, #FFFFFF);
    border-color: color-mix(in srgb, var(--dash-gold) 34%, var(--dash-line));
}

.parking-breakdown-item.is-full {
    border-color: color-mix(in srgb, var(--dash-maint) 34%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-maint) 10%, #FFFFFF);
}

.parking-breakdown-item.is-full strong {
    color: var(--dash-maint);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--dash-maint) 28%, transparent);
}

.parking-breakdown-item.is-over-capacity {
    border-color: color-mix(in srgb, var(--dash-critical) 40%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-critical) 9%, #FFFFFF);
}

.parking-breakdown-item.is-over-capacity strong {
    color: var(--dash-critical);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--dash-critical) 30%, transparent);
}

.parking-breakdown-item.is-unconfigured:not(.has-vehicles) {
    background: color-mix(in srgb, var(--dash-slate-400) 6%, #FFFFFF);
    border-color: color-mix(in srgb, var(--dash-slate-400) 22%, var(--dash-line));
}

.parking-vehicle-list {
    width: 100%;
    display: grid;
    gap: 8px;
    margin-top: 2px;
}

.parking-vehicle-row {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    min-height: 56px;
    padding: 9px;
    border: 1px solid var(--dash-line);
    border-radius: 12px;
    background: #fff;
    color: inherit;
    text-decoration: none;
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.parking-vehicle-row:hover,
.parking-vehicle-row:focus-visible {
    transform: translateY(-1px);
    color: inherit;
    border-color: color-mix(in srgb, var(--dash-gold) 35%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-gold) 5%, #fff);
}

.parking-vehicle-icon {
    width: 34px;
    height: 34px;
    display: inline-grid;
    place-items: center;
    border-radius: 11px;
    border: 1px solid var(--dash-line);
    background: var(--dash-surface-warm);
    color: var(--dash-slate-500);
}

.parking-vehicle-row.is-occupied .parking-vehicle-icon {
    color: var(--dash-occupied);
    background: color-mix(in srgb, var(--dash-occupied) 9%, #fff);
    border-color: color-mix(in srgb, var(--dash-occupied) 28%, var(--dash-line));
}

.parking-vehicle-row.is-reserved .parking-vehicle-icon {
    color: var(--dash-arriving);
    background: color-mix(in srgb, var(--dash-arriving) 10%, #fff);
    border-color: color-mix(in srgb, var(--dash-arriving) 30%, var(--dash-line));
}

.parking-vehicle-copy {
    min-width: 0;
}

.parking-vehicle-copy strong {
    display: block;
    overflow: hidden;
    color: var(--dash-navy);
    font-size: 12.5px;
    font-weight: 900;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.parking-vehicle-copy em {
    display: block;
    margin-top: 2px;
    overflow: hidden;
    color: var(--dash-slate-500);
    font-size: 10.8px;
    font-style: normal;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.parking-vehicle-meta {
    display: grid;
    justify-items: end;
    gap: 2px;
    text-align: right;
}

.parking-vehicle-meta b {
    min-height: 22px;
    display: inline-flex;
    align-items: center;
    padding: 0 8px;
    border-radius: 999px;
    background: var(--dash-surface-warm);
    color: var(--dash-slate-500);
    font-size: 10.5px;
    font-weight: 950;
}

.parking-vehicle-row.is-occupied .parking-vehicle-meta b {
    color: var(--dash-occupied);
    background: color-mix(in srgb, var(--dash-occupied) 10%, #fff);
}

.parking-vehicle-row.is-reserved .parking-vehicle-meta b {
    color: var(--dash-arriving);
    background: color-mix(in srgb, var(--dash-arriving) 12%, #fff);
}

.parking-vehicle-meta small,
.parking-more-note {
    color: var(--dash-slate-500);
    font-size: 10.5px;
    font-weight: 700;
}

.parking-more-note {
    padding: 4px 2px 0;
    text-align: center;
}

.list-row {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    min-height: 58px;
    border-bottom: 1px solid var(--dash-line-soft);
    color: inherit;
    text-decoration: none;
    transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
}

.list-row.is-action {
    margin-inline: -8px;
    padding-inline: 8px;
    border-radius: 13px;
    cursor: pointer;
}

.list-row:last-child {
    border-bottom: 0;
}

.list-row.is-action:hover,
.list-row.is-action:focus-visible {
    background: color-mix(in srgb, var(--card-glow, var(--dash-accent)) 6%, transparent);
    box-shadow: 0 10px 22px color-mix(in srgb, var(--dash-secondary) 8%, transparent);
    transform: translateX(3px);
}

.list-row.is-action:active {
    transform: translateX(2px) scale(.995);
}

.list-avatar {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: #fff;
    background: linear-gradient(150deg, #6E6BD8, #5A57D2);
    font-size: 12px;
    font-weight: 900;
}

.list-avatar.gold {
    background: linear-gradient(150deg, var(--dash-primary), var(--dash-secondary));
    color: var(--dash-on-brand);
}

.list-name {
    color: var(--dash-navy);
    font-size: 13.5px;
    font-weight: 850;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.list-meta {
    margin-top: 2px;
    color: var(--dash-slate-500);
    font-size: 11.5px;
    font-weight: 650;
}

.list-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
    text-align: right;
}

.list-right-copy {
    min-width: 0;
}

.list-time {
    color: var(--dash-navy);
    font-size: 13px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.list-action-icon {
    width: 24px;
    height: 24px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    border: 1px solid var(--dash-line);
    border-radius: 999px;
    color: var(--dash-gold);
    background: #fff;
    font-size: 10px;
    opacity: .62;
    transition: opacity .18s ease, transform .18s ease, border-color .18s ease, background .18s ease;
}

.list-row.is-action:hover .list-name,
.list-row.is-action:focus-visible .list-name {
    color: var(--dash-secondary);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.list-row.is-action:hover .list-action-icon,
.list-row.is-action:focus-visible .list-action-icon {
    border-color: color-mix(in srgb, var(--dash-gold) 42%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-gold) 8%, #fff);
    opacity: 1;
    transform: translateX(2px);
}

.cash-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.cash-box {
    min-height: 82px;
    padding: 13px;
    border: 1px solid var(--dash-line);
    border-radius: 13px;
    background: var(--dash-surface-warm);
    transition: transform .22s ease, border-color .22s ease, background .22s ease;
}

.cash-box.dark {
    border-color: color-mix(in srgb, var(--dash-primary) 74%, var(--dash-line));
    background: linear-gradient(135deg, var(--dash-primary), var(--dash-secondary));
    animation: none;
}

.cash-box:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--dash-accent) 34%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-accent) 5%, var(--dash-surface-warm));
}

.cash-box.dark:hover {
    transform: none;
    border-color: color-mix(in srgb, var(--dash-primary) 74%, var(--dash-line));
    background: linear-gradient(135deg, var(--dash-primary), var(--dash-secondary));
}

.cash-box .label {
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 850;
}

.cash-box.dark .label {
    color: color-mix(in srgb, var(--dash-on-brand) 82%, transparent);
    text-shadow: 0 1px 1px rgba(0,0,0,.22);
}

.cash-box .amount {
    margin-top: 8px;
    color: var(--dash-navy);
    font-size: clamp(14px, 1.15vw, 17px);
    line-height: 1;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.cash-box.dark .amount {
    color: var(--dash-on-brand);
    text-shadow: 0 1px 2px rgba(0,0,0,.2);
}

.button-row {
    display: flex;
    gap: 10px;
    margin-top: 14px;
}

.dash-btn {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 13px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 850;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
}

.dash-btn.primary {
    flex: 1;
    background: var(--dash-primary);
    color: var(--dash-on-brand);
}

.dash-btn.ghost {
    border: 1px solid var(--dash-line);
    color: var(--dash-navy);
    background: #fff;
}

.dash-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 13px 24px color-mix(in srgb, var(--dash-secondary) 13%, transparent);
}

.dash-btn:active {
    transform: translateY(0) scale(.99);
}

.empty-state {
    min-height: 140px;
    display: grid;
    place-items: center;
    color: var(--dash-slate-500);
    text-align: center;
    font-size: 13px;
    line-height: 1.45;
}

.notification-panel {
    margin-top: 22px;
    --card-glow: var(--dash-gold);
}

.notification-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.notification-panel-head p {
    margin: 5px 0 0;
    color: var(--dash-slate-500);
    font-size: 13px;
    line-height: 1.45;
}

.notification-panel-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 0 12px;
    border-radius: 10px;
    border: 1px solid var(--dash-line);
    background: color-mix(in srgb, var(--dash-gold) 8%, #fff);
    color: var(--dash-navy);
    font-size: 12px;
    font-weight: 900;
    text-decoration: none;
    white-space: nowrap;
}

.notification-panel-link:hover,
.notification-panel-link:focus-visible {
    color: var(--dash-navy);
    border-color: color-mix(in srgb, var(--dash-gold) 38%, var(--dash-line));
}

.notification-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin: 16px 0;
}

.notification-summary-item {
    padding: 12px;
    border-radius: 12px;
    border: 1px solid var(--dash-line);
    background: color-mix(in srgb, var(--dash-navy) 3%, #fff);
}

.notification-summary-item span {
    display: block;
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.notification-summary-item strong {
    display: block;
    margin-top: 4px;
    color: var(--dash-navy);
    font-size: 22px;
    font-weight: 900;
}

.notification-list {
    display: grid;
    gap: 8px;
}

.notification-row {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) auto;
    gap: 11px;
    align-items: center;
    padding: 10px;
    border: 1px solid var(--dash-line);
    border-radius: 12px;
    background: #fff;
    color: inherit;
    text-decoration: none;
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.notification-row:hover,
.notification-row:focus-visible {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--dash-gold) 32%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-gold) 5%, #fff);
    color: inherit;
}

.notification-row-icon {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    color: var(--dash-navy);
    background: color-mix(in srgb, var(--dash-gold) 13%, #fff);
    border: 1px solid color-mix(in srgb, var(--dash-gold) 28%, var(--dash-line));
}

.notification-row-main {
    min-width: 0;
}

.notification-row-title {
    color: var(--dash-navy);
    font-size: 13px;
    font-weight: 900;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notification-row-meta {
    margin-top: 3px;
    color: var(--dash-slate-500);
    font-size: 11.5px;
}

.notification-row-badge {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: var(--dash-bg-available);
    color: var(--dash-available);
    font-size: 11px;
    font-weight: 900;
    white-space: nowrap;
}

.notification-row-badge.alta,
.notification-row-badge.critica {
    background: var(--dash-bg-critical);
    color: var(--dash-critical);
}

.notification-row-badge.media {
    background: var(--dash-bg-maint);
    color: var(--dash-maint);
}

@media (min-width: 1600px) {
    .dashboard-boutique {
        grid-template-columns: 268px minmax(0, 1fr);
    }

    .boutique-main {
        padding: 24px 30px 46px;
    }

    .editorial-hero {
        min-height: 250px;
    }

    .grid4,
    .grid3,
    .dashboard-main-flow {
        gap: 22px;
        margin-top: 22px;
    }

    .dashboard-left-flow {
        gap: 22px;
    }

    .dashboard-main-flow {
        grid-template-columns: minmax(0, 1fr) clamp(450px, 23vw, 520px);
    }

    .occ-card {
        min-height: 318px;
    }

    .card-pad {
        padding: 24px;
    }
}

@media (min-width: 1920px) {
    .boutique-main {
        padding-left: 34px;
        padding-right: 34px;
    }

    .editorial-hero {
        min-height: 270px;
    }

    .dashboard-main-flow {
        grid-template-columns: minmax(0, 1fr) clamp(480px, 23vw, 540px);
    }
}

@media (max-width: 1280px) {
    .dashboard-boutique {
        grid-template-columns: 232px minmax(0, 1fr);
    }

    .boutique-main {
        padding: 20px;
        padding-bottom: 34px;
    }
}

@media (max-width: 1120px) {
    .grid4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-main-flow {
        grid-template-columns: 1fr;
    }

    .grid3 {
        grid-template-columns: 1fr;
    }

    .notification-summary {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .dashboard-boutique {
        display: block;
    }

    .boutique-main {
        padding: 16px;
    }

    .editorial-hero {
        min-height: 360px;
    }

    .hero-top {
        align-items: flex-start;
        flex-direction: column;
    }


    .grid4,
    .dashboard-main-flow {
        grid-template-columns: 1fr;
    }

    .grid3 {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 560px) {
    .hero-actions {
        width: 100%;
        justify-content: space-between;
    }

    .hotel-switch {
        max-width: 190px;
    }

    .hero-title {
        padding: 0 18px;
    }

    .hero-title h1 {
        font-size: 36px;
    }

    .notification-panel-head {
        display: grid;
    }

    .notification-summary,
    .notification-row {
        grid-template-columns: 1fr;
    }

    .notification-row {
        align-items: start;
    }

    .editorial-hero {
        min-height: 360px;
    }

    .cash-grid {
        grid-template-columns: 1fr;
    }

    .weekly-chart-head {
        grid-template-columns: 1fr;
    }

    .weekly-chart-summary {
        justify-content: stretch;
    }

    .weekly-summary-chip {
        flex: 1 1 120px;
    }

    .weekly-line-chart {
        --weekly-line-plot-height: clamp(200px, 34vw, 240px);
        grid-template-columns: 26px minmax(0, 1fr);
        gap: 8px;
    }

    .weekly-line-plot {
        padding: 30px 8px 10px;
    }

    .weekly-line-svg,
    .weekly-line-points {
        inset: 30px 8px 10px;
    }

    .weekly-line-svg {
        width: calc(100% - 16px);
        height: calc(100% - 40px);
    }

    .weekly-line-labels,
    .weekly-line-foot {
        padding: 0 8px;
    }

    .weekly-line-foot {
        padding-left: 8px;
        padding-right: 8px;
    }

    .weekly-line-label .f {
        display: none;
    }

    .weekly-line-foot-time {
        display: none;
    }

    .weekly-line-tooltip {
        min-width: 150px;
        padding: 9px 10px;
    }

    .weekly-line-tooltip b {
        font-size: 12px;
    }

    .weekly-line-tooltip small {
        font-size: 9px;
        white-space: normal;
    }

    .parking-status-strip {
        grid-template-columns: 1fr;
    }

    .parking-vehicle-row {
        grid-template-columns: 34px minmax(0, 1fr);
        align-items: start;
    }

    .parking-vehicle-meta {
        grid-column: 2;
        justify-items: start;
        text-align: left;
    }
}

/* ============================================================
   COMPACT MOBILE DASHBOARD  ·  Claude Design "mobile-foto"
   Boutique editorial photo hero + tight operational cards.
   Hidden on desktop; replaces the stacked grid layout on phones.
   ============================================================ */
.dash-mobile {
    display: none;
}

.dm-hero {
    position: relative;
    display: flex;
    align-items: flex-end;
    min-height: 200px;
    overflow: hidden;
    border-radius: 0 0 24px 24px;
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    box-shadow: var(--dash-shadow);
    animation: dashCardIn .52s cubic-bezier(.2, .78, .22, 1) both;
}

.dm-hero-scrim {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg,
            color-mix(in srgb, var(--dash-secondary) 68%, transparent) 0%,
            color-mix(in srgb, var(--dash-secondary) 18%, transparent) 32%,
            color-mix(in srgb, var(--dash-secondary) 90%, transparent) 100%),
        linear-gradient(90deg,
            color-mix(in srgb, var(--dash-primary) 45%, transparent) 0%,
            color-mix(in srgb, var(--dash-primary) 14%, transparent) 64%,
            transparent 100%),
        radial-gradient(circle at 78% 30%, color-mix(in srgb, var(--dash-accent) 22%, transparent), transparent 16rem);
    animation: dashHeroLight 7s ease-in-out infinite;
}

.dm-hero-txt {
    position: relative;
    z-index: 1;
    padding: 0 18px 18px;
}

.dm-eyebrow {
    color: color-mix(in srgb, var(--dash-on-brand) 84%, transparent);
    text-shadow: 0 1px 1px rgba(0,0,0,.24);
    font-size: 9.5px;
    font-weight: 800;
    letter-spacing: .18em;
    text-transform: uppercase;
}

.dm-hotel {
    margin: 5px 0 0;
    color: var(--dash-on-brand);
    font-family: var(--dash-serif);
    font-size: 30px;
    line-height: 1;
    font-weight: 650;
    letter-spacing: -.01em;
    text-shadow:
        0 2px 4px rgba(0,0,0,.44),
        0 12px 28px rgba(0,0,0,.5);
}

.dm-greet {
    margin: 6px 0 0;
    color: color-mix(in srgb, var(--dash-on-brand) 92%, transparent);
    font-size: 12.5px;
    font-weight: 700;
    text-shadow:
        0 1px 2px rgba(0,0,0,.45),
        0 9px 22px rgba(0,0,0,.38);
}

.dm-body {
    padding: 0 14px;
}

.dm-sec {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin: 16px 4px 8px;
    color: var(--dash-slate-400);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.dm-sec a {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-height: 44px;
    padding-inline: 4px;
    color: var(--dash-gold);
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: none;
    text-decoration: none;
    transition: color .18s ease, transform .18s ease;
}

.dm-sec a::after {
    content: ">";
    font-size: 10px;
    line-height: 1;
    opacity: .65;
    transition: transform .18s ease, opacity .18s ease;
}

.dm-sec a:hover,
.dm-sec a:focus-visible {
    color: var(--dash-navy);
}

.dm-sec a:hover::after,
.dm-sec a:focus-visible::after {
    opacity: 1;
    transform: translateX(2px);
}

.dm-sec a:active {
    transform: translateY(1px);
}

.dm-card {
    border: 1px solid var(--dash-line);
    border-radius: 15px;
    padding: 14px;
    background: var(--dash-surface);
    box-shadow: var(--dash-shadow);
    animation: dashCardIn .48s cubic-bezier(.2, .78, .22, 1) both;
    transition: transform .22s ease, border-color .22s ease, background .22s ease;
}

.dm-card + .dm-card {
    margin-top: 9px;
}

.dm-body > .dm-card:nth-child(2) {
    animation-delay: .05s;
}

.dm-body > .dm-card:nth-child(4) {
    animation-delay: .1s;
}

.dm-body > .dm-card:nth-child(6) {
    animation-delay: .15s;
}

.dm-body > .dm-card:nth-child(8) {
    animation-delay: .2s;
}

.dm-body > .dm-card:nth-child(10) {
    animation-delay: .25s;
}

.dm-card:active {
    transform: scale(.99);
    border-color: color-mix(in srgb, var(--dash-accent) 28%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-accent) 4%, var(--dash-surface));
}

.dm-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 999px;
    background: var(--dash-bg-available);
    color: var(--dash-available);
    font-size: 11.5px;
    font-weight: 800;
    white-space: nowrap;
}

.dm-badge .led {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    animation: dashPulseDot 2.4s ease-in-out infinite;
}

/* Ocupación */
.dm-occ {
    display: flex;
    align-items: center;
    gap: 15px;
}

.dm-ring {
    position: relative;
    width: 76px;
    height: 76px;
    flex: 0 0 auto;
}

.dm-ring svg {
    width: 76px;
    height: 76px;
}

.dm-ring svg circle[stroke-dasharray] {
    animation: dashRingDraw .85s cubic-bezier(.2, .78, .22, 1) both .12s;
}

.dm-ring b {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    color: var(--dash-navy);
    font-size: 15px;
    font-weight: 820;
    letter-spacing: -.01em;
    font-variant-numeric: tabular-nums;
}

.dm-occ-meta .big {
    color: var(--dash-navy);
    font-size: 21px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-occ-meta .big span {
    color: var(--dash-slate-400);
    font-size: 15px;
    font-weight: 700;
}

.dm-occ-meta .sub {
    margin-top: 1px;
    color: var(--dash-slate-500);
    font-size: 12.5px;
    font-weight: 700;
}

.dm-occ-meta .dm-badge {
    margin-top: 8px;
}

/* Estacionamiento (móvil) */
.dm-sec-note {
    color: var(--dash-gold);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .03em;
    text-transform: none;
    white-space: nowrap;
}
.dm-park { --parking-ring-start: var(--dash-gold-mid); --parking-ring-end: var(--dash-gold); }
.dm-park.is-busy, .dm-park.is-near-capacity { --parking-ring-start: color-mix(in srgb, var(--dash-maint) 54%, #fff); --parking-ring-end: var(--dash-maint); }
.dm-park.is-over-capacity { --parking-ring-start: color-mix(in srgb, var(--dash-critical) 54%, #fff); --parking-ring-end: var(--dash-critical); }
.dm-park.is-unconfigured { --parking-ring-start: color-mix(in srgb, var(--dash-slate-400) 50%, #fff); --parking-ring-end: var(--dash-slate-500); }
.dm-park-top { align-items: center; gap: 14px; }
.dm-park-ring b { display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 18px; }
.dm-park-ring b span { margin-top: 1px; color: var(--dash-slate-400); font-size: 9.5px; font-weight: 700; letter-spacing: 0; }
.dm-park-stats { flex: 1 1 auto; min-width: 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.dm-park-stat { display: flex; flex-direction: column; gap: 3px; padding: 9px 5px; border-radius: 12px; text-align: center; }
.dm-park-stat span { font-size: 9.5px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
.dm-park-stat strong { font-size: 19px; font-weight: 900; line-height: 1; font-variant-numeric: tabular-nums; }
.dm-park-stat.is-occupied { background: var(--dash-bg-occupied); }
.dm-park-stat.is-occupied span, .dm-park-stat.is-occupied strong { color: var(--dash-occupied); }
.dm-park-stat.is-reserved { background: var(--dash-bg-arriving); }
.dm-park-stat.is-reserved span, .dm-park-stat.is-reserved strong { color: var(--dash-arriving); }
.dm-park-stat.is-free { background: var(--dash-bg-available); }
.dm-park-stat.is-free span, .dm-park-stat.is-free strong { color: var(--dash-available); }
.dm-park-bar { margin-top: 14px; }
.dm-park-bar-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 12.5px; font-weight: 800; color: var(--dash-slate-700); }
.dm-park-bar-head i { margin-right: 5px; color: var(--dash-gold); }
.dm-park-bar-head b { color: var(--dash-navy); font-variant-numeric: tabular-nums; }
.dm-park-note { margin-top: 7px; color: var(--dash-slate-500); font-size: 12px; }
.dm-park-types { margin-top: 12px; display: flex; flex-direction: column; gap: 8px; }
.dm-park-type { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border: 1px solid var(--dash-line); border-radius: 12px; background: color-mix(in srgb, var(--dash-gold) 3%, #fff); }
.dm-park-type .nm { display: flex; flex-direction: column; gap: 2px; min-width: 0; color: var(--dash-navy); font-size: 13.5px; font-weight: 850; }
.dm-park-type .nm small { color: var(--dash-slate-500); font-size: 11.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dm-park-type .ct { flex: 0 0 auto; min-width: 46px; text-align: center; padding: 5px 12px; border-radius: 999px; background: var(--dash-surface); border: 1px solid var(--dash-line); color: var(--dash-navy); font-size: 12.5px; font-weight: 900; font-variant-numeric: tabular-nums; }
.dm-park-type.is-over-capacity .ct { background: var(--dash-gold-bg); border-color: var(--dash-gold-line); color: color-mix(in srgb, var(--dash-gold) 62%, var(--dash-ink)); }
.dm-park-type.is-full .ct { background: var(--dash-bg-occupied); border-color: transparent; color: var(--dash-occupied); }

/* Movimientos del día */
.dm-mvtop {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.dm-mvtop .lbl {
    color: var(--dash-slate-400);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.dm-mvtop .bal {
    color: var(--dash-navy);
    font-size: 22px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9px;
    margin-top: 11px;
}

.dm-split .b {
    padding: 9px 11px;
    border: 1px solid var(--dash-line);
    border-radius: 11px;
    background: var(--dash-surface-warm);
}

.dm-split .t {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.dm-split .v {
    margin-top: 4px;
    font-size: 16px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

/* Habitaciones — barras de estado */
.dm-rst {
    display: flex;
    align-items: center;
    gap: 11px;
    min-height: 46px;
    padding: 8px 0;
    border-top: 1px solid var(--dash-line-soft);
}

.dm-rst.is-link {
    margin-inline: -8px;
    padding-inline: 8px;
    max-width: calc(100% + 16px);
    box-sizing: border-box;
    border-radius: 12px;
    color: inherit;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s ease, transform .18s ease;
}

.dm-rst.is-link:hover,
.dm-rst.is-link:focus-visible {
    background: color-mix(in srgb, var(--state-accent, var(--dash-accent)) 8%, transparent);
    transform: translateX(2px);
}

.dm-rst.is-link:active {
    transform: translateX(1px) scale(.995);
}

.dm-rst:first-child {
    border-top: 0;
}

.dm-rst .dt {
    width: 9px;
    height: 9px;
    flex: 0 0 auto;
    border-radius: 50%;
}

.dm-rst .nm {
    color: var(--dash-slate-700);
    font-size: 13.5px;
    font-weight: 700;
    white-space: nowrap;
}

.dm-rst .bar {
    flex: 1;
    height: 6px;
    overflow: hidden;
    border-radius: 99px;
    background: color-mix(in srgb, var(--dash-accent) 14%, #EFEADF);
}

.dm-rst .bar i {
    display: block;
    height: 100%;
    border-radius: 99px;
    transform-origin: left center;
    animation: dashProgressGrow .68s cubic-bezier(.2, .78, .22, 1) both;
}

.dm-rst .ct {
    width: 26px;
    text-align: right;
    color: var(--dash-navy);
    font-size: 15px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-rst .go {
    width: 14px;
    color: var(--dash-slate-400);
    font-size: 10px;
    text-align: right;
    opacity: .55;
    transition: color .18s ease, opacity .18s ease, transform .18s ease;
}

.dm-rst.is-link:hover .go,
.dm-rst.is-link:focus-visible .go {
    color: var(--dash-gold);
    opacity: 1;
    transform: translateX(2px);
}

/* Agenda */
.dm-ag-h {
    margin-bottom: 4px;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.dm-ag {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
    max-width: 100%;
    min-height: 48px;
    overflow: hidden;
    padding: 8px 0;
    border-top: 1px solid var(--dash-line-soft);
    color: inherit;
    text-decoration: none;
    transition: transform .2s ease, background .2s ease;
}

.dm-ag.is-link {
    margin-inline: -8px;
    padding-inline: 8px;
    max-width: calc(100% + 16px);
    box-sizing: border-box;
    border-radius: 12px;
    cursor: pointer;
}

.dm-ag.is-link:hover,
.dm-ag.is-link:focus-visible {
    background: color-mix(in srgb, var(--dash-accent) 6%, transparent);
    transform: translateX(2px);
}

.dm-ag.first {
    border-top: 0;
}

.dm-ag.is-link:active {
    transform: translateX(3px);
    background: color-mix(in srgb, var(--dash-accent) 5%, transparent);
}

.dm-ag .av {
    width: 34px;
    height: 34px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 10px;
    color: #fff;
    font-size: 12px;
    font-weight: 900;
    background: linear-gradient(150deg, #6E6BD8, #5A57D2);
}

.dm-ag .av.gold {
    background: linear-gradient(150deg, var(--dash-primary), var(--dash-secondary));
    color: var(--dash-on-brand);
}

.dm-ag > .min-w-0,
.dm-ag > :not(.av):not(.tm) {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
}

.dm-ag .nm {
    display: block;
    min-width: 0;
    color: var(--dash-navy);
    font-size: 13.5px;
    font-weight: 800;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dm-ag .mt {
    margin-top: 1px;
    min-width: 0;
    color: var(--dash-slate-500);
    font-size: 11.5px;
    font-weight: 650;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dm-ag .tm {
    margin-left: auto;
    flex: 0 0 auto;
    min-width: 54px;
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 7px;
    color: var(--dash-navy);
    font-size: 13px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-ag .tm i {
    color: var(--dash-slate-400);
    font-size: 10px;
    opacity: .55;
    transition: color .18s ease, opacity .18s ease, transform .18s ease;
}

.dm-ag.is-link:hover .nm,
.dm-ag.is-link:focus-visible .nm {
    color: var(--dash-secondary);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.dm-ag.is-link:hover .tm i,
.dm-ag.is-link:focus-visible .tm i {
    color: var(--dash-gold);
    opacity: 1;
    transform: translateX(2px);
}

.dm-ag-div {
    height: 1px;
    margin: 11px 0;
    border: 0;
    background: var(--dash-line);
}

.dm-empty {
    padding: 9px 0;
    color: var(--dash-slate-500);
    font-size: 12.5px;
}

/* Estado de caja */
.dm-caja {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9px;
}

.dm-cbox {
    padding: 11px;
    border: 1px solid var(--dash-line);
    border-radius: 11px;
    background: var(--dash-surface-warm);
    transition: transform .2s ease, border-color .2s ease, background .2s ease;
}

.dm-cbox.dark {
    border-color: color-mix(in srgb, var(--dash-primary) 74%, var(--dash-line));
    background: linear-gradient(135deg, var(--dash-primary), var(--dash-secondary));
    animation: none;
}

.dm-cbox:active {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--dash-accent) 30%, var(--dash-line));
}

.dm-cbox .t {
    color: var(--dash-slate-500);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.dm-cbox.dark .t {
    color: color-mix(in srgb, var(--dash-on-brand) 82%, transparent);
    text-shadow: 0 1px 1px rgba(0,0,0,.22);
}

.dm-cbox .v {
    margin-top: 7px;
    color: var(--dash-navy);
    font-size: 16px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-cbox.dark .v {
    color: var(--dash-on-brand);
    text-shadow: 0 1px 2px rgba(0,0,0,.2);
}

.dm-actions {
    display: flex;
    gap: 9px;
    margin-top: 11px;
}

.dm-btn {
    flex: 1;
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 11px;
    font-size: 12.5px;
    font-weight: 850;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.dm-btn.primary {
    background: var(--dash-primary);
    color: var(--dash-on-brand);
}

.dm-btn.ghost {
    border: 1px solid var(--dash-line);
    background: #fff;
    color: var(--dash-navy);
}

.dm-btn:active {
    transform: scale(.985);
}

@media (max-width: 767px) {
    .dashboard-boutique .editorial-hero,
    .dashboard-boutique .grid4,
    .dashboard-boutique .dashboard-main-flow,
    .dashboard-boutique .grid3 {
        display: none !important;
    }

    .dashboard-boutique .dash-mobile {
        display: block;
    }

    .dashboard-boutique .boutique-main {
        padding: 0 0 30px !important;
    }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-boutique *,
    .dashboard-boutique *::before,
    .dashboard-boutique *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
}
</style>

<div class="dashboard-boutique">
    <main class="boutique-main">
        <section class="editorial-hero">
            <div class="hero-top">
                <div class="hero-date"><?= dashboard_safe($fecha_hero) ?></div>
                <div class="hero-actions">
                    <div class="hotel-switch">
                        <span><?= dashboard_safe($hotel_display_name, 'Medisoft Hoteles') ?></span>
                    </div>
                    <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('notificaciones')): ?>
                    <div class="notification-bell-shell" data-notification-quick>
                        <button type="button"
                                class="glass-button <?= $notificaciones_pendientes > 0 ? 'has-notifications' : '' ?>"
                                aria-label="Ver pendientes de notificaciones"
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-controls="notificationQuickPanel"
                                data-notification-toggle>
                            <i class="fas fa-bell" aria-hidden="true"></i>
                            <?php if ($notificaciones_pendientes > 0): ?>
                                <span class="notification-dot"></span>
                                <span class="notification-count"><?= $notificaciones_pendientes > 99 ? '99+' : (int)$notificaciones_pendientes ?></span>
                            <?php endif; ?>
                        </button>

                        <div class="notification-quick-panel"
                             id="notificationQuickPanel"
                             data-notification-panel
                             hidden>
                            <div class="notification-quick-head">
                                <span>Pendientes</span>
                                <strong data-notification-count><?= (int)$notificaciones_pendientes ?></strong>
                            </div>

                            <div class="notification-quick-list" data-notification-list>
                                <?php if (empty($notificaciones_recientes)): ?>
                                    <div class="notification-quick-empty">Sin pendientes operativos por ahora.</div>
                                <?php else: ?>
                                    <?php foreach (array_slice($notificaciones_recientes, 0, 8) as $notificacionQuick): ?>
                                        <?php
                                        $quickModulo = (string)($notificacionQuick['modulo'] ?? 'sistema');
                                        $quickId = (int)($notificacionQuick['id'] ?? 0);
                                        $quickUrl = trim((string)($notificacionQuick['url'] ?? ''));
                                        $quickHref = ($quickUrl !== '' && $quickId > 0)
                                            ? url('notificaciones/' . $quickId . '/abrir')
                                            : url('notificaciones');
                                        $quickAutomatica = strpos((string)($notificacionQuick['tipo'] ?? ''), 'regla_') === 0;
                                        $quickSeveridad = strtolower((string)($notificacionQuick['severidad'] ?? ''));
                                        $quickSevClass = in_array($quickSeveridad, ['critica', 'alta'], true)
                                            ? ' nq-crit'
                                            : ($quickSeveridad === 'media' ? ' nq-warn' : '');
                                        ?>
                                        <div class="nq-swipe" data-nq-swipe>
                                            <div class="nq-swipe-bg" aria-hidden="true">
                                                <i class="fas fa-check" aria-hidden="true"></i> Archivar
                                            </div>
                                            <a class="notification-quick-row<?= $quickSevClass ?>"
                                               href="<?= htmlspecialchars($quickHref, ENT_QUOTES, 'UTF-8') ?>"
                                               <?= $quickId > 0 ? 'data-archive-url="' . htmlspecialchars(url('notificaciones/' . $quickId . '/descartar'), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                                                <span class="notification-quick-icon">
                                                    <i class="fas <?= dashboard_safe(dashboard_notif_icon($quickModulo), 'fa-bell') ?>" aria-hidden="true"></i>
                                                </span>
                                                <span class="notification-quick-body">
                                                    <span class="notification-quick-title"><?= dashboard_safe($notificacionQuick['titulo'] ?? 'Notificacion') ?></span>
                                                    <span class="notification-quick-meta">
                                                        <?= dashboard_safe(dashboard_notif_label($quickModulo)) ?> · <?= dashboard_safe(dashboard_format_date($notificacionQuick['created_at'] ?? null, 'd/m H:i')) ?>
                                                        <?= $quickAutomatica ? ' · Automática' : ' · Manual' ?>
                                                    </span>
                                                </span>
                                                <span class="notification-quick-go">
                                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                                </span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($notificaciones_recientes)): ?>
                            <div class="notification-quick-hint" data-notification-hint>
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                Desliza una notificación a la izquierda para archivar
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="avatar" style="border-radius:50%"><?= dashboard_safe($usuario_iniciales, 'M') ?></div>
                </div>
            </div>

            <div class="hero-title">
                <h1><?= dashboard_safe($hotel_display_name, 'Medisoft Hoteles') ?></h1>
                <p><?= dashboard_safe($saludo, 'Hola') ?>, <?= dashboard_safe($usuario_nombre, 'usuario') ?> · Operación hotelera · Recepción</p>
            </div>

        </section>

        <section class="grid4" data-ms-stagger>
            <article class="card card-pad occ-card">
                <div class="occ-wave"></div>
                <div class="card-row-head">
                    <div class="mini-icon"><i class="fas fa-bed" aria-hidden="true"></i></div>
                    <div style="text-align:right">
                        <div class="card-title">Ocupación</div>
                        <div class="occ-percent" data-ms-count><?= dashboard_percent_text($ocupacion_pct) ?></div>
                    </div>
                </div>
                <div class="occ-meta">
                    <strong data-ms-count><?= $habitaciones_ocupadas ?></strong>
                    <span class="muted">/ <?= $habitaciones_total ?> habitaciones ocupadas</span>
                    <div class="soft-note"><?= $habitaciones_libres ?> disponibles esta noche</div>
                    <a class="card-kicker-link ms-pressable" href="<?= url('habitaciones') ?>" title="Ver el tablero de habitaciones">
                        Ver habitaciones
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </article>

            <article class="card card-pad cash-day-card">
                <div class="card-row-head" style="justify-content:flex-start">
                    <div class="mini-icon" style="background:var(--dash-bg-available);color:var(--dash-available)">
                        <i class="fas fa-dollar-sign" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="card-title">Movimientos del día</div>
                        <div class="cash-day-total"><?= format_money($balance_dia) ?></div>
                        <div class="cash-day-subtitle">Resultado de hoy</div>
                    </div>
                </div>
                <div class="cash-day-groups">
                    <div class="cash-day-group">
                        <div class="cash-day-group-title income">
                            <i class="fas fa-arrow-trend-up" aria-hidden="true"></i>
                            Dinero que quedo
                        </div>
                        <div class="cash-day-row"><span>Efectivo</span><strong><?= format_money($stats['ingresos']['efectivo_dia'] ?? 0) ?></strong></div>
                        <div class="cash-day-row"><span>Tarjeta</span><strong><?= format_money($stats['ingresos']['tarjeta_dia'] ?? 0) ?></strong></div>
                        <div class="cash-day-row"><span>Transferencia</span><strong><?= format_money($stats['ingresos']['transferencia_dia'] ?? 0) ?></strong></div>
                    </div>
                    <div class="cash-day-group">
                        <div class="cash-day-group-title expense">
                            <i class="fas fa-arrow-trend-down" aria-hidden="true"></i>
                            Gastos del hotel
                        </div>
                        <div class="cash-day-row"><span>Efectivo</span><strong><?= format_money($stats['egresos']['efectivo_dia'] ?? 0) ?></strong></div>
                        <div class="cash-day-row"><span>Tarjeta</span><strong><?= format_money($stats['egresos']['tarjeta_dia'] ?? 0) ?></strong></div>
                        <div class="cash-day-row"><span>Transferencia</span><strong><?= format_money($stats['egresos']['transferencia_dia'] ?? 0) ?></strong></div>
                    </div>
                </div>
                <div class="cash-day-balance"><span>Dinero que entro</span><strong><?= format_money($ingresos_brutos_total) ?></strong></div>
                <div class="cash-day-balance"><span>Devuelto/cancelado</span><strong>-<?= format_money(abs($reversos_total)) ?></strong></div>
                <div class="cash-day-balance"><span>Resultado</span><strong><?= format_money($balance_dia) ?></strong></div>
                <a class="card-kicker-link" href="<?= url('caja') ?>" title="Ir a caja para revisar movimientos">
                    Revisar caja
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>

            <article class="card card-pad">
                <div class="card-row-head" style="justify-content:flex-start">
                    <div class="mini-icon" style="background:var(--dash-bg-arriving);color:var(--dash-arriving)">
                        <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="card-title">Movimientos</div>
                        <div class="metric-total"><?= $entradas_total + $salidas_total ?></div>
                    </div>
                </div>
                <div style="margin-top:18px">
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-available)"></i>Entradas</span><strong><?= $entradas_total ?></strong></div>
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-maint)"></i>Salidas</span><strong><?= $salidas_total ?></strong></div>
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-arriving)"></i>Pendientes</span><strong><?= $entradas_pendientes + $salidas_pendientes ?></strong></div>
                </div>
                <div class="status-badge"><span class="led"></span>Jornada en curso</div>
                <a class="card-kicker-link" href="<?= url('reservaciones') ?>" title="Ver listado de reservaciones">
                    Ver reservaciones
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>

            <article class="card card-pad">
                <div class="card-row-head" style="justify-content:flex-start">
                    <div class="mini-icon" style="background:var(--dash-gold-bg);color:var(--dash-gold)">
                        <i class="fas fa-building" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="card-title">Habitaciones</div>
                        <div class="metric-total"><?= $habitaciones_total ?></div>
                    </div>
                </div>
                <div style="margin-top:14px">
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=disponible') ?>" style="--swatch:var(--dash-available)" title="Ver habitaciones disponibles">
                        <span><i class="swatch" style="--swatch:var(--dash-available)"></i>Disponibles</span>
                        <span class="legend-value"><strong><?= $habitaciones_disponibles ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=ocupada') ?>" style="--swatch:var(--dash-occupied)" title="Ver habitaciones ocupadas">
                        <span><i class="swatch" style="--swatch:var(--dash-occupied)"></i>Ocupadas</span>
                        <span class="legend-value"><strong><?= $habitaciones_ocupadas ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=por_llegar') ?>" style="--swatch:var(--dash-arriving)" title="Ver habitaciones por llegar">
                        <span><i class="swatch" style="--swatch:var(--dash-arriving)"></i>Por llegar</span>
                        <span class="legend-value"><strong><?= $habitaciones_por_llegar ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=limpieza') ?>" style="--swatch:var(--dash-cleaning)" title="Ver habitaciones en limpieza">
                        <span><i class="swatch" style="--swatch:var(--dash-cleaning)"></i>Limpieza</span>
                        <span class="legend-value"><strong><?= $habitaciones_limpieza ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=mantenimiento') ?>" style="--swatch:var(--dash-maint)" title="Ver habitaciones en mantenimiento">
                        <span><i class="swatch" style="--swatch:var(--dash-maint)"></i>Mantenimiento</span>
                        <span class="legend-value"><strong><?= $habitaciones_mantenimiento ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                </div>
            </article>
        </section>

        <?php if (isset($guardian_resumen) && is_array($guardian_resumen)): ?>
            <?php $guardian_nuevos = (int) ($guardian_resumen['nuevos'] ?? 0); ?>
            <!-- Ficha discreta del Guardian: solo roles con guardian.view (verde casi siempre) -->
            <a href="<?= url('ia/vigilancia-financiera') ?>" class="card ms-pressable"
               style="display:flex;align-items:center;gap:14px;padding:14px 18px;text-decoration:none;margin-top:14px;border-left:4px solid <?= $guardian_nuevos > 0 ? 'var(--dash-maint, #C2841C)' : 'var(--dash-available, #1E9E63)' ?>;">
                <div class="mini-icon" style="flex:0 0 auto;background:<?= $guardian_nuevos > 0 ? 'var(--dash-bg-maint, #FAF0DC)' : 'var(--dash-bg-available, #E7F4EC)' ?>;color:<?= $guardian_nuevos > 0 ? 'var(--dash-maint, #C2841C)' : 'var(--dash-available, #1E9E63)' ?>;">
                    <i class="fas fa-shield-halved" aria-hidden="true"></i>
                </div>
                <div style="min-width:0;flex:1;">
                    <div class="card-title">El Guardi&aacute;n</div>
                    <div class="soft-note" style="margin-top:2px;">
                        <?= $guardian_nuevos > 0
                            ? $guardian_nuevos . ' patr&oacute;n(es) a revisar &mdash; conviene confirmar con el equipo'
                            : 'Todo en orden: nada se sale del patr&oacute;n de tu hotel' ?>
                    </div>
                </div>
                <i class="fas fa-arrow-right" aria-hidden="true" style="flex:0 0 auto;opacity:.55;"></i>
            </a>
        <?php endif; ?>

        <section class="dashboard-main-flow">
            <div class="dashboard-left-flow">
            <article class="card card-pad weekly-occupancy-card">
                <div class="weekly-chart-head">
                    <div>
                        <h2>Ocupación semanal</h2>
                        <p>Habitaciones ocupadas por dia frente al total activo.</p>
                    </div>
                    <div class="weekly-chart-summary">
                        <div class="weekly-summary-chip">
                            <span>Promedio semanal</span>
                            <strong><?= dashboard_safe($weekly_chart_avg_label) ?></strong>
                        </div>
                        <div class="weekly-summary-chip">
                            <span>Día pico</span>
                            <strong><?= dashboard_safe($weekly_chart_peak_day) ?> <?= dashboard_safe($weekly_line_peak_date) ?></strong>
                            <em><?= dashboard_safe(dashboard_room_count_text($weekly_chart_peak)) ?> ocupadas</em>
                        </div>
                    </div>
                </div>
                <?php if (empty($weekly_chart_rows)): ?>
                    <div class="empty-state">Sin datos de ocupación semanal para mostrar.</div>
                <?php else: ?>
                    <div class="weekly-line-chart" aria-label="Gráfica de ocupación semanal">
                        <div class="weekly-line-axis">
                            <span><?= dashboard_room_count_text($weekly_axis_top, true) ?></span>
                            <span><?= dashboard_room_count_text($weekly_axis_75, true) ?></span>
                            <span><?= dashboard_room_count_text($weekly_axis_50, true) ?></span>
                            <span><?= dashboard_room_count_text($weekly_axis_25, true) ?></span>
                            <span>0 hab.</span>
                        </div>
                        <div class="weekly-line-body">
                            <div class="weekly-line-plot">
                                <svg class="weekly-line-svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="weeklyLineFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop class="weekly-line-stop-start" offset="0%"></stop>
                                            <stop class="weekly-line-stop-end" offset="100%"></stop>
                                        </linearGradient>
                                    </defs>
                                    <path class="weekly-line-area" d="<?= dashboard_safe($weekly_area_path, '') ?>" fill="url(#weeklyLineFill)"></path>
                                    <path class="weekly-line-stroke" d="<?= dashboard_safe($weekly_line_path, '') ?>"></path>
                                </svg>
                                <div class="weekly-line-points">
                                    <?php foreach ($weekly_line_rows as $row): ?>
                                        <?php $point_class = 'weekly-line-point' . ($row['es_pico'] ? ' is-peak' : ''); ?>
                                        <div class="<?= $point_class ?>" style="left: <?= round($row['x'], 2) ?>%; bottom: <?= round($row['pct'], 2) ?>%" tabindex="0" aria-label="<?= dashboard_safe($row['dia']) ?> <?= dashboard_safe($row['fecha']) ?>: <?= dashboard_safe(dashboard_room_count_text($row['ocupadas'])) ?> ocupadas">
                                            <span class="weekly-line-value"><?= dashboard_safe(dashboard_room_count_text($row['ocupadas'], true)) ?></span>
                                            <span class="weekly-line-dot"></span>
                                            <span class="weekly-line-tooltip" aria-hidden="true">
                                                <b><?= dashboard_safe(dashboard_room_count_text($row['ocupadas'])) ?> ocupadas</b>
                                                <small><?= dashboard_safe($row['dia']) ?> <?= dashboard_safe($row['fecha']) ?> &middot; <?= dashboard_safe(dashboard_room_count_text($weekly_total_active)) ?> activas</small>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="weekly-line-labels">
                                <?php foreach ($weekly_line_rows as $row): ?>
                                    <?php $label_class = 'weekly-line-label' . ($row['es_hoy'] ? ' is-today' : '') . ($row['es_pico'] ? ' is-peak' : ''); ?>
                                    <div class="<?= $label_class ?>">
                                        <span class="d"><?= dashboard_safe($row['dia']) ?></span>
                                        <span class="f"><?= dashboard_safe($row['fecha']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="weekly-line-foot">
                                <span class="weekly-line-foot-info">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"></circle><path d="M12 11v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><circle cx="12" cy="8" r="1" fill="currentColor"></circle></svg>
                                    Basado en <?= (int)$weekly_total_active ?> habitaciones activas
                                </span>
                                <span class="weekly-line-foot-time">Última actualización: Hoy <?= dashboard_safe($weekly_line_updated_at) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </article>

            <?php
            // Ficha discreta del bloque canal_whatsapp: cuantos mensajes ya
            // redactados esperan un toque hoy (y aviso si falta configuracion).
            $waPendientes = (int) ($mensajes_whatsapp['pendientes'] ?? 0);
            $waFaltantes = $mensajes_whatsapp['faltantes'] ?? [];
            ?>
            <?php if (!empty($mensajes_whatsapp) && ($waPendientes > 0 || !empty($waFaltantes))): ?>
                <a class="card card-pad dashboard-wa-pending" href="<?= url('mensajes') ?>"
                   style="display:flex;align-items:center;gap:14px;margin-bottom:14px;text-decoration:none;"
                   title="Abrir la cola de Mensajes">
                    <span style="flex:none;width:42px;height:42px;border-radius:13px;display:grid;place-items:center;font-size:1.1rem;color:#1E9E63;background:#E7F4EC;" aria-hidden="true">
                        <i class="fab fa-whatsapp"></i>
                    </span>
                    <span style="min-width:0;flex:1;display:grid;gap:2px;">
                        <?php if ($waPendientes > 0): ?>
                            <span class="list-name"><?= $waPendientes ?> mensaje<?= $waPendientes === 1 ? '' : 's' ?> de WhatsApp por enviar hoy</span>
                            <span class="list-meta">Confirmaciones, recordatorios y encuestas ya redactados — solo falta tocar Enviar.</span>
                        <?php else: ?>
                            <span class="list-name">Mensajes WhatsApp: falta un dato para arrancar</span>
                        <?php endif; ?>
                        <?php if (!empty($waFaltantes)): ?>
                            <span class="list-meta" style="color:#C2841C;font-weight:650;">
                                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                Falta capturar <?= implode(' y ', array_map(static function ($f) { return $f === 'datos_deposito' ? 'los datos de depósito' : 'el link de Maps'; }, $waFaltantes)) ?> en la configuración.
                            </span>
                        <?php endif; ?>
                    </span>
                    <span class="list-action-icon" aria-hidden="true" style="flex:none;"><i class="fas fa-arrow-right"></i></span>
                </a>
            <?php endif; ?>

            <section class="grid3 dashboard-flow-cards">
            <article class="card card-pad">
                <div class="section-head">
                    <h2 style="font-size:21px">Próximas llegadas</h2>
                    <span class="status-badge" style="margin-top:0;background:var(--dash-bg-arriving);color:var(--dash-arriving)"><?= count($proximas_llegadas) ?></span>
                </div>
                <?php if (empty($proximas_llegadas)): ?>
                    <div class="empty-state">Sin llegadas programadas para hoy.</div>
                <?php else: ?>
                    <?php foreach (array_slice($proximas_llegadas, 0, 3) as $llegada): ?>
                        <?php
                        $hora_llegada = !empty($llegada['hora_llegada_estimada'])
                            ? date('H:i', strtotime($llegada['hora_llegada_estimada']))
                            : 'Por definir';
                        $nombre_llegada = $llegada['huesped_nombre'] ?? 'Huésped pendiente';
                        ?>
                        <a class="list-row is-action" href="<?= url('reservaciones/ver/' . (int)$llegada['id']) ?>" title="Ver detalle de la reservación de <?= dashboard_safe($nombre_llegada) ?>">
                            <div class="list-avatar"><?= dashboard_safe(dashboard_initials($nombre_llegada), 'H') ?></div>
                            <div>
                                <div class="list-name"><?= dashboard_safe($nombre_llegada) ?></div>
                                <div class="list-meta"><?= dashboard_safe($llegada['habitaciones'] ?? null, 'Sin habitación') ?></div>
                            </div>
                            <div class="list-right">
                                <div class="list-right-copy">
                                    <div class="list-time"><?= dashboard_safe($hora_llegada) ?></div>
                                    <div class="list-meta">hoy</div>
                                </div>
                                <span class="list-action-icon" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </article>

            <article class="card card-pad">
                <div class="section-head">
                    <h2 style="font-size:21px">Próximas salidas</h2>
                    <span class="status-badge warn" style="margin-top:0"><?= count($proximas_salidas) ?></span>
                </div>
                <?php if (empty($proximas_salidas)): ?>
                    <div class="empty-state">Sin salidas programadas para hoy.</div>
                <?php else: ?>
                    <?php foreach (array_slice($proximas_salidas, 0, 3) as $salida): ?>
                        <?php
                        $nombre_salida = $salida['huesped_nombre'] ?? 'Huésped pendiente';
                        $hora_salida = !empty($salida['hora_salida'])
                            ? date('H:i', strtotime($salida['hora_salida']))
                            : (!empty($salida['hora_entrada']) ? date('H:i', strtotime($salida['hora_entrada'])) : 'Pendiente');
                        ?>
                        <a class="list-row is-action" href="<?= url('reservaciones/ver/' . (int)$salida['id']) ?>" title="Ver detalle de la reservación de <?= dashboard_safe($nombre_salida) ?>">
                            <div class="list-avatar gold"><?= dashboard_safe(dashboard_initials($nombre_salida), 'H') ?></div>
                            <div>
                                <div class="list-name"><?= dashboard_safe($nombre_salida) ?></div>
                                <div class="list-meta"><?= dashboard_safe($salida['habitaciones'] ?? null, 'Sin habitación') ?></div>
                            </div>
                            <div class="list-right">
                                <div class="list-right-copy">
                                    <div class="list-time"><?= dashboard_safe($hora_salida) ?></div>
                                    <div class="list-meta"><?= !empty($salida['hora_salida']) ? 'en regla' : 'pendiente' ?></div>
                                </div>
                                <span class="list-action-icon" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </article>

            <article class="card card-pad">
                <div class="section-head">
                    <h2 style="font-size:21px">Estado de caja</h2>
                    <span class="status-badge <?= $caja_abierta ? '' : 'warn' ?>" style="margin-top:0">
                        <span class="led"></span>
                        <?= $caja_abierta ? 'Corte abierto' : 'Corte pendiente' ?>
                        <?php if ($caja_info && !empty($caja_info['fecha_apertura'])): ?>
                            · <?= date('H:i', strtotime($caja_info['fecha_apertura'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="cash-grid">
                    <?php
                    $dash_caja_ingreso_neto = (float)($caja_info['total_ingresos_netos'] ?? $ingresos_total);
                    $dash_caja_reversos = (float)($caja_info['total_reversos'] ?? $reversos_total);
                    $dash_caja_gastos_reales = (float)($caja_info['total_gastos_reales'] ?? $egresos_total);
                    ?>
                    <div class="cash-box">
                        <div class="label">Inicial</div>
                        <div class="amount"><?= format_money($caja_info['monto_inicial'] ?? 0) ?></div>
                    </div>
                    <div class="cash-box">
                        <div class="label">Dinero que quedo</div>
                        <div class="amount" style="color:var(--dash-available)"><?= $dash_caja_ingreso_neto >= 0 ? '+' : '-' ?><?= format_money(abs($dash_caja_ingreso_neto)) ?></div>
                    </div>
                    <div class="cash-box">
                        <div class="label">Devuelto/cancelado</div>
                        <div class="amount" style="color:var(--dash-critical)">-<?= format_money(abs($dash_caja_reversos)) ?></div>
                    </div>
                    <div class="cash-box">
                        <div class="label">Gastos del hotel</div>
                        <div class="amount" style="color:var(--dash-critical)">-<?= format_money(abs($dash_caja_gastos_reales)) ?></div>
                    </div>
                    <div class="cash-box dark">
                        <div class="label">Esperado</div>
                        <div class="amount"><?= format_money($caja_info['efectivo_esperado'] ?? $balance_dia) ?></div>
                    </div>
                </div>
                <div class="button-row">
                    <a href="<?= url('caja') ?>" class="dash-btn primary" title="Registrar un movimiento en caja"><i class="fas fa-plus" aria-hidden="true"></i>Registrar movimiento</a>
                    <a href="<?= url('caja') ?>" class="dash-btn ghost" title="Ver el corte actual"><i class="fas fa-eye" aria-hidden="true"></i>Ver corte</a>
                </div>
            </article>
            </section>
            </div>
            <article class="card card-pad dashboard-parking-card <?= dashboard_safe($estado_visual_estacionamiento) ?>">
                <div class="section-head">
                    <h2>Estacionamiento</h2>
                    <span class="parking-head-note"><?= dashboard_safe($nota_cabeza_estacionamiento) ?></span>
                </div>
                <div class="park-ring">
                    <div class="ring-box">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <circle cx="60" cy="60" r="50" fill="none" style="stroke:var(--dash-line)" stroke-width="11"></circle>
                            <circle cx="60" cy="60" r="50" fill="none" stroke="url(#parkingGold)" stroke-width="11" stroke-linecap="round" stroke-dasharray="<?= $ring_circumference ?>" stroke-dashoffset="<?= $ring_offset ?>" transform="rotate(-90 60 60)"></circle>
                            <defs>
                                <linearGradient id="parkingGold" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" style="stop-color:var(--parking-ring-start)"></stop>
                                    <stop offset="1" style="stop-color:var(--parking-ring-end)"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="ring-num"><div><b><?= $vehiculos_estacionados ?></b><span><?= dashboard_safe($etiqueta_limite_estacionamiento) ?></span></div></div>
                    </div>
                    <div class="parking-status-strip" aria-label="Estado de lugares de estacionamiento">
                        <div class="parking-status-pill is-occupied">
                            <span><i class="fas fa-car-side" aria-hidden="true"></i> Ocupados</span>
                            <strong><?= $vehiculos_ocupados_fisicos ?></strong>
                        </div>
                        <div class="parking-status-pill is-reserved">
                            <span><i class="fas fa-calendar-check" aria-hidden="true"></i> Apartados</span>
                            <strong><?= $vehiculos_apartados_reserva ?></strong>
                        </div>
                        <div class="parking-status-pill is-free">
                            <span><i class="fas fa-check-circle" aria-hidden="true"></i> Libres</span>
                            <strong><?= $espacios_disp ?></strong>
                        </div>
                    </div>
                    <div class="park-bar">
                        <div class="park-bar-head">
                            <span><i class="fas fa-car" style="color:var(--parking-ring-end)" aria-hidden="true"></i> Cupo comprometido</span>
                            <b><?= dashboard_safe($etiqueta_progreso_estacionamiento) ?></b>
                        </div>
                        <div class="progress"><i style="--progress:<?= $pct_estacionamiento ?>%"></i></div>
                        <div class="soft-note">
                            <?php if (!$estacionamiento_tiene_cupo): ?>
                                Configura el cupo activo en Configuracion para medir disponibilidad real.
                            <?php elseif ($espacios_excedidos > 0): ?>
                                <?= $espacios_excedidos ?> espacios sobre el limite configurado
                            <?php else: ?>
                                <?= $espacios_disp ?> espacios disponibles · <?= $vehiculos_registrados_total ?> vehiculos registrados
                            <?php endif; ?>
                        </div>
                        <div class="parking-breakdown" aria-label="Estacionamientos configurados">
                            <?php foreach ($resumen_estacionamientos_dashboard as $parkingItem): ?>
                                <?php
                                $parkingTotal = (int)($parkingItem['total'] ?? 0);
                                $parkingCapacity = (int)($parkingItem['cupo'] ?? 0);
                                $parkingAvailable = (int)($parkingItem['disponibles'] ?? 0);
                                $parkingOver = (int)($parkingItem['sobrecupo'] ?? 0);
                                $parkingClasses = ['parking-breakdown-item'];
                                if ($parkingTotal > 0) {
                                    $parkingClasses[] = 'has-vehicles';
                                }
                                if ($parkingCapacity <= 0) {
                                    $parkingClasses[] = 'is-unconfigured';
                                } elseif ($parkingOver > 0) {
                                    $parkingClasses[] = 'is-over-capacity';
                                } elseif ($parkingAvailable === 0 && $parkingTotal > 0) {
                                    $parkingClasses[] = 'is-full';
                                }
                                $parkingDetail = $parkingCapacity > 0
                                    ? ((int)($parkingItem['ocupados'] ?? 0) . ' ocupados · ' . (int)($parkingItem['apartados'] ?? 0) . ' apartados · ' . $parkingAvailable . ' libres · ' . (int)($parkingItem['registrados'] ?? 0) . ' registrados')
                                    : ((int)($parkingItem['ocupados'] ?? 0) . ' ocupados · ' . (int)($parkingItem['apartados'] ?? 0) . ' apartados · ' . (int)($parkingItem['registrados'] ?? 0) . ' registrados · sin cupo');
                                if ($parkingOver > 0) {
                                    $parkingDetail .= ' · +' . $parkingOver . ' sobre cupo';
                                }
                                ?>
                                <div class="<?= implode(' ', $parkingClasses) ?>">
                                    <span>
                                        <?= dashboard_safe($parkingItem['label'] ?? 'Estacionamiento') ?>
                                        <small><?= dashboard_safe($parkingDetail) ?></small>
                                    </span>
                                    <strong><?= $parkingCapacity > 0 ? ($parkingTotal . '/' . $parkingCapacity) : $parkingTotal ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="parking-vehicle-list" aria-label="Vehiculos vinculados al estacionamiento">
                        <?php if (empty($lista_vehiculos_estacionamiento)): ?>
                            <div class="empty-state" style="min-height:90px">Sin vehiculos con check-in programado para hoy.</div>
                        <?php else: ?>
                            <?php foreach (array_slice($lista_vehiculos_estacionamiento, 0, 5) as $vehiculoParking): ?>
                                <?php
                                $parkingHref = !empty($vehiculoParking['reservacion_id'])
                                    ? url('reservaciones/ver/' . (int)$vehiculoParking['reservacion_id'])
                                    : url('huespedes/' . (int)($vehiculoParking['huesped_id'] ?? 0));
                                $parkingMeta = trim((string)($vehiculoParking['habitaciones'] ?? ''));
                                $parkingMeta = $parkingMeta !== ''
                                    ? 'Hab. ' . $parkingMeta . ' · ' . ($vehiculoParking['parking_label'] ?? 'Estacionamiento')
                                    : ($vehiculoParking['parking_label'] ?? 'Estacionamiento');
                                $parkingPlate = trim((string)($vehiculoParking['placas'] ?? ''));
                                $parkingName = trim((string)($vehiculoParking['vehiculo'] ?? 'Vehiculo registrado'));
                                if ($parkingPlate !== '') {
                                    $parkingName .= ' · ' . $parkingPlate;
                                }
                                ?>
                                <a class="parking-vehicle-row is-<?= dashboard_safe($vehiculoParking['status_class'] ?? 'registered') ?>"
                                   href="<?= $parkingHref ?>"
                                   title="Ver <?= !empty($vehiculoParking['reservacion_id']) ? 'reservacion' : 'huesped' ?> de <?= dashboard_safe($vehiculoParking['huesped'] ?? 'huesped') ?>">
                                    <span class="parking-vehicle-icon"><i class="fas <?= dashboard_safe($vehiculoParking['status_icon'] ?? 'fa-car') ?>" aria-hidden="true"></i></span>
                                    <span class="parking-vehicle-copy">
                                        <strong><?= dashboard_safe($parkingName) ?></strong>
                                        <em><?= dashboard_safe($vehiculoParking['huesped'] ?? 'Huesped') ?> · <?= dashboard_safe($parkingMeta) ?></em>
                                    </span>
                                    <span class="parking-vehicle-meta">
                                        <b><?= dashboard_safe($vehiculoParking['status_label'] ?? 'Registrado') ?></b>
                                        <small><?= dashboard_safe($vehiculoParking['status_meta'] ?? '') ?></small>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (count($lista_vehiculos_estacionamiento) > 5): ?>
                                <div class="parking-more-note">+<?= count($lista_vehiculos_estacionamiento) - 5 ?> vehiculos adicionales registrados</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </section>

        <!-- ════════════════════════════════════════════════════════════
             VISTA MÓVIL COMPACTA · Claude Design "mobile-foto"
             Solo visible en teléfonos (≤767px). Usa los mismos datos PHP
             que el layout de escritorio; no añade consultas ni lógica.
             ════════════════════════════════════════════════════════════ -->
        <div class="dash-mobile">
            <header class="dm-hero" style="background-image:url('<?= htmlspecialchars($hero_image_url, ENT_QUOTES, 'UTF-8') ?>')">
                <div class="dm-hero-scrim"></div>
                <div class="dm-hero-txt">
                    <div class="dm-eyebrow"><?= dashboard_safe($fecha_hero) ?></div>
                    <h1 class="dm-hotel"><?= dashboard_safe($hotel_display_name, 'Medisoft Hoteles') ?></h1>
                    <p class="dm-greet"><?= dashboard_safe($saludo, 'Hola') ?>, <?= dashboard_safe($usuario_nombre, 'usuario') ?></p>
                </div>
            </header>

            <div class="dm-body">
                <div class="dm-sec"><span>Ocupación</span></div>
                <div class="dm-card">
                    <div class="dm-occ">
                        <div class="dm-ring">
                            <svg viewBox="0 0 100 100" aria-hidden="true">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="var(--dash-line)" stroke-width="9"></circle>
                                <circle cx="50" cy="50" r="42" fill="none" stroke="var(--dash-navy)" stroke-width="9" stroke-linecap="round"
                                    stroke-dasharray="<?= $m_ring_circ ?>" stroke-dashoffset="<?= $m_ring_offset ?>" transform="rotate(-90 50 50)"></circle>
                            </svg>
                            <b><?= dashboard_percent_text($ocupacion_pct) ?></b>
                        </div>
                        <div class="dm-occ-meta">
                            <div class="big"><?= $habitaciones_ocupadas ?> <span>/ <?= $habitaciones_total ?></span></div>
                            <div class="sub">habitaciones ocupadas</div>
                            <span class="dm-badge"><span class="led"></span> <?= $habitaciones_libres ?> disponibles</span>
                        </div>
                    </div>
                </div>

                <div class="dm-sec"><span>Estacionamiento</span><span class="dm-sec-note"><?= dashboard_safe($nota_cabeza_estacionamiento) ?></span></div>
                <div class="dm-card dm-park <?= dashboard_safe($estado_visual_estacionamiento) ?>">
                    <div class="dm-occ dm-park-top">
                        <div class="dm-ring dm-park-ring">
                            <svg viewBox="0 0 100 100" aria-hidden="true">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="var(--dash-line)" stroke-width="9"></circle>
                                <circle cx="50" cy="50" r="42" fill="none" stroke="url(#dmParkingRing)" stroke-width="9" stroke-linecap="round"
                                    stroke-dasharray="264" stroke-dashoffset="<?= round(264 - (264 * $pct_estacionamiento / 100), 1) ?>" transform="rotate(-90 50 50)"></circle>
                                <defs>
                                    <linearGradient id="dmParkingRing" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0" style="stop-color:var(--parking-ring-start, var(--dash-gold-mid))"></stop>
                                        <stop offset="1" style="stop-color:var(--parking-ring-end, var(--dash-gold))"></stop>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <b><?= $vehiculos_estacionados ?><span><?= dashboard_safe($etiqueta_limite_estacionamiento) ?></span></b>
                        </div>
                        <div class="dm-park-stats" aria-label="Estado de lugares de estacionamiento">
                            <div class="dm-park-stat is-occupied"><span>Ocupados</span><strong><?= $vehiculos_ocupados_fisicos ?></strong></div>
                            <div class="dm-park-stat is-reserved"><span>Apartados</span><strong><?= $vehiculos_apartados_reserva ?></strong></div>
                            <div class="dm-park-stat is-free"><span>Libres</span><strong><?= $espacios_disp ?></strong></div>
                        </div>
                    </div>
                    <div class="dm-park-bar">
                        <div class="dm-park-bar-head">
                            <span><i class="fas fa-car" aria-hidden="true"></i> Cupo comprometido</span>
                            <b><?= dashboard_safe($etiqueta_progreso_estacionamiento) ?></b>
                        </div>
                        <div class="progress"><i style="--progress:<?= $pct_estacionamiento ?>%"></i></div>
                        <div class="dm-park-note">
                            <?php if (!$estacionamiento_tiene_cupo): ?>
                                Configura el cupo en Configuración para medir disponibilidad.
                            <?php elseif ($espacios_excedidos > 0): ?>
                                <?= $espacios_excedidos ?> sobre el límite · <?= $vehiculos_registrados_total ?> vehículos registrados
                            <?php else: ?>
                                <?= $espacios_disp ?> espacios disponibles · <?= $vehiculos_registrados_total ?> vehículos registrados
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($resumen_estacionamientos_dashboard)): ?>
                        <div class="dm-park-types" aria-label="Estacionamientos por tipo">
                            <?php foreach ($resumen_estacionamientos_dashboard as $parkingItem): ?>
                                <?php
                                $pTotal = (int)($parkingItem['total'] ?? 0);
                                $pCupo  = (int)($parkingItem['cupo'] ?? 0);
                                $pDisp  = (int)($parkingItem['disponibles'] ?? 0);
                                $pOver  = (int)($parkingItem['sobrecupo'] ?? 0);
                                $pOcc   = (int)($parkingItem['ocupados'] ?? 0);
                                $pApar  = (int)($parkingItem['apartados'] ?? 0);
                                $pReg   = (int)($parkingItem['registrados'] ?? 0);
                                $pClass = 'dm-park-type';
                                if ($pCupo <= 0) { $pClass .= ' is-unconfigured'; }
                                elseif ($pOver > 0) { $pClass .= ' is-over-capacity'; }
                                elseif ($pDisp === 0 && $pTotal > 0) { $pClass .= ' is-full'; }
                                if ($pCupo > 0) {
                                    $pDetail = $pOcc . ' ocup · ' . $pApar . ' apart · ' . $pDisp . ' libres';
                                } else {
                                    $pDetail = $pOcc . ' ocup · ' . $pReg . ' registrado' . ($pReg === 1 ? '' : 's');
                                }
                                if ($pOver > 0) { $pDetail .= ' · +' . $pOver . ' sobre cupo'; }
                                $pBadge = $pCupo > 0 ? ($pTotal . '/' . $pCupo) : (string)$pTotal;
                                ?>
                                <div class="<?= $pClass ?>">
                                    <span class="nm"><?= dashboard_safe($parkingItem['label'] ?? 'Estacionamiento') ?><small><?= dashboard_safe($pDetail) ?></small></span>
                                    <strong class="ct"><?= dashboard_safe($pBadge) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="dm-sec"><span>Movimientos del día</span></div>
                <div class="dm-card">
                    <div class="dm-mvtop">
                        <div>
                            <div class="lbl">Balance</div>
                            <div class="bal"><?= format_money($balance_dia) ?></div>
                        </div>
                        <span class="dm-badge"><span class="led"></span> Jornada en curso</span>
                    </div>
                    <div class="dm-split">
                        <div class="b">
                            <div class="t" style="color:var(--dash-available)">Dinero que quedo</div>
                            <div class="v" style="color:var(--dash-available)"><?= format_money($ingresos_total) ?></div>
                        </div>
                        <div class="b">
                            <div class="t" style="color:var(--dash-critical)">Gastos del hotel</div>
                            <div class="v" style="color:var(--dash-critical)"><?= format_money($egresos_total) ?></div>
                        </div>
                        <div class="b">
                            <div class="t" style="color:var(--dash-critical)">Devuelto/cancelado</div>
                            <div class="v" style="color:var(--dash-critical)">-<?= format_money(abs($reversos_total)) ?></div>
                        </div>
                    </div>
                </div>

                <div class="dm-sec"><span>Habitaciones</span><a href="<?= url('habitaciones') ?>">Ver todas</a></div>
                <div class="dm-card">
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=disponible') ?>" style="--state-accent:var(--dash-available)" title="Ver habitaciones disponibles"><span class="dt" style="background:var(--dash-available)"></span><span class="nm">Disponibles</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_disponibles) ?>%;background:var(--dash-available)"></i></span><span class="ct"><?= $habitaciones_disponibles ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=ocupada') ?>" style="--state-accent:var(--dash-occupied)" title="Ver habitaciones ocupadas"><span class="dt" style="background:var(--dash-occupied)"></span><span class="nm">Ocupadas</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_ocupadas) ?>%;background:var(--dash-occupied)"></i></span><span class="ct"><?= $habitaciones_ocupadas ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=por_llegar') ?>" style="--state-accent:var(--dash-arriving)" title="Ver habitaciones por llegar"><span class="dt" style="background:var(--dash-arriving)"></span><span class="nm">Por llegar</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_por_llegar) ?>%;background:var(--dash-arriving)"></i></span><span class="ct"><?= $habitaciones_por_llegar ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=limpieza') ?>" style="--state-accent:var(--dash-cleaning)" title="Ver habitaciones en limpieza"><span class="dt" style="background:var(--dash-cleaning)"></span><span class="nm">Limpieza</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_limpieza) ?>%;background:var(--dash-cleaning)"></i></span><span class="ct"><?= $habitaciones_limpieza ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=mantenimiento') ?>" style="--state-accent:var(--dash-maint)" title="Ver habitaciones en mantenimiento"><span class="dt" style="background:var(--dash-maint)"></span><span class="nm">Mantenimiento</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_mantenimiento) ?>%;background:var(--dash-maint)"></i></span><span class="ct"><?= $habitaciones_mantenimiento ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                </div>

                <div class="dm-sec"><span>Agenda</span><a href="<?= url('reservaciones') ?>">Ver agenda</a></div>
                <div class="dm-card">
                    <div class="dm-ag-h" style="color:var(--dash-arriving)">Llegadas · <?= count($proximas_llegadas) ?></div>
                    <?php if (empty($proximas_llegadas)): ?>
                        <div class="dm-empty">Sin llegadas programadas para hoy.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($proximas_llegadas, 0, 3) as $i => $llegada): ?>
                            <?php
                            $nombre_llegada = $llegada['huesped_nombre'] ?? 'Huésped pendiente';
                            $hora_llegada = !empty($llegada['hora_llegada_estimada'])
                                ? date('H:i', strtotime($llegada['hora_llegada_estimada']))
                                : 'Por definir';
                            ?>
                            <a class="dm-ag is-link<?= $i === 0 ? ' first' : '' ?>" href="<?= url('reservaciones/ver/' . (int)$llegada['id']) ?>" title="Ver detalle de la reservación de <?= dashboard_safe($nombre_llegada) ?>">
                                <div class="av"><?= dashboard_safe(dashboard_initials($nombre_llegada), 'H') ?></div>
                                <div>
                                    <div class="nm"><?= dashboard_safe($nombre_llegada) ?></div>
                                    <div class="mt"><?= dashboard_safe($llegada['habitaciones'] ?? null, 'Sin habitación') ?></div>
                                </div>
                                <span class="tm"><?= dashboard_safe($hora_llegada) ?><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <hr class="dm-ag-div">
                    <div class="dm-ag-h" style="color:var(--dash-maint)">Salidas · <?= count($proximas_salidas) ?></div>
                    <?php if (empty($proximas_salidas)): ?>
                        <div class="dm-empty">Sin salidas programadas para hoy.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($proximas_salidas, 0, 3) as $i => $salida): ?>
                            <?php
                            $nombre_salida = $salida['huesped_nombre'] ?? 'Huésped pendiente';
                            $hora_salida = !empty($salida['hora_salida'])
                                ? date('H:i', strtotime($salida['hora_salida']))
                                : (!empty($salida['hora_entrada']) ? date('H:i', strtotime($salida['hora_entrada'])) : 'Pendiente');
                            ?>
                            <a class="dm-ag is-link<?= $i === 0 ? ' first' : '' ?>" href="<?= url('reservaciones/ver/' . (int)$salida['id']) ?>" title="Ver detalle de la reservación de <?= dashboard_safe($nombre_salida) ?>">
                                <div class="av gold"><?= dashboard_safe(dashboard_initials($nombre_salida), 'H') ?></div>
                                <div>
                                    <div class="nm"><?= dashboard_safe($nombre_salida) ?></div>
                                    <div class="mt"><?= dashboard_safe($salida['habitaciones'] ?? null, 'Sin habitación') ?></div>
                                </div>
                                <span class="tm"><?= dashboard_safe($hora_salida) ?><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="dm-sec"><span>Estado de caja</span></div>
                <div class="dm-card">
                    <div class="dm-caja">
                        <div class="dm-cbox">
                            <div class="t">Inicial</div>
                            <div class="v"><?= format_money($caja_info['monto_inicial'] ?? 0) ?></div>
                        </div>
                        <div class="dm-cbox">
                            <div class="t">Dinero que quedo</div>
                            <div class="v" style="color:var(--dash-available)"><?= $dash_caja_ingreso_neto >= 0 ? '+' : '-' ?><?= format_money(abs($dash_caja_ingreso_neto)) ?></div>
                        </div>
                        <div class="dm-cbox">
                            <div class="t">Devuelto/cancelado</div>
                            <div class="v" style="color:var(--dash-critical)">-<?= format_money(abs($dash_caja_reversos)) ?></div>
                        </div>
                        <div class="dm-cbox">
                            <div class="t">Gastos del hotel</div>
                            <div class="v" style="color:var(--dash-critical)">-<?= format_money(abs($dash_caja_gastos_reales)) ?></div>
                        </div>
                        <div class="dm-cbox dark">
                            <div class="t">Esperado</div>
                            <div class="v"><?= format_money($caja_info['efectivo_esperado'] ?? $balance_dia) ?></div>
                        </div>
                    </div>
                    <div class="dm-actions">
                        <a href="<?= url('caja') ?>" class="dm-btn primary" title="Registrar un movimiento en caja"><i class="fas fa-plus" aria-hidden="true"></i>Registrar movimiento</a>
                        <a href="<?= url('caja') ?>" class="dm-btn ghost" title="Ver el corte actual"><i class="fas fa-eye" aria-hidden="true"></i>Ver corte</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
(function () {
    const root = document.querySelector('[data-notification-quick]');
    if (!root) {
        return;
    }

    const button = root.querySelector('[data-notification-toggle]');
    const panel = root.querySelector('[data-notification-panel]');
    if (!button || !panel) {
        return;
    }

    document.body.appendChild(panel);
    if (window.PWA && typeof window.PWA.initPushControls === 'function') {
        window.PWA.initPushControls();
    }

    function placePanel() {
        const rect = button.getBoundingClientRect();
        const width = Math.min(372, Math.max(280, window.innerWidth - 24));
        const left = Math.min(window.innerWidth - width - 12, Math.max(12, rect.right - width));
        const top = Math.min(window.innerHeight - 12, Math.max(12, rect.bottom + 10));

        panel.style.width = width + 'px';
        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
    }

    let hoverTimer = null;   // retardo de gracia para intención de hover
    let animTimer = null;    // limpieza tras la animación de cierre

    function clearHoverTimer() {
        if (hoverTimer) { clearTimeout(hoverTimer); hoverTimer = null; }
    }

    function openPanel() {
        clearHoverTimer();
        if (animTimer) { clearTimeout(animTimer); animTimer = null; }
        panel.classList.remove('nq-closing');
        panel.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        placePanel();
    }

    function closePanel() {
        clearHoverTimer();
        if (panel.hidden || panel.classList.contains('nq-closing')) {
            return;
        }
        // Deja correr la animación de salida antes de ocultar de verdad.
        panel.classList.add('nq-closing');
        button.setAttribute('aria-expanded', 'false');
        animTimer = setTimeout(function () {
            panel.hidden = true;
            panel.classList.remove('nq-closing');
            animTimer = null;
        }, 180);
    }

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        if (panel.hidden || panel.classList.contains('nq-closing')) {
            openPanel();
            return;
        }

        closePanel();
    });

    // ── Apertura/cierre por hover (solo con puntero fino: mouse/trackpad) ──
    // Al entrar al timbre o al panel se abre; al salir se cierra con un
    // pequeño retardo para poder cruzar el hueco entre botón y panel.
    if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        var scheduleClose = function () {
            clearHoverTimer();
            hoverTimer = setTimeout(closePanel, 220);
        };
        [root, panel].forEach(function (el) {
            el.addEventListener('mouseenter', openPanel);
            el.addEventListener('mouseleave', scheduleClose);
        });
    }

    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (!panel.hidden && !root.contains(event.target) && !panel.contains(event.target)) {
            closePanel();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            closePanel();
            button.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (!panel.hidden) {
            placePanel();
        }
    });

    window.addEventListener('scroll', function () {
        if (!panel.hidden) {
            placePanel();
        }
    }, true);

    // ── Swipe-para-archivar (iOS style) ─────────────────────────────
    // Deslizar una fila a la izquierda archiva la notificación con un
    // POST real a /notificaciones/{id}/descartar (mismo estado que "archivar"
    // en el centro de notificaciones). El click normal sigue navegando.
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const headCount = panel.querySelector('[data-notification-count]');
    const list = panel.querySelector('[data-notification-list]');
    const THRESHOLD = 96;
    let pendientes = headCount ? (parseInt(headCount.textContent, 10) || 0) : 0;

    function renderCounts() {
        if (headCount) {
            headCount.textContent = pendientes;
        }
        const badge = button.querySelector('.notification-count');
        const dot = button.querySelector('.notification-dot');
        if (pendientes <= 0) {
            if (badge) badge.remove();
            if (dot) dot.remove();
            button.classList.remove('has-notifications');
        } else if (badge) {
            badge.textContent = pendientes > 99 ? '99+' : String(pendientes);
        }
    }

    function onArchived(sw) {
        sw.classList.add('gone');
        sw.style.height = '0px';
        pendientes = Math.max(0, pendientes - 1);
        renderCounts();
        setTimeout(function () {
            sw.remove();
            if (list && !list.querySelector('[data-nq-swipe]')) {
                list.innerHTML = '<div class="notification-quick-empty">Sin pendientes operativos por ahora.</div>';
                const hint = panel.querySelector('[data-notification-hint]');
                if (hint) hint.remove();
            }
        }, 340);
    }

    function archiveRow(sw, row) {
        row.classList.add('settle');
        row.style.transform = 'translateX(-110%)';
        sw.style.height = sw.offsetHeight + 'px';

        const body = new URLSearchParams();
        body.set('csrf_token', csrfToken);

        fetch(row.dataset.archiveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            onArchived(sw);
        }).catch(function () {
            sw.style.height = '';
            row.style.transform = 'translateX(0)';
            if (typeof window.msToast === 'function') {
                window.msToast('No se pudo archivar la notificación.', 'error');
            }
        });
    }

    if (list) {
        list.querySelectorAll('[data-nq-swipe]').forEach(function (sw) {
            const row = sw.querySelector('.notification-quick-row');
            if (!row || !row.dataset.archiveUrl) return;

            let startX = 0, startY = 0, dx = 0;
            let active = false, decided = false, horiz = false;

            row.addEventListener('pointerdown', function (e) {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                startX = e.clientX; startY = e.clientY;
                dx = 0; active = true; decided = false; horiz = false;
                row.classList.remove('settle');
            });

            row.addEventListener('pointermove', function (e) {
                if (!active) return;
                const mx = e.clientX - startX;
                const my = e.clientY - startY;
                if (!decided && (Math.abs(mx) > 6 || Math.abs(my) > 6)) {
                    decided = true;
                    horiz = Math.abs(mx) > Math.abs(my);
                    if (horiz) {
                        row.classList.add('dragging');
                        try { row.setPointerCapture(e.pointerId); } catch (err) {}
                    }
                }
                if (!horiz) return;
                if (e.cancelable) e.preventDefault();
                dx = Math.min(0, mx); // solo hacia la izquierda
                row.style.transform = 'translateX(' + dx + 'px)';
            });

            function finishDrag() {
                if (!active) return;
                active = false;
                row.classList.remove('dragging');
                if (horiz) {
                    // Suprime la navegación del click que sigue al arrastre
                    row.dataset.dragged = '1';
                    setTimeout(function () { delete row.dataset.dragged; }, 0);
                    if (dx < -THRESHOLD) {
                        archiveRow(sw, row);
                    } else {
                        row.classList.add('settle');
                        row.style.transform = 'translateX(0)';
                    }
                }
            }

            row.addEventListener('pointerup', finishDrag);
            row.addEventListener('pointercancel', finishDrag);
            row.addEventListener('click', function (e) {
                if (row.dataset.dragged === '1') e.preventDefault();
            });
        });
    }
})();
</script>

<style id="dash-candy-glass-cupertino">
/* ═══ Dashboard: candy glass — SOLO TEMA CUPERTINO (2026-07-10) ══
   Hero editorial rediseñado a Liquid Glass oscuro iOS (2026-07-11): la foto
   respira a plena luz y el contenido flota en vidrio ahumado translúcido
   (blur + saturación reales) con borde especular fino. Aplica en claro y
   oscuro —el vidrio vive sobre la foto, no sobre el lienzo— y también al
   hero móvil (dm-*). Las losas pastel de dinero/CTA/anillo siguen siendo
   solo de modo claro.
   Revertir: borrar este bloque. */

/* ── Tokens del vidrio ahumado (Liquid Glass) ── */
html[data-tema="cupertino"] .dashboard-boutique{
    --lg-tinte: rgba(18,22,30,.36);
    --lg-tinte-hover: rgba(34,40,54,.46);
    --lg-borde: rgba(255,255,255,.30);
    --lg-brillo: inset 0 1px 0 rgba(255,255,255,.32), inset 0 -1px 0 rgba(255,255,255,.06);
    --lg-blur: blur(20px) saturate(1.7);
}
html[data-theme="dark"][data-tema="cupertino"] .dashboard-boutique{
    --lg-tinte: rgba(8,10,16,.46);
    --lg-tinte-hover: rgba(20,24,34,.56);
    --lg-borde: rgba(255,255,255,.22);
}
/* Sin backdrop-filter (navegadores viejos / ahorro de energía): vidrio denso
   para que el texto nunca pierda contraste sobre la foto. */
@supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))){
    html[data-tema="cupertino"] .dashboard-boutique{
        --lg-tinte: rgba(14,18,26,.8);
        --lg-tinte-hover: rgba(24,29,40,.86);
    }
}

/* ── Hero: la foto es la protagonista. Nada de niebla de marca: solo un velo
   cinematográfico mínimo arriba (fecha) y abajo (ancla del panel). ── */
html[data-tema="cupertino"] .dashboard-boutique .editorial-hero{
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    background: url("<?= htmlspecialchars($hero_image_url, ENT_QUOTES, 'UTF-8') ?>") center/cover;
    border:1px solid rgba(255,255,255,.16);
}

html[data-tema="cupertino"] .dashboard-boutique .editorial-hero::before{
    background:
        linear-gradient(180deg, rgba(9,12,18,.34), rgba(9,12,18,0) 32%),
        linear-gradient(0deg, rgba(9,12,18,.4), rgba(9,12,18,0) 55%);
    -webkit-backdrop-filter:none;
    backdrop-filter:none;
    -webkit-mask:none;
    mask:none;
    box-shadow:none;
}

/* Fuera el manchón de luz animado: compite con la foto. */
html[data-tema="cupertino"] .dashboard-boutique .editorial-hero::after{
    content:none;
}

/* ── Panel de vidrio del saludo: la pieza Liquid Glass. El blur desenfoca
   la foto real detrás; el borde y el brillo superior hacen el "specular". ── */
html[data-tema="cupertino"] .dashboard-boutique .hero-title{
    align-self:flex-start;
    width:fit-content;
    max-width:min(640px, calc(100% - 56px));
    margin:18px 28px 24px;
    padding:14px 22px 17px;
    border-radius:22px;
    border:1px solid var(--lg-borde);
    background:
        linear-gradient(150deg, rgba(255,255,255,.14), rgba(255,255,255,0) 46%),
        var(--lg-tinte);
    box-shadow: var(--lg-brillo), 0 18px 44px -20px rgba(6,10,18,.6);
    -webkit-backdrop-filter: var(--lg-blur);
    backdrop-filter: var(--lg-blur);
}

html[data-tema="cupertino"] .dashboard-boutique .hero-title h1{
    color:#FFFFFF;
    text-shadow:0 1px 2px rgba(0,0,0,.28);
}

html[data-tema="cupertino"] .dashboard-boutique .hero-title p{
    color:rgba(255,255,255,.85);
    text-shadow:0 1px 1px rgba(0,0,0,.22);
}

html[data-tema="cupertino"] .dashboard-boutique .hero-date{
    color:rgba(255,255,255,.9);
    text-shadow:0 1px 2px rgba(0,0,0,.35);
}

/* ── Píldoras superiores (hotel, campana): mismo vidrio, forma iOS ── */
html[data-tema="cupertino"] .dashboard-boutique :is(.hotel-switch, .glass-button){
    border-radius:999px;
    border:1px solid var(--lg-borde);
    background:
        linear-gradient(160deg, rgba(255,255,255,.16), rgba(255,255,255,0) 52%),
        var(--lg-tinte);
    color:#FFFFFF;
    box-shadow: var(--lg-brillo), 0 10px 26px -16px rgba(6,10,18,.55);
    -webkit-backdrop-filter: var(--lg-blur);
    backdrop-filter: var(--lg-blur);
}

html[data-tema="cupertino"] .dashboard-boutique .glass-button{
    transition:transform .18s ease, border-color .18s ease, background-color .18s ease;
}

html[data-tema="cupertino"] .dashboard-boutique .glass-button.has-notifications,
html[data-tema="cupertino"] .dashboard-boutique .glass-button:hover{
    border-color:rgba(255,255,255,.46);
    background:
        linear-gradient(160deg, rgba(255,255,255,.22), rgba(255,255,255,.03) 52%),
        var(--lg-tinte-hover);
}

html[data-tema="cupertino"] .dashboard-boutique .glass-button:active{
    transform:scale(.94);
}

html[data-tema="cupertino"] .dashboard-boutique .glass-button:focus-visible{
    outline:2px solid rgba(255,255,255,.9);
    outline-offset:2px;
}

/* Avatar: anillo luminoso para sentarse con la familia de vidrio. */
html[data-tema="cupertino"] .dashboard-boutique .hero-actions .avatar{
    border:1px solid rgba(255,255,255,.5);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.35), 0 10px 26px -16px rgba(6,10,18,.55);
}

/* ── Hero móvil (dm-*): misma gramática de vidrio ahumado ── */
html[data-tema="cupertino"] .dashboard-boutique .dm-hero-scrim{
    background:linear-gradient(180deg, rgba(9,12,18,.3) 0%, rgba(9,12,18,0) 30%, rgba(9,12,18,0) 52%, rgba(9,12,18,.44) 100%);
    animation:none;
}

html[data-tema="cupertino"] .dashboard-boutique .dm-hero-txt{
    width:fit-content;
    max-width:calc(100% - 24px);
    margin:0 12px 12px;
    padding:12px 16px 14px;
    border-radius:18px;
    border:1px solid var(--lg-borde);
    background:
        linear-gradient(150deg, rgba(255,255,255,.14), rgba(255,255,255,0) 46%),
        var(--lg-tinte);
    box-shadow: var(--lg-brillo), 0 14px 34px -18px rgba(6,10,18,.6);
    -webkit-backdrop-filter: var(--lg-blur);
    backdrop-filter: var(--lg-blur);
}

html[data-tema="cupertino"] .dashboard-boutique .dm-eyebrow{
    color:rgba(255,255,255,.82);
    text-shadow:none;
}

html[data-tema="cupertino"] .dashboard-boutique .dm-hotel{
    color:#FFFFFF;
    text-shadow:0 1px 2px rgba(0,0,0,.26);
}

html[data-tema="cupertino"] .dashboard-boutique .dm-greet{
    color:rgba(255,255,255,.86);
    text-shadow:none;
}

/* ── Filas de dinero del día: losas pastel de marca (antes gradiente oscuro) ── */
html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .cash-day-balance{
    background:
        radial-gradient(40% 170% at 97% 50%, rgba(255,255,255,.8), rgba(255,255,255,0) 70%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--dash-primary) 12%, #FFFFFF) 0%,
            color-mix(in srgb, var(--dash-primary) 24%, #FFFFFF) 100%);
    border:1px solid color-mix(in srgb, var(--dash-primary) 22%, rgba(255,255,255,.9));
    color:#1D1D1F;
    box-shadow:inset 0 1px 1px rgba(255,255,255,.9), 0 10px 22px -14px color-mix(in srgb, var(--dash-primary) 45%, rgba(27,39,70,.2));
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .cash-day-balance span{
    color:color-mix(in srgb, var(--dash-primary) 55%, #6E6E73);
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .cash-day-balance strong{
    color:#1D1D1F;
}

/* ── Chip Esperado: losa fuerte de marca con tinta (antes bloque oscuro) ── */
html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .cash-box.dark{
    background:
        radial-gradient(46% 160% at 96% 60%, rgba(255,255,255,.82), rgba(255,255,255,0) 72%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--dash-primary) 16%, #FFFFFF) 0%,
            color-mix(in srgb, var(--dash-primary) 32%, #FFFFFF) 100%);
    border-color:color-mix(in srgb, var(--dash-primary) 32%, rgba(255,255,255,.9));
    box-shadow:inset 0 1px 1px rgba(255,255,255,.9), 0 10px 22px -14px color-mix(in srgb, var(--dash-primary) 50%, rgba(27,39,70,.22));
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .cash-box.dark:hover{
    background:
        radial-gradient(46% 160% at 96% 60%, rgba(255,255,255,.86), rgba(255,255,255,0) 72%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--dash-primary) 18%, #FFFFFF) 0%,
            color-mix(in srgb, var(--dash-primary) 36%, #FFFFFF) 100%);
    border-color:color-mix(in srgb, var(--dash-primary) 40%, rgba(255,255,255,.9));
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .cash-box.dark .label{
    color:color-mix(in srgb, var(--dash-primary) 62%, #111827);
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .cash-box.dark .amount{
    color:#1D1D1F;
}

/* ── Registrar movimiento: losa de marca más cargada (CTA en cristal) ── */
html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .dash-btn.primary{
    background:
        radial-gradient(40% 170% at 96% 50%, rgba(255,255,255,.8), rgba(255,255,255,0) 70%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--dash-primary) 20%, #FFFFFF) 0%,
            color-mix(in srgb, var(--dash-primary) 34%, #FFFFFF) 100%);
    color:color-mix(in srgb, var(--dash-primary) 74%, #111827);
    border:1px solid color-mix(in srgb, var(--dash-primary) 34%, rgba(255,255,255,.9));
    box-shadow:inset 0 1px 0 rgba(255,255,255,.85), 0 6px 14px -8px color-mix(in srgb, var(--dash-primary) 45%, transparent);
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .dash-btn.primary:hover{
    filter:brightness(1.03) saturate(1.05);
}

/* ── Anillo de estacionamiento: arco en tono de marca suave (no manchón negro) ── */
html[data-tema="cupertino"]:not([data-theme="dark"]) .dashboard-boutique .ring-box svg circle[stroke-dasharray]{
    stroke:color-mix(in srgb, var(--dash-primary) 55%, #FFFFFF);
}
</style>
