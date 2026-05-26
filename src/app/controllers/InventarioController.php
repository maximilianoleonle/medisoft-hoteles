<?php
/**
 * Controlador de Inventario
 * Los Cedros
 */
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/MovimientoInventario.php';

class InventarioController extends Controller {
    
    private $inventarioModel;
    private $movimientoModel;
    private $db;
    
    /**
     * Constructor
     */
    public function __construct($route_params = []) {
    parent::__construct($route_params);
    
    // NO uses getConnection(), usa getInstance() directamente
    $this->db = Database::getInstance();
    
    // Inicializar modelos
    $this->inventarioModel = new Inventario();
    $this->movimientoModel = new MovimientoInventario();
}
   
   
public function debugPdfAction() {
    echo "<h1>Debug Sistema de Inventario - PDF</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #333; color: white; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>";
    
    $db = Database::getInstance();
    
    // 1. Verificar conexión a BD
    echo "<div class='debug-section'>";
    echo "<h2>1. Conexión a Base de Datos</h2>";
    try {
        $test = $db->query("SELECT 1");
        echo "<p class='success'>✓ Conexión exitosa</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error de conexión: " . $e->getMessage() . "</p>";
        return;
    }
    echo "</div>";
    
    // 2. Verificar tablas necesarias
    echo "<div class='debug-section'>";
    echo "<h2>2. Verificación de Tablas</h2>";
    $tablas = [
        'movimientos_inventario',
        'inventario_productos',
        'inventario_categorias',
        'habitaciones',
        'usuarios'
    ];
    
    foreach ($tablas as $tabla) {
        try {
            $sql = "SELECT COUNT(*) as total FROM $tabla";
            $stmt = $db->query($sql);
            $result = $stmt->fetch();
            echo "<p class='success'>✓ Tabla '$tabla': {$result['total']} registros</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error en tabla '$tabla': " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    // 3. Analizar estructura de movimientos_inventario
    echo "<div class='debug-section'>";
    echo "<h2>3. Estructura de movimientos_inventario</h2>";
    try {
        $sql = "DESCRIBE movimientos_inventario";
        $stmt = $db->query($sql);
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 4. Analizar fechas en movimientos
    echo "<div class='debug-section'>";
    echo "<h2>4. Análisis de Fechas en Movimientos</h2>";
    try {
        // Estadísticas de fechas
        $sql = "SELECT 
                MIN(created_at) as fecha_min,
                MAX(created_at) as fecha_max,
                COUNT(*) as total,
                COUNT(DISTINCT DATE(created_at)) as dias_distintos,
                COUNT(CASE WHEN created_at IS NULL THEN 1 END) as fechas_nulas
                FROM movimientos_inventario";
        $stmt = $db->query($sql);
        $stats = $stmt->fetch();
        
        echo "<p><strong>Fecha mínima:</strong> " . ($stats['fecha_min'] ?? 'N/A') . "</p>";
        echo "<p><strong>Fecha máxima:</strong> " . ($stats['fecha_max'] ?? 'N/A') . "</p>";
        echo "<p><strong>Total movimientos:</strong> " . $stats['total'] . "</p>";
        echo "<p><strong>Días distintos:</strong> " . $stats['dias_distintos'] . "</p>";
        echo "<p><strong>Fechas nulas:</strong> " . $stats['fechas_nulas'] . "</p>";
        
        // Movimientos por día
        $sql = "SELECT DATE(created_at) as fecha, COUNT(*) as total 
                FROM movimientos_inventario 
                GROUP BY DATE(created_at) 
                ORDER BY fecha DESC 
                LIMIT 10";
        $stmt = $db->query($sql);
        $por_dia = $stmt->fetchAll();
        
        echo "<h3>Movimientos por día (últimos 10 días con movimientos):</h3>";
        echo "<table>";
        echo "<tr><th>Fecha</th><th>Total Movimientos</th></tr>";
        foreach ($por_dia as $dia) {
            echo "<tr><td>" . ($dia['fecha'] ?? 'NULL') . "</td><td>{$dia['total']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 5. Probar consulta del PDF
    echo "<div class='debug-section'>";
    echo "<h2>5. Prueba de Consulta del PDF</h2>";
    
    // Usar fechas que sabemos que tienen datos
    $fecha_desde = '2025-09-01';
    $fecha_hasta = '2025-09-30';
    
    echo "<p><strong>Probando con fechas:</strong> $fecha_desde a $fecha_hasta</p>";
    
    try {
        $sql = "SELECT m.*, 
                       p.nombre as producto_nombre, 
                       p.codigo as producto_codigo,
                       COALESCE(u.nombre, 'Sistema') as usuario_nombre,
                       h.numero as habitacion_numero
                FROM movimientos_inventario m
                LEFT JOIN inventario_productos p ON m.producto_id = p.id
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE DATE(m.created_at) BETWEEN ? AND ?
                ORDER BY m.created_at DESC
                LIMIT 5";
        
        $stmt = $db->query($sql, [$fecha_desde, $fecha_hasta]);
        $movimientos = $stmt->fetchAll();
        
        echo "<p class='success'>✓ Consulta ejecutada exitosamente</p>";
        echo "<p><strong>Movimientos encontrados:</strong> " . count($movimientos) . "</p>";
        
        if (!empty($movimientos)) {
            echo "<h3>Primeros 5 movimientos:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th></tr>";
            foreach ($movimientos as $mov) {
                echo "<tr>";
                echo "<td>{$mov['id']}</td>";
                echo "<td>{$mov['created_at']}</td>";
                echo "<td>" . ($mov['producto_nombre'] ?? 'N/A') . "</td>";
                echo "<td>{$mov['tipo_movimiento']}</td>";
                echo "<td>{$mov['cantidad']}</td>";
                echo "<td>" . substr($mov['motivo'] ?? '', 0, 50) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Mostrar SQL completo para debug
        echo "<h3>SQL Ejecutado:</h3>";
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
        echo "<p>Parámetros: [$fecha_desde, $fecha_hasta]</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error en consulta: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    echo "</div>";
    
    // 6. Verificar productos
    echo "<div class='debug-section'>";
    echo "<h2>6. Verificación de Productos</h2>";
    try {
        $productos = $this->inventarioModel->getAllWithCategory();
        echo "<p class='success'>✓ Productos cargados: " . count($productos) . "</p>";
        
        if (empty($productos)) {
            echo "<p class='warning'>⚠ No hay productos en el sistema</p>";
        } else {
            echo "<table>";
            echo "<tr><th>ID</th><th>Código</th><th>Nombre</th><th>Stock</th><th>Categoría</th></tr>";
            $count = 0;
            foreach ($productos as $prod) {
                if ($count++ >= 5) break;
                echo "<tr>";
                echo "<td>{$prod['id']}</td>";
                echo "<td>{$prod['codigo']}</td>";
                echo "<td>{$prod['nombre']}</td>";
                echo "<td>{$prod['stock_actual']}</td>";
                echo "<td>" . ($prod['categoria_nombre'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p>... mostrando solo los primeros 5 productos</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 7. Simular generación de PDF
    echo "<div class='debug-section'>";
    echo "<h2>7. Simulación de Generación de PDF</h2>";
    echo "<p>Para generar un PDF de prueba con las fechas correctas:</p>";
    echo "<form method='POST' action='" . url('inventario/generarPdfMovimientos') . "'>";
    echo csrf_field();
    echo "<input type='hidden' name='fecha_desde' value='2025-09-01'>";
    echo "<input type='hidden' name='fecha_hasta' value='2025-09-30'>";
    echo "<button type='submit' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "Generar PDF de Prueba (Septiembre 2025)";
    echo "</button>";
    echo "</form>";
    echo "</div>";
    
    // 8. Logs del sistema
    echo "<div class='debug-section'>";
    echo "<h2>8. Información del Sistema</h2>";
    echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
    echo "<p><strong>Fecha/Hora del servidor:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>";
    echo "<p><strong>Usuario actual:</strong> " . ($_SESSION['usuario_nombre'] ?? 'No identificado') . "</p>";
    echo "</div>";
    
    exit();
}

// También agrega este método para generar logs en archivo
public function logInventarioAction() {
    $logFile = APP_PATH . '/logs/inventario_debug_' . date('Y-m-d_H-i-s') . '.log';
    $log = "=== LOG DE DEBUG INVENTARIO ===\n";
    $log .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $log .= "Usuario: " . ($_SESSION['usuario_nombre'] ?? 'Sistema') . "\n\n";
    
    $db = Database::getInstance();
    
    // Log de movimientos
    $sql = "SELECT * FROM movimientos_inventario ORDER BY created_at DESC LIMIT 20";
    $stmt = $db->query($sql);
    $movimientos = $stmt->fetchAll();
    
    $log .= "ÚLTIMOS 20 MOVIMIENTOS:\n";
    $log .= str_repeat("-", 80) . "\n";
    foreach ($movimientos as $mov) {
        $log .= sprintf("ID: %d | Fecha: %s | Tipo: %s | Producto: %d | Cantidad: %d\n",
            $mov['id'],
            $mov['created_at'],
            $mov['tipo_movimiento'],
            $mov['producto_id'],
            $mov['cantidad']
        );
    }
    
    // Guardar log
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    file_put_contents($logFile, $log);
    
    echo "Log guardado en: " . $logFile;
    exit();
}
    
    /**
 * Vista de exportación
 */
public function exportarAction() {
    View::renderTemplate('inventario/exportar', [
        'title' => 'Exportar Inventario - Los Cedros'
    ]);
}

/**
 * Generar PDF de movimientos
 */
public function generarPdfMovimientosAction() {
    if (!$this->isPost()) {
        $this->redirect('inventario');
        return;
    }
    
    $this->validateCSRF();
    
    try {
        $fecha_desde = $this->getPost('fecha_desde');
        $fecha_hasta = $this->getPost('fecha_hasta');
        
        // Validar fechas
        if (!$fecha_desde || !$fecha_hasta) {
            throw new Exception('Debe seleccionar ambas fechas');
        }
        
        // Debug: Ver qué fechas se están usando
        error_log("=== DEBUG FECHAS PDF ===");
        error_log("Fecha desde: " . $fecha_desde);
        error_log("Fecha hasta: " . $fecha_hasta);
        
        // Primero, obtener el rango de fechas que tiene movimientos
        $db = Database::getInstance();
        $sql_rango = "SELECT MIN(DATE(created_at)) as fecha_min, 
                             MAX(DATE(created_at)) as fecha_max,
                             COUNT(*) as total
                      FROM movimientos_inventario";
        $stmt_rango = $db->query($sql_rango);
        $rango = $stmt_rango->fetch();
        
        error_log("Rango de fechas en BD: " . $rango['fecha_min'] . " a " . $rango['fecha_max']);
        error_log("Total de movimientos en BD: " . $rango['total']);
        
        // Consulta de movimientos
        $sql = "SELECT m.*, 
               p.nombre as producto_nombre, 
               p.codigo as producto_codigo,
               h.numero as habitacion_numero
        FROM movimientos_inventario m
        LEFT JOIN inventario_productos p ON m.producto_id = p.id
        LEFT JOIN habitaciones h ON m.habitacion_id = h.id
        WHERE DATE(m.created_at) BETWEEN ? AND ?
        ORDER BY m.created_at DESC";
        
        $stmt = $db->query($sql, [$fecha_desde, $fecha_hasta]);
        $movimientos = $stmt ? $stmt->fetchAll() : [];
        
        error_log("Movimientos encontrados: " . count($movimientos));
        
        // Si no hay movimientos, agregar mensaje informativo
        if (empty($movimientos) && $rango['total'] > 0) {
            // Hay movimientos pero no en el rango seleccionado
            set_mensaje("No se encontraron movimientos entre las fechas seleccionadas. " .
                       "Los movimientos en el sistema están entre " . 
                       format_date($rango['fecha_min']) . " y " . 
                       format_date($rango['fecha_max']), 'warning');
        }
        
        // Obtener stock actual
        $productos = $this->inventarioModel->getAllWithCategory();
        
        // Generar PDF
        $this->generarPdfReporte($movimientos, $productos, $fecha_desde, $fecha_hasta);
        
    } catch (Exception $e) {
        set_mensaje('Error: ' . $e->getMessage(), 'error');
        $this->redirect('inventario/exportar');
    }
}

/**
 * Generar el PDF del reporte
 */
private function generarPdfReporte($movimientos, $productos, $fecha_desde, $fecha_hasta) {
    require_once APP_PATH . '/libs/TCPDF/tcpdf.php';
    
    // Debug para ver si hay movimientos
    error_log("=== DEBUG PDF ===");
    error_log("Total movimientos recibidos: " . count($movimientos));
    error_log("Fecha desde: " . $fecha_desde);
    error_log("Fecha hasta: " . $fecha_hasta);
    
    if (!empty($movimientos)) {
        error_log("Primer movimiento: " . print_r($movimientos[0], true));
    }
    
    // Crear nuevo PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    
    // Configuración
    $pdf->SetCreator('Los Cedros');
    $pdf->SetAuthor('Sistema de Inventario');
    $pdf->SetTitle('Reporte de Inventario');
    
    // Remover header y footer por defecto
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Márgenes
    $pdf->SetMargins(15, 15, 15);
    
    // Agregar página
    $pdf->AddPage();
    
    // Header del reporte con mejor diseño
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(107, 68, 35); // Color marrón del hotel
    $pdf->Cell(0, 12, 'Los Cedros', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(0, 8, 'Reporte de Inventario', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, 'Del ' . format_date($fecha_desde) . ' al ' . format_date($fecha_hasta), 0, 1, 'C');
    
    // Línea decorativa
    $pdf->SetDrawColor(107, 68, 35);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY() + 2, 195, $pdf->GetY() + 2);
    $pdf->Ln(8);
    
    // SECCIÓN 1: Stock Actual (sin columna Mínimo)
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(107, 68, 35);
    $pdf->Cell(0, 10, 'INVENTARIO ACTUAL', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    
    // Tabla de stock mejorada
    $html = '<style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #6B4423; color: white; font-weight: bold; padding: 8px; }
        td { padding: 6px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
    <table border="0.5" cellpadding="5">
        <thead>
            <tr>
                <th width="20%" style="background-color: #6B4423; color: white;">Código</th>
                <th width="40%" style="background-color: #6B4423; color: white;">Producto</th>
                <th width="25%" style="background-color: #6B4423; color: white;">Categoría</th>
                <th width="15%" align="center" style="background-color: #6B4423; color: white;">Stock</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($productos as $producto) {
        $stock = intval($producto['stock_actual']);
        $stock_minimo = intval($producto['stock_minimo']);
        
        // Color del stock según estado
        $stock_color = '';
        if ($stock == 0) {
            $stock_color = 'style="color: #d32f2f; font-weight: bold;"'; // Rojo
        } elseif ($stock <= $stock_minimo) {
            $stock_color = 'style="color: #f57c00; font-weight: bold;"'; // Naranja
        } else {
            $stock_color = 'style="color: #388e3c;"'; // Verde
        }
        
        $html .= '<tr>
            <td>' . htmlspecialchars($producto['codigo']) . '</td>
            <td>' . htmlspecialchars($producto['nombre']) . '</td>
            <td>' . htmlspecialchars($producto['categoria_nombre']) . '</td>
            <td align="center" ' . $stock_color . '>' . $stock . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    $pdf->writeHTML($html);
    
    // Nueva página para movimientos
    $pdf->AddPage();
    
    // SECCIÓN 2: Movimientos
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(107, 68, 35);
    $pdf->Cell(0, 10, 'MOVIMIENTOS DEL PERÍODO', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    
    if (empty($movimientos)) {
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 20, 'No se encontraron movimientos en el período seleccionado', 0, 1, 'C');
    } else {
        $html = '<style>
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #6B4423; color: white; font-weight: bold; padding: 6px; }
            td { padding: 5px; }
            tr:nth-child(even) { background-color: #f9f9f9; }
        </style>
        <table border="0.5" cellpadding="4">
            <thead>
                <tr>
                    <th width="18%" style="background-color: #6B4423; color: white;">Fecha/Hora</th>
                    <th width="28%" style="background-color: #6B4423; color: white;">Producto</th>
                    <th width="12%" align="center" style="background-color: #6B4423; color: white;">Tipo</th>
                    <th width="10%" align="center" style="background-color: #6B4423; color: white;">Cant.</th>
                    <th width="32%" style="background-color: #6B4423; color: white;">Motivo</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($movimientos as $mov) {
            // Formato de fecha más legible
            $fecha = date('d/m/y H:i', strtotime($mov['created_at']));
            
            // Color según tipo de movimiento
            $tipo_badge = '';
            switch($mov['tipo_movimiento']) {
                case 'ENTRADA':
                    $tipo_badge = '<span style="background-color: #4caf50; color: white; padding: 2px 6px; border-radius: 3px;">ENTRADA</span>';
                    break;
                case 'SALIDA':
                    $tipo_badge = '<span style="background-color: #f44336; color: white; padding: 2px 6px; border-radius: 3px;">SALIDA</span>';
                    break;
                case 'AJUSTE':
                    $tipo_badge = '<span style="background-color: #2196f3; color: white; padding: 2px 6px; border-radius: 3px;">AJUSTE</span>';
                    break;
                default:
                    $tipo_badge = $mov['tipo_movimiento'];
            }
            
            // Formatear motivo
            $motivo = htmlspecialchars($mov['motivo']);
            if (!empty($mov['habitacion_numero'])) {
                $motivo .= ' <i>(Hab. ' . $mov['habitacion_numero'] . ')</i>';
            }
            
            $html .= '<tr>
                <td>' . $fecha . '</td>
                <td>' . htmlspecialchars($mov['producto_nombre'] ?? 'Producto #' . $mov['producto_id']) . '</td>
                <td align="center">' . $tipo_badge . '</td>
                <td align="center" style="font-weight: bold;">' . $mov['cantidad'] . '</td>
                <td style="font-size: 8px;">' . $motivo . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        $pdf->writeHTML($html);
    }
    
    // Resumen mejorado
    if (!empty($movimientos)) {
        $pdf->Ln(10);
        
        // Caja de resumen
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect(15, $pdf->GetY(), 180, 35, 'DF');
        
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(107, 68, 35);
        $pdf->Cell(0, 6, 'RESUMEN DE MOVIMIENTOS', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        $total_entradas = count(array_filter($movimientos, fn($m) => $m['tipo_movimiento'] == 'ENTRADA'));
        $total_salidas = count(array_filter($movimientos, fn($m) => $m['tipo_movimiento'] == 'SALIDA'));
        $total_ajustes = count(array_filter($movimientos, fn($m) => $m['tipo_movimiento'] == 'AJUSTE'));
        
        $pdf->SetX(60);
        $pdf->Cell(40, 6, 'Total Entradas:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 6, $total_entradas, 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetX(60);
        $pdf->Cell(40, 6, 'Total Salidas:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 6, $total_salidas, 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetX(60);
        $pdf->Cell(40, 6, 'Total Ajustes:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 6, $total_ajustes, 0, 1);
    }
    
    // Footer mejorado
    $pdf->SetY(-20);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 5, 'Los Cedros - Sistema de Gestión de Inventario', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generado el ' . date('d/m/Y H:i:s') . ' por ' . ($_SESSION['usuario_nombre'] ?? 'Sistema'), 0, 1, 'C');
    
    // Salida del PDF
    $pdf->Output('inventario_hotel_san_nicolas_' . date('Ymd_His') . '.pdf', 'D');
    exit();
}

// También necesitas agregar este método de debug en tu controlador para verificar los movimientos

// También necesitas agregar este método de debug en tu controlador para verificar los movimientos
public function debugMovimientosDateAction() {
    $fecha_desde = '2024-01-01'; // Cambia estas fechas según necesites
    $fecha_hasta = date('Y-m-d');
    
    $sql = "SELECT m.*, 
                   p.nombre as producto_nombre, 
                   p.codigo as producto_codigo,
                   COALESCE(u.nombre, 'Sistema') as usuario_nombre,
                   h.numero as habitacion_numero
            FROM movimientos_inventario m
            LEFT JOIN inventario_productos p ON m.producto_id = p.id
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            LEFT JOIN habitaciones h ON m.habitacion_id = h.id
            WHERE DATE(m.created_at) BETWEEN ? AND ?
            ORDER BY m.created_at DESC";
    
    $db = Database::getInstance();
    $stmt = $db->query($sql, [$fecha_desde, $fecha_hasta]);
    $movimientos = $stmt ? $stmt->fetchAll() : [];
    
    echo "<h2>Debug Movimientos entre $fecha_desde y $fecha_hasta</h2>";
    echo "<p>Total encontrados: " . count($movimientos) . "</p>";
    
    if (!empty($movimientos)) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th></tr>";
        foreach ($movimientos as $mov) {
            echo "<tr>";
            echo "<td>{$mov['id']}</td>";
            echo "<td>{$mov['created_at']}</td>";
            echo "<td>{$mov['producto_nombre']}</td>";
            echo "<td>{$mov['tipo_movimiento']}</td>";
            echo "<td>{$mov['cantidad']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // También verificar si hay movimientos sin fecha
    $sql2 = "SELECT COUNT(*) as total, 
                    COUNT(CASE WHEN created_at IS NULL THEN 1 END) as sin_fecha,
                    MIN(created_at) as fecha_min,
                    MAX(created_at) as fecha_max
             FROM movimientos_inventario";
    $stmt2 = $db->query($sql2);
    $stats = $stmt2->fetch();
    
    echo "<h3>Estadísticas de la tabla movimientos_inventario:</h3>";
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
    
    exit();
}
    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        return true;
    }
    public function eliminar($id) {
    try {
        // Verificar permisos si es necesario
        if (!$this->checkPermission('inventario.eliminar')) {
            $this->redirect('/inventario')->with('error', 'No tiene permisos para eliminar productos');
        }
        
        // Obtener el producto
        $producto = $this->productoModel->find($id);
        
        if (!$producto) {
            $this->redirect('/inventario')->with('error', 'Producto no encontrado');
        }
        
        // Verificar si tiene movimientos recientes (opcional)
        $movimientos_recientes = $this->movimientoInventarioModel->where('producto_id', $id)
                                                                 ->where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
                                                                 ->count();
        
        if ($movimientos_recientes > 0) {
            $this->redirect('/inventario')->with('warning', 'No se puede eliminar un producto con movimientos recientes');
        }
        
        // Eliminar el producto (soft delete o hard delete según tu preferencia)
        $this->productoModel->delete($id);
        
        // O si prefieres soft delete:
        // $this->productoModel->update($id, ['activo' => 0]);
        
        $this->redirect('/inventario')->with('success', 'Producto eliminado correctamente');
        
    } catch (Exception $e) {
        error_log("Error al eliminar producto: " . $e->getMessage());
        $this->redirect('/inventario')->with('error', 'Error al eliminar el producto');
    }
}
    /**
     * Vista principal - Dashboard de inventario
     */
    public function indexAction() {
    $productos = $this->inventarioModel->getAllWithCategory();
    
    // CAMBIO: Usar el método alternativo si el principal falla
    $movimientos_recientes = [];
    
    try {
        // Primero intentar el método normal
        $movimientos_recientes = $this->movimientoModel->getMovimientosDetallados(20);
        
        // Si no hay movimientos o están mal ordenados, usar método alternativo
        if (empty($movimientos_recientes)) {
            error_log("No se encontraron movimientos con getMovimientosDetallados, intentando método alternativo");
            
            // Método alternativo: usar el nuevo método getMovimientosRecientes
            if (method_exists($this->movimientoModel, 'getMovimientosRecientes')) {
                $movimientos_recientes = $this->movimientoModel->getMovimientosRecientes(20);
            }
        } else {
            // Verificar que estén bien ordenados (el primer elemento debe ser el más reciente)
            $primer_mov = $movimientos_recientes[0];
            $ultimo_mov = end($movimientos_recientes);
            
            // Si el primer movimiento es más antiguo que el último, están mal ordenados
            if (isset($primer_mov['id']) && isset($ultimo_mov['id']) && $primer_mov['id'] < $ultimo_mov['id']) {
                error_log("Movimientos mal ordenados, reordenando por ID descendente");
                
                // Reordenar por ID descendente
                usort($movimientos_recientes, function($a, $b) {
                    return $b['id'] - $a['id'];
                });
            }
        }
        
        // Debug: mostrar información de los primeros movimientos
        if (!empty($movimientos_recientes)) {
            error_log("Movimientos encontrados: " . count($movimientos_recientes));
            $primer_movimiento = $movimientos_recientes[0];
            error_log("Primer movimiento - ID: " . ($primer_movimiento['id'] ?? 'N/A') . 
                     ", Fecha: " . ($primer_movimiento['created_at'] ?? 'N/A'));
        }
        
    } catch (Exception $e) {
        error_log("Error obteniendo movimientos: " . $e->getMessage());
        $movimientos_recientes = [];
    }
    
    View::renderTemplate('inventario/index', [
        'title' => 'Inventario - Los Cedros',
        'productos' => $productos,
        'movimientos_recientes' => $movimientos_recientes
    ]);
}
    
    /**
     * Formulario nuevo producto
     */
    public function nuevoAction() {
        $categorias = $this->inventarioModel->getCategorias();
        
        View::renderTemplate('inventario/nuevo', [
            'title' => 'Nuevo Producto - Los Cedros',
            'categorias' => $categorias
        ]);
    }
    
    /**
     * Guardar producto - MÉTODO CORREGIDO
     */
    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }
        
        $this->validateCSRF();
        
        try {
            // Preparar datos del producto
            $data = [
                'codigo' => trim($this->getPost('codigo')),
                'nombre' => trim($this->getPost('nombre')),
                'categoria_id' => $this->getPost('categoria_id'),
                'stock_actual' => intval($this->getPost('stock_inicial', 0)), // MAPEAR stock_inicial -> stock_actual
                'stock_minimo' => intval($this->getPost('stock_minimo', 0)),
                'costo_unitario' => floatval($this->getPost('costo_unitario', 0)),
                'unidad_medida' => $this->getPost('unidad_medida', 'pieza'),
                'descripcion' => trim($this->getPost('descripcion', '')),
                'descuento_automatico' => $this->getPost('descuento_automatico') ? 1 : 0,
                'activo' => 1
            ];
            
            // Crear el producto
            $producto_id = $this->inventarioModel->create($data);
            
            if ($producto_id) {
                // Si hay stock inicial, registrar el movimiento
                if ($data['stock_actual'] > 0) {
                    $movimiento_data = [
    'producto_id' => $producto_id,
    'tipo_movimiento' => 'ENTRADA',
    'cantidad' => $data['stock_actual'],
    'stock_anterior' => 0,
    'stock_posterior' => $data['stock_actual'],
    'motivo' => 'Stock inicial al crear producto',
    'usuario_id' => $_SESSION['usuario_id'] ?? null,
    'created_at' => date('Y-m-d H:i:s') // AGREGAR ESTA LÍNEA
];
                    
                    $this->movimientoModel->create($movimiento_data);
                }
                
                set_mensaje('Producto creado correctamente', 'success');
            } else {
                set_mensaje('Error al crear el producto', 'error');
            }
            
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('inventario');
    }
    
    /**
     * Vista de entrada de inventario
     */
    public function entradaAction() {
        $productos = $this->inventarioModel->getAllWithCategory();
        
        View::renderTemplate('inventario/entrada', [
            'title' => 'Entrada de Inventario - Los Cedros',
            'productos' => $productos
        ]);
    }
    
    /**
     * Procesar entrada - MÉTODO CORREGIDO
     */
    public function procesarEntradaAction() {
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }
        
        $this->validateCSRF();
        
        try {
            $producto_id = intval($this->getPost('producto_id'));
            $cantidad = abs(intval($this->getPost('cantidad')));
            $motivo = trim($this->getPost('motivo'));
            
            // Obtener producto actual
            $producto = $this->inventarioModel->find($producto_id);
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            $stock_anterior = intval($producto['stock_actual']);
            $stock_nuevo = $stock_anterior + $cantidad;
            
            // Actualizar stock directamente
            $actualizado = $this->inventarioModel->update($producto_id, [
                'stock_actual' => $stock_nuevo
            ]);
            
            if ($actualizado) {
                // Registrar movimiento
                $movimiento_data = [
    'producto_id' => $producto_id,
    'tipo_movimiento' => 'ENTRADA',
    'cantidad' => $cantidad,
    'stock_anterior' => $stock_anterior,
    'stock_posterior' => $stock_nuevo,
    'motivo' => $motivo,
    'usuario_id' => $_SESSION['usuario_id'] ?? null,
    'created_at' => date('Y-m-d H:i:s') // AGREGAR ESTA LÍNEA
];
                
                $this->movimientoModel->create($movimiento_data);
                
                set_mensaje('Entrada registrada correctamente', 'success');
            } else {
                throw new Exception('No se pudo actualizar el stock');
            }
            
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('inventario');
    }
    
    /**
     * Vista de salida manual
     */
    public function salidaAction() {
        $productos = $this->inventarioModel->getAllWithCategory();
        $habitaciones = $this->inventarioModel->getHabitacionesActivas();
        
        View::renderTemplate('inventario/salida', [
            'title' => 'Salida de Inventario - Los Cedros',
            'productos' => $productos,
            'habitaciones' => $habitaciones
        ]);
    }
    
    /**
     * Procesar salida - MÉTODO CORREGIDO
     */
    public function procesarSalidaAction() {
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }
        
        $this->validateCSRF();
        
        try {
            $producto_id = intval($this->getPost('producto_id'));
            $cantidad = abs(intval($this->getPost('cantidad')));
            $motivo = trim($this->getPost('motivo'));
            $habitacion_id = $this->getPost('habitacion_id') ?: null;
            
            // Obtener producto actual
            $producto = $this->inventarioModel->find($producto_id);
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            $stock_anterior = intval($producto['stock_actual']);
            
            // Verificar stock suficiente
            if ($stock_anterior < $cantidad) {
                throw new Exception('Stock insuficiente. Disponible: ' . $stock_anterior);
            }
            
            $stock_nuevo = $stock_anterior - $cantidad;
            
            // Actualizar stock directamente
            $actualizado = $this->inventarioModel->update($producto_id, [
                'stock_actual' => $stock_nuevo
            ]);
            
            if ($actualizado) {
                // Registrar movimiento
                $movimiento_data = [
    'producto_id' => $producto_id,
    'tipo_movimiento' => 'SALIDA',
    'cantidad' => $cantidad,
    'stock_anterior' => $stock_anterior,
    'stock_posterior' => $stock_nuevo,
    'motivo' => $motivo,
    'habitacion_id' => $habitacion_id,
    'usuario_id' => $_SESSION['usuario_id'] ?? null,
    'created_at' => date('Y-m-d H:i:s') // AGREGAR ESTA LÍNEA
];
                
                $this->movimientoModel->create($movimiento_data);
                
                set_mensaje('Salida registrada correctamente', 'success');
            } else {
                throw new Exception('No se pudo actualizar el stock');
            }
            
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            $this->redirect('inventario/salida');
            return;
        }
        
        $this->redirect('inventario');
    }
    
    /**
     * Configuración por habitación
     */
    public function configuracionAction() {
        $productos_automaticos = $this->inventarioModel->getProductosDescuentoAutomatico();
        $configuracion = $this->inventarioModel->getConfiguracionCompleta();
        
        $tipos_habitacion = [
    'sencilla' => 'Sencilla',
    'doble' => 'Doble',
    'triple' => 'Triple',
    'cuadruple' => 'Cuádruple',
    'sencilla_manolo' => 'Sencilla Manolo',
    'doble_manolo' => 'Doble Manolo'
];
        
        View::renderTemplate('inventario/configuracion', [
            'title' => 'Configuración de Descuentos - Los Cedros',
            'productos_automaticos' => $productos_automaticos,
            'configuracion' => $configuracion,
            'tipos_habitacion' => $tipos_habitacion
        ]);
    }
    
    /**
     * Guardar configuración
     */
    public function guardarConfiguracionAction() {
    if (!$this->isPost()) {
        $this->redirect('inventario/configuracion');
        return;
    }
    
    $this->validateCSRF();
    
    try {
        // AGREGAR ESTAS LÍNEAS DE DEBUG
        error_log("=== DEBUG CONFIGURACION ===");
        error_log("POST completo: " . print_r($_POST, true));
        
        // Procesar cada configuración enviada
        $config = $this->getPost('config', []);
        
        // AGREGAR ESTA LÍNEA
        error_log("Config procesada: " . print_r($config, true));
        
        foreach ($config as $tipo_hab => $productos) {
    foreach ($productos as $prod_id => $cantidad) {
        // Convertir cantidad a entero
        $cantidad = intval($cantidad);
        $this->inventarioModel->actualizarConfiguracion($tipo_hab, $prod_id, $cantidad);
    }
}
        
        set_mensaje('Configuración actualizada correctamente', 'success');
        
    } catch (Exception $e) {
        error_log("ERROR en guardarConfiguracion: " . $e->getMessage());
        set_mensaje('Error: ' . $e->getMessage(), 'error');
    }
    
    $this->redirect('inventario/configuracion');
}
    
    /**
     * Editar producto
     */
    public function editarAction() {
        $id = $this->route_params['id'] ?? 0;
        
        $producto = $this->inventarioModel->getByIdWithCategory($id);
        
        if (!$producto) {
            set_mensaje('Producto no encontrado', 'error');
            $this->redirect('inventario');
            return;
        }
        
        $categorias = $this->inventarioModel->getCategorias();
        
        View::renderTemplate('inventario/editar', [
            'title' => 'Editar Producto - Los Cedros',
            'producto' => $producto,
            'categorias' => $categorias
        ]);
    }
    
    /**
     * Actualizar producto
     */
    public function actualizarAction() {
        $id = $this->route_params['id'] ?? 0;
        
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }
        
        $this->validateCSRF();
        
        try {
            $data = [
                'codigo' => trim($this->getPost('codigo')),
                'nombre' => trim($this->getPost('nombre')),
                'categoria_id' => $this->getPost('categoria_id'),
                'stock_minimo' => intval($this->getPost('stock_minimo', 0)),
                'costo_unitario' => floatval($this->getPost('costo_unitario', 0)),
                'unidad_medida' => $this->getPost('unidad_medida', 'pieza'),
                'descripcion' => trim($this->getPost('descripcion', '')),
                'descuento_automatico' => $this->getPost('descuento_automatico') ? 1 : 0
            ];
            
            if ($this->inventarioModel->update($id, $data)) {
                set_mensaje('Producto actualizado correctamente', 'success');
            } else {
                throw new Exception('Error al actualizar el producto');
            }
            
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('inventario');
    }
    
    /**
     * Eliminar producto (desactivar)
     */
    public function eliminarAction() {
        $id = $this->route_params['id'] ?? 0;
        
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }
        
        $this->validateCSRF();
        
        try {
            // Solo desactivar, no eliminar físicamente
            if ($this->inventarioModel->update($id, ['activo' => 0])) {
                set_mensaje('Producto eliminado correctamente', 'success');
            } else {
                throw new Exception('Error al eliminar el producto');
            }
            
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('inventario');
    }
    
    /**
     * Ajuste de inventario (para correcciones)
     */
    public function ajusteAction() {
        $productos = $this->inventarioModel->getAllWithCategory();
        
        View::renderTemplate('inventario/ajuste', [
            'title' => 'Ajuste de Inventario - Los Cedros',
            'productos' => $productos
        ]);
    }
    
    /**
     * Procesar ajuste de inventario
     */
    public function procesarAjusteAction() {
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }
        
        $this->validateCSRF();
        
        try {
            $producto_id = intval($this->getPost('producto_id'));
            $stock_nuevo = intval($this->getPost('stock_nuevo'));
            $motivo = trim($this->getPost('motivo'));
            
            // Obtener producto actual
            $producto = $this->inventarioModel->find($producto_id);
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            $stock_anterior = intval($producto['stock_actual']);
            
            // Solo procesar si hay diferencia
            if ($stock_anterior !== $stock_nuevo) {
                // Actualizar stock
                $actualizado = $this->inventarioModel->update($producto_id, [
                    'stock_actual' => $stock_nuevo
                ]);
                
                if ($actualizado) {
                    // Calcular diferencia
                    $diferencia = $stock_nuevo - $stock_anterior;
                    
                    // Registrar movimiento de ajuste
                    $movimiento_data = [
    'producto_id' => $producto_id,
    'tipo_movimiento' => 'AJUSTE',
    'cantidad' => abs($diferencia),
    'stock_anterior' => $stock_anterior,
    'stock_posterior' => $stock_nuevo,
    'motivo' => 'Ajuste manual: ' . $motivo,
    'usuario_id' => $_SESSION['usuario_id'] ?? null,
    'created_at' => date('Y-m-d H:i:s') // AGREGAR ESTA LÍNEA
];
                    // Justo antes de crear el movimiento
error_log("=== DEBUG MOVIMIENTO ===");
error_log("Datos antes de create: " . print_r($movimiento_data, true));

$resultado = $this->movimientoModel->create($movimiento_data);

error_log("Resultado del create: " . $resultado);
error_log("=== FIN DEBUG ===");
                    $this->movimientoModel->create($movimiento_data);
                    
                    set_mensaje('Ajuste realizado correctamente', 'success');
                } else {
                    throw new Exception('No se pudo actualizar el stock');
                }
            } else {
                set_mensaje('No hay cambios en el stock', 'info');
            }
            
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('inventario');
    }
    
    /**
     * Reporte de inventario
     */
    public function reporteAction() {
        $productos = $this->inventarioModel->getAllWithCategory();
        
        // Calcular estadísticas
        $total_productos = count($productos);
        $productos_bajo_minimo = 0;
        $valor_total = 0;
        
        foreach ($productos as $producto) {
            if ($producto['stock_actual'] <= $producto['stock_minimo']) {
                $productos_bajo_minimo++;
            }
            $valor_total += $producto['stock_actual'] * $producto['costo_unitario'];
        }
        
        View::renderTemplate('inventario/reporte', [
            'title' => 'Reporte de Inventario - Los Cedros',
            'productos' => $productos,
            'estadisticas' => [
                'total_productos' => $total_productos,
                'productos_bajo_minimo' => $productos_bajo_minimo,
                'valor_total' => $valor_total
            ]
        ]);
    }
    
    /**
     * Historial de movimientos de un producto
     */
    public function historialAction() {
        $producto_id = $this->route_params['id'] ?? 0;
        
        $producto = $this->inventarioModel->getByIdWithCategory($producto_id);
        
        if (!$producto) {
            set_mensaje('Producto no encontrado', 'error');
            $this->redirect('inventario');
            return;
        }
        
        $movimientos = $this->movimientoModel->getMovimientosPorProducto($producto_id);
        
        View::renderTemplate('inventario/historial', [
            'title' => 'Historial de ' . $producto['nombre'] . ' - Los Cedros',
            'producto' => $producto,
            'movimientos' => $movimientos
        ]);
    }
}