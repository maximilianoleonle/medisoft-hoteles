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
    $requiredHtml = $required ? ' required' : '';
    $maxHtml = $max > 0 ? ' maxlength="' . $max . '"' : '';
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
                   <?= $maxHtml ?>>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
};

$gcVehicleVisibleFields = $gcGuestVisibleFields('vehicle');
$gcRenderVehicleFields = function ($indexToken) use ($gcVehicleVisibleFields, $gcGuestFieldRequired, $gcParkingOptionsTemplate) {
    ob_start();
    foreach ($gcVehicleVisibleFields as $fieldKey => $definition) {
        $input = (string)($definition['input'] ?? 'text');
        $storage = (string)($definition['storage'] ?? 'extra');
        $fieldName = (string)($definition['field'] ?? $fieldKey);
        $label = htmlspecialchars((string)($definition['label'] ?? $fieldKey), ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars((string)($definition['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8');
        $required = $gcGuestFieldRequired($fieldKey);
        $wide = !empty($definition['wide']) || in_array($input, ['textarea', 'parking'], true);
        $max = (int)($definition['max'] ?? 0);
        $name = $storage === 'column'
            ? 'vehiculos[' . $indexToken . '][' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . ']'
            : 'vehiculos[' . $indexToken . '][extras][' . htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') . ']';
        $requiredHtml = $required ? ' required' : '';
        $maxHtml = $max > 0 ? ' maxlength="' . $max . '"' : '';
        ?>
        <div class="gc-field<?= $wide ? ' gc-field-full' : '' ?>">
            <label class="gc-label"><?= $label ?><?= $required ? ' <span class="gc-required">*</span>' : '' ?></label>
            <?php if ($input === 'parking'): ?>
                <div class="gc-radio-grid">
                    <?= str_replace('__PARKING_NAME__', $name, $gcParkingOptionsTemplate) ?>
                </div>
            <?php elseif ($input === 'textarea'): ?>
                <textarea name="<?= $name ?>" rows="<?= (int)($definition['rows'] ?? 2) ?>" placeholder="<?= $placeholder ?>" class="gc-control"<?= $requiredHtml ?><?= $maxHtml ?>></textarea>
            <?php elseif ($input === 'select'): ?>
                <select name="<?= $name ?>" class="gc-control"<?= $requiredHtml ?>>
                    <option value="">Seleccione una opcion</option>
                    <?php foreach (($definition['options'] ?? []) as $optionValue => $optionLabel): ?>
                        <option value="<?= htmlspecialchars((string)$optionValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="<?= in_array($input, ['email', 'tel', 'date'], true) ? htmlspecialchars($input, ENT_QUOTES, 'UTF-8') : 'text' ?>"
                       name="<?= $name ?>"
                       placeholder="<?= $placeholder ?>"
                       class="gc-control<?= !empty($definition['uppercase']) ? ' font-mono' : '' ?>"
                       <?= !empty($definition['uppercase']) ? 'style="text-transform: uppercase"' : '' ?>
                       <?= $requiredHtml ?>
                       <?= $maxHtml ?>>
            <?php endif; ?>
        </div>
        <?php
    }

    return ob_get_clean();
};
$gcVehicleFieldsTemplate = $gcRenderVehicleFields('__INDEX__');
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<style id="guest-create-boutique">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.guest-create-page {
    --gc-brand: var(--brand-primary, #1B2746);
    --gc-brand-2: var(--brand-secondary, #0F172A);
    --gc-accent: var(--brand-accent, #BD9441);
    --gc-accent-dark: color-mix(in srgb, var(--gc-accent) 72%, #3F2E12);
    --gc-accent-soft: color-mix(in srgb, var(--gc-accent) 13%, #FFFFFF);
    --gc-accent-line: color-mix(in srgb, var(--gc-accent) 32%, #E8DDCA);
    --gc-ivory: color-mix(in srgb, var(--gc-accent) 8%, #F8F5ED);
    --gc-ivory-2: color-mix(in srgb, var(--gc-accent) 5%, #FCFAF5);
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
    --gc-serif: 'Cormorant Garamond', Georgia, serif;
    --gc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    background:
        repeating-linear-gradient(135deg, color-mix(in srgb, var(--gc-accent) 3%, transparent) 0 1px, transparent 1px 22px),
        linear-gradient(180deg, var(--gc-ivory-2), var(--gc-ivory) 58%, #F7F2EA);
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
    position: sticky;
    bottom: 12px;
    z-index: 3;
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
        bottom: 10px;
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

@media (prefers-reduced-motion: reduce) {
    .guest-create-page *,
    .guest-create-page *::before,
    .guest-create-page *::after {
        transition: none !important;
        animation: none !important;
    }
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
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <?php if ($gcGuestFieldVisible('procedencia_estado') || $gcGuestFieldVisible('procedencia_ciudad')): ?>
                    <section class="gc-section">
                        <div class="gc-section-head">
                            <div class="gc-section-title-wrap">
                                <span class="gc-section-icon"><i class="fas fa-map-marked-alt"></i></span>
                                <div>
                                    <h2>Procedencia</h2>
                                    <p class="gc-section-sub">Origen del huesped para reportes y seguimiento.</p>
                                </div>
                            </div>
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
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php
                    $gcExtraGuestFields = array_filter($gcGuestVisibleFields('guest'), function ($definition) {
                        return in_array(($definition['storage'] ?? 'column'), ['extra', 'document'], true);
                    });
                    ?>
                    <?php if (!empty($gcExtraGuestFields)): ?>
                        <section class="gc-section">
                            <div class="gc-section-head">
                                <div class="gc-section-title-wrap">
                                    <span class="gc-section-icon"><i class="fas fa-id-card"></i></span>
                                    <div>
                                        <h2>Datos adicionales</h2>
                                        <p class="gc-section-sub">Campos definidos por la configuracion de este hotel.</p>
                                    </div>
                                </div>
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

                    <?php if (!empty($gcVehicleVisibleFields)): ?>
                    <section class="gc-section">
                        <div class="gc-section-head">
                            <div class="gc-section-title-wrap">
                                <span class="gc-section-icon"><i class="fas fa-car"></i></span>
                                <div>
                                    <h2>Vehiculos</h2>
                                    <p class="gc-section-sub">Autos asociados al huesped para control de estacionamiento.</p>
                                </div>
                            </div>
                        </div>

                        <div class="gc-section-body">
                            <div id="vehiculos-container">
                                <div class="vehiculo-item">
                                    <div class="gc-vehicle-head">
                                        <h4 class="gc-vehicle-title">
                                            <i class="fas fa-car-side"></i>
                                            Vehiculo 1
                                        </h4>
                                        <button type="button" onclick="eliminarVehiculo(this)" class="gc-delete-vehicle hidden" title="Eliminar vehiculo">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <div class="gc-grid">
                                        <?= str_replace('__INDEX__', '0', $gcVehicleFieldsTemplate) ?>
                                    </div>
                                </div>
                            </div>

                            <button type="button" onclick="agregarVehiculo()" class="gc-add-vehicle">
                                <i class="fas fa-plus-circle"></i>
                                Agregar otro vehiculo
                            </button>

                            <p class="gc-note">
                                <i class="fas fa-info-circle"></i>
                                Puede registrar multiples vehiculos por huesped. Si no tiene vehiculo, deje los campos en blanco.
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
                        </div>

                        <div class="gc-section-body">
                            <label class="gc-label">Notas<?= $gcRequiredMark('notas') ?></label>
                            <textarea name="notas"
                                      rows="4"
                                      placeholder="Cualquier informacion adicional sobre el huesped..."
                                      class="gc-control"
                                      <?= $gcGuestFieldRequired('notas') ? 'required' : '' ?>><?= old('notas') ?></textarea>
                        </div>
                    </section>
                    <?php endif; ?>
                </main>

                <aside class="gc-side">
                    <div class="gc-side-card">
                        <h3>
                            <span class="gc-side-icon"><i class="fas fa-list-check"></i></span>
                            Registro limpio
                        </h3>
                        <div class="gc-check-list">
                            <span><i class="fas fa-check-circle"></i> Nombre completo y telefono celular son la base del expediente.</span>
                            <span><i class="fas fa-check-circle"></i> Los demas datos dependen de la configuracion del hotel.</span>
                            <span><i class="fas fa-check-circle"></i> Vehiculos se pueden registrar solo si aplican.</span>
                        </div>
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
let vehiculoIndex = 1;
const vehicleFieldsTemplate = <?= json_encode($gcVehicleFieldsTemplate, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

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

// Convertir placas a mayusculas en todos los campos
document.addEventListener('input', function(e) {
    if (e.target.name && e.target.name.includes('[placas]')) {
        e.target.value = e.target.value.toUpperCase();
    }
});

// Validacion del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    // Verificar si hay al menos un vehiculo con datos completos
    const vehiculos = document.querySelectorAll('.vehiculo-item');
    let hayVehiculoCompleto = false;

    vehiculos.forEach(vehiculo => {
        const marca = vehiculo.querySelector('input[name*="[marca]"]')?.value || '';
        const placas = vehiculo.querySelector('input[name*="[placas]"]')?.value || '';

        if (marca && placas) {
            hayVehiculoCompleto = true;
        }
    });

    // Si hay datos parciales en algun vehiculo, mostrar advertencia
    vehiculos.forEach(vehiculo => {
        const inputs = vehiculo.querySelectorAll('input[type="text"]');
        let hayDatosParciales = false;
        let camposLlenos = 0;

        inputs.forEach(input => {
            if (input.value.trim()) camposLlenos++;
        });

        if (camposLlenos > 0 && camposLlenos < 4) {
            vehiculo.style.border = '2px solid #D64539';
            setTimeout(() => {
                vehiculo.style.border = '';
            }, 3000);
        }
    });
});
</script>
