<?php
/**
 * Bandeja de peticiones de huespedes (QR en la habitacion, bloque reputacion).
 * Lo que piden desde el QR de su cuarto: toallas, amenidades, limpieza, algo
 * que no funciona. Tambien llega a la campana y por push al rol que toca.
 */
$vista = $vista ?? 'abiertas';
$peticiones = $peticiones ?? [];
$resumen = $resumen ?? ['nuevas' => 0, 'en_atencion' => 0, 'resueltas_hoy' => 0, 'minutos_respuesta_hoy' => null];
$tipos = $tipos ?? EstanciaService::TIPOS;
$hayQr = !empty($hayQr);
$puedeAtender = !empty($puedeAtender);
$puedeConfigurar = !empty($puedeConfigurar);
$puedeVerHabitacion = !empty($puedeVerHabitacion);

$ptSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$ptHace = static function ($minutos) {
    $minutos = max(0, (int) $minutos);
    if ($minutos < 1) {
        return 'Hace un momento';
    }
    if ($minutos < 60) {
        return 'Hace ' . $minutos . ' min';
    }
    $horas = intdiv($minutos, 60);
    if ($horas < 24) {
        return 'Hace ' . $horas . ' h' . ($minutos % 60 ? ' ' . ($minutos % 60) . ' min' : '');
    }
    $dias = intdiv($horas, 24);
    return 'Hace ' . $dias . ($dias === 1 ? ' día' : ' días');
};

$ptHora = static function ($fecha) {
    $ts = strtotime((string) $fecha);
    if (!$ts) {
        return '';
    }
    $hora = EstanciaService::horaHumana(date('H:i', $ts));
    return date('Y-m-d', $ts) === date('Y-m-d') ? 'hoy ' . $hora : date('d/m', $ts) . ' ' . $hora;
};

$ptEstados = [
    'nueva' => ['Nueva', 'is-nueva', 'fa-inbox'],
    'en_atencion' => ['En atención', 'is-atencion', 'fa-person-walking'],
    'resuelta' => ['Resuelta', 'is-resuelta', 'fa-circle-check'],
    'descartada' => ['Descartada', 'is-descartada', 'fa-ban'],
];

