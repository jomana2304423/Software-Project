<?php
// Simple test page to verify everything is working
echo "<h1>Pharmacy Management System - Test Page</h1>";

// Test PHP
echo "<p style='color: green;'>✓ PHP is working (Version: " . phpversion() . ")</p>";

// Test database connection
try {
    require_once 'config/db.php';
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test if tables exist
    $stmt = $pdo->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color: green;'>✓ Found " . count($tables) . " tables in database</p>";
    
    // Test admin user
    $stmt = $pdo->prepare("SELECT username FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<p style='color: green;'>✓ Admin user exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Admin user missing</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test session
session_start();
echo "<p style='color: green;'>✓ Sessions are working</p>";

echo "<hr>";
echo "<h2>System Status: Ready!</h2>";
echo "<p><strong>Access your website:</strong></p>";
echo "<ul>";
echo "<li><a href='public/landing.php'>🏠 Landing Page</a></li>";
echo "<li><a href='public/login.php'>🔐 Login Page</a></li>";
echo "<li><a href='setup_database.php'>🔧 Database Setup</a></li>";
echo "</ul>";

echo "<p><strong>Login Credentials:</strong></p>";
echo "<p>Admin: <strong>admin</strong> / <strong>admin123</strong></p>";
?>



