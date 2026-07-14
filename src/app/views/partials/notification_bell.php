<?php
/**
 * Campanita de notificaciones para la barra global de vistas (view_topbar).
 *
 * Réplica del dropdown del dashboard (mismo panel, swipe-para-archivar, mismo
 * conteo) pero:
 *   - El botón se adapta al chip CLARO de la topbar (hermano de la flecha de
 *     regreso), no al vidrio del hero oscuro del dashboard.
 *   - Obtiene sus datos de forma AUTÓNOMA (modelo Notificacion), sin depender
 *     de variables que solo prepara el DashboardController.
 *
 * Se incluye desde partials/view_topbar.php DENTRO de <nav class="ms-vtb">, en
 * las rutas de su lista blanca. El panel se mueve a <body> al abrir (posición
 * fija), así que su CSS usa tokens globales con respaldo (--brand-* / --dash-*).
 */

if (!function_exists('has_hotel_context') || !has_hotel_context()) {
    return;
}
if (function_exists('hotel_menu_module_enabled') && !hotel_menu_module_enabled('notificaciones')) {
    return;
}

// ── Datos (autónomos, tolerantes a fallos) ──
$nbPendientes = 0;
$nbRecientes = [];
try {
    if (class_exists('Notificacion')) {
        $nbModelo = new Notificacion();
        if ($nbModelo->tablaDisponible()) {
            $nbHotelId = function_exists('current_hotel_id') ? (int) current_hotel_id() : 0;
            $nbRol = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
            $nbUsuarioId = function_exists('user_id') ? user_id() : null;
            if ($nbHotelId > 0) {
                $nbResumen = $nbModelo->resumenPorHotel($nbHotelId, $nbRol, $nbUsuarioId);
                $nbPendientes = (int) ($nbResumen['nuevas'] ?? 0);
                $nbRecientes = $nbModelo->listarPorHotel($nbHotelId, [
                    'estado' => 'nueva',
                    'rol_usuario' => $nbRol,
                    'usuario_id' => $nbUsuarioId,
                ], 8);
            }
        }
    }
} catch (Throwable $e) {
    $nbPendientes = 0;
    $nbRecientes = [];
}

