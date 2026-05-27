<?php
/**
 * Vista del Dashboard Principal - Versión Rediseñada
 * Los Cedros
 */

// Verificar que tengamos los datos necesarios
$stats = $stats ?? [];
$caja_info = $caja_info ?? null;
$corte_actual = $corte_actual ?? null;
$reservaciones_hoy = $reservaciones_hoy ?? [];
$proximas_llegadas = $proximas_llegadas ?? [];
$proximas_salidas = $proximas_salidas ?? [];
$graficos = $graficos ?? [];

// Debug temporal
echo "<!-- DEBUG EGRESOS: ";
echo "Total: " . ($stats['egresos']['total_dia'] ?? 'NO DEFINIDO') . " | ";
echo "Efectivo: " . ($stats['egresos']['efectivo_dia'] ?? 'NO DEFINIDO') . " | ";
echo "Tarjeta: " . ($stats['egresos']['tarjeta_dia'] ?? 'NO DEFINIDO') . " | ";
echo "Transfer: " . ($stats['egresos']['transferencia_dia'] ?? 'NO DEFINIDO');
echo " -->";

// Obtener estadísticas de estacionamiento - Solo coches
function get_vehiculos_activos_hoy() {
    try {
        $db = Database::getInstance();
        $hotel_id = obtenerHotelIdActualCompat();
        
        $sql = "
            SELECT 
                hv.estacionamiento,
                COUNT(DISTINCT hv.id) as total
            FROM huesped_vehiculos hv
            INNER JOIN reservaciones r ON hv.huesped_id = r.huesped_id
            WHERE r.hotel_id = ?
            AND r.estado = 'checked_in'
            AND r.fecha_entrada <= CURDATE()
            AND r.fecha_salida >= CURDATE()
            AND hv.activo = 1
            AND hv.estacionamiento = 'coches'
            GROUP BY hv.estacionamiento
        ";
        
        $stmt = $db->query($sql, [$hotel_id]);
        if (!$stmt) {
            return ['coches' => 0];
        }
        
        $resultados = $stmt->fetchAll();
        $conteo = ['coches' => 0];
        
        foreach ($resultados as $resultado) {
            if ($resultado['estacionamiento'] === 'coches') {
                $conteo['coches'] = intval($resultado['total']);
            }
        }
        
        return $conteo;
    } catch (Exception $e) {
        error_log("Error obteniendo vehículos activos: " . $e->getMessage());
        return ['coches' => 0];
    }
}

$estadisticas_estacionamiento = get_vehiculos_activos_hoy();
$limite_coches = 30;

// Lista detallada de vehículos en estacionamiento ahora mismo
function get_lista_vehiculos_estacionamiento() {
    try {
        $db = Database::getInstance();
        $hotel_id = obtenerHotelIdActualCompat();
        $sql = "
            SELECT
                h.nombre_completo AS huesped,
                GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones,
                hv.marca,
                hv.modelo,
                hv.color,
                hv.placas
            FROM huesped_vehiculos hv
            INNER JOIN reservaciones r        ON hv.huesped_id = r.huesped_id
            INNER JOIN huespedes h             ON h.id = hv.huesped_id
            LEFT  JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
            LEFT  JOIN habitaciones hab        ON hab.id = rh.habitacion_id AND hab.hotel_id = r.hotel_id
            WHERE r.hotel_id = ?
              AND r.estado = 'checked_in'
              AND r.fecha_entrada <= CURDATE()
              AND r.fecha_salida  >= CURDATE()
              AND hv.activo = 1
            GROUP BY hv.id, h.id, hv.marca, hv.modelo, hv.color, hv.placas
            ORDER BY h.nombre_completo
        ";
        $stmt = $db->query($sql, [$hotel_id]);
        if (!$stmt) {
            error_log('DEBUG VEHICULOS: query() devolvió false');
            return [];
        }
        $rows = $stmt->fetchAll() ?: [];
        error_log('DEBUG VEHICULOS: filas obtenidas = ' . count($rows));
        return $rows;
    } catch (Exception $e) {
        error_log('ERROR lista vehiculos estacionamiento: ' . $e->getMessage());
        echo "<!-- ERROR_VEHICULOS: " . htmlspecialchars($e->getMessage()) . " -->";
        return [];
    }
}
$lista_vehiculos_estacionamiento = get_lista_vehiculos_estacionamiento();

function get_vehiculos_por_habitacion() {
    try {
        $db = Database::getInstance();
        $hotel_id = obtenerHotelIdActualCompat();
        $sql = "
            SELECT
                hab.numero            AS habitacion,
                h.nombre_completo     AS huesped,
                hv.marca,
                hv.modelo,
                hv.color,
                hv.placas,
                hv.estacionamiento
            FROM huesped_vehiculos hv
            INNER JOIN reservaciones r             ON hv.huesped_id = r.huesped_id
            INNER JOIN huespedes h                  ON h.id = hv.huesped_id
            LEFT  JOIN reservacion_habitaciones rh  ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
            LEFT  JOIN habitaciones hab             ON hab.id = rh.habitacion_id AND hab.hotel_id = r.hotel_id
            WHERE r.hotel_id = ?
              AND r.estado = 'checked_in'
              AND r.fecha_entrada <= CURDATE()
              AND r.fecha_salida  >= CURDATE()
              AND hv.activo = 1
            ORDER BY hab.numero, hv.id
        ";
        $stmt = $db->query($sql, [$hotel_id]);
        if (!$stmt) return [];
        $rows = $stmt->fetchAll() ?: [];

        $grouped = [];
        foreach ($rows as $row) {
            $hab = $row['habitacion'] ?? 'Sin hab.';
            if (!isset($grouped[$hab])) {
                $grouped[$hab] = [
                    'habitacion' => $hab,
                    'huesped'    => $row['huesped'],
                    'vehiculos'  => []
                ];
            }
            $grouped[$hab]['vehiculos'][] = [
                'marca'           => $row['marca'],
                'modelo'          => $row['modelo'],
                'color'           => $row['color'],
                'placas'          => $row['placas'],
                'estacionamiento' => $row['estacionamiento'],
            ];
        }
        return array_values($grouped);
    } catch (Exception $e) {
        error_log('Error vehiculos por habitacion: ' . $e->getMessage());
        return [];
    }
}
$vehiculos_por_habitacion = get_vehiculos_por_habitacion();
?>

