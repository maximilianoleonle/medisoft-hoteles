<?php
header('Content-Type: text/html; charset=UTF-8');
/**
 * Vista de Detalle de Solicitud de Factura
 * Los Cedros
 */

$solicitud = $solicitud ?? [];
$habitaciones = $habitaciones ?? [];
$pagos = $pagos ?? [];
$notas_reservacion = $notas_reservacion ?? [];
$regimenes_fiscales = $regimenes_fiscales ?? [];
$usos_cfdi = $usos_cfdi ?? [];
$mensaje = get_mensaje();

// Info de estatus
$estatus_info = [
    'pendiente'  => ['label' => 'Pendiente',  'color' => '#F59E0B', 'bg' => '#FFFBEB', 'border' => '#FDE68A', 'icon' => 'clock'],
    'en_proceso' => ['label' => 'En Proceso', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'border' => '#93C5FD', 'icon' => 'spinner'],
    'completada' => ['label' => 'Completada', 'color' => '#10B981', 'bg' => '#ECFDF5', 'border' => '#6EE7B7', 'icon' => 'check-circle'],
    'cancelada'  => ['label' => 'Cancelada',  'color' => '#6B7280', 'bg' => '#F9FAFB', 'border' => '#D1D5DB', 'icon' => 'times-circle'],
];

$est = $estatus_info[$solicitud['estatus']] ?? $estatus_info['pendiente'];
$es_editable = in_array($solicitud['estatus'], ['pendiente', 'en_proceso']);
?>

<style>
:root {
    --hotel-brown: #8B4513;
    --hotel-brown-dark: #6B3410;
    --hotel-gold: #FFD700;
    --hotel-cream: #FFF8DC;
    --hotel-blue: #3B82F6;
    --hotel-green: #10B981;
    --hotel-red: #EF4444;
    --hotel-purple: #9333EA;
    --hotel-orange: #F97316;
}

.detalle-factura {
    min-height: 100vh;
    background: linear-gradient(to bottom, #F9FAFB, #F3F4F6);
    animation: fadeIn 0.3s ease forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Header */
.df-header {
    background: linear-gradient(135deg, var(--hotel-brown) 0%, var(--hotel-brown-dark) 100%);
    color: white;
    padding: 1rem 0;
    box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
    position: sticky;
    top: 0;
    z-index: 40;
}

.df-header a { color: var(--hotel-gold); transition: all 0.2s; text-decoration: none; }
.df-header a:hover { color: white; }

/* Cards */
.df-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 2px solid transparent;
    transition: all 0.3s ease;
    overflow: hidden;
}

.df-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

.df-card-header {
    padding: 0.875rem 1.25rem;
    background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
    border-bottom: 2px solid #F3F4F6;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.df-card-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.df-card-body {
    padding: 1.25rem;
}

/* Info rows */
.df-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0;
    border-bottom: 1px dashed #E5E7EB;
}

.df-row:last-child { border-bottom: none; }

.df-row:hover {
    background: #F9FAFB;
    margin: 0 -0.5rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    border-radius: 0.375rem;
}

.df-label {
    font-size: 0.75rem;
    color: #6B7280;
    font-weight: 500;
}

.df-value {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1F2937;
}

/* Form inputs */
.df-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 2px solid #E5E7EB;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s;
    background: white;
}

.df-input:focus {
    outline: none;
    border-color: var(--hotel-brown);
    box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
}

.df-input:disabled {
    background: #F3F4F6;
    color: #6B7280;
    cursor: not-allowed;
}

.df-input-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #374151;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.df-input-hint {
    font-size: 0.6875rem;
    color: #9CA3AF;
    margin-top: 0.25rem;
}

/* Buttons */
.df-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 0.625rem;
    font-weight: 600;
    font-size: 0.8125rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    text-decoration: none;
}

