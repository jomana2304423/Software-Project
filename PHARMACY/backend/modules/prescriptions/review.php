<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

// Handle prescription review
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'review_prescription':
                $prescription_id = (int)$_POST['prescription_id'];
                $status = $_POST['status'];
                $notes = sanitize_input($_POST['notes'] ?? '');
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE prescriptions 
                        SET status = ?, reviewed_by = ?, reviewed_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$status, $_SESSION['user']['id'], $prescription_id]);
                    
                    log_activity($_SESSION['user']['id'], 'Review Prescription', 
                        "Updated prescription status to: $status");
                    
                    header('Location: review.php?success=Prescription reviewed successfully');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to update prescription: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get prescriptions list
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $sql = "
        SELECT p.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
               u.full_name as reviewed_by_name
        FROM prescriptions p
        JOIN customers c ON p.customer_id = c.id
        LEFT JOIN users u ON p.reviewed_by = u.id
        $where_clause
        ORDER BY p.uploaded_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $prescriptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $prescriptions = [];
    $error = 'Failed to load prescriptions: ' . $e->getMessage();
}

// Get counts
try {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM prescriptions GROUP BY status");
    $stmt->execute();
    $status_counts = $stmt->fetchAll();
    
    $counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
    foreach ($status_counts as $count) {
        $counts[$count['status']] = $count['count'];
    }
} catch (PDOException $e) {
    $counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
}

$page_title = 'Prescription Review';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-file-medical"></i> Prescription Review</h2>
                <div class="btn-group">
                    <a href="upload.php" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Upload Prescription
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Filter -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="review.php" class="btn btn-outline-secondary <?php echo $status_filter === '' ? 'active' : ''; ?>">
                            All (<?php echo array_sum($counts); ?>)
                        </a>
                        <a href="review.php?status=Pending" class="btn btn-outline-warning <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>">
                            Pending (<?php echo $counts['Pending']; ?>)
                        </a>
                        <a href="review.php?status=Approved" class="btn btn-outline-success <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>">
                            Approved (<?php echo $counts['Approved']; ?>)
                        </a>
                        <a href="review.php?status=Rejected" class="btn btn-outline-danger <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>">
                            Rejected (<?php echo $counts['Rejected']; ?>)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Prescriptions List -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($prescriptions)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-medical text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No prescriptions found</h5>
                    <p class="text-muted">
                        <?php if ($status_filter): ?>
                            No prescriptions with status: <?php echo $status_filter; ?>
                        <?php else: ?>
                            No prescriptions uploaded yet
                        <?php endif; ?>
                    </p>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Upload First Prescription
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($prescriptions as $prescription): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($prescription['customer_name']); ?>
                                </h6>
                                <span class="badge bg-<?php 
                                    echo $prescription['status'] === 'Approved' ? 'success' : 
                                        ($prescription['status'] === 'Rejected' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo $prescription['status']; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1">
                                            <strong>Phone:</strong> 
                                            <a href="tel:<?php echo htmlspecialchars($prescription['customer_phone']); ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($prescription['customer_phone']); ?>
                                            </a>
                                        </p>
                                        <?php if ($prescription['customer_email']): ?>
                                        <p class="mb-1">
                                            <strong>Email:</strong> 
                                            <a href="mailto:<?php echo htmlspecialchars($prescription['customer_email']); ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($prescription['customer_email']); ?>
                                            </a>
                                        </p>
                                        <?php endif; ?>
                                        <p class="mb-1">
                                            <strong>Uploaded:</strong> 
                                            <?php echo format_datetime($prescription['uploaded_at']); ?>
                                        </p>
                                        <?php if ($prescription['reviewed_by_name']): ?>
                                        <p class="mb-1">
                                            <strong>Reviewed by:</strong> 
                                            <?php echo htmlspecialchars($prescription['reviewed_by_name']); ?>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Reviewed at:</strong> 
                                            <?php echo format_datetime($prescription['reviewed_at']); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-center">
                                            <?php
                                            $file_path = __DIR__ . '/../../storage/prescriptions/' . $prescription['file_path'];
                                            $file_extension = pathinfo($prescription['file_path'], PATHINFO_EXTENSION);
                                            ?>
                                            <?php if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                <img src="view.php?id=<?php echo $prescription['id']; ?>" 
                                                     class="img-thumbnail" style="max-height: 150px; cursor: pointer;"
                                                     onclick="openImageModal('<?php echo $prescription['id']; ?>')">
                                            <?php else: ?>
                                                <div class="text-center py-3">
                                                    <i class="bi bi-file-pdf text-danger" style="font-size: 3rem;"></i>
                                                    <p class="mt-2">PDF Document</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($prescription['status'] === 'Pending'): ?>
                                <div class="mt-3">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="review_prescription">
                                        <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                                        <div class="btn-group w-100">
                                            <button type="submit" name="status" value="Approved" 
                                                    class="btn btn-success" 
                                                    onclick="return confirm('Approve this prescription?')">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </button>
                                            <button type="submit" name="status" value="Rejected" 
                                                    class="btn btn-danger" 
                                                    onclick="return confirm('Reject this prescription?')">
                                                <i class="bi bi-x-circle"></i> Reject
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Prescription Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" class="img-fluid" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<script>
function openImageModal(prescriptionId) {
    const img = document.getElementById('modalImage');
    img.src = 'view.php?id=' + prescriptionId;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>
