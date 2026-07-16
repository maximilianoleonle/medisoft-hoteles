<?php require_once __DIR__ . '/../models/IncrementoTarifa.php';
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../services/NotificacionService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/PropietarioDistribucionService.php';
/**
 * Controlador de Habitaciones
 * Sistema hotelero
 */

class HabitacionController extends Controller {
    private $habitacionModel;
    private $habitacionImagenModel;
    private $tareaModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->habitacionModel = new Habitacion();
        $this->habitacionImagenModel = new HabitacionImagen();
        $this->tareaModel = new TareaOperativa();
    }
    
    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        require_hotel_module('habitaciones');
        return true;
    }

    private function erroresCamposHabitacion(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (strpos($lower, 'numero') !== false || strpos($lower, 'número') !== false) {
                $campo = 'numero';
            } elseif (strpos($lower, 'tipo') !== false) {
                $campo = 'tipo';
            } elseif (strpos($lower, 'capacidad') !== false || strpos($lower, 'persona') !== false) {
                $campo = 'capacidad_personas';
            } elseif (strpos($lower, 'cama') !== false) {
                $campo = 'camas_matrimoniales';
            } elseif (strpos($lower, 'precio') !== false || strpos($lower, 'tarifa') !== false) {
                $campo = 'precio_base';
            } elseif (strpos($lower, 'piso') !== false) {
                $campo = 'piso';
            } elseif (strpos($lower, 'imagen') !== false || strpos($lower, 'foto') !== false || strpos($lower, 'archivo') !== false) {
                $campo = 'fotos[]';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function buscarHabitacionPorNumero(string $numero, ?int $excluirId = null): ?array {
        $numero = trim($numero);
        if ($numero === '') {
            return null;
        }

        $params = [$numero, $this->hotelIdActual()];
        $sql = "SELECT id, numero, activa FROM habitaciones WHERE numero = ? AND hotel_id = ?";

        if ($excluirId !== null && $excluirId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }

        $sql .= " ORDER BY activa DESC, id ASC LIMIT 1";

        $stmt = Database::getInstance()->query($sql, $params);
        if (!$stmt) {
            return null;
        }

        $habitacion = $stmt->fetch();
        return $habitacion ?: null;
    }

    private function validarNumeroHabitacionUnico(array &$errores, string $numero, ?int $excluirId = null): void {
        $duplicada = $this->buscarHabitacionPorNumero($numero, $excluirId);
        if (!$duplicada) {
            return;
        }

        if ((int)($duplicada['activa'] ?? 0) === 1) {
            $errores[] = 'Ya existe una habitacion activa con este numero. No se puede repetir.';
            return;
        }

        $errores[] = 'Este numero pertenece a una habitacion dada de baja. Para usarlo, recupera esa habitacion desde el registro existente o crea la habitacion desde Nuevo para reactivarla.';
    }
    
    /**
     * Listado de habitaciones
     */
    /**
 * Listado de habitaciones - ACTUALIZADO CON DETECCIÓN DE "POR LLEGAR"
 */
/**
 * Listado de habitaciones - ACTUALIZADO CON DETECCIÓN DE "POR LLEGAR"
 */
 
 
/**
 * Listado de habitaciones - ACTUALIZADO CON TARIFAS DINÁMICAS
 */
public function indexAction() {
    // Obtener filtros
    $filtros = [
        'estado' => get('estado'),
        'tipo' => get('tipo'),
        'piso' => get('piso'),
        'buscar' => get('buscar'),
        'fecha_consulta' => get('fecha_consulta'),
        'mostrar_disponibilidad' => get('mostrar_disponibilidad')
    ];
    $tipoFiltroSolicitado = strtolower(trim((string)($filtros['tipo'] ?? '')));
    $tipoFiltroTecnico = $tipoFiltroSolicitado !== '' && isset($this->tiposAlmacenamientoCompatibles()[$tipoFiltroSolicitado]);
    
    // Verificar si se está filtrando por fecha específica
    if (!empty($filtros['fecha_consulta']) && !empty($filtros['mostrar_disponibilidad'])) {
        $this->mostrarDisponibilidadPorFecha($filtros);
        return;
    }
    
    // Construir query base
    $conditions = ['activa' => 1];
    
    // Aplicar filtros (excepto estado que se maneja después)
    if ($tipoFiltroTecnico) {
        $conditions['tipo'] = $tipoFiltroSolicitado;
    }
    if ($filtros['piso'] !== null && $filtros['piso'] !== '') {
        $conditions['piso'] = $filtros['piso'];
    }
    
    // Obtener habitaciones
    if ($filtros['buscar']) {
        $habitaciones = $this->habitacionModel->buscar($filtros['buscar']);
    } else {
        $habitaciones = $this->habitacionModel->where($conditions);
    }
    
    // Obtener reservaciones de hoy que no han hecho check-in
    $db = Database::getInstance();
    $hotelId = $this->hotelIdActual();
    $sql = "SELECT 
            rh.habitacion_id,
            r.id as reservacion_id,
            r.hora_llegada_estimada,
            h.nombre_completo,
            h.telefono,
            COUNT(DISTINCT rh2.habitacion_id) as total_habitaciones
            FROM reservaciones r
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id AND hab_scope.hotel_id = r.hotel_id
            INNER JOIN huespedes h ON r.huesped_id = h.id
            LEFT JOIN reservacion_habitaciones rh2 ON r.id = rh2.reservacion_id AND rh2.hotel_id = r.hotel_id
            WHERE r.hotel_id = ?
            AND hab_scope.hotel_id = ?
            AND r.fecha_entrada = CURDATE()
            AND r.estado = 'confirmada'
            GROUP BY rh.habitacion_id, r.id";
    
    $stmt = $db->query($sql, [$hotelId, $hotelId]);
    $reservaciones_pendientes = [];
    
    while ($row = $stmt->fetch()) {
        $reservaciones_pendientes[$row['habitacion_id']] = $row;
    }
    
    // Cargar modelo de tarifas
    require_once __DIR__ . '/../models/IncrementoTarifa.php';
    $tarifaModel = new IncrementoTarifa();
    // Modelo de reservaciones para anexar el saldo pendiente a las ocupadas (badge en tarjeta).
    $reservacionModel = new Reservacion();
    $reservaciones_ocupadas = [];
    $fecha_consulta = date('Y-m-d');
    
    // Procesar cada habitación
    foreach ($habitaciones as &$habitacion) {
        // Calcular precio con incrementos
        $precio_info = $tarifaModel->calcularPrecioConIncremento(
            $habitacion['id'],
            $habitacion['tipo'],
            $habitacion['precio_base'],
            $fecha_consulta
        );
        
        // Agregar información de tarifas
        $habitacion['precio_base_original'] = $habitacion['precio_base'];
        $habitacion['precio_actual'] = $precio_info['precio_final'];
        $habitacion['incremento_total'] = $precio_info['incremento_total'];
        $habitacion['incrementos_aplicados'] = $precio_info['incrementos_aplicados'];
        $habitacion['tiene_incremento'] = ($precio_info['incremento_total'] > 0);
        
        // Verificar si tiene reservación pendiente para hoy
        if (isset($reservaciones_pendientes[$habitacion['id']])) {
            $habitacion['estado_display'] = 'por_llegar';
            $habitacion['reservacion_pendiente'] = $reservaciones_pendientes[$habitacion['id']];
        } else {
            $habitacion['estado_display'] = $habitacion['estado'];
            
            // Información adicional según estado
            if ($habitacion['estado'] == 'ocupada') {
                $habitacion['ocupacion_actual'] = $this->habitacionModel->getOcupacionActual($habitacion['id']);

                if (is_array($habitacion['ocupacion_actual'])) {
                    $reservacion_id_ocup = (int)($habitacion['ocupacion_actual']['id']
                        ?? $habitacion['ocupacion_actual']['reservacion_id'] ?? 0);
                    if ($reservacion_id_ocup > 0) {
                        $reservaciones_ocupadas[$reservacion_id_ocup] = $reservacion_id_ocup;
                    }
                }
            }
            if (in_array($habitacion['estado'], ['ocupada', 'limpieza'])) {
                $habitacion['proxima_salida'] = $this->habitacionModel->getProximaSalida($habitacion['id']);
            }
        }
    }
    unset($habitacion);

    // Anexar resumen de cobro (total/pagado/saldo) para el indicador de saldo
    // pendiente en la tarjeta. En lote: una consulta por fuente, no por habitación.
    // Defensivo: si algo falla, queda en 0.
    $resumenes_ocupadas = [];
    if (!empty($reservaciones_ocupadas)) {
        try {
            $resumenes_ocupadas = $reservacionModel->resumenPagosLote(array_values($reservaciones_ocupadas), $hotelId);
        } catch (\Throwable $e) {
            $resumenes_ocupadas = [];
        }
    }
    foreach ($habitaciones as &$habitacion) {
        if (!is_array($habitacion['ocupacion_actual'] ?? null)) {
            continue;
        }
        $reservacion_id_ocup = (int)($habitacion['ocupacion_actual']['id']
            ?? $habitacion['ocupacion_actual']['reservacion_id'] ?? 0);
        if ($reservacion_id_ocup <= 0) {
            continue;
        }
        $resumenOcup = $resumenes_ocupadas[$reservacion_id_ocup] ?? [];
        $habitacion['ocupacion_actual']['saldo']  = (float)($resumenOcup['saldo'] ?? 0);
        $habitacion['ocupacion_actual']['pagado'] = (float)($resumenOcup['pagado'] ?? 0);
        $habitacion['ocupacion_actual']['total']  = (float)($resumenOcup['total'] ?? 0);
    }
    unset($habitacion);

    if ($tipoFiltroSolicitado !== '' && !$tipoFiltroTecnico) {
        $habitaciones = array_values(array_filter($habitaciones, function ($habitacion) use ($tipoFiltroSolicitado) {
            return $this->codigoTipoCatalogoHabitacion($habitacion) === $tipoFiltroSolicitado;
        }));
    }

    // El estado queda como filtro visual inicial en la vista para no recortar el DOM.
    // Obtener estadísticas actualizadas
    $estadisticas = $this->habitacionModel->estadisticas();
    $estadisticas['por_llegar'] = count($reservaciones_pendientes);
    $estadisticas['disponibles_real'] = $estadisticas['disponibles'] - $estadisticas['por_llegar'];
    
    // Ordenar habitaciones por número
    usort($habitaciones, function($a, $b) {
        return strnatcmp($a['numero'], $b['numero']);
    });
    $habitaciones = $this->anexarResumenTareasHabitaciones($hotelId, $habitaciones);
    $habitaciones = $this->anexarPropietariosHabitaciones($hotelId, $habitaciones);
    
    // Agregar estados para la vista
    $estados = Habitacion::getEstados();
    $estados['por_llegar'] = ['label' => 'Por llegar', 'color' => 'purple', 'icon' => 'clock'];
    $alertasPendientes = $this->obtenerAlertasPendientesHabitaciones($hotelId);
    
    View::renderTemplate('habitaciones/index', [
        'title' => 'Habitaciones - ' . current_hotel_display_name(),
        'habitaciones' => $habitaciones,
        'estadisticas' => $estadisticas,
        'filtros' => $filtros,
        'tipos' => $this->catalogoTiposHabitacion(),
        'estados' => $estados,
        'pisos' => $this->catalogoPisosHabitacion(),
        'checkins_pendientes' => $alertasPendientes['checkins'],
        'checkouts_vencidos' => $alertasPendientes['checkouts'],
        'llegadas_tardias' => $alertasPendientes['llegadas_tardias'],
        'personal_limpieza' => $this->personalLimpiezaVista($hotelId),
    ]);
}

private function obtenerAlertasPendientesHabitaciones(int $hotelId): array {
    if ($hotelId <= 0) {
        return [
            'checkins' => [],
            'checkouts' => [],
            'llegadas_tardias' => [],
        ];
    }

    $db = Database::getInstance();

    $sqlCheckins = "SELECT
                    r.id,
                    r.hotel_id,
                    r.huesped_id,
                    r.fecha_entrada,
                    r.hora_llegada_estimada,
                    r.precio_total,
                    h.nombre_completo,
                    h.telefono,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(CURDATE(), r.fecha_entrada) as dias_retraso
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                  AND r.estado = 'confirmada'
                  AND r.fecha_entrada < CURDATE()
                GROUP BY r.id
                ORDER BY r.fecha_entrada
                LIMIT 10";

    $sqlCheckouts = "SELECT
                    r.id,
                    r.hotel_id,
                    r.huesped_id,
                    r.fecha_salida,
                    r.hora_entrada,
                    r.precio_total,
                    h.nombre_completo,
                    h.telefono,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(CURDATE(), r.fecha_salida) as dias_retraso
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                  AND r.estado = 'checked_in'
                  AND r.fecha_salida < CURDATE()
                GROUP BY r.id
                ORDER BY r.fecha_salida
                LIMIT 10";

    $stmtCheckins = $db->query($sqlCheckins, [$hotelId]);
    $stmtCheckouts = $db->query($sqlCheckouts, [$hotelId]);

    return [
        'checkins' => $stmtCheckins ? ($stmtCheckins->fetchAll() ?: []) : [],
        'checkouts' => $stmtCheckouts ? ($stmtCheckouts->fetchAll() ?: []) : [],
        'llegadas_tardias' => [],
    ];
}

private function anexarResumenTareasHabitaciones(int $hotelId, array $habitaciones): array {
    if ($hotelId <= 0 || empty($habitaciones)) {
        return $habitaciones;
    }

    $habitacionIds = [];
    foreach ($habitaciones as $habitacion) {
        $habitacionId = (int)($habitacion['id'] ?? 0);
        if ($habitacionId > 0) {
            $habitacionIds[] = $habitacionId;
        }
    }

    if (empty($habitacionIds)) {
        return $habitaciones;
    }

    $resumenPorHabitacion = $this->tareaModel->resumenActivoPorHabitacionesHotel($hotelId, $habitacionIds);
    foreach ($habitaciones as &$habitacion) {
        $habitacionId = (int)($habitacion['id'] ?? 0);
        $habitacion['tareas_activas_resumen'] = $resumenPorHabitacion[$habitacionId] ?? [];
    }
    unset($habitacion);

    return $habitaciones;
}

private function mostrarDisponibilidadPorFecha($filtros) {
    $fecha_consulta = $filtros['fecha_consulta'];
    
    $db = Database::getInstance();
    
    // Obtener TODAS las habitaciones activas primero
    $conditions = ['activa' => 1];
    $todasHabitaciones = [];
    $tipoFiltroSolicitado = strtolower(trim((string)($filtros['tipo'] ?? '')));
    $tipoFiltroTecnico = $tipoFiltroSolicitado !== '' && isset($this->tiposAlmacenamientoCompatibles()[$tipoFiltroSolicitado]);
    
    if (!empty($filtros['buscar'])) {
        $todasHabitaciones = $this->habitacionModel->buscar($filtros['buscar']);
    } else {
        if ($tipoFiltroTecnico) {
            $conditions['tipo'] = $tipoFiltroSolicitado;
        }
        if ($filtros['piso'] !== null && $filtros['piso'] !== '') {
            $conditions['piso'] = $filtros['piso'];
        }
        
        $todasHabitaciones = $this->habitacionModel->where($conditions);
    }
    
    error_log("Total habitaciones obtenidas: " . count($todasHabitaciones));
    
    // LOG: Ver cuáles habitaciones tenemos
    foreach ($todasHabitaciones as $h) {
        if ($h['numero'] == '06' || $h['numero'] == '07') {
            error_log("Habitación {$h['numero']} encontrada - ID: {$h['id']}, Estado: {$h['estado']}");
        }
    }
    
    // El estado se conserva para que la vista aplique el filtro sin perder habitaciones.
    // Query para obtener ocupadas - Todas las habitaciones ocupadas EN la fecha específica
    // NO incluye las que hacen check-out ese día (porque ese día se desocupan)
    // Incluye checked_out para mostrar correctamente fechas pasadas
    $hotelId = $this->hotelIdActual();
    $sql_ocupadas = "SELECT DISTINCT 
                     rh.habitacion_id,
                     hab.numero as habitacion_numero,
                     r.id as reservacion_id,
                     r.fecha_entrada, 
                     r.fecha_salida, 
                     r.estado as estado_reservacion,
                     h.nombre_completo
            FROM reservacion_habitaciones rh
            INNER JOIN reservaciones r ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
            WHERE r.estado IN ('confirmada', 'checked_in', 'checked_out')
            AND r.hotel_id = ?
            AND hab.hotel_id = ?
            AND DATE(r.fecha_entrada) <= ?
            AND DATE(r.fecha_salida) > ?
            ORDER BY hab.numero";
    
    error_log("=== EJECUTANDO QUERY DE OCUPADAS ===");
    $stmt = $db->query($sql_ocupadas, [$hotelId, $hotelId, $fecha_consulta, $fecha_consulta]);
    $ocupadas = [];
    
    while ($row = $stmt->fetch()) {
        if ($row['habitacion_numero'] == '06' || $row['habitacion_numero'] == '07') {
            error_log("*** ENCONTRADA EN QUERY: Hab {$row['habitacion_numero']} ***");
            error_log("  ID: {$row['habitacion_id']}");
            error_log("  Entrada: {$row['fecha_entrada']}");
            error_log("  Salida: {$row['fecha_salida']}");
            error_log("  Huésped: {$row['nombre_completo']}");
        }
        
        $ocupadas[$row['habitacion_id']] = [
            'reservacion_id' => $row['reservacion_id'],
            'fecha_entrada' => $row['fecha_entrada'],
            'fecha_salida' => $row['fecha_salida'],
            'huesped' => $row['nombre_completo']
        ];
    }
    
    error_log("Total ocupadas: " . count($ocupadas));
    
    // Cargar modelo de tarifas
    require_once __DIR__ . '/../models/IncrementoTarifa.php';
    $tarifaModel = new IncrementoTarifa();
    
    // Procesar habitaciones
    $habitaciones_procesadas = [];
    $disponibles = 0;
    $ocupadas_count = 0;
    
    foreach ($todasHabitaciones as $habitacion) {
        // Calcular precio
        $precio_info = $tarifaModel->calcularPrecioConIncremento(
            $habitacion['id'],
            $habitacion['tipo'],
            $habitacion['precio_base'],
            $fecha_consulta
        );
        
        $habitacion['precio_base_original'] = $habitacion['precio_base'];
        $habitacion['precio_actual'] = $precio_info['precio_final'];
        $habitacion['incremento_total'] = $precio_info['incremento_total'];
        $habitacion['incrementos_aplicados'] = $precio_info['incrementos_aplicados'];
        $habitacion['tiene_incremento'] = ($precio_info['incremento_total'] > 0);
        
        // Determinar estado
        if ($habitacion['estado'] == 'mantenimiento') {
    $habitacion['estado_display'] = $habitacion['estado'];
            error_log("Hab {$habitacion['numero']}: {$habitacion['estado']}");
        } elseif (isset($ocupadas[$habitacion['id']])) {
            $habitacion['info_ocupacion'] = $ocupadas[$habitacion['id']];
            $habitacion['estado_display'] = 'ocupada_fecha';
            $ocupadas_count++;
            error_log("Hab {$habitacion['numero']}: OCUPADA - {$ocupadas[$habitacion['id']]['huesped']}");
        } else {
            $habitacion['estado_display'] = 'disponible_fecha';
            $disponibles++;
            error_log("Hab {$habitacion['numero']}: DISPONIBLE");
        }
        
        $habitaciones_procesadas[] = $habitacion;
    }

    if ($tipoFiltroSolicitado !== '' && !$tipoFiltroTecnico) {
        $habitaciones_procesadas = array_values(array_filter($habitaciones_procesadas, function ($habitacion) use ($tipoFiltroSolicitado) {
            return $this->codigoTipoCatalogoHabitacion($habitacion) === $tipoFiltroSolicitado;
        }));
    }
    
    error_log("=== ESTADÍSTICAS ===");
    error_log("Disponibles: $disponibles");
    error_log("Ocupadas: $ocupadas_count");
    
    // El estado se conserva para que la vista aplique el filtro sin perder habitaciones.
    // Reindexar y ordenar
    $habitaciones_procesadas = array_values($habitaciones_procesadas);
    $habitaciones_procesadas = $this->anexarResumenTareasHabitaciones($hotelId, $habitaciones_procesadas);
    $habitaciones_procesadas = $this->anexarPropietariosHabitaciones($hotelId, $habitaciones_procesadas);
    usort($habitaciones_procesadas, function($a, $b) {
        return strnatcmp($a['numero'], $b['numero']);
    });
    
    // Estadísticas
    $estadisticas = [
        'total' => count($habitaciones_procesadas),
        'disponibles' => count(array_filter($habitaciones_procesadas, function($h) { 
            return $h['estado_display'] == 'disponible_fecha'; 
        })),
        'ocupadas' => count(array_filter($habitaciones_procesadas, function($h) { 
            return $h['estado_display'] == 'ocupada_fecha'; 
        })),
        'por_llegar' => 0,
        'limpieza' => count(array_filter($habitaciones_procesadas, function($h) { 
            return $h['estado_display'] == 'limpieza'; 
        })),
        'mantenimiento' => count(array_filter($habitaciones_procesadas, function($h) { 
            return $h['estado_display'] == 'mantenimiento'; 
        }))
    ];
    
    error_log("=== FIN DISPONIBILIDAD ===");
    
    // Estados para la vista
    $estados = Habitacion::getEstados();
    $estados['disponible_fecha'] = ['label' => 'Disponible', 'color' => 'green', 'icon' => 'check-circle'];
    $estados['ocupada_fecha'] = ['label' => 'Ocupada', 'color' => 'red', 'icon' => 'user'];
    $alertasPendientes = $this->obtenerAlertasPendientesHabitaciones($hotelId);
    
    View::renderTemplate('habitaciones/index', [
        'title' => 'Disponibilidad ' . format_date($fecha_consulta) . ' - ' . current_hotel_display_name(),
        'habitaciones' => $habitaciones_procesadas,
        'estadisticas' => $estadisticas,
        'filtros' => $filtros,
        'tipos' => $this->catalogoTiposHabitacion(),
        'estados' => $estados,
        'pisos' => $this->catalogoPisosHabitacion(),
        'mostrar_disponibilidad_fecha' => true,
        'fecha_consultada' => $fecha_consulta,
        'checkins_pendientes' => $alertasPendientes['checkins'],
        'checkouts_vencidos' => $alertasPendientes['checkouts'],
        'llegadas_tardias' => $alertasPendientes['llegadas_tardias'],
        'personal_limpieza' => $this->personalLimpiezaVista((int)$hotelId),
    ]);
}
    
    /**
     * Ver detalle de habitación
     */
    /**
 * Ver detalle de habitación - ACTUALIZADO CON DETECCIÓN DE LLEGADAS PENDIENTES
 */
/**
 * Ver detalle de habitación - ACTUALIZADO CON DETECCIÓN DE LLEGADAS PENDIENTES
 */
/**
 * Ver detalle de habitación - ACTUALIZADO CON TARIFAS DINÁMICAS
 */
/**
 * Ver detalle de habitación - ACTUALIZADO CON TARIFAS DINÁMICAS Y OCUPACIÓN ACTUAL
 */
public function verAction() {
    $id = $this->route_params['id'] ?? 0;
    
    $habitacion = $this->habitacionModel->find($id);
    
    if (!$habitacion) {
        set_mensaje('Habitación no encontrada', 'error');
        $this->redirect('habitaciones');
    }
    
    // Obtener información adicional
    $ocupacion_actual = null;
    // IMPORTANTE: Obtener ocupación actual SIEMPRE
$ocupacion_actual = $this->habitacionModel->getOcupacionActual($id);

    $proxima_salida = null;
    $historial_reciente = [];
    $mantenimiento_actual = null;
    $reservacion_pendiente = null;
    
    // NUEVO: Obtener información de tarifas dinámicas
    require_once __DIR__ . '/../models/IncrementoTarifa.php';
    $tarifaModel = new IncrementoTarifa();
    $fecha_consulta = date('Y-m-d');
    
    // Calcular precio con incrementos
    $precio_info = $tarifaModel->calcularPrecioConIncremento(
        $habitacion['id'],
        $habitacion['tipo'],
        $habitacion['precio_base'],
        $fecha_consulta
    );
    
    // Agregar información de tarifas a la habitación
    $habitacion['precio_base_original'] = $habitacion['precio_base'];
    $habitacion['precio_actual'] = $precio_info['precio_final'];
    $habitacion['incremento_total'] = $precio_info['incremento_total'];
    $habitacion['incrementos_aplicados'] = $precio_info['incrementos_aplicados'];
    $habitacion['tiene_incremento'] = ($precio_info['incremento_total'] > 0);
    
    // Verificar si hay reservación pendiente para hoy
    $db = Database::getInstance();
    $hotelId = $this->hotelIdActual();
    $sql = "SELECT 
            r.id as reservacion_id,
            r.hora_llegada_estimada,
            r.fecha_entrada,
            r.precio_total,
            h.nombre_completo,
            h.telefono,
            COUNT(DISTINCT rh2.habitacion_id) as total_habitaciones
            FROM reservaciones r
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            INNER JOIN huespedes h ON r.huesped_id = h.id
            LEFT JOIN reservacion_habitaciones rh2 ON r.id = rh2.reservacion_id
            WHERE rh.habitacion_id = ?
            AND hab_scope.hotel_id = ?
            AND r.fecha_entrada = CURDATE()
            AND r.estado = 'confirmada'
            GROUP BY r.id
            LIMIT 1";
    
    $stmt = $db->query($sql, [$id, $hotelId]);
    $reservacion_pendiente = $stmt->fetch();
    
    // Determinar el estado real de visualización
    if ($reservacion_pendiente) {
        $habitacion['estado_display'] = 'por_llegar';
    } else {
        $habitacion['estado_display'] = $habitacion['estado'];
    }
    $habitacion = $this->anexarPropietariosHabitaciones($hotelId, [$habitacion])[0] ?? $habitacion;
    
    // IMPORTANTE: Obtener ocupación actual SIEMPRE, no solo cuando estado es 'ocupada'
    // Esto permitirá mostrar el huésped actual incluso cuando hay doble movimiento
    $ocupacion_actual = $this->habitacionModel->getOcupacionActual($id);
    
    // Obtener próxima salida si aplica
    if (in_array($habitacion['estado'], ['ocupada', 'limpieza'])) {
        $proxima_salida = $this->habitacionModel->getProximaSalida($id);
    }
    
    // Obtener mantenimiento actual si aplica
    if ($habitacion['estado'] == 'mantenimiento') {
        $mantenimiento_actual = $this->obtenerMantenimientoActual($id);
    }
    
    // Obtener mantenimientos programados
    require_once __DIR__ . '/../models/Mantenimiento.php';
    $mantenimientoModel = new Mantenimiento();
    $mantenimientos_programados = $mantenimientoModel->programadosPorHabitacion($id);
    $reservas_mantenimiento_futuras = $this->reservacionesMantenimientoHabitacion(
        (int)$id,
        date('Y-m-d'),
        date('Y-m-d', strtotime('+365 days')),
        40
    );
    $reservas_mantenimiento_proximas = array_values(array_filter(
        $reservas_mantenimiento_futuras,
        static function ($reserva) {
            $fechaEntrada = substr((string)($reserva['fecha_entrada'] ?? ''), 0, 10);
            return $fechaEntrada !== '' && $fechaEntrada <= date('Y-m-d', strtotime('+7 days'));
        }
    ));
    
    // Obtener historial reciente
    $historial_reciente = [];

    try {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                r.id,
                r.fecha_entrada,
                r.fecha_salida,
                r.estado,
                r.notas as observaciones,
                r.created_at,
                r.precio_total,
                r.total_habitaciones,
                h.nombre_completo as nombre_huesped,
                h.telefono,
                h.procedencia_estado,
                h.procedencia_ciudad,
                h.vehiculo_marca,
                h.vehiculo_placas,
                CASE 
                    WHEN h.vehiculo_marca IS NOT NULL AND h.vehiculo_marca != '' THEN 1
                    ELSE 0
                END as vehiculos
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            WHERE 
                rh.habitacion_id = ?
                AND hab_scope.hotel_id = ?
                AND r.estado IN ('checked_out', 'checked_in', 'confirmada')
            GROUP BY r.id
            ORDER BY r.fecha_salida DESC, r.created_at DESC
            LIMIT 10";
        
        $stmt = $db->query($sql, [$id, $this->hotelIdActual()]);
        
        if ($stmt !== false) {
            $historial_reciente = $stmt->fetchAll();
            
            // Procesar los datos
            if ($historial_reciente && is_array($historial_reciente)) {
                foreach ($historial_reciente as &$reservacion) {
                    // Combinar ciudad y estado en procedencia
                    $procedencia_parts = [];
                    if (!empty($reservacion['procedencia_ciudad'])) {
                        $procedencia_parts[] = $reservacion['procedencia_ciudad'];
                    }
                    if (!empty($reservacion['procedencia_estado'])) {
                        $procedencia_parts[] = $reservacion['procedencia_estado'];
                    }
                    $reservacion['procedencia'] = implode(', ', $procedencia_parts);
                    
                    // Asegurar que precio_total tenga un valor
                    if (empty($reservacion['precio_total'])) {
                        $reservacion['precio_total'] = 0;
                    }
                    
                    // Asegurar que observaciones exista
                    if (!isset($reservacion['observaciones'])) {
                        $reservacion['observaciones'] = '';
                    }
                    
                    // Agregar campo folio vacío (ya que no existe en la BD)
                    $reservacion['folio'] = '';
                    
                    // Usar total_habitaciones que sí existe en la tabla
                    if (empty($reservacion['total_habitaciones'])) {
                        $reservacion['total_habitaciones'] = 1;
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Error en historial: " . $e->getMessage());
        $historial_reciente = [];
    }

    // Asegurar que siempre sea un array
    if (!is_array($historial_reciente)) {
        $historial_reciente = [];
    }
    
    View::renderTemplate('habitaciones/ver', [
        'title' => 'Habitación ' . $habitacion['numero'] . ' - ' . current_hotel_display_name(),
        'habitacion' => $habitacion,
        'ocupacion_actual' => $ocupacion_actual,
        'proxima_salida' => $proxima_salida,
        'historial_reciente' => $historial_reciente,
        'mantenimiento_actual' => $mantenimiento_actual,
        'mantenimientos_programados' => $mantenimientos_programados ?? [],
        'reservas_mantenimiento_futuras' => $reservas_mantenimiento_futuras,
        'reservas_mantenimiento_proximas' => $reservas_mantenimiento_proximas,
        'tareas_contextuales' => $this->tareaModel->listarPorEntidadHotel($hotelId, 'habitacion', (int)$id, 8),
        'reservacion_pendiente' => $reservacion_pendiente,
        'estados' => Habitacion::getEstados(),
        'tipos' => $this->catalogoTiposHabitacion()
    ]);
}
    
    /**
     * Mostrar formulario para crear habitación
     */
    public function crearAction() {
        $this->requirePermission('habitaciones.create');
        
        View::renderTemplate('habitaciones/crear', [
            'title' => 'Nueva Habitación - ' . current_hotel_display_name(),
            'tipos' => $this->catalogoTiposHabitacion(),
            'pisos' => $this->catalogoPisosHabitacion(),
            'amenidades' => $this->catalogoAmenidadesHabitacion()
        ]);
    }
    
    /**
     * Guardar nueva habitación CON MÚLTIPLES IMÁGENES
     */
    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        
        $tipoSolicitado = (string) $this->getPost('tipo');
        $caracteristicas_especiales = $this->normalizarCaracteristicasSeleccionadas(
            $this->getPost('caracteristicas_especiales', [])
        );
        $tipoAlmacenamiento = $this->normalizarTipoHabitacionParaAlmacenamiento(
            $tipoSolicitado,
            $caracteristicas_especiales
        );
        $defaultsHabitacion = $this->defaultsCapacidadCamas($tipoSolicitado ?: $tipoAlmacenamiento);

        // Recopilar datos básicos
        $data = [
            'numero' => trim($this->getPost('numero')),
            'tipo' => $tipoAlmacenamiento,
            'capacidad_personas' => $this->normalizarEnteroHabitacion(
                $this->getPost('capacidad_personas'),
                $defaultsHabitacion['capacidad_personas'],
                1,
                30
            ),
            'camas_matrimoniales' => $this->normalizarEnteroHabitacion(
                $this->getPost('camas_matrimoniales'),
                $defaultsHabitacion['camas_matrimoniales'],
                0,
                20
            ),
            'camas_individuales' => $this->normalizarEnteroHabitacion(
                $this->getPost('camas_individuales'),
                $defaultsHabitacion['camas_individuales'],
                0,
                20
            ),
            'piso' => intval($this->getPost('piso')),
            'precio_base' => floatval($this->getPost('precio_base')),
            'estado' => 'disponible',
            'activa' => $this->getPost('activa') ? 1 : 0,
            'hotel_id' => $this->hotelIdActual()
        ];
        
        // Procesar características
        $caracteristicas_custom = trim($this->getPost('caracteristicas'));
        $data['caracteristicas'] = $this->construirDescripcionCaracteristicas(
            $tipoSolicitado ?: $data['tipo'],
            $caracteristicas_especiales,
            $caracteristicas_custom
        );
        $data['caracteristicas'] = $this->agregarTipoCatalogoEnCaracteristicas(
            $data['caracteristicas'],
            $tipoSolicitado,
            $data['tipo']
        );
        
        // Validar datos contra los catalogos configurables del hotel
        $errores = $this->validarHabitacionConCatalogos($data);
        
        // Verificar número único
        $habitacionInactivaDuplicada = null;
        $habitacionDuplicada = $this->buscarHabitacionPorNumero((string)$data['numero']);
        if ($habitacionDuplicada) {
            if ((int)($habitacionDuplicada['activa'] ?? 0) === 1) {
                $errores[] = 'Ya existe una habitacion activa con este numero. No se puede repetir.';
            } else {
                $habitacionInactivaDuplicada = $habitacionDuplicada;
            }
        }

        $fieldErrors = $this->erroresCamposHabitacion($errores);

        if (!empty($errores)) {
            save_old_input($_POST);
            save_form_errors($fieldErrors);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('habitaciones/create');
        }
        
        // Iniciar transacción
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            $habitacionRecuperada = false;

            if ($habitacionInactivaDuplicada) {
                $habitacion = $this->habitacionModel->update((int)$habitacionInactivaDuplicada['id'], $data);
                $habitacionRecuperada = true;
            } else {
                // Crear habitación
                $habitacion = $this->habitacionModel->create($data);
            }
            
            if (!$habitacion) {
                throw new Exception($habitacionRecuperada ? 'Error al recuperar la habitacion' : 'Error al crear la habitacion');
            }
            
            // Procesar múltiples imágenes si se subieron
            if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
                $imagenes_procesadas = $this->procesarMultiplesImagenes($_FILES['fotos'], $habitacion['id'], $data['numero']);
                
                if (!$imagenes_procesadas['success']) {
                    throw new Exception($imagenes_procesadas['error']);
                }
                
                // Si hay imágenes, establecer la primera como principal en habitaciones.foto_url
                if (!empty($imagenes_procesadas['imagenes'])) {
                    $primera_imagen = $imagenes_procesadas['imagenes'][0];
                    $this->habitacionModel->update($habitacion['id'], ['foto_url' => $primera_imagen['url']]);
                }
            }
            
            // Crear entrada en control de llaves
            $this->crearControlLlave($habitacion['id']);
            
            $db->commit();
            
            clear_old_input();
            set_mensaje($habitacionRecuperada ? 'Habitacion recuperada y actualizada exitosamente' : 'Habitación creada exitosamente', 'success');
            $this->redirect('habitaciones/' . $habitacion['id']);
            
        } catch (Exception $e) {
            $db->rollBack();
            $mensajeError = $this->mensajeErrorHabitacion($e);
            set_mensaje('Error: ' . $mensajeError, 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposHabitacion([$mensajeError]));
            $this->redirect('habitaciones/create');
        }
    }
    /**
 * Historial completo de reservaciones de una habitación
 */
public function historialAction() {
    $id = $this->route_params['id'] ?? 0;
    
    // Obtener habitación usando el mismo método que verAction()
    $habitacion = $this->habitacionModel->find($id);
    
    if (!$habitacion) {
        set_mensaje('Habitación no encontrada', 'error');
        $this->redirect('habitaciones');
        return;
    }
    
    // Obtener TODO el historial de reservaciones (sin límite)
    $historial = [];
    
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                r.id,
                r.fecha_entrada,
                r.fecha_salida,
                r.estado,
                r.notas as observaciones,
                r.created_at,
                r.precio_total,
                r.total_habitaciones,
                h.nombre_completo as nombre_huesped,
                h.telefono,
                h.procedencia_estado,
                h.procedencia_ciudad,
                h.vehiculo_marca,
                h.vehiculo_placas,
                CASE 
                    WHEN h.vehiculo_marca IS NOT NULL AND h.vehiculo_marca != '' THEN 1
                    ELSE 0
                END as vehiculos,
                h.id as huesped_id
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            WHERE 
                rh.habitacion_id = ?
                AND hab_scope.hotel_id = ?
                AND r.estado IN ('checked_out', 'checked_in', 'confirmada')
            GROUP BY r.id
            ORDER BY r.fecha_salida DESC, r.created_at DESC";
        
        $stmt = $db->query($sql, [$id, $this->hotelIdActual()]);
        
        if ($stmt !== false) {
            $historial = $stmt->fetchAll();
            
            // Procesar los datos igual que en verAction
            if ($historial && is_array($historial)) {
                foreach ($historial as &$reservacion) {
                    // Combinar ciudad y estado en procedencia
                    $procedencia_parts = [];
                    if (!empty($reservacion['procedencia_ciudad'])) {
                        $procedencia_parts[] = $reservacion['procedencia_ciudad'];
                    }
                    if (!empty($reservacion['procedencia_estado'])) {
                        $procedencia_parts[] = $reservacion['procedencia_estado'];
                    }
                    $reservacion['procedencia'] = implode(', ', $procedencia_parts);
                    
                    // Asegurar que precio_total tenga un valor
                    if (empty($reservacion['precio_total'])) {
                        $reservacion['precio_total'] = 0;
                    }
                    
                    // Asegurar que observaciones exista
                    if (!isset($reservacion['observaciones'])) {
                        $reservacion['observaciones'] = '';
                    }
                    
                    // Agregar campo folio vacío (ya que no existe en la BD)
                    $reservacion['folio'] = '';
                    
                    // Usar total_habitaciones que sí existe en la tabla
                    if (empty($reservacion['total_habitaciones'])) {
                        $reservacion['total_habitaciones'] = 1;
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Error en historial completo: " . $e->getMessage());
        $historial = [];
    }
    
    // Asegurar que siempre sea un array
    if (!is_array($historial)) {
        $historial = [];
    }
    
    // Renderizar la vista de historial
    View::renderTemplate('habitaciones/historial', [
        'title' => 'Historial Completo - Habitación ' . $habitacion['numero'],
        'habitacion' => $habitacion,
        'historial' => $historial,
        'tipos' => $this->catalogoTiposHabitacion()
    ]);
}
    /**
     * Mostrar formulario para editar habitación
     */
    public function editarAction() {
        $this->requirePermission('habitaciones.edit');
        
        $id = $this->route_params['id'] ?? 0;
        $habitacion = $this->habitacionModel->find($id);
        
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        View::renderTemplate('habitaciones/editar', [
            'title' => 'Editar Habitación - ' . current_hotel_display_name(),
            'habitacion' => $habitacion,
            'reservaciones_bloqueantes_eliminacion' => $this->reservacionesBloqueantesEliminacion((int)$id),
            'tipos' => $this->catalogoTiposHabitacion(),
            'pisos' => $this->catalogoPisosHabitacion(),
            'amenidades' => $this->catalogoAmenidadesHabitacion()
        ]);
    }
    
    /**
     * Reservaciones activas o futuras que impiden eliminar una habitacion.
     */
    private function reservacionesBloqueantesEliminacion(int $habitacionId): array {
        if ($habitacionId <= 0) {
            return [];
        }

        try {
            $hotelId = $this->hotelIdActual();
            $db = Database::getInstance();
            $sql = "SELECT
                    r.id,
                    r.estado,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.precio_total,
                    h.nombre_completo,
                    h.telefono,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    COUNT(DISTINCT rh2.habitacion_id) as total_habitaciones
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh
                    ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab_scope
                    ON rh.habitacion_id = hab_scope.id
                    AND hab_scope.hotel_id = rh.hotel_id
                INNER JOIN huespedes h
                    ON r.huesped_id = h.id
                    AND h.hotel_id = r.hotel_id
                LEFT JOIN reservacion_habitaciones rh2
                    ON r.id = rh2.reservacion_id
                    AND rh2.hotel_id = r.hotel_id
                LEFT JOIN habitaciones hab
                    ON rh2.habitacion_id = hab.id
                    AND hab.hotel_id = rh2.hotel_id
                WHERE rh.habitacion_id = ?
                    AND rh.hotel_id = ?
                    AND r.hotel_id = ?
                    AND hab_scope.hotel_id = ?
                    AND r.estado IN ('confirmada', 'checked_in')
                    AND r.fecha_salida >= CURDATE()
                GROUP BY
                    r.id,
                    r.estado,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.precio_total,
                    h.nombre_completo,
                    h.telefono
                ORDER BY
                    CASE WHEN r.estado = 'checked_in' THEN 0 ELSE 1 END,
                    r.fecha_entrada ASC,
                    r.id ASC";

            $stmt = $db->query($sql, [$habitacionId, $hotelId, $hotelId, $hotelId]);
            return $stmt ? ($stmt->fetchAll() ?: []) : [];
        } catch (Exception $e) {
            error_log('Error consultando reservaciones bloqueantes de habitacion: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar habitacion con multiples imagenes.
     */
    public function actualizarAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        
        $id = $this->route_params['id'] ?? 0;
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        $tipoSolicitado = (string) $this->getPost('tipo');
        $caracteristicas_especiales = $this->normalizarCaracteristicasSeleccionadas(
            $this->getPost('caracteristicas_especiales', [])
        );
        $tipoAlmacenamiento = $this->normalizarTipoHabitacionParaAlmacenamiento(
            $tipoSolicitado,
            $caracteristicas_especiales
        );
        $defaultsHabitacion = $this->defaultsCapacidadCamas($tipoSolicitado ?: $tipoAlmacenamiento);

        // Recopilar datos
        $data = [
            'numero' => trim($this->getPost('numero')),
            'tipo' => $tipoAlmacenamiento,
            'capacidad_personas' => $this->normalizarEnteroHabitacion(
                $this->getPost('capacidad_personas'),
                (int)($habitacion['capacidad_personas'] ?? $defaultsHabitacion['capacidad_personas']),
                1,
                30
            ),
            'camas_matrimoniales' => $this->normalizarEnteroHabitacion(
                $this->getPost('camas_matrimoniales'),
                (int)($habitacion['camas_matrimoniales'] ?? $defaultsHabitacion['camas_matrimoniales']),
                0,
                20
            ),
            'camas_individuales' => $this->normalizarEnteroHabitacion(
                $this->getPost('camas_individuales'),
                (int)($habitacion['camas_individuales'] ?? $defaultsHabitacion['camas_individuales']),
                0,
                20
            ),
            'piso' => intval($this->getPost('piso')),
            'precio_base' => floatval($this->getPost('precio_base')),
            'activa' => $this->getPost('activa') ? 1 : 0
        ];
        
        // Procesar características
        $caracteristicas_custom = trim($this->getPost('caracteristicas'));
        $data['caracteristicas'] = $this->construirDescripcionCaracteristicas(
            $tipoSolicitado ?: $data['tipo'],
            $caracteristicas_especiales,
            $caracteristicas_custom
        );
        $data['caracteristicas'] = $this->agregarTipoCatalogoEnCaracteristicas(
            $data['caracteristicas'],
            $tipoSolicitado,
            $data['tipo']
        );
        
        // Validar datos contra los catalogos configurables del hotel
        $errores = $this->validarHabitacionConCatalogos($data);
        
        // Verificar número único (excluyendo la habitación actual)
        $this->validarNumeroHabitacionUnico($errores, (string)$data['numero'], (int)$id);
        
        // Manejar múltiples imágenes si se subieron nuevas
        if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
            $imagenes_procesadas = $this->procesarMultiplesImagenes($_FILES['fotos'], $id, $data['numero']);
            
            if (!$imagenes_procesadas['success']) {
                $errores[] = $imagenes_procesadas['error'];
            } else if (!empty($imagenes_procesadas['imagenes'])) {
                // Si no hay imagen principal actual, establecer la primera nueva como principal
                $tiene_principal = $this->habitacionImagenModel->obtenerPrincipal($id);
                if (!$tiene_principal) {
                    $primera_imagen = $imagenes_procesadas['imagenes'][0];
                    $data['foto_url'] = $primera_imagen['url'];
                }
            }
        }
        
        $fieldErrors = $this->erroresCamposHabitacion($errores);

        if (!empty($errores)) {
            save_old_input($_POST);
            save_form_errors($fieldErrors);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('habitaciones/' . $id . '/edit');
        }
        
        // Actualizar
        if ($this->habitacionModel->update($id, $data)) {
            clear_old_input();
            set_mensaje('Habitación actualizada exitosamente', 'success');
            $this->redirect('habitaciones/' . $id);
        } else {
            set_mensaje('Error al actualizar la habitación', 'error');
            save_old_input($_POST);
            save_form_errors(['numero' => ['No se pudo actualizar la habitacion. Revisa los datos e intentalo de nuevo.']]);
            $this->redirect('habitaciones/' . $id . '/edit');
        }
    }
    
    /**
     * Eliminar habitación
     */
    public function eliminarAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        $this->requirePermission('habitaciones.delete');
        
        $id = $this->route_params['id'] ?? 0;
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        // Solo se puede eliminar si está disponible
        if ($habitacion['estado'] != 'disponible') {
            set_mensaje('Solo se pueden eliminar habitaciones disponibles', 'error');
            $this->redirect('habitaciones/' . $id);
        }

        $reservacionesBloqueantes = $this->reservacionesBloqueantesEliminacion((int)$id);
        if (!empty($reservacionesBloqueantes)) {
            $totalReservaciones = count($reservacionesBloqueantes);
            $numeroHabitacion = trim((string)($habitacion['numero'] ?? $id));
            set_mensaje(
                'No se puede eliminar la habitacion ' . $numeroHabitacion . ': tiene ' .
                $totalReservaciones . ' reservacion(es) activa(s) o futura(s). Cancela esas reservaciones desde sus detalles y vuelve a intentarlo.',
                'error'
            );
            $this->redirect('habitaciones/' . $id . '/edit');
        }

        try {
            $resultado = $this->habitacionModel->update((int)$id, ['activa' => 0]);

            if (!$resultado) {
                throw new Exception('No se pudo desactivar la habitacion');
            }

            set_mensaje('Habitacion eliminada del listado. Se conservo el historial operativo asociado.', 'success');
            $this->redirect('habitaciones');
            return;
        } catch (Exception $e) {
            error_log('Error al dar de baja habitacion: ' . $e->getMessage());
            set_mensaje('Error al eliminar la habitacion. Intentalo de nuevo o revisa el historial asociado.', 'error');
            $this->redirect('habitaciones/' . $id . '/edit');
            return;
        }
        
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Eliminar imágenes relacionadas
            $imagenes = $this->habitacionImagenModel->porHabitacion($id);
            foreach ($imagenes as $imagen) {
                $this->eliminarImagenHabitacion($imagen['url']);
            }
            $db->query(
                "DELETE FROM habitacion_imagenes WHERE habitacion_id = ? AND hotel_id = ?",
                [$id, $this->hotelIdActual()]
            );
            
            // Eliminar imagen principal si existe
            if (!empty($habitacion['foto_url'])) {
                $this->eliminarImagenHabitacion($habitacion['foto_url']);
            }
            
            // Eliminar control de llaves
            $db->query("DELETE FROM control_llaves WHERE habitacion_id = ?", [$id]);
            
            // Eliminar habitación
            $this->habitacionModel->delete($id);
            
            $db->commit();
            
            set_mensaje('Habitación eliminada exitosamente', 'success');
            $this->redirect('habitaciones');
            
        } catch (Exception $e) {
            $db->rollBack();
            set_mensaje('Error al eliminar la habitación', 'error');
            $this->redirect('habitaciones/' . $id);
        }
    }
    
    /**
     * Gestionar imágenes de habitación
     */
    public function gestionarImagenesAction() {
        $id = $this->route_params['id'] ?? 0;
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        // Obtener todas las imágenes
        $imagenes = $this->habitacionImagenModel->porHabitacion($id);
        
        View::renderTemplate('habitaciones/gestionar-imagenes', [
            'title' => 'Gestionar Imágenes - Habitación ' . $habitacion['numero'],
            'habitacion' => $habitacion,
            'imagenes' => $imagenes
        ]);
    }
    
    /**
     * Agregar imágenes adicionales
     */
    public function agregarImagenesAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        $this->requirePermission('habitaciones.edit');
        
        $id = $this->route_params['id'] ?? 0;
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        // Verificar límite de imágenes
        $imagenes_actuales = $this->habitacionImagenModel->contarPorHabitacion($id);
        if ($imagenes_actuales >= 10) {
            set_mensaje('Has alcanzado el límite máximo de 10 imágenes por habitación', 'error');
            $this->redirect('habitaciones/' . $id . '/imagenes');
        }
        
        // Procesar nuevas imágenes
        if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
            $db = Database::getInstance();
            
            try {
                $db->beginTransaction();
                
                $imagenes_procesadas = $this->procesarMultiplesImagenes($_FILES['fotos'], $id, $habitacion['numero']);
                
                if (!$imagenes_procesadas['success']) {
                    throw new Exception($imagenes_procesadas['error']);
                }
                
                // Si no hay imagen principal actual, establecer la primera nueva como principal
                $tiene_principal = $this->habitacionImagenModel->obtenerPrincipal($id);
                if (!$tiene_principal && !empty($imagenes_procesadas['imagenes'])) {
                    $primera_imagen = $imagenes_procesadas['imagenes'][0];
                    $this->habitacionModel->update($id, ['foto_url' => $primera_imagen['url']]);
                    $this->habitacionImagenModel->establecerPrincipal($primera_imagen['id'], $id);
                }
                
                $db->commit();
                
                $cantidad = count($imagenes_procesadas['imagenes']);
                set_mensaje("Se agregaron {$cantidad} imagen(es) correctamente", 'success');
                
            } catch (Exception $e) {
                $db->rollBack();
                set_mensaje('Error al agregar imágenes: ' . $e->getMessage(), 'error');
            }
        } else {
            set_mensaje('No se seleccionaron imágenes', 'error');
        }
        
        $this->redirect('habitaciones/' . $id . '/imagenes');
    }
    
    /**
     * Eliminar imagen individual
     */
    /**
 * Eliminar imagen individual
 */
/**
 * Eliminar imagen individual
 */
public function eliminarImagenAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    
    // Obtener parámetros
    $imagen_id = intval($this->getPost('imagen_id'));
    $habitacion_id = intval($this->getPost('habitacion_id'));
    
    // Verificar que la imagen existe y pertenece a la habitación
    $imagen = $this->habitacionImagenModel->find($imagen_id);
    
    if (!$imagen || $imagen['habitacion_id'] != $habitacion_id) {
        set_mensaje('Imagen no encontrada', 'error');
        $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
    }
    
    // Verificar permisos
    if (!can('habitaciones.edit')) {
        set_mensaje('No tienes permisos para eliminar imágenes', 'error');
        $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
    }
    
    $db = Database::getInstance();
    
    try {
        $db->beginTransaction();
        
        // Eliminar archivo físico
        if (!empty($imagen['url'])) {
            $rutaCompleta = PUBLIC_PATH . '/' . ltrim($imagen['url'], '/');
            if (file_exists($rutaCompleta)) {
                if (!@unlink($rutaCompleta)) {
                    error_log("No se pudo eliminar el archivo: " . $rutaCompleta);
                }
            }
        }
        
        // Si era la imagen principal, buscar otra para establecer como principal
        if ($imagen['es_principal']) {
            // Quitar foto_url de la habitación
            $this->habitacionModel->update($habitacion_id, ['foto_url' => null]);
            
            // Buscar otra imagen para establecer como principal
            $sql = "SELECT * FROM habitacion_imagenes 
                    WHERE habitacion_id = ? AND id != ? AND hotel_id = ?
                    ORDER BY orden ASC, id ASC 
                    LIMIT 1";
            $stmt = $db->query($sql, [$habitacion_id, $imagen_id, $this->hotelIdActual()]);
            $nueva_principal = $stmt->fetch();
            
            if ($nueva_principal) {
                // Establecer la nueva imagen como principal
                $sql_update = "UPDATE habitacion_imagenes SET es_principal = 1 WHERE id = ? AND hotel_id = ?";
                $db->query($sql_update, [$nueva_principal['id'], $this->hotelIdActual()]);
                
                // Actualizar foto_url en la tabla habitaciones
                $this->habitacionModel->update($habitacion_id, ['foto_url' => $nueva_principal['url']]);
            }
        }
        
        // Eliminar registro de la base de datos
        $sql_delete = "DELETE FROM habitacion_imagenes WHERE id = ? AND hotel_id = ?";
        $stmt = $db->query($sql_delete, [$imagen_id, $this->hotelIdActual()]);
        
        if ($stmt->rowCount() > 0) {
            $db->commit();
            set_mensaje('Imagen eliminada correctamente', 'success');
        } else {
            throw new Exception('No se pudo eliminar el registro de la imagen');
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error al eliminar imagen: " . $e->getMessage());
        set_mensaje('Error al eliminar la imagen: ' . $e->getMessage(), 'error');
    }
    
    $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
}
    
    /**
     * Establecer imagen principal
     */
    public function establecerPrincipalAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        
        $imagen_id = intval($this->getPost('imagen_id'));
        $habitacion_id = intval($this->getPost('habitacion_id'));
        
        $imagen = $this->habitacionImagenModel->find($imagen_id);
        
        if (!$imagen || $imagen['habitacion_id'] != $habitacion_id) {
            set_mensaje('Imagen no encontrada', 'error');
            $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
        }
        
        // Establecer como principal
        $this->habitacionImagenModel->establecerPrincipal($imagen_id, $habitacion_id);
        
        // Actualizar foto_url en la tabla habitaciones
        $this->habitacionModel->update($habitacion_id, ['foto_url' => $imagen['url']]);
        
        set_mensaje('Imagen principal actualizada', 'success');
        $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
    }
    
    /**
     * Cambiar estado de habitación (mantenimiento)
     */
    /**
 * Cambiar estado de habitación (mantenimiento)
 */
public function mantenimientoAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    $this->requirePermission('habitaciones.mantenimiento');
    
    $id = (int)($this->route_params['id'] ?? 0);
    $accion = trim((string)$this->getPost('accion'));
    $hotelId = $this->hotelIdActual();

    if (!in_array($accion, ['iniciar', 'finalizar'], true)) {
        set_mensaje('AcciÃ³n de mantenimiento no vÃ¡lida', 'error');
        $this->redirect('habitaciones/' . $id);
    }
    
    // El nucleo (validaciones, conflicto de reservas, transaccion y
    // notificaciones) vive en MantenimientoService: mismo motor que usa la
    // accion del copiloto. Aqui solo queda la UX de la pantalla.
    require_once __DIR__ . '/../services/MantenimientoService.php';
    $servicio = new MantenimientoService();

    try {
        if ($accion == 'iniciar') {
            $resultado = $servicio->iniciarParaHotel(
                (int)$hotelId,
                (int)$id,
                trim((string)$this->getPost('tipo_mantenimiento')),
                trim((string)$this->getPost('prioridad', 'media')),
                trim((string)$this->getPost('motivo')),
                user_id(),
                $this->confirmoConflictoMantenimiento()
            );

            if (empty($resultado['ok'])) {
                set_mensaje($this->mensajeConflictoMantenimiento($resultado['conflictos'], 'iniciar'), 'warning');
                $this->redirect('habitaciones/' . $id);
                return;
            }

            set_mensaje('Mantenimiento iniciado correctamente', 'success');
        } elseif ($accion == 'finalizar') {
            $servicio->finalizarParaHotel((int)$hotelId, (int)$id, user_id());
            set_mensaje('Mantenimiento finalizado correctamente', 'success');
        }
    } catch (Exception $e) {
        set_mensaje('Error al procesar mantenimiento: ' . $e->getMessage(), 'error');
    }

    $this->redirect('habitaciones/' . $id);
}

/**
 * Programar mantenimiento futuro
 * POST /habitaciones/{id}/programar-mantenimiento
 */
public function programarMantenimientoAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    $this->requirePermission('habitaciones.mantenimiento');
    
    $id = (int)($this->route_params['id'] ?? 0);
    $hotelId = $this->hotelIdActual();
    
    $habitacion = $this->habitacionModel->find($id);
    if (!$habitacion) {
        set_mensaje('Habitación no encontrada', 'error');
        $this->redirect('habitaciones');
        return;
    }
    
    require_once __DIR__ . '/../models/Mantenimiento.php';
    $mantenimientoModel = new Mantenimiento();
    
    $fecha_inicio = trim((string)$this->getPost('fecha_programada'));
    $fecha_fin = trim((string)$this->getPost('fecha_programada_fin'));
    $parseDate = static function ($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ((int)$errors['warning_count'] > 0 || (int)$errors['error_count'] > 0);

        if (!$date || $hasErrors || $date->format('Y-m-d') !== $value) {
            return false;
        }

        return $date;
    };
    
    // Validaciones
    if (empty($fecha_inicio)) {
        set_mensaje('Debe indicar la fecha de inicio del mantenimiento', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }

    $fecha_inicio_dt = $parseDate($fecha_inicio);
    if (!$fecha_inicio_dt) {
        set_mensaje('La fecha programada no es valida', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }

    $fecha_inicio = $fecha_inicio_dt->format('Y-m-d');
    
    if ($fecha_inicio < date('Y-m-d')) {
        set_mensaje('La fecha programada no puede ser en el pasado', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }
    
    if ($fecha_fin !== '') {
        $fecha_fin_dt = $parseDate($fecha_fin);
        if (!$fecha_fin_dt) {
            set_mensaje('La fecha de fin no es valida', 'error');
            $this->redirect('habitaciones/' . $id);
            return;
        }

        $fecha_fin = $fecha_fin_dt->format('Y-m-d');
    }

    if (!empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
        set_mensaje('La fecha de fin no puede ser anterior a la fecha de inicio', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }

    $tipoMantenimiento = trim((string)$this->getPost('tipo_mantenimiento', 'preventivo'));
    $prioridad = trim((string)$this->getPost('prioridad', 'media'));
    $motivo = trim((string)$this->getPost('motivo'));
    $descripcion = trim((string)$this->getPost('descripcion', ''));

    if (!in_array($tipoMantenimiento, array_keys(Mantenimiento::getTipos()), true)) {
        set_mensaje('Debe seleccionar un tipo de mantenimiento valido', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }

    if (!in_array($prioridad, array_keys(Mantenimiento::getPrioridades()), true)) {
        set_mensaje('Debe seleccionar una prioridad valida', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }

    if ($motivo === '') {
        set_mensaje('El motivo es obligatorio', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }

    if ($mantenimientoModel->tieneProgramadoSolapado($id, $fecha_inicio, $fecha_fin ?: null)) {
        set_mensaje('Ya existe un mantenimiento programado para esta habitacion en ese rango de fechas', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }
    
    // Verificar que no haya reservaciones que conflicten con las fechas
    $fecha_fin_check = $fecha_fin ?: $fecha_inicio;
    // Agregar un día extra al fin para cubrir el día completo
    $fecha_fin_check_ext = date('Y-m-d', strtotime($fecha_fin_check . ' +1 day'));
    
    $db = Database::getInstance();
    $sql = "SELECT r.id, h.nombre_completo, r.fecha_entrada, r.fecha_salida
            FROM reservaciones r
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            INNER JOIN huespedes h ON r.huesped_id = h.id
            WHERE rh.habitacion_id = ?
            AND hab_scope.hotel_id = ?
            AND r.estado IN ('confirmada', 'checked_in')
            AND r.fecha_entrada < ?
            AND r.fecha_salida > ?
            LIMIT 5";
    
    $stmt = $db->query($sql, [$id, $hotelId, $fecha_fin_check_ext, $fecha_inicio]);
    $reservaciones_conflicto = $stmt->fetchAll();
    
    if (!empty($reservaciones_conflicto) && !$this->confirmoConflictoMantenimiento()) {
        $conflictos = [];
        foreach ($reservaciones_conflicto as $res) {
            $conflictos[] = $res['nombre_completo'] . ' (' . date('d/m/Y', strtotime($res['fecha_entrada'])) . ' - ' . date('d/m/Y', strtotime($res['fecha_salida'])) . ')';
        }
        set_mensaje('Esta habitacion tiene reservaciones en esas fechas. Confirma el aviso de riesgo para programar de todos modos: ' . implode(', ', $conflictos), 'warning');
        $this->redirect('habitaciones/' . $id);
        return;
    }
    
    $data = [
        'tipo_mantenimiento' => $tipoMantenimiento,
        'prioridad' => $prioridad,
        'motivo' => $motivo,
        'descripcion' => $descripcion,
        'fecha_programada' => $fecha_inicio,
        'fecha_programada_fin' => $fecha_fin ?: null,
        'usuario_registro_id' => user_id()
    ];
    
    $resultado = $mantenimientoModel->programar($id, $data);
    
    if ($resultado) {
        $fecha_txt = date('d/m/Y', strtotime($fecha_inicio));
        $fin_txt = $fecha_fin ? ' al ' . date('d/m/Y', strtotime($fecha_fin)) : '';
        set_mensaje("Mantenimiento programado para el {$fecha_txt}{$fin_txt} en habitación {$habitacion['numero']}", 'success');
        $this->registrarNotificacionHabitacion(
            (int)$id,
            'mantenimiento_programado',
            'Mantenimiento programado para habitacion ' . ($habitacion['numero'] ?? $id),
            'Programado para el ' . $fecha_txt . $fin_txt . '.',
            $prioridad === 'alta' ? 'alta' : 'media'
        );
        if (!empty($reservaciones_conflicto)) {
            $this->registrarNotificacionMantenimientoReserva(
                (int)$id,
                (string)($habitacion['numero'] ?? $id),
                $reservaciones_conflicto,
                'programado'
            );
        }
    } else {
        set_mensaje('Error al programar el mantenimiento', 'error');
    }
    
    $this->redirect('habitaciones/' . $id);
}

/**
 * Cancelar mantenimiento programado
 * POST /habitaciones/cancelar-mantenimiento-programado/{id}
 */
public function cancelarMantenimientoProgramadoAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    $this->requirePermission('habitaciones.mantenimiento');
    
    $mantenimiento_id = (int)($this->route_params['id'] ?? 0);
    
    require_once __DIR__ . '/../models/Mantenimiento.php';
    $mantenimientoModel = new Mantenimiento();
    
    $mantenimiento = $mantenimientoModel->find($mantenimiento_id);
    if (!$mantenimiento) {
        set_mensaje('Mantenimiento no encontrado', 'error');
        $this->redirect('habitaciones');
        return;
    }

    $habitacion = $this->habitacionModel->find((int)($mantenimiento['habitacion_id'] ?? 0));
    if (!$habitacion) {
        set_mensaje('Habitacion no encontrada para el hotel actual', 'error');
        $this->redirect('habitaciones');
        return;
    }
    
    $motivo = trim((string)$this->getPost('motivo_cancelacion', 'Cancelado por el usuario'));
    if ($motivo === '') {
        $motivo = 'Cancelado por el usuario';
    }
    $resultado = $mantenimientoModel->cancelarProgramado($mantenimiento_id, $motivo);
    
    if ($resultado) {
        set_mensaje('Mantenimiento programado cancelado correctamente', 'success');
        $this->registrarNotificacionHabitacion(
            (int)($mantenimiento['habitacion_id'] ?? 0),
            'mantenimiento_programado_cancelado',
            'Mantenimiento programado cancelado',
            trim((string)$motivo) ?: 'Se cancelo un mantenimiento programado.',
            'media'
        );
    } else {
        set_mensaje('Error al cancelar el mantenimiento programado', 'error');
    }
    
    $this->redirect('habitaciones/' . $mantenimiento['habitacion_id']);
}

    /**
     * Liberar habitación
     */
/**
 * Registrar notificacion de habitacion sin bloquear mantenimiento.
 */
private function registrarNotificacionHabitacion(int $habitacionId, string $tipo, string $titulo, string $mensaje, string $severidad = 'info'): void {
    if ($habitacionId <= 0) {
        return;
    }

    NotificacionService::crear([
        'hotel_id' => $this->hotelIdActual(),
        'modulo' => 'habitaciones',
        'tipo' => $tipo,
        'severidad' => $severidad,
        'titulo' => $titulo,
        'mensaje' => $mensaje,
        'entidad_tipo' => 'habitacion',
        'entidad_id' => $habitacionId,
        'url' => 'habitaciones/' . $habitacionId,
        'dedupe_key' => 'habitaciones.' . $tipo . '.' . $habitacionId . '.' . date('YmdHis'),
        'creada_por' => function_exists('user_id') ? user_id() : null,
    ]);
}

/**
 * Reservaciones activas que se cruzan con una ventana de mantenimiento.
 */
private function reservacionesMantenimientoHabitacion(int $habitacionId, string $fechaInicio, ?string $fechaFin = null, int $limite = 8): array {
    if ($habitacionId <= 0) {
        return [];
    }

    $fechaInicio = substr(trim($fechaInicio), 0, 10);
    $fechaFin = substr(trim((string)($fechaFin ?: $fechaInicio)), 0, 10);

    if ($fechaInicio === '' || strtotime($fechaInicio) === false) {
        return [];
    }

    if ($fechaFin === '' || strtotime($fechaFin) === false || $fechaFin < $fechaInicio) {
        $fechaFin = $fechaInicio;
    }

    $limite = max(1, min(40, (int)$limite));
    $fechaFinExclusiva = date('Y-m-d', strtotime($fechaFin . ' +1 day'));
    $hotelId = $this->hotelIdActual();

    try {
        $db = Database::getInstance();
        $sql = "SELECT
                    r.id,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.hora_llegada_estimada,
                    r.estado,
                    h.nombre_completo,
                    h.telefono,
                    COUNT(DISTINCT rh2.habitacion_id) AS total_habitaciones
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh
                    ON rh.reservacion_id = r.id
                INNER JOIN habitaciones hab_scope
                    ON hab_scope.id = rh.habitacion_id
                   AND hab_scope.hotel_id = r.hotel_id
                INNER JOIN huespedes h
                    ON h.id = r.huesped_id
                   AND h.hotel_id = r.hotel_id
                LEFT JOIN reservacion_habitaciones rh2
                    ON rh2.reservacion_id = r.id
                WHERE r.hotel_id = ?
                  AND rh.habitacion_id = ?
                  AND hab_scope.hotel_id = ?
                  AND r.estado IN ('confirmada', 'checked_in')
                  AND r.fecha_entrada < ?
                  AND r.fecha_salida > ?
                GROUP BY r.id, r.fecha_entrada, r.fecha_salida, r.hora_llegada_estimada, r.estado, h.nombre_completo, h.telefono
                ORDER BY r.fecha_entrada ASC, COALESCE(r.hora_llegada_estimada, '23:59:59') ASC, r.id ASC
                LIMIT {$limite}";

        $stmt = $db->query($sql, [
            $hotelId,
            $habitacionId,
            $hotelId,
            $fechaFinExclusiva,
            $fechaInicio,
        ]);

        $rows = $stmt ? $stmt->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        error_log('No se pudieron consultar reservaciones para mantenimiento: ' . $e->getMessage());
        return [];
    }
}

private function confirmoConflictoMantenimiento(): bool {
    return trim((string)$this->getPost('confirmar_conflicto_mantenimiento', '')) === '1';
}

private function mensajeConflictoMantenimiento(array $reservas, string $accion): string {
    $accionTexto = $accion === 'programar' ? 'programar este mantenimiento' : 'iniciar mantenimiento';
    $detalles = [];

    foreach (array_slice($reservas, 0, 3) as $reserva) {
        $nombre = trim((string)($reserva['nombre_completo'] ?? 'Huesped'));
        $entrada = !empty($reserva['fecha_entrada']) ? date('d/m/Y', strtotime((string)$reserva['fecha_entrada'])) : 'sin fecha';
        $hora = trim((string)($reserva['hora_llegada_estimada'] ?? ''));
        $detalles[] = $nombre . ' llega ' . $entrada . ($hora !== '' ? ' ' . substr($hora, 0, 5) : '');
    }

    $resumen = implode('; ', $detalles);
    if (count($reservas) > 3) {
        $resumen .= '; +' . (count($reservas) - 3) . ' mas';
    }

    return 'Atencion: antes de ' . $accionTexto . ', esta habitacion tiene reservacion proxima o empalmada. ' .
        $resumen . '. Marca la confirmacion del aviso si el hotel ya gestiono el riesgo.';
}

private function registrarNotificacionMantenimientoReserva(int $habitacionId, string $habitacionNumero, array $reservas, string $accion): void {
    if ($habitacionId <= 0 || empty($reservas)) {
        return;
    }

    $primera = $reservas[0];
    $nombre = trim((string)($primera['nombre_completo'] ?? 'Huesped'));
    $entrada = !empty($primera['fecha_entrada']) ? date('d/m/Y', strtotime((string)$primera['fecha_entrada'])) : 'sin fecha';
    $hora = trim((string)($primera['hora_llegada_estimada'] ?? ''));
    $horaTexto = $hora !== '' ? ' a las ' . substr($hora, 0, 5) : '';
    $accionTexto = $accion === 'programado' ? 'programado' : 'iniciado';
    $total = count($reservas);

    NotificacionService::crear([
        'hotel_id' => $this->hotelIdActual(),
        'modulo' => 'habitaciones',
        'tipo' => 'mantenimiento_reserva_proxima',
        'severidad' => 'alta',
        'titulo' => 'Mantenimiento con reserva proxima',
        'mensaje' => 'Hab. ' . $habitacionNumero . ': mantenimiento ' . $accionTexto .
            ' con ' . $total . ' reservacion(es) activa(s). Proxima llegada: ' . $nombre . ' el ' . $entrada . $horaTexto .
            '. Revisar reasignacion o seguimiento operativo.',
        'entidad_tipo' => 'habitacion',
        'entidad_id' => $habitacionId,
        'url' => 'habitaciones/' . $habitacionId,
        'dedupe_key' => 'habitaciones.mantenimiento_reserva.' . $habitacionId . '.' . $accion . '.' . date('YmdHi'),
        'creada_por' => function_exists('user_id') ? user_id() : null,
    ]);
}

    /**
     * Liberar habitacion
     */
/**
 * Activar manualmente mantenimiento programado vencido o de hoy.
 * POST /habitaciones/activar-mantenimiento-programado/{id}
 */
public function activarMantenimientoProgramadoAction() {
    if (!$this->isPost()) {
        $this->redirect('reportes/mantenimiento-programado');
        return;
    }

    $this->validateCSRF();
    $this->requirePermission('habitaciones.mantenimiento');

    $mantenimiento_id = (int)($this->route_params['id'] ?? 0);
    $dias = max(0, min(90, (int)$this->getPost('dias', 30)));
    $returnTo = trim((string)$this->getPost('return_to', 'preview'));

    require_once __DIR__ . '/../models/Mantenimiento.php';
    $mantenimientoModel = new Mantenimiento();
    $resultado = $mantenimientoModel->activarProgramadoManual($mantenimiento_id, user_id());

    if (!empty($resultado['success'])) {
        $habitacionId = (int)($resultado['habitacion_id'] ?? 0);
        $habitacionNumero = (string)($resultado['habitacion_numero'] ?? $habitacionId);

        set_mensaje('Mantenimiento programado activado para habitacion ' . $habitacionNumero, 'success');
        $this->registrarNotificacionHabitacion(
            $habitacionId,
            'mantenimiento_programado_activado',
            'Mantenimiento programado activado',
            'La habitacion paso a mantenimiento desde la activacion manual.',
            'alta'
        );
        $this->registrarAuditoriaMantenimientoProgramado('mantenimiento_programado.activado_manual', [
            'mantenimiento_id' => $mantenimiento_id,
            'habitacion_id' => $habitacionId,
            'resultado' => 'activado',
        ]);
    } else {
        $mensaje = trim((string)($resultado['message'] ?? 'No se pudo activar el mantenimiento programado'));
        set_mensaje($mensaje, 'error');
        $this->registrarAuditoriaMantenimientoProgramado('mantenimiento_programado.activacion_bloqueada', [
            'mantenimiento_id' => $mantenimiento_id,
            'resultado' => 'bloqueado',
            'motivo' => $mensaje,
        ]);
    }

    if ($returnTo === 'habitacion' && !empty($resultado['habitacion_id'])) {
        $this->redirect('habitaciones/' . (int)$resultado['habitacion_id']);
        return;
    }

    $this->redirect('reportes/mantenimiento-programado?dias=' . $dias);
}

/**
 * Registrar auditoria de mantenimiento sin bloquear el flujo principal.
 */
private function registrarAuditoriaMantenimientoProgramado(string $accion, array $contexto): void {
    try {
        $mantenimientoId = isset($contexto['mantenimiento_id']) ? (int)$contexto['mantenimiento_id'] : 0;
        AuditService::record($accion, [
            'hotel_id' => $this->hotelIdActual(),
            'usuario_id' => user_id(),
            'entidad_tipo' => 'mantenimiento',
            'entidad_id' => $mantenimientoId > 0 ? (string)$mantenimientoId : null,
            'descripcion' => $accion === 'mantenimiento_programado.activado_manual'
                ? 'Mantenimiento programado activado manualmente'
                : 'Activacion manual de mantenimiento programado bloqueada',
            'datos_despues' => $contexto,
        ]);
    } catch (Throwable $e) {
        error_log('No se pudo registrar auditoria de mantenimiento programado: ' . $e->getMessage());
    }
}

    public function liberarAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    
    $id = $this->route_params['id'] ?? 0;
    
    try {
        // Obtener datos de la habitación
        $habitacion = $this->habitacionModel->find($id);
        
        if (!$habitacion) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Habitación no encontrada'
            ]);
            exit;
        }
        
        // Verificar que esté en estado limpieza
        if ($habitacion['estado'] !== 'limpieza') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Solo se pueden liberar habitaciones en limpieza'
            ]);
            exit;
        }

        // Personal que hizo la limpieza (obligatorio cuando el selector estuvo presente).
        $personalPost = $this->personalLimpiezaPost();
        $errorPersonal = $this->validarPersonalLimpiezaPost($personalPost);
        if ($errorPersonal !== null) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $errorPersonal
            ]);
            exit;
        }

        // Actualizar estado a disponible
        $actualizado = $this->habitacionModel->update($id, ['estado' => 'disponible']);

        if ($actualizado) {
            // Sincronizar tarea operativa: cerrar la limpieza activa registrando quien la hizo.
            if ($personalPost['confirmado']) {
                $this->registrarLimpiezaConPersonal((int)$id, $personalPost['sin_personal'] ? [] : $personalPost['ids']);
            } else {
                $this->completarTareaLimpiezaAuto((int)$id);
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'numero' => $habitacion['numero'],
                'message' => 'Habitación marcada como disponible'
            ]);
            exit;
        } else {
            throw new Exception('No se pudo actualizar el estado');
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
        exit;
    }
}

    
    /**
     * Vista de disponibilidad
     */
    public function disponiblesAction() {
        $fecha_entrada = $this->getQuery('fecha_entrada', date('Y-m-d'));
        $fecha_salida = $this->getQuery('fecha_salida', date('Y-m-d', strtotime('+1 day')));
        $tipo_filtro = $this->getQuery('tipo');
        
        // Obtener habitaciones disponibles
        $habitaciones_disponibles = $this->habitacionModel->disponiblesEnFechas($fecha_entrada, $fecha_salida, $tipo_filtro);
        
        View::renderTemplate('habitaciones/disponibles', [
            'title' => 'Disponibilidad de Habitaciones - ' . current_hotel_display_name(),
            'fecha_entrada' => $fecha_entrada,
            'fecha_salida' => $fecha_salida,
            'tipo_filtro' => $tipo_filtro,
            'habitaciones_disponibles' => $habitaciones_disponibles,
            'tipos' => $this->catalogoTiposHabitacion()
        ]);
    }
    
    // ==================== MÉTODOS PRIVADOS ====================

    private function catalogoTiposHabitacion()
    {
        $tipos = function_exists('hotel_room_catalog_types')
            ? hotel_room_catalog_types($this->hotelIdActual())
            : [];

        return !empty($tipos) ? $tipos : Habitacion::getTipos();
    }

    private function anexarPropietariosHabitaciones(int $hotelId, array $habitaciones): array
    {
        if ($hotelId <= 0 || !class_exists('PropietarioDistribucionService')) {
            return $habitaciones;
        }

        try {
            $service = new PropietarioDistribucionService();
            $config = $service->configuracionParaHotel($hotelId);
        } catch (Throwable $e) {
            error_log('No se pudo cargar propietarios para habitaciones: ' . $e->getMessage());
            return $habitaciones;
        }

        foreach ($habitaciones as &$habitacion) {
            if (!is_array($habitacion)) {
                continue;
            }

            try {
                $ownerKey = $service->propietarioParaHabitacion($habitacion, $config);
                $ownerData = $config['propietarios'][$ownerKey] ?? [];
                $habitacion['propietario_key'] = $ownerKey;
                $habitacion['propietario_nombre'] = $service->nombrePropietario($ownerKey, $config);
                $habitacion['propietario_participacion_pct'] = (float) ($ownerData['participacion_pct'] ?? 100);
                $habitacion['propietario_es_default'] = $ownerKey === ($config['propietario_default'] ?? '');
            } catch (Throwable $e) {
                error_log('No se pudo resolver propietario de habitacion: ' . $e->getMessage());
            }
        }
        unset($habitacion);

        return $habitaciones;
    }

    private function catalogoPisosHabitacion()
    {
        $pisos = function_exists('hotel_room_catalog_floors')
            ? hotel_room_catalog_floors($this->hotelIdActual())
            : [];

        return !empty($pisos) ? $pisos : Habitacion::getPisos();
    }

    private function catalogoAmenidadesHabitacion()
    {
        $amenidades = function_exists('hotel_room_catalog_amenities')
            ? hotel_room_catalog_amenities($this->hotelIdActual())
            : [];

        return !empty($amenidades) ? $amenidades : [
            'pantalla' => 'Pantalla',
            'balcon' => 'Balcon',
            'jacuzzi' => 'Jacuzzi',
            'amplia' => 'Mas amplia',
        ];
    }

    private function normalizarCaracteristicasSeleccionadas($seleccionadas): array
    {
        if (!is_array($seleccionadas)) {
            $seleccionadas = $seleccionadas !== null && $seleccionadas !== '' ? [$seleccionadas] : [];
        }

        $normalizadas = [];
        foreach ($seleccionadas as $codigo) {
            $codigo = strtolower(trim((string)$codigo));
            $codigo = preg_replace('/[^a-z0-9_\-]/', '', $codigo);
            if ($codigo !== '') {
                $normalizadas[] = $codigo;
            }
        }

        return array_values(array_unique($normalizadas));
    }

    private function normalizarTipoHabitacionParaAlmacenamiento(string $tipo, array &$especiales): string
    {
        $tipo = strtolower(trim($tipo));

        $mapaSinMigracion = [
            'doble_jacuzzi' => 'doble',
            'sencilla_jacuzzi' => 'sencilla',
        ];

        if (isset($mapaSinMigracion[$tipo])) {
            $especiales[] = 'jacuzzi';
            $especiales = array_values(array_unique($especiales));
            return $mapaSinMigracion[$tipo];
        }

        if (isset($this->tiposAlmacenamientoCompatibles()[$tipo])) {
            return $tipo;
        }

        $label = $this->labelTipoHabitacionCatalogo($tipo);
        $texto = strtolower(trim($tipo . ' ' . $label));
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($ascii !== false) {
            $texto = $ascii;
        }

        if (strpos($texto, 'jacuzzi') !== false) {
            $especiales[] = 'jacuzzi';
            $especiales = array_values(array_unique($especiales));
        }

        if (strpos($texto, 'cuadruple') !== false || strpos($texto, 'cua') !== false) {
            return 'cuadruple';
        }
        if (strpos($texto, 'triple') !== false || strpos($texto, 'tri') !== false) {
            return 'triple';
        }
        if (strpos($texto, 'doble') !== false || strpos($texto, 'dob') !== false) {
            return 'doble';
        }
        if (strpos($texto, 'sencilla') !== false || strpos($texto, 'simple') !== false || strpos($texto, 'sen') !== false) {
            return 'sencilla';
        }

        if (function_exists('hotel_room_catalog_type_rows')) {
            foreach (hotel_room_catalog_type_rows($this->hotelIdActual(), true) as $row) {
                if (strtolower((string)($row['codigo'] ?? '')) !== $tipo) {
                    continue;
                }

                $capacidad = max(1, (int)($row['capacidad_default'] ?? 2));
                if ($capacidad >= 7) {
                    return 'cuadruple';
                }
                if ($capacidad >= 5) {
                    return 'triple';
                }
                if ($capacidad >= 3) {
                    return 'doble';
                }

                return 'sencilla';
            }
        }

        return $tipo;
    }

    private function tiposAlmacenamientoCompatibles(): array
    {
        $compatibles = [
            'sencilla' => true,
            'doble' => true,
            'triple' => true,
            'cuadruple' => true,
            'sencilla_manolo' => true,
            'doble_manolo' => true,
        ];

        return $compatibles;
    }

    private function labelTipoHabitacionCatalogo(string $tipo): string
    {
        foreach ($this->catalogoTiposHabitacion() as $codigo => $label) {
            if (strtolower((string)$codigo) === strtolower($tipo)) {
                return (string)$label;
            }
        }

        return $tipo;
    }

    private function defaultsCapacidadCamas(string $tipo): array
    {
        $tipo = strtolower(trim($tipo));
        $baseTipo = $tipo;
        if ($tipo === 'doble_jacuzzi') {
            $baseTipo = 'doble';
        } elseif ($tipo === 'sencilla_jacuzzi') {
            $baseTipo = 'sencilla';
        }

        $defaults = [
            'sencilla' => ['capacidad_personas' => 2, 'camas_matrimoniales' => 1, 'camas_individuales' => 0],
            'doble' => ['capacidad_personas' => 4, 'camas_matrimoniales' => 2, 'camas_individuales' => 0],
            'triple' => ['capacidad_personas' => 6, 'camas_matrimoniales' => 3, 'camas_individuales' => 0],
            'cuadruple' => ['capacidad_personas' => 8, 'camas_matrimoniales' => 4, 'camas_individuales' => 0],
            'sencilla_manolo' => ['capacidad_personas' => 2, 'camas_matrimoniales' => 1, 'camas_individuales' => 0],
            'doble_manolo' => ['capacidad_personas' => 4, 'camas_matrimoniales' => 2, 'camas_individuales' => 0],
        ];

        $resultado = $defaults[$baseTipo] ?? ['capacidad_personas' => 2, 'camas_matrimoniales' => 1, 'camas_individuales' => 0];

        if (function_exists('hotel_room_catalog_type_rows')) {
            foreach (hotel_room_catalog_type_rows($this->hotelIdActual(), true) as $row) {
                if (strtolower((string)($row['codigo'] ?? '')) !== $tipo) {
                    continue;
                }

                $capacidad = (int)($row['capacidad_default'] ?? 0);
                if ($capacidad > 0) {
                    $resultado['capacidad_personas'] = $capacidad;
                }
                break;
            }
        }

        return $resultado;
    }

    private function normalizarEnteroHabitacion($valor, int $default, int $min, int $max): int
    {
        if ($valor === null || $valor === '') {
            $valor = $default;
        }

        $valor = (int)$valor;
        return $valor;
    }

    private function construirDescripcionCaracteristicas(string $tipo, array $especiales, string $custom): string
    {
        $generada = $this->generarDescripcionCaracteristicas($tipo, $especiales);
        $partes = array_filter(array_map('trim', explode(',', $generada)));

        if ($custom !== '') {
            $extras = preg_split('/[,;\r\n]+/', $custom) ?: [];
            foreach ($extras as $extra) {
                $extra = trim((string)$extra);
                if ($extra !== '') {
                    $partes[] = $extra;
                }
            }
        }

        $unicas = [];
        $normalizadas = [];
        foreach ($partes as $parte) {
            $clave = strtolower(trim(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $parte) ?: $parte));
            $clave = preg_replace('/\s+/', ' ', $clave);
            if ($clave === '' || isset($normalizadas[$clave])) {
                continue;
            }

            $normalizadas[$clave] = true;
            $unicas[] = $parte;
        }

        return implode(', ', $unicas);
    }

    private function limpiarMarcadoresTipoCatalogo(string $descripcion): string
    {
        $partes = preg_split('/[,;\r\n]+/', $descripcion) ?: [];
        $limpias = [];

        foreach ($partes as $parte) {
            $parte = trim((string)$parte);
            if ($parte === '' || preg_match('/^Tipo catalogo\s*:/i', $parte)) {
                continue;
            }

            $limpias[] = $parte;
        }

        return implode(', ', $limpias);
    }

    private function extraerTipoCatalogoDeCaracteristicas(string $descripcion): ?array
    {
        if (!preg_match('/(?:^|[,;\r\n]\s*)Tipo catalogo\s*:\s*([^\[\r\n,;]+?)\s*\[([a-z0-9_\-]+)\]/i', $descripcion, $matches)) {
            return null;
        }

        $codigo = strtolower(trim((string)($matches[2] ?? '')));
        $label = trim((string)($matches[1] ?? ''));

        if ($codigo === '') {
            return null;
        }

        return [
            'codigo' => $codigo,
            'label' => $label,
        ];
    }

    private function agregarTipoCatalogoEnCaracteristicas(string $descripcion, string $tipoSolicitado, string $tipoAlmacenamiento): string
    {
        $descripcion = $this->limpiarMarcadoresTipoCatalogo($descripcion);
        $tipoSolicitado = strtolower(trim($tipoSolicitado));
        $tipoAlmacenamiento = strtolower(trim($tipoAlmacenamiento));

        if ($tipoSolicitado === '' || $tipoSolicitado === $tipoAlmacenamiento) {
            return $descripcion;
        }

        $label = $this->labelTipoHabitacionCatalogo($tipoSolicitado);
        if ($label === '' || strtolower(trim($label)) === $tipoSolicitado) {
            return $descripcion;
        }

        $marcador = 'Tipo catalogo: ' . $label . ' [' . $tipoSolicitado . ']';

        return $descripcion !== ''
            ? $descripcion . ', ' . $marcador
            : $marcador;
    }

    private function codigoTipoCatalogoHabitacion(array $habitacion): string
    {
        $tipo = strtolower(trim((string)($habitacion['tipo'] ?? '')));
        $caracteristicas = (string)($habitacion['caracteristicas'] ?? '');
        $tipoCatalogo = $this->extraerTipoCatalogoDeCaracteristicas($caracteristicas);

        if (is_array($tipoCatalogo) && !empty($tipoCatalogo['codigo'])) {
            return (string)$tipoCatalogo['codigo'];
        }

        $texto = strtolower($caracteristicas);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($ascii !== false) {
            $texto = $ascii;
        }

        if (strpos($texto, 'jacuzzi') !== false) {
            if ($tipo === 'doble') {
                return 'doble_jacuzzi';
            }
            if ($tipo === 'sencilla') {
                return 'sencilla_jacuzzi';
            }
        }

        return $tipo;
    }

    private function mensajeErrorHabitacion(Throwable $e): string
    {
        $mensaje = $e->getMessage();
        if (strpos($mensaje, 'Duplicate entry') !== false && strpos($mensaje, 'numero') !== false) {
            return 'Ya existe una habitacion con este numero en este hotel. No se puede repetir.';
        }

        return $mensaje;
    }

    private function validarHabitacionConCatalogos(array $data)
    {
        $errores = [];

        if (empty($data['numero'])) {
            $errores[] = 'El numero de habitacion es obligatorio';
        } elseif (strlen((string) $data['numero']) > 10) {
            $errores[] = 'El numero de habitacion no puede exceder 10 caracteres';
        }

        $tiposValidos = $this->catalogoTiposHabitacion();
        if (empty($data['tipo']) || !array_key_exists((string) $data['tipo'], $tiposValidos)) {
            $errores[] = 'Debe seleccionar un tipo de habitacion valido';
        }
        if (!empty($data['tipo']) && !isset($this->tiposAlmacenamientoCompatibles()[(string)$data['tipo']])) {
            $errores[] = 'El tipo seleccionado todavia no es compatible con el catalogo tecnico de habitaciones.';
        }

        if (!is_numeric($data['precio_base']) || $data['precio_base'] <= 0) {
            $errores[] = 'El precio debe ser un numero mayor a cero';
        }

        $capacidad = (int)($data['capacidad_personas'] ?? 0);
        if ($capacidad < 1 || $capacidad > 30) {
            $errores[] = 'La capacidad debe estar entre 1 y 30 personas.';
        }

        $camasMatrimoniales = (int)($data['camas_matrimoniales'] ?? 0);
        $camasIndividuales = (int)($data['camas_individuales'] ?? 0);
        if ($camasMatrimoniales < 0 || $camasIndividuales < 0 || ($camasMatrimoniales + $camasIndividuales) < 1) {
            $errores[] = 'Debe registrar al menos una cama en la habitacion.';
        }
        if ($camasMatrimoniales > 20 || $camasIndividuales > 20) {
            $errores[] = 'El numero de camas no puede exceder 20 por tipo.';
        }

        $pisosValidos = $this->catalogoPisosHabitacion();
        $piso = (int) ($data['piso'] ?? 0);
        if (!is_numeric($data['piso'] ?? null) || !array_key_exists($piso, $pisosValidos)) {
            $errores[] = 'Debe seleccionar un piso valido del catalogo.';
        }

        return $errores;
    }

    private function hotelIdActual()
    {
        return obtenerHotelIdActualCompat();
    }
    
    /**
     * Procesar múltiples imágenes
     */
    private function procesarMultiplesImagenes($files, $habitacion_id, $numero_habitacion) {
        $imagenes_guardadas = [];
        $errores = [];
        $maxFiles = 10;
        
        // Verificar estructura de $_FILES para múltiples archivos
        if (!is_array($files['name'])) {
            return ['success' => false, 'error' => 'Formato de archivos incorrecto'];
        }
        
        $totalFiles = count($files['name']);
        
        if ($totalFiles > $maxFiles) {
            return ['success' => false, 'error' => "Solo se permiten máximo {$maxFiles} imágenes"];
        }
        
        // Procesar cada archivo
        for ($i = 0; $i < $totalFiles; $i++) {
            // Saltar si no hay archivo
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            // Verificar errores de upload
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errores[] = "Error al subir {$files['name'][$i]}";
                continue;
            }
            
            // Crear array temporal para procesarImagenHabitacion
            $archivo_temp = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            // Procesar imagen individual
            $resultado = $this->procesarImagenHabitacion($archivo_temp, $numero_habitacion);
            
            if ($resultado['success']) {
                // Guardar en la tabla habitacion_imagenes
                $imagen_data = [
                    'hotel_id' => $this->hotelIdActual(),
                    'habitacion_id' => $habitacion_id,
                    'url' => $resultado['path'],
                    'descripcion' => null,
                    'es_principal' => count($imagenes_guardadas) === 0 ? 1 : 0, // Primera imagen es principal
                    'orden' => count($imagenes_guardadas)
                ];
                
                $imagen_guardada = $this->habitacionImagenModel->create($imagen_data);
                
                if ($imagen_guardada) {
                    $imagenes_guardadas[] = [
                        'id' => is_array($imagen_guardada) ? $imagen_guardada['id'] : $imagen_guardada,
                        'url' => $resultado['path'],
                        'filename' => $resultado['filename']
                    ];
                }
            } else {
                $errores[] = $resultado['error'];
            }
        }
        
        if (!empty($errores) && empty($imagenes_guardadas)) {
            return ['success' => false, 'error' => implode(', ', $errores)];
        }
        
        return [
            'success' => true,
            'imagenes' => $imagenes_guardadas,
            'errores' => $errores
        ];
    }
    
    /**
     * Procesar imagen de habitación
     */
    private function procesarImagenHabitacion($archivo, $numeroHabitacion) {
        // Validaciones
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $minWidth = 200;
        $minHeight = 200;
        
        // Verificar tamaño
        if ($archivo['size'] > $maxSize) {
            return ['success' => false, 'error' => 'La imagen no puede exceder 5MB'];
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Solo se permiten imágenes JPG, PNG, GIF o WebP'];
        }
        
        // Verificar dimensiones
        $imageInfo = getimagesize($archivo['tmp_name']);
        if (!$imageInfo || $imageInfo[0] < $minWidth || $imageInfo[1] < $minHeight) {
            return ['success' => false, 'error' => "La imagen debe tener al menos {$minWidth}x{$minHeight} píxeles"];
        }
        
        // Crear directorio si no existe
        $uploadDir = ensure_upload_directory('habitaciones');
        if (!$uploadDir) {
            return ['success' => false, 'error' => 'No se pudo crear el directorio de imágenes'];
        }
        
        // Generar nombre único
        $extension = get_extension_by_mime($mimeType);
        $nombreArchivo = generate_room_image_name($numeroHabitacion, $extension);
        $rutaCompleta = $uploadDir . '/' . $nombreArchivo;
        $rutaRelativa = 'uploads/habitaciones/' . $nombreArchivo;
        
        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            // Optimizar imagen si es necesario (opcional)
            $this->optimizarImagen($rutaCompleta, $mimeType);
            
            return [
                'success' => true,
                'path' => $rutaRelativa,
                'filename' => $nombreArchivo
            ];
        } else {
            return ['success' => false, 'error' => 'Error al guardar la imagen'];
        }
    }
    
    /**
     * Optimizar imagen (redimensionar si es muy grande)
     */
    private function optimizarImagen($rutaArchivo, $mimeType) {
        $maxWidth = 1920;
        $maxHeight = 1080;
        $quality = 85;
        
        $imageInfo = getimagesize($rutaArchivo);
        if (!$imageInfo) return false;
        
        list($width, $height) = $imageInfo;
        
        // Si la imagen es pequeña, no hacer nada
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }
        
        // Calcular nuevas dimensiones manteniendo proporción
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
        
        // Crear imagen desde el archivo
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($rutaArchivo);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($rutaArchivo);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($rutaArchivo);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($rutaArchivo);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) return false;
        
        // Crear nueva imagen redimensionada
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparencia para PNG y GIF
        if ($mimeType == 'image/png' || $mimeType == 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Guardar imagen optimizada
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($resizedImage, $rutaArchivo, $quality);
                break;
            case 'image/png':
                imagepng($resizedImage, $rutaArchivo, 6);
                break;
            case 'image/gif':
                imagegif($resizedImage, $rutaArchivo);
                break;
            case 'image/webp':
                imagewebp($resizedImage, $rutaArchivo, $quality);
                break;
        }
        
        // Limpiar memoria
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        return true;
    }
    
    /**
     * Eliminar imagen de habitación
     */
    private function eliminarImagenHabitacion($rutaImagen) {
        if (empty($rutaImagen)) return true;
        
        $rutaCompleta = PUBLIC_PATH . '/' . ltrim($rutaImagen, '/');
        
        if (file_exists($rutaCompleta) && is_safe_image_path($rutaImagen)) {
            return unlink($rutaCompleta);
        }
        
        return true;
    }
    /**
 * Liberar múltiples habitaciones (marcar como disponibles)
 * Endpoint: POST /habitaciones/liberar-multiples
 */
