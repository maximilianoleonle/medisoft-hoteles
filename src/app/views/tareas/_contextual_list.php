<?php
$tareasContextuales = is_array($tareasContextuales ?? null) ? $tareasContextuales : [];
$tituloTareasContextuales = $tituloTareasContextuales ?? 'Tareas';
$subtituloTareasContextuales = $subtituloTareasContextuales ?? 'Tareas relacionadas con este registro.';

if (!function_exists('tlm_context_safe')) {
    function tlm_context_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tlm_context_date')) {
    function tlm_context_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

if (!function_exists('tk_context_estado_meta')) {
    function tk_context_estado_meta($estado): array
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente'  => ['Pendiente', 'is-pendiente'],
            'asignada'   => ['Asignada', 'is-asignada'],
            'en_proceso' => ['En proceso', 'is-proceso'],
            'completada' => ['Completada', 'is-completada'],
            'cancelada'  => ['Cancelada', 'is-cancelada'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft'];
    }
}

if (!function_exists('tk_context_workers_text')) {
    function tk_context_workers_text(array $tarea): string
    {
        foreach (['trabajadores_nombres', 'trabajadores_asignados', 'trabajador_nombre'] as $campo) {
            $texto = trim((string)($tarea[$campo] ?? ''));
            if ($texto !== '') {
                return $texto;
            }
        }

        return '';
    }
}

$tlmCategoriaLabels = [
    'limpieza' => 'Limpieza',
    'mantenimiento' => 'Mantenimiento',
    'general' => 'General',
];
?>

<style>
.tk-context-card {
    --tkc-brand: var(--brand-primary, #1B2746);
    --tkc-gold: var(--brand-accent, #BD9441);
    --tkc-gold-soft: color-mix(in srgb, var(--tkc-gold) 15%, #FFFFFF);
    --tkc-gold-line: color-mix(in srgb, var(--tkc-gold) 42%, #E4D4B0);
    --tkc-gold-ink: color-mix(in srgb, var(--tkc-gold) 72%, #000);
    --tkc-surface: #FFFFFF; --tkc-surface-warm: #FCFAF5;
    --tkc-border: color-mix(in srgb, var(--tkc-brand) 7%, #E7E1D4);
    --tkc-text: #171717; --tkc-muted: #667085; --tkc-heading: #111827;
    --tkc-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --tkc-success: #1E9E63; --tkc-success-bg: #E7F4EC;
    --tkc-warning: #C2841C; --tkc-warning-bg: #FAF0DC;
    --tkc-info: #2F77E0; --tkc-info-bg: #E6EFFC;
    --tkc-proc: #0E8A8A; --tkc-proc-bg: #E2F4F4;
    border: 1px solid var(--tkc-border); background: var(--tkc-surface); border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: var(--tkc-text);
}
.tk-context-card .tk-ctx-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 16px 18px; border-bottom: 1px solid var(--tkc-border); }
.tk-context-card .tk-ctx-head h2 { margin: 0; color: var(--tkc-heading); font-family: var(--tkc-serif); font-size: 1.3rem; font-weight: 700; }
.tk-context-card .tk-ctx-head p { margin: 3px 0 0; color: var(--tkc-muted); font-size: .85rem; }
.tk-context-card .tk-ctx-count { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; background: var(--tkc-gold-soft); color: var(--tkc-gold-ink); border: 1px solid var(--tkc-gold-line); padding: 5px 11px; font-size: .76rem; font-weight: 700; white-space: nowrap; }
.tk-context-card .tk-ctx-body { display: grid; gap: 10px; padding: 14px; }
.tk-context-card .tk-ctx-item { display: grid; grid-template-columns: minmax(0,1fr) auto; gap: 12px; align-items: center; border: 1px solid var(--tkc-border); border-radius: 12px; padding: 12px; background: var(--tkc-surface); transition: border-color .16s ease, background .16s ease; }
.tk-context-card .tk-ctx-item:hover { border-color: var(--tkc-gold-line); background: var(--tkc-surface-warm); }
.tk-context-card .tk-ctx-title { font-weight: 700; color: var(--tkc-heading); text-decoration: none; }
.tk-context-card .tk-ctx-title:hover { text-decoration: underline; text-decoration-color: var(--tkc-gold); text-underline-offset: 3px; }
.tk-context-card .tk-ctx-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 7px; color: var(--tkc-muted); font-size: .78rem; align-items: center; }
.tk-context-card .tk-ctx-tag { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; background: var(--tkc-surface-warm); color: var(--tkc-muted); border: 1px solid var(--tkc-border); padding: 3px 9px; font-size: .72rem; font-weight: 700; }
.tk-context-card .tk-ctx-badge { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; padding: 4px 10px; font-size: .72rem; font-weight: 700; border: 1px solid transparent; white-space: nowrap; }
.tk-context-card .tk-ctx-badge.is-pendiente { color: color-mix(in srgb, var(--tkc-warning) 82%, #000); background: var(--tkc-warning-bg); border-color: color-mix(in srgb, var(--tkc-warning) 28%, #fff); }
.tk-context-card .tk-ctx-badge.is-asignada { color: color-mix(in srgb, var(--tkc-info) 80%, #000); background: var(--tkc-info-bg); border-color: color-mix(in srgb, var(--tkc-info) 26%, #fff); }
.tk-context-card .tk-ctx-badge.is-proceso { color: color-mix(in srgb, var(--tkc-proc) 80%, #000); background: var(--tkc-proc-bg); border-color: color-mix(in srgb, var(--tkc-proc) 26%, #fff); }
.tk-context-card .tk-ctx-badge.is-completada { color: color-mix(in srgb, var(--tkc-success) 78%, #000); background: var(--tkc-success-bg); border-color: color-mix(in srgb, var(--tkc-success) 26%, #fff); }
.tk-context-card .tk-ctx-badge.is-cancelada, .tk-context-card .tk-ctx-badge.is-soft { color: var(--tkc-muted); background: var(--tkc-surface-warm); border-color: var(--tkc-border); }
.tk-context-card .tk-ctx-empty { padding: 26px 16px; text-align: center; color: var(--tkc-muted); }
.tk-context-card .tk-ctx-empty strong { display: block; color: var(--tkc-heading); margin-bottom: 4px; font-weight: 700; }
@media (max-width: 760px) { .tk-context-card .tk-ctx-item { grid-template-columns: 1fr; } }
</style>

<section class="tk-context-card">
    <div class="tk-ctx-head">
        <div>
            <h2><?= tlm_context_safe($tituloTareasContextuales) ?></h2>
            <p><?= tlm_context_safe($subtituloTareasContextuales) ?></p>
        </div>
        <span class="tk-ctx-count"><i class="fas fa-list-check"></i> <?= number_format(count($tareasContextuales)) ?></span>
    </div>

    <?php if (empty($tareasContextuales)): ?>
        <div class="tk-ctx-empty">
            <strong>Sin tareas vinculadas</strong>
            <span>No hay tareas relacionadas con este registro.</span>
        </div>
    <?php else: ?>
        <div class="tk-ctx-body">
            <?php foreach ($tareasContextuales as $tareaContextual): ?>
                <?php
                [$eLabel, $eClass] = tk_context_estado_meta($tareaContextual['estado'] ?? 'pendiente');
                $categoria = (string)($tareaContextual['categoria'] ?? 'general');
                $trabajadoresTexto = tk_context_workers_text($tareaContextual);
                ?>
                <article class="tk-ctx-item">
                    <div>
                        <a class="tk-ctx-title" href="<?= url('tareas/' . (int)($tareaContextual['id'] ?? 0)) ?>">
                            <?= tlm_context_safe($tareaContextual['titulo'] ?? null, 'Tarea #' . (int)($tareaContextual['id'] ?? 0)) ?>
                        </a>
                        <div class="tk-ctx-meta">
                            <span class="tk-ctx-tag"><?= tlm_context_safe($tlmCategoriaLabels[$categoria] ?? $categoria) ?></span>
                            <span>L&iacute;mite: <?= tlm_context_safe(tlm_context_date($tareaContextual['fecha_limite'] ?? null)) ?></span>
                            <?php if (!empty($tareaContextual['habitacion_numero'])): ?>
                                <span>Hab. <?= tlm_context_safe($tareaContextual['habitacion_numero']) ?></span>
                            <?php endif; ?>
                            <?php if ($trabajadoresTexto !== ''): ?>
                                <span><?= tlm_context_safe($trabajadoresTexto) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="tk-ctx-badge <?= $eClass ?>"><?= $eLabel ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
