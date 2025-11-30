<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Admin');

$config = require __DIR__.'/../../app/config/config.php';

// Get dashboard statistics
$low_stock_count = get_low_stock_count();
$expiry_count = get_expiry_count();
$sales_today = get_today_sales_count();
$notifications_count = get_unread_notifications_count();

$page_title = 'Admin Dashboard';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-speedometer2"></i> Admin Dashboard
                <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>!</small>
            </h2>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
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
        
        <div class="col-md-3">
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
        
        <div class="col-md-3">
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
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $notifications_count; ?></h4>
                            <p class="card-text">Notifications</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-bell" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-capsule text-primary" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Manage Medicines</h5>
                    <p class="card-text">Add, edit, and manage medicine inventory</p>
                    <a href="medicines/list.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right"></i> Go to Medicines
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-truck text-success" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Manage Suppliers</h5>
                    <p class="card-text">Add and manage supplier information</p>
                    <a href="suppliers/list.php" class="btn btn-success">
                        <i class="bi bi-arrow-right"></i> Go to Suppliers
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up text-info" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">View Reports</h5>
                    <p class="card-text">Generate sales and inventory reports</p>
                    <a href="reports/index.php" class="btn btn-info">
                        <i class="bi bi-arrow-right"></i> Go to Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT al.*, u.full_name 
                            FROM activity_logs al 
                            LEFT JOIN users u ON al.user_id = u.id 
                            ORDER BY al.created_at DESC 
                            LIMIT 10
                        ");
                        $stmt->execute();
                        $activities = $stmt->fetchAll();
                        
                        if (empty($activities)) {
                            echo '<p class="text-muted">No recent activity.</p>';
                        } else {
                            echo '<div class="list-group list-group-flush">';
                            foreach ($activities as $activity) {
                                echo '<div class="list-group-item">';
                                echo '<div class="d-flex w-100 justify-content-between">';
                                echo '<h6 class="mb-1">' . htmlspecialchars($activity['action']) . '</h6>';
                                echo '<small>' . format_datetime($activity['created_at']) . '</small>';
                                echo '</div>';
                                if ($activity['full_name']) {
                                    echo '<p class="mb-1">By: ' . htmlspecialchars($activity['full_name']) . '</p>';
                                }
                                if ($activity['details']) {
                                    echo '<small>' . htmlspecialchars($activity['details']) . '</small>';
                                }
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="text-danger">Error loading recent activity.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>
