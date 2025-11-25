<?php
// Database Import and Test Script
echo "<h1>Database Setup and Test</h1>";

try {
    // Connect to MySQL
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ MySQL connection successful</p>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS pms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✓ Database 'pms_db' created/verified</p>";
    
    // Use the database
    $pdo->exec("USE pms_db");
    
    // Read and execute SQL file
    $sql = file_get_contents('database/pms_db.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|#)/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Skip errors for existing tables/data
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "<p style='color: orange;'>Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✓ Database schema imported successfully</p>";
    
    // Test tables
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
    
    // Test admin user
    $stmt = $pdo->prepare("SELECT username FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<p style='color: green;'>✓ Admin user exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Admin user missing</p>";
    }
    
    // Test roles
    $stmt = $pdo->prepare("SELECT name FROM roles");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color: green;'>✓ Available roles: " . implode(', ', $roles) . "</p>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Database Setup Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='public/landing.php'>Visit Landing Page</a></li>";
    echo "<li><a href='public/login.php'>Login as Admin</a> (admin / admin123)</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please ensure MySQL is running in XAMPP</p>";
}
?>



