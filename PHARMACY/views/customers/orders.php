<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role('Customer');

$config = require __DIR__.'/../../config/config.php';

// Get customer ID
$customer_id = get_customer_id_by_user($_SESSION['user']['id']);

// Get customer's orders
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name as pharmacist_name
        FROM sales s
        LEFT JOIN users u ON s.pharmacist_id = u.id
        WHERE s.customer_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

$page_title = 'My Orders';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-receipt"></i> My Orders
            </h2>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-receipt text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No Orders Yet</h4>
                        <p class="text-muted">Your order history will appear here after making purchases.</p>
                        <a href="../prescriptions/upload.php" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Upload Prescription
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Pharmacist</th>
                                        <th>Subtotal</th>
                                        <th>Discount</th>
                                        <th>Total</th>
                                        <th>Order Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['invoice_no']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['pharmacist_name']); ?></td>
                                            <td>$<?php echo number_format($order['subtotal'], 2); ?></td>
                                            <td>$<?php echo number_format($order['discount'], 2); ?></td>
                                            <td>
                                                <strong>$<?php echo number_format($order['total'], 2); ?></strong>
                                            </td>
                                            <td><?php echo format_datetime($order['created_at']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    <i class="bi bi-eye"></i> View Details
                                                </button>
                                                <a href="../sales/invoice.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success" target="_blank">
                                                    <i class="bi bi-download"></i> Invoice
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(orderId) {
    // Load order details via AJAX
    fetch(`../sales/order_details.php?id=${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailsContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            alert('Failed to load order details');
        });
}
</script>

<?php include '../../includes/footer.php'; ?>



