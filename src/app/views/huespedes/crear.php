<?php
// Verificar si viene de reservacion rapida
$return_to = $_GET['return_to'] ?? null;
$es_reservacion_rapida = $return_to === 'reservacion_rapida';

// Recuperar datos de reservacion rapida si existen
$reservacion_rapida_params = '';
if ($es_reservacion_rapida) {
    $params_to_pass = [];
    if (isset($_GET['habitacion_id'])) $params_to_pass['habitacion_id'] = $_GET['habitacion_id'];
    if (isset($_GET['fecha_entrada'])) $params_to_pass['fecha_entrada'] = $_GET['fecha_entrada'];
    if (isset($_GET['fecha_salida'])) $params_to_pass['fecha_salida'] = $_GET['fecha_salida'];
    if (isset($_GET['hora_llegada'])) $params_to_pass['hora_llegada'] = $_GET['hora_llegada'];

    if (!empty($params_to_pass)) {
        $reservacion_rapida_params = '&' . http_build_query($params_to_pass);
    }
}

$cancel_url = url('huespedes');
if ($return_to === 'reservacion') {
    $cancel_url = url('reservaciones/crear');
} elseif ($return_to === 'reservacion_rapida') {
    $cancel_url = url('habitaciones');
}

$estacionamientos = [];
if (function_exists('hotel_general_catalog_parking_rows')) {
    foreach (hotel_general_catalog_parking_rows(null, false) as $parkingRow) {
        $parkingCode = trim((string)($parkingRow['codigo'] ?? ''));
        $parkingLabel = trim((string)($parkingRow['label'] ?? ''));
        if ($parkingCode !== '' && $parkingLabel !== '') {
            $estacionamientos[$parkingCode] = $parkingLabel;
        }
    }
}
if (empty($estacionamientos)) {
    $estacionamientos = ['coches' => 'Coches'];
}
reset($estacionamientos);
$estacionamientoDefault = (string)key($estacionamientos);
$gcOldVehicleRows = is_array($_SESSION['old_input']['vehiculos'] ?? null)
    ? array_values($_SESSION['old_input']['vehiculos'])
    : [];
$gcVehicleRows = !empty($gcOldVehicleRows) ? $gcOldVehicleRows : [[]];

$gcParkingOptionsTemplate = '';
foreach ($estacionamientos as $parkingCode => $parkingLabel) {
    $parkingCode = (string)$parkingCode;
    $parkingCodeEsc = htmlspecialchars($parkingCode, ENT_QUOTES, 'UTF-8');
    $parkingLabelEsc = htmlspecialchars((string)$parkingLabel, ENT_QUOTES, 'UTF-8');
    $checkedAttr = $parkingCode === $estacionamientoDefault ? ' checked' : '';
    $gcParkingOptionsTemplate .= '<label class="relative cursor-pointer">';
    $gcParkingOptionsTemplate .= '<input type="radio" name="__PARKING_NAME__" value="' . $parkingCodeEsc . '" class="peer sr-only"' . $checkedAttr . '>';
    $gcParkingOptionsTemplate .= '<div class="gc-radio-card"><i class="fas fa-car"></i><p>' . $parkingLabelEsc . '</p></div>';
    $gcParkingOptionsTemplate .= '</label>';
}

$guestFieldPolicy = is_array($guestFieldPolicy ?? null)
    ? $guestFieldPolicy
    : (function_exists('hotel_guest_field_policy') ? hotel_guest_field_policy() : ['fields' => []]);
