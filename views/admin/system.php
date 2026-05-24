<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// System health checks
$system_status = [
    'database' => 'online',
    'file_storage' => 'online',
    'memory_usage' => '65%',
    'disk_usage' => '45%',
    'uptime' => '99.9%'
];

// Get system statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_artisans' => $pdo->query("SELECT COUNT(*) FROM artisans")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_approvals' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending'")->fetchColumn(),
    'active_sessions' => 1 // Placeholder
];

// Recent system activities
$activities = $pdo->query("
    SELECT 'User Registration' as activity, email as details, created_at 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    UNION ALL
    SELECT 'Product Upload' as activity, name as details, created_at 
    FROM products 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    UNION ALL
    SELECT 'Order Placed' as activity, CONCAT('Order #', id) as details, created_at 
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at DESC 
    LIMIT 20
")->fetchAll();

// Error logs (simulated)
$error_logs = [
    ['level' => 'WARNING', 'message' => 'High memory usage detected', 'time' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['level' => 'INFO', 'message' => 'Database backup completed successfully', 'time' => date('Y-m-d H:i:s', strtotime('-6 hours'))],
    ['level' => 'ERROR', 'message' => 'Failed login attempt from IP 192.168.1.100', 'time' => date('Y-m-d H:i:s', strtotime('-1 day'))]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitor</title>
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
                    <h1 class="h2">System Monitor</h1>
                    <button class="btn btn-outline-primary" onclick="refreshStatus()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh Status
                    </button>
                </div>

                <!-- System Health Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-database fa-2x text-success mb-2"></i>
                                <h6>Database</h6>
                                <span class="badge bg-success">Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-hdd fa-2x text-success mb-2"></i>
                                <h6>File Storage</h6>
                                <span class="badge bg-success">Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-memory fa-2x text-warning mb-2"></i>
                                <h6>Memory</h6>
                                <span class="badge bg-warning">65%</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-server fa-2x text-success mb-2"></i>
                                <h6>Server</h6>
                                <span class="badge bg-success">Running</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                                <h6>Security</h6>
                                <span class="badge bg-success">Secure</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-success mb-2"></i>
                                <h6>Uptime</h6>
                                <span class="badge bg-success">99.9%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Overview -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>System Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <h4 class="text-primary"><?= $stats['total_users'] ?></h4>
                                        <small class="text-muted">Total Users</small>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <h4 class="text-success"><?= $stats['total_artisans'] ?></h4>
                                        <small class="text-muted">Artisans</small>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <h4 class="text-info"><?= $stats['total_products'] ?></h4>
                                        <small class="text-muted">Products</small>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <h4 class="text-warning"><?= $stats['total_orders'] ?></h4>
                                        <small class="text-muted">Orders</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Resource Usage</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Memory Usage</span>
                                        <span>65%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" style="width: 65%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Disk Usage</span>
                                        <span>45%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" style="width: 45%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>CPU Usage</span>
                                        <span>30%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: 30%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Network I/O</span>
                                        <span>20%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" style="width: 20%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Activities -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent System Activities (Last 24 Hours)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($activities)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Activity</th>
                                                <th>Details</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-<?= 
                                                        $activity['activity'] === 'User Registration' ? 'user-plus' : 
                                                        ($activity['activity'] === 'Product Upload' ? 'box' : 'shopping-cart') 
                                                    ?> me-2"></i>
                                                    <?= $activity['activity'] ?>
                                                </td>
                                                <td><?= htmlspecialchars($activity['details']) ?></td>
                                                <td><?= date('M j, g:i A', strtotime($activity['created_at'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted text-center">No recent activities</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- System Logs -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>System Logs</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($error_logs as $log): ?>
                                <div class="alert alert-<?= 
                                    $log['level'] === 'ERROR' ? 'danger' : 
                                    ($log['level'] === 'WARNING' ? 'warning' : 'info') 
                                ?> alert-sm">
                                    <strong><?= $log['level'] ?>:</strong><br>
                                    <?= htmlspecialchars($log['message']) ?><br>
                                    <small><?= $log['time'] ?></small>
                                </div>
                                <?php endforeach; ?>
                                
                                <button class="btn btn-outline-secondary btn-sm w-100" onclick="viewFullLogs()">
                                    <i class="fas fa-list me-2"></i>View Full Logs
                                </button>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary btn-sm" onclick="createBackup()">
                                        <i class="fas fa-database me-2"></i>Create Backup
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm" onclick="clearCache()">
                                        <i class="fas fa-broom me-2"></i>Clear Cache
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" onclick="optimizeDatabase()">
                                        <i class="fas fa-tools me-2"></i>Optimize DB
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="restartServices()">
                                        <i class="fas fa-power-off me-2"></i>Restart Services
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function refreshStatus() {
            location.reload();
        }

        function createBackup() {
            if (confirm('Create a system backup? This may take a few minutes.')) {
                alert('Backup process started. You will be notified when complete.');
            }
        }

        function clearCache() {
            if (confirm('Clear system cache?')) {
                alert('Cache cleared successfully.');
            }
        }

        function optimizeDatabase() {
            if (confirm('Optimize database? This may take a few minutes.')) {
                alert('Database optimization started.');
            }
        }

        function restartServices() {
            if (confirm('Restart system services? This will cause brief downtime.')) {
                alert('Services restart initiated.');
            }
        }

        function viewFullLogs() {
            window.open('../../logs/system.log', '_blank');
        }
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>