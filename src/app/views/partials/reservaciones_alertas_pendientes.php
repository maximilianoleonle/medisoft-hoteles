<?php
/**
 * Alertas pendientes para el index de RESERVACIONES.
 *
 * Diseño IDÉNTICO al del index de habitaciones: mismas clases (hb-move-* / hb-alerts-*),
 * mismo CSS scopeado a .habitaciones-view y las mismas variables/valores. El bloque se
 * envuelve en <div class="habitaciones-view loaded"> para que ese CSS aplique aquí exactamente
 * igual que en habitaciones (incluida su cascada móvil ≤640 / ≤767). Única diferencia funcional:
 * el botón de check-in abre el modal de check-in de reservaciones (abrirModalCheckIn).
 *
 * Requiere en scope: $checkouts_vencidos, $checkins_pendientes, $llegadas_tardias.
 */
$ap_checkouts = isset($checkouts_vencidos) && is_array($checkouts_vencidos) ? $checkouts_vencidos : [];
$ap_checkins  = isset($checkins_pendientes) && is_array($checkins_pendientes) ? $checkins_pendientes : [];
$ap_tardias   = isset($llegadas_tardias) && is_array($llegadas_tardias) ? $llegadas_tardias : [];
if (count($ap_checkouts) + count($ap_checkins) + count($ap_tardias) <= 0) {
    return;
}
?>
<style>
/* ── Variables (idénticas a habitaciones/index) ── */
.habitaciones-view{
  --hb-primary: var(--brand-primary, #1B2746);
  --hb-secondary: var(--brand-secondary, #0F172A);
  --hb-accent: var(--brand-accent, #BD9441);
  --hb-ivory:#F5F5F7; --hb-ivory-2:#FAFAFC;
  --hb-surface:#FFFFFF; --hb-surface-warm:#F5F5F7;
  --hb-line:#E7E1D4; --hb-line-soft:#F0EBE0;
  --hb-slate-700:#3E4A66; --hb-slate-500:#6C7689; --hb-slate-400:#9AA1B2;
  --c-available:#1E9E63; --bg-available:#E7F4EC;
  --c-occupied:#C2603C;  --bg-occupied:#F8EAE1;
  --c-arriving:#5A57D2;  --bg-arriving:#ECEBFB;
  --c-cleaning:#2F77E0;  --bg-cleaning:#E6EFFC;
  --c-maint:#C2841C;     --bg-maint:#FAF0DC;
  --c-critical:#D64539;  --bg-critical:#FBE9E7;
  --serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --hb-radius:16px; --hb-radius-lg:20px;
  --hb-shadow-xs:0 1px 2px rgba(27,39,70,.05);
}

/* ── Base hb-move-* (subconjunto usado por la alerta) ── */
.habitaciones-view .hb-move-card{
    background: #fff;
    border: 1px solid var(--hb-line, #E2D9C8);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--hb-shadow-xs, 0 1px 4px rgba(18,22,34,.06));
}
.habitaciones-view .hb-move-title{
    display: flex;
    align-items: center;
    gap: 8px;
}
.habitaciones-view .hb-move-title strong{
    display: block;
    font-size: .84rem;
    font-weight: 700;
    color: var(--hb-primary, #1B2746);
}
.habitaciones-view .hb-move-title small{
    display: block;
    font-size: .70rem;
    color: var(--hb-slate-400, #94A3B8);
    font-weight: 500;
}
.habitaciones-view .hb-move-body{
    max-height: 200px;
    overflow-y: auto;
    padding: 6px 8px;
}
.habitaciones-view .hb-move-item{
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 6px;
    border-radius: 10px;
    border: 1px solid transparent;
    margin-bottom: 4px;
    background: transparent;
}
.habitaciones-view .hb-move-item:last-child{ margin-bottom: 0; }
.habitaciones-view .hb-move-item:hover{ background: var(--hb-surface-warm, #FAF8F4); }
.habitaciones-view .hb-move-item p:first-child{
    font-size: .84rem;
    font-weight: 700;
    color: var(--hb-primary, #1B2746);
    margin: 0 0 2px;
}
.habitaciones-view .hb-move-item p:last-child{
    font-size: .72rem;
    color: var(--hb-slate-400, #94A3B8);
    margin: 0;
}
.habitaciones-view .hb-move-action{
    color: var(--hb-slate-400, #94A3B8) !important;
    padding: 4px;
}
.habitaciones-view .hb-move-action:hover{ color: var(--hb-primary, #1B2746) !important; }

/* ── Alertas pendientes (idéntico a habitaciones) ── */
.habitaciones-view .hb-alerts{
    margin-bottom: 1rem;
    border-color: color-mix(in srgb, var(--hb-late, #C9322B) 32%, var(--hb-line, #E2D9C8)) !important;
    border-left: 4px solid color-mix(in srgb, var(--hb-late, #C9322B) 78%, var(--hb-line, #E2D9C8)) !important;
    background:
        radial-gradient(circle at 97% 0%, color-mix(in srgb, var(--hb-late, #C9322B) 10%, transparent), transparent 11rem),
        linear-gradient(135deg, #FFFDFC, color-mix(in srgb, var(--hb-late, #C9322B) 5%, #FFF8F5)) !important;
    box-shadow: 0 16px 32px -26px color-mix(in srgb, var(--hb-late, #C9322B) 60%, transparent) !important;
}
.habitaciones-view .hb-alerts-toggle{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    width: 100%;
    padding: 11px 14px;
    border: 0;
    background: transparent;
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    transition: background-color .16s ease;
}
.habitaciones-view .hb-alerts-toggle:hover{
    background: color-mix(in srgb, var(--hb-late, #C9322B) 4%, transparent);
}
.habitaciones-view .hb-alerts-toggle .hb-move-title strong{
    color: color-mix(in srgb, var(--hb-late, #C9322B) 46%, var(--hb-primary, #1B2746));
}
.habitaciones-view .hb-alerts-toggle .hb-move-title small{
    color: color-mix(in srgb, var(--hb-late, #C9322B) 30%, var(--hb-slate-400, #94A3B8));
}
.habitaciones-view .hb-alerts-toggle:focus-visible{
    outline: 2px solid color-mix(in srgb, var(--hb-accent, #BD9441) 72%, #fff);
    outline-offset: -2px;
    border-radius: 14px;
}
.habitaciones-view .hb-alerts.is-open .hb-alerts-toggle{
    border-bottom: 1px solid var(--hb-line, #E2D9C8);
}
.habitaciones-view .hb-alerts-mark-wrap{
    position: relative;
    flex: 0 0 auto;
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    background: var(--hb-late, #C9322B);
    color: #FFFDFB;
    font-size: .82rem;
    box-shadow: 0 10px 18px -12px color-mix(in srgb, var(--hb-late, #C9322B) 90%, transparent);
}
.habitaciones-view .hb-alerts-mark-wrap::after{
    content: "";
    position: absolute;
    top: -2px;
    right: -2px;
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background: var(--hb-accent, #BD9441);
    box-shadow: 0 0 0 2px #fff;
    animation: hbAlertsPing 2.4s ease-out infinite;
}
@keyframes hbAlertsPing{
    0%, 72%, 100% { outline: 0 solid transparent; }
    36% { outline: 5px solid color-mix(in srgb, var(--hb-accent, #BD9441) 30%, transparent); }
}
@media (prefers-reduced-motion: reduce){
    .habitaciones-view .hb-alerts-mark-wrap::after{ animation: none; }
}
.habitaciones-view .hb-alerts-sum{
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 0 0 auto;
}
.habitaciones-view .hb-alerts-chip{
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-height: 26px;
    padding: 0 10px;
    border: 1px solid transparent;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.habitaciones-view .hb-alerts-chip i{ font-size: .64rem; }
.habitaciones-view .hb-alerts-chip em{ font-style: normal; font-weight: 650; }
.habitaciones-view .hb-alerts-chip--late{
    background: #fff;
    border-color: color-mix(in srgb, var(--hb-late, #C9322B) 34%, #fff);
    color: var(--hb-late, #C9322B);
    box-shadow: 0 8px 16px -14px color-mix(in srgb, var(--hb-late, #C9322B) 80%, transparent);
}
.habitaciones-view .hb-alerts-chip--pending{
    background: #fff;
    border-color: color-mix(in srgb, var(--c-maint, #D97706) 36%, #fff);
    color: color-mix(in srgb, var(--c-maint, #D97706) 86%, #000);
    box-shadow: 0 8px 16px -14px color-mix(in srgb, var(--c-maint, #D97706) 70%, transparent);
}
.habitaciones-view .hb-alerts-chip--today{
    background: #fff;
    border-color: color-mix(in srgb, var(--c-arriving, #7C3AED) 32%, #fff);
    color: var(--c-arriving, #7C3AED);
    box-shadow: 0 8px 16px -14px color-mix(in srgb, var(--c-arriving, #7C3AED) 70%, transparent);
}
.habitaciones-view .hb-alerts-chev{
    flex: 0 0 auto;
    width: 26px;
    height: 26px;
    display: grid;
    place-items: center;
    border: 1px solid var(--hb-line, #E2D9C8);
    border-radius: 999px;
    background: #fff;
    color: var(--hb-slate-400, #94A3B8);
    font-size: .62rem;
    transition: transform .28s ease, color .16s ease;
}
.habitaciones-view .hb-alerts.is-open .hb-alerts-chev{
    transform: rotate(180deg);
    color: var(--hb-primary, #1B2746);
}
.habitaciones-view .hb-alerts-collapse{
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows .3s ease;
}
.habitaciones-view .hb-alerts.is-open .hb-alerts-collapse{
    grid-template-rows: 1fr;
}
.habitaciones-view .hb-alerts-collapse-inner{
    overflow: hidden;
    min-height: 0;
}
.habitaciones-view .hb-alerts-body{
    max-height: 264px;
}
@media (max-width: 640px){
    .habitaciones-view .hb-alerts-chip em{ display: none; }
    .habitaciones-view .hb-alerts-toggle .hb-move-title small{ display: none; }
}
.habitaciones-view .hb-alerts-kicker{
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 10px 6px 5px;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--hb-slate-400, #94A3B8);
}
.habitaciones-view .hb-alerts-kicker:first-child{ margin-top: 4px; }
.habitaciones-view .hb-alerts-kicker i{ font-size: .66rem; }
.habitaciones-view .hb-alerts-kicker--late i{ color: var(--hb-late, #C9322B); }
.habitaciones-view .hb-alerts-kicker--pending i{ color: var(--c-maint, #D97706); }
.habitaciones-view .hb-alerts-kicker--today i{ color: var(--c-arriving, #7C3AED); }
.habitaciones-view .hb-alerts-item{
    gap: 10px;
}
.habitaciones-view .hb-alerts-item[data-href]{
    cursor: pointer;
    transition: background-color .16s ease, border-color .16s ease, box-shadow .16s ease;
}
.habitaciones-view .hb-alerts-item[data-href]:hover{
    background: color-mix(in srgb, var(--hb-late, #C9322B) 4%, #fff);
    border-color: color-mix(in srgb, var(--hb-late, #C9322B) 18%, var(--hb-line, #E2D9C8));
}
.habitaciones-view .hb-alerts-item[data-href]:focus-visible{
    outline: 2px solid color-mix(in srgb, var(--hb-accent, #BD9441) 70%, transparent);
    outline-offset: -2px;
    border-radius: 12px;
}
.habitaciones-view .hb-alerts-ico{
    flex: 0 0 auto;
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    font-size: .74rem;
}
.habitaciones-view .hb-alerts-ico--late{
    background: color-mix(in srgb, var(--hb-late, #C9322B) 10%, #fff);
    color: var(--hb-late, #C9322B);
}
.habitaciones-view .hb-alerts-ico--pending{
    background: color-mix(in srgb, var(--c-maint, #D97706) 10%, #fff);
    color: var(--c-maint, #D97706);
}
.habitaciones-view .hb-alerts-ico--today{
    background: color-mix(in srgb, var(--c-arriving, #7C3AED) 10%, #fff);
    color: var(--c-arriving, #7C3AED);
}
.habitaciones-view .hb-alerts-item .hb-alerts-info{ flex: 1; min-width: 0; }
.habitaciones-view .hb-alerts-item p:first-child{
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.habitaciones-view .hb-alerts-item p:last-child b{
    font-weight: 700;
    color: var(--hb-late, #C9322B);
}
.habitaciones-view .hb-alerts-item p:last-child a{
    color: inherit;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.habitaciones-view .hb-alerts-btn{
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 32px;
    padding: 0 11px;
    border: 1px solid var(--hb-line, #E2D9C8);
    border-radius: 10px;
    background: #fff;
    color: var(--hb-primary, #1B2746);
    font-size: .74rem;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
    text-decoration: none;
    transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.habitaciones-view .hb-alerts-btn--late{
    border-color: color-mix(in srgb, var(--hb-late, #C9322B) 32%, var(--hb-line, #E2D9C8));
    color: var(--hb-late, #C9322B);
}
.habitaciones-view .hb-alerts-btn--late:hover{
    background: color-mix(in srgb, var(--hb-late, #C9322B) 8%, #fff);
}
.habitaciones-view .hb-alerts-btn--pending{
    border-color: color-mix(in srgb, var(--c-maint, #D97706) 32%, var(--hb-line, #E2D9C8));
    color: color-mix(in srgb, var(--c-maint, #D97706) 86%, #000);
}
.habitaciones-view .hb-alerts-btn--pending:hover{
    background: color-mix(in srgb, var(--c-maint, #D97706) 8%, #fff);
}
@media (max-width: 640px){
    .habitaciones-view .hb-alerts-btn span{ display: none; }
    .habitaciones-view .hb-alerts-btn{
        width: 34px;
        justify-content: center;
        padding: 0;
    }
}

/* ── Overrides móviles de base hb-move (≤640) — idénticos a habitaciones ── */
@media (max-width: 640px){
  .habitaciones-view .hb-move-card{
    border:1px solid var(--hb-line)!important;
    border-radius:18px!important;
    background:color-mix(in srgb,var(--hb-surface) 82%,var(--hb-ivory))!important;
    box-shadow:0 16px 34px -28px rgba(18,22,34,.38)!important;
    overflow:hidden;
  }
  .habitaciones-view .hb-move-body{
    max-height:210px!important;
    padding:8px!important;
  }
  .habitaciones-view .hb-move-item{
    align-items:center!important;
    gap:10px!important;
    padding:10px!important;
    border:1px solid transparent!important;
    border-radius:14px!important;
    background:var(--hb-surface-warm)!important;
  }
  .habitaciones-view .hb-move-item p:first-child{
    color:var(--hb-primary)!important;
    font-size:.78rem!important;
  }
  .habitaciones-view .hb-move-item p:last-child{
    color:var(--hb-slate-500)!important;
    font-size:.66rem!important;
    line-height:1.25!important;
  }
  .habitaciones-view .hb-move-action{
    width:30px;
    height:30px;
    display:grid!important;
    place-items:center;
    margin-left:4px!important;
    border-radius:999px;
    background:color-mix(in srgb,var(--c-arriving) 12%,var(--hb-surface))!important;
    color:var(--c-arriving)!important;
  }
}

/* ── Overrides móviles de base hb-move (≤767) — idénticos a habitaciones ── */
@media (max-width:767px){
  .habitaciones-view .hb-move-card{
    border:1px solid color-mix(in srgb,var(--hb-secondary) 10%,var(--hb-line))!important;
    border-radius:14px!important;
    overflow:hidden!important;
    background:rgba(255,255,255,.62)!important;
    box-shadow:none!important;
  }
  .habitaciones-view .hb-move-title>span{
    display:grid!important;
    gap:1px!important;
    min-width:0!important;
  }
  .habitaciones-view .hb-move-title strong{
    color:#111827!important;
    font:inherit!important;
    line-height:1!important;
  }
  .habitaciones-view .hb-move-title small{
    color:#8E837B!important;
    font-family:var(--hb-sans,'DM Sans',system-ui,sans-serif)!important;
    font-size:.65rem!important;
    font-weight:500!important;
    line-height:1.15!important;
  }
  .habitaciones-view .hb-move-body{
    max-height:none!important;
    padding:0 9px 9px!important;
    overflow:visible!important;
  }
  .habitaciones-view .hb-move-item{
    padding:8px 10px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 8%,var(--hb-line))!important;
    border-radius:10px!important;
    background:rgba(255,255,255,.72)!important;
  }
  .habitaciones-view .hb-move-item p:first-child{
    color:#111827!important;
    font-size:.82rem!important;
    font-weight:700!important;
  }
  .habitaciones-view .hb-move-item p:last-child{
    color:#756C65!important;
    font-size:.76rem!important;
    line-height:1.35!important;
  }
  .habitaciones-view .hb-move-action{
    width:30px!important;
    height:30px!important;
    display:grid!important;
    place-items:center!important;
    border-radius:999px!important;
    background:color-mix(in srgb,var(--c-arriving) 12%,#FFFFFF)!important;
    color:var(--c-arriving)!important;
    font-size:.78rem!important;
  }
}
</style>

<div class="habitaciones-view loaded">
    <div class="hb-move-card hb-alerts no-print" id="hbAlertsCard" role="region" aria-label="Alertas pendientes">
        <button type="button"
                class="hb-alerts-toggle"
                aria-expanded="false"
                aria-controls="hbAlertsCollapse"
                onclick="hbToggleAlertas()">
            <span class="hb-move-title">
                <span class="hb-alerts-mark-wrap"><i class="fas fa-exclamation-triangle"></i></span>
                <span>
                    <strong>Alertas pendientes</strong>
                    <small>Toca para revisar el detalle</small>
                </span>
            </span>
            <span class="hb-alerts-sum">
                <?php if (!empty($ap_checkouts)): ?>
                <span class="hb-alerts-chip hb-alerts-chip--late" title="Check-outs vencidos">
                    <i class="fas fa-door-open"></i><?= count($ap_checkouts) ?> <em>vencido<?= count($ap_checkouts) > 1 ? 's' : '' ?></em>
                </span>
                <?php endif; ?>
                <?php if (!empty($ap_checkins)): ?>
                <span class="hb-alerts-chip hb-alerts-chip--pending" title="Check-ins pendientes">
                    <i class="fas fa-user-clock"></i><?= count($ap_checkins) ?> <em>sin check-in</em>
                </span>
                <?php endif; ?>
                <?php if (!empty($ap_tardias)): ?>
                <span class="hb-alerts-chip hb-alerts-chip--today" title="Llegadas tardías hoy">
                    <i class="fas fa-clock"></i><?= count($ap_tardias) ?> <em>hoy</em>
                </span>
                <?php endif; ?>
                <span class="hb-alerts-chev"><i class="fas fa-chevron-down"></i></span>
            </span>
        </button>

        <div class="hb-alerts-collapse" id="hbAlertsCollapse">
        <div class="hb-alerts-collapse-inner">
        <div class="hb-move-body hb-alerts-body">

            <?php if (!empty($ap_checkouts)): ?>
            <p class="hb-alerts-kicker hb-alerts-kicker--late">
                <i class="fas fa-door-open"></i>
                Check-outs vencidos · <?= count($ap_checkouts) ?>
            </p>
            <?php foreach ($ap_checkouts as $checkout): ?>
            <div class="hb-move-item hb-alerts-item"
                 data-href="<?= htmlspecialchars(url('reservaciones/ver/' . (int)$checkout['id']), ENT_QUOTES, 'UTF-8') ?>"
                 role="link"
                 tabindex="0"
                 title="Abrir reservación"
                 aria-label="Abrir reservacion de <?= htmlspecialchars($checkout['nombre_completo'] ?? 'huesped', ENT_QUOTES, 'UTF-8') ?>">
                <span class="hb-alerts-ico hb-alerts-ico--late"><i class="fas fa-sign-out-alt"></i></span>
                <div class="hb-alerts-info">
                    <p><?= htmlspecialchars($checkout['nombre_completo'] ?? 'Sin nombre') ?></p>
                    <p>
                        Hab. <?= htmlspecialchars($checkout['habitaciones'] ?? 'S/N') ?>
                        · Salida <?= !empty($checkout['fecha_salida']) ? date('d/m/Y', strtotime($checkout['fecha_salida'])) : 'Sin fecha' ?>
                        · <b><?= (int)($checkout['dias_retraso'] ?? 0) ?> día<?= (int)($checkout['dias_retraso'] ?? 0) === 1 ? '' : 's' ?> de retraso</b>
                    </p>
                </div>
                <button type="button"
                        onclick="confirmarCheckOut(<?= (int)$checkout['id'] ?>)"
                        title="Realizar check-out de esta reservación"
                        class="hb-alerts-btn hb-alerts-btn--late">
                    <i class="fas fa-sign-out-alt"></i><span>Check-out</span>
                </button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($ap_checkins)): ?>
            <p class="hb-alerts-kicker hb-alerts-kicker--pending">
                <i class="fas fa-user-clock"></i>
                Check-ins pendientes · <?= count($ap_checkins) ?>
            </p>
            <?php foreach ($ap_checkins as $checkin): ?>
            <div class="hb-move-item hb-alerts-item"
                 data-href="<?= htmlspecialchars(url('reservaciones/ver/' . (int)$checkin['id']), ENT_QUOTES, 'UTF-8') ?>"
                 role="link"
                 tabindex="0"
                 title="Abrir reservación"
                 aria-label="Abrir reservacion de <?= htmlspecialchars($checkin['nombre_completo'] ?? 'huesped', ENT_QUOTES, 'UTF-8') ?>">
                <span class="hb-alerts-ico hb-alerts-ico--pending"><i class="fas fa-user-clock"></i></span>
                <div class="hb-alerts-info">
                    <p><?= htmlspecialchars($checkin['nombre_completo'] ?? 'Sin nombre') ?></p>
                    <p>
                        Hab. <?= htmlspecialchars($checkin['habitaciones'] ?? 'S/N') ?>
                        · Llegada <?= !empty($checkin['fecha_entrada']) ? date('d/m/Y', strtotime($checkin['fecha_entrada'])) : 'Sin fecha' ?>
                        · <?= (int)($checkin['dias_retraso'] ?? 0) ?> día<?= (int)($checkin['dias_retraso'] ?? 0) === 1 ? '' : 's' ?> sin check-in
                        <?php if (!empty($checkin['telefono'])): ?>
                            · <a href="tel:<?= htmlspecialchars($checkin['telefono']) ?>"><?= htmlspecialchars($checkin['telefono']) ?></a>
                        <?php endif; ?>
                    </p>
                </div>
                <button type="button"
                        onclick="abrirModalCheckIn(<?= (int)$checkin['id'] ?>, <?= htmlspecialchars(json_encode((float)($checkin['precio_total'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>)"
                        title="Abrir check-in de esta reservación"
                        class="hb-alerts-btn hb-alerts-btn--pending">
                    <i class="fas fa-sign-in-alt"></i><span>Check-in</span>
                </button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($ap_tardias)): ?>
            <p class="hb-alerts-kicker hb-alerts-kicker--today">
                <i class="fas fa-clock"></i>
                Llegadas tardías hoy · <?= count($ap_tardias) ?>
            </p>
            <?php foreach ($ap_tardias as $tardio): ?>
            <div class="hb-move-item hb-alerts-item"
                 data-href="<?= htmlspecialchars(url('reservaciones/ver/' . (int)$tardio['id']), ENT_QUOTES, 'UTF-8') ?>"
                 role="link"
                 tabindex="0"
                 title="Abrir reservación"
                 aria-label="Abrir reservacion de <?= htmlspecialchars($tardio['nombre_completo'] ?? 'huesped', ENT_QUOTES, 'UTF-8') ?>">
                <span class="hb-alerts-ico hb-alerts-ico--today"><i class="fas fa-clock"></i></span>
                <div class="hb-alerts-info">
                    <p><?= htmlspecialchars($tardio['nombre_completo'] ?? 'Sin nombre') ?></p>
                    <p>
                        Hab. <?= htmlspecialchars($tardio['habitaciones'] ?? 'S/N') ?>
                        <?php if (!empty($tardio['hora_llegada_estimada'])): ?>
                            · Hora estimada <?= substr($tardio['hora_llegada_estimada'], 0, 5) ?>
                        <?php endif; ?>
                        <?php if (!empty($tardio['telefono'])): ?>
                            · <a href="tel:<?= htmlspecialchars($tardio['telefono']) ?>"><?= htmlspecialchars($tardio['telefono']) ?></a>
                        <?php endif; ?>
                    </p>
                </div>
                <a href="<?= url('reservaciones/ver/' . (int)$tardio['id']) ?>"
                   title="Ver detalle de la reservación"
                   class="hb-move-action hb-alerts-go" aria-label="Ver reservación">
                    <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

        </div>
        </div>
        </div>
    </div>
</div>

<script>
function hbToggleAlertas() {
    const card = document.getElementById('hbAlertsCard');
    if (!card) return;
    const btn = card.querySelector('.hb-alerts-toggle');
    const open = card.classList.toggle('is-open');
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}

(function () {
    const card = document.getElementById('hbAlertsCard');
    if (!card) {
        return;
    }

    function alertRowFromTarget(target) {
        if (!(target instanceof Element)) {
            return null;
        }

        if (target.closest('a, button, input, textarea, select, label')) {
            return null;
        }

        return target.closest('.hb-alerts-item[data-href]');
    }

    function openAlertRow(row, event) {
        const href = row.getAttribute('data-href');
        if (!href) {
            return;
        }

        if (event && (event.ctrlKey || event.metaKey || event.button === 1)) {
            window.open(href, '_blank', 'noopener');
            return;
        }

        window.location.href = href;
    }

    card.addEventListener('click', function (event) {
        const row = alertRowFromTarget(event.target);
        if (!row) {
            return;
        }

        openAlertRow(row, event);
    });

    card.addEventListener('auxclick', function (event) {
        if (event.button !== 1) {
            return;
        }

        const row = alertRowFromTarget(event.target);
        if (!row) {
            return;
        }

        event.preventDefault();
        openAlertRow(row, event);
    });

    card.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        const row = event.target.closest && event.target.closest('.hb-alerts-item[data-href]');
        if (!row || row !== event.target) {
            return;
        }

        event.preventDefault();
        openAlertRow(row, event);
    });
})();
</script>