$guestFieldCatalog = function_exists('hotel_guest_field_catalog') ? hotel_guest_field_catalog() : [];
$gcGuestVisibleFields = function ($scope) use ($guestFieldPolicy) {
    return function_exists('hotel_guest_visible_fields') ? hotel_guest_visible_fields($scope, $guestFieldPolicy) : [];
};
$gcGuestFieldVisible = function ($key) use ($guestFieldPolicy) {
    return function_exists('hotel_guest_field_visible') ? hotel_guest_field_visible($key, $guestFieldPolicy) : true;
};
$gcGuestFieldRequired = function ($key) use ($guestFieldPolicy) {
    return function_exists('hotel_guest_field_required') ? hotel_guest_field_required($key, $guestFieldPolicy) : false;
};
$gcOldExtra = function ($key, $default = '') {
    $value = $_SESSION['old_input']['extras'][$key] ?? $default;
    return is_scalar($value) ? (string)$value : (string)$default;
};
$gcRequiredMark = function ($key) use ($gcGuestFieldRequired) {
    return $gcGuestFieldRequired($key) ? ' <span class="gc-required">*</span>' : '';
};
// Distintivo por seccion: obligatoria (tiene campos requeridos) vs opcional.
$gcSectionBadge = function (bool $required): string {
    return $required
        ? '<span class="gc-badge gc-badge-req"><i class="fas fa-asterisk"></i> Obligatorio</span>'
        : '<span class="gc-badge gc-badge-opt"><i class="far fa-circle"></i> Opcional</span>';
};
$gcRenderGuestExtraField = function ($fieldKey, array $definition) use ($gcGuestFieldRequired, $gcOldExtra) {
    $label = htmlspecialchars((string)($definition['label'] ?? $fieldKey), ENT_QUOTES, 'UTF-8');
    $placeholder = htmlspecialchars((string)($definition['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8');
    $value = htmlspecialchars($gcOldExtra($fieldKey), ENT_QUOTES, 'UTF-8');
    $required = $gcGuestFieldRequired($fieldKey);
    $input = (string)($definition['input'] ?? 'text');
    $wide = !empty($definition['wide']) || in_array($input, ['textarea'], true);
    $max = (int)($definition['max'] ?? 0);
    $fieldKeySafe = htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8');
    $name = 'extras[' . $fieldKeySafe . ']';
    if ($input === 'file') {
        $name = htmlspecialchars((string)($definition['file_name'] ?? $fieldKey), ENT_QUOTES, 'UTF-8');
    }
    $errorKey = $input === 'file'
        ? (string)($definition['file_name'] ?? $fieldKey)
        : 'extras[' . (string)$fieldKey . ']';
    $requiredHtml = $required ? ' required' : '';
    $maxHtml = $max > 0 ? ' maxlength="' . $max . '"' : '';
    $telHtml = $input === 'tel'
        ? ' inputmode="numeric" pattern="[0-9]*"' . ($max > 0 ? ' data-max-digits="' . $max . '"' : '')
        : '';
    $acceptHtml = !empty($definition['accept'])
        ? ' accept="' . htmlspecialchars((string)$definition['accept'], ENT_QUOTES, 'UTF-8') . '"'
        : '';

    ob_start();
    ?>
    <div class="gc-field<?= $wide ? ' gc-field-full' : '' ?>">
        <label class="gc-label"><?= $label ?><?= $required ? ' <span class="gc-required">*</span>' : '' ?></label>
        <?php if ($input === 'file'): ?>
            <input type="file"
                   name="<?= $name ?>"
                   class="gc-control gc-file-control"
                   <?= $acceptHtml ?>
                   <?= $requiredHtml ?>>
            <p class="gc-field-hint">
                Puede tomar foto con la camara, elegir desde galeria o adjuntar PDF/imagen desde archivos.
            </p>
        <?php elseif ($input === 'textarea'): ?>
            <textarea name="<?= $name ?>" rows="<?= (int)($definition['rows'] ?? 3) ?>" placeholder="<?= $placeholder ?>" class="gc-control"<?= $requiredHtml ?><?= $maxHtml ?>><?= $value ?></textarea>
        <?php elseif ($input === 'select'): ?>
            <select name="<?= $name ?>" class="gc-control"<?= $requiredHtml ?>>
                <option value="">Seleccione una opcion</option>
                <?php foreach (($definition['options'] ?? []) as $optionValue => $optionLabel): ?>
                    <option value="<?= htmlspecialchars((string)$optionValue, ENT_QUOTES, 'UTF-8') ?>" <?= $gcOldExtra($fieldKey) === (string)$optionValue ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <input type="<?= in_array($input, ['email', 'tel', 'date'], true) ? htmlspecialchars($input, ENT_QUOTES, 'UTF-8') : 'text' ?>"
                   name="<?= $name ?>"
                   value="<?= $value ?>"
                   placeholder="<?= $placeholder ?>"
                   class="gc-control<?= !empty($definition['uppercase']) ? ' gc-uppercase' : '' ?>"
                   <?= !empty($definition['uppercase']) ? 'style="text-transform: uppercase"' : '' ?>
                   <?= $requiredHtml ?>
                   <?= $telHtml ?>
                   <?= $maxHtml ?>>
        <?php endif; ?>
        <?php if (form_error($errorKey)): ?>
            <span class="gc-form-error"><?= form_error($errorKey) ?></span>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
};

$gcVehicleVisibleFields = $gcGuestVisibleFields('vehicle');
$gcRenderVehicleFields = function ($indexToken, array $values = []) use ($gcVehicleVisibleFields, $gcGuestFieldRequired, $gcParkingOptionsTemplate) {
    ob_start();
    foreach ($gcVehicleVisibleFields as $fieldKey => $definition) {
        $input = (string)($definition['input'] ?? 'text');
        $storage = (string)($definition['storage'] ?? 'extra');
        $fieldName = (string)($definition['field'] ?? $fieldKey);
        $extrasValues = is_array($values['extras'] ?? null) ? $values['extras'] : [];
        $currentValue = $storage === 'column'
            ? (string)($values[$fieldName] ?? '')
            : (string)($extrasValues[$fieldKey] ?? '');
        $label = htmlspecialchars((string)($definition['label'] ?? $fieldKey), ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars((string)($definition['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8');
        $required = $gcGuestFieldRequired($fieldKey);
        $wide = !empty($definition['wide']) || in_array($input, ['textarea', 'parking'], true);
        $max = (int)($definition['max'] ?? 0);
        $errorKey = $storage === 'column'
            ? 'vehiculos[' . $indexToken . '][' . $fieldName . ']'
            : 'vehiculos[' . $indexToken . '][extras][' . (string)$fieldKey . ']';
        $name = $storage === 'column'
            ? 'vehiculos[' . $indexToken . '][' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . ']'
            : 'vehiculos[' . $indexToken . '][extras][' . htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') . ']';
        $requiredHtml = $required ? ' data-vehicle-required="1"' : '';
        $maxHtml = $max > 0 ? ' maxlength="' . $max . '"' : '';
        $valueHtml = htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8');
        $fieldError = form_error($errorKey);
        $isPlacasField = $storage === 'column' && $fieldName === 'placas';
        if (!$isPlacasField && stripos($fieldError, 'placas') !== false) {
            $fieldError = '';
        }
        ?>
        <div class="gc-field<?= $wide ? ' gc-field-full' : '' ?>">
            <label class="gc-label"><?= $label ?><?= $required ? ' <span class="gc-required gc-vehicle-required-mark" title="Obligatorio solo si registra este vehiculo">*</span>' : '' ?></label>
            <?php if ($input === 'parking'): ?>
                <div class="gc-radio-grid">
                    <?= str_replace('__PARKING_NAME__', $name, $gcParkingOptionsTemplate) ?>
                </div>
            <?php elseif ($input === 'textarea'): ?>
                <textarea name="<?= $name ?>" rows="<?= (int)($definition['rows'] ?? 2) ?>" placeholder="<?= $placeholder ?>" class="gc-control"<?= $requiredHtml ?><?= $maxHtml ?>><?= $valueHtml ?></textarea>
            <?php elseif ($input === 'select'): ?>
                <select name="<?= $name ?>" class="gc-control"<?= $requiredHtml ?>>
                    <option value="">Seleccione una opcion</option>
                    <?php foreach (($definition['options'] ?? []) as $optionValue => $optionLabel): ?>
                        <option value="<?= htmlspecialchars((string)$optionValue, ENT_QUOTES, 'UTF-8') ?>" <?= $currentValue === (string)$optionValue ? 'selected' : '' ?>><?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="<?= in_array($input, ['email', 'tel', 'date'], true) ? htmlspecialchars($input, ENT_QUOTES, 'UTF-8') : 'text' ?>"
                       name="<?= $name ?>"
                       value="<?= $valueHtml ?>"
                       placeholder="<?= $placeholder ?>"
                       class="gc-control<?= !empty($definition['uppercase']) ? ' font-mono' : '' ?>"
                       <?= !empty($definition['uppercase']) ? 'style="text-transform: uppercase"' : '' ?>
                       <?= $requiredHtml ?>
                       <?= $maxHtml ?>>
            <?php endif; ?>
            <?php if ($fieldError): ?>
                <span class="gc-form-error"><?= $fieldError ?></span>
            <?php endif; ?>
        </div>
        <?php
    }

    return ob_get_clean();
};
$gcVehicleFieldsTemplate = $gcRenderVehicleFields('__INDEX__');
?>
<link href="<?= asset('vendor/tailwind/tailwind-2.2.19.min.css') ?>" rel="stylesheet">

<style id="guest-create-boutique">
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.guest-create-page {
    --gc-brand: var(--brand-primary, #1B2746);
    --gc-brand-2: var(--brand-secondary, #0F172A);
    --gc-accent: var(--brand-accent, #BD9441);
    --gc-accent-dark: color-mix(in srgb, var(--gc-accent) 72%, #3F2E12);
    --gc-accent-soft: color-mix(in srgb, var(--gc-accent) 13%, #FFFFFF);
    --gc-accent-line: color-mix(in srgb, var(--gc-accent) 32%, #E8DDCA);
    --gc-ivory: color-mix(in srgb, var(--gc-accent) 8%, #F5F5F7);
    --gc-ivory-2: color-mix(in srgb, var(--gc-accent) 5%, #F5F5F7);
    --gc-surface: color-mix(in srgb, var(--gc-accent) 2%, #FFFFFF);
    --gc-surface-warm: color-mix(in srgb, var(--gc-accent) 5%, #FFFFFF);
    --gc-line: color-mix(in srgb, var(--gc-accent) 20%, #E7DEC9);
    --gc-line-soft: color-mix(in srgb, var(--gc-accent) 11%, #F0ECE2);
    --gc-muted: color-mix(in srgb, var(--gc-brand-2) 48%, #94A3B8);
    --gc-text: var(--gc-brand-2);
    --gc-success: #1E9E63;
    --gc-success-bg: #E7F4EC;
    --gc-info: #2F77E0;
    --gc-info-bg: #E6EFFC;
    --gc-danger: #D64539;
    --gc-danger-bg: #FBE9E7;
    --gc-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --gc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    
    color: var(--gc-text);
    font-family: var(--gc-sans);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.guest-create-page *,
.guest-create-page *::before,
.guest-create-page *::after {
    box-sizing: border-box;
}

.guest-create-page :where(p, span, a, button, input, select, textarea, label) {
    font-family: var(--gc-sans);
}

.gc-wrap {
    width: min(100%, 1280px);
    margin: 0 auto;
    padding: 26px 18px 34px;
}

.gc-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    color: var(--gc-muted);
    font-size: .78rem;
    font-weight: 700;
    margin-bottom: 18px;
}

.gc-breadcrumb a {
    color: #111827;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: color .18s ease, transform .18s ease;
}

.gc-breadcrumb a:hover {
    color: #111827;
    transform: translateY(-1px);
}

.gc-hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
}

.gc-hero-main {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    min-width: 0;
    flex: 1 1 auto;
}

.gc-hero-icon,
.gc-section-icon,
.gc-side-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
}

.gc-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(150deg, var(--gc-brand), var(--gc-brand-2));
    color: #FFFFFF;
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--gc-brand) 58%, transparent);
}

.gc-kicker {
    margin: 0 0 4px;
    color: var(--gc-accent-dark);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.gc-title {
    margin: 0;
    color: #111827;
    font-family: var(--gc-serif);
    font-size: clamp(2rem, 3.6vw, 2.75rem);
    font-weight: 650;
    letter-spacing: 0;
    line-height: .96;
    text-wrap: balance;
}

.gc-title-accent {
    color: var(--gc-brand);
}

.gc-subtitle {
    max-width: 62ch;
    margin: 8px 0 0;
    color: var(--gc-muted);
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.55;
}

.gc-flow-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex: 0 0 auto;
    min-height: 38px;
    padding: 8px 12px;
    border: 1px solid var(--gc-accent-line);
    border-radius: 999px;
    background: var(--gc-accent-soft);
    color: var(--gc-accent-dark);
    font-size: .76rem;
    font-weight: 800;
    white-space: nowrap;
}

.gc-fast-note {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin: 0 0 18px;
    padding: 13px 15px;
    border: 1px solid color-mix(in srgb, var(--gc-info) 22%, var(--gc-line));
    border-radius: 15px;
    background: color-mix(in srgb, var(--gc-info) 8%, var(--gc-surface));
    color: #111827;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--gc-brand-2) 4%, transparent);
}

.gc-fast-note i {
    color: var(--gc-info);
    margin-top: 2px;
}

.gc-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(286px, 318px);
    gap: 18px;
    align-items: start;
}

.gc-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    min-width: 0;
}

/* Las secciones obligatorias suben arriba; Informacion personal SIEMPRE primera.
   Se reordena por CSS (sin mover el DOM ni parpadeo); la linea de tiempo lo espeja
   leyendo el mismo `order`. Detecta obligatoria por el distintivo .gc-badge-req. */
main.gc-form > .gc-section { order: 2; }                    /* opcionales al final */
main.gc-form > .gc-section:has(.gc-badge-req) { order: 1; } /* obligatorias arriba */
main.gc-form > .gc-section:first-child { order: 0; }        /* Informacion personal fija primera */

.gc-section,
.gc-side-card,
.gc-actions {
    border: 1px solid var(--gc-line);
    border-radius: 18px;
    background: var(--gc-surface);
    box-shadow: 0 1px 2px color-mix(in srgb, var(--gc-brand-2) 4%, transparent), 0 14px 32px -24px color-mix(in srgb, var(--gc-brand-2) 34%, transparent);
    min-width: 0;
}

.gc-section {
    overflow: hidden;
}

.gc-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 58px;
    padding: 14px 17px;
    border-bottom: 1px solid var(--gc-line);
    background: var(--gc-surface-warm);
    min-width: 0;
}

.gc-section-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.gc-section-icon,
.gc-side-icon {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    border: 1px solid var(--gc-line);
    background: color-mix(in srgb, var(--gc-accent) 13%, #FFFFFF);
    color: var(--gc-accent-dark);
}

.gc-section h2,
.gc-side-card h3 {
    margin: 0;
    color: #111827;
    font-size: .92rem;
    font-weight: 850;
    letter-spacing: 0;
    text-wrap: balance;
}

.gc-section-sub {
    margin: 2px 0 0;
    color: var(--gc-muted);
    font-size: .72rem;
    font-weight: 600;
    overflow-wrap: anywhere;
}

/* Distintivo Obligatorio / Opcional por seccion + leyenda superior */
.gc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex: 0 0 auto;
    padding: 4px 11px;
    border-radius: 999px;
    font-size: .62rem;
    font-weight: 850;
    letter-spacing: .06em;
    text-transform: uppercase;
    line-height: 1;
    white-space: nowrap;
}

.gc-badge i {
    font-size: .56rem;
}

.gc-badge-req {
    color: #FFFFFF;
    background: linear-gradient(135deg, var(--gc-brand), var(--gc-brand-2));
    border: 1px solid color-mix(in srgb, var(--gc-brand) 40%, transparent);
    box-shadow: 0 6px 14px -10px color-mix(in srgb, var(--gc-brand) 70%, transparent);
}

.gc-badge-opt {
    color: var(--gc-muted);
    background: var(--gc-surface-warm);
    border: 1px solid var(--gc-line);
}

.gc-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 9px 18px;
    margin: 0 0 16px;
    padding: 12px 15px;
    border: 1px solid var(--gc-line);
    border-radius: 14px;
    background: var(--gc-surface);
    box-shadow: 0 1px 2px color-mix(in srgb, var(--gc-brand-2) 4%, transparent);
}

.gc-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--gc-muted);
    font-size: .76rem;
    font-weight: 700;
}

.gc-legend-item .gc-required {
    font-weight: 850;
}

.gc-section-body {
    padding: 17px;
}

.gc-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    min-width: 0;
}

.gc-field {
    min-width: 0;
}

.gc-field-full {
    grid-column: 1 / -1;
}

.gc-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 7px;
    color: var(--gc-muted);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.gc-required {
    color: var(--gc-danger);
}

.vehiculo-item.is-empty .gc-vehicle-required-mark {
    display: none;
}

.gc-form-error {
    display: block;
    margin-top: 7px;
    color: var(--gc-danger);
    font-size: .76rem;
    font-weight: 850;
    line-height: 1.35;
}

.gc-input-wrap {
    position: relative;
}

.gc-input-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: color-mix(in srgb, var(--gc-brand) 42%, var(--gc-muted));
    font-size: .78rem;
    pointer-events: none;
}

.gc-control {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--gc-line);
    border-radius: 12px;
    background: var(--gc-surface-warm);
    color: var(--gc-text);
    font-size: .88rem;
    font-weight: 650;
    padding: 10px 12px;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    min-width: 0;
}

.gc-control.has-icon {
    padding-left: 36px;
}

.gc-control::placeholder {
    color: color-mix(in srgb, var(--gc-muted) 72%, #CBD5E1);
    font-weight: 500;
}

.gc-control:focus {
    border-color: var(--gc-accent);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--gc-accent) 22%, transparent);
}

.gc-file-control {
    padding: 9px 12px;
    cursor: pointer;
}

.gc-file-control::file-selector-button {
    margin-right: 12px;
    border: 0;
    border-radius: 999px;
    background: color-mix(in srgb, var(--gc-brand) 92%, #FFFFFF);
    color: #FFFFFF;
    font-size: .76rem;
    font-weight: 800;
    padding: 8px 12px;
    cursor: pointer;
}

.gc-document-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    min-width: 0;
}

.gc-document-grid-count-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gc-doc-upload-card {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
    min-height: 164px;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--gc-accent) 18%, var(--gc-line));
    border-radius: 16px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--gc-accent) 8%, #FFFFFF), #FFFFFF 62%),
        var(--gc-surface);
    box-shadow: 0 10px 28px -24px color-mix(in srgb, var(--gc-brand) 48%, transparent);
}

