<!-- Crear Incremento de Tarifa - Diseño Simplificado y Claro -->
<style>
:root {
    --primary: #6B4423;
    --primary-dark: #5A3A1E;
    --primary-light: #8B5A3A;
    --accent: #D4A574;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
}

/* Contenedor principal */
.tarifa-container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Cards de sección */
.section-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.section-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.section-header {
    background: #f8fafc;
    padding: 20px 24px;
    border-bottom: 2px solid #e2e8f0;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-number {
    width: 32px;
    height: 32px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.section-body {
    padding: 24px;
}

/* Inputs mejorados */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.form-help {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 4px;
}

.form-input {
    width: 100%;
    padding: 10px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(107, 68, 35, 0.1);
}

/* Opciones de selección visual */
.option-cards {
    display: grid;
    gap: 16px;
    margin-top: 12px;
}

.option-card {
    position: relative;
    padding: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    background: #fcfcfc;
}

.option-card:hover {
    border-color: var(--accent);
    background: #fffbf0;
    transform: translateY(-2px);
}

.option-card.selected {
    border-color: var(--primary);
    background: #fef3e7;
    box-shadow: 0 0 0 3px rgba(107, 68, 35, 0.1);
}

.option-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.option-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 12px;
}

.option-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.option-desc {
    font-size: 0.875rem;
    color: #64748b;
}

/* Toggle switch mejorado */
.toggle-container {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 20px;
}

