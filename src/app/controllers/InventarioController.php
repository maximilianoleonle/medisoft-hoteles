<?php
/**
 * Controlador de Inventario
 * Sistema hotelero
 */
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/MovimientoInventario.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../services/AuditService.php';

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

    private function usuarioIdActualInventario(): ?int {
        $usuarioId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        return $usuarioId ? (int) $usuarioId : null;
    }

    private function limitarTextoInventario(string $texto, int $limite): string {
        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $limite, 'UTF-8');
        }

        return substr($texto, 0, $limite);
    }

    private function registrarAuditoriaConfiguracionInventario(array $cambios): void {
        if (empty($cambios)) {
            return;
        }

        try {
            $hotelId = function_exists('obtenerHotelIdActualCompat') ? obtenerHotelIdActualCompat() : null;
            $datosAntes = [];
            $datosDespues = [];

            foreach ($cambios as $cambio) {
                $clave = ($cambio['tipo_habitacion'] ?? 'tipo') . '#' . ($cambio['producto_id'] ?? 'producto');
                $datosAntes[$clave] = [
                    'tipo_habitacion' => $cambio['tipo_habitacion'] ?? null,
                    'producto_id' => $cambio['producto_id'] ?? null,
                    'producto_nombre' => $cambio['producto_nombre'] ?? null,
                    'cantidad' => $cambio['cantidad_anterior'] ?? null,
                    'activo' => $cambio['activo_anterior'] ?? null,
                ];
                $datosDespues[$clave] = [
                    'tipo_habitacion' => $cambio['tipo_habitacion'] ?? null,
                    'producto_id' => $cambio['producto_id'] ?? null,
                    'producto_nombre' => $cambio['producto_nombre'] ?? null,
                    'cantidad' => $cambio['cantidad_nueva'] ?? null,
                    'activo' => $cambio['activo_nuevo'] ?? null,
                    'descuento_automatico_cambio' => (bool)($cambio['descuento_automatico_cambio'] ?? false),
                    'origen' => 'inventario/configuracion',
                ];
            }

            AuditService::record('inventario.configuracion_actualizada', [
                'hotel_id' => $hotelId,
                'usuario_id' => $this->usuarioIdActualInventario(),
                'entidad_tipo' => 'inventario_configuracion',
                'entidad_id' => 'hotel:' . (string)$hotelId,
                'descripcion' => 'Configuracion de inventario por tipo de habitacion actualizada',
                'datos_antes' => $datosAntes,
                'datos_despues' => $datosDespues,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar configuracion de inventario: ' . $e->getMessage());
        }
    }

    private function inventarioPdfBranding(): array {
        $branding = function_exists('current_hotel_branding') ? current_hotel_branding() : [];
        if (!is_array($branding)) {
            $branding = [];
        }

        $primary = $this->inventarioPdfHex($branding['color_primary'] ?? null, '#1B2746');
        $secondary = $this->inventarioPdfHex($branding['color_secondary'] ?? null, '#0F172A');
        $accent = $this->inventarioPdfHex($branding['color_accent'] ?? null, '#BD9441');
        $primaryText = $this->inventarioPdfTextColor($primary);

        return [
            'hotel' => function_exists('current_hotel_display_name')
                ? current_hotel_display_name('Medisoft Hoteles')
                : 'Medisoft Hoteles',
            'primary' => $primary,
            'secondary' => $secondary,
            'accent' => $accent,
            'primary_text' => $primaryText,
            'primary_rgb' => $this->inventarioPdfRgb($primary),
            'secondary_rgb' => $this->inventarioPdfRgb($secondary),
            'accent_rgb' => $this->inventarioPdfRgb($accent),
            'primary_text_rgb' => $this->inventarioPdfRgb($primaryText),
            'soft' => $this->inventarioPdfMix($primary, '#FFFFFF', 0.08),
            'line' => $this->inventarioPdfMix($accent, '#D9DEE8', 0.35),
        ];
    }

    private function inventarioPdfHex($color, string $fallback): string {
        if (function_exists('hotel_branding_hex')) {
            return hotel_branding_hex($color, $fallback);
        }

        $color = trim((string) $color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return strtoupper($color);
        }

        if (preg_match('/^#[0-9a-fA-F]{3}$/', $color)) {
            return strtoupper('#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3]);
        }

        return strtoupper($fallback);
    }

    private function inventarioPdfRgb(string $hex): array {
        $hex = ltrim($this->inventarioPdfHex($hex, '#000000'), '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function inventarioPdfMix(string $hex, string $target, float $ratio): string {
        $ratio = max(0, min(1, $ratio));
        $a = $this->inventarioPdfRgb($hex);
        $b = $this->inventarioPdfRgb($target);

        return sprintf(
            '#%02X%02X%02X',
            (int) round($a[0] * $ratio + $b[0] * (1 - $ratio)),
            (int) round($a[1] * $ratio + $b[1] * (1 - $ratio)),
            (int) round($a[2] * $ratio + $b[2] * (1 - $ratio))
        );
    }

    private function inventarioPdfTextColor(string $hex): string {
        $rgb = $this->inventarioPdfRgb($hex);
        $luminance = (($rgb[0] * 299) + ($rgb[1] * 587) + ($rgb[2] * 114)) / 1000;
        return $luminance > 155 ? '#111827' : '#FFFFFF';
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

    $fecha_hasta = date('Y-m-d');
    $fecha_desde = date('Y-m-01', strtotime($fecha_hasta));

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
    echo "<input type='hidden' name='fecha_desde' value='" . htmlspecialchars($fecha_desde, ENT_QUOTES, 'UTF-8') . "'>";
    echo "<input type='hidden' name='fecha_hasta' value='" . htmlspecialchars($fecha_hasta, ENT_QUOTES, 'UTF-8') . "'>";
    echo "<button type='submit' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "Generar PDF de Prueba (Periodo actual)";
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
    require_hotel_module('exportaciones');
    $fecha_hoy = date('Y-m-d');

    View::renderTemplate('inventario/exportar', [
        'title' => 'Exportar Inventario - ' . current_hotel_display_name(),
        'fecha_hoy' => $fecha_hoy,
        'fecha_inicio_mes' => date('Y-m-01', strtotime($fecha_hoy))
    ]);
}

/**
 * Generar PDF de movimientos
 */
public function generarPdfMovimientosAction() {
    require_hotel_module('exportaciones');
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

        $hotel_id = obtenerHotelIdActualCompat();

        // Primero, obtener el rango de fechas que tiene movimientos del hotel actual
        $db = Database::getInstance();
        $sql_rango = "SELECT MIN(DATE(created_at)) as fecha_min,
                             MAX(DATE(created_at)) as fecha_max,
                             COUNT(*) as total
                      FROM movimientos_inventario
                      WHERE hotel_id = ?";
        $stmt_rango = $db->query($sql_rango, [$hotel_id]);
        $rango = $stmt_rango->fetch();

        error_log("Rango de fechas en BD: " . $rango['fecha_min'] . " a " . $rango['fecha_max']);
        error_log("Total de movimientos en BD: " . $rango['total']);

        // Consulta de movimientos
        $sql = "SELECT m.*,
               p.nombre as producto_nombre,
               p.codigo as producto_codigo,
               h.numero as habitacion_numero
        FROM movimientos_inventario m
        LEFT JOIN inventario_productos p
            ON m.producto_id = p.id
            AND p.hotel_id = m.hotel_id
        LEFT JOIN habitaciones h
            ON m.habitacion_id = h.id
            AND h.hotel_id = m.hotel_id
        WHERE m.hotel_id = ?
        AND DATE(m.created_at) BETWEEN ? AND ?
        ORDER BY m.created_at DESC";

        $stmt = $db->query($sql, [$hotel_id, $fecha_desde, $fecha_hasta]);
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

    // Crear nuevo PDF
    $brand = $this->inventarioPdfBranding();
    $primaryRgb = $brand['primary_rgb'];
    $accentRgb = $brand['accent_rgb'];
    $tableHeaderStyle = 'background-color: ' . $brand['primary'] . '; color: ' . $brand['primary_text'] . ';';

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');

    // Configuración
    $pdf->SetCreator($brand['hotel']);
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
    $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->Cell(0, 12, $brand['hotel'], 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(0, 8, 'Reporte de Inventario', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, 'Del ' . format_date($fecha_desde) . ' al ' . format_date($fecha_hasta), 0, 1, 'C');

    // Línea decorativa
    $pdf->SetDrawColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY() + 2, 195, $pdf->GetY() + 2);
    $pdf->Ln(8);

    // SECCIÓN 1: Stock Actual (sin columna Mínimo)
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->Cell(0, 10, 'INVENTARIO ACTUAL', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    // Tabla de stock mejorada
    $html = '<style>
        table { border-collapse: collapse; width: 100%; }
        th { ' . $tableHeaderStyle . ' font-weight: bold; padding: 8px; }
        td { padding: 6px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
    <table border="0.5" cellpadding="5">
        <thead>
            <tr>
                <th width="20%" style="' . $tableHeaderStyle . '">Código</th>
                <th width="40%" style="' . $tableHeaderStyle . '">Producto</th>
                <th width="25%" style="' . $tableHeaderStyle . '">Categoría</th>
                <th width="15%" align="center" style="' . $tableHeaderStyle . '">Stock</th>
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
    $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
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
            th { ' . $tableHeaderStyle . ' font-weight: bold; padding: 6px; }
            td { padding: 5px; }
            tr:nth-child(even) { background-color: #f9f9f9; }
        </style>
        <table border="0.5" cellpadding="4">
            <thead>
                <tr>
                    <th width="18%" style="' . $tableHeaderStyle . '">Fecha/Hora</th>
                    <th width="28%" style="' . $tableHeaderStyle . '">Producto</th>
                    <th width="12%" align="center" style="' . $tableHeaderStyle . '">Tipo</th>
                    <th width="10%" align="center" style="' . $tableHeaderStyle . '">Cant.</th>
                    <th width="32%" style="' . $tableHeaderStyle . '">Motivo</th>
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
        $softRgb = $this->inventarioPdfRgb($brand['soft']);
        $lineRgb = $this->inventarioPdfRgb($brand['line']);
        $pdf->SetFillColor($softRgb[0], $softRgb[1], $softRgb[2]);
        $pdf->SetDrawColor($lineRgb[0], $lineRgb[1], $lineRgb[2]);
        $pdf->Rect(15, $pdf->GetY(), 180, 35, 'DF');

        $pdf->SetY($pdf->GetY() + 5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
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
    $pdf->Cell(0, 5, $brand['hotel'] . ' - Sistema de Gestión de Inventario', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generado el ' . date('d/m/Y H:i:s') . ' por ' . ($_SESSION['usuario_nombre'] ?? 'Sistema'), 0, 1, 'C');

    // Salida del PDF
    $nombreArchivo = function_exists('hotel_export_filename')
        ? hotel_export_filename('inventario', 'pdf')
        : 'inventario_' . date('Ymd_His') . '.pdf';
    $pdf->Output($nombreArchivo, 'D');
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
        require_hotel_module('inventario');
        // El aislamiento cross-tenant ya lo cubre el modelo (AND hotel_id = ?),
        // pero faltaba el permiso fino: sin esto cualquier autenticado podia
        // crear/editar/eliminar productos y registrar entradas/salidas/ajustes.
        require_permission('inventarios.view');
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
        'title' => 'Inventario - ' . current_hotel_display_name(),
        'productos' => $productos,
        'movimientos_recientes' => $movimientos_recientes
    ]);
}

    private function inventarioTablaTieneColumna($tabla, $columna): bool {
        $tablas_permitidas = [
            'movimientos_inventario',
            'inventario_productos',
            'inventario_categorias',
            'habitaciones',
        ];

        if (!in_array($tabla, $tablas_permitidas, true)) {
            return false;
        }

        $stmt = $this->db->query("SHOW COLUMNS FROM {$tabla} LIKE ?", [$columna]);
        return $stmt && (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function obtenerProductosParaFiltroMovimientos($filtrar_por_hotel, $hotel_id): array {
        $productos_tienen_hotel = $this->inventarioTablaTieneColumna('inventario_productos', 'hotel_id');
        $categorias_tienen_hotel = $this->inventarioTablaTieneColumna('inventario_categorias', 'hotel_id');

        $join_categoria = 'p.categoria_id = c.id';
        if ($productos_tienen_hotel && $categorias_tienen_hotel) {
            $join_categoria .= ' AND c.hotel_id = p.hotel_id';
        }

        $where = ['p.activo = 1'];
        $params = [];

        if ($filtrar_por_hotel && $productos_tienen_hotel) {
            $where[] = 'p.hotel_id = ?';
            $params[] = $hotel_id;
        }

        $stmt = $this->db->query(
            "SELECT p.*,
                    COALESCE(c.nombre, 'Sin categoria') AS categoria_nombre
             FROM inventario_productos p
             LEFT JOIN inventario_categorias c
                ON {$join_categoria}
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.nombre",
            $params
        );

        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    private function unidadMedidaDefaultInventario(): string {
        if (function_exists('hotel_general_catalog_units')) {
            foreach (hotel_general_catalog_units() as $unidadKey => $unidadLabel) {
                $unidadKey = trim((string)$unidadKey);
                if ($unidadKey !== '' && strlen($unidadKey) <= 20) {
                    return $unidadKey;
                }
            }
        }

        return 'pieza';
    }

    private function unidadMedidaDesdePost(string $key): string {
        $unidad = trim((string)$this->getPost($key, ''));
        $unidades = function_exists('hotel_general_catalog_units')
            ? hotel_general_catalog_units()
            : [];

        if ($unidad !== '' && strlen($unidad) <= 20 && (empty($unidades) || array_key_exists($unidad, $unidades))) {
            return $unidad;
        }

        return $this->unidadMedidaDefaultInventario();
    }

    private function normalizarCantidadMovimientoInventario($valor): float {
        $texto = str_replace(',', '.', trim((string)$valor));

        if ($texto === '' || !is_numeric($texto)) {
            throw new Exception('La cantidad debe ser numerica');
        }

        $cantidad = round((float)$texto, 2);

        if ($cantidad <= 0) {
            throw new Exception('La cantidad debe ser mayor a cero');
        }

        return $cantidad;
    }

    private function decimalInventario(float $valor): string {
        return number_format(round($valor, 2), 2, '.', '');
    }

    private function normalizarTipoHabitacionInventario($codigo): string {
        $codigo = strtolower(trim((string)$codigo));

        if ($codigo === '') {
            return '';
        }

        if (function_exists('hotel_room_catalog_storage_type_for_code')) {
            return hotel_room_catalog_storage_type_for_code($codigo) ?: '';
        }

        $compatibles = [
            'sencilla' => true,
            'doble' => true,
            'triple' => true,
            'cuadruple' => true,
            'sencilla_manolo' => true,
            'doble_manolo' => true,
        ];

        return isset($compatibles[$codigo]) ? $codigo : '';
    }

    private function tiposHabitacionInventario(): array {
        $tipos = [];
        $catalogoTipos = [];

        if (function_exists('hotel_room_catalog_types_for_select')) {
            $catalogoTipos = hotel_room_catalog_types_for_select();
        } elseif (function_exists('hotel_room_catalog_types')) {
            $catalogoTipos = hotel_room_catalog_types();
        }

        if (!empty($catalogoTipos)) {
            foreach ($catalogoTipos as $codigo => $nombre) {
                $codigo = trim((string)$codigo);
                if ($codigo === '') {
                    continue;
                }

                $tipoTecnico = $this->normalizarTipoHabitacionInventario($codigo);
                if ($tipoTecnico === '') {
                    continue;
                }

                $tipos[$tipoTecnico] = trim((string)$nombre) !== ''
                    ? (string)$nombre
                    : ucwords(str_replace('_', ' ', $tipoTecnico));
            }
        }

        if (!empty($tipos)) {
            return $tipos;
        }

        return [
            'sencilla' => 'Sencilla',
            'doble' => 'Doble',
            'triple' => 'Triple',
            'cuadruple' => 'Cuadruple',
            'doble_jacuzzi' => 'Doble con Jacuzzi',
            'sencilla_jacuzzi' => 'Sencilla con Jacuzzi'
        ];
    }

    private function normalizarConfiguracionInventarioPorTipo(array $configuracion): array {
        $normalizada = [];

        foreach ($configuracion as $tipo => $configs) {
            $tipoTecnico = $this->normalizarTipoHabitacionInventario($tipo);
            if ($tipoTecnico === '') {
                continue;
            }

            foreach ((array)$configs as $config) {
                $productoId = (int)($config['producto_id'] ?? 0);
                if ($productoId <= 0) {
                    continue;
                }

                $normalizada[$tipoTecnico][$productoId] = $config;
                $normalizada[$tipoTecnico][$productoId]['tipo_habitacion'] = $tipoTecnico;
            }
        }

        return array_map('array_values', $normalizada);
    }

    /**
     * Vista de movimientos de inventario
     */
    public function movimientosAction() {
        $hotel_id = obtenerHotelIdActualCompat();
        $movimientos_tienen_hotel = $this->inventarioTablaTieneColumna('movimientos_inventario', 'hotel_id');
        $productos_tienen_hotel = $this->inventarioTablaTieneColumna('inventario_productos', 'hotel_id');
        $habitaciones_tienen_hotel = $this->inventarioTablaTieneColumna('habitaciones', 'hotel_id');
        $productos = $this->obtenerProductosParaFiltroMovimientos($movimientos_tienen_hotel, $hotel_id);

        $tipo = strtoupper(trim((string) $this->getQuery('tipo', '')));
        $tipos_validos = ['ENTRADA', 'SALIDA', 'AJUSTE'];
        if (!in_array($tipo, $tipos_validos, true)) {
            $tipo = '';
        }

        $origen = strtolower(trim((string) $this->getQuery('origen', '')));
        $origenes_validos = ['manual', 'automatico'];
        if (!in_array($origen, $origenes_validos, true)) {
            $origen = '';
        }

        $filtros = [
            'producto_id' => (int) $this->getQuery('producto_id', 0),
            'tipo' => $tipo,
            'origen' => $origen,
            'fecha_desde' => trim((string) $this->getQuery('fecha_desde', '')),
            'fecha_hasta' => trim((string) $this->getQuery('fecha_hasta', '')),
        ];

        $pagina_actual = max(1, (int) $this->getQuery('pagina', 1));
        $por_pagina = 25;
        $offset = ($pagina_actual - 1) * $por_pagina;

        $where = [];
        $params = [];

        if ($movimientos_tienen_hotel) {
            $where[] = 'm.hotel_id = ?';
            $params[] = $hotel_id;
        }

        if ($filtros['producto_id'] > 0) {
            $where[] = 'm.producto_id = ?';
            $params[] = $filtros['producto_id'];
        }

        if ($filtros['tipo'] !== '') {
            $where[] = 'm.tipo_movimiento = ?';
            $params[] = $filtros['tipo'];
        }

        $motivo_sql = "LOWER(COALESCE(m.motivo, ''))";
        $manual_sql = "(
            {$motivo_sql} LIKE '%manual%'
            OR (
                (m.reservacion_id IS NULL OR m.reservacion_id = 0)
                AND {$motivo_sql} NOT LIKE '%autom%'
                AND {$motivo_sql} NOT LIKE '%check-in%'
                AND {$motivo_sql} NOT LIKE '%devolucion%'
                AND {$motivo_sql} NOT LIKE '%cancelacion%'
            )
        )";

        if ($filtros['origen'] === 'manual') {
            $where[] = $manual_sql;
        } elseif ($filtros['origen'] === 'automatico') {
            $where[] = "NOT {$manual_sql}";
        }

        if ($filtros['fecha_desde'] !== '') {
            $where[] = 'DATE(m.created_at) >= ?';
            $params[] = $filtros['fecha_desde'];
        }

        if ($filtros['fecha_hasta'] !== '') {
            $where[] = 'DATE(m.created_at) <= ?';
            $params[] = $filtros['fecha_hasta'];
        }

        $where_sql = !empty($where) ? implode(' AND ', $where) : '1 = 1';

        $stmt_total = $this->db->query(
            "SELECT COUNT(*) AS total
             FROM movimientos_inventario m
             WHERE {$where_sql}",
            $params
        );
        $total_row = $stmt_total ? $stmt_total->fetch(PDO::FETCH_ASSOC) : [];
        $total_movimientos = (int) ($total_row['total'] ?? 0);
        $total_paginas = max(1, (int) ceil($total_movimientos / $por_pagina));

        if ($pagina_actual > $total_paginas) {
            $pagina_actual = $total_paginas;
            $offset = ($pagina_actual - 1) * $por_pagina;
        }

        $stmt_resumen = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN m.tipo_movimiento = 'ENTRADA' THEN 1 ELSE 0 END) AS entradas,
                SUM(CASE WHEN m.tipo_movimiento = 'SALIDA' THEN 1 ELSE 0 END) AS salidas,
                SUM(CASE WHEN m.tipo_movimiento = 'AJUSTE' THEN 1 ELSE 0 END) AS ajustes,
                SUM(CASE WHEN {$manual_sql} THEN 1 ELSE 0 END) AS manuales,
                SUM(CASE WHEN NOT {$manual_sql} THEN 1 ELSE 0 END) AS automaticos,
                SUM(ABS(COALESCE(m.cantidad, 0))) AS unidades
             FROM movimientos_inventario m
             WHERE {$where_sql}",
            $params
        );
        $resumen_movimientos = $stmt_resumen ? $stmt_resumen->fetch(PDO::FETCH_ASSOC) : [];

        $join_producto = 'm.producto_id = p.id';
        if ($movimientos_tienen_hotel && $productos_tienen_hotel) {
            $join_producto .= ' AND p.hotel_id = m.hotel_id';
        }

        $join_habitacion = 'm.habitacion_id = h.id';
        if ($movimientos_tienen_hotel && $habitaciones_tienen_hotel) {
            $join_habitacion .= ' AND h.hotel_id = m.hotel_id';
        }

        $sql = "SELECT
                    m.*,
                    COALESCE(p.nombre, CONCAT('Producto #', m.producto_id)) AS producto_nombre,
                    COALESCE(p.codigo, 'N/A') AS producto_codigo,
                    COALESCE(h.numero, m.habitacion_id) AS habitacion_numero,
                    COALESCE(u.nombre_completo, u.nombre_usuario, CONCAT('Usuario #', m.usuario_id), 'Sistema') AS usuario_nombre,
                    CASE WHEN {$manual_sql} THEN 'manual' ELSE 'automatico' END AS origen_inferido
                FROM movimientos_inventario m
                LEFT JOIN inventario_productos p
                    ON {$join_producto}
                LEFT JOIN habitaciones h
                    ON {$join_habitacion}
                LEFT JOIN usuarios u
                    ON m.usuario_id = u.id
                WHERE {$where_sql}
                ORDER BY IFNULL(m.created_at, NOW()) DESC, m.id DESC
                LIMIT {$por_pagina} OFFSET {$offset}";

        $stmt = $this->db->query($sql, $params);
        $movimientos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        View::renderTemplate('inventario/movimientos', [
            'title' => 'Movimientos de Inventario - ' . current_hotel_display_name(),
            'productos' => $productos,
            'movimientos' => $movimientos,
            'filtros' => $filtros,
            'resumen_movimientos' => $resumen_movimientos ?: [],
            'total_movimientos' => $total_movimientos,
            'total_paginas' => $total_paginas,
            'pagina_actual' => $pagina_actual,
        ]);
    }

    /**
     * Mapear errores de inventario a campos del formulario.
     */
    private function erroresCamposInventarioProducto(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (strpos($lower, 'codigo') !== false) {
                $campo = 'codigo';
            } elseif (strpos($lower, 'nombre') !== false) {
                $campo = 'nombre';
            } elseif (strpos($lower, 'categoria') !== false) {
                $campo = 'categoria_id';
            } elseif (strpos($lower, 'stock minimo') !== false || strpos($lower, 'minimo') !== false) {
                $campo = 'stock_minimo';
            } elseif (strpos($lower, 'stock') !== false) {
                $campo = 'stock_inicial';
            } elseif (strpos($lower, 'costo') !== false) {
                $campo = 'costo_unitario';
            } elseif (strpos($lower, 'unidad') !== false) {
                $campo = 'unidad_medida';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function erroresCamposInventarioMovimiento(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (strpos($lower, 'producto') !== false) {
                $campo = 'producto_id';
            } elseif (strpos($lower, 'habitaci') !== false) {
                $campo = 'habitacion_id';
            } elseif (strpos($lower, 'cantidad') !== false || strpos($lower, 'stock insuficiente') !== false || strpos($lower, 'stock negativo') !== false) {
                $campo = 'cantidad';
            } elseif (strpos($lower, 'seleccionar') !== false || strpos($lower, 'suma') !== false || strpos($lower, 'resta') !== false) {
                $campo = 'tipo';
            } elseif (strpos($lower, 'motivo') !== false) {
                $campo = 'motivo';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    /**
     * Formulario nuevo producto
     */
    public function nuevoAction() {
        $categorias = $this->inventarioModel->getCategorias();

        View::renderTemplate('inventario/nuevo', [
            'title' => 'Nuevo Producto - ' . current_hotel_display_name(),
            'categorias' => $categorias,
            'unidadesMedida' => function_exists('hotel_general_catalog_units') ? hotel_general_catalog_units() : []
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
                'unidad_medida' => $this->unidadMedidaDesdePost('unidad_medida'),
                'descripcion' => trim($this->getPost('descripcion', '')),
                'descuento_automatico' => $this->getPost('descuento_automatico') ? 1 : 0,
                'activo' => 1
            ];

            $data['hotel_id'] = $this->inventarioModel->hotelIdActual();

            if ($data['codigo'] === '') {
                throw new Exception('El codigo del producto es obligatorio');
            }

            if ($this->inventarioModel->codigoExisteEnHotel($data['codigo'])) {
                throw new Exception('Ya existe un producto con ese codigo en este hotel');
            }

            if (!$this->inventarioModel->categoriaPerteneceAlHotel($data['categoria_id'])) {
                throw new Exception('La categoria seleccionada no pertenece al hotel actual');
            }

            $this->db->safeBeginTransaction();

            // Crear el producto
            $producto_id = $this->inventarioModel->create($data);

            if (!$producto_id) {
                throw new Exception('Error al crear el producto');
            }

            // Si hay stock inicial, registrar el movimiento
            if ($data['stock_actual'] > 0) {
                $movimiento_data = [
                    'producto_id' => $producto_id,
                    'tipo_movimiento' => 'ENTRADA',
                    'cantidad' => $data['stock_actual'],
                    'stock_anterior' => 0,
                    'stock_posterior' => $data['stock_actual'],
                    'motivo' => 'Stock inicial al crear producto',
                    'usuario_id' => $this->usuarioIdActualInventario(),
                    'created_at' => date('Y-m-d H:i:s') // AGREGAR ESTA LINEA
                ];

                $this->movimientoModel->crearMovimientoManual($movimiento_data);
            }

            $this->db->safeCommit();
            clear_old_input();
            set_mensaje('Producto creado correctamente', 'success');
        } catch (Exception $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposInventarioProducto([$e->getMessage()]));
            $this->redirect('inventario/nuevo');
            return;
        }

        $this->redirect('inventario');
    }

    /**
     * Vista de entrada de inventario
     */
    public function entradaAction() {
        $productos = $this->inventarioModel->getAllWithCategory();

        View::renderTemplate('inventario/entrada', [
            'title' => 'Entrada de Inventario - ' . current_hotel_display_name(),
            'productos' => $productos
        ]);
    }

    /**
     * Procesar entrada - MÉTODO CORREGIDO
     */
    public function procesarEntradaAction() {
        require_permission('inventarios.all');
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }

        $this->validateCSRF();

        try {
            $producto_id = intval($this->getPost('producto_id'));
            $cantidad = $this->normalizarCantidadMovimientoInventario($this->getPost('cantidad'));
            $motivo = trim($this->getPost('motivo'));

            // Obtener producto actual
            $producto = $this->inventarioModel->getByIdWithCategory($producto_id);

            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            $stock_anterior = round((float)$producto['stock_actual'], 2);
            $stock_nuevo = round($stock_anterior + $cantidad, 2);

            $this->db->safeBeginTransaction();

            // Actualizar stock directamente
            $actualizado = $this->inventarioModel->actualizarProductoBase($producto_id, [
                'stock_actual' => $this->decimalInventario($stock_nuevo)
            ]);

            if ($actualizado) {
                // Registrar movimiento
                $movimiento_data = [
    'producto_id' => $producto_id,
    'tipo_movimiento' => 'ENTRADA',
    'cantidad' => $this->decimalInventario($cantidad),
    'stock_anterior' => $this->decimalInventario($stock_anterior),
    'stock_posterior' => $this->decimalInventario($stock_nuevo),
    'motivo' => $motivo,
    'usuario_id' => $this->usuarioIdActualInventario(),
    'created_at' => date('Y-m-d H:i:s') // AGREGAR ESTA LÍNEA
];

                $this->movimientoModel->crearMovimientoManual($movimiento_data);

                $this->db->safeCommit();
                clear_old_input();
                set_mensaje('Entrada registrada correctamente', 'success');
            } else {
                throw new Exception('No se pudo actualizar el stock');
            }

	        } catch (Exception $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
	            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposInventarioMovimiento([$e->getMessage()]));
            $this->redirect('inventario/entrada');
            return;
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
            'title' => 'Salida de Inventario - ' . current_hotel_display_name(),
            'productos' => $productos,
            'habitaciones' => $habitaciones
        ]);
    }

    /**
     * Procesar salida - MÉTODO CORREGIDO
     */
    public function procesarSalidaAction() {
        require_permission('inventarios.all');
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }

        $this->validateCSRF();

        try {
            $producto_id = intval($this->getPost('producto_id'));
            $cantidad = $this->normalizarCantidadMovimientoInventario($this->getPost('cantidad'));
            $motivo = trim($this->getPost('motivo'));
            $habitacion_id = $this->getPost('habitacion_id') ?: null;

            // Obtener producto actual
            $producto = $this->inventarioModel->getByIdWithCategory($producto_id);

            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            $stock_anterior = round((float)$producto['stock_actual'], 2);

            // Verificar stock suficiente
            if ($stock_anterior < $cantidad) {
                throw new Exception('Stock insuficiente. Disponible: ' . $this->decimalInventario($stock_anterior));
            }

            $stock_nuevo = round($stock_anterior - $cantidad, 2);

            $this->db->safeBeginTransaction();

            // Actualizar stock directamente
            $actualizado = $this->inventarioModel->actualizarProductoBase($producto_id, [
                'stock_actual' => $this->decimalInventario($stock_nuevo)
            ]);

            if ($actualizado) {
                // Registrar movimiento
                $movimiento_data = [
    'producto_id' => $producto_id,
    'tipo_movimiento' => 'SALIDA',
    'cantidad' => $this->decimalInventario($cantidad),
    'stock_anterior' => $this->decimalInventario($stock_anterior),
    'stock_posterior' => $this->decimalInventario($stock_nuevo),
    'motivo' => $motivo,
    'habitacion_id' => $habitacion_id,
    'usuario_id' => $this->usuarioIdActualInventario(),
    'created_at' => date('Y-m-d H:i:s') // AGREGAR ESTA LÍNEA
];

                $this->movimientoModel->crearMovimientoManual($movimiento_data);

                $this->db->safeCommit();
                clear_old_input();
                set_mensaje('Salida registrada correctamente', 'success');
            } else {
                throw new Exception('No se pudo actualizar el stock');
            }

	        } catch (Exception $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
	            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposInventarioMovimiento([$e->getMessage()]));
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
        $configuracion = $this->normalizarConfiguracionInventarioPorTipo(
            $this->inventarioModel->getConfiguracionCompleta()
        );

        $tipos_habitacion = $this->tiposHabitacionInventario();

        View::renderTemplate('inventario/configuracion', [
            'title' => 'Configuración de Descuentos - ' . current_hotel_display_name(),
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
        $config = $this->getPost('config', []);
        $cambiosAuditables = [];

        foreach ($config as $tipo_hab => $productos) {
            $tipo_hab = $this->normalizarTipoHabitacionInventario($tipo_hab);
            if ($tipo_hab === '') {
                throw new Exception('Configuracion invalida para el tipo de habitacion');
            }

            if (!is_array($productos)) {
                throw new Exception('Configuracion invalida para el tipo de habitacion');
            }

            foreach ($productos as $prod_id => $cantidad) {
                $resultado = $this->inventarioModel->actualizarConfiguracion($tipo_hab, $prod_id, $cantidad);
                if (is_array($resultado) && !empty($resultado['changed'])) {
                    $cambiosAuditables[] = $resultado;
                }
            }
        }

        $this->registrarAuditoriaConfiguracionInventario($cambiosAuditables);

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
            'title' => 'Editar Producto - ' . current_hotel_display_name(),
            'producto' => $producto,
            'categorias' => $categorias,
            'unidadesMedida' => function_exists('hotel_general_catalog_units') ? hotel_general_catalog_units() : []
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
                'unidad_medida' => $this->unidadMedidaDesdePost('unidad_medida'),
                'descripcion' => trim($this->getPost('descripcion', '')),
                'descuento_automatico' => $this->getPost('descuento_automatico') ? 1 : 0
            ];

            $producto = $this->inventarioModel->getByIdWithCategory($id);

            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            if ($data['codigo'] === '') {
                throw new Exception('El codigo del producto es obligatorio');
            }

            if ($this->inventarioModel->codigoExisteEnHotel($data['codigo'], $id)) {
                throw new Exception('Ya existe otro producto con ese codigo en este hotel');
            }

            if (!$this->inventarioModel->categoriaPerteneceAlHotel($data['categoria_id'])) {
                throw new Exception('La categoria seleccionada no pertenece al hotel actual');
            }

            if ($this->inventarioModel->actualizarProductoBase($id, $data)) {
                clear_old_input();
                set_mensaje('Producto actualizado correctamente', 'success');
            } else {
                throw new Exception('Error al actualizar el producto');
            }

        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposInventarioProducto([$e->getMessage()]));
            $this->redirect('inventario/editar/' . $id);
            return;
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
            if ($this->inventarioModel->desactivarProductoBase($id)) {
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
        $producto_id = (int)($this->route_params['id'] ?? 0);
        $producto = $producto_id > 0 ? $this->inventarioModel->getByIdWithCategory($producto_id) : null;

        if (!$producto) {
            set_mensaje('Producto no encontrado para el hotel actual', 'error');
            $this->redirect('inventario');
            return;
        }

        View::renderTemplate('inventario/ajuste', [
            'title' => 'Ajuste de Inventario - ' . current_hotel_display_name(),
            'producto' => $producto
        ]);
    }

    /**
     * Procesar ajuste de inventario
     */
    public function procesarAjusteAction() {
        require_permission('inventarios.all');
        $producto_id = (int)($this->route_params['id'] ?? 0);
        $redirectUrl = $producto_id > 0 ? 'inventario/ajuste/' . $producto_id : 'inventario';

        if (!$this->isPost()) {
            $this->redirect($redirectUrl);
            return;
        }

        $this->validateCSRF();

        try {
            if ($producto_id <= 0) {
                throw new Exception('Producto no encontrado');
            }

            $tipo = strtoupper(trim((string)$this->getPost('tipo', '')));
            $cantidadRaw = trim((string)$this->getPost('cantidad', ''));
            $motivo = trim((string)$this->getPost('motivo', ''));
            $observaciones = trim((string)$this->getPost('observaciones', ''));

            // Obtener producto actual
            $producto = $this->inventarioModel->getByIdWithCategory($producto_id);

            if (!$producto) {
                $redirectUrl = 'inventario';
                throw new Exception('Producto no encontrado');
            }

            if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
                throw new Exception('Debe seleccionar si el ajuste suma o resta stock');
            }

            if ($cantidadRaw === '' || !is_numeric($cantidadRaw)) {
                throw new Exception('La cantidad del ajuste debe ser numerica');
            }

            $cantidad = round((float)$cantidadRaw, 2);
            if ($cantidad <= 0) {
                throw new Exception('La cantidad del ajuste debe ser mayor a cero');
            }

            if ($motivo === '') {
                throw new Exception('El motivo del ajuste es obligatorio');
            }

            $stock_anterior = round((float)$producto['stock_actual'], 2);
            $diferencia = $tipo === 'ENTRADA' ? $cantidad : -$cantidad;
            $stock_nuevo = round($stock_anterior + $diferencia, 2);

            if ($stock_nuevo < 0) {
                throw new Exception('El ajuste dejaria stock negativo. Stock disponible: ' . number_format($stock_anterior, 2));
            }

            $this->db->safeBeginTransaction();

            // Actualizar stock
            $actualizado = $this->inventarioModel->actualizarProductoBase($producto_id, [
                'stock_actual' => $stock_nuevo
            ]);

            if (!$actualizado) {
                throw new Exception('No se pudo actualizar el stock');
            }

            $motivoMovimiento = 'Ajuste manual: ' . $motivo;
            if ($observaciones !== '') {
                $motivoMovimiento .= ' | Obs: ' . $observaciones;
            }

            // Registrar movimiento de ajuste en la tabla moderna.
            $movimiento_data = [
                'producto_id' => $producto_id,
                'tipo_movimiento' => 'AJUSTE',
                'cantidad' => abs($diferencia),
                'stock_anterior' => $stock_anterior,
                'stock_posterior' => $stock_nuevo,
                'motivo' => $this->limitarTextoInventario($motivoMovimiento, 255),
                'usuario_id' => $this->usuarioIdActualInventario(),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $movimiento_id = $this->movimientoModel->crearMovimientoManual($movimiento_data);
            if (!$movimiento_id) {
                throw new Exception('No se pudo registrar el movimiento de inventario');
            }

            AuditService::record('inventario.ajuste_stock', [
                'hotel_id' => obtenerHotelIdActualCompat(),
                'usuario_id' => $this->usuarioIdActualInventario(),
                'entidad_tipo' => 'inventario_producto',
                'entidad_id' => (string)$producto_id,
                'descripcion' => 'Ajuste manual de stock',
                'datos_antes' => [
                    'stock_actual' => $stock_anterior,
                ],
                'datos_despues' => [
                    'stock_actual' => $stock_nuevo,
                    'diferencia' => $diferencia,
                    'tipo_ajuste' => $tipo,
                    'cantidad' => $cantidad,
                    'motivo' => $motivo,
                    'observaciones' => $observaciones !== '' ? $observaciones : null,
                    'movimiento_id' => (int)$movimiento_id,
                ],
            ]);

            $this->db->safeCommit();
            clear_old_input();
            set_mensaje('Ajuste realizado correctamente', 'success');
            $this->redirect('inventario/movimientos');
            return;
	        } catch (Exception $e) {
            if ($this->db->enTransaccion()) {
                $this->db->safeRollBack();
            }
	            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposInventarioMovimiento([$e->getMessage()]));
	        }

        $this->redirect($redirectUrl);
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
            'title' => 'Reporte de Inventario - ' . current_hotel_display_name(),
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
            'title' => 'Historial de ' . $producto['nombre'] . ' - ' . current_hotel_display_name(),
            'producto' => $producto,
            'movimientos' => $movimientos
        ]);
    }
}
