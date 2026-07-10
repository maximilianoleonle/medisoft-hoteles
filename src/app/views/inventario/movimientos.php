<?php
$movimientos = $movimientos ?? [];
$productos = $productos ?? [];
$filtros = $filtros ?? [];
$resumen_movimientos = $resumen_movimientos ?? [];
$total_movimientos = (int) ($total_movimientos ?? count($movimientos));
$total_paginas = max(1, (int) ($total_paginas ?? 1));
$pagina_actual = max(1, (int) ($pagina_actual ?? 1));

$inv_mov_safe = static function ($value, $fallback = '') {
    if ($value === null || $value === '') {
        return $fallback;
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$inv_mov_date = static function ($value) {
    if (empty($value) || $value === '0000-00-00 00:00:00') {
        return 'Sin fecha';
    }
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : 'Sin fecha';
};

$inv_mov_number = static function ($value) {
    return number_format((float) ($value ?? 0), 2);
};

$inv_mov_type_meta = static function ($type) {
    $type = strtoupper((string) $type);
    return match ($type) {
        'ENTRADA' => ['label' => 'Entrada', 'class' => 'entrada', 'icon' => 'arrow-down'],
        'SALIDA' => ['label' => 'Salida', 'class' => 'salida', 'icon' => 'arrow-up'],
        'AJUSTE' => ['label' => 'Ajuste', 'class' => 'ajuste', 'icon' => 'sliders-h'],
        default => ['label' => $type ?: 'Movimiento', 'class' => 'neutral', 'icon' => 'exchange-alt'],
    };
};

$inv_mov_origin_meta = static function (array $movement) {
    $origin = strtolower((string) ($movement['origen_inferido'] ?? ''));
    if ($origin === '') {
        $reason = strtolower((string) ($movement['motivo'] ?? ''));
        $has_reservation = !empty($movement['reservacion_id']);
        $looks_automatic = str_contains($reason, 'autom')
            || str_contains($reason, 'check-in')
            || str_contains($reason, 'devolucion')
            || str_contains($reason, 'cancelacion')
            || $has_reservation;
        $origin = $looks_automatic && !str_contains($reason, 'manual') ? 'automatico' : 'manual';
    }

    return $origin === 'manual'
        ? [
            'label' => 'Manual',
            'class' => 'manual',
            'icon' => 'hand-pointer',
            'detail' => 'Registrado desde una operacion directa de inventario.',
        ]
        : [
            'label' => 'Automatico',
            'class' => 'automatico',
            'icon' => 'robot',
            'detail' => 'Generado por un flujo del sistema.',
        ];
};

$build_page_url = static function ($page) {
    $params = $_GET ?? [];
    $params['pagina'] = max(1, (int) $page);
    return url('inventario/movimientos') . '?' . http_build_query($params);
};

$build_filter_url = static function (array $overrides) {
    $params = array_merge($_GET ?? [], $overrides);
    unset($params['pagina']);
    $params = array_filter($params, static fn($value) => $value !== '' && $value !== null);
    return url('inventario/movimientos') . (!empty($params) ? '?' . http_build_query($params) : '');
};

$total_entradas = (int) ($resumen_movimientos['entradas'] ?? 0);
$total_salidas = (int) ($resumen_movimientos['salidas'] ?? 0);
$total_ajustes = (int) ($resumen_movimientos['ajustes'] ?? 0);
$total_manuales = (int) ($resumen_movimientos['manuales'] ?? 0);
$total_automaticos = (int) ($resumen_movimientos['automaticos'] ?? 0);
$total_unidades = (float) ($resumen_movimientos['unidades'] ?? 0);
?>

<style>
:root {
    --hotel-primary: var(--brand-primary, #5C7A4E);
    --hotel-secondary: var(--brand-secondary, #4A6340);
    --hotel-accent: var(--brand-accent, #C8A96A);
    --hotel-text: var(--brand-text, #344054);
    --lc-green: var(--hotel-primary);
    --lc-green-dark: color-mix(in srgb, var(--hotel-primary) 76%, var(--hotel-secondary));
    --lc-green-deep: color-mix(in srgb, var(--hotel-secondary) 76%, #2F3A2D);
    --lc-gold: var(--hotel-accent);
    --lc-cream: color-mix(in srgb, var(--hotel-accent) 7%, #F7F4EE);
    --lc-cream-mid: color-mix(in srgb, var(--hotel-primary) 7%, #EEE9DE);
    --inv-mov-text: #344054;
    --inv-mov-muted: color-mix(in srgb, var(--hotel-secondary) 42%, #7A8574);
    --inv-mov-line: color-mix(in srgb, var(--hotel-primary) 16%, #DDE8D5);
    --inv-mov-panel: #FFFEFB;
    --inv-mov-soft: color-mix(in srgb, var(--hotel-primary) 6%, #F3F7F0);
    --inv-mov-success: #10B981;
    --inv-mov-warning: #F59E0B;
    --inv-mov-danger: #EF4444;
    --inv-mov-info: #3B6FD6;
    --inv-mov-manual: color-mix(in srgb, var(--hotel-accent) 54%, #7C3AED);
    --inv-mov-manual-entrada: #0891B2;
    --inv-mov-manual-salida: #E11D48;
    --inv-mov-manual-ajuste: #7C3AED;
    --inv-mov-auto: color-mix(in srgb, var(--hotel-primary) 62%, #3B6FD6);
    --inv-mov-shadow: 0 18px 42px -34px color-mix(in srgb, var(--hotel-secondary) 34%, transparent);
}

.inv-mov-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at 12% 0%, color-mix(in srgb, var(--hotel-primary) 13%, transparent), transparent 24rem),
        radial-gradient(circle at 90% 5%, color-mix(in srgb, var(--hotel-accent) 14%, transparent), transparent 26rem),
        linear-gradient(145deg, color-mix(in srgb, var(--hotel-primary) 7%, #EFF4EC) 0%, color-mix(in srgb, var(--hotel-primary) 5%, #E8EFE3) 45%, color-mix(in srgb, var(--hotel-accent) 7%, #F4F1EB) 100%);
    color: var(--inv-mov-text);
    font-family: "Inter", "Segoe UI", system-ui, sans-serif;
}

.inv-mov-topbar {
    position: relative;
    background: rgba(255, 254, 251, .9);
    border-bottom: 1px solid var(--inv-mov-line);
}

.inv-mov-topbar::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--lc-green-deep), var(--lc-gold), var(--lc-green-deep));
}

.inv-mov-shell {
    width: min(1320px, calc(100% - 32px));
    margin: 0 auto;
}

.inv-mov-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 0;
}

.inv-mov-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.inv-mov-icon {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: color-mix(in srgb, var(--hotel-primary) 13%, #FFFEFB);
    color: var(--lc-green);
    border: 1px solid color-mix(in srgb, var(--hotel-primary) 20%, #DDE8D5);
}

.inv-mov-title-group h1 {
    margin: 0;
    color: var(--lc-green-deep);
    font-size: 1.18rem;
    line-height: 1.15;
    font-weight: 780;
}

.inv-mov-title-group p {
    margin: 4px 0 0;
    color: var(--inv-mov-muted);
    font-size: .78rem;
    line-height: 1.35;
}

.inv-mov-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.inv-mov-btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 11px;
    border: 1px solid transparent;
    padding: 0 13px;
    font-size: .78rem;
    font-weight: 760;
    text-decoration: none;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
}

.inv-mov-btn:hover,
.inv-mov-btn:focus-visible {
    transform: translateY(-1px);
    outline: none;
}

.inv-mov-btn:active {
    transform: translateY(0) scale(.99);
}

.inv-mov-btn.ghost {
    background: #FFFEFB;
    border-color: var(--inv-mov-line);
    color: var(--lc-green-deep);
}

.inv-mov-btn.primary {
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    color: #FFFEFB;
    box-shadow: 0 12px 24px -18px color-mix(in srgb, var(--hotel-primary) 70%, transparent);
}

.inv-mov-main {
    padding: 22px 0 54px;
}

.inv-mov-stats {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 14px;
}

.inv-mov-stat {
    position: relative;
    min-height: 92px;
    display: grid;
    align-content: space-between;
    overflow: hidden;
    border: 1px solid var(--inv-mov-line);
    border-radius: 16px;
    background: var(--inv-mov-panel);
    box-shadow: var(--inv-mov-shadow);
    padding: 14px;
}

.inv-mov-stat::before {
    content: "";
    position: absolute;
    right: -18px;
    bottom: -24px;
    width: 76px;
    height: 76px;
    border-radius: 999px;
    background: var(--stat-color, var(--lc-green));
    opacity: .07;
    pointer-events: none;
}

.inv-mov-stat-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: var(--inv-mov-muted);
    font-size: .68rem;
    font-weight: 780;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.inv-mov-stat-label i {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--stat-color, var(--lc-green)) 11%, #FFFEFB);
    color: var(--stat-color, var(--lc-green));
}

.inv-mov-stat strong {
    position: relative;
    display: block;
    margin-top: 10px;
    color: color-mix(in srgb, var(--stat-color, var(--lc-green)) 44%, var(--lc-green-deep));
    font-size: 1.45rem;
    line-height: 1;
    font-weight: 780;
    font-variant-numeric: tabular-nums;
}

.inv-mov-stat.total { --stat-color: var(--lc-green); }
.inv-mov-stat.entrada { --stat-color: var(--inv-mov-success); }
.inv-mov-stat.salida { --stat-color: var(--inv-mov-warning); }
.inv-mov-stat.ajuste { --stat-color: var(--inv-mov-info); }
.inv-mov-stat.manual { --stat-color: var(--inv-mov-manual); }
.inv-mov-stat.automatico { --stat-color: var(--inv-mov-auto); }
.inv-mov-stat.unidades { --stat-color: var(--inv-mov-info); }

.inv-mov-panel {
    border: 1px solid var(--inv-mov-line);
    border-radius: 16px;
    background: var(--inv-mov-panel);
    box-shadow: var(--inv-mov-shadow);
    overflow: hidden;
}

.inv-mov-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 15px 16px;
    border-bottom: 1px solid #EAF0E5;
}

.inv-mov-panel-head h2 {
    margin: 0;
    color: var(--lc-green-deep);
    font-size: .98rem;
    line-height: 1.2;
    font-weight: 780;
}

.inv-mov-panel-head p {
    margin: 4px 0 0;
    color: var(--inv-mov-muted);
    font-size: .76rem;
    line-height: 1.35;
}

.inv-mov-count {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: #F0F5ED;
    border: 1px solid #D5E4CB;
    color: var(--lc-green-dark);
    font-size: .7rem;
    font-weight: 760;
    white-space: nowrap;
}

.inv-mov-filters {
    display: grid;
    grid-template-columns: minmax(200px, 1.2fr) minmax(130px, .7fr) minmax(150px, .8fr) repeat(2, minmax(145px, .7fr)) auto auto;
    gap: 10px;
    align-items: end;
    padding: 14px;
    border-bottom: 1px solid var(--inv-mov-line);
    background: linear-gradient(135deg, color-mix(in srgb, var(--hotel-primary) 4%, #FFFEFB), color-mix(in srgb, var(--hotel-accent) 5%, #F8F7F2));
}

.inv-mov-field label {
    display: block;
    margin-bottom: 6px;
    color: color-mix(in srgb, var(--hotel-secondary) 62%, #4A5568);
    font-size: .7rem;
    line-height: 1.2;
    font-weight: 760;
}

.inv-mov-input {
    width: 100%;
    min-height: 38px;
    border: 1px solid color-mix(in srgb, var(--hotel-primary) 18%, #DADFE7);
    border-radius: 10px;
    background: #FFFEFB;
    color: var(--inv-mov-text);
    padding: 0 11px;
    font-size: .78rem;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.inv-mov-input:focus {
    outline: none;
    border-color: var(--lc-green);
    background: #FFFEFB;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--hotel-primary) 16%, transparent);
}

.inv-mov-quick-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px 14px 0;
    background: linear-gradient(135deg, color-mix(in srgb, var(--hotel-primary) 4%, #FFFEFB), color-mix(in srgb, var(--hotel-accent) 5%, #F8F7F2));
}

.inv-mov-quick {
    min-height: 30px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid color-mix(in srgb, var(--hotel-primary) 14%, #DDE8D5);
    border-radius: 999px;
    background: #FFFEFB;
    color: color-mix(in srgb, var(--hotel-secondary) 64%, #475467);
    padding: 0 10px;
    font-size: .72rem;
    font-weight: 760;
    text-decoration: none;
    transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
}

.inv-mov-quick:hover,
.inv-mov-quick:focus-visible {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--hotel-accent) 42%, var(--inv-mov-line));
    background: color-mix(in srgb, var(--hotel-accent) 8%, #FFFEFB);
    outline: none;
}

.inv-mov-quick.is-active {
    border-color: color-mix(in srgb, var(--hotel-primary) 42%, var(--inv-mov-line));
    background: color-mix(in srgb, var(--hotel-primary) 12%, #FFFEFB);
    color: color-mix(in srgb, var(--hotel-secondary) 78%, #344054);
}

.inv-mov-table-wrap {
    overflow-x: auto;
}

.inv-mov-table {
    width: 100%;
    min-width: 920px;
    border-collapse: collapse;
}

.inv-mov-table th {
    padding: 11px 12px;
    color: color-mix(in srgb, var(--hotel-secondary) 48%, #667085);
    font-size: .67rem;
    font-weight: 780;
    letter-spacing: .05em;
    text-align: left;
    text-transform: uppercase;
    border-bottom: 1px solid #EAF0E5;
    background: #FFFEFB;
}

.inv-mov-table td {
    padding: 12px;
    color: var(--inv-mov-text);
    font-size: .78rem;
    border-bottom: 1px solid #F0F5ED;
    vertical-align: middle;
}

.inv-mov-table tr:hover td {
    background: color-mix(in srgb, var(--hotel-primary) 5%, #FFFEFB);
}

.inv-mov-row.manual td {
    background: color-mix(in srgb, var(--inv-mov-manual) 5%, #FFFEFB);
}

.inv-mov-row.manual.entrada td {
    background: color-mix(in srgb, var(--inv-mov-manual-entrada) 7%, #FFFEFB);
}

.inv-mov-row.manual.salida td {
    background: color-mix(in srgb, var(--inv-mov-manual-salida) 7%, #FFFEFB);
}

.inv-mov-row.manual.ajuste td {
    background: color-mix(in srgb, var(--inv-mov-manual-ajuste) 7%, #FFFEFB);
}

.inv-mov-row.manual:hover td {
    background: color-mix(in srgb, var(--inv-mov-manual) 9%, #FFFEFB);
}

.inv-mov-row.manual.entrada:hover td {
    background: color-mix(in srgb, var(--inv-mov-manual-entrada) 11%, #FFFEFB);
}

.inv-mov-row.manual.salida:hover td {
    background: color-mix(in srgb, var(--inv-mov-manual-salida) 11%, #FFFEFB);
}

.inv-mov-row.manual.ajuste:hover td {
    background: color-mix(in srgb, var(--inv-mov-manual-ajuste) 11%, #FFFEFB);
}

.inv-mov-row.manual .inv-mov-motivo {
    color: #344054;
    font-weight: 780;
}

.inv-mov-product {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.inv-mov-product-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: color-mix(in srgb, var(--hotel-primary) 10%, #FFFEFB);
    color: var(--lc-green);
}

.inv-mov-product strong,
.inv-mov-card-title strong {
    display: block;
    color: #344054;
    font-size: .82rem;
    line-height: 1.2;
    font-weight: 760;
}

.inv-mov-product small,
.inv-mov-card-title small {
    display: block;
    margin-top: 2px;
    color: #98A2B3;
    font-size: .68rem;
    line-height: 1.25;
}

.inv-mov-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-height: 26px;
    padding: 0 9px;
    border-radius: 999px;
    font-size: .69rem;
    font-weight: 780;
}

.inv-mov-badge.entrada {
    color: #065F46;
    background: rgba(16, 185, 129, .1);
    border: 1px solid rgba(16, 185, 129, .22);
}

.inv-mov-badge.salida {
    color: #92400E;
    background: rgba(245, 158, 11, .1);
    border: 1px solid rgba(245, 158, 11, .22);
}

.inv-mov-badge.ajuste {
    color: #1D4ED8;
    background: rgba(59, 111, 214, .1);
    border: 1px solid rgba(59, 111, 214, .22);
}

.inv-mov-badge.neutral {
    color: #475467;
    background: #F2F4F7;
    border: 1px solid #EAECF0;
}

.inv-mov-origin {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 800;
}

.inv-mov-origin.manual {
    color: color-mix(in srgb, var(--inv-mov-manual) 60%, #5B3B00);
    background: color-mix(in srgb, var(--inv-mov-manual) 11%, #FFFEFB);
    border: 1px solid color-mix(in srgb, var(--inv-mov-manual) 25%, #E7DEC9);
}

.inv-mov-origin.manual.entrada {
    color: color-mix(in srgb, var(--inv-mov-manual-entrada) 72%, #1F2937);
    background: color-mix(in srgb, var(--inv-mov-manual-entrada) 13%, #FFFEFB);
    border-color: color-mix(in srgb, var(--inv-mov-manual-entrada) 34%, #DADFE7);
}

.inv-mov-origin.manual.salida {
    color: color-mix(in srgb, var(--inv-mov-manual-salida) 76%, #1F2937);
    background: color-mix(in srgb, var(--inv-mov-manual-salida) 12%, #FFFEFB);
    border-color: color-mix(in srgb, var(--inv-mov-manual-salida) 34%, #DADFE7);
}

.inv-mov-origin.manual.ajuste {
    color: color-mix(in srgb, var(--inv-mov-manual-ajuste) 72%, #1F2937);
    background: color-mix(in srgb, var(--inv-mov-manual-ajuste) 12%, #FFFEFB);
    border-color: color-mix(in srgb, var(--inv-mov-manual-ajuste) 34%, #DADFE7);
}

.inv-mov-origin.automatico {
    color: color-mix(in srgb, var(--inv-mov-auto) 62%, #1D4ED8);
    background: color-mix(in srgb, var(--inv-mov-auto) 10%, #FFFEFB);
    border: 1px solid color-mix(in srgb, var(--inv-mov-auto) 22%, #DDE8D5);
}

.inv-mov-origin-detail {
    display: block;
    margin-top: 5px;
    color: var(--inv-mov-muted);
    font-size: .68rem;
    line-height: 1.3;
}

.inv-mov-num {
    color: #475467;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.inv-mov-motivo {
    max-width: 260px;
    color: #596579;
    line-height: 1.35;
}

.inv-mov-context {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    max-width: 260px;
}

.inv-mov-context-link,
.inv-mov-context-chip {
    min-height: 26px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #E0EBD8;
    border-radius: 999px;
    background: #FFFEFB;
    color: color-mix(in srgb, var(--hotel-secondary) 62%, #475467);
    padding: 0 9px;
    font-size: .7rem;
    font-weight: 760;
    text-decoration: none;
}

.inv-mov-context-link {
    border-color: color-mix(in srgb, var(--hotel-accent) 34%, #DDE8D5);
    color: color-mix(in srgb, var(--hotel-secondary) 74%, #344054);
    text-decoration-line: underline;
    text-decoration-color: color-mix(in srgb, var(--hotel-accent) 56%, transparent);
    text-decoration-thickness: 2px;
    text-underline-offset: 4px;
}

.inv-mov-context-link:hover,
.inv-mov-context-link:focus-visible {
    background: color-mix(in srgb, var(--hotel-accent) 8%, #FFFEFB);
    outline: none;
}

.inv-mov-id-tag {
    display: inline-flex;
    margin-top: 5px;
    color: #98A2B3;
    font-size: .67rem;
    font-weight: 720;
}

.inv-mov-empty {
    padding: 48px 18px;
    text-align: center;
}

.inv-mov-empty i {
    display: inline-flex;
    width: 48px;
    height: 48px;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: color-mix(in srgb, var(--hotel-primary) 9%, #FFFEFB);
    color: var(--lc-green);
    margin-bottom: 12px;
}

.inv-mov-empty h3 {
    margin: 0;
    color: #344054;
    font-size: .95rem;
    font-weight: 760;
}

.inv-mov-empty p {
    margin: 5px auto 0;
    max-width: 420px;
    color: var(--inv-mov-muted);
    font-size: .78rem;
    line-height: 1.45;
}

.inv-mov-mobile-list {
    display: none;
    padding: 12px;
    gap: 10px;
}

.inv-mov-card {
    border: 1px solid #E0EBD8;
    border-radius: 14px;
    background: #FFFEFB;
    padding: 12px;
}

.inv-mov-card.manual {
    border-color: color-mix(in srgb, var(--inv-mov-manual) 32%, #E0EBD8);
    background: color-mix(in srgb, var(--inv-mov-manual) 6%, #FFFEFB);
}

.inv-mov-card.manual.entrada {
    border-color: color-mix(in srgb, var(--inv-mov-manual-entrada) 34%, #E0EBD8);
    background: color-mix(in srgb, var(--inv-mov-manual-entrada) 7%, #FFFEFB);
}

.inv-mov-card.manual.salida {
    border-color: color-mix(in srgb, var(--inv-mov-manual-salida) 34%, #E0EBD8);
    background: color-mix(in srgb, var(--inv-mov-manual-salida) 7%, #FFFEFB);
}

.inv-mov-card.manual.ajuste {
    border-color: color-mix(in srgb, var(--inv-mov-manual-ajuste) 34%, #E0EBD8);
    background: color-mix(in srgb, var(--inv-mov-manual-ajuste) 7%, #FFFEFB);
}

.inv-mov-card-top {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 10px;
}

.inv-mov-card-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.inv-mov-card-kv {
    padding: 8px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--hotel-primary) 5%, #FFFEFB);
}

.inv-mov-card-kv span {
    display: block;
    color: var(--inv-mov-muted);
    font-size: .65rem;
    font-weight: 760;
    text-transform: uppercase;
}

.inv-mov-card-kv strong {
    display: block;
    margin-top: 3px;
    color: #344054;
    font-size: .78rem;
    font-weight: 760;
}

.inv-mov-card-context {
    display: grid;
    gap: 8px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid color-mix(in srgb, var(--hotel-primary) 12%, #EAECF0);
}

.inv-mov-card-note {
    color: #596579;
    font-size: .75rem;
    line-height: 1.4;
}

.inv-mov-card.manual .inv-mov-card-note {
    color: #344054;
    font-weight: 780;
}

.inv-mov-pagination {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 7px;
    padding: 14px;
    border-top: 1px solid #EAF0E5;
}

.inv-mov-page-link {
    min-width: 34px;
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #DDE8D5;
    border-radius: 10px;
    background: #FFFEFB;
    color: var(--lc-green-deep);
    padding: 0 10px;
    font-size: .76rem;
    font-weight: 760;
    text-decoration: none;
}

.inv-mov-page-link.active {
    background: var(--lc-green);
    border-color: var(--lc-green);
    color: #FFFEFB;
}

.inv-mov-page-link.disabled {
    pointer-events: none;
    opacity: .45;
}

@media (max-width: 1160px) {
    .inv-mov-stats {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .inv-mov-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .inv-mov-shell {
        width: min(100% - 24px, 1320px);
    }

    .inv-mov-header,
    .inv-mov-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .inv-mov-stats,
    .inv-mov-filters {
        grid-template-columns: 1fr;
    }

    .inv-mov-panel-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .inv-mov-table-wrap {
        display: none;
    }

    .inv-mov-mobile-list {
        display: grid;
    }
}
</style>

<div class="inv-mov-page">
    <header class="inv-mov-topbar">
        <div class="inv-mov-shell">
            <div class="inv-mov-header">
                <div class="inv-mov-title-group">
                    <div class="inv-mov-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div>
                        <h1>Movimientos de inventario</h1>
                        <p>Entradas, salidas y ajustes registrados en el inventario del hotel.</p>
                    </div>
                </div>

                <div class="inv-mov-actions">
                    <?php $back_arrow_href = back_url('inventario'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                    <a href="<?= back_url('inventario') ?>" class="inv-mov-btn ghost ms-back-legacy">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                    <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
                    <a href="<?= url('inventario/exportar') ?>" class="inv-mov-btn primary">
                        <i class="fas fa-file-pdf"></i>
                        Exportar PDF
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="inv-mov-main">
        <div class="inv-mov-shell">
            <?= get_mensaje() ?>

            <section class="inv-mov-stats" aria-label="Resumen de movimientos">
                <article class="inv-mov-stat total">
                    <span class="inv-mov-stat-label">
                        Movimientos
                        <i class="fas fa-list"></i>
                    </span>
                    <strong><?= number_format($total_movimientos) ?></strong>
                </article>
                <article class="inv-mov-stat entrada">
                    <span class="inv-mov-stat-label">
                        Entradas
                        <i class="fas fa-arrow-down"></i>
                    </span>
                    <strong><?= number_format($total_entradas) ?></strong>
                </article>
                <article class="inv-mov-stat salida">
                    <span class="inv-mov-stat-label">
                        Salidas
                        <i class="fas fa-arrow-up"></i>
                    </span>
                    <strong><?= number_format($total_salidas) ?></strong>
                </article>
                <article class="inv-mov-stat ajuste">
                    <span class="inv-mov-stat-label">
                        Ajustes
                        <i class="fas fa-sliders-h"></i>
                    </span>
                    <strong><?= number_format($total_ajustes) ?></strong>
                </article>
                <article class="inv-mov-stat manual">
                    <span class="inv-mov-stat-label">
                        Manuales
                        <i class="fas fa-hand-pointer"></i>
                    </span>
                    <strong><?= number_format($total_manuales) ?></strong>
                </article>
                <article class="inv-mov-stat automatico">
                    <span class="inv-mov-stat-label">
                        Automaticos
                        <i class="fas fa-robot"></i>
                    </span>
                    <strong><?= number_format($total_automaticos) ?></strong>
                </article>
            </section>

            <section class="inv-mov-panel" aria-label="Listado de movimientos">
                <div class="inv-mov-panel-head">
                    <div>
                        <h2>Historial operativo</h2>
                        <p>Filtra por producto, tipo de movimiento o rango de fechas.</p>
                    </div>
                    <span class="inv-mov-count">
                        <i class="fas fa-database"></i>
                        <?= number_format($total_movimientos) ?> registros
                    </span>
                </div>

                <div class="inv-mov-quick-filters" aria-label="Filtros rapidos de movimientos">
                    <a href="<?= $build_filter_url(['tipo' => '', 'origen' => 'manual']) ?>" class="inv-mov-quick <?= ($filtros['tipo'] ?? '') === '' && ($filtros['origen'] ?? '') === 'manual' ? 'is-active' : '' ?>">
                        <i class="fas fa-hand-pointer"></i>
                        Todos manuales
                    </a>
                    <a href="<?= $build_filter_url(['tipo' => 'ENTRADA', 'origen' => 'manual']) ?>" class="inv-mov-quick <?= ($filtros['tipo'] ?? '') === 'ENTRADA' && ($filtros['origen'] ?? '') === 'manual' ? 'is-active' : '' ?>">
                        <i class="fas fa-hand-pointer"></i>
                        Entradas manuales
                    </a>
                    <a href="<?= $build_filter_url(['tipo' => 'SALIDA', 'origen' => 'manual']) ?>" class="inv-mov-quick <?= ($filtros['tipo'] ?? '') === 'SALIDA' && ($filtros['origen'] ?? '') === 'manual' ? 'is-active' : '' ?>">
                        <i class="fas fa-hand-pointer"></i>
                        Salidas manuales
                    </a>
                    <a href="<?= $build_filter_url(['tipo' => 'AJUSTE', 'origen' => 'manual']) ?>" class="inv-mov-quick <?= ($filtros['tipo'] ?? '') === 'AJUSTE' && ($filtros['origen'] ?? '') === 'manual' ? 'is-active' : '' ?>">
                        <i class="fas fa-sliders-h"></i>
                        Ajustes manuales
                    </a>
                    <a href="<?= $build_filter_url(['tipo' => '', 'origen' => 'automatico']) ?>" class="inv-mov-quick <?= ($filtros['tipo'] ?? '') === '' && ($filtros['origen'] ?? '') === 'automatico' ? 'is-active' : '' ?>">
                        <i class="fas fa-robot"></i>
                        Automaticos
                    </a>
                </div>

                <form action="<?= url('/inventarios/movimientos') ?>" method="GET" class="inv-mov-filters" data-auto-filter-form>
                    <div class="inv-mov-field">
                        <label for="producto_id">Producto</label>
                        <select name="producto_id" id="producto_id" class="inv-mov-input">
                            <option value="">Todos los productos</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?= (int) $producto['id'] ?>" <?= (int) ($filtros['producto_id'] ?? 0) === (int) $producto['id'] ? 'selected' : '' ?>>
                                    <?= $inv_mov_safe($producto['nombre'] ?? 'Producto') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="inv-mov-field">
                        <label for="tipo">Tipo</label>
                        <select name="tipo" id="tipo" class="inv-mov-input">
                            <option value="">Todos</option>
                            <option value="ENTRADA" <?= ($filtros['tipo'] ?? '') === 'ENTRADA' ? 'selected' : '' ?>>Entradas</option>
                            <option value="SALIDA" <?= ($filtros['tipo'] ?? '') === 'SALIDA' ? 'selected' : '' ?>>Salidas</option>
                            <option value="AJUSTE" <?= ($filtros['tipo'] ?? '') === 'AJUSTE' ? 'selected' : '' ?>>Ajustes</option>
                        </select>
                    </div>

                    <div class="inv-mov-field">
                        <label for="origen">Origen</label>
                        <select name="origen" id="origen" class="inv-mov-input">
                            <option value="">Todos</option>
                            <option value="manual" <?= ($filtros['origen'] ?? '') === 'manual' ? 'selected' : '' ?>>Manuales</option>
                            <option value="automatico" <?= ($filtros['origen'] ?? '') === 'automatico' ? 'selected' : '' ?>>Automaticos</option>
                        </select>
                    </div>

                    <div class="inv-mov-field">
                        <label for="fecha_desde">Desde</label>
                        <input type="date" class="inv-mov-input" id="fecha_desde" name="fecha_desde" value="<?= $inv_mov_safe($filtros['fecha_desde'] ?? '') ?>">
                    </div>

                    <div class="inv-mov-field">
                        <label for="fecha_hasta">Hasta</label>
                        <input type="date" class="inv-mov-input" id="fecha_hasta" name="fecha_hasta" value="<?= $inv_mov_safe($filtros['fecha_hasta'] ?? '') ?>">
                    </div>

                    <button type="submit" class="inv-mov-btn primary">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>

                    <a href="<?= url('inventario/movimientos') ?>" class="inv-mov-btn ghost">
                        <i class="fas fa-undo"></i>
                        Limpiar
                    </a>
                </form>

                <?php if (empty($movimientos)): ?>
                    <div class="inv-mov-empty">
                        <i class="fas fa-inbox"></i>
                        <h3>No hay movimientos para mostrar</h3>
                        <p>Ajusta los filtros o registra una entrada, salida o ajuste para ver actividad en este historial.</p>
                    </div>
                <?php else: ?>
                    <div class="inv-mov-table-wrap">
                        <table class="inv-mov-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Origen</th>
                                    <th>Cantidad</th>
                                    <th>Stock anterior</th>
                                    <th>Stock nuevo</th>
                                    <th>Contexto</th>
                                    <th>Motivo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientos as $movimiento): ?>
                                    <?php
                                    $type_meta = $inv_mov_type_meta($movimiento['tipo_movimiento'] ?? '');
                                    $origin_meta = $inv_mov_origin_meta($movimiento);
                                    $stock_nuevo = $movimiento['stock_posterior'] ?? ($movimiento['stock_nuevo'] ?? 0);
                                    $reservacion_id = (int) ($movimiento['reservacion_id'] ?? 0);
                                    $origin_label = $origin_meta['class'] === 'manual'
                                        ? $type_meta['label'] . ' manual'
                                        : $origin_meta['label'];
                                    ?>
                                    <tr class="inv-mov-row <?= $origin_meta['class'] ?> <?= $type_meta['class'] ?>">
                                        <td class="inv-mov-num">
                                            <?= $inv_mov_safe($inv_mov_date($movimiento['created_at'] ?? null)) ?>
                                            <span class="inv-mov-id-tag">Mov. #<?= (int) ($movimiento['id'] ?? 0) ?></span>
                                        </td>
                                        <td>
                                            <div class="inv-mov-product">
                                                <span class="inv-mov-product-icon">
                                                    <i class="fas fa-box"></i>
                                                </span>
                                                <span>
                                                    <strong><?= $inv_mov_safe($movimiento['producto_nombre'] ?? 'Producto') ?></strong>
                                                    <small><?= $inv_mov_safe($movimiento['producto_codigo'] ?? 'N/A') ?></small>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="inv-mov-badge <?= $type_meta['class'] ?>">
                                                <i class="fas fa-<?= $type_meta['icon'] ?>"></i>
                                                <?= $inv_mov_safe($type_meta['label']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="inv-mov-origin <?= $origin_meta['class'] ?> <?= $type_meta['class'] ?>">
                                                <i class="fas fa-<?= $origin_meta['icon'] ?>"></i>
                                                <?= $inv_mov_safe($origin_label) ?>
                                            </span>
                                            <span class="inv-mov-origin-detail"><?= $inv_mov_safe($origin_meta['detail']) ?></span>
                                        </td>
                                        <td class="inv-mov-num"><?= $inv_mov_number($movimiento['cantidad'] ?? 0) ?></td>
                                        <td class="inv-mov-num"><?= $inv_mov_number($movimiento['stock_anterior'] ?? 0) ?></td>
                                        <td class="inv-mov-num"><?= $inv_mov_number($stock_nuevo) ?></td>
                                        <td>
                                            <div class="inv-mov-context">
                                                <?php if ($reservacion_id > 0): ?>
                                                    <a href="<?= url('reservaciones/ver/' . $reservacion_id) ?>" class="inv-mov-context-link">
                                                        <i class="fas fa-calendar-check"></i>
                                                        Reservacion #<?= $reservacion_id ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="inv-mov-context-chip">
                                                        <i class="fas fa-clipboard-list"></i>
                                                        Sin reservacion vinculada
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="inv-mov-motivo"><?= $inv_mov_safe($movimiento['motivo'] ?? 'Sin motivo') ?></div>
                                        </td>
                                        <td><?= $inv_mov_safe($movimiento['usuario_nombre'] ?? 'Sistema') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="inv-mov-mobile-list">
                        <?php foreach ($movimientos as $movimiento): ?>
                            <?php
                            $type_meta = $inv_mov_type_meta($movimiento['tipo_movimiento'] ?? '');
                            $origin_meta = $inv_mov_origin_meta($movimiento);
                            $stock_nuevo = $movimiento['stock_posterior'] ?? ($movimiento['stock_nuevo'] ?? 0);
                            $reservacion_id = (int) ($movimiento['reservacion_id'] ?? 0);
                            $origin_label = $origin_meta['class'] === 'manual'
                                ? $type_meta['label'] . ' manual'
                                : $origin_meta['label'];
                            ?>
                            <article class="inv-mov-card <?= $origin_meta['class'] ?> <?= $type_meta['class'] ?>">
                                <div class="inv-mov-card-top">
                                    <div class="inv-mov-card-title">
                                        <strong><?= $inv_mov_safe($movimiento['producto_nombre'] ?? 'Producto') ?></strong>
                                        <small><?= $inv_mov_safe($inv_mov_date($movimiento['created_at'] ?? null)) ?></small>
                                    </div>
                                    <span class="inv-mov-badge <?= $type_meta['class'] ?>">
                                        <i class="fas fa-<?= $type_meta['icon'] ?>"></i>
                                        <?= $inv_mov_safe($type_meta['label']) ?>
                                    </span>
                                </div>
                                <div class="inv-mov-card-grid">
                                    <div class="inv-mov-card-kv">
                                        <span>Origen</span>
                                        <strong>
                                            <span class="inv-mov-origin <?= $origin_meta['class'] ?> <?= $type_meta['class'] ?>">
                                                <i class="fas fa-<?= $origin_meta['icon'] ?>"></i>
                                                <?= $inv_mov_safe($origin_label) ?>
                                            </span>
                                        </strong>
                                    </div>
                                    <div class="inv-mov-card-kv">
                                        <span>Cantidad</span>
                                        <strong><?= $inv_mov_number($movimiento['cantidad'] ?? 0) ?></strong>
                                    </div>
                                    <div class="inv-mov-card-kv">
                                        <span>Stock anterior</span>
                                        <strong><?= $inv_mov_number($movimiento['stock_anterior'] ?? 0) ?></strong>
                                    </div>
                                    <div class="inv-mov-card-kv">
                                        <span>Stock nuevo</span>
                                        <strong><?= $inv_mov_number($stock_nuevo) ?></strong>
                                    </div>
                                    <div class="inv-mov-card-kv">
                                        <span>Codigo</span>
                                        <strong><?= $inv_mov_safe($movimiento['producto_codigo'] ?? 'N/A') ?></strong>
                                    </div>
                                    <div class="inv-mov-card-kv">
                                        <span>Usuario</span>
                                        <strong><?= $inv_mov_safe($movimiento['usuario_nombre'] ?? 'Sistema') ?></strong>
                                    </div>
                                </div>
                                <div class="inv-mov-card-context">
                                    <span class="inv-mov-origin-detail"><?= $inv_mov_safe($origin_meta['detail']) ?></span>
                                    <div class="inv-mov-context">
                                        <?php if ($reservacion_id > 0): ?>
                                            <a href="<?= url('reservaciones/ver/' . $reservacion_id) ?>" class="inv-mov-context-link">
                                                <i class="fas fa-calendar-check"></i>
                                                Reservacion #<?= $reservacion_id ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="inv-mov-context-chip">
                                                <i class="fas fa-clipboard-list"></i>
                                                Sin reservacion vinculada
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="inv-mov-card-note"><?= $inv_mov_safe($movimiento['motivo'] ?? 'Sin motivo') ?></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($total_paginas > 1): ?>
                    <nav class="inv-mov-pagination" aria-label="Navegacion de paginas">
                        <a class="inv-mov-page-link <?= $pagina_actual <= 1 ? 'disabled' : '' ?>" href="<?= $build_page_url($pagina_actual - 1) ?>">
                            Anterior
                        </a>
                        <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                            <a class="inv-mov-page-link <?= $i === $pagina_actual ? 'active' : '' ?>" href="<?= $build_page_url($i) ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <a class="inv-mov-page-link <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>" href="<?= $build_page_url($pagina_actual + 1) ?>">
                            Siguiente
                        </a>
                    </nav>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-auto-filter-form]');

    if (!form) {
        return;
    }

    const autoSubmit = function () {
        const pageInput = form.querySelector('[name="pagina"]');
        if (pageInput) {
            pageInput.remove();
        }

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.submit();
    };

    form.querySelectorAll('select, input[type="date"]').forEach(function (control) {
        control.addEventListener('change', autoSubmit);
    });
});
</script>