.gc-doc-upload-top {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-width: 0;
}

.gc-doc-upload-icon {
    width: 34px;
    height: 34px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 11px;
    background: color-mix(in srgb, var(--gc-brand) 12%, #FFFFFF);
    color: var(--gc-accent-dark);
    border: 1px solid color-mix(in srgb, var(--gc-accent) 18%, var(--gc-line));
}

.gc-doc-upload-copy {
    min-width: 0;
}

.gc-doc-upload-copy h4 {
    margin: 0;
    color: #111827;
    font-size: .84rem;
    font-weight: 850;
    line-height: 1.25;
}

.gc-doc-upload-copy p {
    margin: 3px 0 0;
    color: var(--gc-muted);
    font-size: .74rem;
    font-weight: 650;
    line-height: 1.4;
}

.gc-doc-upload-card .gc-file-control {
    min-height: 40px;
    background: #FFFFFF;
}

.gc-doc-file-name {
    min-height: 22px;
    padding: 6px 9px;
    border-radius: 10px;
    background: var(--gc-surface-warm);
    color: var(--gc-muted);
    font-size: .73rem;
    font-weight: 750;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.gc-doc-file-name.has-file {
    color: var(--gc-accent-dark);
    background: color-mix(in srgb, var(--gc-accent) 12%, #FFFFFF);
}

.gc-field-hint {
    margin: 7px 0 0;
    color: var(--gc-muted);
    font-size: .76rem;
    font-weight: 650;
    line-height: 1.45;
}

.gc-note {
    margin: 12px 0 0;
    padding: 11px 12px;
    border: 1px solid var(--gc-line);
    border-radius: 13px;
    background: var(--gc-ivory-2);
    color: var(--gc-muted);
    font-size: .78rem;
    font-weight: 600;
    line-height: 1.5;
}

.gc-note i {
    color: var(--gc-accent-dark);
    margin-right: 6px;
}

.vehiculo-item {
    border: 1px solid var(--gc-line);
    border-radius: 16px;
    background: var(--gc-ivory-2);
    padding: 15px;
    margin-bottom: 12px;
    transition: opacity .22s ease, transform .22s ease, border-color .18s ease, box-shadow .18s ease;
    min-width: 0;
}

.vehiculo-item:hover {
    border-color: color-mix(in srgb, var(--gc-accent) 35%, var(--gc-line));
    box-shadow: 0 10px 26px -24px color-mix(in srgb, var(--gc-brand) 45%, transparent);
}

.gc-vehicle-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 13px;
}

.gc-vehicle-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #111827;
    font-size: .84rem;
    font-weight: 850;
}

.gc-vehicle-title i {
    color: var(--gc-accent-dark);
}

.gc-delete-vehicle {
    display: inline-grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border: 1px solid color-mix(in srgb, var(--gc-danger) 22%, #F3D7D4);
    border-radius: 10px;
    background: var(--gc-danger-bg);
    color: var(--gc-danger);
    transition: transform .16s ease, filter .16s ease;
}

.gc-delete-vehicle:hover {
    transform: translateY(-1px);
    filter: brightness(.98);
}

.gc-radio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(118px, 1fr));
    gap: 9px;
}

.gc-radio-card {
    min-height: 68px;
    display: grid;
    place-items: center;
    gap: 4px;
    border: 1px solid var(--gc-line);
    border-radius: 13px;
    background: var(--gc-surface);
    color: #111827;
    text-align: center;
    font-size: .78rem;
    font-weight: 800;
    transition: border-color .18s ease, background .18s ease, color .18s ease, box-shadow .18s ease;
    overflow: hidden;
}

.gc-radio-card i {
    font-size: 1rem;
}

.peer:checked ~ .gc-radio-card {
    border-color: var(--gc-brand);
    background: var(--gc-brand);
    color: #FFFFFF;
    box-shadow: 0 10px 22px -14px color-mix(in srgb, var(--gc-brand) 68%, transparent);
}

.peer:focus ~ .gc-radio-card {
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--gc-accent) 24%, transparent);
}

.gc-add-vehicle {
    width: 100%;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid var(--gc-line);
    border-radius: 13px;
    background: var(--gc-surface-warm);
    color: #111827;
    font-size: .86rem;
    font-weight: 850;
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.gc-add-vehicle:hover {
    transform: translateY(-1px);
    border-color: var(--gc-accent-line);
    background: var(--gc-accent-soft);
    color: var(--gc-accent-dark);
}

.gc-side {
    position: sticky;
    top: 18px;
    display: flex;
    flex-direction: column;
    gap: 13px;
    min-width: 0;
}

.gc-side-card {
    padding: 16px;
}

.gc-side-card h3 {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 11px;
}

.gc-check-list {
    display: grid;
    gap: 9px;
}

.gc-check-list span {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: var(--gc-muted);
    font-size: .78rem;
    font-weight: 650;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.gc-check-list i {
    color: var(--gc-success);
    margin-top: 2px;
}

.gc-mini-card {
    padding: 13px;
    border: 1px solid var(--gc-line-soft);
    border-radius: 14px;
    background: var(--gc-surface-warm);
}

.gc-mini-label {
    margin: 0 0 4px;
    color: var(--gc-muted);
    font-size: .68rem;
    font-weight: 850;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.gc-mini-text {
    margin: 0;
    color: #111827;
    font-size: .82rem;
    font-weight: 750;
    line-height: 1.45;
}

.gc-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px;
    position: static;
    z-index: auto;
    margin-top: 2px;
    align-items: center;
}

.gc-btn {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 12px;
    padding: 10px 15px;
    font-size: .86rem;
    font-weight: 850;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
    white-space: nowrap;
}

.gc-btn:hover {
    transform: translateY(-1px);
}

.gc-btn-secondary {
    border: 1px solid var(--gc-line);
    background: var(--gc-surface-warm);
    color: var(--gc-muted);
}

.gc-btn-secondary:hover {
    border-color: var(--gc-accent-line);
    color: var(--gc-accent-dark);
    background: var(--gc-accent-soft);
}

.gc-btn-primary {
    border: 1px solid color-mix(in srgb, var(--gc-accent) 32%, transparent);
    background: linear-gradient(135deg, var(--gc-brand), var(--gc-brand-2));
    color: #FFFFFF;
    box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--gc-brand) 60%, transparent);
}

.gc-btn-primary:hover {
    color: #FFFFFF;
    box-shadow: 0 16px 30px -14px color-mix(in srgb, var(--gc-brand) 66%, transparent);
}

.gc-btn:active,
.gc-add-vehicle:active,
.gc-delete-vehicle:active {
    transform: translateY(0) scale(.99);
}

.gc-btn:focus-visible,
.gc-add-vehicle:focus-visible,
.gc-delete-vehicle:focus-visible,
.gc-breadcrumb a:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--gc-accent) 28%, transparent);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn .28s cubic-bezier(.22,1,.36,1);
}

.guest-create-page ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.guest-create-page ::-webkit-scrollbar-track {
    background: var(--gc-ivory);
}

