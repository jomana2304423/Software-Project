<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role('Supplier');

$config = require __DIR__.'/../../config/config.php';

// Get order ID from URL
$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    echo '<div class="alert alert-danger">Invalid order ID.</div>';
    exit;
}

// Get supplier ID
$supplier_id = get_supplier_id_by_user($_SESSION['user']['id']);

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT po.*, s.name as supplier_name, u.full_name as created_by_name
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        LEFT JOIN users u ON po.created_by = u.id
        WHERE po.id = ? AND po.supplier_id = ?
    ");
    $stmt->execute([$order_id, $supplier_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo '<div class="alert alert-danger">Order not found or access denied.</div>';
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT poi.*, m.name as medicine_name, m.category
        FROM purchase_order_items poi
        JOIN medicines m ON poi.medicine_id = m.id
        WHERE poi.purchase_order_id = ?
        ORDER BY m.name
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error loading order details.</div>';
    exit;
}
?>

<div class="modal-header">
    <h5 class="modal-title">Order Details - <?php echo htmlspecialchars($order['po_number']); ?></h5>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6">
            <h6>Order Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Order Number:</strong></td>
                    <td><?php echo htmlspecialchars($order['po_number']); ?></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <?php
                        $status_class = match($order['status']) {
                            'Pending' => 'warning',
                            'Shipped' => 'info',
                            'Delivered' => 'success',
                            'Cancelled' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Created By:</strong></td>
                    <td><?php echo htmlspecialchars($order['created_by_name']); ?></td>
                </tr>
                <tr>
                    <td><strong>Order Date:</strong></td>
                    <td><?php echo format_datetime($order['created_at']); ?></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6>Supplier Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Supplier:</strong></td>
                    <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <hr>
    
    <h6>Order Items</h6>
    <?php if (empty($order_items)): ?>
        <p class="text-muted">No items in this order.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Category</th>
                        <th>Requested Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['medicine_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo $item['requested_qty']; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <?php if ($order['status'] !== 'Delivered' && $order['status'] !== 'Cancelled'): ?>
        <div class="btn-group">
            <?php if ($order['status'] === 'Pending'): ?>
                <button class="btn btn-success" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'Shipped')">
                    <i class="bi bi-truck"></i> Mark as Shipped
                </button>
            <?php endif; ?>
            <?php if ($order['status'] === 'Shipped'): ?>
                <button class="btn btn-primary" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'Delivered')">
                    <i class="bi bi-check-circle"></i> Mark as Delivered
                </button>
            <?php endif; ?>
            <?php if ($order['status'] !== 'Delivered'): ?>
                <button class="btn btn-danger" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'Cancelled')">
                    <i class="bi bi-x-circle"></i> Cancel Order
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function updateOrderStatus(orderId, status) {
    if (confirm('Are you sure you want to update this order status to ' + status + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'orders.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_status';
        
        const orderIdInput = document.createElement('input');
        orderIdInput.type = 'hidden';
        orderIdInput.name = 'order_id';
        orderIdInput.value = orderId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'new_status';
        statusInput.value = status;
        
        form.appendChild(actionInput);
        form.appendChild(orderIdInput);
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>



