<?php
// Login Flow Test
session_start();

echo "<h1>Login Flow Test</h1>";

if (isset($_POST['test_login'])) {
    require_once 'includes/auth.php';
    require_once 'includes/helpers.php';
    
    $username = 'admin';
    $password = 'admin123';
    
    if (login($username, $password)) {
        echo "<p style='color: green;'>✓ Login successful!</p>";
        echo "<p>User: " . $_SESSION['user']['full_name'] . "</p>";
        echo "<p>Role: " . $_SESSION['user']['role'] . "</p>";
        
        // Show what the redirect would be
        $role = $_SESSION['user']['role'];
        switch ($role) {
            case 'Admin':
                $redirect_url = '../modules/dashboard/admin.php';
                break;
            case 'Pharmacist':
                $redirect_url = '../modules/dashboard/pharmacist.php';
                break;
            case 'Supplier':
                $redirect_url = '../modules/dashboard/supplier.php';
                break;
            case 'Customer':
                $redirect_url = '../modules/dashboard/customer.php';
                break;
            default:
                $redirect_url = 'login.php';
        }
        
        echo "<p><strong>Redirect URL:</strong> " . $redirect_url . "</p>";
        echo "<p><strong>Full URL:</strong> http://localhost/pharmacy/modules/dashboard/" . strtolower($role) . ".php</p>";
        
        echo "<p><a href='public/index.php' class='btn btn-success'>Go to Dashboard (via index.php)</a></p>";
        echo "<p><a href='modules/dashboard/" . strtolower($role) . ".php' class='btn btn-primary'>Go Directly to Dashboard</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Login failed</p>";
    }
}

if (empty($_SESSION['user'])) {
    echo "<form method='POST'>";
    echo "<button type='submit' name='test_login' class='btn btn-primary'>Test Admin Login</button>";
    echo "</form>";
} else {
    echo "<p>Already logged in as: " . $_SESSION['user']['full_name'] . " (" . $_SESSION['user']['role'] . ")</p>";
    echo "<p><a href='public/index.php' class='btn btn-success'>Go to Dashboard</a></p>";
}
?>



