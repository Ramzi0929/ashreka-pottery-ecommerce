<?php
session_start();
require_once '../../config/database_enhanced.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../auth/login.php');
    exit;
}

// Get wallet statistics
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN transaction_type = 'payment_received' THEN amount ELSE 0 END) as total_received,
        SUM(CASE WHEN transaction_type = 'artisan_payout' THEN amount ELSE 0 END) as total_paid_out,
        SUM(CASE WHEN transaction_type = 'payment_received' THEN amount ELSE 0 END) - 
        SUM(CASE WHEN transaction_type = 'artisan_payout' THEN amount ELSE 0 END) as company_balance
    FROM wallet_transactions 
    WHERE status = 'completed'
");
$stmt->execute();
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - Manager Dashboard</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card { transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .transaction-row { transition: all 0.3s; }
        .transaction-row:hover { background-color: #f8f9fa; }
    </style>
</head>
<body style="background: linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 100%);">
    <?php include '../layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total Received</h6>
                                    <h3 class="mb-0"><?= number_format($stats['total_received'] ?? 0) ?> ETB</h3>
                                </div>
                                <i class="fas fa-arrow-down fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Paid to Artisans</h6>
                                    <h3 class="mb-0"><?= number_format($stats['total_paid_out'] ?? 0) ?> ETB</h3>
                                </div>
                                <i class="fas fa-arrow-up fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Company Balance</h6>
                                    <h3 class="mb-0"><?= number_format($stats['company_balance'] ?? 0) ?> ETB</h3>
                                </div>
                                <i class="fas fa-wallet fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4><i class="fas fa-list me-2"></i>Transaction History</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Order ID</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Recipient</th>
                                            <th>Status</th>
                                            <th>Processed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->prepare("
                                            SELECT 
                                                wt.*,
                                                CASE 
                                                    WHEN wt.recipient_type = 'artisan' THEN a.name
                                                    ELSE 'Company'
                                                END as recipient_name,
                                                u.name as processed_by_name
                                            FROM wallet_transactions wt
                                            LEFT JOIN artisans a ON wt.recipient_id = a.id AND wt.recipient_type = 'artisan'
                                            LEFT JOIN users u ON wt.processed_by = u.id
                                            ORDER BY wt.created_at DESC
                                            LIMIT 50
                                        ");
                                        $stmt->execute();
                                        $transactions = $stmt->fetchAll();
                                        
                                        foreach ($transactions as $transaction):
                                            $typeColor = $transaction['transaction_type'] === 'payment_received' ? 'success' : 'warning';
                                            $statusColor = $transaction['status'] === 'completed' ? 'success' : 'warning';
                                        ?>
                                        <tr class="transaction-row">
                                            <td><?= date('M j, Y H:i', strtotime($transaction['created_at'])) ?></td>
                                            <td>
                                                <a href="#" class="text-decoration-none">#<?= $transaction['order_id'] ?></a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $typeColor ?>">
                                                    <?= ucwords(str_replace('_', ' ', $transaction['transaction_type'])) ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold">
                                                <?= $transaction['transaction_type'] === 'payment_received' ? '+' : '-' ?>
                                                <?= number_format($transaction['amount']) ?> ETB
                                            </td>
                                            <td><?= htmlspecialchars($transaction['recipient_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $statusColor ?>">
                                                    <?= ucfirst($transaction['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($transaction['processed_by_name'] ?? 'System') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No transactions found
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>