<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role('Supplier');

$config = require __DIR__.'/../../config/config.php';

// Get supplier ID
$supplier_id = get_supplier_id_by_user($_SESSION['user']['id']);

// Get date range from URL parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Get sales statistics
try {
    // Total sales for the period
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT po.id) as total_orders,
            SUM(CASE WHEN po.status = 'Delivered' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN po.status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN po.status = 'Shipped' THEN 1 ELSE 0 END) as shipped_orders
        FROM purchase_orders po
        WHERE po.supplier_id = ? 
        AND DATE(po.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$supplier_id, $start_date, $end_date]);
    $stats = $stmt->fetch();
    
    // Monthly sales trend (last 6 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(po.created_at, '%Y-%m') as month,
            COUNT(*) as order_count,
            SUM(CASE WHEN po.status = 'Delivered' THEN 1 ELSE 0 END) as completed_count
        FROM purchase_orders po
        WHERE po.supplier_id = ?
        AND po.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(po.created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$supplier_id]);
    $monthly_trend = $stmt->fetchAll();
    
    // Top products by orders
    $stmt = $pdo->prepare("
        SELECT 
            m.name as product_name,
            COUNT(poi.id) as order_count,
            SUM(poi.requested_qty) as total_qty_requested
        FROM purchase_order_items poi
        JOIN medicines m ON poi.medicine_id = m.id
        JOIN purchase_orders po ON poi.purchase_order_id = po.id
        WHERE po.supplier_id = ?
        AND DATE(po.created_at) BETWEEN ? AND ?
        GROUP BY m.id, m.name
        ORDER BY order_count DESC
        LIMIT 10
    ");
    $stmt->execute([$supplier_id, $start_date, $end_date]);
    $top_products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $stats = ['total_orders' => 0, 'completed_orders' => 0, 'pending_orders' => 0, 'shipped_orders' => 0];
    $monthly_trend = [];
    $top_products = [];
}

$page_title = 'Sales Reports';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-graph-up"></i> Sales Reports
                </h2>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $stats['total_orders']; ?></h4>
                            <p class="card-text">Total Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-list-check" style="font-size: 2rem;"></i>
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
                            <h4 class="card-title"><?php echo $stats['completed_orders']; ?></h4>
                            <p class="card-text">Completed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $stats['pending_orders']; ?></h4>
                            <p class="card-text">Pending</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock" style="font-size: 2rem;"></i>
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
                            <h4 class="card-title"><?php echo $stats['shipped_orders']; ?></h4>
                            <p class="card-text">Shipped</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-truck" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Monthly Trend Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Trend (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($monthly_trend)): ?>
                        <p class="text-muted text-center py-4">No data available for the selected period.</p>
                    <?php else: ?>
                        <canvas id="trendChart" width="400" height="200"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Status Distribution -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Status Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if ($stats['total_orders'] > 0): ?>
                        <canvas id="statusChart" width="300" height="300"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No orders in the selected period.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Products -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Products by Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_products)): ?>
                        <p class="text-muted text-center py-4">No product orders in the selected period.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Orders</th>
                                        <th>Total Quantity Requested</th>
                                        <th>Popularity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo $product['order_count']; ?></td>
                                            <td><?php echo $product['total_qty_requested']; ?></td>
                                            <td>
                                                <?php 
                                                $max_orders = max(array_column($top_products, 'order_count'));
                                                $percentage = ($product['order_count'] / $max_orders) * 100;
                                                ?>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%">
                                                        <?php echo round($percentage); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Trend Chart
<?php if (!empty($monthly_trend)): ?>
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendChart = new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("','", array_column($monthly_trend, 'month')) . "'"; ?>],
        datasets: [{
            label: 'Total Orders',
            data: [<?php echo implode(',', array_column($monthly_trend, 'order_count')); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }, {
            label: 'Completed Orders',
            data: [<?php echo implode(',', array_column($monthly_trend, 'completed_count')); ?>],
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
<?php endif; ?>

// Status Distribution Chart
<?php if ($stats['total_orders'] > 0): ?>
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Pending', 'Shipped'],
        datasets: [{
            data: [<?php echo $stats['completed_orders']; ?>, <?php echo $stats['pending_orders']; ?>, <?php echo $stats['shipped_orders']; ?>],
            backgroundColor: [
                'rgb(40, 167, 69)',
                'rgb(255, 193, 7)',
                'rgb(23, 162, 184)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>