.guest-create-page ::-webkit-scrollbar-thumb {
    background: color-mix(in srgb, var(--gc-accent), #fff 34%);
    border-radius: 999px;
}

@media (min-width: 1440px) {
    .gc-wrap {
        width: min(100%, 1360px);
    }

    .gc-layout {
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 22px;
    }

    .gc-section-body {
        padding: 19px;
    }
}

@media (max-width: 1180px) {
    .gc-wrap {
        width: min(100%, 1100px);
    }

    .gc-layout {
        grid-template-columns: minmax(0, 1fr) 284px;
        gap: 16px;
    }

    .gc-grid {
        gap: 12px;
    }

    .gc-document-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 1024px) {
    .gc-layout {
        grid-template-columns: 1fr;
    }

    .gc-side {
        position: static;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gc-actions {
        padding: 13px;
    }
}

@media (max-width: 860px) {
    .gc-hero {
        flex-direction: column;
        align-items: stretch;
    }

    .gc-flow-pill {
        align-self: flex-start;
        white-space: normal;
    }

    .gc-side {
        grid-template-columns: 1fr;
    }

    .gc-section-head {
        align-items: flex-start;
    }
}

@media (max-width: 700px) {
    .gc-wrap {
        padding: 18px 14px 26px;
    }

    .gc-hero {
        gap: 12px;
    }

    .gc-grid {
        grid-template-columns: 1fr;
    }

    .gc-document-grid {
        grid-template-columns: 1fr;
    }

    .gc-flow-pill {
        white-space: normal;
    }

    .gc-section-head,
    .gc-section-body,
    .gc-side-card {
        padding: 14px;
    }

    .gc-section-title-wrap {
        align-items: flex-start;
    }

    .gc-actions {
        position: static;
        flex-direction: column-reverse;
    }

    .gc-btn {
        width: 100%;
        white-space: normal;
        min-height: 46px;
    }

    .gc-control {
        font-size: 16px;
    }

    .vehiculo-item {
        padding: 13px;
    }
}

@media (max-width: 480px) {
    .gc-wrap {
        padding-left: 12px;
        padding-right: 12px;
    }

    .gc-breadcrumb {
        width: 100%;
        overflow-x: auto;
        padding-bottom: 2px;
        white-space: nowrap;
    }

    .gc-hero-main {
        gap: 11px;
    }

    .gc-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
    }

    .gc-title {
        font-size: 2rem;
    }

    .gc-subtitle {
        font-size: .84rem;
    }

    .gc-section-head {
        min-height: auto;
    }

    .gc-section-icon,
    .gc-side-icon {
        width: 31px;
        height: 31px;
        border-radius: 10px;
    }

    .gc-label {
        letter-spacing: .035em;
    }

    .gc-radio-grid {
        grid-template-columns: 1fr;
    }

    .gc-actions {
        padding: 12px;
    }
}

@media (max-width: 360px) {
    .gc-wrap {
        padding-left: 10px;
        padding-right: 10px;
    }

    .gc-hero-main {
        flex-direction: column;
    }

    .gc-flow-pill {
        width: 100%;
    }

    .gc-flow-pill,
    .gc-btn,
    .gc-add-vehicle {
        justify-content: center;
    }
}

/* Mobile compact workspace */
@media (max-width: 700px) {
    .guest-create-page {
        
    }

    .gc-wrap {
        padding: 10px 10px 16px;
    }

    .gc-breadcrumb {
        width: 100%;
        margin-bottom: 22px;
        gap: 5px;
        overflow-x: auto;
        white-space: nowrap;
        font-size: .66rem;
        scrollbar-width: none;
    }

    .gc-breadcrumb::-webkit-scrollbar {
        display: none;
    }

    .gc-hero {
        gap: 20px;
        margin-bottom: 28px;
        padding: 0 4px 6px;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .gc-hero-main {
        gap: 9px;
    }

    .gc-hero-icon {
        width: 38px;
        height: 38px;
        flex-basis: 38px;
        border-radius: 12px;
        font-size: .88rem;
    }

    .gc-kicker {
        margin-bottom: 1px;
        font-size: .57rem;
        letter-spacing: .12em;
    }

    .gc-title {
        font-size: 1.38rem;
        line-height: .96;
    }

    .gc-subtitle {
        max-width: none;
        margin-top: 3px;
        font-size: .68rem;
        line-height: 1.25;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .gc-flow-pill {
        width: 100%;
        justify-content: center;
        min-height: 34px;
        padding: 7px 9px;
        border-radius: 11px;
        font-size: .63rem;
    }

    .gc-fast-note {
        margin-bottom: 9px;
        padding: 9px 10px;
        border-radius: 13px;
        gap: 8px;
        font-size: .71rem;
        line-height: 1.3;
    }

    .gc-layout,
    .gc-form {
        gap: 9px;
    }

    .gc-section {
        border-radius: 15px;
        box-shadow: 0 10px 24px rgba(24, 33, 46, .07);
    }

    .gc-section-head {
        align-items: center;
        min-height: 0;
        padding: 9px 10px;
        gap: 8px;
    }

    .gc-section-title-wrap {
        align-items: center;
        gap: 8px;
    }

    .gc-section-icon {
        width: 29px;
        height: 29px;
        border-radius: 10px;
        font-size: .78rem;
        flex-basis: 29px;
    }

    .gc-section h2 {
        font-size: .86rem;
        line-height: 1.1;
    }

    .gc-section-sub,
    .gc-field-hint,
    .gc-note,
    .gc-side {
        display: none;
    }

    .gc-legend {
        gap: 6px 12px;
        margin-bottom: 9px;
        padding: 9px 11px;
        border-radius: 12px;
    }

    .gc-legend-item {
        font-size: .66rem;
        gap: 6px;
    }

    .gc-badge {
        padding: 3px 8px;
        font-size: .56rem;
        letter-spacing: .04em;
    }

    .gc-section-body {
        padding: 10px;
    }

    .gc-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .gc-field {
        min-width: 0;
    }

    .gc-field-full {
        grid-column: 1 / -1;
    }

    .gc-label {
        margin-bottom: 4px;
        gap: 4px;
        font-size: .58rem;
        letter-spacing: .045em;
        line-height: 1.1;
    }

    .gc-control {
        min-height: 44px;
        border-radius: 11px;
        padding: 7px 10px;
        font-size: 13px;
        line-height: 1.15;
        font-weight: 650;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .66);
    }

    .gc-control::placeholder {
        font-size: 12px;
        font-weight: 600;
    }

    select.gc-control {
        font-size: 12.5px;
        line-height: 1.15;
        text-overflow: ellipsis;
    }

    .gc-control.has-icon {
        padding-left: 30px;
    }

    .gc-input-wrap i {
        left: 10px;
        font-size: .68rem;
    }

    textarea.gc-control {
        min-height: 76px;
        padding-top: 9px;
        line-height: 1.32;
    }

    .gc-form-error {
        margin-top: 2px;
        font-size: .66rem;
        line-height: 1.25;
    }

    .gc-radio-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .gc-radio-card {
        min-height: 48px;
        padding: 7px 8px;
        border-radius: 12px;
        gap: 5px;
        font-size: .61rem;
        line-height: 1.15;
    }

    .gc-radio-card i {
        font-size: .78rem;
    }

    .gc-document-grid,
    .gc-document-grid-count-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .gc-doc-upload-card {
        min-height: 112px;
        padding: 9px;
        border-radius: 13px;
        gap: 7px;
    }

    .gc-doc-upload-top {
        gap: 7px;
    }

    .gc-doc-upload-icon {
        width: 28px;
        height: 28px;
        border-radius: 9px;
        font-size: .78rem;
        flex-basis: 28px;
    }

    .gc-doc-upload-copy h4 {
        font-size: .72rem;
        line-height: 1.15;
    }

    .gc-doc-upload-copy p {
        display: none;
    }

    .gc-file-control {
        width: 100%;
        max-width: 100%;
        font-size: .61rem;
    }

    .gc-file-control::file-selector-button {
        min-height: 34px;
        margin-right: 7px;
        padding: 0 9px;
        border-radius: 9px;
        font-size: .6rem;
    }

    .gc-doc-file-name {
        min-height: 18px;
        padding: 4px 7px;
        border-radius: 8px;
        font-size: .62rem;
        line-height: 1.2;
    }

    .vehiculo-item {
        margin-bottom: 8px;
        padding: 9px;
        border-radius: 13px;
    }

    .gc-vehicle-head {
        margin-bottom: 8px;
        gap: 8px;
    }

    .gc-vehicle-title {
        gap: 6px;
        font-size: .74rem;
    }

    .gc-delete-vehicle {
        width: 44px;
        height: 44px;
        border-radius: 11px;
        font-size: .82rem;
    }

    .gc-add-vehicle {
        min-height: 44px;
        border-radius: 11px;
        font-size: .72rem;
    }

    .gc-actions {
        display: grid;
        grid-template-columns: minmax(0, .78fr) minmax(0, 1.22fr);
        gap: 8px;
        margin-top: 0;
        padding: 9px;
        border-radius: 15px;
    }

    .gc-btn {
        width: 100%;
        min-height: 44px;
        border-radius: 12px;
        padding: 0 10px;
        font-size: .72rem;
        line-height: 1.1;
    }
}

@media (max-width: 380px) {
    .gc-wrap {
        padding-left: 8px;
        padding-right: 8px;
    }

    .gc-title {
        font-size: 1.22rem;
    }

    .gc-grid,
    .gc-document-grid,
    .gc-document-grid-count-2,
    .gc-radio-grid,
    .gc-actions {
        grid-template-columns: 1fr;
    }

    .gc-actions .gc-btn-primary {
        order: -1;
    }
}

.gc-foreign-block {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px dashed var(--gc-line);
}

.gc-foreign-toggle {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    cursor: pointer;
    user-select: none;
}

.gc-foreign-toggle input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.gc-foreign-check {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 22px;
    height: 22px;
    margin-top: 1px;
    border: 1px solid var(--gc-line);
    border-radius: 7px;
    background: var(--gc-surface-warm);
    color: transparent;
    font-size: .68rem;
    transition: background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
}

.gc-foreign-toggle input:checked ~ .gc-foreign-check {
    border-color: var(--gc-brand);
    background: var(--gc-brand);
    color: #FFFFFF;
    transform: scale(1.03);
}

.gc-foreign-toggle input:focus-visible ~ .gc-foreign-check {
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--gc-accent) 26%, transparent);
}

