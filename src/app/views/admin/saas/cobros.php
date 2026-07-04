<?php
/**
 * Panel SaaS - Cobros mensuales a hoteles. Lenguaje llano, sin jerga.
 */
$periodo = $periodo ?? date('Y-m');
$cobros = $cobros ?? [];
$stripeConfigurado = $stripeConfigurado ?? false;
$correoConfigurado = $correoConfigurado ?? false;

$scSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$scMoney = static function ($n) {
    return '$' . number_format((float) $n, 2);
};

$totalPeriodo = 0.0;
$totalPagado = 0.0;
$pendientes = 0;
$vencidos = 0;
foreach ($cobros as $c) {
    if ($c['estado'] === 'cancelado') continue;
    $totalPeriodo += (float) $c['monto'];
    if ($c['estado'] === 'pagado') $totalPagado += (float) $c['monto'];
    if ($c['estado'] === 'pendiente') $pendientes++;
    if ($c['estado'] === 'vencido') $vencidos++;
}

$badgeCobro = static function ($estado) {
    $map = [
        'pagado' => ['Pagado', 'background:rgba(22,163,74,.12);color:var(--ms-success);'],
        'pendiente' => ['Pendiente', 'background:rgba(245,158,11,.14);color:#92600A;'],
        'vencido' => ['Vencido', 'background:rgba(220,38,38,.12);color:#B91C1C;'],
        'cancelado' => ['Cancelado', 'background:rgba(100,116,139,.12);color:var(--ms-muted);'],
    ];
    return $map[$estado] ?? [$estado, ''];
};

