<?php
if (!function_exists('tlm_safe')) {
    function tlm_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tlm_date')) {
    function tlm_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

$tarea = is_array($tarea ?? null) ? $tarea : [];
$eventos = is_array($eventos ?? null) ? $eventos : [];
$eventosDisponibles = (bool)($eventosDisponibles ?? false);
$trabajadoresActivos = is_array($trabajadoresActivos ?? null) ? $trabajadoresActivos : [];
$puedeAsignar = (bool)($puedeAsignar ?? false);
$puedeCambiarEstado = (bool)($puedeCambiarEstado ?? false);
$documentosEntidad = is_array($documentosEntidad ?? null) ? $documentosEntidad : [];

$estadoLabels = [
    'pendiente' => 'Pendiente',
    'asignada' => 'Asignada',
    'en_proceso' => 'En proceso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
];
$categoriaLabels = [
    'limpieza' => 'Limpieza',
    'mantenimiento' => 'Mantenimiento',
    'general' => 'General',
];
$prioridadLabels = [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
    'urgente' => 'Urgente',
];

$estado = (string)($tarea['estado'] ?? 'pendiente');
$categoria = (string)($tarea['categoria'] ?? 'general');
$prioridad = (string)($tarea['prioridad'] ?? 'media');
?>

<style>
.tlm-detail{padding:24px;max-width:1180px;margin:0 auto;color:#172033}
.tlm-back{display:inline-flex;align-items:center;gap:8px;color:#475569;text-decoration:none;font-weight:800;margin-bottom:18px}
.tlm-hero{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:22px;box-shadow:0 10px 28px rgba(15,23,42,.06);margin-bottom:18px}
.tlm-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:900}
.tlm-title{font-size:30px;line-height:1.1;margin:6px 0 12px;font-weight:900;color:#111827}
.tlm-row{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
.tlm-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900;background:#eef2ff;color:#3730a3}
.tlm-pill--pendiente{background:#fff7ed;color:#9a3412}
.tlm-pill--asignada{background:#eff6ff;color:#1d4ed8}
.tlm-pill--en_proceso{background:#ecfeff;color:#0e7490}
.tlm-pill--completada{background:#ecfdf5;color:#047857}
.tlm-pill--cancelada{background:#f1f5f9;color:#475569}
.tlm-grid{display:grid;grid-template-columns:1.35fr .85fr;gap:18px}
.tlm-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:18px;box-shadow:0 8px 22px rgba(15,23,42,.05)}
.tlm-card h2{font-size:17px;margin:0 0 14px;color:#111827}
.tlm-desc{color:#475569;line-height:1.6;white-space:pre-wrap}
.tlm-defs{display:grid;grid-template-columns:160px 1fr;gap:10px 14px;font-size:14px}
.tlm-defs dt{color:#64748b;font-weight:800}
.tlm-defs dd{margin:0;color:#172033}
.tlm-defs a{color:#1d4ed8;text-decoration:none;font-weight:800}
.tlm-timeline{display:grid;gap:10px}
.tlm-event{border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#fbfcfe}
.tlm-event strong{display:block;color:#111827}
.tlm-muted{color:#64748b;font-size:13px}
.tlm-empty{padding:28px;text-align:center;color:#64748b;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc}
.tlm-form-row{display:grid;gap:10px}
.tlm-select{width:100%;border:1px solid #d1d5db;border-radius:7px;background:#fff;color:#172033;padding:10px 11px;font-size:14px;min-height:42px}
.tlm-textarea{width:100%;border:1px solid #d1d5db;border-radius:7px;background:#fff;color:#172033;padding:10px 11px;font-size:14px;min-height:72px;resize:vertical}
.tlm-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:7px;background:#172033;color:#fff;font-weight:900;padding:10px 16px;text-decoration:none;min-height:40px;cursor:pointer}
.tlm-btn--success{background:#047857}
.tlm-btn--danger{background:#b91c1c}
.tlm-help{font-size:13px;color:#64748b}
.tlm-actions-stack{display:grid;gap:14px}
@media (max-width:900px){.tlm-grid{grid-template-columns:1fr}.tlm-title{font-size:24px}.tlm-defs{grid-template-columns:1fr}}
</style>

<div class="tlm-detail">
    <a class="tlm-back" href="<?= url('tareas') ?>">
        <i class="fas fa-arrow-left"></i>
        Volver a tareas
    </a>

    <section class="tlm-hero">
        <div class="tlm-kicker">Tarea #<?= (int)($tarea['id'] ?? 0) ?></div>
        <h1 class="tlm-title"><?= tlm_safe($tarea['titulo'] ?? '', 'Tarea operativa') ?></h1>
        <div class="tlm-row">
            <span class="tlm-pill"><?= tlm_safe($categoriaLabels[$categoria] ?? $categoria) ?></span>
            <span class="tlm-pill tlm-pill--<?= tlm_safe($estado) ?>"><?= tlm_safe($estadoLabels[$estado] ?? $estado) ?></span>
            <span class="tlm-pill"><?= tlm_safe($prioridadLabels[$prioridad] ?? $prioridad) ?></span>
            <span class="tlm-pill">Origen <?= tlm_safe($tarea['origen'] ?? 'manual') ?></span>
        </div>
    </section>

    <div class="tlm-grid">
        <section class="tlm-card">
            <h2>Descripcion</h2>
            <?php if (!empty($tarea['descripcion'])): ?>
                <div class="tlm-desc"><?= tlm_safe($tarea['descripcion']) ?></div>
            <?php else: ?>
                <div class="tlm-empty">Esta tarea no tiene descripcion registrada.</div>
            <?php endif; ?>
        </section>

        <section class="tlm-card">
            <h2>Contexto</h2>
            <dl class="tlm-defs">
                <dt>Habitacion</dt>
                <dd>
                    <?php if (!empty($tarea['habitacion_id'])): ?>
                        <a href="<?= url('habitaciones/' . (int)$tarea['habitacion_id']) ?>">Hab. <?= tlm_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?></a>
                        <span class="tlm-muted">(<?= tlm_safe($tarea['habitacion_estado'] ?? '-') ?>)</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </dd>

                <dt>Trabajador</dt>
                <dd>
                    <?php if (!empty($tarea['trabajador_id'])): ?>
                        <a href="<?= url('trabajadores/' . (int)$tarea['trabajador_id']) ?>"><?= tlm_safe($tarea['trabajador_nombre'] ?? 'Trabajador #' . (int)$tarea['trabajador_id']) ?></a>
                        <span class="tlm-muted">(<?= tlm_safe($tarea['trabajador_estado'] ?? '-') ?>)</span>
                    <?php else: ?>
                        Sin asignar
                    <?php endif; ?>
                </dd>

                <dt>Reservacion</dt>
                <dd><?= !empty($tarea['reservacion_id']) ? '#' . (int)$tarea['reservacion_id'] : '-' ?></dd>

                <dt>Huesped</dt>
                <dd><?= tlm_safe($tarea['huesped_nombre'] ?? '', '-') ?></dd>

                <dt>Mantenimiento</dt>
                <dd>
                    <?php if (!empty($tarea['mantenimiento_id'])): ?>
                        #<?= (int)$tarea['mantenimiento_id'] ?> · <?= tlm_safe($tarea['mantenimiento_motivo'] ?? '', '-') ?>
                        <span class="tlm-muted">(<?= tlm_safe($tarea['mantenimiento_estado'] ?? '-') ?>)</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </dd>
            </dl>
        </section>

        <section class="tlm-card">
            <h2>Asignacion</h2>
            <?php if (!$puedeAsignar): ?>
                <div class="tlm-empty">Esta tarea no esta disponible para asignacion en esta fase.</div>
            <?php elseif (empty($trabajadoresActivos)): ?>
                <div class="tlm-empty">No hay trabajadores activos disponibles para asignar en el hotel actual.</div>
            <?php else: ?>
                <form method="POST" action="<?= url('tareas/' . (int)($tarea['id'] ?? 0) . '/asignar') ?>" class="tlm-form-row">
                    <?= csrf_field() ?>
                    <select class="tlm-select" name="trabajador_id" required>
                        <option value="">Seleccionar trabajador activo</option>
                        <?php foreach ($trabajadoresActivos as $trabajador): ?>
                            <?php $trabajadorId = (int)($trabajador['id'] ?? 0); ?>
                            <option value="<?= $trabajadorId ?>" <?= $trabajadorId === (int)($tarea['trabajador_id'] ?? 0) ? 'selected' : '' ?>>
                                <?= tlm_safe($trabajador['nombre_completo'] ?? ('Trabajador #' . $trabajadorId)) ?>
                                <?= !empty($trabajador['rol_laboral']) ? ' - ' . tlm_safe($trabajador['rol_laboral']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="tlm-help">Solo trabajadores activos del hotel actual. No genera pagos, asistencia ni cambios de habitacion.</div>
                    <button class="tlm-btn" type="submit">
                        <i class="fas fa-user-check"></i>
                        Asignar trabajador
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <section class="tlm-card">
            <h2>Estado manual</h2>
            <?php if (!$puedeCambiarEstado): ?>
                <div class="tlm-empty">Esta tarea no tiene cambios de estado disponibles.</div>
            <?php else: ?>
                <div class="tlm-actions-stack">
                    <?php if (in_array($estado, ['pendiente', 'asignada'], true)): ?>
                        <form method="POST" action="<?= url('tareas/' . (int)($tarea['id'] ?? 0) . '/iniciar') ?>">
                            <?= csrf_field() ?>
                            <button class="tlm-btn" type="submit">
                                <i class="fas fa-play"></i>
                                Iniciar
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('tareas/' . (int)($tarea['id'] ?? 0) . '/completar') ?>" class="tlm-form-row">
                        <?= csrf_field() ?>
                        <textarea class="tlm-textarea" name="comentario" maxlength="800" placeholder="Nota de cierre opcional"></textarea>
                        <button class="tlm-btn tlm-btn--success" type="submit">
                            <i class="fas fa-check"></i>
                            Completar
                        </button>
                    </form>

                    <form method="POST" action="<?= url('tareas/' . (int)($tarea['id'] ?? 0) . '/cancelar') ?>" class="tlm-form-row">
                        <?= csrf_field() ?>
                        <textarea class="tlm-textarea" name="comentario" maxlength="800" placeholder="Motivo de cancelacion opcional"></textarea>
                        <button class="tlm-btn tlm-btn--danger" type="submit">
                            <i class="fas fa-ban"></i>
                            Cancelar
                        </button>
                    </form>

                    <div class="tlm-help">Estos cambios solo afectan la tarea. No modifican la disponibilidad de la habitacion ni generan Caja.</div>
                </div>
            <?php endif; ?>
        </section>

        <section class="tlm-card">
            <h2>Fechas y responsables</h2>
            <dl class="tlm-defs">
                <dt>Programada</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_programada'] ?? null)) ?></dd>
                <dt>Limite</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_limite'] ?? null)) ?></dd>
                <dt>Inicio</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_inicio'] ?? null)) ?></dd>
                <dt>Cierre</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_cierre'] ?? null)) ?></dd>
                <dt>Creada por</dt><dd><?= tlm_safe($tarea['creada_por_nombre'] ?? '', '-') ?></dd>
                <dt>Asignada por</dt><dd><?= tlm_safe($tarea['asignada_por_nombre'] ?? '', '-') ?></dd>
                <dt>Cerrada por</dt><dd><?= tlm_safe($tarea['cerrada_por_nombre'] ?? '', '-') ?></dd>
            </dl>
        </section>

        <section class="tlm-card">
            <h2>Eventos</h2>
            <?php if (!$eventosDisponibles): ?>
                <div class="tlm-empty">La tabla de eventos no esta disponible.</div>
            <?php elseif (empty($eventos)): ?>
                <div class="tlm-empty">Sin eventos registrados para esta tarea.</div>
            <?php else: ?>
                <div class="tlm-timeline">
                    <?php foreach ($eventos as $evento): ?>
                        <div class="tlm-event">
                            <strong><?= tlm_safe($evento['tipo_evento'] ?? 'Evento') ?></strong>
                            <span class="tlm-muted">
                                <?= tlm_safe(tlm_date($evento['created_at'] ?? null)) ?>
                                · <?= tlm_safe($evento['usuario_nombre'] ?? '', 'Sistema') ?>
                            </span>
                            <?php if (!empty($evento['comentario'])): ?>
                                <div class="tlm-desc"><?= tlm_safe($evento['comentario']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php View::partial('documentos_entidad', [
        'documentosEntidad' => $documentosEntidad,
        'documentosEntidadContexto' => [
            'tipo' => 'tarea',
            'id' => (int)($tarea['id'] ?? 0),
            'label' => 'Tarea',
        ],
        'documentosEntidadPermiteVerTodos' => true,
        'documentosEntidadPermiteVincular' => true,
    ]); ?>
</div>