.gc-foreign-copy {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.gc-foreign-copy strong {
    color: #111827;
    font-size: .85rem;
    font-weight: 800;
}

.gc-foreign-copy small {
    color: var(--gc-muted);
    font-size: .74rem;
    font-weight: 600;
    line-height: 1.4;
}

.gc-foreign-field {
    overflow: hidden;
    max-height: 0;
    margin-top: 0;
    opacity: 0;
    transform: translateY(-4px);
    transition: max-height .28s cubic-bezier(.22,1,.36,1), opacity .22s ease, transform .22s ease, margin-top .22s ease;
}

.gc-foreign-block.is-open .gc-foreign-field {
    max-height: 160px;
    margin-top: 12px;
    opacity: 1;
    transform: translateY(0);
}

@media (prefers-reduced-motion: reduce) {
    .guest-create-page *,
    .guest-create-page *::before,
    .guest-create-page *::after {
        transition: none !important;
        animation: none !important;
    }
}

/* ── Ruta del registro: linea de tiempo de recompensa ── */
.gc-reward-count { color: var(--gc-muted); font-size: .78rem; font-weight: 750; }
.gc-reward-count strong { color: #111827; font-size: 1.05rem; font-weight: 850; }

.gc-reward-bar {
    height: 8px;
    margin-top: 8px;
    border-radius: 999px;
    background: var(--gc-surface-warm);
    border: 1px solid var(--gc-line);
    overflow: hidden;
}

.gc-reward-fill {
    display: block;
    height: 100%;
    width: 0;
    border-radius: inherit;
    position: relative;
    background: linear-gradient(90deg, var(--gc-brand), var(--gc-brand-2));
    transition: width .55s cubic-bezier(.22, 1, .36, 1);
}

.gc-reward-fill::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(100deg, transparent 30%, rgba(255, 255, 255, .5) 50%, transparent 70%);
    transform: translateX(-120%);
}

.gc-reward-fill.is-shine::after { animation: gcShine .9s ease; }
.gc-reward-bar--gold .gc-reward-fill { background: linear-gradient(90deg, var(--gc-accent), var(--gc-accent-dark)); }

.gc-timeline {
    list-style: none;
    position: relative;
    margin: 15px 0 0;
    padding: 0 2px 0 0;
    max-height: 56vh;
    overflow-y: auto;
}

.gc-tl-item {
    position: relative;
    padding: 5px 0;
    z-index: 1;
}

.gc-tl-row {
    display: flex;
    align-items: center;
    gap: 11px;
}

.gc-tl-sub {
    list-style: none;
    margin: 6px 0 2px 12px;
    padding: 3px 0 2px 16px;
    border-left: 2px solid var(--gc-line);
    display: grid;
    gap: 5px;
    transition: border-color .25s ease;
}

.gc-tl-item.is-done .gc-tl-sub { border-left-color: color-mix(in srgb, var(--gc-brand) 32%, var(--gc-line)); }
.gc-tl-item.is-opt.is-done .gc-tl-sub { border-left-color: color-mix(in srgb, var(--gc-accent) 42%, var(--gc-line)); }

.gc-tl-subitem {
    display: flex;
    align-items: center;
    gap: 8px;
}

.gc-tl-subdot {
    width: 11px;
    height: 11px;
    flex: 0 0 auto;
    border-radius: 50%;
    border: 2px solid var(--gc-line);
    background: var(--gc-surface);
    transition: border-color .2s ease, background .2s ease;
}

.gc-tl-subitem.is-done .gc-tl-subdot { border-color: transparent; background: var(--gc-brand); }
.gc-tl-item.is-opt .gc-tl-subitem.is-done .gc-tl-subdot { background: var(--gc-accent); }
.gc-tl-subitem.pop .gc-tl-subdot { animation: gcPop .4s cubic-bezier(.22, 1, .36, 1); }

.gc-tl-sublabel {
    font-size: .72rem;
    font-weight: 650;
    color: var(--gc-muted);
    line-height: 1.25;
}

.gc-tl-subitem.is-done .gc-tl-sublabel { color: #111827; }
.gc-tl-star { color: var(--gc-danger); margin-left: 3px; font-weight: 850; }

.gc-tl-dot {
    width: 26px;
    height: 26px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 2px solid var(--gc-line);
    background: var(--gc-surface);
    color: transparent;
    font-size: .58rem;
    transition: border-color .25s ease, background .25s ease, color .25s ease, box-shadow .25s ease;
}

.gc-tl-item.is-active .gc-tl-dot {
    border-color: var(--gc-accent);
    box-shadow: 0 0 0 4px var(--gc-accent-soft);
}

.gc-tl-item.is-done .gc-tl-dot {
    border-color: transparent;
    color: #FFFFFF;
    background: linear-gradient(135deg, var(--gc-brand), var(--gc-brand-2));
}

.gc-tl-item.is-opt.is-done .gc-tl-dot { background: linear-gradient(135deg, var(--gc-accent), var(--gc-accent-dark)); }

.gc-tl-label {
    background: none;
    border: 0;
    padding: 0;
    text-align: left;
    cursor: pointer;
    color: var(--gc-muted);
    font-size: .8rem;
    font-weight: 750;
    line-height: 1.25;
    transition: color .2s ease;
}

.gc-tl-item.is-done .gc-tl-label { color: #111827; }
.gc-tl-label:hover { color: var(--gc-accent-dark); }

.gc-tl-tag {
    margin-left: 6px;
    font-size: .6rem;
    font-weight: 850;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--gc-accent-dark);
    opacity: .72;
}

.gc-tl-item.pop .gc-tl-dot { animation: gcPop .45s cubic-bezier(.22, 1, .36, 1); }
@keyframes gcPop { 0% { transform: scale(1); } 42% { transform: scale(1.3); } 100% { transform: scale(1); } }

.gc-reward-bonus {
    margin-top: 15px;
    padding-top: 13px;
    border-top: 1px dashed var(--gc-line);
}

.gc-reward-bonus[hidden] { display: none; }

.gc-bonus-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: var(--gc-accent-dark);
    font-size: .72rem;
    font-weight: 850;
    letter-spacing: .03em;
}

.gc-bonus-top i { margin-right: 5px; }

.gc-bonus-note {
    margin: 8px 0 0;
    color: var(--gc-muted);
    font-size: .72rem;
    font-weight: 600;
    line-height: 1.4;
}

.gc-reward-ready {
    margin-top: 14px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 13px;
    border-radius: 999px;
    background: var(--gc-success-bg);
    color: var(--gc-success);
    font-size: .75rem;
    font-weight: 850;
    animation: gcReadyIn .4s cubic-bezier(.22, 1, .36, 1);
}

.gc-reward-ready[hidden] { display: none; }
@keyframes gcReadyIn { from { opacity: 0; transform: translateY(6px) scale(.96); } to { opacity: 1; transform: none; } }

.gc-reward.is-complete {
    box-shadow: 0 0 0 1px var(--gc-accent-line), 0 16px 34px -20px color-mix(in srgb, var(--gc-brand) 45%, transparent);
}

.gc-spark {
    position: absolute;
    left: 5px;
    top: 50%;
    width: 16px;
    height: 16px;
    margin-top: -8px;
    pointer-events: none;
    color: var(--gc-accent);
    font-size: .7rem;
    display: grid;
    place-items: center;
    animation: gcSpark .75s ease forwards;
}

@keyframes gcSpark {
    0% { opacity: 0; transform: scale(.4) rotate(0); }
    40% { opacity: 1; transform: scale(1.2) rotate(90deg); }
    100% { opacity: 0; transform: scale(.6) rotate(160deg); }
}

.gc-btn-primary { position: relative; overflow: hidden; }

.gc-btn-primary.gc-shine::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, transparent 30%, rgba(255, 255, 255, .55) 50%, transparent 70%);
    transform: translateX(-120%);
    animation: gcShine .95s ease;
}

@keyframes gcShine { to { transform: translateX(120%); } }

/* Cinta de progreso movil (el panel lateral se oculta en <=700px) */
.gc-progress-mobile { display: none; }

@media (max-width: 700px) {
    .gc-progress-mobile {
        display: flex;
        align-items: center;
        gap: 10px;
        position: sticky;
        top: 6px;
        z-index: 30;
        margin: 0 0 10px;
        padding: 8px 11px;
        border: 1px solid var(--gc-line);
        border-radius: 12px;
        background: color-mix(in srgb, var(--gc-surface) 90%, transparent);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 20px -14px rgba(24, 33, 46, .5);
    }

    .gc-pm-bar {
        flex: 1;
        height: 7px;
        border-radius: 999px;
        background: var(--gc-surface-warm);
        border: 1px solid var(--gc-line);
        overflow: hidden;
    }

    .gc-pm-bar span {
        display: block;
        height: 100%;
        width: 0;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--gc-brand), var(--gc-brand-2));
        transition: width .55s cubic-bezier(.22, 1, .36, 1);
    }

    .gc-pm-label {
        font-size: .66rem;
        font-weight: 850;
        color: var(--gc-muted);
        white-space: nowrap;
    }

    .gc-pm-label.is-ready { color: var(--gc-success); }
    .gc-pm-label i { margin-right: 3px; }
}
</style>

