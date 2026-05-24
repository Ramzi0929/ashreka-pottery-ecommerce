<?php
session_start();
require_once '../../config/database_enhanced.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: heritage.php');
    exit;
}

// Get heritage item
$stmt = $pdo->prepare("SELECT * FROM heritage_archive WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: heritage.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['title']) ?> - Heritage View</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #8B4513 0%, #D2691E 50%, #FFF8DC 100%); min-height: 100vh; }
        .heritage-container { background: rgba(255,255,255,0.95); border-radius: 20px; backdrop-filter: blur(10px); }
        .heritage-header { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border-radius: 20px 20px 0 0; }
        .content-badge { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="heritage-container">
            <!-- Header -->
            <div class="heritage-header p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1"><?= htmlspecialchars($item['title']) ?></h2>
                        <span class="content-badge px-3 py-1">
                            <i class="fas fa-<?= $item['content_type'] === 'video' ? 'play-circle' : ($item['content_type'] === 'photo' ? 'camera' : 'file-alt') ?> me-2"></i>
                            <?= ucfirst($item['content_type']) ?>
                        </span>
                    </div>
                    <button class="btn btn-light" onclick="window.close()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Content -->
            <div class="p-4">
                <?php if ($item['content_type'] === 'photo' && $item['file_path']): ?>
                    <!-- Image Display -->
                    <div class="text-center mb-4">
                        <img src="../../<?= $item['file_path'] ?>" class="img-fluid rounded" style="max-height: 400px;" alt="<?= htmlspecialchars($item['title']) ?>">
                    </div>
                <?php elseif ($item['content_type'] === 'video' && $item['video_link']): ?>
                    <!-- Video Link -->
                    <div class="text-center mb-4">
                        <div class="card" style="max-width: 500px; margin: 0 auto;">
                            <div class="card-body text-center">
                                <i class="fas fa-play-circle fa-5x text-danger mb-3"></i>
                                <h5>Ethiopian Heritage Video</h5>
                                <a href="<?= htmlspecialchars($item['video_link']) ?>" target="_blank" class="btn btn-danger btn-lg">
                                    <i class="fas fa-external-link-alt me-2"></i>Watch Video
                                </a>
                            </div>
                        </div>
                    </div>
                <?php elseif ($item['content_type'] === 'document' && $item['file_path']): ?>
                    <!-- Document Display -->
                    <div class="text-center mb-4">
                        <div class="card" style="max-width: 500px; margin: 0 auto;">
                            <div class="card-body text-center">
                                <i class="fas fa-file-alt fa-5x text-secondary mb-3"></i>
                                <h5>Heritage Document</h5>
                                <button class="btn btn-primary btn-lg" onclick="previewDocument(<?= $item['id'] ?>)">
                                    <i class="fas fa-eye me-2"></i>Preview Document
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Description -->
                <div class="card">
                    <div class="card-header" style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
                        <h5 class="mb-0" style="color: #8B4513;">
                            <i class="fas fa-info-circle me-2"></i>Heritage Description
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="lead"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                        
                        <hr>
                        
                        <div class="row text-muted">
                            <div class="col-md-6">
                                <small>
                                    <i class="fas fa-calendar me-1"></i>
                                    Created: <?= date('F j, Y g:i A', strtotime($item['created_at'])) ?>
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small>
                                    <i class="fas fa-user me-1"></i>
                                    Heritage ID: #<?= $item['id'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Document Modal -->
    <div class="modal fade" id="documentModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><?= htmlspecialchars($item['title']) ?></h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm" onclick="downloadDocument()">
                            <i class="fas fa-download me-1"></i>Download
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div id="documentViewer" class="w-100 h-100"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function previewDocument(id) {
            const viewer = document.getElementById('documentViewer');
            
            viewer.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100"><div class="spinner-border text-primary" role="status"></div></div>';
            
            fetch(`../../api/document_preview.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    console.log('API Response:', data); // Debug log
                    if (data.success && data.content) {
                        viewer.innerHTML = `
                            <div class="p-4">
                                <div class="mb-4">
                                    <h5 class="text-primary">${data.title || 'Document'}</h5>
                                    <p class="text-muted">${data.description || 'Heritage document'}</p>
                                </div>
                                <div class="border rounded p-3" style="background: #f8f9fa; max-height: 60vh; overflow-y: auto;">
                                    <pre style="white-space: pre-wrap; font-family: 'Segoe UI', sans-serif; line-height: 1.6; margin: 0;">${data.content}</pre>
                                </div>
                                <div class="text-center mt-4">
                                    <button class="btn btn-success btn-lg" onclick="downloadFile('../../${data.file_path}', '${data.title || 'document'}')">
                                        <i class="fas fa-download me-2"></i>Download Full Document
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        viewer.innerHTML = `
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center">
                                    <i class="fas fa-file-alt fa-5x text-muted mb-3"></i>
                                    <h4>${data.title || 'Document'}</h4>
                                    <p class="text-muted">${data.description || 'Heritage document'}</p>
                                    <p class="text-warning">Preview not available for this document type</p>
                                    <button class="btn btn-primary btn-lg" onclick="downloadFile('../../${data.file_path}', '${data.title || 'document'}')">
                                        <i class="fas fa-download me-2"></i>Download to View
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(() => {
                    viewer.innerHTML = '<div class="p-4 text-center"><p class="text-danger">Error loading document preview</p></div>';
                });
            
            const modal = document.getElementById('documentModal');
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }
        
        function downloadFile(filePath, fileName) {
            const a = document.createElement('a');
            a.href = filePath;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
    
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>