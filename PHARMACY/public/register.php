<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/helpers.php';

$config = require __DIR__.'/../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($role)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already exists.';
                } else {
                    // Get role ID
                    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
                    $stmt->execute([$role]);
                    $role_data = $stmt->fetch();
                    
                    if (!$role_data) {
                        $error = 'Invalid role selected.';
                    } else {
                        // Create user
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            INSERT INTO users (username, password_hash, full_name, email, role_id, is_active) 
                            VALUES (?, ?, ?, ?, ?, 1)
                        ");
                        $stmt->execute([$username, $password_hash, $full_name, $email, $role_data['id']]);
                        
                        // Create customer record if role is Customer
                        if ($role === 'Customer') {
                            $user_id = $pdo->lastInsertId();
                            $stmt = $pdo->prepare("
                                INSERT INTO customers (name, phone, email) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$full_name, $phone, $email]);
                        }
                        
                        // Create supplier record if role is Supplier
                        if ($role === 'Supplier') {
                            $stmt = $pdo->prepare("
                                INSERT INTO suppliers (name, contact_name, phone, email, address) 
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$full_name, $full_name, $phone, $email, $address]);
                        }
                        
                        $success = 'Account created successfully! You can now login.';
                        
                        // Clear form data
                        $_POST = [];
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Pharmacy Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="register-container">
        <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-hospital text-primary" style="font-size: 3rem;"></i>
                            <h2 class="mt-3 mb-1">Create Account</h2>
                            <p class="text-muted">Join our pharmacy management system</p>
                        </div>
                        
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
                            <div class="mb-3">
                                <label for="role" class="form-label">Account Type</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select account type</option>
                                    <option value="Customer" <?php echo (($_POST['role'] ?? '') === 'Customer') ? 'selected' : ''; ?>>Customer</option>
                                    <option value="Supplier" <?php echo (($_POST['role'] ?? '') === 'Supplier') ? 'selected' : ''; ?>>Supplier</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3" id="address-field" style="display: none;">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-person-plus"></i> Create Account
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none">Login here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('role').addEventListener('change', function() {
            const addressField = document.getElementById('address-field');
            if (this.value === 'Supplier') {
                addressField.style.display = 'block';
            } else {
                addressField.style.display = 'none';
            }
        });
        
        // Show address field if supplier is already selected
        if (document.getElementById('role').value === 'Supplier') {
            document.getElementById('address-field').style.display = 'block';
        }
    </script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation to register card
        const registerCard = document.querySelector('.card');
        if (registerCard) {
            registerCard.classList.add('animate-in');
        }
        
        // Add focus effects to form inputs
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentNode.classList.remove('focused');
                }
            });
        });
        
        // Add loading state to register button
        const registerForm = document.querySelector('form');
        if (registerForm) {
            registerForm.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<span class="loading"></span> Creating Account...';
                    submitBtn.disabled = true;
                }
            });
        }
        
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = calculatePasswordStrength(password);
                updatePasswordStrength(strength);
            });
        }
    });
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        return strength;
    }
    
    function updatePasswordStrength(strength) {
        const indicator = document.getElementById('password-strength');
        if (!indicator) return;
        
        const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];
        
        indicator.textContent = strengthText[strength] || 'Very Weak';
        indicator.className = `badge bg-${strengthColors[strength] || 'danger'}`;
    }
    </script>
    
    <style>
    .register-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }
    
    .register-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }
    
    .card {
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
        border: none;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    .form-control {
        border: 2px solid rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-1px);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
    }
    
    .animate-in {
        animation: slideInUp 0.6s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .loading {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
        margin-right: 8px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    </style>
</body>
</html>