<div class="guest-create-page">
    <div class="gc-wrap">
        <div class="gc-breadcrumb">
            <a href="<?= url('huespedes') ?>">
                <i class="fas fa-users"></i>
                Huespedes
            </a>
            <i class="fas fa-chevron-right"></i>
            <span>Nuevo registro</span>
        </div>

        <header class="gc-hero">
            <div class="gc-hero-main">
                <div class="gc-hero-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div>
                    <p class="gc-kicker">Registro de huesped</p>
                    <h1 class="gc-title">Crear nuevo <span class="gc-title-accent">huesped</span></h1>
                    <p class="gc-subtitle">
                        Captura los datos principales del huesped, su procedencia y vehiculos para dejar listo el expediente operativo.
                    </p>
                </div>
            </div>
            <span class="gc-flow-pill">
                <i class="fas fa-clipboard-check"></i>
                <?= $es_reservacion_rapida ? 'Continuara a reservacion rapida' : 'Alta directa de huesped' ?>
            </span>
        </header>

        <?php if ($es_reservacion_rapida): ?>
            <div class="gc-fast-note">
                <i class="fas fa-info-circle"></i>
                <p class="text-sm font-semibold leading-relaxed">
                    Registre el nuevo huesped. Al guardar, continuara con la reservacion de la habitacion seleccionada.
                </p>
            </div>
        <?php endif; ?>

        <div class="gc-legend" role="note" aria-label="Que campos son obligatorios">
            <span class="gc-legend-item"><span class="gc-badge gc-badge-req"><i class="fas fa-asterisk"></i> Obligatorio</span> secciones que debes completar</span>
            <span class="gc-legend-item"><span class="gc-badge gc-badge-opt"><i class="far fa-circle"></i> Opcional</span> puedes dejarlas en blanco</span>
            <span class="gc-legend-item"><span class="gc-required">*</span> campo obligatorio dentro de la seccion</span>
        </div>

        <div class="gc-progress-mobile" id="gcProgressMobile" role="status" aria-live="polite">
            <div class="gc-pm-bar"><span id="gcPmFill"></span></div>
            <span class="gc-pm-label" id="gcPmLabel">0/0 campos</span>
        </div>

        <form method="POST" action="<?= url('huespedes/store') . ($return_to ? '?return_to=' . urlencode($return_to) . $reservacion_rapida_params : '') ?>" class="gc-form" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="gc-layout">
                <main class="gc-form">
                    <section class="gc-section">
                        <div class="gc-section-head">
                            <div class="gc-section-title-wrap">
                                <span class="gc-section-icon"><i class="fas fa-user"></i></span>
                                <div>
                                    <h2>Informacion personal</h2>
                                    <p class="gc-section-sub">Datos de contacto y nombre legal para el expediente.</p>
                                </div>
                            </div>
                            <?= $gcSectionBadge(true) ?>
                        </div>

                        <div class="gc-section-body">
                            <div class="gc-grid">
                                <div class="gc-field gc-field-full">
                                    <label class="gc-label">
                                        Nombre completo <span class="gc-required">*</span>
                                    </label>
                                    <input type="text"
                                           name="nombre_completo"
                                           value="<?= old('nombre_completo') ?>"
                                           required
                                           placeholder="Ingrese el nombre completo del huesped"
                                           class="gc-control">
                                    <?php if (form_error('nombre_completo')): ?>
                                        <span class="gc-form-error"><?= form_error('nombre_completo') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="gc-field">
                                    <label class="gc-label">Telefono celular<?= $gcRequiredMark('telefono') ?></label>
                                    <div class="gc-input-wrap">
                                        <i class="fas fa-phone"></i>
                                        <input type="tel"
                                               name="telefono"
                                               value="<?= old('telefono') ?>"
                                               placeholder="10 digitos"
                                               class="gc-control has-icon"
                                               <?= $gcGuestFieldRequired('telefono') ? 'required' : '' ?>>
                                    </div>
                                    <?php if (form_error('telefono')): ?>
                                        <span class="gc-form-error"><?= form_error('telefono') ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($gcGuestFieldVisible('email')): ?>
                                    <div class="gc-field">
                                        <label class="gc-label">Email<?= $gcRequiredMark('email') ?></label>
                                        <div class="gc-input-wrap">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email"
                                                   name="email"
                                                   value="<?= old('email') ?>"
                                                   placeholder="correo@ejemplo.com"
                                                   class="gc-control has-icon"
                                                   <?= $gcGuestFieldRequired('email') ? 'required' : '' ?>>
                                        </div>
                                        <?php if (form_error('email')): ?>
                                            <span class="gc-form-error"><?= form_error('email') ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('descuentos')): ?>
                    <section class="gc-section">
                        <div class="gc-section-head">
                            <div class="gc-section-title-wrap">
                                <span class="gc-section-icon"><i class="fas fa-tags"></i></span>
                                <div>
                                    <h2>Descuento del huesped</h2>
                                    <p class="gc-section-sub">Opcional. Se aplica automaticamente al cotizar reservaciones de este huesped (ajustable en cada reservacion).</p>
                                </div>
                            </div>
                            <?= $gcSectionBadge(false) ?>
                        </div>
                        <div class="gc-section-body">
                            <div class="gc-grid">
                                <div class="gc-field">
                                    <label class="gc-label">Tipo de descuento</label>
                                    <div class="gc-input-wrap">
                                        <i class="fas fa-percent"></i>
                                        <select name="descuento_tipo" class="gc-control has-icon">
                                            <option value="">Sin descuento</option>
                                            <option value="porcentaje" <?= old('descuento_tipo') === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
                                            <option value="monto" <?= old('descuento_tipo') === 'monto' ? 'selected' : '' ?>>Monto fijo ($)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="gc-field">
                                    <label class="gc-label">Valor</label>
                                    <div class="gc-input-wrap">
                                        <i class="fas fa-tag"></i>
                                        <input type="number" name="descuento_valor" min="0" step="0.01"
                                               value="<?= old('descuento_valor') ?>"
                                               placeholder="Ej. 10"
                                               class="gc-control has-icon">
                                    </div>
                                    <span class="gc-field-hint">Porcentaje (ej. 10 = 10%) o pesos, segun el tipo elegido.</span>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ($gcGuestFieldVisible('procedencia_estado') || $gcGuestFieldVisible('procedencia_ciudad') || $gcGuestFieldVisible('nacionalidad')): ?>
                    <section class="gc-section">
                        <div class="gc-section-head">
                            <div class="gc-section-title-wrap">
                                <span class="gc-section-icon"><i class="fas fa-map-marked-alt"></i></span>
                                <div>
                                    <h2>Procedencia</h2>
                                    <p class="gc-section-sub">Origen del huesped para reportes y seguimiento.</p>
                                </div>
                            </div>
                            <?= $gcSectionBadge($gcGuestFieldRequired('procedencia_estado') || $gcGuestFieldRequired('procedencia_ciudad') || $gcGuestFieldRequired('nacionalidad')) ?>
                        </div>

                        <div class="gc-section-body">
                            <div class="gc-grid">
                                <?php if ($gcGuestFieldVisible('procedencia_estado')): ?>
                                    <div class="gc-field">
                                        <label class="gc-label">Estado<?= $gcRequiredMark('procedencia_estado') ?></label>
                                        <select name="procedencia_estado"
                                                class="gc-control"
                                                <?= $gcGuestFieldRequired('procedencia_estado') ? 'required' : '' ?>>
                                            <option value="">Seleccione un estado</option>
                                            <?php foreach ($estados as $estado): ?>
                                                <option value="<?= $estado ?>" <?= old('procedencia_estado') == $estado ? 'selected' : '' ?>>
                                                    <?= $estado ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (form_error('procedencia_estado')): ?>
                                            <span class="gc-form-error"><?= form_error('procedencia_estado') ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($gcGuestFieldVisible('procedencia_ciudad')): ?>
                                    <div class="gc-field">
                                        <label class="gc-label">Ciudad<?= $gcRequiredMark('procedencia_ciudad') ?></label>
                                        <input type="text"
                                               name="procedencia_ciudad"
                                               value="<?= old('procedencia_ciudad') ?>"
                                               placeholder="Ciudad de origen"
                                               class="gc-control"
                                               <?= $gcGuestFieldRequired('procedencia_ciudad') ? 'required' : '' ?>>
                                        <?php if (form_error('procedencia_ciudad')): ?>
                                            <span class="gc-form-error"><?= form_error('procedencia_ciudad') ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($gcGuestFieldVisible('nacionalidad')): ?>
                                <?php
                                    $gcNacValue = $gcOldExtra('nacionalidad');
                                    $gcNacRequired = $gcGuestFieldRequired('nacionalidad');
                                    $gcNacOpen = $gcNacRequired || $gcNacValue !== '';
                                ?>
                                <div class="gc-foreign-block<?= $gcNacOpen ? ' is-open' : '' ?>" data-foreign-block>
                                    <?php if (!$gcNacRequired): ?>
                                        <label class="gc-foreign-toggle">
                                            <input type="checkbox" data-foreign-toggle <?= $gcNacOpen ? 'checked' : '' ?>>
                                            <span class="gc-foreign-check"><i class="fas fa-check"></i></span>
                                            <span class="gc-foreign-copy">
                                                <strong>El huesped es extranjero</strong>
                                                <small>Activalo para registrar su nacionalidad. Aparece en el reporte de procedencia internacional.</small>
                                            </span>
                                        </label>
                                    <?php endif; ?>
                                    <div class="gc-field gc-field-full gc-foreign-field" data-foreign-field>
                                        <label class="gc-label">Nacionalidad<?= $gcNacRequired ? ' <span class="gc-required">*</span>' : '' ?></label>
                                        <div class="gc-input-wrap">
                                            <i class="fas fa-earth-americas"></i>
                                            <input type="text"
                                                   name="extras[nacionalidad]"
                                                   value="<?= htmlspecialchars($gcNacValue, ENT_QUOTES, 'UTF-8') ?>"
                                                   maxlength="80"
                                                   placeholder="Estadounidense, canadiense, espanola..."
                                                   class="gc-control has-icon"
                                                   data-foreign-input
                                                   <?= $gcNacRequired ? 'required' : '' ?>>
                                        </div>
                                        <?php if (form_error('extras[nacionalidad]')): ?>
                                            <span class="gc-form-error"><?= form_error('extras[nacionalidad]') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php
                    $gcExtraGuestFields = array_filter($gcGuestVisibleFields('guest'), function ($definition, $key) {
                        if ($key === 'nacionalidad') {
                            return false; // se captura en la seccion Procedencia (bloque extranjero)
                        }
                        return in_array(($definition['storage'] ?? 'column'), ['extra', 'document'], true);
                    }, ARRAY_FILTER_USE_BOTH);
                    ?>
                    <?php if (!empty($gcExtraGuestFields)): ?>
                        <?php
                        $gcExtraGuestRequired = false;
                        foreach ($gcExtraGuestFields as $gcExtraKey => $gcExtraDef) {
                            if ($gcGuestFieldRequired($gcExtraKey)) { $gcExtraGuestRequired = true; break; }
                        }
                        ?>
                        <section class="gc-section">
                            <div class="gc-section-head">
                                <div class="gc-section-title-wrap">
                                    <span class="gc-section-icon"><i class="fas fa-id-card"></i></span>
                                    <div>
                                        <h2>Datos adicionales</h2>
                                        <p class="gc-section-sub">Campos definidos por la configuracion de este hotel.</p>
                                    </div>
                                </div>
                                <?= $gcSectionBadge($gcExtraGuestRequired) ?>
                            </div>

                            <div class="gc-section-body">
                                <div class="gc-grid">
                                    <?php foreach ($gcExtraGuestFields as $fieldKey => $fieldDefinition): ?>
                                        <?= $gcRenderGuestExtraField($fieldKey, $fieldDefinition) ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php /* Seccion de documentos solo si el hotel configuro visible el archivo de identificacion (unico campo documental de la politica). Sin documento visible => se esconde. */ ?>
                    <?php if ($gcGuestFieldVisible('identificacion_archivo')): ?>
                    <?php
                    $gcInitialDocumentUploads = [];
                    if (!$gcGuestFieldVisible('identificacion_archivo')) {
                        $gcInitialDocumentUploads[] = [
                            'tipo' => 'identificacion',
                            'icon' => 'fa-id-card',
                            'title' => 'INE / identificacion',
                            'copy' => 'Foto o PDF del documento oficial.',
                            'description' => 'Documento de identificacion cargado desde el alta inicial.',
                        ];
                    }
                    $gcInitialDocumentUploads[] = [
                        'tipo' => 'comprobante',
                        'icon' => 'fa-receipt',
                        'title' => 'Comprobante',
                        'copy' => 'Comprobante de domicilio, pago u otro soporte.',
                        'description' => 'Comprobante cargado desde el alta inicial.',
                    ];
                    $gcInitialDocumentUploads[] = [
                        'tipo' => 'otro',
                        'icon' => 'fa-file-alt',
                        'title' => 'Otro documento',
                        'copy' => 'Cualquier archivo adicional del expediente.',
                        'description' => 'Documento adicional cargado desde el alta inicial.',
                    ];
                    ?>
                    <section class="gc-section">
                        <div class="gc-section-head">
                            <div class="gc-section-title-wrap">
                                <span class="gc-section-icon"><i class="fas fa-folder-open"></i></span>
                                <div>
                                    <h2>Documentos iniciales</h2>
                                    <p class="gc-section-sub">Adjuntos opcionales vinculados al expediente desde el primer registro.</p>
                                </div>
                            </div>
                            <?= $gcSectionBadge(false) ?>
                        </div>

                        <div class="gc-section-body">
                            <div class="gc-document-grid gc-document-grid-count-<?= count($gcInitialDocumentUploads) ?>">
                                <?php foreach ($gcInitialDocumentUploads as $docIndex => $docUpload): ?>
                                    <?php
                                    $docFileLabelId = 'gc-doc-file-' . (int)$docIndex;
                                    $docTipo = htmlspecialchars((string)$docUpload['tipo'], ENT_QUOTES, 'UTF-8');
                                    $docTitle = htmlspecialchars((string)$docUpload['title'], ENT_QUOTES, 'UTF-8');
                                    $docCopy = htmlspecialchars((string)$docUpload['copy'], ENT_QUOTES, 'UTF-8');
                                    $docIcon = htmlspecialchars((string)$docUpload['icon'], ENT_QUOTES, 'UTF-8');
                                    $docDescription = htmlspecialchars((string)$docUpload['description'], ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <div class="gc-doc-upload-card">
                                        <div class="gc-doc-upload-top">
                                            <span class="gc-doc-upload-icon"><i class="fas <?= $docIcon ?>"></i></span>
                                            <div class="gc-doc-upload-copy">
                                                <h4><?= $docTitle ?></h4>
                                                <p><?= $docCopy ?></p>
                                            </div>
                                        </div>

                                        <input type="hidden" name="documentos_huesped[tipo][]" value="<?= $docTipo ?>">
                                        <input type="hidden" name="documentos_huesped[titulo][]" value="">
                                        <input type="hidden" name="documentos_huesped[descripcion][]" value="<?= $docDescription ?>">
                                        <input type="file"
                                               name="documentos_huesped[archivo][]"
                                               accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                                               class="gc-control gc-file-control gc-document-input"
                                               data-file-label="<?= $docFileLabelId ?>">
                                        <p class="gc-doc-file-name" id="<?= $docFileLabelId ?>">Sin archivo seleccionado</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <p class="gc-note">
                                <i class="fas fa-info-circle"></i>
                                Puede guardar el huesped sin documentos. Si adjunta archivos, quedaran ligados al mismo expediente y hotel.
                            </p>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if (!empty($gcVehicleVisibleFields) && (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('vehiculos'))): ?>
                    <section class="gc-section">
                        <div class="gc-section-head">
                            <div class="gc-section-title-wrap">
                                <span class="gc-section-icon"><i class="fas fa-car"></i></span>
                                <div>
                                    <h2>Vehiculos</h2>
                                    <p class="gc-section-sub">Autos asociados al huesped para control de estacionamiento.</p>
                                </div>
                            </div>
                            <?= $gcSectionBadge(false) ?>
                        </div>

                        <div class="gc-section-body">
                            <div id="vehiculos-container">
                                <?php foreach ($gcVehicleRows as $vehicleIndex => $vehicleValues): ?>
                                    <?php $vehicleValues = is_array($vehicleValues) ? $vehicleValues : []; ?>
                                    <div class="vehiculo-item">
                                        <div class="gc-vehicle-head">
                                            <h4 class="gc-vehicle-title">
                                                <i class="fas fa-car-side"></i>
                                                Vehiculo <?= (int)$vehicleIndex + 1 ?>
                                            </h4>
                                            <button type="button" onclick="eliminarVehiculo(this)" class="gc-delete-vehicle <?= count($gcVehicleRows) === 1 ? 'hidden' : '' ?>" title="Eliminar vehiculo">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        <div class="gc-grid">
                                            <?= $gcRenderVehicleFields((string)$vehicleIndex, $vehicleValues) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" onclick="agregarVehiculo()" class="gc-add-vehicle">
                                <i class="fas fa-plus-circle"></i>
                                Agregar otro vehiculo
                            </button>

                            <p class="gc-note">
                                <i class="fas fa-info-circle"></i>
                                Puede registrar multiples vehiculos por huesped. Si no tiene vehiculo, deje los campos en blanco; los campos obligatorios del vehiculo solo aplican si captura algun dato.
                            </p>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ($gcGuestFieldVisible('notas')): ?>
                    <section class="gc-section">
                        <div class="gc-section-head">
                            <div class="gc-section-title-wrap">
                                <span class="gc-section-icon"><i class="fas fa-sticky-note"></i></span>
                                <div>
                                    <h2>Notas adicionales</h2>
                                    <p class="gc-section-sub">Observaciones utiles para recepcion y futuras reservaciones.</p>
                                </div>
                            </div>
                            <?= $gcSectionBadge($gcGuestFieldRequired('notas')) ?>
                        </div>

                        <div class="gc-section-body">
                            <label class="gc-label">Notas<?= $gcRequiredMark('notas') ?></label>
                            <textarea name="notas"
                                      rows="4"
                                      placeholder="Cualquier informacion adicional sobre el huesped..."
                                      class="gc-control"
                                      <?= $gcGuestFieldRequired('notas') ? 'required' : '' ?>><?= old('notas') ?></textarea>
                            <?php if (form_error('notas')): ?>
                                <span class="gc-form-error"><?= form_error('notas') ?></span>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </main>

                <aside class="gc-side">
                    <div class="gc-side-card gc-reward" id="gcReward">
                        <h3>
                            <span class="gc-side-icon"><i class="fas fa-flag-checkered"></i></span>
                            Ruta del registro
                        </h3>
                        <div class="gc-reward-count"><strong id="gcReqDone">0</strong> de <span id="gcReqTotal">0</span> campos obligatorios</div>
                        <div class="gc-reward-bar"><span class="gc-reward-fill" id="gcReqFill"></span></div>
                        <ol class="gc-timeline" id="gcTimeline"></ol>
                        <div class="gc-reward-bonus" id="gcBonus" hidden>
                            <div class="gc-bonus-top">
                                <span><i class="fas fa-star"></i> Datos extra</span>
                                <span id="gcBonusPct">0%</span>
                            </div>
                            <div class="gc-reward-bar gc-reward-bar--gold"><span class="gc-reward-fill" id="gcBonusFill"></span></div>
                            <p class="gc-bonus-note">Cada dato ayuda a reconocer al huesped después.</p>
                        </div>
                        <div class="gc-reward-ready" id="gcReadyPill" hidden><i class="fas fa-check-circle"></i> Listo para guardar</div>
                    </div>

                    <div class="gc-side-card">
                        <div class="gc-mini-card">
                            <p class="gc-mini-label">Flujo actual</p>
                            <p class="gc-mini-text">
                                <?= $es_reservacion_rapida ? 'Despues de guardar volveras al flujo de habitacion seleccionada.' : 'Despues de guardar quedara disponible para reservar.' ?>
                            </p>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="gc-actions">
                <a href="<?= $cancel_url ?>"
                   class="gc-btn gc-btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="submit"
                        class="gc-btn gc-btn-primary">
                    <i class="fas fa-save"></i>
                    <?= $es_reservacion_rapida ? 'Guardar y Continuar' : 'Registrar Huesped' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let vehiculoIndex = <?= count($gcVehicleRows) ?>;
const vehicleFieldsTemplate = <?= json_encode($gcVehicleFieldsTemplate, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function controlVehiculoCuentaComoDato(control) {
    if (!control || control.disabled || !control.name || !control.name.includes('vehiculos[')) {
        return false;
    }

    if (control.name.includes('[estacionamiento]')) {
        return false;
    }

    if (control.type === 'radio' || control.type === 'checkbox') {
        return control.checked && String(control.value || '').trim() !== '';
    }

    if (control.type === 'file') {
        return control.files && control.files.length > 0;
    }

    return String(control.value || '').trim() !== '';
}

function vehiculoTieneDatosCapturados(vehiculo) {
    return Array.from(vehiculo.querySelectorAll('input, select, textarea')).some(controlVehiculoCuentaComoDato);
}

function sincronizarVehiculoOpcional(vehiculo) {
    if (!vehiculo) return;

    const tieneDatos = vehiculoTieneDatosCapturados(vehiculo);
    vehiculo.classList.toggle('is-empty', !tieneDatos);

    vehiculo.querySelectorAll('[data-vehicle-required="1"]').forEach(function(control) {
        control.required = tieneDatos;
        if (tieneDatos) {
            control.setAttribute('aria-required', 'true');
        } else {
            control.removeAttribute('aria-required');
        }
    });
}

function sincronizarVehiculosOpcionales(root = document) {
    root.querySelectorAll('.vehiculo-item').forEach(sincronizarVehiculoOpcional);
}

document.querySelectorAll('.gc-document-input').forEach(function(input) {
    input.addEventListener('change', function() {
        const label = document.getElementById(this.dataset.fileLabel || '');
        if (!label) {
            return;
        }

        const fileName = this.files && this.files.length ? this.files[0].name : '';
        label.textContent = fileName || 'Sin archivo seleccionado';
        label.classList.toggle('has-file', fileName !== '');
    });
});

// Funcion para agregar vehiculo
function agregarVehiculo() {
    const container = document.getElementById('vehiculos-container');
    const vehicleFieldsHtml = vehicleFieldsTemplate.replace(/__INDEX__/g, String(vehiculoIndex));
    const vehiculoHtml = `
        <div class="vehiculo-item animate-fadeIn">
            <div class="gc-vehicle-head">
                <h4 class="gc-vehicle-title">
                    <i class="fas fa-car-side"></i>
                    Vehiculo ${vehiculoIndex + 1}
                </h4>
                <button type="button" onclick="eliminarVehiculo(this)" class="gc-delete-vehicle" title="Eliminar vehiculo">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <div class="gc-grid">
                ${vehicleFieldsHtml}
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', vehiculoHtml);
    sincronizarVehiculoOpcional(container.lastElementChild);
    vehiculoIndex++;

    // Mostrar boton de eliminar en el primer vehiculo si hay mas de uno
    const vehiculos = container.querySelectorAll('.vehiculo-item');
    if (vehiculos.length > 1) {
        vehiculos[0].querySelector('button').classList.remove('hidden');
    }
}

// Funcion para eliminar vehiculo
function eliminarVehiculo(button) {
    const vehiculoItem = button.closest('.vehiculo-item');
    vehiculoItem.style.opacity = '0';
    vehiculoItem.style.transform = 'scale(0.98)';
    setTimeout(() => {
        vehiculoItem.remove();

        // Actualizar numeracion
        const vehiculos = document.querySelectorAll('.vehiculo-item');
        vehiculos.forEach((vehiculo, index) => {
            vehiculo.querySelector('h4').innerHTML = `<i class="fas fa-car-side"></i> Vehiculo ${index + 1}`;
        });

        // Ocultar boton de eliminar si solo queda un vehiculo
        if (vehiculos.length === 1) {
            vehiculos[0].querySelector('button').classList.add('hidden');
        }
    }, 300);
}

// Formatear telefono mientras se escribe
document.querySelector('input[name="telefono"]')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) {
        value = value.slice(0, 10);
    }
    e.target.value = value;
});

document.querySelectorAll('input[type="tel"][data-max-digits]').forEach(function(input) {
    input.addEventListener('input', function(e) {
        const maxDigits = parseInt(e.target.dataset.maxDigits || '0', 10);
        let value = e.target.value.replace(/\D/g, '');
        if (maxDigits > 0 && value.length > maxDigits) {
            value = value.slice(0, maxDigits);
        }
        e.target.value = value;
    });
});

// Convertir placas a mayusculas en todos los campos
document.addEventListener('input', function(e) {
    if (e.target.name && e.target.name.includes('[placas]')) {
        e.target.value = e.target.value.toUpperCase();
    }

    if (e.target.name && e.target.name.includes('vehiculos[')) {
        sincronizarVehiculoOpcional(e.target.closest('.vehiculo-item'));
    }
});

document.addEventListener('change', function(e) {
    if (e.target.name && e.target.name.includes('vehiculos[')) {
        sincronizarVehiculoOpcional(e.target.closest('.vehiculo-item'));
    }
});

// Validacion del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    const vehiculos = document.querySelectorAll('.vehiculo-item');
    vehiculos.forEach(sincronizarVehiculoOpcional);

    const vehiculoInvalido = this.querySelector('.vehiculo-item [data-vehicle-required="1"]:invalid');
    if (vehiculoInvalido) {
        e.preventDefault();
        vehiculoInvalido.closest('.vehiculo-item')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        vehiculoInvalido.reportValidity();
        return;
    }

});

sincronizarVehiculosOpcionales();

// Bloque "huesped extranjero": el toggle revela y limpia el campo nacionalidad
document.querySelectorAll('[data-foreign-block]').forEach(function(block) {
    const toggle = block.querySelector('[data-foreign-toggle]');
    const input = block.querySelector('[data-foreign-input]');
    if (!toggle || !input) {
        return; // campo obligatorio: siempre visible, sin toggle
    }

    function sync(clearWhenClosed) {
        const abierto = toggle.checked;
        block.classList.toggle('is-open', abierto);
        if (!abierto && clearWhenClosed) {
            input.value = '';
        }
        if (abierto) {
            input.focus();
        }
    }

    toggle.addEventListener('change', function() { sync(true); });
});

// ── Ruta del registro: linea de tiempo con subcampos + barra por campo ──
// Progreso a nivel CAMPO: con solo poner el nombre la barra ya avanza. Cada
// seccion muestra sus subcampos y su check se enciende al llenarlos.
(function(){
    var page = document.querySelector('.guest-create-page');
    if (!page) return;
    var formEl = page.querySelector('form') || page;

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var timelineEl = document.getElementById('gcTimeline');
    var reqDoneEl  = document.getElementById('gcReqDone');
    var reqTotalEl = document.getElementById('gcReqTotal');
    var reqFillEl  = document.getElementById('gcReqFill');
    var bonusWrap  = document.getElementById('gcBonus');
    var bonusPctEl = document.getElementById('gcBonusPct');
    var bonusFillEl= document.getElementById('gcBonusFill');
    var readyPill  = document.getElementById('gcReadyPill');
    var rewardCard = document.getElementById('gcReward');
    var pmFill     = document.getElementById('gcPmFill');
    var pmLabel    = document.getElementById('gcPmLabel');

    function isFilled(c){
        if (!c || !c.name || c.disabled) return false;
        var t = c.type;
        if (t === 'hidden' || t === 'button' || t === 'submit' || t === 'reset') return false;
        if (c.name.indexOf('[estacionamiento]') !== -1) return false; // radio de estacionamiento viene marcado por defecto
        if (t === 'radio' || t === 'checkbox') return !!c.checked && String(c.value || '').trim() !== '';
        if (t === 'file') return !!(c.files && c.files.length);
        return String(c.value || '').trim() !== '';
    }
    // Campos rastreables de una seccion (uno por control real que el usuario llena)
    function trackable(sec){
        return Array.prototype.slice.call(sec.querySelectorAll('input,select,textarea')).filter(function(c){
            if (!c.name || c.disabled) return false;
            var t = c.type;
            if (t === 'hidden' || t === 'button' || t === 'submit' || t === 'reset') return false;
            if (c.name.indexOf('[estacionamiento]') !== -1) return false;
            return true;
        });
    }
    function clean(txt){ return (txt || '').replace(/\*/g, '').replace(/\s+/g, ' ').trim(); }
    function fieldLabel(c){
        var f = c.closest('.gc-field');
        if (f){ var l = f.querySelector('.gc-label'); if (l) return clean(l.textContent); }
        var doc = c.closest('.gc-doc-upload-card');
        if (doc){ var h = doc.querySelector('h4'); if (h) return clean(h.textContent); }
        // Sin .gc-field: buscar la .gc-label que precede al control (ej. Notas)
        var el = c;
        while (el && el !== formEl){
            var p = el.previousElementSibling;
            while (p){
                if (p.classList && p.classList.contains('gc-label')) return clean(p.textContent);
                var inner = p.querySelector && p.querySelector('.gc-label');
                if (inner) return clean(inner.textContent);
                p = p.previousElementSibling;
            }
            el = el.parentElement;
        }
        return clean(c.name);
    }
    function spark(li){
        var s = document.createElement('span');
        s.className = 'gc-spark';
        s.innerHTML = '<i class="fas fa-star"></i>';
        li.appendChild(s);
        s.addEventListener('animationend', function(){ if (s.parentNode) s.remove(); });
        setTimeout(function(){ if (s.parentNode) s.remove(); }, 1000);
    }
    function pop(el){ if (reduce) return; el.classList.remove('pop'); void el.offsetWidth; el.classList.add('pop'); }
    function setBar(el, ratio){ if (el) el.style.width = (Math.max(0, Math.min(1, ratio)) * 100) + '%'; }

    var sectionNodes = [];   // { sec, required, li, dot, subItems:[] }
    var fields = [];         // { control, li, fieldReq, done }
    var reqTotal = 0, optTotal = 0;

    function build(){
        if (!timelineEl) return;
        timelineEl.innerHTML = '';
        sectionNodes = [];
        fields = [];

        var sections = Array.prototype.slice.call(page.querySelectorAll('.gc-section')).filter(function(s){
            return s.querySelector('h2') && s.querySelector('.gc-badge');
        });
        // Espejar el orden visual del formulario (CSS `order`): obligatorias arriba,
        // Informacion personal primera. Array.sort es estable → conserva el orden de origen en empates.
        sections.sort(function(a, b){
            return (parseInt(getComputedStyle(a).order, 10) || 0) - (parseInt(getComputedStyle(b).order, 10) || 0);
        });

        sections.forEach(function(sec){
            var required = !!sec.querySelector('.gc-badge-req');
            var label = (sec.querySelector('h2').textContent || '').trim();

            var li = document.createElement('li');
            li.className = 'gc-tl-item' + (required ? '' : ' is-opt');

            var row = document.createElement('div');
            row.className = 'gc-tl-row';
            var dot = document.createElement('span');
            dot.className = 'gc-tl-dot';
            dot.innerHTML = '<i class="fas fa-check"></i>';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gc-tl-label';
            btn.textContent = label;
            if (!required){
                var tag = document.createElement('span');
                tag.className = 'gc-tl-tag';
                tag.textContent = 'extra';
                btn.appendChild(tag);
            }
            btn.addEventListener('click', function(){
                sec.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
                var f = sec.querySelector('input,select,textarea');
                if (f) { try { f.focus({ preventScroll: true }); } catch (e) {} }
            });
            row.appendChild(dot);
            row.appendChild(btn);
            li.appendChild(row);

            var controls = trackable(sec);
            var subItems = [];
            if (controls.length){
                var ul = document.createElement('ul');
                ul.className = 'gc-tl-sub';
                controls.forEach(function(c){
                    var fieldReq = c.hasAttribute('required');
                    var sub = document.createElement('li');
                    sub.className = 'gc-tl-subitem';
                    var sdot = document.createElement('span');
                    sdot.className = 'gc-tl-subdot';
                    var slabel = document.createElement('span');
                    slabel.className = 'gc-tl-sublabel';
                    slabel.textContent = fieldLabel(c);
                    if (fieldReq){
                        var star = document.createElement('span');
                        star.className = 'gc-tl-star';
                        star.textContent = '*';
                        slabel.appendChild(star);
                    }
                    sub.appendChild(sdot);
                    sub.appendChild(slabel);
                    ul.appendChild(sub);
                    var item = { control: c, li: sub, fieldReq: fieldReq, done: false };
                    subItems.push(item);
                    fields.push(item);
                });
                li.appendChild(ul);
            }

            timelineEl.appendChild(li);
            sectionNodes.push({ sec: sec, required: required, li: li, dot: dot, subItems: subItems });
        });

        reqTotal = fields.filter(function(f){ return f.fieldReq; }).length;
        optTotal = fields.length - reqTotal;
        if (reqTotalEl) reqTotalEl.textContent = reqTotal;
        if (bonusWrap) bonusWrap.hidden = optTotal === 0;
    }

    var prevReqDone = 0, prevAllReq = false;

    function refresh(animate){
        // Estado por campo
        fields.forEach(function(f){
            var d = isFilled(f.control);
            if (d !== f.done){
                f.done = d;
                f.li.classList.toggle('is-done', d);
                if (d && animate) pop(f.li);
            }
        });

        // Estado por seccion (agregado de sus subcampos)
        sectionNodes.forEach(function(n){
            var reqSubs = n.subItems.filter(function(s){ return s.fieldReq; });
            var complete;
            if (n.required){
                complete = reqSubs.length ? reqSubs.every(function(s){ return s.done; })
                                          : n.subItems.length ? n.subItems.every(function(s){ return s.done; }) : false;
            } else {
                complete = n.subItems.length ? n.subItems.every(function(s){ return s.done; }) : false;
            }
            var wasDone = n.li.classList.contains('is-done');
            n.li.classList.toggle('is-done', complete);
            if (complete && !wasDone && animate){ pop(n.dot.parentNode); if (!n.required) spark(n.li); }
            var anyFilled = n.subItems.some(function(s){ return s.done; });
            var focused = n.sec.contains(document.activeElement);
            n.li.classList.toggle('is-active', !complete && (focused || anyFilled));
        });

        var reqDone = fields.filter(function(f){ return f.fieldReq && f.done; }).length;
        var optDone = fields.filter(function(f){ return !f.fieldReq && f.done; }).length;

        if (reqDoneEl) reqDoneEl.textContent = reqDone;
        setBar(reqFillEl, reqTotal ? reqDone / reqTotal : 0);
        if (reqDone > prevReqDone && reqFillEl && !reduce){
            reqFillEl.classList.remove('is-shine'); void reqFillEl.offsetWidth; reqFillEl.classList.add('is-shine');
        }
        prevReqDone = reqDone;

        var optRatio = optTotal ? optDone / optTotal : 0;
        setBar(bonusFillEl, optRatio);
        if (bonusPctEl) bonusPctEl.textContent = Math.round(optRatio * 100) + '%';

        var allReq = reqTotal > 0 && reqDone === reqTotal;
        setBar(pmFill, reqTotal ? reqDone / reqTotal : 0);
        if (pmLabel){
            if (allReq){ pmLabel.classList.add('is-ready'); pmLabel.innerHTML = '<i class="fas fa-check-circle"></i> Listo para guardar'; }
            else { pmLabel.classList.remove('is-ready'); pmLabel.textContent = reqDone + '/' + reqTotal + ' campos'; }
        }
        if (allReq !== prevAllReq){
            if (readyPill) readyPill.hidden = !allReq;
            if (rewardCard) rewardCard.classList.toggle('is-complete', allReq);
            if (allReq && !reduce){
                var b = formEl.querySelector('.gc-btn-primary');
                if (b){ b.classList.remove('gc-shine'); void b.offsetWidth; b.classList.add('gc-shine'); }
            }
            prevAllReq = allReq;
        }
    }

    build();
    if (!fields.length && !sectionNodes.length) return;

    formEl.addEventListener('input', function(){ refresh(true); }, true);
    formEl.addEventListener('change', function(){ refresh(true); }, true);
    formEl.addEventListener('focusin', function(){ refresh(false); });
    formEl.addEventListener('focusout', function(){ setTimeout(function(){ refresh(false); }, 0); });

    // Vehiculos se agregan/eliminan dinamicamente: reconstruir sus subcampos.
    var vc = document.getElementById('vehiculos-container');
    if (vc && window.MutationObserver){
        var rebuildT;
        new MutationObserver(function(){
            clearTimeout(rebuildT);
            rebuildT = setTimeout(function(){ build(); refresh(false); }, 60);
        }).observe(vc, { childList: true, subtree: true });
    }

    refresh(false);
})();
</script>
