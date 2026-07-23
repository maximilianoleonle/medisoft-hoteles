<?php
/**
 * Vista de detalle de huesped.
 * Rediseño operativo tipo expediente del huésped.
 */

$huesped = $huesped ?? [];
$vehiculos = $vehiculos ?? [];
$reservaciones = $reservaciones ?? [];
$total_reservaciones = (int)($total_reservaciones ?? 0);
$total_gastado = (float)($total_gastado ?? 0);
$gasto_promedio = $total_reservaciones > 0 ? $total_gastado / $total_reservaciones : 0;
$vehiculos_count = count($vehiculos);
$reservaciones_count = count($reservaciones);
$perfilOperativo = is_array($perfilOperativo ?? null) ? $perfilOperativo : [];
$perfilReservaciones = is_array($perfilOperativo['reservaciones'] ?? null) ? $perfilOperativo['reservaciones'] : [];
$perfilVehiculos = is_array($perfilOperativo['vehiculos'] ?? null) ? $perfilOperativo['vehiculos'] : [];
$perfilCxc = is_array($perfilOperativo['cxc'] ?? null) ? $perfilOperativo['cxc'] : [];
$perfilDocumentos = is_array($perfilOperativo['documentos'] ?? null) ? $perfilOperativo['documentos'] : [];
$perfilAlertas = is_array($perfilOperativo['alertas'] ?? null) ? $perfilOperativo['alertas'] : [];
$perfilScore = (int)($perfilOperativo['score'] ?? 0);
$perfilClasificacion = trim((string)($perfilOperativo['clasificacion'] ?? 'Sin historial'));
$perfilProximaVisita = $perfilReservaciones['proxima_visita'] ?? null;
$perfilNoches = (int)($perfilReservaciones['noches'] ?? 0);
$perfilCxcSaldo = (float)($perfilCxc['saldo_pendiente'] ?? 0);
$perfilCxcCuentas = (int)($perfilCxc['cuentas_pendientes'] ?? 0);
$perfilDocumentosActivos = (int)($perfilDocumentos['activos'] ?? 0);
$perfilAlertColors = [
    'warning' => '#B45309',
    'success' => '#148653',
    'info' => '#2563EB',
];
$perfilAlertIcons = [
    'warning' => 'fas fa-triangle-exclamation',
    'success' => 'fas fa-circle-check',
    'info' => 'fas fa-circle-info',
];
$nombre_huesped = trim((string)($huesped['nombre_completo'] ?? 'Huesped'));
$inicial_huesped = function_exists('mb_substr') && function_exists('mb_strtoupper')
    ? mb_strtoupper(mb_substr($nombre_huesped !== '' ? $nombre_huesped : 'H', 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($nombre_huesped !== '' ? $nombre_huesped : 'H', 0, 1));
$procedencia_estado = trim((string)($huesped['procedencia_estado'] ?? ''));
$procedencia_ciudad = trim((string)($huesped['procedencia_ciudad'] ?? ''));
$procedencia_label = $procedencia_estado !== '' ? $procedencia_estado : 'No especificada';
if ($procedencia_ciudad !== '') {
    $procedencia_label .= ' · ' . $procedencia_ciudad;
}
$telefono = trim((string)($huesped['telefono'] ?? ''));
$email = trim((string)($huesped['email'] ?? ''));
$contacto_label = $telefono !== '' || $email !== '' ? 'Contacto disponible' : 'Contacto incompleto';
$ultima_visita_label = $ultima_visita ? format_date($ultima_visita) : 'Sin visitas completadas';
$es_cliente_frecuente = $total_reservaciones >= 3;
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
if (empty($estacionamientos) && class_exists('HuespedVehiculo')) {
    $estacionamientos = HuespedVehiculo::getEstacionamientos();
}
if (empty($estacionamientos)) {
    $estacionamientos = ['coches' => 'Coches'];
}
reset($estacionamientos);
$estacionamientoDefault = (string)key($estacionamientos);

if (!function_exists('guest_detail_safe')) {
    function guest_detail_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('guest_detail_status')) {
    function guest_detail_status($estado) {
        $map = [
            'confirmada' => ['label' => 'Confirmada', 'color' => '#2563EB', 'soft' => '#EEF4FF'],
            'checked_in' => ['label' => 'Check-in', 'color' => '#148653', 'soft' => '#E8F7EF'],
            'checked_out' => ['label' => 'Check-out', 'color' => '#64748B', 'soft' => '#F1F5F9'],
            'completada' => ['label' => 'Completada', 'color' => '#148653', 'soft' => '#E8F7EF'],
            'cancelada' => ['label' => 'Cancelada', 'color' => '#B83D35', 'soft' => '#FFF0EF'],
        ];
        return $map[$estado] ?? ['label' => 'Sin estado', 'color' => '#64748B', 'soft' => '#F1F5F9'];
    }
}

if (!function_exists('guest_detail_json_attr')) {
    function guest_detail_json_attr($value) {
        return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
    }
}

$guestDocuments = array_values(is_array($documentosEntidad ?? null) ? $documentosEntidad : []);
$guestDocBytes = function ($bytes): string {
    $bytes = (int)($bytes ?? 0);
    if ($bytes <= 0) {
        return '-';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $size = (float)$bytes;
    $index = 0;
    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
    }

    return number_format($size, $index === 0 ? 0 : 1) . ' ' . $units[$index];
};
$guestDocIcon = function ($documento): array {
    $mime = strtolower((string)($documento['mime_type'] ?? ''));
    $name = strtolower((string)(($documento['nombre_original'] ?? '') ?: ($documento['titulo'] ?? '')));

    if (strpos($mime, 'pdf') !== false || substr($name, -4) === '.pdf') {
        return ['PDF', 'is-pdf', 'fa-file-pdf'];
    }
    if (strpos($mime, 'image/') === 0 || preg_match('/\.(jpg|jpeg|png|webp)$/', $name)) {
        return ['JPG', 'is-image', 'fa-image'];
    }

    return ['DOC', 'is-file', 'fa-file-lines'];
};
$guestDocPreviewKind = function ($documento): string {
    $mime = strtolower((string)($documento['mime_type'] ?? ''));
    $name = strtolower((string)(($documento['nombre_original'] ?? '') ?: ($documento['titulo'] ?? '')));

    if (strpos($mime, 'image/') === 0 || preg_match('/\.(jpg|jpeg|png|webp)$/', $name)) {
        return 'image';
    }
    if (strpos($mime, 'pdf') !== false || substr($name, -4) === '.pdf') {
        return 'pdf';
    }

    return 'file';
};
$guestDocScore = function ($documento): int {
    $haystack = strtolower(trim(implode(' ', [
        (string)($documento['titulo'] ?? ''),
        (string)($documento['nombre_original'] ?? ''),
        (string)($documento['descripcion'] ?? ''),
        (string)($documento['etiquetas'] ?? ''),
        (string)($documento['tipo_nombre'] ?? ''),
        (string)($documento['tipo_clave'] ?? ''),
        (string)($documento['relacion'] ?? ''),
    ])));

    foreach (['ine', 'identificaci', 'id oficial', 'oficial', 'licencia', 'pasaporte'] as $needle) {
        if (strpos($haystack, $needle) !== false) {
            return 0;
        }
    }

    return 10;
};
usort($guestDocuments, function ($a, $b) use ($guestDocScore, $guestDocPreviewKind) {
    $scoreA = $guestDocScore($a);
    $scoreB = $guestDocScore($b);
    if ($scoreA !== $scoreB) {
        return $scoreA <=> $scoreB;
    }

    $previewRank = ['image' => 0, 'pdf' => 1, 'file' => 2];
    $rankA = $previewRank[$guestDocPreviewKind($a)] ?? 2;
    $rankB = $previewRank[$guestDocPreviewKind($b)] ?? 2;
    if ($rankA !== $rankB) {
        return $rankA <=> $rankB;
    }

    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
});
$guestPrimaryDoc = $guestDocuments[0] ?? null;
$guestDocCount = count($guestDocuments);
$guestDocPanelClass = 'guest-docs ' . ($guestDocCount > 1 ? 'is-gallery' : ($guestDocCount === 1 ? 'is-single' : 'is-empty'));
$guestDocEntityId = (int)($huesped['id'] ?? 0);
$guestDocEntityQuery = $guestDocEntityId > 0 ? '?entidad_tipo=huesped&entidad_id=' . $guestDocEntityId : '';

// Este panel es centro documental dentro de la ficha del huesped: se pinta
// con el mismo contrato que el partial documentos_entidad.
$guestDocsVisible = !function_exists('puede_ver_documentos_vinculados') || puede_ver_documentos_vinculados();
$guestDocsPuedeVincular = !function_exists('puede_vincular_documentos') || puede_vincular_documentos();

$guestParkingOptionsHtml = function ($inputClass = '') use ($estacionamientos, $estacionamientoDefault) {
    $html = '';
    foreach ($estacionamientos as $parkingCode => $parkingLabel) {
        $parkingCode = (string)$parkingCode;
        $parkingCodeEsc = htmlspecialchars($parkingCode, ENT_QUOTES, 'UTF-8');
        $parkingLabelEsc = htmlspecialchars((string)$parkingLabel, ENT_QUOTES, 'UTF-8');
        $classAttr = trim((string)$inputClass);
        $classHtml = $classAttr !== '' ? ' class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '"' : '';
        $checkedAttr = $parkingCode === $estacionamientoDefault ? ' checked' : '';
        $html .= '<label class="guest-radio-card">';
        $html .= '<input type="radio" name="estacionamiento" value="' . $parkingCodeEsc . '"' . $classHtml . $checkedAttr . '>';
        $html .= '<span><i class="fas fa-car"></i>' . $parkingLabelEsc . '</span>';
        $html .= '</label>';
    }

    return $html;
};

$guestFieldPolicy = is_array($guestFieldPolicy ?? null)
    ? $guestFieldPolicy
    : (function_exists('hotel_guest_field_policy') ? hotel_guest_field_policy() : ['fields' => []]);
$guestExtraValues = function_exists('hotel_guest_decode_extra_json')
    ? hotel_guest_decode_extra_json($huesped['datos_extra_json'] ?? null)
    : [];
$guestVisibleFields = function ($scope) use ($guestFieldPolicy) {
    return function_exists('hotel_guest_visible_fields') ? hotel_guest_visible_fields($scope, $guestFieldPolicy) : [];
};
$guestFieldRequired = function ($key) use ($guestFieldPolicy) {
    return function_exists('hotel_guest_field_required') ? hotel_guest_field_required($key, $guestFieldPolicy) : false;
};
$guestExtraDisplayFields = array_filter($guestVisibleFields('guest'), function ($definition) {
    return ($definition['storage'] ?? 'column') === 'extra';
});
$guestVehicleVisibleFields = $guestVisibleFields('vehicle');
$guestRequiredHtml = function ($key) use ($guestFieldRequired) {
    return $guestFieldRequired($key) ? ' <span class="text-red-500">*</span>' : '';
};
$guestRenderVehicleModalFields = function ($mode = 'add') use ($guestVehicleVisibleFields, $guestFieldRequired, $guestParkingOptionsHtml, $guestRequiredHtml) {
    ob_start();
    foreach ($guestVehicleVisibleFields as $fieldKey => $definition) {
        $input = (string)($definition['input'] ?? 'text');
        $storage = (string)($definition['storage'] ?? 'extra');
        $fieldName = (string)($definition['field'] ?? $fieldKey);
        $name = $storage === 'column' ? $fieldName : 'extras[' . $fieldKey . ']';
        $id = $mode === 'edit' ? 'edit_' . ($storage === 'column' ? $fieldName : 'extra_' . $fieldKey) : '';
        $label = htmlspecialchars((string)($definition['label'] ?? $fieldKey), ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars((string)($definition['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8');
        $required = $guestFieldRequired($fieldKey);
        $max = (int)($definition['max'] ?? 0);
        $requiredAttr = $required ? ' required' : '';
        $maxAttr = $max > 0 ? ' maxlength="' . $max . '"' : '';
        $dataExtra = $mode === 'edit' && $storage !== 'column' ? ' data-edit-extra="' . htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') . '"' : '';
        ?>
        <?php if ($input === 'parking'): ?>
            <div class="guest-radio-group">
                <label><?= $label ?><?= $guestRequiredHtml($fieldKey) ?></label>
                <div class="guest-radio-options">
                    <?= $guestParkingOptionsHtml($mode === 'edit' ? 'edit-estacionamiento' : '') ?>
                </div>
            </div>
        <?php else: ?>
            <div class="guest-form-field<?= $input === 'textarea' ? ' is-wide' : '' ?>">
                <label><?= $label ?><?= $guestRequiredHtml($fieldKey) ?></label>
                <?php if ($input === 'textarea'): ?>
                    <textarea name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                              <?= $id !== '' ? 'id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                              rows="<?= (int)($definition['rows'] ?? 2) ?>"
                              placeholder="<?= $placeholder ?>"
                              <?= $dataExtra ?>
                              <?= $requiredAttr ?>
                              <?= $maxAttr ?>></textarea>
                <?php elseif ($input === 'select'): ?>
                    <select name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $id !== '' ? 'id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                            <?= $dataExtra ?>
                            <?= $requiredAttr ?>>
                        <option value="">Seleccione una opcion</option>
                        <?php foreach (($definition['options'] ?? []) as $optionValue => $optionLabel): ?>
                            <option value="<?= htmlspecialchars((string)$optionValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?= in_array($input, ['email', 'tel', 'date'], true) ? htmlspecialchars($input, ENT_QUOTES, 'UTF-8') : 'text' ?>"
                           name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                           <?= $id !== '' ? 'id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                           placeholder="<?= $placeholder ?>"
                           <?= !empty($definition['uppercase']) ? 'style="text-transform: uppercase"' : '' ?>
                           <?= !empty($definition['uppercase']) ? 'class="font-mono"' : '' ?>
                           <?= $dataExtra ?>
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

<style>
.guest-detail-view {
    --gd-primary: var(--brand-primary, #1B2746);
    --gd-secondary: var(--brand-secondary, #0F172A);
    --gd-accent: var(--brand-accent, #BD9441);
    --gd-bg: color-mix(in srgb, var(--gd-accent) 9%, #F7F2EA);
    --gd-surface: rgba(255, 253, 248, .92);
    --gd-surface-solid: #FFFDF8;
    --gd-line: color-mix(in srgb, var(--gd-primary) 13%, #E9DDCF);
    --gd-line-soft: color-mix(in srgb, var(--gd-primary) 8%, #EFE6DA);
    --gd-heading: #111827;
    --gd-text: #172033;
    --gd-text-soft: #334155;
    --gd-muted: #728096;
    --gd-accent-readable: color-mix(in srgb, var(--gd-accent) 66%, #3B2D12);
    --gd-success: #148653;
    --gd-danger: #B83D35;
    min-height: 100vh;
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--gd-primary) 5%, transparent) 0 1px, transparent 1px 30px),
        radial-gradient(circle at 88% 7%, color-mix(in srgb, var(--gd-accent) 22%, transparent), transparent 30rem),
        linear-gradient(180deg, var(--gd-bg), #FBFAF6 48%, #F3ECE2);
    color: var(--gd-text);
}

.guest-detail-shell {
    width: min(1500px, calc(100% - 30px));
    margin: 0 auto;
    padding: 26px 0 46px;
}

.guest-breadcrumb {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 12px;
    color: var(--gd-muted);
    font-size: .84rem;
    font-weight: 800;
}

.guest-breadcrumb a {
    color: var(--gd-text-soft);
}

.guest-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(270px, 360px);
    gap: 18px;
    align-items: stretch;
    margin-bottom: 16px;
}

.guest-identity-card {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 18px;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 26%, transparent);
    border-radius: 26px;
    background:
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--gd-accent) 26%, transparent), transparent 18rem),
        linear-gradient(135deg, rgba(255,253,248,.97), rgba(248,241,230,.92));
    box-shadow: 0 26px 72px -52px rgba(15, 23, 42, .68);
    padding: clamp(20px, 3vw, 32px);
}

.guest-identity-card::after {
    content: "";
    position: absolute;
    inset: auto 24px 0 auto;
    width: 220px;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, transparent, var(--gd-accent), var(--gd-primary));
    opacity: .72;
}

.guest-avatar {
    width: clamp(76px, 10vw, 118px);
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    border-radius: 24px;
    background:
        linear-gradient(145deg, var(--gd-primary), color-mix(in srgb, var(--gd-primary) 78%, #000000));
    color: #FFFDF8;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.1rem, 5vw, 4.4rem);
    font-weight: 800;
    box-shadow: 0 20px 44px -28px rgba(15, 23, 42, .72);
}

.guest-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--gd-accent-readable);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.guest-identity-card h1 {
    margin: 9px 0 8px;
    color: var(--gd-heading);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.25rem, 5vw, 5rem);
    line-height: .91;
    font-weight: 800;
    letter-spacing: 0;
    text-wrap: balance;
}

.guest-identity-card p {
    max-width: 68ch;
    margin: 0;
    color: var(--gd-muted);
    font-weight: 700;
    line-height: 1.55;
}

.guest-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}

.guest-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 18%, transparent);
    border-radius: 12px;
    background: rgba(255,255,255,.68);
    color: var(--gd-text-soft);
    padding: 8px 10px;
    font-size: .8rem;
    font-weight: 900;
}

.guest-action-rail {
    display: grid;
    gap: 10px;
    align-content: center;
    border: 1px solid var(--gd-line);
    border-radius: 22px;
    background: rgba(255,253,248,.88);
    box-shadow: 0 20px 54px -42px rgba(15, 23, 42, .58);
    padding: 16px;
}

.guest-action {
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border: 1px solid var(--gd-line);
    border-radius: 14px;
    background: #FFFFFF;
    color: var(--gd-text);
    padding: 0 14px;
    font-weight: 900;
    transition: transform .18s ease, background .18s ease, border-color .18s ease;
}

.guest-action i:last-child {
    color: color-mix(in srgb, var(--gd-accent) 56%, #FFFFFF);
}

.guest-action:hover {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--gd-accent) 8%, #FFFFFF);
    border-color: color-mix(in srgb, var(--gd-accent) 36%, var(--gd-line));
}

.guest-action.primary {
    border-color: transparent;
    background: linear-gradient(135deg, var(--gd-primary), color-mix(in srgb, var(--gd-primary) 78%, #000000));
    color: #FFFFFF;
}

.guest-action.primary i:last-child {
    color: rgba(255,255,255,.7);
}

.guest-metrics-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.guest-metric {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--gd-line);
    border-radius: 18px;
    background: #FFFFFF;
    padding: 16px;
    box-shadow: 0 14px 34px -30px rgba(15,23,42,.46);
}

.guest-metric::after {
    content: "";
    position: absolute;
    inset: auto 16px 0;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: var(--metric-color, var(--gd-accent));
    opacity: .78;
}

.guest-metric span,
.guest-section-label,
.guest-info-label,
.guest-system-list span {
    display: block;
    color: var(--gd-muted);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.guest-metric strong {
    display: block;
    margin-top: 8px;
    color: var(--gd-heading);
    font-size: clamp(1.45rem, 2.6vw, 2.25rem);
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.guest-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 350px);
    gap: 16px;
    align-items: start;
}

.guest-main-stack,
.guest-side-stack {
    display: grid;
    gap: 16px;
}

.guest-side-stack {
    position: sticky;
    top: 18px;
}

.guest-panel {
    border: 1px solid var(--gd-line);
    border-radius: 22px;
    background: var(--gd-surface);
    box-shadow: 0 18px 48px -38px rgba(15,23,42,.45);
    overflow: hidden;
}

.guest-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--gd-line-soft);
    background: linear-gradient(90deg, color-mix(in srgb, var(--gd-accent) 7%, #FFFFFF), #FFFFFF);
}

.guest-panel-head h2,
.guest-side-card h2 {
    margin: 0;
    color: var(--gd-heading);
    font-size: 1.04rem;
    font-weight: 950;
}

.guest-panel-head p {
    margin: 3px 0 0;
    color: var(--gd-muted);
    font-size: .82rem;
    font-weight: 700;
}

.guest-panel-body {
    padding: 18px;
}

.guest-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.guest-info-card {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    border: 1px solid var(--gd-line-soft);
    border-radius: 16px;
    background: #FFFFFF;
    padding: 14px;
}

.guest-info-card i {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: color-mix(in srgb, var(--gd-accent) 12%, #FFFFFF);
    color: var(--gd-accent-readable);
}

.guest-info-card strong {
    display: block;
    margin-top: 4px;
    color: var(--gd-text);
    font-size: .96rem;
    font-weight: 900;
    overflow-wrap: anywhere;
}

.guest-note {
    margin-top: 12px;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 24%, transparent);
    border-radius: 16px;
    background: color-mix(in srgb, var(--gd-accent) 8%, #FFFFFF);
    padding: 14px;
}

.guest-note strong {
    display: block;
    color: var(--gd-heading);
    margin-bottom: 6px;
}

.guest-note p {
    margin: 0;
    color: var(--gd-muted);
    line-height: 1.6;
}

.guest-docs {
    margin-top: 14px;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 19%, var(--gd-line-soft));
    border-radius: 18px;
    background: linear-gradient(135deg, #FFFFFF, color-mix(in srgb, var(--gd-accent) 5%, #FFFFFF));
    padding: 14px;
}

.guest-docs-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.guest-docs-title {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--gd-heading);
    font-size: .9rem;
    font-weight: 950;
}

.guest-docs-title i {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: var(--gd-accent-readable);
    background: color-mix(in srgb, var(--gd-accent) 12%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--gd-accent) 20%, transparent);
}

.guest-docs-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.guest-doc-action {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border: 1px solid var(--gd-line);
    border-radius: 12px;
    background: #FFFFFF;
    color: var(--gd-text-soft);
    padding: 0 11px;
    font-size: .76rem;
    font-weight: 900;
    text-decoration: none;
}

.guest-doc-action:hover {
    border-color: color-mix(in srgb, var(--gd-accent) 34%, var(--gd-line));
    background: color-mix(in srgb, var(--gd-accent) 8%, #FFFFFF);
}

.guest-doc-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: stretch;
}

.guest-doc-feature {
    width: min(100%, 460px);
    min-height: 142px;
    display: grid;
    grid-template-columns: minmax(118px, 150px) minmax(0, 1fr);
    gap: 12px;
    padding: 10px;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 18%, transparent);
    border-radius: 16px;
    background: #FFFFFF;
    color: inherit;
    text-decoration: none;
    overflow: hidden;
    position: relative;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}

.guest-doc-feature:hover {
    border-color: color-mix(in srgb, var(--gd-accent) 42%, transparent);
    box-shadow: 0 18px 36px -30px rgba(15, 23, 42, .48);
    transform: translateY(-1px);
}

.guest-doc-feature.is-revealable {
    cursor: default;
}

.guest-doc-thumb {
    position: relative;
    min-height: 116px;
    display: grid;
    align-content: space-between;
    gap: 12px;
    padding: 14px;
    border: 0;
    border-radius: 13px;
    overflow: hidden;
    color: #FFFFFF;
    background:
        linear-gradient(135deg, rgba(255,255,255,.14), transparent 40%),
        linear-gradient(145deg, var(--gd-primary), color-mix(in srgb, var(--gd-primary) 72%, #050812));
    font-family: inherit;
    text-align: left;
}

.guest-doc-thumb::after {
    content: "";
    position: absolute;
    right: -28px;
    bottom: -36px;
    width: 100px;
    height: 100px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--gd-accent) 36%, transparent);
}

.guest-doc-reveal {
    width: 100%;
    margin: 0;
    padding: 0;
    border: 0;
    background: none;
    font: inherit;
    text-align: left;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    text-decoration: none;
}

.guest-doc-reveal:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--gd-accent) 42%, transparent);
    outline-offset: 3px;
}