.toggle-switch {
    position: relative;
    width: 48px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: .4s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: var(--success);
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* Preview card */
.preview-card {
    background: #f0f9ff;
    border: 2px solid #bae6fd;
    border-radius: 12px;
    padding: 20px;
    margin-top: 24px;
}

.preview-title {
    font-weight: 600;
    color: #0369a1;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Botones */
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
    box-shadow: 0 4px 12px rgba(107, 68, 35, 0.2);
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

/* Estados de validación */
.is-invalid {
    border-color: var(--danger);
}

.invalid-feedback {
    color: var(--danger);
    font-size: 0.75rem;
    margin-top: 4px;
}

/* Lista de selección mejorada */
.selection-list {
    max-height: 300px;
    overflow-y: auto;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0;
}

.selection-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: background 0.2s;
}

.selection-item:hover {
    background: #f8fafc;
}

.selection-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.selection-item label {
    flex: 1;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<div class="min-h-screen bg-gray-50 py-4">
    <!-- Header Simplificado -->
    <div class="tarifa-container px-4">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Crear Incremento de Tarifa</h1>
                <p class="text-gray-600 mt-1">Configure incrementos de precio para temporadas o eventos especiales</p>
            </div>
            <a href="<?= url('configuracion/tarifas') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Regresar
            </a>
        </div>
    </div>

    <div class="tarifa-container px-4">
        <!-- SECCIÓN: Precios Actuales con Tarifas Vigentes -->
        <div class="section-card mb-6" style="border-left: 4px solid #3b82f6;">
            <div class="section-header" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
                <div class="flex justify-between items-center">
                    <h2 class="section-title">
                        <span style="width: 32px; height: 32px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                            <i class="fas fa-tags"></i>
                        </span>
                        Precios Actuales (con tarifas vigentes)
                    </h2>
                    <button type="button" onclick="togglePreciosActuales()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-chevron-down" id="iconTogglePreciosActuales"></i>
                        <span id="textTogglePreciosActuales">Mostrar</span>
                    </button>
                </div>
            </div>
            <div class="section-body" id="seccionPreciosActuales" style="display: none;">
                <p class="text-sm text-gray-600 mb-4">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                    Estos son los precios actuales de las habitaciones considerando las tarifas vigentes. El nuevo incremento se sumará sobre estos precios.
                </p>
                
                <?php
                // Calcular precios actuales con tarifas vigentes
                $tarifaModel = new IncrementoTarifa();
                $hoy = date('Y-m-d');
                $incrementosVigentes = $tarifaModel->getVigentes();
                ?>
                
                <?php if (!empty($incrementosVigentes)): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-amber-800 font-medium mb-2">
                        <i class="fas fa-layer-group mr-1"></i>
                        Tarifas vigentes actualmente (<?= count($incrementosVigentes) ?>):
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($incrementosVigentes as $inc): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            <?= htmlspecialchars($inc['nombre']) ?>
                            (<?= $inc['tipo_incremento'] == 'porcentaje' ? $inc['valor_incremento'] . '%' : '$' . number_format($inc['valor_incremento'], 0) ?>)
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        No hay tarifas vigentes. Los precios mostrados son los precios base.
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Habitación</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tipo</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Precio Base</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Incremento</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Precio Actual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($habitaciones as $hab): 
                                $calculo = $tarifaModel->calcularPrecioConIncremento(
                                    $hab['id'],
                                    $hab['tipo'],
                                    $hab['precio_base'],
                                    $hoy
                                );
                                $tieneIncremento = $calculo['incremento_total'] > 0;
                            ?>
                            <tr class="hover:bg-gray-50 <?= $tieneIncremento ? 'bg-green-50' : '' ?>">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900"><?= $hab['numero'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <?= get_tipo_habitacion($hab['tipo']) ?>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    <?= format_currency($calculo['precio_base']) ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <?php if ($tieneIncremento): ?>
                                    <span class="text-green-600 font-medium">
                                        +<?= format_currency($calculo['incremento_total']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold <?= $tieneIncremento ? 'text-green-700' : 'text-gray-900' ?>">
                                        <?= format_currency($calculo['precio_final']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($incrementosVigentes)): ?>
                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-xs text-blue-700">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Tip:</strong> El nuevo incremento que crees se sumará a estos precios actuales. 
                        Por ejemplo, si agregas +10% a una habitación que ya tiene un precio de $1,100, el nuevo precio será $1,210.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <form action="<?= url('configuracion/tarifas/crear') ?>" method="POST" id="formIncremento">
            <?= csrf_field() ?>
            
            <!-- PASO 1: Información básica -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">1</span>
                        Información del Incremento
                    </h2>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">
                                Nombre del incremento <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="nombre" 
                                   class="form-input" 
                                   placeholder="Ej: Temporada Alta Navidad"
                                   value="<?= old('nombre') ?>"
                                   required>
                            <p class="form-help">Un nombre corto y descriptivo</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Prioridad</label>
                            <input type="number" 
                                   name="prioridad" 
                                   class="form-input" 
                                   value="<?= old('prioridad', 0) ?>"
                                   min="0"
                                   max="99">
                            <p class="form-help">Mayor número = mayor prioridad</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción (opcional)</label>
                        <textarea name="descripcion" 
                                  class="form-input" 
                                  rows="2"
                                  placeholder="Explique brevemente el motivo del incremento"><?= old('descripcion') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Tipo y valor -->
            <div class="section-card" id="seccionTipo">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">2</span>
                        Tipo y Valor del Incremento
                    </h2>
                </div>
                <div class="section-body">
                    <div class="option-cards grid-cols-2">
                        <div class="option-card selected" onclick="selectTipo('porcentaje')">
                            <input type="radio" name="tipo_incremento" value="porcentaje" checked>
                            <div class="option-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="option-title">Porcentaje</div>
                            <div class="option-desc">Aumenta el precio en un porcentaje</div>
                        </div>

                        <div class="option-card" onclick="selectTipo('monto_fijo')">
                            <input type="radio" name="tipo_incremento" value="monto_fijo">
                            <div class="option-icon bg-green-100 text-green-600">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="option-title">Monto Fijo</div>
                            <div class="option-desc">Suma una cantidad fija al precio</div>
                        </div>
                    </div>

                    <div class="form-group mt-6">
                        <label class="form-label">
                            <span id="labelValor">Porcentaje de incremento</span> <span class="text-red-500">*</span>
                        </label>
                        <div class="flex">
                            <input type="number" 
                                   name="valor_incremento" 
                                   id="valor_incremento"
                                   class="form-input rounded-r-none flex-1" 
                                   step="0.01" 
                                   min="0.01"
                                   placeholder="0.00"
                                   value="<?= old('valor_incremento') ?>"
                                   required>
                            <span class="px-4 py-2 bg-gray-100 border-2 border-l-0 border-gray-300 rounded-r-lg font-semibold" id="simboloValor">%</span>
                        </div>
                        <p class="form-help" id="ayudaValor">Ejemplo: 15 para un incremento del 15%</p>
                    </div>

                    <!-- Preview del cálculo -->
                    <div class="preview-card" id="previewCalculo">
                        <div class="preview-title">
                            <i class="fas fa-calculator"></i>
                            Ejemplo de cálculo
                        </div>
                        <div class="text-sm text-gray-700" id="ejemploCalculo">
                            Si una habitación cuesta $1,000, con un incremento del <strong>15%</strong> 
                            el precio final será <strong>$1,150</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 3: Aplicación -->
            <div class="section-card" id="seccionAlcance">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">3</span>
                        ¿Dónde se aplicará el incremento?
                    </h2>
                </div>
                <div class="section-body">
                    <div class="option-cards" id="alcanceOptions">
                        <div class="option-card selected" onclick="selectAlcance('global')">
                            <input type="radio" name="alcance" value="global" checked>
                            <div class="option-icon bg-purple-100 text-purple-600">
                                <i class="fas fa-hotel"></i>
                            </div>
                            <div class="option-title">Todas las habitaciones</div>
                            <div class="option-desc">Se aplicará a las 66 habitaciones del hotel</div>
                        </div>

                        <div class="option-card" onclick="selectAlcance('tipo_habitacion')">
                            <input type="radio" name="alcance" value="tipo_habitacion">
                            <div class="option-icon bg-indigo-100 text-indigo-600">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div class="option-title">Por tipo de habitación</div>
                            <div class="option-desc">Solo a ciertos tipos (sencilla, doble, etc.)</div>
                        </div>

                        <div class="option-card" onclick="selectAlcance('habitacion')">
                            <input type="radio" name="alcance" value="habitacion">
                            <div class="option-icon bg-amber-100 text-amber-600">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="option-title">Habitaciones específicas</div>
                            <div class="option-desc">Seleccionar habitaciones individuales</div>
                        </div>
                    </div>

                    <!-- Selector de tipos -->
                    <div id="selectorTipos" style="display: none;" class="mt-6">
                        <p class="form-label mb-3">Seleccione los tipos de habitación:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($tipos_habitacion as $tipo): ?>
                            <label class="selection-item bg-gray-50 rounded-lg cursor-pointer">
                                <input type="checkbox" 
                                       name="tipos_habitacion[]" 
                                       value="<?= $tipo['tipo'] ?>">
                                <span class="font-medium"><?= get_tipo_habitacion($tipo['tipo']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Selector de habitaciones -->
                    <div id="selectorHabitaciones" style="display: none;" class="mt-6">
                        <div class="flex justify-between items-center mb-3">
                            <p class="form-label">Seleccione las habitaciones:</p>
                            <button type="button" class="text-sm text-primary hover:underline" onclick="mostrarFiltroTipos()">
                                <i class="fas fa-filter"></i> Filtrar por tipo
                            </button>
                        </div>
                        
                        <?php if (!empty($incrementosVigentes)): ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 mb-3">
                            <p class="text-xs text-amber-700">
                                <i class="fas fa-info-circle mr-1"></i>
                                Los precios mostrados incluyen las tarifas vigentes. El nuevo incremento se sumará a estos precios.
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="selection-list">
                            <?php 
                            $habitaciones_por_tipo = [];
                            foreach ($habitaciones as $hab) {
                                $habitaciones_por_tipo[$hab['tipo']][] = $hab;
                            }
                            ?>
                            <?php foreach ($habitaciones_por_tipo as $tipo => $habs): ?>
                                <div class="tipo-grupo" data-tipo="<?= $tipo ?>">
                                    <div class="px-4 py-2 bg-gray-100 font-semibold text-sm">
                                        <?= get_tipo_habitacion($tipo) ?>
                                    </div>
                                    <?php foreach ($habs as $hab): 
                                        // Calcular precio actual con tarifas vigentes
                                        $calculoHab = $tarifaModel->calcularPrecioConIncremento(
                                            $hab['id'],
                                            $hab['tipo'],
                                            $hab['precio_base'],
                                            $hoy
                                        );
                                        $tieneIncrementoHab = $calculoHab['incremento_total'] > 0;
                                        $tarifasAplicadas = $calculoHab['incrementos_aplicados'] ?? [];
                                    ?>
                                    <label class="selection-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                                        <div class="flex items-center gap-3 w-full">
                                            <input type="checkbox" 
                                                   name="habitaciones[]" 
                                                   value="<?= $hab['id'] ?>"
                                                   data-tipo="<?= $hab['tipo'] ?>"
                                                   onchange="actualizarContadorHabitaciones()">
                                            <span class="flex-1 flex justify-between items-center">
                                                <strong>Habitación <?= $hab['numero'] ?></strong>
                                                <span>
                                                    <?php if ($tieneIncrementoHab): ?>
                                                    <span class="text-gray-400 text-sm line-through"><?= format_currency($hab['precio_base']) ?></span>
                                                    <span class="text-green-600 font-semibold"><?= format_currency($calculoHab['precio_final']) ?></span>
                                                    <?php else: ?>
                                                    <span class="text-gray-600"><?= format_currency($hab['precio_base']) ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </span>
                                        </div>
                                        <?php if (!empty($tarifasAplicadas)): ?>
                                        <div class="flex flex-wrap gap-1 ml-7">
                                            <?php foreach ($tarifasAplicadas as $tarifa): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-700">
                                                <?= htmlspecialchars($tarifa['nombre']) ?>
                                                <span class="ml-1 opacity-75">
                                                    (+<?= $tarifa['tipo'] == 'porcentaje' ? $tarifa['valor'] . '%' : format_currency($tarifa['aumento']) ?>)
                                                </span>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">
                            <span id="contadorHabitaciones">0</span> habitaciones seleccionadas
                        </p>
                    </div>
                </div>
            </div>

            <!-- PASO 4: Vigencia -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-number">4</span>
                        Período de Vigencia
                    </h2>
                </div>
                <div class="section-body">
                    <div class="toggle-container">
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   name="es_permanente" 
                                   id="es_permanente"
                                   value="1"
                                   onchange="togglePermanente()">
                            <span class="toggle-slider"></span>
                        </label>
                        <div>
                            <div class="font-semibold">Incremento permanente</div>
                            <div class="text-sm text-gray-600">Sin fecha de finalización</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">
                                Fecha de inicio <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="fecha_inicio" 
                                   class="form-input"
                                   value="<?= old('fecha_inicio', date('Y-m-d')) ?>"
                                   min="<?= date('Y-m-d') ?>"
                                   required>
                        </div>

                        <div class="form-group" id="grupoFechaFin">
                            <label class="form-label">
                                Fecha de fin <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="fecha_fin" 
                                   id="fecha_fin"
                                   class="form-input"
                                   value="<?= old('fecha_fin') ?>"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            El incremento se aplicará automáticamente a las reservaciones realizadas durante este período.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex justify-end gap-3 mt-6">
                <a href="<?= url('configuracion/tarifas') ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Guardar Incremento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript corregido -->
<script>
// Toggle sección de precios actuales
function togglePreciosActuales() {
    const seccion = document.getElementById('seccionPreciosActuales');
    const icon = document.getElementById('iconTogglePreciosActuales');
    const text = document.getElementById('textTogglePreciosActuales');
    
    if (seccion.style.display === 'none') {
        seccion.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
        text.textContent = 'Ocultar';
    } else {
        seccion.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        text.textContent = 'Mostrar';
    }
}

// Función para seleccionar tipo
function selectTipo(tipo) {
    // Buscar específicamente en la sección de tipo
    document.querySelectorAll('#seccionTipo .option-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    document.querySelector(`input[value="${tipo}"]`).checked = true;

    // Actualizar labels y ejemplo
    const label = document.getElementById('labelValor');
    const simbolo = document.getElementById('simboloValor');
    const ayuda = document.getElementById('ayudaValor');
    const valor = document.getElementById('valor_incremento').value || 15;

    if (tipo === 'porcentaje') {
        label.textContent = 'Porcentaje de incremento';
        simbolo.textContent = '%';
        ayuda.textContent = 'Ejemplo: 15 para un incremento del 15%';
        actualizarEjemplo(valor, 'porcentaje');
    } else {
        label.textContent = 'Monto a incrementar';
        simbolo.textContent = '$';
        ayuda.textContent = 'Ejemplo: 100 para incrementar $100';
        actualizarEjemplo(valor, 'monto_fijo');
    }
}

// Función para seleccionar alcance - CORREGIDA
function selectAlcance(alcance) {
    // Buscar específicamente en la sección de alcance usando el ID
    document.querySelectorAll('#alcanceOptions .option-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Agregar la clase selected al elemento clickeado
    event.currentTarget.classList.add('selected');
    
    // Marcar el radio button correspondiente
    document.querySelector(`input[name="alcance"][value="${alcance}"]`).checked = true;

    // Mostrar/ocultar selectores
    document.getElementById('selectorTipos').style.display = 'none';
    document.getElementById('selectorHabitaciones').style.display = 'none';

    if (alcance === 'tipo_habitacion') {
        document.getElementById('selectorTipos').style.display = 'block';
    } else if (alcance === 'habitacion') {
        document.getElementById('selectorHabitaciones').style.display = 'block';
        actualizarContadorHabitaciones();
    }
}

// Toggle permanente
function togglePermanente() {
    const isPermanente = document.getElementById('es_permanente').checked;
    const fechaFinGroup = document.getElementById('grupoFechaFin');
    const fechaFinInput = document.getElementById('fecha_fin');

    if (isPermanente) {
        fechaFinGroup.style.opacity = '0.5';
        fechaFinInput.removeAttribute('required');
    } else {
        fechaFinGroup.style.opacity = '1';
        fechaFinInput.setAttribute('required', 'required');
    }
}

// Actualizar ejemplo
function actualizarEjemplo(valor, tipo) {
    const ejemplo = document.getElementById('ejemploCalculo');
    const precioBase = 1000;
    let precioFinal;

    if (tipo === 'porcentaje') {
        precioFinal = precioBase + (precioBase * valor / 100);
        ejemplo.innerHTML = `Si una habitación cuesta $${precioBase.toLocaleString()}, con un incremento del <strong>${valor}%</strong> 
                           el precio final será <strong>$${precioFinal.toLocaleString()}</strong>`;
    } else {
        precioFinal = precioBase + parseFloat(valor);
        ejemplo.innerHTML = `Si una habitación cuesta $${precioBase.toLocaleString()}, sumando <strong>$${valor}</strong> 
                           el precio final será <strong>$${precioFinal.toLocaleString()}</strong>`;
    }
}

// Actualizar contador de habitaciones
function actualizarContadorHabitaciones() {
    const total = document.querySelectorAll('input[name="habitaciones[]"]:checked').length;
    document.getElementById('contadorHabitaciones').textContent = total;
}

// Mostrar filtro de tipos
function mostrarFiltroTipos() {
    Swal.fire({
        title: 'Filtrar por tipo',
        html: `
            <div class="text-left">
                <?php foreach ($tipos_habitacion as $tipo): ?>
                <label class="block p-2 hover:bg-gray-100 rounded cursor-pointer">
                    <input type="checkbox" class="tipo-filtro mr-2" value="<?= $tipo['tipo'] ?>">
                    <?= get_tipo_habitacion($tipo['tipo']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Aplicar filtro',
        confirmButtonColor: '#6B4423'
    }).then((result) => {
        if (result.isConfirmed) {
            const tiposSeleccionados = Array.from(document.querySelectorAll('.tipo-filtro:checked')).map(cb => cb.value);
            
            document.querySelectorAll('.tipo-grupo').forEach(grupo => {
                grupo.style.display = tiposSeleccionados.length === 0 || tiposSeleccionados.includes(grupo.dataset.tipo) ? 'block' : 'none';
            });
        }
    });
}

// Event listeners
document.getElementById('valor_incremento').addEventListener('input', function() {
    const tipo = document.querySelector('input[name="tipo_incremento"]:checked').value;
    actualizarEjemplo(this.value || 0, tipo);
});

// Validación del formulario
document.getElementById('formIncremento').addEventListener('submit', function(e) {
    const alcance = document.querySelector('input[name="alcance"]:checked').value;
    
    if (alcance === 'tipo_habitacion') {
        const tipos = document.querySelectorAll('input[name="tipos_habitacion[]"]:checked');
        if (tipos.length === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Seleccione al menos un tipo de habitación', 'error');
            return;
        }
    }
    
    if (alcance === 'habitacion') {
        const habitaciones = document.querySelectorAll('input[name="habitaciones[]"]:checked');
        if (habitaciones.length === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Seleccione al menos una habitación', 'error');
            return;
        }
    }
});

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    togglePermanente();
    
    // Asegurar que el primer tipo esté seleccionado visualmente
    const firstTipoCard = document.querySelector('#seccionTipo .option-card');
    if (firstTipoCard) {
        firstTipoCard.classList.add('selected');
    }
    
    // Asegurar que el primer alcance esté seleccionado visualmente
    const firstAlcanceCard = document.querySelector('#alcanceOptions .option-card');
    if (firstAlcanceCard) {
        firstAlcanceCard.classList.add('selected');
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>