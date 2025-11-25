<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $reorder_level = (int)$_POST['reorder_level'];
    
    try {
        $stmt = $pdo->prepare("UPDATE medicines SET name = ?, category = ?, description = ?, reorder_level = ? WHERE id = ?");
        $stmt->execute([$name, $category, $description, $reorder_level, $medicine_id]);
        
        log_activity($_SESSION['user']['id'], 'Edit Medicine', "Updated medicine: $name");
        header('Location: list.php?success=Medicine updated successfully');
        exit;
    } catch (PDOException $e) {
        $error = 'Failed to update medicine: ' . $e->getMessage();
    }
}

$page_title = 'Edit Medicine - ' . $medicine['name'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="list.php">Medicines</a></li>
                    <li class="breadcrumb-item active">Edit Medicine</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-pencil"></i> Edit Medicine</h2>
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
                    <h5 class="mb-0"><i class="bi bi-capsule"></i> Medicine Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Medicine Name *</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($medicine['name']); ?>" required>
                                    <div class="invalid-feedback">Please enter medicine name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select">
                                        <option value="">Select Category</option>
                                        <option value="Pain Relief" <?php echo $medicine['category'] === 'Pain Relief' ? 'selected' : ''; ?>>Pain Relief</option>
                                        <option value="Antibiotic" <?php echo $medicine['category'] === 'Antibiotic' ? 'selected' : ''; ?>>Antibiotic</option>
                                        <option value="Diabetes" <?php echo $medicine['category'] === 'Diabetes' ? 'selected' : ''; ?>>Diabetes</option>
                                        <option value="Gastric" <?php echo $medicine['category'] === 'Gastric' ? 'selected' : ''; ?>>Gastric</option>
                                        <option value="Cardiac" <?php echo $medicine['category'] === 'Cardiac' ? 'selected' : ''; ?>>Cardiac</option>
                                        <option value="Respiratory" <?php echo $medicine['category'] === 'Respiratory' ? 'selected' : ''; ?>>Respiratory</option>
                                        <option value="Other" <?php echo $medicine['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($medicine['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Reorder Level *</label>
                                    <input type="number" name="reorder_level" class="form-control" 
                                           min="1" value="<?php echo $medicine['reorder_level']; ?>" required>
                                    <div class="form-text">Alert when stock falls below this level</div>
                                    <div class="invalid-feedback">Please enter reorder level.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Medicine
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