.guest-doc-brand,
.guest-doc-seal {
    position: relative;
    z-index: 1;
}

.guest-doc-brand {
    font-size: .68rem;
    font-weight: 950;
    letter-spacing: .1em;
}

.guest-doc-seal {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    color: #FFFFFF;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.2);
}

.guest-doc-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 13px;
    opacity: 0;
    transition: opacity .22s ease;
    z-index: 2;
}

.guest-doc-img {
    -webkit-user-drag: none;
    -webkit-touch-callout: none;
    user-select: none;
    pointer-events: none;
}

.guest-doc-feature.is-revealed .guest-doc-img,
.guest-doc-reveal:hover .guest-doc-img,
.guest-doc-reveal:focus-visible .guest-doc-img {
    opacity: 1;
}

.guest-doc-eye {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 3;
    width: 34px;
    height: 34px;
    display: inline-grid;
    place-items: center;
    border-radius: 999px;
    background: rgba(18,28,42,.68);
    color: #FFFFFF;
    font-size: .82rem;
    backdrop-filter: blur(6px);
    transition: transform .18s ease, background .18s ease;
}

.guest-doc-reveal:hover .guest-doc-eye,
.guest-doc-reveal:focus-visible .guest-doc-eye,
.guest-doc-feature.is-revealed .guest-doc-eye {
    transform: translateY(-1px);
    background: var(--gd-accent-readable);
}

