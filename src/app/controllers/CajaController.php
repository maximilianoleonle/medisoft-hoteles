<?php
/**
 * Controlador de Caja
 */

require_once __DIR__ . '/../services/ReporteEntregaService.php';
require_once __DIR__ . '/../services/NotificacionService.php';
require_once __DIR__ . '/../models/ArqueoMetodosPago.php';

class CajaController extends Controller {
    private $cajaModel;
    private $movimientoModel;
    private $categoriaModel;
    private $arqueoMetodosModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->cajaModel = new Caja();
        $this->movimientoModel = new MovimientoCaja();
        $this->categoriaModel = new CategoriaMovimiento();
        $this->arqueoMetodosModel = new ArqueoMetodosPago();
    }
    
    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        require_hotel_module('caja');
        return true;
}
    public function indexAction() {
    // Obtener caja principal
    $caja = $this->cajaModel->obtenerCajaPrincipal();
    
    if (!$caja) {
        set_mensaje('No hay ninguna caja configurada', 'error');
        $this->redirect('dashboard');
        return;
    }
    
    // Verificar si hay corte abierto
    $corteActual = $this->cajaModel->obtenerCorteActual($caja['id']);
    
    // Si no hay corte abierto, mostrar formulario de apertura
    if (!$corteActual) {
        View::renderTemplate('caja/apertura', [
            'title' => 'Abrir Caja - ' . current_hotel_display_name(),
            'caja' => $caja
        ]);
        return;
    }
    
    // Obtener resumen de caja
    $resumen = $this->cajaModel->obtenerResumenCaja($corteActual['id']);
    
    // Debug: Ver el resumen
    error_log("Resumen de caja: " . json_encode($resumen));
    
    // Obtener últimos movimientos incluyendo egresos
    $ultimosMovimientos = $this->movimientoModel->obtenerUltimosMovimientos(10);
    
    // Obtener movimientos por categoría (actualizado para incluir egresos como gastos)
    $movimientosPorCategoria = [
        'ingresos' => $this->cajaModel->obtenerMovimientosPorCategoria($corteActual['id'], 'ingreso'),
        'reversos' => $this->cajaModel->obtenerMovimientosPorCategoria($corteActual['id'], 'reverso_ingreso'),
        'gastos_reales' => $this->cajaModel->obtenerMovimientosPorCategoria($corteActual['id'], 'gasto_real'),
        'gastos' => $this->cajaModel->obtenerMovimientosPorCategoria($corteActual['id'], 'gasto') // Incluirá egresos
    ];
    
    // Debug: Ver movimientos por categoría
    error_log("Movimientos gastos/egresos: " . json_encode($movimientosPorCategoria['gastos']));
    
    // Obtener categorías para los formularios
    $categorias = [
        'ingreso' => $this->categoriaModel->obtenerPorTipo('ingreso'),
        'gasto' => $this->categoriaModel->obtenerPorTipo('gasto'),
        'egreso' => $this->categoriaModel->obtenerPorTipo('egreso') // Incluir categorías de egreso
    ];
    
    // Combinar categorías de gasto y egreso para el formulario
    $categorias['gasto'] = array_merge(
        $categorias['gasto'] ?? [],
        $categorias['egreso'] ?? []
    );
    
    View::renderTemplate('caja/index', [
        'title' => 'Caja - ' . current_hotel_display_name(),
        'caja' => $caja,
        'corte' => $corteActual,
        'resumen' => $resumen,
        'ultimos_movimientos' => $ultimosMovimientos,
        'movimientos_categoria' => $movimientosPorCategoria,
        'categorias' => $categorias,
        'metodos_pago' => MovimientoCaja::getMetodosPago()
    ]);
}
    
    /**
 * Reporte de pagos por método
 */
public function reporteMetodosAction() {
    $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
    $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
    
    $db = Database::getInstance();
    $hotel_id = obtenerHotelIdActualCompat();
    
    // Obtener totales por método y categoría
    $sql = "SELECT 
            mc.metodo_pago,
            mc.tipo,
            cm.nombre as categoria,
            COUNT(mc.id) as cantidad,
            SUM(mc.monto) as total
            FROM movimientos_caja mc
            LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
            WHERE mc.hotel_id = ?
            AND DATE(mc.created_at) BETWEEN ? AND ?
            GROUP BY mc.metodo_pago, mc.tipo, cm.id
            ORDER BY mc.metodo_pago, mc.tipo, total DESC";
    
    $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $movimientos = $stmt->fetchAll();
    
    // Organizar por método de pago
    $reporte = [
        'efectivo' => ['ingresos' => [], 'gastos' => [], 'total_ingresos' => 0, 'total_gastos' => 0],
        'tarjeta' => ['ingresos' => [], 'gastos' => [], 'total_ingresos' => 0, 'total_gastos' => 0],
        'transferencia' => ['ingresos' => [], 'gastos' => [], 'total_ingresos' => 0, 'total_gastos' => 0]
    ];
    
    foreach ($movimientos as $mov) {
        $metodo = $mov['metodo_pago'];
        $tipo = $mov['tipo'] == 'ingreso' ? 'ingresos' : 'gastos';
        
        $reporte[$metodo][$tipo][] = $mov;
        $reporte[$metodo]['total_' . $tipo] += $mov['total'];
    }
    
    View::renderTemplate('caja/reporte_metodos', [
        'title' => 'Reporte por Métodos de Pago - ' . current_hotel_display_name(),
        'reporte' => $reporte,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'metodos_pago' => MovimientoCaja::getMetodosPago()
    ]);
}