.df-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.df-btn-primary {
    background: linear-gradient(135deg, var(--hotel-brown) 0%, var(--hotel-brown-dark) 100%);
    color: white;
}

.df-btn-success {
    background: linear-gradient(135deg, var(--hotel-green) 0%, #059669 100%);
    color: white;
}

.df-btn-danger {
    background: linear-gradient(135deg, var(--hotel-red) 0%, #DC2626 100%);
    color: white;
}

.df-btn-secondary {
    background: #F3F4F6;
    color: #374151;
    border: 2px solid #E5E7EB;
}

/* Tipo badge grande */
.tipo-badge-lg {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 700;
}

.tipo-cliente {
    background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
    color: #1E40AF;
    border: 2px solid #93C5FD;
}

.tipo-uso-interno {
    background: linear-gradient(135deg, #F5F3FF, #EDE9FE);
    color: #6D28D9;
    border: 2px solid #C4B5FD;
}

/* Progreso visual */
.progress-steps {
    display: flex;
    align-items: center;
    gap: 0;
    margin: 1rem 0;
}

.progress-step {
    flex: 1;
    text-align: center;
    position: relative;
}

.progress-step::after {
    content: '';
    position: absolute;
    top: 1rem;
    left: 50%;
    width: 100%;
    height: 3px;
    background: #E5E7EB;
}

.progress-step:last-child::after { display: none; }

.progress-step.active::after {
    background: linear-gradient(90deg, var(--hotel-green), #E5E7EB);
}

.progress-step.completed::after {
    background: var(--hotel-green);
}

.progress-dot {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
    border: 3px solid #E5E7EB;
    background: white;
    color: #9CA3AF;
}

.progress-step.active .progress-dot {
    border-color: var(--hotel-blue);
    background: var(--hotel-blue);
    color: white;
}

.progress-step.completed .progress-dot {
    border-color: var(--hotel-green);
    background: var(--hotel-green);
    color: white;
}

.progress-label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 600;
    margin-top: 0.375rem;
    color: #9CA3AF;
}

.progress-step.active .progress-label { color: var(--hotel-blue); }
.progress-step.completed .progress-label { color: var(--hotel-green); }

/* Flash message */
.flash-msg {
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.flash-success { background: #ECFDF5; color: #065F46; border: 2px solid #6EE7B7; }
.flash-error { background: #FEF2F2; color: #991B1B; border: 2px solid #FCA5A5; }

/* Modal overlay */
.modal-overlay-factura {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.2s ease;
}

.modal-overlay-factura.hidden { display: none; }

.modal-factura {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    width: 380px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Responsive */
@media (max-width: 1024px) {
    .df-header { position: relative; }
    .grid-2-cols { grid-template-columns: 1fr !important; }
}

@media (max-width: 640px) {
    .df-card { border-radius: 0.75rem; }
    .df-card-body { padding: 0.875rem; }
    .df-btn { padding: 0.5rem 0.875rem; font-size: 0.75rem; }
}
</style>

<div class="detalle-factura">
    <!-- Header -->
    <div class="df-header">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="<?= url('facturacion') ?>" class="hover:scale-110 transition-transform">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-xl font-bold">
                                Solicitud de Factura #<?= $solicitud['id'] ?>
                            </h1>
                            <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background: <?= $est['bg'] ?>; color: <?= $est['color'] ?>; border: 1px solid <?= $est['border'] ?>;">
                                <i class="fas fa-<?= $est['icon'] ?>" style="font-size: 0.625rem;"></i>
                                <?= $est['label'] ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-xs opacity-90 mt-1">
                            <span>
                                <i class="fas fa-<?= $solicitud['tipo'] === 'cliente' ? 'user' : 'building' ?> mr-1" style="color: var(--hotel-gold);"></i>
                                <?= $solicitud['tipo'] === 'cliente' ? 'Factura Cliente' : 'Público General' ?>
                            </span>
                            <span>
                                <i class="fas fa-bed mr-1" style="color: var(--hotel-gold);"></i>
                                Reservación #<?= $solicitud['reservacion_id'] ?>
                            </span>
                            <span>
                                <i class="fas fa-calendar mr-1" style="color: var(--hotel-gold);"></i>
                                <?= date('d/m/Y H:i', strtotime($solicitud['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones header -->
                <div class="flex items-center gap-2">
                    <a href="<?= url('reservaciones/ver/' . $solicitud['reservacion_id']) ?>" 
                       class="df-btn df-btn-secondary" style="font-size: 0.75rem; padding: 0.5rem 0.875rem;">
                        <i class="fas fa-bed"></i> Ver Reservación
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6" style="max-width: 1100px;">
        
        <!-- Flash Message -->
        <?php if ($mensaje): ?>
            <div class="flash-msg flash-<?= $mensaje['tipo'] ?>" style="margin-bottom: 1rem;">
                <i class="fas fa-<?= $mensaje['tipo'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($mensaje['texto']) ?>
            </div>
        <?php endif; ?>

        <!-- Barra de Progreso -->
        <div class="df-card" style="margin-bottom: 1.5rem;">
            <div class="df-card-body" style="padding: 1rem 1.5rem;">
                <div class="progress-steps">
                    <div class="progress-step <?= in_array($solicitud['estatus'], ['pendiente', 'en_proceso', 'completada']) ? 'completed' : '' ?>">
                        <span class="progress-dot"><i class="fas fa-sign-in-alt"></i></span>
                        <span class="progress-label">Check-in</span>
                    </div>
                    <div class="progress-step <?= in_array($solicitud['estatus'], ['en_proceso', 'completada']) ? 'completed' : ($solicitud['estatus'] === 'pendiente' ? 'active' : '') ?>">
                        <span class="progress-dot"><i class="fas fa-edit"></i></span>
                        <span class="progress-label">Datos Fiscales</span>
                    </div>
                    <div class="progress-step <?= $solicitud['estatus'] === 'completada' ? 'completed' : ($solicitud['estatus'] === 'en_proceso' ? 'active' : '') ?>">
                        <span class="progress-dot"><i class="fas fa-file-invoice"></i></span>
                        <span class="progress-label">Facturar en Aspel</span>
                    </div>
                    <div class="progress-step <?= $solicitud['estatus'] === 'completada' ? 'completed' : '' ?>">
                        <span class="progress-dot"><i class="fas fa-check"></i></span>
                        <span class="progress-label">Completada</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-2-cols" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            
            <!-- COLUMNA IZQUIERDA -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <!-- Info de Reservación -->
                <div class="df-card" style="border-color: #60A5FA;">
                    <div class="df-card-header">
                        <div class="df-card-icon" style="background: linear-gradient(135deg, #DBEAFE, #BFDBFE); color: var(--hotel-blue);">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937;">Reservación #<?= $solicitud['reservacion_id'] ?></h3>
                            <p style="font-size: 0.6875rem; color: #6B7280;">Datos del hospedaje</p>
                        </div>
                    </div>
                    <div class="df-card-body">
                        <div class="df-row">
                            <span class="df-label">Huésped</span>
                            <span class="df-value"><?= htmlspecialchars($solicitud['huesped_nombre']) ?></span>
                        </div>
                        <?php if (!empty($solicitud['huesped_telefono'])): ?>
                        <div class="df-row">
                            <span class="df-label">Teléfono</span>
                            <span class="df-value">
                                <a href="tel:<?= $solicitud['huesped_telefono'] ?>" style="color: var(--hotel-blue); text-decoration: none;">
                                    <?= htmlspecialchars($solicitud['huesped_telefono']) ?>
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($solicitud['huesped_email'])): ?>
                        <div class="df-row">
                            <span class="df-label">Email</span>
                            <span class="df-value" style="font-size: 0.8125rem;"><?= htmlspecialchars($solicitud['huesped_email']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="df-row">
                            <span class="df-label">Entrada</span>
                            <span class="df-value"><?= date('d/m/Y', strtotime($solicitud['fecha_entrada'])) ?></span>
                        </div>
                        <div class="df-row">
                            <span class="df-label">Salida</span>
                            <span class="df-value"><?= date('d/m/Y', strtotime($solicitud['fecha_salida'])) ?></span>
                        </div>
                        <div class="df-row">
                            <span class="df-label">Habitaciones</span>
                            <span class="df-value">
                                <?php foreach ($habitaciones as $hab): ?>
                                    <span style="display: inline-block; background: #F3F4F6; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; margin-right: 0.25rem;">
                                        #<?= htmlspecialchars($hab['numero']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </span>
                        </div>
                        
                        <!-- Monto destacado -->
                        <div style="background: linear-gradient(135deg, var(--hotel-gold), #FFC700); color: var(--hotel-brown-dark); padding: 0.875rem; border-radius: 0.75rem; text-align: center; margin-top: 0.75rem; box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);">
                            <p style="font-size: 0.6875rem; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.05em;">Total a Facturar</p>
                            <p style="font-size: 1.75rem; font-weight: 800;">$<?= number_format($solicitud['monto_total'], 2) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Pagos Registrados -->
                <?php if (!empty($pagos)): ?>
                <div class="df-card" style="border-color: #A78BFA;">
                    <div class="df-card-header">
                        <div class="df-card-icon" style="background: linear-gradient(135deg, #EDE9FE, #DDD6FE); color: var(--hotel-purple);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937;">Pagos Registrados</h3>
                            <p style="font-size: 0.6875rem; color: #6B7280;">Movimientos de caja asociados</p>
                        </div>
                    </div>
                    <div class="df-card-body">
                        <?php foreach ($pagos as $pago): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px dashed #E5E7EB;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <?php
                                    $metodo_icons = ['efectivo' => 'money-bill-wave', 'tarjeta' => 'credit-card', 'transferencia' => 'exchange-alt'];
                                    $metodo_colors = ['efectivo' => '#10B981', 'tarjeta' => '#3B82F6', 'transferencia' => '#9333EA'];
                                    ?>
                                    <i class="fas fa-<?= $metodo_icons[$pago['metodo_pago']] ?? 'circle' ?>" 
                                       style="color: <?= $metodo_colors[$pago['metodo_pago']] ?? '#6B7280' ?>;"></i>
                                    <div>
                                        <span style="font-size: 0.8125rem; font-weight: 600;"><?= ucfirst($pago['metodo_pago'] ?? '') ?></span>
                                        <?php if (!empty($pago['referencia'])): ?>
                                            <span style="font-size: 0.6875rem; color: #6B7280; display: block;">Ref: <?= htmlspecialchars($pago['referencia']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span style="font-weight: 700; font-size: 0.875rem;">$<?= number_format($pago['monto'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notas de facturación -->
                <?php if (!empty($solicitud['notas'])): ?>
                <div class="df-card">
                    <div class="df-card-header">
                        <div class="df-card-icon" style="background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #D97706;">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937;">Notas de Facturación</h3>
                    </div>
                    <div class="df-card-body">
                        <p style="font-size: 0.8125rem; color: #4B5563; white-space: pre-line; line-height: 1.6;">
                            <?= htmlspecialchars($solicitud['notas']) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notas de la Reservación -->
                <?php if (!empty($notas_reservacion)): ?>
                <div class="df-card">
                    <div class="df-card-header">
                        <div class="df-card-icon" style="background: linear-gradient(135deg, #E0E7FF, #C7D2FE); color: #4F46E5;">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937;">Notas de la Reservación</h3>
                        <span style="margin-left: auto; background: #4F46E5; color: #fff; font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;">
                            <?= count($notas_reservacion) ?>
                        </span>
                    </div>
                    <div class="df-card-body" style="display: flex; flex-direction: column; gap: 0.625rem;">
                        <?php foreach ($notas_reservacion as $nota): ?>
                        <div style="background: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 8px; padding: 0.625rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                <span style="font-size: 0.75rem; font-weight: 600; color: #4F46E5;">
                                    <i class="fas fa-user-circle" style="margin-right: 4px;"></i>
                                    <?= htmlspecialchars($nota['usuario_nombre']) ?>
                                </span>
                                <span style="font-size: 0.6875rem; color: #6B7280;">
                                    <?= date('d/m/Y H:i', strtotime($nota['created_at'])) ?>
                                </span>
                            </div>
                            <p style="font-size: 0.8125rem; color: #374151; white-space: pre-wrap; margin: 0; line-height: 1.5;">
                                <?= nl2br(htmlspecialchars($nota['nota'])) ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- COLUMNA DERECHA -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <!-- Tipo de Solicitud -->
                <div class="df-card">
                    <div class="df-card-body" style="text-align: center; padding: 1.5rem;">
                        <?php if ($solicitud['tipo'] === 'cliente'): ?>
                            <div class="tipo-badge-lg tipo-cliente" style="display: inline-flex; margin-bottom: 0.75rem;">
                                <i class="fas fa-user"></i>
                                Factura Solicitada por Cliente
                            </div>
                            <p style="font-size: 0.8125rem; color: #4B5563;">
                                El cliente solicitó factura durante el check-in.
                                <br>Complete los datos fiscales para proceder.
                            </p>
                        <?php else: ?>
                            <div class="tipo-badge-lg tipo-uso-interno" style="display: inline-flex; margin-bottom: 0.75rem;">
                                <i class="fas fa-building"></i>
                                Factura de Público General
                            </div>
                            <p style="font-size: 0.8125rem; color: #4B5563;">
                                Registro automático por pago con tarjeta/transferencia.
                                <br>El cliente <strong>no</strong> solicitó factura.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulario de Datos Fiscales -->
                <div class="df-card" style="border-color: <?= $es_editable ? '#34D399' : '#D1D5DB' ?>;">
                    <div class="df-card-header">
                        <div class="df-card-icon" style="background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: var(--hotel-green);">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937;">Datos Fiscales</h3>
                            <p style="font-size: 0.6875rem; color: #6B7280;">
                                <?= $es_editable ? 'Ingrese los datos para facturación' : 'Datos registrados' ?>
                            </p>
                        </div>
                    </div>
                    <div class="df-card-body">
                        <form method="POST" action="<?= url('facturacion/guardar') ?>" id="formDatosFiscales">
                            <?= csrf_field() ?>
                            <input type="hidden" name="solicitud_id" value="<?= $solicitud['id'] ?>">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <!-- RFC -->
                                <div style="grid-column: span 2;">
                                    <label class="df-input-label">
                                        <i class="fas fa-id-card" style="color: var(--hotel-brown); margin-right: 0.25rem;"></i>
                                        RFC <?= $solicitud['tipo'] === 'cliente' ? '<span style="color: #EF4444;">*</span>' : '' ?>
                                    </label>
                                    <input type="text" name="rfc" class="df-input" 
                                           value="<?= htmlspecialchars($solicitud['rfc'] ?? '') ?>"
                                           placeholder="XAXX010101000"
                                           maxlength="13"
                                           style="text-transform: uppercase; font-family: monospace; font-size: 1rem; letter-spacing: 0.1em;"
                                           <?= !$es_editable ? 'disabled' : '' ?>>
                                    <p class="df-input-hint">Persona física: 13 caracteres | Persona moral: 12 caracteres</p>
                                </div>

                                <!-- Razón Social -->
                                <div style="grid-column: span 2;">
                                    <label class="df-input-label">
                                        <i class="fas fa-building" style="color: var(--hotel-brown); margin-right: 0.25rem;"></i>
                                        Razón Social <?= $solicitud['tipo'] === 'cliente' ? '<span style="color: #EF4444;">*</span>' : '' ?>
                                    </label>
                                    <input type="text" name="razon_social" class="df-input" 
                                           value="<?= htmlspecialchars($solicitud['razon_social'] ?? '') ?>"
                                           placeholder="Nombre o razón social"
                                           <?= !$es_editable ? 'disabled' : '' ?>>
                                </div>

                                <!-- Régimen Fiscal -->
                                <div>
                                    <label class="df-input-label">
                                        <i class="fas fa-balance-scale" style="color: var(--hotel-brown); margin-right: 0.25rem;"></i>
                                        Régimen Fiscal <?= $solicitud['tipo'] === 'cliente' ? '<span style="color: #EF4444;">*</span>' : '' ?>
                                    </label>
                                    <select name="regimen_fiscal" class="df-input" <?= !$es_editable ? 'disabled' : '' ?>>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($regimenes_fiscales as $clave => $nombre): ?>
                                            <option value="<?= $clave ?>" <?= ($solicitud['regimen_fiscal'] ?? '') === $clave ? 'selected' : '' ?>>
                                                <?= $clave ?> - <?= $nombre ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Uso CFDI -->
                                <div>
                                    <label class="df-input-label">
                                        <i class="fas fa-tag" style="color: var(--hotel-brown); margin-right: 0.25rem;"></i>
                                        Uso de CFDI <?= $solicitud['tipo'] === 'cliente' ? '<span style="color: #EF4444;">*</span>' : '' ?>
                                    </label>
                                    <select name="uso_cfdi" class="df-input" <?= !$es_editable ? 'disabled' : '' ?>>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($usos_cfdi as $clave => $nombre): ?>
                                            <option value="<?= $clave ?>" <?= ($solicitud['uso_cfdi'] ?? '') === $clave ? 'selected' : '' ?>>
                                                <?= $clave ?> - <?= $nombre ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Código Postal Fiscal -->
                                <div>
                                    <label class="df-input-label">
                                        <i class="fas fa-map-pin" style="color: var(--hotel-brown); margin-right: 0.25rem;"></i>
                                        C.P. Fiscal <?= $solicitud['tipo'] === 'cliente' ? '<span style="color: #EF4444;">*</span>' : '' ?>
                                    </label>
                                    <input type="text" name="codigo_postal_fiscal" class="df-input" 
                                           value="<?= htmlspecialchars($solicitud['codigo_postal_fiscal'] ?? '') ?>"
                                           placeholder="00000"
                                           maxlength="5"
                                           pattern="\d{5}"
                                           style="font-family: monospace;"
                                           <?= !$es_editable ? 'disabled' : '' ?>>
                                </div>

                                <!-- Email Factura -->
                                <div>
                                    <label class="df-input-label">
                                        <i class="fas fa-envelope" style="color: var(--hotel-brown); margin-right: 0.25rem;"></i>
                                        Email para Factura
                                    </label>
                                    <input type="email" name="email_factura" class="df-input" 
                                           value="<?= htmlspecialchars($solicitud['email_factura'] ?? '') ?>"
                                           placeholder="correo@ejemplo.com"
                                           <?= !$es_editable ? 'disabled' : '' ?>>
                                </div>

                                <!-- Notas -->
                                <div style="grid-column: span 2;">
                                    <label class="df-input-label">
                                        <i class="fas fa-sticky-note" style="color: var(--hotel-brown); margin-right: 0.25rem;"></i>
                                        Notas Adicionales
                                    </label>
                                    <textarea name="notas" class="df-input" rows="2" 
                                              placeholder="Observaciones para facturación..."
                                              style="resize: vertical;"
                                              <?= !$es_editable ? 'disabled' : '' ?>><?= htmlspecialchars($solicitud['notas'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <?php if ($es_editable): ?>
                                <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
                                    <button type="submit" class="df-btn df-btn-primary" style="flex: 1;">
                                        <i class="fas fa-save"></i> Guardar Datos
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="df-card" style="border-color: #FDE68A;">
                    <div class="df-card-header">
                        <div class="df-card-icon" style="background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #D97706;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #1F2937;">Acciones</h3>
                    </div>
                    <div class="df-card-body">
                        
                        <?php if ($solicitud['estatus'] === 'pendiente'): ?>
                            <!-- Marcar en proceso -->
                            <form method="POST" action="<?= url('facturacion/en-proceso') ?>" style="margin-bottom: 0.75rem;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="solicitud_id" value="<?= $solicitud['id'] ?>">
                                <button type="submit" class="df-btn" style="width: 100%; background: linear-gradient(135deg, #3B82F6, #2563EB); color: white;">
                                    <i class="fas fa-spinner"></i> Marcar "En Proceso"
                                </button>
                            </form>
                            <p style="font-size: 0.6875rem; color: #6B7280; text-align: center; margin-bottom: 0.75rem;">
                                Indica que ya se están capturando los datos fiscales
                            </p>
                        <?php endif; ?>

                        <?php if (in_array($solicitud['estatus'], ['pendiente', 'en_proceso'])): ?>
                            <!-- Marcar como facturada -->
                            <button type="button" onclick="abrirModalCompletar()" class="df-btn df-btn-success" style="width: 100%; margin-bottom: 0.75rem;">
                                <i class="fas fa-check-circle"></i> Marcar como Facturada
                            </button>
                            <p style="font-size: 0.6875rem; color: #6B7280; text-align: center; margin-bottom: 1rem;">
                                Cuando ya se haya generado la factura en Aspel
                            </p>

                            <hr style="border: none; border-top: 2px dashed #E5E7EB; margin: 1rem 0;">

                            <!-- Cancelar -->
                            <button type="button" onclick="abrirModalCancelar()" class="df-btn df-btn-danger" style="width: 100%; opacity: 0.8;">
                                <i class="fas fa-times-circle"></i> Cancelar Solicitud
                            </button>
                        <?php endif; ?>

                        <?php if ($solicitud['estatus'] === 'completada'): ?>
                            <div style="text-align: center; padding: 1rem;">
                                <i class="fas fa-check-circle" style="font-size: 2.5rem; color: var(--hotel-green); margin-bottom: 0.75rem;"></i>
                                <p style="font-size: 1rem; font-weight: 700; color: #065F46;">Factura Completada</p>
                                <?php if (!empty($solicitud['numero_factura'])): ?>
                                    <p style="font-size: 0.875rem; color: #6B7280; margin-top: 0.25rem;">
                                        Nº Factura: <strong style="font-family: monospace;"><?= htmlspecialchars($solicitud['numero_factura']) ?></strong>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($solicitud['fecha_facturada'])): ?>
                                    <p style="font-size: 0.75rem; color: #9CA3AF; margin-top: 0.25rem;">
                                        Facturada el <?= date('d/m/Y H:i', strtotime($solicitud['fecha_facturada'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($solicitud['estatus'] === 'cancelada'): ?>
                            <div style="text-align: center; padding: 1rem;">
                                <i class="fas fa-times-circle" style="font-size: 2.5rem; color: #9CA3AF; margin-bottom: 0.75rem;"></i>
                                <p style="font-size: 1rem; font-weight: 700; color: #6B7280;">Solicitud Cancelada</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Info de registro -->
                        <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #E5E7EB;">
                            <p style="font-size: 0.6875rem; color: #9CA3AF;">
                                <i class="fas fa-user" style="margin-right: 0.25rem;"></i>
                                Registrado por: <?= htmlspecialchars($solicitud['registrado_por'] ?? 'Sistema') ?>
                            </p>
                            <p style="font-size: 0.6875rem; color: #9CA3AF; margin-top: 0.125rem;">
                                <i class="fas fa-clock" style="margin-right: 0.25rem;"></i>
                                Creado: <?= date('d/m/Y H:i', strtotime($solicitud['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Marcar como Facturada -->
<div id="modalCompletar" class="modal-overlay-factura hidden">
    <div class="modal-factura">
        <div style="padding: 1rem 1.25rem; border-bottom: 2px solid #D1FAE5; background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); border-radius: 1rem 1rem 0 0;">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #065F46;">
                <i class="fas fa-check-circle" style="color: var(--hotel-green); margin-right: 0.5rem;"></i>
                Marcar como Facturada
            </h3>
        </div>
        <form method="POST" action="<?= url('facturacion/completar') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="solicitud_id" value="<?= $solicitud['id'] ?>">
            
            <div style="padding: 1.25rem;">
                <p style="font-size: 0.875rem; color: #4B5563; margin-bottom: 1rem;">
                    ¿Ya generó la factura en Aspel? Ingrese el número de factura (opcional):
                </p>
                
                <div style="margin-bottom: 1rem;">
                    <label class="df-input-label">Número de Factura</label>
                    <input type="text" name="numero_factura" class="df-input" 
                           placeholder="Ej: FA-001234"
                           style="font-family: monospace; font-size: 1rem;">
                    <p class="df-input-hint">Opcional - El folio de la factura generada en Aspel</p>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="cerrarModalCompletar()" class="df-btn df-btn-secondary" style="flex: 1;">
                        Cancelar
                    </button>
                    <button type="submit" class="df-btn df-btn-success" style="flex: 1;">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cancelar Solicitud -->
<div id="modalCancelar" class="modal-overlay-factura hidden">
    <div class="modal-factura">
        <div style="padding: 1rem 1.25rem; border-bottom: 2px solid #FCA5A5; background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%); border-radius: 1rem 1rem 0 0;">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #991B1B;">
                <i class="fas fa-times-circle" style="color: var(--hotel-red); margin-right: 0.5rem;"></i>
                Cancelar Solicitud
            </h3>
        </div>
        <form method="POST" action="<?= url('facturacion/cancelar') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="solicitud_id" value="<?= $solicitud['id'] ?>">
            
            <div style="padding: 1.25rem;">
                <p style="font-size: 0.875rem; color: #4B5563; margin-bottom: 1rem;">
                    ¿Está seguro de cancelar esta solicitud de factura?
                </p>
                
                <div style="margin-bottom: 1rem;">
                    <label class="df-input-label">Motivo de cancelación</label>
                    <textarea name="motivo" class="df-input" rows="2" 
                              placeholder="Motivo de la cancelación..."
                              style="resize: vertical;"></textarea>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="cerrarModalCancelar()" class="df-btn df-btn-secondary" style="flex: 1;">
                        No, volver
                    </button>
                    <button type="submit" class="df-btn df-btn-danger" style="flex: 1;">
                        <i class="fas fa-times"></i> Sí, Cancelar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// RFC auto uppercase
document.querySelector('input[name="rfc"]')?.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// CP only numbers
document.querySelector('input[name="codigo_postal_fiscal"]')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 5);
});

// Modales
function abrirModalCompletar() {
    document.getElementById('modalCompletar').classList.remove('hidden');
}
function cerrarModalCompletar() {
    document.getElementById('modalCompletar').classList.add('hidden');
}
function abrirModalCancelar() {
    document.getElementById('modalCancelar').classList.remove('hidden');
}
function cerrarModalCancelar() {
    document.getElementById('modalCancelar').classList.add('hidden');
}

// Cerrar modales con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalCompletar();
        cerrarModalCancelar();
    }
});

// Cerrar al hacer click fuera
document.getElementById('modalCompletar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCompletar();
});
document.getElementById('modalCancelar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCancelar();
});
</script>