<style>
:root {
    --sage: #9CA777;
    --sage-dark: #7A8B5C;
    --sage-light: #C5D4A4;
    --sage-bg: #F4F7EF;
    --sage-border: #D4DFC4;
}

.dashboard-sn {
    min-height: 100vh;
    background: #F5F5F0;
}

/* Header */
.dash-header {
    background: white;
    border-bottom: 3px solid var(--sage);
    box-shadow: 0 2px 12px rgba(122, 139, 92, 0.1);
}

.dash-header-inner {
    max-width: 1600px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
}

/* Cards base */
.dash-card {
    background: white;
    border-radius: 1rem;
    border: 1px solid #E8E8E3;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.25s ease;
    overflow: hidden;
}

.dash-card:hover {
    box-shadow: 0 6px 20px rgba(122, 139, 92, 0.12);
    transform: translateY(-2px);
}

.dash-card-header {
    padding: 0.875rem 1.25rem;
    border-bottom: 2px solid #F0F0EB;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dash-card-body {
    padding: 1.25rem;
}

/* Stat widgets */
.stat-widget {
    background: white;
    border-radius: 1rem;
    border: 1px solid #E8E8E3;
    padding: 1.25rem;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}

.stat-widget::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity 0.3s;
}

.stat-widget:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    transform: translateY(-2px);
}

.stat-widget:hover::after {
    opacity: 1;
}

.stat-widget.stat-ocupacion::after { background: linear-gradient(90deg, var(--sage), var(--sage-dark)); }
.stat-widget.stat-ingresos::after { background: linear-gradient(90deg, #10B981, #059669); }
.stat-widget.stat-movimientos::after { background: linear-gradient(90deg, #8B5CF6, #7C3AED); }
.stat-widget.stat-habitaciones::after { background: linear-gradient(90deg, #F59E0B, #D97706); }

.stat-icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
}

/* Quick actions */
.quick-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.625rem;
    font-size: 0.8125rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.quick-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.quick-btn-primary {
    background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dark) 100%);
    color: white;
}

.quick-btn-secondary {
    background: white;
    color: var(--sage-dark);
    border: 2px solid var(--sage-border);
}

.quick-btn-secondary:hover {
    background: var(--sage-bg);
    border-color: var(--sage);
}

.quick-btn-caja {
    background: linear-gradient(135deg, #10B981, #059669);
    color: white;
}

.quick-btn-alert {
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: white;
    animation: pulse-soft 2s infinite;
}

@keyframes pulse-soft {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.85; }
}

/* Header stats bar */
.stats-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
    align-items: center;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
    border-top: 1px solid #F0F0EB;
}

.stats-bar-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stats-bar-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

/* Alert banner */
.alert-caja {
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    border: 2px solid #FDE68A;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Tables */
.dash-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.dash-table thead th {
    font-size: 0.6875rem;
    font-weight: 700;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.5rem 0.75rem;
    text-align: left;
    border-bottom: 2px solid #F0F0EB;
}

.dash-table tbody tr {
    transition: background 0.15s;
}

.dash-table tbody tr:hover {
    background: var(--sage-bg);
}

.dash-table tbody td {
    padding: 0.625rem 0.75rem;
    font-size: 0.8125rem;
    border-bottom: 1px solid #F5F5F0;
}

/* Parking card */
.parking-card {
    background: linear-gradient(135deg, var(--sage-bg) 0%, #EDF2E4 100%);
    border: 2px solid var(--sage-border);
    border-radius: 0.75rem;
    padding: 1rem;
}

.parking-bar-bg {
    height: 10px;
    background: rgba(122, 139, 92, 0.2);
    border-radius: 5px;
    overflow: hidden;
}

.parking-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--sage), var(--sage-dark));
    border-radius: 5px;
    transition: width 0.5s ease;
}

/* Caja summary */
.caja-item {
    border-radius: 0.75rem;
    padding: 0.75rem;
    text-align: center;
}

/* Badge */
.dash-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 700;
}

