<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Get date range from request
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Sales Report
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue
    FROM orders 
    WHERE created_at BETWEEN ? AND ? 
    GROUP BY DATE(created_at) 
    ORDER BY date DESC
");
$stmt->execute([$start_date, $end_date]);
$sales_data = $stmt->fetchAll();

// Top Products
$stmt = $pdo->prepare("
    SELECT p.name, COUNT(o.id) as order_count, SUM(o.total_amount) as revenue
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE o.created_at BETWEEN ? AND ? 
    GROUP BY p.id 
    ORDER BY order_count DESC 
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll();

// Artisan Performance
$stmt = $pdo->prepare("
    SELECT u.name, COUNT(o.id) as orders, SUM(o.total_amount) as revenue,
           AVG(op.progress_percentage) as avg_progress
    FROM orders o 
    JOIN artisans a ON o.artisan_id = a.id 
    JOIN users u ON a.user_id = u.id 
    LEFT JOIN order_progress op ON o.id = op.order_id
    WHERE o.created_at BETWEEN ? AND ? 
    GROUP BY a.id 
    ORDER BY orders DESC
");
$stmt->execute([$start_date, $end_date]);
$artisan_performance = $stmt->fetchAll();

// Summary Statistics
$total_orders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?");
$total_orders->execute([$start_date, $end_date]);
$total_orders = $total_orders->fetchColumn();

$total_revenue = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE created_at BETWEEN ? AND ?");
$total_revenue->execute([$start_date, $end_date]);
$total_revenue = $total_revenue->fetchColumn() ?: 0;

$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../layouts/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php include '../layouts/alerts.php'; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reports & Analytics</h1>
                    <button class="btn btn-outline-primary" onclick="exportReport()">
                        <i class="fas fa-download me-2"></i>Export Report
                    </button>
                </div>

                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">
                                    <i class="fas fa-filter me-2"></i>Apply Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= $total_orders ?></h4>
                                        <p class="mb-0">Total Orders</p>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format($total_revenue) ?> ETB</h4>
                                        <p class="mb-0">Total Revenue</p>
                                    </div>
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format($avg_order_value) ?> ETB</h4>
                                        <p class="mb-0">Avg Order Value</p>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= count($artisan_performance) ?></h4>
                                        <p class="mb-0">Active Artisans</p>
                                    </div>
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Sales Chart -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Daily Sales</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($sales_data)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Orders</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sales_data as $day): ?>
                                            <tr>
                                                <td><?= date('M j, Y', strtotime($day['date'])) ?></td>
                                                <td><?= $day['orders'] ?></td>
                                                <td><?= number_format($day['revenue']) ?> ETB</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted text-center">No sales data for selected period</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Top Products</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($top_products)): ?>
                                <?php foreach ($top_products as $product): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?= htmlspecialchars($product['name']) ?></strong>
                                        <br><small class="text-muted"><?= $product['order_count'] ?> orders</small>
                                    </div>
                                    <span class="badge bg-primary"><?= number_format($product['revenue']) ?> ETB</span>
                                </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <p class="text-muted text-center">No product data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Artisan Performance -->
                <div class="card">
                    <div class="card-header">
                        <h5>Artisan Performance</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($artisan_performance)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Artisan</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                        <th>Avg Progress</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($artisan_performance as $artisan): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($artisan['name']) ?></td>
                                        <td><?= $artisan['orders'] ?></td>
                                        <td><?= number_format($artisan['revenue']) ?> ETB</td>
                                        <td><?= number_format($artisan['avg_progress'] ?: 0, 1) ?>%</td>
                                        <td>
                                            <?php
                                            $performance = $artisan['orders'] * 0.4 + ($artisan['avg_progress'] ?: 0) * 0.6;
                                            $badge_class = $performance >= 80 ? 'success' : ($performance >= 60 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>"><?= number_format($performance, 1) ?>%</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center">No artisan performance data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function exportReport() {
            const startDate = '<?= $start_date ?>';
            const endDate = '<?= $end_date ?>';
            window.open(`../../controllers/ManagerController.php?action=export_report&start_date=${startDate}&end_date=${endDate}`, '_blank');
        }
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>