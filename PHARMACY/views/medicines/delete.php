<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

$medicine_id = (int)($_GET['id'] ?? 0);

if (!$medicine_id) {
    header('Location: list.php?error=Invalid medicine ID');
    exit;
}

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

// Check if medicine has batches
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medicine_batches WHERE medicine_id = ?");
    $stmt->execute([$medicine_id]);
    $batch_count = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $batch_count = 0;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Delete batches first (foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM medicine_batches WHERE medicine_id = ?");
        $stmt->execute([$medicine_id]);
        
        // Delete medicine
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$medicine_id]);
        
        log_activity($_SESSION['user']['id'], 'Delete Medicine', "Deleted medicine: {$medicine['name']}");
        header('Location: list.php?success=Medicine deleted successfully');
        exit;
    } catch (PDOException $e) {
        $error = 'Failed to delete medicine: ' . $e->getMessage();
    }
}

$page_title = 'Delete Medicine - ' . $medicine['name'];
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="list.php">Medicines</a></li>
                    <li class="breadcrumb-item active">Delete Medicine</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-trash"></i> Delete Medicine</h2>
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
                        <p class="mb-0">This action will permanently delete the medicine and all associated batches. This cannot be undone.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Medicine Details:</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($medicine['name']); ?></p>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($medicine['category']); ?></p>
                            <p><strong>Reorder Level:</strong> <?php echo $medicine['reorder_level']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Impact:</h6>
                            <p><strong>Batches:</strong> <?php echo $batch_count; ?> batch(es) will be deleted</p>
                            <p><strong>Sales Records:</strong> Historical sales data will remain intact</p>
                        </div>
                    </div>
                    
                    <?php if ($batch_count > 0): ?>
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Note</h6>
                        <p class="mb-0">This medicine has <?php echo $batch_count; ?> batch(es). All batches will also be deleted.</p>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="mt-4">
                        <div class="d-flex justify-content-between">
                            <a href="list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Delete Medicine
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>
