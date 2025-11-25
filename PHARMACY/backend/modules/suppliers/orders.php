<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role('Supplier');

$config = require __DIR__.'/../../config/config.php';

// Get supplier ID
$supplier_id = get_supplier_id_by_user($_SESSION['user']['id']);

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    
    if ($order_id && in_array($new_status, ['Pending', 'Shipped', 'Delivered', 'Cancelled'])) {
        try {
            $stmt = $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE id = ? AND supplier_id = ?");
            $stmt->execute([$new_status, $order_id, $supplier_id]);
            $_SESSION['success'] = 'Order status updated successfully!';
            header('Location: orders.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Failed to update order status.';
        }
    }
}

// Get orders for this supplier
try {
    $stmt = $pdo->prepare("
        SELECT po.*, u.full_name as created_by_name, COUNT(poi.id) as item_count
        FROM purchase_orders po
        LEFT JOIN users u ON po.created_by = u.id
        LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
        WHERE po.supplier_id = ?
        GROUP BY po.id
        ORDER BY po.created_at DESC
    ");
    $stmt->execute([$supplier_id]);
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
                <i class="bi bi-list-check"></i> My Orders
            </h2>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No Orders Yet</h4>
                        <p class="text-muted">Orders from the pharmacy will appear here.</p>
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
                                        <th>Order #</th>
                                        <th>Status</th>
                                        <th>Items</th>
                                        <th>Created By</th>
                                        <th>Order Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['po_number']); ?></strong>
                                            </td>
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
                                            <td>
                                                <span class="badge bg-primary"><?php echo $order['item_count']; ?> items</span>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['created_by_name']); ?></td>
                                            <td><?php echo format_datetime($order['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <?php if ($order['status'] !== 'Delivered' && $order['status'] !== 'Cancelled'): ?>
                                                        <button class="btn btn-sm btn-outline-success dropdown-toggle" 
                                                                data-bs-toggle="dropdown">
                                                            <i class="bi bi-gear"></i> Update
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php if ($order['status'] === 'Pending'): ?>
                                                                <li><a class="dropdown-item" href="#" 
                                                                       onclick="updateStatus(<?php echo $order['id']; ?>, 'Shipped')">
                                                                    <i class="bi bi-truck"></i> Mark as Shipped
                                                                </a></li>
                                                            <?php endif; ?>
                                                            <?php if ($order['status'] === 'Shipped'): ?>
                                                                <li><a class="dropdown-item" href="#" 
                                                                       onclick="updateStatus(<?php echo $order['id']; ?>, 'Delivered')">
                                                                    <i class="bi bi-check-circle"></i> Mark as Delivered
                                                                </a></li>
                                                            <?php endif; ?>
                                                            <?php if ($order['status'] !== 'Delivered'): ?>
                                                                <li><a class="dropdown-item text-danger" href="#" 
                                                                       onclick="updateStatus(<?php echo $order['id']; ?>, 'Cancelled')">
                                                                    <i class="bi bi-x-circle"></i> Cancel Order
                                                                </a></li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
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

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="update_order_id">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">New Status</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="">Select status</option>
                            <option value="Pending">Pending</option>
                            <option value="Shipped">Shipped</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewOrder(orderId) {
    // Load order details via AJAX
    fetch(`order_details.php?id=${orderId}`)
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

function updateStatus(orderId, status) {
    document.getElementById('update_order_id').value = orderId;
    document.getElementById('new_status').value = status;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>



