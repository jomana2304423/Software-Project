<?php
session_start();

// Simple working login page
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user'] = [
            'id' => 1,
            'username' => 'admin',
            'full_name' => 'System Admin',
            'email' => 'admin@example.com',
            'role' => 'Admin',
            'last_activity' => time()
        ];
        $success = 'Login successful!';
    } else {
        $error = 'Invalid username or password.';
    }
}

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    echo "<h1>Welcome, " . $_SESSION['user']['full_name'] . "!</h1>";
    echo "<p>Role: " . $role . "</p>";
    
    echo "<h2>Dashboard Links:</h2>";
    echo "<ul>";
    echo "<li><a href='views/dashboard/admin.php'>Admin Dashboard</a></li>";
    echo "<li><a href='views/dashboard/pharmacist.php'>Pharmacist Dashboard</a></li>";
    echo "<li><a href='views/dashboard/supplier.php'>Supplier Dashboard</a></li>";
    echo "<li><a href='views/dashboard/customer.php'>Customer Dashboard</a></li>";
    echo "</ul>";
    
    echo "<p><a href='logout.php'>Logout</a></p>";
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login - Pharmacy Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <h2 class="text-center mb-4">Login</h2>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
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
                            
                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    Demo: admin / admin123
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>



