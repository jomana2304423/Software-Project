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

// Check if supplier has purchase orders
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $order_count = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $order_count = 0;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Delete purchase orders first (foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id IN (SELECT id FROM purchase_orders WHERE supplier_id = ?)");
        $stmt->execute([$supplier_id]);
        
        $stmt = $pdo->prepare("DELETE FROM purchase_orders WHERE supplier_id = ?");
        $stmt->execute([$supplier_id]);
        
        // Delete supplier
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        
        log_activity($_SESSION['user']['id'], 'Delete Supplier', "Deleted supplier: {$supplier['name']}");
        header('Location: list.php?success=Supplier deleted successfully');
        exit;
    } catch (PDOException $e) {
        $error = 'Failed to delete supplier: ' . $e->getMessage();
    }
}

$page_title = 'Delete Supplier - ' . $supplier['name'];
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="list.php">Suppliers</a></li>
                    <li class="breadcrumb-item active">Delete Supplier</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-trash"></i> Delete Supplier</h2>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Confirm Deletion</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="bi bi-exclamation-triangle"></i> Warning!</h5>
                        <p class="mb-0">This action will permanently delete the supplier and all associated purchase orders. This cannot be undone.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Supplier Details:</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($supplier['name']); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($supplier['contact_name'] ?: 'N/A'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($supplier['phone'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Impact:</h6>
                            <p><strong>Purchase Orders:</strong> <?php echo $order_count; ?> order(s) will be deleted</p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($supplier['email'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($order_count > 0): ?>
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Note</h6>
                        <p class="mb-0">This supplier has <?php echo $order_count; ?> purchase order(s). All orders will also be deleted.</p>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="mt-4">
                        <div class="d-flex justify-content-between">
                            <a href="list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Delete Supplier
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>