/**
 * Liberar múltiples habitaciones (marcar como disponibles)
 * Endpoint: POST /habitaciones/liberar-multiples
 */
public function liberarMultiplesAction() {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Establecer header JSON
    header('Content-Type: application/json; charset=utf-8');
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    // Verificar CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    try {
        // Obtener IDs de habitaciones
        $habitaciones_ids = $_POST['habitaciones_ids'] ?? [];
        
        if (empty($habitaciones_ids) || !is_array($habitaciones_ids)) {
            throw new Exception('Debe seleccionar al menos una habitación');
        }
        
        // Validar que todos los IDs sean números
        $habitaciones_ids = array_map('intval', $habitaciones_ids);
        $habitaciones_ids = array_filter($habitaciones_ids, function($id) {
            return $id > 0;
        });
        
        if (empty($habitaciones_ids)) {
            throw new Exception('IDs de habitaciones inválidos');
        }

        // Personal que hizo la limpieza (obligatorio cuando el selector estuvo presente).
        $personalPost = $this->personalLimpiezaPost();
        $errorPersonal = $this->validarPersonalLimpiezaPost($personalPost);
        if ($errorPersonal !== null) {
            throw new Exception($errorPersonal);
        }

        $habitacion = new Habitacion();
        $actualizadas = 0;
        $numeros_habitaciones = [];

        // Procesar cada habitación
        foreach ($habitaciones_ids as $id) {
            // Obtener información de la habitación
            $hab = $habitacion->find($id);

            if (!$hab) {
                continue; // Saltar si no existe
            }

            // Solo actualizar si está en limpieza
            if ($hab['estado'] === 'limpieza') {
                $resultado = $habitacion->cambiarEstado($id, 'disponible');

                if ($resultado) {
                    $actualizadas++;
                    $numeros_habitaciones[] = $hab['numero'];
                    // Sincronizar tarea operativa: cerrar la limpieza activa registrando quien la hizo.
                    if ($personalPost['confirmado']) {
                        $this->registrarLimpiezaConPersonal((int)$id, $personalPost['sin_personal'] ? [] : $personalPost['ids']);
                    } else {
                        $this->completarTareaLimpiezaAuto((int)$id);
                    }
                }
            }
        }
        
        if ($actualizadas > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Se marcaron {$actualizadas} habitación(es) como disponibles",
                'actualizadas' => $actualizadas,
                'habitaciones' => implode(', ', $numeros_habitaciones)
            ]);
        } else {
            throw new Exception('No se pudo actualizar ninguna habitación. Verifica que estén en estado de limpieza.');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    exit;
}

    /**
     * Sincroniza la tarea operativa de limpieza cuando la habitacion se marca
     * como limpia: completa la tarea de limpieza activa del cuarto (si existe).
     * El estado de la habitacion es la accion autoritativa; aqui solo se refleja.
     * Nunca rompe el flujo principal si Tareas no esta disponible.
     */
    private function completarTareaLimpiezaAuto(int $habitacionId): void
    {
        try {
            if (!$this->tareaModel || !$this->tareaModel->tablaDisponible() || !$this->tareaModel->eventosDisponibles()) {
                return;
            }

            $hotelId = (int)$this->hotelIdActual();
            if ($hotelId <= 0 || $habitacionId <= 0) {
                return;
            }

            $tarea = $this->tareaModel->buscarTareaActivaLimpiezaPorHabitacionHotel($hotelId, $habitacionId);
            if (!$tarea || empty($tarea['id'])) {
                return;
            }

            $usuarioId = function_exists('user_id') ? user_id() : null;
            $this->tareaModel->cambiarEstadoManualParaHotel(
                (int)$tarea['id'],
                $hotelId,
                'completar',
                $usuarioId,
                'Completada automaticamente al marcar la habitacion como limpia.'
            );
        } catch (Throwable $e) {
            error_log('completarTareaLimpiezaAuto hab #' . $habitacionId . ': ' . $e->getMessage());
        }
    }

    /**
     * Cierra la limpieza de un cuarto registrando quien la hizo (uno o mas
     * trabajadores, o lista vacia = "sin registrar personal"). Fail-open: si el
     * registro falla, al menos se cierra la tarea activa como antes.
     */
    private function registrarLimpiezaConPersonal(int $habitacionId, array $trabajadorIds): void
    {
        try {
            if (!$this->tareaModel || !$this->tareaModel->tablaDisponible() || !$this->tareaModel->eventosDisponibles()) {
                return;
            }

            $hotelId = (int)$this->hotelIdActual();
            if ($hotelId <= 0 || $habitacionId <= 0) {
                return;
            }

            $usuarioId = function_exists('user_id') ? user_id() : null;
            $this->tareaModel->completarLimpiezaConPersonalParaHotel($hotelId, $habitacionId, $trabajadorIds, $usuarioId);
        } catch (Throwable $e) {
            error_log('registrarLimpiezaConPersonal hab #' . $habitacionId . ': ' . $e->getMessage());
            $this->completarTareaLimpiezaAuto($habitacionId);
        }
    }

    /**
     * Lee del POST la seleccion de personal de limpieza del selector obligatorio.
     *  - personal_confirmado=1: el cliente mostro el selector (aplica validacion).
     *  - trabajador_ids[]: uno o mas trabajadores que hicieron la limpieza.
     *  - sin_personal=1: eleccion explicita de no registrar personal.
     * Sin el flag (clientes viejos / cola offline) se conserva el flujo anterior.
     */
    private function personalLimpiezaPost(): array
    {
        $ids = [];
        $raw = $_POST['trabajador_ids'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $valor) {
                $valor = (int)$valor;
                if ($valor > 0) {
                    $ids[$valor] = $valor;
                }
            }
        }

        return [
            'confirmado' => (string)($_POST['personal_confirmado'] ?? '') === '1',
            'sin_personal' => (string)($_POST['sin_personal'] ?? '') === '1',
            'ids' => array_values($ids),
        ];
    }

    /**
     * Valida la seleccion de personal. Devuelve un mensaje de error o null si es valida.
     */
    private function validarPersonalLimpiezaPost(array $personalPost): ?string
    {
        if (!$personalPost['confirmado']) {
            return null;
        }

        if (!$personalPost['sin_personal'] && empty($personalPost['ids'])) {
            return 'Indica quién hizo la limpieza o marca "Sin registrar personal".';
        }

        if (!empty($personalPost['ids']) && $this->tareaModel && $this->tareaModel->tablaDisponible()) {
            $activos = [];
            foreach ($this->tareaModel->trabajadoresActivosOpciones((int)$this->hotelIdActual()) as $t) {
                $activos[(int)$t['id']] = true;
            }
            foreach ($personalPost['ids'] as $tid) {
                if (!isset($activos[$tid])) {
                    return 'Uno de los trabajadores seleccionados ya no está activo en el hotel.';
                }
            }
        }

        return null;
    }

    /**
     * Datos de personal de limpieza para los selectores de la vista:
     * lista de trabajadores activos (roles de limpieza primero) y personal ya
     * asignado a la limpieza activa de cada cuarto (para preseleccionar).
     */
    private function personalLimpiezaVista(int $hotelId): array
    {
        $datos = ['disponible' => false, 'personal' => [], 'asignadas' => []];

        try {
            if ($hotelId <= 0 || !$this->tareaModel || !$this->tareaModel->tablaDisponible() || !$this->tareaModel->eventosDisponibles()) {
                return $datos;
            }

            foreach ($this->tareaModel->trabajadoresActivosOpciones($hotelId) as $t) {
                $datos['personal'][] = [
                    'id' => (int)$t['id'],
                    'nombre' => (string)$t['nombre_completo'],
                    'rol' => (string)($t['rol_laboral'] ?? ''),
                ];
            }

            usort($datos['personal'], static function (array $a, array $b): int {
                $esLimpieza = static function (array $p): int {
                    $rol = strtolower($p['rol']);
                    return (strpos($rol, 'camarist') !== false || strpos($rol, 'limpiez') !== false) ? 0 : 1;
                };
                $orden = $esLimpieza($a) <=> $esLimpieza($b);
                return $orden !== 0 ? $orden : strcasecmp($a['nombre'], $b['nombre']);
            });

            $datos['disponible'] = !empty($datos['personal']);
            if (!$datos['disponible']) {
                return $datos;
            }

            foreach ($this->tareaModel->listarPorHotel($hotelId, ['categoria' => 'limpieza'], 300) as $tarea) {
                $habId = (int)($tarea['habitacion_id'] ?? 0);
                $estado = (string)($tarea['estado'] ?? '');
                if ($habId <= 0 || !in_array($estado, ['pendiente', 'asignada', 'en_proceso'], true) || isset($datos['asignadas'][$habId])) {
                    continue;
                }

                $ids = array_values(array_filter(array_map('intval', explode(',', (string)($tarea['trabajadores_ids'] ?? '')))));
                if (!empty($ids)) {
                    $datos['asignadas'][$habId] = $ids;
                }
            }
        } catch (Throwable $e) {
            error_log('personalLimpiezaVista: ' . $e->getMessage());
        }

        return $datos;
    }

    /**
     * API: personal activo para los selectores de limpieza de otras vistas
     * (p. ej. "Finalizar limpieza" en el detalle de habitacion).
     * GET /api/habitaciones/limpieza-personal
     */
    public function limpiezaPersonalApiAction()
    {
        header('Content-Type: application/json');

        try {
            $datos = $this->personalLimpiezaVista((int)$this->hotelIdActual());
            echo json_encode([
                'success' => true,
                'disponible' => $datos['disponible'],
                'personal' => $datos['personal'],
                'asignadas' => $datos['asignadas'],
            ]);
        } catch (Throwable $e) {
            // Fail-open: el selector es opcional para el cliente si no hay datos.
            echo json_encode(['success' => true, 'disponible' => false, 'personal' => [], 'asignadas' => []]);
        }
        exit;
    }

    /**
     * Generar descripción automática de características
     */
    private function generarDescripcionCaracteristicas($tipo, $especiales = []) {
        $especiales = is_array($especiales) ? $especiales : [];

        // Informacion base por tipo conocido
        $camas_info = [
            'sencilla' => '1 cama matrimonial',
            'doble' => '2 camas matrimoniales',
            'triple' => '3 camas matrimoniales',
            'cuadruple' => '4 camas matrimoniales',
            'doble_jacuzzi' => '2 camas matrimoniales',
            'sencilla_jacuzzi' => '1 cama matrimonial'
        ];

        $baseTipo = $camas_info[$tipo] ?? '';

        if ($baseTipo === '' && function_exists('hotel_room_catalog_type_rows')) {
            foreach (hotel_room_catalog_type_rows($this->hotelIdActual(), true) as $row) {
                if (($row['codigo'] ?? '') !== $tipo) {
                    continue;
                }

                $baseTipo = trim((string) ($row['descripcion'] ?? ''));
                if ($baseTipo === '') {
                    $baseTipo = trim((string) ($row['nombre'] ?? ''));
                }
                break;
            }
        }

        $descripcion = [$baseTipo !== '' ? $baseTipo : 'Habitacion'];

        if (strpos((string) $tipo, 'jacuzzi') !== false && !in_array('jacuzzi', $especiales, true)) {
            $descripcion[] = 'jacuzzi';
        }

        $amenidades = $this->catalogoAmenidadesHabitacion();
        $orden_especiales = array_values(array_unique(array_merge(
            ['jacuzzi', 'pantalla', 'balcon', 'amplia'],
            array_keys($amenidades)
        )));

        foreach ($orden_especiales as $especial) {
            if (!in_array($especial, $especiales, true)) {
                continue;
            }

            if ($especial === 'jacuzzi' && strpos((string) $tipo, 'jacuzzi') !== false) {
                continue;
            }

            if (isset($amenidades[$especial])) {
                $descripcion[] = $amenidades[$especial];
            }
        }

        $base = ['bano', 'ventilador', 'agua caliente', 'Wifi', 'Cablevision', 'estacionamiento'];

        if (!in_array('pantalla', $especiales, true)) {
            array_unshift($base, 'TV normal');
        }

        $descripcion = array_merge($descripcion, $base);

        return implode(', ', $descripcion);
    }
    
    /**
     * Crear entrada en control de llaves
     */
    /**
 * Crear entrada en control de llaves
 */
