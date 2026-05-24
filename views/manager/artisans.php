<?php
session_start();
require_once '../../config/database_enhanced.php';
require_once '../../models/Artisan.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle artisan status updates
if ($_POST) {
    $artisan_id = $_POST['artisan_id'];
    $action = $_POST['action'];
    
    if ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE artisans SET approval_status = 'approved' WHERE id = ?");
        $stmt->execute([$artisan_id]);
        $_SESSION['success'] = 'Artisan activated successfully';
    } elseif ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE artisans SET approval_status = 'pending' WHERE id = ?");
        $stmt->execute([$artisan_id]);
        $_SESSION['success'] = 'Artisan deactivated successfully';
    }
    header('Location: artisans.php');
    exit;
}

// Get all artisans
$stmt = $pdo->prepare("
    SELECT a.*, 
           COALESCE(a.name, u.name, u.email) as name, 
           u.email, u.phone, u.created_at 
    FROM artisans a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$artisans = $stmt->fetchAll();

$pageTitle = 'Artisan Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
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
                    <h1 class="h2">Artisan Management</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($artisans)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Specialization</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($artisans as $artisan): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="../../<?= $artisan['profile_image'] ?: 'assets/images/default-avatar.svg' ?>" 
                                                             class="rounded-circle me-2" width="32" height="32">
                                                        <?= htmlspecialchars($artisan['name']) ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($artisan['email']) ?></td>
                                                <td><?= htmlspecialchars($artisan['phone']) ?></td>
                                                <td><?= htmlspecialchars($artisan['skill_type']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $artisan['approval_status'] == 'approved' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($artisan['approval_status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($artisan['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewArtisan(<?= $artisan['id'] ?>)">
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Modal -->
                                            <div class="modal fade" id="artisanModal<?= $artisan['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Artisan Details: <?= htmlspecialchars($artisan['name']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-4 text-center">
                                                                    <img src="../../<?= $artisan['profile_image'] ?: 'assets/images/default-avatar.svg' ?>" 
                                                                         class="rounded-circle mb-3" width="150" height="150">
                                                                    <h5><?= htmlspecialchars($artisan['name']) ?></h5>
                                                                </div>
                                                                <div class="col-md-8">
                                                                    <p><strong>Email:</strong> <?= htmlspecialchars($artisan['email']) ?></p>
                                                                    <p><strong>Phone:</strong> <?= htmlspecialchars($artisan['phone']) ?></p>
                                                                    <p><strong>Specialization:</strong> <?= htmlspecialchars($artisan['skill_type']) ?></p>
                                                                    <p><strong>Address:</strong> <?= htmlspecialchars($artisan['address'] ?: 'Not provided') ?></p>
                                                                    <p><strong>Experience:</strong> <?= $artisan['experience_years'] ?: 0 ?> years</p>
                                                                    <?php if ($artisan['description']): ?>
                                                                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($artisan['description'])) ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <?php if ($artisan['approval_status'] == 'approved'): ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="artisan_id" value="<?= $artisan['id'] ?>">
                                                                    <input type="hidden" name="action" value="deactivate">
                                                                    <button type="submit" class="btn btn-warning">Deactivate</button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="artisan_id" value="<?= $artisan['id'] ?>">
                                                                    <input type="hidden" name="action" value="activate">
                                                                    <button type="submit" class="btn btn-success">Activate</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h4>No Artisans Found</h4>
                                <p class="text-muted">No artisans have registered yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
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
        
        function viewArtisan(id) {
            var modal = new bootstrap.Modal(document.getElementById('artisanModal' + id));
            modal.show();
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>