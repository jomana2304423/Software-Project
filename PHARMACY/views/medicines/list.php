<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

$config = require __DIR__.'/../../app/config/config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_medicine':
                $name = sanitize_input($_POST['name']);
                $category = sanitize_input($_POST['category']);
                $description = sanitize_input($_POST['description']);
                $reorder_level = (int)$_POST['reorder_level'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO medicines (name, category, description, reorder_level) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $category, $description, $reorder_level]);
                    
                    log_activity($_SESSION['user']['id'], 'Add Medicine', "Added medicine: $name");
                    header('Location: list.php?success=Medicine added successfully');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to add medicine: ' . $e->getMessage();
                }
                break;
                
            case 'add_batch':
                $medicine_id = (int)$_POST['medicine_id'];
                $batch_number = sanitize_input($_POST['batch_number']);
                $expiry_date = $_POST['expiry_date'];
                $unit_price = (float)$_POST['unit_price'];
                $quantity = (int)$_POST['quantity'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO medicine_batches (medicine_id, batch_number, expiry_date, unit_price, quantity) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$medicine_id, $batch_number, $expiry_date, $unit_price, $quantity]);
                    
                    log_activity($_SESSION['user']['id'], 'Add Batch', "Added batch $batch_number for medicine ID $medicine_id");
                    header('Location: batches.php?success=Batch added successfully');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to add batch: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get medicines list
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(m.name LIKE ? OR m.category LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'low_stock') {
    $where_conditions[] = "COALESCE(SUM(mb.quantity), 0) <= m.reorder_level";
}

if ($filter === 'expiring') {
    $where_conditions[] = "mb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $sql = "
        SELECT m.*, 
               COALESCE(SUM(mb.quantity), 0) as total_stock,
               COUNT(mb.id) as batch_count,
               MIN(mb.expiry_date) as nearest_expiry
        FROM medicines m
        LEFT JOIN medicine_batches mb ON m.id = mb.medicine_id
        $where_clause
        GROUP BY m.id
        ORDER BY m.name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll();
} catch (PDOException $e) {
    $medicines = [];
    $error = 'Failed to load medicines: ' . $e->getMessage();
}

$page_title = 'Medicines Management';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-capsule"></i> Medicines Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                    <i class="bi bi-plus-circle"></i> Add Medicine
                </button>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Search medicines..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="btn-group" role="group">
                <a href="list.php" class="btn btn-outline-secondary <?php echo $filter === '' ? 'active' : ''; ?>">All</a>
                <a href="list.php?filter=low_stock" class="btn btn-outline-warning <?php echo $filter === 'low_stock' ? 'active' : ''; ?>">Low Stock</a>
                <a href="list.php?filter=expiring" class="btn btn-outline-danger <?php echo $filter === 'expiring' ? 'active' : ''; ?>">Expiring</a>
            </div>
        </div>
    </div>
    
    <!-- Medicines Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="medicinesTable">
                    <thead>
                        <tr>
                            <th>Medicine Name</th>
                            <th>Category</th>
                            <th>Total Stock</th>
                            <th>Batches</th>
                            <th>Nearest Expiry</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $medicine): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($medicine['name']); ?></strong>
                                <?php if ($medicine['description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($medicine['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($medicine['category']); ?></span>
                            </td>
                            <td>
                                <span class="fw-bold <?php echo $medicine['total_stock'] <= $medicine['reorder_level'] ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $medicine['total_stock']; ?>
                                </span>
                                <small class="text-muted">(Reorder: <?php echo $medicine['reorder_level']; ?>)</small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $medicine['batch_count']; ?></span>
                            </td>
                            <td>
                                <?php if ($medicine['nearest_expiry']): ?>
                                    <?php 
                                    $expiry_date = new DateTime($medicine['nearest_expiry']);
                                    $today = new DateTime();
                                    $days_left = $today->diff($expiry_date)->days;
                                    $is_expiring = $expiry_date <= $today->modify('+30 days');
                                    ?>
                                    <span class="<?php echo $is_expiring ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo format_date($medicine['nearest_expiry']); ?>
                                        <br><small>(<?php echo $days_left; ?> days)</small>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">No batches</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($medicine['total_stock'] <= $medicine['reorder_level']): ?>
                                    <span class="badge bg-warning">Low Stock</span>
                                <?php elseif ($medicine['nearest_expiry'] && $is_expiring): ?>
                                    <span class="badge bg-danger">Expiring Soon</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Good</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $medicine['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="batches.php?medicine_id=<?php echo $medicine['id']; ?>" class="btn btn-outline-info" title="Manage Batches">
                                        <i class="bi bi-boxes"></i>
                                    </a>
                                    <button class="btn btn-outline-danger btn-delete" data-id="<?php echo $medicine['id']; ?>" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
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

<!-- Add Medicine Modal -->
<div class="modal fade" id="addMedicineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Medicine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_medicine">
                    
                    <div class="mb-3">
                        <label class="form-label">Medicine Name *</label>
                        <input type="text" name="name" class="form-control" required>
                        <div class="invalid-feedback">Please enter medicine name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">Select Category</option>
                            <option value="Pain Relief">Pain Relief</option>
                            <option value="Antibiotic">Antibiotic</option>
                            <option value="Diabetes">Diabetes</option>
                            <option value="Gastric">Gastric</option>
                            <option value="Cardiac">Cardiac</option>
                            <option value="Respiratory">Respiratory</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reorder Level *</label>
                        <input type="number" name="reorder_level" class="form-control" min="1" value="20" required>
                        <div class="form-text">Alert when stock falls below this level</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Medicine</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#medicinesTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Delete confirmation
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this medicine? This will also delete all associated batches.')) {
            window.location.href = 'delete.php?id=' + this.dataset.id;
        }
    });
});
</script>

<?php include '../../views/footer.php'; ?>
