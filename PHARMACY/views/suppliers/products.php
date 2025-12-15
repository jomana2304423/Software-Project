<?php
require_once __DIR__ . '/../../models/auth.php';
require_once __DIR__ . '/../../models/rbac.php';
require_once __DIR__ . '/../../models/helpers.php';

require_login();
require_role('Supplier');

$config = require __DIR__ . '/../../app/config/config.php';

// ==========================
// HANDLE FORM SUBMISSIONS
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --------------------------
    // ADD PRODUCT
    // --------------------------
    if ($_POST['action'] === 'add_product') {

        $name          = trim($_POST['name'] ?? '');
        $category      = trim($_POST['category'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $unit_price    = floatval($_POST['unit_price'] ?? 0);
        $available_qty = intval($_POST['available_qty'] ?? 0);

        if ($name && $unit_price > 0) {
            try {
                $pdo->beginTransaction();

                // Insert medicine
                $stmt = $pdo->prepare("
                    INSERT INTO medicines (name, category, description, reorder_level)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$name, $category, $description]);
                $medicine_id = $pdo->lastInsertId();

                // Insert batch (NO supplier_id)
                $batch_number = 'SUP' . date('Ymd') . rand(1000, 9999);
                $expiry_date  = date('Y-m-d', strtotime('+2 years'));

                $stmt = $pdo->prepare("
                    INSERT INTO medicine_batches
                    (medicine_id, batch_number, expiry_date, unit_price, quantity)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $medicine_id,
                    $batch_number,
                    $expiry_date,
                    $unit_price,
                    $available_qty
                ]);

                $pdo->commit();

                $_SESSION['success'] = 'Product added successfully!';
                header('Location: products.php');
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Failed to add product.';
            }
        }
    }

    // --------------------------
    // UPDATE STOCK
    // --------------------------
    if ($_POST['action'] === 'update_stock') {

        $batch_id = intval($_POST['batch_id'] ?? 0);
        $new_qty  = intval($_POST['new_qty'] ?? 0);

        if ($batch_id && $new_qty >= 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE medicine_batches
                    SET quantity = ?
                    WHERE id = ?
                ");
                $stmt->execute([$new_qty, $batch_id]);

                $_SESSION['success'] = 'Stock updated successfully!';
                header('Location: products.php');
                exit;

            } catch (PDOException $e) {
                $_SESSION['error'] = 'Failed to update stock.';
            }
        }
    }
}

// ==========================
// FETCH PRODUCTS (NO SUPPLIER FILTER)
// ==========================
try {
    $stmt = $pdo->query("
        SELECT 
            m.name,
            m.category,
            m.description,
            mb.id AS batch_id,
            mb.batch_number,
            mb.unit_price,
            mb.quantity,
            mb.expiry_date
        FROM medicines m
        JOIN medicine_batches mb ON m.id = mb.medicine_id
        ORDER BY m.name, mb.created_at DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $products = [];
}

$page_title = 'My Products';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box"></i> My Products</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-circle"></i> Add Product
        </button>
    </div>

    <?php if (empty($products)): ?>
        <div class="card text-center py-5">
            <h4 class="text-muted">No Products Yet</h4>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Batch</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Expiry</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                    <?php if ($p['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($p['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['category']) ?></td>
                                <td><?= htmlspecialchars($p['batch_number']) ?></td>
                                <td>$<?= number_format($p['unit_price'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $p['quantity'] > 10 ? 'success' : ($p['quantity'] > 0 ? 'warning' : 'danger') ?>">
                                        <?= $p['quantity'] ?>
                                    </span>
                                </td>
                                <td><?= format_date($p['expiry_date']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"
                                            onclick="updateStock(<?= $p['batch_id'] ?>, <?= $p['quantity'] ?>)">
                                        Update Stock
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ADD PRODUCT MODAL -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5>Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="action" value="add_product">

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input class="form-control" name="name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <input class="form-control" name="category">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description"></textarea>
                </div>

                <div class="row">
                    <div class="col">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="unit_price" required>
                    </div>
                    <div class="col">
                        <label class="form-label">Quantity</label>
                        <input type="number" min="0" class="form-control" name="available_qty" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- UPDATE STOCK MODAL -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5>Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="batch_id" id="update_batch_id">

                <label class="form-label">New Quantity</label>
                <input type="number" min="0" class="form-control" name="new_qty" id="new_qty" required>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateStock(batchId, qty) {
    document.getElementById('update_batch_id').value = batchId;
    document.getElementById('new_qty').value = qty;
    new bootstrap.Modal(document.getElementById('updateStockModal')).show();
}
</script>

<?php include '../../views/footer.php'; ?>
