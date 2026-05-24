<?php
session_start();
require_once '../../config/database_enhanced.php';
require_once '../../includes/functions.php';

// Get heritage items with author info
$stmt = $pdo->query("
    SELECT h.*, u.name as author_name 
    FROM heritage_archive h 
    LEFT JOIN users u ON h.uploaded_by = u.id 
    ORDER BY h.created_at DESC
");
$heritage_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ethiopian Heritage Archive - Ashreka Pottery</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="../../assets/css/slideshow.css" rel="stylesheet">
    <link href="../../assets/css/responsive-nav.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.05), rgba(0,0,0,0.05));
        }
        .heritage-header {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        .heritage-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border-left: 5px solid #8B4513;
            transition: all 0.3s ease;
        }
        .heritage-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .ethiopian-pattern {
            background: linear-gradient(45deg, #FFF8DC 25%, transparent 25%);
        }
        .cultural-icon {
            font-size: 3rem;
            color: #8B4513;
            margin-bottom: 1rem;
        }
        .heritage-details {
            background: rgba(255, 248, 220, 0.8);
            border-radius: 10px;
            padding: 15px;
            border-left: 4px solid #8B4513;
        }
        .heritage-details h6 {
            margin-bottom: 8px;
            font-weight: 600;
        }
        .heritage-details p {
            margin-bottom: 0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <img src="../../assets/images/ashru.jpeg" alt="Ashreka" height="40" class="me-2">
                Ashreka & Friends
            </a>
            
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <div class="hamburger-menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </div>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <!-- Language toggle handled by universal system -->
                    <a class="nav-link" href="../../index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-user"></i> Dashboard
                        </a>
                        <a class="nav-link" href="../../controllers/AuthController.php?action=logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="../auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Slideshow -->
    <div class="container mt-4">
        <?php include '../layouts/page_slideshow.php'; ?>
    </div>

    <!-- Heritage Header -->
    <div class="heritage-header">
        <div class="container">
            <h1 class="display-4 mb-3">
                <i class="fas fa-scroll me-3"></i>
                የኢትዮጵያ ባህላዊ ቅርስ መዝገብ
            </h1>
            <h2 class="h3 translate">Ethiopian Heritage Archive</h2>
            <p class="lead translate">Preserving the rich cultural traditions of Ethiopian artisans for future generations</p>
            <div class="row justify-content-center mt-4">
                <div class="col-md-4 text-center">
                    <div class="cultural-icon">🏺</div>
                    <h5 class="translate">Traditional Pottery</h5>
                </div>
                <div class="col-md-4 text-center">
                    <div class="cultural-icon">🧵</div>
                    <h5 class="translate">Ancient Weaving</h5>
                </div>
                <div class="col-md-4 text-center">
                    <div class="cultural-icon">📜</div>
                    <h5 class="translate">Cultural Stories</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <!-- Introduction Section -->
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto">
                <div class="heritage-card p-4 text-center ethiopian-pattern">
                    <h3 class="text-primary translate">About Ashreka & Friends Pottery Association</h3>
                    <p class="translate">
                        Located in Sebeta Mazoria, Ethiopia, our association brings together skilled artisans 
                        specializing in traditional pottery and weaving. Led by Ms. Ashreka Asmal with over 15 years 
                        of experience, we preserve Ethiopian cultural heritage while creating sustainable livelihoods.
                    </p>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6><i class="fas fa-map-marker-alt text-primary me-2"></i>Location</h6>
                            <p>Sebeta Mazoria, Ethiopia</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar text-primary me-2"></i>Established</h6>
                            <p>Traditional crafts spanning generations</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="heritage-card p-3">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-primary active" onclick="filterCategory('all')">All Heritage</button>
                        <button class="btn btn-outline-primary" onclick="filterCategory('document')">Research & Documents</button>
                        <button class="btn btn-outline-primary" onclick="filterCategory('image')">Images</button>
                        <button class="btn btn-outline-primary" onclick="filterCategory('video_link')">Videos</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Category Filter (shown when content type is selected) -->
        <div class="row mb-4" id="mainCategoryFilter" style="display: none;">
            <div class="col-12">
                <div class="heritage-card p-3">
                    <h6 class="mb-3 text-center">Filter by Main Category:</h6>
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-success active" onclick="filterMainCategory('all')">All</button>
                        <button class="btn btn-outline-success" onclick="filterMainCategory('pottery')">🏺 Pottery</button>
                        <button class="btn btn-outline-success" onclick="filterMainCategory('weavery')">🧵 Weavery</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Heritage Items -->
        <?php if (empty($heritage_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-scroll fa-3x text-muted mb-3"></i>
                <h5 class="translate">Heritage Archive Coming Soon</h5>
                <p class="text-muted translate">We are currently collecting and digitizing our cultural heritage content</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($heritage_items as $item): ?>
                <div class="col-lg-4 col-md-6 mb-4 heritage-item" data-category="<?= $item['content_type'] ?>" data-main-category="<?= $item['main_category'] ?? 'pottery' ?>">
                    <div class="heritage-card h-100 shadow" style="min-height: 500px;">
                        <?php if ($item['file_path'] && $item['content_type'] === 'image'): ?>
                            <img src="../../<?= $item['file_path'] ?>" class="card-img-top" 
                                 alt="<?= $item['title'] ?>" style="height: 250px; object-fit: cover; border-radius: 15px 15px 0 0;">
                        <?php elseif ($item['video_link']): ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-primary text-white" 
                                 style="height: 150px; border-radius: 15px 15px 0 0;">
                                <div class="text-center">
                                    <i class="fas fa-play-circle fa-2x mb-2"></i>
                                    <br><span class="translate">Ethiopian Heritage Video</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-secondary text-white" 
                                 style="height: 150px; border-radius: 15px 15px 0 0;">
                                <div class="text-center">
                                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                                    <br><span class="translate">Research & Documents</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="heritage-details">
                                <h6><i class="fas fa-heading text-primary me-1"></i>Title:</h6>
                                <p class="mb-3"><?= htmlspecialchars($item['title']) ?></p>
                                
                                <h6><i class="fas fa-align-left text-primary me-1"></i>Description:</h6>
                                <p class="mb-3"><?= htmlspecialchars($item['description']) ?></p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-tag text-primary me-1"></i>Category:</h6>
                                        <p class="mb-2"><?= ucfirst($item['category'] ?? 'General') ?></p>
                                        
                                        <h6><i class="fas fa-layer-group text-success me-1"></i>Content:</h6>
                                        <p class="mb-2"><?= ucfirst($item['main_category'] ?? 'Pottery') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($item['content_type'] !== 'image'): ?>
                                        <h6><i class="fas fa-language text-info me-1"></i>Language:</h6>
                                        <p class="mb-2"><?= $item['language'] === 'am' ? 'አማርኛ (Amharic)' : 'English' ?></p>
                                        <?php endif; ?>
                                        
                                        <h6><i class="fas fa-file-alt text-warning me-1"></i>Type:</h6>
                                        <p class="mb-2"><?= ucfirst(str_replace('_', ' ', $item['content_type'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-calendar me-1"></i>Uploaded Date: 
                                <?= date('F j, Y', strtotime($item['created_at'])) ?>
                            </small>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2">
                                <?php if ($item['video_link']): ?>
                                    <a href="<?= htmlspecialchars($item['video_link']) ?>" target="_blank" class="btn btn-primary flex-fill">
                                        <i class="fas fa-external-link-alt me-2"></i>
                                        <span class="translate">Watch Video</span>
                                    </a>
                                <?php elseif ($item['file_path'] && $item['content_type'] === 'image'): ?>
                                    <button class="btn btn-primary flex-fill" onclick="viewImage('../../<?= $item['file_path'] ?>', '<?= htmlspecialchars($item['title']) ?>', '<?= htmlspecialchars($item['description']) ?>')">
                                        <i class="fas fa-eye me-2"></i>
                                        <span class="translate">View</span>
                                    </button>
                                    <a href="../../<?= $item['file_path'] ?>" download="<?= htmlspecialchars($item['title']) ?>" class="btn btn-success flex-fill">
                                        <i class="fas fa-download me-2"></i>
                                        <span class="translate">Download</span>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-primary flex-fill" onclick="previewDocument(<?= $item['id'] ?>, '<?= htmlspecialchars($item['title']) ?>')">
                                        <i class="fas fa-eye me-2"></i>
                                        <span class="translate">Preview Document</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Cultural Information Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="heritage-card p-4 ethiopian-pattern">
                    <h3 class="text-center text-primary mb-4 translate">Ethiopian Artisan Traditions</h3>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h5><i class="fas fa-fire text-danger me-2"></i>Pottery Making</h5>
                            <p class="translate">
                                Traditional Ethiopian pottery involves hand-shaping clay using techniques passed down through generations. 
                                Artisans use local clay, natural firing methods, and traditional tools to create functional and decorative pieces.
                            </p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Jebena (Coffee pots)</li>
                                <li><i class="fas fa-check text-success me-2"></i>Mitad (Injera plates)</li>
                                <li><i class="fas fa-check text-success me-2"></i>Storage containers</li>
                                <li><i class="fas fa-check text-success me-2"></i>Decorative items</li>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h5><i class="fas fa-cut text-info me-2"></i>Traditional Weaving</h5>
                            <p class="translate">
                                Ethiopian weaving creates beautiful textiles using traditional looms and techniques. 
                                Artisans work with cotton and other natural fibers to produce clothing and household items.
                            </p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Habesha Kemis (Traditional dresses)</li>
                                <li><i class="fas fa-check text-success me-2"></i>Gabi (Traditional shawls)</li>
                                <li><i class="fas fa-check text-success me-2"></i>Netela (Cotton wraps)</li>
                                <li><i class="fas fa-check text-success me-2"></i>Table runners and scarves</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Modal -->
    <div class="modal fade" id="documentModal" tabindex="-1">
        <div class="modal-dialog modal-lg" style="max-width: 80%; height: 90vh;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="documentModalTitle"></h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm" id="downloadBtn">
                            <i class="fas fa-download me-1"></i>Download
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-0" style="height: calc(100% - 60px); overflow-y: auto;">
                    <div id="documentViewer" class="w-100 h-100"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" class="img-fluid" alt="Heritage Image">
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/lang_universal.php'; ?>
    <script type="text/javascript">
        // Initialize global filter state
        window.currentContentType = 'all';
        window.currentMainCategory = 'all';

        function viewImage(imagePath, title, description) {
            document.getElementById('imageModalTitle').textContent = title;
            
            const modalBody = document.querySelector('#imageModal .modal-body');
            modalBody.innerHTML = `
                <img class="img-fluid mb-3" src="${imagePath}" alt="Heritage Image">
                <div class="heritage-details">
                    <div class="mb-3">
                        <h6 class="text-primary"><i class="fas fa-heading me-2"></i>Heritage Title:</h6>
                        <p class="ms-3">${title}</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-primary"><i class="fas fa-align-left me-2"></i>Heritage Description:</h6>
                        <p class="ms-3">${description}</p>
                    </div>
                </div>
            `;
            
            const modal = document.getElementById('imageModal');
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }

        function previewDocument(id, title) {
            document.getElementById('documentModalTitle').textContent = title;
            const viewer = document.getElementById('documentViewer');
            
            viewer.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100"><div class="spinner-border text-primary" role="status"></div></div>';
            
            fetch(`../../api/document_preview.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    console.log('API Response:', data); // Debug log
                    const downloadBtn = document.getElementById('downloadBtn');
                    
                    if (data.file_path) {
                        downloadBtn.onclick = () => {
                            const a = document.createElement('a');
                            a.href = `../../${data.file_path}`;
                            a.download = data.title || 'document';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        };
                    } else {
                        downloadBtn.style.display = 'none';
                    }
                    
                    if (data.success && data.content) {
                        let viewerContent = '';
                        
                        // Special handling for PDF files
                        if (data.file_path && data.file_type === 'pdf') {
                            viewerContent = `
                                <div class="p-4">
                                    <div class="mb-4">
                                        <h5 class="text-primary">${data.title || 'Document'}</h5>
                                        <p class="text-muted">${data.description || 'Heritage document'}</p>
                                    </div>
                                    <div class="mb-3">
                                        <iframe src="../../${data.file_path}" width="100%" height="500px" style="border: 1px solid #ddd; border-radius: 5px;"></iframe>
                                    </div>
                                    <div class="text-center mt-4">
                                        <button class="btn btn-success btn-lg" onclick="downloadFile('../../${data.file_path}', '${data.title || 'document'}')">
                                            <i class="fas fa-download me-2"></i>Download PDF
                                        </button>
                                    </div>
                                </div>
                            `;
                        } else {
                            // Regular text content display
                            viewerContent = `
                                <div class="p-4">
                                    <div class="mb-4">
                                        <h5 class="text-primary">${data.title || 'Document'}</h5>
                                        <p class="text-muted">${data.description || 'Heritage document'}</p>
                                    </div>
                                    <div class="border rounded p-3" style="background: #f8f9fa; max-height: 60vh; overflow-y: auto;">
                                        <pre style="white-space: pre-wrap; font-family: 'Segoe UI', sans-serif; line-height: 1.6; margin: 0;">${data.content}</pre>
                                    </div>
                                    ${data.file_path ? `
                                    <div class="text-center mt-4">
                                        <button class="btn btn-success btn-lg" onclick="downloadFile('../../${data.file_path}', '${data.title || 'document'}')">
                                            <i class="fas fa-download me-2"></i>Download Full Document
                                        </button>
                                    </div>
                                    ` : ''}
                                </div>
                            `;
                        }
                        
                        viewer.innerHTML = viewerContent;
                    } else {
                        viewer.innerHTML = `
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center">
                                    <i class="fas fa-file-alt fa-5x text-muted mb-3"></i>
                                    <h4>${data.title || 'Document'}</h4>
                                    <p class="text-muted">${data.description || 'Heritage document'}</p>
                                    <p class="text-warning">${data.error || 'Preview not available for this document type'}</p>
                                    ${data.file_path ? `
                                    <button class="btn btn-primary btn-lg" onclick="downloadFile('../../${data.file_path}', '${data.title || 'document'}')">
                                        <i class="fas fa-download me-2"></i>Download to View
                                    </button>
                                    ` : '<p class="text-danger">No file available for download</p>'}
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
        
        // Close modal when clicking close button or backdrop
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-close') || e.target.classList.contains('modal')) {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                });
                document.body.classList.remove('modal-open');
            }
        });

        function filterCategory(category) {
            const items = document.querySelectorAll('.heritage-item');
            const buttons = document.querySelectorAll('.btn-group .btn');
            const mainCategoryFilter = document.getElementById('mainCategoryFilter');
            
            // Remove any existing no results message first
            const existingMessage = document.getElementById('noResultsMessage');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Store current category for main category filter
            window.currentContentType = category;
            
            if (category === 'all') {
                mainCategoryFilter.style.display = 'none';
                // Show all items
                items.forEach(item => {
                    item.style.display = 'block';
                });
                // No message needed for 'all' since we always have items
            } else {
                mainCategoryFilter.style.display = 'block';
                // Reset main category to 'All'
                const mainButtons = mainCategoryFilter.querySelectorAll('.btn');
                mainButtons.forEach(btn => btn.classList.remove('active'));
                mainButtons[0].classList.add('active');
                window.currentMainCategory = 'all';
                
                // Filter by content type only and count visible items
                let visibleCount = 0;
                items.forEach(item => {
                    if (item.dataset.category === category) {
                        item.style.display = 'block';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Only show no results message if truly no items found
                if (visibleCount === 0) {
                    showNoResultsMessage(0, category, 'all');
                }
            }
        }
        
        function filterMainCategory(mainCategory) {
            const buttons = document.querySelectorAll('#mainCategoryFilter .btn');
            const items = document.querySelectorAll('.heritage-item');
            
            // Remove any existing no results message first
            const existingMessage = document.getElementById('noResultsMessage');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Store current main category
            window.currentMainCategory = mainCategory;
            
            // Get current content type
            const contentType = window.currentContentType || 'all';
            
            // Apply both filters and count visible items
            let visibleCount = 0;
            items.forEach(item => {
                const matchesContent = contentType === 'all' || item.dataset.category === contentType;
                const matchesMain = mainCategory === 'all' || item.dataset.mainCategory === mainCategory;
                
                if (matchesContent && matchesMain) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Only show no results message if truly no items found
            if (visibleCount === 0) {
                showNoResultsMessage(0, contentType, mainCategory);
            }
        }
        
        function showNoResultsMessage(count, contentType, mainCategory) {
            // Remove existing message
            const existingMessage = document.getElementById('noResultsMessage');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            if (count === 0) {
                const container = document.querySelector('.row:has(.heritage-item)');
                const noResultsDiv = document.createElement('div');
                noResultsDiv.id = 'noResultsMessage';
                noResultsDiv.className = 'col-12 text-center py-5';
                
                let message = '';
                const categoryName = contentType === 'document' ? 'documents' : 
                                   contentType === 'image' ? 'images' : 
                                   contentType === 'video_link' ? 'videos' : 'items';
                
                if (mainCategory === 'pottery') {
                    message = `No ${categoryName} found in Pottery category yet. Check back later for pottery-related heritage content.`;
                } else if (mainCategory === 'weavery') {
                    message = `No ${categoryName} found in Weavery category yet. Check back later for weaving-related heritage content.`;
                } else {
                    message = `No ${categoryName} found matching your filter criteria.`;
                }
                
                noResultsDiv.innerHTML = `
                    <div class="heritage-card p-4">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nothing Here Yet</h5>
                        <p class="text-muted">${message}</p>
                    </div>
                `;
                container.appendChild(noResultsDiv);
            }
        }

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/responsive-nav.js"></script>
    <script src="../../assets/js/responsive-nav.js"></script>
</body>
</html>