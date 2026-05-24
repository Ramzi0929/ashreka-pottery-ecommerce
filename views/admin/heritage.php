<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle heritage item creation/update
if ($_POST && isset($_POST['save_heritage'])) {
    // Debug: Log all POST and FILES data
    error_log("Heritage Upload Debug - POST data: " . print_r($_POST, true));
    error_log("Heritage Upload Debug - FILES data: " . print_r($_FILES, true));
    
    $title = $_POST['title'];
    $description = $_POST['description'];
    $content = null; // Content removed for all types
    $content_type = $_POST['content_type'];
    $category = $_POST['category'] ?? $_POST['image_category'] ?? 'history';
    $main_category = $_POST['main_category'] ?? 'pottery';
    $language = ($content_type === 'image') ? null : ($_POST['language'] ?? 'en');
    $video_link = $_POST['video_link'] ?? null;
    $id = $_POST['id'] ?? null;
    
    $file_path = null;
    
    // Initialize file_path
    $file_path = null;
    
    // Handle file upload
    if (isset($_FILES['heritage_file']) && $_FILES['heritage_file']['error'] === 0) {
        $upload_dir = '../../assets/uploads/heritage/';
        
        // Debug output
        error_log("File upload attempt:");
        error_log("- File name: " . $_FILES['heritage_file']['name']);
        error_log("- File size: " . $_FILES['heritage_file']['size']);
        error_log("- Upload dir: " . $upload_dir);
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
            error_log("- Created directory");
        }
        
        // Create unique filename
        $file_name = uniqid() . '_' . basename($_FILES['heritage_file']['name']);
        $full_path = $upload_dir . $file_name;
        
        error_log("- Target path: " . $full_path);
        
        if (move_uploaded_file($_FILES['heritage_file']['tmp_name'], $full_path)) {
            $file_path = 'assets/uploads/heritage/' . $file_name;
            error_log("- SUCCESS: File uploaded, path = " . $file_path);
        } else {
            error_log("- FAILED: Could not move uploaded file");
        }
    } else {
        error_log("No file uploaded or error occurred");
        if (isset($_FILES['heritage_file'])) {
            error_log("File error code: " . $_FILES['heritage_file']['error']);
        }
    }
    
    // Enforce video category for video links
    if (!empty($video_link)) {
        $content_type = 'video_link';
        $file_path = null; // Clear file_path for video links
    }
    
    if ($id) {
        $sql = "UPDATE heritage_archive SET title = ?, description = ?, content = ?, content_type = ?, category = ?, main_category = ?, language = ?";
        $params = [$title, $description, $content, $content_type, $category, $main_category, $language];
        
        if ($file_path) {
            $sql .= ", file_path = ?";
            $params[] = $file_path;
        }
        if ($video_link) {
            $sql .= ", video_link = ?";
            $params[] = $video_link;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("INSERT INTO heritage_archive (title, description, content, content_type, category, main_category, language, file_path, video_link, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $success = $stmt->execute([$title, $description, $content, $content_type, $category, $main_category, $language, $file_path, $video_link, $_SESSION['user_id']]);
    }
    
    $_SESSION[$success ? 'success' : 'error'] = $success ? 'Heritage item saved successfully' : 'Failed to save heritage item';
    header('Location: heritage.php');
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM heritage_archive WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        $_SESSION['success'] = 'Heritage item deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete heritage item';
    }
    header('Location: heritage.php');
    exit;
}

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';

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
    'photos' => $pdo->query("SELECT COUNT(*) FROM heritage_archive WHERE content_type = 'image'")->fetchColumn(),
    'videos' => $pdo->query("SELECT COUNT(*) FROM heritage_archive WHERE content_type = 'video_link'")->fetchColumn()
];

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM heritage_archive WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heritage Archive Management</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        .stats-card { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border-radius: 15px; transition: all 0.3s; }
        .stats-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(139,69,19,0.3); }
        .filter-btn { border-radius: 25px; transition: all 0.3s; }
        .filter-btn.active { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; }
        .heritage-card { border-radius: 15px; transition: all 0.3s; }
        .heritage-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .category-badge { color: white; border-radius: 10px; }
        .category-badge.document { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); }
        .category-badge.photo { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .category-badge.video { background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); }
    </style>
