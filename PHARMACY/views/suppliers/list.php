<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Admin');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_supplier':
                $name = sanitize_input($_POST['name']);
                $contact_name = sanitize_input($_POST['contact_name']);
                $phone = sanitize_input($_POST['phone']);
                $email = sanitize_input($_POST['email']);
                $address = sanitize_input($_POST['address']);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_name, phone, email, address) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $contact_name, $phone, $email, $address]);
                    
                    log_activity($_SESSION['user']['id'], 'Add Supplier', "Added supplier: $name");
                    header('Location: list.php?success=Supplier added successfully');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to add supplier: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get suppliers list
$search = $_GET['search'] ?? '';

try {
    $sql = "SELECT * FROM suppliers";
    $params = [];
    
    if ($search) {
        $sql .= " WHERE name LIKE ? OR contact_name LIKE ? OR phone LIKE ? OR email LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
    }
    
    $sql .= " ORDER BY name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) {
    $suppliers = [];
    $error = 'Failed to load suppliers: ' . $e->getMessage();
}

$page_title = 'Suppliers Management';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-truck"></i> Suppliers Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="bi bi-plus-circle"></i> Add Supplier
                </button>
            </div>
        </div>
    </div>
    
    <!-- Search -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Search suppliers..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
    </div>
    
    <!-- Suppliers Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                                <br><small class="text-muted">Added: <?php echo format_date($supplier['created_at']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($supplier['contact_name'] ?: 'N/A'); ?></td>
                            <td>
                                <?php if ($supplier['phone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($supplier['phone']); ?>" class="text-decoration-none">
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($supplier['phone']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($supplier['email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="text-decoration-none">
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($supplier['email']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($supplier['address']): ?>
                                    <span class="text-muted"><?php echo htmlspecialchars($supplier['address']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-outline-danger btn-delete" data-id="<?php echo $supplier['id']; ?>" title="Delete">
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

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_supplier">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier Name *</label>
                                <input type="text" name="name" class="form-control" required>
                                <div class="invalid-feedback">Please enter supplier name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_name" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#suppliersTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Delete confirmation
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) {
            window.location.href = 'delete.php?id=' + this.dataset.id;
        }
    });
});
</script>

<?php include '../../views/footer.php'; ?>
