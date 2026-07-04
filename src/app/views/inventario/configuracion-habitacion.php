<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Configuración de Inventario por Tipo de Habitación</h4>
                <div class="page-title-right">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="<?= url('/') ?>">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="<?= url('inventario') ?>">Inventario</a></li>
                            <li class="breadcrumb-item active">Configuración por Habitación</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <?php if (hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= getFlash('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Información</h5>
                        <p class="mb-2">Configure las cantidades de productos que se descontarán automáticamente para cada tipo de habitación:</p>
                        <ul class="mb-0">
                            <li><strong>Cantidad Check-in:</strong> Se descuenta automáticamente cuando un huésped hace check-in</li>
                            <li><strong>Cantidad Limpieza:</strong> Se descuenta cuando se realiza limpieza de la habitación</li>
                        </ul>
                    </div>

                    <!-- Tabs para cada tipo de habitación -->
                    <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
                        <?php foreach ($tiposHabitacion as $index => $tipo): ?>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                                   data-bs-toggle="tab" 
                                   href="#tipo-<?= $tipo['id'] ?>" 
                                   role="tab">
                                    <?= htmlspecialchars($tipo['nombre']) ?>
                                    <br>
                                    <small class="text-muted"><?= $tipo['total_habitaciones'] ?> habitaciones</small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <form method="POST" action="<?= url('inventario/configuracion-habitacion/guardar') ?>">
                        <div class="tab-content">
                            <?php foreach ($tiposHabitacion as $index => $tipo): ?>
                                <div class="tab-pane <?= $index === 0 ? 'show active' : '' ?>" 
                                     id="tipo-<?= $tipo['id'] ?>" 
                                     role="tabpanel">
                                    
                                    <h5 class="mb-3">Productos para <?= htmlspecialchars($tipo['nombre']) ?></h5>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-centered table-striped">
                                            <thead>
                                                <tr>
                                                    <th width="30%">Producto</th>
                                                    <th width="15%">Stock Actual</th>
                                                    <th width="20%">Cantidad Check-in</th>
                                                    <th width="20%">Cantidad Limpieza</th>
                                                    <th width="15%">Activo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($productos as $producto): ?>
                                                    <?php 
                                                    $config = $configuraciones[$tipo['id']][$producto['id']] ?? null;
                                                    $configId = $config ? $config['id'] : '';
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars($producto['categoria']) ?> - 
                                                                <?= htmlspecialchars($producto['unidad']) ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= $producto['stock_actual'] > $producto['stock_minimo'] ? 'success' : 'danger' ?>">
                                                                <?= number_format($producto['stock_actual'], 2) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <input type="hidden" 
                                                                   name="config[<?= $tipo['id'] ?>][<?= $producto['id'] ?>][id]" 
                                                                   value="<?= $configId ?>">
                                                            <input type="number" 
                                                                   class="form-control form-control-sm" 
                                                                   name="config[<?= $tipo['id'] ?>][<?= $producto['id'] ?>][cantidad_checkin]"
                                                                   value="<?= $config ? $config['cantidad_checkin'] : '0.00' ?>"
                                                                   step="0.01"
                                                                   min="0"
                                                                   <?= !$producto['descuento_automatico_checkin'] ? 'readonly' : '' ?>>
                                                            <?php if (!$producto['descuento_automatico_checkin']): ?>
                                                                <small class="text-warning">Descuento automático desactivado</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <input type="number" 
                                                                   class="form-control form-control-sm" 
                                                                   name="config[<?= $tipo['id'] ?>][<?= $producto['id'] ?>][cantidad_limpieza]"
                                                                   value="<?= $config ? $config['cantidad_limpieza'] : '0.00' ?>"
                                                                   step="0.01"
                                                                   min="0"
                                                                   <?= !$producto['descuento_automatico_limpieza'] ? 'readonly' : '' ?>>
                                                            <?php if (!$producto['descuento_automatico_limpieza']): ?>
                                                                <small class="text-warning">Descuento automático desactivado</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" 
                                                                       name="config[<?= $tipo['id'] ?>][<?= $producto['id'] ?>][activo]" 
                                                                       value="0">
                                                                <input type="checkbox" 
                                                                       class="form-check-input" 
                                                                       name="config[<?= $tipo['id'] ?>][<?= $producto['id'] ?>][activo]"
                                                                       value="1"
                                                                       <?= (!$config || $config['activo']) ? 'checked' : '' ?>>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-end mt-3">
                            <a href="<?= back_url('inventario') ?>" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