private function crearControlLlave($habitacion_id) {
    $db = Database::getInstance();
    $sql = "INSERT INTO control_llaves (habitacion_id, estado, tiene_llave, created_at) 
            VALUES (?, 'disponible', 1, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()";
    return $db->query($sql, [$habitacion_id]);
}
    
    /**
     * Obtener mantenimiento actual
     */
    private function obtenerMantenimientoActual($habitacion_id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM mantenimientos_habitaciones 
                WHERE habitacion_id = ? AND hotel_id = ? AND estado = 'en_proceso'
                ORDER BY fecha_inicio DESC LIMIT 1";
        $stmt = $db->query($sql, [$habitacion_id, $this->hotelIdActual()]);
        return $stmt->fetch();
    }
    
    /**
     * Cambiar estado de habitación
     */
    public function cambiarEstadoAction() {
        if (!$this->isPost()) {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        }
        
        $this->validateCSRF();
        
        $id = $this->route_params['id'] ?? 0;
        $nuevo_estado = $this->getPost('estado');
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            json_response(['success' => false, 'message' => 'Habitación no encontrada']);
        }
        
        // Validar transición de estado
        if (!puede_cambiar_estado_habitacion($habitacion['estado'], $nuevo_estado)) {
            json_response(['success' => false, 'message' => 'Cambio de estado no permitido']);
        }
        
        if ($this->habitacionModel->cambiarEstado($id, $nuevo_estado)) {
            json_response(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } else {
            json_response(['success' => false, 'message' => 'Error al actualizar estado']);
        }
    }
}
