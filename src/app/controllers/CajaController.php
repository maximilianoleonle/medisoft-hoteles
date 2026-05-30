<?php
/**
 * Controlador de Caja
 * Los Cedros
 */

class CajaController extends Controller {
    private $cajaModel;
    private $movimientoModel;
    private $categoriaModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->cajaModel = new Caja();
        $this->movimientoModel = new MovimientoCaja();
        $this->categoriaModel = new CategoriaMovimiento();
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
            'title' => 'Abrir Caja - Los Cedros',
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
        'title' => 'Caja - Los Cedros',
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
        'title' => 'Reporte por Métodos de Pago - Los Cedros',
        'reporte' => $reporte,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'metodos_pago' => MovimientoCaja::getMetodosPago()
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
        
        // Validar
        if ($monto_inicial < 0) {
            set_mensaje('El monto inicial no puede ser negativo', 'error');
            $this->redirect('caja');
            return;
        }
        
        // Abrir caja
        $resultado = $this->cajaModel->abrirCaja($caja_id, $monto_inicial, user_id());
        
        if ($resultado['success']) {
            set_mensaje('Caja abierta exitosamente', 'success');
        } else {
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
        
        // Validar
        $errores = $this->movimientoModel->validarMovimiento($data);
        
        if (!empty($errores)) {
            set_mensaje(implode('<br>', $errores), 'error');
            save_old_input($data);
            $this->redirect('caja');
            return;
        }
        
        // Registrar movimiento
        $resultado = $this->movimientoModel->registrarMovimiento($data);
        
        if ($resultado['success']) {
            clear_old_input();
            set_mensaje('Ingreso registrado exitosamente', 'success');
        } else {
            save_old_input($data);
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
        
        // Validar
        $errores = $this->movimientoModel->validarMovimiento($data);
        
        if (!empty($errores)) {
            set_mensaje(implode('<br>', $errores), 'error');
            save_old_input($data);
            $this->redirect('caja');
            return;
        }
        
        // Registrar movimiento
        $resultado = $this->movimientoModel->registrarMovimiento($data);
        
        if ($resultado['success']) {
            clear_old_input();
            set_mensaje('Gasto registrado exitosamente', 'success');
        } else {
            save_old_input($data);
            set_mensaje($resultado['message'], 'error');
        }
        
        $this->redirect('caja');
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
            'title' => 'Movimientos de Caja - Los Cedros',
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
            'title' => 'Corte de Caja - Los Cedros',
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
    
    // Guardar denominaciones si se proporcionaron
    $denominaciones = $this->getPost('denominaciones', []);
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
        
        // === GENERAR PDF Y ENVIAR POR WHATSAPP ===
        try {
            $this->generarYEnviarReporteCorte($corte_id, $efectivo_contado, $observaciones);
            $mensaje .= ' | Reporte enviado por WhatsApp.';
        } catch (Exception $e) {
            error_log("Error generando/enviando reporte: " . $e->getMessage());
            $mensaje .= ' | No se pudo enviar el reporte por WhatsApp.';
        }
        // === FIN GENERAR PDF ===
        
        set_mensaje($mensaje, 'success');
        
    } else {
        set_mensaje($resultado['message'], 'error');
    }
    
    $this->redirect('caja');
}

/**
 * Generar PDF del corte y enviarlo por WhatsApp
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
    
    // Enviar por WhatsApp
    require_once ROOT_PATH . '/includes/WhatsAppService.php';
    $whatsapp = new WhatsAppService();
    
    if ($whatsapp->estaConfigurado()) {
        $balance = ($resumen['ingresos']['total'] ?? 0) - ($resumen['gastos']['total'] ?? 0);
        
        $caption = $whatsapp->prepararMensajeCorte([
            'fecha' => date('d/m/Y', strtotime($corte['fecha_apertura'])),
            'hora' => date('H:i'),
            'balance' => $balance
        ]);
        
        $nombrePDF = 'Corte_' . date('d-m-Y', strtotime($corte['fecha_apertura'])) . '.pdf';
        
        $resultadoWA = $whatsapp->enviarPDF($rutaPDF, $nombrePDF, $caption);
        
        if ($resultadoWA['success']) {
            error_log("WhatsApp: PDF enviado exitosamente");
        } else {
            error_log("WhatsApp: Error - " . $resultadoWA['message']);
        }
    } else {
        error_log("WhatsApp: Servicio no configurado, PDF solo fue guardado localmente");
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
            'title' => 'Historial de Cortes - Los Cedros',
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
            'title' => 'Detalle de Corte #' . $id . ' - Los Cedros',
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
            'title' => 'Categorías de Movimientos - Los Cedros',
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
    $nombreDescarga = 'Corte_' . date('d-m-Y', strtotime($corte['fecha_apertura'])) . '_#' . $corte_id . '.pdf';
    
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