.guest-doc-copy {
    min-width: 0;
    display: grid;
    align-content: center;
    gap: 7px;
    color: var(--gd-text);
}

.guest-doc-type {
    width: fit-content;
    min-height: 25px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 10px;
    border-radius: 999px;
    background: #E8F7EF;
    color: var(--gd-success);
    font-size: .68rem;
    font-weight: 950;
}

.guest-doc-type.is-pdf { background: #FFF0EF; color: var(--gd-danger); }
.guest-doc-type.is-file { background: #F1F5F9; color: #475569; }

.guest-doc-copy b {
    display: block;
    color: var(--gd-heading);
    font-size: .96rem;
    line-height: 1.2;
    font-weight: 950;
    overflow-wrap: anywhere;
}

.guest-doc-copy small {
    display: block;
    color: var(--gd-muted);
    font-size: .72rem;
    line-height: 1.35;
    font-weight: 760;
}

.guest-doc-open {
    width: max-content;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--gd-accent-readable);
    font-size: .72rem;
    font-weight: 950;
    text-decoration: none;
}

.guest-doc-open:hover {
    text-decoration: underline;
}

.guest-doc-list {
    flex: 1 1 260px;
    min-width: 220px;
    display: grid;
    gap: 8px;
    align-content: start;
}

.guest-doc-chip {
    min-height: 54px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--gd-line-soft);
    border-radius: 13px;
    background: rgba(255,255,255,.82);
    color: var(--gd-text);
    padding: 10px 12px;
    text-decoration: none;
}

.guest-doc-chip:hover {
    border-color: color-mix(in srgb, var(--gd-accent) 34%, transparent);
    box-shadow: 0 14px 28px -25px rgba(15,23,42,.46);
}

.guest-doc-chip-icon {
    width: 32px;
    height: 32px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 10px;
    color: var(--gd-accent-readable);
    background: color-mix(in srgb, var(--gd-accent) 10%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--gd-accent) 16%, transparent);
}

.guest-doc-chip-title,
.guest-doc-chip-meta {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.guest-doc-chip-title {
    color: var(--gd-heading);
    font-size: .78rem;
    font-weight: 950;
}

.guest-doc-chip-meta {
    margin-top: 2px;
    color: var(--gd-muted);
    font-size: .67rem;
    font-weight: 760;
}

.guest-doc-empty {
    min-height: 92px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px dashed color-mix(in srgb, var(--gd-accent) 24%, var(--gd-line));
    border-radius: 14px;
    background: rgba(255,255,255,.66);
    color: var(--gd-muted);
    padding: 14px;
    font-weight: 800;
}

.guest-doc-empty i {
    color: var(--gd-accent-readable);
    font-size: 1.35rem;
}

.guest-panel-action {
    min-height: 44px;
    border: 0;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: var(--gd-primary);
    color: #FFFFFF;
    padding: 0 18px;
    font-weight: 900;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease;
}

.guest-panel-action i {
    flex: 0 0 auto;
    width: auto;
    height: auto;
    margin: 0;
    display: inline-flex;
    place-items: initial;
    border-radius: 0;
    background: transparent;
    color: currentColor;
    font-size: .96rem;
}

.guest-panel-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px -18px rgba(15,23,42,.58);
}

.guest-readonly-badge {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid color-mix(in srgb, var(--gd-primary) 18%, var(--gd-line));
    border-radius: 999px;
    background: #FFFFFF;
    color: var(--gd-text-soft);
    padding: 0 12px;
    font-size: .76rem;
    font-weight: 950;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.guest-operational-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.guest-operational-card {
    border: 1px solid var(--gd-line-soft);
    border-radius: 16px;
    background: #FFFFFF;
    padding: 15px;
}

.guest-operational-card span {
    display: block;
    color: var(--gd-muted);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.guest-operational-card strong {
    display: block;
    margin-top: 8px;
    color: var(--gd-heading);
    font-size: clamp(1.12rem, 2vw, 1.55rem);
    line-height: 1.08;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.guest-operational-card small {
    display: block;
    margin-top: 7px;
    color: var(--gd-muted);
    font-size: .8rem;
    font-weight: 750;
    line-height: 1.45;
}

.guest-alert-list {
    display: grid;
    gap: 9px;
    margin-top: 14px;
}

.guest-alert-item {
    display: grid;
    grid-template-columns: 36px minmax(0, 1fr);
    gap: 10px;
    align-items: start;
    border: 1px solid color-mix(in srgb, var(--alert-color, var(--gd-accent)) 18%, var(--gd-line-soft));
    border-radius: 14px;
    background: color-mix(in srgb, var(--alert-color, var(--gd-accent)) 7%, #FFFFFF);
    padding: 11px;
}

.guest-alert-item i {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: #FFFFFF;
    color: var(--alert-color, var(--gd-accent-readable));
}

.guest-alert-item strong {
    display: block;
    color: var(--gd-heading);
    font-weight: 950;
}

.guest-alert-item p {
    margin: 3px 0 0;
    color: var(--gd-muted);
    font-size: .83rem;
    font-weight: 750;
    line-height: 1.45;
}

.guest-vehicle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
    gap: 12px;
}

.guest-vehicle-card {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(220px, .8fr);
    gap: 14px;
    align-items: stretch;
    border: 1px solid var(--gd-line-soft);
    border-radius: 14px;
    background: #FFFFFF;
    padding: 16px;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}

.guest-vehicle-card:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--gd-accent) 36%, var(--gd-line));
    box-shadow: 0 18px 34px -30px rgba(15,23,42,.48);
}

.guest-vehicle-top {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.guest-vehicle-title {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.guest-vehicle-icon {
    width: 26px;
    height: 26px;
    flex: none;
    display: grid;
    place-items: center;
    border-radius: 0;
    background: transparent;
    color: var(--gd-accent-readable);
}

.guest-vehicle-title strong {
    display: block;
    color: var(--gd-text);
    font-weight: 700;
    line-height: 1.15;
    overflow-wrap: anywhere;
}

.guest-vehicle-title span {
    width: fit-content;
    display: inline-flex;
    margin-top: 6px;
    color: var(--gd-muted);
    padding: 0;
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: .02em;
    font-variant-numeric: tabular-nums;
}

.guest-mini-actions {
    display: flex;
    gap: 7px;
    flex: none;
}

.guest-icon-btn {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border: 1px solid var(--gd-line-soft);
    border-radius: 11px;
    background: #FFFFFF;
    color: var(--gd-text-soft);
    cursor: pointer;
    transition: transform .18s ease, background .18s ease;
}

.guest-icon-btn:hover {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--gd-accent) 7%, #FFFFFF);
}

.guest-icon-btn.danger {
    color: var(--gd-danger);
}

.guest-icon-btn.danger.is-confirming {
    background: color-mix(in srgb, var(--gd-danger) 9%, #FFFFFF);
    border-color: color-mix(in srgb, var(--gd-danger) 24%, var(--gd-line-soft));
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--gd-danger) 12%, transparent);
}

.guest-vehicle-details {
    display: grid;
    gap: 0;
    margin-top: 0;
    padding: 0;
    border: none;
    border-radius: 0;
    background: transparent;
    align-content: start;
}

.guest-detail-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid var(--gd-line-soft);
    color: var(--gd-muted);
    font-size: .82rem;
    font-weight: 500;
}

.guest-detail-row:last-child {
    border-bottom: none;
}

.guest-detail-row strong {
    color: var(--gd-text);
    font-weight: 600;
    text-align: right;
    overflow-wrap: anywhere;
}

.guest-parking-badge,
.guest-status-badge {
    width: fit-content;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 8px;
    padding: 4px 8px;
    font-size: .76rem;
    font-weight: 600;
}

.guest-parking-badge {
    background: color-mix(in srgb, var(--gd-accent) 8%, #FFFFFF);
    color: var(--gd-text-soft);
}

.guest-empty {
    border: 1px dashed color-mix(in srgb, var(--gd-primary) 18%, transparent);
    border-radius: 18px;
    background: rgba(255,255,255,.62);
    padding: 28px 18px;
    text-align: center;
    color: var(--gd-muted);
}

.guest-empty > i {
    width: 60px;
    height: 60px;
    display: inline-grid;
    place-items: center;
    border-radius: 18px;
    background: color-mix(in srgb, var(--gd-accent) 9%, #FFFFFF);
    color: var(--gd-accent-readable);
    font-size: 1.45rem;
    margin-bottom: 12px;
}

.guest-empty strong {
    display: block;
    color: var(--gd-heading);
    font-size: 1.05rem;
    margin-bottom: 5px;
}

.guest-history-empty {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr) auto;
    gap: 16px;
    align-items: center;
    padding: 22px;
    text-align: left;
}

.guest-history-empty i {
    grid-column: 1;
    grid-row: 1 / span 2;
    margin: 0;
}

.guest-history-empty strong {
    grid-column: 2;
    grid-row: 1;
    margin-bottom: 4px;
}

.guest-history-empty p {
    grid-column: 2;
    grid-row: 2;
    margin: 0;
    max-width: 54ch;
}

.guest-history-empty .guest-panel-action {
    grid-column: 3;
    grid-row: 1 / span 2;
    justify-self: center;
    min-width: min(100%, 260px);
    width: max-content;
    white-space: nowrap;
}

.guest-reservation-list {
    display: grid;
    gap: 10px;
}

.guest-reservation-card {
    display: grid;
    grid-template-columns: minmax(230px, 1.3fr) repeat(3, minmax(120px, .72fr)) auto;
    gap: 12px;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--status-color) 22%, var(--gd-line));
    border-radius: 17px;
    background: #FFFFFF;
    box-shadow: inset 4px 0 0 var(--status-color);
    padding: 14px;
}

.guest-reservation-main strong {
    display: block;
    color: var(--gd-text);
    font-weight: 950;
    overflow-wrap: anywhere;
}

.guest-reservation-main span,
.guest-reservation-meta span {
    display: block;
    margin-top: 4px;
    color: var(--gd-muted);
    font-size: .8rem;
    font-weight: 750;
}

