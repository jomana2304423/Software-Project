<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Admin');

$config = require __DIR__.'/../../app/config/config.php';

// Get report statistics
try {
    // Total sales
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total) as total FROM sales");
    $stmt->execute();
    $sales_stats = $stmt->fetch();
    
    // Today's sales
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total) as total FROM sales WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $today_stats = $stmt->fetch();
    
    // This month's sales
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total) as total FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stmt->execute();
    $month_stats = $stmt->fetch();
    
    // Total medicines
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medicines");
    $stmt->execute();
    $medicines_count = $stmt->fetch()['count'];
    
    // Low stock medicines
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM medicines m 
        LEFT JOIN medicine_batches mb ON m.id = mb.medicine_id 
        GROUP BY m.id 
        HAVING COALESCE(SUM(mb.quantity), 0) <= m.reorder_level
    ");
    $stmt->execute();
    $low_stock_count = count($stmt->fetchAll());
    
    // Expiring medicines
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medicine_batches WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute();
    $expiring_count = $stmt->fetch()['count'];
    
    // Top selling medicines
    $stmt = $pdo->prepare("
        SELECT m.name, SUM(si.quantity) as total_qty, SUM(si.line_total) as total_amount
        FROM sale_items si
        JOIN medicine_batches mb ON si.medicine_batch_id = mb.id
        JOIN medicines m ON mb.medicine_id = m.id
        GROUP BY m.id, m.name
        ORDER BY total_qty DESC
        LIMIT 5
    ");
    $stmt->execute();
    $top_medicines = $stmt->fetchAll();
    
    // Sales by day (last 7 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as sale_date, COUNT(*) as count, SUM(total) as total
        FROM sales 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY sale_date DESC
    ");
    $stmt->execute();
    $daily_sales = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load report data: ' . $e->getMessage();
}

$page_title = 'Reports Dashboard';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-graph-up"></i> Reports Dashboard</h2>
                <div class="btn-group">
                    <a href="sales.php" class="btn btn-outline-primary">
                        <i class="bi bi-receipt"></i> Sales Report
                    </a>
                    <a href="inventory.php" class="btn btn-outline-success">
                        <i class="bi bi-boxes"></i> Inventory Report
                    </a>
                    <a href="expiry.php" class="btn btn-outline-warning">
                        <i class="bi bi-calendar-x"></i> Expiry Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $sales_stats['count'] ?? 0; ?></h4>
                            <p class="mb-0">Total Sales</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-receipt" style="font-size: 2rem;"></i>
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
                            <h4><?php echo format_currency($sales_stats['total'] ?? 0); ?></h4>
                            <p class="mb-0">Total Revenue</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-currency-rupee" style="font-size: 2rem;"></i>
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
                            <h4><?php echo $low_stock_count; ?></h4>
                            <p class="mb-0">Low Stock Items</p>
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
                            <h4><?php echo $expiring_count; ?></h4>
                            <p class="mb-0">Expiring Soon</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $today_stats['count'] ?? 0; ?></h4>
                            <p class="mb-0">Today's Sales</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-calendar-day" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo format_currency($today_stats['total'] ?? 0); ?></h4>
                            <p class="mb-0">Today's Revenue</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $month_stats['count'] ?? 0; ?></h4>
                            <p class="mb-0">This Month</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-calendar-month" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-light text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo format_currency($month_stats['total'] ?? 0); ?></h4>
                            <p class="mb-0">Monthly Revenue</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-bar-chart" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Daily Sales Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Sales Trend (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailySalesChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Medicines -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Top Selling Medicines</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_medicines)): ?>
                        <p class="text-muted">No sales data available</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_medicines as $index => $medicine): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($medicine['name']); ?></h6>
                                        <small class="text-muted"><?php echo $medicine['total_qty']; ?> units sold</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo $index + 1; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="sales.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-receipt"></i><br>
                                <strong>Sales Report</strong><br>
                                <small>Detailed sales analysis</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="inventory.php" class="btn btn-outline-success w-100">
                                <i class="bi bi-boxes"></i><br>
                                <strong>Inventory Report</strong><br>
                                <small>Stock levels and alerts</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="expiry.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-calendar-x"></i><br>
                                <strong>Expiry Report</strong><br>
                                <small>Expiring medicines</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="export_csv.php" class="btn btn-outline-info w-100">
                                <i class="bi bi-download"></i><br>
                                <strong>Export Data</strong><br>
                                <small>Download CSV reports</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Sales Chart
const dailySalesData = <?php echo json_encode($daily_sales); ?>;
const labels = dailySalesData.map(item => new Date(item.sale_date).toLocaleDateString()).reverse();
const salesCount = dailySalesData.map(item => item.count).reverse();
const salesAmount = dailySalesData.map(item => parseFloat(item.total || 0)).reverse();

const ctx = document.getElementById('dailySalesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Sales Count',
            data: salesCount,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }, {
            label: 'Sales Amount (₹)',
            data: salesAmount,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});
</script>

<?php include '../../views/footer.php'; ?>
