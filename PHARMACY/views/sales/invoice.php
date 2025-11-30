<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

$sale_id = (int)($_GET['id'] ?? 0);

if (!$sale_id) {
    header('Location: history.php?error=Invalid sale ID');
    exit;
}

// Get sale details
try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
               u.full_name as pharmacist_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.pharmacist_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        header('Location: history.php?error=Sale not found');
        exit;
    }
} catch (PDOException $e) {
    header('Location: history.php?error=Failed to load sale details');
    exit;
}

// Get sale items
try {
    $stmt = $pdo->prepare("
        SELECT si.*, m.name as medicine_name, mb.batch_number, mb.expiry_date
        FROM sale_items si
        JOIN medicine_batches mb ON si.medicine_batch_id = mb.id
        JOIN medicines m ON mb.medicine_id = m.id
        WHERE si.sale_id = ?
        ORDER BY si.id
    ");
    $stmt->execute([$sale_id]);
    $sale_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $sale_items = [];
}

$page_title = 'Invoice - ' . $sale['invoice_no'];
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-receipt"></i> Invoice</h2>
                <div class="btn-group">
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="history.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to History
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-body">
                    <!-- Invoice Header -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h3 class="text-primary">Pharmacy Management System</h3>
                            <p class="text-muted mb-0">
                                <strong>Invoice No:</strong> <?php echo htmlspecialchars($sale['invoice_no']); ?><br>
                                <strong>Date:</strong> <?php echo format_datetime($sale['created_at']); ?><br>
                                <strong>Pharmacist:</strong> <?php echo htmlspecialchars($sale['pharmacist_name']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5>Customer Details</h5>
                            <?php if ($sale['customer_name']): ?>
                                <p class="mb-0">
                                    <strong><?php echo htmlspecialchars($sale['customer_name']); ?></strong><br>
                                    <?php if ($sale['customer_phone']): ?>
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($sale['customer_phone']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($sale['customer_email']): ?>
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($sale['customer_email']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <p class="text-muted mb-0">Walk-in Customer</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Invoice Items -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>S.No</th>
                                    <th>Medicine Name</th>
                                    <th>Batch No</th>
                                    <th>Expiry Date</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sale_items as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                                    <td><?php echo format_date($item['expiry_date']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo format_currency($item['unit_price']); ?></td>
                                    <td><?php echo format_currency($item['line_total']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-end">Subtotal:</th>
                                    <th><?php echo format_currency($sale['subtotal']); ?></th>
                                </tr>
                                <?php if ($sale['discount'] > 0): ?>
                                <tr>
                                    <th colspan="6" class="text-end">Discount:</th>
                                    <th class="text-danger">-<?php echo format_currency($sale['discount']); ?></th>
                                </tr>
                                <?php endif; ?>
                                <tr class="table-success">
                                    <th colspan="6" class="text-end">Total Amount:</th>
                                    <th><?php echo format_currency($sale['total']); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Footer -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> Important Notes</h6>
                                <ul class="mb-0">
                                    <li>Please check expiry dates before consumption</li>
                                    <li>Store medicines as per instructions</li>
                                    <li>Consult doctor for any side effects</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="border p-3">
                                <h6>Thank You for Your Purchase!</h6>
                                <p class="text-muted mb-0">Visit us again for quality medicines</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    .container-fluid {
        padding: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .btn-group {
        display: none !important;
    }
    
    body {
        font-size: 12px;
    }
    
    .table {
        font-size: 11px;
    }
    
    h3, h5, h6 {
        color: #000 !important;
    }
}
</style>

<?php include '../../views/footer.php'; ?>