// ── Helpers locales (guardados: no chocan con los dashboard_*) ──
if (!function_exists('nb_safe')) {
    function nb_safe($value, $fallback = '-') {
        $text = trim((string) ($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('nb_notif_icon')) {
    function nb_notif_icon($modulo) {
        $icons = [
            'caja' => 'fa-wallet',
            'habitaciones' => 'fa-bed',
            'facturacion' => 'fa-file-invoice',
            'inventario' => 'fa-boxes-stacked',
            'reservaciones' => 'fa-calendar-check',
        ];
        return $icons[(string) ($modulo ?? '')] ?? 'fa-bell';
    }
}
if (!function_exists('nb_notif_label')) {
    function nb_notif_label($modulo) {
        $labels = [
            'caja' => 'Caja',
            'habitaciones' => 'Habitaciones',
            'facturacion' => 'Facturacion',
            'inventario' => 'Inventario',
            'reservaciones' => 'Reservaciones',
            'sistema' => 'Sistema',
        ];
        $key = (string) ($modulo ?? '');
        return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
if (!function_exists('nb_fecha')) {
    function nb_fecha($value, $format = 'd/m H:i') {
        if (empty($value)) {
            return '-';
        }
        $ts = strtotime((string) $value);
        return $ts ? date($format, $ts) : '-';
    }
}
?>
<div class="ms-vtb-bell-shell" data-notification-quick>
    <button type="button"
            class="ms-vtb-bell <?= $nbPendientes > 0 ? 'has-notifications' : '' ?>"
            aria-label="Ver pendientes de notificaciones"
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="vtbNotificationPanel"
            data-notification-toggle>
        <i class="fas fa-bell" aria-hidden="true"></i>
        <?php if ($nbPendientes > 0): ?>
            <span class="notification-dot"></span>
            <span class="notification-count"><?= $nbPendientes > 99 ? '99+' : (int) $nbPendientes ?></span>
        <?php endif; ?>
    </button>

    <div class="notification-quick-panel"
         id="vtbNotificationPanel"
         data-notification-panel
         hidden>
        <div class="notification-quick-head">
            <span>Pendientes</span>
            <strong data-notification-count><?= (int) $nbPendientes ?></strong>
        </div>

        <div class="notification-quick-list" data-notification-list>
            <?php if (empty($nbRecientes)): ?>
                <div class="notification-quick-empty">Sin pendientes operativos por ahora.</div>
            <?php else: ?>
                <?php foreach (array_slice($nbRecientes, 0, 8) as $nbItem): ?>
                    <?php
                    $nbModulo = (string) ($nbItem['modulo'] ?? 'sistema');
                    $nbId = (int) ($nbItem['id'] ?? 0);
                    $nbUrl = trim((string) ($nbItem['url'] ?? ''));
                    $nbHref = ($nbUrl !== '' && $nbId > 0)
                        ? url('notificaciones/' . $nbId . '/abrir')
                        : url('notificaciones');
                    $nbAuto = strpos((string) ($nbItem['tipo'] ?? ''), 'regla_') === 0;
                    $nbSev = strtolower((string) ($nbItem['severidad'] ?? ''));
                    $nbSevClass = in_array($nbSev, ['critica', 'alta'], true)
                        ? ' nq-crit'
                        : ($nbSev === 'media' ? ' nq-warn' : '');
                    ?>
                    <div class="nq-swipe" data-nq-swipe>
                        <div class="nq-swipe-bg" aria-hidden="true">
                            <i class="fas fa-check" aria-hidden="true"></i> Archivar
                        </div>
                        <a class="notification-quick-row<?= $nbSevClass ?>"
                           href="<?= htmlspecialchars($nbHref, ENT_QUOTES, 'UTF-8') ?>"
                           <?= $nbId > 0 ? 'data-archive-url="' . htmlspecialchars(url('notificaciones/' . $nbId . '/descartar'), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                            <span class="notification-quick-icon">
                                <i class="fas <?= nb_safe(nb_notif_icon($nbModulo), 'fa-bell') ?>" aria-hidden="true"></i>
                            </span>
                            <span class="notification-quick-body">
                                <span class="notification-quick-title"><?= nb_safe($nbItem['titulo'] ?? 'Notificacion') ?></span>
                                <span class="notification-quick-meta">
                                    <?= nb_safe(nb_notif_label($nbModulo)) ?> · <?= nb_safe(nb_fecha($nbItem['created_at'] ?? null)) ?><?= $nbAuto ? ' · Automática' : ' · Manual' ?>
                                </span>
                            </span>
                            <span class="notification-quick-go">
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($nbRecientes)): ?>
        <div class="notification-quick-hint" data-notification-hint>
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Desliza una notificación a la izquierda para archivar
        </div>
        <?php endif; ?>
    </div>
</div>
<style>
/* ── Campanita en la barra global (partials/notification_bell.php) ──
   Botón: mismo chip claro que la flecha de regreso (.ms-vtb-back).
   Panel: réplica del dropdown del dashboard; se mueve a <body>, así que su
   CSS usa tokens con respaldo (--dash-* en oscuro los define dark-theme.css;
   el fallback cubre el modo claro fuera del dashboard). */
.ms-vtb-bell-shell {
    flex: 0 0 auto;
    margin-left: auto;   /* empuja la campana al extremo derecho de la barra */
    position: relative;
    display: inline-flex;
}
.ms-vtb-bell {
    position: relative;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    border: 1px solid var(--brand-border, #E4DDCE);
    background: var(--brand-surface, #FFFFFF);
    color: var(--brand-primary, #1B2746);
    box-shadow: 0 1px 2px rgba(36, 48, 74, .06);
    cursor: pointer;
    appearance: none;
    -webkit-tap-highlight-color: transparent;
    transition: transform .16s ease, background .16s ease;
}
.ms-vtb-bell i { font-size: 15px; }
.ms-vtb-bell:hover { background: var(--brand-soft, #F6F2EA); }
.ms-vtb-bell:active { transform: scale(.92); }
.ms-vtb-bell:focus-visible {
    outline: 2px solid var(--brand-primary, #1B2746);
    outline-offset: 2px;
}
html[data-theme="dark"] .ms-vtb-bell { box-shadow: none; }
/* Cupertino: chip circular de vidrio, igual que la flecha Apple. */
html[data-tema="cupertino"] .ms-vtb-bell {
    border-radius: 999px;
    border-color: rgba(0, 0, 0, .06);
    box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
}
html[data-theme="dark"][data-tema="cupertino"] .ms-vtb-bell {
    border-color: rgba(255, 255, 255, .12);
}

/* Insignias sobre el chip (contadas/actualizadas por el JW del panel) */
.ms-vtb-bell .notification-dot {
    position: absolute;
    top: 7px;
    right: 8px;
    width: 7px;
    height: 7px;
    border-radius: 99px;
    background: var(--dash-critical, #D64539);
    border: 1px solid var(--brand-surface, #fff);
    animation: nbPulseDot 2.2s ease-in-out infinite;
}
.ms-vtb-bell .notification-count {
    position: absolute;
    top: -7px;
    right: -7px;
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    border-radius: 999px;
    background: var(--dash-critical, #D64539);
    color: #fff;
    border: 2px solid var(--brand-surface, #fff);
    font-size: 10px;
    font-weight: 900;
    line-height: 1;
    box-shadow: 0 8px 18px rgba(136, 42, 42, .28);
}
@keyframes nbPulseDot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50%      { transform: scale(1.35); opacity: .55; }
}
@media (prefers-reduced-motion: reduce) {
    .ms-vtb-bell .notification-dot { animation: none; }
}
/* Móvil: iguala el chip compacto de la flecha de regreso */
@media (max-width: 768px) {
    .ms-vtb-bell { width: 34px; height: 34px; border-radius: 11px; }
    .ms-vtb-bell i { font-size: 14px; }
    .ms-vtb-bell .notification-dot { top: 6px; right: 7px; }
    html[data-tema="cupertino"] .ms-vtb-bell { border-radius: 999px; }
}

/* ── Dropdown (réplica del dashboard) ─────────────────────────────── */
.notification-quick-panel {
    position: fixed;
    z-index: 1200;
    width: min(372px, calc(100vw - 24px));
    border: 1px solid var(--dash-line, #E4DDCE);
    border-radius: 20px;
    background: var(--dash-surface, #fff);
    color: var(--dash-ink, #1B2746);
    box-shadow: var(--dash-shadow-lg, 0 18px 48px rgba(27, 39, 70, .12));
    overflow: hidden;
    animation: nqDrop .24s cubic-bezier(.22, 1, .36, 1);
}
@keyframes nqDrop {
    from { opacity: 0; transform: translateY(-10px) scale(.98); }
    to   { opacity: 1; transform: none; }
}
.notification-quick-panel[hidden] { display: none; }
.notification-quick-panel.nq-closing {
    animation: nqLift .18s cubic-bezier(.4, 0, 1, 1) forwards;
    pointer-events: none;
}
@keyframes nqLift {
    from { opacity: 1; transform: none; }
    to   { opacity: 0; transform: translateY(-8px) scale(.98); }
}
.notification-quick-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px 13px;
}
.notification-quick-head span {
    display: block;
    color: #939BAD;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
}
.notification-quick-head strong {
    color: var(--dash-navy, #1B2746);
    font-family: var(--dash-serif, 'Cormorant Garamond', Georgia, serif);
    font-size: 22px;
    font-weight: 600;
    line-height: 1;
}
.notification-quick-list {
    max-height: min(326px, 52vh);
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
}
.notification-quick-list::-webkit-scrollbar { width: 6px; }
.notification-quick-list::-webkit-scrollbar-thumb { background: #E2D9C8; border-radius: 99px; }
.nq-swipe {
    position: relative;
    border-top: 1px solid var(--dash-line-soft, #F0ECE2);
    overflow: hidden;
}
.nq-swipe.gone {
    transition: height .32s ease, opacity .2s ease;
    opacity: 0;
    border-top: 0;
}
.nq-swipe-bg {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding-right: 22px;
    background: linear-gradient(90deg, #2BA76A, #1E9E63);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
}
.notification-quick-row {
    position: relative;
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 20px;
    background: var(--dash-surface, #fff);
    color: inherit;
    text-decoration: none;
    transition: background .14s ease;
    touch-action: pan-y;
    user-select: none;
    -webkit-user-drag: none;
}
.notification-quick-row.dragging { transition: none; }
.notification-quick-row.settle { transition: transform .3s cubic-bezier(.22, 1, .36, 1); }
.notification-quick-row:hover,
.notification-quick-row:focus-visible {
    background: var(--dash-surface-warm, #FEFCF7);
    outline: none;
}
.notification-quick-icon {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    flex: none;
    color: #6C7689;
    background: var(--dash-ivory-2, #FBF8F2);
    font-size: 15px;
    pointer-events: none;
}
.nq-crit .notification-quick-icon { background: #FBE9E7; color: #D64539; }
.nq-warn .notification-quick-icon { background: #FAF0DC; color: #C2841C; }
.notification-quick-body { min-width: 0; flex: 1; pointer-events: none; }
.notification-quick-title {
    display: block;
    color: var(--dash-navy, #1B2746);
    font-size: 14px;
    font-weight: 700;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.notification-quick-meta {
    display: block;
    margin-top: 3px;
    color: #939BAD;
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.notification-quick-go {
    margin-left: auto;
    color: #B7BDCB;
    font-size: 13px;
    flex: none;
    pointer-events: none;
}
.notification-quick-empty {
    padding: 26px 20px;
    color: #939BAD;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
}
.notification-quick-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    font-size: 11px;
    font-weight: 600;
    color: #939BAD;
    padding: 8px;
    background: var(--dash-surface-warm, #FEFCF7);
    border-top: 1px solid var(--dash-line-soft, #F0ECE2);
}
@media (prefers-reduced-motion: reduce) {
    .notification-quick-panel,
    .notification-quick-panel.nq-closing { animation: none; }
    .notification-quick-row.settle,
    .nq-swipe.gone { transition: none; }
}
</style>
<script>
(function () {
    var root = document.querySelector('.ms-vtb-bell-shell[data-notification-quick]');
    if (!root) { return; }

    var button = root.querySelector('[data-notification-toggle]');
    var panel = root.querySelector('[data-notification-panel]');
    if (!button || !panel) { return; }

    // El panel vive en <body> (posición fija): así ningún contenedor de la
    // vista lo recorta ni le impone contexto de apilamiento.
    document.body.appendChild(panel);
    if (window.PWA && typeof window.PWA.initPushControls === 'function') {
        window.PWA.initPushControls();
    }

    function placePanel() {
        var rect = button.getBoundingClientRect();
        var width = Math.min(372, Math.max(280, window.innerWidth - 24));
        var left = Math.min(window.innerWidth - width - 12, Math.max(12, rect.right - width));
        var top = Math.min(window.innerHeight - 12, Math.max(12, rect.bottom + 10));
        panel.style.width = width + 'px';
        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
    }

    var hoverTimer = null;
    var animTimer = null;

    function clearHoverTimer() {
        if (hoverTimer) { clearTimeout(hoverTimer); hoverTimer = null; }
    }
    function openPanel() {
        clearHoverTimer();
        if (animTimer) { clearTimeout(animTimer); animTimer = null; }
        panel.classList.remove('nq-closing');
        panel.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        placePanel();
    }
    function closePanel() {
        clearHoverTimer();
        if (panel.hidden || panel.classList.contains('nq-closing')) { return; }
        panel.classList.add('nq-closing');
        button.setAttribute('aria-expanded', 'false');
        animTimer = setTimeout(function () {
            panel.hidden = true;
            panel.classList.remove('nq-closing');
            animTimer = null;
        }, 180);
    }

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        if (panel.hidden || panel.classList.contains('nq-closing')) { openPanel(); return; }
        closePanel();
    });

    if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        var scheduleClose = function () {
            clearHoverTimer();
            hoverTimer = setTimeout(closePanel, 220);
        };
        [root, panel].forEach(function (el) {
            el.addEventListener('mouseenter', openPanel);
            el.addEventListener('mouseleave', scheduleClose);
        });
    }

    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) { return; }
        if (!panel.hidden && !root.contains(event.target) && !panel.contains(event.target)) {
            closePanel();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) { closePanel(); button.focus(); }
    });
    window.addEventListener('resize', function () { if (!panel.hidden) { placePanel(); } });
    window.addEventListener('scroll', function () { if (!panel.hidden) { placePanel(); } }, true);

    // ── Swipe-para-archivar (POST real a /notificaciones/{id}/descartar) ──
    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var headCount = panel.querySelector('[data-notification-count]');
    var list = panel.querySelector('[data-notification-list]');
    var THRESHOLD = 96;
    var pendientes = headCount ? (parseInt(headCount.textContent, 10) || 0) : 0;

    function renderCounts() {
        if (headCount) { headCount.textContent = pendientes; }
        var badge = button.querySelector('.notification-count');
        var dot = button.querySelector('.notification-dot');
        if (pendientes <= 0) {
            if (badge) { badge.remove(); }
            if (dot) { dot.remove(); }
            button.classList.remove('has-notifications');
        } else if (badge) {
            badge.textContent = pendientes > 99 ? '99+' : String(pendientes);
        }
    }
    function onArchived(sw) {
        sw.classList.add('gone');
        sw.style.height = '0px';
        pendientes = Math.max(0, pendientes - 1);
        renderCounts();
        setTimeout(function () {
            sw.remove();
            if (list && !list.querySelector('[data-nq-swipe]')) {
                list.innerHTML = '<div class="notification-quick-empty">Sin pendientes operativos por ahora.</div>';
                var hint = panel.querySelector('[data-notification-hint]');
                if (hint) { hint.remove(); }
            }
        }, 340);
    }
    function archiveRow(sw, row) {
        row.classList.add('settle');
        row.style.transform = 'translateX(-110%)';
        sw.style.height = sw.offsetHeight + 'px';
        var body = new URLSearchParams();
        body.set('csrf_token', csrfToken);
        fetch(row.dataset.archiveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (res) {
            if (!res.ok) { throw new Error('HTTP ' + res.status); }
            onArchived(sw);
        }).catch(function () {
            sw.style.height = '';
            row.style.transform = 'translateX(0)';
            if (typeof window.msToast === 'function') {
                window.msToast('No se pudo archivar la notificación.', 'error');
            }
        });
    }

    if (list) {
        list.querySelectorAll('[data-nq-swipe]').forEach(function (sw) {
            var row = sw.querySelector('.notification-quick-row');
            if (!row || !row.dataset.archiveUrl) { return; }
            var startX = 0, startY = 0, dx = 0;
            var active = false, decided = false, horiz = false;

            row.addEventListener('pointerdown', function (e) {
                if (e.pointerType === 'mouse' && e.button !== 0) { return; }
                startX = e.clientX; startY = e.clientY;
                dx = 0; active = true; decided = false; horiz = false;
                row.classList.remove('settle');
            });
            row.addEventListener('pointermove', function (e) {
                if (!active) { return; }
                var mx = e.clientX - startX;
                var my = e.clientY - startY;
                if (!decided && (Math.abs(mx) > 6 || Math.abs(my) > 6)) {
                    decided = true;
                    horiz = Math.abs(mx) > Math.abs(my);
                    if (horiz) {
                        row.classList.add('dragging');
                        try { row.setPointerCapture(e.pointerId); } catch (err) {}
                    }
                }
                if (!horiz) { return; }
                if (e.cancelable) { e.preventDefault(); }
                dx = Math.min(0, mx);
                row.style.transform = 'translateX(' + dx + 'px)';
            });
            function finishDrag() {
                if (!active) { return; }
                active = false;
                row.classList.remove('dragging');
                if (horiz) {
                    row.dataset.dragged = '1';
                    setTimeout(function () { delete row.dataset.dragged; }, 0);
                    if (dx < -THRESHOLD) {
                        archiveRow(sw, row);
                    } else {
                        row.classList.add('settle');
                        row.style.transform = 'translateX(0)';
                    }
                }
            }
            row.addEventListener('pointerup', finishDrag);
            row.addEventListener('pointercancel', finishDrag);
            row.addEventListener('click', function (e) {
                if (row.dataset.dragged === '1') { e.preventDefault(); }
            });
        });
    }
})();
</script>
