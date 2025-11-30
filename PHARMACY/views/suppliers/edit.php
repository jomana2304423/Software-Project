<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Admin');

$supplier_id = (int)($_GET['id'] ?? 0);

if (!$supplier_id) {
    header('Location: list.php?error=Invalid supplier ID');
    exit;
}

// Get supplier details
try {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    
    if (!$supplier) {
        header('Location: list.php?error=Supplier not found');
        exit;
    }
} catch (PDOException $e) {
    header('Location: list.php?error=Failed to load supplier');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $contact_name = sanitize_input($_POST['contact_name']);
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email']);
    $address = sanitize_input($_POST['address']);
    
    try {
        $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_name = ?, phone = ?, email = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $contact_name, $phone, $email, $address, $supplier_id]);
        
        log_activity($_SESSION['user']['id'], 'Edit Supplier', "Updated supplier: $name");
        header('Location: list.php?success=Supplier updated successfully');
        exit;
    } catch (PDOException $e) {
        $error = 'Failed to update supplier: ' . $e->getMessage();
    }
}

$page_title = 'Edit Supplier - ' . $supplier['name'];
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="list.php">Suppliers</a></li>
                    <li class="breadcrumb-item active">Edit Supplier</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-pencil"></i> Edit Supplier</h2>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-truck"></i> Supplier Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Supplier Name *</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                                    <div class="invalid-feedback">Please enter supplier name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" name="contact_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($supplier['contact_name']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($supplier['phone']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($supplier['email']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($supplier['address']); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Supplier
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>
