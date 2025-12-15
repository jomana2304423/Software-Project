<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Supplier');

$config = require __DIR__.'/../../app/config/config.php';

// Get supplier-specific statistics
$pending_orders = get_supplier_pending_orders_count();
$completed_orders = get_supplier_completed_orders_count();
$total_products = get_supplier_products_count();

$page_title = 'Supplier Dashboard';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-truck"></i> Supplier Dashboard
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
                            <h4 class="card-title"><?php echo $pending_orders; ?></h4>
                            <p class="card-text">Pending Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock" style="font-size: 2rem;"></i>
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
                            <h4 class="card-title"><?php echo $completed_orders; ?></h4>
                            <p class="card-text">Completed Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $total_products; ?></h4>
                            <p class="card-text">Products Listed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-box" style="font-size: 2rem;"></i>
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
                    <i class="bi bi-box-seam text-primary" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Manage Products</h5>
                    <p class="card-text">Add and update your product catalog</p>
                    <a href="../suppliers/products.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right"></i> Manage Products
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-list-check text-success" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">View Orders</h5>
                    <p class="card-text">Check pending and completed orders</p>
                    <a href="../suppliers/orders.php" class="btn btn-success">
                        <i class="bi bi-arrow-right"></i> View Orders
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up text-info" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Sales Report</h5>
                    <p class="card-text">View your sales performance</p>
                    <a href="../suppliers/reports.php" class="btn btn-info">
                        <i class="bi bi-arrow-right"></i> View Reports
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-person-gear text-warning" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Profile Settings</h5>
                    <p class="card-text">Update your supplier profile</p>
                    <a href="../suppliers/profile.php" class="btn btn-warning">
                        <i class="bi bi-arrow-right"></i> Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Recent Orders
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT po.*, s.name as supplier_name, u.full_name as created_by_name
                            FROM purchase_orders po 
                            JOIN suppliers s ON po.supplier_id = s.id
                            LEFT JOIN users u ON po.created_by = u.id
                            WHERE po.supplier_id = (SELECT id FROM suppliers WHERE contact_name = ?)
                            ORDER BY po.created_at DESC 
                            LIMIT 10
                        ");
                        $stmt->execute([$_SESSION['user']['full_name']]);
                        $orders = $stmt->fetchAll();
                        
                        if (empty($orders)) {
                            echo '<p class="text-muted">No recent orders found.</p>';
                        } else {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-striped">';
                            echo '<thead><tr><th>Order #</th><th>Status</th><th>Created By</th><th>Date</th><th>Actions</th></tr></thead>';
                            echo '<tbody>';
                            foreach ($orders as $order) {
                                $status_class = $order['status'] === 'Pending' ? 'warning' : 
                                             ($order['status'] === 'Delivered' ? 'success' : 'info');
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($order['po_number']) . '</td>';
                                echo '<td><span class="badge bg-' . $status_class . '">' . htmlspecialchars($order['status']) . '</span></td>';
                                echo '<td>' . htmlspecialchars($order['created_by_name']) . '</td>';
                                echo '<td>' . format_datetime($order['created_at']) . '</td>';
                                echo '<td><a href="../suppliers/order_details.php?id=' . $order['id'] . '" class="btn btn-sm btn-outline-primary">View</a></td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="text-danger">Error loading recent orders.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>

