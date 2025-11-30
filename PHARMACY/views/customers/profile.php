<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Customer');

$config = require __DIR__.'/../../app/config/config.php';

// Get customer ID
$customer_id = get_customer_id_by_user($_SESSION['user']['id']);

$error = '';
$success = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name) || empty($email)) {
        $error = 'Name and email are required.';
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
                // Update user information
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $_SESSION['user']['id']]);
                
                // Update customer information
                $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $phone, $email, $customer_id]);
                
                // Update session
                $_SESSION['user']['full_name'] = $full_name;
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

// Get current customer information
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
} catch (PDOException $e) {
    $customer = null;
}

$page_title = 'My Profile';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-person-gear"></i> My Profile
            </h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
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
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($customer['name'] ?? $_SESSION['user']['full_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($customer['email'] ?? $_SESSION['user']['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
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
                            <span class="badge bg-info"><?php echo htmlspecialchars($_SESSION['user']['role']); ?></span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Member Since</label>
                        <p class="mb-0"><?php echo format_datetime($customer['created_at'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../prescriptions/upload.php" class="btn btn-outline-primary">
                            <i class="bi bi-cloud-upload"></i> Upload Prescription
                        </a>
                        <a href="orders.php" class="btn btn-outline-success">
                            <i class="bi bi-receipt"></i> View Orders
                        </a>
                        <a href="../prescriptions/view.php" class="btn btn-outline-info">
                            <i class="bi bi-file-medical"></i> My Prescriptions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>



