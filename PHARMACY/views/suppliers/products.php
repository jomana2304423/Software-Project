<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role('Supplier');

$config = require __DIR__.'/../../config/config.php';

// Get supplier ID
$supplier_id = get_supplier_id_by_user($_SESSION['user']['id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                $name = trim($_POST['name'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $unit_price = floatval($_POST['unit_price'] ?? 0);
                $available_qty = intval($_POST['available_qty'] ?? 0);
                
                if ($name && $unit_price > 0) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO medicines (name, category, description, reorder_level) 
                            VALUES (?, ?, ?, 0)
                        ");
                        $stmt->execute([$name, $category, $description]);
                        $medicine_id = $pdo->lastInsertId();
                        
                        // Add batch
                        $batch_number = 'SUP' . date('Ymd') . rand(1000, 9999);
                        $expiry_date = date('Y-m-d', strtotime('+2 years'));
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO medicine_batches (medicine_id, batch_number, expiry_date, unit_price, quantity) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$medicine_id, $batch_number, $expiry_date, $unit_price, $available_qty]);
                        
                        $_SESSION['success'] = 'Product added successfully!';
                        header('Location: products.php');
                        exit;
                    } catch (PDOException $e) {
                        $_SESSION['error'] = 'Failed to add product.';
                    }
                }
                break;
                
            case 'update_stock':
                $batch_id = intval($_POST['batch_id'] ?? 0);
                $new_qty = intval($_POST['new_qty'] ?? 0);
                
                if ($batch_id && $new_qty >= 0) {
                    try {
                        $stmt = $pdo->prepare("UPDATE medicine_batches SET quantity = ? WHERE id = ?");
                        $stmt->execute([$new_qty, $batch_id]);
                        $_SESSION['success'] = 'Stock updated successfully!';
                        header('Location: products.php');
                        exit;
                    } catch (PDOException $e) {
                        $_SESSION['error'] = 'Failed to update stock.';
                    }
                }
                break;
        }
    }
}

// Get supplier's products
try {
    $stmt = $pdo->prepare("
        SELECT m.*, mb.id as batch_id, mb.batch_number, mb.expiry_date, mb.unit_price, mb.quantity
        FROM medicines m
        JOIN medicine_batches mb ON m.id = mb.medicine_id
        WHERE mb.supplier_id = ?
        ORDER BY m.name, mb.created_at DESC
    ");
    $stmt->execute([$supplier_id]);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

$page_title = 'My Products';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-box"></i> My Products
                </h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-circle"></i> Add Product
                </button>
            </div>
        </div>
    </div>
    
    <?php if (empty($products)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-box text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No Products Yet</h4>
                        <p class="text-muted">Start by adding your first product to the catalog.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="bi bi-plus-circle"></i> Add Your First Product
                        </button>
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
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Batch Number</th>
                                        <th>Unit Price</th>
                                        <th>Available Qty</th>
                                        <th>Expiry Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <?php if ($product['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($product['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td><?php echo htmlspecialchars($product['batch_number']); ?></td>
                                            <td>$<?php echo number_format($product['unit_price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['quantity'] > 10 ? 'success' : ($product['quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                                    <?php echo $product['quantity']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo format_date($product['expiry_date']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="updateStock(<?php echo $product['batch_id']; ?>, <?php echo $product['quantity']; ?>)">
                                                    <i class="bi bi-pencil"></i> Update Stock
                                                </button>
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_product">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" 
                               placeholder="e.g., Pain Relief, Antibiotic">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unit_price" class="form-label">Unit Price ($)</label>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="available_qty" class="form-label">Available Quantity</label>
                                <input type="number" class="form-control" id="available_qty" name="available_qty" 
                                       min="0" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" name="batch_id" id="update_batch_id">
                    
                    <div class="mb-3">
                        <label for="new_qty" class="form-label">New Quantity</label>
                        <input type="number" class="form-control" id="new_qty" name="new_qty" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateStock(batchId, currentQty) {
    document.getElementById('update_batch_id').value = batchId;
    document.getElementById('new_qty').value = currentQty;
    new bootstrap.Modal(document.getElementById('updateStockModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>



