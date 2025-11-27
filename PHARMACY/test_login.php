<?php
// Login Test Page
session_start();

echo "<h1>Login Test</h1>";

if (isset($_POST['test_login'])) {
    require_once 'includes/auth.php';
    require_once 'includes/helpers.php';
    
    $username = 'admin';
    $password = 'admin123';
    
    if (login($username, $password)) {
        echo "<p style='color: green;'>✓ Login successful!</p>";
        echo "<p>User: " . $_SESSION['user']['full_name'] . "</p>";
        echo "<p>Role: " . $_SESSION['user']['role'] . "</p>";
        echo "<p><a href='public/index.php'>Go to Dashboard</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Login failed</p>";
    }
}

if (empty($_SESSION['user'])) {
    echo "<form method='POST'>";
    echo "<button type='submit' name='test_login' class='btn btn-primary'>Test Admin Login</button>";
    echo "</form>";
} else {
    echo "<p>Already logged in as: " . $_SESSION['user']['full_name'] . "</p>";
    echo "<p><a href='public/index.php'>Go to Dashboard</a></p>";
}
?>