</head>
<body>
    <?php include '../layouts/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../layouts/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php include '../layouts/alerts.php'; ?>
                
                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <h1 class="h2 mb-2" style="color: #8B4513;">
                            <i class="fas fa-scroll me-2"></i>የኢትዮጵያ ባህላዊ ቅርስ መዝገብ
                        </h1>
                        <h3 class="h5 text-muted mb-0">Ethiopian Heritage Archive Management</h3>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (!$edit_item): ?>
                        <button class="btn btn-lg" style="background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border: none; border-radius: 25px;" onclick="showAddModal()">
                            <i class="fas fa-plus me-2"></i>Add Heritage Item
                        </button>
                        <?php endif; ?>
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
                        <button class="btn filter-btn <?= $filter_type === 'image' ? 'active' : 'btn-outline-primary' ?>" onclick="filterContent('image')">
                            <i class="fas fa-camera me-1"></i>Images
                        </button>
                        <button class="btn filter-btn <?= $filter_type === 'video_link' ? 'active' : 'btn-outline-primary' ?>" onclick="filterContent('video_link')">
                            <i class="fas fa-play-circle me-1"></i>Videos
                        </button>
                    </div>
                </div>

                <!-- Heritage Items List -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($heritage_items)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Language</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($heritage_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['file_path'] && $item['content_type'] === 'image'): ?>
                                                <img src="../../<?= $item['file_path'] ?>" width="50" height="50" class="rounded me-2" style="object-fit: cover;">
                                                <?php else: ?>
                                                <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-2" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-<?= $item['content_type'] === 'video_link' ? 'play' : 'file' ?>"></i>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($item['title']) ?></strong>
                                                    <br><small class="text-muted"><?= substr(htmlspecialchars($item['description']), 0, 80) ?>...</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="category-badge <?= $item['content_type'] ?> px-2 py-1 small"><?= ucfirst($item['content_type']) ?></span>
                                            <?php if ($item['video_link']): ?>
                                            <br><a href="<?= htmlspecialchars($item['video_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">EN</span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($item['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editHeritage(<?= $item['id'] ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteHeritage(<?= $item['id'] ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h4>No Heritage Items</h4>
                            <p class="text-muted">Start building the heritage archive by adding cultural content.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Heritage Modal -->
    <div class="modal fade" id="heritageModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border-radius: 20px 20px 0 0;">
                    <h4 class="modal-title">
                        <i class="fas fa-scroll me-2"></i>
                        <?= $edit_item ? 'Edit Heritage Item' : 'Add Ethiopian Heritage Content' ?>
                    </h4>
                    <button type="button" class="btn-close btn-close-white" onclick="hideModal()"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body" style="padding: 2rem; background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
                        <?php if ($edit_item): ?>
                        <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
                        <?php endif; ?>
                        
                        <!-- Content Type Cards -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label h5" style="color: #8B4513;">Choose Heritage Content Type</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="card content-type-card" onclick="selectContentType('document')" style="cursor: pointer; border: 2px solid #ddd; transition: all 0.3s;">
                                            <div class="card-body text-center p-3">
                                                <i class="fas fa-file-alt fa-3x mb-2" style="color: #6c757d;"></i>
                                                <h6>Research & Documents</h6>
                                                <small class="text-muted">Historical information</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card content-type-card" onclick="selectContentType('image')" style="cursor: pointer; border: 2px solid #ddd; transition: all 0.3s;">
                                            <div class="card-body text-center p-3">
                                                <i class="fas fa-camera fa-3x mb-2" style="color: #28a745;"></i>
                                                <h6>Images</h6>
                                                <small class="text-muted">Photos of Ethiopian crafts</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card content-type-card" onclick="selectContentType('video')" style="cursor: pointer; border: 2px solid #ddd; transition: all 0.3s;">
                                            <div class="card-body text-center p-3">
                                                <i class="fas fa-play-circle fa-3x mb-2" style="color: #dc3545;"></i>
                                                <h6>Video Links</h6>
                                                <small class="text-muted">YouTube, Vimeo links</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="content_type" id="contentType" required>
                            </div>
                        </div>
                        
                        <!-- Title -->
                        <div class="mb-4">
                            <label class="form-label h6" style="color: #8B4513;">Heritage Title</label>
                            <input type="text" class="form-control form-control-lg" name="title" 
                                   value="<?= $edit_item ? htmlspecialchars($edit_item['title']) : '' ?>" 
                                   placeholder="Enter heritage item title..." required
                                   style="border-radius: 10px; border: 2px solid #D2691E;">
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label h6" style="color: #8B4513;">Heritage Description</label>
                            <textarea class="form-control" name="description" rows="4" required 
                                      placeholder="Describe this Ethiopian heritage content..."
                                      style="border-radius: 10px; border: 2px solid #D2691E;"><?= $edit_item ? htmlspecialchars($edit_item['description']) : '' ?></textarea>
                        </div>
                        
                        <!-- Main Category (for all types) -->
                        <div class="mb-4">
                            <label class="form-label h6" style="color: #8B4513;">Main Category</label>
                            <select class="form-select" name="main_category" style="border-radius: 10px; border: 2px solid #D2691E;">
                                <option value="pottery" <?= ($edit_item && $edit_item['main_category'] === 'pottery') ? 'selected' : '' ?>>Pottery</option>
                                <option value="weavery" <?= ($edit_item && $edit_item['main_category'] === 'weavery') ? 'selected' : '' ?>>Weavery</option>
                            </select>
                        </div>
                        
                        <!-- Content (hidden for all types) -->
                        <!-- Detailed Content field removed as requested -->
                        
                        <!-- Category and Language (for documents and videos only) -->
                        <div class="row mb-4" id="categoryLanguageFields">
                            <div class="col-md-6">
                                <label class="form-label h6" style="color: #8B4513;">Category</label>
                                <select class="form-select" name="category" style="border-radius: 10px; border: 2px solid #D2691E;">
                                    <option value="history" <?= ($edit_item && $edit_item['category'] === 'history') ? 'selected' : '' ?>>History</option>
                                    <option value="culture" <?= ($edit_item && $edit_item['category'] === 'culture') ? 'selected' : '' ?>>Culture</option>
                                    <option value="art" <?= ($edit_item && $edit_item['category'] === 'art') ? 'selected' : '' ?>>Art & Crafts</option>
                                    <option value="tradition" <?= ($edit_item && $edit_item['category'] === 'tradition') ? 'selected' : '' ?>>Traditions</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label h6" style="color: #8B4513;">Language</label>
                                <select class="form-select" name="language" style="border-radius: 10px; border: 2px solid #D2691E;">
                                    <option value="en" <?= ($edit_item && $edit_item['language'] === 'en') ? 'selected' : '' ?>>English</option>
                                    <option value="am" <?= ($edit_item && $edit_item['language'] === 'am') ? 'selected' : '' ?>>አማርኛ (Amharic)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Dynamic Fields -->
                        <div id="dynamicFields">
                            <!-- Document Upload -->
                            <div class="content-field" id="documentField" style="display:none;">
                                <div class="card" style="border: 2px dashed #6c757d; border-radius: 15px;">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-cloud-upload-alt fa-3x mb-3" style="color: #6c757d;"></i>
                                        <h6>Upload Research Document</h6>
                                        
                                        <input type="file" class="form-control" name="heritage_file" id="docFileInput" accept=".txt,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" required>
                                        <small class="text-muted mt-2 d-block">Upload any document type (PDF, Word, Excel, PowerPoint, Text)</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Image Upload -->
                            <div class="content-field" id="imageField" style="display:none;">
                                <div class="card" style="border: 2px dashed #28a745; border-radius: 15px;">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-images fa-3x mb-3" style="color: #28a745;"></i>
                                        <h6>Upload Heritage Image</h6>
                                        <input type="file" class="form-control" name="heritage_file" accept="image/*" onchange="previewImage(this)">
                                        <small class="text-muted mt-2 d-block">JPG, PNG, GIF images of Ethiopian crafts (Max 5MB)</small>
                                        <div id="imagePreview" class="mt-3" style="display:none;">
                                            <img id="previewImg" style="max-width: 200px; max-height: 200px; border-radius: 10px;">
                                        </div>
                                        
                                        <!-- Category for images only -->
                                        <div class="mt-3">
                                            <label class="form-label">Category</label>
                                            <select class="form-select" name="image_category" id="imageCategory">
                                                <option value="art" <?= ($edit_item && $edit_item['category'] === 'art') ? 'selected' : '' ?>>Art & Crafts</option>
                                                <option value="culture" <?= ($edit_item && $edit_item['category'] === 'culture') ? 'selected' : '' ?>>Culture</option>
                                                <option value="tradition" <?= ($edit_item && $edit_item['category'] === 'tradition') ? 'selected' : '' ?>>Traditions</option>
                                                <option value="history" <?= ($edit_item && $edit_item['category'] === 'history') ? 'selected' : '' ?>>History</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Video Link -->
                            <div class="content-field" id="videoField" style="display:none;">
                                <div class="card" style="border: 2px dashed #dc3545; border-radius: 15px;">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-video fa-3x mb-3" style="color: #dc3545;"></i>
                                        <h6>Add Video Link</h6>
                                        <input type="url" class="form-control form-control-lg" name="video_link" 
                                               value="<?= $edit_item ? htmlspecialchars($edit_item['video_link'] ?? '') : '' ?>"
                                               placeholder="https://youtube.com/watch?v=..."
                                               style="border-radius: 10px;">
                                        <small class="text-muted mt-2 d-block">YouTube, Vimeo, or other video platform links</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($edit_item && $edit_item['file_path']): ?>
                        <div class="alert alert-info mt-3" style="border-radius: 10px;">
                            <i class="fas fa-info-circle me-2"></i>
                            Current file: <strong><?= basename($edit_item['file_path']) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer" style="padding: 1.5rem 2rem; background: #f8f9fa; border-radius: 0 0 20px 20px;">
                        <button type="button" class="btn btn-secondary btn-lg" onclick="hideModal()" style="border-radius: 25px;">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="save_heritage" class="btn btn-lg" style="background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border: none; border-radius: 25px;">
                            <i class="fas fa-save me-2"></i>
                            <?= $edit_item ? 'Update Heritage' : 'Save Heritage Item' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('heritageModal').style.display = 'block';
            document.getElementById('heritageModal').classList.add('show');
            document.body.classList.add('modal-open');
        }

        function hideModal() {
            document.getElementById('heritageModal').style.display = 'none';
            document.getElementById('heritageModal').classList.remove('show');
            document.body.classList.remove('modal-open');
        }

        function editHeritage(id) {
            window.location.href = `heritage.php?edit=${id}`;
        }

        function deleteHeritage(id) {
            if (confirm('Are you sure you want to delete this heritage item?')) {
                window.location.href = `heritage.php?delete=${id}`;
            }
        }

        function filterContent(type) {
            window.location.href = `heritage.php?type=${type}`;
        }
        
        function selectContentType(type) {
            // Update hidden field
            document.getElementById('contentType').value = type;
            
            // Update card styles
            document.querySelectorAll('.content-type-card').forEach(card => {
                card.style.border = '2px solid #ddd';
                card.style.transform = 'scale(1)';
            });
            
            // Highlight selected card
            event.currentTarget.style.border = '2px solid #8B4513';
            event.currentTarget.style.transform = 'scale(1.05)';
            
            // Show/hide fields
            document.querySelectorAll('.content-field').forEach(field => field.style.display = 'none');
            
            // Show/hide category and language fields (hide for images)
            const categoryLanguageFields = document.getElementById('categoryLanguageFields');
            if (type === 'image') {
                categoryLanguageFields.style.display = 'none';
            } else {
                categoryLanguageFields.style.display = 'block';
            }
            
            if (type === 'document') {
                document.getElementById('documentField').style.display = 'block';
            } else if (type === 'image') {
                document.getElementById('imageField').style.display = 'block';
            } else if (type === 'video') {
                document.getElementById('videoField').style.display = 'block';
            }
        }
        
        // Auto-detect and enforce category based on file selection
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const fileName = file.name.toLowerCase();
                        const fileType = file.type;
                        
                        if (fileType.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp)$/.test(fileName)) {
                            selectContentTypeAuto('image');
                        } else if (fileType.startsWith('application/') || fileType.startsWith('text/') || /\.(pdf|doc|docx|txt)$/.test(fileName)) {
                            selectContentTypeAuto('document');
                        }
                    }
                });
            });
            
            const videoInput = document.querySelector('input[name="video_link"]');
            if (videoInput) {
                videoInput.addEventListener('input', function() {
                    if (this.value.trim()) {
                        selectContentTypeAuto('video');
                    }
                });
            }
        });
        
        function selectContentTypeAuto(type) {
            document.getElementById('contentType').value = type;
            
            // Update visual selection
            document.querySelectorAll('.content-type-card').forEach((card, index) => {
                const types = ['document', 'image', 'video'];
                if (types[index] === type) {
                    card.style.border = '2px solid #8B4513';
                    card.style.transform = 'scale(1.05)';
                } else {
                    card.style.border = '2px solid #ddd';
                    card.style.transform = 'scale(1)';
                }
            });
        }
        

        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        

        
        // Form validation before submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const contentType = document.getElementById('contentType').value;
                    
                    if (!contentType) {
                        e.preventDefault();
                        alert('Please select a content type (Document, Image, or Video)');
                        return false;
                    }
                    
                    // Remove disabled file inputs before submit
                    const allFileInputs = document.querySelectorAll('input[type="file"]');
                    allFileInputs.forEach(input => {
                        const parentField = input.closest('.content-field');
                        if (parentField && parentField.style.display === 'none') {
                            input.disabled = true;
                        } else {
                            input.disabled = false;
                        }
                    });
                    
                    // Validate document upload
                    if (contentType === 'document') {
                        const docInput = document.getElementById('docFileInput');
                        if (!docInput.files || docInput.files.length === 0) {
                            e.preventDefault();
                            alert('Please select a document to upload');
                            return false;
                        }
                    }
                    
                    // Validate image upload
                    if (contentType === 'image') {
                        const imageInput = document.querySelector('#imageField input[type="file"]');
                        if (!imageInput.files || imageInput.files.length === 0) {
                            e.preventDefault();
                            alert('Please select an image to upload');
                            return false;
                        }
                    }
                    
                    // Validate video link
                    if (contentType === 'video') {
                        const videoInput = document.querySelector('input[name="video_link"]');
                        if (!videoInput.value.trim()) {
                            e.preventDefault();
                            alert('Please enter a video link');
                            return false;
                        }
                    }
                });
            }
        });
        
        function toggleFields() {
            const type = document.getElementById('contentType').value;
            document.getElementById('documentField').style.display = type === 'document' ? 'block' : 'none';
            document.getElementById('imageField').style.display = type === 'photo' ? 'block' : 'none';
            document.getElementById('videoField').style.display = type === 'video' ? 'block' : 'none';
        }

        <?php if ($edit_item): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showAddModal();
            // Pre-select content type for editing
            const contentType = '<?= $edit_item['content_type'] ?>';
            document.getElementById('contentType').value = contentType;
            
            // Highlight the correct card
            document.querySelectorAll('.content-type-card').forEach((card, index) => {
                const types = ['document', 'image', 'video'];
                if (types[index] === contentType) {
                    card.style.border = '2px solid #8B4513';
                    card.style.transform = 'scale(1.05)';
                }
            });
            
            // Show correct field
            document.querySelectorAll('.content-field').forEach(field => field.style.display = 'none');
            if (contentType === 'document') {
                document.getElementById('documentField').style.display = 'block';
            } else if (contentType === 'image') {
                document.getElementById('imageField').style.display = 'block';
            } else if (contentType === 'video') {
                document.getElementById('videoField').style.display = 'block';
            }
        });
        <?php endif; ?>
    </script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>