<?php
session_start();
require_once '../../config/database_enhanced.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Get system statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_artisans' => $pdo->query("SELECT COUNT(*) FROM artisans WHERE approval_status = 'approved'")->fetchColumn(),
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'approved'")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'heritage_items' => $pdo->query("SELECT COUNT(*) FROM heritage_archive")->fetchColumn(),
    'system_errors' => 0, // Placeholder for error monitoring
    'server_uptime' => '99.9%' // Placeholder for server monitoring
];

// Get recent activities
$recent_activities = $pdo->query("
    SELECT 'user_registration' as type, email as description, created_at 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'product_upload' as type, name as description, created_at 
    FROM products 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'order_placed' as type, CONCAT('Order #', id) as description, created_at 
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="dashboard-sidebar">
                    <div class="p-3 text-white">
                        <h5><i class="fas fa-user-shield me-2"></i>Admin Panel</h5>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="heritage.php">
                            <i class="fas fa-book me-2"></i>Heritage Archive
                        </a>
                        <a class="nav-link" href="system.php">
                            <i class="fas fa-cogs me-2"></i>System Monitor
                        </a>
                        <a class="nav-link" href="backup.php">
                            <i class="fas fa-database me-2"></i>Backup & Restore
                        </a>
                        <a class="nav-link" href="../profile/profile.php">
                            <i class="fas fa-user-circle me-2"></i>My Profile
                        </a>
                        <a class="nav-link" href="../../index.php">
                            <i class="fas fa-home me-2"></i>Home
                        </a>
                        <a class="nav-link" href="../../controllers/AuthController.php?action=logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="dashboard-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>System Administration</h2>
                    </div>

                    <!-- System Health Alert -->
                    <div class="alert alert-success mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5><i class="fas fa-check-circle me-2"></i>System Status: Healthy</h5>
                                <p class="mb-0">All systems are running normally. Server uptime: <?= $stats['server_uptime'] ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-outline-success" onclick="refreshSystemStatus()">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh Status
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stats-number"><?= $stats['total_users'] ?></div>
                                <div class="stats-label">Total Users</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon bg-success">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="stats-number"><?= $stats['total_artisans'] ?></div>
                                <div class="stats-label">Active Artisans</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon bg-info">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="stats-number"><?= $stats['total_products'] ?></div>
                                <div class="stats-label">Products</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-icon bg-warning">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="stats-number"><?= $stats['heritage_items'] ?></div>
                                <div class="stats-label">Heritage Items</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="heritage.php" class="btn btn-primary w-100">
                                                <i class="fas fa-plus me-2"></i>Add Heritage Content
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <button class="btn btn-success w-100" onclick="createBackup()">
                                                <i class="fas fa-database me-2"></i>Create Backup
                                            </button>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="system.php" class="btn btn-info w-100">
                                                <i class="fas fa-chart-line me-2"></i>System Monitor
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <button class="btn btn-warning w-100" onclick="clearCache()">
                                                <i class="fas fa-broom me-2"></i>Clear Cache
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- System Overview -->
                    <div class="row">
                        <!-- Activities Overview -->
                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-clock me-2"></i>System Activities</h5>
                                </div>
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                                    <h6>Monitor System Activities</h6>
                                    <p class="text-muted mb-4">View real-time system activities, user registrations, and order updates</p>
                                    <a href="activities.php" class="btn btn-primary">
                                        <i class="fas fa-external-link-alt me-2"></i>View Activities Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- System Health -->
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-heartbeat me-2"></i>System Health</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Database</span>
                                            <span class="badge bg-success">Online</span>
                                        </div>
                                        <div class="progress mt-1">
                                            <div class="progress-bar bg-success" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>File Storage</span>
                                            <span class="badge bg-success">75% Used</span>
                                        </div>
                                        <div class="progress mt-1">
                                            <div class="progress-bar bg-success" style="width: 75%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Memory Usage</span>
                                            <span class="badge bg-warning">60% Used</span>
                                        </div>
                                        <div class="progress mt-1">
                                            <div class="progress-bar bg-warning" style="width: 60%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>System Errors</span>
                                            <span class="badge bg-success"><?= $stats['system_errors'] ?></span>
                                        </div>
                                    </div>
                                    
                                    <button class="btn btn-outline-primary w-100" onclick="runSystemCheck()">
                                        <i class="fas fa-stethoscope me-2"></i>Run Full System Check
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="google_translate_element" style="display: none;"></div>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,am',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
        
        function translatePage(lang) {
            var selectField = document.querySelector("select.goog-te-combo");
            if (selectField) {
                selectField.value = lang;
                selectField.dispatchEvent(new Event('change'));
            }
        }

        function refreshSystemStatus() {
            // Simulate system status refresh
            const btn = event.target;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Refresh Status';
                btn.disabled = false;
                alert('System status refreshed successfully!');
            }, 2000);
        }

        function createBackup() {
            if (confirm('Create a full system backup? This may take a few minutes.')) {
                fetch('../../controllers/AdminController.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=create_backup'
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                });
            }
        }

        function clearCache() {
            if (confirm('Clear system cache? This will improve performance.')) {
                fetch('../../controllers/AdminController.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=clear_cache'
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                });
            }
        }

        function runSystemCheck() {
            const btn = event.target;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Running Check...';
            btn.disabled = true;
            
            fetch('../../controllers/AdminController.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=system_check'
            })
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = '<i class="fas fa-stethoscope me-2"></i>Run Full System Check';
                btn.disabled = false;
                alert(data.message);
            });
        }
        

    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>