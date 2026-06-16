<?php
$tareasContextuales = is_array($tareasContextuales ?? null) ? $tareasContextuales : [];
$tituloTareasContextuales = $tituloTareasContextuales ?? 'Tareas operativas';
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

$tlmEstadoLabels = [
    'pendiente' => 'Pendiente',
    'asignada' => 'Asignada',
    'en_proceso' => 'En proceso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
];

$tlmCategoriaLabels = [
    'limpieza' => 'Limpieza',
    'mantenimiento' => 'Mantenimiento',
    'general' => 'General',
];
?>

<style>
.tlm-context-card{border:1px solid rgba(148,163,184,.32);background:#fff;border-radius:8px;overflow:hidden}
.tlm-context-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:16px;border-bottom:1px solid rgba(148,163,184,.24);background:#f8fafc}
.tlm-context-head h2{margin:0;color:#111827;font-size:1.05rem;font-weight:900}
.tlm-context-head p{margin:4px 0 0;color:#64748b;font-size:.86rem}
.tlm-context-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;background:#eef2ff;color:#3730a3;padding:5px 9px;font-size:.76rem;font-weight:900;white-space:nowrap}
.tlm-context-body{display:grid;gap:10px;padding:14px}
.tlm-context-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;border:1px solid rgba(148,163,184,.24);border-radius:8px;padding:12px;background:#fff}
.tlm-context-title{font-weight:900;color:#172033;text-decoration:none}
.tlm-context-title:hover{text-decoration:underline}
.tlm-context-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:7px;color:#64748b;font-size:.8rem}
.tlm-context-pill{display:inline-flex;align-items:center;border-radius:999px;background:#f1f5f9;color:#475569;padding:4px 8px;font-size:.74rem;font-weight:800}
.tlm-context-empty{padding:26px 16px;text-align:center;color:#64748b}
.tlm-context-empty strong{display:block;color:#172033;margin-bottom:4px}
@media (max-width:760px){.tlm-context-head,.tlm-context-item{grid-template-columns:1fr;display:grid}.tlm-context-badge{width:max-content}}
</style>

<section class="tlm-context-card">
    <div class="tlm-context-head">
        <div>
            <h2><?= tlm_context_safe($tituloTareasContextuales) ?></h2>
            <p><?= tlm_context_safe($subtituloTareasContextuales) ?></p>
        </div>
        <span class="tlm-context-badge">
            <i class="fas fa-list-check"></i>
            <?= number_format(count($tareasContextuales)) ?>
        </span>
    </div>

    <?php if (empty($tareasContextuales)): ?>
        <div class="tlm-context-empty">
            <strong>Sin tareas vinculadas</strong>
            <span>No hay tareas operativas relacionadas en el hotel actual.</span>
        </div>
    <?php else: ?>
        <div class="tlm-context-body">
            <?php foreach ($tareasContextuales as $tareaContextual): ?>
                <?php
                $estado = (string)($tareaContextual['estado'] ?? 'pendiente');
                $categoria = (string)($tareaContextual['categoria'] ?? 'general');
                ?>
                <article class="tlm-context-item">
                    <div>
                        <a class="tlm-context-title" href="<?= url('tareas/' . (int)($tareaContextual['id'] ?? 0)) ?>">
                            <?= tlm_context_safe($tareaContextual['titulo'] ?? null, 'Tarea #' . (int)($tareaContextual['id'] ?? 0)) ?>
                        </a>
                        <div class="tlm-context-meta">
                            <span class="tlm-context-pill"><?= tlm_context_safe($tlmCategoriaLabels[$categoria] ?? $categoria) ?></span>
                            <span>Limite: <?= tlm_context_safe(tlm_context_date($tareaContextual['fecha_limite'] ?? null)) ?></span>
                            <?php if (!empty($tareaContextual['habitacion_numero'])): ?>
                                <span>Hab. <?= tlm_context_safe($tareaContextual['habitacion_numero']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($tareaContextual['trabajador_nombre'])): ?>
                                <span><?= tlm_context_safe($tareaContextual['trabajador_nombre']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="tlm-context-pill"><?= tlm_context_safe($tlmEstadoLabels[$estado] ?? $estado) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
