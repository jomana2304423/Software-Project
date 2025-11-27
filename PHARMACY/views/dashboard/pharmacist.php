<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role(['Pharmacist', 'Admin']);

$config = require __DIR__.'/../../config/config.php';

// Get dashboard statistics
$low_stock_count = get_low_stock_count();
$expiry_count = get_expiry_count();
$sales_today = get_today_sales_count();

$page_title = 'Pharmacist Dashboard';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-person-badge"></i> Pharmacist Dashboard
                <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>!</small>
            </h2>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $low_stock_count; ?></h4>
                            <p class="card-text">Low Stock Items</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $expiry_count; ?></h4>
                            <p class="card-text">Expiring Soon</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $sales_today; ?></h4>
                            <p class="card-text">Sales Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cart-plus text-primary" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Process Sale</h5>
                    <p class="card-text">Start a new sales transaction</p>
                    <a href="sales/cart.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right"></i> New Sale
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-capsule text-success" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Manage Medicines</h5>
                    <p class="card-text">View and manage inventory</p>
                    <a href="medicines/list.php" class="btn btn-success">
                        <i class="bi bi-arrow-right"></i> View Medicines
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-file-medical text-info" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Prescriptions</h5>
                    <p class="card-text">Review uploaded prescriptions</p>
                    <a href="prescriptions/review.php" class="btn btn-info">
                        <i class="bi bi-arrow-right"></i> Review
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-receipt text-warning" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Sales History</h5>
                    <p class="card-text">View recent sales transactions</p>
                    <a href="sales/history.php" class="btn btn-warning">
                        <i class="bi bi-arrow-right"></i> View History
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Alerts -->
    <?php if ($low_stock_count > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle"></i> Low Stock Alert</h5>
                <p class="mb-0">You have <?php echo $low_stock_count; ?> medicine(s) with low stock. 
                <a href="medicines/list.php?filter=low_stock" class="alert-link">View low stock items</a></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Expiry Alerts -->
    <?php if ($expiry_count > 0): ?>
    <div class="row mt-2">
        <div class="col-12">
            <div class="alert alert-danger">
                <h5><i class="bi bi-calendar-x"></i> Expiry Alert</h5>
                <p class="mb-0">You have <?php echo $expiry_count; ?> medicine(s) expiring within 30 days. 
                <a href="medicines/list.php?filter=expiring" class="alert-link">View expiring items</a></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
