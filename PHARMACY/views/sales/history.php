<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

// Get sales history
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(s.invoice_no LIKE ? OR c.name LIKE ? OR m.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_from) {
    $where_conditions[] = "DATE(s.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(s.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $sql = "
        SELECT s.*, c.name as customer_name, u.full_name as pharmacist_name,
               COUNT(si.id) as item_count
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.pharmacist_id = u.id
        LEFT JOIN sale_items si ON s.id = si.sale_id
        $where_clause
        GROUP BY s.id
        ORDER BY s.created_at DESC
        LIMIT 100
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
} catch (PDOException $e) {
    $sales = [];
    $error = 'Failed to load sales history: ' . $e->getMessage();
}

// Get total sales amount
try {
    $sql = "SELECT SUM(total) as total_amount FROM sales";
    if ($where_conditions) {
        $sql .= " $where_clause";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total_amount = $stmt->fetch()['total_amount'] ?? 0;
} catch (PDOException $e) {
    $total_amount = 0;
}

$page_title = 'Sales History';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-clock-history"></i> Sales History</h2>
                <div class="btn-group">
                    <a href="cart.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Sale
                    </a>
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
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Invoice, customer, or medicine..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo count($sales); ?></h4>
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
                            <p class="mb-0">Total Amount</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-currency-rupee" style="font-size: 2rem;"></i>
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
                            <h4><?php echo format_currency($total_amount / max(count($sales), 1)); ?></h4>
                            <p class="mb-0">Average Sale</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
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
                            <h4><?php echo date('M Y'); ?></h4>
                            <p class="mb-0">Current Month</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-calendar" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Sales</h5>
        </div>
        <div class="card-body">
            <?php if (empty($sales)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No sales found</h5>
                    <p class="text-muted">Start by creating a new sale</p>
                    <a href="cart.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Sale
                    </a>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="invoice.php?id=<?php echo $sale['id']; ?>" 
                                           class="btn btn-outline-primary" title="View Invoice">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button onclick="printInvoice(<?php echo $sale['id']; ?>)" 
                                                class="btn btn-outline-success" title="Print Invoice">
                                            <i class="bi bi-printer"></i>
                                        </button>
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

<script>
function printInvoice(saleId) {
    window.open('invoice.php?id=' + saleId, '_blank');
}
</script>

<?php include '../../views/footer.php'; ?>
