<?php
// Simple login test page
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "Attempting login for: $username<br>";
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if (login($username, $password)) {
            $success = 'Login successful! Redirecting...';
            echo "Login successful!<br>";
            echo "Session data: " . print_r($_SESSION, true) . "<br>";
            
            // Test redirect
            $role = $_SESSION['user']['role'] ?? '';
            echo "User role: $role<br>";
            
            // Don't redirect in test, just show what would happen
            switch ($role) {
                case 'Admin':
                    echo "Would redirect to: ../modules/dashboard/admin.php<br>";
                    break;
                case 'Pharmacist':
                    echo "Would redirect to: ../modules/dashboard/pharmacist.php<br>";
                    break;
                case 'Supplier':
                    echo "Would redirect to: ../modules/dashboard/supplier.php<br>";
                    break;
                case 'Customer':
                    echo "Would redirect to: ../modules/dashboard/customer.php<br>";
                    break;
                default:
                    echo "Would redirect to: login.php (unknown role)<br>";
                    break;
            }
        } else {
            $error = 'Invalid username or password.';
            echo "Login failed!<br>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4>Login Test</h4>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                        <hr>
                        <h6>Test Credentials:</h6>
                        <ul>
                            <li>admin / admin123 (Admin)</li>
                            <li>customer1 / customer123 (Customer)</li>
                            <li>supplier1 / supplier123 (Supplier)</li>
                            <li>pharmacist1 / pharmacist123 (Pharmacist)</li>
                            <li>karim / karim123 (Customer)</li>
                            <li>karim77 / karim123 (Supplier)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


