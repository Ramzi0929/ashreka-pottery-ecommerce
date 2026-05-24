<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_activity':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE system_activities SET deleted_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$id]);
                echo json_encode(['success' => $result]);
                exit();
                
            case 'clear_all':
                $stmt = $pdo->prepare("UPDATE system_activities SET deleted_at = NOW() WHERE deleted_at IS NULL");
                $result = $stmt->execute();
                echo json_encode(['success' => $result]);
                exit();
                
            case 'restore_activity':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE system_activities SET deleted_at = NULL WHERE id = ?");
                $result = $stmt->execute([$id]);
                echo json_encode(['success' => $result]);
                exit();
        }
    }
}

// Get activities with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Check if system_activities table exists, if not create it
try {
    $pdo->query("SELECT 1 FROM system_activities LIMIT 1");
} catch (PDOException $e) {
    // Create table if it doesn't exist
    $pdo->exec("
        CREATE TABLE system_activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            user_id INT,
            reference_id INT,
            reference_table VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL
        )
    ");
}

$activities_query = "
    SELECT id, type, description, created_at
    FROM system_activities 
    WHERE deleted_at IS NULL
    ORDER BY created_at DESC 
    LIMIT $limit OFFSET $offset
";

try {
    $activities = $pdo->query($activities_query)->fetchAll();
    $total_activities = $pdo->query("SELECT COUNT(*) FROM system_activities WHERE deleted_at IS NULL")->fetchColumn();
} catch (PDOException $e) {
    // Fallback to union query if table is empty
    $activities_query = "
        SELECT 'user_registration' as type, CONCAT('New user: ', email) as description, created_at, id
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        UNION ALL
        SELECT 'product_upload' as type, CONCAT('Product: ', name) as description, created_at, id
        FROM products 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        UNION ALL
        SELECT 'order_placed' as type, CONCAT('Order #', id, ' - ETB ', total_amount) as description, created_at, id
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $activities = $pdo->query($activities_query)->fetchAll();
    $total_activities = $pdo->query("SELECT COUNT(*) FROM (" . str_replace("LIMIT $limit OFFSET $offset", "", $activities_query) . ") as total")->fetchColumn();
}
$total_pages = ceil($total_activities / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Activities - Admin</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        .activity-item { transition: all 0.3s ease; }
        .activity-item.deleting { opacity: 0.5; transform: translateX(-20px); }
        .undo-toast { position: fixed; top: 20px; right: 20px; z-index: 1050; }
        .countdown { font-weight: bold; color: #dc3545; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="row g-0">
            <div class="col-md-3 col-lg-2">
                <div class="dashboard-sidebar">
                    <div class="p-3 text-white">
                        <h5><i class="fas fa-chart-line me-2"></i>Activities</h5>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="activities.php">
                            <i class="fas fa-clock me-2"></i>Activities
                        </a>
                        <a class="nav-link" href="heritage.php">
                            <i class="fas fa-book me-2"></i>Heritage
                        </a>
                        <a class="nav-link" href="../../index.php">
                            <i class="fas fa-home me-2"></i>Home
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-9 col-lg-10">
                <div class="dashboard-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-clock me-2"></i>System Activities</h2>
                        <div>
                            <button class="btn btn-outline-danger" onclick="clearAllActivities()">
                                <i class="fas fa-trash me-2"></i>Clear All
                            </button>
                            <button class="btn btn-outline-primary" onclick="refreshActivities()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div id="activities-container">
                                <?php if (empty($activities)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                        <h5>No Recent Activities</h5>
                                        <p class="text-muted">System activities will appear here</p>
                                    </div>
                                <?php else: ?>
                                    <div class="timeline">
                                        <?php foreach ($activities as $activity): ?>
                                        <div class="activity-item mb-3" data-id="<?= $activity['id'] ?? uniqid() ?>" data-type="<?= $activity['type'] ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="timeline-icon me-3">
                                                    <i class="fas fa-<?= 
                                                        $activity['type'] === 'user_registration' ? 'user-plus' : 
                                                        ($activity['type'] === 'product_upload' ? 'box' : 'shopping-cart') 
                                                    ?> text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= ucfirst(str_replace('_', ' ', $activity['type'])) ?></h6>
                                                    <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                                                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?></small>
                                                </div>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteActivity(<?= $activity['id'] ?>, '<?= $activity['type'] ?>')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Pagination -->
                                    <?php if ($total_pages > 1): ?>
                                    <nav class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                            <?php endfor; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Undo Toast -->
    <div id="undoToast" class="toast undo-toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-undo text-warning me-2"></i>
            <strong class="me-auto">Action Performed</strong>
            <button type="button" class="btn-close" onclick="hideUndoToast()"></button>
        </div>
        <div class="toast-body">
            <div id="undoMessage"></div>
            <div class="mt-2">
                <button class="btn btn-sm btn-warning" onclick="undoAction()">
                    <i class="fas fa-undo me-1"></i>Undo (<span id="countdown">5</span>s)
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let undoData = null;
        let undoTimer = null;
        let countdownTimer = null;

        function deleteActivity(id, type) {
            const element = document.querySelector(`[data-id="${id}"]`);
            const activityData = {
                id: id,
                type: type,
                element: element.outerHTML
            };

            element.classList.add('deleting');
            
            fetch('activities.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_activity&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        element.remove();
                        showUndoToast(`Activity deleted`, activityData);
                    }, 300);
                }
            });
        }

        function clearAllActivities() {
            if (confirm('Clear all activities? This action can be undone for 5 seconds.')) {
                const container = document.getElementById('activities-container');
                const allActivities = container.innerHTML;
                
                fetch('activities.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=clear_all'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        container.innerHTML = `
                            <div class="text-center py-5">
                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                <h5>All Activities Cleared</h5>
                                <p class="text-muted">Activities have been removed</p>
                            </div>
                        `;
                        showUndoToast('All activities cleared', {type: 'clear_all', content: allActivities});
                    }
                });
            }
        }

        function refreshActivities() {
            location.reload();
        }

        function showUndoToast(message, data) {
            undoData = data;
            document.getElementById('undoMessage').textContent = message;
            
            const toast = new bootstrap.Toast(document.getElementById('undoToast'));
            toast.show();
            
            startCountdown();
        }

        function startCountdown() {
            let seconds = 5;
            const countdownEl = document.getElementById('countdown');
            
            countdownTimer = setInterval(() => {
                seconds--;
                countdownEl.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdownTimer);
                    hideUndoToast();
                }
            }, 1000);
        }

        function hideUndoToast() {
            const toast = bootstrap.Toast.getInstance(document.getElementById('undoToast'));
            if (toast) toast.hide();
            
            if (countdownTimer) {
                clearInterval(countdownTimer);
            }
            undoData = null;
        }

        function undoAction() {
            if (!undoData) return;
            
            if (undoData.type === 'clear_all') {
                fetch('activities.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=clear_all'
                })
                .then(() => {
                    location.reload();
                });
            } else {
                // Restore single activity
                fetch('activities.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=restore_activity&id=${undoData.id}`
                })
                .then(() => {
                    location.reload();
                });
            }
            
            hideUndoToast();
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshActivities, 30000);
    </script>
</body>
</html>