$abiertas = (int) $resumen['nuevas'] + (int) $resumen['en_atencion'];
$minutos = $resumen['minutos_respuesta_hoy'];
?>
<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.pt {
    --pt-brand: var(--brand-primary, #1B2746);
    --pt-gold: var(--brand-accent, #BD9441);
    --pt-gold-soft: color-mix(in srgb, var(--pt-gold) 15%, #FFFFFF);
    --pt-gold-line: color-mix(in srgb, var(--pt-gold) 42%, #E4D4B0);
    --pt-gold-ink: color-mix(in srgb, var(--pt-gold) 58%, var(--pt-brand));
    --pt-surface: #FFFFFF;
    --pt-surface-2: #FAFAFC;
    --pt-border: color-mix(in srgb, var(--pt-brand) 6%, #E9E1D6);
    --pt-ring: color-mix(in srgb, var(--pt-gold) 32%, transparent);
    --pt-text: color-mix(in srgb, var(--pt-brand) 46%, #707B8C);
    --pt-muted: #7F899A;
    --pt-heading: color-mix(in srgb, var(--pt-brand) 66%, #566172);
    --pt-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --pt-success: #1E7F4F;
    --pt-success-bg: #E7F4EC;
    --pt-warning: #9A6410;
    --pt-warning-bg: #FAF0DC;
    --pt-danger: #B4392B;
    --pt-danger-bg: #F8EAE5;
    --pt-info: #245B9B;
    --pt-info-bg: #E6EFFC;
    --pt-ease: cubic-bezier(.22, 1, .36, 1);
    width: 100%;
    min-height: 100%;
    padding: 18px 16px 40px;
    color: var(--pt-text);
    font-family: var(--pt-sans);
    font-size: .92rem;
}
html[data-theme="dark"] .pt {
    --pt-surface: #1C1C1E;
    --pt-surface-2: #232326;
    --pt-border: rgba(255, 255, 255, 0.09);
    --pt-text: #D1D1D6;
    --pt-muted: #98989D;
    --pt-heading: #F5F5F7;
    --pt-gold-soft: color-mix(in srgb, var(--pt-gold) 18%, #1C1C1E);
    --pt-gold-line: color-mix(in srgb, var(--pt-gold) 40%, #38383A);
    --pt-gold-ink: color-mix(in srgb, var(--pt-gold) 78%, #F5F5F7);
    --pt-success: #6FD19E;
    --pt-success-bg: #16301F;
    --pt-warning: #F2C063;
    --pt-warning-bg: #33270D;
    --pt-danger: #F59A91;
    --pt-danger-bg: #3A1714;
    --pt-info: #8DB8F0;
    --pt-info-bg: #16243A;
}
.pt * { box-sizing: border-box; }
.pt-shell { display: grid; gap: 14px; width: 100%; max-width: 1100px; min-width: 0; margin: 0 auto; }
.pt-shell > * { min-width: 0; }

.pt-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 16px 28px; padding: 2px 0 6px; }
.pt-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; max-width: min(100%, 780px); }
.pt-lockup > div:last-child { min-width: 0; }
.pt-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--pt-gold), var(--pt-brand) 54%, color-mix(in srgb, var(--pt-brand) 68%, var(--pt-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--pt-brand) 72%, transparent);
}
.pt-kicker { margin: 0 0 2px; color: var(--pt-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.pt-title { margin: 0; color: var(--pt-heading); font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 650; line-height: 1.02; overflow-wrap: anywhere; }
.pt-subtitle { max-width: 50rem; margin: 9px 0 0; color: var(--pt-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }
.pt-hero-side { display: grid; gap: 8px; justify-items: end; }
.pt-link {
    display: inline-flex; align-items: center; gap: 7px; min-height: 40px; padding: 0 14px; border-radius: 999px;
    border: 1px solid var(--pt-border); background: var(--pt-surface); color: var(--pt-heading);
    font-size: .82rem; font-weight: 650; text-decoration: none; white-space: nowrap;
    transition: transform .16s var(--pt-ease), box-shadow .16s var(--pt-ease), color .16s ease;
}
.pt-link:hover { color: var(--pt-gold-ink); transform: translateY(-1px); box-shadow: 0 10px 18px -14px rgba(27,39,70,.35); }
.pt-link:focus-visible, .pt-btn:focus-visible, .pt-tab:focus-visible { outline: 3px solid var(--pt-ring); outline-offset: 2px; }


.pt-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.pt-kpi { background: var(--pt-surface); border: 1px solid var(--pt-border); border-radius: 14px; padding: 12px 14px; }
.pt-kpi .nombre { color: var(--pt-muted); font-size: .7rem; font-weight: 650; letter-spacing: .06em; text-transform: uppercase; }
.pt-kpi .valor { margin-top: 4px; color: var(--pt-heading); font-size: 1.6rem; font-weight: 650; line-height: 1.05; }
.pt-kpi .valor small { color: var(--pt-muted); font-size: .78rem; font-weight: 600; }
.pt-kpi.is-live .valor { color: var(--pt-gold-ink); }

.pt-tabs { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
.pt-tab {
    display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 14px; border-radius: 999px;
    border: 1px solid var(--pt-border); background: var(--pt-surface); color: var(--pt-text);
    font-weight: 650; font-size: .85rem; text-decoration: none;
}
.pt-tab[aria-current="page"] { background: var(--pt-brand); border-color: var(--pt-brand); color: #fff; }
html[data-theme="dark"] .pt-tab[aria-current="page"] { background: var(--pt-gold-soft); border-color: var(--pt-gold-line); color: var(--pt-gold-ink); }
.pt-tab .num { font-variant-numeric: tabular-nums; opacity: .85; }
.pt-refresh { margin-left: auto; color: var(--pt-muted); font-size: .8rem; display: inline-flex; align-items: center; gap: 8px; }
.pt-refresh button { border: 0; background: none; color: var(--pt-gold-ink); font: inherit; font-weight: 700; cursor: pointer; min-height: 40px; padding: 0 6px; }

.pt-lista { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
.pt-item {
    display: grid; grid-template-columns: 44px minmax(0, 1fr); gap: 12px 14px;
    background: var(--pt-surface); border: 1px solid var(--pt-border); border-radius: 16px; padding: 14px;
}
.pt-item.is-nueva { border-left: 4px solid var(--pt-info); }
.pt-item.is-atencion { border-left: 4px solid var(--pt-warning); }
.pt-ico { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: var(--pt-gold-soft); color: var(--pt-gold-ink); font-size: 1.05rem; }
.pt-cuerpo { min-width: 0; display: grid; gap: 6px; }
.pt-cab { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; }
.pt-cab h3 { margin: 0; color: var(--pt-heading); font-size: 1rem; font-weight: 700; }
.pt-meta { color: var(--pt-muted); font-size: .82rem; }
.pt-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: .76rem; font-weight: 700; white-space: nowrap; }
.pt-chip.is-nueva { background: var(--pt-info-bg); color: var(--pt-info); }
.pt-chip.is-atencion { background: var(--pt-warning-bg); color: var(--pt-warning); }
.pt-chip.is-resuelta { background: var(--pt-success-bg); color: var(--pt-success); }
.pt-chip.is-descartada { background: color-mix(in srgb, var(--pt-muted) 16%, transparent); color: var(--pt-muted); }
.pt-detalle { margin: 0; padding: 10px 12px; border-radius: 10px; background: var(--pt-surface-2); color: var(--pt-heading); font-size: .92rem; line-height: 1.5; white-space: pre-line; overflow-wrap: anywhere; }
.pt-foto { display: inline-block; width: 120px; height: 90px; border-radius: 10px; overflow: hidden; border: 1px solid var(--pt-border); background: var(--pt-surface-2); }
.pt-foto img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pt-acciones { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 2px; }
.pt-acciones form { margin: 0; }
.pt-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 44px; padding: 0 16px; border-radius: 12px;
    border: 1px solid var(--pt-border); background: var(--pt-surface); color: var(--pt-heading);
    font: inherit; font-weight: 700; font-size: .86rem; cursor: pointer; text-decoration: none;
    transition: transform .14s var(--pt-ease), box-shadow .14s var(--pt-ease), background-color .14s ease;
}
.pt-btn.is-primary { background: var(--pt-brand); border-color: var(--pt-brand); color: #fff; }
html[data-theme="dark"] .pt-btn.is-primary { background: var(--pt-gold-soft); border-color: var(--pt-gold-line); color: var(--pt-gold-ink); }
.pt-btn.is-quiet { border-color: transparent; background: transparent; color: var(--pt-muted); }
.pt-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 18px -14px rgba(27,39,70,.35); }
.pt-btn:disabled { opacity: .6; cursor: progress; transform: none; }
.pt-cierre { color: var(--pt-muted); font-size: .82rem; }

.pt-vacio { display: grid; justify-items: center; gap: 8px; text-align: center; padding: 34px 20px; border: 1px dashed var(--pt-border); border-radius: 18px; background: var(--pt-surface); }
.pt-vacio i { font-size: 1.6rem; color: var(--pt-gold-ink); }
.pt-vacio h2 { margin: 4px 0 0; color: var(--pt-heading); font-size: 1.1rem; }
.pt-vacio p { margin: 0; max-width: 34rem; color: var(--pt-muted); line-height: 1.5; }

@media (max-width: 760px) {
    .pt-hero { grid-template-columns: 1fr; }
    .pt-hero-side { justify-items: start; }
    .pt-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .pt-kpi .valor { font-size: 1.35rem; }
    .pt-item { grid-template-columns: 1fr; }
    .pt-ico { display: none; }
    .pt-acciones .pt-btn { flex: 1 1 auto; }
    .pt-refresh { margin-left: 0; width: 100%; }
}
@media (prefers-reduced-motion: reduce) { .pt * { transition: none !important; } }
</style>

<div class="pt">
    <div class="pt-shell">
        <section class="pt-hero">
            <div class="pt-lockup">
                <div class="pt-hero-icon" aria-hidden="true"><i class="fas fa-bell-concierge"></i></div>
                <div>
                    <p class="pt-kicker">QR en la habitación</p>
                    <h1 class="pt-title">Peticiones de huéspedes</h1>
                    <p class="pt-subtitle">Lo que tus huéspedes piden desde el QR de su cuarto. Atiéndelo antes de que se convierta en una queja o en una mala reseña.</p>
                </div>
            </div>
            <?php if ($puedeConfigurar): ?>
                <div class="pt-hero-side">
                    <a class="pt-link" href="<?= url('peticiones/qr') ?>"><i class="fas fa-qrcode" aria-hidden="true"></i>QR de habitaciones</a>
                </div>
            <?php endif; ?>
        </section>


        <section class="pt-kpis" aria-label="Resumen de peticiones">
            <div class="pt-kpi is-live"><div class="nombre">Nuevas</div><div class="valor"><?= (int) $resumen['nuevas'] ?></div></div>
            <div class="pt-kpi"><div class="nombre">En atención</div><div class="valor"><?= (int) $resumen['en_atencion'] ?></div></div>
            <div class="pt-kpi"><div class="nombre">Resueltas hoy</div><div class="valor"><?= (int) $resumen['resueltas_hoy'] ?></div></div>
            <div class="pt-kpi"><div class="nombre">Respuesta hoy</div><div class="valor"><?= $minutos === null ? '—' : (int) $minutos . ' <small>min</small>' ?></div></div>
        </section>

        <nav class="pt-tabs" aria-label="Filtrar peticiones">
            <a class="pt-tab" href="<?= url('peticiones') ?>" <?= $vista === 'abiertas' ? 'aria-current="page"' : '' ?>>
                Por atender <span class="num"><?= $abiertas ?></span>
            </a>
            <a class="pt-tab" href="<?= url('peticiones?vista=cerradas') ?>" <?= $vista === 'cerradas' ? 'aria-current="page"' : '' ?>>
                Cerradas · 7 días
            </a>
            <span class="pt-refresh">
                <span data-pt-reloj>Actualizado <?= $ptSafe(EstanciaService::horaHumana(date('H:i'))) ?></span>
                <button type="button" data-pt-actualizar><i class="fas fa-rotate" aria-hidden="true"></i> Actualizar</button>
            </span>
        </nav>

        <?php if (empty($peticiones)): ?>
            <div class="pt-vacio">
                <?php if ($vista === 'cerradas'): ?>
                    <i class="fas fa-box-archive" aria-hidden="true"></i>
                    <h2>Sin peticiones cerradas en la última semana</h2>
                    <p>Aquí aparecen las que tu equipo resuelve o descarta.</p>
                <?php elseif (!$hayQr): ?>
                    <i class="fas fa-qrcode" aria-hidden="true"></i>
                    <h2>Tus habitaciones aún no tienen su QR</h2>
                    <p>Imprime el QR de cada cuarto y pégalo a la vista. Con él tus huéspedes ven el Wi-Fi y los horarios, y piden toallas o avisan si algo no funciona sin bajar a recepción.</p>
                    <?php if ($puedeConfigurar): ?>
                        <a class="pt-btn is-primary" href="<?= url('peticiones/qr') ?>"><i class="fas fa-print" aria-hidden="true"></i>Preparar e imprimir los QR</a>
                    <?php else: ?>
                        <p>Pídele a gerencia que los imprima desde <strong>Peticiones de huéspedes → QR de habitaciones</strong>.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <i class="fas fa-mug-hot" aria-hidden="true"></i>
                    <h2>Todo en orden</h2>
                    <p>No hay peticiones esperando. Cuando un huésped pida algo desde su QR te llegará aquí y en la campana.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <ul class="pt-lista">
                <?php foreach ($peticiones as $p):
                    $meta = $tipos[$p['tipo']] ?? ['etiqueta' => 'Petición', 'icono' => 'fa-bell-concierge'];
                    $estado = $ptEstados[$p['estado']] ?? $ptEstados['nueva'];
                    $abierta = in_array($p['estado'], EstanciaService::ESTADOS_ABIERTOS, true);
                    $idPet = (int) $p['id'];
                ?>
                    <li class="pt-item <?= $ptSafe($estado[1]) ?>" id="peticion-<?= $idPet ?>">
                        <div class="pt-ico" aria-hidden="true"><i class="fas <?= $ptSafe($meta['icono']) ?>"></i></div>
                        <div class="pt-cuerpo">
                            <div class="pt-cab">
                                <h3>Hab. <?= $ptSafe($p['habitacion_numero']) ?> · <?= $ptSafe($meta['etiqueta']) ?></h3>
                                <span class="pt-chip <?= $ptSafe($estado[1]) ?>"><i class="fas <?= $ptSafe($estado[2]) ?>" aria-hidden="true"></i><?= $ptSafe($estado[0]) ?></span>
                            </div>
                            <div class="pt-meta">
                                <?php if ($abierta): ?>
                                    <strong><?= $ptSafe($ptHace($p['minutos_espera'] ?? 0)) ?></strong>
                                <?php else: ?>
                                    Pedida <?= $ptSafe($ptHora($p['created_at'] ?? '')) ?>
                                <?php endif; ?>
                                <?php if (!empty($p['huesped_nombre'])): ?> · <?= $ptSafe($p['huesped_nombre']) ?><?php endif; ?>
                                <?php if ($p['estado'] === 'en_atencion' && !empty($p['atendida_por_nombre'])): ?> · la atiende <?= $ptSafe($p['atendida_por_nombre']) ?><?php endif; ?>
                            </div>

                            <?php if (!empty($p['detalle'])): ?>
                                <p class="pt-detalle"><?= $ptSafe($p['detalle']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($p['tiene_foto'])): ?>
                                <a class="pt-foto" href="<?= url('peticiones/' . $idPet . '/foto') ?>" target="_blank" rel="noopener" title="Ver la foto completa">
                                    <img src="<?= url('peticiones/' . $idPet . '/foto') ?>" alt="Foto que mandó el huésped" width="120" height="90" loading="lazy">
                                </a>
                            <?php endif; ?>

                            <?php if (!$abierta): ?>
                                <div class="pt-cierre">
                                    <?= $ptSafe($estado[0]) ?> <?= $ptSafe($ptHora($p['cerrada_at'] ?? '')) ?>
                                    <?php if (!empty($p['cerrada_por_nombre'])): ?> por <?= $ptSafe($p['cerrada_por_nombre']) ?><?php endif; ?>
                                    <?php if (!empty($p['nota_cierre'])): ?> · “<?= $ptSafe($p['nota_cierre']) ?>”<?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($abierta && ($puedeAtender || ($p['tipo'] === 'mantenimiento' && $puedeVerHabitacion))): ?>
                                <div class="pt-acciones">
                                    <?php if ($puedeAtender && $p['estado'] === 'nueva'): ?>
                                        <form method="POST" action="<?= url('peticiones/' . $idPet . '/estado') ?>" data-pt-form data-form-guard="off">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="atender">
                                            <button type="submit" class="pt-btn is-primary"><i class="fas fa-hand" aria-hidden="true"></i>Yo lo atiendo</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($puedeAtender): ?>
                                        <form method="POST" action="<?= url('peticiones/' . $idPet . '/estado') ?>" data-pt-form data-form-guard="off">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="resolver">
                                            <button type="submit" class="pt-btn <?= $p['estado'] === 'en_atencion' ? 'is-primary' : '' ?>"><i class="fas fa-check" aria-hidden="true"></i>Resuelta</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($p['tipo'] === 'mantenimiento' && $puedeVerHabitacion): ?>
                                        <a class="pt-btn" href="<?= url('habitaciones/ver/' . (int) $p['habitacion_id']) ?>"><i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>Ver habitación</a>
                                    <?php endif; ?>
                                    <?php if ($puedeAtender): ?>
                                        <form method="POST" action="<?= url('peticiones/' . $idPet . '/estado') ?>" data-pt-form data-form-guard="off"
                                              data-ms-confirm data-ms-type="warning" data-ms-icon="x"
                                              data-ms-title="¿Descartar esta petición?"
                                              data-ms-msg="Úsalo para bromas o peticiones repetidas. El huésped la verá como cerrada."
                                              data-ms-ok="Sí, descartar">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="descartar">
                                            <button type="submit" class="pt-btn is-quiet"><i class="fas fa-ban" aria-hidden="true"></i>Descartar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var boton = document.querySelector('[data-pt-actualizar]');
    if (boton) {
        boton.addEventListener('click', function () { window.location.reload(); });
    }

    // Anti doble clic: el mismo boton no manda dos veces la misma accion.
    document.querySelectorAll('[data-pt-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var b = form.querySelector('button[type="submit"]');
            if (b) { setTimeout(function () { b.disabled = true; }, 0); }
        });
    });

    // La bandeja se refresca sola cada minuto, pero solo si la pestaña esta
    // a la vista y nadie esta a media accion (un modal abierto o un campo
    // con foco): jamas se recarga mientras alguien captura.
    var abierta = <?= $vista === 'abiertas' ? 'true' : 'false' ?>;
    if (!abierta) { return; }
    setInterval(function () {
        if (document.visibilityState !== 'visible') { return; }
        var activo = document.activeElement;
        if (activo && /^(INPUT|TEXTAREA|SELECT)$/.test(activo.tagName)) { return; }
        if (document.querySelector('.ms-cf-ov.open')) { return; }
        window.location.reload();
    }, 60000);
})();
</script>
