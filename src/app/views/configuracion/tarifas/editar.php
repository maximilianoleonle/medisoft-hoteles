<!-- Editar Incremento de Tarifa - Diseño Simplificado y Claro -->
<style>
:root {
    --primary: var(--brand-primary, #1B2746);
    --primary-dark: var(--brand-secondary, #0F172A);
    --primary-light: color-mix(in srgb, var(--brand-primary, #1B2746) 68%, #FFFFFF);
    --accent: var(--brand-accent, #BD9441);
    --brand-focus-ring: color-mix(in srgb, var(--brand-primary, #1B2746) 18%, transparent);
    --brand-hover-soft: color-mix(in srgb, var(--brand-accent, #BD9441) 12%, #FFFFFF);
    --brand-selected-soft: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, #FFFFFF);
    --brand-elevated-shadow: color-mix(in srgb, var(--brand-primary, #1B2746) 22%, transparent);
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
    box-shadow: 0 0 0 3px var(--brand-focus-ring);
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
    background: var(--brand-hover-soft);
    transform: translateY(-2px);
}

.option-card.selected {
    border-color: var(--primary);
    background: var(--brand-selected-soft);
    box-shadow: 0 0 0 3px var(--brand-focus-ring);
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
    box-shadow: 0 4px 12px var(--brand-elevated-shadow);
}

.btn-primary.is-confirming {
    background: color-mix(in srgb, var(--accent) 48%, var(--primary-dark));
}

.tarifa-form-alert {
    display: none;
    align-items: center;
    gap: 10px;
    margin: 18px 0 0;
    padding: 12px 14px;
    border: 1px solid color-mix(in srgb, var(--danger) 30%, #e2e8f0);
    border-radius: 14px;
    background: color-mix(in srgb, var(--danger) 8%, #fff);
    color: color-mix(in srgb, var(--danger) 78%, #111827);
    font-size: .88rem;
    font-weight: 800;
}

.tarifa-form-alert.is-visible {
    display: flex;
}

.tarifa-form-alert.is-confirmation {
    border-color: color-mix(in srgb, var(--accent) 38%, #e2e8f0);
    background: color-mix(in srgb, var(--accent) 12%, #fff);
    color: color-mix(in srgb, var(--accent) 70%, #111827);
}

.tarifa-form-alert i {
    color: var(--danger);
}

.tarifa-form-alert.is-confirmation i {
    color: color-mix(in srgb, var(--accent) 76%, #111827);
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

/* Info card */
.info-card {
    background: #fffbeb;
    border: 1px solid #fbbf24;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
    display: flex;
    align-items: start;
    gap: 12px;
}

.info-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: #fbbf24;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
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
</style>

<div class="min-h-screen bg-gray-50 py-4">
    <!-- Header Simplificado -->
    <div class="tarifa-container px-4">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Editar Incremento de Tarifa</h1>
                <p class="text-gray-600 mt-1">Modifique los parámetros del incremento</p>
            </div>
            <?php $back_arrow_href = back_url('configuracion/tarifas'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('configuracion/tarifas') ?>" class="btn btn-secondary ms-back-legacy">
                <i class="fas fa-arrow-left"></i>
                Regresar
            </a>
        </div>

        <!-- Información del incremento actual -->
        <div class="info-card">
            <div class="info-icon">
                <i class="fas fa-info"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 mb-1">Información del incremento</h3>
                <p class="text-sm text-gray-700">
                    Editando: <strong><?= htmlspecialchars($incremento['nombre']) ?></strong><br>
                    Creado el <?= format_date($incremento['created_at'], 'd/m/Y H:i') ?> por <?= htmlspecialchars($incremento['usuario_nombre'] ?? 'Usuario desconocido') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="tarifa-container px-4">
        <form action="<?= url('configuracion/tarifas/editar/' . $incremento['id']) ?>" method="POST" id="formIncremento">
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
                                   value="<?= htmlspecialchars($incremento['nombre']) ?>"
                                   required>
                            <p class="form-help">Un nombre corto y descriptivo</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Prioridad</label>
                            <input type="number" 
                                   name="prioridad" 
                                   class="form-input" 
                                   value="<?= $incremento['prioridad'] ?>"
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
                                  placeholder="Explique brevemente el motivo del incremento"><?= htmlspecialchars($incremento['descripcion']) ?></textarea>
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
                    <?php $incClase = $incremento['clase'] ?? 'incremento'; ?>
                    <div class="option-cards grid-cols-2" id="claseCards" style="margin-bottom:1.25rem;">
                        <div class="option-card <?= $incClase === 'descuento' ? '' : 'selected' ?>" onclick="selectClase('incremento')">
                            <input type="radio" name="clase" value="incremento" <?= $incClase === 'descuento' ? '' : 'checked' ?>>
                            <div class="option-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="option-title">Incremento</div>
                            <div class="option-desc">Aumenta el precio de la habitacion</div>
                        </div>

                        <div class="option-card <?= $incClase === 'descuento' ? 'selected' : '' ?>" onclick="selectClase('descuento')">
                            <input type="radio" name="clase" value="descuento" <?= $incClase === 'descuento' ? 'checked' : '' ?>>
                            <div class="option-icon bg-rose-100 text-rose-600">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="option-title">Descuento</div>
                            <div class="option-desc">Resta del precio de la habitacion</div>
                        </div>
                    </div>

                    <div class="option-cards grid-cols-2" id="tipoCards">
                        <div class="option-card <?= $incremento['tipo_incremento'] == 'porcentaje' ? 'selected' : '' ?>" onclick="selectTipo('porcentaje')">
                            <input type="radio" name="tipo_incremento" value="porcentaje" <?= $incremento['tipo_incremento'] == 'porcentaje' ? 'checked' : '' ?>>
                            <div class="option-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="option-title">Porcentaje</div>
                            <div class="option-desc">Aumenta el precio en un porcentaje</div>
                        </div>

                        <div class="option-card <?= $incremento['tipo_incremento'] == 'monto_fijo' ? 'selected' : '' ?>" onclick="selectTipo('monto_fijo')">
                            <input type="radio" name="tipo_incremento" value="monto_fijo" <?= $incremento['tipo_incremento'] == 'monto_fijo' ? 'checked' : '' ?>>
                            <div class="option-icon bg-green-100 text-green-600">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="option-title">Monto Fijo</div>
                            <div class="option-desc">Suma una cantidad fija al precio</div>
                        </div>
                    </div>

                    <div class="form-group mt-6">
                        <label class="form-label">
                            <span id="labelValor"><?= $incremento['tipo_incremento'] == 'porcentaje' ? 'Porcentaje de incremento' : 'Monto a incrementar' ?></span> <span class="text-red-500">*</span>
                        </label>
                        <div class="flex">
                            <input type="number" 
                                   name="valor_incremento" 
                                   id="valor_incremento"
                                   class="form-input rounded-r-none flex-1" 
                                   step="0.01" 
                                   min="0.01"
                                   placeholder="0.00"
                                   value="<?= $incremento['valor_incremento'] ?>"
                                   required>
                            <span class="px-4 py-2 bg-gray-100 border-2 border-l-0 border-gray-300 rounded-r-lg font-semibold" id="simboloValor">
                                <?= $incremento['tipo_incremento'] == 'porcentaje' ? '%' : '$' ?>
                            </span>
                        </div>
                        <p class="form-help" id="ayudaValor">
                            <?= $incremento['tipo_incremento'] == 'porcentaje' 
                                ? 'Ejemplo: 15 para un incremento del 15%' 
                                : 'Ejemplo: 100 para incrementar $100' ?>
                        </p>
                    </div>

                    <!-- Preview del cálculo -->
                    <div class="preview-card" id="previewCalculo">
                        <div class="preview-title">
                            <i class="fas fa-calculator"></i>
                            Ejemplo de cálculo
                        </div>
                        <div class="text-sm text-gray-700" id="ejemploCalculo">
                            <!-- Se actualizará con JavaScript -->
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
                        <div class="option-card <?= $incremento['alcance'] == 'global' ? 'selected' : '' ?>" onclick="selectAlcance('global')">
                            <input type="radio" name="alcance" value="global" <?= $incremento['alcance'] == 'global' ? 'checked' : '' ?>>
                            <div class="option-icon bg-purple-100 text-purple-600">
                                <i class="fas fa-hotel"></i>
                            </div>
                            <div class="option-title">Todas las habitaciones</div>
                            <div class="option-desc">Se aplicará a las 66 habitaciones del hotel</div>
                        </div>

                        <div class="option-card <?= $incremento['alcance'] == 'tipo_habitacion' ? 'selected' : '' ?>" onclick="selectAlcance('tipo_habitacion')">
                            <input type="radio" name="alcance" value="tipo_habitacion" <?= $incremento['alcance'] == 'tipo_habitacion' ? 'checked' : '' ?>>
                            <div class="option-icon bg-indigo-100 text-indigo-600">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div class="option-title">Por tipo de habitación</div>
                            <div class="option-desc">Solo a ciertos tipos (sencilla, doble, etc.)</div>
                        </div>

                        <div class="option-card <?= $incremento['alcance'] == 'habitacion' ? 'selected' : '' ?>" onclick="selectAlcance('habitacion')">
                            <input type="radio" name="alcance" value="habitacion" <?= $incremento['alcance'] == 'habitacion' ? 'checked' : '' ?>>
                            <div class="option-icon bg-amber-100 text-amber-600">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="option-title">Habitaciones específicas</div>
                            <div class="option-desc">Seleccionar habitaciones individuales</div>
                        </div>
                    </div>

                    <!-- Selector de tipos -->
                    <div id="selectorTipos" style="display: <?= $incremento['alcance'] == 'tipo_habitacion' ? 'block' : 'none' ?>;" class="mt-6">
                        <p class="form-label mb-3">Seleccione los tipos de habitación:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($tipos_habitacion as $tipo): ?>
                            <label class="selection-item bg-gray-50 rounded-lg cursor-pointer">
                                <input type="checkbox" 
                                       name="tipos_habitacion[]" 
                                       value="<?= $tipo['tipo'] ?>"
                                       <?= in_array($tipo['tipo'], $incremento['tipos_habitacion_array']) ? 'checked' : '' ?>>
                                <span class="font-medium"><?= get_tipo_habitacion($tipo['tipo']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Selector de habitaciones -->
                    <div id="selectorHabitaciones" style="display: <?= $incremento['alcance'] == 'habitacion' ? 'block' : 'none' ?>;" class="mt-6">
                        <div class="flex justify-between items-center mb-3">
                            <p class="form-label">Seleccione las habitaciones:</p>
                            <button type="button" class="text-sm text-primary hover:underline" onclick="mostrarFiltroTipos()">
                                <i class="fas fa-filter"></i> Filtrar por tipo
                            </button>
                        </div>
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
                                    <?php foreach ($habs as $hab): ?>
                                    <label class="selection-item">
                                        <input type="checkbox" 
                                               name="habitaciones[]" 
                                               value="<?= $hab['id'] ?>"
                                               data-tipo="<?= $hab['tipo'] ?>"
                                               <?= in_array($hab['id'], $incremento['habitaciones_array']) ? 'checked' : '' ?>
                                               onchange="actualizarContadorHabitaciones()">
                                        <span>
                                            <strong>Habitación <?= $hab['numero'] ?></strong>
                                            <span class="text-gray-600 text-sm"><?= format_currency($hab['precio_base']) ?></span>
                                        </span>
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
                                   <?= $incremento['es_permanente'] ? 'checked' : '' ?>
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
                                   value="<?= $incremento['fecha_inicio'] ?>"
                                   required>
                            <p class="form-help">
                                <?php if ($incremento['fecha_inicio'] < date('Y-m-d')): ?>
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                    Este incremento ya está en curso
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="form-group" id="grupoFechaFin">
                            <label class="form-label">
                                Fecha de fin <span class="text-red-500" id="requeridoFin">*</span>
                            </label>
                            <input type="date" 
                                   name="fecha_fin" 
                                   id="fecha_fin"
                                   class="form-input"
                                   value="<?= $incremento['fecha_fin'] ?>">
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

            <!-- Reservaciones existentes: revision opcional del impacto -->
            <div class="section-card" id="cardImpactoReservas">
                <div class="section-body" style="display:flex; align-items:flex-start; gap:.85rem;">
                    <label class="toggle-switch" style="flex-shrink:0; margin-top:.15rem;">
                        <input type="checkbox"
                               name="revisar_reservaciones"
                               id="revisar_reservaciones"
                               value="1">
                        <span class="toggle-slider"></span>
                    </label>
                    <div>
                        <div class="font-semibold">Revisar reservaciones existentes al guardar</div>
                        <div class="text-sm text-gray-600">
                            Las reservaciones ya creadas conservan su precio congelado. Con esta opción, al guardar verás
                            las futuras afectadas (precio actual → nuevo y saldo resultante) y confirmarás el recálculo.
                            Nada se modifica sin tu confirmación.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div id="tarifaFormAlert" class="tarifa-form-alert" role="alert" aria-live="assertive" hidden>
                <i class="fas fa-circle-exclamation"></i>
                <span data-tarifa-alert-text></span>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="<?= back_url('configuracion/tarifas') ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript corregido -->
<script>
function tarifaBrandPrimary() {
    return getComputedStyle(document.documentElement).getPropertyValue('--brand-primary').trim() || '#1B2746';
}

// Función para seleccionar tipo - CORREGIDA
function selectTipo(tipo) {
    // Buscar específicamente en las tarjetas de tipo (no en las de clase)
    document.querySelectorAll('#tipoCards .option-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    document.querySelector(`input[name="tipo_incremento"][value="${tipo}"]`).checked = true;

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

function selectClase(clase) {
    document.querySelectorAll('#claseCards .option-card').forEach(function (card) {
        const radio = card.querySelector('input[name="clase"]');
        const isSel = radio && radio.value === clase;
        card.classList.toggle('selected', isSel);
        if (isSel) { radio.checked = true; }
    });
    actualizarCardImpacto();
}

// La revision de reservaciones existentes solo aplica a incrementos:
// los descuentos se calculan al crear cada reservacion, no en retro.
function actualizarCardImpacto() {
    const card = document.getElementById('cardImpactoReservas');
    if (!card) { return; }
    const claseSel = document.querySelector('input[name="clase"]:checked');
    const esDescuento = claseSel && claseSel.value === 'descuento';
    card.style.display = esDescuento ? 'none' : '';
    if (esDescuento) {
        const chk = document.getElementById('revisar_reservaciones');
        if (chk) { chk.checked = false; }
    }
}
document.addEventListener('DOMContentLoaded', actualizarCardImpacto);

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
    const requerido = document.getElementById('requeridoFin');

    if (isPermanente) {
        fechaFinGroup.style.opacity = '0.5';
        fechaFinInput.removeAttribute('required');
        requerido.style.display = 'none';
    } else {
        fechaFinGroup.style.opacity = '1';
        fechaFinInput.setAttribute('required', 'required');
        requerido.style.display = 'inline';
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
        confirmButtonColor: tarifaBrandPrimary(),
        cancelButtonText: 'Cancelar'
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

const formIncremento = document.getElementById('formIncremento');
const tarifaFormAlert = document.getElementById('tarifaFormAlert');
const tarifaSubmitButton = formIncremento ? formIncremento.querySelector('button[type="submit"]') : null;
const tarifaSubmitDefaultHtml = tarifaSubmitButton ? tarifaSubmitButton.innerHTML : '';
let tarifaSaveConfirmTimer = null;

function mostrarErrorFormularioTarifa(message, targetSelector, mode) {
    if (!tarifaFormAlert) {
        return;
    }

    const text = tarifaFormAlert.querySelector('[data-tarifa-alert-text]');
    if (text) {
        text.textContent = message;
    }

    tarifaFormAlert.hidden = false;
    tarifaFormAlert.classList.add('is-visible');
    tarifaFormAlert.classList.toggle('is-confirmation', mode === 'confirmation');

    const target = targetSelector ? document.querySelector(targetSelector) : tarifaFormAlert;
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function limpiarErrorFormularioTarifa() {
    if (!tarifaFormAlert) {
        return;
    }

    tarifaFormAlert.classList.remove('is-visible');
    tarifaFormAlert.classList.remove('is-confirmation');
    tarifaFormAlert.hidden = true;
}

function setTarifaSaveConfirmState(active) {
    if (!tarifaSubmitButton) {
        return;
    }

    tarifaSubmitButton.classList.toggle('is-confirming', active);
    tarifaSubmitButton.innerHTML = active
        ? '<i class="fas fa-check"></i> Confirmar cambios'
        : tarifaSubmitDefaultHtml;
}

document.querySelectorAll('input[name="tipos_habitacion[]"], input[name="habitaciones[]"], input[name="alcance"]').forEach(input => {
    input.addEventListener('change', limpiarErrorFormularioTarifa);
});

// Validación del formulario
if (formIncremento) {
    formIncremento.addEventListener('submit', function(e) {
        const alcance = document.querySelector('input[name="alcance"]:checked').value;
        limpiarErrorFormularioTarifa();

        if (alcance === 'tipo_habitacion') {
            const tipos = document.querySelectorAll('input[name="tipos_habitacion[]"]:checked');
            if (tipos.length === 0) {
                e.preventDefault();
                mostrarErrorFormularioTarifa('Selecciona al menos un tipo de habitacion para continuar.', '#selectorTipos');
                return;
            }
        }

        if (alcance === 'habitacion') {
            const habitaciones = document.querySelectorAll('input[name="habitaciones[]"]:checked');
            if (habitaciones.length === 0) {
                e.preventDefault();
                mostrarErrorFormularioTarifa('Selecciona al menos una habitacion para continuar.', '#selectorHabitaciones');
                return;
            }
        }

        e.preventDefault();

        if (this.dataset.confirmedTarifaSave === '1') {
            this.submit();
            return;
        }

        this.dataset.confirmedTarifaSave = '1';
        setTarifaSaveConfirmState(true);
        mostrarErrorFormularioTarifa('Vuelve a hacer clic en Confirmar cambios para guardar esta tarifa.', null, 'confirmation');

        window.clearTimeout(tarifaSaveConfirmTimer);
        tarifaSaveConfirmTimer = window.setTimeout(() => {
            delete this.dataset.confirmedTarifaSave;
            setTarifaSaveConfirmState(false);
            limpiarErrorFormularioTarifa();
        }, 6500);
    });
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    togglePermanente();
    actualizarContadorHabitaciones();
    
    // Actualizar ejemplo inicial
    const tipo = document.querySelector('input[name="tipo_incremento"]:checked').value;
    const valor = document.getElementById('valor_incremento').value;
    actualizarEjemplo(valor, tipo);
});
</script>

<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
