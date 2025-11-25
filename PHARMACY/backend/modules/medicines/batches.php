<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

$medicine_id = (int)($_GET['medicine_id'] ?? 0);

// Get medicine details
try {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$medicine_id]);
    $medicine = $stmt->fetch();
    
    if (!$medicine) {
        header('Location: list.php?error=Medicine not found');
        exit;
    }
} catch (PDOException $e) {
    header('Location: list.php?error=Failed to load medicine');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_batch':
                $batch_number = sanitize_input($_POST['batch_number']);
                $expiry_date = $_POST['expiry_date'];
                $unit_price = (float)$_POST['unit_price'];
                $quantity = (int)$_POST['quantity'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO medicine_batches (medicine_id, batch_number, expiry_date, unit_price, quantity) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$medicine_id, $batch_number, $expiry_date, $unit_price, $quantity]);
                    
                    log_activity($_SESSION['user']['id'], 'Add Batch', "Added batch $batch_number for {$medicine['name']}");
                    header('Location: batches.php?medicine_id=' . $medicine_id . '&success=Batch added successfully');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to add batch: ' . $e->getMessage();
                }
                break;
                
            case 'update_batch':
                $batch_id = (int)$_POST['batch_id'];
                $batch_number = sanitize_input($_POST['batch_number']);
                $expiry_date = $_POST['expiry_date'];
                $unit_price = (float)$_POST['unit_price'];
                $quantity = (int)$_POST['quantity'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE medicine_batches SET batch_number = ?, expiry_date = ?, unit_price = ?, quantity = ? WHERE id = ?");
                    $stmt->execute([$batch_number, $expiry_date, $unit_price, $quantity, $batch_id]);
                    
                    log_activity($_SESSION['user']['id'], 'Update Batch', "Updated batch $batch_number for {$medicine['name']}");
                    header('Location: batches.php?medicine_id=' . $medicine_id . '&success=Batch updated successfully');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to update batch: ' . $e->getMessage();
                }
                break;
                
            case 'delete_batch':
                $batch_id = (int)$_POST['batch_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM medicine_batches WHERE id = ?");
                    $stmt->execute([$batch_id]);
                    
                    log_activity($_SESSION['user']['id'], 'Delete Batch', "Deleted batch for {$medicine['name']}");
                    header('Location: batches.php?medicine_id=' . $medicine_id . '&success=Batch deleted successfully');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to delete batch: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get batches for this medicine
try {
    $stmt = $pdo->prepare("
        SELECT mb.*, 
               CASE 
                   WHEN mb.expiry_date <= CURDATE() THEN 'Expired'
                   WHEN mb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
                   ELSE 'Good'
               END as expiry_status
        FROM medicine_batches mb 
        WHERE mb.medicine_id = ? 
        ORDER BY mb.expiry_date ASC
    ");
    $stmt->execute([$medicine_id]);
    $batches = $stmt->fetchAll();
} catch (PDOException $e) {
    $batches = [];
    $error = 'Failed to load batches: ' . $e->getMessage();
}

$page_title = 'Manage Batches - ' . $medicine['name'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="list.php">Medicines</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($medicine['name']); ?></li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-boxes"></i> Manage Batches</h2>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($medicine['name']); ?> - <?php echo htmlspecialchars($medicine['category']); ?></p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBatchModal">
                    <i class="bi bi-plus-circle"></i> Add Batch
                </button>
            </div>
        </div>
    </div>
    
    <!-- Medicine Info Card -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Medicine Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($medicine['name']); ?></p>
                            <p><strong>Category:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($medicine['category']); ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Reorder Level:</strong> <?php echo $medicine['reorder_level']; ?></p>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($medicine['description'] ?: 'No description'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Stock</h5>
                    <h2 class="text-primary">
                        <?php 
                        $total_stock = array_sum(array_column($batches, 'quantity'));
                        echo $total_stock;
                        ?>
                    </h2>
                    <p class="text-muted mb-0"><?php echo count($batches); ?> batch(es)</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Batches Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Medicine Batches</h5>
        </div>
        <div class="card-body">
            <?php if (empty($batches)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-box text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No batches found</h5>
                    <p class="text-muted">Add the first batch for this medicine.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Batch Number</th>
                                <th>Expiry Date</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total Value</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batches as $batch): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($batch['batch_number']); ?></strong></td>
                                <td>
                                    <?php 
                                    $expiry_date = new DateTime($batch['expiry_date']);
                                    $today = new DateTime();
                                    $days_left = $today->diff($expiry_date)->days;
                                    $is_expired = $expiry_date < $today;
                                    $is_expiring = $expiry_date <= $today->modify('+30 days');
                                    ?>
                                    <span class="<?php echo $is_expired ? 'text-danger' : ($is_expiring ? 'text-warning' : 'text-success'); ?>">
                                        <?php echo format_date($batch['expiry_date']); ?>
                                        <br><small>(<?php echo $days_left; ?> days)</small>
                                    </span>
                                </td>
                                <td><?php echo format_currency($batch['unit_price']); ?></td>
                                <td>
                                    <span class="fw-bold <?php echo $batch['quantity'] <= $medicine['reorder_level'] ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $batch['quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo format_currency($batch['unit_price'] * $batch['quantity']); ?></td>
                                <td>
                                    <?php if ($batch['expiry_status'] === 'Expired'): ?>
                                        <span class="badge bg-danger">Expired</span>
                                    <?php elseif ($batch['expiry_status'] === 'Expiring Soon'): ?>
                                        <span class="badge bg-warning">Expiring Soon</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Good</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editBatch(<?php echo htmlspecialchars(json_encode($batch)); ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteBatch(<?php echo $batch['id']; ?>)" title="Delete">
                                            <i class="bi bi-trash"></i>
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

<!-- Add Batch Modal -->
<div class="modal fade" id="addBatchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_batch">
                    
                    <div class="mb-3">
                        <label class="form-label">Batch Number *</label>
                        <input type="text" name="batch_number" class="form-control" required>
                        <div class="invalid-feedback">Please enter batch number.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Expiry Date *</label>
                        <input type="date" name="expiry_date" class="form-control" required>
                        <div class="invalid-feedback">Please select expiry date.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Unit Price (₹) *</label>
                        <input type="number" name="unit_price" class="form-control" step="0.01" min="0" required>
                        <div class="invalid-feedback">Please enter unit price.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantity *</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                        <div class="invalid-feedback">Please enter quantity.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Batch Modal -->
<div class="modal fade" id="editBatchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_batch">
                    <input type="hidden" name="batch_id" id="edit_batch_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Batch Number *</label>
                        <input type="text" name="batch_number" id="edit_batch_number" class="form-control" required>
                        <div class="invalid-feedback">Please enter batch number.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Expiry Date *</label>
                        <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control" required>
                        <div class="invalid-feedback">Please select expiry date.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Unit Price (₹) *</label>
                        <input type="number" name="unit_price" id="edit_unit_price" class="form-control" step="0.01" min="0" required>
                        <div class="invalid-feedback">Please enter unit price.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantity *</label>
                        <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required>
                        <div class="invalid-feedback">Please enter quantity.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBatch(batch) {
    document.getElementById('edit_batch_id').value = batch.id;
    document.getElementById('edit_batch_number').value = batch.batch_number;
    document.getElementById('edit_expiry_date').value = batch.expiry_date;
    document.getElementById('edit_unit_price').value = batch.unit_price;
    document.getElementById('edit_quantity').value = batch.quantity;
    
    new bootstrap.Modal(document.getElementById('editBatchModal')).show();
}

function deleteBatch(batchId) {
    if (confirm('Are you sure you want to delete this batch? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_batch">
            <input type="hidden" name="batch_id" value="${batchId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
