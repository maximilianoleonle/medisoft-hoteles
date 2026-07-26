<?php
/**
 * Vista para editar huesped.
 * Redisenada como expediente editable sin cambiar contrato del formulario.
 */

$huesped = $huesped ?? [];
$estados = $estados ?? [];
$huesped_id = (int)($huesped['id'] ?? 0);
$nombre_huesped = trim((string)($huesped['nombre_completo'] ?? 'Huesped'));
$telefono_huesped = trim((string)($huesped['telefono'] ?? ''));
$email_huesped = trim((string)($huesped['email'] ?? ''));
$procedencia_estado = trim((string)($huesped['procedencia_estado'] ?? ''));
$procedencia_ciudad = trim((string)($huesped['procedencia_ciudad'] ?? ''));
$notas_huesped = (string)($huesped['notas'] ?? '');
$inicial_huesped = function_exists('mb_substr') && function_exists('mb_strtoupper')
    ? mb_strtoupper(mb_substr($nombre_huesped !== '' ? $nombre_huesped : 'H', 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($nombre_huesped !== '' ? $nombre_huesped : 'H', 0, 1));
$procedencia_label = $procedencia_estado !== '' ? $procedencia_estado : 'Sin procedencia';
if ($procedencia_ciudad !== '') {
    $procedencia_label .= ' - ' . $procedencia_ciudad;
}
$contacto_label = $telefono_huesped !== '' || $email_huesped !== '' ? 'Contacto disponible' : 'Contacto pendiente';
$fecha_registro = !empty($huesped['created_at']) ? format_date($huesped['created_at']) : 'Sin registro';
$fecha_actualizacion = !empty($huesped['updated_at']) ? format_datetime($huesped['updated_at']) : 'Sin actualizacion';

if (!function_exists('guest_edit_safe')) {
    function guest_edit_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$guestFieldPolicy = is_array($guestFieldPolicy ?? null)
    ? $guestFieldPolicy
    : (function_exists('hotel_guest_field_policy') ? hotel_guest_field_policy() : ['fields' => []]);
$guestFieldCatalog = function_exists('hotel_guest_field_catalog') ? hotel_guest_field_catalog() : [];
$guestExtraValues = function_exists('hotel_guest_decode_extra_json')
    ? hotel_guest_decode_extra_json($huesped['datos_extra_json'] ?? null)
    : [];
$identificacionDocumentoPresente = !empty($identificacionDocumentoPresente);
$geGuestVisibleFields = function ($scope) use ($guestFieldPolicy) {
    return function_exists('hotel_guest_visible_fields') ? hotel_guest_visible_fields($scope, $guestFieldPolicy) : [];
};
$geGuestFieldVisible = function ($key) use ($guestFieldPolicy) {
    return function_exists('hotel_guest_field_visible') ? hotel_guest_field_visible($key, $guestFieldPolicy) : true;
};
$geGuestFieldRequired = function ($key) use ($guestFieldPolicy) {
    return function_exists('hotel_guest_field_required') ? hotel_guest_field_required($key, $guestFieldPolicy) : false;
};
$geOldExtra = function ($key, $default = '') {
    $value = $_SESSION['old_input']['extras'][$key] ?? $default;
    return is_scalar($value) ? (string)$value : (string)$default;
};
$geRequiredMark = function ($key) use ($geGuestFieldRequired) {
    return $geGuestFieldRequired($key) ? ' <span class="ge-required">*</span>' : '';
};
$geRenderHiddenColumn = function ($fieldName, $value) {
    return '<input type="hidden" name="' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
};
$geRenderGuestExtraField = function ($fieldKey, array $definition) use ($geGuestFieldRequired, $guestExtraValues, $identificacionDocumentoPresente, $geOldExtra) {
    $label = htmlspecialchars((string)($definition['label'] ?? $fieldKey), ENT_QUOTES, 'UTF-8');
    $placeholder = htmlspecialchars((string)($definition['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8');
    $currentStoredValue = is_scalar($guestExtraValues[$fieldKey] ?? null) ? (string)$guestExtraValues[$fieldKey] : '';
    $currentValue = $geOldExtra($fieldKey, $currentStoredValue);
    $value = htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8');
    $required = $geGuestFieldRequired($fieldKey);
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
    $requiredHtml = ($required && !($input === 'file' && $identificacionDocumentoPresente)) ? ' required' : '';
    $maxHtml = $max > 0 ? ' maxlength="' . $max . '"' : '';
    $acceptHtml = !empty($definition['accept'])
        ? ' accept="' . htmlspecialchars((string)$definition['accept'], ENT_QUOTES, 'UTF-8') . '"'
        : '';

    ob_start();
    ?>
    <div class="ge-field<?= $wide ? ' ge-field-full' : '' ?>">
        <label class="ge-label"><?= $label ?><?= $required ? ' <span class="ge-required">*</span>' : '' ?></label>
        <?php if ($input === 'file'): ?>
            <input type="file"
                   name="<?= $name ?>"
                   class="ge-control ge-file-control"
                   <?= $acceptHtml ?>
                   <?= $requiredHtml ?>>
            <p class="ge-field-hint">
                Puede tomar foto con la camara, elegir desde galeria o adjuntar PDF/imagen desde archivos.
                <?php if ($identificacionDocumentoPresente): ?>
                    Ya existe una identificacion vinculada; subir otra reemplaza solo el requisito operativo, no borra la anterior.
                <?php endif; ?>
            </p>
        <?php elseif ($input === 'textarea'): ?>
            <textarea name="<?= $name ?>" rows="<?= (int)($definition['rows'] ?? 3) ?>" placeholder="<?= $placeholder ?>" class="ge-control"<?= $requiredHtml ?><?= $maxHtml ?>><?= $value ?></textarea>
        <?php elseif ($input === 'select'): ?>
            <select name="<?= $name ?>" class="ge-control"<?= $requiredHtml ?>>
                <option value="">Seleccione una opcion</option>
                <?php foreach (($definition['options'] ?? []) as $optionValue => $optionLabel): ?>
                    <option value="<?= htmlspecialchars((string)$optionValue, ENT_QUOTES, 'UTF-8') ?>" <?= $currentValue === (string)$optionValue ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <input type="<?= in_array($input, ['email', 'tel', 'date'], true) ? htmlspecialchars($input, ENT_QUOTES, 'UTF-8') : 'text' ?>"
                   name="<?= $name ?>"
                   value="<?= $value ?>"
                   placeholder="<?= $placeholder ?>"
                   class="ge-control<?= !empty($definition['uppercase']) ? ' ge-uppercase' : '' ?>"
                   <?= !empty($definition['uppercase']) ? 'style="text-transform: uppercase"' : '' ?>
                   <?= $requiredHtml ?>
                   <?= $maxHtml ?>>
        <?php endif; ?>
        <?php if (form_error($errorKey)): ?>
            <span class="ge-form-error"><?= form_error($errorKey) ?></span>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
};

$vehiculos = [];
$estacionamientos = [];
if (class_exists('HuespedVehiculo')) {
    $vehiculoModel = new HuespedVehiculo();
    $vehiculos = $vehiculoModel->porHuespedHotel($huesped_id);
}
if (function_exists('hotel_general_catalog_parking_rows')) {
    foreach (hotel_general_catalog_parking_rows(null, false) as $parkingRow) {
        $parkingCode = trim((string)($parkingRow['codigo'] ?? ''));
        $parkingLabel = trim((string)($parkingRow['label'] ?? ''));
        if ($parkingCode !== '' && $parkingLabel !== '') {
            $estacionamientos[$parkingCode] = $parkingLabel;
        }
    }
}
if (empty($estacionamientos) && class_exists('HuespedVehiculo')) {
    $estacionamientos = HuespedVehiculo::getEstacionamientos();
}
if (empty($estacionamientos)) {
    $estacionamientos = ['coches' => 'Coches'];
}
reset($estacionamientos);
$estacionamientoDefault = (string)key($estacionamientos);
$vehiculos_count = count($vehiculos);
$geVehicleVisibleFields = $geGuestVisibleFields('vehicle');
$geParkingOptionsHtml = function ($fieldName = 'estacionamiento', $formId = 'formAgregarVehiculo') use ($estacionamientos, $estacionamientoDefault) {
    $html = '';
    $fieldNameEsc = htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8');
    $formIdEsc = htmlspecialchars((string)$formId, ENT_QUOTES, 'UTF-8');
    foreach ($estacionamientos as $parkingCode => $parkingLabel) {
        $parkingCode = (string)$parkingCode;
        $parkingCodeEsc = htmlspecialchars($parkingCode, ENT_QUOTES, 'UTF-8');
        $parkingLabelEsc = htmlspecialchars((string)$parkingLabel, ENT_QUOTES, 'UTF-8');
        $checkedAttr = $parkingCode === $estacionamientoDefault ? ' checked' : '';
        $html .= '<label class="ge-radio-card">';
        $html .= '<input type="radio" form="' . $formIdEsc . '" name="' . $fieldNameEsc . '" value="' . $parkingCodeEsc . '"' . $checkedAttr . '>';
        $html .= '<span><i class="fas fa-square-parking"></i>' . $parkingLabelEsc . '</span>';
        $html .= '</label>';
    }

    return $html;
};
$geRenderVehicleInlineFields = function () use ($geVehicleVisibleFields, $geGuestFieldRequired, $geRequiredMark, $geParkingOptionsHtml) {
    ob_start();
    foreach ($geVehicleVisibleFields as $fieldKey => $definition) {
        $input = (string)($definition['input'] ?? 'text');
        $storage = (string)($definition['storage'] ?? 'extra');
        $fieldName = (string)($definition['field'] ?? $fieldKey);
        $name = $storage === 'column' ? $fieldName : 'extras[' . $fieldKey . ']';
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '_', 'add_' . ($storage === 'column' ? $fieldName : 'extra_' . $fieldKey));
        $label = htmlspecialchars((string)($definition['label'] ?? $fieldKey), ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars((string)($definition['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8');
        $requiredAttr = $geGuestFieldRequired($fieldKey) ? ' required' : '';
        $max = (int)($definition['max'] ?? 0);
        $maxAttr = $max > 0 ? ' maxlength="' . $max . '"' : '';
        ?>
        <?php if ($input === 'parking'): ?>
            <fieldset class="ge-inline-radio-group">
                <legend><?= $label ?><?= $geRequiredMark($fieldKey) ?></legend>
                <div class="ge-radio-options">
                    <?= $geParkingOptionsHtml($name) ?>
                </div>
            </fieldset>
        <?php else: ?>
            <div class="ge-inline-field<?= $input === 'textarea' ? ' ge-inline-field-full' : '' ?>">
                <label for="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"><?= $label ?><?= $geRequiredMark($fieldKey) ?></label>
                <?php if ($input === 'textarea'): ?>
                    <textarea id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
                              form="formAgregarVehiculo"
                              name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                              rows="<?= (int)($definition['rows'] ?? 2) ?>"
                              placeholder="<?= $placeholder ?>"
                              <?= $requiredAttr ?>
                              <?= $maxAttr ?>></textarea>
                <?php elseif ($input === 'select'): ?>
                    <select id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
                            form="formAgregarVehiculo"
                            name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $requiredAttr ?>>
                        <option value="">Seleccione una opcion</option>
                        <?php foreach (($definition['options'] ?? []) as $optionValue => $optionLabel): ?>
                            <option value="<?= htmlspecialchars((string)$optionValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
                           type="<?= in_array($input, ['email', 'tel', 'date'], true) ? htmlspecialchars($input, ENT_QUOTES, 'UTF-8') : 'text' ?>"
                           form="formAgregarVehiculo"
                           name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="<?= $placeholder ?>"
                           <?= !empty($definition['uppercase']) ? 'style="text-transform: uppercase"' : '' ?>
                           <?= $requiredAttr ?>
                           <?= $maxAttr ?>>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php
    }

    return ob_get_clean();
};
?>

<style id="guest-edit-redesign">
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.guest-edit-page {
    --ge-brand: var(--brand-primary, #1B2746);
    --ge-brand-dark: var(--brand-secondary, #0F172A);
    --ge-accent: var(--brand-accent, #BD9441);
    --ge-accent-deep: color-mix(in srgb, var(--ge-accent) 72%, #392D16);
    --ge-bg: color-mix(in srgb, var(--ge-accent) 8%, #F8F3EA);
    --ge-bg-soft: color-mix(in srgb, var(--ge-accent) 4%, #FFFCF6);
    --ge-panel: color-mix(in srgb, var(--ge-accent) 2%, #FFFDF8);
    --ge-panel-warm: color-mix(in srgb, var(--ge-accent) 7%, #FFFDF8);
    --ge-line: color-mix(in srgb, var(--ge-brand) 14%, #E9DDCB);
    --ge-line-strong: color-mix(in srgb, var(--ge-accent) 36%, #D8C4A4);
    --ge-muted: color-mix(in srgb, var(--ge-brand-dark) 50%, #94A3B8);
    --ge-heading: #111827;
    --ge-text: #182033;
    --ge-text-soft: #334155;
    --ge-accent-readable: color-mix(in srgb, var(--ge-accent) 66%, #3B2D12);
    --ge-success: #157A52;
    --ge-danger: #B9463D;
    --ge-info: #2E6EA8;
    --ge-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --ge-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    background:
        radial-gradient(circle at 90% 4%, color-mix(in srgb, var(--ge-accent) 22%, transparent), transparent 30rem),
        linear-gradient(90deg, color-mix(in srgb, var(--ge-brand) 5%, transparent) 0 1px, transparent 1px 34px),
        linear-gradient(180deg, var(--ge-bg-soft), var(--ge-bg) 58%, #F4EBDC);
    color: var(--ge-text);
    font-family: var(--ge-sans);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.guest-edit-page *,
.guest-edit-page *::before,
.guest-edit-page *::after {
    box-sizing: border-box;
}

.guest-edit-page :where(a, button, input, textarea, select, label, span, p) {
    font-family: var(--ge-sans);
}

.ge-shell {
    width: min(1460px, calc(100% - 30px));
    margin: 0 auto;
    padding: 26px 0 42px;
}

.ge-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 14px;
    color: var(--ge-muted);
    font-size: .8rem;
    font-weight: 800;
}

.ge-breadcrumb a {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--ge-text-soft);
    transition: color .18s ease, transform .18s ease;
}

.ge-breadcrumb a:hover {
    color: var(--ge-accent-readable);
    transform: translateY(-1px);
}

.ge-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 22px;
    align-items: stretch;
    margin-bottom: 18px;
    border: 1px solid color-mix(in srgb, var(--ge-accent) 28%, transparent);
    border-radius: 28px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--ge-accent) 24%, transparent), transparent 18rem),
        linear-gradient(135deg, rgba(255,253,248,.98), rgba(248,240,226,.92));
    box-shadow: 0 28px 74px -58px rgba(15, 23, 42, .72);
    padding: clamp(20px, 3vw, 32px);
}

.ge-hero::after {
    content: "";
    position: absolute;
    inset: auto 26px 0 auto;
    width: min(260px, 42vw);
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, transparent, var(--ge-accent), var(--ge-brand));
    opacity: .72;
}

.ge-identity {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 18px;
    align-items: center;
    min-width: 0;
}

.ge-avatar {
    width: clamp(72px, 9vw, 112px);
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    border-radius: 26px;
    background:
        linear-gradient(145deg, var(--ge-brand), color-mix(in srgb, var(--ge-brand) 76%, #07111F));
    color: #FFFDF8;
    font-family: var(--ge-serif);
    font-size: clamp(2.4rem, 5vw, 4.5rem);
    font-weight: 700;
    box-shadow: 0 22px 46px -30px rgba(15, 23, 42, .82);
}

.ge-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--ge-accent-readable);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.ge-title {
    margin: 0;
    color: var(--ge-heading);
    font-family: var(--ge-serif);
    font-size: clamp(2.2rem, 5vw, 4.6rem);
    line-height: .9;
    font-weight: 700;
    letter-spacing: 0;
    text-wrap: balance;
}

.ge-subtitle {
    max-width: 70ch;
    margin: 10px 0 0;
    color: var(--ge-muted);
    font-size: .94rem;
    font-weight: 650;
    line-height: 1.58;
}

.ge-hero-actions {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    justify-content: flex-end;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.ge-btn,
.ge-link-btn {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    border-radius: 14px;
    padding: 10px 14px;
    font-size: .82rem;
    font-weight: 900;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
}

.ge-btn:active,
.ge-link-btn:active {
    transform: translateY(1px) scale(.99);
}

.ge-btn:focus-visible,
.ge-link-btn:focus-visible,
.ge-control:focus-visible,
.ge-vehicle-link:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--ge-accent) 42%, transparent);
    outline-offset: 2px;
}

.ge-link-btn {
    border: 1px solid var(--ge-line);
    background: rgba(255,253,248,.82);
    color: var(--ge-text);
}

.ge-link-btn:hover {
    border-color: var(--ge-line-strong);
    background: var(--ge-panel);
    box-shadow: 0 16px 36px -28px rgba(15, 23, 42, .58);
}

.ge-btn-primary {
    border: 1px solid color-mix(in srgb, var(--ge-brand) 78%, #000000);
    background: linear-gradient(145deg, var(--ge-brand), var(--ge-brand-dark));
    color: #FFFDF8;
    box-shadow: 0 18px 34px -24px color-mix(in srgb, var(--ge-brand) 72%, transparent);
}

.ge-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 20px 42px -24px color-mix(in srgb, var(--ge-brand) 76%, transparent);
}

.ge-btn-secondary {
    border: 1px solid var(--ge-line);
    background: var(--ge-panel);
    color: var(--ge-text);
}

.ge-btn-secondary:hover {
    border-color: var(--ge-line-strong);
    background: var(--ge-panel-warm);
}

.ge-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(290px, 350px);
    gap: 18px;
    align-items: start;
}

.ge-main,
.ge-side {
    min-width: 0;
}

.ge-main {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ge-side {
    position: static;
    top: auto;
    z-index: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.ge-panel,
.ge-side-card,
.ge-actions {
    border: 1px solid var(--ge-line);
    border-radius: 22px;
    background: var(--ge-panel);
    box-shadow:
        0 1px 2px color-mix(in srgb, var(--ge-brand-dark) 4%, transparent),
        0 18px 42px -34px rgba(15, 23, 42, .56);
}

.ge-panel {
    overflow: hidden;
}

.ge-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 17px 18px;
    border-bottom: 1px solid var(--ge-line);
    background:
        linear-gradient(90deg, var(--ge-panel-warm), var(--ge-panel));
}

.ge-panel-title {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    min-width: 0;
}

.ge-icon-box {
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 14px;
    border: 1px solid var(--ge-line);
    background: color-mix(in srgb, var(--ge-accent) 12%, #FFFDF8);
    color: var(--ge-accent-deep);
}

.ge-panel h2,
.ge-side-card h3 {
    margin: 0;
    color: var(--ge-heading);
    font-size: 1rem;
    font-weight: 900;
    line-height: 1.2;
}

.ge-panel p,
.ge-side-card p {
    margin: 5px 0 0;
    color: var(--ge-muted);
    font-size: .79rem;
    font-weight: 700;
    line-height: 1.45;
}

.ge-panel-body {
    padding: 18px;
}

.ge-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.ge-field {
    min-width: 0;
}

.ge-field-full {
    grid-column: 1 / -1;
}

.ge-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 7px;
    color: var(--ge-text-soft);
    font-size: .76rem;
    font-weight: 900;
}

.ge-required {
    color: var(--ge-danger);
}

.ge-form-error {
    display: block;
    margin-top: 7px;
    color: var(--ge-danger);
    font-size: .76rem;
    font-weight: 850;
    line-height: 1.35;
}

.ge-input-wrap {
    position: relative;
}

.ge-input-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: color-mix(in srgb, var(--ge-accent) 45%, #94A3B8);
    pointer-events: none;
}

.ge-control {
    width: 100%;
    min-height: 46px;
    border: 1px solid var(--ge-line);
    border-radius: 15px;
    background: color-mix(in srgb, var(--ge-accent) 2%, #FFFEFB);
    color: var(--ge-text);
    padding: 11px 13px;
    font-size: .9rem;
    font-weight: 700;
    line-height: 1.4;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease, transform .18s ease;
}

.ge-control.has-icon {
    padding-left: 42px;
}

.ge-control:hover {
    border-color: var(--ge-line-strong);
}

.ge-control:focus {
    border-color: color-mix(in srgb, var(--ge-accent) 72%, var(--ge-brand));
    background: #FFFDF8;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--ge-accent) 17%, transparent);
}

.ge-control::placeholder {
    color: color-mix(in srgb, var(--ge-muted) 65%, #CBD5E1);
    font-weight: 650;
}

.ge-file-control {
    padding: 9px 13px;
    cursor: pointer;
}

.ge-file-control::file-selector-button {
    margin-right: 12px;
    border: 0;
    border-radius: 999px;
    background: color-mix(in srgb, var(--ge-brand) 88%, #FFFFFF);
    color: #FFFFFF;
    font-size: .76rem;
    font-weight: 850;
    padding: 8px 12px;
    cursor: pointer;
}

.ge-field-hint {
    margin: 7px 0 0;
    color: var(--ge-muted);
    font-size: .76rem;
    font-weight: 650;
    line-height: 1.45;
}

textarea.ge-control {
    min-height: 126px;
    resize: vertical;
}

.ge-context-note {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    padding: 13px 14px;
    border-radius: 17px;
    border: 1px solid color-mix(in srgb, var(--ge-info) 22%, var(--ge-line));
    background: color-mix(in srgb, var(--ge-info) 7%, var(--ge-panel));
    color: var(--ge-text);
}

.ge-context-note i {
    color: var(--ge-info);
    margin-top: 2px;
}

.ge-context-note strong {
    display: block;
    color: var(--ge-heading);
    font-size: .84rem;
    font-weight: 900;
}

.ge-context-note span {
    display: block;
    margin-top: 2px;
    color: var(--ge-muted);
    font-size: .78rem;
    font-weight: 700;
    line-height: 1.45;
}

.ge-vehicle-list {
    display: grid;
    gap: 11px;
    margin-top: 14px;
}

.ge-vehicle-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    padding: 13px;
    border: 1px solid var(--ge-line);
    border-radius: 17px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--ge-accent) 5%, #FFFDF8), color-mix(in srgb, var(--ge-brand) 3%, #FFFDF8));
}

.ge-vehicle-main {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.ge-vehicle-icon {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 13px;
    background: color-mix(in srgb, var(--ge-accent) 10%, #FFFDF8);
    color: var(--ge-accent-readable);
}

.ge-vehicle-card strong {
    display: block;
    color: var(--ge-heading);
    font-size: .92rem;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ge-vehicle-card span {
    display: block;
    margin-top: 2px;
    color: var(--ge-muted);
    font-size: .76rem;
    font-weight: 750;
}

.ge-parking-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 30px;
    padding: 7px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--ge-accent) 13%, #FFFDF8);
    color: var(--ge-accent-deep);
    font-size: .72rem;
    font-weight: 900;
    white-space: nowrap;
}

.ge-empty {
    display: grid;
    place-items: center;
    gap: 8px;
    padding: 24px 14px;
    border: 1px dashed var(--ge-line-strong);
    border-radius: 18px;
    background: color-mix(in srgb, var(--ge-accent) 5%, transparent);
    text-align: center;
    color: var(--ge-muted);
}

.ge-empty i {
    color: var(--ge-accent-readable);
    font-size: 1.8rem;
}

.ge-empty strong {
    color: var(--ge-heading);
    font-size: .96rem;
    font-weight: 900;
}

.ge-vehicle-link {
    width: 100%;
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 14px;
    padding: 10px 12px;
    border: 1px solid var(--ge-line);
    border-radius: 15px;
    background: var(--ge-panel);
    color: var(--ge-text);
    font-size: .82rem;
    font-weight: 900;
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.ge-vehicle-link:hover {
    transform: translateY(-1px);
    border-color: var(--ge-line-strong);
    background: var(--ge-panel-warm);
}

.ge-vehicle-list[hidden],
.ge-empty[hidden] {
    display: none !important;
}

.ge-vehicle-capture {
    margin-top: 14px;
    border: 1px solid var(--ge-line);
    border-radius: 18px;
    background: color-mix(in srgb, var(--ge-accent) 4%, #FFFCF6);
    padding: 15px;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.ge-vehicle-capture:focus-within {
    border-color: color-mix(in srgb, var(--ge-accent) 34%, var(--ge-line));
    box-shadow: 0 12px 28px -24px color-mix(in srgb, var(--ge-brand) 45%, transparent);
}

.ge-vehicle-capture-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 13px;
}

.ge-vehicle-capture-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: var(--ge-heading);
    font-size: .9rem;
    font-weight: 950;
}

.ge-vehicle-capture-title i {
    color: var(--ge-accent-deep);
}

.ge-vehicle-capture-copy {
    margin: 4px 0 0;
    color: var(--ge-muted);
    font-size: .78rem;
    font-weight: 700;
    line-height: 1.45;
}

.ge-inline-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.ge-inline-field,
.ge-inline-radio-group {
    min-width: 0;
    border: 1px solid rgba(67, 78, 94, .1);
    border-radius: 16px;
    background: rgba(255,255,255,.54);
    padding: 12px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.64);
}

.ge-inline-field-full,
.ge-inline-radio-group {
    grid-column: 1 / -1;
}

.ge-inline-field label,
.ge-inline-radio-group legend {
    display: block;
    margin: 0 0 7px;
    color: #263246;
    font-size: .74rem;
    font-weight: 950;
}

.ge-inline-field input,
.ge-inline-field select,
.ge-inline-field textarea {
    width: 100%;
    min-height: 43px;
    border: 1px solid rgba(44, 55, 75, .24);
    border-radius: 12px;
    background: rgba(255,255,255,.96);
    color: var(--ge-text);
    outline: none;
    padding: 9px 12px;
    font-weight: 750;
    transition: border-color .18s ease, box-shadow .18s ease;
    box-shadow: 0 1px 0 rgba(255,255,255,.75);
}

.ge-inline-field textarea {
    min-height: 86px;
    resize: vertical;
}

.ge-inline-field input:focus,
.ge-inline-field select:focus,
.ge-inline-field textarea:focus {
    border-color: color-mix(in srgb, var(--ge-accent) 68%, var(--ge-brand));
    box-shadow:
        0 0 0 4px color-mix(in srgb, var(--ge-accent) 18%, transparent),
        0 10px 22px rgba(28, 35, 49, .08);
}

.ge-radio-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(118px, 1fr));
    gap: 9px;
}

.ge-radio-card {
    cursor: pointer;
}

.ge-radio-card input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.ge-radio-card span {
    min-height: 68px;
    display: grid;
    place-items: center;
    gap: 4px;
    border: 1px solid rgba(44, 55, 75, .2);
    border-radius: 14px;
    background: rgba(255,255,255,.8);
    color: #263246;
    font-size: .82rem;
    font-weight: 900;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.ge-radio-card span:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--ge-accent) 42%, rgba(44,55,75,.2));
    background: rgba(255,255,255,.95);
}

.ge-radio-card input:focus-visible + span {
    outline: 3px solid color-mix(in srgb, var(--ge-accent) 42%, transparent);
    outline-offset: 2px;
}

.ge-radio-card input:checked + span {
    border-color: var(--ge-brand);
    background: var(--ge-brand);
    color: #FFFFFF;
    box-shadow: 0 10px 22px -14px color-mix(in srgb, var(--ge-brand) 68%, transparent);
}

.ge-form-alert {
    display: none;
    align-items: flex-start;
    gap: 9px;
    padding: 11px 12px;
    border-radius: 13px;
    border: 1px solid color-mix(in srgb, var(--ge-danger) 22%, rgba(67,78,94,.1));
    background: color-mix(in srgb, var(--ge-danger) 7%, #FFFFFF);
    color: color-mix(in srgb, var(--ge-danger) 74%, #1C2331);
    font-size: .84rem;
    font-weight: 850;
    line-height: 1.35;
}

.ge-form-alert.is-visible {
    display: flex;
}

.ge-form-alert.is-success {
    border-color: color-mix(in srgb, var(--ge-success) 24%, rgba(67,78,94,.1));
    background: color-mix(in srgb, var(--ge-success) 8%, #FFFFFF);
    color: color-mix(in srgb, var(--ge-success) 78%, #1C2331);
}

.ge-vehicle-capture-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

.ge-vehicle-clear,
.ge-vehicle-save {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 13px;
    padding: 0 16px;
    font-size: .86rem;
    font-weight: 900;
    cursor: pointer;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, opacity .18s ease;
}

.ge-vehicle-clear {
    border: 1px solid var(--ge-line);
    background: rgba(255,255,255,.72);
    color: var(--ge-text-soft);
}

.ge-vehicle-save {
    border: 1px solid color-mix(in srgb, var(--ge-brand) 78%, #000000);
    background: linear-gradient(135deg, #263246, var(--ge-brand));
    color: #FFFFFF;
    box-shadow: 0 14px 26px rgba(28, 35, 49, .22);
}

.ge-vehicle-clear:hover,
.ge-vehicle-save:hover {
    transform: translateY(-1px);
}

.ge-vehicle-save:disabled {
    opacity: .72;
    cursor: wait;
}

.ge-side-card {
    padding: 16px;
}

.ge-preview {
    display: none;
}

.ge-preview h3,
.ge-preview p {
    color: #FFFDF8;
}

.ge-preview p {
    opacity: .74;
}

.ge-preview-avatar {
    width: 56px;
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    margin-bottom: 16px;
    border: 1px solid rgba(255,253,248,.22);
    border-radius: 18px;
    background: rgba(255,253,248,.12);
    font-family: var(--ge-serif);
    font-size: 2rem;
    font-weight: 700;
}

.ge-preview-name {
    margin: 0;
    color: #FFFDF8;
    font-family: var(--ge-serif);
    font-size: clamp(1.8rem, 3vw, 2.5rem);
    line-height: .96;
    font-weight: 700;
    text-wrap: balance;
}

.ge-preview-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}

.ge-preview-tags span,
.ge-meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 30px;
    padding: 7px 10px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 900;
}

.ge-preview-tags span {
    background: rgba(255,253,248,.12);
    color: #FFFDF8;
}

.ge-meta-list {
    display: grid;
    gap: 10px;
    margin-top: 13px;
}

.ge-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 11px 0;
    border-top: 1px solid var(--ge-line);
}

.ge-meta-row:first-child {
    border-top: 0;
    padding-top: 0;
}

.ge-meta-row span {
    color: var(--ge-muted);
    font-size: .76rem;
    font-weight: 800;
}

.ge-meta-row strong {
    color: var(--ge-heading);
    font-size: .8rem;
    font-weight: 950;
    text-align: right;
}

.ge-tips {
    display: grid;
    gap: 10px;
    margin-top: 13px;
}

.ge-tips span {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    color: var(--ge-muted);
    font-size: .78rem;
    font-weight: 750;
    line-height: 1.42;
}

.ge-tips i {
    color: var(--ge-success);
    margin-top: 2px;
}

.ge-actions {
    position: static;
    top: auto;
    bottom: auto;
    z-index: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 13px;
    background: color-mix(in srgb, var(--ge-panel) 96%, #FFFDF8);
    backdrop-filter: none;
}

.ge-actions-copy {
    min-width: 0;
}

.ge-actions-copy strong {
    display: block;
    color: var(--ge-heading);
    font-size: .88rem;
    font-weight: 950;
}

.ge-actions-copy span {
    display: block;
    margin-top: 2px;
    color: var(--ge-muted);
    font-size: .76rem;
    font-weight: 750;
}

.ge-actions-buttons {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex: 0 0 auto;
}

/* Guest-create inspired polish */
.guest-edit-page .ge-shell {
    width: min(100%, 1280px);
    padding: 26px 18px 34px;
}

.guest-edit-page .ge-breadcrumb {
    margin-bottom: 18px;
    font-size: .78rem;
    font-weight: 750;
}

.guest-edit-page .ge-hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    overflow: visible;
}

.guest-edit-page .ge-hero::after {
    display: none;
}

.guest-edit-page .ge-identity {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    min-width: 0;
}

.guest-edit-page .ge-avatar {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    font-size: 1.45rem;
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--ge-brand) 58%, transparent);
}

.guest-edit-page .ge-kicker {
    margin-bottom: 4px;
    font-size: .72rem;
    letter-spacing: .08em;
}

.guest-edit-page .ge-title {
    font-size: clamp(2rem, 3.6vw, 2.75rem);
    font-weight: 650;
    line-height: .96;
}

.guest-edit-page .ge-subtitle {
    max-width: 62ch;
    margin-top: 8px;
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.55;
}

.guest-edit-page .ge-layout {
    grid-template-columns: minmax(0, 1fr) minmax(286px, 318px);
    gap: 18px;
}

.guest-edit-page .ge-main {
    gap: 15px;
}

.guest-edit-page .ge-panel,
.guest-edit-page .ge-side-card,
.guest-edit-page .ge-actions {
    border-radius: 18px;
    box-shadow:
        0 1px 2px color-mix(in srgb, var(--ge-brand-dark) 4%, transparent),
        0 14px 32px -24px color-mix(in srgb, var(--ge-brand-dark) 34%, transparent);
}

.guest-edit-page .ge-panel-head {
    align-items: center;
    min-height: 58px;
    padding: 14px 17px;
}

.guest-edit-page .ge-panel-title {
    align-items: center;
    gap: 10px;
}

.guest-edit-page .ge-icon-box {
    width: 34px;
    height: 34px;
    border-radius: 12px;
}

.guest-edit-page .ge-panel h2,
.guest-edit-page .ge-side-card h3 {
    font-size: .95rem;
}

.guest-edit-page .ge-panel p,
.guest-edit-page .ge-side-card p {
    font-size: .75rem;
}

.guest-edit-page .ge-panel-body {
    padding: 16px;
}

.guest-edit-page .ge-grid {
    gap: 13px;
}

.guest-edit-page .ge-label {
    margin-bottom: 5px;
    font-size: .7rem;
}

.guest-edit-page .ge-control {
    min-height: 44px;
    border-radius: 12px;
    padding: 9px 11px;
    font-size: .84rem;
}

.guest-edit-page .ge-control.has-icon {
    padding-left: 38px;
}

.guest-edit-page .ge-input-wrap i {
    left: 12px;
    font-size: .78rem;
}

.guest-edit-page textarea.ge-control {
    min-height: 104px;
}

@media (max-width: 1100px) {
    .ge-hero,
    .ge-layout {
        grid-template-columns: 1fr;
    }

    .ge-side {
        position: static;
        order: -1;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ge-preview {
        grid-column: 1 / -1;
    }
}

@media (max-width: 720px) {
    .ge-shell {
        width: 100%;
        padding: 18px 10px 26px;
    }

    .ge-breadcrumb {
        max-width: 100%;
        margin-bottom: 16px;
        padding: 0 6px;
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
    }

    .ge-hero,
    .ge-panel,
    .ge-side-card,
    .ge-actions {
        border-radius: 20px;
    }

    .ge-hero {
        flex-direction: column;
        gap: 10px;
        padding: 0 6px;
        margin-bottom: 14px;
    }

    .ge-identity {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .ge-avatar {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.2rem;
    }

    .ge-kicker {
        margin-bottom: 2px;
        font-size: .61rem;
        line-height: 1;
    }

    .ge-title {
        font-size: 1.45rem;
        line-height: .98;
    }

    .ge-subtitle {
        display: -webkit-box;
        margin-top: 2px;
        font-size: .72rem;
        line-height: 1.3;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ge-hero-actions,
    .ge-actions,
    .ge-actions-buttons {
        width: 100%;
    }

    .ge-hero-actions,
    .ge-actions {
        display: grid;
        gap: 8px;
    }

    .ge-hero-actions {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ge-actions {
        grid-template-columns: 1fr;
    }

    .ge-actions-copy {
        display: none;
    }

    .ge-link-btn {
        min-height: 38px;
        border-radius: 11px;
        padding: 0 8px;
        font-size: .68rem;
        line-height: 1.1;
    }

    .ge-layout,
    .ge-main {
        gap: 9px;
    }

    .ge-grid,
    .ge-side {
        grid-template-columns: 1fr;
    }

    .ge-panel-head,
    .ge-panel-body {
        padding: 10px;
    }

    .ge-panel-head {
        min-height: 0;
    }

    .ge-panel-title {
        gap: 8px;
    }

    .ge-icon-box {
        width: 29px;
        height: 29px;
        border-radius: 10px;
        font-size: .78rem;
    }

    .ge-panel h2 {
        font-size: .86rem;
        line-height: 1.1;
    }

    .ge-panel p,
    .ge-field-hint,
    .ge-side {
        display: none;
    }

    .ge-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .ge-field-full {
        grid-column: 1 / -1;
    }

    .ge-label {
        margin-bottom: 4px;
        gap: 4px;
        font-size: .58rem;
        line-height: 1.1;
    }

    .ge-control {
        min-height: 44px;
        border-radius: 11px;
        padding: 7px 10px;
        font-size: 13px;
        line-height: 1.15;
        font-weight: 650;
    }

    select.ge-control {
        font-size: 12.5px;
        line-height: 1.15;
        text-overflow: ellipsis;
    }

    .ge-control.has-icon {
        padding-left: 30px;
    }

    .ge-input-wrap i {
        left: 10px;
        font-size: .68rem;
    }

    textarea.ge-control {
        min-height: 76px;
        padding-top: 9px;
        line-height: 1.32;
    }

    .ge-form-error {
        margin-top: 2px;
        font-size: .66rem;
        line-height: 1.25;
    }

    .ge-vehicle-card {
        grid-template-columns: 1fr;
    }

    .ge-parking-badge {
        justify-self: start;
    }

    .ge-actions-buttons {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ge-inline-grid,
    .ge-radio-options,
    .ge-vehicle-capture-actions {
        grid-template-columns: 1fr;
    }

    .ge-vehicle-capture-actions {
        display: grid;
    }

    .ge-vehicle-clear,
    .ge-vehicle-save {
        width: 100%;
    }

    .ge-btn,
    .ge-link-btn {
        width: 100%;
        min-height: 44px;
        border-radius: 12px;
        padding: 0 10px;
        font-size: .72rem;
        line-height: 1.1;
    }

    .ge-actions {
        padding: 9px;
        border-radius: 15px;
    }
}

@media (max-width: 380px) {
    .ge-grid,
    .ge-actions,
    .ge-actions-buttons,
    .ge-radio-options {
        grid-template-columns: 1fr;
    }

    .ge-actions-buttons .ge-btn-primary {
        order: -1;
    }
}

@media (prefers-reduced-motion: reduce) {
    .guest-edit-page *,
    .guest-edit-page *::before,
    .guest-edit-page *::after {
        animation: none !important;
        transition: none !important;
    }
}
</style>

<div class="guest-edit-page">
    <div class="ge-shell">
        <nav class="ge-breadcrumb" aria-label="Ruta de navegacion">
            <a href="<?= url('huespedes') ?>">
                <i class="fas fa-users"></i>
                Hu&eacute;spedes
            </a>
            <i class="fas fa-chevron-right"></i>
            <a href="<?= url('huespedes/' . $huesped_id) ?>">
                <?= guest_edit_safe($nombre_huesped) ?>
            </a>
            <i class="fas fa-chevron-right"></i>
            <span>Editar</span>
        </nav>

        <header class="ge-hero">
            <div class="ge-identity">
                <div class="ge-avatar" id="guestEditAvatar" aria-hidden="true"><?= guest_edit_safe($inicial_huesped, 'H') ?></div>
                <div>
                    <div class="ge-kicker">
                        <i class="fas fa-pen-to-square"></i>
                        Expediente editable
                    </div>
                    <h1 class="ge-title">Editar hu&eacute;sped</h1>
                    <p class="ge-subtitle">
                        Actualiza los datos principales de <?= guest_edit_safe($nombre_huesped) ?> sin salir del flujo operativo del hotel.
                    </p>
                </div>
            </div>

            <div class="ge-hero-actions">
                <a href="<?= url('huespedes/' . $huesped_id) ?>" class="ge-link-btn">
                    <i class="fas fa-eye"></i>
                    Ver expediente
                </a>
                <?php $back_arrow_href = back_url('huespedes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('huespedes') ?>" class="ge-link-btn ms-back-legacy">
                    <i class="fas fa-arrow-left"></i>
                    Directorio
                </a>
            </div>
        </header>

        <form method="POST" action="<?= url('huespedes/' . $huesped_id . '/update') ?>" class="ge-form" id="guestEditForm" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="ge-layout">
                <main class="ge-main">
                    <section class="ge-panel">
                        <div class="ge-panel-head">
                            <div class="ge-panel-title">
                                <span class="ge-icon-box"><i class="fas fa-user"></i></span>
                                <div>
                                    <h2>Identidad y contacto</h2>
                                    <p>Datos visibles para recepci&oacute;n, b&uacute;squeda y confirmaciones.</p>
                                </div>
                            </div>
                        </div>

                        <div class="ge-panel-body">
                            <div class="ge-grid">
                                <div class="ge-field ge-field-full">
                                    <label class="ge-label" for="nombre_completo">
                                        Nombre completo <span class="ge-required">*</span>
                                    </label>
                                    <input type="text"
                                           id="nombre_completo"
                                           name="nombre_completo"
                                           value="<?= old('nombre_completo', guest_edit_safe($huesped['nombre_completo'] ?? '', '')) ?>"
                                           required
                                           autocomplete="name"
                                           placeholder="Ingrese el nombre completo del hu&eacute;sped"
                                           class="ge-control">
                                    <?php if (form_error('nombre_completo')): ?>
                                        <span class="ge-form-error"><?= form_error('nombre_completo') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="ge-field">
                                    <label class="ge-label" for="telefono">Telefono celular<?= $geRequiredMark('telefono') ?></label>
                                    <div class="ge-input-wrap">
                                        <i class="fas fa-phone"></i>
                                        <input type="tel"
                                               id="telefono"
                                               name="telefono"
                                               value="<?= old('telefono', guest_edit_safe($huesped['telefono'] ?? '', '')) ?>"
                                               autocomplete="tel"
                                               placeholder="10 d&iacute;gitos"
                                               class="ge-control has-icon"
                                               <?= $geGuestFieldRequired('telefono') ? 'required' : '' ?>>
                                    </div>
                                    <?php if (form_error('telefono')): ?>
                                        <span class="ge-form-error"><?= form_error('telefono') ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($geGuestFieldVisible('email')): ?>
                                    <div class="ge-field">
                                        <label class="ge-label" for="email">Email<?= $geRequiredMark('email') ?></label>
                                        <div class="ge-input-wrap">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email"
                                                   id="email"
                                                   name="email"
                                                   value="<?= old('email', guest_edit_safe($huesped['email'] ?? '', '')) ?>"
                                                   autocomplete="email"
                                                   placeholder="correo@ejemplo.com"
                                                   class="ge-control has-icon"
                                                   <?= $geGuestFieldRequired('email') ? 'required' : '' ?>>
                                        </div>
                                        <?php if (form_error('email')): ?>
                                            <span class="ge-form-error"><?= form_error('email') ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?= $geRenderHiddenColumn('email', $huesped['email'] ?? '') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <?php
                        $geDescuentoTipo = old('descuento_tipo', (string)($huesped['descuento_tipo'] ?? ''));
                        $geDescuentoValor = old('descuento_valor', guest_edit_safe($huesped['descuento_valor'] ?? '', ''));
                    ?>
                    <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('descuentos')): ?>
                    <section class="ge-panel">
                        <div class="ge-panel-head">
                            <div class="ge-panel-title">
                                <span class="ge-icon-box"><i class="fas fa-tags"></i></span>
                                <div>
                                    <h2>Descuento del hu&eacute;sped</h2>
                                    <p>Opcional. Se aplica autom&aacute;ticamente al cotizar reservaciones de este hu&eacute;sped (ajustable en cada reservaci&oacute;n).</p>
                                </div>
                            </div>
                        </div>
                        <div class="ge-panel-body">
                            <div class="ge-grid">
                                <div class="ge-field">
                                    <label class="ge-label" for="descuento_tipo">Tipo de descuento</label>
                                    <div class="ge-input-wrap">
                                        <i class="fas fa-percent"></i>
                                        <select id="descuento_tipo" name="descuento_tipo" class="ge-control has-icon">
                                            <option value="">Sin descuento</option>
                                            <option value="porcentaje" <?= $geDescuentoTipo === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
                                            <option value="monto" <?= $geDescuentoTipo === 'monto' ? 'selected' : '' ?>>Monto fijo ($)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="ge-field">
                                    <label class="ge-label" for="descuento_valor">Valor</label>
                                    <div class="ge-input-wrap">
                                        <i class="fas fa-tag"></i>
                                        <input type="number" id="descuento_valor" name="descuento_valor" min="0" step="0.01"
                                               value="<?= $geDescuentoValor ?>"
                                               placeholder="Ej. 10"
                                               class="ge-control has-icon">
                                    </div>
                                    <span class="ge-field-hint">Porcentaje (ej. 10 = 10%) o pesos, seg&uacute;n el tipo elegido.</span>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ($geGuestFieldVisible('procedencia_estado') || $geGuestFieldVisible('procedencia_ciudad') || $geGuestFieldVisible('nacionalidad')): ?>
                    <section class="ge-panel">
                        <div class="ge-panel-head">
                            <div class="ge-panel-title">
                                <span class="ge-icon-box"><i class="fas fa-map-location-dot"></i></span>
                                <div>
                                    <h2>Procedencia</h2>
                                    <p>Origen del hu&eacute;sped para reportes y lectura comercial.</p>
                                </div>
                            </div>
                        </div>

                        <div class="ge-panel-body">
                            <div class="ge-grid">
                                <?php if ($geGuestFieldVisible('procedencia_estado')): ?>
                                    <div class="ge-field">
                                        <label class="ge-label" for="procedencia_estado">Estado<?= $geRequiredMark('procedencia_estado') ?></label>
                                        <select id="procedencia_estado"
                                                name="procedencia_estado"
                                                class="ge-control"
                                                data-ms-combo="Escribe el estado..."
                                                data-ms-combo-empty="Selecciona un estado"
                                                <?= $geGuestFieldRequired('procedencia_estado') ? 'required' : '' ?>>
                                            <option value="">Seleccione un estado</option>
                                            <?php foreach ($estados as $estado): ?>
                                                <option value="<?= guest_edit_safe($estado, '') ?>" <?= old('procedencia_estado', $procedencia_estado) === $estado ? 'selected' : '' ?>>
                                                    <?= guest_edit_safe($estado) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (form_error('procedencia_estado')): ?>
                                            <span class="ge-form-error"><?= form_error('procedencia_estado') ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?= $geRenderHiddenColumn('procedencia_estado', $huesped['procedencia_estado'] ?? '') ?>
                                <?php endif; ?>

                                <?php if ($geGuestFieldVisible('procedencia_ciudad')): ?>
                                    <div class="ge-field">
                                        <label class="ge-label" for="procedencia_ciudad">Ciudad<?= $geRequiredMark('procedencia_ciudad') ?></label>
                                        <input type="text"
                                               id="procedencia_ciudad"
                                               name="procedencia_ciudad"
                                               value="<?= old('procedencia_ciudad', guest_edit_safe($huesped['procedencia_ciudad'] ?? '', '')) ?>"
                                               placeholder="Ciudad de origen"
                                               class="ge-control"
                                               <?= $geGuestFieldRequired('procedencia_ciudad') ? 'required' : '' ?>>
                                        <?php if (form_error('procedencia_ciudad')): ?>
                                            <span class="ge-form-error"><?= form_error('procedencia_ciudad') ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?= $geRenderHiddenColumn('procedencia_ciudad', $huesped['procedencia_ciudad'] ?? '') ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($geGuestFieldVisible('nacionalidad')): ?>
                                <?php
                                    $geNacStored = is_scalar($guestExtraValues['nacionalidad'] ?? null) ? (string)$guestExtraValues['nacionalidad'] : '';
                                    $geNacValue = $geOldExtra('nacionalidad', $geNacStored);
                                    $geNacRequired = $geGuestFieldRequired('nacionalidad');
                                ?>
                                <div class="ge-field ge-field-full" style="margin-top: 14px;">
                                    <label class="ge-label" for="ge_nacionalidad">Pa&iacute;s de origen<?= $geNacRequired ? ' <span class="ge-required">*</span>' : '' ?></label>
                                    <?= ms_select_paises([
                                        'name'        => 'extras[nacionalidad]',
                                        'id'          => 'ge_nacionalidad',
                                        'value'       => $geNacValue,
                                        'class'       => 'ge-control',
                                        'required'    => $geNacRequired,
                                        'placeholder' => 'Solo si el huésped es extranjero',
                                    ]) ?>
                                    <?php if (form_error('extras[nacionalidad]')): ?>
                                        <span class="ge-form-error"><?= form_error('extras[nacionalidad]') ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php else: ?>
                        <?= $geRenderHiddenColumn('procedencia_estado', $huesped['procedencia_estado'] ?? '') ?>
                        <?= $geRenderHiddenColumn('procedencia_ciudad', $huesped['procedencia_ciudad'] ?? '') ?>
                    <?php endif; ?>

                    <?php
                    $geExtraGuestFields = array_filter($geGuestVisibleFields('guest'), function ($definition, $key) {
                        if ($key === 'nacionalidad') {
                            return false; // se captura en la seccion Procedencia
                        }
                        return in_array(($definition['storage'] ?? 'column'), ['extra', 'document'], true);
                    }, ARRAY_FILTER_USE_BOTH);
                    ?>
                    <?php if (!empty($geExtraGuestFields)): ?>
                        <section class="ge-panel">
                            <div class="ge-panel-head">
                                <div class="ge-panel-title">
                                    <span class="ge-icon-box"><i class="fas fa-id-card"></i></span>
                                    <div>
                                        <h2>Datos adicionales</h2>
                                        <p>Campos personalizados definidos por la configuracion del hotel.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="ge-panel-body">
                                <div class="ge-grid">
                                    <?php foreach ($geExtraGuestFields as $fieldKey => $fieldDefinition): ?>
                                        <?= $geRenderGuestExtraField($fieldKey, $fieldDefinition) ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($geVehicleVisibleFields) && (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('vehiculos'))): ?>
                    <section class="ge-panel">
                        <div class="ge-panel-head">
                            <div class="ge-panel-title">
                                <span class="ge-icon-box"><i class="fas fa-car-side"></i></span>
                                <div>
                                    <h2>Veh&iacute;culos vinculados</h2>
                                    <p><span data-ge-vehicle-count-label><?= number_format($vehiculos_count) ?> veh&iacute;culo<?= $vehiculos_count === 1 ? '' : 's' ?> vinculado<?= $vehiculos_count === 1 ? '' : 's' ?></span> al hu&eacute;sped.</p>
                                </div>
                            </div>
                        </div>

                        <div class="ge-panel-body">
                            <div class="ge-context-note">
                                <i class="fas fa-circle-info"></i>
                                <div>
                                    <strong>Puede registrar veh&iacute;culos sin salir de esta edici&oacute;n.</strong>
                                    <span>El veh&iacute;culo se guarda desde esta secci&oacute;n; los cambios del perfil principal siguen usando el bot&oacute;n inferior.</span>
                                </div>
                            </div>

                            <div id="geVehicleList" class="ge-vehicle-list" <?= empty($vehiculos) ? 'hidden' : '' ?>>
                                <?php foreach ($vehiculos as $vehiculo): ?>
                                    <?php
                                        $vehiculo_nombre = trim(($vehiculo['marca'] ?? '') . ' ' . ($vehiculo['modelo'] ?? ''));
                                        $vehiculo_placas = trim((string)($vehiculo['placas'] ?? ''));
                                        $vehiculo_color = trim((string)($vehiculo['color'] ?? ''));
                                        $ubicacion = $estacionamientos[$vehiculo['estacionamiento'] ?? ''] ?? 'No especificado';
                                    ?>
                                    <article class="ge-vehicle-card">
                                        <div class="ge-vehicle-main">
                                            <span class="ge-vehicle-icon"><i class="fas fa-car"></i></span>
                                            <div>
                                                <strong><?= guest_edit_safe($vehiculo_nombre, 'Vehiculo') ?></strong>
                                                <span>
                                                    Placas: <?= guest_edit_safe($vehiculo_placas, 'Sin placas') ?>
                                                    <?php if ($vehiculo_color !== ''): ?>
                                                        &middot; Color: <?= guest_edit_safe($vehiculo_color) ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="ge-parking-badge"><?= guest_edit_safe($ubicacion) ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <div id="geVehicleEmpty" class="ge-empty" <?= !empty($vehiculos) ? 'hidden' : '' ?>>
                                <i class="fas fa-car-rear"></i>
                                <strong>Sin veh&iacute;culos registrados</strong>
                                <span>Agregue placas, color y ubicaci&oacute;n de estacionamiento desde esta pantalla.</span>
                            </div>

                            <div class="ge-vehicle-capture" id="geVehicleCapture">
                                <div class="ge-vehicle-capture-head">
                                    <div>
                                        <h3 class="ge-vehicle-capture-title">
                                            <i class="fas fa-car-side"></i>
                                            Nuevo veh&iacute;culo
                                        </h3>
                                        <p class="ge-vehicle-capture-copy">Capture los datos del auto y gu&aacute;rdelo en el expediente del hu&eacute;sped.</p>
                                    </div>
                                </div>

                                <div class="ge-inline-grid">
                                    <?= $geRenderVehicleInlineFields() ?>
                                </div>

                                <div class="ge-form-alert" data-vehicle-feedback hidden role="alert" aria-live="assertive">
                                    <i class="fas fa-circle-info"></i>
                                    <span></span>
                                </div>

                                <div class="ge-vehicle-capture-actions">
                                    <button type="reset" form="formAgregarVehiculo" class="ge-vehicle-clear">
                                        <i class="fas fa-rotate-left"></i>
                                        Limpiar
                                    </button>
                                    <button type="submit" form="formAgregarVehiculo" class="ge-vehicle-save">
                                        <i class="fas fa-save"></i>
                                        Guardar veh&iacute;culo
                                    </button>
                                </div>
                            </div>

                            <a href="<?= url('huespedes/' . $huesped_id) ?>#vehiculos" class="ge-vehicle-link">
                                <span><i class="fas fa-screwdriver-wrench"></i> Abrir gesti&oacute;n avanzada en expediente</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ($geGuestFieldVisible('notas')): ?>
                    <section class="ge-panel">
                        <div class="ge-panel-head">
                            <div class="ge-panel-title">
                                <span class="ge-icon-box"><i class="fas fa-note-sticky"></i></span>
                                <div>
                                    <h2>Notas internas</h2>
                                    <p>Observaciones &uacute;tiles para futuras estancias y recepci&oacute;n.</p>
                                </div>
                            </div>
                        </div>

                        <div class="ge-panel-body">
                            <label class="ge-label" for="notas">Notas<?= $geRequiredMark('notas') ?></label>
                            <textarea id="notas"
                                      name="notas"
                                      rows="4"
                                      placeholder="Cualquier informaci&oacute;n adicional sobre el hu&eacute;sped..."
                                      class="ge-control"
                                      <?= $geGuestFieldRequired('notas') ? 'required' : '' ?>><?= old('notas', guest_edit_safe($notas_huesped, '')) ?></textarea>
                            <?php if (form_error('notas')): ?>
                                <span class="ge-form-error"><?= form_error('notas') ?></span>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php else: ?>
                        <?= $geRenderHiddenColumn('notas', $huesped['notas'] ?? '') ?>
                    <?php endif; ?>

                    <div class="ge-actions">
                        <div class="ge-actions-copy">
                            <strong>Guardar cambios del expediente</strong>
                            <span>Se actualizar&aacute;n solo los datos principales del hu&eacute;sped.</span>
                        </div>
                        <div class="ge-actions-buttons">
                            <a href="<?= back_url('huespedes/' . $huesped_id) ?>" class="ge-btn ge-btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="ge-btn ge-btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar cambios
                            </button>
                        </div>
                    </div>
                </main>

                <aside class="ge-side" aria-label="Resumen del huesped">
                    <section class="ge-side-card ge-preview">
                        <div class="ge-preview-avatar" id="guestEditPreviewAvatar" aria-hidden="true"><?= guest_edit_safe($inicial_huesped, 'H') ?></div>
                        <h2 class="ge-preview-name" id="guestEditPreviewName"><?= guest_edit_safe($nombre_huesped) ?></h2>
                        <p>Vista previa del expediente tal como lo vera recepci&oacute;n al consultar al hu&eacute;sped.</p>
                        <div class="ge-preview-tags">
                            <span id="guestEditPreviewContact"><i class="fas fa-address-book"></i> <?= guest_edit_safe($contacto_label) ?></span>
                            <span id="guestEditPreviewOrigin"><i class="fas fa-location-dot"></i> <?= guest_edit_safe($procedencia_label) ?></span>
                            <span><i class="fas fa-car"></i> <span data-ge-vehicle-count-label><?= number_format($vehiculos_count) ?> veh&iacute;culo<?= $vehiculos_count === 1 ? '' : 's' ?> vinculado<?= $vehiculos_count === 1 ? '' : 's' ?></span></span>
                        </div>
                    </section>

                    <section class="ge-side-card">
                        <h3>Informaci&oacute;n del sistema</h3>
                        <p>Datos de referencia. No se modifican desde este formulario.</p>
                        <div class="ge-meta-list">
                            <div class="ge-meta-row">
                                <span>ID del hu&eacute;sped</span>
                                <strong>#<?= $huesped_id ?></strong>
                            </div>
                            <div class="ge-meta-row">
                                <span>Registro</span>
                                <strong><?= guest_edit_safe($fecha_registro) ?></strong>
                            </div>
                            <div class="ge-meta-row">
                                <span>Ultima actualizaci&oacute;n</span>
                                <strong><?= guest_edit_safe($fecha_actualizacion) ?></strong>
                            </div>
                        </div>
                    </section>

                    <section class="ge-side-card">
                        <h3>Antes de guardar</h3>
                        <div class="ge-tips">
                            <span><i class="fas fa-check-circle"></i> Nombre completo y telefono celular son datos base.</span>
                            <span><i class="fas fa-check-circle"></i> Los campos visibles dependen de la configuracion del hotel.</span>
                            <span><i class="fas fa-check-circle"></i> Los datos ocultos existentes se conservan.</span>
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    </div>
</div>

<form id="formAgregarVehiculo"
      method="POST"
      action="<?= url('huespedes/agregar-vehiculo') ?>"
      hidden>
    <input type="hidden" name="huesped_id" value="<?= $huesped_id ?>">
    <?= csrf_field() ?>
</form>

<script>
let geVehicleCount = <?= (int)$vehiculos_count ?>;
const geParkingLabels = <?= json_encode($estacionamientos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const geParkingDefault = <?= json_encode($estacionamientoDefault, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function geEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function geVehicleCountLabel(count) {
    return `${Number(count).toLocaleString('es-MX')} veh\u00edculo${Number(count) === 1 ? '' : 's'} vinculado${Number(count) === 1 ? '' : 's'}`;
}

function geRefreshVehicleCount() {
    document.querySelectorAll('[data-ge-vehicle-count-label]').forEach(function(element) {
        element.textContent = geVehicleCountLabel(geVehicleCount);
    });
}

function geShowVehicleFeedback(form, type, message) {
    const alertBox = document.querySelector('#geVehicleCapture [data-vehicle-feedback]');
    if (!alertBox) {
        return;
    }

    const icon = alertBox.querySelector('i');
    const text = alertBox.querySelector('span');
    alertBox.hidden = false;
    alertBox.classList.add('is-visible');
    alertBox.classList.toggle('is-success', type === 'success');
    if (icon) {
        icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-circle-exclamation';
    }
    if (text) {
        text.textContent = message || 'No se pudo completar la accion.';
    }
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function geClearVehicleFeedback(form) {
    const alertBox = document.querySelector('#geVehicleCapture [data-vehicle-feedback]');
    if (!alertBox) {
        return;
    }

    alertBox.hidden = true;
    alertBox.classList.remove('is-visible', 'is-success');
}

function geSetVehicleSubmitting(form, isSubmitting) {
    const button = document.querySelector('.ge-vehicle-save[form="formAgregarVehiculo"]');
    if (!button) {
        return;
    }

    if (!button.dataset.defaultHtml) {
        button.dataset.defaultHtml = button.innerHTML;
    }

    button.disabled = isSubmitting;
    button.innerHTML = isSubmitting
        ? '<i class="fas fa-spinner fa-spin"></i> Guardando'
        : button.dataset.defaultHtml;
}

function geBuildVehicleCard(vehicle) {
    const article = document.createElement('article');
    const marca = String(vehicle.marca || '').trim();
    const modelo = String(vehicle.modelo || '').trim();
    const placas = String(vehicle.placas || '').trim();
    const color = String(vehicle.color || '').trim();
    const parking = String(vehicle.estacionamiento || geParkingDefault || '').trim();
    const nombre = `${marca} ${modelo}`.trim() || 'Vehiculo';
    const parkingLabel = geParkingLabels[parking] || 'No especificado';
    const details = [
        `Placas: ${placas || 'Sin placas'}`,
        color ? `Color: ${color}` : ''
    ].filter(Boolean).join(' · ');

    article.className = 'ge-vehicle-card';
    article.innerHTML = `
        <div class="ge-vehicle-main">
            <span class="ge-vehicle-icon"><i class="fas fa-car"></i></span>
            <div>
                <strong>${geEscapeHtml(nombre)}</strong>
                <span>${geEscapeHtml(details)}</span>
            </div>
        </div>
        <span class="ge-parking-badge">${geEscapeHtml(parkingLabel)}</span>
    `;

    return article;
}

function geAppendVehicleCard(vehicle) {
    const list = document.getElementById('geVehicleList');
    const empty = document.getElementById('geVehicleEmpty');
    if (!list) {
        return;
    }

    list.hidden = false;
    if (empty) {
        empty.hidden = true;
    }
    list.prepend(geBuildVehicleCard(vehicle));
    geVehicleCount += 1;
    geRefreshVehicleCount();
}

function geVehicleFromFormData(formData) {
    return {
        marca: formData.get('marca') || '',
        modelo: formData.get('modelo') || '',
        placas: formData.get('placas') || '',
        color: formData.get('color') || '',
        estacionamiento: formData.get('estacionamiento') || geParkingDefault
    };
}

function geVehicleFormData(form) {
    const formData = new FormData(form);
    document.querySelectorAll('[form="formAgregarVehiculo"][name]').forEach(function(control) {
        if (control.type === 'radio' && !control.checked) {
            return;
        }
        if (formData.has(control.name)) {
            return;
        }
        formData.append(control.name, control.value || '');
    });
    return formData;
}

document.querySelector('[form="formAgregarVehiculo"][name="placas"]')?.addEventListener('input', function(event) {
    event.target.value = event.target.value.toUpperCase();
});

document.getElementById('formAgregarVehiculo')?.addEventListener('reset', function() {
    geClearVehicleFeedback(this);
    geSetVehicleSubmitting(this, false);
});

document.getElementById('formAgregarVehiculo')?.addEventListener('submit', function(event) {
    event.preventDefault();

    const form = this;
    geClearVehicleFeedback(form);
    geSetVehicleSubmitting(form, true);
    const formData = geVehicleFormData(form);
    const vehicleDraft = geVehicleFromFormData(formData);

    fetch(form.action, {
        method: form.method || 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        return response.json().catch(function() {
            return {
                success: false,
                message: response.ok ? 'Respuesta invalida del servidor.' : 'No se pudo guardar el vehiculo.'
            };
        });
    })
    .then(function(data) {
        if (data.success) {
            geAppendVehicleCard(vehicleDraft);
            form.reset();
            geSetVehicleSubmitting(form, false);
            geShowVehicleFeedback(form, 'success', data.message || 'Vehiculo agregado correctamente.');
            document.querySelector('#geVehicleCapture input[form="formAgregarVehiculo"]:not([type="radio"]), #geVehicleCapture select[form="formAgregarVehiculo"], #geVehicleCapture textarea[form="formAgregarVehiculo"]')?.focus();
        } else {
            geSetVehicleSubmitting(form, false);
            geShowVehicleFeedback(form, 'error', data.message || 'No se pudo agregar el vehiculo.');
        }
    })
    .catch(function() {
        geSetVehicleSubmitting(form, false);
        geShowVehicleFeedback(form, 'error', 'Ocurrio un error al agregar el vehiculo.');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('guestEditForm');
    const nombreInput = form?.querySelector('input[name="nombre_completo"]');
    const telefonoInput = form?.querySelector('input[name="telefono"]');
    const emailInput = form?.querySelector('input[name="email"]');
    const estadoInput = form?.querySelector('select[name="procedencia_estado"]');
    const ciudadInput = form?.querySelector('input[name="procedencia_ciudad"]');
    const previewName = document.getElementById('guestEditPreviewName');
    const previewAvatar = document.getElementById('guestEditPreviewAvatar');
    const heroAvatar = document.getElementById('guestEditAvatar');
    const previewContact = document.getElementById('guestEditPreviewContact');
    const previewOrigin = document.getElementById('guestEditPreviewOrigin');

    function safeText(value, fallback) {
        const text = (value || '').trim();
        return text || fallback;
    }

    function setPreviewChip(element, iconClass, text) {
        if (!element) {
            return;
        }

        const icon = document.createElement('i');
        icon.className = iconClass;
        element.replaceChildren(icon, ' ' + text);
    }

    function updatePreview() {
        const nombre = safeText(nombreInput?.value, 'Huesped');
        const initial = nombre.charAt(0).toUpperCase();
        const hasContact = Boolean(safeText(telefonoInput?.value, '') || safeText(emailInput?.value, ''));
        const estado = safeText(estadoInput?.value, '');
        const ciudad = safeText(ciudadInput?.value, '');
        let procedencia = estado || 'Sin procedencia';

        if (ciudad) {
            procedencia += ' - ' + ciudad;
        }

        if (previewName) previewName.textContent = nombre;
        if (previewAvatar) previewAvatar.textContent = initial;
        if (heroAvatar) heroAvatar.textContent = initial;
        setPreviewChip(previewContact, 'fas fa-address-book', hasContact ? 'Contacto disponible' : 'Contacto pendiente');
        setPreviewChip(previewOrigin, 'fas fa-location-dot', procedencia);
    }

    telefonoInput?.addEventListener('input', function(event) {
        let value = event.target.value.replace(/\D/g, '');
        if (value.length > 10) {
            value = value.slice(0, 10);
        }
        event.target.value = value;
        updatePreview();
    });

    [nombreInput, emailInput, estadoInput, ciudadInput].forEach(function(input) {
        input?.addEventListener('input', updatePreview);
        input?.addEventListener('change', updatePreview);
    });

    updatePreview();

    // Llegadas por deep link (#telefono desde la cola de Mensajes, etc.):
    // el ancla ya scrollea, aqui solo se deja el cursor listo para escribir.
    try {
        // Un hash raro (o vacio) haria estallar a querySelector: se ignora.
        const campoDelHash = location.hash.length > 1 ? form?.querySelector(location.hash + '.ge-control') : null;
        if (campoDelHash && typeof campoDelHash.focus === 'function') {
            campoDelHash.focus();
            if (typeof campoDelHash.select === 'function') campoDelHash.select();
        }
    } catch (e) { /* hash no usable como selector */ }
});
</script>