.guest-reservation-meta strong {
    display: block;
    color: var(--gd-heading);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.guest-status-badge {
    background: var(--status-soft);
    color: var(--status-color);
}

.guest-reservation-link {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 12px;
    background: var(--gd-primary);
    color: #FFFFFF;
    padding: 0 11px;
    font-size: .82rem;
    font-weight: 900;
}

.guest-side-card {
    border: 1px solid var(--gd-line);
    border-radius: 20px;
    background: var(--gd-surface);
    padding: 18px;
    box-shadow: 0 18px 48px -38px rgba(15,23,42,.45);
}

.guest-score-card {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--gd-accent) 28%, transparent), transparent 14rem),
        linear-gradient(145deg, var(--gd-primary), var(--gd-secondary));
    color: #FFFFFF;
}

.guest-score-card h2 {
    color: #FFFFFF;
}

.guest-score-number {
    margin: 15px 0 5px;
    font-size: clamp(2.1rem, 4vw, 3.2rem);
    font-weight: 950;
    line-height: 1;
}

.guest-score-card p {
    margin: 0;
    color: rgba(255,255,255,.7);
    font-weight: 700;
}

.guest-score-list {
    display: grid;
    gap: 10px;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,.16);
}

.guest-score-list div,
.guest-system-list li {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
}

.guest-score-list span {
    color: rgba(255,255,255,.64);
    font-size: .82rem;
    font-weight: 750;
}

.guest-score-list strong {
    color: #FFFFFF;
    font-weight: 950;
    text-align: right;
}

.guest-quick-actions {
    display: grid;
    gap: 9px;
    margin-top: 14px;
}

.guest-side-action {
    min-height: 42px;
    border: 1px solid var(--gd-line);
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #FFFFFF;
    color: var(--gd-text);
    padding: 0 12px;
    font-weight: 900;
}

