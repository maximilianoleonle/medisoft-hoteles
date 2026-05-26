<?php require_once VIEWS_PATH . '/templates/header.php'; ?>
<?php require_once VIEWS_PATH . '/templates/sidebar.php'; ?>

<main class="content-wrapper">
    <div class="container-fluid px-4">
        <h1 class="mt-4">Movimientos de Inventario</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="<?= url('/dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/inventarios') ?>">Inventario</a></li>
            <li class="breadcrumb-item active">Movimientos</li>
        </ol>

        <?= get_mensaje() ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <i class="fas fa-filter me-1"></i>
                Filtros de Búsqueda
            </div>
            <div class="card-body">
                <form action="<?= url('/inventarios/movimientos') ?>" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="producto" class="form-label">Producto</label>
                        <select name="producto_id" id="producto" class="form-select select2">
                            <option value="">Todos los productos</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?= $producto['id'] ?>" <?= isset($_GET['producto_id']) && $_GET['producto_id'] == $producto['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($producto['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select name="tipo" id="tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="ENTRADA" <?= isset($_GET['tipo']) && $_GET['tipo'] == 'ENTRADA' ? 'selected' : '' ?>>Entradas</option>
                            <option value="SALIDA" <?= isset($_GET['tipo']) && $_GET['tipo'] == 'SALIDA' ? 'selected' : '' ?>>Salidas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_desde" class="form-label">Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                               value="<?= $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_hasta" class="form-label">Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                               value="<?= $_GET['fecha_hasta'] ?? date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de movimientos -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-exchange-alt me-1"></i>
                    Movimientos de Inventario
                </div>
                <div>
                    <a href="<?= url('/inventarios/exportar-movimientos' . (isset($_GET) && !empty($_GET) ? '?' . http_build_query($_GET) : '')) ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i> Exportar
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Stock Anterior</th>
                                <th>Stock Nuevo</th>
                                <th>Motivo</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movimientos)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i> No se encontraron movimientos con los filtros aplicados
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($movimientos as $movimiento): ?>
                                    <tr>
                                        <td><?= $movimiento['id'] ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($movimiento['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($movimiento['nombre_producto']) ?></td>
                                        <td>
                                            <?php if ($movimiento['tipo_movimiento'] == 'ENTRADA'): ?>
                                                <span class="badge bg-success">ENTRADA</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">SALIDA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($movimiento['cantidad'], 2) ?></td>
                                        <td><?= number_format($movimiento['stock_anterior'], 2) ?></td>
                                        <td><?= number_format($movimiento['stock_nuevo'], 2) ?></td>
                                        <td><?= htmlspecialchars($movimiento['motivo']) ?></td>
                                        <td><?= htmlspecialchars($movimiento['nombre_usuario']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Navegación de páginas">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= url('/inventarios/movimientos' . (isset($_GET) && !empty($_GET) ? '?' . http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) : '?pagina=' . ($pagina_actual - 1))) ?>">
                                    Anterior
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                                <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/inventarios/movimientos' . (isset($_GET) && !empty($_GET) ? '?' . http_build_query(array_merge($_GET, ['pagina' => $i])) : '?pagina=' . $i)) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= url('/inventarios/movimientos' . (isset($_GET) && !empty($_GET) ? '?' . http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) : '?pagina=' . ($pagina_actual + 1))) ?>">
                                    Siguiente
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccione un producto',
        allowClear: true
    });
});
</script>

<?php require_once VIEWS_PATH . '/templates/footer.php'; ?>