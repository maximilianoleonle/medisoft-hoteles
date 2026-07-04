<!-- Vista Detallada de Incremento de Tarifa -->
<style>
:root {
    --primary: var(--brand-primary, #1B2746);
    --primary-dark: var(--brand-secondary, #0F172A);
    --primary-light: color-mix(in srgb, var(--brand-primary, #1B2746) 68%, #FFFFFF);
    --accent: var(--brand-accent, #BD9441);
    --brand-elevated-shadow: color-mix(in srgb, var(--brand-primary, #1B2746) 22%, transparent);
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
}

/* Contenedor principal */
.detail-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Header Card */
.header-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.header-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transform: rotate(45deg);
}

.header-content {
    position: relative;
    z-index: 1;
}

.header-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 12px;
}

.header-subtitle {
    font-size: 1.125rem;
    opacity: 0.9;
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 24px;
    height: 100%;
    transition: all 0.3s ease;
}

.info-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.info-card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.info-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.info-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
}

.info-item {
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 4px;
}

.info-value {
    font-size: 1rem;
    color: #1e293b;
    font-weight: 500;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-badge.active {
    background: #dcfce7;
    color: #166534;
}

.status-badge.inactive {
    background: #f3f4f6;
    color: #6b7280;
}

.status-badge.current {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.future {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.past {
    background: #f3f4f6;
    color: #6b7280;
}

/* Value Display */
.value-display {
    background: #f0fdf4;
    border: 2px solid #86efac;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}

.value-number {
    font-size: 2rem;
    font-weight: 700;
    color: #166534;
}

.value-label {
    font-size: 0.875rem;
    color: #15803d;
    margin-top: 4px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px var(--brand-elevated-shadow);
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

.btn-danger {
    background: #fee2e2;
    color: #dc2626;
}

.btn-danger:hover {
    background: #fecaca;
}

.btn-danger.is-confirming {
    background: #fecaca;
    box-shadow: inset 0 0 0 1px rgba(220, 38, 38, .24);
}

/* Tabs */
.tabs {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 24px;
}

.tab {
    padding: 12px 24px;
    font-weight: 600;
    color: #64748b;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
}

.tab:hover {
    color: #1e293b;
}

.tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

/* Habitaciones Grid */
.habitaciones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
}

.habitacion-item {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    transition: all 0.2s;
}

.habitacion-item.selected {
    background: #fef3c7;
    border-color: #f59e0b;
}

.habitacion-numero {
    font-weight: 600;
    color: #1e293b;
}

.habitacion-precio {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 4px;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
}

.timeline-item {
    position: relative;
    padding-bottom: 24px;
}

.timeline-dot {
    position: absolute;
    left: -28px;
    top: 4px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: white;
    border: 3px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-dot.active {
    border-color: var(--primary);
    background: var(--primary);
    color: white;
}

.timeline-content {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
}

/* Preview Table */
.preview-table {
    width: 100%;
    border-collapse: collapse;
}

.preview-table th {
    background: #f8fafc;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    font-size: 0.875rem;
    border-bottom: 2px solid #e2e8f0;
}

.preview-table td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
}

.preview-table tr:hover {
    background: #f8fafc;
}

.price-original {
    color: #6b7280;
    text-decoration: line-through;
    font-size: 0.875rem;
}

.price-final {
    color: #059669;
    font-weight: 600;
}

/* Alert Box */
.alert {
    padding: 16px;
    border-radius: 12px;
    display: flex;
    align-items: start;
    gap: 12px;
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
}

.alert-icon {
    flex-shrink: 0;
    font-size: 1.25rem;
}
</style>

<div class="min-h-screen bg-gray-50 py-4">
    <div class="detail-container px-4">
        <!-- Header Card -->
        <div class="header-card">
            <div class="header-content">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="header-title"><?= htmlspecialchars($incremento['nombre']) ?></h1>
                        <p class="header-subtitle">
                            <?= htmlspecialchars($incremento['descripcion'] ?: 'Sin descripción') ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="status-badge <?= $incremento['activo'] ? 'active' : 'inactive' ?>">
                            <i class="fas fa-<?= $incremento['activo'] ? 'check' : 'times' ?>-circle"></i>
                            <?= $incremento['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                        <?php
                        $hoy = date('Y-m-d');
                        if (!$incremento['es_permanente']) {
                            if ($incremento['fecha_inicio'] > $hoy) {
                                echo '<span class="status-badge future"><i class="fas fa-clock"></i> Futuro</span>';
                            } elseif ($incremento['fecha_fin'] && $incremento['fecha_fin'] < $hoy) {
                                echo '<span class="status-badge past"><i class="fas fa-history"></i> Finalizado</span>';
                            } else {
                                echo '<span class="status-badge current"><i class="fas fa-calendar-check"></i> Vigente</span>';
                            }
                        } else {
                            if ($incremento['fecha_inicio'] <= $hoy) {
                                echo '<span class="status-badge current"><i class="fas fa-infinity"></i> Permanente</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center mb-6">
            <?php $back_arrow_href = back_url('configuracion/tarifas'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('configuracion/tarifas') ?>" class="btn btn-secondary ms-back-legacy">
                <i class="fas fa-arrow-left"></i>
                Volver al listado
            </a>
            <div class="action-buttons">
                <button onclick="previsualizarPrecios()" class="btn btn-secondary">
                    <i class="fas fa-calculator"></i>
                    Calcular Precios
                </button>
                <a href="<?= url('configuracion/tarifas/editar/' . $incremento['id']) ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i>
                    Editar
                </a>
                <?php if (!($incremento['activo'] && $estado_fecha == 'vigente')): ?>
                <button onclick="eliminarIncremento(this, <?= (int) $incremento['id'] ?>)" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                    Eliminar
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Información Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Card de Valor -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon bg-green-100 text-green-600">
                        <i class="fas fa-<?= $incremento['tipo_incremento'] == 'porcentaje' ? 'percentage' : 'dollar-sign' ?>"></i>
                    </div>
                    <h3 class="info-card-title">Valor del Incremento</h3>
                </div>
                <div class="value-display">
                    <div class="value-number">
                        <?= $incremento['tipo_incremento'] == 'porcentaje' ? '+' : '$' ?>
                        <?= number_format($incremento['valor_incremento'], 2) ?>
                        <?= $incremento['tipo_incremento'] == 'porcentaje' ? '%' : '' ?>
                    </div>
                    <div class="value-label">
                        <?= $incremento['tipo_incremento'] == 'porcentaje' ? 'Incremento porcentual' : 'Incremento fijo' ?>
                    </div>
                </div>
            </div>

            <!-- Card de Vigencia -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="info-card-title">Período de Vigencia</h3>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha de inicio</div>
                    <div class="info-value"><?= format_date($incremento['fecha_inicio'], 'd/m/Y') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha de fin</div>
                    <div class="info-value">
                        <?= $incremento['es_permanente'] 
                            ? '<span class="text-blue-600"><i class="fas fa-infinity mr-1"></i> Sin fecha de fin</span>' 
                            : format_date($incremento['fecha_fin'], 'd/m/Y') ?>
                    </div>
                </div>
                <?php if (!$incremento['es_permanente']): ?>
                    <?php 
                    $dias_restantes = (strtotime($incremento['fecha_fin']) - strtotime(date('Y-m-d'))) / 86400;
                    ?>
                    <div class="info-item">
                        <div class="info-label">Duración</div>
                        <div class="info-value">
                            <?php if ($dias_restantes > 0): ?>
                                <?= $dias_restantes ?> días restantes
                            <?php else: ?>
                                Finalizado hace <?= abs($dias_restantes) ?> días
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card de Alcance -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon bg-purple-100 text-purple-600">
                        <i class="fas fa-<?= 
                            $incremento['alcance'] == 'global' ? 'hotel' : 
                            ($incremento['alcance'] == 'tipo_habitacion' ? 'bed' : 'door-open') 
                        ?>"></i>
                    </div>
                    <h3 class="info-card-title">Alcance de Aplicación</h3>
                </div>
                <div class="info-item">
                    <div class="info-label">Tipo de alcance</div>
                    <div class="info-value">
                        <?php
                        switch($incremento['alcance']) {
                            case 'global':
                                echo 'Todas las habitaciones';
                                break;
                            case 'tipo_habitacion':
                                echo 'Por tipo de habitación';
                                break;
                            case 'habitacion':
                                echo 'Habitaciones específicas';
                                break;
                        }
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Aplicado a</div>
                    <div class="info-value">
                        <?php
                        switch($incremento['alcance']) {
                            case 'global':
                                echo '<span class="text-purple-600 font-semibold">66 habitaciones</span>';
                                break;
                            case 'tipo_habitacion':
                                $tipos = json_decode($incremento['tipos_habitacion'], true) ?: [];
                                echo '<span class="text-purple-600 font-semibold">' . count($tipos) . ' tipos</span>';
                                break;
                            case 'habitacion':
                                $habs = json_decode($incremento['habitaciones'], true) ?: [];
                                echo '<span class="text-purple-600 font-semibold">' . count($habs) . ' habitaciones</span>';
                                break;
                        }
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Prioridad</div>
                    <div class="info-value">
                        <span class="inline-flex items-center justify-center w-8 h-8 text-xs font-bold rounded-full bg-indigo-100 text-indigo-800">
                            <?= $incremento['prioridad'] ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs de Contenido -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="tabs">
                <div class="tab active" onclick="showTab('detalles')">
                    <i class="fas fa-info-circle mr-2"></i>
                    Detalles
                </div>
                <div class="tab" onclick="showTab('aplicacion')">
                    <i class="fas fa-list mr-2"></i>
                    Aplicación
                </div>
                <div class="tab" onclick="showTab('preview')">
                    <i class="fas fa-eye mr-2"></i>
                    Vista Previa
                </div>
                <div class="tab" onclick="showTab('historial')">
                    <i class="fas fa-history mr-2"></i>
                    Historial
                </div>
            </div>

            <div class="p-6">
                <!-- Tab Detalles -->
                <div id="tab-detalles" class="tab-content">
                    <?php if ($incremento['alcance'] == 'tipo_habitacion'): ?>
                        <h4 class="font-semibold text-gray-900 mb-4">Tipos de habitación incluidos:</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <?php 
                            $tipos = json_decode($incremento['tipos_habitacion'], true) ?: [];
                            foreach ($tipos as $tipo): 
                            ?>
                                <div class="bg-purple-50 border border-purple-200 rounded-lg px-4 py-3">
                                    <i class="fas fa-bed text-purple-600 mr-2"></i>
                                    <span class="font-medium"><?= get_tipo_habitacion($tipo) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($incremento['alcance'] == 'habitacion'): ?>
                        <h4 class="font-semibold text-gray-900 mb-4">Habitaciones específicas:</h4>
                        <div class="habitaciones-grid">
                            <?php 
                            $habitaciones_ids = json_decode($incremento['habitaciones'], true) ?: [];
                            // Aquí deberías obtener la información de las habitaciones desde la base de datos
                            foreach ($habitaciones_ids as $hab_id): 
                            ?>
                                <div class="habitacion-item selected">
                                    <div class="habitacion-numero">Hab. <?= $hab_id ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle alert-icon"></i>
                            <div>
                                <p class="font-semibold">Aplicación Global</p>
                                <p class="text-sm mt-1">Este incremento se aplica a todas las habitaciones del hotel sin excepción.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-900 mb-4">Información adicional:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Creado por</p>
                                <p class="font-medium"><?= htmlspecialchars($incremento['usuario_nombre'] ?? 'Usuario desconocido') ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Fecha de creación</p>
                                <p class="font-medium"><?= format_date($incremento['created_at'], 'd/m/Y H:i') ?></p>
                            </div>
                            <?php if ($incremento['updated_at'] && $incremento['updated_at'] != $incremento['created_at']): ?>
                            <div>
                                <p class="text-sm text-gray-600">Última modificación</p>
                                <p class="font-medium"><?= format_date($incremento['updated_at'], 'd/m/Y H:i') ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tab Aplicación -->
                <div id="tab-aplicacion" class="tab-content" style="display: none;">
                    <h4 class="font-semibold text-gray-900 mb-4">Cálculo del incremento:</h4>
                    
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <?php if ($incremento['tipo_incremento'] == 'porcentaje'): ?>
                            <p class="text-lg mb-4">
                                <strong>Fórmula:</strong> Precio Final = Precio Base + (Precio Base × <?= number_format($incremento['valor_incremento'], 2) ?>%)
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Si el precio base es</p>
                                    <p class="text-2xl font-bold text-gray-900">$1,000</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">El incremento será</p>
                                    <p class="text-2xl font-bold text-green-600">+$<?= number_format(1000 * $incremento['valor_incremento'] / 100, 2) ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Precio final</p>
                                    <p class="text-2xl font-bold text-blue-600">$<?= number_format(1000 + (1000 * $incremento['valor_incremento'] / 100), 2) ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-lg mb-4">
                                <strong>Fórmula:</strong> Precio Final = Precio Base + $<?= number_format($incremento['valor_incremento'], 2) ?>
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Si el precio base es</p>
                                    <p class="text-2xl font-bold text-gray-900">$1,000</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">El incremento será</p>
                                    <p class="text-2xl font-bold text-green-600">+$<?= number_format($incremento['valor_incremento'], 2) ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Precio final</p>
                                    <p class="text-2xl font-bold text-blue-600">$<?= number_format(1000 + $incremento['valor_incremento'], 2) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle alert-icon"></i>
                        <div>
                            <p class="font-semibold">Nota importante</p>
                            <p class="text-sm mt-1">Los incrementos se aplican en orden de prioridad. Si hay múltiples incrementos activos para una misma habitación, se aplicarán todos según su prioridad.</p>
                        </div>
                    </div>
                </div>

                <!-- Tab Vista Previa -->
                <div id="tab-preview" class="tab-content" style="display: none;">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar fecha para previsualizar
                        </label>
                        <input type="date" 
                               id="fechaPreview" 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                               value="<?= date('Y-m-d') ?>"
                               onchange="cargarPreview()">
                    </div>
                    <div id="previewContent">
                        <p class="text-center text-gray-500 py-8">
                            Seleccione una fecha para ver cómo se aplicará el incremento
                        </p>
                    </div>
                </div>

                <!-- Tab Historial -->
                <div id="tab-historial" class="tab-content" style="display: none;">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot active">
                                <i class="fas fa-plus text-xs"></i>
                            </div>
                            <div class="timeline-content">
                                <p class="font-semibold">Incremento creado</p>
                                <p class="text-sm text-gray-600">
                                    <?= format_date($incremento['created_at'], 'd/m/Y H:i') ?> por 
                                    <?= htmlspecialchars($incremento['usuario_nombre'] ?? 'Usuario desconocido') ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($incremento['updated_at'] && $incremento['updated_at'] != $incremento['created_at']): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot">
                                <i class="fas fa-edit text-xs"></i>
                            </div>
                            <div class="timeline-content">
                                <p class="font-semibold">Incremento modificado</p>
                                <p class="text-sm text-gray-600">
                                    <?= format_date($incremento['updated_at'], 'd/m/Y H:i') ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($incremento['fecha_inicio'] <= date('Y-m-d')): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot active">
                                <i class="fas fa-play text-xs"></i>
                            </div>
                            <div class="timeline-content">
                                <p class="font-semibold">Incremento iniciado</p>
                                <p class="text-sm text-gray-600">
                                    <?= format_date($incremento['fecha_inicio'], 'd/m/Y') ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$incremento['es_permanente'] && $incremento['fecha_fin'] <= date('Y-m-d')): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot">
                                <i class="fas fa-stop text-xs"></i>
                            </div>
                            <div class="timeline-content">
                                <p class="font-semibold">Incremento finalizado</p>
                                <p class="text-sm text-gray-600">
                                    <?= format_date($incremento['fecha_fin'], 'd/m/Y') ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para previsualización de precios -->
<div class="modal fade" id="modalPrecios" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-xl border-0">
            <div class="modal-header border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-calculator text-indigo-600 mr-2"></i>
                    Previsualización de Precios
                </h5>
                <button type="button" class="close text-gray-400 hover:text-gray-600" data-dismiss="modal">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
            <div class="modal-body p-6">
                <div id="resultadoPrecios">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tabs functionality
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).style.display = 'block';
    
    // Add active class to clicked tab
    event.currentTarget.classList.add('active');
}

// Eliminar incremento
async function eliminarIncremento(trigger, id) {
    const ok = await msConfirm({
        type: 'error',
        icon: 'trash',
        title: '¿Eliminar incremento?',
        msg: 'Esta acción eliminará permanentemente el incremento. No se puede deshacer.',
        confirmLabel: 'Sí, eliminar'
    });
    if (!ok) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url("configuracion/tarifas/eliminar") ?>';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= csrf_token() ?>';
    form.appendChild(csrfInput);

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = id;
    form.appendChild(idInput);

    document.body.appendChild(form);
    form.submit();
}

// Previsualizar precios
function previsualizarPrecios() {
    $('#modalPrecios').modal('show');
    cargarPreciosModal();
}

function cargarPreciosModal() {
    const fecha = new Date().toISOString().split('T')[0];
    
    $('#resultadoPrecios').html(
        '<div class="text-center py-12">' +
        '<div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">' +
        '<i class="fas fa-spinner fa-spin text-indigo-600 text-2xl"></i>' +
        '</div>' +
        '<p class="text-gray-500">Calculando precios con incremento...</p>' +
        '</div>'
    );
    
    // Simulación de carga - aquí deberías hacer la llamada AJAX real
    setTimeout(() => {
        mostrarResultadosPrecios();
    }, 1000);
}

function mostrarResultadosPrecios() {
    // Esta función debería recibir los datos reales del servidor
    let html = '<h6 class="font-semibold text-gray-900 mb-4">Aplicación del incremento:</h6>';
    
    html += '<div class="alert alert-info mb-4">';
    html += '<i class="fas fa-info-circle alert-icon"></i>';
    html += '<div>';
    html += '<p>Incremento: <strong><?= htmlspecialchars($incremento['nombre']) ?></strong></p>';
    html += '<p class="text-sm mt-1">Valor: ';
    <?php if ($incremento['tipo_incremento'] == 'porcentaje'): ?>
        html += '<strong>+<?= number_format($incremento['valor_incremento'], 2) ?>%</strong>';
    <?php else: ?>
        html += '<strong>+$<?= number_format($incremento['valor_incremento'], 2) ?></strong>';
    <?php endif; ?>
    html += '</p>';
    html += '</div>';
    html += '</div>';
    
    html += '<table class="preview-table">';
    html += '<thead>';
    html += '<tr>';
    html += '<th>Habitación</th>';
    html += '<th>Tipo</th>';
    html += '<th>Precio Base</th>';
    html += '<th>Incremento</th>';
    html += '<th>Precio Final</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';
    
    // Ejemplos de habitaciones (esto debería venir del servidor)
    const ejemplos = [
        { numero: 101, tipo: 'Sencilla', precio: 800 },
        { numero: 201, tipo: 'Doble', precio: 1200 },
        { numero: 301, tipo: 'Triple', precio: 1500 }
    ];
    
    ejemplos.forEach(hab => {
        let incremento = 0;
        <?php if ($incremento['tipo_incremento'] == 'porcentaje'): ?>
            incremento = hab.precio * <?= $incremento['valor_incremento'] ?> / 100;
        <?php else: ?>
            incremento = <?= $incremento['valor_incremento'] ?>;
        <?php endif; ?>
        
        const precioFinal = hab.precio + incremento;
        
        html += '<tr>';
        html += '<td>Habitación ' + hab.numero + '</td>';
        html += '<td>' + hab.tipo + '</td>';
        html += '<td>$' + hab.precio.toFixed(2) + '</td>';
        html += '<td class="text-green-600">+$' + incremento.toFixed(2) + '</td>';
        html += '<td class="price-final">$' + precioFinal.toFixed(2) + '</td>';
        html += '</tr>';
    });
    
    html += '</tbody>';
    html += '</table>';
    
    $('#resultadoPrecios').html(html);
}

// Preview en tab
function cargarPreview() {
    const fecha = document.getElementById('fechaPreview').value;
    
    document.getElementById('previewContent').innerHTML = 
        '<div class="text-center py-8">' +
        '<i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>' +
        '<p class="text-gray-500 mt-2">Cargando vista previa...</p>' +
        '</div>';
    
    // Simulación - aquí harías la llamada AJAX real
    setTimeout(() => {
        let html = '<div class="alert alert-info mb-4">';
        html += '<i class="fas fa-calendar-alt alert-icon"></i>';
        html += '<div>';
        html += '<p>Vista previa para: <strong>' + new Date(fecha).toLocaleDateString('es-MX', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        }) + '</strong></p>';
        html += '</div>';
        html += '</div>';
        
        // Aquí agregarías el contenido real basado en la fecha
        html += '<p class="text-gray-600">El incremento <strong><?= htmlspecialchars($incremento['nombre']) ?></strong> ';
        
        const fechaInicio = new Date('<?= $incremento['fecha_inicio'] ?>');
        const fechaFin = <?= $incremento['fecha_fin'] ? "new Date('{$incremento['fecha_fin']}')" : 'null' ?>;
        const fechaSeleccionada = new Date(fecha);
        
        if (fechaSeleccionada < fechaInicio) {
            html += 'aún no estará vigente en esta fecha.</p>';
        } else if (fechaFin && fechaSeleccionada > fechaFin) {
            html += 'ya habrá finalizado en esta fecha.</p>';
        } else {
            html += 'estará vigente y se aplicará a las reservaciones.</p>';
        }
        
        document.getElementById('previewContent').innerHTML = html;
    }, 500);
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips si los hay
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
