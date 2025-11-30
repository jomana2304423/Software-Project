<?php
session_start();
require_once 'config/db.php';
require_once 'models/auth.php';
require_once 'models/helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if (login($username, $password)) {
            // Redirect based on role
            $role = $_SESSION['user']['role'] ?? '';
            switch ($role) {
                case 'Admin':
                    header('Location: ../views/dashboard/admin.php');
                    exit;
                case 'Pharmacist':
                    header('Location: ../views/dashboard/pharmacist.php');
                    exit;
                case 'Supplier':
                    header('Location: ../views/dashboard/supplier.php');
                    exit;
                case 'Customer':
                    header('Location: ../views/dashboard/customer.php');
                    exit;
                default:
                    header('Location: login.php');
                    exit;
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="text-center mb-4">Simple Login Test</h4>
                        
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
                        <ul class="small">
                            <li>admin / admin123 (Admin)</li>
                            <li>customer1 / customer123 (Customer)</li>
                            <li>supplier1 / supplier123 (Supplier)</li>
                            <li>pharmacist1 / pharmacist123 (Pharmacist)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


