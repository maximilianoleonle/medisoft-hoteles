<?php
/**
 * Vista de Apertura de Caja
 * Rediseño boutique (dentro del layout de la app)
 */
$cajaOpenFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];
$cajaOpenOldInput = is_array($_SESSION['old_input'] ?? null) && (($_SESSION['old_input']['form_origen'] ?? '') === 'apertura')
    ? $_SESSION['old_input']
    : [];

if (!function_exists('caja_open_safe')) {
    function caja_open_safe($value, string $default = ''): string
    {
        $value = $value ?? $default;
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('caja_open_error')) {
    function caja_open_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $message = trim((string)($messages[0] ?? ''));
        return $message !== '' ? caja_open_safe($message) : '';
    }
}

if (!function_exists('caja_open_error_class')) {
    function caja_open_error_class(array $errors, string $field): string
    {
        return caja_open_error($errors, $field) !== '' ? ' cj-input-error' : '';
    }
}

if (!function_exists('caja_open_error_attrs')) {
    function caja_open_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (caja_open_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . caja_open_safe($errorId) . '"';
    }
}

$cajaOpenMontoInicial = caja_open_safe($cajaOpenOldInput['monto_inicial'] ?? '0.00', '0.00');
$cajaOpenObservaciones = caja_open_safe($cajaOpenOldInput['observaciones'] ?? '');
?>
<style>
.caja-open {
    --cj-brand: var(--brand-primary, #1B2746);
    --cj-brand-2: var(--brand-secondary, #0F172A);
    --cj-gold: var(--brand-accent, #BD9441);
    --cj-gold-soft: color-mix(in srgb, var(--cj-gold) 15%, #FFFFFF);
    --cj-gold-line: color-mix(in srgb, var(--cj-gold) 42%, #E4D4B0);
    --cj-gold-ink: color-mix(in srgb, var(--cj-gold) 72%, #000);
    --cj-ivory: #F6F2EA; --cj-ivory-2: #FBF8F2;
    --cj-surface: #FFFFFF; --cj-surface-warm: #FCFAF5;
    --cj-border: color-mix(in srgb, var(--cj-brand) 7%, #E7E1D4);
    --cj-ring: color-mix(in srgb, var(--cj-gold) 32%, transparent);
    --cj-text: #171717; --cj-muted: #667085; --cj-heading: #111827;
    --cj-warning: #C2841C; --cj-warning-bg: #FAF0DC;
    --cj-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cj-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
    color: var(--cj-text); font-family: var(--cj-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cj-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cj-ivory-2), var(--cj-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.caja-open .cj-wrap { width: 100%; max-width: 30rem; }
.caja-open .cj-head { text-align: center; margin-bottom: 22px; }
.caja-open .cj-head-icon { width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; color: #fff; font-size: 1.6rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cj-gold), var(--cj-brand) 54%, color-mix(in srgb, var(--cj-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 16px 30px -14px color-mix(in srgb, var(--cj-brand) 70%, transparent); }
.caja-open .cj-title { font-family: var(--cj-serif); color: var(--cj-heading); font-weight: 700; font-size: 2.4rem; line-height: 1; }
.caja-open .cj-date { margin-top: 6px; color: var(--cj-muted); font-size: .9rem; font-weight: 600; text-transform: capitalize; }
.caja-open .cj-sub { margin-top: 6px; color: var(--cj-muted); font-size: .86rem; }

.caja-open .cj-card { background: var(--cj-surface); border: 1px solid var(--cj-border); border-radius: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 22px 48px -28px rgba(27,39,70,.4); }
.caja-open .cj-card-head { padding: 16px 20px; border-bottom: 1px solid var(--cj-border); background: var(--cj-ivory-2); display: flex; align-items: center; gap: 10px; }
.caja-open .cj-card-head h2 { font-family: var(--cj-serif); font-size: 1.4rem; font-weight: 700; color: var(--cj-heading); }
.caja-open .cj-card-head i { color: var(--cj-gold-ink); }
.caja-open .cj-body { padding: 20px; display: grid; gap: 18px; }

.caja-open .cj-info { background: var(--cj-surface-warm); border: 1px solid var(--cj-border); border-radius: 12px; padding: 12px 14px; display: grid; gap: 6px; font-size: .84rem; color: #334155; }
.caja-open .cj-info i { color: var(--cj-muted); width: 16px; }
.caja-open .cj-info strong { color: var(--cj-heading); }

.caja-open label { display: block; font-size: .74rem; font-weight: 700; color: var(--cj-muted); text-transform: uppercase; letter-spacing: .045em; margin-bottom: 7px; }
.caja-open .cj-money { position: relative; }
.caja-open .cj-money span { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--cj-muted); font-size: 1.05rem; font-weight: 700; }
.caja-open .cj-input { width: 100%; min-height: 50px; border: 1px solid var(--cj-border); background: var(--cj-surface-warm); border-radius: 12px; padding: 0 14px 0 30px; color: var(--cj-heading); font-size: 1.15rem; font-weight: 700; font-family: var(--cj-serif); transition: border-color .16s ease, box-shadow .16s ease; }
.caja-open textarea.cj-input { min-height: 84px; padding: 11px 14px; font-family: var(--cj-sans); font-size: .9rem; font-weight: 600; resize: vertical; }
.caja-open .cj-input:focus { outline: none; border-color: var(--cj-gold); box-shadow: 0 0 0 3px var(--cj-ring); background: #fff; }
.caja-open .cj-hint { margin-top: 7px; font-size: .76rem; color: var(--cj-muted); }
.caja-open .cj-input-error { border-color: #B4392B; background: #FFF7F6; }
.caja-open .cj-form-error { display: block; margin-top: 7px; color: #B4392B; font-size: .78rem; font-weight: 800; line-height: 1.35; }
.caja-open .cj-error-summary { padding: 12px 14px; border: 1px solid #F0B8AE; border-radius: 12px; background: #FFF7F6; color: #9E2A1D; font-size: .84rem; font-weight: 800; line-height: 1.35; }

.caja-open .cj-note { background: var(--cj-warning-bg); border: 1px solid color-mix(in srgb, var(--cj-warning) 26%, #fff); border-radius: 12px; padding: 13px 15px; }
.caja-open .cj-note h4 { display: flex; align-items: center; gap: 8px; color: color-mix(in srgb, var(--cj-warning) 84%, #000); font-size: .82rem; font-weight: 700; margin-bottom: 8px; }
.caja-open .cj-note ul { list-style: none; display: grid; gap: 6px; }
.caja-open .cj-note li { display: flex; gap: 8px; align-items: flex-start; font-size: .82rem; color: color-mix(in srgb, var(--cj-warning) 78%, #000); }
.caja-open .cj-note li i { margin-top: 3px; color: var(--cj-warning); }

.caja-open .cj-actions { display: flex; gap: 12px; margin-top: 4px; }
.caja-open .cj-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; border-radius: 12px; font-weight: 700; font-size: .92rem; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: transform .16s ease, box-shadow .16s ease; font-family: var(--cj-sans); }
.caja-open .cj-btn:hover { transform: translateY(-1px); }
.caja-open .cj-btn-muted { background: var(--cj-surface); border-color: var(--cj-border); color: var(--cj-muted); }
.caja-open .cj-btn-gold { background: linear-gradient(135deg, var(--cj-gold), color-mix(in srgb, var(--cj-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--cj-gold) 58%, transparent); }
.caja-open .cj-foot { margin-top: 18px; text-align: center; }
.caja-open .cj-foot a { color: var(--cj-muted); font-size: .82rem; font-weight: 600; text-decoration: none; }
.caja-open .cj-foot a:hover { color: var(--cj-gold-ink); }
</style>

<div class="caja-open">
    <div class="cj-wrap">
        <div class="cj-head">
            <div class="cj-head-icon"><i class="fas fa-cash-register"></i></div>
            <h1 class="cj-title">Abrir caja</h1>
            <p class="cj-date"><?= format_date(date('Y-m-d'), 'l, d \d\e F \d\e Y') ?></p>
            <p class="cj-sub">Abre un corte para registrar los ingresos y gastos del turno.</p>
        </div>

        <div class="cj-card">
            <div class="cj-card-head">
                <i class="fas fa-unlock"></i>
                <h2><?= htmlspecialchars($caja['nombre']) ?></h2>
            </div>

            <form method="POST" action="<?= url('caja/abrir') ?>" class="cj-body">
                <?= csrf_field() ?>
                <input type="hidden" name="caja_id" value="<?= $caja['id'] ?>">

                <?php if (caja_open_error($cajaOpenFieldErrors, '_global') !== ''): ?>
                    <div class="cj-error-summary ms-form-error-summary" role="alert">
                        <?= caja_open_error($cajaOpenFieldErrors, '_global') ?>
                    </div>
                <?php endif; ?>

                <div class="cj-info">
                    <p><i class="fas fa-user-circle"></i> <strong>Usuario:</strong> <?= user_name() ?></p>
                    <p><i class="fas fa-clock"></i> <strong>Hora:</strong> <?= date('H:i:s') ?></p>
                </div>

                <div>
                    <label for="cj_monto_inicial">Efectivo con el que abres</label>
                    <div class="cj-money">
                        <span>$</span>
                        <input type="number" id="cj_monto_inicial" name="monto_inicial" data-money-format="true" step="0.01" min="0" class="cj-input<?= caja_open_error_class($cajaOpenFieldErrors, 'monto_inicial') ?>" placeholder="0.00" value="<?= $cajaOpenMontoInicial ?>" required<?= caja_open_error_attrs($cajaOpenFieldErrors, 'monto_inicial', 'ms-form-error-caja_monto_inicial') ?>>
                    </div>
                    <?php if (caja_open_error($cajaOpenFieldErrors, 'monto_inicial') !== ''): ?>
                        <span id="ms-form-error-caja_monto_inicial" class="cj-form-error ms-form-field-error"><?= caja_open_error($cajaOpenFieldErrors, 'monto_inicial') ?></span>
                    <?php endif; ?>
                    <p class="cj-hint"><i class="fas fa-circle-info"></i> Captura el efectivo con el que comienza este turno.</p>
                </div>

                <div>
                    <label for="cj_observaciones">Observaciones (opcional)</label>
                    <textarea id="cj_observaciones" name="observaciones" rows="3" class="cj-input<?= caja_open_error_class($cajaOpenFieldErrors, 'observaciones') ?>" placeholder="Alguna nota sobre el inicio del turno..."<?= caja_open_error_attrs($cajaOpenFieldErrors, 'observaciones', 'ms-form-error-caja_observaciones') ?>><?= $cajaOpenObservaciones ?></textarea>
                    <?php if (caja_open_error($cajaOpenFieldErrors, 'observaciones') !== ''): ?>
                        <span id="ms-form-error-caja_observaciones" class="cj-form-error ms-form-field-error"><?= caja_open_error($cajaOpenFieldErrors, 'observaciones') ?></span>
                    <?php endif; ?>
                </div>

                <div class="cj-note">
                    <h4><i class="fas fa-triangle-exclamation"></i> Importante</h4>
                    <ul>
                        <li><i class="fas fa-circle-check"></i> <span>Verifica que el monto inicial sea correcto.</span></li>
                        <li><i class="fas fa-circle-check"></i> <span>Cuando termine el turno, haz el corte de caja.</span></li>
                        <li><i class="fas fa-circle-check"></i> <span>Todos los ingresos y gastos quedan en este corte.</span></li>
                    </ul>
                </div>

                <div class="cj-actions">
                    <?php $back_arrow_href = back_url('caja'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                    <a href="<?= back_url('caja') ?>" class="cj-btn cj-btn-muted ms-back-legacy"><i class="fas fa-arrow-left"></i> Volver</a>
                    <button type="submit" class="cj-btn cj-btn-gold"><i class="fas fa-unlock"></i> Abrir caja</button>
                </div>
            </form>
        </div>

        <div class="cj-foot">
            <a href="<?= url('caja/historial') ?>"><i class="fas fa-history"></i> Ver historial de cortes anteriores</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Auto-focus en el campo de monto
document.addEventListener('DOMContentLoaded', function() {
    const montoInicialInput = document.querySelector('input[name="monto_inicial"]');
    if (!montoInicialInput) {
        return;
    }

    if (window.MedisoftMoneyInput) {
        window.MedisoftMoneyInput.init(montoInicialInput);
        window.MedisoftMoneyInput.set(montoInicialInput, montoInicialInput.value);
    }

    montoInicialInput.focus();
});

// Confirmación antes de abrir
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    const montoInicialInput = document.querySelector('input[name="monto_inicial"]');
    const montoInicial = window.MedisoftMoneyInput
        ? window.MedisoftMoneyInput.read(montoInicialInput)
        : parseFloat((montoInicialInput?.value || '').replace(/,/g, '')) || 0;
    const montoInicialTexto = window.MedisoftMoneyInput
        ? '$' + window.MedisoftMoneyInput.format(montoInicial, { fixed: true })
        : `$${montoInicial.toFixed(2)}`;

    Swal.fire({
        title: '¿Confirmar apertura de caja?',
        html: `
            <div class="text-left">
                <p class="mb-2"><strong>Monto inicial:</strong> ${montoInicialTexto}</p>
                <p class="mb-2"><strong>Usuario:</strong> <?= user_name() ?></p>
                <p class="mb-2"><strong>Fecha y hora:</strong> <?= date('d/m/Y H:i:s') ?></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#BD9441',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-unlock mr-2"></i>Sí, abrir caja',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            if (window.MedisoftMoneyInput) {
                window.MedisoftMoneyInput.sanitize(this);
            }
            this.submit();
        }
    });
});
</script>

<?php clear_old_input(); ?>
