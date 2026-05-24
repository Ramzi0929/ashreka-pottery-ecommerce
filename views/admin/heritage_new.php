<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM heritage_archive WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        $_SESSION['success'] = 'Heritage item deleted successfully';
    }
    header('Location: heritage_new.php');
    exit;
}

// Handle heritage item creation/update
if ($_POST && isset($_POST['save_heritage'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $content_type = $_POST['content_type'];
    $video_link = $_POST['video_link'] ?? null;
    $category = $_POST['category'] ?? 'general';
    $tags = $_POST['tags'] ?? '';
    
    $file_path = null;
    
    // Handle file upload
    if (isset($_FILES['heritage_file']) && $_FILES['heritage_file']['error'] === 0) {
        $upload_dir = '../../assets/uploads/heritage/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $file_name = time() . '_' . $_FILES['heritage_file']['name'];
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['heritage_file']['tmp_name'], $file_path);
        $file_path = 'assets/uploads/heritage/' . $file_name;
    }
    
    $stmt = $pdo->prepare("INSERT INTO heritage_archive (title, description, content_type, file_path, video_link, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $success = $stmt->execute([$title, $content, $content_type, $file_path, $video_link, $_SESSION['user_id']]);
    
    $_SESSION[$success ? 'success' : 'error'] = $success ? 'Heritage item saved successfully' : 'Failed to save heritage item';
    header('Location: heritage_new.php');
    exit;
}

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_category = $_GET['category'] ?? 'all';

// Build query with filters
$where_conditions = [];
$params = [];

if ($filter_type !== 'all') {
    $where_conditions[] = "content_type = ?";
    $params[] = $filter_type;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
$stmt = $pdo->prepare("SELECT * FROM heritage_archive $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$heritage_items = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM heritage_archive")->fetchColumn(),
    'documents' => $pdo->query("SELECT COUNT(*) FROM heritage_archive WHERE content_type = 'document'")->fetchColumn(),
    'photos' => $pdo->query("SELECT COUNT(*) FROM heritage_archive WHERE content_type = 'photo'")->fetchColumn(),
    'videos' => $pdo->query("SELECT COUNT(*) FROM heritage_archive WHERE content_type = 'video'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heritage Archive - Modern UI</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #8B4513 0%, #D2691E 50%, #FFF8DC 100%); min-height: 100vh; }
        .main-container { background: rgba(255,255,255,0.98); border-radius: 25px; backdrop-filter: blur(15px); box-shadow: 0 20px 60px rgba(0,0,0,0.1); }
        .stats-card { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border-radius: 20px; transition: all 0.3s; }
        .stats-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(139,69,19,0.3); }
        .filter-btn { border-radius: 25px; transition: all 0.3s; }
        .filter-btn.active { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; }
        .upload-form { background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%); border-radius: 20px; border: 3px solid #D2691E; }
        .heritage-card { border-radius: 20px; overflow: hidden; transition: all 0.4s; border: 2px solid transparent; }
        .heritage-card:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 20px 40px rgba(0,0,0,0.15); border-color: #8B4513; }
        .category-badge { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border-radius: 15px; }
        .drag-drop-area { border: 3px dashed #8B4513; border-radius: 20px; background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%); transition: all 0.3s; }
        .drag-drop-area:hover { background: linear-gradient(135deg, #F5DEB3 0%, #DEB887 100%); transform: scale(1.02); }
        .ethiopian-pattern { background-image: radial-gradient(circle, #8B4513 1px, transparent 1px); background-size: 20px 20px; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="main-container p-4">
            <!-- Header -->
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h1 class="h2 mb-2" style="color: #8B4513;">
                        <i class="fas fa-scroll me-2"></i>የኢትዮጵያ ባህላዊ ቅርስ መዝገብ
                    </h1>
                    <h3 class="h4 text-muted mb-0">Ethiopian Heritage Archive Management</h3>
                    <p class="text-muted mb-0">Preserve and categorize Ethiopian cultural heritage</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-lg" style="background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border: none; border-radius: 25px;" onclick="toggleUploadForm()">
                        <i class="fas fa-plus me-2"></i>Add Heritage Content
                    </button>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card p-3 text-center">
                        <i class="fas fa-archive fa-2x mb-2"></i>
                        <h4><?= $stats['total'] ?></h4>
                        <small>Total Items</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card p-3 text-center">
                        <i class="fas fa-file-alt fa-2x mb-2"></i>
                        <h4><?= $stats['documents'] ?></h4>
                        <small>Documents</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card p-3 text-center">
                        <i class="fas fa-images fa-2x mb-2"></i>
                        <h4><?= $stats['photos'] ?></h4>
                        <small>Images</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card p-3 text-center">
                        <i class="fas fa-video fa-2x mb-2"></i>
                        <h4><?= $stats['videos'] ?></h4>
                        <small>Videos</small>
                    </div>
                </div>
            </div>
            
            <!-- Filter Buttons -->
            <div class="d-flex justify-content-center mb-4">
                <div class="btn-group" role="group">
                    <button class="btn filter-btn <?= $filter_type === 'all' ? 'active' : 'btn-outline-primary' ?>" onclick="filterContent('all')">
                        <i class="fas fa-th-large me-1"></i>All Heritage
                    </button>
                    <button class="btn filter-btn <?= $filter_type === 'document' ? 'active' : 'btn-outline-primary' ?>" onclick="filterContent('document')">
                        <i class="fas fa-file-alt me-1"></i>Documents
                    </button>
                    <button class="btn filter-btn <?= $filter_type === 'photo' ? 'active' : 'btn-outline-primary' ?>" onclick="filterContent('photo')">
                        <i class="fas fa-camera me-1"></i>Images
                    </button>
                    <button class="btn filter-btn <?= $filter_type === 'video' ? 'active' : 'btn-outline-primary' ?>" onclick="filterContent('video')">
                        <i class="fas fa-play-circle me-1"></i>Videos
                    </button>
                </div>
            </div>

            <!-- Upload Form -->
            <div id="uploadForm" class="upload-form p-4 mb-4 ethiopian-pattern" style="display: none;">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 style="color: #8B4513;" class="mb-3">
                                <i class="fas fa-plus-circle me-2"></i>Add New Heritage Content
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Content Type</label>
                                <select class="form-select" name="content_type" required onchange="updateUploadArea(this.value)">
                                    <option value="">Select content type...</option>
                                    <option value="document">📄 Research & Documents</option>
                                    <option value="photo">📸 Heritage Images</option>
                                    <option value="video">🎥 Video Links</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Heritage Title</label>
                                <input type="text" class="form-control" name="title" placeholder="Enter heritage title..." required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Category</label>
                                <select class="form-select" name="category">
                                    <option value="pottery">🏺 Traditional Pottery</option>
                                    <option value="weaving">🧵 Traditional Weaving</option>
                                    <option value="history">📚 Historical Documents</option>
                                    <option value="culture">🎭 Cultural Practices</option>
                                    <option value="general">📋 General Heritage</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <textarea class="form-control" name="content" rows="4" placeholder="Describe this Ethiopian heritage content..." required></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="drag-drop-area p-4 text-center h-100" id="uploadArea">
                                <div id="defaultUpload">
                                    <i class="fas fa-cloud-upload-alt fa-4x mb-3" style="color: #8B4513;"></i>
                                    <h5>Upload Heritage Content</h5>
                                    <p class="text-muted">Select content type first</p>
                                </div>
                                
                                <div id="fileUpload" style="display: none;">
                                    <i class="fas fa-file-upload fa-3x mb-3" style="color: #8B4513;"></i>
                                    <h6>Upload File</h6>
                                    <input type="file" class="form-control" name="heritage_file" accept=".pdf,.doc,.docx,.txt,image/*">
                                    <small class="text-muted mt-2 d-block">PDF, Word, Images allowed</small>
                                </div>
                                
                                <div id="videoUpload" style="display: none;">
                                    <i class="fas fa-video fa-3x mb-3" style="color: #dc3545;"></i>
                                    <h6>Add Video Link</h6>
                                    <input type="url" class="form-control" name="video_link" placeholder="https://youtube.com/watch?v=...">
                                    <small class="text-muted mt-2 d-block">YouTube, Vimeo links</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-outline-secondary me-2" onclick="toggleUploadForm()">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="save_heritage" class="btn btn-lg" style="background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border: none; border-radius: 25px;">
                            <i class="fas fa-save me-2"></i>Save Heritage Item
                        </button>
                    </div>
                </form>
            </div>

            <!-- Heritage Items Grid -->
            <div class="row g-4">
                <?php foreach ($heritage_items as $item): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="heritage-card card h-100">
                        <?php if ($item['file_path'] && $item['content_type'] === 'photo'): ?>
                            <img src="../../<?= $item['file_path'] ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white;">
                                <div class="text-center">
                                    <i class="fas fa-<?= $item['content_type'] === 'video' ? 'play-circle' : 'file-alt' ?> fa-3x mb-2"></i>
                                    <h6><?= ucfirst($item['content_type']) ?></h6>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($item['title']) ?></h5>
                                <span class="category-badge px-2 py-1 small"><?= ucfirst($item['content_type']) ?></span>
                            </div>
                            <p class="card-text text-muted"><?= substr(htmlspecialchars($item['description']), 0, 80) ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= date('M j, Y', strtotime($item['created_at'])) ?>
                                </small>
                                <?php if ($item['video_link']): ?>
                                <a href="<?= htmlspecialchars($item['video_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100">
                                <button class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteItem(<?= $item['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleUploadForm() {
            const form = document.getElementById('uploadForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function updateUploadArea(type) {
            const defaultUpload = document.getElementById('defaultUpload');
            const fileUpload = document.getElementById('fileUpload');
            const videoUpload = document.getElementById('videoUpload');
            
            // Hide all
            defaultUpload.style.display = 'none';
            fileUpload.style.display = 'none';
            videoUpload.style.display = 'none';
            
            if (type === 'video') {
                videoUpload.style.display = 'block';
            } else if (type === 'document' || type === 'photo') {
                fileUpload.style.display = 'block';
                const fileInput = fileUpload.querySelector('input[type="file"]');
                if (type === 'photo') {
                    fileInput.setAttribute('accept', 'image/*');
                    fileUpload.querySelector('small').textContent = 'JPG, PNG, GIF images allowed';
                } else {
                    fileInput.setAttribute('accept', '.pdf,.doc,.docx,.txt');
                    fileUpload.querySelector('small').textContent = 'PDF, Word documents allowed';
                }
            } else {
                defaultUpload.style.display = 'block';
            }
        }

        function filterContent(type) {
            window.location.href = `heritage_new.php?type=${type}`;
        }

        function deleteItem(id) {
            if (confirm('Are you sure you want to delete this heritage item?')) {
                window.location.href = `heritage_new.php?delete=${id}`;
            }
        }

        // Success message display
        <?php if (isset($_SESSION['success'])): ?>
        setTimeout(() => {
            alert('<?= $_SESSION['success'] ?>');
        }, 500);
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        setTimeout(() => {
            alert('<?= $_SESSION['error'] ?>');
        }, 500);
        <?php unset($_SESSION['error']); endif; ?>
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>