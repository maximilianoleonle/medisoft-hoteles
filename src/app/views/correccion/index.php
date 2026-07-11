<?php
/**
 * Vista de Corrección de Precios
 * Rediseño boutique (standalone, no usa el layout de la app)
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Corrección de precios - Medisoft Hoteles</title>
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
    <link href="<?= asset('vendor/fonts/marca.css') ?>" rel="stylesheet">
    <style>
        :root {
            --co-brand: var(--brand-primary, #1B2746);
            --co-brand-2: var(--brand-secondary, #0F172A);
            --co-gold: var(--brand-accent, #BD9441);
            --co-gold-soft: color-mix(in srgb, var(--co-gold) 15%, #FFFFFF);
            --co-gold-line: color-mix(in srgb, var(--co-gold) 42%, #E4D4B0);
            --co-gold-ink: color-mix(in srgb, var(--co-gold) 72%, #000);
            --co-ivory: #F6F2EA; --co-ivory-2: #FBF8F2;
            --co-surface: #FFFFFF; --co-surface-warm: #FCFAF5;
            --co-border: color-mix(in srgb, var(--co-brand) 7%, #E7E1D4);
            --co-text: #171717; --co-muted: #667085; --co-heading: #111827;
            --co-success: #1E9E63; --co-success-bg: #E7F4EC;
            --co-warning: #C2841C; --co-warning-bg: #FAF0DC;
            --co-danger: #B4392B; --co-danger-bg: #F8EAE5;
            --co-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --co-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html {
            touch-action: pan-x pan-y;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
        @media (max-width: 1024px) {
            input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
            select,
            textarea {
                font-size: 16px !important;
            }
        }
        body {
            font-family: var(--co-sans);
            color: var(--co-text);
            padding: 24px;
            -webkit-font-smoothing: antialiased;
            background:
                radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--co-gold) 8%, transparent), transparent 60%),
                linear-gradient(180deg, var(--co-ivory-2), var(--co-ivory));
            min-height: 100vh;
        }
        .container { max-width: 1080px; margin: 0 auto; display: grid; gap: 14px; }
        .co-head { display: grid; grid-template-columns: 48px minmax(0,1fr); align-items: center; column-gap: 14px; }
        .co-head-icon {
            width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
            background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--co-gold), var(--co-brand) 54%, color-mix(in srgb, var(--co-brand) 68%, var(--brand-accent, #BD9441)));
            box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--co-brand) 72%, transparent);
        }
        .co-kicker { margin: 0 0 2px; color: var(--co-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; text-transform: uppercase; line-height: 1; }
        .co-title { margin: 0; font-family: var(--co-serif); color: var(--co-heading); font-weight: 700; font-size: clamp(2rem, 4vw, 2.8rem); line-height: 1; }
        .co-subtitle { margin: 8px 0 0; max-width: 46rem; color: var(--co-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

        .co-summary { background: var(--co-surface); border: 1px solid var(--co-border); border-radius: 16px; padding: 18px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
        .co-summary h2 { font-family: var(--co-serif); font-size: 1.4rem; font-weight: 700; color: var(--co-heading); margin-bottom: 14px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .summary-item { background: var(--co-surface-warm); border: 1px solid var(--co-border); border-radius: 14px; padding: 14px; text-align: center; }
        .summary-number { font-family: var(--co-serif); font-size: 2.1rem; font-weight: 700; line-height: 1; color: var(--co-heading); }
        .summary-label { margin-top: 6px; color: var(--co-muted); font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }

        .alert { padding: 16px 18px; border-radius: 14px; display: flex; align-items: flex-start; gap: 12px; border: 1px solid transparent; font-size: .9rem; line-height: 1.5; }
        .alert i { font-size: 1.2rem; margin-top: 1px; }
        .alert strong { color: var(--co-heading); }
        .alert-warning { background: var(--co-warning-bg); border-color: color-mix(in srgb, var(--co-warning) 26%, #fff); color: color-mix(in srgb, var(--co-warning) 84%, #000); }
        .alert-warning i { color: var(--co-warning); }
        .alert-success { background: var(--co-success-bg); border-color: color-mix(in srgb, var(--co-success) 26%, #fff); color: color-mix(in srgb, var(--co-success) 80%, #000); }
        .alert-success i { color: var(--co-success); }

        .co-section-title { font-family: var(--co-serif); font-size: 1.5rem; font-weight: 700; color: var(--co-heading); margin: 8px 0 2px; }

        .card { background: var(--co-surface); border: 1px solid var(--co-border); border-left: 4px solid var(--co-danger); border-radius: 14px; padding: 18px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 12px 28px -24px rgba(27,39,70,.26); }
        .card h3 { color: var(--co-heading); font-weight: 700; font-size: 1.02rem; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px; }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px; font-size: .72rem; font-weight: 700; background: var(--co-gold-soft); color: var(--co-gold-ink); border: 1px solid var(--co-gold-line); }
        .detail { font-size: .88rem; line-height: 1.7; color: #334155; }
        .detail strong { color: var(--co-heading); }
        .cortesia { color: var(--co-success); font-weight: 700; }

        .price-row { display: flex; justify-content: space-between; gap: 12px; padding: 11px 13px; background: var(--co-surface-warm); border: 1px solid var(--co-border); border-radius: 11px; margin: 7px 0; font-size: .9rem; font-weight: 600; }
        .price-row span:first-child { color: var(--co-muted); }
        .price-incorrect { color: var(--co-danger); font-weight: 700; }
        .price-correct { color: var(--co-success); font-weight: 700; }
        .price-row.is-diff { background: var(--co-danger-bg); border-color: color-mix(in srgb, var(--co-danger) 24%, #fff); }
        .price-row.is-diff span:first-child, .price-row.is-diff span:last-child { color: color-mix(in srgb, var(--co-danger) 84%, #000); }

        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 11px; font-weight: 700; font-size: .92rem; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: transform .16s ease, box-shadow .16s ease, background .16s ease; font-family: var(--co-sans); }
        .btn:hover { transform: translateY(-1px); }
        .btn-gold { background: linear-gradient(135deg, var(--co-gold), color-mix(in srgb, var(--co-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--co-gold) 58%, transparent); }
        .btn-danger { background: linear-gradient(135deg, var(--co-danger), color-mix(in srgb, var(--co-danger) 72%, #000)); color: #fff; box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--co-danger) 52%, transparent); }
        .btn-muted { background: var(--co-surface); color: var(--co-muted); border-color: var(--co-border); }

        .actions { text-align: center; margin-top: 8px; padding: 22px; background: var(--co-gold-soft); border: 1px solid var(--co-gold-line); border-radius: 16px; }
        .actions h3 { font-family: var(--co-serif); font-size: 1.4rem; font-weight: 700; color: var(--co-heading); margin-bottom: 10px; }
        .actions p { color: var(--co-muted); margin-bottom: 18px; font-size: .9rem; line-height: 1.5; }
        .actions form { display: inline-flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
    </style>
    <script>
        (function() {
            var lastTouchEnd = 0;
            function blockZoom(event) {
                if (event.cancelable) {
                    event.preventDefault();
                }
            }

            document.addEventListener('gesturestart', blockZoom, { passive: false });
            document.addEventListener('gesturechange', blockZoom, { passive: false });
            document.addEventListener('gestureend', blockZoom, { passive: false });
            document.addEventListener('touchmove', function(event) {
                if (event.touches && event.touches.length > 1) {
                    blockZoom(event);
                }
            }, { passive: false });
            document.addEventListener('touchend', function(event) {
                var now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    blockZoom(event);
                }
                lastTouchEnd = now;
            }, { passive: false });
        })();
    </script>
</head>
<body>
    <div class="container">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <div class="co-head">
            <div class="co-head-icon"><i class="fas fa-scale-balanced"></i></div>
            <div>
                <p class="co-kicker">Mantenimiento de datos</p>
                <h1 class="co-title">Corrección de precios</h1>
                <p class="co-subtitle">Revisa las reservaciones cuyo precio total no coincide con el cálculo correcto y aplica los ajustes cuando estés listo.</p>
            </div>
        </div>

        <div class="co-summary">
            <h2>Resumen del análisis</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-number"><?= $total_reservaciones ?></div>
                    <div class="summary-label">Revisadas</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number" style="color: var(--co-success);"><?= $correctas ?></div>
                    <div class="summary-label">Correctas</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number" style="color: var(--co-warning);"><?= count($incorrectas) ?></div>
                    <div class="summary-label">Por corregir</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number" style="color: var(--co-danger);">$<?= number_format($total_diferencia, 2) ?></div>
                    <div class="summary-label">Diferencia total</div>
                </div>
            </div>
        </div>

        <?php if (empty($incorrectas)): ?>
            <div class="alert alert-success">
                <i class="fas fa-circle-check"></i>
                <div>
                    <strong>Todas las reservaciones tienen el precio correcto.</strong><br>
                    No se encontró ninguna que necesite corrección.
                </div>
            </div>
            <div style="text-align: center;">
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-gold">
                    <i class="fas fa-house"></i> Volver al inicio
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    <strong>Encontramos <?= count($incorrectas) ?> reservaciones con el precio incorrecto.</strong><br>
                    Revisa los detalles y aplica las correcciones cuando estés listo.
                </div>
            </div>

            <h2 class="co-section-title">Reservaciones a corregir</h2>

            <?php foreach ($incorrectas as $inc): ?>
                <div class="card">
                    <h3>
                        Reservación #<?= $inc['reservacion']['id'] ?> &middot; <?= htmlspecialchars($inc['reservacion']['huesped']) ?>
                        <span class="badge">
                            <i class="fas fa-calendar-days"></i>
                            <?= date('d/m/Y', strtotime($inc['reservacion']['fecha_entrada'])) ?> &rarr;
                            <?= date('d/m/Y', strtotime($inc['reservacion']['fecha_salida'])) ?>
                            (<?= $inc['reservacion']['noches'] ?> noches)
                        </span>
                    </h3>

                    <div class="detail">
                        <strong>Habitaciones:</strong><br>
                        <?php foreach ($inc['habitaciones'] as $hab): ?>
                            &bull; Hab. <?= $hab['numero'] ?>:
                            $<?= number_format($hab['precio_base'], 2) ?> &times; <?= $inc['reservacion']['noches'] ?> =
                            $<?= number_format($hab['precio_base'] * $inc['reservacion']['noches'], 2) ?>
                            <?= $hab['es_cortesia'] == 1 ? '<span class="cortesia">(CORTESÍA)</span>' : '' ?><br>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 14px;">
                        <div class="price-row">
                            <span>Precio actual (incorrecto)</span>
                            <span class="price-incorrect">$<?= number_format($inc['reservacion']['precio_total'], 2) ?></span>
                        </div>
                        <div class="price-row">
                            <span>Precio correcto</span>
                            <span class="price-correct">$<?= number_format($inc['precio_correcto'], 2) ?></span>
                        </div>
                        <div class="price-row is-diff">
                            <span><strong>Diferencia</strong></span>
                            <span>-$<?= number_format(abs($inc['diferencia']), 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="actions">
                <h3>¿Aplicar todas las correcciones?</h3>
                <p>
                    Esto actualizará <?= count($incorrectas) ?> reservaciones en la base de datos.<br>
                    <strong>Asegúrate de haber revisado los detalles antes de continuar.</strong>
                </p>
                <form method="POST" action="<?= BASE_URL ?>/correccion/aplicar" data-ms-confirm data-ms-type="error" data-ms-icon="alert" data-ms-title="¿Aplicar correcciones?" data-ms-msg="Esta acción modificará la base de datos y no se puede deshacer." data-ms-ok="Aplicar correcciones">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-check"></i> Aplicar correcciones
                    </button>
                    <a href="<?= BASE_URL ?>/dashboard" class="btn btn-muted">
                        <i class="fas fa-xmark"></i> Cancelar
                    </a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
