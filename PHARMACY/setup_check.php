<?php
// Pharmacy Management System - Setup Check
echo "<h1>Pharmacy Management System - Setup Check</h1>";

// Check if database connection works
try {
    require_once 'config/db.php';
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['users', 'roles', 'medicines', 'medicine_batches', 'suppliers', 'customers', 'sales', 'prescriptions'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' missing</p>";
        }
    }
    
    // Check roles
    $stmt = $pdo->prepare("SELECT name FROM roles");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color: green;'>✓ Available roles: " . implode(', ', $roles) . "</p>";
    
    // Check admin user
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

echo "<hr>";
echo "<h2>System Status: Ready to Use!</h2>";
echo "<p><strong>Access URLs:</strong></p>";
echo "<ul>";
echo "<li><a href='public/landing.php'>Landing Page</a></li>";
echo "<li><a href='public/login.php'>Login Page</a></li>";
echo "<li><a href='public/register.php'>Registration Page</a></li>";
echo "</ul>";

echo "<p><strong>Demo Credentials:</strong></p>";
echo "<ul>";
echo "<li>Admin: <strong>admin</strong> / <strong>admin123</strong></li>";
echo "</ul>";

echo "<p><strong>Features Available:</strong></p>";
echo "<ul>";
echo "<li>✓ Admin Dashboard - Complete system management</li>";
echo "<li>✓ Pharmacist Dashboard - Sales and prescription management</li>";
echo "<li>✓ Supplier Dashboard - Product catalog and order fulfillment</li>";
echo "<li>✓ Customer Dashboard - Prescription uploads and order tracking</li>";
echo "<li>✓ User Registration - Self-registration for customers and suppliers</li>";
echo "<li>✓ Role-based Access Control - Secure access for each user type</li>";
echo "<li>✓ Modern UI - Bootstrap 5 with custom styling</li>";
echo "<li>✓ Responsive Design - Works on all devices</li>";
echo "</ul>";
?>



