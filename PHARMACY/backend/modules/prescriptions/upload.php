<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

// Handle prescription upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'upload_prescription') {
        $customer_name = sanitize_input($_POST['customer_name']);
        $customer_phone = sanitize_input($_POST['customer_phone']);
        $customer_email = sanitize_input($_POST['customer_email']);
        
        // Validate required fields
        if (empty($customer_name) || empty($customer_phone)) {
            $error = 'Customer name and phone are required.';
        } elseif (!isset($_FILES['prescription_file']) || $_FILES['prescription_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please select a valid prescription file.';
        } else {
            $file = $_FILES['prescription_file'];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Only JPEG, PNG, GIF, and PDF files are allowed.';
            } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                $error = 'File size must be less than 5MB.';
            } else {
                try {
                    // Create customer if not exists
                    $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
                    $stmt->execute([$customer_phone]);
                    $customer = $stmt->fetch();
                    
                    if (!$customer) {
                        $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
                        $stmt->execute([$customer_name, $customer_phone, $customer_email]);
                        $customer_id = $pdo->lastInsertId();
                    } else {
                        $customer_id = $customer['id'];
                    }
                    
                    // Create upload directory if not exists
                    $upload_dir = __DIR__ . '/../../storage/prescriptions/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'prescription_' . $customer_id . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        // Save prescription record
                        $stmt = $pdo->prepare("INSERT INTO prescriptions (customer_id, file_path, status) VALUES (?, ?, 'Pending')");
                        $stmt->execute([$customer_id, $filename]);
                        
                        log_activity($_SESSION['user']['id'], 'Upload Prescription', "Uploaded prescription for customer: $customer_name");
                        header('Location: review.php?success=Prescription uploaded successfully');
                        exit;
                    } else {
                        $error = 'Failed to upload file.';
                    }
                } catch (PDOException $e) {
                    $error = 'Failed to save prescription: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'Upload Prescription';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="review.php">Prescriptions</a></li>
                    <li class="breadcrumb-item active">Upload Prescription</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-cloud-upload"></i> Upload Prescription</h2>
                <a href="review.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Review
                </a>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-file-medical"></i> Prescription Upload Form</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="upload_prescription">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Customer Name *</label>
                                    <input type="text" name="customer_name" class="form-control" required>
                                    <div class="invalid-feedback">Please enter customer name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" name="customer_phone" class="form-control" required>
                                    <div class="invalid-feedback">Please enter phone number.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="customer_email" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Prescription File *</label>
                            <input type="file" name="prescription_file" class="form-control" 
                                   accept="image/*,.pdf" required>
                            <div class="form-text">
                                Supported formats: JPEG, PNG, GIF, PDF (Max size: 5MB)
                            </div>
                            <div class="invalid-feedback">Please select a prescription file.</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Upload Guidelines</h6>
                            <ul class="mb-0">
                                <li>Ensure the prescription is clearly visible and readable</li>
                                <li>File should be in good quality (not blurry or dark)</li>
                                <li>Include all pages if the prescription is multi-page</li>
                                <li>Only upload prescriptions from licensed doctors</li>
                            </ul>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-cloud-upload"></i> Upload Prescription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// File size validation
document.querySelector('input[name="prescription_file"]').addEventListener('change', function() {
    const file = this.files[0];
    if (file && file.size > 5 * 1024 * 1024) {
        this.setCustomValidity('File size must be less than 5MB');
    } else {
        this.setCustomValidity('');
    }
});

// Preview uploaded file
document.querySelector('input[name="prescription_file"]').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You can add image preview here if needed
            console.log('File selected:', file.name, 'Size:', file.size);
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