$mesAnterior = date('Y-m', strtotime($periodo . '-01 -1 month'));
$mesSiguiente = date('Y-m', strtotime($periodo . '-01 +1 month'));
$etiquetaPeriodo = function_exists('strftime') ? null : null;
$meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
$etiquetaPeriodo = ($meses[substr($periodo, 5, 2)] ?? $periodo) . ' ' . substr($periodo, 0, 4);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--ms-text);">Cobros a hoteles</h1>
            <p class="mt-1 text-sm" style="color:var(--ms-muted);">Lo que cada hotel te paga al mes (paquete básico + bloques activos).</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= url('admin/saas/hoteles') ?>"
               class="inline-flex items-center justify-center gap-1.5 rounded-md border px-4 py-2 text-sm font-medium transition hover:bg-slate-50"
               style="border-color:var(--ms-border);color:var(--ms-text);">
                <i class="fas fa-building text-xs" style="color:var(--ms-muted);"></i>
                Hoteles
            </a>
            <form method="POST" action="<?= url('admin/saas/cobros/generar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="periodo" value="<?= $scSafe($periodo) ?>">
                <button type="submit"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-px hover:opacity-90"
                        style="background:var(--ms-primary);">
                    <i class="fas fa-file-invoice-dollar text-xs"></i>
                    Generar cobros de <?= $scSafe($etiquetaPeriodo) ?>
                </button>
            </form>
            <form method="POST" action="<?= url('admin/saas/cobros/ciclo') ?>"
                  onsubmit="return confirm('Esto genera los cobros que falten, manda correos de cobro y recordatorios, y marca vencidos. ¿Continuar?');">
                <?= csrf_field() ?>
                <input type="hidden" name="periodo" value="<?= $scSafe($periodo) ?>">
                <button type="submit"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border px-4 py-2 text-sm font-medium transition hover:bg-slate-50"
                        style="border-color:var(--ms-border);color:var(--ms-text);"
                        title="Lo mismo que corre el cron diario: generar + correos + recordatorios + vencidos">
                    <i class="fas fa-rotate text-xs" style="color:var(--ms-muted);"></i>
                    Ejecutar ciclo ahora
                </button>
            </form>
        </div>
    </div>

    <!-- Selector de mes -->
    <div class="mb-6 flex items-center gap-3">
        <a href="<?= url('admin/saas/cobros?periodo=' . urlencode($mesAnterior)) ?>"
           class="inline-flex h-9 w-9 items-center justify-center rounded-md border transition hover:bg-slate-50"
           style="border-color:var(--ms-border);color:var(--ms-text);"><i class="fas fa-chevron-left text-xs"></i></a>
        <div class="text-sm font-semibold" style="color:var(--ms-text);"><?= $scSafe($etiquetaPeriodo) ?></div>
        <a href="<?= url('admin/saas/cobros?periodo=' . urlencode($mesSiguiente)) ?>"
           class="inline-flex h-9 w-9 items-center justify-center rounded-md border transition hover:bg-slate-50"
           style="border-color:var(--ms-border);color:var(--ms-text);"><i class="fas fa-chevron-right text-xs"></i></a>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(37,99,235,.10);">
                <i class="fas fa-coins text-sm" style="color:var(--ms-primary);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Facturado del mes</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-text);"><?= $scMoney($totalPeriodo) ?></div>
            </div>
        </div>
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(22,163,74,.10);">
                <i class="fas fa-circle-check text-sm" style="color:var(--ms-success);"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Ya pagado</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:var(--ms-success);"><?= $scMoney($totalPagado) ?></div>
            </div>
        </div>
        <div class="rounded-lg border bg-white flex items-center gap-3 px-4 py-3" style="border-color:var(--ms-border);">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" style="background:rgba(245,158,11,.12);">
                <i class="fas fa-hourglass-half text-sm" style="color:#B45309;"></i>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Pendientes / vencidos</div>
                <div class="mt-0.5 text-2xl font-semibold" style="color:#B45309;"><?= (int) $pendientes ?><?php if ($vencidos > 0): ?> <span style="color:#B91C1C;">· <?= (int) $vencidos ?> vencido(s)</span><?php endif; ?></div>
            </div>
        </div>
    </div>

    <?php if (!$stripeConfigurado): ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm border-amber-200 bg-amber-50 text-amber-800">
            Los links de pago requieren <code>SAAS_STRIPE_SECRET_KEY</code> en el .env (llave de TU cuenta Stripe, no la de un hotel). Mientras tanto puedes marcar pagos como manuales.
        </div>
    <?php endif; ?>

    <?php if (!$correoConfigurado): ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm border-amber-200 bg-amber-50 text-amber-800">
            Los correos de cobro requieren <code>SAAS_EMAIL_REMITENTE</code> en el .env (correo de tu plataforma; opcional <code>SAAS_EMAIL_NOMBRE</code>). El ciclo automático funciona igual, pero sin envíos.
        </div>
    <?php endif; ?>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= $tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' ?>">
            <?= $mensaje['texto'] ?? '' ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg overflow-hidden shadow-sm" style="border:1px solid var(--ms-border);">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y" style="border-color:var(--ms-border);">
                <thead style="background:var(--ms-bg);">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Hotel</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Desglose</th>
                        <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Monto</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Estado</th>
                        <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color:var(--ms-border);">
                <?php if (empty($cobros)): ?>
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm" style="color:var(--ms-muted);">
                        No hay cobros de este mes todavía. Usa "Generar cobros" para crearlos con los bloques activos de cada hotel.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($cobros as $c): ?>
                        <?php
                        [$badgeTxt, $badgeStyle] = $badgeCobro($c['estado']);
                        $desglose = json_decode((string) ($c['desglose_json'] ?? ''), true) ?: [];
                        $numBloques = count($desglose['modulos'] ?? []);
                        ?>
                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold" style="color:var(--ms-text);"><?= $scSafe($c['hotel_nombre']) ?></td>
                            <td class="px-5 py-4 text-xs" style="color:var(--ms-muted);">
                                Básico <?= $scMoney($desglose['precio_base'] ?? 0) ?> + <?= (int) $numBloques ?> bloque(s) <?= $scMoney($desglose['total_modulos'] ?? 0) ?>
                                <?php if ($c['metodo']): ?><div class="mt-0.5">Vía: <?= $scSafe($c['metodo']) ?><?= $c['pagado_at'] ? ' · ' . $scSafe(date('d/m/Y', strtotime((string) $c['pagado_at']))) : '' ?></div><?php endif; ?>
                                <?php if (!empty($c['vence_at']) && in_array($c['estado'], ['pendiente', 'vencido'], true)): ?>
                                    <div class="mt-0.5">Vence: <?= $scSafe(date('d/m/Y', strtotime((string) $c['vence_at']))) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($c['correo_enviado_at'])): ?>
                                    <div class="mt-0.5">✉ Correo <?= $scSafe(date('d/m', strtotime((string) $c['correo_enviado_at']))) ?><?= !empty($c['recordatorio_enviado_at']) ? ' · recordatorio ' . $scSafe(date('d/m', strtotime((string) $c['recordatorio_enviado_at']))) : '' ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-semibold" style="color:var(--ms-text);"><?= $scMoney($c['monto']) ?></td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="<?= $badgeStyle ?>"><?= $scSafe($badgeTxt) ?></span></td>
                            <td class="px-5 py-4 text-right">
                                <?php if (in_array($c['estado'], ['pendiente', 'vencido'], true)): ?>
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        <?php if ($correoConfigurado): ?>
                                            <form method="POST" action="<?= url('admin/saas/cobros/' . (int) $c['id'] . '/correo') ?>">
                                                <?= csrf_field() ?>
                                                <?php $yaEnviado = !empty($c['correo_enviado_at']); ?>
                                                <input type="hidden" name="recordatorio" value="<?= $yaEnviado ? 1 : 0 ?>">
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-md border px-2.5 py-1.5 text-xs font-medium transition hover:bg-slate-50" style="border-color:var(--ms-border);color:var(--ms-text);">
                                                    <i class="fas fa-envelope text-[10px]" style="color:var(--ms-muted);"></i>
                                                    <?= $yaEnviado ? 'Reenviar recordatorio' : 'Enviar correo de cobro' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (!empty($c['checkout_url'])): ?>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1 rounded-md border px-2.5 py-1.5 text-xs font-medium transition hover:bg-slate-50"
                                                    style="border-color:var(--ms-border);color:var(--ms-text);"
                                                    data-link="<?= $scSafe($c['checkout_url']) ?>"
                                                    onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.link).then(() => { this.textContent='Copiado ✓'; })">
                                                <i class="fas fa-link text-[10px]"></i> Copiar link de pago
                                            </button>
                                        <?php elseif ($stripeConfigurado): ?>
                                            <form method="POST" action="<?= url('admin/saas/cobros/' . (int) $c['id'] . '/link') ?>">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-semibold text-white" style="background:var(--ms-primary);">
                                                    <i class="fab fa-stripe-s text-[10px]"></i> Crear link de pago
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="<?= url('admin/saas/cobros/' . (int) $c['id'] . '/pagado') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-md border px-2.5 py-1.5 text-xs font-medium transition hover:bg-slate-50" style="border-color:var(--ms-border);color:var(--ms-success);">
                                                ✓ Pagado manual
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= url('admin/saas/cobros/' . (int) $c['id'] . '/cancelar') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-md border px-2.5 py-1.5 text-xs font-medium transition hover:bg-red-50" style="border-color:var(--ms-border);color:#B91C1C;">
                                                Cancelar
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs" style="color:var(--ms-muted);">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-4 text-xs" style="color:var(--ms-muted);">
        El monto se congela al generar el cobro (si el hotel cambia bloques después, aplica al mes siguiente).
        El link de pago se marca solo como pagado cuando el hotel paga (webhook de Stripe en <code>/saas/webhook/stripe</code>).
    </p>
</div>