.guest-side-action:hover {
    background: color-mix(in srgb, var(--gd-accent) 7%, #FFFFFF);
}

.guest-system-list {
    display: grid;
    gap: 10px;
    margin: 14px 0 0;
    padding: 0;
    list-style: none;
}

.guest-system-list strong {
    color: var(--gd-text);
    font-weight: 900;
    text-align: right;
}

.guest-modal {
    --gd-primary: color-mix(in srgb, var(--brand-primary, #1B2746) 34%, #243044);
    --gd-secondary: #121A2A;
    --gd-accent: color-mix(in srgb, var(--brand-accent, #BD9441) 62%, #B98B3E);
    --gd-line: rgba(67, 78, 94, .18);
    --gd-line-soft: rgba(67, 78, 94, .1);
    --gd-text: #182032;
    --gd-muted: #6B7586;
    position: fixed;
    inset: 0;
    z-index: 13000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background:
        radial-gradient(circle at 50% 15%, color-mix(in srgb, var(--gd-accent) 20%, transparent), transparent 24rem),
        rgba(10, 15, 25, .76);
    -webkit-backdrop-filter: blur(12px) saturate(1.05);
    backdrop-filter: blur(12px) saturate(1.05);
}

.guest-modal.hidden {
    display: none !important;
}

.guest-modal-card {
    width: min(560px, 100%);
    max-height: 92dvh;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 34%, rgba(255,255,255,.16));
    border-radius: 24px;
    background:
        linear-gradient(180deg, #FFF8ED 0%, #F5ECDD 100%);
    box-shadow:
        0 34px 90px -36px rgba(0,0,0,.9),
        inset 0 1px 0 rgba(255,255,255,.86);
}

.guest-modal-head {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px;
    color: #FFFFFF;
    background:
        radial-gradient(circle at 86% 0%, color-mix(in srgb, var(--gd-accent) 36%, transparent), transparent 12rem),
        linear-gradient(135deg, #182238 0%, var(--gd-primary) 48%, #111827 100%);
}

.guest-modal-head::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
    bottom: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--gd-accent) 72%, #FFFFFF), transparent);
}

.guest-modal-head h3 {
    position: relative;
    z-index: 1;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.08rem;
    font-weight: 950;
}

.guest-modal-head h3 i {
    width: 38px;
    height: 38px;
    display: inline-grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 13px;
    background: rgba(255,255,255,.12);
    color: color-mix(in srgb, var(--gd-accent) 72%, #FFFFFF);
}

.guest-modal-close {
    position: relative;
    z-index: 1;
    width: 36px;
    height: 36px;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 12px;
    background: rgba(255,255,255,.13);
    color: #FFFFFF;
    cursor: pointer;
    transition: transform .18s ease, background .18s ease;
}

.guest-modal-close:hover {
    transform: translateY(-1px);
    background: rgba(255,255,255,.22);
}

.guest-vehicle-form {
    display: grid;
    gap: 14px;
    padding: 18px;
    max-height: calc(92dvh - 74px);
    overflow-y: auto;
    background:
        radial-gradient(circle at 9% 8%, rgba(189, 148, 65, .14), transparent 13rem),
        linear-gradient(180deg, #FFF8ED, #F5ECDD);
}

.guest-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.guest-form-field,
.guest-radio-group {
    border: 1px solid var(--gd-line-soft);
    border-radius: 16px;
    background: rgba(255,255,255,.54);
    padding: 12px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.64);
}

.guest-form-field label,
.guest-radio-group > label {
    display: block;
    margin-bottom: 7px;
    color: #263246;
    font-size: .74rem;
    font-weight: 950;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.guest-form-field input,
.guest-form-field select,
.guest-form-field textarea {
    width: 100%;
    min-height: 43px;
    border: 1px solid rgba(44, 55, 75, .24);
    border-radius: 12px;
    background: rgba(255,255,255,.96);
    color: var(--gd-text);
    outline: none;
    padding: 9px 12px;
    font-weight: 750;
    transition: border-color .18s ease, box-shadow .18s ease;
    box-shadow: 0 1px 0 rgba(255,255,255,.75);
}

.guest-form-field textarea {
    min-height: 86px;
    resize: vertical;
}

.guest-form-field.is-wide {
    grid-column: 1 / -1;
}

.guest-form-field input:focus,
.guest-form-field select:focus,
.guest-form-field textarea:focus {
    border-color: color-mix(in srgb, var(--gd-accent) 68%, var(--gd-primary));
    box-shadow:
        0 0 0 4px color-mix(in srgb, var(--gd-accent) 18%, transparent),
        0 10px 22px rgba(28, 35, 49, .08);
}

.guest-form-alert {
    display: none;
    align-items: flex-start;
    gap: 9px;
    padding: 11px 12px;
    border-radius: 13px;
    border: 1px solid color-mix(in srgb, var(--gd-danger) 22%, var(--gd-line-soft));
    background: color-mix(in srgb, var(--gd-danger) 7%, #FFFFFF);
    color: color-mix(in srgb, var(--gd-danger) 74%, #1C2331);
    font-size: .84rem;
    font-weight: 850;
    line-height: 1.35;
}

.guest-form-alert.is-visible {
    display: flex;
}

.guest-form-alert.is-success {
    border-color: color-mix(in srgb, var(--gd-success) 24%, var(--gd-line-soft));
    background: color-mix(in srgb, var(--gd-success) 8%, #FFFFFF);
    color: color-mix(in srgb, var(--gd-success) 78%, #1C2331);
}

.guest-form-alert i {
    margin-top: 2px;
}

.guest-radio-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.guest-radio-card {
    cursor: pointer;
}

.guest-radio-card input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.guest-radio-card span {
    min-height: 58px;
    display: grid;
    place-items: center;
    gap: 5px;
    border: 1px solid rgba(44, 55, 75, .2);
    border-radius: 14px;
    background: rgba(255,255,255,.8);
    color: #263246;
    font-size: .82rem;
    font-weight: 900;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.guest-radio-card span:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--gd-accent) 42%, rgba(44,55,75,.2));
    background: rgba(255,255,255,.95);
}

.guest-radio-card input:checked + span {
    border-color: color-mix(in srgb, var(--gd-accent) 72%, var(--gd-primary));
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--gd-accent) 18%, #FFFFFF), rgba(255,255,255,.92));
    box-shadow: 0 12px 24px rgba(55, 43, 25, .12);
}

.guest-radio-card input:checked + span i {
    color: color-mix(in srgb, var(--gd-accent) 74%, var(--gd-primary));
}

.guest-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin: 2px -18px -18px;
    padding: 14px 18px 18px;
    border-top: 1px solid rgba(44, 55, 75, .12);
    background: rgba(255,255,255,.46);
}

.guest-cancel,
.guest-save {
    min-height: 42px;
    border-radius: 12px;
    padding: 0 15px;
    font-weight: 900;
    cursor: pointer;
}

.guest-cancel {
    border: 1px solid rgba(44, 55, 75, .18);
    background: rgba(255,255,255,.78);
    color: #263246;
}

.guest-save {
    border: 0;
    background: linear-gradient(135deg, #263246, var(--gd-primary));
    color: #FFFFFF;
    box-shadow: 0 14px 26px rgba(28, 35, 49, .22);
}

.guest-cancel:hover,
.guest-save:hover {
    transform: translateY(-1px);
}

@media (max-width: 1180px) {
    .guest-layout {
        grid-template-columns: 1fr;
    }

    .guest-operational-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .guest-side-stack {
        position: static;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .guest-doc-feature,
    .guest-doc-list {
        width: 100%;
        min-width: 0;
    }
}

@media (max-width: 900px) {
    .guest-hero,
    .guest-identity-card {
        grid-template-columns: 1fr;
    }

    .guest-action-rail,
    .guest-side-stack,
    .guest-operational-grid,
    .guest-metrics-strip,
    .guest-info-grid {
        grid-template-columns: 1fr;
    }

    .guest-reservation-card {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .guest-vehicle-card {
        grid-template-columns: 1fr;
    }

    .guest-vehicle-details {
        margin-top: 0;
    }

    .guest-doc-feature {
        grid-template-columns: 132px minmax(0, 1fr);
    }
}

@media (max-width: 620px) {
    .guest-detail-shell {
        width: min(100% - 18px, 1500px);
        padding-top: 14px;
    }

    .guest-identity-card,
    .guest-action-rail,
    .guest-panel,
    .guest-side-card {
        border-radius: 18px;
    }

    .guest-avatar {
        width: 76px;
        border-radius: 18px;
    }

    .guest-identity-card h1 {
        font-size: clamp(2.1rem, 13vw, 3.1rem);
    }

    .guest-panel-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .guest-docs-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .guest-docs-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .guest-doc-feature {
        grid-template-columns: 1fr;
    }

    .guest-doc-thumb {
        min-height: 124px;
    }

    .guest-doc-seal {
        display: none;
    }

    .guest-panel-action,
    .guest-action,
    .guest-side-action {
        width: 100%;
    }

    .guest-vehicle-top {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
    }

    .guest-vehicle-title {
        align-items: flex-start;
    }

    .guest-history-empty {
        grid-template-columns: 1fr;
        text-align: center;
        justify-items: center;
    }

    .guest-history-empty > i,
    .guest-history-empty strong,
    .guest-history-empty p,
    .guest-history-empty .guest-panel-action {
        grid-column: auto;
        grid-row: auto;
    }

    .guest-history-empty .guest-panel-action {
        width: 100%;
        margin-top: 8px;
    }

    .guest-form-grid,
    .guest-radio-options {
        grid-template-columns: 1fr;
    }

    .guest-modal-actions {
        display: grid;
        grid-template-columns: 1fr;
    }
}
</style>

<div class="guest-detail-view">
    <div class="guest-detail-shell">
        <nav class="guest-breadcrumb" aria-label="Ruta de navegacion">
            <a href="<?= url('huespedes') ?>">Huespedes</a>
            <i class="fas fa-chevron-right"></i>
            <span><?= guest_detail_safe($nombre_huesped) ?></span>
        </nav>

        <header class="guest-hero">
            <section class="guest-identity-card">
                <div class="guest-avatar" aria-hidden="true"><?= guest_detail_safe($inicial_huesped) ?></div>
                <div>
                    <div class="guest-kicker">
                        <i class="fas fa-id-badge"></i>
                        Expediente de huésped
                    </div>
                    <h1><?= guest_detail_safe($nombre_huesped) ?></h1>
                    <p>
                        Perfil operativo para revisar contacto, procedencia, vehículos, historial de reservaciones y acciones rápidas del huésped.
                    </p>
                    <div class="guest-hero-meta">
                        <span class="guest-chip">
                            <i class="fas fa-hashtag"></i>
                            ID <?= (int)($huesped['id'] ?? 0) ?>
                        </span>
                        <span class="guest-chip">
                            <i class="fas fa-location-dot"></i>
                            <?= guest_detail_safe($procedencia_label) ?>
                        </span>
                        <span class="guest-chip">
                            <i class="fas fa-address-book"></i>
                            <?= guest_detail_safe($contacto_label) ?>
                        </span>
                        <?php if ($es_cliente_frecuente): ?>
                            <span class="guest-chip">
                                <i class="fas fa-star"></i>
                                Huésped frecuente
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <aside class="guest-action-rail" aria-label="Acciones principales">
                <a href="<?= url('reservaciones/crear?huesped_id=' . ($huesped['id'] ?? 0)) ?>" class="guest-action primary">
                    <span><i class="fas fa-calendar-plus"></i> Nueva reservación</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="<?= url('huespedes/' . ($huesped['id'] ?? 0) . '/edit') ?>" class="guest-action">
                    <span><i class="fas fa-pen"></i> Editar información</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <?php $back_arrow_href = back_url('huespedes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('huespedes') ?>" class="guest-action ms-back-legacy">
                    <span><i class="fas fa-arrow-left"></i> Volver al directorio</span>
                    <i class="fas fa-list"></i>
                </a>
            </aside>
        </header>

        <section class="guest-metrics-strip" aria-label="Resumen del huésped">
            <article class="guest-metric" style="--metric-color: var(--gd-primary);">
                <span>Visitas válidas</span>
                <strong><?= number_format($total_reservaciones) ?></strong>
            </article>
            <article class="guest-metric" style="--metric-color: var(--gd-accent);">
                <span>Total gastado</span>
                <strong><?= format_money($total_gastado) ?></strong>
            </article>
            <article class="guest-metric" style="--metric-color: var(--gd-success);">
                <span>Promedio</span>
                <strong><?= format_money($gasto_promedio) ?></strong>
            </article>
            <article class="guest-metric" style="--metric-color: #64748B;">
                <span>Vehículos</span>
                <strong><?= number_format($vehiculos_count) ?></strong>
            </article>
        </section>

        <section class="guest-panel guest-readonly-profile" aria-label="Perfil operativo del huesped">
            <div class="guest-panel-head">
                <div>
                    <h2>Perfil operativo</h2>
                    <p>Cu&aacute;ntas veces ha venido, cu&aacute;nto debe y qu&eacute; documentos tiene.</p>
                </div>
                <span class="guest-readonly-badge">
                    <i class="fas fa-shield-alt"></i>
                    Solo lectura
                </span>
            </div>
            <div class="guest-panel-body">
                <div class="guest-operational-grid">
                    <article class="guest-operational-card">
                        <span>Clasificacion</span>
                        <strong><?= guest_detail_safe($perfilClasificacion, 'Sin historial') ?></strong>
                        <small><?= number_format($perfilScore) ?> / 100 puntos operativos</small>
                    </article>
                    <article class="guest-operational-card">
                        <span>Proxima estancia</span>
                        <strong><?= $perfilProximaVisita ? format_date($perfilProximaVisita) : 'No programada' ?></strong>
                        <small><?= number_format((int)($perfilReservaciones['proximas'] ?? 0)) ?> reservaciones futuras</small>
                    </article>
                    <article class="guest-operational-card">
                        <span>Saldo por cobrar</span>
                        <strong><?= format_money($perfilCxcSaldo) ?></strong>
                        <small><?= number_format($perfilCxcCuentas) ?> cuentas pendientes</small>
                    </article>
                    <article class="guest-operational-card">
                        <span>Expediente</span>
                        <strong><?= number_format($perfilDocumentosActivos) ?></strong>
                        <small><?= number_format((int)($perfilDocumentos['total'] ?? 0)) ?> documentos vinculados</small>
                    </article>
                </div>

                <?php if (!empty($perfilAlertas)): ?>
                    <div class="guest-alert-list" aria-label="Alertas operativas del perfil">
                        <?php foreach ($perfilAlertas as $alerta): ?>
                            <?php
                                $alertNivel = (string)($alerta['nivel'] ?? 'info');
                                $alertColor = $perfilAlertColors[$alertNivel] ?? '#64748B';
                                $alertIcon = $perfilAlertIcons[$alertNivel] ?? 'fas fa-circle-info';
                            ?>
                            <article class="guest-alert-item" style="--alert-color: <?= guest_detail_safe($alertColor, '#64748B') ?>;">
                                <i class="<?= guest_detail_safe($alertIcon, 'fas fa-circle-info') ?>"></i>
                                <div>
                                    <strong><?= guest_detail_safe($alerta['titulo'] ?? 'Aviso operativo') ?></strong>
                                    <p><?= guest_detail_safe($alerta['mensaje'] ?? 'Dato disponible para revision.') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <div class="guest-layout">
            <main class="guest-main-stack">
                <section class="guest-panel">
                    <div class="guest-panel-head">
                        <div>
                            <h2>Datos del huésped</h2>
                            <p>Contacto, procedencia y datos de registro.</p>
                        </div>
                    </div>
                    <div class="guest-panel-body">
                        <div class="guest-info-grid">
                            <article class="guest-info-card">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <span class="guest-info-label">Teléfono</span>
                                    <strong><?= guest_detail_safe($telefono, 'No registrado') ?></strong>
                                </div>
                            </article>
                            <article class="guest-info-card">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <span class="guest-info-label">Email</span>
                                    <strong><?= guest_detail_safe($email, 'No registrado') ?></strong>
                                </div>
                            </article>
                            <article class="guest-info-card">
                                <i class="fas fa-map-location-dot"></i>
                                <div>
                                    <span class="guest-info-label">Procedencia</span>
                                    <strong><?= guest_detail_safe($procedencia_label) ?></strong>
                                </div>
                            </article>
                            <article class="guest-info-card">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <span class="guest-info-label">Última visita</span>
                                    <strong><?= guest_detail_safe($ultima_visita_label) ?></strong>
                                </div>
                            </article>
                            <?php
                            $descTipoVer = (string)($huesped['descuento_tipo'] ?? '');
                            $descValorVer = (float)($huesped['descuento_valor'] ?? 0);
                            $descLabelVer = 'Sin descuento';
                            if ($descValorVer > 0 && $descTipoVer === 'porcentaje') {
                                $descLabelVer = rtrim(rtrim(number_format($descValorVer, 2), '0'), '.') . '%';
                            } elseif ($descValorVer > 0 && $descTipoVer === 'monto') {
                                $descLabelVer = '$' . number_format($descValorVer, 2);
                            }
                            ?>
                            <article class="guest-info-card">
                                <i class="fas fa-tags"></i>
                                <div>
                                    <span class="guest-info-label">Descuento</span>
                                    <strong><?= guest_detail_safe($descLabelVer) ?></strong>
                                </div>
                            </article>
                            <?php if (!empty($huesped['vehiculo_marca']) || !empty($huesped['vehiculo_placas'])): ?>
                                <article class="guest-info-card">
                                    <i class="fas fa-car-side"></i>
                                    <div>
                                        <span class="guest-info-label">Vehículo anterior</span>
                                        <strong>
                                            <?= guest_detail_safe(trim(($huesped['vehiculo_marca'] ?? '') . ' ' . ($huesped['vehiculo_placas'] ?? ''))) ?>
                                        </strong>
                                    </div>
                                </article>
                            <?php endif; ?>
                            <?php foreach ($guestExtraDisplayFields as $fieldKey => $fieldDefinition): ?>
                                <?php
                                $extraValue = is_scalar($guestExtraValues[$fieldKey] ?? null) ? trim((string)$guestExtraValues[$fieldKey]) : '';
                                if ($extraValue === '') {
                                    continue;
                                }
                                ?>
                                <article class="guest-info-card">
                                    <i class="fas <?= guest_detail_safe($fieldDefinition['icon'] ?? 'fa-circle-info') ?>"></i>
                                    <div>
                                        <span class="guest-info-label"><?= guest_detail_safe($fieldDefinition['label'] ?? $fieldKey) ?></span>
                                        <strong><?= guest_detail_safe($extraValue) ?></strong>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($guestDocsVisible): ?>
                        <section class="<?= guest_detail_safe($guestDocPanelClass) ?>" aria-label="Documentos del huesped">
                            <div class="guest-docs-head">
                                <div class="guest-docs-title">
                                    <i class="fas fa-id-card-clip"></i>
                                    <span>Vista documental del huesped</span>
                                </div>
                                <?php if ($guestDocEntityId > 0 && $guestDocsPuedeVincular): ?>
                                    <div class="guest-docs-actions">
                                        <a class="guest-doc-action" href="<?= url('documentos/subir' . $guestDocEntityQuery) ?>">
                                            <i class="fas fa-paperclip"></i>
                                            Vincular
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (empty($guestDocuments)): ?>
                                <div class="guest-doc-empty">
                                    <i class="fas fa-folder-open"></i>
                                    <div>
                                        Sin documento vinculado.
                                        <?php if ($guestDocEntityId > 0 && $guestDocsPuedeVincular): ?>
                                            <br><a class="guest-doc-open" href="<?= url('documentos/subir' . $guestDocEntityQuery) ?>">Agregar identificacion o archivo</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php
                                    $guestPrimaryDocId = (int)($guestPrimaryDoc['id'] ?? 0);
                                    $guestPrimaryTitle = trim((string)(($guestPrimaryDoc['titulo'] ?? '') ?: ($guestPrimaryDoc['nombre_original'] ?? 'Documento del huesped')));
                                    $guestPrimaryMeta = trim(implode(' - ', array_filter([
                                        (string)($guestPrimaryDoc['tipo_nombre'] ?? ''),
                                        $guestDocBytes($guestPrimaryDoc['size_bytes'] ?? 0),
                                    ])));
                                    [$guestPrimaryDocLabel, $guestPrimaryDocClass, $guestPrimaryDocIcon] = $guestDocIcon($guestPrimaryDoc);
                                    $guestPrimaryKind = $guestDocPreviewKind($guestPrimaryDoc);
                                ?>
                                <div class="guest-doc-grid">
                                    <?php if ($guestPrimaryKind === 'image' && $guestPrimaryDocId > 0): ?>
                                        <?php $guestPrimaryPreviewUrl = url('documentos/' . $guestPrimaryDocId . '/descargar') . '?preview=1'; ?>
                                        <div class="guest-doc-feature is-revealable is-revealed" role="group" aria-label="Documento del huesped <?= guest_detail_safe($guestPrimaryTitle, 'documento') ?>">
                                            <button type="button"
                                                    class="guest-doc-thumb guest-doc-reveal"
                                                    onclick="guestAbrirDocLightbox(this)"
                                                    data-doc-src="<?= guest_detail_safe($guestPrimaryPreviewUrl, '') ?>"
                                                    data-doc-title="<?= guest_detail_safe($guestPrimaryTitle, 'Documento del huesped') ?>"
                                                    aria-label="Ver documento del huesped <?= guest_detail_safe($guestPrimaryTitle, 'documento') ?> en pantalla completa"
                                                    aria-haspopup="dialog">
                                                <span class="guest-doc-brand">MEDISOFT</span>
                                                <span class="guest-doc-seal"><i class="fas fa-file-shield"></i></span>
                                                <img class="guest-doc-img"
                                                     src="<?= guest_detail_safe($guestPrimaryPreviewUrl, '') ?>"
                                                     alt="<?= guest_detail_safe($guestPrimaryTitle, 'Documento del huesped') ?>"
                                                     loading="lazy"
                                                     decoding="async"
                                                     draggable="false"
                                                     oncontextmenu="return false;">
                                                <span class="guest-doc-eye"><i class="fas fa-eye" aria-hidden="true"></i></span>
                                            </button>
                                            <span class="guest-doc-copy">
                                                <span class="guest-doc-type <?= guest_detail_safe($guestPrimaryDocClass, 'is-file') ?>">
                                                    <i class="fas <?= guest_detail_safe($guestPrimaryDocIcon, 'fa-file-lines') ?>"></i>
                                                    <?= guest_detail_safe($guestPrimaryDocLabel, 'DOC') ?>
                                                </span>
                                                <b><?= guest_detail_safe($guestPrimaryTitle, 'Documento del huesped') ?></b>
                                                <small><?= guest_detail_safe($guestPrimaryMeta, 'Archivo vinculado') ?></small>
                                                <a class="guest-doc-open" href="<?= url('documentos/' . $guestPrimaryDocId) ?>">Abrir ficha <i class="fas fa-arrow-up-right-from-square"></i></a>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <a class="guest-doc-feature" href="<?= url('documentos/' . $guestPrimaryDocId) ?>" title="Ver <?= guest_detail_safe($guestPrimaryTitle, 'documento') ?>">
                                            <span class="guest-doc-thumb" aria-hidden="true">
                                                <span class="guest-doc-brand">MEDISOFT</span>
                                                <span class="guest-doc-seal"><i class="fas fa-file-shield"></i></span>
                                            </span>
                                            <span class="guest-doc-copy">
                                                <span class="guest-doc-type <?= guest_detail_safe($guestPrimaryDocClass, 'is-file') ?>">
                                                    <i class="fas <?= guest_detail_safe($guestPrimaryDocIcon, 'fa-file-lines') ?>"></i>
                                                    <?= guest_detail_safe($guestPrimaryDocLabel, 'DOC') ?>
                                                </span>
                                                <b><?= guest_detail_safe($guestPrimaryTitle, 'Documento del huesped') ?></b>
                                                <small><?= guest_detail_safe($guestPrimaryMeta, 'Archivo vinculado') ?></small>
                                            </span>
                                        </a>
                                    <?php endif; ?>

                                    <?php $guestSecondaryDocs = array_slice($guestDocuments, 1, 4); ?>
                                    <?php if (!empty($guestSecondaryDocs)): ?>
                                        <div class="guest-doc-list">
                                            <?php foreach ($guestSecondaryDocs as $guestDoc): ?>
                                                <?php
                                                    $guestDocId = (int)($guestDoc['id'] ?? 0);
                                                    if ($guestDocId <= 0) {
                                                        continue;
                                                    }
                                                    $guestDocTitle = trim((string)(($guestDoc['titulo'] ?? '') ?: ($guestDoc['nombre_original'] ?? 'Documento')));
                                                    $guestDocMeta = trim(implode(' - ', array_filter([
                                                        (string)($guestDoc['tipo_nombre'] ?? ''),
                                                        (string)($guestDoc['relacion'] ?? ''),
                                                        $guestDocBytes($guestDoc['size_bytes'] ?? 0),
                                                    ])));
                                                    [, , $guestDocListIcon] = $guestDocIcon($guestDoc);
                                                ?>
                                                <a class="guest-doc-chip" href="<?= url('documentos/' . $guestDocId) ?>">
                                                    <span class="guest-doc-chip-icon"><i class="fas <?= guest_detail_safe($guestDocListIcon, 'fa-file-lines') ?>"></i></span>
                                                    <span>
                                                        <span class="guest-doc-chip-title"><?= guest_detail_safe($guestDocTitle, 'Documento') ?></span>
                                                        <span class="guest-doc-chip-meta"><?= guest_detail_safe($guestDocMeta, 'Archivo vinculado') ?></span>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                        <?php endif; ?>

                        <?php if (!empty($huesped['notas'])): ?>
                            <div class="guest-note">
                                <strong>Notas internas</strong>
                                <p><?= nl2br(guest_detail_safe($huesped['notas'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('vehiculos')): ?>
                <section id="vehiculos" class="guest-panel guest-vehicles-panel">
                    <div class="guest-panel-head">
                        <div>
                            <h2>Vehículos registrados</h2>
                            <p>Control de placas, color y estacionamiento.</p>
                        </div>
                        <button type="button" onclick="abrirModalAgregarVehiculo()" class="guest-panel-action">
                            <i class="fas fa-plus"></i>
                            Agregar vehículo
                        </button>
                    </div>
                    <div class="guest-panel-body">
                        <?php if (!empty($vehiculos)): ?>
                            <div class="guest-vehicle-grid">
                                <?php foreach ($vehiculos as $vehiculo): ?>
                                    <?php
                                        $ubicacion = $estacionamientos[$vehiculo['estacionamiento'] ?? ''] ?? 'No especificado';
                                        $icono = ($vehiculo['estacionamiento'] ?? '') === 'coches' ? 'fa-car' : 'fa-square-parking';
                                        $vehiculo_nombre = trim(($vehiculo['marca'] ?? '') . ' ' . ($vehiculo['modelo'] ?? ''));
                                        $vehiculo_desc = trim(($vehiculo['marca'] ?? '') . ' - ' . ($vehiculo['placas'] ?? ''));
                                        $vehiculoExtras = function_exists('hotel_guest_decode_extra_json')
                                            ? hotel_guest_decode_extra_json($vehiculo['datos_extra_json'] ?? null)
                                            : [];
                                    ?>
                                    <article class="guest-vehicle-card">
                                        <div class="guest-vehicle-top">
                                            <div class="guest-vehicle-title">
                                                <span class="guest-vehicle-icon">
                                                    <i class="fas <?= guest_detail_safe($icono) ?>"></i>
                                                </span>
                                                <div>
                                                    <strong><?= guest_detail_safe($vehiculo_nombre, 'Vehículo') ?></strong>
                                                    <span><?= guest_detail_safe($vehiculo['placas'] ?? '', 'Sin placas') ?></span>
                                                </div>
                                            </div>
                                            <div class="guest-mini-actions">
                                                <button type="button"
                                                        onclick='editarVehiculo(<?= guest_detail_json_attr($vehiculo) ?>)'
                                                        class="guest-icon-btn"
                                                        title="Editar vehículo">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button"
                                                        onclick='confirmarEliminarVehiculo(this, <?= (int)($vehiculo['id'] ?? 0) ?>, <?= guest_detail_json_attr($vehiculo_desc) ?>)'
                                                        class="guest-icon-btn danger"
                                                        title="Eliminar vehículo">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="guest-vehicle-details">
                                            <div class="guest-detail-row">
                                                <span>Color</span>
                                                <strong><?= guest_detail_safe($vehiculo['color'] ?? '', 'No especificado') ?></strong>
                                            </div>
                                            <div class="guest-detail-row">
                                                <span>Ubicación</span>
                                                <strong class="guest-parking-badge">
                                                    <i class="fas <?= guest_detail_safe($icono) ?>"></i>
                                                    <?= guest_detail_safe($ubicacion) ?>
                                                </strong>
                                            </div>
                                            <?php foreach ($guestVehicleVisibleFields as $vehicleFieldKey => $vehicleFieldDefinition): ?>
                                                <?php
                                                if (($vehicleFieldDefinition['storage'] ?? 'column') !== 'extra') {
                                                    continue;
                                                }
                                                $vehicleExtraValue = is_scalar($vehiculoExtras[$vehicleFieldKey] ?? null) ? trim((string)$vehiculoExtras[$vehicleFieldKey]) : '';
                                                if ($vehicleExtraValue === '') {
                                                    continue;
                                                }
                                                ?>
                                                <div class="guest-detail-row">
                                                    <span><?= guest_detail_safe($vehicleFieldDefinition['label'] ?? $vehicleFieldKey) ?></span>
                                                    <strong><?= guest_detail_safe($vehicleExtraValue) ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="guest-empty">
                                <i class="fas fa-car-side"></i>
                                <strong>Sin vehículos registrados</strong>
                                <p>Agrega un vehículo para llevar control del estacionamiento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <section class="guest-panel guest-history-panel">
                    <div class="guest-panel-head">
                        <div>
                            <h2>Historial de reservaciones</h2>
                            <p><?= number_format($reservaciones_count) ?> registros asociados a este huésped.</p>
                        </div>
                    </div>
                    <div class="guest-panel-body">
                        <?php if (!empty($reservaciones)): ?>
                            <div class="guest-reservation-list">
                                <?php foreach ($reservaciones as $reservacion): ?>
                                    <?php
                                        $estado = $reservacion['estado'] ?? 'desconocido';
                                        $estadoReserva = guest_detail_status($estado);
                                        $habitaciones_total = (int)($reservacion['total_habitaciones'] ?? 0);
                                    ?>
                                    <article class="guest-reservation-card"
                                             style="--status-color: <?= guest_detail_safe($estadoReserva['color']) ?>; --status-soft: <?= guest_detail_safe($estadoReserva['soft']) ?>;">
                                        <div class="guest-reservation-main">
                                            <strong><?= guest_detail_safe($reservacion['habitaciones_numeros'] ?? '', 'Sin habitaciones') ?></strong>
                                            <span>
                                                <?= $habitaciones_total ?> <?= $habitaciones_total === 1 ? 'habitación' : 'habitaciones' ?>
                                                <?php if (!empty($reservacion['habitaciones_cortesia'])): ?>
                                                    · <?= (int)$reservacion['habitaciones_cortesia'] ?> cortesía
                                                <?php endif; ?>
                                            </span>
                                            <?php if (!empty($reservacion['tipos_habitacion'])): ?>
                                                <span><?= guest_detail_safe($reservacion['tipos_habitacion']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="guest-reservation-meta">
                                            <span>Entrada</span>
                                            <strong><?= format_date($reservacion['fecha_entrada'] ?? '') ?></strong>
                                        </div>
                                        <div class="guest-reservation-meta">
                                            <span>Salida</span>
                                            <strong><?= format_date($reservacion['fecha_salida'] ?? '') ?></strong>
                                        </div>
                                        <div class="guest-reservation-meta">
                                            <span>Total</span>
                                            <strong><?= format_money($reservacion['precio_total'] ?? 0) ?></strong>
                                            <span><?= guest_detail_safe(ucfirst($reservacion['metodo_pago'] ?? 'No especificado')) ?></span>
                                        </div>
                                        <div>
                                            <span class="guest-status-badge">
                                                <i class="fas fa-circle"></i>
                                                <?= guest_detail_safe($estadoReserva['label']) ?>
                                            </span>
                                        </div>
                                        <a href="<?= url('reservaciones/ver/' . ($reservacion['id'] ?? 0)) ?>" class="guest-reservation-link">
                                            <i class="fas fa-eye"></i>
                                            Ver
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="guest-empty guest-history-empty">
                                <i class="fas fa-calendar-times"></i>
                                <strong>Sin reservaciones registradas</strong>
                                <p>Este huésped aún no tiene reservaciones en el sistema.</p>
                                <a href="<?= url('reservaciones/crear?huesped_id=' . ($huesped['id'] ?? 0)) ?>" class="guest-panel-action">
                                    <i class="fas fa-calendar-plus"></i>
                                    Crear primera reservación
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php View::partial('documentos_entidad', [
                    'documentosEntidad' => $documentosEntidad ?? [],
                    'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
                ]); ?>
            </main>

            <aside class="guest-side-stack">
                <section class="guest-side-card guest-score-card">
                    <h2>Perfil de actividad</h2>
                    <div class="guest-score-number"><?= number_format($total_reservaciones) ?></div>
                    <p><?= $es_cliente_frecuente ? 'Huésped frecuente con historial activo.' : 'Historial en crecimiento.' ?></p>
                    <div class="guest-score-list">
                        <div>
                            <span>Total gastado</span>
                            <strong><?= format_money($total_gastado) ?></strong>
                        </div>
                        <div>
                            <span>Gasto promedio</span>
                            <strong><?= format_money($gasto_promedio) ?></strong>
                        </div>
                        <div>
                            <span>Vehículos</span>
                            <strong><?= number_format($vehiculos_count) ?></strong>
                        </div>
                    </div>
                </section>

                <section class="guest-side-card">
                    <h2>Acciones rápidas</h2>
                    <div class="guest-quick-actions">
                        <a href="<?= url('reservaciones/crear?huesped_id=' . ($huesped['id'] ?? 0)) ?>" class="guest-side-action">
                            <span><i class="fas fa-calendar-plus"></i> Nueva reservación</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="<?= url('huespedes/' . ($huesped['id'] ?? 0) . '/edit') ?>" class="guest-side-action">
                            <span><i class="fas fa-pen"></i> Editar información</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <?php if ($telefono !== ''): ?>
                            <a href="tel:<?= guest_detail_safe($telefono) ?>" class="guest-side-action">
                                <span><i class="fas fa-phone"></i> Llamar</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($email !== ''): ?>
                            <a href="mailto:<?= guest_detail_safe($email) ?>" class="guest-side-action">
                                <span><i class="fas fa-envelope"></i> Enviar email</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="guest-side-card">
                    <h2>Información del sistema</h2>
                    <ul class="guest-system-list">
                        <li>
                            <span>ID</span>
                            <strong>#<?= (int)($huesped['id'] ?? 0) ?></strong>
                        </li>
                        <li>
                            <span>Registrado</span>
                            <strong><?= format_datetime($huesped['created_at'] ?? '') ?></strong>
                        </li>
                        <li>
                            <span>Actualizado</span>
                            <strong><?= format_datetime($huesped['updated_at'] ?? '') ?></strong>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</div>

<div id="modalAgregarVehiculo" class="guest-modal hidden">
    <div class="guest-modal-card">
        <div class="guest-modal-head">
            <h3>
                <i class="fas fa-car"></i>
                Agregar vehículo
            </h3>
            <button type="button" onclick="cerrarModalAgregarVehiculo()" class="guest-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formAgregarVehiculo" class="guest-vehicle-form">
            <input type="hidden" name="huesped_id" value="<?= (int)($huesped['id'] ?? 0) ?>">
            <?= csrf_field() ?>

            <div class="guest-form-grid">
                <?= $guestRenderVehicleModalFields('add') ?>
            </div>

            <div class="guest-form-alert" data-vehicle-feedback hidden role="alert" aria-live="assertive">
                <i class="fas fa-circle-info"></i>
                <span></span>
            </div>

            <div class="guest-modal-actions">
                <button type="button" onclick="cerrarModalAgregarVehiculo()" class="guest-cancel">Cancelar</button>
                <button type="submit" class="guest-save">
                    <i class="fas fa-save"></i>
                    Guardar vehículo
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditarVehiculo" class="guest-modal hidden">
    <div class="guest-modal-card">
        <div class="guest-modal-head">
            <h3>
                <i class="fas fa-pen-to-square"></i>
                Editar vehículo
            </h3>
            <button type="button" onclick="cerrarModalEditarVehiculo()" class="guest-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formEditarVehiculo" class="guest-vehicle-form">
            <input type="hidden" name="vehiculo_id" id="edit_vehiculo_id">
            <?= csrf_field() ?>

            <div class="guest-form-grid">
                <?= $guestRenderVehicleModalFields('edit') ?>
            </div>

            <div class="guest-form-alert" data-vehicle-feedback hidden role="alert" aria-live="assertive">
                <i class="fas fa-circle-info"></i>
                <span></span>
            </div>

            <div class="guest-modal-actions">
                <button type="button" onclick="cerrarModalEditarVehiculo()" class="guest-cancel">Cancelar</button>
                <button type="submit" class="guest-save">
                    <i class="fas fa-save"></i>
                    Actualizar vehículo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Lightbox del documento del huésped (ver imagen sin salir de la vista) ── -->
<div id="guestDocLightbox" class="guest-doc-lightbox" hidden role="dialog" aria-modal="true" aria-label="Documento del huesped en pantalla completa">
    <figure class="guest-doc-lightbox-frame">
        <img id="guestDocLightboxImg" src="" alt="" draggable="false" oncontextmenu="return false;">
        <figcaption id="guestDocLightboxCaption"></figcaption>
    </figure>
    <button type="button" class="guest-doc-lightbox-close" aria-label="Cerrar vista de documento" onclick="guestCerrarDocLightbox()">
        <i class="fas fa-times" aria-hidden="true"></i>
    </button>
</div>
<style>
.guest-doc-lightbox {
    position: fixed; inset: 0; z-index: 10050;
    display: flex; align-items: center; justify-content: center;
    padding: 18px;
    background: rgba(10, 16, 26, .88);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
    animation: guestDocLbIn .22s ease;
}
.guest-doc-lightbox[hidden] { display: none; }
@keyframes guestDocLbIn { from { opacity: 0; } to { opacity: 1; } }
.guest-doc-lightbox-frame { margin: 0; display: flex; flex-direction: column; align-items: center; gap: 10px; max-width: 100%; }
.guest-doc-lightbox-frame img {
    max-width: min(94vw, 1100px);
    max-height: 82vh;
    border-radius: 14px;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .55);
    animation: guestDocLbImgIn .26s cubic-bezier(.22, 1, .36, 1);
    user-select: none;
}
@keyframes guestDocLbImgIn { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }
.guest-doc-lightbox-frame figcaption { color: rgba(255, 255, 255, .82); font-size: .82rem; font-weight: 700; text-align: center; max-width: 92vw; overflow-wrap: anywhere; }
.guest-doc-lightbox-close {
    position: absolute;
    top: calc(env(safe-area-inset-top, 0px) + 14px);
    right: 14px;
    width: 42px; height: 42px;
    display: grid; place-items: center;
    border: 1px solid rgba(255, 255, 255, .28);
    border-radius: 999px;
    background: rgba(255, 255, 255, .12);
    color: #fff; font-size: 1rem; cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    transition: background .16s ease, transform .16s ease;
}
.guest-doc-lightbox-close:hover, .guest-doc-lightbox-close:focus-visible { background: rgba(255, 255, 255, .24); }
.guest-doc-lightbox-close:active { transform: scale(.92); }
@media (prefers-reduced-motion: reduce) {
    .guest-doc-lightbox, .guest-doc-lightbox-frame img { animation: none; }
}
</style>
<script>
var guestDocLbScrollY = 0;
function guestAbrirDocLightbox(btn) {
    var lb = document.getElementById('guestDocLightbox');
    if (!lb) return;
    var img = document.getElementById('guestDocLightboxImg');
    var cap = document.getElementById('guestDocLightboxCaption');
    img.src = btn.getAttribute('data-doc-src') || '';
    img.alt = btn.getAttribute('data-doc-title') || 'Documento del huesped';
    cap.textContent = btn.getAttribute('data-doc-title') || '';
    guestDocLbScrollY = window.scrollY || document.documentElement.scrollTop || 0;
    lb.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    lb.querySelector('.guest-doc-lightbox-close').focus({ preventScroll: true });
}
function guestCerrarDocLightbox() {
    var lb = document.getElementById('guestDocLightbox');
    if (!lb || lb.hidden) return;
    lb.hidden = true;
    document.getElementById('guestDocLightboxImg').src = '';
    document.documentElement.style.overflow = '';
    window.scrollTo(0, guestDocLbScrollY);
}
document.getElementById('guestDocLightbox').addEventListener('click', function (ev) {
    if (ev.target === this) guestCerrarDocLightbox();
});
document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') guestCerrarDocLightbox();
});
</script>
<script>
function guestShowVehicleFeedback(form, type, message) {
    const alertBox = form ? form.querySelector('[data-vehicle-feedback]') : null;
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

function guestClearVehicleFeedback(form) {
    const alertBox = form ? form.querySelector('[data-vehicle-feedback]') : null;
    if (!alertBox) {
        return;
    }

    alertBox.hidden = true;
    alertBox.classList.remove('is-visible', 'is-success');
}

function guestSetVehicleSubmitting(form, isSubmitting) {
    const button = form ? form.querySelector('button[type="submit"]') : null;
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

function guestShowActionNotice(message, type) {
    let notice = document.querySelector('[data-guest-action-notice]');
    if (!notice) {
        notice = document.createElement('div');
        notice.setAttribute('data-guest-action-notice', '1');
        notice.setAttribute('role', 'status');
        notice.style.position = 'fixed';
        notice.style.right = '24px';
        notice.style.bottom = '24px';
        notice.style.zIndex = '9999';
        notice.style.maxWidth = '360px';
        notice.style.padding = '12px 14px';
        notice.style.borderRadius = '14px';
        notice.style.boxShadow = '0 16px 36px rgba(28, 35, 49, .16)';
        notice.style.fontSize = '14px';
        notice.style.fontWeight = '850';
        document.body.appendChild(notice);
    }

    const isError = type === 'error';
    const isSuccess = type === 'success';
    notice.style.border = isSuccess
        ? '1px solid rgba(35, 126, 91, .28)'
        : (isError ? '1px solid rgba(184, 61, 53, .26)' : '1px solid rgba(184, 139, 58, .28)');
    notice.style.background = isSuccess
        ? 'rgba(239, 253, 246, .98)'
        : (isError ? 'rgba(254, 242, 242, .98)' : 'rgba(255, 249, 235, .98)');
    notice.style.color = isSuccess
        ? '#166534'
        : (isError ? '#991b1b' : '#7a4b0d');
    notice.textContent = message;
    notice.style.display = 'block';

    window.clearTimeout(notice._hideTimer);
    notice._hideTimer = window.setTimeout(() => {
        notice.style.display = 'none';
    }, 4200);
}

function guestSetModalState(modalId, isOpen) {
    const modal = document.getElementById(modalId);
    const sidebar = document.getElementById('sidebar');
    if (!modal) return;

    modal.classList.toggle('hidden', !isOpen);
    document.body.classList.toggle('overflow-hidden', isOpen);
    if (sidebar) {
        sidebar.style.display = isOpen ? 'none' : '';
    }
}

function abrirModalAgregarVehiculo() {
    const form = document.getElementById('formAgregarVehiculo');
    guestClearVehicleFeedback(form);
    guestSetVehicleSubmitting(form, false);
    guestSetModalState('modalAgregarVehiculo', true);
}

function cerrarModalAgregarVehiculo() {
    guestSetModalState('modalAgregarVehiculo', false);
    const form = document.getElementById('formAgregarVehiculo');
    if (form) {
        form.reset();
        guestClearVehicleFeedback(form);
        guestSetVehicleSubmitting(form, false);
    }
}

function abrirModalEditarVehiculo() {
    const form = document.getElementById('formEditarVehiculo');
    guestClearVehicleFeedback(form);
    guestSetVehicleSubmitting(form, false);
    guestSetModalState('modalEditarVehiculo', true);
}

function cerrarModalEditarVehiculo() {
    guestSetModalState('modalEditarVehiculo', false);
    const form = document.getElementById('formEditarVehiculo');
    if (form) {
        form.reset();
        guestClearVehicleFeedback(form);
        guestSetVehicleSubmitting(form, false);
    }
}

function editarVehiculo(vehiculo) {
    document.getElementById('edit_vehiculo_id').value = vehiculo.id || '';
    document.getElementById('edit_marca') && (document.getElementById('edit_marca').value = vehiculo.marca || '');
    document.getElementById('edit_modelo') && (document.getElementById('edit_modelo').value = vehiculo.modelo || '');
    document.getElementById('edit_placas') && (document.getElementById('edit_placas').value = vehiculo.placas || '');
    document.getElementById('edit_color') && (document.getElementById('edit_color').value = vehiculo.color || '');

    let extras = {};
    try {
        extras = typeof vehiculo.datos_extra_json === 'string'
            ? JSON.parse(vehiculo.datos_extra_json || '{}')
            : (vehiculo.datos_extra_json || {});
    } catch (error) {
        extras = {};
    }

    document.querySelectorAll('[data-edit-extra]').forEach(input => {
        const key = input.dataset.editExtra;
        input.value = extras && Object.prototype.hasOwnProperty.call(extras, key) ? extras[key] : '';
    });

    const selectedParking = vehiculo.estacionamiento || <?= json_encode($estacionamientoDefault, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const parkingRadios = document.querySelectorAll('.edit-estacionamiento');
    let parkingMatched = false;
    parkingRadios.forEach(radio => {
        radio.checked = radio.value === selectedParking;
        if (radio.checked) {
            parkingMatched = true;
        }
    });
    if (!parkingMatched && parkingRadios.length > 0) {
        parkingRadios[0].checked = true;
    }

    abrirModalEditarVehiculo();
}

document.getElementById('modalAgregarVehiculo')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAgregarVehiculo();
});

document.getElementById('modalEditarVehiculo')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEditarVehiculo();
});

document.querySelector('#formAgregarVehiculo input[name="placas"]')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

document.getElementById('edit_placas')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

document.getElementById('formAgregarVehiculo')?.addEventListener('submit', function(e) {
    e.preventDefault();

    guestClearVehicleFeedback(this);
    guestSetVehicleSubmitting(this, true);
    const formData = new FormData(this);

    fetch('<?= url('huespedes/agregar-vehiculo') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            guestShowVehicleFeedback(this, 'success', data.message || 'Vehiculo agregado correctamente.');
            window.setTimeout(() => location.reload(), 700);
        } else {
            guestSetVehicleSubmitting(this, false);
            guestShowVehicleFeedback(this, 'error', data.message || 'No se pudo agregar el vehiculo.');
        }
    })
    .catch(() => {
        guestSetVehicleSubmitting(this, false);
        guestShowVehicleFeedback(this, 'error', 'Ocurrio un error al agregar el vehiculo.');
    });
});

document.getElementById('formEditarVehiculo')?.addEventListener('submit', function(e) {
    e.preventDefault();

    guestClearVehicleFeedback(this);
    guestSetVehicleSubmitting(this, true);
    const formData = new FormData(this);

    fetch('<?= url('huespedes/actualizar-vehiculo') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            guestShowVehicleFeedback(this, 'success', data.message || 'Vehiculo actualizado correctamente.');
            window.setTimeout(() => location.reload(), 700);
        } else {
            guestSetVehicleSubmitting(this, false);
            guestShowVehicleFeedback(this, 'error', data.message || 'Error al actualizar el vehiculo.');
        }
    })
    .catch(() => {
        guestSetVehicleSubmitting(this, false);
        guestShowVehicleFeedback(this, 'error', 'Ocurrio un error al actualizar el vehiculo. Intente nuevamente.');
    });
});

function confirmarEliminarVehiculo(trigger, vehiculoId, descripcion) {
    msConfirm({
        type: 'error',
        icon: 'trash',
        title: 'Eliminar vehículo',
        msg: `Se quitará ${descripcion} del huésped. Esta acción no se puede deshacer.`,
        confirmLabel: 'Eliminar'
    }).then(ok => {
        if (!ok) return;
        eliminarVehiculoConfirmado(trigger, vehiculoId);
    });
}

function eliminarVehiculoConfirmado(trigger, vehiculoId) {
    if (trigger) {
        trigger.disabled = true;
    }

    const formData = new FormData();
    formData.append('vehiculo_id', vehiculoId);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('<?= url('huespedes/eliminar-vehiculo') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            guestShowActionNotice(data.message || 'Vehiculo eliminado correctamente.', 'success');
            window.setTimeout(() => location.reload(), 700);
        } else {
            if (trigger) {
                trigger.disabled = false;
            }
            guestShowActionNotice(data.message || 'No se pudo eliminar el vehiculo.', 'error');
        }
    })
    .catch(() => {
        if (trigger) {
            trigger.disabled = false;
        }
        guestShowActionNotice('No se pudo eliminar el vehiculo. Intente nuevamente.', 'error');
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalAgregarVehiculo();
        cerrarModalEditarVehiculo();
    }
});
</script>

<style id="gd-candy-glass-cupertino">
/* ═══ Expediente de huésped: candy glass — SOLO TEMA CUPERTINO, claro ═══════
   Mismo lenguaje que caja/habitaciones/dashboard: lienzo gris perla, héroe
   como losa pastel de la marca con bloom y tinta, chips lechosos, y la
   tarjeta "Perfil de actividad" (antes banda oscura con texto blanco) como
   losa fuerte de marca con tinta. El avatar y el CTA "Nueva reservación"
   conservan su sólido de marca (anclas). Deleite y modo oscuro intactos.
   Revertir: borrar este bloque. */

/* Tokens del expediente → neutrales Apple (el lienzo crema pasa a gris perla) */
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view{
    --gd-bg:#F5F5F7;
    --gd-surface:rgba(255,255,255,.92);
    --gd-surface-solid:#FFFFFF;
    --gd-line:#E8E8ED;
    --gd-line-soft:#EDEDF0;
}

/* Héroe: losa de cristal de la marca */
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-identity-card{
    border:1px solid color-mix(in srgb, var(--gd-primary) 22%, rgba(255,255,255,.9));
    border-radius:22px;
    background:
        radial-gradient(46% 160% at 96% 72%, rgba(255,255,255,.82), rgba(255,255,255,0) 72%),
        linear-gradient(180deg, rgba(255,255,255,.5), rgba(255,255,255,0) 40%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--gd-primary) 12%, #FFFFFF) 0%,
            color-mix(in srgb, var(--gd-primary) 26%, #FFFFFF) 100%);
    box-shadow:
        inset 0 1px 1px rgba(255,255,255,.95),
        inset 0 -2px 5px color-mix(in srgb, var(--gd-primary) 12%, transparent),
        0 3px 7px color-mix(in srgb, var(--gd-primary) 10%, rgba(27,39,70,.05)),
        0 24px 44px -20px color-mix(in srgb, var(--gd-primary) 42%, rgba(27,39,70,.22));
}

/* Fuera la regla ornamental oro→navy del pie del héroe (lenguaje Deleite) */
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-identity-card::after{
    display:none;
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-kicker{
    color:color-mix(in srgb, var(--gd-primary) 62%, #111827);
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-identity-card h1{
    color:color-mix(in srgb, var(--gd-primary) 30%, #111827);
    text-shadow:0 1px 0 rgba(255,255,255,.4);
}

/* Chips del héroe: lechosos */
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-chip{
    border:1px solid rgba(255,255,255,.88);
    background:rgba(255,255,255,.62);
    color:#1F2937;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.85);
    font-weight:700;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-chip i{
    color:color-mix(in srgb, var(--gd-primary) 70%, #111827);
}

/* Rail de acciones: losa candy suave; el CTA conserva su sólido de marca */
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-action-rail{
    border:1px solid color-mix(in srgb, var(--gd-primary) 18%, rgba(255,255,255,.9));
    background:
        radial-gradient(46% 160% at 96% 80%, rgba(255,255,255,.8), rgba(255,255,255,0) 72%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--gd-primary) 8%, #FFFFFF) 0%,
            color-mix(in srgb, var(--gd-primary) 17%, #FFFFFF) 100%);
    box-shadow:
        inset 0 1px 1px rgba(255,255,255,.92),
        0 2px 5px color-mix(in srgb, var(--gd-primary) 8%, rgba(27,39,70,.04)),
        0 16px 30px -18px color-mix(in srgb, var(--gd-primary) 36%, rgba(27,39,70,.18));
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-action:not(.primary){
    border:1px solid rgba(255,255,255,.88);
    background:rgba(255,255,255,.62);
    color:#1F2937;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.85);
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-action:not(.primary):hover{
    background:rgba(255,255,255,.85);
}

/* Historial de reservaciones: cada renglón como losa candy suave; el hilo
   de estado (inset izquierdo) y el chip conservan su color SEMÁNTICO */
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-reservation-card{
    border:1px solid color-mix(in srgb, var(--gd-primary) 16%, rgba(255,255,255,.9));
    background:
        radial-gradient(46% 160% at 96% 80%, rgba(255,255,255,.8), rgba(255,255,255,0) 72%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--gd-primary) 7%, #FFFFFF) 0%,
            color-mix(in srgb, var(--gd-primary) 15%, #FFFFFF) 100%);
    box-shadow:
        inset 4px 0 0 var(--status-color),
        inset 0 1px 1px rgba(255,255,255,.9),
        0 12px 24px -16px color-mix(in srgb, var(--gd-primary) 32%, rgba(27,39,70,.16));
}

/* Vista documental y vacíos punteados: lechosos con hilo de marca */
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-docs-title,
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-docs-title i{
    color:color-mix(in srgb, var(--gd-primary) 62%, #111827);
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-doc-action{
    border:1px solid rgba(255,255,255,.88);
    background:rgba(255,255,255,.62);
    color:#1F2937;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.85);
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-doc-action:hover{
    background:rgba(255,255,255,.85);
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-doc-empty,
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-empty{
    border:1px dashed color-mix(in srgb, var(--gd-primary) 28%, rgba(17,24,39,.1));
    background:rgba(255,255,255,.55);
    color:#6E6E73;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.8);
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-doc-empty i,
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-empty > i{
    color:color-mix(in srgb, var(--gd-primary) 45%, #94A3B8);
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-action.primary{
    box-shadow:inset 0 1px 0 rgba(255,255,255,.3), 0 8px 16px -10px color-mix(in srgb, var(--gd-primary) 60%, transparent);
}

/* Perfil de actividad: de banda oscura a losa fuerte de marca con tinta */
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-score-card{
    border:1px solid color-mix(in srgb, var(--gd-primary) 28%, rgba(255,255,255,.9));
    background:
        radial-gradient(46% 160% at 96% 66%, rgba(255,255,255,.82), rgba(255,255,255,0) 72%),
        linear-gradient(180deg, rgba(255,255,255,.5), rgba(255,255,255,0) 40%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--gd-primary) 16%, #FFFFFF) 0%,
            color-mix(in srgb, var(--gd-primary) 32%, #FFFFFF) 100%);
    color:#1F2937;
    box-shadow:
        inset 0 1px 1px rgba(255,255,255,.95),
        inset 0 -2px 5px color-mix(in srgb, var(--gd-primary) 13%, transparent),
        0 18px 34px -18px color-mix(in srgb, var(--gd-primary) 45%, rgba(27,39,70,.22));
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-score-card h2{
    color:color-mix(in srgb, var(--gd-primary) 62%, #111827);
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-score-number{
    color:color-mix(in srgb, var(--gd-primary) 30%, #111827);
    text-shadow:0 1px 0 rgba(255,255,255,.4);
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-score-card p{
    color:#48484A;
}

html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-score-list div{
    border-color:color-mix(in srgb, var(--gd-primary) 16%, rgba(17,24,39,.06)) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-score-list span{
    color:#48484A;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .guest-detail-view .guest-score-list strong{
    color:#111827;
}
</style>
