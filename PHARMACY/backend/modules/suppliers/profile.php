<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role('Supplier');

$config = require __DIR__.'/../../config/config.php';

// Get supplier ID
$supplier_id = get_supplier_id_by_user($_SESSION['user']['id']);

$error = '';
$success = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($contact_name) || empty($email)) {
        $error = 'Name, contact name, and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            // Check if email is already used by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user']['id']]);
            if ($stmt->fetch()) {
                $error = 'Email is already in use by another account.';
            } else {
                // Update supplier information
                $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_name = ?, phone = ?, email = ?, address = ? WHERE id = ?");
                $stmt->execute([$name, $contact_name, $phone, $email, $address, $supplier_id]);
                
                // Update user information
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$contact_name, $email, $_SESSION['user']['id']]);
                
                // Update session
                $_SESSION['user']['full_name'] = $contact_name;
                $_SESSION['user']['email'] = $email;
                
                // Handle password change if provided
                if (!empty($current_password) && !empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error = 'New passwords do not match.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'New password must be at least 6 characters long.';
                    } else {
                        // Verify current password
                        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user']['id']]);
                        $user = $stmt->fetch();
                        
                        if ($user && password_verify($current_password, $user['password_hash'])) {
                            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                            $stmt->execute([$new_password_hash, $_SESSION['user']['id']]);
                            $success = 'Profile and password updated successfully!';
                        } else {
                            $error = 'Current password is incorrect.';
                        }
                    }
                } else {
                    $success = 'Profile updated successfully!';
                }
            }
        } catch (PDOException $e) {
            $error = 'Failed to update profile. Please try again.';
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// Get current supplier information
try {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
} catch (PDOException $e) {
    $supplier = null;
}

$page_title = 'Supplier Profile';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-person-gear"></i> Supplier Profile
            </h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Company Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($supplier['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                           value="<?php echo htmlspecialchars($supplier['contact_name'] ?? $_SESSION['user']['full_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($supplier['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($supplier['email'] ?? $_SESSION['user']['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr>
                        <h6 class="mb-3">Change Password (Optional)</h6>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Username</label>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Account Type</label>
                        <p class="mb-0">
                            <span class="badge bg-warning"><?php echo htmlspecialchars($_SESSION['user']['role']); ?></span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Member Since</label>
                        <p class="mb-0"><?php echo format_datetime($supplier['created_at'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="bi bi-box"></i> Manage Products
                        </a>
                        <a href="orders.php" class="btn btn-outline-success">
                            <i class="bi bi-list-check"></i> View Orders
                        </a>
                        <a href="reports.php" class="btn btn-outline-info">
                            <i class="bi bi-graph-up"></i> Sales Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>



