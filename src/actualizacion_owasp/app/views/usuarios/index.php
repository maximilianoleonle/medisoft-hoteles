<?php
/**
 * Vista de listado de usuarios
 * Los Cedros — Paleta sage green / gold / cream
 */
?>

<style>
/* ══════════════════════════════════════════
   LOS CEDROS · Gestión de Usuarios
   ══════════════════════════════════════════ */
:root {
    --lc-green:       #5C7A4E;
    --lc-green-dark:  #4A6340;
    --lc-green-deep:  #3D5234;
    --lc-gold:        #C8A96A;
    --lc-gold-dark:   #B8994A;
    --lc-cream:       #F7F4EE;
}

.usuarios-view { opacity:0; transition:opacity .3s ease; }
.usuarios-view.loaded { opacity:1; }

/* Page background */
.usr-bg { background: linear-gradient(145deg,#EFF4EC 0%,#E8EEE3 50%,#F4F1EC 100%); min-height:100vh; }

/* ── Hero header ─────────────────────────── */
.usr-hero {
    background: linear-gradient(135deg,#3D5234 0%,#4A6340 55%,#5C7A4E 100%);
    position:relative; overflow:hidden;
}
.usr-hero::before {
    content:''; position:absolute;
    top:-50px; right:-50px; width:240px; height:240px;
    border-radius:50%; background:rgba(200,169,106,.08); pointer-events:none;
}
.usr-hero::after {
    content:''; position:absolute;
    bottom:-70px; left:-30px; width:180px; height:180px;
    border-radius:50%; background:rgba(255,255,255,.04); pointer-events:none;
}
.gold-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(200,169,106,.18); border:1px solid rgba(200,169,106,.35);
    color:var(--lc-gold-dark); border-radius:20px;
    padding:3px 11px; font-size:.7rem; font-weight:700;
}

/* ── New user button ─────────────────────── */
.btn-nuevo {
    display:inline-flex; align-items:center; gap:7px;
    background:linear-gradient(135deg,var(--lc-gold),var(--lc-gold-dark));
    color:#3D5234; padding:8px 16px; border-radius:10px;
    font-size:.82rem; font-weight:700; text-decoration:none;
    transition:box-shadow .2s, transform .2s;
    box-shadow:0 3px 10px rgba(200,169,106,.3);
}
.btn-nuevo:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(200,169,106,.4); color:#3D5234; }

/* ── Stat widgets ────────────────────────── */
.usr-widget {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; padding:20px;
    transition:transform .25s, box-shadow .25s;
    position:relative; overflow:hidden;
}
.usr-widget::after {
    content:''; position:absolute; bottom:0; left:0; right:0;
    height:2px; opacity:0; transition:opacity .25s;
    background:var(--w-accent,#5C7A4E);
}
.usr-widget:hover { transform:translateY(-3px); box-shadow:0 10px 26px rgba(92,122,78,.11); }
.usr-widget:hover::after { opacity:1; }
.w-icon {
    width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; font-size:18px;
}

/* ── Avatar ──────────────────────────────── */
.usr-avatar {
    width:40px; height:40px; border-radius:12px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.85rem; font-weight:800;
    background:linear-gradient(135deg,var(--lc-gold),var(--lc-gold-dark));
    color:#3D5234; letter-spacing:.05em;
}

/* ── Table panel ─────────────────────────── */
.usr-panel {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; overflow:hidden;
}
.usr-panel-hd {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark));
    padding:14px 18px;
    display:flex; align-items:center; justify-content:space-between;
}
.usr-th {
    font-size:.67rem; font-weight:700; letter-spacing:.05em;
    text-transform:uppercase; color:#7A9B6A;
    padding:10px 14px; white-space:nowrap;
}
.usr-tr { border-bottom:1px solid #F0F5ED; transition:background .15s; }
.usr-tr:hover { background:#F7FCF4; }
.usr-tr:last-child { border-bottom:none; }

/* ── Role badges ─────────────────────────── */
.rol-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px;
    font-size:.68rem; font-weight:700;
}
.rol-admin        { background:linear-gradient(135deg,#F3E8FF,#E9D5FF); color:#7C3AED; border:1px solid #C084FC; }
.rol-recepcion    { background:linear-gradient(135deg,#EFF6FF,#DBEAFE); color:#2563EB; border:1px solid #93C5FD; }
.rol-limpieza     { background:rgba(92,122,78,.1);                       color:#3D5234; border:1px solid rgba(92,122,78,.25); }
.rol-mantenimiento{ background:linear-gradient(135deg,#FFFBEB,#FEF3C7); color:#D97706; border:1px solid #FDE047; }
.rol-contador     { background:linear-gradient(135deg,#FEF2F2,#FEE2E2); color:#DC2626; border:1px solid #FCA5A5; }

/* ── Status badges ───────────────────────── */
.status-on  { background:rgba(16,185,129,.1); color:#065F46; border:1px solid rgba(16,185,129,.25); display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:.68rem; font-weight:700; }
.status-off { background:rgba(239,68,68,.1);  color:#991B1B; border:1px solid rgba(239,68,68,.25);  display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:.68rem; font-weight:700; }

/* ── Action buttons ──────────────────────── */
.act-btn {
    width:30px; height:30px; border-radius:8px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.72rem; transition:background .15s; border:none; cursor:pointer;
    text-decoration:none;
}
.act-edit   { color:#5C7A4E; background:transparent; }
.act-edit:hover   { background:#EEF4EB; }
.act-deact  { color:#DC2626; background:transparent; }
.act-deact:hover  { background:#FEF2F2; }
.act-act    { color:#059669; background:transparent; }
.act-act:hover    { background:#ECFDF5; }

/* ── Scrollbar ───────────────────────────── */
.lc-scroll::-webkit-scrollbar { width:4px; height:4px; }
.lc-scroll::-webkit-scrollbar-track { background:#F0F5ED; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background:#5C7A4E; }

/* ── User row animation ──────────────────── */
.usr-tr { opacity:0; transform:translateX(-8px); }
.usr-tr.visible { transition:opacity .3s ease, transform .3s ease; opacity:1; transform:translateX(0); }
</style>

<!-- ═══════════════════ USUARIOS PAGE ══════════════════════ -->
<div class="usuarios-view usr-bg">

    <!-- Hero Header -->
    <div class="usr-hero">
        <div class="container mx-auto px-5 sm:px-7 py-5 relative z-10">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1.5">
                        <div style="background:rgba(255,255,255,.12);width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-users text-white text-lg"></i>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white">Gestión de Usuarios</h1>
                    </div>
                    <p class="text-white/55 text-sm ml-14">Control de accesos y permisos del personal · Hotel Los Cedros</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 ml-14 lg:ml-0">
                    <span class="gold-badge"><i class="fas fa-shield-alt text-xs"></i> Administración</span>
                    <?php if (can('usuarios.create')): ?>
                    <a href="<?= url('usuarios/create') ?>" class="btn-nuevo">
                        <i class="fas fa-plus text-xs"></i> Nuevo Usuario
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-5 sm:px-7 py-6 max-w-none">

        <!-- Stat Widgets -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

            <!-- Total -->
            <div class="usr-widget" style="--w-accent:#5C7A4E">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-icon" style="background:rgba(92,122,78,.1);color:#5C7A4E;">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="text-2xl font-bold text-[#3D5234]"><?= count($usuarios) ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Usuarios</p>
                <div class="flex items-baseline gap-1.5 mt-1.5">
                    <span class="text-lg font-bold text-[#3D5234]">
                        <?= count(array_filter($usuarios, fn($u) => $u['activo'])) ?>
                    </span>
                    <span class="text-xs text-gray-400">activos en el sistema</span>
                </div>
            </div>

            <!-- Actividad reciente -->
            <div class="usr-widget" style="--w-accent:#10b981">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-icon" style="background:rgba(16,185,129,.1);color:#059669;">
                        <i class="fas fa-signal"></i>
                    </div>
                    <span class="text-2xl font-bold text-emerald-600">
                        <?php
                        echo count(array_filter($usuarios, fn($u) =>
                            $u['ultimo_login'] && strtotime($u['ultimo_login']) > strtotime('-24 hours')
                        ));
                        ?>
                    </span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Actividad Reciente</p>
                <p class="text-xs text-gray-400 mt-1.5">Últimas 24 horas</p>
            </div>

            <!-- Roles -->
            <div class="usr-widget" style="--w-accent:#8b5cf6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-icon" style="background:rgba(139,92,246,.1);color:#7C3AED;">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <span class="text-2xl font-bold text-violet-700">
                        <?= count(array_unique(array_column($usuarios, 'rol'))) ?>
                    </span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Roles Activos</p>
                <?php
                $rolesCounts = array_count_values(array_column($usuarios, 'rol'));
                $topRole = array_search(max($rolesCounts), $rolesCounts);
                ?>
                <p class="text-xs text-gray-400 mt-1.5">
                    Mayor: <span class="font-semibold text-gray-600"><?= ucfirst($topRole) ?> (<?= $rolesCounts[$topRole] ?>)</span>
                </p>
            </div>
        </div>

        <!-- Users Table -->
        <div class="usr-panel">
            <!-- Panel header -->
            <div class="usr-panel-hd">
                <div class="flex items-center gap-2.5">
                    <div style="background:rgba(255,255,255,.18);width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-list text-white text-xs"></i>
                    </div>
                    <h3 class="text-sm font-bold text-white">Usuarios del Sistema</h3>
                </div>
                <span class="bg-white/20 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                    <?= count($usuarios) ?>
                </span>
            </div>

            <?php if (empty($usuarios)): ?>
                <div class="text-center py-16">
                    <i class="fas fa-users text-5xl mb-4" style="color:#D5E4CB"></i>
                    <h3 class="text-base font-bold text-gray-600 mb-1">No hay usuarios registrados</h3>
                    <p class="text-sm text-gray-400 mb-5">Aún no se han creado usuarios en el sistema.</p>
                    <a href="<?= url('usuarios/create') ?>" class="btn-nuevo">
                        <i class="fas fa-plus text-xs"></i> Crear primer usuario
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto lc-scroll">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-[#EAF0E5]">
                                <th class="usr-th text-left">Usuario</th>
                                <th class="usr-th text-left">Información</th>
                                <th class="usr-th text-left">Rol</th>
                                <th class="usr-th text-left">Estado</th>
                                <th class="usr-th text-left">Último Acceso</th>
                                <th class="usr-th text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr class="usr-tr">
                                <!-- Avatar + username -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="usr-avatar">
                                            <?= strtoupper(substr($usuario['nombre_usuario'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800"><?= htmlspecialchars($usuario['nombre_usuario']) ?></p>
                                            <p class="text-xs text-gray-400">ID: #<?= $usuario['id'] ?></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Info -->
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold text-gray-800"><?= htmlspecialchars($usuario['nombre_completo']) ?></p>
                                    <?php if ($usuario['email']): ?>
                                        <p class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                                            <i class="fas fa-envelope text-xs"></i>
                                            <?= htmlspecialchars($usuario['email']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($usuario['telefono']): ?>
                                        <p class="text-xs text-gray-400 flex items-center gap-1">
                                            <i class="fas fa-phone text-xs"></i>
                                            <?= htmlspecialchars($usuario['telefono']) ?>
                                        </p>
                                    <?php endif; ?>
                                </td>

                                <!-- Rol -->
                                <td class="px-4 py-3">
                                    <span class="rol-badge rol-<?= $usuario['rol'] ?>">
                                        <?= ucfirst($usuario['rol']) ?>
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td class="px-4 py-3">
                                    <?php if ($usuario['activo']): ?>
                                        <span class="status-on">
                                            <i class="fas fa-check-circle text-xs"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="status-off">
                                            <i class="fas fa-times-circle text-xs"></i> Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Último acceso -->
                                <td class="px-4 py-3">
                                    <?php if ($usuario['ultimo_login']): ?>
                                        <p class="text-xs font-semibold text-gray-800"><?= format_datetime($usuario['ultimo_login']) ?></p>
                                        <p class="text-xs text-gray-400">
                                            <?php
                                            $hace = time() - strtotime($usuario['ultimo_login']);
                                            if ($hace < 3600) echo 'Hace ' . round($hace/60) . ' min';
                                            elseif ($hace < 86400) echo 'Hace ' . round($hace/3600) . ' h';
                                            else echo 'Hace ' . round($hace/86400) . ' días';
                                            ?>
                                        </p>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-300 italic">Nunca</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="<?= url("usuarios/{$usuario['id']}/edit") ?>"
                                           class="act-btn act-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($usuario['id'] != user_id()): ?>
                                            <?php if ($usuario['activo']): ?>
                                                <button onclick="cambiarEstadoUsuario(<?= (int) $usuario['id'] ?>, false, <?= json_encode($usuario['nombre_completo'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)"
                                                        class="act-btn act-deact" title="Desactivar">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button onclick="cambiarEstadoUsuario(<?= (int) $usuario['id'] ?>, true, <?= json_encode($usuario['nombre_completo'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)"
                                                        class="act-btn act-act" title="Activar">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function cambiarEstadoUsuario(id, activar, nombre) {
    Swal.fire({
        title: activar ? '¿Activar usuario?' : '¿Desactivar usuario?',
        text: activar
            ? `Se habilitará el acceso al sistema para ${nombre}`
            : `Se bloqueará el acceso al sistema para ${nombre}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: activar ? '#059669' : '#DC2626',
        cancelButtonColor: '#5C7A4E',
        confirmButtonText: activar
            ? '<i class="fas fa-check mr-2"></i>Sí, activar'
            : '<i class="fas fa-ban mr-2"></i>Sí, desactivar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('usuarios/') ?>' + id + '/toggle';
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = 'csrf_token';
            csrf.value = '<?= csrf_token() ?>';
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.usuarios-view');
    if (view) view.classList.add('loaded');

    document.querySelectorAll('.usr-tr').forEach((row, i) => {
        setTimeout(() => row.classList.add('visible'), i * 45);
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