/* Progress bar */
.progress-bar {
    height: 6px;
    background: #E5E7EB;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* Responsive */
@media (max-width: 1024px) {
    .grid-4-cols { grid-template-columns: repeat(2, 1fr) !important; }
    .grid-3-cols { grid-template-columns: 1fr !important; }
}

@media (max-width: 640px) {
    .grid-4-cols { grid-template-columns: 1fr !important; }
    .dash-card-body { padding: 0.875rem; }
    .stat-widget { padding: 0.875rem; }
    .hide-mobile { display: none !important; }
}

@media (max-width: 375px) {
    .grid-4-cols { grid-template-columns: 1fr !important; }
}

/* Scrollbar */
.overflow-x-auto::-webkit-scrollbar { height: 4px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f3f4f6; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: var(--sage-border); border-radius: 2px; }

/* No overflow */
body { overflow-x: hidden; }
</style>

<div class="dashboard-sn">
    <!-- ====== HEADER ====== -->
    <div class="dash-header">
        <div class="dash-header-inner">
            <!-- Top row -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <!-- Logo & title -->
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: var(--sage-bg); padding: 0.625rem; border-radius: 0.75rem; border: 2px solid var(--sage-border);">
                        <i class="fas fa-hotel" style="color: var(--sage-dark); font-size: 1.125rem;"></i>
                    </div>
                    <div>
                        <h1 style="font-size: 1.25rem; font-weight: 700; color: #1F2937; margin: 0;">Dashboard</h1>
                        <p style="font-size: 0.75rem; color: #6B7280; margin: 0;">
                            <?= format_date(date('Y-m-d'), 'l, d \d\e F') ?> · <?= date('H:i') ?>
                        </p>
                    </div>
                </div>
                
                <!-- Quick actions -->
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="/reservaciones/crear" class="quick-btn quick-btn-primary">
                        <i class="fas fa-plus"></i>
                        <span class="hide-mobile">Nueva</span> Reserva
                    </a>
                    
                    <?php if ($corte_actual): ?>
                        <a href="/caja" class="quick-btn quick-btn-caja">
                            <i class="fas fa-cash-register"></i>
                            <span class="hide-mobile">Caja</span>
                        </a>
                    <?php else: ?>
                        <a href="/caja" class="quick-btn quick-btn-alert">
                            <i class="fas fa-lock"></i>
                            Abrir Caja
                        </a>
                    <?php endif; ?>
                    
                    <a href="/facturacion" class="quick-btn quick-btn-secondary">
                        <i class="fas fa-file-invoice"></i>
                        <span class="hide-mobile">Facturación</span>
                    </a>
                </div>
            </div>
            
            <!-- Stats bar -->
            <div class="stats-bar">
                <div class="stats-bar-item">
                    <span class="stats-bar-dot" style="background: var(--sage);"></span>
                    <span style="font-size: 0.75rem; color: #6B7280;">Ocupación:</span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #1F2937;">
                        <?= $stats['habitaciones']['porcentaje_ocupacion'] ?? 0 ?>%
                    </span>
                </div>
                <div class="stats-bar-item">
                    <span class="stats-bar-dot" style="background: #10B981;"></span>
                    <span style="font-size: 0.75rem; color: #6B7280;">Ingresos hoy:</span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #1F2937;">
                        <?= format_money($stats['ingresos']['total_dia'] ?? 0) ?>
                    </span>
                </div>
                <div class="stats-bar-item">
                    <span class="stats-bar-dot" style="background: #8B5CF6;"></span>
                    <span style="font-size: 0.75rem; color: #6B7280;">Check-ins:</span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #1F2937;">
                        <?= $stats['entradas']['total'] ?? 0 ?>
                    </span>
                </div>
                <div class="stats-bar-item">
                    <span class="stats-bar-dot" style="background: #F59E0B;"></span>
                    <span style="font-size: 0.75rem; color: #6B7280;">Check-outs:</span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #1F2937;">
                        <?= $stats['salidas']['total'] ?? 0 ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== CONTENT ====== -->
<div style="max-width: 1600px; margin: 0 auto; padding: 1.25rem 1.5rem;">        
        <!-- Alerta caja cerrada -->
        <?php if (!$corte_actual): ?>
        <div class="alert-caja">
            <i class="fas fa-exclamation-triangle" style="color: #D97706; font-size: 1rem;"></i>
            <p style="font-size: 0.8125rem; color: #92400E; margin: 0;">
                <strong>Atención:</strong> La caja está cerrada. Realice la apertura para registrar movimientos.
            </p>
        </div>
        <?php endif; ?>

        <!-- ====== STAT WIDGETS ====== -->
        <div class="grid-4-cols" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem;">
            
            <!-- Widget: Ocupación -->
            <div class="stat-widget stat-ocupacion">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div class="stat-icon" style="background: var(--sage-bg); color: var(--sage-dark);">
                        <i class="fas fa-bed"></i>
                    </div>
                    <span style="font-size: 1.5rem; font-weight: 800; color: var(--sage-dark);">
                        <?= $stats['habitaciones']['porcentaje_ocupacion'] ?? 0 ?>%
                    </span>
                </div>
                <p style="font-size: 0.6875rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.25rem;">Ocupación</p>
                <div style="display: flex; align-items: baseline; gap: 0.25rem;">
                    <span style="font-size: 1.125rem; font-weight: 700; color: #1F2937;">
                        <?= $stats['habitaciones']['ocupadas'] ?? 0 ?>
                    </span>
                    <span style="font-size: 0.75rem; color: #9CA3AF;">/<?= $stats['habitaciones']['total'] ?? 0 ?></span>
                </div>
                <div class="progress-bar" style="margin-top: 0.5rem;">
                    <div class="progress-fill" style="width: <?= $stats['habitaciones']['porcentaje_ocupacion'] ?? 0 ?>%; background: linear-gradient(90deg, var(--sage), var(--sage-dark));"></div>
                </div>
            </div>

            <!-- Widget: Ingresos y Egresos -->
            <div class="stat-widget stat-ingresos">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div class="stat-icon" style="background: #ECFDF5; color: #059669;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <span style="font-size: 1.25rem; font-weight: 800; color: #059669;">
                        <?= format_money($stats['ingresos']['total_dia'] ?? 0) ?>
                    </span>
                </div>
                <p style="font-size: 0.6875rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.5rem;">Movimientos del Día</p>
                
                <!-- Ingresos -->
                <div style="margin-bottom: 0.375rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span style="font-size: 0.6875rem; font-weight: 700; color: #059669;"><i class="fas fa-arrow-up" style="font-size: 0.6rem; margin-right: 0.125rem;"></i>INGRESOS</span>
                        <span style="font-size: 0.6875rem; font-weight: 700; color: #059669;"><?= format_money($stats['ingresos']['total_dia'] ?? 0) ?></span>
                    </div>
                    <div style="padding-left: 0.5rem; display: flex; flex-direction: column; gap: 0.125rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.6875rem;">
                            <span style="color: #6B7280;"><i class="fas fa-money-bill-wave" style="color: #10B981; font-size: 0.6rem; margin-right: 0.25rem;"></i>Efectivo</span>
                            <span style="font-weight: 600; color: #374151;"><?= format_money($stats['ingresos']['efectivo_dia'] ?? 0) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.6875rem;">
                            <span style="color: #6B7280;"><i class="fas fa-credit-card" style="color: #3B82F6; font-size: 0.6rem; margin-right: 0.25rem;"></i>Tarjeta</span>
                            <span style="font-weight: 600; color: #374151;"><?= format_money($stats['ingresos']['tarjeta_dia'] ?? 0) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.6875rem;">
                            <span style="color: #6B7280;"><i class="fas fa-exchange-alt" style="color: #8B5CF6; font-size: 0.6rem; margin-right: 0.25rem;"></i>Transfer.</span>
                            <span style="font-weight: 600; color: #374151;"><?= format_money($stats['ingresos']['transferencia_dia'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
                
                <div style="border-top: 1px dashed #E5E7EB; margin: 0.375rem 0;"></div>
                
                <!-- Egresos -->
                <div style="margin-bottom: 0.375rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span style="font-size: 0.6875rem; font-weight: 700; color: #DC2626;"><i class="fas fa-arrow-down" style="font-size: 0.6rem; margin-right: 0.125rem;"></i>EGRESOS</span>
                        <span style="font-size: 0.6875rem; font-weight: 700; color: #DC2626;"><?= format_money($stats['egresos']['total_dia'] ?? 0) ?></span>
                    </div>
                    <div style="padding-left: 0.5rem; display: flex; flex-direction: column; gap: 0.125rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.6875rem;">
                            <span style="color: #6B7280;"><i class="fas fa-money-bill-wave" style="color: #EF4444; font-size: 0.6rem; margin-right: 0.25rem;"></i>Efectivo</span>
                            <span style="font-weight: 600; color: #374151;"><?= format_money($stats['egresos']['efectivo_dia'] ?? 0) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.6875rem;">
                            <span style="color: #6B7280;"><i class="fas fa-credit-card" style="color: #EF4444; font-size: 0.6rem; margin-right: 0.25rem;"></i>Tarjeta</span>
                            <span style="font-weight: 600; color: #374151;"><?= format_money($stats['egresos']['tarjeta_dia'] ?? 0) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.6875rem;">
                            <span style="color: #6B7280;"><i class="fas fa-exchange-alt" style="color: #EF4444; font-size: 0.6rem; margin-right: 0.25rem;"></i>Transfer.</span>
                            <span style="font-weight: 600; color: #374151;"><?= format_money($stats['egresos']['transferencia_dia'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
                
                <div style="border-top: 2px solid #E5E7EB; margin-top: 0.375rem; padding-top: 0.375rem;">
                    <?php 
                    $balance = ($stats['ingresos']['total_dia'] ?? 0) - ($stats['egresos']['total_dia'] ?? 0);
                    $color_bal = $balance >= 0 ? '#059669' : '#DC2626';
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.6875rem; font-weight: 700; color: #374151;">BALANCE</span>
                        <span style="font-size: 0.875rem; font-weight: 800; color: <?= $color_bal ?>;"><?= format_money($balance) ?></span>
                    </div>
                </div>
            </div>

            <!-- Widget: Check-ins/outs -->
            <div class="stat-widget stat-movimientos">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div class="stat-icon" style="background: #EDE9FE; color: #7C3AED;">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>
                <p style="font-size: 0.6875rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.75rem;">Movimientos</p>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <!-- Entradas -->
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 6px; height: 6px; background: var(--sage); border-radius: 50%; display: inline-block;"></span>
                            <span style="font-size: 0.8125rem; color: #4B5563;">Entradas</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.375rem;">
                            <span style="font-size: 1.125rem; font-weight: 700; color: #1F2937;"><?= $stats['entradas']['total'] ?? 0 ?></span>
                            <?php if (($stats['entradas']['pendientes'] ?? 0) > 0): ?>
                                <span class="dash-badge" style="background: var(--sage-bg); color: var(--sage-dark); border: 1px solid var(--sage-border);">
                                    <?= $stats['entradas']['pendientes'] ?> pend.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Salidas -->
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 6px; height: 6px; background: #F59E0B; border-radius: 50%; display: inline-block;"></span>
                            <span style="font-size: 0.8125rem; color: #4B5563;">Salidas</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.375rem;">
                            <span style="font-size: 1.125rem; font-weight: 700; color: #1F2937;"><?= $stats['salidas']['total'] ?? 0 ?></span>
                            <?php if (($stats['salidas']['pendientes'] ?? 0) > 0): ?>
                                <span class="dash-badge" style="background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;">
                                    <?= $stats['salidas']['pendientes'] ?> pend.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widget: Estado Habitaciones -->
            <div class="stat-widget stat-habitaciones">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div class="stat-icon" style="background: #FEF3C7; color: #D97706;">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <span style="font-size: 1.5rem; font-weight: 800; color: #D97706;">
                        <?= $stats['habitaciones']['total'] ?? 0 ?>
                    </span>
                </div>
                <p style="font-size: 0.6875rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.5rem;">Habitaciones</p>
                
                <div style="display: flex; flex-direction: column; gap: 0.375rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                        <span style="color: #4B5563;"><i class="fas fa-circle" style="color: var(--sage); font-size: 0.5rem; margin-right: 0.375rem;"></i>Disponibles</span>
                        <span style="font-weight: 700; color: #1F2937;"><?= $stats['habitaciones']['disponibles_reales'] ?? $stats['habitaciones']['disponibles'] ?? 0 ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                        <span style="color: #4B5563;"><i class="fas fa-circle" style="color: #8B5CF6; font-size: 0.5rem; margin-right: 0.375rem;"></i>Por llegar</span>
                        <span style="font-weight: 700; color: #1F2937;"><?= $stats['habitaciones']['por_llegar'] ?? 0 ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                        <span style="color: #4B5563;"><i class="fas fa-circle" style="color: #EF4444; font-size: 0.5rem; margin-right: 0.375rem;"></i>Ocupadas</span>
                        <span style="font-weight: 700; color: #1F2937;"><?= $stats['habitaciones']['ocupadas'] ?? 0 ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                        <span style="color: #4B5563;"><i class="fas fa-circle" style="color: #F59E0B; font-size: 0.5rem; margin-right: 0.375rem;"></i>Mantenimiento</span>
                        <span style="font-weight: 700; color: #1F2937;"><?= $stats['habitaciones']['mantenimiento'] ?? 0 ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                        <span style="color: #4B5563;"><i class="fas fa-circle" style="color: #3B82F6; font-size: 0.5rem; margin-right: 0.375rem;"></i>Limpieza</span>
                        <span style="font-weight: 700; color: #1F2937;"><?= $stats['habitaciones']['limpieza'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== SEGUNDA FILA: Gráfica + Estacionamiento ====== -->
        <div class="grid-3-cols" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            
            <!-- Gráfica de Ocupación Semanal -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-chart-bar" style="color: var(--sage-dark);"></i>
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937; margin: 0;">Ocupación Semanal</h3>
                    </div>
                    <div style="display: flex; gap: 1rem; font-size: 0.6875rem;" class="hide-mobile">
                        <div style="display: flex; align-items: center; gap: 0.25rem;">
                            <span style="width: 10px; height: 10px; background: var(--sage); border-radius: 2px; display: inline-block;"></span>
                            <span style="color: #6B7280;">Ocupadas</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.25rem;">
                            <span style="width: 10px; height: 10px; background: #E5E7EB; border-radius: 2px; display: inline-block;"></span>
                            <span style="color: #6B7280;">Disponibles</span>
                        </div>
                    </div>
                </div>
                <div class="dash-card-body">
                    <div style="position: relative; height: 220px; min-height: 200px;">
                        <canvas id="chartOcupacion"></canvas>
                    </div>
                </div>
            </div>

            <!-- Estacionamiento - Solo Coches -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-parking" style="color: var(--sage-dark);"></i>
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937; margin: 0;">Estacionamiento</h3>
                    </div>
                    <span style="font-size: 0.6875rem; color: #6B7280; font-weight: 600;">
                        <?= $estadisticas_estacionamiento['coches'] ?? 0 ?> vehículos
                    </span>
                </div>
                <div class="dash-card-body">
                    <?php 
                    $coches = $estadisticas_estacionamiento['coches'] ?? 0;
                    $pct_coches = ($coches / $limite_coches) * 100;
                    $espacios_disp = $limite_coches - $coches;
                    ?>
                    
                    <!-- Visual principal -->
                    <div style="text-align: center; padding: 1.5rem 0;">
                        <div style="position: relative; display: inline-block;">
                            <svg width="140" height="140" viewBox="0 0 140 140">
                                <!-- Background circle -->
                                <circle cx="70" cy="70" r="55" fill="none" stroke="#E8E8E3" stroke-width="12"/>
                                <!-- Progress circle -->
                                <circle cx="70" cy="70" r="55" fill="none" 
                                        stroke="url(#sageGrad)" stroke-width="12"
                                        stroke-linecap="round"
                                        stroke-dasharray="<?= 2 * M_PI * 55 ?>"
                                        stroke-dashoffset="<?= 2 * M_PI * 55 * (1 - $pct_coches / 100) ?>"
                                        transform="rotate(-90 70 70)"/>
                                <defs>
                                    <linearGradient id="sageGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color: var(--sage)"/>
                                        <stop offset="100%" style="stop-color: var(--sage-dark)"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                <span style="font-size: 1.75rem; font-weight: 800; color: var(--sage-dark);"><?= $coches ?></span>
                                <span style="display: block; font-size: 0.6875rem; color: #6B7280;">de <?= $limite_coches ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle -->
                    <div class="parking-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-car" style="color: var(--sage-dark); font-size: 1rem;"></i>
                                <span style="font-size: 0.875rem; font-weight: 700; color: #374151;">Coches</span>
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--sage-dark);">
                                <?= round($pct_coches) ?>%
                            </span>
                        </div>
                        <div class="parking-bar-bg">
                            <div class="parking-bar-fill" style="width: <?= min($pct_coches, 100) ?>%;"></div>
                        </div>
                        <p style="font-size: 0.75rem; color: #6B7280; margin-top: 0.5rem; text-align: center;">
                            <i class="fas fa-check-circle" style="color: var(--sage); margin-right: 0.25rem;"></i>
                            <strong><?= $espacios_disp ?></strong> espacios disponibles
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== LISTA VEHICULOS ESTACIONAMIENTO ====== -->
        <?php if (!empty($lista_vehiculos_estacionamiento)): ?>
        <div class="dash-card" style="margin-bottom: 1.25rem;">
            <div class="dash-card-header">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-car" style="color:var(--sage-dark);"></i>
                    <h3 style="font-size:0.9375rem; font-weight:700; color:#1F2937; margin:0;">
                        Vehículos en Estacionamiento
                    </h3>
                </div>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <span class="dash-badge" style="background:var(--sage-bg); color:var(--sage-dark); border:1px solid var(--sage-border);">
                        <?= count($lista_vehiculos_estacionamiento) ?>
                    </span>
                    <!-- Toggle de vista -->
                    <div style="display:flex; border:1px solid var(--sage-border); border-radius:0.5rem; overflow:hidden;">
                        <button onclick="switchVehTab('vehiculo')" id="btn-tab-vehiculo"
                            style="padding:0.25rem 0.625rem; font-size:0.75rem; font-weight:600; border:none; cursor:pointer;
                                   background:var(--sage); color:white; transition:all 0.2s;">
                            <i class="fas fa-car" style="margin-right:0.25rem;"></i>Por vehículo
                        </button>
                        <button onclick="switchVehTab('habitacion')" id="btn-tab-habitacion"
                            style="padding:0.25rem 0.625rem; font-size:0.75rem; font-weight:600; border:none; cursor:pointer;
                                   background:white; color:var(--sage-dark); transition:all 0.2s;">
                            <i class="fas fa-door-open" style="margin-right:0.25rem;"></i>Por habitación
                        </button>
                    </div>
                </div>
            </div>
            <div class="dash-card-body" style="padding:0; overflow-x:auto;">

                <!-- ── VISTA: Por Vehículo ── -->
                <div id="view-vehiculo">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Huésped</th>
                                <th style="text-align:center;">Hab.</th>
                                <th>Vehículo</th>
                                <th style="text-align:center;">Color</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_vehiculos_estacionamiento as $v): ?>
                            <?php
                                $colores_bg = [
                                    'blanco'  => ['#F9FAFB','#374151'],
                                    'negro'   => ['#1F2937','#F9FAFB'],
                                    'gris'    => ['#6B7280','#FFFFFF'],
                                    'plata'   => ['#D1D5DB','#374151'],
                                    'rojo'    => ['#EF4444','#FFFFFF'],
                                    'azul'    => ['#3B82F6','#FFFFFF'],
                                    'verde'   => ['#10B981','#FFFFFF'],
                                    'amarillo'=> ['#F59E0B','#1F2937'],
                                    'café'    => ['#92400E','#FFFFFF'],
                                    'naranja' => ['#F97316','#FFFFFF'],
                                    'morado'  => ['#8B5CF6','#FFFFFF'],
                                ];
                                $color_key = strtolower(trim($v['color'] ?? ''));
                                [$pill_bg, $pill_text] = $colores_bg[$color_key] ?? ['#E5E7EB','#374151'];
                            ?>
                            <tr>
                                <td>
                                    <span style="font-weight:600; color:#1F2937; white-space:nowrap;
                                                 overflow:hidden; text-overflow:ellipsis; max-width:140px; display:block;">
                                        <?= htmlspecialchars($v['huesped']) ?>
                                    </span>
                                </td>
                                <td style="text-align:center; font-weight:700; color:var(--sage-dark);">
                                    <?= htmlspecialchars($v['habitaciones'] ?? '-') ?>
                                </td>
                                <td>
                                    <span style="font-weight:600; color:#1F2937;">
                                        <?= htmlspecialchars(trim($v['marca'] . ' ' . $v['modelo'])) ?>
                                    </span>
                                    <?php if (!empty($v['placas'])): ?>
                                    <span style="display:block; font-size:0.65rem; color:#9CA3AF; font-family:monospace;">
                                        <?= htmlspecialchars($v['placas']) ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <span style="display:inline-block; padding:0.2rem 0.6rem;
                                                 border-radius:9999px; font-size:0.7rem; font-weight:600;
                                                 background:<?= $pill_bg ?>; color:<?= $pill_text ?>;
                                                 border:1px solid rgba(0,0,0,0.08); white-space:nowrap;">
                                        <?= htmlspecialchars(ucfirst($v['color'] ?? '-')) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ── VISTA: Por Habitación ── -->
                <div id="view-habitacion" style="display:none;">
                    <?php if (empty($vehiculos_por_habitacion)): ?>
                        <div style="text-align:center; padding:2rem;">
                            <p style="color:#9CA3AF; font-size:0.875rem;">Sin vehículos registrados.</p>
                        </div>
                    <?php else: ?>
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th style="text-align:center;">Hab.</th>
                                <th>Huésped</th>
                                <th>Vehículo(s)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $colores_bg2 = [
                                'blanco'  => ['#F9FAFB','#374151'],
                                'negro'   => ['#1F2937','#F9FAFB'],
                                'gris'    => ['#6B7280','#FFFFFF'],
                                'plata'   => ['#D1D5DB','#374151'],
                                'rojo'    => ['#EF4444','#FFFFFF'],
                                'azul'    => ['#3B82F6','#FFFFFF'],
                                'verde'   => ['#10B981','#FFFFFF'],
                                'amarillo'=> ['#F59E0B','#1F2937'],
                                'café'    => ['#92400E','#FFFFFF'],
                                'naranja' => ['#F97316','#FFFFFF'],
                                'morado'  => ['#8B5CF6','#FFFFFF'],
                            ];
                            foreach ($vehiculos_por_habitacion as $hab_row):
                            ?>
                            <tr>
                                <td style="text-align:center; vertical-align:top; padding-top:0.75rem;">
                                    <span style="display:inline-block; background:var(--sage-bg); color:var(--sage-dark);
                                                 border:2px solid var(--sage-border); border-radius:0.5rem;
                                                 padding:0.25rem 0.6rem; font-weight:800; font-size:0.875rem; white-space:nowrap;">
                                        <?= htmlspecialchars($hab_row['habitacion']) ?>
                                    </span>
                                </td>
                                <td style="vertical-align:top; padding-top:0.75rem;">
                                    <span style="font-weight:600; color:#374151; font-size:0.8125rem;">
                                        <?= htmlspecialchars($hab_row['huesped']) ?>
                                    </span>
                                </td>
                                <td style="vertical-align:top; padding-top:0.6rem;">
                                    <div style="display:flex; flex-direction:column; gap:0.375rem;">
                                    <?php foreach ($hab_row['vehiculos'] as $veh):
                                        $ck = strtolower(trim($veh['color'] ?? ''));
                                        [$pbg, $ptx] = $colores_bg2[$ck] ?? ['#E5E7EB','#374151'];
                                    ?>
                                        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                            <i class="fas fa-car" style="color:var(--sage); font-size:0.75rem;"></i>
                                            <span style="font-weight:600; color:#1F2937; font-size:0.8125rem;">
                                                <?= htmlspecialchars(trim($veh['marca'] . ' ' . $veh['modelo'])) ?>
                                            </span>
                                            <?php if (!empty($veh['placas'])): ?>
                                            <span style="font-family:monospace; font-size:0.7rem; color:#6B7280;
                                                         background:#F3F4F6; padding:0.1rem 0.4rem; border-radius:0.25rem;">
                                                <?= htmlspecialchars($veh['placas']) ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if (!empty($veh['color'])): ?>
                                            <span style="display:inline-block; padding:0.1rem 0.45rem;
                                                         border-radius:9999px; font-size:0.65rem; font-weight:600;
                                                         background:<?= $pbg ?>; color:<?= $ptx ?>;
                                                         border:1px solid rgba(0,0,0,0.08);">
                                                <?= htmlspecialchars(ucfirst($veh['color'])) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <script>
        function switchVehTab(tab) {
            const isVeh = tab === 'vehiculo';
            document.getElementById('view-vehiculo').style.display   = isVeh ? '' : 'none';
            document.getElementById('view-habitacion').style.display = isVeh ? 'none' : '';
            const btnVeh = document.getElementById('btn-tab-vehiculo');
            const btnHab = document.getElementById('btn-tab-habitacion');
            btnVeh.style.background = isVeh ? 'var(--sage)' : 'white';
            btnVeh.style.color      = isVeh ? 'white'       : 'var(--sage-dark)';
            btnHab.style.background = isVeh ? 'white'       : 'var(--sage)';
            btnHab.style.color      = isVeh ? 'var(--sage-dark)' : 'white';
        }
        </script>

        <!-- ====== TERCERA FILA: Llegadas y Salidas ====== -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;" class="grid-4-cols">
            
            <!-- Próximas Llegadas -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-sign-in-alt" style="color: var(--sage-dark);"></i>
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937; margin: 0;">Próximas Llegadas</h3>
                    </div>
                    <span class="dash-badge" style="background: var(--sage-bg); color: var(--sage-dark); border: 1px solid var(--sage-border);">
                        <?= count($proximas_llegadas) ?>
                    </span>
                </div>
                <div class="dash-card-body" style="padding: 0;">
                    <?php if (empty($proximas_llegadas)): ?>
                        <div style="text-align: center; padding: 2.5rem 1rem;">
                            <i class="fas fa-calendar-check" style="font-size: 2rem; color: #D1D5DB; margin-bottom: 0.5rem;"></i>
                            <p style="font-size: 0.875rem; color: #9CA3AF; margin: 0;">Sin llegadas para hoy</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Hora</th>
                                        <th>Huésped</th>
                                        <th style="text-align: center;">Hab</th>
                                        <th style="text-align: center;"><i class="fas fa-car" style="color: #9CA3AF;"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($proximas_llegadas, 0, 5) as $llegada): ?>
                                    <?php
                                    $tiene_vehiculo = false;
                                    if (isset($llegada['huesped_id'])) {
                                        try {
                                            $db = Database::getInstance();
                                            $hotel_id = obtenerHotelIdActualCompat();
                                            $stmt = $db->query("
                                                SELECT COUNT(DISTINCT hv.id) as total
                                                FROM huesped_vehiculos hv
                                                INNER JOIN reservaciones r ON hv.huesped_id = r.huesped_id
                                                WHERE hv.huesped_id = ?
                                                AND r.id = ?
                                                AND r.hotel_id = ?
                                                AND hv.activo = 1
                                            ", [$llegada['huesped_id'], $llegada['id'], $hotel_id]);
                                            if ($stmt) {
                                                $result = $stmt->fetch();
                                                $tiene_vehiculo = $result && $result['total'] > 0;
                                            }
                                        } catch (Exception $e) { $tiene_vehiculo = false; }
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight: 600; color: #1F2937;">
                                                <?= date('H:i', strtotime($llegada['hora_llegada_estimada'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <p style="font-weight: 600; color: #1F2937; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">
                                                    <?= htmlspecialchars($llegada['huesped_nombre']) ?>
                                                </p>
                                                <p style="font-size: 0.6875rem; color: #9CA3AF; margin: 0;">
                                                    <?= htmlspecialchars($llegada['procedencia']) ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td style="text-align: center; font-weight: 600;">
                                            <?= htmlspecialchars($llegada['habitaciones']) ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($tiene_vehiculo): ?>
                                                <i class="fas fa-check-circle" style="color: var(--sage);"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle" style="color: #D1D5DB;"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Próximas Salidas -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-sign-out-alt" style="color: #D97706;"></i>
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937; margin: 0;">Próximas Salidas</h3>
                    </div>
                    <span class="dash-badge" style="background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;">
                        <?= count($proximas_salidas) ?>
                    </span>
                </div>
                <div class="dash-card-body" style="padding: 0;">
                    <?php if (empty($proximas_salidas)): ?>
                        <div style="text-align: center; padding: 2.5rem 1rem;">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; color: #D1D5DB; margin-bottom: 0.5rem;"></i>
                            <p style="font-size: 0.875rem; color: #9CA3AF; margin: 0;">Sin salidas para hoy</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Huésped</th>
                                        <th style="text-align: center;">Hab</th>
                                        <th style="text-align: center;">Estado</th>
                                        <th style="text-align: center;"><i class="fas fa-key" style="color: #9CA3AF;"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($proximas_salidas, 0, 5) as $salida): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <p style="font-weight: 600; color: #1F2937; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">
                                                    <?= htmlspecialchars($salida['huesped_nombre']) ?>
                                                </p>
                                                <p style="font-size: 0.6875rem; color: #9CA3AF; margin: 0;">
                                                    <?= date('H:i', strtotime($salida['hora_entrada'])) ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td style="text-align: center; font-weight: 600;">
                                            <?= htmlspecialchars($salida['habitaciones']) ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($salida['hora_salida']): ?>
                                                <span class="dash-badge" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">
                                                    <i class="fas fa-check" style="font-size: 0.6rem;"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="dash-badge" style="background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;">
                                                    <i class="fas fa-clock" style="font-size: 0.6rem;"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if (isset($salida['llave_entregada']) && !$salida['llave_entregada']): ?>
                                                <i class="fas fa-key" style="color: #F59E0B;"></i>
                                            <?php else: ?>
                                                <i class="fas fa-check-circle" style="color: var(--sage);"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ====== ESTADO DE CAJA ====== -->
        <?php if ($caja_info): ?>
        <div class="dash-card">
            <div class="dash-card-header">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-cash-register" style="color: var(--sage-dark);"></i>
                    <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937; margin: 0;">Estado de Caja</h3>
                </div>
                <span style="font-size: 0.75rem; color: #6B7280;">
                    Desde: <?= date('H:i', strtotime($caja_info['fecha_apertura'])) ?>
                </span>
            </div>
            <div class="dash-card-body">
                <div class="grid-4-cols" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem;">
                    <div class="caja-item" style="background: #F9FAFB; border: 2px solid #E5E7EB;">
                        <p style="font-size: 0.6875rem; color: #6B7280; margin: 0 0 0.25rem; font-weight: 600;">Inicial</p>
                        <p style="font-size: 1rem; font-weight: 700; color: #374151; margin: 0;">
                            <?= format_money($caja_info['monto_inicial']) ?>
                        </p>
                    </div>
                    <div class="caja-item" style="background: var(--sage-bg); border: 2px solid var(--sage-border);">
                        <p style="font-size: 0.6875rem; color: #6B7280; margin: 0 0 0.25rem; font-weight: 600;">Ingresos</p>
                        <p style="font-size: 1rem; font-weight: 700; color: var(--sage-dark); margin: 0;">
                            +<?= format_money($caja_info['total_ingresos']) ?>
                        </p>
                    </div>
                    <div class="caja-item" style="background: #FEF2F2; border: 2px solid #FECACA;">
                        <p style="font-size: 0.6875rem; color: #6B7280; margin: 0 0 0.25rem; font-weight: 600;">Gastos</p>
                        <p style="font-size: 1rem; font-weight: 700; color: #DC2626; margin: 0;">
                            -<?= format_money($caja_info['total_gastos']) ?>
                        </p>
                    </div>
                    <div class="caja-item" style="background: #EFF6FF; border: 2px solid #BFDBFE;">
                        <p style="font-size: 0.6875rem; color: #6B7280; margin: 0 0 0.25rem; font-weight: 600;">Esperado</p>
                        <p style="font-size: 1rem; font-weight: 700; color: #2563EB; margin: 0;">
                            <?= format_money($caja_info['efectivo_esperado']) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts para las gráficas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    Chart.defaults.font.size = window.innerWidth < 640 ? 10 : 12;
    
    // Gráfica de Ocupación Semanal
    const ctxOcupacion = document.getElementById('chartOcupacion').getContext('2d');
    const dataOcupacion = <?= json_encode($graficos['ocupacion_semanal'] ?? []) ?>;
    
    new Chart(ctxOcupacion, {
        type: 'bar',
        data: {
            labels: dataOcupacion.map(d => window.innerWidth < 640 ? d.dia.substring(0, 3) : d.dia),
            datasets: [
                {
                    label: 'Ocupadas',
                    data: dataOcupacion.map(d => d.ocupadas),
                    backgroundColor: 'rgba(156, 167, 119, 0.85)',
                    borderColor: '#7A8B5C',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Disponibles',
                    data: dataOcupacion.map(d => d.disponibles),
                    backgroundColor: 'rgba(229, 231, 235, 0.7)',
                    borderColor: '#D1D5DB',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        font: { size: window.innerWidth < 640 ? 9 : 11 }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    max: <?= max(1, (int)($stats['habitaciones']['total'] ?? 0)) ?>,
                    ticks: {
                        stepSize: 7,
                        font: { size: window.innerWidth < 640 ? 9 : 11 }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.04)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: window.innerWidth >= 640,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        padding: 10,
                        font: { size: 11 },
                        usePointStyle: true,
                        pointStyle: 'rectRounded'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.95)',
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 11 },
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.dataset.label + ': ' + context.raw + ' hab.';
                        }
                    }
                }
            }
        }
    });
    
    // Resize handler
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            Chart.defaults.font.size = window.innerWidth < 640 ? 10 : 12;
        }, 250);
    });
});
</script>