public function arqueoMetodosAction() {
    $hotelId = obtenerHotelIdActualCompat();
    $filtros = [
        'fecha_desde' => $this->getQuery('fecha_desde', ''),
        'fecha_hasta' => $this->getQuery('fecha_hasta', ''),
        'corte_id' => $this->getQuery('corte_id', 0),
        'caja_id' => $this->getQuery('caja_id', 0),
        'estado_corte' => $this->getQuery('estado_corte', 'todos'),
        'metodo_pago' => $this->getQuery('metodo_pago', 'todos'),
        'severidad' => $this->getQuery('severidad', 'todos'),
        'page' => $this->getQuery('page', 1),
        'limit' => $this->getQuery('limit', 25),
    ];

    View::renderTemplate('caja/arqueo_metodos', [
        'title' => 'Arqueo por metodo - ' . current_hotel_display_name(),
        'reporte' => $this->arqueoMetodosModel->reporteReadOnlyPorHotel((int)$hotelId, $filtros),
        'metodos_pago' => MovimientoCaja::getMetodosPago(),
    ]);
}
    /**
     * Abrir caja
     */
    public function abrirAction() {
        if (!$this->isPost()) {
            $this->redirect('caja');
            return;
        }
        
        $this->validateCSRF();
        
        $caja_id = intval($this->getPost('caja_id'));
        $monto_inicial = floatval($this->getPost('monto_inicial', 0));
        $oldInput = [
            'caja_id' => $caja_id,
            'monto_inicial' => $this->getPost('monto_inicial', '0.00'),
            'observaciones' => trim($this->getPost('observaciones', '')),
            'form_origen' => 'apertura'
        ];
        
        // Validar
        if ($monto_inicial < 0) {
            save_old_input($oldInput);
            save_form_errors(['monto_inicial' => ['El monto inicial no puede ser negativo']]);
            set_mensaje('El monto inicial no puede ser negativo', 'error');
            $this->redirect('caja');
            return;
        }
        
        // Abrir caja
        $resultado = $this->cajaModel->abrirCaja($caja_id, $monto_inicial, user_id());
        
        if ($resultado['success']) {
            clear_old_input();
            set_mensaje('Caja abierta exitosamente', 'success');
        } else {
            save_old_input($oldInput);
            save_form_errors($this->erroresCamposAperturaCaja([$resultado['message'] ?? 'No se pudo abrir la caja']));
            set_mensaje($resultado['message'], 'error');
        }
        
        $this->redirect('caja');
    }
    
    /**
     * Registrar ingreso
     */
    public function registrarIngresoAction() {
        if (!$this->isPost()) {
            $this->redirect('caja');
            return;
        }
        
        $this->validateCSRF();
        
        // Recopilar datos
        $data = [
            'tipo' => 'ingreso',
            'categoria_id' => intval($this->getPost('categoria_id')),
            'descripcion' => trim($this->getPost('descripcion')),
            'monto' => floatval($this->getPost('monto')),
            'metodo_pago' => $this->getPost('metodo_pago'),
            'referencia' => trim($this->getPost('referencia')),
            'comprobante' => trim($this->getPost('comprobante'))
        ];
        $oldInput = array_merge($data, [
            'monto' => $this->getPost('monto', ''),
            'form_origen' => 'ingreso'
        ]);
        
        // Validar
        $errores = $this->movimientoModel->validarMovimiento($data);
        
        if (!empty($errores)) {
            set_mensaje(implode('<br>', $errores), 'error');
            save_old_input($oldInput);
            save_form_errors($this->erroresCamposMovimientoCaja($errores));
            $this->redirect('caja');
            return;
        }
        
        // Registrar movimiento
        $resultado = $this->movimientoModel->registrarMovimiento($data);
        
        if ($resultado['success']) {
            clear_old_input();
            set_mensaje('Ingreso registrado exitosamente', 'success');
        } else {
            save_old_input($oldInput);
            save_form_errors($this->erroresCamposMovimientoCaja([$resultado['message'] ?? 'No se pudo registrar el ingreso']));
            set_mensaje($resultado['message'], 'error');
        }
        
        $this->redirect('caja');
    }
    
    /**
     * Registrar gasto
     */
    public function registrarGastoAction() {
        if (!$this->isPost()) {
            $this->redirect('caja');
            return;
        }
        
        $this->validateCSRF();
        
        // Recopilar datos
        $data = [
            'tipo' => 'gasto',
            'categoria_id' => intval($this->getPost('categoria_id')),
            'descripcion' => trim($this->getPost('descripcion')),
            'monto' => floatval($this->getPost('monto')),
            'metodo_pago' => $this->getPost('metodo_pago'),
            'referencia' => trim($this->getPost('referencia')),
            'comprobante' => trim($this->getPost('comprobante')),
            'proveedor' => trim($this->getPost('proveedor'))
        ];
        $oldInput = array_merge($data, [
            'monto' => $this->getPost('monto', ''),
            'form_origen' => 'gasto'
        ]);
        
        // Validar
        $errores = $this->movimientoModel->validarMovimiento($data);
        
        if (!empty($errores)) {
            set_mensaje(implode('<br>', $errores), 'error');
            save_old_input($oldInput);
            save_form_errors($this->erroresCamposMovimientoCaja($errores));
            $this->redirect('caja');
            return;
        }
        
        // Registrar movimiento
        $resultado = $this->movimientoModel->registrarMovimiento($data);
        
        if ($resultado['success']) {
            clear_old_input();
            set_mensaje('Gasto registrado exitosamente', 'success');
        } else {
            save_old_input($oldInput);
            save_form_errors($this->erroresCamposMovimientoCaja([$resultado['message'] ?? 'No se pudo registrar el gasto']));
            set_mensaje($resultado['message'], 'error');
        }
        
        $this->redirect('caja');
    }

    private function erroresCamposAperturaCaja(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = $this->normalizarMensajeFormularioCaja($mensaje);
            $campo = null;

            if (strpos($lower, 'monto') !== false || strpos($lower, 'inicial') !== false || strpos($lower, 'negativo') !== false) {
                $campo = 'monto_inicial';
            } elseif (strpos($lower, 'observacion') !== false || strpos($lower, 'nota') !== false) {
                $campo = 'observaciones';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            } else {
                $fieldErrors['_global'][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function erroresCamposMovimientoCaja(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = $this->normalizarMensajeFormularioCaja($mensaje);
            $campo = null;

            if (strpos($lower, 'categoria') !== false) {
                $campo = 'categoria_id';
            } elseif (strpos($lower, 'descripcion') !== false) {
                $campo = 'descripcion';
            } elseif (strpos($lower, 'monto') !== false || strpos($lower, 'numero') !== false || strpos($lower, 'mayor a 0') !== false) {
                $campo = 'monto';
            } elseif (strpos($lower, 'referencia') !== false || strpos($lower, 'autorizacion') !== false) {
                $campo = 'referencia';
            } elseif (strpos($lower, 'metodo') !== false || strpos($lower, 'pago') !== false) {
                $campo = 'metodo_pago';
            } elseif (strpos($lower, 'comprobante') !== false) {
                $campo = 'comprobante';
            } elseif (strpos($lower, 'proveedor') !== false || strpos($lower, 'beneficiario') !== false) {
                $campo = 'proveedor';
            } elseif (strpos($lower, 'tipo') !== false) {
                $campo = 'tipo';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            } else {
                $fieldErrors['_global'][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function normalizarMensajeFormularioCaja(string $mensaje): string {
        $lower = function_exists('mb_strtolower') ? mb_strtolower($mensaje, 'UTF-8') : strtolower($mensaje);

        return strtr($lower, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
            'Á' => 'a',
            'É' => 'e',
            'Í' => 'i',
            'Ó' => 'o',
            'Ú' => 'u',
            'Ñ' => 'n',
        ]);
    }
    
    /**
     * Listado de movimientos
     */
    public function movimientosAction() {
        // Obtener filtros
        $filtros = [
            'tipo' => $this->getQuery('tipo'),
            'metodo_pago' => $this->getQuery('metodo_pago'),
            'categoria_id' => $this->getQuery('categoria'),
            'fecha_inicio' => $this->getQuery('fecha_inicio'),
            'fecha_fin' => $this->getQuery('fecha_fin'),
            'buscar' => $this->getQuery('buscar')
        ];
        
        // Si no hay fecha_fin y hay fecha_inicio, usar la misma fecha
        if ($filtros['fecha_inicio'] && !$filtros['fecha_fin']) {
            $filtros['fecha_fin'] = $filtros['fecha_inicio'];
        }
        
        // Obtener corte actual
        $corteActual = $this->cajaModel->obtenerCorteActual();
        if ($corteActual) {
            $filtros['corte_id'] = $corteActual['id'];
        }
        
        // Obtener movimientos
        $movimientos = $this->movimientoModel->obtenerMovimientosDetallados($filtros);
        
        // Calcular totales
        $totales = [
            'ingresos' => 0,
            'gastos' => 0,
            'balance' => 0
        ];
        
        foreach ($movimientos as $mov) {
            if ($mov['tipo'] == 'ingreso') {
                $totales['ingresos'] += $mov['monto'];
            } else {
                $totales['gastos'] += $mov['monto'];
            }
        }
        
        $totales['balance'] = $totales['ingresos'] - $totales['gastos'];
        
        // Obtener categorías para filtros
        $categorias = $this->categoriaModel->where(['activa' => 1]);
        
        View::renderTemplate('caja/movimientos', [
            'title' => 'Movimientos de Caja - ' . current_hotel_display_name(),
            'movimientos' => $movimientos,
            'filtros' => $filtros,
            'totales' => $totales,
            'categorias' => $categorias,
            'metodos_pago' => MovimientoCaja::getMetodosPago(),
            'tipos' => MovimientoCaja::getTipos()
        ]);
    }
    
    /**
     * Vista de corte de caja
     */
    public function corteAction() {
        // Obtener corte actual
        $corteActual = $this->cajaModel->obtenerCorteActual();
        
        if (!$corteActual) {
            set_mensaje('No hay una caja abierta para realizar corte', 'error');
            $this->redirect('caja');
            return;
        }
        
        // Obtener resumen completo
        $resumen = $this->cajaModel->obtenerResumenCaja($corteActual['id']);
        
        // Obtener movimientos del corte
        $movimientos = $this->movimientoModel->obtenerMovimientosDetallados([
            'corte_id' => $corteActual['id']
        ]);
        
        // Obtener movimientos por categoría
        $movimientosPorCategoria = [
            'ingresos' => $this->cajaModel->obtenerMovimientosPorCategoria($corteActual['id'], 'ingreso'),
            'gastos' => $this->cajaModel->obtenerMovimientosPorCategoria($corteActual['id'], 'gasto')
        ];
        
        // Denominaciones para el arqueo
        $denominaciones = [
            1000, 500, 200, 100, 50, 20, 10, 5, 2, 1, 0.50
        ];
        
        View::renderTemplate('caja/corte', [
            'title' => 'Corte de Caja - ' . current_hotel_display_name(),
            'corte' => $corteActual,
            'resumen' => $resumen,
            'movimientos' => $movimientos,
            'movimientos_categoria' => $movimientosPorCategoria,
            'denominaciones' => $denominaciones
        ]);
    }
    
    /**
     * Cerrar corte
     */
    /**
 * Cerrar corte
 */
public function cerrarCorteAction() {
    if (!$this->isPost()) {
        $this->redirect('caja/corte');
        return;
    }
    
    $this->validateCSRF();
    
    $corte_id = intval($this->getPost('corte_id'));
    $efectivo_contado = floatval($this->getPost('efectivo_contado'));
    $observaciones = trim($this->getPost('observaciones'));

    if (!$this->cajaModel->obtenerCortePorId($corte_id)) {
        set_mensaje('Corte no encontrado para el hotel actual', 'error');
        $this->redirect('caja/corte');
        return;
    }
    
    $denominaciones = $this->getPost('denominaciones', []);
    if (is_array($denominaciones)) {
        $efectivo_contado = $this->calcularTotalDenominacionesPost($denominaciones);
    }

    // Guardar denominaciones si se proporcionaron
    if (!empty($denominaciones)) {
        $this->guardarDenominaciones($corte_id, $denominaciones);
    }
    
    // Cerrar corte
    $resultado = $this->cajaModel->cerrarCaja(
        $corte_id, 
        $efectivo_contado, 
        $observaciones, 
        user_id()
    );
    
    if ($resultado['success']) {
        $mensaje = 'Corte de caja realizado exitosamente.';
        
        if ($resultado['diferencia'] != 0) {
            $tipo = $resultado['diferencia'] > 0 ? 'sobrante' : 'faltante';
            $mensaje .= sprintf(' Hay un %s de $%s', 
                $tipo, 
                number_format(abs($resultado['diferencia']), 2)
            );
        }
        
        // === GENERAR PDF, LINK SEGURO Y CORREO CONFIGURADO ===
        try {
            $this->generarYEnviarReporteCorte($corte_id, $efectivo_contado, $observaciones);
            $mensaje .= ' | Reporte preparado con link seguro y correo segun configuracion.';
        } catch (Exception $e) {
            error_log("Error generando/enviando reporte: " . $e->getMessage());
            $mensaje .= ' | No se pudo preparar el envio del reporte.';
        }
        // === FIN GENERAR PDF ===

        $this->registrarNotificacionCorteCerrado($corte_id, $resultado);
        
        set_mensaje($mensaje, 'success');
        
    } else {
        set_mensaje($resultado['message'], 'error');
    }
    
    $this->redirect('caja');
}

private function calcularTotalDenominacionesPost($denominaciones): float {
    if (!is_array($denominaciones)) {
        return 0.0;
    }

    $total = 0.0;
    foreach ($denominaciones as $denominacion => $cantidad) {
        $valor = (float) str_replace(',', '.', (string) $denominacion);
        $cantidad = max(0, (int) $cantidad);

        if ($valor <= 0 || $cantidad <= 0) {
            continue;
        }

        $total += $valor * $cantidad;
    }

    return round($total, 2);
}

private function registrarNotificacionCorteCerrado($corte_id, array $resultado) {
    $diferencia = (float)($resultado['diferencia'] ?? 0);
    $severidad = abs($diferencia) > 0 ? 'alta' : 'info';
    $mensaje = 'El corte de caja fue cerrado correctamente.';

    if ($diferencia != 0.0) {
        $tipo = $diferencia > 0 ? 'sobrante' : 'faltante';
        $mensaje .= ' Hay un ' . $tipo . ' de $' . number_format(abs($diferencia), 2) . '.';
    }

    NotificacionService::crear([
        'hotel_id' => obtenerHotelIdActualCompat(),
        'modulo' => 'caja',
        'tipo' => 'corte_cerrado',
        'severidad' => $severidad,
        'titulo' => 'Corte de caja #' . (int)$corte_id . ' cerrado',
        'mensaje' => $mensaje,
        'entidad_tipo' => 'corte_caja',
        'entidad_id' => (int)$corte_id,
        'url' => 'caja/corte/' . (int)$corte_id,
        'dedupe_key' => 'caja.corte_cerrado.' . (int)$corte_id,
        'creada_por' => function_exists('user_id') ? user_id() : null,
    ]);
}

/**
 * Generar PDF del corte y enviarlo por correo con link seguro
 */
private function generarYEnviarReporteCorte($corte_id, $efectivo_contado, $observaciones) {
    // Obtener datos completos del corte
    $db = Database::getInstance();
    $hotel_id = obtenerHotelIdActualCompat();

    $sql = "SELECT cc.*, 
            c.nombre as caja_nombre,
            ua.nombre_completo as usuario_apertura,
            uc.nombre_completo as usuario_cierre
            FROM cortes_caja cc
            INNER JOIN cajas c ON cc.caja_id = c.id AND c.hotel_id = cc.hotel_id
            LEFT JOIN usuarios ua ON cc.usuario_apertura_id = ua.id
            LEFT JOIN usuarios uc ON cc.usuario_cierre_id = uc.id
            WHERE cc.id = ?
            AND cc.hotel_id = ?";
    
    $stmt = $db->query($sql, [$corte_id, $hotel_id]);
    $corte = $stmt->fetch();
    
    if (!$corte) {
        throw new Exception("Corte no encontrado: $corte_id");
    }
    
    // Obtener resumen
    $resumen = $this->cajaModel->obtenerResumenCaja($corte_id);
    
    // Obtener movimientos detallados
    $movimientos = $this->movimientoModel->obtenerMovimientosDetallados([
        'corte_id' => $corte_id
    ]);
    
    // Obtener ingresos por tipo de habitación (Sencilla Manolo / Doble Manolo)
    $ingresosPorTipo = $this->cajaModel->obtenerIngresosPorTipoHabitacion($corte_id);
    
    // Generar PDF
    require_once ROOT_PATH . '/includes/ReporteCortePDF.php';
    $pdfGenerator = new ReporteCortePDF();
    $rutaPDF = $pdfGenerator->generar(
        $corte, 
        $resumen, 
        $movimientos, 
        $ingresosPorTipo, 
        $efectivo_contado, 
        $observaciones
    );
    
    error_log("PDF generado en: $rutaPDF");
    
    $nombrePDF = function_exists('hotel_export_filename')
        ? hotel_export_filename('Corte_' . date('d-m-Y', strtotime($corte['fecha_apertura'])) . '_' . (int)$corte_id, 'pdf', false)
        : 'Corte_' . date('d-m-Y', strtotime($corte['fecha_apertura'])) . '_' . (int)$corte_id . '.pdf';
    $entrega = new ReporteEntregaService();
    $registro = $entrega->registrarArchivoExistenteYEnviar($rutaPDF, [
        'hotel_id' => $hotel_id,
        'tipo_reporte' => 'corte-caja',
        'titulo' => 'Corte de caja #' . (int)$corte_id,
        'descripcion' => 'Reporte PDF generado al cerrar caja.',
        'archivo_nombre' => $nombrePDF,
        'parametros' => [
            'corte_id' => (int)$corte_id,
            'caja_id' => (int)($corte['caja_id'] ?? 0),
            'fecha_apertura' => $corte['fecha_apertura'] ?? null,
            'fecha_cierre' => $corte['fecha_cierre'] ?? null,
        ],
    ]);

    if (!$registro) {
        error_log('No se pudo registrar link seguro para corte de caja #' . (int)$corte_id);
    }
}
    
    /**
     * Guardar denominaciones del arqueo
     */
    private function guardarDenominaciones($corte_id, $denominaciones) {
        $db = Database::getInstance();
        
        foreach ($denominaciones as $denominacion => $cantidad) {
            if ($cantidad > 0) {
                $sql = "INSERT INTO denominaciones_efectivo 
                        (corte_id, denominacion, cantidad) 
                        VALUES (?, ?, ?)";
                
                $db->query($sql, [$corte_id, $denominacion, $cantidad]);
            }
        }
    }
    
    /**
     * Editar movimiento (AJAX)
     */
    public function editarMovimientoAction() {
        $this->requireAjax();
        
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $id = intval($this->getPost('id'));
        $data = [
            'descripcion' => trim($this->getPost('descripcion')),
            'monto' => floatval($this->getPost('monto')),
            'metodo_pago' => $this->getPost('metodo_pago'),
            'referencia' => trim($this->getPost('referencia'))
        ];
        $motivo = trim($this->getPost('motivo_edicion'));
        
        if (empty($motivo)) {
            $this->jsonResponse(['success' => false, 'message' => 'Debe indicar el motivo de la edición']);
            return;
        }
        
        $resultado = $this->movimientoModel->editarMovimiento($id, $data, $motivo, user_id());
        
        $this->jsonResponse($resultado);
    }
    
    /**
     * Obtener movimiento (AJAX)
     */
    public function obtenerMovimientoAction() {
        $this->requireAjax();
        
        $id = intval($this->getQuery('id'));
        $movimiento = $this->movimientoModel->obtenerPorId($id);
        
        if ($movimiento) {
            $this->jsonResponse(['success' => true, 'data' => $movimiento]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Movimiento no encontrado']);
        }
    }
    
    /**
     * Exportar movimientos
     */
    public function exportarAction() {
        $formato = $this->getQuery('formato', 'excel');
        $corte_id = intval($this->getQuery('corte_id'));
        $hotel_id = obtenerHotelIdActualCompat();
        
        if (!$corte_id) {
            set_mensaje('Corte no especificado', 'error');
            $this->redirect('caja/movimientos');
            return;
        }

        $corte = $this->cajaModel->obtenerCortePorId($corte_id, $hotel_id);
        if (!$corte) {
            set_mensaje('Corte no encontrado para el hotel actual', 'error');
            $this->redirect('caja/movimientos');
            return;
        }
        
        // Obtener datos
        $movimientos = $this->movimientoModel->obtenerParaExportar($corte_id);
        
        if ($formato == 'excel') {
            $this->exportarExcel($movimientos, $corte_id);
        } else {
            $this->exportarPDF($movimientos, $corte_id);
        }
    }

    /**
     * Exporta el detalle de movimientos del corte en formato compatible con Excel.
     */
    private function exportarExcel($movimientos, $corte_id) {
        $movimientos = is_array($movimientos) ? $movimientos : [];
        $columnas = !empty($movimientos)
            ? array_keys(reset($movimientos))
            : [
                'Fecha y Hora',
                'Tipo',
                'Categoria',
                'Descripcion',
                'Monto',
                'Metodo de Pago',
                'Referencia',
                'Comprobante',
                'Proveedor',
                'Registrado por',
                'Reservacion'
            ];

        $nombreArchivo = function_exists('hotel_export_filename')
            ? hotel_export_filename('Movimientos_Corte_' . (int)$corte_id, 'xls', false)
            : 'Movimientos_Corte_' . (int)$corte_id . '.xls';

        $bufferLevel = ob_get_level();
        while ($bufferLevel-- > 0) {
            @ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0, no-cache, must-revalidate');
        header('Pragma: public');

        echo "\xEF\xBB\xBF";
        echo '<!doctype html><html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:10pt;">';
        echo '<tr>';
        foreach ($columnas as $columna) {
            echo '<th style="background:#1f2937;color:#ffffff;font-weight:bold;text-align:left;">'
                . $this->excelCell($columna)
                . '</th>';
        }
        echo '</tr>';

        if (empty($movimientos)) {
            echo '<tr><td colspan="' . max(1, count($columnas)) . '" style="color:#64748b;">Sin movimientos para exportar</td></tr>';
        } else {
            foreach ($movimientos as $movimiento) {
                echo '<tr>';
                foreach ($columnas as $columna) {
                    $valor = $movimiento[$columna] ?? '';
                    $align = $columna === 'Monto' ? 'right' : 'left';
                    echo '<td style="text-align:' . $align . ';">'
                        . $this->excelCell($this->formatExcelValue($columna, $valor))
                        . '</td>';
                }
                echo '</tr>';
            }
        }

        echo '</table></body></html>';
        exit;
    }

    /**
     * Exporta el detalle de movimientos a PDF basico si se solicita explicitamente.
     */
    private function exportarPDF($movimientos, $corte_id) {
        require_once APP_PATH . '/libs/TCPDF/tcpdf.php';

        $movimientos = is_array($movimientos) ? $movimientos : [];
        $pdf = new TCPDF('L', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator('Medisoft Hoteles');
        $pdf->SetAuthor(current_hotel_display_name());
        $pdf->SetTitle('Movimientos Corte #' . (int)$corte_id);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 8, 'Detalle de movimientos - Corte #' . (int)$corte_id, 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 8);

        $html = '<table border="1" cellpadding="4" cellspacing="0">'
            . '<thead><tr style="background-color:#1f2937;color:#ffffff;">'
            . '<th width="14%">Fecha y hora</th>'
            . '<th width="9%">Tipo</th>'
            . '<th width="13%">Categoria</th>'
            . '<th width="25%">Descripcion</th>'
            . '<th width="10%">Monto</th>'
            . '<th width="12%">Metodo</th>'
            . '<th width="17%">Referencia</th>'
            . '</tr></thead><tbody>';

        if (empty($movimientos)) {
            $html .= '<tr><td colspan="7">Sin movimientos para exportar</td></tr>';
        } else {
            foreach ($movimientos as $movimiento) {
                $html .= '<tr>'
                    . '<td>' . $this->excelCell($this->exportRowValue($movimiento, ['Fecha y Hora'])) . '</td>'
                    . '<td>' . $this->excelCell($this->exportRowValue($movimiento, ['Tipo'])) . '</td>'
                    . '<td>' . $this->excelCell($this->exportRowValue($movimiento, ['Categoria', 'Categoría', 'CategorÃ­a'])) . '</td>'
                    . '<td>' . $this->excelCell($this->exportRowValue($movimiento, ['Descripcion', 'Descripción', 'DescripciÃ³n'])) . '</td>'
                    . '<td align="right">' . $this->excelCell($this->formatExcelValue('Monto', $this->exportRowValue($movimiento, ['Monto']))) . '</td>'
                    . '<td>' . $this->excelCell($this->exportRowValue($movimiento, ['Metodo de Pago', 'Método de Pago', 'MÃ©todo de Pago'])) . '</td>'
                    . '<td>' . $this->excelCell($this->exportRowValue($movimiento, ['Referencia'])) . '</td>'
                    . '</tr>';
            }
        }

        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $nombreArchivo = function_exists('hotel_export_filename')
            ? hotel_export_filename('Movimientos_Corte_' . (int)$corte_id, 'pdf', false)
            : 'Movimientos_Corte_' . (int)$corte_id . '.pdf';

        $bufferLevel = ob_get_level();
        while ($bufferLevel-- > 0) {
            @ob_end_clean();
        }

        $pdf->Output($nombreArchivo, 'D');
        exit;
    }

    private function formatExcelValue($columna, $valor) {
        if ($columna === 'Monto' && is_numeric($valor)) {
            return number_format((float)$valor, 2, '.', '');
        }

        $texto = (string)($valor ?? '');
        if ($texto !== '' && preg_match('/^[=+\-@]/', $texto)) {
            return "'" . $texto;
        }

        return $texto;
    }

    private function excelCell($valor) {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function exportRowValue(array $row, array $aliases) {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row)) {
                return $row[$alias];
            }
        }

        return '';
    }
    
    /**
     * Historial de cortes
     */
    public function historialAction() {
        $mes = intval($this->getQuery('mes', date('m')));
        $año = intval($this->getQuery('año', date('Y')));
        
        // Obtener historial
        $cortes = $this->cajaModel->obtenerHistorialCortes(null, 100);
        
        // Filtrar por mes y año
        $cortesFiltrados = array_filter($cortes, function($corte) use ($mes, $año) {
            $fecha = new DateTime($corte['fecha_apertura']);
            return $fecha->format('m') == $mes && $fecha->format('Y') == $año;
        });
        
        // Estadísticas del mes
        $estadisticas = $this->cajaModel->obtenerEstadisticasMes($mes, $año);
        
        View::renderTemplate('caja/historial', [
            'title' => 'Historial de Cortes - ' . current_hotel_display_name(),
            'cortes' => $cortesFiltrados,
            'estadisticas' => $estadisticas,
            'mes' => $mes,
            'año' => $año
        ]);
    }
    
    /**
     * Ver detalle de un corte
     */
    public function verCorteAction() {
        $id = $this->route_params['id'] ?? 0;
        
        $db = Database::getInstance();
        $hotel_id = obtenerHotelIdActualCompat();
        $sql = "SELECT cc.*, 
                c.nombre as caja_nombre,
                ua.nombre_completo as usuario_apertura,
                uc.nombre_completo as usuario_cierre
                FROM cortes_caja cc
                INNER JOIN cajas c ON cc.caja_id = c.id AND c.hotel_id = cc.hotel_id
                LEFT JOIN usuarios ua ON cc.usuario_apertura_id = ua.id
                LEFT JOIN usuarios uc ON cc.usuario_cierre_id = uc.id
                WHERE cc.id = ?
                AND cc.hotel_id = ?";
        
        $stmt = $db->query($sql, [$id, $hotel_id]);
        $corte = $stmt->fetch();
        
        if (!$corte) {
            set_mensaje('Corte no encontrado', 'error');
            $this->redirect('caja/historial');
            return;
        }
        
        // Obtener movimientos
        $movimientos = $this->movimientoModel->obtenerMovimientosDetallados([
            'corte_id' => $id
        ]);
        
        // Obtener denominaciones si existen
        $sql = "SELECT * FROM denominaciones_efectivo WHERE corte_id = ? ORDER BY denominacion DESC";
        $stmt = $db->query($sql, [$id]);
        $denominaciones = $stmt->fetchAll();
        
        View::renderTemplate('caja/ver_corte', [
            'title' => 'Detalle de Corte #' . $id . ' - ' . current_hotel_display_name(),
            'corte' => $corte,
            'movimientos' => $movimientos,
            'denominaciones' => $denominaciones
        ]);
    }
    
    /**
     * Configuración de categorías
     */
    public function categoriasAction() {
        $this->requireRole('gerente');
        
        $categorias = $this->categoriaModel->orderBy('tipo', 'ASC');
        $estadisticas = $this->categoriaModel->obtenerEstadisticasUso();
        
        View::renderTemplate('caja/categorias', [
            'title' => 'Categorías de Movimientos - ' . current_hotel_display_name(),
            'categorias' => $categorias,
            'estadisticas' => $estadisticas,
            'iconos' => CategoriaMovimiento::getIconosDisponibles(),
            'colores' => CategoriaMovimiento::getColoresDisponibles()
        ]);
    }
    
    /**
     * Respuesta JSON
     */
    private function jsonResponse($data, $code = 200) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Crear nueva categoría (AJAX)
     */
    public function crearCategoriaAction() {
        $this->requireAjax();
        $this->requireRole('gerente');
        
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $this->validateCSRF();
        
        $data = [
            'nombre' => trim($this->getPost('nombre')),
            'tipo' => $this->getPost('tipo'),
            'descripcion' => trim($this->getPost('descripcion')),
            'icono' => $this->getPost('icono', 'fas fa-tag'),
            'color' => $this->getPost('color', '#6B7280')
        ];
        
        $resultado = $this->categoriaModel->crearCategoria($data);
        $this->jsonResponse($resultado);
    }
    
    /**
     * Actualizar categoría (AJAX)
     */
    public function actualizarCategoriaAction() {
        $this->requireAjax();
        $this->requireRole('gerente');
        
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $this->validateCSRF();
        
        $id = intval($this->getPost('id'));
        $data = [
            'nombre' => trim($this->getPost('nombre')),
            'tipo' => $this->getPost('tipo'),
            'descripcion' => trim($this->getPost('descripcion')),
            'icono' => $this->getPost('icono'),
            'color' => $this->getPost('color')
        ];
        
        $categoria = $this->categoriaModel->update($id, $data);
        
        if ($categoria) {
            $this->jsonResponse(['success' => true, 'message' => 'Categoría actualizada']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar']);
        }
    }
    
    /**
     * Toggle activar/desactivar categoría (AJAX)
     */
    public function toggleCategoriaAction() {
        $this->requireAjax();
        $this->requireRole('gerente');
        
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $this->validateCSRF();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        $resultado = $this->categoriaModel->desactivar($id);
        $this->jsonResponse($resultado);
    }
    
    /**
     * Actualizar orden de categorías (AJAX)
     */
    public function ordenCategoriaAction() {
        $this->requireAjax();
        $this->requireRole('gerente');
        
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $this->validateCSRF();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $ordenamiento = $data['orden'] ?? [];
        
        $resultado = $this->categoriaModel->actualizarOrden($ordenamiento);
        $this->jsonResponse($resultado);
    }
    
    /**
 * Descargar PDF del corte de caja
 */
public function descargarPDFAction() {
$corte_id = intval($this->route_params['id'] ?? 0);   
    $hotel_id = obtenerHotelIdActualCompat();

    if (!$corte_id) {
        set_mensaje('Corte no encontrado', 'error');
        $this->redirect('caja/historial');
        return;
    }
    
    // Obtener datos del corte
    $db = Database::getInstance();
    $sql = "SELECT cc.*, 
            c.nombre as caja_nombre,
            ua.nombre_completo as usuario_apertura,
            uc.nombre_completo as usuario_cierre
            FROM cortes_caja cc
            INNER JOIN cajas c ON cc.caja_id = c.id AND c.hotel_id = cc.hotel_id
            LEFT JOIN usuarios ua ON cc.usuario_apertura_id = ua.id
            LEFT JOIN usuarios uc ON cc.usuario_cierre_id = uc.id
            WHERE cc.id = ?
            AND cc.hotel_id = ?";
    
    $stmt = $db->query($sql, [$corte_id, $hotel_id]);
    $corte = $stmt->fetch();
    
    if (!$corte) {
        set_mensaje('Corte no encontrado', 'error');
        $this->redirect('caja/historial');
        return;
    }
    
    $resumen = $this->cajaModel->obtenerResumenCaja($corte_id);
    $movimientos = $this->movimientoModel->obtenerMovimientosDetallados(['corte_id' => $corte_id]);
    $ingresosPorTipo = $this->cajaModel->obtenerIngresosPorTipoHabitacion($corte_id);
    
    $efectivo_contado = $corte['efectivo_contado'] ?? 0;
    $observaciones = $corte['observaciones'] ?? '';
    
    require_once ROOT_PATH . '/includes/ReporteCortePDF.php';
    $pdfGenerator = new ReporteCortePDF();
    $rutaPDF = $pdfGenerator->generar(
        $corte, $resumen, $movimientos, $ingresosPorTipo, 
        $efectivo_contado, $observaciones
    );
    
    // Forzar descarga
    $nombreDescarga = function_exists('hotel_export_filename')
        ? hotel_export_filename('Corte_' . date('d-m-Y', strtotime($corte['fecha_apertura'])) . '_' . (int)$corte_id, 'pdf', false)
        : 'Corte_' . date('d-m-Y', strtotime($corte['fecha_apertura'])) . '_' . (int)$corte_id . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
    header('Content-Length: ' . filesize($rutaPDF));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    readfile($rutaPDF);
    exit;
}
    
    /**
     * Requerir petición AJAX
     */
    private function requireAjax() {
        if (!$this->isAjax()) {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso no permitido'], 403);
            exit;
        }
    }
}
