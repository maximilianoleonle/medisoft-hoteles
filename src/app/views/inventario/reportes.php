<?php require_once VIEWS_PATH . '/templates/header.php'; ?>
<?php require_once VIEWS_PATH . '/templates/sidebar.php'; ?>

<main class="content-wrapper">
    <div class="container-fluid px-4">
        <h1 class="mt-4">Reportes de Inventario</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="<?= url('/dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/inventarios') ?>">Inventario</a></li>
            <li class="breadcrumb-item active">Reportes</li>
        </ol>

        <?= get_mensaje() ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <i class="fas fa-filter me-1"></i>
                Filtros de Reportes
            </div>
            <div class="card-body">
                <form action="<?= url('/inventarios/reportes') ?>" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select name="categoria_id" id="categoria" class="form-select">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= isset($_GET['categoria_id']) && $_GET['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
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
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Generar Reportes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Gráficas y Reportes -->
        <div class="row">
            <!-- Productos más movidos -->
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-1"></i>
                        Top 10 Productos con Mayor Movimiento
                    </div>
                    <div class="card-body">
                        <canvas id="graficoTopProductos" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Valor de inventario por categoría -->
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-1"></i>
                        Valor de Inventario por Categoría
                    </div>
                    <div class="card-body">
                        <canvas id="graficoValorCategorias" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Entradas vs Salidas -->
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-exchange-alt me-1"></i>
                        Entradas vs Salidas (Por Día)
                    </div>
                    <div class="card-body">
                        <canvas id="graficoEntradasSalidas" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Productos cerca de stock mínimo -->
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Productos Cerca de Stock Mínimo
                    </div>
                    <div class="card-body">
                        <canvas id="graficoStockMinimo" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de resumen de productos -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-table me-1"></i>
                    Resumen de Productos
                </div>
                <div>
                    <a href="<?= url('/inventarios/exportar-reporte' . (isset($_GET) && !empty($_GET) ? '?' . http_build_query($_GET) : '')) ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                    </a>
                    <a href="<?= url('/inventarios/exportar-reporte-pdf' . (isset($_GET) && !empty($_GET) ? '?' . http_build_query($_GET) : '')) ?>" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i> Exportar a PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Stock Actual</th>
                                <th>Valor Total</th>
                                <th>Entradas</th>
                                <th>Salidas</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resumen_productos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i> No se encontraron datos con los filtros aplicados
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($resumen_productos as $producto): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($producto['codigo']) ?></td>
                                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                        <td><?= htmlspecialchars($producto['categoria_nombre']) ?></td>
                                        <td class="text-end"><?= number_format($producto['stock_actual'], 2) ?></td>
                                        <td class="text-end">$<?= number_format($producto['valor_total'], 2) ?></td>
                                        <td class="text-end text-success">+<?= number_format($producto['total_entradas'], 2) ?></td>
                                        <td class="text-end text-danger">-<?= number_format($producto['total_salidas'], 2) ?></td>
                                        <td class="text-end <?= $producto['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $producto['balance'] >= 0 ? '+' : '' ?><?= number_format($producto['balance'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar los datos para las gráficas
    const ctxTopProductos = document.getElementById('graficoTopProductos').getContext('2d');
    const ctxValorCategorias = document.getElementById('graficoValorCategorias').getContext('2d');
    const ctxEntradasSalidas = document.getElementById('graficoEntradasSalidas').getContext('2d');
    const ctxStockMinimo = document.getElementById('graficoStockMinimo').getContext('2d');
    
    // Datos para gráfica de top productos
    const datosTopProductos = {
        labels: <?= json_encode(array_column($top_productos, 'nombre')) ?>,
        datasets: [{
            label: 'Cantidad de Movimientos',
            backgroundColor: 'rgba(0, 123, 255, 0.7)',
            borderColor: 'rgb(0, 123, 255)',
            borderWidth: 1,
            data: <?= json_encode(array_column($top_productos, 'total_movimientos')) ?>
        }]
    };
    
    // Datos para gráfica de valor por categoría
    const datosValorCategorias = {
        labels: <?= json_encode(array_column($valor_categorias, 'nombre')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($valor_categorias, 'valor_total')) ?>,
            backgroundColor: [
                'rgba(0, 123, 255, 0.7)',
                'rgba(40, 167, 69, 0.7)',
                'rgba(255, 193, 7, 0.7)',
                'rgba(220, 53, 69, 0.7)',
                'rgba(111, 66, 193, 0.7)',
                'rgba(23, 162, 184, 0.7)',
                'rgba(108, 117, 125, 0.7)'
            ],
            borderColor: [
                'rgb(0, 123, 255)',
                'rgb(40, 167, 69)',
                'rgb(255, 193, 7)',
                'rgb(220, 53, 69)',
                'rgb(111, 66, 193)',
                'rgb(23, 162, 184)',
                'rgb(108, 117, 125)'
            ],
            borderWidth: 1
        }]
    };
    
    // Datos para gráfica de entradas vs salidas
    const datosEntradasSalidas = {
        labels: <?= json_encode($entradas_salidas['labels']) ?>,
        datasets: [
            {
                label: 'Entradas',
                backgroundColor: 'rgba(40, 167, 69, 0.5)',
                borderColor: 'rgb(40, 167, 69)',
                borderWidth: 1,
                data: <?= json_encode($entradas_salidas['entradas']) ?>
            },
            {
                label: 'Salidas',
                backgroundColor: 'rgba(220, 53, 69, 0.5)',
                borderColor: 'rgb(220, 53, 69)',
                borderWidth: 1,
                data: <?= json_encode($entradas_salidas['salidas']) ?>
            }
        ]
    };
    
    // Datos para gráfica de stock mínimo
    const datosStockMinimo = {
        labels: <?= json_encode(array_column($productos_stock_minimo, 'nombre')) ?>,
        datasets: [
            {
                label: 'Stock Actual',
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: 'rgb(0, 123, 255)',
                borderWidth: 1,
                data: <?= json_encode(array_column($productos_stock_minimo, 'stock_actual')) ?>
            },
            {
                label: 'Stock Mínimo',
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: 'rgb(220, 53, 69)',
                borderWidth: 1,
                data: <?= json_encode(array_column($productos_stock_minimo, 'stock_minimo')) ?>
            }
        ]
    };
    
    // Crear gráfica de top productos
    new Chart(ctxTopProductos, {
        type: 'bar',
        data: datosTopProductos,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            indexAxis: 'y'
        }
    });
    
    // Crear gráfica de valor por categoría
    new Chart(ctxValorCategorias, {
        type: 'pie',
        data: datosValorCategorias,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            return label + ': $' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });
    
    // Crear gráfica de entradas vs salidas
    new Chart(ctxEntradasSalidas, {
        type: 'line',
        data: datosEntradasSalidas,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Crear gráfica de stock mínimo
    new Chart(ctxStockMinimo, {
        type: 'bar',
        data: datosStockMinimo,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php require_once VIEWS_PATH . '/templates/footer.php'; ?>