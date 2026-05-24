<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Approvals - Manager Dashboard</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .artisan-card { transition: all 0.3s; }
        .artisan-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <?php include '../layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark">
                            <h4><i class="fas fa-user-check me-2"></i>Pending Artisan Approvals</h4>
                        </div>
                        <div class="card-body">
                            <div id="artisansContainer">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-warning" role="status"></div>
                                    <p class="mt-2">Loading pending artisan registrations...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Artisan Details Modal -->
    <div class="modal fade" id="artisanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Artisan Registration Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="artisanDetails">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="rejectArtisanBtn" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Reject
                    </button>
                    <button type="button" id="approveArtisanBtn" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Approve
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentArtisanId = null;

        // Load pending artisans
        async function loadPendingArtisans() {
            try {
                const response = await fetch('../../api/pending_artisans.php');
                const result = await response.json();
                
                if (result.success) {
                    displayArtisans(result.data);
                } else {
                    document.getElementById('artisansContainer').innerHTML = 
                        '<div class="alert alert-info">No pending artisan registrations</div>';
                }
            } catch (error) {
                console.error('Error loading artisans:', error);
                document.getElementById('artisansContainer').innerHTML = 
                    '<div class="alert alert-danger">Error loading artisan registrations</div>';
            }
        }

        function displayArtisans(artisans) {
            const container = document.getElementById('artisansContainer');
            
            if (artisans.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No pending artisan registrations</div>';
                return;
            }

            let html = '<div class="row">';
            
            artisans.forEach(artisan => {
                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card artisan-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">${artisan.name}</h6>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Email:</strong> ${artisan.email}
                                </div>
                                <div class="mb-2">
                                    <strong>Phone:</strong> ${artisan.phone}
                                </div>
                                <div class="mb-2">
                                    <strong>Location:</strong> ${artisan.location}
                                </div>
                                <div class="mb-2">
                                    <strong>Specialization:</strong> ${artisan.specialization}
                                </div>
                                <div class="mb-2">
                                    <strong>Experience:</strong> ${artisan.experience_years} years
                                </div>
                                <div class="mb-2">
                                    <strong>Registered:</strong> ${new Date(artisan.created_at).toLocaleDateString()}
                                </div>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary btn-sm w-100" 
                                        onclick="viewArtisanDetails(${artisan.id}, '${artisan.name}', '${artisan.email}', '${artisan.phone}', '${artisan.location}', '${artisan.specialization}', '${artisan.experience_years}', '${artisan.bio || ''}', '${artisan.created_at}')">
                                    <i class="fas fa-eye me-1"></i>Review Application
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        function viewArtisanDetails(id, name, email, phone, location, specialization, experience, bio, createdAt) {
            currentArtisanId = id;

            document.getElementById('artisanDetails').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Personal Information</h6>
                        <div class="mb-2"><strong>Full Name:</strong> ${name}</div>
                        <div class="mb-2"><strong>Email:</strong> ${email}</div>
                        <div class="mb-2"><strong>Phone:</strong> ${phone}</div>
                        <div class="mb-2"><strong>Location:</strong> ${location}</div>
                        <div class="mb-2"><strong>Registration Date:</strong> ${new Date(createdAt).toLocaleDateString()}</div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success mb-3">Professional Information</h6>
                        <div class="mb-2"><strong>Specialization:</strong> ${specialization}</div>
                        <div class="mb-2"><strong>Experience:</strong> ${experience} years</div>
                        ${bio ? `<div class="mb-2"><strong>Bio:</strong> ${bio}</div>` : ''}
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Review the artisan's information carefully before approving or rejecting their registration.
                </div>
            `;

            const modal = new bootstrap.Modal(document.getElementById('artisanModal'));
            modal.show();
        }

        // Approve artisan
        document.getElementById('approveArtisanBtn').addEventListener('click', async function() {
            if (!currentArtisanId) return;

            try {
                const response = await fetch('../../api/artisan_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'approve',
                        artisan_id: currentArtisanId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('artisanModal')).hide();
                    alert('Artisan approved successfully! They can now access their dashboard.');
                    loadPendingArtisans(); // Reload list
                } else {
                    alert('Failed to approve artisan: ' + result.message);
                }
            } catch (error) {
                console.error('Error approving artisan:', error);
                alert('Error approving artisan');
            }
        });

        // Reject artisan
        document.getElementById('rejectArtisanBtn').addEventListener('click', async function() {
            if (!currentArtisanId) return;

            const reason = prompt('Please provide a reason for rejection:');
            if (!reason) return;

            try {
                const response = await fetch('../../api/artisan_approval.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'reject',
                        artisan_id: currentArtisanId,
                        reason: reason
                    })
                });

                const result = await response.json();

                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('artisanModal')).hide();
                    alert('Artisan registration rejected.');
                    loadPendingArtisans(); // Reload list
                } else {
                    alert('Failed to reject artisan: ' + result.message);
                }
            } catch (error) {
                console.error('Error rejecting artisan:', error);
                alert('Error rejecting artisan');
            }
        });

        // Load artisans on page load
        loadPendingArtisans();

        // Auto-refresh every 30 seconds
        setInterval(loadPendingArtisans, 30000);
    </script>
</body>
</html>