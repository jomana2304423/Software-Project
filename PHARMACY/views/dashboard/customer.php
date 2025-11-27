<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role('Customer');

$config = require __DIR__.'/../../config/config.php';

// Get customer ID
$customer_id = get_customer_id_by_user($_SESSION['user']['id']);

// Get customer's recent orders
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name as pharmacist_name
        FROM sales s
        LEFT JOIN users u ON s.pharmacist_id = u.id
        WHERE s.customer_id = ?
        ORDER BY s.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$customer_id]);
    $recent_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_orders = [];
}

// Get customer's prescriptions
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as reviewed_by_name
        FROM prescriptions p
        LEFT JOIN users u ON p.reviewed_by = u.id
        WHERE p.customer_id = ?
        ORDER BY p.uploaded_at DESC
        LIMIT 5
    ");
    $stmt->execute([$customer_id]);
    $recent_prescriptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_prescriptions = [];
}

$page_title = 'Customer Dashboard';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-house-door"></i> Customer Dashboard
                <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>!</small>
            </h2>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-capsule text-primary" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Browse Medicines</h5>
                    <p class="card-text">View available medicines and place orders</p>
                    <a href="../customers/medicines.php" class="btn btn-primary">
                        <i class="bi bi-search"></i> Browse Catalog
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cloud-upload text-success" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Upload Prescription</h5>
                    <p class="card-text">Upload your prescription for review</p>
                    <a href="../prescriptions/upload.php" class="btn btn-success">
                        <i class="bi bi-cloud-upload"></i> Upload Now
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-receipt text-info" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">My Orders</h5>
                    <p class="card-text">View your order history and status</p>
                    <a href="../customers/orders.php" class="btn btn-info">
                        <i class="bi bi-receipt"></i> View Orders
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-file-medical text-warning" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">My Prescriptions</h5>
                    <p class="card-text">View your prescription history</p>
                    <a href="../prescriptions/view.php" class="btn btn-warning">
                        <i class="bi bi-file-medical"></i> View Prescriptions
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt"></i> Recent Orders
                    </h5>
                    <a href="../customers/orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-receipt" style="font-size: 3rem;"></i>
                            <p class="mt-2">No orders yet</p>
                            <a href="../customers/medicines.php" class="btn btn-primary">Browse Medicines</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">Invoice #<?php echo htmlspecialchars($order['invoice_no']); ?></h6>
                                            <p class="mb-1 text-muted">
                                                <i class="bi bi-calendar"></i> <?php echo format_datetime($order['created_at']); ?>
                                            </p>
                                            <?php if ($order['pharmacist_name']): ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($order['pharmacist_name']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success">₹<?php echo number_format($order['total'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Prescriptions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-file-medical"></i> Recent Prescriptions
                    </h5>
                    <a href="../prescriptions/view.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_prescriptions)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-file-medical" style="font-size: 3rem;"></i>
                            <p class="mt-2">No prescriptions uploaded yet</p>
                            <a href="../prescriptions/upload.php" class="btn btn-primary">Upload Prescription</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_prescriptions as $prescription): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">Prescription #<?php echo $prescription['id']; ?></h6>
                                            <p class="mb-1 text-muted">
                                                <i class="bi bi-calendar"></i> <?php echo format_datetime($prescription['uploaded_at']); ?>
                                            </p>
                                            <?php if ($prescription['reviewed_by_name']): ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-person-check"></i> <?php echo htmlspecialchars($prescription['reviewed_by_name']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <?php
                                            $status_class = '';
                                            switch ($prescription['status']) {
                                                case 'Approved':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'Rejected':
                                                    $status_class = 'bg-danger';
                                                    break;
                                                default:
                                                    $status_class = 'bg-warning';
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($prescription['status']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


