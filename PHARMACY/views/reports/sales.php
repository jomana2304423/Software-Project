<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Admin');

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today
$export = $_GET['export'] ?? '';

// Build query
$where_conditions = ["DATE(s.created_at) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

try {
    $sql = "
        SELECT s.*, c.name as customer_name, u.full_name as pharmacist_name,
               GROUP_CONCAT(m.name SEPARATOR ', ') as medicines,
               COUNT(si.id) as item_count
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.pharmacist_id = u.id
        LEFT JOIN sale_items si ON s.id = si.sale_id
        LEFT JOIN medicine_batches mb ON si.medicine_batch_id = mb.id
        LEFT JOIN medicines m ON mb.medicine_id = m.id
        WHERE " . implode(' AND ', $where_conditions) . "
        GROUP BY s.id
        ORDER BY s.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
    
    // Calculate totals
    $total_sales = count($sales);
    $total_amount = array_sum(array_column($sales, 'total'));
    $total_discount = array_sum(array_column($sales, 'discount'));
    
} catch (PDOException $e) {
    $sales = [];
    $error = 'Failed to load sales data: ' . $e->getMessage();
}

// Handle CSV export
if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Invoice No', 'Date', 'Customer', 'Pharmacist', 'Items', 
        'Subtotal', 'Discount', 'Total'
    ]);
    
    // CSV data
    foreach ($sales as $sale) {
        fputcsv($output, [
            $sale['invoice_no'],
            format_date($sale['created_at']),
            $sale['customer_name'] ?: 'Walk-in',
            $sale['pharmacist_name'],
            $sale['item_count'],
            $sale['subtotal'],
            $sale['discount'],
            $sale['total']
        ]);
    }
    
    fclose($output);
    exit;
}

$page_title = 'Sales Report';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reports</a></li>
                    <li class="breadcrumb-item active">Sales Report</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-receipt"></i> Sales Report</h2>
                <div class="btn-group">
                    <a href="?export=csv&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                       class="btn btn-outline-success">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">From Date</label>
                            <input type="date" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">To Date</label>
                            <input type="date" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $total_sales; ?></h4>
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
                            <h4><?php echo format_currency($total_amount); ?></h4>
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
                            <h4><?php echo format_currency($total_discount); ?></h4>
                            <p class="mb-0">Total Discount</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-percent" style="font-size: 2rem;"></i>
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
                            <h4><?php echo $total_sales > 0 ? format_currency($total_amount / $total_sales) : '₹0.00'; ?></h4>
                            <p class="mb-0">Average Sale</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-list-ul"></i> Sales Details 
                <small class="text-muted">(<?php echo format_date($date_from); ?> to <?php echo format_date($date_to); ?>)</small>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($sales)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No sales found</h5>
                    <p class="text-muted">No sales recorded for the selected date range</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Pharmacist</th>
                                <th>Items</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($sale['invoice_no']); ?></strong>
                                </td>
                                <td><?php echo format_datetime($sale['created_at']); ?></td>
                                <td>
                                    <?php if ($sale['customer_name']): ?>
                                        <?php echo htmlspecialchars($sale['customer_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Walk-in</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($sale['pharmacist_name']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $sale['item_count']; ?></span>
                                </td>
                                <td><?php echo format_currency($sale['subtotal']); ?></td>
                                <td>
                                    <?php if ($sale['discount'] > 0): ?>
                                        <span class="text-danger">-<?php echo format_currency($sale['discount']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">₹0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo format_currency($sale['total']); ?></strong>
                                </td>
                                <td>
                                    <a href="../sales/invoice.php?id=<?php echo $sale['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="View Invoice">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5">Total</th>
                                <th><?php echo format_currency($total_amount + $total_discount); ?></th>
                                <th class="text-danger">-<?php echo format_currency($total_discount); ?></th>
                                <th><?php echo format_currency($total_amount); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    .btn-group {
        display: none !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
    }
}
</style>

<?php include '../../views/footer.php'; ?>
